<?php

namespace App\Http\Controllers;

use App\Models\Country;
use App\Models\EconomicIndicator;
use App\Models\ExchangeRate;
use App\Models\NewsSentiment;
use App\Models\RiskScore;
use App\Models\Watchlist;
use App\Models\WeatherData;
use App\Services\ExchangeRateService;
use App\Services\NewsService;
use App\Services\WeatherService;
use Carbon\CarbonInterface;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Schema;
use Illuminate\View\View;
use Throwable;

class WatchlistController extends Controller
{
    private const INFLATION_CODE = 'FP.CPI.TOTL.ZG';

    public function index(
        Request $request,
        WeatherService $weatherService,
        ExchangeRateService $exchangeRateService,
        NewsService $newsService
    ): View {
        return view('watchlist.index', $this->buildWatchlistData(
            $request,
            $weatherService,
            $exchangeRateService,
            $newsService
        ));
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'country_id' => ['required', 'integer', 'exists:countries,id'],
        ]);

        Watchlist::query()->firstOrCreate([
            'user_id' => $request->user()->id,
            'country_id' => $validated['country_id'],
        ]);

        return back()
            ->with('success', 'Negara berhasil disimpan ke Favorite Monitoring List.');
    }

    public function destroy(Request $request, Country $country): RedirectResponse
    {
        Watchlist::query()
            ->where('user_id', $request->user()->id)
            ->where('country_id', $country->id)
            ->delete();

        return back()
            ->with('success', 'Negara berhasil dihapus dari Favorite Monitoring List.');
    }

    public function show(
        Request $request,
        WeatherService $weatherService,
        ExchangeRateService $exchangeRateService,
        NewsService $newsService
    ): JsonResponse {
        $data = $this->buildWatchlistData(
            $request,
            $weatherService,
            $exchangeRateService,
            $newsService
        );

        return response()->json([
            'success' => true,
            'message' => 'Data favorit pemantauan berhasil dimuat.',
            'summary' => $data['summary'],
            'watchlist' => $data['watchlist'],
            'chart_data' => $data['chartData'],
            'sync_warnings' => $data['syncWarnings'],
        ]);
    }

    private function buildWatchlistData(
        Request $request,
        WeatherService $weatherService,
        ExchangeRateService $exchangeRateService,
        NewsService $newsService
    ): array {
        $userId = $request->user()->id;
        $forceRefresh = $request->boolean('refresh');
        $syncWarnings = [];

        $favoriteRows = Watchlist::query()
            ->with('country')
            ->where('user_id', $userId)
            ->latest()
            ->get();

        $favoriteCountryIds = $favoriteRows
            ->pluck('country_id')
            ->filter()
            ->unique()
            ->values();

        $availableCountries = Country::query()
            ->whereNotIn('id', $favoriteCountryIds)
            ->orderBy('name')
            ->get();

        $watchlist = $favoriteRows
            ->pluck('country')
            ->filter()
            ->map(function (Country $country) use (
                $weatherService,
                $exchangeRateService,
                $newsService,
                $forceRefresh,
                &$syncWarnings
            ) {
                return $this->buildCountryItem(
                    $country,
                    $weatherService,
                    $exchangeRateService,
                    $newsService,
                    $forceRefresh,
                    $syncWarnings
                );
            })
            ->sortByDesc('risk_score.total_score')
            ->values();

        $summary = $this->buildSummary($watchlist);

        return [
            'summary' => $summary,
            'watchlist' => $watchlist,
            'availableCountries' => $availableCountries,
            'chartData' => $this->buildChartData($watchlist, $summary),
            'syncWarnings' => array_values(array_unique($syncWarnings)),
        ];
    }

    private function buildCountryItem(
        Country $country,
        WeatherService $weatherService,
        ExchangeRateService $exchangeRateService,
        NewsService $newsService,
        bool $forceRefresh,
        array &$syncWarnings
    ): array {
        $latestRiskScore = $this->getLatestRiskScore($country);

        $latestWeather = $this->syncWeather(
            $country,
            $weatherService,
            $forceRefresh,
            $syncWarnings
        );

        $latestExchangeRate = $this->syncCurrency(
            $country,
            $exchangeRateService,
            $forceRefresh,
            $syncWarnings
        );

        $latestInflation = $this->getLatestInflation($country);

        $this->syncNews(
            $country,
            $newsService,
            $forceRefresh,
            $syncWarnings
        );

        $newsSummary = $this->getNewsSummary($country);

        $weatherScore = $latestWeather
            ? round((float) $latestWeather->weather_risk, 2)
            : round((float) ($latestRiskScore?->weather_score ?? 25), 2);

        $inflationScore = $latestInflation
            ? $this->resolveInflationScore((float) $this->getEconomicValue($latestInflation))
            : round((float) ($latestRiskScore?->inflation_score ?? 30), 2);

        $currencyScore = $latestExchangeRate
            ? round((float) $latestExchangeRate->currency_risk, 2)
            : round((float) ($latestRiskScore?->currency_score ?? 20), 2);

        $newsScore = ($newsSummary['average_risk_score'] ?? 0) > 0
            ? round((float) $newsSummary['average_risk_score'], 2)
            : round((float) ($latestRiskScore?->news_score ?? 35), 2);

        $totalScore = round(
            ($weatherScore * 0.30)
            + ($inflationScore * 0.20)
            + ($currencyScore * 0.10)
            + ($newsScore * 0.40),
            2
        );

        $riskLevel = $this->riskLevelFromScore($totalScore);

        $lastUpdate = $this->latestDateDisplay([
            $latestRiskScore?->calculated_at,
            $latestWeather?->recorded_at,
            $latestExchangeRate?->recorded_at,
            $latestInflation?->fetched_at,
            $newsSummary['last_analyzed_at'] ?? null,
        ]);

        return [
            'country' => $this->formatCountry($country),
            'risk_score' => [
                'weather_score' => $weatherScore,
                'inflation_score' => $inflationScore,
                'currency_score' => $currencyScore,
                'news_score' => $newsScore,
                'total_score' => $totalScore,
                'risk_level' => $riskLevel,
                'risk_level_label' => $this->riskLevelLabel($riskLevel),
            ],
            'weather' => [
                'available' => $latestWeather !== null,
                'temperature' => $latestWeather
                    ? round((float) $latestWeather->temperature, 2)
                    : null,
                'precipitation' => $latestWeather
                    ? round((float) $latestWeather->precipitation, 2)
                    : null,
                'wind_speed' => $latestWeather
                    ? round((float) $latestWeather->wind_speed, 2)
                    : null,
                'weather_code' => $latestWeather?->weather_code,
                'condition' => $latestWeather
                    ? $this->weatherCondition((int) $latestWeather->weather_code)
                    : 'Belum tersedia',
                'recorded_at' => $this->dateDisplay($latestWeather?->recorded_at),
            ],
            'currency' => [
                'available' => $latestExchangeRate !== null,
                'base_currency' => $latestExchangeRate?->base_currency,
                'target_currency' => $latestExchangeRate?->target_currency,
                'rate' => $latestExchangeRate
                    ? round((float) $latestExchangeRate->rate, 4)
                    : null,
                'change_percentage' => $latestExchangeRate?->change_percentage !== null
                    ? round((float) $latestExchangeRate->change_percentage, 4)
                    : null,
                'recorded_at' => $this->dateDisplay($latestExchangeRate?->recorded_at),
            ],
            'news' => $newsSummary,
            'economic' => [
                'inflation_available' => $latestInflation !== null,
                'inflation_value' => $latestInflation
                    ? round((float) $this->getEconomicValue($latestInflation), 2)
                    : null,
                'inflation_year' => $latestInflation?->year,
            ],
            'last_update' => $lastUpdate,
        ];
    }

    private function syncWeather(
        Country $country,
        WeatherService $weatherService,
        bool $forceRefresh,
        array &$syncWarnings
    ): ?WeatherData {
        try {
            return $weatherService->getCurrentWeather(
                $country,
                $forceRefresh
            );
        } catch (Throwable $exception) {
            $syncWarnings[] = 'Weather ' . $country->name . ': ' . $exception->getMessage();

            return $this->getLatestWeather($country);
        }
    }

    private function syncCurrency(
        Country $country,
        ExchangeRateService $exchangeRateService,
        bool $forceRefresh,
        array &$syncWarnings
    ): ?ExchangeRate {
        try {
            if (!$country->currency_code) {
                throw new \RuntimeException('Kode mata uang belum tersedia.');
            }

            return $exchangeRateService->getLatestRate(
                $country,
                'USD',
                $forceRefresh
            );
        } catch (Throwable $exception) {
            $syncWarnings[] = 'Currency ' . $country->name . ': ' . $exception->getMessage();

            return $this->getLatestExchangeRate($country);
        }
    }

    private function syncNews(
        Country $country,
        NewsService $newsService,
        bool $forceRefresh,
        array &$syncWarnings
    ): void {
        try {
            $hasNews = NewsSentiment::query()
                ->whereHas('newsCache', function ($query) use ($country) {
                    $query->where('country_id', $country->id);
                })
                ->exists();

            if ($forceRefresh || !$hasNews) {
                $newsService->getLatestNews(
                    $country,
                    $forceRefresh,
                    'all'
                );
            }
        } catch (Throwable $exception) {
            $syncWarnings[] = 'News ' . $country->name . ': ' . $exception->getMessage();
        }
    }

    private function buildSummary(Collection $watchlist): array
    {
        $itemsWithRisk = $watchlist
            ->filter(function ($item) {
                return isset($item['risk_score']['total_score'])
                    && $item['risk_score']['total_score'] !== null;
            })
            ->values();

        $averageRisk = $itemsWithRisk->isNotEmpty()
            ? round((float) $itemsWithRisk->avg('risk_score.total_score'), 2)
            : 0.0;

        $highestRisk = $itemsWithRisk
            ->sortByDesc('risk_score.total_score')
            ->first();

        $riskLevelCounts = [
            'low' => 0,
            'moderate' => 0,
            'high' => 0,
            'critical' => 0,
        ];

        foreach ($watchlist as $item) {
            $level = $item['risk_score']['risk_level'] ?? 'low';

            if (!array_key_exists($level, $riskLevelCounts)) {
                $level = 'low';
            }

            $riskLevelCounts[$level]++;
        }

        return [
            'total_countries' => $watchlist->count(),
            'average_risk_score' => $averageRisk,
            'highest_risk_country' => $highestRisk['country']['name'] ?? 'Belum tersedia',
            'highest_risk_score' => isset($highestRisk['risk_score']['total_score'])
                ? round((float) $highestRisk['risk_score']['total_score'], 2)
                : 0.0,
            'risk_label' => $this->riskScoreLabel($averageRisk),
            'low_count' => $riskLevelCounts['low'],
            'moderate_count' => $riskLevelCounts['moderate'],
            'high_count' => $riskLevelCounts['high'],
            'critical_count' => $riskLevelCounts['critical'],
        ];
    }

    private function buildChartData(
        Collection $watchlist,
        array $summary
    ): array {
        $topRiskItems = $watchlist
            ->sortByDesc('risk_score.total_score')
            ->take(25)
            ->values();

        return [
            'risk_level' => [
                'labels' => [
                    'Risiko Rendah',
                    'Risiko Sedang',
                    'Risiko Tinggi',
                    'Risiko Kritis',
                ],
                'values' => [
                    $summary['low_count'] ?? 0,
                    $summary['moderate_count'] ?? 0,
                    $summary['high_count'] ?? 0,
                    $summary['critical_count'] ?? 0,
                ],
            ],
            'top_risk' => [
                'labels' => $topRiskItems
                    ->map(fn ($item) => $item['country']['iso3_code'] ?? '-')
                    ->values()
                    ->all(),
                'values' => $topRiskItems
                    ->map(fn ($item) => round((float) ($item['risk_score']['total_score'] ?? 0), 2))
                    ->values()
                    ->all(),
            ],
            'summary' => [
                'average_risk_score' => $summary['average_risk_score'] ?? 0,
                'highest_risk_score' => $summary['highest_risk_score'] ?? 0,
            ],
        ];
    }

    private function getLatestRiskScore(Country $country): ?RiskScore
    {
        $query = RiskScore::query()
            ->where('country_id', $country->id);

        if (Schema::hasColumn('risk_scores', 'calculated_at')) {
            return $query->latest('calculated_at')->first();
        }

        return $query->latest()->first();
    }

    private function getLatestWeather(Country $country): ?WeatherData
    {
        return WeatherData::query()
            ->where('country_id', $country->id)
            ->latest('recorded_at')
            ->first();
    }

    private function getLatestExchangeRate(Country $country): ?ExchangeRate
    {
        return ExchangeRate::query()
            ->where('country_id', $country->id)
            ->where('base_currency', 'USD')
            ->when(
                $country->currency_code,
                fn ($query) => $query->where('target_currency', $country->currency_code)
            )
            ->latest('recorded_at')
            ->first();
    }

    private function getLatestInflation(Country $country): ?EconomicIndicator
    {
        return EconomicIndicator::query()
            ->where('country_id', $country->id)
            ->where('indicator_code', self::INFLATION_CODE)
            ->orderByDesc('year')
            ->orderByDesc('fetched_at')
            ->first();
    }

    private function getNewsSummary(Country $country): array
    {
        $sentiments = NewsSentiment::query()
            ->whereHas('newsCache', function ($query) use ($country) {
                $query->where('country_id', $country->id);
            })
            ->latest('analyzed_at')
            ->limit(10)
            ->get();

        $positiveCount = $sentiments
            ->where('sentiment', 'positive')
            ->count();

        $neutralCount = $sentiments
            ->where('sentiment', 'neutral')
            ->count();

        $negativeCount = $sentiments
            ->where('sentiment', 'negative')
            ->count();

        $averageRiskScore = $sentiments->isNotEmpty()
            ? round((float) $sentiments->avg('risk_score'), 2)
            : 35.0;

        $lastAnalyzedAt = $sentiments->max('analyzed_at');

        return [
            'total_articles' => $sentiments->count(),
            'positive_count' => $positiveCount,
            'neutral_count' => $neutralCount,
            'negative_count' => $negativeCount,
            'average_risk_score' => $averageRiskScore,
            'risk_label' => $this->riskLevelLabel(
                $this->riskLevelFromScore($averageRiskScore)
            ),
            'last_analyzed_at' => $lastAnalyzedAt,
            'last_analyzed_at_display' => $this->dateDisplay($lastAnalyzedAt),
        ];
    }

    private function getEconomicValue(?EconomicIndicator $indicator): ?float
    {
        if (!$indicator) {
            return null;
        }

        $value = $indicator->value
            ?? $indicator->raw_value
            ?? null;

        return is_numeric($value)
            ? (float) $value
            : null;
    }

    private function resolveInflationScore(float $inflation): float
    {
        $inflation = abs($inflation);

        return match (true) {
            $inflation <= 3 => 10.0,
            $inflation <= 5 => 25.0,
            $inflation <= 8 => 50.0,
            $inflation <= 12 => 75.0,
            default => 90.0,
        };
    }

    private function riskLevelFromScore(float $score): string
    {
        return match (true) {
            $score >= 75 => 'critical',
            $score >= 50 => 'high',
            $score >= 25 => 'moderate',
            default => 'low',
        };
    }

    private function riskLevelLabel(?string $level): string
    {
        return match ($level) {
            'critical' => 'Risiko Kritis',
            'high' => 'Risiko Tinggi',
            'moderate', 'medium' => 'Risiko Sedang',
            'low' => 'Risiko Rendah',
            default => 'Belum dihitung',
        };
    }

    private function riskScoreLabel(float $score): string
    {
        return $this->riskLevelLabel(
            $this->riskLevelFromScore($score)
        );
    }

    private function weatherCondition(int $weatherCode): string
    {
        return match (true) {
            $weatherCode === 0 => 'Cerah',
            in_array($weatherCode, [1, 2, 3], true) => 'Berawan',
            in_array($weatherCode, [45, 48], true) => 'Berkabut',
            in_array($weatherCode, [51, 53, 55, 56, 57], true) => 'Gerimis',
            in_array($weatherCode, [61, 63, 65, 66, 67], true) => 'Hujan',
            in_array($weatherCode, [80, 81, 82], true) => 'Hujan Lokal',
            in_array($weatherCode, [95, 96, 99], true) => 'Badai',
            default => 'Normal',
        };
    }

    private function formatCountry(Country $country): array
    {
        return [
            'id' => $country->id,
            'name' => $country->name,
            'official_name' => $country->official_name,
            'iso2_code' => $country->iso2_code,
            'iso3_code' => $country->iso3_code,
            'capital' => $country->capital,
            'region' => $country->region,
            'subregion' => $country->subregion,
            'currency_code' => $country->currency_code,
            'currency_name' => $country->currency_name,
            'flag_url' => $country->flag_url,
        ];
    }

    private function latestDateDisplay(array $values): ?string
    {
        $latest = collect($values)
            ->map(fn ($value) => $this->toCarbon($value))
            ->filter()
            ->sortByDesc(fn (CarbonInterface $value) => $value->timestamp)
            ->first();

        return $latest ? $latest->format('d M Y H:i') : null;
    }

    private function dateDisplay(mixed $value): ?string
    {
        $date = $this->toCarbon($value);

        return $date ? $date->format('d M Y H:i') : null;
    }

    private function toCarbon(mixed $value): ?CarbonInterface
    {
        if (!$value) {
            return null;
        }

        if ($value instanceof CarbonInterface) {
            return $value;
        }

        try {
            return Carbon::parse($value);
        } catch (Throwable) {
            return null;
        }
    }
}
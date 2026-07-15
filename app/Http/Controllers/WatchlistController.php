<?php

namespace App\Http\Controllers;

use App\Models\Country;
use App\Models\EconomicIndicator;
use App\Models\ExchangeRate;
use App\Models\NewsCache;
use App\Models\NewsSentiment;
use App\Models\RiskScore;
use App\Models\WeatherData;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\View\View;

class WatchlistController extends Controller
{
    private const INFLATION_CODE = 'FP.CPI.TOTL.ZG';

    public function index(Request $request): View
    {
        return view('watchlist.index', $this->buildWatchlistData());
    }

    public function show(Request $request): JsonResponse
    {
        $data = $this->buildWatchlistData();

        return response()->json([
            'success' => true,
            'message' => 'Data favorit pemantauan berhasil dimuat.',
            'summary' => $data['summary'],
            'watchlist' => $data['watchlist'],
            'chart_data' => $data['chartData'],
        ]);
    }

    private function buildWatchlistData(): array
    {
        $countryIds = $this->getMonitoredCountryIds();

        $countries = Country::query()
            ->whereIn('id', $countryIds)
            ->orderBy('name')
            ->get();

        $watchlist = $countries
            ->map(fn (Country $country) => $this->buildCountryItem($country))
            ->sortByDesc('risk_score.total_score')
            ->values();

        $summary = $this->buildSummary($watchlist);

        return [
            'summary' => $summary,
            'watchlist' => $watchlist,
            'chartData' => $this->buildChartData($watchlist, $summary),
        ];
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

        return [
            'total_countries' => $watchlist->count(),
            'average_risk_score' => $averageRisk,
            'highest_risk_country' => $highestRisk['country']['name'] ?? 'Belum tersedia',
            'highest_risk_score' => isset($highestRisk['risk_score']['total_score'])
                ? round((float) $highestRisk['risk_score']['total_score'], 2)
                : 0.0,
            'risk_label' => $this->riskScoreLabel($averageRisk),
        ];
    }

    private function buildChartData(
        Collection $watchlist,
        array $summary
    ): array {
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
                    $riskLevelCounts['low'],
                    $riskLevelCounts['moderate'],
                    $riskLevelCounts['high'],
                    $riskLevelCounts['critical'],
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

    private function getMonitoredCountryIds(): Collection
    {
        return collect()
            ->merge(RiskScore::query()->whereNotNull('country_id')->distinct()->pluck('country_id'))
            ->merge(WeatherData::query()->whereNotNull('country_id')->distinct()->pluck('country_id'))
            ->merge(ExchangeRate::query()->whereNotNull('country_id')->distinct()->pluck('country_id'))
            ->merge(NewsCache::query()->whereNotNull('country_id')->distinct()->pluck('country_id'))
            ->merge(EconomicIndicator::query()->whereNotNull('country_id')->distinct()->pluck('country_id'))
            ->filter()
            ->unique()
            ->values();
    }

    private function buildCountryItem(Country $country): array
    {
        $latestRiskScore = $this->getLatestRiskScore($country);
        $latestWeather = $this->getLatestWeather($country);
        $latestExchangeRate = $this->getLatestExchangeRate($country);
        $latestInflation = $this->getLatestInflation($country);
        $newsSummary = $this->getNewsSummary($country);

        $weatherScore = $latestWeather
            ? round((float) $latestWeather->weather_risk, 2)
            : round((float) ($latestRiskScore?->weather_score ?? 0), 2);

        $inflationScore = $latestInflation
            ? $this->resolveInflationScore((float) $latestInflation->value)
            : round((float) ($latestRiskScore?->inflation_score ?? 0), 2);

        $currencyScore = $latestExchangeRate
            ? round((float) $latestExchangeRate->currency_risk, 2)
            : round((float) ($latestRiskScore?->currency_score ?? 0), 2);

        $newsScore = ($newsSummary['average_risk_score'] ?? 0) > 0
            ? round((float) $newsSummary['average_risk_score'], 2)
            : round((float) ($latestRiskScore?->news_score ?? 0), 2);

        $hasComponentData = $weatherScore > 0
            || $inflationScore > 0
            || $currencyScore > 0
            || $newsScore > 0;

        $totalScore = $hasComponentData
            ? round(
                ($weatherScore * 0.30)
                + ($inflationScore * 0.20)
                + ($currencyScore * 0.10)
                + ($newsScore * 0.40),
                2
            )
            : round((float) ($latestRiskScore?->total_score ?? 0), 2);

        $riskLevel = $this->riskLevelFromScore($totalScore);

        $timestamps = collect([
            $latestRiskScore?->calculated_at,
            $latestWeather?->recorded_at,
            $latestExchangeRate?->recorded_at,
            $latestInflation?->fetched_at,
            $newsSummary['last_analyzed_at'] ?? null,
        ])->filter();

        $lastUpdate = $timestamps->isNotEmpty()
            ? $timestamps->sortDesc()->first()?->format('d M Y H:i')
            : null;

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
                'recorded_at' => $latestWeather?->recorded_at?->format('d M Y H:i'),
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
                'recorded_at' => $latestExchangeRate?->recorded_at?->format('d M Y H:i'),
            ],
            'news' => $newsSummary,
            'economic' => [
                'inflation_available' => $latestInflation !== null,
                'inflation_value' => $latestInflation
                    ? round((float) $latestInflation->value, 2)
                    : null,
                'inflation_year' => $latestInflation?->year,
            ],
            'last_update' => $lastUpdate,
        ];
    }

    private function getLatestRiskScore(Country $country): ?RiskScore
    {
        return RiskScore::query()
            ->where('country_id', $country->id)
            ->latest('calculated_at')
            ->first();
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
            ->where('target_currency', $country->currency_code)
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
            : 0.0;

        return [
            'total_articles' => $sentiments->count(),
            'positive_count' => $positiveCount,
            'neutral_count' => $neutralCount,
            'negative_count' => $negativeCount,
            'average_risk_score' => $averageRiskScore,
            'risk_label' => $this->riskLevelLabel(
                $this->riskLevelFromScore($averageRiskScore)
            ),
            'last_analyzed_at' => $sentiments->max('analyzed_at'),
            'last_analyzed_at_display' => $sentiments->max('analyzed_at')
                ? $sentiments->max('analyzed_at')->format('d M Y H:i')
                : null,
        ];
    }

    private function resolveInflationScore(float $inflation): float
    {
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
}
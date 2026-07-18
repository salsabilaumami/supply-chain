<?php

namespace App\Http\Controllers;

use App\Models\Country;
use App\Models\EconomicIndicator;
use App\Models\ExchangeRate;
use App\Models\NewsCache;
use App\Models\NewsSentiment;
use App\Models\RiskComponent;
use App\Models\RiskScore;
use App\Models\Watchlist;
use App\Models\WeatherData;
use App\Services\ExchangeRateService;
use App\Services\NewsService;
use App\Services\RiskScoringService;
use App\Services\WeatherService;
use App\Services\WorldBankService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\View\View;
use Throwable;

class CountryMonitoringController extends Controller
{
    private const ECONOMIC_INDICATORS = [
        'gdp' => [
            'code' => 'NY.GDP.MKTP.CD',
            'label' => 'GDP',
            'unit' => 'US$',
        ],
        'inflation' => [
            'code' => 'FP.CPI.TOTL.ZG',
            'label' => 'Inflasi',
            'unit' => '%',
        ],
        'population' => [
            'code' => 'SP.POP.TOTL',
            'label' => 'Populasi',
            'unit' => 'Orang',
        ],
        'exports' => [
            'code' => 'NE.EXP.GNFS.CD',
            'label' => 'Ekspor',
            'unit' => 'US$',
        ],
        'imports' => [
            'code' => 'NE.IMP.GNFS.CD',
            'label' => 'Impor',
            'unit' => 'US$',
        ],
    ];

    public function index(Request $request): View
    {
        $countries = Country::query()
            ->orderBy('name')
            ->get();

        $selectedCountry = $this->resolveSelectedCountry(
            $request,
            $countries
        );

        $isFavorite = false;

        if ($selectedCountry && $request->user()) {
            $isFavorite = Watchlist::query()
                ->where('user_id', $request->user()->id)
                ->where('country_id', $selectedCountry->id)
                ->exists();
        }

        return view('countries.index', [
            'countries' => $countries,
            'selectedCountry' => $selectedCountry,
            'isFavorite' => $isFavorite,
            'economicSummary' => $selectedCountry
                ? $this->getEconomicSummary($selectedCountry)
                : [],
            'weatherSummary' => $selectedCountry
                ? $this->getWeatherSummary($selectedCountry)
                : null,
            'currencySummary' => $selectedCountry
                ? $this->getCurrencySummary($selectedCountry)
                : null,
            'newsSummary' => $selectedCountry
                ? $this->getNewsSummary($selectedCountry)
                : null,
            'riskSummary' => $selectedCountry
                ? $this->getRiskSummary($selectedCountry)
                : null,
        ]);
    }

    public function show(Request $request): JsonResponse
    {
        $countries = Country::query()
            ->orderBy('name')
            ->get();

        $selectedCountry = $this->resolveSelectedCountry(
            $request,
            $countries
        );

        $isFavorite = false;

        if ($selectedCountry && $request->user()) {
            $isFavorite = Watchlist::query()
                ->where('user_id', $request->user()->id)
                ->where('country_id', $selectedCountry->id)
                ->exists();
        }

        return response()->json([
            'success' => true,
            'message' => 'Data negara berhasil dimuat.',
            'selected_country' => $selectedCountry
                ? $this->formatCountry($selectedCountry)
                : null,
            'is_favorite' => $isFavorite,
            'economic_indicators' => $selectedCountry
                ? $this->getEconomicSummary($selectedCountry)
                : [],
            'weather' => $selectedCountry
                ? $this->getWeatherSummary($selectedCountry)
                : null,
            'currency' => $selectedCountry
                ? $this->getCurrencySummary($selectedCountry)
                : null,
            'news' => $selectedCountry
                ? $this->getNewsSummary($selectedCountry)
                : null,
            'risk_score' => $selectedCountry
                ? $this->getRiskSummary($selectedCountry)
                : null,
            'countries' => $countries
                ->map(fn (Country $country) => $this->formatCountry($country))
                ->values(),
        ]);
    }

    public function syncAll(
        Request $request,
        WorldBankService $worldBankService,
        WeatherService $weatherService,
        ExchangeRateService $exchangeRateService,
        NewsService $newsService,
        RiskScoringService $riskScoringService
    ): RedirectResponse {
        $country = $this->findCountryFromRequest($request);

        if (!$country) {
            return redirect()
                ->route('countries.index')
                ->with('error', 'Negara tidak ditemukan.');
        }

        $errors = [];
        $successes = [];

        try {
            $worldBankService->syncCountryIndicators($country);
            $successes[] = 'ekonomi World Bank';
        } catch (Throwable $exception) {
            $errors[] = 'World Bank: ' . $exception->getMessage();
        }

        try {
            $weatherService->getCurrentWeather($country, true);
            $successes[] = 'cuaca Open-Meteo';
        } catch (Throwable $exception) {
            $errors[] = 'Open-Meteo: ' . $exception->getMessage();
        }

        try {
            $exchangeRateService->getLatestRate($country, 'USD', true);
            $successes[] = 'kurs ExchangeRate';
        } catch (Throwable $exception) {
            $errors[] = 'ExchangeRate: ' . $exception->getMessage();
        }

        try {
            $newsService->getLatestNews($country, true);
            $successes[] = 'berita GNews';
        } catch (Throwable $exception) {
            $errors[] = 'GNews: ' . $exception->getMessage();
        }

        try {
            $this->calculateAndStoreRisk(
                $country,
                $riskScoringService
            );

            $successes[] = 'Risk Score';
        } catch (Throwable $exception) {
            $errors[] = 'Risk Score: ' . $exception->getMessage();
        }

        if (!empty($errors)) {
            return redirect()
                ->route('countries.index', ['country' => $country->iso3_code])
                ->with(
                    'error',
                    'Sinkronisasi sebagian berhasil: '
                    . implode(', ', $successes)
                    . '. Namun ada kendala: '
                    . implode(' | ', $errors)
                );
        }

        return redirect()
            ->route('countries.index', ['country' => $country->iso3_code])
            ->with(
                'success',
                'Semua data ' . $country->name
                . ' berhasil disinkronkan dan Risk Score sudah dihitung.'
            );
    }

    public function syncEconomic(
        Request $request,
        WorldBankService $worldBankService
    ): RedirectResponse {
        $country = $this->findCountryFromRequest($request);

        if (!$country) {
            return redirect()
                ->route('countries.index')
                ->with('error', 'Negara tidak ditemukan.');
        }

        try {
            $result = $worldBankService->syncCountryIndicators($country);

            return redirect()
                ->route('countries.index', ['country' => $country->iso3_code])
                ->with(
                    'success',
                    'Data ekonomi ' . $country->name . ' berhasil disinkronkan. '
                    . 'Tersimpan: ' . $result['synced']
                    . ', dilewati: ' . $result['skipped'] . '.'
                );
        } catch (Throwable $exception) {
            return redirect()
                ->route('countries.index', ['country' => $country->iso3_code])
                ->with(
                    'error',
                    'Gagal sinkronisasi data World Bank: '
                    . $exception->getMessage()
                );
        }
    }

    public function syncWeather(
        Request $request,
        WeatherService $weatherService
    ): RedirectResponse {
        $country = $this->findCountryFromRequest($request);

        if (!$country) {
            return redirect()
                ->route('countries.index')
                ->with('error', 'Negara tidak ditemukan.');
        }

        try {
            $weatherService->getCurrentWeather($country, true);

            return redirect()
                ->route('countries.index', ['country' => $country->iso3_code])
                ->with(
                    'success',
                    'Data cuaca ' . $country->name . ' berhasil disinkronkan dari Open-Meteo.'
                );
        } catch (Throwable $exception) {
            return redirect()
                ->route('countries.index', ['country' => $country->iso3_code])
                ->with(
                    'error',
                    'Gagal sinkronisasi data cuaca: '
                    . $exception->getMessage()
                );
        }
    }

    public function syncCurrency(
        Request $request,
        ExchangeRateService $exchangeRateService
    ): RedirectResponse {
        $country = $this->findCountryFromRequest($request);

        if (!$country) {
            return redirect()
                ->route('countries.index')
                ->with('error', 'Negara tidak ditemukan.');
        }

        try {
            $exchangeRateService->getLatestRate($country, 'USD', true);

            return redirect()
                ->route('countries.index', ['country' => $country->iso3_code])
                ->with(
                    'success',
                    'Data kurs ' . $country->name . ' berhasil disinkronkan dari ExchangeRate API.'
                );
        } catch (Throwable $exception) {
            return redirect()
                ->route('countries.index', ['country' => $country->iso3_code])
                ->with(
                    'error',
                    'Gagal sinkronisasi data kurs: '
                    . $exception->getMessage()
                );
        }
    }

    public function syncNews(
        Request $request,
        NewsService $newsService
    ): RedirectResponse {
        $country = $this->findCountryFromRequest($request);

        if (!$country) {
            return redirect()
                ->route('countries.index')
                ->with('error', 'Negara tidak ditemukan.');
        }

        try {
            $news = $newsService->getLatestNews($country, true);

            return redirect()
                ->route('countries.index', ['country' => $country->iso3_code])
                ->with(
                    'success',
                    'Berita ' . $country->name . ' berhasil disinkronkan dari GNews. '
                    . 'Artikel tersimpan: ' . $news->count() . '.'
                );
        } catch (Throwable $exception) {
            return redirect()
                ->route('countries.index', ['country' => $country->iso3_code])
                ->with(
                    'error',
                    'Gagal sinkronisasi berita GNews: '
                    . $exception->getMessage()
                );
        }
    }

    public function calculateRisk(
        Request $request,
        RiskScoringService $riskScoringService
    ): RedirectResponse {
        $country = $this->findCountryFromRequest($request);

        if (!$country) {
            return redirect()
                ->route('countries.index')
                ->with('error', 'Negara tidak ditemukan.');
        }

        try {
            $riskScore = $this->calculateAndStoreRisk(
                $country,
                $riskScoringService
            );

            return redirect()
                ->route('countries.index', ['country' => $country->iso3_code])
                ->with(
                    'success',
                    'Risk Score ' . $country->name . ' berhasil dihitung. '
                    . 'Total: ' . number_format((float) $riskScore->total_score, 2, ',', '.')
                    . ' (' . $this->riskLevelLabel($riskScore->risk_level) . ').'
                );
        } catch (Throwable $exception) {
            return redirect()
                ->route('countries.index', ['country' => $country->iso3_code])
                ->with(
                    'error',
                    'Gagal menghitung Risk Score: '
                    . $exception->getMessage()
                );
        }
    }

    private function calculateAndStoreRisk(
        Country $country,
        RiskScoringService $riskScoringService
    ): RiskScore {
        $weights = $riskScoringService->getWeights();

        $weather = WeatherData::query()
            ->where('country_id', $country->id)
            ->latest('recorded_at')
            ->first();

        $inflation = $this->getLatestEconomicIndicator(
            $country,
            self::ECONOMIC_INDICATORS['inflation']['code']
        );

        $currency = ExchangeRate::query()
            ->where('country_id', $country->id)
            ->where('base_currency', 'USD')
            ->where('target_currency', $country->currency_code)
            ->latest('recorded_at')
            ->first();

        $weatherScore = $weather
            ? (float) $weather->weather_risk
            : 50.0;

        $inflationRaw = $inflation
            ? (float) $inflation->value
            : null;

        $inflationScore = $inflationRaw !== null
            ? $riskScoringService->calculateInflationScore($inflationRaw)
            : 50.0;

        $currencyChange = $currency && $currency->change_percentage !== null
            ? (float) $currency->change_percentage
            : null;

        $currencyScore = $currency
            ? (float) $currency->currency_risk
            : 50.0;

        if ($currency && $currencyChange !== null) {
            $currencyScore = $riskScoringService
                ->calculateCurrencyScore($currencyChange);
        }

        $newsScore = $this->getNewsRiskScore($country);

        $totalScore = $riskScoringService->calculateTotalScore(
            $weatherScore,
            $inflationScore,
            $currencyScore,
            $newsScore
        );

        $riskScore = RiskScore::create([
            'country_id' => $country->id,
            'weather_score' => $weatherScore,
            'inflation_score' => $inflationScore,
            'currency_score' => $currencyScore,
            'news_score' => $newsScore,
            'total_score' => $totalScore,
            'risk_level' => $riskScoringService->determineRiskLevel($totalScore),
            'calculated_at' => now(),
        ]);

        $components = [
            'weather' => [
                'raw_value' => $weather ? (float) $weather->weather_risk : null,
                'normalized_score' => $weatherScore,
                'weight' => $weights['weather'],
            ],
            'inflation' => [
                'raw_value' => $inflationRaw,
                'normalized_score' => $inflationScore,
                'weight' => $weights['inflation'],
            ],
            'currency' => [
                'raw_value' => $currencyChange,
                'normalized_score' => $currencyScore,
                'weight' => $weights['currency'],
            ],
            'news' => [
                'raw_value' => $newsScore,
                'normalized_score' => $newsScore,
                'weight' => $weights['news'],
            ],
        ];

        foreach ($components as $componentName => $component) {
            RiskComponent::create([
                'risk_score_id' => $riskScore->id,
                'component_name' => $componentName,
                'raw_value' => $component['raw_value'],
                'normalized_score' => $component['normalized_score'],
                'weight' => $component['weight'],
                'weighted_score' => round(
                    $component['normalized_score'] * $component['weight'],
                    2
                ),
            ]);
        }

        return $riskScore;
    }

    private function resolveSelectedCountry(
        Request $request,
        Collection $countries
    ): ?Country {
        if ($countries->isEmpty()) {
            return null;
        }

        $selectedIsoCode = strtoupper(
            trim($request->string('country', 'IDN')->toString())
        );

        $selectedCountry = Country::query()
            ->where('iso3_code', $selectedIsoCode)
            ->orWhere('iso2_code', $selectedIsoCode)
            ->first();

        if ($selectedCountry) {
            return $selectedCountry;
        }

        $indonesia = Country::query()
            ->where('iso3_code', 'IDN')
            ->first();

        if ($indonesia) {
            return $indonesia;
        }

        return $countries->first();
    }

    private function findCountryFromRequest(Request $request): ?Country
    {
        $selectedIsoCode = strtoupper(
            trim($request->string('country', 'IDN')->toString())
        );

        return Country::query()
            ->where('iso3_code', $selectedIsoCode)
            ->orWhere('iso2_code', $selectedIsoCode)
            ->first();
    }

    private function getLatestEconomicIndicator(
        Country $country,
        string $indicatorCode
    ): ?EconomicIndicator {
        return EconomicIndicator::query()
            ->where('country_id', $country->id)
            ->where('indicator_code', $indicatorCode)
            ->orderByDesc('year')
            ->orderByDesc('fetched_at')
            ->first();
    }

    private function getEconomicSummary(Country $country): array
    {
        $indicatorCodes = collect(self::ECONOMIC_INDICATORS)
            ->pluck('code')
            ->all();

        $latestIndicators = EconomicIndicator::query()
            ->where('country_id', $country->id)
            ->whereIn('indicator_code', $indicatorCodes)
            ->orderByDesc('year')
            ->orderByDesc('fetched_at')
            ->get()
            ->groupBy('indicator_code')
            ->map(fn (Collection $items) => $items->first());

        $summary = [];

        foreach (self::ECONOMIC_INDICATORS as $key => $meta) {
            $indicator = $latestIndicators->get($meta['code']);

            $value = $indicator
                ? (float) $indicator->value
                : null;

            $summary[$key] = [
                'key' => $key,
                'code' => $meta['code'],
                'label' => $meta['label'],
                'unit' => $meta['unit'],
                'value' => $value,
                'display_value' => $this->formatEconomicValue($key, $value),
                'year' => $indicator?->year,
                'source' => $indicator?->source,
                'fetched_at' => $indicator?->fetched_at?->format('d M Y H:i'),
            ];
        }

        return $summary;
    }

    private function getWeatherSummary(Country $country): array
    {
        $weather = WeatherData::query()
            ->where('country_id', $country->id)
            ->latest('recorded_at')
            ->first();

        if (!$weather) {
            return [
                'available' => false,
                'temperature' => null,
                'precipitation' => null,
                'wind_speed' => null,
                'weather_code' => null,
                'weather_description' => 'Belum tersedia',
                'weather_risk' => null,
                'risk_label' => 'Belum tersedia',
                'recorded_at' => null,
                'fetched_at' => null,
            ];
        }

        $risk = (float) $weather->weather_risk;

        return [
            'available' => true,
            'temperature' => (float) $weather->temperature,
            'precipitation' => (float) $weather->precipitation,
            'wind_speed' => (float) $weather->wind_speed,
            'weather_code' => (int) $weather->weather_code,
            'weather_description' => $this->describeWeatherCode(
                (int) $weather->weather_code
            ),
            'weather_risk' => $risk,
            'risk_label' => $this->riskScoreLabel($risk),
            'recorded_at' => $weather->recorded_at?->format('d M Y H:i'),
            'fetched_at' => $weather->fetched_at?->format('d M Y H:i'),
        ];
    }

    private function getCurrencySummary(Country $country): array
    {
        $exchangeRate = ExchangeRate::query()
            ->where('country_id', $country->id)
            ->where('base_currency', 'USD')
            ->where('target_currency', $country->currency_code)
            ->latest('recorded_at')
            ->first();

        if (!$exchangeRate) {
            return [
                'available' => false,
                'base_currency' => 'USD',
                'target_currency' => $country->currency_code,
                'rate' => null,
                'display_rate' => 'Belum tersedia',
                'change_percentage' => null,
                'display_change' => 'Belum tersedia',
                'currency_risk' => null,
                'risk_label' => 'Belum tersedia',
                'recorded_at' => null,
                'fetched_at' => null,
            ];
        }

        $rate = (float) $exchangeRate->rate;
        $changePercentage = $exchangeRate->change_percentage !== null
            ? (float) $exchangeRate->change_percentage
            : null;
        $currencyRisk = (float) $exchangeRate->currency_risk;

        return [
            'available' => true,
            'base_currency' => $exchangeRate->base_currency,
            'target_currency' => $exchangeRate->target_currency,
            'rate' => $rate,
            'display_rate' => '1 '
                . $exchangeRate->base_currency
                . ' = '
                . number_format($rate, 4, ',', '.')
                . ' '
                . $exchangeRate->target_currency,
            'change_percentage' => $changePercentage,
            'display_change' => $changePercentage !== null
                ? number_format($changePercentage, 4, ',', '.') . '%'
                : 'Belum ada pembanding',
            'currency_risk' => $currencyRisk,
            'risk_label' => $this->riskScoreLabel($currencyRisk),
            'recorded_at' => $exchangeRate->recorded_at?->format('d M Y H:i'),
            'fetched_at' => $exchangeRate->fetched_at?->format('d M Y H:i'),
        ];
    }

    private function getNewsSummary(Country $country): array
    {
        $newsItems = NewsCache::query()
            ->with('sentiment')
            ->where('country_id', $country->id)
            ->latest('published_at')
            ->limit(8)
            ->get();

        $sentiments = $newsItems
            ->pluck('sentiment')
            ->filter();

        $positive = $sentiments
            ->where('sentiment', 'positive')
            ->count();

        $neutral = $sentiments
            ->where('sentiment', 'neutral')
            ->count();

        $negative = $sentiments
            ->where('sentiment', 'negative')
            ->count();

        $averageRisk = $sentiments->isNotEmpty()
            ? round((float) $sentiments->avg('risk_score'), 2)
            : null;

        return [
            'available' => $newsItems->isNotEmpty(),
            'total_articles' => $newsItems->count(),
            'positive_count' => $positive,
            'neutral_count' => $neutral,
            'negative_count' => $negative,
            'average_risk_score' => $averageRisk,
            'display_average_risk_score' => $averageRisk !== null
                ? number_format($averageRisk, 2, ',', '.')
                : 'Belum tersedia',
            'risk_label' => $averageRisk !== null
                ? $this->riskScoreLabel($averageRisk)
                : 'Belum tersedia',
            'items' => $newsItems
                ->map(function (NewsCache $news) {
                    return [
                        'id' => $news->id,
                        'title' => $news->title,
                        'description' => $news->description,
                        'url' => $news->url,
                        'image_url' => $news->image_url ?? null,
                        'source_name' => $news->source_name,
                        'author' => $news->author ?? null,
                        'published_at' => $news->published_at?->format('d M Y H:i'),
                        'sentiment' => $news->sentiment?->sentiment ?? 'neutral',
                        'sentiment_label' => $this->sentimentLabel(
                            $news->sentiment?->sentiment ?? 'neutral'
                        ),
                        'positive_score' => $news->sentiment?->positive_score ?? 0,
                        'negative_score' => $news->sentiment?->negative_score ?? 0,
                        'neutral_score' => $news->sentiment?->neutral_score ?? 0,
                        'risk_score' => $news->sentiment
                            ? (float) $news->sentiment->risk_score
                            : 50.0,
                    ];
                })
                ->values()
                ->all(),
        ];
    }

    private function getRiskSummary(Country $country): array
    {
        $riskScore = RiskScore::query()
            ->with('components')
            ->where('country_id', $country->id)
            ->latest('calculated_at')
            ->first();

        if (!$riskScore) {
            return [
                'available' => false,
                'total_score' => null,
                'display_total_score' => 'Belum dihitung',
                'risk_level' => null,
                'risk_level_label' => 'Belum dihitung',
                'weather_score' => null,
                'inflation_score' => null,
                'currency_score' => null,
                'news_score' => null,
                'calculated_at' => null,
                'components' => [],
            ];
        }

        return [
            'available' => true,
            'total_score' => (float) $riskScore->total_score,
            'display_total_score' => number_format((float) $riskScore->total_score, 2, ',', '.'),
            'risk_level' => $riskScore->risk_level,
            'risk_level_label' => $this->riskLevelLabel($riskScore->risk_level),
            'weather_score' => (float) $riskScore->weather_score,
            'inflation_score' => (float) $riskScore->inflation_score,
            'currency_score' => (float) $riskScore->currency_score,
            'news_score' => (float) $riskScore->news_score,
            'calculated_at' => $riskScore->calculated_at?->format('d M Y H:i'),
            'components' => $riskScore->components
                ->map(function (RiskComponent $component) {
                    return [
                        'component_name' => $component->component_name,
                        'component_label' => $this->componentLabel($component->component_name),
                        'raw_value' => $component->raw_value !== null
                            ? (float) $component->raw_value
                            : null,
                        'normalized_score' => (float) $component->normalized_score,
                        'weight' => (float) $component->weight,
                        'display_weight' => number_format((float) $component->weight * 100, 0, ',', '.') . '%',
                        'weighted_score' => (float) $component->weighted_score,
                    ];
                })
                ->values()
                ->all(),
        ];
    }

    private function getNewsRiskScore(Country $country): float
    {
        $sentiments = NewsSentiment::query()
            ->whereHas('newsCache', function ($query) use ($country) {
                $query->where('country_id', $country->id);
            })
            ->latest('analyzed_at')
            ->limit(10)
            ->get();

        if ($sentiments->isEmpty()) {
            return 50.0;
        }

        return round((float) $sentiments->avg('risk_score'), 2);
    }

    private function formatEconomicValue(
        string $key,
        ?float $value
    ): string {
        if ($value === null) {
            return 'Belum tersedia';
        }

        if ($key === 'inflation') {
            return number_format($value, 2, ',', '.') . '%';
        }

        if ($key === 'population') {
            return number_format($value, 0, ',', '.');
        }

        if (in_array($key, ['gdp', 'exports', 'imports'], true)) {
            if (abs($value) >= 1_000_000_000_000) {
                return 'US$ ' . number_format($value / 1_000_000_000_000, 2, ',', '.') . ' T';
            }

            if (abs($value) >= 1_000_000_000) {
                return 'US$ ' . number_format($value / 1_000_000_000, 2, ',', '.') . ' B';
            }

            if (abs($value) >= 1_000_000) {
                return 'US$ ' . number_format($value / 1_000_000, 2, ',', '.') . ' M';
            }

            return 'US$ ' . number_format($value, 2, ',', '.');
        }

        return number_format($value, 2, ',', '.');
    }

    private function riskScoreLabel(float $score): string
    {
        return match (true) {
            $score >= 75 => 'Risiko Kritis',
            $score >= 50 => 'Risiko Tinggi',
            $score >= 25 => 'Risiko Sedang',
            default => 'Risiko Rendah',
        };
    }

    private function riskLevelLabel(?string $level): string
    {
        return match ($level) {
            'critical' => 'Risiko Kritis',
            'high' => 'Risiko Tinggi',
            'moderate' => 'Risiko Sedang',
            'low' => 'Risiko Rendah',
            default => 'Belum dihitung',
        };
    }

    private function sentimentLabel(string $sentiment): string
    {
        return match ($sentiment) {
            'positive' => 'Positif',
            'negative' => 'Negatif',
            default => 'Netral',
        };
    }

    private function componentLabel(string $component): string
    {
        return match ($component) {
            'weather' => 'Cuaca',
            'inflation' => 'Inflasi',
            'currency' => 'Kurs',
            'news' => 'Berita',
            default => ucfirst($component),
        };
    }

    private function describeWeatherCode(int $code): string
    {
        return match (true) {
            $code === 0 => 'Cerah',
            in_array($code, [1, 2, 3], true) => 'Cerah berawan',
            in_array($code, [45, 48], true) => 'Berkabut',
            in_array($code, [51, 53, 55, 56, 57], true) => 'Gerimis',
            in_array($code, [61, 63, 65, 66, 67], true) => 'Hujan',
            in_array($code, [71, 73, 75, 77], true) => 'Salju',
            in_array($code, [80, 81, 82], true) => 'Hujan lokal',
            in_array($code, [85, 86], true) => 'Hujan salju',
            in_array($code, [95, 96, 99], true) => 'Badai petir',
            default => 'Kode cuaca ' . $code,
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
            'latitude' => $country->latitude,
            'longitude' => $country->longitude,
            'currency' => [
                'code' => $country->currency_code,
                'name' => $country->currency_name,
                'symbol' => $country->currency_symbol,
            ],
            'population' => $country->population,
            'flag_url' => $country->flag_url,
        ];
    }
}
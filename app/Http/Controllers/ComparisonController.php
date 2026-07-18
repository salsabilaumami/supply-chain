<?php

namespace App\Http\Controllers;

use App\Models\Country;
use App\Models\EconomicIndicator;
use App\Models\ExchangeRate;
use App\Models\NewsSentiment;
use App\Models\RiskScore;
use App\Models\WeatherData;
use App\Services\ExchangeRateService;
use App\Services\NewsService;
use App\Services\WeatherService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Throwable;

class ComparisonController extends Controller
{
    private const INDICATOR_CODES = [
        'gdp' => [
            'code' => 'NY.GDP.MKTP.CD',
            'label' => 'GDP',
        ],
        'inflation' => [
            'code' => 'FP.CPI.TOTL.ZG',
            'label' => 'Inflasi',
        ],
        'population' => [
            'code' => 'SP.POP.TOTL',
            'label' => 'Populasi',
        ],
        'exports' => [
            'code' => 'NE.EXP.GNFS.CD',
            'label' => 'Ekspor',
        ],
        'imports' => [
            'code' => 'NE.IMP.GNFS.CD',
            'label' => 'Impor',
        ],
    ];

    public function index(
        Request $request,
        WeatherService $weatherService,
        ExchangeRateService $exchangeRateService,
        NewsService $newsService
    ): View {
        return view('comparison.index', $this->buildComparisonData(
            $request,
            $weatherService,
            $exchangeRateService,
            $newsService
        ));
    }

    public function show(
        Request $request,
        WeatherService $weatherService,
        ExchangeRateService $exchangeRateService,
        NewsService $newsService
    ): JsonResponse {
        $data = $this->buildComparisonData(
            $request,
            $weatherService,
            $exchangeRateService,
            $newsService
        );

        return response()->json([
            'success' => true,
            'message' => 'Data perbandingan negara berhasil dimuat.',
            'country_a' => $data['countryA']
                ? $this->formatCountry($data['countryA'])
                : null,
            'country_b' => $data['countryB']
                ? $this->formatCountry($data['countryB'])
                : null,
            'comparison' => [
                'country_a' => $data['summaryA'],
                'country_b' => $data['summaryB'],
            ],
            'chart_data' => $data['chartData'],
        ]);
    }

    private function buildComparisonData(
        Request $request,
        WeatherService $weatherService,
        ExchangeRateService $exchangeRateService,
        NewsService $newsService
    ): array {
        $countries = Country::query()
            ->alphabetical()
            ->get();

        $countryAIso = strtoupper(trim($request->string('country_a', 'IDN')->toString()));
        $countryBIso = strtoupper(trim($request->string('country_b', 'SGP')->toString()));

        $countryA = Country::query()
            ->byIsoCode($countryAIso)
            ->first();

        if (!$countryA) {
            $countryA = Country::query()
                ->where('iso3_code', 'IDN')
                ->first();
        }

        if (!$countryA) {
            $countryA = $countries->first();
        }

        $countryB = Country::query()
            ->byIsoCode($countryBIso)
            ->first();

        if (!$countryB || ($countryA && $countryB->id === $countryA->id)) {
            $countryB = $countries->first(function (Country $country) use ($countryA) {
                return !$countryA || $country->id !== $countryA->id;
            });
        }

        if (!$countryB) {
            $countryB = $countryA;
        }

        $forceRefresh = $request->boolean('refresh');

        if ($countryA) {
            $this->refreshRealtimeData(
                $countryA,
                $weatherService,
                $exchangeRateService,
                $newsService,
                $forceRefresh
            );
        }

        if ($countryB) {
            $this->refreshRealtimeData(
                $countryB,
                $weatherService,
                $exchangeRateService,
                $newsService,
                $forceRefresh
            );
        }

        $summaryA = $countryA ? $this->getCountrySummary($countryA) : null;
        $summaryB = $countryB ? $this->getCountrySummary($countryB) : null;

        $chartData = $this->buildChartData(
            $countryA,
            $countryB,
            $summaryA,
            $summaryB
        );

        return [
            'countries' => $countries,
            'countryA' => $countryA,
            'countryB' => $countryB,
            'summaryA' => $summaryA,
            'summaryB' => $summaryB,
            'chartData' => $chartData,
            'selectedCountryA' => $countryA,
            'selectedCountryB' => $countryB,
            'countryAData' => $summaryA,
            'countryBData' => $summaryB,
            'comparisonChartData' => $chartData,
            'comparison' => [
                'country_a' => $summaryA,
                'country_b' => $summaryB,
            ],
        ];
    }

    private function refreshRealtimeData(
        Country $country,
        WeatherService $weatherService,
        ExchangeRateService $exchangeRateService,
        NewsService $newsService,
        bool $forceRefresh = false
    ): void {
        try {
            $weatherService->getCurrentWeather($country, $forceRefresh);
        } catch (Throwable) {
            //
        }

        try {
            $exchangeRateService->getLatestRate($country, 'USD', $forceRefresh);
        } catch (Throwable) {
            //
        }

        try {
            $newsService->getLatestNews($country, $forceRefresh);
        } catch (Throwable) {
            //
        }
    }

    private function getCountrySummary(Country $country): array
    {
        $economicData = $this->getEconomicData($country);
        $weatherData = $this->getLatestWeather($country);
        $exchangeRate = $this->getLatestExchangeRate($country);
        $newsSummary = $this->getNewsSummary($country);
        $storedRiskScore = $this->getLatestRiskScore($country);

        $weatherScore = $weatherData && $weatherData->weather_risk !== null
            ? round((float) $weatherData->weather_risk, 2)
            : round((float) ($storedRiskScore?->weather_score ?? 0), 2);

        $inflationValue = $economicData['inflation']['value'] ?? null;

        $inflationScore = $inflationValue !== null
            ? $this->resolveInflationScore((float) $inflationValue)
            : round((float) ($storedRiskScore?->inflation_score ?? 0), 2);

        $currencyScore = $exchangeRate && $exchangeRate->currency_risk !== null
            ? round((float) $exchangeRate->currency_risk, 2)
            : round((float) ($storedRiskScore?->currency_score ?? 0), 2);

        $newsScore = ($newsSummary['average_risk_score'] ?? 0) > 0
            ? round((float) $newsSummary['average_risk_score'], 2)
            : round((float) ($storedRiskScore?->news_score ?? 0), 2);

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
            : round((float) ($storedRiskScore?->total_score ?? 0), 2);

        $riskLevel = $this->riskLevelFromScore($totalScore);

        return [
            'country' => $this->formatCountry($country),
            'economic' => $economicData,
            'weather' => $weatherData
                ? [
                    'temperature' => (float) $weatherData->temperature,
                    'precipitation' => (float) $weatherData->precipitation,
                    'wind_speed' => (float) $weatherData->wind_speed,
                    'weather_code' => (int) $weatherData->weather_code,
                    'weather_risk' => $weatherScore,
                    'recorded_at' => $weatherData->recorded_at?->toDateTimeString(),
                    'fetched_at' => $weatherData->fetched_at?->toDateTimeString(),
                ]
                : null,
            'currency' => $exchangeRate
                ? [
                    'base_currency' => $exchangeRate->base_currency,
                    'target_currency' => $exchangeRate->target_currency,
                    'rate' => (float) $exchangeRate->rate,
                    'change_percentage' => $exchangeRate->change_percentage !== null
                        ? (float) $exchangeRate->change_percentage
                        : null,
                    'currency_risk' => $currencyScore,
                    'recorded_at' => $exchangeRate->recorded_at?->toDateTimeString(),
                    'fetched_at' => $exchangeRate->fetched_at?->toDateTimeString(),
                ]
                : null,
            'news' => $newsSummary,
            'risk_score' => [
                'weather_score' => $weatherScore,
                'inflation_score' => $inflationScore,
                'currency_score' => $currencyScore,
                'news_score' => $newsScore,
                'total_score' => $totalScore,
                'risk_level' => $riskLevel,
                'risk_level_label' => $this->riskLevelLabel($riskLevel),
                'calculated_at' => now()->toDateTimeString(),
                'stored_calculated_at' => $storedRiskScore?->calculated_at?->toDateTimeString(),
            ],
        ];
    }

    private function getEconomicData(Country $country): array
    {
        $indicatorCodes = collect(self::INDICATOR_CODES)
            ->pluck('code')
            ->all();

        $indicators = EconomicIndicator::query()
            ->where('country_id', $country->id)
            ->whereIn('indicator_code', $indicatorCodes)
            ->orderByDesc('year')
            ->orderByDesc('fetched_at')
            ->get()
            ->unique('indicator_code')
            ->keyBy('indicator_code');

        $data = [];

        foreach (self::INDICATOR_CODES as $key => $meta) {
            $indicator = $indicators->get($meta['code']);

            $value = $indicator
                ? (float) $indicator->value
                : null;

            $data[$key] = [
                'label' => $meta['label'],
                'code' => $meta['code'],
                'value' => $value,
                'display_value' => $this->formatEconomicValue($key, $value),
                'year' => $indicator?->year,
                'fetched_at' => $indicator?->fetched_at?->toDateTimeString(),
            ];
        }

        return $data;
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
        if (!$country->currency_code) {
            return null;
        }

        return ExchangeRate::query()
            ->where('country_id', $country->id)
            ->where('base_currency', 'USD')
            ->where('target_currency', $country->currency_code)
            ->latest('recorded_at')
            ->first();
    }

    private function getLatestRiskScore(Country $country): ?RiskScore
    {
        return RiskScore::query()
            ->where('country_id', $country->id)
            ->latest('calculated_at')
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

        $positiveCount = $sentiments->where('sentiment', 'positive')->count();
        $neutralCount = $sentiments->where('sentiment', 'neutral')->count();
        $negativeCount = $sentiments->where('sentiment', 'negative')->count();

        $averageRiskScore = $sentiments->isNotEmpty()
            ? round((float) $sentiments->avg('risk_score'), 2)
            : 0.0;

        return [
            'total_articles' => $sentiments->count(),
            'positive_count' => $positiveCount,
            'neutral_count' => $neutralCount,
            'negative_count' => $negativeCount,
            'average_risk_score' => $averageRiskScore,
            'risk_label' => $this->riskScoreLabel($averageRiskScore),
            'last_analyzed_at' => optional($sentiments->max('analyzed_at'))->toDateTimeString(),
        ];
    }

    private function buildChartData(
        ?Country $countryA,
        ?Country $countryB,
        ?array $summaryA,
        ?array $summaryB
    ): array {
        $countryALabel = $countryA?->iso3_code ?? 'A';
        $countryBLabel = $countryB?->iso3_code ?? 'B';

        return [
            'risk' => [
                'labels' => [
                    $countryALabel,
                    $countryBLabel,
                ],
                'values' => [
                    round((float) ($summaryA['risk_score']['total_score'] ?? 0), 2),
                    round((float) ($summaryB['risk_score']['total_score'] ?? 0), 2),
                ],
            ],
            'economic' => [
                'labels' => [
                    'GDP',
                    'Ekspor',
                    'Impor',
                    'Inflasi',
                    'Populasi',
                ],
                'country_a' => [
                    round((float) ($summaryA['economic']['gdp']['value'] ?? 0) / 1000000000000, 2),
                    round((float) ($summaryA['economic']['exports']['value'] ?? 0) / 1000000000, 2),
                    round((float) ($summaryA['economic']['imports']['value'] ?? 0) / 1000000000, 2),
                    round((float) ($summaryA['economic']['inflation']['value'] ?? 0), 2),
                    round((float) ($summaryA['economic']['population']['value'] ?? 0) / 1000000, 2),
                ],
                'country_b' => [
                    round((float) ($summaryB['economic']['gdp']['value'] ?? 0) / 1000000000000, 2),
                    round((float) ($summaryB['economic']['exports']['value'] ?? 0) / 1000000000, 2),
                    round((float) ($summaryB['economic']['imports']['value'] ?? 0) / 1000000000, 2),
                    round((float) ($summaryB['economic']['inflation']['value'] ?? 0), 2),
                    round((float) ($summaryB['economic']['population']['value'] ?? 0) / 1000000, 2),
                ],
                'country_a_label' => $countryALabel,
                'country_b_label' => $countryBLabel,
            ],
            'operational' => [
                'labels' => [
                    'Cuaca',
                    'Inflasi',
                    'Kurs',
                    'Berita',
                ],
                'country_a' => [
                    round((float) ($summaryA['risk_score']['weather_score'] ?? 0), 2),
                    round((float) ($summaryA['risk_score']['inflation_score'] ?? 0), 2),
                    round((float) ($summaryA['risk_score']['currency_score'] ?? 0), 2),
                    round((float) ($summaryA['risk_score']['news_score'] ?? 0), 2),
                ],
                'country_b' => [
                    round((float) ($summaryB['risk_score']['weather_score'] ?? 0), 2),
                    round((float) ($summaryB['risk_score']['inflation_score'] ?? 0), 2),
                    round((float) ($summaryB['risk_score']['currency_score'] ?? 0), 2),
                    round((float) ($summaryB['risk_score']['news_score'] ?? 0), 2),
                ],
                'country_a_label' => $countryALabel,
                'country_b_label' => $countryBLabel,
            ],
            'labels' => [
                'country_a' => $countryALabel,
                'country_b' => $countryBLabel,
            ],
        ];
    }

    private function resolveInflationScore(?float $inflation): float
    {
        if ($inflation === null) {
            return 0.0;
        }

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

    private function formatEconomicValue(string $key, ?float $value): string
    {
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
            if (abs($value) >= 1000000000000) {
                return 'US$ ' . number_format($value / 1000000000000, 2, ',', '.') . ' T';
            }

            if (abs($value) >= 1000000000) {
                return 'US$ ' . number_format($value / 1000000000, 2, ',', '.') . ' B';
            }

            if (abs($value) >= 1000000) {
                return 'US$ ' . number_format($value / 1000000, 2, ',', '.') . ' M';
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
            'moderate', 'medium' => 'Risiko Sedang',
            'low' => 'Risiko Rendah',
            default => 'Belum dihitung',
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
}
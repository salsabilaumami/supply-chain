<?php

namespace App\Http\Controllers;

use App\Models\Country;
use App\Models\EconomicIndicator;
use App\Models\ExchangeRate;
use App\Models\WeatherData;
use App\Services\ExchangeRateService;
use App\Services\WeatherService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\View\View;
use Throwable;

class ComparisonController extends Controller
{
    private const GDP_CODE = 'NY.GDP.MKTP.CD';

    private const INFLATION_CODE = 'FP.CPI.TOTL.ZG';

    public function index(
        Request $request,
        WeatherService $weatherService,
        ExchangeRateService $exchangeRateService
    ): View {
        return view('comparison.index', $this->buildComparisonData(
            $request,
            $weatherService,
            $exchangeRateService
        ));
    }

    public function show(
        Request $request,
        WeatherService $weatherService,
        ExchangeRateService $exchangeRateService
    ): JsonResponse {
        $data = $this->buildComparisonData(
            $request,
            $weatherService,
            $exchangeRateService
        );

        return response()->json([
            'success' => true,
            'message' => 'Data perbandingan negara berhasil dimuat.',
            'country_a' => $data['summaryA'],
            'country_b' => $data['summaryB'],
            'recommendation' => $data['recommendation'],
            'chart_data' => $data['chartData'],
            'sync_warnings' => $data['syncWarnings'],
        ]);
    }

    private function buildComparisonData(
        Request $request,
        WeatherService $weatherService,
        ExchangeRateService $exchangeRateService
    ): array {
        $countries = Country::query()
            ->alphabetical()
            ->get();

        $countryA = $this->resolveCountry(
            $request->string('country_a', 'DEU')->toString(),
            $countries,
            'DEU'
        );

        $countryB = $this->resolveCountry(
            $request->string('country_b', 'AUS')->toString(),
            $countries,
            'AUS',
            $countryA->id
        );

        $forceRefresh = $request->boolean('refresh');

        $syncWarnings = [];

        $summaryA = $this->buildCountrySummary(
            $countryA,
            $weatherService,
            $exchangeRateService,
            $forceRefresh,
            $syncWarnings
        );

        $summaryB = $this->buildCountrySummary(
            $countryB,
            $weatherService,
            $exchangeRateService,
            $forceRefresh,
            $syncWarnings
        );

        $recommendation = $this->buildRecommendation(
            $summaryA,
            $summaryB
        );

        return [
            'countries' => $countries,
            'countryA' => $countryA,
            'countryB' => $countryB,
            'summaryA' => $summaryA,
            'summaryB' => $summaryB,
            'recommendation' => $recommendation,
            'chartData' => $this->buildChartData($summaryA, $summaryB),
            'syncWarnings' => array_values(array_unique($syncWarnings)),
        ];
    }

    private function resolveCountry(
        string $isoCode,
        Collection $countries,
        string $defaultIsoCode,
        ?int $exceptCountryId = null
    ): Country {
        $isoCode = strtoupper(trim($isoCode));

        $country = Country::query()
            ->byIsoCode($isoCode)
            ->when($exceptCountryId, fn ($query) => $query->where('id', '!=', $exceptCountryId))
            ->first();

        if ($country) {
            return $country;
        }

        $defaultCountry = Country::query()
            ->where('iso3_code', $defaultIsoCode)
            ->when($exceptCountryId, fn ($query) => $query->where('id', '!=', $exceptCountryId))
            ->first();

        if ($defaultCountry) {
            return $defaultCountry;
        }

        return $countries
            ->when(
                $exceptCountryId,
                fn ($collection) => $collection->where('id', '!=', $exceptCountryId)
            )
            ->firstOrFail();
    }

    private function buildCountrySummary(
        Country $country,
        WeatherService $weatherService,
        ExchangeRateService $exchangeRateService,
        bool $forceRefresh,
        array &$syncWarnings
    ): array {
        $gdp = $this->getEconomicIndicator($country, self::GDP_CODE);
        $inflation = $this->getEconomicIndicator($country, self::INFLATION_CODE);

        $weather = $this->syncWeather(
            $country,
            $weatherService,
            $forceRefresh,
            $syncWarnings
        );

        $currency = $this->syncCurrency(
            $country,
            $exchangeRateService,
            $forceRefresh,
            $syncWarnings
        );

        $gdpValue = $this->getEconomicValue($gdp);
        $inflationValue = $this->getEconomicValue($inflation);

        $weatherRisk = $weather
            ? (float) ($weather->weather_risk ?? 0)
            : null;

        $currencyRisk = $currency
            ? (float) ($currency->currency_risk ?? 0)
            : null;

        $risk = $this->calculateDynamicRisk(
            $inflationValue,
            $weatherRisk,
            $currencyRisk
        );

        return [
            'country' => [
                'id' => $country->id,
                'name' => $country->name,
                'official_name' => $country->official_name ?? null,
                'iso2_code' => $country->iso2_code,
                'iso3_code' => $country->iso3_code,
                'flag_url' => $country->flag_url,
                'currency_code' => $country->currency_code,
                'currency_name' => $country->currency_name ?? null,
                'currency_symbol' => $country->currency_symbol ?? null,
            ],
            'gdp' => [
                'value' => $gdpValue,
                'display' => $this->formatMoney($gdpValue),
                'year' => $gdp?->year,
            ],
            'inflation' => [
                'value' => $inflationValue,
                'display' => $this->formatPercent($inflationValue),
                'year' => $inflation?->year,
            ],
            'risk' => [
                'value' => $risk['total_score'],
                'display' => number_format($risk['total_score'], 2, ',', '.'),
                'label' => $this->riskLabel($risk['total_score']),
                'class' => $this->riskClass($risk['total_score']),
                'weather_score' => $risk['weather_score'],
                'inflation_score' => $risk['inflation_score'],
                'currency_score' => $risk['currency_score'],
                'news_score' => $risk['news_score'],
                'source' => $risk['source'],
            ],
            'weather' => [
                'available' => $weather !== null,
                'temperature' => $weather?->temperature,
                'precipitation' => $weather?->precipitation,
                'wind_speed' => $weather?->wind_speed,
                'weather_code' => $weather?->weather_code,
                'weather_risk' => $weatherRisk,
                'condition' => $weather
                    ? $this->weatherCondition((int) $weather->weather_code)
                    : 'Belum tersedia',
                'display' => $weather
                    ? number_format((float) $weather->temperature, 1, ',', '.') . '°C'
                    : 'Belum tersedia',
                'detail' => $weather
                    ? 'Hujan ' . number_format((float) $weather->precipitation, 2, ',', '.') . ' mm • Angin ' . number_format((float) $weather->wind_speed, 1, ',', '.') . ' km/jam'
                    : 'Weather API belum tersedia',
            ],
            'currency' => [
                'available' => $currency !== null,
                'rate' => $currency?->rate,
                'base_currency' => $currency?->base_currency ?? 'USD',
                'target_currency' => $currency?->target_currency ?? $country->currency_code,
                'display' => $currency
                    ? '1 ' . $currency->base_currency . ' = '
                        . number_format((float) $currency->rate, 4, ',', '.')
                        . ' '
                        . $currency->target_currency
                    : 'Belum tersedia',
                'change' => $currency?->change_percentage,
                'currency_risk' => $currencyRisk,
                'risk_display' => $currencyRisk !== null
                    ? number_format($currencyRisk, 2, ',', '.')
                    : 'Belum tersedia',
            ],
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

            return WeatherData::query()
                ->where('country_id', $country->id)
                ->latest('recorded_at')
                ->first();
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
    }

    private function calculateDynamicRisk(
        ?float $inflation,
        ?float $weatherRisk,
        ?float $currencyRisk
    ): array {
        $inflationScore = $this->calculateInflationRisk($inflation);

        $finalWeatherRisk = $weatherRisk ?? 25.0;
        $finalCurrencyRisk = $currencyRisk ?? 20.0;
        $newsScore = 35.0;

        $totalScore = (
            ($finalWeatherRisk * 0.30)
            + ($inflationScore * 0.20)
            + ($finalCurrencyRisk * 0.10)
            + ($newsScore * 0.40)
        );

        return [
            'total_score' => round($totalScore, 2),
            'weather_score' => round($finalWeatherRisk, 2),
            'inflation_score' => round($inflationScore, 2),
            'currency_score' => round($finalCurrencyRisk, 2),
            'news_score' => round($newsScore, 2),
            'source' => $weatherRisk !== null && $currencyRisk !== null
                ? 'Terhubung langsung dari Weather, Inflation, Currency, dan baseline News'
                : 'Estimasi parsial dari data yang tersedia',
        ];
    }

    private function calculateInflationRisk(?float $inflation): float
    {
        if ($inflation === null) {
            return 30.0;
        }

        $absoluteInflation = abs($inflation);

        return match (true) {
            $absoluteInflation <= 2 => 15.0,
            $absoluteInflation <= 5 => 30.0,
            $absoluteInflation <= 8 => 55.0,
            $absoluteInflation <= 12 => 75.0,
            default => 90.0,
        };
    }

    private function buildRecommendation(
        array $summaryA,
        array $summaryB
    ): array {
        $scoreA = $this->calculateReadinessScore($summaryA, $summaryB);
        $scoreB = $this->calculateReadinessScore($summaryB, $summaryA);

        $recommended = $scoreA >= $scoreB ? $summaryA : $summaryB;
        $other = $scoreA >= $scoreB ? $summaryB : $summaryA;
        $score = max($scoreA, $scoreB);

        return [
            'country_name' => $recommended['country']['name'],
            'other_country_name' => $other['country']['name'],
            'score' => round($score, 2),
            'label' => $score >= 75
                ? 'Sangat Direkomendasikan'
                : ($score >= 55 ? 'Lebih Direkomendasikan' : 'Perlu Monitoring Lanjutan'),
            'description' => $recommended['country']['name']
                . ' lebih direkomendasikan dibanding '
                . $other['country']['name']
                . ' berdasarkan perbandingan GDP, inflasi, risk score, kondisi cuaca, dan risiko mata uang yang tersedia pada sistem.',
        ];
    }

    private function calculateReadinessScore(
        array $current,
        array $opponent
    ): float {
        $score = 50;

        if ($current['risk']['value'] <= $opponent['risk']['value']) {
            $score += 18;
        } else {
            $score -= 8;
        }

        if ($current['inflation']['value'] !== null && $opponent['inflation']['value'] !== null) {
            $score += $current['inflation']['value'] <= $opponent['inflation']['value'] ? 12 : -6;
        }

        if ($current['weather']['weather_risk'] !== null && $opponent['weather']['weather_risk'] !== null) {
            $score += $current['weather']['weather_risk'] <= $opponent['weather']['weather_risk'] ? 10 : -5;
        }

        if ($current['currency']['currency_risk'] !== null && $opponent['currency']['currency_risk'] !== null) {
            $score += $current['currency']['currency_risk'] <= $opponent['currency']['currency_risk'] ? 10 : -5;
        }

        if ($current['gdp']['value'] !== null && $opponent['gdp']['value'] !== null) {
            $score += $current['gdp']['value'] >= $opponent['gdp']['value'] ? 10 : -3;
        }

        return max(0, min(100, $score));
    }

    private function buildChartData(
        array $summaryA,
        array $summaryB
    ): array {
        return [
            'gdp' => [
                'labels' => [
                    $summaryA['country']['name'],
                    $summaryB['country']['name'],
                ],
                'values' => [
                    $summaryA['gdp']['value'] ? round($summaryA['gdp']['value'] / 1000000000, 2) : 0,
                    $summaryB['gdp']['value'] ? round($summaryB['gdp']['value'] / 1000000000, 2) : 0,
                ],
            ],
            'metrics' => [
                'labels' => [
                    'Inflation',
                    'Risk',
                    'Weather',
                    'Currency',
                ],
                'country_a_name' => $summaryA['country']['name'],
                'country_b_name' => $summaryB['country']['name'],
                'country_a' => [
                    round((float) ($summaryA['inflation']['value'] ?? 0), 2),
                    round((float) ($summaryA['risk']['value'] ?? 0), 2),
                    round((float) ($summaryA['risk']['weather_score'] ?? 0), 2),
                    round((float) ($summaryA['risk']['currency_score'] ?? 0), 2),
                ],
                'country_b' => [
                    round((float) ($summaryB['inflation']['value'] ?? 0), 2),
                    round((float) ($summaryB['risk']['value'] ?? 0), 2),
                    round((float) ($summaryB['risk']['weather_score'] ?? 0), 2),
                    round((float) ($summaryB['risk']['currency_score'] ?? 0), 2),
                ],
            ],
        ];
    }

    private function getEconomicIndicator(
        Country $country,
        string $indicatorCode
    ): ?EconomicIndicator {
        return EconomicIndicator::query()
            ->where('country_id', $country->id)
            ->where('indicator_code', $indicatorCode)
            ->latest('year')
            ->first();
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

    private function formatMoney(?float $value): string
    {
        if ($value === null) {
            return 'Belum tersedia';
        }

        if ($value >= 1000000000000) {
            return '$' . number_format($value / 1000000000000, 2, ',', '.') . ' T';
        }

        if ($value >= 1000000000) {
            return '$' . number_format($value / 1000000000, 2, ',', '.') . ' B';
        }

        if ($value >= 1000000) {
            return '$' . number_format($value / 1000000, 2, ',', '.') . ' M';
        }

        return '$' . number_format($value, 2, ',', '.');
    }

    private function formatPercent(?float $value): string
    {
        return $value !== null
            ? number_format($value, 2, ',', '.') . '%'
            : 'Belum tersedia';
    }

    private function riskLabel(float $score): string
    {
        return match (true) {
            $score >= 75 => 'Critical Risk',
            $score >= 50 => 'High Risk',
            $score >= 25 => 'Medium Risk',
            default => 'Low Risk',
        };
    }

    private function riskClass(float $score): string
    {
        return match (true) {
            $score >= 75 => 'risk-critical',
            $score >= 50 => 'risk-high',
            $score >= 25 => 'risk-medium',
            default => 'risk-low',
        };
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
}
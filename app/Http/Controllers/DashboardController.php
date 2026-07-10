<?php

namespace App\Http\Controllers;

use App\Models\Country;
use App\Models\EconomicIndicator;
use App\Models\ExchangeRate;
use App\Models\NewsSentiment;
use App\Models\RiskScore;
use App\Models\WeatherData;
use App\Services\ExchangeRateService;
use App\Services\RiskScoringService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Throwable;

class DashboardController extends Controller
{
    public function index(
        Request $request,
        RiskScoringService $riskScoringService,
        ExchangeRateService $exchangeRateService
    ): View {
        return view('dashboard', $this->buildDashboardData(
            $request,
            $riskScoringService,
            $exchangeRateService
        ));
    }

    public function show(
        Request $request,
        RiskScoringService $riskScoringService,
        ExchangeRateService $exchangeRateService
    ): JsonResponse {
        $data = $this->buildDashboardData(
            $request,
            $riskScoringService,
            $exchangeRateService
        );

        /** @var Country $selectedCountry */
        $selectedCountry = $data['selectedCountry'];

        return response()->json([
            'success' => true,
            'message' => 'Data Tinjauan Global berhasil dimuat.',
            'selected_country' => $this->formatCountry($selectedCountry),
            'economic_data' => $data['economicData'],
            'weather' => $this->formatWeather($data['weatherData']),
            'currency' => $this->formatExchangeRate($data['exchangeRate']),
            'news_summary' => $data['newsSummary'],
            'risk_score' => [
                'available' => $data['riskScoreAvailable'],
                'weather_score' => round((float) $data['weatherScore'], 2),
                'inflation_score' => round((float) $data['inflationScore'], 2),
                'currency_score' => round((float) $data['currencyScore'], 2),
                'news_score' => round((float) $data['newsScore'], 2),
                'total_score' => round((float) $data['totalScore'], 2),
                'risk_level' => $data['riskLevel'],
                'calculated_at' => $data['latestRiskScore']?->calculated_at?->toDateTimeString(),
            ],
            'global_summary' => $data['globalSummary'],
            'chart_data' => $data['dashboardChartData'],
            'countries' => $data['countries']
                ->map(fn (Country $country) => $this->formatCountry($country))
                ->values(),
        ]);
    }

    private function buildDashboardData(
        Request $request,
        RiskScoringService $riskScoringService,
        ExchangeRateService $exchangeRateService
    ): array {
        $countries = Country::query()
            ->alphabetical()
            ->get();

        $selectedIsoCode = strtoupper(
            trim($request->string('country', 'IDN')->toString())
        );

        $selectedCountry = Country::query()
            ->byIsoCode($selectedIsoCode)
            ->first();

        if (!$selectedCountry) {
            $selectedCountry = Country::query()
                ->where('iso3_code', 'IDN')
                ->first();
        }

        if (!$selectedCountry) {
            $selectedCountry = $countries->firstOrFail();
        }

        $economicData = $this->getEconomicData($selectedCountry);
        $weatherData = $this->getLatestWeather($selectedCountry);

        $exchangeRate = $this->getRealtimeExchangeRate(
            $selectedCountry,
            $exchangeRateService
        );

        $newsSummary = $this->getNewsSummary($selectedCountry);
        $latestRiskScore = $this->getLatestRiskScore($selectedCountry);

        $weatherScore = $latestRiskScore
            ? (float) $latestRiskScore->weather_score
            : $this->resolveWeatherScore($weatherData);

        $inflationScore = $latestRiskScore
            ? (float) $latestRiskScore->inflation_score
            : $this->resolveInflationScore(
                $economicData['inflation']['value'],
                $riskScoringService
            );

        $currencyScore = $latestRiskScore
            ? (float) $latestRiskScore->currency_score
            : $this->resolveCurrencyScore(
                $exchangeRate,
                $riskScoringService
            );

        $newsScore = $latestRiskScore
            ? (float) $latestRiskScore->news_score
            : $this->resolveNewsScore($newsSummary);

        $totalScore = $riskScoringService->calculateTotalScore(
            $weatherScore,
            $inflationScore,
            $currencyScore,
            $newsScore
        );

        $weatherLevel = $this->normalizeRiskLevel(
            $riskScoringService->determineRiskLevel($weatherScore)
        );

        $inflationLevel = $this->normalizeRiskLevel(
            $riskScoringService->determineRiskLevel($inflationScore)
        );

        $currencyLevel = $this->normalizeRiskLevel(
            $riskScoringService->determineRiskLevel($currencyScore)
        );

        $newsLevel = $this->normalizeRiskLevel(
            $riskScoringService->determineRiskLevel($newsScore)
        );

        $riskLevel = $this->normalizeRiskLevel(
            $riskScoringService->determineRiskLevel($totalScore)
        );

        $globalSummary = $this->getGlobalSummary();

        $dashboardChartData = [
            'riskComponents' => [
                'labels' => ['Cuaca', 'Inflasi', 'Mata Uang', 'Berita'],
                'values' => [
                    round($weatherScore, 2),
                    round($inflationScore, 2),
                    round($currencyScore, 2),
                    round($newsScore, 2),
                ],
            ],
            'economic' => [
                'labels' => [
                    'GDP (US$ T)',
                    'Ekspor (US$ B)',
                    'Impor (US$ B)',
                    'Inflasi (%)',
                    'Populasi (Juta)',
                ],
                'values' => [
                    round((float) ($economicData['gdp']['value'] ?? 0) / 1000000000000, 2),
                    round((float) ($economicData['exports']['value'] ?? 0) / 1000000000, 2),
                    round((float) ($economicData['imports']['value'] ?? 0) / 1000000000, 2),
                    round((float) ($economicData['inflation']['value'] ?? 0), 2),
                    round((float) ($economicData['population']['value'] ?? 0) / 1000000, 2),
                ],
            ],
            'globalRisk' => [
                'labels' => $globalSummary['risk_chart_labels'],
                'values' => $globalSummary['risk_chart_values'],
            ],
        ];

        return [
            'countries' => $countries,
            'selectedCountry' => $selectedCountry,
            'economicData' => $economicData,
            'hasEconomicData' => $this->hasEconomicData($economicData),
            'weatherData' => $weatherData,
            'weatherAvailable' => $weatherData !== null,
            'exchangeRate' => $exchangeRate,
            'currencyAvailable' => $exchangeRate !== null,
            'newsSummary' => $newsSummary,
            'newsAvailable' => $newsSummary['total_articles'] > 0,
            'latestRiskScore' => $latestRiskScore,
            'riskScoreAvailable' => $latestRiskScore !== null,
            'weatherScore' => $weatherScore,
            'inflationScore' => $inflationScore,
            'currencyScore' => $currencyScore,
            'newsScore' => $newsScore,
            'weatherLevel' => $weatherLevel,
            'inflationLevel' => $inflationLevel,
            'currencyLevel' => $currencyLevel,
            'newsLevel' => $newsLevel,
            'totalScore' => $totalScore,
            'riskLevel' => $riskLevel,
            'globalSummary' => $globalSummary,
            'dashboardChartData' => $dashboardChartData,
        ];
    }

    private function indicatorCodes(): array
    {
        return [
            'gdp' => 'NY.GDP.MKTP.CD',
            'inflation' => 'FP.CPI.TOTL.ZG',
            'population' => 'SP.POP.TOTL',
            'exports' => 'NE.EXP.GNFS.CD',
            'imports' => 'NE.IMP.GNFS.CD',
        ];
    }

    private function getEconomicData(Country $country): array
    {
        $indicatorCodes = $this->indicatorCodes();

        $indicators = EconomicIndicator::query()
            ->where('country_id', $country->id)
            ->whereIn('indicator_code', array_values($indicatorCodes))
            ->latestAvailable()
            ->get()
            ->unique('indicator_code')
            ->keyBy('indicator_code');

        $economicData = [];

        foreach ($indicatorCodes as $key => $code) {
            $indicator = $indicators->get($code);

            $economicData[$key] = [
                'code' => $code,
                'value' => $indicator ? (float) $indicator->value : null,
                'year' => $indicator?->year,
                'source' => $indicator?->source,
                'fetched_at' => $indicator?->fetched_at,
            ];
        }

        return $economicData;
    }

    private function hasEconomicData(array $economicData): bool
    {
        foreach ($economicData as $indicator) {
            if ($indicator['value'] !== null) {
                return true;
            }
        }

        return false;
    }

    private function getLatestWeather(Country $country): ?WeatherData
    {
        return WeatherData::query()
            ->where('country_id', $country->id)
            ->latest('recorded_at')
            ->first();
    }

    private function getRealtimeExchangeRate(
        Country $country,
        ExchangeRateService $exchangeRateService
    ): ?ExchangeRate {
        try {
            return $exchangeRateService->getLatestRate(
                $country,
                'USD',
                true
            );
        } catch (Throwable) {
            return $this->getLatestExchangeRate($country);
        }
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

        return [
            'total_articles' => $sentiments->count(),
            'positive_count' => $sentiments->where('sentiment', 'positive')->count(),
            'neutral_count' => $sentiments->where('sentiment', 'neutral')->count(),
            'negative_count' => $sentiments->where('sentiment', 'negative')->count(),
            'average_risk_score' => $sentiments->isNotEmpty()
                ? round((float) $sentiments->avg('risk_score'), 2)
                : 0.0,
        ];
    }

    private function resolveWeatherScore(?WeatherData $weatherData): float
    {
        return $weatherData ? round((float) $weatherData->weather_risk, 2) : 0.0;
    }

    private function resolveInflationScore(
        ?float $inflationRate,
        RiskScoringService $riskScoringService
    ): float {
        return $inflationRate !== null
            ? round($riskScoringService->calculateInflationScore($inflationRate), 2)
            : 0.0;
    }

    private function resolveCurrencyScore(
        ?ExchangeRate $exchangeRate,
        RiskScoringService $riskScoringService
    ): float {
        if (!$exchangeRate) {
            return 0.0;
        }

        if ($exchangeRate->change_percentage !== null) {
            return round(
                $riskScoringService->calculateCurrencyScore(
                    (float) $exchangeRate->change_percentage
                ),
                2
            );
        }

        return round((float) $exchangeRate->currency_risk, 2);
    }

    private function resolveNewsScore(array $newsSummary): float
    {
        return ($newsSummary['total_articles'] ?? 0) > 0
            ? round((float) $newsSummary['average_risk_score'], 2)
            : 0.0;
    }

    private function getGlobalSummary(): array
    {
        $totalCountries = Country::query()->count();

        $latestRiskScores = RiskScore::query()
            ->latest('calculated_at')
            ->get()
            ->unique('country_id')
            ->values();

        $countriesWithRisk = $latestRiskScores->count();

        $averageRiskScore = $latestRiskScores->isNotEmpty()
            ? round((float) $latestRiskScores->avg('total_score'), 2)
            : 0.0;

        $highestRisk = $latestRiskScores
            ->sortByDesc('total_score')
            ->first();

        $countryIds = $latestRiskScores
            ->pluck('country_id')
            ->filter()
            ->unique()
            ->values();

        $countries = Country::query()
            ->whereIn('id', $countryIds)
            ->get()
            ->keyBy('id');

        $highestRiskCountry = $highestRisk
            ? $countries->get($highestRisk->country_id)?->name
            : null;

        $chartScores = $latestRiskScores
            ->sortByDesc('total_score')
            ->take(6)
            ->values();

        $riskChartLabels = $chartScores
            ->map(fn (RiskScore $riskScore) => $countries->get($riskScore->country_id)?->iso3_code ?? '-')
            ->values()
            ->all();

        $riskChartValues = $chartScores
            ->map(fn (RiskScore $riskScore) => round((float) $riskScore->total_score, 2))
            ->values()
            ->all();

        if (empty($riskChartLabels)) {
            $riskChartLabels = ['Belum ada data'];
            $riskChartValues = [0];
        }

        return [
            'total_countries' => $totalCountries,
            'countries_with_risk' => $countriesWithRisk,
            'average_risk_score' => $averageRiskScore,
            'highest_risk_country' => $highestRiskCountry,
            'highest_risk_score' => $highestRisk
                ? round((float) $highestRisk->total_score, 2)
                : 0.0,
            'risk_chart_labels' => $riskChartLabels,
            'risk_chart_values' => $riskChartValues,
        ];
    }

    private function normalizeRiskLevel(?string $level): string
    {
        return match ($level) {
            'critical' => 'critical',
            'high' => 'high',
            'moderate' => 'moderate',
            'medium' => 'medium',
            'low' => 'low',
            default => 'low',
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
            'currency_code' => $country->currency_code,
            'currency_name' => $country->currency_name,
            'currency_symbol' => $country->currency_symbol,
            'population' => $country->population,
            'flag_url' => $country->flag_url,
        ];
    }

    private function formatWeather(?WeatherData $weatherData): ?array
    {
        if (!$weatherData) {
            return null;
        }

        return [
            'temperature' => (float) $weatherData->temperature,
            'precipitation' => (float) $weatherData->precipitation,
            'wind_speed' => (float) $weatherData->wind_speed,
            'weather_code' => (int) $weatherData->weather_code,
            'weather_risk' => (float) $weatherData->weather_risk,
            'recorded_at' => $weatherData->recorded_at?->toDateTimeString(),
            'fetched_at' => $weatherData->fetched_at?->toDateTimeString(),
        ];
    }

    private function formatExchangeRate(?ExchangeRate $exchangeRate): ?array
    {
        if (!$exchangeRate) {
            return null;
        }

        return [
            'base_currency' => $exchangeRate->base_currency,
            'target_currency' => $exchangeRate->target_currency,
            'rate' => (float) $exchangeRate->rate,
            'change_percentage' => $exchangeRate->change_percentage !== null
                ? (float) $exchangeRate->change_percentage
                : null,
            'currency_risk' => (float) $exchangeRate->currency_risk,
            'recorded_at' => $exchangeRate->recorded_at?->toDateTimeString(),
            'fetched_at' => $exchangeRate->fetched_at?->toDateTimeString(),
        ];
    }
}
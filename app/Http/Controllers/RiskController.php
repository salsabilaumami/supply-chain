<?php

namespace App\Http\Controllers;

use App\Models\Country;
use App\Models\EconomicIndicator;
use App\Models\ExchangeRate;
use App\Models\NewsSentiment;
use App\Models\RiskScore;
use App\Models\WeatherData;
use App\Services\RiskScoringService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class RiskController extends Controller
{
    private const INFLATION_CODE = 'FP.CPI.TOTL.ZG';

    public function index(
        Request $request,
        RiskScoringService $riskScoringService
    ): View {
        return view('risk.index', $this->buildRiskData(
            $request,
            $riskScoringService
        ));
    }

    public function show(
        Request $request,
        RiskScoringService $riskScoringService
    ): JsonResponse {
        $data = $this->buildRiskData(
            $request,
            $riskScoringService
        );

        return response()->json([
            'success' => true,
            'message' => 'Data risk scoring berhasil dimuat.',
            'selected_country' => $data['selectedCountry'],
            'weights' => $data['weights'],
            'components' => $data['components'],
            'weighted_components' => $data['weightedComponents'],
            'total_score' => $data['totalScore'],
            'risk_level' => $data['riskLevel'],
            'risk_label' => $data['riskLabel'],
            'chart_data' => $data['chartData'],
        ]);
    }

    private function buildRiskData(
        Request $request,
        RiskScoringService $riskScoringService
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

        $weights = $riskScoringService->getWeights();

        $weatherData = $this->getLatestWeatherData($selectedCountry);
        $inflationData = $this->getLatestInflationData($selectedCountry);
        $exchangeRate = $this->getLatestExchangeRate($selectedCountry);
        $newsSummary = $this->getNewsSummary($selectedCountry);

        $weatherScore = $this->resolveWeatherScore(
            $weatherData,
            $riskScoringService
        );

        $inflationRate = $inflationData
            ? (float) $inflationData->value
            : null;

        $inflationScore = $inflationRate !== null
            ? $riskScoringService->calculateInflationScore($inflationRate)
            : 0.0;

        $currencyScore = $this->resolveCurrencyScore(
            $exchangeRate,
            $riskScoringService
        );

        $newsScore = $newsSummary['risk_score'];

        $totalScore = $riskScoringService->calculateTotalScore(
            $weatherScore,
            $inflationScore,
            $currencyScore,
            $newsScore
        );

        $riskLevel = $riskScoringService->determineRiskLevel($totalScore);
        $riskLabel = $this->riskLevelLabel($riskLevel);

        $weightedComponents = [
            'weather' => round($weatherScore * $weights['weather'], 2),
            'inflation' => round($inflationScore * $weights['inflation'], 2),
            'currency' => round($currencyScore * $weights['currency'], 2),
            'news' => round($newsScore * $weights['news'], 2),
        ];

        $latestStoredRisk = RiskScore::query()
            ->where('country_id', $selectedCountry->id)
            ->latest('calculated_at')
            ->latest('id')
            ->first();

        return [
            'countries' => $countries,
            'selectedCountry' => $selectedCountry,
            'weights' => $weights,
            'components' => [
                'weather' => [
                    'label' => 'Weather',
                    'score' => $weatherScore,
                    'weight' => $weights['weather'],
                    'weighted_score' => $weightedComponents['weather'],
                    'description' => 'Risiko cuaca dihitung dari hujan, angin, dan kondisi cuaca.',
                    'source_value' => $this->formatWeatherSource($weatherData),
                ],
                'inflation' => [
                    'label' => 'Inflation',
                    'score' => $inflationScore,
                    'weight' => $weights['inflation'],
                    'weighted_score' => $weightedComponents['inflation'],
                    'description' => 'Risiko inflasi dihitung dari tingkat inflasi negara.',
                    'source_value' => $inflationRate !== null
                        ? number_format($inflationRate, 2, ',', '.') . '%'
                        : 'Belum tersedia',
                ],
                'currency' => [
                    'label' => 'Exchange Rate',
                    'score' => $currencyScore,
                    'weight' => $weights['currency'],
                    'weighted_score' => $weightedComponents['currency'],
                    'description' => 'Risiko kurs dihitung dari perubahan nilai tukar.',
                    'source_value' => $exchangeRate
                        ? $exchangeRate->base_currency . ' ke ' . $exchangeRate->target_currency
                        : 'Belum tersedia',
                ],
                'news' => [
                    'label' => 'News Sentiment',
                    'score' => $newsScore,
                    'weight' => $weights['news'],
                    'weighted_score' => $weightedComponents['news'],
                    'description' => 'Risiko berita dihitung dari sentimen negatif pada berita logistik, trade, shipping, dan economy.',
                    'source_value' => $newsSummary['display'],
                ],
            ],
            'weightedComponents' => $weightedComponents,
            'totalScore' => $totalScore,
            'riskLevel' => $riskLevel,
            'riskLabel' => $riskLabel,
            'latestStoredRisk' => $latestStoredRisk,
            'chartData' => [
                'components' => [
                    'labels' => [
                        'Weather',
                        'Inflation',
                        'Exchange Rate',
                        'News Sentiment',
                    ],
                    'scores' => [
                        $weatherScore,
                        $inflationScore,
                        $currencyScore,
                        $newsScore,
                    ],
                    'weighted' => [
                        $weightedComponents['weather'],
                        $weightedComponents['inflation'],
                        $weightedComponents['currency'],
                        $weightedComponents['news'],
                    ],
                ],
            ],
        ];
    }

    private function getLatestWeatherData(Country $country): ?WeatherData
    {
        return WeatherData::query()
            ->where('country_id', $country->id)
            ->latest('recorded_at')
            ->latest('id')
            ->first();
    }

    private function getLatestInflationData(Country $country): ?EconomicIndicator
    {
        return EconomicIndicator::query()
            ->where('country_id', $country->id)
            ->where('indicator_code', self::INFLATION_CODE)
            ->latest('year')
            ->latest('id')
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
            ->latest('id')
            ->first();
    }

    private function getNewsSummary(Country $country): array
    {
        $sentiments = NewsSentiment::query()
            ->whereHas('newsCache', function ($query) use ($country) {
                $query->where('country_id', $country->id);
            })
            ->latest('analyzed_at')
            ->limit(20)
            ->get();

        if ($sentiments->isEmpty()) {
            return [
                'risk_score' => 50.0,
                'positive' => 0,
                'neutral' => 0,
                'negative' => 0,
                'display' => 'Belum tersedia',
            ];
        }

        $positive = $sentiments->where('sentiment', 'positive')->count();
        $neutral = $sentiments->where('sentiment', 'neutral')->count();
        $negative = $sentiments->where('sentiment', 'negative')->count();

        $riskScore = round((float) $sentiments->avg('risk_score'), 2);

        return [
            'risk_score' => $riskScore,
            'positive' => $positive,
            'neutral' => $neutral,
            'negative' => $negative,
            'display' => 'Positive ' . $positive . ' • Neutral ' . $neutral . ' • Negative ' . $negative,
        ];
    }

    private function resolveWeatherScore(
        ?WeatherData $weatherData,
        RiskScoringService $riskScoringService
    ): float {
        if (!$weatherData) {
            return 0.0;
        }

        if ($weatherData->weather_risk !== null) {
            return round((float) $weatherData->weather_risk, 2);
        }

        if ($weatherData->risk_score !== null) {
            return round((float) $weatherData->risk_score, 2);
        }

        $precipitation = (float) (
            $weatherData->precipitation
            ?? $weatherData->precipitation_sum
            ?? 0
        );

        $windSpeed = (float) (
            $weatherData->wind_speed
            ?? $weatherData->wind_speed_10m
            ?? 0
        );

        $weatherCode = (int) (
            $weatherData->weather_code
            ?? 0
        );

        return $riskScoringService->calculateWeatherScore(
            $precipitation,
            $windSpeed,
            $weatherCode
        );
    }

    private function resolveCurrencyScore(
        ?ExchangeRate $exchangeRate,
        RiskScoringService $riskScoringService
    ): float {
        if (!$exchangeRate) {
            return 0.0;
        }

        if ($exchangeRate->currency_risk !== null) {
            return round((float) $exchangeRate->currency_risk, 2);
        }

        if ($exchangeRate->change_percentage !== null) {
            return $riskScoringService->calculateCurrencyScore(
                (float) $exchangeRate->change_percentage
            );
        }

        return 20.0;
    }

    private function formatWeatherSource(?WeatherData $weatherData): string
    {
        if (!$weatherData) {
            return 'Belum tersedia';
        }

        $temperature = $weatherData->temperature
            ?? $weatherData->temperature_2m
            ?? null;

        $precipitation = $weatherData->precipitation
            ?? $weatherData->precipitation_sum
            ?? null;

        $windSpeed = $weatherData->wind_speed
            ?? $weatherData->wind_speed_10m
            ?? null;

        return 'Temp '
            . ($temperature !== null ? number_format((float) $temperature, 1, ',', '.') . '°C' : '-')
            . ' • Hujan '
            . ($precipitation !== null ? number_format((float) $precipitation, 1, ',', '.') . ' mm' : '-')
            . ' • Angin '
            . ($windSpeed !== null ? number_format((float) $windSpeed, 1, ',', '.') . ' km/j' : '-');
    }

    private function riskLevelLabel(string $riskLevel): string
    {
        return match ($riskLevel) {
            'critical' => 'Critical Risk',
            'high' => 'High Risk',
            'moderate' => 'Medium Risk',
            default => 'Low Risk',
        };
    }
}
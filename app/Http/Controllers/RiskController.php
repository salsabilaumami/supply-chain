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
use App\Services\RiskScoringService;
use App\Services\WeatherService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;
use Illuminate\View\View;
use Throwable;

class RiskController extends Controller
{
    private const INFLATION_CODE = 'FP.CPI.TOTL.ZG';

    public function index(
        Request $request,
        RiskScoringService $riskScoringService,
        WeatherService $weatherService,
        ExchangeRateService $exchangeRateService,
        NewsService $newsService
    ): View {
        return view('risk.index', $this->buildRiskData(
            $request,
            $riskScoringService,
            $weatherService,
            $exchangeRateService,
            $newsService
        ));
    }

    public function show(
        Request $request,
        RiskScoringService $riskScoringService,
        WeatherService $weatherService,
        ExchangeRateService $exchangeRateService,
        NewsService $newsService
    ): JsonResponse {
        $data = $this->buildRiskData(
            $request,
            $riskScoringService,
            $weatherService,
            $exchangeRateService,
            $newsService
        );

        return response()->json([
            'success' => true,
            'message' => 'Risk score berhasil dihitung.',
            'country' => $data['selectedCountry'],
            'total_score' => $data['totalScore'],
            'risk_level' => $data['riskLevel'],
            'risk_label' => $data['riskLabel'],
            'components' => $data['components'],
            'source_status' => $data['sourceStatus'],
            'recommendation' => $data['recommendation'],
            'chart_data' => $data['chartData'],
            'sync_warnings' => $data['syncWarnings'],
        ]);
    }

    private function buildRiskData(
        Request $request,
        RiskScoringService $riskScoringService,
        WeatherService $weatherService,
        ExchangeRateService $exchangeRateService,
        NewsService $newsService
    ): array {
        $countries = Country::query()
            ->orderBy('name')
            ->get();

        $selectedIsoCode = strtoupper(
            trim($request->string('country', 'DEU')->toString())
        );

        $selectedCountry = Country::query()
            ->where(function ($query) use ($selectedIsoCode) {
                $query->where('iso3_code', $selectedIsoCode)
                    ->orWhere('iso2_code', $selectedIsoCode);
            })
            ->first();

        if (!$selectedCountry) {
            $selectedCountry = Country::query()
                ->where('iso3_code', 'DEU')
                ->first();
        }

        if (!$selectedCountry) {
            $selectedCountry = $countries->firstOrFail();
        }

        $forceRefresh = $request->boolean('refresh');
        $syncWarnings = [];

        $weather = $this->syncWeather(
            $selectedCountry,
            $weatherService,
            $forceRefresh,
            $syncWarnings
        );

        $exchangeRate = $this->syncCurrency(
            $selectedCountry,
            $exchangeRateService,
            $forceRefresh,
            $syncWarnings
        );

        $this->syncNews(
            $selectedCountry,
            $newsService,
            $forceRefresh,
            $syncWarnings
        );

        $inflation = $this->getLatestInflation($selectedCountry);
        $newsSummary = $this->getNewsSummary($selectedCountry);
        $inflationValue = $this->getEconomicValue($inflation);

        $weatherScore = $weather
            ? $riskScoringService->calculateWeatherScore(
                (float) ($weather->precipitation ?? 0),
                (float) ($weather->wind_speed ?? 0),
                (int) ($weather->weather_code ?? 0)
            )
            : 25.0;

        $inflationScore = $riskScoringService->calculateInflationScore(
            $inflationValue
        );

        $currencyScore = $riskScoringService->calculateCurrencyScore(
            $exchangeRate?->change_percentage !== null
                ? (float) $exchangeRate->change_percentage
                : null,
            $exchangeRate?->currency_risk !== null
                ? (float) $exchangeRate->currency_risk
                : null
        );

        $newsScore = $riskScoringService->calculateNewsScore(
            $newsSummary['positive_count'],
            $newsSummary['neutral_count'],
            $newsSummary['negative_count'],
            $newsSummary['average_risk_score']
        );

        $result = $riskScoringService->calculateDetailedScore(
            $weatherScore,
            $inflationScore,
            $currencyScore,
            $newsScore
        );

        $savedRiskScore = $this->storeRiskScore(
            $selectedCountry,
            $result
        );

        $components = [
            [
                'key' => 'weather',
                'label' => 'Weather',
                'score' => $result['weather_score'],
                'weight' => RiskScoringService::WEATHER_WEIGHT,
                'weighted_score' => $result['weather_weighted'],
                'source_value' => $weather
                    ? number_format((float) ($weather->temperature ?? 0), 1, ',', '.')
                        . '°C, hujan '
                        . number_format((float) ($weather->precipitation ?? 0), 2, ',', '.')
                        . ' mm, angin '
                        . number_format((float) ($weather->wind_speed ?? 0), 1, ',', '.')
                        . ' km/jam'
                    : 'Data cuaca belum tersedia, memakai baseline 25',
                'description' => 'Menilai risiko berdasarkan curah hujan, kecepatan angin, dan kondisi cuaca.',
                'status' => $weather ? 'Tersedia' : 'Baseline',
            ],
            [
                'key' => 'inflation',
                'label' => 'Inflation',
                'score' => $result['inflation_score'],
                'weight' => RiskScoringService::INFLATION_WEIGHT,
                'weighted_score' => $result['inflation_weighted'],
                'source_value' => $inflationValue !== null
                    ? number_format($inflationValue, 2, ',', '.') . '%'
                    : 'Data inflasi belum tersedia, memakai baseline 30',
                'description' => 'Menilai tekanan biaya ekonomi menggunakan nilai inflasi terbaru.',
                'status' => $inflationValue !== null ? 'Tersedia' : 'Baseline',
            ],
            [
                'key' => 'currency',
                'label' => 'Exchange Rate',
                'score' => $result['currency_score'],
                'weight' => RiskScoringService::CURRENCY_WEIGHT,
                'weighted_score' => $result['currency_weighted'],
                'source_value' => $exchangeRate
                    ? '1 '
                        . ($exchangeRate->base_currency ?? 'USD')
                        . ' = '
                        . number_format((float) ($exchangeRate->rate ?? 0), 4, ',', '.')
                        . ' '
                        . ($exchangeRate->target_currency ?? $selectedCountry->currency_code ?? '-')
                    : 'Data kurs belum tersedia, memakai baseline 20',
                'description' => 'Menilai risiko berdasarkan perubahan nilai tukar mata uang terhadap USD.',
                'status' => $exchangeRate ? 'Tersedia' : 'Baseline',
            ],
            [
                'key' => 'news',
                'label' => 'News Sentiment',
                'score' => $result['news_score'],
                'weight' => RiskScoringService::NEWS_WEIGHT,
                'weighted_score' => $result['news_weighted'],
                'source_value' => $newsSummary['total_articles'] . ' berita dianalisis',
                'description' => 'Menilai sentimen berita terkait logistik, perdagangan, ekonomi, dan geopolitik.',
                'status' => $newsSummary['total_articles'] > 0 ? 'Tersedia' : 'Baseline',
            ],
        ];

        return [
            'countries' => $countries,
            'selectedCountry' => $selectedCountry,
            'totalScore' => $result['total_score'],
            'riskLevel' => $result['risk_level'],
            'riskLabel' => $result['risk_label'],
            'components' => $components,
            'sourceStatus' => [
                'weather' => $weather ? 'Tersedia' : 'Baseline',
                'inflation' => $inflationValue !== null ? 'Tersedia' : 'Baseline',
                'currency' => $exchangeRate ? 'Tersedia' : 'Baseline',
                'news' => $newsSummary['total_articles'] > 0 ? 'Tersedia' : 'Baseline',
            ],
            'recommendation' => $riskScoringService->getRecommendation(
                $result['total_score'],
                $selectedCountry->name
            ),
            'latestRiskScore' => $savedRiskScore,
            'syncWarnings' => array_values(array_unique($syncWarnings)),
            'chartData' => [
                'components' => [
                    'labels' => collect($components)->pluck('label')->values()->all(),
                    'scores' => collect($components)->pluck('score')->values()->all(),
                    'weighted' => collect($components)->pluck('weighted_score')->values()->all(),
                ],
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
            $syncWarnings[] = 'Weather: ' . $exception->getMessage();

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
                throw new \RuntimeException('Kode mata uang negara belum tersedia.');
            }

            return $exchangeRateService->getLatestRate(
                $country,
                'USD',
                $forceRefresh
            );
        } catch (Throwable $exception) {
            $syncWarnings[] = 'Currency: ' . $exception->getMessage();

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
            $syncWarnings[] = 'News: ' . $exception->getMessage();
        }
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
            : null;

        return [
            'total_articles' => $sentiments->count(),
            'positive_count' => $positiveCount,
            'neutral_count' => $neutralCount,
            'negative_count' => $negativeCount,
            'average_risk_score' => $averageRiskScore,
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

    private function storeRiskScore(
        Country $country,
        array $result
    ): ?RiskScore {
        try {
            $riskScore = new RiskScore();

            $this->setColumnValue($riskScore, 'country_id', $country->id);
            $this->setColumnValue($riskScore, 'weather_score', $result['weather_score']);
            $this->setColumnValue($riskScore, 'inflation_score', $result['inflation_score']);
            $this->setColumnValue($riskScore, 'currency_score', $result['currency_score']);
            $this->setColumnValue($riskScore, 'news_score', $result['news_score']);
            $this->setColumnValue($riskScore, 'total_score', $result['total_score']);
            $this->setColumnValue($riskScore, 'risk_level', $result['risk_level']);
            $this->setColumnValue($riskScore, 'calculated_at', now());

            $riskScore->save();

            return $riskScore;
        } catch (Throwable) {
            return RiskScore::query()
                ->where('country_id', $country->id)
                ->when(
                    Schema::hasColumn('risk_scores', 'calculated_at'),
                    fn ($query) => $query->latest('calculated_at'),
                    fn ($query) => $query->latest()
                )
                ->first();
        }
    }

    private function setColumnValue(
        RiskScore $riskScore,
        string $column,
        mixed $value
    ): void {
        if (Schema::hasColumn('risk_scores', $column)) {
            $riskScore->{$column} = $value;
        }
    }
}
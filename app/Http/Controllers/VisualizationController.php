<?php

namespace App\Http\Controllers;

use App\Models\Country;
use App\Models\EconomicIndicator;
use App\Models\ExchangeRate;
use App\Models\RiskScore;
use App\Models\WeatherData;
use App\Services\ExchangeRateService;
use App\Services\WeatherService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\View\View;
use Throwable;

class VisualizationController extends Controller
{
    private const GDP_CODE = 'NY.GDP.MKTP.CD';

    private const INFLATION_CODE = 'FP.CPI.TOTL.ZG';

    public function index(
        Request $request,
        WeatherService $weatherService,
        ExchangeRateService $exchangeRateService
    ): View {
        return view('visualization.index', $this->buildVisualizationData(
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
        $data = $this->buildVisualizationData(
            $request,
            $weatherService,
            $exchangeRateService
        );

        return response()->json([
            'success' => true,
            'message' => 'Data visualization dashboard berhasil dimuat.',
            'selected_country' => $data['selectedCountry'],
            'summary' => $data['summary'],
            'trend_insight' => $data['trendInsight'],
            'chart_data' => $data['chartData'],
            'sync_warnings' => $data['syncWarnings'],
        ]);
    }

    private function buildVisualizationData(
        Request $request,
        WeatherService $weatherService,
        ExchangeRateService $exchangeRateService
    ): array {
        $countries = Country::query()
            ->orderBy('name')
            ->get();

        $selectedIsoCode = strtoupper(
            trim($request->string('country', 'IDN')->toString())
        );

        $selectedCountry = Country::query()
            ->where(function ($query) use ($selectedIsoCode) {
                $query->where('iso3_code', $selectedIsoCode)
                    ->orWhere('iso2_code', $selectedIsoCode);
            })
            ->first();

        if (!$selectedCountry) {
            $selectedCountry = Country::query()
                ->where('iso3_code', 'IDN')
                ->first();
        }

        if (!$selectedCountry) {
            $selectedCountry = $countries->firstOrFail();
        }

        $forceRefresh = $request->boolean('refresh');
        $syncWarnings = [];

        $latestWeather = $this->syncWeather(
            $selectedCountry,
            $weatherService,
            $forceRefresh,
            $syncWarnings
        );

        $latestCurrency = $this->syncCurrency(
            $selectedCountry,
            $exchangeRateService,
            $forceRefresh,
            $syncWarnings
        );

        $gdpTrend = $this->getEconomicTrend(
            $selectedCountry,
            self::GDP_CODE
        );

        $inflationTrend = $this->getEconomicTrend(
            $selectedCountry,
            self::INFLATION_CODE
        );

        $currencyTrend = $this->getCurrencyTrend($selectedCountry);

        $currentRisk = $this->calculateCurrentRisk(
            $inflationTrend,
            $latestWeather,
            $latestCurrency
        );

        $riskTrend = $this->getRiskTrend(
            $selectedCountry,
            $currentRisk
        );

        $summary = [
            'gdp_points' => $gdpTrend->count(),
            'inflation_points' => $inflationTrend->count(),
            'currency_points' => $currencyTrend->count(),
            'risk_points' => $riskTrend->count(),
            'latest_gdp' => $this->latestDisplayValue($gdpTrend, 'US$'),
            'latest_inflation' => $this->latestDisplayValue($inflationTrend, '%'),
            'latest_currency' => $this->latestCurrencyValue($currencyTrend, $selectedCountry),
            'latest_risk' => $this->latestRiskValue($riskTrend),
            'risk_label' => $this->riskLabel((float) ($riskTrend->last()['value'] ?? 0)),
            'last_sync' => now()->format('d M Y H:i'),
        ];

        $chartData = [
            'gdp' => [
                'labels' => $gdpTrend->pluck('label')->values()->all(),
                'values' => $gdpTrend->pluck('value')->values()->all(),
            ],
            'inflation' => [
                'labels' => $inflationTrend->pluck('label')->values()->all(),
                'values' => $inflationTrend->pluck('value')->values()->all(),
            ],
            'currency' => [
                'labels' => $currencyTrend->pluck('label')->values()->all(),
                'values' => $currencyTrend->pluck('value')->values()->all(),
            ],
            'risk' => [
                'labels' => $riskTrend->pluck('label')->values()->all(),
                'values' => $riskTrend->pluck('value')->values()->all(),
            ],
        ];

        return [
            'countries' => $countries,
            'selectedCountry' => $selectedCountry,
            'summary' => $summary,
            'trendInsight' => $this->buildTrendInsight(
                $gdpTrend,
                $inflationTrend,
                $currencyTrend,
                $riskTrend
            ),
            'chartData' => $chartData,
            'syncWarnings' => array_values(array_unique($syncWarnings)),
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

    private function getEconomicTrend(
        Country $country,
        string $indicatorCode
    ): Collection {
        return EconomicIndicator::query()
            ->where('country_id', $country->id)
            ->where('indicator_code', $indicatorCode)
            ->whereNotNull('value')
            ->orderBy('year')
            ->get()
            ->map(function (EconomicIndicator $indicator) {
                return [
                    'label' => (string) $indicator->year,
                    'value' => round((float) $indicator->value, 2),
                    'raw_value' => (float) $indicator->value,
                ];
            })
            ->values();
    }

    private function getCurrencyTrend(Country $country): Collection
    {
        if (!$country->currency_code) {
            return collect();
        }

        return ExchangeRate::query()
            ->where('country_id', $country->id)
            ->where('base_currency', 'USD')
            ->where('target_currency', $country->currency_code)
            ->whereNotNull('rate')
            ->orderBy('recorded_at')
            ->limit(30)
            ->get()
            ->map(function (ExchangeRate $exchangeRate) {
                return [
                    'label' => $exchangeRate->recorded_at?->format('d M H:i') ?? '-',
                    'value' => round((float) $exchangeRate->rate, 4),
                    'raw_value' => (float) $exchangeRate->rate,
                ];
            })
            ->values();
    }

    private function getRiskTrend(
        Country $country,
        array $currentRisk
    ): Collection {
        $riskTrend = RiskScore::query()
            ->where('country_id', $country->id)
            ->whereNotNull('total_score')
            ->orderBy('calculated_at')
            ->limit(30)
            ->get()
            ->map(function (RiskScore $riskScore) {
                return [
                    'label' => $riskScore->calculated_at?->format('d M H:i') ?? '-',
                    'value' => round((float) $riskScore->total_score, 2),
                    'raw_value' => (float) $riskScore->total_score,
                    'risk_level' => $riskScore->risk_level,
                ];
            })
            ->values();

        $riskTrend->push([
            'label' => 'Saat ini',
            'value' => round((float) $currentRisk['total_score'], 2),
            'raw_value' => round((float) $currentRisk['total_score'], 2),
            'risk_level' => $this->riskLevel((float) $currentRisk['total_score']),
        ]);

        return $riskTrend->values();
    }

    private function calculateCurrentRisk(
        Collection $inflationTrend,
        ?WeatherData $weather,
        ?ExchangeRate $currency
    ): array {
        $latestInflation = $inflationTrend->last();
        $inflationValue = $latestInflation
            ? (float) $latestInflation['raw_value']
            : null;

        $inflationScore = $this->calculateInflationRisk($inflationValue);
        $weatherScore = $weather
            ? round((float) $weather->weather_risk, 2)
            : 25.0;
        $currencyScore = $currency
            ? round((float) $currency->currency_risk, 2)
            : 20.0;
        $newsScore = 35.0;

        $totalScore = (
            ($weatherScore * 0.30)
            + ($inflationScore * 0.20)
            + ($currencyScore * 0.10)
            + ($newsScore * 0.40)
        );

        return [
            'weather_score' => round($weatherScore, 2),
            'inflation_score' => round($inflationScore, 2),
            'currency_score' => round($currencyScore, 2),
            'news_score' => round($newsScore, 2),
            'total_score' => round($totalScore, 2),
        ];
    }

    private function calculateInflationRisk(?float $inflation): float
    {
        if ($inflation === null) {
            return 30.0;
        }

        $inflation = abs($inflation);

        return match (true) {
            $inflation <= 3 => 10.0,
            $inflation <= 5 => 25.0,
            $inflation <= 8 => 50.0,
            $inflation <= 12 => 75.0,
            default => 90.0,
        };
    }

    private function buildTrendInsight(
        Collection $gdpTrend,
        Collection $inflationTrend,
        Collection $currencyTrend,
        Collection $riskTrend
    ): array {
        return [
            'gdp' => $this->buildSingleInsight(
                $gdpTrend,
                'GDP',
                'GDP negara menunjukkan kecenderungan meningkat.',
                'GDP negara menunjukkan kecenderungan menurun.',
                'GDP negara relatif stabil atau data pembanding masih terbatas.'
            ),
            'inflation' => $this->buildSingleInsight(
                $inflationTrend,
                'Inflation',
                'Inflasi meningkat sehingga perlu dipantau dalam keputusan impor dan biaya logistik.',
                'Inflasi menurun sehingga tekanan biaya ekonomi cenderung lebih terkendali.',
                'Inflasi relatif stabil atau data pembanding masih terbatas.'
            ),
            'currency' => $this->buildSingleInsight(
                $currencyTrend,
                'Currency',
                'Nilai tukar meningkat terhadap USD, sehingga perlu dipantau untuk biaya transaksi.',
                'Nilai tukar menurun terhadap USD, menunjukkan perubahan kurs yang perlu dianalisis.',
                'Kurs relatif stabil atau data pembanding masih terbatas.'
            ),
            'risk' => $this->buildSingleInsight(
                $riskTrend,
                'Risk',
                'Risk score meningkat sehingga negara ini perlu dipantau lebih ketat.',
                'Risk score menurun sehingga kondisi risiko cenderung membaik.',
                'Risk score relatif stabil atau data pembanding masih terbatas.'
            ),
        ];
    }

    private function buildSingleInsight(
        Collection $trend,
        string $label,
        string $upText,
        string $downText,
        string $stableText
    ): array {
        if ($trend->count() < 2) {
            return [
                'label' => $label,
                'direction' => 'stable',
                'description' => $stableText,
            ];
        }

        $previous = (float) $trend->slice(-2, 1)->first()['raw_value'];
        $latest = (float) $trend->last()['raw_value'];
        $difference = $latest - $previous;

        if (abs($difference) < 0.0001) {
            return [
                'label' => $label,
                'direction' => 'stable',
                'description' => $stableText,
            ];
        }

        return [
            'label' => $label,
            'direction' => $difference > 0 ? 'up' : 'down',
            'description' => $difference > 0 ? $upText : $downText,
        ];
    }

    private function latestDisplayValue(
        Collection $trend,
        string $unit
    ): string {
        $latest = $trend->last();

        if (!$latest) {
            return 'Belum tersedia';
        }

        $value = (float) $latest['raw_value'];

        if ($unit === '%') {
            return number_format($value, 2, ',', '.') . '%';
        }

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

    private function latestCurrencyValue(
        Collection $trend,
        Country $country
    ): string {
        $latest = $trend->last();

        if (!$latest) {
            return 'Belum tersedia';
        }

        return '1 USD = '
            . number_format((float) $latest['value'], 4, ',', '.')
            . ' '
            . ($country->currency_code ?? '');
    }

    private function latestRiskValue(Collection $trend): string
    {
        $latest = $trend->last();

        if (!$latest) {
            return 'Belum tersedia';
        }

        return number_format((float) $latest['value'], 2, ',', '.');
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

    private function riskLevel(float $score): string
    {
        return match (true) {
            $score >= 75 => 'critical',
            $score >= 50 => 'high',
            $score >= 25 => 'moderate',
            default => 'low',
        };
    }
}
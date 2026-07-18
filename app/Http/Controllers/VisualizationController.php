<?php

namespace App\Http\Controllers;

use App\Models\Country;
use App\Models\EconomicIndicator;
use App\Models\ExchangeRate;
use App\Models\RiskScore;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\View\View;

class VisualizationController extends Controller
{
    private const GDP_CODE = 'NY.GDP.MKTP.CD';
    private const INFLATION_CODE = 'FP.CPI.TOTL.ZG';

    public function index(Request $request): View
    {
        return view('visualization.index', $this->buildVisualizationData($request));
    }

    public function show(Request $request): JsonResponse
    {
        $data = $this->buildVisualizationData($request);

        return response()->json([
            'success' => true,
            'message' => 'Data visualization dashboard berhasil dimuat.',
            'selected_country' => $data['selectedCountry'],
            'summary' => $data['summary'],
            'chart_data' => $data['chartData'],
        ]);
    }

    private function buildVisualizationData(Request $request): array
    {
        $countries = Country::query()
            ->orderBy('name')
            ->get();

        $selectedIsoCode = strtoupper(
            trim($request->string('country', 'IDN')->toString())
        );

        $selectedCountry = Country::query()
            ->where('iso3_code', $selectedIsoCode)
            ->orWhere('iso2_code', $selectedIsoCode)
            ->first();

        if (!$selectedCountry) {
            $selectedCountry = Country::query()
                ->where('iso3_code', 'IDN')
                ->first();
        }

        if (!$selectedCountry) {
            $selectedCountry = $countries->firstOrFail();
        }

        $gdpTrend = $this->getEconomicTrend(
            $selectedCountry,
            self::GDP_CODE
        );

        $inflationTrend = $this->getEconomicTrend(
            $selectedCountry,
            self::INFLATION_CODE
        );

        $currencyTrend = $this->getCurrencyTrend($selectedCountry);

        $riskTrend = $this->getRiskTrend($selectedCountry);

        return [
            'countries' => $countries,
            'selectedCountry' => $selectedCountry,
            'summary' => [
                'gdp_points' => $gdpTrend->count(),
                'inflation_points' => $inflationTrend->count(),
                'currency_points' => $currencyTrend->count(),
                'risk_points' => $riskTrend->count(),
                'latest_gdp' => $this->latestDisplayValue($gdpTrend, 'US$'),
                'latest_inflation' => $this->latestDisplayValue($inflationTrend, '%'),
                'latest_currency' => $this->latestCurrencyValue($currencyTrend),
                'latest_risk' => $this->latestRiskValue($riskTrend),
            ],
            'chartData' => [
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
            ],
        ];
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

    private function getRiskTrend(Country $country): Collection
    {
        return RiskScore::query()
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

    private function latestCurrencyValue(Collection $trend): string
    {
        $latest = $trend->last();

        if (!$latest) {
            return 'Belum tersedia';
        }

        return number_format((float) $latest['value'], 4, ',', '.');
    }

    private function latestRiskValue(Collection $trend): string
    {
        $latest = $trend->last();

        if (!$latest) {
            return 'Belum tersedia';
        }

        return number_format((float) $latest['value'], 2, ',', '.');
    }
}
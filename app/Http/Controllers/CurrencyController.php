<?php

namespace App\Http\Controllers;

use App\Models\Country;
use App\Models\ExchangeRate;
use App\Services\ExchangeRateService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Throwable;

class CurrencyController extends Controller
{
    public function index(
        Request $request,
        ExchangeRateService $exchangeRateService
    ): View {
        return view('currency.index', $this->buildCurrencyData(
            $request,
            $exchangeRateService
        ));
    }

    public function show(
        Request $request,
        ExchangeRateService $exchangeRateService
    ): JsonResponse {
        $data = $this->buildCurrencyData(
            $request,
            $exchangeRateService
        );

        /** @var Country $selectedCountry */
        $selectedCountry = $data['selectedCountry'];

        return response()->json([
            'success' => true,
            'message' => 'Data kurs mata uang berhasil dimuat.',
            'selected_country' => [
                'id' => $selectedCountry->id,
                'name' => $selectedCountry->name,
                'official_name' => $selectedCountry->official_name,
                'iso2_code' => $selectedCountry->iso2_code,
                'iso3_code' => $selectedCountry->iso3_code,
                'currency_code' => $selectedCountry->currency_code,
                'currency_name' => $selectedCountry->currency_name,
                'currency_symbol' => $selectedCountry->currency_symbol,
                'flag_url' => $selectedCountry->flag_url,
            ],
            'currency' => $this->formatExchangeRate($data['exchangeRate']),
            'history' => $data['history']
                ->map(fn (ExchangeRate $exchangeRate) => $this->formatExchangeRate($exchangeRate))
                ->values(),
            'summary' => [
                'available' => $data['currencyAvailable'],
                'display_rate' => $data['displayRate'],
                'display_change' => $data['displayChange'],
                'currency_risk' => $data['currencyRisk'],
                'risk_label' => $data['riskLabel'],
                'last_update' => $data['lastUpdate'],
            ],
            'chart_data' => $data['chartData'],
        ]);
    }

    private function buildCurrencyData(
        Request $request,
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

        $forceRefresh = $request->boolean('refresh');

        [$exchangeRate, $apiError] = $this->getRealtimeExchangeRate(
            $selectedCountry,
            $exchangeRateService,
            $forceRefresh
        );

        $history = ExchangeRate::query()
            ->where('country_id', $selectedCountry->id)
            ->where('base_currency', 'USD')
            ->where('target_currency', $selectedCountry->currency_code)
            ->latest('recorded_at')
            ->limit(40)
            ->get()
            ->unique(function (ExchangeRate $item) {
                return $item->recorded_at?->format('Y-m-d H:i') . '-' . $item->rate;
            })
            ->take(14)
            ->sortBy('recorded_at')
            ->values();

        $currencyAvailable = $exchangeRate !== null;

        $displayRate = $currencyAvailable
            ? '1 '
                . $exchangeRate->base_currency
                . ' = '
                . number_format((float) $exchangeRate->rate, 4, ',', '.')
                . ' '
                . $exchangeRate->target_currency
            : 'Belum tersedia';

        $displayChange = $currencyAvailable && $exchangeRate->change_percentage !== null
            ? number_format((float) $exchangeRate->change_percentage, 4, ',', '.') . '%'
            : 'Belum ada pembanding';

        $currencyRisk = $currencyAvailable
            ? round((float) $exchangeRate->currency_risk, 2)
            : 0.0;

        $riskLabel = $this->riskScoreLabel($currencyRisk);

        $lastUpdate = $currencyAvailable
            ? $exchangeRate->recorded_at?->format('d M Y H:i')
            : null;

        $chartLabels = $history
            ->map(fn (ExchangeRate $item) => $item->recorded_at?->format('d M H:i') ?? '-')
            ->values()
            ->all();

        $chartRates = $history
            ->map(fn (ExchangeRate $item) => round((float) $item->rate, 4))
            ->values()
            ->all();

        $chartRisks = $history
            ->map(fn (ExchangeRate $item) => round((float) $item->currency_risk, 2))
            ->values()
            ->all();

        if (empty($chartLabels)) {
            $chartLabels = ['Belum ada data'];
            $chartRates = [0];
            $chartRisks = [0];
        }

        return [
            'countries' => $countries,
            'selectedCountry' => $selectedCountry,
            'exchangeRate' => $exchangeRate,
            'history' => $history,
            'currencyAvailable' => $currencyAvailable,
            'displayRate' => $displayRate,
            'displayChange' => $displayChange,
            'currencyRisk' => $currencyRisk,
            'riskLabel' => $riskLabel,
            'lastUpdate' => $lastUpdate,
            'apiError' => $apiError,
            'chartData' => [
                'rate' => [
                    'labels' => $chartLabels,
                    'values' => $chartRates,
                ],
                'risk' => [
                    'labels' => $chartLabels,
                    'values' => $chartRisks,
                ],
            ],
        ];
    }

    private function getRealtimeExchangeRate(
        Country $country,
        ExchangeRateService $exchangeRateService,
        bool $forceRefresh = false
    ): array {
        try {
            $exchangeRate = $exchangeRateService->getLatestRate(
                $country,
                'USD',
                $forceRefresh
            );

            return [$exchangeRate, null];
        } catch (Throwable) {
            $fallback = ExchangeRate::query()
                ->where('country_id', $country->id)
                ->where('base_currency', 'USD')
                ->where('target_currency', $country->currency_code)
                ->latest('recorded_at')
                ->first();

            return [
                $fallback,
                'Data kurs terbaru belum dapat diperbarui. Sistem menampilkan data terakhir yang tersimpan.',
            ];
        }
    }

    private function formatExchangeRate(?ExchangeRate $exchangeRate): ?array
    {
        if (!$exchangeRate) {
            return null;
        }

        return [
            'id' => $exchangeRate->id,
            'country_id' => $exchangeRate->country_id,
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

    private function riskScoreLabel(float $score): string
    {
        return match (true) {
            $score >= 75 => 'Risiko Kritis',
            $score >= 50 => 'Risiko Tinggi',
            $score >= 25 => 'Risiko Sedang',
            default => 'Risiko Rendah',
        };
    }
}
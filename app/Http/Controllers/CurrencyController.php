<?php

namespace App\Http\Controllers;

use App\Models\Country;
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
        $countries = Country::query()
            ->whereNotNull('currency_code')
            ->orderBy('name')
            ->get();

        $selectedIsoCode = strtoupper(
            trim($request->string('country', 'IDN')->toString())
        );

        $baseCurrency = strtoupper(
            trim($request->string('base', 'USD')->toString())
        );

        $selectedCountry = Country::query()
            ->whereNotNull('currency_code')
            ->where(function ($query) use ($selectedIsoCode) {
                $query->where('iso3_code', $selectedIsoCode)
                    ->orWhere('iso2_code', $selectedIsoCode);
            })
            ->first();

        if (!$selectedCountry) {
            $selectedCountry = Country::query()
                ->where('iso3_code', 'IDN')
                ->firstOrFail();
        }

        $exchangeRate = null;
        $history = collect();
        $errorMessage = null;

        try {
            $exchangeRate = $exchangeRateService->getLatestRate(
                $selectedCountry,
                $baseCurrency,
                $request->boolean('refresh')
            );

            $history = $exchangeRateService->getRateHistory(
                $selectedCountry,
                $baseCurrency
            );
        } catch (Throwable $exception) {
            $errorMessage = $exception->getMessage();
        }

        return view('currency.index', [
            'countries' => $countries,
            'selectedCountry' => $selectedCountry,
            'baseCurrency' => $baseCurrency,
            'exchangeRate' => $exchangeRate,
            'history' => $history,
            'errorMessage' => $errorMessage,
        ]);
    }

    public function show(
        Request $request,
        ExchangeRateService $exchangeRateService
    ): JsonResponse {
        $countryCode = strtoupper(
            trim($request->string('country', 'IDN')->toString())
        );

        $baseCurrency = strtoupper(
            trim($request->string('base', 'USD')->toString())
        );

        $country = Country::query()
            ->whereNotNull('currency_code')
            ->where(function ($query) use ($countryCode) {
                $query->where('iso3_code', $countryCode)
                    ->orWhere('iso2_code', $countryCode);
            })
            ->firstOrFail();

        $exchangeRate = $exchangeRateService->getLatestRate(
            $country,
            $baseCurrency,
            $request->boolean('refresh')
        );

        return response()->json([
            'country' => [
                'id' => $country->id,
                'name' => $country->name,
                'iso2_code' => $country->iso2_code,
                'iso3_code' => $country->iso3_code,
                'currency_code' => $country->currency_code,
                'currency_name' => $country->currency_name,
            ],
            'exchange_rate' => [
                'base_currency' => $exchangeRate->base_currency,
                'target_currency' => $exchangeRate->target_currency,
                'rate' => (float) $exchangeRate->rate,
                'change_percentage' => $exchangeRate->change_percentage !== null
                    ? (float) $exchangeRate->change_percentage
                    : null,
                'currency_risk' => (float) $exchangeRate->currency_risk,
                'recorded_at' => $exchangeRate->recorded_at,
                'fetched_at' => $exchangeRate->fetched_at,
            ],
        ]);
    }
}
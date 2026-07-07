<?php

namespace App\Http\Controllers;

use App\Models\Country;
use App\Services\WeatherService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Throwable;

class WeatherController extends Controller
{
    public function index(
        Request $request,
        WeatherService $weatherService
    ): View {
        $countries = Country::query()
            ->whereNotNull('latitude')
            ->whereNotNull('longitude')
            ->orderBy('name')
            ->get();

        $selectedIsoCode = strtoupper(
            trim($request->string('country', 'IDN')->toString())
        );

        $selectedCountry = Country::query()
            ->whereNotNull('latitude')
            ->whereNotNull('longitude')
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

        $weather = null;
        $history = collect();
        $errorMessage = null;

        try {
            $weather = $weatherService->getCurrentWeather(
                $selectedCountry,
                $request->boolean('refresh')
            );

            $history = $selectedCountry
                ->weatherData()
                ->latest('recorded_at')
                ->limit(20)
                ->get();
        } catch (Throwable $exception) {
            $errorMessage = $exception->getMessage();
        }

        return view('weather.index', [
            'countries' => $countries,
            'selectedCountry' => $selectedCountry,
            'weather' => $weather,
            'history' => $history,
            'errorMessage' => $errorMessage,
        ]);
    }

    public function show(
        Request $request,
        WeatherService $weatherService
    ): JsonResponse {
        $countryCode = strtoupper(
            trim($request->string('country', 'IDN')->toString())
        );

        $country = Country::query()
            ->whereNotNull('latitude')
            ->whereNotNull('longitude')
            ->where(function ($query) use ($countryCode) {
                $query->where('iso3_code', $countryCode)
                    ->orWhere('iso2_code', $countryCode);
            })
            ->firstOrFail();

        $weather = $weatherService->getCurrentWeather(
            $country,
            $request->boolean('refresh')
        );

        return response()->json([
            'country' => [
                'id' => $country->id,
                'name' => $country->name,
                'iso2_code' => $country->iso2_code,
                'iso3_code' => $country->iso3_code,
                'latitude' => (float) $country->latitude,
                'longitude' => (float) $country->longitude,
            ],
            'weather' => [
                'temperature' => (float) $weather->temperature,
                'precipitation' => (float) $weather->precipitation,
                'wind_speed' => (float) $weather->wind_speed,
                'weather_code' => (int) $weather->weather_code,
                'weather_risk' => (float) $weather->weather_risk,
                'recorded_at' => $weather->recorded_at,
                'fetched_at' => $weather->fetched_at,
            ],
        ]);
    }
}
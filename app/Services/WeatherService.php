<?php

namespace App\Services;

use App\Models\Country;
use App\Models\WeatherData;
use Illuminate\Support\Facades\Http;
use RuntimeException;

class WeatherService
{
    private const CACHE_MINUTES = 30;

    public function __construct(
        private readonly RiskScoringService $riskScoringService
    ) {
    }

    public function getCurrentWeather(
        Country $country,
        bool $forceRefresh = false
    ): WeatherData {
        $this->ensureCoordinatesAvailable($country);

        if (!$forceRefresh) {
            $cachedWeather = $this->getFreshCachedWeather($country);

            if ($cachedWeather) {
                return $cachedWeather;
            }
        }

        return $this->fetchAndStore($country);
    }

    private function fetchAndStore(Country $country): WeatherData
    {
        $baseUrl = config(
            'services.open_meteo.base_url',
            'https://api.open-meteo.com/v1'
        );

        $response = Http::acceptJson()
            ->timeout(30)
            ->retry(3, 1000)
            ->get(
                rtrim($baseUrl, '/') . '/forecast',
                [
                    'latitude' => (float) $country->latitude,
                    'longitude' => (float) $country->longitude,
                    'current' => implode(',', [
                        'temperature_2m',
                        'precipitation',
                        'wind_speed_10m',
                        'weather_code',
                    ]),
                    'timezone' => 'auto',
                ]
            );

        if ($response->failed()) {
            throw new RuntimeException(
                'Gagal mengambil data cuaca untuk '
                . $country->name
                . '. Status API: '
                . $response->status()
            );
        }

        $current = $response->json('current');

        if (!is_array($current)) {
            throw new RuntimeException(
                'Data cuaca untuk '
                . $country->name
                . ' tidak tersedia dari Open-Meteo.'
            );
        }

        $temperature = (float) ($current['temperature_2m'] ?? 0);
        $precipitation = (float) ($current['precipitation'] ?? 0);
        $windSpeed = (float) ($current['wind_speed_10m'] ?? 0);
        $weatherCode = (int) ($current['weather_code'] ?? 0);

        $weatherRisk = $this->riskScoringService
            ->calculateWeatherScore(
                $precipitation,
                $windSpeed,
                $weatherCode
            );

        return WeatherData::create([
            'country_id' => $country->id,
            'temperature' => $temperature,
            'precipitation' => $precipitation,
            'wind_speed' => $windSpeed,
            'weather_code' => $weatherCode,
            'weather_risk' => $weatherRisk,
            'recorded_at' => $current['time'] ?? now(),
            'fetched_at' => now(),
        ]);
    }

    private function getFreshCachedWeather(
        Country $country
    ): ?WeatherData {
        return WeatherData::query()
            ->where('country_id', $country->id)
            ->where(
                'fetched_at',
                '>=',
                now()->subMinutes(self::CACHE_MINUTES)
            )
            ->latest('recorded_at')
            ->first();
    }

    private function ensureCoordinatesAvailable(
        Country $country
    ): void {
        if (
            $country->latitude === null ||
            $country->longitude === null
        ) {
            throw new RuntimeException(
                'Koordinat untuk '
                . $country->name
                . ' belum tersedia.'
            );
        }
    }
}
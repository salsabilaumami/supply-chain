<?php

namespace App\Services;

use App\Models\Country;
use App\Models\WeatherData;
use Illuminate\Support\Facades\Http;
use RuntimeException;

class WeatherService
{
    private const CACHE_MINUTES = 30;

    private const RAIN_CODES = [
        51, 53, 55,
        56, 57,
        61, 63, 65,
        66, 67,
        80, 81, 82,
    ];

    private const STORM_CODES = [
        95, 96, 99,
    ];

    private const STRONG_WIND_LIMIT = 40.0;

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

    public function getDailyForecast(
        Country $country,
        int $days = 7
    ): array {
        $this->ensureCoordinatesAvailable($country);

        $days = max(1, min($days, 7));

        $payload = $this->requestForecast($country, [
            'latitude' => (float) $country->latitude,
            'longitude' => (float) $country->longitude,
            'daily' => implode(',', [
                'weather_code',
                'temperature_2m_max',
                'temperature_2m_min',
                'precipitation_sum',
                'wind_speed_10m_max',
            ]),
            'forecast_days' => $days,
            'timezone' => 'auto',
        ]);

        $daily = $payload['daily'] ?? [];

        if (!is_array($daily) || empty($daily['time'])) {
            return [];
        }

        $forecast = [];

        foreach ($daily['time'] as $index => $date) {
            $weatherCode = (int) ($daily['weather_code'][$index] ?? 0);
            $precipitation = (float) ($daily['precipitation_sum'][$index] ?? 0);
            $windSpeed = (float) ($daily['wind_speed_10m_max'][$index] ?? 0);
            $temperatureMax = (float) ($daily['temperature_2m_max'][$index] ?? 0);
            $temperatureMin = (float) ($daily['temperature_2m_min'][$index] ?? 0);

            $alerts = $this->detectWeatherAlerts(
                $precipitation,
                $windSpeed,
                $weatherCode
            );

            $shippingImpact = $this->buildShippingImpact(
                $precipitation,
                $windSpeed,
                $weatherCode
            );

            $forecast[] = [
                'date' => $date,
                'day_label' => $this->formatForecastDay($date),
                'weather_code' => $weatherCode,
                'condition' => $this->describeWeatherCode($weatherCode),
                'temperature_max' => $temperatureMax,
                'temperature_min' => $temperatureMin,
                'precipitation' => $precipitation,
                'wind_speed' => $windSpeed,
                'alerts' => $alerts,
                'shipping_impact' => $shippingImpact,
            ];
        }

        return $forecast;
    }

    public function describeWeatherCode(int $weatherCode): string
    {
        return match (true) {
            $weatherCode === 0 => 'Cerah',
            in_array($weatherCode, [1, 2, 3], true) => 'Berawan',
            in_array($weatherCode, [45, 48], true) => 'Berkabut',
            in_array($weatherCode, [51, 53, 55, 56, 57], true) => 'Gerimis',
            in_array($weatherCode, [61, 63, 65, 66, 67], true) => 'Hujan',
            in_array($weatherCode, [71, 73, 75, 77], true) => 'Salju',
            in_array($weatherCode, [80, 81, 82], true) => 'Hujan Lokal',
            in_array($weatherCode, [85, 86], true) => 'Hujan Salju',
            in_array($weatherCode, self::STORM_CODES, true) => 'Badai Petir',
            default => 'Tidak diketahui',
        };
    }

    public function detectWeatherAlerts(
        float $precipitation,
        float $windSpeed,
        int $weatherCode
    ): array {
        $isRain = $precipitation > 0
            || in_array($weatherCode, self::RAIN_CODES, true);

        $isStorm = in_array($weatherCode, self::STORM_CODES, true);

        $isStrongWind = $windSpeed >= self::STRONG_WIND_LIMIT;

        return [
            'rain' => [
                'active' => $isRain,
                'label' => $isRain ? 'Hujan terdeteksi' : 'Tidak ada hujan',
                'value' => $precipitation,
            ],
            'storm' => [
                'active' => $isStorm,
                'label' => $isStorm ? 'Badai terdeteksi' : 'Tidak ada badai',
                'value' => $weatherCode,
            ],
            'strong_wind' => [
                'active' => $isStrongWind,
                'label' => $isStrongWind ? 'Angin kencang terdeteksi' : 'Angin normal',
                'value' => $windSpeed,
            ],
        ];
    }

    public function buildShippingImpact(
        float $precipitation,
        float $windSpeed,
        int $weatherCode
    ): array {
        $alerts = $this->detectWeatherAlerts(
            $precipitation,
            $windSpeed,
            $weatherCode
        );

        if ($alerts['storm']['active']) {
            return [
                'level' => 'high',
                'label' => 'Berisiko Tinggi',
                'description' => 'Badai berpotensi mengganggu aktivitas pelabuhan dan pengiriman.',
            ];
        }

        if ($alerts['strong_wind']['active']) {
            return [
                'level' => 'medium',
                'label' => 'Waspada Angin',
                'description' => 'Angin kencang dapat memengaruhi proses bongkar muat dan jadwal pengiriman.',
            ];
        }

        if ($alerts['rain']['active']) {
            return [
                'level' => 'medium',
                'label' => 'Waspada Hujan',
                'description' => 'Hujan dapat memperlambat aktivitas logistik di area pelabuhan.',
            ];
        }

        return [
            'level' => 'low',
            'label' => 'Normal',
            'description' => 'Tidak ada indikasi hujan, badai, atau angin kencang.',
        ];
    }

    private function fetchAndStore(Country $country): WeatherData
    {
        $payload = $this->requestForecast($country, [
            'latitude' => (float) $country->latitude,
            'longitude' => (float) $country->longitude,
            'current' => implode(',', [
                'temperature_2m',
                'precipitation',
                'wind_speed_10m',
                'weather_code',
            ]),
            'timezone' => 'auto',
        ]);

        $current = $payload['current'] ?? null;

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

    private function requestForecast(
        Country $country,
        array $query
    ): array {
        $baseUrl = config(
            'services.open_meteo.base_url',
            'https://api.open-meteo.com/v1'
        );

        $response = Http::acceptJson()
            ->timeout(30)
            ->retry(3, 1000)
            ->get(
                rtrim($baseUrl, '/') . '/forecast',
                $query
            );

        if ($response->failed()) {
            throw new RuntimeException(
                'Gagal mengambil data cuaca untuk '
                . $country->name
                . '. Status API: '
                . $response->status()
            );
        }

        $payload = $response->json();

        if (!is_array($payload)) {
            throw new RuntimeException(
                'Respons cuaca untuk '
                . $country->name
                . ' tidak valid.'
            );
        }

        return $payload;
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

    private function formatForecastDay(string $date): string
    {
        return date('d M', strtotime($date));
    }
}
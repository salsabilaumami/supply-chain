<?php

namespace App\Http\Controllers;

use App\Models\Country;
use App\Models\WeatherData;
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
        return view('weather.index', $this->buildWeatherData(
            $request,
            $weatherService
        ));
    }

    public function show(
        Request $request,
        WeatherService $weatherService
    ): JsonResponse {
        $data = $this->buildWeatherData(
            $request,
            $weatherService
        );

        return response()->json([
            'success' => true,
            'message' => 'Data cuaca berhasil dimuat.',
            'selected_country' => [
                'id' => $data['selectedCountry']->id,
                'name' => $data['selectedCountry']->name,
                'iso2_code' => $data['selectedCountry']->iso2_code,
                'iso3_code' => $data['selectedCountry']->iso3_code,
                'latitude' => $data['selectedCountry']->latitude,
                'longitude' => $data['selectedCountry']->longitude,
                'flag_url' => $data['selectedCountry']->flag_url,
            ],
            'current_weather' => $data['currentSummary'],
            'map_point' => $data['mapPoint'],
            'forecast' => $data['forecast'],
            'chart_data' => $data['chartData'],
        ]);
    }

    private function buildWeatherData(
        Request $request,
        WeatherService $weatherService
    ): array {
        $countries = Country::query()
            ->whereNotNull('latitude')
            ->whereNotNull('longitude')
            ->alphabetical()
            ->get();

        $selectedIsoCode = strtoupper(
            trim($request->string('country', 'IDN')->toString())
        );

        $selectedCountry = Country::query()
            ->byIsoCode($selectedIsoCode)
            ->whereNotNull('latitude')
            ->whereNotNull('longitude')
            ->first();

        if (!$selectedCountry) {
            $selectedCountry = Country::query()
                ->where('iso3_code', 'IDN')
                ->whereNotNull('latitude')
                ->whereNotNull('longitude')
                ->first();
        }

        if (!$selectedCountry) {
            $selectedCountry = $countries->firstOrFail();
        }

        $forceRefresh = $request->boolean('refresh');

        [$weatherData, $apiError] = $this->getCurrentWeather(
            $selectedCountry,
            $weatherService,
            $forceRefresh
        );

        [$forecast, $forecastError] = $this->getForecast(
            $selectedCountry,
            $weatherService
        );

        $currentSummary = $this->buildCurrentSummary(
            $selectedCountry,
            $weatherData,
            $weatherService
        );

        $mapPoint = $this->buildMapPoint(
            $selectedCountry,
            $currentSummary
        );

        $chartData = $this->buildChartData($forecast);

        return [
            'countries' => $countries,
            'selectedCountry' => $selectedCountry,
            'weatherData' => $weatherData,
            'currentSummary' => $currentSummary,
            'mapPoint' => $mapPoint,
            'forecast' => $forecast,
            'apiError' => $apiError,
            'forecastError' => $forecastError,
            'chartData' => $chartData,
        ];
    }

    private function getCurrentWeather(
        Country $country,
        WeatherService $weatherService,
        bool $forceRefresh
    ): array {
        try {
            return [
                $weatherService->getCurrentWeather(
                    $country,
                    $forceRefresh
                ),
                null,
            ];
        } catch (Throwable) {
            $fallback = WeatherData::query()
                ->where('country_id', $country->id)
                ->latest('recorded_at')
                ->first();

            return [
                $fallback,
                'Data cuaca terbaru belum dapat diperbarui. Sistem menampilkan data terakhir yang tersimpan.',
            ];
        }
    }

    private function getForecast(
        Country $country,
        WeatherService $weatherService
    ): array {
        try {
            return [
                $weatherService->getDailyForecast($country, 7),
                null,
            ];
        } catch (Throwable $exception) {
            return [
                [],
                $exception->getMessage(),
            ];
        }
    }

    private function buildCurrentSummary(
        Country $country,
        ?WeatherData $weatherData,
        WeatherService $weatherService
    ): array {
        if (!$weatherData) {
            return [
                'available' => false,
                'country' => $country->name,
                'temperature' => 0,
                'precipitation' => 0,
                'wind_speed' => 0,
                'weather_code' => 0,
                'condition' => 'Belum tersedia',
                'weather_risk' => 0,
                'risk_label' => 'Belum tersedia',
                'recorded_at' => null,
                'alerts' => [
                    'rain' => [
                        'active' => false,
                        'label' => 'Belum tersedia',
                        'value' => 0,
                    ],
                    'storm' => [
                        'active' => false,
                        'label' => 'Belum tersedia',
                        'value' => 0,
                    ],
                    'strong_wind' => [
                        'active' => false,
                        'label' => 'Belum tersedia',
                        'value' => 0,
                    ],
                ],
                'shipping_impact' => [
                    'level' => 'unknown',
                    'label' => 'Belum tersedia',
                    'description' => 'Data cuaca belum tersedia.',
                ],
            ];
        }

        $temperature = (float) $weatherData->temperature;
        $precipitation = (float) $weatherData->precipitation;
        $windSpeed = (float) $weatherData->wind_speed;
        $weatherCode = (int) $weatherData->weather_code;
        $weatherRisk = round((float) $weatherData->weather_risk, 2);

        return [
            'available' => true,
            'country' => $country->name,
            'temperature' => $temperature,
            'precipitation' => $precipitation,
            'wind_speed' => $windSpeed,
            'weather_code' => $weatherCode,
            'condition' => $weatherService->describeWeatherCode($weatherCode),
            'weather_risk' => $weatherRisk,
            'risk_label' => $this->riskLabel($weatherRisk),
            'recorded_at' => $weatherData->recorded_at?->format('d M Y H:i'),
            'alerts' => $weatherService->detectWeatherAlerts(
                $precipitation,
                $windSpeed,
                $weatherCode
            ),
            'shipping_impact' => $weatherService->buildShippingImpact(
                $precipitation,
                $windSpeed,
                $weatherCode
            ),
        ];
    }

    private function buildMapPoint(
        Country $country,
        array $summary
    ): array {
        $alerts = $summary['alerts'];

        $type = 'normal';
        $label = 'Normal';

        if ($alerts['storm']['active']) {
            $type = 'storm';
            $label = 'Badai';
        } elseif ($alerts['strong_wind']['active']) {
            $type = 'wind';
            $label = 'Angin Kencang';
        } elseif ($alerts['rain']['active']) {
            $type = 'rain';
            $label = 'Hujan';
        }

        return [
            'country' => $country->name,
            'iso3_code' => $country->iso3_code,
            'latitude' => (float) $country->latitude,
            'longitude' => (float) $country->longitude,
            'type' => $type,
            'label' => $label,
            'temperature' => $summary['temperature'],
            'precipitation' => $summary['precipitation'],
            'wind_speed' => $summary['wind_speed'],
            'weather_code' => $summary['weather_code'],
            'condition' => $summary['condition'],
        ];
    }

    private function buildChartData(array $forecast): array
    {
        return [
            'labels' => array_map(
                fn (array $item) => $item['day_label'],
                $forecast
            ),
            'temperature_max' => array_map(
                fn (array $item) => round((float) $item['temperature_max'], 2),
                $forecast
            ),
            'temperature_min' => array_map(
                fn (array $item) => round((float) $item['temperature_min'], 2),
                $forecast
            ),
            'precipitation' => array_map(
                fn (array $item) => round((float) $item['precipitation'], 2),
                $forecast
            ),
            'wind_speed' => array_map(
                fn (array $item) => round((float) $item['wind_speed'], 2),
                $forecast
            ),
        ];
    }

    private function riskLabel(float $score): string
    {
        return match (true) {
            $score >= 75 => 'Risiko Kritis',
            $score >= 50 => 'Risiko Tinggi',
            $score >= 25 => 'Risiko Sedang',
            default => 'Risiko Rendah',
        };
    }
}
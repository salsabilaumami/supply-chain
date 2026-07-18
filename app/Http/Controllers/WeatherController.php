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
        $data = $this->buildWeatherData(
            $request,
            $weatherService
        );

        return view('weather.index', $data);
    }

    public function show(
        Request $request,
        WeatherService $weatherService
    ): JsonResponse {
        $data = $this->buildWeatherData(
            $request,
            $weatherService
        );

        /** @var Country $selectedCountry */
        $selectedCountry = $data['selectedCountry'];

        return response()->json([
            'success' => true,
            'message' => 'Data cuaca berhasil dimuat.',
            'selected_country' => [
                'id' => $selectedCountry->id,
                'name' => $selectedCountry->name,
                'official_name' => $selectedCountry->official_name,
                'iso2_code' => $selectedCountry->iso2_code,
                'iso3_code' => $selectedCountry->iso3_code,
                'capital' => $selectedCountry->capital,
                'region' => $selectedCountry->region,
                'subregion' => $selectedCountry->subregion,
                'latitude' => $selectedCountry->latitude,
                'longitude' => $selectedCountry->longitude,
                'flag_url' => $selectedCountry->flag_url,
            ],
            'weather' => $this->formatWeather($data['weatherData']),
            'history' => $data['history']
                ->map(fn (WeatherData $weather) => $this->formatWeather($weather))
                ->values(),
            'summary' => [
                'available' => $data['weatherAvailable'],
                'temperature' => $data['temperature'],
                'precipitation' => $data['precipitation'],
                'wind_speed' => $data['windSpeed'],
                'weather_code' => $data['weatherCode'],
                'weather_description' => $data['weatherDescription'],
                'weather_risk' => $data['weatherRisk'],
                'risk_label' => $data['riskLabel'],
                'weather_alert_type' => $data['weatherAlertType'],
                'weather_alert_label' => $data['weatherAlertLabel'],
                'last_update' => $data['lastUpdate'],
            ],
            'map_points' => $data['mapPoints'],
            'chart_data' => $data['chartData'],
        ]);
    }

    private function buildWeatherData(
        Request $request,
        WeatherService $weatherService
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

        
        $forceRefresh = $request->boolean('refresh', false);

        $weatherData = $this->getRealtimeWeather(
            $selectedCountry,
            $weatherService,
            $forceRefresh
        );

        $history = WeatherData::query()
            ->where('country_id', $selectedCountry->id)
            ->latest('recorded_at')
            ->limit(10)
            ->get()
            ->sortBy('recorded_at')
            ->values();

        $weatherAvailable = $weatherData !== null;

        $temperature = $weatherAvailable
            ? round((float) $weatherData->temperature, 2)
            : 0.0;

        $precipitation = $weatherAvailable
            ? round((float) $weatherData->precipitation, 2)
            : 0.0;

        $windSpeed = $weatherAvailable
            ? round((float) $weatherData->wind_speed, 2)
            : 0.0;

        $weatherCode = $weatherAvailable
            ? (int) $weatherData->weather_code
            : 0;

        $weatherRisk = $weatherAvailable
            ? round((float) $weatherData->weather_risk, 2)
            : 0.0;

        $weatherDescription = $weatherAvailable
            ? $this->describeWeatherCode($weatherCode)
            : 'Belum tersedia';

        $riskLabel = $this->riskScoreLabel($weatherRisk);

        $weatherAlert = $this->buildWeatherAlert(
            $weatherCode,
            $precipitation,
            $windSpeed,
            $weatherRisk
        );

        $lastUpdate = $weatherAvailable
            ? $weatherData->recorded_at?->format('d M Y H:i')
            : null;

        $chartLabels = $history
            ->map(fn (WeatherData $item) => $item->recorded_at?->format('d M H:i') ?? '-')
            ->values()
            ->all();

        $chartTemperature = $history
            ->map(fn (WeatherData $item) => round((float) $item->temperature, 2))
            ->values()
            ->all();

        $chartPrecipitation = $history
            ->map(fn (WeatherData $item) => round((float) $item->precipitation, 2))
            ->values()
            ->all();

        $chartWind = $history
            ->map(fn (WeatherData $item) => round((float) $item->wind_speed, 2))
            ->values()
            ->all();

        $chartRisk = $history
            ->map(fn (WeatherData $item) => round((float) $item->weather_risk, 2))
            ->values()
            ->all();

        if (empty($chartLabels)) {
            $chartLabels = ['Belum ada data'];
            $chartTemperature = [0];
            $chartPrecipitation = [0];
            $chartWind = [0];
            $chartRisk = [0];
        }

        $mapPoints = $this->buildMapPoints(
            $selectedCountry,
            $weatherData
        );

        return [
            'countries' => $countries,
            'selectedCountry' => $selectedCountry,

            'weatherData' => $weatherData,
            'history' => $history,
            'weatherAvailable' => $weatherAvailable,

            'temperature' => $temperature,
            'precipitation' => $precipitation,
            'windSpeed' => $windSpeed,
            'weatherCode' => $weatherCode,
            'weatherDescription' => $weatherDescription,
            'weatherRisk' => $weatherRisk,
            'riskLabel' => $riskLabel,
            'weatherAlertType' => $weatherAlert['type'],
            'weatherAlertLabel' => $weatherAlert['label'],
            'weatherAlertColor' => $weatherAlert['color'],
            'weatherAlertIcon' => $weatherAlert['icon'],
            'lastUpdate' => $lastUpdate,

            'mapPoints' => $mapPoints,

            'chartData' => [
                'temperature' => [
                    'labels' => $chartLabels,
                    'values' => $chartTemperature,
                ],
                'precipitation' => [
                    'labels' => $chartLabels,
                    'values' => $chartPrecipitation,
                ],
                'wind' => [
                    'labels' => $chartLabels,
                    'values' => $chartWind,
                ],
                'risk' => [
                    'labels' => $chartLabels,
                    'values' => $chartRisk,
                ],
                'mapPoints' => $mapPoints,
            ],
        ];
    }

    private function getRealtimeWeather(
        Country $country,
        WeatherService $weatherService,
        bool $forceRefresh = false
    ): ?WeatherData {
        try {
            return $weatherService->getCurrentWeather(
                $country,
                $forceRefresh
            );
        } catch (Throwable) {
            return WeatherData::query()
                ->where('country_id', $country->id)
                ->latest('recorded_at')
                ->first();
        }
    }

    private function buildMapPoints(
        Country $selectedCountry,
        ?WeatherData $selectedWeather
    ): array {
        $latestWeatherIds = WeatherData::query()
            ->selectRaw('MAX(id) as id')
            ->groupBy('country_id')
            ->pluck('id')
            ->filter()
            ->values();

        $latestWeather = WeatherData::query()
            ->whereIn('id', $latestWeatherIds)
            ->get()
            ->keyBy('country_id');

        if ($selectedWeather) {
            $latestWeather->put($selectedCountry->id, $selectedWeather);
        }

        if ($latestWeather->isEmpty()) {
            return [];
        }

        $countries = Country::query()
            ->whereIn('id', $latestWeather->keys()->values()->all())
            ->whereNotNull('latitude')
            ->whereNotNull('longitude')
            ->get()
            ->keyBy('id');

        return $latestWeather
            ->map(function (WeatherData $weather) use ($countries, $selectedCountry) {
                $country = $countries->get($weather->country_id);

                if (!$country) {
                    return null;
                }

                $weatherCode = (int) $weather->weather_code;
                $precipitation = round((float) $weather->precipitation, 2);
                $windSpeed = round((float) $weather->wind_speed, 2);
                $weatherRisk = round((float) $weather->weather_risk, 2);

                $alert = $this->buildWeatherAlert(
                    $weatherCode,
                    $precipitation,
                    $windSpeed,
                    $weatherRisk
                );

                return [
                    'country_id' => $country->id,
                    'name' => $country->name,
                    'iso2_code' => $country->iso2_code,
                    'iso3_code' => $country->iso3_code,
                    'capital' => $country->capital,
                    'latitude' => (float) $country->latitude,
                    'longitude' => (float) $country->longitude,
                    'temperature' => round((float) $weather->temperature, 2),
                    'precipitation' => $precipitation,
                    'wind_speed' => $windSpeed,
                    'weather_code' => $weatherCode,
                    'weather_description' => $this->describeWeatherCode($weatherCode),
                    'weather_risk' => $weatherRisk,
                    'risk_label' => $this->riskScoreLabel($weatherRisk),
                    'alert_type' => $alert['type'],
                    'alert_label' => $alert['label'],
                    'alert_color' => $alert['color'],
                    'alert_icon' => $alert['icon'],
                    'is_selected' => $country->id === $selectedCountry->id,
                    'recorded_at' => $weather->recorded_at?->format('d M Y H:i'),
                ];
            })
            ->filter()
            ->sortByDesc('weather_risk')
            ->values()
            ->all();
    }

    private function buildWeatherAlert(
        int $weatherCode,
        float $precipitation,
        float $windSpeed,
        float $weatherRisk
    ): array {
        if (in_array($weatherCode, [95, 96, 99], true)) {
            return [
                'type' => 'storm',
                'label' => 'Badai Petir',
                'color' => 'danger',
                'icon' => 'bi-cloud-lightning-rain',
            ];
        }

        if ($windSpeed >= 35) {
            return [
                'type' => 'strong_wind',
                'label' => 'Angin Kencang',
                'color' => 'warning',
                'icon' => 'bi-wind',
            ];
        }

        if (
            $precipitation > 0 ||
            in_array($weatherCode, [51, 53, 55, 56, 57, 61, 63, 65, 66, 67, 80, 81, 82], true)
        ) {
            return [
                'type' => 'rain',
                'label' => 'Hujan',
                'color' => 'primary',
                'icon' => 'bi-cloud-rain-heavy',
            ];
        }

        if ($weatherRisk >= 50) {
            return [
                'type' => 'weather_risk',
                'label' => 'Risiko Cuaca Tinggi',
                'color' => 'danger',
                'icon' => 'bi-exclamation-triangle',
            ];
        }

        return [
            'type' => 'normal',
            'label' => 'Normal',
            'color' => 'success',
            'icon' => 'bi-sun',
        ];
    }

    private function formatWeather(?WeatherData $weatherData): ?array
    {
        if (!$weatherData) {
            return null;
        }

        $weatherCode = (int) $weatherData->weather_code;
        $precipitation = round((float) $weatherData->precipitation, 2);
        $windSpeed = round((float) $weatherData->wind_speed, 2);
        $weatherRisk = round((float) $weatherData->weather_risk, 2);

        $alert = $this->buildWeatherAlert(
            $weatherCode,
            $precipitation,
            $windSpeed,
            $weatherRisk
        );

        return [
            'id' => $weatherData->id,
            'country_id' => $weatherData->country_id,
            'temperature' => round((float) $weatherData->temperature, 2),
            'precipitation' => $precipitation,
            'wind_speed' => $windSpeed,
            'weather_code' => $weatherCode,
            'weather_description' => $this->describeWeatherCode($weatherCode),
            'weather_risk' => $weatherRisk,
            'risk_label' => $this->riskScoreLabel($weatherRisk),
            'alert_type' => $alert['type'],
            'alert_label' => $alert['label'],
            'alert_color' => $alert['color'],
            'alert_icon' => $alert['icon'],
            'recorded_at' => $weatherData->recorded_at?->toDateTimeString(),
            'fetched_at' => $weatherData->fetched_at?->toDateTimeString(),
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

    private function describeWeatherCode(int $code): string
    {
        return match (true) {
            $code === 0 => 'Cerah',
            in_array($code, [1, 2, 3], true) => 'Cerah berawan',
            in_array($code, [45, 48], true) => 'Berkabut',
            in_array($code, [51, 53, 55, 56, 57], true) => 'Gerimis',
            in_array($code, [61, 63, 65, 66, 67], true) => 'Hujan',
            in_array($code, [71, 73, 75, 77], true) => 'Salju',
            in_array($code, [80, 81, 82], true) => 'Hujan lokal',
            in_array($code, [85, 86], true) => 'Hujan salju',
            in_array($code, [95, 96, 99], true) => 'Badai petir',
            default => 'Kode cuaca ' . $code,
        };
    }
}
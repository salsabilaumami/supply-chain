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
                'last_update' => $data['lastUpdate'],
            ],
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

        /*
         |--------------------------------------------------------------------------
         | REAL-TIME OPEN-METEO
         |--------------------------------------------------------------------------
         | Setiap halaman /weather atau /api/weather dibuka,
         | sistem mencoba mengambil cuaca terbaru dari Open-Meteo API.
         | Jika API gagal, sistem tetap memakai data terakhir dari database.
         |--------------------------------------------------------------------------
         */
        $weatherData = $this->getRealtimeWeather(
            $selectedCountry,
            $weatherService
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
            'lastUpdate' => $lastUpdate,

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
            ],
        ];
    }

    private function getRealtimeWeather(
        Country $country,
        WeatherService $weatherService
    ): ?WeatherData {
        try {
            return $weatherService->getCurrentWeather(
                $country,
                true
            );
        } catch (Throwable $exception) {
            return WeatherData::query()
                ->where('country_id', $country->id)
                ->latest('recorded_at')
                ->first();
        }
    }

    private function formatWeather(?WeatherData $weatherData): ?array
    {
        if (!$weatherData) {
            return null;
        }

        return [
            'id' => $weatherData->id,
            'country_id' => $weatherData->country_id,
            'temperature' => (float) $weatherData->temperature,
            'precipitation' => (float) $weatherData->precipitation,
            'wind_speed' => (float) $weatherData->wind_speed,
            'weather_code' => (int) $weatherData->weather_code,
            'weather_description' => $this->describeWeatherCode((int) $weatherData->weather_code),
            'weather_risk' => (float) $weatherData->weather_risk,
            'risk_label' => $this->riskScoreLabel((float) $weatherData->weather_risk),
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
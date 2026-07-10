<?php

namespace App\Http\Controllers;

use App\Models\Country;
use App\Models\GlobalPort;
use App\Services\OverpassPortService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\View\View;
use Throwable;

class PortController extends Controller
{
    public function index(
        Request $request,
        OverpassPortService $overpassPortService
    ): View {
        return view('ports.index', $this->buildPortData(
            $request,
            $overpassPortService
        ));
    }

    public function show(
        Request $request,
        OverpassPortService $overpassPortService
    ): JsonResponse {
        $data = $this->buildPortData(
            $request,
            $overpassPortService
        );

        return response()->json([
            'success' => true,
            'message' => 'Data pelabuhan global berhasil dimuat.',
            'source' => $data['source'],
            'api_available' => $data['apiAvailable'],
            'api_error' => $data['apiError'],
            'selected_country' => $data['selectedCountry']
                ? $this->formatCountry($data['selectedCountry'])
                : null,
            'selected_port' => $data['selectedPort'],
            'summary' => $data['summary'],
            'ports' => $data['ports']->values(),
            'chart_data' => $data['chartData'],
        ]);
    }

    private function buildPortData(
        Request $request,
        OverpassPortService $overpassPortService
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
            $selectedCountry = $countries->first();
        }

        $ports = collect();
        $apiAvailable = false;
        $apiError = null;
        $source = 'Database fallback';

        if ($selectedCountry) {
            try {
                $ports = $overpassPortService->getPortsByCountry($selectedCountry);

                if ($ports->isNotEmpty()) {
                    $apiAvailable = true;
                    $source = 'OpenStreetMap Overpass API';
                }
            } catch (Throwable $exception) {
                $apiError = $exception->getMessage();
            }

            if ($ports->isEmpty()) {
                $ports = $this->getFallbackPorts($selectedCountry);
                $source = 'Database fallback';
            }
        }

        $selectedPortCode = strtoupper(
            trim($request->string('port', '')->toString())
        );

        $selectedPort = $selectedPortCode !== ''
            ? $ports->first(function (array $port) use ($selectedPortCode) {
                return strtoupper((string) ($port['code'] ?? '')) === $selectedPortCode;
            })
            : $ports->first();

        if (!$selectedPort && $ports->isNotEmpty()) {
            $selectedPort = $ports->first();
        }

        $averageRisk = $ports->isNotEmpty()
            ? round((float) $ports->avg('risk_score'), 2)
            : 0.0;

        $highestRisk = $ports
            ->sortByDesc('risk_score')
            ->first();

        $chartLabels = $ports
            ->map(fn (array $port) => $port['code'] ?? $port['name'] ?? '-')
            ->values()
            ->all();

        $chartValues = $ports
            ->map(fn (array $port) => round((float) ($port['risk_score'] ?? 0), 2))
            ->values()
            ->all();

        if (empty($chartLabels)) {
            $chartLabels = ['Belum ada data'];
            $chartValues = [0];
        }

        $defaultLatitude = $selectedPort['latitude']
            ?? $selectedCountry?->latitude
            ?? -6.1045;

        $defaultLongitude = $selectedPort['longitude']
            ?? $selectedCountry?->longitude
            ?? 106.8866;

        return [
            'countries' => $countries,
            'selectedCountry' => $selectedCountry,
            'ports' => $ports,
            'selectedPort' => $selectedPort,
            'defaultLatitude' => $defaultLatitude,
            'defaultLongitude' => $defaultLongitude,
            'source' => $source,
            'apiAvailable' => $apiAvailable,
            'apiError' => $apiError,
            'summary' => [
                'total_ports' => $ports->count(),
                'average_risk_score' => $averageRisk,
                'highest_risk_port' => $highestRisk['name'] ?? null,
                'highest_risk_score' => $highestRisk
                    ? round((float) ($highestRisk['risk_score'] ?? 0), 2)
                    : 0.0,
            ],
            'chartData' => [
                'risk' => [
                    'labels' => $chartLabels,
                    'values' => $chartValues,
                ],
            ],
        ];
    }

    private function getFallbackPorts(Country $country): Collection
    {
        return GlobalPort::query()
            ->with('country')
            ->where('country_id', $country->id)
            ->orderBy('name')
            ->get()
            ->map(function (GlobalPort $port) {
                return [
                    'id' => 'db-' . $port->id,
                    'source' => 'Database fallback',
                    'source_type' => 'database',
                    'osm_id' => null,

                    'country' => $port->country
                        ? $this->formatCountry($port->country)
                        : null,

                    'name' => $port->name,
                    'code' => $port->code,
                    'city' => $port->city,
                    'type' => $port->type,
                    'latitude' => $port->latitude,
                    'longitude' => $port->longitude,
                    'capacity_score' => $port->capacity_score,
                    'congestion_score' => $port->congestion_score,
                    'weather_exposure_score' => $port->weather_exposure_score,
                    'risk_score' => $port->risk_score,
                    'risk_level' => $port->risk_level,
                    'importance_score' => $port->capacity_score,
                    'description' => $port->description,
                ];
            })
            ->values();
    }

    private function formatCountry(Country $country): array
    {
        return [
            'id' => $country->id,
            'name' => $country->name,
            'official_name' => $country->official_name,
            'iso2_code' => $country->iso2_code,
            'iso3_code' => $country->iso3_code,
            'region' => $country->region,
            'subregion' => $country->subregion,
            'flag_url' => $country->flag_url,
        ];
    }
}
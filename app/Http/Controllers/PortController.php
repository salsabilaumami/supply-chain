<?php

namespace App\Http\Controllers;

use App\Models\Country;
use App\Models\GlobalPort;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\View\View;

class PortController extends Controller
{
    public function index(Request $request): View
    {
        return view('ports.index', $this->buildPortData($request));
    }

    public function show(Request $request): JsonResponse
    {
        $data = $this->buildPortData($request);

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

    private function buildPortData(Request $request): array
    {
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

        $ports = $selectedCountry
            ? $this->getDatabasePorts($selectedCountry)
            : collect();

        $source = $ports->isNotEmpty()
            ? 'World Port Index Dataset'
            : 'Belum ada data pelabuhan';

        $selectedPortKeyword = strtoupper(
            trim($request->string('port', '')->toString())
        );

        $selectedPort = $this->resolveSelectedPort(
            $ports,
            $selectedPortKeyword
        );

        $averageRisk = $ports->isNotEmpty()
            ? round((float) $ports->avg('risk_score'), 2)
            : 0.0;

        $highestRisk = $ports
            ->sortByDesc('risk_score')
            ->first();

        $chartPorts = $ports
            ->sortByDesc('risk_score')
            ->take(30)
            ->values();

        $chartLabels = $chartPorts
            ->map(fn (array $port) => $port['code'] ?? $port['name'] ?? '-')
            ->values()
            ->all();

        $chartValues = $chartPorts
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
            'defaultLatitude' => (float) $defaultLatitude,
            'defaultLongitude' => (float) $defaultLongitude,
            'source' => $source,
            'apiAvailable' => $ports->isNotEmpty(),
            'apiError' => null,
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

    private function getDatabasePorts(Country $country): Collection
    {
        return GlobalPort::query()
            ->with('country')
            ->where('country_id', $country->id)
            ->orderByDesc('risk_score')
            ->orderBy('name')
            ->get()
            ->map(function (GlobalPort $port) {
                return [
                    'id' => 'db-' . $port->id,
                    'source' => 'World Port Index Dataset',
                    'source_type' => 'database',
                    'osm_id' => null,

                    'country' => $port->country
                        ? $this->formatCountry($port->country)
                        : null,

                    'name' => $port->name,
                    'code' => $port->code ?: 'WPI-' . $port->id,
                    'city' => $port->city ?: '-',
                    'type' => $port->type ?: 'World Port Index Port',
                    'latitude' => (float) $port->latitude,
                    'longitude' => (float) $port->longitude,
                    'capacity_score' => (float) $port->capacity_score,
                    'congestion_score' => (float) $port->congestion_score,
                    'weather_exposure_score' => (float) $port->weather_exposure_score,
                    'risk_score' => (float) $port->risk_score,
                    'risk_level' => $port->risk_level ?: 'moderate',
                    'importance_score' => (float) $port->capacity_score,
                    'description' => $port->description
                        ?: 'Data pelabuhan dari World Port Index Dataset.',
                ];
            })
            ->values();
    }

    private function resolveSelectedPort(
        Collection $ports,
        string $selectedPortKeyword
    ): ?array {
        if ($ports->isEmpty()) {
            return null;
        }

        if ($selectedPortKeyword === '') {
            return $ports->first();
        }

        $selectedPort = $ports->first(function (array $port) use ($selectedPortKeyword) {
            $code = strtoupper((string) ($port['code'] ?? ''));
            $name = strtoupper((string) ($port['name'] ?? ''));

            return $code === $selectedPortKeyword
                || $name === $selectedPortKeyword;
        });

        if ($selectedPort) {
            return $selectedPort;
        }

        $selectedPort = $ports->first(function (array $port) use ($selectedPortKeyword) {
            $name = strtoupper((string) ($port['name'] ?? ''));

            return str_contains($name, $selectedPortKeyword);
        });

        return $selectedPort ?: $ports->first();
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
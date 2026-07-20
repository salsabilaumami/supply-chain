<?php

namespace App\Http\Controllers;

use App\Models\Country;
use App\Models\GlobalPort;
use App\Models\RiskScore;
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
            'route_estimator' => $data['routeEstimator'],
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

        $routePortOptions = $this->getRoutePortOptions();

        $routeEstimator = $this->buildRouteEstimator(
            $request,
            $routePortOptions,
            $selectedPort
        );

        $averageRisk = $ports->isNotEmpty()
            ? round((float) $ports->avg('risk_score'), 2)
            : 0.0;

        $highestRisk = $ports
            ->sortByDesc('risk_score')
            ->first();

        $chartPorts = $ports
            ->sortByDesc('risk_score')
            ->take(12)
            ->values();

        $chartLabels = $chartPorts
            ->map(function (array $port) {
                return $port['code'] ?: ($port['name'] ?? '-');
            })
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

        $defaultLatitude = $routeEstimator['map_center']['latitude']
            ?? $selectedPort['latitude']
            ?? $selectedCountry?->latitude
            ?? -6.1045;

        $defaultLongitude = $routeEstimator['map_center']['longitude']
            ?? $selectedPort['longitude']
            ?? $selectedCountry?->longitude
            ?? 106.8866;

        return [
            'countries' => $countries,
            'selectedCountry' => $selectedCountry,
            'ports' => $ports,
            'selectedPort' => $selectedPort,
            'selectedPortKeyword' => $selectedPortKeyword,
            'routePortOptions' => $routePortOptions,
            'routeEstimator' => $routeEstimator,
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
                'selected_port_name' => $selectedPort['name'] ?? null,
                'selected_port_score' => $selectedPort
                    ? round((float) ($selectedPort['risk_score'] ?? 0), 2)
                    : 0.0,
                'route_distance_km' => $routeEstimator['distance']['sea_km'] ?? 0,
                'route_duration_days' => $routeEstimator['duration']['days'] ?? 0,
                'route_risk_score' => $routeEstimator['risk']['score'] ?? 0,
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
                return $this->formatPort($port);
            })
            ->values();
    }

    private function getRoutePortOptions(): Collection
    {
        return GlobalPort::query()
            ->with('country')
            ->whereNotNull('latitude')
            ->whereNotNull('longitude')
            ->orderBy('name')
            ->get()
            ->map(function (GlobalPort $port) {
                return $this->formatPort($port);
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
            $id = strtoupper((string) ($port['id'] ?? ''));
            $code = strtoupper((string) ($port['code'] ?? ''));
            $name = strtoupper((string) ($port['name'] ?? ''));

            return $id === $selectedPortKeyword
                || $code === $selectedPortKeyword
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

    private function buildRouteEstimator(
        Request $request,
        Collection $routePortOptions,
        ?array $selectedPort
    ): array {
        $originKeyword = trim($request->string('origin_port', '')->toString());
        $destinationKeyword = trim($request->string('destination_port', '')->toString());

        $originPort = $originKeyword !== ''
            ? $this->findRoutePort($routePortOptions, $originKeyword)
            : $selectedPort;

        if (!$originPort) {
            $originPort = $routePortOptions->first();
        }

        $destinationPort = $destinationKeyword !== ''
            ? $this->findRoutePort($routePortOptions, $destinationKeyword)
            : null;

        if (!$destinationPort && $originPort) {
            $destinationPort = $routePortOptions->first(function (array $port) use ($originPort) {
                return ($port['id'] ?? null) !== ($originPort['id'] ?? null)
                    && ($port['country']['id'] ?? null) !== ($originPort['country']['id'] ?? null);
            });
        }

        if (!$destinationPort && $originPort) {
            $destinationPort = $routePortOptions->first(function (array $port) use ($originPort) {
                return ($port['id'] ?? null) !== ($originPort['id'] ?? null);
            });
        }

        if (
            !$originPort
            || !$destinationPort
            || ($originPort['id'] ?? null) === ($destinationPort['id'] ?? null)
        ) {
            return [
                'available' => false,
                'message' => 'Pilih dua pelabuhan berbeda untuk menghitung estimasi rute.',
                'origin_port' => $originPort,
                'destination_port' => $destinationPort,
                'distance' => [
                    'straight_km' => 0,
                    'sea_km' => 0,
                    'nautical_miles' => 0,
                ],
                'duration' => [
                    'speed_knots' => 18,
                    'hours' => 0,
                    'days' => 0,
                    'display' => 'Belum tersedia',
                ],
                'risk' => [
                    'score' => 0,
                    'level' => 'low',
                    'label' => 'Belum dihitung',
                    'recommendation' => 'Pilih port asal dan port tujuan terlebih dahulu.',
                ],
                'route_line' => [],
                'map_center' => null,
            ];
        }

        $straightDistanceKm = $this->calculateHaversineDistance(
            (float) $originPort['latitude'],
            (float) $originPort['longitude'],
            (float) $destinationPort['latitude'],
            (float) $destinationPort['longitude']
        );

        $seaDistanceKm = $straightDistanceKm * 1.18;
        $nauticalMiles = $seaDistanceKm / 1.852;

        $speedKnots = 18;
        $durationHours = $nauticalMiles / $speedKnots;
        $durationDays = $durationHours / 24;

        $distanceRiskScore = $this->calculateDistanceRiskScore($seaDistanceKm);
        $originRiskScore = (float) ($originPort['risk_score'] ?? 0);
        $destinationRiskScore = (float) ($destinationPort['risk_score'] ?? 0);

        $routeRiskScore = ($originRiskScore * 0.35)
            + ($destinationRiskScore * 0.35)
            + ($distanceRiskScore * 0.30);

        $routeRiskScore = round($routeRiskScore, 2);
        $routeRiskLevel = $this->determineRouteRiskLevel($routeRiskScore);

        $originCountryRisk = $this->getLatestCountryRisk(
            (int) ($originPort['country']['id'] ?? 0)
        );

        $destinationCountryRisk = $this->getLatestCountryRisk(
            (int) ($destinationPort['country']['id'] ?? 0)
        );

        return [
            'available' => true,
            'message' => 'Estimasi rute berhasil dihitung.',
            'origin_port' => array_merge($originPort, [
                'country_risk' => $originCountryRisk,
            ]),
            'destination_port' => array_merge($destinationPort, [
                'country_risk' => $destinationCountryRisk,
            ]),
            'distance' => [
                'straight_km' => round($straightDistanceKm, 2),
                'sea_km' => round($seaDistanceKm, 2),
                'nautical_miles' => round($nauticalMiles, 2),
            ],
            'duration' => [
                'speed_knots' => $speedKnots,
                'hours' => round($durationHours, 2),
                'days' => round($durationDays, 2),
                'display' => $this->formatDuration($durationHours),
            ],
            'risk' => [
                'origin_port_score' => round($originRiskScore, 2),
                'destination_port_score' => round($destinationRiskScore, 2),
                'distance_score' => round($distanceRiskScore, 2),
                'score' => $routeRiskScore,
                'level' => $routeRiskLevel,
                'label' => $this->routeRiskLabel($routeRiskLevel),
                'recommendation' => $this->routeRecommendation($routeRiskLevel),
            ],
            'route_line' => [
                [
                    'latitude' => (float) $originPort['latitude'],
                    'longitude' => (float) $originPort['longitude'],
                ],
                [
                    'latitude' => (float) $destinationPort['latitude'],
                    'longitude' => (float) $destinationPort['longitude'],
                ],
            ],
            'map_center' => [
                'latitude' => round(
                    ((float) $originPort['latitude'] + (float) $destinationPort['latitude']) / 2,
                    6
                ),
                'longitude' => round(
                    ((float) $originPort['longitude'] + (float) $destinationPort['longitude']) / 2,
                    6
                ),
            ],
        ];
    }

    private function findRoutePort(Collection $ports, string $keyword): ?array
    {
        $keyword = strtoupper(trim($keyword));

        if ($keyword === '') {
            return null;
        }

        $port = $ports->first(function (array $port) use ($keyword) {
            $id = strtoupper((string) ($port['id'] ?? ''));
            $databaseId = strtoupper((string) ($port['database_id'] ?? ''));
            $code = strtoupper((string) ($port['code'] ?? ''));
            $name = strtoupper((string) ($port['name'] ?? ''));

            return $id === $keyword
                || $databaseId === $keyword
                || $code === $keyword
                || $name === $keyword;
        });

        if ($port) {
            return $port;
        }

        return $ports->first(function (array $port) use ($keyword) {
            $name = strtoupper((string) ($port['name'] ?? ''));
            $code = strtoupper((string) ($port['code'] ?? ''));
            $countryName = strtoupper((string) ($port['country']['name'] ?? ''));

            return str_contains($name, $keyword)
                || str_contains($code, $keyword)
                || str_contains($countryName, $keyword);
        });
    }

    private function calculateHaversineDistance(
        float $originLatitude,
        float $originLongitude,
        float $destinationLatitude,
        float $destinationLongitude
    ): float {
        $earthRadiusKm = 6371;

        $latitudeDifference = deg2rad($destinationLatitude - $originLatitude);
        $longitudeDifference = deg2rad($destinationLongitude - $originLongitude);

        $originLatitude = deg2rad($originLatitude);
        $destinationLatitude = deg2rad($destinationLatitude);

        $a = sin($latitudeDifference / 2) ** 2
            + cos($originLatitude)
            * cos($destinationLatitude)
            * sin($longitudeDifference / 2) ** 2;

        $c = 2 * asin(min(1, sqrt($a)));

        return $earthRadiusKm * $c;
    }

    private function calculateDistanceRiskScore(float $seaDistanceKm): float
    {
        return match (true) {
            $seaDistanceKm <= 1000 => 15.0,
            $seaDistanceKm <= 3000 => 30.0,
            $seaDistanceKm <= 6000 => 45.0,
            $seaDistanceKm <= 10000 => 60.0,
            default => 75.0,
        };
    }

    private function determineRouteRiskLevel(float $score): string
    {
        return match (true) {
            $score >= 75 => 'critical',
            $score >= 50 => 'high',
            $score >= 25 => 'moderate',
            default => 'low',
        };
    }

    private function routeRiskLabel(string $level): string
    {
        return match ($level) {
            'critical' => 'Risiko Tinggi',
            'high' => 'Perlu Mitigasi',
            'moderate' => 'Perlu Monitoring',
            default => 'Aman Digunakan',
        };
    }

    private function routeRecommendation(string $level): string
    {
        return match ($level) {
            'critical' => 'Rute memiliki risiko tinggi. Evaluasi ulang penggunaan pelabuhan dan siapkan alternatif rute logistik.',
            'high' => 'Rute memerlukan mitigasi sebelum digunakan. Periksa cuaca, risiko pelabuhan, dan kondisi negara tujuan.',
            'moderate' => 'Rute dapat digunakan dengan pemantauan berkala terhadap cuaca, kurs, dan berita logistik.',
            default => 'Rute relatif aman digunakan untuk aktivitas rantai pasok dengan pemantauan standar.',
        };
    }

    private function formatDuration(float $hours): string
    {
        if ($hours <= 0) {
            return 'Belum tersedia';
        }

        $days = floor($hours / 24);
        $remainingHours = round($hours - ($days * 24));

        if ($days <= 0) {
            return $remainingHours . ' jam';
        }

        if ($remainingHours <= 0) {
            return $days . ' hari';
        }

        return $days . ' hari ' . $remainingHours . ' jam';
    }

    private function getLatestCountryRisk(int $countryId): ?array
    {
        if ($countryId <= 0) {
            return null;
        }

        $riskScore = RiskScore::query()
            ->where('country_id', $countryId)
            ->latest('calculated_at')
            ->first();

        if (!$riskScore) {
            return null;
        }

        return [
            'score' => round((float) $riskScore->total_score, 2),
            'level' => $riskScore->risk_level,
            'calculated_at' => $riskScore->calculated_at?->format('d M Y H:i'),
        ];
    }

    private function formatPort(GlobalPort $port): array
    {
        return [
            'id' => 'db-' . $port->id,
            'database_id' => $port->id,
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
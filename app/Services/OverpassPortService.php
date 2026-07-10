<?php

namespace App\Services;

use App\Models\Country;
use App\Models\GlobalPort;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use RuntimeException;
use Throwable;

class OverpassPortService
{
    private array $endpoints = [
        'https://overpass.kumi.systems/api/interpreter',
        'https://overpass-api.de/api/interpreter',
        'https://overpass.openstreetmap.fr/api/interpreter',
    ];

    public function getPortsByCountry(Country $country): Collection
    {
        $iso2Code = strtoupper((string) $country->iso2_code);

        if ($iso2Code === '') {
            return collect();
        }

        $query = $this->buildQuery($iso2Code);
        $lastError = null;

        foreach ($this->endpoints as $endpoint) {
            try {
                $response = Http::withOptions([
                        'connect_timeout' => 20,
                        'timeout' => 90,
                    ])
                    ->asForm()
                    ->post($endpoint, [
                        'data' => $query,
                    ]);

                if (!$response->successful()) {
                    $lastError = 'HTTP ' . $response->status() . ' dari ' . $endpoint;
                    continue;
                }

                $payload = $response->json();

                $ports = $this->transformElements(
                    collect($payload['elements'] ?? []),
                    $country
                );

                if ($ports->isNotEmpty()) {
                    $this->savePortsToDatabase($ports, $country);

                    return $ports;
                }

                $lastError = 'Overpass berhasil merespons, tetapi tidak menemukan data pelabuhan.';
            } catch (Throwable $exception) {
                $lastError = $exception->getMessage();
            }
        }

        throw new RuntimeException(
            'Gagal mengambil data pelabuhan dari semua server Overpass API. Detail terakhir: ' . $lastError
        );
    }

    private function buildQuery(string $iso2Code): string
    {
        return <<<OVERPASS
[out:json][timeout:80];
area["ISO3166-1"="{$iso2Code}"][admin_level=2]->.searchArea;
(
  nwr["industrial"="port"](area.searchArea);
  nwr["landuse"="harbour"](area.searchArea);
  nwr["harbour"](area.searchArea);
  nwr["seamark:type"="harbour"](area.searchArea);
  nwr["port"](area.searchArea);
);
out center tags 300;
OVERPASS;
    }

    private function transformElements(
        Collection $elements,
        Country $country
    ): Collection {
        return $elements
            ->map(function (array $element) use ($country) {
                return $this->formatElement($element, $country);
            })
            ->filter()
            ->unique(function (array $port) {
                return Str::lower($port['name']) . '|' .
                    round((float) $port['latitude'], 3) . '|' .
                    round((float) $port['longitude'], 3);
            })
            ->sortByDesc('importance_score')
            ->values()
            ->take(150);
    }

    private function formatElement(
        array $element,
        Country $country
    ): ?array {
        $tags = $element['tags'] ?? [];

        $name = $tags['name:en']
            ?? $tags['name']
            ?? $tags['official_name']
            ?? null;

        if (!$name) {
            return null;
        }

        $latitude = $element['lat']
            ?? $element['center']['lat']
            ?? null;

        $longitude = $element['lon']
            ?? $element['center']['lon']
            ?? null;

        if (!$latitude || !$longitude) {
            return null;
        }

        if ($this->isNotMainPort($tags, $name)) {
            return null;
        }

        $code = $tags['locode']
            ?? $tags['unlocode']
            ?? $tags['UN/LOCODE']
            ?? $tags['ref']
            ?? 'OSM-' . ($element['id'] ?? uniqid());

        $city = $tags['addr:city']
            ?? $tags['is_in:city']
            ?? $tags['is_in:town']
            ?? $tags['is_in']
            ?? $country->capital
            ?? '-';

        $type = $this->resolvePortType($tags);

        $importanceScore = $this->calculateImportanceScore($tags, $name);

        $congestionScore = $this->estimateCongestionScore($importanceScore);
        $weatherExposureScore = $this->estimateWeatherExposureScore($tags);

        $riskScore = $this->calculateRiskScore(
            $importanceScore,
            $congestionScore,
            $weatherExposureScore
        );

        return [
            'id' => 'osm-' . ($element['type'] ?? 'item') . '-' . ($element['id'] ?? uniqid()),
            'source' => 'OpenStreetMap Overpass API',
            'source_type' => $element['type'] ?? 'osm',
            'osm_id' => $element['id'] ?? null,

            'country' => [
                'id' => $country->id,
                'name' => $country->name,
                'official_name' => $country->official_name,
                'iso2_code' => $country->iso2_code,
                'iso3_code' => $country->iso3_code,
                'region' => $country->region,
                'subregion' => $country->subregion,
                'flag_url' => $country->flag_url,
            ],

            'name' => $name,
            'code' => strtoupper((string) $code),
            'city' => $city,
            'type' => $type,
            'latitude' => (float) $latitude,
            'longitude' => (float) $longitude,

            'capacity_score' => $importanceScore,
            'congestion_score' => $congestionScore,
            'weather_exposure_score' => $weatherExposureScore,
            'risk_score' => $riskScore,
            'risk_level' => $this->riskLevel($riskScore),
            'importance_score' => $importanceScore,

            'description' => $name . ' merupakan data pelabuhan yang diambil dari OpenStreetMap Overpass API berdasarkan negara yang dipilih.',
        ];
    }

    private function savePortsToDatabase(
        Collection $ports,
        Country $country
    ): void {
        foreach ($ports as $port) {
            GlobalPort::updateOrCreate(
                [
                    'code' => $port['code'],
                    'country_id' => $country->id,
                ],
                [
                    'name' => $port['name'],
                    'city' => $port['city'],
                    'type' => $port['type'],
                    'latitude' => $port['latitude'],
                    'longitude' => $port['longitude'],
                    'capacity_score' => $port['capacity_score'],
                    'congestion_score' => $port['congestion_score'],
                    'weather_exposure_score' => $port['weather_exposure_score'],
                    'risk_score' => $port['risk_score'],
                    'risk_level' => $port['risk_level'],
                    'description' => $port['description'],
                ]
            );
        }
    }

    private function isNotMainPort(
        array $tags,
        string $name
    ): bool {
        $lowerName = Str::lower($name);

        if (($tags['leisure'] ?? null) === 'marina') {
            return true;
        }

        if (str_contains($lowerName, 'marina')) {
            return true;
        }

        if (str_contains($lowerName, 'yacht')) {
            return true;
        }

        if (str_contains($lowerName, 'boat club')) {
            return true;
        }

        if (str_contains($lowerName, 'ferry terminal')) {
            return true;
        }

        return false;
    }

    private function resolvePortType(array $tags): string
    {
        if (($tags['industrial'] ?? null) === 'port') {
            return 'Industrial Port';
        }

        if (($tags['landuse'] ?? null) === 'harbour') {
            return 'Harbour Area';
        }

        if (isset($tags['seamark:type'])) {
            return 'Seamark Harbour';
        }

        if (isset($tags['harbour'])) {
            return 'Harbour';
        }

        if (isset($tags['port'])) {
            return 'Port';
        }

        return 'Seaport';
    }

    private function calculateImportanceScore(
        array $tags,
        string $name
    ): float {
        $score = 55.0;
        $lowerName = Str::lower($name);

        if (isset($tags['locode']) || isset($tags['unlocode']) || isset($tags['UN/LOCODE'])) {
            $score += 18;
        }

        if (($tags['industrial'] ?? null) === 'port') {
            $score += 15;
        }

        if (($tags['landuse'] ?? null) === 'harbour') {
            $score += 10;
        }

        if (isset($tags['seamark:type'])) {
            $score += 8;
        }

        if (str_contains($lowerName, 'container')) {
            $score += 10;
        }

        if (str_contains($lowerName, 'terminal')) {
            $score += 8;
        }

        if (str_contains($lowerName, 'international')) {
            $score += 8;
        }

        if (str_contains($lowerName, 'port of')) {
            $score += 6;
        }

        if (str_contains($lowerName, 'harbour')) {
            $score += 4;
        }

        return round(min(100, max(0, $score)), 2);
    }

    private function estimateCongestionScore(float $importanceScore): float
    {
        return round(min(70, max(18, $importanceScore * 0.45)), 2);
    }

    private function estimateWeatherExposureScore(array $tags): float
    {
        $score = 25.0;

        if (isset($tags['seamark:type'])) {
            $score += 5;
        }

        if (($tags['natural'] ?? null) === 'coastline') {
            $score += 8;
        }

        return round(min(60, max(15, $score)), 2);
    }

    private function calculateRiskScore(
        float $importanceScore,
        float $congestionScore,
        float $weatherExposureScore
    ): float {
        $capacityRisk = max(0, 100 - $importanceScore);

        return round(
            ($congestionScore * 0.45)
            + ($weatherExposureScore * 0.35)
            + ($capacityRisk * 0.20),
            2
        );
    }

    private function riskLevel(float $score): string
    {
        return match (true) {
            $score >= 75 => 'critical',
            $score >= 50 => 'high',
            $score >= 25 => 'moderate',
            default => 'low',
        };
    }
}
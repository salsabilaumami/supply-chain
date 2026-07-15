<?php

namespace App\Services;

use App\Models\Country;
use App\Models\GlobalPort;
use RuntimeException;

class WorldPortIndexImportService
{
    public function import(?string $filePath = null): array
    {
        $filePath = $this->resolveFilePath($filePath);

        if (!file_exists($filePath)) {
            throw new RuntimeException('File World Port Index tidak ditemukan: ' . $filePath);
        }

        $handle = fopen($filePath, 'r');

        if (!$handle) {
            throw new RuntimeException('File World Port Index gagal dibuka.');
        }

        $header = fgetcsv($handle);

        if (!$header) {
            fclose($handle);
            throw new RuntimeException('Header CSV World Port Index tidak terbaca.');
        }

        $header = $this->cleanHeader($header);

        $imported = 0;
        $updated = 0;
        $skipped = 0;
        $skippedRows = [];

        while (($row = fgetcsv($handle)) !== false) {
            $data = $this->combineRow($header, $row);

            $portName = $this->value($data, 'Main Port Name');
            $countryCode = strtoupper($this->value($data, 'Country Code'));
            $latitude = $this->toFloat($this->value($data, 'Latitude'));
            $longitude = $this->toFloat($this->value($data, 'Longitude'));

            if (!$portName || $latitude === null || $longitude === null) {
                $skipped++;

                $skippedRows[] = [
                    'port' => $portName ?: '-',
                    'country_code' => $countryCode ?: '-',
                    'reason' => 'Nama pelabuhan, latitude, atau longitude kosong.',
                ];

                continue;
            }

            $country = $this->findCountry($countryCode, $data);

            if (!$country) {
                $skipped++;

                $skippedRows[] = [
                    'port' => $portName,
                    'country_code' => $countryCode ?: '-',
                    'reason' => 'Negara tidak ditemukan di tabel countries.',
                ];

                continue;
            }

            $code = $this->resolveCode($data);
            $type = $this->resolveType($data);
            $city = $this->resolveCity($data, $country);

            $capacityScore = $this->calculateCapacityScore($data);
            $congestionScore = $this->calculateCongestionScore($data, $capacityScore);
            $weatherExposureScore = $this->calculateWeatherExposureScore($data);

            $riskScore = $this->calculateRiskScore(
                $capacityScore,
                $congestionScore,
                $weatherExposureScore
            );

            $existing = GlobalPort::query()
                ->where('code', $code)
                ->where('country_id', $country->id)
                ->first();

            GlobalPort::updateOrCreate(
                [
                    'code' => $code,
                    'country_id' => $country->id,
                ],
                [
                    'name' => $portName,
                    'city' => $city,
                    'type' => $type,
                    'latitude' => $latitude,
                    'longitude' => $longitude,
                    'capacity_score' => $capacityScore,
                    'congestion_score' => $congestionScore,
                    'weather_exposure_score' => $weatherExposureScore,
                    'risk_score' => $riskScore,
                    'risk_level' => $this->riskLevel($riskScore),
                    'description' => $this->buildDescription($data, $portName),
                ]
            );

            if ($existing) {
                $updated++;
            } else {
                $imported++;
            }
        }

        fclose($handle);

        return [
            'file' => $filePath,
            'imported' => $imported,
            'updated' => $updated,
            'skipped' => $skipped,
            'skipped_rows' => array_slice($skippedRows, 0, 20),
            'total_saved' => $imported + $updated,
        ];
    }

    private function resolveFilePath(?string $filePath): string
    {
        $filePath = $filePath ?: config('services.world_port_index.dataset_path');

        if (!$filePath) {
            $filePath = 'storage/app/datasets/world_port_index.csv';
        }

        if (str_starts_with($filePath, DIRECTORY_SEPARATOR)) {
            return $filePath;
        }

        if (preg_match('/^[A-Za-z]:\\\\/', $filePath)) {
            return $filePath;
        }

        return base_path($filePath);
    }

    private function cleanHeader(array $header): array
    {
        return array_map(function ($item) {
            $item = preg_replace('/^\xEF\xBB\xBF/', '', (string) $item);

            return trim($item);
        }, $header);
    }

    private function combineRow(array $header, array $row): array
    {
        $row = array_pad($row, count($header), null);

        return array_combine($header, array_slice($row, 0, count($header))) ?: [];
    }

    private function value(array $data, string $key): string
    {
        return trim((string) ($data[$key] ?? ''));
    }

    private function toFloat(?string $value): ?float
    {
        $value = trim((string) $value);

        if ($value === '') {
            return null;
        }

        $value = str_replace(',', '.', $value);

        if (!is_numeric($value)) {
            return null;
        }

        return (float) $value;
    }

    private function findCountry(string $countryCode, array $data): ?Country
    {
        $countryCode = strtoupper(trim($countryCode));
        $unlocode = strtoupper($this->value($data, 'UN/LOCODE'));
        $portName = strtoupper($this->value($data, 'Main Port Name'));

        $country = $this->findCountryByNames([
            $countryCode,
        ]);

        if ($country) {
            return $country;
        }

        if (strlen($unlocode) >= 2) {
            $iso2FromLocode = substr($unlocode, 0, 2);

            $country = Country::query()
                ->where('iso2_code', $iso2FromLocode)
                ->first();

            if ($country) {
                return $country;
            }
        }

        $aliases = [
            'BURMA' => [
                'MYANMAR',
            ],
            'MYANMAR (BURMA)' => [
                'MYANMAR',
            ],
            'CONGO (BRAZZAVILLE)' => [
                'REPUBLIC OF THE CONGO',
                'CONGO',
            ],
            'CONGO BRAZZAVILLE' => [
                'REPUBLIC OF THE CONGO',
                'CONGO',
            ],
            'CONGO (KINSHASA)' => [
                'DEMOCRATIC REPUBLIC OF THE CONGO',
                'CONGO, THE DEMOCRATIC REPUBLIC OF THE',
            ],
            'SAINT HELENA, ASCENSION, AND TRISTAN DA CUNHA' => [
                'SAINT HELENA, ASCENSION AND TRISTAN DA CUNHA',
                'SAINT HELENA',
                'UNITED KINGDOM',
            ],
            'SOUTH GEORGIA AND SOUTH SANDWICH ISLANDS' => [
                'SOUTH GEORGIA AND THE SOUTH SANDWICH ISLANDS',
                'SOUTH GEORGIA',
                'UNITED KINGDOM',
            ],
            'WAKE ISLAND' => [
                'UNITED STATES',
                'UNITED STATES OF AMERICA',
            ],
            'JOHNSON ATOLL' => [
                'UNITED STATES',
                'UNITED STATES OF AMERICA',
            ],
            'JOHNSTON ATOLL' => [
                'UNITED STATES',
                'UNITED STATES OF AMERICA',
            ],
            'MIDWAY ISLANDS' => [
                'UNITED STATES',
                'UNITED STATES OF AMERICA',
            ],
            'UNITED STATES' => [
                'UNITED STATES',
                'UNITED STATES OF AMERICA',
                'USA',
                'US',
            ],
            'UNITED KINGDOM' => [
                'UNITED KINGDOM',
                'GREAT BRITAIN',
                'BRITAIN',
                'UK',
            ],
            'TURKEY' => [
                'TURKEY',
                'TÜRKIYE',
                'TURKIYE',
            ],
            'TANZANIA' => [
                'TANZANIA',
                'UNITED REPUBLIC OF TANZANIA',
            ],
            'PAPUA NEW GUINEA' => [
                'PAPUA NEW GUINEA',
            ],
            'FEDERATED STATES OF MICRONESIA' => [
                'MICRONESIA',
                'FEDERATED STATES OF MICRONESIA',
            ],
            'MICRONESIA' => [
                'MICRONESIA',
                'FEDERATED STATES OF MICRONESIA',
            ],
            'CAPE VERDE' => [
                'CAPE VERDE',
                'CABO VERDE',
            ],
            'BRUNEI' => [
                'BRUNEI',
                'BRUNEI DARUSSALAM',
            ],
            'VIETNAM' => [
                'VIETNAM',
                'VIET NAM',
            ],
            'RUSSIA' => [
                'RUSSIA',
                'RUSSIAN FEDERATION',
            ],
            'SOUTH KOREA' => [
                'SOUTH KOREA',
                'KOREA, REPUBLIC OF',
                'REPUBLIC OF KOREA',
            ],
            'NORTH KOREA' => [
                'NORTH KOREA',
                'KOREA, DEMOCRATIC PEOPLE\'S REPUBLIC OF',
            ],
        ];

        if (array_key_exists($countryCode, $aliases)) {
            $country = $this->findCountryByNames($aliases[$countryCode]);

            if ($country) {
                return $country;
            }
        }

        $portCountryFallbacks = [
            'MEHAMNFJORDEN' => 'NORWAY',
            'FIBORGTANGEN' => 'NORWAY',
            'JOSSINGFJORD' => 'NORWAY',
            'NESNA' => 'NORWAY',
        ];

        if (array_key_exists($portName, $portCountryFallbacks)) {
            return $this->findCountryByNames([
                $portCountryFallbacks[$portName],
            ]);
        }

        return null;
    }

    private function findCountryByNames(array $names): ?Country
    {
        foreach ($names as $name) {
            $name = strtoupper(trim((string) $name));

            if ($name === '' || $name === '-') {
                continue;
            }

            $country = Country::query()
                ->where('iso2_code', $name)
                ->orWhere('iso3_code', $name)
                ->orWhereRaw('UPPER(name) = ?', [$name])
                ->orWhereRaw('UPPER(official_name) = ?', [$name])
                ->first();

            if ($country) {
                return $country;
            }
        }

        foreach ($names as $name) {
            $name = strtoupper(trim((string) $name));

            if ($name === '' || $name === '-') {
                continue;
            }

            $country = Country::query()
                ->whereRaw('UPPER(name) LIKE ?', ['%' . $name . '%'])
                ->orWhereRaw('UPPER(official_name) LIKE ?', ['%' . $name . '%'])
                ->first();

            if ($country) {
                return $country;
            }
        }

        return null;
    }

    private function resolveCode(array $data): string
    {
        $unlocode = strtoupper($this->value($data, 'UN/LOCODE'));

        if ($unlocode !== '') {
            return $unlocode;
        }

        $wpiNumber = $this->value($data, 'World Port Index Number');

        if ($wpiNumber !== '') {
            return 'WPI-' . $wpiNumber;
        }

        return 'WPI-' . md5(
            $this->value($data, 'Main Port Name') .
            $this->value($data, 'Country Code') .
            $this->value($data, 'Latitude') .
            $this->value($data, 'Longitude')
        );
    }

    private function resolveCity(array $data, Country $country): string
    {
        $alternateName = $this->value($data, 'Alternate Port Name');

        if ($alternateName !== '') {
            return $alternateName;
        }

        return $country->capital ?: '-';
    }

    private function resolveType(array $data): string
    {
        $harborType = $this->value($data, 'Harbor Type');
        $harborUse = $this->value($data, 'Harbor Use');
        $harborSize = $this->value($data, 'Harbor Size');

        $parts = array_filter([
            $harborSize ? $harborSize . ' Harbor' : null,
            $harborType,
            $harborUse,
        ]);

        return $parts ? implode(' - ', $parts) : 'World Port Index Port';
    }

    private function calculateCapacityScore(array $data): float
    {
        $score = 45.0;

        $harborSize = strtoupper($this->value($data, 'Harbor Size'));

        if (str_contains($harborSize, 'L')) {
            $score += 25;
        } elseif (str_contains($harborSize, 'M')) {
            $score += 15;
        } elseif (str_contains($harborSize, 'S')) {
            $score += 8;
        }

        $facilities = [
            'Facilities - Container',
            'Facilities - Wharves',
            'Facilities - Ro-Ro',
            'Facilities - Solid Bulk',
            'Facilities - Liquid Bulk',
            'Facilities - Oil Terminal',
            'Facilities - LNG Terminal',
        ];

        foreach ($facilities as $facilityColumn) {
            if ($this->isAvailable($this->value($data, $facilityColumn))) {
                $score += 5;
            }
        }

        $channelDepth = $this->toFloat($this->value($data, 'Channel Depth (m)'));
        $cargoPierDepth = $this->toFloat($this->value($data, 'Cargo Pier Depth (m)'));
        $maxLength = $this->toFloat($this->value($data, 'Maximum Vessel Length (m)'));

        if ($channelDepth !== null && $channelDepth >= 10) {
            $score += 8;
        }

        if ($cargoPierDepth !== null && $cargoPierDepth >= 10) {
            $score += 8;
        }

        if ($maxLength !== null && $maxLength >= 200) {
            $score += 8;
        }

        return round(min(100, max(0, $score)), 2);
    }

    private function calculateCongestionScore(array $data, float $capacityScore): float
    {
        $score = 25.0;

        $harborUse = strtoupper($this->value($data, 'Harbor Use'));

        if (str_contains($harborUse, 'COMMERCIAL')) {
            $score += 20;
        }

        if ($this->isAvailable($this->value($data, 'Facilities - Container'))) {
            $score += 15;
        }

        if ($this->isAvailable($this->value($data, 'Facilities - Oil Terminal'))) {
            $score += 10;
        }

        if ($capacityScore >= 80) {
            $score += 10;
        }

        return round(min(100, max(0, $score)), 2);
    }

    private function calculateWeatherExposureScore(array $data): float
    {
        $score = 25.0;

        $shelter = strtoupper($this->value($data, 'Shelter Afforded'));
        $tideRestriction = $this->value($data, 'Entrance Restriction - Tide');
        $swellRestriction = $this->value($data, 'Entrance Restriction - Heavy Swell');
        $iceRestriction = $this->value($data, 'Entrance Restriction - Ice');
        $tidalRange = $this->toFloat($this->value($data, 'Tidal Range (m)'));

        if (str_contains($shelter, 'NONE') || str_contains($shelter, 'POOR')) {
            $score += 25;
        } elseif (str_contains($shelter, 'FAIR')) {
            $score += 15;
        } elseif (str_contains($shelter, 'GOOD') || str_contains($shelter, 'EXCELLENT')) {
            $score -= 8;
        }

        foreach ([$tideRestriction, $swellRestriction, $iceRestriction] as $restriction) {
            if ($this->isAvailable($restriction)) {
                $score += 8;
            }
        }

        if ($tidalRange !== null && $tidalRange >= 3) {
            $score += 8;
        }

        return round(min(100, max(5, $score)), 2);
    }

    private function calculateRiskScore(
        float $capacityScore,
        float $congestionScore,
        float $weatherExposureScore
    ): float {
        $capacityRisk = max(0, 100 - $capacityScore);

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

    private function isAvailable(?string $value): bool
    {
        $value = strtoupper(trim((string) $value));

        return in_array($value, ['Y', 'YES', 'TRUE', '1', 'AVAILABLE'], true);
    }

    private function buildDescription(array $data, string $portName): string
    {
        $wpiNumber = $this->value($data, 'World Port Index Number');
        $countryCode = $this->value($data, 'Country Code');
        $waterBody = $this->value($data, 'World Water Body');
        $harborSize = $this->value($data, 'Harbor Size');
        $harborType = $this->value($data, 'Harbor Type');
        $harborUse = $this->value($data, 'Harbor Use');

        return trim(
            $portName .
            ' merupakan pelabuhan dari World Port Index Dataset. ' .
            'Nomor WPI: ' . ($wpiNumber ?: '-') . '. ' .
            'Kode negara: ' . ($countryCode ?: '-') . '. ' .
            'Perairan: ' . ($waterBody ?: '-') . '. ' .
            'Ukuran pelabuhan: ' . ($harborSize ?: '-') . '. ' .
            'Tipe pelabuhan: ' . ($harborType ?: '-') . '. ' .
            'Fungsi pelabuhan: ' . ($harborUse ?: '-') . '.'
        );
    }
}
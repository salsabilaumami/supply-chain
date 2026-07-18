<?php

namespace App\Services;

use App\Models\Country;
use App\Models\EconomicIndicator;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Http;
use RuntimeException;

class WorldBankService
{
    private const API_URL = 'https://api.worldbank.org/v2';

    private const START_YEAR = 2015;

    private const INDICATORS = [
        'gdp' => [
            'code' => 'NY.GDP.MKTP.CD',
            'name' => 'GDP (Current US$)',
        ],
        'inflation' => [
            'code' => 'FP.CPI.TOTL.ZG',
            'name' => 'Inflation, Consumer Prices (Annual %)',
        ],
        'population' => [
            'code' => 'SP.POP.TOTL',
            'name' => 'Population, Total',
        ],
        'exports' => [
            'code' => 'NE.EXP.GNFS.CD',
            'name' => 'Exports of Goods and Services (Current US$)',
        ],
        'imports' => [
            'code' => 'NE.IMP.GNFS.CD',
            'name' => 'Imports of Goods and Services (Current US$)',
        ],
    ];

    public function syncCountryIndicators(Country $country): array
    {
        if (empty($country->iso3_code)) {
            throw new RuntimeException(
                'Negara ' . $country->name . ' tidak memiliki kode ISO3.'
            );
        }

        $synced = 0;
        $skipped = 0;
        $skippedIndicators = [];

        foreach (self::INDICATORS as $key => $indicator) {
            $rows = $this->getIndicatorHistory(
                $country->iso3_code,
                $indicator['code']
            );

            if ($rows->isEmpty()) {
                $skipped++;

                $skippedIndicators[] = [
                    'indicator' => $indicator['name'],
                    'code' => $indicator['code'],
                    'reason' => 'Tidak ada data tersedia dari World Bank',
                ];

                continue;
            }

            foreach ($rows as $row) {
                EconomicIndicator::updateOrCreate(
                    [
                        'country_id' => $country->id,
                        'indicator_code' => $indicator['code'],
                        'year' => $row['year'],
                    ],
                    [
                        'indicator_name' => $indicator['name'],
                        'value' => $row['value'],
                        'source' => 'World Bank',
                        'fetched_at' => now(),
                    ]
                );

                $synced++;
            }
        }

        return [
            'country' => $country->name,
            'iso3_code' => $country->iso3_code,
            'synced' => $synced,
            'skipped' => $skipped,
            'skipped_indicators' => $skippedIndicators,
        ];
    }

    public function getCountryIndicators(string $iso3Code): array
    {
        $iso3Code = strtoupper(trim($iso3Code));

        if (strlen($iso3Code) !== 3) {
            throw new RuntimeException('Kode ISO3 negara tidak valid.');
        }

        $results = [];

        foreach (self::INDICATORS as $key => $indicator) {
            $results[$key] = $this->getLatestIndicator(
                $iso3Code,
                $indicator['code']
            );
        }

        return $results;
    }

    public function getCountryIndicatorHistory(string $iso3Code): array
    {
        $iso3Code = strtoupper(trim($iso3Code));

        if (strlen($iso3Code) !== 3) {
            throw new RuntimeException('Kode ISO3 negara tidak valid.');
        }

        $results = [];

        foreach (self::INDICATORS as $key => $indicator) {
            $results[$key] = $this->getIndicatorHistory(
                $iso3Code,
                $indicator['code']
            )->values()->all();
        }

        return $results;
    }

    private function getLatestIndicator(
        string $iso3Code,
        string $indicatorCode
    ): array {
        $history = $this->getIndicatorHistory(
            $iso3Code,
            $indicatorCode
        );

        $latestData = $history
            ->sortByDesc('year')
            ->first();

        if (!$latestData) {
            return [
                'indicator_code' => $indicatorCode,
                'value' => null,
                'year' => null,
            ];
        }

        return [
            'indicator_code' => $indicatorCode,
            'value' => (float) $latestData['value'],
            'year' => (int) $latestData['year'],
        ];
    }

    private function getIndicatorHistory(
        string $iso3Code,
        string $indicatorCode
    ): Collection {
        $response = Http::timeout(60)
            ->retry(3, 1000)
            ->acceptJson()
            ->get(
                self::API_URL
                . '/country/'
                . strtoupper($iso3Code)
                . '/indicator/'
                . $indicatorCode,
                [
                    'format' => 'json',
                    'per_page' => 100,
                    'date' => self::START_YEAR . ':' . now()->year,
                ]
            );

        if ($response->failed()) {
            throw new RuntimeException(
                'World Bank API gagal untuk indikator '
                . $indicatorCode
                . '. HTTP '
                . $response->status()
            );
        }

        $payload = $response->json();

        if (
            !is_array($payload) ||
            !isset($payload[1]) ||
            !is_array($payload[1])
        ) {
            return collect();
        }

        return collect($payload[1])
            ->filter(function ($item) {
                return isset($item['value'], $item['date'])
                    && $item['value'] !== null
                    && $item['date'] !== null;
            })
            ->map(function ($item) use ($indicatorCode) {
                return [
                    'indicator_code' => $indicatorCode,
                    'value' => (float) $item['value'],
                    'year' => (int) $item['date'],
                ];
            })
            ->sortBy('year')
            ->values();
    }
}
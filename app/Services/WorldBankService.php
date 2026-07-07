<?php

namespace App\Services;

use App\Models\Country;
use App\Models\EconomicIndicator;
use Illuminate\Support\Facades\Http;
use RuntimeException;

class WorldBankService
{
    private const API_URL = 'https://api.worldbank.org/v2';

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

    public function getCountryIndicators(string $iso3Code): array
    {
        $iso3Code = strtoupper(trim($iso3Code));

        if (strlen($iso3Code) !== 3) {
            throw new RuntimeException(
                'Kode ISO3 negara tidak valid.'
            );
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

    public function syncCountryIndicators(Country $country): array
    {
        if (empty($country->iso3_code)) {
            throw new RuntimeException(
                'Negara ' . $country->name . ' tidak memiliki kode ISO3.'
            );
        }

        $apiResults = $this->getCountryIndicators(
            $country->iso3_code
        );

        $synced = 0;
        $skipped = 0;
        $skippedIndicators = [];

        foreach (self::INDICATORS as $key => $indicator) {
            $data = $apiResults[$key] ?? null;

            if (
                !is_array($data) ||
                $data['value'] === null ||
                $data['year'] === null
            ) {
                $skipped++;

                $skippedIndicators[] = [
                    'indicator' => $indicator['name'],
                    'code' => $indicator['code'],
                    'reason' => 'Tidak ada data tersedia',
                ];

                continue;
            }

            EconomicIndicator::updateOrCreate(
                [
                    'country_id' => $country->id,
                    'indicator_code' => $indicator['code'],
                    'year' => $data['year'],
                ],
                [
                    'indicator_name' => $indicator['name'],
                    'value' => $data['value'],
                    'source' => 'World Bank',
                    'fetched_at' => now(),
                ]
            );

            $synced++;
        }

        return [
            'country' => $country->name,
            'iso3_code' => $country->iso3_code,
            'synced' => $synced,
            'skipped' => $skipped,
            'skipped_indicators' => $skippedIndicators,
        ];
    }

    private function getLatestIndicator(
        string $iso3Code,
        string $indicatorCode
    ): array {
        $response = Http::timeout(60)
            ->retry(3, 1000)
            ->acceptJson()
            ->get(
                self::API_URL
                . '/country/'
                . $iso3Code
                . '/indicator/'
                . $indicatorCode,
                [
                    'format' => 'json',
                    'per_page' => 20,
                    'date' => '2015:' . now()->year,
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
            return [
                'indicator_code' => $indicatorCode,
                'value' => null,
                'year' => null,
            ];
        }

        $latestData = collect($payload[1])
            ->first(function ($item) {
                return isset($item['value'])
                    && $item['value'] !== null;
            });

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
            'year' => (int) $latestData['date'],
        ];
    }
}
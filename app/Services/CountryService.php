<?php

namespace App\Services;

use App\Models\Country;
use Illuminate\Support\Facades\Http;
use RuntimeException;

class CountryService
{
    private const API_URL = 'https://api.restcountries.com/countries/v5';

    public function syncCountries(): array
    {
        $apiKey = config('services.rest_countries.key');

        if (empty($apiKey)) {
            throw new RuntimeException(
                'REST_COUNTRIES_API_KEY belum terbaca oleh Laravel.'
            );
        }

        $allCountries = [];
        $offset = 0;
        $limit = 100;

        do {
            $response = Http::timeout(60)
                ->retry(3, 1000)
                ->acceptJson()
                ->withToken($apiKey)
                ->get(self::API_URL, [
                    'limit' => $limit,
                    'offset' => $offset,
                    'response_fields' => implode(',', [
                        'names.common',
                        'names.official',
                        'codes.alpha_2',
                        'codes.alpha_3',
                        'capitals',
                        'region',
                        'subregion',
                        'coordinates',
                        'currencies',
                        'population',
                        'flag.url_svg',
                        'flag.url_png',
                    ]),
                ]);

            if ($response->failed()) {
                throw new RuntimeException(
                    'REST Countries API gagal. HTTP '
                    . $response->status()
                    . ' | Respons: '
                    . $response->body()
                );
            }

            $payload = $response->json();

            $objects = $payload['data']['objects'] ?? null;
            $meta = $payload['data']['meta'] ?? null;

            if (!is_array($objects) || !is_array($meta)) {
                throw new RuntimeException(
                    'Format respons REST Countries v5 tidak sesuai. Respons: '
                    . $response->body()
                );
            }

            $allCountries = array_merge(
                $allCountries,
                $objects
            );

            $hasMore = (bool) ($meta['more'] ?? false);
            $offset += $limit;
        } while ($hasMore);

        $synced = 0;
        $skipped = 0;
        $skippedCountries = [];

        foreach ($allCountries as $countryData) {
            $iso2 = $countryData['codes']['alpha_2'] ?? null;
            $iso3 = $countryData['codes']['alpha_3'] ?? null;
            $name = $countryData['names']['common'] ?? null;

            if (empty($iso2) || empty($iso3) || empty($name)) {
                $skipped++;

                $skippedCountries[] = [
                    'name' => $name ?? 'NULL',
                    'iso2' => $iso2 ?? 'NULL',
                    'iso3' => $iso3 ?? 'NULL',
                    'reason' => $this->determineSkipReason(
                        $name,
                        $iso2,
                        $iso3
                    ),
                ];

                continue;
            }

            $primaryCapital = collect(
                $countryData['capitals'] ?? []
            )->firstWhere('primary', true);

            $capital = $primaryCapital['name']
                ?? ($countryData['capitals'][0]['name'] ?? null);

            $currency = $countryData['currencies'][0] ?? [];

            Country::updateOrCreate(
                [
                    'iso3_code' => $iso3,
                ],
                [
                    'name' => $name,
                    'official_name' =>
                        $countryData['names']['official'] ?? null,

                    'iso2_code' => $iso2,

                    'capital' => $capital,

                    'region' =>
                        $countryData['region'] ?? null,

                    'subregion' =>
                        $countryData['subregion'] ?? null,

                    'latitude' =>
                        $countryData['coordinates']['lat'] ?? null,

                    'longitude' =>
                        $countryData['coordinates']['lng'] ?? null,

                    'currency_code' =>
                        $currency['code'] ?? null,

                    'currency_name' =>
                        $currency['name'] ?? null,

                    'currency_symbol' =>
                        $currency['symbol'] ?? null,

                    'population' =>
                        $countryData['population'] ?? 0,

                    'flag_url' =>
                        $countryData['flag']['url_svg']
                        ?? $countryData['flag']['url_png']
                        ?? null,
                ]
            );

            $synced++;
        }

        return [
            'synced' => $synced,
            'skipped' => $skipped,
            'skipped_countries' => $skippedCountries,
            'total_received' => count($allCountries),
        ];
    }

    private function determineSkipReason(
        ?string $name,
        ?string $iso2,
        ?string $iso3
    ): string {
        $missingFields = [];

        if (empty($name)) {
            $missingFields[] = 'name';
        }

        if (empty($iso2)) {
            $missingFields[] = 'iso2';
        }

        if (empty($iso3)) {
            $missingFields[] = 'iso3';
        }

        return 'Data kosong: ' . implode(', ', $missingFields);
    }
}
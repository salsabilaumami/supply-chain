<?php

namespace Database\Seeders;

use App\Models\Country;
use Illuminate\Database\Seeder;

class CountrySeeder extends Seeder
{
    public function run(): void
    {
        $countries = [
            [
                'name' => 'Indonesia',
                'official_name' => 'Republic of Indonesia',
                'iso2_code' => 'ID',
                'iso3_code' => 'IDN',
                'capital' => 'Jakarta',
                'region' => 'Asia',
                'subregion' => 'South-Eastern Asia',
                'latitude' => -6.2000000,
                'longitude' => 106.8166667,
                'currency_code' => 'IDR',
                'currency_name' => 'Indonesian Rupiah',
                'currency_symbol' => 'Rp',
                'population' => 277534122,
                'flag_url' => 'https://flagcdn.com/id.svg',
            ],
            [
                'name' => 'China',
                'official_name' => 'People\'s Republic of China',
                'iso2_code' => 'CN',
                'iso3_code' => 'CHN',
                'capital' => 'Beijing',
                'region' => 'Asia',
                'subregion' => 'Eastern Asia',
                'latitude' => 39.9042000,
                'longitude' => 116.4074000,
                'currency_code' => 'CNY',
                'currency_name' => 'Chinese Yuan',
                'currency_symbol' => '¥',
                'population' => 1411750000,
                'flag_url' => 'https://flagcdn.com/cn.svg',
            ],
            [
                'name' => 'Germany',
                'official_name' => 'Federal Republic of Germany',
                'iso2_code' => 'DE',
                'iso3_code' => 'DEU',
                'capital' => 'Berlin',
                'region' => 'Europe',
                'subregion' => 'Western Europe',
                'latitude' => 52.5200000,
                'longitude' => 13.4050000,
                'currency_code' => 'EUR',
                'currency_name' => 'Euro',
                'currency_symbol' => '€',
                'population' => 83280000,
                'flag_url' => 'https://flagcdn.com/de.svg',
            ],
            [
                'name' => 'Australia',
                'official_name' => 'Commonwealth of Australia',
                'iso2_code' => 'AU',
                'iso3_code' => 'AUS',
                'capital' => 'Canberra',
                'region' => 'Oceania',
                'subregion' => 'Australia and New Zealand',
                'latitude' => -35.2809000,
                'longitude' => 149.1300000,
                'currency_code' => 'AUD',
                'currency_name' => 'Australian Dollar',
                'currency_symbol' => '$',
                'population' => 26638800,
                'flag_url' => 'https://flagcdn.com/au.svg',
            ],
            [
                'name' => 'Singapore',
                'official_name' => 'Republic of Singapore',
                'iso2_code' => 'SG',
                'iso3_code' => 'SGP',
                'capital' => 'Singapore',
                'region' => 'Asia',
                'subregion' => 'South-Eastern Asia',
                'latitude' => 1.3521000,
                'longitude' => 103.8198000,
                'currency_code' => 'SGD',
                'currency_name' => 'Singapore Dollar',
                'currency_symbol' => '$',
                'population' => 5917600,
                'flag_url' => 'https://flagcdn.com/sg.svg',
            ],
        ];

        foreach ($countries as $country) {
            Country::updateOrCreate(
                ['iso3_code' => $country['iso3_code']],
                $country
            );
        }
    }
}
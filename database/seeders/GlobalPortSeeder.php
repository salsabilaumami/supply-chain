<?php

namespace Database\Seeders;

use App\Models\Country;
use App\Models\GlobalPort;
use Illuminate\Database\Seeder;

class GlobalPortSeeder extends Seeder
{
    public function run(): void
    {
        /*
         |--------------------------------------------------------------------------
         | Reset Data Pelabuhan
         |--------------------------------------------------------------------------
         | Supaya data lama seperti hanya Tanjung Priok tidak tertinggal,
         | seluruh data global_ports dihapus dulu lalu diisi ulang.
         |--------------------------------------------------------------------------
         */
        GlobalPort::query()->delete();

        $ports = [
            // Indonesia
            ['IDN', 'Port of Tanjung Priok', 'IDTPP', 'Jakarta', 'Seaport', -6.1045, 106.8866, 82, 45, 25],
            ['IDN', 'Port of Tanjung Perak', 'IDTPK', 'Surabaya', 'Seaport', -7.1966, 112.7325, 78, 40, 28],
            ['IDN', 'Port of Tanjung Emas', 'IDSRG', 'Semarang', 'Seaport', -6.9489, 110.4211, 70, 38, 30],
            ['IDN', 'Port of Belawan', 'IDBLW', 'Medan', 'Seaport', 3.7857, 98.6942, 68, 35, 30],
            ['IDN', 'Port of Makassar', 'IDMAK', 'Makassar', 'Seaport', -5.1333, 119.4167, 70, 32, 34],
            ['IDN', 'Port of Bitung', 'IDBIT', 'Bitung', 'Seaport', 1.4404, 125.1890, 66, 28, 35],
            ['IDN', 'Port of Dumai', 'IDDUM', 'Dumai', 'Seaport', 1.6829, 101.4507, 64, 30, 30],
            ['IDN', 'Port of Panjang', 'IDPJG', 'Bandar Lampung', 'Seaport', -5.4726, 105.3217, 65, 31, 27],
            ['IDN', 'Port of Teluk Bayur', 'IDTBR', 'Padang', 'Seaport', -0.9993, 100.3722, 63, 29, 32],
            ['IDN', 'Port of Batu Ampar', 'IDBTH', 'Batam', 'Seaport', 1.1494, 104.0107, 72, 34, 25],
            ['IDN', 'Port of Kijing', 'IDKJG', 'Mempawah', 'Seaport', 0.3418, 108.9544, 67, 27, 28],
            ['IDN', 'Port of Balikpapan', 'IDBPN', 'Balikpapan', 'Seaport', -1.2667, 116.8333, 65, 30, 30],
            ['IDN', 'Port of Banjarmasin', 'IDBDJ', 'Banjarmasin', 'Seaport', -3.3219, 114.5908, 63, 33, 29],
            ['IDN', 'Port of Pontianak', 'IDPNK', 'Pontianak', 'Seaport', -0.0263, 109.3425, 60, 31, 30],
            ['IDN', 'Port of Ambon', 'IDAMQ', 'Ambon', 'Seaport', -3.6954, 128.1814, 58, 25, 33],
            ['IDN', 'Port of Jayapura', 'IDDJJ', 'Jayapura', 'Seaport', -2.5337, 140.7181, 57, 26, 34],
            ['IDN', 'Port of Sorong', 'IDSOQ', 'Sorong', 'Seaport', -0.8762, 131.2558, 58, 27, 35],
            ['IDN', 'Port of Kendari', 'IDKDI', 'Kendari', 'Seaport', -3.9985, 122.5120, 56, 24, 31],
            ['IDN', 'Port of Benoa', 'IDBOA', 'Denpasar', 'Seaport', -8.7467, 115.2136, 61, 26, 27],
            ['IDN', 'Port of Tenau Kupang', 'IDKOE', 'Kupang', 'Seaport', -10.1919, 123.5275, 55, 23, 32],

            // Singapore
            ['SGP', 'Port of Singapore', 'SGSIN', 'Singapore', 'Seaport', 1.2644, 103.8400, 95, 25, 20],
            ['SGP', 'Jurong Port', 'SGJUR', 'Jurong', 'Seaport', 1.3099, 103.7156, 82, 22, 18],

            // Malaysia
            ['MYS', 'Port Klang', 'MYPKG', 'Klang', 'Seaport', 3.0017, 101.3928, 86, 35, 24],
            ['MYS', 'Port of Tanjung Pelepas', 'MYTPP', 'Johor', 'Seaport', 1.3628, 103.5483, 84, 30, 22],
            ['MYS', 'Penang Port', 'MYPEN', 'Penang', 'Seaport', 5.4141, 100.3288, 70, 28, 24],
            ['MYS', 'Johor Port', 'MYJHB', 'Pasir Gudang', 'Seaport', 1.4500, 103.9000, 72, 29, 23],
            ['MYS', 'Kuantan Port', 'MYKUA', 'Kuantan', 'Seaport', 3.9800, 103.4300, 67, 25, 28],

            // Thailand
            ['THA', 'Laem Chabang Port', 'THLCH', 'Chonburi', 'Seaport', 13.0833, 100.8833, 83, 34, 26],
            ['THA', 'Bangkok Port', 'THBKK', 'Bangkok', 'Seaport', 13.7000, 100.5833, 70, 38, 25],

            // Vietnam
            ['VNM', 'Port of Hai Phong', 'VNHPH', 'Hai Phong', 'Seaport', 20.8648, 106.6835, 76, 35, 30],
            ['VNM', 'Cat Lai Terminal', 'VNCLI', 'Ho Chi Minh City', 'Container Terminal', 10.7667, 106.7833, 78, 38, 29],
            ['VNM', 'Da Nang Port', 'VNDAD', 'Da Nang', 'Seaport', 16.0678, 108.2208, 68, 25, 30],

            // Philippines
            ['PHL', 'Port of Manila', 'PHMNL', 'Manila', 'Seaport', 14.5833, 120.9667, 78, 42, 34],
            ['PHL', 'Port of Cebu', 'PHCEB', 'Cebu', 'Seaport', 10.3000, 123.9000, 66, 28, 32],
            ['PHL', 'Davao Port', 'PHDVO', 'Davao', 'Seaport', 7.0667, 125.6333, 62, 25, 32],

            // China
            ['CHN', 'Port of Shanghai', 'CNSHA', 'Shanghai', 'Seaport', 31.2304, 121.4737, 96, 55, 35],
            ['CHN', 'Port of Ningbo-Zhoushan', 'CNNGB', 'Ningbo', 'Seaport', 29.8683, 121.5440, 94, 48, 38],
            ['CHN', 'Port of Shenzhen', 'CNSZX', 'Shenzhen', 'Seaport', 22.5431, 114.0579, 90, 46, 32],
            ['CHN', 'Port of Guangzhou', 'CNCAN', 'Guangzhou', 'Seaport', 23.1291, 113.2644, 88, 42, 30],
            ['CHN', 'Port of Qingdao', 'CNQDG', 'Qingdao', 'Seaport', 36.0671, 120.3826, 86, 38, 28],
            ['CHN', 'Port of Tianjin', 'CNTSN', 'Tianjin', 'Seaport', 39.0000, 117.7000, 85, 40, 30],
            ['CHN', 'Port of Xiamen', 'CNXMN', 'Xiamen', 'Seaport', 24.4798, 118.0894, 78, 32, 31],
            ['CHN', 'Port of Dalian', 'CNDLC', 'Dalian', 'Seaport', 38.9140, 121.6147, 76, 30, 28],

            // Hong Kong
            ['HKG', 'Port of Hong Kong', 'HKHKG', 'Hong Kong', 'Seaport', 22.3027, 114.1772, 88, 38, 40],
            ['HKG', 'Kwai Tsing Container Terminals', 'HKKCT', 'Kwai Tsing', 'Container Terminal', 22.3446, 114.1250, 86, 36, 38],

            // Japan
            ['JPN', 'Port of Tokyo', 'JPTYO', 'Tokyo', 'Seaport', 35.6272, 139.7770, 82, 33, 30],
            ['JPN', 'Port of Yokohama', 'JPYOK', 'Yokohama', 'Seaport', 35.4437, 139.6380, 84, 31, 29],
            ['JPN', 'Port of Kobe', 'JPUKB', 'Kobe', 'Seaport', 34.6901, 135.1955, 80, 28, 27],
            ['JPN', 'Port of Nagoya', 'JPNGO', 'Nagoya', 'Seaport', 35.0500, 136.8500, 81, 30, 28],
            ['JPN', 'Port of Osaka', 'JPOSA', 'Osaka', 'Seaport', 34.6500, 135.4333, 77, 29, 27],

            // South Korea
            ['KOR', 'Port of Busan', 'KRBUS', 'Busan', 'Seaport', 35.1000, 129.0400, 90, 34, 26],
            ['KOR', 'Port of Incheon', 'KRINC', 'Incheon', 'Seaport', 37.4563, 126.7052, 78, 32, 26],
            ['KOR', 'Port of Gwangyang', 'KRKAN', 'Gwangyang', 'Seaport', 34.9000, 127.7000, 75, 27, 25],

            // India
            ['IND', 'Jawaharlal Nehru Port', 'INNSA', 'Navi Mumbai', 'Seaport', 18.9490, 72.9512, 82, 42, 29],
            ['IND', 'Mundra Port', 'INMUN', 'Mundra', 'Seaport', 22.8394, 69.7211, 84, 35, 24],
            ['IND', 'Port of Chennai', 'INMAA', 'Chennai', 'Seaport', 13.0827, 80.2707, 75, 38, 31],
            ['IND', 'Port of Kolkata', 'INCCU', 'Kolkata', 'Seaport', 22.5726, 88.3639, 68, 36, 34],
            ['IND', 'Cochin Port', 'INCOK', 'Kochi', 'Seaport', 9.9312, 76.2673, 70, 30, 32],

            // United Arab Emirates
            ['ARE', 'Jebel Ali Port', 'AEJEA', 'Dubai', 'Seaport', 25.0118, 55.0610, 92, 30, 20],
            ['ARE', 'Khalifa Port', 'AEKHL', 'Abu Dhabi', 'Seaport', 24.8000, 54.6500, 84, 24, 21],
            ['ARE', 'Port Rashid', 'AEPRA', 'Dubai', 'Seaport', 25.2700, 55.2800, 74, 26, 20],

            // Saudi Arabia
            ['SAU', 'Jeddah Islamic Port', 'SAJED', 'Jeddah', 'Seaport', 21.4858, 39.1925, 82, 35, 24],
            ['SAU', 'King Abdulaziz Port', 'SADMM', 'Dammam', 'Seaport', 26.4350, 50.1030, 78, 30, 23],

            // Netherlands
            ['NLD', 'Port of Rotterdam', 'NLRTM', 'Rotterdam', 'Seaport', 51.9244, 4.4777, 95, 28, 26],
            ['NLD', 'Port of Amsterdam', 'NLAMS', 'Amsterdam', 'Seaport', 52.3676, 4.9041, 78, 24, 25],

            // Belgium
            ['BEL', 'Port of Antwerp-Bruges', 'BEANR', 'Antwerp', 'Seaport', 51.2602, 4.4028, 90, 30, 25],
            ['BEL', 'Port of Zeebrugge', 'BEZEE', 'Zeebrugge', 'Seaport', 51.3300, 3.2000, 78, 25, 26],

            // Germany
            ['DEU', 'Port of Hamburg', 'DEHAM', 'Hamburg', 'Seaport', 53.5461, 9.9661, 80, 30, 28],
            ['DEU', 'Port of Bremerhaven', 'DEBRV', 'Bremerhaven', 'Seaport', 53.5396, 8.5809, 76, 26, 30],
            ['DEU', 'Port of Wilhelmshaven', 'DEWVN', 'Wilhelmshaven', 'Seaport', 53.5167, 8.1333, 74, 22, 30],

            // United Kingdom
            ['GBR', 'Port of Felixstowe', 'GBFXT', 'Felixstowe', 'Seaport', 51.9542, 1.3100, 82, 32, 28],
            ['GBR', 'Port of Southampton', 'GBSOU', 'Southampton', 'Seaport', 50.9097, -1.4044, 78, 27, 26],
            ['GBR', 'London Gateway Port', 'GBLGP', 'London', 'Container Terminal', 51.5000, 0.5000, 80, 26, 27],

            // France
            ['FRA', 'Port of Le Havre', 'FRLEH', 'Le Havre', 'Seaport', 49.4944, 0.1079, 80, 28, 27],
            ['FRA', 'Port of Marseille Fos', 'FRMRS', 'Marseille', 'Seaport', 43.2965, 5.3698, 78, 26, 25],
            ['FRA', 'Port of Dunkirk', 'FRDKK', 'Dunkirk', 'Seaport', 51.0344, 2.3768, 72, 24, 28],

            // Spain
            ['ESP', 'Port of Valencia', 'ESVLC', 'Valencia', 'Seaport', 39.4699, -0.3763, 84, 32, 24],
            ['ESP', 'Port of Algeciras', 'ESALG', 'Algeciras', 'Seaport', 36.1408, -5.4562, 86, 30, 24],
            ['ESP', 'Port of Barcelona', 'ESBCN', 'Barcelona', 'Seaport', 41.3851, 2.1734, 80, 28, 24],

            // Italy
            ['ITA', 'Port of Genoa', 'ITGOA', 'Genoa', 'Seaport', 44.4056, 8.9463, 78, 32, 25],
            ['ITA', 'Port of Trieste', 'ITTRS', 'Trieste', 'Seaport', 45.6495, 13.7768, 76, 25, 24],
            ['ITA', 'Port of Gioia Tauro', 'ITGIT', 'Gioia Tauro', 'Seaport', 38.4250, 15.9000, 80, 28, 24],

            // Turkey
            ['TUR', 'Port of Ambarli', 'TRAMR', 'Istanbul', 'Seaport', 40.9667, 28.6833, 80, 36, 25],
            ['TUR', 'Port of Mersin', 'TRMER', 'Mersin', 'Seaport', 36.8000, 34.6333, 74, 30, 24],
            ['TUR', 'Port of Izmir', 'TRIZM', 'Izmir', 'Seaport', 38.4237, 27.1428, 72, 28, 24],

            // Egypt
            ['EGY', 'Port Said Port', 'EGPSD', 'Port Said', 'Seaport', 31.2653, 32.3019, 80, 34, 22],
            ['EGY', 'Alexandria Port', 'EGALY', 'Alexandria', 'Seaport', 31.2001, 29.9187, 76, 32, 22],
            ['EGY', 'Suez Port', 'EGSUZ', 'Suez', 'Seaport', 29.9668, 32.5498, 72, 28, 22],

            // Morocco
            ['MAR', 'Tanger Med Port', 'MAPTM', 'Tangier', 'Seaport', 35.8840, -5.5000, 88, 27, 23],
            ['MAR', 'Port of Casablanca', 'MACAS', 'Casablanca', 'Seaport', 33.5731, -7.5898, 74, 30, 24],

            // South Africa
            ['ZAF', 'Port of Durban', 'ZADUR', 'Durban', 'Seaport', -29.8587, 31.0218, 78, 36, 30],
            ['ZAF', 'Port of Cape Town', 'ZACPT', 'Cape Town', 'Seaport', -33.9249, 18.4241, 72, 30, 31],
            ['ZAF', 'Port of Ngqura', 'ZANGQ', 'Gqeberha', 'Seaport', -33.8000, 25.6833, 74, 26, 30],

            // United States
            ['USA', 'Port of Los Angeles', 'USLAX', 'Los Angeles', 'Seaport', 33.7405, -118.2775, 90, 42, 24],
            ['USA', 'Port of Long Beach', 'USLGB', 'Long Beach', 'Seaport', 33.7542, -118.2165, 88, 40, 24],
            ['USA', 'Port of New York and New Jersey', 'USNYC', 'New York', 'Seaport', 40.6681, -74.0451, 86, 38, 28],
            ['USA', 'Port of Savannah', 'USSAV', 'Savannah', 'Seaport', 32.0835, -81.0998, 82, 32, 26],
            ['USA', 'Port of Houston', 'USHOU', 'Houston', 'Seaport', 29.7604, -95.3698, 84, 35, 30],
            ['USA', 'Port of Seattle-Tacoma', 'USSEA', 'Seattle', 'Seaport', 47.6062, -122.3321, 80, 30, 28],
            ['USA', 'Port of Oakland', 'USOAK', 'Oakland', 'Seaport', 37.8044, -122.2711, 76, 32, 24],

            // Canada
            ['CAN', 'Port of Vancouver', 'CAVAN', 'Vancouver', 'Seaport', 49.2827, -123.1207, 84, 32, 30],
            ['CAN', 'Port of Montreal', 'CAMTR', 'Montreal', 'Seaport', 45.5017, -73.5673, 76, 28, 31],
            ['CAN', 'Port of Prince Rupert', 'CAPRR', 'Prince Rupert', 'Seaport', 54.3150, -130.3208, 74, 24, 32],

            // Mexico
            ['MEX', 'Port of Manzanillo', 'MXZLO', 'Manzanillo', 'Seaport', 19.0500, -104.3167, 80, 35, 28],
            ['MEX', 'Port of Veracruz', 'MXVER', 'Veracruz', 'Seaport', 19.1738, -96.1342, 76, 32, 29],
            ['MEX', 'Port of Lazaro Cardenas', 'MXLZC', 'Lazaro Cardenas', 'Seaport', 17.9568, -102.1943, 78, 30, 29],

            // Brazil
            ['BRA', 'Port of Santos', 'BRSSZ', 'Santos', 'Seaport', -23.9608, -46.3336, 84, 40, 30],
            ['BRA', 'Port of Rio de Janeiro', 'BRRIO', 'Rio de Janeiro', 'Seaport', -22.9068, -43.1729, 72, 34, 30],
            ['BRA', 'Port of Paranagua', 'BRPNG', 'Paranagua', 'Seaport', -25.5205, -48.5095, 76, 32, 31],

            // Chile
            ['CHL', 'Port of San Antonio', 'CLSAI', 'San Antonio', 'Seaport', -33.5947, -71.6075, 76, 30, 25],
            ['CHL', 'Port of Valparaiso', 'CLVAP', 'Valparaiso', 'Seaport', -33.0472, -71.6127, 74, 30, 26],

            // Peru
            ['PER', 'Port of Callao', 'PECLL', 'Callao', 'Seaport', -12.0464, -77.0428, 78, 36, 28],
            ['PER', 'Port of Paita', 'PEPAI', 'Paita', 'Seaport', -5.0892, -81.1144, 68, 28, 27],

            // Australia
            ['AUS', 'Port of Melbourne', 'AUMEL', 'Melbourne', 'Seaport', -37.8136, 144.9631, 74, 32, 24],
            ['AUS', 'Port Botany', 'AUBTB', 'Sydney', 'Seaport', -33.9692, 151.2225, 72, 34, 22],
            ['AUS', 'Port of Brisbane', 'AUBNE', 'Brisbane', 'Seaport', -27.3817, 153.1670, 76, 28, 24],
            ['AUS', 'Port of Fremantle', 'AUFRE', 'Fremantle', 'Seaport', -32.0569, 115.7439, 70, 26, 23],
            ['AUS', 'Port Adelaide', 'AUPAE', 'Adelaide', 'Seaport', -34.8470, 138.5070, 68, 24, 23],

            // New Zealand
            ['NZL', 'Port of Auckland', 'NZAKL', 'Auckland', 'Seaport', -36.8485, 174.7633, 72, 28, 26],
            ['NZL', 'Port of Tauranga', 'NZTRG', 'Tauranga', 'Seaport', -37.6878, 176.1651, 76, 26, 25],
            ['NZL', 'Lyttelton Port', 'NZLYT', 'Christchurch', 'Seaport', -43.6000, 172.7167, 68, 24, 27],
        ];

        foreach ($ports as $port) {
            [
                $iso3Code,
                $name,
                $code,
                $city,
                $type,
                $latitude,
                $longitude,
                $capacityScore,
                $congestionScore,
                $weatherExposureScore,
            ] = $port;

            $country = Country::query()
                ->where('iso3_code', $iso3Code)
                ->first();

            if (!$country) {
                continue;
            }

            $riskScore = $this->calculateRiskScore(
                $capacityScore,
                $congestionScore,
                $weatherExposureScore
            );

            GlobalPort::create([
                'country_id' => $country->id,
                'name' => $name,
                'code' => $code,
                'city' => $city,
                'type' => $type,
                'latitude' => $latitude,
                'longitude' => $longitude,
                'capacity_score' => $capacityScore,
                'congestion_score' => $congestionScore,
                'weather_exposure_score' => $weatherExposureScore,
                'risk_score' => $riskScore,
                'risk_level' => $this->riskLevel($riskScore),
                'description' => $name . ' merupakan pelabuhan internasional/utama di ' . $city . ' yang mendukung aktivitas ekspor, impor, dan distribusi logistik global maupun regional.',
            ]);
        }
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
}
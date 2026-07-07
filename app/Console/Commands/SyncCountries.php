<?php

namespace App\Console\Commands;

use App\Services\CountryService;
use Illuminate\Console\Command;
use Throwable;

class SyncCountries extends Command
{
    protected $signature = 'countries:sync';

    protected $description =
        'Sinkronisasi seluruh data negara dari REST Countries API ke database';

    public function handle(CountryService $countryService): int
    {
        $this->info('Memulai sinkronisasi data negara...');

        try {
            $result = $countryService->syncCountries();

            $this->newLine();
            $this->info('Sinkronisasi negara berhasil.');

            $this->table(
                ['Keterangan', 'Jumlah'],
                [
                    [
                        'Data diterima dari API',
                        $result['total_received'],
                    ],
                    [
                        'Negara disimpan',
                        $result['synced'],
                    ],
                    [
                        'Data dilewati',
                        $result['skipped'],
                    ],
                ]
            );

            $skippedCountries = $result['skipped_countries'] ?? [];

            if (!empty($skippedCountries)) {
                $this->newLine();

                $this->warn(
                    'Detail data yang dilewati:'
                );

                $rows = [];

                foreach ($skippedCountries as $country) {
                    $rows[] = [
                        $country['name'] ?? 'NULL',
                        $country['iso2'] ?? 'NULL',
                        $country['iso3'] ?? 'NULL',
                        $country['reason'] ?? 'Alasan tidak diketahui',
                    ];
                }

                $this->table(
                    [
                        'Nama',
                        'ISO2',
                        'ISO3',
                        'Alasan',
                    ],
                    $rows
                );
            } else {
                $this->newLine();

                $this->info(
                    'Tidak ada data negara yang dilewati.'
                );
            }

            $this->newLine();

            $this->info(
                'Proses sinkronisasi selesai.'
            );

            return self::SUCCESS;
        } catch (Throwable $exception) {
            $this->newLine();

            $this->error(
                'Sinkronisasi negara gagal.'
            );

            $this->error(
                $exception->getMessage()
            );

            return self::FAILURE;
        }
    }
}
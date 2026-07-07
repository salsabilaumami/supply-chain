<?php

namespace App\Console\Commands;

use App\Models\Country;
use App\Services\WorldBankService;
use Illuminate\Console\Command;
use Throwable;

class SyncWorldBank extends Command
{
    protected $signature = 'worldbank:sync
                            {country? : Kode ISO2 atau ISO3 negara}
                            {--all : Sinkronkan seluruh negara}';

    protected $description =
        'Sinkronisasi indikator ekonomi dari World Bank API ke database';

    public function handle(WorldBankService $worldBankService): int
    {
        if ($this->option('all')) {
            return $this->syncAllCountries($worldBankService);
        }

        return $this->syncSingleCountry($worldBankService);
    }

    private function syncSingleCountry(
        WorldBankService $worldBankService
    ): int {
        $isoCode = $this->argument('country');

        if (empty($isoCode)) {
            $isoCode = 'IDN';
        }

        $country = Country::query()
            ->byIsoCode($isoCode)
            ->first();

        if (!$country) {
            $this->error(
                'Negara dengan kode ' . strtoupper($isoCode) . ' tidak ditemukan.'
            );

            return self::FAILURE;
        }

        $this->info(
            'Memulai sinkronisasi World Bank untuk '
            . $country->name
            . ' (' . $country->iso3_code . ')...'
        );

        try {
            $result = $worldBankService->syncCountryIndicators(
                $country
            );

            $this->newLine();

            $this->info(
                'Sinkronisasi World Bank berhasil.'
            );

            $this->table(
                ['Keterangan', 'Hasil'],
                [
                    ['Negara', $result['country']],
                    ['Kode ISO3', $result['iso3_code']],
                    ['Indikator disimpan', $result['synced']],
                    ['Indikator dilewati', $result['skipped']],
                ]
            );

            $this->displaySkippedIndicators(
                $result['skipped_indicators'] ?? []
            );

            return self::SUCCESS;
        } catch (Throwable $exception) {
            $this->newLine();

            $this->error(
                'Sinkronisasi World Bank gagal.'
            );

            $this->error(
                $exception->getMessage()
            );

            return self::FAILURE;
        }
    }

    private function syncAllCountries(
        WorldBankService $worldBankService
    ): int {
        $countries = Country::query()
            ->alphabetical()
            ->get();

        if ($countries->isEmpty()) {
            $this->error(
                'Tidak ada data negara di database.'
            );

            return self::FAILURE;
        }

        $this->warn(
            'Mode sinkronisasi seluruh negara akan memproses '
            . $countries->count()
            . ' negara.'
        );

        if (
            !$this->confirm(
                'Apakah kamu yakin ingin melanjutkan?',
                false
            )
        ) {
            $this->info(
                'Sinkronisasi dibatalkan.'
            );

            return self::SUCCESS;
        }

        $this->newLine();

        $progressBar = $this->output->createProgressBar(
            $countries->count()
        );

        $progressBar->start();

        $successfulCountries = 0;
        $failedCountries = 0;
        $syncedIndicators = 0;
        $skippedIndicators = 0;
        $failures = [];

        foreach ($countries as $country) {
            try {
                $result = $worldBankService
                    ->syncCountryIndicators($country);

                $successfulCountries++;
                $syncedIndicators += $result['synced'];
                $skippedIndicators += $result['skipped'];
            } catch (Throwable $exception) {
                $failedCountries++;

                $failures[] = [
                    'country' => $country->name,
                    'iso3' => $country->iso3_code,
                    'error' => $exception->getMessage(),
                ];
            }

            $progressBar->advance();

            usleep(250000);
        }

        $progressBar->finish();

        $this->newLine(2);

        $this->info(
            'Sinkronisasi seluruh negara selesai.'
        );

        $this->table(
            ['Keterangan', 'Jumlah'],
            [
                ['Total negara', $countries->count()],
                ['Negara berhasil', $successfulCountries],
                ['Negara gagal', $failedCountries],
                ['Indikator disimpan', $syncedIndicators],
                ['Indikator dilewati', $skippedIndicators],
            ]
        );

        if (!empty($failures)) {
            $this->newLine();

            $this->warn(
                'Daftar negara yang gagal:'
            );

            $rows = [];

            foreach ($failures as $failure) {
                $rows[] = [
                    $failure['country'],
                    $failure['iso3'],
                    $failure['error'],
                ];
            }

            $this->table(
                ['Negara', 'ISO3', 'Error'],
                $rows
            );
        }

        return $failedCountries === 0
            ? self::SUCCESS
            : self::FAILURE;
    }

    private function displaySkippedIndicators(
        array $skippedIndicators
    ): void {
        if (empty($skippedIndicators)) {
            return;
        }

        $this->newLine();

        $this->warn(
            'Indikator yang dilewati:'
        );

        $rows = [];

        foreach ($skippedIndicators as $indicator) {
            $rows[] = [
                $indicator['indicator'] ?? '-',
                $indicator['code'] ?? '-',
                $indicator['reason'] ?? '-',
            ];
        }

        $this->table(
            ['Indikator', 'Kode', 'Alasan'],
            $rows
        );
    }
}
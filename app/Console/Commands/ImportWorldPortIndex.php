<?php

namespace App\Console\Commands;

use App\Services\WorldPortIndexImportService;
use Illuminate\Console\Command;
use Throwable;

class ImportWorldPortIndex extends Command
{
    protected $signature = 'ports:import-world-port-index {--file= : Path file CSV World Port Index}';

    protected $description = 'Import World Port Index CSV ke tabel global_ports';

    public function handle(WorldPortIndexImportService $importService): int
    {
        $this->info('Mulai import World Port Index Dataset...');

        try {
            $result = $importService->import($this->option('file'));

            $this->info('Import selesai.');
            $this->line('File       : ' . $result['file']);
            $this->line('Baru       : ' . $result['imported']);
            $this->line('Update     : ' . $result['updated']);
            $this->line('Dilewati   : ' . $result['skipped']);
            $this->line('Total save : ' . $result['total_saved']);

            if (!empty($result['skipped_rows'])) {
                $this->warn('Contoh data yang dilewati:');

                foreach ($result['skipped_rows'] as $row) {
                    $this->line(
                        '- ' .
                        ($row['port'] ?? '-') .
                        ' | ' .
                        ($row['country_code'] ?? '-') .
                        ' | ' .
                        ($row['reason'] ?? '-')
                    );
                }
            }

            return self::SUCCESS;
        } catch (Throwable $exception) {
            $this->error('Import gagal: ' . $exception->getMessage());

            return self::FAILURE;
        }
    }
}
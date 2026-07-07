<?php

namespace App\Services;

use App\Models\Country;
use App\Models\ExchangeRate;
use Carbon\Carbon;
use Illuminate\Support\Facades\Http;
use RuntimeException;

class ExchangeRateService
{
    private const CACHE_MINUTES = 60;

    public function getLatestRate(
        Country $country,
        string $baseCurrency = 'USD',
        bool $forceRefresh = false
    ): ExchangeRate {
        $targetCurrency = $this->normalizeCurrencyCode($country->currency_code);
        $baseCurrency = $this->normalizeCurrencyCode($baseCurrency);

        if (!$forceRefresh) {
            $cachedRate = $this->getFreshCachedRate(
                $country,
                $baseCurrency,
                $targetCurrency
            );

            if ($cachedRate) {
                return $cachedRate;
            }
        }

        return $this->fetchAndStore(
            $country,
            $baseCurrency,
            $targetCurrency
        );
    }

    public function getRateHistory(
        Country $country,
        string $baseCurrency = 'USD',
        int $limit = 30
    ) {
        $targetCurrency = $this->normalizeCurrencyCode($country->currency_code);
        $baseCurrency = $this->normalizeCurrencyCode($baseCurrency);

        return ExchangeRate::query()
            ->where('country_id', $country->id)
            ->where('base_currency', $baseCurrency)
            ->where('target_currency', $targetCurrency)
            ->latest('recorded_at')
            ->limit($limit)
            ->get();
    }

    private function fetchAndStore(
        Country $country,
        string $baseCurrency,
        string $targetCurrency
    ): ExchangeRate {
        $response = Http::acceptJson()
            ->timeout(20)
            ->retry(3, 700)
            ->get(
                rtrim(config('services.exchange_rate.base_url'), '/') .
                '/latest/' . $baseCurrency
            );

        if ($response->failed()) {
            throw new RuntimeException(
                'Gagal mengambil data kurs. Status API: ' .
                $response->status()
            );
        }

        $payload = $response->json();

        if (($payload['result'] ?? null) !== 'success') {
            throw new RuntimeException('Respons API kurs tidak berhasil.');
        }

        $rates = $payload['rates'] ?? [];

        if (!array_key_exists($targetCurrency, $rates)) {
            throw new RuntimeException(
                'Mata uang ' . $targetCurrency .
                ' tidak tersedia pada respons API kurs.'
            );
        }

        $currentRate = (float) $rates[$targetCurrency];
        $recordedAt = $this->resolveRecordedAt($payload);

        $previousRate = $this->getPreviousRate(
            $country,
            $baseCurrency,
            $targetCurrency,
            $recordedAt
        );

        $changePercentage = $this->calculateChangePercentage(
            $currentRate,
            $previousRate
        );

        $currencyRisk = $this->calculateCurrencyRisk(
            $changePercentage
        );

        return ExchangeRate::create([
            'country_id' => $country->id,
            'base_currency' => $baseCurrency,
            'target_currency' => $targetCurrency,
            'rate' => $currentRate,
            'change_percentage' => $changePercentage,
            'currency_risk' => $currencyRisk,
            'recorded_at' => $recordedAt,
            'fetched_at' => now(),
        ]);
    }

    private function getFreshCachedRate(
        Country $country,
        string $baseCurrency,
        string $targetCurrency
    ): ?ExchangeRate {
        return ExchangeRate::query()
            ->where('country_id', $country->id)
            ->where('base_currency', $baseCurrency)
            ->where('target_currency', $targetCurrency)
            ->where('fetched_at', '>=', now()->subMinutes(self::CACHE_MINUTES))
            ->latest('recorded_at')
            ->first();
    }

    private function getPreviousRate(
        Country $country,
        string $baseCurrency,
        string $targetCurrency,
        Carbon $recordedAt
    ): ?ExchangeRate {
        return ExchangeRate::query()
            ->where('country_id', $country->id)
            ->where('base_currency', $baseCurrency)
            ->where('target_currency', $targetCurrency)
            ->where('recorded_at', '<', $recordedAt)
            ->latest('recorded_at')
            ->first();
    }

    private function calculateChangePercentage(
        float $currentRate,
        ?ExchangeRate $previousRate
    ): ?float {
        if (!$previousRate || (float) $previousRate->rate === 0.0) {
            return null;
        }

        $oldRate = (float) $previousRate->rate;

        return round((($currentRate - $oldRate) / $oldRate) * 100, 4);
    }

    private function calculateCurrencyRisk(?float $changePercentage): float
    {
        if ($changePercentage === null) {
            return 20;
        }

        $change = abs($changePercentage);

        if ($change <= 1) {
            return 15;
        }

        if ($change <= 3) {
            return 35;
        }

        if ($change <= 5) {
            return 60;
        }

        if ($change <= 10) {
            return 80;
        }

        return 95;
    }

    private function resolveRecordedAt(array $payload): Carbon
    {
        if (!empty($payload['time_last_update_unix'])) {
            return Carbon::createFromTimestamp(
                (int) $payload['time_last_update_unix']
            );
        }

        return now();
    }

    private function normalizeCurrencyCode(?string $currencyCode): string
    {
        $currencyCode = strtoupper(trim((string) $currencyCode));

        if (!preg_match('/^[A-Z]{3}$/', $currencyCode)) {
            throw new RuntimeException(
                'Kode mata uang tidak valid atau belum tersedia.'
            );
        }

        return $currencyCode;
    }
}
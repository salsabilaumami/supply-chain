@extends('layouts.app')

@section('title', 'Dampak Mata Uang')

@section('content')
@php
    $currencyRiskWidth = $exchangeRate
        ? min(100, max(0, (float) $exchangeRate->currency_risk))
        : 0;

    $currencyChartHistory = $history->reverse()->values();

    $currencyChartLabels = $currencyChartHistory
        ->map(function ($item) {
            return $item->recorded_at
                ? $item->recorded_at->format('d M H:i')
                : '';
        })
        ->toArray();

    $currencyChartRates = $currencyChartHistory
        ->map(function ($item) {
            return (float) $item->rate;
        })
        ->toArray();

    $currencyChartTitle = 'Kurs ' .
        $baseCurrency .
        ' ke ' .
        ($selectedCountry->currency_code ?? '-');
@endphp

<div class="dashboard-page">
    <section class="dashboard-header">
        <div class="dashboard-heading">
            <div class="page-eyebrow">
                Currency Impact Dashboard
            </div>

            <h1 class="page-title">
                Dampak Mata Uang
            </h1>

            <p class="page-description">
                Pantau kurs mata uang negara tujuan impor dan hitung risiko
                perubahan kurs terhadap rantai pasokan.
            </p>
        </div>

        <form
            method="GET"
            action="{{ route('currency.index') }}"
            class="country-selector"
        >
            <label for="country" class="country-selector-label">
                Pilih Negara
            </label>

            <div class="country-selector-control">
                <select
                    name="country"
                    id="country"
                    class="form-select"
                    onchange="this.form.submit()"
                >
                    @foreach ($countries as $country)
                        <option
                            value="{{ $country->iso3_code }}"
                            {{ $selectedCountry->id === $country->id ? 'selected' : '' }}
                        >
                            {{ $country->name }} - {{ $country->currency_code }}
                        </option>
                    @endforeach
                </select>
            </div>

            <input type="hidden" name="base" value="{{ $baseCurrency }}">
        </form>
    </section>

    @if ($errorMessage)
        <div class="alert alert-warning border-0 shadow-sm">
            <i class="bi bi-exclamation-triangle me-2"></i>
            {{ $errorMessage }}
        </div>
    @endif

    <section class="country-overview-card">
        <div class="country-overview-main">
            <div class="country-flag">
                @if ($selectedCountry->flag_url)
                    <img
                        src="{{ $selectedCountry->flag_url }}"
                        alt="Bendera {{ $selectedCountry->name }}"
                    >
                @else
                    <div class="country-flag-placeholder">
                        <i class="bi bi-flag"></i>
                    </div>
                @endif
            </div>

            <div class="country-identity">
                <span class="country-overview-label">
                    Negara Tujuan
                </span>

                <h2>
                    {{ $selectedCountry->name }}
                </h2>

                <p>
                    {{ $baseCurrency }} ke {{ $selectedCountry->currency_code }}
                </p>
            </div>
        </div>

        <div class="country-overview-stats">
            <div class="country-stat">
                <span>Mata Uang Lokal</span>
                <strong>{{ $selectedCountry->currency_code ?? '-' }}</strong>
                <small>{{ $selectedCountry->currency_name ?? '-' }}</small>
            </div>

            <div class="country-stat">
                <span>Kurs Saat Ini</span>
                <strong>
                    @if ($exchangeRate)
                        {{ number_format((float) $exchangeRate->rate, 4, ',', '.') }}
                    @else
                        -
                    @endif
                </strong>
                <small>1 {{ $baseCurrency }}</small>
            </div>

            <div class="country-stat">
                <span>Perubahan</span>
                <strong>
                    @if ($exchangeRate && $exchangeRate->change_percentage !== null)
                        {{ number_format((float) $exchangeRate->change_percentage, 4, ',', '.') }}%
                    @else
                        Data awal
                    @endif
                </strong>
                <small>Dibanding data sebelumnya</small>
            </div>

            <div class="country-stat">
                <span>Risiko Kurs</span>
                <strong>
                    @if ($exchangeRate)
                        {{ number_format((float) $exchangeRate->currency_risk, 0) }} / 100
                    @else
                        -
                    @endif
                </strong>
                <small>Simple scoring algorithm</small>
            </div>
        </div>
    </section>

    <section class="risk-analysis-grid">
        <article class="analysis-card total-risk-card">
            <div class="analysis-card-header">
                <div>
                    <span class="analysis-label">
                        Risiko Mata Uang
                    </span>

                    <strong class="total-risk-score">
                        @if ($exchangeRate)
                            {{ number_format((float) $exchangeRate->currency_risk, 2) }}
                        @else
                            -
                        @endif
                    </strong>
                </div>

                <span class="badge text-bg-primary px-3 py-2">
                    Currency Risk
                </span>
            </div>

            <div class="progress total-risk-progress">
                <div
                    id="currencyRiskProgress"
                    class="progress-bar"
                    role="progressbar"
                    aria-valuenow="{{ $currencyRiskWidth }}"
                    aria-valuemin="0"
                    aria-valuemax="100"
                    data-risk-width="{{ $currencyRiskWidth }}"
                ></div>
            </div>

            <p class="analysis-description">
                Skor kurs dihitung dari besar perubahan persentase nilai tukar.
                Semakin besar fluktuasinya, semakin tinggi risiko biaya impor.
            </p>
        </article>

        <article class="analysis-card">
            <div class="analysis-heading">
                <h3>
                    Grafik Riwayat Kurs
                </h3>

                <p>
                    Data diambil dari tabel <strong>exchange_rates</strong>
                    berdasarkan negara dan mata uang yang dipilih.
                </p>
            </div>

            <div class="mt-4">
                <div
                    id="currencyChartData"
                    class="d-none"
                    data-labels='{{ json_encode($currencyChartLabels) }}'
                    data-rates='{{ json_encode($currencyChartRates) }}'
                    data-title="{{ $currencyChartTitle }}"
                ></div>

                <canvas id="currencyChart" height="120"></canvas>
            </div>
        </article>
    </section>

    <section class="economic-section">
        <div class="section-heading">
            <div class="page-eyebrow">
                Riwayat Data
            </div>

            <h2>
                Log Kurs Tersimpan
            </h2>

            <p>
                Setiap request baru akan disimpan agar sistem dapat menghitung
                perubahan kurs dari waktu ke waktu.
            </p>
        </div>

        <div class="analysis-card">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead>
                        <tr>
                            <th>Waktu Data</th>
                            <th>Pair</th>
                            <th>Kurs</th>
                            <th>Perubahan</th>
                            <th>Risiko</th>
                            <th>Diambil</th>
                        </tr>
                    </thead>

                    <tbody>
                        @forelse ($history as $item)
                            <tr>
                                <td>
                                    {{ $item->recorded_at ? $item->recorded_at->format('d M Y H:i') : '-' }}
                                </td>

                                <td>
                                    {{ $item->base_currency }} / {{ $item->target_currency }}
                                </td>

                                <td>
                                    {{ number_format((float) $item->rate, 4, ',', '.') }}
                                </td>

                                <td>
                                    @if ($item->change_percentage !== null)
                                        {{ number_format((float) $item->change_percentage, 4, ',', '.') }}%
                                    @else
                                        Data awal
                                    @endif
                                </td>

                                <td>
                                    {{ number_format((float) $item->currency_risk, 2) }}
                                </td>

                                <td>
                                    {{ $item->fetched_at ? $item->fetched_at->format('d M Y H:i') : '-' }}
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="text-center text-muted py-4">
                                    Data kurs belum tersedia.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </section>
</div>
@endsection

@push('scripts')
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

    <script>
        const riskProgress = document.getElementById('currencyRiskProgress');

        if (riskProgress) {
            const riskWidth = Number(riskProgress.dataset.riskWidth || 0);
            riskProgress.style.width = `${riskWidth}%`;
        }

        const chartElement = document.getElementById('currencyChart');
        const chartDataElement = document.getElementById('currencyChartData');

        if (chartElement && chartDataElement) {
            const currencyLabels = JSON.parse(
                chartDataElement.dataset.labels || '[]'
            );

            const currencyRates = JSON.parse(
                chartDataElement.dataset.rates || '[]'
            );

            const currencyTitle = chartDataElement.dataset.title || 'Kurs Mata Uang';

            new Chart(chartElement, {
                type: 'line',
                data: {
                    labels: currencyLabels,
                    datasets: [
                        {
                            label: currencyTitle,
                            data: currencyRates,
                            tension: 0.35,
                            fill: false,
                        }
                    ],
                },
                options: {
                    responsive: true,
                    plugins: {
                        legend: {
                            display: true,
                        },
                    },
                    scales: {
                        y: {
                            beginAtZero: false,
                        },
                    },
                },
            });
        }
    </script>
@endpush
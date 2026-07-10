@extends('layouts.app')

@section('title', 'Dampak Mata Uang')

@section('content')
    @php
        $riskBadgeClass = match (true) {
            ($currencyRisk ?? 0) >= 75 => 'bg-danger',
            ($currencyRisk ?? 0) >= 50 => 'bg-warning text-dark',
            ($currencyRisk ?? 0) >= 25 => 'bg-info text-dark',
            default => 'bg-success',
        };
    @endphp

    <div class="dashboard-page">
        <section class="dashboard-header">
            <div class="dashboard-heading">
                <div class="page-eyebrow">
                    CURRENCY IMPACT DASHBOARD
                </div>

                <h1 class="page-title">
                    Dampak Mata Uang
                </h1>

                <p class="page-description">
                    Pantau nilai tukar mata uang secara real-time menggunakan ExchangeRate API
                    untuk melihat potensi dampak perubahan kurs terhadap biaya impor.
                </p>
            </div>

            <form
                method="GET"
                action="{{ route('currency.index') }}"
                class="country-selector"
            >
                <label
                    for="country"
                    class="country-selector-label"
                >
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
                                @selected($selectedCountry->id === $country->id)
                            >
                                {{ $country->display_name ?? ($country->name . ' (' . $country->iso3_code . ')') }}
                            </option>
                        @endforeach
                    </select>

                    <noscript>
                        <button
                            type="submit"
                            class="btn btn-primary"
                        >
                            Tampilkan
                        </button>
                    </noscript>
                </div>
            </form>
        </section>

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
                        Negara Dipantau
                    </span>

                    <h2>
                        {{ $selectedCountry->name }}
                    </h2>

                    <p>
                        {{ $selectedCountry->currency_code ?? '-' }}
                        —
                        {{ $selectedCountry->currency_name ?? 'Mata uang belum tersedia' }}
                    </p>
                </div>
            </div>

            <div class="country-overview-stats">
                <div class="country-stat">
                    <span>Kurs Real-time</span>

                    <strong>
                        {{ $displayRate ?? 'Belum tersedia' }}
                    </strong>

                    <small>
                        Basis USD ke {{ $selectedCountry->currency_code ?? '-' }}
                    </small>
                </div>

                <div class="country-stat">
                    <span>Perubahan Kurs</span>

                    <strong>
                        {{ $displayChange ?? 'Belum ada pembanding' }}
                    </strong>

                    <small>
                        Dibanding data sebelumnya
                    </small>
                </div>

                <div class="country-stat">
                    <span>Risiko Kurs</span>

                    <strong>
                        {{ number_format($currencyRisk ?? 0, 2, ',', '.') }}
                    </strong>

                    <small>
                        {{ $riskLabel ?? 'Belum tersedia' }}
                    </small>
                </div>

                <div class="country-stat">
                    <span>Pembaruan Terakhir</span>

                    <strong>
                        {{ $lastUpdate ?? 'Belum tersedia' }}
                    </strong>

                    <small>
                        ExchangeRate API
                    </small>
                </div>
            </div>
        </section>

        <section class="risk-analysis-grid mt-4">
            <article class="analysis-card total-risk-card">
                <div class="analysis-card-header">
                    <div>
                        <span class="analysis-label">
                            Skor Risiko Mata Uang
                        </span>

                        <strong class="total-risk-score">
                            {{ number_format($currencyRisk ?? 0, 2, ',', '.') }}
                        </strong>
                    </div>

                    <span class="badge {{ $riskBadgeClass }} px-3 py-2">
                        {{ $riskLabel ?? 'Belum tersedia' }}
                    </span>
                </div>

                <div class="progress total-risk-progress">
                    <div
                        class="progress-bar js-progress-bar"
                        role="progressbar"
                        data-progress-width="{{ min(100, max(0, $currencyRisk ?? 0)) }}"
                    ></div>
                </div>

                <p class="analysis-description">
                    Nilai risiko dihitung berdasarkan perubahan kurs mata uang.
                    Semakin besar perubahan kurs, semakin tinggi potensi risiko biaya impor.
                </p>
            </article>

            <article class="analysis-card">
                <div class="analysis-heading">
                    <h3>
                        Status ExchangeRate API
                    </h3>

                    <p>
                        Status Kurs
                    </p>
                </div>

                <div class="country-overview-stats">
                    <div class="country-stat">
                        <span>Base Currency</span>

                        <strong>
                            {{ $exchangeRate?->base_currency ?? 'USD' }}
                        </strong>

                        <small>
                            Mata uang dasar
                        </small>
                    </div>

                    <div class="country-stat">
                        <span>Target Currency</span>

                        <strong>
                            {{ $exchangeRate?->target_currency ?? ($selectedCountry->currency_code ?? '-') }}
                        </strong>

                        <small>
                            Mata uang negara dipilih
                        </small>
                    </div>

                    <div class="country-stat">
                        <span>Jumlah Riwayat</span>

                        <strong>
                            {{ $history->count() }}
                        </strong>

                        <small>
                            Data kurs tersimpan
                        </small>
                    </div>
                </div>
            </article>
        </section>

        <section class="risk-analysis-grid mt-4">
            <article
                class="analysis-card"
                style="grid-column: 1 / -1;"
            >
                <div class="analysis-heading">
                    <h3>
                        Grafik Dampak Mata Uang
                    </h3>

                </div>

                <div class="row g-4">
                    <div class="col-12 col-xl-6">
                        <div class="border rounded-4 p-3 h-100">
                            <h5 class="fw-bold mb-3">
                                Grafik Nilai Tukar
                            </h5>

                            <div style="height: 180px;">
                                <canvas id="currencyRateChart"></canvas>
                            </div>
                        </div>
                    </div>

                    <div class="col-12 col-xl-6">
                        <div class="border rounded-4 p-3 h-100">
                            <h5 class="fw-bold mb-3">
                                Grafik Risiko Kurs
                            </h5>

                            <div style="height: 180px;">
                                <canvas id="currencyRiskChart"></canvas>
                            </div>
                        </div>
                    </div>
                </div>
            </article>
        </section>

        <section class="risk-analysis-grid mt-4">
            <article
                class="analysis-card"
                style="grid-column: 1 / -1;"
            >
                <div class="analysis-heading">
                    <h3>
                        Riwayat Kurs Terakhir
                    </h3>

                    <p>
                        Daftar data kurs terbaru dari USD ke mata uang negara yang dipilih.
                    </p>
                </div>

                <div class="table-responsive">
                    <table class="table align-middle mb-0">
                        <thead>
                            <tr>
                                <th>Waktu</th>
                                <th>Base</th>
                                <th>Target</th>
                                <th>Rate</th>
                                <th>Perubahan</th>
                                <th>Risiko</th>
                            </tr>
                        </thead>

                        <tbody>
                            @forelse ($history as $item)
                                <tr>
                                    <td>
                                        {{ $item->recorded_at?->format('d M Y H:i') ?? '-' }}
                                    </td>

                                    <td>
                                        {{ $item->base_currency }}
                                    </td>

                                    <td>
                                        {{ $item->target_currency }}
                                    </td>

                                    <td>
                                        {{ number_format((float) $item->rate, 4, ',', '.') }}
                                    </td>

                                    <td>
                                        @if ($item->change_percentage !== null)
                                            {{ number_format((float) $item->change_percentage, 4, ',', '.') }}%
                                        @else
                                            Belum ada pembanding
                                        @endif
                                    </td>

                                    <td>
                                        {{ number_format((float) $item->currency_risk, 2, ',', '.') }}
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td
                                        colspan="6"
                                        class="text-center text-muted py-4"
                                    >
                                        Data kurs belum tersedia.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </article>
        </section>

        <section class="country-overview-card mt-4">
            <div class="country-overview-main">
                <div class="country-identity">
                    <span class="country-overview-label">
                        API Dampak Mata Uang
                    </span>

                    <h2>
                        Endpoint JSON Currency
                    </h2>

                </div>
            </div>

            <a
                href="{{ route('api.currency.show', ['country' => $selectedCountry->iso3_code]) }}"
                target="_blank"
                class="btn btn-outline-primary mt-3"
            >
                <i class="bi bi-code-slash me-1"></i>
                Lihat JSON API
            </a>
        </section>
    </div>
@endsection

@push('scripts')
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

    <script
        id="currencyChartData"
        type="application/json"
    >{!! json_encode($chartData ?? [], JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT) !!}</script>

    <script>
        document.addEventListener('DOMContentLoaded', function () {
            var progressBars = document.querySelectorAll('.js-progress-bar');

            progressBars.forEach(function (progressBar) {
                var width = progressBar.getAttribute('data-progress-width') || 0;

                progressBar.style.width = width + '%';
            });

            if (typeof Chart === 'undefined') {
                return;
            }

            var chartDataElement = document.getElementById('currencyChartData');
            var chartData = {};

            try {
                chartData = JSON.parse(chartDataElement.textContent || '{}');
            } catch (error) {
                chartData = {};
            }

            function getChartLabels(groupName) {
                if (
                    chartData[groupName] &&
                    chartData[groupName].labels
                ) {
                    return chartData[groupName].labels;
                }

                return [];
            }

            function getChartValues(groupName) {
                if (
                    chartData[groupName] &&
                    chartData[groupName].values
                ) {
                    return chartData[groupName].values;
                }

                return [];
            }

            function createBarChart(canvasId, label, labels, values) {
                var canvas = document.getElementById(canvasId);

                if (!canvas) {
                    return;
                }

                new Chart(canvas, {
                    type: 'bar',
                    data: {
                        labels: labels,
                        datasets: [
                            {
                                label: label,
                                data: values,
                                borderWidth: 1
                            }
                        ]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        scales: {
                            y: {
                                beginAtZero: true
                            }
                        }
                    }
                });
            }

            createBarChart(
                'currencyRateChart',
                'Nilai Tukar',
                getChartLabels('rate'),
                getChartValues('rate')
            );

            createBarChart(
                'currencyRiskChart',
                'Risiko Kurs',
                getChartLabels('risk'),
                getChartValues('risk')
            );
        });
    </script>
@endpush
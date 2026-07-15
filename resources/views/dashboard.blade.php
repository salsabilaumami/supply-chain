@extends('layouts.app')

@section('title', 'Tinjauan Global')

@section('content')
    @php
        $levelLabels = [
            'low' => 'Rendah',
            'medium' => 'Sedang',
            'moderate' => 'Sedang',
            'high' => 'Tinggi',
            'critical' => 'Kritis',
        ];

        $levelColors = [
            'low' => 'success',
            'medium' => 'warning',
            'moderate' => 'warning',
            'high' => 'danger',
            'critical' => 'dark',
        ];

        $weatherColor = $levelColors[$weatherLevel] ?? 'secondary';
        $inflationColor = $levelColors[$inflationLevel] ?? 'secondary';
        $currencyColor = $levelColors[$currencyLevel] ?? 'secondary';
        $newsColor = $levelColors[$newsLevel] ?? 'secondary';
        $riskColor = $levelColors[$riskLevel] ?? 'secondary';

        $weatherLabel = $levelLabels[$weatherLevel] ?? 'Belum tersedia';
        $inflationLabel = $levelLabels[$inflationLevel] ?? 'Belum tersedia';
        $currencyLabel = $levelLabels[$currencyLevel] ?? 'Belum tersedia';
        $newsLabel = $levelLabels[$newsLevel] ?? 'Belum tersedia';
        $riskLabel = $levelLabels[$riskLevel] ?? 'Belum tersedia';
    @endphp

    <div class="dashboard-page">
        <section class="dashboard-header">
            <div class="dashboard-heading">
                <div class="page-eyebrow">
                    Pusat Intelijen Risiko Rantai Pasokan
                </div>

                <h1 class="page-title">
                    Tinjauan Global
                </h1>

                <p class="page-description">
                    Pantau ekonomi, cuaca, kurs, berita, dan Risk Score negara terpilih.
                </p>
            </div>

            <form
                method="GET"
                action="{{ route('dashboard') }}"
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
                <div class="country-identity">
                    <span class="country-overview-label">
                        Ringkasan Global
                    </span>

                    <h2>
                        Status Monitoring Global
                    </h2>
                </div>
            </div>

            <div class="country-overview-stats">
                <div class="country-stat">
                    <span>Total Negara</span>

                    <strong>
                        {{ number_format($globalSummary['total_countries'] ?? 0, 0, ',', '.') }}
                    </strong>

                    <small>
                        Negara tersedia
                    </small>
                </div>

                <div class="country-stat">
                    <span>Negara dengan Risk Score</span>

                    <strong>
                        {{ number_format($globalSummary['countries_with_risk'] ?? 0, 0, ',', '.') }}
                    </strong>

                    <small>
                        Sudah dihitung
                    </small>
                </div>

                <div class="country-stat">
                    <span>Rata-rata Risiko</span>

                    <strong>
                        {{ number_format($globalSummary['average_risk_score'] ?? 0, 2, ',', '.') }}
                    </strong>

                    <small>
                        Skor rata-rata
                    </small>
                </div>

                <div class="country-stat">
                    <span>Risiko Tertinggi</span>

                    <strong>
                        {{ $globalSummary['highest_risk_country'] ?? 'Belum tersedia' }}
                    </strong>

                    <small>
                        Skor {{ number_format($globalSummary['highest_risk_score'] ?? 0, 2, ',', '.') }}
                    </small>
                </div>
            </div>
        </section>

        <section class="country-overview-card mt-4">
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
                        {{ $selectedCountry->official_name ?? '-' }}
                    </p>
                </div>
            </div>

            <div class="country-overview-stats">
                <div class="country-stat">
                    <span>Ibu Kota</span>

                    <strong>
                        {{ $selectedCountry->capital ?? '-' }}
                    </strong>

                    <small>
                        Pusat negara
                    </small>
                </div>

                <div class="country-stat">
                    <span>Wilayah</span>

                    <strong>
                        {{ $selectedCountry->region ?? '-' }}
                    </strong>

                    <small>
                        {{ $selectedCountry->subregion ?? '-' }}
                    </small>
                </div>

                <div class="country-stat">
                    <span>Mata Uang</span>

                    <strong>
                        {{ $selectedCountry->currency_code ?? '-' }}
                    </strong>

                    <small>
                        {{ $selectedCountry->currency_name ?? '-' }}
                    </small>
                </div>

                <div class="country-stat">
                    <span>Populasi</span>

                    <strong>
                        @if ($economicData['population']['value'] !== null)
                            {{ number_format($economicData['population']['value'], 0, ',', '.') }}
                        @else
                            {{ number_format($selectedCountry->population ?? 0, 0, ',', '.') }}
                        @endif
                    </strong>

                    <small>
                        Tahun {{ $economicData['population']['year'] ?? '-' }}
                    </small>
                </div>
            </div>
        </section>

        <section class="risk-card-grid mt-4">
            <article class="risk-card">
                <div class="risk-card-header">
                    <div>
                        <span class="risk-card-label">
                            Risiko Cuaca
                        </span>

                        <strong class="risk-card-score">
                            {{ number_format($weatherScore, 0, ',', '.') }}
                        </strong>

                        <span class="badge text-bg-{{ $weatherColor }}">
                            {{ $weatherLabel }}
                        </span>
                    </div>

                    <div class="risk-card-icon">
                        <i class="bi bi-cloud-lightning-rain"></i>
                    </div>
                </div>

                <div class="progress risk-progress">
                    <div
                        class="progress-bar bg-{{ $weatherColor }} js-progress-bar"
                        role="progressbar"
                        data-progress-width="{{ min(100, max(0, $weatherScore)) }}"
                    ></div>
                </div>
            </article>

            <article class="risk-card">
                <div class="risk-card-header">
                    <div>
                        <span class="risk-card-label">
                            Risiko Inflasi
                        </span>

                        <strong class="risk-card-score">
                            {{ number_format($inflationScore, 0, ',', '.') }}
                        </strong>

                        <span class="badge text-bg-{{ $inflationColor }}">
                            {{ $inflationLabel }}
                        </span>
                    </div>

                    <div class="risk-card-icon">
                        <i class="bi bi-graph-up-arrow"></i>
                    </div>
                </div>

                <div class="progress risk-progress">
                    <div
                        class="progress-bar bg-{{ $inflationColor }} js-progress-bar"
                        role="progressbar"
                        data-progress-width="{{ min(100, max(0, $inflationScore)) }}"
                    ></div>
                </div>
            </article>

            <article class="risk-card">
                <div class="risk-card-header">
                    <div>
                        <span class="risk-card-label">
                            Risiko Mata Uang
                        </span>

                        <strong class="risk-card-score">
                            {{ number_format($currencyScore, 0, ',', '.') }}
                        </strong>

                        <span class="badge text-bg-{{ $currencyColor }}">
                            {{ $currencyLabel }}
                        </span>
                    </div>

                    <div class="risk-card-icon">
                        <i class="bi bi-currency-exchange"></i>
                    </div>
                </div>

                <div class="progress risk-progress">
                    <div
                        class="progress-bar bg-{{ $currencyColor }} js-progress-bar"
                        role="progressbar"
                        data-progress-width="{{ min(100, max(0, $currencyScore)) }}"
                    ></div>
                </div>
            </article>

            <article class="risk-card">
                <div class="risk-card-header">
                    <div>
                        <span class="risk-card-label">
                            Risiko Berita
                        </span>

                        <strong class="risk-card-score">
                            {{ number_format($newsScore, 0, ',', '.') }}
                        </strong>

                        <span class="badge text-bg-{{ $newsColor }}">
                            {{ $newsLabel }}
                        </span>
                    </div>

                    <div class="risk-card-icon">
                        <i class="bi bi-newspaper"></i>
                    </div>
                </div>

                <div class="progress risk-progress">
                    <div
                        class="progress-bar bg-{{ $newsColor }} js-progress-bar"
                        role="progressbar"
                        data-progress-width="{{ min(100, max(0, $newsScore)) }}"
                    ></div>
                </div>
            </article>
        </section>

        <section class="risk-analysis-grid mt-4">
            <article class="analysis-card total-risk-card">
                <div class="analysis-card-header">
                    <div>
                        <span class="analysis-label">
                            Skor Risiko {{ $selectedCountry->name }}
                        </span>

                        <strong class="total-risk-score">
                            {{ number_format($totalScore, 2, ',', '.') }}
                        </strong>
                    </div>

                    <span class="badge text-bg-{{ $riskColor }} px-3 py-2">
                        {{ $riskLabel }}
                    </span>
                </div>

                <div class="progress total-risk-progress">
                    <div
                        class="progress-bar bg-{{ $riskColor }} js-progress-bar"
                        role="progressbar"
                        data-progress-width="{{ min(100, max(0, $totalScore)) }}"
                    ></div>
                </div>

                @if ($riskScoreAvailable)
                    <small class="text-muted">
                        Pembaruan:
                        {{ $latestRiskScore->calculated_at?->format('d M Y H:i') ?? '-' }}
                    </small>
                @else
                    <small class="text-muted">
                        Belum tersimpan
                    </small>
                @endif
            </article>

            <article class="analysis-card">
                <div class="analysis-heading">
                    <h3>
                        Komposisi Risiko
                    </h3>
                </div>

                <div class="weight-list">
                    <div class="weight-item">
                        <div class="weight-label">
                            <span>Cuaca</span>
                            <strong>30%</strong>
                        </div>

                        <div class="progress weight-progress">
                            <div
                                class="progress-bar js-progress-bar"
                                data-progress-width="30"
                            ></div>
                        </div>
                    </div>

                    <div class="weight-item">
                        <div class="weight-label">
                            <span>Inflasi</span>
                            <strong>20%</strong>
                        </div>

                        <div class="progress weight-progress">
                            <div
                                class="progress-bar js-progress-bar"
                                data-progress-width="20"
                            ></div>
                        </div>
                    </div>

                    <div class="weight-item">
                        <div class="weight-label">
                            <span>Mata Uang</span>
                            <strong>10%</strong>
                        </div>

                        <div class="progress weight-progress">
                            <div
                                class="progress-bar js-progress-bar"
                                data-progress-width="10"
                            ></div>
                        </div>
                    </div>

                    <div class="weight-item">
                        <div class="weight-label">
                            <span>Berita</span>
                            <strong>40%</strong>
                        </div>

                        <div class="progress weight-progress">
                            <div
                                class="progress-bar js-progress-bar"
                                data-progress-width="40"
                            ></div>
                        </div>
                    </div>
                </div>
            </article>
        </section>

        <section class="risk-analysis-grid mt-4">
            <article class="analysis-card">
                <div class="analysis-heading">
                    <h3>
                        Status Data Cuaca
                    </h3>
                </div>

                <div class="country-overview-stats">
                    <div class="country-stat">
                        <span>Temperatur</span>

                        <strong>
                            @if ($weatherAvailable)
                                {{ number_format($weatherData->temperature, 1, ',', '.') }}°C
                            @else
                                Belum tersedia
                            @endif
                        </strong>

                        <small>
                            Suhu saat ini
                        </small>
                    </div>

                    <div class="country-stat">
                        <span>Curah Hujan</span>

                        <strong>
                            @if ($weatherAvailable)
                                {{ number_format($weatherData->precipitation, 2, ',', '.') }} mm
                            @else
                                Belum tersedia
                            @endif
                        </strong>

                        <small>
                            Presipitasi
                        </small>
                    </div>

                    <div class="country-stat">
                        <span>Kecepatan Angin</span>

                        <strong>
                            @if ($weatherAvailable)
                                {{ number_format($weatherData->wind_speed, 1, ',', '.') }} km/jam
                            @else
                                Belum tersedia
                            @endif
                        </strong>

                        <small>
                            Angin 10 meter
                        </small>
                    </div>
                </div>
            </article>

            <article class="analysis-card">
                <div class="analysis-heading">
                    <h3>
                        Status Mata Uang dan Berita
                    </h3>
                </div>

                <div class="country-overview-stats">
                    <div class="country-stat">
                        <span>Kurs USD</span>

                        <strong>
                            @if ($currencyAvailable)
                                1 USD =
                                {{ number_format($exchangeRate->rate, 4, ',', '.') }}
                                {{ $exchangeRate->target_currency }}
                            @else
                                Belum tersedia
                            @endif
                        </strong>

                        <small>
                            Nilai tukar
                        </small>
                    </div>

                    <div class="country-stat">
                        <span>Artikel Berita</span>

                        <strong>
                            {{ number_format($newsSummary['total_articles'] ?? 0, 0, ',', '.') }}
                        </strong>

                        <small>
                            Artikel
                        </small>
                    </div>

                    <div class="country-stat">
                        <span>Berita Negatif</span>

                        <strong>
                            {{ number_format($newsSummary['negative_count'] ?? 0, 0, ',', '.') }}
                        </strong>

                        <small>
                            Risiko berita
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
                        Visualisasi Tinjauan Global
                    </h3>
                </div>

                <div class="row g-3">
                    <div class="col-12 col-xl-6">
                        <div class="border rounded-4 p-3 h-100">
                            <h5 class="fw-bold mb-3">
                                Grafik Komponen Risiko
                            </h5>

                            <div style="height: 190px;">
                                <canvas id="dashboardRiskComponentChart"></canvas>
                            </div>
                        </div>
                    </div>

                    <div class="col-12 col-xl-6">
                        <div class="border rounded-4 p-3 h-100">
                            <h5 class="fw-bold mb-3">
                                Grafik Indikator Ekonomi
                            </h5>

                            <div style="height: 190px;">
                                <canvas id="dashboardEconomicChart"></canvas>
                            </div>
                        </div>
                    </div>

                    <div class="col-12">
                        <div class="border rounded-4 p-3 h-100">
                            <h5 class="fw-bold mb-3">
                                Grafik Risk Score Antarnegara
                            </h5>

                            <div style="height: 200px;">
                                <canvas id="dashboardGlobalRiskChart"></canvas>
                            </div>
                        </div>
                    </div>
                </div>
            </article>
        </section>

        <section class="economic-section mt-4">
            <div class="section-heading">
                <div class="page-eyebrow">
                    Indikator Ekonomi
                </div>

                <h2>
                    Data Ekonomi {{ $selectedCountry->name }}
                </h2>
            </div>

            @if ($hasEconomicData)
                <div class="economic-card-grid">
                    <article class="economic-card">
                        <div class="economic-card-main">
                            <div>
                                <span class="economic-card-label">
                                    Produk Domestik Bruto
                                </span>

                                <strong class="economic-card-value">
                                    @if ($economicData['gdp']['value'] !== null)
                                        US$
                                        {{ number_format($economicData['gdp']['value'] / 1000000000000, 2, ',', '.') }}
                                        T
                                    @else
                                        -
                                    @endif
                                </strong>
                            </div>

                            <div class="economic-card-icon">
                                <i class="bi bi-bank"></i>
                            </div>
                        </div>

                        <div class="economic-card-footer">
                            <span>Tahun</span>

                            <strong>
                                {{ $economicData['gdp']['year'] ?? '-' }}
                            </strong>
                        </div>
                    </article>

                    <article class="economic-card">
                        <div class="economic-card-main">
                            <div>
                                <span class="economic-card-label">
                                    Inflasi Aktual
                                </span>

                                <strong class="economic-card-value">
                                    @if ($economicData['inflation']['value'] !== null)
                                        {{ number_format($economicData['inflation']['value'], 2, ',', '.') }}%
                                    @else
                                        -
                                    @endif
                                </strong>
                            </div>

                            <div class="economic-card-icon">
                                <i class="bi bi-graph-up"></i>
                            </div>
                        </div>

                        <div class="economic-card-footer">
                            <span>Tahun</span>

                            <strong>
                                {{ $economicData['inflation']['year'] ?? '-' }}
                            </strong>
                        </div>
                    </article>

                    <article class="economic-card">
                        <div class="economic-card-main">
                            <div>
                                <span class="economic-card-label">
                                    Ekspor Barang & Jasa
                                </span>

                                <strong class="economic-card-value">
                                    @if ($economicData['exports']['value'] !== null)
                                        US$
                                        {{ number_format($economicData['exports']['value'] / 1000000000, 2, ',', '.') }}
                                        B
                                    @else
                                        -
                                    @endif
                                </strong>
                            </div>

                            <div class="economic-card-icon">
                                <i class="bi bi-box-arrow-up-right"></i>
                            </div>
                        </div>

                        <div class="economic-card-footer">
                            <span>Tahun</span>

                            <strong>
                                {{ $economicData['exports']['year'] ?? '-' }}
                            </strong>
                        </div>
                    </article>

                    <article class="economic-card">
                        <div class="economic-card-main">
                            <div>
                                <span class="economic-card-label">
                                    Impor Barang & Jasa
                                </span>

                                <strong class="economic-card-value">
                                    @if ($economicData['imports']['value'] !== null)
                                        US$
                                        {{ number_format($economicData['imports']['value'] / 1000000000, 2, ',', '.') }}
                                        B
                                    @else
                                        -
                                    @endif
                                </strong>
                            </div>

                            <div class="economic-card-icon">
                                <i class="bi bi-box-arrow-in-down-right"></i>
                            </div>
                        </div>

                        <div class="economic-card-footer">
                            <span>Tahun</span>

                            <strong>
                                {{ $economicData['imports']['year'] ?? '-' }}
                            </strong>
                        </div>
                    </article>
                </div>
            @else
                <div class="economic-empty-state">
                    <i class="bi bi-exclamation-triangle"></i>

                    <div>
                        <h3>
                            Data ekonomi belum tersedia
                        </h3>

                        <p>
                            Buka Pemantau Negara untuk sinkronisasi data.
                        </p>
                    </div>
                </div>
            @endif
        </section>

        <section class="country-overview-card mt-4">
            <div class="country-overview-main">
                <div class="country-identity">
                    <span class="country-overview-label">
                        Aksi Lanjutan
                    </span>

                    <h2>
                        Sinkronisasi Data Negara
                    </h2>
                </div>
            </div>

            <a
                href="{{ route('countries.index', ['country' => $selectedCountry->iso3_code]) }}"
                class="btn btn-primary mt-3"
            >
                <i class="bi bi-globe2 me-1"></i>
                Buka Pemantau Negara
            </a>
        </section>
    </div>
@endsection

@push('scripts')
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

    <script
        id="dashboardChartData"
        type="application/json"
    >{!! json_encode($dashboardChartData ?? [], JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT) !!}</script>

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

            var chartDataElement = document.getElementById('dashboardChartData');
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
                                borderWidth: 1,
                                borderRadius: 6,
                                maxBarThickness: 32
                            }
                        ]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        layout: {
                            padding: 0
                        },
                        scales: {
                            x: {
                                ticks: {
                                    font: {
                                        size: 10
                                    }
                                },
                                grid: {
                                    display: false
                                }
                            },
                            y: {
                                beginAtZero: true,
                                ticks: {
                                    font: {
                                        size: 10
                                    }
                                }
                            }
                        },
                        plugins: {
                            legend: {
                                labels: {
                                    boxWidth: 12,
                                    font: {
                                        size: 11
                                    }
                                }
                            }
                        }
                    }
                });
            }

            createBarChart(
                'dashboardRiskComponentChart',
                'Komponen Risiko',
                getChartLabels('riskComponents'),
                getChartValues('riskComponents')
            );

            createBarChart(
                'dashboardEconomicChart',
                'Indikator Ekonomi',
                getChartLabels('economic'),
                getChartValues('economic')
            );

            createBarChart(
                'dashboardGlobalRiskChart',
                'Risk Score Negara',
                getChartLabels('globalRisk'),
                getChartValues('globalRisk')
            );
        });
    </script>
@endpush
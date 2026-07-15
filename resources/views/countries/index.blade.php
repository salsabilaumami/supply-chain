@extends('layouts.app')

@section('title', 'Pemantau Negara')

@section('content')
<div class="dashboard-page">
    <section class="dashboard-header">
        <div class="dashboard-heading">
            <div class="page-eyebrow">
                GLOBAL COUNTRY DASHBOARD
            </div>

            <h1 class="page-title">
                Pemantau Negara
            </h1>

            <p class="page-description">
                Pilih negara untuk melihat profil, ekonomi, cuaca, kurs, berita,
                dan Risk Score rantai pasok.
            </p>
        </div>

        <form
            method="GET"
            action="{{ route('countries.index') }}"
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
                >
                    @foreach ($countries as $country)
                        <option
                            value="{{ $country->iso3_code }}"
                            @selected($selectedCountry && $selectedCountry->id === $country->id)
                        >
                            {{ $country->name }} ({{ $country->iso3_code }})
                        </option>
                    @endforeach
                </select>

                <button
                    type="submit"
                    class="btn btn-primary"
                >
                    Tampilkan
                </button>
            </div>
        </form>
    </section>

    @if (session('error'))
        <div
            class="alert alert-danger border-0 shadow-sm mb-4"
            role="alert"
        >
            <i class="bi bi-exclamation-triangle me-2"></i>
            {{ session('error') }}
        </div>
    @endif

    @if ($countries->isEmpty())
        <div class="alert alert-warning border-0 shadow-sm">
            <i class="bi bi-exclamation-triangle me-2"></i>
            Data negara belum tersedia. Jalankan seeder terlebih dahulu.
        </div>
    @elseif ($selectedCountry)
        @php
            $economicChartLabels = [
                'GDP (US$ T)',
                'Ekspor (US$ B)',
                'Impor (US$ B)',
                'Inflasi (%)',
                'Populasi (Juta)',
            ];

            $economicChartValues = [
                round((float) ($economicSummary['gdp']['value'] ?? 0) / 1000000000000, 2),
                round((float) ($economicSummary['exports']['value'] ?? 0) / 1000000000, 2),
                round((float) ($economicSummary['imports']['value'] ?? 0) / 1000000000, 2),
                round((float) ($economicSummary['inflation']['value'] ?? 0), 2),
                round((float) ($economicSummary['population']['value'] ?? 0) / 1000000, 2),
            ];

            $weatherChartLabels = [
                'Temperatur °C',
                'Curah Hujan mm',
                'Angin km/jam',
                'Risiko Cuaca',
            ];

            $weatherChartValues = [
                round((float) ($weatherSummary['temperature'] ?? 0), 2),
                round((float) ($weatherSummary['precipitation'] ?? 0), 2),
                round((float) ($weatherSummary['wind_speed'] ?? 0), 2),
                round((float) ($weatherSummary['weather_risk'] ?? 0), 2),
            ];

            $riskComponentLabels = collect($riskSummary['components'] ?? [])
                ->pluck('component_label')
                ->values()
                ->all();

            $riskComponentValues = collect($riskSummary['components'] ?? [])
                ->pluck('weighted_score')
                ->map(fn ($value) => round((float) $value, 2))
                ->values()
                ->all();

            if (empty($riskComponentLabels)) {
                $riskComponentLabels = ['Belum dihitung'];
                $riskComponentValues = [0];
            }

            $sentimentChartLabels = [
                'Positif',
                'Netral',
                'Negatif',
            ];

            $sentimentChartValues = [
                (int) ($newsSummary['positive_count'] ?? 0),
                (int) ($newsSummary['neutral_count'] ?? 0),
                (int) ($newsSummary['negative_count'] ?? 0),
            ];

            $summaryRiskLabels = [
                'Risiko Kurs',
                'Risiko Berita',
                'Risk Score Total',
            ];

            $summaryRiskValues = [
                round((float) ($currencySummary['currency_risk'] ?? 0), 2),
                round((float) ($newsSummary['average_risk_score'] ?? 0), 2),
                round((float) ($riskSummary['total_score'] ?? 0), 2),
            ];

            $dashboardChartData = [
                'economic' => [
                    'labels' => $economicChartLabels,
                    'values' => $economicChartValues,
                ],
                'weather' => [
                    'labels' => $weatherChartLabels,
                    'values' => $weatherChartValues,
                ],
                'riskComponents' => [
                    'labels' => $riskComponentLabels,
                    'values' => $riskComponentValues,
                ],
                'sentiment' => [
                    'labels' => $sentimentChartLabels,
                    'values' => $sentimentChartValues,
                ],
                'summaryRisk' => [
                    'labels' => $summaryRiskLabels,
                    'values' => $summaryRiskValues,
                ],
            ];
        @endphp

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
                        {{ $selectedCountry->official_name ?? 'Nama resmi belum tersedia' }}
                    </p>
                </div>
            </div>

            <div class="country-overview-stats">
                <div class="country-stat">
                    <span>Kode ISO</span>

                    <strong>
                        {{ $selectedCountry->iso2_code }} / {{ $selectedCountry->iso3_code }}
                    </strong>

                    <small>
                        Kode negara
                    </small>
                </div>

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
            </div>
        </section>

        <section class="country-overview-card mt-4">
            <div class="country-overview-main">
                <div class="country-identity">
                    <span class="country-overview-label">
                        Sinkronisasi
                    </span>

                    <h2>
                        Sinkronkan Data Negara
                    </h2>

                    <p>
                        Perbarui ekonomi, cuaca, kurs, berita, dan Risk Score.
                    </p>
                </div>
            </div>

            <form
                method="POST"
                action="{{ route('countries.sync-all') }}"
                class="mt-3"
            >
                @csrf

                <input
                    type="hidden"
                    name="country"
                    value="{{ $selectedCountry->iso3_code }}"
                >

                <button
                    type="submit"
                    class="btn btn-success btn-lg"
                >
                    <i class="bi bi-arrow-repeat me-1"></i>
                    Sinkronkan Semua Data
                </button>
            </form>
        </section>

        <section class="risk-analysis-grid mt-4">
            <article class="analysis-card">
                <div class="analysis-heading">
                    <h3>
                        Risk Scoring Engine
                    </h3>

                    <p>
                        Risiko dihitung dari cuaca, inflasi, kurs, dan berita.
                    </p>
                </div>

                <form
                    method="POST"
                    action="{{ route('countries.calculate-risk') }}"
                    class="mb-4"
                >
                    @csrf

                    <input
                        type="hidden"
                        name="country"
                        value="{{ $selectedCountry->iso3_code }}"
                    >

                    <button
                        type="submit"
                        class="btn btn-danger"
                    >
                        <i class="bi bi-shield-exclamation me-1"></i>
                        Hitung Risk Score
                    </button>
                </form>

                @php
                    $riskLevel = $riskSummary['risk_level'] ?? null;

                    $riskBadgeClass = match ($riskLevel) {
                        'critical' => 'bg-danger',
                        'high' => 'bg-warning text-dark',
                        'moderate' => 'bg-info text-dark',
                        'low' => 'bg-success',
                        default => 'bg-secondary',
                    };
                @endphp

                <div class="table-responsive">
                    <table class="table align-middle mb-0">
                        <tbody>
                            <tr>
                                <th style="width: 45%;">
                                    Total Risk Score
                                </th>

                                <td>
                                    <div class="fw-bold fs-4">
                                        {{ $riskSummary['display_total_score'] ?? 'Belum dihitung' }}
                                    </div>

                                    <small class="text-muted">
                                        Skor risiko
                                    </small>
                                </td>
                            </tr>

                            <tr>
                                <th>
                                    Level Risiko
                                </th>

                                <td>
                                    <span class="badge {{ $riskBadgeClass }} px-3 py-2">
                                        {{ $riskSummary['risk_level_label'] ?? 'Belum dihitung' }}
                                    </span>
                                </td>
                            </tr>

                            <tr>
                                <th>
                                    Dihitung Pada
                                </th>

                                <td>
                                    <strong>
                                        {{ $riskSummary['calculated_at'] ?? '-' }}
                                    </strong>

                                    <br>

                                    <small class="text-muted">
                                        Kalkulasi terakhir
                                    </small>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </article>

            <article class="analysis-card">
                <div class="analysis-heading">
                    <h3>
                        Intelijen Berita
                    </h3>

                    <p>
                        Berita dianalisis untuk membaca potensi risiko.
                    </p>
                </div>

                <form
                    method="POST"
                    action="{{ route('countries.sync-news') }}"
                    class="mb-4"
                >
                    @csrf

                    <input
                        type="hidden"
                        name="country"
                        value="{{ $selectedCountry->iso3_code }}"
                    >

                    <button
                        type="submit"
                        class="btn btn-primary"
                    >
                        <i class="bi bi-newspaper me-1"></i>
                        Sinkronkan Berita
                    </button>
                </form>

                <div class="country-overview-stats">
                    <div class="country-stat">
                        <span>Artikel</span>

                        <strong>
                            {{ $newsSummary['total_articles'] ?? 0 }}
                        </strong>

                        <small>
                            Berita tersimpan
                        </small>
                    </div>

                    <div class="country-stat">
                        <span>Risiko Berita</span>

                        <strong>
                            {{ $newsSummary['display_average_risk_score'] ?? 'Belum tersedia' }}
                        </strong>

                        <small>
                            {{ $newsSummary['risk_label'] ?? 'Belum tersedia' }}
                        </small>
                    </div>

                    <div class="country-stat">
                        <span>Negatif</span>

                        <strong>
                            {{ $newsSummary['negative_count'] ?? 0 }}
                        </strong>

                        <small>
                            Artikel berisiko
                        </small>
                    </div>

                    <div class="country-stat">
                        <span>Positif</span>

                        <strong>
                            {{ $newsSummary['positive_count'] ?? 0 }}
                        </strong>

                        <small>
                            Artikel positif
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
                        Visualisasi Dashboard
                    </h3>

                    <p>
                        Grafik ekonomi, cuaca, berita, kurs, dan Risk Score.
                    </p>
                </div>

                <div class="row g-4">
                    <div class="col-12 col-xl-6">
                        <div class="border rounded-4 p-3 h-100">
                            <h5 class="fw-bold mb-3">
                                Grafik Indikator Ekonomi
                            </h5>

                            <div style="height: 180px;">
                                <canvas id="economicChart"></canvas>
                            </div>
                        </div>
                    </div>

                    <div class="col-12 col-xl-6">
                        <div class="border rounded-4 p-3 h-100">
                            <h5 class="fw-bold mb-3">
                                Grafik Cuaca dan Risiko
                            </h5>

                            <div style="height: 180px;">
                                <canvas id="weatherChart"></canvas>
                            </div>
                        </div>
                    </div>

                    <div class="col-12 col-xl-6">
                        <div class="border rounded-4 p-3 h-100">
                            <h5 class="fw-bold mb-3">
                                Grafik Komponen Risk Score
                            </h5>

                            <div style="height: 180px;">
                                <canvas id="riskComponentChart"></canvas>
                            </div>
                        </div>
                    </div>

                    <div class="col-12 col-xl-6">
                        <div class="border rounded-4 p-3 h-100">
                            <h5 class="fw-bold mb-3">
                                Grafik Sentimen Berita
                            </h5>

                            <div style="height: 180px;">
                                <canvas id="sentimentChart"></canvas>
                            </div>
                        </div>
                    </div>

                    <div class="col-12">
                        <div class="border rounded-4 p-3 h-100">
                            <h5 class="fw-bold mb-3">
                                Grafik Ringkasan Risiko
                            </h5>

                            <div style="height: 180px;">
                                <canvas id="summaryRiskChart"></canvas>
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
                        Daftar Berita Terkini
                    </h3>

                    <p>
                        Artikel terkait ekonomi, logistik, perdagangan, dan rantai pasok.
                    </p>
                </div>

                <div class="d-flex flex-column gap-3">
                    @forelse (($newsSummary['items'] ?? []) as $news)
                        @php
                            $sentimentBadge = match ($news['sentiment']) {
                                'positive' => 'bg-success',
                                'negative' => 'bg-danger',
                                default => 'bg-secondary',
                            };
                        @endphp

                        <div class="border rounded-4 p-3 shadow-sm bg-white">
                            <div class="d-flex justify-content-between align-items-start gap-3 flex-wrap">
                                <div style="max-width: 760px;">
                                    <h5 class="mb-2 fw-bold">
                                        {{ $news['title'] }}
                                    </h5>

                                    @if ($news['description'])
                                        <p class="mb-2 text-muted">
                                            {{ \Illuminate\Support\Str::limit($news['description'], 180) }}
                                        </p>
                                    @endif

                                    <div class="d-flex flex-wrap gap-3 text-muted small">
                                        <span>
                                            <i class="bi bi-building me-1"></i>
                                            {{ $news['source_name'] ?? 'Sumber tidak tersedia' }}
                                        </span>

                                        <span>
                                            <i class="bi bi-calendar-event me-1"></i>
                                            {{ $news['published_at'] ?? '-' }}
                                        </span>
                                    </div>
                                </div>

                                <div class="text-end">
                                    <span class="badge {{ $sentimentBadge }} px-3 py-2 mb-2">
                                        {{ $news['sentiment_label'] }}
                                    </span>

                                    <div class="fw-bold">
                                        Risk: {{ number_format($news['risk_score'], 2, ',', '.') }}
                                    </div>

                                    @if ($news['url'])
                                        <a
                                            href="{{ $news['url'] }}"
                                            target="_blank"
                                            rel="noopener"
                                            class="btn btn-sm btn-outline-primary mt-2"
                                        >
                                            Buka Berita
                                        </a>
                                    @endif
                                </div>
                            </div>
                        </div>
                    @empty
                        <div class="alert alert-warning border-0 shadow-sm mb-0">
                            <i class="bi bi-info-circle me-2"></i>
                            Berita belum tersedia. Klik tombol
                            <strong>Sinkronkan Berita</strong>.
                        </div>
                    @endforelse
                </div>
            </article>
        </section>

        <section class="risk-analysis-grid mt-4">
            <article class="analysis-card">
                <div class="analysis-heading">
                    <h3>
                        Indikator Ekonomi
                    </h3>

                    <p>
                        Data ekonomi terbaru negara terpilih.
                    </p>
                </div>

                <form
                    method="POST"
                    action="{{ route('countries.sync-economic') }}"
                    class="mb-4"
                >
                    @csrf

                    <input
                        type="hidden"
                        name="country"
                        value="{{ $selectedCountry->iso3_code }}"
                    >

                    <button
                        type="submit"
                        class="btn btn-primary"
                    >
                        <i class="bi bi-arrow-repeat me-1"></i>
                        Sinkronkan Data Ekonomi
                    </button>
                </form>

                <div class="country-overview-stats">
                    @foreach ($economicSummary as $indicator)
                        <div class="country-stat">
                            <span>
                                {{ $indicator['label'] }}
                            </span>

                            <strong>
                                {{ $indicator['display_value'] }}
                            </strong>

                            <small>
                                @if ($indicator['year'])
                                    Tahun {{ $indicator['year'] }}
                                @else
                                    Klik sinkronkan data
                                @endif
                            </small>
                        </div>
                    @endforeach
                </div>
            </article>

            <article class="analysis-card">
                <div class="analysis-heading">
                    <h3>
                        Cuaca Saat Ini
                    </h3>

                    <p>
                        Data cuaca untuk membaca potensi gangguan pengiriman.
                    </p>
                </div>

                <form
                    method="POST"
                    action="{{ route('countries.sync-weather') }}"
                    class="mb-4"
                >
                    @csrf

                    <input
                        type="hidden"
                        name="country"
                        value="{{ $selectedCountry->iso3_code }}"
                    >

                    <button
                        type="submit"
                        class="btn btn-primary"
                    >
                        <i class="bi bi-cloud-arrow-down me-1"></i>
                        Sinkronkan Cuaca
                    </button>
                </form>

                <div class="country-overview-stats">
                    <div class="country-stat">
                        <span>Temperatur</span>

                        <strong>
                            @if ($weatherSummary && $weatherSummary['available'])
                                {{ number_format($weatherSummary['temperature'], 1, ',', '.') }}°C
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
                            @if ($weatherSummary && $weatherSummary['available'])
                                {{ number_format($weatherSummary['precipitation'], 2, ',', '.') }} mm
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
                            @if ($weatherSummary && $weatherSummary['available'])
                                {{ number_format($weatherSummary['wind_speed'], 1, ',', '.') }} km/jam
                            @else
                                Belum tersedia
                            @endif
                        </strong>

                        <small>
                            Angin 10 meter
                        </small>
                    </div>

                    <div class="country-stat">
                        <span>Risiko Cuaca</span>

                        <strong>
                            @if ($weatherSummary && $weatherSummary['available'])
                                {{ number_format($weatherSummary['weather_risk'], 1, ',', '.') }}
                            @else
                                Belum tersedia
                            @endif
                        </strong>

                        <small>
                            {{ $weatherSummary['risk_label'] ?? 'Klik sinkronkan cuaca' }}
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
                        Kurs Mata Uang
                    </h3>

                    <p>
                        Data kurs untuk melihat dampak perubahan mata uang.
                    </p>
                </div>

                <form
                    method="POST"
                    action="{{ route('countries.sync-currency') }}"
                    class="mb-4"
                >
                    @csrf

                    <input
                        type="hidden"
                        name="country"
                        value="{{ $selectedCountry->iso3_code }}"
                    >

                    <button
                        type="submit"
                        class="btn btn-primary"
                    >
                        <i class="bi bi-currency-exchange me-1"></i>
                        Sinkronkan Kurs
                    </button>
                </form>

                <div class="country-overview-stats">
                    <div class="country-stat">
                        <span>Kurs</span>

                        <strong>
                            {{ $currencySummary['display_rate'] ?? 'Belum tersedia' }}
                        </strong>

                        <small>
                            Basis USD
                        </small>
                    </div>

                    <div class="country-stat">
                        <span>Perubahan</span>

                        <strong>
                            {{ $currencySummary['display_change'] ?? 'Belum tersedia' }}
                        </strong>

                        <small>
                            Perubahan kurs
                        </small>
                    </div>

                    <div class="country-stat">
                        <span>Risiko Kurs</span>

                        <strong>
                            @if ($currencySummary && $currencySummary['available'])
                                {{ number_format($currencySummary['currency_risk'], 1, ',', '.') }}
                            @else
                                Belum tersedia
                            @endif
                        </strong>

                        <small>
                            {{ $currencySummary['risk_label'] ?? 'Klik sinkronkan kurs' }}
                        </small>
                    </div>
                </div>
            </article>
        </section>
    @endif
</div>
@endsection

@push('scripts')
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

    <script
        id="countryChartData"
        type="application/json"
    >{!! json_encode($dashboardChartData ?? [], JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT) !!}</script>

    <script>
        document.addEventListener('DOMContentLoaded', function () {
            if (typeof Chart === 'undefined') {
                return;
            }

            var chartDataElement = document.getElementById('countryChartData');
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
                            padding: {
                                top: 6,
                                right: 6,
                                bottom: 0,
                                left: 0
                            }
                        },
                        scales: {
                            x: {
                                ticks: {
                                    autoSkip: true,
                                    maxRotation: 35,
                                    minRotation: 0,
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
                                position: 'top',
                                labels: {
                                    boxWidth: 12,
                                    font: {
                                        size: 11
                                    }
                                }
                            },
                            tooltip: {
                                callbacks: {
                                    label: function (context) {
                                        return label + ': ' + context.parsed.y;
                                    }
                                }
                            }
                        }
                    }
                });
            }

            createBarChart(
                'economicChart',
                'Indikator Ekonomi',
                getChartLabels('economic'),
                getChartValues('economic')
            );

            createBarChart(
                'weatherChart',
                'Cuaca dan Risiko',
                getChartLabels('weather'),
                getChartValues('weather')
            );

            createBarChart(
                'riskComponentChart',
                'Skor Tertimbang',
                getChartLabels('riskComponents'),
                getChartValues('riskComponents')
            );

            createBarChart(
                'sentimentChart',
                'Jumlah Artikel',
                getChartLabels('sentiment'),
                getChartValues('sentiment')
            );

            createBarChart(
                'summaryRiskChart',
                'Ringkasan Risiko',
                getChartLabels('summaryRisk'),
                getChartValues('summaryRisk')
            );
        });
    </script>
@endpush
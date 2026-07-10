@extends('layouts.app')

@section('title', 'Pemantau Cuaca')

@section('content')
    @php
        $riskBadgeClass = match (true) {
            ($weatherRisk ?? 0) >= 75 => 'bg-danger',
            ($weatherRisk ?? 0) >= 50 => 'bg-warning text-dark',
            ($weatherRisk ?? 0) >= 25 => 'bg-info text-dark',
            default => 'bg-success',
        };
    @endphp

    <div class="dashboard-page">
        <section class="dashboard-header">
            <div class="dashboard-heading">
                <div class="page-eyebrow">
                    GLOBAL WEATHER MONITORING
                </div>

                <h1 class="page-title">
                    Pemantau Cuaca
                </h1>

                <p class="page-description">
                    Pantau suhu, curah hujan, angin, dan risiko cuaca negara terpilih.
                </p>
            </div>

            <form
                method="GET"
                action="{{ route('weather.index') }}"
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
                        {{ $selectedCountry->capital ?? '-' }}
                        —
                        {{ $selectedCountry->region ?? '-' }}
                    </p>
                </div>
            </div>

            <div class="country-overview-stats">
                <div class="country-stat">
                    <span>Temperatur</span>

                    <strong>
                        @if ($weatherAvailable)
                            {{ number_format($temperature, 1, ',', '.') }}°C
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
                            {{ number_format($precipitation, 2, ',', '.') }} mm
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
                            {{ number_format($windSpeed, 1, ',', '.') }} km/jam
                        @else
                            Belum tersedia
                        @endif
                    </strong>

                    <small>
                        Angin 10 meter
                    </small>
                </div>

                <div class="country-stat">
                    <span>Pembaruan Terakhir</span>

                    <strong>
                        {{ $lastUpdate ?? 'Belum tersedia' }}
                    </strong>

                    <small>
                        Open-Meteo API
                    </small>
                </div>
            </div>
        </section>

        <section class="risk-analysis-grid mt-4">
            <article class="analysis-card total-risk-card">
                <div class="analysis-card-header">
                    <div>
                        <span class="analysis-label">
                            Skor Risiko Cuaca
                        </span>

                        <strong class="total-risk-score">
                            {{ number_format($weatherRisk ?? 0, 2, ',', '.') }}
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
                        data-progress-width="{{ min(100, max(0, $weatherRisk ?? 0)) }}"
                    ></div>
                </div>

                <p class="analysis-description">
                    Risiko cuaca dihitung dari kombinasi curah hujan, kecepatan angin,
                    dan kode cuaca. Nilai ini membantu membaca potensi gangguan pengiriman.
                </p>
            </article>

            <article class="analysis-card">
                <div class="analysis-heading">
                    <h3>
                        Status Open-Meteo API
                    </h3>

                    <p>
                        Status Cuaca
                    </p>
                </div>

                <div class="country-overview-stats">
                    <div class="country-stat">
                        <span>Kode Cuaca</span>

                        <strong>
                            {{ $weatherCode ?? 0 }}
                        </strong>

                        <small>
                            {{ $weatherDescription ?? 'Belum tersedia' }}
                        </small>
                    </div>

                    <div class="country-stat">
                        <span>Koordinat</span>

                        <strong>
                            {{ $selectedCountry->latitude ?? '-' }},
                            {{ $selectedCountry->longitude ?? '-' }}
                        </strong>

                        <small>
                           Koordinat negara
                        </small>
                    </div>

                    <div class="country-stat">
                        <span>Jumlah Riwayat</span>

                        <strong>
                            {{ $history->count() }}
                        </strong>

                        <small>
                             Riwayat tersimpan
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
                        Grafik Pemantauan Cuaca
                    </h3>

                <div class="row g-4">
                    <div class="col-12 col-xl-6">
                        <div class="border rounded-4 p-3 h-100">
                            <h5 class="fw-bold mb-3">
                                Grafik Temperatur
                            </h5>

                            <div style="height: 180px;">
                                <canvas id="weatherTemperatureChart"></canvas>
                            </div>
                        </div>
                    </div>

                    <div class="col-12 col-xl-6">
                        <div class="border rounded-4 p-3 h-100">
                            <h5 class="fw-bold mb-3">
                                Grafik Curah Hujan
                            </h5>

                            <div style="height: 180px;">
                                <canvas id="weatherPrecipitationChart"></canvas>
                            </div>
                        </div>
                    </div>

                    <div class="col-12 col-xl-6">
                        <div class="border rounded-4 p-3 h-100">
                            <h5 class="fw-bold mb-3">
                                Grafik Kecepatan Angin
                            </h5>

                            <div style="height: 180px;">
                                <canvas id="weatherWindChart"></canvas>
                            </div>
                        </div>
                    </div>

                    <div class="col-12 col-xl-6">
                        <div class="border rounded-4 p-3 h-100">
                            <h5 class="fw-bold mb-3">
                                Grafik Risiko Cuaca
                            </h5>

                            <div style="height: 180px;">
                                <canvas id="weatherRiskChart"></canvas>
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
                        Riwayat Cuaca Terakhir
                    </h3>

                    <p>
                        Daftar data cuaca terbaru dari Open-Meteo untuk negara yang dipilih.
                    </p>
                </div>

                <div class="table-responsive">
                    <table class="table align-middle mb-0">
                        <thead>
                            <tr>
                                <th>Waktu</th>
                                <th>Temperatur</th>
                                <th>Curah Hujan</th>
                                <th>Angin</th>
                                <th>Kode Cuaca</th>
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
                                        {{ number_format((float) $item->temperature, 1, ',', '.') }}°C
                                    </td>

                                    <td>
                                        {{ number_format((float) $item->precipitation, 2, ',', '.') }} mm
                                    </td>

                                    <td>
                                        {{ number_format((float) $item->wind_speed, 1, ',', '.') }} km/jam
                                    </td>

                                    <td>
                                        {{ $item->weather_code }}
                                    </td>

                                    <td>
                                        {{ number_format((float) $item->weather_risk, 2, ',', '.') }}
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td
                                        colspan="6"
                                        class="text-center text-muted py-4"
                                    >
                                        Data cuaca belum tersedia.
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
                        API Pemantau Cuaca
                    </span>

                    <h2>
                        Endpoint JSON Weather
                    </h2>

                </div>
            </div>

            <a
                href="{{ route('api.weather.show', ['country' => $selectedCountry->iso3_code]) }}"
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
        id="weatherChartData"
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

            var chartDataElement = document.getElementById('weatherChartData');
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
                'weatherTemperatureChart',
                'Temperatur',
                getChartLabels('temperature'),
                getChartValues('temperature')
            );

            createBarChart(
                'weatherPrecipitationChart',
                'Curah Hujan',
                getChartLabels('precipitation'),
                getChartValues('precipitation')
            );

            createBarChart(
                'weatherWindChart',
                'Kecepatan Angin',
                getChartLabels('wind'),
                getChartValues('wind')
            );

            createBarChart(
                'weatherRiskChart',
                'Risiko Cuaca',
                getChartLabels('risk'),
                getChartValues('risk')
            );
        });
    </script>
@endpush
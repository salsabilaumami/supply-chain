@extends('layouts.app')

@section('title', 'Pemantau Cuaca')

@section('content')
@php
    $weatherRisk = $weather ? (float) $weather->weather_risk : 0;
    $weatherRiskWidth = min(100, max(0, $weatherRisk));

    if ($weatherRisk <= 30) {
        $riskLevel = 'Low Risk';
        $riskBadgeClass = 'text-bg-success';
    } elseif ($weatherRisk <= 60) {
        $riskLevel = 'Medium Risk';
        $riskBadgeClass = 'text-bg-warning';
    } else {
        $riskLevel = 'High Risk';
        $riskBadgeClass = 'text-bg-danger';
    }

    $weatherChartHistory = $history->reverse()->values();

    $weatherChartLabels = $weatherChartHistory
        ->map(function ($item) {
            return $item->recorded_at
                ? $item->recorded_at->format('d M H:i')
                : '';
        })
        ->toArray();

    $temperatureData = $weatherChartHistory
        ->map(function ($item) {
            return (float) $item->temperature;
        })
        ->toArray();

    $precipitationData = $weatherChartHistory
        ->map(function ($item) {
            return (float) $item->precipitation;
        })
        ->toArray();

    $windData = $weatherChartHistory
        ->map(function ($item) {
            return (float) $item->wind_speed;
        })
        ->toArray();
@endphp

<link
    rel="stylesheet"
    href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css"
/>

<div class="dashboard-page">
    <section class="dashboard-header">
        <div class="dashboard-heading">
            <div class="page-eyebrow">
                Global Weather Monitoring
            </div>

            <h1 class="page-title">
                Pemantau Cuaca
            </h1>

            <p class="page-description">
                Pantau temperatur, curah hujan, kecepatan angin, dan risiko
                cuaca ekstrem berdasarkan negara tujuan rantai pasok.
            </p>
        </div>

        <form
            method="GET"
            action="{{ route('weather.index') }}"
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
                            {{ $country->name }} - {{ $country->iso3_code }}
                        </option>
                    @endforeach
                </select>
            </div>
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
                    Negara Dipantau
                </span>

                <h2>
                    {{ $selectedCountry->name }}
                </h2>

                <p>
                    Koordinat:
                    {{ $selectedCountry->latitude }},
                    {{ $selectedCountry->longitude }}
                </p>
            </div>
        </div>

        <div class="country-overview-stats">
            <div class="country-stat">
                <span>Temperatur</span>
                <strong>
                    {{ $weather ? number_format((float) $weather->temperature, 1, ',', '.') . '°C' : '-' }}
                </strong>
                <small>Suhu saat ini</small>
            </div>

            <div class="country-stat">
                <span>Curah Hujan</span>
                <strong>
                    {{ $weather ? number_format((float) $weather->precipitation, 2, ',', '.') . ' mm' : '-' }}
                </strong>
                <small>Presipitasi</small>
            </div>

            <div class="country-stat">
                <span>Kecepatan Angin</span>
                <strong>
                    {{ $weather ? number_format((float) $weather->wind_speed, 1, ',', '.') . ' km/jam' : '-' }}
                </strong>
                <small>Wind speed</small>
            </div>

            <div class="country-stat">
                <span>Kode Cuaca</span>
                <strong>
                    {{ $weather ? $weather->weather_code : '-' }}
                </strong>
                <small>Open-Meteo code</small>
            </div>
        </div>
    </section>

    <section class="risk-analysis-grid">
        <article class="analysis-card total-risk-card">
            <div class="analysis-card-header">
                <div>
                    <span class="analysis-label">
                        Risiko Cuaca
                    </span>

                    <strong class="total-risk-score">
                        {{ number_format($weatherRisk, 2) }}
                    </strong>
                </div>

                <span class="badge {{ $riskBadgeClass }} px-3 py-2">
                    {{ $riskLevel }}
                </span>
            </div>

            <div class="progress total-risk-progress">
                <div
                    id="weatherRiskProgress"
                    class="progress-bar"
                    role="progressbar"
                    data-risk-width="{{ $weatherRiskWidth }}"
                    aria-valuenow="{{ $weatherRisk }}"
                    aria-valuemin="0"
                    aria-valuemax="100"
                ></div>
            </div>

            <p class="analysis-description">
                Risiko cuaca dihitung dari curah hujan, kecepatan angin, dan
                kode kondisi cuaca. Semakin ekstrem indikatornya, semakin besar
                potensi gangguan pengiriman.
            </p>
        </article>

        <article class="analysis-card">
            <div class="analysis-heading">
                <h3>
                    Grafik Riwayat Cuaca
                </h3>

                <p>
                    Grafik menampilkan log data cuaca yang tersimpan pada tabel
                    <strong>weather_data</strong>.
                </p>
            </div>

            <div
                id="weatherChartData"
                class="d-none"
                data-labels='{{ json_encode($weatherChartLabels) }}'
                data-temperature='{{ json_encode($temperatureData) }}'
                data-precipitation='{{ json_encode($precipitationData) }}'
                data-wind='{{ json_encode($windData) }}'
            ></div>

            <div class="mt-4">
                <canvas id="weatherChart" height="120"></canvas>
            </div>
        </article>
    </section>

    <section class="risk-analysis-grid">
        <article class="analysis-card">
            <div class="analysis-heading">
                <h3>
                    Peta Lokasi Cuaca
                </h3>

                <p>
                    Titik peta menampilkan lokasi negara berdasarkan koordinat
                    dari data negara.
                </p>
            </div>

            <div
                id="weatherMapData"
                class="d-none"
                data-lat="{{ $selectedCountry->latitude }}"
                data-lng="{{ $selectedCountry->longitude }}"
                data-country="{{ $selectedCountry->name }}"
            ></div>

            <div
                id="weatherMap"
                class="rounded-4 mt-4"
                style="width: 100%; height: 360px;"
            ></div>
        </article>

        <article class="analysis-card">
            <div class="analysis-heading">
                <h3>
                    Detail Data Terbaru
                </h3>

                <p>
                    Data cuaca diambil dari Open-Meteo API dan disimpan agar
                    dapat digunakan untuk analisis tren.
                </p>
            </div>

            <div class="table-responsive mt-4">
                <table class="table table-hover align-middle mb-0">
                    <tbody>
                        <tr>
                            <th>Negara</th>
                            <td>{{ $selectedCountry->name }}</td>
                        </tr>

                        <tr>
                            <th>Temperatur</th>
                            <td>
                                {{ $weather ? number_format((float) $weather->temperature, 2, ',', '.') . ' °C' : '-' }}
                            </td>
                        </tr>

                        <tr>
                            <th>Curah Hujan</th>
                            <td>
                                {{ $weather ? number_format((float) $weather->precipitation, 2, ',', '.') . ' mm' : '-' }}
                            </td>
                        </tr>

                        <tr>
                            <th>Kecepatan Angin</th>
                            <td>
                                {{ $weather ? number_format((float) $weather->wind_speed, 2, ',', '.') . ' km/jam' : '-' }}
                            </td>
                        </tr>

                        <tr>
                            <th>Weather Risk</th>
                            <td>
                                {{ number_format($weatherRisk, 2) }} / 100
                            </td>
                        </tr>

                        <tr>
                            <th>Waktu Data</th>
                            <td>
                                {{ $weather && $weather->recorded_at ? $weather->recorded_at->format('d M Y H:i') : '-' }}
                            </td>
                        </tr>

                        <tr>
                            <th>Diambil Pada</th>
                            <td>
                                {{ $weather && $weather->fetched_at ? $weather->fetched_at->format('d M Y H:i') : '-' }}
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </article>
    </section>

    <section class="economic-section">
        <div class="section-heading">
            <div class="page-eyebrow">
                Riwayat Cuaca
            </div>

            <h2>
                Log Data Cuaca
            </h2>

            <p>
                Data ini digunakan untuk memantau perubahan kondisi cuaca dari
                waktu ke waktu.
            </p>
        </div>

        <div class="analysis-card">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead>
                        <tr>
                            <th>Waktu Data</th>
                            <th>Temperatur</th>
                            <th>Curah Hujan</th>
                            <th>Angin</th>
                            <th>Kode</th>
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
                                    {{ number_format((float) $item->temperature, 2, ',', '.') }} °C
                                </td>

                                <td>
                                    {{ number_format((float) $item->precipitation, 2, ',', '.') }} mm
                                </td>

                                <td>
                                    {{ number_format((float) $item->wind_speed, 2, ',', '.') }} km/jam
                                </td>

                                <td>
                                    {{ $item->weather_code }}
                                </td>

                                <td>
                                    {{ number_format((float) $item->weather_risk, 2) }}
                                </td>

                                <td>
                                    {{ $item->fetched_at ? $item->fetched_at->format('d M Y H:i') : '-' }}
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="text-center text-muted py-4">
                                    Data cuaca belum tersedia.
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
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>

    <script>
        const weatherRiskProgress = document.getElementById('weatherRiskProgress');

        if (weatherRiskProgress) {
            const riskWidth = Number(weatherRiskProgress.dataset.riskWidth || 0);
            weatherRiskProgress.style.width = `${riskWidth}%`;
        }

        const weatherChartElement = document.getElementById('weatherChart');
        const weatherChartData = document.getElementById('weatherChartData');

        if (weatherChartElement && weatherChartData) {
            const labels = JSON.parse(weatherChartData.dataset.labels || '[]');
            const temperature = JSON.parse(weatherChartData.dataset.temperature || '[]');
            const precipitation = JSON.parse(weatherChartData.dataset.precipitation || '[]');
            const wind = JSON.parse(weatherChartData.dataset.wind || '[]');

            new Chart(weatherChartElement, {
                type: 'line',
                data: {
                    labels: labels,
                    datasets: [
                        {
                            label: 'Temperatur (°C)',
                            data: temperature,
                            tension: 0.35,
                            fill: false,
                        },
                        {
                            label: 'Curah Hujan (mm)',
                            data: precipitation,
                            tension: 0.35,
                            fill: false,
                        },
                        {
                            label: 'Angin (km/jam)',
                            data: wind,
                            tension: 0.35,
                            fill: false,
                        },
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

        const weatherMapElement = document.getElementById('weatherMap');
        const weatherMapData = document.getElementById('weatherMapData');

        if (weatherMapElement && weatherMapData && typeof L !== 'undefined') {
            const latitude = Number(weatherMapData.dataset.lat || 0);
            const longitude = Number(weatherMapData.dataset.lng || 0);
            const countryName = weatherMapData.dataset.country || 'Selected Country';

            const map = L.map('weatherMap').setView([latitude, longitude], 4);

            L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                attribution: '&copy; OpenStreetMap contributors',
            }).addTo(map);

            L.marker([latitude, longitude])
                .addTo(map)
                .bindPopup(countryName)
                .openPopup();
        }
    </script>
@endpush
@extends('layouts.app')

@section('title', 'Weather Monitoring')

@section('content')
    @php
        $riskBadgeClass = match (true) {
            ($weatherRisk ?? 0) >= 75 => 'bg-danger',
            ($weatherRisk ?? 0) >= 50 => 'bg-warning text-dark',
            ($weatherRisk ?? 0) >= 25 => 'bg-info text-dark',
            default => 'bg-success',
        };

        $alertColor = $weatherAlertColor ?? 'success';
        $alertIcon = $weatherAlertIcon ?? 'bi-sun';
        $alertLabel = $weatherAlertLabel ?? 'Normal';

        $selectedLatitude = $selectedCountry->latitude ? (float) $selectedCountry->latitude : -2.5489;
        $selectedLongitude = $selectedCountry->longitude ? (float) $selectedCountry->longitude : 118.0149;
    @endphp

    <link
        rel="stylesheet"
        href="https://cdn.jsdelivr.net/npm/leaflet@1.9.4/dist/leaflet.css"
    >

    <style>
        .weather-map {
            width: 100%;
            height: 360px;
            border-radius: 20px;
            overflow: hidden;
            border: 1px solid rgba(148, 163, 184, 0.35);
        }

        .weather-marker {
            width: 34px;
            height: 34px;
            border-radius: 999px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #ffffff;
            font-size: 16px;
            border: 3px solid #ffffff;
            box-shadow: 0 10px 24px rgba(15, 23, 42, 0.28);
        }

        .weather-marker.normal {
            background: #198754;
        }

        .weather-marker.rain {
            background: #0d6efd;
        }

        .weather-marker.strong_wind {
            background: #ffc107;
            color: #111827;
        }

        .weather-marker.storm,
        .weather-marker.weather_risk {
            background: #dc3545;
        }

        .weather-marker.selected {
            width: 42px;
            height: 42px;
            font-size: 19px;
            box-shadow: 0 12px 30px rgba(220, 53, 69, 0.38);
        }

        .weather-legend {
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
            margin-top: 14px;
        }

        .weather-legend-item {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            font-size: 13px;
            color: #64748b;
        }

        .weather-legend-dot {
            width: 12px;
            height: 12px;
            border-radius: 999px;
            display: inline-block;
        }

        .weather-legend-dot.normal {
            background: #198754;
        }

        .weather-legend-dot.rain {
            background: #0d6efd;
        }

        .weather-legend-dot.strong_wind {
            background: #ffc107;
        }

        .weather-legend-dot.storm {
            background: #dc3545;
        }

        .weather-alert-list {
            display: grid;
            gap: 12px;
        }

        .weather-alert-item {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 14px;
            padding: 12px 14px;
            border: 1px solid rgba(148, 163, 184, 0.25);
            border-radius: 16px;
            background: rgba(248, 250, 252, 0.7);
        }

        .weather-alert-name {
            font-weight: 700;
            color: #0f172a;
        }

        .weather-alert-meta {
            font-size: 12px;
            color: #64748b;
        }
    </style>

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

                <a
                    href="{{ route('weather.index', ['country' => $selectedCountry->iso3_code, 'refresh' => 1]) }}"
                    class="btn btn-primary mt-2 w-100"
                >
                    <i class="bi bi-arrow-clockwise me-1"></i>
                    Perbarui Cuaca
                </a>
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
                        Data terbaru
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
                    Pembaruan: {{ $lastUpdate ?? 'Belum tersedia' }}
                </p>
            </article>

            <article class="analysis-card">
                <div class="analysis-heading">
                    <h3>
                        Status Cuaca
                    </h3>
                </div>

                <div class="country-overview-stats">
                    <div class="country-stat">
                        <span>Kondisi</span>

                        <strong>
                            <span class="badge text-bg-{{ $alertColor }}">
                                <i class="bi {{ $alertIcon }} me-1"></i>
                                {{ $alertLabel }}
                            </span>
                        </strong>

                        <small>
                            {{ $weatherDescription ?? 'Belum tersedia' }}
                        </small>
                    </div>

                    <div class="country-stat">
                        <span>Kode Cuaca</span>

                        <strong>
                            {{ $weatherCode ?? 0 }}
                        </strong>

                        <small>
                            Kode kondisi
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
                        Peta Pemantauan Cuaca Global
                    </h3>

                    <p>
                        Marker menampilkan kondisi cuaca negara yang sudah memiliki data tersimpan.
                    </p>
                </div>

                <div id="weatherMap" class="weather-map"></div>

                <div class="weather-legend">
                    <span class="weather-legend-item">
                        <span class="weather-legend-dot normal"></span>
                        Normal
                    </span>

                    <span class="weather-legend-item">
                        <span class="weather-legend-dot rain"></span>
                        Hujan
                    </span>

                    <span class="weather-legend-item">
                        <span class="weather-legend-dot strong_wind"></span>
                        Angin Kencang
                    </span>

                    <span class="weather-legend-item">
                        <span class="weather-legend-dot storm"></span>
                        Badai Petir / Risiko Tinggi
                    </span>
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
                </div>

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
            <article class="analysis-card">
                <div class="analysis-heading">
                    <h3>
                        Ringkasan Kondisi Global
                    </h3>

                    <p>
                        Negara dengan risiko cuaca tertinggi dari data tersimpan.
                    </p>
                </div>

                <div class="weather-alert-list" id="weatherAlertList">
                    <div class="text-muted">
                        Data ringkasan akan ditampilkan setelah peta dimuat.
                    </div>
                </div>
            </article>

            <article class="analysis-card">
                <div class="analysis-heading">
                    <h3>
                        Riwayat Cuaca Terakhir
                    </h3>

                    <p>
                        Data cuaca terbaru negara terpilih.
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
                                <th>Kode</th>
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
    </div>
@endsection

@push('scripts')
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/leaflet@1.9.4/dist/leaflet.js"></script>

    <script
        id="weatherChartData"
        type="application/json"
    >{!! json_encode($chartData ?? [], JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT) !!}</script>

    <script
        id="selectedWeatherCountry"
        type="application/json"
    >{!! json_encode([
        'name' => $selectedCountry->name,
        'iso3_code' => $selectedCountry->iso3_code,
        'latitude' => $selectedLatitude,
        'longitude' => $selectedLongitude,
    ], JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT) !!}</script>

    <script>
        document.addEventListener('DOMContentLoaded', function () {
            var progressBars = document.querySelectorAll('.js-progress-bar');

            progressBars.forEach(function (progressBar) {
                var width = progressBar.getAttribute('data-progress-width') || 0;

                progressBar.style.width = width + '%';
            });

            var chartDataElement = document.getElementById('weatherChartData');
            var selectedCountryElement = document.getElementById('selectedWeatherCountry');
            var chartData = {};
            var selectedCountry = {};

            try {
                chartData = JSON.parse(chartDataElement.textContent || '{}');
            } catch (error) {
                chartData = {};
            }

            try {
                selectedCountry = JSON.parse(selectedCountryElement.textContent || '{}');
            } catch (error) {
                selectedCountry = {};
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

                if (!canvas || typeof Chart === 'undefined') {
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
                                maxBarThickness: 30
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

            function markerIcon(point) {
                var type = point.alert_type || 'normal';
                var icon = point.alert_icon || 'bi-sun';
                var selectedClass = point.is_selected ? ' selected' : '';

                return L.divIcon({
                    html: '<div class="weather-marker ' + type + selectedClass + '"><i class="bi ' + icon + '"></i></div>',
                    className: '',
                    iconSize: point.is_selected ? [42, 42] : [34, 34],
                    iconAnchor: point.is_selected ? [21, 21] : [17, 17],
                    popupAnchor: [0, -18]
                });
            }

            function renderWeatherMap() {
                var mapElement = document.getElementById('weatherMap');

                if (!mapElement || typeof L === 'undefined') {
                    return;
                }

                var centerLatitude = selectedCountry.latitude || -2.5489;
                var centerLongitude = selectedCountry.longitude || 118.0149;

                var map = L.map('weatherMap', {
                    scrollWheelZoom: false
                }).setView([centerLatitude, centerLongitude], 4);

                L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                    maxZoom: 18,
                    attribution: '&copy; OpenStreetMap'
                }).addTo(map);

                var points = Array.isArray(chartData.mapPoints) ? chartData.mapPoints : [];

                if (points.length === 0 && selectedCountry.latitude && selectedCountry.longitude) {
                    points = [
                        {
                            name: selectedCountry.name || 'Negara terpilih',
                            iso3_code: selectedCountry.iso3_code || '-',
                            latitude: selectedCountry.latitude,
                            longitude: selectedCountry.longitude,
                            temperature: 0,
                            precipitation: 0,
                            wind_speed: 0,
                            weather_risk: 0,
                            weather_description: 'Belum tersedia',
                            risk_label: 'Belum tersedia',
                            alert_type: 'normal',
                            alert_label: 'Normal',
                            alert_icon: 'bi-sun',
                            is_selected: true,
                            recorded_at: '-'
                        }
                    ];
                }

                var bounds = [];

                points.forEach(function (point) {
                    if (!point.latitude || !point.longitude) {
                        return;
                    }

                    var marker = L.marker(
                        [point.latitude, point.longitude],
                        {
                            icon: markerIcon(point)
                        }
                    ).addTo(map);

                    marker.bindPopup(
                        '<strong>' + point.name + ' (' + point.iso3_code + ')</strong><br>' +
                        'Kondisi: ' + point.alert_label + '<br>' +
                        'Cuaca: ' + point.weather_description + '<br>' +
                        'Temperatur: ' + point.temperature + '°C<br>' +
                        'Hujan: ' + point.precipitation + ' mm<br>' +
                        'Angin: ' + point.wind_speed + ' km/jam<br>' +
                        'Risiko: ' + point.weather_risk
                    );

                    bounds.push([point.latitude, point.longitude]);
                });

                if (bounds.length > 1) {
                    map.fitBounds(bounds, {
                        padding: [30, 30]
                    });
                }

                setTimeout(function () {
                    map.invalidateSize();
                }, 300);
            }

            function renderAlertList() {
                var listElement = document.getElementById('weatherAlertList');

                if (!listElement) {
                    return;
                }

                var points = Array.isArray(chartData.mapPoints) ? chartData.mapPoints : [];

                if (points.length === 0) {
                    listElement.innerHTML = '<div class="text-muted">Belum ada data cuaca global tersimpan.</div>';
                    return;
                }

                var topPoints = points
                    .slice()
                    .sort(function (a, b) {
                        return (b.weather_risk || 0) - (a.weather_risk || 0);
                    })
                    .slice(0, 5);

                listElement.innerHTML = topPoints.map(function (point) {
                    var color = point.alert_color || 'success';

                    return (
                        '<div class="weather-alert-item">' +
                            '<div>' +
                                '<div class="weather-alert-name">' + point.name + '</div>' +
                                '<div class="weather-alert-meta">' +
                                    point.alert_label + ' • ' +
                                    point.temperature + '°C • ' +
                                    point.wind_speed + ' km/jam' +
                                '</div>' +
                            '</div>' +
                            '<span class="badge text-bg-' + color + '">' +
                                point.weather_risk +
                            '</span>' +
                        '</div>'
                    );
                }).join('');
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

            renderWeatherMap();
            renderAlertList();
        });
    </script>
@endpush
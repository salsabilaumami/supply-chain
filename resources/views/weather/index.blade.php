@extends('layouts.app')

@section('title', 'Weather Monitoring')

@section('content')
    @php
        $impactClass = 'impact-' . ($currentSummary['shipping_impact']['level'] ?? 'unknown');
    @endphp

    <div class="weather-page">
        <section class="weather-hero">
            <div>
                <div class="page-eyebrow">
                    GLOBAL WEATHER MONITORING
                </div>

                <h1>
                    Weather Monitoring
                </h1>

                <p>
                    Pantau hujan, badai, angin kencang, dan prakiraan cuaca untuk mendukung keputusan pengiriman global.
                </p>
            </div>

            <div class="weather-country-card">
                @if ($selectedCountry->flag_url)
                    <img
                        src="{{ $selectedCountry->flag_url }}"
                        alt="Bendera {{ $selectedCountry->name }}"
                    >
                @else
                    <div class="weather-flag-placeholder">
                        <i class="bi bi-flag"></i>
                    </div>
                @endif

                <div>
                    <span>Negara Dipantau</span>

                    <strong>
                        {{ $selectedCountry->name }}
                    </strong>

                    <small>
                        {{ $selectedCountry->iso3_code }}
                    </small>
                </div>
            </div>
        </section>

        <section class="weather-filter-card">
            <form
                method="GET"
                action="{{ route('weather.index') }}"
                class="weather-filter-form"
            >
                <div class="weather-field">
                    <label for="country">
                        Pilih Negara
                    </label>

                    <select
                        name="country"
                        id="country"
                        class="form-select"
                    >
                        @foreach ($countries as $country)
                            <option
                                value="{{ $country->iso3_code }}"
                                @selected($selectedCountry->id === $country->id)
                            >
                                {{ $country->name }} ({{ $country->iso3_code }})
                            </option>
                        @endforeach
                    </select>
                </div>

                <button
                    type="submit"
                    class="btn btn-primary"
                >
                    Tampilkan
                </button>

                <button
                    type="submit"
                    name="refresh"
                    value="1"
                    class="btn btn-outline-primary"
                >
                    Perbarui
                </button>
            </form>
        </section>

        @if (!empty($apiError))
            <div class="weather-alert">
                <i class="bi bi-info-circle"></i>
                <span>{{ $apiError }}</span>
            </div>
        @endif

        @if (!empty($forecastError))
            <div class="weather-alert">
                <i class="bi bi-exclamation-triangle"></i>
                <span>{{ $forecastError }}</span>
            </div>
        @endif

        <section class="weather-main-grid">
            <article class="weather-current-card">
                <div class="weather-heading">
                    <span>Cuaca Saat Ini</span>

                    <h2>
                        {{ $currentSummary['condition'] ?? 'Belum tersedia' }}
                    </h2>
                </div>

                <div class="weather-temperature">
                    {{ number_format($currentSummary['temperature'] ?? 0, 1, ',', '.') }}°C
                </div>

                <div class="weather-current-stats">
                    <div>
                        <span>Curah Hujan</span>

                        <strong>
                            {{ number_format($currentSummary['precipitation'] ?? 0, 2, ',', '.') }} mm
                        </strong>
                    </div>

                    <div>
                        <span>Angin</span>

                        <strong>
                            {{ number_format($currentSummary['wind_speed'] ?? 0, 1, ',', '.') }} km/jam
                        </strong>
                    </div>

                    <div>
                        <span>Kode Cuaca</span>

                        <strong>
                            {{ $currentSummary['weather_code'] ?? 0 }}
                        </strong>
                    </div>

                    <div>
                        <span>Weather Risk</span>

                        <strong>
                            {{ number_format($currentSummary['weather_risk'] ?? 0, 2, ',', '.') }}
                        </strong>
                    </div>
                </div>

                <small>
                    Update: {{ $currentSummary['recorded_at'] ?? 'Belum tersedia' }}
                </small>
            </article>

            <article class="weather-impact-card">
                <div class="weather-heading">
                    <span>Dampak Pengiriman</span>

                    <h2>
                        Status Logistik
                    </h2>
                </div>

                <div class="impact-badge {{ $impactClass }}">
                    {{ $currentSummary['shipping_impact']['label'] ?? 'Belum tersedia' }}
                </div>

                <p>
                    {{ $currentSummary['shipping_impact']['description'] ?? 'Data cuaca belum tersedia.' }}
                </p>

                <strong>
                    {{ $currentSummary['risk_label'] ?? 'Belum tersedia' }}
                </strong>
            </article>
        </section>

        <section class="weather-alert-grid">
            <article class="weather-alert-card {{ ($currentSummary['alerts']['rain']['active'] ?? false) ? 'active-rain' : '' }}">
                <i class="bi bi-cloud-rain"></i>

                <div>
                    <span>Hujan</span>

                    <strong>
                        {{ $currentSummary['alerts']['rain']['label'] ?? 'Belum tersedia' }}
                    </strong>

                    <small>
                        {{ number_format($currentSummary['alerts']['rain']['value'] ?? 0, 2, ',', '.') }} mm
                    </small>
                </div>
            </article>

            <article class="weather-alert-card {{ ($currentSummary['alerts']['storm']['active'] ?? false) ? 'active-storm' : '' }}">
                <i class="bi bi-cloud-lightning-rain"></i>

                <div>
                    <span>Badai</span>

                    <strong>
                        {{ $currentSummary['alerts']['storm']['label'] ?? 'Belum tersedia' }}
                    </strong>

                    <small>
                        Kode {{ $currentSummary['alerts']['storm']['value'] ?? 0 }}
                    </small>
                </div>
            </article>

            <article class="weather-alert-card {{ ($currentSummary['alerts']['strong_wind']['active'] ?? false) ? 'active-wind' : '' }}">
                <i class="bi bi-wind"></i>

                <div>
                    <span>Angin Kencang</span>

                    <strong>
                        {{ $currentSummary['alerts']['strong_wind']['label'] ?? 'Belum tersedia' }}
                    </strong>

                    <small>
                        {{ number_format($currentSummary['alerts']['strong_wind']['value'] ?? 0, 1, ',', '.') }} km/jam
                    </small>
                </div>
            </article>
        </section>

        <section class="weather-map-card">
            <div class="weather-heading">
                <span>Peta Dunia</span>

                <h2>
                    Hujan, Badai, dan Angin Kencang Berdasarkan Negara Terpilih
                </h2>
            </div>

            <div id="weatherMap"></div>

            <div class="weather-map-legend">
                <span><b class="legend-dot normal"></b> Normal</span>
                <span><b class="legend-dot rain"></b> Hujan</span>
                <span><b class="legend-dot storm"></b> Badai</span>
                <span><b class="legend-dot wind"></b> Angin Kencang</span>
            </div>
        </section>

        <section class="weather-chart-grid">
            <article class="weather-panel">
                <div class="weather-heading">
                    <span>Prakiraan 7 Hari</span>

                    <h2>
                        Suhu Harian
                    </h2>
                </div>

                <div class="weather-chart-box">
                    <canvas id="temperatureForecastChart"></canvas>
                </div>
            </article>

            <article class="weather-panel">
                <div class="weather-heading">
                    <span>Potensi Gangguan</span>

                    <h2>
                        Curah Hujan dan Angin
                    </h2>
                </div>

                <div class="weather-chart-box">
                    <canvas id="rainWindForecastChart"></canvas>
                </div>
            </article>
        </section>

        <section class="weather-panel">
            <div class="weather-heading">
                <span>Forecast Detail</span>

                <h2>
                    Prakiraan Cuaca 7 Hari
                </h2>
            </div>

            <div class="forecast-grid">
                @forelse ($forecast as $item)
                    <article class="forecast-card impact-{{ $item['shipping_impact']['level'] }}">
                        <span>
                            {{ $item['day_label'] }}
                        </span>

                        <strong>
                            {{ $item['condition'] }}
                        </strong>

                        <small>
                            {{ number_format($item['temperature_min'], 1, ',', '.') }}°C -
                            {{ number_format($item['temperature_max'], 1, ',', '.') }}°C
                        </small>

                        <div>
                            <b>
                                Hujan:
                            </b>
                            {{ number_format($item['precipitation'], 2, ',', '.') }} mm
                        </div>

                        <div>
                            <b>
                                Angin:
                            </b>
                            {{ number_format($item['wind_speed'], 1, ',', '.') }} km/jam
                        </div>

                        <em>
                            {{ $item['shipping_impact']['label'] }}
                        </em>
                    </article>
                @empty
                    <div class="empty-forecast">
                        Prakiraan cuaca belum tersedia.
                    </div>
                @endforelse
            </div>
        </section>
    </div>
@endsection

@push('styles')
    <link
        rel="stylesheet"
        href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css"
    >

    <style>
        .weather-page {
            width: 100%;
            max-width: 1100px;
            margin: 0 auto;
            padding: 10px 12px 22px;
            display: flex;
            flex-direction: column;
            gap: 10px;
            overflow-x: hidden;
        }

        .weather-hero {
            display: grid;
            grid-template-columns: minmax(0, 1fr) 250px;
            gap: 10px;
            align-items: end;
        }

        .weather-hero h1 {
            margin: 0 0 4px;
            color: #111827;
            font-size: 1.45rem;
            font-weight: 950;
        }

        .weather-hero p {
            margin: 0;
            color: #7c8aa5;
            font-size: 0.8rem;
            line-height: 1.4;
        }

        .weather-country-card,
        .weather-filter-card,
        .weather-current-card,
        .weather-impact-card,
        .weather-alert-card,
        .weather-map-card,
        .weather-panel {
            background: #ffffff;
            border: 1px solid rgba(148, 163, 184, 0.22);
            border-radius: 15px;
            box-shadow: 0 8px 20px rgba(15, 23, 42, 0.035);
            min-width: 0;
            overflow: hidden;
        }

        .weather-country-card {
            display: flex;
            align-items: center;
            gap: 10px;
            padding: 9px 11px;
            min-height: 66px;
        }

        .weather-country-card img,
        .weather-flag-placeholder {
            width: 40px;
            height: 27px;
            border-radius: 8px;
            object-fit: cover;
            background: #e2e8f0;
        }

        .weather-flag-placeholder {
            display: grid;
            place-items: center;
            color: #64748b;
        }

        .weather-country-card span,
        .weather-heading span,
        .weather-field label,
        .weather-current-stats span,
        .weather-alert-card span,
        .forecast-card span {
            display: block;
            margin-bottom: 3px;
            color: #7c8aa5;
            font-size: 0.66rem;
            font-weight: 850;
            text-transform: uppercase;
            letter-spacing: 0.035em;
        }

        .weather-country-card strong {
            display: block;
            color: #111827;
            font-size: 0.92rem;
            font-weight: 900;
        }

        .weather-country-card small {
            color: #7c8aa5;
            font-size: 0.7rem;
        }

        .weather-filter-card {
            padding: 9px 10px;
        }

        .weather-filter-form {
            display: grid;
            grid-template-columns: minmax(0, 1fr) 95px 95px;
            gap: 8px;
            align-items: end;
        }

        .weather-filter-form .form-select,
        .weather-filter-form .btn {
            height: 36px;
            border-radius: 10px;
            font-size: 0.79rem;
            font-weight: 800;
        }

        .weather-alert {
            display: flex;
            align-items: center;
            gap: 8px;
            padding: 9px 11px;
            border-radius: 13px;
            background: #fffbeb;
            color: #92400e;
            border: 1px solid rgba(245, 158, 11, 0.22);
            font-size: 0.8rem;
            font-weight: 750;
        }

        .weather-main-grid {
            display: grid;
            grid-template-columns: minmax(0, 1.4fr) minmax(240px, 0.6fr);
            gap: 10px;
        }

        .weather-current-card,
        .weather-impact-card,
        .weather-map-card,
        .weather-panel {
            padding: 12px;
        }

        .weather-heading {
            margin-bottom: 9px;
        }

        .weather-heading h2 {
            margin: 0;
            color: #111827;
            font-size: 0.95rem;
            font-weight: 950;
        }

        .weather-temperature {
            color: #111827;
            font-size: 2rem;
            font-weight: 950;
            line-height: 1;
            margin-bottom: 10px;
        }

        .weather-current-stats {
            display: grid;
            grid-template-columns: repeat(4, minmax(0, 1fr));
            gap: 8px;
            margin-bottom: 8px;
        }

        .weather-current-stats div {
            padding: 9px;
            border-radius: 12px;
            background: #f8fafc;
            border: 1px solid rgba(148, 163, 184, 0.16);
        }

        .weather-current-stats strong {
            color: #111827;
            font-size: 0.8rem;
            font-weight: 900;
        }

        .weather-current-card small {
            color: #7c8aa5;
            font-size: 0.7rem;
        }

        .impact-badge {
            display: inline-flex;
            padding: 7px 10px;
            border-radius: 999px;
            font-size: 0.76rem;
            font-weight: 900;
            margin-bottom: 8px;
        }

        .impact-low {
            background: #eef6ff;
            color: #1d4ed8;
            border: 1px solid rgba(37, 99, 235, 0.18);
        }

        .impact-medium {
            background: #fef3c7;
            color: #92400e;
            border: 1px solid rgba(245, 158, 11, 0.3);
        }

        .impact-high {
            background: #fee2e2;
            color: #991b1b;
            border: 1px solid rgba(239, 68, 68, 0.28);
        }

        .impact-unknown {
            background: #f1f5f9;
            color: #64748b;
            border: 1px solid rgba(148, 163, 184, 0.24);
        }

        .weather-impact-card p {
            margin: 0 0 8px;
            color: #64748b;
            font-size: 0.76rem;
            line-height: 1.45;
        }

        .weather-impact-card strong {
            color: #111827;
            font-size: 0.9rem;
            font-weight: 950;
        }

        .weather-alert-grid {
            display: grid;
            grid-template-columns: repeat(3, minmax(0, 1fr));
            gap: 10px;
        }

        .weather-alert-card {
            display: flex;
            align-items: center;
            gap: 10px;
            padding: 11px;
        }

        .weather-alert-card i {
            width: 38px;
            height: 38px;
            border-radius: 999px;
            display: grid;
            place-items: center;
            background: #f1f5f9;
            color: #64748b;
            flex: 0 0 auto;
        }

        .weather-alert-card strong {
            color: #111827;
            font-size: 0.82rem;
            font-weight: 900;
        }

        .weather-alert-card small {
            color: #7c8aa5;
            font-size: 0.7rem;
        }

        .active-rain i {
            background: #e0f2fe;
            color: #0284c7;
        }

        .active-storm i {
            background: #fee2e2;
            color: #dc2626;
        }

        .active-wind i {
            background: #ffedd5;
            color: #ea580c;
        }

        #weatherMap {
            width: 100%;
            height: 330px;
            border-radius: 13px;
            overflow: hidden;
            border: 1px solid rgba(148, 163, 184, 0.18);
        }

        .weather-map-legend {
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
            margin-top: 8px;
            color: #64748b;
            font-size: 0.74rem;
            font-weight: 800;
        }

        .legend-dot {
            display: inline-block;
            width: 10px;
            height: 10px;
            border-radius: 999px;
            margin-right: 4px;
        }

        .legend-dot.normal { background: #2563eb; }
        .legend-dot.rain { background: #0284c7; }
        .legend-dot.storm { background: #dc2626; }
        .legend-dot.wind { background: #ea580c; }

        .weather-chart-grid {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 10px;
        }

        .weather-chart-box {
            height: 170px;
        }

        .forecast-grid {
            display: grid;
            grid-template-columns: repeat(7, minmax(0, 1fr));
            gap: 8px;
        }

        .forecast-card {
            padding: 9px;
            border-radius: 12px;
            border: 1px solid rgba(148, 163, 184, 0.16);
            background: #f8fafc;
            min-width: 0;
        }

        .forecast-card strong {
            display: block;
            color: #111827;
            font-size: 0.78rem;
            font-weight: 900;
            margin-bottom: 3px;
        }

        .forecast-card small,
        .forecast-card div {
            display: block;
            color: #64748b;
            font-size: 0.68rem;
            line-height: 1.35;
        }

        .forecast-card em {
            display: inline-flex;
            margin-top: 6px;
            font-size: 0.66rem;
            font-style: normal;
            font-weight: 850;
            color: #111827;
        }

        .empty-forecast {
            grid-column: 1 / -1;
            padding: 16px;
            text-align: center;
            color: #7c8aa5;
            font-size: 0.8rem;
        }

        @media (max-width: 1100px) {
            .weather-main-grid,
            .weather-chart-grid {
                grid-template-columns: 1fr;
            }

            .forecast-grid {
                grid-template-columns: repeat(3, minmax(0, 1fr));
            }
        }

        @media (max-width: 900px) {
            .weather-hero {
                grid-template-columns: 1fr;
            }

            .weather-filter-form {
                grid-template-columns: 1fr;
            }

            .weather-filter-form .btn {
                width: 100%;
            }

            .weather-current-stats,
            .weather-alert-grid {
                grid-template-columns: 1fr;
            }
        }

        @media (max-width: 640px) {
            .weather-page {
                padding: 10px;
            }

            .weather-hero h1 {
                font-size: 1.25rem;
            }

            .forecast-grid {
                grid-template-columns: 1fr;
            }

            #weatherMap {
                height: 280px;
            }
        }
    </style>
@endpush

@push('scripts')
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

    <script
        src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"
    ></script>

    <script
        id="weatherMapData"
        type="application/json"
    >{!! json_encode($mapPoint ?? [], JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT) !!}</script>

    <script
        id="weatherChartData"
        type="application/json"
    >{!! json_encode($chartData ?? [], JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT) !!}</script>

    <script>
        document.addEventListener('DOMContentLoaded', function () {
            var mapDataElement = document.getElementById('weatherMapData');
            var mapData = {};

            try {
                mapData = JSON.parse(mapDataElement.textContent || '{}');
            } catch (error) {
                mapData = {};
            }

            if (typeof L !== 'undefined' && mapData.latitude && mapData.longitude) {
                var map = L.map('weatherMap', {
                    zoomControl: true,
                    scrollWheelZoom: false
                }).setView([mapData.latitude, mapData.longitude], 4);

                L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                    maxZoom: 18,
                    attribution: '&copy; OpenStreetMap'
                }).addTo(map);

                var colorMap = {
                    normal: '#2563eb',
                    rain: '#0284c7',
                    storm: '#dc2626',
                    wind: '#ea580c'
                };

                var markerColor = colorMap[mapData.type] || colorMap.normal;

                L.circleMarker([mapData.latitude, mapData.longitude], {
                    radius: 13,
                    color: markerColor,
                    fillColor: markerColor,
                    fillOpacity: 0.72,
                    weight: 2
                })
                    .addTo(map)
                    .bindPopup(
                        '<strong>' + mapData.country + '</strong><br>' +
                        'Status: ' + mapData.label + '<br>' +
                        'Kondisi: ' + mapData.condition + '<br>' +
                        'Hujan: ' + mapData.precipitation + ' mm<br>' +
                        'Angin: ' + mapData.wind_speed + ' km/jam<br>' +
                        'Kode cuaca: ' + mapData.weather_code
                    )
                    .openPopup();
            }

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

            function createChart(canvasId, labels, datasets) {
                var canvas = document.getElementById(canvasId);

                if (!canvas) {
                    return;
                }

                new Chart(canvas, {
                    type: 'line',
                    data: {
                        labels: labels,
                        datasets: datasets
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        tension: 0.28,
                        scales: {
                            x: {
                                ticks: {
                                    maxRotation: 0,
                                    font: {
                                        size: 9
                                    }
                                },
                                grid: {
                                    display: false
                                }
                            },
                            y: {
                                ticks: {
                                    font: {
                                        size: 9
                                    }
                                }
                            }
                        },
                        plugins: {
                            legend: {
                                position: 'top',
                                labels: {
                                    boxWidth: 10,
                                    font: {
                                        size: 10
                                    }
                                }
                            }
                        }
                    }
                });
            }

            createChart(
                'temperatureForecastChart',
                chartData.labels || [],
                [
                    {
                        label: 'Suhu Maksimum',
                        data: chartData.temperature_max || [],
                        borderWidth: 2,
                        pointRadius: 3,
                        fill: false
                    },
                    {
                        label: 'Suhu Minimum',
                        data: chartData.temperature_min || [],
                        borderWidth: 2,
                        pointRadius: 3,
                        fill: false
                    }
                ]
            );

            createChart(
                'rainWindForecastChart',
                chartData.labels || [],
                [
                    {
                        label: 'Curah Hujan',
                        data: chartData.precipitation || [],
                        borderWidth: 2,
                        pointRadius: 3,
                        fill: false
                    },
                    {
                        label: 'Angin Maksimum',
                        data: chartData.wind_speed || [],
                        borderWidth: 2,
                        pointRadius: 3,
                        fill: false
                    }
                ]
            );
        });
    </script>
@endpush
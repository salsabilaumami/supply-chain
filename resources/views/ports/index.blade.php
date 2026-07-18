@extends('layouts.app')

@section('title', 'Pelabuhan Global')

@section('content')
    @php
        $averageRisk = $summary['average_risk_score'] ?? 0;

        $riskBadgeClass = match (true) {
            $averageRisk >= 75 => 'risk-critical',
            $averageRisk >= 50 => 'risk-high',
            $averageRisk >= 25 => 'risk-medium',
            default => 'risk-low',
        };

        $selectedPortRiskClass = match (true) {
            ($selectedPort['risk_score'] ?? 0) >= 75 => 'risk-critical',
            ($selectedPort['risk_score'] ?? 0) >= 50 => 'risk-high',
            ($selectedPort['risk_score'] ?? 0) >= 25 => 'risk-medium',
            default => 'risk-low',
        };

        $portMapData = $ports
            ->map(function ($port) {
                return [
                    'name' => $port['name'] ?? '-',
                    'code' => $port['code'] ?? '-',
                    'city' => $port['city'] ?? '-',
                    'country' => $port['country']['name'] ?? '-',
                    'latitude' => $port['latitude'] ?? null,
                    'longitude' => $port['longitude'] ?? null,
                    'risk_score' => $port['risk_score'] ?? 0,
                    'risk_level' => $port['risk_level'] ?? 'low',
                    'capacity_score' => $port['capacity_score'] ?? 0,
                    'congestion_score' => $port['congestion_score'] ?? 0,
                    'weather_exposure_score' => $port['weather_exposure_score'] ?? 0,
                    'source' => $port['source'] ?? '-',
                    'description' => $port['description'] ?? '',
                ];
            })
            ->values()
            ->all();

        $selectedLocation = [
            'latitude' => $defaultLatitude ?? -6.1045,
            'longitude' => $defaultLongitude ?? 106.8866,
        ];

        $tablePorts = $ports->take(60);
    @endphp

    <div class="ports-page">
        <section class="ports-top-grid">
            <div class="ports-title-area">
                <div class="page-eyebrow">
                    GLOBAL PORT LOCATION DASHBOARD
                </div>

                <h1>
                    Pelabuhan Global
                </h1>

                <p>
                    Pantau lokasi pelabuhan, koordinat, peta interaktif, dan risiko logistik negara terpilih.
                </p>
            </div>

            <div class="ports-country-mini-card">
                <div class="ports-country-flag">
                    @if ($selectedCountry?->flag_url)
                        <img
                            src="{{ $selectedCountry->flag_url }}"
                            alt="Bendera {{ $selectedCountry->name }}"
                        >
                    @else
                        <div class="ports-flag-placeholder">
                            <i class="bi bi-flag"></i>
                        </div>
                    @endif
                </div>

                <div>
                    <span>
                        Negara Dipantau
                    </span>

                    <strong>
                        {{ $selectedCountry?->name ?? 'Negara belum tersedia' }}
                    </strong>

                    <small>
                        {{ $selectedCountry?->iso3_code ?? '-' }}
                        •
                        {{ $selectedCountry?->region ?? '-' }}
                    </small>
                </div>
            </div>
        </section>

        <section class="ports-filter-card">
            <form
                method="GET"
                action="{{ route('ports.index') }}"
                class="ports-filter"
            >
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

                <input
                    type="text"
                    name="port"
                    class="form-control"
                    value="{{ $selectedPortKeyword ?? '' }}"
                    placeholder="Cari kode / nama pelabuhan"
                >

                <button
                    type="submit"
                    class="btn btn-primary"
                >
                    Tampilkan
                </button>

                <a
                    href="{{ route('ports.index', ['country' => $selectedCountry?->iso3_code ?? 'IDN']) }}"
                    class="btn btn-outline-primary"
                >
                    Reset
                </a>
            </form>
        </section>

        @if ($apiError)
            <div class="ports-alert">
                <i class="bi bi-info-circle"></i>

                <span>
                    Data terbaru belum dapat dimuat. Sistem menampilkan data tersimpan.
                </span>
            </div>
        @endif

        @if ($ports->isEmpty())
            <div class="ports-alert">
                <i class="bi bi-info-circle"></i>

                <span>
                    Data pelabuhan untuk negara ini belum tersedia.
                </span>
            </div>
        @endif

        <section class="ports-summary-grid">
            <article class="ports-stat-card">
                <span>Total Pelabuhan</span>

                <strong>
                    {{ number_format($summary['total_ports'] ?? 0, 0, ',', '.') }}
                </strong>

                <small>
                    Data negara dipilih
                </small>
            </article>

            <article class="ports-stat-card">
                <span>Rata-rata Risiko</span>

                <strong>
                    {{ number_format($summary['average_risk_score'] ?? 0, 2, ',', '.') }}
                </strong>

                <small>
                    Skor risiko pelabuhan
                </small>
            </article>

            <article class="ports-stat-card">
                <span>Status Risiko</span>

                <strong>
                    <b class="{{ $riskBadgeClass }}">
                        {{ $averageRisk >= 75 ? 'Risiko Kritis' : ($averageRisk >= 50 ? 'Risiko Tinggi' : ($averageRisk >= 25 ? 'Risiko Sedang' : 'Risiko Rendah')) }}
                    </b>
                </strong>

                <small>
                    Rata-rata negara
                </small>
            </article>

            <article class="ports-stat-card">
                <span>Risiko Tertinggi</span>

                <strong>
                    {{ $summary['highest_risk_port'] ?? 'Belum tersedia' }}
                </strong>

                <small>
                    Skor {{ number_format($summary['highest_risk_score'] ?? 0, 2, ',', '.') }}
                </small>
            </article>

            <article class="ports-stat-card">
                <span>Pelabuhan Dipilih</span>

                <strong>
                    {{ $selectedPort['code'] ?? '-' }}
                </strong>

                <small>
                    {{ $selectedPort['name'] ?? 'Belum tersedia' }}
                </small>
            </article>

            <article class="ports-stat-card">
                <span>Sumber Data</span>

                <strong>
                    Dataset
                </strong>

                <small>
                    World Port Index
                </small>
            </article>
        </section>

        <section class="ports-main-grid">
            <article class="ports-card ports-selected-card">
                <div class="ports-card-heading">
                    <span>Pelabuhan Dipilih</span>

                    <h2>
                        {{ $selectedPort['name'] ?? 'Belum tersedia' }}
                    </h2>
                </div>

                <p>
                    {{ $selectedPort['description'] ?? 'Data pelabuhan belum tersedia untuk negara ini.' }}
                </p>

                <div class="ports-selected-grid">
                    <div>
                        <span>Kode</span>

                        <strong>
                            {{ $selectedPort['code'] ?? '-' }}
                        </strong>
                    </div>

                    <div>
                        <span>Kota</span>

                        <strong>
                            {{ $selectedPort['city'] ?? '-' }}
                        </strong>
                    </div>

                    <div>
                        <span>Latitude</span>

                        <strong>
                            {{ $selectedPort['latitude'] ?? '-' }}
                        </strong>
                    </div>

                    <div>
                        <span>Longitude</span>

                        <strong>
                            {{ $selectedPort['longitude'] ?? '-' }}
                        </strong>
                    </div>

                    <div>
                        <span>Capacity</span>

                        <strong>
                            {{ number_format($selectedPort['capacity_score'] ?? 0, 2, ',', '.') }}
                        </strong>
                    </div>

                    <div>
                        <span>Congestion</span>

                        <strong>
                            {{ number_format($selectedPort['congestion_score'] ?? 0, 2, ',', '.') }}
                        </strong>
                    </div>

                    <div>
                        <span>Weather Exposure</span>

                        <strong>
                            {{ number_format($selectedPort['weather_exposure_score'] ?? 0, 2, ',', '.') }}
                        </strong>
                    </div>

                    <div>
                        <span>Risk Score</span>

                        <strong>
                            <b class="{{ $selectedPortRiskClass }}">
                                {{ number_format($selectedPort['risk_score'] ?? 0, 2, ',', '.') }}
                            </b>
                        </strong>
                    </div>
                </div>
            </article>

            <article class="ports-card ports-map-card">
                <div class="ports-card-heading">
                    <span>Peta Interaktif</span>

                    <h2>
                        Lokasi Pelabuhan
                    </h2>
                </div>

                <div id="portsMap"></div>
            </article>
        </section>

        <section class="ports-card">
            <div class="ports-card-heading">
                <span>Grafik Risiko</span>

                <h2>
                    Top Risiko Pelabuhan
                </h2>
            </div>

            <div class="ports-chart-box">
                <canvas id="portRiskChart"></canvas>
            </div>
        </section>

        <section class="ports-card">
            <div class="ports-card-heading">
                <span>Daftar Pelabuhan</span>

                <h2>
                    Pelabuhan Utama
                </h2>
            </div>

            <div class="table-responsive ports-table-wrapper">
                <table class="table align-middle mb-0 ports-table">
                    <thead>
                        <tr>
                            <th>Pelabuhan</th>
                            <th>Kota</th>
                            <th>Tipe</th>
                            <th>Koordinat</th>
                            <th>Capacity</th>
                            <th>Congestion</th>
                            <th>Weather</th>
                            <th>Risk</th>
                            <th>Aksi</th>
                        </tr>
                    </thead>

                    <tbody>
                        @forelse ($tablePorts as $port)
                            <tr>
                                <td>
                                    <strong>
                                        {{ $port['name'] ?? '-' }}
                                    </strong>

                                    <br>

                                    <small>
                                        {{ $port['code'] ?? '-' }}
                                    </small>
                                </td>

                                <td>
                                    {{ $port['city'] ?? '-' }}
                                </td>

                                <td>
                                    {{ $port['type'] ?? '-' }}
                                </td>

                                <td>
                                    {{ number_format((float) ($port['latitude'] ?? 0), 4, ',', '.') }},
                                    {{ number_format((float) ($port['longitude'] ?? 0), 4, ',', '.') }}
                                </td>

                                <td>
                                    {{ number_format($port['capacity_score'] ?? 0, 2, ',', '.') }}
                                </td>

                                <td>
                                    {{ number_format($port['congestion_score'] ?? 0, 2, ',', '.') }}
                                </td>

                                <td>
                                    {{ number_format($port['weather_exposure_score'] ?? 0, 2, ',', '.') }}
                                </td>

                                <td>
                                    <strong>
                                        {{ number_format($port['risk_score'] ?? 0, 2, ',', '.') }}
                                    </strong>
                                </td>

                                <td>
                                    <a
                                        href="{{ route('ports.index', ['country' => $selectedCountry?->iso3_code, 'port' => $port['code'] ?? null]) }}"
                                        class="btn btn-sm btn-outline-primary"
                                    >
                                        Lihat
                                    </a>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td
                                    colspan="9"
                                    class="text-center text-muted py-4"
                                >
                                    Data pelabuhan belum tersedia.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            @if ($ports->count() > $tablePorts->count())
                <p class="ports-table-note">
                    Menampilkan {{ $tablePorts->count() }} dari {{ $ports->count() }} pelabuhan dengan risiko tertinggi.
                </p>
            @endif
        </section>
    </div>
@endsection

@push('styles')
    <link
        rel="stylesheet"
        href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css"
    >

    <style>
        .ports-page {
            width: 100%;
            max-width: 1180px;
            margin: 0 auto;
            padding: 14px 18px 24px;
            display: flex;
            flex-direction: column;
            gap: 12px;
        }

        .ports-top-grid {
            display: grid;
            grid-template-columns: minmax(0, 1fr) 300px;
            gap: 14px;
            align-items: end;
        }

        .ports-title-area {
            padding-left: 6px;
        }

        .ports-title-area h1 {
            margin: 0 0 4px;
            color: #111827;
            font-size: 1.65rem;
            font-weight: 900;
            line-height: 1.1;
        }

        .ports-title-area p {
            margin: 0;
            color: #7c8aa5;
            font-size: 0.84rem;
            line-height: 1.45;
            max-width: 760px;
        }

        .ports-country-mini-card,
        .ports-filter-card,
        .ports-stat-card,
        .ports-card {
            background: #ffffff;
            border: 1px solid rgba(148, 163, 184, 0.22);
            border-radius: 16px;
            box-shadow: 0 10px 24px rgba(15, 23, 42, 0.04);
        }

        .ports-country-mini-card {
            display: flex;
            align-items: center;
            gap: 10px;
            padding: 10px 12px;
            min-height: 76px;
        }

        .ports-country-flag {
            width: 42px;
            height: 28px;
            border-radius: 8px;
            overflow: hidden;
            background: #e2e8f0;
            flex: 0 0 auto;
        }

        .ports-country-flag img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }

        .ports-flag-placeholder {
            width: 100%;
            height: 100%;
            display: grid;
            place-items: center;
            color: #64748b;
        }

        .ports-country-mini-card span,
        .ports-stat-card span,
        .ports-card-heading span,
        .ports-selected-grid span {
            display: block;
            margin-bottom: 3px;
            color: #7c8aa5;
            font-size: 0.68rem;
            font-weight: 800;
            text-transform: uppercase;
            letter-spacing: 0.035em;
        }

        .ports-country-mini-card strong {
            display: block;
            color: #111827;
            font-size: 0.95rem;
            font-weight: 900;
            line-height: 1.2;
        }

        .ports-country-mini-card small {
            display: block;
            color: #7c8aa5;
            font-size: 0.72rem;
            line-height: 1.3;
        }

        .ports-filter-card {
            width: fit-content;
            max-width: 100%;
            padding: 10px 12px;
        }

        .ports-filter {
            display: grid;
            grid-template-columns: 360px 260px 105px 90px;
            gap: 8px;
            align-items: center;
        }

        .ports-filter .form-select,
        .ports-filter .form-control,
        .ports-filter .btn {
            height: 38px;
            border-radius: 10px;
            font-size: 0.82rem;
            font-weight: 800;
        }

        .ports-alert {
            display: flex;
            align-items: center;
            gap: 8px;
            padding: 10px 12px;
            border-radius: 14px;
            background: #fffbeb;
            color: #92400e;
            border: 1px solid rgba(245, 158, 11, 0.22);
            font-size: 0.82rem;
            font-weight: 750;
        }

        .ports-summary-grid {
            display: grid;
            grid-template-columns: repeat(6, minmax(0, 1fr));
            gap: 10px;
        }

        .ports-stat-card {
            min-width: 0;
            padding: 12px 14px;
        }

        .ports-stat-card strong {
            display: block;
            color: #111827;
            font-size: 0.95rem;
            font-weight: 900;
            line-height: 1.25;
            word-break: break-word;
        }

        .ports-stat-card small {
            display: block;
            margin-top: 4px;
            color: #7c8aa5;
            font-size: 0.7rem;
            line-height: 1.3;
        }

        .ports-stat-card b,
        .ports-selected-grid b,
        .risk-low,
        .risk-medium,
        .risk-high,
        .risk-critical {
            display: inline-flex;
            justify-content: center;
            align-items: center;
            min-width: 102px;
            padding: 5px 8px;
            border-radius: 999px;
            font-size: 0.68rem;
            font-weight: 850;
            white-space: nowrap;
        }

        .risk-low {
            background: #eef6ff;
            color: #1d4ed8;
            border: 1px solid rgba(37, 99, 235, 0.18);
        }

        .risk-medium {
            background: #cffafe;
            color: #0f172a;
            border: 1px solid rgba(6, 182, 212, 0.24);
        }

        .risk-high {
            background: #fef3c7;
            color: #92400e;
            border: 1px solid rgba(245, 158, 11, 0.3);
        }

        .risk-critical {
            background: #fee2e2;
            color: #991b1b;
            border: 1px solid rgba(239, 68, 68, 0.28);
        }

        .ports-main-grid {
            display: grid;
            grid-template-columns: minmax(0, 0.9fr) minmax(0, 1.1fr);
            gap: 10px;
        }

        .ports-card {
            padding: 14px;
            min-width: 0;
        }

        .ports-card-heading {
            margin-bottom: 10px;
        }

        .ports-card-heading h2 {
            margin: 0;
            color: #111827;
            font-size: 0.98rem;
            font-weight: 900;
            line-height: 1.25;
        }

        .ports-selected-card p {
            margin: 0 0 10px;
            color: #64748b;
            font-size: 0.78rem;
            line-height: 1.45;
        }

        .ports-selected-grid {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 8px;
        }

        .ports-selected-grid > div {
            padding: 9px;
            border-radius: 12px;
            background: #f8fafc;
            border: 1px solid rgba(148, 163, 184, 0.14);
        }

        .ports-selected-grid strong {
            display: block;
            color: #111827;
            font-size: 0.78rem;
            font-weight: 850;
            line-height: 1.3;
            word-break: break-word;
        }

        #portsMap {
            width: 100%;
            height: 300px;
            border-radius: 14px;
            border: 1px solid rgba(148, 163, 184, 0.18);
            overflow: hidden;
        }

        .ports-chart-box {
            width: 100%;
            height: 210px;
        }

        .ports-table-wrapper {
            border: 1px solid rgba(148, 163, 184, 0.18);
            border-radius: 14px;
            overflow: auto;
        }

        .ports-table {
            min-width: 980px;
        }

        .ports-table thead th {
            background: #f8fafc;
            color: #64748b;
            font-size: 0.72rem;
            font-weight: 900;
            text-transform: uppercase;
            letter-spacing: 0.035em;
            white-space: nowrap;
        }

        .ports-table tbody td {
            color: #334155;
            font-size: 0.8rem;
            border-bottom: 1px solid rgba(148, 163, 184, 0.14);
        }

        .ports-table tbody td strong {
            color: #111827;
            font-weight: 850;
        }

        .ports-table tbody td small {
            color: #7c8aa5;
        }

        .ports-table .btn {
            padding: 3px 8px;
            border-radius: 8px;
            font-size: 0.68rem;
            font-weight: 800;
        }

        .ports-table-note {
            margin: 10px 0 0;
            color: #7c8aa5;
            font-size: 0.75rem;
        }

        @media (max-width: 1280px) {
            .ports-summary-grid {
                grid-template-columns: repeat(3, minmax(0, 1fr));
            }

            .ports-main-grid {
                grid-template-columns: 1fr;
            }
        }

        @media (max-width: 980px) {
            .ports-top-grid {
                grid-template-columns: 1fr;
            }

            .ports-filter-card {
                width: 100%;
            }

            .ports-filter {
                grid-template-columns: 1fr;
            }

            .ports-filter .btn {
                width: 100%;
            }
        }

        @media (max-width: 720px) {
            .ports-page {
                padding: 12px;
            }

            .ports-summary-grid,
            .ports-selected-grid {
                grid-template-columns: repeat(2, minmax(0, 1fr));
            }

            .ports-title-area {
                padding-left: 0;
            }

            .ports-title-area h1 {
                font-size: 1.45rem;
            }
        }

        @media (max-width: 520px) {
            .ports-summary-grid,
            .ports-selected-grid {
                grid-template-columns: 1fr;
            }
        }
    </style>
@endpush

@push('scripts')
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>

    <script
        id="portChartData"
        type="application/json"
    >{!! json_encode($chartData ?? [], JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT) !!}</script>

    <script
        id="portMapData"
        type="application/json"
    >{!! json_encode($portMapData ?? [], JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT) !!}</script>

    <script
        id="portSelectedLocation"
        type="application/json"
    >{!! json_encode($selectedLocation, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT) !!}</script>

    <script>
        document.addEventListener('DOMContentLoaded', function () {
            var mapElement = document.getElementById('portsMap');
            var mapDataElement = document.getElementById('portMapData');
            var selectedLocationElement = document.getElementById('portSelectedLocation');

            if (mapElement && typeof L !== 'undefined') {
                var ports = [];
                var selectedLocation = {
                    latitude: -6.1045,
                    longitude: 106.8866
                };

                try {
                    ports = JSON.parse(mapDataElement.textContent || '[]');
                } catch (error) {
                    ports = [];
                }

                try {
                    selectedLocation = JSON.parse(selectedLocationElement.textContent || '{}');
                } catch (error) {
                    selectedLocation = {
                        latitude: -6.1045,
                        longitude: 106.8866
                    };
                }

                var defaultLatitude = Number(selectedLocation.latitude || -6.1045);
                var defaultLongitude = Number(selectedLocation.longitude || 106.8866);

                var map = L.map('portsMap', {
                    scrollWheelZoom: true,
                    dragging: true,
                    zoomControl: true
                }).setView([defaultLatitude, defaultLongitude], 6);

                L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                    maxZoom: 19,
                    attribution: '&copy; OpenStreetMap'
                }).addTo(map);

                var markerGroup = L.featureGroup().addTo(map);

                ports.forEach(function (port) {
                    var latitude = Number(port.latitude);
                    var longitude = Number(port.longitude);

                    if (
                        Number.isNaN(latitude) ||
                        Number.isNaN(longitude)
                    ) {
                        return;
                    }

                    var marker = L.marker([
                        latitude,
                        longitude
                    ]).bindPopup(
                        '<strong>' + (port.name || '-') + '</strong><br>' +
                        'Kode: ' + (port.code || '-') + '<br>' +
                        'Kota: ' + (port.city || '-') + '<br>' +
                        'Negara: ' + (port.country || '-') + '<br>' +
                        'Capacity: ' + (port.capacity_score || 0) + '<br>' +
                        'Congestion: ' + (port.congestion_score || 0) + '<br>' +
                        'Weather: ' + (port.weather_exposure_score || 0) + '<br>' +
                        'Risk Score: ' + (port.risk_score || 0)
                    );

                    marker.addTo(markerGroup);
                });

                if (markerGroup.getLayers().length > 1) {
                    map.fitBounds(markerGroup.getBounds(), {
                        padding: [30, 30]
                    });
                } else if (markerGroup.getLayers().length === 1) {
                    map.setView(markerGroup.getLayers()[0].getLatLng(), 8);
                }

                setTimeout(function () {
                    map.invalidateSize();
                }, 300);
            }

            if (typeof Chart === 'undefined') {
                return;
            }

            var chartDataElement = document.getElementById('portChartData');
            var chartData = {};

            try {
                chartData = JSON.parse(chartDataElement.textContent || '{}');
            } catch (error) {
                chartData = {};
            }

            var canvas = document.getElementById('portRiskChart');

            if (!canvas) {
                return;
            }

            new Chart(canvas, {
                type: 'bar',
                data: {
                    labels: chartData.risk ? chartData.risk.labels : [],
                    datasets: [
                        {
                            label: 'Risk Score',
                            data: chartData.risk ? chartData.risk.values : [],
                            borderWidth: 1,
                            borderRadius: 6,
                            maxBarThickness: 18
                        }
                    ]
                },
                options: {
                    indexAxis: 'y',
                    responsive: true,
                    maintainAspectRatio: false,
                    layout: {
                        padding: 0
                    },
                    scales: {
                        x: {
                            beginAtZero: true,
                            suggestedMax: 100,
                            ticks: {
                                font: {
                                    size: 9
                                }
                            }
                        },
                        y: {
                            ticks: {
                                font: {
                                    size: 9
                                }
                            },
                            grid: {
                                display: false
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
                        },
                        tooltip: {
                            callbacks: {
                                label: function (context) {
                                    return 'Risk Score: ' + context.parsed.x;
                                }
                            }
                        }
                    }
                }
            });
        });
    </script>
@endpush
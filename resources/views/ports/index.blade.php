@extends('layouts.app')

@section('title', 'Pelabuhan Global')

@section('content')
    @php
        $riskBadgeClass = match (true) {
            ($summary['average_risk_score'] ?? 0) >= 75 => 'bg-danger',
            ($summary['average_risk_score'] ?? 0) >= 50 => 'bg-warning text-dark',
            ($summary['average_risk_score'] ?? 0) >= 25 => 'bg-info text-dark',
            default => 'bg-success',
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
    @endphp

    <div class="dashboard-page">
        <section class="dashboard-header">
            <div class="dashboard-heading">
                <div class="page-eyebrow">
                    GLOBAL PORT LOCATION DASHBOARD
                </div>

                <h1 class="page-title">
                    Pelabuhan Global
                </h1>

                <p class="page-description">
                    Pantau lokasi pelabuhan utama, koordinat, peta interaktif,
                    dan risiko estimasi negara terpilih.
                </p>
            </div>

            <form
                method="GET"
                action="{{ route('ports.index') }}"
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
                                @selected($selectedCountry && $selectedCountry->id === $country->id)
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
                    @if ($selectedCountry?->flag_url)
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
                        {{ $selectedCountry?->name ?? 'Negara belum tersedia' }}
                    </h2>

                    <p>
                        Pelabuhan utama dan lokasi logistik negara terpilih.
                    </p>
                </div>
            </div>

            <div class="country-overview-stats">
                <div class="country-stat">
                    <span>Total Pelabuhan</span>

                    <strong>
                        {{ number_format($summary['total_ports'] ?? 0, 0, ',', '.') }}
                    </strong>

                    <small>
                        Data negara dipilih
                    </small>
                </div>

                <div class="country-stat">
                    <span>Status Data</span>

                    <strong>
                        {{ $apiAvailable ? 'Terkini' : 'Tersimpan' }}
                    </strong>

                    <small>
                        {{ $apiAvailable ? 'Data terbaru' : 'Data cadangan' }}
                    </small>
                </div>

                <div class="country-stat">
                    <span>Rata-rata Risiko</span>

                    <strong>
                        {{ number_format($summary['average_risk_score'] ?? 0, 2, ',', '.') }}
                    </strong>

                    <small>
                        Skor risiko
                    </small>
                </div>

                <div class="country-stat">
                    <span>Risiko Tertinggi</span>

                    <strong>
                        {{ $summary['highest_risk_port'] ?? 'Belum tersedia' }}
                    </strong>

                    <small>
                        Skor {{ number_format($summary['highest_risk_score'] ?? 0, 2, ',', '.') }}
                    </small>
                </div>
            </div>
        </section>

        @if ($apiError)
            <section class="mt-4">
                <div class="alert alert-warning border-0 shadow-sm">
                    <i class="bi bi-info-circle me-2"></i>
                    Data terbaru belum dapat dimuat. Sistem menampilkan data tersimpan.
                </div>
            </section>
        @endif

        @if ($ports->isEmpty())
            <section class="mt-4">
                <div class="alert alert-warning border-0 shadow-sm">
                    <i class="bi bi-info-circle me-2"></i>
                    Data pelabuhan untuk negara ini belum tersedia.
                </div>
            </section>
        @endif

        <section class="risk-analysis-grid mt-4">
            <article class="analysis-card total-risk-card">
                <div class="analysis-card-header">
                    <div>
                        <span class="analysis-label">
                            Pelabuhan Dipilih
                        </span>

                        <strong class="total-risk-score">
                            {{ $selectedPort['name'] ?? 'Belum tersedia' }}
                        </strong>
                    </div>

                    <span class="badge {{ $riskBadgeClass }} px-3 py-2">
                        Risiko Rata-rata
                    </span>
                </div>

                <p class="analysis-description">
                    {{ $selectedPort['description'] ?? 'Data pelabuhan belum tersedia untuk negara ini.' }}
                </p>

                <div class="country-overview-stats">
                    <div class="country-stat">
                        <span>Kode</span>

                        <strong>
                            {{ $selectedPort['code'] ?? '-' }}
                        </strong>

                        <small>
                            Kode pelabuhan
                        </small>
                    </div>

                    <div class="country-stat">
                        <span>Kota</span>

                        <strong>
                            {{ $selectedPort['city'] ?? '-' }}
                        </strong>

                        <small>
                            Lokasi pelabuhan
                        </small>
                    </div>

                    <div class="country-stat">
                        <span>Sumber</span>

                        <strong>
                            {{ $selectedPort['source'] ?? '-' }}
                        </strong>

                        <small>
                            Sumber data
                        </small>
                    </div>

                    <div class="country-stat">
                        <span>Risk Score</span>

                        <strong>
                            {{ number_format($selectedPort['risk_score'] ?? 0, 2, ',', '.') }}
                        </strong>

                        <small>
                            Risiko estimasi
                        </small>
                    </div>
                </div>
            </article>

            <article class="analysis-card">
                <div class="analysis-heading">
                    <h3>
                        Peta Lokasi Interaktif
                    </h3>

                    <p>
                        Marker menampilkan lokasi pelabuhan negara terpilih.
                    </p>
                </div>

                <div
                    id="portsMap"
                    class="border rounded-4 overflow-hidden"
                    style="height: 300px; width: 100%;"
                ></div>
            </article>
        </section>

        <section class="risk-analysis-grid mt-4">
            <article
                class="analysis-card"
                style="grid-column: 1 / -1;"
            >
                <div class="analysis-heading">
                    <h3>
                        Grafik Risiko Pelabuhan
                    </h3>

                    <p>
                        Perbandingan skor risiko pelabuhan.
                    </p>
                </div>

                <div style="height: 180px;">
                    <canvas id="portRiskChart"></canvas>
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
                        Daftar Pelabuhan Utama
                    </h3>

                    <p>
                        Ringkasan pelabuhan, koordinat, sumber, dan risiko estimasi.
                    </p>
                </div>

                <div class="table-responsive">
                    <table class="table align-middle mb-0">
                        <thead>
                            <tr>
                                <th>Pelabuhan</th>
                                <th>Negara</th>
                                <th>Kota</th>
                                <th>Tipe</th>
                                <th>Koordinat</th>
                                <th>Sumber</th>
                                <th>Risiko</th>
                                <th>Aksi</th>
                            </tr>
                        </thead>

                        <tbody>
                            @forelse ($ports as $port)
                                <tr>
                                    <td>
                                        <strong>
                                            {{ $port['name'] ?? '-' }}
                                        </strong>

                                        <br>

                                        <small class="text-muted">
                                            {{ $port['code'] ?? '-' }}
                                        </small>
                                    </td>

                                    <td>
                                        {{ $port['country']['name'] ?? '-' }}
                                    </td>

                                    <td>
                                        {{ $port['city'] ?? '-' }}
                                    </td>

                                    <td>
                                        {{ $port['type'] ?? '-' }}
                                    </td>

                                    <td>
                                        {{ $port['latitude'] ?? '-' }},
                                        {{ $port['longitude'] ?? '-' }}
                                    </td>

                                    <td>
                                        {{ $port['source'] ?? '-' }}
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
                                        colspan="8"
                                        class="text-center text-muted py-4"
                                    >
                                        Data pelabuhan belum tersedia.
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

@push('styles')
    <link
        rel="stylesheet"
        href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css"
    >
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
                    if (!port.latitude || !port.longitude) {
                        return;
                    }

                    var marker = L.marker([
                        Number(port.latitude),
                        Number(port.longitude)
                    ]).bindPopup(
                        '<strong>' + (port.name || '-') + '</strong><br>' +
                        'Kode: ' + (port.code || '-') + '<br>' +
                        'Kota: ' + (port.city || '-') + '<br>' +
                        'Negara: ' + (port.country || '-') + '<br>' +
                        'Sumber: ' + (port.source || '-') + '<br>' +
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
                            label: 'Risk Score Pelabuhan',
                            data: chartData.risk ? chartData.risk.values : [],
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
                                    return 'Risk Score: ' + context.parsed.y;
                                }
                            }
                        }
                    }
                }
            });
        });
    </script>
@endpush
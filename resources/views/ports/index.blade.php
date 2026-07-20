@extends('layouts.app')

@section('title', 'Pelabuhan Global')

@section('content')
    @php
        $routePortOptions = collect($routePortOptions ?? []);

        $routeEstimatorData = $routeEstimator ?? [
            'available' => false,
            'message' => 'Pilih dua pelabuhan berbeda untuk menghitung estimasi rute.',
            'origin_port' => null,
            'destination_port' => null,
            'distance' => [
                'straight_km' => 0,
                'sea_km' => 0,
                'nautical_miles' => 0,
            ],
            'duration' => [
                'speed_knots' => 18,
                'hours' => 0,
                'days' => 0,
                'display' => 'Belum tersedia',
            ],
            'risk' => [
                'score' => 0,
                'level' => 'low',
                'label' => 'Belum dihitung',
                'recommendation' => 'Pilih port asal dan port tujuan terlebih dahulu.',
            ],
            'route_line' => [],
            'map_center' => null,
        ];

        $averageRisk = $summary['average_risk_score'] ?? 0;

        $riskBadgeClass = match (true) {
            $averageRisk >= 75 => 'risk-critical',
            $averageRisk >= 50 => 'risk-high',
            $averageRisk >= 25 => 'risk-medium',
            default => 'risk-low',
        };

        $selectedPortRiskScore = (float) ($selectedPort['risk_score'] ?? 0);

        $selectedPortRiskClass = match (true) {
            $selectedPortRiskScore >= 75 => 'risk-critical',
            $selectedPortRiskScore >= 50 => 'risk-high',
            $selectedPortRiskScore >= 25 => 'risk-medium',
            default => 'risk-low',
        };

        $routeRiskScore = (float) ($routeEstimatorData['risk']['score'] ?? 0);

        $routeRiskClass = match (true) {
            $routeRiskScore >= 75 => 'risk-critical',
            $routeRiskScore >= 50 => 'risk-high',
            $routeRiskScore >= 25 => 'risk-medium',
            default => 'risk-low',
        };

        $routeOriginValue = request('origin_port', $routeEstimatorData['origin_port']['id'] ?? '');
        $routeDestinationValue = request('destination_port', $routeEstimatorData['destination_port']['id'] ?? '');

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
                ];
            })
            ->values()
            ->all();

        $routeMapData = [
            'available' => $routeEstimatorData['available'] ?? false,
            'origin_port' => $routeEstimatorData['origin_port'] ?? null,
            'destination_port' => $routeEstimatorData['destination_port'] ?? null,
            'route_line' => $routeEstimatorData['route_line'] ?? [],
            'risk' => $routeEstimatorData['risk'] ?? [],
            'distance' => $routeEstimatorData['distance'] ?? [],
            'duration' => $routeEstimatorData['duration'] ?? [],
        ];

        $selectedLocation = [
            'latitude' => $defaultLatitude ?? -6.1045,
            'longitude' => $defaultLongitude ?? 106.8866,
        ];

        $tablePorts = $ports->take(25);
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
                    Pantau lokasi pelabuhan, risiko logistik, dan estimasi rute antar pelabuhan.
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
                    Negara dipilih
                </small>
            </article>

            <article class="ports-stat-card">
                <span>Rata-rata Risiko</span>

                <strong>
                    {{ number_format($summary['average_risk_score'] ?? 0, 2, ',', '.') }}
                </strong>

                <small>
                    Skor pelabuhan
                </small>
            </article>

            <article class="ports-stat-card">
                <span>Status Risiko</span>

                <strong>
                    <b class="{{ $riskBadgeClass }}">
                        {{ $averageRisk >= 75 ? 'Kritis' : ($averageRisk >= 50 ? 'Tinggi' : ($averageRisk >= 25 ? 'Sedang' : 'Rendah')) }}
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
        </section>

        <section class="ports-main-grid">
            <article class="ports-card ports-selected-card">
                <div class="ports-card-heading">
                    <span>Pelabuhan Dipilih</span>

                    <h2>
                        {{ $selectedPort['name'] ?? 'Belum tersedia' }}
                    </h2>
                </div>

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
                        <span>Weather</span>

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
                    <span>Peta Daftar Pelabuhan</span>

                    <h2>
                        Lokasi Pelabuhan {{ $selectedCountry?->name ?? '' }}
                    </h2>
                </div>

                <div id="portsListMap"></div>
            </article>
        </section>

        <section class="ports-data-grid">
            <article class="ports-card">
                <div class="ports-card-heading">
                    <span>Grafik Risiko</span>

                    <h2>
                        Top Risiko Pelabuhan
                    </h2>
                </div>

                <div class="ports-chart-box">
                    <canvas id="portRiskChart"></canvas>
                </div>
            </article>

            <article class="ports-card">
                <div class="ports-card-heading ports-table-heading">
                    <div>
                        <span>Daftar Pelabuhan</span>

                        <h2>
                            Pelabuhan Utama
                        </h2>
                    </div>

                    <small>
                        {{ $tablePorts->count() }} / {{ $ports->count() }} data
                    </small>
                </div>

                <div class="table-responsive ports-table-wrapper">
                    <table class="table align-middle mb-0 ports-table">
                        <thead>
                            <tr>
                                <th>Pelabuhan</th>
                                <th>Kota</th>
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
                                        colspan="4"
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

        <section class="ports-card ports-route-card">
            <div class="ports-card-heading">
                <span>Port Route Estimator</span>

                <h2>
                    Estimasi Rute Antar Pelabuhan
                </h2>

                <p>
                    Pilih port asal dan tujuan untuk menghitung estimasi jarak, waktu tempuh, dan risiko rute.
                </p>
            </div>

            <form
                method="GET"
                action="{{ route('ports.index') }}"
                class="ports-route-form"
            >
                <input
                    type="hidden"
                    name="country"
                    value="{{ $selectedCountry?->iso3_code ?? 'IDN' }}"
                >

                <input
                    type="hidden"
                    name="port"
                    value="{{ $selectedPortKeyword ?? '' }}"
                >

                <div>
                    <label for="origin_port">
                        Port Asal
                    </label>

                    <select
                        name="origin_port"
                        id="origin_port"
                        class="form-select"
                    >
                        @forelse ($routePortOptions as $port)
                            <option
                                value="{{ $port['id'] }}"
                                @selected((string) $routeOriginValue === (string) ($port['id'] ?? ''))
                            >
                                {{ $port['name'] ?? '-' }}
                                —
                                {{ $port['country']['name'] ?? '-' }}
                                ({{ $port['code'] ?? '-' }})
                            </option>
                        @empty
                            <option value="">
                                Data port belum tersedia
                            </option>
                        @endforelse
                    </select>
                </div>

                <div>
                    <label for="destination_port">
                        Port Tujuan
                    </label>

                    <select
                        name="destination_port"
                        id="destination_port"
                        class="form-select"
                    >
                        @forelse ($routePortOptions as $port)
                            <option
                                value="{{ $port['id'] }}"
                                @selected((string) $routeDestinationValue === (string) ($port['id'] ?? ''))
                            >
                                {{ $port['name'] ?? '-' }}
                                —
                                {{ $port['country']['name'] ?? '-' }}
                                ({{ $port['code'] ?? '-' }})
                            </option>
                        @empty
                            <option value="">
                                Data port belum tersedia
                            </option>
                        @endforelse
                    </select>
                </div>

                <button
                    type="submit"
                    class="btn btn-primary"
                >
                    Estimasi
                </button>
            </form>

            <div class="ports-route-layout">
                <article class="ports-route-map-card">
                    <div class="ports-card-heading">
                        <span>Peta Route Estimator</span>

                        <h2>
                            Jalur Estimasi Rute
                        </h2>
                    </div>

                    <div id="portsRouteMap"></div>
                </article>

                <article class="ports-route-result-card">
                    <div class="ports-route-result-grid">
                        <div>
                            <span>Port Asal</span>

                            <strong>
                                {{ $routeEstimatorData['origin_port']['name'] ?? 'Belum tersedia' }}
                            </strong>

                            <small>
                                {{ $routeEstimatorData['origin_port']['country']['name'] ?? '-' }}
                                •
                                {{ $routeEstimatorData['origin_port']['code'] ?? '-' }}
                            </small>
                        </div>

                        <div>
                            <span>Port Tujuan</span>

                            <strong>
                                {{ $routeEstimatorData['destination_port']['name'] ?? 'Belum tersedia' }}
                            </strong>

                            <small>
                                {{ $routeEstimatorData['destination_port']['country']['name'] ?? '-' }}
                                •
                                {{ $routeEstimatorData['destination_port']['code'] ?? '-' }}
                            </small>
                        </div>

                        <div>
                            <span>Jarak</span>

                            <strong>
                                {{ number_format($routeEstimatorData['distance']['sea_km'] ?? 0, 2, ',', '.') }} km
                            </strong>

                            <small>
                                {{ number_format($routeEstimatorData['distance']['nautical_miles'] ?? 0, 2, ',', '.') }} nautical miles
                            </small>
                        </div>

                        <div>
                            <span>Waktu</span>

                            <strong>
                                {{ $routeEstimatorData['duration']['display'] ?? 'Belum tersedia' }}
                            </strong>

                            <small>
                                Kecepatan {{ $routeEstimatorData['duration']['speed_knots'] ?? 18 }} knot
                            </small>
                        </div>

                        <div>
                            <span>Risk Rute</span>

                            <strong>
                                <b class="{{ $routeRiskClass }}">
                                    {{ number_format($routeRiskScore, 2, ',', '.') }}
                                </b>
                            </strong>

                            <small>
                                {{ $routeEstimatorData['risk']['label'] ?? 'Belum dihitung' }}
                            </small>
                        </div>

                        <div>
                            <span>Rekomendasi</span>

                            <strong>
                                {{ $routeEstimatorData['risk']['label'] ?? 'Belum dihitung' }}
                            </strong>

                            <small>
                                {{ $routeEstimatorData['risk']['recommendation'] ?? 'Pilih port asal dan tujuan terlebih dahulu.' }}
                            </small>
                        </div>
                    </div>

                    <div class="ports-route-note">
                        <i class="bi bi-info-circle"></i>

                        <span>
                            Estimasi memakai koordinat pelabuhan dan perhitungan jarak awal. Sistem ini bukan pelacakan kapal real-time.
                        </span>
                    </div>
                </article>
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
        .ports-page {
            width: 100%;
            max-width: 1120px;
            margin: 0 auto;
            padding: 14px 18px 24px;
            display: flex;
            flex-direction: column;
            gap: 12px;
        }

        .ports-top-grid {
            display: grid;
            grid-template-columns: minmax(0, 1fr) 280px;
            gap: 12px;
            align-items: end;
        }

        .ports-title-area {
            padding-left: 4px;
        }

        .ports-title-area h1 {
            margin: 0 0 4px;
            color: #111827;
            font-size: 1.6rem;
            font-weight: 900;
            line-height: 1.1;
        }

        .ports-title-area p {
            margin: 0;
            color: #7c8aa5;
            font-size: 0.83rem;
            line-height: 1.45;
            max-width: 720px;
        }

        .ports-country-mini-card,
        .ports-filter-card,
        .ports-stat-card,
        .ports-card,
        .ports-route-result-card,
        .ports-route-map-card {
            background: #ffffff;
            border: 1px solid rgba(148, 163, 184, 0.22);
            border-radius: 16px;
            box-shadow: 0 10px 24px rgba(15, 23, 42, 0.04);
            min-width: 0;
            overflow: hidden;
        }

        .ports-country-mini-card {
            display: flex;
            align-items: center;
            gap: 10px;
            padding: 10px 12px;
            min-height: 72px;
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
        .ports-selected-grid span,
        .ports-route-form label,
        .ports-route-result-grid span {
            display: block;
            margin-bottom: 3px;
            color: #7c8aa5;
            font-size: 0.66rem;
            font-weight: 850;
            text-transform: uppercase;
            letter-spacing: 0.035em;
        }

        .ports-country-mini-card strong {
            display: block;
            color: #111827;
            font-size: 0.92rem;
            font-weight: 900;
            line-height: 1.2;
        }

        .ports-country-mini-card small {
            display: block;
            color: #7c8aa5;
            font-size: 0.7rem;
            line-height: 1.3;
        }

        .ports-filter-card {
            width: fit-content;
            max-width: 100%;
            padding: 10px 12px;
        }

        .ports-filter {
            display: grid;
            grid-template-columns: 320px 240px 96px 82px;
            gap: 8px;
            align-items: center;
        }

        .ports-filter .form-select,
        .ports-filter .form-control,
        .ports-filter .btn,
        .ports-route-form .form-select,
        .ports-route-form .btn {
            height: 38px;
            border-radius: 10px;
            font-size: 0.8rem;
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
            font-size: 0.8rem;
            font-weight: 750;
        }

        .ports-summary-grid {
            display: grid;
            grid-template-columns: repeat(4, minmax(0, 1fr));
            gap: 10px;
        }

        .ports-stat-card {
            padding: 12px 14px;
        }

        .ports-stat-card strong {
            display: block;
            color: #111827;
            font-size: 0.93rem;
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
        .ports-route-result-grid b,
        .risk-low,
        .risk-medium,
        .risk-high,
        .risk-critical {
            display: inline-flex;
            justify-content: center;
            align-items: center;
            min-width: 86px;
            padding: 5px 8px;
            border-radius: 999px;
            font-size: 0.66rem;
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

        .ports-card,
        .ports-route-result-card,
        .ports-route-map-card {
            padding: 14px;
        }

        .ports-card-heading {
            margin-bottom: 10px;
        }

        .ports-card-heading h2 {
            margin: 0;
            color: #111827;
            font-size: 0.96rem;
            font-weight: 900;
            line-height: 1.25;
        }

        .ports-card-heading p {
            margin: 4px 0 0;
            color: #64748b;
            font-size: 0.74rem;
            line-height: 1.4;
        }

        .ports-main-grid {
            display: grid;
            grid-template-columns: 0.78fr 1.22fr;
            gap: 10px;
        }

        .ports-selected-grid {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 8px;
        }

        .ports-selected-grid > div,
        .ports-route-result-grid > div {
            padding: 9px;
            border-radius: 12px;
            background: #f8fafc;
            border: 1px solid rgba(148, 163, 184, 0.14);
            min-width: 0;
        }

        .ports-selected-grid strong,
        .ports-route-result-grid strong {
            display: block;
            color: #111827;
            font-size: 0.78rem;
            font-weight: 850;
            line-height: 1.3;
            word-break: break-word;
        }

        .ports-route-result-grid small {
            display: block;
            margin-top: 4px;
            color: #64748b;
            font-size: 0.69rem;
            line-height: 1.35;
        }

        #portsListMap,
        #portsRouteMap {
            width: 100%;
            border-radius: 14px;
            border: 1px solid rgba(148, 163, 184, 0.18);
            overflow: hidden;
        }

        #portsListMap {
            height: 305px;
        }

        #portsRouteMap {
            height: 315px;
        }

        .ports-data-grid {
            display: grid;
            grid-template-columns: 0.9fr 1.1fr;
            gap: 10px;
            align-items: start;
        }

        .ports-chart-box {
            width: 100%;
            height: 210px;
        }

        .ports-table-heading {
            display: flex;
            justify-content: space-between;
            gap: 10px;
            align-items: flex-start;
        }

        .ports-table-heading small {
            color: #7c8aa5;
            font-size: 0.7rem;
            font-weight: 800;
            white-space: nowrap;
        }

        .ports-table-wrapper {
            border: 1px solid rgba(148, 163, 184, 0.18);
            border-radius: 14px;
            overflow: auto;
            max-height: 285px;
        }

        .ports-table {
            min-width: 620px;
        }

        .ports-table thead th {
            background: #f8fafc;
            color: #64748b;
            font-size: 0.7rem;
            font-weight: 900;
            text-transform: uppercase;
            letter-spacing: 0.035em;
            white-space: nowrap;
        }

        .ports-table tbody td {
            color: #334155;
            font-size: 0.78rem;
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

        .ports-route-card {
            margin-top: 4px;
        }

        .ports-route-form {
            display: grid;
            grid-template-columns: minmax(0, 1fr) minmax(0, 1fr) 108px;
            gap: 10px;
            align-items: end;
            margin-top: 12px;
        }

        .ports-route-layout {
            display: grid;
            grid-template-columns: 1.15fr 0.85fr;
            gap: 10px;
            margin-top: 12px;
            align-items: start;
        }

        .ports-route-result-grid {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 8px;
        }

        .ports-route-note {
            display: flex;
            align-items: flex-start;
            gap: 8px;
            margin-top: 10px;
            padding: 9px;
            border-radius: 12px;
            background: #f8fafc;
            color: #64748b;
            border: 1px solid rgba(148, 163, 184, 0.14);
            font-size: 0.72rem;
            line-height: 1.4;
        }

        @media (max-width: 1280px) {
            .ports-summary-grid {
                grid-template-columns: repeat(2, minmax(0, 1fr));
            }

            .ports-main-grid,
            .ports-data-grid,
            .ports-route-layout {
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

            .ports-filter,
            .ports-route-form {
                grid-template-columns: 1fr;
            }

            .ports-filter .btn,
            .ports-route-form .btn {
                width: 100%;
            }
        }

        @media (max-width: 720px) {
            .ports-page {
                padding: 12px;
            }

            .ports-summary-grid,
            .ports-selected-grid,
            .ports-route-result-grid {
                grid-template-columns: 1fr;
            }

            .ports-title-area {
                padding-left: 0;
            }

            .ports-title-area h1 {
                font-size: 1.45rem;
            }

            #portsListMap,
            #portsRouteMap {
                height: 280px;
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

    <script
        id="portRouteData"
        type="application/json"
    >{!! json_encode($routeMapData, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT) !!}</script>

    <script>
        document.addEventListener('DOMContentLoaded', function () {
            function parseJsonElement(id, fallback) {
                var element = document.getElementById(id);

                if (!element) {
                    return fallback;
                }

                try {
                    return JSON.parse(element.textContent || '');
                } catch (error) {
                    return fallback;
                }
            }

            function escapeHtml(value) {
                return String(value || '')
                    .replace(/&/g, '&amp;')
                    .replace(/</g, '&lt;')
                    .replace(/>/g, '&gt;')
                    .replace(/"/g, '&quot;')
                    .replace(/'/g, '&#039;');
            }

            var ports = parseJsonElement('portMapData', []);
            var selectedLocation = parseJsonElement('portSelectedLocation', {
                latitude: -6.1045,
                longitude: 106.8866
            });

            var routeData = parseJsonElement('portRouteData', {
                available: false,
                route_line: []
            });

            var defaultLatitude = Number(selectedLocation.latitude || -6.1045);
            var defaultLongitude = Number(selectedLocation.longitude || 106.8866);

            function buildPortPopup(port) {
                return '<strong>' + escapeHtml(port.name || '-') + '</strong><br>' +
                    'Kode: ' + escapeHtml(port.code || '-') + '<br>' +
                    'Kota: ' + escapeHtml(port.city || '-') + '<br>' +
                    'Negara: ' + escapeHtml(port.country || '-') + '<br>' +
                    'Risk Score: ' + escapeHtml(port.risk_score || 0);
            }

            function createBaseMap(elementId, zoom) {
                var element = document.getElementById(elementId);

                if (!element || typeof L === 'undefined') {
                    return null;
                }

                var map = L.map(elementId, {
                    scrollWheelZoom: true,
                    dragging: true,
                    zoomControl: true
                }).setView([defaultLatitude, defaultLongitude], zoom);

                L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                    maxZoom: 19,
                    attribution: '&copy; OpenStreetMap'
                }).addTo(map);

                return map;
            }

            var listMap = createBaseMap('portsListMap', 6);

            if (listMap) {
                var listMarkerGroup = L.featureGroup().addTo(listMap);

                ports.forEach(function (port) {
                    var latitude = Number(port.latitude);
                    var longitude = Number(port.longitude);

                    if (
                        Number.isNaN(latitude) ||
                        Number.isNaN(longitude)
                    ) {
                        return;
                    }

                    L.marker([
                        latitude,
                        longitude
                    ])
                        .bindPopup(buildPortPopup(port))
                        .addTo(listMarkerGroup);
                });

                if (listMarkerGroup.getLayers().length > 1) {
                    listMap.fitBounds(listMarkerGroup.getBounds(), {
                        padding: [28, 28]
                    });
                } else if (listMarkerGroup.getLayers().length === 1) {
                    listMap.setView(listMarkerGroup.getLayers()[0].getLatLng(), 8);
                }

                setTimeout(function () {
                    listMap.invalidateSize();
                }, 300);
            }

            var routeMap = createBaseMap('portsRouteMap', 4);

            if (routeMap) {
                var routeMarkerGroup = L.featureGroup().addTo(routeMap);

                if (
                    routeData.available &&
                    Array.isArray(routeData.route_line) &&
                    routeData.route_line.length === 2
                ) {
                    var originPoint = routeData.route_line[0];
                    var destinationPoint = routeData.route_line[1];

                    var routePoints = [
                        [
                            Number(originPoint.latitude),
                            Number(originPoint.longitude)
                        ],
                        [
                            Number(destinationPoint.latitude),
                            Number(destinationPoint.longitude)
                        ]
                    ];

                    var routeLine = L.polyline(routePoints, {
                        weight: 4,
                        opacity: 0.85,
                        dashArray: '8, 8'
                    }).addTo(routeMap);

                    if (routeData.origin_port) {
                        L.marker(routePoints[0])
                            .bindPopup(
                                '<strong>Port Asal</strong><br>' +
                                escapeHtml(routeData.origin_port.name || '-') + '<br>' +
                                'Negara: ' + escapeHtml(routeData.origin_port.country ? routeData.origin_port.country.name : '-') + '<br>' +
                                'Risk: ' + escapeHtml(routeData.origin_port.risk_score || 0)
                            )
                            .addTo(routeMarkerGroup);
                    }

                    if (routeData.destination_port) {
                        L.marker(routePoints[1])
                            .bindPopup(
                                '<strong>Port Tujuan</strong><br>' +
                                escapeHtml(routeData.destination_port.name || '-') + '<br>' +
                                'Negara: ' + escapeHtml(routeData.destination_port.country ? routeData.destination_port.country.name : '-') + '<br>' +
                                'Risk: ' + escapeHtml(routeData.destination_port.risk_score || 0)
                            )
                            .addTo(routeMarkerGroup);
                    }

                    routeMap.fitBounds(routeLine.getBounds(), {
                        padding: [35, 35]
                    });
                } else {
                    routeMap.setView([defaultLatitude, defaultLongitude], 4);
                }

                setTimeout(function () {
                    routeMap.invalidateSize();
                }, 300);
            }

            if (typeof Chart === 'undefined') {
                return;
            }

            var chartData = parseJsonElement('portChartData', {});
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
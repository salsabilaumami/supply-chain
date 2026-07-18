@extends('layouts.app')

@section('title', 'Perbandingan Negara')

@section('content')
    @php
        $selectedCountryA = $selectedCountryA ?? $countryA ?? null;
        $selectedCountryB = $selectedCountryB ?? $countryB ?? null;

        $countryAData = $countryAData ?? ($comparison['country_a'] ?? []);
        $countryBData = $countryBData ?? ($comparison['country_b'] ?? []);

        $countryAName = $selectedCountryA?->name ?? ($countryAData['country']['name'] ?? 'Negara A');
        $countryBName = $selectedCountryB?->name ?? ($countryBData['country']['name'] ?? 'Negara B');

        $countryAIso = $selectedCountryA?->iso3_code ?? ($countryAData['country']['iso3_code'] ?? 'IDN');
        $countryBIso = $selectedCountryB?->iso3_code ?? ($countryBData['country']['iso3_code'] ?? 'SGP');

        $countryAFlag = $selectedCountryA?->flag_url ?? ($countryAData['country']['flag_url'] ?? null);
        $countryBFlag = $selectedCountryB?->flag_url ?? ($countryBData['country']['flag_url'] ?? null);

        $countryARegion = $selectedCountryA?->region ?? ($countryAData['country']['region'] ?? '-');
        $countryBRegion = $selectedCountryB?->region ?? ($countryBData['country']['region'] ?? '-');

        $countryAScore = $countryAData['risk_score']['total_score'] ?? 0;
        $countryBScore = $countryBData['risk_score']['total_score'] ?? 0;

        $weatherA = $countryAData['risk_score']['weather_score'] ?? 0;
        $weatherB = $countryBData['risk_score']['weather_score'] ?? 0;

        $inflationA = $countryAData['risk_score']['inflation_score'] ?? 0;
        $inflationB = $countryBData['risk_score']['inflation_score'] ?? 0;

        $currencyA = $countryAData['risk_score']['currency_score'] ?? 0;
        $currencyB = $countryBData['risk_score']['currency_score'] ?? 0;

        $newsA = $countryAData['risk_score']['news_score'] ?? 0;
        $newsB = $countryBData['risk_score']['news_score'] ?? 0;

        $gdpA = $countryAData['economic']['gdp']['display_value'] ?? 'Belum tersedia';
        $gdpB = $countryBData['economic']['gdp']['display_value'] ?? 'Belum tersedia';

        $inflationValueA = $countryAData['economic']['inflation']['display_value'] ?? 'Belum tersedia';
        $inflationValueB = $countryBData['economic']['inflation']['display_value'] ?? 'Belum tersedia';

        $currencyCodeA = $selectedCountryA?->currency_code ?? ($countryAData['country']['currency_code'] ?? '-');
        $currencyCodeB = $selectedCountryB?->currency_code ?? ($countryBData['country']['currency_code'] ?? '-');

        $riskLabel = function ($score) {
            return match (true) {
                $score >= 75 => 'Risiko Kritis',
                $score >= 50 => 'Risiko Tinggi',
                $score >= 25 => 'Risiko Sedang',
                default => 'Risiko Rendah',
            };
        };

        $riskClass = function ($score) {
            return match (true) {
                $score >= 75 => 'risk-critical',
                $score >= 50 => 'risk-high',
                $score >= 25 => 'risk-medium',
                default => 'risk-low',
            };
        };

        $comparisonChartData = $comparisonChartData ?? $chartData ?? [
            'risk' => [
                'labels' => [$countryAIso, $countryBIso],
                'values' => [
                    round((float) $countryAScore, 2),
                    round((float) $countryBScore, 2),
                ],
            ],
            'economic' => [
                'labels' => ['GDP', 'Ekspor', 'Impor', 'Inflasi', 'Populasi'],
                'country_a' => [
                    round((float) ($countryAData['economic']['gdp']['value'] ?? 0) / 1000000000000, 2),
                    round((float) ($countryAData['economic']['exports']['value'] ?? 0) / 1000000000, 2),
                    round((float) ($countryAData['economic']['imports']['value'] ?? 0) / 1000000000, 2),
                    round((float) ($countryAData['economic']['inflation']['value'] ?? 0), 2),
                    round((float) ($countryAData['economic']['population']['value'] ?? 0) / 1000000, 2),
                ],
                'country_b' => [
                    round((float) ($countryBData['economic']['gdp']['value'] ?? 0) / 1000000000000, 2),
                    round((float) ($countryBData['economic']['exports']['value'] ?? 0) / 1000000000, 2),
                    round((float) ($countryBData['economic']['imports']['value'] ?? 0) / 1000000000, 2),
                    round((float) ($countryBData['economic']['inflation']['value'] ?? 0), 2),
                    round((float) ($countryBData['economic']['population']['value'] ?? 0) / 1000000, 2),
                ],
                'country_a_label' => $countryAIso,
                'country_b_label' => $countryBIso,
            ],
            'operational' => [
                'labels' => ['Cuaca', 'Inflasi', 'Kurs', 'Berita'],
                'country_a' => [
                    round((float) $weatherA, 2),
                    round((float) $inflationA, 2),
                    round((float) $currencyA, 2),
                    round((float) $newsA, 2),
                ],
                'country_b' => [
                    round((float) $weatherB, 2),
                    round((float) $inflationB, 2),
                    round((float) $currencyB, 2),
                    round((float) $newsB, 2),
                ],
                'country_a_label' => $countryAIso,
                'country_b_label' => $countryBIso,
            ],
            'labels' => [
                'country_a' => $countryAIso,
                'country_b' => $countryBIso,
            ],
        ];
    @endphp

    <div class="comparison-page">
        <section class="comparison-top-grid">
            <div class="comparison-title-area">
                <div class="page-eyebrow">
                    COUNTRY COMPARISON ENGINE
                </div>

                <h1>
                    Perbandingan Negara
                </h1>

                <p>
                    Bandingkan GDP, inflasi, cuaca, kurs, berita, dan total Risk Score dari dua negara.
                </p>
            </div>

            <form
                method="GET"
                action="{{ route('comparison.index') }}"
                class="comparison-filter-card"
            >
                <select
                    name="country_a"
                    class="form-select"
                >
                    @foreach ($countries as $country)
                        <option
                            value="{{ $country->iso3_code }}"
                            @selected($country->iso3_code === $countryAIso)
                        >
                            {{ $country->name }} ({{ $country->iso3_code }})
                        </option>
                    @endforeach
                </select>

                <select
                    name="country_b"
                    class="form-select"
                >
                    @foreach ($countries as $country)
                        <option
                            value="{{ $country->iso3_code }}"
                            @selected($country->iso3_code === $countryBIso)
                        >
                            {{ $country->name }} ({{ $country->iso3_code }})
                        </option>
                    @endforeach
                </select>

                <button
                    type="submit"
                    class="btn btn-primary"
                >
                    Bandingkan
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

        <section class="comparison-country-grid">
            <article class="comparison-country-card">
                <div class="comparison-country-main">
                    <div class="comparison-flag">
                        @if ($countryAFlag)
                            <img
                                src="{{ $countryAFlag }}"
                                alt="Bendera {{ $countryAName }}"
                            >
                        @else
                            <div class="comparison-flag-placeholder">
                                <i class="bi bi-flag"></i>
                            </div>
                        @endif
                    </div>

                    <div>
                        <span>
                            Negara A
                        </span>

                        <strong>
                            {{ $countryAName }}
                        </strong>

                        <small>
                            {{ $countryAIso }} • {{ $countryARegion }}
                        </small>
                    </div>
                </div>

                <div class="comparison-score-box">
                    <strong>
                        {{ number_format((float) $countryAScore, 2, ',', '.') }}
                    </strong>

                    <b class="{{ $riskClass($countryAScore) }}">
                        {{ $riskLabel($countryAScore) }}
                    </b>
                </div>
            </article>

            <article class="comparison-country-card">
                <div class="comparison-country-main">
                    <div class="comparison-flag">
                        @if ($countryBFlag)
                            <img
                                src="{{ $countryBFlag }}"
                                alt="Bendera {{ $countryBName }}"
                            >
                        @else
                            <div class="comparison-flag-placeholder">
                                <i class="bi bi-flag"></i>
                            </div>
                        @endif
                    </div>

                    <div>
                        <span>
                            Negara B
                        </span>

                        <strong>
                            {{ $countryBName }}
                        </strong>

                        <small>
                            {{ $countryBIso }} • {{ $countryBRegion }}
                        </small>
                    </div>
                </div>

                <div class="comparison-score-box">
                    <strong>
                        {{ number_format((float) $countryBScore, 2, ',', '.') }}
                    </strong>

                    <b class="{{ $riskClass($countryBScore) }}">
                        {{ $riskLabel($countryBScore) }}
                    </b>
                </div>
            </article>
        </section>

        <section class="comparison-metric-grid">
            <article class="comparison-metric-card">
                <span>GDP</span>

                <strong>
                    {{ $gdpA }}
                </strong>

                <small>
                    {{ $countryAIso }}
                </small>
            </article>

            <article class="comparison-metric-card">
                <span>GDP</span>

                <strong>
                    {{ $gdpB }}
                </strong>

                <small>
                    {{ $countryBIso }}
                </small>
            </article>

            <article class="comparison-metric-card">
                <span>Inflasi</span>

                <strong>
                    {{ $inflationValueA }}
                    /
                    {{ $inflationValueB }}
                </strong>

                <small>
                    Nilai ekonomi
                </small>
            </article>

            <article class="comparison-metric-card">
                <span>Mata Uang</span>

                <strong>
                    {{ $currencyCodeA }}
                    /
                    {{ $currencyCodeB }}
                </strong>

                <small>
                    Kode kurs
                </small>
            </article>
        </section>

        <section class="comparison-metric-grid">
            <article class="comparison-metric-card">
                <span>Cuaca</span>

                <strong>
                    {{ number_format((float) $weatherA, 2, ',', '.') }}
                    /
                    {{ number_format((float) $weatherB, 2, ',', '.') }}
                </strong>

                <small>
                    Risiko cuaca
                </small>
            </article>

            <article class="comparison-metric-card">
                <span>Inflasi Risk</span>

                <strong>
                    {{ number_format((float) $inflationA, 2, ',', '.') }}
                    /
                    {{ number_format((float) $inflationB, 2, ',', '.') }}
                </strong>

                <small>
                    Risiko inflasi
                </small>
            </article>

            <article class="comparison-metric-card">
                <span>Kurs Risk</span>

                <strong>
                    {{ number_format((float) $currencyA, 2, ',', '.') }}
                    /
                    {{ number_format((float) $currencyB, 2, ',', '.') }}
                </strong>

                <small>
                    Risiko mata uang
                </small>
            </article>

            <article class="comparison-metric-card">
                <span>Berita</span>

                <strong>
                    {{ number_format((float) $newsA, 2, ',', '.') }}
                    /
                    {{ number_format((float) $newsB, 2, ',', '.') }}
                </strong>

                <small>
                    Sentimen berita
                </small>
            </article>
        </section>

        <section class="comparison-chart-grid">
            <article class="comparison-chart-card">
                <div class="comparison-card-heading">
                    <span>Risk Score</span>

                    <h2>
                        Risiko Total
                    </h2>
                </div>

                <div class="chart-compact-box">
                    <canvas id="comparisonRiskChart"></canvas>
                </div>
            </article>

            <article class="comparison-chart-card">
                <div class="comparison-card-heading">
                    <span>Economic</span>

                    <h2>
                        Indikator Ekonomi
                    </h2>
                </div>

                <div class="chart-compact-box">
                    <canvas id="comparisonEconomicChart"></canvas>
                </div>
            </article>

            <article class="comparison-chart-card">
                <div class="comparison-card-heading">
                    <span>Operational</span>

                    <h2>
                        Risiko Operasional
                    </h2>
                </div>

                <div class="chart-compact-box">
                    <canvas id="comparisonOperationalChart"></canvas>
                </div>
            </article>
        </section>

        <section class="comparison-detail-card">
            <div class="comparison-card-heading">
                <span>Ringkasan Detail</span>

                <h2>
                    Perbandingan Komponen Risiko
                </h2>
            </div>

            <div class="table-responsive comparison-table-wrapper">
                <table class="table align-middle mb-0 comparison-table">
                    <thead>
                        <tr>
                            <th>Komponen</th>
                            <th>{{ $countryAIso }}</th>
                            <th>{{ $countryBIso }}</th>
                            <th>Keterangan</th>
                        </tr>
                    </thead>

                    <tbody>
                        <tr>
                            <td>Total Risiko</td>
                            <td>{{ number_format((float) $countryAScore, 2, ',', '.') }}</td>
                            <td>{{ number_format((float) $countryBScore, 2, ',', '.') }}</td>
                            <td>Skor utama risiko rantai pasok.</td>
                        </tr>

                        <tr>
                            <td>Risiko Cuaca</td>
                            <td>{{ number_format((float) $weatherA, 2, ',', '.') }}</td>
                            <td>{{ number_format((float) $weatherB, 2, ',', '.') }}</td>
                            <td>Risiko dari kondisi cuaca.</td>
                        </tr>

                        <tr>
                            <td>Risiko Inflasi</td>
                            <td>{{ number_format((float) $inflationA, 2, ',', '.') }}</td>
                            <td>{{ number_format((float) $inflationB, 2, ',', '.') }}</td>
                            <td>Risiko tekanan ekonomi.</td>
                        </tr>

                        <tr>
                            <td>Risiko Mata Uang</td>
                            <td>{{ number_format((float) $currencyA, 2, ',', '.') }}</td>
                            <td>{{ number_format((float) $currencyB, 2, ',', '.') }}</td>
                            <td>Risiko perubahan nilai tukar.</td>
                        </tr>

                        <tr>
                            <td>Risiko Berita</td>
                            <td>{{ number_format((float) $newsA, 2, ',', '.') }}</td>
                            <td>{{ number_format((float) $newsB, 2, ',', '.') }}</td>
                            <td>Risiko dari sentimen berita.</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </section>
    </div>
@endsection

@push('styles')
    <style>
        .comparison-page {
            width: 100%;
            max-width: 1180px;
            margin: 0 auto;
            padding: 14px 18px 24px;
            display: flex;
            flex-direction: column;
            gap: 10px;
        }

        .comparison-top-grid {
            display: grid;
            grid-template-columns: minmax(0, 1fr) minmax(560px, 700px);
            gap: 12px;
            align-items: end;
        }

        .comparison-title-area {
            padding-left: 6px;
        }

        .comparison-title-area h1 {
            margin: 0 0 4px;
            color: #111827;
            font-size: 1.55rem;
            font-weight: 900;
            line-height: 1.1;
        }

        .comparison-title-area p {
            margin: 0;
            color: #7c8aa5;
            font-size: 0.82rem;
            line-height: 1.42;
            max-width: 660px;
        }

        .comparison-filter-card,
        .comparison-country-card,
        .comparison-metric-card,
        .comparison-chart-card,
        .comparison-detail-card {
            background: #ffffff;
            border: 1px solid rgba(148, 163, 184, 0.22);
            border-radius: 15px;
            box-shadow: 0 10px 24px rgba(15, 23, 42, 0.04);
        }

        .comparison-filter-card {
            display: grid;
            grid-template-columns: minmax(0, 1fr) minmax(0, 1fr) 100px 100px;
            gap: 7px;
            align-items: center;
            padding: 9px 10px;
        }

        .comparison-filter-card .form-select,
        .comparison-filter-card .btn {
            height: 36px;
            border-radius: 9px;
            font-size: 0.78rem;
            font-weight: 800;
        }

        .comparison-country-grid {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 10px;
        }

        .comparison-country-card {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 12px;
            padding: 11px 12px;
        }

        .comparison-country-main {
            display: flex;
            align-items: center;
            gap: 10px;
            min-width: 0;
        }

        .comparison-flag {
            width: 40px;
            height: 27px;
            border-radius: 8px;
            overflow: hidden;
            background: #e2e8f0;
            flex: 0 0 auto;
        }

        .comparison-flag img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }

        .comparison-flag-placeholder {
            width: 100%;
            height: 100%;
            display: grid;
            place-items: center;
            color: #64748b;
        }

        .comparison-country-main span,
        .comparison-metric-card span,
        .comparison-card-heading span {
            display: block;
            margin-bottom: 3px;
            color: #7c8aa5;
            font-size: 0.66rem;
            font-weight: 800;
            text-transform: uppercase;
            letter-spacing: 0.035em;
        }

        .comparison-country-main strong {
            display: block;
            color: #111827;
            font-size: 0.95rem;
            font-weight: 900;
            line-height: 1.2;
        }

        .comparison-country-main small {
            display: block;
            color: #7c8aa5;
            font-size: 0.7rem;
            line-height: 1.3;
        }

        .comparison-score-box {
            text-align: right;
            flex: 0 0 auto;
        }

        .comparison-score-box strong {
            display: block;
            color: #111827;
            font-size: 1.08rem;
            font-weight: 900;
            line-height: 1;
            margin-bottom: 5px;
        }

        .comparison-score-box b {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            min-width: 104px;
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

        .comparison-metric-grid {
            display: grid;
            grid-template-columns: repeat(4, minmax(0, 1fr));
            gap: 10px;
        }

        .comparison-metric-card {
            padding: 11px 12px;
            min-width: 0;
        }

        .comparison-metric-card strong {
            display: block;
            color: #111827;
            font-size: 0.92rem;
            font-weight: 900;
            line-height: 1.2;
            word-break: break-word;
        }

        .comparison-metric-card small {
            display: block;
            margin-top: 4px;
            color: #7c8aa5;
            font-size: 0.68rem;
        }

        .comparison-chart-grid {
            display: grid;
            grid-template-columns: repeat(3, minmax(0, 1fr));
            gap: 10px;
        }

        .comparison-chart-card {
            padding: 12px;
            min-width: 0;
        }

        .comparison-card-heading {
            margin-bottom: 8px;
        }

        .comparison-card-heading h2 {
            margin: 0;
            color: #111827;
            font-size: 0.94rem;
            font-weight: 900;
            line-height: 1.25;
        }

        .chart-compact-box {
            width: 100%;
            height: 145px;
        }

        .comparison-detail-card {
            padding: 12px;
        }

        .comparison-table-wrapper {
            border: 1px solid rgba(148, 163, 184, 0.18);
            border-radius: 13px;
            overflow: auto;
        }

        .comparison-table {
            min-width: 720px;
        }

        .comparison-table thead th {
            background: #f8fafc;
            color: #64748b;
            font-size: 0.7rem;
            font-weight: 900;
            text-transform: uppercase;
            letter-spacing: 0.035em;
            white-space: nowrap;
        }

        .comparison-table tbody td {
            color: #334155;
            font-size: 0.78rem;
            border-bottom: 1px solid rgba(148, 163, 184, 0.14);
        }

        @media (max-width: 1280px) {
            .comparison-top-grid,
            .comparison-chart-grid {
                grid-template-columns: 1fr;
            }

            .comparison-filter-card {
                grid-template-columns: 1fr 1fr auto auto;
            }
        }

        @media (max-width: 860px) {
            .comparison-country-grid,
            .comparison-metric-grid,
            .comparison-filter-card {
                grid-template-columns: 1fr;
            }

            .comparison-country-card {
                align-items: flex-start;
                flex-direction: column;
            }

            .comparison-score-box {
                text-align: left;
            }

            .comparison-filter-card .btn {
                width: 100%;
            }
        }

        @media (max-width: 720px) {
            .comparison-page {
                padding: 12px;
            }

            .comparison-title-area {
                padding-left: 0;
            }

            .comparison-title-area h1 {
                font-size: 1.4rem;
            }
        }
    </style>
@endpush

@push('scripts')
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

    <script
        id="comparisonChartData"
        type="application/json"
    >{!! json_encode($comparisonChartData, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT) !!}</script>

    <script>
        document.addEventListener('DOMContentLoaded', function () {
            if (typeof Chart === 'undefined') {
                return;
            }

            var chartDataElement = document.getElementById('comparisonChartData');
            var chartData = {};

            try {
                chartData = JSON.parse(chartDataElement.textContent || '{}');
            } catch (error) {
                chartData = {};
            }

            var countryALabel = chartData.labels ? chartData.labels.country_a : 'Negara A';
            var countryBLabel = chartData.labels ? chartData.labels.country_b : 'Negara B';

            var chartOptions = {
                responsive: true,
                maintainAspectRatio: false,
                layout: {
                    padding: 0
                },
                scales: {
                    x: {
                        ticks: {
                            font: {
                                size: 9
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
            };

            var riskCanvas = document.getElementById('comparisonRiskChart');

            if (riskCanvas) {
                new Chart(riskCanvas, {
                    type: 'bar',
                    data: {
                        labels: chartData.risk ? chartData.risk.labels : [],
                        datasets: [
                            {
                                label: 'Total',
                                data: chartData.risk ? chartData.risk.values : [],
                                borderWidth: 1,
                                borderRadius: 6,
                                maxBarThickness: 26
                            }
                        ]
                    },
                    options: chartOptions
                });
            }

            var economicCanvas = document.getElementById('comparisonEconomicChart');

            if (economicCanvas) {
                new Chart(economicCanvas, {
                    type: 'bar',
                    data: {
                        labels: chartData.economic ? chartData.economic.labels : [],
                        datasets: [
                            {
                                label: countryALabel,
                                data: chartData.economic ? chartData.economic.country_a : [],
                                borderWidth: 1,
                                borderRadius: 5,
                                maxBarThickness: 20
                            },
                            {
                                label: countryBLabel,
                                data: chartData.economic ? chartData.economic.country_b : [],
                                borderWidth: 1,
                                borderRadius: 5,
                                maxBarThickness: 20
                            }
                        ]
                    },
                    options: chartOptions
                });
            }

            var operationalCanvas = document.getElementById('comparisonOperationalChart');

            if (operationalCanvas) {
                new Chart(operationalCanvas, {
                    type: 'bar',
                    data: {
                        labels: chartData.operational ? chartData.operational.labels : [],
                        datasets: [
                            {
                                label: countryALabel,
                                data: chartData.operational ? chartData.operational.country_a : [],
                                borderWidth: 1,
                                borderRadius: 5,
                                maxBarThickness: 20
                            },
                            {
                                label: countryBLabel,
                                data: chartData.operational ? chartData.operational.country_b : [],
                                borderWidth: 1,
                                borderRadius: 5,
                                maxBarThickness: 20
                            }
                        ]
                    },
                    options: chartOptions
                });
            }
        });
    </script>
@endpush
@extends('layouts.app')

@section('title', 'Perbandingan Negara')

@section('content')
    @php
        $selectedCountryA = $selectedCountryA ?? $countryA ?? ($comparison['country_a']['country'] ?? null);
        $selectedCountryB = $selectedCountryB ?? $countryB ?? ($comparison['country_b']['country'] ?? null);

        $countryAData = $countryAData ?? ($comparison['country_a'] ?? []);
        $countryBData = $countryBData ?? ($comparison['country_b'] ?? []);

        $countryAName = $selectedCountryA?->name ?? ($countryAData['country']['name'] ?? 'Negara A');
        $countryBName = $selectedCountryB?->name ?? ($countryBData['country']['name'] ?? 'Negara B');

        $countryAOfficialName = $selectedCountryA?->official_name ?? ($countryAData['country']['official_name'] ?? $countryAName);
        $countryBOfficialName = $selectedCountryB?->official_name ?? ($countryBData['country']['official_name'] ?? $countryBName);

        $countryAIso = $selectedCountryA?->iso3_code ?? ($countryAData['country']['iso3_code'] ?? 'IDN');
        $countryBIso = $selectedCountryB?->iso3_code ?? ($countryBData['country']['iso3_code'] ?? 'SGP');

        $countryAScore = $countryAData['risk_score']['total_score']
            ?? $countryAData['risk']['total_score']
            ?? $countryAData['total_score']
            ?? 0;

        $countryBScore = $countryBData['risk_score']['total_score']
            ?? $countryBData['risk']['total_score']
            ?? $countryBData['total_score']
            ?? 0;

        $weatherA = $countryAData['risk_score']['weather_score'] ?? $countryAData['weather_score'] ?? 0;
        $weatherB = $countryBData['risk_score']['weather_score'] ?? $countryBData['weather_score'] ?? 0;

        $inflationA = $countryAData['risk_score']['inflation_score'] ?? $countryAData['inflation_score'] ?? 0;
        $inflationB = $countryBData['risk_score']['inflation_score'] ?? $countryBData['inflation_score'] ?? 0;

        $currencyA = $countryAData['risk_score']['currency_score'] ?? $countryAData['currency_score'] ?? 0;
        $currencyB = $countryBData['risk_score']['currency_score'] ?? $countryBData['currency_score'] ?? 0;

        $newsA = $countryAData['risk_score']['news_score'] ?? $countryAData['news_score'] ?? 0;
        $newsB = $countryBData['risk_score']['news_score'] ?? $countryBData['news_score'] ?? 0;

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

        $comparisonChartData = [
            'risk' => [
                'labels' => [$countryAIso, $countryBIso],
                'values' => [
                    round((float) $countryAScore, 2),
                    round((float) $countryBScore, 2),
                ],
            ],
            'economic' => [
                'labels' => ['Inflasi', 'Kurs', 'Berita'],
                'country_a' => [
                    round((float) $inflationA, 2),
                    round((float) $currencyA, 2),
                    round((float) $newsA, 2),
                ],
                'country_b' => [
                    round((float) $inflationB, 2),
                    round((float) $currencyB, 2),
                    round((float) $newsB, 2),
                ],
            ],
            'operational' => [
                'labels' => ['Cuaca', 'Kurs', 'Berita', 'Total'],
                'country_a' => [
                    round((float) $weatherA, 2),
                    round((float) $currencyA, 2),
                    round((float) $newsA, 2),
                    round((float) $countryAScore, 2),
                ],
                'country_b' => [
                    round((float) $weatherB, 2),
                    round((float) $currencyB, 2),
                    round((float) $newsB, 2),
                    round((float) $countryBScore, 2),
                ],
            ],
            'labels' => [
                'country_a' => $countryAIso,
                'country_b' => $countryBIso,
            ],
        ];
    @endphp

    <div class="comparison-page">
        <section class="comparison-top-card">
            <div class="comparison-title-area">
                <div class="page-eyebrow">
                    COUNTRY COMPARISON DASHBOARD
                </div>

                <h1>
                    Perbandingan Negara
                </h1>

                <p>
                    Bandingkan Risk Score, cuaca, kurs, inflasi, dan berita
                    dari dua negara terpilih.
                </p>
            </div>

            <form
                method="GET"
                action="{{ route('comparison.index') }}"
                class="comparison-form"
            >
                <div class="comparison-form-label">
                    Pilih Dua Negara
                </div>

                <div class="comparison-form-row">
                    <select
                        name="country_a"
                        class="form-select comparison-select"
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
                        class="form-select comparison-select"
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
                        class="btn btn-primary comparison-button"
                    >
                        Bandingkan
                    </button>
                </div>
            </form>
        </section>

        <section class="comparison-result-grid">
            <article class="comparison-result-card">
                <div>
                    <span>
                        {{ $countryAName }}
                    </span>

                    <strong>
                        {{ number_format((float) $countryAScore, 2, ',', '.') }}
                    </strong>

                    <small>
                        {{ $countryAOfficialName }}
                    </small>
                </div>

                <b class="{{ $riskClass($countryAScore) }}">
                    {{ $riskLabel($countryAScore) }}
                </b>
            </article>

            <article class="comparison-result-card">
                <div>
                    <span>
                        {{ $countryBName }}
                    </span>

                    <strong>
                        {{ number_format((float) $countryBScore, 2, ',', '.') }}
                    </strong>

                    <small>
                        {{ $countryBOfficialName }}
                    </small>
                </div>

                <b class="{{ $riskClass($countryBScore) }}">
                    {{ $riskLabel($countryBScore) }}
                </b>
            </article>
        </section>

        <section class="comparison-chart-grid">
            <article class="comparison-chart-card">
                <h3>
                    Risiko Total
                </h3>

                <div class="chart-compact-box">
                    <canvas id="comparisonRiskChart"></canvas>
                </div>
            </article>

            <article class="comparison-chart-card">
                <h3>
                    Indikator Ekonomi
                </h3>

                <div class="chart-compact-box">
                    <canvas id="comparisonEconomicChart"></canvas>
                </div>
            </article>

            <article class="comparison-chart-card">
                <h3>
                    Risiko Operasional
                </h3>

                <div class="chart-compact-box">
                    <canvas id="comparisonOperationalChart"></canvas>
                </div>
            </article>
        </section>

        <section class="comparison-detail-card">
            <div class="comparison-detail-heading">
                <h2>
                    Ringkasan Detail
                </h2>

                <p>
                    Perbandingan komponen risiko utama negara terpilih.
                </p>
            </div>

            <div class="table-responsive">
                <table class="table align-middle mb-0">
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
            max-width: 100%;
            min-width: 0;
            overflow-x: hidden;
            display: flex;
            flex-direction: column;
            gap: 18px;
        }

        .comparison-top-card,
        .comparison-result-card,
        .comparison-chart-card,
        .comparison-detail-card {
            background: #ffffff;
            border: 1px solid rgba(148, 163, 184, 0.22);
            border-radius: 18px;
            box-shadow: 0 14px 32px rgba(15, 23, 42, 0.045);
        }

        .comparison-top-card {
            display: grid;
            grid-template-columns: minmax(280px, 0.85fr) minmax(420px, 1.15fr);
            gap: 20px;
            align-items: end;
            padding: 22px 24px;
        }

        .comparison-title-area h1 {
            margin: 0 0 8px;
            color: #111827;
            font-size: clamp(1.7rem, 2.6vw, 2.35rem);
            font-weight: 900;
            line-height: 1.1;
        }

        .comparison-title-area p {
            margin: 0;
            color: #7c8aa5;
            font-size: 0.94rem;
            line-height: 1.55;
            max-width: 620px;
        }

        .comparison-form {
            min-width: 0;
        }

        .comparison-form-label {
            margin-bottom: 8px;
            color: #334155;
            font-size: 0.9rem;
            font-weight: 800;
        }

        .comparison-form-row {
            display: grid;
            grid-template-columns: minmax(0, 1fr) minmax(0, 1fr) 118px;
            gap: 10px;
            align-items: center;
        }

        .comparison-select {
            min-width: 0;
            width: 100%;
            height: 46px;
            border-radius: 12px;
            font-size: 0.95rem;
            padding: 0 14px;
        }

        .comparison-button {
            height: 46px;
            border-radius: 12px;
            font-size: 0.94rem;
            font-weight: 800;
            padding-inline: 12px;
            white-space: nowrap;
        }

        .comparison-result-grid {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 18px;
        }

        .comparison-result-card {
            min-width: 0;
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 18px;
            padding: 22px 24px;
        }

        .comparison-result-card span {
            display: block;
            color: #7c8aa5;
            font-size: 0.9rem;
            font-weight: 700;
            margin-bottom: 8px;
        }

        .comparison-result-card strong {
            display: block;
            color: #111827;
            font-size: clamp(2rem, 3vw, 2.7rem);
            font-weight: 900;
            line-height: 1;
            margin-bottom: 8px;
        }

        .comparison-result-card small {
            display: block;
            color: #7c8aa5;
            font-size: 0.9rem;
            line-height: 1.4;
        }

        .comparison-result-card b {
            min-width: 128px;
            text-align: center;
            padding: 9px 12px;
            border-radius: 10px;
            font-size: 0.86rem;
            font-weight: 800;
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

        .comparison-chart-grid {
            display: grid;
            grid-template-columns: repeat(3, minmax(0, 1fr));
            gap: 18px;
        }

        .comparison-chart-card {
            min-width: 0;
            padding: 20px;
        }

        .comparison-chart-card h3 {
            margin: 0 0 12px;
            color: #111827;
            font-size: 1.08rem;
            font-weight: 900;
            line-height: 1.25;
        }

        .chart-compact-box {
            width: 100%;
            height: 190px;
        }

        .comparison-detail-card {
            padding: 22px 24px;
        }

        .comparison-detail-heading h2 {
            margin: 0 0 6px;
            color: #111827;
            font-size: 1.35rem;
            font-weight: 900;
        }

        .comparison-detail-heading p {
            margin: 0 0 16px;
            color: #7c8aa5;
            font-size: 0.94rem;
            line-height: 1.5;
        }

        .comparison-detail-card table {
            min-width: 760px;
        }

        .comparison-detail-card thead th {
            background: #f8fafc;
            color: #64748b;
            font-size: 0.8rem;
            font-weight: 900;
            text-transform: uppercase;
            letter-spacing: 0.035em;
            white-space: nowrap;
        }

        .comparison-detail-card tbody td {
            color: #334155;
            font-size: 0.9rem;
        }

        @media (max-width: 1280px) {
            .comparison-top-card {
                grid-template-columns: 1fr;
            }

            .comparison-chart-grid {
                grid-template-columns: 1fr;
            }

            .chart-compact-box {
                height: 210px;
            }
        }

        @media (max-width: 860px) {
            .comparison-form-row,
            .comparison-result-grid {
                grid-template-columns: 1fr;
            }

            .comparison-button {
                width: 100%;
            }

            .comparison-result-card {
                align-items: flex-start;
                flex-direction: column;
            }

            .comparison-result-card b {
                width: 100%;
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
                                size: 10
                            }
                        },
                        grid: {
                            display: false
                        }
                    },
                    y: {
                        beginAtZero: true,
                        suggestedMax: 100,
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
                            boxWidth: 14,
                            font: {
                                size: 11
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
                                maxBarThickness: 38
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
                                maxBarThickness: 28
                            },
                            {
                                label: countryBLabel,
                                data: chartData.economic ? chartData.economic.country_b : [],
                                borderWidth: 1,
                                borderRadius: 5,
                                maxBarThickness: 28
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
                                maxBarThickness: 28
                            },
                            {
                                label: countryBLabel,
                                data: chartData.operational ? chartData.operational.country_b : [],
                                borderWidth: 1,
                                borderRadius: 5,
                                maxBarThickness: 28
                            }
                        ]
                    },
                    options: chartOptions
                });
            }
        });
    </script>
@endpush
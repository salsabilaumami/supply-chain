@extends('layouts.app')

@section('title', 'Country Comparison')

@section('content')
    <div class="comparison-page">
        <section class="comparison-hero">
            <div>
                <div class="page-eyebrow">
                    COUNTRY COMPARISON ENGINE
                </div>

                <h1>
                    Country Comparison
                </h1>

                <p>
                    Bandingkan Germany vs Australia atau negara lain berdasarkan GDP, inflasi, risk score, cuaca, dan mata uang.
                </p>
            </div>
        </section>

        <section class="comparison-filter-card">
            <form
                method="GET"
                action="{{ route('comparison.index') }}"
                class="comparison-filter-form"
            >
                <div class="comparison-field">
                    <label for="country_a">
                        Negara Pertama
                    </label>

                    <select
                        name="country_a"
                        id="country_a"
                        class="form-select"
                    >
                        @foreach ($countries as $country)
                            <option
                                value="{{ $country->iso3_code }}"
                                @selected($countryA->id === $country->id)
                            >
                                {{ $country->name }} ({{ $country->iso3_code }})
                            </option>
                        @endforeach
                    </select>
                </div>

                <div class="comparison-field">
                    <label for="country_b">
                        Negara Kedua
                    </label>

                    <select
                        name="country_b"
                        id="country_b"
                        class="form-select"
                    >
                        @foreach ($countries as $country)
                            <option
                                value="{{ $country->iso3_code }}"
                                @selected($countryB->id === $country->id)
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
                    Bandingkan
                </button>

                <button
                    type="submit"
                    name="refresh"
                    value="1"
                    class="btn btn-outline-primary"
                >
                    Sinkronkan
                </button>
            </form>
        </section>

        @if (!empty($syncWarnings))
            <section class="comparison-warning">
                <i class="bi bi-info-circle"></i>

                <div>
                    <strong>
                        Sebagian data memakai data terakhir yang tersimpan.
                    </strong>

                    <span>
                        Sistem tetap menghitung perbandingan dari data yang tersedia.
                    </span>
                </div>
            </section>
        @endif

        <section class="comparison-country-grid">
            @foreach ([$summaryA, $summaryB] as $summary)
                <article class="comparison-country-card">
                    <div class="comparison-country-header">
                        <div class="comparison-flag">
                            @if (!empty($summary['country']['flag_url']))
                                <img
                                    src="{{ $summary['country']['flag_url'] }}"
                                    alt="Bendera {{ $summary['country']['name'] }}"
                                >
                            @else
                                <i class="bi bi-flag"></i>
                            @endif
                        </div>

                        <div>
                            <span>
                                {{ $summary['country']['iso3_code'] }}
                            </span>

                            <strong>
                                {{ $summary['country']['name'] }}
                            </strong>

                            <small>
                                {{ $summary['country']['currency_code'] ?? '-' }}
                                •
                                {{ $summary['country']['currency_name'] ?? 'Mata uang belum tersedia' }}
                            </small>
                        </div>
                    </div>

                    <div class="comparison-metric-grid">
                        <div>
                            <span>GDP</span>
                            <strong>{{ $summary['gdp']['display'] }}</strong>
                            <small>Tahun {{ $summary['gdp']['year'] ?? '-' }}</small>
                        </div>

                        <div>
                            <span>Inflation</span>
                            <strong>{{ $summary['inflation']['display'] }}</strong>
                            <small>Tahun {{ $summary['inflation']['year'] ?? '-' }}</small>
                        </div>

                        <div>
                            <span>Risk</span>
                            <strong>{{ $summary['risk']['display'] }}</strong>
                            <small>{{ $summary['risk']['label'] }}</small>
                        </div>

                        <div>
                            <span>Weather</span>
                            <strong>{{ $summary['weather']['display'] }}</strong>
                            <small>{{ $summary['weather']['condition'] }}</small>
                        </div>

                        <div>
                            <span>Currency</span>
                            <strong>{{ $summary['currency']['display'] }}</strong>
                            <small>{{ $summary['currency']['risk_display'] }} risk</small>
                        </div>
                    </div>

                    <div class="comparison-weather-detail">
                        <i class="bi bi-cloud-sun"></i>

                        <span>
                            {{ $summary['weather']['detail'] }}
                        </span>
                    </div>

                    <div class="comparison-risk-detail">
                        <div>
                            <span>Weather Score</span>
                            <strong>{{ number_format($summary['risk']['weather_score'], 2, ',', '.') }}</strong>
                        </div>

                        <div>
                            <span>Inflation Score</span>
                            <strong>{{ number_format($summary['risk']['inflation_score'], 2, ',', '.') }}</strong>
                        </div>

                        <div>
                            <span>Currency Score</span>
                            <strong>{{ number_format($summary['risk']['currency_score'], 2, ',', '.') }}</strong>
                        </div>
                    </div>
                </article>
            @endforeach
        </section>

        <section class="comparison-recommendation-card">
            <div>
                <span>
                    Best Country Recommendation
                </span>

                <h2>
                    {{ $recommendation['country_name'] }}
                </h2>

                <p>
                    {{ $recommendation['description'] }}
                </p>
            </div>

            <div class="comparison-score-badge">
                <strong>
                    {{ number_format($recommendation['score'], 0, ',', '.') }}
                </strong>

                <small>
                    {{ $recommendation['label'] }}
                </small>
            </div>
        </section>

        <section class="comparison-chart-grid">
            <article class="comparison-panel">
                <div class="comparison-heading">
                    <span>GDP Comparison</span>

                    <h2>
                        Perbandingan GDP
                    </h2>
                </div>

                <div class="comparison-chart-box">
                    <canvas id="gdpComparisonChart"></canvas>
                </div>
            </article>

            <article class="comparison-panel">
                <div class="comparison-heading">
                    <span>Risk Factors</span>

                    <h2>
                        Inflation, Risk, Weather, Currency
                    </h2>
                </div>

                <div class="comparison-chart-box">
                    <canvas id="metricComparisonChart"></canvas>
                </div>
            </article>
        </section>
    </div>
@endsection

@push('styles')
    <style>
        .comparison-page {
            width: 100%;
            max-width: 1100px;
            margin: 0 auto;
            padding: 10px 12px 22px;
            display: flex;
            flex-direction: column;
            gap: 10px;
            overflow-x: hidden;
        }

        .comparison-hero h1 {
            margin: 0 0 4px;
            color: #111827;
            font-size: 1.45rem;
            font-weight: 950;
        }

        .comparison-hero p {
            margin: 0;
            color: #7c8aa5;
            font-size: 0.8rem;
            line-height: 1.4;
            max-width: 760px;
        }

        .comparison-filter-card,
        .comparison-country-card,
        .comparison-recommendation-card,
        .comparison-panel,
        .comparison-warning {
            background: #ffffff;
            border: 1px solid rgba(148, 163, 184, 0.22);
            border-radius: 15px;
            box-shadow: 0 8px 20px rgba(15, 23, 42, 0.035);
            min-width: 0;
            overflow: hidden;
        }

        .comparison-filter-card {
            padding: 10px;
        }

        .comparison-filter-form {
            display: grid;
            grid-template-columns: minmax(0, 1fr) minmax(0, 1fr) 110px 110px;
            gap: 8px;
            align-items: end;
        }

        .comparison-field label,
        .comparison-country-header span,
        .comparison-metric-grid span,
        .comparison-recommendation-card span,
        .comparison-heading span,
        .comparison-risk-detail span {
            display: block;
            margin-bottom: 3px;
            color: #7c8aa5;
            font-size: 0.66rem;
            font-weight: 850;
            text-transform: uppercase;
            letter-spacing: 0.035em;
        }

        .comparison-filter-form .form-select,
        .comparison-filter-form .btn {
            height: 36px;
            border-radius: 10px;
            font-size: 0.79rem;
            font-weight: 800;
        }

        .comparison-warning {
            display: flex;
            gap: 8px;
            align-items: center;
            padding: 10px 12px;
            background: #fffbeb;
            color: #92400e;
            border-color: rgba(245, 158, 11, 0.25);
        }

        .comparison-warning strong {
            display: block;
            font-size: 0.78rem;
            font-weight: 900;
        }

        .comparison-warning span {
            display: block;
            font-size: 0.72rem;
        }

        .comparison-country-grid {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 10px;
        }

        .comparison-country-card {
            padding: 12px;
        }

        .comparison-country-header {
            display: flex;
            align-items: center;
            gap: 10px;
            margin-bottom: 10px;
        }

        .comparison-flag {
            width: 44px;
            height: 30px;
            border-radius: 9px;
            overflow: hidden;
            background: #e2e8f0;
            display: grid;
            place-items: center;
            color: #64748b;
            flex: 0 0 auto;
        }

        .comparison-flag img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }

        .comparison-country-header strong {
            display: block;
            color: #111827;
            font-size: 0.98rem;
            font-weight: 950;
            line-height: 1.2;
        }

        .comparison-country-header small {
            display: block;
            color: #7c8aa5;
            font-size: 0.72rem;
        }

        .comparison-metric-grid {
            display: grid;
            grid-template-columns: repeat(5, minmax(0, 1fr));
            gap: 8px;
        }

        .comparison-metric-grid div {
            min-width: 0;
            padding: 9px;
            border-radius: 12px;
            background: #f8fafc;
            border: 1px solid rgba(148, 163, 184, 0.16);
        }

        .comparison-metric-grid strong {
            display: block;
            color: #111827;
            font-size: 0.78rem;
            font-weight: 950;
            line-height: 1.3;
            overflow-wrap: anywhere;
        }

        .comparison-metric-grid small {
            color: #7c8aa5;
            font-size: 0.68rem;
            line-height: 1.3;
        }

        .comparison-weather-detail {
            display: flex;
            gap: 7px;
            align-items: center;
            margin-top: 9px;
            padding: 9px;
            border-radius: 12px;
            background: #eef6ff;
            color: #1d4ed8;
            font-size: 0.74rem;
            font-weight: 800;
            line-height: 1.35;
        }

        .comparison-risk-detail {
            display: grid;
            grid-template-columns: repeat(3, minmax(0, 1fr));
            gap: 8px;
            margin-top: 8px;
        }

        .comparison-risk-detail div {
            padding: 8px;
            border-radius: 11px;
            background: #ffffff;
            border: 1px solid rgba(148, 163, 184, 0.16);
            min-width: 0;
        }

        .comparison-risk-detail strong {
            color: #111827;
            font-size: 0.75rem;
            font-weight: 900;
        }

        .comparison-recommendation-card {
            display: grid;
            grid-template-columns: minmax(0, 1fr) 140px;
            gap: 12px;
            align-items: center;
            padding: 12px;
        }

        .comparison-recommendation-card h2 {
            margin: 0 0 4px;
            color: #111827;
            font-size: 1.1rem;
            font-weight: 950;
        }

        .comparison-recommendation-card p {
            margin: 0;
            color: #64748b;
            font-size: 0.78rem;
            line-height: 1.45;
        }

        .comparison-score-badge {
            min-height: 86px;
            border-radius: 14px;
            background: #eef6ff;
            border: 1px solid rgba(37, 99, 235, 0.18);
            color: #1d4ed8;
            display: grid;
            place-items: center;
            text-align: center;
            padding: 10px;
        }

        .comparison-score-badge strong {
            display: block;
            font-size: 1.4rem;
            font-weight: 950;
            line-height: 1;
        }

        .comparison-score-badge small {
            color: #1d4ed8;
            font-size: 0.68rem;
            font-weight: 850;
            line-height: 1.3;
        }

        .comparison-chart-grid {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 10px;
        }

        .comparison-panel {
            padding: 12px;
        }

        .comparison-heading {
            margin-bottom: 9px;
        }

        .comparison-heading h2 {
            margin: 0;
            color: #111827;
            font-size: 0.95rem;
            font-weight: 950;
        }

        .comparison-chart-box {
            height: 180px;
        }

        @media (max-width: 1100px) {
            .comparison-country-grid,
            .comparison-chart-grid {
                grid-template-columns: 1fr;
            }

            .comparison-metric-grid {
                grid-template-columns: repeat(3, minmax(0, 1fr));
            }

            .comparison-filter-form {
                grid-template-columns: 1fr 1fr;
            }
        }

        @media (max-width: 780px) {
            .comparison-filter-form,
            .comparison-recommendation-card {
                grid-template-columns: 1fr;
            }

            .comparison-filter-form .btn {
                width: 100%;
            }

            .comparison-metric-grid,
            .comparison-risk-detail {
                grid-template-columns: repeat(2, minmax(0, 1fr));
            }
        }

        @media (max-width: 520px) {
            .comparison-page {
                padding: 10px;
            }

            .comparison-hero h1 {
                font-size: 1.25rem;
            }

            .comparison-metric-grid,
            .comparison-risk-detail {
                grid-template-columns: 1fr;
            }
        }
    </style>
@endpush

@push('scripts')
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

    <script
        id="comparisonChartData"
        type="application/json"
    >{!! json_encode($chartData ?? [], JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT) !!}</script>

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

            function createBarChart(canvasId, labels, values, label) {
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
                                borderRadius: 8
                            }
                        ]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
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
                                display: false
                            }
                        }
                    }
                });
            }

            function createGroupedBarChart(canvasId, labels, dataA, dataB, labelA, labelB) {
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
                                label: labelA,
                                data: dataA,
                                borderWidth: 1,
                                borderRadius: 8
                            },
                            {
                                label: labelB,
                                data: dataB,
                                borderWidth: 1,
                                borderRadius: 8
                            }
                        ]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
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
                    }
                });
            }

            createBarChart(
                'gdpComparisonChart',
                chartData.gdp?.labels || [],
                chartData.gdp?.values || [],
                'GDP dalam miliar USD'
            );

            createGroupedBarChart(
                'metricComparisonChart',
                chartData.metrics?.labels || [],
                chartData.metrics?.country_a || [],
                chartData.metrics?.country_b || [],
                chartData.metrics?.country_a_name || 'Negara A',
                chartData.metrics?.country_b_name || 'Negara B'
            );
        });
    </script>
@endpush
@extends('layouts.app')

@section('title', 'Data Visualization')

@section('content')
    <div class="visualization-page">
        <section class="visualization-top-grid">
            <div class="visualization-title-area">
                <div class="page-eyebrow">
                    DATA VISUALIZATION DASHBOARD
                </div>

                <h1>
                    Data Visualization
                </h1>

                <p>
                    Visualisasi trend GDP, inflasi, kurs mata uang, dan Risk Score
                    berdasarkan negara yang dipilih.
                </p>
            </div>

            <div class="visualization-country-mini-card">
                <div class="visualization-country-flag">
                    @if ($selectedCountry->flag_url)
                        <img
                            src="{{ $selectedCountry->flag_url }}"
                            alt="Bendera {{ $selectedCountry->name }}"
                        >
                    @else
                        <div class="visualization-flag-placeholder">
                            <i class="bi bi-flag"></i>
                        </div>
                    @endif
                </div>

                <div>
                    <span>
                        Negara Dipantau
                    </span>

                    <strong>
                        {{ $selectedCountry->name }}
                    </strong>

                    <small>
                        {{ $selectedCountry->iso3_code }}
                        •
                        {{ $selectedCountry->currency_code ?? '-' }}
                    </small>
                </div>
            </div>
        </section>

        <section class="visualization-filter-card">
            <form
                method="GET"
                action="{{ route('visualization.index') }}"
                class="visualization-filter"
            >
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

                <button
                    type="submit"
                    class="btn btn-primary"
                >
                    Tampilkan
                </button>
            </form>
        </section>

        <section class="visualization-summary-grid">
            <article class="visualization-stat-card">
                <span>GDP Trend</span>

                <strong>
                    {{ $summary['latest_gdp'] ?? 'Belum tersedia' }}
                </strong>

                <small>
                    {{ $summary['gdp_points'] ?? 0 }} data tahunan
                </small>
            </article>

            <article class="visualization-stat-card">
                <span>Inflation Trend</span>

                <strong>
                    {{ $summary['latest_inflation'] ?? 'Belum tersedia' }}
                </strong>

                <small>
                    {{ $summary['inflation_points'] ?? 0 }} data tahunan
                </small>
            </article>

            <article class="visualization-stat-card">
                <span>Currency Trend</span>

                <strong>
                    {{ $summary['latest_currency'] ?? 'Belum tersedia' }}
                </strong>

                <small>
                    {{ $summary['currency_points'] ?? 0 }} data kurs
                </small>
            </article>

            <article class="visualization-stat-card">
                <span>Risk Trend</span>

                <strong>
                    {{ $summary['latest_risk'] ?? 'Belum tersedia' }}
                </strong>

                <small>
                    {{ $summary['risk_points'] ?? 0 }} data risk score
                </small>
            </article>
        </section>

        <section class="visualization-chart-grid">
            <article class="visualization-chart-card">
                <div class="visualization-card-heading">
                    <span>GDP Trend</span>

                    <h2>
                        Perkembangan GDP
                    </h2>
                </div>

                <div class="visualization-chart-box">
                    <canvas id="gdpTrendChart"></canvas>
                </div>
            </article>

            <article class="visualization-chart-card">
                <div class="visualization-card-heading">
                    <span>Inflation Trend</span>

                    <h2>
                        Perkembangan Inflasi
                    </h2>
                </div>

                <div class="visualization-chart-box">
                    <canvas id="inflationTrendChart"></canvas>
                </div>
            </article>

            <article class="visualization-chart-card">
                <div class="visualization-card-heading">
                    <span>Currency Trend</span>

                    <h2>
                        Perubahan Kurs
                    </h2>
                </div>

                <div class="visualization-chart-box">
                    <canvas id="currencyTrendChart"></canvas>
                </div>
            </article>

            <article class="visualization-chart-card">
                <div class="visualization-card-heading">
                    <span>Risk Trend</span>

                    <h2>
                        Perkembangan Risk Score
                    </h2>
                </div>

                <div class="visualization-chart-box">
                    <canvas id="riskTrendChart"></canvas>
                </div>
            </article>
        </section>
    </div>
@endsection

@push('styles')
    <style>
        .visualization-page {
            width: 100%;
            max-width: 1180px;
            margin: 0 auto;
            padding: 14px 18px 24px;
            display: flex;
            flex-direction: column;
            gap: 12px;
        }

        .visualization-top-grid {
            display: grid;
            grid-template-columns: minmax(0, 1fr) 300px;
            gap: 14px;
            align-items: end;
        }

        .visualization-title-area {
            padding-left: 6px;
        }

        .visualization-title-area h1 {
            margin: 0 0 4px;
            color: #111827;
            font-size: 1.65rem;
            font-weight: 900;
            line-height: 1.1;
        }

        .visualization-title-area p {
            margin: 0;
            color: #7c8aa5;
            font-size: 0.84rem;
            line-height: 1.45;
            max-width: 760px;
        }

        .visualization-country-mini-card,
        .visualization-filter-card,
        .visualization-stat-card,
        .visualization-chart-card {
            background: #ffffff;
            border: 1px solid rgba(148, 163, 184, 0.22);
            border-radius: 16px;
            box-shadow: 0 10px 24px rgba(15, 23, 42, 0.04);
        }

        .visualization-country-mini-card {
            display: flex;
            align-items: center;
            gap: 10px;
            padding: 10px 12px;
            min-height: 76px;
        }

        .visualization-country-flag {
            width: 42px;
            height: 28px;
            border-radius: 8px;
            overflow: hidden;
            background: #e2e8f0;
            flex: 0 0 auto;
        }

        .visualization-country-flag img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }

        .visualization-flag-placeholder {
            width: 100%;
            height: 100%;
            display: grid;
            place-items: center;
            color: #64748b;
        }

        .visualization-country-mini-card span,
        .visualization-stat-card span,
        .visualization-card-heading span {
            display: block;
            margin-bottom: 3px;
            color: #7c8aa5;
            font-size: 0.68rem;
            font-weight: 800;
            text-transform: uppercase;
            letter-spacing: 0.035em;
        }

        .visualization-country-mini-card strong {
            display: block;
            color: #111827;
            font-size: 0.95rem;
            font-weight: 900;
            line-height: 1.2;
        }

        .visualization-country-mini-card small {
            display: block;
            color: #7c8aa5;
            font-size: 0.72rem;
            line-height: 1.3;
        }

        .visualization-filter-card {
            width: fit-content;
            max-width: 100%;
            padding: 10px 12px;
        }

        .visualization-filter {
            display: grid;
            grid-template-columns: 520px 105px;
            gap: 8px;
            align-items: center;
        }

        .visualization-filter .form-select,
        .visualization-filter .btn {
            height: 38px;
            border-radius: 10px;
            font-size: 0.82rem;
            font-weight: 800;
        }

        .visualization-summary-grid {
            display: grid;
            grid-template-columns: repeat(4, minmax(0, 1fr));
            gap: 10px;
        }

        .visualization-stat-card {
            min-width: 0;
            padding: 12px 14px;
        }

        .visualization-stat-card strong {
            display: block;
            color: #111827;
            font-size: 0.98rem;
            font-weight: 900;
            line-height: 1.25;
            word-break: break-word;
        }

        .visualization-stat-card small {
            display: block;
            margin-top: 5px;
            color: #7c8aa5;
            font-size: 0.7rem;
            line-height: 1.35;
        }

        .visualization-chart-grid {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 10px;
        }

        .visualization-chart-card {
            padding: 14px;
            min-width: 0;
        }

        .visualization-card-heading {
            margin-bottom: 10px;
        }

        .visualization-card-heading h2 {
            margin: 0;
            color: #111827;
            font-size: 0.98rem;
            font-weight: 900;
            line-height: 1.25;
        }

        .visualization-chart-box {
            width: 100%;
            height: 210px;
        }

        @media (max-width: 1280px) {
            .visualization-summary-grid {
                grid-template-columns: repeat(2, minmax(0, 1fr));
            }

            .visualization-chart-grid {
                grid-template-columns: 1fr;
            }
        }

        @media (max-width: 980px) {
            .visualization-top-grid {
                grid-template-columns: 1fr;
            }

            .visualization-filter-card {
                width: 100%;
            }

            .visualization-filter {
                grid-template-columns: 1fr;
            }

            .visualization-filter .btn {
                width: 100%;
            }
        }

        @media (max-width: 720px) {
            .visualization-page {
                padding: 12px;
            }

            .visualization-summary-grid {
                grid-template-columns: 1fr;
            }

            .visualization-title-area {
                padding-left: 0;
            }

            .visualization-title-area h1 {
                font-size: 1.45rem;
            }
        }
    </style>
@endpush

@push('scripts')
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

    <script
        id="visualizationChartData"
        type="application/json"
    >{!! json_encode($chartData ?? [], JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT) !!}</script>

    <script>
        document.addEventListener('DOMContentLoaded', function () {
            if (typeof Chart === 'undefined') {
                return;
            }

            var chartDataElement = document.getElementById('visualizationChartData');
            var chartData = {};

            try {
                chartData = JSON.parse(chartDataElement.textContent || '{}');
            } catch (error) {
                chartData = {};
            }

            function getChartLabels(groupName) {
                if (chartData[groupName] && chartData[groupName].labels) {
                    return chartData[groupName].labels;
                }

                return [];
            }

            function getChartValues(groupName) {
                if (chartData[groupName] && chartData[groupName].values) {
                    return chartData[groupName].values.map(function (value) {
                        return Number(value);
                    });
                }

                return [];
            }

            function createLineChart(canvasId, label, labels, values, compactValue, suggestedMax) {
                var canvas = document.getElementById(canvasId);

                if (!canvas) {
                    return;
                }

                new Chart(canvas, {
                    type: 'line',
                    data: {
                        labels: labels,
                        datasets: [
                            {
                                label: label,
                                data: values,
                                borderWidth: 2,
                                pointRadius: 2,
                                pointHoverRadius: 4,
                                tension: 0.35,
                                fill: false
                            }
                        ]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        layout: {
                            padding: 0
                        },
                        scales: {
                            x: {
                                ticks: {
                                    autoSkip: true,
                                    maxTicksLimit: 7,
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
                                beginAtZero: false,
                                suggestedMax: suggestedMax,
                                ticks: {
                                    font: {
                                        size: 9
                                    },
                                    callback: function (value) {
                                        if (compactValue) {
                                            return Number(value).toLocaleString('id-ID', {
                                                notation: 'compact',
                                                maximumFractionDigits: 2
                                            });
                                        }

                                        return Number(value).toLocaleString('id-ID', {
                                            maximumFractionDigits: 4
                                        });
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
                            },
                            tooltip: {
                                callbacks: {
                                    label: function (context) {
                                        var value = Number(context.parsed.y);

                                        if (compactValue) {
                                            value = value.toLocaleString('id-ID', {
                                                notation: 'compact',
                                                maximumFractionDigits: 2
                                            });
                                        } else {
                                            value = value.toLocaleString('id-ID', {
                                                maximumFractionDigits: 4
                                            });
                                        }

                                        return label + ': ' + value;
                                    }
                                }
                            }
                        }
                    }
                });
            }

            createLineChart(
                'gdpTrendChart',
                'GDP',
                getChartLabels('gdp'),
                getChartValues('gdp'),
                true,
                null
            );

            createLineChart(
                'inflationTrendChart',
                'Inflasi',
                getChartLabels('inflation'),
                getChartValues('inflation'),
                false,
                null
            );

            createLineChart(
                'currencyTrendChart',
                'Kurs',
                getChartLabels('currency'),
                getChartValues('currency'),
                false,
                null
            );

            createLineChart(
                'riskTrendChart',
                'Risk Score',
                getChartLabels('risk'),
                getChartValues('risk'),
                false,
                100
            );
        });
    </script>
@endpush
@extends('layouts.app')

@section('title', 'Risk Scoring Engine')

@section('content')
    @php
        $totalRiskClass = match (true) {
            ($totalScore ?? 0) >= 75 => 'risk-critical',
            ($totalScore ?? 0) >= 50 => 'risk-high',
            ($totalScore ?? 0) >= 25 => 'risk-medium',
            default => 'risk-low',
        };

        $componentClass = function ($score) {
            return match (true) {
                $score >= 75 => 'risk-critical',
                $score >= 50 => 'risk-high',
                $score >= 25 => 'risk-medium',
                default => 'risk-low',
            };
        };
    @endphp

    <div class="risk-engine-page">
        <section class="risk-top-grid">
            <div class="risk-title-area">
                <div class="page-eyebrow">
                    RISK SCORING ENGINE
                </div>

                <h1>
                    Risk Scoring Engine
                </h1>

                <p>
                    Mesin perhitungan risiko rantai pasok global berdasarkan cuaca, inflasi,
                    nilai tukar, dan sentimen berita.
                </p>
            </div>

            <div class="risk-country-mini-card">
                <div class="risk-country-flag">
                    @if ($selectedCountry->flag_url)
                        <img
                            src="{{ $selectedCountry->flag_url }}"
                            alt="Bendera {{ $selectedCountry->name }}"
                        >
                    @else
                        <div class="risk-flag-placeholder">
                            <i class="bi bi-flag"></i>
                        </div>
                    @endif
                </div>

                <div>
                    <span>
                        Negara Dianalisis
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

        <section class="risk-filter-card">
            <form
                method="GET"
                action="{{ route('risk.index') }}"
                class="risk-filter"
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
                    Hitung Risiko
                </button>
            </form>
        </section>

        <section class="risk-summary-grid">
            <article class="risk-total-card">
                <span>Total Risk Score</span>

                <strong>
                    {{ number_format($totalScore ?? 0, 2, ',', '.') }}
                </strong>

                <b class="{{ $totalRiskClass }}">
                    {{ $riskLabel ?? 'Low Risk' }}
                </b>

                <small>
                    Output utama Risk Scoring Engine.
                </small>
            </article>

            @foreach ($components as $component)
                <article class="risk-stat-card">
                    <span>
                        {{ $component['label'] }}
                    </span>

                    <strong>
                        {{ number_format($component['score'] ?? 0, 2, ',', '.') }}
                    </strong>

                    <b class="{{ $componentClass($component['score'] ?? 0) }}">
                        Bobot {{ number_format(($component['weight'] ?? 0) * 100, 0, ',', '.') }}%
                    </b>

                    <small>
                        {{ $component['source_value'] ?? 'Belum tersedia' }}
                    </small>
                </article>
            @endforeach
        </section>

        <section class="risk-formula-grid">
            <article class="risk-card">
                <div class="risk-card-heading">
                    <span>Formula</span>

                    <h2>
                        Algoritma Risk Score
                    </h2>
                </div>

                <div class="risk-formula-box">
                    Risk Score =
                    Weather × 30%
                    +
                    Inflation × 20%
                    +
                    Exchange Rate × 10%
                    +
                    News Sentiment × 40%
                </div>

            </article>

            <article class="risk-card">
                <div class="risk-card-heading">
                    <span>Bobot</span>

                    <h2>
                        Komposisi Perhitungan
                    </h2>
                </div>

                <div class="risk-weight-list">
                    @foreach ($components as $component)
                        <div>
                            <span>
                                {{ $component['label'] }}
                            </span>

                            <strong>
                                {{ number_format($component['score'], 2, ',', '.') }}
                                ×
                                {{ number_format($component['weight'] * 100, 0, ',', '.') }}%
                                =
                                {{ number_format($component['weighted_score'], 2, ',', '.') }}
                            </strong>
                        </div>
                    @endforeach
                </div>
            </article>
        </section>

        <section class="risk-chart-grid">
            <article class="risk-card">
                <div class="risk-card-heading">
                    <span>Komponen Risiko</span>

                    <h2>
                        Skor Mentah Komponen
                    </h2>
                </div>

                <div class="risk-chart-box">
                    <canvas id="riskComponentChart"></canvas>
                </div>
            </article>

            <article class="risk-card">
                <div class="risk-card-heading">
                    <span>Kontribusi Bobot</span>

                    <h2>
                        Kontribusi ke Total Score
                    </h2>
                </div>

                <div class="risk-chart-box">
                    <canvas id="riskWeightedChart"></canvas>
                </div>
            </article>
        </section>

        <section class="risk-card">
            <div class="risk-card-heading">
                <span>Detail Perhitungan</span>

                <h2>
                    Breakdown Risk Scoring
                </h2>
            </div>

            <div class="table-responsive risk-table-wrapper">
                <table class="table align-middle mb-0 risk-table">
                    <thead>
                        <tr>
                            <th>Komponen</th>
                            <th>Skor</th>
                            <th>Bobot</th>
                            <th>Kontribusi</th>
                            <th>Keterangan</th>
                        </tr>
                    </thead>

                    <tbody>
                        @foreach ($components as $component)
                            <tr>
                                <td>
                                    <strong>
                                        {{ $component['label'] }}
                                    </strong>
                                </td>

                                <td>
                                    {{ number_format($component['score'], 2, ',', '.') }}
                                </td>

                                <td>
                                    {{ number_format($component['weight'] * 100, 0, ',', '.') }}%
                                </td>

                                <td>
                                    <strong>
                                        {{ number_format($component['weighted_score'], 2, ',', '.') }}
                                    </strong>
                                </td>

                                <td>
                                    {{ $component['description'] }}
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </section>
    </div>
@endsection

@push('styles')
    <style>
        .risk-engine-page {
            width: 100%;
            max-width: 1180px;
            margin: 0 auto;
            padding: 14px 18px 24px;
            display: flex;
            flex-direction: column;
            gap: 12px;
        }

        .risk-top-grid {
            display: grid;
            grid-template-columns: minmax(0, 1fr) 300px;
            gap: 14px;
            align-items: end;
        }

        .risk-title-area {
            padding-left: 6px;
        }

        .risk-title-area h1 {
            margin: 0 0 4px;
            color: #111827;
            font-size: 1.65rem;
            font-weight: 900;
            line-height: 1.1;
        }

        .risk-title-area p {
            margin: 0;
            color: #7c8aa5;
            font-size: 0.84rem;
            line-height: 1.45;
            max-width: 760px;
        }

        .risk-country-mini-card,
        .risk-filter-card,
        .risk-total-card,
        .risk-stat-card,
        .risk-card {
            background: #ffffff;
            border: 1px solid rgba(148, 163, 184, 0.22);
            border-radius: 16px;
            box-shadow: 0 10px 24px rgba(15, 23, 42, 0.04);
        }

        .risk-country-mini-card {
            display: flex;
            align-items: center;
            gap: 10px;
            padding: 10px 12px;
            min-height: 76px;
        }

        .risk-country-flag {
            width: 42px;
            height: 28px;
            border-radius: 8px;
            overflow: hidden;
            background: #e2e8f0;
            flex: 0 0 auto;
        }

        .risk-country-flag img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }

        .risk-flag-placeholder {
            width: 100%;
            height: 100%;
            display: grid;
            place-items: center;
            color: #64748b;
        }

        .risk-country-mini-card span,
        .risk-total-card span,
        .risk-stat-card span,
        .risk-card-heading span,
        .risk-weight-list span {
            display: block;
            margin-bottom: 3px;
            color: #7c8aa5;
            font-size: 0.68rem;
            font-weight: 800;
            text-transform: uppercase;
            letter-spacing: 0.035em;
        }

        .risk-country-mini-card strong {
            display: block;
            color: #111827;
            font-size: 0.95rem;
            font-weight: 900;
            line-height: 1.2;
        }

        .risk-country-mini-card small {
            display: block;
            color: #7c8aa5;
            font-size: 0.72rem;
            line-height: 1.3;
        }

        .risk-filter-card {
            width: fit-content;
            max-width: 100%;
            padding: 10px 12px;
        }

        .risk-filter {
            display: grid;
            grid-template-columns: 520px 130px;
            gap: 8px;
            align-items: center;
        }

        .risk-filter .form-select,
        .risk-filter .btn {
            height: 38px;
            border-radius: 10px;
            font-size: 0.82rem;
            font-weight: 800;
        }

        .risk-summary-grid {
            display: grid;
            grid-template-columns: 1.4fr repeat(4, minmax(0, 1fr));
            gap: 10px;
        }

        .risk-total-card,
        .risk-stat-card {
            min-width: 0;
            padding: 12px 14px;
        }

        .risk-total-card strong {
            display: block;
            color: #111827;
            font-size: 2rem;
            font-weight: 950;
            line-height: 1.05;
        }

        .risk-stat-card strong {
            display: block;
            color: #111827;
            font-size: 1rem;
            font-weight: 900;
            line-height: 1.2;
        }

        .risk-total-card small,
        .risk-stat-card small {
            display: block;
            margin-top: 5px;
            color: #7c8aa5;
            font-size: 0.7rem;
            line-height: 1.3;
        }

        .risk-total-card b,
        .risk-stat-card b,
        .risk-low,
        .risk-medium,
        .risk-high,
        .risk-critical {
            display: inline-flex;
            justify-content: center;
            align-items: center;
            min-width: 104px;
            margin-top: 7px;
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

        .risk-formula-grid,
        .risk-chart-grid {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 10px;
        }

        .risk-card {
            padding: 14px;
            min-width: 0;
        }

        .risk-card-heading {
            margin-bottom: 10px;
        }

        .risk-card-heading h2 {
            margin: 0;
            color: #111827;
            font-size: 0.98rem;
            font-weight: 900;
            line-height: 1.25;
        }

        .risk-formula-box {
            padding: 12px;
            border-radius: 14px;
            background: #f8fafc;
            border: 1px solid rgba(148, 163, 184, 0.18);
            color: #111827;
            font-size: 0.88rem;
            font-weight: 900;
            line-height: 1.6;
        }

        .risk-card p {
            margin: 10px 0 0;
            color: #64748b;
            font-size: 0.78rem;
            line-height: 1.45;
        }

        .risk-weight-list {
            display: grid;
            gap: 8px;
        }

        .risk-weight-list > div {
            padding: 9px 10px;
            border-radius: 12px;
            background: #f8fafc;
            border: 1px solid rgba(148, 163, 184, 0.14);
        }

        .risk-weight-list strong {
            color: #111827;
            font-size: 0.82rem;
            font-weight: 850;
        }

        .risk-chart-box {
            width: 100%;
            height: 190px;
        }

        .risk-table-wrapper {
            border: 1px solid rgba(148, 163, 184, 0.18);
            border-radius: 14px;
            overflow: auto;
        }

        .risk-table {
            min-width: 820px;
        }

        .risk-table thead th {
            background: #f8fafc;
            color: #64748b;
            font-size: 0.72rem;
            font-weight: 900;
            text-transform: uppercase;
            letter-spacing: 0.035em;
            white-space: nowrap;
        }

        .risk-table tbody td {
            color: #334155;
            font-size: 0.8rem;
            border-bottom: 1px solid rgba(148, 163, 184, 0.14);
        }

        .risk-table tbody td strong {
            color: #111827;
            font-weight: 850;
        }

        @media (max-width: 1280px) {
            .risk-summary-grid {
                grid-template-columns: repeat(2, minmax(0, 1fr));
            }

            .risk-formula-grid,
            .risk-chart-grid {
                grid-template-columns: 1fr;
            }
        }

        @media (max-width: 980px) {
            .risk-top-grid {
                grid-template-columns: 1fr;
            }

            .risk-filter-card {
                width: 100%;
            }

            .risk-filter {
                grid-template-columns: 1fr;
            }

            .risk-filter .btn {
                width: 100%;
            }
        }

        @media (max-width: 720px) {
            .risk-engine-page {
                padding: 12px;
            }

            .risk-summary-grid {
                grid-template-columns: 1fr;
            }

            .risk-title-area {
                padding-left: 0;
            }

            .risk-title-area h1 {
                font-size: 1.45rem;
            }
        }
    </style>
@endpush

@push('scripts')
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

    <script
        id="riskChartData"
        type="application/json"
    >{!! json_encode($chartData ?? [], JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT) !!}</script>

    <script>
        document.addEventListener('DOMContentLoaded', function () {
            if (typeof Chart === 'undefined') {
                return;
            }

            var chartDataElement = document.getElementById('riskChartData');
            var chartData = {};

            try {
                chartData = JSON.parse(chartDataElement.textContent || '{}');
            } catch (error) {
                chartData = {};
            }

            var labels = chartData.components ? chartData.components.labels : [];
            var scores = chartData.components ? chartData.components.scores : [];
            var weighted = chartData.components ? chartData.components.weighted : [];

            function createBarChart(canvasId, label, values, maxValue) {
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
                                borderRadius: 6,
                                maxBarThickness: 34
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
                                suggestedMax: maxValue,
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
                            },
                            tooltip: {
                                callbacks: {
                                    label: function (context) {
                                        return label + ': ' + Number(context.parsed.y).toLocaleString('id-ID', {
                                            maximumFractionDigits: 2
                                        });
                                    }
                                }
                            }
                        }
                    }
                });
            }

            createBarChart(
                'riskComponentChart',
                'Skor Komponen',
                scores,
                100
            );

            createBarChart(
                'riskWeightedChart',
                'Kontribusi Bobot',
                weighted,
                40
            );
        });
    </script>
@endpush
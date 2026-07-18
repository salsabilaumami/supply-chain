@extends('layouts.app')

@section('title', 'Dampak Mata Uang')

@section('content')
    @php
        $riskBadgeClass = match (true) {
            ($currencyRisk ?? 0) >= 75 => 'risk-critical',
            ($currencyRisk ?? 0) >= 50 => 'risk-high',
            ($currencyRisk ?? 0) >= 25 => 'risk-medium',
            default => 'risk-low',
        };
    @endphp

    <div class="currency-page">
        <section class="currency-top-grid">
            <div class="currency-title-area">
                <div class="page-eyebrow">
                    CURRENCY IMPACT DASHBOARD
                </div>

                <h1>
                    Dampak Mata Uang
                </h1>

                <p>
                    Pantau nilai tukar, perubahan kurs, dan risiko mata uang negara terpilih.
                </p>
            </div>

            <div class="currency-country-mini-card">
                <div class="currency-country-flag">
                    @if ($selectedCountry->flag_url)
                        <img
                            src="{{ $selectedCountry->flag_url }}"
                            alt="Bendera {{ $selectedCountry->name }}"
                        >
                    @else
                        <div class="currency-flag-placeholder">
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

        <section class="currency-filter-card">
            <form
                method="GET"
                action="{{ route('currency.index') }}"
                class="currency-filter"
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
            <div class="currency-alert">
                <i class="bi bi-info-circle"></i>

                <span>
                    {{ $apiError }}
                </span>
            </div>
        @endif

        <section class="currency-summary-grid">
            <article class="currency-stat-card">
                <span>Kurs Terbaru</span>

                <strong>
                    {{ $displayRate ?? 'Belum tersedia' }}
                </strong>

                <small>
                    USD ke {{ $selectedCountry->currency_code ?? '-' }}
                </small>
            </article>

            <article class="currency-stat-card">
                <span>Perubahan</span>

                <strong>
                    {{ $displayChange ?? 'Belum ada pembanding' }}
                </strong>

                <small>
                    Perubahan kurs terakhir
                </small>
            </article>

            <article class="currency-stat-card">
                <span>Currency Risk</span>

                <strong>
                    {{ number_format($currencyRisk ?? 0, 2, ',', '.') }}
                </strong>

                <small>
                    {{ $riskLabel ?? 'Belum tersedia' }}
                </small>
            </article>

            <article class="currency-stat-card">
                <span>Status</span>

                <strong>
                    <b class="{{ $riskBadgeClass }}">
                        {{ $riskLabel ?? 'Belum tersedia' }}
                    </b>
                </strong>

                <small>
                    Risiko mata uang
                </small>
            </article>

            <article class="currency-stat-card">
                <span>Mata Uang</span>

                <strong>
                    {{ $selectedCountry->currency_code ?? '-' }}
                </strong>

                <small>
                    {{ $selectedCountry->currency_name ?? 'Belum tersedia' }}
                </small>
            </article>

            <article class="currency-stat-card">
                <span>Update</span>

                <strong>
                    {{ $lastUpdate ?? 'Belum tersedia' }}
                </strong>

                <small>
                    Pembaruan terakhir
                </small>
            </article>
        </section>

        <section class="currency-chart-grid">
            <article class="currency-chart-card">
                <div class="currency-card-heading">
                    <span>Trend Kurs</span>

                    <h2>
                        Grafik Nilai Tukar
                    </h2>
                </div>

                <div class="currency-chart-box">
                    <canvas id="currencyRateChart"></canvas>
                </div>
            </article>

            <article class="currency-chart-card">
                <div class="currency-card-heading">
                    <span>Risk Trend</span>

                    <h2>
                        Grafik Risiko Kurs
                    </h2>
                </div>

                <div class="currency-chart-box">
                    <canvas id="currencyRiskChart"></canvas>
                </div>
            </article>
        </section>

        <section class="currency-detail-card">
            <div class="currency-card-heading">
                <span>Riwayat Kurs</span>

                <h2>
                    Data Kurs Terakhir
                </h2>
            </div>

            <div class="table-responsive currency-table-wrapper">
                <table class="table align-middle mb-0 currency-table">
                    <thead>
                        <tr>
                            <th>Waktu</th>
                            <th>Base</th>
                            <th>Target</th>
                            <th>Rate</th>
                            <th>Perubahan</th>
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
                                    {{ $item->base_currency }}
                                </td>

                                <td>
                                    {{ $item->target_currency }}
                                </td>

                                <td>
                                    {{ number_format((float) $item->rate, 4, ',', '.') }}
                                </td>

                                <td>
                                    @if ($item->change_percentage !== null)
                                        {{ number_format((float) $item->change_percentage, 4, ',', '.') }}%
                                    @else
                                        Belum ada pembanding
                                    @endif
                                </td>

                                <td>
                                    {{ number_format((float) $item->currency_risk, 2, ',', '.') }}
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td
                                    colspan="6"
                                    class="text-center text-muted py-4"
                                >
                                    Data kurs belum tersedia.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </section>
    </div>
@endsection

@push('styles')
    <style>
        .currency-page {
            width: 100%;
            max-width: 1180px;
            margin: 0 auto;
            padding: 14px 18px 24px;
            display: flex;
            flex-direction: column;
            gap: 12px;
        }

        .currency-top-grid {
            display: grid;
            grid-template-columns: minmax(0, 1fr) 300px;
            gap: 14px;
            align-items: end;
        }

        .currency-title-area {
            padding-left: 6px;
        }

        .currency-title-area h1 {
            margin: 0 0 4px;
            color: #111827;
            font-size: 1.65rem;
            font-weight: 900;
            line-height: 1.1;
        }

        .currency-title-area p {
            margin: 0;
            color: #7c8aa5;
            font-size: 0.84rem;
            line-height: 1.45;
            max-width: 720px;
        }

        .currency-country-mini-card,
        .currency-filter-card,
        .currency-stat-card,
        .currency-chart-card,
        .currency-detail-card {
            background: #ffffff;
            border: 1px solid rgba(148, 163, 184, 0.22);
            border-radius: 16px;
            box-shadow: 0 10px 24px rgba(15, 23, 42, 0.04);
        }

        .currency-country-mini-card {
            display: flex;
            align-items: center;
            gap: 10px;
            padding: 10px 12px;
            min-height: 76px;
        }

        .currency-country-flag {
            width: 42px;
            height: 28px;
            border-radius: 8px;
            overflow: hidden;
            background: #e2e8f0;
            flex: 0 0 auto;
        }

        .currency-country-flag img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }

        .currency-flag-placeholder {
            width: 100%;
            height: 100%;
            display: grid;
            place-items: center;
            color: #64748b;
        }

        .currency-country-mini-card span,
        .currency-stat-card span,
        .currency-card-heading span {
            display: block;
            margin-bottom: 3px;
            color: #7c8aa5;
            font-size: 0.68rem;
            font-weight: 800;
            text-transform: uppercase;
            letter-spacing: 0.035em;
        }

        .currency-country-mini-card strong {
            display: block;
            color: #111827;
            font-size: 0.95rem;
            font-weight: 900;
            line-height: 1.2;
        }

        .currency-country-mini-card small {
            display: block;
            color: #7c8aa5;
            font-size: 0.72rem;
            line-height: 1.3;
        }

        .currency-filter-card {
            width: fit-content;
            max-width: 100%;
            padding: 10px 12px;
        }

        .currency-filter {
            display: grid;
            grid-template-columns: 520px 105px 105px;
            gap: 8px;
            align-items: center;
        }

        .currency-filter .form-select,
        .currency-filter .btn {
            height: 38px;
            border-radius: 10px;
            font-size: 0.82rem;
            font-weight: 800;
        }

        .currency-alert {
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

        .currency-summary-grid {
            display: grid;
            grid-template-columns: repeat(6, minmax(0, 1fr));
            gap: 10px;
        }

        .currency-stat-card {
            min-width: 0;
            padding: 12px 14px;
        }

        .currency-stat-card strong {
            display: block;
            color: #111827;
            font-size: 0.95rem;
            font-weight: 900;
            line-height: 1.25;
            word-break: break-word;
        }

        .currency-stat-card small {
            display: block;
            margin-top: 4px;
            color: #7c8aa5;
            font-size: 0.7rem;
            line-height: 1.3;
        }

        .currency-stat-card b,
        .risk-low,
        .risk-medium,
        .risk-high,
        .risk-critical {
            display: inline-flex;
            justify-content: center;
            align-items: center;
            min-width: 110px;
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

        .currency-chart-grid {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 10px;
        }

        .currency-chart-card {
            padding: 14px;
            min-width: 0;
        }

        .currency-card-heading {
            margin-bottom: 10px;
        }

        .currency-card-heading h2 {
            margin: 0;
            color: #111827;
            font-size: 0.98rem;
            font-weight: 900;
            line-height: 1.25;
        }

        .currency-chart-box {
            width: 100%;
            height: 165px;
        }

        .currency-detail-card {
            padding: 14px;
        }

        .currency-table-wrapper {
            border: 1px solid rgba(148, 163, 184, 0.18);
            border-radius: 14px;
            overflow: auto;
        }

        .currency-table {
            min-width: 760px;
        }

        .currency-table thead th {
            background: #f8fafc;
            color: #64748b;
            font-size: 0.72rem;
            font-weight: 900;
            text-transform: uppercase;
            letter-spacing: 0.035em;
            white-space: nowrap;
        }

        .currency-table tbody td {
            color: #334155;
            font-size: 0.8rem;
            border-bottom: 1px solid rgba(148, 163, 184, 0.14);
        }

        @media (max-width: 1280px) {
            .currency-summary-grid {
                grid-template-columns: repeat(3, minmax(0, 1fr));
            }

            .currency-chart-grid {
                grid-template-columns: 1fr;
            }
        }

        @media (max-width: 980px) {
            .currency-top-grid {
                grid-template-columns: 1fr;
            }

            .currency-filter-card {
                width: 100%;
            }

            .currency-filter {
                grid-template-columns: 1fr;
            }

            .currency-filter .btn {
                width: 100%;
            }
        }

        @media (max-width: 720px) {
            .currency-page {
                padding: 12px;
            }

            .currency-summary-grid {
                grid-template-columns: repeat(2, minmax(0, 1fr));
            }

            .currency-title-area {
                padding-left: 0;
            }

            .currency-title-area h1 {
                font-size: 1.45rem;
            }
        }

        @media (max-width: 520px) {
            .currency-summary-grid {
                grid-template-columns: 1fr;
            }
        }
    </style>
@endpush

@push('scripts')
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

    <script
        id="currencyChartData"
        type="application/json"
    >{!! json_encode($chartData ?? [], JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT) !!}</script>

    <script>
        document.addEventListener('DOMContentLoaded', function () {
            if (typeof Chart === 'undefined') {
                return;
            }

            var chartDataElement = document.getElementById('currencyChartData');
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

            function buildAdaptiveScale(values, fallbackMin, fallbackMax) {
                var numericValues = values.filter(function (value) {
                    return !Number.isNaN(value);
                });

                if (numericValues.length === 0) {
                    return {
                        suggestedMin: fallbackMin,
                        suggestedMax: fallbackMax
                    };
                }

                var minValue = Math.min.apply(null, numericValues);
                var maxValue = Math.max.apply(null, numericValues);

                if (minValue === maxValue) {
                    var padding = minValue === 0 ? 1 : Math.abs(minValue) * 0.001;

                    return {
                        suggestedMin: minValue - padding,
                        suggestedMax: maxValue + padding
                    };
                }

                var range = maxValue - minValue;
                var padding = range * 0.25;

                return {
                    suggestedMin: minValue - padding,
                    suggestedMax: maxValue + padding
                };
            }

            function createLineChart(canvasId, label, labels, values, forceRiskScale) {
                var canvas = document.getElementById(canvasId);

                if (!canvas) {
                    return;
                }

                var scale = forceRiskScale
                    ? {
                        suggestedMin: 0,
                        suggestedMax: 100
                    }
                    : buildAdaptiveScale(values, 0, 1);

                new Chart(canvas, {
                    type: 'line',
                    data: {
                        labels: labels,
                        datasets: [
                            {
                                label: label,
                                data: values,
                                borderWidth: 2,
                                pointRadius: 3,
                                pointHoverRadius: 5,
                                tension: 0.28,
                                fill: false
                            }
                        ]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        layout: {
                            padding: {
                                top: 4,
                                right: 4,
                                bottom: 0,
                                left: 0
                            }
                        },
                        scales: {
                            x: {
                                ticks: {
                                    autoSkip: true,
                                    maxTicksLimit: 6,
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
                                suggestedMin: scale.suggestedMin,
                                suggestedMax: scale.suggestedMax,
                                ticks: {
                                    font: {
                                        size: 9
                                    },
                                    callback: function (value) {
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
                                        return label + ': ' + Number(context.parsed.y).toLocaleString('id-ID', {
                                            maximumFractionDigits: 4
                                        });
                                    }
                                }
                            }
                        }
                    }
                });
            }

            var rateLabels = getChartLabels('rate');
            var rateValues = getChartValues('rate');

            var riskLabels = getChartLabels('risk');
            var riskValues = getChartValues('risk');

            createLineChart(
                'currencyRateChart',
                'Nilai Tukar',
                rateLabels,
                rateValues,
                false
            );

            createLineChart(
                'currencyRiskChart',
                'Risiko Kurs',
                riskLabels,
                riskValues,
                true
            );
        });
    </script>
@endpush
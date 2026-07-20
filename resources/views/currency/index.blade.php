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
        <section class="currency-hero">
            <div class="currency-hero-text">
                <div class="page-eyebrow">
                    CURRENCY IMPACT DASHBOARD
                </div>

                <h1>
                    Dampak Mata Uang
                </h1>

                <p>
                    Pantau nilai tukar, perubahan kurs, dan risiko mata uang untuk mendukung keputusan rantai pasok global.
                </p>
            </div>

            <div class="currency-country-card">
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

                <div class="currency-country-info">
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

        <section class="currency-control-card">
            <form
                method="GET"
                action="{{ route('currency.index') }}"
                class="currency-dashboard-form"
            >
                <div class="currency-field">
                    <label for="country">
                        Pilih Negara Dashboard
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
                                {{ $country->name }} ({{ $country->currency_code ?? '-' }})
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
            <div class="currency-alert">
                <i class="bi bi-info-circle"></i>

                <span>
                    {{ $apiError }}
                </span>
            </div>
        @endif

        <section class="currency-primary-grid">
            <article class="currency-exchange-card">
                <div class="currency-section-heading">
                    <span>
                        Nilai Tukar Utama
                    </span>

                    <h2>
                        USD ke {{ $selectedCountry->currency_code ?? '-' }}
                    </h2>
                </div>

                <div class="currency-rate-display">
                    <span>
                        Kurs Terbaru
                    </span>

                    <strong>
                        {{ $displayRate ?? 'Belum tersedia' }}
                    </strong>

                    <small>
                        Data kurs utama yang digunakan untuk pemantauan negara terpilih.
                    </small>
                </div>

                <div class="currency-mini-stats">
                    <div>
                        <span>
                            Perubahan
                        </span>

                        <strong>
                            {{ $displayChange ?? 'Belum ada pembanding' }}
                        </strong>
                    </div>

                    <div>
                        <span>
                            Risiko
                        </span>

                        <strong>
                            {{ number_format($currencyRisk ?? 0, 2, ',', '.') }}
                        </strong>
                    </div>

                    <div>
                        <span>
                            Update
                        </span>

                        <strong>
                            {{ $lastUpdate ?? 'Belum tersedia' }}
                        </strong>
                    </div>
                </div>
            </article>

            <article class="currency-status-card">
                <div class="currency-section-heading">
                    <span>
                        Status Risiko
                    </span>

                    <h2>
                        Risiko Mata Uang
                    </h2>
                </div>

                <div class="currency-risk-circle {{ $riskBadgeClass }}">
                    {{ number_format($currencyRisk ?? 0, 0, ',', '.') }}
                </div>

                <strong>
                    {{ $riskLabel ?? 'Belum tersedia' }}
                </strong>

                <p>
                    Skor ini membantu membaca dampak volatilitas kurs terhadap aktivitas impor, ekspor, dan rantai pasok.
                </p>
            </article>
        </section>

        <section class="currency-chart-grid">
            <article class="currency-panel">
                <div class="currency-section-heading">
                    <span>
                        Grafik Perubahan Kurs
                    </span>

                    <h2>
                        Trend Nilai Tukar
                    </h2>
                </div>

                <div class="currency-chart-box">
                    <canvas id="currencyRateChart"></canvas>
                </div>
            </article>

            <article class="currency-panel">
                <div class="currency-section-heading">
                    <span>
                        Grafik Risiko
                    </span>

                    <h2>
                        Trend Risiko Kurs
                    </h2>
                </div>

                <div class="currency-chart-box">
                    <canvas id="currencyRiskChart"></canvas>
                </div>
            </article>
        </section>

        <section class="currency-converter-card">
            <div class="currency-section-heading">
                <span>
                    Fitur Tambahan
                </span>

                <h2>
                    Konversi Mata Uang Antar Negara
                </h2>
            </div>

            <form
                method="GET"
                action="{{ route('currency.index') }}"
                class="currency-converter-form"
            >
                <input
                    type="hidden"
                    name="country"
                    value="{{ $selectedCountry->iso3_code }}"
                >

                <div class="currency-field">
                    <label for="amount">
                        Nominal
                    </label>

                    <input
                        type="number"
                        min="0"
                        step="any"
                        name="amount"
                        id="amount"
                        class="form-control"
                        value="{{ $converter['amount'] ?? 1 }}"
                    >
                </div>

                <div class="currency-field">
                    <label for="from_country">
                        Dari Negara
                    </label>

                    <select
                        name="from_country"
                        id="from_country"
                        class="form-select"
                    >
                        @foreach ($countries as $country)
                            @if ($country->currency_code)
                                <option
                                    value="{{ $country->iso3_code }}"
                                    @selected(($converter['from_country']->id ?? null) === $country->id)
                                >
                                    {{ $country->name }} - {{ $country->currency_code }}
                                </option>
                            @endif
                        @endforeach
                    </select>
                </div>

                <div class="currency-field">
                    <label for="to_country">
                        Ke Negara
                    </label>

                    <select
                        name="to_country"
                        id="to_country"
                        class="form-select"
                    >
                        @foreach ($countries as $country)
                            @if ($country->currency_code)
                                <option
                                    value="{{ $country->iso3_code }}"
                                    @selected(($converter['to_country']->id ?? null) === $country->id)
                                >
                                    {{ $country->name }} - {{ $country->currency_code }}
                                </option>
                            @endif
                        @endforeach
                    </select>
                </div>

                <button
                    type="submit"
                    class="btn btn-primary"
                >
                    Konversi
                </button>
            </form>

            @if (!empty($converter['error']))
                <div class="currency-alert currency-alert-small">
                    <i class="bi bi-exclamation-triangle"></i>

                    <span>
                        {{ $converter['error'] }}
                    </span>
                </div>
            @endif

            <div class="converter-box">
                <div class="converter-result">
                    <span>
                        Dari
                    </span>

                    <strong>
                        {{ $converter['display_amount'] ?? 'Belum tersedia' }}
                    </strong>

                    <small>
                        {{ $converter['from_country']->name ?? '-' }}
                        •
                        {{ $converter['from_currency'] ?? '-' }}
                    </small>
                </div>

                <div class="converter-icon">
                    <i class="bi bi-arrow-left-right"></i>
                </div>

                <div class="converter-result">
                    <span>
                        Menjadi
                    </span>

                    <strong>
                        {{ $converter['display_converted_amount'] ?? 'Belum tersedia' }}
                    </strong>

                    <small>
                        {{ $converter['to_country']->name ?? '-' }}
                        •
                        {{ $converter['to_currency'] ?? '-' }}
                    </small>
                </div>
            </div>

            <div class="converter-rate-row">
                <div>
                    <span>
                        Rate
                    </span>

                    <strong>
                        {{ $converter['display_rate'] ?? 'Belum tersedia' }}
                    </strong>
                </div>

                <div>
                    <span>
                        Reverse Rate
                    </span>

                    <strong>
                        {{ $converter['display_reverse_rate'] ?? 'Belum tersedia' }}
                    </strong>
                </div>

                <div>
                    <span>
                        Update
                    </span>

                    <strong>
                        {{ $converter['last_update'] ?? 'Belum tersedia' }}
                    </strong>
                </div>
            </div>
        </section>

        <section class="currency-panel">
            <div class="currency-section-heading">
                <span>
                    Riwayat Kurs
                </span>

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
            max-width: 1080px;
            margin: 0 auto;
            padding: 10px 12px 22px;
            display: flex;
            flex-direction: column;
            gap: 10px;
            overflow-x: hidden;
        }

        .currency-hero {
            display: grid;
            grid-template-columns: minmax(0, 1fr) 250px;
            gap: 10px;
            align-items: end;
        }

        .currency-hero-text {
            min-width: 0;
        }

        .currency-hero-text h1 {
            margin: 0 0 4px;
            color: #111827;
            font-size: 1.45rem;
            font-weight: 950;
            line-height: 1.1;
        }

        .currency-hero-text p {
            margin: 0;
            color: #7c8aa5;
            font-size: 0.8rem;
            line-height: 1.4;
            max-width: 720px;
        }

        .currency-country-card,
        .currency-control-card,
        .currency-exchange-card,
        .currency-status-card,
        .currency-panel,
        .currency-converter-card {
            background: #ffffff;
            border: 1px solid rgba(148, 163, 184, 0.22);
            border-radius: 15px;
            box-shadow: 0 8px 20px rgba(15, 23, 42, 0.035);
            min-width: 0;
            overflow: hidden;
        }

        .currency-country-card {
            display: flex;
            align-items: center;
            gap: 10px;
            min-height: 66px;
            padding: 9px 11px;
        }

        .currency-country-flag {
            width: 40px;
            height: 27px;
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

        .currency-country-info {
            min-width: 0;
        }

        .currency-country-info span,
        .currency-section-heading span,
        .currency-field label,
        .currency-rate-display span,
        .currency-mini-stats span,
        .converter-result span,
        .converter-rate-row span {
            display: block;
            margin-bottom: 3px;
            color: #7c8aa5;
            font-size: 0.66rem;
            font-weight: 850;
            text-transform: uppercase;
            letter-spacing: 0.035em;
        }

        .currency-country-info strong {
            display: block;
            color: #111827;
            font-size: 0.92rem;
            font-weight: 900;
            line-height: 1.2;
            overflow-wrap: anywhere;
        }

        .currency-country-info small {
            display: block;
            color: #7c8aa5;
            font-size: 0.7rem;
            line-height: 1.3;
        }

        .currency-control-card {
            padding: 9px 10px;
        }

        .currency-dashboard-form {
            display: grid;
            grid-template-columns: minmax(0, 1fr) 95px 95px;
            gap: 8px;
            align-items: end;
        }

        .currency-field {
            min-width: 0;
        }

        .currency-dashboard-form .form-select,
        .currency-dashboard-form .btn,
        .currency-converter-form .form-control,
        .currency-converter-form .form-select,
        .currency-converter-form .btn {
            height: 36px;
            border-radius: 10px;
            font-size: 0.79rem;
            font-weight: 800;
            min-width: 0;
        }

        .currency-alert {
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

        .currency-alert-small {
            margin-bottom: 8px;
        }

        .currency-primary-grid {
            display: grid;
            grid-template-columns: minmax(0, 1.45fr) minmax(220px, 0.55fr);
            gap: 10px;
        }

        .currency-exchange-card,
        .currency-status-card,
        .currency-panel,
        .currency-converter-card {
            padding: 12px;
        }

        .currency-section-heading {
            margin-bottom: 9px;
        }

        .currency-section-heading h2 {
            margin: 0;
            color: #111827;
            font-size: 0.95rem;
            font-weight: 950;
            line-height: 1.25;
            overflow-wrap: anywhere;
        }

        .currency-rate-display {
            padding: 12px;
            border-radius: 14px;
            background: linear-gradient(135deg, #f8fafc, #eef6ff);
            border: 1px solid rgba(37, 99, 235, 0.12);
            min-width: 0;
        }

        .currency-rate-display strong {
            display: block;
            color: #111827;
            font-size: 1.25rem;
            font-weight: 950;
            line-height: 1.2;
            overflow-wrap: anywhere;
        }

        .currency-rate-display small {
            display: block;
            margin-top: 4px;
            color: #64748b;
            font-size: 0.72rem;
            line-height: 1.35;
        }

        .currency-mini-stats {
            display: grid;
            grid-template-columns: repeat(3, minmax(0, 1fr));
            gap: 8px;
            margin-top: 8px;
        }

        .currency-mini-stats div {
            padding: 9px;
            border-radius: 12px;
            background: #ffffff;
            border: 1px solid rgba(148, 163, 184, 0.16);
            min-width: 0;
        }

        .currency-mini-stats strong {
            display: block;
            color: #111827;
            font-size: 0.78rem;
            font-weight: 900;
            line-height: 1.3;
            overflow-wrap: anywhere;
        }

        .currency-status-card {
            display: flex;
            flex-direction: column;
            justify-content: space-between;
            gap: 8px;
        }

        .currency-risk-circle {
            width: 72px;
            height: 72px;
            border-radius: 999px;
            display: grid;
            place-items: center;
            font-size: 1.25rem;
            font-weight: 950;
        }

        .currency-status-card > strong {
            display: block;
            color: #111827;
            font-size: 0.95rem;
            font-weight: 950;
        }

        .currency-status-card p {
            margin: 0;
            color: #64748b;
            font-size: 0.73rem;
            line-height: 1.4;
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

        .currency-chart-box {
            width: 100%;
            height: 155px;
        }

        .currency-converter-form {
            display: grid;
            grid-template-columns: 0.5fr 1fr 1fr 92px;
            gap: 8px;
            align-items: end;
            margin-bottom: 9px;
        }

        .currency-converter-form .btn {
            width: 100%;
        }

        .converter-box {
            display: grid;
            grid-template-columns: minmax(0, 1fr) 42px minmax(0, 1fr);
            gap: 8px;
            align-items: center;
            margin-bottom: 8px;
        }

        .converter-result {
            padding: 10px;
            border-radius: 13px;
            background: #f8fafc;
            border: 1px solid rgba(148, 163, 184, 0.16);
            min-width: 0;
            overflow: hidden;
        }

        .converter-result strong {
            display: block;
            color: #111827;
            font-size: 0.94rem;
            font-weight: 950;
            line-height: 1.2;
            overflow-wrap: anywhere;
        }

        .converter-result small {
            display: block;
            margin-top: 4px;
            color: #7c8aa5;
            font-size: 0.7rem;
            line-height: 1.3;
            overflow-wrap: anywhere;
        }

        .converter-icon {
            width: 38px;
            height: 38px;
            border-radius: 999px;
            display: grid;
            place-items: center;
            color: #2563eb;
            background: #eef6ff;
            border: 1px solid rgba(37, 99, 235, 0.18);
            margin: 0 auto;
        }

        .converter-rate-row {
            display: grid;
            grid-template-columns: repeat(3, minmax(0, 1fr));
            gap: 8px;
        }

        .converter-rate-row > div {
            padding: 9px;
            border-radius: 12px;
            border: 1px solid rgba(148, 163, 184, 0.16);
            background: #ffffff;
            min-width: 0;
            overflow: hidden;
        }

        .converter-rate-row strong {
            display: block;
            color: #111827;
            font-size: 0.75rem;
            font-weight: 900;
            line-height: 1.35;
            overflow-wrap: anywhere;
        }

        .currency-table-wrapper {
            width: 100%;
            max-width: 100%;
            border: 1px solid rgba(148, 163, 184, 0.18);
            border-radius: 13px;
            overflow-x: auto;
            overflow-y: hidden;
        }

        .currency-table {
            min-width: 700px;
            width: 100%;
        }

        .currency-table thead th {
            background: #f8fafc;
            color: #64748b;
            font-size: 0.7rem;
            font-weight: 900;
            text-transform: uppercase;
            letter-spacing: 0.035em;
            white-space: nowrap;
        }

        .currency-table tbody td {
            color: #334155;
            font-size: 0.78rem;
            border-bottom: 1px solid rgba(148, 163, 184, 0.14);
        }

        @media (max-width: 1100px) {
            .currency-primary-grid,
            .currency-chart-grid {
                grid-template-columns: 1fr;
            }

            .currency-converter-form {
                grid-template-columns: repeat(2, minmax(0, 1fr));
            }

            .converter-rate-row {
                grid-template-columns: 1fr;
            }
        }

        @media (max-width: 900px) {
            .currency-hero {
                grid-template-columns: 1fr;
            }

            .currency-dashboard-form {
                grid-template-columns: 1fr;
            }

            .currency-dashboard-form .btn {
                width: 100%;
            }

            .currency-mini-stats {
                grid-template-columns: 1fr;
            }

            .currency-converter-form {
                grid-template-columns: 1fr;
            }

            .converter-box {
                grid-template-columns: 1fr;
            }

            .converter-icon {
                transform: rotate(90deg);
            }
        }

        @media (max-width: 640px) {
            .currency-page {
                padding: 10px;
            }

            .currency-hero-text h1 {
                font-size: 1.25rem;
            }

            .currency-rate-display strong {
                font-size: 1.05rem;
            }

            .currency-table {
                min-width: 620px;
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

            createLineChart(
                'currencyRateChart',
                'Nilai Tukar',
                getChartLabels('rate'),
                getChartValues('rate'),
                false
            );

            createLineChart(
                'currencyRiskChart',
                'Risiko Kurs',
                getChartLabels('risk'),
                getChartValues('risk'),
                true
            );
        });
    </script>
@endpush
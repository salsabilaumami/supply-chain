@extends('layouts.app')

@section('title', 'Favorite Monitoring List')

@section('content')
    @php
        $averageRisk = $summary['average_risk_score'] ?? 0;

        $riskBadgeClass = match (true) {
            $averageRisk >= 75 => 'bg-danger',
            $averageRisk >= 50 => 'bg-warning text-dark',
            $averageRisk >= 25 => 'bg-info text-dark',
            default => 'badge-risk-low',
        };

        $riskStatusLabel = match (true) {
            $averageRisk >= 75 => 'Risiko Kritis',
            $averageRisk >= 50 => 'Risiko Tinggi',
            $averageRisk >= 25 => 'Risiko Sedang',
            default => 'Risiko Rendah',
        };

        $riskLevelValues = data_get($chartData ?? [], 'risk_level.values', []);

        $lowCount = $summary['low_count'] ?? ($riskLevelValues[0] ?? 0);
        $moderateCount = $summary['moderate_count'] ?? ($riskLevelValues[1] ?? 0);
        $highCount = $summary['high_count'] ?? ($riskLevelValues[2] ?? 0);
        $criticalCount = $summary['critical_count'] ?? ($riskLevelValues[3] ?? 0);
    @endphp

    <div class="dashboard-page watchlist-page">
        <section class="watchlist-hero">
            <div>
                <div class="page-eyebrow">
                    FAVORITE MONITORING LIST
                </div>

                <h1 class="page-title">
                    Favorit Pemantauan
                </h1>

                <p class="page-description">
                    Simpan negara prioritas yang ingin dipantau dari sisi risiko, cuaca, kurs, berita, dan inflasi.
                </p>
            </div>

            <form
                method="POST"
                action="{{ route('watchlist.store') }}"
                class="watchlist-add-form"
            >
                @csrf

                <select
                    name="country_id"
                    id="country_id"
                    class="form-select"
                    @disabled($availableCountries->isEmpty())
                >
                    @forelse ($availableCountries as $country)
                        <option value="{{ $country->id }}">
                            {{ $country->name }} ({{ $country->iso3_code }})
                        </option>
                    @empty
                        <option value="">
                            Semua negara sudah ada di favorit
                        </option>
                    @endforelse
                </select>

                <button
                    type="submit"
                    class="btn btn-primary"
                    @disabled($availableCountries->isEmpty())
                >
                    <i class="bi bi-bookmark-plus"></i>
                    Tambah
                </button>
            </form>
        </section>

        @if (session('success'))
            <div class="watchlist-alert watchlist-alert-success">
                <i class="bi bi-check-circle"></i>
                <span>{{ session('success') }}</span>
            </div>
        @endif

        @if ($errors->any())
            <div class="watchlist-alert watchlist-alert-error">
                <i class="bi bi-exclamation-triangle"></i>
                <span>{{ $errors->first() }}</span>
            </div>
        @endif

        <section class="watchlist-card">
            <div class="watchlist-card-heading compact-heading">
                <div>
                    <span class="watchlist-label">
                        Negara Favorit
                    </span>

                    <h2>
                        Daftar Negara Pantauan
                    </h2>

                    <p>
                        Negara yang disimpan user untuk pemantauan risiko rantai pasok.
                    </p>
                </div>

                <div class="watchlist-count-pill">
                    {{ number_format($summary['total_countries'] ?? 0, 0, ',', '.') }} Negara
                </div>
            </div>

            <div class="watchlist-country-grid">
                @forelse ($watchlist as $item)
                    @php
                        $score = $item['risk_score']['total_score'] ?? 0;

                        $badgeClass = match (true) {
                            $score >= 75 => 'bg-danger',
                            $score >= 50 => 'bg-warning text-dark',
                            $score >= 25 => 'bg-info text-dark',
                            default => 'badge-risk-low',
                        };

                        $countryId = $item['country']['id'] ?? null;
                        $iso3 = $item['country']['iso3_code'] ?? 'IDN';
                    @endphp

                    <article class="watchlist-country-card">
                        <div class="watchlist-country-top">
                            <div class="watchlist-country-cell">
                                @if (!empty($item['country']['flag_url']))
                                    <img
                                        src="{{ $item['country']['flag_url'] }}"
                                        alt="Bendera {{ $item['country']['name'] }}"
                                    >
                                @else
                                    <div class="watchlist-flag-placeholder">
                                        <i class="bi bi-flag"></i>
                                    </div>
                                @endif

                                <div>
                                    <strong>
                                        {{ $item['country']['name'] }}
                                    </strong>

                                    <small>
                                        {{ $item['country']['iso3_code'] }}
                                    </small>
                                </div>
                            </div>

                            <div class="watchlist-score-box">
                                <strong>
                                    {{ number_format($score, 2, ',', '.') }}
                                </strong>

                                <span class="badge {{ $badgeClass }}">
                                    {{ $item['risk_score']['risk_level_label'] }}
                                </span>
                            </div>
                        </div>

                        <div class="watchlist-metric-grid">
                            <div>
                                <span>Cuaca</span>

                                <strong>
                                    @if ($item['weather']['available'])
                                        {{ number_format($item['weather']['temperature'], 1, ',', '.') }}°C
                                    @else
                                        -
                                    @endif
                                </strong>

                                <small>
                                    Risk {{ number_format($item['risk_score']['weather_score'] ?? 0, 2, ',', '.') }}
                                </small>
                            </div>

                            <div>
                                <span>Kurs</span>

                                <strong>
                                    @if ($item['currency']['available'])
                                        {{ $item['currency']['target_currency'] }}
                                    @else
                                        -
                                    @endif
                                </strong>

                                <small>
                                    Risk {{ number_format($item['risk_score']['currency_score'] ?? 0, 2, ',', '.') }}
                                </small>
                            </div>

                            <div>
                                <span>Berita</span>

                                <strong>
                                    {{ number_format($item['news']['total_articles'] ?? 0, 0, ',', '.') }}
                                </strong>

                                <small>
                                    Risk {{ number_format($item['risk_score']['news_score'] ?? 0, 2, ',', '.') }}
                                </small>
                            </div>

                            <div>
                                <span>Inflasi</span>

                                <strong>
                                    @if ($item['economic']['inflation_available'])
                                        {{ number_format($item['economic']['inflation_value'], 2, ',', '.') }}%
                                    @else
                                        -
                                    @endif
                                </strong>

                                <small>
                                    {{ $item['economic']['inflation_year'] ?? 'Belum tersedia' }}
                                </small>
                            </div>
                        </div>

                        <div class="watchlist-country-footer">
                            <small>
                                Update: {{ $item['last_update'] ?? 'Belum tersedia' }}
                            </small>

                            <div class="watchlist-action-buttons">
                                <a
                                    href="{{ route('countries.index', ['country' => $iso3]) }}"
                                    class="btn btn-sm btn-outline-primary"
                                >
                                    Negara
                                </a>

                                <a
                                    href="{{ route('weather.index', ['country' => $iso3]) }}"
                                    class="btn btn-sm btn-outline-secondary"
                                >
                                    Cuaca
                                </a>

                                <a
                                    href="{{ route('currency.index', ['country' => $iso3]) }}"
                                    class="btn btn-sm btn-outline-secondary"
                                >
                                    Kurs
                                </a>

                                <a
                                    href="{{ route('news.index', ['country' => $iso3]) }}"
                                    class="btn btn-sm btn-outline-secondary"
                                >
                                    Berita
                                </a>

                                @if ($countryId)
                                    <form
                                        method="POST"
                                        action="{{ route('watchlist.destroy', $countryId) }}"
                                        onsubmit="return confirm('Hapus negara ini dari favorit?')"
                                    >
                                        @csrf
                                        @method('DELETE')

                                        <button
                                            type="submit"
                                            class="btn btn-sm btn-outline-danger"
                                        >
                                            Hapus
                                        </button>
                                    </form>
                                @endif
                            </div>
                        </div>
                    </article>
                @empty
                    <div class="watchlist-empty">
                        <i class="bi bi-bookmark"></i>

                        <div>
                            <strong>
                                Belum ada negara favorit.
                            </strong>

                            <p>
                                Pilih negara pada form di atas untuk mulai membuat daftar pemantauan.
                            </p>
                        </div>
                    </div>
                @endforelse
            </div>
        </section>

        <section class="watchlist-summary-grid">
            <article class="watchlist-stat-card">
                <span>Total Favorit</span>

                <strong>
                    {{ number_format($summary['total_countries'] ?? 0, 0, ',', '.') }}
                </strong>

                <small>
                    Negara dalam pemantauan
                </small>
            </article>

            <article class="watchlist-stat-card">
                <span>Rata-rata Risiko</span>

                <strong>
                    {{ number_format($summary['average_risk_score'] ?? 0, 2, ',', '.') }}
                </strong>

                <small>
                    Skor risiko favorit
                </small>
            </article>

            <article class="watchlist-stat-card">
                <span>Risiko Tertinggi</span>

                <strong>
                    {{ $summary['highest_risk_country'] ?? 'Belum tersedia' }}
                </strong>

                <small>
                    Skor {{ number_format($summary['highest_risk_score'] ?? 0, 2, ',', '.') }}
                </small>
            </article>

            <article class="watchlist-stat-card">
                <span>Status Umum</span>

                <strong>
                    <span class="badge {{ $riskBadgeClass }} watchlist-status-badge">
                        {{ $riskStatusLabel }}
                    </span>
                </strong>

                <small>
                    Status rata-rata
                </small>
            </article>
        </section>

        <section class="watchlist-analysis-grid">
            <article class="watchlist-card compact-card">
                <div class="watchlist-card-heading">
                    <h3>
                        Komposisi Risiko
                    </h3>

                    <p>
                        Jumlah negara berdasarkan level risiko.
                    </p>
                </div>

                <div class="risk-level-grid">
                    <div class="risk-level-item">
                        <span>Kritis</span>

                        <strong>
                            {{ number_format($criticalCount, 0, ',', '.') }}
                        </strong>

                        <small>
                            Skor ≥ 75
                        </small>
                    </div>

                    <div class="risk-level-item">
                        <span>Tinggi</span>

                        <strong>
                            {{ number_format($highCount, 0, ',', '.') }}
                        </strong>

                        <small>
                            Skor 50–74
                        </small>
                    </div>

                    <div class="risk-level-item">
                        <span>Sedang</span>

                        <strong>
                            {{ number_format($moderateCount, 0, ',', '.') }}
                        </strong>

                        <small>
                            Skor 25–49
                        </small>
                    </div>

                    <div class="risk-level-item">
                        <span>Rendah</span>

                        <strong>
                            {{ number_format($lowCount, 0, ',', '.') }}
                        </strong>

                        <small>
                            Skor &lt; 25
                        </small>
                    </div>
                </div>
            </article>

            <article class="watchlist-card compact-card">
                <div class="watchlist-card-heading">
                    <h3>
                        Grafik Risiko
                    </h3>

                    <p>
                        Skor risiko negara favorit.
                    </p>
                </div>

                <div class="watchlist-chart-box">
                    <canvas id="watchlistRiskChart"></canvas>
                </div>
            </article>
        </section>
    </div>
@endsection

@push('styles')
    <style>
        .watchlist-page {
            display: flex;
            flex-direction: column;
            gap: 14px;
        }

        .watchlist-hero {
            display: grid;
            grid-template-columns: minmax(0, 1fr) minmax(360px, 480px);
            gap: 16px;
            align-items: end;
        }

        .watchlist-add-form {
            display: grid;
            grid-template-columns: minmax(0, 1fr) 110px;
            gap: 8px;
            align-items: center;
            padding: 12px;
            background: #ffffff;
            border: 1px solid rgba(148, 163, 184, 0.22);
            border-radius: 16px;
            box-shadow: 0 10px 24px rgba(15, 23, 42, 0.04);
        }

        .watchlist-add-form .form-select,
        .watchlist-add-form .btn {
            height: 38px;
            border-radius: 10px;
            font-size: 0.86rem;
            font-weight: 700;
        }

        .watchlist-alert {
            display: flex;
            align-items: center;
            gap: 8px;
            padding: 10px 12px;
            border-radius: 14px;
            font-size: 0.86rem;
            font-weight: 750;
            border: 1px solid transparent;
        }

        .watchlist-alert-success {
            background: #ecfdf5;
            color: #047857;
            border-color: rgba(16, 185, 129, 0.22);
        }

        .watchlist-alert-error {
            background: #fef2f2;
            color: #b91c1c;
            border-color: rgba(239, 68, 68, 0.22);
        }

        .watchlist-card,
        .watchlist-stat-card,
        .watchlist-country-card {
            background: #ffffff;
            border: 1px solid rgba(148, 163, 184, 0.22);
            border-radius: 16px;
            box-shadow: 0 10px 24px rgba(15, 23, 42, 0.04);
        }

        .watchlist-card {
            padding: 16px;
        }

        .compact-card {
            padding: 15px;
        }

        .watchlist-card-heading {
            margin-bottom: 12px;
        }

        .compact-heading {
            display: flex;
            align-items: flex-end;
            justify-content: space-between;
            gap: 12px;
        }

        .watchlist-label {
            display: block;
            margin-bottom: 5px;
            color: #7c8aa5;
            font-size: 0.72rem;
            font-weight: 800;
            letter-spacing: 0.04em;
            text-transform: uppercase;
        }

        .watchlist-card-heading h2,
        .watchlist-card-heading h3 {
            margin: 0 0 4px;
            color: #111827;
            font-weight: 900;
            line-height: 1.25;
        }

        .watchlist-card-heading h2 {
            font-size: 1.18rem;
        }

        .watchlist-card-heading h3 {
            font-size: 1rem;
        }

        .watchlist-card-heading p {
            margin: 0;
            color: #7c8aa5;
            font-size: 0.82rem;
            line-height: 1.45;
        }

        .watchlist-count-pill {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            min-height: 30px;
            padding: 6px 10px;
            border-radius: 999px;
            background: #eef6ff;
            color: #1d4ed8;
            font-size: 0.78rem;
            font-weight: 850;
            white-space: nowrap;
        }

        .watchlist-country-grid {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 12px;
        }

        .watchlist-country-card {
            padding: 13px;
            display: flex;
            flex-direction: column;
            gap: 10px;
        }

        .watchlist-country-top {
            display: flex;
            align-items: flex-start;
            justify-content: space-between;
            gap: 10px;
        }

        .watchlist-country-cell {
            display: flex;
            align-items: center;
            gap: 9px;
            min-width: 0;
        }

        .watchlist-country-cell img,
        .watchlist-flag-placeholder {
            width: 31px;
            height: 21px;
            flex: 0 0 auto;
            border-radius: 6px;
            object-fit: cover;
            background: #e2e8f0;
        }

        .watchlist-flag-placeholder {
            display: grid;
            place-items: center;
            color: #64748b;
        }

        .watchlist-country-cell strong {
            display: block;
            color: #111827;
            font-size: 0.94rem;
            font-weight: 900;
            line-height: 1.25;
        }

        .watchlist-country-cell small,
        .watchlist-country-footer small {
            display: block;
            color: #7c8aa5;
            font-size: 0.72rem;
            line-height: 1.35;
        }

        .watchlist-score-box {
            text-align: right;
            flex: 0 0 auto;
        }

        .watchlist-score-box strong {
            display: block;
            color: #111827;
            font-size: 1.08rem;
            font-weight: 900;
            line-height: 1.1;
            margin-bottom: 4px;
        }

        .watchlist-score-box .badge,
        .watchlist-status-badge {
            padding: 5px 8px;
            border-radius: 999px;
            font-size: 0.7rem;
            font-weight: 800;
        }

        .badge-risk-low {
            background: #eef6ff !important;
            color: #1d4ed8 !important;
            border: 1px solid rgba(37, 99, 235, 0.18);
        }

        .watchlist-metric-grid {
            display: grid;
            grid-template-columns: repeat(4, minmax(0, 1fr));
            gap: 8px;
        }

        .watchlist-metric-grid > div,
        .risk-level-item {
            min-width: 0;
            padding: 9px;
            border-radius: 12px;
            background: #f8fafc;
            border: 1px solid rgba(148, 163, 184, 0.14);
        }

        .watchlist-metric-grid span,
        .watchlist-stat-card span,
        .risk-level-item span {
            display: block;
            margin-bottom: 4px;
            color: #7c8aa5;
            font-size: 0.7rem;
            font-weight: 800;
        }

        .watchlist-metric-grid strong,
        .watchlist-stat-card strong,
        .risk-level-item strong {
            display: block;
            color: #111827;
            font-size: 0.9rem;
            font-weight: 900;
            line-height: 1.2;
            word-break: break-word;
        }

        .watchlist-metric-grid small,
        .watchlist-stat-card small,
        .risk-level-item small {
            display: block;
            margin-top: 3px;
            color: #7c8aa5;
            font-size: 0.68rem;
            line-height: 1.25;
        }

        .watchlist-country-footer {
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 10px;
            padding-top: 2px;
        }

        .watchlist-action-buttons {
            display: flex;
            flex-wrap: wrap;
            justify-content: flex-end;
            gap: 5px;
        }

        .watchlist-action-buttons .btn {
            padding: 3px 7px;
            font-size: 0.7rem;
            border-radius: 8px;
            font-weight: 750;
        }

        .watchlist-summary-grid {
            display: grid;
            grid-template-columns: repeat(4, minmax(0, 1fr));
            gap: 12px;
        }

        .watchlist-stat-card {
            padding: 13px;
        }

        .watchlist-stat-card strong {
            font-size: 1.05rem;
        }

        .watchlist-analysis-grid {
            display: grid;
            grid-template-columns: minmax(280px, 0.85fr) minmax(380px, 1.15fr);
            gap: 12px;
        }

        .risk-level-grid {
            display: grid;
            grid-template-columns: repeat(4, minmax(0, 1fr));
            gap: 8px;
        }

        .risk-level-item strong {
            font-size: 1.05rem;
        }

        .watchlist-chart-box {
            height: 185px;
            width: 100%;
        }

        .watchlist-empty {
            grid-column: 1 / -1;
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 18px;
            border-radius: 14px;
            background: #f8fafc;
            border: 1px dashed rgba(148, 163, 184, 0.45);
            color: #64748b;
        }

        .watchlist-empty i {
            font-size: 1.6rem;
            color: #2563eb;
        }

        .watchlist-empty strong {
            display: block;
            color: #111827;
            font-size: 0.95rem;
            font-weight: 900;
        }

        .watchlist-empty p {
            margin: 3px 0 0;
            font-size: 0.82rem;
        }

        @media (max-width: 1280px) {
            .watchlist-hero,
            .watchlist-analysis-grid {
                grid-template-columns: 1fr;
            }

            .watchlist-summary-grid,
            .risk-level-grid {
                grid-template-columns: repeat(2, minmax(0, 1fr));
            }
        }

        @media (max-width: 980px) {
            .watchlist-country-grid {
                grid-template-columns: 1fr;
            }
        }

        @media (max-width: 720px) {
            .watchlist-hero,
            .watchlist-add-form,
            .watchlist-country-footer {
                grid-template-columns: 1fr;
                flex-direction: column;
                align-items: stretch;
            }

            .watchlist-add-form {
                display: grid;
            }

            .watchlist-summary-grid,
            .risk-level-grid,
            .watchlist-metric-grid {
                grid-template-columns: repeat(2, minmax(0, 1fr));
            }

            .compact-heading,
            .watchlist-country-top {
                align-items: flex-start;
                flex-direction: column;
            }

            .watchlist-score-box {
                text-align: left;
            }

            .watchlist-action-buttons {
                justify-content: flex-start;
            }
        }
    </style>
@endpush

@push('scripts')
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

    <script
        id="watchlistChartData"
        type="application/json"
    >{!! json_encode($chartData ?? [], JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT) !!}</script>

    <script>
        document.addEventListener('DOMContentLoaded', function () {
            if (typeof Chart === 'undefined') {
                return;
            }

            var chartDataElement = document.getElementById('watchlistChartData');
            var chartData = {};

            try {
                chartData = JSON.parse(chartDataElement.textContent || '{}');
            } catch (error) {
                chartData = {};
            }

            var canvas = document.getElementById('watchlistRiskChart');

            if (!canvas) {
                return;
            }

            var riskData = chartData.top_risk || chartData.risk || {};
            var labels = Array.isArray(riskData.labels) ? riskData.labels : [];
            var values = Array.isArray(riskData.values) ? riskData.values : [];

            labels = labels.slice(0, 12);
            values = values.slice(0, 12);

            new Chart(canvas, {
                type: 'bar',
                data: {
                    labels: labels,
                    datasets: [
                        {
                            label: 'Risk Score',
                            data: values,
                            borderWidth: 1,
                            borderRadius: 6,
                            maxBarThickness: 24
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
                                maxRotation: 25,
                                minRotation: 0,
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
                            suggestedMax: 100,
                            ticks: {
                                font: {
                                    size: 9
                                }
                            }
                        }
                    },
                    plugins: {
                        legend: {
                            display: true,
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
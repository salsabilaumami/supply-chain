@extends('layouts.app')

@section('title', 'Favorit Pemantauan')

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
        <section class="dashboard-header watchlist-header">
            <div class="dashboard-heading">
                <div class="page-eyebrow">
                    FAVORITE MONITORING LIST
                </div>

                <h1 class="page-title">
                    Favorit Pemantauan
                </h1>

                <p class="page-description">
                    Pantau negara prioritas yang disimpan untuk monitoring risiko.
                </p>
            </div>
        </section>

        <section class="watchlist-summary-card">
            <div class="watchlist-summary-info">
                <span class="watchlist-label">
                    Ringkasan Favorit
                </span>

                <h2>
                    Negara Favorit
                </h2>

                <p>
                    Negara prioritas yang dipantau berdasarkan skor risiko.
                </p>
            </div>

            <div class="watchlist-summary-grid">
                <div class="watchlist-stat-card">
                    <span>Total Favorit</span>

                    <strong>
                        {{ number_format($summary['total_countries'] ?? 0, 0, ',', '.') }}
                    </strong>

                    <small>
                        Negara dalam pemantauan
                    </small>
                </div>

                <div class="watchlist-stat-card">
                    <span>Rata-rata Risiko</span>

                    <strong>
                        {{ number_format($summary['average_risk_score'] ?? 0, 2, ',', '.') }}
                    </strong>

                    <small>
                        Skor risiko
                    </small>
                </div>

                <div class="watchlist-stat-card">
                    <span>Risiko Tertinggi</span>

                    <strong>
                        {{ $summary['highest_risk_country'] ?? 'Belum tersedia' }}
                    </strong>

                    <small>
                        Skor {{ number_format($summary['highest_risk_score'] ?? 0, 2, ',', '.') }}
                    </small>
                </div>

                <div class="watchlist-stat-card">
                    <span>Status Umum</span>

                    <strong>
                        <span class="badge {{ $riskBadgeClass }} watchlist-status-badge">
                            {{ $riskStatusLabel }}
                        </span>
                    </strong>

                    <small>
                        Status rata-rata
                    </small>
                </div>
            </div>
        </section>

        <section class="watchlist-analysis-grid">
            <article class="watchlist-card">
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

            <article class="watchlist-card">
                <div class="watchlist-card-heading">
                    <h3>
                        Grafik Risiko
                    </h3>

                    <p>
                        Negara dengan skor risiko tertinggi.
                    </p>
                </div>

                <div class="watchlist-chart-box">
                    <canvas id="watchlistRiskChart"></canvas>
                </div>
            </article>
        </section>

        <section class="watchlist-card watchlist-table-card">
            <div class="watchlist-card-heading">
                <h3>
                    Negara Favorit
                </h3>

                <p>
                    Ringkasan risiko, cuaca, kurs, berita, dan inflasi.
                </p>
            </div>

            <div class="table-responsive watchlist-table-wrapper">
                <table class="table align-middle mb-0 watchlist-table">
                    <thead>
                        <tr>
                            <th>Negara</th>
                            <th>Risk Score</th>
                            <th>Cuaca</th>
                            <th>Kurs</th>
                            <th>Berita</th>
                            <th>Inflasi</th>
                            <th>Update</th>
                            <th>Aksi</th>
                        </tr>
                    </thead>

                    <tbody>
                        @forelse ($watchlist as $item)
                            @php
                                $score = $item['risk_score']['total_score'] ?? 0;

                                $badgeClass = match (true) {
                                    $score >= 75 => 'bg-danger',
                                    $score >= 50 => 'bg-warning text-dark',
                                    $score >= 25 => 'bg-info text-dark',
                                    default => 'badge-risk-low',
                                };

                                $iso3 = $item['country']['iso3_code'] ?? 'IDN';
                            @endphp

                            <tr>
                                <td>
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
                                </td>

                                <td>
                                    <strong>
                                        {{ number_format($score, 2, ',', '.') }}
                                    </strong>

                                    <br>

                                    <span class="badge {{ $badgeClass }}">
                                        {{ $item['risk_score']['risk_level_label'] }}
                                    </span>
                                </td>

                                <td>
                                    @if ($item['weather']['available'])
                                        {{ number_format($item['weather']['temperature'], 1, ',', '.') }}°C

                                        <br>

                                        <small class="text-muted">
                                            Risk {{ number_format($item['risk_score']['weather_score'], 2, ',', '.') }}
                                        </small>
                                    @else
                                        <span class="text-muted">
                                            Belum tersedia
                                        </span>
                                    @endif
                                </td>

                                <td>
                                    @if ($item['currency']['available'])
                                        1 {{ $item['currency']['base_currency'] }}
                                        =
                                        {{ number_format($item['currency']['rate'], 4, ',', '.') }}
                                        {{ $item['currency']['target_currency'] }}

                                        <br>

                                        <small class="text-muted">
                                            Risk {{ number_format($item['risk_score']['currency_score'], 2, ',', '.') }}
                                        </small>
                                    @else
                                        <span class="text-muted">
                                            Belum tersedia
                                        </span>
                                    @endif
                                </td>

                                <td>
                                    {{ number_format($item['news']['total_articles'] ?? 0, 0, ',', '.') }}
                                    artikel

                                    <br>

                                    <small class="text-muted">
                                        Risk {{ number_format($item['risk_score']['news_score'], 2, ',', '.') }}
                                    </small>
                                </td>

                                <td>
                                    @if ($item['economic']['inflation_available'])
                                        {{ number_format($item['economic']['inflation_value'], 2, ',', '.') }}%

                                        <br>

                                        <small class="text-muted">
                                            Tahun {{ $item['economic']['inflation_year'] ?? '-' }}
                                        </small>
                                    @else
                                        <span class="text-muted">
                                            Belum tersedia
                                        </span>
                                    @endif
                                </td>

                                <td>
                                    {{ $item['last_update'] ?? 'Belum tersedia' }}
                                </td>

                                <td>
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
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td
                                    colspan="8"
                                    class="text-center text-muted py-4"
                                >
                                    Belum ada negara dalam favorit pemantauan.
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
        .watchlist-page {
            display: flex;
            flex-direction: column;
            gap: 18px;
        }

        .watchlist-header {
            margin-bottom: 0;
        }

        .watchlist-summary-card,
        .watchlist-card {
            background: #ffffff;
            border: 1px solid rgba(148, 163, 184, 0.22);
            border-radius: 18px;
            box-shadow: 0 12px 28px rgba(15, 23, 42, 0.045);
        }

        .watchlist-summary-card {
            display: grid;
            grid-template-columns: minmax(250px, 0.85fr) minmax(380px, 1.6fr);
            gap: 20px;
            align-items: stretch;
            padding: 22px 24px;
        }

        .watchlist-summary-info {
            min-width: 0;
            display: flex;
            flex-direction: column;
            justify-content: center;
        }

        .watchlist-label {
            display: block;
            margin-bottom: 8px;
            color: #7c8aa5;
            font-size: 0.78rem;
            font-weight: 700;
            letter-spacing: 0.035em;
            text-transform: uppercase;
        }

        .watchlist-summary-info h2 {
            margin: 0 0 8px;
            color: #121827;
            font-size: 1.45rem;
            font-weight: 800;
            line-height: 1.25;
        }

        .watchlist-summary-info p,
        .watchlist-card-heading p {
            margin: 0;
            color: #7c8aa5;
            font-size: 0.88rem;
            line-height: 1.55;
        }

        .watchlist-summary-grid {
            display: grid;
            grid-template-columns: repeat(4, minmax(130px, 1fr));
            gap: 12px;
            min-width: 0;
        }

        .watchlist-stat-card {
            min-width: 0;
            padding: 14px 14px;
            border-radius: 14px;
            background: #f8fafc;
            border: 1px solid rgba(148, 163, 184, 0.16);
        }

        .watchlist-stat-card span,
        .risk-level-item span {
            display: block;
            margin-bottom: 6px;
            color: #7c8aa5;
            font-size: 0.78rem;
            font-weight: 700;
        }

        .watchlist-stat-card strong,
        .risk-level-item strong {
            display: block;
            color: #111827;
            font-size: 1.22rem;
            font-weight: 800;
            line-height: 1.25;
            word-break: break-word;
        }

        .watchlist-stat-card small,
        .risk-level-item small {
            display: block;
            margin-top: 5px;
            color: #7c8aa5;
            font-size: 0.76rem;
            line-height: 1.35;
        }

        .watchlist-status-badge {
            width: 100%;
            max-width: 190px;
            padding: 7px 10px;
            font-size: 0.78rem;
            font-weight: 700;
            border-radius: 9px;
        }

        .badge-risk-low {
            background: #eef6ff !important;
            color: #1d4ed8 !important;
            border: 1px solid rgba(37, 99, 235, 0.18);
        }

        .watchlist-analysis-grid {
            display: grid;
            grid-template-columns: minmax(300px, 0.9fr) minmax(420px, 1.5fr);
            gap: 18px;
        }

        .watchlist-card {
            padding: 22px 24px;
            overflow: visible;
        }

        .watchlist-card-heading {
            margin-bottom: 16px;
        }

        .watchlist-card-heading h3 {
            margin: 0 0 6px;
            color: #121827;
            font-size: 1.08rem;
            font-weight: 800;
            line-height: 1.35;
        }

        .risk-level-grid {
            display: grid;
            grid-template-columns: repeat(2, minmax(120px, 1fr));
            gap: 12px;
        }

        .risk-level-item {
            padding: 14px;
            border-radius: 14px;
            background: #f8fafc;
            border: 1px solid rgba(148, 163, 184, 0.16);
        }

        .watchlist-chart-box {
            height: 220px;
            width: 100%;
        }

        .watchlist-table-card {
            padding: 22px 24px;
        }

        .watchlist-table-wrapper {
            border: 1px solid rgba(148, 163, 184, 0.18);
            border-radius: 16px;
            overflow: auto;
        }

        .watchlist-table {
            min-width: 1120px;
        }

        .watchlist-table thead th {
            background: #f8fafc;
            color: #64748b;
            font-size: 0.76rem;
            font-weight: 800;
            text-transform: uppercase;
            letter-spacing: 0.035em;
            border-bottom: 1px solid rgba(148, 163, 184, 0.22);
            white-space: nowrap;
        }

        .watchlist-table tbody td {
            color: #334155;
            font-size: 0.86rem;
            vertical-align: middle;
            border-bottom: 1px solid rgba(148, 163, 184, 0.14);
        }

        .watchlist-country-cell {
            display: flex;
            align-items: center;
            gap: 10px;
            min-width: 190px;
        }

        .watchlist-country-cell img,
        .watchlist-flag-placeholder {
            width: 32px;
            height: 22px;
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
            font-size: 0.9rem;
            font-weight: 800;
            line-height: 1.25;
        }

        .watchlist-country-cell small {
            display: block;
            margin-top: 2px;
            color: #7c8aa5;
            font-size: 0.76rem;
        }

        .watchlist-action-buttons {
            display: flex;
            flex-wrap: wrap;
            gap: 6px;
            min-width: 210px;
        }

        .watchlist-action-buttons .btn {
            padding: 4px 8px;
            font-size: 0.76rem;
            border-radius: 8px;
        }

        @media (max-width: 1320px) {
            .watchlist-summary-card {
                grid-template-columns: 1fr;
            }

            .watchlist-summary-grid {
                grid-template-columns: repeat(2, minmax(150px, 1fr));
            }
        }

        @media (max-width: 1100px) {
            .watchlist-analysis-grid {
                grid-template-columns: 1fr;
            }
        }

        @media (max-width: 768px) {
            .watchlist-summary-card,
            .watchlist-card,
            .watchlist-table-card {
                padding: 18px;
                border-radius: 16px;
            }

            .watchlist-summary-grid,
            .risk-level-grid {
                grid-template-columns: 1fr;
            }

            .watchlist-chart-box {
                height: 210px;
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

            labels = labels.slice(0, 25);
            values = values.slice(0, 25);

            new Chart(canvas, {
                type: 'bar',
                data: {
                    labels: labels,
                    datasets: [
                        {
                            label: 'Risk Score Negara',
                            data: values,
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
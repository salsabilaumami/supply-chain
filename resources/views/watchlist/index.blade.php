@extends('layouts.app')

@section('title', 'Daftar Pemantauan')

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
    @endphp

    <div class="dashboard-page watchlist-page">
        <section class="dashboard-header watchlist-header">
            <div class="dashboard-heading">
                <div class="page-eyebrow">
                    COUNTRY WATCHLIST
                </div>

                <h1 class="page-title">
                    Daftar Pemantauan
                </h1>

                <p class="page-description">
                    Pantau negara-negara yang sudah memiliki data risiko, cuaca,
                    mata uang, berita, dan indikator ekonomi pada sistem.
                </p>
            </div>
        </section>

        <section class="watchlist-summary-card">
            <div class="watchlist-summary-info">
                <span class="watchlist-label">
                    Ringkasan Watchlist
                </span>

                <h2>
                    Negara Dipantau
                </h2>

                <p>
                    Daftar prioritas negara berdasarkan skor risiko,
    kondisi cuaca, kurs, berita, dan indikator ekonomi.
                </p>
            </div>

            <div class="watchlist-summary-grid">
                <div class="watchlist-stat-card">
                    <span>Total Negara</span>

                    <strong>
                        {{ number_format($summary['total_countries'] ?? 0, 0, ',', '.') }}
                    </strong>

                    <small>
                        Negara terpantau
                    </small>
                </div>

                <div class="watchlist-stat-card">
                    <span>Rata-rata Risiko</span>

                    <strong>
                        {{ number_format($summary['average_risk_score'] ?? 0, 2, ',', '.') }}
                    </strong>

                    <small>
                        Skala 0 sampai 100
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
                        Komposisi Level Risiko
                    </h3>

                    <p>
                         Jumlah negara pada setiap level risiko.
                    </p>
                </div>

                <div class="risk-level-grid">
                    <div class="risk-level-item">
                        <span>Kritis</span>

                        <strong>
                            {{ $summary['critical_count'] ?? 0 }}
                        </strong>

                        <small>
                            Skor ≥ 75
                        </small>
                    </div>

                    <div class="risk-level-item">
                        <span>Tinggi</span>

                        <strong>
                            {{ $summary['high_count'] ?? 0 }}
                        </strong>

                        <small>
                            Skor 50–74
                        </small>
                    </div>

                    <div class="risk-level-item">
                        <span>Sedang</span>

                        <strong>
                            {{ $summary['moderate_count'] ?? 0 }}
                        </strong>

                        <small>
                            Skor 25–49
                        </small>
                    </div>

                    <div class="risk-level-item">
                        <span>Rendah</span>

                        <strong>
                            {{ $summary['low_count'] ?? 0 }}
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
                        Grafik Risiko Watchlist
                    </h3>

                    <p>
                        Menampilkan negara dengan skor risiko tertinggi 
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
                    Daftar Negara Dipantau
                </h3>

                <p>
                    Ringkasan status risiko, cuaca, kurs, berita, inflasi, dan akses cepat
                    menuju modul pemantauan tiap negara.
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
                                    Belum ada negara dalam daftar pemantauan.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </section>

        <section class="watchlist-api-card">
            <div>
                <span class="watchlist-label">
                    API Daftar Pemantauan
                </span>

                <h2>
                    Endpoint JSON Watchlist
                </h2>

                <p>
                    Endpoint internal untuk membaca ringkasan negara yang sudah dipantau
                    beserta risk score, cuaca, kurs, berita, dan indikator inflasi.
                </p>
            </div>

            <a
                href="{{ route('api.watchlist.show') }}"
                target="_blank"
                class="btn btn-outline-primary"
            >
                <i class="bi bi-code-slash me-1"></i>
                Lihat JSON API
            </a>
        </section>
    </div>
@endsection

@push('styles')
    <style>
        .watchlist-page {
            display: flex;
            flex-direction: column;
            gap: 24px;
        }

        .watchlist-header {
            margin-bottom: 0;
        }

        .watchlist-summary-card,
        .watchlist-card,
        .watchlist-api-card {
            background: #ffffff;
            border: 1px solid rgba(148, 163, 184, 0.22);
            border-radius: 22px;
            box-shadow: 0 16px 40px rgba(15, 23, 42, 0.05);
        }

        .watchlist-summary-card {
            display: grid;
            grid-template-columns: minmax(280px, 0.95fr) minmax(420px, 1.55fr);
            gap: 28px;
            align-items: stretch;
            padding: 28px 30px;
            overflow: visible;
        }

        .watchlist-summary-info {
            min-width: 0;
            display: flex;
            flex-direction: column;
            justify-content: center;
        }

        .watchlist-label {
            display: block;
            margin-bottom: 10px;
            color: #7c8aa5;
            font-size: 0.92rem;
            font-weight: 600;
            letter-spacing: 0.01em;
        }

        .watchlist-summary-info h2,
        .watchlist-api-card h2 {
            margin: 0 0 10px;
            color: #121827;
            font-size: clamp(1.35rem, 2vw, 1.75rem);
            font-weight: 800;
            line-height: 1.25;
        }

        .watchlist-summary-info p,
        .watchlist-card-heading p,
        .watchlist-api-card p {
            margin: 0;
            color: #7c8aa5;
            font-size: 0.96rem;
            line-height: 1.7;
            white-space: normal;
            overflow: visible;
            text-overflow: clip;
            max-width: 100%;
        }

        .watchlist-summary-grid {
            display: grid;
            grid-template-columns: repeat(2, minmax(180px, 1fr));
            gap: 16px;
            min-width: 0;
        }

        .watchlist-stat-card {
            min-width: 0;
            padding: 18px 18px;
            border-radius: 18px;
            background: #f8fafc;
            border: 1px solid rgba(148, 163, 184, 0.16);
        }

        .watchlist-stat-card span,
        .risk-level-item span {
            display: block;
            margin-bottom: 8px;
            color: #7c8aa5;
            font-size: 0.88rem;
            font-weight: 600;
        }

        .watchlist-stat-card strong,
        .risk-level-item strong {
            display: block;
            color: #111827;
            font-size: 1.45rem;
            font-weight: 800;
            line-height: 1.25;
            word-break: break-word;
        }

        .watchlist-stat-card small,
        .risk-level-item small {
            display: block;
            margin-top: 6px;
            color: #7c8aa5;
            font-size: 0.82rem;
            line-height: 1.45;
        }

        .watchlist-status-badge {
            width: 100%;
            max-width: 240px;
            padding: 8px 12px;
            font-size: 0.86rem;
            font-weight: 700;
            border-radius: 10px;
        }

        .badge-risk-low {
            background: #eef6ff !important;
            color: #1d4ed8 !important;
            border: 1px solid rgba(37, 99, 235, 0.18);
        }

        .watchlist-analysis-grid {
            display: grid;
            grid-template-columns: minmax(320px, 0.95fr) minmax(460px, 1.45fr);
            gap: 24px;
        }

        .watchlist-card {
            padding: 28px 30px;
            overflow: visible;
        }

        .watchlist-card-heading {
            margin-bottom: 22px;
        }

        .watchlist-card-heading h3 {
            margin: 0 0 8px;
            color: #121827;
            font-size: 1.25rem;
            font-weight: 800;
            line-height: 1.35;
        }

        .risk-level-grid {
            display: grid;
            grid-template-columns: repeat(2, minmax(140px, 1fr));
            gap: 14px;
        }

        .risk-level-item {
            padding: 18px;
            border-radius: 18px;
            background: #f8fafc;
            border: 1px solid rgba(148, 163, 184, 0.16);
        }

        .watchlist-chart-box {
            height: 300px;
            width: 100%;
        }

        .watchlist-table-card {
            padding: 28px 30px;
        }

        .watchlist-table-wrapper {
            border: 1px solid rgba(148, 163, 184, 0.18);
            border-radius: 18px;
            overflow: auto;
        }

        .watchlist-table {
            min-width: 1150px;
        }

        .watchlist-table thead th {
            background: #f8fafc;
            color: #64748b;
            font-size: 0.82rem;
            font-weight: 800;
            text-transform: uppercase;
            letter-spacing: 0.035em;
            border-bottom: 1px solid rgba(148, 163, 184, 0.22);
            white-space: nowrap;
        }

        .watchlist-table tbody td {
            color: #334155;
            font-size: 0.92rem;
            vertical-align: middle;
            border-bottom: 1px solid rgba(148, 163, 184, 0.14);
        }

        .watchlist-country-cell {
            display: flex;
            align-items: center;
            gap: 12px;
            min-width: 210px;
        }

        .watchlist-country-cell img,
        .watchlist-flag-placeholder {
            width: 34px;
            height: 24px;
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
            font-size: 0.95rem;
            font-weight: 800;
            line-height: 1.3;
        }

        .watchlist-country-cell small {
            display: block;
            margin-top: 2px;
            color: #7c8aa5;
            font-size: 0.82rem;
        }

        .watchlist-action-buttons {
            display: flex;
            flex-wrap: wrap;
            gap: 8px;
            min-width: 220px;
        }

        .watchlist-api-card {
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 24px;
            padding: 26px 30px;
        }

        .watchlist-api-card .btn {
            flex: 0 0 auto;
        }

        @media (max-width: 1200px) {
            .watchlist-summary-card,
            .watchlist-analysis-grid {
                grid-template-columns: 1fr;
            }

            .watchlist-summary-grid {
                grid-template-columns: repeat(2, minmax(160px, 1fr));
            }
        }

        @media (max-width: 768px) {
            .watchlist-summary-card,
            .watchlist-card,
            .watchlist-api-card,
            .watchlist-table-card {
                padding: 22px;
                border-radius: 18px;
            }

            .watchlist-summary-grid,
            .risk-level-grid {
                grid-template-columns: 1fr;
            }

            .watchlist-api-card {
                align-items: flex-start;
                flex-direction: column;
            }

            .watchlist-api-card .btn {
                width: 100%;
            }

            .watchlist-chart-box {
                height: 260px;
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

            var labels = chartData.risk ? chartData.risk.labels : [];
            var values = chartData.risk ? chartData.risk.values : [];

            var maxItems = 25;

            labels = labels.slice(0, maxItems);
            values = values.slice(0, maxItems);

            new Chart(canvas, {
                type: 'bar',
                data: {
                    labels: labels,
                    datasets: [
                        {
                            label: 'Top 25 Risk Score Negara',
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
                        padding: {
                            top: 8,
                            right: 8,
                            bottom: 0,
                            left: 0
                        }
                    },
                    scales: {
                        x: {
                            ticks: {
                                autoSkip: true,
                                maxRotation: 45,
                                minRotation: 0
                            },
                            grid: {
                                display: false
                            }
                        },
                        y: {
                            beginAtZero: true,
                            suggestedMax: 100
                        }
                    },
                    plugins: {
                        legend: {
                            position: 'top'
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
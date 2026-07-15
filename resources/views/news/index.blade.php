@extends('layouts.app')

@section('title', 'Intelijen Berita')

@section('content')
    @php
        $averageRisk = $summary['average_risk_score'] ?? 0;

        $riskBadgeClass = match (true) {
            $averageRisk >= 75 => 'bg-danger',
            $averageRisk >= 50 => 'bg-warning text-dark',
            $averageRisk >= 25 => 'badge-risk-medium',
            default => 'badge-risk-low',
        };
    @endphp

    <div class="news-page">
        <section class="news-panel news-header-panel">
            <div class="news-title-block">
                <div class="page-eyebrow">
                    NEWS INTELLIGENCE
                </div>

                <h1>
                    Intelijen Berita
                </h1>

                <p>
                    Pantau berita ekonomi, logistik, perdagangan, dan rantai pasok
                    berdasarkan negara yang dipilih.
                </p>
            </div>

            <form
                method="GET"
                action="{{ route('news.index') }}"
                class="news-filter"
            >
                <label for="country">
                    Pilih Negara
                </label>

                <div class="news-filter-row">
                    <select
                        name="country"
                        id="country"
                        class="form-select"
                    >
                        @foreach ($countries as $country)
                            <option
                                value="{{ $country->iso3_code }}"
                                @selected($selectedCountry && $selectedCountry->id === $country->id)
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
                </div>
            </form>
        </section>

        @if ($apiError)
            <div class="alert alert-warning border-0 shadow-sm mb-0">
                <i class="bi bi-info-circle me-2"></i>
                Data berita belum dapat diperbarui saat ini. Sistem menampilkan data tersimpan.
            </div>
        @endif

        <section class="news-summary-grid">
            <article class="news-stat-card">
                <span>Total Artikel</span>

                <strong>
                    {{ number_format($summary['total_articles'] ?? 0, 0, ',', '.') }}
                </strong>

                <small>
                    Artikel tersimpan
                </small>
            </article>

            <article class="news-stat-card">
                <span>Risk Rata-rata</span>

                <strong>
                    {{ number_format($summary['average_risk_score'] ?? 0, 2, ',', '.') }}
                </strong>

                <small>
                    Skor risiko
                </small>
            </article>

            <article class="news-stat-card">
                <span>Positif</span>

                <strong>
                    {{ number_format($summary['positive_count'] ?? 0, 0, ',', '.') }}
                </strong>

                <small>
                    Sentimen positif
                </small>
            </article>

            <article class="news-stat-card">
                <span>Netral</span>

                <strong>
                    {{ number_format($summary['neutral_count'] ?? 0, 0, ',', '.') }}
                </strong>

                <small>
                    Sentimen netral
                </small>
            </article>

            <article class="news-stat-card">
                <span>Negatif</span>

                <strong>
                    {{ number_format($summary['negative_count'] ?? 0, 0, ',', '.') }}
                </strong>

                <small>
                    Sentimen negatif
                </small>
            </article>

            <article class="news-stat-card">
                <span>Status</span>

                <strong>
                    <span class="badge {{ $riskBadgeClass }} news-status-badge">
                        {{ $summary['risk_label'] ?? 'Risiko Rendah' }}
                    </span>
                </strong>

                <small>
                    Rata-rata berita
                </small>
            </article>
        </section>

        <section class="news-middle-grid">
            <article class="news-panel news-chart-panel">
                <div class="news-section-heading">
                    <h2>
                        Komposisi Sentimen
                    </h2>

                    <p>
                        Ringkasan sentimen artikel.
                    </p>
                </div>

                <div class="news-chart-box">
                    <canvas id="newsSentimentChart"></canvas>
                </div>
            </article>

            <article class="news-panel news-country-panel">
                <div class="news-section-heading">
                    <h2>
                        Negara
                    </h2>

                    <p>
                        Negara yang sedang dipantau.
                    </p>
                </div>

                <div class="news-country-card">
                    @if ($selectedCountry?->flag_url)
                        <img
                            src="{{ $selectedCountry->flag_url }}"
                            alt="Bendera {{ $selectedCountry->name }}"
                        >
                    @else
                        <div class="news-flag-fallback">
                            <i class="bi bi-flag"></i>
                        </div>
                    @endif

                    <div>
                        <strong>
                            {{ $selectedCountry?->name ?? '-' }}
                        </strong>

                        <span>
                            {{ $selectedCountry?->iso3_code ?? '-' }}
                        </span>
                    </div>
                </div>
            </article>
        </section>

        <section class="news-panel news-list-panel">
            <div class="news-section-heading">
                <h2>
                    Berita Terkini
                </h2>

                <p>
                    Artikel terbaru beserta sentimen dan skor risiko.
                </p>
            </div>

            <div class="news-list">
                @forelse ($newsItems as $news)
                    @php
                        $sentimentBadge = match ($news['sentiment'] ?? null) {
                            'positive' => 'news-badge-positive',
                            'negative' => 'news-badge-negative',
                            default => 'news-badge-neutral',
                        };

                        $imageUrl = $news['image_url'] ?? null;
                    @endphp

                    <article class="news-item">
                        <div class="news-thumb">
                            @if ($imageUrl)
                                <img
                                    src="{{ $imageUrl }}"
                                    alt="{{ $news['title'] ?? 'Gambar berita' }}"
                                    loading="lazy"
                                    onerror="this.style.display='none'; this.nextElementSibling.style.display='flex';"
                                >

                                <div
                                    class="news-thumb-fallback"
                                    style="display: none;"
                                >
                                    <i class="bi bi-newspaper"></i>
                                </div>
                            @else
                                <div class="news-thumb-fallback">
                                    <i class="bi bi-newspaper"></i>
                                </div>
                            @endif
                        </div>

                        <div class="news-item-body">
                            <div class="news-meta">
                                <span>
                                    <i class="bi bi-building me-1"></i>
                                    {{ $news['source_name'] ?? 'Sumber tidak tersedia' }}
                                </span>

                                <span>
                                    <i class="bi bi-calendar-event me-1"></i>
                                    {{ $news['published_at'] ?? '-' }}
                                </span>
                            </div>

                            <h3>
                                {{ $news['title'] ?? 'Judul berita tidak tersedia' }}
                            </h3>

                            @if (!empty($news['description']))
                                <p>
                                    {{ \Illuminate\Support\Str::limit($news['description'], 170) }}
                                </p>
                            @endif
                        </div>

                        <div class="news-item-action">
                            <span class="news-sentiment {{ $sentimentBadge }}">
                                {{ $news['sentiment_label'] ?? 'Netral' }}
                            </span>

                            <div class="news-risk">
                                <span>Risk</span>

                                <strong>
                                    {{ number_format((float) ($news['risk_score'] ?? 0), 2, ',', '.') }}
                                </strong>
                            </div>

                            @if (!empty($news['url']))
                                <a
                                    href="{{ $news['url'] }}"
                                    target="_blank"
                                    rel="noopener"
                                    class="btn btn-sm btn-outline-primary"
                                >
                                    Buka
                                </a>
                            @endif
                        </div>
                    </article>
                @empty
                    <div class="alert alert-warning border-0 shadow-sm mb-0">
                        <i class="bi bi-info-circle me-2"></i>
                        Berita belum tersedia. Klik tombol
                        <strong>Perbarui</strong>.
                    </div>
                @endforelse
            </div>
        </section>
    </div>
@endsection

@push('styles')
    <style>
        .news-page {
            width: 100%;
            max-width: 1180px;
            margin: 0 auto;
            display: flex;
            flex-direction: column;
            gap: 18px;
        }

        .news-panel,
        .news-stat-card {
            background: #ffffff;
            border: 1px solid rgba(148, 163, 184, 0.22);
            border-radius: 18px;
            box-shadow: 0 14px 32px rgba(15, 23, 42, 0.045);
        }

        .news-header-panel {
            display: grid;
            grid-template-columns: minmax(280px, 1fr) minmax(360px, 0.85fr);
            gap: 24px;
            align-items: end;
            padding: 24px;
        }

        .news-title-block h1 {
            margin: 0 0 8px;
            color: #111827;
            font-size: clamp(1.8rem, 3vw, 2.55rem);
            font-weight: 900;
            line-height: 1.12;
        }

        .news-title-block p {
            margin: 0;
            color: #7c8aa5;
            font-size: 0.95rem;
            line-height: 1.6;
            max-width: 680px;
        }

        .news-filter label {
            display: block;
            margin-bottom: 8px;
            color: #334155;
            font-size: 0.9rem;
            font-weight: 800;
        }

        .news-filter-row {
            display: grid;
            grid-template-columns: minmax(0, 1fr) auto auto;
            gap: 10px;
            align-items: center;
        }

        .news-filter-row .form-select,
        .news-filter-row .btn {
            height: 44px;
            border-radius: 12px;
            font-weight: 750;
        }

        .news-summary-grid {
            display: grid;
            grid-template-columns: repeat(6, minmax(0, 1fr));
            gap: 12px;
        }

        .news-stat-card {
            min-width: 0;
            padding: 16px;
        }

        .news-stat-card span {
            display: block;
            color: #7c8aa5;
            font-size: 0.8rem;
            font-weight: 800;
            margin-bottom: 8px;
        }

        .news-stat-card strong {
            display: block;
            color: #111827;
            font-size: 1.28rem;
            font-weight: 900;
            line-height: 1.2;
            word-break: break-word;
        }

        .news-stat-card small {
            display: block;
            margin-top: 6px;
            color: #7c8aa5;
            font-size: 0.76rem;
            line-height: 1.35;
        }

        .badge-risk-low {
            background: #eef6ff !important;
            color: #1d4ed8 !important;
            border: 1px solid rgba(37, 99, 235, 0.18);
        }

        .badge-risk-medium {
            background: #cffafe !important;
            color: #0f172a !important;
            border: 1px solid rgba(6, 182, 212, 0.24);
        }

        .news-status-badge {
            padding: 7px 10px;
            border-radius: 9px;
            font-size: 0.78rem;
            font-weight: 800;
            white-space: nowrap;
        }

        .news-middle-grid {
            display: grid;
            grid-template-columns: minmax(0, 1.35fr) minmax(260px, 0.65fr);
            gap: 18px;
        }

        .news-chart-panel,
        .news-country-panel,
        .news-list-panel {
            padding: 22px;
        }

        .news-section-heading {
            margin-bottom: 14px;
        }

        .news-section-heading h2 {
            margin: 0 0 6px;
            color: #111827;
            font-size: 1.2rem;
            font-weight: 900;
            line-height: 1.3;
        }

        .news-section-heading p {
            margin: 0;
            color: #7c8aa5;
            font-size: 0.9rem;
            line-height: 1.55;
        }

        .news-chart-box {
            width: 100%;
            height: 190px;
        }

        .news-country-card {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 14px;
            border-radius: 16px;
            background: #f8fafc;
            border: 1px solid rgba(148, 163, 184, 0.16);
        }

        .news-country-card img,
        .news-flag-fallback {
            width: 44px;
            height: 30px;
            flex: 0 0 auto;
            border-radius: 8px;
            object-fit: cover;
            background: #e2e8f0;
        }

        .news-flag-fallback {
            display: grid;
            place-items: center;
            color: #64748b;
        }

        .news-country-card strong {
            display: block;
            color: #111827;
            font-weight: 900;
            line-height: 1.25;
        }

        .news-country-card span {
            display: block;
            color: #7c8aa5;
            font-size: 0.82rem;
        }

        .news-list {
            display: flex;
            flex-direction: column;
            gap: 12px;
        }

        .news-item {
            display: grid;
            grid-template-columns: 150px minmax(0, 1fr) 110px;
            gap: 14px;
            align-items: stretch;
            padding: 12px;
            border-radius: 16px;
            border: 1px solid rgba(148, 163, 184, 0.18);
            background: #ffffff;
        }

        .news-thumb {
            width: 100%;
            height: 105px;
            border-radius: 13px;
            overflow: hidden;
            background: #f1f5f9;
        }

        .news-thumb img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            display: block;
        }

        .news-thumb-fallback {
            width: 100%;
            height: 100%;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #64748b;
            background: linear-gradient(135deg, #f8fafc, #e2e8f0);
            font-size: 1.8rem;
        }

        .news-item-body {
            min-width: 0;
        }

        .news-meta {
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
            margin-bottom: 6px;
            color: #7c8aa5;
            font-size: 0.78rem;
        }

        .news-item-body h3 {
            margin: 0 0 6px;
            color: #111827;
            font-size: 0.98rem;
            font-weight: 900;
            line-height: 1.38;
        }

        .news-item-body p {
            margin: 0;
            color: #64748b;
            font-size: 0.86rem;
            line-height: 1.55;
        }

        .news-item-action {
            display: flex;
            flex-direction: column;
            align-items: flex-end;
            justify-content: flex-start;
            gap: 8px;
            text-align: right;
        }

        .news-sentiment {
            min-width: 82px;
            display: inline-flex;
            justify-content: center;
            align-items: center;
            padding: 7px 10px;
            border-radius: 999px;
            font-size: 0.76rem;
            font-weight: 850;
        }

        .news-badge-positive {
            background: #ecfdf5;
            color: #047857;
            border: 1px solid rgba(16, 185, 129, 0.18);
        }

        .news-badge-neutral {
            background: #f1f5f9;
            color: #475569;
            border: 1px solid rgba(100, 116, 139, 0.18);
        }

        .news-badge-negative {
            background: #fef2f2;
            color: #b91c1c;
            border: 1px solid rgba(239, 68, 68, 0.2);
        }

        .news-risk span {
            display: block;
            color: #7c8aa5;
            font-size: 0.72rem;
            font-weight: 800;
        }

        .news-risk strong {
            display: block;
            color: #111827;
            font-size: 1rem;
            font-weight: 900;
            line-height: 1.2;
        }

        @media (max-width: 1280px) {
            .news-page {
                max-width: 100%;
            }

            .news-header-panel,
            .news-middle-grid {
                grid-template-columns: 1fr;
            }

            .news-summary-grid {
                grid-template-columns: repeat(3, minmax(0, 1fr));
            }
        }

        @media (max-width: 860px) {
            .news-filter-row,
            .news-summary-grid {
                grid-template-columns: 1fr;
            }

            .news-filter-row .btn {
                width: 100%;
            }

            .news-item {
                grid-template-columns: 1fr;
            }

            .news-thumb {
                height: 180px;
            }

            .news-item-action {
                align-items: flex-start;
                text-align: left;
            }
        }

        @media (max-width: 576px) {
            .news-header-panel,
            .news-chart-panel,
            .news-country-panel,
            .news-list-panel {
                padding: 18px;
                border-radius: 16px;
            }

            .news-chart-box {
                height: 180px;
            }
        }
    </style>
@endpush

@push('scripts')
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

    <script
        id="newsChartData"
        type="application/json"
    >{!! json_encode($chartData ?? [], JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT) !!}</script>

    <script>
        document.addEventListener('DOMContentLoaded', function () {
            if (typeof Chart === 'undefined') {
                return;
            }

            var chartDataElement = document.getElementById('newsChartData');
            var chartData = {};

            try {
                chartData = JSON.parse(chartDataElement.textContent || '{}');
            } catch (error) {
                chartData = {};
            }

            var canvas = document.getElementById('newsSentimentChart');

            if (!canvas) {
                return;
            }

            new Chart(canvas, {
                type: 'bar',
                data: {
                    labels: chartData.sentiment ? chartData.sentiment.labels : [],
                    datasets: [
                        {
                            label: 'Jumlah Artikel',
                            data: chartData.sentiment ? chartData.sentiment.values : [],
                            borderWidth: 1,
                            borderRadius: 7,
                            maxBarThickness: 38
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
                                    size: 10
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
                }
            });
        });
    </script>
@endpush
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
        <section class="news-top-grid">
            <div class="news-title-area">
                <div class="page-eyebrow">
                    NEWS INTELLIGENCE
                </div>

                <h1>
                    Intelijen Berita
                </h1>

                <p>
                    Berita ekonomi, logistik, perdagangan, shipping, dan rantai pasok berdasarkan negara yang dipilih.
                </p>
            </div>

            <div class="news-country-mini-card">
                <div class="news-country-flag">
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
                </div>

                <div>
                    <span>
                        Negara Dipantau
                    </span>

                    <strong>
                        {{ $selectedCountry?->name ?? '-' }}
                    </strong>

                    <small>
                        {{ $selectedCountry?->iso3_code ?? '-' }}
                        •
                        {{ $selectedCountry?->region ?? '-' }}
                    </small>
                </div>
            </div>
        </section>

        <section class="news-filter-card">
            <form
                method="GET"
                action="{{ route('news.index') }}"
                class="news-filter"
            >
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
            </form>
        </section>

        @if ($apiError)
            <div class="news-alert">
                <i class="bi bi-info-circle"></i>

                <span>
                    Data berita belum dapat diperbarui. Sistem menampilkan data tersimpan.
                </span>
            </div>
        @endif

        <section class="news-summary-grid">
            <article class="news-stat-card">
                <span>Total Artikel</span>

                <strong>
                    {{ number_format($summary['total_articles'] ?? 0, 0, ',', '.') }}
                </strong>

                <small>
                    Artikel
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
                    Sentimen
                </small>
            </article>

            <article class="news-stat-card">
                <span>Netral</span>

                <strong>
                    {{ number_format($summary['neutral_count'] ?? 0, 0, ',', '.') }}
                </strong>

                <small>
                    Sentimen
                </small>
            </article>

            <article class="news-stat-card">
                <span>Negatif</span>

                <strong>
                    {{ number_format($summary['negative_count'] ?? 0, 0, ',', '.') }}
                </strong>

                <small>
                    Sentimen
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
                    Rata-rata
                </small>
            </article>
        </section>

        <section class="news-main-grid">
            <article class="news-card news-chart-card">
                <div class="news-card-heading">
                    <div>
                        <span>
                            Komposisi Sentimen
                        </span>

                        <h2>
                            Grafik Sentimen
                        </h2>
                    </div>

                    <small>
                        Lexicon Based
                    </small>
                </div>

                <div class="news-chart-box">
                    <canvas id="newsSentimentChart"></canvas>
                </div>
            </article>

            <article class="news-card news-method-card">
                <div class="news-card-heading">
                    <div>
                        <span>
                            Metode Analisis
                        </span>

                        <h2>
                            Lexicon Based Sentiment
                        </h2>
                    </div>
                </div>

                <div class="news-method-grid">
                    <div>
                        <span>Positive Words</span>

                        <strong>
                            Growth, stable, recovery
                        </strong>
                    </div>

                    <div>
                        <span>Negative Words</span>

                        <strong>
                            Crisis, war, delay
                        </strong>
                    </div>

                    <div>
                        <span>Output</span>

                        <strong>
                            Positif / Netral / Negatif
                        </strong>
                    </div>

                    <div>
                        <span>Risk</span>

                        <strong>
                            Skor berita
                        </strong>
                    </div>
                </div>
            </article>
        </section>

        <section class="news-card news-list-panel">
            <div class="news-card-heading">
                <div>
                    <span>
                        Artikel Berita
                    </span>

                    <h2>
                        Berita Terkini
                    </h2>
                </div>

                <small>
                    Logistics • Trade • Shipping • Economy
                </small>
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
                                    {{ \Illuminate\Support\Str::limit($news['description'], 120) }}
                                </p>
                            @endif

                            <div class="news-score-row">
                                <span>
                                    Positif:
                                    {{ number_format($news['positive_score'] ?? 0, 0, ',', '.') }}
                                </span>

                                <span>
                                    Netral:
                                    {{ number_format($news['neutral_score'] ?? 0, 0, ',', '.') }}
                                </span>

                                <span>
                                    Negatif:
                                    {{ number_format($news['negative_score'] ?? 0, 0, ',', '.') }}
                                </span>
                            </div>
                        </div>

                        <div class="news-item-action">
                            <span class="news-sentiment {{ $sentimentBadge }}">
                                {{ $news['sentiment_label'] ?? 'Netral' }}
                            </span>

                            <div class="news-risk">
                                <span>
                                    Risk
                                </span>

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
                    <div class="news-empty">
                        <i class="bi bi-info-circle"></i>

                        <span>
                            Berita belum tersedia. Klik tombol <strong>Perbarui</strong>.
                        </span>
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
            padding: 14px 18px 24px;
            display: flex;
            flex-direction: column;
            gap: 12px;
        }

        .news-top-grid {
            display: grid;
            grid-template-columns: minmax(0, 1fr) 300px;
            gap: 14px;
            align-items: end;
        }

        .news-title-area {
            padding-left: 6px;
        }

        .news-title-area h1 {
            margin: 0 0 4px;
            color: #111827;
            font-size: 1.65rem;
            font-weight: 900;
            line-height: 1.1;
        }

        .news-title-area p {
            margin: 0;
            color: #7c8aa5;
            font-size: 0.84rem;
            line-height: 1.45;
            max-width: 760px;
        }

        .news-country-mini-card,
        .news-filter-card,
        .news-card,
        .news-stat-card {
            background: #ffffff;
            border: 1px solid rgba(148, 163, 184, 0.22);
            border-radius: 16px;
            box-shadow: 0 10px 24px rgba(15, 23, 42, 0.04);
        }

        .news-country-mini-card {
            display: flex;
            align-items: center;
            gap: 10px;
            padding: 10px 12px;
            min-height: 76px;
        }

        .news-country-flag {
            width: 42px;
            height: 28px;
            border-radius: 8px;
            overflow: hidden;
            background: #e2e8f0;
            flex: 0 0 auto;
        }

        .news-country-flag img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }

        .news-flag-fallback {
            width: 100%;
            height: 100%;
            display: grid;
            place-items: center;
            color: #64748b;
        }

        .news-country-mini-card span,
        .news-card-heading span,
        .news-stat-card span,
        .news-method-grid span {
            display: block;
            margin-bottom: 3px;
            color: #7c8aa5;
            font-size: 0.68rem;
            font-weight: 800;
            text-transform: uppercase;
            letter-spacing: 0.035em;
        }

        .news-country-mini-card strong {
            display: block;
            color: #111827;
            font-size: 0.95rem;
            font-weight: 900;
            line-height: 1.2;
        }

        .news-country-mini-card small {
            display: block;
            color: #7c8aa5;
            font-size: 0.72rem;
            line-height: 1.3;
        }

        .news-filter-card {
            width: fit-content;
            max-width: 100%;
            padding: 10px 12px;
        }

        .news-filter {
            display: grid;
            grid-template-columns: 520px 105px 105px;
            gap: 8px;
            align-items: center;
        }

        .news-filter .form-select,
        .news-filter .btn {
            height: 38px;
            border-radius: 10px;
            font-size: 0.82rem;
            font-weight: 800;
        }

        .news-filter .form-select {
            max-width: 520px;
        }

        .news-alert {
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

        .news-summary-grid {
            display: grid;
            grid-template-columns: repeat(6, minmax(0, 1fr));
            gap: 10px;
        }

        .news-stat-card {
            min-width: 0;
            padding: 12px 14px;
        }

        .news-stat-card strong {
            display: block;
            color: #111827;
            font-size: 1.12rem;
            font-weight: 900;
            line-height: 1.2;
            word-break: break-word;
        }

        .news-stat-card small {
            display: block;
            margin-top: 4px;
            color: #7c8aa5;
            font-size: 0.7rem;
            line-height: 1.3;
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
            display: inline-flex;
            justify-content: center;
            min-width: 135px;
            padding: 5px 8px;
            border-radius: 999px;
            font-size: 0.7rem;
            font-weight: 850;
            white-space: nowrap;
        }

        .news-main-grid {
            display: grid;
            grid-template-columns: minmax(280px, 0.75fr) minmax(420px, 1.25fr);
            gap: 10px;
        }

        .news-card {
            padding: 14px;
        }

        .news-card-heading {
            display: flex;
            align-items: flex-end;
            justify-content: space-between;
            gap: 10px;
            margin-bottom: 10px;
        }

        .news-card-heading h2 {
            margin: 0;
            color: #111827;
            font-size: 0.98rem;
            font-weight: 900;
            line-height: 1.25;
        }

        .news-card-heading small {
            color: #7c8aa5;
            font-size: 0.72rem;
            font-weight: 750;
            white-space: nowrap;
        }

        .news-chart-box {
            width: 100%;
            height: 155px;
        }

        .news-method-grid {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 8px;
        }

        .news-method-grid > div {
            padding: 9px;
            border-radius: 12px;
            background: #f8fafc;
            border: 1px solid rgba(148, 163, 184, 0.14);
        }

        .news-method-grid strong {
            display: block;
            color: #111827;
            font-size: 0.76rem;
            font-weight: 850;
            line-height: 1.35;
        }

        .news-list-panel {
            padding: 14px;
        }

        .news-list {
            display: flex;
            flex-direction: column;
            gap: 8px;
        }

        .news-item {
            display: grid;
            grid-template-columns: 82px minmax(0, 1fr) 90px;
            gap: 10px;
            align-items: stretch;
            padding: 9px;
            border-radius: 14px;
            border: 1px solid rgba(148, 163, 184, 0.16);
            background: #ffffff;
        }

        .news-thumb {
            width: 82px;
            height: 64px;
            border-radius: 10px;
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
            font-size: 1.2rem;
        }

        .news-item-body {
            min-width: 0;
        }

        .news-meta {
            display: flex;
            flex-wrap: wrap;
            gap: 8px;
            margin-bottom: 4px;
            color: #7c8aa5;
            font-size: 0.66rem;
        }

        .news-item-body h3 {
            margin: 0 0 4px;
            color: #111827;
            font-size: 0.84rem;
            font-weight: 900;
            line-height: 1.3;
        }

        .news-item-body p {
            margin: 0;
            color: #64748b;
            font-size: 0.72rem;
            line-height: 1.38;
        }

        .news-score-row {
            display: flex;
            flex-wrap: wrap;
            gap: 5px;
            margin-top: 6px;
        }

        .news-score-row span {
            padding: 3px 6px;
            border-radius: 999px;
            background: #f8fafc;
            border: 1px solid rgba(148, 163, 184, 0.16);
            color: #64748b;
            font-size: 0.64rem;
            font-weight: 750;
        }

        .news-item-action {
            display: flex;
            flex-direction: column;
            align-items: flex-end;
            justify-content: flex-start;
            gap: 6px;
            text-align: right;
        }

        .news-sentiment {
            min-width: 70px;
            display: inline-flex;
            justify-content: center;
            align-items: center;
            padding: 5px 7px;
            border-radius: 999px;
            font-size: 0.66rem;
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
            font-size: 0.62rem;
            font-weight: 800;
        }

        .news-risk strong {
            display: block;
            color: #111827;
            font-size: 0.84rem;
            font-weight: 900;
            line-height: 1.2;
        }

        .news-item-action .btn {
            padding: 3px 8px;
            border-radius: 8px;
            font-size: 0.66rem;
            font-weight: 800;
        }

        .news-empty {
            display: flex;
            align-items: center;
            gap: 8px;
            padding: 12px;
            border-radius: 14px;
            background: #f8fafc;
            color: #64748b;
            border: 1px dashed rgba(148, 163, 184, 0.45);
            font-size: 0.82rem;
        }

        @media (max-width: 1280px) {
            .news-summary-grid {
                grid-template-columns: repeat(3, minmax(0, 1fr));
            }

            .news-main-grid {
                grid-template-columns: 1fr;
            }
        }

        @media (max-width: 980px) {
            .news-top-grid {
                grid-template-columns: 1fr;
            }

            .news-filter-card {
                width: 100%;
            }

            .news-filter {
                grid-template-columns: 1fr;
            }

            .news-filter .form-select {
                max-width: 100%;
            }

            .news-item {
                grid-template-columns: 1fr;
            }

            .news-thumb {
                width: 100%;
                height: 145px;
            }

            .news-item-action {
                align-items: flex-start;
                text-align: left;
            }
        }

        @media (max-width: 720px) {
            .news-page {
                padding: 12px;
            }

            .news-summary-grid,
            .news-method-grid {
                grid-template-columns: repeat(2, minmax(0, 1fr));
            }

            .news-title-area {
                padding-left: 0;
            }

            .news-title-area h1 {
                font-size: 1.45rem;
            }
        }

        @media (max-width: 520px) {
            .news-summary-grid,
            .news-method-grid {
                grid-template-columns: 1fr;
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
                            borderRadius: 6,
                            maxBarThickness: 28
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
        });
    </script>
@endpush
@extends('layouts.app')

@section('title', 'News Intelligence')

@section('content')
    <div class="news-page">
        <section class="news-hero">
            <div>
                <div class="page-eyebrow">
                    NEWS INTELLIGENCE
                </div>

                <h1>
                    News Intelligence
                </h1>

                <p>
                    Pantau berita ekonomi, logistik, perdagangan, shipping, dan geopolitik untuk membaca potensi risiko rantai pasok global.
                </p>
            </div>

            <div class="news-country-card">
                @if ($selectedCountry->flag_url)
                    <img
                        src="{{ $selectedCountry->flag_url }}"
                        alt="Bendera {{ $selectedCountry->name }}"
                    >
                @else
                    <div class="news-flag-placeholder">
                        <i class="bi bi-flag"></i>
                    </div>
                @endif

                <div>
                    <span>Negara Dipantau</span>

                    <strong>
                        {{ $selectedCountry->name }}
                    </strong>

                    <small>
                        {{ $selectedCountry->iso3_code }}
                    </small>
                </div>
            </div>
        </section>

        <section class="news-filter-card">
            <form
                method="GET"
                action="{{ route('news.index') }}"
                class="news-filter-form"
            >
                <div class="news-field">
                    <label for="country">
                        Pilih Negara
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
                                {{ $country->name }} ({{ $country->iso3_code }})
                            </option>
                        @endforeach
                    </select>
                </div>

                <div class="news-field">
                    <label for="category">
                        Kategori
                    </label>

                    <select
                        name="category"
                        id="category"
                        class="form-select"
                    >
                        @foreach ($categories as $key => $label)
                            <option
                                value="{{ $key }}"
                                @selected($selectedCategory === $key)
                            >
                                {{ $label }}
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
            <div class="news-alert">
                <i class="bi bi-info-circle"></i>

                <span>
                    {{ $apiError }}
                </span>
            </div>
        @endif

        <section class="news-summary-grid">
            <article class="news-summary-card">
                <span>Total Berita</span>

                <strong>
                    {{ $summary['total'] ?? 0 }}
                </strong>

                <small>
                    Berita terbaru yang dianalisis
                </small>
            </article>

            <article class="news-summary-card">
                <span>Positive</span>

                <strong>
                    {{ $summary['positive'] ?? 0 }}
                </strong>

                <small>
                    Sentimen positif
                </small>
            </article>

            <article class="news-summary-card">
                <span>Neutral</span>

                <strong>
                    {{ $summary['neutral'] ?? 0 }}
                </strong>

                <small>
                    Sentimen netral
                </small>
            </article>

            <article class="news-summary-card">
                <span>Negative</span>

                <strong>
                    {{ $summary['negative'] ?? 0 }}
                </strong>

                <small>
                    Sentimen negatif
                </small>
            </article>

            <article class="news-summary-card">
                <span>Average News Risk</span>

                <strong>
                    {{ number_format($summary['average_risk'] ?? 0, 2, ',', '.') }}
                </strong>

                <small>
                    {{ $summary['average_risk_label'] ?? 'Low Risk' }}
                </small>
            </article>
        </section>

        <section class="news-insight-grid">
            <article class="news-risk-card">
                <div class="news-heading">
                    <span>News Risk Insight</span>

                    <h2>
                        Dampak Berita terhadap Supply Chain
                    </h2>
                </div>

                <div class="news-risk-row">
                    <div class="news-risk-score {{ $summary['average_risk_class'] ?? 'risk-low' }}">
                        {{ number_format($summary['average_risk'] ?? 0, 0, ',', '.') }}
                    </div>

                    <div>
                        <strong>
                            {{ $summary['average_risk_label'] ?? 'Low Risk' }}
                        </strong>

                        <p>
                            {{ $summary['recommendation'] ?? 'Belum ada rekomendasi.' }}
                        </p>
                    </div>
                </div>
            </article>

            <article class="news-dominant-card">
                <div class="news-heading">
                    <span>Dominant Sentiment</span>

                    <h2>
                        Sentimen Dominan
                    </h2>
                </div>

                <div class="sentiment-badge {{ $summary['dominant_sentiment_class'] ?? 'sentiment-neutral' }}">
                    {{ $summary['dominant_sentiment_label'] ?? 'Neutral' }}
                </div>

                <p>
                    Ringkasan ini dihitung dari berita yang dianalisis menggunakan lexicon positive dan negative words.
                </p>
            </article>
        </section>

        <section class="news-category-card">
            <div class="news-heading">
                <span>Kategori Berita</span>

                <h2>
                    Distribusi Topik
                </h2>
            </div>

            <div class="news-category-grid">
                @foreach ($categoryCounts as $key => $item)
                    <article class="news-category-item">
                        <span>
                            {{ $item['label'] }}
                        </span>

                        <strong>
                            {{ $item['count'] }}
                        </strong>

                        <small>
                            Berita
                        </small>
                    </article>
                @endforeach
            </div>
        </section>

        <section class="news-chart-grid">
            <article class="news-panel">
                <div class="news-heading">
                    <span>Sentiment Chart</span>

                    <h2>
                        Positive, Neutral, Negative
                    </h2>
                </div>

                <div class="news-chart-box">
                    <canvas id="newsSentimentChart"></canvas>
                </div>
            </article>

            <article class="news-panel">
                <div class="news-heading">
                    <span>Category Chart</span>

                    <h2>
                        Kategori Berita
                    </h2>
                </div>

                <div class="news-chart-box">
                    <canvas id="newsCategoryChart"></canvas>
                </div>
            </article>
        </section>

        <section class="news-panel">
            <div class="news-heading">
                <span>Daftar Berita</span>

                <h2>
                    Berita Terkait Supply Chain
                </h2>
            </div>

            <div class="news-list">
                @forelse ($articles as $article)
                    <article class="news-item">
                        <div class="news-image">
                            @if (!empty($article['image_url']))
                                <img
                                    src="{{ $article['image_url'] }}"
                                    alt="{{ $article['title'] }}"
                                >
                            @else
                                <div class="news-image-placeholder">
                                    <i class="bi bi-newspaper"></i>
                                </div>
                            @endif
                        </div>

                        <div class="news-content">
                            <div class="news-meta-row">
                                <span class="category-pill">
                                    {{ $article['category_label'] }}
                                </span>

                                <span class="sentiment-pill {{ $article['sentiment_class'] }}">
                                    {{ $article['sentiment_label'] }}
                                </span>

                                <span class="risk-pill {{ $article['risk_class'] }}">
                                    {{ $article['risk_label'] }}
                                </span>
                            </div>

                            <h3>
                                {{ $article['title'] }}
                            </h3>

                            <p>
                                {{ $article['description'] }}
                            </p>

                            <div class="news-footer">
                                <span>
                                    {{ $article['source_name'] }}
                                    •
                                    {{ $article['published_at'] ?? 'Waktu tidak tersedia' }}
                                </span>

                                @if (!empty($article['url']))
                                    <a
                                        href="{{ $article['url'] }}"
                                        target="_blank"
                                        rel="noopener noreferrer"
                                    >
                                        Baca Berita
                                    </a>
                                @endif
                            </div>
                        </div>
                    </article>
                @empty
                    <div class="news-empty">
                        Berita belum tersedia untuk negara dan kategori yang dipilih.
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
            max-width: 1100px;
            margin: 0 auto;
            padding: 10px 12px 22px;
            display: flex;
            flex-direction: column;
            gap: 10px;
            overflow-x: hidden;
        }

        .news-hero {
            display: grid;
            grid-template-columns: minmax(0, 1fr) 250px;
            gap: 10px;
            align-items: end;
        }

        .news-hero h1 {
            margin: 0 0 4px;
            color: #111827;
            font-size: 1.45rem;
            font-weight: 950;
        }

        .news-hero p {
            margin: 0;
            color: #7c8aa5;
            font-size: 0.8rem;
            line-height: 1.4;
        }

        .news-country-card,
        .news-filter-card,
        .news-summary-card,
        .news-risk-card,
        .news-dominant-card,
        .news-category-card,
        .news-panel {
            background: #ffffff;
            border: 1px solid rgba(148, 163, 184, 0.22);
            border-radius: 15px;
            box-shadow: 0 8px 20px rgba(15, 23, 42, 0.035);
            min-width: 0;
            overflow: hidden;
        }

        .news-country-card {
            display: flex;
            align-items: center;
            gap: 10px;
            min-height: 66px;
            padding: 9px 11px;
        }

        .news-country-card img,
        .news-flag-placeholder {
            width: 40px;
            height: 27px;
            border-radius: 8px;
            object-fit: cover;
            background: #e2e8f0;
        }

        .news-flag-placeholder {
            display: grid;
            place-items: center;
            color: #64748b;
        }

        .news-country-card span,
        .news-heading span,
        .news-field label,
        .news-summary-card span,
        .news-category-item span {
            display: block;
            margin-bottom: 3px;
            color: #7c8aa5;
            font-size: 0.66rem;
            font-weight: 850;
            text-transform: uppercase;
            letter-spacing: 0.035em;
        }

        .news-country-card strong {
            display: block;
            color: #111827;
            font-size: 0.92rem;
            font-weight: 900;
        }

        .news-country-card small {
            color: #7c8aa5;
            font-size: 0.7rem;
        }

        .news-filter-card,
        .news-risk-card,
        .news-dominant-card,
        .news-category-card,
        .news-panel {
            padding: 12px;
        }

        .news-filter-form {
            display: grid;
            grid-template-columns: minmax(0, 1fr) 220px 95px 95px;
            gap: 8px;
            align-items: end;
        }

        .news-filter-form .form-select,
        .news-filter-form .btn {
            height: 36px;
            border-radius: 10px;
            font-size: 0.79rem;
            font-weight: 800;
        }

        .news-alert {
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

        .news-summary-grid {
            display: grid;
            grid-template-columns: repeat(5, minmax(0, 1fr));
            gap: 10px;
        }

        .news-summary-card {
            padding: 11px;
        }

        .news-summary-card strong {
            display: block;
            color: #111827;
            font-size: 1rem;
            font-weight: 950;
        }

        .news-summary-card small {
            color: #7c8aa5;
            font-size: 0.7rem;
        }

        .news-insight-grid {
            display: grid;
            grid-template-columns: minmax(0, 1.4fr) minmax(230px, 0.6fr);
            gap: 10px;
        }

        .news-heading {
            margin-bottom: 9px;
        }

        .news-heading h2 {
            margin: 0;
            color: #111827;
            font-size: 0.95rem;
            font-weight: 950;
        }

        .news-risk-row {
            display: flex;
            gap: 12px;
            align-items: center;
        }

        .news-risk-score {
            width: 70px;
            height: 70px;
            border-radius: 999px;
            display: grid;
            place-items: center;
            font-size: 1.2rem;
            font-weight: 950;
            flex: 0 0 auto;
        }

        .news-risk-row strong,
        .news-dominant-card strong {
            color: #111827;
            font-size: 0.9rem;
            font-weight: 950;
        }

        .news-risk-row p,
        .news-dominant-card p {
            margin: 4px 0 0;
            color: #64748b;
            font-size: 0.76rem;
            line-height: 1.45;
        }

        .sentiment-badge,
        .sentiment-pill,
        .risk-pill,
        .category-pill {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            padding: 5px 8px;
            border-radius: 999px;
            font-size: 0.68rem;
            font-weight: 850;
            white-space: nowrap;
        }

        .sentiment-positive {
            background: #dcfce7;
            color: #166534;
            border: 1px solid rgba(34, 197, 94, 0.22);
        }

        .sentiment-neutral {
            background: #f1f5f9;
            color: #475569;
            border: 1px solid rgba(148, 163, 184, 0.26);
        }

        .sentiment-negative {
            background: #fee2e2;
            color: #991b1b;
            border: 1px solid rgba(239, 68, 68, 0.28);
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

        .category-pill {
            background: #eef6ff;
            color: #1d4ed8;
            border: 1px solid rgba(37, 99, 235, 0.16);
        }

        .news-category-grid {
            display: grid;
            grid-template-columns: repeat(5, minmax(0, 1fr));
            gap: 8px;
        }

        .news-category-item {
            padding: 9px;
            border-radius: 12px;
            background: #f8fafc;
            border: 1px solid rgba(148, 163, 184, 0.16);
        }

        .news-category-item strong {
            display: block;
            color: #111827;
            font-size: 0.95rem;
            font-weight: 950;
        }

        .news-category-item small {
            color: #7c8aa5;
            font-size: 0.7rem;
        }

        .news-chart-grid {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 10px;
        }

        .news-chart-box {
            height: 170px;
        }

        .news-list {
            display: flex;
            flex-direction: column;
            gap: 8px;
        }

        .news-item {
            display: grid;
            grid-template-columns: 120px minmax(0, 1fr);
            gap: 10px;
            padding: 9px;
            border-radius: 13px;
            background: #f8fafc;
            border: 1px solid rgba(148, 163, 184, 0.16);
            min-width: 0;
        }

        .news-image {
            width: 120px;
            height: 86px;
            border-radius: 12px;
            overflow: hidden;
            background: #e2e8f0;
        }

        .news-image img,
        .news-image-placeholder {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }

        .news-image-placeholder {
            display: grid;
            place-items: center;
            color: #64748b;
            font-size: 1.3rem;
        }

        .news-content {
            min-width: 0;
        }

        .news-meta-row {
            display: flex;
            flex-wrap: wrap;
            gap: 5px;
            margin-bottom: 5px;
        }

        .news-content h3 {
            margin: 0 0 4px;
            color: #111827;
            font-size: 0.92rem;
            line-height: 1.3;
            font-weight: 950;
        }

        .news-content p {
            margin: 0 0 6px;
            color: #64748b;
            font-size: 0.76rem;
            line-height: 1.4;
        }

        .news-footer {
            display: flex;
            justify-content: space-between;
            gap: 10px;
            align-items: center;
            color: #7c8aa5;
            font-size: 0.72rem;
            font-weight: 750;
        }

        .news-footer a {
            color: #2563eb;
            text-decoration: none;
            font-weight: 900;
            white-space: nowrap;
        }

        .news-empty {
            padding: 18px;
            text-align: center;
            color: #7c8aa5;
            font-size: 0.82rem;
        }

        @media (max-width: 1100px) {
            .news-summary-grid,
            .news-category-grid {
                grid-template-columns: repeat(3, minmax(0, 1fr));
            }

            .news-insight-grid,
            .news-chart-grid {
                grid-template-columns: 1fr;
            }
        }

        @media (max-width: 900px) {
            .news-hero,
            .news-filter-form {
                grid-template-columns: 1fr;
            }

            .news-filter-form .btn {
                width: 100%;
            }

            .news-summary-grid,
            .news-category-grid {
                grid-template-columns: repeat(2, minmax(0, 1fr));
            }

            .news-item {
                grid-template-columns: 1fr;
            }

            .news-image {
                width: 100%;
                height: 160px;
            }

            .news-footer {
                flex-direction: column;
                align-items: flex-start;
            }
        }

        @media (max-width: 640px) {
            .news-page {
                padding: 10px;
            }

            .news-hero h1 {
                font-size: 1.25rem;
            }

            .news-summary-grid,
            .news-category-grid {
                grid-template-columns: 1fr;
            }

            .news-risk-row {
                flex-direction: column;
                align-items: flex-start;
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
                                    precision: 0,
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

            createBarChart(
                'newsSentimentChart',
                chartData.sentiment?.labels || [],
                chartData.sentiment?.values || [],
                'Sentiment'
            );

            createBarChart(
                'newsCategoryChart',
                chartData.category?.labels || [],
                chartData.category?.values || [],
                'Kategori Berita'
            );
        });
    </script>
@endpush
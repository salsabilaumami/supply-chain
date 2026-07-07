@extends('layouts.app')

@section('title', 'Intelijen Berita')

@section('content')
@php
    $totalNews = $news->count();

    $positiveCount = $news->filter(function ($item) {
        return $item->sentiment && $item->sentiment->sentiment === 'positive';
    })->count();

    $neutralCount = $news->filter(function ($item) {
        return $item->sentiment && $item->sentiment->sentiment === 'neutral';
    })->count();

    $negativeCount = $news->filter(function ($item) {
        return $item->sentiment && $item->sentiment->sentiment === 'negative';
    })->count();

    $averageRisk = $news->avg(function ($item) {
        return $item->sentiment ? (float) $item->sentiment->risk_score : 0;
    });

    $averageRisk = $totalNews > 0 ? round($averageRisk, 2) : 0;

    if ($averageRisk <= 30) {
        $riskLevel = 'Low Risk';
        $riskBadgeClass = 'text-bg-success';
    } elseif ($averageRisk <= 60) {
        $riskLevel = 'Medium Risk';
        $riskBadgeClass = 'text-bg-warning';
    } else {
        $riskLevel = 'High Risk';
        $riskBadgeClass = 'text-bg-danger';
    }

    $riskProgressWidth = min(100, max(0, $averageRisk));
@endphp

<div class="dashboard-page">
    <section class="dashboard-header">
        <div class="dashboard-heading">
            <div class="page-eyebrow">
                News Intelligence
            </div>

            <h1 class="page-title">
                Intelijen Berita
            </h1>

            <p class="page-description">
                Pantau berita ekonomi, logistik, perdagangan, dan rantai pasok
                berdasarkan negara yang dipilih. Sistem akan menganalisis
                sentimen berita menggunakan kamus kata positif dan negatif.
            </p>
        </div>

        <form
            method="GET"
            action="{{ route('news.index') }}"
            class="country-selector"
        >
            <label for="country" class="country-selector-label">
                Pilih Negara
            </label>

            <div class="country-selector-control">
                <select
                    name="country"
                    id="country"
                    class="form-select"
                    onchange="this.form.submit()"
                >
                    @foreach ($countries as $country)
                        <option
                            value="{{ $country->iso3_code }}"
                            {{ $selectedCountry->id === $country->id ? 'selected' : '' }}
                        >
                            {{ $country->name }} - {{ $country->iso3_code }}
                        </option>
                    @endforeach
                </select>
            </div>
        </form>
    </section>

    @if ($errorMessage)
        <div class="alert alert-warning border-0 shadow-sm">
            <i class="bi bi-exclamation-triangle me-2"></i>
            {{ $errorMessage }}
        </div>
    @endif

    <section class="country-overview-card">
        <div class="country-overview-main">
            <div class="country-flag">
                @if ($selectedCountry->flag_url)
                    <img
                        src="{{ $selectedCountry->flag_url }}"
                        alt="Bendera {{ $selectedCountry->name }}"
                    >
                @else
                    <div class="country-flag-placeholder">
                        <i class="bi bi-flag"></i>
                    </div>
                @endif
            </div>

            <div class="country-identity">
                <span class="country-overview-label">
                    Negara Dipantau
                </span>

                <h2>
                    {{ $selectedCountry->name }}
                </h2>

                <p>
                    Berita logistik, perdagangan, ekonomi, dan supply chain.
                </p>
            </div>
        </div>

        <div class="country-overview-stats">
            <div class="country-stat">
                <span>Total Berita</span>
                <strong>{{ $totalNews }}</strong>
                <small>Artikel tersimpan</small>
            </div>

            <div class="country-stat">
                <span>Positive</span>
                <strong>{{ $positiveCount }}</strong>
                <small>Sentimen positif</small>
            </div>

            <div class="country-stat">
                <span>Neutral</span>
                <strong>{{ $neutralCount }}</strong>
                <small>Sentimen netral</small>
            </div>

            <div class="country-stat">
                <span>Negative</span>
                <strong>{{ $negativeCount }}</strong>
                <small>Sentimen negatif</small>
            </div>
        </div>
    </section>

    <section class="risk-analysis-grid">
        <article class="analysis-card total-risk-card">
            <div class="analysis-card-header">
                <div>
                    <span class="analysis-label">
                        Risiko Berita
                    </span>

                    <strong class="total-risk-score">
                        {{ number_format($averageRisk, 2) }}
                    </strong>
                </div>

                <span class="badge {{ $riskBadgeClass }} px-3 py-2">
                    {{ $riskLevel }}
                </span>
            </div>

            <div class="progress total-risk-progress">
                <div
                    id="newsRiskProgress"
                    class="progress-bar"
                    role="progressbar"
                    data-risk-width="{{ $riskProgressWidth }}"
                    aria-valuenow="{{ $averageRisk }}"
                    aria-valuemin="0"
                    aria-valuemax="100"
                ></div>
            </div>

            <p class="analysis-description">
                Risiko berita dihitung dari hasil analisis sentimen.
                Semakin banyak kata negatif seperti war, crisis, delay,
                inflation, dan disruption, maka semakin tinggi risiko berita.
            </p>
        </article>

        <article class="analysis-card">
            <div class="analysis-heading">
                <h3>
                    Ringkasan Sentimen
                </h3>

                <p>
                    Sistem menggunakan metode lexicon based sentiment analysis,
                    yaitu mencocokkan kata pada judul dan deskripsi berita
                    dengan kamus kata positif dan negatif.
                </p>
            </div>

            <div class="row g-3 mt-2">
                <div class="col-md-4">
                    <div class="country-stat">
                        <span>Positive</span>
                        <strong>{{ $positiveCount }}</strong>
                        <small>Berita bernada baik</small>
                    </div>
                </div>

                <div class="col-md-4">
                    <div class="country-stat">
                        <span>Neutral</span>
                        <strong>{{ $neutralCount }}</strong>
                        <small>Berita netral</small>
                    </div>
                </div>

                <div class="col-md-4">
                    <div class="country-stat">
                        <span>Negative</span>
                        <strong>{{ $negativeCount }}</strong>
                        <small>Berita berisiko</small>
                    </div>
                </div>
            </div>
        </article>
    </section>

    <section class="economic-section">
        <div class="section-heading">
            <div class="page-eyebrow">
                Daftar Artikel
            </div>

            <h2>
                Berita Terkait {{ $selectedCountry->name }}
            </h2>

            <p>
                Data berita diambil dari GNews API, disimpan ke tabel
                <strong>news_caches</strong>, lalu dianalisis ke tabel
                <strong>news_sentiments</strong>.
            </p>
        </div>

        <div class="row g-4">
            @forelse ($news as $item)
                @php
                    $sentiment = $item->sentiment;

                    $sentimentText = $sentiment
                        ? ucfirst($sentiment->sentiment)
                        : 'Not analyzed';

                    $sentimentBadgeClass = 'text-bg-secondary';

                    if ($sentiment && $sentiment->sentiment === 'positive') {
                        $sentimentBadgeClass = 'text-bg-success';
                    } elseif ($sentiment && $sentiment->sentiment === 'neutral') {
                        $sentimentBadgeClass = 'text-bg-warning';
                    } elseif ($sentiment && $sentiment->sentiment === 'negative') {
                        $sentimentBadgeClass = 'text-bg-danger';
                    }
                @endphp

                <div class="col-lg-6">
                    <article class="analysis-card h-100">
                        @if ($item->image_url)
                            <div class="mb-3">
                                <img
                                    src="{{ $item->image_url }}"
                                    alt="{{ $item->title }}"
                                    class="img-fluid rounded-4"
                                    style="width: 100%; max-height: 220px; object-fit: cover;"
                                >
                            </div>
                        @endif

                        <div class="d-flex justify-content-between align-items-start gap-3 mb-3">
                            <div>
                                <span class="page-eyebrow">
                                    {{ $item->source_name ?? 'Unknown Source' }}
                                </span>

                                <h3 class="mt-2 mb-2">
                                    {{ $item->title }}
                                </h3>
                            </div>

                            <span class="badge {{ $sentimentBadgeClass }} px-3 py-2">
                                {{ $sentimentText }}
                            </span>
                        </div>

                        <p class="analysis-description">
                            {{ $item->description ?? 'Tidak ada deskripsi berita.' }}
                        </p>

                        <div class="row g-3 mt-2">
                            <div class="col-6 col-md-3">
                                <div class="country-stat">
                                    <span>Positive</span>

                                    <strong>
                                        {{ $sentiment ? $sentiment->positive_score : 0 }}
                                    </strong>
                                </div>
                            </div>

                            <div class="col-6 col-md-3">
                                <div class="country-stat">
                                    <span>Negative</span>

                                    <strong>
                                        {{ $sentiment ? $sentiment->negative_score : 0 }}
                                    </strong>
                                </div>
                            </div>

                            <div class="col-6 col-md-3">
                                <div class="country-stat">
                                    <span>Neutral</span>

                                    <strong>
                                        {{ $sentiment ? $sentiment->neutral_score : 0 }}
                                    </strong>
                                </div>
                            </div>

                            <div class="col-6 col-md-3">
                                <div class="country-stat">
                                    <span>Risk</span>

                                    <strong>
                                        {{ $sentiment ? number_format((float) $sentiment->risk_score, 0) : 0 }}
                                    </strong>
                                </div>
                            </div>
                        </div>

                        <div class="d-flex justify-content-between align-items-center mt-4">
                            <small class="text-muted">
                                {{ $item->published_at ? $item->published_at->format('d M Y H:i') : '-' }}
                            </small>

                            <a
                                href="{{ $item->url }}"
                                target="_blank"
                                rel="noopener noreferrer"
                                class="btn btn-sm btn-outline-primary"
                            >
                                Buka Berita
                            </a>
                        </div>
                    </article>
                </div>
            @empty
                <div class="col-12">
                    <div class="analysis-card text-center py-5">
                        <h3>
                            Belum ada berita
                        </h3>

                        <p class="text-muted mb-0">
                            Data berita belum tersedia atau API belum berhasil mengambil artikel.
                        </p>
                    </div>
                </div>
            @endforelse
        </div>
    </section>
</div>
@endsection

@push('scripts')
    <script>
        const newsRiskProgress = document.getElementById('newsRiskProgress');

        if (newsRiskProgress) {
            const riskWidth = Number(newsRiskProgress.dataset.riskWidth || 0);
            newsRiskProgress.style.width = `${riskWidth}%`;
        }
    </script>
@endpush
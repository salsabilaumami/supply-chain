@extends('layouts.app')

@section('title', 'Country Monitoring')

@section('content')
    <div class="country-dashboard-page">
        <section class="country-dashboard-top">
            <div class="country-title-area">
                <div class="page-eyebrow">
                    GLOBAL COUNTRY DASHBOARD
                </div>

                <h1>
                    Country Monitoring
                </h1>

                <p>
                    Pilih negara untuk melihat indikator utama: GDP, inflasi, populasi,
                    mata uang, dan cuaca saat ini.
                </p>
            </div>

            @if ($selectedCountry)
                <div class="country-mini-card">
                    <div class="country-mini-flag">
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

                    <div>
                        <span>
                            Negara Dipantau
                        </span>

                        <strong>
                            {{ $selectedCountry->name }}
                        </strong>

                        <small>
                            {{ $selectedCountry->iso2_code }}
                            /
                            {{ $selectedCountry->iso3_code }}
                            •
                            {{ $selectedCountry->region ?? '-' }}
                        </small>
                    </div>
                </div>
            @endif
        </section>

        @if ($countries->isEmpty())
            <div class="country-alert">
                <i class="bi bi-exclamation-triangle"></i>

                <span>
                    Data negara belum tersedia. Jalankan seeder terlebih dahulu.
                </span>
            </div>
        @elseif ($selectedCountry)
            @php
                $gdp = $economicSummary['gdp'] ?? null;
                $inflation = $economicSummary['inflation'] ?? null;
                $population = $economicSummary['population'] ?? null;
                $exports = $economicSummary['exports'] ?? null;
                $imports = $economicSummary['imports'] ?? null;

                $weatherAvailable = $weatherSummary && ($weatherSummary['available'] ?? false);
                $currencyAvailable = $currencySummary && ($currencySummary['available'] ?? false);
                $riskAvailable = $riskSummary && ($riskSummary['available'] ?? false);
                $newsAvailable = $newsSummary && ($newsSummary['available'] ?? false);

                $riskLevel = $riskSummary['risk_level'] ?? null;

                $riskBadgeClass = match ($riskLevel) {
                    'critical' => 'risk-critical',
                    'high' => 'risk-high',
                    'moderate' => 'risk-medium',
                    'low' => 'risk-low',
                    default => 'risk-empty',
                };

                $economicChartLabels = [
                    'GDP',
                    'Inflasi',
                    'Populasi',
                    'Ekspor',
                    'Impor',
                ];

                $economicChartValues = [
                    round((float) ($gdp['value'] ?? 0) / 1000000000000, 2),
                    round((float) ($inflation['value'] ?? 0), 2),
                    round((float) ($population['value'] ?? 0) / 1000000, 2),
                    round((float) ($exports['value'] ?? 0) / 1000000000, 2),
                    round((float) ($imports['value'] ?? 0) / 1000000000, 2),
                ];

                $weatherChartLabels = [
                    'Temperatur',
                    'Curah Hujan',
                    'Angin',
                    'Risiko Cuaca',
                ];

                $weatherChartValues = [
                    round((float) ($weatherSummary['temperature'] ?? 0), 2),
                    round((float) ($weatherSummary['precipitation'] ?? 0), 2),
                    round((float) ($weatherSummary['wind_speed'] ?? 0), 2),
                    round((float) ($weatherSummary['weather_risk'] ?? 0), 2),
                ];

                $riskComponentLabels = collect($riskSummary['components'] ?? [])
                    ->pluck('component_label')
                    ->values()
                    ->all();

                $riskComponentValues = collect($riskSummary['components'] ?? [])
                    ->pluck('weighted_score')
                    ->map(fn ($value) => round((float) $value, 2))
                    ->values()
                    ->all();

                if (empty($riskComponentLabels)) {
                    $riskComponentLabels = ['Belum dihitung'];
                    $riskComponentValues = [0];
                }

                $sentimentChartLabels = [
                    'Positif',
                    'Netral',
                    'Negatif',
                ];

                $sentimentChartValues = [
                    (int) ($newsSummary['positive_count'] ?? 0),
                    (int) ($newsSummary['neutral_count'] ?? 0),
                    (int) ($newsSummary['negative_count'] ?? 0),
                ];

                $countryChartData = [
                    'economic' => [
                        'labels' => $economicChartLabels,
                        'values' => $economicChartValues,
                    ],
                    'weather' => [
                        'labels' => $weatherChartLabels,
                        'values' => $weatherChartValues,
                    ],
                    'risk' => [
                        'labels' => $riskComponentLabels,
                        'values' => $riskComponentValues,
                    ],
                    'sentiment' => [
                        'labels' => $sentimentChartLabels,
                        'values' => $sentimentChartValues,
                    ],
                ];
            @endphp

            <section class="country-filter-card">
                <form
                    method="GET"
                    action="{{ route('countries.index') }}"
                    class="country-filter-form"
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
                </form>

                <div class="country-filter-actions">
                    @if ($isFavorite ?? false)
                        <form
                            method="POST"
                            action="{{ route('watchlist.destroy', $selectedCountry->id) }}"
                            onsubmit="return confirm('Hapus negara ini dari favorit?')"
                        >
                            @csrf
                            @method('DELETE')

                            <button
                                type="submit"
                                class="btn btn-outline-danger"
                            >
                                <i class="bi bi-bookmark-check-fill me-1"></i>
                                Favorit
                            </button>
                        </form>
                    @else
                        <form
                            method="POST"
                            action="{{ route('watchlist.store') }}"
                        >
                            @csrf

                            <input
                                type="hidden"
                                name="country_id"
                                value="{{ $selectedCountry->id }}"
                            >

                            <button
                                type="submit"
                                class="btn btn-outline-primary"
                            >
                                <i class="bi bi-bookmark-plus me-1"></i>
                                Simpan
                            </button>
                        </form>
                    @endif
                </div>
            </section>

            <section class="country-primary-grid">
                <article class="country-primary-card country-profile-card">
                    <span>Profil Negara</span>

                    <div class="country-profile-row">
                        <div class="country-profile-flag">
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

                        <div>
                            <strong>
                                {{ $selectedCountry->name }}
                            </strong>

                            <small>
                                {{ $selectedCountry->official_name ?? 'Nama resmi belum tersedia' }}
                            </small>
                        </div>
                    </div>

                    <div class="country-profile-meta">
                        <div>
                            <span>Ibu Kota</span>
                            <b>{{ $selectedCountry->capital ?? '-' }}</b>
                        </div>

                        <div>
                            <span>Wilayah</span>
                            <b>{{ $selectedCountry->subregion ?? ($selectedCountry->region ?? '-') }}</b>
                        </div>
                    </div>
                </article>

                <article class="country-primary-card">
                    <span>GDP</span>

                    <strong>
                        {{ $gdp['display_value'] ?? 'Belum tersedia' }}
                    </strong>

                    <small>
                        @if (!empty($gdp['year']))
                            Tahun {{ $gdp['year'] }}
                        @else
                            Data ekonomi belum tersinkron
                        @endif
                    </small>
                </article>

                <article class="country-primary-card">
                    <span>Inflasi</span>

                    <strong>
                        {{ $inflation['display_value'] ?? 'Belum tersedia' }}
                    </strong>

                    <small>
                        @if (!empty($inflation['year']))
                            Tahun {{ $inflation['year'] }}
                        @else
                            Data inflasi belum tersedia
                        @endif
                    </small>
                </article>

                <article class="country-primary-card">
                    <span>Populasi</span>

                    <strong>
                        {{ $population['display_value'] ?? 'Belum tersedia' }}
                    </strong>

                    <small>
                        @if (!empty($population['year']))
                            Tahun {{ $population['year'] }}
                        @else
                            Data populasi belum tersedia
                        @endif
                    </small>
                </article>

                <article class="country-primary-card">
                    <span>Mata Uang</span>

                    <strong>
                        {{ $selectedCountry->currency_code ?? '-' }}
                    </strong>

                    <small>
                        {{ $selectedCountry->currency_name ?? 'Mata uang belum tersedia' }}
                    </small>
                </article>

                <article class="country-primary-card country-weather-card">
                    <span>Cuaca Saat Ini</span>

                    <strong>
                        @if ($weatherAvailable)
                            {{ number_format($weatherSummary['temperature'], 1, ',', '.') }}°C
                        @else
                            Belum tersedia
                        @endif
                    </strong>

                    <small>
                        @if ($weatherAvailable)
                            {{ $weatherSummary['weather_description'] }}
                            •
                            Hujan {{ number_format($weatherSummary['precipitation'], 1, ',', '.') }} mm
                            •
                            Angin {{ number_format($weatherSummary['wind_speed'], 1, ',', '.') }} km/jam
                        @else
                            Data cuaca belum tersinkron
                        @endif
                    </small>
                </article>
            </section>

            <section class="country-support-grid">
                <article class="country-support-card">
                    <span>Risk Score</span>

                    <strong>
                        {{ $riskSummary['display_total_score'] ?? 'Belum dihitung' }}
                    </strong>

                    <b class="{{ $riskBadgeClass }}">
                        {{ $riskSummary['risk_level_label'] ?? 'Belum dihitung' }}
                    </b>
                </article>

                <article class="country-support-card">
                    <span>Kurs</span>

                    <strong>
                        {{ $currencySummary['display_rate'] ?? 'Belum tersedia' }}
                    </strong>

                    <small>
                        {{ $currencySummary['display_change'] ?? 'Belum ada pembanding' }}
                    </small>
                </article>

                <article class="country-support-card">
                    <span>Ekspor</span>

                    <strong>
                        {{ $exports['display_value'] ?? 'Belum tersedia' }}
                    </strong>

                    <small>
                        @if (!empty($exports['year']))
                            Tahun {{ $exports['year'] }}
                        @else
                            Tambahan ekonomi
                        @endif
                    </small>
                </article>

                <article class="country-support-card">
                    <span>Impor</span>

                    <strong>
                        {{ $imports['display_value'] ?? 'Belum tersedia' }}
                    </strong>

                    <small>
                        @if (!empty($imports['year']))
                            Tahun {{ $imports['year'] }}
                        @else
                            Tambahan ekonomi
                        @endif
                    </small>
                </article>

                <article class="country-support-card">
                    <span>Sentimen Berita</span>

                    <strong>
                        {{ $newsSummary['display_average_risk_score'] ?? 'Belum tersedia' }}
                    </strong>

                    <small>
                        Positif {{ $newsSummary['positive_count'] ?? 0 }}
                        •
                        Netral {{ $newsSummary['neutral_count'] ?? 0 }}
                        •
                        Negatif {{ $newsSummary['negative_count'] ?? 0 }}
                    </small>
                </article>
            </section>

            <section class="country-action-card">
                <div>
                    <span>Update Data</span>

                    <strong>
                        Sinkronisasi Negara
                    </strong>

                    <small>
                        Perbarui data utama dan hitung risk score setelah data tersedia.
                    </small>
                </div>

                <div class="country-action-buttons">
                    <form
                        method="POST"
                        action="{{ route('countries.sync-all') }}"
                    >
                        @csrf

                        <input
                            type="hidden"
                            name="country"
                            value="{{ $selectedCountry->iso3_code }}"
                        >

                        <button
                            type="submit"
                            class="btn btn-success"
                        >
                            Sinkronkan Semua
                        </button>
                    </form>

                    <form
                        method="POST"
                        action="{{ route('countries.calculate-risk') }}"
                    >
                        @csrf

                        <input
                            type="hidden"
                            name="country"
                            value="{{ $selectedCountry->iso3_code }}"
                        >

                        <button
                            type="submit"
                            class="btn btn-danger"
                        >
                            Hitung Risk Score
                        </button>
                    </form>

                    <form
                        method="POST"
                        action="{{ route('countries.sync-economic') }}"
                    >
                        @csrf

                        <input
                            type="hidden"
                            name="country"
                            value="{{ $selectedCountry->iso3_code }}"
                        >

                        <button
                            type="submit"
                            class="btn btn-outline-primary"
                        >
                            Ekonomi
                        </button>
                    </form>

                    <form
                        method="POST"
                        action="{{ route('countries.sync-weather') }}"
                    >
                        @csrf

                        <input
                            type="hidden"
                            name="country"
                            value="{{ $selectedCountry->iso3_code }}"
                        >

                        <button
                            type="submit"
                            class="btn btn-outline-primary"
                        >
                            Cuaca
                        </button>
                    </form>

                    <form
                        method="POST"
                        action="{{ route('countries.sync-currency') }}"
                    >
                        @csrf

                        <input
                            type="hidden"
                            name="country"
                            value="{{ $selectedCountry->iso3_code }}"
                        >

                        <button
                            type="submit"
                            class="btn btn-outline-primary"
                        >
                            Kurs
                        </button>
                    </form>

                    <form
                        method="POST"
                        action="{{ route('countries.sync-news') }}"
                    >
                        @csrf

                        <input
                            type="hidden"
                            name="country"
                            value="{{ $selectedCountry->iso3_code }}"
                        >

                        <button
                            type="submit"
                            class="btn btn-outline-primary"
                        >
                            Berita
                        </button>
                    </form>
                </div>
            </section>

            <section class="country-chart-grid">
                <article class="country-panel-card">
                    <div class="country-panel-heading">
                        <span>Data Visualization</span>

                        <h2>
                            Indikator Ekonomi
                        </h2>
                    </div>

                    <div class="country-chart-box">
                        <canvas id="economicChart"></canvas>
                    </div>
                </article>

                <article class="country-panel-card">
                    <div class="country-panel-heading">
                        <span>Current Weather</span>

                        <h2>
                            Cuaca dan Risiko
                        </h2>
                    </div>

                    <div class="country-chart-box">
                        <canvas id="weatherChart"></canvas>
                    </div>
                </article>

                <article class="country-panel-card">
                    <div class="country-panel-heading">
                        <span>Risk Components</span>

                        <h2>
                            Komponen Risk Score
                        </h2>
                    </div>

                    <div class="country-chart-box">
                        <canvas id="riskComponentChart"></canvas>
                    </div>
                </article>

                <article class="country-panel-card">
                    <div class="country-panel-heading">
                        <span>News Sentiment</span>

                        <h2>
                            Sentimen Berita
                        </h2>
                    </div>

                    <div class="country-chart-box">
                        <canvas id="sentimentChart"></canvas>
                    </div>
                </article>
            </section>

            <section class="country-panel-card">
                <div class="country-panel-heading">
                    <span>Detail Berita</span>

                    <h2>
                        Berita Terkini
                    </h2>
                </div>

                <div class="country-news-list">
                    @forelse (($newsSummary['items'] ?? []) as $news)
                        @php
                            $sentimentClass = match ($news['sentiment']) {
                                'positive' => 'sentiment-positive',
                                'negative' => 'sentiment-negative',
                                default => 'sentiment-neutral',
                            };
                        @endphp

                        <article class="country-news-card">
                            <div>
                                <strong>
                                    {{ $news['title'] }}
                                </strong>

                                @if ($news['description'])
                                    <p>
                                        {{ \Illuminate\Support\Str::limit($news['description'], 150) }}
                                    </p>
                                @endif

                                <small>
                                    {{ $news['source_name'] ?? 'Sumber tidak tersedia' }}
                                    •
                                    {{ $news['published_at'] ?? '-' }}
                                </small>
                            </div>

                            <div class="country-news-side">
                                <b class="{{ $sentimentClass }}">
                                    {{ $news['sentiment_label'] }}
                                </b>

                                <span>
                                    Risk {{ number_format($news['risk_score'], 2, ',', '.') }}
                                </span>

                                @if ($news['url'])
                                    <a
                                        href="{{ $news['url'] }}"
                                        target="_blank"
                                        rel="noopener"
                                    >
                                        Buka
                                    </a>
                                @endif
                            </div>
                        </article>
                    @empty
                        <div class="country-empty-state">
                            <i class="bi bi-info-circle"></i>

                            <span>
                                Berita belum tersedia. Gunakan tombol sinkronisasi berita.
                            </span>
                        </div>
                    @endforelse
                </div>
            </section>
        @endif
    </div>
@endsection

@push('styles')
    <style>
        .country-dashboard-page {
            width: 100%;
            max-width: 1180px;
            margin: 0 auto;
            padding: 14px 18px 24px;
            display: flex;
            flex-direction: column;
            gap: 12px;
        }

        .country-dashboard-top {
            display: grid;
            grid-template-columns: minmax(0, 1fr) 300px;
            gap: 14px;
            align-items: end;
        }

        .country-title-area {
            padding-left: 6px;
        }

        .country-title-area h1 {
            margin: 0 0 4px;
            color: #111827;
            font-size: 1.65rem;
            font-weight: 900;
            line-height: 1.1;
        }

        .country-title-area p {
            margin: 0;
            color: #7c8aa5;
            font-size: 0.84rem;
            line-height: 1.45;
            max-width: 760px;
        }

        .country-mini-card,
        .country-filter-card,
        .country-primary-card,
        .country-support-card,
        .country-action-card,
        .country-panel-card {
            background: #ffffff;
            border: 1px solid rgba(148, 163, 184, 0.22);
            border-radius: 16px;
            box-shadow: 0 10px 24px rgba(15, 23, 42, 0.04);
        }

        .country-mini-card {
            display: flex;
            align-items: center;
            gap: 10px;
            padding: 10px 12px;
            min-height: 76px;
        }

        .country-mini-flag,
        .country-profile-flag {
            width: 42px;
            height: 28px;
            border-radius: 8px;
            overflow: hidden;
            background: #e2e8f0;
            flex: 0 0 auto;
        }

        .country-profile-flag {
            width: 56px;
            height: 38px;
            border-radius: 10px;
        }

        .country-mini-flag img,
        .country-profile-flag img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }

        .country-flag-placeholder {
            width: 100%;
            height: 100%;
            display: grid;
            place-items: center;
            color: #64748b;
        }

        .country-mini-card span,
        .country-primary-card span,
        .country-support-card span,
        .country-action-card span,
        .country-panel-heading span,
        .country-profile-meta span {
            display: block;
            margin-bottom: 3px;
            color: #7c8aa5;
            font-size: 0.68rem;
            font-weight: 800;
            text-transform: uppercase;
            letter-spacing: 0.035em;
        }

        .country-mini-card strong {
            display: block;
            color: #111827;
            font-size: 0.95rem;
            font-weight: 900;
            line-height: 1.2;
        }

        .country-mini-card small {
            display: block;
            color: #7c8aa5;
            font-size: 0.72rem;
            line-height: 1.3;
        }

        .country-filter-card {
            width: fit-content;
            max-width: 100%;
            padding: 10px 12px;
            display: flex;
            align-items: center;
            gap: 8px;
            flex-wrap: wrap;
        }

        .country-filter-form {
            display: grid;
            grid-template-columns: 520px 105px;
            gap: 8px;
            align-items: center;
        }

        .country-filter-actions {
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .country-filter-form .form-select,
        .country-filter-form .btn,
        .country-filter-actions .btn,
        .country-action-buttons .btn {
            height: 38px;
            border-radius: 10px;
            font-size: 0.82rem;
            font-weight: 800;
        }

        .country-alert,
        .country-empty-state {
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

        .country-primary-grid {
            display: grid;
            grid-template-columns: 1.25fr repeat(5, minmax(0, 1fr));
            gap: 10px;
        }

        .country-primary-card,
        .country-support-card {
            min-width: 0;
            padding: 12px 14px;
        }

        .country-primary-card strong,
        .country-support-card strong {
            display: block;
            color: #111827;
            font-size: 0.98rem;
            font-weight: 900;
            line-height: 1.25;
            word-break: break-word;
        }

        .country-primary-card small,
        .country-support-card small {
            display: block;
            margin-top: 5px;
            color: #7c8aa5;
            font-size: 0.7rem;
            line-height: 1.35;
        }

        .country-profile-row {
            display: flex;
            align-items: center;
            gap: 10px;
            margin-bottom: 12px;
        }

        .country-profile-row strong {
            font-size: 1rem;
        }

        .country-profile-meta {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 8px;
        }

        .country-profile-meta > div {
            padding: 8px;
            border-radius: 12px;
            background: #f8fafc;
            border: 1px solid rgba(148, 163, 184, 0.14);
        }

        .country-profile-meta b {
            display: block;
            color: #111827;
            font-size: 0.78rem;
            line-height: 1.3;
        }

        .country-weather-card {
            grid-column: span 1;
        }

        .country-support-grid {
            display: grid;
            grid-template-columns: repeat(5, minmax(0, 1fr));
            gap: 10px;
        }

        .country-support-card b,
        .risk-low,
        .risk-medium,
        .risk-high,
        .risk-critical,
        .risk-empty,
        .sentiment-positive,
        .sentiment-neutral,
        .sentiment-negative {
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

        .risk-low,
        .sentiment-positive {
            background: #eef6ff;
            color: #1d4ed8;
            border: 1px solid rgba(37, 99, 235, 0.18);
        }

        .risk-medium,
        .sentiment-neutral {
            background: #cffafe;
            color: #0f172a;
            border: 1px solid rgba(6, 182, 212, 0.24);
        }

        .risk-high {
            background: #fef3c7;
            color: #92400e;
            border: 1px solid rgba(245, 158, 11, 0.3);
        }

        .risk-critical,
        .sentiment-negative {
            background: #fee2e2;
            color: #991b1b;
            border: 1px solid rgba(239, 68, 68, 0.28);
        }

        .risk-empty {
            background: #f1f5f9;
            color: #64748b;
            border: 1px solid rgba(148, 163, 184, 0.22);
        }

        .country-action-card {
            padding: 14px;
            display: grid;
            grid-template-columns: minmax(0, 280px) minmax(0, 1fr);
            gap: 14px;
            align-items: center;
        }

        .country-action-card strong {
            display: block;
            color: #111827;
            font-size: 0.95rem;
            font-weight: 900;
        }

        .country-action-card small {
            display: block;
            margin-top: 4px;
            color: #7c8aa5;
            font-size: 0.72rem;
            line-height: 1.35;
        }

        .country-action-buttons {
            display: flex;
            flex-wrap: wrap;
            gap: 8px;
            justify-content: flex-end;
        }

        .country-chart-grid {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 10px;
        }

        .country-panel-card {
            padding: 14px;
            min-width: 0;
        }

        .country-panel-heading {
            margin-bottom: 10px;
        }

        .country-panel-heading h2 {
            margin: 0;
            color: #111827;
            font-size: 0.98rem;
            font-weight: 900;
            line-height: 1.25;
        }

        .country-chart-box {
            width: 100%;
            height: 170px;
        }

        .country-news-list {
            display: grid;
            gap: 8px;
        }

        .country-news-card {
            display: grid;
            grid-template-columns: minmax(0, 1fr) 130px;
            gap: 10px;
            padding: 10px;
            border-radius: 14px;
            background: #f8fafc;
            border: 1px solid rgba(148, 163, 184, 0.14);
        }

        .country-news-card strong {
            display: block;
            color: #111827;
            font-size: 0.82rem;
            font-weight: 900;
            line-height: 1.35;
        }

        .country-news-card p {
            margin: 4px 0;
            color: #64748b;
            font-size: 0.75rem;
            line-height: 1.4;
        }

        .country-news-card small {
            color: #7c8aa5;
            font-size: 0.7rem;
        }

        .country-news-side {
            display: flex;
            flex-direction: column;
            align-items: flex-end;
            gap: 5px;
        }

        .country-news-side span {
            color: #334155;
            font-size: 0.72rem;
            font-weight: 800;
        }

        .country-news-side a {
            color: #2563eb;
            font-size: 0.72rem;
            font-weight: 850;
            text-decoration: none;
        }

        @media (max-width: 1280px) {
            .country-primary-grid {
                grid-template-columns: repeat(3, minmax(0, 1fr));
            }

            .country-profile-card {
                grid-column: span 3;
            }

            .country-support-grid {
                grid-template-columns: repeat(3, minmax(0, 1fr));
            }
        }

        @media (max-width: 980px) {
            .country-dashboard-top {
                grid-template-columns: 1fr;
            }

            .country-filter-card {
                width: 100%;
            }

            .country-filter-form {
                width: 100%;
                grid-template-columns: 1fr;
            }

            .country-filter-form .btn,
            .country-filter-actions,
            .country-filter-actions .btn {
                width: 100%;
            }

            .country-action-card {
                grid-template-columns: 1fr;
            }

            .country-action-buttons {
                justify-content: flex-start;
            }

            .country-chart-grid {
                grid-template-columns: 1fr;
            }
        }

        @media (max-width: 720px) {
            .country-dashboard-page {
                padding: 12px;
            }

            .country-title-area {
                padding-left: 0;
            }

            .country-title-area h1 {
                font-size: 1.45rem;
            }

            .country-primary-grid,
            .country-support-grid {
                grid-template-columns: repeat(2, minmax(0, 1fr));
            }

            .country-profile-card {
                grid-column: span 2;
            }

            .country-news-card {
                grid-template-columns: 1fr;
            }

            .country-news-side {
                align-items: flex-start;
            }
        }

        @media (max-width: 520px) {
            .country-primary-grid,
            .country-support-grid,
            .country-profile-meta {
                grid-template-columns: 1fr;
            }

            .country-profile-card {
                grid-column: span 1;
            }
        }
    </style>
@endpush

@push('scripts')
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

    <script
        id="countryChartData"
        type="application/json"
    >{!! json_encode($countryChartData ?? [], JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT) !!}</script>

    <script>
        document.addEventListener('DOMContentLoaded', function () {
            if (typeof Chart === 'undefined') {
                return;
            }

            var chartDataElement = document.getElementById('countryChartData');
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
                    return chartData[groupName].values;
                }

                return [];
            }

            function createBarChart(canvasId, label, labels, values, suggestedMax) {
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
                                    autoSkip: false,
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
                                beginAtZero: true,
                                suggestedMax: suggestedMax,
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
                'economicChart',
                'Indikator',
                getChartLabels('economic'),
                getChartValues('economic'),
                null
            );

            createBarChart(
                'weatherChart',
                'Cuaca',
                getChartLabels('weather'),
                getChartValues('weather'),
                100
            );

            createBarChart(
                'riskComponentChart',
                'Risk Score',
                getChartLabels('risk'),
                getChartValues('risk'),
                40
            );

            createBarChart(
                'sentimentChart',
                'Artikel',
                getChartLabels('sentiment'),
                getChartValues('sentiment'),
                null
            );
        });
    </script>
@endpush
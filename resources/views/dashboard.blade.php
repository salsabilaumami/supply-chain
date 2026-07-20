@extends('layouts.app')

@section('title', 'Global Overview')

@section('content')
    @php
        $levelLabels = [
            'low' => 'Low Risk',
            'medium' => 'Medium Risk',
            'moderate' => 'Medium Risk',
            'high' => 'High Risk',
            'critical' => 'Critical Risk',
        ];

        $levelClass = function ($level) {
            return match ($level) {
                'critical' => 'dashboard-risk-critical',
                'high' => 'dashboard-risk-high',
                'medium', 'moderate' => 'dashboard-risk-medium',
                'low' => 'dashboard-risk-low',
                default => 'dashboard-risk-empty',
            };
        };

        $formatMoney = function ($value) {
            if ($value === null) {
                return 'Belum tersedia';
            }

            $value = (float) $value;

            if (abs($value) >= 1_000_000_000_000) {
                return 'US$ ' . number_format($value / 1_000_000_000_000, 2, ',', '.') . ' T';
            }

            if (abs($value) >= 1_000_000_000) {
                return 'US$ ' . number_format($value / 1_000_000_000, 2, ',', '.') . ' B';
            }

            if (abs($value) >= 1_000_000) {
                return 'US$ ' . number_format($value / 1_000_000, 2, ',', '.') . ' M';
            }

            return 'US$ ' . number_format($value, 2, ',', '.');
        };

        $gdpValue = $economicData['gdp']['value'] ?? null;
        $inflationValue = $economicData['inflation']['value'] ?? null;
        $populationValue = $economicData['population']['value'] ?? ($selectedCountry->population ?? null);

        $gdpYear = $economicData['gdp']['year'] ?? '-';
        $inflationYear = $economicData['inflation']['year'] ?? '-';
        $populationYear = $economicData['population']['year'] ?? '-';

        $riskLabel = $levelLabels[$riskLevel] ?? 'Low Risk';
        $riskBadgeClass = $levelClass($riskLevel);

        $weatherBadgeClass = $levelClass($weatherLevel ?? null);
        $inflationBadgeClass = $levelClass($inflationLevel ?? null);
        $currencyBadgeClass = $levelClass($currencyLevel ?? null);
        $newsBadgeClass = $levelClass($newsLevel ?? null);

        $weatherLabel = $levelLabels[$weatherLevel] ?? 'Belum tersedia';
        $inflationLabel = $levelLabels[$inflationLevel] ?? 'Belum tersedia';
        $currencyLabel = $levelLabels[$currencyLevel] ?? 'Belum tersedia';
        $newsLabel = $levelLabels[$newsLevel] ?? 'Belum tersedia';

        $selectedIso = $selectedCountry->iso3_code ?? 'IDN';
    @endphp

    <div class="dashboard-overview-page">
        <section class="dashboard-overview-header">
            <div class="dashboard-overview-title">
                <div class="page-eyebrow">
                    GLOBAL OVERVIEW
                </div>

                <h1>
                    Tinjauan Global
                </h1>

                <p>
                    Ringkasan cepat indikator negara: GDP, inflasi, populasi, mata uang,
                    cuaca saat ini, dan Risk Score.
                </p>
            </div>

            <form
                method="GET"
                action="{{ route('dashboard') }}"
                class="dashboard-country-filter"
            >
                <label for="country">
                    Pilih Negara
                </label>

                <select
                    name="country"
                    id="country"
                    class="form-select"
                    onchange="this.form.submit()"
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

                <noscript>
                    <button
                        type="submit"
                        class="btn btn-primary mt-2"
                    >
                        Tampilkan
                    </button>
                </noscript>
            </form>
        </section>

        <section class="dashboard-country-card">
            <div class="dashboard-country-main">
                <div class="dashboard-country-flag">
                    @if ($selectedCountry->flag_url)
                        <img
                            src="{{ $selectedCountry->flag_url }}"
                            alt="Bendera {{ $selectedCountry->name }}"
                        >
                    @else
                        <div class="dashboard-flag-placeholder">
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
                        {{ $selectedCountry->official_name ?? 'Nama resmi belum tersedia' }}
                    </small>
                </div>
            </div>

            <div class="dashboard-country-detail-grid">
                <div>
                    <span>Ibu Kota</span>
                    <strong>{{ $selectedCountry->capital ?? '-' }}</strong>
                </div>

                <div>
                    <span>Wilayah</span>
                    <strong>{{ $selectedCountry->region ?? '-' }}</strong>
                </div>

                <div>
                    <span>Subwilayah</span>
                    <strong>{{ $selectedCountry->subregion ?? '-' }}</strong>
                </div>

                <div>
                    <span>Kode Negara</span>
                    <strong>{{ $selectedCountry->iso2_code ?? '-' }} / {{ $selectedCountry->iso3_code ?? '-' }}</strong>
                </div>
            </div>
        </section>

        <section class="dashboard-card-grid">
            <article class="dashboard-info-card">
                <div class="dashboard-info-main">
                    <div>
                        <span>GDP</span>

                        <strong>
                            {{ $formatMoney($gdpValue) }}
                        </strong>
                    </div>

                    <div class="dashboard-info-icon">
                        <i class="bi bi-bank"></i>
                    </div>
                </div>

                <div class="dashboard-info-footer">
                    <span>Tahun</span>
                    <strong>{{ $gdpYear }}</strong>
                </div>
            </article>

            <article class="dashboard-info-card">
                <div class="dashboard-info-main">
                    <div>
                        <span>Inflasi</span>

                        <strong>
                            @if ($inflationValue !== null)
                                {{ number_format((float) $inflationValue, 2, ',', '.') }}%
                            @else
                                Belum tersedia
                            @endif
                        </strong>
                    </div>

                    <div class="dashboard-info-icon">
                        <i class="bi bi-graph-up-arrow"></i>
                    </div>
                </div>

                <div class="dashboard-info-footer">
                    <span>Tahun</span>
                    <strong>{{ $inflationYear }}</strong>
                </div>
            </article>

            <article class="dashboard-info-card">
                <div class="dashboard-info-main">
                    <div>
                        <span>Populasi</span>

                        <strong>
                            @if ($populationValue !== null)
                                {{ number_format((float) $populationValue, 0, ',', '.') }}
                            @else
                                Belum tersedia
                            @endif
                        </strong>
                    </div>

                    <div class="dashboard-info-icon">
                        <i class="bi bi-people"></i>
                    </div>
                </div>

                <div class="dashboard-info-footer">
                    <span>Tahun</span>
                    <strong>{{ $populationYear }}</strong>
                </div>
            </article>

            <article class="dashboard-info-card">
                <div class="dashboard-info-main">
                    <div>
                        <span>Mata Uang</span>

                        <strong>
                            {{ $selectedCountry->currency_code ?? '-' }}
                        </strong>
                    </div>

                    <div class="dashboard-info-icon">
                        <i class="bi bi-cash-coin"></i>
                    </div>
                </div>

                <div class="dashboard-info-footer">
                    <span>Nama</span>
                    <strong>{{ $selectedCountry->currency_name ?? '-' }}</strong>
                </div>
            </article>

            <article class="dashboard-info-card">
                <div class="dashboard-info-main">
                    <div>
                        <span>Cuaca Saat Ini</span>

                        <strong>
                            @if ($weatherAvailable && $weatherData)
                                {{ number_format((float) $weatherData->temperature, 1, ',', '.') }}°C
                            @else
                                Belum tersedia
                            @endif
                        </strong>
                    </div>

                    <div class="dashboard-info-icon">
                        <i class="bi bi-cloud-sun"></i>
                    </div>
                </div>

                <div class="dashboard-info-footer">
                    <span>Status</span>

                    <strong>
                        @if ($weatherAvailable && $weatherData)
                            Hujan {{ number_format((float) $weatherData->precipitation, 1, ',', '.') }} mm
                            •
                            Angin {{ number_format((float) $weatherData->wind_speed, 1, ',', '.') }} km/jam
                        @else
                            Data belum tersedia
                        @endif
                    </strong>
                </div>
            </article>

            <article class="dashboard-info-card">
                <div class="dashboard-info-main">
                    <div>
                        <span>Total Risk Score</span>

                        <strong>
                            {{ number_format((float) $totalScore, 2, ',', '.') }}
                        </strong>
                    </div>

                    <div class="dashboard-info-icon">
                        <i class="bi bi-shield-check"></i>
                    </div>
                </div>

                <div class="dashboard-info-footer">
                    <span>Kategori</span>

                    <strong>
                        <b class="{{ $riskBadgeClass }}">
                            {{ $riskLabel }}
                        </b>
                    </strong>
                </div>
            </article>
        </section>

        <section class="dashboard-section-title">
            <span>
                Risk Components
            </span>

            <h2>
                Komponen Risiko Negara
            </h2>
        </section>

        <section class="dashboard-risk-card-grid">
            <article class="dashboard-info-card dashboard-risk-component-card">
                <div class="dashboard-info-main">
                    <div>
                        <span>Weather Risk</span>

                        <strong>
                            {{ number_format((float) $weatherScore, 2, ',', '.') }}
                        </strong>
                    </div>

                    <div class="dashboard-info-icon">
                        <i class="bi bi-cloud-lightning-rain"></i>
                    </div>
                </div>

                <div class="dashboard-info-footer">
                    <span>Kategori</span>

                    <strong>
                        <b class="{{ $weatherBadgeClass }}">
                            {{ $weatherLabel }}
                        </b>
                    </strong>
                </div>
            </article>

            <article class="dashboard-info-card dashboard-risk-component-card">
                <div class="dashboard-info-main">
                    <div>
                        <span>Inflation Risk</span>

                        <strong>
                            {{ number_format((float) $inflationScore, 2, ',', '.') }}
                        </strong>
                    </div>

                    <div class="dashboard-info-icon">
                        <i class="bi bi-activity"></i>
                    </div>
                </div>

                <div class="dashboard-info-footer">
                    <span>Kategori</span>

                    <strong>
                        <b class="{{ $inflationBadgeClass }}">
                            {{ $inflationLabel }}
                        </b>
                    </strong>
                </div>
            </article>

            <article class="dashboard-info-card dashboard-risk-component-card">
                <div class="dashboard-info-main">
                    <div>
                        <span>Exchange Rate Risk</span>

                        <strong>
                            {{ number_format((float) $currencyScore, 2, ',', '.') }}
                        </strong>
                    </div>

                    <div class="dashboard-info-icon">
                        <i class="bi bi-currency-exchange"></i>
                    </div>
                </div>

                <div class="dashboard-info-footer">
                    <span>Kategori</span>

                    <strong>
                        <b class="{{ $currencyBadgeClass }}">
                            {{ $currencyLabel }}
                        </b>
                    </strong>
                </div>
            </article>

            <article class="dashboard-info-card dashboard-risk-component-card">
                <div class="dashboard-info-main">
                    <div>
                        <span>News Sentiment Risk</span>

                        <strong>
                            {{ number_format((float) $newsScore, 2, ',', '.') }}
                        </strong>
                    </div>

                    <div class="dashboard-info-icon">
                        <i class="bi bi-newspaper"></i>
                    </div>
                </div>

                <div class="dashboard-info-footer">
                    <span>Kategori</span>

                    <strong>
                        <b class="{{ $newsBadgeClass }}">
                            {{ $newsLabel }}
                        </b>
                    </strong>
                </div>
            </article>
        </section>

        <section class="dashboard-section-title">
            <span>
                Supporting Data
            </span>

            <h2>
                Data Pendukung
            </h2>
        </section>

        <section class="dashboard-support-grid">
            <article class="dashboard-info-card dashboard-support-card">
                <div class="dashboard-info-main">
                    <div>
                        <span>Kurs USD</span>

                        <strong>
                            @if ($currencyAvailable && $exchangeRate)
                                1 USD =
                                {{ number_format((float) $exchangeRate->rate, 4, ',', '.') }}
                                {{ $exchangeRate->target_currency }}
                            @else
                                Belum tersedia
                            @endif
                        </strong>
                    </div>

                    <div class="dashboard-info-icon">
                        <i class="bi bi-currency-dollar"></i>
                    </div>
                </div>

                <div class="dashboard-info-footer">
                    <span>Perubahan</span>

                    <strong>
                        @if ($currencyAvailable && $exchangeRate && $exchangeRate->change_percentage !== null)
                            {{ number_format((float) $exchangeRate->change_percentage, 4, ',', '.') }}%
                        @else
                            Belum ada pembanding
                        @endif
                    </strong>
                </div>
            </article>

            <article class="dashboard-info-card dashboard-support-card">
                <div class="dashboard-info-main">
                    <div>
                        <span>Artikel Berita</span>

                        <strong>
                            {{ number_format((int) ($newsSummary['total_articles'] ?? 0), 0, ',', '.') }}
                        </strong>
                    </div>

                    <div class="dashboard-info-icon">
                        <i class="bi bi-journal-text"></i>
                    </div>
                </div>

                <div class="dashboard-info-footer">
                    <span>Sentimen</span>

                    <strong>
                        Positif {{ $newsSummary['positive_count'] ?? 0 }}
                        •
                        Netral {{ $newsSummary['neutral_count'] ?? 0 }}
                        •
                        Negatif {{ $newsSummary['negative_count'] ?? 0 }}
                    </strong>
                </div>
            </article>

            <article class="dashboard-info-card dashboard-support-card">
                <div class="dashboard-info-main">
                    <div>
                        <span>Negara Tersedia</span>

                        <strong>
                            {{ number_format((int) ($globalSummary['total_countries'] ?? 0), 0, ',', '.') }}
                        </strong>
                    </div>

                    <div class="dashboard-info-icon">
                        <i class="bi bi-globe2"></i>
                    </div>
                </div>

                <div class="dashboard-info-footer">
                    <span>Status</span>
                    <strong>Total negara dalam sistem</strong>
                </div>
            </article>

            <article class="dashboard-info-card dashboard-support-card">
                <div class="dashboard-info-main">
                    <div>
                        <span>Risk Tertinggi</span>

                        <strong>
                            {{ $globalSummary['highest_risk_country'] ?? 'Belum tersedia' }}
                        </strong>
                    </div>

                    <div class="dashboard-info-icon">
                        <i class="bi bi-exclamation-triangle"></i>
                    </div>
                </div>

                <div class="dashboard-info-footer">
                    <span>Skor</span>
                    <strong>{{ number_format((float) ($globalSummary['highest_risk_score'] ?? 0), 2, ',', '.') }}</strong>
                </div>
            </article>
        </section>

        <section class="dashboard-action-card">
            <div>
                <span>
                    Quick Access
                </span>

                <strong>
                    Menu Analisis Lanjutan
                </strong>

                <small>
                    Buka halaman detail sesuai kebutuhan analisis negara.
                </small>
            </div>

            <div class="dashboard-action-buttons">
                <a
                    href="{{ route('countries.index', ['country' => $selectedIso]) }}"
                    class="btn btn-outline-primary"
                >
                    Country Monitoring
                </a>

                <a
                    href="{{ url('/risk?country=' . $selectedIso) }}"
                    class="btn btn-outline-primary"
                >
                    Risk Scoring
                </a>

                <a
                    href="{{ url('/weather?country=' . $selectedIso) }}"
                    class="btn btn-outline-primary"
                >
                    Weather
                </a>

                <a
                    href="{{ url('/currency?country=' . $selectedIso) }}"
                    class="btn btn-outline-primary"
                >
                    Currency
                </a>

                <a
                    href="{{ url('/news?country=' . $selectedIso) }}"
                    class="btn btn-outline-primary"
                >
                    News
                </a>
            </div>
        </section>
    </div>
@endsection

@push('styles')
    <style>
        .dashboard-overview-page {
            width: 100%;
            max-width: 1180px;
            margin: 0 auto;
            padding: 14px 18px 24px;
            display: flex;
            flex-direction: column;
            gap: 12px;
            overflow-x: hidden;
        }

        .dashboard-overview-header {
            display: grid;
            grid-template-columns: minmax(0, 1fr) 340px;
            gap: 14px;
            align-items: end;
        }

        .dashboard-overview-title {
            padding-left: 6px;
        }

        .dashboard-overview-title h1 {
            margin: 0 0 4px;
            color: #111827;
            font-size: 1.65rem;
            font-weight: 900;
            line-height: 1.1;
        }

        .dashboard-overview-title p {
            margin: 0;
            max-width: 760px;
            color: #7c8aa5;
            font-size: 0.84rem;
            line-height: 1.45;
        }

        .dashboard-country-filter,
        .dashboard-country-card,
        .dashboard-info-card,
        .dashboard-action-card {
            background: #ffffff;
            border: 1px solid rgba(148, 163, 184, 0.22);
            border-radius: 16px;
            box-shadow: 0 10px 24px rgba(15, 23, 42, 0.04);
            min-width: 0;
            overflow: hidden;
        }

        .dashboard-country-filter {
            padding: 12px;
        }

        .dashboard-country-filter label {
            display: block;
            margin-bottom: 6px;
            color: #7c8aa5;
            font-size: 0.68rem;
            font-weight: 850;
            text-transform: uppercase;
            letter-spacing: 0.035em;
        }

        .dashboard-country-filter .form-select {
            height: 38px;
            border-radius: 10px;
            font-size: 0.82rem;
            font-weight: 800;
        }

        .dashboard-country-card {
            padding: 14px;
            display: grid;
            grid-template-columns: minmax(0, 1fr) minmax(0, 1.7fr);
            gap: 12px;
            align-items: center;
        }

        .dashboard-country-main {
            display: flex;
            align-items: center;
            gap: 12px;
            min-width: 0;
        }

        .dashboard-country-flag {
            width: 64px;
            height: 44px;
            border-radius: 12px;
            overflow: hidden;
            background: #e2e8f0;
            flex: 0 0 auto;
        }

        .dashboard-country-flag img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }

        .dashboard-flag-placeholder {
            width: 100%;
            height: 100%;
            display: grid;
            place-items: center;
            color: #64748b;
        }

        .dashboard-country-main span,
        .dashboard-country-detail-grid span,
        .dashboard-info-card span,
        .dashboard-section-title span,
        .dashboard-action-card span {
            display: block;
            margin-bottom: 4px;
            color: #7c8aa5;
            font-size: 0.68rem;
            font-weight: 850;
            text-transform: uppercase;
            letter-spacing: 0.045em;
        }

        .dashboard-country-main strong {
            display: block;
            color: #111827;
            font-size: 1.18rem;
            font-weight: 950;
            line-height: 1.15;
        }

        .dashboard-country-main small {
            display: block;
            margin-top: 4px;
            color: #64748b;
            font-size: 0.76rem;
            line-height: 1.35;
        }

        .dashboard-country-detail-grid {
            display: grid;
            grid-template-columns: repeat(4, minmax(0, 1fr));
            gap: 8px;
        }

        .dashboard-country-detail-grid > div {
            padding: 10px;
            border-radius: 12px;
            background: #f8fafc;
            border: 1px solid rgba(148, 163, 184, 0.14);
            min-width: 0;
        }

        .dashboard-country-detail-grid strong {
            display: block;
            color: #111827;
            font-size: 0.8rem;
            font-weight: 900;
            line-height: 1.25;
            word-break: break-word;
        }

        .dashboard-card-grid {
            display: grid;
            grid-template-columns: repeat(3, minmax(0, 1fr));
            gap: 14px;
        }

        .dashboard-risk-card-grid,
        .dashboard-support-grid {
            display: grid;
            grid-template-columns: repeat(4, minmax(0, 1fr));
            gap: 14px;
        }

        .dashboard-info-card {
            min-height: 158px;
            padding: 24px 32px;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
        }

        .dashboard-info-main {
            display: grid;
            grid-template-columns: minmax(0, 1fr) 66px;
            gap: 16px;
            align-items: start;
        }

        .dashboard-info-card strong {
            display: block;
            color: #111827;
            font-size: 1.32rem;
            font-weight: 950;
            line-height: 1.28;
            word-break: break-word;
        }

        .dashboard-info-icon {
            width: 58px;
            height: 58px;
            border-radius: 18px;
            background: #eef6ff;
            color: #0b63ff;
            display: grid;
            place-items: center;
            font-size: 1.45rem;
            justify-self: end;
        }

        .dashboard-info-footer {
            margin-top: 24px;
            padding-top: 18px;
            border-top: 1px solid rgba(148, 163, 184, 0.18);
            display: flex;
            justify-content: space-between;
            gap: 12px;
            align-items: center;
        }

        .dashboard-info-footer span {
            margin-bottom: 0;
            color: #7c8aa5;
            font-size: 0.78rem;
            font-weight: 600;
            text-transform: none;
            letter-spacing: 0;
        }

        .dashboard-info-footer strong {
            color: #334155;
            font-size: 0.82rem;
            font-weight: 900;
            text-align: right;
        }

        .dashboard-section-title {
            padding: 4px 2px 0;
        }

        .dashboard-section-title h2 {
            margin: 0;
            color: #111827;
            font-size: 1.15rem;
            font-weight: 950;
            line-height: 1.2;
        }

        .dashboard-risk-component-card,
        .dashboard-support-card {
            min-height: 150px;
            padding: 20px 24px;
        }

        .dashboard-action-card {
            padding: 18px 22px;
            display: grid;
            grid-template-columns: minmax(0, 260px) minmax(0, 1fr);
            gap: 14px;
            align-items: center;
        }

        .dashboard-action-card strong {
            display: block;
            color: #111827;
            font-size: 0.98rem;
            font-weight: 950;
            line-height: 1.25;
        }

        .dashboard-action-card small {
            display: block;
            margin-top: 4px;
            color: #7c8aa5;
            font-size: 0.72rem;
            line-height: 1.35;
        }

        .dashboard-action-buttons {
            display: flex;
            flex-wrap: wrap;
            gap: 8px;
            justify-content: flex-end;
        }

        .dashboard-action-buttons .btn {
            height: 38px;
            border-radius: 10px;
            font-size: 0.8rem;
            font-weight: 850;
            display: inline-flex;
            align-items: center;
        }

        .dashboard-risk-low,
        .dashboard-risk-medium,
        .dashboard-risk-high,
        .dashboard-risk-critical,
        .dashboard-risk-empty {
            display: inline-flex;
            justify-content: center;
            align-items: center;
            min-width: 96px;
            padding: 5px 8px;
            border-radius: 999px;
            font-size: 0.68rem;
            font-weight: 850;
            white-space: nowrap;
        }

        .dashboard-risk-low {
            background: #eef6ff;
            color: #1d4ed8;
            border: 1px solid rgba(37, 99, 235, 0.18);
        }

        .dashboard-risk-medium {
            background: #cffafe;
            color: #0f172a;
            border: 1px solid rgba(6, 182, 212, 0.24);
        }

        .dashboard-risk-high {
            background: #fef3c7;
            color: #92400e;
            border: 1px solid rgba(245, 158, 11, 0.3);
        }

        .dashboard-risk-critical {
            background: #fee2e2;
            color: #991b1b;
            border: 1px solid rgba(239, 68, 68, 0.28);
        }

        .dashboard-risk-empty {
            background: #f1f5f9;
            color: #64748b;
            border: 1px solid rgba(148, 163, 184, 0.22);
        }

        @media (max-width: 1280px) {
            .dashboard-card-grid {
                grid-template-columns: repeat(2, minmax(0, 1fr));
            }

            .dashboard-risk-card-grid,
            .dashboard-support-grid {
                grid-template-columns: repeat(2, minmax(0, 1fr));
            }

            .dashboard-country-card {
                grid-template-columns: 1fr;
            }

            .dashboard-country-detail-grid {
                grid-template-columns: repeat(2, minmax(0, 1fr));
            }
        }

        @media (max-width: 980px) {
            .dashboard-overview-header {
                grid-template-columns: 1fr;
            }

            .dashboard-action-card {
                grid-template-columns: 1fr;
            }

            .dashboard-action-buttons {
                justify-content: flex-start;
            }
        }

        @media (max-width: 720px) {
            .dashboard-overview-page {
                padding: 12px;
            }

            .dashboard-overview-title {
                padding-left: 0;
            }

            .dashboard-overview-title h1 {
                font-size: 1.45rem;
            }

            .dashboard-card-grid,
            .dashboard-risk-card-grid,
            .dashboard-support-grid,
            .dashboard-country-detail-grid {
                grid-template-columns: 1fr;
            }

            .dashboard-info-card {
                padding: 20px;
            }

            .dashboard-info-main {
                grid-template-columns: minmax(0, 1fr) 54px;
            }

            .dashboard-info-icon {
                width: 50px;
                height: 50px;
                border-radius: 16px;
                font-size: 1.25rem;
            }

            .dashboard-info-card strong {
                font-size: 1.1rem;
            }

            .dashboard-action-buttons .btn {
                width: 100%;
                justify-content: center;
            }
        }
    </style>
@endpush
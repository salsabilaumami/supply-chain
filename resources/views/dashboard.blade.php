@extends('layouts.app')

@section('title', 'Tinjauan Global')

@section('content')
    @php
        $levelLabels = [
            'low' => 'Rendah',
            'medium' => 'Sedang',
            'high' => 'Tinggi',
            'critical' => 'Kritis',
        ];

        $levelColors = [
            'low' => 'success',
            'medium' => 'warning',
            'high' => 'danger',
            'critical' => 'dark',
        ];
    @endphp

    <div class="dashboard-page">

        {{-- =====================================================
             PAGE HEADER
             ===================================================== --}}
        <section class="dashboard-header">
            <div class="dashboard-heading">
                <div class="page-eyebrow">
                    Pusat Intelijen Risiko Rantai Pasokan
                </div>

                <h1 class="page-title">
                    Tinjauan Global
                </h1>

                <p class="page-description">
                    Pantau risiko ekonomi, cuaca, mata uang, dan berita
                    pada rantai pasokan global.
                </p>
            </div>

            <form
                method="GET"
                action="{{ route('dashboard') }}"
                class="country-selector"
            >
                <label
                    for="country"
                    class="country-selector-label"
                >
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
                                @selected($selectedCountry->id === $country->id)
                            >
                                {{ $country->display_name }}
                            </option>
                        @endforeach
                    </select>

                    <noscript>
                        <button
                            type="submit"
                            class="btn btn-primary"
                        >
                            Tampilkan
                        </button>
                    </noscript>
                </div>
            </form>
        </section>

        {{-- =====================================================
             COUNTRY OVERVIEW
             ===================================================== --}}
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
                        Negara yang Dipantau
                    </span>

                    <h2>
                        {{ $selectedCountry->name }}
                    </h2>

                    <p>
                        {{ $selectedCountry->official_name ?? '-' }}
                    </p>
                </div>
            </div>

            <div class="country-overview-stats">
                <div class="country-stat">
                    <span>Ibu Kota</span>

                    <strong>
                        {{ $selectedCountry->capital ?? '-' }}
                    </strong>
                </div>

                <div class="country-stat">
                    <span>Wilayah</span>

                    <strong>
                        {{ $selectedCountry->region ?? '-' }}
                    </strong>
                </div>

                <div class="country-stat">
                    <span>Mata Uang</span>

                    <strong>
                        {{ $selectedCountry->currency_code ?? '-' }}
                    </strong>

                    <small>
                        {{ $selectedCountry->currency_name ?? '-' }}
                    </small>
                </div>

                <div class="country-stat">
                    <span>Populasi</span>

                    <strong>
                        @if ($economicData['population']['value'] !== null)
                            {{ number_format(
                                $economicData['population']['value'],
                                0,
                                ',',
                                '.'
                            ) }}
                        @else
                            {{ number_format(
                                $selectedCountry->population ?? 0,
                                0,
                                ',',
                                '.'
                            ) }}
                        @endif
                    </strong>

                    <small>
                        @if ($economicData['population']['year'])
                            World Bank {{ $economicData['population']['year'] }}
                        @else
                            Data profil negara
                        @endif
                    </small>
                </div>
            </div>
        </section>

        {{-- =====================================================
             RISK COMPONENTS
             ===================================================== --}}
        <section class="risk-card-grid">

            {{-- Weather Risk --}}
            <article class="risk-card">
                <div class="risk-card-header">
                    <div>
                        <span class="risk-card-label">
                            Risiko Cuaca
                        </span>

                        <strong class="risk-card-score">
                            {{ number_format($weatherScore, 0) }}
                        </strong>

                        <span
                            class="badge text-bg-{{ $levelColors[$weatherLevel] }}"
                        >
                            {{ $levelLabels[$weatherLevel] }}
                        </span>
                    </div>

                    <div class="risk-card-icon">
                        <i class="bi bi-cloud-lightning-rain"></i>
                    </div>
                </div>

                <div class="progress risk-progress">
                    <div
                        class="progress-bar bg-{{ $levelColors[$weatherLevel] }}"
                        role="progressbar"
                        style="width: {{ min(100, max(0, $weatherScore)) }}%"
                    ></div>
                </div>
            </article>

            {{-- Inflation Risk --}}
            <article class="risk-card">
                <div class="risk-card-header">
                    <div>
                        <span class="risk-card-label">
                            Risiko Inflasi
                        </span>

                        <strong class="risk-card-score">
                            {{ number_format($inflationScore, 0) }}
                        </strong>

                        <span
                            class="badge text-bg-{{ $levelColors[$inflationLevel] }}"
                        >
                            {{ $levelLabels[$inflationLevel] }}
                        </span>
                    </div>

                    <div class="risk-card-icon">
                        <i class="bi bi-graph-up-arrow"></i>
                    </div>
                </div>

                <div class="progress risk-progress">
                    <div
                        class="progress-bar bg-{{ $levelColors[$inflationLevel] }}"
                        role="progressbar"
                        style="width: {{ min(100, max(0, $inflationScore)) }}%"
                    ></div>
                </div>
            </article>

            {{-- Currency Risk --}}
            <article class="risk-card">
                <div class="risk-card-header">
                    <div>
                        <span class="risk-card-label">
                            Risiko Mata Uang
                        </span>

                        <strong class="risk-card-score">
                            {{ number_format($currencyScore, 0) }}
                        </strong>

                        <span
                            class="badge text-bg-{{ $levelColors[$currencyLevel] }}"
                        >
                            {{ $levelLabels[$currencyLevel] }}
                        </span>
                    </div>

                    <div class="risk-card-icon">
                        <i class="bi bi-currency-exchange"></i>
                    </div>
                </div>

                <div class="progress risk-progress">
                    <div
                        class="progress-bar bg-{{ $levelColors[$currencyLevel] }}"
                        role="progressbar"
                        style="width: {{ min(100, max(0, $currencyScore)) }}%"
                    ></div>
                </div>
            </article>

            {{-- News Risk --}}
            <article class="risk-card">
                <div class="risk-card-header">
                    <div>
                        <span class="risk-card-label">
                            Risiko Berita
                        </span>

                        <strong class="risk-card-score">
                            {{ number_format($newsScore, 0) }}
                        </strong>

                        <span
                            class="badge text-bg-{{ $levelColors[$newsLevel] }}"
                        >
                            {{ $levelLabels[$newsLevel] }}
                        </span>
                    </div>

                    <div class="risk-card-icon">
                        <i class="bi bi-newspaper"></i>
                    </div>
                </div>

                <div class="progress risk-progress">
                    <div
                        class="progress-bar bg-{{ $levelColors[$newsLevel] }}"
                        role="progressbar"
                        style="width: {{ min(100, max(0, $newsScore)) }}%"
                    ></div>
                </div>
            </article>
        </section>

        {{-- =====================================================
             TOTAL RISK & COMPOSITION
             ===================================================== --}}
        <section class="risk-analysis-grid">

            <article class="analysis-card total-risk-card">
                <div class="analysis-card-header">
                    <div>
                        <span class="analysis-label">
                            Skor Risiko {{ $selectedCountry->name }}
                        </span>

                        <strong class="total-risk-score">
                            {{ number_format($totalScore, 2) }}
                        </strong>
                    </div>

                    <span
                        class="badge text-bg-{{ $levelColors[$riskLevel] }} px-3 py-2"
                    >
                        {{ $levelLabels[$riskLevel] }}
                    </span>
                </div>

                <div class="progress total-risk-progress">
                    <div
                        class="progress-bar bg-{{ $levelColors[$riskLevel] }}"
                        role="progressbar"
                        style="width: {{ min(100, max(0, $totalScore)) }}%"
                    ></div>
                </div>

                <p class="analysis-description">
                    Skor gabungan berdasarkan risiko cuaca, inflasi,
                    mata uang, dan sentimen berita.
                </p>
            </article>

            <article class="analysis-card">
                <div class="analysis-heading">
                    <h3>
                        Komposisi Risiko
                    </h3>

                    <p>
                        Bobot setiap indikator dalam algoritma penilaian risiko.
                    </p>
                </div>

                <div class="weight-list">
                    <div class="weight-item">
                        <div class="weight-label">
                            <span>Cuaca</span>
                            <strong>25%</strong>
                        </div>

                        <div class="progress weight-progress">
                            <div
                                class="progress-bar"
                                style="width: 25%"
                            ></div>
                        </div>
                    </div>

                    <div class="weight-item">
                        <div class="weight-label">
                            <span>Inflasi</span>
                            <strong>25%</strong>
                        </div>

                        <div class="progress weight-progress">
                            <div
                                class="progress-bar"
                                style="width: 25%"
                            ></div>
                        </div>
                    </div>

                    <div class="weight-item">
                        <div class="weight-label">
                            <span>Mata Uang</span>
                            <strong>20%</strong>
                        </div>

                        <div class="progress weight-progress">
                            <div
                                class="progress-bar"
                                style="width: 20%"
                            ></div>
                        </div>
                    </div>

                    <div class="weight-item">
                        <div class="weight-label">
                            <span>Berita</span>
                            <strong>30%</strong>
                        </div>

                        <div class="progress weight-progress">
                            <div
                                class="progress-bar"
                                style="width: 30%"
                            ></div>
                        </div>
                    </div>
                </div>
            </article>
        </section>

        {{-- =====================================================
             ECONOMIC DATA
             ===================================================== --}}
        <section class="economic-section">
            <div class="section-heading">
                <div class="page-eyebrow">
                    Indikator Ekonomi
                </div>

                <h2>
                    Data Ekonomi {{ $selectedCountry->name }}
                </h2>

                <p>
                    Data ekonomi terbaru yang tersedia dari World Bank.
                </p>
            </div>

            @if ($hasEconomicData)
                <div class="economic-card-grid">

                    {{-- GDP --}}
                    <article class="economic-card">
                        <div class="economic-card-main">
                            <div>
                                <span class="economic-card-label">
                                    Produk Domestik Bruto
                                </span>

                                <strong class="economic-card-value">
                                    @if ($economicData['gdp']['value'] !== null)
                                        US$
                                        {{ number_format(
                                            $economicData['gdp']['value'] / 1000000000000,
                                            2
                                        ) }}
                                        T
                                    @else
                                        -
                                    @endif
                                </strong>
                            </div>

                            <div class="economic-card-icon">
                                <i class="bi bi-bank"></i>
                            </div>
                        </div>

                        <div class="economic-card-footer">
                            <span>Tahun</span>

                            <strong>
                                {{ $economicData['gdp']['year'] ?? '-' }}
                            </strong>
                        </div>
                    </article>

                    {{-- Inflation --}}
                    <article class="economic-card">
                        <div class="economic-card-main">
                            <div>
                                <span class="economic-card-label">
                                    Inflasi Aktual
                                </span>

                                <strong class="economic-card-value">
                                    @if ($economicData['inflation']['value'] !== null)
                                        {{ number_format(
                                            $economicData['inflation']['value'],
                                            2
                                        ) }}%
                                    @else
                                        -
                                    @endif
                                </strong>
                            </div>

                            <div class="economic-card-icon">
                                <i class="bi bi-graph-up"></i>
                            </div>
                        </div>

                        <div class="economic-card-footer">
                            <span>Tahun</span>

                            <strong>
                                {{ $economicData['inflation']['year'] ?? '-' }}
                            </strong>
                        </div>
                    </article>

                    {{-- Exports --}}
                    <article class="economic-card">
                        <div class="economic-card-main">
                            <div>
                                <span class="economic-card-label">
                                    Ekspor Barang & Jasa
                                </span>

                                <strong class="economic-card-value">
                                    @if ($economicData['exports']['value'] !== null)
                                        US$
                                        {{ number_format(
                                            $economicData['exports']['value'] / 1000000000,
                                            2
                                        ) }}
                                        B
                                    @else
                                        -
                                    @endif
                                </strong>
                            </div>

                            <div class="economic-card-icon">
                                <i class="bi bi-box-arrow-up-right"></i>
                            </div>
                        </div>

                        <div class="economic-card-footer">
                            <span>Tahun</span>

                            <strong>
                                {{ $economicData['exports']['year'] ?? '-' }}
                            </strong>
                        </div>
                    </article>

                    {{-- Imports --}}
                    <article class="economic-card">
                        <div class="economic-card-main">
                            <div>
                                <span class="economic-card-label">
                                    Impor Barang & Jasa
                                </span>

                                <strong class="economic-card-value">
                                    @if ($economicData['imports']['value'] !== null)
                                        US$
                                        {{ number_format(
                                            $economicData['imports']['value'] / 1000000000,
                                            2
                                        ) }}
                                        B
                                    @else
                                        -
                                    @endif
                                </strong>
                            </div>

                            <div class="economic-card-icon">
                                <i class="bi bi-box-arrow-in-down-right"></i>
                            </div>
                        </div>

                        <div class="economic-card-footer">
                            <span>Tahun</span>

                            <strong>
                                {{ $economicData['imports']['year'] ?? '-' }}
                            </strong>
                        </div>
                    </article>
                </div>

                <div class="data-source-card">
                    <div>
                        <h3>
                            Sumber Data Ekonomi
                        </h3>

                        <p>
                            Indikator ekonomi diambil dari World Bank
                            dan disimpan pada database lokal.
                        </p>
                    </div>

                    <span class="data-source-badge">
                        <i class="bi bi-database"></i>
                        World Bank
                    </span>
                </div>
            @else
                <div class="economic-empty-state">
                    <i class="bi bi-exclamation-triangle"></i>

                    <div>
                        <h3>
                            Data ekonomi belum tersedia
                        </h3>

                        <p>
                            Data World Bank untuk
                            {{ $selectedCountry->name }}
                            belum tersimpan di database.
                        </p>
                    </div>
                </div>
            @endif
        </section>
    </div>
@endsection
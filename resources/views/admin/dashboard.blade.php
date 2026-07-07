@extends('layouts.app')

@section('title', 'Dasbor Admin')

@section('content')
    <div class="admin-page">

        {{-- =====================================================
             HEADER
             ===================================================== --}}
        <section class="admin-header">
            <div>
                <div class="page-eyebrow">
                    Pusat Kendali Sistem
                </div>

                <h1 class="page-title">
                    Dasbor Admin
                </h1>

                <p class="page-description">
                    Pantau pengguna, cakupan data, aktivitas sinkronisasi,
                    dan kondisi sumber data sistem.
                </p>
            </div>

            <div class="admin-header-badge">
                <i class="bi bi-shield-check"></i>

                <div>
                    <span>Akses Sistem</span>
                    <strong>Administrator</strong>
                </div>
            </div>
        </section>

        {{-- =====================================================
             PRIMARY STATISTICS
             ===================================================== --}}
        <section class="admin-stat-grid">

            <article class="admin-stat-card">
                <div class="admin-stat-top">
                    <div>
                        <span class="admin-stat-label">
                            Total Pengguna
                        </span>

                        <strong class="admin-stat-value">
                            {{ number_format($stats['users_total'], 0, ',', '.') }}
                        </strong>
                    </div>

                    <div class="admin-stat-icon">
                        <i class="bi bi-people"></i>
                    </div>
                </div>

                <div class="admin-stat-footer">
                    <span>
                        {{ number_format($stats['active_users'], 0, ',', '.') }}
                        pengguna aktif
                    </span>
                </div>
            </article>

            <article class="admin-stat-card">
                <div class="admin-stat-top">
                    <div>
                        <span class="admin-stat-label">
                            Administrator
                        </span>

                        <strong class="admin-stat-value">
                            {{ number_format($stats['admins_total'], 0, ',', '.') }}
                        </strong>
                    </div>

                    <div class="admin-stat-icon">
                        <i class="bi bi-shield-lock"></i>
                    </div>
                </div>

                <div class="admin-stat-footer">
                    <span>
                        Akun dengan akses sistem
                    </span>
                </div>
            </article>

            <article class="admin-stat-card">
                <div class="admin-stat-top">
                    <div>
                        <span class="admin-stat-label">
                            Negara Terpantau
                        </span>

                        <strong class="admin-stat-value">
                            {{ number_format($stats['countries_total'], 0, ',', '.') }}
                        </strong>
                    </div>

                    <div class="admin-stat-icon">
                        <i class="bi bi-globe2"></i>
                    </div>
                </div>

                <div class="admin-stat-footer">
                    <span>
                        Profil negara tersimpan
                    </span>
                </div>
            </article>

            <article class="admin-stat-card">
                <div class="admin-stat-top">
                    <div>
                        <span class="admin-stat-label">
                            Data Ekonomi
                        </span>

                        <strong class="admin-stat-value">
                            {{ number_format(
                                $stats['economic_indicators_total'],
                                0,
                                ',',
                                '.'
                            ) }}
                        </strong>
                    </div>

                    <div class="admin-stat-icon">
                        <i class="bi bi-bar-chart-line"></i>
                    </div>
                </div>

                <div class="admin-stat-footer">
                    <span>
                        Indikator World Bank
                    </span>
                </div>
            </article>

        </section>

        {{-- =====================================================
             DATA COVERAGE
             ===================================================== --}}
        <section class="admin-section">

            <div class="admin-section-heading">
                <div>
                    <div class="page-eyebrow">
                        Cakupan Data
                    </div>

                    <h2>
                        Dataset Sistem
                    </h2>

                    <p>
                        Jumlah data yang tersedia pada setiap komponen
                        intelijen risiko.
                    </p>
                </div>
            </div>

            <div class="admin-data-grid">

                <article class="admin-data-card">
                    <div class="admin-data-icon">
                        <i class="bi bi-cloud-lightning-rain"></i>
                    </div>

                    <div>
                        <span>Data Cuaca</span>

                        <strong>
                            {{ number_format(
                                $stats['weather_records_total'],
                                0,
                                ',',
                                '.'
                            ) }}
                        </strong>

                        <small>
                            Rekaman kondisi cuaca
                        </small>
                    </div>
                </article>

                <article class="admin-data-card">
                    <div class="admin-data-icon">
                        <i class="bi bi-currency-exchange"></i>
                    </div>

                    <div>
                        <span>Nilai Tukar</span>

                        <strong>
                            {{ number_format(
                                $stats['exchange_rates_total'],
                                0,
                                ',',
                                '.'
                            ) }}
                        </strong>

                        <small>
                            Rekaman perubahan kurs
                        </small>
                    </div>
                </article>

                <article class="admin-data-card">
                    <div class="admin-data-icon">
                        <i class="bi bi-newspaper"></i>
                    </div>

                    <div>
                        <span>Berita</span>

                        <strong>
                            {{ number_format(
                                $stats['news_cache_total'],
                                0,
                                ',',
                                '.'
                            ) }}
                        </strong>

                        <small>
                            Artikel tersimpan
                        </small>
                    </div>
                </article>

                <article class="admin-data-card">
                    <div class="admin-data-icon">
                        <i class="bi bi-exclamation-triangle"></i>
                    </div>

                    <div>
                        <span>Skor Risiko</span>

                        <strong>
                            {{ number_format(
                                $stats['risk_scores_total'],
                                0,
                                ',',
                                '.'
                            ) }}
                        </strong>

                        <small>
                            Hasil analisis risiko
                        </small>
                    </div>
                </article>

                <article class="admin-data-card">
                    <div class="admin-data-icon">
                        <i class="bi bi-bookmark-star"></i>
                    </div>

                    <div>
                        <span>Watchlist</span>

                        <strong>
                            {{ number_format(
                                $stats['watchlists_total'],
                                0,
                                ',',
                                '.'
                            ) }}
                        </strong>

                        <small>
                            Negara dalam pemantauan
                        </small>
                    </div>
                </article>

                <article class="admin-data-card">
                    <div class="admin-data-icon">
                        <i class="bi bi-activity"></i>
                    </div>

                    <div>
                        <span>Log API</span>

                        <strong>
                            {{ number_format(
                                $stats['api_logs_total'],
                                0,
                                ',',
                                '.'
                            ) }}
                        </strong>

                        <small>
                            Aktivitas integrasi data
                        </small>
                    </div>
                </article>

            </div>
        </section>

        {{-- =====================================================
             SYNCHRONIZATION STATUS
             ===================================================== --}}
        <section class="admin-section">

            <div class="admin-section-heading">
                <div>
                    <div class="page-eyebrow">
                        Integrasi Data
                    </div>

                    <h2>
                        Status Sinkronisasi
                    </h2>

                    <p>
                        Waktu pembaruan terakhir dari setiap sumber data.
                    </p>
                </div>
            </div>

            <div class="admin-sync-card">

                <div class="admin-sync-row">
                    <div class="admin-sync-source">
                        <div class="admin-sync-icon">
                            <i class="bi bi-bank"></i>
                        </div>

                        <div>
                            <strong>
                                Data Ekonomi
                            </strong>

                            <span>
                                World Bank
                            </span>
                        </div>
                    </div>

                    <div class="admin-sync-time">
                        @if ($stats['last_economic_sync'])
                            <strong>
                                {{ $stats['last_economic_sync']->format('d M Y') }}
                            </strong>

                            <span>
                                {{ $stats['last_economic_sync']->format('H:i') }}
                            </span>
                        @else
                            <strong>Belum tersedia</strong>
                        @endif
                    </div>
                </div>

                <div class="admin-sync-row">
                    <div class="admin-sync-source">
                        <div class="admin-sync-icon">
                            <i class="bi bi-cloud-sun"></i>
                        </div>

                        <div>
                            <strong>
                                Data Cuaca
                            </strong>

                            <span>
                                Sumber cuaca global
                            </span>
                        </div>
                    </div>

                    <div class="admin-sync-time">
                        @if ($stats['last_weather_sync'])
                            <strong>
                                {{ $stats['last_weather_sync']->format('d M Y') }}
                            </strong>

                            <span>
                                {{ $stats['last_weather_sync']->format('H:i') }}
                            </span>
                        @else
                            <strong>Belum tersedia</strong>
                        @endif
                    </div>
                </div>

                <div class="admin-sync-row">
                    <div class="admin-sync-source">
                        <div class="admin-sync-icon">
                            <i class="bi bi-currency-exchange"></i>
                        </div>

                        <div>
                            <strong>
                                Nilai Tukar
                            </strong>

                            <span>
                                Data pergerakan mata uang
                            </span>
                        </div>
                    </div>

                    <div class="admin-sync-time">
                        @if ($stats['last_exchange_sync'])
                            <strong>
                                {{ $stats['last_exchange_sync']->format('d M Y') }}
                            </strong>

                            <span>
                                {{ $stats['last_exchange_sync']->format('H:i') }}
                            </span>
                        @else
                            <strong>Belum tersedia</strong>
                        @endif
                    </div>
                </div>

                <div class="admin-sync-row">
                    <div class="admin-sync-source">
                        <div class="admin-sync-icon">
                            <i class="bi bi-newspaper"></i>
                        </div>

                        <div>
                            <strong>
                                Berita Global
                            </strong>

                            <span>
                                Cache intelijen berita
                            </span>
                        </div>
                    </div>

                    <div class="admin-sync-time">
                        @if ($stats['last_news_sync'])
                            <strong>
                                {{ $stats['last_news_sync']->format('d M Y') }}
                            </strong>

                            <span>
                                {{ $stats['last_news_sync']->format('H:i') }}
                            </span>
                        @else
                            <strong>Belum tersedia</strong>
                        @endif
                    </div>
                </div>

            </div>
        </section>

        {{-- =====================================================
             SYSTEM SUMMARY
             ===================================================== --}}
        <section class="admin-system-card">
            <div class="admin-system-status">
                <span class="admin-system-dot"></span>

                <div>
                    <strong>
                        Sistem Beroperasi
                    </strong>

                    <p>
                        Database dan modul autentikasi tersedia.
                        Integrasi data akan bertambah sesuai proses sinkronisasi.
                    </p>
                </div>
            </div>

            <div class="admin-system-meta">
                <span>
                    Log API terakhir
                </span>

                <strong>
                    @if ($stats['last_api_log'])
                        {{ $stats['last_api_log']->format('d M Y, H:i') }}
                    @else
                        Belum tersedia
                    @endif
                </strong>
            </div>
        </section>

    </div>
@endsection
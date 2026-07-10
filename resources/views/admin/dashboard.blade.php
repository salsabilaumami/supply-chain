@extends('layouts.app')

@section('title', 'Dasbor Admin')

@section('content')
    <div class="admin-page">
        <section class="admin-hero">
            <div>
                <div class="page-eyebrow">
                    PUSAT KENDALI SISTEM
                </div>

                <h1>
                    Dasbor Admin
                </h1>

                <p>
                    Pantau pengguna, cakupan data, sinkronisasi, berita, risk score,
                    dan kondisi sumber data sistem secara ringkas.
                </p>
            </div>

            <div class="admin-access-card">
                <div class="admin-access-icon">
                    <i class="bi bi-shield-check"></i>
                </div>

                <div>
                    <span>Akses Sistem</span>

                    <strong>
                        Administrator
                    </strong>

                    <small>
                        Update {{ $lastUpdated ?? '-' }}
                    </small>
                </div>
            </div>
        </section>

        <section class="admin-stat-grid">
            <article class="admin-stat-card">
                <div>
                    <span>Total Pengguna</span>
                    <strong>{{ number_format($stats['total_users'] ?? 0, 0, ',', '.') }}</strong>
                    <small>Akun terdaftar</small>
                </div>

                <i class="bi bi-people"></i>
            </article>

            <article class="admin-stat-card">
                <div>
                    <span>Administrator</span>
                    <strong>{{ number_format($stats['admin_users'] ?? 0, 0, ',', '.') }}</strong>
                    <small>Akun admin sistem</small>
                </div>

                <i class="bi bi-shield-lock"></i>
            </article>

            <article class="admin-stat-card">
                <div>
                    <span>Negara Terpantau</span>
                    <strong>{{ number_format($stats['countries'] ?? 0, 0, ',', '.') }}</strong>
                    <small>Profil negara</small>
                </div>

                <i class="bi bi-globe2"></i>
            </article>

            <article class="admin-stat-card">
                <div>
                    <span>Total Dataset</span>
                    <strong>
                        {{ number_format(
                            ($stats['economic_data'] ?? 0)
                            + ($stats['weather_data'] ?? 0)
                            + ($stats['currency_data'] ?? 0)
                            + ($stats['news_data'] ?? 0)
                            + ($stats['risk_scores'] ?? 0),
                            0,
                            ',',
                            '.'
                        ) }}
                    </strong>
                    <small>Data aktif sistem</small>
                </div>

                <i class="bi bi-database-check"></i>
            </article>
        </section>

        <section class="admin-section">
            <div class="admin-section-heading">
                <div>
                    <div class="page-eyebrow">
                        PENGGUNA SISTEM
                    </div>

                    <h2>
                        Daftar Pengguna
                    </h2>

                    <p>
                        Menampilkan pengguna terbaru yang terdaftar pada sistem.
                    </p>
                </div>
            </div>

            <div class="admin-card admin-table-card">
                <div class="table-responsive">
                    <table class="table align-middle mb-0 admin-table">
                        <thead>
                            <tr>
                                <th>Pengguna</th>
                                <th>Email</th>
                                <th>Akses</th>
                                <th>Terdaftar</th>
                                <th>Terakhir Update</th>
                            </tr>
                        </thead>

                        <tbody>
                            @forelse ($users as $user)
                                <tr>
                                    <td>
                                        <div class="admin-user-cell">
                                            <div class="admin-user-avatar">
                                                {{ $user['initial'] }}
                                            </div>

                                            <div>
                                                <strong>
                                                    {{ $user['name'] }}
                                                </strong>

                                                <small>
                                                    ID #{{ $user['id'] }}
                                                </small>
                                            </div>
                                        </div>
                                    </td>

                                    <td>
                                        {{ $user['email'] }}
                                    </td>

                                    <td>
                                        <span class="admin-role {{ $user['role_class'] }}">
                                            {{ $user['role'] }}
                                        </span>
                                    </td>

                                    <td>
                                        {{ $user['created_at'] }}
                                    </td>

                                    <td>
                                        {{ $user['updated_at'] }}
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td
                                        colspan="5"
                                        class="text-center text-muted py-4"
                                    >
                                        Belum ada pengguna terdaftar.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </section>

        <section class="admin-section">
            <div class="admin-section-heading">
                <div>
                    <div class="page-eyebrow">
                        CAKUPAN DATA
                    </div>

                    <h2>
                        Dataset Sistem
                    </h2>

                    <p>
                        Jumlah data yang tersedia pada setiap komponen intelijen risiko.
                    </p>
                </div>
            </div>

            <div class="admin-dataset-grid">
                @foreach ($datasetCards as $card)
                    <article class="admin-dataset-card">
                        <div class="admin-dataset-icon">
                            <i class="bi {{ $card['icon'] }}"></i>
                        </div>

                        <div>
                            <span>
                                {{ $card['label'] }}
                            </span>

                            <strong>
                                {{ number_format($card['value'], 0, ',', '.') }}
                            </strong>

                            <small>
                                {{ $card['description'] }}
                            </small>
                        </div>
                    </article>
                @endforeach
            </div>
        </section>

        <section class="admin-grid-two">
            <article class="admin-card">
                <div class="admin-section-heading compact">
                    <div>
                        <div class="page-eyebrow">
                            RISK SCORE
                        </div>

                        <h2>
                            Risiko Terbaru
                        </h2>

                        <p>
                            Hasil perhitungan risiko terakhir.
                        </p>
                    </div>
                </div>

                <div class="admin-list">
                    @forelse ($latestRisks as $risk)
                        @php
                            $riskClass = match ($risk['risk_level']) {
                                'critical' => 'admin-risk-critical',
                                'high' => 'admin-risk-high',
                                'moderate', 'medium' => 'admin-risk-medium',
                                default => 'admin-risk-low',
                            };
                        @endphp

                        <div class="admin-list-item">
                            <div>
                                <strong>
                                    {{ $risk['country_name'] }}
                                </strong>

                                <small>
                                    {{ $risk['country_iso3'] }} • {{ $risk['calculated_at'] }}
                                </small>
                            </div>

                            <div class="admin-list-score">
                                <span class="{{ $riskClass }}">
                                    {{ $risk['risk_label'] }}
                                </span>

                                <b>
                                    {{ number_format($risk['total_score'], 2, ',', '.') }}
                                </b>
                            </div>
                        </div>
                    @empty
                        <div class="admin-empty">
                            Belum ada risk score.
                        </div>
                    @endforelse
                </div>
            </article>

            <article class="admin-card">
                <div class="admin-section-heading compact">
                    <div>
                        <div class="page-eyebrow">
                            BERITA
                        </div>

                        <h2>
                            Berita Terbaru
                        </h2>

                        <p>
                            Artikel terbaru yang tersimpan di sistem.
                        </p>
                    </div>
                </div>

                <div class="admin-news-list">
                    @forelse ($latestNews as $news)
                        <div class="admin-news-item">
                            <div class="admin-news-thumb">
                                @if ($news['image_url'])
                                    <img
                                        src="{{ $news['image_url'] }}"
                                        alt="{{ $news['title'] }}"
                                        loading="lazy"
                                        onerror="this.style.display='none'; this.nextElementSibling.style.display='grid';"
                                    >

                                    <div
                                        class="admin-news-fallback"
                                        style="display: none;"
                                    >
                                        <i class="bi bi-newspaper"></i>
                                    </div>
                                @else
                                    <div class="admin-news-fallback">
                                        <i class="bi bi-newspaper"></i>
                                    </div>
                                @endif
                            </div>

                            <div>
                                <strong>
                                    {{ \Illuminate\Support\Str::limit($news['title'], 70) }}
                                </strong>

                                <small>
                                    {{ $news['source_name'] ?? '-' }}
                                    •
                                    {{ $news['country_iso3'] }}
                                    •
                                    {{ $news['published_at'] }}
                                </small>
                            </div>
                        </div>
                    @empty
                        <div class="admin-empty">
                            Belum ada berita.
                        </div>
                    @endforelse
                </div>
            </article>
        </section>
    </div>
@endsection

@push('styles')
    <style>
        .admin-page {
            width: 100%;
            max-width: 1180px;
            margin: 0 auto;
            display: flex;
            flex-direction: column;
            gap: 22px;
        }

        .admin-hero {
            display: flex;
            align-items: flex-end;
            justify-content: space-between;
            gap: 24px;
        }

        .admin-hero h1 {
            margin: 0 0 8px;
            color: #111827;
            font-size: clamp(2rem, 3.4vw, 2.8rem);
            font-weight: 900;
            line-height: 1.1;
        }

        .admin-hero p,
        .admin-section-heading p {
            margin: 0;
            color: #7c8aa5;
            font-size: 0.95rem;
            line-height: 1.55;
        }

        .admin-access-card,
        .admin-stat-card,
        .admin-card,
        .admin-dataset-card {
            background: #ffffff;
            border: 1px solid rgba(148, 163, 184, 0.22);
            border-radius: 18px;
            box-shadow: 0 14px 32px rgba(15, 23, 42, 0.045);
        }

        .admin-access-card {
            min-width: 240px;
            display: flex;
            align-items: center;
            gap: 14px;
            padding: 16px 18px;
            background: #eef6ff;
            border-color: rgba(37, 99, 235, 0.12);
        }

        .admin-access-icon,
        .admin-stat-card > i,
        .admin-dataset-icon {
            display: grid;
            place-items: center;
            width: 44px;
            height: 44px;
            border-radius: 14px;
            background: #e0edff;
            color: #2563eb;
            font-size: 1.25rem;
        }

        .admin-access-card span,
        .admin-stat-card span,
        .admin-dataset-card span {
            display: block;
            color: #7c8aa5;
            font-size: 0.82rem;
            font-weight: 700;
            margin-bottom: 4px;
        }

        .admin-access-card strong {
            display: block;
            color: #1e3a8a;
            font-size: 0.98rem;
            font-weight: 900;
        }

        .admin-access-card small {
            display: block;
            margin-top: 3px;
            color: #64748b;
            font-size: 0.75rem;
        }

        .admin-stat-grid {
            display: grid;
            grid-template-columns: repeat(4, minmax(0, 1fr));
            gap: 16px;
        }

        .admin-stat-card {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            gap: 14px;
            padding: 20px;
        }

        .admin-stat-card strong {
            display: block;
            color: #111827;
            font-size: 2rem;
            font-weight: 900;
            line-height: 1.1;
        }

        .admin-stat-card small,
        .admin-dataset-card small {
            display: block;
            margin-top: 6px;
            color: #7c8aa5;
            font-size: 0.8rem;
        }

        .admin-section {
            display: flex;
            flex-direction: column;
            gap: 14px;
        }

        .admin-section-heading {
            display: flex;
            align-items: flex-end;
            justify-content: space-between;
            gap: 18px;
        }

        .admin-section-heading h2 {
            margin: 0 0 6px;
            color: #111827;
            font-size: 1.45rem;
            font-weight: 900;
        }

        .admin-section-heading.compact {
            margin-bottom: 14px;
        }

        .admin-table-card,
        .admin-card {
            padding: 20px;
        }

        .admin-table {
            min-width: 850px;
        }

        .admin-table thead th {
            background: #f8fafc;
            color: #64748b;
            font-size: 0.78rem;
            font-weight: 900;
            text-transform: uppercase;
            letter-spacing: 0.035em;
            border-bottom: 1px solid rgba(148, 163, 184, 0.22);
            white-space: nowrap;
        }

        .admin-table tbody td {
            color: #334155;
            font-size: 0.9rem;
            vertical-align: middle;
            border-bottom: 1px solid rgba(148, 163, 184, 0.14);
        }

        .admin-user-cell {
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .admin-user-avatar {
            width: 38px;
            height: 38px;
            display: grid;
            place-items: center;
            border-radius: 12px;
            background: #0f2d5c;
            color: #ffffff;
            font-weight: 900;
            flex: 0 0 auto;
        }

        .admin-user-cell strong {
            display: block;
            color: #111827;
            font-weight: 900;
            line-height: 1.3;
        }

        .admin-user-cell small {
            display: block;
            color: #7c8aa5;
            font-size: 0.78rem;
        }

        .admin-role {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            padding: 7px 10px;
            border-radius: 999px;
            font-size: 0.75rem;
            font-weight: 850;
            white-space: nowrap;
        }

        .admin-role-badge {
            background: #eef6ff;
            color: #1d4ed8;
            border: 1px solid rgba(37, 99, 235, 0.18);
        }

        .user-role-badge {
            background: #f1f5f9;
            color: #475569;
            border: 1px solid rgba(100, 116, 139, 0.18);
        }

        .admin-dataset-grid {
            display: grid;
            grid-template-columns: repeat(3, minmax(0, 1fr));
            gap: 16px;
        }

        .admin-dataset-card {
            display: flex;
            align-items: center;
            gap: 14px;
            padding: 18px;
        }

        .admin-dataset-card strong {
            display: block;
            color: #111827;
            font-size: 1.45rem;
            font-weight: 900;
            line-height: 1.2;
        }

        .admin-grid-two {
            display: grid;
            grid-template-columns: minmax(0, 1fr) minmax(0, 1fr);
            gap: 18px;
        }

        .admin-list,
        .admin-news-list {
            display: flex;
            flex-direction: column;
            gap: 10px;
        }

        .admin-list-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 14px;
            padding: 12px;
            border-radius: 14px;
            background: #f8fafc;
            border: 1px solid rgba(148, 163, 184, 0.14);
        }

        .admin-list-item strong,
        .admin-news-item strong {
            display: block;
            color: #111827;
            font-size: 0.9rem;
            font-weight: 900;
            line-height: 1.35;
        }

        .admin-list-item small,
        .admin-news-item small {
            display: block;
            margin-top: 3px;
            color: #7c8aa5;
            font-size: 0.76rem;
            line-height: 1.35;
        }

        .admin-list-score {
            text-align: right;
            flex: 0 0 auto;
        }

        .admin-list-score span {
            display: block;
            margin-bottom: 4px;
            font-size: 0.72rem;
            font-weight: 850;
        }

        .admin-list-score b {
            color: #111827;
            font-size: 1rem;
        }

        .admin-risk-low {
            color: #1d4ed8;
        }

        .admin-risk-medium {
            color: #0891b2;
        }

        .admin-risk-high {
            color: #b45309;
        }

        .admin-risk-critical {
            color: #b91c1c;
        }

        .admin-news-item {
            display: grid;
            grid-template-columns: 64px minmax(0, 1fr);
            gap: 12px;
            padding: 10px;
            border-radius: 14px;
            background: #f8fafc;
            border: 1px solid rgba(148, 163, 184, 0.14);
        }

        .admin-news-thumb {
            width: 64px;
            height: 48px;
            overflow: hidden;
            border-radius: 12px;
            background: #e2e8f0;
        }

        .admin-news-thumb img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }

        .admin-news-fallback {
            width: 100%;
            height: 100%;
            display: grid;
            place-items: center;
            color: #64748b;
            background: linear-gradient(135deg, #f8fafc, #e2e8f0);
        }

        .admin-empty {
            padding: 14px;
            border-radius: 14px;
            color: #7c8aa5;
            background: #f8fafc;
            border: 1px solid rgba(148, 163, 184, 0.14);
            font-size: 0.9rem;
        }

        @media (max-width: 1280px) {
            .admin-page {
                max-width: 100%;
            }

            .admin-stat-grid,
            .admin-dataset-grid {
                grid-template-columns: repeat(2, minmax(0, 1fr));
            }

            .admin-grid-two {
                grid-template-columns: 1fr;
            }
        }

        @media (max-width: 860px) {
            .admin-hero {
                align-items: flex-start;
                flex-direction: column;
            }

            .admin-access-card {
                width: 100%;
            }

            .admin-stat-grid,
            .admin-dataset-grid {
                grid-template-columns: 1fr;
            }
        }
    </style>
@endpush
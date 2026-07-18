@extends('layouts.app')

@section('title', 'Dasbor Admin')

@section('content')
    @php
        $algorithmWeights = [
            [
                'label' => 'Cuaca',
                'weight' => 30,
                'description' => 'Menilai potensi gangguan dari hujan, angin, suhu, dan kondisi cuaca ekstrem.',
                'icon' => 'bi-cloud-lightning-rain',
            ],
            [
                'label' => 'Inflasi',
                'weight' => 20,
                'description' => 'Menilai tekanan ekonomi dari data inflasi negara.',
                'icon' => 'bi-graph-up-arrow',
            ],
            [
                'label' => 'Kurs',
                'weight' => 10,
                'description' => 'Menilai risiko perubahan nilai tukar mata uang terhadap USD.',
                'icon' => 'bi-currency-exchange',
            ],
            [
                'label' => 'Berita',
                'weight' => 40,
                'description' => 'Menilai risiko dari sentimen berita ekonomi, logistik, perdagangan, dan rantai pasok.',
                'icon' => 'bi-newspaper',
            ],
        ];

        $totalDataset = ($stats['economic_data'] ?? 0)
            + ($stats['weather_data'] ?? 0)
            + ($stats['currency_data'] ?? 0)
            + ($stats['news_data'] ?? 0)
            + ($stats['risk_scores'] ?? 0);

        $currentUserId = auth()->id();
    @endphp

    <div class="admin-page">
        @if (session('success'))
            <div class="admin-alert admin-alert-success">
                <i class="bi bi-check-circle"></i>
                <span>{{ session('success') }}</span>
            </div>
        @endif

        @if (session('error'))
            <div class="admin-alert admin-alert-error">
                <i class="bi bi-exclamation-triangle"></i>
                <span>{{ session('error') }}</span>
            </div>
        @endif

        @if ($errors->any())
            <div class="admin-alert admin-alert-error">
                <i class="bi bi-exclamation-triangle"></i>

                <div>
                    <strong>Terjadi kesalahan input.</strong>

                    <ul class="mb-0 mt-1">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            </div>
        @endif

        <section class="admin-hero">
            <div>
                <div class="page-eyebrow">
                    SYSTEM CONTROL CENTER
                </div>

                <h1>
                    Dasbor Admin
                </h1>

                <p>
                    Pusat kendali untuk memantau pengguna, cakupan dataset,
                    artikel analisis, pelabuhan global, dan algoritma risk scoring
                    pada sistem intelijen risiko rantai pasok.
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

                    <strong>
                        {{ number_format($stats['total_users'] ?? 0, 0, ',', '.') }}
                    </strong>

                    <small>
                        Akun terdaftar
                    </small>
                </div>

                <i class="bi bi-people"></i>
            </article>

            <article class="admin-stat-card">
                <div>
                    <span>Administrator</span>

                    <strong>
                        {{ number_format($stats['admin_users'] ?? 0, 0, ',', '.') }}
                    </strong>

                    <small>
                        Akun admin sistem
                    </small>
                </div>

                <i class="bi bi-shield-lock"></i>
            </article>

            <article class="admin-stat-card">
                <div>
                    <span>Negara Terpantau</span>

                    <strong>
                        {{ number_format($stats['countries'] ?? 0, 0, ',', '.') }}
                    </strong>

                    <small>
                        Profil negara
                    </small>
                </div>

                <i class="bi bi-globe2"></i>
            </article>

            <article class="admin-stat-card">
                <div>
                    <span>Total Dataset</span>

                    <strong>
                        {{ number_format($totalDataset, 0, ',', '.') }}
                    </strong>

                    <small>
                        Data aktif sistem
                    </small>
                </div>

                <i class="bi bi-database-check"></i>
            </article>
        </section>

        <section class="admin-section">
            <div class="admin-section-heading">
                <div>
                    <div class="page-eyebrow">
                        ADMIN CONTROL
                    </div>

                    <h2>
                        Kontrol Pengelolaan Sistem
                    </h2>

                    <p>
                        Ringkasan area yang dapat diawasi dan dikelola admin untuk memastikan data sistem tetap lengkap dan relevan.
                    </p>
                </div>
            </div>

            <div class="admin-control-grid">
                <article class="admin-control-card">
                    <div class="admin-control-icon">
                        <i class="bi bi-person-gear"></i>
                    </div>

                    <div>
                        <span>Kelola Pengguna</span>

                        <strong>
                            {{ number_format($stats['total_users'] ?? 0, 0, ',', '.') }} Akun
                        </strong>

                        <p>
                            Admin dapat memantau role, mengubah status aktif/tidak aktif, dan menghapus pengguna yang tidak diperlukan.
                        </p>
                    </div>
                </article>

                <article class="admin-control-card">
                    <div class="admin-control-icon">
                        <i class="bi bi-geo-alt"></i>
                    </div>

                    <div>
                        <span>Dataset Pelabuhan</span>

                        <strong>
                            {{ number_format($stats['ports'] ?? 0, 0, ',', '.') }} Data
                        </strong>

                        <p>
                            Dataset pelabuhan global digunakan untuk mendukung analisis lokasi logistik.
                        </p>

                        @if (\Illuminate\Support\Facades\Route::has('ports.index'))
                            <a
                                href="{{ route('ports.index') }}"
                                class="admin-control-link"
                            >
                                Buka Data Pelabuhan
                                <i class="bi bi-arrow-right"></i>
                            </a>
                        @endif
                    </div>
                </article>

                <article class="admin-control-card">
                    <div class="admin-control-icon">
                        <i class="bi bi-journal-text"></i>
                    </div>

                    <div>
                        <span>Artikel Analisis</span>

                        <strong>
                            {{ number_format($stats['news_data'] ?? 0, 0, ',', '.') }} Artikel
                        </strong>

                        <p>
                            Artikel berita dianalisis untuk menghasilkan skor risiko berbasis sentimen.
                        </p>

                        @if (\Illuminate\Support\Facades\Route::has('news.index'))
                            <a
                                href="{{ route('news.index') }}"
                                class="admin-control-link"
                            >
                                Buka Intelijen Berita
                                <i class="bi bi-arrow-right"></i>
                            </a>
                        @endif
                    </div>
                </article>

                <article class="admin-control-card">
                    <div class="admin-control-icon">
                        <i class="bi bi-diagram-3"></i>
                    </div>

                    <div>
                        <span>Risk Scoring Engine</span>

                        <strong>
                            {{ number_format($stats['risk_scores'] ?? 0, 0, ',', '.') }} Perhitungan
                        </strong>

                        <p>
                            Mesin risiko menghitung skor dari cuaca, inflasi, kurs, dan berita.
                        </p>

                        @if (\Illuminate\Support\Facades\Route::has('dashboard'))
                            <a
                                href="{{ route('dashboard') }}"
                                class="admin-control-link"
                            >
                                Buka Global Overview
                                <i class="bi bi-arrow-right"></i>
                            </a>
                        @endif
                    </div>
                </article>
            </div>
        </section>

        <section class="admin-section">
            <div class="admin-section-heading">
                <div>
                    <div class="page-eyebrow">
                        RISK SCORING ALGORITHM
                    </div>

                    <h2>
                        Algoritma Risk Scoring
                    </h2>

                    <p>
                        Bobot komponen yang digunakan sistem untuk menghitung risiko rantai pasok global.
                    </p>
                </div>
            </div>

            <div class="admin-algorithm-card">
                <div class="admin-formula-box">
                    <span>Formula</span>

                    <strong>
                        Total Risk Score = Cuaca 30% + Inflasi 20% + Kurs 10% + Berita 40%
                    </strong>

                    <p>
                        Setiap komponen dinormalisasi menjadi skor risiko, kemudian dikalikan dengan bobotnya
                        untuk menghasilkan total Risk Score.
                    </p>
                </div>

                <div class="admin-algorithm-grid">
                    @foreach ($algorithmWeights as $algorithm)
                        <article class="admin-algorithm-item">
                            <div class="admin-algorithm-icon">
                                <i class="bi {{ $algorithm['icon'] }}"></i>
                            </div>

                            <div>
                                <span>
                                    {{ $algorithm['label'] }}
                                </span>

                                <strong>
                                    {{ $algorithm['weight'] }}%
                                </strong>

                                <small>
                                    {{ $algorithm['description'] }}
                                </small>
                            </div>
                        </article>
                    @endforeach
                </div>
            </div>
        </section>

        <section class="admin-section">
            <div class="admin-section-heading">
                <div>
                    <div class="page-eyebrow">
                        USER MANAGEMENT
                    </div>

                    <h2>
                        Kelola Pengguna
                    </h2>

                    <p>
                        Admin dapat memantau role pengguna, mengubah status akun, dan menghapus pengguna dari sistem.
                    </p>
                </div>
            </div>

            <div class="admin-card admin-table-card">
                <div class="table-responsive">
                    <table class="table align-middle mb-0 admin-table admin-user-table">
                        <thead>
                            <tr>
                                <th>Pengguna</th>
                                <th>Email</th>
                                <th>Role</th>
                                <th>Status</th>
                                <th>Terdaftar</th>
                                <th>Aksi</th>
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
                                        <div class="admin-inline-action">
                                            <span class="admin-status {{ $user['status_class'] ?? 'admin-status-active' }}">
                                                {{ $user['status_label'] ?? 'Aktif' }}
                                            </span>

                                            <form
                                                method="POST"
                                                action="{{ route('admin.users.update-status', $user['id']) }}"
                                                class="admin-mini-form"
                                            >
                                                @csrf
                                                @method('PATCH')

                                                <select
                                                    name="status"
                                                    class="admin-mini-select"
                                                    {{ (int) $user['id'] === (int) $currentUserId ? 'disabled' : '' }}
                                                >
                                                    <option
                                                        value="active"
                                                        {{ ($user['status'] ?? 'active') === 'active' ? 'selected' : '' }}
                                                    >
                                                        Aktif
                                                    </option>

                                                    <option
                                                        value="inactive"
                                                        {{ ($user['status'] ?? 'active') === 'inactive' ? 'selected' : '' }}
                                                    >
                                                        Tidak Aktif
                                                    </option>
                                                </select>

                                                <button
                                                    type="submit"
                                                    class="admin-action-btn admin-action-primary"
                                                    {{ (int) $user['id'] === (int) $currentUserId ? 'disabled' : '' }}
                                                >
                                                    Ubah
                                                </button>
                                            </form>
                                        </div>
                                    </td>

                                    <td>
                                        {{ $user['created_at'] }}
                                    </td>

                                    <td>
                                        <div class="admin-action-group">
                                            @if ((int) $user['id'] === (int) $currentUserId)
                                                <span class="admin-self-note">
                                                    Akun aktif
                                                </span>
                                            @else
                                                <form
                                                    method="POST"
                                                    action="{{ route('admin.users.destroy', $user['id']) }}"
                                                    onsubmit="return confirm('Yakin ingin menghapus pengguna ini?')"
                                                >
                                                    @csrf
                                                    @method('DELETE')

                                                    <button
                                                        type="submit"
                                                        class="admin-action-btn admin-action-danger"
                                                    >
                                                        <i class="bi bi-trash"></i>
                                                        Hapus
                                                    </button>
                                                </form>
                                            @endif
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td
                                        colspan="6"
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
                        DATASET COVERAGE
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
                            PORT DATASET
                        </div>

                        <h2>
                            Pelabuhan Terbaru
                        </h2>

                        <p>
                            Data pelabuhan global terbaru yang tersimpan di sistem.
                        </p>
                    </div>
                </div>

                <div class="admin-port-list">
                    @forelse ($latestPorts ?? collect() as $port)
                        @php
                            $portRiskClass = match ($port['risk_level']) {
                                'critical' => 'admin-risk-critical',
                                'high' => 'admin-risk-high',
                                'moderate', 'medium' => 'admin-risk-medium',
                                default => 'admin-risk-low',
                            };
                        @endphp

                        <div class="admin-port-item">
                            <div>
                                <strong>
                                    {{ $port['name'] }}
                                </strong>

                                <small>
                                    {{ $port['code'] }}
                                    •
                                    {{ $port['city'] }}
                                    •
                                    {{ $port['country_iso3'] }}
                                    •
                                    {{ $port['updated_at'] }}
                                </small>

                                <span class="{{ $portRiskClass }}">
                                    {{ $port['risk_label'] }}
                                    —
                                    {{ number_format($port['risk_score'], 2, ',', '.') }}
                                </span>
                            </div>

                            <form
                                method="POST"
                                action="{{ route('admin.ports.destroy', $port['id']) }}"
                                onsubmit="return confirm('Yakin ingin menghapus data pelabuhan ini?')"
                            >
                                @csrf
                                @method('DELETE')

                                <button
                                    type="submit"
                                    class="admin-action-btn admin-action-danger"
                                >
                                    <i class="bi bi-trash"></i>
                                    Hapus
                                </button>
                            </form>
                        </div>
                    @empty
                        <div class="admin-empty">
                            Belum ada data pelabuhan.
                        </div>
                    @endforelse
                </div>
            </article>
        </section>

        <section class="admin-section">
            <div class="admin-section-heading">
                <div>
                    <div class="page-eyebrow">
                        ARTICLE MANAGEMENT
                    </div>

                    <h2>
                        Artikel Berita Terbaru
                    </h2>

                    <p>
                        Admin dapat membuka artikel sumber atau menghapus artikel yang tidak relevan dari cache berita.
                    </p>
                </div>
            </div>

            <div class="admin-card">
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
                                    {{ \Illuminate\Support\Str::limit($news['title'], 95) }}
                                </strong>

                                <small>
                                    {{ $news['source_name'] ?? '-' }}
                                    •
                                    {{ $news['country_iso3'] }}
                                    •
                                    {{ $news['published_at'] }}
                                </small>

                                <div class="admin-news-actions">
                                    @if (!empty($news['url']))
                                        <a
                                            href="{{ $news['url'] }}"
                                            target="_blank"
                                            rel="noopener"
                                            class="admin-news-link"
                                        >
                                            Buka artikel
                                        </a>
                                    @endif

                                    <form
                                        method="POST"
                                        action="{{ route('admin.news.destroy', $news['id']) }}"
                                        onsubmit="return confirm('Yakin ingin menghapus artikel berita ini?')"
                                    >
                                        @csrf
                                        @method('DELETE')

                                        <button
                                            type="submit"
                                            class="admin-action-btn admin-action-danger"
                                        >
                                            <i class="bi bi-trash"></i>
                                            Hapus
                                        </button>
                                    </form>
                                </div>
                            </div>
                        </div>
                    @empty
                        <div class="admin-empty">
                            Belum ada berita.
                        </div>
                    @endforelse
                </div>
            </div>
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

        .admin-alert {
            display: flex;
            align-items: flex-start;
            gap: 10px;
            padding: 14px 16px;
            border-radius: 16px;
            font-size: 0.9rem;
            font-weight: 800;
            border: 1px solid transparent;
        }

        .admin-alert-success {
            background: #ecfdf5;
            color: #047857;
            border-color: rgba(16, 185, 129, 0.22);
        }

        .admin-alert-error {
            background: #fef2f2;
            color: #b91c1c;
            border-color: rgba(239, 68, 68, 0.22);
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
        .admin-dataset-card,
        .admin-control-card,
        .admin-algorithm-card {
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
        .admin-dataset-icon,
        .admin-control-icon,
        .admin-algorithm-icon {
            display: grid;
            place-items: center;
            width: 44px;
            height: 44px;
            border-radius: 14px;
            background: #e0edff;
            color: #2563eb;
            font-size: 1.25rem;
            flex: 0 0 auto;
        }

        .admin-access-card span,
        .admin-stat-card span,
        .admin-dataset-card span,
        .admin-control-card span,
        .admin-algorithm-item span,
        .admin-formula-box span {
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
        .admin-dataset-card small,
        .admin-algorithm-item small {
            display: block;
            margin-top: 6px;
            color: #7c8aa5;
            font-size: 0.8rem;
            line-height: 1.45;
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

        .admin-control-grid {
            display: grid;
            grid-template-columns: repeat(4, minmax(0, 1fr));
            gap: 16px;
        }

        .admin-control-card {
            display: flex;
            align-items: flex-start;
            gap: 14px;
            padding: 18px;
        }

        .admin-control-card strong {
            display: block;
            color: #111827;
            font-size: 1.05rem;
            font-weight: 900;
            line-height: 1.25;
            margin-bottom: 6px;
        }

        .admin-control-card p {
            margin: 0;
            color: #7c8aa5;
            font-size: 0.84rem;
            line-height: 1.5;
        }

        .admin-control-link,
        .admin-news-link {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            margin-top: 10px;
            color: #2563eb;
            font-size: 0.82rem;
            font-weight: 800;
            text-decoration: none;
        }

        .admin-control-link:hover,
        .admin-news-link:hover {
            color: #1d4ed8;
            text-decoration: underline;
        }

        .admin-algorithm-card {
            padding: 20px;
        }

        .admin-formula-box {
            padding: 18px;
            border-radius: 16px;
            background: #f8fafc;
            border: 1px solid rgba(148, 163, 184, 0.16);
            margin-bottom: 16px;
        }

        .admin-formula-box strong {
            display: block;
            color: #111827;
            font-size: 1.08rem;
            font-weight: 900;
            line-height: 1.45;
        }

        .admin-formula-box p {
            margin: 8px 0 0;
            color: #7c8aa5;
            font-size: 0.88rem;
            line-height: 1.55;
        }

        .admin-algorithm-grid {
            display: grid;
            grid-template-columns: repeat(4, minmax(0, 1fr));
            gap: 14px;
        }

        .admin-algorithm-item {
            display: flex;
            align-items: flex-start;
            gap: 12px;
            padding: 16px;
            border-radius: 16px;
            background: #ffffff;
            border: 1px solid rgba(148, 163, 184, 0.18);
        }

        .admin-algorithm-item strong {
            display: block;
            color: #111827;
            font-size: 1.65rem;
            font-weight: 900;
            line-height: 1;
        }

        .admin-table-card,
        .admin-card {
            padding: 20px;
        }

        .admin-table {
            min-width: 1050px;
        }

        .admin-user-table {
            min-width: 1080px;
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

        .admin-role,
        .admin-status {
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

        .admin-status-active {
            background: #ecfdf5;
            color: #047857;
            border: 1px solid rgba(16, 185, 129, 0.22);
        }

        .admin-status-inactive {
            background: #f8fafc;
            color: #64748b;
            border: 1px solid rgba(100, 116, 139, 0.22);
        }

        .admin-inline-action {
            display: flex;
            flex-direction: column;
            align-items: flex-start;
            gap: 8px;
        }

        .admin-mini-form {
            display: flex;
            align-items: center;
            gap: 6px;
        }

        .admin-mini-select {
            height: 34px;
            min-width: 120px;
            border-radius: 10px;
            border: 1px solid rgba(148, 163, 184, 0.38);
            background: #ffffff;
            color: #334155;
            padding: 0 9px;
            font-size: 0.78rem;
            font-weight: 750;
            outline: none;
        }

        .admin-mini-select:disabled {
            background: #f1f5f9;
            color: #94a3b8;
            cursor: not-allowed;
        }

        .admin-action-group,
        .admin-news-actions {
            display: flex;
            align-items: center;
            gap: 8px;
            flex-wrap: wrap;
        }

        .admin-action-btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 6px;
            min-height: 34px;
            padding: 7px 10px;
            border-radius: 10px;
            border: 1px solid transparent;
            font-size: 0.78rem;
            font-weight: 850;
            line-height: 1;
            cursor: pointer;
            transition: 0.2s ease;
            white-space: nowrap;
        }

        .admin-action-btn:disabled {
            cursor: not-allowed;
            opacity: 0.55;
        }

        .admin-action-primary {
            background: #eef6ff;
            color: #1d4ed8;
            border-color: rgba(37, 99, 235, 0.18);
        }

        .admin-action-primary:hover:not(:disabled) {
            background: #dbeafe;
        }

        .admin-action-danger {
            background: #fef2f2;
            color: #b91c1c;
            border-color: rgba(239, 68, 68, 0.2);
        }

        .admin-action-danger:hover {
            background: #fee2e2;
        }

        .admin-self-note {
            display: inline-flex;
            padding: 7px 10px;
            border-radius: 999px;
            color: #64748b;
            background: #f1f5f9;
            font-size: 0.75rem;
            font-weight: 850;
            white-space: nowrap;
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
        .admin-news-list,
        .admin-port-list {
            display: flex;
            flex-direction: column;
            gap: 10px;
        }

        .admin-list-item,
        .admin-port-item {
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
        .admin-port-item strong,
        .admin-news-item strong {
            display: block;
            color: #111827;
            font-size: 0.9rem;
            font-weight: 900;
            line-height: 1.35;
        }

        .admin-list-item small,
        .admin-port-item small,
        .admin-news-item small {
            display: block;
            margin-top: 3px;
            color: #7c8aa5;
            font-size: 0.76rem;
            line-height: 1.35;
        }

        .admin-port-item span {
            display: inline-flex;
            margin-top: 6px;
            font-size: 0.76rem;
            font-weight: 850;
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
            .admin-dataset-grid,
            .admin-control-grid,
            .admin-algorithm-grid {
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
            .admin-dataset-grid,
            .admin-control-grid,
            .admin-algorithm-grid {
                grid-template-columns: 1fr;
            }

            .admin-list-item,
            .admin-port-item {
                align-items: flex-start;
                flex-direction: column;
            }
        }
    </style>
@endpush
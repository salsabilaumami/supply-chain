<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>@yield('title', 'Akses Sistem') | Supply Chain Risk Intelligence</title>

    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>

<body class="auth-page">
    <main class="auth-shell">
        <section class="auth-intelligence-panel">
            <div class="auth-brand">
                <div class="auth-brand-icon">
                    <i class="bi bi-radar"></i>
                </div>

                <div class="auth-brand-text">
                    <strong>Supply Chain</strong>
                    <span>Risk Intelligence</span>
                </div>
            </div>

            <div class="auth-intelligence-content">
                <span class="auth-kicker">
                    GLOBAL RISK SIGNAL NETWORK
                </span>

                <h1>
                    Satu akses untuk membaca perubahan risiko global.
                </h1>

                <p>
                    Platform menggabungkan sinyal ekonomi, cuaca,
                    mata uang, dan berita menjadi penilaian risiko
                    yang dapat ditelusuri.
                </p>

                <div class="auth-signal-grid">
                    <div class="auth-signal-item">
                        <div class="auth-signal-icon">
                            <i class="bi bi-cloud-lightning-rain"></i>
                        </div>

                        <div>
                            <strong>Cuaca</strong>
                            <span>Sinyal gangguan alam</span>
                        </div>
                    </div>

                    <div class="auth-signal-item">
                        <div class="auth-signal-icon">
                            <i class="bi bi-graph-up-arrow"></i>
                        </div>

                        <div>
                            <strong>Ekonomi</strong>
                            <span>Tekanan inflasi negara</span>
                        </div>
                    </div>

                    <div class="auth-signal-item">
                        <div class="auth-signal-icon">
                            <i class="bi bi-currency-exchange"></i>
                        </div>

                        <div>
                            <strong>Mata Uang</strong>
                            <span>Perubahan nilai tukar</span>
                        </div>
                    </div>

                    <div class="auth-signal-item">
                        <div class="auth-signal-icon">
                            <i class="bi bi-newspaper"></i>
                        </div>

                        <div>
                            <strong>Berita</strong>
                            <span>Sentimen gangguan global</span>
                        </div>
                    </div>
                </div>
            </div>

            <div class="auth-panel-footer">
                <span class="auth-status-dot"></span>

                <span>
                    Intelligence workspace
                </span>
            </div>
        </section>

        <section class="auth-form-panel">
            <div class="auth-form-container">
                @if (session('success'))
                    <div
                        class="alert alert-success border-0 shadow-sm mb-4"
                        role="alert"
                    >
                        <i class="bi bi-check-circle me-2"></i>
                        {{ session('success') }}
                    </div>
                @endif

                @yield('content')
            </div>
        </section>
    </main>
</body>
</html>
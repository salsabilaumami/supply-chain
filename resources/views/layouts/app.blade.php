<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0"
    >

    <title>
        @yield('title', 'Tinjauan Global') | Supply Chain Risk Intelligence
    </title>

    @vite([
        'resources/css/app.css',
        'resources/js/app.js'
    ])
</head>

<body>
    <div class="app-shell">

        {{-- =====================================================
             SIDEBAR
             ===================================================== --}}
        <aside class="sidebar">

            {{-- Brand --}}
            <div class="brand">
                <div class="brand-icon">
                    <i class="bi bi-radar"></i>
                </div>

                <div class="brand-copy">
                    <h1 class="brand-title">
                        Rantai Pasokan
                    </h1>

                    <p class="brand-subtitle">
                        Intelijen Risiko
                    </p>
                </div>
            </div>

            {{-- Navigation --}}
            <nav class="sidebar-nav">

                <div class="nav-section">
                    KECERDASAN
                </div>

                <a
                    href="{{ route('dashboard') }}"
                    class="sidebar-link {{ request()->routeIs('dashboard') ? 'active' : '' }}"
                >
                    <i class="bi bi-grid-1x2"></i>

                    <span>
                        Tinjauan Global
                    </span>
                </a>

                <a
                    href="{{ route('countries.index') }}"
                    class="sidebar-link {{ request()->routeIs('countries.*') ? 'active' : '' }}"
                >
                    <i class="bi bi-globe2"></i>

                    <span>
                        Pemantau Negara
                    </span>
                </a>

                <a
                    href="{{ route('weather.index') }}"
                    class="sidebar-link {{ request()->routeIs('weather.*') ? 'active' : '' }}"
                >
                    <i class="bi bi-cloud-lightning-rain"></i>

                    <span>
                        Pemantau Cuaca
                    </span>
                </a>

                <a
                    href="{{ route('currency.index') }}"
                    class="sidebar-link {{ request()->routeIs('currency.*') ? 'active' : '' }}"
                >
                    <i class="bi bi-currency-exchange"></i>

                    <span>
                        Dampak Mata Uang
                    </span>
                </a>

                <a
                    href="{{ route('news.index') }}"
                    class="sidebar-link {{ request()->routeIs('news.*') ? 'active' : '' }}"
                >
                    <i class="bi bi-newspaper"></i>

                    <span>
                        Intelijen Berita
                    </span>
                </a>

                <div class="nav-section">
                    LOGISTIK
                </div>

                <a
                    href="#"
                    class="sidebar-link"
                    title="Fitur ini akan dibuat pada tahap berikutnya"
                >
                    <i class="bi bi-geo-alt"></i>

                    <span>
                        Pelabuhan Global
                    </span>
                </a>

                <a
                    href="#"
                    class="sidebar-link"
                    title="Fitur ini akan dibuat pada tahap berikutnya"
                >
                    <i class="bi bi-arrow-left-right"></i>

                    <span>
                        Perbandingan Negara
                    </span>
                </a>

                <a
                    href="#"
                    class="sidebar-link"
                    title="Fitur ini akan dibuat pada tahap berikutnya"
                >
                    <i class="bi bi-bookmark-star"></i>

                    <span>
                        Daftar Pemantauan
                    </span>
                </a>

                @if (auth()->user()?->isAdmin())
                    <div class="nav-section">
                        SISTEM
                    </div>

                    <a
                        href="{{ route('admin.dashboard') }}"
                        class="sidebar-link {{ request()->routeIs('admin.*') ? 'active' : '' }}"
                    >
                        <i class="bi bi-sliders"></i>

                        <span>
                            Dasbor Admin
                        </span>
                    </a>
                @endif

            </nav>

            {{-- User Account --}}
            <div class="sidebar-user">
                <div class="sidebar-user-info">

                    <div class="sidebar-user-avatar">
                        {{ strtoupper(substr(auth()->user()->name, 0, 1)) }}
                    </div>

                    <div class="sidebar-user-text">
                        <strong>
                            {{ auth()->user()->name }}
                        </strong>

                        <span>
                            {{ auth()->user()->isAdmin()
                                ? 'Administrator'
                                : 'Pengguna'
                            }}
                        </span>
                    </div>
                </div>

                <form
                    method="POST"
                    action="{{ route('logout') }}"
                >
                    @csrf

                    <button
                        type="submit"
                        class="sidebar-logout"
                        title="Keluar dari sistem"
                        aria-label="Keluar dari sistem"
                    >
                        <i class="bi bi-box-arrow-right"></i>
                    </button>
                </form>
            </div>
        </aside>

        {{-- =====================================================
             APPLICATION AREA
             ===================================================== --}}
        <div class="main-wrapper">

            {{-- Topbar --}}
            <header class="topbar">
                <div class="topbar-context">
                    <span class="topbar-eyebrow">
                        WORKSPACE
                    </span>

                    <strong class="topbar-title">
                        Intelijen Rantai Pasokan Global
                    </strong>
                </div>

                <div class="topbar-actions">
                    <div class="system-status">
                        <span class="status-dot"></span>

                        <span>
                            Sistem Online
                        </span>
                    </div>
                </div>
            </header>

            {{-- Scrollable Application Workspace --}}
            <main class="main-content">

                <div class="content-workspace">

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
            </main>
        </div>
    </div>

    @stack('scripts')
</body>
</html>
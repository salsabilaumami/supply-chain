@extends('layouts.auth')

@section('title', 'Masuk')

@section('content')
    <div class="auth-form-header">
        <div class="auth-form-eyebrow">
            Intelligence Access
        </div>

        <h2>Masuk ke ruang pemantauan</h2>

        <p>
            Akses sinyal risiko negara, perbandingan kondisi,
            dan daftar pemantauan yang tersimpan pada akun Anda.
        </p>
    </div>

    <form method="POST" action="{{ route('login.store') }}">
        @csrf

        <div class="auth-field">
            <label for="email" class="form-label">
                Alamat Email
            </label>

            <div class="auth-input-wrap">
                <i class="bi bi-envelope auth-input-icon"></i>

                <input
                    type="email"
                    id="email"
                    name="email"
                    value="{{ old('email') }}"
                    class="form-control auth-input @error('email') is-invalid @enderror"
                    placeholder="nama@email.com"
                    autocomplete="email"
                    autofocus
                    required
                >
            </div>

            @error('email')
                <div class="auth-validation">
                    {{ $message }}
                </div>
            @enderror
        </div>

        <div class="auth-field">
            <label for="password" class="form-label">
                Password
            </label>

            <div class="auth-input-wrap">
                <i class="bi bi-lock auth-input-icon"></i>

                <input
                    type="password"
                    id="password"
                    name="password"
                    class="form-control auth-input @error('password') is-invalid @enderror"
                    placeholder="Masukkan password"
                    autocomplete="current-password"
                    required
                >
            </div>

            @error('password')
                <div class="auth-validation">
                    {{ $message }}
                </div>
            @enderror
        </div>

        <div class="auth-options">
            <div class="form-check">
                <input
                    class="form-check-input"
                    type="checkbox"
                    name="remember"
                    id="remember"
                    value="1"
                    @checked(old('remember'))
                >

                <label class="form-check-label" for="remember">
                    Pertahankan sesi
                </label>
            </div>

            <span class="text-secondary small">
                Akses terenkripsi
            </span>
        </div>

        <button type="submit" class="btn btn-primary auth-submit">
            Masuk ke Intelligence Workspace
            <i class="bi bi-arrow-right ms-2"></i>
        </button>
    </form>

    <div class="auth-switch">
        Belum memiliki ruang pemantauan?

        <a href="{{ route('register') }}">
            Buat akun
        </a>
    </div>
@endsection
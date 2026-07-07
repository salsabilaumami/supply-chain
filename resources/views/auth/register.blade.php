@extends('layouts.auth')

@section('title', 'Buat Akun')

@section('content')
    <div class="auth-form-header">
        <div class="auth-form-eyebrow">
            Create Monitoring Identity
        </div>

        <h2>Bangun ruang pemantauan Anda</h2>

        <p>
            Buat identitas pemantauan untuk menyimpan negara prioritas
            dan mengakses analisis risiko rantai pasokan.
        </p>
    </div>

    <form method="POST" action="{{ route('register.store') }}">
        @csrf

        <div class="auth-field">
            <label for="name" class="form-label">
                Nama
            </label>

            <div class="auth-input-wrap">
                <i class="bi bi-person auth-input-icon"></i>

                <input
                    type="text"
                    id="name"
                    name="name"
                    value="{{ old('name') }}"
                    class="form-control auth-input @error('name') is-invalid @enderror"
                    placeholder="Nama pengguna"
                    autocomplete="name"
                    autofocus
                    required
                >
            </div>

            @error('name')
                <div class="auth-validation">
                    {{ $message }}
                </div>
            @enderror
        </div>

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
                <i class="bi bi-shield-lock auth-input-icon"></i>

                <input
                    type="password"
                    id="password"
                    name="password"
                    class="form-control auth-input @error('password') is-invalid @enderror"
                    placeholder="Minimal 8 karakter"
                    autocomplete="new-password"
                    required
                >
            </div>

            @error('password')
                <div class="auth-validation">
                    {{ $message }}
                </div>
            @enderror
        </div>

        <div class="auth-field">
            <label for="password_confirmation" class="form-label">
                Konfirmasi Password
            </label>

            <div class="auth-input-wrap">
                <i class="bi bi-check2-circle auth-input-icon"></i>

                <input
                    type="password"
                    id="password_confirmation"
                    name="password_confirmation"
                    class="form-control auth-input"
                    placeholder="Ulangi password"
                    autocomplete="new-password"
                    required
                >
            </div>
        </div>

        <div class="alert alert-light border mb-4">
            <div class="d-flex gap-2">
                <i class="bi bi-info-circle text-primary"></i>

                <small class="text-secondary">
                    Akun baru dibuat sebagai pengguna aktif.
                    Hak akses administratif tidak dapat dipilih saat registrasi.
                </small>
            </div>
        </div>

        <button type="submit" class="btn btn-primary auth-submit">
            Aktifkan Ruang Pemantauan
            <i class="bi bi-arrow-right ms-2"></i>
        </button>
    </form>

    <div class="auth-switch">
        Sudah memiliki akun?

        <a href="{{ route('login') }}">
            Masuk
        </a>
    </div>
@endsection
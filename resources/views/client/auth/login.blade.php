@extends('layouts.guest')

@section('content')
    {{-- Judul --}}
    <div class="text-center mb-4">
        <h4 class="fw-bold">Client Portal Login</h4>
        <p class="text-muted">Selamat datang! Silakan masuk untuk melanjutkan.</p>
    </div>

    {{-- Notifikasi flash message --}}
    @if (session('error'))
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            {{ session('error') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

    @if (session('success'))
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            {{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

    {{-- Validasi error --}}
    @if ($errors->any())
        <div class="alert alert-danger">
            <ul class="mb-0">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    {{-- Form login manual --}}
    <form method="POST" action="{{ route('client.login') }}">
        @csrf

        <div class="mb-3">
            <label for="email" class="form-label">Alamat Email</label>
            <input id="email" type="email" name="email"
                   class="form-control @error('email') is-invalid @enderror"
                   value="{{ old('email') }}" required autofocus placeholder="name@example.com">
            @error('email')
                <div class="invalid-feedback">{{ $message }}</div>
            @enderror
        </div>

        <div class="mb-3">
            <label for="password" class="form-label">Kata Sandi</label>
            <input id="password" type="password" name="password"
                   class="form-control @error('password') is-invalid @enderror"
                   required placeholder="Masukkan password Anda">
            @error('password')
                <div class="invalid-feedback">{{ $message }}</div>
            @enderror
        </div>

        <div class="mb-3 form-check">
            <input type="checkbox" name="remember" id="remember" class="form-check-input">
            <label for="remember" class="form-check-label">Ingat saya</label>
        </div>

        <div class="d-grid mb-3">
            <button type="submit" class="btn btn-primary fw-semibold">
                <i class="bi bi-box-arrow-in-right me-1"></i> Masuk
            </button>
        </div>
    </form>

    {{-- Atau login dengan Google --}}
    <div class="text-center text-muted my-3">
        <span>atau</span>
    </div>

    <div class="d-grid mb-3">
        <a href="{{ route('client.auth.google') }}" class="btn btn-outline-danger fw-semibold">
            <i class="bi bi-google me-1"></i> Masuk dengan Google
        </a>
    </div>

    <div class="text-center">
        <small class="text-muted">
            Belum memiliki akun? Hubungi admin perusahaan Anda untuk pendaftaran.
        </small>
    </div>
@endsection

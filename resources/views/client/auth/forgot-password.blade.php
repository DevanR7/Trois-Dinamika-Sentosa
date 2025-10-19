@extends('layouts.guest')

@section('content')

    {{-- Judul --}}
    <div class="text-center mb-4">
        <h4 class="fw-bold">Lupa Password?</h4>
        <p class="text-muted" style="color: #555 !important;">Masukkan email Anda. Kami akan mengirimkan link reset.</p>
    </div>

    {{-- Notifikasi --}}
    @if (session('status'))
        <div class="alert alert-success" role="alert">
            {{ session('status') }}
        </div>
    @endif
    @if ($errors->any())
        <div class="alert alert-danger">
            <ul class="mb-0 ps-3">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    {{-- Form Minta Link --}}
    <form method="POST" action="{{ route('client.password.email') }}" class="login-form">
        @csrf

        {{-- Input Email dengan Ikon --}}
        <div class="mb-3">
            <label for="email" class="form-label">Email address</label>
            <div class="input-wrapper">
                <i class="bi bi-person input-icon"></i>
                <input id="email" type="email" name="email"
                       class="form-control @error('email') is-invalid @enderror"
                       value="{{ old('email') }}" required autofocus
                       placeholder="Masukkan Email Anda">
            </div>
        </div>

        {{-- Tombol Kirim --}}
        <div class="d-grid mb-4">
            <button type="submit" class="btn btn-login">
                Kirim Link Reset Password
            </button>
        </div>

        {{-- Link Kembali ke Login --}}
        <div class="text-center">
            <a href="{{ route('client.login') }}" class="forgot-password-link">Batal & Kembali ke Login</a>
        </div>
    </form>
@endsection
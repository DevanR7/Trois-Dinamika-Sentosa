@extends('layouts.admin-guest')

@section('content')

    {{-- 
      Judul "Selamat Datang Kembali" kita hilangkan agar konsisten
      dengan desain baru, di mana logo sudah menjadi 'judul' utama 
      di bagian atas form.
    --}}

    {{-- Notifikasi Error/Sukses (style disamakan) --}}
    @if ($errors->any())
        <div class="alert alert-danger">
            <ul class="mb-0 ps-3">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif
    @if (session('error'))
        <div class="alert alert-danger" role="alert">
            {{ session('error') }}
        </div>
    @endif

    {{-- Form login (dengan class & style baru) --}}
    <form method="POST" action="{{ route('login') }}" class="login-form">
        @csrf

        {{-- Input Username dengan Ikon (Struktur disamakan) --}}
        <div class="mb-3">
            <label for="username" class="form-label">{{ __('Username') }}</label>
            <div class="input-wrapper">
                {{-- Ikon Orang --}}
                <i class="bi bi-person input-icon"></i>
                <input id="username" type="text" name="username"
                       class="form-control @error('username') is-invalid @enderror"
                       value="{{ old('username') }}" required autofocus 
                       placeholder="Masukkan Username Anda">
            </div>
        </div>

        {{-- Input Password dengan Ikon & Toggle (Struktur disamakan) --}}
        <div class="mb-3">
            <label for="password" class="form-label">{{ __('Password') }}</label>
            <div class="input-wrapper">
                {{-- Ikon Gembok --}}
                <i class="bi bi-lock input-icon"></i>
                <input id="password" type="password" name="password"
                       class="form-control @error('password') is-invalid @enderror"
                       required 
                       placeholder="Masukkan Password Anda">
                {{-- Ikon Mata (Toggle) --}}
                <i class="bi bi-eye-slash" id="togglePassword"></i>
            </div>
        </div>

        {{-- Remember me & Forgot Password --}}
        <div class="d-flex justify-content-between align-items-center mb-4">
            <div class="form-check">
                <input type="checkbox" name="remember" id="remember" class="form-check-input">
                <label for="remember" class="form-check-label">{{ __('Remember me') }}</label>
            </div>
            
            @if (Route::has('password.request'))
                <a href="{{ route('password.request') }}" class="forgot-password-link">
                    {{ __('Lupa password?') }}
                </a>
            @endif
        </div>

        {{-- Tombol Login (Full-width, tanpa "Sign up") --}}
        <div class="d-grid mb-4">
            <button type="submit" class="btn btn-login">
                {{ __('Log in') }}
            </button>
        </div>
        
        {{-- Pemisah "ATAU" (Style disamakan) --}}
        <div class="text-center text-muted my-3">
            <span>ATAU</span>
        </div>

        {{-- Login Google (Style disamakan) --}}
        <div class="d-grid mb-3">
            <a href="{{ route('auth.google') }}" class="btn btn-outline-secondary fw-semibold">
                <i class="bi bi-google me-1"></i> Masuk dengan Google
            </a>
        </div>

        <div class="text-center mt-4">
            <small class="text-muted">
                Apakah Anda seorang Klien? 
                {{-- Pastikan 'client.login' adalah nama route Anda --}}
                <a href="{{ route('client.login') }}" class="redirect-link">Masuk di Portal Klien</a>
            </small>
        </div>

    </form>
@endsection

{{-- SCRIPT UNTUK TOGGLE PASSWORD (Harus sama dengan script client) --}}
@push('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const togglePassword = document.querySelector('#togglePassword');
            const password = document.querySelector('#password');

            if (togglePassword && password) {
                togglePassword.addEventListener('click', function (e) {
                    // Toggle tipe input
                    const type = password.getAttribute('type') === 'password' ? 'text' : 'password';
                    password.setAttribute('type', type);
                    
                    // Toggle ikon mata
                    this.classList.toggle('bi-eye');
                    this.classList.toggle('bi-eye-slash');
                });
            }
        });
    </script>
@endpush
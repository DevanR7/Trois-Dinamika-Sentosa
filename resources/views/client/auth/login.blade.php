@extends('layouts.guest')

@section('content')

    {{-- Notifikasi Error/Sukses (jika ada) --}}
    @if (session('error'))
        <div class="alert alert-danger" role="alert">
            {{ session('error') }}
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

    {{-- Form login (dengan class & style baru) --}}
    <form method="POST" action="{{ route('client.login') }}" class="login-form">
        @csrf

        {{-- Input Email dengan Ikon --}}
        <div class="mb-3">
            <label for="email" class="form-label">Email Address</label>
            <div class="input-wrapper">
                {{-- Ikon Orang --}}
                <i class="bi bi-person input-icon"></i>
                <input id="email" type="email" name="email"
                       class="form-control @error('email') is-invalid @enderror"
                       value="{{ old('email') }}" required autofocus 
                       placeholder="Masukkan Email Anda">
            </div>
        </div>

        {{-- Input Password dengan Ikon & Toggle --}}
        <div class="mb-3">
            <label for="password" class="form-label">Password</label>
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
                <label for="remember" class="form-check-label">Remember me</label>
            </div>
            
            <a href="#" class="forgot-password-link">Forgot password?</a>
        </div>

        {{-- Tombol Login & Sign up --}}
        <div class="row g-2 mb-4">
            <div class="col-6">
                <button type="submit" class="btn btn-login">
                    Login
                </button>
            </div>
            <div class="col-6">
                <a href="#" class="btn btn-signup">
                    Sign up
                </a>
            </div>
        </div>
        
        {{-- Login Google --}}
        <div class="text-center text-muted my-3">
            <span>atau</span>
        </div>
        <div class="d-grid mb-3">
            <a href="{{ route('client.auth.google') }}" class="btn btn-outline-secondary fw-semibold">
                <i class="bi bi-google me-1"></i> Masuk dengan Google
            </a>
        </div>

        {{-- Link Social Media 
        <div class="d-flex justify-content-center align-items-center mt-4">
            <p class="social-follow-text">FOLLOW</p>
            <div class="social-links">
                <a href="#" target="_blank"><i class="bi bi-facebook"></i></a>
                <a href="#" target="_blank"><i class="bi bi-twitter-x"></i></a>
            </div>
        </div> --}}
    </form>
@endsection

{{-- SCRIPT UNTUK TOGGLE PASSWORD --}}
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
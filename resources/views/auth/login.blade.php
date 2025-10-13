@extends('layouts.guest')

@section('content')
    <form method="POST" action="{{ route("login") }}">
        @csrf

        <div class="text-center mb-4">
            <h4 class="fw-bold">Selamat Datang Kembali</h4>
            <p class="text-muted">Silakan masuk untuk melanjutkan</p>
        </div>

        @if ($errors->any())
            <div class="alert alert-danger text-center p-2 mb-3">
                {{ $errors->first() }}
            </div>
        @endif
        @if (session('error'))
            <div class="alert alert-danger text-center p-2 mb-3">
                {{ session('error') }}
            </div>
        @endif

        <div class="mb-3">
            <label for="username" class="form-label">{{ __("Username") }}</label>
            <div class="input-group">
                <span class="input-group-text"><i class="bi bi-person-fill"></i></span>
                <input id="username" class="form-control" type="text" name="username" placeholder="Masukkan username Anda" value="{{ old("username") }}" required autofocus />
            </div>
        </div>

        <div class="mb-3">
            <label for="password" class="form-label">{{ __("Password") }}</label>
            <div class="input-group">
                <span class="input-group-text"><i class="bi bi-lock-fill"></i></span>
                <input id="password" class="form-control" type="password" name="password" placeholder="Masukkan password" required />
                <button class="btn btn-outline-secondary" type="button" id="togglePassword">
                    <i class="bi bi-eye-slash-fill"></i>
                </button>
            </div>
        </div>

        <div class="d-flex justify-content-between align-items-center mb-4">
            <div class="form-check">
                <input id="remember_me" type="checkbox" class="form-check-input" name="remember" />
                <label for="remember_me" class="form-check-label">{{ __("Remember me") }}</label>
            </div>
            @if (Route::has("password.request"))
                <a class="text-decoration-none small" href="{{ route("password.request") }}">
                    {{ __("Lupa password?") }}
                </a>
            @endif
        </div>

        <div class="d-grid">
            <button type="submit" class="btn btn-primary btn-lg">
                {{ __("Log in") }}
            </button>
        </div>

        <div class="d-flex align-items-center my-4">
            <hr class="flex-grow-1">
            <span class="px-3 text-muted">ATAU</span>
            <hr class="flex-grow-1">
        </div>

        <div class="d-grid">
            <a href="{{ route('auth.google') }}" class="btn btn-outline-dark">
                <svg class="me-2" style="width: 1.2em; height: 1.2em; vertical-align: text-bottom;" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path d="M22.56,12.25C22.56,11.47,22.49,10.72,22.36,10H12V14.5H18.33C18.05,16.05,17.26,17.34,16.07,18.23V21H20.25C22.09,19.34,22.56,16.53,22.56,12.25Z" fill="#4285F4"/><path d="M12,24C15.24,24,17.97,22.9,20.25,21L16.07,18.23C14.99,18.91,13.62,19.33,12,19.33C9.09,19.33,6.6,17.4,5.63,14.83H1.34V17.74C3.23,21.46,7.27,24,12,24Z" fill="#34A853"/><path d="M5.63,14.83C5.44,14.28,5.33,13.67,5.33,13C5.33,12.33,5.44,11.72,5.63,11.17V8.26H1.34C0.47,10.04,0,11.9,0,14C0,16.1,0.47,17.96,1.34,19.74L5.63,14.83Z" fill="#FBBC05"/><path d="M12,6.67C13.75,6.67,15.06,7.34,15.93,8.14L19.43,4.64C17.97,3.23,15.24,2,12,2C7.27,2,3.23,4.54,1.34,8.26L5.63,11.17C6.6,8.6,9.09,6.67,12,6.67Z" fill="#EA4335"/></svg>
                Lanjutkan dengan Google
            </a>
        </div>
    </form>
@endsection

@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function() {
        const togglePassword = document.querySelector('#togglePassword');
        const password = document.querySelector('#password');
        const icon = togglePassword.querySelector('i');

        togglePassword.addEventListener('click', function () {
            const type = password.getAttribute('type') === 'password' ? 'text' : 'password';
            password.setAttribute('type', type);
            icon.classList.toggle('bi-eye-slash-fill');
            icon.classList.toggle('bi-eye-fill');
        });
    });
</script>
@endpush
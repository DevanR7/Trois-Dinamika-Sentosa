@extends('layouts.guest')

@section('content')

    {{-- Judul --}}
    <div class="text-center mb-4">
        <h4 class="fw-bold">Atur Password Baru</h4>
        <p class="text-muted" style="color: #555 !important;">Silakan masukkan password baru Anda.</p>
    </div>

    {{-- Notifikasi --}}
    @if ($errors->any())
        <div class="alert alert-danger">
            <ul class="mb-0 ps-3">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    {{-- Form Reset --}}
    <form method="POST" action="{{ route('client.password.update') }}" class="login-form">
        @csrf

        {{-- Input Token (Hidden) --}}
        <input type="hidden" name="token" value="{{ $token }}">

        {{-- Input Email (Di-hardcode dari link) --}}
        <div class="mb-3">
            <label for="email" class="form-label">Email address</label>
            <div class="input-wrapper">
                <i class="bi bi-person input-icon"></i>
                <input id="email" type="email" name="email"
                       class="form-control @error('email') is-invalid @enderror"
                       value="{{ $email ?? old('email') }}" required readonly
                       style="background-color: #f3f4f6;">
            </div>
        </div>

        {{-- Input Password Baru --}}
        <div class="mb-3">
            <label for="password" class="form-label">Password Baru</label>
            <div class="input-wrapper">
                <i class="bi bi-lock input-icon"></i>
                <input id="password" type="password" name="password"
                       class="form-control @error('password') is-invalid @enderror"
                       required 
                       placeholder="Masukkan Password Baru">
                <i class="bi bi-eye-slash" id="togglePassword"></i>
            </div>
        </div>

        {{-- Input Konfirmasi Password --}}
        <div class="mb-4">
            <label for="password_confirmation" class="form-label">Konfirmasi Password</label>
            <div class="input-wrapper">
                <i class="bi bi-lock input-icon"></i>
                <input id="password_confirmation" type="password" name="password_confirmation"
                       class="form-control"
                       required 
                       placeholder="Ulangi Password Baru">
            </div>

            <div id="password-match-error" class="text-danger small mt-1 d-none">
                Password dan Konfirmasi Password tidak cocok.
            </div>
        </div>

        {{-- Tombol Reset --}}
        <div class="d-grid mb-4">
            <button type="submit" class="btn btn-login">
                Reset Password
            </button>
        </div>
    </form>
@endsection

{{-- Script untuk toggle password DAN validasi --}}
@push('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            
            // --- 1. LOGIKA TOGGLE PASSWORD ---
            const togglePassword = document.querySelector('#togglePassword');
            // 'password' akan didefinisikan ulang di bawah, jadi kita beri nama unik di sini
            const passwordInputForToggle = document.getElementById('password'); 

            if (togglePassword && passwordInputForToggle) {
                togglePassword.addEventListener('click', function (e) {
                    const type = passwordInputForToggle.getAttribute('type') === 'password' ? 'text' : 'password';
                    passwordInputForToggle.setAttribute('type', type);
                    this.classList.toggle('bi-eye');
                    this.classList.toggle('bi-eye-slash');
                });
            }

            // ===================================
            //     TAMBAHKAN LOGIKA VALIDASI INI
            // ===================================
            
            // --- 2. LOGIKA VALIDASI PASSWORD COCOK ---
            const passwordInput = document.getElementById('password');
            const passwordConfirmationInput = document.getElementById('password_confirmation');
            const passwordError = document.getElementById('password-match-error');

            if (passwordInput && passwordConfirmationInput && passwordError) {
                
                function validatePasswordMatch() {
                    if (passwordInput.value !== passwordConfirmationInput.value && passwordConfirmationInput.value.length > 0) {
                        passwordError.classList.remove('d-none'); // Tampilkan error
                    } else {
                        passwordError.classList.add('d-none'); // Sembunyikan error
                    }
                }

                passwordInput.addEventListener('input', validatePasswordMatch);
                passwordConfirmationInput.addEventListener('input', validatePasswordMatch);
            }
        });
    </script>
@endpush
@extends('layouts.guest')

@section('content')

    {{-- Judul --}}
    <div class="text-center mb-4">
        <h4 class="fw-bold">Buat Akun Klien</h4>
        <p class="text-muted" style="color: #555 !important;">Isi data di bawah untuk mendaftar.</p>
    </div>

    {{-- Notifikasi Error (jika validasi gagal) --}}
    @if ($errors->any())
        <div class="alert alert-danger">
            <ul class="mb-0 ps-3">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    {{-- Form Registrasi --}}
    <form method="POST" action="{{ route('client.register') }}" class="login-form">
        @csrf

        {{-- Input Nama Klien --}}
        <div class="mb-3">
            <label for="client_name" class="form-label">Nama Perusahaan/Klien</label>
            <div class="input-wrapper">
                <i class="bi bi-person input-icon"></i>
                <input id="client_name" type="text" name="client_name"
                       class="form-control"
                       value="{{ old('client_name') }}" required autofocus 
                       placeholder="Masukkan Nama Anda">
            </div>
        </div>

        {{-- Input Email --}}
        <div class="mb-3">
            <label for="email" class="form-label">Email address</label>
            <div class="input-wrapper">
                <i class="bi bi-person input-icon"></i>
                <input id="email" type="email" name="email"
                       class="form-control"
                       value="{{ old('email') }}" required 
                       placeholder="Masukkan Email Anda">
            </div>
        </div>

        {{-- Input Password Baru --}}
        <div class="mb-3">
            <label for="password" class="form-label">Password</label>
            <div class="input-wrapper">
                <i class="bi bi-lock input-icon"></i>
                <input id="password" type="password" name="password"
                       class="form-control"
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

        {{-- Tombol Register --}}
        <div class="d-grid mb-4">
            <button type="submit" class="btn btn-login">
                Daftar
            </button>
        </div>

        {{-- Link Kembali ke Login --}}
        <div class="text-center">
             <small class="text-muted">
                Sudah punya akun?
                <a href="{{ route('client.login') }}" class="redirect-link">Masuk di sini</a>
            </small>
        </div>
    </form>
@endsection

{{-- Script untuk toggle password DAN validasi --}}
@push('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            
            // --- 1. LOGIKA TOGGLE PASSWORD ---
            const togglePassword = document.querySelector('#togglePassword');
            const password = document.querySelector('#password');

            if (togglePassword && password) {
                togglePassword.addEventListener('click', function (e) {
                    const type = password.getAttribute('type') === 'password' ? 'text' : 'password';
                    password.setAttribute('type', type);
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
                
                // Buat fungsi untuk dicek setiap kali ada ketikan
                function validatePasswordMatch() {
                    if (passwordInput.value !== passwordConfirmationInput.value && passwordConfirmationInput.value.length > 0) {
                        // Jika tidak cocok DAN field konfirmasi tidak kosong
                        passwordError.classList.remove('d-none'); // Tampilkan error
                    } else {
                        // Jika cocok atau field konfirmasi kosong
                        passwordError.classList.add('d-none'); // Sembunyikan error
                    }
                }

                // Jalankan fungsi di atas setiap kali pengguna mengetik
                passwordInput.addEventListener('input', validatePasswordMatch);
                passwordConfirmationInput.addEventListener('input', validatePasswordMatch);
            }
        });
    </script>
@endpush
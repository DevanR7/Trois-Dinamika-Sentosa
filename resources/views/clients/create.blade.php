@extends('layouts.app')

@section('content')
<div class="container py-4">
    <div class="row justify-content-center">
        <div class="col-lg-8">
            <div class="card shadow-sm border-0">
                <div class="card-header bg-primary text-white">
                    <h4 class="mb-0">Tambah Klien Baru</h4>
                </div>
                <div class="card-body p-4">
                    <form action="{{ route('clients.store') }}" method="POST">
                        @csrf
                        
                        {{-- Memuat form isian dari file partial --}}
                        @include('clients._form')

                        <div class="d-flex justify-content-end mt-4">
                            <a href="{{ route('clients.index') }}" class="btn btn-secondary me-2">Batal</a>
                            <button type="submit" class="btn btn-primary">Simpan Klien</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

{{-- ========================================================== --}}
{{-- PASTIKAN BLOK SCRIPT INI ADA --}}
{{-- ========================================================== --}}
@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {

    // --- LOGIKA UNTUK HIDE/UNHIDE PASSWORD ---
    function setupPasswordToggle(inputId, toggleId) {
        const passwordInput = document.getElementById(inputId);
        const toggleButton = document.getElementById(toggleId);
        
        if (!passwordInput || !toggleButton) return;

        const eyeIcon = toggleButton.querySelector('i');

        toggleButton.addEventListener('click', function() {
            // Ganti tipe input
            const type = passwordInput.getAttribute('type') === 'password' ? 'text' : 'password';
            passwordInput.setAttribute('type', type);
            
            // Ganti ikon mata
            eyeIcon.classList.toggle('bi-eye-slash');
            eyeIcon.classList.toggle('bi-eye');
        });
    }

    setupPasswordToggle('password', 'toggle-password');
    setupPasswordToggle('password_confirmation', 'toggle-password-confirmation');


    // --- LOGIKA UNTUK VALIDASI KONFIRMASI PASSWORD ---
    const passwordInput = document.getElementById('password');
    const passwordConfirmationInput = document.getElementById('password_confirmation');
    const passwordError = document.getElementById('password-match-error');

    if (passwordInput && passwordConfirmationInput && passwordError) {
        function validatePasswordMatch() {
            if (passwordConfirmationInput.value.length > 0 && passwordInput.value !== passwordConfirmationInput.value) {
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
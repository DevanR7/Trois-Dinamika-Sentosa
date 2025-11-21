@extends('layouts.app')

@section('content')
<div class="container-fluid py-2">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h3 class="fw-bold text-dark mb-1">Tambah Klien Baru</h3>
            <p class="text-muted mb-0 small">Daftarkan mitra atau pelanggan baru.</p>
        </div>
        <div>
            <a href="{{ route('clients.index') }}" class="btn btn-outline-secondary btn-sm">
                <i class="bi bi-arrow-left me-1"></i> Kembali
            </a>
        </div>
    </div>

    <div class="row justify-content-center">
        <div class="col-lg-10">
            <form action="{{ route('clients.store') }}" method="POST">
                @csrf
                
                <div class="card card-transaction border-0 shadow-sm">
                    <div class="card-header bg-white p-4 border-bottom">
                        <div class="form-section-title mb-0"><i class="bi bi-person-plus"></i> Form Data Klien</div>
                    </div>
                    
                    <div class="card-body p-4">
                        @include('clients._form')

                        <div class="d-flex justify-content-end mt-4 pt-3 border-top">
                            <a href="{{ route('clients.index') }}" class="btn btn-light border me-2">Batal</a>
                            <button type="submit" class="btn btn-primary px-4 fw-bold">Simpan Klien</button>
                        </div>
                    </div>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection

{{-- SCRIPT PASSWORD TOGGLE TETAP SAMA --}}
@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    function setupPasswordToggle(inputId, toggleId) {
        const passwordInput = document.getElementById(inputId);
        const toggleButton = document.getElementById(toggleId);
        if (!passwordInput || !toggleButton) return;
        const eyeIcon = toggleButton.querySelector('i');

        toggleButton.addEventListener('click', function() {
            const type = passwordInput.getAttribute('type') === 'password' ? 'text' : 'password';
            passwordInput.setAttribute('type', type);
            eyeIcon.classList.toggle('bi-eye-slash');
            eyeIcon.classList.toggle('bi-eye');
        });
    }

    setupPasswordToggle('password', 'toggle-password');
    setupPasswordToggle('password_confirmation', 'toggle-password-confirmation');

    const passwordInput = document.getElementById('password');
    const passwordConfirmationInput = document.getElementById('password_confirmation');
    const passwordError = document.getElementById('password-match-error');

    if (passwordInput && passwordConfirmationInput && passwordError) {
        function validatePasswordMatch() {
            if (passwordConfirmationInput.value.length > 0 && passwordInput.value !== passwordConfirmationInput.value) {
                passwordError.classList.remove('d-none');
            } else {
                passwordError.classList.add('d-none');
            }
        }
        passwordInput.addEventListener('input', validatePasswordMatch);
        passwordConfirmationInput.addEventListener('input', validatePasswordMatch);
    }
});
</script>
@endpush
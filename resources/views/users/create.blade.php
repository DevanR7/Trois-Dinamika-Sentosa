@extends('layouts.app')

@section('content')
<div class="container-fluid py-2">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h3 class="fw-bold text-dark mb-1">Tambah User Baru</h3>
            <p class="text-muted mb-0 small">Tambahkan staf atau admin baru.</p>
        </div>
        <div>
            <a href="{{ route('users.index') }}" class="btn btn-outline-secondary btn-sm">
                <i class="bi bi-arrow-left me-1"></i> Kembali
            </a>
        </div>
    </div>

    <div class="row justify-content-center">
        <div class="col-lg-10">
            <form action="{{ route('users.store') }}" method="POST">
                @csrf
                
                <div class="card card-transaction border-0 shadow-sm">
                    <div class="card-header bg-white p-4 border-bottom">
                        <div class="form-section-title mb-0"><i class="bi bi-person-plus"></i> Form Data User</div>
                    </div>
                    
                    <div class="card-body p-4">
                        @include('users._form')

                        <div class="d-flex justify-content-end mt-4 pt-3 border-top">
                            <a href="{{ route('users.index') }}" class="btn btn-light border me-2">Batal</a>
                            <button type="submit" class="btn btn-primary px-4 fw-bold">Simpan User</button>
                        </div>
                    </div>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Init Select2 untuk Role
    $('#role').select2({ theme: 'bootstrap-5', placeholder: 'Pilih Role...', width: '100%' });

    // --- LOGIKA UNTUK HIDE/UNHIDE PASSWORD ---
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

    // --- LOGIKA UNTUK VALIDASI KONFIRMASI PASSWORD ---
    const passwordInput = document.getElementById('password');
    const passwordConfirmationInput = document.getElementById('password_confirmation');
    const passwordError = document.getElementById('password-match-error');
    if (passwordInput && passwordConfirmationInput && passwordError) {
        function validatePasswordMatch() {
            if (passwordInput.value !== passwordConfirmationInput.value && passwordConfirmationInput.value.length > 0) {
                passwordError.classList.remove('d-none');
            } else {
                passwordError.classList.add('d-none');
            }
        }
        passwordInput.addEventListener('input', validatePasswordMatch);
        passwordConfirmationInput.addEventListener('input', validatePasswordMatch);
    }
    
    // --- LOGIKA UNTUK MENAMPILKAN KODE SALES (SAMA DENGAN EDIT) ---
    const roleSelect = document.getElementById('role');
    const salesCodeContainer = document.getElementById('sales-code-container');
    if (roleSelect && salesCodeContainer) {
        function toggleSalesCode() {
            salesCodeContainer.style.display = (roleSelect.value === 'sales') ? 'block' : 'none';
        }
        toggleSalesCode();
        roleSelect.addEventListener('change', toggleSalesCode);
    }
});
</script>
@endpush
@extends('layouts.app')

@section('content')
<div class="container py-4">
    <div class="row justify-content-center">
        <div class="col-lg-8">
            <div class="card shadow-sm border-0">
                <div class="card-header bg-success text-white"><h4 class="mb-0">Edit User: {{ $user->full_name }}</h4></div>
                <div class="card-body p-4">
                    <form action="{{ route('users.update', $user->user_id) }}" method="POST">
                        @csrf
                        @method('PUT')
                        @include('users._form')
                        <div class="d-flex justify-content-end mt-4">
                            <a href="{{ route('users.index') }}" class="btn btn-secondary me-2">Batal</a>
                            <button type="submit" class="btn btn-success">Update User</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

{{-- ✅ SCRIPT DIGABUNGKAN MENJADI SATU BLOK --}}
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
    
    // --- LOGIKA UNTUK MENAMPILKAN KODE SALES ---
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
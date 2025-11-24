@extends('layouts.app')

@section('title', 'Edit User')

@section('content')
<div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 py-6">
    
    {{-- HEADER --}}
    <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center mb-6">
        <div>
            <div class="flex items-center gap-2 text-sm text-gray-500 mb-1">
                <a href="{{ route('users.index') }}" class="hover:text-indigo-600 transition">Users</a>
                <span>/</span>
                <span class="text-gray-800">Edit</span>
            </div>
            <h2 class="text-2xl font-bold text-gray-900 tracking-tight">Edit User</h2>
            <p class="text-sm text-gray-500 mt-1">
                Perbarui informasi untuk: <span class="font-bold text-indigo-600">{{ $user->full_name }}</span>
            </p>
        </div>
        <div class="mt-4 sm:mt-0">
            <a href="{{ route('users.index') }}" class="inline-flex items-center px-4 py-2 bg-white border border-gray-300 rounded-lg font-medium text-gray-700 hover:bg-gray-50 shadow-sm transition text-sm">
                <i class="material-icons text-lg mr-2">arrow_back</i> Kembali
            </a>
        </div>
    </div>

    <form action="{{ route('users.update', $user->user_id) }}" method="POST">
        @csrf
        @method('PUT')
        
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
            <div class="px-6 py-4 border-b border-gray-200 bg-gray-50 flex items-center gap-2">
                <i class="material-icons text-indigo-500">manage_accounts</i>
                <h3 class="text-sm font-bold text-gray-700 uppercase tracking-wider">Form Edit User</h3>
            </div>
            
            <div class="p-6">
                @include('users._form')
            </div>

            <div class="px-6 py-4 bg-gray-50 border-t border-gray-200 flex justify-end gap-3">
                <a href="{{ route('users.index') }}" class="px-4 py-2 bg-white border border-gray-300 rounded-lg text-gray-700 text-sm font-medium hover:bg-gray-50 shadow-sm transition">
                    Batal
                </a>
                <button type="submit" class="inline-flex items-center px-4 py-2 bg-green-600 border border-transparent rounded-lg font-bold text-sm text-white hover:bg-green-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-green-500 shadow-md transition">
                    <i class="material-icons text-lg mr-2">check_circle</i> Update User
                </button>
            </div>
        </div>
    </form>
</div>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Init Select2 (Theme sudah ada di app.css global)
    $('#role').select2({ theme: 'bootstrap-5', placeholder: 'Pilih Role...', width: '100%' });

    // Toggle Password Visibility
    function setupPasswordToggle(inputId, toggleId) {
        const passwordInput = document.getElementById(inputId);
        const toggleButton = document.getElementById(toggleId);
        if (!passwordInput || !toggleButton) return;

        const eyeIcon = toggleButton.querySelector('i');
        toggleButton.addEventListener('click', function() {
            const type = passwordInput.getAttribute('type') === 'password' ? 'text' : 'password';
            passwordInput.setAttribute('type', type);
            eyeIcon.innerText = type === 'password' ? 'visibility_off' : 'visibility';
        });
    }
    setupPasswordToggle('password', 'toggle-password');
    setupPasswordToggle('password_confirmation', 'toggle-password-confirmation');

    // Validate Password Match
    const passwordInput = document.getElementById('password');
    const passwordConfirmationInput = document.getElementById('password_confirmation');
    const passwordError = document.getElementById('password-match-error');
    
    if (passwordInput && passwordConfirmationInput && passwordError) {
        function validatePasswordMatch() {
            if (passwordInput.value !== passwordConfirmationInput.value && passwordConfirmationInput.value.length > 0) {
                passwordError.classList.remove('hidden');
            } else {
                passwordError.classList.add('hidden');
            }
        }
        passwordInput.addEventListener('input', validatePasswordMatch);
        passwordConfirmationInput.addEventListener('input', validatePasswordMatch);
    }
    
    // Toggle Sales Code
    const roleSelect = $('#role'); 
    const salesCodeContainer = document.getElementById('sales-code-container');
    
    if (roleSelect.length && salesCodeContainer) {
        function toggleSalesCode() {
            const val = roleSelect.val();
            salesCodeContainer.style.display = (val === 'sales') ? 'block' : 'none';
        }
        
        // Jalankan saat load untuk mengecek value yang ada (old value / db value)
        toggleSalesCode(); 
        
        // Jalankan saat berubah
        roleSelect.on('change', toggleSalesCode);
    }
});
</script>
@endpush
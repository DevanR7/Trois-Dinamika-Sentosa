@extends('layouts.app')

@section('title', 'Edit Klien')

@section('content')
<div class="max-w-4xl mx-auto">
    
    <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center mb-6 gap-4">
        <div>
            <h2 class="text-2xl font-bold text-gray-900 tracking-tight">Edit Klien</h2>
            <p class="text-sm text-gray-500 mt-1">Perbarui informasi: <span class="font-bold text-indigo-600">{{ $client->client_name }}</span></p>
        </div>
        <a href="{{ route('clients.index') }}" class="px-4 py-2 bg-white border border-gray-300 rounded-lg text-gray-700 text-sm font-medium hover:bg-gray-50 transition shadow-sm">
            Kembali
        </a>
    </div>

    <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
        <div class="px-6 py-4 border-b border-gray-100 bg-gray-50 flex items-center gap-2">
            <i class="bi bi-pencil-square text-indigo-500"></i>
            <h3 class="text-sm font-bold text-gray-700 uppercase tracking-wider">Edit Data Klien</h3>
        </div>
        
        <div class="p-6">
            <form action="{{ route('clients.update', $client->client_id) }}" method="POST">
                @csrf @method('PUT')
                
                @include('clients._form')

                <div class="flex justify-end gap-3 pt-6 border-t border-gray-100 mt-6">
                    <a href="{{ route('clients.index') }}" class="px-4 py-2 bg-white border border-gray-300 rounded-lg text-gray-700 text-sm font-medium hover:bg-gray-50 transition">Batal</a>
                    <button type="submit" class="px-6 py-2 bg-green-600 hover:bg-green-700 text-white text-sm font-bold rounded-lg shadow-md transition flex items-center">
                        <i class="bi bi-check-lg mr-2"></i> Update Klien
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    // (Copy JS Logic Password Toggle dari Create Blade disini jika perlu, atau buat file JS terpisah)
    // Sama persis dengan create.blade.php
    function setupPasswordToggle(inputId, btnId) {
        const input = document.getElementById(inputId);
        const btn = document.getElementById(btnId);
        if(!input || !btn) return;
        btn.addEventListener('click', () => {
            const type = input.type === 'password' ? 'text' : 'password';
            input.type = type;
            btn.querySelector('i').classList.toggle('bi-eye');
            btn.querySelector('i').classList.toggle('bi-eye-slash');
        });
    }
    setupPasswordToggle('password', 'toggle-password');
    setupPasswordToggle('password_confirmation', 'toggle-password-confirmation');

    const p1 = document.getElementById('password');
    const p2 = document.getElementById('password_confirmation');
    const err = document.getElementById('password-match-error');
    if(p1 && p2 && err) {
        const checkMatch = () => {
            if(p2.value && p1.value !== p2.value) err.classList.remove('hidden');
            else err.classList.add('hidden');
        };
        p1.addEventListener('input', checkMatch);
        p2.addEventListener('input', checkMatch);
    }
});
</script>
@endpush
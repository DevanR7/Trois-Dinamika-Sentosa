@extends('layouts.app')

@section('title', 'Edit User')

@section('content')
<div class="max-w-4xl mx-auto pb-20 animate-enter">
    
    {{-- HEADER NAVIGATION --}}
    <div class="flex items-center justify-between mb-6">
        <div>
            <nav class="flex text-sm text-slate-500 mb-1">
                <a href="{{ route('users.index') }}" class="hover:text-indigo-600 transition-colors">Users</a>
                <span class="mx-2 text-slate-300">/</span>
                <span class="text-slate-800 font-semibold">Edit</span>
            </nav>
            <h1 class="text-2xl font-bold text-slate-800 tracking-tight">Edit User</h1>
        </div>
        <a href="{{ route('users.index') }}" 
           class="hidden sm:flex items-center justify-center px-4 py-2 bg-white border border-slate-200 rounded-lg text-slate-600 text-sm font-medium hover:bg-slate-50 hover:text-indigo-600 transition-all shadow-sm">
            <i class="material-icons text-[18px] mr-1">arrow_back</i> Kembali
        </a>
    </div>

    <form action="{{ route('users.update', $user->user_id) }}" method="POST">
        @csrf
        @method('PUT')
        
        <div class="dashboard-card p-0 overflow-hidden shadow-lg border-0 ring-1 ring-slate-900/5">
            {{-- Banner User Info --}}
            <div class="px-6 py-4 bg-indigo-50 border-b border-indigo-100 flex items-center gap-3">
                <div class="w-10 h-10 rounded-full bg-indigo-200 text-indigo-700 flex items-center justify-center font-bold text-lg">
                    {{ substr($user->full_name, 0, 1) }}
                </div>
                <div>
                    <h3 class="text-sm font-bold text-indigo-900">Mengedit Akun: {{ $user->full_name }}</h3>
                    <p class="text-xs text-indigo-600">Terakhir diperbarui: {{ $user->updated_at->diffForHumans() }}</p>
                </div>
            </div>

            {{-- Content Form --}}
            <div class="p-6 md:p-8 bg-white">
                @include('users._form')
            </div>

            {{-- Footer Action --}}
            <div class="px-6 py-4 bg-slate-50 border-t border-slate-100 flex items-center justify-end gap-3">
                <a href="{{ route('users.index') }}" 
                   class="px-5 py-2.5 bg-white border border-slate-300 text-slate-600 rounded-lg text-sm font-bold hover:bg-slate-50 transition-all shadow-sm">
                    Batal
                </a>
                <button type="submit" 
                        class="px-6 py-2.5 bg-indigo-600 hover:bg-indigo-700 text-white rounded-lg text-sm font-bold shadow-md shadow-indigo-200 transition-all flex items-center gap-2">
                    <i class="material-icons text-[18px]">check_circle</i> Simpan Perubahan
                </button>
            </div>
        </div>
    </form>
</div>
@endsection

@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function() {
        $('#role').select2({ placeholder: 'Pilih Role...', width: '100%', dropdownCssClass: 'select2-dropdown-clean' });

        const roleSelect = $('#role');
        const salesContainer = document.getElementById('sales-code-container');
        
        function checkSalesRole() {
            if(roleSelect.val() === 'sales') {
                salesContainer.classList.remove('hidden');
            } else {
                salesContainer.classList.add('hidden');
            }
        }
        roleSelect.on('change', checkSalesRole);
        checkSalesRole();

        // Password logic sama seperti create
        function setupToggle(id, btnId) {
            const input = document.getElementById(id);
            const btn = document.getElementById(btnId);
            if(!input || !btn) return;
            btn.addEventListener('click', () => {
                const type = input.type === 'password' ? 'text' : 'password';
                input.type = type;
                btn.querySelector('i').innerText = type === 'password' ? 'visibility_off' : 'visibility';
            });
        }
        setupToggle('password', 'toggle-password');
        setupToggle('password_confirmation', 'toggle-password-confirmation');

        // Check Match
        const pass = document.getElementById('password');
        const confirm = document.getElementById('password_confirmation');
        const indicator = document.getElementById('password-match-indicator');
        const indicatorText = indicator?.querySelector('.match-text');

        function checkMatch() {
            // Edit: Hanya cek jika salah satu diisi
            if(!pass.value && !confirm.value) { indicator.classList.add('hidden'); return; }
            indicator.classList.remove('hidden');
            
            if(pass.value === confirm.value) {
                indicatorText.innerHTML = '<i class="material-icons text-emerald-500 text-[14px]">check_circle</i> Password Cocok';
                indicatorText.className = 'match-text text-emerald-600 flex items-center gap-1';
            } else {
                indicatorText.innerHTML = '<i class="material-icons text-red-500 text-[14px]">cancel</i> Password Tidak Sama';
                indicatorText.className = 'match-text text-red-600 flex items-center gap-1';
            }
        }
        pass.addEventListener('input', checkMatch);
        confirm.addEventListener('input', checkMatch);
    });
</script>
@endpush
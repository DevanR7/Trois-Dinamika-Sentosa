@extends('admin.layouts.app')

@section('title', 'Tambah User')

@section('content')
<div class="max-w-4xl mx-auto pb-20 animate-enter">
    
    {{-- HEADER NAVIGATION --}}
    <div class="flex items-center justify-between mb-6">
        <div>
            <nav class="flex text-sm text-slate-500 mb-1">
                <a href="{{ route('admin.users.index') }}" class="hover:text-indigo-600 transition-colors">Users</a>
                <span class="mx-2 text-slate-300">/</span>
                <span class="text-slate-800 font-semibold">Buat Baru</span>
            </nav>
            <h1 class="text-2xl font-bold text-slate-800 tracking-tight">Tambah User Baru</h1>
        </div>
        <a href="{{ route('admin.users.index') }}" 
           class="hidden sm:flex items-center justify-center px-4 py-2 bg-white border border-slate-200 rounded-lg text-slate-600 text-sm font-medium hover:bg-slate-50 hover:text-indigo-600 transition-all shadow-sm">
            <i class="material-icons text-[18px] mr-1">arrow_back</i> Kembali
        </a>
    </div>

    <form action="{{ route('admin.users.store') }}" method="POST">
        @csrf
        
        <div class="dashboard-card p-0 overflow-hidden shadow-lg border-0 ring-1 ring-slate-900/5">
            {{-- Content Form --}}
            <div class="p-6 md:p-8 bg-white">
                @include('admin.users._form')
            </div>

            {{-- Footer Action --}}
            <div class="px-6 py-4 bg-slate-50 border-t border-slate-100 flex items-center justify-end gap-3">
                <a href="{{ route('admin.users.index') }}" 
                   class="px-5 py-2.5 bg-white border border-slate-300 text-slate-600 rounded-lg text-sm font-bold hover:bg-slate-50 transition-all shadow-sm">
                    Batal
                </a>
                <button type="submit" 
                        class="px-6 py-2.5 bg-indigo-600 hover:bg-indigo-700 text-white rounded-lg text-sm font-bold shadow-md shadow-indigo-200 transition-all flex items-center gap-2">
                    <i class="material-icons text-[18px]">save</i> Simpan Data
                </button>
            </div>
        </div>
    </form>
</div>
@endsection

@push('scripts')
<script>
    // Logic khusus halaman Create (Select2 & Password Toggle)
    document.addEventListener('DOMContentLoaded', function() {
        // Init Select2
        $('#role').select2({ placeholder: 'Pilih Role...', width: '100%', dropdownCssClass: 'select2-dropdown-clean' });

        // Logic Kode Sales
        const roleSelect = $('#role');
        const salesContainer = document.getElementById('sales-code-container');
        
        function checkSalesRole() {
            // Ubah logika ini sesuai value role sales di DB Anda (misal 'sales', 'salesman', dll)
            if(roleSelect.val() === 'sales') {
                salesContainer.classList.remove('hidden');
            } else {
                salesContainer.classList.add('hidden');
            }
        }
        roleSelect.on('change', checkSalesRole);
        checkSalesRole(); // Run on load

        // Password Toggle Helpers
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

        // Check Match Password
        const pass = document.getElementById('password');
        const confirm = document.getElementById('password_confirmation');
        const indicator = document.getElementById('password-match-indicator');
        const indicatorText = indicator?.querySelector('.match-text');

        function checkMatch() {
            if(!confirm.value) { indicator.classList.add('hidden'); return; }
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
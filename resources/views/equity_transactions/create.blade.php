@extends('layouts.app')

@section('title', 'Catat Transaksi Modal')

@push('styles')
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
@endpush

@section('content')
<div class="max-w-3xl mx-auto pb-20 animate-enter">
    
    {{-- HEADER --}}
    <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center mb-8 gap-4">
        <div>
            <nav class="flex items-center gap-2 text-sm text-slate-500 mb-1">
                <a href="{{ route('equity-transactions.index') }}" class="hover:text-indigo-600 transition-colors">Modal & Prive</a>
                <span class="mx-2 text-slate-300">/</span>
                <span class="text-slate-800 font-semibold">Baru</span>
            </nav>
            <h1 class="text-2xl font-bold text-slate-800 tracking-tight">Catat Transaksi Modal</h1>
        </div>
        <a href="{{ route('equity-transactions.index') }}" 
           class="h-[48px] px-6 bg-white border border-slate-300 text-slate-600 rounded-lg text-sm font-bold hover:bg-slate-50 transition-all flex items-center justify-center gap-2 shadow-sm">
            <i class="material-icons text-[18px]">arrow_back</i> Kembali
        </a>
    </div>

    <form action="{{ route('equity-transactions.store') }}" method="POST" id="equity-form">
        @csrf
        
        <div class="dashboard-card p-0 overflow-hidden shadow-lg border-0 ring-1 ring-slate-900/5">
            <div class="px-6 py-4 border-b border-slate-100 bg-slate-50/50 flex items-center gap-3">
                <div class="p-2 bg-indigo-50 rounded-lg text-indigo-600">
                    <i class="material-icons text-[20px]">account_balance_wallet</i>
                </div>
                <h3 class="text-sm font-bold text-slate-700 uppercase tracking-wide">Form Transaksi</h3>
            </div>
            
            <div class="p-6 grid grid-cols-1 md:grid-cols-2 gap-6">
                
                {{-- Tanggal --}}
                <div>
                    <label for="transaction_date" class="block text-xs font-bold text-slate-500 uppercase mb-1.5">Tanggal Transaksi <span class="text-red-500">*</span></label>
                    <input type="date" name="transaction_date" id="transaction_date" value="{{ old('transaction_date', now()->toDateString()) }}" class="form-input" required>
                    @error('transaction_date') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                </div>

                {{-- Jumlah --}}
                <div>
                    <label for="amount_display" class="block text-xs font-bold text-slate-500 uppercase mb-1.5">Jumlah (Rp) <span class="text-red-500">*</span></label>
                    <div class="relative">
                        <div class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3">
                            <span class="text-slate-400 font-bold text-sm">Rp</span>
                        </div>
                        <input type="text" id="amount_display" class="form-input pl-10 text-lg font-bold text-slate-800 font-mono" placeholder="0" required>
                        <input type="hidden" name="amount" id="amount" value="{{ old('amount') }}">
                    </div>
                    @error('amount') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                </div>

                {{-- Akun Modal --}}
                <div>
                    <label for="equity_account_id" class="block text-xs font-bold text-slate-500 uppercase mb-1.5">Akun Modal / Prive <span class="text-red-500">*</span></label>
                    {{-- PERBAIKAN: Menggunakan class 'form-input select2-basic' --}}
                    <select name="equity_account_id" id="equity_account_id" class="form-input select2-basic" required>
                        <option value="" disabled selected>-- Pilih Akun --</option>
                        @foreach ($equityAccounts as $account)
                            <option value="{{ $account->account_id }}" @selected(old('equity_account_id') == $account->account_id)>
                                {{ $account->account_number }} - {{ $account->account_name }}
                            </option>
                        @endforeach
                    </select>
                    <p class="mt-1 text-xs text-slate-400">Pilih Akun Modal (untuk setoran) atau Prive (untuk penarikan).</p>
                    @error('equity_account_id') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                </div>

                {{-- Akun Kas --}}
                <div>
                    <label for="cash_bank_account_id" class="block text-xs font-bold text-slate-500 uppercase mb-1.5">Akun Kas / Bank <span class="text-red-500">*</span></label>
                    {{-- PERBAIKAN: Menggunakan class 'form-input select2-basic' --}}
                    <select name="cash_bank_account_id" id="cash_bank_account_id" class="form-input select2-basic" required>
                        <option value="" disabled selected>-- Pilih Akun Kas --</option>
                        @foreach ($cashAccounts as $account)
                            <option value="{{ $account->account_id }}" @selected(old('cash_bank_account_id') == $account->account_id)>
                                {{ $account->account_number }} - {{ $account->account_name }}
                            </option>
                        @endforeach
                    </select>
                    <p class="mt-1 text-xs text-slate-400">Akun Kas yang bertambah/berkurang.</p>
                    @error('cash_bank_account_id') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                </div>

                {{-- Deskripsi --}}
                <div class="md:col-span-2">
                    <label for="description" class="block text-xs font-bold text-slate-500 uppercase mb-1.5">Deskripsi <span class="text-red-500">*</span></label>
                    <textarea name="description" id="description" rows="3" class="form-textarea" placeholder="Contoh: Setoran modal awal dari Pemilik" required>{{ old('description') }}</textarea>
                    @error('description') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                </div>

            </div>

            <div class="px-6 py-4 bg-slate-50 border-t border-slate-100 flex justify-end gap-3">
                <a href="{{ route('equity-transactions.index') }}" 
                   class="h-[48px] px-6 bg-white border border-slate-300 text-slate-600 rounded-lg text-sm font-bold hover:bg-slate-50 transition-all flex items-center justify-center shadow-sm">
                    Batal
                </a>
                <button type="submit" 
                        class="h-[48px] px-8 bg-indigo-600 hover:bg-indigo-700 text-white text-sm font-bold rounded-lg shadow-md transition-all flex items-center justify-center gap-2 group hover:-translate-y-0.5">
                    <i class="material-icons text-[20px] group-hover:scale-110 transition-transform">save</i> Simpan Transaksi
                </button>
            </div>
        </div>
    </form>
</div>
@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/autonumeric@4.6.0/dist/autoNumeric.min.js"></script>
<script>
    document.addEventListener('DOMContentLoaded', function() {
        // 1. Init Select2
        $('.select2-basic').select2({ placeholder: '-- Pilih --', allowClear: true, width: '100%', dropdownCssClass: 'select2-dropdown-clean' });

        // 2. Init AutoNumeric
        const amountDisplay = document.getElementById('amount_display');
        const amountInput = document.getElementById('amount');
        
        const anElement = new AutoNumeric(amountDisplay, {
            decimalPlaces: 0,
            digitGroupSeparator: '.',
            decimalCharacter: ',',
            minimumValue: '0'
        });

        amountDisplay.addEventListener('autoNumeric:rawValueModified', e => {
            amountInput.value = e.detail.newRawValue;
        });

        // 3. Validasi Submit
        document.getElementById('equity-form').addEventListener('submit', function(e) {
            if(!amountInput.value || amountInput.value == 0) {
                e.preventDefault();
                window.showToast('Jumlah nominal tidak boleh kosong!', 'error'); // Menggunakan Global Toast
                amountDisplay.focus();
            }
        });
    });
</script>
@endpush
@extends('layouts.app')

@section('title', 'Edit Pinjaman')

@push('styles')
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
@endpush

@section('content')
<div class="max-w-4xl mx-auto pb-20 animate-enter">
    
    {{-- HEADER --}}
    <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center mb-8 gap-4">
        <div>
            <nav class="flex items-center gap-2 text-sm text-slate-500 mb-1">
                <a href="{{ route('loans.index') }}" class="hover:text-indigo-600 transition-colors">Pinjaman</a>
                <span class="mx-2 text-slate-300">/</span>
                <span class="text-slate-800 font-semibold">Edit</span>
            </nav>
            <h1 class="text-2xl font-bold text-slate-800 tracking-tight">Edit Pinjaman</h1>
            <p class="text-sm text-slate-500 mt-1">Perbarui data pinjaman: <span class="font-bold text-indigo-600">{{ $loan->lender_name }}</span></p>
        </div>
        <a href="{{ route('loans.index') }}" 
           class="h-[48px] px-6 bg-white border border-slate-300 text-slate-600 rounded-lg text-sm font-bold hover:bg-slate-50 transition-all flex items-center justify-center gap-2 shadow-sm">
            <i class="material-icons text-[18px]">arrow_back</i> Kembali
        </a>
    </div>

    {{-- ALERT JIKA SUDAH ADA PEMBAYARAN --}}
    @if ($loan->payments()->exists())
        <div class="mb-6 bg-red-50 border-l-4 border-red-500 p-4 rounded-md shadow-sm flex items-center gap-3">
            <i class="material-icons text-red-600 text-2xl">lock</i>
            <div>
                <strong class="text-red-800 text-sm font-bold block">Pinjaman Terkunci</strong>
                <span class="text-red-700 text-xs">Pinjaman ini sudah memiliki riwayat pembayaran. Nilai pokok dan akun tidak dapat diubah.</span>
            </div>
        </div>
    @endif

    <form action="{{ route('loans.update', $loan) }}" method="POST" id="loan-form">
        @csrf
        @method('PUT')
        
        <div class="dashboard-card p-0 overflow-hidden shadow-lg border-0 ring-1 ring-slate-900/5">
            
            <div class="px-6 py-4 border-b border-slate-100 bg-slate-50/50 flex items-center gap-3">
                <div class="p-2 bg-indigo-50 rounded-lg text-indigo-600">
                    <i class="material-icons text-[20px]">edit_note</i>
                </div>
                <h3 class="text-sm font-bold text-slate-700 uppercase tracking-wide">Edit Data Pinjaman</h3>
            </div>
            
            <fieldset {{ $loan->payments()->exists() ? 'disabled' : '' }} class="group disabled:opacity-75 transition-opacity">
                <div class="p-6 grid grid-cols-1 md:grid-cols-2 gap-6">
                    
                    <div class="md:col-span-2">
                        <label for="lender_name" class="block text-xs font-bold text-slate-500 uppercase mb-1.5">Nama Pemberi Pinjaman <span class="text-red-500">*</span></label>
                        <input type="text" name="lender_name" id="lender_name" value="{{ old('lender_name', $loan->lender_name) }}" class="form-input" required>
                        @error('lender_name') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                    </div>

                    <div>
                        <label for="loan_date" class="block text-xs font-bold text-slate-500 uppercase mb-1.5">Tanggal Diterima <span class="text-red-500">*</span></label>
                        <input type="date" name="loan_date" id="loan_date" value="{{ old('loan_date', $loan->loan_date->format('Y-m-d')) }}" class="form-input" required>
                        @error('loan_date') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                    </div>

                    <div>
                        <label for="principal_amount_display" class="block text-xs font-bold text-slate-500 uppercase mb-1.5">Jumlah Pokok Pinjaman (Rp) <span class="text-red-500">*</span></label>
                        <div class="relative">
                            <div class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3">
                                <span class="text-slate-400 font-bold text-sm">Rp</span>
                            </div>
                            {{-- PERBAIKAN: Hapus class 'input-currency' agar tidak bentrok dengan app.js --}}
                            <input type="text" id="principal_amount_display" class="form-input pl-10 font-mono font-bold text-slate-800" required>
                            
                            {{-- Input Hidden untuk menyimpan nilai asli ke database --}}
                            <input type="hidden" name="principal_amount" id="principal_amount" value="{{ old('principal_amount', intval($loan->principal_amount)) }}">
                        </div>
                        @error('principal_amount') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                    </div>

                    <div>
                        <label for="loan_account_id" class="block text-xs font-bold text-slate-500 uppercase mb-1.5">Akun Utang (Kredit) <span class="text-red-500">*</span></label>
                        <select name="loan_account_id" id="loan_account_id" class="form-input select2-basic" required>
                            @foreach ($loanAccounts as $account)
                                <option value="{{ $account->account_id }}" @selected(old('loan_account_id', $loan->loan_account_id) == $account->account_id)>
                                    {{ $account->account_number }} - {{ $account->account_name }}
                                </option>
                            @endforeach
                        </select>
                        <p class="mt-1.5 text-[10px] text-slate-400">Akun Kewajiban yang bertambah.</p>
                        @error('loan_account_id') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                    </div>

                    <div>
                        <label for="cash_bank_account_id" class="block text-xs font-bold text-slate-500 uppercase mb-1.5">Dana Diterima Ke (Debit) <span class="text-red-500">*</span></label>
                        <select name="cash_bank_account_id" id="cash_bank_account_id" class="form-input select2-basic" required>
                            @foreach ($cashAccounts as $account)
                                <option value="{{ $account->account_id }}" @selected(old('cash_bank_account_id', $loan->cash_bank_account_id) == $account->account_id)>
                                    {{ $account->account_number }} - {{ $account->account_name }}
                                </option>
                            @endforeach
                        </select>
                        <p class="mt-1.5 text-[10px] text-slate-400">Akun Kas/Bank tempat uang masuk.</p>
                        @error('cash_bank_account_id') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                    </div>

                    <div class="md:col-span-2">
                        <label for="description" class="block text-xs font-bold text-slate-500 uppercase mb-1.5">Deskripsi</label>
                        <textarea name="description" id="description" rows="3" class="form-textarea">{{ old('description', $loan->description) }}</textarea>
                        @error('description') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                    </div>

                </div>
            </fieldset>

            <div class="px-6 py-4 bg-slate-50 border-t border-slate-100 flex justify-end gap-3">
                <a href="{{ route('loans.index') }}" class="h-[48px] px-6 bg-white border border-slate-300 text-slate-600 rounded-lg text-sm font-bold hover:bg-slate-50 transition-all flex items-center justify-center shadow-sm">
                    Batal
                </a>
                <button type="submit" class="h-[48px] px-8 bg-indigo-600 hover:bg-indigo-700 text-white text-sm font-bold rounded-lg shadow-md transition-all flex items-center justify-center gap-2 group hover:-translate-y-0.5" {{ $loan->payments()->exists() ? 'disabled' : '' }}>
                    <i class="material-icons text-[20px] group-hover:scale-110 transition-transform">save</i> Update Pinjaman
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
        // Init Select2 Manual (karena select2-basic di app.js mungkin telat untuk edit yang kompleks)
        // Namun jika class 'select2-basic' ada, app.js akan menghandle-nya.
        // Kita biarkan app.js menghandle select2, fokus kita di AutoNumeric.

        const display = document.getElementById('principal_amount_display');
        const hidden = document.getElementById('principal_amount');

        // 1. Inisialisasi Manual AutoNumeric KHUSUS HALAMAN EDIT
        // Kita gunakan setting yang sama dengan global
        const anElement = new AutoNumeric(display, {
            decimalPlaces: 0,
            digitGroupSeparator: '.',
            decimalCharacter: ',',
            minimumValue: '0'
        });

        // 2. SET VALUE SAAT LOAD (PENTING!)
        if (hidden.value) {
            anElement.set(hidden.value);
        }

        // 3. Update Hidden Input Saat Diketik
        display.addEventListener('autoNumeric:rawValueModified', e => {
            hidden.value = e.detail.newRawValue;
        });
        
        // 4. Validasi Submit
        document.getElementById('loan-form').addEventListener('submit', function(e) {
             // Pastikan nilai hidden terisi
            if (display && AutoNumeric.getAutoNumericElement(display)) {
                hidden.value = AutoNumeric.getAutoNumericElement(display).getNumber();
            }
            
            if(!hidden.value || hidden.value == 0) {
                e.preventDefault();
                window.showToast('Jumlah pokok tidak boleh kosong!', 'error');
                display.focus();
            }
        });

        @if(session('success')) window.showToast("{{ session('success') }}", 'success'); @endif
        @if(session('error')) window.showToast("{{ session('error') }}", 'error'); @endif
    });
</script>
@endpush
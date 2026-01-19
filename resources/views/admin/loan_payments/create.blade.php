@extends('admin.layouts.app')

@section('title', 'Bayar Cicilan Pinjaman')

@section('content')
    {{-- Header --}}
    <div class="page-header">
        <div>
            <h1 class="page-title">Bayar Cicilan Pinjaman</h1>
            <p class="page-subtitle">Pencatatan pembayaran hutang kepada: <strong>{{ $loan->lender_name }}</strong></p>
        </div>
        <div>
            <a href="{{ route('admin.loans.show', $loan->loan_id) }}" class="btn btn-secondary">
                <i class="material-icons text-[18px]">arrow_back</i> Kembali
            </a>
        </div>
    </div>

    {{-- Menggunakan Alpine Data untuk kalkulasi total otomatis --}}
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6" 
         x-data="{ 
            principal: 0, 
            interest: 0,
            
            // Format Rupiah untuk tampilan text
            formatRupiah(number) {
                return new Intl.NumberFormat('id-ID', { style: 'currency', currency: 'IDR', minimumFractionDigits: 0 }).format(number);
            },
            
            // Helper untuk mengambil value dari event AutoNumeric
            updateValue(type, event) {
                if(type === 'principal') this.principal = parseFloat(event.detail.newRawValue) || 0;
                if(type === 'interest') this.interest = parseFloat(event.detail.newRawValue) || 0;
            }
         }">

        {{-- Kolom Kiri: Form Pembayaran --}}
        <div class="lg:col-span-2">
            <div class="card">
                <div class="card-body">
                    <form action="{{ route('admin.loan-payments.store', $loan->loan_id) }}" method="POST">
                        @csrf

                        {{-- Tanggal Bayar --}}
                        <div class="form-group mb-5">
                            <label class="form-label label-required">Tanggal Pembayaran</label>
                            <input type="date" name="payment_date" 
                                   class="form-input @error('payment_date') is-invalid @enderror" 
                                   value="{{ old('payment_date', date('Y-m-d')) }}" required>
                            @error('payment_date') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>

                        {{-- Section Nominal --}}
                        <div class="p-5 bg-slate-50 dark:bg-slate-800/50 rounded-xl border border-slate-200 dark:border-slate-700 mb-6">
                            <h4 class="text-xs font-bold text-slate-500 uppercase mb-4">Rincian Pembayaran</h4>
                            
                            {{-- Pembayaran Pokok --}}
                            <div class="form-group mb-4">
                                <label class="form-label label-required">Pembayaran Pokok (Mengurangi Hutang)</label>
                                <div class="relative">
                                    <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                        <span class="text-slate-500 font-bold">Rp</span>
                                    </div>
                                    {{-- Event listener khusus untuk menangkap perubahan value AutoNumeric --}}
                                    <input type="text" name="principal_paid" 
                                           class="form-input pl-10 text-lg font-bold text-emerald-600 autonumeric @error('principal_paid') is-invalid @enderror"
                                           value="{{ old('principal_paid') }}"
                                           placeholder="0"
                                           required
                                           x-on:autoNumeric:rawValueModified.window="if($el.name == 'principal_paid_visual') updateValue('principal', $event)">
                                </div>
                                @error('principal_paid') <div class="invalid-feedback">{{ $message }}</div> @enderror
                                <p class="text-[10px] text-slate-400 mt-1">Nominal ini akan mengurangi sisa hutang.</p>
                            </div>

                            {{-- Pembayaran Bunga --}}
                            <div class="form-group">
                                <label class="form-label label-required">Pembayaran Bunga (Beban)</label>
                                <div class="relative">
                                    <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                        <span class="text-slate-500 font-bold">Rp</span>
                                    </div>
                                    <input type="text" name="interest_paid" 
                                           class="form-input pl-10 text-lg font-bold text-rose-600 autonumeric @error('interest_paid') is-invalid @enderror"
                                           value="{{ old('interest_paid') }}"
                                           placeholder="0"
                                           required
                                           x-on:autoNumeric:rawValueModified.window="if($el.name == 'interest_paid_visual') updateValue('interest', $event)">
                                </div>
                                @error('interest_paid') <div class="invalid-feedback">{{ $message }}</div> @enderror
                                <p class="text-[10px] text-slate-400 mt-1">Nominal ini dicatat sebagai biaya/beban bunga.</p>
                            </div>
                        </div>

                        {{-- Section Akun COA --}}
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-5 mb-5">
                            
                            {{-- Sumber Dana (Kredit) --}}
                            <div class="form-group">
                                <label class="form-label label-required">Sumber Dana (Akun Kas/Bank)</label>
                                <select name="cash_bank_account_id" class="tom-select @error('cash_bank_account_id') is-invalid @enderror">
                                    <option value="">Pilih Akun Kas/Bank...</option>
                                    @foreach($cashAccounts as $acc)
                                        <option value="{{ $acc->account_id }}" {{ old('cash_bank_account_id') == $acc->account_id ? 'selected' : '' }}>
                                            {{ $acc->account_number }} - {{ $acc->account_name }}
                                        </option>
                                    @endforeach
                                </select>
                                <p class="text-[10px] text-slate-400 mt-1">Uang keluar dari akun ini.</p>
                                @error('cash_bank_account_id') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            </div>

                            {{-- Akun Beban Bunga (Debit) --}}
                            <div class="form-group">
                                <label class="form-label">Akun Beban Bunga</label>
                                <select name="interest_expense_account_id" class="tom-select @error('interest_expense_account_id') is-invalid @enderror">
                                    <option value="">Pilih Akun Beban...</option>
                                    @foreach($expenseAccounts as $acc)
                                        <option value="{{ $acc->account_id }}" {{ old('interest_expense_account_id') == $acc->account_id ? 'selected' : '' }}>
                                            {{ $acc->account_number }} - {{ $acc->account_name }}
                                        </option>
                                    @endforeach
                                </select>
                                <p class="text-[10px] text-slate-400 mt-1">Wajib dipilih jika ada pembayaran bunga.</p>
                                @error('interest_expense_account_id') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            </div>
                        </div>

                        {{-- Keterangan --}}
                        <div class="form-group mb-6">
                            <label class="form-label">Keterangan / Catatan</label>
                            <textarea name="notes" class="form-textarea" rows="2" placeholder="Contoh: Pembayaran cicilan ke-1">{{ old('notes') }}</textarea>
                        </div>

                        {{-- Actions --}}
                        <div class="flex justify-end gap-3 pt-4 border-t border-slate-100 dark:border-slate-700">
                            <a href="{{ route('admin.loans.show', $loan->loan_id) }}" class="btn btn-secondary">Batal</a>
                            <button type="submit" class="btn btn-primary">
                                <i class="material-icons text-[18px]">payment</i>
                                Proses Pembayaran
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        {{-- Kolom Kanan: Ringkasan --}}
        <div class="lg:col-span-1 space-y-6">
            
            {{-- Card Info Hutang --}}
            <div class="card bg-gradient-to-br from-slate-800 to-[#0f172a] text-white border-none shadow-lg">
                <div class="card-body">
                    <h4 class="text-xs font-bold text-slate-400 uppercase mb-4">Posisi Hutang Saat Ini</h4>
                    
                    <div class="mb-4">
                        <p class="text-xs text-slate-400">Sisa Pokok Hutang</p>
                        <p class="text-2xl font-bold text-white">
                            Rp {{ number_format($loan->remaining_balance, 0, ',', '.') }}
                        </p>
                    </div>

                    <div class="h-px bg-slate-700 my-4"></div>

                    <div class="grid grid-cols-2 gap-4 text-xs">
                        <div>
                            <p class="text-slate-400">Total Pinjaman</p>
                            <p class="font-mono mt-1">Rp {{ number_format($loan->principal_amount, 0, ',', '.') }}</p>
                        </div>
                        <div>
                            <p class="text-slate-400">Tgl Pinjam</p>
                            <p class="font-mono mt-1">{{ $loan->loan_date->format('d/m/Y') }}</p>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Card Kalkulasi Total (Live Update) --}}
            <div class="card border-l-4 border-emerald-500">
                <div class="card-body">
                    <h4 class="text-sm font-bold text-slate-700 dark:text-slate-200 mb-3">Estimasi Total Keluar</h4>
                    
                    <div class="flex justify-between items-center text-sm mb-2">
                        <span class="text-slate-500">Pokok</span>
                        <span class="font-mono font-bold" x-text="formatRupiah(principal)">Rp 0</span>
                    </div>
                    <div class="flex justify-between items-center text-sm mb-3">
                        <span class="text-slate-500">Bunga</span>
                        <span class="font-mono font-bold text-rose-500" x-text="'+ ' + formatRupiah(interest)">+ Rp 0</span>
                    </div>
                    
                    <div class="border-t border-slate-100 dark:border-slate-700 pt-3 flex justify-between items-center">
                        <span class="font-bold text-slate-700 dark:text-slate-200">TOTAL</span>
                        <span class="text-lg font-bold text-indigo-600 dark:text-indigo-400" x-text="formatRupiah(principal + interest)">
                            Rp 0
                        </span>
                    </div>
                </div>
            </div>

        </div>
    </div>
@endsection
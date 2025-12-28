@extends('admin.layouts.app')

@section('title', 'Bayar Cicilan Pinjaman')

@section('content')
    <div class="max-w-5xl mx-auto">
        
        {{-- Header Navigation --}}
        <div class="flex items-center gap-3 mb-6">
            <a href="{{ route('admin.loans.show', $loan->loan_id) }}" 
               class="btn btn-icon btn-sm btn-secondary" 
               title="Kembali ke Detail Pinjaman">
                <i class="material-icons">arrow_back</i>
            </a>
            <div>
                <h1 class="page-title">Bayar Cicilan Pinjaman</h1>
                <p class="page-subtitle">
                    Pencatatan pembayaran untuk: 
                    <span class="font-bold text-slate-800 dark:text-white">{{ $loan->lender_name }}</span>
                </p>
            </div>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
            
            {{-- KOLOM KIRI: INFO RINGKAS UTANG --}}
            <div class="md:col-span-1">
                <div class="card p-5 bg-slate-50 dark:bg-slate-800 border-slate-200 dark:border-slate-700 sticky top-24">
                    <div class="flex items-center gap-2 mb-4 text-slate-500 dark:text-slate-400">
                        <i class="material-icons text-lg">info</i>
                        <h3 class="text-xs font-bold uppercase tracking-wider">Status Pinjaman</h3>
                    </div>
                    
                    <div class="space-y-4">
                        <div class="p-3 bg-white dark:bg-slate-700/50 rounded-lg border border-slate-200 dark:border-slate-600">
                            <span class="text-xs text-slate-500 dark:text-slate-400 block mb-1">Total Pokok Awal</span>
                            <span class="font-medium text-slate-800 dark:text-slate-200 font-mono text-lg">
                                Rp {{ number_format($loan->principal_amount, 0, ',', '.') }}
                            </span>
                        </div>

                        <div class="p-3 bg-white dark:bg-slate-700/50 rounded-lg border border-rose-200 dark:border-rose-900/50 ring-1 ring-rose-100 dark:ring-rose-900/30">
                            <span class="text-xs text-rose-500 dark:text-rose-400 block mb-1 font-bold">Sisa Utang Pokok</span>
                            <span class="font-bold text-xl text-rose-600 dark:text-rose-400 font-mono">
                                Rp {{ number_format($loan->remaining_balance, 0, ',', '.') }}
                            </span>
                        </div>
                    </div>

                    <hr class="my-5 border-slate-200 dark:border-slate-700">
                    
                    <div class="text-xs text-slate-500 dark:text-slate-400 leading-relaxed">
                        <p class="mb-2"><strong>Catatan Akuntansi:</strong></p>
                        <ul class="list-disc pl-4 space-y-1">
                            <li><strong>Pokok:</strong> Mengurangi saldo akun Kewajiban (Liabilitas).</li>
                            <li><strong>Bunga:</strong> Dicatat sebagai Beban Bunga (Laba Rugi).</li>
                            <li><strong>Total:</strong> Mengurangi saldo Kas/Bank (Aset).</li>
                        </ul>
                    </div>
                </div>
            </div>

            {{-- KOLOM KANAN: FORM PEMBAYARAN --}}
            <div class="md:col-span-2">
                <form action="{{ route('admin.loan-payments.store', $loan->loan_id) }}" method="POST">
                    @csrf
                    
                    <div class="card">
                        <div class="card-body space-y-6">
                            
                            {{-- Tanggal Bayar --}}
                            <div>
                                <label class="form-label">Tanggal Pembayaran <span class="text-red-500">*</span></label>
                                <input type="date" name="payment_date" class="form-input" 
                                       value="{{ old('payment_date', date('Y-m-d')) }}" required>
                                @error('payment_date') <p class="text-xs text-red-500 mt-1">{{ $message }}</p> @enderror
                            </div>

                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-5">
                                {{-- Input Pokok --}}
                                <div>
                                    <label class="form-label flex justify-between">
                                        <span>Bayar Pokok (Rp) <span class="text-red-500">*</span></span>
                                    </label>
                                    <div class="relative">
                                        {{-- Input AutoNumeric --}}
                                        <input type="text" class="form-input text-right autonumeric font-bold text-slate-700 dark:text-white" 
                                               name="principal_paid" 
                                               value="{{ old('principal_paid') }}" 
                                               placeholder="0" required>
                                    </div>
                                    <p class="text-[10px] text-slate-400 mt-1">Mengurangi sisa utang.</p>
                                    @error('principal_paid') <p class="text-xs text-red-500 mt-1">{{ $message }}</p> @enderror
                                </div>

                                {{-- Input Bunga --}}
                                <div>
                                    <label class="form-label">Bayar Bunga (Rp)</label>
                                    <div class="relative">
                                        <input type="text" class="form-input text-right autonumeric text-rose-600 dark:text-rose-400" 
                                               name="interest_paid" 
                                               value="{{ old('interest_paid') }}" 
                                               placeholder="0">
                                    </div>
                                    <p class="text-[10px] text-slate-400 mt-1">Masuk sebagai beban operasional.</p>
                                    @error('interest_paid') <p class="text-xs text-red-500 mt-1">{{ $message }}</p> @enderror
                                </div>
                            </div>

                            <hr class="border-dashed border-slate-200 dark:border-slate-700">

                            {{-- Akun Sumber Dana (Kredit) --}}
                            <div>
                                <label class="form-label">Sumber Dana (Kas/Bank) <span class="text-red-500">*</span></label>
                                <select name="cash_bank_account_id" class="tom-select" required>
                                    <option value="" disabled selected>Pilih Akun Kas...</option>
                                    @foreach($cashAccounts as $account)
                                        <option value="{{ $account->account_id }}" {{ old('cash_bank_account_id') == $account->account_id ? 'selected' : '' }}>
                                            {{ $account->account_number }} - {{ $account->account_name }}
                                        </option>
                                    @endforeach
                                </select>
                                @error('cash_bank_account_id') <p class="text-xs text-red-500 mt-1">{{ $message }}</p> @enderror
                            </div>

                            {{-- Akun Beban Bunga (Debit) --}}
                            <div>
                                <label class="form-label">Akun Beban Bunga</label>
                                <select name="interest_expense_account_id" class="tom-select">
                                    <option value="" disabled selected>Pilih Akun Beban...</option>
                                    @foreach($expenseAccounts as $account)
                                        <option value="{{ $account->account_id }}" {{ old('interest_expense_account_id') == $account->account_id ? 'selected' : '' }}>
                                            {{ $account->account_number }} - {{ $account->account_name }}
                                        </option>
                                    @endforeach
                                </select>
                                <p class="text-xs text-slate-400 mt-1">Wajib dipilih jika nominal bunga > 0.</p>
                                @error('interest_expense_account_id') <p class="text-xs text-red-500 mt-1">{{ $message }}</p> @enderror
                            </div>

                            {{-- Catatan --}}
                            <div>
                                <label class="form-label">Catatan / No. Referensi</label>
                                <textarea name="notes" rows="2" class="form-input" 
                                          placeholder="Contoh: Transfer via KlikBCA No. Ref 123456">{{ old('notes') }}</textarea>
                            </div>

                        </div>

                        <div class="card-footer bg-slate-50 dark:bg-slate-800/50 flex justify-end gap-3">
                            <a href="{{ route('admin.loans.show', $loan->loan_id) }}" class="btn btn-secondary">Batal</a>
                            <button type="submit" class="btn btn-primary">
                                <i class="material-icons text-sm">save</i>
                                Simpan Pembayaran
                            </button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endsection
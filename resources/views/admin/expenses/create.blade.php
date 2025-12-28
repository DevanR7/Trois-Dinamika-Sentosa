@extends('admin.layouts.app')

@section('title', 'Catat Pengeluaran')

@section('content')

    <div class="max-w-3xl mx-auto">
        
        {{-- Navigation --}}
        <div class="flex items-center justify-between mb-6">
            <div>
                <h1 class="page-title">Catat Pengeluaran</h1>
                <p class="page-subtitle">Input biaya operasional baru.</p>
            </div>
            <a href="{{ route('admin.expenses.index') }}" class="btn btn-secondary">
                <i class="material-icons text-sm mr-1">arrow_back</i> Kembali
            </a>
        </div>

        <form action="{{ route('admin.expenses.store') }}" method="POST">
            @csrf

            <div class="card">
                <div class="card-header">
                    <h3 class="card-header-title">Formulir Biaya</h3>
                </div>
                <div class="card-body space-y-6">

                    {{-- Tanggal & Jumlah --}}
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div>
                            <label class="form-label label-required">Tanggal</label>
                            <input type="date" name="expense_date" class="form-input" 
                                   value="{{ old('expense_date', date('Y-m-d')) }}" required>
                            @error('expense_date') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>
                        <div>
                            <label class="form-label label-required">Jumlah (Rp)</label>
                            <div class="input-group">
                                <span class="input-group-text">Rp</span>
                                <input type="text" name="amount" 
                                       class="form-input autonumeric text-right font-bold text-rose-600" 
                                       placeholder="0" value="{{ old('amount') }}" required>
                            </div>
                            @error('amount') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>
                    </div>

                    {{-- Akun & Sumber Dana --}}
                    <div class="p-4 bg-slate-50 dark:bg-slate-800/50 rounded-xl border border-slate-200 dark:border-slate-700 grid grid-cols-1 md:grid-cols-2 gap-6">
                        
                        {{-- Akun Biaya (DEBIT) --}}
                        <div>
                            <label class="form-label label-required text-indigo-600 dark:text-indigo-400">
                                <i class="material-icons text-[10px] align-middle mr-1">trending_up</i>
                                Untuk Biaya Apa? (Debit)
                            </label>
                            <select name="chart_of_account_id" class="tom-select" required>
                                <option value="">Pilih Akun Beban...</option>
                                @foreach($expenseAccounts as $coa)
                                    <option value="{{ $coa->account_id }}" {{ old('chart_of_account_id') == $coa->account_id ? 'selected' : '' }}>
                                        {{ $coa->account_number }} - {{ $coa->account_name }}
                                    </option>
                                @endforeach
                            </select>
                            <div class="form-hint">Pilih kategori beban yang sesuai dari COA.</div>
                            @error('chart_of_account_id') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>

                        {{-- Sumber Dana (KREDIT) --}}
                        <div>
                            <label class="form-label label-required text-rose-600 dark:text-rose-400">
                                <i class="material-icons text-[10px] align-middle mr-1">account_balance_wallet</i>
                                Bayar Pakai Apa? (Kredit)
                            </label>
                            <select name="cash_bank_account_id" class="tom-select" required>
                                <option value="">Pilih Sumber Dana...</option>
                                @foreach($cashAccounts as $coa)
                                    <option value="{{ $coa->account_id }}" {{ old('cash_bank_account_id') == $coa->account_id ? 'selected' : '' }}>
                                        {{ $coa->account_number }} - {{ $coa->account_name }}
                                    </option>
                                @endforeach
                            </select>
                            <div class="form-hint">Kas atau Bank yang digunakan untuk membayar.</div>
                            @error('cash_bank_account_id') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>

                    </div>

                    {{-- Deskripsi --}}
                    <div>
                        <label class="form-label label-required">Keterangan / Deskripsi</label>
                        <textarea name="description" class="form-textarea" rows="3" 
                                  placeholder="Contoh: Pembayaran listrik bulan Januari, Pembelian ATK, dll." required>{{ old('description') }}</textarea>
                        @error('description') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>

                </div>
            </div>

            {{-- Submit --}}
            <div class="mt-8 pt-6 border-t border-slate-200 dark:border-slate-700 flex justify-end">
                <button type="submit" class="btn btn-primary btn-lg">
                    <i class="material-icons text-sm mr-2">save</i> Simpan Pengeluaran
                </button>
            </div>

        </form>
    </div>

@endsection
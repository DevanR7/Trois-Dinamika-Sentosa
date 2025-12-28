@extends('admin.layouts.app')

@section('title', 'Catat Transaksi Ekuitas')

@section('content')

    <div class="max-w-3xl mx-auto">
        
        {{-- Navigation --}}
        <div class="flex items-center justify-between mb-6">
            <div>
                <h1 class="page-title">Catat Transaksi Ekuitas</h1>
                <p class="page-subtitle">Input setoran modal atau penarikan prive.</p>
            </div>
            <a href="{{ route('admin.equity-transactions.index') }}" class="btn btn-secondary">
                <i class="material-icons text-sm mr-1">arrow_back</i> Kembali
            </a>
        </div>

        <form action="{{ route('admin.equity-transactions.store') }}" method="POST">
            @csrf

            <div class="space-y-6">
                
                {{-- CARD UTAMA --}}
                <div class="card">
                    <div class="card-header">
                        <h3 class="card-header-title">Formulir Transaksi</h3>
                    </div>
                    <div class="card-body space-y-6">

                        {{-- Tanggal & Jumlah --}}
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div>
                                <label class="form-label label-required">Tanggal Transaksi</label>
                                <input type="date" name="transaction_date" class="form-input" 
                                       value="{{ old('transaction_date', date('Y-m-d')) }}" required>
                                @error('transaction_date') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            </div>
                            <div>
                                <label class="form-label label-required">Nominal (Rp)</label>
                                <div class="input-group">
                                    <span class="input-group-text">Rp</span>
                                    <input type="text" name="amount" 
                                           class="form-input autonumeric text-right font-bold text-slate-700" 
                                           value="{{ old('amount') }}" required>
                                </div>
                                @error('amount') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            </div>
                        </div>

                        <div class="border-t border-slate-100 dark:border-slate-700"></div>

                        {{-- Konfigurasi Akun --}}
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            
                            {{-- Akun Ekuitas --}}
                            <div>
                                <label class="form-label label-required text-indigo-600 dark:text-indigo-400">
                                    Akun Ekuitas (Modal/Prive)
                                </label>
                                <select name="equity_account_id" class="tom-select" required>
                                    <option value="">Pilih Akun Ekuitas...</option>
                                    @foreach($equityAccounts as $coa)
                                        <option value="{{ $coa->account_id }}" {{ old('equity_account_id') == $coa->account_id ? 'selected' : '' }}>
                                            {{ $coa->account_number }} - {{ $coa->account_name }} ({{ $coa->normal_balance }})
                                        </option>
                                    @endforeach
                                </select>
                                <div class="form-hint mt-2 p-2 bg-slate-50 dark:bg-slate-800 rounded-lg text-xs text-slate-500 leading-relaxed">
                                    <span class="font-bold text-slate-600 dark:text-slate-300">Logika Sistem:</span><br>
                                    • Jika Akun <b>Kredit</b> (Modal) dipilih -> Dianggap <b>Setoran Modal</b>.<br>
                                    • Jika Akun <b>Debit</b> (Prive) dipilih -> Dianggap <b>Penarikan</b>.
                                </div>
                                @error('equity_account_id') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            </div>

                            {{-- Akun Kas/Bank --}}
                            <div>
                                <label class="form-label label-required text-emerald-600 dark:text-emerald-400">
                                    Akun Kas / Bank (Sumber/Tujuan)
                                </label>
                                <select name="cash_bank_account_id" class="tom-select" required>
                                    <option value="">Pilih Akun Kas/Bank...</option>
                                    @foreach($cashAccounts as $coa)
                                        <option value="{{ $coa->account_id }}" {{ old('cash_bank_account_id') == $coa->account_id ? 'selected' : '' }}>
                                            {{ $coa->account_number }} - {{ $coa->account_name }}
                                        </option>
                                    @endforeach
                                </select>
                                <div class="form-hint mt-1">
                                    Akun yang akan bertambah (Setoran) atau berkurang (Prive).
                                </div>
                                @error('cash_bank_account_id') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            </div>

                        </div>

                        {{-- Deskripsi --}}
                        <div>
                            <label class="form-label label-required">Keterangan</label>
                            <textarea name="description" class="form-textarea" rows="3" 
                                      placeholder="Contoh: Setoran modal awal Tuan A, atau Pengambilan pribadi bulan Juni..." required>{{ old('description') }}</textarea>
                            @error('description') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>

                    </div>
                </div>

            </div>

            {{-- Submit --}}
            <div class="mt-8 pt-6 border-t border-slate-200 dark:border-slate-700 flex justify-end">
                <button type="submit" class="btn btn-primary btn-lg">
                    <i class="material-icons text-sm mr-2">save</i> Simpan Transaksi
                </button>
            </div>

        </form>
    </div>

@endsection
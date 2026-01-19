@extends('admin.layouts.app')

@section('title', 'Catat Pinjaman Baru')

@section('content')
    {{-- Header --}}
    <div class="page-header">
        <div>
            <h1 class="page-title">Catat Pinjaman Baru</h1>
            <p class="page-subtitle">Formulir penerimaan dana pinjaman (Hutang)</p>
        </div>
        <div>
            <a href="{{ route('admin.loans.index') }}" class="btn btn-secondary">
                <i class="material-icons text-[18px]">arrow_back</i> Kembali
            </a>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        {{-- Form Section --}}
        <div class="lg:col-span-2">
            <div class="card">
                <div class="card-body">
                    <form action="{{ route('admin.loans.store') }}" method="POST">
                        @csrf

                        {{-- Baris 1: Pemberi & Tanggal --}}
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-5 mb-5">
                            <div class="form-group">
                                <label class="form-label label-required">Pemberi Pinjaman (Lender)</label>
                                <input type="text" name="lender_name" class="form-input @error('lender_name') is-invalid @enderror" 
                                       value="{{ old('lender_name') }}" placeholder="Contoh: Bank Mandiri, Tuan A..." required>
                                @error('lender_name') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            </div>

                            <div class="form-group">
                                <label class="form-label label-required">Tanggal Penerimaan</label>
                                <input type="date" name="loan_date" class="form-input @error('loan_date') is-invalid @enderror" 
                                       value="{{ old('loan_date', date('Y-m-d')) }}" required>
                                @error('loan_date') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            </div>
                        </div>

                        {{-- Baris 2: Pokok Pinjaman --}}
                        <div class="form-group mb-5">
                            <label class="form-label label-required">Jumlah Pokok Pinjaman (Principal)</label>
                            <div class="relative">
                                <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                    <span class="text-slate-500 font-bold">Rp</span>
                                </div>
                                {{-- AutoNumeric Class: 'autonumeric' --}}
                                <input type="text" name="principal_amount" 
                                       class="form-input pl-10 text-lg font-bold text-slate-800 autonumeric @error('principal_amount') is-invalid @enderror" 
                                       value="{{ old('principal_amount') }}" required placeholder="0">
                            </div>
                            @error('principal_amount') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>

                        {{-- Baris 3: Akun Akuntansi (PENTING) --}}
                        <div class="p-4 bg-slate-50 dark:bg-slate-800/50 rounded-xl border border-slate-200 dark:border-slate-700 mb-5">
                            <h4 class="text-xs font-bold text-slate-500 uppercase mb-3">Konfigurasi Jurnal Otomatis</h4>
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                                {{-- Akun Kas/Bank (Debit) --}}
                                <div class="form-group">
                                    <label class="form-label label-required">Masuk ke Akun (Debit)</label>
                                    <select name="cash_bank_account_id" class="tom-select @error('cash_bank_account_id') is-invalid @enderror">
                                        <option value="">Pilih Akun Kas/Bank...</option>
                                        @foreach($cashAccounts as $acc)
                                            <option value="{{ $acc->account_id }}" {{ old('cash_bank_account_id') == $acc->account_id ? 'selected' : '' }}>
                                                {{ $acc->account_number }} - {{ $acc->account_name }}
                                            </option>
                                        @endforeach
                                    </select>
                                    <p class="text-[10px] text-slate-400 mt-1">Akun Aset tempat uang diterima.</p>
                                    @error('cash_bank_account_id') <div class="invalid-feedback">{{ $message }}</div> @enderror
                                </div>

                                {{-- Akun Hutang (Kredit) --}}
                                <div class="form-group">
                                    <label class="form-label label-required">Dicatat sbg Hutang (Kredit)</label>
                                    <select name="loan_account_id" class="tom-select @error('loan_account_id') is-invalid @enderror">
                                        <option value="">Pilih Akun Liabilitas...</option>
                                        @foreach($loanAccounts as $acc)
                                            <option value="{{ $acc->account_id }}" {{ old('loan_account_id') == $acc->account_id ? 'selected' : '' }}>
                                                {{ $acc->account_number }} - {{ $acc->account_name }}
                                            </option>
                                        @endforeach
                                    </select>
                                    <p class="text-[10px] text-slate-400 mt-1">Akun Kewajiban Jangka Pendek/Panjang.</p>
                                    @error('loan_account_id') <div class="invalid-feedback">{{ $message }}</div> @enderror
                                </div>
                            </div>
                        </div>

                        {{-- Deskripsi --}}
                        <div class="form-group mb-6">
                            <label class="form-label">Keterangan / Catatan</label>
                            <textarea name="description" class="form-textarea" rows="3" placeholder="Contoh: Pinjaman modal kerja jangka waktu 1 tahun">{{ old('description') }}</textarea>
                        </div>

                        {{-- Actions --}}
                        <div class="flex justify-end gap-3">
                            <button type="reset" class="btn btn-secondary">Reset</button>
                            <button type="submit" class="btn btn-primary">
                                <i class="material-icons text-[18px]">save</i> Simpan Pinjaman
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        {{-- Info Panel --}}
        <div class="lg:col-span-1">
            <div class="card bg-indigo-50 border-indigo-100 dark:bg-indigo-900/20 dark:border-indigo-800">
                <div class="card-body">
                    <h3 class="text-sm font-bold text-indigo-800 dark:text-indigo-300 mb-2 flex items-center gap-2">
                        <i class="material-icons text-[18px]">info</i> Informasi Akuntansi
                    </h3>
                    <p class="text-xs text-indigo-700 dark:text-indigo-400 leading-relaxed">
                        Sistem akan otomatis membuat jurnal umum:
                    </p>
                    <ul class="list-disc list-inside text-xs text-indigo-700 dark:text-indigo-400 mt-2 space-y-1">
                        <li><b>Debit:</b> Akun Kas/Bank (Aset bertambah)</li>
                        <li><b>Kredit:</b> Akun Hutang (Liabilitas bertambah)</li>
                    </ul>
                    <p class="text-xs text-indigo-700 dark:text-indigo-400 mt-3 leading-relaxed">
                        Pastikan Anda memilih akun Liabilitas yang sesuai (misal: Hutang Bank atau Hutang Pihak Ketiga).
                    </p>
                </div>
            </div>
        </div>
    </div>
@endsection
@extends('admin.layouts.app')

@section('title', 'Edit Pinjaman')

@section('content')
    <div class="page-header">
        <div>
            <h1 class="page-title">Edit Pinjaman</h1>
            <p class="page-subtitle">Koreksi data pinjaman: {{ $loan->lender_name }}</p>
        </div>
        <div>
            <a href="{{ route('admin.loans.index') }}" class="btn btn-secondary">
                <i class="material-icons text-[18px]">arrow_back</i> Kembali
            </a>
        </div>
    </div>

    <div class="max-w-3xl">
        <div class="card">
            <div class="card-body">
                <form action="{{ route('admin.loans.update', $loan->loan_id) }}" method="POST">
                    @csrf
                    @method('PUT')

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-5 mb-5">
                        <div class="form-group">
                            <label class="form-label label-required">Pemberi Pinjaman</label>
                            <input type="text" name="lender_name" class="form-input @error('lender_name') is-invalid @enderror" 
                                   value="{{ old('lender_name', $loan->lender_name) }}" required>
                            @error('lender_name') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>

                        <div class="form-group">
                            <label class="form-label label-required">Tanggal</label>
                            <input type="date" name="loan_date" class="form-input @error('loan_date') is-invalid @enderror" 
                                   value="{{ old('loan_date', $loan->loan_date->format('Y-m-d')) }}" required>
                            @error('loan_date') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>
                    </div>

                    <div class="form-group mb-5">
                        <label class="form-label label-required">Jumlah Pokok Pinjaman</label>
                        <div class="relative">
                            <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                <span class="text-slate-500 font-bold">Rp</span>
                            </div>
                            <input type="text" name="principal_amount" 
                                   class="form-input pl-10 text-lg font-bold autonumeric @error('principal_amount') is-invalid @enderror" 
                                   value="{{ old('principal_amount', $loan->principal_amount) }}" required>
                        </div>
                        @error('principal_amount') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>

                    <div class="p-4 bg-slate-50 dark:bg-slate-800/50 rounded-xl border border-slate-200 dark:border-slate-700 mb-5">
                        <h4 class="text-xs font-bold text-slate-500 uppercase mb-3">Konfigurasi Akun (COA)</h4>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                            <div class="form-group">
                                <label class="form-label label-required">Akun Kas/Bank (Debit)</label>
                                <select name="cash_bank_account_id" class="tom-select">
                                    @foreach($cashAccounts as $acc)
                                        <option value="{{ $acc->account_id }}" {{ old('cash_bank_account_id', $loan->cash_bank_account_id) == $acc->account_id ? 'selected' : '' }}>
                                            {{ $acc->account_number }} - {{ $acc->account_name }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>

                            <div class="form-group">
                                <label class="form-label label-required">Akun Hutang (Kredit)</label>
                                <select name="loan_account_id" class="tom-select">
                                    @foreach($loanAccounts as $acc)
                                        <option value="{{ $acc->account_id }}" {{ old('loan_account_id', $loan->loan_account_id) == $acc->account_id ? 'selected' : '' }}>
                                            {{ $acc->account_number }} - {{ $acc->account_name }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                    </div>

                    <div class="form-group mb-6">
                        <label class="form-label">Keterangan</label>
                        <textarea name="description" class="form-textarea" rows="3">{{ old('description', $loan->description) }}</textarea>
                    </div>

                    <div class="flex justify-end gap-3">
                        <a href="{{ route('admin.loans.index') }}" class="btn btn-secondary">Batal</a>
                        <button type="submit" class="btn btn-primary">
                            <i class="material-icons text-[18px]">save</i> Simpan Perubahan
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endsection
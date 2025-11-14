@extends('layouts.app')

@section('content')
<div class="container py-4">
    <div class="row justify-content-center">
        <div class="col-md-8">
            <div class="card shadow-sm">
                <div class="card-header">
                    <h2 class="fw-bold mb-0">Mulai Rekonsiliasi Bank Baru</h2>
                </div>
                <div class="card-body">
                    <form action="{{ route('bank-reconciliations.store') }}" method="POST">
                        @csrf
                        <div class="row g-3">
                            <div class="col-12 mb-3">
                                <label for="company_bank_account_id" class="form-label">Pilih Akun Bank (dari COA) <span class="text-danger">*</span></label>
                                <select class="form-select @error('company_bank_account_id') is-invalid @enderror" id="company_bank_account_id" name="company_bank_account_id" required>
                                    <option value="" disabled selected>-- Pilih Akun Kas/Bank --</option>
                                    @forelse ($bankAccounts as $account)
                                        <option value="{{ $account->company_bank_account_id }}" {{ old('company_bank_account_id') == $account->company_bank_account_id ? 'selected' : '' }}>
                                            {{ $account->account->account_number ?? '' }} - {{ $account->account->account_name ?? $account->bank_name }}
                                        </option>
                                    @empty
                                        <option value="" disabled>Tidak ada Akun Bank yang terhubung ke COA. Silakan atur di menu "Akun Bank Perusahaan".</option>
                                    @endforelse
                                </select>
                                @error('company_bank_account_id')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                            
                            <div class="col-md-6 mb-3">
                                <label for="statement_date" class="form-label">Tanggal Akhir Rekening Koran <span class="text-danger">*</span></label>
                                <input type="date" class="form-control @error('statement_date') is-invalid @enderror" id="statement_date" name="statement_date" value="{{ old('statement_date', now()->endOfMonth()->toDateString()) }}" required>
                                @error('statement_date')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="col-md-6 mb-3">
                                <label for="statement_balance" class="form-label">Saldo Akhir (di Bank) <span class="text-danger">*</span></label>
                                <input type="number" step="0.01" class="form-control @error('statement_balance') is-invalid @enderror" id="statement_balance" name="statement_balance" value="{{ old('statement_balance') }}" placeholder="Contoh: 150000000" required>
                                @error('statement_balance')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>
                        <div class="d-flex justify-content-end mt-4">
                            <a href="{{ route('bank-reconciliations.index') }}" class="btn btn-outline-secondary me-2">Batal</a>
                            <button type="submit" class="btn btn-dark" {{ $bankAccounts->isEmpty() ? 'disabled' : '' }}>
                                <i class="bi bi-play-fill"></i> Mulai Proses Rekonsiliasi
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
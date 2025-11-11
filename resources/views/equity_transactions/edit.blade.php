@extends('layouts.app')

@section('content')
<div class="container py-4">
    <div class="row justify-content-center">
        <div class="col-md-8">
            <div class="card shadow-sm">
                <div class="card-header">
                    <h2 class="fw-bold mb-0">Edit Transaksi Modal</h2>
                </div>
                <div class="card-body">
                    <form action="{{ route('equity-transactions.update', $transaction) }}" method="POST">
                        @csrf
                        @method('PUT')
                        
                        <div class="row g-3">
                            <div class="col-md-6 mb-3">
                                <label for="transaction_date" class="form-label">Tanggal Transaksi <span class="text-danger">*</span></label>
                                <input type="date" class="form-control @error('transaction_date') is-invalid @enderror" id="transaction_date" name="transaction_date" value="{{ old('transaction_date', $transaction->transaction_date->toDateString()) }}" required>
                                @error('transaction_date')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="amount" class="form-label">Jumlah (Rp) <span class="text-danger">*</span></label>
                                <input type="number" class="form-control @error('amount') is-invalid @enderror" id="amount" name="amount" value="{{ old('amount', $transaction->amount) }}" required>
                                @error('amount')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                            
                            {{-- ✅ FIELD TIPE (DIGANTI) --}}
                            <div class="col-md-6 mb-3">
                                <label for="equity_account_id" class="form-label">Akun Modal <span class="text-danger">*</span></label>
                                <select class="form-select @error('equity_account_id') is-invalid @enderror" id="equity_account_id" name="equity_account_id" required>
                                    <option value="" disabled>-- Pilih Akun Modal/Prive --</option>
                                    @foreach ($equityAccounts as $account)
                                        <option value="{{ $account->account_id }}" {{ old('equity_account_id', $transaction->equity_account_id) == $account->account_id ? 'selected' : '' }}>
                                            {{ $account->account_number }} - {{ $account->account_name }} (Saldo: {{ $account->normal_balance }})
                                        </option>
                                    @endforeach
                                </select>
                                @error('equity_account_id')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            {{-- ✅ FIELD SUMBER DANA (BARU) --}}
                            <div class="col-md-6 mb-3">
                                <label for="cash_bank_account_id" class="form-label">Akun Kas/Bank <span class="text-danger">*</span></label>
                                <select class="form-select @error('cash_bank_account_id') is-invalid @enderror" id="cash_bank_account_id" name="cash_bank_account_id" required>
                                    <option value="" disabled>-- Pilih Akun Kas/Bank --</option>
                                    @foreach ($cashAccounts as $account)
                                        <option value="{{ $account->account_id }}" {{ old('cash_bank_account_id', $transaction->cash_bank_account_id) == $account->account_id ? 'selected' : '' }}>
                                            {{ $account->account_number }} - {{ $account->account_name }}
                                        </option>
                                    @endforeach
                                </select>
                                @error('cash_bank_account_id')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="col-12 mb-3">
                                <label for="description" class="form-label">Deskripsi <span class="text-danger">*</span></label>
                                <textarea class="form-control @error('description') is-invalid @enderror" id="description" name="description" rows="3" required>{{ old('description', $transaction->description) }}</textarea>
                                @error('description')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>
                        <div class="d-flex justify-content-end mt-4">
                            <a href="{{ route('equity-transactions.index') }}" class="btn btn-outline-secondary me-2">Batal</a>
                            <button type="submit" class="btn btn-dark">
                                <i class="bi bi-save-fill"></i> Simpan Perubahan
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
@extends('layouts.app')

@section('content')
<div class="container py-4">
    <div class="row justify-content-center">
        <div class="col-md-8">
            <div class="card shadow-sm">
                <div class="card-header">
                    <h2 class="fw-bold mb-0">Edit Akun Bank</h2>
                </div>
                <div class="card-body">
                    <form action="{{ route('company-bank-accounts.update', $account) }}" method="POST">
                        @csrf
                        @method('PUT')
                        <div class="row g-3">
                            <div class="col-md-6 mb-3">
                                <label for="bank_name" class="form-label">Nama Bank <span class="text-danger">*</span></label>
                                <input type="text" class="form-control @error('bank_name') is-invalid @enderror" id="bank_name" name="bank_name" value="{{ old('bank_name', $account->bank_name) }}" required>
                                @error('bank_name')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="account_name" class="form-label">Atas Nama <span class="text-danger">*</span></label>
                                <input type="text" class="form-control @error('account_name') is-invalid @enderror" id="account_name" name="account_name" value="{{ old('account_name', $account->account_name) }}" required>
                                @error('account_name')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="account_number" class="form-label">Nomor Rekening</label>
                                <input type="text" class="form-control @error('account_number') is-invalid @enderror" id="account_number" name="account_number" value="{{ old('account_number', $account->account_number) }}">
                                @error('account_number')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                            
                            {{-- ✅ DROPDOWN COA (BARU) --}}
                            <div class="col-md-6 mb-3">
                                <label for="chart_of_account_id" class="form-label">Hubungkan ke Akun (COA) <span class="text-danger">*</span></label>
                                <select class="form-select @error('chart_of_account_id') is-invalid @enderror" id="chart_of_account_id" name="chart_of_account_id" required>
                                    <option value="" disabled>-- Pilih Akun Aset --</option>
                                    @foreach ($assetAccounts as $asset)
                                        <option value="{{ $asset->account_id }}" {{ old('chart_of_account_id', $account->chart_of_account_id) == $asset->account_id ? 'selected' : '' }}>
                                            {{ $asset->account_number }} - {{ $asset->account_name }}
                                        </option>
                                    @endforeach
                                </select>
                                <div class="form-text">Hubungkan ke akun Kas/Bank di Jurnal Umum.</div>
                                @error('chart_of_account_id')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="col-12 mb-3">
                                <div class="form-check form-switch">
                                    <input class="form-check-input" type="checkbox" role="switch" id="is_active" name="is_active" value="1" {{ old('is_active', $account->is_active) ? 'checked' : '' }}>
                                    <label class="form-check-label" for="is_active">Aktifkan Akun Ini</label>
                                </div>
                            </div>
                        </div>
                        
                        <div class="d-flex justify-content-end mt-4">
                            <a href="{{ route('company-bank-accounts.index') }}" class="btn btn-outline-secondary me-2">Batal</a>
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
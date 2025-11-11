@extends('layouts.app')

@section('content')
<div class="container py-4">
    <div class="row justify-content-center">
        <div class="col-md-8">
            <div class="card shadow-sm">
                <div class="card-header">
                    <h2 class="fw-bold mb-0">Buat Akun Baru (COA)</h2>
                </div>
                <div class="card-body">
                    <form action="{{ route('chart-of-accounts.store') }}" method="POST">
                        @csrf
                        <div class="row g-3">
                            <div class="col-md-6 mb-3">
                                <label for="account_number" class="form-label">Nomor Akun <span class="text-danger">*</span></label>
                                <input type="text" class="form-control @error('account_number') is-invalid @enderror" id="account_number" name="account_number" value="{{ old('account_number') }}" required>
                                @error('account_number')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="account_name" class="form-label">Nama Akun <span class="text-danger">*</span></label>
                                <input type="text" class="form-control @error('account_name') is-invalid @enderror" id="account_name" name="account_name" value="{{ old('account_name') }}" required>
                                @error('account_name')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="col-md-6 mb-3">
                                <label for="account_type" class="form-label">Tipe Akun <span class="text-danger">*</span></label>
                                <select class="form-select @error('account_type') is-invalid @enderror" id="account_type" name="account_type" required>
                                    <option value="" disabled selected>-- Pilih Tipe --</option>
                                    @foreach ($accountTypes as $type)
                                        <option value="{{ $type }}" {{ old('account_type') == $type ? 'selected' : '' }}>{{ $type }}</option>
                                    @endforeach
                                </select>
                                @error('account_type')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                            
                            <div class="col-md-6 mb-3">
                                <label for="normal_balance" class="form-label">Saldo Normal <span class="text-danger">*</span></label>
                                <select class="form-select @error('normal_balance') is-invalid @enderror" id="normal_balance" name="normal_balance" required>
                                    <option value="" disabled selected>-- Pilih Saldo --</option>
                                    @foreach ($normalBalances as $balance)
                                        <option value="{{ $balance }}" {{ old('normal_balance') == $balance ? 'selected' : '' }}>{{ $balance }}</option>
                                    @endforeach
                                </select>
                                @error('normal_balance')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                            
                            <div class="col-12 mb-3">
                                <label for="parent_account_id" class="form-label">Akun Induk (Opsional)</label>
                                <select class="form-select @error('parent_account_id') is-invalid @enderror" id="parent_account_id" name="parent_account_id">
                                    <option value="">-- Tidak Ada Induk (Jadikan Akun Parent) --</option>
                                    @foreach ($parentAccounts as $parent)
                                        <option value="{{ $parent->account_id }}" {{ old('parent_account_id') == $parent->account_id ? 'selected' : '' }}>
                                            {{ $parent->account_number }} - {{ $parent->account_name }}
                                        </option>
                                    @endforeach
                                </select>
                                <div class="form-text">Pilih ini jika Anda ingin mengelompokkan akun (e.g., Kas BCA di bawah Kas & Bank).</div>
                                @error('parent_account_id')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="col-12 mb-3">
                                <label for="description" class="form-label">Deskripsi (Opsional)</label>
                                <textarea class="form-control" id="description" name="description" rows="2">{{ old('description') }}</textarea>
                            </div>
                            
                            <div class="col-12 mb-3">
                                <div class="form-check form-switch">
                                    <input class="form-check-input" type="checkbox" role="switch" id="is_active" name="is_active" value="1" {{ old('is_active', true) ? 'checked' : '' }}>
                                    <label class="form-check-label" for="is_active">Aktifkan Akun Ini</label>
                                </div>
                            </div>
                        </div>

                        <div class="d-flex justify-content-end mt-4">
                            <a href="{{ route('chart-of-accounts.index') }}" class="btn btn-outline-secondary me-2">Batal</a>
                            <button type="submit" class="btn btn-dark">
                                <i class="bi bi-save-fill"></i> Simpan Akun
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
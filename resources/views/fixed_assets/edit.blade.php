@extends('layouts.app')

@push('styles')
{{-- Select2 untuk search dropdown akun --}}
<link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/select2-bootstrap-5-theme@1.3.0/dist/select2-bootstrap-5-theme.min.css" />
<style>
    .select2-container .select2-selection--single { height: 38px !important; }
    .select2-container--bootstrap-5 .select2-selection--single .select2-selection__rendered { line-height: 2.4 !important; }
    .select2-container--bootstrap-5 .select2-selection--single .select2-selection__arrow { top: 0.45rem !important; }
</style>
@endpush

@section('content')
<div class="container py-4">
    <div class="row justify-content-center">
        <div class="col-md-10"> {{-- Lebarkan kolom --}}
            <div class="card shadow-sm">
                <div class="card-header">
                    <h2 class="fw-bold mb-0">Edit Aset Tetap</h2>
                </div>
                <div class="card-body">
                    
                    @if ($fixedAsset->depreciations()->exists())
                    <div class="alert alert-danger">
                        <i class="bi bi-exclamation-triangle-fill"></i> <strong>Aset Terkunci.</strong>
                        Aset ini sudah memiliki riwayat penyusutan dan tidak dapat diubah lagi. Hapus riwayat penyusutan terlebih dahulu untuk mengubah data ini.
                    </div>
                    @endif

                    <form action="{{ route('fixed-assets.update', $fixedAsset) }}" method="POST">
                        @csrf
                        @method('PUT')
                        
                        {{-- Kunci semua field jika sudah ada riwayat penyusutan --}}
                        <fieldset {{ $fixedAsset->depreciations()->exists() ? 'disabled' : '' }}>
                            <h5 class="fw-semibold text-primary">1. Informasi Aset & Pembelian</h5>
                            <div class="row g-3 mb-3">
                                <div class="col-12 mb-3">
                                    <label for="asset_name" class="form-label">Nama Aset <span class="text-danger">*</span></label>
                                    <input type="text" class="form-control @error('asset_name') is-invalid @enderror" id="asset_name" name="asset_name" value="{{ old('asset_name', $fixedAsset->asset_name) }}" required>
                                    @error('asset_name') <div class="invalid-feedback">{{ $message }}</div> @enderror
                                </div>
                                <div class="col-md-4 mb-3">
                                    <label for="purchase_date" class="form-label">Tanggal Beli <span class="text-danger">*</span></label>
                                    <input type="date" class="form-control @error('purchase_date') is-invalid @enderror" id="purchase_date" name="purchase_date" value="{{ old('purchase_date', $fixedAsset->purchase_date->format('Y-m-d')) }}" required>
                                    @error('purchase_date') <div class="invalid-feedback">{{ $message }}</div> @enderror
                                </div>
                                <div class="col-md-4 mb-3">
                                    <label for="purchase_cost" class="form-label">Harga Beli (Rp) <span class="text-danger">*</span></label>
                                    <input type="number" class="form-control @error('purchase_cost') is-invalid @enderror" id="purchase_cost" name="purchase_cost" value="{{ old('purchase_cost', $fixedAsset->purchase_cost) }}" required>
                                    @error('purchase_cost') <div class="invalid-feedback">{{ $message }}</div> @enderror
                                </div>
                                <div class="col-md-4 mb-3">
                                    <label for="cash_bank_account_id" class="form-label">Sumber Dana (Kredit) <span class="text-danger">*</span></label>
                                    <select class="form-select account-select @error('cash_bank_account_id') is-invalid @enderror" id="cash_bank_account_id" name="cash_bank_account_id" required>
                                        <option value="" disabled>-- Pilih Akun Kas/Bank --</option>
                                        @foreach ($cashAccounts as $account)
                                            <option value="{{ $account->account_id }}" {{ old('cash_bank_account_id', $fixedAsset->cash_bank_account_id) == $account->account_id ? 'selected' : '' }}>
                                                {{ $account->account_number }} - {{ $account->account_name }}
                                            </option>
                                        @endforeach
                                    </select>
                                    @error('cash_bank_account_id') <div class="invalid-feedback">{{ $message }}</div> @enderror
                                </div>
                                <div class="col-12 mb-3">
                                    <label for="description" class="form-label">Deskripsi</label>
                                    <textarea class="form-control @error('description') is-invalid @enderror" id="description" name="description" rows="2">{{ old('description', $fixedAsset->description) }}</textarea>
                                    @error('description') <div class="invalid-feedback">{{ $message }}</div> @enderror
                                </div>
                            </div>

                            <hr>
                            <h5 class="fw-semibold text-primary">2. Informasi Akuntansi & Penyusutan</h5>
                            <div class="row g-3 mb-3">
                                <div class="col-md-6 mb-3">
                                    <label for="fixed_asset_account_id" class="form-label">Akun Aset (Debit) <span class="text-danger">*</span></label>
                                    <select class="form-select account-select @error('fixed_asset_account_id') is-invalid @enderror" id="fixed_asset_account_id" name="fixed_asset_account_id" required>
                                        <option value="" disabled>-- Pilih Akun Aset --</option>
                                        @foreach ($assetAccounts as $account)
                                            <option value="{{ $account->account_id }}" {{ old('fixed_asset_account_id', $fixedAsset->fixed_asset_account_id) == $account->account_id ? 'selected' : '' }}>
                                                {{ $account->account_number }} - {{ $account->account_name }}
                                            </option>
                                        @endforeach
                                    </select>
                                    @error('fixed_asset_account_id') <div class="invalid-feedback">{{ $message }}</div> @enderror
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label for="depreciation_expense_account_id" class="form-label">Akun Beban Penyusutan (Debit) <span class="text-danger">*</span></label>
                                    <select class="form-select account-select @error('depreciation_expense_account_id') is-invalid @enderror" id="depreciation_expense_account_id" name="depreciation_expense_account_id" required>
                                        <option value="" disabled>-- Pilih Akun Beban --</option>
                                        @foreach ($expenseAccounts as $account)
                                            <option value="{{ $account->account_id }}" {{ old('depreciation_expense_account_id', $fixedAsset->depreciation_expense_account_id) == $account->account_id ? 'selected' : '' }}>
                                                {{ $account->account_number }} - {{ $account->account_name }}
                                            </option>
                                        @endforeach
                                    </select>
                                    @error('depreciation_expense_account_id') <div class="invalid-feedback">{{ $message }}</div> @enderror
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label for="accumulated_depreciation_account_id" class="form-label">Akun Akumulasi Penyusutan (Kredit) <span class="text-danger">*</span></label>
                                    <select class="form-select account-select @error('accumulated_depreciation_account_id') is-invalid @enderror" id="accumulated_depreciation_account_id" name="accumulated_depreciation_account_id" required>
                                        <option value="" disabled>-- Pilih Akun Kontra-Aset --</option>
                                        @foreach ($contraAssetAccounts as $account)
                                            <option value="{{ $account->account_id }}" {{ old('accumulated_depreciation_account_id', $fixedAsset->accumulated_depreciation_account_id) == $account->account_id ? 'selected' : '' }}>
                                                {{ $account->account_number }} - {{ $account->account_name }}
                                            </option>
                                        @endforeach
                                    </select>
                                    @error('accumulated_depreciation_account_id') <div class="invalid-feedback">{{ $message }}</div> @enderror
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label for="depreciation_method" class="form-label">Metode Penyusutan <span class="text-danger">*</span></label>
                                    <select class="form-select" id="depreciation_method" name="depreciation_method" required>
                                        <option value="straight_line" {{ $fixedAsset->depreciation_method == 'straight_line' ? 'selected' : '' }}>Garis Lurus (Straight Line)</option>
                                    </select>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label for="useful_life_months" class="form-label">Masa Manfaat (Bulan) <span class="text-danger">*</span></label>
                                    <input type="number" class="form-control @error('useful_life_months') is-invalid @enderror" id="useful_life_months" name="useful_life_months" value="{{ old('useful_life_months', $fixedAsset->useful_life_months) }}" required>
                                    @error('useful_life_months') <div class="invalid-feedback">{{ $message }}</div> @enderror
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label for="salvage_value" class="form-label">Nilai Sisa (Residu) (Rp) <span class="text-danger">*</span></label>
                                    <input type="number" class="form-control @error('salvage_value') is-invalid @enderror" id="salvage_value" name="salvage_value" value="{{ old('salvage_value', $fixedAsset->salvage_value) }}" required>
                                    @error('salvage_value') <div class="invalid-feedback">{{ $message }}</div> @enderror
                                </div>
                            </div>

                            <div class="d-flex justify-content-end mt-4">
                                <a href="{{ route('fixed-assets.index') }}" class="btn btn-outline-secondary me-2">Batal</a>
                                <button type="submit" class="btn btn-dark" {{ $fixedAsset->depreciations()->exists() ? 'disabled' : '' }}>
                                    <i class="bi bi-save-fill"></i> Simpan Perubahan
                                </button>
                            </div>
                        </fieldset>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
<script>
    document.addEventListener('DOMContentLoaded', function () {
        $('.account-select').select2({
            theme: 'bootstrap-5',
            width: '100%'
        });
    });
</script>
@endpush
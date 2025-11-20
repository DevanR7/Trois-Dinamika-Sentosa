@extends('layouts.app')

@section('styles')
{{-- Select2 untuk pencarian Akun --}}
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" />
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/select2-bootstrap-5-theme@1.3.0/dist/select2-bootstrap-5-theme.min.css" />
@endsection

@section('content')
<div class="container py-4">
    <div class="row justify-content-center">
        <div class="col-md-8">
            <div class="card shadow-sm border-0">
                <div class="card-header bg-dark text-white">
                    <h2 class="fw-bold mb-0 fs-4">Tambah Akun Bank Baru</h2>
                </div>
                <div class="card-body">
                    <form action="{{ route('company-bank-accounts.store') }}" method="POST" id="bank-form">
                        @csrf
                        <div class="row g-3">
                            {{-- Nama Bank --}}
                            <div class="col-md-6 mb-3">
                                <label for="bank_name" class="form-label">Nama Bank <span class="text-danger">*</span></label>
                                <input type="text" class="form-control @error('bank_name') is-invalid @enderror" id="bank_name" name="bank_name" value="{{ old('bank_name') }}" placeholder="Misal: BCA, Mandiri, Kas Tunai" required>
                                @error('bank_name')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            {{-- Atas Nama --}}
                            <div class="col-md-6 mb-3">
                                <label for="account_name" class="form-label">Atas Nama <span class="text-danger">*</span></label>
                                <input type="text" class="form-control @error('account_name') is-invalid @enderror" id="account_name" name="account_name" value="{{ old('account_name') }}" placeholder="Misal: PT. Usaha Jaya" required>
                                @error('account_name')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            {{-- No. Rekening --}}
                            <div class="col-md-6 mb-3">
                                <label for="account_number" class="form-label">Nomor Rekening</label>
                                <input type="text" class="form-control @error('account_number') is-invalid @enderror" id="account_number" name="account_number" value="{{ old('account_number') }}" placeholder="(Opsional)">
                                @error('account_number')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                            
                            {{-- Hubungkan ke COA --}}
                            <div class="col-md-6 mb-3">
                                <label for="chart_of_account_id" class="form-label">Hubungkan ke Akun (COA) <span class="text-danger">*</span></label>
                                <select class="form-select select2 @error('chart_of_account_id') is-invalid @enderror" id="chart_of_account_id" name="chart_of_account_id" required>
                                    <option value="" disabled selected></option>
                                    @foreach ($assetAccounts as $asset)
                                        <option value="{{ $asset->account_id }}" {{ old('chart_of_account_id') == $asset->account_id ? 'selected' : '' }}>
                                            {{ $asset->account_number }} - {{ $asset->account_name }}
                                        </option>
                                    @endforeach
                                </select>
                                <div class="form-text small text-muted">Pilih akun Kas/Bank dari Chart of Accounts.</div>
                                @error('chart_of_account_id')
                                    <div class="text-danger small mt-1">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="col-12 mb-3">
                                <div class="form-check form-switch">
                                    <input class="form-check-input" type="checkbox" role="switch" id="is_active" name="is_active" value="1" {{ old('is_active', true) ? 'checked' : '' }}>
                                    <label class="form-check-label" for="is_active">Aktifkan Akun Ini</label>
                                </div>
                            </div>
                        </div>
                        
                        <div class="d-flex justify-content-end mt-4">
                            <a href="{{ route('company-bank-accounts.index') }}" class="btn btn-outline-secondary me-2">Batal</a>
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

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
    $(document).ready(function() {
        // Init Select2
        $('.select2').select2({
            theme: 'bootstrap-5',
            placeholder: '-- Pilih Akun Aset --',
            width: '100%'
        });

        // Validasi Submit
        $('#bank-form').on('submit', function(e) {
            if(!this.checkValidity()) {
                // Biarkan HTML5 validation bekerja
            }
        });
    });
</script>
@endpush
@extends('layouts.app')

@push('styles')
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
        <div class="col-md-10">
            <div class="card shadow-sm">
                <div class="card-header bg-dark text-white">
                    <h2 class="fw-bold mb-0 fs-4">Catat Aset Tetap Baru</h2>
                </div>
                <div class="card-body">
                    <form action="{{ route('fixed-assets.store') }}" method="POST" id="asset-form">
                        @csrf
                        
                        <h5 class="fw-semibold text-primary mb-3">1. Informasi Aset & Pembelian</h5>
                        <div class="row g-3 mb-3">
                             <div class="col-12 mb-3">
                                <label for="asset_name" class="form-label">Nama Aset <span class="text-danger">*</span></label>
                                <input type="text" class="form-control @error('asset_name') is-invalid @enderror" id="asset_name" name="asset_name" value="{{ old('asset_name') }}" placeholder="Contoh: Laptop Kantor A, Mobil Avanza B 1234 XYZ" required>
                                @error('asset_name') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            </div>

                            <div class="col-md-4 mb-3">
                                <label for="purchase_date" class="form-label">Tanggal Beli <span class="text-danger">*</span></label>
                                <input type="date" class="form-control @error('purchase_date') is-invalid @enderror" id="purchase_date" name="purchase_date" value="{{ old('purchase_date', now()->toDateString()) }}" required>
                                @error('purchase_date') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            </div>

                            {{-- FORMAT RUPIAH: HARGA BELI --}}
                            <div class="col-md-4 mb-3">
                                <label for="purchase_cost_display" class="form-label">Harga Beli (Rp) <span class="text-danger">*</span></label>
                                <div class="input-group">
                                    <span class="input-group-text">Rp</span>
                                    <input type="text" class="form-control rupiah-input @error('purchase_cost') is-invalid @enderror" id="purchase_cost_display" placeholder="0" required>
                                </div>
                                <input type="hidden" name="purchase_cost" id="purchase_cost" value="{{ old('purchase_cost') }}">
                                @error('purchase_cost') <div class="text-danger small mt-1">{{ $message }}</div> @enderror
                            </div>

                            <div class="col-md-4 mb-3">
                                <label for="cash_bank_account_id" class="form-label">Sumber Dana (Kredit) <span class="text-danger">*</span></label>
                                <select class="form-select account-select @error('cash_bank_account_id') is-invalid @enderror" id="cash_bank_account_id" name="cash_bank_account_id" required>
                                    <option value="" disabled selected>-- Pilih Akun Kas/Bank --</option>
                                    @foreach ($cashAccounts as $account)
                                        <option value="{{ $account->account_id }}" {{ old('cash_bank_account_id') == $account->account_id ? 'selected' : '' }}>
                                            {{ $account->account_number }} - {{ $account->account_name }}
                                        </option>
                                    @endforeach
                                </select>
                                @error('cash_bank_account_id') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            </div>

                            <div class="col-12 mb-3">
                                <label for="description" class="form-label">Deskripsi</label>
                                <textarea class="form-control @error('description') is-invalid @enderror" id="description" name="description" rows="2" placeholder="Catatan opsional (misal: serial number, lokasi, No. Polisi)">{{ old('description') }}</textarea>
                                @error('description') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            </div>
                        </div>

                        <hr>
                        <h5 class="fw-semibold text-primary mb-3">2. Informasi Akuntansi & Penyusutan</h5>
                        <div class="row g-3 mb-3">
                            <div class="col-md-6 mb-3">
                                <label for="fixed_asset_account_id" class="form-label">Akun Aset (Debit) <span class="text-danger">*</span></label>
                                <select class="form-select account-select @error('fixed_asset_account_id') is-invalid @enderror" id="fixed_asset_account_id" name="fixed_asset_account_id" required>
                                    <option value="" disabled selected>-- Pilih Akun Aset --</option>
                                    @foreach ($assetAccounts as $account)
                                        <option value="{{ $account->account_id }}" {{ old('fixed_asset_account_id') == $account->account_id ? 'selected' : '' }}>
                                            {{ $account->account_number }} - {{ $account->account_name }}
                                        </option>
                                    @endforeach
                                </select>
                                <div class="form-text">Akun di Neraca untuk mencatat nilai aset ini.</div>
                                @error('fixed_asset_account_id') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            </div>

                            <div class="col-md-6 mb-3">
                                <label for="depreciation_expense_account_id" class="form-label">Akun Beban Penyusutan (Debit) <span class="text-danger">*</span></label>
                                <select class="form-select account-select @error('depreciation_expense_account_id') is-invalid @enderror" id="depreciation_expense_account_id" name="depreciation_expense_account_id" required>
                                    <option value="" disabled selected>-- Pilih Akun Beban --</option>
                                    @foreach ($expenseAccounts as $account)
                                        <option value="{{ $account->account_id }}" {{ old('depreciation_expense_account_id') == $account->account_id ? 'selected' : '' }}>
                                            {{ $account->account_number }} - {{ $account->account_name }}
                                        </option>
                                    @endforeach
                                </select>
                                <div class="form-text">Akun di Laba Rugi untuk mencatat beban bulanan.</div>
                                @error('depreciation_expense_account_id') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            </div>

                            <div class="col-md-6 mb-3">
                                <label for="accumulated_depreciation_account_id" class="form-label">Akun Akumulasi Penyusutan (Kredit) <span class="text-danger">*</span></label>
                                <select class="form-select account-select @error('accumulated_depreciation_account_id') is-invalid @enderror" id="accumulated_depreciation_account_id" name="accumulated_depreciation_account_id" required>
                                    <option value="" disabled selected>-- Pilih Akun Kontra-Aset --</option>
                                    @foreach ($contraAssetAccounts as $account)
                                        <option value="{{ $account->account_id }}" {{ old('accumulated_depreciation_account_id') == $account->account_id ? 'selected' : '' }}>
                                            {{ $account->account_number }} - {{ $account->account_name }}
                                        </option>
                                    @endforeach
                                </select>
                                <div class="form-text">Akun di Neraca (kontra-aset) untuk mengumpulkan penyusutan.</div>
                                @error('accumulated_depreciation_account_id') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            </div>

                            <div class="col-md-6 mb-3">
                                <label for="depreciation_method" class="form-label">Metode Penyusutan <span class="text-danger">*</span></label>
                                <select class="form-select" id="depreciation_method" name="depreciation_method" required>
                                    <option value="straight_line" selected>Garis Lurus (Straight Line)</option>
                                    <option value="double_declining">Saldo Menurun Ganda (Double Declining)</option>
                                </select>
                            </div>

                            <div class="col-md-6 mb-3">
                                <label for="useful_life_months" class="form-label">Masa Manfaat (Bulan) <span class="text-danger">*</span></label>
                                <input type="number" class="form-control @error('useful_life_months') is-invalid @enderror" id="useful_life_months" name="useful_life_months" value="{{ old('useful_life_months') }}" placeholder="Contoh: 48 (untuk 4 tahun)" required>
                                @error('useful_life_months') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            </div>

                            {{-- FORMAT RUPIAH: NILAI SISA --}}
                            <div class="col-md-6 mb-3">
                                <label for="salvage_value_display" class="form-label">Nilai Sisa (Residu) (Rp) <span class="text-danger">*</span></label>
                                <div class="input-group">
                                    <span class="input-group-text">Rp</span>
                                    <input type="text" class="form-control rupiah-input @error('salvage_value') is-invalid @enderror" id="salvage_value_display" placeholder="0" required>
                                </div>
                                <input type="hidden" name="salvage_value" id="salvage_value" value="{{ old('salvage_value', 0) }}">
                                @error('salvage_value') <div class="text-danger small mt-1">{{ $message }}</div> @enderror
                            </div>
                        </div>

                        <div class="d-flex justify-content-end mt-4">
                            <a href="{{ route('fixed-assets.index') }}" class="btn btn-outline-secondary me-2">Batal</a>
                            <button type="submit" class="btn btn-dark">
                                <i class="bi bi-save-fill"></i> Simpan Aset
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
    document.addEventListener('DOMContentLoaded', function () {
        // Init Select2
        $('.account-select').select2({
            theme: 'bootstrap-5',
            width: '100%'
        });

        // --- FORMAT RUPIAH LOGIC ---
        function formatRupiah(angka) {
            let number_string = angka.toString().replace(/[^,\d]/g, "").toString(),
                split = number_string.split(","),
                sisa = split[0].length % 3,
                rupiah = split[0].substr(0, sisa),
                ribuan = split[0].substr(sisa).match(/\d{3}/gi);

            if (ribuan) {
                let separator = sisa ? "." : "";
                rupiah += separator + ribuan.join(".");
            }

            rupiah = split[1] != undefined ? rupiah + "," + split[1] : rupiah;
            return rupiah;
        }

        // Fungsi untuk mengikat event listener ke input
        function attachRupiahListener(displayId, hiddenId) {
            const displayInput = document.getElementById(displayId);
            const hiddenInput = document.getElementById(hiddenId);

            if(displayInput && hiddenInput) {
                // Format awal jika ada nilai old
                if(hiddenInput.value) {
                    displayInput.value = formatRupiah(hiddenInput.value);
                } else {
                    displayInput.value = '0'; // Default 0 display
                    hiddenInput.value = '0'; // Default 0 hidden
                }

                displayInput.addEventListener('keyup', function(e) {
                    let val = this.value.replace(/\./g, ''); // Hapus titik
                    this.value = formatRupiah(val); // Format ulang visual
                    hiddenInput.value = val; // Simpan angka murni
                });
            }
        }

        // Attach ke Harga Beli
        attachRupiahListener('purchase_cost_display', 'purchase_cost');
        // Attach ke Nilai Sisa
        attachRupiahListener('salvage_value_display', 'salvage_value');

        // Validasi sebelum submit (opsional)
        document.getElementById('asset-form').addEventListener('submit', function(e) {
            const cost = document.getElementById('purchase_cost').value;
            if(cost === '' || cost == 0) {
                e.preventDefault();
                Swal.fire('Error', 'Harga Beli tidak boleh kosong/nol', 'error');
            }
        });
    });
</script>
@endpush
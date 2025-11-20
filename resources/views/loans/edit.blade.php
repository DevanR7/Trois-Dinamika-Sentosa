@extends('layouts.app')

@section('content')
<div class="container py-4">
    <div class="row justify-content-center">
        <div class="col-md-8">
            <div class="card shadow-sm">
                <div class="card-header bg-dark text-white">
                    <h2 class="fw-bold mb-0 fs-4">Edit Pinjaman</h2>
                </div>
                <div class="card-body">
                    @if ($loan->payments()->exists())
                        <div class="alert alert-danger d-flex align-items-center">
                            <i class="bi bi-lock-fill fs-4 me-2"></i>
                            <div>
                                <strong>Terkunci!</strong> Pinjaman ini tidak bisa diedit karena sudah memiliki riwayat pembayaran.
                            </div>
                        </div>
                    @endif
                    
                    <form action="{{ route('loans.update', $loan) }}" method="POST" id="loan-form">
                        @csrf
                        @method('PUT')
                        
                        {{-- Disable form fieldset jika sudah ada pembayaran --}}
                        <fieldset {{ $loan->payments()->exists() ? 'disabled' : '' }}>
                            <div class="row g-3">
                                <div class="col-12 mb-3">
                                    <label for="lender_name" class="form-label">Nama Pemberi Pinjaman <span class="text-danger">*</span></label>
                                    <input type="text" class="form-control @error('lender_name') is-invalid @enderror" id="lender_name" name="lender_name" value="{{ old('lender_name', $loan->lender_name) }}" required>
                                    @error('lender_name')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>

                                <div class="col-md-6 mb-3">
                                    <label for="loan_date" class="form-label">Tanggal Diterima <span class="text-danger">*</span></label>
                                    <input type="date" class="form-control @error('loan_date') is-invalid @enderror" id="loan_date" name="loan_date" value="{{ old('loan_date', $loan->loan_date->toDateString()) }}" required>
                                    @error('loan_date')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>

                                {{-- Jumlah Pokok (Rupiah) --}}
                                <div class="col-md-6 mb-3">
                                    <label for="principal_amount_display" class="form-label">Jumlah Pokok Pinjaman (Rp) <span class="text-danger">*</span></label>
                                    <div class="input-group">
                                        <span class="input-group-text">Rp</span>
                                        <input type="text" class="form-control rupiah-input @error('principal_amount') is-invalid @enderror" id="principal_amount_display" required>
                                    </div>
                                    <input type="hidden" name="principal_amount" id="principal_amount" value="{{ old('principal_amount', intval($loan->principal_amount)) }}">
                                    @error('principal_amount')
                                        <div class="text-danger small mt-1">{{ $message }}</div>
                                    @enderror
                                </div>
                                
                                <div class="col-md-6 mb-3">
                                    <label for="loan_account_id" class="form-label">Akun Utang (Kredit) <span class="text-danger">*</span></label>
                                    <select class="form-select @error('loan_account_id') is-invalid @enderror" id="loan_account_id" name="loan_account_id" required>
                                        <option value="" disabled>-- Pilih Akun Liabilitas --</option>
                                        @foreach ($loanAccounts as $account)
                                            <option value="{{ $account->account_id }}" {{ old('loan_account_id', $loan->loan_account_id) == $account->account_id ? 'selected' : '' }}>
                                                {{ $account->account_number }} - {{ $account->account_name }}
                                            </option>
                                        @endforeach
                                    </select>
                                    @error('loan_account_id')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>

                                <div class="col-md-6 mb-3">
                                    <label for="cash_bank_account_id" class="form-label">Dana Diterima Ke (Debit) <span class="text-danger">*</span></label>
                                    <select class="form-select @error('cash_bank_account_id') is-invalid @enderror" id="cash_bank_account_id" name="cash_bank_account_id" required>
                                        <option value="" disabled>-- Pilih Akun Kas/Bank --</option>
                                        @foreach ($cashAccounts as $account)
                                            <option value="{{ $account->account_id }}" {{ old('cash_bank_account_id', $loan->cash_bank_account_id) == $account->account_id ? 'selected' : '' }}>
                                                {{ $account->account_number }} - {{ $account->account_name }}
                                            </option>
                                        @endforeach
                                    </select>
                                    @error('cash_bank_account_id')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                                
                                <div class="col-12 mb-3">
                                    <label for="description" class="form-label">Deskripsi</label>
                                    <textarea class="form-control @error('description') is-invalid @enderror" id="description" name="description" rows="3">{{ old('description', $loan->description) }}</textarea>
                                    @error('description')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                            <div class="d-flex justify-content-end mt-4">
                                <a href="{{ route('loans.index') }}" class="btn btn-outline-secondary me-2">Batal</a>
                                <button type="submit" class="btn btn-dark" {{ $loan->payments()->exists() ? 'disabled' : '' }}>
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
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
    const amountDisplay = document.getElementById('principal_amount_display');
    const amountInput = document.getElementById('principal_amount');
    const form = document.getElementById('loan-form');

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
        return rupiah;
    }

    // 1. Init
    if(amountInput.value) {
        amountDisplay.value = formatRupiah(amountInput.value);
    }

    // 2. Live Typing
    amountDisplay.addEventListener('keyup', function(e) {
        let val = this.value.replace(/\./g, '');
        this.value = formatRupiah(val);
        amountInput.value = val;
    });

    // 3. Validasi
    form.addEventListener('submit', function(e) {
        if(!this.checkValidity()) return; // HTML5 validation
        
        if(amountInput.value === '' || amountInput.value == 0) {
            e.preventDefault();
            Swal.fire('Error', 'Jumlah pokok tidak boleh kosong!', 'error');
        }
    });
</script>
@endpush
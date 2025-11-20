@extends('layouts.app')

@section('content')
<div class="container py-4">
    <div class="row justify-content-center">
        <div class="col-md-8">
            <div class="card shadow-sm">
                <div class="card-header bg-dark text-white">
                    <h2 class="fw-bold mb-0 fs-4">Catat Pinjaman Baru</h2>
                </div>
                <div class="card-body">
                    <form action="{{ route('loans.store') }}" method="POST" id="loan-form">
                        @csrf
                        <div class="row g-3">
                            {{-- Nama Pemberi Pinjaman --}}
                             <div class="col-12 mb-3">
                                <label for="lender_name" class="form-label">Nama Pemberi Pinjaman <span class="text-danger">*</span></label>
                                <input type="text" class="form-control @error('lender_name') is-invalid @enderror" id="lender_name" name="lender_name" value="{{ old('lender_name') }}" placeholder="Contoh: Bank BCA, Koperasi Mandiri" required>
                                @error('lender_name')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            {{-- Tanggal --}}
                            <div class="col-md-6 mb-3">
                                <label for="loan_date" class="form-label">Tanggal Diterima <span class="text-danger">*</span></label>
                                <input type="date" class="form-control @error('loan_date') is-invalid @enderror" id="loan_date" name="loan_date" value="{{ old('loan_date', now()->toDateString()) }}" required>
                                @error('loan_date')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            {{-- Jumlah Pokok (Rupiah) --}}
                            <div class="col-md-6 mb-3">
                                <label for="principal_amount_display" class="form-label">Jumlah Pokok Pinjaman (Rp) <span class="text-danger">*</span></label>
                                <div class="input-group">
                                    <span class="input-group-text">Rp</span>
                                    <input type="text" class="form-control rupiah-input @error('principal_amount') is-invalid @enderror" id="principal_amount_display" placeholder="0" required>
                                </div>
                                <input type="hidden" name="principal_amount" id="principal_amount" value="{{ old('principal_amount') }}">
                                @error('principal_amount')
                                    <div class="text-danger small mt-1">{{ $message }}</div>
                                @enderror
                            </div>
                            
                            {{-- Akun Utang --}}
                            <div class="col-md-6 mb-3">
                                <label for="loan_account_id" class="form-label">Akun Utang (Kredit) <span class="text-danger">*</span></label>
                                <select class="form-select @error('loan_account_id') is-invalid @enderror" id="loan_account_id" name="loan_account_id" required>
                                    <option value="" disabled selected>-- Pilih Akun Liabilitas --</option>
                                    @foreach ($loanAccounts as $account)
                                        <option value="{{ $account->account_id }}" {{ old('loan_account_id') == $account->account_id ? 'selected' : '' }}>
                                            {{ $account->account_number }} - {{ $account->account_name }}
                                        </option>
                                    @endforeach
                                </select>
                                <div class="form-text small text-muted">Akun Kewajiban yang akan bertambah.</div>
                                @error('loan_account_id')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            {{-- Akun Kas --}}
                            <div class="col-md-6 mb-3">
                                <label for="cash_bank_account_id" class="form-label">Dana Diterima Ke (Debit) <span class="text-danger">*</span></label>
                                <select class="form-select @error('cash_bank_account_id') is-invalid @enderror" id="cash_bank_account_id" name="cash_bank_account_id" required>
                                    <option value="" disabled selected>-- Pilih Akun Kas/Bank --</option>
                                    @foreach ($cashAccounts as $account)
                                        <option value="{{ $account->account_id }}" {{ old('cash_bank_account_id') == $account->account_id ? 'selected' : '' }}>
                                            {{ $account->account_number }} - {{ $account->account_name }}
                                        </option>
                                    @endforeach
                                </select>
                                <div class="form-text small text-muted">Akun Kas/Bank tempat uang masuk.</div>
                                @error('cash_bank_account_id')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                            
                            <div class="col-12 mb-3">
                                <label for="description" class="form-label">Deskripsi</label>
                                <textarea class="form-control @error('description') is-invalid @enderror" id="description" name="description" rows="3" placeholder="Catatan opsional (misal: Tenor 5 tahun, Bunga 10%)">{{ old('description') }}</textarea>
                                @error('description')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>
                        <div class="d-flex justify-content-end mt-4">
                            <a href="{{ route('loans.index') }}" class="btn btn-outline-secondary me-2">Batal</a>
                            <button type="submit" class="btn btn-dark">
                                <i class="bi bi-save-fill"></i> Simpan Pinjaman
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
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
    const amountDisplay = document.getElementById('principal_amount_display');
    const amountInput = document.getElementById('principal_amount');
    const form = document.getElementById('loan-form');

    // Helper Format Rupiah
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

    // 1. Init Format (jika old value ada)
    if(amountInput.value) {
        amountDisplay.value = formatRupiah(amountInput.value);
    }

    // 2. Live Typing Event
    amountDisplay.addEventListener('keyup', function(e) {
        // Hanya izinkan angka
        let val = this.value.replace(/\./g, ''); 
        this.value = formatRupiah(val);
        amountInput.value = val;
    });

    // 3. Validasi Submit
    form.addEventListener('submit', function(e) {
        if(amountInput.value === '' || amountInput.value == 0) {
            e.preventDefault();
            Swal.fire({
                icon: 'error',
                title: 'Oops...',
                text: 'Jumlah Pokok Pinjaman tidak boleh kosong atau nol!',
            });
        }
    });
</script>
@endpush
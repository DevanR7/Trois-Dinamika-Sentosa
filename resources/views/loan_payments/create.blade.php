@extends('layouts.app')

@section('content')
<div class="container py-4">
    <div class="row justify-content-center">
        <div class="col-md-8">
            <div class="card shadow-sm">
                <div class="card-header bg-dark text-white">
                    <h2 class="fw-bold mb-0 fs-4">Catat Pembayaran Cicilan</h2>
                    <p class="mb-0 text-white-50 small">
                        Untuk Pinjaman: <strong>{{ $loan->lender_name }}</strong>
                    </p>
                </div>
                <div class="card-body">
                    
                    {{-- Alert Sisa Pokok --}}
                    <div class="alert alert-info border-info d-flex align-items-center mb-4">
                        <i class="bi bi-info-circle-fill fs-4 me-3 text-info"></i>
                        <div>
                            Sisa Pokok Pinjaman Saat Ini: <br>
                            <strong class="fs-4">Rp {{ number_format($loan->remaining_balance, 0, ',', '.') }}</strong>
                            <input type="hidden" id="max_payment" value="{{ $loan->remaining_balance }}">
                        </div>
                    </div>

                    <form action="{{ route('loans.payments.store', $loan) }}" method="POST" id="payment-form">
                        @csrf
                        
                        <div class="row g-3">
                            <div class="col-12 mb-3">
                                <label for="payment_date" class="form-label">Tanggal Bayar <span class="text-danger">*</span></label>
                                <input type="date" class="form-control @error('payment_date') is-invalid @enderror" id="payment_date" name="payment_date" value="{{ old('payment_date', now()->toDateString()) }}" required>
                                @error('payment_date')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>

                        <div class="row g-3">
                            {{-- INPUT BAYAR POKOK --}}
                            <div class="col-md-6 mb-3">
                                <label for="principal_paid_display" class="form-label">Jumlah Bayar Pokok (Rp) <span class="text-danger">*</span></label>
                                <div class="input-group">
                                    <span class="input-group-text">Rp</span>
                                    <input type="text" class="form-control rupiah-input @error('principal_paid') is-invalid @enderror" id="principal_paid_display" placeholder="0" required>
                                </div>
                                <input type="hidden" name="principal_paid" id="principal_paid" value="{{ old('principal_paid', 0) }}">
                                <div class="form-text small text-muted">Mendebit Akun Utang ({{ $loan->loanAccount->account_name ?? 'N/A' }}).</div>
                                @error('principal_paid')
                                    <div class="text-danger small mt-1">{{ $message }}</div>
                                @enderror
                            </div>

                            {{-- INPUT BAYAR BUNGA --}}
                            <div class="col-md-6 mb-3">
                                <label for="interest_paid_display" class="form-label">Jumlah Bayar Bunga (Rp) <span class="text-danger">*</span></label>
                                <div class="input-group">
                                    <span class="input-group-text">Rp</span>
                                    <input type="text" class="form-control rupiah-input @error('interest_paid') is-invalid @enderror" id="interest_paid_display" placeholder="0" required>
                                </div>
                                <input type="hidden" name="interest_paid" id="interest_paid" value="{{ old('interest_paid', 0) }}">
                                <div class="form-text small text-muted">Dicatat sebagai Beban Bunga.</div>
                                @error('interest_paid')
                                    <div class="text-danger small mt-1">{{ $message }}</div>
                                @enderror
                            </div>
                            
                            {{-- AKUN BEBAN BUNGA --}}
                            <div class="col-md-6 mb-3">
                                <label for="interest_expense_account_id" class="form-label">Akun Beban Bunga (Debit) <span class="text-danger">*</span></label>
                                <select class="form-select @error('interest_expense_account_id') is-invalid @enderror" id="interest_expense_account_id" name="interest_expense_account_id">
                                    <option value="" disabled selected>-- Pilih Akun Beban --</option>
                                    @foreach ($expenseAccounts as $account)
                                        <option value="{{ $account->account_id }}" {{ old('interest_expense_account_id') == $account->account_id ? 'selected' : '' }}>
                                            {{ $account->account_number }} - {{ $account->account_name }}
                                        </option>
                                    @endforeach
                                </select>
                                <div class="form-text small text-muted">Wajib jika bayar bunga > 0.</div>
                                @error('interest_expense_account_id')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            {{-- SUMBER DANA --}}
                            <div class="col-md-6 mb-3">
                                <label for="cash_bank_account_id" class="form-label">Sumber Dana (Kredit) <span class="text-danger">*</span></label>
                                <select class="form-select @error('cash_bank_account_id') is-invalid @enderror" id="cash_bank_account_id" name="cash_bank_account_id" required>
                                    <option value="" disabled selected>-- Pilih Akun Kas/Bank --</option>
                                    @foreach ($cashAccounts as $account)
                                        <option value="{{ $account->account_id }}" {{ old('cash_bank_account_id') == $account->account_id ? 'selected' : '' }}>
                                            {{ $account->account_number }} - {{ $account->account_name }}
                                        </option>
                                    @endforeach
                                </select>
                                <div class="form-text small text-muted">Akun Kas/Bank yang berkurang.</div>
                                @error('cash_bank_account_id')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                            
                            <div class="col-12 mb-3">
                                <label for="notes" class="form-label">Catatan</label>
                                <textarea class="form-control @error('notes') is-invalid @enderror" id="notes" name="notes" rows="2" placeholder="Contoh: Pembayaran cicilan ke-1">{{ old('notes') }}</textarea>
                                @error('notes')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>

                        <div class="d-flex justify-content-end mt-4">
                            <a href="{{ route('loans.show', $loan) }}" class="btn btn-outline-secondary me-2">Batal</a>
                            <button type="submit" class="btn btn-dark">
                                <i class="bi bi-save-fill"></i> Simpan Pembayaran
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
    // --- Format Rupiah Logic ---
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

    function attachRupiahListener(displayId, hiddenId) {
        const displayInput = document.getElementById(displayId);
        const hiddenInput = document.getElementById(hiddenId);

        if(displayInput && hiddenInput) {
            // Init
            if(hiddenInput.value > 0) {
                displayInput.value = formatRupiah(hiddenInput.value);
            }

            // Typing
            displayInput.addEventListener('keyup', function(e) {
                let val = this.value.replace(/\./g, '');
                this.value = formatRupiah(val);
                hiddenInput.value = val;
            });
        }
    }

    attachRupiahListener('principal_paid_display', 'principal_paid');
    attachRupiahListener('interest_paid_display', 'interest_paid');

    // --- Validasi Submit ---
    document.getElementById('payment-form').addEventListener('submit', function(e) {
        const principal = parseFloat(document.getElementById('principal_paid').value) || 0;
        const interest = parseFloat(document.getElementById('interest_paid').value) || 0;
        const maxPayment = parseFloat(document.getElementById('max_payment').value);

        // 1. Cek jika total bayar 0
        if (principal + interest <= 0) {
            e.preventDefault();
            Swal.fire('Error', 'Total pembayaran (Pokok + Bunga) harus lebih dari 0.', 'error');
            return;
        }

        // 2. Cek Overpayment (Pokok melebihi sisa hutang)
        if (principal > maxPayment) {
            e.preventDefault();
            Swal.fire({
                icon: 'error',
                title: 'Pembayaran Berlebih!',
                text: 'Jumlah bayar pokok (Rp ' + formatRupiah(principal) + ') tidak boleh melebihi sisa hutang (Rp ' + formatRupiah(maxPayment) + ').',
            });
            return;
        }
        
        // 3. Cek Akun Bunga
        const interestAcc = document.getElementById('interest_expense_account_id').value;
        if(interest > 0 && !interestAcc) {
            e.preventDefault();
            Swal.fire('Error', 'Silakan pilih Akun Beban Bunga jika ada pembayaran bunga.', 'warning');
            return;
        }
    });
</script>
@endpush
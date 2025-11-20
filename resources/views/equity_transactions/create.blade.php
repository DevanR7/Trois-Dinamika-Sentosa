@extends('layouts.app')

@section('content')
<div class="container py-4">
    <div class="row justify-content-center">
        <div class="col-md-8">
            <div class="card shadow-sm">
                <div class="card-header bg-dark text-white">
                    <h2 class="fw-bold mb-0 fs-4">Catat Transaksi Modal Baru</h2>
                </div>
                <div class="card-body">
                    <form action="{{ route('equity-transactions.store') }}" method="POST" id="equity-form">
                        @csrf
                        
                        <div class="row g-3">
                            <div class="col-md-6 mb-3">
                                <label for="transaction_date" class="form-label">Tanggal Transaksi <span class="text-danger">*</span></label>
                                <input type="date" class="form-control @error('transaction_date') is-invalid @enderror" id="transaction_date" name="transaction_date" value="{{ old('transaction_date', now()->toDateString()) }}" required>
                                @error('transaction_date')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            {{-- INPUT JUMLAH DENGAN FORMAT RUPIAH --}}
                            <div class="col-md-6 mb-3">
                                <label for="amount_display" class="form-label">Jumlah (Rp) <span class="text-danger">*</span></label>
                                <div class="input-group">
                                    <span class="input-group-text">Rp</span>
                                    <input type="text" class="form-control rupiah-input @error('amount') is-invalid @enderror" id="amount_display" placeholder="0" required>
                                </div>
                                {{-- Input Hidden untuk kirim ke database --}}
                                <input type="hidden" name="amount" id="amount" value="{{ old('amount') }}">
                                
                                @error('amount')
                                    <div class="text-danger small mt-1">{{ $message }}</div>
                                @enderror
                            </div>
                            
                            <div class="col-md-6 mb-3">
                                <label for="equity_account_id" class="form-label">Akun Modal <span class="text-danger">*</span></label>
                                <select class="form-select @error('equity_account_id') is-invalid @enderror" id="equity_account_id" name="equity_account_id" required>
                                    <option value="" disabled selected>-- Pilih Akun Modal/Prive --</option>
                                    @foreach ($equityAccounts as $account)
                                        <option value="{{ $account->account_id }}" {{ old('equity_account_id') == $account->account_id ? 'selected' : '' }}>
                                            {{ $account->account_number }} - {{ $account->account_name }} (Saldo: {{ $account->normal_balance }})
                                        </option>
                                    @endforeach
                                </select>
                                <div class="form-text small text-muted">Pilih Akun Modal (untuk setoran) atau Prive (untuk penarikan).</div>
                                @error('equity_account_id')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="col-md-6 mb-3">
                                <label for="cash_bank_account_id" class="form-label">Akun Kas/Bank <span class="text-danger">*</span></label>
                                <select class="form-select @error('cash_bank_account_id') is-invalid @enderror" id="cash_bank_account_id" name="cash_bank_account_id" required>
                                    <option value="" disabled selected>-- Pilih Akun Kas/Bank --</option>
                                    @foreach ($cashAccounts as $account)
                                        <option value="{{ $account->account_id }}" {{ old('cash_bank_account_id') == $account->account_id ? 'selected' : '' }}>
                                            {{ $account->account_number }} - {{ $account->account_name }}
                                        </option>
                                    @endforeach
                                </select>
                                <div class="form-text small text-muted">Akun Kas yang bertambah/berkurang.</div>
                                @error('cash_bank_account_id')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="col-12 mb-3">
                                <label for="description" class="form-label">Deskripsi <span class="text-danger">*</span></label>
                                <textarea class="form-control @error('description') is-invalid @enderror" id="description" name="description" rows="3" placeholder="Contoh: Setoran modal awal dari Pemilik" required>{{ old('description') }}</textarea>
                                @error('description')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>
                        <div class="d-flex justify-content-end mt-4">
                            <a href="{{ route('equity-transactions.index') }}" class="btn btn-outline-secondary me-2">Batal</a>
                            <button type="submit" class="btn btn-dark">
                                <i class="bi bi-save-fill"></i> Simpan Transaksi
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
    const amountDisplay = document.getElementById('amount_display');
    const amountInput = document.getElementById('amount');
    const form = document.getElementById('equity-form');

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

    // 1. Init jika old value ada
    if(amountInput.value) {
        amountDisplay.value = formatRupiah(amountInput.value);
    }

    // 2. Live Typing
    amountDisplay.addEventListener('keyup', function(e) {
        let val = this.value.replace(/\./g, ''); // hapus titik
        this.value = formatRupiah(val);
        amountInput.value = val;
    });

    // 3. Validasi Submit
    form.addEventListener('submit', function(e) {
        if(amountInput.value === '' || amountInput.value == 0) {
            e.preventDefault();
            Swal.fire('Error', 'Jumlah nominal tidak boleh kosong!', 'error');
        }
    });
</script>
@endpush
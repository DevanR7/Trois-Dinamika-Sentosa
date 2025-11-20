@extends('layouts.app')

@section('content')
<div class="container py-4">
    <div class="row justify-content-center">
        <div class="col-md-8">
            <div class="card shadow-sm">
                <div class="card-header bg-dark text-white">
                    <h2 class="fw-bold mb-0 fs-4">Tambah Pengeluaran Baru</h2>
                </div>
                <div class="card-body">
                    <form action="{{ route('expenses.store') }}" method="POST" id="expense-form">
                        @csrf
                        
                        <div class="row g-3">
                            <div class="col-md-6 mb-3">
                                <label for="expense_date" class="form-label">Tanggal Pengeluaran <span class="text-danger">*</span></label>
                                <input type="date" class="form-control @error('expense_date') is-invalid @enderror" id="expense_date" name="expense_date" value="{{ old('expense_date', now()->toDateString()) }}" required>
                                @error('expense_date')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="col-md-6 mb-3">
                                <label for="amount_display" class="form-label">Jumlah (Rp) <span class="text-danger">*</span></label>
                                {{-- Input Visual (dengan titik) --}}
                                <div class="input-group">
                                    <span class="input-group-text">Rp</span>
                                    <input type="text" class="form-control rupiah-input @error('amount') is-invalid @enderror" id="amount_display" placeholder="0" required>
                                </div>
                                {{-- Input Asli (Hidden, untuk dikirim ke server) --}}
                                <input type="hidden" name="amount" id="amount" value="{{ old('amount') }}">
                                
                                @error('amount')
                                    <div class="text-danger small mt-1">{{ $message }}</div>
                                @enderror
                            </div>
                            
                            <div class="col-md-6 mb-3">
                                <label for="chart_of_account_id" class="form-label">Kategori Beban <span class="text-danger">*</span></label>
                                <select class="form-select @error('chart_of_account_id') is-invalid @enderror" id="chart_of_account_id" name="chart_of_account_id" required>
                                    <option value="" disabled selected>-- Pilih Akun Beban --</option>
                                    @foreach ($expenseAccounts as $account)
                                        <option value="{{ $account->account_id }}" {{ old('chart_of_account_id') == $account->account_id ? 'selected' : '' }}>
                                            {{ $account->account_number }} - {{ $account->account_name }}
                                        </option>
                                    @endforeach
                                </select>
                                @error('chart_of_account_id')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="col-md-6 mb-3">
                                <label for="cash_bank_account_id" class="form-label">Sumber Dana <span class="text-danger">*</span></label>
                                <select class="form-select @error('cash_bank_account_id') is-invalid @enderror" id="cash_bank_account_id" name="cash_bank_account_id" required>
                                    <option value="" disabled selected>-- Pilih Akun Kas/Bank --</option>
                                    @foreach ($cashAccounts as $account)
                                        <option value="{{ $account->account_id }}" {{ old('cash_bank_account_id') == $account->account_id ? 'selected' : '' }}>
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
                                <textarea class="form-control @error('description') is-invalid @enderror" id="description" name="description" rows="4" placeholder="Contoh: Pembayaran Gaji Karyawan Bulan Oktober" required>{{ old('description') }}</textarea>
                                @error('description')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>
                        <div class="d-flex justify-content-end mt-4">
                            <a href="{{ route('expenses.index') }}" class="btn btn-outline-secondary me-2">Batal</a>
                            <button type="submit" class="btn btn-dark">
                                <i class="bi bi-save-fill"></i> Simpan
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
    // Fungsi Format Rupiah
    const amountDisplay = document.getElementById('amount_display');
    const amountInput = document.getElementById('amount');
    const form = document.getElementById('expense-form');

    // 1. Format saat mengetik
    amountDisplay.addEventListener('keyup', function(e) {
        let val = this.value.replace(/[^0-9]/g, ''); // Hanya angka
        this.value = formatRupiah(val);
        amountInput.value = val; // Simpan angka murni ke input hidden
    });

    // 2. Helper Function Format
    function formatRupiah(angka) {
        let number_string = angka.toString(),
            sisa    = number_string.length % 3,
            rupiah  = number_string.substr(0, sisa),
            ribuan  = number_string.substr(sisa).match(/\d{3}/g);
            
        if (ribuan) {
            let separator = sisa ? '.' : '';
            rupiah += separator + ribuan.join('.');
        }
        return rupiah;
    }

    // 3. Intercept Submit untuk Validasi Visual
    form.addEventListener('submit', function(e) {
        if(amountInput.value === '' || amountInput.value == 0) {
            e.preventDefault();
            Swal.fire('Error', 'Jumlah nominal tidak boleh kosong!', 'error');
        }
    });
</script>
@endpush
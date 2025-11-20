@extends('layouts.app')

@section('content')
<div class="container py-4">
    <div class="row justify-content-center">
        <div class="col-md-8">
            <div class="card shadow-sm">
                <div class="card-header bg-dark text-white">
                    <h2 class="fw-bold mb-0 fs-4">Edit Pengeluaran</h2>
                </div>
                <div class="card-body">
                    <form action="{{ route('expenses.update', $expense) }}" method="POST" id="expense-form">
                        @csrf
                        @method('PUT')
                        
                        <div class="row g-3">
                            <div class="col-md-6 mb-3">
                                <label for="expense_date" class="form-label">Tanggal Pengeluaran <span class="text-danger">*</span></label>
                                <input type="date" class="form-control @error('expense_date') is-invalid @enderror" id="expense_date" name="expense_date" value="{{ old('expense_date', $expense->expense_date->toDateString()) }}" required>
                                @error('expense_date')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="col-md-6 mb-3">
                                <label for="amount_display" class="form-label">Jumlah (Rp) <span class="text-danger">*</span></label>
                                {{-- Input Visual --}}
                                <div class="input-group">
                                    <span class="input-group-text">Rp</span>
                                    <input type="text" class="form-control rupiah-input @error('amount') is-invalid @enderror" id="amount_display" required>
                                </div>
                                {{-- Input Hidden --}}
                                <input type="hidden" name="amount" id="amount" value="{{ old('amount', intval($expense->amount)) }}">
                                
                                @error('amount')
                                    <div class="text-danger small mt-1">{{ $message }}</div>
                                @enderror
                            </div>
                            
                            <div class="col-md-6 mb-3">
                                <label for="chart_of_account_id" class="form-label">Kategori Beban <span class="text-danger">*</span></label>
                                <select class="form-select @error('chart_of_account_id') is-invalid @enderror" id="chart_of_account_id" name="chart_of_account_id" required>
                                    <option value="" disabled>-- Pilih Akun Beban --</option>
                                    @foreach ($expenseAccounts as $account)
                                        <option value="{{ $account->account_id }}" {{ old('chart_of_account_id', $expense->chart_of_account_id) == $account->account_id ? 'selected' : '' }}>
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
                                    <option value="" disabled>-- Pilih Akun Kas/Bank --</option>
                                    @foreach ($cashAccounts as $account)
                                        <option value="{{ $account->account_id }}" {{ old('cash_bank_account_id', $expense->cash_bank_account_id) == $account->account_id ? 'selected' : '' }}>
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
                                <textarea class="form-control @error('description') is-invalid @enderror" id="description" name="description" rows="4" required>{{ old('description', $expense->description) }}</textarea>
                                @error('description')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>
                        <div class="d-flex justify-content-end mt-4">
                            <a href="{{ route('expenses.index') }}" class="btn btn-outline-secondary me-2">Batal</a>
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

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
    const amountDisplay = document.getElementById('amount_display');
    const amountInput = document.getElementById('amount');
    const form = document.getElementById('expense-form');

    // Helper Format Rupiah
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

    // 1. Init Value (Saat halaman dimuat, format angka dari DB/Old)
    if(amountInput.value) {
        amountDisplay.value = formatRupiah(amountInput.value);
    }

    // 2. Live Typing
    amountDisplay.addEventListener('keyup', function(e) {
        let val = this.value.replace(/[^0-9]/g, '');
        this.value = formatRupiah(val);
        amountInput.value = val;
    });

    // 3. Submit Handler
    form.addEventListener('submit', function(e) {
        if(amountInput.value === '' || amountInput.value == 0) {
            e.preventDefault();
            Swal.fire('Error', 'Jumlah nominal tidak boleh kosong!', 'error');
        }
    });
</script>
@endpush
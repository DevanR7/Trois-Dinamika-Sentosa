@extends('admin.layouts.app')

@section('title', 'Edit Pinjaman')

@section('content')
    <div class="max-w-4xl mx-auto animate-enter">
        
        <div class="flex items-center gap-4 mb-6">
            <a href="{{ route('admin.loans.index') }}" class="btn-icon btn-secondary">
                <i class="material-icons">arrow_back</i>
            </a>
            <div>
                <h1 class="page-title">Edit Data Pinjaman</h1>
                <p class="page-subtitle">Perbarui informasi pinjaman. Hati-hati, perubahan akan merevisi Jurnal Akuntansi.</p>
            </div>
        </div>

        <form action="{{ route('admin.loans.update', $loan->loan_id) }}" method="POST" class="card" id="editLoanForm">
            @csrf
            @method('PUT')
            
            <div class="card-header">
                <h3 class="card-header-title">Formulir Edit Pinjaman #{{ $loan->loan_id }}</h3>
            </div>

            <div class="card-body grid grid-cols-1 md:grid-cols-2 gap-6">
                
                {{-- KIRI --}}
                <div class="space-y-4">
                    <div>
                        <label class="form-label required">Pemberi Pinjaman</label>
                        <input type="text" name="lender_name" class="form-input" value="{{ old('lender_name', $loan->lender_name) }}" required>
                    </div>

                    <div>
                        <label class="form-label required">Tanggal Penerimaan</label>
                        <input type="date" name="loan_date" class="form-input" value="{{ old('loan_date', $loan->loan_date->format('Y-m-d')) }}" required>
                    </div>

                    <div>
                        <label class="form-label required">Jumlah Pokok</label>
                        <div class="relative">
                            <span class="absolute left-3 top-1/2 -translate-y-1/2 text-slate-500">Rp</span>
                            <input type="text" class="form-input pl-10 input-currency autonumeric" required>
                            <input type="hidden" name="principal_amount" value="{{ old('principal_amount', $loan->principal_amount) }}">
                        </div>
                        @if($loan->payments()->exists())
                             <p class="text-xs text-amber-500 mt-1 flex items-center gap-1">
                                <i class="material-icons text-xs">warning</i>
                                <span>Peringatan: Pinjaman ini sudah memiliki riwayat pembayaran. Mengubah pokok mungkin menyebabkan inkonsistensi sisa hutang.</span>
                             </p>
                        @endif
                    </div>

                    <div>
                        <label class="form-label">Keterangan</label>
                        <textarea name="description" rows="3" class="form-input">{{ old('description', $loan->description) }}</textarea>
                    </div>
                </div>

                {{-- KANAN --}}
                <div class="space-y-4">
                    <div class="p-3 bg-amber-50 dark:bg-amber-900/20 rounded-lg text-xs text-amber-700 dark:text-amber-400 mb-4">
                        <i class="material-icons text-sm align-middle mr-1">warning</i>
                        <strong>Perhatian:</strong> Mengubah akun di bawah ini akan memicu <em>Reversal Jurnal</em> lama dan memposting Jurnal Baru.
                    </div>

                    <div>
                        <label class="form-label required">Masuk ke Akun (Debit)</label>
                        <select name="cash_bank_account_id" class="tom-select" required>
                            @foreach($cashAccounts as $account)
                                <option value="{{ $account->account_id }}" {{ old('cash_bank_account_id', $loan->cash_bank_account_id) == $account->account_id ? 'selected' : '' }}>
                                    {{ $account->account_number }} - {{ $account->account_name }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div>
                        <label class="form-label required">Akun Hutang (Kredit)</label>
                        <select name="loan_account_id" class="tom-select" required>
                            @foreach($loanAccounts as $account)
                                <option value="{{ $account->account_id }}" {{ old('loan_account_id', $loan->loan_account_id) == $account->account_id ? 'selected' : '' }}>
                                    {{ $account->account_number }} - {{ $account->account_name }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                </div>

            </div>

            <div class="card-footer flex justify-end gap-3">
                <a href="{{ route('admin.loans.index') }}" class="btn btn-secondary">Batal</a>
                <button type="submit" class="btn btn-primary">Update Data</button>
            </div>
        </form>
    </div>
@endsection

@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function() {
        const form = document.getElementById('editLoanForm');
        const visualInput = form.querySelector('.autonumeric');
        const hiddenInput = form.querySelector('input[name="principal_amount"]');

        if(visualInput && hiddenInput) {
            if (!AutoNumeric.getAutoNumericElement(visualInput)) {
                new AutoNumeric(visualInput, window.defaultAutoNumericOptions);
            }
            visualInput.addEventListener('autoNumeric:rawValueModified', e => {
                hiddenInput.value = e.detail.newRawValue;
            });
            // Pre-fill value from database
            if(hiddenInput.value) {
                AutoNumeric.getAutoNumericElement(visualInput).set(hiddenInput.value);
            }
        }
    });
</script>
@endpush
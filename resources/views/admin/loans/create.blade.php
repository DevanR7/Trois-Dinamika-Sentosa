@extends('admin.layouts.app')

@section('title', 'Tambah Pinjaman')

@section('content')
    <div class="max-w-4xl mx-auto animate-enter">
        
        {{-- Header --}}
        <div class="flex items-center gap-4 mb-6">
            <a href="{{ route('admin.loans.index') }}" class="btn-icon btn-secondary">
                <i class="material-icons">arrow_back</i>
            </a>
            <div>
                <h1 class="page-title">Tambah Pinjaman Baru</h1>
                <p class="page-subtitle">Catat penerimaan dana pinjaman dan pemetaan akun akuntansinya.</p>
            </div>
        </div>

        <form action="{{ route('admin.loans.store') }}" method="POST" class="card" id="createLoanForm">
            @csrf
            
            <div class="card-header">
                <h3 class="card-header-title">Formulir Pinjaman</h3>
            </div>

            <div class="card-body grid grid-cols-1 md:grid-cols-2 gap-6">
                
                {{-- KIRI: Informasi Dasar --}}
                <div class="space-y-4">
                    <h4 class="text-sm font-bold text-slate-800 dark:text-white border-b pb-2 mb-4">Informasi Dasar</h4>

                    <div>
                        <label class="form-label required">Pemberi Pinjaman (Lender)</label>
                        <input type="text" name="lender_name" class="form-input" placeholder="Misal: Bank Mandiri, Leasing..." value="{{ old('lender_name') }}" required>
                        @error('lender_name') <span class="text-xs text-red-500">{{ $message }}</span> @enderror
                    </div>

                    <div>
                        <label class="form-label required">Tanggal Penerimaan</label>
                        <input type="date" name="loan_date" class="form-input" value="{{ old('loan_date', date('Y-m-d')) }}" required>
                        @error('loan_date') <span class="text-xs text-red-500">{{ $message }}</span> @enderror
                    </div>

                    <div>
                        <label class="form-label required">Jumlah Pokok (Principal)</label>
                        <div class="relative">
                            <span class="absolute left-3 top-1/2 -translate-y-1/2 text-slate-500">Rp</span>
                            {{-- Input visual autonumeric --}}
                            <input type="text" class="form-input pl-10 input-currency autonumeric" 
                                   placeholder="0" required>
                            {{-- Input hidden untuk kirim data bersih --}}
                            <input type="hidden" name="principal_amount" value="{{ old('principal_amount') }}">
                        </div>
                        @error('principal_amount') <span class="text-xs text-red-500">{{ $message }}</span> @enderror
                    </div>

                    <div>
                        <label class="form-label">Keterangan / No. Kontrak</label>
                        <textarea name="description" rows="3" class="form-input" placeholder="Catatan tambahan...">{{ old('description') }}</textarea>
                    </div>
                </div>

                {{-- KANAN: Integrasi Akuntansi --}}
                <div class="space-y-4">
                    <h4 class="text-sm font-bold text-slate-800 dark:text-white border-b pb-2 mb-4">Integrasi Akuntansi (Jurnal Otomatis)</h4>
                    
                    <div class="p-3 bg-blue-50 dark:bg-blue-900/20 rounded-lg text-xs text-blue-700 dark:text-blue-300 mb-4">
                        <i class="material-icons text-sm align-middle mr-1">info</i>
                        Sistem akan membuat Jurnal Umum: <br>
                        <strong>(Dr) Kas/Bank</strong> bertambah.<br>
                        <strong>(Cr) Hutang Pinjaman</strong> bertambah.
                    </div>

                    <div>
                        <label class="form-label required">Masuk ke Akun (Debit)</label>
                        <select name="cash_bank_account_id" class="tom-select" required>
                            <option value="">Pilih Akun Kas/Bank...</option>
                            @foreach($cashAccounts as $account)
                                <option value="{{ $account->account_id }}" {{ old('cash_bank_account_id') == $account->account_id ? 'selected' : '' }}>
                                    {{ $account->account_number }} - {{ $account->account_name }}
                                </option>
                            @endforeach
                        </select>
                        <p class="text-[10px] text-slate-400 mt-1">Akun Aset tempat uang diterima.</p>
                        @error('cash_bank_account_id') <span class="text-xs text-red-500">{{ $message }}</span> @enderror
                    </div>

                    <div>
                        <label class="form-label required">Akun Hutang (Kredit)</label>
                        <select name="loan_account_id" class="tom-select" required>
                            <option value="">Pilih Akun Kewajiban...</option>
                            @foreach($loanAccounts as $account)
                                <option value="{{ $account->account_id }}" {{ old('loan_account_id') == $account->account_id ? 'selected' : '' }}>
                                    {{ $account->account_number }} - {{ $account->account_name }}
                                </option>
                            @endforeach
                        </select>
                        <p class="text-[10px] text-slate-400 mt-1">Akun Liabilitas untuk mencatat hutang.</p>
                        @error('loan_account_id') <span class="text-xs text-red-500">{{ $message }}</span> @enderror
                    </div>
                </div>

            </div>

            <div class="card-footer flex justify-end gap-3 bg-slate-50 dark:bg-slate-800/50">
                <a href="{{ route('admin.loans.index') }}" class="btn btn-secondary">Batal</a>
                <button type="submit" class="btn btn-primary">Simpan Data</button>
            </div>
        </form>
    </div>
@endsection

@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function() {
        // Logika sederhana untuk sync AutoNumeric ke Hidden Input
        // (Logika global di app.js sudah menangani init, ini untuk memastikan value terisi saat submit)
        const form = document.getElementById('createLoanForm');
        const visualInput = form.querySelector('.autonumeric');
        const hiddenInput = form.querySelector('input[name="principal_amount"]');

        if(visualInput && hiddenInput) {
             // Init AutoNumeric manual jika belum (opsional, jaga-jaga)
            if (!AutoNumeric.getAutoNumericElement(visualInput)) {
                new AutoNumeric(visualInput, window.defaultAutoNumericOptions);
            }

            // Sync saat ketik
            visualInput.addEventListener('autoNumeric:rawValueModified', e => {
                hiddenInput.value = e.detail.newRawValue;
            });
            
            // Pre-fill jika old value ada (Validation error)
            if(hiddenInput.value) {
                AutoNumeric.getAutoNumericElement(visualInput).set(hiddenInput.value);
            }
        }
    });
</script>
@endpush
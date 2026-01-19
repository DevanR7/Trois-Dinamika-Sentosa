@extends('admin.layouts.app')

@section('title', 'Tambah Akun Bank')

@section('content')
    {{-- Header --}}
    <div class="page-header">
        <div>
            <h1 class="page-title">Tambah Rekening Bank</h1>
            <p class="page-subtitle">Daftarkan rekening bank baru untuk operasional perusahaan</p>
        </div>
        <div>
            <a href="{{ route('admin.company-bank-accounts.index') }}" class="btn btn-secondary">
                <i class="material-icons text-[18px]">arrow_back</i>
                Kembali
            </a>
        </div>
    </div>

    <div class="max-w-xl">
        <div class="card">
            <div class="card-body">
                <form action="{{ route('admin.company-bank-accounts.store') }}" method="POST">
                    @csrf

                    {{-- Nama Bank --}}
                    <div class="form-group mb-4">
                        <label for="bank_name" class="form-label label-required">Nama Bank</label>
                        <div class="relative">
                            <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                <i class="material-icons text-slate-400 text-[18px]">account_balance</i>
                            </div>
                            <input type="text" id="bank_name" name="bank_name" 
                                   class="form-input pl-10 @error('bank_name') is-invalid @enderror" 
                                   value="{{ old('bank_name') }}" 
                                   placeholder="Contoh: BCA, Mandiri, BRI" required>
                        </div>
                        @error('bank_name')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    {{-- Nomor Rekening --}}
                    <div class="form-group mb-4">
                        <label for="account_number" class="form-label label-required">Nomor Rekening</label>
                        <div class="relative">
                            <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                <i class="material-icons text-slate-400 text-[18px]">tag</i>
                            </div>
                            <input type="text" id="account_number" name="account_number" 
                                   class="form-input pl-10 font-mono @error('account_number') is-invalid @enderror" 
                                   value="{{ old('account_number') }}" 
                                   placeholder="Contoh: 1234567890">
                        </div>
                        @error('account_number')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    {{-- Atas Nama --}}
                    <div class="form-group mb-4">
                        <label for="account_name" class="form-label label-required">Atas Nama (Pemilik)</label>
                        <div class="relative">
                            <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                <i class="material-icons text-slate-400 text-[18px]">badge</i>
                            </div>
                            <input type="text" id="account_name" name="account_name" 
                                   class="form-input pl-10 @error('account_name') is-invalid @enderror" 
                                   value="{{ old('account_name') }}" 
                                   placeholder="Contoh: PT. Nama Perusahaan" required>
                        </div>
                        @error('account_name')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    {{-- Link ke Akun Akuntansi (COA) --}}
                    <div class="form-group mb-6">
                        <label for="chart_of_account_id" class="form-label label-required">Hubungkan ke Akun Aset (COA)</label>
                        <select id="chart_of_account_id" name="chart_of_account_id" 
                                class="tom-select @error('chart_of_account_id') is-invalid @enderror" required>
                            <option value="">Pilih Akun Kas/Bank...</option>
                            @foreach($assetAccounts as $coa)
                                <option value="{{ $coa->account_id }}" {{ old('chart_of_account_id') == $coa->account_id ? 'selected' : '' }}>
                                    {{ $coa->account_number }} - {{ $coa->account_name }}
                                </option>
                            @endforeach
                        </select>
                        <p class="text-xs text-slate-400 mt-1">Setiap transaksi masuk/keluar dari rekening ini akan dicatat ke akun COA yang dipilih.</p>
                        @error('chart_of_account_id')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    {{-- Status Aktif --}}
                    <div class="form-group mb-6">
                        <label class="flex items-center gap-2 cursor-pointer">
                            {{-- Hidden input untuk handle unchecked value (opsional, tapi Laravel biasanya butuh ini atau validasi boolean) --}}
                            <input type="hidden" name="is_active" value="0">
                            <input type="checkbox" name="is_active" value="1" class="form-check-input" checked>
                            <span class="form-check-label">Rekening Aktif</span>
                        </label>
                    </div>

                    {{-- Actions --}}
                    <div class="flex items-center justify-end gap-3 pt-4 border-t border-slate-100 dark:border-slate-700">
                        <a href="{{ route('admin.company-bank-accounts.index') }}" class="btn btn-secondary">Batal</a>
                        <button type="submit" class="btn btn-primary">
                            <i class="material-icons text-[18px]">save</i>
                            Simpan Rekening
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endsection
@extends('admin.layouts.app')

@section('title', 'Tambah Rekening')

@section('content')

    <div class="max-w-2xl mx-auto">
        
        {{-- Navigation --}}
        <div class="flex items-center justify-between mb-6">
            <div>
                <h1 class="page-title">Tambah Rekening</h1>
                <p class="page-subtitle">Daftarkan akun bank atau kas baru.</p>
            </div>
            <a href="{{ route('admin.company-bank-accounts.index') }}" class="btn btn-secondary">
                <i class="material-icons text-sm mr-1">arrow_back</i> Kembali
            </a>
        </div>

        <form action="{{ route('admin.company-bank-accounts.store') }}" method="POST">
            @csrf

            <div class="card">
                <div class="card-header">
                    <h3 class="card-header-title">Informasi Rekening</h3>
                </div>
                <div class="card-body space-y-6">

                    {{-- Nama Bank --}}
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div>
                            <label class="form-label label-required">Nama Bank / Kas</label>
                            <input type="text" name="bank_name" 
                                   class="form-input @error('bank_name') is-invalid @enderror" 
                                   placeholder="Contoh: BCA, Mandiri, Kas Kecil" 
                                   value="{{ old('bank_name') }}" required>
                            @error('bank_name') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>
                        <div>
                            <label class="form-label label-required">Atas Nama</label>
                            <input type="text" name="account_name" 
                                   class="form-input @error('account_name') is-invalid @enderror" 
                                   placeholder="Contoh: PT. Maju Jaya" 
                                   value="{{ old('account_name') }}" required>
                            @error('account_name') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>
                    </div>

                    {{-- Nomor Rekening --}}
                    <div>
                        <label class="form-label label-optional">Nomor Rekening</label>
                        <input type="text" name="account_number" 
                               class="form-input @error('account_number') is-invalid @enderror" 
                               placeholder="Contoh: 1234567890" 
                               value="{{ old('account_number') }}">
                        <div class="form-hint">Kosongkan jika ini adalah akun Kas Tunai.</div>
                        @error('account_number') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>

                    {{-- Chart of Account --}}
                    <div>
                        <label class="form-label label-required">Hubungkan ke Akun (COA)</label>
                        <select name="chart_of_account_id" class="tom-select" required placeholder="Pilih Akun Aset...">
                            <option value="">Pilih Akun...</option>
                            @foreach($assetAccounts as $coa)
                                <option value="{{ $coa->account_id }}" {{ old('chart_of_account_id') == $coa->account_id ? 'selected' : '' }}>
                                    {{ $coa->account_number }} - {{ $coa->account_name }}
                                </option>
                            @endforeach
                        </select>
                        <div class="form-hint text-indigo-600 dark:text-indigo-400 mt-1">
                            <i class="material-icons text-[10px] align-middle">info</i>
                            Penting: Semua transaksi pada rekening ini akan dijurnal ke akun COA yang dipilih.
                        </div>
                        @error('chart_of_account_id') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>

                    {{-- Status Aktif --}}
                    <div class="p-4 bg-slate-50 dark:bg-slate-800/50 rounded-xl border border-slate-100 dark:border-slate-700 flex items-center justify-between">
                        <div>
                            <span class="text-sm font-bold text-slate-700 dark:text-slate-200">Status Aktif</span>
                            <p class="text-xs text-slate-500">Rekening ini dapat dipilih saat pembayaran.</p>
                        </div>
                        <label class="relative inline-flex items-center cursor-pointer">
                            <input type="checkbox" name="is_active" value="1" class="sr-only peer" checked>
                            <div class="w-11 h-6 bg-slate-200 peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-indigo-300 dark:peer-focus:ring-indigo-800 rounded-full peer dark:bg-slate-700 peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all dark:border-gray-600 peer-checked:bg-indigo-600"></div>
                        </label>
                    </div>

                </div>
            </div>

            {{-- Submit --}}
            <div class="mt-8 pt-6 border-t border-slate-200 dark:border-slate-700 flex justify-end">
                <button type="submit" class="btn btn-primary btn-lg">
                    <i class="material-icons text-sm mr-2">save</i> Simpan Rekening
                </button>
            </div>

        </form>
    </div>

@endsection
@extends('admin.layouts.app')

@section('title', 'Edit Aset Tetap')

@section('content')

    <div class="max-w-4xl mx-auto">
        
        {{-- Navigation --}}
        <div class="flex items-center justify-between mb-6">
            <div>
                <h1 class="page-title">Edit Aset: {{ $fixedAsset->asset_name }}</h1>
                <p class="page-subtitle">Perbarui data aset dan konfigurasi akuntansi.</p>
            </div>
            <div class="flex gap-3">
                <button type="button" onclick="confirmDelete()" class="btn btn-danger">
                    <i class="material-icons text-sm mr-1">delete</i> Hapus
                </button>
                <a href="{{ route('admin.fixed-assets.index') }}" class="btn btn-secondary">
                    <i class="material-icons text-sm mr-1">arrow_back</i> Kembali
                </a>
            </div>
        </div>

        <form action="{{ route('admin.fixed-assets.update', $fixedAsset->asset_id) }}" method="POST">
            @csrf
            @method('PUT')

            <div class="space-y-8">
                
                {{-- CARD 1: DATA ASET --}}
                <div class="card">
                    <div class="card-header">
                        <h3 class="card-header-title">1. Informasi Aset & Nilai</h3>
                    </div>
                    <div class="card-body space-y-6">
                        
                        {{-- Nama Aset --}}
                        <div>
                            <label class="form-label label-required">Nama Aset</label>
                            <input type="text" name="asset_name" 
                                   class="form-input @error('asset_name') is-invalid @enderror" 
                                   value="{{ old('asset_name', $fixedAsset->asset_name) }}" required>
                            @error('asset_name') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>

                        {{-- Tanggal & Harga --}}
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div>
                                <label class="form-label label-required">Tanggal Perolehan</label>
                                <input type="date" name="purchase_date" class="form-input" 
                                       value="{{ old('purchase_date', $fixedAsset->purchase_date->format('Y-m-d')) }}" required>
                                @error('purchase_date') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            </div>
                            <div>
                                <label class="form-label label-required">Harga Perolehan</label>
                                <div class="input-group">
                                    <span class="input-group-text">Rp</span>
                                    <input type="text" name="purchase_cost" 
                                           class="form-input autonumeric text-right font-bold text-slate-700" 
                                           value="{{ old('purchase_cost', $fixedAsset->purchase_cost) }}" required>
                                </div>
                                @error('purchase_cost') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            </div>
                        </div>

                        {{-- Deskripsi --}}
                        <div>
                            <label class="form-label label-optional">Deskripsi</label>
                            <textarea name="description" class="form-textarea" rows="2">{{ old('description', $fixedAsset->description) }}</textarea>
                        </div>

                        <div class="border-t border-slate-100 dark:border-slate-700"></div>

                        {{-- Parameter Penyusutan --}}
                        <div>
                            <h4 class="text-sm font-bold text-slate-700 dark:text-slate-200 mb-4">Parameter Penyusutan</h4>
                            <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                                <div>
                                    <label class="form-label label-required">Metode Penyusutan</label>
                                    <select name="depreciation_method" class="tom-select" required>
                                        <option value="straight_line" {{ old('depreciation_method', $fixedAsset->depreciation_method) == 'straight_line' ? 'selected' : '' }}>Garis Lurus (Straight Line)</option>
                                        <option value="double_declining" {{ old('depreciation_method', $fixedAsset->depreciation_method) == 'double_declining' ? 'selected' : '' }}>Saldo Menurun Ganda</option>
                                    </select>
                                </div>
                                <div>
                                    <label class="form-label label-required">Umur Manfaat (Bulan)</label>
                                    <input type="number" name="useful_life_months" class="form-input" 
                                           value="{{ old('useful_life_months', $fixedAsset->useful_life_months) }}" required>
                                </div>
                                <div>
                                    <label class="form-label label-required">Nilai Sisa (Residu)</label>
                                    <div class="input-group">
                                        <span class="input-group-text">Rp</span>
                                        <input type="text" name="salvage_value" 
                                               class="form-input autonumeric text-right" 
                                               value="{{ old('salvage_value', $fixedAsset->salvage_value) }}" required>
                                    </div>
                                    {{-- PERBAIKAN STYLE HINT --}}
                                    <p class="mt-1 text-[11px] text-slate-500 italic">
                                        Nilai taksiran saat umur ekonomis habis.
                                    </p>
                                </div>
                            </div>
                        </div>

                    </div>
                </div>

                {{-- CARD 2: AKUNTANSI --}}
                <div class="card border-l-4 border-indigo-500">
                    <div class="card-header bg-indigo-50/50 dark:bg-indigo-900/10">
                        <h3 class="card-header-title text-indigo-700 dark:text-indigo-300 flex items-center gap-2">
                            <i class="material-icons text-sm">account_balance</i> 2. Konfigurasi Akun (COA)
                        </h3>
                    </div>
                    <div class="card-body space-y-6">

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            {{-- 1. Akun Aset --}}
                            <div>
                                <label class="form-label label-required">A. Akun Aset Tetap</label>
                                <select name="fixed_asset_account_id" class="tom-select" required>
                                    @foreach($assetAccounts as $coa)
                                        <option value="{{ $coa->account_id }}" {{ old('fixed_asset_account_id', $fixedAsset->fixed_asset_account_id) == $coa->account_id ? 'selected' : '' }}>
                                            {{ $coa->account_number }} - {{ $coa->account_name }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>

                            {{-- 2. Sumber Dana --}}
                            <div>
                                <label class="form-label label-required">B. Sumber Dana</label>
                                <select name="cash_bank_account_id" class="tom-select" required>
                                    @foreach($cashAccounts as $coa)
                                        <option value="{{ $coa->account_id }}" {{ old('cash_bank_account_id', $fixedAsset->cash_bank_account_id) == $coa->account_id ? 'selected' : '' }}>
                                            {{ $coa->account_number }} - {{ $coa->account_name }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>

                            {{-- 3. Akun Beban --}}
                            <div>
                                <label class="form-label label-required">C. Akun Beban Penyusutan</label>
                                <select name="depreciation_expense_account_id" class="tom-select" required>
                                    @foreach($expenseAccounts as $coa)
                                        <option value="{{ $coa->account_id }}" {{ old('depreciation_expense_account_id', $fixedAsset->depreciation_expense_account_id) == $coa->account_id ? 'selected' : '' }}>
                                            {{ $coa->account_number }} - {{ $coa->account_name }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>

                            {{-- 4. Akumulasi --}}
                            <div>
                                <label class="form-label label-required">D. Akun Akumulasi Penyusutan</label>
                                <select name="accumulated_depreciation_account_id" class="tom-select" required>
                                    @foreach($contraAssetAccounts as $coa)
                                        <option value="{{ $coa->account_id }}" {{ old('accumulated_depreciation_account_id', $fixedAsset->accumulated_depreciation_account_id) == $coa->account_id ? 'selected' : '' }}>
                                            {{ $coa->account_number }} - {{ $coa->account_name }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                        </div>

                    </div>
                </div>

            </div>

            {{-- Submit --}}
            <div class="mt-8 pt-6 border-t border-slate-200 dark:border-slate-700 flex justify-end">
                <button type="submit" class="btn btn-primary btn-lg">
                    <i class="material-icons text-sm mr-2">save</i> Simpan Perubahan
                </button>
            </div>

        </form>

        <form id="deleteForm" action="{{ route('admin.fixed-assets.destroy', $fixedAsset->asset_id) }}" method="POST" class="hidden">
            @csrf
            @method('DELETE')
        </form>
    </div>

@endsection

@push('scripts')
<script>
    function confirmDelete() {
        window.confirmDialog({
            title: 'Hapus Aset?',
            text: "Data ini akan dihapus dan jurnal pembelian akan dibalik.",
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#ef4444',
            cancelButtonColor: '#64748b',
            confirmButtonText: 'Ya, Hapus!',
            cancelButtonText: 'Batal'
        }).then((result) => {
            if (result.isConfirmed) {
                document.getElementById('deleteForm').submit();
            }
        });
    }
</script>
@endpush
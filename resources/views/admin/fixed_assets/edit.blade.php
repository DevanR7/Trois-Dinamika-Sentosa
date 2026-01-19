@extends('admin.layouts.app')

@section('title', 'Edit Aset Tetap')

@section('content')
<div class="flex flex-col gap-6 max-w-5xl mx-auto">
    
    <div class="flex items-center justify-between">
        <div>
            <a href="{{ route('admin.fixed-assets.index') }}" class="flex items-center text-sm text-slate-500 hover:text-indigo-600 transition-colors mb-2">
                <i class="material-icons text-[16px] mr-1">arrow_back</i> Kembali
            </a>
            <h1 class="page-title">Edit Aset <span class="text-slate-400">#{{ $fixedAsset->asset_name }}</span></h1>
        </div>
    </div>

    {{-- Warning Logic --}}
    @if($fixedAsset->depreciations->count() > 0)
        <div class="bg-amber-50 border-l-4 border-amber-400 p-4 rounded-r shadow-sm mb-2">
            <div class="flex">
                <div class="flex-shrink-0">
                    <i class="material-icons text-amber-400">warning</i>
                </div>
                <div class="ml-3">
                    <p class="text-sm text-amber-700">
                        Aset ini sudah memiliki riwayat penyusutan. Mengubah data finansial (Harga, Umur, Metode) dapat menyebabkan inkonsistensi pada laporan.
                    </p>
                </div>
            </div>
        </div>
    @endif

    <form action="{{ route('admin.fixed-assets.update', $fixedAsset->asset_id) }}" method="POST" x-data="fixedAssetEditForm()">
        @csrf
        @method('PUT')

        {{-- Section 1: Informasi Aset & Pembelian --}}
        <div class="card p-6 mb-6">
            <h3 class="text-sm font-bold text-indigo-600 uppercase tracking-wider mb-4 border-b pb-2 border-slate-100 dark:border-slate-700">
                1. Informasi Pembelian & Nilai
            </h3>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                
                <div class="md:col-span-2">
                    <label for="asset_name" class="form-label label-required">Nama Aset</label>
                    <input type="text" id="asset_name" name="asset_name" 
                           value="{{ old('asset_name', $fixedAsset->asset_name) }}" 
                           class="form-input @error('asset_name') is-invalid @enderror" required>
                    @error('asset_name') <div class="invalid-feedback">{{ $message }}</div> @enderror
                </div>

                <div>
                    <label for="purchase_date" class="form-label label-required">Tanggal Pembelian</label>
                    <input type="date" id="purchase_date" name="purchase_date" 
                           value="{{ old('purchase_date', $fixedAsset->purchase_date->format('Y-m-d')) }}" 
                           class="form-input @error('purchase_date') is-invalid @enderror" required>
                    @error('purchase_date') <div class="invalid-feedback">{{ $message }}</div> @enderror
                </div>

                {{-- Harga Perolehan (FIXED with Alpine) --}}
                <div>
                    <label for="purchase_cost" class="form-label label-required">Harga Perolehan (Cost)</label>
                    <div class="relative">
                        <span class="absolute left-3 top-2.5 text-slate-500 font-bold">Rp</span>
                        
                        {{-- Input Visual --}}
                        <input type="text" class="form-input pl-10 text-right font-mono font-bold"
                               id="purchase_cost_visual"
                               x-ref="purchase_cost_visual"
                               placeholder="0" required>
                        
                        {{-- Input Hidden --}}
                        <input type="hidden" id="purchase_cost" name="purchase_cost" x-model="purchaseCost">
                    </div>
                    @error('purchase_cost') <div class="invalid-feedback">{{ $message }}</div> @enderror
                </div>

                <div>
                    <label for="fixed_asset_account_id" class="form-label label-required">Akun Aset Tetap (Debit)</label>
                    <select id="fixed_asset_account_id" name="fixed_asset_account_id" class="tom-select" required>
                        @foreach($assetAccounts as $acc)
                            <option value="{{ $acc->account_id }}" 
                                {{ old('fixed_asset_account_id', $fixedAsset->fixed_asset_account_id) == $acc->account_id ? 'selected' : '' }}>
                                {{ $acc->account_number }} - {{ $acc->account_name }}
                            </option>
                        @endforeach
                    </select>
                    @error('fixed_asset_account_id') <div class="invalid-feedback">{{ $message }}</div> @enderror
                </div>

                <div>
                    <label for="cash_bank_account_id" class="form-label label-required">Sumber Dana (Kredit)</label>
                    <select id="cash_bank_account_id" name="cash_bank_account_id" class="tom-select" required>
                        @foreach($cashAccounts as $acc)
                            <option value="{{ $acc->account_id }}" 
                                {{ old('cash_bank_account_id', $fixedAsset->cash_bank_account_id) == $acc->account_id ? 'selected' : '' }}>
                                {{ $acc->account_number }} - {{ $acc->account_name }}
                            </option>
                        @endforeach
                    </select>
                    @error('cash_bank_account_id') <div class="invalid-feedback">{{ $message }}</div> @enderror
                </div>

                <div class="md:col-span-2">
                    <label for="description" class="form-label label-optional">Keterangan / Spesifikasi</label>
                    <textarea id="description" name="description" rows="2" class="form-textarea">{{ old('description', $fixedAsset->description) }}</textarea>
                </div>
            </div>
        </div>

        {{-- Section 2: Konfigurasi Penyusutan --}}
        <div class="card p-6">
            <h3 class="text-sm font-bold text-indigo-600 uppercase tracking-wider mb-4 border-b pb-2 border-slate-100 dark:border-slate-700">
                2. Konfigurasi Penyusutan (Depreciation)
            </h3>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                
                <div>
                    <label for="depreciation_method" class="form-label label-required">Metode Penyusutan</label>
                    <select id="depreciation_method" name="depreciation_method" class="form-select" required>
                        <option value="straight_line" {{ old('depreciation_method', $fixedAsset->depreciation_method) == 'straight_line' ? 'selected' : '' }}>Garis Lurus (Straight Line)</option>
                        <option value="double_declining" {{ old('depreciation_method', $fixedAsset->depreciation_method) == 'double_declining' ? 'selected' : '' }}>Saldo Menurun Ganda (Double Declining)</option>
                    </select>
                </div>

                <div>
                    <label for="useful_life_months" class="form-label label-required">Umur Ekonomis (Bulan)</label>
                    <div class="flex items-center">
                        <input type="number" id="useful_life_months" name="useful_life_months" 
                               value="{{ old('useful_life_months', $fixedAsset->useful_life_months) }}" 
                               class="form-input rounded-r-none" min="1" required>
                        <span class="input-group-text rounded-l-none">Bulan</span>
                    </div>
                    @error('useful_life_months') <div class="invalid-feedback">{{ $message }}</div> @enderror
                </div>

                {{-- Nilai Sisa (FIXED with Alpine) --}}
                <div>
                    <label for="salvage_value" class="form-label label-required">Nilai Sisa (Residu)</label>
                    <div class="relative">
                        <span class="absolute left-3 top-2.5 text-slate-500 font-bold">Rp</span>
                        
                        {{-- Input Visual --}}
                        <input type="text" class="form-input pl-10 text-right font-mono font-bold"
                               id="salvage_value_visual"
                               x-ref="salvage_value_visual"
                               placeholder="0" required>
                        
                        {{-- Input Hidden --}}
                        <input type="hidden" id="salvage_value" name="salvage_value" x-model="salvageValue">
                    </div>
                    @error('salvage_value') <div class="invalid-feedback">{{ $message }}</div> @enderror
                </div>

                <div class="hidden md:block"></div>

                <div>
                    <label for="accumulated_depreciation_account_id" class="form-label label-required">Akun Akumulasi Penyusutan (Kredit)</label>
                    <select id="accumulated_depreciation_account_id" name="accumulated_depreciation_account_id" class="tom-select" required>
                        @foreach($contraAssetAccounts as $acc)
                            <option value="{{ $acc->account_id }}" 
                                {{ old('accumulated_depreciation_account_id', $fixedAsset->accumulated_depreciation_account_id) == $acc->account_id ? 'selected' : '' }}>
                                {{ $acc->account_number }} - {{ $acc->account_name }}
                            </option>
                        @endforeach
                    </select>
                    @error('accumulated_depreciation_account_id') <div class="invalid-feedback">{{ $message }}</div> @enderror
                </div>

                <div>
                    <label for="depreciation_expense_account_id" class="form-label label-required">Akun Beban Penyusutan (Debit)</label>
                    <select id="depreciation_expense_account_id" name="depreciation_expense_account_id" class="tom-select" required>
                        @foreach($expenseAccounts as $acc)
                            <option value="{{ $acc->account_id }}" 
                                {{ old('depreciation_expense_account_id', $fixedAsset->depreciation_expense_account_id) == $acc->account_id ? 'selected' : '' }}>
                                {{ $acc->account_number }} - {{ $acc->account_name }}
                            </option>
                        @endforeach
                    </select>
                    @error('depreciation_expense_account_id') <div class="invalid-feedback">{{ $message }}</div> @enderror
                </div>

            </div>

            {{-- Footer --}}
            <div class="flex items-center justify-end gap-3 mt-8 pt-6 border-t border-slate-100 dark:border-slate-700">
                <a href="{{ route('admin.fixed-assets.index') }}" class="btn btn-secondary">Batal</a>
                <button type="submit" class="btn btn-primary">
                    <i class="material-icons text-[18px] mr-2">save</i> Simpan Perubahan
                </button>
            </div>
        </div>
    </form>
</div>

@push('scripts')
<script>
    function fixedAssetEditForm() {
        return {
            // Load data existing dari Controller
            purchaseCost: '{{ old('purchase_cost', $fixedAsset->purchase_cost) }}',
            salvageValue: '{{ old('salvage_value', $fixedAsset->salvage_value) }}',

            init() {
                // 1. Init Purchase Cost
                if(this.$refs.purchase_cost_visual) {
                    const anCost = new AutoNumeric(this.$refs.purchase_cost_visual, window.defaultAutoNumericOptions);
                    anCost.set(this.purchaseCost); // Set nilai awal dari DB
                    
                    this.$refs.purchase_cost_visual.addEventListener('autoNumeric:rawValueModified', e => {
                        this.purchaseCost = e.detail.newRawValue;
                    });
                }

                // 2. Init Salvage Value
                if(this.$refs.salvage_value_visual) {
                    const anSalvage = new AutoNumeric(this.$refs.salvage_value_visual, window.defaultAutoNumericOptions);
                    anSalvage.set(this.salvageValue); // Set nilai awal dari DB

                    this.$refs.salvage_value_visual.addEventListener('autoNumeric:rawValueModified', e => {
                        this.salvageValue = e.detail.newRawValue;
                    });
                }
            }
        }
    }
</script>
@endpush
@endsection
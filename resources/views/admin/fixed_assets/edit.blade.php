@extends('admin.layouts.app')

@section('title', 'Edit Aset Tetap')

@section('content')
<div class="max-w-4xl mx-auto pb-20 animate-enter">
    
    {{-- HEADER --}}
    <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center mb-8 gap-4">
        <div>
            <nav class="flex items-center gap-2 text-sm text-slate-500 mb-1">
                <a href="{{ route('admin.fixed-assets.index') }}" class="hover:text-indigo-600 transition-colors">Aset Tetap</a>
                <span class="mx-2 text-slate-300">/</span>
                <span class="text-slate-800 font-semibold">Edit</span>
            </nav>
            <h1 class="text-2xl font-bold text-slate-800 tracking-tight">Edit Aset Tetap</h1>
        </div>
        <a href="{{ route('admin.fixed-assets.index') }}" 
           class="h-[48px] px-6 bg-white border border-slate-300 text-slate-600 rounded-lg text-sm font-bold hover:bg-slate-50 transition-all flex items-center justify-center gap-2 shadow-sm">
            <i class="material-icons text-[18px]">arrow_back</i> Kembali
        </a>
    </div>

    @if ($fixedAsset->depreciations()->exists())
        <div class="mb-6 bg-red-50 border-l-4 border-red-500 p-4 rounded-md shadow-sm flex items-center gap-3">
            <i class="material-icons text-red-600 text-2xl">lock</i>
            <div>
                <strong class="text-red-800 text-sm font-bold block">Aset Terkunci</strong>
                <span class="text-red-700 text-xs">Aset ini sudah memiliki riwayat penyusutan. Nilai finansial tidak dapat diubah.</span>
            </div>
        </div>
    @endif

    <form action="{{ route('admin.fixed-assets.update', $fixedAsset) }}" method="POST" id="asset-form">
        @csrf
        @method('PUT')
        
        <div class="dashboard-card p-0 overflow-hidden shadow-lg border-0 ring-1 ring-slate-900/5">
            
            <fieldset {{ $fixedAsset->depreciations()->exists() ? 'disabled' : '' }} class="group disabled:opacity-75 transition-opacity">
                
                {{-- Bagian 1 --}}
                <div class="px-6 py-4 border-b border-slate-100 bg-slate-50/50 flex items-center gap-3">
                    <div class="w-8 h-8 rounded-lg bg-indigo-100 flex items-center justify-center text-indigo-600 font-bold text-sm">1</div>
                    <h3 class="text-sm font-bold text-slate-700 uppercase tracking-wide">Informasi Aset</h3>
                </div>

                <div class="p-6 grid grid-cols-1 md:grid-cols-3 gap-6">
                    <div class="md:col-span-3">
                        <label for="asset_name" class="block text-xs font-bold text-slate-500 uppercase mb-1.5">Nama Aset</label>
                        <input type="text" name="asset_name" id="asset_name" value="{{ old('asset_name', $fixedAsset->asset_name) }}" class="form-input font-medium text-slate-800" required>
                    </div>
                    
                    <div>
                        <label for="purchase_date" class="block text-xs font-bold text-slate-500 uppercase mb-1.5">Tanggal Beli</label>
                        <input type="date" name="purchase_date" id="purchase_date" value="{{ old('purchase_date', $fixedAsset->purchase_date->format('Y-m-d')) }}" class="form-input" required>
                    </div>

                    <div>
                        <label for="purchase_cost_display" class="block text-xs font-bold text-slate-500 uppercase mb-1.5">Harga Beli (Rp)</label>
                        <div class="relative">
                            <div class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3">
                                <span class="text-slate-400 font-bold text-sm">Rp</span>
                            </div>
                            <input type="text" id="purchase_cost_display" class="form-input input-currency pl-10 font-mono font-bold text-slate-800" required>
                            <input type="hidden" name="purchase_cost" id="purchase_cost" value="{{ old('purchase_cost', intval($fixedAsset->purchase_cost)) }}">
                        </div>
                    </div>

                    <div>
                        <label for="cash_bank_account_id" class="block text-xs font-bold text-slate-500 uppercase mb-1.5">Sumber Dana</label>
                        <select name="cash_bank_account_id" id="cash_bank_account_id" class="form-input select2-basic" required>
                            @foreach ($cashAccounts as $account)
                                <option value="{{ $account->account_id }}" @selected(old('cash_bank_account_id', $fixedAsset->cash_bank_account_id) == $account->account_id)>
                                    {{ $account->account_number }} - {{ $account->account_name }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="md:col-span-3">
                        <label for="description" class="block text-xs font-bold text-slate-500 uppercase mb-1.5">Deskripsi</label>
                        <textarea name="description" id="description" rows="2" class="form-textarea">{{ old('description', $fixedAsset->description) }}</textarea>
                    </div>
                </div>

                {{-- Bagian 2 --}}
                <div class="px-6 py-4 border-t border-b border-slate-100 bg-slate-50/50 flex items-center gap-3">
                    <div class="w-8 h-8 rounded-lg bg-indigo-100 flex items-center justify-center text-indigo-600 font-bold text-sm">2</div>
                    <h3 class="text-sm font-bold text-slate-700 uppercase tracking-wide">Akuntansi & Penyusutan</h3>
                </div>

                <div class="p-6 grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div>
                        <label for="fixed_asset_account_id" class="block text-xs font-bold text-slate-500 uppercase mb-1.5">Akun Aset (Debit)</label>
                        <select name="fixed_asset_account_id" id="fixed_asset_account_id" class="form-input select2-basic" required>
                            @foreach ($assetAccounts as $account)
                                <option value="{{ $account->account_id }}" @selected(old('fixed_asset_account_id', $fixedAsset->fixed_asset_account_id) == $account->account_id)>
                                    {{ $account->account_number }} - {{ $account->account_name }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div>
                        <label for="depreciation_expense_account_id" class="block text-xs font-bold text-slate-500 uppercase mb-1.5">Akun Beban Penyusutan</label>
                        <select name="depreciation_expense_account_id" id="depreciation_expense_account_id" class="form-input select2-basic" required>
                            @foreach ($expenseAccounts as $account)
                                <option value="{{ $account->account_id }}" @selected(old('depreciation_expense_account_id', $fixedAsset->depreciation_expense_account_id) == $account->account_id)>
                                    {{ $account->account_number }} - {{ $account->account_name }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div>
                        <label for="accumulated_depreciation_account_id" class="block text-xs font-bold text-slate-500 uppercase mb-1.5">Akun Akumulasi Penyusutan</label>
                        <select name="accumulated_depreciation_account_id" id="accumulated_depreciation_account_id" class="form-input select2-basic" required>
                            @foreach ($contraAssetAccounts as $account)
                                <option value="{{ $account->account_id }}" @selected(old('accumulated_depreciation_account_id', $fixedAsset->accumulated_depreciation_account_id) == $account->account_id)>
                                    {{ $account->account_number }} - {{ $account->account_name }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div>
                        <label for="depreciation_method" class="block text-xs font-bold text-slate-500 uppercase mb-1.5">Metode Penyusutan</label>
                        <select name="depreciation_method" id="depreciation_method" class="form-input select2-basic" required>
                            <option value="straight_line" @selected($fixedAsset->depreciation_method == 'straight_line')>Garis Lurus (Straight Line)</option>
                            <option value="double_declining" @selected($fixedAsset->depreciation_method == 'double_declining')>Saldo Menurun Ganda</option>
                        </select>
                    </div>

                    <div>
                        <label for="useful_life_months" class="block text-xs font-bold text-slate-500 uppercase mb-1.5">Masa Manfaat (Bulan)</label>
                        <input type="number" name="useful_life_months" id="useful_life_months" value="{{ old('useful_life_months', $fixedAsset->useful_life_months) }}" class="form-input" required>
                    </div>

                    <div>
                        <label for="salvage_value_display" class="block text-xs font-bold text-slate-500 uppercase mb-1.5">Nilai Sisa (Rp)</label>
                        <div class="relative">
                            <div class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3">
                                <span class="text-slate-400 font-bold text-sm">Rp</span>
                            </div>
                            <input type="text" id="salvage_value_display" class="form-input input-currency pl-10 font-mono" required>
                            <input type="hidden" name="salvage_value" id="salvage_value" value="{{ old('salvage_value', intval($fixedAsset->salvage_value)) }}">
                        </div>
                    </div>
                </div>

                <div class="px-6 py-4 bg-slate-50 border-t border-slate-100 flex justify-end gap-3">
                    <a href="{{ route('admin.fixed-assets.index') }}" class="h-[48px] px-6 bg-white border border-slate-300 text-slate-600 rounded-lg text-sm font-bold hover:bg-slate-50 transition-all flex items-center justify-center shadow-sm">
                        Batal
                    </a>
                    <button type="submit" class="h-[48px] px-8 bg-indigo-600 hover:bg-indigo-700 text-white text-sm font-bold rounded-lg shadow-md transition-all flex items-center justify-center gap-2 group hover:-translate-y-0.5" {{ $fixedAsset->depreciations()->exists() ? 'disabled' : '' }}>
                        <i class="material-icons text-[20px] group-hover:scale-110 transition-transform">check_circle</i> Update Aset
                    </button>
                </div>

            </fieldset>
        </div>
    </form>
</div>
@endsection

@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function () {
        // Sync Hidden Inputs for AutoNumeric
        const purchaseInput = document.getElementById('purchase_cost_display');
        const purchaseHidden = document.getElementById('purchase_cost');
        
        // Set initial values manually because global init might clear them
        if(purchaseHidden.value && AutoNumeric.getAutoNumericElement(purchaseInput)) {
             AutoNumeric.getAutoNumericElement(purchaseInput).set(purchaseHidden.value);
        }
        
        purchaseInput.addEventListener('autoNumeric:rawValueModified', e => {
            purchaseHidden.value = e.detail.newRawValue;
        });

        const salvageInput = document.getElementById('salvage_value_display');
        const salvageHidden = document.getElementById('salvage_value');
        
        if(salvageHidden.value && AutoNumeric.getAutoNumericElement(salvageInput)) {
             AutoNumeric.getAutoNumericElement(salvageInput).set(salvageHidden.value);
        }

        salvageInput.addEventListener('autoNumeric:rawValueModified', e => {
            salvageHidden.value = e.detail.newRawValue;
        });

        @if(session('success')) window.showToast("{{ session('success') }}", 'success'); @endif
        @if(session('error')) window.showToast("{{ session('error') }}", 'error'); @endif
    });
</script>
@endpush
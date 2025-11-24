@extends('layouts.app')

@section('title', 'Edit Aset Tetap')

@section('content')
<div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 py-6">
    
    {{-- HEADER --}}
    <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center mb-6">
        <div>
            <div class="flex items-center gap-2 text-sm text-gray-500 mb-1">
                <a href="{{ route('fixed-assets.index') }}" class="hover:text-indigo-600 transition">Aset Tetap</a>
                <span>/</span>
                <span class="text-gray-800">Edit</span>
            </div>
            <h2 class="text-2xl font-bold text-gray-900 tracking-tight">Edit Aset Tetap</h2>
            <p class="text-sm text-gray-500 mt-1">Perbarui data aset: <span class="font-bold text-indigo-600">{{ $fixedAsset->asset_name }}</span></p>
        </div>
        <div class="mt-4 sm:mt-0">
            <a href="{{ route('fixed-assets.index') }}" class="inline-flex items-center px-4 py-2 bg-white border border-gray-300 rounded-lg font-medium text-gray-700 hover:bg-gray-50 shadow-sm transition text-sm">
                <i class="material-icons text-lg mr-2">arrow_back</i> Kembali
            </a>
        </div>
    </div>

    @if ($fixedAsset->depreciations()->exists())
        <div class="mb-6 bg-red-50 border border-red-200 rounded-lg p-4 flex items-center gap-3">
            <i class="material-icons text-red-500 text-2xl">warning</i>
            <div>
                <strong class="text-red-800 text-sm font-bold block">Aset Terkunci</strong>
                <span class="text-red-700 text-xs">Aset ini sudah memiliki riwayat penyusutan. Anda tidak dapat mengubah nilai yang mempengaruhi akuntansi.</span>
            </div>
        </div>
    @endif

    <form action="{{ route('fixed-assets.update', $fixedAsset) }}" method="POST" id="asset-form">
        @csrf
        @method('PUT')
        
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
            
            <fieldset {{ $fixedAsset->depreciations()->exists() ? 'disabled' : '' }} class="group disabled:opacity-75 transition-opacity">
                
                {{-- Bagian 1 --}}
                <div class="px-6 py-4 border-b border-gray-200 bg-gray-50 flex items-center gap-2">
                    <div class="w-8 h-8 rounded-full bg-indigo-100 flex items-center justify-center text-indigo-600">
                        <span class="font-bold text-sm">1</span>
                    </div>
                    <h3 class="text-sm font-bold text-gray-700 uppercase tracking-wider">Informasi Aset & Pembelian</h3>
                </div>

                <div class="p-6 grid grid-cols-1 md:grid-cols-3 gap-6">
                    <div class="md:col-span-3">
                        <label for="asset_name" class="block text-xs font-bold text-gray-500 uppercase mb-1">Nama Aset <span class="text-red-500">*</span></label>
                        <input type="text" name="asset_name" id="asset_name" value="{{ old('asset_name', $fixedAsset->asset_name) }}" class="form-input block w-full rounded-lg border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm" required>
                    </div>
                    
                    <div>
                        <label for="purchase_date" class="block text-xs font-bold text-gray-500 uppercase mb-1">Tanggal Beli <span class="text-red-500">*</span></label>
                        <input type="date" name="purchase_date" id="purchase_date" value="{{ old('purchase_date', $fixedAsset->purchase_date->format('Y-m-d')) }}" class="form-input block w-full rounded-lg border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm" required>
                    </div>

                    <div>
                        <label for="purchase_cost_display" class="block text-xs font-bold text-gray-500 uppercase mb-1">Harga Beli (Rp) <span class="text-red-500">*</span></label>
                        <div class="relative rounded-md shadow-sm">
                            <div class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3">
                                <span class="text-gray-500 sm:text-sm">Rp</span>
                            </div>
                            <input type="text" id="purchase_cost_display" class="form-input block w-full rounded-lg border-gray-300 pl-10 focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm font-medium" required>
                            <input type="hidden" name="purchase_cost" id="purchase_cost" value="{{ old('purchase_cost', intval($fixedAsset->purchase_cost)) }}">
                        </div>
                    </div>

                    <div>
                        <label for="cash_bank_account_id" class="block text-xs font-bold text-gray-500 uppercase mb-1">Sumber Dana (Kredit) <span class="text-red-500">*</span></label>
                        <select name="cash_bank_account_id" id="cash_bank_account_id" class="select2 form-select block w-full rounded-lg border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm" required>
                            @foreach ($cashAccounts as $account)
                                <option value="{{ $account->account_id }}" @selected(old('cash_bank_account_id', $fixedAsset->cash_bank_account_id) == $account->account_id)>
                                    {{ $account->account_number }} - {{ $account->account_name }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="md:col-span-3">
                        <label for="description" class="block text-xs font-bold text-gray-500 uppercase mb-1">Deskripsi</label>
                        <textarea name="description" id="description" rows="2" class="form-textarea block w-full rounded-lg border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">{{ old('description', $fixedAsset->description) }}</textarea>
                    </div>
                </div>

                {{-- Bagian 2 --}}
                <div class="px-6 py-4 border-t border-b border-gray-200 bg-gray-50 flex items-center gap-2">
                    <div class="w-8 h-8 rounded-full bg-indigo-100 flex items-center justify-center text-indigo-600">
                        <span class="font-bold text-sm">2</span>
                    </div>
                    <h3 class="text-sm font-bold text-gray-700 uppercase tracking-wider">Informasi Akuntansi & Penyusutan</h3>
                </div>

                <div class="p-6 grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div>
                        <label for="fixed_asset_account_id" class="block text-xs font-bold text-gray-500 uppercase mb-1">Akun Aset (Debit) <span class="text-red-500">*</span></label>
                        <select name="fixed_asset_account_id" id="fixed_asset_account_id" class="select2 form-select block w-full rounded-lg border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm" required>
                            @foreach ($assetAccounts as $account)
                                <option value="{{ $account->account_id }}" @selected(old('fixed_asset_account_id', $fixedAsset->fixed_asset_account_id) == $account->account_id)>
                                    {{ $account->account_number }} - {{ $account->account_name }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div>
                        <label for="depreciation_expense_account_id" class="block text-xs font-bold text-gray-500 uppercase mb-1">Akun Beban Penyusutan (Debit) <span class="text-red-500">*</span></label>
                        <select name="depreciation_expense_account_id" id="depreciation_expense_account_id" class="select2 form-select block w-full rounded-lg border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm" required>
                            @foreach ($expenseAccounts as $account)
                                <option value="{{ $account->account_id }}" @selected(old('depreciation_expense_account_id', $fixedAsset->depreciation_expense_account_id) == $account->account_id)>
                                    {{ $account->account_number }} - {{ $account->account_name }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div>
                        <label for="accumulated_depreciation_account_id" class="block text-xs font-bold text-gray-500 uppercase mb-1">Akun Akumulasi Penyusutan (Kredit) <span class="text-red-500">*</span></label>
                        <select name="accumulated_depreciation_account_id" id="accumulated_depreciation_account_id" class="select2 form-select block w-full rounded-lg border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm" required>
                            @foreach ($contraAssetAccounts as $account)
                                <option value="{{ $account->account_id }}" @selected(old('accumulated_depreciation_account_id', $fixedAsset->accumulated_depreciation_account_id) == $account->account_id)>
                                    {{ $account->account_number }} - {{ $account->account_name }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div>
                        <label for="depreciation_method" class="block text-xs font-bold text-gray-500 uppercase mb-1">Metode Penyusutan <span class="text-red-500">*</span></label>
                        <select name="depreciation_method" id="depreciation_method" class="form-select block w-full rounded-lg border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm" required>
                            <option value="straight_line" @selected($fixedAsset->depreciation_method == 'straight_line')>Garis Lurus (Straight Line)</option>
                            <option value="double_declining" @selected($fixedAsset->depreciation_method == 'double_declining')>Saldo Menurun Ganda (Double Declining)</option>
                        </select>
                    </div>

                    <div>
                        <label for="useful_life_months" class="block text-xs font-bold text-gray-500 uppercase mb-1">Masa Manfaat (Bulan) <span class="text-red-500">*</span></label>
                        <input type="number" name="useful_life_months" id="useful_life_months" value="{{ old('useful_life_months', $fixedAsset->useful_life_months) }}" class="form-input block w-full rounded-lg border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm" required>
                    </div>

                    <div>
                        <label for="salvage_value_display" class="block text-xs font-bold text-gray-500 uppercase mb-1">Nilai Sisa (Residu) (Rp) <span class="text-red-500">*</span></label>
                        <div class="relative rounded-md shadow-sm">
                            <div class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3">
                                <span class="text-gray-500 sm:text-sm">Rp</span>
                            </div>
                            <input type="text" id="salvage_value_display" class="form-input block w-full rounded-lg border-gray-300 pl-10 focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm" required>
                            <input type="hidden" name="salvage_value" id="salvage_value" value="{{ old('salvage_value', intval($fixedAsset->salvage_value)) }}">
                        </div>
                    </div>
                </div>

                <div class="px-6 py-4 bg-gray-50 border-t border-gray-200 flex justify-end gap-3">
                    <a href="{{ route('fixed-assets.index') }}" class="px-4 py-2 bg-white border border-gray-300 rounded-lg text-gray-700 text-sm font-medium hover:bg-gray-50 shadow-sm transition">
                        Batal
                    </a>
                    <button type="submit" class="inline-flex items-center px-4 py-2 bg-indigo-600 border border-transparent rounded-lg font-bold text-sm text-white hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 shadow-md transition disabled:opacity-50 disabled:cursor-not-allowed" {{ $fixedAsset->depreciations()->exists() ? 'disabled' : '' }}>
                        <i class="material-icons text-lg mr-2">check_circle</i> Simpan Perubahan
                    </button>
                </div>

            </fieldset>
        </div>
    </form>
</div>
@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/autonumeric@4.6.0/dist/autoNumeric.min.js"></script>
<script>
    document.addEventListener('DOMContentLoaded', function () {
        $('.select2').select2({ theme: 'bootstrap-5', width: '100%' });

        const anOptions = { decimalPlaces: 0, digitGroupSeparator: '.', decimalCharacter: ',', minimumValue: '0' };
        
        const purchaseCostInput = document.getElementById('purchase_cost_display');
        const purchaseCostHidden = document.getElementById('purchase_cost');
        if(purchaseCostInput) {
            const anCost = new AutoNumeric(purchaseCostInput, anOptions);
            if(purchaseCostHidden.value) anCost.set(purchaseCostHidden.value);
            purchaseCostInput.addEventListener('autoNumeric:rawValueModified', e => purchaseCostHidden.value = e.detail.newRawValue);
        }

        const salvageInput = document.getElementById('salvage_value_display');
        const salvageHidden = document.getElementById('salvage_value');
        if(salvageInput) {
            const anSalvage = new AutoNumeric(salvageInput, anOptions);
            if(salvageHidden.value) anSalvage.set(salvageHidden.value);
            salvageInput.addEventListener('autoNumeric:rawValueModified', e => salvageHidden.value = e.detail.newRawValue);
        }
    });
</script>
@endpush
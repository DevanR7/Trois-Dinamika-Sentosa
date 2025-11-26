@extends('layouts.app')

@section('title', 'Catat Aset Tetap Baru')

@section('content')
<div class="max-w-4xl mx-auto pb-20 animate-enter">
    
    {{-- HEADER --}}
    <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center mb-8 gap-4">
        <div>
            <nav class="flex items-center gap-2 text-sm text-slate-500 mb-1">
                <a href="{{ route('fixed-assets.index') }}" class="hover:text-indigo-600 transition-colors">Aset Tetap</a>
                <span class="mx-2 text-slate-300">/</span>
                <span class="text-slate-800 font-semibold">Baru</span>
            </nav>
            <h1 class="text-2xl font-bold text-slate-800 tracking-tight">Catat Aset Baru</h1>
        </div>
        <a href="{{ route('fixed-assets.index') }}" 
           class="h-[48px] px-6 bg-white border border-slate-300 text-slate-600 rounded-lg text-sm font-bold hover:bg-slate-50 transition-all flex items-center justify-center gap-2 shadow-sm">
            <i class="material-icons text-[18px]">arrow_back</i> Kembali
        </a>
    </div>

    <form action="{{ route('fixed-assets.store') }}" method="POST" id="asset-form">
        @csrf
        
        <div class="dashboard-card p-0 overflow-hidden shadow-lg border-0 ring-1 ring-slate-900/5">
            
            {{-- Bagian 1: Informasi Aset --}}
            <div class="px-6 py-4 border-b border-slate-100 bg-slate-50/50 flex items-center gap-3">
                <div class="w-8 h-8 rounded-lg bg-indigo-100 flex items-center justify-center text-indigo-600 font-bold text-sm">1</div>
                <h3 class="text-sm font-bold text-slate-700 uppercase tracking-wide">Informasi Aset & Pembelian</h3>
            </div>

            <div class="p-6 grid grid-cols-1 md:grid-cols-3 gap-6">
                
                <div class="md:col-span-3">
                    <label for="asset_name" class="block text-xs font-bold text-slate-500 uppercase mb-1.5">Nama Aset <span class="text-red-500">*</span></label>
                    <input type="text" name="asset_name" id="asset_name" value="{{ old('asset_name') }}" class="form-input font-medium text-slate-800" placeholder="Contoh: Laptop Kantor A" required>
                    @error('asset_name') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label for="purchase_date" class="block text-xs font-bold text-slate-500 uppercase mb-1.5">Tanggal Beli <span class="text-red-500">*</span></label>
                    <input type="date" name="purchase_date" id="purchase_date" value="{{ old('purchase_date', now()->toDateString()) }}" class="form-input" required>
                    @error('purchase_date') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label for="purchase_cost_display" class="block text-xs font-bold text-slate-500 uppercase mb-1.5">Harga Beli (Rp) <span class="text-red-500">*</span></label>
                    <div class="relative">
                        <div class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3">
                            <span class="text-slate-400 font-bold text-sm">Rp</span>
                        </div>
                        {{-- TAMBAHKAN CLASS 'input-currency' AGAR AUTO INIT DARI APP.JS --}}
                        <input type="text" id="purchase_cost_display" class="form-input input-currency pl-10 font-mono text-slate-800 font-bold" placeholder="0" required>
                        <input type="hidden" name="purchase_cost" id="purchase_cost" value="{{ old('purchase_cost') }}">
                    </div>
                    @error('purchase_cost') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label for="cash_bank_account_id" class="block text-xs font-bold text-slate-500 uppercase mb-1.5">Sumber Dana (Kredit) <span class="text-red-500">*</span></label>
                    {{-- Gunakan select2-basic untuk auto init Select2 --}}
                    <select name="cash_bank_account_id" id="cash_bank_account_id" class="form-input select2-basic" required>
                        <option value="" disabled selected>-- Pilih Akun Kas/Bank --</option>
                        @foreach ($cashAccounts as $account)
                            <option value="{{ $account->account_id }}" @selected(old('cash_bank_account_id') == $account->account_id)>
                                {{ $account->account_number }} - {{ $account->account_name }}
                            </option>
                        @endforeach
                    </select>
                    @error('cash_bank_account_id') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                </div>

                <div class="md:col-span-3">
                    <label for="description" class="block text-xs font-bold text-slate-500 uppercase mb-1.5">Deskripsi</label>
                    <textarea name="description" id="description" rows="2" class="form-textarea" placeholder="Catatan opsional (misal: serial number, lokasi)">{{ old('description') }}</textarea>
                </div>

            </div>

            {{-- Bagian 2: Akuntansi --}}
            <div class="px-6 py-4 border-t border-b border-slate-100 bg-slate-50/50 flex items-center gap-3">
                <div class="w-8 h-8 rounded-lg bg-indigo-100 flex items-center justify-center text-indigo-600 font-bold text-sm">2</div>
                <h3 class="text-sm font-bold text-slate-700 uppercase tracking-wide">Informasi Akuntansi & Penyusutan</h3>
            </div>

            <div class="p-6 grid grid-cols-1 md:grid-cols-2 gap-6">
                
                <div>
                    <label for="fixed_asset_account_id" class="block text-xs font-bold text-slate-500 uppercase mb-1.5">Akun Aset (Debit) <span class="text-red-500">*</span></label>
                    <select name="fixed_asset_account_id" id="fixed_asset_account_id" class="form-input select2-basic" required>
                        <option value="" disabled selected>-- Pilih Akun Aset --</option>
                        @foreach ($assetAccounts as $account)
                            <option value="{{ $account->account_id }}" @selected(old('fixed_asset_account_id') == $account->account_id)>
                                {{ $account->account_number }} - {{ $account->account_name }}
                            </option>
                        @endforeach
                    </select>
                    <p class="mt-1 text-[10px] text-slate-400">Akun Neraca.</p>
                    @error('fixed_asset_account_id') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label for="depreciation_expense_account_id" class="block text-xs font-bold text-slate-500 uppercase mb-1.5">Akun Beban Penyusutan (Debit) <span class="text-red-500">*</span></label>
                    <select name="depreciation_expense_account_id" id="depreciation_expense_account_id" class="form-input select2-basic" required>
                        <option value="" disabled selected>-- Pilih Akun Beban --</option>
                        @foreach ($expenseAccounts as $account)
                            <option value="{{ $account->account_id }}" @selected(old('depreciation_expense_account_id') == $account->account_id)>
                                {{ $account->account_number }} - {{ $account->account_name }}
                            </option>
                        @endforeach
                    </select>
                    <p class="mt-1 text-[10px] text-slate-400">Akun Laba Rugi.</p>
                    @error('depreciation_expense_account_id') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label for="accumulated_depreciation_account_id" class="block text-xs font-bold text-slate-500 uppercase mb-1.5">Akun Akumulasi Penyusutan (Kredit) <span class="text-red-500">*</span></label>
                    <select name="accumulated_depreciation_account_id" id="accumulated_depreciation_account_id" class="form-input select2-basic" required>
                        <option value="" disabled selected>-- Pilih Akun Kontra --</option>
                        @foreach ($contraAssetAccounts as $account)
                            <option value="{{ $account->account_id }}" @selected(old('accumulated_depreciation_account_id') == $account->account_id)>
                                {{ $account->account_number }} - {{ $account->account_name }}
                            </option>
                        @endforeach
                    </select>
                    <p class="mt-1 text-[10px] text-slate-400">Akun Kontra-Aset.</p>
                    @error('accumulated_depreciation_account_id') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label for="depreciation_method" class="block text-xs font-bold text-slate-500 uppercase mb-1.5">Metode Penyusutan <span class="text-red-500">*</span></label>
                    <select name="depreciation_method" id="depreciation_method" class="form-input select2-basic" required>
                        <option value="straight_line" selected>Garis Lurus (Straight Line)</option>
                        <option value="double_declining">Saldo Menurun Ganda</option>
                    </select>
                </div>

                <div>
                    <label for="useful_life_months" class="block text-xs font-bold text-slate-500 uppercase mb-1.5">Masa Manfaat (Bulan) <span class="text-red-500">*</span></label>
                    <input type="number" name="useful_life_months" id="useful_life_months" value="{{ old('useful_life_months') }}" class="form-input" placeholder="Contoh: 48 (4 tahun)" required>
                    @error('useful_life_months') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label for="salvage_value_display" class="block text-xs font-bold text-slate-500 uppercase mb-1.5">Nilai Sisa (Residu) (Rp) <span class="text-red-500">*</span></label>
                    <div class="relative">
                        <div class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3">
                            <span class="text-slate-400 font-bold text-sm">Rp</span>
                        </div>
                        {{-- CLASS input-currency --}}
                        <input type="text" id="salvage_value_display" class="form-input input-currency pl-10 font-mono" placeholder="0" required>
                        <input type="hidden" name="salvage_value" id="salvage_value" value="{{ old('salvage_value', 0) }}">
                    </div>
                    @error('salvage_value') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                </div>

            </div>

            <div class="px-6 py-4 bg-slate-50 border-t border-slate-200 flex justify-end gap-3">
                <a href="{{ route('fixed-assets.index') }}" class="h-[48px] px-6 bg-white border border-slate-300 text-slate-600 rounded-lg text-sm font-bold hover:bg-slate-50 transition-all flex items-center justify-center shadow-sm">
                    Batal
                </a>
                <button type="submit" class="h-[48px] px-8 bg-indigo-600 hover:bg-indigo-700 text-white text-sm font-bold rounded-lg shadow-md transition-all flex items-center justify-center gap-2 group hover:-translate-y-0.5">
                    <i class="material-icons text-[20px] group-hover:scale-110 transition-transform">save</i> Simpan Aset
                </button>
            </div>
        </div>
    </form>
</div>
@endsection

@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function () {
        // Select2 & AutoNumeric sudah otomatis di-init oleh app.js karena class 'select2-basic' & 'input-currency'

        // --- Logic Sinkronisasi Input Hidden ---
        // Kita hanya perlu menangkap event update dari AutoNumeric (yang di-init global)
        // untuk mengisi input hidden sebelum submit
        
        const purchaseInput = document.getElementById('purchase_cost_display');
        const purchaseHidden = document.getElementById('purchase_cost');
        
        // Set Initial Value jika ada old input (karena global init mungkin reset valuenya)
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

        // Validasi Submit
        document.getElementById('asset-form').addEventListener('submit', function(e) {
            // Pastikan nilai hidden terisi (fallback jika event tidak ter-trigger)
            if (AutoNumeric.getAutoNumericElement(purchaseInput)) {
                purchaseHidden.value = AutoNumeric.getAutoNumericElement(purchaseInput).getNumber();
            }

            if(!purchaseHidden.value || purchaseHidden.value == 0) {
                e.preventDefault();
                window.showToast('Harga Beli tidak boleh kosong!', 'error');
                purchaseInput.focus();
            }
        });
        
        // Toast Notifikasi
        @if(session('success')) window.showToast("{{ session('success') }}", 'success'); @endif
        @if(session('error')) window.showToast("{{ session('error') }}", 'error'); @endif
    });
</script>
@endpush
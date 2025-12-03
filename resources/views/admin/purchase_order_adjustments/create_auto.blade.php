@extends('admin.layouts.app')

@section('title', 'Koreksi Otomatis PO')

@push('styles')
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
    <style>
        /* Styling khusus halaman ini agar tidak bentrok */
        .select2-container .select2-selection--single { height: 38px !important; }
        .select2-container--default .select2-selection--single .select2-selection__rendered { line-height: 38px !important; }
    </style>
@endpush

@section('content')
<div class="max-w-full mx-auto pb-20 animate-enter">
    
    {{-- HEADER --}}
    <div class="flex flex-col md:flex-row justify-between items-start md:items-center mb-6 gap-4">
        <div>
            <div class="flex items-center gap-2 text-xs font-bold text-slate-400 uppercase tracking-wider mb-1">
                <a href="{{ route('admin.purchase-order-adjustments.create') }}" class="hover:text-indigo-600 transition">Penyesuaian</a>
                <i class="material-icons text-[12px]">chevron_right</i>
                <span class="text-slate-600">Otomatis</span>
            </div>
            <h2 class="text-2xl font-bold text-slate-800 tracking-tight">
                Koreksi PO: <span class="text-indigo-600 font-mono ml-1">{{ $purchaseOrder->po_number }}</span>
            </h2>
        </div>
        <a href="{{ route('admin.purchase-order-adjustments.create') }}" class="inline-flex items-center px-4 py-2 bg-white border border-slate-300 rounded-lg text-slate-600 hover:bg-slate-50 hover:text-indigo-600 transition shadow-sm text-sm font-bold">
            <i class="material-icons text-sm mr-2">arrow_back</i> Kembali
        </a>
    </div>

    {{-- INFO BOX --}}
    <div class="mb-6 bg-blue-50 border border-blue-100 rounded-xl p-4 flex items-start gap-3 shadow-sm">
        <i class="material-icons text-blue-600 text-xl mt-0.5">auto_fix_high</i>
        <div>
            <h3 class="text-sm font-bold text-blue-900">Mode Revisi Otomatis</h3>
            <p class="text-sm text-blue-800 mt-1 leading-relaxed">
                Ubah Qty, Harga, atau Diskon di bawah ini sesuai kondisi nyata. Sistem akan otomatis menghitung selisihnya dan membuat <b>Nota Debit</b> atau <b>Nota Kredit</b> tanpa mengubah data PO asli yang sudah terkunci.
            </p>
        </div>
    </div>

    {{-- ALERT ERROR --}}
    @if ($errors->any())
        <div class="mb-6 bg-red-50 border border-red-200 rounded-xl p-4 flex items-start gap-3">
            <i class="material-icons text-red-500 text-xl">error</i>
            <div>
                <h3 class="text-sm font-bold text-red-800">Terdapat kesalahan input:</h3>
                <ul class="mt-1 list-disc list-inside text-sm text-red-700">
                    @foreach ($errors->all() as $error) <li>{{ $error }}</li> @endforeach
                </ul>
            </div>
        </div>
    @endif

    {{-- FORM START --}}
    <form action="{{ route('admin.purchase-order-adjustments.store.auto', $purchaseOrder->po_id) }}" method="POST" id="po-adj-form">
        @csrf

        <div class="space-y-6">
            
            {{-- 1. INFO PO ASLI (READONLY) --}}
            <div class="dashboard-card p-0 overflow-hidden bg-slate-50/50 border border-slate-200">
                <div class="px-6 py-4 border-b border-slate-200 flex items-center gap-2">
                    <i class="material-icons text-slate-400 text-sm">lock</i>
                    <h3 class="text-xs font-bold text-slate-500 uppercase tracking-wider">Data PO Asli (Referensi)</h3>
                </div>
                <div class="p-6 grid grid-cols-1 md:grid-cols-3 gap-6">
                    <div>
                        <label class="block text-[11px] font-bold text-slate-500 uppercase mb-1">Supplier</label>
                        <div class="font-bold text-slate-700">{{ $purchaseOrder->supplier->supplier_name }}</div>
                    </div>
                    <div>
                        <label class="block text-[11px] font-bold text-slate-500 uppercase mb-1">Tanggal Order</label>
                        <div class="font-bold text-slate-700">{{ optional($purchaseOrder->order_date)->format('d M Y') }}</div>
                    </div>
                    <div>
                        <label class="block text-[11px] font-bold text-slate-500 uppercase mb-1">Total Awal</label>
                        <div class="font-mono font-bold text-slate-700">Rp {{ number_format($purchaseOrder->total_amount, 0, ',', '.') }}</div>
                    </div>
                </div>
            </div>

            {{-- 2. EDIT ITEM (CARD TENGAH - Full Width) --}}
            <div class="dashboard-card p-0 overflow-hidden">
                <div class="px-6 py-4 border-b border-slate-100 bg-slate-50/50 flex flex-col md:flex-row justify-between items-center gap-4">
                    <div class="flex items-center gap-2">
                        <i class="material-icons text-indigo-500 text-sm">inventory</i>
                        <h3 class="text-sm font-bold text-slate-700 uppercase tracking-wider">Revisi Barang</h3>
                    </div>
                    
                    {{-- TOOLBAR DISKON GLOBAL --}}
                    <div class="flex items-center bg-white border border-slate-300 rounded-lg p-1 shadow-sm">
                        <div class="px-3 border-r border-slate-200 bg-slate-50 rounded-l flex items-center">
                            <span class="text-[10px] font-bold text-slate-500 uppercase">Bulk Disc</span>
                        </div>
                        <input type="text" id="bulk-chain-discount" 
                               class="text-xs w-32 px-3 py-1.5 focus:outline-none font-mono text-slate-700" 
                               placeholder="Cth: 61+10">
                        
                        <button type="button" id="btn-apply-selected" 
                                class="px-3 py-1.5 text-[10px] font-bold text-indigo-600 hover:bg-indigo-50 uppercase tracking-wide border-l border-slate-200 transition">
                            Selected
                        </button>
                        <button type="button" id="btn-apply-all" 
                                class="px-3 py-1.5 text-[10px] font-bold text-white bg-indigo-600 hover:bg-indigo-700 rounded-r uppercase tracking-wide transition">
                            Apply All
                        </button>
                    </div>
                </div>

                <div class="overflow-x-auto">
                    <table class="dashboard-table w-full">
                        <thead>
                            <tr class="bg-slate-50 border-b border-slate-200">
                                <th class="w-10 text-center p-2">
                                    <input type="checkbox" id="check-all-rows" class="rounded border-slate-300 text-indigo-600 focus:ring-indigo-500 cursor-pointer">
                                </th>
                                <th class="min-w-[300px] p-2 text-left text-xs font-bold text-slate-500 uppercase pl-4">Produk</th> 
                                <th class="w-32 text-center p-2 text-xs font-bold text-slate-500 uppercase">Qty</th> 
                                <th class="w-40 text-right p-2 text-xs font-bold text-slate-500 uppercase">Harga Beli (@)</th> 
                                <th class="w-48 text-center p-2 text-xs font-bold text-slate-500 uppercase">Diskon Item (%)</th> 
                                <th class="w-48 text-right p-2 text-xs font-bold text-slate-500 uppercase">Subtotal Revisi</th> 
                                <th class="w-10 text-center p-2"></th>
                            </tr>
                        </thead>
                        <tbody id="product-items">
                            {{-- JS Injects Existing Rows Here --}}
                        </tbody>
                    </table>
                </div>
                
                <div class="p-4 bg-slate-50 border-t border-slate-100 text-center">
                     <button type="button" onclick="addProductRow()" class="inline-flex items-center px-6 py-3 bg-white border border-indigo-200 text-indigo-600 text-sm font-bold rounded-full hover:bg-indigo-50 transition shadow-sm uppercase tracking-wide hover:shadow-md transform hover:-translate-y-0.5">
                        <i class="material-icons text-lg mr-2">add</i> Tambah Item Baru
                    </button>
                </div>
            </div>

            {{-- 3. RINGKASAN & ALASAN (CARD BAWAH) --}}
            <div class="dashboard-card p-6 border-t-4 border-indigo-500">
                <div class="grid grid-cols-1 lg:grid-cols-2 gap-10">
                    
                    {{-- Kolom Kiri: Alasan, Pajak, Penanganan Overpayment --}}
                    <div class="space-y-6">
                        <div>
                            <label class="block text-[11px] font-bold text-slate-500 uppercase mb-2 ml-1">Alasan Koreksi (Wajib)</label>
                            <textarea class="form-textarea w-full bg-yellow-50/30 border-yellow-100 focus:border-yellow-400 focus:ring-yellow-200" name="notes" id="notes" rows="4" placeholder="Jelaskan mengapa nilai PO berubah (misal: perubahan harga dari supplier)..." required>{{ old('notes') }}</textarea>
                        </div>

                        <div class="p-4 bg-slate-50 rounded-xl border border-slate-200">
                            <h4 class="text-xs font-bold text-slate-700 uppercase mb-3">Pengaturan Pajak</h4>
                            
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                <div>
                                    <label class="block text-[10px] font-bold text-slate-500 uppercase mb-1">Pajak (PPN)</label>
                                    {{-- Gunakan class po-adjust-select --}}
                                    <select name="tax_id" id="tax_id" class="po-adjust-select w-full">
                                        <option value="">-- Tanpa Pajak --</option>
                                        @foreach(\App\Models\Tax::where('is_active', true)->get() as $tax)
                                            <option value="{{ $tax->id }}" data-rate="{{ $tax->rate }}" {{ old('tax_id', $purchaseOrder->tax_id) == $tax->id ? 'selected' : '' }}>
                                                {{ $tax->name }} ({{ $tax->rate }}%)
                                            </option>
                                        @endforeach
                                    </select>
                                </div>
                                <div>
                                     <div class="flex items-center gap-2 mt-6">
                                        <input type="checkbox" id="use_custom_dpp_factor" name="use_custom_dpp_factor" value="1" class="rounded border-slate-300 text-indigo-600 focus:ring-indigo-500 h-4 w-4 cursor-pointer" {{ old('use_custom_dpp_factor', $purchaseOrder->use_custom_dpp_factor) ? 'checked' : '' }}>
                                        <label class="text-xs text-slate-600 cursor-pointer select-none" for="use_custom_dpp_factor">Override Faktor DPP</label>
                                    </div>
                                </div>
                            </div>

                            {{-- Input DPP Custom --}}
                            <div id="custom-dpp-container" class="mt-3 {{ old('use_custom_dpp_factor', $purchaseOrder->use_custom_dpp_factor) ? '' : 'hidden' }}">
                                <label class="block text-[10px] text-slate-500 mb-1">Faktor DPP Manual (Bisa Pecahan: 11/12)</label>
                                <input type="text" class="form-input text-xs h-8 w-full" name="custom_dpp_factor" id="custom_dpp_factor" value="{{ old('custom_dpp_factor', $purchaseOrder->custom_dpp_factor) }}" placeholder="0.91666666">
                            </div>
                        </div>

                        {{-- OPSI OVERPAYMENT --}}
                        <div class="p-4 bg-blue-50/50 border border-blue-100 rounded-xl">
                            <label class="block text-[10px] font-bold text-blue-800 uppercase mb-2">Jika Terjadi Kelebihan Bayar (Nota Kredit):</label>
                            <div class="space-y-2">
                                <label class="flex items-center cursor-pointer group">
                                    <input type="radio" name="overpayment_action" value="deposit" checked class="text-indigo-600 focus:ring-indigo-500 border-slate-300 h-4 w-4 mr-2">
                                    <span class="text-sm text-slate-700 font-medium group-hover:text-indigo-600">Simpan ke Deposit Supplier</span>
                                </label>
                                <label class="flex items-center cursor-pointer group">
                                    <input type="radio" name="overpayment_action" value="refund" class="text-indigo-600 focus:ring-indigo-500 border-slate-300 h-4 w-4 mr-2">
                                    <span class="text-sm text-slate-700 font-medium group-hover:text-indigo-600">Biarkan Minus (Refund Manual)</span>
                                </label>
                            </div>
                        </div>
                    </div>

                    {{-- Kolom Kanan: Kalkulasi --}}
                    <div class="bg-slate-50/50 rounded-xl p-6 border border-slate-200 space-y-4 sticky top-6">
                        <h4 class="text-xs font-bold text-slate-500 uppercase tracking-wider border-b border-slate-200 pb-2 mb-2">Kalkulasi Baru</h4>

                        {{-- Subtotal --}}
                        <div class="flex justify-between items-center text-sm">
                            <span class="text-slate-500 font-medium">Subtotal Barang</span>
                            <span class="font-bold text-slate-800 text-base" id="summary-subtotal">Rp 0</span>
                        </div>

                        {{-- Diskon Tambahan --}}
                        <div class="flex justify-between items-center">
                             <div class="flex items-center gap-2">
                                <input type="checkbox" id="apply_disc_fee" name="apply_disc_fee" value="1" class="rounded border-slate-300 text-indigo-600 h-4 w-4" {{ old('apply_disc_fee', $purchaseOrder->apply_disc_fee) ? 'checked' : '' }}>
                                <label for="apply_disc_fee" class="text-xs font-bold text-slate-500 uppercase cursor-pointer">Diskon Faktur / Fee</label>
                            </div>
                            <div id="disc-fee-inputs" class="flex items-center gap-2 {{ old('apply_disc_fee', $purchaseOrder->apply_disc_fee) ? '' : 'hidden' }}">
                                <input type="number" step="any" min="0" class="form-input text-xs w-16 text-right h-8" name="disc_fee_percent" id="disc_fee_percent" placeholder="%" value="{{ old('disc_fee_percent', $purchaseOrder->disc_fee_percent) }}">
                                <span class="text-slate-400">/</span>
                                
                                {{-- Display Input --}}
                                <input type="text" class="form-input text-xs w-28 text-right h-8 po-adjust-autonumeric" id="disc_fee_amount_display" placeholder="Rp">
                                {{-- Hidden Input --}}
                                <input type="hidden" name="disc_fee_amount" id="disc_fee_amount" value="{{ $purchaseOrder->disc_fee_amount }}">
                            </div>
                            <span class="text-red-500 font-medium text-sm" id="summary-disc">- Rp 0</span>
                        </div>

                        {{-- Pembulatan --}}
                        <div class="flex justify-between items-center">
                            <div class="flex items-center gap-2">
                                <input type="checkbox" id="apply_rounding_discount" name="apply_rounding_discount" value="1" class="rounded border-slate-300 text-indigo-600 h-4 w-4" {{ old('apply_rounding_discount', $purchaseOrder->apply_rounding_discount) ? 'checked' : '' }}>
                                <label for="apply_rounding_discount" class="text-xs font-bold text-slate-500 uppercase cursor-pointer">Pembulatan</label>
                            </div>
                            
                            <input type="text" class="form-input text-xs w-28 text-right h-8 {{ old('apply_rounding_discount', $purchaseOrder->apply_rounding_discount) ? '' : 'hidden' }} po-adjust-autonumeric" id="rounding_discount_amount_display" placeholder="Rp">
                            <input type="hidden" name="rounding_discount_amount" id="rounding_discount_amount" value="{{ $purchaseOrder->rounding_discount_amount }}">
                            
                            <span class="text-red-500 font-medium text-sm" id="summary-rounding">- Rp 0</span>
                        </div>

                        <div class="border-t border-dashed border-slate-300 my-2"></div>

                        {{-- Pajak Details --}}
                        <div class="flex justify-between items-center text-xs text-slate-500">
                            <span>DPP</span>
                            <span id="summary-dpp">Rp 0</span>
                        </div>
                        <div class="flex justify-between items-center text-sm text-slate-600">
                            <span>PPN (<span id="summary-tax-rate">0</span>%)</span>
                            <span class="font-bold" id="summary-ppn">Rp 0</span>
                        </div>

                        {{-- Ongkir --}}
                        <div class="flex justify-between items-center pt-2">
                            <label class="text-xs font-bold text-slate-500 uppercase">Ongkos Kirim</label>
                            <div class="w-32">
                                <input type="text" class="form-input text-right font-bold text-sm h-9 border-slate-300 focus:border-indigo-500 focus:ring-indigo-500 rounded po-adjust-autonumeric" id="shipping_amount_display" placeholder="0">
                                <input type="hidden" name="shipping_amount" id="shipping_amount" value="{{ $purchaseOrder->shipping_amount }}">
                            </div>
                        </div>

                        {{-- GRAND TOTAL --}}
                        <div class="bg-indigo-50 rounded-lg p-4 flex justify-between items-center mt-4 border border-indigo-100">
                            <span class="text-sm font-bold text-indigo-900 uppercase tracking-wider">Total Revisi</span>
                            <span class="text-2xl font-bold text-indigo-600 font-mono tracking-tight" id="summary-grand">Rp 0</span>
                        </div>

                    </div>
                </div>

                {{-- TOMBOL AKSI --}}
                <div class="flex justify-end gap-4 mt-8 pt-6 border-t border-slate-100">
                    <a href="{{ route('admin.purchase-order-adjustments.create') }}" class="px-6 py-3 bg-white border border-slate-300 text-slate-600 rounded-lg text-sm font-bold hover:bg-slate-50 transition shadow-sm">
                         Batal
                    </a>
                    <button type="submit" id="submit-btn" class="px-8 py-3 bg-indigo-600 hover:bg-indigo-700 text-white rounded-lg text-sm font-bold shadow-lg hover:shadow-xl transition transform hover:-translate-y-0.5 flex items-center gap-2">
                        <i class="material-icons text-lg">save</i> Simpan Koreksi
                    </button>
                </div>
            </div>

        </div>
    </form>
</div>

{{-- TEMPLATE ROW --}}
<template id="product-row-template">
    <tr class="group transition-colors hover:bg-slate-50 border-b border-slate-100 last:border-0">
        <td class="text-center align-middle p-2">
            <input type="checkbox" class="row-checkbox rounded border-slate-300 text-indigo-600 focus:ring-indigo-500 cursor-pointer h-4 w-4">
        </td>
        <td class="p-2 align-top pl-4">
            <select class="product-select text-sm" style="width: 100%;" required>
                <option value="" data-unit="-" disabled selected>-- Cari Produk --</option>
                @foreach ($products as $product)
                    <option value="{{ $product->product_id }}"
                            data-unit="{{ $product->unit->name ?? '' }}"
                            data-default-discounts='@json($product->default_discounts ?? [])'
                            data-default-price="{{ $product->purchase_price ?? 0 }}">
                        {{ $product->product_name }}
                    </option>
                @endforeach
            </select>
            <div class="mt-1 flex items-center gap-2">
                 <span class="text-[10px] text-slate-400 bg-slate-100 px-1.5 rounded unit-display">-</span>
            </div>
        </td>
        <td class="p-2 align-top">
            <div class="relative">
                <input type="number" class="form-input quantity w-full text-center text-sm font-bold h-10" value="1" min="0.01" step="0.01" required>
            </div>
        </td>
        <td class="p-2 align-top">
            {{-- Display Input --}}
            <input type="text" class="form-input purchase-price-formatted w-full text-right text-sm font-medium h-10" placeholder="0">
            {{-- Hidden Input (Value Murni) --}}
            <input type="hidden" class="purchase-price-hidden" value="0">
        </td>
        <td class="p-2 align-top text-center">
            <div class="discount-wrapper space-y-1 flex flex-col items-center">
                <div class="flex items-center justify-center gap-1 relative w-full">
                     <input type="number" step="any" min="0" max="100" class="discount-percentage form-input text-xs w-20 text-center h-8 p-1" name="products[INDEX][discounts][]" placeholder="0">
                     <span class="text-xs text-slate-400 absolute right-6">%</span>
                </div>
            </div>
            <button type="button" class="text-[10px] text-indigo-500 hover:underline mt-1 add-disc-btn font-bold flex items-center justify-center w-full gap-1">
                <i class="material-icons text-[10px]">add</i> Lapis
            </button>
        </td>
        <td class="p-2 align-middle text-right">
            <span class="subtotal font-bold text-slate-700 text-sm">Rp 0</span>
        </td>
        <td class="p-2 align-middle text-center">
            <button type="button" class="remove-product-btn text-slate-300 hover:text-red-500 transition p-1 rounded-full hover:bg-red-50">
                <i class="material-icons text-lg">delete</i>
            </button>
        </td>
    </tr>
</template>

@endsection

@push('scripts')
<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/autonumeric@4.6.0/dist/autoNumeric.min.js"></script>

<script>
    document.addEventListener('DOMContentLoaded', function () {
        // --- 1. DATA EXISTING DARI PO LAMA ---
        const existingPoItems = @json($purchaseOrder->items->load('discounts') ?? []);

        const form = document.getElementById('po-adj-form');
        const productItemsContainer = document.getElementById('product-items');
        const productRowTemplate = document.getElementById('product-row-template');

        // Bulk Discount Elements
        const inputBulkChain = document.getElementById('bulk-chain-discount');
        const btnApplySelected = document.getElementById('btn-apply-selected');
        const btnApplyAll = document.getElementById('btn-apply-all');
        const checkAllRows = document.getElementById('check-all-rows');

        // Elements Kalkulasi
        const inputDiscFeePercent = document.getElementById('disc_fee_percent');
        const inputDiscFeeAmountDisplay = document.getElementById('disc_fee_amount_display');
        const inputDiscFeeAmountHidden = document.getElementById('disc_fee_amount');

        const inputRoundingAmountDisplay = document.getElementById('rounding_discount_amount_display');
        const inputRoundingAmountHidden = document.getElementById('rounding_discount_amount');

        const inputShippingDisplay = document.getElementById('shipping_amount_display');
        const inputShippingHidden = document.getElementById('shipping_amount');

        const inputTaxId = document.getElementById('tax_id');
        
        const checkboxDiscFee = document.getElementById('apply_disc_fee');
        const checkboxRounding = document.getElementById('apply_rounding_discount');
        const checkboxCustomDpp = document.getElementById('use_custom_dpp_factor');
        const inputCustomDpp = document.getElementById('custom_dpp_factor');

        // Elements Display
        const displaySubtotal = document.getElementById('summary-subtotal');
        const displayDisc = document.getElementById('summary-disc');
        const displayRounding = document.getElementById('summary-rounding');
        const displayDpp = document.getElementById('summary-dpp');
        const displayPpn = document.getElementById('summary-ppn');
        const displayTaxRate = document.getElementById('summary-tax-rate');
        const displayGrand = document.getElementById('summary-grand');

        let productIndex = 0;

        // --- 1. HELPER FUNCTIONS ---
        function formatCurrency(n) { return new Intl.NumberFormat('id-ID', { style: 'currency', currency: 'IDR', minimumFractionDigits: 0 }).format(Math.round(n || 0)); }
        function parseNumericForInput(str) { if (!str) return 0; return parseFloat(String(str).replace(/[^\d\-\.\,]/g, '').replace(/,/g, '.')) || 0; }
        function getAllRows() { return Array.from(productItemsContainer.querySelectorAll('tr')); }
        
        // FUNGSI PINTAR: Konversi "11/12" ke 0.9166...
        function parseFractionOrNumber(val) {
            if (!val) return 1;
            const str = String(val).trim();
            if (str.includes('/')) {
                const parts = str.split('/');
                const num = parseFloat(parts[0]);
                const den = parseFloat(parts[1]);
                if (den !== 0 && !isNaN(num) && !isNaN(den)) return num / den;
            }
            return parseFloat(str.replace(',', '.')) || 1;
        }

        // Helper Init AutoNumeric (Display <-> Hidden Binding)
        function initBoundAutoNumeric(displayElement, hiddenElement) {
            if (!displayElement) return;
            const an = new AutoNumeric(displayElement, { 
                decimalCharacter: ',', 
                digitGroupSeparator: '.', 
                decimalPlaces: 0, 
                minimumValue: '0', 
                emptyInputBehavior: 'zero',
                currencySymbol: '', 
                unformatOnSubmit: false 
            });
            
            // Saat display diketik, update hidden input dengan raw value
            displayElement.addEventListener('autoNumeric:rawValueModified', e => {
                hiddenElement.value = e.detail.newRawValue;
                calculateTotals();
            });

            // Initial Set value from hidden (jika ada value di hidden saat load)
            if(hiddenElement.value) {
                an.set(hiddenElement.value);
            }
            return an;
        }

        // Init AutoNumeric untuk field header
        const anDiscFee = initBoundAutoNumeric(inputDiscFeeAmountDisplay, inputDiscFeeAmountHidden);
        const anRounding = initBoundAutoNumeric(inputRoundingAmountDisplay, inputRoundingAmountHidden);
        const anShipping = initBoundAutoNumeric(inputShippingDisplay, inputShippingHidden);

        // --- 2. INIT SELECT2 (Manual) ---
        $('.po-adjust-select').select2({ width: '100%', dropdownCssClass: 'select2-dropdown-clean', allowClear: true, placeholder: '-- Pilih --' });

        // --- 3. ROW MANAGEMENT ---
        window.addProductRow = function(data = null) {
            const clone = productRowTemplate.content.cloneNode(true);
            const tr = clone.querySelector('tr');
            
            // Naming Inputs
            tr.querySelector('.product-select').name = `products[${productIndex}][product_id]`;
            tr.querySelector('.quantity').name = `products[${productIndex}][quantity]`;
            tr.querySelector('.purchase-price-hidden').name = `products[${productIndex}][price_per_unit]`;
            
            // Set Name Initial Discount
            const initialDiscInput = tr.querySelector('.discount-percentage');
            initialDiscInput.name = `products[${productIndex}][discounts][]`;

            productItemsContainer.appendChild(tr);
            
            // Init Plugins for Row
            const select = $(tr.querySelector('.product-select'));
            // Gunakan custom styling, jangan theme bootstrap-5 agar panah custom CSS muncul
            select.select2({ width: '100%', placeholder: '-- Pilih Produk --', dropdownCssClass: 'select2-dropdown-clean' });

            const priceInput = tr.querySelector('.purchase-price-formatted');
            const priceHidden = tr.querySelector('.purchase-price-hidden');
            const qtyInput = tr.querySelector('.quantity');
            
            // Init AutoNumeric Row (Binding ke Hidden)
            const anPrice = new AutoNumeric(priceInput, { 
                decimalCharacter: ',', 
                digitGroupSeparator: '.', 
                decimalPlaces: 0, 
                minimumValue: '0',
                unformatOnSubmit: false
            });

            // Events
            select.on('select2:select', function(e) {
                const option = e.params.data.element;
                const unit = option.dataset.unit || '-';
                const price = parseFloat(option.dataset.defaultPrice) || 0;
                
                tr.querySelector('.unit-display').textContent = unit;
                if (priceInput.value == 0 || priceInput.value == '') {
                    anPrice.set(price);
                    priceHidden.value = price;
                }
                calculateTotals();
            });

            priceInput.addEventListener('autoNumeric:rawValueModified', e => {
                priceHidden.value = e.detail.newRawValue;
                calculateTotals();
            });

            qtyInput.addEventListener('input', calculateTotals);
            initialDiscInput.addEventListener('input', calculateTotals);
            
            tr.querySelector('.add-disc-btn').addEventListener('click', () => {
                addDiscountInputToRow(tr);
            });

            tr.querySelector('.remove-product-btn').addEventListener('click', function() {
                select.select2('destroy');
                tr.remove();
                calculateTotals();
            });

            // --- POPULATE EXISTING DATA ---
            if (data) {
                select.val(data.product_id).trigger('change');
                qtyInput.value = data.quantity;
                anPrice.set(data.price_per_unit);
                priceHidden.value = data.price_per_unit;
                
                // Handle Discounts
                if (data.discounts && data.discounts.length > 0) {
                    initialDiscInput.value = data.discounts[0].percentage;
                    for (let i = 1; i < data.discounts.length; i++) {
                        addDiscountInputToRow(tr, data.discounts[i].percentage);
                    }
                }
                
                const selectedOpt = select.find(':selected')[0];
                if(selectedOpt) tr.querySelector('.unit-display').textContent = selectedOpt.dataset.unit || '-';
            }

            productIndex++;
        };

        function addDiscountInputToRow(tr, value = '') {
            const discContainer = tr.querySelector('.discount-wrapper');
            const nameAttr = tr.querySelector('.product-select').name; 
            const rowIndexMatch = nameAttr.match(/products\[(\d+)\]/);
            const rowIndex = rowIndexMatch ? rowIndexMatch[1] : productIndex;

            const div = document.createElement('div');
            div.className = 'flex items-center justify-center gap-1 relative w-full';
            div.innerHTML = `
                <input type="number" step="any" min="0" max="100" class="discount-percentage form-input text-xs w-20 text-center h-8 p-1" name="products[${rowIndex}][discounts][]" placeholder="0" value="${value}">
                <span class="text-xs text-slate-400 absolute right-6">%</span>
                <button type="button" class="text-red-400 hover:text-red-600 absolute -right-4" onclick="this.parentElement.remove(); calculateTotals();">&times;</button>
            `;
            div.querySelector('input').addEventListener('input', calculateTotals);
            discContainer.appendChild(div);
            calculateTotals();
        }

        // --- 4. BULK DISCOUNT ---
        function parseChainDiscount(str) {
            if(!str) return [];
            return str.split(/[,+]/).map(s => parseFloat(s.trim())).filter(n => !isNaN(n) && n > 0 && n <= 100);
        }
        
        function applyBulkDiscountToRows(rows) {
            const inputStr = inputBulkChain.value;
            const discounts = parseChainDiscount(inputStr);
            if (discounts.length === 0) {
                Swal.fire('Format Salah', 'Gunakan format seperti: 10+5', 'warning');
                return;
            }
            rows.forEach(tr => {
                const container = tr.querySelector('.discount-wrapper');
                container.innerHTML = ''; 
                discounts.forEach(val => {
                    addDiscountInputToRow(tr, val);
                });
            });
            calculateTotals();
        }

        btnApplySelected.addEventListener('click', function() {
            const checkedRows = Array.from(productItemsContainer.querySelectorAll('tr')).filter(tr => tr.querySelector('.row-checkbox').checked);
            if(checkedRows.length === 0) return;
            applyBulkDiscountToRows(checkedRows);
        });

        btnApplyAll.addEventListener('click', function() {
            const allRows = Array.from(productItemsContainer.querySelectorAll('tr'));
            if(allRows.length === 0) return;
            applyBulkDiscountToRows(allRows);
        });

        checkAllRows.addEventListener('change', function() {
            const isChecked = this.checked;
            productItemsContainer.querySelectorAll('.row-checkbox').forEach(cb => cb.checked = isChecked);
        });

        // --- 5. CALCULATION ENGINE ---
        window.calculateTotals = function() {
            let subtotalGlobal = 0;

            productItemsContainer.querySelectorAll('tr').forEach(row => {
                const qty = parseFloat(row.querySelector('.quantity').value) || 0;
                const price = parseFloat(row.querySelector('.purchase-price-hidden').value) || 0; // Use hidden value
                
                let netPrice = price;
                row.querySelectorAll('.discount-percentage').forEach(d => {
                    const disc = parseFloat(d.value) || 0;
                    if(disc > 0) netPrice = netPrice * (1 - (disc / 100));
                });

                const rowSubtotal = qty * netPrice;
                row.querySelector('.subtotal').textContent = formatCurrency(rowSubtotal);
                subtotalGlobal += rowSubtotal;
            });

            let discFeeVal = 0;
            if (checkboxDiscFee.checked) {
                const pct = parseFloat(inputDiscFeePercent.value) || 0;
                const amt = parseFloat(inputDiscFeeAmountHidden.value) || 0; 
                
                if (pct > 0) discFeeVal = subtotalGlobal * (pct / 100);
                else if (amt > 0) discFeeVal = amt;
            }

            const roundingVal = checkboxRounding.checked ? (parseFloat(inputRoundingAmountHidden.value) || 0) : 0;
            let taxableBase = Math.max(0, subtotalGlobal - discFeeVal - roundingVal);

            let dppVal = taxableBase;
            let ppnVal = 0;
            const selectedTaxOption = $(inputTaxId).select2('data')[0];
            const taxRate = (selectedTaxOption && selectedTaxOption.element) ? parseFloat(selectedTaxOption.element.dataset.rate) : 0;

            if (taxRate > 0) {
                let dppFactor = 1; 
                if (checkboxCustomDpp.checked) {
                    dppFactor = parseFractionOrNumber(inputCustomDpp.value);
                }
                dppVal = taxableBase * dppFactor;
                ppnVal = dppVal * (taxRate / 100);
            }

            const shippingVal = parseFloat(inputShippingHidden.value) || 0;
            const grandTotal = taxableBase + ppnVal + shippingVal;

            displaySubtotal.textContent = formatCurrency(subtotalGlobal);
            displayDisc.textContent = `(-) ${formatCurrency(discFeeVal)}`;
            displayRounding.textContent = `(-) ${formatCurrency(roundingVal)}`;
            displayDpp.textContent = formatCurrency(dppVal);
            displayPpn.textContent = `(+) ${formatCurrency(ppnVal)}`;
            displayTaxRate.textContent = taxRate;
            displayGrand.textContent = formatCurrency(grandTotal);
            
            document.getElementById('disc-fee-inputs').classList.toggle('hidden', !checkboxDiscFee.checked);
            document.getElementById('rounding_discount_amount_display').classList.toggle('hidden', !checkboxRounding.checked);
            document.getElementById('custom-dpp-container').classList.toggle('hidden', !checkboxCustomDpp.checked);
        };

        // --- 6. EVENT LISTENERS ---
        const calcTriggers = [inputDiscFeePercent, inputCustomDpp];
        calcTriggers.forEach(el => {
            if(el) el.addEventListener('input', calculateTotals);
        });
        $(inputTaxId).on('select2:select select2:unselect', calculateTotals);

        [checkboxDiscFee, checkboxRounding, checkboxCustomDpp].forEach(el => {
            el.addEventListener('change', calculateTotals);
        });

        // --- 7. LOAD & SUBMIT ---
        if (existingPoItems.length > 0) {
            existingPoItems.forEach(item => addProductRow(item));
        } else {
            addProductRow();
        }

        form.addEventListener('submit', function(e) {
            if (getAllRows().length === 0) {
                e.preventDefault();
                Swal.fire('Perhatian', 'Harap tambahkan minimal satu item.', 'warning');
                return;
            }
            if (checkboxCustomDpp.checked && inputCustomDpp.value) {
                inputCustomDpp.value = parseFractionOrNumber(inputCustomDpp.value);
            }
            const submitBtn = document.getElementById('submit-btn');
            if(submitBtn) {
                submitBtn.disabled = true;
                submitBtn.innerHTML = '<i class="material-icons animate-spin text-sm">sync</i> Menyimpan...';
            }
        });
        
        setTimeout(calculateTotals, 500);
    });
</script>
@endpush
@extends('admin.layouts.app')

@section('title', 'Buat PO Baru')

@push('styles')
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
    <style>
        /* Fix conflict styles */
        .select2-container .select2-selection--single { height: 38px !important; }
        .select2-container--default .select2-selection--single .select2-selection__rendered { line-height: 38px !important; }
    </style>
@endpush

@section('content')

<div class="max-w-full mx-auto pb-20 animate-enter">

    {{-- HEADER --}}
    <div class="flex flex-col md:flex-row justify-between items-start md:items-center mb-6 gap-4">
        <div>
            <h2 class="text-2xl font-bold text-slate-800 tracking-tight">Buat Purchase Order Baru</h2>
            <p class="text-slate-500 text-sm mt-1">Buat pesanan pembelian baru ke supplier.</p>
        </div>
        <a href="{{ route('admin.purchase-orders.index') }}" class="inline-flex items-center px-4 py-2 bg-white border border-slate-300 rounded-lg text-slate-600 hover:bg-slate-50 hover:text-indigo-600 transition shadow-sm text-sm font-bold">
            <i class="material-icons text-sm mr-2">arrow_back</i> Kembali
        </a>
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

    <form action="{{ route('admin.purchase-orders.store') }}" method="POST" id="po-form">
        @csrf

        <div class="space-y-6">
            
            {{-- 1. INFO PESANAN --}}
            <div class="dashboard-card p-0 overflow-hidden">
                <div class="bg-slate-50/50 px-6 py-4 border-b border-slate-100">
                    <h3 class="text-xs font-bold text-slate-500 uppercase tracking-wider flex items-center gap-2">
                        <i class="material-icons text-indigo-500 text-sm">info</i> Informasi Supplier & Tanggal
                    </h3>
                </div>
                <div class="p-6 grid grid-cols-1 md:grid-cols-12 gap-6">
                    <div class="md:col-span-4">
                        <label class="block text-[11px] font-bold text-slate-500 uppercase mb-2 ml-1">Supplier <span class="text-red-500">*</span></label>
                        {{-- HAPUS class 'select2-basic' agar tidak bentrok dengan app.js --}}
                        <select name="supplier_id" id="supplier_id" class="w-full po-select2" required>
                            <option value="" disabled selected>-- Pilih Supplier --</option>
                            @foreach ($suppliers as $supplier)
                                <option value="{{ $supplier->supplier_id }}">{{ $supplier->supplier_name }}</option>
                            @endforeach
                        </select>
                    </div>
                    
                    <div class="md:col-span-3">
                        <label class="block text-[11px] font-bold text-slate-500 uppercase mb-2 ml-1">Tanggal Pesan <span class="text-red-500">*</span></label>
                        <input type="date" class="form-input" id="order_date" name="order_date" value="{{ now()->format('Y-m-d') }}" required>
                    </div>
                    
                    <div class="md:col-span-2">
                        <label class="block text-[11px] font-bold text-slate-500 uppercase mb-2 ml-1">Jatuh Tempo</label>
                        <input type="date" class="form-input" id="due_date" name="due_date">
                    </div>

                    <div class="md:col-span-3">
                        <label class="block text-[11px] font-bold text-slate-500 uppercase mb-2 ml-1">Dipesan Oleh</label>
                        <select name="requester_user_id" id="requester_user_id" class="w-full po-select2">
                            <option value="">-- Pembelian Umum --</option>
                            @foreach ($users as $user)
                                <option value="{{ $user->user_id }}">{{ $user->full_name }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>
            </div>

            {{-- 2. ITEM PESANAN --}}
            <div class="dashboard-card p-0 overflow-hidden">
                <div class="px-6 py-4 border-b border-slate-100 bg-slate-50/50 flex flex-col md:flex-row justify-between items-center gap-4">
                    <div class="flex items-center gap-2">
                        <i class="material-icons text-indigo-500 text-sm">inventory_2</i>
                        <h3 class="text-sm font-bold text-slate-700 uppercase tracking-wider">Daftar Barang</h3>
                    </div>

                    {{-- TOOLBAR DISKON GLOBAL --}}
                    <div class="flex items-center bg-white border border-slate-300 rounded-lg p-1 shadow-sm">
                        <div class="px-3 border-r border-slate-200 bg-slate-50 rounded-l flex items-center">
                            <span class="text-[10px] font-bold text-slate-500 uppercase">Bulk Disc</span>
                        </div>
                        <input type="text" id="bulk-chain-discount" 
                               class="text-xs w-32 px-3 py-1.5 focus:outline-none font-mono text-slate-700" 
                               placeholder="Contoh: 10+5">
                        
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
                                <th class="w-40 text-right p-2 text-xs font-bold text-slate-500 uppercase">Harga Satuan (Rp)</th> 
                                <th class="w-48 text-center p-2 text-xs font-bold text-slate-500 uppercase">Diskon Item (%)</th> 
                                <th class="w-48 text-right p-2 text-xs font-bold text-slate-500 uppercase">Subtotal</th> 
                                <th class="w-10 text-center p-2"></th>
                            </tr>
                        </thead>
                        <tbody id="product-items">
                            {{-- JS Injects Rows Here --}}
                        </tbody>
                    </table>
                </div>
                
                <div class="p-4 bg-slate-50 border-t border-slate-100 text-center">
                     <button type="button" onclick="addProductRow()" class="inline-flex items-center px-6 py-3 bg-white border border-indigo-200 text-indigo-600 text-sm font-bold rounded-full hover:bg-indigo-50 transition shadow-sm uppercase tracking-wide hover:shadow-md transform hover:-translate-y-0.5">
                        <i class="material-icons text-lg mr-2">add</i> Tambah Item Baris
                    </button>
                </div>
            </div>

            {{-- 3. RINGKASAN BIAYA --}}
            <div class="dashboard-card p-6 border-t-4 border-indigo-500">
                <div class="grid grid-cols-1 lg:grid-cols-2 gap-10">
                    
                    <div class="space-y-6">
                        <div>
                            <label class="block text-[11px] font-bold text-slate-500 uppercase mb-2 ml-1">Catatan PO</label>
                            <textarea class="form-textarea w-full bg-yellow-50/30 border-yellow-100 focus:border-yellow-400 focus:ring-yellow-200" name="notes" id="notes" rows="4" placeholder="Instruksi pengiriman..."></textarea>
                        </div>

                        <div class="p-4 bg-slate-50 rounded-xl border border-slate-200">
                            <h4 class="text-xs font-bold text-slate-700 uppercase mb-3">Pengaturan Pajak</h4>
                            
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                <div>
                                    <label class="block text-[10px] font-bold text-slate-500 uppercase mb-1">Pajak (PPN)</label>
                                    <select name="tax_id" id="tax_id" class="w-full po-select2">
                                        <option value="" selected>-- Tanpa Pajak --</option>
                                        @foreach(\App\Models\Tax::where('is_active', true)->get() as $tax)
                                            <option value="{{ $tax->id }}" data-rate="{{ $tax->rate }}">{{ $tax->name }} ({{ $tax->rate }}%)</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div>
                                     <div class="flex items-center gap-2 mt-6">
                                        <input type="checkbox" id="use_custom_dpp_factor" name="use_custom_dpp_factor" value="1" class="rounded border-slate-300 text-indigo-600 focus:ring-indigo-500 h-4 w-4 cursor-pointer">
                                        <label class="text-xs text-slate-600 cursor-pointer select-none" for="use_custom_dpp_factor">Override Faktor DPP</label>
                                     </div>
                                </div>
                            </div>

                            <div id="custom-dpp-container" class="mt-3 hidden">
                                <label class="block text-[10px] text-slate-500 mb-1">Faktor DPP Manual (Contoh: 11/12)</label>
                                <input type="text" class="form-input text-xs h-8 w-full" name="custom_dpp_factor" id="custom_dpp_factor" placeholder="0.91666666">
                            </div>
                        </div>
                    </div>

                    <div class="bg-slate-50/50 rounded-xl p-6 border border-slate-200 space-y-4 sticky top-6">
                        
                        <div class="flex justify-between items-center text-sm">
                            <span class="text-slate-500 font-medium">Subtotal Barang</span>
                            <span class="font-bold text-slate-800 text-base" id="summary-subtotal">Rp 0</span>
                        </div>

                        {{-- Diskon Faktur --}}
                        <div class="flex justify-between items-center">
                             <div class="flex items-center gap-2">
                                <input type="checkbox" id="apply_disc_fee" name="apply_disc_fee" value="1" class="rounded border-slate-300 text-indigo-600 h-4 w-4">
                                <label for="apply_disc_fee" class="text-xs font-bold text-slate-500 uppercase cursor-pointer">Diskon Faktur / Fee</label>
                            </div>
                            <div id="disc-fee-inputs" class="flex items-center gap-2 hidden">
                                <input type="number" step="any" min="0" class="form-input text-xs w-16 text-right h-8" name="disc_fee_percent" id="disc_fee_percent" placeholder="%">
                                <span class="text-slate-400">/</span>
                                
                                {{-- Hapus class input-currency agar tidak bentrok dengan app.js --}}
                                <input type="text" class="form-input text-xs w-28 text-right h-8 po-autonumeric" id="disc_fee_amount_display" placeholder="Rp">
                                <input type="hidden" name="disc_fee_amount" id="disc_fee_amount">
                            </div>
                            <span class="text-red-500 font-medium text-sm" id="summary-disc">- Rp 0</span>
                        </div>

                        {{-- Pembulatan --}}
                        <div class="flex justify-between items-center">
                            <div class="flex items-center gap-2">
                                <input type="checkbox" id="apply_rounding_discount" name="apply_rounding_discount" value="1" class="rounded border-slate-300 text-indigo-600 h-4 w-4">
                                <label for="apply_rounding_discount" class="text-xs font-bold text-slate-500 uppercase cursor-pointer">Pembulatan</label>
                            </div>
                            
                            <input type="text" class="form-input text-xs w-28 text-right h-8 hidden po-autonumeric" id="rounding_discount_amount_display" placeholder="Rp">
                            <input type="hidden" name="rounding_discount_amount" id="rounding_discount_amount">
                            
                            <span class="text-red-500 font-medium text-sm" id="summary-rounding">- Rp 0</span>
                        </div>

                        <div class="border-t border-dashed border-slate-300 my-2"></div>

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
                                <input type="text" class="form-input text-right font-bold text-sm h-9 border-slate-300 focus:border-indigo-500 focus:ring-indigo-500 rounded po-autonumeric" id="shipping_amount_display" placeholder="0">
                                <input type="hidden" name="shipping_amount" id="shipping_amount">
                            </div>
                        </div>

                        <div class="bg-indigo-50 rounded-lg p-4 flex justify-between items-center mt-4 border border-indigo-100">
                            <span class="text-sm font-bold text-indigo-900 uppercase tracking-wider">Grand Total</span>
                            <span class="text-2xl font-bold text-indigo-600 font-mono tracking-tight" id="summary-grand">Rp 0</span>
                        </div>

                    </div>
                </div>

                {{-- TOMBOL AKSI --}}
                <div class="flex justify-end gap-4 mt-8 pt-6 border-t border-slate-100">
                    <button type="button" onclick="location.reload()" class="px-6 py-3 bg-white border border-red-200 text-red-600 rounded-lg text-sm font-bold hover:bg-red-50 transition shadow-sm flex items-center gap-2">
                         <i class="material-icons text-lg">refresh</i> Reset Form
                    </button>
                    <button type="submit" id="submit-btn" class="px-8 py-3 bg-indigo-600 hover:bg-indigo-700 text-white rounded-lg text-sm font-bold shadow-lg hover:shadow-xl transition transform hover:-translate-y-0.5 flex items-center gap-2">
                        <i class="material-icons text-lg">save</i> Simpan Pesanan
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
            <select class="product-select" style="width: 100%;" required>
                <option value="" data-unit="-" disabled selected>-- Cari Produk --</option>
                @foreach ($products as $product)
                    <option value="{{ $product->product_id }}"
                            data-unit="{{ $product->unit->name ?? '' }}"
                            data-default-price="{{ $product->purchase_price ?? 0 }}">
                        {{ $product->product_name }}
                    </option>
                @endforeach
            </select>
            <div class="mt-1 flex items-center gap-2">
                 <span class="text-[10px] text-slate-400 bg-slate-100 px-1.5 rounded unit-display">-</span>
                 <label class="flex items-center gap-1 cursor-pointer">
                    <input type="checkbox" class="update-master-price h-3 w-3 rounded border-slate-300 text-indigo-600" value="1">
                    <span class="text-[9px] text-slate-500">Update Master Harga</span>
                 </label>
            </div>
        </td>
        <td class="p-2 align-top">
            <div class="relative">
                <input type="number" class="form-input quantity w-full text-center text-sm font-bold h-10" value="1" min="0.01" step="0.01" required>
            </div>
        </td>
        <td class="p-2 align-top">
            <div class="relative flex items-center">
                <div class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3 z-10">
                    <span class="text-slate-500 text-xs font-bold">Rp</span>
                </div>
                {{-- Display Input (Formatted) --}}
                <input type="text" class="form-input purchase-price-formatted w-full pl-8 text-right text-sm font-medium h-10" placeholder="0">
                {{-- Hidden Input (Raw Value) --}}
                <input type="hidden" class="purchase-price-hidden" value="0">
            </div>
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
        const form = document.getElementById('po-form');
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

        // --- 1. INIT UTILS ---
        function formatCurrency(n) {
            return new Intl.NumberFormat('id-ID', { style: 'currency', currency: 'IDR', minimumFractionDigits: 0 }).format(Math.round(n || 0));
        }

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
        // unformatOnSubmit: false (karena kita punya hidden input manual)
        function initBoundAutoNumeric(displayElement, hiddenElement) {
            if (!displayElement) return;
            const an = new AutoNumeric(displayElement, { 
                decimalCharacter: ',', 
                digitGroupSeparator: '.', 
                decimalPlaces: 0, 
                minimumValue: '0', 
                emptyInputBehavior: 'zero',
                currencySymbol: '', // Tidak pakai simbol di input (sudah ada di UI atau tidak perlu)
                unformatOnSubmit: false 
            });
            
            displayElement.addEventListener('autoNumeric:rawValueModified', e => {
                hiddenElement.value = e.detail.newRawValue;
                calculateTotals();
            });
            return an;
        }

        // Init AutoNumeric untuk field header
        const anDiscFee = initBoundAutoNumeric(inputDiscFeeAmountDisplay, inputDiscFeeAmountHidden);
        const anRounding = initBoundAutoNumeric(inputRoundingAmountDisplay, inputRoundingAmountHidden);
        const anShipping = initBoundAutoNumeric(inputShippingDisplay, inputShippingHidden);

        // --- 2. INIT SELECT2 (Manual, hindari bentrok app.js) ---
        $('.po-select2').select2({ width: '100%', dropdownCssClass: 'select2-dropdown-clean', allowClear: true, placeholder: '-- Pilih --' });

        // --- 3. ROW MANAGEMENT ---
        window.addProductRow = function(data = null) {
            const clone = productRowTemplate.content.cloneNode(true);
            const tr = clone.querySelector('tr');
            
            // Naming Inputs
            tr.querySelector('.product-select').name = `products[${productIndex}][product_id]`;
            tr.querySelector('.quantity').name = `products[${productIndex}][quantity]`;
            tr.querySelector('.purchase-price-hidden').name = `products[${productIndex}][price_per_unit]`;
            tr.querySelector('.update-master-price').name = `products[${productIndex}][update_master_price]`;
            
            // Set Name Initial Discount
            const initialDiscInput = tr.querySelector('.discount-percentage');
            initialDiscInput.name = `products[${productIndex}][discounts][]`;

            productItemsContainer.appendChild(tr);
            
            // Init Plugins for Row
            const select = $(tr.querySelector('.product-select'));
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
            
            // Multiple Discount Logic
            initialDiscInput.addEventListener('input', calculateTotals);
            tr.querySelector('.add-disc-btn').addEventListener('click', () => {
                addDiscountInputToRow(tr);
            });

            tr.querySelector('.remove-product-btn').addEventListener('click', function() {
                select.select2('destroy');
                tr.remove();
                calculateTotals();
            });

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

        // --- 4. BULK DISCOUNT LOGIC ---
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

        // Event Listeners for Bulk Actions
        btnApplySelected.addEventListener('click', function() {
            const checkedRows = Array.from(productItemsContainer.querySelectorAll('tr')).filter(tr => tr.querySelector('.row-checkbox').checked);
            if(checkedRows.length === 0) {
                Swal.fire('Pilih Item', 'Centang minimal satu baris produk.', 'info');
                return;
            }
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

            // Loop Rows
            productItemsContainer.querySelectorAll('tr').forEach(row => {
                const qty = parseFloat(row.querySelector('.quantity').value) || 0;
                // Ambil dari hidden value (raw number)
                const price = parseFloat(row.querySelector('.purchase-price-hidden').value) || 0;
                
                let netPrice = price;
                row.querySelectorAll('.discount-percentage').forEach(d => {
                    const disc = parseFloat(d.value) || 0;
                    if(disc > 0) {
                        netPrice = netPrice * (1 - (disc / 100));
                    }
                });

                const rowSubtotal = qty * netPrice;
                row.querySelector('.subtotal').textContent = formatCurrency(rowSubtotal);
                subtotalGlobal += rowSubtotal;
            });

            // Header Discounts
            let discFeeVal = 0;
            if (checkboxDiscFee.checked) {
                const pct = parseFloat(inputDiscFeePercent.value) || 0;
                // Ambil dari hidden value
                const amt = parseFloat(inputDiscFeeAmountHidden.value) || 0;
                
                if (pct > 0) discFeeVal = subtotalGlobal * (pct / 100);
                else if (amt > 0) discFeeVal = amt;
            }

            const roundingVal = checkboxRounding.checked ? (parseFloat(inputRoundingAmountHidden.value) || 0) : 0;
            
            let taxableBase = subtotalGlobal - discFeeVal - roundingVal;
            if (taxableBase < 0) taxableBase = 0;

            // Tax
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

            // Render Summary
            displaySubtotal.textContent = formatCurrency(subtotalGlobal);
            displayDisc.textContent = `(-) ${formatCurrency(discFeeVal)}`;
            displayRounding.textContent = `(-) ${formatCurrency(roundingVal)}`;
            displayDpp.textContent = formatCurrency(dppVal);
            displayPpn.textContent = `(+) ${formatCurrency(ppnVal)}`;
            displayTaxRate.textContent = taxRate;
            displayGrand.textContent = formatCurrency(grandTotal);
            
            // Toggle Input Visibility
            document.getElementById('disc-fee-inputs').classList.toggle('hidden', !checkboxDiscFee.checked);
            document.getElementById('rounding_discount_amount_display').classList.toggle('hidden', !checkboxRounding.checked);
            document.getElementById('custom-dpp-container').classList.toggle('hidden', !checkboxCustomDpp.checked);
        };

        // --- 6. EVENT LISTENERS ---
        const calcTriggers = [inputDiscFeePercent, inputCustomDpp];
        calcTriggers.forEach(el => el.addEventListener('input', calculateTotals));
        $(inputTaxId).on('select2:select select2:unselect', calculateTotals);

        [checkboxDiscFee, checkboxRounding, checkboxCustomDpp].forEach(el => {
            el.addEventListener('change', calculateTotals);
        });

        // Add Initial Row
        addProductRow();
        
        // Form Validation Interceptor
        form.addEventListener('submit', function(e) {
            if (productIndex === 0 || productItemsContainer.children.length === 0) {
                e.preventDefault();
                Swal.fire('Perhatian', 'Mohon tambahkan minimal satu item.', 'warning');
                return;
            }
            // Convert fraction to decimal for backend
            if (checkboxCustomDpp.checked && inputCustomDpp.value) {
                inputCustomDpp.value = parseFractionOrNumber(inputCustomDpp.value);
            }

            const submitBtn = document.getElementById('submit-btn');
            if(submitBtn) {
                submitBtn.disabled = true;
                submitBtn.innerHTML = '<i class="material-icons animate-spin text-sm">sync</i> Menyimpan...';
            }
        });
        
        // Calc once on load
        setTimeout(calculateTotals, 500);
    });
</script>
@endpush
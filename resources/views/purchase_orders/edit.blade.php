@extends('layouts.app')

@section('title', 'Edit Pesanan Pembelian')

@section('content')
<div class="max-w-full mx-auto pb-20 animate-enter">
    
    {{-- HEADER --}}
    <div class="flex flex-col md:flex-row justify-between items-start md:items-center mb-6 gap-4">
        <div>
            <div class="flex items-center gap-2 text-xs font-bold text-slate-400 uppercase tracking-wider mb-1">
                <a href="{{ route('purchase-orders.index') }}" class="hover:text-indigo-600 transition">Pesanan</a>
                <i class="material-icons text-[12px]">chevron_right</i>
                <span class="text-slate-600">Edit</span>
            </div>
            <h2 class="text-2xl font-bold text-slate-800 tracking-tight">
                Edit Pesanan <span class="text-indigo-600 font-mono ml-1">{{ $purchaseOrder->po_number }}</span>
            </h2>
        </div>
        <a href="{{ route('purchase-orders.index') }}" class="inline-flex items-center px-4 py-2 bg-white border border-slate-300 rounded-lg text-slate-600 hover:bg-slate-50 transition shadow-sm text-sm font-bold">
            <i class="material-icons text-sm mr-2">arrow_back</i> Kembali
        </a>
    </div>

    {{-- ALERT ERROR --}}
    @if ($errors->any())
        <div class="mb-6 bg-red-50 border border-red-200 rounded-xl p-4 flex items-start gap-3">
            <i class="material-icons text-red-500 text-xl">error</i>
            <div>
                <h3 class="text-sm font-bold text-red-800">Gagal memperbarui pesanan:</h3>
                <ul class="mt-1 list-disc list-inside text-sm text-red-700">
                    @foreach ($errors->all() as $error) <li>{{ $error }}</li> @endforeach
                </ul>
            </div>
        </div>
    @endif

    <form action="{{ route('purchase-orders.update', $purchaseOrder->po_id) }}" method="POST" id="po-form">
        @csrf
        @method('PUT')

        <div class="grid grid-cols-1 xl:grid-cols-12 gap-6">
            
            {{-- KOLOM KIRI (DETAIL & ITEMS) - Span 9 --}}
            <div class="xl:col-span-9 space-y-6">
                
                {{-- 1. INFO PESANAN --}}
                <div class="dashboard-card p-0 overflow-hidden">
                    <div class="bg-slate-50/50 px-6 py-4 border-b border-slate-100">
                        <h3 class="text-xs font-bold text-slate-500 uppercase tracking-wider flex items-center gap-2">
                            <i class="material-icons text-indigo-500 text-sm">edit</i> Informasi Pesanan
                        </h3>
                    </div>
                    <div class="p-6 grid grid-cols-1 md:grid-cols-12 gap-6">
                        <div class="md:col-span-6">
                            <label class="block text-[11px] font-bold text-slate-500 uppercase mb-2 ml-1">Supplier <span class="text-red-500">*</span></label>
                            <select name="supplier_id" id="supplier_id" class="w-full select2-basic" required>
                                @foreach ($suppliers as $supplier)
                                    <option value="{{ $supplier->supplier_id }}" @selected(old('supplier_id', $purchaseOrder->supplier_id) == $supplier->supplier_id)>
                                        {{ $supplier->supplier_name }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        
                        <div class="md:col-span-3">
                            <label class="block text-[11px] font-bold text-slate-500 uppercase mb-2 ml-1">Tanggal Pesan</label>
                            <input type="date" class="form-input" id="order_date" name="order_date" value="{{ old('order_date', optional($purchaseOrder->order_date)->format('Y-m-d')) }}" required>
                        </div>
                        
                        <div class="md:col-span-3">
                            <label class="block text-[11px] font-bold text-slate-500 uppercase mb-2 ml-1">Jatuh Tempo</label>
                            <input type="date" class="form-input" id="due_date" name="due_date" value="{{ old('due_date', optional($purchaseOrder->due_date)->format('Y-m-d')) }}">
                        </div>

                        <div class="md:col-span-12">
                            <label class="block text-[11px] font-bold text-slate-500 uppercase mb-2 ml-1">Dipesan Oleh</label>
                            <select name="requester_user_id" id="requester_user_id" class="w-full select2-basic">
                                <option value="">-- Pembelian Umum / Stok --</option>
                                @foreach ($users as $user)
                                    <option value="{{ $user->user_id }}" @selected(old('requester_user_id', $purchaseOrder->requester_user_id) == $user->user_id)>
                                        {{ $user->full_name }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                </div>

                {{-- 2. EDIT ITEM (Optimized) --}}
                <div class="dashboard-card p-0 overflow-hidden">
                    <div class="px-6 py-4 border-b border-slate-100 bg-slate-50/50 flex justify-between items-center">
                        <h3 class="text-xs font-bold text-slate-500 uppercase tracking-wider flex items-center gap-2">
                            <i class="material-icons text-indigo-500 text-sm">inventory</i> Edit Item
                        </h3>
                        {{-- Toolbar sama seperti create --}}
                        <div class="flex items-center gap-2">
                            <div class="flex items-center gap-0 shadow-sm rounded-md">
                                <input id="bulk-discount-input" type="number" step="any" min="0" class="w-20 form-input rounded-none rounded-l-md text-xs h-8 py-1" placeholder="Disc %">
                                <button type="button" id="apply-bulk-discount-btn" class="bg-indigo-600 hover:bg-indigo-700 text-white text-[10px] font-bold px-3 h-8 uppercase transition">Selected</button>
                                <button type="button" id="apply-all-discount-btn" class="bg-slate-700 hover:bg-slate-800 text-white text-[10px] font-bold px-3 h-8 rounded-r-md uppercase transition">All</button>
                            </div>
                        </div>
                    </div>

                    <div class="overflow-x-auto">
                        <table class="dashboard-table">
                            <thead>
                                <tr>
                                    <th class="w-8 text-center">
                                        <input type="checkbox" class="rounded border-slate-300 text-indigo-600 focus:ring-indigo-500 cursor-pointer" id="header-row-select">
                                    </th>
                                    <th class="min-w-[280px]">Produk</th> 
                                    <th class="w-20 text-center">Qty</th> 
                                    <th class="w-32 text-right">Harga Beli (@)</th> 
                                    <th class="w-24 text-center">Disc</th> 
                                    <th class="w-32 text-right">Subtotal</th> 
                                    <th class="w-8 text-center"><i class="material-icons text-slate-400 text-sm">settings</i></th>
                                </tr>
                            </thead>
                            <tbody id="product-items">
                                {{-- JS populates this --}}
                            </tbody>
                        </table>
                    </div>
                    
                    <div class="p-4 bg-slate-50 border-t border-slate-100 text-center">
                         <button type="button" id="add-product-btn" class="inline-flex items-center px-4 py-2 bg-white border border-indigo-200 text-indigo-600 text-xs font-bold rounded-full hover:bg-indigo-50 transition shadow-sm uppercase tracking-wide">
                            <i class="material-icons text-sm mr-1">add</i> Tambah Item
                        </button>
                    </div>

                    <div class="p-6 border-t border-slate-100">
                        <label class="block text-[11px] font-bold text-slate-500 uppercase mb-2 ml-1">Catatan</label>
                        <textarea class="form-textarea" name="notes" id="notes" rows="2">{{ old('notes', $purchaseOrder->notes) }}</textarea>
                    </div>
                </div>
            </div>

            {{-- KOLOM KANAN (SUMMARY) - Span 3 --}}
            <div class="xl:col-span-3">
                <div class="dashboard-card p-0 sticky top-6">
                    <div class="px-6 py-4 border-b border-slate-100 bg-slate-50/50 flex items-center gap-2">
                        <i class="material-icons text-slate-400 text-sm">calculate</i>
                        <h3 class="text-sm font-bold text-slate-700 uppercase tracking-wider">Ringkasan</h3>
                    </div>

                    <div class="p-6 space-y-5">
                        
                        {{-- Diskon Fee --}}
                        <div>
                            <div class="flex items-center justify-between mb-2">
                                <label class="text-[11px] font-bold text-slate-500 uppercase cursor-pointer" for="apply_disc_fee">Diskon Akhir</label>
                                <input type="checkbox" name="apply_disc_fee" id="apply_disc_fee" value="1" class="rounded border-slate-300 text-indigo-600 focus:ring-indigo-500 h-4 w-4 cursor-pointer" {{ old('apply_disc_fee', $purchaseOrder->apply_disc_fee) ? 'checked' : '' }}>
                            </div>
                            <div class="p-3 bg-slate-50 rounded-lg border border-slate-100 {{ old('apply_disc_fee', $purchaseOrder->apply_disc_fee) ? '' : 'hidden' }}" id="disc-fee-container">
                                <div class="grid grid-cols-2 gap-2">
                                    <div>
                                        <label class="block text-[10px] text-slate-400 mb-1">%</label>
                                        <input type="number" step="any" min="0" class="form-input text-xs h-8 px-2" name="disc_fee_percent" id="disc_fee_percent" value="{{ old('disc_fee_percent', $purchaseOrder->disc_fee_percent) }}">
                                    </div>
                                    <div>
                                        <label class="block text-[10px] text-slate-400 mb-1">Rp</label>
                                        <input type="text" class="form-input text-xs h-8 px-2 text-right" name="disc_fee_amount" id="disc_fee_amount" value="{{ old('disc_fee_amount', $purchaseOrder->disc_fee_amount) }}">
                                    </div>
                                </div>
                            </div>
                        </div>

                        {{-- Pembulatan --}}
                        <div>
                            <div class="flex items-center justify-between mb-2">
                                <label class="text-[11px] font-bold text-slate-500 uppercase cursor-pointer" for="apply_rounding_discount">Pembulatan</label>
                                <input type="checkbox" id="apply_rounding_discount" name="apply_rounding_discount" value="1" class="rounded border-slate-300 text-indigo-600 focus:ring-indigo-500 h-4 w-4 cursor-pointer" {{ old('apply_rounding_discount', $purchaseOrder->apply_rounding_discount) ? 'checked' : '' }}>
                            </div>
                            <input type="text" class="form-input text-right text-xs font-bold h-8" name="rounding_discount_amount" id="rounding_discount_amount" value="{{ old('rounding_discount_amount', $purchaseOrder->rounding_discount_amount) }}">
                        </div>

                        {{-- Pajak --}}
                        <div>
                            <label class="block text-[11px] font-bold text-slate-500 uppercase mb-2 ml-1">Pajak</label>
                            <select name="tax_id" id="tax_id" class="form-select text-xs h-9">
                                <option value="">-- Tanpa Pajak --</option>
                                @foreach(\App\Models\Tax::where('is_active', true)->get() as $tax)
                                    <option value="{{ $tax->id }}" data-rate="{{ $tax->rate }}" {{ old('tax_id', $purchaseOrder->tax_id) == $tax->id ? 'selected' : '' }}>
                                        {{ $tax->name }} ({{ $tax->rate }}%)
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        {{-- Advanced Tax Toggle --}}
                        <div>
                            <label class="text-[10px] font-bold text-indigo-600 uppercase cursor-pointer flex items-center gap-1 hover:underline" id="toggle-advanced-tax">
                                <i class="material-icons text-[12px]">tune</i> Opsi Lanjutan
                            </label>
                            <div class="hidden mt-2 p-3 bg-slate-50 rounded-lg border border-slate-100" id="advancedTaxOptions">
                                <div class="flex items-center mb-2">
                                    <input type="checkbox" id="use_custom_dpp_factor" name="use_custom_dpp_factor" value="1" class="rounded border-slate-300 text-indigo-600 h-3.5 w-3.5 mr-2" {{ old('use_custom_dpp_factor', $purchaseOrder->use_custom_dpp_factor) ? 'checked' : '' }}>
                                    <label class="text-[11px] text-slate-600" for="use_custom_dpp_factor">Override DPP</label>
                                </div>
                                <input type="text" class="form-input text-xs h-8" name="custom_dpp_factor" id="custom_dpp_factor" value="{{ old('custom_dpp_factor', $purchaseOrder->custom_dpp_factor) }}">
                            </div>
                        </div>

                        {{-- Ongkir --}}
                        <div>
                            <label class="block text-[11px] font-bold text-slate-500 uppercase mb-2 ml-1">Ongkir (Rp)</label>
                            <input type="text" class="form-input text-right font-bold" name="shipping_amount" id="shipping_amount" value="{{ old('shipping_amount', $purchaseOrder->shipping_amount) }}">
                        </div>

                        {{-- SUMMARY VALUES --}}
                        <div class="bg-slate-50 rounded-lg p-4 border border-slate-200 space-y-2">
                            <div class="flex justify-between text-xs text-slate-500"><span>Subtotal</span><span class="font-bold text-slate-700" id="summary-subtotal">Rp 0</span></div>
                            <div class="flex justify-between text-xs text-red-500"><span>Diskon</span><span id="summary-disc">- Rp 0</span></div>
                            <div class="flex justify-between text-xs text-red-500"><span>Pembulatan</span><span id="summary-rounding">- Rp 0</span></div>
                            <div class="border-t border-slate-200 my-2"></div>
                            <div class="flex justify-between text-xs text-slate-600"><span>PPN (<span id="summary-tax-rate">0</span>%)</span><span class="font-bold" id="summary-ppn">Rp 0</span></div>
                            <div class="flex justify-between text-xs text-slate-600"><span>Ongkir</span><span class="font-bold" id="summary-shipping">Rp 0</span></div>
                            
                            <div class="border-t border-dashed border-slate-300 my-2 pt-2">
                                <div class="flex justify-between items-center">
                                    <span class="text-sm font-bold text-slate-800">TOTAL</span>
                                    <span class="text-lg font-bold text-indigo-600" id="summary-grand">Rp 0</span>
                                </div>
                            </div>
                        </div>

                        <div class="grid grid-cols-1 gap-3">
                            <button type="submit" class="w-full py-3 px-4 bg-indigo-600 hover:bg-indigo-700 text-white text-sm font-bold rounded-lg shadow-md transition flex justify-center items-center gap-2">
                                <i class="material-icons text-sm">check_circle</i> Update Pesanan
                            </button>
                            <a href="{{ route('purchase-orders.index') }}" class="w-full py-2.5 border border-slate-300 rounded-lg text-xs font-bold text-slate-600 hover:bg-white hover:shadow-sm text-center transition uppercase tracking-wide">
                                Batal
                            </a>
                        </div>

                    </div>
                </div>
            </div>
        </div>
    </form>
</div>

{{-- TEMPLATE ROW (Optimized) --}}
<template id="product-row-template">
    <tr class="group transition-colors hover:bg-slate-50">
        <td class="text-center align-middle p-1">
            <input type="checkbox" class="row-select rounded border-slate-300 text-indigo-600 focus:ring-indigo-500 cursor-pointer h-4 w-4">
        </td>
        <td class="p-1">
            <select class="product-select form-select text-xs w-full" required>
                <option value="" data-unit="-" data-default-discounts="[]" disabled selected>-- Pilih --</option>
                @foreach ($products as $product)
                    <option value="{{ $product->product_id }}"
                            data-unit="{{ $product->unit->name ?? '' }}"
                            data-default-discounts='@json($product->default_discounts ?? [])'
                            data-default-price="{{ $product->purchase_price ?? 0 }}">
                        {{ $product->product_name }}
                    </option>
                @endforeach
            </select>
        </td>
        <td class="p-1">
            <div class="flex items-center border border-slate-300 rounded-md bg-white overflow-hidden h-8 focus-within:border-indigo-500 focus-within:ring-1 focus-within:ring-indigo-500">
                <input type="number" class="table-input quantity w-full text-center text-sm font-bold border-none focus:ring-0 p-0 h-full" value="1" min="1" step="any" required>
                <span class="bg-slate-100 text-slate-500 text-[10px] px-1 h-full flex items-center border-l border-slate-200 unit-display font-medium min-w-[20px] justify-center">-</span>
            </div>
        </td>
        <td class="p-1">
            <div class="relative">
                <span class="absolute left-2 top-1/2 -translate-y-1/2 text-slate-400 text-[10px]">Rp</span>
                <input type="text" class="table-input purchase-price-formatted w-full form-input pl-6 text-right text-sm font-medium h-8" placeholder="0">
                <input type="hidden" class="purchase-price-hidden" value="0">
            </div>
            <div class="flex items-center justify-end gap-1 mt-1">
                <input class="update-master-price rounded border-slate-300 text-indigo-600 focus:ring-indigo-500 h-3 w-3 cursor-pointer" type="checkbox" value="1">
                <label class="text-[9px] text-slate-400 italic cursor-pointer">Master</label>
            </div>
        </td>
        <td class="p-1 align-top">
            <div class="discount-container space-y-1"></div>
            <button type="button" class="add-discount-btn text-[10px] text-indigo-600 font-bold uppercase tracking-wide mt-1 hover:underline flex items-center justify-center w-full">
                + Disc
            </button>
        </td>
        <td class="p-1 text-right align-middle font-bold text-slate-800 text-sm">
            <span class="subtotal">Rp 0</span>
        </td>
        <td class="text-center align-middle p-1">
            <button type="button" class="remove-product-btn text-slate-300 hover:text-red-500 transition p-1 rounded-full hover:bg-red-50">
                <i class="material-icons text-lg">delete</i>
            </button>
        </td>
    </tr>
</template>
@endsection

@push('scripts')
{{-- ASUMSI: SCRIPT JAVASCRIPT LENGKAP DARI PROMPT SEBELUMNYA DI-PASTE DI SINI --}}
<script>
    document.addEventListener('DOMContentLoaded', function () {
        // === 1. DATA EXISTING (UNTUK EDIT) ===
        const existingPoItems = @json($purchaseOrder->items->load('discounts') ?? []);

        const form = document.getElementById('po-form');
        const productItemsContainer = document.getElementById('product-items');
        const productRowTemplate = document.getElementById('product-row-template');

        const inputDiscFeeAmount = document.getElementById('disc_fee_amount');
        const inputRoundingAmount = document.getElementById('rounding_discount_amount');
        const inputShipping = document.getElementById('shipping_amount');
        const inputTaxId = document.getElementById('tax_id');
        const discFeeCheckbox = document.getElementById('apply_disc_fee');
        const discFeeContainer = document.getElementById('disc-fee-container');
        let productIndex = 0;

        // === INITIALIZE ===
        $('#supplier_id').select2({ theme: 'bootstrap-5', width: '100%', dropdownCssClass: 'select2-dropdown-clean' });
        $('#requester_user_id').select2({ theme: 'bootstrap-5', width: '100%', dropdownCssClass: 'select2-dropdown-clean' });

        // === AUTONUMERIC FIX (INIT) ===
        const autoNumericOptions = { 
            decimalCharacter: ',', 
            digitGroupSeparator: '.', 
            decimalPlaces: 0, 
            minimumValue: '0' 
        };
        new AutoNumeric(inputDiscFeeAmount, autoNumericOptions);
        new AutoNumeric(inputRoundingAmount, autoNumericOptions);
        new AutoNumeric(inputShipping, autoNumericOptions);

        // Toggle Advanced Tax
        document.getElementById('toggle-advanced-tax').addEventListener('click', function() {
            document.getElementById('advancedTaxOptions').classList.toggle('hidden');
        });

        // Toggle Disc Fee Container
        discFeeCheckbox.addEventListener('change', function() {
            discFeeContainer.classList.toggle('hidden', !this.checked);
            calculateTotals();
        });

        // Helper Functions (As defined in Create Script)
        function formatCurrency(n) { return new Intl.NumberFormat('id-ID', { style: 'currency', currency: 'IDR', minimumFractionDigits: 0 }).format(Math.round(n || 0)); }
        function formatThousands(n) { if (!n) return ''; return new Intl.NumberFormat('id-ID', { maximumFractionDigits: 0 }).format(Math.floor(Number(n))); }
        function parseNumericForInput(str) { if (!str) return 0; return parseFloat(String(str).replace(/[^\d\-\.\,]/g, '').replace(/,/g, '.')) || 0; }
        function getAllRows() { return Array.from(productItemsContainer.querySelectorAll('tr')); }
        function getRawNumericValue(elementId) {
            const el = document.getElementById(elementId);
            if (AutoNumeric.getAutoNumericElement(el)) return parseFloat(AutoNumeric.getAutoNumericElement(el).getNumericString()) || 0;
            return parseNumericForInput(el.value);
        }
        function getSelectedTaxRate() {
            const opt = inputTaxId.selectedOptions[0];
            return (opt && opt.value) ? parseFloat(opt.dataset.rate) : null;
        }
        function parseFractionOrNumber(val) {
 	        if (!val) return 1;
 	        if (val.includes('/')) {
 	            const parts = val.split('/');
 	            return parseFloat(parts[0]) / parseFloat(parts[1]);
 	        }
 	        return parseFloat(val) || 1;
 	    }

        // === CALCULATION LOGIC ===
        function calculateRowSubtotal(row) {
            const quantity = parseFloat(row.querySelector('.quantity').value) || 0;
            const price = parseFloat(row.querySelector('.purchase-price-hidden')?.value || 0);
            let finalUnitPrice = price;
            row.querySelectorAll('.discount-percentage').forEach(input => {
                const rate = parseFloat(input.value) || 0;
                if (rate > 0 && rate <= 100) finalUnitPrice *= (1 - (rate / 100));
            });
            const subtotal = quantity * finalUnitPrice;
            row.querySelector('.subtotal').textContent = formatCurrency(subtotal);
            return subtotal;
        }

        function calculateTotals() {
            let subtotalBarang = 0;
            getAllRows().forEach(row => { subtotalBarang += calculateRowSubtotal(row); });

            const inputDiscFeePercent = document.getElementById('disc_fee_percent');
            const inputApplyRounding = document.getElementById('apply_rounding_discount');
            const inputRoundingAmount = document.getElementById('rounding_discount_amount');
            const inputUseCustomDpp = document.getElementById('use_custom_dpp_factor');
            const inputCustomDppFactor = document.getElementById('custom_dpp_factor');

            // Fee Header (Raw Value)
            let discFeeAmount = 0;
            if (discFeeCheckbox.checked) {
                const percent = parseFloat(inputDiscFeePercent.value) || 0;
                const fixed = getRawNumericValue('disc_fee_amount'); 
                if (percent > 0) discFeeAmount = (percent / 100.0) * subtotalBarang;
                else if (fixed > 0) discFeeAmount = fixed;
            }

            // Pembulatan (Raw Value)
            const roundingAmount = inputApplyRounding.checked ? getRawNumericValue('rounding_discount_amount') : 0; 
            
            const taxableBase = Math.max(0, subtotalBarang - discFeeAmount - roundingAmount);

            let dpp = taxableBase;
            let ppn = 0;
            let taxRate = getSelectedTaxRate();

            if (taxRate !== null && taxRate > 0) {
                let dppFactor = 100 / (100 + taxRate);
                if (inputUseCustomDpp.checked) {
                    const customFactor = parseFractionOrNumber(inputCustomDppFactor.value);
                    if (customFactor > 0) dppFactor = customFactor;
                }
                dpp = Math.round(taxableBase * dppFactor);
                ppn = Math.round(dpp * (taxRate / 100.0));
            } else {
                taxRate = 0;
            }

            // Shipping (Raw Value)
            const shipping = getRawNumericValue('shipping_amount'); 
            const grandTotal = Math.round(taxableBase + ppn + shipping);

            document.getElementById('summary-subtotal').textContent = formatCurrency(subtotalBarang);
            document.getElementById('summary-disc').textContent = formatCurrency(discFeeAmount);
            document.getElementById('summary-rounding').textContent = formatCurrency(roundingAmount);
            document.getElementById('summary-dpp').textContent = formatCurrency(dpp);
            document.getElementById('summary-ppn').textContent = formatCurrency(ppn);
            document.getElementById('summary-shipping').textContent = formatCurrency(shipping);
            document.getElementById('summary-grand').textContent = formatCurrency(grandTotal);
            document.getElementById('summary-tax-rate').textContent = taxRate || 0;
        }

        // === ROW CREATION ===
        function createDiscountInputForRow(row, value = '') {
            const index = row.dataset.index;
            const container = row.querySelector('.discount-container');
            const div = document.createElement('div');
            div.className = 'flex items-center gap-1';
            div.innerHTML = `
                <input type="number" step="any" min="0" max="100" class="discount-percentage text-xs w-10 text-center border border-slate-300 rounded shadow-sm focus:ring-1 focus:ring-indigo-500 p-0.5" placeholder="%" value="${value}" name="products[${index}][discounts][]">
                <button type="button" class="remove-discount-btn text-slate-300 hover:text-red-500 transition p-1 rounded-full hover:bg-red-50"><i class="material-icons text-sm">close</i></button>
            `;
            div.querySelector('input').addEventListener('input', () => { calculateRowSubtotal(row); calculateTotals(); });
            div.querySelector('button').onclick = () => { div.remove(); calculateRowSubtotal(row); calculateTotals(); };
            container.appendChild(div);
        }

        function addProductRow(shouldCalculate = true) {
            const newRow = productRowTemplate.content.cloneNode(true).querySelector('tr');
            newRow.dataset.index = productIndex;
            newRow.querySelector('.product-select').name = `products[${productIndex}][product_id]`;
            newRow.querySelector('.quantity').name = `products[${productIndex}][quantity]`;
            newRow.querySelector('.update-master-price').name = `products[${productIndex}][update_master_price]`;
            
            const priceHidden = document.createElement('input');
            priceHidden.type = 'hidden';
            priceHidden.className = 'purchase-price-hidden';
            priceHidden.name = `products[${productIndex}][price_per_unit]`;
            priceHidden.value = '0';
            newRow.querySelector('.purchase-price-formatted').parentElement.appendChild(priceHidden);
            productItemsContainer.appendChild(newRow);
            
            const selectElem = $(newRow.querySelector('.product-select'));
            selectElem.select2({ placeholder: '-- Cari Produk --', theme: 'bootstrap-5', width: '100%', dropdownCssClass: 'select2-dropdown-clean' });
            
            selectElem.on('select2:select', function(e) {
                const el = e.params.data.element;
                newRow.querySelector('.unit-display').textContent = el.dataset.unit || '-';
                const defPrice = el.dataset.defaultPrice || 0;
                priceHidden.value = defPrice;
                newRow.querySelector('.purchase-price-formatted').value = formatThousands(defPrice);
                calculateRowSubtotal(newRow); calculateTotals();
            });

            const qtyInput = newRow.querySelector('.quantity');
            const priceInput = newRow.querySelector('.purchase-price-formatted');
            
            qtyInput.addEventListener('input', () => { calculateRowSubtotal(newRow); calculateTotals(); });
            priceInput.addEventListener('input', () => {
                priceHidden.value = parseNumericForInput(priceInput.value);
                calculateRowSubtotal(newRow); calculateTotals();
            });
            priceInput.addEventListener('blur', () => { priceInput.value = formatThousands(priceHidden.value); });
            
            newRow.querySelector('.add-discount-btn').onclick = () => createDiscountInputForRow(newRow, '');
            newRow.querySelector('.remove-product-btn').onclick = () => {
                selectElem.select2('destroy');
                newRow.remove();
                calculateTotals();
            };

            productIndex++;
            if (shouldCalculate) calculateTotals();
            return newRow;
        }

        // === POPULATE EXISTING ITEMS & LISTENERS ===
        if (existingPoItems && existingPoItems.length > 0) {
            existingPoItems.forEach(item => {
                const newRow = addProductRow(false);
                const selectElem = $(newRow.querySelector('.product-select'));
                selectElem.val(item.product_id).trigger('change.select2');
                setTimeout(() => {
                    newRow.querySelector('.quantity').value = item.quantity;
                    newRow.querySelector('.purchase-price-hidden').value = item.price_per_unit;
                    newRow.querySelector('.purchase-price-formatted').value = formatThousands(item.price_per_unit);
                    if (item.discounts && item.discounts.length > 0) item.discounts.forEach(d => createDiscountInputForRow(newRow, d.percentage));
                    calculateRowSubtotal(newRow);
                    calculateTotals();
                }, 100);
            });
        } else {
            // Do nothing, as edit page should always have items or show error.
        }

        // Global Listeners
        document.getElementById('add-product-btn').onclick = () => addProductRow();
        const calcInputs = ['disc_fee_percent', 'custom_dpp_factor', 'tax_id', 'apply_rounding_discount', 'apply_disc_fee', 'use_custom_dpp_factor'];
        calcInputs.forEach(id => {
            const el = document.getElementById(id);
            if (el) {
                el.addEventListener('input', calculateTotals);
                el.addEventListener('change', calculateTotals);
            }
        });
        
        document.getElementById('disc_fee_amount').addEventListener('autoNumeric:rawValueModified', calculateTotals);
        document.getElementById('rounding_discount_amount').addEventListener('autoNumeric:rawValueModified', calculateTotals);
        document.getElementById('shipping_amount').addEventListener('autoNumeric:rawValueModified', calculateTotals);

        // Init visibility for advanced tax and disc fee
        if({{ $purchaseOrder->use_custom_dpp_factor ? 'true' : 'false' }}) document.getElementById('advancedTaxOptions').classList.remove('hidden');
        if(!{{ $purchaseOrder->apply_disc_fee ? 'true' : 'false' }}) document.getElementById('disc-fee-container').classList.add('hidden');

        // Initial calculation
        setTimeout(calculateTotals, 200);

        // Form Submit Validation
        form.addEventListener('submit', (e) => {
            if (getAllRows().length === 0) { e.preventDefault(); Swal.fire('Perhatian', 'Harap tambahkan minimal satu item.', 'warning'); }
        });
    });
</script>
@endpush
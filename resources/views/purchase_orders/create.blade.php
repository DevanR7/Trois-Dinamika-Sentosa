@extends('layouts.app')

@section('title', 'Buat PO Baru')

@section('content')
<div class="max-w-full mx-auto pb-20 animate-enter">
    
    {{-- INDIKATOR AUTO SAVE --}}
    <div id="autosave-indicator" class="fixed top-20 right-6 z-50 hidden transition-opacity duration-300">
        <div class="flex items-center bg-emerald-600 text-white px-4 py-2 rounded-lg shadow-lg border border-emerald-500 text-xs font-bold">
            <i class="material-icons text-sm mr-2">cloud_done</i>
            Draft disimpan otomatis
        </div>
    </div>

    {{-- HEADER --}}
    <div class="flex flex-col md:flex-row justify-between items-start md:items-center mb-6 gap-4">
        <div>
            <h2 class="text-2xl font-bold text-slate-800 tracking-tight">Buat Purchase Order Baru</h2>
            <p class="text-slate-500 text-sm mt-1">Buat pesanan pembelian baru ke supplier.</p>
        </div>
        <a href="{{ route('purchase-orders.index') }}" class="inline-flex items-center px-4 py-2 bg-white border border-slate-300 rounded-lg text-slate-600 hover:bg-slate-50 hover:text-indigo-600 transition shadow-sm text-sm font-bold">
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

    {{-- FORM --}}
    <form action="{{ route('purchase-orders.store') }}" method="POST" id="po-form">
        @csrf

        <div class="grid grid-cols-1 xl:grid-cols-12 gap-6">
            
            {{-- KOLOM KIRI (DETAIL & ITEMS) - Span 9 --}}
            <div class="xl:col-span-9 space-y-6">
                
                {{-- 1. INFO PESANAN --}}
                <div class="dashboard-card p-0 overflow-hidden">
                    <div class="bg-slate-50/50 px-6 py-4 border-b border-slate-100">
                        <h3 class="text-xs font-bold text-slate-500 uppercase tracking-wider flex items-center gap-2">
                            <i class="material-icons text-indigo-500 text-sm">info</i> Informasi Pesanan
                        </h3>
                    </div>
                    <div class="p-6 grid grid-cols-1 md:grid-cols-12 gap-6">
                        <div class="md:col-span-6">
                            <label class="block text-[11px] font-bold text-slate-500 uppercase mb-2 ml-1">Supplier <span class="text-red-500">*</span></label>
                            <select name="supplier_id" id="supplier_id" class="select2-basic w-full" required>
                                <option value="" disabled selected></option>
                                @foreach ($suppliers as $supplier)
                                    <option value="{{ $supplier->supplier_id }}">{{ $supplier->supplier_name }}</option>
                                @endforeach
                            </select>
                        </div>
                        
                        <div class="md:col-span-3">
                            <label class="block text-[11px] font-bold text-slate-500 uppercase mb-2 ml-1">Tanggal Pesan <span class="text-red-500">*</span></label>
                            <input type="date" class="form-input" id="order_date" name="order_date" value="{{ now()->format('Y-m-d') }}" required>
                        </div>
                        
                        <div class="md:col-span-3">
                            <label class="block text-[11px] font-bold text-slate-500 uppercase mb-2 ml-1">Jatuh Tempo</label>
                            <input type="date" class="form-input" id="due_date" name="due_date">
                        </div>

                        <div class="md:col-span-12">
                            <label class="block text-[11px] font-bold text-slate-500 uppercase mb-2 ml-1">Dipesan Oleh</label>
                            <select name="requester_user_id" id="requester_user_id" class="select2-basic w-full">
                                <option value="">-- Pembelian Umum / Stok --</option>
                                @foreach ($users as $user)
                                    <option value="{{ $user->user_id }}">{{ $user->full_name }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                </div>

                {{-- 2. RINCIAN BARANG (Optimized) --}}
                <div class="dashboard-card p-0 overflow-hidden">
                    <div class="px-6 py-4 border-b border-slate-100 bg-slate-50/50 flex justify-between items-center">
                        <div class="flex items-center gap-2">
                            <i class="material-icons text-indigo-500 text-sm">inventory_2</i>
                            <h3 class="text-sm font-bold text-slate-700 uppercase tracking-wider">Item Pesanan</h3>
                        </div>
                        
                        {{-- Toolbar Bulk Action --}}
                        <div class="flex items-center gap-2">
                            <div class="flex items-center gap-0 shadow-sm rounded-md">
                                <input id="bulk-discount-input" type="number" step="any" min="0" class="w-20 form-input rounded-none rounded-l-md text-xs h-8 py-1" placeholder="Disc %">
                                <button type="button" id="apply-bulk-discount-btn" class="bg-indigo-600 hover:bg-indigo-700 text-white text-[10px] font-bold px-3 h-8 uppercase tracking-wide transition">Selected</button>
                                <button type="button" id="apply-all-discount-btn" class="bg-slate-700 hover:bg-slate-800 text-white text-[10px] font-bold px-3 h-8 rounded-r-md uppercase tracking-wide transition">All</button>
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
                                    <th class="min-w-[250px] max-w-[300px]">Produk</th> 
                                    {{-- LEBAR QTY DIBUAT W-24 (96px) --}}
                                    <th class="w-24 text-center">Qty</th> 
                                    {{-- HARGA DIPERLEBAR SEDIKIT --}}
                                    <th class="w-36 text-right">Harga Beli (@)</th> 
                                    <th class="w-24 text-center">Disc</th> 
                                    <th class="w-32 text-right">Subtotal</th> 
                                    <th class="w-8 text-center"><i class="material-icons text-slate-400 text-sm">settings</i></th>
                                </tr>
                            </thead>
                            <tbody id="product-items">
                                {{-- JS Injects Rows Here --}}
                            </tbody>
                        </table>
                    </div>
                    
                    <div class="p-4 bg-slate-50 border-t border-slate-100 text-center">
                         <button type="button" id="add-product-btn" class="inline-flex items-center px-4 py-2 bg-white border border-indigo-200 text-indigo-600 text-xs font-bold rounded-full hover:bg-indigo-50 transition shadow-sm uppercase tracking-wide">
                            <i class="material-icons text-sm mr-1">add</i> Tambah Item
                        </button>
                    </div>

                    <div class="p-6 border-t border-slate-100">
                        <label for="notes" class="block text-[11px] font-bold text-slate-500 uppercase mb-2 ml-1">Catatan</label>
                        <textarea class="form-textarea bg-yellow-50/30 border-yellow-100 focus:border-yellow-400 focus:ring-yellow-200" name="notes" id="notes" rows="2" placeholder="Instruksi pengiriman, dsb...">{{ old('notes') }}</textarea>
                    </div>
                </div>
            </div>

            {{-- KOLOM KANAN (SUMMARY) - Span 3 --}}
            <div class="xl:col-span-3">
                <div class="dashboard-card p-0 sticky top-6">
                    <div class="px-6 py-4 border-b border-slate-100 bg-slate-50/50 flex items-center gap-2">
                        <i class="material-icons text-slate-400 text-sm">calculate</i>
                        <h3 class="text-sm font-bold text-slate-700 uppercase tracking-wider">Kalkulasi</h3>
                    </div>

                    <div class="p-6 space-y-5">
                        
                        {{-- 1. Diskon Tambahan --}}
                        <div>
                            <div class="flex items-center justify-between mb-2">
                                <label class="text-[11px] font-bold text-slate-500 uppercase cursor-pointer" for="apply_disc_fee">Diskon Akhir / Fee</label>
                                <input type="checkbox" name="apply_disc_fee" id="apply_disc_fee" value="1" class="rounded border-slate-300 text-indigo-600 focus:ring-indigo-500 h-4 w-4 cursor-pointer">
                            </div>
                            <div id="disc-fee-container" style="display: none;" class="p-3 bg-slate-50 rounded-lg border border-slate-100">
                                <div class="grid grid-cols-2 gap-2">
                                    <div>
                                        <label class="block text-[10px] text-slate-400 mb-1">Persen (%)</label>
                                        <input type="number" step="any" min="0" class="form-input text-xs h-8 px-2" name="disc_fee_percent" id="disc_fee_percent" placeholder="0">
                                    </div>
                                    <div>
                                        <label class="block text-[10px] text-slate-400 mb-1">Nominal (Rp)</label>
                                        <input type="text" class="form-input text-xs h-8 px-2 text-right" name="disc_fee_amount" id="disc_fee_amount" placeholder="0">
                                    </div>
                                </div>
                            </div>
                        </div>

                        {{-- 2. Pembulatan --}}
                        <div>
                            <div class="flex items-center justify-between mb-2">
                                <label class="text-[11px] font-bold text-slate-500 uppercase cursor-pointer" for="apply_rounding_discount">Diskon Pembulatan</label>
                                <input type="checkbox" id="apply_rounding_discount" name="apply_rounding_discount" value="1" class="rounded border-slate-300 text-indigo-600 focus:ring-indigo-500 h-4 w-4 cursor-pointer">
                            </div>
                            <div class="relative">
                                <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                    <span class="text-slate-400 text-xs font-bold">Rp</span>
                                </div>
                                <input type="text" class="form-input pl-8 text-right text-xs font-bold" name="rounding_discount_amount" id="rounding_discount_amount" placeholder="0">
                            </div>
                        </div>

                        <div class="h-px bg-slate-200 border-none"></div>

                        {{-- 3. Pajak --}}
                        <div>
                            <label class="block text-[11px] font-bold text-slate-500 uppercase mb-2 ml-1">Pajak (PPN)</label>
                            <select name="tax_id" id="tax_id" class="form-select text-xs h-9">
                                <option value="">-- Tanpa Pajak --</option>
                                @foreach(\App\Models\Tax::where('is_active', true)->get() as $tax)
                                    <option value="{{ $tax->id }}" data-rate="{{ $tax->rate }}">{{ $tax->name }} ({{ $tax->rate }}%)</option>
                                @endforeach
                            </select>
                        </div>

                        {{-- 4. Opsi Lanjutan --}}
                        <div>
                            <div class="flex items-center justify-between mb-2" onclick="document.getElementById('advancedTaxOptions').classList.toggle('hidden')">
                                <label class="text-[10px] font-bold text-indigo-600 uppercase cursor-pointer flex items-center gap-1 hover:underline">
                                    <i class="material-icons text-[12px]">tune</i> Opsi Lanjutan
                                </label>
                            </div>
                            <div class="hidden p-3 bg-slate-50 rounded-lg border border-slate-100" id="advancedTaxOptions">
                                <div class="flex items-center mb-2">
                                    <input type="checkbox" id="use_custom_dpp_factor" name="use_custom_dpp_factor" value="1" class="rounded border-slate-300 text-indigo-600 focus:ring-indigo-500 h-3.5 w-3.5 mr-2">
                                    <label class="text-[11px] text-slate-600 cursor-pointer" for="use_custom_dpp_factor">Override Faktor DPP</label>
                                </div>
                                <input type="text" class="form-input text-xs h-8" name="custom_dpp_factor" id="custom_dpp_factor" placeholder="Contoh: 11/12">
                            </div>
                        </div>

                        {{-- 5. Ongkir --}}
                        <div>
                            <label class="block text-[11px] font-bold text-slate-500 uppercase mb-2 ml-1">Ongkos Kirim</label>
                            <div class="relative">
                                <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                    <span class="text-slate-400 text-xs font-bold">Rp</span>
                                </div>
                                <input type="text" class="form-input pl-8 text-right font-bold" name="shipping_amount" id="shipping_amount" value="0">
                            </div>
                        </div>

                        {{-- SUMMARY BOX --}}
                        <div class="bg-slate-50 rounded-lg p-4 border border-slate-200 space-y-2">
                            <div class="flex justify-between text-xs text-slate-500">
                                <span>Subtotal</span>
                                <span class="font-bold text-slate-700" id="summary-subtotal">Rp 0</span>
                            </div>
                            <div class="flex justify-between text-xs text-red-500">
                                <span>Diskon/Fee</span>
                                <span id="summary-disc">- Rp 0</span>
                            </div>
                            <div class="flex justify-between text-xs text-red-500">
                                <span>Pembulatan</span>
                                <span id="summary-rounding">- Rp 0</span>
                            </div>
                            <div class="border-t border-slate-200 my-2"></div>
                            <div class="flex justify-between text-[10px] text-slate-400">
                                <span>DPP</span>
                                <span id="summary-dpp">Rp 0</span>
                            </div>
                            <div class="flex justify-between text-xs text-slate-600">
                                <span>PPN (<span id="summary-tax-rate">0</span>%)</span>
                                <span class="font-bold" id="summary-ppn">Rp 0</span>
                            </div>
                            <div class="flex justify-between text-xs text-slate-600">
                                <span>Ongkir</span>
                                <span class="font-bold" id="summary-shipping">Rp 0</span>
                            </div>
                            <div class="border-t border-dashed border-slate-300 my-2 pt-2">
                                <div class="flex justify-between items-center">
                                    <span class="text-sm font-bold text-slate-800">TOTAL</span>
                                    <span class="text-lg font-bold text-indigo-600" id="summary-grand">Rp 0</span>
                                </div>
                            </div>
                        </div>

                        {{-- ACTIONS --}}
                        <div class="grid grid-cols-1 gap-3">
                            <button type="submit" class="w-full py-3 px-4 bg-indigo-600 hover:bg-indigo-700 text-white text-sm font-bold rounded-lg shadow-md hover:shadow-lg transition flex justify-center items-center gap-2" id="submit-btn">
                                <i class="material-icons text-sm">save</i> Simpan Pesanan
                            </button>
                            <div class="flex justify-between gap-3">
                                <a href="{{ route('purchase-orders.index') }}" class="w-full py-2.5 border border-slate-300 rounded-lg text-xs font-bold text-slate-600 hover:bg-white hover:shadow-sm text-center transition uppercase tracking-wide flex items-center justify-center">
                                    Batal
                                </a>
                                <button type="button" class="w-full py-2.5 border border-red-200 bg-red-50 rounded-lg text-xs font-bold text-red-600 hover:bg-red-100 text-center transition uppercase tracking-wide flex items-center justify-center gap-1" onclick="clearDraft()">
                                    <i class="material-icons text-sm">delete_sweep</i> Hapus Draft
                                </button>
                            </div>
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
            {{-- W-16 untuk input QTY --}}
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
<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/autonumeric@4.6.0/dist/autoNumeric.min.js"></script>

<script>
    let clearDraft; 

    document.addEventListener('DOMContentLoaded', function () {
        const DRAFT_KEY = 'po_draft_fix_v1'; 
        const form = document.getElementById('po-form');
        const productItemsContainer = document.getElementById('product-items');
        const productRowTemplate = document.getElementById('product-row-template');
        
        const autosaveIndicator = document.getElementById('autosave-indicator');
        const bulkDiscountInput = document.getElementById('bulk-discount-input');
        const selectAllBtn = document.getElementById('select-all-btn');
        const deselectAllBtn = document.getElementById('deselect-all-btn');
        const applyBulkBtn = document.getElementById('apply-bulk-discount-btn');
        const applyAllBtn = document.getElementById('apply-all-discount-btn');
        
        const elSummarySubtotal = document.getElementById('summary-subtotal');
        const elSummaryDisc = document.getElementById('summary-disc');
        const elSummaryRounding = document.getElementById('summary-rounding');
        const elSummaryDpp = document.getElementById('summary-dpp');
        const elSummaryPpn = document.getElementById('summary-ppn');
        const elSummaryGrand = document.getElementById('summary-grand');
        const elSummaryShipping = document.getElementById('summary-shipping');
        const elSummaryTaxRate = document.getElementById('summary-tax-rate');

        const inputTaxId = document.getElementById('tax_id');
        const inputUseCustomDpp = document.getElementById('use_custom_dpp_factor');
        const inputCustomDppFactor = document.getElementById('custom_dpp_factor');
        
        const inputDiscFeeAmount = document.getElementById('disc_fee_amount');
        const inputRoundingAmount = document.getElementById('rounding_discount_amount');
        const inputShipping = document.getElementById('shipping_amount');
        
        let productIndex = 0;
        let isRestoring = false; 

        // === INITIALIZE SELECT2 & AUTONUMERIC GLOBAL ===
        $('#supplier_id').select2({ theme: 'bootstrap-5', placeholder: '-- Pilih Supplier --', width: '100%', dropdownCssClass: 'select2-dropdown-clean' });
        $('#requester_user_id').select2({ theme: 'bootstrap-5', placeholder: '-- Pembelian Umum --', allowClear: true, width: '100%', dropdownCssClass: 'select2-dropdown-clean' });
        
        const autoNumericOptions = { 
            decimalCharacter: ',', 
            digitGroupSeparator: '.', 
            decimalPlaces: 0, 
            minimumValue: '0', 
            emptyInputBehavior: 'zero'
        };

        new AutoNumeric(inputDiscFeeAmount, autoNumericOptions);
        new AutoNumeric(inputRoundingAmount, autoNumericOptions);
        new AutoNumeric(inputShipping, autoNumericOptions);

        // === LOGIC TOGGLE MANUAL ===
        const discFeeCheckbox = document.getElementById('apply_disc_fee');
        const discFeeContainer = document.getElementById('disc-fee-container');
        
        discFeeCheckbox.addEventListener('change', function() {
            discFeeContainer.style.display = this.checked ? 'block' : 'none';
            calculateTotals();
            saveFormState();
        });


        // === HELPER FUNCTIONS ===
        function formatCurrency(n) {
            return new Intl.NumberFormat('id-ID', { style: 'currency', currency: 'IDR', minimumFractionDigits: 0 }).format(Math.round(n || 0));
        }
        
        function formatThousands(n) {
            if (!n) return '';
            return new Intl.NumberFormat('id-ID', { maximumFractionDigits: 0 }).format(Math.floor(Number(n)));
        }
        
        function parseNumericForInput(str) {
            if (!str) return 0;
            return parseFloat(String(str).replace(/[^\d\-\.\,]/g, '').replace(/,/g, '.')) || 0;
        }
        
        function parseFractionOrNumber(val) {
            if (!val) return 1;
            if (val.includes('/')) {
                const parts = val.split('/');
                return parseFloat(parts[0]) / parseFloat(parts[1]);
            }
            return parseFloat(val) || 1;
        }
        
        function getAllRows() { 
            return Array.from(productItemsContainer.querySelectorAll('tr')); 
        }
        
        function getSelectedTaxRate() {
            const opt = inputTaxId.selectedOptions[0];
            return (opt && opt.value) ? parseFloat(opt.dataset.rate) : null;
        }
        
        function getRawNumericValue(elementId) {
            const el = document.getElementById(elementId);
            if (AutoNumeric.getAutoNumericElement(el)) {
                return parseFloat(AutoNumeric.getAutoNumericElement(el).getNumericString()) || 0;
            }
            return parseNumericForInput(el.value);
        }

        // === CORE CALCULATION LOGIC ===
        function calculateTotals() {
            const inputApplyDiscFee = document.getElementById('apply_disc_fee');
            const inputDiscFeePercent = document.getElementById('disc_fee_percent');
            const inputApplyRounding = document.getElementById('apply_rounding_discount');

            let subtotalBarang = 0;
            getAllRows().forEach(row => {
                const price = parseFloat(row.querySelector('.purchase-price-hidden')?.value || 0);
                const quantity = parseFloat(row.querySelector('.quantity').value) || 0;
                
                let finalPrice = price;
                row.querySelectorAll('.discount-percentage').forEach(d => {
                    const rate = parseFloat(d.value) || 0;
                    if (rate > 0 && rate <= 100) finalPrice *= (1 - rate / 100);
                });
                
                const subtotal = quantity * finalPrice;
                row.querySelector('.subtotal').textContent = formatCurrency(subtotal);
                subtotalBarang += subtotal;
            });

            let discFeeAmount = 0;
            if (inputApplyDiscFee.checked) {
                const percent = parseFloat(inputDiscFeePercent.value) || 0;
                const fixed = getRawNumericValue('disc_fee_amount'); 
                if (percent > 0) discFeeAmount = (percent / 100.0) * subtotalBarang;
                else if (fixed > 0) discFeeAmount = fixed;
            }

            const roundingAmount = inputApplyRounding.checked ? 
                getRawNumericValue('rounding_discount_amount') : 0;

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

            const shipping = getRawNumericValue('shipping_amount');
            const grandTotal = Math.round(taxableBase + ppn + shipping);

            elSummarySubtotal.textContent = formatCurrency(subtotalBarang);
            elSummaryDisc.textContent = formatCurrency(discFeeAmount);
            elSummaryRounding.textContent = formatCurrency(roundingAmount);
            elSummaryDpp.textContent = formatCurrency(dpp); 
            elSummaryPpn.textContent = formatCurrency(ppn);
            elSummaryShipping.textContent = formatCurrency(shipping);
            elSummaryGrand.textContent = formatCurrency(grandTotal);
            elSummaryTaxRate.textContent = taxRate;
            
            // Re-format nominal inputs on blur if they are not AutoNumeric (for non-table items)
            document.getElementById('rounding_discount_amount').value = formatThousands(roundingAmount);
        }

        // === SAVE & LOAD LOGIC ===
        function showSaveIndicator() {
            autosaveIndicator.classList.remove('hidden');
            setTimeout(() => { autosaveIndicator.classList.add('hidden'); }, 2000);
        }

        function saveFormState() {
            if (isRestoring) return;

            const productRowsData = getAllRows().map(row => {
                const select = row.querySelector('.product-select');
                const productId = $(select).val();
                
                return {
                    productId: productId, 
                    quantity: row.querySelector('.quantity').value,
                    priceFormatted: row.querySelector('.purchase-price-formatted').value, 
                    priceHidden: row.querySelector('.purchase-price-hidden').value,
                    discounts: Array.from(row.querySelectorAll('.discount-percentage')).map(d => d.value),
                    updateMaster: row.querySelector('.update-master-price').checked
                };
            });

            const state = {
                supplier_id: $('#supplier_id').val(),
                order_date: document.getElementById('order_date').value,
                due_date: document.getElementById('due_date').value,
                requester_user_id: $('#requester_user_id').val(),
                notes: document.getElementById('notes').value,
                
                apply_disc_fee: document.getElementById('apply_disc_fee').checked,
                disc_fee_percent: document.getElementById('disc_fee_percent').value,
                disc_fee_amount: getRawNumericValue('disc_fee_amount'),
                
                apply_rounding_discount: document.getElementById('apply_rounding_discount').checked,
                rounding_discount_amount: getRawNumericValue('rounding_discount_amount'),
                
                tax_id: document.getElementById('tax_id').value,
                use_custom_dpp_factor: document.getElementById('use_custom_dpp_factor').checked,
                custom_dpp_factor: document.getElementById('custom_dpp_factor').value,
                shipping_amount: getRawNumericValue('shipping_amount'),
                
                products: productRowsData
            };

            localStorage.setItem(DRAFT_KEY, JSON.stringify(state));
            showSaveIndicator();
        }

        function loadFormState() {
            const savedData = localStorage.getItem(DRAFT_KEY);
            if (!savedData) {
                addProductRow(); 
                return;
            }

            isRestoring = true; 
            try {
                const data = JSON.parse(savedData);
                
                if(data.supplier_id) $('#supplier_id').val(data.supplier_id).trigger('change.select2');
                if(data.requester_user_id) $('#requester_user_id').val(data.requester_user_id).trigger('change.select2');
                
                document.getElementById('order_date').value = data.order_date || '';
                document.getElementById('due_date').value = data.due_date || '';
                document.getElementById('notes').value = data.notes || '';

                document.getElementById('apply_disc_fee').checked = data.apply_disc_fee;
                document.getElementById('disc-fee-container').style.display = data.apply_disc_fee ? 'block' : 'none';

                document.getElementById('disc_fee_percent').value = data.disc_fee_percent;
                AutoNumeric.getAutoNumericElement(inputDiscFeeAmount).set(data.disc_fee_amount || 0);
                
                document.getElementById('apply_rounding_discount').checked = data.apply_rounding_discount;
                AutoNumeric.getAutoNumericElement(inputRoundingAmount).set(data.rounding_discount_amount || 0);

                document.getElementById('tax_id').value = data.tax_id;
                
                document.getElementById('use_custom_dpp_factor').checked = data.use_custom_dpp_factor;
                if(data.use_custom_dpp_factor) document.getElementById('advancedTaxOptions').classList.remove('hidden');

                document.getElementById('custom_dpp_factor').value = data.custom_dpp_factor;
                AutoNumeric.getAutoNumericElement(inputShipping).set(data.shipping_amount || 0);

                productItemsContainer.innerHTML = ''; 
                if (data.products && data.products.length > 0) {
                    data.products.forEach(p => {
                        const row = addProductRow(false); 
                        const select = $(row.querySelector('.product-select'));
                        
                        select.val(p.productId).trigger('change.select2'); 
                        
                        setTimeout(() => {
                            row.querySelector('.quantity').value = p.quantity;
                            row.querySelector('.purchase-price-formatted').value = p.priceFormatted;
                            row.querySelector('.purchase-price-hidden').value = p.priceHidden;
                            row.querySelector('.update-master-price').checked = p.updateMaster;
                            
                            if(p.discounts) {
                                p.discounts.forEach(val => createDiscountInputForRow(row, val));
                            }
                            const selectedOption = select.find(':selected')[0];
                            if(selectedOption) {
                                row.querySelector('.unit-display').textContent = selectedOption.dataset.unit || '-';
                            }
                            calculateTotals();
                        }, 100);
                    });
                } else {
                    addProductRow();
                }

                setTimeout(() => {
                    calculateTotals();
                    isRestoring = false; 
                }, 500);

            } catch (e) {
                console.error('Error loading draft:', e);
                isRestoring = false;
                addProductRow();
            }
        }

        // === ROW MANAGEMENT ===
        function createDiscountInputForRow(row, value = '') {
            const index = row.dataset.index;
            const container = row.querySelector('.discount-container');
            const div = document.createElement('div');
            div.className = 'flex items-center gap-1';
            div.innerHTML = `
                <input type="number" step="any" min="0" max="100" class="discount-percentage text-xs w-10 text-center border border-slate-300 rounded shadow-sm focus:ring-1 focus:ring-indigo-500 p-0.5" placeholder="%" value="${value}" name="products[${index}][discounts][]">
                <button type="button" class="remove-discount-btn text-slate-300 hover:text-red-500 transition p-1 rounded-full hover:bg-red-50"><i class="material-icons text-sm">close</i></button>
            `;
            
            const input = div.querySelector('input');
            input.addEventListener('input', () => { calculateTotals(); saveFormState(); });

            div.querySelector('button').onclick = () => { 
                div.remove(); 
                calculateTotals(); 
                saveFormState(); 
            };
            
            container.appendChild(div);
        }

        function addProductRow(shouldCalculate = true) {
            const newRow = productRowTemplate.content.cloneNode(true).querySelector('tr');
            const currentIndex = productIndex;
            newRow.dataset.index = currentIndex;

            newRow.querySelector('.product-select').name = `products[${currentIndex}][product_id]`;
            newRow.querySelector('.quantity').name = `products[${currentIndex}][quantity]`;
            newRow.querySelector('.update-master-price').name = `products[${currentIndex}][update_master_price]`;

            const priceHidden = document.createElement('input');
            priceHidden.type = 'hidden';
            priceHidden.className = 'purchase-price-hidden';
            priceHidden.name = `products[${currentIndex}][price_per_unit]`;
            priceHidden.value = '0';
            newRow.querySelector('.purchase-price-formatted').parentElement.appendChild(priceHidden);

            productItemsContainer.appendChild(newRow);

            const selectElem = $(newRow.querySelector('.product-select'));
            selectElem.select2({ 
                placeholder: '-- Pilih Produk --', 
                theme: 'bootstrap-5', 
                width: '100%',
                dropdownCssClass: 'select2-dropdown-clean'
            });

            const qtyInput = newRow.querySelector('.quantity');
            const priceInput = newRow.querySelector('.purchase-price-formatted');
            const addDiscBtn = newRow.querySelector('.add-discount-btn');
            const delBtn = newRow.querySelector('.remove-product-btn');

            selectElem.on('select2:select', function(e) {
                const el = e.params.data.element;
                newRow.querySelector('.unit-display').textContent = el.dataset.unit || '-';
                const defPrice = el.dataset.defaultPrice || 0;
                priceHidden.value = defPrice;
                priceInput.value = formatThousands(defPrice);
                calculateTotals();
                saveFormState();
            });

            qtyInput.addEventListener('input', () => { calculateTotals(); saveFormState(); });
            
            priceInput.addEventListener('input', () => {
                priceHidden.value = parseNumericForInput(priceInput.value);
                calculateTotals();
                saveFormState();
            });
            
            priceInput.addEventListener('blur', () => {
                priceInput.value = formatThousands(priceHidden.value);
            });

            addDiscBtn.onclick = () => createDiscountInputForRow(newRow, '');
            
            delBtn.onclick = () => {
                Swal.fire({
                    title: 'Hapus Item?', text: "Baris ini akan dihapus permanen.", icon: 'warning',
                    showCancelButton: true, confirmButtonColor: '#ef4444', cancelButtonColor: '#94a3b8',
                    confirmButtonText: 'Ya, Hapus!', cancelButtonText: 'Batal'
                }).then((result) => {
                    if (result.isConfirmed) {
                        selectElem.select2('destroy');
                        newRow.remove();
                        calculateTotals();
                        saveFormState();
                    }
                });
            };

            newRow.querySelector('.row-select').addEventListener('change', saveFormState);
            
            productIndex++;
            
            if (shouldCalculate) {
                calculateTotals();
                saveFormState();
            }
            
            return newRow;
        }

        // === GLOBAL LISTENERS SETUP ===
        function setupGlobalListeners() {
            const calculationInputs = [
                document.getElementById('apply_disc_fee'), document.getElementById('disc_fee_percent'), 
                document.getElementById('apply_rounding_discount'), document.getElementById('rounding_discount_amount'), 
                document.getElementById('use_custom_dpp_factor'), document.getElementById('custom_dpp_factor'), 
                document.getElementById('tax_id')
            ];
            
            const autoNumericInputs = [inputDiscFeeAmount, inputRoundingAmount, inputShipping];

            autoNumericInputs.forEach(el => {
                el.addEventListener('autoNumeric:rawValueModified', () => { 
                    calculateTotals(); 
                    clearTimeout(window.saveTimeout); 
                    window.saveTimeout = setTimeout(saveFormState, 500); 
                });
            });

            calculationInputs.forEach(el => {
                if (el) { 
                    el.addEventListener('input', () => { 
                        calculateTotals(); 
                        clearTimeout(window.saveTimeout); 
                        window.saveTimeout = setTimeout(saveFormState, 500); 
                    });
                    el.addEventListener('change', () => { 
                        calculateTotals(); 
                        clearTimeout(window.saveTimeout); 
                        window.saveTimeout = setTimeout(saveFormState, 500); 
                    });
                }
            });

            $('#supplier_id, #requester_user_id').on('change', saveFormState);
            
            // Tombol "Tambah Item Baru"
            // *FIX* : Pastikan off('click') digunakan agar tidak ada event ganda saat load draft
            $('#add-product-btn').off('click').on('click', addProductRow); 
            
            const applyDiscountBulk = (rows) => {
                const v = parseFloat(bulkDiscountInput.value);
                if (isNaN(v) || v <= 0 || v > 100) return Swal.fire('Info', 'Masukkan angka diskon valid (0-100).', 'info');
                
                rows.forEach(r => {
                    createDiscountInputForRow(r, v);
                });
                
                bulkDiscountInput.value = '';
                calculateTotals();
                saveFormState();
            };

            applyAllBtn.onclick = () => applyDiscountBulk(getAllRows());
            applyBulkBtn.onclick = () => {
                const rows = getAllRows().filter(r => r.querySelector('.row-select').checked);
                if (rows.length === 0) return Swal.fire('Info', 'Pilih baris terlebih dahulu.', 'info');
                applyDiscountBulk(rows);
            };

            selectAllBtn.onclick = () => {
                getAllRows().forEach(row => {
                    row.querySelector('.row-select').checked = true;
                });
                document.getElementById('header-row-select').checked = true;
                saveFormState();
            };

            deselectAllBtn.onclick = () => {
                getAllRows().forEach(row => {
                    row.querySelector('.row-select').checked = false;
                });
                document.getElementById('header-row-select').checked = false;
                saveFormState();
            };

            document.getElementById('header-row-select').addEventListener('change', function() {
                const isChecked = this.checked;
                getAllRows().forEach(row => {
                    row.querySelector('.row-select').checked = isChecked;
                });
                saveFormState();
            });
            
            form.addEventListener('submit', (e) => {
                if (getAllRows().length === 0) { 
                    e.preventDefault(); 
                    Swal.fire('Perhatian', 'Harap tambahkan minimal satu produk.', 'warning');
                    return; 
                }

                const dppInput = document.getElementById('custom_dpp_factor');
                if (dppInput && dppInput.value) {
                    dppInput.value = parseFractionOrNumber(dppInput.value); 
                }
                
                localStorage.removeItem(DRAFT_KEY);
            });
            
            clearDraft = function() {
                Swal.fire({
                    title: 'Hapus Draft?',
                    text: "Data yang belum disimpan akan hilang.",
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonColor: '#d33',
                    confirmButtonText: 'Ya, Hapus!',
                    cancelButtonText: 'Batal'
                }).then((result) => {
                    if (result.isConfirmed) {
                        localStorage.removeItem(DRAFT_KEY);
                        location.reload();
                    }
                });
            };
        }

        setupGlobalListeners();
        loadFormState();
    });
</script>
@endpush
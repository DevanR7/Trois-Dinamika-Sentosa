@extends('layouts.app')

@section('title', 'Koreksi Otomatis PO')

@section('content')
<div class="max-w-7xl mx-auto pb-20 animate-enter">
    
    {{-- HEADER HALAMAN --}}
    <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center mb-6 gap-4">
        <div>
            <div class="flex items-center gap-2 text-xs font-bold text-slate-400 uppercase tracking-wider mb-1">
                <a href="{{ route('purchase-order-adjustments.create') }}" class="hover:text-indigo-600 transition">Penyesuaian</a>
                <i class="material-icons text-[12px]">chevron_right</i>
                <span class="text-slate-600">Otomatis</span>
            </div>
            <h2 class="text-2xl font-bold text-slate-800 tracking-tight">Koreksi PO: <span class="text-indigo-600 font-mono ml-1">{{ $purchaseOrder->po_number }}</span></h2>
        </div>
        <a href="{{ route('purchase-order-adjustments.create') }}" class="inline-flex items-center px-4 py-2 bg-white border border-slate-300 rounded-lg text-slate-600 text-sm font-bold hover:bg-slate-50 transition shadow-sm">
            <i class="material-icons text-sm mr-2">arrow_back</i> Kembali
        </a>
    </div>

    {{-- INFO ALERT --}}
    <div class="mb-6 bg-blue-50/50 border border-blue-100 rounded-xl p-4 flex items-start gap-3 shadow-sm">
        <i class="material-icons text-blue-500 text-xl mt-0.5">info</i>
        <div>
            <h3 class="text-sm font-bold text-blue-800">Mode Koreksi Otomatis</h3>
            <p class="text-sm text-blue-700/80 mt-1 leading-relaxed">
                Silakan ubah data Qty/Harga/Diskon item. Sistem akan otomatis menghitung selisihnya (Nota Debet/Kredit) tanpa mengubah PO asli.
            </p>
        </div>
    </div>

    {{-- ERROR HANDLING --}}
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

    <form action="{{ route('purchase-order-adjustments.store.auto', $purchaseOrder->po_id) }}" method="POST" id="po-form">
        @csrf

        <div class="grid grid-cols-1 lg:grid-cols-12 gap-6">
            
            {{-- KOLOM KIRI: FORM ITEM (Span 8) --}}
            <div class="lg:col-span-8 space-y-6">
                
                {{-- 1. INFORMASI PO ASLI (READONLY) --}}
                <div class="dashboard-card p-0 overflow-hidden">
                    <div class="bg-slate-50/50 px-6 py-4 border-b border-slate-100">
                        <h3 class="text-xs font-bold text-slate-500 uppercase tracking-wider flex items-center gap-2">
                            <i class="material-icons text-indigo-500 text-sm">description</i> Informasi PO Asli
                        </h3>
                    </div>
                    <div class="p-6 grid grid-cols-1 md:grid-cols-3 gap-6">
                        
                        <div>
                            <label class="block text-[11px] font-bold text-slate-500 uppercase mb-2 ml-1">Supplier</label>
                            <input type="text" class="form-input bg-slate-50 cursor-not-allowed text-slate-500" value="{{ $purchaseOrder->supplier->supplier_name }}" readonly>
                            <input type="hidden" name="supplier_id" value="{{ $purchaseOrder->supplier_id }}">
                            <input type="hidden" name="order_date" value="{{ optional($purchaseOrder->order_date)->format('Y-m-d') }}">
                            <input type="hidden" name="requester_user_id" value="{{ $purchaseOrder->requester_user_id }}">
                            <input type="hidden" name="due_date" value="{{ optional($purchaseOrder->due_date)->format('Y-m-d') }}">
                        </div>

                        <div>
                            <label class="block text-[11px] font-bold text-slate-500 uppercase mb-2 ml-1">Tanggal Pesan</label>
                            <input type="text" class="form-input bg-slate-50 cursor-not-allowed text-slate-500" value="{{ optional($purchaseOrder->order_date)->format('d M Y') }}" readonly>
                        </div>

                        <div>
                            <label class="block text-[11px] font-bold text-slate-500 uppercase mb-2 ml-1">Dipesan Oleh</label>
                            <input type="text" class="form-input bg-slate-50 cursor-not-allowed text-slate-500" value="{{ $purchaseOrder->requester->full_name ?? '-- Umum --' }}" readonly>
                        </div>
                    </div>
                </div>

                {{-- 2. CARD REVISI ITEM --}}
                <div class="dashboard-card p-0 overflow-hidden">
                    <div class="px-6 py-4 border-b border-slate-100 bg-slate-50/50 flex justify-between items-center">
                        <h3 class="text-xs font-bold text-slate-500 uppercase tracking-wider flex items-center gap-2">
                            <i class="material-icons text-indigo-500 text-sm">inventory_2</i> Revisi Item
                        </h3>
                        <button type="button" id="add-product-btn" class="inline-flex items-center px-3 py-1.5 bg-indigo-50 text-indigo-700 text-xs font-bold rounded-lg hover:bg-indigo-100 border border-indigo-200 transition">
                            <i class="material-icons text-sm mr-1">add</i> Tambah Item
                        </button>
                    </div>

                    {{-- Toolbar Bulk Action --}}
                    <div class="px-6 py-3 border-b border-slate-100 bg-white">
                        <div class="flex flex-wrap items-center justify-between gap-3">
                            <div class="flex items-center gap-2 bg-slate-50 px-3 py-1.5 rounded-lg border border-slate-200">
                                <span class="text-xs font-bold text-slate-500 uppercase"><i class="material-icons text-sm mr-1">check_circle_outline</i> Bulk:</span>
                                <button type="button" id="select-all-btn" class="text-xs font-medium text-indigo-600 hover:text-indigo-800 hover:underline">All</button>
                                <span class="text-slate-300">|</span>
                                <button type="button" id="deselect-all-btn" class="text-xs font-medium text-slate-500 hover:text-slate-800 hover:underline">None</button>
                            </div>
                            
                            <div class="flex items-center gap-0 shadow-sm rounded-md">
                                <span class="bg-slate-100 border border-slate-300 border-r-0 text-slate-500 text-xs px-3 py-2 rounded-l-md font-medium">Disc Massal</span>
                                <input id="bulk-discount-input" type="number" step="any" min="0" class="w-20 form-input rounded-none text-xs h-[34px] py-1" placeholder="%">
                                <button type="button" id="apply-bulk-discount-btn" class="bg-indigo-600 hover:bg-indigo-700 text-white text-xs font-bold px-3 py-2 h-[34px] transition rounded-r-md">Set Selected</button>
                            </div>
                        </div>
                    </div>

                    {{-- TABLE ITEM (OPTIMIZED FOR FIT) --}}
                    <div class="overflow-x-auto">
                        <table class="dashboard-table">
                            <thead>
                                <tr>
                                    <th class="px-2 py-2 text-center w-8">
                                        <input type="checkbox" class="rounded border-slate-300 text-indigo-600 focus:ring-indigo-500 cursor-pointer h-4 w-4" id="header-row-select">
                                    </th>
                                    <th class="px-2 py-2 text-xs font-bold text-slate-500 uppercase min-w-[200px]">Produk</th>
                                    <th class="px-2 py-2 text-xs font-bold text-slate-500 uppercase w-20 text-center">Qty</th>
                                    <th class="px-2 py-2 text-xs font-bold text-slate-500 uppercase w-32 text-right">Harga (@)</th>
                                    <th class="px-2 py-2 text-xs font-bold text-slate-500 uppercase w-24 text-center">Diskon</th>
                                    <th class="px-2 py-2 text-xs font-bold text-slate-500 uppercase w-28 text-right">Subtotal</th>
                                    <th class="px-2 py-2 text-center w-8"><i class="material-icons text-slate-400 text-sm">settings</i></th>
                                </tr>
                            </thead>
                            <tbody id="product-items" class="divide-y divide-slate-100">
                                {{-- JS Injects Rows Here --}}
                            </tbody>
                        </table>
                    </div>

                    {{-- Alasan Koreksi --}}
                    <div class="p-6 border-t border-slate-100 bg-slate-50/50">
                        <label for="notes" class="block text-[11px] font-bold text-slate-500 uppercase mb-2 ml-1">Alasan Koreksi <span class="text-red-500">*</span></label>
                        <textarea class="form-textarea bg-white" name="notes" id="notes" rows="2" placeholder="Contoh: Salah input harga dari supplier..." required>{{ old('notes') }}</textarea>
                    </div>
                </div>
            </div>

            {{-- KOLOM KANAN: INFO PO & KALKULASI (Span 4) --}}
            <div class="lg:col-span-4 space-y-6">
                
                {{-- CARD KALKULASI REVISI --}}
                <div class="dashboard-card p-0 sticky top-6 z-30">
                    <div class="px-6 py-4 border-b border-slate-100 bg-slate-50/50 flex items-center gap-2">
                        <i class="material-icons text-slate-400 text-sm">calculate</i>
                        <h3 class="text-sm font-bold text-slate-700 uppercase tracking-wider">Kalkulasi Revisi</h3>
                    </div>

                    <div class="p-6 space-y-5">
                        
                        {{-- Diskon --}}
                        <div>
                            <div class="flex items-center justify-between mb-2">
                                <label class="text-[11px] font-bold text-slate-500 uppercase cursor-pointer" for="apply_disc_fee">Diskon Header</label>
                                <input type="checkbox" name="apply_disc_fee" id="apply_disc_fee" value="1" class="rounded border-slate-300 text-indigo-600 focus:ring-indigo-500 h-4 w-4 cursor-pointer" {{ old('apply_disc_fee', $purchaseOrder->apply_disc_fee) ? 'checked' : '' }}>
                            </div>
                            <div class="p-3 bg-slate-50 rounded-lg border border-slate-200 {{ old('apply_disc_fee', $purchaseOrder->apply_disc_fee) ? '' : 'hidden' }}" id="disc-fee-container">
                                <div class="grid grid-cols-2 gap-2">
                                    <div>
                                        <label class="block text-[10px] text-slate-400 mb-1">Diskon %</label>
                                        <input type="number" step="any" min="0" class="form-input text-xs h-8 px-2" name="disc_fee_percent" id="disc_fee_percent" placeholder="0" value="{{ old('disc_fee_percent', $purchaseOrder->disc_fee_percent) }}">
                                    </div>
                                    <div>
                                        <label class="block text-[10px] text-slate-400 mb-1">Nominal (Rp)</label>
                                        <input type="text" class="form-input text-xs h-8 px-2 text-right" name="disc_fee_amount" id="disc_fee_amount" placeholder="0" value="{{ old('disc_fee_amount', $purchaseOrder->disc_fee_amount) }}">
                                    </div>
                                </div>
                            </div>
                        </div>

                        {{-- Pembulatan --}}
                        <div>
                            <div class="flex items-center justify-between mb-2">
                                <label class="text-[11px] font-bold text-slate-500 uppercase cursor-pointer" for="apply_rounding_discount">Diskon Pembulatan</label>
                                <input type="checkbox" id="apply_rounding_discount" name="apply_rounding_discount" value="1" class="rounded border-slate-300 text-indigo-600 focus:ring-indigo-500 h-4 w-4 cursor-pointer" {{ old('apply_rounding_discount', $purchaseOrder->apply_rounding_discount) ? 'checked' : '' }}>
                            </div>
                            <div class="relative">
                                <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                    <span class="text-slate-400 text-xs font-bold">Rp</span>
                                </div>
                                <input type="text" class="form-input pl-8 text-right text-xs font-bold" name="rounding_discount_amount" id="rounding_discount_amount" placeholder="0" value="{{ old('rounding_discount_amount', $purchaseOrder->rounding_discount_amount) }}">
                            </div>
                        </div>

                        <div class="h-px bg-slate-200 border-none"></div>

                        {{-- Pajak --}}
                        <div>
                            <label class="block text-[11px] font-bold text-slate-500 uppercase mb-2 ml-1">Pajak (PPN)</label>
                            <select name="tax_id" id="tax_id" class="form-select text-xs h-9">
                                <option value="">-- Tanpa Pajak --</option>
                                @foreach(\App\Models\Tax::where('is_active', true)->get() as $tax)
                                    <option value="{{ $tax->id }}" data-rate="{{ $tax->rate }}" {{ old('tax_id', $purchaseOrder->tax_id) == $tax->id ? 'selected' : '' }}>
                                        {{ $tax->name }} ({{ $tax->rate }}%)
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        {{-- Opsi Lanjutan --}}
                        <div>
                            <a class="text-xs text-indigo-600 font-semibold cursor-pointer hover:underline flex items-center" id="toggle-advanced-tax">
                                <i class="material-icons text-[12px] mr-1">tune</i> Opsi Pajak Lanjutan
                            </a>
                            <div class="hidden mt-2 p-3 bg-slate-50 rounded border border-slate-200" id="advancedTaxOptions">
                                <div class="flex items-center mb-2">
                                    <input type="checkbox" id="use_custom_dpp_factor" name="use_custom_dpp_factor" value="1" class="rounded border-slate-300 text-indigo-600 h-4 w-4 mr-2" {{ old('use_custom_dpp_factor', $purchaseOrder->use_custom_dpp_factor) ? 'checked' : '' }}>
                                    <label class="text-xs text-slate-700" for="use_custom_dpp_factor">Override Faktor DPP</label>
                                </div>
                                <input type="text" class="form-input text-xs h-8" name="custom_dpp_factor" id="custom_dpp_factor" placeholder="Contoh: 11/12" value="{{ old('custom_dpp_factor', $purchaseOrder->custom_dpp_factor) }}">
                            </div>
                        </div>

                        {{-- Ongkir --}}
                        <div>
                            <label class="block text-[11px] font-bold text-slate-500 uppercase mb-2 ml-1">Ongkos Kirim</label>
                            <div class="relative">
                                <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                    <span class="text-slate-400 text-xs font-bold">Rp</span>
                                </div>
                                <input type="text" class="form-input pl-8 text-right font-bold" name="shipping_amount" id="shipping_amount" value="{{ old('shipping_amount', $purchaseOrder->shipping_amount ?? 0) }}">
                            </div>
                        </div>
                    </div>

                    {{-- SUMMARY TOTALS --}}
                    <div class="bg-slate-50 rounded-lg p-4 border border-slate-200 mx-4 mb-4 space-y-2">
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

                        <div class="flex justify-between mb-1 text-[10px] text-slate-400">
                            <span>DPP</span>
                            <span id="summary-dpp">Rp 0</span>
                        </div>
                        <div class="flex justify-between mb-1 text-xs text-slate-600">
                            <span>PPN (<span id="summary-tax-rate">0</span>%)</span>
                            <span class="font-bold text-slate-800" id="summary-ppn">Rp 0</span>
                        </div>
                        <div class="flex justify-between mb-2 text-xs text-slate-600">
                            <span>Ongkir</span>
                            <span class="font-bold text-slate-800" id="summary-shipping">Rp 0</span>
                        </div>
                        
                        <div class="border-t-2 border-dashed border-slate-300 my-3 pt-2"></div>

                        <div class="flex justify-between items-center">
                            <span class="text-sm font-bold text-slate-800">TOTAL REVISI</span>
                            <span class="text-xl font-bold text-indigo-600" id="summary-grand">Rp 0</span>
                        </div>
                    </div>

                    {{-- OPSI KELEBIHAN BAYAR --}}
                    <div class="mt-4 p-4 bg-amber-50/50 border border-amber-200 rounded-lg mx-4">
                        <label class="block text-[10px] font-bold text-amber-700 uppercase mb-2">Jika Ada Kelebihan Bayar (Nota Kredit):</label>
                        <div class="space-y-2">
                            <div class="flex items-center">
                                <input class="rounded border-amber-300 text-amber-600 focus:ring-amber-500 h-4 w-4 mr-2" type="radio" name="overpayment_action" id="overpayment_deposit" value="deposit" checked>
                                <label class="text-xs text-amber-800 cursor-pointer" for="overpayment_deposit">Simpan ke Deposit Supplier</label>
                            </div>
                            <div class="flex items-center">
                                <input class="rounded border-amber-300 text-amber-600 focus:ring-amber-500 h-4 w-4 mr-2" type="radio" name="overpayment_action" id="overpayment_refund" value="refund">
                                <label class="text-xs text-amber-800 cursor-pointer" for="overpayment_refund">Refund Manual (Saldo Minus)</label>
                            </div>
                        </div>
                    </div>

                    {{-- ACTIONS --}}
                    <div class="flex flex-col gap-3 mt-6 px-6 pb-6">
                        <button type="submit" class="w-full flex justify-center py-3 px-4 border border-transparent rounded-lg shadow-md text-sm font-bold text-white bg-emerald-600 hover:bg-emerald-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-emerald-500 transition" id="submit-btn">
                            <i class="material-icons text-sm mr-2">save</i> Simpan Koreksi
                        </button>
                        <a href="{{ route('purchase-order-adjustments.create') }}" class="w-full flex justify-center py-3 px-4 border border-slate-300 rounded-lg shadow-sm text-sm font-bold text-slate-600 bg-white hover:bg-slate-50 transition">Batal</a>
                    </div>
                </div>
            </div>
        </div>
    </form>
</div>

{{-- TEMPLATE ROW PRODUK (Optimized) --}}
<template id="product-row-template">
    <tr class="hover:bg-slate-50 transition-colors border-b border-slate-100 last:border-0">
        {{-- 1. Checkbox --}}
        <td class="text-center align-top py-2 px-2">
            <input type="checkbox" class="row-select rounded border-slate-300 text-indigo-600 focus:ring-indigo-500 cursor-pointer h-4 w-4 mt-2">
        </td>

        {{-- 2. Produk (Stack Select2) --}}
        <td class="align-top py-2 px-2">
            <select class="product-select form-select text-xs w-full" required>
                <option value="" data-unit="-" data-default-discounts="[]" disabled selected>-- Cari --</option>
                @foreach ($products as $product)
                    <option value="{{ $product->product_id }}"
                            data-unit="{{ $product->unit->name ?? '' }}"
                            data-default-discounts='@json($product->default_discounts ?? [])'
                            data-default-price="{{ $product->purchase_price ?? 0 }}">
                        {{ $product->product_name }}
                    </option>
                @endforeach
            </select>
            
            {{-- Checkbox Update Master (Kecil di bawah) --}}
            <div class="flex items-center mt-1 ml-1">
                <input class="update-master-price rounded border-slate-300 text-indigo-600 focus:ring-indigo-500 h-3 w-3 mr-1" type="checkbox" value="1">
                <label class="text-[10px] text-slate-500 italic cursor-pointer">Update Master</label>
            </div>
        </td>

        {{-- 3. Qty (Compact Input) --}}
        <td class="align-top py-2 px-2">
            <div class="flex items-center rounded-md bg-white border border-slate-300 focus-within:border-indigo-500 focus-within:ring-1 focus-within:ring-indigo-500 h-8 overflow-hidden">
                <input type="number" class="quantity text-center font-bold bg-transparent border-0 focus:ring-0 p-0 h-full w-full text-xs" value="1" min="1" step="any" required>
                <span class="bg-slate-100 text-slate-500 text-[9px] px-1 h-full flex items-center border-l border-slate-200 unit-display font-medium min-w-[20px] justify-center">-</span>
            </div>
        </td>

        {{-- 4. Harga (Compact Input) --}}
        <td class="align-top py-2 px-2">
            <div class="relative flex items-center border rounded-md border-slate-300 bg-white focus-within:border-indigo-500 h-8 px-1">
                <span class="text-slate-400 text-[10px] mr-1">Rp</span>
                <input type="text" class="purchase-price-formatted text-right p-0 border-none focus:ring-0 w-full text-xs font-medium">
                <input type="hidden" class="purchase-price-hidden" value="0">
            </div>
        </td>

        {{-- 5. Diskon (Stack Vertical) --}}
        <td class="align-top py-2 px-2">
            <div class="discount-container space-y-1"></div>
            <button type="button" class="w-full mt-1 text-[10px] text-indigo-600 font-bold uppercase tracking-wide add-discount-btn flex justify-center items-center bg-indigo-50 px-1 py-0.5 rounded hover:bg-indigo-100 transition border border-indigo-100">
                + Disc
            </button>
        </td>

        {{-- 6. Subtotal --}}
        <td class="text-right py-3 px-2 align-top font-bold text-slate-800 text-xs">
            <span class="subtotal">Rp 0</span>
        </td>

        {{-- 7. Delete --}}
        <td class="text-center py-2 px-2 align-top">
            <button type="button" class="text-slate-300 hover:text-red-500 hover:bg-red-50 rounded p-1 transition remove-product-btn" title="Hapus">
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
    const existingPoItems = @json($purchaseOrder->items->load('discounts') ?? []);
    const form = document.getElementById('po-form');
    const productItemsContainer = document.getElementById('product-items');
    const productRowTemplate = document.getElementById('product-row-template');
    const addProductBtn = document.getElementById('add-product-btn');
    const selectAllBtn = document.getElementById('select-all-btn');
    const deselectAllBtn = document.getElementById('deselect-all-btn');
    const applyBulkBtn = document.getElementById('apply-bulk-discount-btn');
    const applyAllBtn = document.getElementById('apply-all-discount-btn');
    const bulkDiscountInput = document.getElementById('bulk-discount-input');
    const headerRowSelect = document.getElementById('header-row-select');
    
    // Summary elements
    const elSummarySubtotal = document.getElementById('summary-subtotal');
    const elSummaryDisc = document.getElementById('summary-disc');
    const elSummaryRounding = document.getElementById('summary-rounding');
    const elSummaryTaxable = document.getElementById('summary-taxable');
    const elSummaryDpp = document.getElementById('summary-dpp');
    const elSummaryPpn = document.getElementById('summary-ppn');
    const elSummaryGrand = document.getElementById('summary-grand');
    const elSummaryShipping = document.getElementById('summary-shipping');
    const elSummaryTaxRate = document.getElementById('summary-tax-rate');
    
    // Calculation inputs
    const inputApplyDiscFee = document.getElementById('apply_disc_fee');
    const inputDiscFeePercent = document.getElementById('disc_fee_percent');
    const inputDiscFeeAmount = document.getElementById('disc_fee_amount');
    const inputApplyRounding = document.getElementById('apply_rounding_discount');
    const inputRoundingAmount = document.getElementById('rounding_discount_amount');
    const inputTaxId = document.getElementById('tax_id');
    const inputUseCustomDpp = document.getElementById('use_custom_dpp_factor');
    const inputCustomDppFactor = document.getElementById('custom_dpp_factor');
    const inputShipping = document.getElementById('shipping_amount');
    
    let productIndex = 0;
    const autoNumericInstances = new Map();

    // AutoNumeric options
    const anOptions = { 
        decimalCharacter: ',', 
        digitGroupSeparator: '.', 
        decimalPlaces: 0, 
        minimumValue: '0', 
        emptyInputBehavior: 'zero' 
    };

    // Initialize AutoNumeric for header/summary currency fields
    new AutoNumeric(inputDiscFeeAmount, anOptions);
    new AutoNumeric(inputRoundingAmount, anOptions);
    new AutoNumeric(inputShipping, anOptions);

    // --- HELPER FUNCTIONS ---
    function formatCurrency(n) {
        if (n === null || n === undefined) return 'Rp 0';
        return new Intl.NumberFormat('id-ID', { style: 'currency', currency: 'IDR', minimumFractionDigits: 0 }).format(Math.round(n));
    }

    function parseNumericForInput(str) {
        if (!str && str !== 0) return 0;
        let s = String(str).replace(/[^\d\-\.\,]/g, '');
        s = s.replace(/\./g, '').replace(/,/g, '.');
        const v = parseFloat(s);
        return isNaN(v) ? 0 : v;
    }

    function getRawNumericValue(elementId) {
        const el = document.getElementById(elementId);
        if (AutoNumeric.getAutoNumericElement(el)) {
            return parseFloat(AutoNumeric.getAutoNumericElement(el).getNumericString()) || 0;
        }
        return parseNumericForInput(el.value);
    }

    function getAllRows() {
        return Array.from(productItemsContainer.querySelectorAll('tr'));
    }

    function parseFractionOrNumber(val) {
        if (typeof val !== 'string' || !val) return 1;
        val = val.trim().replace(',', '.');
        if (val.includes('/')) {
            const parts = val.split('/');
            if (parts.length === 2) {
                const num = parseFloat(parts[0]);
                const den = parseFloat(parts[1]);
                if (!isNaN(num) && !isNaN(den) && den !== 0) return num / den;
            }
        }
        const parsed = parseFloat(val);
        return isNaN(parsed) ? 1 : parsed;
    }

    function getSelectedTaxRate() {
        const opt = inputTaxId.selectedOptions[0];
        return (opt && opt.value) ? parseFloat(opt.dataset.rate) : null;
    }

    // --- ITEM ROW LOGIC ---
    function calculateRowSubtotal(row) {
        const quantity = parseFloat(row.querySelector('.quantity').value) || 0;
        const price = parseFloat(row.querySelector('.purchase-price-hidden')?.value || 0);
        let finalPrice = price;
        
        row.querySelectorAll('.discount-percentage').forEach(d => {
            const rate = parseFloat(d.value) || 0;
            if (rate > 0 && rate <= 100) finalPrice *= (1 - rate / 100);
        });
        
        row.querySelector('.subtotal').textContent = formatCurrency(quantity * finalPrice);
    }
    
    function createDiscountInputForRow(row, value = '') {
        const index = row.dataset.index;
        const container = row.querySelector('.discount-container');
        const div = document.createElement('div');
        div.className = 'flex items-center gap-1';
        div.innerHTML = `
            <input type="number" step="any" class="w-10 rounded border-slate-300 text-xs py-1 text-center" placeholder="%" value="${value}" name="products[${index}][discounts][]">
            <button type="button" class="text-slate-300 hover:text-red-500 text-xs px-1 hover:bg-red-50 rounded">
                <i class="material-icons text-sm">close</i>
            </button>
        `;
        
        const input = div.querySelector('input');
        input.oninput = () => { 
            calculateRowSubtotal(row); 
            calculateTotals(); 
        };
        
        div.querySelector('button').onclick = () => { 
            div.remove(); 
            calculateRowSubtotal(row); 
            calculateTotals(); 
        };
        
        container.appendChild(div);
    }

    function addProductRow(shouldCalculate = true) {
        const newRow = productRowTemplate.content.cloneNode(true).querySelector('tr');
        const rowIndex = productIndex;
        newRow.dataset.index = rowIndex;
        
        const productSelect = newRow.querySelector('.product-select');
        const quantityInput = newRow.querySelector('.quantity');
        const formattedPriceInput = newRow.querySelector('.purchase-price-formatted');
        const updateMasterCheckbox = newRow.querySelector('.update-master-price');
        const addDiscountBtn = newRow.querySelector('.add-discount-btn');
        const removeBtn = newRow.querySelector('.remove-product-btn');
        
        const priceHiddenInput = document.createElement('input');
        priceHiddenInput.type = 'hidden';
        priceHiddenInput.className = 'purchase-price-hidden';
        priceHiddenInput.name = `products[${rowIndex}][price_per_unit]`;
        priceHiddenInput.value = '0';
        formattedPriceInput.parentElement.appendChild(priceHiddenInput);
        
        // Set names
        productSelect.name = `products[${rowIndex}][product_id]`;
        quantityInput.name = `products[${rowIndex}][quantity]`;
        updateMasterCheckbox.name = `products[${rowIndex}][update_master_price]`;
        
        productItemsContainer.appendChild(newRow);
        
        // Init AutoNumeric for price input
        const anInstance = new AutoNumeric(formattedPriceInput, anOptions);
        autoNumericInstances.set(rowIndex, anInstance);

        // Init Select2
        const select2 = $(productSelect).select2({ 
            placeholder: '-- Pilih Produk --', 
            theme: 'bootstrap-5', 
            width: '100%',
            dropdownCssClass: 'select2-dropdown-clean',
            dropdownParent: $(productSelect).parent() 
        });
        
        // Event Listeners for the new row
        select2.on('select2:select', function(e) {
            const el = e.params.data.element;
            newRow.querySelector('.unit-display').textContent = el.dataset.unit || '-';
            const defaultPrice = el.dataset.defaultPrice || 0;
            priceHiddenInput.value = defaultPrice;
            anInstance.set(defaultPrice);
            
            // Clear existing discounts and add defaults
            newRow.querySelector('.discount-container').innerHTML = '';
            try { 
                JSON.parse(el.dataset.defaultDiscounts || '[]').forEach(d => 
                    createDiscountInputForRow(newRow, d.percentage)
                ); 
            } catch (err) {}
            
            calculateRowSubtotal(newRow);
            if (shouldCalculate) calculateTotals();
        });

        addDiscountBtn.onclick = () => createDiscountInputForRow(newRow, '');
        
        removeBtn.onclick = () => {
             Swal.fire({
                title: 'Hapus Item?', text: "Baris ini akan dihapus permanen.", icon: 'warning',
                showCancelButton: true, confirmButtonColor: '#ef4444', cancelButtonColor: '#94a3b8',
                confirmButtonText: 'Ya, Hapus!', cancelButtonText: 'Batal'
            }).then((result) => {
                if (result.isConfirmed) {
                    $(productSelect).select2('destroy');
                    autoNumericInstances.delete(rowIndex);
                    newRow.remove();
                    if (shouldCalculate) calculateTotals();
                }
            });
        };

        const updatePrice = () => {
            priceHiddenInput.value = anInstance.getNumericString() || 0;
            calculateRowSubtotal(newRow);
            if (shouldCalculate) calculateTotals();
        };
        
        formattedPriceInput.addEventListener('autoNumeric:rawValueModified', updatePrice);
        quantityInput.oninput = () => { 
            calculateRowSubtotal(newRow); 
            if (shouldCalculate) calculateTotals(); 
        };
        
        productIndex++;
        return newRow;
    }

    // --- MAIN CALCULATION FUNCTION ---
    function calculateTotals() {
        let subtotalBarang = getAllRows().reduce((total, row) => {
            const quantity = parseFloat(row.querySelector('.quantity').value) || 0;
            const price = parseFloat(row.querySelector('.purchase-price-hidden')?.value || 0);
            let finalPrice = price;
            row.querySelectorAll('.discount-percentage').forEach(d => {
                const rate = parseFloat(d.value) || 0;
                if (rate > 0 && rate <= 100) finalPrice *= (1 - rate / 100);
            });
            return total + (quantity * finalPrice);
        }, 0);
        
        let discFeeAmount = 0;
        if (inputApplyDiscFee.checked) {
            const percent = parseFloat(inputDiscFeePercent.value) || 0;
            const fixed = getRawNumericValue('disc_fee_amount');
            if (percent > 0) discFeeAmount = (percent / 100.0) * subtotalBarang;
            else if (fixed > 0) discFeeAmount = fixed;
        }
        
        const roundingAmount = inputApplyRounding.checked ? getRawNumericValue('rounding_discount_amount') : 0;
        const taxableBase = Math.max(0, subtotalBarang - discFeeAmount - roundingAmount);
        
        let dpp = taxableBase, ppn = 0;
        let taxRate = getSelectedTaxRate();
        
        if (taxRate !== null) {
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
        
        // Render View
        elSummarySubtotal.textContent = formatCurrency(subtotalBarang);
        elSummaryDisc.textContent = formatCurrency(discFeeAmount);
        elSummaryRounding.textContent = formatCurrency(roundingAmount);
        elSummaryTaxable.textContent = formatCurrency(taxableBase);
        elSummaryDpp.textContent = formatCurrency(dpp);
        elSummaryPpn.textContent = formatCurrency(ppn);
        elSummaryShipping.textContent = formatCurrency(shipping);
        elSummaryGrand.textContent = formatCurrency(grandTotal);
        elSummaryTaxRate.textContent = taxRate;

        // Sync inputs (important for AutoNumeric blur state)
        if (inputRoundingAmount) AutoNumeric.getAutoNumericElement(inputRoundingAmount).set(roundingAmount);
    }

    // --- INITIALIZATION ---
    function populateExistingItems() {
        if (existingPoItems && existingPoItems.length > 0) {
            existingPoItems.forEach(item => {
                const newRow = addProductRow(false);
                const productSelect = newRow.querySelector('.product-select');
                const quantityInput = newRow.querySelector('.quantity');
                const hiddenPriceInput = newRow.querySelector('.purchase-price-hidden');
                const anInstance = autoNumericInstances.get(parseInt(newRow.dataset.index));

                hiddenPriceInput.value = item.price_per_unit;
                if (anInstance) anInstance.set(item.price_per_unit);
                quantityInput.value = item.quantity;

                // Select2 must be populated AFTER options are available
                $(productSelect).val(item.product_id).trigger('change.select2');
                
                const selectedOption = productSelect.options[productSelect.selectedIndex];
                if (selectedOption) {
                    newRow.querySelector('.unit-display').textContent = selectedOption.dataset.unit || '-';
                }

                newRow.querySelector('.discount-container').innerHTML = '';
                if (item.discounts && item.discounts.length > 0) {
                    item.discounts.forEach(discount => 
                        createDiscountInputForRow(newRow, discount.percentage)
                    );
                }
                calculateRowSubtotal(newRow);
            });
        } else {
            addProductRow(true);
        }
        calculateTotals();
    }

    function setupEventListeners() {
        // Toggle advanced tax options
        document.getElementById('toggle-advanced-tax').addEventListener('click', function() {
            document.getElementById('advancedTaxOptions').classList.toggle('hidden');
        });

        // Toggle disc/fee container
        inputApplyDiscFee.addEventListener('change', function() {
            document.getElementById('disc-fee-container').classList.toggle('hidden', !this.checked);
            calculateTotals();
        });

        // Header checkbox
        headerRowSelect.onchange = (e) => 
            getAllRows().forEach(r => r.querySelector('.row-select').checked = e.target.checked);
        
        selectAllBtn.onclick = () => 
            getAllRows().forEach(r => r.querySelector('.row-select').checked = true);
        
        deselectAllBtn.onclick = () => 
            getAllRows().forEach(r => r.querySelector('.row-select').checked = false);

        const applyDiscount = (rows) => {
            const v = parseFloat(bulkDiscountInput.value);
            if (isNaN(v)) return Swal.fire('Info', 'Masukkan angka diskon valid.', 'info');
            rows.forEach(r => createDiscountInputForRow(r, v));
            rows.forEach(r => calculateRowSubtotal(r));
            calculateTotals();
        };

        applyBulkBtn.onclick = () => {
            const rows = getAllRows().filter(r => r.querySelector('.row-select').checked);
            if (rows.length === 0) return Swal.fire('Info', 'Pilih baris terlebih dahulu atau gunakan Tambah Item.', 'info');
            applyDiscount(rows);
        };

        applyAllBtn.onclick = () => applyDiscount(getAllRows());
        
        addProductBtn.onclick = () => addProductRow();

        // Calculate totals on input changes
        [inputApplyDiscFee, inputDiscFeePercent, inputApplyRounding, 
         inputTaxId, inputUseCustomDpp, inputCustomDppFactor].forEach(el => {
            el.addEventListener('input', calculateTotals);
            el.addEventListener('change', calculateTotals);
        });

        // AutoNumeric fields
        inputDiscFeeAmount.addEventListener('autoNumeric:rawValueModified', calculateTotals);
        inputRoundingAmount.addEventListener('autoNumeric:rawValueModified', calculateTotals);
        inputShipping.addEventListener('autoNumeric:rawValueModified', calculateTotals);

        // Form validation
        form.addEventListener('submit', (e) => {
            if (getAllRows().length === 0) { 
                e.preventDefault(); 
                Swal.fire('Perhatian', 'Harap tambahkan setidaknya satu item produk.', 'warning'); 
                return; 
            }
            // Ensure final form data has calculated DPP factor if used
            inputCustomDppFactor.value = parseFractionOrNumber(inputCustomDppFactor.value);
        });
    }

    setupEventListeners();
    populateExistingItems();
});
</script>
@endpush
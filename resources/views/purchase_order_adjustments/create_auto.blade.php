@extends('layouts.app')

@section('title', 'Koreksi Otomatis PO')

@section('content')
<div class="max-w-7xl mx-auto">
    
    {{-- HEADER HALAMAN --}}
    <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center mb-6 gap-4">
        <div>
            <div class="flex items-center gap-2 text-sm text-gray-500 mb-1">
                <a href="{{ route('purchase-order-adjustments.create') }}" class="hover:text-indigo-600 transition">Penyesuaian</a>
                <span>/</span>
                <span class="text-gray-800">Otomatis</span>
            </div>
            <h2 class="text-2xl font-bold text-gray-900 tracking-tight">Koreksi PO: <span class="text-indigo-600">{{ $purchaseOrder->po_number }}</span></h2>
        </div>
        <a href="{{ route('purchase-order-adjustments.create') }}" class="px-4 py-2 bg-white border border-gray-300 rounded-lg text-gray-700 text-sm font-medium hover:bg-gray-50 transition shadow-sm">
            Kembali
        </a>
    </div>

    {{-- INFO ALERT --}}
    <div class="mb-6 bg-blue-50 border-l-4 border-blue-500 p-4 rounded-r shadow-sm flex items-start gap-3">
        <i class="bi bi-info-circle-fill text-blue-500 text-xl mt-0.5"></i>
        <div>
            <h3 class="text-sm font-bold text-blue-800">Mode Koreksi Otomatis</h3>
            <p class="text-sm text-blue-700 mt-1">
                Silakan ubah data di bawah ini sesuai kondisi riil. Sistem akan otomatis menghitung selisihnya (Nota Debet/Kredit) tanpa mengubah PO asli.
            </p>
        </div>
    </div>

    {{-- ERROR HANDLING --}}
    @if ($errors->any())
        <div class="mb-6 bg-red-50 border-l-4 border-red-500 p-4 rounded-r shadow-sm">
            <div class="flex">
                <div class="flex-shrink-0">
                    <i class="bi bi-exclamation-triangle-fill text-red-400"></i>
                </div>
                <div class="ml-3">
                    <h3 class="text-sm font-medium text-red-800">Terdapat kesalahan input:</h3>
                    <ul class="mt-2 list-disc list-inside text-sm text-red-700 space-y-1">
                        @foreach ($errors->all() as $error) <li>{{ $error }}</li> @endforeach
                    </ul>
                </div>
            </div>
        </div>
    @endif

    <form action="{{ route('purchase-order-adjustments.store.auto', $purchaseOrder->po_id) }}" method="POST" id="po-form">
        @csrf

        <div class="grid grid-cols-1 lg:grid-cols-12 gap-6">
            
            {{-- ===================================================
                 KOLOM KIRI: FORM ITEM (Span 8)
                 =================================================== --}}
            <div class="lg:col-span-8 space-y-6">
                
                {{-- CARD REVISI ITEM --}}
                <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
                    <div class="px-6 py-4 border-b border-gray-100 bg-gray-50 flex justify-between items-center">
                        <h3 class="text-xs font-bold text-gray-500 uppercase tracking-wider flex items-center gap-2">
                            <i class="bi bi-box-seam text-indigo-500"></i> Revisi Item
                        </h3>
                        <button type="button" id="add-product-btn" class="inline-flex items-center px-3 py-1.5 bg-indigo-50 text-indigo-700 text-xs font-bold rounded-md hover:bg-indigo-100 border border-indigo-200 transition">
                            <i class="bi bi-plus-lg mr-1"></i> Tambah Item
                        </button>
                    </div>

                    {{-- Toolbar Bulk Action --}}
                    <div class="px-6 py-3 border-b border-gray-100 bg-white">
                        <div class="flex flex-wrap items-center justify-between gap-3">
                            <div class="flex items-center gap-2 bg-gray-50 px-3 py-1.5 rounded-lg border border-gray-200">
                                <span class="text-xs font-bold text-gray-500 uppercase"><i class="bi bi-check-all mr-1"></i> Bulk:</span>
                                <button type="button" id="select-all-btn" class="text-xs font-medium text-indigo-600 hover:text-indigo-800 hover:underline">All</button>
                                <span class="text-gray-300">|</span>
                                <button type="button" id="deselect-all-btn" class="text-xs font-medium text-gray-500 hover:text-gray-800 hover:underline">None</button>
                            </div>
                            
                            <div class="flex items-center gap-0 shadow-sm rounded-md">
                                <span class="bg-gray-100 border border-gray-300 border-r-0 text-gray-500 text-xs px-3 py-2 rounded-l-md font-medium">Diskon Massal</span>
                                <input id="bulk-discount-input" type="number" step="any" min="0" class="w-20 border-gray-300 focus:ring-indigo-500 focus:border-indigo-500 text-sm py-1.5 h-[34px]" placeholder="%">
                                <button type="button" id="apply-bulk-discount-btn" class="bg-green-600 hover:bg-green-700 text-white text-xs font-bold px-3 py-2 h-[34px] transition border-l border-green-700">Set</button>
                                <button type="button" id="apply-all-discount-btn" class="bg-indigo-600 hover:bg-indigo-700 text-white text-xs font-bold px-3 py-2 rounded-r-md h-[34px] transition border-l border-indigo-700">All</button>
                            </div>
                        </div>
                    </div>

                    {{-- TABLE ITEM --}}
                    {{-- TABLE ITEM (OPTIMIZED FOR FIT) --}}
                    <div class="overflow-x-auto">
                        <table class="w-full text-left border-collapse">
                            <thead class="bg-gray-50 border-b border-gray-200">
                                <tr>
                                    <th class="px-2 py-2 text-center w-8">
                                        <input type="checkbox" class="rounded border-gray-300 text-indigo-600 focus:ring-indigo-500 cursor-pointer h-4 w-4" id="header-row-select">
                                    </th>
                                    {{-- Produk lebih lebar --}}
                                    <th class="px-2 py-2 text-xs font-bold text-gray-500 uppercase w-[30%]">Produk</th>
                                    {{-- Kolom angka dipersempit --}}
                                    <th class="px-2 py-2 text-xs font-bold text-gray-500 uppercase w-[12%] text-center">Qty</th>
                                    <th class="px-2 py-2 text-xs font-bold text-gray-500 uppercase w-[20%] text-right">Harga (@)</th>
                                    <th class="px-2 py-2 text-xs font-bold text-gray-500 uppercase w-[15%] text-center">Diskon</th>
                                    <th class="px-2 py-2 text-xs font-bold text-gray-500 uppercase w-[18%] text-right">Subtotal</th>
                                    <th class="px-2 py-2 text-center w-8"></th>
                                </tr>
                            </thead>
                            <tbody id="product-items" class="divide-y divide-gray-100">
                                {{-- JS Injects Rows Here --}}
                            </tbody>
                        </table>
                    </div>

                    {{-- Alasan Koreksi --}}
                    <div class="p-6 border-t border-gray-100 bg-yellow-50/30">
                        <label for="notes" class="block text-xs font-bold text-gray-700 uppercase mb-2">Alasan Koreksi <span class="text-red-500">*</span></label>
                        <textarea class="w-full rounded-lg border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 text-sm" name="notes" id="notes" rows="2" placeholder="Contoh: Salah input harga dari supplier..." required>{{ old('notes') }}</textarea>
                    </div>
                </div>
            </div>

            {{-- ===================================================
                 KOLOM KANAN: INFO PO & KALKULASI (Span 4)
                 =================================================== --}}
            <div class="lg:col-span-4 space-y-6">
                
                {{-- CARD INFO PO ASLI (READONLY) --}}
                <div class="bg-gray-50 rounded-xl border border-gray-200 p-5">
                    <h3 class="text-xs font-bold text-gray-500 uppercase tracking-wider mb-4 border-b border-gray-200 pb-2">Informasi PO Asli</h3>
                    <div class="space-y-3 text-sm">
                        <div>
                            <span class="block text-xs text-gray-400 mb-1">Supplier</span>
                            <div class="font-semibold text-gray-800">{{ $purchaseOrder->supplier->supplier_name }}</div>
                            <input type="hidden" name="supplier_id" value="{{ $purchaseOrder->supplier_id }}">
                        </div>
                        <div>
                            <span class="block text-xs text-gray-400 mb-1">Tanggal Pesanan</span>
                            <div class="font-medium text-gray-700">{{ optional($purchaseOrder->order_date)->format('d M Y') }}</div>
                            <input type="hidden" name="order_date" value="{{ optional($purchaseOrder->order_date)->format('Y-m-d') }}">
                        </div>
                        <div>
                            <span class="block text-xs text-gray-400 mb-1">Dipesan Oleh</span>
                            <div class="font-medium text-gray-700">{{ $purchaseOrder->requester->full_name ?? '-- Pembelian Umum --' }}</div>
                            <input type="hidden" name="requester_user_id" value="{{ $purchaseOrder->requester_user_id }}">
                            <input type="hidden" name="due_date" value="{{ optional($purchaseOrder->due_date)->format('Y-m-d') }}">
                        </div>
                    </div>
                </div>

                {{-- CARD KALKULASI REVISI --}}
                <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6 sticky top-6 z-30">
                    <h3 class="text-sm font-bold text-gray-900 mb-4 flex items-center gap-2 border-b border-gray-100 pb-3">
                        <i class="bi bi-calculator text-indigo-500"></i> Kalkulasi Revisi
                    </h3>

                    <div class="space-y-4 mb-6">
                        {{-- Diskon --}}
                        <div>
                            <div class="flex items-center justify-between mb-2">
                                <label class="text-xs font-bold text-gray-700 cursor-pointer" for="apply_disc_fee">Diskon / Fee</label>
                                <input type="checkbox" name="apply_disc_fee" id="apply_disc_fee" value="1" class="rounded border-gray-300 text-indigo-600 focus:ring-indigo-500 h-4 w-4 cursor-pointer" {{ old('apply_disc_fee', $purchaseOrder->apply_disc_fee) ? 'checked' : '' }}>
                            </div>
                            <div class="p-3 bg-gray-50 rounded-lg border border-gray-200 {{ old('apply_disc_fee', $purchaseOrder->apply_disc_fee) ? '' : 'hidden' }}" id="disc-fee-container">
                                <div class="grid grid-cols-2 gap-2">
                                    <div>
                                        <label class="block text-[10px] text-gray-500 mb-1">Diskon %</label>
                                        <input type="number" step="any" min="0" class="w-full rounded border-gray-300 text-xs py-1.5 px-2" name="disc_fee_percent" id="disc_fee_percent" placeholder="0" value="{{ old('disc_fee_percent', $purchaseOrder->disc_fee_percent) }}">
                                    </div>
                                    <div>
                                        <label class="block text-[10px] text-gray-500 mb-1">Nominal (Rp)</label>
                                        <input type="text" class="w-full rounded border-gray-300 text-xs py-1.5 px-2 text-end" name="disc_fee_amount" id="disc_fee_amount" placeholder="0" value="{{ old('disc_fee_amount', $purchaseOrder->disc_fee_amount) }}">
                                    </div>
                                </div>
                            </div>
                        </div>

                        {{-- Pembulatan --}}
                        <div>
                            <div class="flex items-center justify-between mb-2">
                                <label class="text-xs font-bold text-gray-700 cursor-pointer" for="apply_rounding_discount">Diskon Pembulatan</label>
                                <input type="checkbox" id="apply_rounding_discount" name="apply_rounding_discount" value="1" class="rounded border-gray-300 text-indigo-600 focus:ring-indigo-500 h-4 w-4 cursor-pointer" {{ old('apply_rounding_discount', $purchaseOrder->apply_rounding_discount) ? 'checked' : '' }}>
                            </div>
                            <div class="relative rounded-md shadow-sm">
                                <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                    <span class="text-gray-500 sm:text-xs">Rp</span>
                                </div>
                                <input type="text" class="pl-8 block w-full rounded border-gray-300 text-xs py-1.5 text-end" name="rounding_discount_amount" id="rounding_discount_amount" placeholder="0" value="{{ old('rounding_discount_amount', $purchaseOrder->rounding_discount_amount) }}">
                            </div>
                        </div>

                        <div class="border-t border-dashed border-gray-200"></div>

                        {{-- Pajak --}}
                        <div>
                            <label class="block text-xs font-bold text-gray-700 mb-1">Pajak (PPN)</label>
                            <select name="tax_id" id="tax_id" class="block w-full rounded border-gray-300 text-xs py-1.5">
                                <option value="">-- Tidak Ada Pajak --</option>
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
                                <i class="bi bi-sliders mr-1"></i> Opsi Pajak Lanjutan
                            </a>
                            <div class="hidden mt-2 p-3 bg-gray-50 rounded border border-gray-200" id="advancedTaxOptions">
                                <div class="flex items-center mb-2">
                                    <input type="checkbox" id="use_custom_dpp_factor" name="use_custom_dpp_factor" value="1" class="rounded border-gray-300 text-indigo-600 h-4 w-4 mr-2" {{ old('use_custom_dpp_factor', $purchaseOrder->use_custom_dpp_factor) ? 'checked' : '' }}>
                                    <label class="text-xs text-gray-700" for="use_custom_dpp_factor">Override Faktor DPP</label>
                                </div>
                                <input type="text" class="block w-full rounded border-gray-300 text-xs py-1.5 px-2" name="custom_dpp_factor" id="custom_dpp_factor" placeholder="Contoh: 11/12" value="{{ old('custom_dpp_factor', $purchaseOrder->custom_dpp_factor) }}">
                            </div>
                        </div>

                        {{-- Ongkir --}}
                        <div>
                            <label class="block text-xs font-bold text-gray-700 mb-1">Ongkos Kirim (Rp)</label>
                            <input type="text" class="block w-full rounded border-gray-300 text-xs py-1.5 px-2 text-end" name="shipping_amount" id="shipping_amount" value="{{ old('shipping_amount', $purchaseOrder->shipping_amount ?? 0) }}">
                        </div>
                    </div>

                    {{-- SUMMARY TOTALS --}}
                    <div class="bg-gray-50 rounded-lg p-4 border border-gray-200">
                        <div class="flex justify-between mb-2 text-sm text-gray-600">
                            <span>Subtotal</span>
                            <span class="font-semibold text-gray-900" id="summary-subtotal">Rp 0</span>
                        </div>
                        <div class="flex justify-between mb-2 text-xs text-red-500">
                            <span>Diskon/Fee</span>
                            <span id="summary-disc">- Rp 0</span>
                        </div>
                        <div class="flex justify-between mb-2 text-xs text-red-500">
                            <span>Pembulatan</span>
                            <span id="summary-rounding">- Rp 0</span>
                        </div>
                        
                        <div class="border-t border-gray-200 my-2"></div>

                        <div class="flex justify-between mb-1 text-xs text-gray-400">
                            <span>Taxable Base</span>
                            <span id="summary-taxable">Rp 0</span>
                        </div>
                        <div class="flex justify-between mb-1 text-xs text-gray-400 italic">
                            <span>DPP</span>
                            <span id="summary-dpp">Rp 0</span>
                        </div>
                        <div class="flex justify-between mb-1 text-sm text-gray-600">
                            <span>PPN (<span id="summary-tax-rate">0</span>%)</span>
                            <span class="font-semibold text-gray-900" id="summary-ppn">Rp 0</span>
                        </div>
                        <div class="flex justify-between mb-2 text-sm text-gray-600">
                            <span>Ongkir</span>
                            <span class="font-semibold text-gray-900" id="summary-shipping">Rp 0</span>
                        </div>
                        
                        <div class="border-t-2 border-dashed border-gray-300 my-3"></div>

                        <div class="flex justify-between items-center">
                            <span class="text-sm font-bold text-gray-900">TOTAL REVISI</span>
                            <span class="text-xl font-bold text-indigo-600" id="summary-grand">Rp 0</span>
                        </div>
                    </div>

                    {{-- OPSI KELEBIHAN BAYAR (JIKA NILAI TURUN) --}}
                    <div class="mt-4 pt-3 border-t border-dashed border-gray-200">
                        <label class="block text-xs font-bold text-gray-700 uppercase mb-2">Jika Ada Kelebihan Bayar:</label>
                        <div class="space-y-2">
                            <div class="flex items-center">
                                <input class="rounded border-gray-300 text-indigo-600 focus:ring-indigo-500 h-4 w-4 mr-2" type="radio" name="overpayment_action" id="overpayment_deposit" value="deposit" checked>
                                <label class="text-xs text-gray-600 cursor-pointer" for="overpayment_deposit">Simpan ke Deposit</label>
                            </div>
                            <div class="flex items-center">
                                <input class="rounded border-gray-300 text-indigo-600 focus:ring-indigo-500 h-4 w-4 mr-2" type="radio" name="overpayment_action" id="overpayment_refund" value="refund">
                                <label class="text-xs text-gray-600 cursor-pointer" for="overpayment_refund">Refund Manual (Saldo Minus)</label>
                            </div>
                        </div>
                    </div>

                    {{-- ACTIONS --}}
                    <div class="flex flex-col gap-3 mt-6">
                        <button type="submit" class="w-full flex justify-center py-3 px-4 border border-transparent rounded-lg shadow-sm text-sm font-bold text-white bg-green-600 hover:bg-green-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-green-500 transition" id="submit-btn">
                            <i class="bi bi-check-circle mr-2"></i> Simpan Koreksi
                        </button>
                        <a href="{{ route('purchase-order-adjustments.create') }}?purchase_order_id={{ $purchaseOrder->po_id }}" class="w-full flex justify-center py-3 px-4 border border-gray-300 rounded-lg shadow-sm text-sm font-medium text-gray-700 bg-white hover:bg-gray-50 transition">Batal</a>
                    </div>
                </div>
            </div>
        </div>
    </form>
</div>

{{-- TEMPLATE ROW PRODUK --}}
{{-- TEMPLATE ROW (COMPACT VERSION) --}}
<template id="product-row-template">
    <tr class="hover:bg-gray-50 transition-colors border-b border-gray-50 last:border-0">
        {{-- 1. Checkbox --}}
        <td class="text-center align-top py-3 px-2">
            <input type="checkbox" class="row-select rounded border-gray-300 text-indigo-600 focus:ring-indigo-500 cursor-pointer h-4 w-4 mt-2">
        </td>

        {{-- 2. Produk (Stack Select2) --}}
        <td class="align-top py-2 px-2">
            <select class="product-select table-input w-full text-sm" required>
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
            <div class="flex items-center mt-1">
                <input class="update-master-price rounded border-gray-300 text-indigo-600 focus:ring-indigo-500 h-3 w-3 mr-1" type="checkbox" value="1">
                <label class="text-[10px] text-gray-400 italic cursor-pointer">Update Master</label>
            </div>
        </td>

        {{-- 3. Qty (Compact Input) --}}
        <td class="align-top py-2 px-2">
            <div class="flex items-center rounded-md bg-white border border-gray-300 focus-within:border-indigo-500 focus-within:ring-1 focus-within:ring-indigo-500 h-8 overflow-hidden">
                <input type="number" class="table-input quantity text-center font-bold bg-transparent border-0 focus:ring-0 p-0 h-full w-full text-xs" value="1" min="1" step="any" required>
                <span class="bg-gray-100 text-gray-500 text-[9px] px-1 h-full flex items-center border-l border-gray-200 unit-display font-medium">-</span>
            </div>
        </td>

        {{-- 4. Harga (Compact Input) --}}
        <td class="align-top py-2 px-2">
            <div class="flex items-center border rounded-md border-gray-300 bg-white focus-within:border-indigo-500 h-8 px-1">
                <span class="text-gray-400 text-[10px] mr-1">Rp</span>
                <input type="text" class="table-input purchase-price-formatted text-right p-0 border-none focus:ring-0 w-full text-xs font-medium" placeholder="0">
            </div>
        </td>

        {{-- 5. Diskon (Stack Vertical) --}}
        <td class="align-top py-2 px-2">
            <div class="discount-container space-y-1"></div>
            <button type="button" class="w-full mt-1 text-[10px] text-indigo-600 hover:text-indigo-800 font-bold add-discount-btn flex justify-center items-center uppercase tracking-wide bg-indigo-50 px-1 py-0.5 rounded hover:bg-indigo-100 transition border border-indigo-100">
                <i class="bi bi-plus mr-1"></i> Disc
            </button>
        </td>

        {{-- 6. Subtotal --}}
        <td class="text-right py-3 px-2 align-top font-bold text-gray-900 text-xs">
            <span class="subtotal">Rp 0</span>
        </td>

        {{-- 7. Delete --}}
        <td class="text-center py-2 px-2 align-top">
            <button type="button" class="text-gray-400 hover:text-red-500 hover:bg-red-50 rounded p-1 transition remove-product-btn" title="Hapus">
                <i class="bi bi-trash"></i>
            </button>
        </td>
    </tr>
</template>
@endsection

@push('scripts')
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
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
    
    const elSummarySubtotal = document.getElementById('summary-subtotal');
    const elSummaryDisc = document.getElementById('summary-disc');
    const elSummaryRounding = document.getElementById('summary-rounding');
    const elSummaryTaxable = document.getElementById('summary-taxable');
    const elSummaryDpp = document.getElementById('summary-dpp');
    const elSummaryPpn = document.getElementById('summary-ppn');
    const elSummaryGrand = document.getElementById('summary-grand');
    const elSummaryShipping = document.getElementById('summary-shipping');
    const elSummaryTaxRate = document.getElementById('summary-tax-rate');
    
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

    // Initialize AutoNumeric for currency fields
    new AutoNumeric(inputDiscFeeAmount, anOptions);
    new AutoNumeric(inputRoundingAmount, anOptions);
    new AutoNumeric(inputShipping, anOptions);

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
            <input type="number" step="any" class="w-16 rounded border-gray-300 text-xs py-1 text-center" placeholder="%" value="${value}" name="products[${index}][discounts][]">
            <button type="button" class="text-red-500 hover:text-red-700 text-xs px-1">
                <i class="bi bi-x"></i>
            </button>
        `;
        
        div.querySelector('button').onclick = () => { 
            div.remove(); 
            calculateRowSubtotal(row); 
            calculateTotals(); 
        };
        
        div.querySelector('input').oninput = () => { 
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
        
        productSelect.name = `products[${rowIndex}][product_id]`;
        quantityInput.name = `products[${rowIndex}][quantity]`;
        updateMasterCheckbox.name = `products[${rowIndex}][update_master_price]`;
        
        productItemsContainer.appendChild(newRow);
        
        const anInstance = new AutoNumeric(formattedPriceInput, anOptions);
        autoNumericInstances.set(rowIndex, anInstance);

        const select2 = $(productSelect).select2({ 
            placeholder: '-- Pilih Produk --', 
            theme: 'bootstrap-5', 
            width: '100%',
            dropdownParent: $(productSelect).parent() 
        });
        
        select2.on('select2:select', function(e) {
            const el = e.params.data.element;
            newRow.querySelector('.unit-display').textContent = el.dataset.unit || '-';
            const defaultPrice = el.dataset.defaultPrice || 0;
            priceHiddenInput.value = defaultPrice;
            anInstance.set(defaultPrice);
            
            newRow.querySelector('.discount-container').innerHTML = '';
            try { 
                JSON.parse(el.dataset.defaultDiscounts || '[]').forEach(d => 
                    createDiscountInputForRow(newRow, d)
                ); 
            } catch (err) {}
            
            calculateRowSubtotal(newRow);
            if (shouldCalculate) calculateTotals();
        });

        addDiscountBtn.onclick = () => createDiscountInputForRow(newRow, '');
        
        removeBtn.onclick = () => {
            $(productSelect).select2('destroy');
            autoNumericInstances.delete(rowIndex);
            newRow.remove();
            if (shouldCalculate) calculateTotals();
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

    function getSelectedTaxRate() {
        const opt = inputTaxId.selectedOptions[0];
        return (opt && opt.value) ? parseFloat(opt.dataset.rate) : null;
    }

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
        
        let dpp = 0, ppn = 0;
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
        
        elSummarySubtotal.textContent = formatCurrency(subtotalBarang);
        elSummaryDisc.textContent = formatCurrency(discFeeAmount);
        elSummaryRounding.textContent = formatCurrency(roundingAmount);
        elSummaryTaxable.textContent = formatCurrency(taxableBase);
        elSummaryDpp.textContent = formatCurrency(dpp);
        elSummaryPpn.textContent = formatCurrency(ppn);
        elSummaryShipping.textContent = formatCurrency(shipping);
        elSummaryGrand.textContent = formatCurrency(grandTotal);
        elSummaryTaxRate.textContent = taxRate;
    }

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
            if (isNaN(v)) return alert('Masukkan angka diskon valid');
            rows.forEach(r => createDiscountInputForRow(r, v));
            rows.forEach(r => calculateRowSubtotal(r));
            calculateTotals();
        };

        applyBulkBtn.onclick = () => {
            const rows = getAllRows().filter(r => r.querySelector('.row-select').checked);
            if (rows.length === 0) return alert('Pilih baris terlebih dahulu atau gunakan Apply to All.');
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
                alert('Harap tambahkan setidaknya satu item produk.'); 
                return; 
            }
            inputCustomDppFactor.value = parseFractionOrNumber(inputCustomDppFactor.value);
        });
    }

    setupEventListeners();
    populateExistingItems();
});
</script>
@endpush
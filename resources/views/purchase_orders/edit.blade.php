@extends('layouts.app')

@section('title', 'Edit Pesanan Pembelian')

@section('content')
<div class="max-w-full mx-auto py-4 px-4 sm:px-6 lg:px-8">
    
    {{-- Indikator Loading / Save --}}
    <div id="autosave-indicator" class="fixed top-5 right-5 z-50 hidden transition-opacity duration-300">
        <div class="flex items-center bg-blue-600 text-white px-4 py-3 rounded-lg shadow-lg border border-blue-500">
            <i class="bi bi-arrow-repeat animate-spin mr-2 text-lg"></i>
            <span class="text-sm font-bold">Memproses data...</span>
        </div>
    </div>

    {{-- HEADER HALAMAN --}}
    <div class="flex flex-col md:flex-row justify-between items-start md:items-center mb-6 gap-4">
        <div>
            <div class="flex items-center gap-2 text-sm text-gray-500 mb-1">
                <a href="{{ route('purchase-orders.index') }}" class="hover:text-indigo-600 transition">Pesanan</a>
                <span>/</span>
                <span class="text-gray-800">Edit</span>
            </div>
            <h3 class="text-2xl font-bold text-gray-900 tracking-tight">Edit Pesanan: <span class="text-indigo-600">{{ $purchaseOrder->po_number }}</span></h3>
        </div>
        <div>
            <a href="{{ route('purchase-orders.index') }}" class="inline-flex items-center px-4 py-2 bg-white border border-gray-300 rounded-lg text-gray-700 hover:bg-gray-50 hover:text-indigo-600 transition shadow-sm text-sm font-medium">
                <i class="bi bi-arrow-left mr-2"></i> Kembali
            </a>
        </div>
    </div>

    {{-- ALERT ERROR --}}
    @if ($errors->any())
        <div class="mb-6 bg-red-50 border-l-4 border-red-500 p-4 rounded-r shadow-sm flex items-start gap-3">
            <i class="bi bi-exclamation-triangle-fill text-red-500 text-xl"></i>
            <div>
                <h3 class="text-sm font-bold text-red-800">Gagal memperbarui pesanan:</h3>
                <ul class="mt-1 list-disc list-inside text-sm text-red-700">
                    @foreach ($errors->all() as $error) <li>{{ $error }}</li> @endforeach
                </ul>
            </div>
        </div>
    @endif

    {{-- FORM UTAMA --}}
    <form action="{{ route('purchase-orders.update', $purchaseOrder->po_id) }}" method="POST" id="po-form">
        @csrf
        @method('PUT')

        <div class="grid grid-cols-1 xl:grid-cols-12 gap-6">
            
            {{-- ===================================================
                 KOLOM KIRI: INFO & ITEMS (Span 9)
                 =================================================== --}}
            <div class="xl:col-span-9 space-y-6">
                
                {{-- 1. CARD INFORMASI PESANAN --}}
                <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
                    <div class="px-6 py-4 border-b border-gray-100 bg-gray-50">
                        <h3 class="text-xs font-bold text-gray-500 uppercase tracking-wider flex items-center gap-2">
                            <i class="bi bi-pencil-square text-indigo-500"></i> Informasi Pesanan
                        </h3>
                    </div>
                    <div class="p-6">
                        <div class="grid grid-cols-1 md:grid-cols-12 gap-6">
                            {{-- Supplier --}}
                            <div class="md:col-span-6">
                                <label for="supplier_id" class="block text-xs font-bold text-gray-700 uppercase mb-1">Supplier <span class="text-red-500">*</span></label>
                                <select name="supplier_id" id="supplier_id" class="w-full" required>
                                    @foreach ($suppliers as $supplier)
                                        <option value="{{ $supplier->supplier_id }}" @selected(old('supplier_id', $purchaseOrder->supplier_id) == $supplier->supplier_id)>
                                            {{ $supplier->supplier_name }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                            
                            {{-- Tgl Pesanan --}}
                            <div class="md:col-span-3">
                                <label for="order_date" class="block text-xs font-bold text-gray-700 uppercase mb-1">Tanggal Pesanan <span class="text-red-500">*</span></label>
                                <input type="date" class="w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 text-sm" id="order_date" name="order_date" value="{{ old('order_date', optional($purchaseOrder->order_date)->format('Y-m-d')) }}" required>
                            </div>
                            
                            {{-- Jatuh Tempo --}}
                            <div class="md:col-span-3">
                                <label for="due_date" class="block text-xs font-bold text-gray-700 uppercase mb-1">Jatuh Tempo</label>
                                <input type="date" class="w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 text-sm" id="due_date" name="due_date" value="{{ old('due_date', optional($purchaseOrder->due_date)->format('Y-m-d')) }}">
                            </div>

                            {{-- User Requester --}}
                            <div class="md:col-span-12">
                                <label for="requester_user_id" class="block text-xs font-bold text-gray-700 uppercase mb-1">Dipesan Oleh</label>
                                <select name="requester_user_id" id="requester_user_id" class="w-full">
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
                </div>

                {{-- 2. CARD RINCIAN BARANG --}}
                <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
                    <div class="px-6 py-4 border-b border-gray-100 bg-gray-50">
                        <h3 class="text-xs font-bold text-gray-500 uppercase tracking-wider flex items-center gap-2">
                            <i class="bi bi-box-seam text-indigo-500"></i> Edit Item
                        </h3>
                    </div>

                    {{-- Toolbar --}}
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
                                <button type="button" id="apply-bulk-discount-btn" class="bg-green-600 hover:bg-green-700 text-white text-xs font-bold px-3 py-2 h-[34px] transition border-l border-green-700">Selected</button>
                                <button type="button" id="apply-all-discount-btn" class="bg-indigo-600 hover:bg-indigo-700 text-white text-xs font-bold px-3 py-2 rounded-r-md h-[34px] transition border-l border-indigo-700">All</button>
                            </div>
                        </div>
                    </div>

                    {{-- TABLE ITEM --}}
                    <div class="overflow-x-auto">
                        <table class="w-full text-left border-collapse">
                            <thead class="bg-gray-50 border-b border-gray-200">
                                <tr>
                                    <th class="px-4 py-3 text-center w-10">
                                        <input type="checkbox" class="rounded border-gray-300 text-indigo-600 focus:ring-indigo-500 cursor-pointer" id="header-row-select">
                                    </th>
                                    <th class="px-4 py-3 text-xs font-bold text-gray-500 uppercase w-[35%]">Produk</th>
                                    <th class="px-4 py-3 text-xs font-bold text-gray-500 uppercase w-[15%]">Qty</th>
                                    <th class="px-4 py-3 text-xs font-bold text-gray-500 uppercase w-[20%]">Harga Beli (@)</th>
                                    <th class="px-4 py-3 text-xs font-bold text-gray-500 uppercase w-[10%]">Diskon</th>
                                    <th class="px-4 py-3 text-xs font-bold text-gray-500 uppercase text-right w-[15%]">Subtotal</th>
                                    <th class="px-4 py-3 text-center w-10"><i class="bi bi-gear"></i></th>
                                </tr>
                            </thead>
                            <tbody id="product-items" class="divide-y divide-gray-100">
                                {{-- JS will populate this --}}
                            </tbody>
                        </table>
                    </div>
                    
                    <div class="p-4 bg-gray-50 border-t border-gray-200 text-center">
                         <button type="button" id="add-product-btn" class="inline-flex items-center px-6 py-2 bg-white border border-indigo-200 text-indigo-700 text-sm font-bold rounded-full hover:bg-indigo-50 hover:border-indigo-300 transition shadow-sm">
                            <i class="bi bi-plus-lg mr-1"></i> Tambah Item
                        </button>
                    </div>

                    {{-- Notes --}}
                    <div class="p-6 border-t border-gray-100">
                        <label for="notes" class="block text-xs font-bold text-gray-500 uppercase mb-2">Catatan / Keterangan</label>
                        <textarea class="w-full rounded-lg border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 text-sm bg-yellow-50/30" name="notes" id="notes" rows="2">{{ old('notes', $purchaseOrder->notes) }}</textarea>
                    </div>
                </div>
            </div>

            {{-- ===================================================
                 KOLOM KANAN: SUMMARY (Span 3)
                 =================================================== --}}
            <div class="xl:col-span-3">
                <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6 sticky top-6 z-30">
                    <h3 class="text-sm font-bold text-gray-900 mb-4 flex items-center gap-2 border-b border-gray-100 pb-3">
                        <i class="bi bi-calculator text-indigo-500"></i> Ringkasan Biaya
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
                                        {{-- ✅ FIXED: Tipe TEXT --}}
                                        <input type="text" class="w-full rounded border-gray-300 text-xs py-1.5 px-2 focus:border-indigo-500 focus:ring-indigo-500 text-end font-medium" name="disc_fee_amount" id="disc_fee_amount" placeholder="0" value="{{ old('disc_fee_amount', $purchaseOrder->disc_fee_amount) }}">
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
                                {{-- ✅ FIXED: Tipe TEXT --}}
                                <input type="text" class="pl-8 block w-full rounded border-gray-300 text-xs py-1.5 focus:border-indigo-500 focus:ring-indigo-500 text-end font-medium" name="rounding_discount_amount" id="rounding_discount_amount" placeholder="0" value="{{ old('rounding_discount_amount', $purchaseOrder->rounding_discount_amount) }}">
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
                            {{-- ✅ FIXED: Tipe TEXT --}}
                            <input type="text" class="block w-full rounded border-gray-300 text-xs py-1.5 px-2 focus:border-indigo-500 focus:ring-indigo-500 text-end font-medium" name="shipping_amount" id="shipping_amount" value="{{ old('shipping_amount', $purchaseOrder->shipping_amount) }}">
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
                            <span class="text-sm font-bold text-gray-900">GRAND TOTAL</span>
                            <span class="text-xl font-bold text-indigo-600" id="summary-grand">Rp 0</span>
                        </div>
                    </div>

                    <div class="flex flex-col gap-3 mt-6">
                        <button type="submit" class="w-full flex justify-center py-3 px-4 border border-transparent rounded-lg shadow-sm text-sm font-bold text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 transition" id="submit-btn">
                            <i class="bi bi-check-circle mr-2"></i> Update Pesanan
                        </button>
                        <a href="{{ route('purchase-orders.index') }}" class="w-full flex justify-center py-3 px-4 border border-gray-300 rounded-lg shadow-sm text-sm font-medium text-gray-700 bg-white hover:bg-gray-50 transition">Batal</a>
                    </div>
                </div>
            </div>
        </div>
    </form>
</div>

{{-- TEMPLATE ROW PRODUK (HIDDEN) --}}
<template id="product-row-template">
    <tr class="hover:bg-gray-50 transition-colors border-b border-gray-50 last:border-0">
        <td class="text-center align-middle pl-4 py-2">
            <input type="checkbox" class="row-select rounded border-gray-300 text-indigo-600 focus:ring-indigo-500 cursor-pointer h-4 w-4">
        </td>
        <td class="align-middle p-2">
            <select class="product-select table-input w-full" required>
                <option value="" data-unit="-" data-default-discounts="[]" disabled selected>-- Cari Produk --</option>
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
        <td class="align-middle p-2">
            <div class="flex items-center rounded-md bg-white border border-gray-300 hover:border-indigo-400 focus-within:border-indigo-500 focus-within:ring-1 focus-within:ring-indigo-500 h-8 overflow-hidden">
                <input type="number" class="table-input quantity text-center font-bold bg-transparent border-0 focus:ring-0 p-0 h-full w-full text-sm" value="1" min="1" step="any" required>
                <span class="bg-gray-100 text-gray-500 text-[10px] px-2 h-full flex items-center border-l border-gray-200 unit-display font-medium">-</span>
            </div>
        </td>
        <td class="align-middle p-2">
            <div class="flex items-center border-b border-gray-300 focus-within:border-indigo-500 mb-1 pb-0.5">
                <span class="text-gray-400 text-[10px] mr-1">Rp</span>
                <input type="text" class="table-input purchase-price-formatted text-right p-0 border-none focus:ring-0 w-full text-sm font-medium" placeholder="0">
            </div>
            <div class="flex items-center justify-end gap-1">
                <input class="update-master-price rounded border-gray-300 text-indigo-600 focus:ring-indigo-500 h-3 w-3" type="checkbox" value="1">
                <label class="text-[10px] text-gray-400 italic">Update Master</label>
            </div>
        </td>
        <td class="align-middle p-2">
            <div class="discount-container mb-1 space-y-1"></div>
            <button type="button" class="text-[10px] text-indigo-600 hover:text-indigo-800 font-bold add-discount-btn flex items-center uppercase tracking-wide bg-indigo-50 px-2 py-1 rounded hover:bg-indigo-100 transition">
                <i class="bi bi-plus-circle mr-1"></i> Disc
            </button>
        </td>
        <td class="text-right pr-6 align-middle font-bold text-gray-900 text-sm">
            <span class="subtotal">Rp 0</span>
        </td>
        <td class="text-center align-middle">
            <button type="button" class="text-gray-400 hover:text-red-500 hover:bg-red-50 rounded-full p-2 transition remove-product-btn" title="Hapus Item">
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
        $('#supplier_id').select2({ theme: 'bootstrap-5', width: '100%' });
        $('#requester_user_id').select2({ theme: 'bootstrap-5', width: '100%' });

        // === AUTONUMERIC FIX (INIT) ===
        const autoNumericOptions = { decimalCharacter: ',', digitGroupSeparator: '.', decimalPlaces: 0, minimumValue: '0' };
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

        // Helper Functions
        function formatCurrency(n) { return new Intl.NumberFormat('id-ID', { style: 'currency', currency: 'IDR', minimumFractionDigits: 0 }).format(Math.round(n || 0)); }
        function formatThousands(n) { if (!n) return ''; return new Intl.NumberFormat('id-ID', { maximumFractionDigits: 0 }).format(Math.floor(Number(n))); }
        function parseNumericForInput(str) { if (!str) return 0; return parseFloat(String(str).replace(/[^\d\-\.\,]/g, '').replace(/,/g, '.')) || 0; }
        function getAllRows() { return Array.from(productItemsContainer.querySelectorAll('tr')); }
        
        // ✅ FIXED: Helper getRawNumericValue
        function getRawNumericValue(elementId) {
            const el = document.getElementById(elementId);
            if (AutoNumeric.getAutoNumericElement(el)) return parseFloat(AutoNumeric.getAutoNumericElement(el).getNumericString()) || 0;
            return parseNumericForInput(el.value);
        }

        function getSelectedTaxRate() {
            const opt = inputTaxId.selectedOptions[0];
            return (opt && opt.value) ? parseFloat(opt.dataset.rate) : null;
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

            // Fee Header (Raw Value)
            let discFeeAmount = 0;
            if (discFeeCheckbox.checked) {
                const percent = parseFloat(document.getElementById('disc_fee_percent').value) || 0;
                const fixed = getRawNumericValue('disc_fee_amount'); // Fixed
                if (percent > 0) discFeeAmount = (percent / 100.0) * subtotalBarang;
                else if (fixed > 0) discFeeAmount = fixed;
            }

            // Pembulatan (Raw Value)
            const roundingAmount = document.getElementById('apply_rounding_discount').checked ? getRawNumericValue('rounding_discount_amount') : 0; // Fixed
            
            const taxableBase = Math.max(0, subtotalBarang - discFeeAmount - roundingAmount);

            let dpp = taxableBase;
            let ppn = 0;
            let taxRate = getSelectedTaxRate();

            if (taxRate !== null && taxRate > 0) {
                let dppFactor = 100 / (100 + taxRate);
                if (document.getElementById('use_custom_dpp_factor').checked) {
                    const customVal = document.getElementById('custom_dpp_factor').value;
                    if (customVal.includes('/')) {
                        const parts = customVal.split('/');
                        dppFactor = parseFloat(parts[0]) / parseFloat(parts[1]);
                    } else if (parseFloat(customVal) > 0) {
                        dppFactor = parseFloat(customVal);
                    }
                }
                dpp = Math.round(taxableBase * dppFactor);
                ppn = Math.round(dpp * (taxRate / 100.0));
            }

            // Shipping (Raw Value)
            const shipping = getRawNumericValue('shipping_amount'); // Fixed
            const grandTotal = Math.round(taxableBase + ppn + shipping);

            document.getElementById('summary-subtotal').textContent = formatCurrency(subtotalBarang);
            document.getElementById('summary-disc').textContent = formatCurrency(discFeeAmount);
            document.getElementById('summary-rounding').textContent = formatCurrency(roundingAmount);
            document.getElementById('summary-taxable').textContent = formatCurrency(taxableBase);
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
            div.className = 'flex items-center gap-1 mb-1';
            div.innerHTML = `
                <input type="number" step="any" class="discount-percentage table-input w-16 text-xs text-center border rounded bg-white shadow-sm" placeholder="%" value="${value}" name="products[${index}][discounts][]">
                <button type="button" class="remove-discount-btn text-red-500 hover:text-red-700 p-1 rounded-full hover:bg-red-50"><i class="bi bi-x"></i></button>
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
            selectElem.select2({ placeholder: '-- Cari Produk --', theme: 'bootstrap-5', width: '100%' });
            selectElem.on('select2:select', function(e) {
                const el = e.params.data.element;
                newRow.querySelector('.unit-display').textContent = el.dataset.unit || '-';
                const defPrice = el.dataset.defaultPrice || 0;
                priceHidden.value = defPrice;
                newRow.querySelector('.purchase-price-formatted').value = formatThousands(defPrice);
                calculateRowSubtotal(newRow);
                calculateTotals();
            });

            const qtyInput = newRow.querySelector('.quantity');
            const priceInput = newRow.querySelector('.purchase-price-formatted');
            qtyInput.addEventListener('input', () => { calculateRowSubtotal(newRow); calculateTotals(); });
            priceInput.addEventListener('input', () => {
                priceHidden.value = parseNumericForInput(priceInput.value);
                calculateRowSubtotal(newRow);
                calculateTotals();
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

        // === POPULATE EXISTING ITEMS ===
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
            addProductRow();
        }

        // Global Listeners & Init
        document.getElementById('add-product-btn').onclick = () => addProductRow();
        
        const calcInputs = ['disc_fee_percent', 'custom_dpp_factor', 'tax_id', 'apply_rounding_discount'];
        calcInputs.forEach(id => {
            document.getElementById(id).addEventListener('input', calculateTotals);
            document.getElementById(id).addEventListener('change', calculateTotals);
        });
        
        inputDiscFeeAmount.addEventListener('autoNumeric:rawValueModified', calculateTotals);
        inputRoundingAmount.addEventListener('autoNumeric:rawValueModified', calculateTotals);
        inputShipping.addEventListener('autoNumeric:rawValueModified', calculateTotals);

        if({{ $purchaseOrder->use_custom_dpp_factor ? 'true' : 'false' }}) document.getElementById('advancedTaxOptions').classList.remove('hidden');

        form.addEventListener('submit', (e) => {
            if (getAllRows().length === 0) { e.preventDefault(); alert('Harap tambahkan minimal satu item.'); }
        });
    });
</script>
@endpush
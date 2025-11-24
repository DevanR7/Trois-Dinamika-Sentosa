@extends('layouts.app')

@section('title', 'Buat PO Baru')

@section('content')
<div class="max-w-full mx-auto py-4 px-4 sm:px-6 lg:px-8">
    
    {{-- Indikator Auto Save --}}
    <div id="autosave-indicator" class="fixed top-5 right-5 z-50 hidden transition-opacity duration-300">
        <div class="flex items-center bg-green-600 text-white px-4 py-3 rounded-lg shadow-lg border border-green-500">
            <i class="bi bi-cloud-check mr-2 text-lg"></i>
            <span class="text-sm font-bold">Draft disimpan otomatis</span>
        </div>
    </div>

    {{-- HEADER HALAMAN --}}
    <div class="flex flex-col md:flex-row justify-between items-start md:items-center mb-6 gap-4">
        <div>
            <h3 class="text-2xl font-bold text-gray-900 tracking-tight">Buat Purchase Order Baru</h3>
            <p class="text-gray-500 text-sm mt-1">Isi formulir di bawah untuk membuat pesanan pembelian ke supplier.</p>
        </div>
        <div>
            <a href="{{ route('purchase-orders.index') }}" class="inline-flex items-center px-4 py-2 bg-white border border-gray-300 rounded-lg text-gray-700 hover:bg-gray-50 hover:text-indigo-600 transition shadow-sm text-sm font-medium">
                <i class="bi bi-arrow-left mr-2"></i> Kembali ke List
            </a>
        </div>
    </div>

    {{-- ALERT ERROR --}}
    @if ($errors->any())
        <div class="mb-6 bg-red-50 border-l-4 border-red-500 p-4 rounded-r shadow-sm flex items-start gap-3">
            <i class="bi bi-exclamation-triangle-fill text-red-500 text-xl"></i>
            <div>
                <h3 class="text-sm font-bold text-red-800">Terdapat kesalahan input:</h3>
                <ul class="mt-1 list-disc list-inside text-sm text-red-700">
                    @foreach ($errors->all() as $error) <li>{{ $error }}</li> @endforeach
                </ul>
            </div>
        </div>
    @endif

    {{-- FORM UTAMA --}}
    <form action="{{ route('purchase-orders.store') }}" method="POST" id="po-form">
        @csrf

        <div class="grid grid-cols-1 xl:grid-cols-12 gap-6">
            
            {{-- ===================================================
                 KOLOM KIRI: INFO & ITEMS (Span 9)
                 =================================================== --}}
            <div class="xl:col-span-9 space-y-6">
                
                {{-- 1. CARD INFORMASI PESANAN --}}
                <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
                    <div class="px-6 py-4 border-b border-gray-100 bg-gray-50">
                        <h3 class="text-xs font-bold text-gray-500 uppercase tracking-wider flex items-center gap-2">
                            <i class="bi bi-info-circle text-indigo-500"></i> Informasi Pesanan
                        </h3>
                    </div>
                    <div class="p-6">
                        <div class="grid grid-cols-1 md:grid-cols-12 gap-6">
                            {{-- Supplier --}}
                            <div class="md:col-span-6">
                                <label for="supplier_id" class="block text-xs font-bold text-gray-700 uppercase mb-1">Pilih Supplier <span class="text-red-500">*</span></label>
                                <select name="supplier_id" id="supplier_id" class="w-full" required>
                                    <option value="" disabled selected>-- Pilih Supplier --</option>
                                    @foreach ($suppliers as $supplier)
                                        <option value="{{ $supplier->supplier_id }}">{{ $supplier->supplier_name }}</option>
                                    @endforeach
                                </select>
                            </div>
                            
                            {{-- Tgl Pesanan --}}
                            <div class="md:col-span-3">
                                <label for="order_date" class="block text-xs font-bold text-gray-700 uppercase mb-1">Tanggal Pesanan <span class="text-red-500">*</span></label>
                                <input type="date" class="w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 text-sm" id="order_date" name="order_date" value="{{ now()->format('Y-m-d') }}" required>
                            </div>
                            
                            {{-- Jatuh Tempo --}}
                            <div class="md:col-span-3">
                                <label for="due_date" class="block text-xs font-bold text-gray-700 uppercase mb-1">Jatuh Tempo <span class="font-normal text-gray-400 normal-case">(Opsional)</span></label>
                                <input type="date" class="w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 text-sm" id="due_date" name="due_date" value="{{ old('due_date') }}">
                            </div>

                            {{-- User Requester --}}
                            <div class="md:col-span-12">
                                <label for="requester_user_id" class="block text-xs font-bold text-gray-700 uppercase mb-1">Dipesan Oleh (User)</label>
                                <select name="requester_user_id" id="requester_user_id" class="w-full">
                                    <option value="">-- Pembelian Umum / Stok --</option>
                                    @foreach ($users as $user)
                                        <option value="{{ $user->user_id }}">{{ $user->full_name }}</option>
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
                            <i class="bi bi-box-seam text-indigo-500"></i> Rincian Item
                        </h3>
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
                                {{-- JS Injects Rows Here --}}
                            </tbody>
                        </table>
                    </div>
                    
                    {{-- Add Item Button --}}
                    <div class="p-4 bg-gray-50 border-t border-gray-200 text-center">
                         <button type="button" id="add-product-btn" class="inline-flex items-center px-6 py-2 bg-white border border-indigo-200 text-indigo-700 text-sm font-bold rounded-full hover:bg-indigo-50 hover:border-indigo-300 transition shadow-sm">
                            <i class="bi bi-plus-lg mr-1"></i> Tambah Item Baru
                        </button>
                    </div>

                    {{-- Notes --}}
                    <div class="p-6 border-t border-gray-100">
                        <label for="notes" class="block text-xs font-bold text-gray-500 uppercase mb-2">Catatan / Keterangan</label>
                        <textarea class="w-full rounded-lg border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 text-sm bg-yellow-50/30" name="notes" id="notes" rows="2" placeholder="Contoh: Pengiriman tolong dilakukan lewat pintu gudang belakang...">{{ old('notes') }}</textarea>
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

                    {{-- OPSI KALKULASI --}}
                    <div class="space-y-4 mb-6">
                        
                        {{-- 1. Diskon Tambahan --}}
                        <div>
                            <div class="flex items-center justify-between mb-2">
                                <label class="text-xs font-bold text-gray-700 cursor-pointer select-none" for="apply_disc_fee">Diskon / Fee Tambahan</label>
                                <input type="checkbox" name="apply_disc_fee" id="apply_disc_fee" value="1" class="rounded border-gray-300 text-indigo-600 focus:ring-indigo-500 h-4 w-4 cursor-pointer">
                            </div>
                            {{-- Container Hidden by default using style="display:none" for JS toggle --}}
                            <div class="p-3 bg-gray-50 rounded-lg border border-gray-200" id="disc-fee-container" style="display: none;">
                                <div class="grid grid-cols-2 gap-2">
                                    <div>
                                        <label class="block text-[10px] text-gray-500 mb-1">Diskon %</label>
                                        <input type="number" step="any" min="0" class="w-full rounded border-gray-300 text-xs py-1.5 px-2 focus:border-indigo-500 focus:ring-indigo-500" name="disc_fee_percent" id="disc_fee_percent" placeholder="0">
                                    </div>
                                    <div>
                                        <label class="block text-[10px] text-gray-500 mb-1">Nominal (Rp)</label>
                                        <input type="text" class="w-full rounded border-gray-300 text-xs py-1.5 px-2 focus:border-indigo-500 focus:ring-indigo-500" name="disc_fee_amount" id="disc_fee_amount" placeholder="0">
                                    </div>
                                </div>
                            </div>
                        </div>

                        {{-- 2. Pembulatan --}}
                        <div>
                            <div class="flex items-center justify-between mb-2">
                                <label class="text-xs font-bold text-gray-700 cursor-pointer select-none" for="apply_rounding_discount">Diskon Pembulatan</label>
                                <input type="checkbox" id="apply_rounding_discount" name="apply_rounding_discount" value="1" class="rounded border-gray-300 text-indigo-600 focus:ring-indigo-500 h-4 w-4 cursor-pointer">
                            </div>
                            <div class="relative rounded-md shadow-sm">
                                <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                    <span class="text-gray-500 sm:text-xs">Rp</span>
                                </div>
                                <input type="number" step="any" min="0" class="pl-8 block w-full rounded border-gray-300 text-xs py-1.5 focus:border-indigo-500 focus:ring-indigo-500" name="rounding_discount_amount" id="rounding_discount_amount" placeholder="0">
                            </div>
                        </div>

                        <div class="border-t border-dashed border-gray-200"></div>

                        {{-- 3. Pajak --}}
                        <div>
                            <label class="block text-xs font-bold text-gray-700 mb-1">Pajak (PPN)</label>
                            <select name="tax_id" id="tax_id" class="block w-full rounded border-gray-300 text-xs py-1.5 focus:border-indigo-500 focus:ring-indigo-500">
                                <option value="">-- Tidak Ada Pajak --</option>
                                @foreach(\App\Models\Tax::where('is_active', true)->get() as $tax)
                                    <option value="{{ $tax->id }}" data-rate="{{ $tax->rate }}">{{ $tax->name }} ({{ $tax->rate }}%)</option>
                                @endforeach
                            </select>
                        </div>

                        {{-- 4. Opsi Lanjutan --}}
                        <div>
                            <a class="text-xs text-indigo-600 font-semibold cursor-pointer hover:underline flex items-center" data-bs-toggle="collapse" href="#advancedTaxOptions" role="button" aria-expanded="false" onclick="document.getElementById('advancedTaxOptions').classList.toggle('hidden')">
                                <i class="bi bi-sliders mr-1"></i> Opsi Pajak Lanjutan
                            </a>
                            <div class="hidden mt-2 p-3 bg-gray-50 rounded border border-gray-200" id="advancedTaxOptions">
                                <div class="flex items-center mb-2">
                                    <input type="checkbox" id="use_custom_dpp_factor" name="use_custom_dpp_factor" value="1" class="rounded border-gray-300 text-indigo-600 focus:ring-indigo-500 h-4 w-4 mr-2">
                                    <label class="text-xs text-gray-700" for="use_custom_dpp_factor">Override Faktor DPP</label>
                                </div>
                                <input type="text" class="block w-full rounded border-gray-300 text-xs py-1.5 px-2 focus:border-indigo-500 focus:ring-indigo-500" name="custom_dpp_factor" id="custom_dpp_factor" placeholder="Contoh: 11/12">
                            </div>
                        </div>

                        {{-- 5. Ongkir --}}
                        <div>
                            <label class="block text-xs font-bold text-gray-700 mb-1">Ongkos Kirim (Rp)</label>
                            <input type="text" class="block w-full rounded border-gray-300 text-xs py-1.5 px-2 focus:border-indigo-500 focus:ring-indigo-500" name="shipping_amount" id="shipping_amount" value="0">
                        </div>
                    </div>

                    {{-- SUMMARY BOX --}}
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

                    {{-- ACTION BUTTONS --}}
                    <div class="flex flex-col gap-3 mt-6">
                        <button type="submit" class="w-full flex justify-center py-3 px-4 border border-transparent rounded-lg shadow-sm text-sm font-bold text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 transition" id="submit-btn">
                            <i class="bi bi-save mr-2"></i> Simpan Pesanan
                        </button>
                        <a href="{{ route('purchase-orders.index') }}" class="w-full flex justify-center py-3 px-4 border border-gray-300 rounded-lg shadow-sm text-sm font-medium text-gray-700 bg-white hover:bg-gray-50 transition">Batal</a>
                    </div>
                    <div class="text-center mt-3">
                        <button type="button" class="text-xs text-red-500 hover:text-red-700 underline" onclick="clearDraft()">
                            <i class="bi bi-trash"></i> Hapus Draft
                        </button>
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
            {{-- Class .table-input dan .product-select penting untuk JS --}}
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
            {{-- Harga Beli --}}
            <div class="flex items-center border-b border-gray-300 focus-within:border-indigo-500 mb-1 pb-0.5">
                <span class="text-gray-400 text-[10px] mr-1">Rp</span>
                <input type="text" class="table-input purchase-price-formatted text-right p-0 border-none focus:ring-0 w-full text-sm font-medium" placeholder="0">
            </div>
            
            {{-- Checkbox Update Master --}}
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
    let clearDraft; 

    document.addEventListener('DOMContentLoaded', function () {
        const DRAFT_KEY = 'po_draft_fix_v1'; 
        const form = document.getElementById('po-form');
        const productItemsContainer = document.getElementById('product-items');
        const productRowTemplate = document.getElementById('product-row-template');
        
        // Elements
        const autosaveIndicator = document.getElementById('autosave-indicator');
        const bulkDiscountInput = document.getElementById('bulk-discount-input');
        const selectAllBtn = document.getElementById('select-all-btn');
        const deselectAllBtn = document.getElementById('deselect-all-btn');
        const applyBulkBtn = document.getElementById('apply-bulk-discount-btn');
        const applyAllBtn = document.getElementById('apply-all-discount-btn');
        
        // Summary Elements
        const elSummarySubtotal = document.getElementById('summary-subtotal');
        const elSummaryDisc = document.getElementById('summary-disc');
        const elSummaryRounding = document.getElementById('summary-rounding');
        const elSummaryTaxable = document.getElementById('summary-taxable');
        const elSummaryDpp = document.getElementById('summary-dpp');
        const elSummaryPpn = document.getElementById('summary-ppn');
        const elSummaryGrand = document.getElementById('summary-grand');
        const elSummaryShipping = document.getElementById('summary-shipping');
        const elSummaryTaxRate = document.getElementById('summary-tax-rate');

        const inputTaxId = document.getElementById('tax_id');
        const inputUseCustomDpp = document.getElementById('use_custom_dpp_factor');
        const inputCustomDppFactor = document.getElementById('custom_dpp_factor');
        
        // Inputs for AutoNumeric formatting
        const inputDiscFeeAmount = document.getElementById('disc_fee_amount');
        const inputShipping = document.getElementById('shipping_amount');

        let productIndex = 0;
        let isRestoring = false; 

        // === INITIALIZE SELECT2 ===
        $('#supplier_id').select2({ theme: 'bootstrap-5', placeholder: '-- Pilih Supplier --', width: '100%' });
        $('#requester_user_id').select2({ theme: 'bootstrap-5', placeholder: '-- Pembelian Umum --', allowClear: true, width: '100%' });
        
        new AutoNumeric(inputDiscFeeAmount, { 
            decimalCharacter: ',', digitGroupSeparator: '.', decimalPlaces: 0, minimumValue: '0', emptyInputBehavior: 'zero'
        });
        
        new AutoNumeric(inputShipping, { 
            decimalCharacter: ',', digitGroupSeparator: '.', decimalPlaces: 0, minimumValue: '0', emptyInputBehavior: 'zero'
        });

        // === LOGIC TOGGLE MANUAL (GANTI BOOTSTRAP COLLAPSE) ===
        const discFeeCheckbox = document.getElementById('apply_disc_fee');
        const discFeeContainer = document.getElementById('disc-fee-container');
        
        discFeeCheckbox.addEventListener('change', function() {
            if(this.checked) {
                discFeeContainer.style.display = 'block';
            } else {
                discFeeContainer.style.display = 'none';
            }
            calculateTotals();
            saveFormState();
        });


        // === HELPER FUNCTIONS (SAME AS BEFORE) ===
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

        // === CORE CALCULATION LOGIC (SAME AS BEFORE) ===
        function calculateTotals() {
            const inputApplyDiscFee = document.getElementById('apply_disc_fee');
            const inputDiscFeePercent = document.getElementById('disc_fee_percent');
            const inputApplyRounding = document.getElementById('apply_rounding_discount');
            const inputRoundingAmount = document.getElementById('rounding_discount_amount');

            // 1. Subtotal Produk
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

            // 2. Diskon / Fee Header
            let discFeeAmount = 0;
            if (inputApplyDiscFee.checked) {
                const percent = parseFloat(inputDiscFeePercent.value) || 0;
                const fixed = getRawNumericValue('disc_fee_amount');
                if (percent > 0) discFeeAmount = (percent / 100.0) * subtotalBarang;
                else if (fixed > 0) discFeeAmount = fixed;
            }

            // 3. Pembulatan
            const roundingAmount = inputApplyRounding.checked ? 
                (parseFloat(inputRoundingAmount.value) || 0) : 0;

            // 4. Taxable Base
            const taxableBase = Math.max(0, subtotalBarang - discFeeAmount - roundingAmount);

            // 5. Hitung DPP & PPN
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

            // 6. Ongkir & Grand Total
            const shipping = getRawNumericValue('shipping_amount');
            const grandTotal = Math.round(taxableBase + ppn + shipping);

            // 7. Render View
            elSummarySubtotal.textContent = formatCurrency(subtotalBarang);
            elSummaryDisc.textContent = formatCurrency(discFeeAmount);
            elSummaryRounding.textContent = formatCurrency(roundingAmount);
            if(elSummaryTaxable) elSummaryTaxable.textContent = formatCurrency(taxableBase);
            elSummaryDpp.textContent = formatCurrency(dpp); 
            elSummaryPpn.textContent = formatCurrency(ppn);
            elSummaryShipping.textContent = formatCurrency(shipping);
            elSummaryGrand.textContent = formatCurrency(grandTotal);
            elSummaryTaxRate.textContent = taxRate;
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
                rounding_discount_amount: document.getElementById('rounding_discount_amount').value,
                
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
                document.getElementById('disc_fee_percent').value = data.disc_fee_percent;
                
                if (data.disc_fee_amount) {
                    AutoNumeric.getAutoNumericElement(inputDiscFeeAmount).set(data.disc_fee_amount);
                }
                
                document.getElementById('apply_rounding_discount').checked = data.apply_rounding_discount;
                document.getElementById('rounding_discount_amount').value = data.rounding_discount_amount;
                document.getElementById('tax_id').value = data.tax_id;
                document.getElementById('use_custom_dpp_factor').checked = data.use_custom_dpp_factor;
                document.getElementById('custom_dpp_factor').value = data.custom_dpp_factor;
                
                if (data.shipping_amount) {
                    AutoNumeric.getAutoNumericElement(inputShipping).set(data.shipping_amount);
                }

                // REPLACEMENT FOR BOOTSTRAP COLLAPSE
                if(data.apply_disc_fee) document.getElementById('disc-fee-container').style.display = 'block';
                if(data.use_custom_dpp_factor) document.getElementById('advancedTaxOptions').classList.remove('hidden');

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
                            calculateTotals();
                        }, 50);
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
            div.className = 'flex items-center gap-1 mb-1';
            div.innerHTML = `
                <input type="number" step="any" class="discount-percentage table-input w-16 text-xs text-center border rounded bg-white shadow-sm focus:ring-1 focus:ring-indigo-500" placeholder="%" value="${value}" name="products[${index}][discounts][]">
                <button type="button" class="remove-discount-btn text-red-500 hover:text-red-700 p-1 rounded-full hover:bg-red-50"><i class="bi bi-x"></i></button>
            `;
            
            const input = div.querySelector('.discount-percentage');
            input.addEventListener('input', () => { calculateTotals(); saveFormState(); });

            div.querySelector('.remove-discount-btn').onclick = () => { 
                div.remove(); 
                calculateTotals(); 
                saveFormState(); 
            };
            
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

                calculateTotals();
                saveFormState();
            });

            const qtyInput = newRow.querySelector('.quantity');
            const priceInput = newRow.querySelector('.purchase-price-formatted');
            const addDiscBtn = newRow.querySelector('.add-discount-btn');
            const delBtn = newRow.querySelector('.remove-product-btn');

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
                selectElem.select2('destroy');
                newRow.remove();
                calculateTotals();
                saveFormState();
            };

            const rowSelect = newRow.querySelector('.row-select');
            rowSelect.addEventListener('change', saveFormState);

            productIndex++;
            
            if (shouldCalculate) {
                calculateTotals();
                saveFormState();
            }
            
            return newRow;
        }

        // === GLOBAL LISTENERS SETUP ===
        function setupGlobalListeners() {
            const inputApplyDiscFee = document.getElementById('apply_disc_fee');
            const inputDiscFeePercent = document.getElementById('disc_fee_percent');
            const inputApplyRounding = document.getElementById('apply_rounding_discount');
            const inputRoundingAmount = document.getElementById('rounding_discount_amount');
            const inputUseCustomDpp = document.getElementById('use_custom_dpp_factor');
            const inputCustomDppFactor = document.getElementById('custom_dpp_factor');
            const bulkDiscountInput = document.getElementById('bulk-discount-input');

            const calculationInputs = [
                inputApplyDiscFee, inputDiscFeePercent, 
                inputApplyRounding, inputRoundingAmount, inputUseCustomDpp, 
                inputCustomDppFactor, document.getElementById('tax_id')
            ];
            
            document.getElementById('disc_fee_amount').addEventListener('autoNumeric:rawValueModified', () => { 
                calculateTotals(); 
                clearTimeout(window.saveTimeout); 
                window.saveTimeout = setTimeout(saveFormState, 500); 
            });
            
            document.getElementById('shipping_amount').addEventListener('autoNumeric:rawValueModified', () => { 
                calculateTotals(); 
                clearTimeout(window.saveTimeout); 
                window.saveTimeout = setTimeout(saveFormState, 500); 
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
            
            form.addEventListener('submit', (e) => {
                const dppInput = document.getElementById('custom_dpp_factor');
                if (dppInput && dppInput.value) {
                     dppInput.value = parseFractionOrNumber(dppInput.value); 
                }
                
                if (getAllRows().length === 0) { 
                    e.preventDefault(); 
                    Swal.fire('Perhatian', 'Harap tambahkan minimal satu produk.', 'warning');
                    return; 
                }

                localStorage.removeItem(DRAFT_KEY);
            });
            
            const applyDiscountBulk = (rows) => {
                const v = parseFloat(bulkDiscountInput.value);
                if (isNaN(v) || v <= 0) return Swal.fire('Info', 'Masukkan angka diskon valid (>0).', 'info');
                
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
            
            $('#add-product-btn').on('click', addProductRow); 
            
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
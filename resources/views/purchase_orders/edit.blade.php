@extends('layouts.app')

@section('content')

<div class="container-fluid py-2">
    
    {{-- HEADER HALAMAN --}}
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h3 class="fw-bold text-dark mb-1">Edit Pesanan Pembelian</h3>
            <p class="text-muted mb-0 small">
                Nomor PO: <span class="text-primary fw-bold">{{ $purchaseOrder->po_number ?? 'PO-'.$purchaseOrder->po_id }}</span>
            </p>
        </div>
        <div>
            <a href="{{ route('purchase-orders.index') }}" class="btn btn-outline-secondary btn-sm">
                <i class="bi bi-arrow-left me-1"></i> Kembali
            </a>
        </div>
    </div>

    {{-- ALERT ERROR --}}
    @if ($errors->any())
        <div class="alert alert-danger border-0 shadow-sm rounded-3 mb-4">
            <div class="d-flex align-items-center gap-3">
                <i class="bi bi-exclamation-triangle-fill fs-4"></i>
                <ul class="mb-0 ps-3">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        </div>
    @endif

    <form action="{{ route('purchase-orders.update', $purchaseOrder->po_id) }}" method="POST" id="po-form">
        @csrf
        @method('PUT')

        <div class="row g-4">
            {{-- KOLOM KIRI: INFORMASI & ITEM --}}
            <div class="col-lg-8 col-xl-9">
                
                {{-- 1. CARD INFORMASI PESANAN --}}
                <div class="card card-transaction border-0 shadow-sm mb-4">
                    <div class="card-header bg-white">
                        <div class="form-section-title mb-0"><i class="bi bi-info-circle"></i> Informasi Pesanan</div>
                    </div>
                    <div class="card-body p-4">
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label for="supplier_id" class="form-label fw-semibold text-secondary small">Supplier <span class="text-danger">*</span></label>
                                <div class="input-group">
                                    <span class="input-group-text bg-light border-end-0"><i class="bi bi-building"></i></span>
                                    <select name="supplier_id" id="supplier_id" class="form-select border-start-0 ps-0" required>
                                        <option value="" disabled>-- Pilih Supplier --</option>
                                        @foreach ($suppliers as $supplier)
                                            <option value="{{ $supplier->supplier_id }}" {{ old('supplier_id', $purchaseOrder->supplier_id) == $supplier->supplier_id ? 'selected' : '' }}>
                                                {{ $supplier->supplier_name }}
                                            </option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <label for="order_date" class="form-label fw-semibold text-secondary small">Tanggal Pesanan <span class="text-danger">*</span></label>
                                <input type="date" class="form-control" id="order_date" name="order_date" value="{{ old('order_date', optional($purchaseOrder->order_date)->format('Y-m-d')) }}" required>
                            </div>
                            <div class="col-md-3">
                                <label for="due_date" class="form-label fw-semibold text-secondary small">Jatuh Tempo <span class="fw-normal text-muted">(Opsional)</span></label>
                                <input type="date" class="form-control" id="due_date" name="due_date" 
                                    value="{{ old('due_date', optional($purchaseOrder->due_date)->format('Y-m-d')) }}">
                            </div>
                            <div class="col-md-12">
                                <label for="requester_user_id" class="form-label fw-semibold text-secondary small">Dipesan Oleh (User)</label>
                                <div class="input-group">
                                    <span class="input-group-text bg-light border-end-0"><i class="bi bi-person"></i></span>
                                    <select name="requester_user_id" id="requester_user_id" class="form-select border-start-0 ps-0">
                                        <option value="">-- Pembelian Umum / Stok --</option>
                                        @foreach ($users as $user)
                                            <option value="{{ $user->user_id }}" {{ old('requester_user_id', $purchaseOrder->requester_user_id) == $user->user_id ? 'selected' : '' }}>
                                                {{ $user->full_name }}
                                            </option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- 2. CARD RINCIAN BARANG --}}
                <div class="card card-transaction border-0 shadow-sm mb-4">
                    <div class="card-header bg-white d-flex justify-content-between align-items-center">
                        <div class="form-section-title mb-0"><i class="bi bi-box-seam"></i> Rincian Item</div>
                        {{-- Tombol Tambah Item DIPINDAH KE BAWAH --}}
                    </div>

                    {{-- Toolbar Bulk Action --}}
                    <div class="px-4 pt-3">
                        <div class="transaction-toolbar d-flex flex-wrap align-items-center justify-content-between gap-2">
                            <div class="d-flex align-items-center gap-2">
                                <span class="text-muted small fw-bold text-uppercase"><i class="bi bi-check-all me-1"></i> Bulk:</span>
                                <button type="button" id="select-all-btn" class="btn btn-xs btn-outline-secondary py-0 small" style="font-size: 0.75rem;">All</button>
                                <button type="button" id="deselect-all-btn" class="btn btn-xs btn-outline-secondary py-0 small" style="font-size: 0.75rem;">None</button>
                            </div>
                            <div class="input-group input-group-sm" style="max-width: 380px;">
                                <span class="input-group-text bg-white text-muted small">Diskon Massal</span>
                                <input id="bulk-discount-input" type="number" step="any" min="0" class="form-control" placeholder="%">
                                <button type="button" id="apply-bulk-discount-btn" class="btn btn-success text-white">Apply Selected</button>
                                <button type="button" id="apply-all-discount-btn" class="btn btn-outline-primary">Apply All</button>
                            </div>
                        </div>
                    </div>

                    <div class="card-body p-0 mt-2">
                        <div class="table-responsive">
                            <table class="table table-hover table-transaction mb-0 align-middle">
                                <thead>
                                    <tr>
                                        <th class="text-center ps-4" style="width:40px;">
                                            <input type="checkbox" class="form-check-input cursor-pointer" id="header-row-select">
                                        </th>
                                        <th style="width: 40%;">Produk</th> {{-- Lebar diperbesar agar muat --}}
                                        <th style="width: 15%;">Qty</th>
                                        <th style="width: 20%;">Harga Beli (@)</th>
                                        <th style="width: 15%;">Diskon</th>
                                        <th class="text-end pe-4" style="width: 15%;">Subtotal</th>
                                        <th class="text-center" style="width: 50px;"><i class="bi bi-gear"></i></th>
                                    </tr>
                                </thead>
                                <tbody id="product-items">
                                    {{-- JavaScript akan mengisi baris di sini --}}
                                </tbody>
                            </table>
                        </div>

                        {{-- Tombol Tambah Item (POSISI BARU DI BAWAH TABEL) --}}
                        <div class="p-3 bg-light border-top text-center">
                             <button type="button" id="add-product-btn" class="btn btn-primary rounded-pill px-4 shadow-sm">
                                <i class="bi bi-plus-lg me-1"></i> Tambah Item Baru
                            </button>
                        </div>
                        
                        {{-- Catatan Tambahan --}}
                        <div class="p-4 bg-white border-top mt-0">
                            <label for="notes" class="form-label fw-semibold text-secondary small">Catatan / Keterangan</label>
                            <textarea class="form-control bg-light border-muted" name="notes" id="notes" rows="2">{{ old('notes', $purchaseOrder->notes) }}</textarea>
                        </div>
                    </div>
                </div>
            </div>

            {{-- KOLOM KANAN: KALKULASI & SUMMARY --}}
            <div class="col-lg-4 col-xl-3">
                <div class="card card-transaction border-0 shadow-sm sticky-top" style="top: 20px; z-index: 99;">
                    <div class="card-header bg-white">
                        <div class="form-section-title mb-0"><i class="bi bi-calculator"></i> Ringkasan Biaya</div>
                    </div>
                    <div class="card-body p-4">
                        
                        {{-- OPSI KALKULASI --}}
                        <div class="mb-4">
                            {{-- 1. Diskon / Fee --}}
                            <div class="d-flex align-items-center justify-content-between mb-2">
                                <label class="form-check-label small fw-bold text-dark" for="apply_disc_fee">Diskon / Fee Tambahan</label>
                                <div class="form-check form-switch m-0">
                                    <input class="form-check-input cursor-pointer" type="checkbox" id="apply_disc_fee" name="apply_disc_fee" value="1" {{ old('apply_disc_fee', $purchaseOrder->apply_disc_fee) ? 'checked' : '' }}>
                                </div>
                            </div>
                            <div class="p-3 bg-light rounded-3 mb-3 border border-light shadow-sm collapse {{ old('apply_disc_fee', $purchaseOrder->apply_disc_fee) ? 'show' : '' }}" id="disc-fee-container">
                                <div class="row g-2">
                                    <div class="col-6">
                                        <label class="small text-muted mb-1">Diskon %</label>
                                        <input type="number" step="any" min="0" class="form-control form-control-sm" name="disc_fee_percent" id="disc_fee_percent" placeholder="0" value="{{ old('disc_fee_percent', $purchaseOrder->disc_fee_percent) }}">
                                    </div>
                                    <div class="col-6">
                                        <label class="small text-muted mb-1">Nominal (Rp)</label>
                                        <input type="number" step="any" min="0" class="form-control form-control-sm" name="disc_fee_amount" id="disc_fee_amount" placeholder="0" value="{{ old('disc_fee_amount', $purchaseOrder->disc_fee_amount) }}">
                                    </div>
                                </div>
                            </div>

                            {{-- 2. Pembulatan --}}
                            <div class="d-flex align-items-center justify-content-between mb-2">
                                <label class="form-check-label small fw-bold text-dark" for="apply_rounding_discount">Diskon Pembulatan</label>
                                <div class="form-check form-switch m-0">
                                    <input class="form-check-input cursor-pointer" type="checkbox" id="apply_rounding_discount" name="apply_rounding_discount" value="1" {{ old('apply_rounding_discount', $purchaseOrder->apply_rounding_discount) ? 'checked' : '' }}>
                                </div>
                            </div>
                            <div class="mb-3">
                                <div class="input-group input-group-sm">
                                    <span class="input-group-text border-end-0 bg-light text-muted">Rp</span>
                                    <input type="number" step="any" min="0" class="form-control border-start-0" name="rounding_discount_amount" id="rounding_discount_amount" placeholder="0" value="{{ old('rounding_discount_amount', $purchaseOrder->rounding_discount_amount) }}">
                                </div>
                            </div>

                            <hr class="border-dashed">

                            {{-- 3. Pajak --}}
                            <div class="mb-3">
                                <label class="form-label small fw-bold text-dark">Pajak (PPN)</label>
                                <select name="tax_id" id="tax_id" class="form-select form-select-sm">
                                    <option value="">-- Tidak Ada Pajak --</option>
                                    @foreach(\App\Models\Tax::where('is_active', true)->get() as $tax)
                                        <option value="{{ $tax->id }}" data-rate="{{ $tax->rate }}" {{ old('tax_id', $purchaseOrder->tax_id) == $tax->id ? 'selected' : '' }}>
                                            {{ $tax->name }} ({{ $tax->rate }}%)
                                        </option>
                                    @endforeach
                                </select>
                            </div>

                            {{-- 4. Custom DPP --}}
                            <div class="mb-3">
                                <a class="text-decoration-none small text-primary cursor-pointer fw-semibold" data-bs-toggle="collapse" href="#advancedTaxOptions" role="button" aria-expanded="false">
                                    <i class="bi bi-sliders me-1"></i> Opsi Pajak Lanjutan
                                </a>
                                <div class="collapse {{ old('use_custom_dpp_factor', $purchaseOrder->use_custom_dpp_factor) ? 'show' : '' }} mt-2 p-2 bg-light rounded border" id="advancedTaxOptions">
                                    <div class="form-check form-switch mb-2">
                                        <input class="form-check-input" type="checkbox" id="use_custom_dpp_factor" name="use_custom_dpp_factor" value="1" {{ old('use_custom_dpp_factor', $purchaseOrder->use_custom_dpp_factor) ? 'checked' : '' }}>
                                        <label class="form-check-label small" for="use_custom_dpp_factor">Override Faktor DPP</label>
                                    </div>
                                    <input type="text" class="form-control form-control-sm" name="custom_dpp_factor" id="custom_dpp_factor" placeholder="Contoh: 11/12" value="{{ old('custom_dpp_factor', $purchaseOrder->custom_dpp_factor) }}">
                                    <small class="text-muted d-block mt-1" style="font-size: 0.65rem;">Default PPN Inklusif: 100/(100+Rate)</small>
                                </div>
                            </div>

                            {{-- 5. Ongkir --}}
                            <div class="mb-2">
                                <label class="form-label small fw-bold text-dark">Ongkos Kirim (Rp)</label>
                                <input type="number" step="any" min="0" class="form-control form-control-sm" name="shipping_amount" id="shipping_amount" value="{{ old('shipping_amount', $purchaseOrder->shipping_amount ?? 0) }}">
                            </div>
                        </div>

                        {{-- SUMMARY BOX --}}
                        <div class="summary-box mt-4">
                            <div class="summary-item">
                                <span>Subtotal Barang</span>
                                <span class="fw-semibold text-dark" id="summary-subtotal">Rp 0</span>
                            </div>
                            <div class="summary-item text-danger">
                                <span>Diskon/Fee</span>
                                <span id="summary-disc">- Rp 0</span>
                            </div>
                            <div class="summary-item text-danger">
                                <span>Pembulatan</span>
                                <span id="summary-rounding">- Rp 0</span>
                            </div>
                            <div class="summary-item text-muted small fst-italic mt-2 border-top border-light pt-2">
                                <span>Taxable Base</span>
                                <span id="summary-taxable">Rp 0</span>
                            </div>
                            <div class="summary-item text-muted small fst-italic">
                                <span>DPP (Dasar Pajak)</span>
                                <span id="summary-dpp">Rp 0</span>
                            </div>
                            <div class="summary-item">
                                <span>PPN (<span id="summary-tax-rate">0</span>%)</span>
                                <span class="fw-semibold text-dark" id="summary-ppn">Rp 0</span>
                            </div>
                            <div class="summary-item">
                                <span>Ongkos Kirim</span>
                                <span class="fw-semibold text-dark" id="summary-shipping">Rp 0</span>
                            </div>
                            
                            <div class="summary-total">
                                <span class="label">GRAND TOTAL</span>
                                <span class="value" id="summary-grand">Rp 0</span>
                            </div>
                        </div>

                        {{-- ACTIONS --}}
                        <div class="d-grid gap-2 mt-4">
                            <button type="submit" class="btn btn-success btn-lg fw-bold shadow-sm" id="submit-btn">
                                <i class="bi bi-check-circle me-2"></i> Update Pesanan
                            </button>
                            <a href="{{ route('purchase-orders.index') }}" class="btn btn-light text-muted border">Batal</a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </form>
</div>

{{-- Template Row (Sama dengan Create) --}}
<template id="product-row-template">
    <tr>
        <td class="text-center align-middle ps-4">
            <input type="checkbox" class="row-select form-check-input cursor-pointer">
        </td>
        <td class="align-middle">
            <select class="form-select form-select-sm product-select table-input" required>
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
        <td class="align-middle">
            <div class="input-group input-group-sm">
                <input type="number" class="form-control table-input quantity text-center fw-bold" value="1" min="1" step="any" required>
                <span class="input-group-text bg-light unit-display text-muted small">-</span>
            </div>
        </td>
        <td class="align-middle">
            <div class="input-group input-group-sm mb-1">
                <span class="input-group-text bg-transparent border-0 px-1 text-muted" style="font-size: 0.8rem;">Rp</span>
                <input type="text" class="form-control table-input purchase-price-formatted text-end" placeholder="0">
            </div>
            {{-- Checkbox Update Master --}}
            <div class="form-check mb-0 ps-1 text-end">
                <input class="form-check-input update-master-price float-none me-1" type="checkbox" value="1" style="transform: scale(0.8);">
                <label class="form-check-label text-muted fst-italic" style="font-size: 0.7rem;">Update Master</label>
            </div>
        </td>
        <td class="align-middle">
            <div class="discount-container mb-1"></div>
            <button type="button" class="btn btn-link btn-sm p-0 text-decoration-none add-discount-btn small fw-bold" style="font-size: 0.75rem;">
                <i class="bi bi-plus-circle"></i> Disc
            </button>
        </td>
        <td class="text-end pe-4 align-middle fw-bold text-dark">
            <span class="subtotal">Rp 0</span>
        </td>
        <td class="text-center align-middle">
            <button type="button" class="btn btn-icon btn-sm text-danger remove-product-btn p-1 rounded-circle hover-bg-light" title="Hapus Item">
                <i class="bi bi-trash"></i>
            </button>
        </td>
    </tr>
</template>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    // === KONFIGURASI & SELEKTOR ===
    // Mengambil data item existing dari database
    const existingPoItems = typeof @json($purchaseOrder->items ?? []) !== 'undefined' ? @json($purchaseOrder->items->load('discounts') ?? []) : [];
    
    const form = document.getElementById('po-form');
    const productItemsContainer = document.getElementById('product-items');
    const productRowTemplate = document.getElementById('product-row-template');
    
    // Buttons
    const addProductBtn = document.getElementById('add-product-btn');
    const selectAllBtn = document.getElementById('select-all-btn');
    const deselectAllBtn = document.getElementById('deselect-all-btn');
    const applyBulkBtn = document.getElementById('apply-bulk-discount-btn');
    const applyAllBtn = document.getElementById('apply-all-discount-btn');
    
    // Inputs Header & Summary
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
    
    // Opsi Kalkulasi
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

    // === INIT HEADER DROPDOWNS ===
    $('#supplier_id').select2({ theme: 'bootstrap-5', placeholder: '-- Pilih Supplier --', width: '100%' });
    $('#requester_user_id').select2({ theme: 'bootstrap-5', placeholder: '-- Pembelian Umum / Stok --', allowClear: true, width: '100%' });

    // === 1. CORE CALCULATION LOGIC ===
    function calculateRowSubtotal(row) {
        const quantity = parseFloat(row.querySelector('.quantity').value) || 0;
        const price = parseFloat(row.querySelector('.purchase-price-hidden')?.value || 0);
        
        let finalUnitPrice = price;
        row.querySelectorAll('.discount-percentage').forEach(input => {
            const rate = parseFloat(input.value) || 0;
            if (rate > 0 && rate <= 100) {
                finalUnitPrice = finalUnitPrice * (1 - (rate / 100));
            }
        });

        const subtotal = quantity * finalUnitPrice;
        const subtotalEl = row.querySelector('.subtotal');
        if(subtotalEl) subtotalEl.textContent = formatCurrency(subtotal);
        
        return subtotal;
    }

    function calculateTotals() {
        let subtotalBarang = 0;
        getAllRows().forEach(row => {
            subtotalBarang += calculateRowSubtotal(row);
        });

        // 2. Diskon / Fee
        let discFeeAmount = 0;
        if (inputApplyDiscFee.checked) {
            const percent = parseFloat(inputDiscFeePercent.value) || 0;
            const fixed = parseFloat(inputDiscFeeAmount.value) || 0;
            if (percent > 0) discFeeAmount = (percent / 100.0) * subtotalBarang;
            else if (fixed > 0) discFeeAmount = fixed;
        }

        // 3. Pembulatan
        const roundingAmount = inputApplyRounding.checked ? (parseFloat(inputRoundingAmount.value) || 0) : 0;

        // 4. Taxable Base
        const taxableBase = Math.max(0, subtotalBarang - discFeeAmount - roundingAmount);

        // 5. Hitung DPP & PPN
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

        // 6. Ongkir
        const shipping = parseFloat(inputShipping.value || 0);

        // 7. Grand Total
        const grandTotal = Math.round(taxableBase + ppn + shipping);

        // Update UI
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

    // === 3. HELPER FUNCTIONS ===
    function formatCurrency(n) {
        if (n === null || n === undefined) return 'Rp 0';
        return new Intl.NumberFormat('id-ID', { style: 'currency', currency: 'IDR', minimumFractionDigits: 0 }).format(Math.round(n));
    }
    function formatThousands(n) {
        if (n === '' || n === null || isNaN(n)) return '';
        return new Intl.NumberFormat('id-ID', { maximumFractionDigits: 0 }).format(Math.floor(Number(n)));
    }
    function parseNumericForInput(str) {
        if (!str && str !== 0) return 0;
        let s = String(str).replace(/[^\d\-\.\,]/g, '');
        s = s.replace(/\./g, '').replace(/,/g, '.');
        const v = parseFloat(s);
        return isNaN(v) ? 0 : v;
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
    function getAllRows() { return Array.from(productItemsContainer.querySelectorAll('tr')); }
    function getSelectedTaxRate() {
        const opt = inputTaxId.selectedOptions[0];
        return (opt && opt.value) ? parseFloat(opt.dataset.rate) : null;
    }

    // === 4. ROW MANAGEMENT ===
    function createDiscountInputForRow(row, value = '') {
        const index = row.dataset.index;
        const container = row.querySelector('.discount-container');
        const div = document.createElement('div');
        div.className = 'input-group input-group-sm mb-1';
        div.innerHTML = `
            <input type="number" step="any" class="form-control discount-percentage table-input" placeholder="%" value="${value}" name="products[${index}][discounts][]">
            <button type="button" class="btn btn-outline-danger remove-discount-btn px-2"><i class="bi bi-x"></i></button>
        `;
        div.querySelector('.remove-discount-btn').onclick = () => { div.remove(); calculateRowSubtotal(row); calculateTotals(); };
        div.querySelector('.discount-percentage').oninput = () => { calculateRowSubtotal(row); calculateTotals(); };
        container.appendChild(div);
    }

    function addProductRow(shouldCalculate = true) {
        const newRow = productRowTemplate.content.cloneNode(true).querySelector('tr');
        newRow.dataset.index = productIndex;
        
        const productSelect = newRow.querySelector('.product-select');
        const quantityInput = newRow.querySelector('.quantity');
        const formattedPriceInput = newRow.querySelector('.purchase-price-formatted');
        const addDiscountBtn = newRow.querySelector('.add-discount-btn');
        const removeBtn = newRow.querySelector('.remove-product-btn');
        const updateMasterCheckbox = newRow.querySelector('.update-master-price');
        
        const priceHiddenInput = document.createElement('input');
        priceHiddenInput.type = 'hidden';
        priceHiddenInput.className = 'purchase-price-hidden';
        priceHiddenInput.name = `products[${productIndex}][price_per_unit]`;
        priceHiddenInput.value = '0';
        formattedPriceInput.parentElement.appendChild(priceHiddenInput);

        productSelect.name = `products[${productIndex}][product_id]`;
        quantityInput.name = `products[${productIndex}][quantity]`;
        updateMasterCheckbox.name = `products[${productIndex}][update_master_price]`;
        
        productItemsContainer.appendChild(newRow);

        $(productSelect).select2({ placeholder: '-- Cari Produk --', theme: 'bootstrap-5', width: '100%' })
            .on('select2:select', function(e) {
                const el = e.params.data.element;
                newRow.querySelector('.unit-display').textContent = el.dataset.unit || '-';
                const defaultPrice = el.dataset.defaultPrice || 0;
                priceHiddenInput.value = defaultPrice;
                formattedPriceInput.value = formatThousands(defaultPrice);
                newRow.querySelector('.discount-container').innerHTML = '';
                try { JSON.parse(el.dataset.defaultDiscounts || '[]').forEach(d => createDiscountInputForRow(newRow, d)); } catch (err) {}
                calculateRowSubtotal(newRow);
                if (shouldCalculate) calculateTotals();
            });

        addDiscountBtn.onclick = () => createDiscountInputForRow(newRow, '');
        removeBtn.onclick = () => { $(productSelect).select2('destroy'); newRow.remove(); if (shouldCalculate) calculateTotals(); };
        
        const updatePrice = () => {
            priceHiddenInput.value = parseNumericForInput(formattedPriceInput.value);
            calculateRowSubtotal(newRow);
            if (shouldCalculate) calculateTotals();
        };
        formattedPriceInput.oninput = updatePrice;
        formattedPriceInput.onblur = () => { formattedPriceInput.value = formatThousands(priceHiddenInput.value); updatePrice(); };
        quantityInput.oninput = () => { calculateRowSubtotal(newRow); if (shouldCalculate) calculateTotals(); };
        
        productIndex++;
        return newRow;
    }

    // === 5. POPULATE EXISTING ITEMS (KHUSUS EDIT PAGE) ===
    function populateExistingItems() {
        if (existingPoItems && existingPoItems.length > 0) {
            existingPoItems.forEach(item => {
                const newRow = addProductRow(false);
                
                // Set Value Select2
                $(newRow.querySelector('.product-select')).val(item.product_id).trigger('change.select2');
                
                // Set Value Inputs secara manual (karena trigger select2 mungkin mereset)
                setTimeout(() => {
                    newRow.querySelector('.quantity').value = item.quantity;
                    
                    const formattedPriceInput = newRow.querySelector('.purchase-price-formatted');
                    const hiddenPriceInput = newRow.querySelector('.purchase-price-hidden');
                    
                    // Pastikan harga dari DB masuk
                    if(hiddenPriceInput) hiddenPriceInput.value = item.price_per_unit;
                    if(formattedPriceInput) formattedPriceInput.value = formatThousands(item.price_per_unit);
                    
                    const discountContainer = newRow.querySelector('.discount-container');
                    if(discountContainer) discountContainer.innerHTML = '';
                    
                    // Load Diskon per item jika ada
                    if (item.discounts && item.discounts.length > 0) {
                        item.discounts.forEach(discount => createDiscountInputForRow(newRow, discount.percentage));
                    }
                    
                    calculateRowSubtotal(newRow);
                    calculateTotals();
                }, 100);
            });
        } else {
            addProductRow();
        }
    }

    // === 6. EVENT LISTENERS ===
    headerRowSelect.onchange = (e) => getAllRows().forEach(r => r.querySelector('.row-select').checked = e.target.checked);
    selectAllBtn.onclick = () => getAllRows().forEach(r => r.querySelector('.row-select').checked = true);
    deselectAllBtn.onclick = () => getAllRows().forEach(r => r.querySelector('.row-select').checked = false);
    
    const applyDiscount = (rows) => {
        const v = parseFloat(bulkDiscountInput.value);
        if (isNaN(v)) return Swal.fire('Error', 'Masukkan angka diskon valid', 'warning');
        rows.forEach(r => createDiscountInputForRow(r, v));
        rows.forEach(r => calculateRowSubtotal(r));
        calculateTotals();
    };
    applyBulkBtn.onclick = () => {
        const rows = getAllRows().filter(r => r.querySelector('.row-select').checked);
        if (rows.length === 0) return Swal.fire('Info', 'Pilih baris terlebih dahulu.', 'info');
        applyDiscount(rows);
    };
    applyAllBtn.onclick = () => applyDiscount(getAllRows());
    addProductBtn.onclick = () => addProductRow();
    
    [inputApplyDiscFee, inputDiscFeePercent, inputDiscFeeAmount, inputApplyRounding, inputRoundingAmount, inputUseCustomDpp, inputCustomDppFactor, inputTaxId, inputShipping].forEach(el => {
        el.addEventListener('input', calculateTotals);
        el.addEventListener('change', calculateTotals);
    });
    
    // Init: Load Data from DB
    populateExistingItems();
});
</script>
@endpush
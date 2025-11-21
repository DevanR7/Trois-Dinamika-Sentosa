@extends('layouts.app')

@section('content')
<div class="container-fluid py-2">
    
    {{-- Indikator Auto Save --}}
    <div id="autosave-indicator" class="position-fixed top-0 end-0 p-3" style="z-index: 1050; display: none;">
        <div class="toast show align-items-center text-white bg-success border-0 shadow" role="alert" aria-live="assertive" aria-atomic="true">
            <div class="d-flex">
                <div class="toast-body small">
                    <i class="bi bi-cloud-check me-1"></i> Draft disimpan otomatis
                </div>
            </div>
        </div>
    </div>

    {{-- HEADER HALAMAN --}}
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h3 class="fw-bold text-dark mb-1">Buat Purchase Order Baru</h3>
            <p class="text-muted mb-0 small">Isi formulir di bawah untuk membuat pesanan pembelian ke supplier.</p>
        </div>
        <div>
            <a href="{{ route('purchase-orders.index') }}" class="btn btn-outline-secondary btn-sm">
                <i class="bi bi-arrow-left me-1"></i> Kembali ke List
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

    {{-- FORM UTAMA --}}
    <form action="{{ route('purchase-orders.store') }}" method="POST" id="po-form">
        @csrf

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
                                <label for="supplier_id" class="form-label fw-semibold text-secondary small">Pilih Supplier <span class="text-danger">*</span></label>
                                <div class="input-group">
                                    <span class="input-group-text bg-light border-end-0"><i class="bi bi-building"></i></span>
                                    <select name="supplier_id" id="supplier_id" class="form-select border-start-0 ps-0" required>
                                        <option value="" disabled selected>-- Pilih Supplier --</option>
                                        @foreach ($suppliers as $supplier)
                                            <option value="{{ $supplier->supplier_id }}">{{ $supplier->supplier_name }}</option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <label for="order_date" class="form-label fw-semibold text-secondary small">Tanggal Pesanan <span class="text-danger">*</span></label>
                                <input type="date" class="form-control" id="order_date" name="order_date" value="{{ now()->format('Y-m-d') }}" required>
                            </div>
                            <div class="col-md-3">
                                <label for="due_date" class="form-label fw-semibold text-secondary small">Jatuh Tempo <span class="fw-normal text-muted">(Opsional)</span></label>
                                <input type="date" class="form-control" id="due_date" name="due_date" value="{{ old('due_date') }}">
                            </div>
                            <div class="col-md-12">
                                <label for="requester_user_id" class="form-label fw-semibold text-secondary small">Dipesan Oleh (User)</label>
                                <div class="input-group">
                                    <span class="input-group-text bg-light border-end-0"><i class="bi bi-person"></i></span>
                                    <select name="requester_user_id" id="requester_user_id" class="form-select border-start-0 ps-0">
                                        <option value="">-- Pembelian Umum / Stok --</option>
                                        @foreach ($users as $user)
                                            <option value="{{ $user->user_id }}">{{ $user->full_name }}</option>
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
                                        <th style="width: 30%;">Produk</th>
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
                        
                        {{-- Tombol Tambah Item --}}
                        <div class="p-3 bg-light border-top text-center">
                             <button type="button" id="add-product-btn" class="btn btn-primary rounded-pill px-4 shadow-sm">
                                <i class="bi bi-plus-lg me-1"></i> Tambah Item Baru
                            </button>
                        </div>

                        {{-- Catatan Tambahan --}}
                        <div class="p-4 bg-white border-top mt-0">
                            <label for="notes" class="form-label fw-semibold text-secondary small">Catatan / Keterangan</label>
                            <textarea class="form-control bg-light border-muted" name="notes" id="notes" rows="2" placeholder="Contoh: Pengiriman tolong dilakukan lewat pintu gudang belakang...">{{ old('notes') }}</textarea>
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
                            {{-- 1. Diskon / Fee Tambahan --}}
                            <div class="d-flex align-items-center justify-content-between mb-2">
                                <label class="form-check-label small fw-bold text-dark" for="apply_disc_fee">Diskon / Fee Tambahan</label>
                                <div class="form-check form-switch m-0">
                                    <input class="form-check-input cursor-pointer" type="checkbox" id="apply_disc_fee" name="apply_disc_fee" value="1">
                                </div>
                            </div>
                            <div class="p-3 bg-light rounded-3 mb-3 border border-light shadow-sm collapse show" id="disc-fee-container">
                                <div class="row g-2">
                                    <div class="col-6">
                                        <label class="small text-muted mb-1">Diskon %</label>
                                        <input type="number" step="any" min="0" class="form-control form-control-sm" name="disc_fee_percent" id="disc_fee_percent" placeholder="0">
                                    </div>
                                    <div class="col-6">
                                        <label class="small text-muted mb-1">Nominal (Rp)</label>
                                        <input type="number" step="any" min="0" class="form-control form-control-sm" name="disc_fee_amount" id="disc_fee_amount" placeholder="0">
                                    </div>
                                </div>
                            </div>

                            {{-- 2. Diskon Pembulatan --}}
                            <div class="d-flex align-items-center justify-content-between mb-2">
                                <label class="form-check-label small fw-bold text-dark" for="apply_rounding_discount">Diskon Pembulatan</label>
                                <div class="form-check form-switch m-0">
                                    <input class="form-check-input cursor-pointer" type="checkbox" id="apply_rounding_discount" name="apply_rounding_discount" value="1">
                                </div>
                            </div>
                            <div class="mb-3">
                                <div class="input-group input-group-sm">
                                    <span class="input-group-text border-end-0 bg-light text-muted">Rp</span>
                                    <input type="number" step="any" min="0" class="form-control border-start-0" name="rounding_discount_amount" id="rounding_discount_amount" placeholder="0">
                                </div>
                            </div>

                            <hr class="border-dashed">

                            {{-- 3. Pajak --}}
                            <div class="mb-3">
                                <label class="form-label small fw-bold text-dark">Pajak (PPN)</label>
                                <select name="tax_id" id="tax_id" class="form-select form-select-sm">
                                    <option value="">-- Tidak Ada Pajak --</option>
                                    @foreach(\App\Models\Tax::where('is_active', true)->get() as $tax)
                                        <option value="{{ $tax->id }}" data-rate="{{ $tax->rate }}">{{ $tax->name }} ({{ $tax->rate }}%)</option>
                                    @endforeach
                                </select>
                            </div>

                            {{-- 4. Opsi Lanjutan --}}
                            <div class="mb-3">
                                <a class="text-decoration-none small text-primary cursor-pointer fw-semibold" data-bs-toggle="collapse" href="#advancedTaxOptions" role="button" aria-expanded="false">
                                    <i class="bi bi-sliders me-1"></i> Opsi Pajak Lanjutan
                                </a>
                                <div class="collapse mt-2 p-2 bg-light rounded border" id="advancedTaxOptions">
                                    <div class="form-check form-switch mb-2">
                                        <input class="form-check-input" type="checkbox" id="use_custom_dpp_factor" name="use_custom_dpp_factor" value="1">
                                        <label class="form-check-label small" for="use_custom_dpp_factor">Override Faktor DPP</label>
                                    </div>
                                    <input type="text" class="form-control form-control-sm" name="custom_dpp_factor" id="custom_dpp_factor" placeholder="Contoh: 11/12">
                                </div>
                            </div>

                            {{-- 5. Ongkir --}}
                            <div class="mb-2">
                                <label class="form-label small fw-bold text-dark">Ongkos Kirim (Rp)</label>
                                <input type="number" step="any" min="0" class="form-control form-control-sm" name="shipping_amount" id="shipping_amount" value="0">
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
                            
                            <div class="summary-item text-muted small border-top border-light pt-2">
                                <span>Taxable Base</span>
                                <span id="summary-taxable">Rp 0</span>
                            </div>

                            <div class="summary-item text-muted small fst-italic">
                                <span>DPP</span>
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

                        {{-- ACTION BUTTONS --}}
                        <div class="d-grid gap-2 mt-4">
                            <button type="submit" class="btn btn-primary btn-lg fw-bold shadow-sm" id="submit-btn">
                                <i class="bi bi-save me-2"></i> Simpan Pesanan
                            </button>
                            <a href="{{ route('purchase-orders.index') }}" class="btn btn-light text-muted border">Batal</a>
                        </div>
                        <div class="text-center mt-2">
                            <button type="button" class="btn btn-link text-danger small p-0" onclick="clearDraft()" style="font-size: 0.7rem;">
                                <i class="bi bi-trash"></i> Hapus Draft
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </form>
</div>

{{-- TEMPLATE ROW PRODUK --}}
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
            {{-- Harga Beli dengan Input Group --}}
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
// Variabel global agar bisa diakses fungsi inline
let clearDraft; 

document.addEventListener('DOMContentLoaded', function () {
    // === CONFIGURATION ===
    const DRAFT_KEY = 'po_draft_fix_v1'; 
    const form = document.getElementById('po-form');
    const productItemsContainer = document.getElementById('product-items');
    const productRowTemplate = document.getElementById('product-row-template');
    
    // Elements
    const addProductBtn = document.getElementById('add-product-btn');
    const autosaveIndicator = document.getElementById('autosave-indicator');
    
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

    let productIndex = 0;
    let isRestoring = false; // Flag untuk mencegah save saat restore berjalan

    // === INITIALIZE SELECT2 ===
    $('#supplier_id').select2({ theme: 'bootstrap-5', placeholder: '-- Pilih Supplier --', width: '100%' });
    $('#requester_user_id').select2({ theme: 'bootstrap-5', placeholder: '-- Pembelian Umum --', allowClear: true, width: '100%' });

    // === CALCULATION FUNCTIONS ===
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

        // Hitungan Header
        let discFeeAmount = 0;
        if (document.getElementById('apply_disc_fee').checked) {
            const percent = parseFloat(document.getElementById('disc_fee_percent').value) || 0;
            const fixed = parseFloat(document.getElementById('disc_fee_amount').value) || 0;
            if (percent > 0) discFeeAmount = (percent / 100.0) * subtotalBarang;
            else if (fixed > 0) discFeeAmount = fixed;
        }

        const roundingAmount = document.getElementById('apply_rounding_discount').checked ? 
            (parseFloat(document.getElementById('rounding_discount_amount').value) || 0) : 0;

        const taxableBase = Math.max(0, subtotalBarang - discFeeAmount - roundingAmount);

        // Tax
        let dpp = 0, ppn = 0;
        let taxRate = 0;
        const taxOpt = document.getElementById('tax_id').selectedOptions[0];
        
        if (taxOpt && taxOpt.value) {
            taxRate = parseFloat(taxOpt.dataset.rate);
            let dppFactor = 100 / (100 + taxRate);
            
            if (document.getElementById('use_custom_dpp_factor').checked) {
                dppFactor = parseFractionOrNumber(document.getElementById('custom_dpp_factor').value);
            }
            
            dpp = Math.round(taxableBase * dppFactor);
            ppn = Math.round(dpp * (taxRate / 100.0));
        }

        const shipping = parseFloat(document.getElementById('shipping_amount').value || 0);
        const grandTotal = Math.round(taxableBase + ppn + shipping);

        // Render View
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

    // === HELPER FORMATTERS ===
    function formatCurrency(n) {
        return new Intl.NumberFormat('id-ID', { style: 'currency', currency: 'IDR', minimumFractionDigits: 0 }).format(Math.round(n || 0));
    }
    function formatThousands(n) {
        return new Intl.NumberFormat('id-ID', { maximumFractionDigits: 0 }).format(Math.floor(Number(n || 0)));
    }
    function parseNumericForInput(str) {
        if (!str) return 0;
        return parseFloat(String(str).replace(/\./g, '').replace(/,/g, '.')) || 0;
    }
    function parseFractionOrNumber(val) {
        if (!val) return 1;
        if (val.includes('/')) {
            const [n, d] = val.split('/');
            return parseFloat(n) / parseFloat(d);
        }
        return parseFloat(val) || 1;
    }
    function getAllRows() { return Array.from(productItemsContainer.querySelectorAll('tr')); }

    // === SAVE & LOAD LOGIC (CORE FIX) ===
    function showSaveIndicator() {
        autosaveIndicator.style.display = 'block';
        setTimeout(() => { autosaveIndicator.style.display = 'none'; }, 2000);
    }

    function saveFormState() {
        if (isRestoring) return; // Jangan save saat sedang loading data

        const productRowsData = getAllRows().map(row => {
            // Ambil nilai dengan aman
            const select = row.querySelector('.product-select');
            const productId = $(select).val(); // Gunakan jQuery utk Select2 value
            
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
            disc_fee_amount: document.getElementById('disc_fee_amount').value,
            
            apply_rounding_discount: document.getElementById('apply_rounding_discount').checked,
            rounding_discount_amount: document.getElementById('rounding_discount_amount').value,
            
            tax_id: document.getElementById('tax_id').value,
            use_custom_dpp_factor: document.getElementById('use_custom_dpp_factor').checked,
            custom_dpp_factor: document.getElementById('custom_dpp_factor').value,
            shipping_amount: document.getElementById('shipping_amount').value,
            
            products: productRowsData
        };

        localStorage.setItem(DRAFT_KEY, JSON.stringify(state));
        showSaveIndicator();
    }

    function loadFormState() {
        const savedData = localStorage.getItem(DRAFT_KEY);
        if (!savedData) {
            addProductRow(); // Default 1 baris kosong
            return;
        }

        isRestoring = true; // Pause saving
        try {
            const data = JSON.parse(savedData);
            
            // Restore Header Fields
            if(data.supplier_id) $('#supplier_id').val(data.supplier_id).trigger('change.select2');
            if(data.requester_user_id) $('#requester_user_id').val(data.requester_user_id).trigger('change.select2');
            document.getElementById('order_date').value = data.order_date || '';
            document.getElementById('due_date').value = data.due_date || '';
            document.getElementById('notes').value = data.notes || '';

            // Restore Checkboxes & Inputs
            document.getElementById('apply_disc_fee').checked = data.apply_disc_fee;
            document.getElementById('disc_fee_percent').value = data.disc_fee_percent;
            document.getElementById('disc_fee_amount').value = data.disc_fee_amount;
            document.getElementById('apply_rounding_discount').checked = data.apply_rounding_discount;
            document.getElementById('rounding_discount_amount').value = data.rounding_discount_amount;
            document.getElementById('tax_id').value = data.tax_id;
            document.getElementById('use_custom_dpp_factor').checked = data.use_custom_dpp_factor;
            document.getElementById('custom_dpp_factor').value = data.custom_dpp_factor;
            document.getElementById('shipping_amount').value = data.shipping_amount;

            // Trigger UI Collapses
            if(data.apply_disc_fee) new bootstrap.Collapse(document.getElementById('disc-fee-container'), {toggle:false}).show();
            if(data.use_custom_dpp_factor) new bootstrap.Collapse(document.getElementById('advancedTaxOptions'), {toggle:false}).show();

            // Restore Products
            productItemsContainer.innerHTML = ''; // Kosongkan dulu
            if (data.products && data.products.length > 0) {
                data.products.forEach(p => {
                    const row = addProductRow(false); // Buat row tapi jangan hitung dulu
                    
                    // Set Select2 Value
                    const select = $(row.querySelector('.product-select'));
                    select.val(p.productId).trigger('change.select2'); 
                    
                    // Set Inputs Manual
                    row.querySelector('.quantity').value = p.quantity;
                    row.querySelector('.purchase-price-formatted').value = p.priceFormatted;
                    row.querySelector('.purchase-price-hidden').value = p.priceHidden;
                    row.querySelector('.update-master-price').checked = p.updateMaster;

                    // Restore Discounts per row
                    if(p.discounts) {
                        p.discounts.forEach(val => createDiscountInputForRow(row, val));
                    }
                });
            } else {
                addProductRow();
            }

            // Trigger Calculation Akhir
            setTimeout(() => {
                calculateTotals();
                isRestoring = false; // Resume saving
                console.log('Draft loaded successfully');
            }, 500);

        } catch (e) {
            console.error("Gagal load draft:", e);
            isRestoring = false;
            addProductRow();
        }
    }

    // === ROW MANAGEMENT ===
    function createDiscountInputForRow(row, value = '') {
        const index = row.dataset.index;
        const container = row.querySelector('.discount-container');
        const div = document.createElement('div');
        div.className = 'input-group input-group-sm mb-1';
        div.innerHTML = `
            <input type="number" step="any" class="form-control discount-percentage table-input" placeholder="%" value="${value}" name="products[${index}][discounts][]">
            <button type="button" class="btn btn-outline-danger remove-discount-btn px-2"><i class="bi bi-x"></i></button>
        `;
        
        // Event Listener untuk diskon dinamis
        const input = div.querySelector('.discount-percentage');
        input.addEventListener('input', () => {
            calculateTotals();
            saveFormState();
        });

        div.querySelector('.remove-discount-btn').onclick = () => { 
            div.remove(); 
            calculateTotals(); 
            saveFormState(); 
        };
        
        container.appendChild(div);
    }

    function addProductRow() {
        const newRow = productRowTemplate.content.cloneNode(true).querySelector('tr');
        newRow.dataset.index = productIndex;

        // Set Names
        newRow.querySelector('.product-select').name = `products[${productIndex}][product_id]`;
        newRow.querySelector('.quantity').name = `products[${productIndex}][quantity]`;
        newRow.querySelector('.update-master-price').name = `products[${productIndex}][update_master_price]`;

        // Hidden Price Input creation
        const priceHidden = document.createElement('input');
        priceHidden.type = 'hidden';
        priceHidden.className = 'purchase-price-hidden';
        priceHidden.name = `products[${productIndex}][price_per_unit]`;
        priceHidden.value = '0';
        newRow.querySelector('.purchase-price-formatted').parentElement.appendChild(priceHidden);

        productItemsContainer.appendChild(newRow);

        // Initialize Select2 on new row
        const selectElem = $(newRow.querySelector('.product-select'));
        selectElem.select2({ placeholder: '-- Cari Produk --', theme: 'bootstrap-5', width: '100%' });

        // EVENT: Select2 Changed
        selectElem.on('select2:select', function(e) {
            const el = e.params.data.element;
            newRow.querySelector('.unit-display').textContent = el.dataset.unit || '-';
            
            // Set harga default jika hidden masih 0 (baru pilih)
            if(priceHidden.value == 0 || priceHidden.value == '0') {
                const defPrice = el.dataset.defaultPrice || 0;
                priceHidden.value = defPrice;
                newRow.querySelector('.purchase-price-formatted').value = formatThousands(defPrice);
            }

            calculateTotals();
            saveFormState(); // Save saat pilih produk
        });

        // EVENT: Inputs Changed
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

        productIndex++;
        return newRow;
    }

    // === GLOBAL LISTENERS ===
    // Simpan saat input text/number berubah
    form.addEventListener('input', () => {
        // Debounce sedikit agar tidak terlalu sering
        clearTimeout(window.saveTimeout);
        window.saveTimeout = setTimeout(saveFormState, 500);
    });

    // Simpan saat checkbox/select native berubah
    form.addEventListener('change', (e) => {
        if (!e.target.classList.contains('product-select')) { // Select2 dihandle terpisah
            saveFormState();
        }
    });

    // Simpan saat Select2 Supplier Header berubah
    $('#supplier_id, #requester_user_id').on('change', function() {
        saveFormState();
    });

    addProductBtn.onclick = () => addProductRow();

    // Hapus draft saat submit sukses
    form.addEventListener('submit', () => {
        localStorage.removeItem(DRAFT_KEY);
    });

    // Expose function hapus draft
    clearDraft = function() {
        if(confirm('Hapus draft form ini?')) {
            localStorage.removeItem(DRAFT_KEY);
            location.reload();
        }
    };

    // START
    loadFormState();
});
</script>
@endpush
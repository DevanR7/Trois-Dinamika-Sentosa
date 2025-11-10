@extends('layouts.app')

@push('styles')
<!-- SECTION: EXTERNAL STYLESHEETS -->
<link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/select2-bootstrap-5-theme@1.3.0/dist/select2-bootstrap-5-theme.min.css" />
@endpush

@section('content')
<!-- SECTION: MAIN CONTAINER -->
<div class="container py-4">
    <div class="row justify-content-center">
        <div class="col-lg-10">
            
            <!-- SECTION: CARD CONTAINER -->
            <div class="card shadow-sm border-0">
                
                <!-- SECTION: CARD HEADER -->
                <div class="card-header bg-primary text-white">
                    <h4 class="mb-0">Koreksi Otomatis untuk PO: {{ $purchaseOrder->po_number ?? 'PO-'.$purchaseOrder->po_id }}</h4>
                </div>
                <!-- END SECTION: CARD HEADER -->

                <!-- SECTION: CARD BODY -->
                <div class="card-body p-4">

                    <!-- SECTION: ERROR HANDLING -->
                    @if ($errors->any())
                        <div class="alert alert-danger">
                            <ul class="mb-0">
                                @foreach ($errors->all() as $error)
                                    <li>{{ $error }}</li>
                                @endforeach
                            </ul>
                        </div>
                    @endif
                    <!-- END SECTION: ERROR HANDLING -->
                    
                    <!-- SECTION: WARNING ALERT -->
                    <div class="alert alert-warning">
                        <strong>Perhatian!</strong> Anda sedang dalam mode "Koreksi Otomatis". Mengubah data di bawah ini **tidak akan** mengubah PO asli. Sistem akan **menghitung selisih** antara total lama (Rp {{ number_format($purchaseOrder->total_amount, 0, ',', '.') }}) dengan total baru yang Anda buat, lalu membuat Nota Kredit/Debit PO secara otomatis.
                    </div>
                    <!-- END SECTION: WARNING ALERT -->

                    <!-- SECTION: ADJUSTMENT FORM -->
                    <form action="{{ route('purchase-order-adjustments.store.auto', $purchaseOrder->po_id) }}" method="POST" id="po-form">
                        @csrf

                        <!-- SECTION: HEADER INFORMATION (READONLY) -->
                        <div class="row mb-4 g-3">
                            <div class="col-md-4">
                                <label class="form-label fw-semibold">Supplier</label>
                                <input type="text" class="form-control" value="{{ $purchaseOrder->supplier->supplier_name }}" readonly>
                                <input type="hidden" name="supplier_id" value="{{ $purchaseOrder->supplier_id }}">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label fw-semibold">Tanggal Pesanan</label>
                                <input type="date" class="form-control" value="{{ optional($purchaseOrder->order_date)->format('Y-m-d') }}" readonly>
                                <input type="hidden" name="order_date" value="{{ optional($purchaseOrder->order_date)->format('Y-m-d') }}">
                            </div> 
                            <div class="col-md-3">
                                <label class="form-label fw-semibold">Jatuh Tempo</label>
                                <input type="date" class="form-control" value="{{ optional($purchaseOrder->due_date)->format('Y-m-d') }}" readonly>
                                <input type="hidden" name="due_date" value="{{ optional($purchaseOrder->due_date)->format('Y-m-d') }}">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label fw-semibold">Dipesan Oleh</label>
                                <input type="text" class="form-control" value="{{ $purchaseOrder->requester->full_name ?? '-- Pembelian Umum --' }}" readonly>
                                <input type="hidden" name="requester_user_id" value="{{ $purchaseOrder->requester_user_id }}">
                            </div>
                        </div>
                        <!-- END SECTION: HEADER INFORMATION -->

                        <!-- SECTION: BULK ACTIONS -->
                        <div class="d-flex gap-2 mb-2 align-items-center">
                            <div>
                                <button type="button" id="select-all-btn" class="btn btn-sm btn-outline-secondary">Select All</button>
                                <button type="button" id="deselect-all-btn" class="btn btn-sm btn-outline-secondary">Deselect All</button>
                            </div>
                            <div class="input-group input-group-sm ms-3" style="width: 420px;">
                                <input id="bulk-discount-input" type="number" step="any" min="0" class="form-control" placeholder="Diskon (%) yang akan diterapkan">
                                <button type="button" id="apply-bulk-discount-btn" class="btn btn-success">Apply to Selected</button>
                                <button type="button" id="apply-all-discount-btn" class="btn btn-primary">Apply to All</button>
                            </div>
                        </div>
                        <!-- END SECTION: BULK ACTIONS -->

                        <!-- SECTION: PRODUCT ITEMS TABLE -->
                        <div class="row">
                            <div class="col-12">
                                <h5 class="fw-semibold mb-3">Rincian Item</h5>
                                <div class="table-responsive">
                                    <table class="table table-bordered align-middle">
                                        <thead class="table-light">
                                            <tr>
                                                <th class="text-center" style="width:40px;">
                                                    <input type="checkbox" class="form-check-input" id="header-row-select">
                                                </th>
                                                <th style="width: 30%;">Produk</th>
                                                <th style="width: 12%;">Kuantitas</th>
                                                <th style="width: 15%;">Harga Beli</th>
                                                <th style="width: 12%;">Diskon (%)</th>
                                                <th class="text-end" style="width: 20%;">Subtotal</th>
                                                <th class="text-center">Aksi</th>
                                            </tr>
                                        </thead>
                                        <tbody id="product-items"></tbody>
                                    </table>
                                </div>
                                <div class="mb-3">
                                    <button type="button" id="add-product-btn" class="btn btn-secondary btn-sm">
                                        <i class="bi bi-plus-circle me-1"></i> Tambah Item
                                    </button>
                                </div>
                                
                                <!-- SECTION: ADJUSTMENT REASON -->
                                <div class="mb-3 mt-4">
                                    <label for="notes" class="form-label fw-semibold">Alasan Koreksi (Wajib Diisi)</label>
                                    <textarea class="form-control" name="notes" id="notes" rows="3" placeholder="Contoh: Koreksi salah input diskon supplier" required>{{ old('notes') }}</textarea>
                                </div>
                                <!-- END SECTION: ADJUSTMENT REASON -->
                            </div>
                        </div>
                        <!-- END SECTION: PRODUCT ITEMS TABLE -->

                        <!-- SECTION: CALCULATION OPTIONS & SUMMARY -->
                        <div class="row mt-4">
                            <div class="col-12">
                                <h5 class="fw-semibold mb-3">Opsi Perhitungan & Ringkasan</h5>
                                <div class="row">
                                    
                                    <!-- SUBSECTION: CALCULATION OPTIONS -->
                                    <div class="col-md-6">
                                        <div class="card mb-3">
                                            <div class="card-body">
                                                
                                                <!-- Discount/Fee Options -->
                                                <div class="mb-2 form-check form-switch">
                                                    <input class="form-check-input" type="checkbox" id="apply_disc_fee" name="apply_disc_fee" value="1" {{ old('apply_disc_fee', $purchaseOrder->apply_disc_fee) ? 'checked' : '' }}>
                                                    <label class="form-check-label" for="apply_disc_fee">Gunakan Diskon/Fee</label>
                                                </div>
                                                <div class="mb-2">
                                                    <label class="form-label small mb-1">Diskon (%)</label>
                                                    <input type="number" step="any" min="0" class="form-control form-control-sm" name="disc_fee_percent" id="disc_fee_percent" placeholder="0" value="{{ old('disc_fee_percent', $purchaseOrder->disc_fee_percent) }}">
                                                </div>
                                                <div class="mb-2">
                                                    <label class="form-label small mb-1">Atau Diskon (Rp)</label>
                                                    <input type="number" step="any" min="0" class="form-control form-control-sm" name="disc_fee_amount" id="disc_fee_amount" placeholder="0" value="{{ old('disc_fee_amount', $purchaseOrder->disc_fee_amount) }}">
                                                </div>
                                                <hr/>
                                                
                                                <!-- Rounding Discount Options -->
                                                <div class="mb-2 form-check form-switch">
                                                    <input class="form-check-input" type="checkbox" id="apply_rounding_discount" name="apply_rounding_discount" value="1" {{ old('apply_rounding_discount', $purchaseOrder->apply_rounding_discount) ? 'checked' : '' }}>
                                                    <label class="form-check-label" for="apply_rounding_discount">Gunakan Diskon Pembulatan</label>
                                                </div>
                                                <div class="mb-2">
                                                    <label class="form-label small mb-1">Jumlah Pembulatan (Rp)</label>
                                                    <input type="number" step="any" min="0" class="form-control form-control-sm" name="rounding_discount_amount" id="rounding_discount_amount" placeholder="0" value="{{ old('rounding_discount_amount', $purchaseOrder->rounding_discount_amount) }}">
                                                </div>
                                                <hr/>
                                                
                                                <!-- Tax Options -->
                                                <div class="mb-2">
                                                    <label class="form-label small mb-1">Tarif Pajak (opsional)</label>
                                                    <select name="tax_id" id="tax_id" class="form-select form-select-sm">
                                                        <option value="">-- Pilih Tarif Pajak (opsional) --</option>
                                                        @foreach(\App\Models\Tax::where('is_active', true)->get() as $tax)
                                                            <option value="{{ $tax->id }}" data-rate="{{ $tax->rate }}" {{ old('tax_id', $purchaseOrder->tax_id) == $tax->id ? 'selected' : '' }}>
                                                                {{ $tax->name }} ({{ $tax->rate }}%)
                                                            </option>
                                                        @endforeach
                                                    </select>
                                                </div>
                                                <hr/>
                                                
                                                <!-- DPP Factor Options -->
                                                <div class="mb-2 form-check form-switch">
                                                    <input class="form-check-input" type="checkbox" id="use_custom_dpp_factor" name="use_custom_dpp_factor" value="1" {{ old('use_custom_dpp_factor', $purchaseOrder->use_custom_dpp_factor) ? 'checked' : '' }}>
                                                    <label class="form-check-label" for="use_custom_dpp_factor">Override Faktor DPP</label>
                                                </div>
                                                <div class="mb-2">
                                                    <label class="form-label small mb-1">Faktor DPP (mis. 11/12 atau 0.9167)</label>
                                                    <input type="text" class="form-control form-control-sm" name="custom_dpp_factor" id="custom_dpp_factor" placeholder="11/12" value="{{ old('custom_dpp_factor', $purchaseOrder->custom_dpp_factor) }}">
                                                    <small class="text-muted">Default untuk PPN Inklusif: 100/(100+Tarif PPN)</small>
                                                </div>
                                                <hr/>
                                                
                                                <!-- Shipping Options -->
                                                <div class="mb-2">
                                                    <label class="form-label small mb-1">Ongkos Kirim (Rp)</label>
                                                    <input type="number" step="any" min="0" class="form-control form-control-sm" name="shipping_amount" id="shipping_amount" value="{{ old('shipping_amount', $purchaseOrder->shipping_amount ?? 0) }}">
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    <!-- END SUBSECTION: CALCULATION OPTIONS -->

                                    <!-- SUBSECTION: SUMMARY PANEL -->
                                    <div class="col-md-6">
                                        <div class="card">
                                            <div class="card-body">
                                                <h6 class="fw-semibold">Ringkasan</h6>
                                                <div class="d-flex justify-content-between">
                                                    <div>Subtotal Barang</div>
                                                    <div id="summary-subtotal">Rp 0</div>
                                                </div>
                                                <div class="d-flex justify-content-between">
                                                    <div>Diskon/Fee</div>
                                                    <div id="summary-disc">Rp 0</div>
                                                </div>
                                                <div class="d-flex justify-content-between">
                                                    <div>Diskon Pembulatan</div>
                                                    <div id="summary-rounding">Rp 0</div>
                                                </div>
                                                <hr/>
                                                <div class="d-flex justify-content-between">
                                                    <div>Taxable Base</div>
                                                    <div id="summary-taxable">Rp 0</div>
                                                </div>
                                                <div class="d-flex justify-content-between">
                                                    <div>DPP</div>
                                                    <div id="summary-dpp">Rp 0</div>
                                                </div>
                                                <div class="d-flex justify-content-between">
                                                    <div>PPN (<span id="summary-tax-rate">0</span>%)</div>
                                                    <div id="summary-ppn">Rp 0</div>
                                                </div>
                                                <div class="d-flex justify-content-between">
                                                    <div>Ongkos Kirim</div>
                                                    <div id="summary-shipping">Rp 0</div>
                                                </div>
                                                <hr/>
                                                <div class="d-flex justify-content-between fw-bold fs-5">
                                                    <div>Grand Total</div>
                                                    <div id="summary-grand">Rp 0</div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    <!-- END SUBSECTION: SUMMARY PANEL -->
                                </div>
                            </div>
                        </div>
                        <!-- END SECTION: CALCULATION OPTIONS & SUMMARY -->
                        
                        <!-- SECTION: OVERPAYMENT HANDLING OPTIONS -->
                        <div class="row mt-4">
                            <div class="col-12">
                                <div class="card border-info shadow-sm">
                                    <div class="card-header bg-info text-white fw-semibold">
                                        Opsi Penanganan Kelebihan Bayar
                                    </div>
                                    <div class="card-body">
                                        <p class="card-text small text-muted">
                                            Jika penyesuaian ini (terutama Nota Kredit) menyebabkan kelebihan bayar pada invoice/PO yang sudah lunas, tentukan apa yang harus sistem lakukan:
                                        </p>
                                        <div class="form-check">
                                            <input class="form-check-input" type="radio" name="overpayment_action" id="overpayment_deposit" value="deposit" checked>
                                            <label class="form-check-label" for="overpayment_deposit">
                                                <strong>Simpan sebagai Deposit (Default)</strong><br>
                                                <small>Kelebihan bayar akan otomatis masuk ke saldo Deposit Klien/Supplier.</small>
                                            </label>
                                        </div>
                                        <div class="form-check mt-2">
                                            <input class="form-check-input" type="radio" name="overpayment_action" id="overpayment_refund" value="refund">
                                            <label class="form-check-label" for="overpayment_refund">
                                                <strong>Proses Pengembalian Dana (Manual Refund)</strong><br>
                                                <small>Saldo akan dibiarkan negatif (minus). Anda harus memproses pengembalian dana ini secara manual (misal: transfer balik).</small>
                                            </label>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <!-- END SECTION: OVERPAYMENT HANDLING OPTIONS -->

                        <!-- SECTION: FORM ACTIONS -->
                        <div class="d-flex justify-content-end mt-4">
                            <a href="{{ route('purchase-order-adjustments.create') }}?purchase_order_id={{ $purchaseOrder->po_id }}" class="btn btn-light me-2">Batal</a>
                            <button type="submit" class="btn btn-primary" id="submit-btn">Hitung & Simpan Koreksi</button>
                        </div>
                        <!-- END SECTION: FORM ACTIONS -->
                    </form>
                    <!-- END SECTION: ADJUSTMENT FORM -->
                </div>
                <!-- END SECTION: CARD BODY -->
            </div>
            <!-- END SECTION: CARD CONTAINER -->
        </div>
    </div>
</div>
<!-- END SECTION: MAIN CONTAINER -->

<!-- SECTION: PRODUCT ROW TEMPLATE -->
<template id="product-row-template">
    <tr>
        <td class="text-center align-middle" style="width:40px;">
            <input type="checkbox" class="row-select form-check-input">
        </td>
        <td>
            <select class="form-select form-select-sm product-select" required>
                <option value="" data-unit="-" data-default-discounts="[]" disabled selected>-- Pilih Produk --</option>
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
        <td>
            <div class="input-group input-group-sm">
                <input type="number" class="form-control quantity" value="1" min="1" step="any" required>
                <span class="input-group-text unit-display">-</span>
            </div>
        </td>
        <td>
            <input type="text" class="form-control form-control-sm purchase-price-formatted mb-1" placeholder="0">
            <div class="form-check form-check-inline">
                <input class="form-check-input update-master-price" type="checkbox" value="1">
                <label class="form-check-label small" style="font-size: 0.75rem;">Update Harga Master</label>
            </div>
        </td>
        <td>
            <div class="discount-container"></div>
            <button type="button" class="btn btn-outline-success btn-sm mt-1 add-discount-btn w-100">+ Diskon</button>
        </td>
        <td class="text-end fw-bold">
            <span class="subtotal">Rp 0</span>
        </td>
        <td class="text-center">
            <button type="button" class="btn btn-danger btn-sm remove-product-btn">
                <i class="bi bi-trash"></i>
            </button>
        </td>
    </tr>
</template>
<!-- END SECTION: PRODUCT ROW TEMPLATE -->
@endsection

@push('scripts')
<!-- SECTION: EXTERNAL JAVASCRIPT LIBRARIES -->
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/autonumeric@4.6.0/dist/autoNumeric.min.js"></script>

<script>
/**
 * SECTION: MAIN JAVASCRIPT FUNCTIONALITY
 * Inisialisasi semua komponen JavaScript setelah DOM siap
 */
document.addEventListener('DOMContentLoaded', function () {
    // SECTION: VARIABLE DECLARATIONS
    const existingPoItems = @json($purchaseOrder->items);
    
    // DOM Elements
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
    
    // Input Elements
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

    // SECTION: UTILITY FUNCTIONS
    /**
     * Format number as Indonesian Rupiah currency
     */
    function formatCurrency(n) {
        if (n === null || n === undefined) return 'Rp 0';
        return new Intl.NumberFormat('id-ID', { 
            style: 'currency', 
            currency: 'IDR', 
            minimumFractionDigits: 0 
        }).format(Math.round(n));
    }

    /**
     * Parse numeric value from formatted string for input
     */
    function parseNumericForInput(str) {
        if (!str && str !== 0) return 0;
        let s = String(str).replace(/[^\d\-\.\,]/g, '');
        s = s.replace(/\./g, '').replace(/,/g, '.');
        const v = parseFloat(s);
        return isNaN(v) ? 0 : v;
    }

    /**
     * Get all product rows from the table
     */
    function getAllRows() {
        return Array.from(productItemsContainer.querySelectorAll('tr'));
    }

    /**
     * Parse fraction or number string to float
     */
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

    // SECTION: ROW CALCULATION FUNCTIONS
    /**
     * Calculate subtotal for a single row
     */
    function calculateRowSubtotal(row) {
        const quantity = parseFloat(row.querySelector('.quantity').value) || 0;
        const price = parseFloat(row.querySelector('.purchase-price-hidden')?.value || 0);
        let finalPrice = price;
        
        // Apply all discounts in the row
        row.querySelectorAll('.discount-percentage').forEach(d => {
            const rate = parseFloat(d.value) || 0;
            if (rate > 0 && rate <= 100) finalPrice *= (1 - rate / 100);
        });
        
        row.querySelector('.subtotal').textContent = formatCurrency(quantity * finalPrice);
    }

    /**
     * Create discount input field for a row
     */
    function createDiscountInputForRow(row, value = '') {
        const index = row.dataset.index;
        const container = row.querySelector('.discount-container');
        const div = document.createElement('div');
        div.className = 'input-group input-group-sm mb-1';
        div.innerHTML = `
            <input type="number" step="any" class="form-control discount-percentage" placeholder="0" value="${value}" name="products[${index}][discounts][]">
            <button type="button" class="btn btn-outline-danger remove-discount-btn">x</button>
        `;
        
        // Remove discount button handler
        div.querySelector('.remove-discount-btn').onclick = () => { 
            div.remove(); 
            calculateRowSubtotal(row); 
            calculateTotals(); 
        };
        
        // Discount input change handler
        div.querySelector('.discount-percentage').oninput = () => { 
            calculateRowSubtotal(row); 
            calculateTotals(); 
        };
        
        container.appendChild(div);
    }

    // SECTION: PRODUCT ROW MANAGEMENT
    /**
     * Add a new product row to the table
     */
    function addProductRow(shouldCalculate = true) {
        const newRow = productRowTemplate.content.cloneNode(true).querySelector('tr');
        const rowIndex = productIndex;
        newRow.dataset.index = rowIndex;
        
        // Get DOM elements from the new row
        const productSelect = newRow.querySelector('.product-select');
        const quantityInput = newRow.querySelector('.quantity');
        const formattedPriceInput = newRow.querySelector('.purchase-price-formatted');
        const updateMasterCheckbox = newRow.querySelector('.update-master-price');
        
        // Create hidden price input
        const priceHiddenInput = document.createElement('input');
        priceHiddenInput.type = 'hidden';
        priceHiddenInput.className = 'purchase-price-hidden';
        priceHiddenInput.name = `products[${rowIndex}][price_per_unit]`;
        priceHiddenInput.value = '0';
        formattedPriceInput.parentElement.appendChild(priceHiddenInput);
        
        // Set input names
        productSelect.name = `products[${rowIndex}][product_id]`;
        quantityInput.name = `products[${rowIndex}][quantity]`;
        updateMasterCheckbox.name = `products[${rowIndex}][update_master_price]`;
        
        // Add row to table
        productItemsContainer.appendChild(newRow);
        
        // Initialize AutoNumeric for price formatting
        const anInstance = new AutoNumeric(formattedPriceInput, {
            decimalPlaces: 0,
            digitGroupSeparator: '.',
            decimalCharacter: ',',
            minimumValue: 0
        });
        autoNumericInstances.set(rowIndex, anInstance);

        // Initialize Select2 for product selection
        const select2 = $(productSelect).select2({ 
            placeholder: '-- Pilih Produk --', 
            theme: 'bootstrap-5', 
            dropdownParent: $(productSelect).parent() 
        });
        
        // Product selection handler
        select2.on('select2:select', function(e) {
            const el = e.params.data.element;
            newRow.querySelector('.unit-display').textContent = el.dataset.unit || '-';
            const defaultPrice = el.dataset.defaultPrice || 0;
            priceHiddenInput.value = defaultPrice;
            anInstance.set(defaultPrice);
            
            // Clear and recreate discounts
            newRow.querySelector('.discount-container').innerHTML = '';
            try { 
                JSON.parse(el.dataset.defaultDiscounts || '[]').forEach(d => 
                    createDiscountInputForRow(newRow, d)
                ); 
            } catch (err) {}
            
            calculateRowSubtotal(newRow);
            if (shouldCalculate) calculateTotals();
        });

        // Add discount button handler
        newRow.querySelector('.add-discount-btn').onclick = () => createDiscountInputForRow(newRow, '');
        
        // Remove product button handler
        newRow.querySelector('.remove-product-btn').onclick = () => {
            $(productSelect).select2('destroy');
            autoNumericInstances.delete(rowIndex);
            newRow.remove();
            if (shouldCalculate) calculateTotals();
        };

        // Price update handler
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

    // SECTION: TAX AND CALCULATION FUNCTIONS
    /**
     * Get selected tax rate from dropdown
     */
    function getSelectedTaxRate() {
        const opt = inputTaxId.selectedOptions[0];
        return (opt && opt.value) ? parseFloat(opt.dataset.rate) : null;
    }

    /**
     * Calculate all totals and update summary
     */
    function calculateTotals() {
        // Calculate subtotal from all rows
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
        
        // Calculate discount/fee amount
        let discFeeAmount = 0;
        if (inputApplyDiscFee.checked) {
            const percent = parseFloat(inputDiscFeePercent.value) || 0;
            const fixed = parseFloat(inputDiscFeeAmount.value) || 0;
            if (percent > 0) discFeeAmount = (percent / 100.0) * subtotalBarang;
            else if (fixed > 0) discFeeAmount = fixed;
        }
        
        // Calculate rounding amount
        const roundingAmount = inputApplyRounding.checked ? (parseFloat(inputRoundingAmount.value) || 0) : 0;
        
        // Calculate taxable base
        const taxableBase = Math.max(0, subtotalBarang - discFeeAmount - roundingAmount);
        
        // Calculate tax components
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
        
        // Calculate shipping and grand total
        const shipping = parseFloat(inputShipping.value || 0);
        const grandTotal = Math.round(taxableBase + ppn + shipping);
        
        // Update summary display
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

    // SECTION: INITIAL DATA POPULATION
    /**
     * Populate form with existing PO items
     */
    function populateExistingItems() {
        if (existingPoItems && existingPoItems.length > 0) {
            existingPoItems.forEach(item => {
                // Create new row without calculation
                const newRow = addProductRow(false);
                const productSelect = newRow.querySelector('.product-select');
                const quantityInput = newRow.querySelector('.quantity');
                const hiddenPriceInput = newRow.querySelector('.purchase-price-hidden');

                // Get AutoNumeric instance for this row
                const anInstance = autoNumericInstances.get(parseInt(newRow.dataset.index));

                // Set data from PO item
                hiddenPriceInput.value = item.price_per_unit;

                // Set AutoNumeric value
                if (anInstance) {
                    anInstance.set(item.price_per_unit);
                }

                // Set quantity
                quantityInput.value = item.quantity;

                // Set Select2 dropdown with silent trigger
                $(productSelect).val(item.product_id).trigger('change.select2');

                // Set unit data
                const selectedOption = productSelect.options[productSelect.selectedIndex];
                if (selectedOption) {
                    newRow.querySelector('.unit-display').textContent = selectedOption.dataset.unit || '-';
                }

                // Set discounts
                newRow.querySelector('.discount-container').innerHTML = '';
                if (item.discounts && item.discounts.length > 0) {
                    item.discounts.forEach(discount => 
                        createDiscountInputForRow(newRow, discount.percentage)
                    );
                }

                // Calculate row subtotal
                calculateRowSubtotal(newRow);
            });
        } else {
            // Add empty row if no items exist
            addProductRow(true);
        }

        // Calculate overall totals after all rows are added
        calculateTotals();
    }

    // SECTION: EVENT LISTENERS SETUP
    /**
     * Setup all event listeners for the form
     */
    function setupEventListeners() {
        // Row selection handlers
        headerRowSelect.onchange = (e) => 
            getAllRows().forEach(r => r.querySelector('.row-select').checked = e.target.checked);
        
        selectAllBtn.onclick = () => 
            getAllRows().forEach(r => r.querySelector('.row-select').checked = true);
        
        deselectAllBtn.onclick = () => 
            getAllRows().forEach(r => r.querySelector('.row-select').checked = false);

        // Bulk discount application
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
        
        // Add product button
        addProductBtn.onclick = () => addProductRow();

        // Calculation triggers
        const calculationInputs = [
            inputApplyDiscFee, inputDiscFeePercent, inputDiscFeeAmount, 
            inputApplyRounding, inputRoundingAmount, inputUseCustomDpp, 
            inputCustomDppFactor, inputTaxId, inputShipping
        ];
        
        calculationInputs.forEach(el => {
            el.addEventListener('input', calculateTotals);
            el.addEventListener('change', calculateTotals);
        });

        // Form submission handler
        form.addEventListener('submit', (e) => {
            if (getAllRows().length === 0) { 
                e.preventDefault(); 
                alert('Harap tambahkan setidaknya satu item produk.'); 
                return; 
            }
            inputCustomDppFactor.value = parseFractionOrNumber(inputCustomDppFactor.value);
        });
    }

    // SECTION: INITIALIZATION
    /**
     * Initialize the page functionality
     */
    function initializePage() {
        setupEventListeners();
        populateExistingItems();
    }

    // Start the application
    initializePage();
});
</script>
@endpush
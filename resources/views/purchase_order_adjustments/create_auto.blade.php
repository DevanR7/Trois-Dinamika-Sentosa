@extends('layouts.app')

@push('styles')
<link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/select2-bootstrap-5-theme@1.3.0/dist/select2-bootstrap-5-theme.min.css" />
@endpush

@section('content')
<div class="container py-4">
    <div class="row justify-content-center">
        <div class="col-lg-10">
            <div class="card shadow-sm border-0">
                {{-- 1. JUDUL --}}
                <div class="card-header bg-primary text-white">
                    <h4 class="mb-0">Koreksi Otomatis untuk PO: {{ $purchaseOrder->po_number ?? 'PO-'.$purchaseOrder->po_id }}</h4>
                </div>

                <div class="card-body p-4">
                    @if ($errors->any())
                        <div class="alert alert-danger"><ul class="mb-0">@foreach ($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>
                    @endif
                    
                    <div class="alert alert-warning">
                        <strong>Perhatian!</strong> Anda sedang dalam mode "Koreksi Otomatis". Mengubah data di bawah ini **tidak akan** mengubah PO asli. Sistem akan **menghitung selisih** antara total lama (Rp {{ number_format($purchaseOrder->total_amount, 0, ',', '.') }}) dengan total baru yang Anda buat, lalu membuat Nota Kredit/Debit PO secara otomatis.
                    </div>

                    {{-- 2. FORM ACTION --}}
                    <form action="{{ route('purchase-order-adjustments.store.auto', $purchaseOrder->po_id) }}" method="POST" id="po-form">
                        @csrf
                        {{-- (Tidak perlu @method('PUT')) --}}

                        {{-- Header Form (READONLY) --}}
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

                        {{-- Kontrol Aksi Massal (Bulk Actions) --}}
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

                        {{-- Tabel Item dan Catatan --}}
                        <div class="row">
                            <div class="col-12">
                                <h5 class="fw-semibold mb-3">Rincian Item</h5>
                                <div class="table-responsive">
                                    <table class="table table-bordered align-middle">
                                        <thead class="table-light">
                                            <tr>
                                                <th class="text-center" style="width:40px;"><input type="checkbox" class="form-check-input" id="header-row-select"></th>
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
                                    <button type="button" id="add-product-btn" class="btn btn-secondary btn-sm"><i class="bi bi-plus-circle me-1"></i> Tambah Item</button>
                                </div>
                                <div class="mb-3 mt-4">
                                    <label for="notes" class="form-label fw-semibold">Alasan Koreksi (Wajib Diisi)</label>
                                    <textarea class="form-control" name="notes" id="notes" rows="3" placeholder="Contoh: Koreksi salah input diskon supplier" required>{{ old('notes') }}</textarea>
                                </div>
                            </div>
                        </div>

                        {{-- Opsi Perhitungan & Ringkasan --}}
                        <div class="row mt-4">
                            <div class="col-12">
                                <h5 class="fw-semibold mb-3">Opsi Perhitungan & Ringkasan</h5>
                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="card mb-3">
                                            <div class="card-body">
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
                                                <div class="mb-2 form-check form-switch">
                                                    <input class="form-check-input" type="checkbox" id="apply_rounding_discount" name="apply_rounding_discount" value="1" {{ old('apply_rounding_discount', $purchaseOrder->apply_rounding_discount) ? 'checked' : '' }}>
                                                    <label class="form-check-label" for="apply_rounding_discount">Gunakan Diskon Pembulatan</label>
                                                </div>
                                                <div class="mb-2">
                                                    <label class="form-label small mb-1">Jumlah Pembulatan (Rp)</label>
                                                    <input type="number" step="any" min="0" class="form-control form-control-sm" name="rounding_discount_amount" id="rounding_discount_amount" placeholder="0" value="{{ old('rounding_discount_amount', $purchaseOrder->rounding_discount_amount) }}">
                                                </div>
                                                <hr/>
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
                                                <div class="mb-2">
                                                    <label class="form-label small mb-1">Ongkos Kirim (Rp)</label>
                                                    <input type="number" step="any" min="0" class="form-control form-control-sm" name="shipping_amount" id="shipping_amount" value="{{ old('shipping_amount', $purchaseOrder->shipping_amount ?? 0) }}">
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="card">
                                            <div class="card-body">
                                                <h6 class="fw-semibold">Ringkasan</h6>
                                                <div class="d-flex justify-content-between"><div>Subtotal Barang</div><div id="summary-subtotal">Rp 0</div></div>
                                                <div class="d-flex justify-content-between"><div>Diskon/Fee</div><div id="summary-disc">Rp 0</div></div>
                                                <div class="d-flex justify-content-between"><div>Diskon Pembulatan</div><div id="summary-rounding">Rp 0</div></div>
                                                <hr/>
                                                <div class="d-flex justify-content-between"><div>Taxable Base</div><div id="summary-taxable">Rp 0</div></div>
                                                <div class="d-flex justify-content-between"><div>DPP</div><div id="summary-dpp">Rp 0</div></div>
                                                <div class="d-flex justify-content-between"><div>PPN (<span id="summary-tax-rate">0</span>%)</div><div id="summary-ppn">Rp 0</div></div>
                                                <div class="d-flex justify-content-between"><div>Ongkos Kirim</div><div id="summary-shipping">Rp 0</div></div>
                                                <hr/>
                                                <div class="d-flex justify-content-between fw-bold fs-5"><div>Grand Total</div><div id="summary-grand">Rp 0</div></div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="d-flex justify-content-end mt-4">
                            <a href="{{ route('purchase-order-adjustments.create') }}?purchase_order_id={{ $purchaseOrder->po_id }}" class="btn btn-light me-2">Batal</a>
                            {{-- ✅ 3. TOMBOL DIUBAH --}}
                            <button type="submit" class="btn btn-primary" id="submit-btn">Hitung & Simpan Koreksi</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

{{-- Template Row (Salin dari edit.blade.php Anda) --}}
<template id="product-row-template">
    <tr>
        <td class="text-center align-middle" style="width:40px;"><input type="checkbox" class="row-select form-check-input"></td>
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
        <td class="text-end fw-bold"><span class="subtotal">Rp 0</span></td>
        <td class="text-center"><button type="button" class="btn btn-danger btn-sm remove-product-btn"><i class="bi bi-trash"></i></button></td>
    </tr>
</template>
@endsection

@push('scripts')
{{-- ====================================================== --}}
{{-- ✅ BLOK SCRIPT YANG BENAR (dari edit.blade.php) --}}
{{-- ====================================================== --}}
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/autonumeric@4.6.0/dist/autoNumeric.min.js"></script>

<script>
document.addEventListener('DOMContentLoaded', function () {
    const existingPoItems = @json($purchaseOrder->items);

    // Deklarasi semua variabel elemen
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
    
    // Inisialisasi AutoNumeric
    const autoNumericInstances = new Map();

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
        div.className = 'input-group input-group-sm mb-1';
        div.innerHTML = `<input type="number" step="any" class="form-control discount-percentage" placeholder="0" value="${value}" name="products[${index}][discounts][]"><button type="button" class="btn btn-outline-danger remove-discount-btn">x</button>`;
        div.querySelector('.remove-discount-btn').onclick = () => { div.remove(); calculateRowSubtotal(row); calculateTotals(); };
        div.querySelector('.discount-percentage').oninput = () => { calculateRowSubtotal(row); calculateTotals(); };
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
        
        const anInstance = new AutoNumeric(formattedPriceInput, {
            decimalPlaces: 0,
            digitGroupSeparator: '.',
            decimalCharacter: ',',
            minimumValue: 0
        });
        autoNumericInstances.set(rowIndex, anInstance);

        const select2 = $(productSelect).select2({ placeholder: '-- Pilih Produk --', theme: 'bootstrap-5', dropdownParent: $(productSelect).parent() });
        
        // Listener saat user memilih produk BARU
        select2.on('select2:select', function(e) {
            const el = e.params.data.element;
            newRow.querySelector('.unit-display').textContent = el.dataset.unit || '-';
            const defaultPrice = el.dataset.defaultPrice || 0;
            priceHiddenInput.value = defaultPrice;
            anInstance.set(defaultPrice); // Set nilai ke AutoNumeric
            
            newRow.querySelector('.discount-container').innerHTML = '';
            try { JSON.parse(el.dataset.defaultDiscounts || '[]').forEach(d => createDiscountInputForRow(newRow, d)); } catch (err) {}
            
            calculateRowSubtotal(newRow);
            if (shouldCalculate) calculateTotals();
        });

        newRow.querySelector('.add-discount-btn').onclick = () => createDiscountInputForRow(newRow, '');
        newRow.querySelector('.remove-product-btn').onclick = () => {
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
        
        quantityInput.oninput = () => { calculateRowSubtotal(newRow); if (shouldCalculate) calculateTotals(); };
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
            const fixed = parseFloat(inputDiscFeeAmount.value) || 0;
            if (percent > 0) discFeeAmount = (percent / 100.0) * subtotalBarang;
            else if (fixed > 0) discFeeAmount = fixed;
        }
        const roundingAmount = inputApplyRounding.checked ? (parseFloat(inputRoundingAmount.value) || 0) : 0;
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
        const shipping = parseFloat(inputShipping.value || 0);
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

    // ✅ FUNGSI 'populateExistingItems' YANG BENAR
    function populateExistingItems() {
    if (existingPoItems && existingPoItems.length > 0) {
        existingPoItems.forEach(item => {
            // 1. Buat baris baru (tanpa kalkulasi)
            const newRow = addProductRow(false);
            const productSelect = newRow.querySelector('.product-select');
            const quantityInput = newRow.querySelector('.quantity');
            const hiddenPriceInput = newRow.querySelector('.purchase-price-hidden');

            // 2. Ambil instance AutoNumeric untuk baris ini
            const anInstance = autoNumericInstances.get(parseInt(newRow.dataset.index));

            // 3. Set data dari PO item (HARUS SEBELUM TRIGGER)
            hiddenPriceInput.value = item.price_per_unit;

            // 4. Set nilai AutoNumeric (harga per unit)
            if (anInstance) {
                anInstance.set(item.price_per_unit);
            }

            // 5. Set kuantitas
            quantityInput.value = item.quantity;

            // 6. Set dropdown Select2 menggunakan trigger "silent"
            $(productSelect).val(item.product_id).trigger('change.select2');

            // 7. Set data unit (karena trigger silent tidak mengatur unit)
            const selectedOption = productSelect.options[productSelect.selectedIndex];
            if (selectedOption) {
                newRow.querySelector('.unit-display').textContent = selectedOption.dataset.unit || '-';
            }

            // 8. Set diskon (karena trigger silent tidak mengatur diskon)
            newRow.querySelector('.discount-container').innerHTML = ''; // Hapus diskon default
            if (item.discounts && item.discounts.length > 0) {
                item.discounts.forEach(discount => createDiscountInputForRow(newRow, discount.percentage));
            }

            // 9. Hitung subtotal baris
            calculateRowSubtotal(newRow);
        });
    } else {
        // Jika tidak ada item, tambahkan baris kosong
        addProductRow(true);
    }

    // 10. Hitung total keseluruhan setelah semua baris ditambahkan
    calculateTotals();
}


    // Event Listeners
    headerRowSelect.onchange = (e) => getAllRows().forEach(r => r.querySelector('.row-select').checked = e.target.checked);
    selectAllBtn.onclick = () => getAllRows().forEach(r => r.querySelector('.row-select').checked = true);
    deselectAllBtn.onclick = () => getAllRows().forEach(r => r.querySelector('.row-select').checked = false);
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
    addProductBtn.onclick = () => addProductRow(); // Panggil addProductRow tanpa argumen
    
    [inputApplyDiscFee, inputDiscFeePercent, inputDiscFeeAmount, inputApplyRounding, inputRoundingAmount, inputUseCustomDpp, inputCustomDppFactor, inputTaxId, inputShipping].forEach(el => {
        el.addEventListener('input', calculateTotals);
        el.addEventListener('change', calculateTotals);
    });
    
    form.addEventListener('submit', (e) => {
        if (getAllRows().length === 0) { e.preventDefault(); alert('Harap tambahkan setidaknya satu item produk.'); return; }
        inputCustomDppFactor.value = parseFractionOrNumber(inputCustomDppFactor.value);
    });

    // Inisialisasi Halaman
    populateExistingItems();
});
</script>
@endpush
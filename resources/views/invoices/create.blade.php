@extends('layouts.app')

@section('styles')
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" />
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/select2-bootstrap-5-theme@1.3.0/dist/select2-bootstrap-5-theme.min.css" />
@endsection

@section('content')
<div class="container-fluid py-2">
    
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h3 class="fw-bold text-dark mb-1">Buat Invoice Baru</h3>
            <p class="text-muted mb-0 small">Buat tagihan penjualan untuk pelanggan.</p>
        </div>
        <div>
            <a href="{{ route('invoices.index') }}" class="btn btn-outline-secondary btn-sm">
                <i class="bi bi-arrow-left me-1"></i> Kembali
            </a>
        </div>
    </div>

    <div class="row justify-content-center">
        <div class="col-lg-10">
            
            @if ($errors->any())
                <div class="alert alert-danger border-0 shadow-sm rounded-3 mb-4">
                    <ul class="mb-0 small ps-3">
                        @foreach ($errors->all() as $error)<li>{{ $error }}</li>@endforeach
                    </ul>
                </div>
            @endif

            <form action="{{ route('invoices.store') }}" method="POST" id="invoice-form">
                @csrf
                @if (isset($order))
                    <input type="hidden" name="sales_order_id" value="{{ $order->order_id }}">
                @endif
                
                <div class="card card-transaction border-0 shadow-sm">
                    <div class="card-header bg-white p-4 border-bottom">
                        <div class="form-section-title mb-0"><i class="bi bi-receipt"></i> Data Tagihan</div>
                    </div>
                    
                    <div class="card-body p-4">
                        
                        {{-- 1. HEADER --}}
                        <div class="row g-3 mb-4">
                            <div class="col-md-4">
                                <label for="client_id" class="form-label fw-bold small text-muted">PELANGGAN</label>
                                <select name="client_id" id="client_id" class="form-select" required>
                                    <option value="" disabled selected>-- Pilih Klien --</option>
                                    @foreach ($clients as $client)
                                        <option value="{{ $client->client_id }}" @selected(old("client_id", isset($order) ? $order->client_id : '') == $client->client_id)>
                                            {{ $client->client_name }}
                                        </option>
                                    @endforeach
                                </select>
                                @if (isset($order)) <input type="hidden" name="client_id" value="{{ $order->client_id }}" /> @endif
                            </div>
                            <div class="col-md-4">
                                <label for="order_date" class="form-label fw-bold small text-muted">TANGGAL INVOICE</label>
                                <input type="date" class="form-control" id="order_date" name="order_date" value="{{ old("order_date", now()->format("Y-m-d")) }}" required>
                            </div>
                            <div class="col-md-4">
                                <label for="due_date" class="form-label fw-bold small text-muted">JATUH TEMPO</label>
                                <input type="date" class="form-control" id="due_date" name="due_date" value="{{ old("due_date", now()->addDays(30)->format("Y-m-d")) }}" required>
                            </div>
                            <div class="col-md-4">
                                <label for="user_id_sales" class="form-label fw-bold small text-muted">SALES (OPSIONAL)</label>
                                <select name="user_id_sales" id="user_id_sales" class="form-select">
                                    <option value="">-- Umum / Tanpa Sales --</option>
                                    @foreach ($salesUsers as $sales)
                                        <option value="{{ $sales->user_id }}" @selected(old('user_id_sales', isset($order) ? $order->user_id_sales : null) == $sales->user_id)>
                                            {{ $sales->full_name }} ({{ $sales->sales_code }})
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                        </div>

                        <hr class="border-dashed">

                        {{-- 2. TABEL ITEM --}}
                        <h6 class="fw-bold text-dark mb-3">Rincian Produk</h6>
                        <div class="table-responsive mb-3">
                            <table class="table table-hover table-transaction align-middle mb-0">
                                <thead class="bg-light">
                                    <tr>
                                        <th style="width: 35%">Produk</th>
                                        <th style="width: 10%" class="text-center">Qty</th>
                                        <th style="width: 25%">Harga Satuan (Rp)</th> 
                                        <th class="text-end" style="width: 20%">Subtotal</th>
                                        <th class="text-center" style="width: 10%"></th>
                                    </tr>
                                </thead>
                                <tbody id="product-items"></tbody>
                            </table>
                        </div>

                        {{-- TOMBOL TAMBAH PRODUK (POSISI DI BAWAH TABEL) --}}
                        <button type="button" id="add-product-btn" class="btn btn-primary btn-sm rounded-pill px-3 mb-4">
                            <i class="bi bi-plus-lg me-1"></i> Tambah Baris Produk
                        </button>

                        <hr class="border-dashed my-4">

                        {{-- 3. BIAYA TAMBAHAN --}}
                        <h6 class="fw-bold text-dark mb-3">Biaya Tambahan (Opsional)</h6>
                        <div class="table-responsive mb-2">
                            <table class="table table-bordered table-sm mb-0">
                                <tbody id="additional-cost-items"></tbody>
                            </table>
                        </div>
                        <button type="button" id="add-cost-btn" class="btn btn-outline-secondary btn-sm rounded-pill px-3 mb-4">
                            <i class="bi bi-cash-stack me-1"></i> Tambah Biaya Lain
                        </button>

                        {{-- 4. RINGKASAN TOTAL --}}
                        <div class="row g-4">
                            <div class="col-md-6">
                                <div class="mb-4">
                                    <label for="notes" class="form-label fw-bold small text-muted">CATATAN</label>
                                    <textarea class="form-control bg-light" id="notes" name="notes" rows="3" placeholder="Catatan untuk klien...">{{ old('notes') }}</textarea>
                                </div>
                                
                                <div class="card bg-light border-0 p-3">
                                    <h6 class="fw-bold small text-uppercase mb-3">Pajak</h6>
                                    <div id="tax-options">
                                        @forelse ($taxes as $tax)
                                            <div class="form-check mb-1">
                                                <input class="form-check-input tax-checkbox cursor-pointer" type="checkbox" name="taxes[]" value="{{ $tax->id }}" id="tax{{ $tax->id }}" data-rate="{{ $tax->rate }}">
                                                <label class="form-check-label cursor-pointer" for="tax{{ $tax->id }}">{{ $tax->name }} ({{ $tax->rate }}%)</label>
                                            </div>
                                        @empty
                                            <p class="text-muted small fst-italic">Tidak ada data pajak aktif.</p>
                                        @endforelse
                                    </div>
                                </div>
                            </div>

                            <div class="col-md-6">
                                <div class="card border">
                                    <div class="card-header bg-light fw-bold py-2">Ringkasan Pembayaran</div>
                                    <div class="card-body">
                                        <div class="d-flex justify-content-between mb-2">
                                            <span class="text-muted">Subtotal Produk</span>
                                            <span id="subtotal-display" class="fw-medium text-dark">Rp 0</span>
                                        </div>
                                        
                                        <div class="row align-items-center mb-2">
                                            <div class="col-7">
                                                <label for="discount_percentage" class="form-label mb-0 small text-muted">Diskon Global (%)</label>
                                            </div>
                                            <div class="col-5">
                                                {{-- ✅ UPDATE: Default value 60 --}}
                                                <input type="number" step="any" class="form-control form-control-sm text-end fw-bold text-danger" name="discount_percentage" id="discount_percentage" value="{{ old('discount_percentage', 60) }}" min="0" max="100">
                                            </div>
                                        </div>

                                        <div class="d-flex justify-content-between mb-2 text-danger">
                                            <span>Potongan Diskon</span>
                                            <span id="discount-amount-display">Rp 0</span>
                                        </div>
                                        
                                        <hr class="my-2 border-dashed">
                                        
                                        <div class="d-flex justify-content-between mb-2 fw-bold text-dark">
                                            <span>Subtotal (Setelah Diskon)</span>
                                            <span id="subtotal-after-discount">Rp 0</span>
                                        </div>
                                        
                                        <div id="tax-breakdown" class="text-muted small mb-2"></div>
                                        
                                        <div class="d-flex justify-content-between mb-2 text-secondary">
                                            <span>Biaya Tambahan</span>
                                            <span id="total-additional-display">Rp 0</span>
                                        </div>
                                        
                                        <hr class="my-2" />
                                        
                                        <div class="d-flex justify-content-between align-items-center">
                                            <h5 class="fw-bold mb-0 text-dark">TOTAL AKHIR</h5>
                                            <h4 class="fw-bold text-primary mb-0" id="grand-total">Rp 0</h4>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="d-flex justify-content-end mt-4 pt-3 border-top">
                            <a href="{{ route('invoices.index') }}" class="btn btn-light border me-2">Batal</a>
                            <button type="submit" class="btn btn-primary px-4 fw-bold">Simpan Invoice</button>
                        </div>

                    </div>
                </div>
            </form>
        </div>
    </div>
</div>

{{-- TEMPLATES --}}
<template id="product-row-template">
    <tr>
        <td>
            <select class="form-select form-select-sm product-select" required>
                <option value="" data-price="0" disabled selected>-- Pilih Produk --</option>
                @foreach ($products as $product)
                    <option value="{{ $product->product_id }}" data-price="{{ $product->selling_price ?? 0 }}"> {{ $product->product_name }} </option>
                @endforeach
            </select>
        </td>
        <td><input type="number" class="form-control form-control-sm quantity text-center" value="1" min="1" required /></td>
        <td>
            <input type="text" class="form-control form-control-sm price-display text-end" required placeholder="0" />
            <input type="hidden" class="price-raw" value="0" />
            <div class="form-check mt-1 small">
                <input class="form-check-input update-master-check" type="checkbox" value="1">
                <label class="form-check-label text-muted">Update Master Harga</label>
            </div>
        </td>
        <td class="text-end align-middle fw-semibold"><span class="subtotal">Rp 0</span></td>
        <td class="text-center align-middle">
            <button type="button" class="btn btn-link text-danger btn-sm remove-product-btn p-0"><i class="bi bi-trash-fill"></i></button>
        </td>
    </tr>
</template>

<template id="additional-cost-template">
    <tr>
        <td><input type="text" class="form-control form-control-sm cost-desc" placeholder="Keterangan Biaya" required></td>
        <td>
            <input type="text" class="form-control form-control-sm cost-display text-end" required placeholder="0" />
            <input type="hidden" class="cost-raw" value="0" />
        </td>
        <td class="text-center align-middle"><button type="button" class="btn btn-link text-danger btn-sm remove-cost-btn p-0"><i class="bi bi-trash-fill"></i></button></td>
    </tr>
</template>

@endsection

@push("scripts")
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/autonumeric@4.6.0/dist/autoNumeric.min.js"></script> 
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<script>
document.addEventListener('DOMContentLoaded', function () {
    $('#client_id').select2({ theme: 'bootstrap-5', placeholder: '-- Pilih Klien --', width: '100%' });
    $('#user_id_sales').select2({ theme: 'bootstrap-5', placeholder: '-- Pilih Sales --', width: '100%' });

    // Pre-fill Order Data
    const orderItems = @json(isset($order) ? $order->items : []);
    const orderClientId = @json(isset($order) ? $order->client_id : null);
    if (orderClientId) { $('#client_id').val(orderClientId).trigger('change'); }
    
    // Elements
    const productItemsContainer = document.getElementById('product-items');
    const additionalCostItemsContainer = document.getElementById('additional-cost-items');
    const productRowTemplate = document.getElementById('product-row-template');
    const costRowTemplate = document.getElementById('additional-cost-template');
    const taxOptionsContainer = document.getElementById('tax-options');
    const discountInput = document.getElementById('discount_percentage');
    
    let productIndex = 0;
    let costIndex = 0;

    function formatRupiah(number) {
        return new Intl.NumberFormat('id-ID', { style: 'currency', currency: 'IDR', minimumFractionDigits: 0 }).format(number);
    }

    function initAutoNumeric(element, hiddenElement) {
        const an = new AutoNumeric(element, { decimalCharacter: ',', digitGroupSeparator: '.', decimalPlaces: 0, minimumValue: '0', unformatOnSubmit: true });
        element.addEventListener('autoNumeric:rawValueModified', e => {
            hiddenElement.value = e.detail.newRawValue;
            calculateTotals();
            triggerAutoSave();
        });
        return an;
    }

    function calculateTotals() {
        let subtotalProducts = 0;
        productItemsContainer.querySelectorAll('tr').forEach((row) => {
            const price = parseFloat(row.querySelector('.price-raw').value) || 0;
            const quantity = parseInt(row.querySelector('.quantity').value) || 0;
            const subtotal = price * quantity;
            row.querySelector('.subtotal').textContent = formatRupiah(subtotal);
            subtotalProducts += subtotal;
        });

        let totalAdditionalCosts = 0;
        additionalCostItemsContainer.querySelectorAll('tr').forEach((row) => {
            const amount = parseFloat(row.querySelector('.cost-raw').value) || 0;
            totalAdditionalCosts += amount;
        });

        const discountRate = parseFloat(discountInput.value) || 0;
        const discountAmount = subtotalProducts * (discountRate / 100);
        const subtotalAfterDiscount = subtotalProducts - discountAmount;

        let totalTaxAmount = 0;
        let taxHtml = '';
        taxOptionsContainer.querySelectorAll('.tax-checkbox:checked').forEach((checkbox) => {
            const rate = parseFloat(checkbox.dataset.rate) || 0;
            const name = checkbox.nextElementSibling.textContent.trim();
            const taxAmount = subtotalAfterDiscount * (rate / 100);
            totalTaxAmount += taxAmount;
            taxHtml += `<div class="d-flex justify-content-between mb-1 text-muted small"><span>+ ${name}</span> <span>${formatRupiah(taxAmount)}</span></div>`;
        });
        
        const grandTotal = subtotalAfterDiscount + totalTaxAmount + totalAdditionalCosts;

        document.getElementById('subtotal-display').textContent = formatRupiah(subtotalProducts);
        document.getElementById('discount-amount-display').textContent = `(-) ${formatRupiah(discountAmount)}`;
        document.getElementById('subtotal-after-discount').textContent = formatRupiah(subtotalAfterDiscount);
        document.getElementById('tax-breakdown').innerHTML = taxHtml;
        document.getElementById('total-additional-display').textContent = `(+) ${formatRupiah(totalAdditionalCosts)}`;
        document.getElementById('grand-total').textContent = formatRupiah(grandTotal);
    }

    function addProductRow(item = null) {
        const newRow = productRowTemplate.content.cloneNode(true).querySelector('tr');
        const productSelect = newRow.querySelector('.product-select');
        const quantityInput = newRow.querySelector('.quantity');
        const priceDisplayInput = newRow.querySelector('.price-display');
        const priceRawInput = newRow.querySelector('.price-raw');
        const updateMasterCheck = newRow.querySelector('.update-master-check');
        
        productSelect.name = `products[${productIndex}][product_id]`;
        quantityInput.name = `products[${productIndex}][quantity]`;
        priceRawInput.name = `products[${productIndex}][custom_price]`; 
        updateMasterCheck.name = `products[${productIndex}][update_master_price]`;
        
        const checkId = `update_master_${productIndex}`;
        updateMasterCheck.id = checkId;
        newRow.querySelector('label.form-check-label').setAttribute('for', checkId);
        
        productItemsContainer.appendChild(newRow);

        const select2 = $(productSelect).select2({ theme: 'bootstrap-5', dropdownParent: $(productSelect).parent(), placeholder: '-- Cari Produk --', width: '100%' });
        const anPrice = initAutoNumeric(priceDisplayInput, priceRawInput);

        select2.on('change', function() {
            const selectedOption = this.options[this.selectedIndex];
            const basePrice = parseFloat(selectedOption.dataset.price) || 0;
            if(priceRawInput.value == 0) {
                anPrice.set(basePrice);
                priceRawInput.value = basePrice;
            }
            calculateTotals();
            triggerAutoSave();
        });

        quantityInput.addEventListener('input', () => { calculateTotals(); triggerAutoSave(); });
        updateMasterCheck.addEventListener('change', triggerAutoSave);

        newRow.querySelector('.remove-product-btn').addEventListener('click', () => {
            select2.select2('destroy');
            newRow.remove();
            calculateTotals();
            triggerAutoSave();
        });
        
        productIndex++;
        return { newRow, anPrice, priceRawInput, productSelect, quantityInput, updateMasterCheck }; 
    }

    function addCostRow(cost = null) {
        const newRow = costRowTemplate.content.cloneNode(true).querySelector('tr');
        const descInput = newRow.querySelector('.cost-desc');
        const costDisplayInput = newRow.querySelector('.cost-display');
        const costRawInput = newRow.querySelector('.cost-raw');

        descInput.name = `additional_costs[${costIndex}][description]`;
        costRawInput.name = `additional_costs[${costIndex}][amount]`;

        additionalCostItemsContainer.appendChild(newRow);
        initAutoNumeric(costDisplayInput, costRawInput);

        descInput.addEventListener('input', triggerAutoSave);
        newRow.querySelector('.remove-cost-btn').addEventListener('click', () => {
            newRow.remove();
            calculateTotals();
            triggerAutoSave();
        });
        costIndex++;
        return { newRow, descInput, costRawInput, costDisplayInput };
    }

    document.getElementById('add-product-btn').addEventListener('click', () => addProductRow());
    document.getElementById('add-cost-btn').addEventListener('click', () => addCostRow());
    taxOptionsContainer.addEventListener('change', () => { calculateTotals(); triggerAutoSave(); });
    discountInput.addEventListener('input', () => { calculateTotals(); triggerAutoSave(); });
    
    $('#client_id, #user_id_sales').on('change', triggerAutoSave);
    $('#order_date, #due_date, #notes').on('input', triggerAutoSave);

    // --- DRAFT & INIT ---
    const STORAGE_KEY = 'invoice_draft_v3'; // Updated key
    let saveTimeout;

    function triggerAutoSave() { clearTimeout(saveTimeout); saveTimeout = setTimeout(saveDraft, 1000); }

    function saveDraft() {
        @if(isset($invoice)) return; @endif
        const data = {
            client_id: $('#client_id').val(),
            order_date: document.getElementById('order_date').value,
            due_date: document.getElementById('due_date').value,
            user_id_sales: $('#user_id_sales').val(),
            notes: document.getElementById('notes').value,
            discount_percentage: document.getElementById('discount_percentage').value,
            taxes: [], products: [], additional_costs: []
        };
        document.querySelectorAll('.tax-checkbox:checked').forEach(cb => data.taxes.push(cb.value));
        document.querySelectorAll('#product-items tr').forEach(row => {
            const prodId = $(row.querySelector('.product-select')).val();
            if(prodId) {
                data.products.push({
                    product_id: prodId, quantity: row.querySelector('.quantity').value,
                    price: row.querySelector('.price-raw').value, update_master: row.querySelector('.update-master-check').checked
                });
            }
        });
        document.querySelectorAll('#additional-cost-items tr').forEach(row => {
            data.additional_costs.push({
                description: row.querySelector('.cost-desc').value, amount: row.querySelector('.cost-raw').value
            });
        });
        localStorage.setItem(STORAGE_KEY, JSON.stringify(data));
    }

    function loadDraft() {
        @if(isset($order) || isset($invoice)) return; @endif
        const rawData = localStorage.getItem(STORAGE_KEY);
        if (!rawData) { addProductRow(); return; }

        Swal.fire({
            title: 'Lanjutkan Draft?', text: 'Ditemukan invoice yang belum tersimpan.', icon: 'info',
            showCancelButton: true, confirmButtonText: 'Ya, Pulihkan', cancelButtonText: 'Buat Baru', confirmButtonColor: '#0d6efd'
        }).then((result) => {
            if (result.isConfirmed) { applyDraft(JSON.parse(rawData)); } else { localStorage.removeItem(STORAGE_KEY); addProductRow(); }
        });
    }

    function applyDraft(data) {
        if(data.client_id) $('#client_id').val(data.client_id).trigger('change');
        if(data.order_date) document.getElementById('order_date').value = data.order_date;
        if(data.due_date) document.getElementById('due_date').value = data.due_date;
        if(data.user_id_sales) $('#user_id_sales').val(data.user_id_sales).trigger('change');
        if(data.notes) document.getElementById('notes').value = data.notes;
        
        // ✅ Default 60 jika null
        document.getElementById('discount_percentage').value = (data.discount_percentage !== undefined && data.discount_percentage !== null) ? data.discount_percentage : 60;

        if(data.taxes) { data.taxes.forEach(id => { const cb = document.getElementById('tax' + id); if(cb) cb.checked = true; }); }

        productItemsContainer.innerHTML = ''; productIndex = 0;
        if (data.products && data.products.length > 0) {
            data.products.forEach(item => {
                const { newRow, anPrice, priceRawInput, productSelect, quantityInput, updateMasterCheck } = addProductRow();
                $(productSelect).val(item.product_id).trigger('change');
                setTimeout(() => { anPrice.set(item.price); priceRawInput.value = item.price; calculateTotals(); }, 50);
                quantityInput.value = item.quantity; updateMasterCheck.checked = item.update_master;
            });
        } else { addProductRow(); }

        additionalCostItemsContainer.innerHTML = ''; costIndex = 0;
        if (data.additional_costs && data.additional_costs.length > 0) {
            data.additional_costs.forEach(cost => {
                const { descInput, costRawInput, costDisplayInput } = addCostRow();
                descInput.value = cost.description; costRawInput.value = cost.amount;
                if(AutoNumeric.getAutoNumericElement(costDisplayInput)) AutoNumeric.getAutoNumericElement(costDisplayInput).set(cost.amount);
            });
        }
        calculateTotals();
        const Toast = Swal.mixin({ toast: true, position: 'top-end', showConfirmButton: false, timer: 3000, timerProgressBar: true });
        Toast.fire({ icon: 'success', title: 'Draft berhasil dipulihkan' });
    }

    document.getElementById('invoice-form').addEventListener('submit', function() { localStorage.removeItem(STORAGE_KEY); });

    if (orderItems.length > 0) {
        orderItems.forEach(item => {
            const { newRow, anPrice, priceRawInput, productSelect, quantityInput } = addProductRow();
            $(productSelect).val(item.product_id).trigger('change');
            quantityInput.value = item.quantity;
        });
        calculateTotals();
    } else {
        loadDraft();
    }
});
</script>
@endpush
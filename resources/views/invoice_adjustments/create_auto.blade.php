@extends('layouts.app')

@section('styles')
{{-- Stylesheet untuk Select2 --}}
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" />
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/select2-bootstrap-5-theme@1.3.0/dist/select2-bootstrap-5-theme.min.css" />
@endsection

@section('content')
<div class="container-fluid py-2">
    
    {{-- HEADER --}}
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h3 class="fw-bold text-dark mb-1">Koreksi Otomatis Invoice</h3>
            <p class="text-muted mb-0 small">
                Revisi item untuk Invoice: <span class="text-primary fw-bold">{{ $invoice->invoice_number }}</span>
            </p>
        </div>
        <div>
            <a href="{{ route('invoice-adjustments.create') }}" class="btn btn-outline-secondary btn-sm">
                <i class="bi bi-arrow-left me-1"></i> Kembali
            </a>
        </div>
    </div>

    {{-- ERROR HANDLING --}}
    @if ($errors->any())
        <div class="alert alert-danger border-0 shadow-sm rounded-3 mb-4">
            <ul class="mb-0 small ps-3">
                @foreach ($errors->all() as $error)<li>{{ $error }}</li>@endforeach
            </ul>
        </div>
    @endif

    {{-- INFO ALERT --}}
    <div class="alert alert-info border-0 shadow-sm rounded-3 mb-4 d-flex align-items-center">
        <i class="bi bi-info-circle-fill fs-4 me-3 text-info"></i>
        <div>
            <strong class="d-block text-dark">Mode Revisi Item</strong>
            <span class="text-muted small">Silakan ubah data di bawah ini sesuai kondisi riil. Sistem akan otomatis menghitung selisihnya (Nota Debet/Kredit) tanpa mengubah invoice asli.</span>
        </div>
    </div>

    <form action="{{ route('invoice-adjustments.store.auto', $invoice->invoice_id) }}" method="POST" id="adjustment-form">
        @csrf

        <div class="row g-4">
            {{-- KOLOM KIRI: FORM ITEM --}}
            <div class="col-lg-8 col-xl-9">
                
                {{-- 1. INFORMASI READONLY --}}
                <div class="card card-transaction border-0 shadow-sm mb-4">
                    <div class="card-header bg-white p-3 border-bottom">
                        <div class="form-section-title mb-0"><i class="bi bi-file-earmark-text"></i> Informasi Dasar (Read-only)</div>
                    </div>
                    <div class="card-body p-4 bg-light bg-opacity-25">
                        <div class="row g-3">
                            <div class="col-md-4">
                                <label class="form-label small text-muted fw-bold">KLIEN</label>
                                <input type="text" class="form-control form-control-sm bg-white" value="{{ $invoice->client->client_name }}" readonly>
                                <input type="hidden" name="client_id" value="{{ $invoice->client_id }}">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label small text-muted fw-bold">TANGGAL INVOICE</label>
                                <input type="date" class="form-control form-control-sm bg-white" value="{{ optional($invoice->order_date)->format('Y-m-d') }}" readonly>
                                <input type="hidden" name="order_date" value="{{ optional($invoice->order_date)->format('Y-m-d') }}">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label small text-muted fw-bold">SALES</label>
                                <input type="text" class="form-control form-control-sm bg-white" value="{{ $invoice->sales->full_name ?? '-- Tanpa Sales --' }}" readonly>
                                <input type="hidden" name="user_id_sales" value="{{ $invoice->user_id_sales }}">
                                <input type="hidden" name="due_date" value="{{ optional($invoice->due_date)->format('Y-m-d') }}">
                            </div>
                        </div>
                    </div>
                </div>

                {{-- 2. TABEL ITEM --}}
                <div class="card card-transaction border-0 shadow-sm mb-4">
                    <div class="card-header bg-white d-flex justify-content-between align-items-center p-3 border-bottom">
                        <div class="form-section-title mb-0"><i class="bi bi-box-seam"></i> Revisi Item</div>
                        <button type="button" id="add-product-btn" class="btn btn-primary btn-sm rounded-pill px-3 shadow-sm">
                            <i class="bi bi-plus-lg me-1"></i> Tambah Item
                        </button>
                    </div>

                    <div class="card-body p-0">
                        <div class="table-responsive">
                            <table class="table table-hover table-transaction mb-0 align-middle">
                                <thead class="bg-light">
                                    <tr>
                                        <th class="text-center ps-4" style="width:40px;">
                                            <input type="checkbox" class="form-check-input cursor-pointer" id="header-row-select">
                                        </th>
                                        <th style="width: 35%;">Produk</th>
                                        <th style="width: 15%;">Qty Revisi</th>
                                        <th style="width: 20%;">Harga Revisi (@)</th>
                                        <th class="text-end pe-4" style="width: 20%;">Subtotal</th>
                                        <th class="text-center" style="width: 50px;"><i class="bi bi-gear"></i></th>
                                    </tr>
                                </thead>
                                <tbody id="product-items">
                                    {{-- JS akan mengisi row di sini --}}
                                </tbody>
                            </table>
                        </div>
                        <div class="p-4 bg-white border-top mt-2">
                            <label for="reason" class="form-label fw-semibold text-secondary small">Alasan Koreksi <span class="text-danger">*</span></label>
                            <textarea class="form-control bg-light border-muted" name="reason" id="reason" rows="2" placeholder="Contoh: Koreksi salah input harga, perubahan qty, dll..." required>{{ old('reason') }}</textarea>
                        </div>
                    </div>
                </div>
            </div>

            {{-- KOLOM KANAN: KALKULASI --}}
            <div class="col-lg-4 col-xl-3">
                <div class="card card-transaction border-0 shadow-sm sticky-top" style="top: 20px; z-index: 99;">
                    <div class="card-header bg-white p-3 border-bottom">
                        <div class="form-section-title mb-0"><i class="bi bi-calculator"></i> Kalkulasi Revisi</div>
                    </div>
                    <div class="card-body p-4">
                        
                        {{-- OPSI KALKULASI --}}
                        <div class="mb-3">
                            <label for="discount_percentage" class="form-label small fw-bold text-muted">Diskon Global (%)</label>
                            <input type="number" step="any" class="form-control form-control-sm" name="discount_percentage" id="discount_percentage" value="{{ old('discount_percentage', $invoice->discount_percentage) }}" min="0" max="100">
                        </div>

                        <div class="mb-3">
                             <label class="form-label small fw-bold text-muted">Pajak</label>
                             @foreach(\App\Models\Tax::where('is_active', true)->get() as $tax)
                                <div class="form-check mb-1">
                                    <input class="form-check-input tax-checkbox cursor-pointer" type="checkbox" name="taxes[]" value="{{ $tax->id }}" id="tax{{ $tax->id }}" data-rate="{{ $tax->rate }}" @checked($invoice->taxes->contains($tax->id))>
                                    <label class="form-check-label small cursor-pointer" for="tax{{ $tax->id }}">{{ $tax->name }} ({{ $tax->rate }}%)</label>
                                </div>
                             @endforeach
                        </div>

                        <hr class="border-dashed">

                        {{-- SUMMARY BOX --}}
                        <div class="summary-box mt-4">
                            <div class="d-flex justify-content-between mb-2 small"><span>Subtotal Barang</span><span class="fw-medium" id="summary-subtotal">Rp 0</span></div>
                            <div class="d-flex justify-content-between mb-2 small text-danger"><span>Diskon</span><span id="summary-disc">- Rp 0</span></div>
                            <div id="summary-taxes" class="small text-muted mb-2"></div>
                            
                            <hr class="my-2 border-dashed">
                            
                            <div class="summary-total">
                                <span class="label">TOTAL BARU</span>
                                <span class="value" id="summary-grand">Rp 0</span>
                            </div>
                        </div>

                        {{-- OPSI KELEBIHAN BAYAR --}}
                        <div class="mt-4 pt-3 border-top border-dashed">
                            <label class="form-label small fw-bold text-dark mb-2">Jika terjadi kelebihan bayar:</label>
                            
                            <div class="form-check mb-2">
                                <input class="form-check-input" type="radio" name="overpayment_action" id="overpayment_deposit" value="deposit" checked>
                                <label class="form-check-label small" for="overpayment_deposit">
                                    Simpan ke <strong>Saldo Kredit</strong>
                                </label>
                            </div>
                            <div class="form-check">
                                <input class="form-check-input" type="radio" name="overpayment_action" id="overpayment_refund" value="refund">
                                <label class="form-check-label small" for="overpayment_refund">
                                    Proses <strong>Refund Manual</strong>
                                </label>
                            </div>
                        </div>

                        <div class="d-grid gap-2 mt-4">
                            <button type="submit" class="btn btn-success btn-lg fw-bold shadow-sm">
                                <i class="bi bi-check-circle me-2"></i> Simpan Koreksi
                            </button>
                            <a href="{{ route('invoice-adjustments.create') }}" class="btn btn-light text-muted border">Batal</a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </form>
</div>

{{-- TEMPLATE ROW --}}
<template id="product-row-template">
    <tr>
        <td class="text-center align-middle ps-4">
            <input type="checkbox" class="row-select form-check-input cursor-pointer">
        </td>
        <td class="align-middle">
            <select class="form-select form-select-sm product-select table-input" required>
                <option value="" data-price="0" disabled selected>-- Cari Produk --</option>
                @foreach ($products as $product)
                    <option value="{{ $product->product_id }}" data-price="{{ $product->selling_price ?? 0 }}">{{ $product->product_name }}</option>
                @endforeach
            </select>
        </td>
        <td class="align-middle">
            <input type="number" class="form-control table-input quantity text-center fw-bold" value="1" min="1" required>
        </td>
        <td class="align-middle">
            <input type="text" class="form-control table-input purchase-price-formatted text-end" placeholder="0">
            <input type="hidden" class="purchase-price-hidden" value="0">
            <div class="form-check mt-1 ms-1">
                <input class="form-check-input update-master-price" type="checkbox" value="1" style="transform: scale(0.8);">
                <label class="form-check-label text-muted fst-italic" style="font-size: 0.7rem;">Update Master</label>
            </div>
        </td>
        <td class="text-end pe-4 align-middle fw-bold text-dark">
            <span class="subtotal">Rp 0</span>
        </td>
        <td class="text-center align-middle">
            <button type="button" class="btn btn-icon btn-sm text-danger remove-product-btn">
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
    // Data & Elemen
    const existingItems = @json($invoice->items);
    const productItemsContainer = document.getElementById('product-items');
    const productRowTemplate = document.getElementById('product-row-template');
    const addProductBtn = document.getElementById('add-product-btn');
    const taxOptionsContainer = document.getElementById('tax-options'); // Pembungkus checkbox pajak
    const discountInput = document.getElementById('discount_percentage');
    const headerRowSelect = document.getElementById('header-row-select');

    let productIndex = 0;
    // Map untuk menyimpan instance AutoNumeric per baris: key=rowIndex, value=AutoNumericInstance
    const autoNumericInstances = new Map();

    // --- Helper Formatter ---
    function formatRupiah(number) {
        return new Intl.NumberFormat('id-ID', { style: 'currency', currency: 'IDR', minimumFractionDigits: 0 }).format(number);
    }
    
    function parseNumericForInput(str) {
        if (!str && str !== 0) return 0;
        // Hapus titik ribuan, ganti koma desimal jadi titik
        let s = String(str).replace(/\./g, '').replace(/,/g, '.');
        return parseFloat(s) || 0;
    }

    // --- Fungsi Hitung Total ---
    function calculateTotals() {
        let subtotalProducts = 0;
        
        // Loop setiap baris produk
        const rows = Array.from(productItemsContainer.querySelectorAll('tr'));
        rows.forEach(row => {
            const qty = parseFloat(row.querySelector('.quantity').value) || 0;
            const price = parseFloat(row.querySelector('.purchase-price-hidden').value) || 0;
            const subtotal = qty * price;

            row.querySelector('.subtotal').textContent = formatRupiah(subtotal);
            subtotalProducts += subtotal;
        });

        // Hitung Diskon
        const discountRate = parseFloat(discountInput.value) || 0;
        const discountAmount = subtotalProducts * (discountRate / 100);
        const subtotalAfterDiscount = subtotalProducts - discountAmount;

        // Hitung Pajak
        let totalTaxAmount = 0;
        let taxHtml = '';
        document.querySelectorAll('.tax-checkbox:checked').forEach(cb => {
            const rate = parseFloat(cb.dataset.rate) || 0;
            const name = cb.nextElementSibling.textContent;
            const taxVal = subtotalAfterDiscount * (rate / 100);
            totalTaxAmount += taxVal;
            taxHtml += `<div class="d-flex justify-content-between mb-1 small text-muted"><span>+ ${name}</span><span>${formatRupiah(taxVal)}</span></div>`;
        });

        const grandTotal = subtotalAfterDiscount + totalTaxAmount;

        // Update Tampilan Summary
        document.getElementById('summary-subtotal').textContent = formatRupiah(subtotalProducts);
        document.getElementById('summary-disc').textContent = `(-) ${formatRupiah(discountAmount)}`;
        document.getElementById('summary-taxes').innerHTML = taxHtml;
        document.getElementById('summary-grand').textContent = formatRupiah(grandTotal);
    }

    // --- Fungsi Tambah Baris ---
    function addProductRow(item = null) {
        const clone = productRowTemplate.content.cloneNode(true);
        const row = clone.querySelector('tr');
        const currentIndex = productIndex; // Simpan index saat ini
        
        // Elemen dalam baris
        const select = row.querySelector('.product-select');
        const qtyInput = row.querySelector('.quantity');
        const priceDisplay = row.querySelector('.purchase-price-formatted');
        const priceHidden = row.querySelector('.purchase-price-hidden');
        const updateCheck = row.querySelector('.update-master-price');
        const removeBtn = row.querySelector('.remove-product-btn');

        // Set Name Attributes
        select.name = `products[${currentIndex}][product_id]`;
        qtyInput.name = `products[${currentIndex}][quantity]`;
        priceHidden.name = `products[${currentIndex}][price]`;
        updateCheck.name = `products[${currentIndex}][update_master_price]`;

        productItemsContainer.appendChild(row);

        // Init Select2
        const select2 = $(select).select2({
            theme: 'bootstrap-5',
            placeholder: '-- Pilih Produk --',
            dropdownParent: $(select).parent(),
            width: '100%'
        });

        // Init AutoNumeric pada input harga
        const anInstance = new AutoNumeric(priceDisplay, {
            decimalPlaces: 0,
            digitGroupSeparator: '.',
            decimalCharacter: ',',
            minimumValue: '0'
        });
        autoNumericInstances.set(currentIndex, anInstance);

        // Event: Ganti Produk
        select2.on('change', function() {
            const selectedOption = this.options[this.selectedIndex];
            const defaultPrice = parseFloat(selectedOption.dataset.price) || 0;
            
            // Jika bukan load data awal (user ganti manual), set harga default
            if (!item || $(this).val() != item.product_id) {
                anInstance.set(defaultPrice);
                priceHidden.value = defaultPrice;
                calculateTotals();
            }
        });

        // Event: Ketik Harga (Update hidden & hitung)
        priceDisplay.addEventListener('autoNumeric:rawValueModified', e => {
            priceHidden.value = e.detail.newRawValue;
            calculateTotals();
        });

        // Event: Ganti Qty
        qtyInput.addEventListener('input', calculateTotals);

        // Event: Hapus Baris
        removeBtn.addEventListener('click', function() {
            select2.select2('destroy');
            autoNumericInstances.delete(currentIndex);
            row.remove();
            calculateTotals();
        });

        // ISI DATA JIKA ADA (Mode Edit/Load)
        if (item) {
            $(select).val(item.product_id).trigger('change'); // Trigger change select2
            qtyInput.value = item.quantity;
            
            // Override harga dengan harga item (bukan master produk)
            setTimeout(() => {
                anInstance.set(item.price_per_unit);
                priceHidden.value = item.price_per_unit;
                calculateTotals();
            }, 50);
        }

        productIndex++;
    }

    // --- Event Listeners Global ---
    addProductBtn.addEventListener('click', () => addProductRow());
    
    // Recalculate saat checkbox pajak berubah
    if(taxOptionsContainer) {
        taxOptionsContainer.addEventListener('change', (e) => {
            if(e.target.classList.contains('tax-checkbox')) calculateTotals();
        });
    }
    
    // Recalculate saat diskon berubah
    discountInput.addEventListener('input', calculateTotals);

    // Select All Checkbox
    if(headerRowSelect) {
        headerRowSelect.addEventListener('change', function() {
            document.querySelectorAll('.row-select').forEach(cb => cb.checked = this.checked);
        });
    }

    // --- Initial Load ---
    if (existingItems.length > 0) {
        existingItems.forEach(item => addProductRow(item));
    } else {
        addProductRow();
    }
});
</script>
@endpush
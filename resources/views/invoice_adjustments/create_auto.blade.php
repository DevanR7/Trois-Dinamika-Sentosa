@extends("layouts.app")

{{-- Stylesheet (jika diperlukan oleh Select2, salin dari edit.blade.php) --}}
@push('styles')
<link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/select2-bootstrap-5-theme@1.3.0/dist/select2-bootstrap-5-theme.min.css" />
@endpush

@section("content")
<div class="container py-4">
    <div class="row justify-content-center">
        <div class="col-lg-10">
            <div class="card shadow-sm border-0">
                {{-- ✅ 1. JUDUL DIUBAH --}}
                <div class="card-header bg-primary text-white"><h4 class="mb-0">Koreksi Otomatis untuk Invoice: {{ $invoice->invoice_number }}</h4></div>
                <div class="card-body p-4">
                    @if ($errors->any() || session("error"))
                        <div class="alert alert-danger">
                            <ul class="mb-0">
                                @foreach ($errors->all() as $error)<li>{{ $error }}</li>@endforeach
                                @if (session("error"))<li>{{ session("error") }}</li>@endif
                            </ul>
                        </div>
                    @endif

                    <div class="alert alert-warning">
                        <strong>Perhatian!</strong> Anda sedang dalam mode "Koreksi Otomatis". Mengubah data di bawah ini **tidak akan** mengubah invoice asli. Sistem akan **menghitung selisih** antara total lama (Rp {{ number_format($invoice->total_amount, 0, ',', '.') }}) dengan total baru yang Anda buat, lalu membuat Nota Kredit/Debit secara otomatis.
                    </div>

                    {{-- ✅ 2. FORM ACTION DIUBAH --}}
                    <form action="{{ route("invoice-adjustments.store.auto", $invoice->invoice_id) }}" method="POST">
                        @csrf
                        {{-- (Tidak perlu @method('PUT')) --}}
                        
                        <div class="row mb-4 g-3">
                            <div class="col-md-4">
                                <label for="client_id" class="form-label fw-semibold">Klien</label>
                                {{-- Dibuat readonly karena klien tidak boleh diubah saat koreksi --}}
                                <input type="text" class="form-control" value="{{ $invoice->client->client_name }}" readonly>
                                <input type="hidden" name="client_id" value="{{ $invoice->client_id }}">
                            </div>
                             <div class="col-md-4">
                                <label for="order_date" class="form-label fw-semibold">Tanggal Pesanan</label>
                                <input type="date" class="form-control" id="order_date" name="order_date" value="{{ old("order_date", optional($invoice->order_date)->format("Y-m-d")) }}" readonly />
                            </div>
                            <div class="col-md-4">
                                <label for="due_date" class="form-label fw-semibold">Tanggal Jatuh Tempo</label>
                                <input type="date" class="form-control" id="due_date" name="due_date" value="{{ old("due_date", optional($invoice->due_date)->format("Y-m-d")) }}" readonly />
                            </div>

                            {{-- Bungkus 'row' ini di dalam 'col-12' agar sejajar --}}
                            <div class="col-12">
                                <div class="row">
                                    <div class="col-md-4">
                                        <label for="user_id_sales" class="form-label fw-semibold">Sales</label>
                                        <input type="text" class="form-control" value="{{ $invoice->sales->full_name ?? '-- Umum / Tanpa Sales --' }}" readonly>
                                        <input type="hidden" name="user_id_sales" value="{{ $invoice->user_id_sales }}">
                                    </div>
                                </div>
                            </div>
                        </div>

                        <h5 class="fw-semibold mb-3">Rincian Item (Berdasarkan Harga Jual)</h5>
                        <div class="table-responsive">
                            <table class="table table-bordered">
                                <thead class="table-light">
                                    <tr>
                                        <th style="width: 40%">Produk</th>
                                        <th style="width: 15%">Kuantitas</th>
                                        <th style="width: 20%">Harga Satuan</th>
                                        <th class="text-end" style="width: 20%">Subtotal</th>
                                        <th class="text-center">Aksi</th>
                                    </tr>
                                </thead>
                                <tbody id="product-items"></tbody>
                            </table>
                        </div>
                        <button type="button" id="add-product-btn" class="btn btn-secondary btn-sm"><i class="bi bi-plus-circle me-1"></i> Tambah Item</button>

                        <hr class="my-4" />

                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="notes" class="form-label fw-semibold">Alasan Koreksi (Wajib Diisi)</label>
                                    <textarea class="form-control" id="notes" name="notes" rows="4" placeholder="Contoh: Koreksi diskon dari 60% menjadi 61%">{{ old('notes') }}</textarea>
                                </div>
                                <h5 class="fw-semibold mb-3 mt-4">Biaya Tambahan / Pajak</h5>
                                <div id="tax-options">
                                    @php
                                        $appliedTaxIds = $invoice->taxes->pluck('id')->toArray();
                                    @endphp
                                    @forelse ($taxes as $tax)
                                        <div class="form-check">
                                            <input class="form-check-input tax-checkbox" type="checkbox" name="taxes[]" value="{{ $tax->id }}" id="tax{{ $tax->id }}" data-rate="{{ $tax->rate }}" @checked(in_array($tax->id, old('taxes', $appliedTaxIds))) />
                                            <label class="form-check-label" for="tax{{ $tax->id }}">{{ $tax->name }} ({{ $tax->rate }}%)</label>
                                        </div>
                                    @empty
                                        <p class="text-muted">Tidak ada data pajak aktif.</p>
                                    @endforelse
                                </div>
                            </div>
                            <div class="col-md-6">
                                <h5 class="fw-semibold mb-3">Ringkasan Total</h5>
                                <div class="border rounded p-3">
                                    <div class="d-flex justify-content-between mb-2"><span>Subtotal Produk</span><span id="subtotal-display">Rp 0</span></div>
                                    <div class="d-flex justify-content-between align-items-center mb-2">
                                        <label for="discount_percentage" class="form-label mb-0">Diskon Global (%)</label>
                                        <input type="number" step="any" class="form-control form-control-sm" name="discount_percentage" id="discount_percentage" value="{{ old('discount_percentage', $invoice->discount_percentage) }}" style="width: 80px;">
                                    </div>
                                    <div class="d-flex justify-content-between mb-2"><span class="text-danger">Potongan Diskon</span><span class="text-danger" id="discount-amount-display">Rp 0</span></div>
                                    <hr>
                                    <div class="d-flex justify-content-between mb-2 fw-semibold"><span>Subtotal Setelah Diskon</span><span id="subtotal-after-discount">Rp 0</span></div>
                                    <div id="tax-breakdown">{{-- Rincian pajak --}}</div>
                                    <hr />
                                    <h4 class="fw-bold d-flex justify-content-between"><span>Total Baru</span><span id="grand-total" class="text-primary">Rp 0</span></h4>
                                </div>
                            </div>
                        </div>

                        <div class="d-flex justify-content-end mt-4">
                            {{-- ✅ 3. TOMBOL DIUBAH --}}
                            <a href="{{ route("invoice-adjustments.create", $invoice->invoice_id) }}" class="btn btn-light me-2">Batal</a>
                            <button type="submit" class="btn btn-primary">Hitung & Simpan Koreksi</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

{{-- Template & @push('scripts') disalin dari edit.blade.php Anda --}}
<template id="product-row-template">
    <tr>
        <td>
            <select class="form-select form-select-sm product-select" required>
                <option value="" data-price="0" disabled selected>-- Pilih Produk --</option>
                @foreach ($products as $product)
                    <option value="{{ $product->product_id }}" data-price="{{ $product->selling_price ?? 0 }}"> {{ $product->product_name }}
                @endforeach
            </select>
        </td>
        <td><input type="number" class="form-control form-control-sm quantity" value="1" min="1" required /></td>
        <td><input type="text" class="form-control form-control-sm price-display" readonly /></td>
        <td class="text-end"><span class="subtotal">Rp 0</span></td>
        <td class="text-center"><button type="button" class="btn btn-danger btn-sm remove-product-btn"><i class="bi bi-trash"></i></button></td>
    </tr>
</template>

{{-- =================================== --}}
{{-- ✅ INI ADALAH PERBAIKANNYA --}}
{{-- =================================== --}}
@endsection 
{{-- (Sebelumnya @endpush) --}}
{{-- =================================== --}}


@push("scripts")
{{-- Tambahkan jQuery jika belum ada di layout utama --}}
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
{{-- Tambahkan Select2 JS --}}
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function () {
    // Select2 untuk Klien (meskipun readonly, mungkin diperlukan)
    $('#client_id').select2({
        theme: 'bootstrap-5',
        placeholder: '-- Pilih Klien --'
    });
    
    // Data item asli
    const existingItems = @json($invoice->items);
    const productItemsContainer = document.getElementById('product-items');
    const productRowTemplate = document.getElementById('product-row-template');
    const addProductBtn = document.getElementById('add-product-btn');
    const taxOptionsContainer = document.getElementById('tax-options');
    const discountInput = document.getElementById('discount_percentage');
    let productIndex = 0;

    function formatRupiah(number) {
        if (isNaN(number)) return 'Rp 0';
        return new Intl.NumberFormat('id-ID', {
            style: 'currency',
            currency: 'IDR',
            minimumFractionDigits: 0,
        }).format(number);
    }

    function calculateTotals() {
        let subtotalProducts = 0;
        productItemsContainer.querySelectorAll('tr').forEach((row) => {
            const selectedOption = row.querySelector('.product-select option:checked');
            const price = parseFloat(selectedOption.dataset.price) || 0;
            const quantity = parseInt(row.querySelector('.quantity').value) || 0;
            const subtotal = price * quantity;
            row.querySelector('.price-display').value = formatRupiah(price);
            row.querySelector('.subtotal').textContent = formatRupiah(subtotal);
            subtotalProducts += subtotal;
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
            taxHtml += `<div class="d-flex justify-content-between mb-2"><span>${name}:</span> <span>${formatRupiah(taxAmount)}</span></div>`;
        });
        
        const grandTotal = subtotalAfterDiscount + totalTaxAmount;

        document.getElementById('subtotal-display').textContent = formatRupiah(subtotalProducts);
        document.getElementById('discount-amount-display').textContent = `(-) ${formatRupiah(discountAmount)}`;
        document.getElementById('subtotal-after-discount').textContent = formatRupiah(subtotalAfterDiscount);
        document.getElementById('tax-breakdown').innerHTML = taxHtml;
        document.getElementById('grand-total').textContent = formatRupiah(grandTotal);
    }

    function addProductRow(item = null) {
        const newRow = productRowTemplate.content.cloneNode(true).querySelector('tr');
        const productSelect = newRow.querySelector('.product-select');
        const quantityInput = newRow.querySelector('.quantity');
        
        // Atur nama input untuk form submission
        productSelect.name = `products[${productIndex}][product_id]`;
        quantityInput.name = `products[${productIndex}][quantity]`;
        
        productItemsContainer.appendChild(newRow);

        const select2 = $(productSelect).select2({ theme: 'bootstrap-5', dropdownParent: $(productSelect).parent(), placeholder: '-- Pilih Produk --' });

        select2.on('change', calculateTotals);

        quantityInput.addEventListener('input', calculateTotals);
        newRow.querySelector('.remove-product-btn').addEventListener('click', () => {
            select2.select2('destroy');
            newRow.remove();
            calculateTotals();
        });

        if (item) {
            $(productSelect).val(item.product_id).trigger('change');
            quantityInput.value = item.quantity;
        }
        
        productIndex++;
    }

    // --- Inisialisasi Halaman ---
    addProductBtn.addEventListener('click', () => addProductRow());
    taxOptionsContainer.addEventListener('change', calculateTotals);
    discountInput.addEventListener('input', calculateTotals);

    if (existingItems.length > 0) {
        existingItems.forEach(item => addProductRow(item));
    } else {
        addProductRow();
    }
    
    // Panggil kalkulasi di akhir
    calculateTotals();
});
</script>
@endpush
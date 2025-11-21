@extends('layouts.app')

@section('content')
<div class="container-fluid py-2">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h3 class="fw-bold text-dark mb-1">Edit Pesanan Penjualan</h3>
            <p class="text-muted mb-0 small">No. Pesanan: <span class="text-primary fw-bold">{{ $order->order_number }}</span></p>
        </div>
        <div>
            <a href="{{ route('sales-orders.index') }}" class="btn btn-outline-secondary btn-sm">
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

            <form action="{{ route('sales-orders.update', $order->order_id) }}" method="POST">
                @csrf
                @method('PUT')
                
                <div class="card card-transaction border-0 shadow-sm">
                    <div class="card-header bg-white p-4 border-bottom">
                        <div class="form-section-title mb-0"><i class="bi bi-pencil-square"></i> Edit Data Pesanan</div>
                    </div>
                    
                    <div class="card-body p-4">
                        <div class="row g-3 mb-4">
                            <div class="col-md-6">
                                <label for="client_id" class="form-label fw-bold small text-muted">PELANGGAN (KLIEN)</label>
                                <select name="client_id" id="client_id" class="form-select" required>
                                    @foreach ($clients as $client)
                                        <option value="{{ $client->client_id }}" {{ old('client_id', $order->client_id) == $client->client_id ? 'selected' : '' }}>
                                            {{ $client->client_name }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label for="order_date" class="form-label fw-bold small text-muted">TANGGAL PESANAN</label>
                                <input type="date" class="form-control" id="order_date" name="order_date" value="{{ old('order_date', $order->order_date->format('Y-m-d')) }}" required>
                            </div>
                        </div>

                        <hr class="border-dashed">

                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <h6 class="fw-bold text-dark mb-0">Rincian Item</h6>
                            <button type="button" id="add-product-btn" class="btn btn-primary btn-sm rounded-pill px-3">
                                <i class="bi bi-plus-lg me-1"></i> Tambah Item
                            </button>
                        </div>

                        <div class="table-responsive mb-3">
                            <table class="table table-hover table-transaction align-middle mb-0">
                                <thead class="bg-light">
                                    <tr>
                                        <th style="width: 40%;">Produk</th>
                                        <th style="width: 15%;">Kuantitas</th>
                                        <th style="width: 20%;">Harga Satuan</th>
                                        <th class="text-end" style="width: 20%;">Subtotal</th>
                                        <th class="text-center" style="width: 5%;"></th>
                                    </tr>
                                </thead>
                                <tbody id="product-items"></tbody>
                            </table>
                        </div>

                        <div class="row mt-4">
                            <div class="col-md-7">
                                <label for="notes" class="form-label fw-bold small text-muted">CATATAN</label>
                                <textarea class="form-control bg-light" name="notes" id="notes" rows="3">{{ old('notes', $order->notes) }}</textarea>
                            </div>
                            <div class="col-md-5">
                                <div class="card bg-light border-0 p-3">
                                    <div class="d-flex justify-content-between align-items-center">
                                        <span class="fw-bold text-secondary">TOTAL PESANAN</span>
                                        <span class="fw-bold fs-4 text-primary" id="grand-total">Rp 0</span>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="d-flex justify-content-end mt-4 pt-3 border-top">
                            <a href="{{ route('sales-orders.index') }}" class="btn btn-light border me-2">Batal</a>
                            <button type="submit" class="btn btn-success px-4 fw-bold">Update Pesanan</button>
                        </div>
                    </div>
                </div>
            </form>
        </div>
    </div>
</div>

{{-- TEMPLATE ROW SAMA DENGAN CREATE --}}
<template id="product-row-template">
    <tr>
        <td>
            <select class="form-select form-select-sm product-select" required>
                <option></option>
                @foreach ($products as $product)
                    <option value="{{ $product->product_id }}" data-price="{{ $product->selling_price ?? 0 }}">
                        {{ $product->product_name }}
                    </option>
                @endforeach
            </select>
        </td>
        <td><input type="number" class="form-control form-control-sm quantity text-center" value="1" min="1" required></td>
        <td>
            <div class="input-group input-group-sm">
                <span class="input-group-text border-0 bg-transparent px-1 text-muted">Rp</span>
                <input type="text" class="form-control border-0 bg-transparent px-0 price-display" readonly>
            </div>
            <input type="hidden" class="price-raw">
        </td>
        <td class="text-end fw-bold text-dark"><span class="subtotal">Rp 0</span></td>
        <td class="text-center">
            <button type="button" class="btn btn-link text-danger btn-sm remove-product-btn p-0"><i class="bi bi-trash"></i></button>
        </td>
    </tr>
</template>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    $('#client_id').select2({ theme: 'bootstrap-5', placeholder: '-- Pilih Klien --', width: '100%' });
    
    const orderItems = @json($order->items);
    const productItemsContainer = document.getElementById('product-items');
    const productRowTemplate = document.getElementById('product-row-template');
    const addProductBtn = document.getElementById('add-product-btn');
    let productIndex = 0;

    function formatRupiah(number) {
        return new Intl.NumberFormat('id-ID', { style: 'currency', currency: 'IDR', minimumFractionDigits: 0 }).format(number);
    }

    function calculateTotals() {
        let grandTotal = 0;
        productItemsContainer.querySelectorAll('tr').forEach(row => {
            const price = parseFloat(row.querySelector('.price-raw').value) || 0;
            const quantity = parseInt(row.querySelector('.quantity').value) || 0;
            const subtotal = price * quantity;
            row.querySelector('.subtotal').textContent = formatRupiah(subtotal);
            grandTotal += subtotal;
        });
        document.getElementById('grand-total').textContent = formatRupiah(grandTotal);
    }

    function addProductRow(item = null) {
        const newRowFragment = productRowTemplate.content.cloneNode(true);
        const newRow = newRowFragment.querySelector('tr');
        
        const productSelect = newRow.querySelector('.product-select');
        const quantityInput = newRow.querySelector('.quantity');
        const priceDisplay = newRow.querySelector('.price-display');
        const priceRaw = newRow.querySelector('.price-raw');
        const removeBtn = newRow.querySelector('.remove-product-btn');

        productSelect.name = `products[${productIndex}][product_id]`;
        quantityInput.name = `products[${productIndex}][quantity]`;
        priceRaw.name = `products[${productIndex}][price]`;
        
        productItemsContainer.appendChild(newRow);

        const select2 = $(productSelect).select2({
            placeholder: '-- Pilih Produk --',
            theme: 'bootstrap-5',
            dropdownParent: $(productSelect).parent(),
            width: '100%'
        });

        select2.on('select2:select', function(e) {
            const selectedOption = e.params.data.element;
            const price = selectedOption.getAttribute('data-price') || 0;
            priceDisplay.value = formatRupiah(price).replace('Rp', '').trim();
            priceRaw.value = price;
            calculateTotals();
        });

        quantityInput.addEventListener('input', calculateTotals);
        removeBtn.addEventListener('click', () => {
            select2.select2('destroy');
            newRow.remove();
            calculateTotals();
        });

        if (item) {
            $(productSelect).val(item.product_id).trigger('change.select2');
            quantityInput.value = item.quantity;
            priceDisplay.value = formatRupiah(item.price_per_unit).replace('Rp', '').trim();
            priceRaw.value = item.price_per_unit;
        } else {
            $(productSelect).trigger('change');
        }
        
        productIndex++;
    }

    addProductBtn.addEventListener('click', () => addProductRow());
    
    if (orderItems.length > 0) {
        orderItems.forEach(item => addProductRow(item));
    } else {
        addProductRow();
    }
    
    calculateTotals();
});
</script>
@endpush
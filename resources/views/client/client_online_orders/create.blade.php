@extends('layouts.client')

@section('content')
<div class="container-fluid py-4">
    <h2 class="fw-bold mb-4">Buat Pesanan Online Baru</h2> {{-- Judul diubah --}}
    <div class="card shadow-sm border-0">
        <div class="card-body p-4">
            @if ($errors->any())
                <div class="alert alert-danger"><ul class="mb-0">@foreach ($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>
            @endif
             @if (session('error'))
                <div class="alert alert-danger">{{ session('error') }}</div>
            @endif

            {{-- Action form ke route baru --}}
            <form action="{{ route('client.client-orders.store') }}" method="POST">
                @csrf
                <div class="row mb-4">
                    <div class="col-md-6">
                        <label class="form-label fw-semibold">Nama Klien</label>
                        <input type="text" class="form-control" value="{{ Auth::guard('client')->user()->client_name }}" readonly>
                    </div>
                    <div class="col-md-6">
                        <label for="order_date" class="form-label fw-semibold">Tanggal Pesanan</label>
                        <input type="date" class="form-control" id="order_date" name="order_date" value="{{ old('order_date', now()->format('Y-m-d')) }}" required max="{{ now()->format('Y-m-d') }}">
                    </div>
                </div>

                <h5 class="fw-semibold mb-3">Rincian Item</h5>
                <div class="table-responsive">
                    <table class="table table-bordered align-middle">
                        <thead class="table-light">
                            <tr>
                                <th style="width: 40%;">Produk</th>
                                <th style="width: 15%;">Kuantitas</th>
                                <th style="width: 20%;">Harga Satuan</th>
                                <th class="text-end" style="width: 20%;">Subtotal</th>
                                <th class="text-center">Aksi</th>
                            </tr>
                        </thead>
                        <tbody id="product-items"></tbody>
                    </table>
                </div>
                <button type="button" id="add-product-btn" class="btn btn-secondary btn-sm"><i class="bi bi-plus-circle me-1"></i> Tambah Item</button>

                <hr class="my-4">

                <div class="row justify-content-between">
                    <div class="col-md-6">
                        <label for="notes" class="form-label fw-semibold">Catatan (Opsional)</label>
                        <textarea class="form-control" name="notes" id="notes" rows="3">{{ old('notes') }}</textarea>
                    </div>
                    <div class="col-md-5 text-end">
                        <h4 class="fw-bold">Total Pesanan: <span id="grand-total" class="text-primary">Rp 0</span></h4>
                    </div>
                </div>

                <div class="d-flex justify-content-end mt-4">
                    {{-- Link batal ke index pesanan online --}}
                    <a href="{{ route('client.client-orders.index') }}" class="btn btn-light me-2">Batal</a>
                    <button type="submit" class="btn btn-primary">Kirim Permintaan Pesanan</button>
                </div>
            </form>
        </div>
    </div>
</div>

{{-- Template untuk baris item --}}
<template id="product-row-template">
    <tr>
        <td>
            <select class="form-select form-select-sm product-select" required>
                <option value="" data-price="0" disabled selected>-- Pilih Produk --</option>
                @foreach ($products as $product)
                    {{-- Tetap pakai purchase_price sesuai request --}}
                    <option value="{{ $product->product_id }}" data-price="{{ $product->purchase_price ?? 0 }}">{{ $product->product_name }}</option>
                @endforeach
            </select>
            <input type="hidden" class="price-raw">
        </td>
        <td><input type="number" class="form-control form-control-sm quantity" value="1" min="1" required></td>
        <td><input type="text" class="form-control form-control-sm price-display" readonly></td>
        <td class="text-end fw-bold"><span class="subtotal">Rp 0</span></td>
        <td class="text-center"><button type="button" class="btn btn-danger btn-sm remove-product-btn"><i class="bi bi-trash"></i></button></td>
    </tr>
</template>
@endsection

@push('scripts')
{{-- Select2 CSS & JS jika belum ada di layout utama --}}
<link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/select2-bootstrap-5-theme@1.3.0/dist/select2-bootstrap-5-theme.min.css" />
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>

<script>
    // Kode JavaScript persis sama dengan sebelumnya, tidak perlu diubah
    document.addEventListener('DOMContentLoaded', function () {
        const productItemsContainer = document.getElementById('product-items');
        const productRowTemplate = document.getElementById('product-row-template');
        const addProductBtn = document.getElementById('add-product-btn');
        let productIndex = 0;

        function formatRupiah(number) { /* ... */
             if (isNaN(number)) return 'Rp 0';
            return new Intl.NumberFormat('id-ID', { style: 'currency', currency: 'IDR', minimumFractionDigits: 0 }).format(number);
        }

        function calculateTotals() { /* ... */
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

        function addProductRow(preselectedProductId = null) { /* ... */
            const newRowFragment = productRowTemplate.content.cloneNode(true);
            const newRow = newRowFragment.querySelector('tr');
            const productSelect = newRow.querySelector('.product-select');
            const quantityInput = newRow.querySelector('.quantity');
            const priceDisplay = newRow.querySelector('.price-display');
            const priceRaw = newRow.querySelector('.price-raw');
            const removeBtn = newRow.querySelector('.remove-product-btn');
            productSelect.name = `products[${productIndex}][product_id]`;
            quantityInput.name = `products[${productIndex}][quantity]`;
            productItemsContainer.appendChild(newRow);
            const select2 = $(productSelect).select2({
                placeholder: '-- Pilih Produk --',
                theme: 'bootstrap-5',
                dropdownParent: $(productSelect).parent()
            });
            select2.on('change', function(e) {
                const selectedOption = this.options[this.selectedIndex];
                const price = selectedOption.getAttribute('data-price') || 0;
                priceDisplay.value = formatRupiah(price);
                priceRaw.value = price;
                calculateTotals();
            });
            quantityInput.addEventListener('input', calculateTotals);
            removeBtn.addEventListener('click', () => {
                select2.select2('destroy');
                newRow.remove();
                calculateTotals();
            });
            if (preselectedProductId) {
                select2.val(preselectedProductId).trigger('change');
            } else {
                 select2.trigger('change');
            }
            productIndex++;
        }
        addProductBtn.addEventListener('click', addProductRow);
        addProductRow();
        calculateTotals();
    });
</script>
@endpush
@extends('layouts.app')

@section('title', 'Edit Pesanan Penjualan')

@section('content')
<div class="max-w-6xl mx-auto">
    
    {{-- HEADER --}}
    <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center mb-6 gap-4">
        <div>
            <div class="flex items-center gap-2 text-sm text-gray-500 mb-1">
                <a href="{{ route('sales-orders.index') }}" class="hover:text-indigo-600 transition">Pesanan</a>
                <span>/</span>
                <span class="text-gray-800">Edit</span>
            </div>
            <h2 class="text-2xl font-bold text-gray-900 tracking-tight">
                Edit Pesanan: <span class="text-indigo-600">{{ $order->order_number }}</span>
            </h2>
        </div>
        <a href="{{ route('sales-orders.index') }}" class="px-4 py-2 bg-white border border-gray-300 rounded-lg text-gray-700 text-sm font-medium hover:bg-gray-50 transition shadow-sm">
            Kembali
        </a>
    </div>

    @if ($errors->any())
        <div class="mb-6 bg-red-50 border-l-4 border-red-500 p-4 rounded-r shadow-sm">
            <ul class="list-disc list-inside text-sm text-red-700">
                @foreach ($errors->all() as $error) <li>{{ $error }}</li> @endforeach
            </ul>
        </div>
    @endif

    <form action="{{ route('sales-orders.update', $order->order_id) }}" method="POST">
        @csrf @method('PUT')
        
        <div class="grid grid-cols-1 lg:grid-cols-12 gap-6">
            
            <div class="lg:col-span-8 space-y-6">
                <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
                    <div class="px-6 py-4 border-b border-gray-100 bg-gray-50 flex items-center gap-2">
                        <i class="bi bi-pencil-square text-indigo-500"></i>
                        <h3 class="text-sm font-bold text-gray-700 uppercase tracking-wider">Edit Data Pesanan</h3>
                    </div>
                    <div class="p-6 grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div class="md:col-span-2">
                            <label class="block text-xs font-bold text-gray-700 uppercase mb-1">Pelanggan (Klien)</label>
                            <select name="client_id" id="client_id" class="w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 text-sm" required>
                                @foreach ($clients as $client)
                                    <option value="{{ $client->client_id }}" @selected(old('client_id', $order->client_id) == $client->client_id)>{{ $client->client_name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div>
                            <label class="block text-xs font-bold text-gray-700 uppercase mb-1">Tanggal Pesanan</label>
                            <input type="date" name="order_date" value="{{ old('order_date', $order->order_date->format('Y-m-d')) }}" class="w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 text-sm" required>
                        </div>
                    </div>
                </div>

                <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
                    <div class="px-6 py-4 border-b border-gray-100 bg-gray-50 flex justify-between items-center">
                        <h3 class="text-sm font-bold text-gray-700 uppercase tracking-wider flex items-center gap-2">
                            <i class="bi bi-cart text-indigo-500"></i> Edit Item
                        </h3>
                        <button type="button" id="add-product-btn" class="inline-flex items-center px-3 py-1.5 bg-indigo-50 text-indigo-700 text-xs font-bold rounded-md hover:bg-indigo-100 border border-indigo-200 transition">
                            <i class="bi bi-plus-lg mr-1"></i> Tambah Item
                        </button>
                    </div>
                    
                    <div class="overflow-x-auto">
                        <table class="w-full text-left border-collapse">
                            <thead class="bg-gray-50 border-b border-gray-200">
                                <tr>
                                    <th class="px-4 py-3 text-xs font-bold text-gray-500 uppercase w-5/12">Produk</th>
                                    <th class="px-4 py-3 text-xs font-bold text-gray-500 uppercase text-center w-2/12">Qty</th>
                                    <th class="px-4 py-3 text-xs font-bold text-gray-500 uppercase text-right w-2/12">Harga (@)</th>
                                    <th class="px-4 py-3 text-xs font-bold text-gray-500 uppercase text-right w-2/12">Subtotal</th>
                                    <th class="px-4 py-3 w-10"></th>
                                </tr>
                            </thead>
                            <tbody id="product-items" class="divide-y divide-gray-100 bg-white">
                                {{-- JS Inject Rows --}}
                            </tbody>
                        </table>
                    </div>
                    
                    <div class="p-6 border-t border-gray-100 bg-yellow-50/30">
                        <label class="block text-xs font-bold text-gray-700 uppercase mb-2">Catatan</label>
                        <textarea class="w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 text-sm bg-white" name="notes" rows="2">{{ old('notes', $order->notes) }}</textarea>
                    </div>
                </div>
            </div>

            <div class="lg:col-span-4 space-y-6">
                <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6 sticky top-6">
                    <h3 class="text-sm font-bold text-gray-900 mb-4 flex items-center gap-2 border-b border-gray-100 pb-3">
                        <i class="bi bi-calculator text-indigo-500"></i> Ringkasan
                    </h3>
                    <div class="flex justify-between items-center mb-6">
                        <span class="font-medium text-gray-700">TOTAL PESANAN</span>
                        <span class="text-xl font-bold text-indigo-600" id="grand-total">Rp 0</span>
                    </div>
                    <div class="flex flex-col gap-3">
                        <button type="submit" class="w-full py-3 bg-green-600 hover:bg-green-700 text-white text-sm font-bold rounded-lg shadow-md transition flex justify-center items-center gap-2">
                            <i class="bi bi-check-circle"></i> Update Pesanan
                        </button>
                        <a href="{{ route('sales-orders.index') }}" class="w-full py-3 bg-white border border-gray-300 text-gray-700 text-sm font-bold rounded-lg hover:bg-gray-50 transition text-center shadow-sm">
                            Batal
                        </a>
                    </div>
                </div>
            </div>

        </div>
    </form>
</div>

{{-- TEMPLATE ROW SAMA DENGAN CREATE --}}
<template id="product-row-template">
    <tr class="hover:bg-gray-50 transition-colors border-b border-gray-50 last:border-0">
        <td class="p-3 align-top">
           <select class="product-select table-input w-full text-sm" required>
                <option></option>
                @foreach ($products as $product)
                    <option value="{{ $product->product_id }}" data-price="{{ $product->selling_price ?? 0 }}">
                        {{ $product->product_name }}
                    </option>
                @endforeach
            </select>
        </td>
        <td class="p-3 align-top">
            <input type="number" class="table-input quantity text-center w-full font-bold text-gray-700 border border-gray-300 rounded-md h-9" value="1" min="1" required>
        </td>
        <td class="p-3 align-top">
            <div class="relative">
                <span class="absolute inset-y-0 left-0 pl-2 flex items-center text-gray-400 text-xs">Rp</span>
                <input type="text" class="price-display block w-full pl-8 pr-2 py-1.5 border border-gray-300 rounded-md text-right text-sm bg-gray-50 text-gray-600 cursor-not-allowed" readonly>
            </div>
            <input type="hidden" class="price-raw">
        </td>
        <td class="p-3 align-top text-right font-bold text-gray-900 text-sm align-middle">
            <span class="subtotal">Rp 0</span>
        </td>
        <td class="p-3 align-top text-center align-middle">
            <button type="button" class="text-gray-400 hover:text-red-500 hover:bg-red-50 rounded p-1 transition remove-product-btn">
                <i class="bi bi-trash"></i>
            </button>
        </td>
    </tr>
</template>
@endsection

@push('scripts')
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function () {
    $('#client_id').select2({ theme: 'bootstrap-5', width: '100%' });
    
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
            priceDisplay.value = new Intl.NumberFormat('id-ID').format(price);
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
            priceDisplay.value = new Intl.NumberFormat('id-ID').format(item.price_per_unit);
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
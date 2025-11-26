@extends('layouts.app')

@section('title', 'Edit Pesanan Penjualan')

@push('styles')
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
@endpush

@section('content')
<div class="max-w-7xl mx-auto pb-20 animate-enter">
    
    {{-- HEADER --}}
    <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center mb-8 gap-4">
        <div>
            <nav class="flex text-sm text-slate-500 mb-1">
                <a href="{{ route('sales-orders.index') }}" class="hover:text-indigo-600 transition-colors">Pesanan</a>
                <span class="mx-2 text-slate-300">/</span>
                <span class="text-slate-800 font-semibold">Edit</span>
            </nav>
            <h1 class="text-2xl font-bold text-slate-800 tracking-tight">
                Edit Pesanan <span class="text-indigo-600 font-mono">{{ $order->order_number }}</span>
            </h1>
        </div>
        
        <div class="flex gap-3 w-full sm:w-auto">
            @can("delete", $order)
            <form action="{{ route('sales-orders.destroy', $order->order_id) }}" method="POST" class="delete-form hidden sm:block">
                @csrf @method('DELETE')
                <button type="submit" 
                        data-name="{{ $order->order_number }}" 
                        class="h-[48px] px-5 bg-red-50 border border-red-200 text-red-700 font-bold rounded-lg hover:bg-red-100 transition-all text-sm flex items-center justify-center gap-2">
                    <i class="material-icons text-[18px]">delete</i> Hapus
                </button>
            </form>
            @endcan

            <a href="{{ route('sales-orders.index') }}" class="h-[48px] px-6 bg-white border border-slate-300 text-slate-700 rounded-lg text-sm font-bold hover:bg-slate-50 transition-all flex items-center justify-center gap-2 shadow-sm">
                <i class="material-icons text-[18px]">close</i> Batal
            </a>
        </div>
    </div>

    <form action="{{ route('sales-orders.update', $order->order_id) }}" method="POST" id="sales-order-form">
        @csrf @method('PUT')
        
        <div class="grid grid-cols-1 lg:grid-cols-12 gap-6">
            
            {{-- KOLOM KIRI --}}
            <div class="lg:col-span-8 space-y-6">
                <div class="dashboard-card p-0 overflow-hidden shadow-sm">
                    <div class="px-6 py-4 border-b border-slate-100 bg-slate-50/50 flex items-center gap-3">
                        <div class="p-2 bg-indigo-50 rounded-lg text-indigo-600">
                            <i class="material-icons text-[20px]">edit_note</i>
                        </div>
                        <h3 class="text-sm font-bold text-slate-700 uppercase tracking-wide">Edit Data Pesanan</h3>
                    </div>
                    <div class="p-6 grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div class="md:col-span-2">
                            <label for="client_id">Pelanggan (Klien) <span class="text-red-500">*</span></label>
                            <select name="client_id" id="client_id" class="form-input select2-basic" required>
                                @foreach ($clients as $client)
                                    <option value="{{ $client->client_id }}" @selected(old('client_id', $order->client_id) == $client->client_id)>{{ $client->client_name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div>
                            <label for="order_date">Tanggal Pesanan</label>
                            <input type="date" class="form-input" id="order_date" name="order_date" value="{{ old('order_date', $order->order_date->format('Y-m-d')) }}" required>
                        </div>
                        <div>
                             <label for="sales_id">Sales Person</label>
                            <select name="sales_id" id="sales_id" class="form-input select2-basic">
                                <option value="" @selected(old('sales_id', $order->sales_id) == null)>-- Pilih Sales --</option>
                                @foreach ($salesUsers as $sale)
                                    <option value="{{ $sale->user_id }}" @selected(old('sales_id', $order->sales_id) == $sale->user_id)>{{ $sale->full_name }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                </div>

                {{-- CARD ITEM (Sama dengan Create, tapi dengan data existing) --}}
                <div class="dashboard-card p-0 overflow-hidden shadow-sm">
                    <div class="px-6 py-4 border-b border-slate-100 bg-slate-50/80 flex justify-between items-center">
                        <h3 class="card-title flex items-center gap-2">
                            <i class="material-icons text-indigo-600">shopping_cart</i> Edit Item
                        </h3>
                        <button type="button" id="add-product-btn" class="inline-flex items-center px-3 py-1.5 bg-white border border-slate-300 rounded-lg text-xs font-bold text-slate-700 hover:bg-slate-50 hover:text-indigo-600 transition shadow-sm gap-1">
                            <i class="material-icons text-base">add</i> Tambah Item
                        </button>
                    </div>
                    
                    <div class="overflow-x-auto">
                        <table class="dashboard-table min-w-full">
                            <thead class="bg-slate-50 border-b border-slate-200">
                                <tr>
                                    <th class="px-4 py-3 text-xs font-bold text-slate-500 uppercase w-5/12 pl-6">Produk</th>
                                    <th class="px-4 py-3 text-xs font-bold text-slate-500 uppercase text-center w-2/12">Qty</th>
                                    <th class="px-4 py-3 text-xs font-bold text-slate-500 uppercase text-right w-2/12">Harga (@)</th>
                                    <th class="px-4 py-3 text-xs font-bold text-slate-500 uppercase text-right w-2/12">Subtotal</th>
                                    <th class="px-4 py-3 w-10 pr-6"></th>
                                </tr>
                            </thead>
                            <tbody id="product-items" class="divide-y divide-slate-100 bg-white">
                                {{-- JS Inject Rows --}}
                            </tbody>
                        </table>
                    </div>
                    
                    <div class="p-6 border-t border-slate-100 bg-slate-50/80">
                        <label for="notes">Catatan / Instruksi</label>
                        <textarea class="form-textarea bg-white" name="notes" id="notes" rows="2">{{ old('notes', $order->notes) }}</textarea>
                    </div>
                </div>
            </div>

            {{-- KOLOM KANAN --}}
            <div class="lg:col-span-4 space-y-6">
                <div class="dashboard-card p-6 shadow-lg sticky top-6 border-t-4 border-indigo-500">
                    <h3 class="card-title mb-4 border-b border-slate-100 pb-3 flex items-center gap-2">
                        <i class="material-icons text-indigo-600">calculate</i> Ringkasan
                    </h3>

                    <div class="flex justify-between items-center mb-8 bg-slate-50 p-4 rounded-xl border border-slate-100">
                        <span class="font-bold text-slate-800 uppercase">TOTAL TAGIHAN</span>
                        <span class="text-2xl font-bold text-indigo-700 font-mono" id="grand-total">Rp 0</span>
                    </div>

                    <div class="flex flex-col gap-3">
                        <button type="submit" class="w-full h-[48px] bg-indigo-600 hover:bg-indigo-700 text-white text-sm font-bold rounded-lg shadow-md transition-all flex justify-center items-center gap-2 hover:-translate-y-0.5">
                            <i class="material-icons text-[18px]">save</i> Update Pesanan
                        </button>
                        <a href="{{ route('sales-orders.index') }}" class="w-full h-[48px] bg-white border border-slate-300 text-slate-700 text-sm font-bold rounded-lg hover:bg-slate-50 transition-all text-center shadow-sm flex items-center justify-center">
                            Batal
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </form>
</div>

{{-- TEMPLATE ROW (Hidden) --}}
<table class="hidden">
    <tbody id="product-row-template">
        <tr class="hover:bg-slate-50/50 transition-colors group">
            <td class="p-3 pl-6 align-top">
               <select class="product-select form-input w-full text-sm" required>
                    <option></option>
                    @foreach ($products as $product)
                        <option value="{{ $product->product_id }}" data-price="{{ $product->selling_price ?? 0 }}">
                            {{ $product->product_name }}
                        </option>
                    @endforeach
                </select>
            </td>
            <td class="p-3 align-top">
                <input type="number" class="form-input quantity text-center w-full font-bold text-slate-700 h-10" value="1" min="1" required>
            </td>
            <td class="p-3 align-top">
                <div class="relative">
                    <input type="text" class="form-input price-display block w-full text-right text-sm bg-slate-50 text-slate-500 border-slate-200 cursor-not-allowed font-mono" readonly>
                </div>
                <input type="hidden" class="price-raw">
            </td>
            <td class="p-3 align-top text-right font-bold text-slate-800 text-sm align-middle font-mono">
                <span class="subtotal">Rp 0</span>
            </td>
            <td class="p-3 pr-6 align-top text-center align-middle">
                <button type="button" class="w-8 h-8 flex items-center justify-center rounded-lg text-slate-400 hover:text-red-600 hover:bg-red-50 transition remove-product-btn">
                    <i class="material-icons text-[18px]">delete</i>
                </button>
            </td>
        </tr>
    </tbody>
</table>
@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function () {
    // DATA EXISTING
    const existingItems = @json($order->items);
    
    $('#client_id, #sales_id').select2({ placeholder: '-- Pilih --', width: '100%', dropdownCssClass: 'select2-dropdown-clean', allowClear: true });
    
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
        const newRow = productRowTemplate.rows[0].cloneNode(true);
        const productSelect = newRow.querySelector('.product-select');
        const quantityInput = newRow.querySelector('.quantity');
        const priceDisplay = newRow.querySelector('.price-display');
        const priceRaw = newRow.querySelector('.price-raw');
        const removeBtn = newRow.querySelector('.remove-product-btn');

        productSelect.name = `items[${productIndex}][product_id]`;
        quantityInput.name = `items[${productIndex}][quantity]`;
        priceRaw.name = `items[${productIndex}][price]`;
        
        productItemsContainer.appendChild(newRow);

        const select2 = $(productSelect).select2({
            placeholder: 'Pilih Produk...',
            dropdownCssClass: 'select2-dropdown-clean',
            dropdownParent: $(productSelect).parent(),
            width: '100%'
        });

        // Set Existing Data
        if (item) {
            $(productSelect).val(item.product_id).trigger('change.select2');
            quantityInput.value = item.quantity;
            // Price might be stored in pivot table in real app, but here we use current price or product price
            const price = item.price_per_unit || 0; 
            priceDisplay.value = formatRupiah(price).replace('Rp', '').trim();
            priceRaw.value = price;
        }

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

        productIndex++;
        calculateTotals();
    }

    addProductBtn.addEventListener('click', () => addProductRow());
    
    // Load existing items
    if (existingItems && existingItems.length > 0) {
        existingItems.forEach(item => addProductRow(item));
    } else {
        addProductRow();
    }

    @if(session('success')) window.showToast("{{ session('success') }}", 'success'); @endif
    @if(session('error')) window.showToast("{{ session('error') }}", 'error'); @endif
});
</script>
@endpush
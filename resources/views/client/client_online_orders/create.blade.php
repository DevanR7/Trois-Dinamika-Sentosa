@extends('client.layouts.app')

@section('title', 'Buat Pesanan Baru')

@section('content')
<div class="max-w-5xl mx-auto space-y-6">

    {{-- Header --}}
    <div class="flex flex-col md:flex-row justify-between items-start md:items-center gap-4">
        <div>
            <h2 class="text-2xl font-bold text-slate-800 dark:text-slate-100 tracking-tight">Buat Pesanan Online</h2>
            <p class="text-sm text-slate-500 dark:text-slate-400 mt-1">Isi formulir di bawah untuk membuat pesanan baru secara mandiri.</p>
        </div>
    </div>

    {{-- Error Handling --}}
    @if ($errors->any())
        <div class="bg-red-50 text-red-700 p-4 rounded-lg border border-red-200">
            <ul class="list-disc list-inside text-sm">
                @foreach ($errors->all() as $error)<li>{{ $error }}</li>@endforeach
            </ul>
        </div>
    @endif
    @if (session('error'))
        <div class="bg-red-50 text-red-700 p-4 rounded-lg border border-red-200 flex items-center gap-2">
            <i class="material-icons text-base">error</i> {{ session('error') }}
        </div>
    @endif

    <form action="{{ route('client.client-orders.store') }}" method="POST" id="order-form">
        @csrf
        
        <div class="dashboard-card p-6 mb-6">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
                {{-- Nama Klien --}}
                <div>
                    <label class="block text-xs font-bold text-slate-500 uppercase tracking-wide mb-2">Nama Pemesan</label>
                    <div class="relative">
                        <span class="absolute inset-y-0 left-0 flex items-center pl-3 text-slate-400">
                            <i class="material-icons text-[18px]">person</i>
                        </span>
                        <input type="text" class="form-input pl-10 bg-slate-50 text-slate-500 cursor-not-allowed" value="{{ Auth::guard('client')->user()->client_name }}" readonly>
                    </div>
                </div>

                {{-- Tanggal Pesanan --}}
                <div>
                    <label for="order_date" class="block text-xs font-bold text-slate-500 uppercase tracking-wide mb-2">Tanggal Pesanan</label>
                    <div class="relative">
                        <span class="absolute inset-y-0 left-0 flex items-center pl-3 text-slate-400">
                            <i class="material-icons text-[18px]">event</i>
                        </span>
                        <input type="date" id="order_date" name="order_date" class="form-input pl-10" value="{{ old('order_date', now()->format('Y-m-d')) }}" required max="{{ now()->format('Y-m-d') }}">
                    </div>
                </div>
            </div>

            <div class="border-t border-slate-100 dark:border-slate-700 pt-6">
                <div class="flex items-center justify-between mb-4">
                    <h3 class="font-bold text-slate-800 dark:text-slate-100">Rincian Item</h3>
                    <button type="button" id="add-product-btn" class="text-xs font-bold text-indigo-600 hover:text-indigo-700 flex items-center gap-1 transition">
                        <i class="material-icons text-[16px]">add_circle</i> Tambah Item
                    </button>
                </div>

                <div class="overflow-hidden border border-slate-200 dark:border-slate-700 rounded-lg mb-4">
                    <table class="w-full text-left text-sm">
                        <thead class="bg-slate-50 dark:bg-slate-800 border-b border-slate-200 dark:border-slate-700 text-slate-500 font-semibold uppercase text-xs">
                            <tr>
                                <th class="p-3 w-[40%]">Produk</th>
                                <th class="p-3 w-[15%] text-center">Qty</th>
                                <th class="p-3 w-[20%] text-right">Harga</th>
                                <th class="p-3 w-[20%] text-right">Subtotal</th>
                                <th class="p-3 w-[5%] text-center"></th>
                            </tr>
                        </thead>
                        <tbody id="product-items" class="divide-y divide-slate-100 dark:divide-slate-700 bg-white dark:bg-slate-900">
                            {{-- Items injected by JS --}}
                        </tbody>
                    </table>
                </div>

                {{-- Empty State (Helper) --}}
                <div id="empty-cart-msg" class="text-center py-8 text-slate-400 border border-dashed border-slate-300 rounded-lg hidden">
                    <i class="material-icons text-4xl mb-2 opacity-50">shopping_cart</i>
                    <p class="text-sm">Belum ada produk yang ditambahkan.</p>
                </div>
            </div>
        </div>

        <div class="dashboard-card p-6">
            <div class="flex flex-col md:flex-row gap-8">
                {{-- Catatan --}}
                <div class="flex-1">
                    <label for="notes" class="block text-xs font-bold text-slate-500 uppercase tracking-wide mb-2">Catatan Tambahan (Opsional)</label>
                    <textarea name="notes" id="notes" class="form-textarea w-full" rows="3" placeholder="Instruksi khusus untuk pesanan ini...">{{ old('notes') }}</textarea>
                </div>

                {{-- Total --}}
                <div class="w-full md:w-1/3 flex flex-col justify-end items-end text-right">
                    <span class="text-sm text-slate-500 font-medium uppercase">Total Estimasi</span>
                    <h4 class="text-3xl font-bold text-indigo-600 dark:text-indigo-400 mt-1" id="grand-total">Rp 0</h4>
                    <p class="text-xs text-slate-400 mt-2">*Harga final akan dikonfirmasi oleh sales.</p>
                </div>
            </div>

            <div class="border-t border-slate-100 dark:border-slate-700 mt-6 pt-6 flex justify-end gap-3">
                <a href="{{ route('client.client-orders.index') }}" class="px-5 py-2.5 rounded-lg border border-slate-300 text-slate-600 font-bold hover:bg-slate-50 transition">Batal</a>
                <button type="submit" class="px-5 py-2.5 rounded-lg bg-indigo-600 text-white font-bold hover:bg-indigo-700 shadow-lg transition transform hover:-translate-y-0.5 flex items-center gap-2">
                    <i class="material-icons text-[18px]">send</i> Kirim Pesanan
                </button>
            </div>
        </div>
    </form>
</div>

{{-- Template Baris Item (Tailwind) --}}
<template id="product-row-template">
    <tr class="group hover:bg-slate-50 dark:hover:bg-slate-800/50 transition-colors">
        <td class="p-3">
            <select class="product-select w-full" required>
                <option value="" data-price="0" disabled selected>-- Pilih Produk --</option>
                @foreach ($products as $product)
                    <option value="{{ $product->product_id }}" data-price="{{ $product->selling_price ?? 0 }}">{{ $product->product_name }}</option>
                @endforeach
            </select>
            <input type="hidden" class="price-raw">
        </td>
        <td class="p-3">
            <input type="number" class="form-input text-center h-9 quantity" value="1" min="1" required>
        </td>
        <td class="p-3">
            <input type="text" class="form-input text-right h-9 bg-slate-50 text-slate-500 price-display border-slate-200" readonly>
        </td>
        <td class="p-3 text-right font-bold text-slate-700 dark:text-slate-200">
            <span class="subtotal">Rp 0</span>
        </td>
        <td class="p-3 text-center">
            <button type="button" class="remove-product-btn text-slate-400 hover:text-red-500 transition p-1 rounded-full hover:bg-red-50">
                <i class="material-icons text-[18px]">delete</i>
            </button>
        </td>
    </tr>
</template>
@endsection

@push('scripts')
{{-- Library Select2 --}}
<link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
<style>
    /* Custom Select2 untuk match Tailwind */
    .select2-container--bootstrap-5 .select2-selection {
        border-color: #e2e8f0 !important; 
        border-radius: 0.5rem !important;
        height: 38px !important; 
        font-size: 0.875rem !important;
        padding-top: 0.25rem;
    }
    .dark .select2-container--bootstrap-5 .select2-selection {
        background-color: #1e293b !important;
        border-color: #334155 !important;
        color: #e2e8f0 !important;
    }
</style>
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/select2-bootstrap-5-theme@1.3.0/dist/select2-bootstrap-5-theme.min.css" />
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>

<script>
    document.addEventListener('DOMContentLoaded', function () {
        const productItemsContainer = document.getElementById('product-items');
        const emptyMsg = document.getElementById('empty-cart-msg');
        const productRowTemplate = document.getElementById('product-row-template');
        const addProductBtn = document.getElementById('add-product-btn');
        let productIndex = 0;

        function formatRupiah(number) {
             if (isNaN(number)) return 'Rp 0';
            return new Intl.NumberFormat('id-ID', { style: 'currency', currency: 'IDR', minimumFractionDigits: 0 }).format(number);
        }

        function checkEmpty() {
            if (productItemsContainer.children.length === 0) {
                emptyMsg.classList.remove('hidden');
            } else {
                emptyMsg.classList.add('hidden');
            }
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

        function addProductRow(preselectedProductId = null) {
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
            checkEmpty();

            // Init Select2
            const select2 = $(productSelect).select2({
                placeholder: '-- Pilih Produk --',
                theme: 'bootstrap-5',
                dropdownParent: $(productSelect).parent(),
                width: '100%'
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
                checkEmpty();
            });

            if (preselectedProductId) {
                select2.val(preselectedProductId).trigger('change');
            } else {
                 select2.trigger('change');
            }
            productIndex++;
        }

        addProductBtn.addEventListener('click', () => addProductRow());
        
        // Tambah 1 baris kosong saat load
        addProductRow();
        calculateTotals();
    });
</script>
@endpush
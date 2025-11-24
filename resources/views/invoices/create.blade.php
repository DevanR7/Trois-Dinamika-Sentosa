@extends('layouts.app')

@section('title', 'Buat Invoice Baru')

@section('content')
<div class="max-w-7xl mx-auto">
    
    {{-- HEADER HALAMAN --}}
    <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center mb-6 gap-4">
        <div>
            <h2 class="text-2xl font-bold text-gray-900 tracking-tight">Buat Invoice Baru</h2>
            <p class="text-sm text-gray-500 mt-1">Buat tagihan penjualan untuk pelanggan.</p>
        </div>
        <a href="{{ route('invoices.index') }}" class="px-4 py-2 bg-white border border-gray-300 rounded-lg text-gray-700 text-sm font-medium hover:bg-gray-50 transition shadow-sm">
            Kembali
        </a>
    </div>

    {{-- ERROR ALERT --}}
    @if ($errors->any())
        <div class="mb-6 bg-red-50 border-l-4 border-red-500 p-4 rounded-r shadow-sm">
            <div class="flex">
                <div class="flex-shrink-0">
                    <i class="bi bi-exclamation-triangle-fill text-red-400 text-xl"></i>
                </div>
                <div class="ml-3">
                    <h3 class="text-sm font-bold text-red-800">Terdapat kesalahan input:</h3>
                    <ul class="mt-1 list-disc list-inside text-sm text-red-700">
                        @foreach ($errors->all() as $error) <li>{{ $error }}</li> @endforeach
                    </ul>
                </div>
            </div>
        </div>
    @endif

    <form action="{{ route('invoices.store') }}" method="POST" id="invoice-form">
        @csrf
        @if (isset($order))
            <input type="hidden" name="sales_order_id" value="{{ $order->order_id }}">
        @endif

        <div class="grid grid-cols-1 lg:grid-cols-12 gap-6">
            
            {{-- ===================================================
                 KOLOM KIRI: FORM DATA (Span 8)
                 =================================================== --}}
            <div class="lg:col-span-8 space-y-6">
                
                {{-- CARD 1: INFO UTAMA --}}
                <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
                    <div class="px-6 py-4 border-b border-gray-100 bg-gray-50 flex items-center gap-2">
                        <i class="bi bi-receipt text-indigo-500"></i>
                        <h3 class="text-sm font-bold text-gray-700 uppercase tracking-wider">Data Tagihan</h3>
                    </div>
                    <div class="p-6 grid grid-cols-1 md:grid-cols-2 gap-6">
                        
                        {{-- Pelanggan --}}
                        <div class="md:col-span-2">
                            <label for="client_id" class="block text-xs font-bold text-gray-700 uppercase mb-1">Pelanggan (Klien) <span class="text-red-500">*</span></label>
                            <select name="client_id" id="client_id" class="w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 text-sm" required>
                                <option value="" disabled selected>-- Pilih Klien --</option>
                                @foreach ($clients as $client)
                                    <option value="{{ $client->client_id }}" @selected(old('client_id', isset($order) ? $order->client_id : '') == $client->client_id)>
                                        {{ $client->client_name }}
                                    </option>
                                @endforeach
                            </select>
                            @if(isset($order)) <input type="hidden" name="client_id" value="{{ $order->client_id }}"> @endif
                        </div>

                        {{-- Tanggal Invoice --}}
                        <div>
                            <label for="order_date" class="block text-xs font-bold text-gray-700 uppercase mb-1">Tanggal Invoice</label>
                            <input type="date" name="order_date" id="order_date" value="{{ old('order_date', now()->format('Y-m-d')) }}" class="w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 text-sm" required>
                        </div>

                        {{-- Jatuh Tempo --}}
                        <div>
                            <label for="due_date" class="block text-xs font-bold text-gray-700 uppercase mb-1">Jatuh Tempo</label>
                            <input type="date" name="due_date" id="due_date" value="{{ old('due_date', now()->addDays(30)->format('Y-m-d')) }}" class="w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 text-sm" required>
                        </div>

                        {{-- Sales Person --}}
                        <div class="md:col-span-2">
                            <label for="user_id_sales" class="block text-xs font-bold text-gray-700 uppercase mb-1">Sales Person</label>
                            <select name="user_id_sales" id="user_id_sales" class="w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 text-sm">
                                <option value="">-- Umum / Tanpa Sales --</option>
                                @foreach ($salesUsers as $sales)
                                    <option value="{{ $sales->user_id }}" @selected(old('user_id_sales', isset($order) ? $order->user_id_sales : null) == $sales->user_id)>
                                        {{ $sales->full_name }} ({{ $sales->sales_code }})
                                    </option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                </div>

                {{-- CARD 2: RINCIAN PRODUK --}}
                <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
                    <div class="px-6 py-4 border-b border-gray-100 bg-gray-50 flex justify-between items-center">
                        <h3 class="text-sm font-bold text-gray-700 uppercase tracking-wider flex items-center gap-2">
                            <i class="bi bi-cart text-indigo-500"></i> Rincian Produk
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
                                    <th class="px-4 py-3 text-xs font-bold text-gray-500 uppercase text-right w-3/12">Harga (@)</th>
                                    <th class="px-4 py-3 text-xs font-bold text-gray-500 uppercase text-right w-2/12">Subtotal</th>
                                    <th class="px-4 py-3 w-10"></th>
                                </tr>
                            </thead>
                            <tbody id="product-items" class="divide-y divide-gray-100 bg-white">
                                {{-- JS Injects Rows Here --}}
                            </tbody>
                        </table>
                    </div>
                </div>

                {{-- CARD 3: BIAYA TAMBAHAN --}}
                <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
                    <div class="px-6 py-4 border-b border-gray-100 bg-gray-50 flex justify-between items-center">
                        <h3 class="text-sm font-bold text-gray-700 uppercase tracking-wider flex items-center gap-2">
                            <i class="bi bi-cash-stack text-green-600"></i> Biaya Tambahan (Opsional)
                        </h3>
                        <button type="button" id="add-cost-btn" class="inline-flex items-center px-3 py-1.5 bg-gray-100 text-gray-700 text-xs font-bold rounded-md hover:bg-gray-200 border border-gray-300 transition">
                            <i class="bi bi-plus-lg mr-1"></i> Tambah Biaya
                        </button>
                    </div>
                    <div class="overflow-x-auto">
                        <table class="w-full text-left border-collapse">
                            <tbody id="additional-cost-items" class="divide-y divide-gray-100 bg-white">
                                {{-- JS Injects Costs Here --}}
                            </tbody>
                        </table>
                    </div>
                </div>

                {{-- CARD 4: CATATAN --}}
                <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
                    <label for="notes" class="block text-xs font-bold text-gray-700 uppercase mb-2">Catatan Invoice</label>
                    <textarea name="notes" id="notes" rows="3" class="w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 text-sm bg-yellow-50/30" placeholder="Catatan untuk klien...">{{ old('notes') }}</textarea>
                </div>

            </div>

            {{-- ===================================================
                 KOLOM KANAN: SUMMARY (Span 4)
                 =================================================== --}}
            <div class="lg:col-span-4 space-y-6">
                
                <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6 sticky top-6 z-30">
                    <h3 class="text-sm font-bold text-gray-900 mb-4 flex items-center gap-2 border-b border-gray-100 pb-3">
                        <i class="bi bi-calculator text-indigo-500"></i> Ringkasan
                    </h3>

                    <div class="space-y-3 text-sm">
                        <div class="flex justify-between text-gray-600">
                            <span>Subtotal Produk</span>
                            <span class="font-medium text-gray-900" id="subtotal-display">Rp 0</span>
                        </div>

                        {{-- Diskon --}}
                        <div class="flex items-center justify-between gap-2">
                            <label for="discount_percentage" class="text-xs text-gray-500">Diskon Global (%)</label>
                            <input type="number" name="discount_percentage" id="discount_percentage" value="{{ old('discount_percentage', 0) }}" class="w-16 text-right text-xs border-gray-300 rounded py-1 focus:ring-indigo-500 focus:border-indigo-500" min="0" max="100">
                        </div>
                        <div class="flex justify-between text-red-500 text-xs">
                            <span>Potongan Diskon</span>
                            <span id="discount-amount-display">- Rp 0</span>
                        </div>

                        <div class="border-t border-dashed border-gray-200 my-2"></div>

                        <div class="flex justify-between text-gray-500 text-xs font-bold">
                            <span>Subtotal (Net)</span>
                            <span id="subtotal-after-discount">Rp 0</span>
                        </div>

                        {{-- Pajak --}}
                        <div class="mt-3">
                            <label class="block text-xs font-bold text-gray-500 uppercase mb-2">Pajak</label>
                            <div id="tax-options" class="space-y-1">
                                @forelse ($taxes as $tax)
                                    <label class="flex items-center space-x-2 cursor-pointer">
                                        <input type="checkbox" name="taxes[]" value="{{ $tax->id }}" data-rate="{{ $tax->rate }}" class="tax-checkbox rounded border-gray-300 text-indigo-600 focus:ring-indigo-500 h-4 w-4" @checked(in_array($tax->id, old('taxes', [])))>
                                        <span class="text-xs text-gray-700">{{ $tax->name }} ({{ $tax->rate }}%)</span>
                                    </label>
                                @empty
                                    <p class="text-xs text-gray-400 italic">Tidak ada data pajak aktif.</p>
                                @endforelse
                            </div>
                            {{-- Breakdown Pajak Dinamis --}}
                            <div id="tax-breakdown" class="mt-2 space-y-1"></div>
                        </div>

                        <div class="flex justify-between text-gray-600 mt-2">
                            <span>Biaya Tambahan</span>
                            <span id="total-additional-display">Rp 0</span>
                        </div>

                        <div class="border-t border-gray-300 my-4"></div>

                        <div class="flex justify-between items-center">
                            <span class="text-sm font-bold text-gray-900 uppercase">Total Akhir</span>
                            <span class="text-xl font-bold text-indigo-600" id="grand-total">Rp 0</span>
                        </div>
                    </div>

                    <div class="flex flex-col gap-3 mt-6">
                        <button type="submit" class="w-full py-3 bg-indigo-600 hover:bg-indigo-700 text-white text-sm font-bold rounded-lg shadow-md transition flex justify-center items-center gap-2 group">
                            <i class="bi bi-save group-hover:scale-110 transition-transform"></i> Simpan Invoice
                        </button>
                        <a href="{{ route('invoices.index') }}" class="block w-full py-3 bg-white border border-gray-300 text-gray-700 text-sm font-bold rounded-lg hover:bg-gray-50 transition text-center shadow-sm">
                            Batal
                        </a>
                    </div>
                </div>

            </div>
        </div>
    </form>
</div>

{{-- TEMPLATE ROW PRODUK --}}
<template id="product-row-template">
    <tr class="hover:bg-gray-50 transition-colors border-b border-gray-50 last:border-0 group">
        <td class="p-3 align-top">
            <select class="product-select table-input w-full text-sm" required>
                <option value="" data-price="0" disabled selected>-- Pilih Produk --</option>
                @foreach ($products as $product)
                    <option value="{{ $product->product_id }}" data-price="{{ $product->selling_price ?? 0 }}">
                        {{ $product->product_name }}
                    </option>
                @endforeach
            </select>
        </td>
        <td class="p-3 align-top">
            <input type="number" class="table-input quantity text-center w-full font-bold text-gray-700 border border-gray-300 rounded-md h-9 focus:ring-indigo-500 focus:border-indigo-500" value="1" min="1" required>
        </td>
        <td class="p-3 align-top">
            <div class="relative">
                {{-- Input TEXT untuk AutoNumeric --}}
                <input type="text" class="price-display block w-full pr-2 py-1.5 border border-gray-300 rounded-md text-right text-sm font-medium focus:ring-indigo-500 focus:border-indigo-500" required placeholder="0">
                <input type="hidden" class="price-raw" value="0">
            </div>
            <div class="mt-1 flex items-center">
                <input type="checkbox" class="update-master-check rounded border-gray-300 text-indigo-600 h-3 w-3 mr-1">
                <label class="text-[10px] text-gray-500 cursor-pointer">Update Master</label>
            </div>
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

{{-- TEMPLATE ROW BIAYA TAMBAHAN --}}
<template id="additional-cost-template">
    <tr class="hover:bg-gray-50 transition-colors border-b border-gray-50 last:border-0">
        <td class="p-2">
            <input type="text" class="cost-desc w-full border-gray-300 rounded-md text-sm focus:ring-indigo-500 focus:border-indigo-500" placeholder="Keterangan Biaya (Mis: Packing, Lembur)" required>
        </td>
        <td class="p-2 w-40">
            {{-- Input TEXT untuk AutoNumeric --}}
            <input type="text" class="cost-display w-full border-gray-300 rounded-md text-sm text-right focus:ring-indigo-500 focus:border-indigo-500" placeholder="0" required>
            <input type="hidden" class="cost-raw" value="0">
        </td>
        <td class="p-2 text-center w-10">
            <button type="button" class="text-gray-400 hover:text-red-500 remove-cost-btn">
                <i class="bi bi-x-lg"></i>
            </button>
        </td>
    </tr>
</template>

@endsection

@push("scripts")
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/autonumeric@4.6.0/dist/autoNumeric.min.js"></script> 

<script>
document.addEventListener('DOMContentLoaded', function () {
    // Init Select2
    $('#client_id').select2({ theme: 'bootstrap-5', width: '100%' });
    $('#user_id_sales').select2({ theme: 'bootstrap-5', width: '100%' });

    // Check if creating from order
    const orderItems = @json(isset($order) ? $order->items : []);
    const orderClientId = @json(isset($order) ? $order->client_id : null);

    if (orderClientId) {
        $('#client_id').val(orderClientId).trigger('change');
    }
    
    // Container Elements
    const productItemsContainer = document.getElementById('product-items');
    const additionalCostItemsContainer = document.getElementById('additional-cost-items');
    const productRowTemplate = document.getElementById('product-row-template');
    const costRowTemplate = document.getElementById('additional-cost-template');
    
    // Calc Elements
    const taxOptionsContainer = document.getElementById('tax-options');
    const discountInput = document.getElementById('discount_percentage');
    
    let productIndex = 0;
    let costIndex = 0;

    // Helper Formatting
    function formatRupiah(number) {
        return new Intl.NumberFormat('id-ID', { style: 'currency', currency: 'IDR', minimumFractionDigits: 0 }).format(number);
    }

    // Helper AutoNumeric
    function initAutoNumeric(element, hiddenElement) {
        const an = new AutoNumeric(element, { 
            decimalCharacter: ',', 
            digitGroupSeparator: '.', 
            decimalPlaces: 0, 
            minimumValue: '0',
            unformatOnSubmit: true 
        });

        element.addEventListener('autoNumeric:rawValueModified', e => {
            hiddenElement.value = e.detail.newRawValue;
            calculateTotals();
        });
        return an;
    }

    // --- CORE CALCULATION ---
    function calculateTotals() {
        // 1. Subtotal Barang
        let subtotalProducts = 0;
        productItemsContainer.querySelectorAll('tr').forEach((row) => {
            const price = parseFloat(row.querySelector('.price-raw').value) || 0;
            const quantity = parseInt(row.querySelector('.quantity').value) || 0;
            const subtotal = price * quantity;
            row.querySelector('.subtotal').textContent = formatRupiah(subtotal);
            subtotalProducts += subtotal;
        });

        // 2. Biaya Tambahan
        let totalAdditionalCosts = 0;
        additionalCostItemsContainer.querySelectorAll('tr').forEach((row) => {
            const amount = parseFloat(row.querySelector('.cost-raw').value) || 0;
            totalAdditionalCosts += amount;
        });

        // 3. Diskon
        const discountRate = parseFloat(discountInput.value) || 0;
        const discountAmount = subtotalProducts * (discountRate / 100);
        const subtotalAfterDiscount = subtotalProducts - discountAmount;

        // 4. Pajak
        let totalTaxAmount = 0;
        let taxHtml = '';
        taxOptionsContainer.querySelectorAll('.tax-checkbox:checked').forEach((checkbox) => {
            const rate = parseFloat(checkbox.dataset.rate) || 0;
            const name = checkbox.nextElementSibling.textContent.trim();
            const taxAmount = subtotalAfterDiscount * (rate / 100);
            totalTaxAmount += taxAmount;
            taxHtml += `<div class="flex justify-between text-xs text-gray-500"><span>+ ${name}</span> <span>${formatRupiah(taxAmount)}</span></div>`;
        });
        
        // 5. Grand Total
        const grandTotal = subtotalAfterDiscount + totalTaxAmount + totalAdditionalCosts;

        // 6. Update UI
        document.getElementById('subtotal-display').textContent = formatRupiah(subtotalProducts);
        document.getElementById('discount-amount-display').textContent = `(-) ${formatRupiah(discountAmount)}`;
        document.getElementById('subtotal-after-discount').textContent = formatRupiah(subtotalAfterDiscount);
        document.getElementById('tax-breakdown').innerHTML = taxHtml;
        document.getElementById('total-additional-display').textContent = `(+) ${formatRupiah(totalAdditionalCosts)}`;
        document.getElementById('grand-total').textContent = formatRupiah(grandTotal);
    }

    // --- ADD PRODUCT ROW ---
    function addProductRow(item = null) {
        const newRow = productRowTemplate.content.cloneNode(true).querySelector('tr');
        const productSelect = newRow.querySelector('.product-select');
        const quantityInput = newRow.querySelector('.quantity');
        const priceDisplayInput = newRow.querySelector('.price-display');
        const priceRawInput = newRow.querySelector('.price-raw');
        const updateMasterCheck = newRow.querySelector('.update-master-check');
        
        // Set Names Array
        productSelect.name = `products[${productIndex}][product_id]`;
        quantityInput.name = `products[${productIndex}][quantity]`;
        priceRawInput.name = `products[${productIndex}][custom_price]`; 
        updateMasterCheck.name = `products[${productIndex}][update_master_price]`;
        
        // Unique ID Label
        const checkId = `update_master_${productIndex}`;
        updateMasterCheck.id = checkId;
        newRow.querySelector('label').setAttribute('for', checkId);
        
        productItemsContainer.appendChild(newRow);

        // Init Plugins
        const select2 = $(productSelect).select2({ theme: 'bootstrap-5', dropdownParent: $(productSelect).parent(), placeholder: '-- Cari Produk --', width: '100%' });
        const anPrice = initAutoNumeric(priceDisplayInput, priceRawInput);

        // Event Listeners
        select2.on('change', function() {
            const selectedOption = this.options[this.selectedIndex];
            const basePrice = parseFloat(selectedOption.dataset.price) || 0;
            // Set price only if raw input is empty/zero (fresh row)
            if(priceRawInput.value == 0) {
                anPrice.set(basePrice);
                priceRawInput.value = basePrice;
            }
            calculateTotals();
        });

        quantityInput.addEventListener('input', calculateTotals);

        newRow.querySelector('.remove-product-btn').addEventListener('click', () => {
            select2.select2('destroy');
            newRow.remove();
            calculateTotals();
        });
        
        // Pre-fill Data (from Order)
        if (item) {
            $(productSelect).val(item.product_id).trigger('change.select2');
            quantityInput.value = item.quantity;
            anPrice.set(item.price_per_unit); 
            priceRawInput.value = item.price_per_unit;
        }
        
        productIndex++;
    }

    // --- ADD COST ROW ---
    function addCostRow() {
        const newRow = costRowTemplate.content.cloneNode(true).querySelector('tr');
        const descInput = newRow.querySelector('.cost-desc');
        const costDisplayInput = newRow.querySelector('.cost-display');
        const costRawInput = newRow.querySelector('.cost-raw');

        descInput.name = `additional_costs[${costIndex}][description]`;
        costRawInput.name = `additional_costs[${costIndex}][amount]`;

        additionalCostItemsContainer.appendChild(newRow);
        const anCost = initAutoNumeric(costDisplayInput, costRawInput);

        newRow.querySelector('.remove-cost-btn').addEventListener('click', () => {
            newRow.remove();
            calculateTotals();
        });
        
        costDisplayInput.addEventListener('autoNumeric:rawValueModified', calculateTotals);

        costIndex++;
    }

    // --- GLOBAL EVENTS ---
    document.getElementById('add-product-btn').addEventListener('click', () => addProductRow());
    document.getElementById('add-cost-btn').addEventListener('click', () => addCostRow());
    
    taxOptionsContainer.addEventListener('change', calculateTotals);
    discountInput.addEventListener('input', calculateTotals);

    // --- INIT LOADING ---
    if (orderItems.length > 0) {
        orderItems.forEach(item => addProductRow(item));
    } else {
        addProductRow(); // Empty row
    }
    calculateTotals();
});
</script>
@endpush
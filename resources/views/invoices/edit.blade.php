@extends('layouts.app')

@section('title', 'Edit Invoice')

@push('styles')
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
@endpush

@section('content')
<div class="max-w-7xl mx-auto pb-20 animate-enter">
    
    {{-- HEADER --}}
    <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center mb-8 gap-4">
        <div>
            <nav class="flex items-center gap-2 text-sm text-slate-500 mb-1">
                <a href="{{ route('invoices.index') }}" class="hover:text-indigo-600 transition-colors">Invoice</a>
                <span class="mx-2 text-slate-300">/</span>
                <span class="text-slate-800 font-semibold">Edit</span>
            </nav>
            <h1 class="text-2xl font-bold text-slate-800 tracking-tight flex items-center gap-3">
                Edit Invoice <span class="text-indigo-600 font-mono bg-indigo-50 px-2 rounded">{{ $invoice->invoice_number }}</span>
            </h1>
        </div>
        <div class="flex gap-3">
            @if($invoice->status == 'draft')
                {{-- Tombol Hapus Draft --}}
                <form action="{{ route('invoices.destroy', $invoice->invoice_id) }}" method="POST" class="delete-form">
                    @csrf @method('DELETE')
                    <button type="submit" 
                            data-title="Hapus Draft?" 
                            data-text="Data draft akan dihapus permanen." 
                            class="h-[48px] px-5 bg-red-50 border border-red-200 text-red-600 rounded-lg text-sm font-bold hover:bg-red-100 transition shadow-sm flex items-center justify-center gap-2">
                        <i class="material-icons text-[18px]">delete</i> Hapus
                    </button>
                </form>
            @endif
            
            <a href="{{ route('invoices.index') }}" 
               class="h-[48px] px-6 bg-white border border-slate-300 text-slate-600 rounded-lg text-sm font-bold hover:bg-slate-50 transition-all flex items-center justify-center gap-2 shadow-sm">
                <i class="material-icons text-[18px]">arrow_back</i> Kembali
            </a>
        </div>
    </div>

    {{-- WARNING JIKA STATUS BUKAN DRAFT --}}
    @if($invoice->status != 'draft')
        <div class="mb-6 bg-amber-50 border border-amber-200 rounded-lg p-4 flex items-center gap-3">
            <i class="material-icons text-amber-600 text-xl">warning</i>
            <div class="text-sm text-amber-800">
                <strong>Perhatian:</strong> Invoice ini sudah diterbitkan (Status: {{ ucfirst($invoice->status) }}). Perubahan data dapat mempengaruhi laporan keuangan.
            </div>
        </div>
    @endif

    <form action="{{ route('invoices.update', $invoice->invoice_id) }}" method="POST" id="invoice-form">
        @csrf
        @method('PUT')
        
        <div class="grid grid-cols-1 lg:grid-cols-12 gap-6">
            
            {{-- KOLOM KIRI --}}
            <div class="lg:col-span-8 space-y-6">
                
                {{-- CARD 1: INFO UTAMA --}}
                <div class="dashboard-card p-0 overflow-hidden shadow-sm">
                    <div class="px-6 py-4 border-b border-slate-100 bg-slate-50/50 flex items-center gap-3">
                        <div class="p-2 bg-indigo-50 rounded-lg text-indigo-600">
                            <i class="material-icons text-[20px]">edit_note</i>
                        </div>
                        <h3 class="text-sm font-bold text-slate-700 uppercase tracking-wide">Data Tagihan</h3>
                    </div>
                    <div class="p-6 grid grid-cols-1 md:grid-cols-2 gap-6">
                        
                        <div class="md:col-span-2">
                            <label for="client_id" class="block text-xs font-bold text-slate-500 uppercase mb-1.5">Pelanggan (Klien) <span class="text-red-500">*</span></label>
                            <select name="client_id" id="client_id" class="form-input select2-basic" required>
                                @foreach ($clients as $client)
                                    <option value="{{ $client->client_id }}" @selected(old('client_id', $invoice->client_id) == $client->client_id)>
                                        {{ $client->client_name }}
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        <div>
                            <label for="order_date" class="block text-xs font-bold text-slate-500 uppercase mb-1.5">Tanggal Invoice</label>
                            <input type="date" name="order_date" id="order_date" value="{{ old('order_date', $invoice->order_date->format('Y-m-d')) }}" class="form-input" required>
                        </div>

                        <div>
                            <label for="due_date" class="block text-xs font-bold text-slate-500 uppercase mb-1.5">Jatuh Tempo</label>
                            <input type="date" name="due_date" id="due_date" value="{{ old('due_date', $invoice->due_date ? $invoice->due_date->format('Y-m-d') : '') }}" class="form-input" required>
                        </div>

                        <div class="md:col-span-2">
                            <label for="user_id_sales" class="block text-xs font-bold text-slate-500 uppercase mb-1.5">Sales Person</label>
                            <select name="user_id_sales" id="user_id_sales" class="form-input select2-basic">
                                <option value="">-- Umum / Tanpa Sales --</option>
                                @foreach ($salesUsers as $sales)
                                    <option value="{{ $sales->user_id }}" @selected(old('user_id_sales', $invoice->user_id_sales) == $sales->user_id)>
                                        {{ $sales->full_name }} ({{ $sales->sales_code }})
                                    </option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                </div>

                {{-- CARD 2: RINCIAN PRODUK --}}
                <div class="dashboard-card p-0 overflow-hidden shadow-sm">
                    {{-- Header (Hapus Button dari sini) --}}
                    <div class="px-6 py-4 border-b border-slate-100 bg-slate-50/50 flex items-center gap-2">
                        <div class="p-2 bg-indigo-50 rounded-lg text-indigo-600">
                            <i class="material-icons text-[20px]">shopping_cart</i>
                        </div>
                        <h3 class="text-sm font-bold text-slate-700 uppercase tracking-wide">Rincian Produk</h3>
                    </div>
                    
                    {{-- Tabel --}}
                    <div class="overflow-x-auto">
                        <table class="dashboard-table min-w-full">
                            <thead class="bg-slate-50 border-b border-slate-200">
                                <tr>
                                    <th class="pl-6 w-5/12">Produk</th>
                                    <th class="text-center w-2/12">Qty</th>
                                    <th class="text-right w-2/12">Harga (@)</th>
                                    <th class="text-right w-2/12">Subtotal</th>
                                    <th class="w-10 pr-6"></th>
                                </tr>
                            </thead>
                            <tbody id="product-items" class="divide-y divide-slate-100 bg-white">
                                {{-- JS Inject Rows --}}
                            </tbody>
                        </table>
                    </div>

                    {{-- TOMBOL TAMBAH DI BAWAH (Full Width) --}}
                    <div class="p-4 bg-white border-t border-slate-100">
                        <button type="button" id="add-product-btn" 
                                class="w-full py-3 border-2 border-dashed border-slate-300 rounded-xl text-slate-500 font-bold text-sm hover:border-indigo-400 hover:text-indigo-600 hover:bg-indigo-50 transition-all flex items-center justify-center gap-2 group">
                            <i class="material-icons text-[20px] group-hover:scale-110 transition-transform">add_circle_outline</i>
                            Tambah Baris Produk
                        </button>
                    </div>
                </div>

                {{-- CARD 3: BIAYA TAMBAHAN --}}
                <div class="dashboard-card p-0 overflow-hidden shadow-sm">
                    <div class="px-6 py-4 border-b border-slate-100 bg-slate-50/50 flex justify-between items-center">
                        <h3 class="text-sm font-bold text-slate-700 uppercase tracking-wide flex items-center gap-2">
                            <i class="material-icons text-green-600 text-[18px]">attach_money</i> Biaya Tambahan
                        </h3>
                        <button type="button" id="add-cost-btn" class="h-[36px] px-4 bg-white border border-slate-200 text-slate-600 rounded-lg text-xs font-bold hover:bg-slate-50 transition flex items-center gap-1 shadow-sm">
                            <i class="material-icons text-[16px]">add</i> Tambah Biaya
                        </button>
                    </div>
                    <div class="overflow-x-auto">
                        <table class="dashboard-table min-w-full">
                            <tbody id="additional-cost-items" class="divide-y divide-slate-100 bg-white">
                                {{-- JS Inject Cost Rows --}}
                            </tbody>
                        </table>
                    </div>
                </div>

                {{-- CARD 4: CATATAN --}}
                <div class="dashboard-card p-6 shadow-sm">
                    <label for="notes" class="block text-xs font-bold text-slate-500 uppercase mb-2">Catatan Invoice</label>
                    <textarea name="notes" id="notes" rows="3" class="form-textarea bg-yellow-50/30" placeholder="Catatan untuk klien...">{{ old('notes', $invoice->notes) }}</textarea>
                </div>

            </div>

            {{-- KOLOM KANAN: SUMMARY --}}
            <div class="lg:col-span-4 space-y-6">
                
                <div class="dashboard-card p-6 shadow-lg sticky top-6 border-t-4 border-indigo-500">
                    <h3 class="card-title mb-6 border-b border-slate-100 pb-3 flex items-center gap-2">
                        <i class="material-icons text-indigo-600">calculate</i> Ringkasan
                    </h3>

                    <div class="space-y-3 text-sm">
                        <div class="flex justify-between text-slate-600">
                            <span>Subtotal Produk</span>
                            <span class="font-medium text-slate-900" id="subtotal-display">Rp 0</span>
                        </div>

                        {{-- Diskon --}}
                        <div class="flex items-center justify-between gap-2">
                            <label for="discount_percentage" class="text-xs text-slate-500">Diskon (%)</label>
                            <input type="number" name="discount_percentage" id="discount_percentage" value="{{ old('discount_percentage', $invoice->discount_percentage) }}" class="w-16 text-right text-xs border-slate-300 rounded form-input py-1 h-8" min="0" max="100">
                        </div>
                        <div class="flex justify-between text-red-500 text-xs font-medium">
                            <span>Potongan Diskon</span>
                            <span id="discount-amount-display">- Rp 0</span>
                        </div>

                        <div class="border-t border-dashed border-slate-200 my-2"></div>

                        <div class="flex justify-between text-slate-500 text-xs font-bold">
                            <span>Subtotal (Net)</span>
                            <span id="subtotal-after-discount">Rp 0</span>
                        </div>

                        {{-- Pajak --}}
                        <div class="mt-3">
                            <label class="block text-xs font-bold text-slate-500 uppercase mb-2">Pajak</label>
                            <div id="tax-options" class="space-y-2 bg-slate-50 p-3 rounded-lg border border-slate-100">
                                @php 
                                    $appliedTaxIds = $invoice->taxes->pluck('id')->toArray();
                                @endphp
                                @foreach ($taxes as $tax)
                                    <label class="flex items-center space-x-2 cursor-pointer">
                                        <input type="checkbox" name="taxes[]" value="{{ $tax->id }}" data-rate="{{ $tax->rate }}" class="tax-checkbox rounded border-slate-300 text-indigo-600 focus:ring-indigo-500 h-4 w-4" @checked(in_array($tax->id, old('taxes', $appliedTaxIds)))>
                                        <span class="text-xs text-slate-700 font-medium">{{ $tax->name }} ({{ $tax->rate }}%)</span>
                                    </label>
                                @endforeach
                            </div>
                            <div id="tax-breakdown" class="mt-2 space-y-1 pl-2"></div>
                        </div>

                        <div class="flex justify-between text-slate-600 mt-2">
                            <span>Biaya Tambahan</span>
                            <span class="font-medium text-slate-900" id="total-additional-display">Rp 0</span>
                        </div>

                        <div class="border-t border-slate-300 my-4 pt-2"></div>

                        <div class="flex justify-between items-center">
                            <span class="text-sm font-bold text-slate-900 uppercase">Total Akhir</span>
                            <span class="text-2xl font-bold text-indigo-600 font-mono tracking-tight" id="grand-total">Rp 0</span>
                        </div>
                    </div>

                    <div class="flex flex-col gap-3 mt-8">
                        <button type="submit" class="w-full h-[48px] bg-indigo-600 hover:bg-indigo-700 text-white text-sm font-bold rounded-lg shadow-md transition-all flex justify-center items-center gap-2 group hover:-translate-y-0.5">
                            <i class="material-icons text-[20px] group-hover:scale-110 transition-transform">check_circle</i> Update Invoice
                        </button>
                        <a href="{{ route('invoices.index') }}" class="block w-full h-[48px] bg-white border border-slate-300 text-slate-700 text-sm font-bold rounded-lg hover:bg-slate-50 transition-all flex justify-center items-center shadow-sm">
                            Batal
                        </a>
                    </div>
                </div>

            </div>
        </div>
    </form>
</div>

{{-- TEMPLATE ROW (Sama dengan Create) --}}
<template id="product-row-template">
    <tr class="hover:bg-slate-50 transition-colors border-b border-gray-50 last:border-0 group">
        <td class="pl-6 py-3 align-top">
            <select class="product-select form-input w-full text-sm" required>
                <option value="" data-price="0" disabled selected>-- Pilih --</option>
                @foreach ($products as $product)
                    <option value="{{ $product->product_id }}" data-price="{{ $product->selling_price ?? 0 }}">
                        {{ $product->product_name }}
                    </option>
                @endforeach
            </select>
        </td>
        <td class="py-3 align-top">
            <input type="number" class="form-input quantity text-center w-full font-bold text-gray-700 h-9" value="1" min="1" required>
        </td>
        <td class="py-3 align-top">
            <div class="relative">
                <input type="text" class="price-display form-input input-currency block w-full pr-2 text-right text-sm font-medium" required placeholder="0">
                <input type="hidden" class="price-raw" value="0">
            </div>
            <div class="mt-1 flex items-center pl-1">
                <input type="checkbox" class="update-master-check rounded border-slate-300 text-indigo-600 h-3 w-3 mr-1">
                <label class="text-[10px] text-slate-500 cursor-pointer select-none">Update Master</label>
            </div>
        </td>
        <td class="py-3 align-top text-right font-bold text-gray-900 text-sm align-middle font-mono">
            <span class="subtotal">Rp 0</span>
        </td>
        <td class="pr-6 py-3 align-top text-center align-middle">
            <button type="button" class="text-slate-400 hover:text-red-500 hover:bg-red-50 rounded p-1 transition remove-product-btn">
                <i class="material-icons text-[18px]">delete</i>
            </button>
        </td>
    </tr>
</template>

<template id="additional-cost-template">
    <tr class="hover:bg-slate-50 transition-colors border-b border-gray-50 last:border-0">
        <td class="pl-6 py-2">
            <input type="text" class="cost-desc form-input w-full text-sm" placeholder="Keterangan Biaya" required>
        </td>
        <td class="py-2 w-40">
            <input type="text" class="cost-display form-input input-currency w-full text-sm text-right" placeholder="0" required>
            <input type="hidden" class="cost-raw" value="0">
        </td>
        <td class="pr-6 py-2 text-center w-10">
            <button type="button" class="text-slate-400 hover:text-red-500 remove-cost-btn">
                <i class="material-icons text-[18px]">close</i>
            </button>
        </td>
    </tr>
</template>

@endsection

@push("scripts")
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/autonumeric@4.6.0/dist/autoNumeric.min.js"></script> 
<script>
document.addEventListener('DOMContentLoaded', function () {
    // Init Select2
    $('.select2-basic').select2({ placeholder: '-- Pilih --', width: '100%', dropdownCssClass: 'select2-dropdown-clean' });

    // DATA EXISTING
    // Pastikan data di-pass dari controller dalam format yang benar (misal JSON)
    const existingItems = @json($invoice->items);
    const existingCosts = @json($invoice->additionalCosts);

    // Variables
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
        const an = new AutoNumeric(element, { decimalCharacter: ',', digitGroupSeparator: '.', decimalPlaces: 0, minimumValue: '0' });
        element.addEventListener('autoNumeric:rawValueModified', e => {
            hiddenElement.value = e.detail.newRawValue;
            calculateTotals();
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
            taxHtml += `<div class="flex justify-between text-xs text-slate-500"><span>+ ${name}</span> <span>${formatRupiah(taxAmount)}</span></div>`;
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
        
        // Unique ID for label
        const checkId = `update_master_${productIndex}`;
        updateMasterCheck.id = checkId;
        newRow.querySelector('label').setAttribute('for', checkId);
        
        productItemsContainer.appendChild(newRow);

        const select2 = $(productSelect).select2({ 
            theme: 'bootstrap-5', 
            // dropdownParent dihapus agar menempel ke body
            placeholder: '-- Pilih --', 
            width: '100%',
            dropdownCssClass: 'select2-dropdown-clean'
        });

        const anPrice = initAutoNumeric(priceDisplayInput, priceRawInput);

        select2.on('change', function() {
            const selectedOption = this.options[this.selectedIndex];
            const basePrice = parseFloat(selectedOption.dataset.price) || 0;
            // Hanya update harga jika input harga masih kosong (kasus tambah baru)
            // atau jika user belum mengeditnya secara manual (logic bisa disesuaikan)
            if(priceRawInput.value == 0 && !item) { 
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

        // Populate Data (Untuk Edit)
        if (item) {
            $(productSelect).val(item.product_id).trigger('change'); 
            quantityInput.value = item.quantity;
            anPrice.set(item.price_per_unit); 
            priceRawInput.value = item.price_per_unit;
        }
        
        productIndex++;
    }

    function addCostRow(cost = null) {
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

        if (cost) {
            descInput.value = cost.description;
            anCost.set(cost.amount);
            costRawInput.value = cost.amount;
        }

        costIndex++;
    }

    document.getElementById('add-product-btn').addEventListener('click', () => addProductRow());
    document.getElementById('add-cost-btn').addEventListener('click', () => addCostRow());
    taxOptionsContainer.addEventListener('change', calculateTotals);
    discountInput.addEventListener('input', calculateTotals);

    // Load Existing Items
    if (existingItems && existingItems.length > 0) {
        existingItems.forEach(item => addProductRow(item));
    } else {
        addProductRow(); // Blank row if empty
    }

    // Load Existing Costs
    if (existingCosts && existingCosts.length > 0) {
        existingCosts.forEach(cost => addCostRow(cost));
    }
    
    calculateTotals();
    
    // Notifications
    @if(session('success')) window.showToast("{{ session('success') }}", 'success'); @endif
    @if(session('error')) window.showToast("{{ session('error') }}", 'error'); @endif
});
</script>
@endpush
@extends('admin.layouts.app')

@section('title', 'Edit Invoice')

@push('styles')
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
@endpush

@section('content')
<div class="max-w-full mx-auto pb-20 animate-enter">
    
    {{-- HEADER --}}
    <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center mb-8 gap-4">
        <div>
            <nav class="flex items-center gap-2 text-sm text-slate-500 mb-1">
                <a href="{{ route('admin.invoices.index') }}" class="hover:text-indigo-600 transition-colors">Invoice</a>
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
            
            <a href="{{ route('admin.invoices.index') }}" 
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

    {{-- BLOKIR JIKA ADA RETUR --}}
    @if($invoice->returns()->exists())
        <div class="mb-6 bg-red-50 border-l-4 border-red-500 p-4 rounded-md shadow-sm">
            <div class="flex items-start gap-3">
                <i class="material-icons text-red-600 text-xl">block</i>
                <div>
                    <h3 class="text-sm font-bold text-red-800">Invoice Terkunci</h3>
                    <p class="text-sm text-red-700 mt-1">
                        Invoice ini memiliki data <strong>Retur Penjualan</strong>. 
                        Anda tidak dapat mengedit detail item. Silakan batalkan retur terlebih dahulu jika ingin melakukan perubahan.
                    </p>
                </div>
            </div>
        </div>
        {{-- Script Pengunci Form --}}
        <script>
            document.addEventListener('DOMContentLoaded', function() {
                const formElements = document.querySelectorAll('#invoice-form input, #invoice-form select, #invoice-form textarea, #invoice-form button[type="submit"], #add-product-btn, #add-cost-btn');
                formElements.forEach(el => {
                    el.disabled = true;
                    el.classList.add('opacity-50', 'cursor-not-allowed');
                });
            });
        </script>
    @endif

    <form action="{{ route('admin.invoices.update', $invoice->invoice_id) }}" method="POST" id="invoice-form">
        @csrf
        @method('PUT')
        
        <div class="space-y-6">
            
            {{-- 1. INFO INVOICE (CARD ATAS) --}}
            <div class="dashboard-card p-0 overflow-hidden shadow-sm">
                <div class="px-6 py-4 border-b border-slate-100 bg-slate-50/50 flex items-center gap-3">
                    <div class="p-2 bg-indigo-50 rounded-lg text-indigo-600">
                        <i class="material-icons text-[20px]">edit_note</i>
                    </div>
                    <h3 class="text-sm font-bold text-slate-700 uppercase tracking-wide">Data Tagihan</h3>
                </div>
                <div class="p-6 grid grid-cols-1 md:grid-cols-12 gap-6">
                    
                    <div class="md:col-span-6">
                        <label for="client_id" class="block text-xs font-bold text-slate-500 uppercase mb-1.5">Pelanggan (Klien) <span class="text-red-500">*</span></label>
                        <select name="client_id" id="client_id" class="form-input select2-basic" required>
                            @foreach ($clients as $client)
                                <option value="{{ $client->client_id }}" @selected(old('client_id', $invoice->client_id) == $client->client_id)>
                                    {{ $client->client_name }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="md:col-span-3">
                        <label for="order_date" class="block text-xs font-bold text-slate-500 uppercase mb-1.5">Tanggal Invoice</label>
                        <input type="date" name="order_date" id="order_date" value="{{ old('order_date', $invoice->order_date->format('Y-m-d')) }}" class="form-input" required>
                    </div>

                    <div class="md:col-span-3">
                        <label for="due_date" class="block text-xs font-bold text-slate-500 uppercase mb-1.5">Jatuh Tempo</label>
                        <input type="date" name="due_date" id="due_date" value="{{ old('due_date', $invoice->due_date ? $invoice->due_date->format('Y-m-d') : '') }}" class="form-input" required>
                    </div>

                    <div class="md:col-span-12">
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

            {{-- 2. ITEM PRODUK (CARD TENGAH) --}}
            <div class="dashboard-card p-0 overflow-hidden shadow-sm">
                <div class="px-6 py-4 border-b border-slate-100 bg-slate-50/50 flex justify-between items-center">
                    <div class="flex items-center gap-2">
                        <i class="material-icons text-indigo-600 text-[20px]">shopping_cart</i> 
                        <h3 class="text-sm font-bold text-slate-700 uppercase tracking-wide">Rincian Produk</h3>
                    </div>
                </div>
                
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

                <div class="p-4 bg-white border-t border-slate-100">
                    <button type="button" id="add-product-btn" 
                            class="w-full py-3 border-2 border-dashed border-slate-300 rounded-xl text-slate-500 font-bold text-sm hover:border-indigo-400 hover:text-indigo-600 hover:bg-indigo-50 transition-all flex items-center justify-center gap-2 group">
                        <i class="material-icons text-[20px] group-hover:scale-110 transition-transform">add_circle_outline</i>
                        Tambah Baris Produk
                    </button>
                </div>
            </div>
            
            {{-- 3. BIAYA TAMBAHAN --}}
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

            {{-- 4. RINGKASAN & TOTAL (CARD BAWAH) --}}
            <div class="dashboard-card p-6 border-t-4 border-indigo-500">
                <div class="grid grid-cols-1 lg:grid-cols-2 gap-10">
                    
                    {{-- Kolom Kiri: Catatan --}}
                    <div class="space-y-6">
                        <div>
                            <label class="block text-[11px] font-bold text-slate-500 uppercase mb-2 ml-1">Catatan Invoice</label>
                            <textarea name="notes" id="notes" rows="4" class="form-textarea w-full bg-yellow-50/30 border-yellow-100 focus:border-yellow-400 focus:ring-yellow-200" placeholder="Catatan untuk klien...">{{ old('notes', $invoice->notes) }}</textarea>
                        </div>
                    </div>

                    {{-- Kolom Kanan: Kalkulasi --}}
                    <div class="bg-slate-50/50 rounded-xl p-6 border border-slate-200 space-y-4">
                        <h4 class="text-xs font-bold text-slate-500 uppercase tracking-wider border-b border-slate-200 pb-2 mb-2">Kalkulasi Biaya</h4>

                        <div class="flex justify-between text-sm text-slate-600">
                            <span>Subtotal Produk</span>
                            <span class="font-medium text-slate-900" id="subtotal-display">Rp 0</span>
                        </div>

                        {{-- Diskon --}}
                        <div class="flex items-center justify-between gap-4">
                            <label for="discount_percentage" class="text-xs font-bold text-slate-500 uppercase">Diskon (%)</label>
                            <input type="number" name="discount_percentage" id="discount_percentage" value="{{ old('discount_percentage', $invoice->discount_percentage) }}" class="w-20 text-right text-xs border-slate-300 rounded form-input h-8" min="0" max="100">
                        </div>
                        <div class="flex justify-between text-xs text-red-500 font-medium">
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
                            <div id="tax-options" class="space-y-2 bg-white p-3 rounded border border-slate-100">
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

                        <div class="border-t-2 border-slate-800 my-3"></div>

                        <div class="flex justify-between items-center">
                            <span class="text-sm font-bold text-slate-900 uppercase">Total Akhir</span>
                            <span class="text-2xl font-bold text-indigo-600 font-mono tracking-tight" id="grand-total">Rp 0</span>
                        </div>
                    </div>
                </div>

                <div class="flex justify-end gap-4 mt-8 pt-6 border-t border-slate-100">
                    <a href="{{ route('admin.invoices.index') }}" class="px-6 py-3 bg-white border border-slate-300 text-slate-600 rounded-lg text-sm font-bold hover:bg-slate-50 transition shadow-sm">
                         Batal
                    </a>
                    <button type="submit" id="submit-btn" class="px-8 py-3 bg-indigo-600 hover:bg-indigo-700 text-white rounded-lg text-sm font-bold shadow-lg hover:shadow-xl transition transform hover:-translate-y-0.5 flex items-center gap-2">
                        <i class="material-icons text-lg">check_circle</i> Update Invoice
                    </button>
                </div>
            </div>

        </div>
    </form>
</div>

{{-- TEMPLATE ROW PRODUK --}}
<template id="product-row-template">
    <tr class="hover:bg-slate-50 transition-colors border-b border-gray-50 last:border-0 group">
        <td class="pl-6 py-3 align-top">
            <select class="product-select text-sm" style="width: 100%;" required>
                <option value="" data-price="0" disabled selected>-- Pilih --</option>
                @foreach ($products as $product)
                    <option value="{{ $product->product_id }}" data-price="{{ $product->selling_price ?? 0 }}">
                        {{ $product->product_name }}
                    </option>
                @endforeach
            </select>
        </td>
        <td class="py-3 align-top">
            <input type="number" class="form-input quantity text-center w-full font-bold text-gray-700 h-9" value="1" min="0.01" step="0.01" required>
        </td>
        {{-- KOLOM HARGA (FIX: Tanpa class 'input-currency') --}}
        <td class="py-3 align-top">
            <div class="relative flex items-center">
                <div class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3 z-10">
                    <span class="text-slate-500 text-xs font-bold">Rp</span>
                </div>
                <input type="text" class="price-display form-input block w-full pl-9 pr-2 text-right text-sm font-medium h-9 focus:ring-1 focus:ring-indigo-500" required placeholder="0">
                <input type="hidden" class="price-raw" value="0">
            </div>
            <div class="mt-1 flex items-center pl-1 gap-2">
                 <label class="flex items-center gap-1 cursor-pointer">
                    <input type="checkbox" class="update-master-check h-3 w-3 rounded border-slate-300 text-indigo-600" value="1">
                    <span class="text-[9px] text-slate-500 italic">Update Master</span>
                 </label>
            </div>
        </td>
        <td class="py-3 align-top text-right font-bold text-gray-900 text-sm align-middle font-mono">
            <span class="subtotal">Rp 0</span>
        </td>
        <td class="pr-6 py-3 align-top text-center align-middle">
            <button type="button" class="remove-product-btn text-slate-400 hover:text-red-500 hover:bg-red-50 rounded p-1 transition">
                <i class="material-icons text-[18px]">delete</i>
            </button>
        </td>
    </tr>
</template>

{{-- TEMPLATE ROW BIAYA TAMBAHAN --}}
<template id="additional-cost-template">
    <tr class="hover:bg-slate-50 transition-colors border-b border-gray-50 last:border-0">
        <td class="pl-6 py-2">
            <input type="text" class="cost-desc form-input w-full text-sm" placeholder="Keterangan Biaya (Mis: Packing)" required>
        </td>
        {{-- KOLOM BIAYA (FIX: Tanpa class 'input-currency') --}}
        <td class="py-2 w-40">
            <div class="relative flex items-center">
                <div class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3 z-10">
                    <span class="text-slate-500 text-[10px] font-bold">Rp</span>
                </div>
                <input type="text" class="cost-display form-input w-full pl-8 text-xs text-right" placeholder="0" required>
                <input type="hidden" class="cost-raw" value="0">
            </div>
        </td>
        <td class="pr-6 py-2 text-center w-10">
            <button type="button" class="remove-cost-btn text-slate-400 hover:text-red-500 transition p-1 rounded-full hover:bg-red-50">
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
    // Init Select2 (Header only)
    $('.select2-basic').select2({ placeholder: '-- Pilih --', width: '100%', dropdownCssClass: 'select2-dropdown-clean' });

    // DATA EXISTING
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

    // FUNGSI INIT AUTONUMERIC LOKAL (Tanpa Symbol Global)
    function initAutoNumeric(element, hiddenElement) {
        const an = new AutoNumeric(element, { 
            decimalCharacter: ',', 
            digitGroupSeparator: '.', 
            decimalPlaces: 0, 
            minimumValue: '0',
            currencySymbol: '', // Symbol dikosongkan karena sudah ada di HTML
            unformatOnSubmit: true
        });
        
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
            const quantity = parseFloat(row.querySelector('.quantity').value) || 0; 
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
            const name = checkbox.nextElementSibling.innerText.trim(); 
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
        
        const checkId = `update_master_${productIndex}`;
        updateMasterCheck.id = checkId;
        
        productItemsContainer.appendChild(newRow);

        const select2 = $(productSelect).select2({ 
            placeholder: '-- Pilih --', 
            width: '100%', 
            dropdownCssClass: 'select2-dropdown-clean'
        });

        // Init AutoNumeric Manual
        const anPrice = initAutoNumeric(priceDisplayInput, priceRawInput);

        select2.on('change', function() {
            const selectedOption = this.options[this.selectedIndex];
            const basePrice = parseFloat(selectedOption.dataset.price) || 0;
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
        
        if (item) {
            $(productSelect).val(item.product_id).trigger('change'); 
            setTimeout(() => {
                quantityInput.value = item.quantity;
                anPrice.set(item.price_per_unit); 
                priceRawInput.value = item.price_per_unit;
                calculateTotals();
            }, 50);
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
        
        // Init AutoNumeric Manual
        const anCost = initAutoNumeric(costDisplayInput, costRawInput);

        newRow.querySelector('.remove-cost-btn').addEventListener('click', () => {
            newRow.remove();
            calculateTotals();
        });
        
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

    if (existingItems && existingItems.length > 0) {
        existingItems.forEach(item => addProductRow(item));
    } else {
        addProductRow(); 
    }

    if (existingCosts && existingCosts.length > 0) {
        existingCosts.forEach(cost => addCostRow(cost));
    }
    
    setTimeout(calculateTotals, 500);
});
</script>
@endpush
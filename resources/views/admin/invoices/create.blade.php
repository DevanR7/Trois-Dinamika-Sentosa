@extends('admin.layouts.app')

@section('title', 'Buat Invoice Baru')

@section('content')
<div class="max-w-full mx-auto pb-20 animate-enter">
    
    {{-- HEADER --}}
    <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center mb-6 gap-4">
        <div>
            <div class="flex items-center gap-2 text-xs font-bold text-slate-400 uppercase tracking-wider mb-1">
                <a href="{{ route('admin.invoices.index') }}" class="hover:text-indigo-600 transition">Invoice</a>
                <i class="material-icons text-[12px]">chevron_right</i>
                <span class="text-slate-600">Baru</span>
            </div>
            <h1 class="text-2xl font-bold text-slate-800 tracking-tight">Buat Invoice Baru</h1>
        </div>
        <a href="{{ route('admin.invoices.index') }}" 
           class="inline-flex items-center px-4 py-2 bg-white border border-slate-300 text-slate-600 rounded-lg text-sm font-bold hover:bg-slate-50 transition-all shadow-sm">
            <i class="material-icons text-sm mr-2">arrow_back</i> Kembali
        </a>
    </div>

    {{-- ERROR ALERT --}}
    @if ($errors->any())
        <div class="mb-6 bg-red-50 border border-red-200 rounded-xl p-4 flex items-start gap-3">
            <i class="material-icons text-red-500 text-xl">error</i>
            <div>
                <h3 class="text-sm font-bold text-red-800">Terdapat kesalahan input:</h3>
                <ul class="mt-1 list-disc list-inside text-sm text-red-700">
                    @foreach ($errors->all() as $error) <li>{{ $error }}</li> @endforeach
                </ul>
            </div>
        </div>
    @endif

    <form action="{{ route('admin.invoices.store') }}" method="POST" id="invoice-form">
        @csrf
        
        {{-- Jika create dari Sales Order --}}
        @if (isset($order))
            <input type="hidden" name="sales_order_id" value="{{ $order->order_id }}">
        @endif

        <div class="space-y-6">
            
            {{-- 1. INFO INVOICE (CARD ATAS) --}}
            <div class="dashboard-card p-0 overflow-hidden shadow-sm">
                <div class="bg-slate-50/50 px-6 py-4 border-b border-slate-100 flex items-center gap-2">
                    <i class="material-icons text-indigo-500 text-sm">receipt</i>
                    <h3 class="text-sm font-bold text-slate-700 uppercase tracking-wider">Data Tagihan & Klien</h3>
                </div>
                <div class="p-6 grid grid-cols-1 md:grid-cols-12 gap-6">
                    
                    {{-- PILIH KLIEN --}}
                    <div class="md:col-span-6">
                        <label class="block text-[11px] font-bold text-slate-500 uppercase mb-2 ml-1">Pelanggan (Klien) <span class="text-red-500">*</span></label>
                        <select name="client_id" id="client_id" class="w-full select2-basic" required>
                            <option value="" disabled selected>-- Pilih Klien --</option>
                            @foreach ($clients as $client)
                                <option value="{{ $client->client_id }}" @selected(old('client_id', isset($order) ? $order->client_id : '') == $client->client_id)>
                                    {{ $client->client_name }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    
                    {{-- TANGGAL INVOICE --}}
                    <div class="md:col-span-3">
                        <label class="block text-[11px] font-bold text-slate-500 uppercase mb-2 ml-1">Tanggal Invoice</label>
                        <input type="date" name="order_date" id="order_date" value="{{ old('order_date', now()->format('Y-m-d')) }}" class="form-input" required>
                    </div>
                    
                    {{-- JATUH TEMPO --}}
                    <div class="md:col-span-3">
                        <label class="block text-[11px] font-bold text-slate-500 uppercase mb-2 ml-1">Jatuh Tempo</label>
                        <input type="date" name="due_date" id="due_date" value="{{ old('due_date', now()->addDays(30)->format('Y-m-d')) }}" class="form-input" required>
                    </div>

                    {{-- SALES PERSON --}}
                    <div class="md:col-span-12">
                        <label class="block text-[11px] font-bold text-slate-500 uppercase mb-2 ml-1">Sales Person</label>
                        <select name="user_id_sales" id="user_id_sales" class="w-full select2-basic">
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

            {{-- 2. ITEM PRODUK (CARD TENGAH) --}}
            <div class="dashboard-card p-0 overflow-hidden shadow-sm">
                <div class="px-6 py-4 border-b border-slate-100 bg-slate-50/50 flex justify-between items-center">
                    <div class="flex items-center gap-2">
                        <i class="material-icons text-indigo-500 text-sm">shopping_cart</i>
                        <h3 class="text-sm font-bold text-slate-700 uppercase tracking-wider">Daftar Produk</h3>
                    </div>
                </div>
                
                <div class="overflow-x-auto">
                    <table class="dashboard-table w-full">
                        <thead>
                            <tr>
                                <th class="min-w-[300px] pl-6">Produk</th>
                                <th class="w-24 text-center">Qty</th>
                                <th class="w-40 text-right">Harga Satuan (@)</th>
                                <th class="w-40 text-right">Subtotal</th>
                                <th class="w-10 pr-6"></th>
                            </tr>
                        </thead>
                        <tbody id="product-items">
                            {{-- JS Inject Rows Here --}}
                        </tbody>
                    </table>
                </div>

                <div class="p-4 bg-slate-50 border-t border-slate-100 text-center">
                    <button type="button" id="add-product-btn" class="inline-flex items-center px-6 py-3 bg-white border border-indigo-200 text-indigo-600 text-sm font-bold rounded-full hover:bg-indigo-50 transition shadow-sm uppercase tracking-wide hover:shadow-md transform hover:-translate-y-0.5">
                        <i class="material-icons text-lg mr-2">add_circle_outline</i> Tambah Baris Produk
                    </button>
                </div>
            </div>
            
            {{-- 3. BIAYA TAMBAHAN --}}
            <div class="dashboard-card p-0 overflow-hidden shadow-sm">
                <div class="px-6 py-4 border-b border-slate-100 bg-slate-50/50 flex justify-between items-center">
                    <h3 class="text-xs font-bold text-slate-500 uppercase tracking-wider flex items-center gap-2">
                        <i class="material-icons text-green-600 text-sm">attach_money</i> Biaya Tambahan
                    </h3>
                    <button type="button" id="add-cost-btn" class="inline-flex items-center px-3 py-1.5 bg-green-50 text-green-700 text-xs font-bold rounded-lg hover:bg-green-100 border border-green-200 transition">
                        <i class="material-icons text-sm mr-1">add</i> Tambah Biaya
                    </button>
                </div>
                <div class="overflow-x-auto">
                    <table class="dashboard-table w-full">
                         <tbody id="additional-cost-items">
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
                            <textarea name="notes" id="notes" rows="4" class="form-textarea w-full bg-yellow-50/30 border-yellow-100 focus:border-yellow-400 focus:ring-yellow-200" placeholder="Catatan untuk klien...">{{ old('notes') }}</textarea>
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
                            <input type="number" name="discount_percentage" id="discount_percentage" value="{{ old('discount_percentage', 60) }}" class="w-20 text-right text-xs border-slate-300 rounded form-input h-8" min="0" max="100">
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
                        <div class="mt-2">
                            <label class="block text-[10px] font-bold text-slate-400 uppercase mb-1">Pajak</label>
                            <div id="tax-options" class="space-y-2 bg-white p-3 rounded border border-slate-100">
                                @foreach (\App\Models\Tax::where('is_active', true)->get() as $tax)
                                    <label class="flex items-center space-x-2 cursor-pointer">
                                        <input type="checkbox" name="taxes[]" value="{{ $tax->id }}" data-rate="{{ $tax->rate }}" class="tax-checkbox rounded border-slate-300 text-indigo-600 focus:ring-indigo-500 h-4 w-4" @checked(in_array($tax->id, old('taxes', [])))>
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
                        <i class="material-icons text-lg">save</i> Simpan Invoice
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
                <option value="" data-price="0" disabled selected>-- Pilih Produk --</option>
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
        
        {{-- KOLOM HARGA (FIX BUG HOVER) --}}
        <td class="py-3 align-top">
            <div class="relative flex items-center">
                {{-- Label Rp --}}
                <div class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3 z-10">
                    <span class="text-slate-500 text-xs font-bold">Rp</span>
                </div>
                {{-- Input tanpa class 'input-currency' untuk kontrol manual JS --}}
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

@push('scripts')
{{-- Load Library (CDN) --}}
<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/autonumeric@4.6.0/dist/autoNumeric.min.js"></script>

<script>
document.addEventListener('DOMContentLoaded', function () {
    
    // Init Select2 Global (untuk Header)
    $('.select2-basic').select2({ 
        placeholder: '-- Pilih --', 
        width: '100%', 
        dropdownCssClass: 'select2-dropdown-clean' 
    });

    // Variables
    const productItemsContainer = document.getElementById('product-items');
    const additionalCostItemsContainer = document.getElementById('additional-cost-items');
    const productRowTemplate = document.getElementById('product-row-template');
    const costRowTemplate = document.getElementById('additional-cost-template');
    const taxOptionsContainer = document.getElementById('tax-options');
    const discountInput = document.getElementById('discount_percentage');
    
    let productIndex = 0;
    let costIndex = 0;

    // Helper
    function formatRupiah(number) {
        return new Intl.NumberFormat('id-ID', { style: 'currency', currency: 'IDR', minimumFractionDigits: 0 }).format(number);
    }

    // Init AutoNumeric Manual (Tanpa symbol global agar tidak bentrok)
    function initAutoNumeric(element, hiddenElement) {
        // Cek dulu apakah elemen sudah punya instance autonumeric
        if(AutoNumeric.getAutoNumericElement(element) === null) {
            const an = new AutoNumeric(element, { 
                decimalCharacter: ',', 
                digitGroupSeparator: '.', 
                decimalPlaces: 0, 
                minimumValue: '0',
                currencySymbol: '', // Kosongkan symbol karena sudah ada di HTML (div absolute)
                unformatOnSubmit: true
            });
            
            element.addEventListener('autoNumeric:rawValueModified', e => {
                hiddenElement.value = e.detail.newRawValue;
                calculateTotals();
            });
            return an;
        }
        return null;
    }

    // --- FUNGSI HITUNG TOTAL ---
    function calculateTotals() {
        let subtotalProducts = 0;
        
        // Loop Row Produk
        productItemsContainer.querySelectorAll('tr').forEach((row) => {
            const price = parseFloat(row.querySelector('.price-raw').value) || 0;
            const quantity = parseFloat(row.querySelector('.quantity').value) || 0;
            const subtotal = price * quantity;
            
            row.querySelector('.subtotal').textContent = formatRupiah(subtotal);
            subtotalProducts += subtotal;
        });

        // Loop Row Biaya Tambahan
        let totalAdditionalCosts = 0;
        additionalCostItemsContainer.querySelectorAll('tr').forEach((row) => {
            const amount = parseFloat(row.querySelector('.cost-raw').value) || 0;
            totalAdditionalCosts += amount;
        });

        // Diskon & Pajak
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

        // Update UI
        document.getElementById('subtotal-display').textContent = formatRupiah(subtotalProducts);
        document.getElementById('discount-amount-display').textContent = `(-) ${formatRupiah(discountAmount)}`;
        document.getElementById('subtotal-after-discount').textContent = formatRupiah(subtotalAfterDiscount);
        document.getElementById('tax-breakdown').innerHTML = taxHtml;
        document.getElementById('total-additional-display').textContent = `(+) ${formatRupiah(totalAdditionalCosts)}`;
        document.getElementById('grand-total').textContent = formatRupiah(grandTotal);
    }

    // --- TAMBAH BARIS PRODUK ---
    function addProductRow(item = null) {
        const newRow = productRowTemplate.content.cloneNode(true).querySelector('tr');
        const productSelect = newRow.querySelector('.product-select');
        const quantityInput = newRow.querySelector('.quantity');
        const priceDisplayInput = newRow.querySelector('.price-display');
        const priceRawInput = newRow.querySelector('.price-raw');
        const updateMasterCheck = newRow.querySelector('.update-master-check');
        
        // Set Attributes Name (Array)
        productSelect.name = `products[${productIndex}][product_id]`;
        quantityInput.name = `products[${productIndex}][quantity]`;
        priceRawInput.name = `products[${productIndex}][custom_price]`; 
        updateMasterCheck.name = `products[${productIndex}][update_master_price]`;
        
        productItemsContainer.appendChild(newRow);

        // Init Select2
        const select2 = $(productSelect).select2({ 
            placeholder: '-- Pilih --', 
            width: '100%', 
            dropdownCssClass: 'select2-dropdown-clean'
        });
        
        // Init AutoNumeric
        const anPrice = initAutoNumeric(priceDisplayInput, priceRawInput);

        // Listener saat ganti produk
        select2.on('change', function() {
            const selectedOption = this.options[this.selectedIndex];
            const basePrice = parseFloat(selectedOption.dataset.price) || 0;
            // Auto fill hanya jika data kosong dan bukan sedang load data existing
            if(priceRawInput.value == 0 && !item) { 
                anPrice.set(basePrice);
                priceRawInput.value = basePrice;
            }
            calculateTotals();
        });

        quantityInput.addEventListener('input', calculateTotals);
        
        newRow.querySelector('.remove-product-btn').addEventListener('click', () => {
            select2.select2('destroy'); // Bersihkan instance select2
            newRow.remove();
            calculateTotals();
        });
        
        // Pre-fill Data (Jika dari Order atau Edit mode)
        if (item) {
            $(productSelect).val(item.product_id).trigger('change.select2'); 
            // Gunakan timeout agar trigger change tidak mereset harga ke default master
            setTimeout(() => {
                quantityInput.value = item.quantity;
                anPrice.set(item.price_per_unit); 
                priceRawInput.value = item.price_per_unit;
                calculateTotals();
            }, 50);
        }
        
        productIndex++;
    }

    // --- TAMBAH BARIS BIAYA ---
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

    // --- EVENT LISTENERS GLOBAL ---
    document.getElementById('add-product-btn').addEventListener('click', () => addProductRow());
    document.getElementById('add-cost-btn').addEventListener('click', () => addCostRow());
    taxOptionsContainer.addEventListener('change', calculateTotals);
    discountInput.addEventListener('input', calculateTotals);

    // --- LOAD DATA EXISTING ---
    // Check if creating from order ($order passed from controller)
    @if(isset($order) && $order->items->count() > 0)
        const orderItems = @json($order->items);
        orderItems.forEach(item => addProductRow(item));
    @else
        addProductRow(); // Default 1 row kosong
    @endif
    
    // Initial Calc
    setTimeout(calculateTotals, 500);
});
</script>
@endpush
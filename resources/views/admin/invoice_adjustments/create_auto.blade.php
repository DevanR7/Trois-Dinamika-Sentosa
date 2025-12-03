@extends('admin.layouts.app')

@section('title', 'Revisi Otomatis Invoice')

@push('styles')
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
    <style>
        /* Style tambahan untuk highlight selisih */
        .diff-box { transition: all 0.3s ease; }
        .diff-credit { @apply bg-emerald-50 border-emerald-200 text-emerald-700; }
        .diff-debit { @apply bg-amber-50 border-amber-200 text-amber-700; }
        .diff-neutral { @apply bg-slate-50 border-slate-200 text-slate-500; }
    </style>
@endpush

@section('content')
<div class="max-w-full mx-auto pb-20 animate-enter">
    
    {{-- HEADER --}}
    <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center mb-6 gap-4">
        <div>
            <div class="flex items-center gap-2 text-xs font-bold text-slate-400 uppercase tracking-wider mb-1">
                <a href="{{ route('admin.invoices.index') }}" class="hover:text-indigo-600 transition">Invoice</a>
                <i class="material-icons text-[12px]">chevron_right</i>
                <span class="text-slate-600">Koreksi Otomatis</span>
            </div>
            <h1 class="text-2xl font-bold text-slate-800 tracking-tight flex items-center gap-2">
                Revisi Invoice <span class="font-mono bg-indigo-50 text-indigo-600 px-2 py-1 rounded text-lg border border-indigo-100">{{ $invoice->invoice_number }}</span>
            </h1>
            <p class="text-sm text-slate-500 mt-1">Ubah item di bawah ini. Sistem akan otomatis menghitung selisih untuk Nota Debet/Kredit.</p>
        </div>
        <a href="{{ route('admin.invoice-adjustments.create') }}" 
           class="inline-flex items-center px-4 py-2 bg-white border border-slate-300 text-slate-600 rounded-lg text-sm font-bold hover:bg-slate-50 transition-all shadow-sm">
            <i class="material-icons text-sm mr-2">arrow_back</i> Kembali
        </a>
    </div>

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

    <form action="{{ route('admin.invoice-adjustments.store.auto', $invoice->invoice_id) }}" method="POST" id="adjustment-form">
        @csrf
        
        <div class="grid grid-cols-1 xl:grid-cols-3 gap-6">

            {{-- KOLOM KIRI: FORM EDITOR --}}
            <div class="xl:col-span-2 space-y-6">
                
                {{-- 1. INFO INVOICE ASLI (READ ONLY) --}}
                <div class="dashboard-card p-0 overflow-hidden shadow-sm">
                    <div class="bg-slate-50/50 px-6 py-3 border-b border-slate-100 flex items-center gap-2">
                        <i class="material-icons text-slate-400 text-sm">info</i>
                        <h3 class="text-xs font-bold text-slate-500 uppercase tracking-wider">Info Invoice Asli</h3>
                    </div>
                    <div class="p-4 grid grid-cols-2 md:grid-cols-4 gap-4 text-sm">
                        <div>
                            <span class="block text-[10px] uppercase text-slate-400 font-bold">Klien</span>
                            <span class="font-semibold text-slate-700">{{ $invoice->client->client_name }}</span>
                        </div>
                        <div>
                            <span class="block text-[10px] uppercase text-slate-400 font-bold">Tanggal</span>
                            <span class="font-semibold text-slate-700">{{ $invoice->order_date->format('d M Y') }}</span>
                        </div>
                        <div>
                            <span class="block text-[10px] uppercase text-slate-400 font-bold">Sales</span>
                            <span class="font-semibold text-slate-700">{{ $invoice->sales->full_name ?? '-' }}</span>
                        </div>
                        <div>
                            <span class="block text-[10px] uppercase text-slate-400 font-bold">Total Asli</span>
                            <span class="font-mono font-bold text-slate-800">Rp {{ number_format($invoice->total_amount, 0, ',', '.') }}</span>
                            {{-- Hidden input untuk JS comparison --}}
                            <input type="hidden" id="original-grand-total" value="{{ $invoice->total_amount }}">
                        </div>
                    </div>
                </div>

                {{-- 2. EDITOR ITEM PRODUK --}}
                <div class="dashboard-card p-0 overflow-hidden shadow-sm">
                    <div class="px-6 py-4 border-b border-slate-100 bg-white flex justify-between items-center">
                        <div class="flex items-center gap-2">
                            <i class="material-icons text-indigo-600 text-[20px]">edit_note</i> 
                            <h3 class="text-sm font-bold text-slate-700 uppercase tracking-wide">Revisi Item Produk</h3>
                        </div>
                        <div class="text-xs text-slate-400 italic">*Silakan edit qty/harga atau hapus item</div>
                    </div>
                    
                    <div class="overflow-x-auto">
                        <table class="dashboard-table w-full">
                            <thead class="bg-slate-50 border-b border-slate-200">
                                <tr>
                                    <th class="pl-6 w-5/12">Produk</th>
                                    <th class="text-center w-2/12">Qty Baru</th>
                                    <th class="text-right w-2/12">Harga Baru (@)</th>
                                    <th class="text-right w-2/12">Subtotal</th>
                                    <th class="w-10 pr-6"></th>
                                </tr>
                            </thead>
                            <tbody id="product-items" class="divide-y divide-slate-100 bg-white">
                                {{-- JS Inject Rows --}}
                            </tbody>
                        </table>
                    </div>

                    <div class="p-4 bg-slate-50/50 border-t border-slate-100 text-center">
                        <button type="button" id="add-product-btn" 
                                class="inline-flex items-center px-4 py-2 bg-white border border-dashed border-indigo-300 text-indigo-600 text-xs font-bold rounded-lg hover:bg-indigo-50 transition shadow-sm uppercase tracking-wide">
                            <i class="material-icons text-base mr-1">add</i> Tambah Produk
                        </button>
                    </div>
                </div>
                
                {{-- 3. REVISI BIAYA TAMBAHAN (Opsional) --}}
                <div class="dashboard-card p-0 overflow-hidden shadow-sm">
                    <div class="px-6 py-3 border-b border-slate-100 bg-white flex justify-between items-center">
                        <h3 class="text-xs font-bold text-slate-500 uppercase tracking-wide flex items-center gap-2">
                            <i class="material-icons text-green-600 text-sm">attach_money</i> Revisi Biaya Tambahan
                        </h3>
                        <button type="button" id="add-cost-btn" class="text-xs font-bold text-green-600 hover:text-green-700 underline">
                            + Tambah
                        </button>
                    </div>
                    <div class="overflow-x-auto">
                        <table class="dashboard-table w-full">
                             <tbody id="additional-cost-items" class="divide-y divide-slate-100 bg-white">
                                {{-- JS Inject Cost Rows --}}
                            </tbody>
                        </table>
                    </div>
                </div>

                {{-- 4. ALASAN KOREKSI --}}
                <div class="dashboard-card p-6">
                    <label class="block text-[11px] font-bold text-slate-500 uppercase mb-2 ml-1">Alasan Koreksi <span class="text-red-500">*</span></label>
                    <textarea name="notes" id="notes" rows="3" class="form-textarea w-full bg-white border-slate-200 focus:border-indigo-500 focus:ring-indigo-200" placeholder="Jelaskan kenapa invoice ini direvisi (Wajib diisi untuk audit)..." required>{{ old('notes') }}</textarea>
                </div>

            </div>

            {{-- KOLOM KANAN: KALKULASI SELISIH --}}
            <div class="xl:col-span-1">
                <div class="dashboard-card p-0 sticky top-6 border-t-4 border-indigo-500">
                    <div class="p-6 space-y-6">
                        <h4 class="text-xs font-bold text-slate-500 uppercase tracking-wider border-b border-slate-200 pb-2 mb-2">Kalkulasi Baru</h4>

                        {{-- Form Input Diskon & Pajak --}}
                        <div class="space-y-4">
                            <div class="flex items-center justify-between gap-4">
                                <label for="discount_percentage" class="text-xs font-bold text-slate-500 uppercase">Diskon (%)</label>
                                <input type="number" name="discount_percentage" id="discount_percentage" value="{{ old('discount_percentage', $invoice->discount_percentage) }}" class="w-20 text-right text-xs border-slate-300 rounded form-input h-8" min="0" max="100">
                            </div>
                            
                            <div>
                                <label class="block text-[10px] font-bold text-slate-400 uppercase mb-1">Pajak</label>
                                <div id="tax-options" class="space-y-1 bg-slate-50 p-2 rounded border border-slate-100">
                                    @php 
                                        $appliedTaxIds = $invoice->taxes->pluck('id')->toArray();
                                        $allTaxes = \App\Models\Tax::where('is_active', true)->get();
                                    @endphp
                                    @foreach ($allTaxes as $tax)
                                        <label class="flex items-center space-x-2 cursor-pointer">
                                            <input type="checkbox" name="taxes[]" value="{{ $tax->id }}" data-rate="{{ $tax->rate }}" class="tax-checkbox rounded border-slate-300 text-indigo-600 focus:ring-indigo-500 h-3.5 w-3.5" @checked(in_array($tax->id, old('taxes', $appliedTaxIds)))>
                                            <span class="text-xs text-slate-600">{{ $tax->name }} ({{ $tax->rate }}%)</span>
                                        </label>
                                    @endforeach
                                </div>
                            </div>
                        </div>

                        {{-- Ringkasan Angka Baru --}}
                        <div class="bg-slate-50 p-4 rounded-lg space-y-2 border border-slate-100">
                            <div class="flex justify-between text-xs text-slate-500">
                                <span>Subtotal Baru</span>
                                <span class="font-bold text-slate-700" id="subtotal-display">Rp 0</span>
                            </div>
                            <div class="flex justify-between text-xs text-slate-500">
                                <span>Diskon & Pajak</span>
                                <span class="font-bold text-slate-700" id="tax-disc-display">Rp 0</span>
                            </div>
                            <div class="flex justify-between text-xs text-slate-500">
                                <span>Biaya Tambahan</span>
                                <span class="font-bold text-slate-700" id="total-additional-display">Rp 0</span>
                            </div>
                            <div class="border-t border-slate-200 my-2 pt-2 flex justify-between items-center">
                                <span class="text-xs font-bold text-slate-800 uppercase">Total Tagihan Baru</span>
                                <span class="text-lg font-bold text-indigo-600 font-mono" id="grand-total">Rp 0</span>
                            </div>
                        </div>

                        {{-- BOX SELISIH (THE CORE FEATURE) --}}
                        <div id="diff-container" class="diff-box p-5 rounded-xl border-2 border-dashed text-center">
                            <span class="block text-xs font-bold uppercase tracking-widest opacity-70 mb-1">Estimasi Penyesuaian</span>
                            <h3 class="text-2xl font-bold font-mono my-1" id="diff-amount">Rp 0</h3>
                            <span class="inline-block px-2 py-1 rounded text-[10px] font-bold uppercase tracking-wide mt-1" id="diff-label">Tidak Ada Perubahan</span>
                            
                            <input type="hidden" name="adjustment_type" id="adjustment_type">
                            <input type="hidden" name="adjustment_amount" id="adjustment_amount_input">
                        </div>

                        {{-- Opsi Kelebihan Bayar (Jika Nota Kredit) --}}
                        <div id="overpayment-options" class="hidden bg-amber-50 rounded-lg p-3 border border-amber-100">
                            <label class="block text-[10px] font-bold text-amber-700 uppercase mb-2">Aksi Kelebihan Bayar:</label>
                            <div class="space-y-1">
                                <div class="flex items-center">
                                    <input type="radio" name="overpayment_action" id="act_deposit" value="deposit" checked class="h-3.5 w-3.5 text-amber-600 border-amber-300 focus:ring-amber-500">
                                    <label for="act_deposit" class="ml-2 text-xs text-amber-800 font-medium">Simpan ke Deposit Klien</label>
                                </div>
                                <div class="flex items-center">
                                    <input type="radio" name="overpayment_action" id="act_refund" value="refund" class="h-3.5 w-3.5 text-amber-600 border-amber-300 focus:ring-amber-500">
                                    <label for="act_refund" class="ml-2 text-xs text-amber-800 font-medium">Refund Manual Nanti</label>
                                </div>
                            </div>
                        </div>

                        {{-- Action Buttons --}}
                        <div class="grid grid-cols-1 gap-3 pt-2">
                            <button type="submit" id="submit-btn" class="w-full py-3 px-4 rounded-lg shadow-md text-sm font-bold text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 flex justify-center items-center transition-all disabled:opacity-50 disabled:cursor-not-allowed">
                                <i class="material-icons text-sm mr-2">save</i> Simpan Koreksi
                            </button>
                            <a href="{{ route('admin.invoices.show', $invoice->invoice_id) }}" class="w-full py-3 px-4 border border-slate-300 rounded-lg text-sm font-bold text-slate-600 bg-white hover:bg-slate-50 focus:outline-none text-center transition-all">
                                Batal
                            </a>
                        </div>

                    </div>
                </div>
            </div>

        </div>
    </form>
</div>

{{-- TEMPLATE ROW PRODUK --}}
<template id="product-row-template">
    <tr class="hover:bg-slate-50 transition-colors border-b border-gray-50 last:border-0 group">
        <td class="pl-6 py-2 align-top">
            <select class="product-select text-sm" style="width: 100%;" required>
                <option value="" data-price="0" disabled selected>-- Pilih Produk --</option>
                @foreach ($products as $product)
                    <option value="{{ $product->product_id }}" data-price="{{ $product->selling_price ?? 0 }}">
                        {{ $product->product_name }}
                    </option>
                @endforeach
            </select>
        </td>
        <td class="py-2 align-top">
            <input type="number" class="form-input quantity text-center w-full font-bold text-gray-700 h-9" value="1" min="0.01" step="0.01" required>
        </td>
        {{-- KOLOM HARGA (DIREVISI) --}}
        <td class="py-2 align-top">
            <div class="relative flex items-center">
                {{-- Label Rp Absolut (Z-index tinggi agar di atas input) --}}
                <div class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3 z-10">
                    <span class="text-slate-500 text-xs font-bold">Rp</span>
                </div>
                {{-- Input Tanpa class 'input-currency' untuk menghindari konflik --}}
                <input type="text" class="price-display form-input block w-full pl-9 pr-2 text-right text-sm font-medium h-9 focus:ring-1 focus:ring-indigo-500" required placeholder="0">
                <input type="hidden" class="price-raw" value="0">
            </div>
        </td>
        <td class="py-2 align-top text-right font-bold text-gray-900 text-xs align-middle font-mono">
            <span class="subtotal">Rp 0</span>
        </td>
        <td class="pr-6 py-2 align-top text-center align-middle">
            <button type="button" class="remove-product-btn text-slate-400 hover:text-red-500 hover:bg-red-50 rounded p-1 transition">
                <i class="material-icons text-[18px]">close</i>
            </button>
        </td>
    </tr>
</template>

{{-- TEMPLATE ROW BIAYA TAMBAHAN --}}
<template id="additional-cost-template">
    <tr class="hover:bg-slate-50 transition-colors border-b border-gray-50 last:border-0">
        <td class="pl-6 py-2">
            <input type="text" class="cost-desc form-input w-full text-xs" placeholder="Keterangan Biaya (Mis: Packing)" required>
        </td>
        {{-- KOLOM HARGA BIAYA (DIREVISI) --}}
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
    // --- 1. DATA & VARIABEL ---
    const originalGrandTotal = parseFloat(document.getElementById('original-grand-total').value) || 0;
    
    const existingItems = @json($invoice->items);
    const existingCosts = @json($invoice->additionalCosts);

    const productItemsContainer = document.getElementById('product-items');
    const additionalCostItemsContainer = document.getElementById('additional-cost-items');
    const productRowTemplate = document.getElementById('product-row-template');
    const costRowTemplate = document.getElementById('additional-cost-template');
    const taxOptionsContainer = document.getElementById('tax-options');
    const discountInput = document.getElementById('discount_percentage');
    
    // Elemen Kalkulasi Selisih
    const diffContainer = document.getElementById('diff-container');
    const diffAmountEl = document.getElementById('diff-amount');
    const diffLabelEl = document.getElementById('diff-label');
    const overpaymentOptions = document.getElementById('overpayment-options');
    const submitBtn = document.getElementById('submit-btn');

    let productIndex = 0;
    let costIndex = 0;

    // --- 2. HELPER FUNCTIONS ---
    function formatRupiah(number) {
        return new Intl.NumberFormat('id-ID', { style: 'currency', currency: 'IDR', minimumFractionDigits: 0 }).format(number);
    }

    function initAutoNumeric(element, hiddenElement) {
        // Cek apakah elemen sudah punya instance autonumeric
        if(AutoNumeric.getAutoNumericElement(element) === null) {
            const an = new AutoNumeric(element, { 
                decimalCharacter: ',', 
                digitGroupSeparator: '.', 
                decimalPlaces: 0, 
                minimumValue: '0',
                currencySymbol: '', // Pastikan kosong agar tidak double dengan label Rp
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

    // --- 3. KALKULASI UTAMA (CORE LOGIC) ---
    function calculateTotals() {
        let subtotalProducts = 0;
        
        // Hitung Subtotal Produk
        productItemsContainer.querySelectorAll('tr').forEach((row) => {
            const price = parseFloat(row.querySelector('.price-raw').value) || 0;
            const quantity = parseFloat(row.querySelector('.quantity').value) || 0;
            const subtotal = price * quantity;
            row.querySelector('.subtotal').textContent = formatRupiah(subtotal);
            subtotalProducts += subtotal;
        });

        // Hitung Biaya Tambahan
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
        taxOptionsContainer.querySelectorAll('.tax-checkbox:checked').forEach((checkbox) => {
            const rate = parseFloat(checkbox.dataset.rate) || 0;
            totalTaxAmount += subtotalAfterDiscount * (rate / 100);
        });
        
        // Total Baru
        const newGrandTotal = subtotalAfterDiscount + totalTaxAmount + totalAdditionalCosts;

        // Update Display Ringkasan
        document.getElementById('subtotal-display').textContent = formatRupiah(subtotalProducts);
        document.getElementById('tax-disc-display').textContent = formatRupiah(totalTaxAmount - discountAmount);
        document.getElementById('total-additional-display').textContent = formatRupiah(totalAdditionalCosts);
        document.getElementById('grand-total').textContent = formatRupiah(newGrandTotal);

        // --- 4. HITUNG SELISIH (ADJUSTMENT) ---
        const diff = originalGrandTotal - newGrandTotal;
        const absDiff = Math.abs(diff);

        // Reset Style
        diffContainer.className = "diff-box p-5 rounded-xl border-2 border-dashed text-center mt-4";
        overpaymentOptions.classList.add('hidden');
        submitBtn.disabled = false;

        if (absDiff <= 10) { // Toleransi floating point kecil
            // Tidak ada perubahan
            diffContainer.classList.add('diff-neutral');
            diffAmountEl.textContent = "Rp 0";
            diffLabelEl.textContent = "TIDAK ADA PERUBAHAN";
            diffLabelEl.className = "inline-block px-2 py-1 rounded text-[10px] font-bold uppercase tracking-wide mt-1 bg-slate-200 text-slate-600";
            submitBtn.disabled = true; // Disable tombol simpan jika tidak ada perubahan
        } 
        else if (diff > 0) {
            // NOTA KREDIT (Total Baru < Total Lama -> Kita hutang ke klien)
            diffContainer.classList.add('diff-credit');
            diffAmountEl.textContent = formatRupiah(absDiff);
            diffLabelEl.textContent = "NOTA KREDIT (POTONGAN)";
            diffLabelEl.className = "inline-block px-2 py-1 rounded text-[10px] font-bold uppercase tracking-wide mt-1 bg-emerald-200 text-emerald-800";
            
            // Tampilkan opsi kelebihan bayar
            overpaymentOptions.classList.remove('hidden');
        } 
        else {
            // NOTA DEBET (Total Baru > Total Lama -> Klien kurang bayar)
            diffContainer.classList.add('diff-debit');
            diffAmountEl.textContent = formatRupiah(absDiff);
            diffLabelEl.textContent = "NOTA DEBET (TAGIHAN TAMBAHAN)";
            diffLabelEl.className = "inline-block px-2 py-1 rounded text-[10px] font-bold uppercase tracking-wide mt-1 bg-amber-200 text-amber-800";
        }
    }

    // --- 5. FUNGSI MANAJEMEN BARIS ---

    function addProductRow(item = null) {
        const newRow = productRowTemplate.content.cloneNode(true).querySelector('tr');
        const productSelect = newRow.querySelector('.product-select');
        const quantityInput = newRow.querySelector('.quantity');
        const priceDisplayInput = newRow.querySelector('.price-display');
        const priceRawInput = newRow.querySelector('.price-raw');
        
        // Set Name Attributes
        productSelect.name = `products[${productIndex}][product_id]`;
        quantityInput.name = `products[${productIndex}][quantity]`;
        priceRawInput.name = `products[${productIndex}][price_per_unit]`;
        
        productItemsContainer.appendChild(newRow);

        // Init JS Plugins untuk baris ini
        const select2 = $(productSelect).select2({ 
            placeholder: '-- Pilih Produk --', 
            width: '100%', 
            dropdownCssClass: 'select2-dropdown-clean' 
        });
        const anPrice = initAutoNumeric(priceDisplayInput, priceRawInput);

        // Event Listeners Row
        select2.on('change', function() {
            const selectedOption = this.options[this.selectedIndex];
            const basePrice = parseFloat(selectedOption.dataset.price) || 0;
            // Auto fill price only if raw is 0 and we are NOT loading existing item data
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
        
        // Pre-fill Data (Jika mode edit/load existing)
        if (item) {
            $(productSelect).val(item.product_id).trigger('change.select2');
            
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

    // --- 6. INITIALIZATION & EVENTS GLOBAL ---
    
    document.getElementById('add-product-btn').addEventListener('click', () => addProductRow());
    document.getElementById('add-cost-btn').addEventListener('click', () => addCostRow());
    
    taxOptionsContainer.addEventListener('change', calculateTotals);
    discountInput.addEventListener('input', calculateTotals);

    // Load Existing Items from Invoice
    if (existingItems && existingItems.length > 0) {
        existingItems.forEach(item => addProductRow(item));
    } else {
        addProductRow(); 
    }

    // Load Existing Costs from Invoice
    if (existingCosts && existingCosts.length > 0) {
        existingCosts.forEach(cost => addCostRow(cost));
    }
    
    // Initial Calculation
    setTimeout(calculateTotals, 500);
});
</script>
@endpush
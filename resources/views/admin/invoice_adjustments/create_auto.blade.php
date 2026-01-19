@extends('admin.layouts.app')

@section('title', 'Koreksi Otomatis Invoice #' . $invoice->invoice_number)

@section('content')
<div x-data="autoAdjustmentForm()" x-init="initData()" class="max-w-7xl mx-auto">

    {{-- HEADER --}}
    <div class="flex items-center gap-4 mb-6">
        <a href="{{ route('admin.invoice-adjustments.create', ['invoice_id' => $invoice->invoice_id]) }}" 
           class="w-9 h-9 flex items-center justify-center rounded-lg bg-white border border-slate-200 text-slate-500 hover:bg-slate-100 hover:text-indigo-600 transition-all shadow-sm">
            <i class="material-icons text-xl leading-none">arrow_back</i>
        </a>
        <div>
            <div class="flex items-center gap-3">
                <h1 class="page-title text-xl">Koreksi Otomatis <span class="text-indigo-600">#{{ $invoice->invoice_number }}</span></h1>
            </div>
            <p class="text-sm text-slate-500 dark:text-slate-400">
                Ubah Qty menjadi <strong>0</strong> untuk menghapus item (Retur Penuh).
            </p>
        </div>
    </div>

    {{-- ALERT INFO --}}
    <div class="bg-indigo-50 dark:bg-indigo-900/20 border border-indigo-200 dark:border-indigo-800 rounded-xl p-4 mb-6 flex gap-4 items-start">
        <div class="w-10 h-10 rounded-full bg-indigo-100 dark:bg-indigo-800 text-indigo-600 dark:text-indigo-300 flex items-center justify-center shrink-0">
            <i class="material-icons">auto_fix_high</i>
        </div>
        <div>
            <h4 class="font-bold text-indigo-800 dark:text-indigo-300 text-sm">Mode Revisi Fleksibel</h4>
            <ul class="text-xs text-indigo-700 dark:text-indigo-400 mt-1 leading-relaxed list-disc list-inside">
                <li>Set <strong>Qty = 0</strong> untuk menghapus barang dari invoice (Retur Penuh).</li>
                <li>Kurangi Qty untuk Retur Sebagian (Partial).</li>
                <li>Sistem otomatis membuat <strong>Credit Note</strong> (Potong Tagihan) atau <strong>Debit Note</strong> (Tagihan Tambahan).</li>
            </ul>
        </div>
    </div>

    {{-- MAIN FORM --}}
    <form action="{{ route('admin.invoice-adjustments.store.auto', $invoice->invoice_id) }}" method="POST" @submit="validateForm($event)">
        @csrf
        
        <div class="card overflow-hidden">
            <div class="h-1 bg-emerald-500 w-full"></div>

            <div class="p-6 md:p-8 space-y-8">
                
                {{-- SECTION 1: HEADER (Read Only) --}}
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
                    <div class="lg:col-span-1">
                        <label class="form-label">Klien</label>
                        <input type="text" class="form-input bg-slate-100 text-slate-500 cursor-not-allowed" value="{{ $invoice->client->client_name }}" readonly>
                    </div>
                    <div class="lg:col-span-1">
                        <label class="form-label">Sales Person</label>
                        <input type="text" class="form-input bg-slate-100 text-slate-500 cursor-not-allowed" value="{{ $invoice->sales->full_name ?? '-' }}" readonly>
                        <input type="hidden" name="requester_user_id" value="{{ $invoice->user_id_sales }}">
                    </div>
                    <div class="lg:col-span-1">
                        <label class="form-label label-required">Tanggal Invoice</label>
                        <input type="date" name="order_date" class="form-input" value="{{ old('order_date', $invoice->order_date->format('Y-m-d')) }}" required>
                    </div>
                    <div class="lg:col-span-1">
                        <label class="form-label label-required">Jatuh Tempo</label>
                        <input type="date" name="due_date" class="form-input" value="{{ old('due_date', $invoice->due_date->format('Y-m-d')) }}" required>
                    </div>
                </div>

                <hr class="border-slate-100 dark:border-slate-700">

                {{-- SECTION 2: ITEM BARANG --}}
                <div>
                    <div class="flex justify-between items-center mb-4">
                        <h3 class="font-bold text-slate-700 dark:text-white flex items-center gap-2">
                            <i class="material-icons text-indigo-500">shopping_cart</i> Item Barang (Revisi)
                        </h3>
                        <button type="button" @click="addItem()" class="btn btn-sm btn-secondary hover:text-indigo-600">
                            <i class="material-icons text-sm mr-1">add</i> Tambah Barang
                        </button>
                    </div>

                    <div class="overflow-x-auto rounded-lg border border-slate-200 dark:border-slate-700">
                        <table class="w-full text-sm text-left">
                            <thead class="bg-slate-50 dark:bg-slate-800 text-xs text-slate-500 uppercase font-bold">
                                <tr>
                                    <th class="px-4 py-3 min-w-[250px]">Produk</th>
                                    <th class="px-4 py-3 w-32 text-right">Stok Gudang</th>
                                    <th class="px-4 py-3 w-48 text-right">Harga Satuan</th>
                                    <th class="px-4 py-3 w-24 text-center">Qty</th>
                                    <th class="px-4 py-3 w-40 text-right">Total</th>
                                    <th class="px-4 py-3 w-10"></th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-100 dark:divide-slate-700 bg-white dark:bg-slate-800/50">
                                <template x-for="(item, index) in items" :key="item.key">
                                    {{-- 
                                        VISUAL FEEDBACK: 
                                        - Merah (Rose) jika Qty = 0 (Dihapus)
                                        - Kuning (Amber) jika Qty < Original (Retur Parsial)
                                    --}}
                                    <tr :class="item.qty == 0 ? 'bg-rose-50/50 dark:bg-rose-900/10' : (item.qty < item.original_qty ? 'bg-amber-50/50 dark:bg-amber-900/10' : '')">
                                        
                                        {{-- KOLOM PRODUK --}}
                                        <td class="px-4 py-3 align-top">
                                            <select :name="`products[${index}][product_id]`" class="tom-select-dynamic w-full" x-init="initTomSelect($el, index)" required>
                                                <option value="">Pilih Produk...</option>
                                                @foreach($products as $product)
                                                    <option value="{{ $product->product_id }}" data-price="{{ $product->selling_price }}" data-stock="{{ $product->stock_quantity }}">
                                                        {{ $product->product_name }} ({{ $product->product_code }})
                                                    </option>
                                                @endforeach
                                            </select>
                                            
                                            <div class="flex items-center gap-4 mt-2">
                                                {{-- Checkbox Update Harga Master --}}
                                                <label class="inline-flex items-center cursor-pointer" x-show="item.qty > 0">
                                                    <input type="checkbox" :name="`products[${index}][update_master_price]`" value="1" class="form-checkbox h-3 w-3 text-indigo-600 rounded border-gray-300">
                                                    <span class="ml-2 text-[10px] text-slate-500">Update harga master</span>
                                                </label>
                                                
                                                {{-- Label Status --}}
                                                <span x-show="item.qty == 0" class="text-[10px] text-rose-600 font-bold flex items-center gap-1 uppercase tracking-wider">
                                                    <i class="material-icons text-sm">delete_forever</i> Dihapus / Retur Full
                                                </span>
                                                <span x-show="item.qty > 0 && item.qty < item.original_qty" class="text-[10px] text-amber-600 font-bold flex items-center gap-1">
                                                    <i class="material-icons text-[10px]">history</i> Qty Awal: <span x-text="item.original_qty"></span>
                                                </span>
                                            </div>
                                        </td>

                                        {{-- STOK --}}
                                        <td class="px-4 py-3 align-top text-right pt-4">
                                            <span class="font-mono text-xs" :class="item.stock > 0 ? 'text-emerald-600' : 'text-red-500'" x-text="formatNumber(item.stock)"></span>
                                        </td>

                                        {{-- HARGA SATUAN --}}
                                        <td class="px-4 py-3 align-top">
                                            <div class="relative">
                                                <span class="absolute left-3 top-2 text-slate-400 text-xs font-bold">Rp</span>
                                                <input type="text" 
                                                       x-model="item.formatted_price"
                                                       @input="handlePriceInput(index, $el.value)"
                                                       @blur="formatPriceOnBlur(index)"
                                                       class="form-input text-right pl-8 h-9 font-mono" 
                                                       :disabled="item.qty == 0"
                                                       required>
                                                <input type="hidden" :name="`products[${index}][price_per_unit]`" :value="item.price">
                                            </div>
                                        </td>

                                        {{-- QUANTITY (BISA 0) --}}
                                        <td class="px-4 py-3 align-top">
                                            <input type="number" 
                                                   :name="`products[${index}][quantity]`" 
                                                   x-model.number="item.qty" 
                                                   @input="calculateRow(index)" 
                                                   class="form-input text-center h-9 font-bold" 
                                                   :class="item.qty == 0 ? 'text-rose-600 bg-rose-50 border-rose-300' : ''"
                                                   min="0" 
                                                   step="any" 
                                                   required>
                                        </td>

                                        {{-- SUBTOTAL --}}
                                        <td class="px-4 py-3 align-top text-right pt-4">
                                            <span class="font-bold text-slate-700 dark:text-white" :class="item.qty == 0 ? 'line-through text-slate-400' : ''" x-text="formatNumber(item.subtotal)"></span>
                                        </td>

                                        {{-- TOMBOL HAPUS BARIS --}}
                                        <td class="px-4 py-3 align-top text-center pt-2">
                                            <button type="button" @click="removeItem(index)" class="text-slate-400 hover:text-red-500 transition-colors" title="Hapus Baris">
                                                <i class="material-icons text-lg">close</i>
                                            </button>
                                        </td>
                                    </tr>
                                </template>
                            </tbody>
                        </table>
                    </div>
                </div>

                {{-- SECTION 3: BIAYA, NOTES & OVERPAYMENT LOGIC --}}
                <div class="grid grid-cols-1 lg:grid-cols-2 gap-8 mt-4">
                    <div class="space-y-6">
                        
                        {{-- Biaya Tambahan --}}
                        <div>
                            <div class="flex justify-between items-center mb-3">
                                <h3 class="font-bold text-slate-700 dark:text-white flex items-center gap-2 text-sm">
                                    <i class="material-icons text-amber-500 text-sm">add_circle</i> Biaya Tambahan
                                </h3>
                                <button type="button" @click="addCost()" class="text-xs text-indigo-600 font-bold hover:underline">+ Tambah Biaya</button>
                            </div>
                            <div class="space-y-3">
                                <template x-for="(cost, index) in costs" :key="cost.key">
                                    <div class="flex gap-3 items-start">
                                        <input type="text" :name="`additional_costs[${index}][description]`" x-model="cost.desc" placeholder="Keterangan" class="form-input h-9 text-xs" required>
                                        <div class="relative w-32">
                                            <span class="absolute left-2 top-2 text-xs text-slate-400">Rp</span>
                                            <input type="number" :name="`additional_costs[${index}][amount]`" x-model.number="cost.amount" @input="calculateTotal()" class="form-input h-9 text-xs text-right pl-6" required>
                                        </div>
                                        <button type="button" @click="removeCost(index)" class="text-slate-400 hover:text-red-500 mt-2"><i class="material-icons text-sm">close</i></button>
                                    </div>
                                </template>
                            </div>
                        </div>

                        {{-- Catatan --}}
                        <div>
                            <label class="form-label label-required">Alasan Koreksi</label>
                            <textarea name="notes" class="form-textarea h-24 text-sm" placeholder="Contoh: Barang retur, revisi harga, item batal..." required minlength="5"></textarea>
                        </div>

                        {{-- LOGIKA OVERPAYMENT (CONDITIONAL SHOW) --}}
                        {{-- LOGIKA OVERPAYMENT (CONDITIONAL SHOW) --}}
                        <div x-show="diffValue < 0" x-transition.opacity>
                            
                            {{-- KASUS 1: Overpayment Real (Tagihan Baru < Sudah Bayar) --}}
                            {{-- Muncul jika terjadi kelebihan bayar --}}
                            <div class="bg-indigo-50 border border-indigo-100 rounded-lg p-4" x-show="grandTotal < amountPaid">
                                <label class="form-label label-required text-indigo-800">Tindakan Kelebihan Bayar</label>
                                <p class="text-xs text-indigo-600 mb-3">
                                    Total baru (Rp <span x-text="formatNumber(grandTotal)"></span>) lebih kecil dari pembayaran yang masuk (Rp <span x-text="formatNumber(amountPaid)"></span>). 
                                    <br>Selisih <strong>Rp <span x-text="formatNumber(amountPaid - grandTotal)"></span></strong> akan:
                                </p>
                                <div class="space-y-2">
                                    <label class="flex items-center gap-2 cursor-pointer">
                                        {{-- Tambahkan :disabled --}}
                                        <input type="radio" name="overpayment_action" value="deposit" class="form-radio text-indigo-600" 
                                               :disabled="grandTotal >= amountPaid" checked>
                                        <span class="text-sm text-slate-700">Disimpan ke Deposit Klien (Untuk Invoice Lain)</span>
                                    </label>
                                    <label class="flex items-center gap-2 cursor-pointer">
                                        {{-- Tambahkan :disabled --}}
                                        <input type="radio" name="overpayment_action" value="refund" class="form-radio text-indigo-600"
                                               :disabled="grandTotal >= amountPaid">
                                        <span class="text-sm text-slate-700">Biarkan sebagai Lebih Bayar (Untuk di-Refund Manual)</span>
                                    </label>
                                </div>
                            </div>

                            {{-- KASUS 2: Hanya Mengurangi Hutang (Tagihan Baru >= Sudah Bayar) --}}
                            <div class="bg-emerald-50 border border-emerald-100 rounded-lg p-4" x-show="grandTotal >= amountPaid">
                                <div class="flex items-center gap-2 text-emerald-700 font-bold text-sm">
                                    <i class="material-icons">check_circle</i> Sisa Tagihan Berkurang
                                </div>
                                <p class="text-xs text-emerald-600 mt-1">
                                    Tagihan berkurang, tetapi tidak terjadi kelebihan bayar. 
                                    <br>Sistem akan otomatis memotong sisa hutang (Credit Note).
                                    
                                    {{-- FIX: Input ini hanya aktif jika kondisi terpenuhi --}}
                                    <input type="hidden" name="overpayment_action" value="deposit" :disabled="grandTotal < amountPaid">
                                </p>
                            </div>

                        </div>
                    </div>

                    {{-- SECTION 4: RINGKASAN --}}
                    <div class="bg-slate-50 dark:bg-slate-800/50 rounded-xl p-6 border border-slate-200 dark:border-slate-700 h-fit">
                        <h3 class="font-bold text-slate-700 dark:text-white mb-4">Ringkasan Revisi</h3>
                        
                        <div class="space-y-3 text-sm">
                            <div class="flex justify-between text-slate-600 dark:text-slate-400">
                                <span>Subtotal Barang</span>
                                <span class="font-bold" x-text="formatNumber(subtotal)">0</span>
                            </div>
                            
                            {{-- Diskon --}}
                            <div class="flex justify-between items-center text-rose-600">
                                <div class="flex items-center gap-2">
                                    <span>Diskon Global</span>
                                    <div class="relative w-16">
                                        <input type="number" name="discount_percentage" x-model.number="discountPercent" @input="calculateTotal()" class="form-input h-7 text-center text-xs pr-4 border-rose-200 focus:border-rose-500" min="0" max="100">
                                        <span class="absolute right-1.5 top-1.5 text-[10px] font-bold">%</span>
                                    </div>
                                </div>
                                <span>- <span x-text="formatNumber(discountAmount)">0</span></span>
                            </div>

                            <div class="h-px bg-slate-200 dark:bg-slate-700 my-2"></div>
                            
                            <div class="flex justify-between text-slate-500 text-xs">
                                <span>DPP</span>
                                <span x-text="formatNumber(taxableBase)">0</span>
                            </div>

                            {{-- Pajak --}}
                            <div class="flex justify-between items-start text-slate-600 dark:text-slate-300">
                                <div class="flex flex-col gap-1 w-1/2">
                                    <span>Pajak (PPN)</span>
                                    <div class="flex flex-wrap gap-2 mt-1">
                                        @foreach($taxes as $tax)
                                            <label class="inline-flex items-center p-1.5 border rounded cursor-pointer bg-white hover:bg-slate-50 transition-colors" :class="selectedTaxes.includes({{ $tax->id }}) ? 'border-indigo-500 bg-indigo-50' : 'border-slate-200'">
                                                <input type="checkbox" name="taxes[]" value="{{ $tax->id }}" @change="toggleTax({{ $tax->id }}, {{ $tax->rate }})" class="hidden">
                                                <span class="text-[10px] font-bold" :class="selectedTaxes.includes({{ $tax->id }}) ? 'text-indigo-600' : 'text-slate-500'">{{ $tax->name }} ({{ $tax->rate }}%)</span>
                                            </label>
                                        @endforeach
                                    </div>
                                </div>
                                <span class="font-bold mt-1">+ <span x-text="formatNumber(taxAmount)">0</span></span>
                            </div>

                            <div class="flex justify-between text-slate-600 dark:text-slate-400" x-show="totalAdditionalCosts > 0">
                                <span>Biaya Tambahan</span>
                                <span>+ <span x-text="formatNumber(totalAdditionalCosts)">0</span></span>
                            </div>

                            <div class="h-px bg-slate-300 dark:bg-slate-600 my-2"></div>
                            
                            {{-- COMPARE SECTION --}}
                            <div class="flex justify-between items-center text-slate-400 text-xs">
                                <span>Total Lama</span>
                                <span class="line-through decoration-rose-500">Rp {{ number_format($invoice->total_amount, 0, ',', '.') }}</span>
                            </div>

                            <div class="flex justify-between items-center mt-2">
                                <span class="text-base font-bold text-slate-800 dark:text-white">Total Baru</span>
                                <span class="text-2xl font-black text-indigo-600 dark:text-indigo-400">Rp <span x-text="formatNumber(grandTotal)">0</span></span>
                            </div>

                            <div class="flex justify-between items-center text-xs text-emerald-600 mt-1">
                                <span>Sudah Dibayar</span>
                                <span class="font-bold">Rp {{ number_format($invoice->amount_paid, 0, ',', '.') }}</span>
                            </div>

                            {{-- SELISIH INDICATOR --}}
                            <div class="mt-4 p-3 rounded-lg border flex justify-between items-center transition-all duration-300"
                                 :class="diffValue > 0 ? 'bg-rose-50 border-rose-200 text-rose-700' : (diffValue < 0 ? 'bg-emerald-50 border-emerald-200 text-emerald-700' : 'bg-slate-50 border-slate-200 text-slate-500')">
                                <div class="flex flex-col">
                                    <span class="text-xs font-bold uppercase">Estimasi Koreksi</span>
                                    <span class="text-[10px]" x-text="diffValue > 0 ? 'Debit Note (Tagihan Naik)' : (diffValue < 0 ? 'Credit Note (Tagihan Turun)' : 'Tidak ada perubahan')"></span>
                                </div>
                                <span class="font-mono font-bold text-lg" x-text="(diffValue > 0 ? '+' : '') + formatNumber(diffValue)"></span>
                            </div>
                        </div>

                        <div class="mt-8 flex gap-3">
                            <a href="{{ route('admin.invoice-adjustments.create', ['invoice_id' => $invoice->invoice_id]) }}" class="btn btn-secondary flex-1 justify-center">Batal</a>
                            <button type="submit" class="btn btn-primary flex-1 justify-center shadow-lg shadow-indigo-500/30" :disabled="grandTotal == originalTotal">
                                <i class="material-icons text-sm mr-2">save</i> Simpan Koreksi
                            </button>
                        </div>
                    </div>
                </div>

            </div>
        </div>
    </form>
</div>

@php
    // Prepare Data for AlpineJS
    $initialItems = $invoice->items->map(function($item) {
        return [
            'id' => $item->item_id,
            'product_id' => $item->product_id,
            'price' => (float)$item->price_per_unit,
            'qty' => (float)$item->quantity,
            'original_qty' => (float)$item->quantity, 
            'stock' => (float)($item->product->stock_quantity ?? 0),
            'subtotal' => (float)$item->subtotal
        ];
    });

    $initialCosts = $invoice->additionalCosts->map(function($cost) {
        return ['desc' => $cost->description, 'amount' => (float)$cost->amount];
    });

    $initialTaxes = $invoice->taxes->pluck('id')->toArray();
@endphp

@push('scripts')
<script>
    function autoAdjustmentForm() {
        return {
            items: [],
            costs: [],
            subtotal: 0,
            discountPercent: {{ $invoice->discount_percentage ?? 0 }},
            discountAmount: 0,
            taxableBase: 0,
            selectedTaxes: [],
            selectedTaxRates: {},
            taxAmount: 0,
            totalAdditionalCosts: 0,
            grandTotal: 0,
            originalTotal: {{ $invoice->total_amount }},
            amountPaid: {{ $invoice->amount_paid }}, 
            taxesRef: @json($taxes->pluck('rate', 'id')),

            initData() {
                const prefilledItems = @json($initialItems);
                this.items = prefilledItems.map(item => ({
                    ...item,
                    key: Date.now() + Math.random(),
                    formatted_price: this.formatNumber(item.price)
                }));

                const prefilledCosts = @json($initialCosts);
                this.costs = prefilledCosts.map(cost => ({ ...cost, key: Date.now() + Math.random() }));

                const prefilledTaxIds = @json($initialTaxes);
                prefilledTaxIds.forEach(id => {
                    if (this.taxesRef[id] !== undefined) {
                        this.selectedTaxes.push(id);
                        this.selectedTaxRates[id] = parseFloat(this.taxesRef[id]);
                    }
                });

                this.calculateTotal();
            },

            addItem() {
                this.items.push({
                    key: Date.now() + Math.random(),
                    product_id: '',
                    stock: 0,
                    price: 0,
                    formatted_price: '',
                    qty: 1,
                    original_qty: 0,
                    subtotal: 0
                });
            },
            
            removeItem(index) { 
                this.items.splice(index, 1); 
                this.calculateTotal(); 
            },

            addCost() { 
                this.costs.push({ key: Date.now() + Math.random(), desc: '', amount: 0 }); 
            },
            
            removeCost(index) { 
                this.costs.splice(index, 1); 
                this.calculateTotal(); 
            },

            toggleTax(id, rate) {
                if (this.selectedTaxes.includes(id)) {
                    this.selectedTaxes = this.selectedTaxes.filter(t => t !== id);
                    delete this.selectedTaxRates[id];
                } else {
                    this.selectedTaxes.push(id);
                    this.selectedTaxRates[id] = rate;
                }
                this.calculateTotal();
            },

            handlePriceInput(index, value) {
                let raw = value.replace(/\./g, '').replace(/,/g, '').replace(/\D/g, '');
                this.items[index].price = parseFloat(raw) || 0;
                this.calculateRow(index);
            },
            
            formatPriceOnBlur(index) {
                this.items[index].formatted_price = this.formatNumber(this.items[index].price);
            },

            calculateRow(index) {
                const item = this.items[index];
                item.subtotal = item.price * item.qty;
                this.calculateTotal();
            },

            calculateTotal() {
                this.subtotal = this.items.reduce((sum, item) => sum + item.subtotal, 0);
                this.discountAmount = this.subtotal * (this.discountPercent / 100);
                this.taxableBase = Math.max(0, this.subtotal - this.discountAmount);

                let totalRate = 0;
                this.selectedTaxes.forEach(id => totalRate += this.selectedTaxRates[id]);
                this.taxAmount = this.taxableBase * (totalRate / 100);

                this.totalAdditionalCosts = this.costs.reduce((sum, c) => sum + (parseFloat(c.amount) || 0), 0);
                
                this.grandTotal = this.taxableBase + this.taxAmount + this.totalAdditionalCosts;
            },

            get diffValue() {
                return this.grandTotal - this.originalTotal;
            },

            updateProduct(index, el, productId) {
                const option = el.querySelector(`option[value="${productId}"]`);
                if (option) {
                    const price = parseFloat(option.dataset.price) || 0;
                    const stock = parseFloat(option.dataset.stock) || 0;
                    
                    this.items[index].product_id = productId;
                    this.items[index].price = price;
                    this.items[index].formatted_price = this.formatNumber(price);
                    this.items[index].stock = stock;
                    // Reset qty ke 1 jika produk diganti (kecuali user set 0)
                    if(this.items[index].qty === 0) this.items[index].qty = 1; 
                    this.items[index].original_qty = 0;
                    
                    this.calculateRow(index);
                }
            },

            initTomSelect(el, index) {
                if (el.tomselect) return;
                new TomSelect(el, {
                    ...window.defaultTomSelectConfig,
                    placeholder: 'Cari Produk...',
                    onChange: (value) => { this.updateProduct(index, el, value); }
                });
                if (this.items[index].product_id) {
                    el.tomselect.setValue(this.items[index].product_id, true);
                }
            },

            formatNumber(num) {
                return new Intl.NumberFormat('id-ID').format(Math.round(num));
            },

            validateForm(e) {
                if (this.items.length === 0) {
                    e.preventDefault();
                    window.showToast('Minimal harus ada 1 barang (walaupun qty 0).', 'error');
                    return;
                }
                
                if (this.grandTotal === this.originalTotal) {
                    e.preventDefault();
                    window.showToast('Tidak ada perubahan nilai. Koreksi tidak diperlukan.', 'warning');
                    return;
                }

                let valid = true;
                // Revisi validasi: Qty boleh 0 (untuk hapus), tapi tidak boleh kosong/null
                this.items.forEach(item => { 
                    if (!item.product_id) valid = false; 
                    if (item.qty === '' || item.qty === null || item.qty < 0) valid = false; 
                });
                
                if (!valid) {
                    e.preventDefault();
                    window.showToast('Data produk tidak lengkap (Cek Produk/Qty).', 'error');
                }
            }
        }
    }
</script>
@endpush
@endsection
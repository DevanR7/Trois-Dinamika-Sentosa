@extends('admin.layouts.app')

@section('title', 'Buat Invoice Penjualan')

@section('content')
<div x-data="invoiceForm()" x-init="initData()" class="max-w-7xl mx-auto">

    {{-- HEADER --}}
    <div class="flex items-center gap-4 mb-6">
        <a href="{{ route('admin.invoices.index') }}" class="btn-icon btn-secondary">
            <i class="material-icons">arrow_back</i>
        </a>
        <div>
            <h1 class="page-title text-xl">Buat Invoice Baru</h1>
            <p class="text-sm text-slate-500 dark:text-slate-400">Isi formulir di bawah untuk menerbitkan nota penjualan.</p>
        </div>
    </div>

    {{-- MAIN FORM CARD --}}
    <form action="{{ route('admin.invoices.store') }}" method="POST" @submit="validateForm($event)">
        @csrf
        
        @if(isset($order))
            <input type="hidden" name="sales_order_id" value="{{ $order->order_id }}">
        @endif

        <div class="card overflow-hidden">
            <div class="h-1 bg-indigo-500 w-full"></div>

            <div class="p-6 md:p-8 space-y-8">

                {{-- SECTION 1: INFORMASI UTAMA --}}
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
                    {{-- Klien --}}
                    <div class="lg:col-span-1">
                        <label class="form-label label-required">Klien / Pelanggan</label>
                        <select name="client_id" class="tom-select" required>
                            <option value="">Pilih Klien...</option>
                            @foreach($clients as $client)
                                <option value="{{ $client->client_id }}" {{ (old('client_id') == $client->client_id || (isset($order) && $order->client_id == $client->client_id)) ? 'selected' : '' }}>
                                    {{ $client->client_name }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    {{-- Sales Person --}}
                    <div class="lg:col-span-1">
                        <label class="form-label label-required">Sales Person</label>
                        @if(Auth::user()->hasRole('sales'))
                            <input type="text" class="form-input bg-slate-100 text-slate-500 cursor-not-allowed" value="{{ Auth::user()->full_name }}" readonly>
                            <input type="hidden" name="user_id_sales" value="{{ Auth::user()->user_id }}">
                        @else
                            <select name="user_id_sales" class="tom-select">
                                <option value="">Pilih Sales...</option>
                                @foreach($salesUsers as $sales)
                                    <option value="{{ $sales->user_id }}" {{ (old('user_id_sales') == $sales->user_id || (isset($order) && $order->user_id_sales == $sales->user_id)) ? 'selected' : '' }}>
                                        {{ $sales->full_name }} ({{ $sales->sales_code }})
                                    </option>
                                @endforeach
                            </select>
                        @endif
                    </div>

                    {{-- Tanggal --}}
                    <div class="lg:col-span-1">
                        <label class="form-label label-required">Tanggal Terbit</label>
                        <input type="date" name="order_date" class="form-input" value="{{ old('order_date', isset($order) ? $order->order_date->format('Y-m-d') : date('Y-m-d')) }}" required>
                    </div>
                    <div class="lg:col-span-1">
                        <label class="form-label label-required">Jatuh Tempo</label>
                        <input type="date" name="due_date" class="form-input" value="{{ old('due_date', isset($order) ? $order->order_date->addDays(30)->format('Y-m-d') : date('Y-m-d', strtotime('+30 days'))) }}" required>
                    </div>
                </div>

                <hr class="border-slate-100 dark:border-slate-700">

                {{-- SECTION 2: ITEM BARANG --}}
                <div>
                    <div class="flex justify-between items-center mb-4">
                        <h3 class="font-bold text-slate-700 dark:text-white flex items-center gap-2">
                            <i class="material-icons text-indigo-500">shopping_cart</i> Item Barang
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
                                    <th class="px-4 py-3 w-32 text-right">Stok</th>
                                    <th class="px-4 py-3 w-48 text-right">Harga Satuan</th>
                                    <th class="px-4 py-3 w-24 text-center">Qty</th>
                                    <th class="px-4 py-3 w-40 text-right">Total</th>
                                    <th class="px-4 py-3 w-10"></th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-100 dark:divide-slate-700 bg-white dark:bg-slate-800/50">
                                <template x-for="(item, index) in items" :key="item.key">
                                    <tr>
                                        {{-- Produk --}}
                                        <td class="px-4 py-3 align-top">
                                            <select :name="`products[${index}][product_id]`" class="tom-select-dynamic w-full" x-init="initTomSelect($el, index)" required>
                                                <option value="">Pilih Produk...</option>
                                                @foreach($products as $product)
                                                    <option value="{{ $product->product_id }}"
                                                        data-price="{{ $product->selling_price }}"
                                                        data-stock="{{ $product->stock_quantity }}">
                                                        {{ $product->product_name }} ({{ $product->product_code }})
                                                    </option>
                                                @endforeach
                                            </select>
                                        </td>
                                        
                                        {{-- Stok --}}
                                        <td class="px-4 py-3 align-top text-right pt-4">
                                            <span class="font-mono text-xs" :class="item.stock > 0 ? 'text-emerald-600' : 'text-red-500'" x-text="formatNumber(item.stock)"></span>
                                        </td>

                                        {{-- Harga Satuan (Format Rupiah) --}}
                                        <td class="px-4 py-3 align-top">
                                            <div class="relative">
                                                <span class="absolute left-3 top-2 text-slate-400 text-xs font-bold">Rp</span>
                                                {{-- Input Visual (Formatted) --}}
                                                <input type="text" 
                                                       x-model="item.formatted_price"
                                                       @input="handlePriceInput(index, $el.value)"
                                                       @blur="formatPriceOnBlur(index)"
                                                       class="form-input text-right pl-8 h-9 font-mono" 
                                                       placeholder="0"
                                                       required>
                                                {{-- Input Hidden (Angka Murni untuk Submit) --}}
                                                <input type="hidden" :name="`products[${index}][custom_price]`" :value="item.price">
                                            </div>
                                        </td>

                                        {{-- Qty --}}
                                        <td class="px-4 py-3 align-top">
                                            <input type="number" :name="`products[${index}][quantity]`" x-model.number="item.qty" @input="calculateRow(index)" class="form-input text-center h-9" min="1" required>
                                        </td>

                                        {{-- Total --}}
                                        <td class="px-4 py-3 align-top text-right pt-4">
                                            <span class="font-bold text-slate-700 dark:text-white" x-text="formatNumber(item.subtotal)"></span>
                                        </td>

                                        {{-- Hapus --}}
                                        <td class="px-4 py-3 align-top text-center pt-2">
                                            <button type="button" @click="removeItem(index)" class="text-slate-400 hover:text-red-500 transition-colors">
                                                <i class="material-icons text-lg">delete</i>
                                            </button>
                                        </td>
                                    </tr>
                                </template>
                                <tr x-show="items.length === 0">
                                    <td colspan="6" class="px-6 py-8 text-center text-slate-400 italic">
                                        Klik tombol "Tambah Barang" untuk memulai.
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>

                {{-- SECTION 3: BIAYA LAIN & CATATAN --}}
                <div class="grid grid-cols-1 lg:grid-cols-2 gap-8 mt-4">
                    <div>
                        {{-- Additional Cost --}}
                        <div class="flex justify-between items-center mb-3">
                            <h3 class="font-bold text-slate-700 dark:text-white flex items-center gap-2 text-sm">
                                <i class="material-icons text-amber-500 text-sm">add_circle</i> Biaya Tambahan
                            </h3>
                            <button type="button" @click="addCost()" class="text-xs text-indigo-600 font-bold hover:underline">+ Tambah Biaya</button>
                        </div>
                        <div class="space-y-3">
                            <template x-for="(cost, index) in costs" :key="cost.key">
                                <div class="flex gap-3 items-start">
                                    <input type="text" :name="`additional_costs[${index}][description]`" x-model="cost.desc" class="form-input h-9 text-xs" placeholder="Deskripsi (Mis: Ongkir)">
                                    <div class="relative w-32">
                                        <span class="absolute left-2 top-2 text-xs text-slate-400">Rp</span>
                                        <input type="number" :name="`additional_costs[${index}][amount]`" x-model.number="cost.amount" @input="calculateTotal()" class="form-input h-9 text-xs text-right pl-6" placeholder="0">
                                    </div>
                                    <button type="button" @click="removeCost(index)" class="text-slate-400 hover:text-red-500 mt-2"><i class="material-icons text-sm">close</i></button>
                                </div>
                            </template>
                            <div x-show="costs.length === 0" class="text-xs text-slate-400 italic p-2 border border-dashed rounded text-center">Tidak ada biaya tambahan.</div>
                        </div>

                        {{-- Notes --}}
                        <div class="mt-6">
                            <label class="form-label">Catatan Invoice</label>
                            <textarea name="notes" class="form-textarea h-24 text-xs" placeholder="Catatan untuk klien...">{{ old('notes', $order->notes ?? '') }}</textarea>
                        </div>
                    </div>

                    {{-- SECTION 4: RINGKASAN --}}
                    <div class="bg-slate-50 dark:bg-slate-800/50 rounded-xl p-6 border border-slate-200 dark:border-slate-700 h-fit">
                        <h3 class="font-bold text-slate-700 dark:text-white mb-4">Ringkasan Pembayaran</h3>
                        <div class="space-y-3 text-sm">
                            <div class="flex justify-between text-slate-600 dark:text-slate-400">
                                <span>Subtotal Barang</span>
                                <span class="font-bold" x-text="formatNumber(subtotal)">0</span>
                            </div>
                            
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
                                <span>Dasar Pengenaan Pajak (DPP)</span>
                                <span x-text="formatNumber(taxableBase)">0</span>
                            </div>

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

                            <div class="flex justify-between items-center">
                                <span class="text-base font-bold text-slate-800 dark:text-white">Total Tagihan</span>
                                <span class="text-2xl font-black text-indigo-600 dark:text-indigo-400">Rp <span x-text="formatNumber(grandTotal)">0</span></span>
                            </div>
                        </div>

                        <div class="mt-8 flex gap-3">
                            <a href="{{ route('admin.invoices.index') }}" class="btn btn-secondary flex-1 justify-center">Batal</a>
                            <button type="submit" class="btn btn-primary flex-1 justify-center shadow-lg shadow-indigo-500/30"><i class="material-icons text-sm mr-2">save</i> Simpan Draft</button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </form>
</div>

@php
    $initialItems = [];
    if(isset($order)) {
        $initialItems = $order->items->map(function($item) {
            return [
                'product_id' => $item->product_id,
                'price' => (float)$item->price_per_unit,
                'qty' => (float)$item->quantity,
                'stock' => (float)$item->product->stock_quantity,
                'subtotal' => (float)$item->subtotal
            ];
        });
    }
@endphp

@push('scripts')
<script>
    function invoiceForm() {
        return {
            items: [],
            costs: [],
            subtotal: 0,
            discountPercent: 60,
            discountAmount: 0,
            taxableBase: 0,
            selectedTaxes: [],
            selectedTaxRates: {},
            taxAmount: 0,
            totalAdditionalCosts: 0,
            grandTotal: 0,

            initData() {
                const prefilled = @json($initialItems);
                if (prefilled.length > 0) {
                    this.items = prefilled.map(item => ({
                        ...item,
                        key: Date.now() + Math.random(),
                        formatted_price: this.formatNumber(item.price) // Init Format
                    }));
                } else {
                    this.addItem();
                }
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
                    subtotal: 0
                });
            },
            removeItem(index) {
                this.items.splice(index, 1);
                this.calculateTotal();
            },

            addCost() { this.costs.push({ key: Date.now() + Math.random(), desc: '', amount: 0 }); },
            removeCost(index) { this.costs.splice(index, 1); this.calculateTotal(); },

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

            // --- Logic Format Rupiah ---
            handlePriceInput(index, value) {
                // Hapus semua karakter kecuali angka
                let raw = value.replace(/\./g, '').replace(/,/g, '').replace(/\D/g, '');
                
                // Update Nilai Asli (Float)
                this.items[index].price = parseFloat(raw) || 0;
                
                // Hitung ulang baris
                this.calculateRow(index);
            },

            formatPriceOnBlur(index) {
                // Format kembali tampilan saat blur (tambah titik ribuan)
                this.items[index].formatted_price = this.formatNumber(this.items[index].price);
            },

            updateProduct(index, el, productId) {
                const option = el.querySelector(`option[value="${productId}"]`);
                if (option) {
                    const price = parseFloat(option.dataset.price) || 0;
                    const stock = parseFloat(option.dataset.stock) || 0;
                    
                    this.items[index].product_id = productId;
                    this.items[index].price = price;
                    this.items[index].formatted_price = this.formatNumber(price); // Set display
                    this.items[index].stock = stock;
                    this.items[index].qty = 1;
                    
                    this.calculateRow(index);
                }
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
                    window.showToast('Masukkan minimal satu barang.', 'error');
                    return;
                }
                let valid = true;
                this.items.forEach(item => {
                    if (!item.product_id || item.qty <= 0) valid = false;
                });
                if (!valid) {
                    e.preventDefault();
                    window.showToast('Lengkapi data produk.', 'error');
                }
            }
        }
    }
</script>
@endpush
@endsection
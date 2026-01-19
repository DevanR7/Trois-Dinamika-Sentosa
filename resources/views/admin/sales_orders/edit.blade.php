@extends('admin.layouts.app')

@section('title', 'Edit Pesanan #' . $order->order_number)

@section('content')
<div x-data="salesOrderEditForm()" x-init="initData()" class="flex flex-col gap-6">

    {{-- HEADER --}}
    <div class="flex items-center gap-4">
        {{-- FIX: Button Back Simetris --}}
        <a href="{{ route('admin.sales-orders.index') }}" 
           class="w-9 h-9 flex items-center justify-center rounded-lg bg-white border border-slate-200 text-slate-500 hover:bg-slate-100 hover:text-indigo-600 transition-all dark:bg-slate-800 dark:border-slate-700 dark:text-slate-400 dark:hover:text-white shadow-sm">
            <i class="material-icons text-xl leading-none">arrow_back</i>
        </a>
        <div>
            <h1 class="page-title text-xl">Edit Pesanan <span class="text-indigo-600">#{{ $order->order_number }}</span></h1>
            <p class="text-sm text-slate-500 dark:text-slate-400">Ubah detail pesanan penjualan (Status: {{ ucfirst($order->status) }}).</p>
        </div>
    </div>

    {{-- FORM UTAMA --}}
    <form action="{{ route('admin.sales-orders.update', $order->order_id) }}" method="POST" class="flex flex-col gap-6" @submit="validateForm($event)">
        @csrf
        @method('PUT')

        {{-- SECTION 1: INFO PESANAN --}}
        <div class="card p-6">
            <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                
                {{-- Klien --}}
                <div class="col-span-1">
                    <label class="form-label label-required">Klien / Pelanggan</label>
                    <select name="client_id" class="tom-select" required placeholder="Pilih Klien...">
                        <option value="">- Pilih Klien -</option>
                        @foreach($clients as $client)
                            <option value="{{ $client->client_id }}" {{ old('client_id', $order->client_id) == $client->client_id ? 'selected' : '' }}>
                                {{ $client->client_name }}
                            </option>
                        @endforeach
                    </select>
                    @error('client_id') <p class="invalid-feedback">{{ $message }}</p> @enderror
                </div>

                {{-- Tanggal Order --}}
                <div class="col-span-1">
                    <label class="form-label label-required">Tanggal Pesanan</label>
                    <input type="date" name="order_date" class="form-input" value="{{ old('order_date', $order->order_date->format('Y-m-d')) }}" required>
                    @error('order_date') <p class="invalid-feedback">{{ $message }}</p> @enderror
                </div>

                {{-- Sales Person --}}
                <div class="col-span-1">
                    <label class="form-label label-required">Salesperson</label>
                    @if(Auth::user()->hasRole('sales') && Auth::id() != $order->user_id_sales)
                        <input type="text" class="form-input bg-slate-100 text-slate-500 cursor-not-allowed" value="{{ $order->sales->full_name ?? '-' }}" readonly>
                        <input type="hidden" name="sales_id" value="{{ $order->user_id_sales }}">
                    @elseif(Auth::user()->hasRole('sales'))
                         <input type="text" class="form-input bg-slate-100 text-slate-500 cursor-not-allowed" value="{{ Auth::user()->full_name }}" readonly>
                         <input type="hidden" name="sales_id" value="{{ Auth::user()->user_id }}">
                    @else
                        <select name="sales_id" class="tom-select">
                            <option value="">- Pilih Sales -</option>
                            @foreach($salesUsers as $sales)
                                <option value="{{ $sales->user_id }}" {{ old('sales_id', $order->user_id_sales) == $sales->user_id ? 'selected' : '' }}>
                                    {{ $sales->full_name }}
                                </option>
                            @endforeach
                        </select>
                    @endif
                    @error('sales_id') <p class="invalid-feedback">{{ $message }}</p> @enderror
                </div>

            </div>
        </div>

        {{-- SECTION 2: ITEM PRODUK --}}
        <div class="card overflow-hidden">
            <div class="card-header flex justify-between items-center bg-slate-50 dark:bg-slate-800 border-b border-slate-200 dark:border-slate-700">
                <h3 class="font-bold text-slate-700 dark:text-white">Daftar Barang</h3>
                <button type="button" @click="addItem()" class="btn btn-sm btn-primary">
                    <i class="material-icons text-sm mr-1">add</i> Tambah Baris
                </button>
            </div>

            <div class="overflow-x-auto">
                <table class="w-full text-sm text-left">
                    <thead class="text-xs text-slate-500 uppercase bg-slate-100 dark:bg-slate-700/50">
                        <tr>
                            <th class="px-4 py-3 min-w-[250px]">Produk</th>
                            <th class="px-4 py-3 w-32 text-right">Stok</th>
                            <th class="px-4 py-3 w-40 text-right">Harga Satuan</th>
                            <th class="px-4 py-3 w-32 text-center">Qty</th>
                            <th class="px-4 py-3 w-40 text-right">Subtotal</th>
                            <th class="px-4 py-3 w-16 text-center">Hapus</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 dark:divide-slate-700">
                        <template x-for="(item, index) in items" :key="item.key">
                            <tr class="hover:bg-slate-50 dark:hover:bg-slate-800/50 transition-colors">
                                {{-- Produk Select --}}
                                <td class="px-4 py-3 align-top">
                                    <select 
                                        :name="`products[${index}][product_id]`" 
                                        class="tom-select-dynamic w-full"
                                        x-init="initTomSelect($el, index)"
                                        x-model="item.product_id"
                                        required
                                    >
                                        <option value="">Pilih Produk...</option>
                                        @foreach($products as $product)
                                            <option value="{{ $product->product_id }}" 
                                                data-price="{{ $product->selling_price }}"
                                                data-stock="{{ $product->stock_quantity }}"
                                                data-code="{{ $product->product_code }}">
                                                {{ $product->product_name }}
                                            </option>
                                        @endforeach
                                    </select>
                                    <div class="text-[10px] text-slate-400 mt-1" x-text="item.code || '-'"></div>
                                    {{-- Hidden input untuk item_id --}}
                                    <input type="hidden" :name="`products[${index}][item_id]`" :value="item.id"> 
                                </td>

                                {{-- Stok Info --}}
                                <td class="px-4 py-3 text-right align-top">
                                    <span class="font-mono font-bold" 
                                          :class="{'text-red-500': item.stock <= 0, 'text-emerald-600': item.stock > 0}" 
                                          x-text="formatNumber(item.stock)">0</span>
                                </td>

                                {{-- Harga Satuan --}}
                                <td class="px-4 py-3 text-right align-top">
                                    <div class="relative">
                                        <span class="text-xs text-slate-400 absolute left-0 top-1">Rp</span>
                                        <input type="text" 
                                               class="text-right bg-transparent border-0 p-0 w-full focus:ring-0 font-medium text-slate-700 dark:text-slate-300 cursor-not-allowed" 
                                               :value="formatNumber(item.price)" 
                                               readonly>
                                    </div>
                                </td>

                                {{-- Qty Input --}}
                                <td class="px-4 py-3 align-top">
                                    <input type="number" 
                                           :name="`products[${index}][quantity]`" 
                                           x-model.number="item.quantity"
                                           @input="calculateRow(index)"
                                           min="1" 
                                           :max="item.stock > 0 ? item.stock : 1"
                                           class="form-input text-center h-9" 
                                           required>
                                    {{-- Validasi Visual Stok --}}
                                    <div x-show="item.quantity > item.stock && item.stock > 0" class="text-[10px] text-red-500 mt-1 text-center font-bold">
                                        Stok Kurang!
                                    </div>
                                </td>

                                {{-- Subtotal --}}
                                <td class="px-4 py-3 text-right align-top">
                                    <span class="font-bold text-indigo-600 dark:text-indigo-400" x-text="'Rp ' + formatNumber(item.subtotal)"></span>
                                </td>

                                {{-- Delete Button --}}
                                <td class="px-4 py-3 text-center align-top">
                                    <button type="button" @click="removeItem(index)" class="text-slate-400 hover:text-red-500 transition-colors" title="Hapus Baris">
                                        <i class="material-icons text-xl">delete</i>
                                    </button>
                                </td>
                            </tr>
                        </template>
                        
                        {{-- Empty State --}}
                        <tr x-show="items.length === 0">
                            <td colspan="6" class="px-6 py-10 text-center text-slate-400">
                                <div class="flex flex-col items-center justify-center">
                                    <i class="material-icons text-4xl mb-2 text-slate-300">add_shopping_cart</i>
                                    <p class="text-sm">Belum ada barang.</p>
                                    <button type="button" @click="addItem()" class="text-indigo-600 font-bold hover:underline text-sm mt-1">Klik tambah baris</button>
                                </div>
                            </td>
                        </tr>
                    </tbody>
                    
                    {{-- Footer Total --}}
                    <tfoot class="bg-slate-50 dark:bg-slate-800/50 border-t border-slate-200 dark:border-slate-700" x-show="items.length > 0">
                        <tr>
                            <td colspan="4" class="px-4 py-4 text-right font-bold text-slate-600 dark:text-slate-400 uppercase text-xs tracking-wider">Total Estimasi</td>
                            <td class="px-4 py-4 text-right">
                                <span class="text-lg font-black text-slate-800 dark:text-white" x-text="'Rp ' + formatNumber(grandTotal)"></span>
                            </td>
                            <td></td>
                        </tr>
                    </tfoot>
                </table>
            </div>
        </div>

        {{-- SECTION 3: NOTES & SUBMIT --}}
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <div class="card p-4">
                <label class="form-label">Catatan Pesanan</label>
                <textarea name="notes" class="form-textarea h-24" placeholder="Catatan internal sales...">{{ old('notes', $order->notes) }}</textarea>
            </div>
            
            <div class="flex flex-col justify-end gap-3">
                <div class="card p-4 bg-amber-50 dark:bg-amber-900/20 border border-amber-100 dark:border-amber-800">
                    <div class="flex gap-3">
                        <i class="material-icons text-amber-500">warning</i>
                        <div>
                            <h4 class="font-bold text-amber-700 dark:text-amber-400 text-sm">Mode Edit</h4>
                            <p class="text-xs text-amber-600/80 dark:text-amber-500 mt-1">
                                Data pesanan akan ditimpa dengan data baru di atas. Stok akan divalidasi ulang saat disimpan.
                            </p>
                        </div>
                    </div>
                </div>
                
                <div class="flex gap-3 mt-auto">
                    <a href="{{ route('admin.sales-orders.index') }}" class="btn btn-secondary flex-1 justify-center">Batal</a>
                    <button type="submit" class="btn btn-primary flex-1 justify-center shadow-lg shadow-indigo-500/30">
                        <i class="material-icons text-sm mr-2">save</i> Simpan Perubahan
                    </button>
                </div>
            </div>
        </div>

    </form>
</div>

@php
    $initialData = $order->items->map(function($item) {
        return [
            'id' => $item->item_id, 
            'product_id' => $item->product_id,
            'code' => $item->product->product_code ?? '',
            'price' => (float) $item->price_per_unit,
            'stock' => (float) ($item->product->stock_quantity ?? 0), 
            'quantity' => (float) $item->quantity,
            'subtotal' => (float) $item->subtotal
        ];
    });
@endphp

@push('scripts')
<script>
    function salesOrderEditForm() {
        return {
            items: [],
            grandTotal: 0,

            initData() {
                const existingItems = @json($initialData);

                this.items = existingItems.map(item => ({
                    ...item,
                    key: Date.now() + Math.random().toString(36).substr(2, 9)
                }));

                this.calculateTotal();
            },

            addItem() {
                this.items.push({
                    id: null, 
                    key: Date.now() + Math.random().toString(36).substr(2, 9),
                    product_id: '',
                    code: '',
                    price: 0,
                    stock: 0,
                    quantity: 1,
                    subtotal: 0
                });
            },

            removeItem(index) {
                this.items.splice(index, 1);
                this.calculateTotal();
            },

            initTomSelect(el, index) {
                if (el.tomselect) return;

                new TomSelect(el, {
                    ...window.defaultTomSelectConfig,
                    placeholder: 'Pilih Produk...',
                    onChange: (value) => {
                        this.updateRow(index, el, value);
                    }
                });

                if (this.items[index].product_id) {
                    el.tomselect.setValue(this.items[index].product_id, true);
                }
            },

            updateRow(index, selectElement, productId) {
                const option = selectElement.querySelector(`option[value="${productId}"]`);
                
                if (option) {
                    const price = parseFloat(option.dataset.price) || 0;
                    const stock = parseFloat(option.dataset.stock) || 0;
                    const code = option.dataset.code || '';

                    this.items[index].product_id = productId;
                    this.items[index].price = price;
                    this.items[index].stock = stock;
                    this.items[index].code = code;
                    
                    this.items[index].quantity = 1;

                    this.calculateRow(index);
                }
            },

            calculateRow(index) {
                const item = this.items[index];
                item.subtotal = item.price * item.quantity;
                this.calculateTotal();
            },

            calculateTotal() {
                this.grandTotal = this.items.reduce((sum, item) => sum + item.subtotal, 0);
            },

            formatNumber(num) {
                return new Intl.NumberFormat('id-ID').format(num);
            },

            validateForm(e) {
                if (this.items.length === 0) {
                    e.preventDefault();
                    window.showToast('Minimal harus ada 1 barang.', 'error');
                    return;
                }
                
                let isValid = true;
                this.items.forEach(item => {
                    if (item.quantity > item.stock && item.stock >= 0) {
                        isValid = false;
                    }
                    if(!item.product_id) isValid = false;
                });

                if (!isValid) {
                    e.preventDefault();
                    window.showToast('Periksa kembali: Ada produk kosong atau jumlah melebihi stok.', 'error');
                }
            }
        }
    }
</script>
@endpush
@endsection
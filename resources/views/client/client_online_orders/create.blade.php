@extends('client.layouts.app')

@section('title', 'Buat Pesanan Baru')

@section('content')

    <div class="max-w-5xl mx-auto">
        
        {{-- Header --}}
        <div class="mb-6">
            <a href="{{ route('client.client-orders.index') }}" class="text-slate-500 hover:text-indigo-600 text-sm font-medium flex items-center gap-1 mb-2">
                <i class="material-icons text-sm">arrow_back</i> Kembali ke Daftar
            </a>
            <h1 class="text-2xl font-bold text-slate-800 dark:text-white">Formulir Pesanan</h1>
            <p class="text-slate-500 text-sm mt-1">Silahkan pilih produk yang ingin Anda pesan.</p>
        </div>

        <form action="{{ route('client.client-orders.store') }}" method="POST" x-data="orderForm()" id="order-form">
            @csrf
            
            <div class="flex flex-col gap-6">
                
                {{-- 1. Informasi Umum --}}
                <div class="card">
                    <div class="card-header">
                        <h3 class="card-header-title">Informasi Pesanan</h3>
                    </div>
                    <div class="card-body grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div>
                            <label class="block text-sm font-bold text-slate-700 dark:text-slate-300 mb-2">Tanggal Pesanan</label>
                            <input type="date" name="order_date" value="{{ date('Y-m-d') }}" class="form-input w-full rounded-lg border-slate-300 dark:bg-slate-800 dark:border-slate-600 dark:text-white" required>
                        </div>
                        <div>
                            <label class="block text-sm font-bold text-slate-700 dark:text-slate-300 mb-2">Catatan (Opsional)</label>
                            <input type="text" name="notes" class="form-input w-full rounded-lg border-slate-300 dark:bg-slate-800 dark:border-slate-600 dark:text-white" placeholder="Contoh: Kirim sebelum jam 12 siang...">
                        </div>
                    </div>
                </div>

                {{-- 2. Daftar Produk --}}
                <div class="card">
                    <div class="card-header justify-between">
                        <h3 class="card-header-title">Item Produk</h3>
                        <button type="button" @click="addItem()" class="btn btn-primary btn-sm">
                            <i class="material-icons text-sm">add</i> Tambah Baris
                        </button>
                    </div>
                    
                    <div class="card-body p-0">
                        <div class="table-container border-0 shadow-none rounded-none overflow-visible">
                            <table class="table-modern w-full">
                                <thead>
                                    <tr>
                                        <th class="w-[45%]">Produk</th>
                                        <th class="w-[15%] text-center">Qty</th>
                                        <th class="w-[20%] text-right">Harga Satuan</th>
                                        <th class="w-[20%] text-right">Subtotal</th>
                                        <th class="w-[50px]"></th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <template x-for="(item, index) in items" :key="item.id">
                                        <tr>
                                            <td class="align-top p-4">
                                                {{-- Product Select (Custom wrapper for TomSelect) --}}
                                                <div class="relative">
                                                    <select 
                                                        :name="`products[${index}][product_id]`"
                                                        class="tom-select-product w-full"
                                                        x-init="initTomSelect($el, index)"
                                                        required>
                                                        <option value="">Pilih Produk...</option>
                                                        @foreach($products as $product)
                                                            <option value="{{ $product->product_id }}" 
                                                                data-price="{{ $product->selling_price }}"
                                                                data-stock="{{ $product->stock_quantity }}">
                                                                {{ $product->product_name }} 
                                                                ({{ $product->stock_quantity }} {{ $product->unit->name ?? 'Unit' }})
                                                            </option>
                                                        @endforeach
                                                    </select>
                                                    
                                                    {{-- Stock Info Display --}}
                                                    <div class="mt-1 text-[10px]" :class="item.stock < item.quantity ? 'text-red-500 font-bold' : 'text-slate-500'">
                                                        <span x-text="item.stock_text"></span>
                                                    </div>
                                                </div>
                                            </td>
                                            <td class="align-top p-4">
                                                <input type="number" 
                                                    :name="`products[${index}][quantity]`" 
                                                    x-model.number="item.quantity"
                                                    min="1" 
                                                    class="form-input w-full text-center h-10 rounded-lg border-slate-300 focus:ring-indigo-500 focus:border-indigo-500 dark:bg-slate-800 dark:border-slate-600 dark:text-white"
                                                    required>
                                            </td>
                                            <td class="align-top text-right p-4 pt-5 text-slate-600 dark:text-slate-300">
                                                <span x-text="formatRupiah(item.price)"></span>
                                            </td>
                                            <td class="align-top text-right p-4 pt-5 font-bold text-slate-800 dark:text-white">
                                                <span x-text="formatRupiah(item.price * item.quantity)"></span>
                                            </td>
                                            <td class="align-top text-center p-4">
                                                <button type="button" @click="removeItem(index)" class="text-slate-400 hover:text-red-500 transition-colors pt-2 bg-slate-100 hover:bg-red-50 p-2 rounded-lg dark:bg-slate-800 dark:hover:bg-red-900/30">
                                                    <i class="material-icons text-lg">delete</i>
                                                </button>
                                            </td>
                                        </tr>
                                    </template>
                                </tbody>
                            </table>
                        </div>
                        
                        {{-- Empty State --}}
                        <div x-show="items.length === 0" class="p-12 text-center text-slate-400 bg-slate-50 dark:bg-slate-800/50 border-t border-slate-100 dark:border-slate-700">
                            <i class="material-icons text-4xl mb-2 text-slate-300">playlist_add</i>
                            <p>Belum ada produk dipilih. Klik tombol "Tambah Baris" di atas.</p>
                        </div>
                    </div>
                </div>

                {{-- 3. Ringkasan & Submit (Full Width di Bawah) --}}
                <div class="card bg-slate-50 dark:bg-slate-800/50 border-slate-200 dark:border-slate-700">
                    <div class="card-body">
                        <div class="flex flex-col md:flex-row justify-between items-center gap-6">
                            
                            {{-- Info Kiri --}}
                            <div class="text-center md:text-left">
                                <p class="text-sm text-slate-500 dark:text-slate-400 mb-1">Total Item</p>
                                <p class="text-lg font-bold text-slate-800 dark:text-white"><span x-text="items.length"></span> Produk</p>
                            </div>

                            {{-- Total Kanan --}}
                            <div class="text-center md:text-right">
                                <p class="text-sm text-slate-500 dark:text-slate-400 mb-1">Estimasi Total Pembayaran</p>
                                <p class="text-3xl font-extrabold text-indigo-600 dark:text-indigo-400" x-text="formatRupiah(grandTotal)"></p>
                            </div>

                            {{-- Tombol Aksi --}}
                            <div class="w-full md:w-auto flex flex-col gap-2 min-w-[200px]">
                                <button type="submit" class="btn btn-primary w-full justify-center py-3 text-base shadow-lg shadow-indigo-200 dark:shadow-none" :disabled="items.length === 0">
                                    <i class="material-icons text-sm">send</i> Kirim Pesanan
                                </button>
                                <p class="text-[10px] text-slate-400 text-center">
                                    * Stok akan diamankan setelah dikirim.
                                </p>
                            </div>
                        </div>
                    </div>
                </div>

            </div>
        </form>
    </div>

    @push('scripts')
    <script>
        document.addEventListener('alpine:init', () => {
            Alpine.data('orderForm', () => ({
                items: [],

                init() {
                    this.addItem(); // Tambahkan 1 baris awal
                },

                addItem() {
                    this.items.push({
                        id: Date.now() + Math.random(),
                        product_id: '',
                        quantity: 1,
                        price: 0,
                        stock: 0,
                        stock_text: 'Pilih produk...'
                    });
                },

                removeItem(index) {
                    this.items.splice(index, 1);
                },

                formatRupiah(number) {
                    return new Intl.NumberFormat('id-ID', { 
                        style: 'currency', 
                        currency: 'IDR',
                        minimumFractionDigits: 0 
                    }).format(number);
                },

                get grandTotal() {
                    return this.items.reduce((sum, item) => sum + (item.price * item.quantity), 0);
                },

                initTomSelect(element, index) {
                    if (element.tomselect) return;

                    new TomSelect(element, {
                        ...window.defaultTomSelectConfig,
                        placeholder: 'Cari Produk...',
                        dropdownParent: 'body', // Agar dropdown tidak terpotong overflow
                        onChange: (value) => {
                            const selectedOption = element.querySelector(`option[value="${value}"]`);
                            if (selectedOption) {
                                this.items[index].product_id = value;
                                this.items[index].price = parseFloat(selectedOption.dataset.price || 0);
                                this.items[index].stock = parseFloat(selectedOption.dataset.stock || 0);
                                this.items[index].stock_text = `Stok Tersedia: ${this.items[index].stock}`;
                            } else {
                                this.items[index].price = 0;
                                this.items[index].stock = 0;
                                this.items[index].stock_text = '';
                            }
                        }
                    });
                }
            }));
        });
    </script>
    @endpush

@endsection
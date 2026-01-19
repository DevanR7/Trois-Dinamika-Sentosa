@extends('admin.layouts.app')

@section('title', 'Input Stock Opname')

@section('content')

    {{-- Header --}}
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 mb-6">
        <div>
            <h1 class="text-2xl font-bold text-slate-800 dark:text-white tracking-tight">Stock Opname Baru</h1>
            <p class="text-sm text-slate-500 dark:text-slate-400">
                Sesuaikan stok sistem dengan stok fisik aktual di gudang.
            </p>
        </div>
        <a href="{{ route('admin.stock-opnames.index') }}" class="btn btn-secondary">
            <i class="material-icons text-[18px]">arrow_back</i>
            <span>Batal</span>
        </a>
    </div>

    {{-- Alpine Component Wrapper --}}
    <form action="{{ route('admin.stock-opnames.store') }}" method="POST" 
          x-data="stockOpnameForm(@js($products))">
        @csrf

        <div class="space-y-6"> {{-- Container Vertikal --}}

            {{-- CARD 1: INFORMASI UMUM --}}
            <div class="card p-5">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                    <div>
                        <label class="form-label label-required">Tanggal Opname</label>
                        <input type="date" name="opname_date" 
                               class="form-input @error('opname_date') is-invalid @enderror" 
                               value="{{ date('Y-m-d') }}" required>
                        @error('opname_date') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>
                    <div>
                        <label class="form-label">Catatan / Referensi</label>
                        <input type="text" name="notes" class="form-input" placeholder="Contoh: Audit Tahunan 2025">
                    </div>
                </div>
            </div>

            {{-- CARD 2: DAFTAR BARANG (Tabel) --}}
            <div class="card min-h-[400px] flex flex-col">
                <div class="card-header bg-slate-50 dark:bg-slate-800/50 flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4 py-4">
                    <h3 class="card-header-title">Daftar Barang</h3>
                    
                    {{-- Product Selector --}}
                    <div class="w-full sm:w-80" wire:ignore>
                        <select id="product-selector" class="tom-select" placeholder="Cari & Tambah Produk...">
                            <option value=""></option>
                            @foreach($products as $p)
                                <option value="{{ $p['id'] }}">
                                    {{ $p['code'] }} - {{ $p['name'] }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                </div>

                {{-- Table Area --}}
                <div class="overflow-x-auto flex-1">
                    <table class="table-modern w-full">
                        <thead>
                            <tr>
                                <th class="w-10 text-center">#</th>
                                <th class="w-16">Foto</th>
                                <th>Produk</th>
                                <th class="w-32 text-center bg-slate-100 dark:bg-slate-700/50">Stok Sistem</th>
                                <th class="w-32 text-center bg-indigo-50 dark:bg-indigo-900/20 text-indigo-700 dark:text-indigo-300 border-x border-indigo-100 dark:border-indigo-800">Fisik (Aktual)</th>
                                <th class="w-32 text-center">Selisih</th>
                                <th class="w-12"></th>
                            </tr>
                        </thead>
                        <tbody>
                            <template x-for="(row, index) in rows" :key="row.id">
                                <tr class="group hover:bg-slate-50 dark:hover:bg-slate-700/20">
                                    
                                    {{-- Index --}}
                                    <td class="text-center text-slate-400 text-xs" x-text="index + 1"></td>
                                    
                                    {{-- Image --}}
                                    <td class="p-2">
                                        <div class="w-10 h-10 rounded-lg bg-slate-100 dark:bg-slate-700 border border-slate-200 dark:border-slate-600 flex items-center justify-center overflow-hidden">
                                            <template x-if="row.image">
                                                <img :src="row.image" class="w-full h-full object-cover">
                                            </template>
                                            <template x-if="!row.image">
                                                <i class="material-icons text-slate-300 text-sm">image</i>
                                            </template>
                                        </div>
                                    </td>

                                    {{-- Product Info --}}
                                    <td>
                                        <div class="flex flex-col">
                                            <span class="font-bold text-slate-700 dark:text-slate-200 text-sm" x-text="row.name"></span>
                                            <span class="text-[11px] font-mono text-slate-500" x-text="row.code"></span>
                                            <input type="hidden" :name="`products[${index}][product_id]`" :value="row.id">
                                        </div>
                                    </td>

                                    {{-- System Qty (Readonly) --}}
                                    <td class="text-center bg-slate-50/50 dark:bg-slate-800/30">
                                        <span class="text-sm font-medium text-slate-500" x-text="row.stock"></span>
                                        <span class="text-[10px] text-slate-400 ml-1" x-text="row.unit"></span>
                                    </td>

                                    {{-- Physical Qty (Input) --}}
                                    <td class="p-0 border-x border-indigo-100 dark:border-indigo-800/50 relative">
                                        <input type="number" step="any"
                                               :name="`products[${index}][physical_qty]`"
                                               x-model.number="row.physical"
                                               class="w-full h-full absolute inset-0 border-none focus:ring-2 focus:ring-inset focus:ring-indigo-500 bg-indigo-50/30 text-center font-bold text-slate-700 dark:text-white dark:bg-slate-800"
                                               placeholder="0" required>
                                    </td>

                                    {{-- Difference (Computed) --}}
                                    <td class="text-center">
                                        <div class="flex items-center justify-center font-bold text-sm"
                                             :class="{
                                                'text-emerald-600': (row.physical - row.stock) > 0,
                                                'text-rose-600': (row.physical - row.stock) < 0,
                                                'text-slate-300': (row.physical - row.stock) == 0
                                             }">
                                            <span x-text="(row.physical - row.stock) > 0 ? '+' : ''"></span>
                                            <span x-text="parseFloat((row.physical - row.stock).toFixed(2))"></span>
                                        </div>
                                    </td>

                                    {{-- Delete Action --}}
                                    <td class="text-center">
                                        <button type="button" @click="removeRow(index)" class="text-slate-400 hover:text-rose-500 transition-colors p-1">
                                            <i class="material-icons text-[18px]">close</i>
                                        </button>
                                    </td>
                                </tr>
                            </template>

                            {{-- Empty State --}}
                            <template x-if="rows.length === 0">
                                <tr>
                                    <td colspan="7" class="py-16 text-center">
                                        <div class="flex flex-col items-center justify-center opacity-50">
                                            <i class="material-icons text-4xl text-slate-300 mb-2">playlist_add</i>
                                            <p class="text-sm text-slate-500">Belum ada produk yang ditambahkan.</p>
                                            <p class="text-xs text-slate-400">Gunakan pencarian di atas untuk mulai opname.</p>
                                        </div>
                                    </td>
                                </tr>
                            </template>
                        </tbody>
                    </table>
                </div>
            </div>

            {{-- CARD 3: RINGKASAN & AKSI (Di Bawah) --}}
            <div class="flex flex-col lg:flex-row justify-end items-start gap-6">
                
                {{-- Spacer (Kosong di kiri agar card ringkasan ke kanan) --}}
                <div class="hidden lg:block lg:w-1/2"></div>

                {{-- Summary Card (Lebar 50% di Desktop) --}}
                <div class="w-full lg:w-1/2">
                    <div class="card p-5 bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700 shadow-md">
                        <h3 class="text-sm font-bold text-slate-500 uppercase tracking-wider mb-4 border-b border-slate-100 dark:border-slate-700 pb-2">
                            Ringkasan Opname
                        </h3>
                        
                        <div class="space-y-3 mb-6">
                            <div class="flex justify-between items-center">
                                <span class="text-sm text-slate-600 dark:text-slate-400">Total Item Diperiksa</span>
                                <span class="font-bold text-slate-800 dark:text-white" x-text="rows.length">0</span>
                            </div>

                            <div class="flex justify-between items-center">
                                <span class="text-sm text-slate-600 dark:text-slate-400">Item dengan Selisih</span>
                                <span class="font-bold text-amber-600" x-text="countDifferences()">0</span>
                            </div>
                            
                            <div class="p-3 bg-indigo-50 dark:bg-indigo-900/20 rounded-lg text-xs text-indigo-700 dark:text-indigo-300 leading-relaxed border border-indigo-100 dark:border-indigo-800">
                                <i class="material-icons text-[14px] align-text-bottom mr-1">info</i>
                                Setelah disimpan, stok produk akan <strong>langsung diperbarui</strong> mengikuti "Fisik (Aktual)" dan selisih nilai akan dicatat ke Jurnal Akuntansi.
                            </div>
                        </div>

                        <div class="flex items-center gap-3">
                            <a href="{{ route('admin.stock-opnames.index') }}" class="btn btn-secondary flex-1 justify-center">
                                Batal
                            </a>
                            <button type="submit" 
                                    class="btn btn-primary flex-1 justify-center py-2.5 shadow-lg shadow-indigo-500/30"
                                    :disabled="rows.length === 0">
                                <i class="material-icons text-[18px]">save</i>
                                Selesaikan Opname
                            </button>
                        </div>
                    </div>
                </div>

            </div>

        </div>
    </form>

    @push('scripts')
    <script>
        function stockOpnameForm(allProducts) {
            return {
                productsSource: allProducts, 
                rows: [],
                
                init() {
                    let selectEl = document.getElementById('product-selector');
                    
                    if (selectEl) {
                        let tom = new TomSelect(selectEl, {
                            create: false,
                            placeholder: "Ketik Nama / Kode Produk...",
                            valueField: 'id',
                            labelField: 'name',
                            searchField: ['name', 'code'],
                            options: this.productsSource,
                            // Dropdown parent body agar tidak terpotong (sesuai aturan Anda)
                            dropdownParent: 'body', 
                            render: {
                                option: function(data, escape) {
                                    return `<div class="flex items-center gap-2 py-1 px-2">
                                                <div class="flex-1">
                                                    <div class="font-bold text-sm text-slate-700 dark:text-slate-200">${escape(data.name)}</div>
                                                    <div class="text-xs text-slate-500">Kode: ${escape(data.code)} | Stok: ${escape(data.stock)} ${escape(data.unit)}</div>
                                                </div>
                                            </div>`;
                                },
                                item: function(data, escape) {
                                    return `<div>${escape(data.code)} - ${escape(data.name)}</div>`;
                                }
                            },
                            onChange: (value) => {
                                if (value) {
                                    this.addProduct(value);
                                    tom.clear(); 
                                }
                            }
                        });
                    }
                },

                addProduct(productId) {
                    if (this.rows.find(r => r.id == productId)) {
                        // Menggunakan helper toast bawaan app.js Anda
                        if(window.showToast) window.showToast('Produk ini sudah ada di daftar!', 'warning');
                        else alert('Produk sudah ada!');
                        return;
                    }

                    let product = this.productsSource.find(p => p.id == productId);
                    
                    if (product) {
                        this.rows.push({
                            id: product.id,
                            name: product.name,
                            code: product.code,
                            image: product.image,
                            stock: parseFloat(product.stock),
                            unit: product.unit,
                            physical: parseFloat(product.stock) // Default sama dengan sistem
                        });
                    }
                },

                removeRow(index) {
                    this.rows.splice(index, 1);
                },

                countDifferences() {
                    // Hitung berapa baris yang fisik != stock
                    // Gunakan toleransi float kecil
                    return this.rows.filter(r => Math.abs(r.physical - r.stock) > 0.0001).length;
                }
            }
        }
    </script>
    @endpush

@endsection
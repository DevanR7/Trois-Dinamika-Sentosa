@extends('admin.layouts.app')

@section('title', 'Riwayat Produk')

@section('content')
    {{-- Header --}}
    <div class="page-header print:hidden">
        <div>
            <h1 class="page-title">Riwayat Produk</h1>
            <p class="page-subtitle">Jejak lengkap transaksi dan mutasi barang per produk</p>
        </div>
        <div>
            <button onclick="window.print()" class="btn btn-secondary">
                <i class="material-icons text-[18px]">print</i>
                Cetak Laporan
            </button>
        </div>
    </div>

    {{-- Filter Section --}}
    <div class="card mb-6 print:hidden">
        <div class="card-body">
            <form action="{{ route('admin.reports.product-history') }}" method="GET" class="flex flex-col md:flex-row gap-4 items-end">
                
                {{-- Pilih Produk --}}
                <div class="flex-1 w-full">
                    <label class="form-label">Pilih Produk untuk Dianalisis</label>
                    <select name="product_id" class="tom-select" placeholder="Cari nama atau kode produk...">
                        <option value="">-- Pilih Produk --</option>
                        @foreach($products as $product)
                            <option value="{{ $product->product_id }}" 
                                {{ request('product_id') == $product->product_id ? 'selected' : '' }}>
                                {{ $product->product_name }} ({{ $product->product_code }})
                            </option>
                        @endforeach
                    </select>
                </div>

                {{-- Submit --}}
                <button type="submit" class="btn btn-primary w-full md:w-auto">
                    <i class="material-icons text-[18px]">search</i>
                    Tampilkan Data
                </button>
            </form>
        </div>
    </div>

    {{-- Content Area --}}
    @if($selectedProduct)
        
        {{-- CARD 1: INFORMASI DETAIL PRODUK --}}
        <div class="card mb-6">
            <div class="card-header border-l-4 border-l-indigo-500">
                <h3 class="card-header-title">Informasi Detail Produk</h3>
            </div>
            <div class="card-body">
                <div class="grid grid-cols-1 lg:grid-cols-4 gap-8">
                    
                    {{-- Kolom 1: Gambar & Identitas Utama --}}
                    <div class="flex flex-col items-center text-center lg:items-start lg:text-left lg:border-r lg:border-slate-100 lg:dark:border-slate-700 pr-0 lg:pr-6">
                        <div class="w-32 h-32 rounded-xl bg-slate-100 dark:bg-slate-700 flex items-center justify-center overflow-hidden mb-4 border border-slate-200 dark:border-slate-600">
                            @if($selectedProduct->image_path)
                                <img src="{{ asset('storage/' . $selectedProduct->image_path) }}" alt="Product Image" class="w-full h-full object-cover">
                            @else
                                <i class="material-icons text-6xl text-slate-300">image</i>
                            @endif
                        </div>
                        <h2 class="text-lg font-bold text-slate-800 dark:text-white leading-tight">
                            {{ $selectedProduct->product_name }}
                        </h2>
                        <span class="inline-block mt-2 px-2 py-1 bg-slate-100 dark:bg-slate-700 text-slate-600 dark:text-slate-300 rounded font-mono text-xs font-bold border border-slate-200 dark:border-slate-600">
                            {{ $selectedProduct->product_code }}
                        </span>
                        <div class="mt-3">
                            @if($selectedProduct->is_active)
                                <span class="badge badge-success">Produk Aktif</span>
                            @else
                                <span class="badge badge-danger">Non-Aktif</span>
                            @endif
                        </div>
                    </div>

                    {{-- Kolom 2: Klasifikasi & Supplier --}}
                    <div class="space-y-4">
                        <h4 class="text-xs font-bold text-slate-400 uppercase tracking-wider mb-2">Klasifikasi</h4>
                        
                        <div>
                            <p class="text-xs text-slate-500">Kategori</p>
                            <p class="font-medium text-slate-700 dark:text-slate-200">
                                {{ $selectedProduct->category->name ?? '-' }}
                            </p>
                        </div>
                        <div>
                            <p class="text-xs text-slate-500">Satuan Unit</p>
                            <p class="font-medium text-slate-700 dark:text-slate-200">
                                {{ $selectedProduct->unit->name ?? 'Pcs' }}
                            </p>
                        </div>
                        <div>
                            <p class="text-xs text-slate-500">Supplier Utama</p>
                            <a href="{{ route('admin.suppliers.show', $selectedProduct->supplier_id) }}" class="text-indigo-600 hover:underline font-medium text-sm">
                                {{ $selectedProduct->supplier->supplier_name ?? '-' }}
                            </a>
                        </div>
                    </div>

                    {{-- Kolom 3: Informasi Harga (Pricing) --}}
                    <div class="space-y-4">
                        <h4 class="text-xs font-bold text-slate-400 uppercase tracking-wider mb-2">Informasi Harga</h4>
                        
                        <div>
                            <p class="text-xs text-slate-500">Harga Beli Terakhir</p>
                            <p class="font-mono font-medium text-slate-700 dark:text-slate-200">
                                Rp {{ number_format($selectedProduct->purchase_price, 0, ',', '.') }}
                            </p>
                        </div>
                        <div>
                            <p class="text-xs text-slate-500">Harga Jual</p>
                            <p class="font-mono font-bold text-emerald-600">
                                Rp {{ number_format($selectedProduct->selling_price, 0, ',', '.') }}
                            </p>
                        </div>
                        <div class="pt-2 border-t border-slate-100 dark:border-slate-700">
                            <p class="text-xs text-slate-500" title="Harga Pokok Penjualan (Average)">HPP (Rata-rata)</p>
                            <p class="font-mono font-medium text-amber-600">
                                Rp {{ number_format($selectedProduct->average_cost, 2, ',', '.') }}
                            </p>
                        </div>
                    </div>

                    {{-- Kolom 4: Stok & Valuasi --}}
                    <div class="bg-slate-50 dark:bg-slate-800/50 rounded-xl p-4 border border-slate-200 dark:border-slate-700 flex flex-col justify-center">
                        <h4 class="text-xs font-bold text-slate-400 uppercase tracking-wider mb-4 text-center">Status Inventori</h4>
                        
                        <div class="text-center mb-4">
                            <p class="text-xs text-slate-500 mb-1">Stok Fisik Saat Ini</p>
                            <p class="text-3xl font-bold text-indigo-600 dark:text-indigo-400">
                                {{ number_format($selectedProduct->stock_quantity, 0, ',', '.') }}
                            </p>
                            <span class="text-xs text-slate-400">{{ $selectedProduct->unit->name ?? 'Unit' }}</span>
                        </div>

                        <div class="text-center pt-4 border-t border-slate-200 dark:border-slate-600">
                            <p class="text-xs text-slate-500 mb-1">Total Nilai Aset (Valuasi)</p>
                            <p class="text-sm font-mono font-bold text-slate-700 dark:text-white">
                                Rp {{ number_format($selectedProduct->stock_quantity * $selectedProduct->average_cost, 0, ',', '.') }}
                            </p>
                        </div>
                    </div>

                </div>
                
                {{-- Deskripsi Tambahan --}}
                @if($selectedProduct->description)
                    <div class="mt-6 pt-6 border-t border-slate-100 dark:border-slate-700">
                        <p class="text-xs text-slate-400 font-bold uppercase mb-1">Deskripsi Produk</p>
                        <p class="text-sm text-slate-600 dark:text-slate-400 italic">
                            "{{ $selectedProduct->description }}"
                        </p>
                    </div>
                @endif
            </div>
        </div>

        {{-- CARD 2: KRONOLOGI TRANSAKSI --}}
        <div class="card card-plain">
            <div class="card-header border-b border-slate-200 dark:border-slate-700">
                <h3 class="card-header-title flex items-center gap-2">
                    <i class="material-icons text-slate-400">history</i>
                    Kronologi Transaksi
                </h3>
            </div>
            <div class="table-container">
                <table class="table-modern">
                    <thead>
                        <tr>
                            <th class="w-12">#</th>
                            <th>Tanggal & Waktu</th>
                            <th>No. Referensi</th>
                            <th>Tipe Transaksi</th>
                            <th>Keterangan</th>
                            <th class="text-right text-emerald-600 w-24">Masuk</th>
                            <th class="text-right text-rose-600 w-24">Keluar</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($history as $row)
                            <tr class="hover:bg-slate-50 dark:hover:bg-slate-800/50 transition-colors">
                                <td>{{ $loop->iteration }}</td>
                                <td>
                                    <div class="font-bold text-slate-700 dark:text-slate-200">
                                        {{ \Carbon\Carbon::parse($row->date)->format('d M Y') }}
                                    </div>
                                    <div class="text-xs text-slate-400">
                                        {{ \Carbon\Carbon::parse($row->created_at)->format('H:i') }}
                                    </div>
                                </td>
                                <td>
                                    <span class="font-mono text-xs font-bold bg-white dark:bg-slate-700 border border-slate-200 dark:border-slate-600 px-2 py-1 rounded">
                                        {{ $row->reference }}
                                    </span>
                                </td>
                                <td>
                                    @php
                                        $badgeClass = 'bg-slate-100 text-slate-600';
                                        $icon = 'article';
                                        
                                        switch($row->source) {
                                            case 'sales_invoice':
                                                $badgeClass = 'bg-rose-50 text-rose-600 border border-rose-200 dark:bg-rose-900/20';
                                                $icon = 'shopping_cart';
                                                break;
                                            case 'purchase_order':
                                                $badgeClass = 'bg-emerald-50 text-emerald-600 border border-emerald-200 dark:bg-emerald-900/20';
                                                $icon = 'inventory';
                                                break;
                                            case 'sales_return':
                                                $badgeClass = 'bg-blue-50 text-blue-600 border border-blue-200 dark:bg-blue-900/20';
                                                $icon = 'assignment_return';
                                                break;
                                            case 'purchase_return':
                                                $badgeClass = 'bg-orange-50 text-orange-600 border border-orange-200 dark:bg-orange-900/20';
                                                $icon = 'local_shipping';
                                                break;
                                            case 'stock_opname':
                                                $badgeClass = 'bg-purple-50 text-purple-600 border border-purple-200 dark:bg-purple-900/20';
                                                $icon = 'fact_check';
                                                break;
                                        }
                                    @endphp
                                    <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-md text-xs font-bold {{ $badgeClass }}">
                                        <i class="material-icons text-[14px]">{{ $icon }}</i>
                                        {{ $row->type }}
                                    </span>
                                </td>
                                <td class="text-sm text-slate-600 dark:text-slate-400 max-w-xs truncate">
                                    {{ $row->description }}
                                </td>
                                <td class="text-right font-mono font-bold text-emerald-600 bg-emerald-50/50 dark:bg-emerald-900/10">
                                    @if($row->qty_in > 0)
                                        +{{ number_format($row->qty_in, 0, ',', '.') }}
                                    @else
                                        <span class="text-slate-300">-</span>
                                    @endif
                                </td>
                                <td class="text-right font-mono font-bold text-rose-600 bg-rose-50/50 dark:bg-rose-900/10">
                                    @if($row->qty_out > 0)
                                        -{{ number_format($row->qty_out, 0, ',', '.') }}
                                    @else
                                        <span class="text-slate-300">-</span>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="text-center py-12 text-slate-400">
                                    <i class="material-icons text-4xl mb-2">history_toggle_off</i>
                                    <p>Tidak ada riwayat transaksi untuk produk ini.</p>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

    @elseif(request('product_id'))
        {{-- State jika ID produk tidak valid --}}
        <div class="card p-12 text-center text-slate-400">
            <i class="material-icons text-6xl mb-4">search_off</i>
            <p>Produk tidak ditemukan.</p>
        </div>
    @else
        {{-- Empty State Awal --}}
        <div class="card p-12 text-center">
            <div class="w-20 h-20 bg-indigo-50 dark:bg-slate-800 rounded-full flex items-center justify-center mx-auto mb-4 text-indigo-500">
                <i class="material-icons text-4xl">inventory_2</i>
            </div>
            <h3 class="text-lg font-bold text-slate-700 dark:text-white mb-1">Pilih Produk</h3>
            <p class="text-slate-500 text-sm">Silakan pilih produk di atas untuk melihat detail lengkap dan kronologi transaksi.</p>
        </div>
    @endif
@endsection
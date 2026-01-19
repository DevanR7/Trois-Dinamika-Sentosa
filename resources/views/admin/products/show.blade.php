@extends('admin.layouts.app')

@section('title', 'Detail Produk')

@section('content')

    {{-- 1. PAGE HEADER --}}
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 mb-6">
        <div>
            <div class="flex items-center gap-2">
                <h1 class="text-2xl font-bold text-slate-800 dark:text-white tracking-tight">Detail Produk</h1>
                @if($product->is_active)
                    <span class="px-2 py-0.5 rounded text-[10px] font-bold uppercase bg-emerald-100 text-emerald-600 dark:bg-emerald-900/30 dark:text-emerald-400 border border-emerald-200 dark:border-emerald-800">
                        Aktif
                    </span>
                @else
                    <span class="px-2 py-0.5 rounded text-[10px] font-bold uppercase bg-slate-200 text-slate-500 dark:bg-slate-700 dark:text-slate-400 border border-slate-300 dark:border-slate-600">
                        Non-Aktif
                    </span>
                @endif
            </div>
            <p class="text-sm text-slate-500 dark:text-slate-400 mt-1">
                Manajemen data produk, analisis profitabilitas, dan riwayat stok.
            </p>
        </div>

        <div class="flex flex-wrap gap-2">
            <a href="{{ route('admin.products.index') }}" class="btn btn-secondary">
                <i class="material-icons text-[18px]">arrow_back</i>
                Kembali
            </a>

            @can('create-purchase-orders')
                {{-- Button Restock --}}
                <a href="{{ route('admin.purchase-orders.create', ['product_id' => $product->product_id]) }}" class="btn bg-indigo-600 text-white hover:bg-indigo-700 border-transparent shadow-indigo-500/20 shadow-md">
                    <i class="material-icons text-[18px]">add_shopping_cart</i>
                    Restock (Beli)
                </a>
            @endcan

            {{-- Button Control Dropdown (Edit/Status/Arsip) --}}
            <div class="relative" x-data="{ open: false }">
                <button @click="open = !open" @click.outside="open = false" class="btn btn-primary">
                    <i class="material-icons text-[18px]">settings</i>
                    Kontrol
                    <i class="material-icons text-[18px] ml-1">expand_more</i>
                </button>
                <div x-show="open" 
                     x-transition:enter="transition ease-out duration-100"
                     x-transition:enter-start="transform opacity-0 scale-95"
                     x-transition:enter-end="transform opacity-100 scale-100"
                     x-transition:leave="transition ease-in duration-75"
                     x-transition:leave-start="transform opacity-100 scale-100"
                     x-transition:leave-end="transform opacity-0 scale-95"
                     class="absolute right-0 mt-2 w-48 bg-white dark:bg-slate-800 rounded-xl shadow-xl border border-slate-200 dark:border-slate-700 z-50 overflow-hidden"
                     style="display: none;">
                    
                    {{-- Edit --}}
                    @can('edit-products')
                        <a href="{{ route('admin.products.edit', $product->product_id) }}" class="block px-4 py-2.5 text-sm text-slate-700 dark:text-slate-300 hover:bg-slate-50 dark:hover:bg-slate-700 flex items-center gap-2">
                            <i class="material-icons text-[16px] text-amber-500">edit</i> Edit Data
                        </a>
                    @endcan

                    {{-- Toggle Status --}}
                    @can('edit-products')
                        <form action="{{ route('admin.products.toggle-status', $product->product_id) }}" method="POST">
                            @csrf @method('PATCH')
                            <button type="submit" class="w-full text-left px-4 py-2.5 text-sm text-slate-700 dark:text-slate-300 hover:bg-slate-50 dark:hover:bg-slate-700 flex items-center gap-2">
                                <i class="material-icons text-[16px] {{ $product->is_active ? 'text-slate-400' : 'text-emerald-500' }}">
                                    {{ $product->is_active ? 'toggle_off' : 'toggle_on' }}
                                </i>
                                {{ $product->is_active ? 'Non-aktifkan' : 'Aktifkan' }}
                            </button>
                        </form>
                    @endcan

                    {{-- Hapus/Arsip --}}
                    @can('delete-products')
                        <div class="border-t border-slate-100 dark:border-slate-700 my-1"></div>
                        <form action="{{ route('admin.products.destroy', $product->product_id) }}" method="POST">
                            @csrf @method('DELETE')
                            <button type="submit" 
                                    onclick="return confirmAction(event, 'Arsipkan Produk?', 'Produk akan dipindahkan ke sampah.', 'warning')"
                                    class="w-full text-left px-4 py-2.5 text-sm text-rose-600 hover:bg-rose-50 dark:hover:bg-rose-900/20 flex items-center gap-2">
                                <i class="material-icons text-[16px]">delete</i> Arsipkan
                            </button>
                        </form>
                    @endcan
                </div>
            </div>
        </div>
    </div>

    <div class="grid grid-cols-1 xl:grid-cols-3 gap-6">

        {{-- KOLOM KIRI: PROFILE PRODUK (Style Anda) --}}
        <div class="xl:col-span-1 space-y-6">
            
            {{-- Card Foto & Identitas --}}
            <div class="card overflow-hidden">
                <div class="p-6 flex flex-col items-center">
                    
                    {{-- Foto Produk (Clickable Modal) --}}
                    <div class="relative w-48 h-48 mb-6 group cursor-pointer" x-data="{ showModal: false }">
                        @if($product->image_path)
                            <img src="{{ asset('storage/' . $product->image_path) }}" 
                                 class="w-full h-full object-cover rounded-2xl shadow-lg border border-slate-200 dark:border-slate-700 group-hover:opacity-90 transition-opacity"
                                 alt="{{ $product->product_name }}"
                                 @click="showModal = true">
                            
                            {{-- Zoom Icon --}}
                            <div class="absolute inset-0 flex items-center justify-center opacity-0 group-hover:opacity-100 transition-opacity" @click="showModal = true">
                                <div class="bg-black/50 rounded-full p-2 text-white shadow-lg backdrop-blur-sm">
                                    <i class="material-icons text-2xl">zoom_in</i>
                                </div>
                            </div>

                            {{-- Modal Preview --}}
                            <template x-teleport="body">
                                <div x-show="showModal" class="fixed inset-0 z-[9999] flex items-center justify-center bg-black/90 backdrop-blur-sm p-4" @click.self="showModal = false" style="display: none;"
                                     x-transition:enter="transition ease-out duration-300" x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100"
                                     x-transition:leave="transition ease-in duration-200" x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0">
                                    <img src="{{ asset('storage/' . $product->image_path) }}" class="max-w-full max-h-[90vh] rounded shadow-2xl">
                                    <button @click="showModal = false" class="absolute top-4 right-4 text-white hover:text-gray-300 bg-black/50 rounded-full p-1"><i class="material-icons text-3xl">close</i></button>
                                </div>
                            </template>
                        @else
                            <div class="w-full h-full bg-slate-100 dark:bg-slate-700 rounded-2xl flex flex-col items-center justify-center text-slate-400 border-2 border-dashed border-slate-300 dark:border-slate-600">
                                <i class="material-icons text-6xl opacity-50">inventory_2</i>
                                <span class="text-xs font-bold mt-2 uppercase tracking-wide">No Image</span>
                            </div>
                        @endif
                    </div>

                    {{-- Nama & Kode --}}
                    <h2 class="text-lg font-bold text-slate-800 dark:text-white text-center leading-tight mb-2">
                        {{ $product->product_name }}
                    </h2>
                    <div class="inline-flex items-center gap-2 px-3 py-1 rounded-full bg-slate-100 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 mb-6">
                        <i class="material-icons text-[14px] text-slate-400">qr_code</i>
                        <span class="text-xs font-mono font-bold text-slate-600 dark:text-slate-300">{{ $product->product_code }}</span>
                    </div>

                    {{-- Indikator Stok Besar --}}
                    @php
                        $stockColor = 'text-emerald-600 bg-emerald-50 border-emerald-100 dark:bg-emerald-900/20 dark:border-emerald-800 dark:text-emerald-400';
                        $stockLabel = 'Stok Aman';
                        $icon = 'check_circle';

                        if($product->stock_quantity <= 0) {
                            $stockColor = 'text-slate-500 bg-slate-100 border-slate-200 dark:bg-slate-800 dark:text-slate-400';
                            $stockLabel = 'Habis';
                            $icon = 'remove_circle_outline';
                        } elseif($product->stock_quantity <= 5) {
                            $stockColor = 'text-rose-600 bg-rose-50 border-rose-100 dark:bg-rose-900/20 dark:border-rose-800 dark:text-rose-400 animate-pulse';
                            $stockLabel = 'Kritis';
                            $icon = 'warning';
                        } elseif($product->stock_quantity <= 10) {
                            $stockColor = 'text-amber-600 bg-amber-50 border-amber-100 dark:bg-amber-900/20 dark:border-amber-800 dark:text-amber-400';
                            $stockLabel = 'Menipis';
                            $icon = 'priority_high';
                        }
                    @endphp

                    <div class="w-full p-4 rounded-xl border {{ $stockColor }} flex items-center justify-between mb-4 shadow-sm">
                        <div class="flex flex-col">
                            <span class="text-[10px] uppercase font-bold tracking-wider opacity-80">Stok Fisik</span>
                            <span class="text-3xl font-extrabold">{{ number_format($product->stock_quantity, 0, ',', '.') }}</span>
                        </div>
                        <div class="text-right flex flex-col items-end">
                            <i class="material-icons text-3xl mb-1">{{ $icon }}</i>
                            <p class="text-xs font-bold uppercase">{{ $stockLabel }}</p>
                        </div>
                    </div>
                    
                    <div class="w-full text-center border-t border-slate-100 dark:border-slate-700 pt-4">
                        <span class="text-xs text-slate-400">Satuan: <strong>{{ $product->unit->name ?? 'pcs' }}</strong></span>
                    </div>
                </div>
            </div>

            {{-- Card Info Supplier --}}
            <div class="card p-5">
                <h3 class="text-xs font-bold text-slate-400 uppercase tracking-wider mb-4 border-b border-slate-100 dark:border-slate-700 pb-2">
                    Informasi Tambahan
                </h3>
                <div class="space-y-4">
                    {{-- Supplier --}}
                    <div class="flex items-start gap-3">
                        <div class="w-8 h-8 rounded bg-indigo-50 dark:bg-indigo-900/30 flex items-center justify-center text-indigo-600 shrink-0">
                            <i class="material-icons text-[18px]">store</i>
                        </div>
                        <div>
                            <p class="text-xs text-slate-400">Supplier Utama</p>
                            <a href="{{ route('admin.suppliers.show', $product->supplier_id) }}" class="text-sm font-bold text-slate-700 dark:text-slate-200 hover:text-indigo-600 hover:underline">
                                {{ $product->supplier->supplier_name ?? 'Tanpa Supplier' }}
                            </a>
                        </div>
                    </div>
                    {{-- Kategori --}}
                    <div class="flex items-start gap-3">
                        <div class="w-8 h-8 rounded bg-orange-50 dark:bg-orange-900/30 flex items-center justify-center text-orange-600 shrink-0">
                            <i class="material-icons text-[18px]">category</i>
                        </div>
                        <div>
                            <p class="text-xs text-slate-400">Kategori</p>
                            <p class="text-sm font-bold text-slate-700 dark:text-slate-200">
                                {{ $product->category->name ?? 'Tanpa Kategori' }}
                            </p>
                        </div>
                    </div>
                </div>
            </div>

        </div>

        {{-- KOLOM KANAN: ANALISIS & TRANSAKSI (Lebar 2 Kolom) --}}
        <div class="xl:col-span-2 space-y-6">

            {{-- 1. ANALISIS HARGA & PROFIT (Desain Baru) --}}
            <div class="card p-0 overflow-hidden">
                <div class="card-header bg-slate-50 dark:bg-slate-800/50 border-b border-slate-200 dark:border-slate-700">
                    <h3 class="card-header-title flex items-center gap-2">
                        <i class="material-icons text-emerald-500">analytics</i>
                        Analisis Harga & Profitabilitas
                    </h3>
                </div>
                <div class="p-6">
                    <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                        {{-- Harga Beli --}}
                        <div class="p-4 bg-slate-50 dark:bg-slate-700/30 rounded-xl border border-slate-200 dark:border-slate-700">
                            <p class="text-[10px] text-slate-400 uppercase tracking-wider mb-1 font-bold">Harga Beli (HPP)</p>
                            <p class="text-lg font-bold text-slate-700 dark:text-slate-300">
                                Rp {{ number_format($product->average_cost, 0, ',', '.') }}
                            </p>
                        </div>

                        {{-- Harga Jual --}}
                        <div class="p-4 bg-indigo-50 dark:bg-indigo-900/20 rounded-xl border border-indigo-100 dark:border-indigo-800">
                            <p class="text-[10px] text-indigo-400 uppercase tracking-wider mb-1 font-bold">Harga Jual</p>
                            <p class="text-lg font-bold text-indigo-700 dark:text-indigo-300">
                                Rp {{ number_format($product->selling_price, 0, ',', '.') }}
                            </p>
                        </div>

                        {{-- Margin --}}
                        <div class="p-4 bg-emerald-50 dark:bg-emerald-900/20 rounded-xl border border-emerald-100 dark:border-emerald-800">
                            <div class="flex justify-between items-start mb-1">
                                <p class="text-[10px] text-emerald-600 uppercase tracking-wider font-bold">Margin / Unit</p>
                                <span class="text-[10px] font-bold px-1.5 py-0.5 rounded-full bg-white/50 text-emerald-700 border border-emerald-200">
                                    {{ number_format($marginPercentage, 1) }}%
                                </span>
                            </div>
                            <p class="text-lg font-bold text-emerald-700 dark:text-emerald-400">
                                +Rp {{ number_format($marginPerUnit, 0, ',', '.') }}
                            </p>
                        </div>

                        {{-- Valuasi Stok --}}
                        <div class="p-4 bg-white dark:bg-slate-800 rounded-xl border border-slate-200 dark:border-slate-600 shadow-sm">
                            <p class="text-[10px] text-slate-400 uppercase tracking-wider mb-1 font-bold">Total Aset Stok</p>
                            <p class="text-lg font-bold text-slate-800 dark:text-white truncate" title="Nilai HPP x Stok">
                                Rp {{ number_format($product->stock_quantity * $product->average_cost, 0, ',', '.') }}
                            </p>
                        </div>
                    </div>
                </div>
            </div>

            {{-- 2. DESKRIPSI (Jika Ada) --}}
            @if($product->description)
                <div class="card p-5">
                    <h3 class="text-xs font-bold text-slate-400 uppercase tracking-wider mb-2">Deskripsi Produk</h3>
                    <div class="text-sm text-slate-600 dark:text-slate-300 leading-relaxed whitespace-pre-line bg-slate-50 dark:bg-slate-800 p-4 rounded-lg border border-slate-100 dark:border-slate-700">
                        {{ $product->description }}
                    </div>
                </div>
            @endif

            {{-- 3. RIWAYAT STOK OPNAME --}}
            <div class="card">
                <div class="card-header bg-slate-50 dark:bg-slate-800/50 flex justify-between items-center py-3">
                    <h3 class="card-header-title flex items-center gap-2">
                        <i class="material-icons text-slate-400 text-[18px]">inventory</i> Riwayat Stock Opname
                    </h3>
                    @can('manage-stock-opnames')
                        <a href="{{ route('admin.stock-opnames.create') }}" class="btn btn-sm btn-white border border-slate-200 hover:bg-slate-50 text-indigo-600 shadow-sm">
                            <i class="material-icons text-[14px] mr-1">add</i> Opname Baru
                        </a>
                    @endcan
                </div>
                <div class="table-container border-0 rounded-t-none">
                    <table class="table-modern">
                        <thead>
                            <tr>
                                <th>Tanggal</th>
                                <th>No. Opname</th>
                                <th class="text-center">Sistem</th>
                                <th class="text-center">Fisik</th>
                                <th class="text-center">Selisih</th>
                                <th>Oleh</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($product->stockOpnameItems->sortByDesc('created_at')->take(5) as $opnameItem)
                                <tr>
                                    <td class="text-xs">{{ $opnameItem->created_at->format('d M Y') }}</td>
                                    <td>
                                        <a href="{{ route('admin.stock-opnames.show', $opnameItem->opname_id) }}" class="text-indigo-600 hover:underline font-mono text-xs font-bold">
                                            {{ $opnameItem->opname->opname_number ?? '-' }}
                                        </a>
                                    </td>
                                    <td class="text-center font-mono text-xs text-slate-500">{{ (float)$opnameItem->system_qty }}</td>
                                    <td class="text-center font-mono text-xs font-bold text-slate-700 dark:text-white">{{ (float)$opnameItem->physical_qty }}</td>
                                    <td class="text-center">
                                        @if($opnameItem->difference > 0)
                                            <span class="badge bg-emerald-100 text-emerald-700 text-[10px] border-emerald-200">+{{ (float)$opnameItem->difference }}</span>
                                        @elseif($opnameItem->difference < 0)
                                            <span class="badge bg-rose-100 text-rose-700 text-[10px] border-rose-200">{{ (float)$opnameItem->difference }}</span>
                                        @else
                                            <span class="badge bg-slate-100 text-slate-500 text-[10px] border-slate-200">Sesuai</span>
                                        @endif
                                    </td>
                                    <td class="text-xs text-slate-500">{{ $opnameItem->opname->user->full_name ?? 'System' }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="6" class="text-center py-6 text-slate-400 text-xs italic">
                                        Belum ada riwayat stock opname.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

            {{-- 4. RIWAYAT PENJUALAN TERAKHIR --}}
            <div class="card">
                <div class="card-header bg-slate-50 dark:bg-slate-800/50 flex justify-between items-center py-3">
                    <h3 class="card-header-title flex items-center gap-2">
                        <i class="material-icons text-slate-400 text-[18px]">receipt_long</i> Penjualan Terakhir
                    </h3>
                    <a href="{{ route('admin.reports.product-history', ['product_id' => $product->product_id]) }}" class="text-xs font-bold text-indigo-600 hover:text-indigo-800 flex items-center">
                        Lihat Semua <i class="material-icons text-[14px] ml-1">arrow_forward</i>
                    </a>
                </div>
                <div class="table-container border-0 rounded-t-none">
                    <table class="table-modern">
                        <thead>
                            <tr>
                                <th>Tanggal</th>
                                <th>No. Invoice</th>
                                <th>Klien</th>
                                <th class="text-center">Qty</th>
                                <th class="text-right">Total (Rp)</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($product->invoiceItems()->with('salesInvoice.client')->latest('item_id')->take(10)->get() as $invItem)
                                @if($invItem->salesInvoice)
                                    <tr>
                                        <td class="text-xs text-slate-600">{{ $invItem->salesInvoice->order_date->format('d M Y') }}</td>
                                        <td>
                                            <a href="{{ route('admin.invoices.show', $invItem->invoice_id) }}" class="text-indigo-600 hover:underline font-mono text-xs font-bold">
                                                {{ $invItem->salesInvoice->invoice_number }}
                                            </a>
                                        </td>
                                        <td class="text-xs truncate max-w-[150px]" title="{{ $invItem->salesInvoice->client->client_name ?? '-' }}">
                                            {{ $invItem->salesInvoice->client->client_name ?? '-' }}
                                        </td>
                                        <td class="text-center font-bold text-xs">{{ (float)$invItem->quantity }}</td>
                                        <td class="text-right font-mono text-xs text-emerald-600">
                                            {{ number_format($invItem->subtotal, 0, ',', '.') }}
                                        </td>
                                    </tr>
                                @endif
                            @empty
                                <tr>
                                    <td colspan="5" class="text-center py-6 text-slate-400 text-xs italic">
                                        Belum ada transaksi penjualan.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

        </div>

    </div>

    {{-- Script Confirm Action --}}
    @push('scripts')
    <script>
        function confirmAction(e, title, text, type) {
            e.preventDefault(); 
            const form = e.target.closest('form');
            if (typeof window.confirmDialog === 'function') {
                window.confirmDialog({
                    title: title, text: text, icon: type === 'danger' ? 'error' : type,
                    confirmButtonText: 'Ya, Lanjutkan', confirmButtonColor: type
                }).then((result) => { if (result.isConfirmed) form.submit(); });
            } else {
                if (confirm(title + "\n" + text)) form.submit();
            }
            return false;
        }
    </script>
    @endpush

@endsection
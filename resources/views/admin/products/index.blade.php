@extends('admin.layouts.app')

@section('title', request('status') == 'trash' ? 'Arsip Produk' : 'Daftar Produk')

@section('content')
    
    {{-- 1. PAGE HEADER --}}
    <div class="page-header">
        <div>
            <h1 class="page-title">
                {{ request('status') == 'trash' ? 'Arsip Produk (Sampah)' : 'Manajemen Produk' }}
            </h1>
            <p class="page-subtitle">
                {{ request('status') == 'trash' 
                    ? 'Kelola data produk yang telah dihapus sementara.' 
                    : 'Inventaris lengkap, stok, harga, dan manajemen katalog.' }}
            </p>
        </div>

        <div class="flex flex-wrap gap-2">
            @if(request('status') == 'trash')
                <a href="{{ route('admin.products.index') }}" class="btn btn-secondary">
                    <i class="material-icons text-[18px]">arrow_back</i>
                    Kembali ke Daftar
                </a>
            @else
                {{-- Shortcut ke Laporan --}}
                <div class="hidden sm:flex gap-2 mr-2">
                    <a href="{{ route('admin.reports.product-history') }}" class="btn btn-secondary" title="Riwayat Transaksi">
                        <i class="material-icons text-[18px] text-slate-500">history</i>
                    </a>
                    <a href="{{ route('admin.reports.stock-card') }}" class="btn btn-secondary" title="Kartu Stok">
                        <i class="material-icons text-[18px] text-slate-500">topic</i>
                    </a>
                </div>

                {{-- Link ke Sampah --}}
                @can('delete-products')
                    <a href="{{ route('admin.products.index', ['status' => 'trash']) }}" 
                       class="btn btn-secondary text-rose-600 border-rose-200 hover:bg-rose-50 dark:border-rose-900/50 dark:hover:bg-rose-900/20"
                       title="Lihat Sampah">
                        <i class="material-icons text-[18px]">delete_outline</i>
                        <span class="hidden sm:inline">Sampah ({{ \App\Models\Product::onlyTrashed()->count() }})</span>
                    </a>
                @endcan

                {{-- Tambah Produk --}}
                @can('create-products')
                    <a href="{{ route('admin.products.create') }}" class="btn btn-primary">
                        <i class="material-icons text-[18px]">add</i>
                        Tambah Produk
                    </a>
                @endcan
            @endif
        </div>
    </div>

    {{-- 2. FILTERS & SEARCH --}}
    <div class="card mb-6">
        <div class="card-body">
            <form action="{{ route('admin.products.index') }}" method="GET" class="grid grid-cols-1 md:grid-cols-12 gap-4 items-center">
                
                @if(request('status'))
                    <input type="hidden" name="status" value="{{ request('status') }}">
                @endif

                {{-- Search Input (Lebar) --}}
                <div class="md:col-span-8 relative">
                    <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                        <i class="material-icons text-slate-400 text-[20px]">search</i>
                    </div>
                    <input type="text" name="search" value="{{ request('search') }}" 
                           class="form-input pl-10" 
                           placeholder="Cari Kode Produk, Nama, atau SKU...">
                </div>

                {{-- Sort Dropdown --}}
                <div class="md:col-span-3">
                    <div class="relative">
                        <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                            <i class="material-icons text-slate-400 text-[18px]">sort</i>
                        </div>
                        <select name="sort" class="form-select pl-10 cursor-pointer" onchange="this.form.submit()">
                            <option value="terbaru" {{ request('sort') == 'terbaru' ? 'selected' : '' }}>Terbaru</option>
                            <option value="A-Z" {{ request('sort') == 'A-Z' ? 'selected' : '' }}>Nama (A-Z)</option>
                            <option value="Z-A" {{ request('sort') == 'Z-A' ? 'selected' : '' }}>Nama (Z-A)</option>
                            <option value="stok-terbanyak" {{ request('sort') == 'stok-terbanyak' ? 'selected' : '' }}>Stok Tertinggi</option>
                            <option value="stok-sedikit" {{ request('sort') == 'stok-sedikit' ? 'selected' : '' }}>Stok Terendah</option>
                        </select>
                    </div>
                </div>

                {{-- Reset Button --}}
                <div class="md:col-span-1 text-right">
                    @if(request()->anyFilled(['search', 'sort']))
                        <a href="{{ route('admin.products.index', ['status' => request('status')]) }}" 
                           class="btn btn-secondary w-full justify-center" 
                           title="Reset Filter">
                            <i class="material-icons text-[18px]">refresh</i>
                        </a>
                    @endif
                </div>
            </form>
        </div>
    </div>

    {{-- 3. TABLE DATA --}}
    <div class="card card-plain">
        <div class="table-container">
            <table class="table-modern">
                <thead>
                    <tr>
                        <th class="w-14 text-center">No</th>
                        <th class="w-16 text-center">Img</th>
                        <th>Info Produk & Status</th> {{-- Header Updated --}}
                        <th class="hidden lg:table-cell">Kategori & Supplier</th>
                        <th class="text-right">Harga & Margin</th>
                        <th class="text-center">Stok</th>
                        <th class="text-right w-40 sticky right-0 z-10 bg-slate-50 dark:bg-slate-800/90 backdrop-blur-sm">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($products as $index => $product)
                        <tr class="group hover:bg-slate-50 dark:hover:bg-slate-800/50 transition-colors">
                            
                            {{-- No --}}
                            <td class="text-center text-slate-500 font-mono text-xs">
                                {{ $products->firstItem() + $index }}
                            </td>

                            {{-- Image --}}
                            <td class="text-center">
                                <div class="w-10 h-10 rounded-lg bg-slate-100 dark:bg-slate-700 border border-slate-200 dark:border-slate-600 flex items-center justify-center overflow-hidden mx-auto relative group-hover:shadow-sm transition-all">
                                    @if($product->image_path)
                                        <img src="{{ asset('storage/' . $product->image_path) }}" 
                                             alt="Img" 
                                             class="w-full h-full object-cover">
                                    @else
                                        <i class="material-icons text-slate-300 text-[18px]">image</i>
                                    @endif
                                </div>
                            </td>

                            {{-- Product Info & Status Indicator --}}
                            <td>
                                <div class="flex flex-col">
                                    <span class="font-bold text-slate-800 dark:text-white text-sm line-clamp-1 group-hover:text-indigo-600 transition-colors" title="{{ $product->product_name }}">
                                        {{ $product->product_name }}
                                    </span>
                                    <div class="flex items-center gap-2 mt-1">
                                        <span class="font-mono text-[10px] text-slate-500 bg-slate-100 dark:bg-slate-700 px-1.5 py-0.5 rounded border border-slate-200 dark:border-slate-600">
                                            {{ $product->product_code }}
                                        </span>
                                        
                                        {{-- STATUS INDICATOR --}}
                                        @if(request('status') !== 'trash')
                                            @if($product->is_active)
                                                <span class="text-[10px] font-bold text-emerald-600 bg-emerald-50 border border-emerald-100 px-1.5 py-0.5 rounded flex items-center gap-1">
                                                    <span class="w-1.5 h-1.5 rounded-full bg-emerald-500"></span> Aktif
                                                </span>
                                            @else
                                                <span class="text-[10px] font-bold text-slate-500 bg-slate-100 border border-slate-200 px-1.5 py-0.5 rounded flex items-center gap-1">
                                                    <span class="w-1.5 h-1.5 rounded-full bg-slate-400"></span> Non-Aktif
                                                </span>
                                            @endif
                                        @else
                                            <span class="text-[10px] text-rose-500 font-bold uppercase bg-rose-50 px-1.5 py-0.5 rounded">Terhapus</span>
                                        @endif
                                    </div>
                                </div>
                            </td>

                            {{-- Category & Supplier --}}
                            <td class="hidden lg:table-cell">
                                <div class="flex flex-col gap-1">
                                    <div class="flex items-center gap-1.5">
                                        <i class="material-icons text-[14px] text-slate-400">category</i>
                                        <span class="text-xs text-slate-600 dark:text-slate-300">{{ $product->category->name ?? '-' }}</span>
                                    </div>
                                    <div class="flex items-center gap-1.5">
                                        <i class="material-icons text-[14px] text-slate-400">store</i>
                                        <span class="text-[11px] text-slate-500 truncate max-w-[150px]" title="{{ $product->supplier->supplier_name ?? '-' }}">
                                            {{ $product->supplier->supplier_name ?? '-' }}
                                        </span>
                                    </div>
                                </div>
                            </td>

                            {{-- Pricing & Margin --}}
                            <td class="text-right">
                                <div class="flex flex-col items-end">
                                    <span class="font-bold text-slate-700 dark:text-emerald-400 text-sm">
                                        Rp {{ number_format($product->selling_price, 0, ',', '.') }}
                                    </span>
                                    <div class="flex items-center gap-1 text-[10px] text-slate-400 mt-0.5">
                                        <span>Beli: {{ number_format($product->average_cost > 0 ? $product->average_cost : $product->purchase_price, 0, ',', '.') }}</span>
                                    </div>
                                    
                                    @php
                                        $cost = $product->average_cost > 0 ? $product->average_cost : $product->purchase_price;
                                        $margin = $product->selling_price - $cost;
                                        $marginPercent = ($product->selling_price > 0 && $cost > 0) ? ($margin / $product->selling_price * 100) : 0;
                                    @endphp
                                    @if($margin > 0)
                                        <span class="text-[9px] font-bold text-emerald-600 bg-emerald-50 dark:bg-emerald-900/20 px-1.5 rounded-full mt-0.5">
                                            +{{ number_format($marginPercent, 0) }}%
                                        </span>
                                    @endif
                                </div>
                            </td>

                            {{-- Stock --}}
                            <td class="text-center align-middle">
                                @php
                                    $stock = $product->stock_quantity;
                                    $unit = $product->unit->name ?? '';
                                    $badgeColor = 'bg-emerald-100 text-emerald-700 border-emerald-200 dark:bg-emerald-900/30 dark:text-emerald-400 dark:border-emerald-800';
                                    if ($stock <= 0) $badgeColor = 'bg-rose-100 text-rose-700 border-rose-200 dark:bg-rose-900/30 dark:text-rose-400 dark:border-rose-800';
                                    elseif ($stock <= 10) $badgeColor = 'bg-amber-100 text-amber-700 border-amber-200 dark:bg-amber-900/30 dark:text-amber-400 dark:border-amber-800';
                                @endphp
                                <div class="inline-flex flex-col items-center">
                                    <span class="badge {{ $badgeColor }} border px-2 py-0.5 rounded text-xs font-mono font-bold">
                                        {{ number_format($stock, 0, ',', '.') }}
                                    </span>
                                    <span class="text-[10px] text-slate-400 lowercase mt-0.5">{{ $unit }}</span>
                                </div>
                            </td>

                            {{-- Actions --}}
                            <td class="text-right sticky right-0 z-10 bg-white dark:bg-slate-800 border-l border-slate-100 dark:border-slate-700/50 group-hover:bg-slate-50 dark:group-hover:bg-slate-700/30 transition-colors px-4">
                                <div class="flex items-center justify-end gap-1">
                                    
                                    @if(request('status') == 'trash')
                                        {{-- Trash Actions --}}
                                        @can('delete-products')
                                            <form action="{{ route('admin.products.restore', $product->product_id) }}" method="POST">
                                                @csrf @method('PATCH')
                                                <button type="submit" class="btn-action btn-action-restore" title="Pulihkan Produk" onclick="return confirmAction(event, 'Pulihkan Produk?', 'Produk akan kembali aktif.', 'success')">
                                                    <i class="material-icons">restore_from_trash</i>
                                                </button>
                                            </form>
                                            <form action="{{ route('admin.products.forceDelete', $product->product_id) }}" method="POST">
                                                @csrf @method('DELETE')
                                                <button type="submit" class="btn-action btn-action-delete" title="Hapus Permanen" onclick="return confirmAction(event, 'Hapus Permanen?', 'Data akan hilang selamanya!', 'danger')">
                                                    <i class="material-icons">delete_forever</i>
                                                </button>
                                            </form>
                                        @endcan
                                    @else
                                        {{-- Active Actions --}}
                                        
                                        {{-- 1. BUTTON TOGGLE STATUS (BARU) --}}
                                        @can('edit-products')
                                            <form action="{{ route('admin.products.toggle-status', $product->product_id) }}" method="POST" class="inline-block m-0">
                                                @csrf @method('PATCH')
                                                <button type="submit" 
                                                        class="btn-action {{ $product->is_active ? 'text-emerald-600 bg-white border-slate-200 hover:bg-emerald-50 hover:text-emerald-700' : 'text-slate-400 bg-slate-100 border-slate-200 hover:bg-emerald-50 hover:text-emerald-600' }}"
                                                        title="{{ $product->is_active ? 'Non-aktifkan Produk' : 'Aktifkan Produk' }}">
                                                    <i class="material-icons text-[18px]">{{ $product->is_active ? 'toggle_on' : 'toggle_off' }}</i>
                                                </button>
                                            </form>
                                        @endcan

                                        {{-- 2. View --}}
                                        <a href="{{ route('admin.products.show', $product->product_id) }}" class="btn-action btn-action-view" title="Detail & History">
                                            <i class="material-icons">visibility</i>
                                        </a>

                                        {{-- 3. Edit --}}
                                        @can('edit-products')
                                            <a href="{{ route('admin.products.edit', $product->product_id) }}" class="btn-action btn-action-edit" title="Edit Produk">
                                                <i class="material-icons">edit</i>
                                            </a>
                                        @endcan

                                        {{-- 4. Delete --}}
                                        @can('delete-products')
                                            <form action="{{ route('admin.products.destroy', $product->product_id) }}" method="POST">
                                                @csrf @method('DELETE')
                                                <button type="submit" class="btn-action btn-action-delete" title="Arsipkan" onclick="return confirmAction(event, 'Arsipkan Produk?', 'Produk akan dipindahkan ke sampah sementara.', 'warning')">
                                                    <i class="material-icons">delete</i>
                                                </button>
                                            </form>
                                        @endcan
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="text-center py-16">
                                <div class="flex flex-col items-center justify-center opacity-50">
                                    <div class="w-16 h-16 bg-slate-100 dark:bg-slate-700 rounded-full flex items-center justify-center mb-3">
                                        <i class="material-icons text-3xl text-slate-400">inventory_2</i>
                                    </div>
                                    <h3 class="text-slate-800 dark:text-white font-bold text-lg">Tidak ada produk ditemukan</h3>
                                    <p class="text-slate-500 text-sm mt-1 mb-4">Coba ubah filter pencarian atau tambah produk baru.</p>
                                    @if(request('status') !== 'trash')
                                        @can('create-products')
                                            <a href="{{ route('admin.products.create') }}" class="btn btn-primary">
                                                <i class="material-icons text-sm">add</i> Tambah Produk
                                            </a>
                                        @endcan
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        {{-- Pagination --}}
        <div class="p-4 border-t border-slate-100 dark:border-slate-700">
            {{ $products->links('vendor.pagination.admin') }}
        </div>
    </div>

    @push('scripts')
    <script>
        function confirmAction(e, title, text, type) {
            e.preventDefault(); 
            const form = e.target.closest('form');
            if (typeof window.confirmDialog === 'function') {
                window.confirmDialog({
                    title: title,
                    text: text,
                    icon: type === 'danger' ? 'error' : type,
                    confirmButtonText: 'Ya, Lanjutkan',
                    confirmButtonColor: type
                }).then((result) => {
                    if (result.isConfirmed) {
                        form.submit();
                    }
                });
            } else {
                if (confirm(title + "\n" + text)) form.submit();
            }
            return false;
        }
    </script>
    @endpush

@endsection
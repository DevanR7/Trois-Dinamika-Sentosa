@extends('admin.layouts.app')

@section('title', 'Manajemen Produk')

@section('content')
    <div class="flex flex-col gap-8">
        
        {{-- Header Section --}}
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
            <div>
                <h1 class="text-2xl font-bold text-slate-800 dark:text-white tracking-tight">Daftar Produk</h1>
                <p class="text-sm text-slate-500 dark:text-slate-400 mt-1">Kelola data inventaris, harga, dan stok.</p>
            </div>
            <div class="flex items-center gap-3">
                {{-- Tombol Stock Opname --}}
                <a href="{{ route('admin.stock-opnames.index') }}" class="btn btn-secondary h-11 shadow-sm">
                    <span class="flex items-center justify-center">
                        <i class="material-icons text-[20px]">inventory</i>
                    </span>
                    <span class="ml-2">Stock Opname</span>
                </a>

                {{-- Tombol Tambah --}}
                <a href="{{ route('admin.products.create') }}" class="btn btn-primary h-11 shadow-lg shadow-indigo-500/20">
                    <span class="flex items-center justify-center">
                        <i class="material-icons text-[20px]">add</i>
                    </span>
                    <span class="ml-2">Produk Baru</span>
                </a>
            </div>
        </div>

        {{-- Filter & Search Card --}}
        <div class="bg-white dark:bg-slate-800 rounded-2xl border border-slate-200 dark:border-slate-700 p-5 shadow-sm">
            <form action="{{ route('admin.products.index') }}" method="GET" class="flex flex-col md:flex-row items-center gap-4">
                <div class="relative flex-1 w-full">
                    <div class="absolute inset-y-0 left-0 pl-3 flex items-center justify-center pointer-events-none">
                        <i class="material-icons text-slate-400 text-[20px]">search</i>
                    </div>
                    <input type="text" name="search" value="{{ request('search') }}" 
                           placeholder="Cari berdasarkan kode, nama, atau supplier..." 
                           class="form-input pl-10 h-11 w-full bg-slate-50 dark:bg-slate-900 border-slate-200 dark:border-slate-700 focus:bg-white transition-all rounded-xl">
                </div>
                <div class="w-full md:w-56">
                    <select name="sort" class="tom-select w-full" onchange="this.form.submit()">
                        <option value="terbaru" {{ request('sort') == 'terbaru' ? 'selected' : '' }}>Terbaru Ditambahkan</option>
                        <option value="A-Z" {{ request('sort') == 'A-Z' ? 'selected' : '' }}>Nama (A-Z)</option>
                        <option value="Z-A" {{ request('sort') == 'Z-A' ? 'selected' : '' }}>Nama (Z-A)</option>
                        <option value="stok-terbanyak" {{ request('sort') == 'stok-terbanyak' ? 'selected' : '' }}>Stok Terbanyak</option>
                        <option value="stok-sedikit" {{ request('sort') == 'stok-sedikit' ? 'selected' : '' }}>Stok Sedikit</option>
                    </select>
                </div>
            </form>
        </div>

        {{-- Table Content --}}
        <div class="bg-white dark:bg-slate-800 rounded-2xl border border-slate-200 dark:border-slate-700 shadow-sm overflow-hidden">
            <div class="overflow-x-auto">
                <table class="w-full text-sm text-left">
                    <thead class="text-xs text-slate-500 uppercase bg-slate-50 dark:bg-slate-700/50 border-b border-slate-200 dark:border-slate-700">
                        <tr>
                            <th class="px-6 py-4 font-semibold tracking-wide">Produk</th>
                            <th class="px-6 py-4 font-semibold tracking-wide">Satuan & Pemasok</th>
                            <th class="px-6 py-4 font-semibold tracking-wide text-right">Harga Beli</th>
                            <th class="px-6 py-4 font-semibold tracking-wide text-right">Harga Jual</th>
                            <th class="px-6 py-4 font-semibold tracking-wide text-center">Stok</th>
                            <th class="px-6 py-4 font-semibold tracking-wide text-center">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 dark:divide-slate-700">
                        @forelse($products as $product)
                            <tr class="hover:bg-slate-50/80 dark:hover:bg-slate-700/30 transition-colors duration-200">
                                <td class="px-6 py-4">
                                    <div class="flex items-center gap-4">
                                        {{-- Image Thumbnail --}}
                                        <div class="w-12 h-12 rounded-xl bg-slate-100 dark:bg-slate-900 border border-slate-200 dark:border-slate-700 flex items-center justify-center overflow-hidden shrink-0">
                                            @if($product->image_path)
                                                <img src="{{ asset('storage/' . $product->image_path) }}" alt="{{ $product->product_name }}" class="w-full h-full object-cover">
                                            @else
                                                <i class="material-icons text-slate-400 text-xl">image</i>
                                            @endif
                                        </div>
                                        {{-- Product Name & Code --}}
                                        <div>
                                            <div class="font-bold text-slate-800 dark:text-white text-base mb-0.5">
                                                {{ $product->product_name }}
                                            </div>
                                            <div class="flex items-center gap-2 text-xs text-slate-500 font-mono">
                                                <span class="px-1.5 py-0.5 rounded bg-slate-100 dark:bg-slate-700 border border-slate-200 dark:border-slate-600">
                                                    {{ $product->product_code }}
                                                </span>
                                            </div>
                                        </div>
                                    </div>
                                </td>
                                <td class="px-6 py-4">
                                    <div class="flex flex-col items-start gap-1.5">
                                        {{-- REVISI: Satuan dengan Frame (Border) --}}
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-md border border-slate-200 dark:border-slate-600 bg-slate-50 dark:bg-slate-700/50 text-slate-600 dark:text-slate-300 text-xs font-semibold uppercase tracking-wide">
                                            {{ $product->unit->name ?? 'N/A' }}
                                        </span>
                                        {{-- Supplier dibawahnya --}}
                                        <div class="flex items-center gap-1 text-xs text-slate-500 dark:text-slate-400">
                                            <i class="material-icons text-[14px]">store</i>
                                            <span>{{ $product->supplier->supplier_name ?? '-' }}</span>
                                        </div>
                                    </div>
                                </td>
                                <td class="px-6 py-4 text-right">
                                    <span class="text-slate-600 dark:text-slate-400 font-medium">
                                        {{ number_format($product->purchase_price, 0, ',', '.') }}
                                    </span>
                                </td>
                                <td class="px-6 py-4 text-right">
                                    <span class="text-emerald-600 dark:text-emerald-400 font-bold">
                                        {{ number_format($product->selling_price, 0, ',', '.') }}
                                    </span>
                                </td>
                                <td class="px-6 py-4 text-center">
                                    @if($product->stock_quantity <= 5)
                                        <div class="inline-flex items-center justify-center px-3 py-1 rounded-full bg-red-50 dark:bg-red-900/30 text-red-600 dark:text-red-400 text-xs font-bold border border-red-100 dark:border-red-800">
                                            {{ number_format($product->stock_quantity, 0, ',', '.') }}
                                        </div>
                                    @else
                                        <div class="inline-flex items-center justify-center px-3 py-1 rounded-full bg-slate-100 dark:bg-slate-700 text-slate-600 dark:text-slate-300 text-xs font-bold">
                                            {{ number_format($product->stock_quantity, 0, ',', '.') }}
                                        </div>
                                    @endif
                                </td>
                                <td class="px-6 py-4">
                                    <div class="flex items-center justify-center gap-2">
                                        <a href="{{ route('admin.products.show', $product->product_id) }}" 
                                           class="w-9 h-9 rounded-lg flex items-center justify-center text-slate-500 hover:bg-slate-100 hover:text-indigo-600 dark:text-slate-400 dark:hover:bg-slate-700 transition-all"
                                           title="Detail">
                                            <i class="material-icons text-[18px]">visibility</i>
                                        </a>
                                        <a href="{{ route('admin.products.edit', $product->product_id) }}" 
                                           class="w-9 h-9 rounded-lg flex items-center justify-center text-slate-500 hover:bg-indigo-50 hover:text-indigo-600 dark:text-slate-400 dark:hover:bg-indigo-900/30 dark:hover:text-indigo-400 transition-all"
                                           title="Edit">
                                            <i class="material-icons text-[18px]">edit</i>
                                        </a>
                                        
                                        <button type="button" 
                                                class="w-9 h-9 rounded-lg flex items-center justify-center text-slate-500 hover:bg-red-50 hover:text-red-600 dark:text-slate-400 dark:hover:bg-red-900/30 dark:hover:text-red-400 transition-all"
                                                title="Hapus"
                                                onclick="confirmDelete('{{ $product->product_id }}', '{{ $product->product_name }}')">
                                            <i class="material-icons text-[18px]">delete</i>
                                        </button>
                                        
                                        <form id="delete-form-{{ $product->product_id }}" 
                                              action="{{ route('admin.products.destroy', $product->product_id) }}" 
                                              method="POST" class="hidden">
                                            @csrf
                                            @method('DELETE')
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="px-6 py-12 text-center">
                                    <div class="flex flex-col items-center justify-center">
                                        <div class="w-16 h-16 bg-slate-50 dark:bg-slate-800 rounded-full flex items-center justify-center mb-4">
                                            <i class="material-icons text-3xl text-slate-300">inventory_2</i>
                                        </div>
                                        <h3 class="text-slate-900 dark:text-white font-medium mb-1">Data Tidak Ditemukan</h3>
                                        <p class="text-slate-500 text-sm">Coba ubah kata kunci pencarian atau tambah produk baru.</p>
                                    </div>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            
            {{-- Pagination --}}
            <div class="px-6 py-5 border-t border-slate-200 dark:border-slate-700">
                {{ $products->links() }}
            </div>
        </div>
    </div>

    @push('scripts')
    <script>
        function confirmDelete(id, name) {
            window.confirmDialog({
                title: 'Hapus Produk?',
                text: 'Anda akan menghapus produk "' + name + '". Tindakan ini tidak dapat dibatalkan.',
                icon: 'warning',
                confirmButtonText: 'Ya, Hapus',
                confirmButtonColor: 'danger'
            }).then((result) => {
                if (result.isConfirmed) {
                    document.getElementById('delete-form-' + id).submit();
                }
            });
        }
    </script>
    @endpush
@endsection
@extends('layouts.app')

@section('title', 'Manajemen Produk')

@section('content')
<div class="max-w-7xl mx-auto">
    
    {{-- ========================================================================
         HEADER HALAMAN
         ======================================================================== --}}
    <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center mb-6 gap-4">
        <div>
            <h2 class="text-2xl font-bold text-gray-900">Manajemen Produk</h2>
            <p class="text-sm text-gray-500 mt-1">Daftar stok dan inventaris barang.</p>
        </div>
        @can('create', App\Models\Product::class)
            <a href="{{ route('products.create') }}" class="inline-flex items-center px-4 py-2 bg-indigo-600 hover:bg-indigo-700 text-white text-sm font-medium rounded-lg shadow-sm transition-colors duration-200">
                <i class="bi bi-plus-lg mr-2"></i> Tambah Produk
            </a>
        @endcan
    </div>

    {{-- ========================================================================
         FLASH MESSAGES
         ======================================================================== --}}
    @if (session('success'))
        <div class="mb-6 bg-green-50 border-l-4 border-green-500 p-4 rounded-r shadow-sm flex justify-between items-center animate-fade-in-down">
            <div class="flex items-center">
                <i class="bi bi-check-circle-fill text-green-500 text-xl mr-3"></i>
                <div class="text-sm text-green-700 font-medium">{{ session('success') }}</div>
            </div>
            <button type="button" class="text-green-500 hover:text-green-700 transition" onclick="this.parentElement.remove()">
                <i class="bi bi-x text-lg"></i>
            </button>
        </div>
    @endif
    @if (session('error'))
        <div class="mb-6 bg-red-50 border-l-4 border-red-500 p-4 rounded-r shadow-sm flex justify-between items-center animate-fade-in-down">
            <div class="flex items-center">
                <i class="bi bi-exclamation-triangle-fill text-red-500 text-xl mr-3"></i>
                <div class="text-sm text-red-700 font-medium">{{ session('error') }}</div>
            </div>
            <button type="button" class="text-red-500 hover:text-red-700 transition" onclick="this.parentElement.remove()">
                <i class="bi bi-x text-lg"></i>
            </button>
        </div>
    @endif

    {{-- ========================================================================
         FILTER CARD (LAYOUT DIPERBAIKI)
         ======================================================================== --}}
    <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6 mb-6">
        <form action="{{ route('products.index') }}" method="GET">
            <div class="grid grid-cols-1 md:grid-cols-12 gap-6 items-end">
                
                {{-- 1. INPUT PENCARIAN (Lebar: 5 Kolom) --}}
                <div class="md:col-span-5">
                    <label for="search" class="block text-xs font-bold text-gray-500 uppercase tracking-wider mb-2">
                        Pencarian
                    </label>
                    <div class="relative">
                        <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                            <i class="bi bi-search text-gray-400"></i>
                        </div>
                        <input type="text" name="search" id="search" value="{{ request('search') }}" 
                            class="pl-10 w-full rounded-lg border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm h-10" 
                            placeholder="Cari Nama atau Kode Produk...">
                    </div>
                </div>

                {{-- 2. INPUT SORTIR (Lebar: 4 Kolom) --}}
                <div class="md:col-span-4">
                    <label for="sort" class="block text-xs font-bold text-gray-500 uppercase tracking-wider mb-2">
                        Urutkan Berdasarkan
                    </label>
                    <select name="sort" id="sort" class="w-full rounded-lg border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm h-10 cursor-pointer">
                        <option value="" disabled @selected(!request('sort'))>-- Default --</option>
                        <option value="A-Z" @selected(request('sort') == 'A-Z')>Nama (A-Z)</option>
                        <option value="Z-A" @selected(request('sort') == 'Z-A')>Nama (Z-A)</option>
                        <option value="stok-terbanyak" @selected(request('sort') == 'stok-terbanyak')>Stok Terbanyak</option>
                        <option value="stok-sedikit" @selected(request('sort') == 'stok-sedikit')>Stok Terendah</option>
                    </select>
                </div>

                {{-- 3. TOMBOL AKSI (Lebar: 3 Kolom) --}}
                <div class="md:col-span-3 flex gap-2">
                    {{-- Tombol Filter --}}
                    <button type="submit" class="flex-1 bg-gray-900 hover:bg-black text-white font-medium h-10 px-4 rounded-lg shadow-sm transition-colors duration-200 flex items-center justify-center text-sm">
                        <i class="bi bi-funnel-fill mr-2"></i> Filter
                    </button>
                    
                    {{-- Tombol Reset --}}
                    <a href="{{ route('products.index') }}" class="h-10 w-10 bg-white border border-gray-300 rounded-lg text-gray-500 hover:text-indigo-600 hover:border-indigo-300 transition-colors duration-200 flex items-center justify-center shadow-sm" title="Reset Filter">
                        <i class="bi bi-arrow-counterclockwise text-lg"></i>
                    </a>
                </div>
            </div>
        </form>
    </div>

    {{-- ========================================================================
         TABEL PRODUK (TABLE-FIXED AGAR TIDAK SCROLL SAMPING)
         ======================================================================== --}}
    <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full divide-y divide-gray-200 table-fixed">
                <thead class="bg-gray-50">
                    <tr>
                        <th scope="col" class="px-3 py-3 text-left text-xs font-bold text-gray-500 uppercase tracking-wider w-12">#</th>
                        <th scope="col" class="px-3 py-3 text-left text-xs font-bold text-gray-500 uppercase tracking-wider w-1/3">Produk</th>
                        <th scope="col" class="px-3 py-3 text-left text-xs font-bold text-gray-500 uppercase tracking-wider w-24">Kode</th>
                        <th scope="col" class="px-3 py-3 text-left text-xs font-bold text-gray-500 uppercase tracking-wider w-32">Supplier</th>
                        <th scope="col" class="px-3 py-3 text-center text-xs font-bold text-gray-500 uppercase tracking-wider w-16">Stok</th>
                        <th scope="col" class="px-3 py-3 text-left text-xs font-bold text-gray-500 uppercase tracking-wider w-16">Unit</th>
                        <th scope="col" class="px-3 py-3 text-right text-xs font-bold text-gray-500 uppercase tracking-wider w-28">Beli</th>
                        <th scope="col" class="px-3 py-3 text-right text-xs font-bold text-gray-500 uppercase tracking-wider w-28">Jual</th>
                        <th scope="col" class="px-3 py-3 text-center text-xs font-bold text-gray-500 uppercase tracking-wider w-28">Aksi</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    @forelse ($products as $product)
                        <tr class="hover:bg-gray-50 transition-colors">
                            <td class="px-3 py-3 text-sm text-gray-500 text-center align-middle">
                                {{ $loop->iteration + $products->firstItem() - 1 }}
                            </td>
                            <td class="px-3 py-3 align-top">
                                <div class="flex items-start">
                                    <div class="h-9 w-9 flex-shrink-0 mt-1">
                                        @if ($product->image_path)
                                            <img class="h-9 w-9 rounded-lg object-cover border border-gray-200" src="{{ asset('storage/' . $product->image_path) }}" alt="">
                                        @else
                                            <div class="h-9 w-9 rounded-lg bg-gray-100 flex items-center justify-center text-gray-400 border border-gray-200">
                                                <i class="bi bi-box-seam text-sm"></i>
                                            </div>
                                        @endif
                                    </div>
                                    <div class="ml-3">
                                        <div class="text-sm font-medium text-gray-900 whitespace-normal break-words leading-snug">
                                            {{ $product->product_name }}
                                        </div>
                                    </div>
                                </div>
                            </td>
                            <td class="px-3 py-3 text-xs text-gray-500 font-mono align-top pt-4">
                                {{ $product->product_code }}
                            </td>
                            <td class="px-3 py-3 text-xs text-gray-500 align-top pt-4 whitespace-normal break-words">
                                {{ $product->supplier->supplier_name ?? '-' }}
                            </td>
                            <td class="px-3 py-3 text-center align-top pt-4">
                                @if(($product->stock_quantity ?? 0) <= 5)
                                    <span class="px-2 py-0.5 inline-flex text-xs leading-4 font-bold rounded-full bg-red-100 text-red-700 border border-red-200">
                                        {{ $product->stock_quantity ?? 0 }}
                                    </span>
                                @else
                                    <span class="px-2 py-0.5 inline-flex text-xs leading-4 font-bold rounded-full bg-green-100 text-green-700 border border-green-200">
                                        {{ $product->stock_quantity ?? 0 }}
                                    </span>
                                @endif
                            </td>
                            <td class="px-3 py-3 text-xs text-gray-500 align-top pt-4">
                                {{ $product->unit->name ?? '-' }}
                            </td>
                            <td class="px-3 py-3 text-xs text-gray-500 text-right align-top pt-4">
                                Rp {{ number_format($product->purchase_price ?? 0, 0, ',', '.') }}
                            </td>
                            <td class="px-3 py-3 text-xs font-bold text-gray-900 text-right align-top pt-4">
                                Rp {{ number_format($product->selling_price ?? 0, 0, ',', '.') }}
                            </td>
                            <td class="px-3 py-3 text-center align-top pt-3">
                                <div class="flex justify-center gap-1">
                                    @can('view', $product)
                                        <a href="{{ route('products.show', $product->product_id) }}" class="p-1.5 bg-white border border-gray-300 rounded-md text-indigo-600 hover:bg-indigo-50 hover:border-indigo-300 transition shadow-sm" title="Detail">
                                            <i class="bi bi-eye"></i>
                                        </a>
                                    @endcan

                                    @can('update', $product)
                                        <a href="{{ route('products.edit', $product->product_id) }}" class="p-1.5 bg-white border border-gray-300 rounded-md text-yellow-600 hover:bg-yellow-50 hover:border-yellow-300 transition shadow-sm" title="Edit">
                                            <i class="bi bi-pencil-square"></i>
                                        </a>
                                    @endcan

                                    @can('delete', $product)
                                        <form action="{{ route('products.destroy', $product->product_id) }}" method="POST" class="d-inline form-delete" data-product-name="{{ e($product->product_name) }}">
                                            @csrf 
                                            @method('DELETE')
                                            <button type="submit" class="p-1.5 bg-white border border-gray-300 rounded-md text-red-600 hover:bg-red-50 hover:border-red-300 transition shadow-sm" title="Hapus">
                                                <i class="bi bi-trash"></i>
                                            </button>
                                        </form>
                                    @endcan
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="9" class="px-6 py-12 text-center text-gray-500">
                                <div class="flex flex-col items-center justify-center">
                                    <div class="w-16 h-16 bg-gray-100 rounded-full flex items-center justify-center mb-3">
                                        <i class="bi bi-inbox text-3xl text-gray-400"></i>
                                    </div>
                                    <h5 class="text-gray-900 font-semibold">Tidak ada produk</h5>
                                    <p class="text-gray-500 text-sm">Belum ada data produk yang ditemukan.</p>
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        
        {{-- Pagination --}}
        <div class="px-6 py-4 border-t border-gray-200 bg-gray-50">
            {{ $products->links() }}
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function () {
        // SweetAlert untuk Delete Confirmation
        const deleteForms = document.querySelectorAll('.form-delete');
        deleteForms.forEach(form => {
            form.addEventListener('submit', function(event) {
                event.preventDefault(); 
                const productName = this.dataset.productName;
                Swal.fire({
                    title: 'Hapus Produk?',
                    text: `Anda yakin ingin menghapus "${productName}"?`,
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonColor: '#ef4444',
                    cancelButtonColor: '#6b7280',
                    confirmButtonText: 'Ya, Hapus!',
                    cancelButtonText: 'Batal'
                }).then((result) => {
                    if (result.isConfirmed) {
                        this.submit();
                    }
                });
            });
        });
    });
</script>
@endpush
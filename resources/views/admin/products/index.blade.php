@extends('admin.layouts.app')

@section('title', 'Daftar Produk')

@section('content')
<div class="max-w-7xl mx-auto space-y-6 pb-20 animate-enter">
    
    {{-- HEADER (RESPONSIF MOBILE) --}}
    {{-- Perubahan: flex-col di mobile, flex-row di desktop --}}
    <div class="flex flex-col sm:flex-row sm:justify-between sm:items-end gap-4 mb-6">
        <div>
            <h1 class="text-2xl font-bold text-slate-800 tracking-tight">Manajemen Produk</h1>
            <p class="text-slate-500 text-sm mt-1">Kelola katalog, harga, dan stok barang.</p>
        </div>
        
        @can('create', App\Models\Product::class)
        {{-- Perubahan: w-full di mobile agar mudah ditekan --}}
        <a href="{{ route('admin.products.create') }}" class="w-full sm:w-auto h-[48px] px-6 bg-indigo-600 hover:bg-indigo-700 text-white rounded-lg text-sm font-bold shadow-md transition-all flex items-center justify-center gap-2">
            <i class="material-icons text-[20px]">add</i>
            <span>Tambah Produk</span>
        </a>
        @endcan
    </div>

    {{-- FILTER CARD --}}
    <div class="dashboard-card p-6">
        <form action="{{ route('admin.products.index') }}" method="GET">
            {{-- Grid responsif: 1 kolom di mobile, 12 kolom di desktop --}}
            <div class="grid grid-cols-1 md:grid-cols-12 gap-4 items-end">
                
                {{-- Search --}}
                <div class="md:col-span-5">
                    <label class="block text-xs font-bold text-slate-500 uppercase mb-1.5">Pencarian</label>
                    <div class="relative">
                        <i class="material-icons absolute left-3 top-1/2 -translate-y-1/2 text-slate-400 text-[20px]">search</i>
                        <input type="text" name="search" value="{{ request('search') }}" 
                            class="form-input pl-10" 
                            placeholder="Cari nama, SKU, atau supplier...">
                    </div>
                </div>
                
                {{-- Sort --}}
                <div class="md:col-span-4">
                    <label class="block text-xs font-bold text-slate-500 uppercase mb-1.5">Urutkan</label>
                    <select name="sort" class="select2-basic">
                        <option value="newest" @selected(request('sort') == 'newest')>Terbaru Ditambahkan</option>
                        <option value="name_asc" @selected(request('sort') == 'name_asc')>Nama (A-Z)</option>
                        <option value="name_desc" @selected(request('sort') == 'name_desc')>Nama (Z-A)</option>
                        <option value="stock_low" @selected(request('sort') == 'stock_low')>Stok Terendah</option>
                        <option value="stock_high" @selected(request('sort') == 'stock_high')>Stok Tertinggi</option>
                    </select>
                </div>

                {{-- Buttons --}}
                <div class="md:col-span-3 flex gap-2">
                    <button type="submit" class="h-[48px] flex-1 bg-slate-800 hover:bg-slate-900 text-white rounded-lg text-sm font-bold transition-all shadow-sm flex items-center justify-center gap-2">
                        <i class="material-icons text-[18px]">filter_list</i> Filter
                    </button>
                    <a href="{{ route('admin.products.index') }}" class="h-[48px] w-[48px] flex items-center justify-center bg-white border border-slate-300 hover:border-indigo-500 hover:text-indigo-600 rounded-lg transition-all" title="Reset">
                        <i class="material-icons text-[20px]">refresh</i>
                    </a>
                </div>
            </div>
        </form>
    </div>
    
    {{-- TABLE --}}
    <div class="dashboard-card p-0 overflow-hidden shadow-lg border-0 ring-1 ring-slate-900/5">
        <div class="overflow-x-auto">
            <table class="dashboard-table">
                <thead class="bg-slate-50 border-b border-slate-200">
                    <tr>
                        <th class="pl-6">Produk</th>
                        <th>Detail</th>
                        <th class="text-center">Stok</th>
                        <th class="text-right">HPP</th>
                        <th class="text-right">Harga Jual</th>
                        <th class="text-center pr-6">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 bg-white">
                    @forelse ($products as $product)
                        <tr class="hover:bg-slate-50/50 transition-colors group">
                            {{-- Kolom Produk --}}
                            <td class="pl-6 py-3 w-[35%]">
                                <div class="flex items-center gap-4">
                                    <div class="w-12 h-12 rounded-lg bg-slate-100 border border-slate-200 flex items-center justify-center overflow-hidden flex-shrink-0">
                                        @if ($product->image_path)
                                            <img class="w-full h-full object-cover" src="{{ asset('storage/' . $product->image_path) }}" alt="">
                                        @else
                                            <i class="material-icons text-slate-300 text-xl">image</i>
                                        @endif
                                    </div>
                                    <div>
                                        <a href="{{ route('admin.products.show', $product->product_id) }}" class="text-sm font-bold text-slate-700 line-clamp-1 group-hover:text-indigo-600 transition-colors">
                                            {{ $product->product_name }}
                                        </a>
                                        <div class="text-xs text-slate-500 font-mono mt-0.5 flex items-center gap-1">
                                            <i class="material-icons text-[10px]">qr_code</i> {{ $product->product_code }}
                                        </div>
                                    </div>
                                </div>
                            </td>

                            {{-- Kolom Detail --}}
                            <td class="py-3">
                                <div class="text-xs text-slate-500 space-y-1">
                                    <div class="flex items-center gap-1">
                                        <i class="material-icons text-[12px]">business</i> {{ $product->supplier->supplier_name ?? '-' }}
                                    </div>
                                    <div class="flex items-center gap-1">
                                        <i class="material-icons text-[12px]">straighten</i> {{ $product->unit->name ?? 'Pcs' }}
                                    </div>
                                </div>
                            </td>

                            {{-- Kolom Stok --}}
                            <td class="text-center py-3">
                                @php $isLow = ($product->stock_quantity <= 5); @endphp
                                <span class="inline-flex items-center px-2.5 py-1 rounded-md text-xs font-bold {{ $isLow ? 'bg-red-50 text-red-600 border border-red-100' : 'bg-emerald-50 text-emerald-600 border border-emerald-100' }}">
                                    {{-- Menggunakan casting (float) agar angka desimal tampil benar (1.5) tapi bulat tetap bulat (10) --}}
                                    {{ (float) $product->stock_quantity }}
                                </span>
                            </td>

                            {{-- Kolom HPP --}}
                            <td class="text-right py-3 text-sm text-slate-500 font-mono">
                                {{ number_format($product->purchase_price, 0, ',', '.') }}
                            </td>

                            {{-- Kolom Harga Jual --}}
                            <td class="text-right py-3 text-sm font-bold text-slate-700 font-mono">
                                {{ number_format($product->selling_price, 0, ',', '.') }}
                            </td>

                            {{-- Kolom Aksi (SELALU MUNCUL) --}}
                            <td class="text-center pr-6 py-3">
                                <div class="flex items-center justify-center gap-2">
                                    {{-- Detail --}}
                                    <a href="{{ route('admin.products.show', $product->product_id) }}" class="w-8 h-8 flex items-center justify-center rounded-lg bg-white text-slate-500 border border-slate-200 hover:text-indigo-600 hover:border-indigo-200 transition-colors shadow-sm" title="Detail">
                                        <i class="material-icons text-[16px]">visibility</i>
                                    </a>
                                    
                                    {{-- Edit --}}
                                    @can('update', $product)
                                    <a href="{{ route('admin.products.edit', $product->product_id) }}" class="w-8 h-8 flex items-center justify-center rounded-lg bg-white text-slate-500 border border-slate-200 hover:text-amber-600 hover:border-amber-200 transition-colors shadow-sm" title="Edit">
                                        <i class="material-icons text-[16px]">edit</i>
                                    </a>
                                    @endcan
                                    
                                    {{-- Delete (Menggunakan Global Handler app.js) --}}
                                    @can('delete', $product)
                                    <form action="{{ route('admin.products.destroy', $product->product_id) }}" method="POST" class="delete-form">
                                        @csrf @method('DELETE')
                                        <button type="submit" data-name="{{ $product->product_name }}" class="w-8 h-8 flex items-center justify-center rounded-lg bg-white text-slate-500 border border-slate-200 hover:text-red-600 hover:border-red-200 transition-colors shadow-sm" title="Hapus">
                                            <i class="material-icons text-[16px]">delete</i>
                                        </button>
                                    </form>
                                    @endcan
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="py-12 text-center">
                                <div class="flex flex-col items-center justify-center text-slate-400">
                                    <div class="w-16 h-16 bg-slate-50 rounded-full flex items-center justify-center mb-3">
                                        <i class="material-icons text-4xl opacity-30">inventory_2</i>
                                    </div>
                                    <span class="text-sm font-medium">Tidak ada produk ditemukan.</span>
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
    
    {{-- PAGINATION --}}
    <div class="mt-4">
        {{ $products->withQueryString()->links() }}
    </div>
</div>
@endsection

@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function() {
        // Select2 Init
        $('.select2-basic').select2({ 
            minimumResultsForSearch: Infinity, 
            width: '100%', 
            dropdownCssClass: 'select2-dropdown-clean' 
        });

        // Global Toast Handler
        @if(session('success')) window.showToast("{{ session('success') }}", 'success'); @endif
        @if(session('error')) window.showToast("{{ session('error') }}", 'error'); @endif
    });
</script>
@endpush
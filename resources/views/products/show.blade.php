@extends('layouts.app')

@section('title', 'Detail Produk')

@section('content')
<div class="max-w-6xl mx-auto">
    
    {{-- BREADCRUMB & HEADER --}}
    <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center mb-6">
        <div>
            <div class="flex items-center gap-2 text-sm text-gray-500 mb-1">
                <a href="{{ route('products.index') }}" class="hover:text-indigo-600 transition">Produk</a>
                <span>/</span>
                <span class="text-gray-800">Detail</span>
            </div>
            <h2 class="text-2xl font-bold text-gray-900 tracking-tight">{{ $product->product_name }}</h2>
        </div>
        
        {{-- ACTION BUTTONS --}}
        <div class="flex gap-3 mt-4 sm:mt-0">
            <a href="{{ route('products.index') }}" class="px-4 py-2 bg-white border border-gray-300 text-gray-700 rounded-lg text-sm font-medium hover:bg-gray-50 transition shadow-sm">
                Kembali
            </a>
            @can('update', $product)
            <a href="{{ route('products.edit', $product->product_id) }}" class="px-4 py-2 bg-amber-100 text-amber-800 rounded-lg text-sm font-medium hover:bg-amber-200 transition border border-amber-200">
                <i class="bi bi-pencil mr-1"></i> Edit
            </a>
            @endcan
            @can('delete', $product)
            <button onclick="confirmDelete('{{ $product->product_id }}')" class="px-4 py-2 bg-red-100 text-red-800 rounded-lg text-sm font-medium hover:bg-red-200 transition border border-red-200">
                <i class="bi bi-trash mr-1"></i> Hapus
            </button>
            <form id="delete-form-{{ $product->product_id }}" action="{{ route('products.destroy', $product->product_id) }}" method="POST" class="hidden">
                @csrf @method('DELETE')
            </form>
            @endcan
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
        
        {{-- KOLOM KIRI: GAMBAR & STATUS --}}
        <div class="lg:col-span-1 space-y-6">
            {{-- IMAGE CARD --}}
            <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-4">
                <div class="aspect-square w-full bg-gray-50 rounded-lg overflow-hidden flex items-center justify-center border border-gray-100 relative">
                    @if ($product->image_path)
                        <img src="{{ asset('storage/' . $product->image_path) }}" alt="{{ $product->product_name }}" class="w-full h-full object-contain">
                    @else
                        <div class="text-center text-gray-400">
                            <i class="bi bi-image text-6xl opacity-30"></i>
                            <p class="text-xs mt-2">Tidak ada gambar</p>
                        </div>
                    @endif
                    
                    {{-- Badge Stock Status Overlay --}}
                    <div class="absolute top-3 right-3">
                        @if($product->stock_quantity <= 5)
                            <span class="px-3 py-1 bg-red-500 text-white text-xs font-bold rounded-full shadow-md">Stok Menipis</span>
                        @else
                            <span class="px-3 py-1 bg-green-500 text-white text-xs font-bold rounded-full shadow-md">Tersedia</span>
                        @endif
                    </div>
                </div>
            </div>

            {{-- STOCK INFO CARD --}}
            <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
                <h3 class="text-xs font-bold text-gray-500 uppercase tracking-wider mb-4">Informasi Stok</h3>
                <div class="flex items-end justify-between">
                    <div>
                        <span class="text-3xl font-bold text-gray-900">{{ $product->stock_quantity }}</span>
                        <span class="text-gray-500 ml-1">{{ $product->unit->name ?? 'Unit' }}</span>
                    </div>
                    <div class="text-right">
                        <span class="block text-xs text-gray-500 mb-1">Nilai Aset (Estimasi)</span>
                        <span class="font-medium text-indigo-600">Rp {{ number_format($product->stock_quantity * $product->purchase_price, 0, ',', '.') }}</span>
                    </div>
                </div>
                <div class="w-full bg-gray-100 rounded-full h-2 mt-4">
                    {{-- Visual bar stok (misal max 100) --}}
                    <div class="bg-indigo-600 h-2 rounded-full" style="width: {{ min(($product->stock_quantity / 100) * 100, 100) }}%"></div>
                </div>
            </div>
        </div>

        {{-- KOLOM KANAN: DETAIL --}}
        <div class="lg:col-span-2 space-y-6">
            
            {{-- DETAIL UTAMA --}}
            <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
                <h3 class="text-lg font-bold text-gray-900 mb-4 flex items-center gap-2">
                    <i class="bi bi-info-circle text-indigo-500"></i> Detail Produk
                </h3>
                
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-y-6 gap-x-8">
                    <div>
                        <span class="block text-xs font-bold text-gray-500 uppercase">Kode Produk</span>
                        <span class="block text-base font-medium text-gray-900 mt-1 font-mono bg-gray-50 inline-block px-2 py-1 rounded border border-gray-200">{{ $product->product_code }}</span>
                    </div>
                    <div>
                        <span class="block text-xs font-bold text-gray-500 uppercase">Supplier</span>
                        <a href="{{ route('suppliers.show', $product->supplier_id) }}" class="block text-base font-medium text-indigo-600 mt-1 hover:underline">
                            {{ $product->supplier->supplier_name ?? '-' }}
                        </a>
                    </div>
                    <div class="sm:col-span-2">
                        <span class="block text-xs font-bold text-gray-500 uppercase">Deskripsi</span>
                        <p class="text-sm text-gray-700 mt-2 leading-relaxed bg-gray-50 p-4 rounded-lg border border-gray-100">
                            {{ $product->description ?? 'Tidak ada deskripsi.' }}
                        </p>
                    </div>
                </div>
            </div>

            {{-- HARGA --}}
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-6">
                <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
                    <span class="block text-xs font-bold text-gray-500 uppercase mb-2">Harga Beli (HPP)</span>
                    <div class="flex items-center gap-3">
                        <div class="p-2 bg-gray-100 rounded-lg text-gray-600"><i class="bi bi-tag"></i></div>
                        <span class="text-xl font-bold text-gray-900">Rp {{ number_format($product->purchase_price, 0, ',', '.') }}</span>
                    </div>
                </div>
                <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6 relative overflow-hidden">
                    <div class="absolute top-0 right-0 w-16 h-16 bg-indigo-50 rounded-bl-full -mr-8 -mt-8"></div>
                    <span class="block text-xs font-bold text-indigo-600 uppercase mb-2">Harga Jual</span>
                    <div class="flex items-center gap-3 relative z-10">
                        <div class="p-2 bg-indigo-100 rounded-lg text-indigo-600"><i class="bi bi-cash-coin"></i></div>
                        <span class="text-2xl font-bold text-indigo-700">Rp {{ number_format($product->selling_price, 0, ',', '.') }}</span>
                    </div>
                    {{-- Margin Profit Badge --}}
                    @php 
                        $margin = $product->selling_price - $product->purchase_price;
                        $marginPercent = $product->purchase_price > 0 ? ($margin / $product->purchase_price) * 100 : 0;
                    @endphp
                    <div class="mt-2 text-xs font-medium text-green-600 flex items-center">
                        <i class="bi bi-graph-up-arrow mr-1"></i> Margin: +{{ number_format($marginPercent, 1) }}%
                    </div>
                </div>
            </div>

            {{-- META INFO --}}
            <div class="flex justify-between text-xs text-gray-400 px-2">
                <span>Dibuat: {{ $product->created_at->format('d M Y H:i') }}</span>
                <span>Update: {{ $product->updated_at->format('d M Y H:i') }}</span>
            </div>

        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
    function confirmDelete(id) {
        Swal.fire({
            title: 'Hapus Produk?',
            text: "Data yang dihapus tidak dapat dikembalikan!",
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#ef4444',
            cancelButtonColor: '#6b7280',
            confirmButtonText: 'Ya, Hapus!',
            cancelButtonText: 'Batal'
        }).then((result) => {
            if (result.isConfirmed) {
                document.getElementById('delete-form-' + id).submit();
            }
        })
    }
</script>
@endpush
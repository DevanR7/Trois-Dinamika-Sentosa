@extends('admin.layouts.app')

@section('title', 'Detail Produk')

@section('content')
<div class="max-w-6xl mx-auto pb-20 animate-enter">
    
    {{-- HEADER --}}
    <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center mb-8 gap-4">
        <div>
            <nav class="flex text-sm text-slate-500 mb-1">
                <a href="{{ route('admin.products.index') }}" class="hover:text-indigo-600 transition-colors">Produk</a>
                <span class="mx-2 text-slate-300">/</span>
                <span class="text-slate-800 font-semibold">Detail</span>
            </nav>
            <h1 class="text-2xl font-bold text-slate-800 tracking-tight">{{ $product->product_name }}</h1>
        </div>
        
        <div class="flex gap-2 w-full sm:w-auto">
            <a href="{{ route('admin.products.index') }}" class="px-4 py-2 bg-white border border-slate-300 text-slate-600 rounded-lg text-sm font-bold hover:bg-slate-50 transition-all shadow-sm flex items-center justify-center gap-2">
                <i class="material-icons text-[18px]">arrow_back</i> Kembali
            </a>
            
            @can('update', $product)
            <a href="{{ route('admin.products.edit', $product->product_id) }}" class="px-4 py-2 bg-white border border-slate-300 text-indigo-600 rounded-lg text-sm font-bold hover:bg-indigo-50 hover:border-indigo-200 transition-all shadow-sm flex items-center justify-center gap-2">
                <i class="material-icons text-[18px]">edit</i> Edit
            </a>
            @endcan

            @can('delete', $product)
            <form action="{{ route('admin.products.destroy', $product->product_id) }}" method="POST" class="delete-form inline-block">
                @csrf @method('DELETE')
                <button type="submit" data-name="{{ $product->product_name }}" class="px-4 py-2 bg-red-50 border border-red-200 text-red-600 rounded-lg text-sm font-bold hover:bg-red-100 transition-all shadow-sm flex items-center justify-center gap-2">
                    <i class="material-icons text-[18px]">delete</i> Hapus
                </button>
            </form>
            @endcan
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
        
        {{-- KIRI --}}
        <div class="space-y-8">
            <div class="dashboard-card p-4">
                <div class="aspect-square rounded-lg bg-slate-50 flex items-center justify-center overflow-hidden relative border border-slate-100">
                    @if($product->image_path)
                        <img src="{{ asset('storage/' . $product->image_path) }}" class="w-full h-full object-cover">
                    @else
                        <div class="text-center text-slate-300">
                            <i class="material-icons text-6xl opacity-30">image</i>
                            <p class="text-xs mt-2 font-medium">Tidak ada gambar</p>
                        </div>
                    @endif
                    
                    <div class="absolute top-4 right-4">
                        @php $isLow = ($product->stock_quantity <= 5); @endphp
                        <span class="px-3 py-1.5 {{ $isLow ? 'bg-red-500' : 'bg-emerald-500' }} text-white text-xs font-bold rounded-full shadow-md flex items-center gap-1">
                            <i class="material-icons text-[14px]">{{ $isLow ? 'warning' : 'check_circle' }}</i> {{ $isLow ? 'Stok Menipis' : 'Tersedia' }}
                        </span>
                    </div>
                </div>
            </div>

            <div class="dashboard-card p-6">
                <h4 class="text-xs font-bold text-slate-500 uppercase tracking-wider mb-4 flex items-center gap-2">
                    <i class="material-icons text-indigo-500 text-[18px]">inventory_2</i> Inventaris
                </h4>
                <div class="flex justify-between items-end mb-4">
                    <div>
                        <span class="text-4xl font-bold text-slate-800">{{ (float) $product->stock_quantity }}</span>
                        <span class="text-sm font-medium text-slate-500 ml-1">{{ $product->unit->name ?? 'Unit' }}</span>
                    </div>
                    <div class="text-right">
                        <p class="text-[10px] font-bold text-slate-400 uppercase">Nilai Aset</p>
                        <p class="text-sm font-bold text-indigo-600">Rp {{ number_format($product->stock_quantity * $product->purchase_price, 0, ',', '.') }}</p>
                    </div>
                </div>
                <div class="w-full bg-slate-100 rounded-full h-1.5 overflow-hidden">
                    <div class="bg-indigo-500 h-1.5 rounded-full" style="width: {{ min(($product->stock_quantity / 100) * 100, 100) }}%"></div>
                </div>
            </div>
        </div>

        {{-- KANAN --}}
        <div class="lg:col-span-2 space-y-8">
            <div class="dashboard-card p-0 overflow-hidden">
                <div class="px-6 py-4 border-b border-slate-100 bg-slate-50/50">
                    <h3 class="text-sm font-bold text-slate-700 uppercase tracking-wide">Spesifikasi</h3>
                </div>
                <div class="p-6 grid grid-cols-1 md:grid-cols-2 gap-y-8 gap-x-8">
                    <div>
                        <label class="text-xs font-bold text-slate-400 uppercase block mb-1">SKU / Kode</label>
                        <span class="inline-block bg-slate-100 text-slate-700 px-3 py-1 rounded text-sm font-mono font-bold border border-slate-200">
                            {{ $product->product_code }}
                        </span>
                    </div>
                    <div>
                        <label class="text-xs font-bold text-slate-400 uppercase block mb-1">Supplier</label>
                        <span class="text-indigo-600 font-bold text-sm flex items-center gap-1">
                            <i class="material-icons text-[16px]">business</i> {{ $product->supplier->supplier_name ?? '-' }}
                        </span>
                    </div>
                    <div class="md:col-span-2">
                        <label class="text-xs font-bold text-slate-400 uppercase block mb-2">Deskripsi</label>
                        <div class="text-sm text-slate-600 leading-relaxed bg-slate-50 p-4 rounded-xl border border-slate-100 min-h-[100px]">
                            {{ $product->description ?: 'Tidak ada deskripsi.' }}
                        </div>
                    </div>
                </div>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-6">
                <div class="dashboard-card p-6">
                    <span class="text-xs font-bold text-slate-400 uppercase mb-2 block">Harga Beli (HPP)</span>
                    <div class="flex items-center gap-3">
                        <div class="w-10 h-10 rounded-full bg-slate-100 flex items-center justify-center text-slate-500">
                            <i class="material-icons text-[20px]">shopping_bag</i>
                        </div>
                        <span class="text-xl font-bold text-slate-700 font-mono">Rp {{ number_format($product->purchase_price, 0, ',', '.') }}</span>
                    </div>
                </div>
                
                <div class="dashboard-card p-6 relative overflow-hidden">
                    <div class="absolute top-0 right-0 w-16 h-16 bg-indigo-50 rounded-bl-full -mr-4 -mt-4 opacity-60"></div>
                    <span class="text-xs font-bold text-indigo-500 uppercase mb-2 block relative z-10">Harga Jual</span>
                    <div class="flex items-center gap-3 relative z-10">
                        <div class="w-10 h-10 rounded-full bg-indigo-100 flex items-center justify-center text-indigo-600">
                            <i class="material-icons text-[20px]">sell</i>
                        </div>
                        <span class="text-2xl font-bold text-indigo-700 font-mono">Rp {{ number_format($product->selling_price, 0, ',', '.') }}</span>
                    </div>
                    
                    @php 
                        $margin = $product->selling_price - $product->purchase_price;
                        $percent = $product->purchase_price > 0 ? ($margin / $product->purchase_price) * 100 : 0;
                    @endphp
                    <div class="mt-3 text-xs font-bold text-emerald-600 flex items-center gap-1 relative z-10 bg-emerald-50 w-fit px-2 py-1 rounded">
                        <i class="material-icons text-[14px]">trending_up</i>
                        Margin: {{ number_format($percent, 1) }}%
                    </div>
                </div>
            </div>

            <div class="flex items-center justify-between text-xs text-slate-400 px-2">
                <span class="flex items-center gap-1"><i class="material-icons text-[14px]">schedule</i> Dibuat: {{ $product->created_at->format('d M Y') }}</span>
                <span class="flex items-center gap-1"><i class="material-icons text-[14px]">update</i> Update: {{ $product->updated_at->format('d M Y') }}</span>
            </div>
        </div>
    </div>
</div>
@endsection
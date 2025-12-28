@extends('admin.layouts.app')

@section('title', 'Detail Produk')

@section('content')
    <div class="max-w-6xl mx-auto flex flex-col gap-8">
        
        {{-- Header Navigation --}}
        <div class="flex items-center justify-between">
            <div class="flex items-center gap-4">
                <a href="{{ route('admin.products.index') }}" 
                   class="w-10 h-10 rounded-xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800 flex items-center justify-center text-slate-500 hover:text-indigo-600 hover:border-indigo-200 transition-all shadow-sm">
                    <i class="material-icons text-[20px]">arrow_back</i>
                </a>
                <div>
                    <h1 class="text-xl font-bold text-slate-900 dark:text-white">{{ $product->product_name }}</h1>
                    <p class="text-sm text-slate-500 dark:text-slate-400 font-mono">{{ $product->product_code }}</p>
                </div>
            </div>
            
            <a href="{{ route('admin.products.edit', $product->product_id) }}" 
               class="btn btn-primary h-11 px-6 shadow-lg shadow-indigo-500/20">
                <span class="flex items-center gap-2">
                    <i class="material-icons text-[18px]">edit</i> Edit
                </span>
            </a>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
            {{-- Left Column: Image & Quick Stats --}}
            <div class="lg:col-span-1 flex flex-col gap-6">
                
                {{-- Product Image Card --}}
                <div class="bg-white dark:bg-slate-800 rounded-2xl p-4 border border-slate-200 dark:border-slate-700 shadow-sm flex flex-col items-center">
                    <div class="w-full aspect-square rounded-xl bg-slate-50 dark:bg-slate-900 overflow-hidden mb-5 border border-slate-100 dark:border-slate-700 flex items-center justify-center relative">
                        @if($product->image_path)
                            <img src="{{ asset('storage/' . $product->image_path) }}" 
                                 alt="{{ $product->product_name }}" 
                                 class="w-full h-full object-cover">
                        @else
                            <div class="flex flex-col items-center text-slate-300 dark:text-slate-600">
                                <i class="material-icons text-6xl mb-2">image_not_supported</i>
                                <span class="text-xs font-medium uppercase tracking-wider">No Image</span>
                            </div>
                        @endif
                    </div>
                    
                    <div class="flex items-center justify-center gap-2 mb-2">
                        <span class="px-3 py-1 rounded-full text-xs font-bold uppercase tracking-wide bg-indigo-50 text-indigo-600 dark:bg-indigo-900/30 dark:text-indigo-400 border border-indigo-100 dark:border-indigo-800">
                            {{ $product->unit->name ?? 'Unit' }}
                        </span>
                    </div>
                </div>

                {{-- Status Card --}}
                <div class="bg-white dark:bg-slate-800 rounded-2xl p-6 border border-slate-200 dark:border-slate-700 shadow-sm">
                    <h4 class="text-xs font-bold text-slate-400 uppercase tracking-wider mb-4">Status Inventaris</h4>
                    <div class="flex items-center justify-between mb-4">
                        <div class="flex items-center gap-3">
                            <div class="w-10 h-10 rounded-lg bg-emerald-50 dark:bg-emerald-900/30 flex items-center justify-center text-emerald-600">
                                <i class="material-icons text-[20px]">inventory_2</i>
                            </div>
                            <div>
                                <p class="text-sm font-medium text-slate-500 dark:text-slate-400">Stok Fisik</p>
                                <p class="text-lg font-bold text-slate-800 dark:text-white">{{ number_format($product->stock_quantity, 0, ',', '.') }}</p>
                            </div>
                        </div>
                    </div>
                    
                    <div class="w-full h-1.5 bg-slate-100 dark:bg-slate-700 rounded-full overflow-hidden">
                        @php $percent = min(100, ($product->stock_quantity / 100) * 100); @endphp
                        <div class="h-full bg-emerald-500 rounded-full" style="width: {{ $percent }}%"></div>
                    </div>
                    <p class="text-xs text-slate-400 mt-2 text-right">Target min: 10</p>
                </div>
            </div>

            {{-- Right Column: Detailed Info --}}
            <div class="lg:col-span-2 flex flex-col gap-6">
                
                {{-- 1. Pricing Info --}}
                <div class="bg-white dark:bg-slate-800 rounded-2xl border border-slate-200 dark:border-slate-700 shadow-sm overflow-hidden">
                    <div class="bg-slate-50 dark:bg-slate-900/50 px-6 py-4 border-b border-slate-100 dark:border-slate-700">
                        <h3 class="text-base font-bold text-slate-800 dark:text-white">Informasi Harga</h3>
                    </div>
                    <div class="p-6 grid grid-cols-1 sm:grid-cols-2 gap-6">
                        {{-- Harga Beli --}}
                        <div class="p-5 rounded-xl border border-slate-100 dark:border-slate-700 bg-slate-50/50 dark:bg-slate-800/50">
                            <p class="text-xs text-slate-500 font-semibold uppercase tracking-wider mb-2">Harga Beli Terakhir</p>
                            <div class="flex items-baseline gap-1">
                                <span class="text-sm text-slate-500 font-medium">Rp</span>
                                <span class="text-2xl font-bold text-slate-700 dark:text-slate-200">
                                    {{ number_format($product->purchase_price, 0, ',', '.') }}
                                </span>
                            </div>
                        </div>

                        {{-- Harga Jual --}}
                        <div class="p-5 rounded-xl border border-indigo-100 dark:border-indigo-900/30 bg-indigo-50/50 dark:bg-indigo-900/10">
                            <p class="text-xs text-indigo-500 font-semibold uppercase tracking-wider mb-2">Harga Jual</p>
                            <div class="flex items-baseline gap-1">
                                <span class="text-sm text-indigo-500 font-medium">Rp</span>
                                <span class="text-2xl font-bold text-indigo-600 dark:text-indigo-400">
                                    {{ number_format($product->selling_price, 0, ',', '.') }}
                                </span>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- 2. Profitability Analysis Card (BARU) --}}
                @php
                    // Logika Kalkulasi Margin
                    // Prioritaskan Average Cost (HPP Real), jika 0 gunakan harga beli terakhir
                    $baseCost = $product->average_cost > 0 ? $product->average_cost : $product->purchase_price;
                    $marginRp = $product->selling_price - $baseCost;
                    $marginPercent = $product->selling_price > 0 ? ($marginRp / $product->selling_price) * 100 : 0;
                    $potentialProfit = $marginRp * $product->stock_quantity;
                    
                    // Warna Indikator
                    $isProfit = $marginRp > 0;
                    $colorClass = $isProfit ? 'emerald' : 'red';
                    $iconClass = $isProfit ? 'trending_up' : 'trending_down';
                @endphp

                <div class="bg-white dark:bg-slate-800 rounded-2xl border border-slate-200 dark:border-slate-700 shadow-sm overflow-hidden">
                    <div class="bg-slate-50 dark:bg-slate-900/50 px-6 py-4 border-b border-slate-100 dark:border-slate-700 flex items-center justify-between">
                        <h3 class="text-base font-bold text-slate-800 dark:text-white flex items-center gap-2">
                            <i class="material-icons text-{{ $colorClass }}-500 text-[20px]">analytics</i> 
                            Analisis Profitabilitas
                        </h3>
                        <span class="text-[10px] uppercase font-bold text-slate-400 bg-slate-200 dark:bg-slate-700 px-2 py-0.5 rounded">
                            Estimasi
                        </span>
                    </div>
                    <div class="p-6">
                        {{-- HPP Row --}}
                        <div class="flex items-center justify-between p-3 mb-4 rounded-lg bg-slate-50 dark:bg-slate-900/30 border border-slate-100 dark:border-slate-700">
                            <div class="flex items-center gap-2">
                                <div class="w-8 h-8 rounded-full bg-white dark:bg-slate-800 shadow-sm flex items-center justify-center text-slate-400">
                                    <i class="material-icons text-[16px]">functions</i>
                                </div>
                                <div>
                                    <p class="text-xs text-slate-500 font-semibold uppercase">HPP (Average Cost)</p>
                                    <p class="text-xs text-slate-400">Basis perhitungan margin</p>
                                </div>
                            </div>
                            <span class="text-base font-bold text-slate-700 dark:text-white font-mono">
                                Rp {{ number_format($product->average_cost, 0, ',', '.') }}
                            </span>
                        </div>

                        {{-- Margin Grid --}}
                        <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                            {{-- Margin Rp --}}
                            <div class="p-4 rounded-xl border border-{{ $colorClass }}-100 dark:border-{{ $colorClass }}-900/30 bg-{{ $colorClass }}-50/50 dark:bg-{{ $colorClass }}-900/10 flex flex-col items-center justify-center text-center">
                                <p class="text-xs text-{{ $colorClass }}-600 dark:text-{{ $colorClass }}-400 font-bold uppercase mb-1">Margin / Unit</p>
                                <span class="text-lg font-bold text-{{ $colorClass }}-700 dark:text-{{ $colorClass }}-300">
                                    Rp {{ number_format($marginRp, 0, ',', '.') }}
                                </span>
                            </div>

                            {{-- Margin % --}}
                            <div class="p-4 rounded-xl border border-{{ $colorClass }}-100 dark:border-{{ $colorClass }}-900/30 bg-{{ $colorClass }}-50/50 dark:bg-{{ $colorClass }}-900/10 flex flex-col items-center justify-center text-center">
                                <p class="text-xs text-{{ $colorClass }}-600 dark:text-{{ $colorClass }}-400 font-bold uppercase mb-1">Margin %</p>
                                <div class="flex items-center gap-1">
                                    <i class="material-icons text-sm text-{{ $colorClass }}-500">{{ $iconClass }}</i>
                                    <span class="text-lg font-bold text-{{ $colorClass }}-700 dark:text-{{ $colorClass }}-300">
                                        {{ number_format($marginPercent, 1) }}%
                                    </span>
                                </div>
                            </div>

                            {{-- Potensi Laba --}}
                            <div class="p-4 rounded-xl border border-slate-200 dark:border-slate-600 bg-white dark:bg-slate-800 flex flex-col items-center justify-center text-center shadow-sm">
                                <p class="text-xs text-slate-500 font-bold uppercase mb-1">Potensi Laba Stok</p>
                                <span class="text-lg font-bold text-slate-800 dark:text-white">
                                    Rp {{ number_format($potentialProfit, 0, ',', '.') }}
                                </span>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- 3. General Details --}}
                <div class="bg-white dark:bg-slate-800 rounded-2xl border border-slate-200 dark:border-slate-700 shadow-sm overflow-hidden">
                    <div class="bg-slate-50 dark:bg-slate-900/50 px-6 py-4 border-b border-slate-100 dark:border-slate-700">
                        <h3 class="text-base font-bold text-slate-800 dark:text-white">Detail Tambahan</h3>
                    </div>
                    <div class="p-6">
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-y-8 gap-x-6">
                            <div>
                                <p class="text-xs text-slate-400 uppercase tracking-wider mb-1">Pemasok Utama</p>
                                @if($product->supplier)
                                    <a href="{{ route('admin.suppliers.edit', $product->supplier_id) }}" 
                                       class="flex items-center gap-2 text-indigo-600 hover:text-indigo-700 font-medium transition-colors group">
                                        <i class="material-icons text-[18px]">store</i>
                                        <span class="group-hover:underline">{{ $product->supplier->supplier_name }}</span>
                                    </a>
                                @else
                                    <span class="text-slate-400 italic text-sm">Tidak ada data</span>
                                @endif
                            </div>

                            <div>
                                <p class="text-xs text-slate-400 uppercase tracking-wider mb-1">Terakhir Diperbarui</p>
                                <div class="flex items-center gap-2 text-slate-700 dark:text-slate-300 font-medium text-sm">
                                    <i class="material-icons text-[18px] text-slate-400">update</i>
                                    {{ $product->updated_at->format('d F Y, H:i') }}
                                </div>
                            </div>

                            <div class="sm:col-span-2">
                                <p class="text-xs text-slate-400 uppercase tracking-wider mb-2">Deskripsi Produk</p>
                                <div class="p-4 rounded-xl bg-slate-50 dark:bg-slate-900/50 border border-slate-100 dark:border-slate-700 text-sm text-slate-600 dark:text-slate-300 leading-relaxed">
                                    {{ $product->description ?: 'Tidak ada deskripsi tersedia untuk produk ini.' }}
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

            </div>
        </div>
    </div>
@endsection
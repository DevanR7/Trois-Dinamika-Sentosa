@extends('layouts.app')

@section('title', 'Daftar Retur Pembelian')

@section('content')
<div class="max-w-7xl mx-auto">

    {{-- HEADER HALAMAN --}}
    <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center mb-6 gap-4">
        <div>
            <h2 class="text-2xl font-bold text-gray-900 tracking-tight">Retur Pembelian</h2>
            <p class="text-sm text-gray-500 mt-1">Daftar pengembalian barang ke supplier.</p>
        </div>
        <a href="{{ route('purchase-returns.create') }}" class="inline-flex items-center px-5 py-2.5 bg-indigo-600 hover:bg-indigo-700 text-white text-sm font-medium rounded-lg shadow-sm transition-colors duration-200">
            <i class="bi bi-plus-lg mr-2"></i> Buat Retur Baru
        </a>
    </div>

    {{-- FLASH MESSAGE --}}
    @if (session('success'))
        <div class="mb-6 bg-green-50 border-l-4 border-green-500 p-4 rounded-r shadow-sm flex justify-between items-center animate-fade-in-down">
            <div class="flex items-center gap-3">
                <i class="bi bi-check-circle-fill text-green-500 text-xl"></i>
                <div class="text-sm text-green-700 font-medium">{{ session('success') }}</div>
            </div>
            <button onclick="this.parentElement.remove()" class="text-green-500 hover:text-green-700"><i class="bi bi-x text-lg"></i></button>
        </div>
    @endif

    {{-- FILTER SECTION --}}
    <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-5 mb-6">
        <form action="{{ route('purchase-returns.index') }}" method="GET">
            <div class="grid grid-cols-1 md:grid-cols-12 gap-4 items-end">
                
                {{-- 1. PENCARIAN (5 Kolom) --}}
                <div class="md:col-span-5">
                    <label class="block text-xs font-bold text-gray-500 uppercase tracking-wider mb-1">Pencarian</label>
                    <div class="relative">
                        <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                            <i class="bi bi-search text-gray-400"></i>
                        </div>
                        <input type="text" name="search" value="{{ request('search') }}" 
                            class="pl-10 w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 text-sm py-2" 
                            placeholder="Cari No. Retur / Supplier / PO...">
                    </div>
                </div>

                {{-- 2. TANGGAL (4 Kolom) --}}
                <div class="md:col-span-4">
                    <label class="block text-xs font-bold text-gray-500 uppercase tracking-wider mb-1">Tanggal Retur</label>
                    <input type="date" name="return_date" value="{{ request('return_date') }}" 
                        class="w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 text-sm py-2">
                </div>

                {{-- 3. TOMBOL (3 Kolom) --}}
                <div class="md:col-span-3 flex gap-2">
                    <button type="submit" class="flex-1 bg-gray-800 hover:bg-gray-900 text-white font-medium py-2 px-4 rounded-md shadow-sm transition text-sm flex items-center justify-center">
                        <i class="bi bi-funnel-fill mr-2"></i> Filter
                    </button>
                    <a href="{{ route('purchase-returns.index') }}" class="px-3 py-2 bg-white border border-gray-300 rounded-md text-gray-500 hover:text-indigo-600 hover:border-indigo-300 transition flex items-center justify-center" title="Reset">
                        <i class="bi bi-arrow-clockwise text-lg"></i>
                    </a>
                </div>
            </div>
        </form>
    </div>

    {{-- TABEL DATA --}}
    <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse">
                <thead class="bg-gray-50 border-b border-gray-200">
                    <tr>
                        <th class="px-6 py-3 text-xs font-bold text-gray-500 uppercase tracking-wider">No. Retur</th>
                        <th class="px-6 py-3 text-xs font-bold text-gray-500 uppercase tracking-wider">Supplier</th>
                        <th class="px-6 py-3 text-xs font-bold text-gray-500 uppercase tracking-wider">PO Asli</th>
                        <th class="px-6 py-3 text-xs font-bold text-gray-500 uppercase tracking-wider">Tanggal</th>
                        <th class="px-6 py-3 text-xs font-bold text-gray-500 uppercase tracking-wider text-right">Total Nilai</th>
                        <th class="px-6 py-3 text-xs font-bold text-gray-500 uppercase tracking-wider text-center">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @forelse ($purchaseReturns as $return)
                        <tr class="hover:bg-gray-50 transition-colors">
                            <td class="px-6 py-4 whitespace-nowrap">
                                <a href="{{ route('purchase-returns.show', $return->return_id) }}" class="text-sm font-bold text-indigo-600 hover:underline font-mono">
                                    {{ $return->return_number }}
                                </a>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="text-sm font-medium text-gray-900">{{ $return->supplier->supplier_name ?? '-' }}</div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <a href="{{ route('purchase-orders.show', $return->purchase_order_id) }}" class="text-xs font-medium text-gray-500 hover:text-indigo-600 bg-gray-100 px-2 py-1 rounded">
                                    {{ $return->purchaseOrder->po_number ?? '-' }}
                                </a>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-600">
                                {{ optional($return->return_date)->format('d M Y') }}
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-right">
                                <span class="text-sm font-bold text-red-600">Rp {{ number_format($return->total_amount, 0, ',', '.') }}</span>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-center text-sm font-medium">
                                <a href="{{ route('purchase-returns.show', $return->return_id) }}" class="p-1.5 bg-white border border-gray-300 rounded-md text-indigo-600 hover:bg-indigo-50 transition shadow-sm inline-block" title="Detail">
                                    <i class="bi bi-eye"></i>
                                </a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="px-6 py-10 text-center text-gray-500">
                                <div class="flex flex-col items-center justify-center">
                                    <i class="bi bi-inbox text-4xl text-gray-300 mb-3"></i>
                                    <p class="text-sm">Belum ada data retur pembelian.</p>
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        
        {{-- PAGINATION --}}
        <div class="px-6 py-4 border-t border-gray-200 bg-gray-50">
            {{ $purchaseReturns->appends(request()->query())->links() }}
        </div>
    </div>
</div>
@endsection
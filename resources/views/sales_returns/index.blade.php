@extends('layouts.app')

@section('title', 'Daftar Retur Penjualan')

@section('content')
<div class="max-w-7xl mx-auto">
    
    {{-- HEADER --}}
    <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center mb-6 gap-4">
        <div>
            <h2 class="text-2xl font-bold text-gray-900 tracking-tight">Retur Penjualan</h2>
            <p class="text-sm text-gray-500 mt-1">Daftar pengembalian barang dari pelanggan.</p>
        </div>
        <a href="{{ route('sales-returns.create') }}" class="inline-flex items-center px-4 py-2 bg-indigo-600 hover:bg-indigo-700 text-white text-sm font-bold rounded-lg shadow-md transition flex items-center gap-2">
            <i class="bi bi-plus-lg"></i> Buat Retur Baru
        </a>
    </div>

    {{-- NOTIFIKASI --}}
    @if (session('success'))
        <div class="mb-6 bg-green-50 border-l-4 border-green-500 p-4 rounded-r shadow-sm flex justify-between items-center animate-fade-in-down">
            <div class="flex items-center gap-3">
                <i class="bi bi-check-circle-fill text-green-500 text-xl"></i>
                <div class="text-sm text-green-700 font-medium">{{ session('success') }}</div>
            </div>
            <button onclick="this.parentElement.remove()" class="text-green-500 hover:text-green-700"><i class="bi bi-x text-lg"></i></button>
        </div>
    @endif

    {{-- FILTER CARD --}}
    <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-5 mb-6">
        <form action="{{ route('sales-returns.index') }}" method="GET">
            <div class="grid grid-cols-1 md:grid-cols-12 gap-4 items-end">
                
                {{-- Pencarian --}}
                <div class="md:col-span-5">
                    <label class="block text-xs font-bold text-gray-500 uppercase tracking-wider mb-1">Pencarian</label>
                    <div class="relative">
                        <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                            <i class="bi bi-search text-gray-400"></i>
                        </div>
                        <input type="text" name="search" value="{{ request('search') }}" 
                            class="pl-10 w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 text-sm py-2" 
                            placeholder="No. Retur / Klien / Invoice...">
                    </div>
                </div>

                {{-- Tanggal --}}
                <div class="md:col-span-4">
                    <label class="block text-xs font-bold text-gray-500 uppercase tracking-wider mb-1">Tanggal Retur</label>
                    <input type="date" name="return_date" value="{{ request('return_date') }}" 
                        class="w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 text-sm py-2">
                </div>

                {{-- Tombol --}}
                <div class="md:col-span-3 flex gap-2">
                    <button type="submit" class="flex-1 bg-gray-800 hover:bg-gray-900 text-white font-medium py-2 px-4 rounded-md shadow-sm transition text-sm flex items-center justify-center gap-2">
                        <i class="bi bi-funnel-fill"></i> Filter
                    </button>
                    <a href="{{ route('sales-returns.index') }}" class="px-3 py-2 bg-white border border-gray-300 rounded-md text-gray-500 hover:text-indigo-600 font-medium transition flex items-center justify-center shadow-sm" title="Reset">
                        <i class="bi bi-arrow-clockwise text-lg"></i>
                    </a>
                </div>
            </div>
        </form>
    </div>

    {{-- LIST CARD --}}
    <div class="flex flex-col gap-4">
        @forelse ($salesReturns as $return)
            <div class="bg-white rounded-lg shadow-sm border border-gray-200 overflow-hidden border-l-4 border-l-red-500 hover:shadow-md transition-shadow">
                
                {{-- HEADER CARD --}}
                <div class="p-5 cursor-pointer hover:bg-gray-50 transition-colors" onclick="toggleAccordion('collapse-{{ $return->return_id }}')">
                    <div class="flex flex-col lg:flex-row lg:items-center justify-between gap-6">
                        
                        {{-- Info Utama --}}
                        <div class="flex items-center gap-4 lg:w-1/3">
                            <div class="w-12 h-12 rounded-full bg-red-50 flex items-center justify-center text-red-500 flex-shrink-0 border border-red-100">
                                <i class="bi bi-arrow-counterclockwise text-xl"></i>
                            </div>
                            <div>
                                <h3 class="text-base font-bold text-gray-900 mb-0.5">{{ $return->return_number }}</h3>
                                <p class="text-sm font-medium text-indigo-600">{{ $return->client->client_name ?? 'Klien Dihapus' }}</p>
                            </div>
                        </div>

                        {{-- Invoice & Tanggal --}}
                        <div class="flex gap-8 lg:w-1/3">
                            <div>
                                <span class="block text-[10px] font-bold text-gray-500 uppercase mb-1">Invoice Asal</span>
                                <span class="text-sm font-medium text-gray-900 bg-gray-100 px-2 py-0.5 rounded border border-gray-200">
                                    {{ $return->salesInvoice->invoice_number ?? 'N/A' }}
                                </span>
                            </div>
                            <div>
                                <span class="block text-[10px] font-bold text-gray-500 uppercase mb-1">Tanggal</span>
                                <span class="text-sm font-medium text-gray-900">{{ optional($return->return_date)->format('d M Y') }}</span>
                            </div>
                        </div>

                        {{-- Nilai & Icon --}}
                        <div class="flex items-center justify-between lg:justify-end gap-4 lg:w-1/3">
                            <div class="text-right">
                                <span class="block text-[10px] font-bold text-gray-500 uppercase mb-1">Nilai Retur</span>
                                <span class="text-sm font-bold text-red-600">Rp {{ number_format($return->total_amount, 0, ',', '.') }}</span>
                            </div>
                            <i class="bi bi-chevron-down text-gray-400 transition-transform duration-200" id="icon-collapse-{{ $return->return_id }}"></i>
                        </div>
                    </div>
                </div>

                {{-- COLLAPSE BODY --}}
                <div id="collapse-{{ $return->return_id }}" class="hidden bg-gray-50 border-t border-gray-100">
                    <div class="p-5 flex justify-end">
                        <a href="{{ route('sales-returns.show', $return->return_id) }}" class="px-4 py-2 bg-white border border-indigo-200 text-indigo-700 font-medium rounded-lg hover:bg-indigo-50 hover:border-indigo-300 transition text-sm shadow-sm flex items-center gap-2">
                            <i class="bi bi-eye"></i> Lihat Detail
                        </a>
                    </div>
                </div>

            </div>
        @empty
            <div class="text-center py-12 bg-white rounded-xl border border-dashed border-gray-300">
                <div class="w-16 h-16 bg-gray-100 rounded-full flex items-center justify-center mx-auto mb-4">
                    <i class="bi bi-inbox text-3xl text-gray-400"></i>
                </div>
                <h3 class="text-lg font-medium text-gray-900">Tidak ada data retur</h3>
                <p class="text-gray-500 text-sm mt-1">Belum ada pengembalian barang yang tercatat.</p>
            </div>
        @endforelse
    </div>

    <div class="mt-6">
        {{ $salesReturns->appends(request()->query())->links() }}
    </div>
</div>
@endsection

@push('scripts')
<script>
    function toggleAccordion(id) {
        const el = document.getElementById(id);
        const icon = document.getElementById('icon-' + id);
        if(el.classList.contains('hidden')) {
            el.classList.remove('hidden');
            if(icon) icon.classList.add('rotate-180');
        } else {
            el.classList.add('hidden');
            if(icon) icon.classList.remove('rotate-180');
        }
    }
</script>
@endpush
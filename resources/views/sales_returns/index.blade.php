@extends('layouts.app')

@section('title', 'Daftar Retur Penjualan')

@section('content')
<div class="max-w-7xl mx-auto pb-20 animate-enter">
    
    {{-- HEADER --}}
    <div class="flex flex-col sm:flex-row justify-between items-end gap-4 mb-8">
        <div>
            <h1 class="text-2xl font-bold text-slate-800 tracking-tight">Retur Penjualan</h1>
            <p class="text-slate-500 text-sm mt-1">Daftar pengembalian barang dari pelanggan.</p>
        </div>
        <a href="{{ route('sales-returns.create') }}" class="h-[48px] px-8 bg-indigo-600 hover:bg-indigo-700 text-white text-sm font-bold rounded-lg shadow-md hover:shadow-lg transition-all flex items-center justify-center gap-2 group hover:-translate-y-0.5">
            <i class="material-icons text-[20px] group-hover:rotate-90 transition-transform">add</i> 
            <span>Buat Retur Baru</span>
        </a>
    </div>

    {{-- NOTIFIKASI --}}
    @push('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            @if(session('success')) window.showToast("{{ session('success') }}", 'success'); @endif
            @if(session('error')) window.showToast("{{ session('error') }}", 'error'); @endif
        });
    </script>
    @endpush

    {{-- FILTER CARD --}}
    <div class="dashboard-card p-6 mb-6">
        <form action="{{ route('sales-returns.index') }}" method="GET">
            <div class="grid grid-cols-1 md:grid-cols-12 gap-4 items-end">
                
                {{-- Pencarian --}}
                <div class="md:col-span-5">
                    <label class="block text-xs font-bold text-slate-500 uppercase mb-1.5">Pencarian</label>
                    <div class="relative">
                        <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                            <i class="material-icons text-slate-400 text-[20px]">search</i>
                        </div>
                        <input type="text" name="search" value="{{ request('search') }}" 
                            class="form-input pl-10" 
                            placeholder="No. Retur / Klien / Invoice...">
                    </div>
                </div>

                {{-- Tanggal --}}
                <div class="md:col-span-4">
                    <label class="block text-xs font-bold text-slate-500 uppercase mb-1.5">Tanggal Retur</label>
                    <input type="date" name="return_date" value="{{ request('return_date') }}" 
                        class="form-input">
                </div>

                {{-- Tombol --}}
                <div class="md:col-span-3 flex gap-2">
                    <button type="submit" class="flex-1 h-[48px] bg-slate-800 hover:bg-slate-900 text-white font-bold rounded-lg shadow-sm transition-all flex items-center justify-center gap-2">
                        <i class="material-icons text-[18px]">filter_list</i> Filter
                    </button>
                    <a href="{{ route('sales-returns.index') }}" class="h-[48px] w-[48px] flex items-center justify-center bg-white border border-slate-300 text-slate-500 hover:text-indigo-600 hover:border-indigo-500 font-medium rounded-lg shadow-sm transition" title="Reset">
                        <i class="material-icons text-[20px]">refresh</i>
                    </a>
                </div>
            </div>
        </form>
    </div>

    {{-- TABEL DATA --}}
    <div class="dashboard-card p-0 overflow-hidden shadow-lg border-0 ring-1 ring-slate-900/5">
        <div class="overflow-x-auto">
            <table class="dashboard-table min-w-full">
                <thead class="bg-slate-50 border-b border-slate-200">
                    <tr>
                        <th class="pl-6 w-32">No. Retur</th>
                        <th>Klien</th>
                        <th>Invoice Asal</th>
                        <th>Tanggal</th>
                        <th class="text-right">Total Nilai</th>
                        <th class="w-24 text-center pr-6">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 bg-white">
                    @forelse ($salesReturns as $return)
                        <tr class="hover:bg-slate-50/50 transition-colors">
                            <td class="pl-6 py-4">
                                <a href="{{ route('sales-returns.show', $return->return_id) }}" class="text-sm font-bold text-indigo-600 hover:text-indigo-800 hover:underline font-mono">
                                    {{ $return->return_number }}
                                </a>
                            </td>
                            <td class="py-4">
                                <div class="text-sm font-bold text-slate-800">{{ $return->client->client_name ?? 'Klien Dihapus' }}</div>
                            </td>
                            <td class="py-4">
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded text-xs font-bold bg-slate-100 text-slate-600 border border-slate-200 font-mono">
                                    {{ $return->salesInvoice->invoice_number ?? 'N/A' }}
                                </span>
                            </td>
                            <td class="py-4 text-sm text-slate-600">
                                <div class="flex items-center gap-2">
                                    <i class="material-icons text-slate-400 text-[16px]">event</i>
                                    {{ optional($return->return_date)->format('d M Y') }}
                                </div>
                            </td>
                            <td class="py-4 text-right text-sm font-bold text-red-600 font-mono">
                                Rp {{ number_format($return->total_amount, 0, ',', '.') }}
                            </td>
                            <td class="pr-6 py-4 text-center">
                                <a href="{{ route('sales-returns.show', $return->return_id) }}" 
                                   class="w-8 h-8 flex items-center justify-center mx-auto bg-white border border-slate-200 rounded-lg text-slate-500 hover:text-indigo-600 hover:border-indigo-200 transition shadow-sm" 
                                   title="Lihat Detail">
                                    <i class="material-icons text-[16px]">visibility</i>
                                </a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="px-6 py-12 text-center">
                                <div class="flex flex-col items-center justify-center">
                                    <div class="w-16 h-16 bg-slate-50 rounded-full flex items-center justify-center mb-4 text-slate-300">
                                        <i class="material-icons text-4xl">assignment_return</i>
                                    </div>
                                    <h3 class="text-base font-bold text-slate-800">Belum ada data retur</h3>
                                    <p class="text-sm text-slate-500 mt-1">Belum ada pengembalian barang yang tercatat.</p>
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <div class="mt-6">
        {{ $salesReturns->appends(request()->query())->links() }}
    </div>
</div>
@endsection
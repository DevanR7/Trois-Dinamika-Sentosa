@extends('admin.layouts.app')

@section('title', 'Sampah Pesanan Penjualan')

@section('content')
<div class="flex flex-col gap-6">

    {{-- HEADER SECTION --}}
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div class="flex items-center gap-3">
            {{-- PERBAIKAN: Button Back Presisi Tengah --}}
            <a href="{{ route('admin.sales-orders.index') }}" 
               class="w-9 h-9 flex items-center justify-center rounded-lg bg-white border border-slate-200 text-slate-500 hover:bg-slate-100 hover:text-indigo-600 transition-all dark:bg-slate-800 dark:border-slate-700 dark:text-slate-400 dark:hover:text-white shadow-sm">
                <i class="material-icons text-xl leading-none">arrow_back</i>
            </a>

            <div>
                <h1 class="page-title text-xl text-red-600 dark:text-red-400">Sampah Pesanan Penjualan</h1>
                <p class="page-subtitle">Kelola pesanan yang telah dihapus sementara.</p>
            </div>
        </div>
    </div>

    {{-- FILTER SECTION --}}
    <div class="card p-4">
        <form method="GET" action="{{ route('admin.sales-orders.trash') }}">
            <div class="relative max-w-md">
                <div class="absolute inset-y-0 left-0 flex items-center pl-3 pointer-events-none">
                    <i class="material-icons text-slate-400">search</i>
                </div>
                <input type="text" name="search" value="{{ request('search') }}" 
                    class="form-input pl-10" 
                    placeholder="Cari No. Order, Klien di sampah...">
            </div>
        </form>
    </div>

    {{-- TABLE SECTION --}}
    <div class="card overflow-hidden">
        <div class="table-container border-0 shadow-none rounded-none">
            <table class="table-modern">
                <thead class="bg-red-50 dark:bg-red-900/20">
                    <tr>
                        <th class="w-16 text-center">#</th>
                        <th>No. Pesanan</th>
                        <th>Klien</th>
                        <th>Sales</th>
                        <th class="text-right">Total</th>
                        <th>Dihapus Pada</th>
                        <th class="text-right">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($orders as $order)
                    <tr class="group hover:bg-red-50/50 dark:hover:bg-red-900/10 transition-colors">
                        <td class="text-center text-slate-400 text-xs">
                            {{ ($orders->currentpage()-1) * $orders->perpage() + $loop->index + 1 }}
                        </td>
                        <td>
                            <span class="font-bold text-slate-700 dark:text-slate-200">
                                {{ $order->order_number }}
                            </span>
                            <span class="text-xs text-slate-400 block mt-0.5">
                                Order: {{ $order->order_date->format('d/m/Y') }}
                            </span>
                        </td>
                        <td>
                            <div class="font-medium text-slate-700 dark:text-slate-200">
                                {{ $order->client->client_name ?? 'Klien Terhapus' }}
                            </div>
                        </td>
                        <td>
                            <span class="text-sm text-slate-500">
                                {{ $order->sales->full_name ?? 'System' }}
                            </span>
                        </td>
                        <td class="text-right">
                            <span class="font-bold text-slate-700 dark:text-white">
                                Rp {{ number_format($order->total_amount, 0, ',', '.') }}
                            </span>
                        </td>
                        <td>
                            <span class="text-xs font-mono text-red-500 bg-red-50 px-2 py-1 rounded">
                                {{ $order->deleted_at->format('d M Y H:i') }}
                            </span>
                        </td>
                        <td class="text-right">
                            <div class="flex items-center justify-end gap-2">
                                
                                {{-- Tombol Restore --}}
                                <form action="{{ route('admin.sales-orders.restore', $order->order_id) }}" method="POST">
                                    @csrf
                                    @method('PATCH')
                                    <button type="submit" 
                                            class="btn-action btn-action-restore" 
                                            title="Pulihkan Pesanan">
                                        <i class="material-icons">restore</i>
                                    </button>
                                </form>

                                {{-- Tombol Force Delete --}}
                                <form action="{{ route('admin.sales-orders.forceDelete', $order->order_id) }}" method="POST" onsubmit="return confirm('PERINGATAN: Pesanan ini akan dihapus permanen beserta item didalamnya. Tindakan tidak dapat dibatalkan. Lanjutkan?');">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" 
                                            class="btn-action btn-action-delete" 
                                            title="Hapus Permanen">
                                        <i class="material-icons">delete_forever</i>
                                    </button>
                                </form>

                            </div>
                        </td>
                    </tr>
                    @empty
                    {{-- EMPTY STATE --}}
                    <tr>
                        <td colspan="7">
                            <div class="flex flex-col items-center justify-center py-12 text-center">
                                <div class="w-20 h-20 bg-slate-100 dark:bg-slate-700 rounded-full flex items-center justify-center mb-4 mx-auto">
                                    <i class="material-icons text-4xl text-slate-400">delete_outline</i>
                                </div>
                                <h3 class="text-lg font-bold text-slate-700 dark:text-white">Sampah Kosong</h3>
                                <p class="text-sm text-slate-500 mt-2 max-w-sm mx-auto">
                                    Tidak ada pesanan penjualan yang dihapus.
                                </p>
                                <a href="{{ route('admin.sales-orders.index') }}" class="btn btn-secondary mt-6 border-slate-300">
                                    Kembali ke Daftar Pesanan
                                </a>
                            </div>
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        {{-- Pagination --}}
        @if($orders->hasPages())
        <div class="px-6 py-4 border-t border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-800">
            {{ $orders->links('vendor.pagination.admin') }}
        </div>
        @endif
    </div>
</div>
@endsection
@extends('admin.layouts.app')

@section('title', 'Tinjauan Pesanan Klien')

@section('content')
<div class="flex flex-col gap-6">

    {{-- HEADER SECTION --}}
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div>
            <h1 class="page-title">Pesanan Dari Klien</h1>
            <p class="page-subtitle">Review dan proses pesanan yang dibuat mandiri oleh klien.</p>
        </div>
        
        {{-- Statistik Singkat (Opsional) --}}
        <div class="flex items-center gap-2">
            <span class="px-3 py-1 rounded-lg bg-indigo-50 text-indigo-600 text-xs font-bold border border-indigo-100 dark:bg-indigo-900/30 dark:border-indigo-800 dark:text-indigo-400">
                Total Pending: {{ \App\Models\Order::where('order_source', 'client')->where('status', 'pending_review')->count() }}
            </span>
        </div>
    </div>

    {{-- TABS NAVIGATION --}}
    <div class="flex border-b border-slate-200 dark:border-slate-700">
        <a href="{{ route('admin.client-order-reviews.index', ['view' => 'pending']) }}" 
           class="flex items-center gap-2 px-6 py-3 text-sm font-bold border-b-2 transition-all {{ $view === 'pending' ? 'border-indigo-600 text-indigo-600 dark:text-indigo-400' : 'border-transparent text-slate-500 hover:text-slate-700 hover:border-slate-300 dark:text-slate-400 dark:hover:text-slate-200' }}">
            <i class="material-icons text-lg">pending_actions</i>
            Menunggu Review
        </a>
        <a href="{{ route('admin.client-order-reviews.index', ['view' => 'history']) }}" 
           class="flex items-center gap-2 px-6 py-3 text-sm font-bold border-b-2 transition-all {{ $view === 'history' ? 'border-indigo-600 text-indigo-600 dark:text-indigo-400' : 'border-transparent text-slate-500 hover:text-slate-700 hover:border-slate-300 dark:text-slate-400 dark:hover:text-slate-200' }}">
            <i class="material-icons text-lg">history</i>
            Riwayat Proses
        </a>
    </div>

    {{-- FILTER SECTION --}}
    <div class="card p-4">
        <form method="GET" action="{{ route('admin.client-order-reviews.index') }}" class="grid grid-cols-1 md:grid-cols-12 gap-4">
            {{-- Pertahankan Tab Aktif --}}
            <input type="hidden" name="view" value="{{ $view }}">

            {{-- Search Bar --}}
            <div class="md:col-span-5 relative">
                <div class="absolute inset-y-0 left-0 flex items-center pl-3 pointer-events-none">
                    <i class="material-icons text-slate-400">search</i>
                </div>
                <input type="text" name="search" value="{{ request('search') }}" 
                    class="form-input pl-10" 
                    placeholder="Cari No. Order atau Nama Klien...">
            </div>

            {{-- Filter Tanggal --}}
            <div class="md:col-span-3">
                <select name="date_filter" class="form-select" onchange="this.form.submit()">
                    <option value="">Semua Periode</option>
                    @foreach($uniqueDates as $ym => $label)
                        <option value="{{ $ym }}" {{ request('date_filter') == $ym ? 'selected' : '' }}>
                            {{ $label }}
                        </option>
                    @endforeach
                </select>
            </div>

            {{-- Filter Status (Hanya muncul di Tab History) --}}
            @if($view === 'history')
            <div class="md:col-span-2">
                <select name="status_filter" class="form-select" onchange="this.form.submit()">
                    <option value="">Semua Status</option>
                    <option value="invoiced" {{ request('status_filter') == 'invoiced' ? 'selected' : '' }}>Disetujui</option>
                    <option value="rejected" {{ request('status_filter') == 'rejected' ? 'selected' : '' }}>Ditolak</option>
                </select>
            </div>
            @else
            <div class="md:col-span-2"></div> {{-- Spacer --}}
            @endif

            {{-- Reset Button --}}
            <div class="md:col-span-2">
                <a href="{{ route('admin.client-order-reviews.index', ['view' => $view]) }}" class="btn btn-secondary w-full justify-center">
                    Reset
                </a>
            </div>
        </form>
    </div>

    {{-- TABLE SECTION --}}
    <div class="card overflow-hidden">
        <div class="table-container border-0 shadow-none rounded-none">
            <table class="table-modern">
                <thead class="bg-slate-50 dark:bg-slate-800">
                    <tr>
                        <th class="w-16 text-center">#</th>
                        <th>No. Pesanan</th>
                        <th>Klien</th>
                        <th>Tanggal Order</th>
                        <th class="text-right">Estimasi Total</th>
                        <th class="text-center">Status</th>
                        <th class="text-right">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($clientOrders as $order)
                    <tr class="group hover:bg-slate-50 dark:hover:bg-slate-800/50 transition-colors">
                        <td class="text-center text-slate-400 text-xs">
                            {{ ($clientOrders->currentpage()-1) * $clientOrders->perpage() + $loop->index + 1 }}
                        </td>
                        <td>
                            <div class="flex flex-col">
                                <span class="font-bold text-indigo-600 dark:text-indigo-400">
                                    {{ $order->order_number }}
                                </span>
                                @if($order->notes)
                                    <span class="text-[10px] text-slate-400 flex items-center gap-1 mt-1">
                                        <i class="material-icons text-[10px]">sticky_note_2</i> Ada Catatan
                                    </span>
                                @endif
                            </div>
                        </td>
                        <td>
                            <div class="font-medium text-slate-700 dark:text-slate-200">
                                {{ $order->client->client_name ?? 'Klien Terhapus' }}
                            </div>
                            <div class="text-[10px] text-slate-400">
                                {{ $order->client->email ?? '-' }}
                            </div>
                        </td>
                        <td>
                            <span class="text-sm text-slate-600 dark:text-slate-400">
                                {{ $order->order_date->format('d M Y') }}
                            </span>
                            <span class="text-[10px] text-slate-400 block">
                                {{ $order->created_at->format('H:i') }} WIB
                            </span>
                        </td>
                        <td class="text-right">
                            <span class="font-bold text-slate-700 dark:text-white block">
                                Rp {{ number_format($order->total_amount, 0, ',', '.') }}
                            </span>
                            <span class="text-[10px] text-slate-400">{{ $order->items->count() }} jenis barang</span>
                        </td>
                        <td class="text-center">
                            @php
                                $statusClass = match($order->status) {
                                    'pending_review' => 'badge-warning',
                                    'invoiced' => 'badge-success', // Approved jadi Invoice
                                    'rejected' => 'badge-danger',
                                    default => 'badge-secondary'
                                };
                                $statusLabel = match($order->status) {
                                    'pending_review' => 'Perlu Review',
                                    'invoiced' => 'Disetujui',
                                    'rejected' => 'Ditolak',
                                    default => ucfirst($order->status)
                                };
                            @endphp
                            <span class="badge {{ $statusClass }} animate-pulse-slow">
                                {{ $statusLabel }}
                            </span>
                        </td>
                        <td class="text-right">
                            <div class="flex items-center justify-end gap-2">
                                <a href="{{ route('admin.client-order-reviews.show', $order->order_id) }}" 
                                   class="btn-action btn-action-view hover:bg-indigo-50 dark:hover:bg-slate-700 transition-colors" 
                                   title="Review Pesanan">
                                    <i class="material-icons leading-none">visibility</i>
                                </a>
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="7">
                            <div class="flex flex-col items-center justify-center py-12 text-center">
                                <div class="w-20 h-20 bg-slate-100 dark:bg-slate-700 rounded-full flex items-center justify-center mb-4 mx-auto">
                                    <i class="material-icons text-4xl text-slate-400">assignment_turned_in</i>
                                </div>
                                <h3 class="text-lg font-bold text-slate-700 dark:text-white">
                                    {{ $view === 'pending' ? 'Tidak ada pesanan baru' : 'Riwayat kosong' }}
                                </h3>
                                <p class="text-sm text-slate-500 mt-2 max-w-sm mx-auto">
                                    {{ $view === 'pending' 
                                        ? 'Semua pesanan klien telah diproses. Kerja bagus!' 
                                        : 'Belum ada riwayat pesanan klien yang diproses.' }}
                                </p>
                            </div>
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        {{-- Pagination --}}
        @if($clientOrders->hasPages())
        <div class="px-6 py-4 border-t border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-800">
            {{ $clientOrders->links('vendor.pagination.admin') }}
        </div>
        @endif
    </div>
</div>
@endsection
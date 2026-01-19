@extends('admin.layouts.app')

@section('title', 'Pesanan Penjualan (Sales Orders)')

@section('content')
<div class="flex flex-col gap-6">

    {{-- HEADER SECTION --}}
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div>
            <h1 class="page-title">Pesanan Penjualan</h1>
            <p class="page-subtitle">Kelola pesanan masuk dari tim sales internal.</p>
        </div>
        <div class="flex items-center gap-3">
            {{-- Tombol Sampah --}}
            <a href="{{ route('admin.sales-orders.trash') }}" class="btn btn-secondary text-slate-500 border-slate-200 hover:text-red-600 hover:border-red-200">
                <i class="material-icons text-lg">delete_outline</i>
                <span class="hidden sm:inline">Sampah</span>
            </a>
            
            {{-- Tombol Buat Baru --}}
            <a href="{{ route('admin.sales-orders.create') }}" class="btn btn-primary">
                <i class="material-icons text-lg">add</i>
                <span>Buat Pesanan</span>
            </a>
        </div>
    </div>

    {{-- FILTER SECTION --}}
    <div class="card p-4">
        <form method="GET" action="{{ route('admin.sales-orders.index') }}" class="grid grid-cols-1 md:grid-cols-12 gap-4">
            
            {{-- Search Bar --}}
            <div class="md:col-span-4 relative">
                <div class="absolute inset-y-0 left-0 flex items-center pl-3 pointer-events-none">
                    <i class="material-icons text-slate-400">search</i>
                </div>
                <input type="text" name="search" value="{{ request('search') }}" 
                    class="form-input pl-10" 
                    placeholder="Cari No. Order, Klien...">
            </div>

            {{-- Filter Tanggal (Bulan) --}}
            <div class="md:col-span-2">
                <select name="date" class="form-select" onchange="this.form.submit()">
                    <option value="">Semua Periode</option>
                    @foreach($uniqueDates as $ym => $label)
                        <option value="{{ $ym }}" {{ request('date') == $ym ? 'selected' : '' }}>
                            {{ $label }}
                        </option>
                    @endforeach
                </select>
            </div>

            {{-- Filter Status --}}
            <div class="md:col-span-2">
                <select name="status_filter" class="form-select" onchange="this.form.submit()">
                    <option value="">Semua Status</option>
                    <option value="pending" {{ request('status_filter') == 'pending' ? 'selected' : '' }}>Pending</option>
                    <option value="approved" {{ request('status_filter') == 'approved' ? 'selected' : '' }}>Approved</option>
                    <option value="invoiced" {{ request('status_filter') == 'invoiced' ? 'selected' : '' }}>Invoiced</option>
                    <option value="rejected" {{ request('status_filter') == 'rejected' ? 'selected' : '' }}>Rejected</option>
                </select>
            </div>

            {{-- Sorting --}}
            <div class="md:col-span-2">
                <select name="sort" class="form-select" onchange="this.form.submit()">
                    <option value="terbaru" {{ request('sort') == 'terbaru' ? 'selected' : '' }}>Terbaru</option>
                    <option value="terlama" {{ request('sort') == 'terlama' ? 'selected' : '' }}>Terlama</option>
                    <option value="klien_az" {{ request('sort') == 'klien_az' ? 'selected' : '' }}>Klien (A-Z)</option>
                </select>
            </div>

            {{-- Reset Button --}}
            <div class="md:col-span-2">
                <a href="{{ route('admin.sales-orders.index') }}" class="btn btn-secondary w-full justify-center">
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
                        <th>Sales</th>
                        <th class="text-right">Total</th>
                        <th class="text-center">Status</th>
                        <th class="text-right">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($orders as $order)
                    <tr class="group hover:bg-slate-50 dark:hover:bg-slate-800/50 transition-colors">
                        <td class="text-center text-slate-400 text-xs">
                            {{ ($orders->currentpage()-1) * $orders->perpage() + $loop->index + 1 }}
                        </td>
                        <td>
                            <div class="flex flex-col">
                                <span class="font-bold text-indigo-600 dark:text-indigo-400 group-hover:underline">
                                    <a href="{{ route('admin.sales-orders.show', $order->order_id) }}">
                                        {{ $order->order_number }}
                                    </a>
                                </span>
                                <span class="text-xs text-slate-500 flex items-center gap-1 mt-0.5">
                                    <i class="material-icons text-[12px]">calendar_today</i>
                                    {{ $order->order_date->format('d M Y') }}
                                </span>
                            </div>
                        </td>
                        <td>
                            <div class="font-medium text-slate-700 dark:text-slate-200">
                                {{ $order->client->client_name ?? 'Klien Terhapus' }}
                            </div>
                            @if($order->client && $order->client->person_in_charge)
                                <div class="text-[10px] text-slate-400 uppercase tracking-wide">
                                    PIC: {{ $order->client->person_in_charge }}
                                </div>
                            @endif
                        </td>
                        <td>
                            <div class="flex items-center gap-2">
                                <div class="w-6 h-6 rounded-full bg-slate-100 flex items-center justify-center text-[10px] font-bold text-slate-500 shrink-0">
                                    {{ substr($order->sales->full_name ?? 'S', 0, 1) }}
                                </div>
                                <span class="text-sm text-slate-600 dark:text-slate-400">
                                    {{ $order->sales->full_name ?? 'System' }}
                                </span>
                            </div>
                        </td>
                        <td class="text-right">
                            <span class="font-bold text-slate-700 dark:text-white block">
                                Rp {{ number_format($order->total_amount, 0, ',', '.') }}
                            </span>
                            <span class="text-[10px] text-slate-400">{{ $order->items_count ?? $order->items->count() }} item</span>
                        </td>
                        <td class="text-center">
                            @php
                                $statusClass = match($order->status) {
                                    'pending' => 'badge-warning',
                                    'approved' => 'badge-primary',
                                    'invoiced' => 'badge-success',
                                    'rejected' => 'badge-danger',
                                    'pending_review' => 'badge-warning',
                                    default => 'badge-secondary'
                                };
                                $statusLabel = match($order->status) {
                                    'pending' => 'Pending',
                                    'approved' => 'Disetujui',
                                    'invoiced' => 'Terbit Invoice',
                                    'rejected' => 'Ditolak/Batal',
                                    'pending_review' => 'Review Klien',
                                    default => ucfirst($order->status)
                                };
                            @endphp
                            <span class="badge {{ $statusClass }}">
                                {{ $statusLabel }}
                            </span>
                        </td>
                        <td class="text-right">
                            <div class="flex items-center justify-end gap-2">
                                
                                {{-- Tombol Lihat Detail --}}
                                <a href="{{ route('admin.sales-orders.show', $order->order_id) }}" 
                                   class="btn-action btn-action-view" 
                                   title="Lihat Detail">
                                    <i class="material-icons">visibility</i>
                                </a>

                                @if($order->status === 'pending')
                                    {{-- Tombol Edit --}}
                                    <a href="{{ route('admin.sales-orders.edit', $order->order_id) }}" 
                                       class="btn-action btn-action-edit" 
                                       title="Edit Pesanan">
                                        <i class="material-icons">edit</i>
                                    </a>

                                    {{-- Tombol Proses ke Invoice --}}
                                    <a href="{{ route('admin.invoices.createFromOrder', $order->order_id) }}" 
                                       class="btn-action bg-emerald-50 text-emerald-600 hover:bg-emerald-100 border-emerald-200" 
                                       title="Proses ke Invoice">
                                        <i class="material-icons">receipt_long</i>
                                    </a>

                                    {{-- Tombol Hapus --}}
                                    <form action="{{ route('admin.sales-orders.destroy', $order->order_id) }}" method="POST" onsubmit="return confirm('Pindahkan pesanan ini ke sampah?');">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="btn-action btn-action-delete" title="Hapus">
                                            <i class="material-icons">delete</i>
                                        </button>
                                    </form>
                                @endif

                                @if($order->status === 'invoiced')
                                    {{-- Link ke Invoice --}}
                                    @if($order->invoice_id)
                                    <a href="{{ route('admin.invoices.show', $order->invoice_id) }}" 
                                       class="btn-action text-indigo-600 bg-indigo-50 border-indigo-200 hover:bg-indigo-100" 
                                       title="Lihat Invoice">
                                        <i class="material-icons">description</i>
                                    </a>
                                    @endif
                                @endif
                            </div>
                        </td>
                    </tr>
                    @empty
                    {{-- EMPTY STATE: FIXED LAYOUT --}}
                    <tr>
                        <td colspan="7">
                            <div class="flex flex-col items-center justify-center py-12 text-center">
                                {{-- Icon Wrapper dengan mx-auto agar di tengah --}}
                                <div class="w-20 h-20 bg-slate-100 dark:bg-slate-700 rounded-full flex items-center justify-center mb-4 mx-auto">
                                    <i class="material-icons text-4xl text-slate-400">remove_shopping_cart</i>
                                </div>
                                <h3 class="text-lg font-bold text-slate-700 dark:text-white">Tidak ada pesanan ditemukan</h3>
                                <p class="text-sm text-slate-500 mt-2 max-w-sm mx-auto">
                                    Coba ubah filter pencarian atau mulai buat pesanan penjualan baru.
                                </p>
                                <a href="{{ route('admin.sales-orders.create') }}" class="btn btn-primary mt-6">
                                    <i class="material-icons text-sm mr-2">add</i>
                                    Buat Pesanan Baru
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
@extends('client.layouts.app')

@section('title', 'Riwayat Pesanan Sales')

@section('content')

    {{-- Header & Filters --}}
    <div class="card mb-6">
        <div class="card-body flex flex-col md:flex-row gap-4 items-center justify-between">
            
            {{-- Search --}}
            <form action="{{ route('client.sales-orders.index') }}" method="GET" class="w-full md:w-1/3">
                <div class="relative">
                    <div class="absolute inset-y-0 left-0 flex items-center pl-3 pointer-events-none">
                        <i class="material-icons text-slate-400">search</i>
                    </div>
                    <input type="text" name="search" value="{{ request('search') }}" 
                        class="block w-full p-2 pl-10 text-sm text-slate-900 border border-slate-300 rounded-lg bg-gray-50 focus:ring-blue-500 focus:border-blue-500 dark:bg-slate-700 dark:border-slate-600 dark:placeholder-slate-400 dark:text-white" 
                        placeholder="Cari No. Order...">
                </div>
            </form>

            {{-- Filter Group --}}
            <div class="flex gap-2 w-full md:w-auto overflow-x-auto">
                {{-- Date Filter --}}
                <div class="min-w-[140px]">
                    <select onchange="window.location.href=this.value" class="tom-select">
                        <option value="{{ route('client.sales-orders.index') }}">Semua Bulan</option>
                        @foreach($uniqueDates as $ym => $label)
                            <option value="{{ route('client.sales-orders.index', array_merge(request()->query(), ['date_filter' => $ym])) }}" 
                                {{ request('date_filter') == $ym ? 'selected' : '' }}>
                                {{ $label }}
                            </option>
                        @endforeach
                    </select>
                </div>

                {{-- Status Filter --}}
                <div class="min-w-[140px]">
                    <select onchange="window.location.href=this.value" class="tom-select">
                        <option value="{{ route('client.sales-orders.index', array_merge(request()->except('status_filter'))) }}">Semua Status</option>
                        <option value="{{ route('client.sales-orders.index', array_merge(request()->query(), ['status_filter' => 'pending'])) }}" {{ request('status_filter') == 'pending' ? 'selected' : '' }}>Pending</option>
                        <option value="{{ route('client.sales-orders.index', array_merge(request()->query(), ['status_filter' => 'approved'])) }}" {{ request('status_filter') == 'approved' ? 'selected' : '' }}>Disetujui</option>
                        <option value="{{ route('client.sales-orders.index', array_merge(request()->query(), ['status_filter' => 'invoiced'])) }}" {{ request('status_filter') == 'invoiced' ? 'selected' : '' }}>Ditagihkan</option>
                        <option value="{{ route('client.sales-orders.index', array_merge(request()->query(), ['status_filter' => 'rejected'])) }}" {{ request('status_filter') == 'rejected' ? 'selected' : '' }}>Ditolak</option>
                    </select>
                </div>
            </div>
        </div>
    </div>

    {{-- Table List --}}
    <div class="card">
        <div class="table-container">
            <table class="table-modern">
                <thead>
                    <tr>
                        <th>No. Order</th>
                        <th>Tanggal</th>
                        <th>Sales</th>
                        <th class="text-right">Total</th>
                        <th class="text-center">Status</th>
                        <th class="text-center">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($salesOrders as $order)
                        <tr>
                            <td class="font-bold text-slate-700 dark:text-white">
                                {{ $order->order_number }}
                            </td>
                            <td>
                                {{ $order->order_date->format('d M Y') }}
                            </td>
                            <td>
                                <div class="flex items-center gap-2">
                                    <div class="w-6 h-6 rounded-full bg-slate-200 flex items-center justify-center text-xs font-bold text-slate-600">
                                        {{ substr($order->sales->name ?? 'S', 0, 1) }}
                                    </div>
                                    <span>{{ $order->sales->name ?? 'Sales' }}</span>
                                </div>
                            </td>
                            <td class="text-right font-medium">
                                Rp {{ number_format($order->total_amount, 0, ',', '.') }}
                            </td>
                            <td class="text-center">
                                @php
                                    $badgeClass = match($order->status) {
                                        'pending' => 'badge-warning',
                                        'approved' => 'badge-primary', // Biru
                                        'invoiced' => 'badge-success', // Hijau (Final)
                                        'rejected' => 'badge-danger',
                                        default => 'badge-secondary',
                                    };
                                    
                                    $statusLabel = match($order->status) {
                                        'pending' => 'Menunggu',
                                        'approved' => 'Disetujui',
                                        'invoiced' => 'Sudah Faktur',
                                        'rejected' => 'Dibatalkan',
                                        'pending_review' => 'Review',
                                        default => $order->status
                                    };
                                @endphp
                                <span class="badge {{ $badgeClass }}">{{ $statusLabel }}</span>
                            </td>
                            <td class="text-center">
                                <a href="{{ route('client.sales-orders.show', $order->order_id) }}" class="btn btn-icon btn-sm btn-secondary" title="Lihat Detail">
                                    <i class="material-icons text-sm">visibility</i>
                                </a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="text-center py-12">
                                <div class="flex flex-col items-center justify-center">
                                    <div class="bg-slate-100 dark:bg-slate-800 p-4 rounded-full mb-3">
                                        <i class="material-icons text-slate-400 text-3xl">shopping_bag</i>
                                    </div>
                                    <p class="text-slate-500 dark:text-slate-400 font-medium">Belum ada pesanan dari Sales.</p>
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        
        {{-- Pagination --}}
        <div class="p-4 border-t border-slate-100 dark:border-slate-700/50">
            {{ $salesOrders->links() }}
        </div>
    </div>

@endsection
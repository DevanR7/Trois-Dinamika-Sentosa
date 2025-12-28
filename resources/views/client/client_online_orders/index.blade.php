@extends('client.layouts.app')

@section('title', 'Pesanan Saya')

@section('content')

    {{-- Header & Filters --}}
    <div class="card mb-6">
        <div class="card-body flex flex-col md:flex-row gap-4 items-center justify-between">
            
            {{-- Search Bar --}}
            <form action="{{ route('client.client-orders.index') }}" method="GET" class="w-full md:w-1/3">
                <div class="relative">
                    <div class="absolute inset-y-0 left-0 flex items-center pl-3 pointer-events-none">
                        <i class="material-icons text-slate-400">search</i>
                    </div>
                    <input type="text" name="search" value="{{ request('search') }}" 
                        class="block w-full p-2 pl-10 text-sm text-slate-900 border border-slate-300 rounded-lg bg-gray-50 focus:ring-indigo-500 focus:border-indigo-500 dark:bg-slate-700 dark:border-slate-600 dark:placeholder-slate-400 dark:text-white" 
                        placeholder="Cari No. Order...">
                </div>
            </form>

            {{-- Filter Group --}}
            <div class="flex gap-2 w-full md:w-auto overflow-x-auto">
                <a href="{{ route('client.client-orders.create') }}" class="btn btn-primary whitespace-nowrap">
                    <i class="material-icons text-sm">add</i> Buat Pesanan
                </a>

                {{-- Date Filter --}}
                <div class="min-w-[150px]">
                    <select onchange="window.location.href=this.value" class="tom-select">
                        <option value="{{ route('client.client-orders.index') }}">Semua Bulan</option>
                        @foreach($uniqueDates as $ym => $label)
                            <option value="{{ route('client.client-orders.index', array_merge(request()->query(), ['date_filter' => $ym])) }}" 
                                {{ request('date_filter') == $ym ? 'selected' : '' }}>
                                {{ $label }}
                            </option>
                        @endforeach
                    </select>
                </div>
            </div>
        </div>
    </div>

    {{-- Orders List --}}
    <div class="card">
        <div class="table-container">
            <table class="table-modern">
                <thead>
                    <tr>
                        <th>No. Order</th>
                        <th>Tanggal</th>
                        <th class="text-right">Total Est.</th>
                        <th class="text-center">Status</th>
                        <th class="text-center">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($myOrders as $order)
                        <tr>
                            <td class="font-bold text-slate-700 dark:text-white">
                                {{ $order->order_number }}
                            </td>
                            <td>
                                {{ $order->order_date->format('d M Y') }}
                            </td>
                            <td class="text-right font-medium">
                                Rp {{ number_format($order->total_amount, 0, ',', '.') }}
                            </td>
                            <td class="text-center">
                                @php
                                    $badgeClass = match($order->status) {
                                        'pending_review' => 'badge-warning', // Kuning (Menunggu Admin)
                                        'approved' => 'badge-primary',      // Biru (Diproses)
                                        'invoiced' => 'badge-success',      // Hijau (Sudah jadi tagihan)
                                        'rejected' => 'badge-danger',       // Merah (Ditolak)
                                        default => 'badge-secondary',
                                    };
                                    
                                    $label = match($order->status) {
                                        'pending_review' => 'Menunggu Review',
                                        'approved' => 'Diproses',
                                        'invoiced' => 'Selesai (Faktur)',
                                        'rejected' => 'Ditolak',
                                        default => $order->status
                                    };
                                @endphp
                                <span class="badge {{ $badgeClass }}">{{ $label }}</span>
                            </td>
                            <td class="text-center">
                                <a href="{{ route('client.client-orders.show', $order->order_id) }}" 
                                   class="btn btn-icon btn-sm btn-secondary" 
                                   title="Lihat Detail">
                                    <i class="material-icons text-sm">visibility</i>
                                </a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="text-center py-12">
                                <div class="flex flex-col items-center justify-center">
                                    <div class="bg-slate-100 dark:bg-slate-800 p-4 rounded-full mb-3">
                                        <i class="material-icons text-slate-400 text-3xl">add_shopping_cart</i>
                                    </div>
                                    <p class="text-slate-500 dark:text-slate-400 font-medium mb-2">Anda belum membuat pesanan.</p>
                                    <a href="{{ route('client.client-orders.create') }}" class="text-indigo-600 hover:underline text-sm">
                                        Buat pesanan pertama Anda sekarang
                                    </a>
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        {{-- Pagination --}}
        <div class="p-4 border-t border-slate-100 dark:border-slate-700/50">
            {{ $myOrders->links() }}
        </div>
    </div>

@endsection
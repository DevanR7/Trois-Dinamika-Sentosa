@extends('client.layouts.app')

@section('title', 'Riwayat Pesanan Sales')

@section('content')
<div class="space-y-6">
    
    {{-- Header --}}
    <div class="flex flex-col md:flex-row justify-between md:items-center gap-4">
        <div>
            <h2 class="text-2xl font-bold text-slate-800 dark:text-slate-100 tracking-tight">Riwayat Pesanan Sales</h2>
            <p class="text-sm text-slate-500 dark:text-slate-400 mt-1">Daftar pesanan yang dibuatkan oleh tim sales untuk Anda.</p>
        </div>
    </div>

    {{-- Alert Global --}}
    @if (session('success'))
        <div class="bg-emerald-50 text-emerald-700 p-4 rounded-lg border border-emerald-200 flex items-center gap-2">
            <i class="material-icons text-base">check_circle</i> {{ session('success') }}
        </div>
    @endif
    @if (session('error'))
        <div class="bg-red-50 text-red-700 p-4 rounded-lg border border-red-200 flex items-center gap-2">
            <i class="material-icons text-base">error</i> {{ session('error') }}
        </div>
    @endif

    {{-- FILTER SECTION --}}
    <div class="dashboard-card p-6">
        <form action="{{ route('client.sales-orders.index') }}" method="GET">
            <div class="grid grid-cols-1 md:grid-cols-12 gap-4 items-end">
                
                {{-- Search --}}
                <div class="md:col-span-3">
                    <label class="block text-xs font-bold text-slate-500 uppercase tracking-wide mb-2">Cari No. Pesanan</label>
                    <div class="relative">
                        <span class="absolute inset-y-0 left-0 flex items-center pl-3 text-slate-400">
                            <i class="material-icons text-[18px]">search</i>
                        </span>
                        <input type="text" name="search" class="form-input pl-10" placeholder="Contoh: SO/..." value="{{ request('search') }}">
                    </div>
                </div>

                {{-- Date Filter --}}
                <div class="md:col-span-3">
                    <label class="block text-xs font-bold text-slate-500 uppercase tracking-wide mb-2">Periode</label>
                    {{-- ✅ TAMBAHKAN CLASS 'select2-basic' DISINI --}}
                    <select name="date_filter" class="form-select select2-basic" data-placeholder="-- Semua Tanggal --">
                        <option value=""></option> {{-- Option kosong untuk placeholder Select2 --}}
                        @foreach($uniqueDates as $ym => $dateLabel)
                            <option value="{{ $ym }}" @selected(request('date_filter') == $ym)>{{ $dateLabel }}</option>
                        @endforeach
                    </select>
                </div>

                {{-- Status --}}
                <div class="md:col-span-2">
                    <label class="block text-xs font-bold text-slate-500 uppercase tracking-wide mb-2">Status</label>
                    {{-- ✅ TAMBAHKAN CLASS 'select2-basic' DISINI --}}
                    <select name="status_filter" class="form-select select2-basic" data-placeholder="-- Semua Status --">
                        <option value=""></option>
                        <option value="pending" @selected(request('status_filter') == 'pending')>Pending</option>
                        <option value="approved" @selected(request('status_filter') == 'approved')>Approved</option>
                        <option value="rejected" @selected(request('status_filter') == 'rejected')>Rejected</option>
                        <option value="invoiced" @selected(request('status_filter') == 'invoiced')>Invoiced</option>
                    </select>
                </div>

                {{-- Sort --}}
                <div class="md:col-span-2">
                    <label class="block text-xs font-bold text-slate-500 uppercase tracking-wide mb-2">Urutan</label>
                    {{-- ✅ TAMBAHKAN CLASS 'select2-basic' DISINI --}}
                    <select name="sort" class="form-select select2-basic" data-placeholder="Urutkan">
                        <option value="terbaru" @selected(request('sort', 'terbaru') == 'terbaru')>Terbaru</option>
                        <option value="terlama" @selected(request('sort') == 'terlama')>Terlama</option>
                    </select>
                </div>

                {{-- Buttons --}}
                <div class="md:col-span-2 flex gap-2">
                    <button type="submit" class="bg-slate-800 hover:bg-slate-700 text-white font-bold py-2.5 px-4 rounded-lg w-full transition shadow-md dark:bg-slate-700 dark:hover:bg-slate-600">
                        Filter
                    </button>
                    <a href="{{ route('client.sales-orders.index') }}" class="bg-white dark:bg-slate-800 border border-slate-300 dark:border-slate-600 text-slate-600 dark:text-slate-300 hover:bg-slate-50 dark:hover:bg-slate-700 font-bold py-2.5 px-4 rounded-lg w-full text-center transition">
                        Reset
                    </a>
                </div>
            </div>
        </form>
    </div>

    {{-- TABLE SECTION --}}
    <div class="dashboard-card overflow-hidden">
        <div class="overflow-x-auto">
            <table class="dashboard-table">
                <thead>
                    <tr>
                        <th>No. Pesanan</th>
                        <th>Tanggal</th>
                        <th class="text-right">Total</th>
                        <th class="text-center">Status</th>
                        <th class="text-center">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($salesOrders as $order)
                        <tr>
                            <td>
                                <span class="font-bold text-slate-700 dark:text-slate-200">{{ $order->order_number }}</span>
                            </td>
                            <td>
                                <div class="flex items-center gap-2 text-slate-600 dark:text-slate-400">
                                    <i class="material-icons text-[16px]">event</i>
                                    {{ $order->order_date->format('d M Y') }}
                                </div>
                            </td>
                            <td class="text-right font-bold text-slate-700 dark:text-slate-200">
                                Rp {{ number_format($order->total_amount, 0, ',', '.') }}
                            </td>
                            <td class="text-center">
                                @php
                                    $statusClass = [
                                        'pending' => 'status-pending',
                                        'approved' => 'status-approved',
                                        'rejected' => 'status-rejected',
                                        'invoiced' => 'status-completed',
                                    ];
                                    $label = [
                                        'pending' => 'Pending',
                                        'approved' => 'Disetujui',
                                        'rejected' => 'Ditolak',
                                        'invoiced' => 'Tagihan Terbit',
                                    ];
                                @endphp
                                <span class="status-badge {{ $statusClass[$order->status] ?? 'status-draft' }}">
                                    {{ $label[$order->status] ?? $order->status }}
                                </span>
                            </td>
                            <td class="text-center">
                                <a href="{{ route('client.sales-orders.show', $order->order_id) }}" 
                                   class="inline-flex items-center justify-center w-8 h-8 rounded-full bg-indigo-50 text-indigo-600 hover:bg-indigo-600 hover:text-white transition-all duration-200"
                                   title="Lihat Detail">
                                    <i class="material-icons text-[18px]">visibility</i>
                                </a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="py-12 text-center text-slate-500">
                                <div class="flex flex-col items-center">
                                    <div class="w-16 h-16 bg-slate-100 dark:bg-slate-700 rounded-full flex items-center justify-center mb-3">
                                        <i class="material-icons text-3xl text-slate-400">search_off</i>
                                    </div>
                                    <p class="font-medium">Tidak ada riwayat pesanan yang cocok.</p>
                                    <p class="text-xs mt-1">Coba ubah filter pencarian Anda.</p>
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        
        {{-- Pagination --}}
        <div class="px-6 py-4 border-t border-slate-100 dark:border-slate-700">
            {{ $salesOrders->appends(request()->query())->links() }}
        </div>
    </div>
</div>
@endsection
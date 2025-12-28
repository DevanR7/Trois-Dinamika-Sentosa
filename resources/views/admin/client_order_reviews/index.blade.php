@extends('admin.layouts.app')

@section('title', 'Review Pesanan Klien')

@section('content')
    <div class="flex flex-col gap-6">
        
        {{-- Header --}}
        <div class="page-header">
            <div>
                <h1 class="page-title">Review Pesanan Klien</h1>
                <p class="page-subtitle">Verifikasi pesanan yang dibuat oleh klien sebelum diproses menjadi invoice.</p>
            </div>
        </div>

        {{-- Tabs & Filters --}}
        <div class="card p-4">
            <div class="flex flex-col lg:flex-row justify-between gap-4 mb-4">
                
                {{-- View Switcher (Pending vs History) --}}
                <div class="flex p-1 bg-slate-100 dark:bg-slate-700/50 rounded-xl w-full lg:w-auto">
                    <a href="{{ route('admin.client-order-reviews.index', ['view' => 'pending']) }}" 
                       class="flex-1 lg:flex-none px-6 py-2 rounded-lg text-sm font-medium transition-all text-center
                       {{ $view === 'pending' ? 'bg-white dark:bg-slate-600 text-indigo-600 dark:text-white shadow-sm' : 'text-slate-500 hover:text-slate-700 dark:hover:text-slate-300' }}">
                        Menunggu Review
                        @if(\App\Models\Order::where('order_source', 'client')->where('status', 'pending_review')->count() > 0)
                            <span class="ml-2 bg-rose-500 text-white text-[10px] px-1.5 py-0.5 rounded-full">
                                {{ \App\Models\Order::where('order_source', 'client')->where('status', 'pending_review')->count() }}
                            </span>
                        @endif
                    </a>
                    <a href="{{ route('admin.client-order-reviews.index', ['view' => 'history']) }}" 
                       class="flex-1 lg:flex-none px-6 py-2 rounded-lg text-sm font-medium transition-all text-center
                       {{ $view === 'history' ? 'bg-white dark:bg-slate-600 text-indigo-600 dark:text-white shadow-sm' : 'text-slate-500 hover:text-slate-700 dark:hover:text-slate-300' }}">
                        Riwayat
                    </a>
                </div>

                {{-- Filter Tools --}}
                <form method="GET" class="flex flex-col sm:flex-row gap-3 w-full lg:w-auto">
                    <input type="hidden" name="view" value="{{ $view }}">
                    
                    {{-- Filter Tanggal --}}
                    <div class="w-full sm:w-48">
                        <select name="date_filter" class="tom-select" onchange="this.form.submit()">
                            <option value="">Semua Periode</option>
                            @foreach($uniqueDates as $ym => $label)
                                <option value="{{ $ym }}" {{ request('date_filter') == $ym ? 'selected' : '' }}>
                                    {{ $label }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    {{-- Search --}}
                    <div class="input-group w-full sm:w-64">
                        <span class="input-group-text bg-white dark:bg-slate-800"><i class="material-icons text-sm">search</i></span>
                        <input type="text" name="search" class="form-input" 
                               placeholder="Cari No. Order atau Klien..." 
                               value="{{ request('search') }}">
                    </div>
                </form>
            </div>
        </div>

        {{-- Table --}}
        <div class="card card-plain">
            <div class="table-container">
                <table class="table-modern">
                    <thead>
                        <tr>
                            <th>No. Pesanan</th>
                            <th>Klien</th>
                            <th>Tanggal</th>
                            <th>Total Nominal</th>
                            <th>Status</th>
                            <th class="text-right">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($clientOrders as $order)
                            <tr>
                                <td>
                                    <div class="font-bold text-slate-700 dark:text-white">
                                        {{ $order->order_number }}
                                    </div>
                                    @if($order->notes)
                                        <div class="text-[10px] text-muted truncate max-w-[150px]" title="{{ $order->notes }}">
                                            <i class="material-icons text-[10px] align-middle">sticky_note_2</i> {{ $order->notes }}
                                        </div>
                                    @endif
                                </td>
                                <td>
                                    <div class="font-medium text-slate-700 dark:text-slate-200">
                                        {{ $order->client->client_name }}
                                    </div>
                                    <div class="text-xs text-muted">{{ $order->client->person_in_charge ?? '-' }}</div>
                                </td>
                                <td>
                                    {{ $order->order_date->format('d/m/Y') }}
                                    <div class="text-[10px] text-muted">{{ $order->created_at->format('H:i') }}</div>
                                </td>
                                <td>
                                    <span class="font-bold text-slate-700 dark:text-slate-200">
                                        Rp {{ number_format($order->total_amount, 0, ',', '.') }}
                                    </span>
                                </td>
                                <td>
                                    @if($order->status == 'pending_review')
                                        <span class="badge badge-warning">Menunggu Review</span>
                                    @elseif($order->status == 'invoiced')
                                        <span class="badge badge-success">Disetujui (Invoiced)</span>
                                    @elseif($order->status == 'rejected')
                                        <span class="badge badge-danger">Ditolak</span>
                                    @else
                                        <span class="badge badge-secondary">{{ ucfirst($order->status) }}</span>
                                    @endif
                                </td>
                                <td class="text-right">
                                    <a href="{{ route('admin.client-order-reviews.show', $order->order_id) }}" 
                                       class="btn btn-sm {{ $order->status == 'pending_review' ? 'btn-primary' : 'btn-secondary' }}">
                                        @if($order->status == 'pending_review')
                                            Review
                                        @else
                                            Detail
                                        @endif
                                    </a>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="text-center py-8">
                                    <div class="flex flex-col items-center justify-center">
                                        <i class="material-icons text-4xl text-slate-300 mb-2">assignment_turned_in</i>
                                        <span class="text-muted">Tidak ada pesanan ditemukan.</span>
                                    </div>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            
            {{-- Pagination --}}
            <div class="px-4 py-3 border-t border-slate-200 dark:border-slate-700">
                {{ $clientOrders->links() }}
            </div>
        </div>
    </div>
@endsection
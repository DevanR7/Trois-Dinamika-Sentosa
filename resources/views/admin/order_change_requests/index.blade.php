@extends('admin.layouts.app')

@section('title', 'Permintaan Perubahan Order')

@section('content')
<div class="flex flex-col gap-6">

    {{-- HEADER SECTION --}}
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div>
            <h1 class="page-title">Permintaan Perubahan Order</h1>
            <p class="page-subtitle">Kelola pengajuan pembatalan atau revisi pesanan dari klien.</p>
        </div>
        
        {{-- Statistik Singkat (Pending) --}}
        <div class="flex items-center gap-2">
            <span class="px-3 py-1 rounded-lg bg-amber-50 text-amber-600 text-xs font-bold border border-amber-100 dark:bg-amber-900/30 dark:border-amber-800 dark:text-amber-400">
                Pending: {{ \App\Models\OrderChangeRequest::where('status', 'pending')->count() }}
            </span>
        </div>
    </div>

    {{-- FILTER & SEARCH CARD --}}
    <div class="card p-4">
        <form method="GET" action="{{ route('admin.order-change-requests.index') }}" class="grid grid-cols-1 md:grid-cols-12 gap-4">
            
            {{-- Search Bar --}}
            <div class="md:col-span-4 relative">
                <div class="absolute inset-y-0 left-0 flex items-center pl-3 pointer-events-none">
                    <i class="material-icons text-slate-400">search</i>
                </div>
                <input type="text" name="search" value="{{ request('search') }}" 
                    class="form-input pl-10" 
                    placeholder="Cari No. Request, Order, Klien...">
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

            {{-- Filter Tipe --}}
            <div class="md:col-span-3">
                <select name="type_filter" class="form-select" onchange="this.form.submit()">
                    <option value="">Semua Tipe Request</option>
                    <option value="cancel" {{ request('type_filter') == 'cancel' ? 'selected' : '' }}>Pembatalan (Cancel)</option>
                    <option value="modify" {{ request('type_filter') == 'modify' ? 'selected' : '' }}>Revisi Item (Modify)</option>
                </select>
            </div>

            {{-- Reset Button --}}
            <div class="md:col-span-2">
                <a href="{{ route('admin.order-change-requests.index') }}" class="btn btn-secondary w-full justify-center">
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
                        <th>No. Request</th>
                        <th>Pesanan Asal</th>
                        <th>Klien</th>
                        <th>Tipe</th>
                        <th>Tanggal</th>
                        <th class="text-center">Status</th>
                        <th class="text-right">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($changeRequests as $req)
                    <tr class="group hover:bg-slate-50 dark:hover:bg-slate-800/50 transition-colors">
                        <td class="text-center text-slate-400 text-xs">
                            {{ ($changeRequests->currentpage()-1) * $changeRequests->perpage() + $loop->index + 1 }}
                        </td>
                        <td>
                            <a href="{{ route('admin.order-change-requests.show', $req->request_id) }}" class="font-bold text-indigo-600 dark:text-indigo-400 hover:underline">
                                #{{ $req->request_id }}
                            </a>
                        </td>
                        <td>
                            <a href="{{ route('admin.sales-orders.show', $req->order_id) }}" class="flex items-center gap-1 text-sm text-slate-600 hover:text-indigo-600 font-medium font-mono">
                                <i class="material-icons text-[14px] text-slate-400">receipt</i> 
                                {{ $req->order->order_number }}
                            </a>
                        </td>
                        <td>
                            <div class="font-medium text-slate-700 dark:text-slate-200">
                                {{ $req->client->client_name ?? '-' }}
                            </div>
                        </td>
                        <td>
                            @if($req->request_type == 'cancel')
                                <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-xs font-bold bg-rose-100 text-rose-700 dark:bg-rose-900/30 dark:text-rose-400">
                                    <i class="material-icons text-[14px]">cancel</i> Batal
                                </span>
                            @else
                                <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-xs font-bold bg-blue-100 text-blue-700 dark:bg-blue-900/30 dark:text-blue-400">
                                    <i class="material-icons text-[14px]">edit</i> Revisi
                                </span>
                            @endif
                        </td>
                        <td>
                            <span class="text-sm text-slate-600 dark:text-slate-400">
                                {{ $req->created_at->format('d M Y') }}
                            </span>
                            <span class="text-[10px] text-slate-400 block">
                                {{ $req->created_at->format('H:i') }}
                            </span>
                        </td>
                        <td class="text-center">
                            @php
                                $statusClass = match($req->status) {
                                    'pending' => 'badge-warning animate-pulse-slow',
                                    'approved' => 'badge-success',
                                    'rejected' => 'badge-danger',
                                    default => 'badge-secondary'
                                };
                                $statusLabel = match($req->status) {
                                    'pending' => 'Menunggu',
                                    'approved' => 'Disetujui',
                                    'rejected' => 'Ditolak',
                                    default => ucfirst($req->status)
                                };
                            @endphp
                            <span class="badge {{ $statusClass }}">
                                {{ $statusLabel }}
                            </span>
                        </td>
                        <td class="text-right">
                            <div class="flex items-center justify-end gap-2">
                                <a href="{{ route('admin.order-change-requests.show', $req->request_id) }}" 
                                   class="btn-action btn-action-view {{ $req->status == 'pending' ? 'bg-indigo-50 border-indigo-200 text-indigo-600 hover:bg-indigo-100' : '' }}" 
                                   title="{{ $req->status == 'pending' ? 'Proses Permintaan' : 'Lihat Detail' }}">
                                    @if($req->status == 'pending')
                                        <i class="material-icons text-sm leading-none">rate_review</i>
                                    @else
                                        <i class="material-icons text-sm leading-none">visibility</i>
                                    @endif
                                </a>
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="8">
                            <div class="flex flex-col items-center justify-center py-12 text-center">
                                <div class="w-20 h-20 bg-slate-100 dark:bg-slate-700 rounded-full flex items-center justify-center mb-4 mx-auto">
                                    <i class="material-icons text-4xl text-slate-400">inbox</i>
                                </div>
                                <h3 class="text-lg font-bold text-slate-700 dark:text-white">Tidak ada permintaan</h3>
                                <p class="text-sm text-slate-500 mt-2 max-w-sm mx-auto">
                                    Belum ada permintaan perubahan pesanan yang masuk sesuai filter saat ini.
                                </p>
                            </div>
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        {{-- Pagination --}}
        @if($changeRequests->hasPages())
        <div class="px-6 py-4 border-t border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-800">
            {{ $changeRequests->links('vendor.pagination.admin') }}
        </div>
        @endif
    </div>
</div>
@endsection
@extends('layouts.app')

@section('content')
<div class="container-fluid py-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2 class="fw-bold">Review Pesanan Online Klien</h2>
    </div>

    {{-- Notifikasi --}}
    @if (session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif
    @if (session('error'))
        <div class="alert alert-danger">{{ session('error') }}</div>
    @endif

    {{-- TAB NAVIGASI (Pending / History) --}}
    <ul class="nav nav-tabs mb-3">
        <li class="nav-item">
            <a class="nav-link {{ $view == 'pending' ? 'active fw-semibold' : '' }}" href="{{ route('client-order-reviews.index', ['view' => 'pending']) }}">
                Menunggu Review
                @php $pendingCount = \App\Models\Order::where('order_source', 'client')->where('status', 'pending_review')->count(); @endphp
                @if($pendingCount > 0)
                    <span class="badge bg-danger rounded-pill ms-1">{{ $pendingCount }}</span>
                @endif
            </a>
        </li>
        <li class="nav-item">
            <a class="nav-link {{ $view == 'history' ? 'active fw-semibold' : '' }}" href="{{ route('client-order-reviews.index', ['view' => 'history']) }}">
                Riwayat (Diterima/Ditolak)
            </a>
        </li>
    </ul>

    {{-- FORM FILTER --}}
    <div class="card shadow-sm mb-4">
        <div class="card-body">
            <form action="{{ route('client-order-reviews.index') }}" method="GET" class="row g-3 align-items-end">
                <input type="hidden" name="view" value="{{ $view }}"> {{-- Pertahankan view saat filter --}}

                <div class="col-md-4">
                    <label for="search" class="form-label">Cari</label>
                    <input type="text" name="search" id="search" class="form-control form-control-sm" placeholder="No. Order/Klien..." value="{{ request('search') }}">
                </div>

                <div class="col-md-3">
                    <label for="date_filter" class="form-label">Bulan/Tahun</label>
                    <select name="date_filter" id="date_filter" class="form-select form-select-sm">
                        <option value="">-- Semua Tanggal --</option>
                        @foreach($uniqueDates as $ym => $dateLabel)
                            <option value="{{ $ym }}" @selected(request('date_filter') == $ym)>{{ $dateLabel }}</option>
                        @endforeach
                    </select>
                </div>

                {{-- Filter Status (Hanya muncul di view History) --}}
                @if($view == 'history')
                <div class="col-md-2">
                    <label for="status_filter" class="form-label">Status</label>
                    <select name="status_filter" id="status_filter" class="form-select form-select-sm">
                        <option value="">-- Semua Status --</option>
                        <option value="invoiced" @selected(request('status_filter') == 'invoiced')>Disetujui (Invoiced)</option>
                        <option value="approved" @selected(request('status_filter') == 'approved')>Disetujui (Lama)</option>
                        <option value="rejected" @selected(request('status_filter') == 'rejected')>Ditolak</option>
                    </select>
                </div>
                @endif

                <div class="col-md-2">
                    <label for="sort" class="form-label">Urutkan</label>
                    <select name="sort" id="sort" class="form-select form-select-sm">
                        <option value="terbaru" @selected(request('sort', 'terbaru') == 'terbaru')>Terbaru</option>
                        <option value="terlama" @selected(request('sort') == 'terlama')>Terlama</option>
                    </select>
                </div>

                <div class="col-md-3 d-flex gap-2">
                    <button type="submit" class="btn btn-dark btn-sm w-100">Filter</button>
                    <a href="{{ route('client-order-reviews.index', ['view' => $view]) }}" class="btn btn-outline-secondary btn-sm w-100" title="Reset">Reset</a>
                </div>
            </form>
        </div>
    </div>
    
    <div class="card shadow-sm border-0">
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-hover align-middle">
                    <thead class="table-light">
                        <tr>
                            <th scope="col">#</th>
                            <th scope="col">Nomor Pesanan</th>
                            <th scope="col">Klien</th>
                            <th scope="col">Tanggal Pesan</th>
                            <th scope="col" class="text-end">Jumlah</th>
                            <th scope="col" class="text-center">Status</th>
                            <th scope="col" class="text-center">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($clientOrders as $order) {{-- Ganti var ke $clientOrders --}}
                            <tr>
                                <th scope="row">{{ $loop->iteration + $clientOrders->firstItem() - 1 }}</th>
                                <td>
                                    {{-- Link tetap ke 'show' --}}
                                    <a href="{{ route('client-order-reviews.show', $order->order_id) }}" class="fw-semibold">{{ $order->order_number }}</a>
                                </td>
                                <td>{{ $order->client->client_name ?? "N/A" }}</td>
                                <td>{{ $order->order_date->format("d M Y") }}</td>
                                <td class="text-end">Rp {{ number_format($order->total_amount, 0, ",", ".") }}</td>
                                <td class="text-center">
                                    {{-- Badge dinamis --}}
                                    @php
                                        $statusClass = [
                                            'pending_review' => 'bg-info text-dark',
                                            'approved' => 'bg-success', // Ini status lama, mungkin
                                            'rejected' => 'bg-danger',
                                            'invoiced' => 'bg-success',
                                        ];
                                    @endphp
                                    <span class="badge {{ $statusClass[$order->status] ?? 'bg-secondary' }}">
                                        {{ Str::title(str_replace('_', ' ', $order->status)) }}
                                    </span>
                                </td>
                                <td class="text-center">
                                    <a href="{{ route('client-order-reviews.show', $order->order_id) }}" class="btn btn-sm {{ $view == 'pending' ? 'btn-outline-primary' : 'btn-outline-info' }}" title="{{ $view == 'pending' ? 'Review Detail' : 'Lihat Detail' }}">
                                        <i class="bi bi-{{ $view == 'pending' ? 'search' : 'eye' }}"></i> {{ $view == 'pending' ? 'Review' : 'Detail' }}
                                    </a>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="text-center py-4 text-muted">
                                    @if($view == 'pending')
                                        Tidak ada pesanan online klien yang menunggu review.
                                    @else
                                        Tidak ada riwayat pesanan online klien yang cocok dengan filter Anda.
                                    @endif
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    {{-- Pagination --}}
    <div class="mt-4 d-flex justify-content-center">
        {{ $clientOrders->appends(request()->query())->links() }}
    </div>
</div>
@endsection
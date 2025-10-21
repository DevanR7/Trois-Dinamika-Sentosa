@extends('layouts.client')

@section('content')
    <h2 class="fw-bold mb-4">Riwayat Pesanan Online Saya</h2>

    {{-- Notifikasi --}}
    @if (session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif
    @if (session('error'))
        <div class="alert alert-danger">{{ session('error') }}</div>
    @endif

    {{-- ✅ FORM FILTER BARU --}}
    <div class="card shadow-sm mb-4">
        <div class="card-body">
            <form action="{{ route('client.client-orders.index') }}" method="GET" class="row g-3 align-items-end">
                <div class="col-md-3">
                    <label for="search" class="form-label">Cari No. Pesanan</label>
                    <input type="text" name="search" id="search" class="form-control form-control-sm" placeholder="Contoh: CO/..." value="{{ request('search') }}">
                </div>
                <div class="col-md-3">
                    <label for="date_filter" class="form-label">Filter Tanggal Pesan</label>
                    <select name="date_filter" id="date_filter" class="form-select form-select-sm">
                        <option value="">-- Semua Tanggal --</option>
                        @foreach($uniqueDates as $ym => $dateLabel)
                            <option value="{{ $ym }}" @selected(request('date_filter') == $ym)>{{ $dateLabel }}</option>
                        @endforeach
                    </select>
                </div>
                 <div class="col-md-2">
                    <label for="status_filter" class="form-label">Status</label>
                    <select name="status_filter" id="status_filter" class="form-select form-select-sm">
                        <option value="">-- Semua Status --</option>
                        <option value="pending_review" @selected(request('status_filter') == 'pending_review')>Pending Review</option>
                        <option value="approved" @selected(request('status_filter') == 'approved')>Approved</option>
                        <option value="rejected" @selected(request('status_filter') == 'rejected')>Rejected</option>
                        <option value="invoiced" @selected(request('status_filter') == 'invoiced')>Invoiced</option>
                    </select>
                </div>
                <div class="col-md-2">
                    <label for="sort" class="form-label">Urutkan</label>
                    <select name="sort" id="sort" class="form-select form-select-sm">
                        <option value="terbaru" @selected(request('sort', 'terbaru') == 'terbaru')>Terbaru</option>
                        <option value="terlama" @selected(request('sort') == 'terlama')>Terlama</option>
                    </select>
                </div>
                <div class="col-md-2 d-flex gap-2">
                    <button type="submit" class="btn btn-dark btn-sm w-100">Filter</button>
                    <a href="{{ route('client.client-orders.index') }}" class="btn btn-outline-secondary btn-sm w-100">Reset</a>
                </div>
            </form>
        </div>
    </div>
    {{-- ======================== --}}

    <div class="card shadow-sm">
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-hover align-middle">
                    <thead class="table-light">
                        <tr>
                            <th>No. Pesanan</th>
                            <th>Tanggal</th>
                            <th class="text-end">Total</th>
                            <th class="text-center">Status</th>
                            <th class="text-center">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($myOrders as $order)
                            <tr>
                                <td>{{ $order->order_number }}</td>
                                <td>{{ $order->order_date->format('d M Y') }}</td>
                                <td class="text-end">Rp {{ number_format($order->total_amount, 0, ',', '.') }}</td>
                                <td class="text-center">
                                    @php
                                        $statusClass = [
                                            'pending_review' => 'bg-info text-dark',
                                            'pending' => 'bg-secondary',
                                            'approved' => 'bg-primary',
                                            'rejected' => 'bg-danger',
                                            'invoiced' => 'bg-success',
                                        ];
                                    @endphp
                                    <span class="badge {{ $statusClass[$order->status] ?? 'bg-light text-dark' }}">
                                        {{ Str::title(str_replace('_', ' ', $order->status)) }}
                                    </span>
                                </td>
                                <td class="text-center">
                                    <a href="{{ route('client.client-orders.show', $order->order_id) }}" class="btn btn-sm btn-outline-primary">
                                        <i class="bi bi-eye"></i> Detail
                                    </a>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="text-center py-4">Tidak ada riwayat pesanan online yang cocok dengan filter Anda.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            <div class="mt-3 d-flex justify-content-center">
                {{-- Tambahkan appends agar filter tetap ada saat ganti halaman --}}
                {{ $myOrders->appends(request()->query())->links() }}
            </div>
        </div>
    </div>
@endsection
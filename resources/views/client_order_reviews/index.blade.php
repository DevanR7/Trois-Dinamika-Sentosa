@extends('layouts.app') {{-- Sesuaikan dengan layout admin Anda --}}

@section('content')
<div class="container-fluid py-4">
    {{-- Header Halaman --}}
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2 class="fw-bold">Review Pesanan Online Klien</h2>
        {{-- Tombol aksi lain jika perlu --}}
    </div>

    {{-- Notifikasi --}}
    @if (session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif
    @if (session('error'))
        <div class="alert alert-danger">{{ session('error') }}</div>
    @endif

    {{-- Filter Search (Opsional) --}}
    <div class="card shadow-sm mb-4">
        <div class="card-body">
            <form action="{{ route('client-order-reviews.index') }}" method="GET" class="row g-3 align-items-center">
                <div class="col-md-6">
                    <input type="text" name="search" class="form-control form-control-sm" placeholder="Cari No. Pesanan / Nama Klien..." value="{{ request('search') }}">
                </div>
                {{-- Tambahkan filter tanggal jika perlu --}}
                <div class="col-md-2">
                    <button type="submit" class="btn btn-dark btn-sm w-100">Cari</button>
                </div>
                 <div class="col-md-2">
                    <a href="{{ route('client-order-reviews.index') }}" class="btn btn-outline-secondary btn-sm w-100">Reset</a>
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
                        @forelse ($pendingClientOrders as $order)
                            <tr>
                                <th scope="row">{{ $loop->iteration + $pendingClientOrders->firstItem() - 1 }}</th>
                                <td>
                                    <a href="{{ route('client-order-reviews.show', $order->order_id) }}" class="fw-semibold">{{ $order->order_number }}</a>
                                </td>
                                <td>{{ $order->client->client_name ?? "N/A" }}</td>
                                <td>{{ $order->order_date->format("d M Y") }}</td>
                                <td class="text-end">Rp {{ number_format($order->total_amount, 0, ",", ".") }}</td>
                                <td class="text-center">
                                    <span class="badge bg-info text-dark">{{ Str::title(str_replace('_', ' ', $order->status)) }}</span>
                                </td>
                                <td class="text-center">
                                    <a href="{{ route('client-order-reviews.show', $order->order_id) }}" class="btn btn-sm btn-outline-primary" title="Review Detail">
                                        <i class="bi bi-search"></i> Review
                                    </a>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="text-center py-4 text-muted">
                                    Tidak ada pesanan online klien yang menunggu review.
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
        {{ $pendingClientOrders->appends(request()->query())->links() }}
    </div>
</div>
@endsection
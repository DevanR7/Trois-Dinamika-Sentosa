@extends('layouts.app')

@section('content')
<div class="container-fluid py-4">
    {{-- Header Halaman --}}
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2 class="fw-bold">Review Permintaan Perubahan Pesanan</h2>
        {{-- Tombol Tambah (jika diperlukan) bisa diletakkan di sini --}}
    </div>

    {{-- Notifikasi Sukses/Error --}}
    @if (session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif
    @if (session('error'))
        <div class="alert alert-danger">{{ session('error') }}</div>
    @endif

    {{-- Filter (Opsional) --}}
    {{-- <div class="card shadow-sm mb-4">
        <div class="card-body">
            <form action="{{ route('order-change-requests.index') }}" method="GET" class="row g-3 align-items-center">
                <div class="col-md-4">
                    <input type="text" name="search" class="form-control" placeholder="Cari No. Order / Klien..." value="{{ request('search') }}">
                </div>
                Add more filters if needed (date range, type)
                <div class="col-md-2">
                    <button type="submit" class="btn btn-dark w-100">Filter</button>
                </div>
            </form>
        </div>
    </div> --}}

    <div class="card shadow-sm border-0">
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-hover align-middle">
                    <thead class="table-light">
                        <tr>
                            <th scope="col">#</th>
                            <th scope="col">No. Request</th>
                            <th scope="col">No. Pesanan</th>
                            <th scope="col">Klien</th>
                            <th scope="col">Tipe Request</th>
                            <th scope="col">Tgl Diajukan</th>
                            <th scope="col" class="text-center">Status</th>
                            <th scope="col" class="text-center">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($changeRequests as $request)
                            <tr>
                                <th scope="row">{{ $loop->iteration + $changeRequests->firstItem() - 1 }}</th>
                                <td>
                                    {{-- Link ke halaman detail request --}}
                                    <a href="{{ route('order-change-requests.show', $request->request_id) }}">REQ-{{ str_pad($request->request_id, 5, '0', STR_PAD_LEFT) }}</a>
                                </td>
                                <td>
                                    {{-- Link ke halaman detail order asli --}}
                                    @if($request->order)
                                        <a href="{{ route('sales-orders.show', $request->order->order_id) }}">{{ $request->order->order_number }}</a>
                                    @else
                                        <span class="text-muted">Order Dihapus</span>
                                    @endif
                                </td>
                                <td>{{ $request->client->client_name ?? 'Klien Dihapus' }}</td>
                                <td>{{ $request->request_type == 'cancel' ? 'Pembatalan' : 'Modifikasi Item' }}</td>
                                <td>{{ $request->created_at->format('d M Y H:i') }}</td>
                                <td class="text-center">
                                    <span class="badge bg-warning text-dark">{{ Str::title($request->status) }}</span>
                                </td>
                                <td class="text-center">
                                    <a href="{{ route('order-change-requests.show', $request->request_id) }}" class="btn btn-sm btn-outline-primary" title="Lihat & Proses">
                                        <i class="bi bi-search"></i> Review
                                    </a>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="8" class="text-center py-4 text-muted">
                                    Tidak ada permintaan perubahan yang menunggu diproses.
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
        {{ $changeRequests->links() }}
    </div>
</div>
@endsection
@extends('layouts.client')

@section('content')
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        {{-- Link kembali ke index pesanan online --}}
        <a href="{{ route('client.client-orders.index') }}" class="btn btn-outline-secondary">
            <i class="bi bi-arrow-left"></i> Kembali ke Daftar Pesanan Online
        </a>
        <h2 class="fw-bold mb-0">Detail Pesanan Online: {{ $order->order_number }}</h2>
    </div>

    {{-- Notifikasi --}}
    @if (session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif
    @if (session('error'))
        <div class="alert alert-danger">{{ session('error') }}</div>
    @endif

    <div class="card shadow-sm">
        <div class="card-body p-4">
            <div class="row mb-4">
                <div class="col-md-6">
                    {{-- Klien tidak perlu lihat nama klien lagi --}}
                    {{-- <strong>Klien:</strong> {{ $order->client->client_name }} <br> --}}
                    {{-- Sales tidak relevan di sini --}}
                    {{-- <strong>Sales:</strong> {{ $order->sales->full_name ?? 'N/A' }} --}}
                </div>
                <div class="col-md-6 text-md-end">
                    <strong>Tanggal Pesanan:</strong> {{ $order->order_date->format('d M Y') }} <br>
                    <strong>Status:</strong>
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
                </div>
            </div>
            <hr>
            <h5 class="fw-semibold mt-4">Rincian Item Pesanan</h5>
            <table class="table table-bordered">
                <thead class="table-light">
                    <tr>
                        <th>#</th>
                        <th>Produk</th>
                        <th class="text-center">Kuantitas</th>
                        <th class="text-end">Harga Satuan</th>
                        <th class="text-end">Subtotal</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($order->items as $item)
                    <tr>
                        <td>{{ $loop->iteration }}</td>
                        <td>{{ $item->product->product_name ?? 'Produk Dihapus' }}</td>
                        <td class="text-center">{{ $item->quantity }}</td>
                        <td class="text-end">Rp {{ number_format($item->price_per_unit, 0, ',', '.') }}</td>
                        <td class="text-end">Rp {{ number_format($item->subtotal, 0, ',', '.') }}</td>
                    </tr>
                    @endforeach
                </tbody>
                <tfoot>
                    <tr class="table-light">
                        <td colspan="4" class="text-end fw-bold fs-5">Total Pesanan</td>
                        <td class="text-end fw-bold fs-5">Rp {{ number_format($order->total_amount, 0, ',', '.') }}</td>
                    </tr>
                </tfoot>
            </table>

            {{-- Tampilkan Catatan Jika Ada --}}
             @if($order->notes)
            <div class="mt-4">
                <h6 class="fw-semibold">Catatan Anda:</h6>
                <p class="text-muted fst-italic bg-light p-3 rounded">{{ $order->notes }}</p>
            </div>
            @endif
        </div>
    </div>
</div>
@endsection
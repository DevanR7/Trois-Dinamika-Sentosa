@extends('layouts.client')

@section('content')
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <a href="{{ route('client.sales-orders.index') }}" class="btn btn-outline-secondary">
            <i class="bi bi-arrow-left"></i> Kembali ke Daftar
        </a>
        <h2 class="fw-bold mb-0">Detail Pesanan: {{ $salesOrder->order_number }}</h2>
    </div>

    <div class="card shadow-sm">
        <div class="card-body p-4">
            <div class="row mb-4">
                <div class="col-md-6">
                    <strong>Klien:</strong> {{ $salesOrder->client->client_name }} <br>
                    <strong>Sales:</strong> {{ $salesOrder->sales->full_name ?? 'N/A' }}
                </div>
                <div class="col-md-6 text-md-end">
                    <strong>Tanggal Pesanan:</strong> {{ $salesOrder->order_date->format('d M Y') }} <br>
                    <strong>Status:</strong> <span class="badge bg-secondary">{{ Str::title($salesOrder->status) }}</span>
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
                    @foreach($salesOrder->items as $item)
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
                        <td class="text-end fw-bold fs-5">Rp {{ number_format($salesOrder->total_amount, 0, ',', '.') }}</td>
                    </tr>
                </tfoot>
            </table>
        </div>
    </div>
</div>
@endsection
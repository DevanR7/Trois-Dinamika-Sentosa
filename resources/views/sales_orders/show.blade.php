@extends('layouts.app')

@section('content')
<div class="container py-4">
    {{-- HEADER HALAMAN --}}
    <div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
        <h2 class="fw-bold mb-0">Detail Pesanan: {{ $salesOrder->order_number }}</h2>
        
        <div class="d-flex flex-wrap justify-content-end gap-2">
            <a href="{{ route('sales-orders.index') }}" class="btn btn-outline-secondary">
                <i class="bi bi-arrow-left me-1"></i> Kembali
            </a>

            {{-- Tombol untuk membuat Invoice dari Sales Order ini --}}
            @if($salesOrder->status !== 'invoiced' && $salesOrder->status !== 'rejected')
                <a href="{{ route('invoices.createFromOrder', $salesOrder->order_id) }}" class="btn btn-primary">
                    <i class="bi bi-receipt-cutoff me-1"></i> Buat Invoice
                </a>
            @endif
            
            {{-- Tombol aksi lainnya jika diperlukan --}}
            {{-- <a href="{{ route('sales-orders.edit', $salesOrder->order_id) }}" class="btn btn-secondary">Edit</a> --}}
        </div>
    </div>

    {{-- KARTU DETAIL --}}
    <div class="card shadow-sm border-0">
        <div class="card-body p-4">
            {{-- Info Klien & Status --}}
            <div class="row mb-4">
                <div class="col-md-6">
                    <h5 class="fw-semibold">Klien:</h5>
                    <p class="mb-0">{{ $salesOrder->client->client_name }}</p>
                    <p class="text-muted">{{ $salesOrder->client->address ?? 'Alamat tidak tersedia' }}</p>
                </div>
                <div class="col-md-6 text-md-end">
                    <p class="mb-1"><strong>Tanggal Pesanan:</strong> {{ optional($salesOrder->order_date)->format('d F Y') }}</p>
                    <p class="mb-1"><strong>Sales:</strong> {{ $salesOrder->sales->full_name ?? 'N/A' }} ({{ $salesOrder->sales->sales_code ?? '' }})</p>
                    <p class="mb-1"><strong>Status:</strong>
                        @if($salesOrder->status == 'invoiced')
                            <span class="badge bg-success">Sudah Dibuat Invoice</span>
                        @elseif($salesOrder->status == 'rejected')
                            <span class="badge bg-danger">Ditolak</span>
                        @else
                            <span class="badge bg-secondary">{{ Str::title($salesOrder->status) }}</span>
                        @endif
                    </p>
                </div>
            </div>
            <hr>

            {{-- Tabel Rincian Item --}}
            <h5 class="fw-semibold mt-4">Rincian Item Dipesan</h5>
            <div class="table-responsive">
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
                        @forelse($salesOrder->items as $item)
                        <tr>
                            <td>{{ $loop->iteration }}</td>
                            <td>{{ $item->product->product_name ?? 'Produk Dihapus' }}</td>
                            <td class="text-center">{{ $item->quantity }} {{ $item->product->unit->name ?? '' }}</td>
                            <td class="text-end">Rp {{ number_format($item->price_per_unit, 0, ',', '.') }}</td>
                            <td class="text-end">Rp {{ number_format($item->subtotal, 0, ',', '.') }}</td>
                        </tr>
                        @empty
                        <tr><td colspan="5" class="text-center">Tidak ada item dalam pesanan ini.</td></tr>
                        @endforelse
                    </tbody>
                    <tfoot>
                        <tr class="table-light">
                            <td colspan="4" class="text-end fw-bold fs-5">Total Pesanan</td>
                            <td class="text-end fw-bold fs-5">Rp {{ number_format($salesOrder->total_amount, 0, ',', '.') }}</td>
                        </tr>
                    </tfoot>
                </table>
            </div>

            @if($salesOrder->notes)
            <div class="mt-4">
                <h6 class="fw-semibold">Catatan:</h6>
                <p class="text-muted fst-italic">{{ $salesOrder->notes }}</p>
            </div>
            @endif
        </div>
    </div>
</div>
@endsection
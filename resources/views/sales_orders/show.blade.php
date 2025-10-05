@extends('layouts.app')

@section('content')
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2 class="fw-bold">Detail Pesanan Pembelian: {{ $purchaseOrder->po_number ?? 'PO-'.$purchaseOrder->po_id }}</h2>
        <div>
            @if(in_array($purchaseOrder->status, ['draft', 'ordered']))
                <form action="{{ route('purchase-orders.receive', $purchaseOrder->po_id) }}" method="POST">
                    @csrf
                    <button type="submit" class="btn btn-success">
                        <i class="bi bi-check-circle me-2"></i>Tandai Telah Diterima & Tambah Stok
                    </button>
                </form>
            @endif
        </div>
    </div>

    <div class="card shadow-sm">
        <div class="card-body p-4">
            <div class="row mb-4">
                <div class="col-md-6">
                    <strong>Supplier:</strong> {{ $purchaseOrder->supplier->supplier_name }} <br>
                    <strong>Dipesan Oleh:</strong> {{ $purchaseOrder->requester->full_name ?? 'Pembelian Umum' }}
                </div>
                <div class="col-md-6 text-md-end">
                    <strong>Tanggal Pesanan:</strong> {{ $purchaseOrder->order_date->format('d M Y') }} <br>
                    <strong>Status:</strong>
                    @if($purchaseOrder->status == 'completed')
                        <span class="badge bg-success">Selesai</span>
                    @elseif($purchaseOrder->status == 'cancelled')
                        <span class="badge bg-danger">Dibatalkan</span>
                    @else
                        <span class="badge bg-secondary">{{ Str::title($purchaseOrder->status) }}</span>
                    @endif
                </div>
            </div>
            <hr>
            <h5 class="fw-semibold mt-4">Rincian Item Dipesan</h5>
            <div class="table-responsive">
                <table class="table table-bordered">
                    <thead class="table-light">
                        <tr>
                            <th>#</th>
                            <th>Produk</th>
                            <th class="text-center">Kuantitas</th>
                            <th class="text-end">Harga Beli Final</th>
                            <th class="text-end">Subtotal</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($purchaseOrder->items as $item)
                        <tr>
                            <td>{{ $loop->iteration }}</td>
                            <td>
                                {{ $item->product->product_name ?? 'Produk Dihapus' }}
                                {{-- Tampilkan rincian diskon --}}
                                @if($item->discounts->isNotEmpty())
                                <small class="d-block text-muted">
                                    Diskon: {{ $item->discounts->pluck('percentage')->join('%, ') }}%
                                </small>
                                @endif
                            </td>
                            <td class="text-center">{{ $item->quantity }} {{ $item->product->unit->name ?? '' }}</td>
                            <td class="text-end">Rp {{ number_format($item->price_per_unit, 0, ',', '.') }}</td>
                            <td class="text-end">Rp {{ number_format($item->subtotal, 0, ',', '.') }}</td>
                        </tr>
                        @endforeach
                    </tbody>
                    <tfoot>
                        <tr class="table-light">
                            <td colspan="4" class="text-end fw-bold fs-5">Total Pesanan</td>
                            <td class="text-end fw-bold fs-5">Rp {{ number_format($purchaseOrder->total_amount, 0, ',', '.') }}</td>
                        </tr>
                    </tfoot>
                </table>
            </div>
             @if($purchaseOrder->notes)
            <div class="mt-3">
                <strong>Catatan:</strong>
                <p class="text-muted">{{ $purchaseOrder->notes }}</p>
            </div>
            @endif
        </div>
    </div>
</div>
@endsection
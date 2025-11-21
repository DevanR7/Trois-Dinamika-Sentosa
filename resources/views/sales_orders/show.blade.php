@extends('layouts.app')

@section('content')
<div class="container-fluid py-2">
    {{-- HEADER HALAMAN --}}
    <div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
        <div>
            <h3 class="fw-bold text-dark mb-1">Detail Pesanan: <span class="text-primary">{{ $order->order_number }}</span></h3>
            <p class="text-muted mb-0 small">Tanggal Pesan: {{ $order->order_date->format('d F Y') }}</p>
        </div>
    
        <div class="d-flex flex-wrap justify-content-end gap-2">
            <a href="{{ route('sales-orders.index') }}" class="btn btn-outline-secondary btn-sm shadow-sm">
                <i class="bi bi-arrow-left me-1"></i> Kembali
            </a>

            @can('create', App\Models\SalesInvoice::class)
                @if($order->status !== 'invoiced' && $order->status !== 'rejected')
                    <a href="{{ route('invoices.createFromOrder', $order) }}" class="btn btn-primary btn-sm shadow-sm">
                        <i class="bi bi-receipt-cutoff me-1"></i> Buat Invoice
                    </a>
                @endif
            @endcan
            
            @if (!in_array($order->status, ['invoiced', 'rejected']))
                @can("update", $order)
                    <a href="{{ route('sales-orders.edit', $order->order_id) }}" class="btn btn-warning btn-sm shadow-sm text-dark">
                        <i class="bi bi-pencil-square me-1"></i> Edit
                    </a>
                @endcan
                @can("delete", $order)
                    <form class="delete-form d-inline" action="{{ route('sales-orders.destroy', $order->order_id) }}" method="POST">
                        @csrf @method('DELETE')
                        <button type="submit" class="btn btn-danger btn-sm shadow-sm">
                            <i class="bi bi-trash me-1"></i> Hapus
                        </button>
                    </form>
                @endcan
            @endif
        </div>
    </div>

    <div class="row g-4">
        {{-- KOLOM KIRI: Detail & Item --}}
        <div class="col-lg-8">
            <div class="card card-transaction border-0 shadow-sm h-100">
                <div class="card-header bg-white p-3 border-bottom d-flex justify-content-between align-items-center">
                    <div class="form-section-title mb-0"><i class="bi bi-info-circle"></i> Informasi Pesanan</div>
                    
                    {{-- Status Badge --}}
                    @if($order->status == 'invoiced')
                        <span class="badge bg-success bg-opacity-10 text-success border border-success border-opacity-25">Invoiced</span>
                    @elseif($order->status == 'rejected')
                        <span class="badge bg-danger bg-opacity-10 text-danger border border-danger border-opacity-25">Ditolak</span>
                    @else
                        <span class="badge bg-secondary bg-opacity-10 text-secondary border border-secondary border-opacity-25">{{ Str::title($order->status) }}</span>
                    @endif
                </div>
                
                <div class="card-body p-4">
                    <div class="row mb-4">
                        <div class="col-md-6">
                            <h6 class="fw-bold text-secondary small text-uppercase">Klien</h6>
                            <h5 class="fw-bold text-dark mb-1">{{ $order->client->client_name }}</h5>
                            <p class="text-muted small mb-0">{{ $order->client->address ?? 'Alamat tidak tersedia' }}</p>
                            <p class="text-muted small">{{ $order->client->phone_number ?? '-' }}</p>
                        </div>
                        <div class="col-md-6 text-md-end">
                            <h6 class="fw-bold text-secondary small text-uppercase">Sales Person</h6>
                            <p class="mb-1 fw-medium">{{ $order->sales->full_name ?? 'N/A' }}</p>
                            <p class="text-muted small mb-0">{{ $order->sales->sales_code ?? '' }}</p>
                        </div>
                    </div>
                    
                    <h5 class="fw-semibold mt-4 mb-3" style="font-size: 0.9rem;">Rincian Item</h5>
                    <div class="table-responsive">
                        <table class="table table-hover table-transaction align-middle mb-0">
                            <thead class="bg-light">
                                <tr>
                                    <th class="ps-4">Produk</th>
                                    <th class="text-center">Qty</th>
                                    <th class="text-end">Harga (@)</th>
                                    <th class="text-end pe-4">Subtotal</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($order->items as $item)
                                <tr>
                                    <td class="ps-4 fw-medium">{{ $item->product->product_name ?? 'Produk Dihapus' }}</td>
                                    <td class="text-center">
                                        <span class="badge bg-light text-dark border">{{ $item->quantity }} {{ $item->product->unit->name ?? '' }}</span>
                                    </td>
                                    <td class="text-end text-muted small">Rp {{ number_format($item->price_per_unit, 0, ',', '.') }}</td>
                                    <td class="text-end pe-4 fw-bold">Rp {{ number_format($item->subtotal, 0, ',', '.') }}</td>
                                </tr>
                                @empty
                                <tr><td colspan="4" class="text-center py-3 text-muted">Tidak ada item.</td></tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>

                    @if($order->notes)
                    <div class="mt-4 p-3 bg-light rounded border border-light">
                        <h6 class="fw-semibold text-secondary small mb-1"><i class="bi bi-sticky"></i> Catatan:</h6>
                        <p class="text-muted mb-0 fst-italic small">{{ $order->notes }}</p>
                    </div>
                    @endif
                </div>
            </div>
        </div>

        {{-- KOLOM KANAN: Summary --}}
        <div class="col-lg-4">
            <div class="card card-transaction border-0 shadow-sm">
                <div class="card-header bg-white p-3 border-bottom">
                    <div class="form-section-title mb-0"><i class="bi bi-calculator"></i> Ringkasan</div>
                </div>
                <div class="card-body p-4">
                    <div class="d-flex justify-content-between mb-2">
                        <span class="text-muted small">Jumlah Item</span>
                        <span class="fw-medium">{{ $order->items->count() }}</span>
                    </div>
                    <div class="d-flex justify-content-between mb-2">
                        <span class="text-muted small">Total Kuantitas</span>
                        <span class="fw-medium">{{ $order->items->sum('quantity') }}</span>
                    </div>
                    
                    <hr class="border-dashed my-3">
                    
                    <div class="d-flex justify-content-between align-items-center">
                        <span class="fw-bold text-dark">TOTAL TAGIHAN</span>
                        <span class="fs-4 fw-bold text-primary">Rp {{ number_format($order->total_amount, 0, ',', '.') }}</span>
                    </div>
                </div>
                <div class="card-footer bg-light p-3 text-center border-top-0">
                    <small class="text-muted">Dibuat pada {{ $order->created_at->format('d M Y H:i') }}</small>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    const deleteForm = document.querySelector('.delete-form');
    if (deleteForm) {
        deleteForm.addEventListener('submit', function(event) {
            event.preventDefault(); 
            Swal.fire({
                title: 'Hapus Pesanan?',
                text: "Data yang dihapus tidak bisa dikembalikan.",
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#d33',
                cancelButtonColor: '#6c757d',
                confirmButtonText: 'Ya, Hapus!',
                cancelButtonText: 'Batal'
            }).then((result) => {
                if (result.isConfirmed) {
                    deleteForm.submit();
                }
            });
        });
    }
});
</script>
@endpush
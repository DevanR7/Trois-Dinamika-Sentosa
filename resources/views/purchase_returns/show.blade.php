@extends('layouts.app')

@section('content')
<div class="container-fluid py-2">
    
    {{-- HEADER HALAMAN --}}
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h3 class="fw-bold text-dark mb-1">Detail Retur: {{ $purchaseReturn->return_number }}</h3>
            <p class="text-muted mb-0 small">
                Tanggal: {{ optional($purchaseReturn->return_date)->format('d F Y') }}
            </p>
        </div>
        <div>
            <a href="{{ route('purchase-returns.index') }}" class="btn btn-outline-secondary btn-sm">
                <i class="bi bi-arrow-left me-1"></i> Kembali
            </a>
        </div>
    </div>

    <div class="row g-4">
        {{-- KOLOM KIRI --}}
        <div class="col-lg-8 col-xl-9">
            
            {{-- KARTU INFORMASI --}}
            <div class="card card-transaction border-0 shadow-sm mb-4">
                <div class="card-header bg-white">
                    <div class="form-section-title mb-0"><i class="bi bi-info-circle"></i> Data Transaksi</div>
                </div>
                <div class="card-body p-4">
                    <div class="row mb-4">
                        <div class="col-md-6">
                            <h6 class="fw-bold text-secondary small text-uppercase">Supplier</h6>
                            <h5 class="fw-bold text-dark mb-1">{{ $purchaseReturn->supplier->supplier_name }}</h5>
                            <p class="text-muted small mb-0">
                                PO Asli: <a href="{{ route('purchase-orders.show', $purchaseReturn->purchase_order_id) }}" class="text-decoration-none fw-bold">{{ $purchaseReturn->purchaseOrder->po_number }}</a>
                            </p>
                        </div>
                        <div class="col-md-6 text-md-end">
                            <h6 class="fw-bold text-secondary small text-uppercase">Info Lain</h6>
                            <p class="mb-1 small"><strong>Diproses Oleh:</strong> {{ $purchaseReturn->user->full_name ?? 'N/A' }}</p>
                            <p class="mb-0 small"><strong>Tanggal Input:</strong> {{ $purchaseReturn->created_at->format('d/m/Y H:i') }}</p>
                        </div>
                    </div>

                    <h5 class="fw-semibold mb-3" style="font-size: 0.9rem;">Item Dikembalikan</h5>
                    <div class="table-responsive">
                        <table class="table table-transaction table-hover align-middle mb-0">
                            <thead>
                                <tr>
                                    <th class="text-center" style="width: 5%;">#</th>
                                    <th>Produk</th>
                                    <th class="text-center">Qty Retur</th>
                                    <th class="text-end">Harga Beli (@)</th>
                                    <th class="text-end">Subtotal</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($purchaseReturn->items as $item)
                                <tr>
                                    <td class="text-center">{{ $loop->iteration }}</td>
                                    <td>
                                        <div class="fw-bold text-dark">{{ $item->product->product_name ?? 'Produk Dihapus' }}</div>
                                    </td>
                                    <td class="text-center">
                                        <span class="badge bg-danger bg-opacity-10 text-danger border border-danger border-opacity-25">{{ $item->quantity }} {{ $item->product->unit->name ?? '' }}</span>
                                    </td>
                                    <td class="text-end text-muted small">Rp {{ number_format($item->price_per_unit, 0, ',', '.') }}</td>
                                    <td class="text-end fw-bold">Rp {{ number_format($item->subtotal, 0, ',', '.') }}</td>
                                </tr>
                                @empty
                                <tr><td colspan="5" class="text-center text-muted fst-italic">Tidak ada item dalam retur ini.</td></tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            @if($purchaseReturn->notes)
            <div class="p-3 bg-light rounded border border-light">
                <h6 class="fw-semibold text-secondary small"><i class="bi bi-sticky"></i> Alasan Retur:</h6>
                <p class="text-muted mb-0 fst-italic">{{ $purchaseReturn->notes }}</p>
            </div>
            @endif

        </div>

        {{-- KOLOM KANAN (SUMMARY) --}}
        <div class="col-lg-4 col-xl-3">
            <div class="card card-transaction border-0 shadow-sm mb-4">
                <div class="card-header bg-white">
                    <div class="form-section-title mb-0"><i class="bi bi-calculator"></i> Total Retur</div>
                </div>
                <div class="card-body p-4">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <span class="text-muted small fw-bold">TOTAL NILAI</span>
                        <span class="fs-4 fw-bold text-danger">Rp {{ number_format($purchaseReturn->total_amount, 0, ',', '.') }}</span>
                    </div>
                    
                    <hr class="border-dashed my-3">
                    
                    <div class="d-grid">
                        <form action="{{ route('purchase-returns.destroy', $purchaseReturn->return_id) }}" method="POST" class="delete-form">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="btn btn-danger w-100 shadow-sm">
                                <i class="bi bi-trash me-2"></i> Batalkan Retur
                            </button>
                        </form>
                        <div class="text-center mt-2">
                            <small class="text-muted" style="font-size: 0.7rem;">Membatalkan retur akan mengembalikan stok barang masuk.</small>
                        </div>
                    </div>
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
                title: 'Batalkan Retur?',
                text: "Stok produk akan dikembalikan ke sistem. Data retur ini akan dihapus permanen!",
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#d33',
                cancelButtonColor: '#6c757d',
                confirmButtonText: 'Ya, Hapus!',
                cancelButtonText: 'Batal'
            }).then((result) => {
                if (result.isConfirmed) {
                    event.target.submit();
                }
            });
        });
    }
});
</script>
@endpush
@extends('layouts.app')

@section('content')
<div class="container-fluid py-2">
    
    {{-- HEADER --}}
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h3 class="fw-bold text-dark mb-1">Detail Retur Penjualan</h3>
            <p class="text-muted mb-0 small">No. Retur: <span class="text-primary fw-bold">{{ $salesReturn->return_number }}</span></p>
        </div>
        <div>
            <a href="{{ route('sales-returns.index') }}" class="btn btn-outline-secondary btn-sm">
                <i class="bi bi-arrow-left me-1"></i> Kembali
            </a>
        </div>
    </div>

    <div class="row g-4">
        
        {{-- KOLOM KIRI: DETAIL --}}
        <div class="col-lg-8">
            <div class="card card-transaction border-0 shadow-sm mb-4">
                <div class="card-header bg-white p-3 border-bottom">
                    <div class="form-section-title mb-0"><i class="bi bi-info-circle"></i> Informasi Retur</div>
                </div>
                <div class="card-body p-4">
                    <div class="row mb-4">
                        <div class="col-md-6">
                            <h6 class="fw-bold text-secondary small text-uppercase">Klien</h6>
                            <h5 class="fw-bold text-dark mb-1">{{ $salesReturn->client->client_name }}</h5>
                            <p class="text-muted small mb-0">
                                Invoice Asal: <a href="{{ route('invoices.show', $salesReturn->sales_invoice_id) }}" class="text-decoration-none fw-bold">{{ $salesReturn->salesInvoice->invoice_number }}</a>
                            </p>
                        </div>
                        <div class="col-md-6 text-md-end">
                            <h6 class="fw-bold text-secondary small text-uppercase">Tanggal & Petugas</h6>
                            <p class="mb-1 small"><strong>Tanggal:</strong> {{ optional($salesReturn->return_date)->format('d F Y') }}</p>
                            <p class="mb-0 small"><strong>Diproses:</strong> {{ $salesReturn->user->full_name ?? 'N/A' }}</p>
                        </div>
                    </div>

                    <h5 class="fw-semibold mb-3" style="font-size: 0.9rem;">Item Dikembalikan</h5>
                    <div class="table-responsive">
                        <table class="table table-hover table-transaction align-middle mb-0">
                            <thead class="bg-light">
                                <tr>
                                    <th class="ps-3">Produk</th>
                                    <th class="text-center">Qty</th>
                                    <th class="text-end">Harga Jual (@)</th>
                                    <th class="text-end pe-3">Subtotal</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($salesReturn->items as $item)
                                <tr>
                                    <td class="ps-3 fw-medium">{{ $item->product->product_name ?? 'Produk Dihapus' }}</td>
                                    <td class="text-center">
                                        <span class="badge bg-danger bg-opacity-10 text-danger border border-danger border-opacity-25">{{ $item->quantity }}</span>
                                    </td>
                                    <td class="text-end text-muted small">Rp {{ number_format($item->price_per_unit, 0, ',', '.') }}</td>
                                    <td class="text-end pe-3 fw-bold">Rp {{ number_format($item->subtotal, 0, ',', '.') }}</td>
                                </tr>
                                @empty
                                <tr><td colspan="4" class="text-center text-muted py-3">Tidak ada item.</td></tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>

                    @if($salesReturn->notes)
                    <div class="mt-4 p-3 bg-light rounded border border-light">
                        <h6 class="fw-semibold text-secondary small mb-1"><i class="bi bi-sticky"></i> Catatan:</h6>
                        <p class="text-muted mb-0 fst-italic small">{{ $salesReturn->notes }}</p>
                    </div>
                    @endif
                </div>
            </div>
        </div>

        {{-- KOLOM KANAN: SUMMARY & ACTION --}}
        <div class="col-lg-4">
            <div class="card card-transaction border-0 shadow-sm mb-4">
                <div class="card-header bg-white p-3 border-bottom">
                    <div class="form-section-title mb-0"><i class="bi bi-calculator"></i> Ringkasan Nilai</div>
                </div>
                <div class="card-body p-4">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <span class="text-muted small fw-bold">TOTAL NILAI</span>
                        <span class="fs-4 fw-bold text-danger">Rp {{ number_format($salesReturn->total_amount, 0, ',', '.') }}</span>
                    </div>
                    
                    <hr class="border-dashed my-3">
                    
                    <div class="d-grid">
                        <form action="{{ route('sales-returns.destroy', $salesReturn->return_id) }}" method="POST" class="delete-form">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="btn btn-danger w-100 shadow-sm fw-bold">
                                <i class="bi bi-trash me-2"></i> Batalkan Retur
                            </button>
                        </form>
                        <div class="text-center mt-2">
                            <small class="text-muted" style="font-size: 0.7rem;">Stok akan dikembalikan dan saldo disesuaikan.</small>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    const deleteForm = document.querySelector('.delete-form');
    if (deleteForm) {
        deleteForm.addEventListener('submit', function(event) {
            event.preventDefault();
            Swal.fire({
                title: 'Batalkan Retur?',
                text: "Tindakan ini akan mengembalikan stok dan membatalkan penyesuaian saldo. Data tidak bisa dikembalikan!",
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
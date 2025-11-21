@extends('layouts.app')

@section('content')
<div class="container-fluid py-2">
    {{-- HEADER --}}
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h3 class="fw-bold text-dark mb-1">Review Pesanan</h3>
            <p class="text-muted mb-0 small">
                No. Order: <span class="text-primary fw-bold">{{ $order->order_number }}</span>
            </p>
        </div>
        <div>
            <a href="{{ route('client-order-reviews.index') }}" class="btn btn-outline-secondary btn-sm">
                <i class="bi bi-arrow-left me-1"></i> Kembali
            </a>
        </div>
    </div>

    {{-- NOTIFIKASI --}}
    @if (session('success'))
        <div class="alert alert-success border-0 shadow-sm d-flex align-items-center mb-4">
            <i class="bi bi-check-circle-fill fs-4 me-2"></i>
            <div>{{ session('success') }}</div>
        </div>
    @endif
    @if (session('error'))
        <div class="alert alert-danger border-0 shadow-sm d-flex align-items-center mb-4">
            <i class="bi bi-exclamation-triangle-fill fs-4 me-2"></i>
            <div>{{ session('error') }}</div>
        </div>
    @endif

    <div class="row g-4">
        
        {{-- KOLOM KIRI: DETAIL ITEM --}}
        <div class="col-lg-8">
            <div class="card card-transaction border-0 shadow-sm mb-4">
                <div class="card-header bg-white p-3 border-bottom">
                    <div class="form-section-title mb-0"><i class="bi bi-cart-check"></i> Rincian Item Pesanan</div>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover table-transaction align-middle mb-0">
                            <thead class="bg-light">
                                <tr>
                                    <th class="ps-4" style="width: 5%;">#</th>
                                    <th>Produk</th>
                                    <th class="text-center" style="width: 15%;">Qty</th>
                                    <th class="text-end" style="width: 20%;">Harga (@)</th>
                                    <th class="text-end pe-4" style="width: 20%;">Subtotal</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($order->items as $item)
                                <tr>
                                    <td class="ps-4 text-muted">{{ $loop->iteration }}</td>
                                    <td>
                                        <div class="fw-bold text-dark">{{ $item->product->product_name ?? 'Produk Dihapus' }}</div>
                                    </td>
                                    <td class="text-center">
                                        <span class="badge bg-light text-dark border">{{ $item->quantity }}</span>
                                    </td>
                                    <td class="text-end text-muted small">Rp {{ number_format($item->price_per_unit, 0, ',', '.') }}</td>
                                    <td class="text-end pe-4 fw-semibold">Rp {{ number_format($item->subtotal, 0, ',', '.') }}</td>
                                </tr>
                                @empty
                                <tr><td colspan="5" class="text-center py-4 text-muted">Tidak ada item dalam pesanan ini.</td></tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
                {{-- Total Bar --}}
                <div class="card-footer bg-white p-3 text-end border-top-0">
                    <span class="text-muted small me-2">TOTAL PESANAN</span>
                    <span class="fs-4 fw-bold text-dark">Rp {{ number_format($order->total_amount, 0, ',', '.') }}</span>
                </div>
            </div>

            @if($order->notes)
            <div class="card card-transaction border-0 shadow-sm">
                <div class="card-body p-4">
                    <h6 class="fw-bold text-dark mb-2">Catatan Klien:</h6>
                    <p class="mb-0 text-muted fst-italic bg-light p-3 rounded">{{ $order->notes }}</p>
                </div>
            </div>
            @endif
        </div>

        {{-- KOLOM KANAN: INFO & AKSI --}}
        <div class="col-lg-4">
            
            {{-- INFO KLIEN --}}
            <div class="card card-transaction border-0 shadow-sm mb-4">
                <div class="card-header bg-white p-3 border-bottom">
                    <div class="form-section-title mb-0"><i class="bi bi-person-lines-fill"></i> Informasi Klien</div>
                </div>
                <div class="card-body p-4">
                    <div class="mb-3">
                        <label class="small fw-bold text-muted d-block">NAMA KLIEN</label>
                        <span class="fw-bold text-dark fs-5">{{ $order->client->client_name ?? 'N/A' }}</span>
                    </div>
                    <div class="mb-3">
                        <label class="small fw-bold text-muted d-block">ALAMAT PENGIRIMAN</label>
                        <span class="text-dark">{{ $order->client->address ?? '-' }}</span>
                    </div>
                    <div class="mb-3">
                        <label class="small fw-bold text-muted d-block">KONTAK</label>
                        <span class="text-dark">{{ $order->client->phone_number ?? '-' }}</span>
                    </div>
                    <div class="mb-0">
                        <label class="small fw-bold text-muted d-block">TANGGAL ORDER</label>
                        <span class="text-dark">{{ $order->order_date->format('d F Y') }}</span>
                    </div>
                </div>
            </div>

            {{-- STATUS & AKSI --}}
            <div class="card card-transaction border-0 shadow-sm">
                <div class="card-header bg-white p-3 border-bottom">
                    <div class="form-section-title mb-0"><i class="bi bi-shield-check"></i> Status & Aksi</div>
                </div>
                <div class="card-body p-4">
                    
                    <div class="mb-4 text-center">
                        <label class="small fw-bold text-muted d-block mb-2">STATUS SAAT INI</label>
                        @if($order->status == 'pending_review')
                            <span class="badge bg-info bg-opacity-10 text-info border border-info border-opacity-25 fs-6 px-3 py-2">Menunggu Review</span>
                        @elseif($order->status == 'invoiced')
                            <span class="badge bg-success bg-opacity-10 text-success border border-success border-opacity-25 fs-6 px-3 py-2">Disetujui (Invoiced)</span>
                        @elseif($order->status == 'rejected')
                            <span class="badge bg-danger bg-opacity-10 text-danger border border-danger border-opacity-25 fs-6 px-3 py-2">Ditolak</span>
                        @else
                            <span class="badge bg-secondary fs-6 px-3 py-2">{{ $order->status }}</span>
                        @endif
                    </div>

                    @if($order->status == 'pending_review')
                        <div class="d-grid gap-2">
                            {{-- Tombol Approve --}}
                            <a href="{{ route('invoices.createFromOrder', $order->order_id) }}" class="btn btn-success py-2 fw-bold shadow-sm">
                                <i class="bi bi-check-circle-fill me-2"></i> Setujui & Buat Invoice
                            </a>
                            
                            {{-- Tombol Reject --}}
                            <button type="button" class="btn btn-outline-danger py-2 fw-bold" data-bs-toggle="modal" data-bs-target="#rejectModal">
                                <i class="bi bi-x-circle me-2"></i> Tolak Pesanan
                            </button>
                        </div>
                        <div class="mt-3 text-center">
                            <small class="text-muted fst-italic">
                                <i class="bi bi-info-circle me-1"></i> Menyetujui akan membuat Draft Invoice yang bisa diedit (tambah pajak/diskon).
                            </small>
                        </div>
                    @elseif($order->status == 'invoiced' && $order->invoice_id)
                        <div class="d-grid">
                            <a href="{{ route('invoices.show', $order->invoice_id) }}" class="btn btn-primary btn-sm">
                                <i class="bi bi-receipt me-2"></i> Lihat Invoice Terkait
                            </a>
                        </div>
                    @endif

                </div>
            </div>

        </div>
    </div>
</div>

{{-- MODAL TOLAK --}}
<div class="modal fade" id="rejectModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <form action="{{ route('client-order-reviews.reject', $order->order_id) }}" method="POST">
                @csrf
                <div class="modal-header bg-danger text-white">
                    <h5 class="modal-title fw-bold"><i class="bi bi-exclamation-triangle-fill me-2"></i> Tolak Pesanan</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <p class="text-muted mb-3">Anda yakin ingin menolak pesanan <strong>{{ $order->order_number }}</strong>? Tindakan ini akan mengembalikan status dan tidak dapat membuat invoice.</p>
                    
                    <div class="mb-3">
                        <label for="rejection_notes" class="form-label fw-bold small text-muted">ALASAN PENOLAKAN (OPSIONAL)</label>
                        <textarea class="form-control" name="rejection_notes" id="rejection_notes" rows="3" placeholder="Contoh: Stok habis, harga berubah, dll..."></textarea>
                    </div>
                </div>
                <div class="modal-footer bg-light">
                    <button type="button" class="btn btn-light border" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-danger fw-bold">Ya, Tolak Pesanan</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Jika ingin konfirmasi tambahan sebelum submit form reject (opsional)
    // Untuk saat ini modal bootstrap sudah cukup representatif
});
</script>
@endpush
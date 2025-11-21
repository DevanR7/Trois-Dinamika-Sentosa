@extends('layouts.app')

@section('content')
<div class="container-fluid py-2">
    {{-- HEADER HALAMAN --}}
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h3 class="fw-bold text-dark mb-1">Review Permintaan</h3>
            <p class="text-muted mb-0 small">
                ID Request: <span class="text-primary fw-bold">REQ-{{ str_pad($changeRequest->request_id, 5, '0', STR_PAD_LEFT) }}</span>
            </p>
        </div>
        <div>
            <a href="{{ route('order-change-requests.index') }}" class="btn btn-outline-secondary btn-sm">
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
        
        {{-- KOLOM KIRI: DETAIL REQUEST --}}
        <div class="col-lg-8">
            <div class="card card-transaction border-0 shadow-sm mb-4">
                <div class="card-header bg-white p-3 border-bottom d-flex justify-content-between align-items-center">
                    <div class="form-section-title mb-0"><i class="bi bi-info-circle"></i> Detail Permintaan</div>
                    @if($changeRequest->request_type == 'cancel')
                        <span class="badge bg-danger bg-opacity-10 text-danger border border-danger border-opacity-25">Pembatalan</span>
                    @else
                        <span class="badge bg-info bg-opacity-10 text-info border border-info border-opacity-25">Modifikasi Item</span>
                    @endif
                </div>
                <div class="card-body p-4">
                    <div class="row mb-4">
                        <div class="col-md-6">
                            <h6 class="fw-bold text-secondary small text-uppercase">Informasi Pesanan</h6>
                            <p class="mb-1">
                                No. Order: 
                                @if($changeRequest->order)
                                    <a href="{{ route('sales-orders.show', $changeRequest->order_id) }}" class="fw-bold text-decoration-none">{{ $changeRequest->order->order_number }}</a>
                                @else
                                    <span class="text-danger fst-italic">Order Dihapus</span>
                                @endif
                            </p>
                            <p class="mb-0">Klien: <span class="fw-medium">{{ $changeRequest->client->client_name ?? 'N/A' }}</span></p>
                        </div>
                        <div class="col-md-6 text-md-end">
                            <h6 class="fw-bold text-secondary small text-uppercase">Waktu Pengajuan</h6>
                            <p class="mb-0">{{ $changeRequest->created_at->format('d F Y, H:i') }}</p>
                        </div>
                    </div>

                    @if($changeRequest->request_type == 'modify')
                        <h6 class="fw-bold text-dark mb-3">Rincian Perubahan Item</h6>
                        <div class="table-responsive">
                            <table class="table table-hover table-transaction align-middle mb-0">
                                <thead class="bg-light">
                                    <tr>
                                        <th class="ps-3">Produk</th>
                                        <th class="text-center">Aksi</th>
                                        <th class="text-center">Qty Awal</th>
                                        <th class="text-center">Qty Baru</th>
                                        <th class="text-end pe-3">Subtotal Baru</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse($changeRequest->items as $item)
                                    <tr class="{{ $item->action == 'remove' ? 'bg-danger bg-opacity-10' : ($item->action == 'add' ? 'bg-success bg-opacity-10' : '') }}">
                                        <td class="ps-3 fw-medium">{{ $item->product->product_name ?? 'Produk Dihapus' }}</td>
                                        <td class="text-center">
                                            @if($item->action == 'add') <span class="badge bg-success">Tambah</span>
                                            @elseif($item->action == 'remove') <span class="badge bg-danger">Hapus</span>
                                            @elseif($item->action == 'update_qty') <span class="badge bg-info text-dark">Ubah Qty</span>
                                            @endif
                                        </td>
                                        <td class="text-center text-muted">{{ $item->original_quantity ?? '-' }}</td>
                                        <td class="text-center fw-bold">{{ $item->requested_quantity }}</td>
                                        <td class="text-end pe-3 fw-bold">Rp {{ number_format($item->subtotal, 0, ',', '.') }}</td>
                                    </tr>
                                    @empty
                                    <tr><td colspan="5" class="text-center text-muted py-3">Tidak ada detail item.</td></tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                    @else
                        <div class="alert alert-warning border-0 bg-warning bg-opacity-10 text-dark">
                            <i class="bi bi-exclamation-triangle-fill me-2"></i> Klien meminta pembatalan seluruh pesanan ini.
                        </div>
                    @endif

                    @if($changeRequest->client_notes)
                        <div class="mt-4 p-3 bg-light rounded border border-light">
                            <h6 class="fw-semibold text-secondary small mb-1"><i class="bi bi-chat-quote"></i> Catatan Klien:</h6>
                            <p class="mb-0 fst-italic text-muted">{{ $changeRequest->client_notes }}</p>
                        </div>
                    @endif
                </div>
            </div>
        </div>

        {{-- KOLOM KANAN: ACTION --}}
        <div class="col-lg-4">
            
            {{-- STATUS CARD --}}
            <div class="card card-transaction border-0 shadow-sm mb-4">
                <div class="card-header bg-white p-3 border-bottom">
                    <div class="form-section-title mb-0"><i class="bi bi-shield-check"></i> Status & Tindakan</div>
                </div>
                <div class="card-body p-4">
                    
                    <div class="mb-4 text-center">
                        <label class="small fw-bold text-muted d-block mb-2">STATUS SAAT INI</label>
                        @if($changeRequest->status == 'pending')
                            <span class="badge bg-warning bg-opacity-10 text-warning border border-warning border-opacity-25 fs-6 px-3 py-2">Menunggu Review</span>
                        @elseif($changeRequest->status == 'approved')
                            <span class="badge bg-success bg-opacity-10 text-success border border-success border-opacity-25 fs-6 px-3 py-2">Disetujui</span>
                        @else
                            <span class="badge bg-danger bg-opacity-10 text-danger border border-danger border-opacity-25 fs-6 px-3 py-2">Ditolak</span>
                        @endif
                    </div>

                    @if($changeRequest->status == 'pending')
                        <form action="{{ route('order-change-requests.process', $changeRequest->request_id) }}" method="POST">
                            @csrf
                            
                            <div class="mb-3">
                                <label class="form-label fw-bold small text-muted">TINDAKAN ANDA</label>
                                <div class="d-grid gap-2">
                                    <input type="radio" class="btn-check" name="action" id="action_approve" value="approve" checked>
                                    <label class="btn btn-outline-success fw-bold" for="action_approve">
                                        <i class="bi bi-check-circle me-1"></i> Setujui Permintaan
                                    </label>

                                    <input type="radio" class="btn-check" name="action" id="action_reject" value="reject">
                                    <label class="btn btn-outline-danger fw-bold" for="action_reject">
                                        <i class="bi bi-x-circle me-1"></i> Tolak Permintaan
                                    </label>
                                </div>
                            </div>

                            <div class="mb-3">
                                <label for="admin_notes" class="form-label fw-bold small text-muted">CATATAN ADMIN (OPSIONAL)</label>
                                <textarea class="form-control bg-light" name="admin_notes" id="admin_notes" rows="3" placeholder="Tulis alasan persetujuan/penolakan..."></textarea>
                            </div>

                            <button type="submit" class="btn btn-primary w-100 shadow-sm fw-bold">
                                <i class="bi bi-send me-1"></i> Simpan Keputusan
                            </button>
                        </form>
                    @else
                        <div class="bg-light p-3 rounded text-center border border-light">
                            <p class="mb-1 text-muted small">Diproses oleh: <strong>{{ $changeRequest->processor->full_name ?? 'System' }}</strong></p>
                            <p class="mb-0 text-muted small">Pada: {{ optional($changeRequest->processed_at)->format('d M Y, H:i') }}</p>
                            
                            @if($changeRequest->admin_notes)
                                <hr class="my-2 border-secondary opacity-25">
                                <p class="mb-0 fst-italic text-dark small">"{{ $changeRequest->admin_notes }}"</p>
                            @endif
                        </div>
                    @endif
                </div>
            </div>

        </div>
    </div>
</div>
@endsection
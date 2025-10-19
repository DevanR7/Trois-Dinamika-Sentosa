@extends('layouts.app')

@section('content')
<div class="container py-4">
    {{-- HEADER HALAMAN --}}
    <div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
        <h2 class="fw-bold mb-0">Review Permintaan Perubahan #REQ-{{ str_pad($changeRequest->request_id, 5, '0', STR_PAD_LEFT) }}</h2>
        <a href="{{ route('order-change-requests.index') }}" class="btn btn-outline-secondary">
            <i class="bi bi-arrow-left me-1"></i> Kembali ke Daftar
        </a>
    </div>

    @if (session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif
    @if (session('error'))
        <div class="alert alert-danger">{{ session('error') }}</div>
    @endif

    {{-- KARTU DETAIL PERMINTAAN --}}
    <div class="card shadow-sm border-0 mb-4">
        <div class="card-header bg-light">
            <h5 class="mb-0 fw-semibold">Detail Permintaan</h5>
        </div>
        <div class="card-body p-4">
            <div class="row mb-3">
                <div class="col-md-6">
                    <p class="mb-1"><strong>Nomor Pesanan:</strong>
                        @if($changeRequest->order)
                            <a href="{{ route('sales-orders.show', $changeRequest->order_id) }}">{{ $changeRequest->order->order_number }}</a>
                        @else
                            <span class="text-muted">Order Dihapus</span>
                        @endif
                    </p>
                    <p class="mb-1"><strong>Klien:</strong> {{ $changeRequest->client->client_name ?? 'Klien Dihapus' }}</p>
                    <p class="mb-1"><strong>Tipe Permintaan:</strong> <span class="fw-semibold">{{ $changeRequest->request_type == 'cancel' ? 'Pembatalan Pesanan' : 'Modifikasi Item' }}</span></p>
                </div>
                <div class="col-md-6 text-md-end">
                    <p class="mb-1"><strong>Tanggal Diajukan:</strong> {{ $changeRequest->created_at->format('d M Y H:i') }}</p>
                    <p class="mb-0"><strong>Status Saat Ini:</strong> <span class="badge bg-warning text-dark fs-6">{{ Str::title($changeRequest->status) }}</span></p>
                </div>
            </div>

            @if($changeRequest->client_notes)
                <h6 class="fw-semibold mt-3">Catatan dari Klien:</h6>
                <p class="text-muted fst-italic bg-light p-3 rounded">{{ $changeRequest->client_notes }}</p>
            @endif
        </div>
    </div>

    {{-- DETAIL PERUBAHAN ITEM (JIKA TIPE 'modify') --}}
    @if($changeRequest->request_type == 'modify')
    <div class="card shadow-sm border-0 mb-4">
        <div class="card-header bg-light">
            <h5 class="mb-0 fw-semibold">Detail Perubahan Item Diminta</h5>
        </div>
        <div class="card-body p-0"> {{-- p-0 agar tabel rapat --}}
            <div class="table-responsive">
                <table class="table table-bordered table-striped mb-0 align-middle">
                    <thead class="table-secondary">
                        <tr>
                            <th>Produk</th>
                            <th class="text-center">Aksi</th>
                            <th class="text-center">Qty Asli</th>
                            <th class="text-center">Qty Diminta</th>
                            <th class="text-end">Harga Satuan</th>
                            <th class="text-end">Subtotal Diminta</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($changeRequest->items as $item)
                        <tr class="{{ $item->action == 'remove' ? 'table-danger' : ($item->action == 'add' ? 'table-success' : '') }}">
                            <td>{{ $item->product->product_name ?? 'Produk Dihapus' }}</td>
                            <td class="text-center">
                                @if($item->action == 'add') <span class="badge bg-success">Tambah</span>
                                @elseif($item->action == 'remove') <span class="badge bg-danger">Hapus</span>
                                @elseif($item->action == 'update_qty') <span class="badge bg-info text-dark">Ubah Qty</span>
                                @endif
                            </td>
                            <td class="text-center">{{ $item->original_quantity ?? '-' }}</td>
                            <td class="text-center fw-bold">{{ $item->requested_quantity }}</td>
                            <td class="text-end">Rp {{ number_format($item->price_per_unit, 0, ',', '.') }}</td>
                            <td class="text-end fw-bold">Rp {{ number_format($item->subtotal, 0, ',', '.') }}</td>
                        </tr>
                        @empty
                        <tr><td colspan="6" class="text-center text-muted">Tidak ada detail perubahan item.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    @endif

    {{-- FORM PROSES PERMINTAAN --}}
    @if($changeRequest->status == 'pending')
    <div class="card shadow-sm border-0">
         <div class="card-header bg-primary text-white">
            <h5 class="mb-0 fw-semibold">Proses Permintaan</h5>
        </div>
        <div class="card-body p-4">
             <form action="{{ route('order-change-requests.process', $changeRequest->request_id) }}" method="POST">
                @csrf
                <div class="mb-3">
                    <label class="form-label fw-semibold">Tindakan <span class="text-danger">*</span></label>
                     <div>
                         <div class="form-check form-check-inline">
                             <input class="form-check-input" type="radio" name="action" id="action_approve" value="approve" required>
                             <label class="form-check-label text-success fw-bold" for="action_approve">
                                 <i class="bi bi-check-circle-fill me-1"></i> Setujui Permintaan
                             </label>
                         </div>
                         <div class="form-check form-check-inline">
                             <input class="form-check-input" type="radio" name="action" id="action_reject" value="reject" required>
                             <label class="form-check-label text-danger fw-bold" for="action_reject">
                                 <i class="bi bi-x-circle-fill me-1"></i> Tolak Permintaan
                             </label>
                         </div>
                     </div>
                </div>
                 <div class="mb-3">
                     <label for="admin_notes" class="form-label fw-semibold">Catatan / Alasan (Opsional)</label>
                     <textarea class="form-control" name="admin_notes" id="admin_notes" rows="3" placeholder="Berikan alasan jika menolak, atau catatan tambahan jika menyetujui..."></textarea>
                 </div>
                 <div class="d-flex justify-content-end">
                     <button type="submit" class="btn btn-primary">
                         <i class="bi bi-check2-square me-1"></i> Proses Permintaan
                     </button>
                 </div>
             </form>
        </div>
    </div>
     @else
     {{-- Tampilkan info jika sudah diproses --}}
      <div class="card shadow-sm border-0">
          <div class="card-body p-4">
               <h5 class="fw-semibold">Status Permintaan</h5>
               <p>Permintaan ini telah di-<strong class="{{ $changeRequest->status == 'approved' ? 'text-success' : 'text-danger' }}">{{ $changeRequest->status }}</strong>
                   oleh {{ $changeRequest->processor->full_name ?? 'Sistem' }}
                   pada {{ optional($changeRequest->processed_at)->format('d M Y H:i') }}.
               </p>
               @if($changeRequest->admin_notes)
               <h6 class="fw-semibold mt-3">Catatan Admin:</h6>
               <p class="text-muted fst-italic bg-light p-3 rounded">{{ $changeRequest->admin_notes }}</p>
               @endif
          </div>
      </div>
     @endif

</div>
@endsection
@extends('layouts.app') {{-- Sesuaikan dengan layout admin Anda --}}

@section('content')
<div class="container py-4">
    {{-- HEADER HALAMAN --}}
    <div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
        <h2 class="fw-bold mb-0">Review Pesanan Klien: {{ $order->order_number }}</h2>
        {{-- Pastikan nama route index benar (order-change-requests.index atau client-order-reviews.index) --}}
        <a href="{{ route('client-order-reviews.index') }}" class="btn btn-outline-secondary">
            <i class="bi bi-arrow-left me-1"></i> Kembali ke Daftar Review
        </a>
    </div>

    {{-- Notifikasi --}}
    @if (session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif
    @if (session('error'))
        <div class="alert alert-danger">{{ session('error') }}</div>
    @endif

    {{-- KARTU DETAIL PESANAN --}}
    <div class="card shadow-sm border-0 mb-4">
         <div class="card-header bg-light d-flex justify-content-between align-items-center">
             <h5 class="mb-0 fw-semibold">Detail Pesanan</h5>
             <span class="badge bg-info text-dark fs-6">{{ Str::title(str_replace('_', ' ', $order->status)) }}</span>
         </div>
        <div class="card-body p-4">
            <div class="row mb-4">
                <div class="col-md-6">
                    <h6 class="fw-semibold">Klien:</h6>
                    <p class="mb-0">{{ $order->client->client_name ?? 'N/A' }}</p>
                    <p class="text-muted">{{ $order->client->address ?? 'Alamat tidak tersedia' }}</p>
                    <p class="text-muted">{{ $order->client->phone_number ?? '' }}</p>
                </div>
                <div class="col-md-6 text-md-end">
                    <p class="mb-1"><strong>Tanggal Pesanan:</strong> {{ optional($order->order_date)->format('d F Y') }}</p>
                </div>
            </div>
            <hr>

            <h6 class="fw-semibold mt-4">Rincian Item Dipesan</h6>
            <div class="table-responsive">
                <table class="table table-bordered">
                    <thead class="table-light">
                        <tr>
                            <th>#</th>
                            <th>Produk</th>
                            <th class="text-center">Kuantitas</th>
                            <th class="text-end">Harga Satuan (Beli)</th>
                            <th class="text-end">Subtotal</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($order->items as $item)
                        <tr>
                            <td>{{ $loop->iteration }}</td>
                            <td>{{ $item->product->product_name ?? 'Produk Dihapus' }}</td>
                            <td class="text-center">{{ $item->quantity }} {{ $item->product->unit->name ?? '' }}</td>
                            <td class="text-end">Rp {{ number_format($item->price_per_unit, 0, ',', '.') }}</td>
                            <td class="text-end">Rp {{ number_format($item->subtotal, 0, ',', '.') }}</td>
                        </tr>
                        @empty
                        <tr><td colspan="5" class="text-center text-muted">Tidak ada item dalam pesanan ini.</td></tr>
                        @endforelse
                    </tbody>
                    <tfoot>
                        <tr class="table-light">
                            <td colspan="4" class="text-end fw-bold fs-5">Total Pesanan</td>
                            <td class="text-end fw-bold fs-5">Rp {{ number_format($order->total_amount, 0, ',', '.') }}</td>
                        </tr>
                    </tfoot>
                </table>
            </div>

            @if($order->notes)
            <div class="mt-4">
                <h6 class="fw-semibold">Catatan dari Klien:</h6>
                <p class="text-muted fst-italic bg-light p-3 rounded">{{ $order->notes }}</p>
            </div>
            @endif
        </div>
    </div>

    {{-- FORM AKSI APPROVE / REJECT --}}
    @if($order->status == 'pending_review')
    <div class="card shadow-sm border-0">
         <div class="card-header bg-primary text-white">
            <h5 class="mb-0 fw-semibold">Tindakan Review</h5>
        </div>
        <div class="card-body p-4">
            <p>Setujui pesanan ini untuk melanjutkan ke pembuatan invoice (Anda bisa menambahkan pajak/diskon di langkah berikutnya), atau tolak pesanan ini.</p>
            
            <div class="d-flex justify-content-between align-items-center flex-wrap gap-3">
                
                {{-- Tombol Tolak (dengan Modal) --}}
                <button type="button" class="btn btn-danger" data-bs-toggle="modal" data-bs-target="#rejectModal">
                     <i class="bi bi-x-circle-fill me-1"></i> Tolak Pesanan Ini
                </button>

                {{-- ✅ TOMBOL BARU: BUAT INVOICE --}}
                {{-- Ini mengarah ke route yang SAMA dengan "Buat Invoice" dari Sales Order --}}
                <a href="{{ route('invoices.createFromOrder', $order->order_id) }}" class="btn btn-success btn-lg">
                    <i class="bi bi-check-circle-fill me-1"></i> Proses & Buat Invoice
                </a>
            </div>
        </div>
    </div>
    @else
    {{-- Tampilkan info jika sudah diproses --}}
    <div class="card shadow-sm border-0">
        <div class="card-header bg-light">
             <h5 class="mb-0 fw-semibold">Status Pesanan</h5>
        </div>
         <div class="card-body p-4">
             <p classs="mb-0">Pesanan ini telah diproses dan statusnya sekarang adalah: 
                <strong class="{{ $order->status == 'invoiced' ? 'text-success' : ($order->status == 'rejected' ? 'text-danger' : 'text-secondary') }}">
                    {{ Str::title(str_replace('_', ' ', $order->status)) }}
                </strong>.
            </p>
            @if($order->invoice_id)
                <a href="{{ route('invoices.show', $order->invoice_id) }}" class="btn btn-outline-info btn-sm mt-2">Lihat Invoice Terkait</a>
            @endif
         </div>
     </div>
    @endif
</div>

{{-- MODAL UNTUK REJECT (PINDAHKAN DARI FORM) --}}
<div class="modal fade" id="rejectModal" tabindex="-1" aria-labelledby="rejectModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <form action="{{ route('client-order-reviews.reject', $order->order_id) }}" method="POST"> {{-- Pastikan nama route benar --}}
                @csrf
                <div class="modal-header">
                    <h5 class="modal-title" id="rejectModalLabel">Tolak Pesanan #{{ $order->order_number }}</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="rejection_notes" class="form-label">Alasan Penolakan (Opsional)</label>
                        <textarea class="form-control" name="rejection_notes" id="rejection_notes" rows="3" placeholder="Jelaskan mengapa pesanan ini ditolak..."></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-danger">Tolak Pesanan</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection

@push('scripts')
{{-- SweetAlert untuk konfirmasi Tolak --}}
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    const rejectForm = document.querySelector('#rejectModal form'); // Target form di dalam modal

    if (rejectForm) {
        rejectForm.addEventListener('submit', function(event) {
            event.preventDefault();
            // Sembunyikan modal dulu (opsional, tapi rapi)
            // const modalInstance = bootstrap.Modal.getInstance(document.getElementById('rejectModal'));
            // modalInstance.hide();
            
            Swal.fire({
                title: 'Tolak Pesanan?',
                text: "Pesanan yang ditolak tidak dapat diproses lebih lanjut dan stok akan dikembalikan.",
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#dc3545',
                cancelButtonColor: '#6c757d',
                confirmButtonText: 'Ya, Tolak!',
                cancelButtonText: 'Batal'
            }).then((result) => {
                if (result.isConfirmed) {
                    event.target.submit(); // Kirim form jika dikonfirmasi
                }
                // else {
                //     modalInstance.show(); // Tampilkan lagi modal jika batal
                // }
            });
        });
    }
});
</script>
@endpush
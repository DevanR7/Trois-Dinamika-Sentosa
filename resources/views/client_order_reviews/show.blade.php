@extends('layouts.app') {{-- Sesuaikan dengan layout admin Anda --}}

@section('content')
<div class="container py-4">
    {{-- HEADER HALAMAN --}}
    <div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
        <h2 class="fw-bold mb-0">Review Pesanan Klien: {{ $order->order_number }}</h2>
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

    {{-- KARTU DETAIL PESANAN (Mirip sales_orders.show tapi tanpa tombol edit/invoice) --}}
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
                    {{-- Sales tidak ada untuk order ini --}}
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
    <div class="card shadow-sm border-0">
         <div class="card-header bg-primary text-white">
            <h5 class="mb-0 fw-semibold">Tindakan Review</h5>
        </div>
        <div class="card-body p-4">
            {{-- Form untuk Reject --}}
             <form action="{{ route('client-order-reviews.reject', $order->order_id) }}" method="POST" id="reject-form" class="mb-3">
                 @csrf
                 <div class="mb-3">
                    <label for="rejection_notes" class="form-label">Alasan Penolakan (Opsional)</label>
                    <textarea class="form-control form-control-sm" name="rejection_notes" id="rejection_notes" rows="2"></textarea>
                 </div>
                 <button type="submit" class="btn btn-danger">
                     <i class="bi bi-x-circle-fill me-1"></i> Tolak Pesanan Ini
                 </button>
             </form>

             <hr>

             {{-- Form untuk Approve --}}
             <form action="{{ route('client-order-reviews.approve', $order->order_id) }}" method="POST" id="approve-form" class="mt-3 text-end">
                  @csrf
                 <button type="submit" class="btn btn-success btn-lg"> {{-- Buat tombol approve lebih besar --}}
                     <i class="bi bi-check-circle-fill me-1"></i> Setujui Pesanan Ini
                 </button>
             </form>
        </div>
    </div>
</div>
@endsection

@push('scripts')
{{-- SweetAlert untuk konfirmasi --}}
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    const approveForm = document.getElementById('approve-form');
    const rejectForm = document.getElementById('reject-form');

    if (approveForm) {
        approveForm.addEventListener('submit', function(event) {
            event.preventDefault();
            Swal.fire({
                title: 'Setujui Pesanan?',
                text: "Pastikan detail pesanan sudah benar sebelum disetujui.",
                icon: 'question',
                showCancelButton: true,
                confirmButtonColor: '#198754',
                cancelButtonColor: '#6c757d',
                confirmButtonText: 'Ya, Setujui!',
                cancelButtonText: 'Batal'
            }).then((result) => {
                if (result.isConfirmed) {
                    event.target.submit();
                }
            });
        });
    }

     if (rejectForm) {
        rejectForm.addEventListener('submit', function(event) {
            event.preventDefault();
            Swal.fire({
                title: 'Tolak Pesanan?',
                text: "Pesanan yang ditolak tidak dapat diproses lebih lanjut.",
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#dc3545',
                cancelButtonColor: '#6c757d',
                confirmButtonText: 'Ya, Tolak!',
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
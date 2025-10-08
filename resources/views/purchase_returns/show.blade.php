@extends('layouts.app')

@section('content')
<div class="container py-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2 class="fw-bold">Detail Retur Pembelian: {{ $purchaseReturn->return_number }}</h2>
        <a href="{{ route('purchase-returns.index') }}" class="btn btn-outline-secondary">
            <i class="bi bi-arrow-left me-1"></i> Kembali ke Daftar Retur
        </a>
    </div>

    <div class="card shadow-sm border-0">
        <div class="card-body p-4">
            <div class="row mb-4">
                <div class="col-md-6">
                    <h5 class="fw-semibold">Supplier:</h5>
                    <p class="mb-0">{{ $purchaseReturn->supplier->supplier_name }}</p>
                </div>
                <div class="col-md-6 text-md-end">
                    <p class="mb-1"><strong>Tanggal Retur:</strong> {{ optional($purchaseReturn->return_date)->format('d F Y') }}</p>
                    <p class="mb-1"><strong>PO Asli:</strong> 
                        <a href="{{ route('purchase-orders.show', $purchaseReturn->purchase_order_id) }}">{{ $purchaseReturn->purchaseOrder->po_number }}</a>
                    </p>
                    <p class="mb-1"><strong>Diproses oleh:</strong> {{ $purchaseReturn->user->full_name ?? 'N/A' }}</p>
                </div>
            </div>
            <hr>

            <h5 class="fw-semibold mt-4">Rincian Item yang Dikembalikan ke Supplier</h5>
            <div class="table-responsive">
                <table class="table table-bordered">
                    <thead class="table-light">
                        <tr>
                            <th>#</th>
                            <th>Produk</th>
                            <th class="text-center">Kuantitas</th>
                            <th class="text-end">Harga Beli Satuan</th>
                            <th class="text-end">Subtotal</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($purchaseReturn->items as $item)
                        <tr>
                            <td>{{ $loop->iteration }}</td>
                            <td>{{ $item->product->product_name ?? 'Produk Dihapus' }}</td>
                            <td class="text-center">{{ $item->quantity }} {{ $item->product->unit->name ?? '' }}</td>
                            <td class="text-end">Rp {{ number_format($item->price_per_unit, 0, ',', '.') }}</td>
                            <td class="text-end">Rp {{ number_format($item->subtotal, 0, ',', '.') }}</td>
                        </tr>
                        @empty
                        <tr><td colspan="5" class="text-center">Tidak ada item dalam retur ini.</td></tr>
                        @endforelse
                    </tbody>
                    <tfoot>
                        <tr class="table-light">
                            <td colspan="4" class="text-end fw-bold fs-5">Total Nilai Retur</td>
                            <td class="text-end fw-bold fs-5">Rp {{ number_format($purchaseReturn->total_amount, 0, ',', '.') }}</td>
                        </tr>
                    </tfoot>
                </table>
            </div>

            @if($purchaseReturn->notes)
            <div class="mt-4">
                <h6 class="fw-semibold">Catatan (Alasan Retur):</h6>
                <p class="text-muted fst-italic">{{ $purchaseReturn->notes }}</p>
            </div>
            @endif

            <div class="d-flex justify-content-end mt-4">
                <form action="{{ route('purchase-returns.destroy', $purchaseReturn->return_id) }}" method="POST" class="delete-form">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="btn btn-danger">
                        <i class="bi bi-trash me-1"></i> Batalkan & Hapus Retur Ini
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
{{-- Script untuk konfirmasi hapus dengan SweetAlert --}}
<script>
document.addEventListener('DOMContentLoaded', function() {
    const deleteForm = document.querySelector('.delete-form');
    if (deleteForm) {
        deleteForm.addEventListener('submit', function(event) {
            event.preventDefault();
            Swal.fire({
                title: 'Anda Yakin?',
                text: "Membatalkan retur akan menambah kembali stok produk ke sistem Anda. Aksi ini tidak bisa diurungkan!",
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#d33',
                cancelButtonColor: '#6c757d',
                confirmButtonText: 'Ya, Batalkan Retur!',
                cancelButtonText: 'Tidak'
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
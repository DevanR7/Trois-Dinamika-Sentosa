@extends('layouts.app')

@section('content')
<div class="container py-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2 class="fw-bold">Detail Retur: {{ $salesReturn->return_number }}</h2>
        <a href="{{ route('sales-returns.index') }}" class="btn btn-outline-secondary">
            <i class="bi bi-arrow-left me-1"></i> Kembali ke Daftar Retur
        </a>
    </div>

    <div class="card shadow-sm border-0">
        <div class="card-body p-4">
            <div class="row mb-4">
                <div class="col-md-6">
                    <h5 class="fw-semibold">Klien:</h5>
                    <p class="mb-0">{{ $salesReturn->client->client_name }}</p>
                </div>
                <div class="col-md-6 text-md-end">
                    <p class="mb-1"><strong>Tanggal Retur:</strong> {{ optional($salesReturn->return_date)->format('d F Y') }}</p>
                    <p class="mb-1"><strong>Invoice Asli:</strong> 
                        <a href="{{ route('invoices.show', $salesReturn->sales_invoice_id) }}">{{ $salesReturn->salesInvoice->invoice_number }}</a>
                    </p>
                    <p class="mb-1"><strong>Diproses oleh:</strong> {{ $salesReturn->user->full_name ?? 'N/A' }}</p>
                </div>
            </div>
            <hr>

            <h5 class="fw-semibold mt-4">Rincian Item yang Dikembalikan</h5>
            <div class="table-responsive">
                <table class="table table-bordered">
                    <thead class="table-light">
                        <tr>
                            <th>#</th>
                            <th>Produk</th>
                            <th class="text-center">Kuantitas</th>
                            <th class="text-end">Harga Satuan</th>
                            <th class="text-end">Subtotal</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($salesReturn->items as $item)
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
                            <td class="text-end fw-bold fs-5">Rp {{ number_format($salesReturn->total_amount, 0, ',', '.') }}</td>
                        </tr>
                    </tfoot>
                </table>
            </div>

            @if($salesReturn->notes)
            <div class="mt-4">
                <h6 class="fw-semibold">Catatan (Alasan Retur):</h6>
                <p class="text-muted fst-italic">{{ $salesReturn->notes }}</p>
            </div>
            @endif

            <div class="d-flex justify-content-end mt-4">
                <form action="{{ route('sales-returns.destroy', $salesReturn->return_id) }}" method="POST" class="delete-form">
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
                text: "Membatalkan retur akan mengurangi kembali stok produk dan menyesuaikan tagihan. Aksi ini tidak bisa diurungkan!",
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
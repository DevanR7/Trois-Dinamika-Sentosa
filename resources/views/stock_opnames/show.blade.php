@extends('layouts.app')

@section('content')
<div class="container py-4">
    
    {{-- Header --}}
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h2 class="fw-bold mb-0">Detail Stock Opname</h2>
            <p class="text-muted mb-0">Nomor: {{ $stockOpname->opname_number }}</p>
        </div>
        
        <div class="d-flex gap-2">
            {{-- Tombol Batalkan / Hapus --}}
            <form action="{{ route('stock-opnames.destroy', $stockOpname->opname_id) }}" method="POST" class="form-delete-opname-detail">
                @csrf
                @method('DELETE')
                <button type="submit" class="btn btn-danger">
                    <i class="bi bi-trash me-1"></i> Batalkan & Hapus
                </button>
            </form>

            <a href="{{ route('stock-opnames.index') }}" class="btn btn-outline-secondary">
                <i class="bi bi-arrow-left me-1"></i> Kembali
            </a>
        </div>
    </div>

    <div class="row g-4">
        {{-- Info Card --}}
        <div class="col-md-4">
            <div class="card shadow-sm border-0 h-100">
                <div class="card-body">
                    <h5 class="card-title fw-bold mb-3">Informasi Opname</h5>
                    <ul class="list-group list-group-flush">
                        <li class="list-group-item d-flex justify-content-between px-0">
                            <span class="text-muted">Tanggal</span>
                            <span class="fw-medium">{{ $stockOpname->opname_date->format('d M Y') }}</span>
                        </li>
                        <li class="list-group-item d-flex justify-content-between px-0">
                            <span class="text-muted">Petugas</span>
                            <span class="fw-medium">{{ $stockOpname->user->full_name ?? 'System' }}</span>
                        </li>
                        <li class="list-group-item d-flex justify-content-between px-0">
                            <span class="text-muted">Status</span>
                            <span class="badge bg-success">Selesai</span>
                        </li>
                        <li class="list-group-item px-0">
                            <span class="text-muted d-block mb-1">Catatan</span>
                            <p class="mb-0 fst-italic bg-light p-2 rounded small">{{ $stockOpname->notes ?: '-' }}</p>
                        </li>
                    </ul>

                    {{-- Kotak Ringkasan Nilai --}}
                    <div class="mt-4 p-3 rounded {{ $stockOpname->total_adjustment_value < 0 ? 'bg-danger bg-opacity-10 border border-danger' : 'bg-success bg-opacity-10 border border-success' }}">
                        <h6 class="mb-1 {{ $stockOpname->total_adjustment_value < 0 ? 'text-danger' : 'text-success' }}">Total Nilai Penyesuaian</h6>
                        <h3 class="fw-bold mb-0 {{ $stockOpname->total_adjustment_value < 0 ? 'text-danger' : 'text-success' }}">
                            {{ $stockOpname->total_adjustment_value < 0 ? '-' : '+' }} Rp {{ number_format(abs($stockOpname->total_adjustment_value), 0, ',', '.') }}
                        </h3>
                        <small class="text-muted">
                            @if($stockOpname->total_adjustment_value < 0)
                                (Kerugian / Stok Hilang)
                            @elseif($stockOpname->total_adjustment_value > 0)
                                (Keuntungan / Stok Lebih)
                            @else
                                (Tidak ada selisih nilai)
                            @endif
                        </small>
                    </div>
                </div>
            </div>
        </div>

        {{-- Items Table --}}
        <div class="col-md-8">
            <div class="card shadow-sm border-0 h-100">
                <div class="card-header bg-white py-3">
                    <h5 class="mb-0 fw-bold">Rincian Barang</h5>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive" style="max-height: 600px;">
                        <table class="table table-hover align-middle mb-0">
                            <thead class="table-light sticky-top">
                                <tr>
                                    <th>Produk</th>
                                    <th class="text-center">System</th>
                                    <th class="text-center">Fisik</th>
                                    <th class="text-center">Selisih</th>
                                    <th class="text-end">Nilai (Rp)</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($stockOpname->items as $item)
                                {{-- Highlight baris yang ada selisih dengan warna --}}
                                <tr class="{{ $item->difference != 0 ? ($item->difference < 0 ? 'table-danger' : 'table-success') : '' }}">
                                    <td>
                                        <div class="fw-medium">{{ $item->product->product_name }}</div>
                                        <small class="text-muted">HPP: Rp {{ number_format($item->cost_per_unit, 0, ',', '.') }}</small>
                                    </td>
                                    <td class="text-center">{{ $item->system_qty }}</td>
                                    <td class="text-center fw-bold">{{ $item->physical_qty }}</td>
                                    <td class="text-center fw-bold">
                                        @if($item->difference > 0) +{{ $item->difference }}
                                        @else {{ $item->difference }}
                                        @endif
                                    </td>
                                    <td class="text-end">
                                        @if($item->adjustment_value != 0)
                                            {{ $item->adjustment_value < 0 ? '-' : '+' }} Rp {{ number_format(abs($item->adjustment_value), 0, ',', '.') }}
                                        @else
                                            -
                                        @endif
                                    </td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
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
    // Konfirmasi Hapus untuk Halaman Detail
    const deleteForm = document.querySelector('.form-delete-opname-detail');
    if (deleteForm) {
        deleteForm.addEventListener('submit', function(event) {
            event.preventDefault();
            
            Swal.fire({
                title: 'Batalkan Stock Opname?',
                text: "PERINGATAN: Stok barang akan dikembalikan ke jumlah sebelum opname ini. Jurnal penyesuaian akan dihapus/dibalik.",
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#d33',
                cancelButtonColor: '#6c757d',
                confirmButtonText: 'Ya, Hapus & Balikkan!',
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
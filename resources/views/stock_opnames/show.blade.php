@extends('layouts.app')

@section('content')
<div class="container-fluid py-2">
    
    {{-- Header --}}
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h3 class="fw-bold text-dark mb-1">Detail Opname</h3>
            <p class="text-muted mb-0 small">Nomor Dokumen: <span class="text-primary fw-bold">{{ $stockOpname->opname_number }}</span></p>
        </div>
        
        <div class="d-flex gap-2">
            <form action="{{ route('stock-opnames.destroy', $stockOpname->opname_id) }}" method="POST" class="form-delete-opname-detail">
                @csrf @method('DELETE')
                <button type="submit" class="btn btn-danger btn-sm shadow-sm">
                    <i class="bi bi-trash me-1"></i> Batalkan Opname
                </button>
            </form>

            <a href="{{ route('stock-opnames.index') }}" class="btn btn-outline-secondary btn-sm shadow-sm">
                <i class="bi bi-arrow-left me-1"></i> Kembali
            </a>
        </div>
    </div>

    <div class="row g-4">
        {{-- Info Card --}}
        <div class="col-md-4">
            <div class="card card-transaction border-0 shadow-sm h-100">
                <div class="card-header bg-white p-3 border-bottom">
                    <div class="form-section-title mb-0"><i class="bi bi-info-circle"></i> Informasi Umum</div>
                </div>
                <div class="card-body p-4">
                    <dl class="row mb-0">
                        <dt class="col-sm-4 small text-muted text-uppercase">Tanggal</dt>
                        <dd class="col-sm-8 fw-medium">{{ $stockOpname->opname_date->format('d F Y') }}</dd>

                        <dt class="col-sm-4 small text-muted text-uppercase">Petugas</dt>
                        <dd class="col-sm-8">{{ $stockOpname->user->full_name ?? 'System' }}</dd>

                        <dt class="col-sm-4 small text-muted text-uppercase">Status</dt>
                        <dd class="col-sm-8"><span class="badge bg-success bg-opacity-10 text-success border border-success border-opacity-25">Selesai</span></dd>

                        <dt class="col-sm-12 small text-muted text-uppercase mt-2">Catatan</dt>
                        <dd class="col-sm-12 bg-light p-3 rounded border border-light fst-italic small text-muted mb-0">
                            {{ $stockOpname->notes ?: 'Tidak ada catatan.' }}
                        </dd>
                    </dl>

                    <hr class="border-dashed my-4">

                    <div class="text-center">
                        <small class="text-muted d-block mb-1 text-uppercase fw-bold">Total Nilai Penyesuaian</small>
                        <h2 class="fw-bold {{ $stockOpname->total_adjustment_value < 0 ? 'text-danger' : ($stockOpname->total_adjustment_value > 0 ? 'text-success' : 'text-muted') }}">
                            {{ $stockOpname->total_adjustment_value < 0 ? '-' : '+' }} Rp {{ number_format(abs($stockOpname->total_adjustment_value), 0, ',', '.') }}
                        </h2>
                        <span class="badge rounded-pill {{ $stockOpname->total_adjustment_value < 0 ? 'bg-danger' : 'bg-success' }} bg-opacity-10 {{ $stockOpname->total_adjustment_value < 0 ? 'text-danger' : 'text-success' }}">
                            @if($stockOpname->total_adjustment_value < 0) Selisih Kurang (Rugi)
                            @elseif($stockOpname->total_adjustment_value > 0) Selisih Lebih (Untung)
                            @else Sesuai (Balance)
                            @endif
                        </span>
                    </div>
                </div>
            </div>
        </div>

        {{-- Items Table --}}
        <div class="col-md-8">
            <div class="card card-transaction border-0 shadow-sm h-100">
                <div class="card-header bg-white p-3 border-bottom">
                    <div class="form-section-title mb-0"><i class="bi bi-list-check"></i> Rincian Barang</div>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive" style="max-height: 600px;">
                        <table class="table table-hover table-transaction align-middle mb-0">
                            <thead class="bg-light sticky-top">
                                <tr>
                                    <th class="ps-4">Produk</th>
                                    <th class="text-center">System</th>
                                    <th class="text-center">Fisik</th>
                                    <th class="text-center">Selisih</th>
                                    <th class="text-end pe-4">Nilai (Rp)</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($stockOpname->items as $item)
                                <tr class="{{ $item->difference != 0 ? ($item->difference < 0 ? 'bg-danger bg-opacity-10' : 'bg-success bg-opacity-10') : '' }}">
                                    <td class="ps-4">
                                        <div class="fw-bold text-dark">{{ $item->product->product_name }}</div>
                                        <small class="text-muted">HPP: Rp {{ number_format($item->cost_per_unit, 0, ',', '.') }}</small>
                                    </td>
                                    <td class="text-center text-muted">{{ $item->system_qty }}</td>
                                    <td class="text-center fw-bold text-dark">{{ $item->physical_qty }}</td>
                                    <td class="text-center fw-bold">
                                        @if($item->difference > 0) <span class="text-success">+{{ $item->difference }}</span>
                                        @elseif($item->difference < 0) <span class="text-danger">{{ $item->difference }}</span>
                                        @else <span class="text-muted">0</span>
                                        @endif
                                    </td>
                                    <td class="text-end pe-4 fw-medium">
                                        @if($item->adjustment_value != 0)
                                            <span class="{{ $item->adjustment_value < 0 ? 'text-danger' : 'text-success' }}">
                                                {{ $item->adjustment_value < 0 ? '-' : '+' }} Rp {{ number_format(abs($item->adjustment_value), 0, ',', '.') }}
                                            </span>
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
    const deleteForm = document.querySelector('.form-delete-opname-detail');
    if (deleteForm) {
        deleteForm.addEventListener('submit', function(event) {
            event.preventDefault();
            Swal.fire({
                title: 'Batalkan Opname?',
                text: "Stok akan dikembalikan dan jurnal dihapus!",
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#d33',
                cancelButtonColor: '#6c757d',
                confirmButtonText: 'Ya, Hapus!',
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
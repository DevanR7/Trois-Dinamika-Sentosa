@extends('layouts.app')

@section('content')
<div class="container-fluid py-4">
    
    {{-- HEADER --}}
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h3 class="fw-bold text-dark mb-0">Riwayat Stock Opname</h3>
            <p class="text-muted small mb-0">Audit dan penyesuaian stok gudang</p>
        </div>
        
        <div class="d-flex gap-2">
            <a href="{{ route('stock-opnames.worksheet') }}" class="btn btn-light border shadow-sm text-dark">
                <i class="bi bi-printer me-1"></i> Cetak Worksheet
            </a>
            <a href="{{ route('stock-opnames.create') }}" class="btn btn-primary shadow-sm">
                <i class="bi bi-plus-lg me-1"></i> Mulai Opname Baru
            </a>
        </div>
    </div>

    @if(session('success'))
    <div class="alert alert-success border-0 shadow-sm d-flex align-items-center mb-4">
        <i class="bi bi-check-circle-fill fs-4 me-2"></i>
        <div>{{ session('success') }}</div>
        <button type="button" class="btn-close ms-auto" data-bs-dismiss="alert"></button>
    </div>
    @endif

    {{-- CARD LIST --}}
    <div class="card card-transaction border-0 shadow-sm">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover table-transaction align-middle mb-0">
                    <thead class="bg-light">
                        <tr>
                            <th class="ps-4">No. Opname</th>
                            <th>Tanggal</th>
                            <th>Petugas</th>
                            <th>Catatan</th>
                            <th class="text-end">Nilai Penyesuaian</th>
                            <th class="text-center">Status</th>
                            <th class="text-center pe-4">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($opnames as $opname)
                        <tr>
                            <td class="ps-4 fw-bold text-primary">{{ $opname->opname_number }}</td>
                            <td>{{ $opname->opname_date->format('d M Y') }}</td>
                            <td>
                                <div class="d-flex align-items-center">
                                    <div class="bg-light rounded-circle d-flex justify-content-center align-items-center me-2" style="width: 30px; height: 30px;">
                                        <i class="bi bi-person text-muted small"></i>
                                    </div>
                                    {{ $opname->user->full_name ?? 'System' }}
                                </div>
                            </td>
                            <td class="text-muted small">{{ Str::limit($opname->notes, 40) ?: '-' }}</td>
                            
                            <td class="text-end fw-bold {{ $opname->total_adjustment_value < 0 ? 'text-danger' : ($opname->total_adjustment_value > 0 ? 'text-success' : 'text-muted') }}">
                                {{ $opname->total_adjustment_value < 0 ? '-' : '+' }} Rp {{ number_format(abs($opname->total_adjustment_value), 0, ',', '.') }}
                            </td>
                            
                            <td class="text-center">
                                @if($opname->status == 'completed')
                                    <span class="badge bg-success bg-opacity-10 text-success border border-success border-opacity-25 rounded-pill px-3">Selesai</span>
                                @else
                                    <span class="badge bg-secondary bg-opacity-10 text-secondary border border-secondary border-opacity-25 rounded-pill px-3">{{ $opname->status }}</span>
                                @endif
                            </td>
                            
                            <td class="text-center pe-4">
                                <a href="{{ route('stock-opnames.show', $opname->opname_id) }}" class="btn btn-sm btn-light border text-primary shadow-sm" title="Detail">
                                    <i class="bi bi-eye"></i>
                                </a>
                                
                                <form action="{{ route('stock-opnames.destroy', $opname->opname_id) }}" method="POST" class="d-inline form-delete-opname">
                                    @csrf @method('DELETE')
                                    <button type="submit" class="btn btn-sm btn-light border text-danger shadow-sm ms-1" title="Hapus">
                                        <i class="bi bi-trash"></i>
                                    </button>
                                </form>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="7" class="text-center py-5 text-muted">
                                <i class="bi bi-clipboard-x fs-1 d-block mb-2 opacity-25"></i>
                                Belum ada riwayat Stock Opname.
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    
    <div class="mt-4 d-flex justify-content-center">
        {{ $opnames->links() }}
    </div>
</div>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    const deleteForms = document.querySelectorAll('.form-delete-opname');
    deleteForms.forEach(form => {
        form.addEventListener('submit', function(event) {
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
                    form.submit();
                }
            });
        });
    });
});
</script>
@endpush
@extends('layouts.app')

@section('content')
<div class="container-fluid py-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2 class="fw-bold mb-0">Riwayat Stock Opname</h2>
        
        <div class="d-flex gap-2">
            {{-- ✅ TOMBOL BARU: DOWNLOAD WORKSHEET --}}
            <a href="{{ route('stock-opnames.worksheet') }}" class="btn btn-outline-dark">
                <i class="bi bi-printer me-1"></i> Cetak Lembar Kerja
            </a>
            
            {{-- Tombol Lama --}}
            <a href="{{ route('stock-opnames.create') }}" class="btn btn-primary">
                <i class="bi bi-plus-lg me-1"></i> Mulai Stock Opname Baru
            </a>
        </div>
    </div>

    {{-- Notifikasi Sukses --}}
    @if(session('success'))
    <div class="alert alert-success alert-dismissible fade show" role="alert">
        <i class="bi bi-check-circle-fill me-2"></i> {{ session('success') }}
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
    @endif

    <div class="card shadow-sm border-0">
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-hover align-middle">
                    <thead class="table-light">
                        <tr>
                            <th>No. Opname</th>
                            <th>Tanggal</th>
                            <th>Dilakukan Oleh</th>
                            <th>Catatan</th>
                            <th class="text-end">Nilai Penyesuaian</th>
                            <th class="text-center">Status</th>
                            <th class="text-center">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($opnames as $opname)
                        <tr>
                            <td class="fw-semibold">{{ $opname->opname_number }}</td>
                            <td>{{ $opname->opname_date->format('d M Y') }}</td>
                            <td>
                                <div class="d-flex align-items-center">
                                    <div class="bg-light rounded-circle p-1 me-2 d-flex justify-content-center align-items-center" style="width: 32px; height: 32px;">
                                        <i class="bi bi-person text-secondary"></i>
                                    </div>
                                    {{ $opname->user->full_name ?? 'System' }}
                                </div>
                            </td>
                            <td>{{ Str::limit($opname->notes, 30) ?: '-' }}</td>
                            
                            {{-- Warna Nilai: Merah (Rugi), Hijau (Untung) --}}
                            <td class="text-end fw-bold {{ $opname->total_adjustment_value < 0 ? 'text-danger' : ($opname->total_adjustment_value > 0 ? 'text-success' : 'text-muted') }}">
                                {{ $opname->total_adjustment_value < 0 ? '-' : '+' }} Rp {{ number_format(abs($opname->total_adjustment_value), 0, ',', '.') }}
                            </td>
                            
                            <td class="text-center">
                                @if($opname->status == 'completed')
                                    <span class="badge bg-success"><i class="bi bi-check-circle me-1"></i> Selesai</span>
                                @else
                                    <span class="badge bg-secondary">{{ $opname->status }}</span>
                                @endif
                            </td>
                            
                            <td class="text-center">
                                {{-- Tombol Detail --}}
                                <a href="{{ route('stock-opnames.show', $opname->opname_id) }}" class="btn btn-sm btn-outline-primary" title="Lihat Detail">
                                    <i class="bi bi-eye"></i>
                                </a>
                                
                                {{-- Tombol Hapus dengan SweetAlert --}}
                                <form action="{{ route('stock-opnames.destroy', $opname->opname_id) }}" method="POST" class="d-inline form-delete-opname">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="btn btn-sm btn-outline-danger ms-1" title="Hapus & Balikkan Stok">
                                        <i class="bi bi-trash"></i>
                                    </button>
                                </form>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="7" class="text-center text-muted py-5">
                                <i class="bi bi-inbox fs-1 d-block mb-2 opacity-25"></i>
                                <p class="mb-0">Belum ada riwayat Stock Opname.</p>
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            
            <div class="mt-4">
                {{ $opnames->links() }}
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Konfirmasi Hapus untuk Tabel Index
    const deleteForms = document.querySelectorAll('.form-delete-opname');
    deleteForms.forEach(form => {
        form.addEventListener('submit', function(event) {
            event.preventDefault();
            
            Swal.fire({
                title: 'Batalkan Stock Opname?',
                text: "PERINGATAN: Stok akan dikembalikan ke posisi sebelum opname, dan Jurnal Penyesuaian akan dihapus/dibalik.",
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
@extends('layouts.app')

@section('content')
<div class="container-fluid py-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h2 class="fw-bold mb-0">Jurnal Umum Manual</h2>
            <p class="text-muted small mb-0">Kelola transaksi jurnal manual non-otomatis</p>
        </div>
        <a href="{{ route('manual-journals.create') }}" class="btn btn-dark shadow-sm">
            <i class="bi bi-plus-lg"></i> Buat Jurnal
        </a>
    </div>

    {{-- Filter Card --}}
    <div class="card border-0 shadow-sm mb-4">
        <div class="card-body">
            <form action="{{ route('manual-journals.index') }}" method="GET">
                <div class="row g-2">
                    <div class="col-md-10">
                        <div class="input-group">
                            <span class="input-group-text bg-white"><i class="bi bi-search"></i></span>
                            <input type="text" name="search" class="form-control" value="{{ request('search') }}" placeholder="Cari Nomor Jurnal atau Deskripsi...">
                        </div>
                    </div>
                    <div class="col-md-2">
                        <button type="submit" class="btn btn-dark w-100">Filter</button>
                    </div>
                </div>
            </form>
        </div>
    </div>

    {{-- Table --}}
    <div class="card border-0 shadow-sm">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-dark">
                        <tr>
                            <th class="ps-3">Tanggal</th>
                            <th>No. Jurnal</th>
                            <th>Deskripsi</th>
                            <th class="text-end">Total Nilai</th>
                            <th>User</th>
                            <th class="text-center">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($manualJournals as $journal)
                        <tr>
                            <td class="ps-3">{{ $journal->entry_date->format('d/m/Y') }}</td>
                            <td><span class="badge bg-light text-dark border">{{ $journal->journal_number }}</span></td>
                            <td>{{ Str::limit($journal->description, 60) }}</td>
                            <td class="text-end fw-bold font-monospace text-primary">
                                Rp {{ number_format($journal->total_debit, 0, ',', '.') }}
                            </td>
                            <td><small class="text-muted">{{ $journal->user->name ?? '-' }}</small></td>
                            <td class="text-center">
                                <div class="btn-group" role="group">
                                    <a href="{{ route('manual-journals.show', $journal) }}" class="btn btn-sm btn-outline-secondary" title="Detail">
                                        <i class="bi bi-eye"></i>
                                    </a>
                                    <a href="{{ route('manual-journals.edit', $journal) }}" class="btn btn-sm btn-outline-primary" title="Edit">
                                        <i class="bi bi-pencil"></i>
                                    </a>
                                    {{-- Form Delete dengan Class khusus untuk SweetAlert --}}
                                    <form action="{{ route('manual-journals.destroy', $journal) }}" method="POST" class="d-inline form-delete">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="btn btn-sm btn-outline-danger" title="Hapus">
                                            <i class="bi bi-trash"></i>
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="6" class="text-center py-5 text-muted">
                                <i class="bi bi-inbox fs-1 d-block mb-2"></i>
                                Belum ada data jurnal manual.
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
        @if($manualJournals->hasPages())
        <div class="card-footer bg-white">
            {{ $manualJournals->links() }}
        </div>
        @endif
    </div>
</div>
@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
    // 1. Konfirmasi Delete
    document.querySelectorAll('.form-delete').forEach(form => {
        form.addEventListener('submit', function(e) {
            e.preventDefault();
            Swal.fire({
                title: 'Hapus Jurnal?',
                text: "Tindakan ini akan membuat Jurnal Reversal (Pembalik). Aksi tidak dapat dibatalkan sepenuhnya.",
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#d33',
                cancelButtonColor: '#3085d6',
                confirmButtonText: 'Ya, Hapus!',
                cancelButtonText: 'Batal'
            }).then((result) => {
                if (result.isConfirmed) {
                    this.submit();
                }
            });
        });
    });

    // 2. Flash Message Success (Dari Controller)
    @if(session('success'))
        Swal.fire({
            icon: 'success',
            title: 'Berhasil!',
            text: "{{ session('success') }}",
            timer: 3000,
            showConfirmButton: false
        });
    @endif

    // 3. Flash Message Error
    @if(session('error'))
        Swal.fire({
            icon: 'error',
            title: 'Gagal!',
            text: "{{ session('error') }}",
        });
    @endif
</script>
@endpush
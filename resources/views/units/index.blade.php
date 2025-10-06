@extends('layouts.app')

@section('content')
<div class="container py-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2 class="fw-bold">Kelola Satuan</h2>
        <a href="{{ route('units.create') }}" class="btn btn-primary">
            <i class="bi bi-plus-circle me-2"></i>Tambah Satuan Baru
        </a>
    </div>

    @if (session('success'))<div class="alert alert-success">{{ session('success') }}</div>@endif
    @if (session('error'))<div class="alert alert-danger">{{ session('error') }}</div>@endif
    
    <div class="card shadow-sm border-0">
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-hover align-middle">
                    <thead class="table-light">
                        <tr>
                            <th>No.</th>
                            <th>Nama Satuan</th>
                            <th class="text-center">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($units as $unit)
                        <tr>
                            <td>{{ $loop->iteration + $units->firstItem() - 1 }}</td>
                            <td>{{ $unit->name }}</td>
                            <td class="text-center">
                                <div class="d-flex justify-content-center gap-1">
                                    <a href="{{ route('units.edit', $unit->unit_id) }}" class="btn btn-sm btn-outline-secondary" title="Edit"><i class="bi bi-pencil-square"></i></a>
                                    
                                    {{-- PERUBAHAN DI SINI: Menghapus onsubmit="..." dan menambahkan class="delete-form" --}}
                                    <form action="{{ route('units.destroy', $unit->unit_id) }}" method="POST" class="d-inline delete-form">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="btn btn-sm btn-outline-danger" title="Hapus"><i class="bi bi-trash"></i></button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                        @empty
                        <tr><td colspan="3" class="text-center">Tidak ada data satuan.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    <div class="mt-4 d-flex justify-content-center">
        {{ $units->links() }}
    </div>
</div>
@endsection

{{-- ============================================= --}}
{{-- SCRIPT BARU UNTUK KONFIRMASI SWEETALERT --}}
{{-- ============================================= --}}
@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Cari semua form yang memiliki class 'delete-form'
    const deleteForms = document.querySelectorAll('.delete-form');

    deleteForms.forEach(form => {
        form.addEventListener('submit', function(event) {
            // Hentikan aksi default dari form (yaitu submit langsung)
            event.preventDefault(); 
            
            Swal.fire({
                title: 'Anda Yakin?',
                text: "Data yang sudah dihapus tidak bisa dikembalikan!",
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#d33',
                cancelButtonColor: '#6c757d',
                confirmButtonText: 'Ya, Hapus!',
                cancelButtonText: 'Batal'
            }).then((result) => {
                // Jika user menekan tombol "Ya, Hapus!"
                if (result.isConfirmed) {
                    // Lanjutkan proses submit form
                    event.target.submit();
                }
            });
        });
    });
});
</script>
@endpush
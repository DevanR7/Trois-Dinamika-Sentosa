@extends('layouts.app')
@section('content')
<div class="container py-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2 class="fw-bold">Manajemen Role</h2>
        <a href="{{ route('roles.create') }}" class="btn btn-primary"><i class="bi bi-plus-circle me-2"></i>Tambah Role Baru</a>
    </div>
    @if (session('success'))<div class="alert alert-success">{{ session('success') }}</div>@endif
    @if (session('error'))<div class="alert alert-danger">{{ session('error') }}</div>@endif
    <div class="card shadow-sm border-0">
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-hover align-middle">
                    <thead class="table-light">
                        <tr>
                            <th>Nama Role</th>
                            <th>Hak Akses (Permissions)</th>
                            <th class="text-center">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($roles as $role)
                        <tr>
                            <td class="fw-semibold">{{ Str::title($role->name) }}</td>
                            <td>
                                @foreach($role->permissions as $permission)
                                    <span class="badge bg-secondary">{{ $permission->name }}</span>
                                @endforeach
                            </td>
                            <td class="text-center">
                                <div class="d-flex justify-content-center gap-1">
                                    <a href="{{ route('roles.edit', $role->id) }}" class="btn btn-sm btn-outline-secondary" title="Edit"><i class="bi bi-pencil-square"></i></a>
                                    
                                    {{-- Menghapus onsubmit dan menambahkan class="delete-form" --}}
                                    <form action="{{ route('roles.destroy', $role->id) }}" method="POST" class="d-inline delete-form">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="btn btn-sm btn-outline-danger" title="Hapus"><i class="bi bi-trash"></i></button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                        @empty
                        <tr><td colspan="3" class="text-center">Tidak ada data role.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
{{-- SCRIPT BARU UNTUK KONFIRMASI SWEETALERT --}}
<script>
document.addEventListener('DOMContentLoaded', function() {
    const deleteForms = document.querySelectorAll('.delete-form');
    deleteForms.forEach(form => {
        form.addEventListener('submit', function(event) {
            event.preventDefault();
            
            Swal.fire({
                title: 'Anda Yakin?',
                text: "Role yang dihapus tidak bisa dikembalikan!",
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#d33',
                cancelButtonColor: '#6c757d',
                confirmButtonText: 'Ya, Hapus!',
                cancelButtonText: 'Batal'
            }).then((result) => {
                if (result.isConfirmed) {
                    event.target.submit();
                }
            });
        });
    });
});
</script>
@endpush
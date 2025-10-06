@extends("layouts.app")

@section("content")
    <div class="container py-4">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h2 class="fw-bold">Manajemen User</h2>
            <a href="{{ route("users.create") }}" class="btn btn-primary">
                <i class="bi bi-plus-circle me-2"></i> Tambah User Baru
            </a>
        </div>

        @if (session('success')) <div class="alert alert-success">{{ session('success') }}</div> @endif
        @if (session('error')) <div class="alert alert-danger">{{ session('error') }}</div> @endif

        <div class="card shadow-sm border-0">
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-hover align-middle">
                        <thead class="table-light">
                            <tr>
                                <th>#</th>
                                <th>Nama Lengkap</th>
                                <th>Username</th>
                                <th>Email</th>
                                <th class="text-center">Role</th>
                                <th class="text-center">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($users as $user)
                                <tr>
                                    <td>{{ $loop->iteration + $users->firstItem() - 1 }}</td>
                                    <td>{{ $user->full_name }}</td>
                                    <td>{{ $user->username }}</td>
                                    <td>{{ $user->email }}</td>
                                    <td class="text-center">
                                        @foreach($user->getRoleNames() as $role)
                                            <span class="badge bg-info text-dark">{{ Str::title($role) }}</span>
                                        @endforeach
                                    </td>
                                    <td class="text-center">
                                        <div class="d-flex justify-content-center gap-1">
                                            <a href="{{ route("users.edit", $user->user_id) }}" class="btn btn-sm btn-outline-secondary" title="Edit">
                                                <i class="bi bi-pencil-square"></i>
                                            </a>
                                            {{-- Menghapus onsubmit dan menambahkan class="delete-form" --}}
                                            <form action="{{ route("users.destroy", $user->user_id) }}" method="POST" class="d-inline delete-form">
                                                @csrf
                                                @method("DELETE")
                                                <button type="submit" class="btn btn-sm btn-outline-danger" title="Hapus">
                                                    <i class="bi bi-trash"></i>
                                                </button>
                                            </form>
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr><td colspan="6" class="text-center">Tidak ada data user.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <div class="mt-4 d-flex justify-content-center">
            {{ $users->links() }}
        </div>
    </div>
@endsection

@push('scripts')
{{-- Script untuk konfirmasi hapus dengan SweetAlert --}}
<script>
document.addEventListener('DOMContentLoaded', function() {
    const deleteForms = document.querySelectorAll('.delete-form');
    deleteForms.forEach(form => {
        form.addEventListener('submit', function(event) {
            event.preventDefault();
            
            Swal.fire({
                title: 'Anda Yakin?',
                text: "User yang dihapus tidak bisa dikembalikan!",
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
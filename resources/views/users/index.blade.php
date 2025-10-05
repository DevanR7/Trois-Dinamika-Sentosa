@extends("layouts.app")

@section("content")
    <div class="container-fluid">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h2 class="fw-bold">Manajemen User</h2>
            <a href="{{ route("users.create") }}" class="btn btn-primary">
                <i class="bi bi-plus-circle me-2"></i>
                Tambah User Baru
            </a>
        </div>

        @if (session("success"))
            <div class="alert alert-success">
                {{ session("success") }}
            </div>
        @endif

        <div class="card shadow-sm">
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
                                    <th>
                                        {{ $loop->iteration + $users->firstItem() - 1 }}
                                    </th>
                                    <td>{{ $user->full_name }}</td>
                                    <td>{{ $user->username }}</td>
                                    <td>{{ $user->email }}</td>
                                    <td class="text-center">
                                        <span class="badge bg-info">
                                            {{ Str::title($user->role) }}
                                        </span>
                                    </td>
                                    <td class="text-center">
                                        <form
                                            action="{{ route("users.destroy", $user->user_id) }}"
                                            method="POST"
                                            onsubmit="return confirm('Apakah Anda yakin ingin menghapus user ini?');"
                                        >
                                            <a
                                                href="{{ route("users.edit", $user->user_id) }}"
                                                class="btn btn-sm btn-outline-secondary"
                                            >
                                                <i
                                                    class="bi bi-pencil-square"
                                                ></i>
                                                Edit
                                            </a>
                                            @csrf
                                            @method("DELETE")
                                            <button
                                                type="submit"
                                                class="btn btn-sm btn-outline-danger"
                                            >
                                                <i class="bi bi-trash"></i>
                                                Hapus
                                            </button>
                                        </form>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="6" class="text-center">
                                        Tidak ada data user.
                                    </td>
                                </tr>
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

@extends('layouts.app') {{-- Sesuaikan dengan layout admin Anda --}}

@section('content')
<div class="container py-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2 class="fw-bold">Kelola User (Staf & Admin)</h2>
        {{-- Pastikan user memiliki izin untuk membuat user baru --}}
        @can('manage-users') 
            <a href="{{ route('users.create') }}" class="btn btn-primary">
                <i class="bi bi-plus-circle me-2"></i>Tambah User Baru
            </a>
        @endcan
    </div>

    {{-- Notifikasi Sukses --}}
    @if (session('success'))
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            {{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

    {{-- Notifikasi Error --}}
    @if (session('error'))
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            {{ session('error') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif
    
    <div class="card shadow-sm border-0">
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-hover align-middle">
                    <thead class="table-light">
                        <tr>
                            <th>No.</th>
                            <th>Nama Lengkap</th>
                            <th>Username</th>
                            <th>Email</th>
                            <th>Role</th>
                            <th class="text-center">Status</th>
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
                            <td>
                                {{-- Ambil nama role pertama (jika ada) --}}
                                {{ $user->getRoleNames()->first() ?? '-' }}
                            </td>
                            <td class="text-center">
                                @if($user->is_approved)
                                    <span class="badge bg-success">Disetujui</span>
                                @else
                                    {{-- Cek apakah dia admin, admin tidak perlu approval --}}
                                    {{-- Sesuikan nama role 'Admin', 'Superadmin' --}}
                                    @if($user->hasRole(['admin', 'superadmin']))
                                        <span class="badge bg-primary">Admin</span>
                                    @else
                                        <span class="badge bg-warning text-dark">Menunggu Persetujuan</span>
                                    @endif
                                @endif
                            </td>
                            <td class="text-center">
                                <div class="d-flex justify-content-center gap-1">
                                    
                                    {{-- Tampilkan tombol 'Setujui' HANYA jika:
                                         1. User BELUM disetujui
                                         2. User BUKAN role Admin/Superadmin
                                    --}}
                                    @if(!$user->is_approved && !$user->hasRole(['admin', 'superadmin']))
                                        @can('manage-users') {{-- Hanya yang bisa manage-users boleh menyetujui --}}
                                        <form action="{{ route('users.approve', $user->user_id) }}" method="POST" onsubmit="return confirm('Anda yakin ingin menyetujui user ini?');">
                                            @csrf
                                            @method('PATCH')
                                            <button type="submit" class="btn btn-sm btn-success" title="Setujui User">
                                                <i class="bi bi-check-circle"></i>
                                            </button>
                                        </form>
                                        @endcan
                                    @endif
                                
                                    @can('manage-users') {{-- Hanya yang bisa manage-users boleh edit/hapus --}}
                                    <a href="{{ route('users.edit', $user->user_id) }}" class="btn btn-sm btn-outline-secondary" title="Edit">
                                        <i class="bi bi-pencil-square"></i>
                                    </a>
                                    
                                    {{-- Jangan biarkan user hapus diri sendiri --}}
                                    @if(Auth::id() !== $user->user_id)
                                    <form action="{{ route('users.destroy', $user->user_id) }}" method="POST" onsubmit="return confirm('Anda yakin ingin menghapus user ini? Ini tidak bisa dibatalkan.');">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="btn btn-sm btn-outline-danger" title="Hapus">
                                            <i class="bi bi-trash"></i>
                                        </button>
                                    </form>
                                    @endif
                                    @endcan

                                </div>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="7" class="text-center">Tidak ada data user.</td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    <div class="mt-4 d-flex justify-content-center">
        {{-- Tampilkan pagination --}}
        {{ $users->appends(request()->query())->links() }}
    </div>
</div>
@endsection
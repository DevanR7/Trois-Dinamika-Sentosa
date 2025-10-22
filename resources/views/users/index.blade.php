@extends('layouts.app') {{-- Sesuaikan dengan layout admin Anda --}}

@section('content')
<div class="container py-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2 class="fw-bold">Kelola User (Staf & Admin)</h2>
        <div>
            {{-- ✅ TAMBAHKAN LINK UNTUK MELIHAT ARSIP/DATA TERHAPUS --}}
            @if(request('status') === 'deleted')
                <a href="{{ route('users.index') }}" class="btn btn-outline-secondary">
                    <i class="bi bi-arrow-left me-2"></i>Kembali ke User Aktif
                </a>
            @else
                <a href="{{ route('users.index', ['status' => 'deleted']) }}" class="btn btn-outline-secondary">
                    <i class="bi bi-archive me-2"></i>Lihat Arsip User
                </a>
                @can('manage-users')
                <a href="{{ route('users.create') }}" class="btn btn-primary">
                    <i class="bi bi-plus-circle me-2"></i>Tambah User Baru
                </a>
                @endcan
            @endif
        </div>
    </div>
    
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
                            <td>{{ $user->getRoleNames()->first() ?? '-' }}</td>
                            <td class="text-center">
                                {{-- ✅ TAMBAHKAN LOGIKA UNTUK STATUS TERHAPUS --}}
                                @if($user->trashed())
                                    <span class="badge bg-danger">Telah Dihapus</span>
                                @elseif($user->is_approved)
                                    <span class="badge bg-success">Disetujui</span>
                                @else
                                    @if($user->hasRole(['admin', 'superadmin']))
                                        <span class="badge bg-primary">Admin</span>
                                    @else
                                        <span class="badge bg-warning text-dark">Menunggu Persetujuan</span>
                                    @endif
                                @endif
                            </td>
                            <td class="text-center">
                                <div class="d-flex justify-content-center gap-1">
                                    
                                    {{-- ✅ LOGIKA BARU: TAMPILKAN TOMBOL BERDASARKAN STATUS --}}
                                    @if($user->trashed())
                                        {{-- JIKA TERHAPUS: Tampilkan tombol RESTORE --}}
                                        @can('manage-users')
                                        <form action="{{ route('users.restore', $user->user_id) }}" method="POST" class="form-restore d-inline">
                                            @csrf
                                            @method('PATCH')
                                            <button type="submit" class="btn btn-sm btn-info" data-name="{{ $user->full_name }}">Pulihkan</button>
                                        </form>
                                        @endcan
                                    @else
                                        {{-- JIKA AKTIF: Tampilkan tombol seperti biasa --}}
                                        @if(!$user->is_approved && !$user->hasRole(['admin', 'superadmin']))
                                            @can('manage-users')
                                            <form action="{{ route('users.approve', $user->user_id) }}" method="POST" class="form-approve d-inline">
                                                @csrf
                                                @method('PATCH')
                                                <button type="submit" class="btn btn-sm btn-success" title="Setujui User" data-name="{{ $user->full_name }}">
                                                    <i class="bi bi-check-circle"></i>
                                                </button>
                                            </form>
                                            @endcan
                                        @endif
                                    
                                        @can('manage-users')
                                        <a href="{{ route('users.edit', $user->user_id) }}" class="btn btn-sm btn-outline-secondary" title="Edit">
                                            <i class="bi bi-pencil-square"></i>
                                        </a>
                                        
                                        @if(Auth::id() !== $user->user_id)
                                        <form action="{{ route('users.destroy', $user->user_id) }}" method="POST" class="form-delete d-inline">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="btn btn-sm btn-outline-danger" title="Hapus" data-name="{{ $user->full_name }}">
                                                <i class="bi bi-trash"></i>
                                            </button>
                                        </form>
                                        @endif
                                        @endcan
                                    @endif
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
        {{ $users->appends(request()->query())->links() }}
    </div>
</div>
@endsection


@push('scripts')
{{-- Pastikan SweetAlert2 sudah di-load di layout utama Anda --}}
<script>
    document.addEventListener('DOMContentLoaded', function() {
        
        // --- 1. NOTIFIKASI TOAST (SETELAH AKSI) ---
        @if(session('success'))
            Swal.fire({
                toast: true,
                position: 'top-end',
                icon: 'success',
                title: '{{ session('success') }}',
                showConfirmButton: false,
                timer: 3000,
                timerProgressBar: true
            });
        @endif
        @if(session('error'))
            Swal.fire({
                toast: true,
                position: 'top-end',
                icon: 'error',
                title: '{{ session('error') }}',
                showConfirmButton: false,
                timer: 5000,
                timerProgressBar: true
            });
        @endif


        // --- 2. KONFIRMASI "SETUJUI" ---
        document.querySelectorAll('.form-approve').forEach(form => {
            form.addEventListener('submit', function(e) {
                e.preventDefault();
                const userName = this.querySelector('button').dataset.name;
                Swal.fire({
                    title: `Setujui User Ini?`,
                    text: `Anda yakin ingin menyetujui user "${userName}"?`,
                    icon: 'question',
                    showCancelButton: true,
                    confirmButtonColor: '#28a745',
                    cancelButtonColor: '#6c757d',
                    confirmButtonText: 'Ya, Setujui!',
                    cancelButtonText: 'Batal'
                }).then((result) => {
                    if (result.isConfirmed) {
                        this.submit();
                    }
                });
            });
        });

        
        // --- 3. KONFIRMASI "DELETE" (ARSIPKAN) ---
        document.querySelectorAll('.form-delete').forEach(form => {
            form.addEventListener('submit', function(e) {
                e.preventDefault();
                const userName = this.querySelector('button').dataset.name;
                Swal.fire({
                    title: 'Anda Yakin?',
                    text: `Anda akan mengarsipkan user "${userName}".`, // Teks diubah, karena ini soft delete
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonColor: '#d33',
                    cancelButtonColor: '#6c757d',
                    confirmButtonText: 'Ya, Arsipkan!', // Teks tombol diubah
                    cancelButtonText: 'Batal'
                }).then((result) => {
                    if (result.isConfirmed) {
                        this.submit();
                    }
                });
            });
        });

        // --- 4. KONFIRMASI "PULIHKAN/RESTORE" ---
        document.querySelectorAll('.form-restore').forEach(form => {
            form.addEventListener('submit', function(e) {
                e.preventDefault();
                const userName = this.querySelector('button').dataset.name;
                Swal.fire({
                    title: 'Pulihkan User Ini?',
                    text: `Anda akan memulihkan akun untuk user "${userName}".`,
                    icon: 'info',
                    showCancelButton: true,
                    confirmButtonColor: '#17a2b8', // Biru-Info
                    cancelButtonColor: '#6c757d',
                    confirmButtonText: 'Ya, Pulihkan!',
                    cancelButtonText: 'Batal'
                }).then((result) => {
                    if (result.isConfirmed) {
                        this.submit();
                    }
                });
            });
        });

    });
</script>
@endpush
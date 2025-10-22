@extends('layouts.app') {{-- Sesuaikan dengan layout admin Anda --}}

@section('content')
<div class="container py-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2 class="fw-bold">Kelola User (Staf & Admin)</h2>
        @can('manage-users') 
            <a href="{{ route('users.create') }}" class="btn btn-primary">
                <i class="bi bi-plus-circle me-2"></i>Tambah User Baru
            </a>
        @endcan
    </div>

    {{-- 
      Kita TIDAK PERLU lagi notifikasi Bootstrap ini, 
      karena akan digantikan oleh SweetAlert Toast.
      Anda bisa menghapusnya atau membiarkannya sebagai fallback.
    --}}
    {{-- @if (session('success'))
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            {{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif
    @if (session('error'))
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            {{ session('error') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif --}}
    
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
                                @if($user->is_approved)
                                    <span class="badge bg-success">{{ __('Disetujui') }}</span>
                                @else
                                    @if($user->hasRole(['admin', 'superadmin']))
                                        <span class="badge bg-primary">Admin</span>
                                    @else
                                        <span class="badge bg-warning text-dark">{{ __('Menunggu Persetujuan') }}</span>
                                    @endif
                                @endif
                            </td>
                            <td class="text-center">
                                <div class="d-flex justify-content-center gap-1">
                                    
                                    @if(!$user->is_approved && !$user->hasRole(['admin', 'superadmin']))
                                        @can('manage-users')
                                        {{-- ✅ PERUBAHAN: Hapus onsubmit, tambahkan class 'form-approve' dan 'd-inline' --}}
                                        <form action="{{ route('users.approve', $user->user_id) }}" method="POST" class="form-approve d-inline">
                                            @csrf
                                            @method('PATCH')
                                            {{-- ✅ TAMBAHKAN data-name untuk pesan dinamis --}}
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
                                    {{-- ✅ PERUBAHAN: Hapus onsubmit, tambahkan class 'form-delete' dan 'd-inline' --}}
                                    <form action="{{ route('users.destroy', $user->user_id) }}" method="POST" class="form-delete d-inline">
                                        @csrf
                                        @method('DELETE')
                                        {{-- ✅ TAMBAHKAN data-name untuk pesan dinamis --}}
                                        <button type="submit" class="btn btn-sm btn-outline-danger" title="Hapus" data-name="{{ $user->full_name }}">
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
        {{ $users->appends(request()->query())->links() }}
    </div>
</div>
@endsection


{{-- ✅ TAMBAHKAN SEMUA JAVASCRIPT DI BAWAH INI --}}
@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function() {
        
        // --- 1. NOTIFIKASI TOAST (SETELAH AKSI) ---
        // Cek jika ada session 'success'
        @if(session('success'))
            Swal.fire({
                toast: true,
                position: 'top-end',
                icon: 'success',
                title: '{{ session('success') }}',
                showConfirmButton: false,
                timer: 3000, // 3 detik
                timerProgressBar: true
            });
        @endif

        // Cek jika ada session 'error'
        @if(session('error'))
            Swal.fire({
                toast: true,
                position: 'top-end',
                icon: 'error',
                title: '{{ session('error') }}',
                showConfirmButton: false,
                timer: 5000, // 5 detik
                timerProgressBar: true
            });
        @endif


        // --- 2. KONFIRMASI AKSI "SETUJUI" ---
        // Tangkap semua form dengan class 'form-approve'
        document.querySelectorAll('.form-approve').forEach(form => {
            form.addEventListener('submit', function(e) {
                e.preventDefault(); // Hentikan submit form
                
                // Ambil nama user dari data-name di tombol
                const userName = this.querySelector('button').dataset.name;
                
                Swal.fire({
                    title: `Setujui User Ini?`,
                    text: `Anda yakin ingin menyetujui user "${userName}"?`,
                    icon: 'question',
                    showCancelButton: true,
                    confirmButtonColor: '#28a745', // Warna hijau
                    cancelButtonColor: '#6c757d',
                    confirmButtonText: 'Ya, Setujui!',
                    cancelButtonText: 'Batal'
                }).then((result) => {
                    if (result.isConfirmed) {
                        this.submit(); // Lanjutkan submit form
                    }
                });
            });
        });

        
        // --- 3. KONFIRMASI AKSI "DELETE" ---
        // Tangkap semua form dengan class 'form-delete'
        document.querySelectorAll('.form-delete').forEach(form => {
            form.addEventListener('submit', function(e) {
                e.preventDefault(); // Hentikan submit form
                
                // Ambil nama user dari data-name di tombol
                const userName = this.querySelector('button').dataset.name;

                Swal.fire({
                    title: 'Anda Yakin?',
                    text: `Anda akan menghapus user "${userName}". Tindakan ini tidak bisa dibatalkan!`,
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonColor: '#d33', // Warna merah
                    cancelButtonColor: '#6c757d',
                    confirmButtonText: 'Ya, Hapus!',
                    cancelButtonText: 'Batal'
                }).then((result) => {
                    if (result.isConfirmed) {
                        this.submit(); // Lanjutkan submit form
                    }
                });
            });
        });

    });
</script>
@endpush
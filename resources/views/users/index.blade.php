@extends('layouts.app') {{-- Sesuaikan dengan layout admin Anda --}}

@section('content')
<div class="container py-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2 class="fw-bold">Kelola User (Staf & Admin)</h2>
        <div>
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
    
    {{-- ✅ MENGGANTI TABLE DENGAN ACCORDION --}}
    <div class="accordion shadow-sm" id="usersAccordion">
        
        @forelse ($users as $user)
        <div class="accordion-item">
            <h2 class="accordion-header" id="heading-{{ $user->user_id }}">
                {{-- TAMPILAN TERTUTUP (COLLAPSED) --}}
                <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" 
                        data-bs-target="#collapse-{{ $user->user_id }}" aria-expanded="false" 
                        aria-controls="collapse-{{ $user->user_id }}">
                    
                    <div class="d-flex justify-content-between w-100 align-items-center">
                        
                        {{-- Kiri: Nama & Username --}}
                        <div class="d-flex flex-column">
                            <strong class="fs-5">{{ $user->full_name }}</strong>
                            <span class="text-muted small">{{ $user->username }}</span>
                        </div>
                        
                        {{-- Kanan: Role & Status --}}
                        <div class="me-3">
                            <span class="badge bg-secondary rounded-pill me-2">
                                <i class="bi bi-shield-check me-1"></i>
                                {{ $user->getRoleNames()->first() ?? '-' }}
                            </span>

                            @if($user->trashed())
                                <span class="badge bg-danger rounded-pill">Telah Dihapus</span>
                            @elseif($user->is_approved)
                                <span class="badge bg-success rounded-pill">Disetujui</span>
                            @else
                                @if($user->hasRole(['admin', 'superadmin']))
                                    <span class="badge bg-primary rounded-pill">Admin</span>
                                @else
                                    <span class="badge bg-warning text-dark rounded-pill">Menunggu Persetujuan</span>
                                @endif
                            @endif
                        </div>
                    </div>
                </button>
            </h2>

            {{-- TAMPILAN TERBUKA (EXPANDED) --}}
            <div id="collapse-{{ $user->user_id }}" class="accordion-collapse collapse" 
                 aria-labelledby="heading-{{ $user->user_id }}" data-bs-parent="#usersAccordion">
                <div class="accordion-body">
                    <div class="row">
                        <div class="col-md-6">
                            <strong>Email</strong>
                            <p>{{ $user->email ?? '-' }}</p>
                            
                            <strong>NIK</strong>
                            <p>{{ $user->nik ?? '-' }}</p>
                        </div>
                        <div class="col-md-6">
                            <strong>No. Telepon</strong>
                            <p>{{ $user->phone_number ?? '-' }}</p>
                            
                            <strong>Alamat</strong>
                            <p class="mb-0">{{ $user->address ?? '-' }}</p>
                        </div>
                    </div>
                    
                    <hr class="my-3">
                    
                    {{-- Tombol Aksi --}}
                    <strong>Aksi:</strong>
                    <div class="d-flex justify-content-start flex-wrap gap-2 mt-2">
                        @if($user->trashed())
                            {{-- JIKA TERHAPUS: Tampilkan tombol RESTORE --}}
                            @can('manage-users')
                            <form action="{{ route('users.restore', $user->user_id) }}" method="POST" class="form-restore d-inline">
                                @csrf
                                @method('PATCH')
                                <button type="submit" class="btn btn-sm btn-info" data-name="{{ $user->full_name }}">
                                    <i class="bi bi-arrow-counterclockwise me-1"></i> Pulihkan
                                </button>
                            </form>
                            @endcan
                        @else
                            {{-- JIKA AKTIF: Tampilkan tombol seperti biasa --}}
                            
                            {{-- Tombol Setujui --}}
                            @if(!$user->is_approved && !$user->hasRole(['admin', 'superadmin']))
                                @can('manage-users')
                                <form action="{{ route('users.approve', $user->user_id) }}" method="POST" class="form-approve d-inline">
                                    @csrf
                                    @method('PATCH')
                                    <button type="submit" class="btn btn-sm btn-success" title="Setujui User" data-name="{{ $user->full_name }}">
                                        <i class="bi bi-check-circle me-1"></i> Setujui
                                    </button>
                                </form>
                                @endcan
                            @endif
                        
                            {{-- Tombol Edit --}}
                            @can('manage-users')
                            <a href="{{ route('users.edit', $user->user_id) }}" class="btn btn-sm btn-outline-secondary" title="Edit">
                                <i class="bi bi-pencil-square me-1"></i> Edit
                            </a>
                            
                            {{-- Tombol Hapus/Arsipkan --}}
                            @if(Auth::id() !== $user->user_id)
                            <form action="{{ route('users.destroy', $user->user_id) }}" method="POST" class="form-delete d-inline">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="btn btn-sm btn-outline-danger" title="Hapus" data-name="{{ $user->full_name }}">
                                    <i class="bi bi-archive me-1"></i> Arsipkan
                                </button>
                            </form>
                            @endif
                            @endcan
                        @endif
                    </div>
                </div>
            </div>
        </div>
        @empty
        {{-- Tampilan jika tidak ada user --}}
        <div class="accordion-item">
            <div class="accordion-header">
                <button class="accordion-button collapsed" type="button" disabled>
                    Tidak ada data user.
                </button>
            </div>
        </div>
        @endforelse

    </div>
    
    <div class="mt-4 d-flex justify-content-center">
        {{ $users->appends(request()->query())->links() }}
    </div>
</div>
@endsection


@push('scripts')
{{-- SCRIPT SWEETALERT ANDA TIDAK PERLU DIUBAH, SUDAH BERFUNGSI --}}
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
                    text: `Anda akan mengarsipkan user "${userName}".`, 
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonColor: '#d33',
                    cancelButtonColor: '#6c757d',
                    confirmButtonText: 'Ya, Arsipkan!', 
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
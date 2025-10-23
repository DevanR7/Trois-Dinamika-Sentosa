@extends('layouts.app')

@section('content')
<div class="container py-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2 class="fw-bold">Manajemen Pengumuman</h2>
        <div>
            @if(request('status') === 'deleted')
                <a href="{{ route('announcements.index') }}" class="btn btn-outline-secondary">
                    <i class="bi bi-arrow-left me-2"></i>Kembali ke Pengumuman Aktif
                </a>
            @else
                <a href="{{ route('announcements.index', ['status' => 'deleted']) }}" class="btn btn-outline-secondary">
                    <i class="bi bi-archive me-2"></i>Lihat Arsip
                </a>
                <a href="{{ route('announcements.create') }}" class="btn btn-primary">
                    <i class="bi bi-plus-circle me-2"></i>Buat Pengumuman Baru
                </a>
            @endif
        </div>
    </div>
    
    <div class="card shadow-sm border-0">
        <div class="card-body">
            @if($announcements->isEmpty() && request('status') !== 'deleted')
                <div class="alert alert-info text-center">Belum ada pengumuman aktif.</div>
            @elseif($announcements->isEmpty() && request('status') === 'deleted')
                 <div class="alert alert-secondary text-center">Arsip pengumuman kosong.</div>
            @else
            <div class="list-group list-group-flush">
                @foreach ($announcements as $announcement)
                <div class="list-group-item d-flex justify-content-between align-items-start py-3">
                    <div class="ms-2 me-auto">
                        <div class="fw-bold fs-5">
                            {{ $announcement->title ?? 'Tanpa Judul' }} 
                            @if($announcement->trashed())
                                <span class="badge bg-danger ms-2">Diarsipkan</span>
                            @elseif($announcement->is_active)
                                <span class="badge bg-success ms-2">Aktif</span>
                            @else
                                <span class="badge bg-secondary ms-2">Nonaktif</span>
                            @endif
                        </div>
                        <p class="mb-1 text-muted">{{ Str::limit($announcement->content, 150) }}</p>
                        <small>
                            @if($announcement->type == 'broadcast')
                                <i class="bi bi-broadcast text-primary me-1"></i> Broadcast
                            @else
                                <i class="bi bi-people text-info me-1"></i> Targeted ({{ $announcement->clients->count() }} Klien)
                            @endif
                             - Dibuat: {{ $announcement->created_at->format('d M Y H:i') }}
                             @if($announcement->trashed())
                               - Diarsipkan: {{ $announcement->deleted_at->format('d M Y H:i') }}
                             @endif
                        </small>
                    </div>
                    {{-- Tombol Aksi --}}
                    <div class="d-flex flex-column gap-2 ms-3">
                        @if($announcement->trashed())
                            {{-- Restore & Force Delete --}}
                             <form action="{{ route('announcements.restore', $announcement->id) }}" method="POST" class="form-restore d-inline">
                                @csrf @method('PATCH')
                                <button type="submit" class="btn btn-sm btn-info w-100" data-title="{{ $announcement->title ?? 'ini' }}">
                                    <i class="bi bi-arrow-counterclockwise"></i> Pulihkan
                                </button>
                            </form>
                            <form action="{{ route('announcements.forceDelete', $announcement->id) }}" method="POST" class="form-force-delete d-inline">
                                @csrf @method('DELETE')
                                <button type="submit" class="btn btn-sm btn-danger w-100" data-title="{{ $announcement->title ?? 'ini' }}">
                                    <i class="bi bi-trash3-fill"></i> Hapus Permanen
                                </button>
                            </form>
                        @else
                            {{-- Edit & Delete (Arsipkan) --}}
                            <a href="{{ route('announcements.edit', $announcement->id) }}" class="btn btn-sm btn-outline-secondary w-100">
                                <i class="bi bi-pencil-square"></i> Edit
                            </a>
                            <form action="{{ route('announcements.destroy', $announcement->id) }}" method="POST" class="form-delete d-inline">
                                @csrf @method('DELETE')
                                <button type="submit" class="btn btn-sm btn-outline-danger w-100" data-title="{{ $announcement->title ?? 'ini' }}">
                                    <i class="bi bi-archive"></i> Arsipkan
                                </button>
                            </form>
                        @endif
                    </div>
                </div>
                @endforeach
            </div>
            @endif
        </div>
    </div>
    
    {{-- Pagination --}}
    <div class="mt-4 d-flex justify-content-center">
        {{ $announcements->appends(request()->query())->links() }}
    </div>
</div>
@endsection

@push('scripts')
{{-- SweetAlert2 harus sudah ada di layout utama --}}
<script>
    document.addEventListener('DOMContentLoaded', function() {
        
        // --- NOTIFIKASI TOAST ---
        @if(session('success')) Swal.fire({ toast: true, position: 'top-end', icon: 'success', title: '{{ session('success') }}', showConfirmButton: false, timer: 3000, timerProgressBar: true }); @endif
        @if(session('error')) Swal.fire({ toast: true, position: 'top-end', icon: 'error', title: '{{ session('error') }}', showConfirmButton: false, timer: 5000, timerProgressBar: true }); @endif

        // --- KONFIRMASI DELETE (ARSIPKAN) ---
        document.querySelectorAll('.form-delete').forEach(form => {
            form.addEventListener('submit', function(e) {
                e.preventDefault();
                const title = this.querySelector('button').dataset.title;
                Swal.fire({
                    title: 'Arsipkan Pengumuman?', text: `Anda akan mengarsipkan pengumuman "${title}".`, icon: 'warning',
                    showCancelButton: true, confirmButtonColor: '#d33', cancelButtonColor: '#6c757d',
                    confirmButtonText: 'Ya, Arsipkan!', cancelButtonText: 'Batal'
                }).then((result) => { if (result.isConfirmed) { this.submit(); } });
            });
        });

        // --- KONFIRMASI RESTORE ---
        document.querySelectorAll('.form-restore').forEach(form => {
            form.addEventListener('submit', function(e) {
                e.preventDefault();
                const title = this.querySelector('button').dataset.title;
                 Swal.fire({
                    title: 'Pulihkan Pengumuman?', text: `Anda akan memulihkan pengumuman "${title}".`, icon: 'info',
                    showCancelButton: true, confirmButtonColor: '#17a2b8', cancelButtonColor: '#6c757d',
                    confirmButtonText: 'Ya, Pulihkan!', cancelButtonText: 'Batal'
                }).then((result) => { if (result.isConfirmed) { this.submit(); } });
            });
        });

        // --- KONFIRMASI FORCE DELETE ---
         document.querySelectorAll('.form-force-delete').forEach(form => {
            form.addEventListener('submit', function(e) {
                e.preventDefault();
                const title = this.querySelector('button').dataset.title;
                Swal.fire({
                    title: 'ANDA SANGAT YAKIN?',
                    html: `Anda akan menghapus pengumuman "<strong>${title}</strong>" secara <strong>PERMANEN</strong>.<br><strong class="text-danger">Tindakan ini TIDAK BISA DIBATALKAN!</strong>`,
                    icon: 'error', showCancelButton: true, confirmButtonColor: '#d33', cancelButtonColor: '#6c757d',
                    confirmButtonText: 'Ya, Hapus Permanen!', cancelButtonText: 'Batal'
                }).then((result) => { if (result.isConfirmed) { this.submit(); } });
            });
        });

    });
</script>
@endpush
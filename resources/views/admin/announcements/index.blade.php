@extends('admin.layouts.app')

@section('title', 'Manajemen Pengumuman')

@section('content')

    {{-- HEADER & ACTIONS --}}
    <div class="page-header">
        <div>
            <h1 class="page-title">Pengumuman</h1>
            <p class="page-subtitle">Kelola informasi broadcast atau pesan khusus untuk klien.</p>
        </div>
        <div class="flex items-center gap-3">
            <a href="{{ route('admin.announcements.create') }}" class="btn btn-primary">
                <i class="material-icons text-sm mr-1">campaign</i> Buat Pengumuman
            </a>
        </div>
    </div>

    {{-- FILTER TABS --}}
    <div class="card mb-6">
        <div class="card-body">
            <div class="flex bg-slate-100 dark:bg-slate-800 p-1 rounded-lg w-fit">
                <a href="{{ route('admin.announcements.index') }}" 
                   class="px-4 py-2 text-sm font-medium rounded-md transition-all {{ !request('status') ? 'bg-white text-indigo-600 shadow-sm dark:bg-slate-700 dark:text-white' : 'text-slate-500 hover:text-slate-700 dark:text-slate-400' }}">
                    Aktif
                </a>
                <a href="{{ route('admin.announcements.index', ['status' => 'deleted']) }}" 
                   class="px-4 py-2 text-sm font-medium rounded-md transition-all {{ request('status') == 'deleted' ? 'bg-white text-rose-600 shadow-sm dark:bg-slate-700 dark:text-white' : 'text-slate-500 hover:text-slate-700 dark:text-slate-400' }}">
                    Arsip (Deleted)
                </a>
            </div>
        </div>
    </div>

    {{-- TABLE DATA --}}
    <div class="card">
        <div class="table-container">
            <table class="table-modern">
                <thead>
                    <tr>
                        <th class="w-16 text-center">#</th>
                        <th class="w-1/4">Judul</th>
                        <th>Isi Ringkas</th>
                        <th>Tipe</th>
                        <th class="text-center">Status</th>
                        <th class="text-center w-36">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($announcements as $index => $announcement)
                        <tr>
                            <td class="text-center text-slate-500">{{ $announcements->firstItem() + $index }}</td>
                            <td>
                                <div class="font-bold text-slate-700 dark:text-slate-200">
                                    {{ $announcement->title ?? 'Tanpa Judul' }}
                                </div>
                                <div class="text-xs text-slate-500">
                                    {{ $announcement->created_at->format('d M Y, H:i') }}
                                </div>
                            </td>
                            <td>
                                <div class="text-sm text-slate-600 dark:text-slate-300 max-w-md truncate">
                                    {{ Str::limit(strip_tags($announcement->content), 60) }}
                                </div>
                            </td>
                            <td>
                                @if($announcement->type == 'broadcast')
                                    <span class="badge badge-info">
                                        <i class="material-icons text-[10px] mr-1">podcasts</i> Broadcast
                                    </span>
                                @else
                                    <span class="badge badge-primary">
                                        <i class="material-icons text-[10px] mr-1">person_search</i> Targeted
                                    </span>
                                    <div class="text-[10px] text-slate-400 mt-1">
                                        {{ $announcement->clients_count ?? 0 }} Klien
                                    </div>
                                @endif
                            </td>
                            <td class="text-center">
                                @if($announcement->is_active)
                                    <span class="badge badge-success">Publik</span>
                                @else
                                    <span class="badge badge-secondary">Draft/Hidden</span>
                                @endif
                            </td>
                            <td class="text-center">
                                <div class="flex items-center justify-center gap-2">
                                    
                                    @if(request('status') == 'deleted')
                                        {{-- Restore --}}
                                        <form action="{{ route('admin.announcements.restore', $announcement->id) }}" method="POST">
                                            @csrf
                                            @method('PATCH')
                                            <button type="submit" class="w-9 h-9 rounded-full flex items-center justify-center bg-emerald-50 text-emerald-600 hover:bg-emerald-600 hover:text-white transition-colors shadow-sm" title="Pulihkan">
                                                <i class="material-icons text-[18px] leading-none">restore</i>
                                            </button>
                                        </form>

                                        {{-- Force Delete --}}
                                        <button type="button" onclick="confirmForceDelete('{{ $announcement->id }}')" 
                                                class="w-9 h-9 rounded-full flex items-center justify-center bg-rose-50 text-rose-600 hover:bg-rose-600 hover:text-white transition-colors shadow-sm"
                                                title="Hapus Permanen">
                                            <i class="material-icons text-[18px] leading-none">delete_forever</i>
                                        </button>
                                        <form id="force-delete-form-{{ $announcement->id }}" action="{{ route('admin.announcements.forceDelete', $announcement->id) }}" method="POST" class="hidden">
                                            @csrf @method('DELETE')
                                        </form>

                                    @else
                                        {{-- Edit --}}
                                        <a href="{{ route('admin.announcements.edit', $announcement->id) }}" 
                                           class="w-9 h-9 rounded-full flex items-center justify-center bg-indigo-50 text-indigo-600 hover:bg-indigo-600 hover:text-white transition-colors shadow-sm"
                                           title="Edit">
                                            <i class="material-icons text-[18px] leading-none">edit</i>
                                        </a>

                                        {{-- Archive (Soft Delete) --}}
                                        <button type="button" onclick="confirmDelete('{{ $announcement->id }}', '{{ $announcement->title }}')" 
                                                class="w-9 h-9 rounded-full flex items-center justify-center bg-rose-50 text-rose-600 hover:bg-rose-600 hover:text-white transition-colors shadow-sm"
                                                title="Arsipkan">
                                            <i class="material-icons text-[18px] leading-none">archive</i>
                                        </button>
                                        
                                        <form id="delete-form-{{ $announcement->id }}" action="{{ route('admin.announcements.destroy', $announcement->id) }}" method="POST" class="hidden">
                                            @csrf @method('DELETE')
                                        </form>
                                    @endif

                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="text-center p-8">
                                <div class="flex flex-col items-center justify-center text-slate-400">
                                    <i class="material-icons text-5xl mb-2">notifications_off</i>
                                    <span>Belum ada pengumuman.</span>
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        
        <div class="card-footer">
            {{ $announcements->links() }}
        </div>
    </div>

@endsection

@push('scripts')
<script>
    function confirmDelete(id, name) {
        window.confirmDialog({
            title: 'Arsipkan Pengumuman?',
            text: "Pengumuman '" + (name || 'Tanpa Judul') + "' akan dipindahkan ke arsip.",
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#ef4444',
            cancelButtonColor: '#64748b',
            confirmButtonText: 'Ya, Arsipkan!',
            cancelButtonText: 'Batal'
        }).then((result) => {
            if (result.isConfirmed) {
                document.getElementById('delete-form-' + id).submit();
            }
        });
    }

    function confirmForceDelete(id) {
        window.confirmDialog({
            title: 'Hapus Permanen?',
            text: "Data ini akan hilang selamanya!",
            icon: 'error',
            showCancelButton: true,
            confirmButtonColor: '#ef4444',
            cancelButtonColor: '#64748b',
            confirmButtonText: 'Ya, Hapus!',
            cancelButtonText: 'Batal'
        }).then((result) => {
            if (result.isConfirmed) {
                document.getElementById('force-delete-form-' + id).submit();
            }
        });
    }
</script>
@endpush
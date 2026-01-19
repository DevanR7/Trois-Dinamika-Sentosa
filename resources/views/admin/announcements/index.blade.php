@extends('admin.layouts.app')

@section('title', 'Pengumuman')

@section('content')
    {{-- Header --}}
    <div class="page-header">
        <div>
            <h1 class="page-title">Manajemen Pengumuman</h1>
            <p class="page-subtitle">Kelola informasi atau notifikasi untuk klien (Portal Klien)</p>
        </div>
        <div>
            <a href="{{ route('admin.announcements.create') }}" class="btn btn-primary">
                <i class="material-icons text-[18px]">add</i>
                Buat Pengumuman
            </a>
        </div>
    </div>

    {{-- Filter & Tabs --}}
    <div class="card mb-6">
        <div class="card-body">
            <div class="flex flex-col md:flex-row justify-between items-center gap-4">
                {{-- Tabs Status --}}
                <div class="flex bg-slate-100 dark:bg-slate-700/50 rounded-lg p-1">
                    <a href="{{ route('admin.announcements.index') }}" 
                       class="px-4 py-2 text-xs font-bold rounded-md transition-all {{ request('status') !== 'deleted' ? 'bg-white dark:bg-slate-600 shadow-sm text-indigo-600 dark:text-white' : 'text-slate-500 hover:text-slate-700 dark:hover:text-slate-300' }}">
                        Aktif
                    </a>
                    <a href="{{ route('admin.announcements.index', ['status' => 'deleted']) }}" 
                       class="px-4 py-2 text-xs font-bold rounded-md transition-all {{ request('status') === 'deleted' ? 'bg-white dark:bg-slate-600 shadow-sm text-indigo-600 dark:text-white' : 'text-slate-500 hover:text-slate-700 dark:hover:text-slate-300' }}">
                        Arsip ({{ \App\Models\Announcement::onlyTrashed()->count() }})
                    </a>
                </div>
            </div>
        </div>
    </div>

    {{-- Data Table --}}
    <div class="card card-plain">
        <div class="table-container">
            <table class="table-modern">
                <thead>
                    <tr>
                        <th class="w-16">#</th>
                        <th>Judul & Isi</th>
                        <th>Tipe</th>
                        <th>Target Audience</th>
                        <th>Status</th>
                        <th>Dibuat Pada</th>
                        <th class="w-32 text-end">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($announcements as $announcement)
                    <tr>
                        <td>{{ $loop->iteration + $announcements->firstItem() - 1 }}</td>
                        <td>
                            <div class="max-w-sm">
                                <div class="font-bold text-slate-700 dark:text-slate-200 mb-1">
                                    {{ $announcement->title ?? 'Tanpa Judul' }}
                                </div>
                                <div class="text-xs text-slate-500 line-clamp-2">
                                    {{ Str::limit(strip_tags($announcement->content), 80) }}
                                </div>
                            </div>
                        </td>
                        <td>
                            @if($announcement->type === 'broadcast')
                                <span class="badge bg-purple-50 text-purple-600 border border-purple-200 dark:bg-purple-900/20 dark:border-purple-800">
                                    <i class="material-icons text-[14px] mr-1">podcasts</i> Broadcast
                                </span>
                            @else
                                <span class="badge bg-amber-50 text-amber-600 border border-amber-200 dark:bg-amber-900/20 dark:border-amber-800">
                                    <i class="material-icons text-[14px] mr-1">group</i> Targeted
                                </span>
                            @endif
                        </td>
                        <td>
                            @if($announcement->type === 'broadcast')
                                <span class="text-xs font-medium text-slate-500">Semua Klien</span>
                            @else
                                <span class="text-xs font-bold text-slate-700 dark:text-slate-300">
                                    {{ $announcement->clients->count() }} Klien Terpilih
                                </span>
                            @endif
                        </td>
                        <td>
                            @if($announcement->is_active)
                                <span class="badge badge-success">Tayang</span>
                            @else
                                <span class="badge badge-secondary">Draft/Off</span>
                            @endif
                        </td>
                        <td>
                            <div class="text-xs text-slate-500">
                                {{ $announcement->created_at->format('d M Y') }}
                                <span class="block text-[10px] text-slate-400">{{ $announcement->created_at->format('H:i') }}</span>
                            </div>
                        </td>
                        <td class="text-end">
                            <div class="flex items-center justify-end gap-2">
                                @if(request('status') === 'deleted')
                                    {{-- Restore --}}
                                    <form action="{{ route('admin.announcements.restore', $announcement->id) }}" method="POST">
                                        @csrf
                                        @method('PATCH')
                                        <button type="submit" class="btn-action btn-action-restore" title="Pulihkan">
                                            <i class="material-icons">restore</i>
                                        </button>
                                    </form>
                                    
                                    {{-- Force Delete --}}
                                    <button type="button" 
                                            class="btn-action btn-action-delete"
                                            title="Hapus Permanen"
                                            onclick="confirmDialog({
                                                title: 'Hapus Permanen?',
                                                text: 'Pengumuman ini akan dihapus selamanya!',
                                                icon: 'warning',
                                                confirmText: 'Ya, Hapus',
                                                confirmColor: 'danger'
                                            }).then((result) => {
                                                if (result.isConfirmed) document.getElementById('force-delete-{{ $announcement->id }}').submit();
                                            })">
                                        <i class="material-icons">delete_forever</i>
                                    </button>
                                    <form id="force-delete-{{ $announcement->id }}" action="{{ route('admin.announcements.forceDelete', $announcement->id) }}" method="POST" class="hidden">
                                        @csrf
                                        @method('DELETE')
                                    </form>
                                @else
                                    {{-- Edit --}}
                                    <a href="{{ route('admin.announcements.edit', $announcement->id) }}" class="btn-action btn-action-edit" title="Edit">
                                        <i class="material-icons">edit</i>
                                    </a>

                                    {{-- Soft Delete --}}
                                    <button type="button" 
                                            class="btn-action btn-action-delete"
                                            title="Arsipkan"
                                            onclick="confirmDialog({
                                                title: 'Arsipkan Pengumuman?',
                                                text: 'Pengumuman ini akan berhenti ditayangkan.',
                                                icon: 'question',
                                                confirmText: 'Ya, Arsipkan',
                                                confirmColor: 'danger'
                                            }).then((result) => {
                                                if (result.isConfirmed) document.getElementById('delete-form-{{ $announcement->id }}').submit();
                                            })">
                                        <i class="material-icons">delete_outline</i>
                                    </button>
                                    <form id="delete-form-{{ $announcement->id }}" action="{{ route('admin.announcements.destroy', $announcement->id) }}" method="POST" class="hidden">
                                        @csrf
                                        @method('DELETE')
                                    </form>
                                @endif
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="7" class="text-center py-12 text-slate-400">
                            <i class="material-icons text-4xl mb-2">campaign</i>
                            <p>Belum ada pengumuman dibuat.</p>
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        
        {{-- Pagination --}}
        <div class="p-4 border-t border-slate-100 dark:border-slate-700">
            {{ $announcements->links('vendor.pagination.admin') }}
        </div>
    </div>
@endsection
@extends('layouts.app')

@section('title', 'Manajemen Pengumuman')

@section('content')
<div class="max-w-7xl mx-auto pb-20 animate-enter">
    
    {{-- HEADER --}}
    <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center mb-8 gap-4">
        <div>
            <h1 class="text-2xl font-bold text-slate-800 tracking-tight">Manajemen Pengumuman</h1>
            <p class="text-slate-500 text-sm mt-1">Kelola informasi yang disiarkan ke klien.</p>
        </div>
        <div class="mt-4 sm:mt-0 flex gap-2 w-full sm:w-auto">
            @if(request('status') === 'deleted')
                <a href="{{ route('announcements.index') }}" class="flex-1 sm:flex-none h-[48px] px-6 bg-white border border-slate-300 rounded-lg text-slate-700 text-sm font-bold hover:bg-slate-50 transition-all shadow-sm flex items-center justify-center gap-2">
                    <i class="material-icons text-[18px]">arrow_back</i> Kembali ke Aktif
                </a>
            @else
                <a href="{{ route('announcements.index', ['status' => 'deleted']) }}" class="flex-1 sm:flex-none h-[48px] px-6 bg-white border border-slate-300 rounded-lg text-slate-700 text-sm font-bold hover:bg-slate-50 transition-all shadow-sm flex items-center justify-center gap-2">
                    <i class="material-icons text-[18px]">archive</i> Lihat Arsip
                </a>
                <a href="{{ route('announcements.create') }}" class="flex-1 sm:flex-none h-[48px] px-8 bg-indigo-600 hover:bg-indigo-700 text-white text-sm font-bold rounded-lg shadow-sm transition-all flex items-center justify-center gap-2">
                    <i class="material-icons text-[20px]">add</i> Buat Baru
                </a>
            @endif
        </div>
    </div>

    {{-- LIST CONTENT --}}
    <div class="dashboard-card p-0 overflow-hidden shadow-sm">
        
        @if($announcements->isEmpty())
            <div class="empty-state py-12">
                <div class="empty-state-icon">
                    <i class="material-icons text-4xl">campaign</i>
                </div>
                <h3 class="text-lg font-medium text-slate-800">Tidak Ada Pengumuman</h3>
                <p class="text-slate-500 max-w-sm mt-1 text-sm">
                    @if(request('status') === 'deleted')
                        Arsip pengumuman kosong.
                    @else
                        Belum ada pengumuman aktif yang dibuat.
                    @endif
                </p>
            </div>
        @else
            <div class="divide-y divide-slate-100">
                @foreach ($announcements as $announcement)
                <div class="p-5 hover:bg-slate-50/50 transition-colors group">
                    <div class="flex justify-between items-start gap-4">
                        {{-- Konten Kiri --}}
                        <div class="flex-grow">
                            <div class="flex items-center gap-2 mb-1">
                                <h4 class="text-base font-bold text-slate-800 group-hover:text-indigo-600 transition-colors">
                                    {{ $announcement->title ?? 'Tanpa Judul' }}
                                </h4>
                                
                                {{-- Status Badge --}}
                                @if($announcement->trashed())
                                    <span class="status-rejected flex items-center gap-1">Diarsipkan <i class="material-icons text-[12px]">archive</i></span>
                                @elseif($announcement->is_active)
                                    <span class="status-completed flex items-center gap-1">Aktif <i class="material-icons text-[12px]">check_circle</i></span>
                                @else
                                    <span class="status-draft flex items-center gap-1">Draft <i class="material-icons text-[12px]">edit_note</i></span>
                                @endif
                            </div>
                            
                            <p class="text-sm text-slate-600 line-clamp-2 mb-2">
                                {{ $announcement->content }}
                            </p>
                            
                            <div class="flex items-center gap-4 text-xs text-slate-500">
                                @if($announcement->type == 'broadcast')
                                    <div class="flex items-center gap-1 text-blue-600 font-medium">
                                        <i class="material-icons text-[14px]">public</i> Broadcast
                                    </div>
                                @else
                                    <div class="flex items-center gap-1 text-indigo-600 font-medium">
                                        <i class="material-icons text-[14px]">group</i> Targeted ({{ $announcement->clients->count() }} Klien)
                                    </div>
                                @endif
                                
                                <div class="flex items-center gap-1">
                                    <i class="material-icons text-[14px]">schedule</i>
                                    {{ $announcement->created_at->format('d M Y H:i') }}
                                </div>
                            </div>
                        </div>

                        {{-- Tombol Aksi (Kanan) --}}
                        <div class="flex flex-col items-end gap-2 flex-shrink-0">
                            @if($announcement->trashed())
                                <form action="{{ route('announcements.restore', $announcement->id) }}" method="POST" class="form-restore w-full">
                                    @csrf @method('PATCH')
                                    <button type="submit" class="inline-flex justify-center items-center w-full px-3 py-1.5 bg-green-50 text-emerald-700 hover:bg-green-100 rounded border border-green-200 text-xs font-medium transition" data-title="{{ $announcement->title ?? 'ini' }}">
                                        <i class="material-icons text-sm mr-1">restore</i> Pulihkan
                                    </button>
                                </form>
                                <form action="{{ route('announcements.forceDelete', $announcement->id) }}" method="POST" class="form-force-delete w-full">
                                    @csrf @method('DELETE')
                                    <button type="submit" class="inline-flex justify-center items-center w-full px-3 py-1.5 bg-red-50 text-red-700 hover:bg-red-100 rounded border border-red-200 text-xs font-medium transition" data-title="{{ $announcement->title ?? 'ini' }}">
                                        <i class="material-icons text-sm mr-1">delete_forever</i> Hapus Permanen
                                    </button>
                                </form>
                            @else
                                <div class="flex gap-2">
                                    <a href="{{ route('announcements.edit', $announcement->id) }}" class="inline-flex items-center p-2 bg-white border border-slate-200 rounded text-amber-600 hover:bg-amber-50 shadow-sm transition text-xs font-medium" title="Edit">
                                        <i class="material-icons text-sm">edit</i>
                                    </a>
                                    <form action="{{ route('announcements.destroy', $announcement->id) }}" method="POST" class="form-delete inline-block">
                                        @csrf @method('DELETE')
                                        <button type="submit" class="inline-flex items-center p-2 bg-white border border-slate-200 rounded text-red-600 hover:bg-red-50 shadow-sm transition text-xs font-medium" title="Arsipkan" data-title="{{ $announcement->title ?? 'ini' }}">
                                            <i class="material-icons text-sm">archive</i>
                                        </button>
                                    </form>
                                </div>
                            @endif
                        </div>
                    </div>
                </div>
                @endforeach
            </div>
        @endif
    </div>
    
    <div class="mt-6">
        {{ $announcements->appends(request()->query())->links() }}
    </div>
</div>
@endsection

@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function() {
        // NOTIFIKASI TOAST (Menggunakan global showToast yang sudah kita definisikan)
        @if(session('success')) showToast("{{ session('success') }}", 'success'); @endif
        @if(session('error')) showToast("{{ session('error') }}", 'error'); @endif

        // KONFIRMASI ACTIONS (Menggunakan SweetAlert)
        function confirmAction(selector, title, text, btnColor, btnText) {
            document.querySelectorAll(selector).forEach(form => {
                form.addEventListener('submit', function(e) {
                    e.preventDefault();
                    const itemTitle = this.querySelector('button').dataset.title;
                    Swal.fire({
                        title: title,
                        html: text.replace(':title', `<b>${itemTitle}</b>`),
                        icon: 'warning',
                        showCancelButton: true,
                        confirmButtonColor: btnColor,
                        cancelButtonColor: '#6b7280',
                        confirmButtonText: btnText,
                        cancelButtonText: 'Batal',
                        customClass: {
                            popup: 'colored-toast rounded-xl',
                            confirmButton: 'px-6 py-2.5 rounded-lg font-bold',
                            cancelButton: 'px-6 py-2.5 rounded-lg font-bold'
                        }
                    }).then((result) => { 
                        if (result.isConfirmed) this.submit(); 
                    });
                });
            });
        }

        confirmAction('.form-delete', 'Arsipkan Pengumuman?', 'Anda yakin ingin mengarsipkan pengumuman ":title".', '#dc2626', 'Ya, Arsipkan!');
        confirmAction('.form-restore', 'Pulihkan Pengumuman?', 'Anda yakin ingin memulihkan pengumuman ":title".', '#10b981', 'Ya, Pulihkan!');
        confirmAction('.form-force-delete', 'Hapus Permanen?', 'PERINGATAN: Pengumuman ":title" akan dihapus selamanya, TIDAK DAPAT DIKEMBALIKAN!', '#dc2626', 'Ya, Hapus Permanen!');
    });
</script>
@endpush
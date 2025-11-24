@extends('layouts.app')

@section('title', 'Manajemen Pengumuman')

@section('content')
<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-6">
    
    {{-- HEADER --}}
    <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center mb-6">
        <div>
            <h3 class="text-2xl font-bold text-gray-900 tracking-tight">Manajemen Pengumuman</h3>
            <p class="text-sm text-gray-500 mt-1">Kelola informasi yang disiarkan ke klien.</p>
        </div>
        <div class="mt-4 sm:mt-0 flex gap-2">
            @if(request('status') === 'deleted')
                <a href="{{ route('announcements.index') }}" class="inline-flex items-center px-3 py-2 border border-gray-300 shadow-sm text-xs font-medium rounded-lg text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 transition">
                    <i class="material-icons text-sm mr-1">arrow_back</i> Kembali
                </a>
            @else
                <a href="{{ route('announcements.index', ['status' => 'deleted']) }}" class="inline-flex items-center px-3 py-2 border border-gray-300 shadow-sm text-xs font-medium rounded-lg text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 transition">
                    <i class="material-icons text-sm mr-1">archive</i> Lihat Arsip
                </a>
                <a href="{{ route('announcements.create') }}" class="inline-flex items-center px-4 py-2 border border-transparent shadow-sm text-xs font-bold rounded-lg text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 transition">
                    <i class="material-icons text-sm mr-1">add</i> Buat Baru
                </a>
            @endif
        </div>
    </div>

    {{-- LIST CONTENT --}}
    <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
        
        @if($announcements->isEmpty())
            <div class="flex flex-col items-center justify-center py-12 text-center">
                <div class="w-16 h-16 bg-gray-50 rounded-full flex items-center justify-center mb-3">
                    <i class="material-icons text-4xl text-gray-300">campaign</i>
                </div>
                <h3 class="text-lg font-medium text-gray-900">Tidak Ada Pengumuman</h3>
                <p class="text-gray-500 max-w-sm mt-1">
                    @if(request('status') === 'deleted')
                        Arsip pengumuman kosong.
                    @else
                        Belum ada pengumuman aktif yang dibuat.
                    @endif
                </p>
            </div>
        @else
            <div class="divide-y divide-gray-100">
                @foreach ($announcements as $announcement)
                <div class="p-5 hover:bg-gray-50 transition-colors group">
                    <div class="flex justify-between items-start gap-4">
                        {{-- Konten Kiri --}}
                        <div class="flex-grow">
                            <div class="flex items-center gap-2 mb-1">
                                <h4 class="text-base font-bold text-gray-900 group-hover:text-indigo-600 transition-colors">
                                    {{ $announcement->title ?? 'Tanpa Judul' }}
                                </h4>
                                
                                @if($announcement->trashed())
                                    <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-red-100 text-red-800">
                                        Diarsipkan
                                    </span>
                                @elseif($announcement->is_active)
                                    <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-green-100 text-green-800">
                                        Aktif
                                    </span>
                                @else
                                    <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-gray-100 text-gray-800">
                                        Draft (Nonaktif)
                                    </span>
                                @endif
                            </div>
                            
                            <p class="text-sm text-gray-600 line-clamp-2 mb-2">
                                {{ $announcement->content }}
                            </p>
                            
                            <div class="flex items-center gap-4 text-xs text-gray-500">
                                @if($announcement->type == 'broadcast')
                                    <div class="flex items-center gap-1 text-blue-600 font-medium">
                                        <i class="material-icons text-[14px]">podcasts</i> Broadcast
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
                        <div class="flex flex-col items-end gap-2">
                            @if($announcement->trashed())
                                <form action="{{ route('announcements.restore', $announcement->id) }}" method="POST" class="form-restore w-full">
                                    @csrf @method('PATCH')
                                    <button type="submit" class="inline-flex justify-center items-center w-full px-3 py-1.5 bg-green-50 text-green-700 hover:bg-green-100 rounded border border-green-200 text-xs font-medium transition" data-title="{{ $announcement->title ?? 'ini' }}">
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
                                    <a href="{{ route('announcements.edit', $announcement->id) }}" class="inline-flex items-center p-2 bg-white border border-gray-300 rounded text-gray-700 hover:bg-gray-50 shadow-sm transition text-xs font-medium" title="Edit">
                                        <i class="material-icons text-sm">edit</i>
                                    </a>
                                    <form action="{{ route('announcements.destroy', $announcement->id) }}" method="POST" class="form-delete inline-block">
                                        @csrf @method('DELETE')
                                        <button type="submit" class="inline-flex items-center p-2 bg-white border border-red-200 rounded text-red-600 hover:bg-red-50 shadow-sm transition text-xs font-medium" title="Arsipkan" data-title="{{ $announcement->title ?? 'ini' }}">
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
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
    document.addEventListener('DOMContentLoaded', function() {
        // NOTIFIKASI
        @if(session('success')) 
            Swal.fire({ toast: true, position: 'top-end', icon: 'success', title: '{{ session('success') }}', showConfirmButton: false, timer: 3000, timerProgressBar: true }); 
        @endif
        @if(session('error')) 
            Swal.fire({ toast: true, position: 'top-end', icon: 'error', title: '{{ session('error') }}', showConfirmButton: false, timer: 5000, timerProgressBar: true }); 
        @endif

        // KONFIRMASI ACTIONS
        function confirmAction(selector, title, text, btnColor, btnText) {
            document.querySelectorAll(selector).forEach(form => {
                form.addEventListener('submit', function(e) {
                    e.preventDefault();
                    const itemTitle = this.querySelector('button').dataset.title;
                    Swal.fire({
                        title: title,
                        text: text.replace(':title', itemTitle),
                        icon: 'warning',
                        showCancelButton: true,
                        confirmButtonColor: btnColor,
                        cancelButtonColor: '#6b7280',
                        confirmButtonText: btnText,
                        cancelButtonText: 'Batal'
                    }).then((result) => { if (result.isConfirmed) this.submit(); });
                });
            });
        }

        confirmAction('.form-delete', 'Arsipkan Pengumuman?', 'Anda akan mengarsipkan pengumuman ":title".', '#dc2626', 'Ya, Arsipkan!');
        confirmAction('.form-restore', 'Pulihkan Pengumuman?', 'Anda akan memulihkan pengumuman ":title".', '#16a34a', 'Ya, Pulihkan!');
        confirmAction('.form-force-delete', 'Hapus Permanen?', 'PERINGATAN: Pengumuman ":title" akan dihapus selamanya!', '#dc2626', 'Ya, Hapus!');
    });
</script>
@endpush
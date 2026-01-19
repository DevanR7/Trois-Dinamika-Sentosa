@extends('admin.layouts.app')

{{-- Judul Halaman Dinamis --}}
@section('title', request('status') == 'trash' ? 'Arsip Kategori' : 'Manajemen Kategori')

@section('content')

    {{-- 1. PAGE HEADER --}}
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 mb-6">
        <div>
            <h1 class="text-2xl font-bold text-slate-800 dark:text-white tracking-tight">
                {{ request('status') == 'trash' ? 'Arsip Kategori' : 'Kategori Produk' }}
            </h1>
            <p class="text-slate-500 dark:text-slate-400 text-sm mt-1">
                {{ request('status') == 'trash' 
                    ? 'Kategori yang dihapus sementara. Pulihkan jika masih ada produk terkait.' 
                    : 'Kelompokkan produk Anda agar manajemen inventori lebih rapi.' }}
            </p>
        </div>

        <div class="flex flex-wrap gap-3">
            @if(request('status') == 'trash')
                {{-- Tombol Kembali --}}
                <a href="{{ route('admin.categories.index') }}" class="btn btn-secondary">
                    <i class="material-icons text-[18px]">arrow_back</i>
                    <span>Kembali ke Daftar</span>
                </a>
            @else
                {{-- Tombol Lihat Sampah & Tambah --}}
                
                @can('delete-products') {{-- Asumsi permission delete category ikut products/manager --}}
                    <a href="{{ route('admin.categories.index', ['status' => 'trash']) }}" 
                       class="btn bg-white border border-slate-200 text-slate-600 hover:bg-slate-50 hover:text-rose-600 transition-colors dark:bg-slate-800 dark:border-slate-700 dark:text-slate-300 dark:hover:text-rose-400"
                       title="Lihat Kategori Terhapus">
                        <i class="material-icons text-[18px]">delete_outline</i>
                        <span class="hidden sm:inline">Sampah</span>
                    </a>
                @endcan

                @can('create-products') {{-- Asumsi permission create category ikut products --}}
                    <a href="{{ route('admin.categories.create') }}" class="btn btn-primary">
                        <i class="material-icons text-[18px]">add</i>
                        <span>Kategori Baru</span>
                    </a>
                @endcan
            @endif
        </div>
    </div>

    {{-- 2. SEARCH & FILTER --}}
    <div class="card mb-6">
        <div class="card-body p-4">
            <form action="{{ route('admin.categories.index') }}" method="GET" class="flex flex-col md:flex-row gap-4">
                @if(request('status'))
                    <input type="hidden" name="status" value="{{ request('status') }}">
                @endif

                {{-- Search Input --}}
                <div class="flex-1 relative">
                    <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                        <i class="material-icons text-slate-400">search</i>
                    </div>
                    <input type="text" name="search" value="{{ request('search') }}" 
                           class="form-input pl-10" 
                           placeholder="Cari nama kategori...">
                </div>

                {{-- Reset Button --}}
                @if(request()->filled('search'))
                    <a href="{{ route('admin.categories.index', ['status' => request('status')]) }}" 
                       class="btn btn-secondary btn-icon" 
                       title="Reset Pencarian">
                        <i class="material-icons">refresh</i>
                    </a>
                @endif
            </form>
        </div>
    </div>

    {{-- 3. TABLE DATA --}}
    <div class="card border-0 shadow-none bg-transparent">
        <div class="table-container bg-white dark:bg-slate-800 shadow-sm rounded-xl border border-slate-200 dark:border-slate-700">
            <table class="table-modern">
                <thead>
                    <tr>
                        <th class="w-14 text-center">No</th>
                        <th>Info Kategori</th>
                        <th class="hidden md:table-cell">Deskripsi</th>
                        <th class="text-center">Jumlah Produk</th>
                        <th class="text-center w-24">Status</th>
                        <th class="text-right sticky right-0 z-10 bg-slate-50 dark:bg-slate-800/50 backdrop-blur-sm w-32 px-4">
                            Aksi
                        </th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($categories as $index => $category)
                        <tr class="group hover:bg-slate-50 dark:hover:bg-slate-700/30 transition-colors">
                            
                            {{-- No --}}
                            <td class="text-center text-slate-400 text-xs">
                                {{ $categories->firstItem() + $index }}
                            </td>

                            {{-- Info Kategori --}}
                            <td>
                                <div class="flex items-center gap-4">
                                    {{-- Thumbnail / Icon --}}
                                    <div class="shrink-0">
                                        @if($category->image_path)
                                            <img src="{{ asset('storage/' . $category->image_path) }}" 
                                                 alt="{{ $category->name }}" 
                                                 class="w-10 h-10 rounded-lg object-cover border border-slate-200 dark:border-slate-600">
                                        @else
                                            <div class="w-10 h-10 rounded-lg bg-orange-50 dark:bg-orange-900/20 flex items-center justify-center text-orange-400 border border-orange-100 dark:border-orange-800">
                                                <i class="material-icons text-[20px]">category</i>
                                            </div>
                                        @endif
                                    </div>

                                    <div class="flex flex-col">
                                        <span class="font-bold text-slate-700 dark:text-slate-200 text-sm group-hover:text-indigo-600 transition-colors">
                                            {{ $category->name }}
                                        </span>
                                        <span class="text-[11px] font-mono text-slate-400">
                                            /{{ $category->slug }}
                                        </span>
                                    </div>
                                </div>
                            </td>

                            {{-- Deskripsi --}}
                            <td class="hidden md:table-cell max-w-xs">
                                <p class="text-xs text-slate-500 dark:text-slate-400 line-clamp-2" title="{{ $category->description }}">
                                    {{ $category->description ?? '-' }}
                                </p>
                            </td>

                            {{-- Jumlah Produk --}}
                            <td class="text-center">
                                @if($category->products_count > 0)
                                    <span class="badge bg-indigo-50 text-indigo-600 border-indigo-100 px-2.5 py-0.5 rounded-full font-bold text-xs">
                                        {{ $category->products_count }} Produk
                                    </span>
                                @else
                                    <span class="text-xs text-slate-400 italic">Kosong</span>
                                @endif
                            </td>

                            {{-- Status --}}
                            <td class="text-center">
                                @if($category->is_active)
                                    <span class="inline-flex items-center justify-center w-6 h-6 rounded-full bg-emerald-100 text-emerald-600" title="Aktif">
                                        <i class="material-icons text-[14px]">check</i>
                                    </span>
                                @else
                                    <span class="inline-flex items-center justify-center w-6 h-6 rounded-full bg-slate-100 text-slate-400" title="Non-Aktif">
                                        <i class="material-icons text-[14px]">close</i>
                                    </span>
                                @endif
                            </td>

                            {{-- Aksi --}}
                            <td class="text-right sticky right-0 bg-white dark:bg-slate-800 border-l border-slate-100 dark:border-slate-700/50 group-hover:bg-slate-50 dark:group-hover:bg-slate-700/30 transition-colors z-10 px-4">
                                <div class="flex items-center justify-end gap-2">
                                    
                                    @if(request('status') == 'trash')
                                        {{-- MODE SAMPAH --}}
                                        @can('delete-products')
                                            {{-- Restore --}}
                                            <form action="{{ route('admin.categories.restore', $category->category_id) }}" method="POST" class="inline-block m-0">
                                                @csrf
                                                @method('PATCH')
                                                <button type="button" class="btn-action btn-action-restore" 
                                                        onclick="handleAction(this, 'Pulihkan Kategori?', 'Kategori ini akan aktif kembali.', 'success')"
                                                        title="Pulihkan">
                                                    <i class="material-icons">restore_from_trash</i>
                                                </button>
                                            </form>

                                            {{-- Force Delete --}}
                                            <form action="{{ route('admin.categories.forceDelete', $category->category_id) }}" method="POST" class="inline-block m-0">
                                                @csrf
                                                @method('DELETE')
                                                <button type="button" class="btn-action btn-action-delete" 
                                                        onclick="handleAction(this, 'Hapus Permanen?', 'Data akan hilang selamanya.', 'danger')"
                                                        title="Hapus Permanen">
                                                    <i class="material-icons">delete_forever</i>
                                                </button>
                                            </form>
                                        @endcan

                                    @else
                                        {{-- MODE AKTIF --}}
                                        @can('edit-products')
                                            <a href="{{ route('admin.categories.edit', $category->category_id) }}" class="btn-action btn-action-edit" title="Edit">
                                                <i class="material-icons">edit</i>
                                            </a>
                                        @endcan

                                        @can('delete-products')
                                            <form action="{{ route('admin.categories.destroy', $category->category_id) }}" method="POST" class="inline-block m-0">
                                                @csrf
                                                @method('DELETE')
                                                <button type="button" class="btn-action btn-action-delete" 
                                                        onclick="handleAction(this, 'Arsipkan Kategori?', 'Pastikan tidak ada produk aktif di kategori ini sebelum menghapus.', 'warning')"
                                                        title="Arsipkan">
                                                    <i class="material-icons">delete</i>
                                                </button>
                                            </form>
                                        @endcan
                                    @endif

                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="text-center py-12">
                                <div class="flex flex-col items-center justify-center opacity-60">
                                    <div class="w-16 h-16 bg-slate-100 dark:bg-slate-700/50 rounded-full flex items-center justify-center mb-4">
                                        <i class="material-icons text-3xl text-slate-400">category</i>
                                    </div>
                                    <h3 class="text-slate-800 dark:text-white font-medium text-lg">Tidak ada data kategori</h3>
                                    <p class="text-slate-500 text-sm mt-1 max-w-xs mx-auto">
                                        {{ request('search') ? 'Coba kata kunci lain.' : 'Buat kategori untuk mengelompokkan produk Anda.' }}
                                    </p>
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        {{-- Pagination --}}
        <div class="mt-6">
            {{ $categories->links() }}
        </div>
    </div>

{{-- SCRIPT KHUSUS HANDLE ACTION (Sama seperti Products) --}}
@push('scripts')
<script>
    function handleAction(button, title, text, type) {
        event.preventDefault();
        const form = button.closest('form');
        
        if (typeof window.confirmDialog === 'function') {
            window.confirmDialog({
                title: title,
                text: text,
                icon: type === 'danger' ? 'error' : type,
                confirmButtonText: 'Ya, Lanjutkan',
                cancelButtonText: 'Batal',
                confirmButtonColor: type
            }).then((result) => {
                if (result.isConfirmed) {
                    form.submit();
                }
            });
        } else {
            if(confirm(text)) form.submit();
        }
    }
</script>
@endpush

@endsection
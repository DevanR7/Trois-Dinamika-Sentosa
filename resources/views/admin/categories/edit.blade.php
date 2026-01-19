@extends('admin.layouts.app')

@section('title', 'Edit Kategori')

@section('content')

    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 mb-6">
        <div>
            <h1 class="text-2xl font-bold text-slate-800 dark:text-white tracking-tight">Edit Kategori</h1>
            <p class="text-sm text-slate-500 dark:text-slate-400">
                Perbarui data kategori <span class="font-bold text-indigo-600">{{ $category->name }}</span>
            </p>
        </div>
        <div class="flex gap-2">
            @if($category->deleted_at)
                <span class="badge bg-rose-100 text-rose-600 border-rose-200">Arsip</span>
            @endif
            <a href="{{ route('admin.categories.index') }}" class="btn btn-secondary">
                <i class="material-icons text-[18px]">arrow_back</i>
                <span>Kembali</span>
            </a>
        </div>
    </div>

    <form action="{{ route('admin.categories.update', $category->category_id) }}" method="POST" enctype="multipart/form-data"
          x-data="categoryEditForm()">
        @csrf
        @method('PUT')

        <div class="grid grid-cols-1 xl:grid-cols-12 gap-6">

            {{-- KOLOM KIRI --}}
            <div class="xl:col-span-8 space-y-6">
                
                <div class="card p-6 h-full">
                    <div class="card-header bg-slate-50 dark:bg-slate-800/50 border-b border-slate-100 dark:border-slate-700 pb-3 mb-5">
                        <h3 class="card-header-title">Informasi Kategori</h3>
                    </div>
                    
                    <div class="grid grid-cols-1 gap-6">
                        {{-- Nama --}}
                        <div>
                            <label class="form-label label-required">Nama Kategori</label>
                            <input type="text" name="name" 
                                   x-model="name"
                                   class="form-input @error('name') is-invalid @enderror" 
                                   required>
                            @error('name') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>

                        {{-- Slug (Readonly di Edit biasanya lebih aman, tapi editable jika perlu) --}}
                        <div>
                            <label class="form-label">Slug</label>
                            <div class="relative flex items-center">
                                <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                    <i class="material-icons text-slate-400 text-[18px]">link</i>
                                </div>
                                <input type="text" name="slug" value="{{ old('slug', $category->slug) }}"
                                       class="form-input pl-10 font-mono text-slate-600 bg-slate-50 dark:bg-slate-900/50" 
                                       readonly>
                                <span class="absolute right-3 text-[10px] text-slate-400 italic">Auto-generated</span>
                            </div>
                        </div>

                        {{-- Deskripsi --}}
                        <div>
                            <label class="form-label">Deskripsi</label>
                            <textarea name="description" class="form-textarea h-32">{{ old('description', $category->description) }}</textarea>
                        </div>
                    </div>
                </div>
            </div>

            {{-- KOLOM KANAN --}}
            <div class="xl:col-span-4 space-y-6">
                
                {{-- CARD GAMBAR --}}
                <div class="card p-5 bg-white dark:bg-slate-800">
                    <label class="form-label mb-3 block">Ikon Kategori</label>
                    
                    <div class="relative w-full aspect-square bg-slate-50 dark:bg-slate-900 rounded-xl border-2 border-dashed border-slate-300 dark:border-slate-700 hover:border-indigo-500 transition-all cursor-pointer group overflow-hidden mx-auto max-w-[280px]"
                         @click="$refs.fileInput.click()">
                        
                        <div x-show="isUploading" class="absolute inset-0 z-20 bg-white/80 dark:bg-slate-800/80 flex flex-col items-center justify-center backdrop-blur-sm" style="display: none;">
                            <svg class="animate-spin h-8 w-8 text-indigo-600 mb-2" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                            </svg>
                        </div>

                        <template x-if="imagePreview">
                            <div class="absolute inset-0 z-10">
                                <img :src="imagePreview" class="w-full h-full object-cover">
                                <div class="absolute inset-0 bg-black/40 opacity-0 group-hover:opacity-100 transition-opacity flex flex-col items-center justify-center text-white">
                                    <i class="material-icons text-3xl mb-1">edit</i>
                                    <span class="text-xs font-medium">Ganti Ikon</span>
                                </div>
                            </div>
                        </template>

                        <template x-if="!imagePreview">
                            <div class="absolute inset-0 flex flex-col items-center justify-center text-slate-400">
                                <i class="material-icons text-4xl mb-2">category</i>
                                <span class="text-xs font-bold">Tidak ada ikon</span>
                            </div>
                        </template>

                        <input type="file" name="image" x-ref="fileInput" 
                               accept="image/png, image/jpeg, image/jpg" 
                               class="hidden" 
                               @change="handleFileUpload">
                    </div>
                    @error('image') <div class="invalid-feedback mt-2 text-center">{{ $message }}</div> @enderror
                </div>

                {{-- CARD STATUS & AKSI --}}
                <div class="card p-5">
                    <h3 class="text-xs font-bold text-slate-400 uppercase tracking-wider mb-4">Pengaturan</h3>
                    
                    <div class="flex items-center justify-between mb-6">
                        <div>
                            <label class="text-sm font-bold text-slate-700 dark:text-slate-200 block">Status Aktif</label>
                            <span class="text-[10px] text-slate-500">Tampilkan di sistem?</span>
                        </div>
                        <label class="relative inline-flex items-center cursor-pointer">
                            <input type="checkbox" name="is_active" value="1" class="sr-only peer" 
                                {{ old('is_active', $category->is_active) ? 'checked' : '' }}>
                            <div class="w-11 h-6 bg-slate-200 peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-indigo-300 dark:peer-focus:ring-indigo-800 rounded-full peer dark:bg-slate-700 peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all dark:border-gray-600 peer-checked:bg-indigo-600"></div>
                        </label>
                    </div>

                    <div class="flex flex-col gap-3">
                        <button type="submit" class="btn btn-primary w-full justify-center shadow-lg shadow-indigo-500/20">
                            <i class="material-icons text-[18px]">save</i>
                            Simpan Perubahan
                        </button>
                        <a href="{{ route('admin.categories.index') }}" class="btn btn-secondary w-full justify-center">
                            Batal
                        </a>
                    </div>
                </div>

            </div>
        </div>
    </form>

    @push('scripts')
    <script>
        function categoryEditForm() {
            return {
                name: '{{ old("name", $category->name) }}',
                imagePreview: '{{ $category->image_path ? asset("storage/".$category->image_path) : "" }}',
                isUploading: false,

                handleFileUpload(event) {
                    const file = event.target.files[0];
                    if (!file) return;

                    this.isUploading = true;
                    setTimeout(() => {
                        this.imagePreview = URL.createObjectURL(file);
                        this.isUploading = false;
                    }, 500);
                }
            }
        }
    </script>
    @endpush

@endsection
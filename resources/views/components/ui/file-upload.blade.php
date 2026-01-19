@props([
    'name',
    'id' => null,
    'label' => 'Upload File',
    'accept' => 'image/png, image/jpeg, image/jpg',
    'helperText' => 'PNG, JPG or JPEG (MAX. 10MB)',
    'required' => false,
    'variant' => 'dropzone'
])

@php
    $inputId = $id ?? $name . '_' . uniqid();
@endphp

<div class="w-full" 
     x-data="{ 
        fileName: null,
        isUploading: false,
        imageUrl: null, 
        
        handleFileChange(event) {
            const file = event.target.files[0];
            if (file) {
                // 1. Validasi Ukuran (10MB)
                if(file.size > 10 * 1024 * 1024) {
                    alert('Ukuran file terlalu besar! Maksimal 10MB.');
                    event.target.value = ''; 
                    return;
                }

                // 2. Mulai Loading Animation
                this.isUploading = true;
                this.fileName = null;
                this.imageUrl = null;

                // 3. Proses Preview dengan FileReader
                setTimeout(() => {
                    if (file.type.startsWith('image/')) {
                        const reader = new FileReader();
                        reader.onload = (e) => {
                            this.imageUrl = e.target.result;
                            this.fileName = file.name;
                            this.isUploading = false;
                        };
                        reader.readAsDataURL(file);
                    } else {
                        this.fileName = file.name;
                        this.isUploading = false;
                    }
                }, 1000);
            }
        }
     }">

    @if($label)
        <label class="block mb-2 text-xs font-bold text-slate-500 uppercase tracking-wide" for="{{ $inputId }}">
            {{ $label }}
        </label>
    @endif

    <div class="flex items-center justify-center w-full relative">
        <label for="{{ $inputId }}" 
               class="relative flex flex-col items-center justify-center w-full h-64 border-2 border-slate-300 border-dashed rounded-2xl cursor-pointer bg-slate-50 hover:bg-indigo-50/50 hover:border-indigo-400 dark:hover:bg-slate-800 dark:bg-slate-800/50 dark:border-slate-600 dark:hover:border-indigo-500 transition-all duration-300 group overflow-hidden">
            
            {{-- A. LOADING STATE --}}
            <div x-show="isUploading" 
                 class="absolute inset-0 z-20 flex flex-col items-center justify-center bg-white/90 dark:bg-slate-800/90 backdrop-blur-sm transition-opacity"
                 style="display: none;">
                <svg class="animate-spin h-10 w-10 text-indigo-600 dark:text-indigo-400" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                </svg>
                <p class="mt-3 text-xs font-bold text-indigo-600 dark:text-indigo-400 animate-pulse">Memproses Gambar...</p>
            </div>

            {{-- B. DEFAULT STATE --}}
            <div class="flex flex-col items-center justify-center pt-5 pb-6 px-4 transition-opacity duration-300" 
                 x-show="!fileName && !imageUrl && !isUploading">
                
                {{-- PERBAIKAN DI SINI: --}}
                {{-- Menggunakan w-14 h-14 (fixed size) + flex center agar bulat sempurna --}}
                <div class="w-14 h-14 bg-white dark:bg-slate-700 rounded-full shadow-sm mb-3 flex items-center justify-center group-hover:scale-110 transition-transform duration-300">
                    <i class="material-icons text-3xl text-slate-400 group-hover:text-indigo-500">cloud_upload</i>
                </div>
                
                <p class="mb-2 text-sm text-slate-500 dark:text-slate-400 text-center">
                    <span class="font-bold text-slate-700 dark:text-slate-200">Klik untuk upload</span> atau drag & drop
                </p>
                <p class="text-xs text-slate-400 dark:text-slate-500 text-center">
                    {{ $helperText }}
                </p>
            </div>

            {{-- C. PREVIEW STATE --}}
            <div class="absolute inset-0 z-10 bg-white dark:bg-slate-800 w-full h-full flex flex-col items-center justify-center p-2"
                 x-show="(fileName || imageUrl) && !isUploading"
                 style="display: none;">
                
                {{-- Image Preview --}}
                <template x-if="imageUrl">
                    <div class="relative w-full h-full rounded-xl overflow-hidden group/preview">
                        <img :src="imageUrl" class="w-full h-full object-contain bg-slate-100 dark:bg-slate-900 rounded-xl">
                        <div class="absolute inset-0 bg-black/40 opacity-0 group-hover/preview:opacity-100 transition-opacity flex items-center justify-center gap-3 backdrop-blur-[2px]">
                            <p class="text-white text-xs font-medium absolute top-4 left-4 truncate max-w-[80%]" x-text="fileName"></p>
                            <div class="text-white flex flex-col items-center cursor-pointer hover:text-indigo-300 transition-colors">
                                <i class="material-icons text-3xl">edit</i>
                                <span class="text-[10px] font-bold uppercase mt-1">Ganti</span>
                            </div>
                        </div>
                    </div>
                </template>

                {{-- Non-Image File Preview --}}
                <template x-if="!imageUrl && fileName">
                    <div class="flex flex-col items-center justify-center h-full">
                        <div class="w-16 h-16 bg-emerald-100 text-emerald-600 rounded-2xl flex items-center justify-center mb-3">
                            <i class="material-icons text-4xl">description</i>
                        </div>
                        <p class="text-sm font-bold text-slate-700 dark:text-slate-200 text-center break-all px-4" x-text="fileName"></p>
                    </div>
                </template>
            </div>

            <input id="{{ $inputId }}" 
                   name="{{ $name }}" 
                   type="file" 
                   class="absolute inset-0 w-full h-full opacity-0 cursor-pointer z-30"
                   accept="{{ $accept }}"
                   @if($required) required @endif
                   @change="handleFileChange($event)" 
            />
        </label>
    </div>
</div>
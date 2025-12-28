@extends('admin.layouts.app')

@section('title', 'Edit Produk')

@section('content')
    <div class="max-w-5xl mx-auto flex flex-col gap-8">
        
        {{-- Header Navigation --}}
        <div class="flex items-center gap-4">
            <a href="{{ route('admin.products.index') }}" 
               class="w-10 h-10 rounded-xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800 flex items-center justify-center text-slate-500 hover:text-indigo-600 hover:border-indigo-200 transition-all shadow-sm">
                <i class="material-icons text-[20px]">arrow_back</i>
            </a>
            <div>
                <h1 class="text-xl font-bold text-slate-900 dark:text-white">Edit Produk</h1>
                <p class="text-sm text-slate-500 dark:text-slate-400">Perbarui informasi produk <strong>{{ $product->product_name }}</strong>.</p>
            </div>
        </div>

        <form action="{{ route('admin.products.update', $product->product_id) }}" method="POST" enctype="multipart/form-data">
            @csrf
            @method('PUT')

            <div class="flex flex-col gap-6">
                
                {{-- Card 1: Informasi Utama --}}
                <div class="bg-white dark:bg-slate-800 rounded-2xl p-6 sm:p-8 border border-slate-200 dark:border-slate-700 shadow-sm">
                    <h3 class="text-lg font-bold text-slate-800 dark:text-white mb-6 flex items-center gap-2">
                        <i class="material-icons text-indigo-500">edit_note</i> Detail Produk
                    </h3>

                    <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
                        {{-- Image Upload --}}
                        <div class="lg:col-span-1">
                            <label class="block text-sm font-semibold text-slate-700 dark:text-slate-300 mb-2">Foto Produk</label>
                            
                            <div class="relative w-full aspect-square bg-slate-50 dark:bg-slate-900 rounded-2xl border-2 border-dashed border-slate-300 dark:border-slate-600 hover:border-indigo-400 transition-colors group cursor-pointer overflow-hidden flex flex-col items-center justify-center text-center">
                                <input type="file" id="image_input" name="image" class="absolute inset-0 w-full h-full opacity-0 cursor-pointer z-10" accept="image/*" onchange="previewImage(event)">
                                
                                {{-- Loading Indicator --}}
                                <div id="image-loading" class="absolute inset-0 flex flex-col items-center justify-center bg-white/90 dark:bg-slate-800/90 z-20 hidden">
                                    <svg class="animate-spin h-8 w-8 text-indigo-600" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                    </svg>
                                    <span class="text-xs text-indigo-600 font-medium mt-2">Memuat...</span>
                                </div>

                                {{-- Jika ada gambar lama --}}
                                @if($product->image_path)
                                    <img id="image-preview" src="{{ asset('storage/' . $product->image_path) }}" alt="Preview" class="absolute inset-0 w-full h-full object-cover">
                                    <div id="placeholder-content" class="absolute inset-0 bg-black/40 flex flex-col items-center justify-center opacity-0 group-hover:opacity-100 transition-opacity duration-200 z-0">
                                        <i class="material-icons text-white text-3xl">edit</i>
                                        <p class="text-white text-xs mt-1">Ganti Foto</p>
                                    </div>
                                @else
                                    <div id="placeholder-content" class="flex flex-col items-center p-4">
                                        <div class="w-12 h-12 rounded-full bg-indigo-50 dark:bg-indigo-900/30 flex items-center justify-center mb-3 group-hover:scale-110 transition-transform">
                                            <i class="material-icons text-indigo-500">add_a_photo</i>
                                        </div>
                                        <p class="text-xs text-slate-500 dark:text-slate-400">Upload Foto</p>
                                    </div>
                                    <img id="image-preview" src="#" alt="Preview" class="absolute inset-0 w-full h-full object-cover hidden">
                                @endif
                            </div>
                            @error('image') <p class="text-xs text-red-500 mt-2">{{ $message }}</p> @enderror
                        </div>

                        {{-- Inputs --}}
                        <div class="lg:col-span-2 flex flex-col gap-6">
                            
                            {{-- Nama Produk --}}
                            <div>
                                <label for="product_name" class="form-label">Nama Produk</label>
                                <input type="text" id="product_name" name="product_name" 
                                       class="form-input" 
                                       value="{{ old('product_name', $product->product_name) }}" required>
                                @error('product_name') <p class="form-error">{{ $message }}</p> @enderror
                            </div>

                            {{-- Kode Produk dengan Smart Generator --}}
                            <div>
                                <label for="product_code" class="form-label flex justify-between">
                                    <span>Kode Produk</span>
                                    <span class="text-[10px] text-indigo-500 cursor-pointer hover:underline" onclick="generateSmartCode()">Generate Ulang</span>
                                </label>
                                <div class="relative flex items-center">
                                    <input type="text" id="product_code" name="product_code" 
                                           class="form-input pr-12 font-mono uppercase bg-slate-50 dark:bg-slate-900 @error('product_code') border-red-500 @enderror" 
                                           value="{{ old('product_code', $product->product_code) }}" required>
                                    
                                    {{-- Tombol Generate --}}
                                    <button type="button" 
                                            onclick="generateSmartCode()"
                                            class="absolute right-1.5 w-9 h-8 bg-white dark:bg-slate-800 hover:bg-slate-100 dark:hover:bg-slate-700 text-indigo-600 rounded-lg transition-colors flex items-center justify-center shadow-sm border border-slate-200 dark:border-slate-600"
                                            title="Generate Ulang Kode">
                                        <i class="material-icons text-[18px]">auto_fix_high</i>
                                    </button>
                                </div>
                                @error('product_code') <p class="form-error">{{ $message }}</p> @enderror
                            </div>

                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-5">
                                <div>
                                    <label class="form-label">Pemasok</label>
                                    <select name="supplier_id" class="tom-select" required>
                                        @foreach($suppliers as $supplier)
                                            <option value="{{ $supplier->supplier_id }}" {{ old('supplier_id', $product->supplier_id) == $supplier->supplier_id ? 'selected' : '' }}>
                                                {{ $supplier->supplier_name }}
                                            </option>
                                        @endforeach
                                    </select>
                                </div>
                                <div>
                                    <label class="form-label">Satuan</label>
                                    <select name="unit_id" class="tom-select" required>
                                        @foreach($units as $unit)
                                            <option value="{{ $unit->unit_id }}" {{ old('unit_id', $product->unit_id) == $unit->unit_id ? 'selected' : '' }}>
                                                {{ $unit->name }}
                                            </option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>

                            <div>
                                <label class="form-label">Deskripsi</label>
                                <textarea name="description" rows="3" class="form-textarea">{{ old('description', $product->description) }}</textarea>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Card 2: Harga & Stok --}}
                <div class="bg-white dark:bg-slate-800 rounded-2xl p-6 sm:p-8 border border-slate-200 dark:border-slate-700 shadow-sm">
                    <h3 class="text-lg font-bold text-slate-800 dark:text-white mb-6 flex items-center gap-2">
                        <i class="material-icons text-emerald-500">price_check</i> Pembaruan Harga
                    </h3>

                    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                        <div>
                            <label class="form-label">Harga Beli</label>
                            <div class="relative">
                                <div class="absolute inset-y-0 left-0 w-10 flex items-center justify-center pointer-events-none z-10 border-r border-slate-200 dark:border-slate-700 h-full bg-slate-50 dark:bg-slate-900 rounded-l-lg">
                                    <span class="text-slate-500 font-medium text-xs">Rp</span>
                                </div>
                                <input type="text" name="purchase_price" 
                                       class="form-input pl-12 text-right font-medium autonumeric" 
                                       value="{{ old('purchase_price', $product->purchase_price) }}" required>
                            </div>
                        </div>

                        <div>
                            <label class="form-label">Harga Jual</label>
                            <div class="relative">
                                <div class="absolute inset-y-0 left-0 w-10 flex items-center justify-center pointer-events-none z-10 border-r border-slate-200 dark:border-slate-700 h-full bg-slate-50 dark:bg-slate-900 rounded-l-lg">
                                    <span class="text-slate-500 font-medium text-xs">Rp</span>
                                </div>
                                <input type="text" name="selling_price" 
                                       class="form-input pl-12 text-right font-bold text-emerald-600 dark:text-emerald-400 autonumeric" 
                                       value="{{ old('selling_price', $product->selling_price) }}" required>
                            </div>
                        </div>

                        <div>
                            <label class="form-label">Stok Saat Ini</label>
                            <div class="relative">
                                <div class="absolute inset-y-0 left-0 w-10 flex items-center justify-center pointer-events-none z-10 border-r border-slate-200 dark:border-slate-700 h-full bg-slate-50 dark:bg-slate-900 rounded-l-lg">
                                    <i class="material-icons text-slate-400 text-[18px]">layers</i>
                                </div>
                                <input type="text" name="stock_quantity" 
                                       class="form-input pl-12 text-right bg-slate-50 dark:bg-slate-900 autonumeric" 
                                       value="{{ old('stock_quantity', $product->stock_quantity) }}">
                            </div>
                            <p class="text-[10px] text-slate-400 mt-1">Ubah manual atau gunakan Stock Opname.</p>
                        </div>
                    </div>
                </div>

                {{-- Action Buttons --}}
                <div class="flex items-center justify-end gap-4 pt-4">
                    <a href="{{ route('admin.products.index') }}" class="btn btn-secondary h-11 px-6">Batal</a>
                    <button type="submit" class="btn btn-primary h-11 px-8 shadow-lg shadow-indigo-500/30">
                        <span class="flex items-center gap-2">
                            <i class="material-icons text-[20px]">check</i> Simpan Perubahan
                        </span>
                    </button>
                </div>

            </div>
        </form>
    </div>

    @push('scripts')
    <script>
        function previewImage(event) {
            const loading = document.getElementById('image-loading');
            const preview = document.getElementById('image-preview');
            const placeholder = document.getElementById('placeholder-content');
            
            loading.classList.remove('hidden');
            if(placeholder) placeholder.style.opacity = '0';
            
            const reader = new FileReader();
            reader.onload = function(){
                setTimeout(() => {
                    preview.src = reader.result;
                    preview.classList.remove('hidden');
                    loading.classList.add('hidden');
                }, 400);
            };
            if(event.target.files[0]) {
                reader.readAsDataURL(event.target.files[0]);
            }
        }

        // === SAMA DENGAN CREATE: SCRIPT SMART GENERATOR ===
        function generateSmartCode() {
            const nameInput = document.getElementById('product_name');
            const codeInput = document.getElementById('product_code');
            const rawName = nameInput.value.trim().toUpperCase();

            if (!rawName) {
                if(typeof window.showToast === 'function') {
                    window.showToast('Harap isi Nama Produk terlebih dahulu!', 'warning');
                } else {
                    alert('Harap isi Nama Produk terlebih dahulu!');
                }
                nameInput.focus();
                return;
            }

            const aliases = {
                'BULL': 'BL', 'TEKIRO': 'TKR', 'MAKITA': 'MKT', 'BOSCH': 'BSH',
                'HONDA': 'HND', 'YAMAHA': 'YMH', 'MESIN': 'MSN', 'OBENG': 'OBG',
                'PALU': 'PLU', 'PAKU': 'PKU', 'SEMEN': 'SMN', 'CAT': 'PT',
                'BESI': 'FE', 'KAYU': 'WD', 'BATU': 'STN', 'GERINDA': 'GRN'
            };

            const words = rawName.replace(/[^A-Z0-9\s]/g, '').split(/\s+/);
            let codeParts = [];
            let numbers = [];

            words.forEach(word => {
                if (aliases[word]) {
                    codeParts.push(aliases[word]);
                } else if (/\d/.test(word)) {
                    numbers.push(word);
                } else {
                    if (word.length > 2) {
                        const consonants = word.replace(/[AIUEO]/g, '');
                        const code = consonants.length > 0 ? consonants.substring(0, 3) : word.substring(0, 3);
                        codeParts.push(code);
                    }
                }
            });

            let finalParts = [...codeParts.slice(0, 3), ...numbers];
            let baseCode = finalParts.join('-').toUpperCase();
            if(!baseCode) baseCode = rawName.substring(0, 3);
            const randomSuffix = Math.floor(Math.random() * 900) + 100;

            codeInput.value = `${baseCode}-${randomSuffix}`;
            codeInput.focus();
            codeInput.classList.add('ring-2', 'ring-indigo-500');
            setTimeout(() => codeInput.classList.remove('ring-2', 'ring-indigo-500'), 500);
        }
    </script>
    @endpush
@endsection
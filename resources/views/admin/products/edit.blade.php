@extends('admin.layouts.app')

@section('title', 'Edit Produk')

@section('content')
    <div class="page-header">
        <div>
            <h1 class="page-title">Edit Produk</h1>
            <p class="page-subtitle">Perbarui informasi produk: <strong>{{ $product->product_name }}</strong></p>
        </div>
        <div>
            <a href="{{ route('admin.products.index') }}" class="btn btn-secondary">
                <i class="material-icons text-[18px]">arrow_back</i> Kembali
            </a>
        </div>
    </div>

    <form action="{{ route('admin.products.update', $product->product_id) }}" method="POST" enctype="multipart/form-data" 
          x-data="codeGenerator()">
        @csrf
        @method('PUT')

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
            
            {{-- KOLOM KIRI --}}
            <div class="lg:col-span-2 space-y-6">
                
                {{-- Card Identitas --}}
                <div class="card">
                    <div class="card-header">
                        <h3 class="card-header-title">Identitas Produk</h3>
                    </div>
                    <div class="card-body grid grid-cols-1 md:grid-cols-2 gap-5">
                        
                        <div class="md:col-span-2">
                            <label class="form-label label-required">Supplier</label>
                            <select name="supplier_id" class="tom-select" required>
                                <option value="">-- Pilih Supplier --</option>
                                @foreach($suppliers as $supplier)
                                    <option value="{{ $supplier->supplier_id }}" {{ old('supplier_id', $product->supplier_id) == $supplier->supplier_id ? 'selected' : '' }}>
                                        {{ $supplier->supplier_name }}
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        <div class="md:col-span-2">
                            <label class="form-label label-required">Nama Produk</label>
                            <input type="text" name="product_name" id="product_name"
                                   value="{{ old('product_name', $product->product_name) }}"
                                   class="form-input font-medium" required>
                        </div>

                        {{-- Wizard Code --}}
                        <div class="md:col-span-2 relative">
                            <label class="form-label label-required">Kode Produk (SKU)</label>
                            <div class="flex gap-2">
                                <div class="relative flex-1">
                                    <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                        <i class="material-icons text-slate-400 text-[18px]">qr_code</i>
                                    </div>
                                    <input type="text" name="product_code" 
                                           value="{{ old('product_code', $product->product_code) }}"
                                           class="form-input pl-10 font-mono uppercase" required>
                                </div>
                                <div class="relative">
                                    <button type="button" @click="generateSuggestions()" 
                                            class="btn btn-secondary h-full px-3 text-indigo-600 border-indigo-200 bg-indigo-50 hover:bg-indigo-100 transition-colors">
                                        <i class="material-icons text-[20px]">auto_fix_high</i>
                                    </button>
                                    <div x-show="showSuggestions" @click.outside="showSuggestions = false" x-transition class="absolute right-0 top-full mt-2 w-64 bg-white dark:bg-slate-800 rounded-xl shadow-xl border border-slate-200 z-50 overflow-hidden" style="display: none;">
                                        <div class="px-3 py-2 bg-slate-50 border-b text-xs font-bold text-slate-500 uppercase">Saran Kode</div>
                                        <ul class="max-h-60 overflow-y-auto custom-scrollbar p-1">
                                            <template x-for="code in suggestions" :key="code">
                                                <li><button type="button" @click="selectCode(code)" class="w-full text-left px-3 py-2 text-sm font-mono hover:bg-indigo-50 rounded-lg"><span x-text="code"></span></button></li>
                                            </template>
                                        </ul>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div>
                            <label class="form-label">Kategori</label>
                            <select name="category_id" class="tom-select">
                                <option value="">-- Tanpa Kategori --</option>
                                @foreach($categories as $cat)
                                    <option value="{{ $cat->category_id }}" {{ old('category_id', $product->category_id) == $cat->category_id ? 'selected' : '' }}>
                                        {{ $cat->name }}
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        <div>
                            <label class="form-label label-required">Satuan Unit</label>
                            <select name="unit_id" class="tom-select" required>
                                @foreach($units as $unit)
                                    <option value="{{ $unit->unit_id }}" {{ old('unit_id', $product->unit_id) == $unit->unit_id ? 'selected' : '' }}>
                                        {{ $unit->name }}
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        <div class="md:col-span-2">
                            <label class="form-label label-optional">Deskripsi Lengkap</label>
                            <textarea name="description" class="form-textarea" rows="3">{{ old('description', $product->description) }}</textarea>
                        </div>

                    </div>
                </div>

                {{-- Harga & Stok --}}
                <div class="card">
                    <div class="card-header border-l-4 border-amber-500">
                        <h3 class="card-header-title">Harga & Inventaris</h3>
                    </div>
                    <div class="card-body grid grid-cols-1 md:grid-cols-3 gap-5">
                        <div>
                            <label class="form-label label-required">Harga Beli</label>
                            <div class="relative">
                                <span class="absolute left-3 top-2.5 text-slate-400 text-sm font-bold">Rp</span>
                                <input type="text" name="purchase_price" class="form-input pl-10 input-currency autonumeric" 
                                       required value="{{ old('purchase_price', $product->purchase_price) }}">
                            </div>
                        </div>
                        <div>
                            <label class="form-label">Harga Jual</label>
                            <div class="relative">
                                <span class="absolute left-3 top-2.5 text-slate-400 text-sm font-bold">Rp</span>
                                <input type="text" name="selling_price" class="form-input pl-10 input-currency autonumeric" 
                                       value="{{ old('selling_price', $product->selling_price) }}">
                            </div>
                        </div>
                        <div>
                            <label class="form-label label-required">Stok Saat Ini</label>
                            <input type="text" name="stock_quantity" class="form-input autonumeric bg-slate-100" 
                                   data-an-m-dec="0" required value="{{ old('stock_quantity', $product->stock_quantity) }}">
                            <p class="text-[10px] text-slate-400 mt-1">Gunakan <b>Stock Opname</b> untuk adjustment akurat.</p>
                        </div>
                    </div>
                </div>
            </div>

            {{-- KOLOM KANAN --}}
            <div class="space-y-6">
                
                {{-- Foto --}}
                <div class="card">
                    <div class="card-header">
                        <h3 class="card-header-title">Foto Produk</h3>
                    </div>
                    <div class="card-body">
                        @if($product->image_path)
                            <div class="mb-4 text-center bg-slate-50 dark:bg-slate-700/30 p-4 rounded-lg border border-slate-100 dark:border-slate-700">
                                <img src="{{ asset('storage/' . $product->image_path) }}" 
                                     class="h-48 w-48 object-cover mx-auto rounded-lg shadow-sm border border-slate-200 dark:border-slate-600">
                                <p class="text-xs text-slate-400 mt-2">Gambar Saat Ini (1:1)</p>
                            </div>
                        @endif
                        <x-ui.file-upload name="image" label="Ganti Foto" :required="false" helperText="JPG, PNG (Max 10MB)" />
                    </div>
                </div>

                {{-- Status --}}
                <div class="card">
                    <div class="card-header">
                        <h3 class="card-header-title">Status</h3>
                    </div>
                    <div class="card-body">
                        <div class="flex items-center justify-between p-3 bg-slate-50 dark:bg-slate-700/50 rounded-lg border border-slate-200 dark:border-slate-700">
                            <div>
                                <span class="text-sm font-bold text-slate-700 dark:text-white block">Status Aktif</span>
                            </div>
                            <label class="relative inline-flex items-center cursor-pointer">
                                <input type="checkbox" name="is_active" class="sr-only peer" {{ $product->is_active ? 'checked' : '' }}>
                                <div class="w-11 h-6 bg-slate-200 peer-focus:outline-none rounded-full peer dark:bg-slate-700 peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-indigo-600"></div>
                            </label>
                        </div>
                        
                        <div class="mt-4 pt-4 border-t border-slate-100 dark:border-slate-700">
                            <p class="text-xs text-slate-400 mb-1">Dibuat: {{ $product->created_at->format('d M Y H:i') }}</p>
                            <p class="text-xs text-slate-400">Update: {{ $product->updated_at->format('d M Y H:i') }}</p>
                        </div>
                    </div>
                </div>

                <div class="flex flex-col gap-3">
                    <button type="submit" class="btn btn-primary w-full shadow-lg">
                        <i class="material-icons">save</i> Simpan Perubahan
                    </button>
                    <a href="{{ route('admin.products.index') }}" class="btn btn-secondary w-full">Batal</a>
                </div>
            </div>
        </div>
    </form>

    @push('scripts')
    <script>
        function codeGenerator() {
            return {
                showSuggestions: false,
                suggestions: [],
                generateSuggestions() {
                    const name = document.getElementById('product_name').value.trim();
                    if (name.length < 3) {
                        window.showToast('Nama produk terlalu pendek.', 'warning');
                        return;
                    }
                    const words = name.split(/\s+/);
                    const firstWord = words[0].toUpperCase();
                    const numbers = name.match(/\d+/g); 
                    const lastNumber = numbers ? numbers[numbers.length - 1] : '';
                    let results = new Set();
                    results.add(name.toUpperCase().replace(/\s+/g, '-'));
                    if (lastNumber) {
                        results.add(`${firstWord} ${lastNumber}`);
                        results.add(`${firstWord}-${lastNumber}`);
                    }
                    if (words.length > 1) {
                        const secondWord = words[1].toUpperCase();
                        if (/\d/.test(secondWord)) {
                            results.add(`${secondWord}`);
                            results.add(`${firstWord}-${secondWord}`);
                        }
                    }
                    const randomSuffix = Math.floor(1000 + Math.random() * 9000);
                    results.add(`${firstWord.substring(0, 3)}-${randomSuffix}`);
                    this.suggestions = Array.from(results).filter(s => s.length > 3).slice(0, 7);
                    this.showSuggestions = true;
                },
                selectCode(code) {
                    document.getElementsByName('product_code')[0].value = code;
                    this.showSuggestions = false;
                    window.showToast('Kode diperbarui!', 'success');
                }
            }
        }
    </script>
    @endpush
@endsection
@extends('layouts.app')

@section('title', 'Tambah Produk Baru')

@section('content')
<div class="max-w-7xl mx-auto pb-20 animate-enter">
    
    <form action="{{ route('products.store') }}" method="POST" enctype="multipart/form-data" id="productForm">
        @csrf

        {{-- HEADER --}}
        <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center mb-8 gap-4">
            <div>
                <nav class="flex text-sm text-slate-500 mb-1">
                    <a href="{{ route('products.index') }}" class="hover:text-indigo-600 transition-colors">Produk</a>
                    <span class="mx-2 text-slate-300">/</span>
                    <span class="text-slate-800 font-semibold">Baru</span>
                </nav>
                <h1 class="text-2xl font-bold text-slate-800 tracking-tight">Tambah Produk Baru</h1>
            </div>
            
            <div class="flex gap-3 w-full sm:w-auto">
                <a href="{{ route('products.index') }}" 
                   class="flex-1 sm:flex-none h-[48px] px-6 bg-white border border-slate-300 text-slate-600 rounded-lg text-sm font-bold hover:bg-slate-50 transition-all flex items-center justify-center gap-2">
                    <i class="material-icons text-[18px]">close</i> Batal
                </a>
                <button type="submit" 
                        class="flex-1 sm:flex-none h-[48px] px-8 bg-indigo-600 hover:bg-indigo-700 text-white rounded-lg text-sm font-bold shadow-sm transition-all flex items-center justify-center gap-2">
                    <i class="material-icons text-[18px]">save</i> Simpan
                </button>
            </div>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-12 gap-8">
            
            {{-- KOLOM KIRI: FORM INPUT --}}
            <div class="lg:col-span-8 space-y-6">
                <div class="dashboard-card p-0 overflow-hidden">
                    <div class="px-6 py-4 border-b border-slate-100 bg-slate-50/50 flex items-center gap-3">
                        <div class="p-2 bg-indigo-50 rounded-lg text-indigo-600">
                            <i class="material-icons text-[20px]">inventory_2</i>
                        </div>
                        <h3 class="text-sm font-bold text-slate-700 uppercase tracking-wide">Detail Produk</h3>
                    </div>

                    <div class="p-6 space-y-6">
                        {{-- Nama Produk --}}
                        <div>
                            <label for="product_name">Nama Produk <span class="text-red-500">*</span></label>
                            <input type="text" name="product_name" id="product_name" value="{{ old('product_name') }}" class="form-input" placeholder="Contoh: Sepatu Sneakers Nike" required>
                            @error('product_name') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                        </div>

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            {{-- SKU --}}
                            <div>
                                <label for="product_code">SKU / Kode <span class="text-red-500">*</span></label>
                                <input type="text" name="product_code" id="product_code" value="{{ old('product_code') }}" class="form-input font-mono uppercase" placeholder="AUTO-GEN" required>
                                @error('product_code') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                            </div>
                            {{-- Satuan --}}
                            <div>
                                <label for="unit_id">Satuan (Unit) <span class="text-red-500">*</span></label>
                                <select name="unit_id" id="unit_id" class="select2-basic" required>
                                    <option value="" selected disabled>Pilih Satuan...</option>
                                    @foreach ($units as $unit)
                                        <option value="{{ $unit->unit_id }}" @selected(old('unit_id') == $unit->unit_id)>{{ $unit->name }}</option>
                                    @endforeach
                                </select>
                                @error('unit_id') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                            </div>
                        </div>

                        {{-- Supplier --}}
                        <div>
                            <label for="supplier_id">Supplier Utama <span class="text-red-500">*</span></label>
                            <select name="supplier_id" id="supplier_id" class="select2-basic" required>
                                <option value="" selected disabled>Pilih Supplier...</option>
                                @foreach($suppliers as $supplier)
                                    <option value="{{ $supplier->supplier_id }}" @selected(old('supplier_id') == $supplier->supplier_id)>{{ $supplier->supplier_name }}</option>
                                @endforeach
                            </select>
                            @error('supplier_id') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                        </div>

                        <hr class="border-slate-100">

                        {{-- Harga & Stok --}}
                        <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                            <div>
                                <label for="purchase_price">Harga Beli (HPP)</label>
                                <input type="text" id="purchase_price_display" class="form-input input-currency font-mono" placeholder="0">
                                <input type="hidden" name="purchase_price" id="purchase_price" value="{{ old('purchase_price', 0) }}">
                                @error('purchase_price') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                            </div>
                            <div>
                                <label for="selling_price">Harga Jual <span class="text-red-500">*</span></label>
                                <input type="text" id="selling_price_display" class="form-input input-currency font-mono font-bold text-indigo-600" placeholder="0">
                                <input type="hidden" name="selling_price" id="selling_price" value="{{ old('selling_price', 0) }}">
                                @error('selling_price') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                            </div>
                            <div>
                                <label for="stock_quantity">Stok Awal</label>
                                <input type="number" name="stock_quantity" id="stock_quantity" value="{{ old('stock_quantity', 0) }}" class="form-input" min="0">
                                @error('stock_quantity') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                            </div>
                        </div>

                        {{-- Deskripsi --}}
                        <div>
                            <label for="description">Deskripsi / Catatan</label>
                            <textarea name="description" id="description" class="form-textarea" placeholder="Spesifikasi produk...">{{ old('description') }}</textarea>
                        </div>
                    </div>
                </div>
            </div>

            {{-- KOLOM KANAN: GAMBAR --}}
            <div class="lg:col-span-4 space-y-6">
                <div class="dashboard-card">
                    <div class="px-6 py-4 border-b border-slate-100 flex items-center justify-between">
                        <h3 class="text-sm font-bold text-slate-700 uppercase tracking-wide">Gambar Produk</h3>
                    </div>
                    
                    <div class="p-6">
                        <div class="relative w-full aspect-square bg-slate-50 rounded-xl border-2 border-dashed border-slate-300 hover:border-indigo-400 hover:bg-indigo-50/10 transition-all group cursor-pointer overflow-hidden flex flex-col items-center justify-center text-center" id="upload-area">
                            
                            <div id="upload-placeholder" class="p-6">
                                <div class="w-12 h-12 bg-white rounded-full shadow-sm flex items-center justify-center mx-auto mb-3 text-indigo-500">
                                    <i class="material-icons text-2xl">cloud_upload</i>
                                </div>
                                <p class="text-sm font-bold text-slate-700">Upload Gambar</p>
                                <p class="text-xs text-slate-400 mt-1">Max 2MB (JPG/PNG)</p>
                            </div>
                            
                            <img id="image-preview" src="#" class="hidden absolute inset-0 w-full h-full object-cover rounded-xl">
                            <input type="file" name="image" id="image" class="absolute inset-0 w-full h-full opacity-0 cursor-pointer z-10" accept="image/*">
                            
                            {{-- Tombol Hapus Gambar --}}
                            <button type="button" id="remove-image" class="hidden absolute top-3 right-3 w-8 h-8 bg-white text-red-500 rounded-lg shadow-md hover:bg-red-50 z-20 flex items-center justify-center">
                                <i class="material-icons text-[18px]">delete</i>
                            </button>
                        </div>
                        @error('image') <p class="text-red-500 text-xs mt-2 text-center">{{ $message }}</p> @enderror
                    </div>
                </div>
            </div>

        </div>
    </form>
</div>
@endsection

@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function() {
        // Select2 Init
        $('.select2-basic').select2({ placeholder: 'Pilih...', allowClear: true, width: '100%', dropdownCssClass: 'select2-dropdown-clean' });

        // AutoNumeric Manual Init (jika ingin lebih spesifik selain class .input-currency)
        // Tapi karena di app.js sudah ada global .input-currency, ini opsional
        // Cukup pastikan input hidden terisi saat submit
        
        const purchaseInput = document.getElementById('purchase_price_display');
        const sellingInput = document.getElementById('selling_price_display');
        
        // Logic Gambar
        const input = document.getElementById('image');
        const preview = document.getElementById('image-preview');
        const ph = document.getElementById('upload-placeholder');
        const rm = document.getElementById('remove-image');
        const area = document.getElementById('upload-area');

        input.addEventListener('change', function(e) {
            const file = e.target.files[0];
            if(file) {
                if(file.size > 2*1024*1024) {
                    window.showToast('Ukuran file terlalu besar (Max 2MB)', 'error');
                    this.value = '';
                    return;
                }
                const reader = new FileReader();
                reader.onload = (ev) => {
                    preview.src = ev.target.result;
                    preview.classList.remove('hidden');
                    ph.classList.add('hidden');
                    rm.classList.remove('hidden');
                    area.classList.remove('border-dashed', 'border-slate-300');
                    area.classList.add('border-solid', 'border-indigo-200');
                }
                reader.readAsDataURL(file);
            }
        });

        rm.addEventListener('click', function(e) {
            e.preventDefault();
            input.value = '';
            preview.src = '#';
            preview.classList.add('hidden');
            ph.classList.remove('hidden');
            rm.classList.add('hidden');
            area.classList.add('border-dashed', 'border-slate-300');
            area.classList.remove('border-solid', 'border-indigo-200');
        });

        // Submit Handler: Pindahkan nilai AutoNumeric ke Hidden Input
        document.getElementById('productForm').addEventListener('submit', function() {
            // Ambil instance AutoNumeric
            if(AutoNumeric.getAutoNumericElement(purchaseInput)) {
                document.getElementById('purchase_price').value = AutoNumeric.getAutoNumericElement(purchaseInput).getNumber();
            }
            if(AutoNumeric.getAutoNumericElement(sellingInput)) {
                document.getElementById('selling_price').value = AutoNumeric.getAutoNumericElement(sellingInput).getNumber();
            }
        });

        // Notifikasi Session
        @if(session('success')) window.showToast("{{ session('success') }}", 'success'); @endif
        @if(session('error')) window.showToast("{{ session('error') }}", 'error'); @endif
    });
</script>
@endpush
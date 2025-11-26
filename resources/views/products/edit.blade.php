@extends('layouts.app')

@section('title', 'Edit Produk')

@section('content')
<div class="max-w-7xl mx-auto pb-20 animate-enter">
    
    <form action="{{ route('products.update', $product->product_id) }}" method="POST" enctype="multipart/form-data" id="productForm">
        @csrf @method('PUT')

        {{-- HEADER --}}
        <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center mb-8 gap-4">
            <div>
                <nav class="flex text-sm text-slate-500 mb-1">
                    <a href="{{ route('products.index') }}" class="hover:text-indigo-600 transition-colors">Produk</a>
                    <span class="mx-2 text-slate-300">/</span>
                    <span class="text-slate-800 font-semibold">Edit</span>
                </nav>
                <h1 class="text-2xl font-bold text-slate-800 tracking-tight">{{ $product->product_name }}</h1>
            </div>
            
            <div class="flex gap-3 w-full sm:w-auto">
                @can('delete', $product)
                    {{-- Global Delete Handler --}}
                    <div class="form-confirm hidden sm:block"> {{-- Div wrapper dummy untuk selector JS --}}
                        <button type="button" 
                                onclick="document.getElementById('delete-form-main').submit()" {{-- Trigger form terpisah --}}
                                class="h-[48px] px-5 bg-white border border-red-200 text-red-600 rounded-lg text-sm font-bold hover:bg-red-50 transition-all flex items-center justify-center gap-2 shadow-sm">
                            <i class="material-icons text-[18px]">delete</i> Hapus
                        </button>
                    </div>
                @endcan
                
                <a href="{{ route('products.index') }}" 
                   class="flex-1 sm:flex-none h-[48px] px-6 bg-white border border-slate-300 text-slate-600 rounded-lg text-sm font-bold hover:bg-slate-50 transition-all flex items-center justify-center gap-2">
                    <i class="material-icons text-[18px]">close</i> Batal
                </a>
                <button type="submit" 
                        class="flex-1 sm:flex-none h-[48px] px-8 bg-indigo-600 hover:bg-indigo-700 text-white rounded-lg text-sm font-bold shadow-sm transition-all flex items-center justify-center gap-2">
                    <i class="material-icons text-[18px]">check</i> Update
                </button>
            </div>
        </div>

        {{-- FORM CONTENT --}}
        <div class="grid grid-cols-1 lg:grid-cols-12 gap-8">
            
            <div class="lg:col-span-8 space-y-6">
                <div class="dashboard-card">
                    <div class="px-6 py-4 border-b border-slate-100 flex items-center gap-3">
                        <div class="p-2 bg-indigo-50 rounded-lg text-indigo-600">
                            <i class="material-icons text-[20px]">edit</i>
                        </div>
                        <h3 class="text-sm font-bold text-slate-700 uppercase tracking-wide">Edit Informasi</h3>
                    </div>

                    <div class="p-6 space-y-6">
                        {{-- Nama --}}
                        <div>
                            <label for="product_name">Nama Produk <span class="text-red-500">*</span></label>
                            <input type="text" name="product_name" value="{{ old('product_name', $product->product_name) }}" class="form-input font-medium text-slate-800" required>
                            @error('product_name') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                        </div>

                        {{-- SKU & Unit --}}
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div>
                                <label for="product_code">SKU</label>
                                <input type="text" name="product_code" value="{{ old('product_code', $product->product_code) }}" class="form-input font-mono uppercase" required>
                                @error('product_code') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                            </div>
                            <div>
                                <label for="unit_id">Satuan</label>
                                <select name="unit_id" class="select2-basic" required>
                                    @foreach ($units as $unit)
                                        <option value="{{ $unit->unit_id }}" @selected(old('unit_id', $product->unit_id) == $unit->unit_id)>{{ $unit->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>

                        {{-- Supplier --}}
                        <div>
                            <label for="supplier_id">Supplier</label>
                            <select name="supplier_id" class="select2-basic" required>
                                @foreach($suppliers as $supplier)
                                    <option value="{{ $supplier->supplier_id }}" @selected(old('supplier_id', $product->supplier_id) == $supplier->supplier_id)>{{ $supplier->supplier_name }}</option>
                                @endforeach
                            </select>
                        </div>

                        <hr class="border-slate-100">

                        {{-- Harga & Stok --}}
                        <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                            <div>
                                <label>Harga Beli</label>
                                <input type="text" id="purchase_price_display" class="form-input input-currency font-mono">
                                <input type="hidden" name="purchase_price" id="purchase_price" value="{{ old('purchase_price', $product->purchase_price) }}">
                            </div>
                            <div>
                                <label>Harga Jual</label>
                                <input type="text" id="selling_price_display" class="form-input input-currency font-mono font-bold text-indigo-600">
                                <input type="hidden" name="selling_price" id="selling_price" value="{{ old('selling_price', $product->selling_price) }}">
                            </div>
                            <div>
                                <label>Stok Saat Ini</label>
                                <input type="number" name="stock_quantity" value="{{ old('stock_quantity', $product->stock_quantity) }}" class="form-input">
                            </div>
                        </div>

                        {{-- Deskripsi --}}
                        <div>
                            <label>Deskripsi</label>
                            <textarea name="description" rows="4" class="form-textarea">{{ old('description', $product->description) }}</textarea>
                        </div>
                    </div>
                </div>
            </div>

            {{-- KOLOM KANAN --}}
            <div class="lg:col-span-4 space-y-6">
                <div class="dashboard-card">
                    <div class="px-6 py-4 border-b border-slate-100">
                        <h3 class="text-sm font-bold text-slate-700 uppercase tracking-wide">Gambar</h3>
                    </div>
                    <div class="p-6">
                        <div class="relative w-full aspect-square bg-slate-50 rounded-xl border-2 border-dashed border-slate-300 hover:border-indigo-400 hover:bg-indigo-50/10 transition-all group cursor-pointer overflow-hidden flex flex-col items-center justify-center text-center {{ $product->image_path ? 'border-none' : '' }}" id="upload-area">
                            
                            <div id="upload-placeholder" class="{{ $product->image_path ? 'hidden' : '' }} p-6">
                                <div class="w-12 h-12 bg-white rounded-full shadow-sm flex items-center justify-center mx-auto mb-3 text-indigo-500">
                                    <i class="material-icons text-2xl">cloud_upload</i>
                                </div>
                                <p class="text-sm font-bold text-slate-700">Ganti Gambar</p>
                            </div>
                            
                            <img id="image-preview" src="{{ $product->image_path ? asset('storage/' . $product->image_path) : '#' }}" class="{{ $product->image_path ? '' : 'hidden' }} absolute inset-0 w-full h-full object-cover rounded-xl">
                            <input type="file" name="image" id="image" class="absolute inset-0 w-full h-full opacity-0 cursor-pointer z-10" accept="image/*">
                            
                            <button type="button" id="remove-image" class="{{ $product->image_path ? '' : 'hidden' }} absolute top-3 right-3 w-8 h-8 bg-white text-red-500 rounded-lg shadow-md hover:bg-red-50 z-20 flex items-center justify-center">
                                <i class="material-icons text-[18px]">delete</i>
                            </button>
                        </div>
                    </div>
                </div>
            </div>

        </div>
    </form>

    {{-- Form Delete Terpisah (Untuk Global Handler) --}}
    @can('delete', $product)
    <form id="delete-form-main" action="{{ route('products.destroy', $product->product_id) }}" method="POST" class="form-confirm hidden">
        @csrf @method('DELETE')
        <button type="submit" 
                data-title="Hapus Produk?" 
                data-text="Produk <b>{{ $product->product_name }}</b> akan dihapus permanen."
                class="hidden"></button>
    </form>
    @endcan

</div>
@endsection

@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function() {
        $('.select2-basic').select2({ placeholder: 'Pilih...', allowClear: true, width: '100%', dropdownCssClass: 'select2-dropdown-clean' });

        // Set AutoNumeric Values from DB
        const purchaseInput = document.getElementById('purchase_price_display');
        const sellingInput = document.getElementById('selling_price_display');
        
        // Init AutoNumeric manual agar bisa set value
        const anPurchase = new AutoNumeric(purchaseInput, { digitGroupSeparator: '.', decimalCharacter: ',', decimalPlaces: 0, minimumValue: '0' });
        const anSelling = new AutoNumeric(sellingInput, { digitGroupSeparator: '.', decimalCharacter: ',', decimalPlaces: 0, minimumValue: '0' });

        anPurchase.set("{{ $product->purchase_price }}");
        anSelling.set("{{ $product->selling_price }}");

        // Handle Image Logic (Sama seperti create)
        const input = document.getElementById('image');
        const preview = document.getElementById('image-preview');
        const ph = document.getElementById('upload-placeholder');
        const rm = document.getElementById('remove-image');
        const area = document.getElementById('upload-area');

        input.addEventListener('change', function(e) {
            const file = e.target.files[0];
            if(file) {
                const reader = new FileReader();
                reader.onload = (ev) => {
                    preview.src = ev.target.result;
                    preview.classList.remove('hidden');
                    ph.classList.add('hidden');
                    rm.classList.remove('hidden');
                    area.classList.remove('border-dashed', 'border-slate-300');
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
        });

        // Submit Handler
        document.getElementById('productForm').addEventListener('submit', function() {
            document.getElementById('purchase_price').value = anPurchase.getNumber();
            document.getElementById('selling_price').value = anSelling.getNumber();
        });

        @if(session('success')) window.showToast("{{ session('success') }}", 'success'); @endif
        @if(session('error')) window.showToast("{{ session('error') }}", 'error'); @endif
    });
</script>
@endpush
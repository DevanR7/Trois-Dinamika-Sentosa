@extends('layouts.app')

@section('title', 'Edit Produk')

@section('content')
<div class="max-w-6xl mx-auto">
    
    <form action="{{ route('products.update', $product->product_id) }}" method="POST" enctype="multipart/form-data">
        @csrf
        @method('PUT')

        <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center mb-8">
            <div>
                <div class="flex items-center gap-2 text-sm text-gray-500 mb-1">
                    <a href="{{ route('products.index') }}" class="hover:text-indigo-600 transition">Produk</a>
                    <span>/</span>
                    <span class="text-gray-800">Edit</span>
                </div>
                <h2 class="text-2xl font-bold text-gray-900 tracking-tight">Edit Produk: <span class="text-indigo-600">{{ $product->product_name }}</span></h2>
            </div>
            <div class="flex gap-3 mt-4 sm:mt-0">
                <a href="{{ route('products.index') }}" class="px-4 py-2.5 bg-white border border-gray-300 text-gray-700 text-sm font-medium rounded-lg hover:bg-gray-50 transition shadow-sm">
                    Batal
                </a>
                <button type="submit" class="px-6 py-2.5 bg-indigo-600 hover:bg-indigo-700 text-white text-sm font-bold rounded-lg shadow-md transition flex items-center">
                    <i class="bi bi-check-circle mr-2"></i> Update Produk
                </button>
            </div>
        </div>

        @if ($errors->any())
            <div class="mb-6 bg-red-50 border border-red-200 rounded-lg p-4 flex items-start gap-3">
                <i class="bi bi-x-circle-fill text-red-500 mt-0.5"></i>
                <div>
                    <h3 class="text-sm font-bold text-red-800">Gagal memperbarui</h3>
                    <ul class="mt-1 list-disc list-inside text-sm text-red-700">
                        @foreach ($errors->all() as $error) <li>{{ $error }}</li> @endforeach
                    </ul>
                </div>
            </div>
        @endif

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
            
            {{-- MAIN CONTENT --}}
            <div class="lg:col-span-2 space-y-6">
                <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
                    <h3 class="text-base font-bold text-gray-900 mb-4">Informasi Umum</h3>
                    <div class="space-y-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Nama Produk</label>
                            <input type="text" name="product_name" value="{{ old('product_name', $product->product_name) }}" class="form-input w-full rounded-lg border-gray-300 focus:border-indigo-500 focus:ring-indigo-500" required>
                        </div>
                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Kode (SKU)</label>
                                <input type="text" name="product_code" value="{{ old('product_code', $product->product_code) }}" class="form-input w-full rounded-lg border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 font-mono text-sm" required>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Unit</label>
                                <select name="unit_id" id="unit_id" class="form-select w-full rounded-lg border-gray-300" required>
                                    @foreach ($units as $unit)
                                        <option value="{{ $unit->unit_id }}" @selected(old('unit_id', $product->unit_id) == $unit->unit_id)>{{ $unit->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Deskripsi</label>
                            <textarea name="description" rows="4" class="form-textarea w-full rounded-lg border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 text-sm">{{ old('description', $product->description) }}</textarea>
                        </div>
                    </div>
                </div>

                <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
                    <h3 class="text-base font-bold text-gray-900 mb-4">Harga</h3>
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-6">
                        <div>
                            <label class="block text-xs font-bold text-gray-500 uppercase mb-1">Harga Beli (HPP)</label>
                            <div class="relative rounded-md shadow-sm">
                                <div class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3">
                                    <span class="text-gray-500 sm:text-sm">Rp</span>
                                </div>
                                <input type="text" id="purchase_price_display" 
                                    value="{{ old('purchase_price', $product->purchase_price) ? number_format(old('purchase_price', $product->purchase_price), 0, ',', '.') : '' }}"
                                    class="block w-full rounded-lg border-gray-300 pl-10 focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm font-medium">
                                <input type="hidden" name="purchase_price" id="purchase_price" value="{{ old('purchase_price', $product->purchase_price) }}">
                            </div>
                        </div>
                        <div>
                            <label class="block text-xs font-bold text-gray-500 uppercase mb-1">Harga Jual</label>
                            <div class="relative rounded-md shadow-sm">
                                <div class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3">
                                    <span class="text-gray-500 sm:text-sm">Rp</span>
                                </div>
                                <input type="text" id="selling_price_display" 
                                    value="{{ old('selling_price', $product->selling_price) ? number_format(old('selling_price', $product->selling_price), 0, ',', '.') : '' }}"
                                    class="block w-full rounded-lg border-gray-300 pl-10 focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm font-bold text-gray-900">
                                <input type="hidden" name="selling_price" id="selling_price" value="{{ old('selling_price', $product->selling_price) }}">
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            {{-- SIDEBAR --}}
            <div class="space-y-6">
                <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
                    <h3 class="text-base font-bold text-gray-900 mb-4">Media</h3>
                    <div class="flex flex-col items-center justify-center w-full">
                        
                        {{-- Logic Tampilan Gambar Lama/Baru --}}
                        <div id="image-preview-container" class="{{ $product->image_path ? '' : 'hidden' }} mb-4 w-full relative group">
                            <img id="image-preview" src="{{ $product->image_path ? asset('storage/' . $product->image_path) : '#' }}" class="w-full h-48 object-cover rounded-lg border border-gray-200">
                            <button type="button" id="remove-image" class="absolute top-2 right-2 bg-white rounded-full p-1 shadow-md hover:bg-red-50 text-red-600 transition">
                                <i class="bi bi-x text-xl leading-none"></i>
                            </button>
                        </div>

                        <label for="image" id="image-upload-box" class="{{ $product->image_path ? 'hidden' : 'flex' }} flex-col items-center justify-center w-full h-40 border-2 border-gray-300 border-dashed rounded-lg cursor-pointer bg-gray-50 hover:bg-gray-100 transition">
                            <div class="flex flex-col items-center justify-center pt-5 pb-6">
                                <i class="bi bi-cloud-arrow-up text-3xl text-gray-400 mb-2"></i>
                                <p class="mb-1 text-sm text-gray-500">Ganti Gambar</p>
                            </div>
                            <input id="image" name="image" type="file" class="hidden" accept="image/*" />
                        </label>
                    </div>
                </div>

                <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
                    <h3 class="text-base font-bold text-gray-900 mb-4">Organisasi</h3>
                    <div class="space-y-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Supplier</label>
                            <select name="supplier_id" id="supplier_id" class="w-full" required>
                                @foreach($suppliers as $supplier)
                                    <option value="{{ $supplier->supplier_id }}" @selected(old('supplier_id', $product->supplier_id) == $supplier->supplier_id)>{{ $supplier->supplier_name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Stok Saat Ini</label>
                            <input type="number" name="stock_quantity" value="{{ old('stock_quantity', $product->stock_quantity) }}" class="form-input w-full rounded-lg border-gray-300">
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </form>
</div>
@endsection

@push('scripts')
<script>
    $(document).ready(function() {
        $('#supplier_id').select2({ theme: 'bootstrap-5', width: '100%' });
        $('#unit_id').select2({ theme: 'bootstrap-5', width: '100%' });

        const imageInput = document.getElementById('image');
        const previewContainer = document.getElementById('image-preview-container');
        const previewImage = document.getElementById('image-preview');
        const uploadBox = document.getElementById('image-upload-box');
        const removeBtn = document.getElementById('remove-image');

        imageInput.addEventListener('change', function() {
            const file = this.files[0];
            if (file) {
                const reader = new FileReader();
                reader.onload = function(e) {
                    previewImage.src = e.target.result;
                    previewContainer.classList.remove('hidden');
                    uploadBox.classList.add('hidden');
                }
                reader.readAsDataURL(file);
            }
        });

        removeBtn.addEventListener('click', function() {
            imageInput.value = '';
            previewContainer.classList.add('hidden');
            uploadBox.classList.remove('hidden');
            // Optional: Tambahkan hidden input untuk flag hapus gambar jika didukung backend
        });
    });

    function setupCurrencyInput(displayId, hiddenId) {
        const amountDisplay = document.getElementById(displayId);
        const amountRaw = document.getElementById(hiddenId);
        if (!amountDisplay || !amountRaw) return;

        amountDisplay.addEventListener('input', function(e) {
            let rawValue = e.target.value.replace(/[^0-9]/g, '');
            amountRaw.value = rawValue;
            e.target.value = rawValue ? parseInt(rawValue, 10).toLocaleString('id-ID') : '';
        });
    }

    document.addEventListener('DOMContentLoaded', function() {
        setupCurrencyInput('purchase_price_display', 'purchase_price');
        setupCurrencyInput('selling_price_display', 'selling_price');
    });
</script>
@endpush
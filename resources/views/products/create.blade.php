@extends('layouts.app')

@section('title', 'Tambah Produk Baru')

@section('content')
<div class="max-w-6xl mx-auto">
    
    <form action="{{ route('products.store') }}" method="POST" enctype="multipart/form-data">
        @csrf

        {{-- HEADER HALAMAN --}}
        <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center mb-8">
            <div>
                <div class="flex items-center gap-2 text-sm text-gray-500 mb-1">
                    <a href="{{ route('products.index') }}" class="hover:text-indigo-600 transition">Produk</a>
                    <span>/</span>
                    <span class="text-gray-800">Baru</span>
                </div>
                <h2 class="text-2xl font-bold text-gray-900 tracking-tight">Tambah Produk</h2>
            </div>
            <div class="flex gap-3 mt-4 sm:mt-0">
                <a href="{{ route('products.index') }}" class="px-4 py-2.5 bg-white border border-gray-300 text-gray-700 text-sm font-medium rounded-lg hover:bg-gray-50 transition shadow-sm">
                    Batal
                </a>
                <button type="submit" class="px-6 py-2.5 bg-indigo-600 hover:bg-indigo-700 text-white text-sm font-bold rounded-lg shadow-md transition flex items-center">
                    <i class="bi bi-save mr-2"></i> Simpan Produk
                </button>
            </div>
        </div>

        {{-- ALERT ERROR --}}
        @if ($errors->any())
            <div class="mb-6 bg-red-50 border border-red-200 rounded-lg p-4 flex items-start gap-3">
                <i class="bi bi-x-circle-fill text-red-500 mt-0.5"></i>
                <div>
                    <h3 class="text-sm font-bold text-red-800">Gagal menyimpan produk</h3>
                    <ul class="mt-1 list-disc list-inside text-sm text-red-700">
                        @foreach ($errors->all() as $error) <li>{{ $error }}</li> @endforeach
                    </ul>
                </div>
            </div>
        @endif

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
            
            {{-- KOLOM KIRI (UTAMA) --}}
            <div class="lg:col-span-2 space-y-6">
                
                {{-- CARD 1: INFORMASI UMUM --}}
                <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
                    <h3 class="text-base font-bold text-gray-900 mb-4">Informasi Umum</h3>
                    <div class="space-y-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Nama Produk <span class="text-red-500">*</span></label>
                            <input type="text" name="product_name" value="{{ old('product_name') }}" class="form-input w-full rounded-lg border-gray-300 focus:border-indigo-500 focus:ring-indigo-500" placeholder="Contoh: Sepatu Lari Nike Air" required>
                        </div>
                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Kode Produk (SKU) <span class="text-red-500">*</span></label>
                                <input type="text" name="product_code" value="{{ old('product_code') }}" class="form-input w-full rounded-lg border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 font-mono text-sm" placeholder="PRD-001" required>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Unit / Satuan <span class="text-red-500">*</span></label>
                                <select name="unit_id" id="unit_id" class="form-select w-full rounded-lg border-gray-300 focus:border-indigo-500 focus:ring-indigo-500" required>
                                    <option value="" disabled selected>-- Pilih --</option>
                                    @foreach ($units as $unit)
                                        <option value="{{ $unit->unit_id }}" @selected(old('unit_id') == $unit->unit_id)>{{ $unit->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Deskripsi</label>
                            <textarea name="description" rows="4" class="form-textarea w-full rounded-lg border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 text-sm" placeholder="Jelaskan spesifikasi produk...">{{ old('description') }}</textarea>
                        </div>
                    </div>
                </div>

                {{-- CARD 2: HARGA --}}
                <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
                    <h3 class="text-base font-bold text-gray-900 mb-4">Harga</h3>
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-6">
                        <div>
                            <label class="block text-xs font-bold text-gray-500 uppercase mb-1">Harga Beli (HPP)</label>
                            <div class="relative rounded-md shadow-sm">
                                <div class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3">
                                    <span class="text-gray-500 sm:text-sm">Rp</span>
                                </div>
                                <input type="text" id="purchase_price_display" class="block w-full rounded-lg border-gray-300 pl-10 focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm font-medium" placeholder="0">
                                <input type="hidden" name="purchase_price" id="purchase_price">
                            </div>
                        </div>
                        <div>
                            <label class="block text-xs font-bold text-gray-500 uppercase mb-1">Harga Jual</label>
                            <div class="relative rounded-md shadow-sm">
                                <div class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3">
                                    <span class="text-gray-500 sm:text-sm">Rp</span>
                                </div>
                                <input type="text" id="selling_price_display" class="block w-full rounded-lg border-gray-300 pl-10 focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm font-bold text-gray-900" placeholder="0">
                                <input type="hidden" name="selling_price" id="selling_price">
                            </div>
                        </div>
                    </div>
                </div>

            </div>

            {{-- KOLOM KANAN (SIDEBAR) --}}
            <div class="space-y-6">
                
                {{-- CARD 3: MEDIA (GAMBAR) --}}
                <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
                    <h3 class="text-base font-bold text-gray-900 mb-4">Media</h3>
                    
                    <div class="flex flex-col items-center justify-center w-full">
                        {{-- Preview Container --}}
                        <div id="image-preview-container" class="hidden mb-4 w-full relative group">
                            <img id="image-preview" src="#" class="w-full h-48 object-cover rounded-lg border border-gray-200">
                            <button type="button" id="remove-image" class="absolute top-2 right-2 bg-white rounded-full p-1 shadow-md hover:bg-red-50 text-red-600 transition">
                                <i class="bi bi-x text-xl leading-none"></i>
                            </button>
                        </div>

                        {{-- Upload Box --}}
                        <label for="image" id="image-upload-box" class="flex flex-col items-center justify-center w-full h-40 border-2 border-gray-300 border-dashed rounded-lg cursor-pointer bg-gray-50 hover:bg-gray-100 transition">
                            <div class="flex flex-col items-center justify-center pt-5 pb-6">
                                <i class="bi bi-cloud-arrow-up text-3xl text-gray-400 mb-2"></i>
                                <p class="mb-1 text-sm text-gray-500"><span class="font-semibold">Klik upload</span> atau drag</p>
                                <p class="text-xs text-gray-500">JPG, PNG (Max 2MB)</p>
                            </div>
                            <input id="image" name="image" type="file" class="hidden" accept="image/*" />
                        </label>
                    </div>
                </div>

                {{-- CARD 4: ORGANISASI --}}
                <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
                    <h3 class="text-base font-bold text-gray-900 mb-4">Organisasi</h3>
                    
                    <div class="space-y-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Supplier <span class="text-red-500">*</span></label>
                            <select name="supplier_id" id="supplier_id" class="w-full" required>
                                <option value="" disabled selected>-- Pilih Supplier --</option>
                                @foreach($suppliers as $supplier)
                                    <option value="{{ $supplier->supplier_id }}" @selected(old('supplier_id') == $supplier->supplier_id)>{{ $supplier->supplier_name }}</option>
                                @endforeach
                            </select>
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Stok Awal</label>
                            <input type="number" name="stock_quantity" value="{{ old('stock_quantity') }}" class="form-input w-full rounded-lg border-gray-300 focus:border-indigo-500 focus:ring-indigo-500" placeholder="0">
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
        // Init Select2 Modern
        $('#supplier_id').select2({ theme: 'bootstrap-5', width: '100%' });
        $('#unit_id').select2({ theme: 'bootstrap-5', width: '100%' });

        // Image Preview Logic
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
        });
    });

    // Currency Helper
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
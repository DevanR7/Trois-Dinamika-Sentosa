@extends('layouts.app')

@section('content')
<div class="container-fluid py-2">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h3 class="fw-bold text-dark mb-1">Edit Produk</h3>
            <p class="text-muted mb-0 small">Perbarui informasi produk: <span class="text-primary fw-bold">{{ $product->product_name }}</span></p>
        </div>
        <div>
            <a href="{{ route('products.index') }}" class="btn btn-outline-secondary btn-sm">
                <i class="bi bi-arrow-left me-1"></i> Kembali
            </a>
        </div>
    </div>

    <div class="row justify-content-center">
        <div class="col-lg-10">
            @if ($errors->any())
                <div class="alert alert-danger border-0 shadow-sm rounded-3 mb-4">
                    <ul class="mb-0 small ps-3">
                        @foreach ($errors->all() as $error)<li>{{ $error }}</li>@endforeach
                    </ul>
                </div>
            @endif

            <form action="{{ route('products.update', $product->product_id) }}" method="POST" enctype="multipart/form-data">
                @csrf
                @method('PUT')
                
                <div class="card card-transaction border-0 shadow-sm">
                    <div class="card-header bg-white p-4 border-bottom">
                        <div class="form-section-title mb-0"><i class="bi bi-pencil-square"></i> Edit Data Produk</div>
                    </div>
                    
                    <div class="card-body p-4">
                        
                        <h6 class="fw-bold text-dark mb-3">Informasi Dasar</h6>
                        <div class="row g-3 mb-4">
                            <div class="col-md-6">
                                <label for="product_code" class="form-label fw-bold small text-muted">KODE PRODUK</label>
                                <input type="text" class="form-control" id="product_code" name="product_code" value="{{ old('product_code', $product->product_code) }}" required>
                            </div>
                            <div class="col-md-6">
                                <label for="product_name" class="form-label fw-bold small text-muted">NAMA PRODUK</label>
                                <input type="text" class="form-control" id="product_name" name="product_name" value="{{ old('product_name', $product->product_name) }}" required>
                            </div>
                            <div class="col-md-6">
                                <label for="supplier_id" class="form-label fw-bold small text-muted">SUPPLIER</label>
                                <select name="supplier_id" id="supplier_id" class="form-select" required>
                                    @foreach($suppliers as $supplier)
                                        <option value="{{ $supplier->supplier_id }}" {{ old('supplier_id', $product->supplier_id) == $supplier->supplier_id ? 'selected' : '' }}>
                                            {{ $supplier->supplier_name }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label for="unit_id" class="form-label fw-bold small text-muted">SATUAN UNIT</label>
                                <select class="form-select" id="unit_id" name="unit_id" required>
                                    @foreach ($units as $unit)
                                        <option value="{{ $unit->unit_id }}" {{ old('unit_id', $product->unit_id) == $unit->unit_id ? 'selected' : '' }}>
                                            {{ $unit->name }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-12">
                                <label for="description" class="form-label fw-bold small text-muted">DESKRIPSI (OPSIONAL)</label>
                                <textarea class="form-control" id="description" name="description" rows="3">{{ old('description', $product->description) }}</textarea>
                            </div>
                        </div>

                        <hr class="border-dashed">

                        <h6 class="fw-bold text-dark mb-3">Harga & Stok</h6>
                        <div class="row g-3 mb-4">
                            <div class="col-md-4">
                                <label for="purchase_price_display" class="form-label fw-bold small text-muted">HARGA BELI (OPSIONAL)</label>
                                <div class="input-group">
                                    <span class="input-group-text bg-light text-muted">Rp</span>
                                    <input type="text" class="form-control" id="purchase_price_display" placeholder="0" 
                                           value="{{ old('purchase_price', $product->purchase_price) ? number_format(old('purchase_price', $product->purchase_price), 0, ',', '.') : '' }}">
                                    <input type="hidden" name="purchase_price" id="purchase_price" value="{{ old('purchase_price', $product->purchase_price) }}">
                                </div>
                            </div>
                            <div class="col-md-4">
                                <label for="selling_price_display" class="form-label fw-bold small text-muted">HARGA JUAL (OPSIONAL)</label>
                                <div class="input-group">
                                    <span class="input-group-text bg-light text-muted">Rp</span>
                                    <input type="text" class="form-control" id="selling_price_display" placeholder="0" 
                                           value="{{ old('selling_price', $product->selling_price) ? number_format(old('selling_price', $product->selling_price), 0, ',', '.') : '' }}">
                                    <input type="hidden" name="selling_price" id="selling_price" value="{{ old('selling_price', $product->selling_price) }}">
                                </div>
                            </div>
                            <div class="col-md-4">
                                <label for="stock_quantity" class="form-label fw-bold small text-muted">STOK SAAT INI</label>
                                <input type="number" class="form-control" id="stock_quantity" name="stock_quantity" value="{{ old('stock_quantity', $product->stock_quantity) }}" placeholder="0">
                            </div>
                        </div>

                        <hr class="border-dashed">

                        <h6 class="fw-bold text-dark mb-3">Gambar Produk</h6>
                        <div class="row align-items-center">
                            @if ($product->image_path)
                                <div class="col-md-2">
                                    <img src="{{ asset('storage/' . $product->image_path) }}" alt="Preview" class="img-thumbnail rounded" style="width: 100px; height: 100px; object-fit: cover;">
                                </div>
                            @endif
                            <div class="col-md-{{ $product->image_path ? '10' : '12' }}">
                                <input class="form-control" type="file" id="image" name="image" accept="image/png, image/jpeg, image/jpg">
                                <div class="form-text small">Biarkan kosong jika tidak ingin mengubah gambar. Format: JPG, PNG. Max 2MB.</div>
                            </div>
                        </div>

                        <div class="d-flex justify-content-end gap-2 mt-4 pt-3 border-top">
                            <a href="{{ route('products.index') }}" class="btn btn-light border">Batal</a>
                            <button type="submit" class="btn btn-success px-4 fw-bold">
                                <i class="bi bi-check-circle me-1"></i> Update Produk
                            </button>
                        </div>

                    </div>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
    $(document).ready(function() {
        $('#supplier_id').select2({ theme: 'bootstrap-5', placeholder: '-- Pilih Supplier --', width: '100%' });
        $('#unit_id').select2({ theme: 'bootstrap-5', placeholder: '-- Pilih Satuan --', width: '100%' });
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
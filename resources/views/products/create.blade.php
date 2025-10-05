    @extends('layouts.app')

@section('content')
<div class="container py-4">
    <div class="row justify-content-center">
        <div class="col-lg-8">
            <div class="card shadow-sm border-0">
                <div class="card-header bg-primary text-white">
                    <h4 class="mb-0">Tambah Produk Baru</h4>
                </div>
                <div class="card-body p-4">
                    @if ($errors->any())
                        <div class="alert alert-danger"><ul class="mb-0">@foreach ($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>
                    @endif

                    <form action="{{ route('products.store') }}" method="POST" enctype="multipart/form-data">
                        @csrf
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label for="product_code" class="form-label fw-semibold">Kode Produk</label>
                                <input type="text" class="form-control" id="product_code" name="product_code" value="{{ old('product_code') }}" required>
                            </div>
                            <div class="col-md-6">
                                <label for="product_name" class="form-label fw-semibold">Nama Produk</label>
                                <input type="text" class="form-control" id="product_name" name="product_name" value="{{ old('product_name') }}" required>
                            </div>

                            <div class="col-md-12">
                                <label for="supplier_id" class="form-label fw-semibold">Supplier</label>
                                <select name="supplier_id" id="supplier_id" class="form-select" required>
                                    {{-- ... (isi dropdown supplier tidak berubah) ... --}}
                                </select>
                            </div>

                            {{-- TAMBAHKAN KOLOM DESKRIPSI DI SINI --}}
                            <div class="col-12">
                                <label for="description" class="form-label fw-semibold">Deskripsi (Opsional)</label>
                                <textarea class="form-control" id="description" name="description" rows="3">{{ old('description') }}</textarea>
                            </div>

                            <div class="col-md-6">
                                <label for="purchase_price_display" class="form-label fw-semibold">Harga Beli (Opsional)</label>
                                <div class="input-group">
                                    <span class="input-group-text">Rp</span>
                                    <input type="text" class="form-control" id="purchase_price_display" placeholder="0" value="{{ old('purchase_price') ? number_format(old('purchase_price')) : '' }}">
                                    <input type="hidden" name="purchase_price" id="purchase_price" value="{{ old('purchase_price') }}">
                                </div>
                            </div>

                            <div class="col-md-6">
                                <label for="selling_price_display" class="form-label fw-semibold">Harga Jual (Opsional)</label>
                                <div class="input-group">
                                    <span class="input-group-text">Rp</span>
                                    <input type="text" class="form-control" id="selling_price_display" placeholder="0" value="{{ old('selling_price') ? number_format(old('selling_price')) : '' }}">
                                    {{-- Hapus 'required' dari input tersembunyi --}}
                                    <input type="hidden" name="selling_price" id="selling_price" value="{{ old('selling_price') }}">
                                </div>
                            </div>

                            <div class="col-md-6">
                                <label for="stock_quantity" class="form-label fw-semibold">Jumlah Stok Awal</label>
                                <input type="number" class="form-control" id="stock_quantity" name="stock_quantity" value="{{ old('stock_quantity') }}" placeholder="0">
                            </div>

                            <div class="col-md-12">
                                <label for="unit_id" class="form-label fw-semibold">Satuan Unit</label>
                                <select class="form-select" id="unit_id" name="unit_id" required>
                                    {{-- ... (isi dropdown unit tidak berubah) ... --}}
                                </select>
                            </div>
                        </div>

                        <div class="d-flex justify-content-end mt-4">
                            <a href="{{ route('products.index') }}" class="btn btn-secondary me-2">Batal</a>
                            <button type="submit" class="btn btn-primary">Simpan Produk</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

    @push('scripts')
    <script>
    // Fungsi helper untuk format input Rupiah
    function setupCurrencyInput(displayId, hiddenId) {
        const amountDisplay = document.getElementById(displayId);
        const amountRaw = document.getElementById(hiddenId);

        if (!amountDisplay || !amountRaw) return;

        amountDisplay.addEventListener('input', function(e) {
            let rawValue = e.target.value.replace(/[^0-9]/g, '');
            amountRaw.value = rawValue;
            if (rawValue) {
                e.target.value = parseInt(rawValue, 10).toLocaleString('id-ID');
            } else {
                e.target.value = '';
            }
        });
    }

    document.addEventListener('DOMContentLoaded', function() {
        // Terapkan fungsi ke kedua input harga
        setupCurrencyInput('purchase_price_display', 'purchase_price');
        setupCurrencyInput('selling_price_display', 'selling_price');
    });
</script>
@endpush
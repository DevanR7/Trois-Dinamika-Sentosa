@extends('layouts.app')

@section('content')
<div class="container-fluid py-2">
    {{-- HEADER --}}
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h3 class="fw-bold text-dark mb-1">Detail Produk</h3>
            <p class="text-muted mb-0 small">Informasi lengkap stok barang</p>
        </div>
        <a href="{{ route('products.index') }}" class="btn btn-outline-secondary btn-sm">
            <i class="bi bi-arrow-left me-1"></i> Kembali
        </a>
    </div>

    <div class="row justify-content-center">
        <div class="col-lg-10">
            <div class="card card-transaction border-0 shadow-sm">
                <div class="card-body p-4">
                    <div class="row g-5 align-items-start">
                        
                        {{-- KOLOM KIRI: GAMBAR --}}
                        <div class="col-md-4 text-center">
                            <div class="p-2 border rounded bg-light d-inline-block shadow-sm">
                                @if ($product->image_path)
                                    <img src="{{ asset('storage/' . $product->image_path) }}" 
                                         alt="{{ $product->product_name }}" 
                                         class="img-fluid rounded" 
                                         style="max-height: 300px; object-fit: contain;">
                                @else
                                    <div class="d-flex align-items-center justify-content-center bg-white rounded text-muted" 
                                         style="width: 250px; height: 250px;">
                                        <div class="text-center">
                                            <i class="bi bi-image fs-1 opacity-25"></i>
                                            <p class="small mt-2 mb-0">Tidak ada gambar</p>
                                        </div>
                                    </div>
                                @endif
                            </div>
                        </div>

                        {{-- KOLOM KANAN: DETAIL --}}
                        <div class="col-md-8">
                            <div class="d-flex justify-content-between align-items-start">
                                <div>
                                    <h2 class="fw-bold text-dark mb-1">{{ $product->product_name }}</h2>
                                    <span class="badge bg-light text-dark border px-3 py-2 fs-6 fw-normal mt-1">
                                        <i class="bi bi-upc-scan me-1"></i> {{ $product->product_code }}
                                    </span>
                                </div>
                                <div class="text-end">
                                    <small class="text-muted d-block">STOK SAAT INI</small>
                                    <span class="fs-3 fw-bold {{ $product->stock_quantity > 0 ? 'text-success' : 'text-danger' }}">
                                        {{ $product->stock_quantity ?? 0 }}
                                    </span>
                                    <span class="text-muted ms-1">{{ $product->unit->name ?? '' }}</span>
                                </div>
                            </div>

                            <hr class="border-dashed my-4">

                            <div class="row g-4">
                                <div class="col-sm-6">
                                    <label class="small fw-bold text-muted d-block mb-1">SUPPLIER</label>
                                    <span class="fs-5">{{ $product->supplier->supplier_name ?? '-' }}</span>
                                </div>
                                <div class="col-sm-6">
                                    <label class="small fw-bold text-muted d-block mb-1">DESKRIPSI</label>
                                    <p class="mb-0 text-dark">{{ $product->description ?? '-' }}</p>
                                </div>
                                <div class="col-sm-6">
                                    <label class="small fw-bold text-muted d-block mb-1">HARGA BELI (HPP)</label>
                                    <span class="fs-5">Rp {{ number_format($product->purchase_price ?? 0, 0, ',', '.') }}</span>
                                </div>
                                <div class="col-sm-6">
                                    <label class="small fw-bold text-muted d-block mb-1">HARGA JUAL</label>
                                    <span class="fs-5 text-primary fw-bold">Rp {{ number_format($product->selling_price ?? 0, 0, ',', '.') }}</span>
                                </div>
                            </div>

                            <hr class="border-dashed my-4">

                            <div class="d-flex justify-content-between align-items-center">
                                <small class="text-muted">
                                    Dibuat: {{ $product->created_at->format('d M Y') }} <br>
                                    Update Terakhir: {{ $product->updated_at->format('d M Y, H:i') }}
                                </small>

                                <div class="btn-group">
                                    @can('update', $product)
                                        <a href="{{ route('products.edit', $product->product_id) }}" class="btn btn-warning px-4 fw-bold text-dark">
                                            <i class="bi bi-pencil-square me-2"></i> Edit Produk
                                        </a>
                                    @endcan
                                    
                                    @can('delete', $product)
                                        <form action="{{ route('products.destroy', $product->product_id) }}" method="POST" class="d-inline form-delete" data-product-name="{{ e($product->product_name) }}">
                                            @csrf @method('DELETE')
                                            <button type="submit" class="btn btn-outline-danger ms-2" title="Hapus">
                                                <i class="bi bi-trash"></i>
                                            </button>
                                        </form>
                                    @endcan
                                </div>
                            </div>
                        </div>

                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function () {
        const deleteForms = document.querySelectorAll('.form-delete');
        deleteForms.forEach(form => {
            form.addEventListener('submit', function(event) {
                event.preventDefault(); 
                const productName = this.dataset.productName;
                Swal.fire({
                    title: 'Hapus Produk?',
                    text: `Anda yakin ingin menghapus "${productName}"?`,
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonColor: '#d33',
                    cancelButtonColor: '#6c757d',
                    confirmButtonText: 'Ya, Hapus',
                    cancelButtonText: 'Batal'
                }).then((result) => {
                    if (result.isConfirmed) {
                        this.submit();
                    }
                });
            });
        });
    });
</script>
@endpush
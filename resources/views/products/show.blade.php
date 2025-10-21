@extends('layouts.app')

@section('content')
<div class="container py-4">
    <div class="row justify-content-center">
        <div class="col-lg-10">
            <div class="card shadow-sm border-0">
                <div class="card-header bg-light d-flex justify-content-between align-items-center">
                    <h4 class="mb-0">Detail Produk</h4>
                    <a href="{{ route('products.index') }}" class="btn btn-outline-secondary">
                        <i class="bi bi-arrow-left"></i> Kembali ke Daftar
                    </a>
                </div>
                <div class="card-body p-4">
                    <div class="row g-4">
                        {{-- Kolom Kiri: Gambar --}}
                        <div class="col-md-5 text-center">
                            @if ($product->image_path)
                                <img src="{{ asset('storage/' . $product->image_path) }}" 
                                     alt="{{ $product->product_name }}" 
                                     class="img-fluid rounded shadow-sm" 
                                     style="max-height: 400px; object-fit: contain;">
                            @else
                                <div class="d-flex align-items-center justify-content-center bg-light rounded shadow-sm text-muted" 
                                     style="min-height: 300px; max-height: 400px; width: 100%;">
                                    <i class="bi bi-box-seam" style="font-size: 8rem;"></i>
                                </div>
                            @endif
                        </div>

                        {{-- Kolom Kanan: Detail Info --}}
                        <div class="col-md-7">
                            <h2 class="fw-bold mb-1">{{ $product->product_name }}</h2>
                            <span class="badge bg-secondary fs-6 mb-3">{{ $product->product_code }}</span>

                            {{-- Tombol Aksi Edit/Hapus --}}
                            <div class="mb-3">
                                @can('update', $product)
                                    <a href="{{ route('products.edit', $product->product_id) }}" class="btn btn-warning me-2">
                                        <i class="bi bi-pencil-square"></i> Edit
                                    </a>
                                @endcan
                                
                                @can('delete', $product)
                                    {{-- Form Hapus (terhubung ke SweetAlert via class 'form-delete') --}}
                                    <form action="{{ route('products.destroy', $product->product_id) }}" method="POST" 
                                          class="d-inline form-delete"
                                          data-product-name="{{ e($product->product_name) }}">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="btn btn-danger">
                                            <i class="bi bi-trash"></i> Hapus
                                        </button>
                                    </form>
                                @endcan
                            </div>

                            <hr>

                            {{-- Daftar Detail --}}
                            <dl class="row g-3">
                                <dt class="col-sm-4 fw-semibold">Supplier</dt>
                                <dd class="col-sm-8">{{ $product->supplier->supplier_name ?? 'N/A' }}</dd>

                                <dt class="col-sm-4 fw-semibold">Stok Saat Ini</dt>
                                <dd class="col-sm-8">{{ $product->stock_quantity ?? 0 }}</dd>

                                <dt class="col-sm-4 fw-semibold">Satuan Unit</dt>
                                <dd class="col-sm-8">{{ $product->unit->name ?? 'N/A' }}</dd>

                                <dt class="col-sm-4 fw-semibold">Harga Beli</dt>
                                <dd class="col-sm-8 text-primary fw-bold">Rp {{ number_format($product->purchase_price ?? 0, 0, ',', '.') }}</dd>
                                
                                <dt class="col-sm-4 fw-semibold">Harga Jual</dt>
                                <dd class="col-sm-8 text-success fw-bold">Rp {{ number_format($product->selling_price ?? 0, 0, ',', '.') }}</dd>
                                
                                <dt class="col-sm-4 fw-semibold">Deskripsi</dt>
                                <dd class="col-sm-8">
                                    <p style="white-space: pre-wrap;">{{ $product->description ?? 'Tidak ada deskripsi.' }}</p>
                                </dd>

                                <dt class="col-sm-4 fw-semibold">Tanggal Dibuat</dt>
                                <dd class="col-sm-8">{{ $product->created_at->format('d M Y, H:i') }}</dd>

                                <dt class="col-sm-4 fw-semibold">Terakhir Diupdate</dt>
                                <dd class="col-sm-8">{{ $product->updated_at->format('d M Y, H:i') }}</dd>
                            </dl>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

{{-- Script untuk memicu SweetAlert --}}
@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function () {
        // Temukan form hapus di halaman ini
        const deleteForm = document.querySelector('.form-delete');
        
        // Cek jika form-nya ada
        if (deleteForm) {
            deleteForm.addEventListener('submit', function(event) {
                // Hentikan form agar tidak langsung terkirim
                event.preventDefault(); 
                
                // Ambil nama produk dari data-attribute
                const productName = this.dataset.productName;

                // Tampilkan SweetAlert
                Swal.fire({
                    title: 'Apakah Anda yakin?',
                    text: `Anda akan menghapus produk "${productName}". Tindakan ini tidak dapat dibatalkan!`,
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonColor: '#d33',
                    cancelButtonColor: '#6c757d',
                    confirmButtonText: 'Ya, hapus!',
                    cancelButtonText: 'Batal'
                }).then((result) => {
                    // Jika user menekan "Ya, hapus!"
                    if (result.isConfirmed) {
                        // Lanjutkan submit form
                        this.submit();
                    }
                });
            });
        }
    });
</script>
@endpush
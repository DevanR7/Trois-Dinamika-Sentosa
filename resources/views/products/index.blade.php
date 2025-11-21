@extends('layouts.app')

@section('content')
<div class="container-fluid py-4">
    
    {{-- HEADER --}}
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h3 class="fw-bold text-dark mb-0">Manajemen Produk</h3>
            <p class="text-muted small mb-0">Daftar stok dan inventaris barang</p>
        </div>
        @can('create', App\Models\Product::class)
            <a href="{{ route('products.create') }}" class="btn btn-primary shadow-sm">
                <i class="bi bi-plus-lg me-2"></i> Tambah Produk Baru
            </a>
        @endcan
    </div>

    {{-- ALERT --}}
    @if (session('success'))
        <div class="alert alert-success border-0 shadow-sm d-flex align-items-center mb-4">
            <i class="bi bi-check-circle-fill fs-4 me-2"></i>
            <div>{{ session('success') }}</div>
            <button type="button" class="btn-close ms-auto" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif
    @if (session('error'))
        <div class="alert alert-danger border-0 shadow-sm d-flex align-items-center mb-4">
            <i class="bi bi-exclamation-triangle-fill fs-4 me-2"></i>
            <div>{{ session('error') }}</div>
            <button type="button" class="btn-close ms-auto" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

    {{-- FILTER CARD --}}
    <div class="card card-transaction border-0 shadow-sm mb-4">
        <div class="card-body p-3">
            <form action="{{ route('products.index') }}" method="GET" class="row g-2 align-items-center">
                <div class="col-md-5">
                    <div class="input-group">
                        <span class="input-group-text bg-white border-end-0 text-muted"><i class="bi bi-search"></i></span>
                        <input type="text" name="search" id="search" class="form-control border-start-0 ps-0" placeholder="Cari Nama atau Kode Produk..." value="{{ request('search') }}">
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="input-group">
                        <span class="input-group-text bg-light text-muted small">Sort</span>
                        <select name="sort" id="sort" class="form-select">
                            <option value="" disabled @selected(!request('sort'))>-- Urutkan --</option>
                            <option value="A-Z" @selected(request('sort') == 'A-Z')>Nama (A-Z)</option>
                            <option value="Z-A" @selected(request('sort') == 'Z-A')>Nama (Z-A)</option>
                            <option value="stok-terbanyak" @selected(request('sort') == 'stok-terbanyak')>Stok Terbanyak</option>
                            <option value="stok-sedikit" @selected(request('sort') == 'stok-sedikit')>Stok Terendah</option>
                        </select>
                    </div>
                </div>
                <div class="col-md-3 d-flex gap-2">
                    <button type="submit" class="btn btn-dark w-100">Filter</button>
                    <a href="{{ route('products.index') }}" class="btn btn-light border text-muted" title="Reset"><i class="bi bi-arrow-clockwise"></i></a>
                </div>
            </form>
        </div>
    </div>

    {{-- TABEL PRODUK --}}
    <div class="card card-transaction border-0 shadow-sm">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover table-transaction align-middle mb-0">
                    <thead>
                        <tr>
                            <th class="ps-4" style="width: 50px;">#</th>
                            <th>Produk</th>
                            <th>Kode</th>
                            <th>Supplier</th>
                            <th class="text-center">Stok</th>
                            <th>Satuan</th>
                            <th class="text-end">Harga Beli</th>
                            <th class="text-end">Harga Jual</th>
                            <th class="text-center pe-4">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($products as $product)
                            <tr>
                                <td class="ps-4 text-muted">{{ $loop->iteration + $products->firstItem() - 1 }}</td>
                                <td>
                                    <div class="d-flex align-items-center">
                                        @if ($product->image_path)
                                            <img src="{{ asset('storage/' . $product->image_path) }}" class="rounded me-3" style="width: 40px; height: 40px; object-fit: cover;">
                                        @else
                                            <div class="rounded me-3 bg-light d-flex align-items-center justify-content-center text-muted" style="width: 40px; height: 40px;">
                                                <i class="bi bi-box-seam"></i>
                                            </div>
                                        @endif
                                        <span class="fw-bold text-dark">{{ $product->product_name }}</span>
                                    </div>
                                </td>
                                <td class="text-muted small">{{ $product->product_code }}</td>
                                <td class="small">{{ $product->supplier->supplier_name ?? '-' }}</td>
                                <td class="text-center">
                                    @if(($product->stock_quantity ?? 0) <= 5)
                                        <span class="badge bg-danger bg-opacity-10 text-danger">{{ $product->stock_quantity ?? 0 }}</span>
                                    @else
                                        <span class="badge bg-success bg-opacity-10 text-success">{{ $product->stock_quantity ?? 0 }}</span>
                                    @endif
                                </td>
                                <td class="small text-muted">{{ $product->unit->name ?? '-' }}</td>
                                <td class="text-end small text-muted">Rp {{ number_format($product->purchase_price ?? 0, 0, ',', '.') }}</td>
                                <td class="text-end fw-semibold">Rp {{ number_format($product->selling_price ?? 0, 0, ',', '.') }}</td>
                                <td class="text-center pe-4">
    {{-- Ganti btn-group dengan d-flex gap-1 agar rapi --}}
    <div class="d-flex justify-content-center gap-1">
        
        @can('view', $product)
            <a href="{{ route('products.show', $product->product_id) }}" 
               class="btn btn-sm btn-light border text-primary shadow-sm" 
               title="Detail" 
               style="width: 32px; height: 32px; display: flex; align-items: center; justify-content: center;">
                <i class="bi bi-eye"></i>
            </a>
        @endcan

        @can('update', $product)
            <a href="{{ route('products.edit', $product->product_id) }}" 
               class="btn btn-sm btn-light border text-warning shadow-sm" 
               title="Edit"
               style="width: 32px; height: 32px; display: flex; align-items: center; justify-content: center;">
                <i class="bi bi-pencil-square"></i>
            </a>
        @endcan

        @can('delete', $product)
            <form action="{{ route('products.destroy', $product->product_id) }}" 
                  method="POST" 
                  class="d-inline form-delete" 
                  data-product-name="{{ e($product->product_name) }}">
                @csrf 
                @method('DELETE')
                <button type="submit" 
                        class="btn btn-sm btn-light border text-danger shadow-sm" 
                        title="Hapus"
                        style="width: 32px; height: 32px; display: flex; align-items: center; justify-content: center;">
                    <i class="bi bi-trash"></i>
                </button>
            </form>
        @endcan

    </div>
</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="9" class="text-center py-5 text-muted">
                                    <i class="bi bi-inbox fs-1 d-block mb-2 opacity-50"></i>
                                    Belum ada data produk.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <div class="mt-4 d-flex justify-content-center">
        {{ $products->links() }}
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
                    text: `Anda yakin ingin menghapus "${productName}"? Data yang sudah dihapus tidak bisa dikembalikan.`,
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonColor: '#d33',
                    cancelButtonColor: '#6c757d',
                    confirmButtonText: 'Ya, Hapus!',
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
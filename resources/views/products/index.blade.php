@extends('layouts.app')

@section('content')
<div class="container-fluid py-4">
    {{-- Header Halaman --}}
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2 class="fw-bold">Manajemen Produk</h2>
        @can('create', App\Models\Product::class)
            <a href="{{ route('products.create') }}" class="btn btn-primary">
                <i class="bi bi-plus-lg"></i> Tambah Produk Baru
            </a>
        @endcan
    </div>

    {{-- Notifikasi Sukses/Error --}}
    @if (session('success'))
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            {{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif
    @if (session('error'))
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            {{ session('error') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

    {{-- Filter, Pencarian, dan Sorting --}}
    <div class="card shadow-sm mb-4">
        <div class="card-body">
            <form action="{{ route('products.index') }}" method="GET" class="row g-3 align-items-center">
                <div class="col-md-5">
                    <label for="search" class="visually-hidden">Cari Produk</label>
                    <div class="input-group">
                        <span class="input-group-text"><i class="bi bi-search"></i></span>
                        <input type="text" name="search" id="search" class="form-control" placeholder="Cari berdasarkan Nama atau Kode Produk..." value="{{ request('search') }}">
                    </div>
                </div>
                <div class="col-md-4">
                    <label for="sort" class="visually-hidden">Urutkan</label>
                    <select name="sort" id="sort" class="form-select">
                        <option value="" disabled @selected(!request('sort'))>Urutkan Berdasarkan...</option>
                        <option value="A-Z" @selected(request('sort') == 'A-Z')>Nama (A-Z)</option>
                        <option value="Z-A" @selected(request('sort') == 'Z-A')>Nama (Z-A)</option>
                        <option value="stok-terbanyak" @selected(request('sort') == 'stok-terbanyak')>Stok Terbanyak</option>
                        <option value="stok-sedikit" @selected(request('sort') == 'stok-sedikit')>Stok Terendah</option>
                    </select>
                </div>
                <div class="col-md-3">
                    <button type="submit" class="btn btn-dark w-100">Filter / Cari</button>
                </div>
            </form>
        </div>
    </div>

    {{-- Daftar Produk --}}
    <div class="card shadow-sm border-0">
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-hover align-middle">
                    <thead class="table-light">
                        <tr>
                            <th scope="col">#</th>
                            <th scope="col">Produk</th>
                            <th scope="col">Kode</th>
                            <th scope="col">Supplier</th>
                            <th scope="col" class="text-center">Stok</th>
                            <th scope="col">Satuan</th>
                            <th scope="col" class="text-end">Harga Beli</th>
                            <th scope="col" class="text-end">Harga Jual</th>
                            <th scope="col" class="text-center">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($products as $product)
                            <tr>
                                <th scope="row">{{ $loop->iteration + $products->firstItem() - 1 }}</th>
                                <td>
                                    <div class="d-flex align-items-center">
                                        {{-- Gambar Produk --}}
                                        @if ($product->image_path)
                                            <img src="{{ asset('storage/' . $product->image_path) }}" alt="{{ $product->product_name }}" class="me-2" style="width: 45px; height: 45px; object-fit: cover; border-radius: 5px;">
                                        @else
                                            <div class="me-2" style="width: 45px; height: 45px; background-color: #f0f0f0; border-radius: 5px; display: flex; align-items: center; justify-content: center;">
                                                <i class="bi bi-box-seam text-muted fs-5"></i>
                                            </div>
                                        @endif
                                        {{-- Nama Produk --}}
                                        <strong>{{ $product->product_name }}</strong>
                                    </div>
                                </td>
                                <td>{{ $product->product_code }}</td>
                                {{-- Kita panggil relasi supplier, pastikan supplier ada --}}
                                <td>{{ $product->supplier->supplier_name ?? 'N/A' }}</td>
                                <td class="text-center">{{ $product->stock_quantity ?? 0 }}</td>
                                {{-- Kita panggil relasi unit --}}
                                <td>{{ $product->unit->name ?? 'N/A' }}</td>
                                <td class="text-end">Rp {{ number_format($product->purchase_price ?? 0, 0, ',', '.') }}</td>
                                <td class="text-end">Rp {{ number_format($product->selling_price ?? 0, 0, ',', '.') }}</td>
                                <td class="text-center">
                                    {{-- Tombol Aksi --}}
                                    @can('view', $product)
                                        <a href="{{ route('products.show', $product->product_id) }}" class="btn btn-sm btn-outline-info" title="Detail">
                                            <i class="bi bi-eye"></i>
                                        </a>
                                    @endcan
                                    @can('update', $product)
                                        <a href="{{ route('products.edit', $product->product_id) }}" class="btn btn-sm btn-outline-warning" title="Edit">
                                            <i class="bi bi-pencil-square"></i>
                                        </a>
                                    @endcan
                                    @can('delete', $product)
                                        {{-- Tombol Hapus memerlukan form --}}
                                        <form action="{{ route('products.destroy', $product->product_id) }}" method="POST" class="d-inline" onsubmit="return confirm('Apakah Anda yakin ingin menghapus produk {{ $product->product_name }}?');">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="btn btn-sm btn-outline-danger" title="Hapus">
                                                <i class="bi bi-trash"></i>
                                            </button>
                                        </form>
                                    @endcan
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="9" class="text-center py-4 text-muted">
                                    Tidak ada data produk yang ditemukan.
                                    @if(request('search'))
                                        (untuk pencarian "{{ request('search') }}")
                                    @endif
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    {{-- Pagination --}}
    <div class="mt-4 d-flex justify-content-center">
        {{-- Tampilkan links pagination, dan pastikan parameter filter (search & sort) tetap ada --}}
        {{ $products->links() }}
    </div>
</div>
@endsection
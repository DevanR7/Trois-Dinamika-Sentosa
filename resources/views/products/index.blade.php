@extends("layouts.app")

@section("content")
    <div class="container py-4">
        {{-- Header Halaman --}}
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h2 class="fw-bold">Daftar Produk</h2>
            @can("create", App\Models\Product::class)
                <a
                    href="{{ route("products.create") }}"
                    class="btn btn-primary"
                >
                    <i class="bi bi-plus-circle me-2"></i>
                    Tambah Produk Baru
                </a>
            @endcan
        </div>

        <div class="card shadow-sm mb-4">
        <div class="card-body">
            <form action="{{ route('products.index') }}" method="GET" class="row g-3 align-items-center">
                <div class="col-md-6">
                    <input type="text" name="search" class="form-control" placeholder="Cari Nama / Kode Produk..." value="{{ request('search') }}">
                </div>
                <div class="col-md-4">
                    <select name="sort" class="form-select">
                        <option value="">Urutkan Berdasarkan...</option>
                        <option value="A-Z" {{ request('sort') == 'A-Z' ? 'selected' : '' }}>Nama A-Z</option>
                        <option value="Z-A" {{ request('sort') == 'Z-A' ? 'selected' : '' }}>Nama Z-A</option>
                        <option value="stok-terbanyak" {{ request('sort') == 'stok-terbanyak' ? 'selected' : '' }}>Stok Terbanyak</option>
                        <option value="stok-sedikit" {{ request('sort') == 'stok-sedikit' ? 'selected' : '' }}>Stok Paling Sedikit</option>
                    </select>
                </div>
                <div class="col-md-2">
                    <button type="submit" class="btn btn-dark w-100">Filter</button>
                </div>
            </form>
        </div>
    </div>

        {{-- Notifikasi Sukses --}}
        @if (session("success"))
            <div class="alert alert-success">
                {{ session("success") }}
            </div>
        @endif

        {{-- Grid untuk Kartu Produk --}}
        <div class="row g-4">
            @forelse ($products as $product)
                <div class="col-md-6 col-lg-4 col-xl-3">
                    <div class="card h-100 shadow-sm border-0">
                        <div class="card-body d-flex flex-column">
                            {{-- Nama Produk & Kode --}}
                            <h5 class="card-title fw-semibold">
                                {{ $product->product_name }}
                            </h5>
                            <small class="text-muted mb-2">
                                Kode: {{ $product->product_code }}
                            </small>
                            @if($product->description)
        <p class="card-text text-muted small mb-2" style="font-size: 0.8rem;">
            {{ Str::limit($product->description, 50) }}
        </p>
    @endif
                            {{-- Harga Produk --}}
                            <p class="card-text fs-5 text-primary fw-bold mb-2">
                                Rp
                                {{ number_format($product->selling_price, 0, ",", ".") }}
                            </p>

                            {{-- Stok Produk --}}
                            <small class="card-text mb-3">
                                Stok:
                                <span
                                    class="fw-bold {{ $product->stock_quantity > 10 ? "text-success" : "text-danger" }}"
                                >
                                    {{ $product->stock_quantity }}
                                </span>
                            </small>

                            {{-- Tombol Aksi (diletakkan di bawah) --}}
                            <div
                                class="mt-auto d-flex justify-content-end gap-2"
                            >
                                @can("update", $product)
                                    <a
                                        href="{{ route("products.edit", $product->product_id) }}"
                                        class="btn btn-sm btn-outline-secondary"
                                    >
                                        <i class="bi bi-pencil-square"></i>
                                        Edit
                                    </a>
                                @endcan

                                @can("delete", $product)
                                    <form
                                        class="delete-form"
                                        action="{{ route("products.destroy", $product->product_id) }}"
                                        method="POST"
                                    >
                                        @csrf
                                        @method("DELETE")
                                        <button
                                            type="submit"
                                            class="btn btn-sm btn-outline-danger"
                                        >
                                            <i class="bi bi-trash"></i>
                                            Hapus
                                        </button>
                                    </form>
                                @endcan
                            </div>
                        </div>
                    </div>
                </div>
            @empty
                {{-- Pesan jika tidak ada produk --}}
                <div class="col-12">
                    <div class="alert alert-info text-center">
                        Belum ada produk yang ditambahkan.
                    </div>
                </div>
            @endforelse
        </div>

        {{-- Pagination (jika ada) --}}
        <div class="mt-4 d-flex justify-content-center">
            {{ $products->links() }}
        </div>
    </div>
@endsection

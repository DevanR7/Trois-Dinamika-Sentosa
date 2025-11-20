@extends('layouts.app')

@section('content')
<div class="container py-4">
    <h2 class="fw-bold mb-4">Migrasi Data (Import Excel)</h2>

    @if(session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif
    @if(session('error'))
        <div class="alert alert-danger">{{ session('error') }}</div>
    @endif

    <div class="row g-4">
        
        {{-- KARTU IMPORT PRODUK --}}
        <div class="col-md-6">
            <div class="card shadow-sm h-100">
                <div class="card-header bg-primary text-white">Import Data Produk</div>
                <div class="card-body">
                    <p class="text-muted small">
                        Pastikan header Excel: <code>kode_produk, nama_produk, harga_beli, harga_jual, stok_awal, satuan, nama_supplier, deskripsi</code>
                    </p>
                    <form action="{{ route('migration.import-products') }}" method="POST" enctype="multipart/form-data">
                        @csrf
                        <div class="mb-3">
                            <input type="file" name="file" class="form-control" required>
                        </div>
                        <button type="submit" class="btn btn-primary w-100">Upload & Import Produk</button>
                    </form>
                </div>
            </div>
        </div>

        {{-- KARTU IMPORT KLIEN --}}
        <div class="col-md-6">
            <div class="card shadow-sm h-100">
                <div class="card-header bg-success text-white">Import Data Klien</div>
                <div class="card-body">
                    <p class="text-muted small">
                        Pastikan header Excel: <code>nama_klien, email, no_telepon, alamat, pic</code>
                    </p>
                    <form action="{{ route('migration.import-clients') }}" method="POST" enctype="multipart/form-data">
                        @csrf
                        <div class="mb-3">
                            <input type="file" name="file" class="form-control" required>
                        </div>
                        <button type="submit" class="btn btn-success w-100">Upload & Import Klien</button>
                    </form>
                </div>
            </div>
        </div>

    </div>
</div>
@endsection
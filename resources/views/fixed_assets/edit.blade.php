@extends('layouts.app')

@section('content')
<div class="container py-4">
    <div class="row justify-content-center">
        <div class="col-md-8">
            <div class="card shadow-sm">
                <div class="card-header">
                    <h2 class="fw-bold mb-0">Edit Aset Tetap</h2>
                </div>
                <div class="card-body">
                    <form action="{{ route('fixed-assets.update', $fixedAsset) }}" method="POST">
                        @csrf
                        @method('PUT')
                        
                        <div class="row g-3">
                             <div class="col-12 mb-3">
                                <label for="asset_name" class="form-label">Nama Aset <span class="text-danger">*</span></label>
                                <input type="text" class="form-control @error('asset_name') is-invalid @enderror" id="asset_name" name="asset_name" value="{{ old('asset_name', $fixedAsset->asset_name) }}" required>
                                @error('asset_name')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="col-md-6 mb-3">
                                <label for="purchase_date" class="form-label">Tanggal Beli <span class="text-danger">*</span></label>
                                <input type="date" class="form-control @error('purchase_date') is-invalid @enderror" id="purchase_date" name="purchase_date" value="{{ old('purchase_date', $fixedAsset->purchase_date->format('Y-m-d')) }}" required>
                                @error('purchase_date')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="col-md-6 mb-3">
                                <label for="purchase_cost" class="form-label">Harga Beli (Rp) <span class="text-danger">*</span></label>
                                <input type="number" class="form-control @error('purchase_cost') is-invalid @enderror" id="purchase_cost" name="purchase_cost" value="{{ old('purchase_cost', $fixedAsset->purchase_cost) }}" required>
                                @error('purchase_cost')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="col-12 mb-3">
                                <label for="description" class="form-label">Deskripsi</label>
                                <textarea class="form-control @error('description') is-invalid @enderror" id="description" name="description" rows="3">{{ old('description', $fixedAsset->description) }}</textarea>
                                @error('description')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>

                        <div class="d-flex justify-content-end mt-4">
                            <a href="{{ route('fixed-assets.index') }}" class="btn btn-outline-secondary me-2">Batal</a>
                            <button type="submit" class="btn btn-dark">
                                <i class="bi bi-save-fill"></i> Update Aset
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
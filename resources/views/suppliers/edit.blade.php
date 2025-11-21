@extends('layouts.app')

@section('content')
<div class="container-fluid py-2">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h3 class="fw-bold text-dark mb-1">Edit Supplier</h3>
            <p class="text-muted small mb-0">Perbarui data: <span class="text-primary fw-bold">{{ $supplier->supplier_name }}</span></p>
        </div>
        <a href="{{ route('suppliers.index') }}" class="btn btn-outline-secondary btn-sm">
            <i class="bi bi-arrow-left me-1"></i> Kembali
        </a>
    </div>

    <div class="row justify-content-center">
        <div class="col-lg-8">
            <div class="card card-transaction border-0 shadow-sm">
                <div class="card-header bg-white p-4 border-bottom">
                    <div class="form-section-title mb-0"><i class="bi bi-pencil-square"></i> Edit Data Supplier</div>
                </div>
                <div class="card-body p-4">

                    @if ($errors->any())
                        <div class="alert alert-danger border-0 shadow-sm rounded-3 mb-4">
                            <ul class="mb-0 small ps-3">@foreach ($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul>
                        </div>
                    @endif

                    <form action="{{ route('suppliers.update', $supplier->supplier_id) }}" method="POST">
                        @csrf
                        @method('PUT')
                        <div class="row g-4">
                            <div class="col-md-6">
                                <label for="supplier_name" class="form-label fw-semibold small text-muted">NAMA SUPPLIER <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" id="supplier_name" name="supplier_name" value="{{ old('supplier_name', $supplier->supplier_name) }}" required>
                            </div>
                            <div class="col-md-6">
                                <label for="person_in_charge" class="form-label fw-semibold small text-muted">NARAHUBUNG (PIC)</label>
                                <input type="text" class="form-control" id="person_in_charge" name="person_in_charge" value="{{ old('person_in_charge', $supplier->person_in_charge) }}">
                            </div>
                            <div class="col-md-6">
                                <label for="phone_number" class="form-label fw-semibold small text-muted">NO. TELEPON</label>
                                <input type="text" class="form-control" id="phone_number" name="phone_number" value="{{ old('phone_number', $supplier->phone_number) }}">
                            </div>
                            <div class="col-md-6">
                                <label for="address" class="form-label fw-semibold small text-muted">ALAMAT</label>
                                <textarea class="form-control" id="address" name="address" rows="1">{{ old('address', $supplier->address) }}</textarea>
                            </div>
                            
                            <div class="col-12"><hr class="my-0 border-dashed"></div>
                            
                            <div class="col-12"><h6 class="fw-bold text-dark">Informasi Bank & NPWP (Opsional)</h6></div>

                            <div class="col-md-4">
                                <label for="npwp" class="form-label fw-semibold small text-muted">NPWP</label>
                                <input type="text" class="form-control" id="npwp" name="npwp" value="{{ old('npwp', $supplier->npwp) }}">
                            </div>
                            <div class="col-md-4">
                                <label for="bank_name" class="form-label fw-semibold small text-muted">NAMA BANK</label>
                                <input type="text" class="form-control" id="bank_name" name="bank_name" value="{{ old('bank_name', $supplier->bank_name) }}">
                            </div>
                            <div class="col-md-4">
                                <label for="account_number" class="form-label fw-semibold small text-muted">NO. REKENING</label>
                                <input type="text" class="form-control" id="account_number" name="account_number" value="{{ old('account_number', $supplier->account_number) }}">
                            </div>
                        </div>

                        <div class="d-flex justify-content-end mt-4 pt-3 border-top">
                            <a href="{{ route('suppliers.index') }}" class="btn btn-light border me-2">Batal</a>
                            <button type="submit" class="btn btn-success px-4 fw-bold">Update Supplier</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
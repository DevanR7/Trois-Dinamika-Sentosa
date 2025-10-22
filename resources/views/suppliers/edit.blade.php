@extends('layouts.app')
@section('content')
<div class="container-fluid">
    <h2 class="fw-bold mb-4">Edit Supplier</h2>
    <div class="card shadow-sm border-0">
        <div class="card-body p-4">

            @if ($errors->any())
                <div class="alert alert-danger"><ul class="mb-0">@foreach ($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>
            @endif

            <form action="{{ route('suppliers.update', $supplier->supplier_id) }}" method="POST">
                @csrf
                @method('PUT')
                <div class="row g-3">
                    <div class="col-md-6">
                        <label for="supplier_name" class="form-label fw-semibold">Nama Supplier <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" id="supplier_name" name="supplier_name" value="{{ $supplier->supplier_name }}" required>
                    </div>
                    <div class="col-md-6">
                        <label for="person_in_charge" class="form-label fw-semibold">Narahubung (PIC)</label>
                        <input type="text" class="form-control" id="person_in_charge" name="person_in_charge" value="{{ $supplier->person_in_charge }}">
                    </div>
                    <div class="col-md-6">
                        <label for="phone_number" class="form-label fw-semibold">No. Telepon</label>
                        <input type="text" class="form-control" id="phone_number" name="phone_number" value="{{ $supplier->phone_number }}">
                    </div>
                    <div class="col-md-6">
                        <label for="address" class="form-label fw-semibold">Alamat</label>
                        <textarea class="form-control" id="address" name="address" rows="3">{{ $supplier->address }}</textarea>
                    </div>
                    
                    <div class="col-12"><hr class="my-3"></div>
                    <div class="col-12"><h5 class="fw-semibold">Informasi Bank (Opsional)</h5></div>

                    <div class="col-md-4">
                        <label for="npwp" class="form-label fw-semibold">NPWP</label>
                        <input type="text" class="form-control" id="npwp" name="npwp" value="{{ $supplier->npwp }}">
                    </div>
                    <div class="col-md-4">
                        <label for="bank_name" class="form-label fw-semibold">Nama Bank</label>
                        <input type="text" class="form-control" id="bank_name" name="bank_name" value="{{ $supplier->bank_name }}">
                    </div>
                    <div class="col-md-4">
                        <label for="account_number" class="form-label fw-semibold">No. Rekening</label>
                        <input type="text" class="form-control" id="account_number" name="account_number" value="{{ $supplier->account_number }}">
                    </div>
                </div>

                <div class="d-flex justify-content-end mt-4">
                    <a href="{{ route('suppliers.index') }}" class="btn btn-secondary me-2">Batal</a>
                    <button type="submit" class="btn btn-primary">Update</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
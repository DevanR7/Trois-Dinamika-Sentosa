@extends('layouts.app')
@section('content')
<div class="container-fluid">
    <h2 class="fw-bold mb-4">Edit Supplier</h2>
    <div class="card shadow-sm">
        <div class="card-body">
            <form action="{{ route('suppliers.update', $supplier->supplier_id) }}" method="POST">
                @csrf
                @method('PUT')
                <div class="mb-3">
                    <label for="supplier_name" class="form-label">Nama Supplier</label>
                    <input type="text" class="form-control" id="supplier_name" name="supplier_name" value="{{ $supplier->supplier_name }}" required>
                </div>
                <div class="mb-3">
                    <label for="person_in_charge" class="form-label">Narahubung (PIC)</label>
                    <input type="text" class="form-control" id="person_in_charge" name="person_in_charge" value="{{ $supplier->person_in_charge }}">
                </div>
                <div class="mb-3">
                    <label for="phone_number" class="form-label">No. Telepon</label>
                    <input type="text" class="form-control" id="phone_number" name="phone_number" value="{{ $supplier->phone_number }}">
                </div>
                <div class="mb-3">
                    <label for="address" class="form-label">Alamat</label>
                    <textarea class="form-control" id="address" name="address" rows="3">{{ $supplier->address }}</textarea>
                </div>
                <button type="submit" class="btn btn-primary">Update</button>
            </form>
        </div>
    </div>
</div>
@endsection
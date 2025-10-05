@extends('layouts.app')

@section('content')
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2 class="fw-bold">Manajemen Supplier</h2>
        <a href="{{ route('suppliers.create') }}" class="btn btn-primary">
            <i class="bi bi-plus-circle me-2"></i>Tambah Supplier Baru
        </a>
    </div>
    <div class="card shadow-sm">
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-hover align-middle">
                    <thead class="table-light">
                        <tr>
                            <th>#</th>
                            <th>Nama Supplier</th>
                            <th>Narahubung</th>
                            <th>No. Telepon</th>
                            <th class="text-center">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($suppliers as $supplier)
                        <tr>
                            <th>{{ $loop->iteration }}</th>
                            <td>{{ $supplier->supplier_name }}</td>
                            <td>{{ $supplier->person_in_charge }}</td>
                            <td>{{ $supplier->phone_number }}</td>
                            <td class="text-center">
    <div class="d-flex justify-content-center gap-2">
        <a href="{{ route('suppliers.edit', $supplier->supplier_id) }}" class="btn btn-sm btn-outline-secondary" title="Edit">
            <i class="bi bi-pencil-square"></i> Edit
        </a>

        <form class="delete-form" action="{{ route('suppliers.destroy', $supplier->supplier_id) }}" method="POST">
            @csrf
            @method('DELETE')
            <button type="submit" class="btn btn-sm btn-outline-danger" title="Hapus">
                <i class="bi bi-trash"></i> Hapus
            </button>
        </form>
    </div>
</td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="5" class="text-center">Tidak ada data supplier.</td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
@endsection
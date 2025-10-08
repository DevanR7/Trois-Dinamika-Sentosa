@extends('layouts.app')
@section('content')
<div class="container py-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2 class="fw-bold">Retur Pembelian</h2>
        <a href="{{ route('purchase-returns.create') }}" class="btn btn-primary">
            <i class="bi bi-plus-circle me-2"></i>Buat Retur Baru
        </a>
    </div>
    @if (session('success'))<div class="alert alert-success">{{ session('success') }}</div>@endif
    <div class="card shadow-sm border-0">
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-hover align-middle">
                    <thead class="table-light">
                        <tr>
                            <th>No. Retur</th>
                            <th>Supplier</th>
                            <th>No. PO Asli</th>
                            <th>Tanggal Retur</th>
                            <th class="text-end">Total Nilai Retur</th>
                            <th class="text-center">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($purchaseReturns as $return)
                        <tr>
                            <td><a href="{{ route('purchase-returns.show', $return->return_id) }}" class="fw-semibold text-decoration-none">{{ $return->return_number }}</a></td>
                            <td>{{ $return->supplier->supplier_name ?? 'N/A' }}</td>
                            <td>{{ $return->purchaseOrder->po_number ?? 'N/A' }}</td>
                            <td>{{ optional($return->return_date)->format('d M Y') }}</td>
                            <td class="text-end">Rp {{ number_format($return->total_amount, 0, ',', '.') }}</td>
                            <td class="text-center">
                                <a href="{{ route('purchase-returns.show', $return->return_id) }}" class="btn btn-sm btn-outline-info" title="Detail"><i class="bi bi-eye"></i></a>
                            </td>
                        </tr>
                        @empty
                        <tr><td colspan="6" class="text-center">Belum ada data retur pembelian.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    <div class="mt-4 d-flex justify-content-center">{{ $purchaseReturns->links() }}</div>
</div>
@endsection
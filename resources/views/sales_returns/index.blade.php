@extends('layouts.app')

@section('content')
<div class="container py-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2 class="fw-bold">Retur Penjualan</h2>
        <a href="{{ route('sales-returns.create') }}" class="btn btn-primary">
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
                            <th>Klien</th>
                            <th>No. Invoice Asli</th>
                            <th>Tanggal Retur</th>
                            <th class="text-end">Total Nilai Retur</th>
                            <th class="text-center">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($salesReturns as $return)
                        <tr>
                            <td>
                                <a href="{{ route('sales-returns.show', $return->return_id) }}" class="fw-semibold text-decoration-none">
                                    {{ $return->return_number }}
                                </a>
                            </td>
                            <td>{{ $return->client->client_name ?? 'N/A' }}</td>
                            <td>{{ $return->salesInvoice->invoice_number ?? 'N/A' }}</td>
                            <td>{{ optional($return->return_date)->format('d M Y') }}</td>
                            <td class="text-end">Rp {{ number_format($return->total_amount, 0, ',', '.') }}</td>
                            <td class="text-center">
                                <a href="{{ route('sales-returns.show', $return->return_id) }}" class="btn btn-sm btn-outline-info" title="Detail"><i class="bi bi-eye"></i></a>
                            </td>
                        </tr>
                        @empty
                        <tr><td colspan="6" class="text-center">Belum ada data retur penjualan.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    <div class="mt-4 d-flex justify-content-center">
        {{ $salesReturns->links() }}
    </div>
</div>
@endsection
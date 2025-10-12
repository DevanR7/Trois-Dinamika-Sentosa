@extends('layouts.app')

@section('content')
<div class="container-fluid py-4">
    <h2 class="fw-bold mb-4">Laporan Keuangan</h2>

    {{-- FORM FILTER --}}
    <div class="card shadow-sm mb-4">
        <div class="card-body">
            <form action="{{ route('reports.index') }}" method="GET" class="row g-3 align-items-center">
                <div class="col-md-5">
                    <label for="start_date" class="form-label">Dari Tanggal</label>
                    <input type="date" name="start_date" id="start_date" class="form-control" value="{{ $startDate }}">
                </div>
                <div class="col-md-5">
                    <label for="end_date" class="form-label">Sampai Tanggal</label>
                    <input type="date" name="end_date" id="end_date" class="form-control" value="{{ $endDate }}">
                </div>
                <div class="col-md-2 align-self-end">
                    <button type="submit" class="btn btn-dark w-100"><i class="bi bi-funnel-fill"></i> Tampilkan</button>
                </div>
            </form>
        </div>
    </div>

    {{-- Laporan Pemasukan & Pengeluaran --}}
    <div class="row g-4 mb-4">
        <div class="col-md-6">
            <div class="card shadow-sm h-100">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="mb-0 fw-semibold">Laporan Pemasukan</h5>
                    <span class="fw-bold fs-5 text-success">Rp {{ number_format($totalPemasukan, 0, ',', '.') }}</span>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-sm table-striped">
                            <thead><tr><th>Tanggal</th><th>Dari Invoice</th><th class="text-end">Jumlah</th></tr></thead>
                            <tbody>
                                @forelse ($pemasukan as $item)
                                <tr>
                                    <td>{{ optional($item->payment_date)->format('d/m/Y') }}</td>
                                    <td><a href="{{ route('invoices.show', $item->invoice_id) }}">{{ $item->salesInvoice->invoice_number ?? 'N/A' }}</a></td>
                                    <td class="text-end">Rp {{ number_format($item->amount, 0, ',', '.') }}</td>
                                </tr>
                                @empty
                                <tr><td colspan="3" class="text-center text-muted">Tidak ada pemasukan.</td></tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-6">
            <div class="card shadow-sm h-100">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="mb-0 fw-semibold">Laporan Pengeluaran</h5>
                     <span class="fw-bold fs-5 text-danger">Rp {{ number_format($totalPengeluaran, 0, ',', '.') }}</span>
                </div>
                 <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-sm table-striped">
                            <thead><tr><th>Tanggal</th><th>Untuk PO</th><th class="text-end">Jumlah</th></tr></thead>
                            <tbody>
                                @forelse ($pengeluaran as $item)
                                <tr>
                                    <td>{{ optional($item->payment_date)->format('d/m/Y') }}</td>
                                    <td><a href="{{ route('purchase-orders.show', $item->po_id) }}">{{ $item->purchaseOrder->po_number ?? 'N/A' }}</a></td>
                                    <td class="text-end">Rp {{ number_format($item->amount, 0, ',', '.') }}</td>
                                </tr>
                                @empty
                                <tr><td colspan="3" class="text-center text-muted">Tidak ada pengeluaran.</td></tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Laporan Utang & Piutang --}}
    <div class="row g-4">
        <div class="col-md-6">
            <div class="card shadow-sm h-100">
                <div class="card-header"><h5 class="mb-0 fw-semibold">Daftar Piutang (Tagihan ke Klien)</h5></div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-sm table-striped">
                            <thead><tr><th>Invoice</th><th>Klien</th><th class="text-end">Sisa Tagihan</th></tr></thead>
                            <tbody>
                                @forelse ($laporanPiutang as $invoice)
                                <tr>
                                    <td><a href="{{ route('invoices.show', $invoice->invoice_id) }}">{{ $invoice->invoice_number }}</a></td>
                                    <td>{{ $invoice->client->client_name }}</td>
                                    <td class="text-end">Rp {{ number_format($invoice->total_amount - $invoice->amount_paid, 0, ',', '.') }}</td>
                                </tr>
                                @empty
                                <tr><td colspan="3" class="text-center text-muted">Tidak ada piutang.</td></tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-6">
            <div class="card shadow-sm h-100">
                <div class="card-header"><h5 class="mb-0 fw-semibold">Daftar Utang (Tagihan dari Supplier)</h5></div>
                 <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-sm table-striped">
                            <thead><tr><th>No. PO</th><th>Supplier</th><th class="text-end">Sisa Utang</th></tr></thead>
                            <tbody>
                                @forelse ($laporanUtang as $po)
                                <tr>
                                    <td><a href="{{ route('purchase-orders.show', $po->po_id) }}">{{ $po->po_number }}</a></td>
                                    <td>{{ $po->supplier->supplier_name }}</td>
                                    <td class="text-end">Rp {{ number_format($po->total_amount - $po->total_returned - $po->amount_paid, 0, ',', '.') }}</td>
                                </tr>
                                @empty
                                <tr><td colspan="3" class="text-center text-muted">Tidak ada utang.</td></tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
@extends('layouts.client')

@section('content')
    <h2 class="fw-bold mb-4">Riwayat Invoice</h2>

    <div class="card shadow-sm">
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-hover align-middle">
                    <thead class="table-light">
                        <tr>
                            <th>No. Invoice</th>
                            <th>Tanggal Terbit</th>
                            <th>Jatuh Tempo</th>
                            <th class="text-end">Total Tagihan</th>
                            <th class="text-end">Sisa Tagihan</th>
                            <th class="text-center">Status</th>
                            <th class="text-center">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($invoices as $invoice)
                            <tr>
                                <td>{{ $invoice->invoice_number }}</td>
                                <td>{{ optional($invoice->order_date)->format('d M Y') }}</td>
                                <td class="{{ optional($invoice->due_date)->isPast() && $invoice->status != 'paid' ? 'text-danger fw-bold' : '' }}">
                                    {{ optional($invoice->due_date)->format('d M Y') }}
                                </td>
                                <td class="text-end">Rp {{ number_format($invoice->total_amount, 0, ',', '.') }}</td>
                                <td class="text-end fw-bold {{ ($invoice->total_amount - $invoice->amount_paid > 0) ? 'text-danger' : '' }}">
                                    Rp {{ number_format($invoice->total_amount - $invoice->amount_paid, 0, ',', '.') }}
                                </td>
                                <td class="text-center">
                                    @if($invoice->status == 'paid')
                                        <span class="badge bg-success">Lunas</span>
                                    @elseif($invoice->status == 'partially_paid')
                                        <span class="badge bg-info">Dibayar Sebagian</span>
                                    @else
                                        <span class="badge bg-warning text-dark">Belum Lunas</span>
                                    @endif
                                </td>
                                <td class="text-center">
                                    <a href="{{ route('client.invoices.show', $invoice->invoice_id) }}" class="btn btn-sm btn-outline-primary">
                                        <i class="bi bi-eye"></i> Detail
                                    </a>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="text-center py-4">Anda belum memiliki invoice.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            <div class="mt-3 d-flex justify-content-center">
                {{ $invoices->links() }}
            </div>
        </div>
    </div>
@endsection
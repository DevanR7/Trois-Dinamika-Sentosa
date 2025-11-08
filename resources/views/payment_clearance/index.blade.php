@extends('layouts.app')

@section('content')
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2 class="mb-0">Kliring Pembayaran (Giro / Cek)</h2>
    </div>

    <div class="card shadow-sm border-0">
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-hover align-middle">
                    <thead class="table-light">
                        <tr>
                            <th>Tanggal</th>
                            <th>Tipe</th>
                            <th>Relasi</th>
                            <th>No. Referensi</th>
                            <th>Metode</th>
                            <th>Setor ke Akun</th>
                            <th class="text-end">Jumlah</th>
                            <th class="text-center">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($pendingPayments as $payment)
                            @if ($payment instanceof \App\Models\Payment)
                                {{-- INI ADALAH PIUTANG (SALES PAYMENT) --}}
                                <tr>
                                    <td>{{ $payment->payment_date->format('d M Y') }}</td>
                                    <td>
                                        <span class="badge bg-success-subtle text-success-emphasis">
                                            Piutang
                                        </span>
                                    </td>
                                    <td>
                                        <a href="{{ route('clients.show', $payment->salesInvoice->client_id) }}">
                                            {{ $payment->salesInvoice->client->client_name }}
                                        </a>
                                    </td>
                                    <td>
                                        <a href="{{ route('invoices.show', $payment->invoice_id) }}">
                                            {{ $payment->salesInvoice->invoice_number }}
                                        </a>
                                    </td>
                                    <td>{{ $payment->paymentMethod->name ?? 'N/A' }}</td>
                                    <td>
                        @if($payment->companyBankAccount)
                            <strong>{{ $payment->companyBankAccount->bank_name }}</strong>
                            <small class="text-muted d-block">{{ $payment->companyBankAccount->account_number ?? $payment->companyBankAccount->account_name }}</small>
                        @else
                            <span class="text-danger">N/A</span>
                        @endif
                    </td>
                                    <td class="text-end">Rp {{ number_format($payment->amount, 0, ',', '.') }}</td>
                                    <td class="text-center">
                                        <div class="d-flex justify-content-center gap-2">
                                            <form action="{{ route('payment-clearance.sales.approve', $payment) }}" method="POST" class="d-inline" onsubmit="return confirm('Anda yakin ingin menyetujui pembayaran ini?')">
                                                @csrf
                                                <button type="submit" class="btn btn-success btn-sm" data-bs-toggle="tooltip" title="Setujui Kliring">
                                                    <i class="bi bi-check-lg"></i>
                                                </button>
                                            </form>
                                            <form action="{{ route('payment-clearance.sales.reject', $payment) }}" method="POST" class="d-inline" onsubmit="return confirm('Anda yakin ingin MENOLAK pembayaran ini?')">
                                                @csrf
                                                <button type="submit" class="btn btn-danger btn-sm" data-bs-toggle="tooltip" title="Tolak Kliring">
                                                    <i class="bi bi-x-lg"></i>
                                                </button>
                                            </form>
                                        </div>
                                    </td>
                                </tr>
                            @elseif ($payment instanceof \App\Models\PurchaseOrderPayment)
                                {{-- INI ADALAH HUTANG (PURCHASE PAYMENT) --}}
                                <tr>
                                    <td>{{ $payment->payment_date->format('d M Y') }}</td>
                                    <td>
                                        <span class="badge bg-danger-subtle text-danger-emphasis">
                                            Hutang
                                        </span>
                                    </td>
                                    <td>
                                        <a href="{{ route('suppliers.show', $payment->purchaseOrder->supplier_id) }}">
                                            {{ $payment->purchaseOrder->supplier->supplier_name }}
                                        </a>
                                    </td>
                                    <td>
                                        <a href="{{ route('purchase-orders.show', $payment->po_id) }}">
                                            {{ $payment->purchaseOrder->po_number }}
                                        </a>
                                    </td>
                                    <td>{{ $payment->paymentMethod->name ?? 'N/A' }}</td>
                                    <td>
                        @if($payment->companyBankAccount)
                            <strong>{{ $payment->companyBankAccount->bank_name }}</strong>
                            <small class="text-muted d-block">{{ $payment->companyBankAccount->account_number ?? $payment->companyBankAccount->account_name }}</small>
                        @else
                            <span class="text-danger">N/A</span>
                        @endif
                    </td>
                                    <td class="text-end">Rp {{ number_format($payment->amount, 0, ',', '.') }}</td>
                                    <td class="text-center">
                                        <div class="d-flex justify-content-center gap-2">
                                            <form action="{{ route('payment-clearance.purchase.approve', $payment) }}" method="POST" class="d-inline" onsubmit="return confirm('Anda yakin ingin menyetujui pembayaran ini?')">
                                                @csrf
                                                <button type="submit" class="btn btn-success btn-sm" data-bs-toggle="tooltip" title="Setujui Kliring">
                                                    <i class="bi bi-check-lg"></i>
                                                </button>
                                            </form>
                                            <form action="{{ route('payment-clearance.purchase.reject', $payment) }}" method="POST" class="d-inline" onsubmit="return confirm('Anda yakin ingin MENOLAK pembayaran ini?')">
                                                @csrf
                                                <button type="submit" class="btn btn-danger btn-sm" data-bs-toggle="tooltip" title="Tolak Kliring">
                                                    <i class="bi bi-x-lg"></i>
                                                </button>
                                            </form>
                                        </div>
                                    </td>
                                </tr>
                            @endif
                        @empty
                            <tr>
                               <td colspan="8" class="text-center py-4">
                                    Tidak ada pembayaran yang menunggu kliring.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
    // Aktifkan Bootstrap Tooltip
    var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'))
    var tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
      return new bootstrap.Tooltip(tooltipTriggerEl)
    })
</script>
@endpush
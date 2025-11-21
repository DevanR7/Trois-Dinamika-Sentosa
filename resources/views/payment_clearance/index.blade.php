@extends('layouts.app')

@section('content')
<div class="container-fluid py-4">
    {{-- HEADER --}}
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h3 class="fw-bold text-dark mb-0">Kliring Pembayaran</h3>
            <p class="text-muted small mb-0">Verifikasi Cek/Giro/Transfer Tertunda</p>
        </div>
    </div>

    {{-- NOTIFIKASI --}}
    @if (session('success'))
        <div class="alert alert-success border-0 shadow-sm d-flex align-items-center mb-4">
            <i class="bi bi-check-circle-fill fs-4 me-2"></i>
            <div>{{ session('success') }}</div>
        </div>
    @endif
    @if (session('error'))
        <div class="alert alert-danger border-0 shadow-sm d-flex align-items-center mb-4">
            <i class="bi bi-exclamation-triangle-fill fs-4 me-2"></i>
            <div>{{ session('error') }}</div>
        </div>
    @endif

    {{-- TABEL DATA --}}
    <div class="card card-transaction border-0 shadow-sm">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover table-transaction align-middle mb-0">
                    <thead class="bg-light">
                        <tr>
                            <th class="ps-4">Tanggal</th>
                            <th>Jenis Transaksi</th>
                            <th>Relasi (Klien/Supplier)</th>
                            <th>Referensi</th>
                            <th>Metode & Akun</th>
                            <th class="text-end">Jumlah</th>
                            <th class="text-center pe-4">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($pendingPayments as $payment)
                            <tr>
                                <td class="ps-4 text-muted">{{ $payment->payment_date->format('d M Y') }}</td>
                                
                                {{-- KOLOM JENIS TRANSAKSI --}}
                                <td>
                                    @if ($payment instanceof \App\Models\Payment)
                                        {{-- Pembayaran Penjualan (Piutang) --}}
                                        <span class="badge bg-success bg-opacity-10 text-success border border-success border-opacity-25 rounded-pill px-3">
                                            <i class="bi bi-arrow-down-left me-1"></i> Masuk (Piutang)
                                        </span>
                                    @elseif ($payment instanceof \App\Models\PurchaseOrderPayment)
                                        {{-- Pembayaran Pembelian (Hutang) --}}
                                        <span class="badge bg-danger bg-opacity-10 text-danger border border-danger border-opacity-25 rounded-pill px-3">
                                            <i class="bi bi-arrow-up-right me-1"></i> Keluar (Hutang)
                                        </span>
                                    @endif
                                </td>

                                {{-- KOLOM RELASI --}}
                                <td>
                                    @if ($payment instanceof \App\Models\Payment)
                                        <a href="{{ route('clients.show', $payment->salesInvoice->client_id) }}" class="fw-bold text-decoration-none text-dark">
                                            {{ $payment->salesInvoice->client->client_name }}
                                        </a>
                                        <small class="d-block text-muted">Inv: <a href="{{ route('invoices.show', $payment->invoice_id) }}">#{{ $payment->salesInvoice->invoice_number }}</a></small>
                                    @elseif ($payment instanceof \App\Models\PurchaseOrderPayment)
                                        <a href="{{ route('suppliers.show', $payment->purchaseOrder->supplier_id) }}" class="fw-bold text-decoration-none text-dark">
                                            {{ $payment->purchaseOrder->supplier->supplier_name }}
                                        </a>
                                        <small class="d-block text-muted">PO: <a href="{{ route('purchase-orders.show', $payment->po_id) }}">#{{ $payment->purchaseOrder->po_number }}</a></small>
                                    @endif
                                </td>

                                {{-- KOLOM REFERENSI --}}
                                <td>
                                    @if($payment->reference_number)
                                        <span class="text-dark fw-medium">{{ $payment->reference_number }}</span>
                                    @else
                                        <span class="text-muted fst-italic">-</span>
                                    @endif
                                </td>

                                {{-- KOLOM METODE & AKUN --}}
                                <td>
                                    <div class="d-flex flex-column">
                                        <span class="fw-medium text-primary small">{{ $payment->paymentMethod->name ?? 'N/A' }}</span>
                                        @if($payment->companyBankAccount)
                                            <span class="text-muted small" style="font-size: 0.75rem;">
                                                {{ $payment->companyBankAccount->bank_name }} - {{ $payment->companyBankAccount->account_number }}
                                            </span>
                                        @else
                                            <span class="text-danger small">Akun Tidak Valid</span>
                                        @endif
                                    </div>
                                </td>

                                {{-- KOLOM JUMLAH --}}
                                <td class="text-end fw-bold text-dark fs-6">
                                    Rp {{ number_format($payment->amount, 0, ',', '.') }}
                                </td>

                                {{-- KOLOM AKSI --}}
                                <td class="text-center pe-4">
                                    <div class="d-flex justify-content-center gap-2">
                                        @if ($payment instanceof \App\Models\Payment)
                                            {{-- Aksi untuk Sales Payment --}}
                                            <form action="{{ route('payment-clearance.sales.approve', $payment->payment_id) }}" method="POST" class="form-approve-sales">
                                                @csrf
                                                <button type="submit" class="btn btn-sm btn-success shadow-sm" title="Setujui Kliring">
                                                    <i class="bi bi-check-lg"></i>
                                                </button>
                                            </form>
                                            <form action="{{ route('payment-clearance.sales.reject', $payment->payment_id) }}" method="POST" class="form-reject-sales">
                                                @csrf
                                                <button type="submit" class="btn btn-sm btn-outline-danger shadow-sm" title="Tolak Kliring">
                                                    <i class="bi bi-x-lg"></i>
                                                </button>
                                            </form>

                                        @elseif ($payment instanceof \App\Models\PurchaseOrderPayment)
                                            {{-- Aksi untuk Purchase Payment --}}
                                            <form action="{{ route('payment-clearance.purchase.approve', $payment->payment_id) }}" method="POST" class="form-approve-purchase">
                                                @csrf
                                                <button type="submit" class="btn btn-sm btn-success shadow-sm" title="Setujui Kliring">
                                                    <i class="bi bi-check-lg"></i>
                                                </button>
                                            </form>
                                            <form action="{{ route('payment-clearance.purchase.reject', $payment->payment_id) }}" method="POST" class="form-reject-purchase">
                                                @csrf
                                                <button type="submit" class="btn btn-sm btn-outline-danger shadow-sm" title="Tolak Kliring">
                                                    <i class="bi bi-x-lg"></i>
                                                </button>
                                            </form>
                                        @endif
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="text-center py-5 text-muted">
                                    <i class="bi bi-check2-all fs-1 d-block mb-2 opacity-25"></i>
                                    Tidak ada pembayaran tertunda yang perlu dikliring.
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
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    
    // Fungsi reusable untuk konfirmasi
    function confirmAction(selector, title, text, btnColor, btnText) {
        document.querySelectorAll(selector).forEach(form => {
            form.addEventListener('submit', function(e) {
                e.preventDefault();
                Swal.fire({
                    title: title,
                    text: text,
                    icon: 'question',
                    showCancelButton: true,
                    confirmButtonColor: btnColor,
                    cancelButtonColor: '#6c757d',
                    confirmButtonText: btnText,
                    cancelButtonText: 'Batal'
                }).then((result) => {
                    if (result.isConfirmed) this.submit();
                });
            });
        });
    }

    // Bind ke Form Sales
    confirmAction('.form-approve-sales', 'Setujui Penerimaan?', 'Dana akan masuk ke kas/bank dan status menjadi Completed.', '#198754', 'Ya, Setujui!');
    confirmAction('.form-reject-sales', 'Tolak Penerimaan?', 'Pembayaran akan dibatalkan (Failed).', '#d33', 'Ya, Tolak!');

    // Bind ke Form Purchase
    confirmAction('.form-approve-purchase', 'Setujui Pengeluaran?', 'Dana akan keluar dari kas/bank dan status menjadi Completed.', '#198754', 'Ya, Setujui!');
    confirmAction('.form-reject-purchase', 'Tolak Pengeluaran?', 'Pembayaran akan dibatalkan (Failed).', '#d33', 'Ya, Tolak!');
});
</script>
@endpush
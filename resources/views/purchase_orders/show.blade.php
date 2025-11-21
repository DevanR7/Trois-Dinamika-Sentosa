@extends('layouts.app')

{{-- ==================================================================== --}}
{{-- ✅ BLOK PHP GLOBAL --}}
{{-- ==================================================================== --}}
@php
    // 1. Ringkasan Keuangan
    $sisaUtang = $purchaseOrder->remaining_balance;
    $adjustments = $purchaseOrder->adjustments;
    $totalCreditNotesPO = $adjustments->where('type', 'credit_note')->sum('amount');
    $totalDebitNotesPO = $adjustments->where('type', 'debit_note')->sum('amount');
    $totalReturDeposit = $purchaseOrder->returns
        ->where('return_handling_type', 'store_as_deposit')
        ->sum('total_amount');

    // 2. Modal Pembayaran
    $sisaTagihanPO = $sisaUtang; 
    $saldoDepositSupplier = $purchaseOrder->supplier->balance ?? 0;
@endphp

@section('content')
<div class="container-fluid py-2">
    
    {{-- HEADER HALAMAN --}}
    <div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
        <div>
            <h3 class="fw-bold text-dark mb-1">Detail Pesanan: {{ $purchaseOrder->po_number }}</h3>
            <p class="text-muted mb-0 small">
                Tanggal Transaksi: {{ optional($purchaseOrder->order_date)->format('d F Y') }}
            </p>
        </div>
        
        <div class="d-flex flex-wrap justify-content-end gap-2">
            <a href="{{ route('purchase-orders.index') }}" class="btn btn-outline-secondary btn-sm">
                <i class="bi bi-arrow-left me-1"></i> Kembali
            </a>

            {{-- TOMBOL TERIMA BARANG --}}
            @can('receive', $purchaseOrder)
                @if(in_array($purchaseOrder->status, ['draft', 'ordered']))
                    <form id="receive-goods-form" action="{{ route('purchase-orders.receive', $purchaseOrder->po_id) }}" method="POST" class="d-inline">
                        @csrf
                        <button type="submit" class="btn btn-primary btn-sm">
                            <i class="bi bi-box-seam me-1"></i> Terima Barang
                        </button>
                    </form>
                @endif
            @endcan

            {{-- TOMBOL CATAT PEMBAYARAN --}}
            @can('pay', $purchaseOrder)
                @if($sisaUtang > 0.01 && $purchaseOrder->payment_status != 'paid')
                    @php
                        $barangBelumDiterima = in_array($purchaseOrder->status, ['draft', 'ordered']);
                        $btnClass = $barangBelumDiterima ? 'btn-warning text-dark' : 'btn-success';
                        $btnText  = $barangBelumDiterima ? 'Catat DP (Uang Muka)' : 'Catat Pembayaran';
                    @endphp
                    
                    <button type="button" class="btn btn-sm {{ $btnClass }}" data-bs-toggle="modal" data-bs-target="#paymentModal" id="add-payment-btn">
                        <i class="bi bi-cash-coin me-1"></i> {{ $btnText }}
                    </button>
                @endif
            @endcan

            {{-- DROPDOWN OPSI --}}
            <div class="btn-group">
                <button type="button" class="btn btn-outline-secondary btn-sm dropdown-toggle" data-bs-toggle="dropdown" aria-expanded="false">
                    <i class="bi bi-gear"></i> Opsi
                </button>
                <ul class="dropdown-menu dropdown-menu-end">
                    @if (in_array($purchaseOrder->status, ['draft', 'ordered']))
                    <li>
                        <a class="dropdown-item" href="{{ route('purchase-orders.edit', $purchaseOrder->po_id) }}">
                            <i class="bi bi-pencil-square me-2"></i> Edit Pesanan
                        </a>
                    </li>
                    @endif
                    
                    <li><hr class="dropdown-divider"></li>

                    <li>
                        <a class="dropdown-item" href="{{ route('purchase-order-adjustments.create') }}?purchase_order_id={{ $purchaseOrder->po_id }}">
                            <i class="bi bi-file-earmark-diff me-2"></i> Buat Penyesuaian PO
                        </a>
                    </li>

                    <li>
                        <a class="dropdown-item" href="{{ route('purchase-orders.pdf', $purchaseOrder->po_id) }}">
                            <i class="bi bi-file-earmark-pdf me-2"></i> Download PDF
                        </a>
                    </li> 
                    
                    @can('cancel', $purchaseOrder)
                        @if(in_array($purchaseOrder->status, ['draft', 'ordered', 'completed'])) 
                            <li><hr class="dropdown-divider"></li>
                            <li>
                                <form action="{{ route('purchase-orders.cancel', $purchaseOrder->po_id) }}" method="POST" class="form-cancel-po">
                                    @csrf
                                    <button type="submit" class="dropdown-item text-danger">
                                        <i class="bi bi-x-circle me-2"></i> Batalkan Pesanan
                                    </button>
                                </form>
                            </li>
                        @endif
                    @endcan
                </ul>
            </div>
        </div>
    </div>

    <div class="row g-4">
        {{-- KOLOM KIRI --}}
        <div class="col-lg-8 col-xl-9">
            {{-- KARTU DETAIL UTAMA --}}
            <div class="card card-transaction border-0 shadow-sm mb-4">
                <div class="card-header bg-white d-flex justify-content-between align-items-center">
                    <div class="form-section-title mb-0"><i class="bi bi-info-circle"></i> Data Transaksi</div>
                    <div class="text-end">
                         {{-- Status Badges --}}
                         @if($purchaseOrder->status == 'completed') <span class="badge bg-success">Barang Diterima</span>
                         @elseif($purchaseOrder->status == 'cancelled') <span class="badge bg-danger">Dibatalkan</span>
                         @else <span class="badge bg-warning text-dark">{{ Str::title($purchaseOrder->status) }}</span>
                         @endif
 
                         @if($purchaseOrder->payment_status == 'paid') <span class="badge bg-primary">Lunas</span>
                         @elseif($purchaseOrder->payment_status == 'partially_paid') <span class="badge bg-info text-dark">Cicil</span>
                         @else <span class="badge bg-danger">Belum Lunas</span>
                         @endif
                    </div>
                </div>
                <div class="card-body p-4">
                    <div class="row mb-4">
                        <div class="col-md-6">
                            <h6 class="fw-bold text-secondary small text-uppercase">Supplier</h6>
                            <h5 class="fw-bold text-dark mb-1">{{ $purchaseOrder->supplier->supplier_name }}</h5>
                            <p class="text-muted small mb-0">{{ $purchaseOrder->supplier->address ?? 'Alamat tidak tersedia' }}</p>
                        </div>
                        <div class="col-md-6 text-md-end">
                            <h6 class="fw-bold text-secondary small text-uppercase">Informasi Lain</h6>
                            <p class="mb-1 small"><strong>No. Faktur Supplier:</strong> 
                                <a href="#" data-bs-toggle="modal" data-bs-target="#supplierInvoiceModal" class="text-decoration-none border-bottom border-primary border-opacity-25" title="Klik untuk input/edit">
                                    @if($purchaseOrder->supplier_invoice_number)
                                        <span class="fw-bold text-primary">{{ $purchaseOrder->supplier_invoice_number }}</span>
                                    @else
                                        <span class="text-muted fst-italic">-- Input Faktur --</span>
                                    @endif
                                </a>
                            </p>
                            @if($purchaseOrder->due_date)
                                <p class="mb-1 small"><strong>Jatuh Tempo:</strong> {{ optional($purchaseOrder->due_date)->format('d F Y') }}</p>
                            @endif
                            <p class="mb-0 small"><strong>Dibuat Oleh:</strong> {{ $purchaseOrder->creator->full_name ?? 'System' }}</p>
                        </div>
                    </div>
                    
                    {{-- Tabel Item --}}
                    <h5 class="fw-semibold mb-3" style="font-size: 0.9rem;">Rincian Item</h5>
                    <div class="table-responsive">
                        <table class="table table-transaction table-hover align-middle mb-0">
                            <thead>
                                <tr>
                                    <th class="text-center" style="width: 5%;">No</th>
                                    <th>Produk</th>
                                    <th class="text-center" style="width: 15%;">Qty</th>
                                    <th class="text-end" style="width: 20%;">Harga (@)</th>
                                    <th class="text-end" style="width: 20%;">Subtotal</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($purchaseOrder->items as $item)
                                <tr>
                                    <td class="text-center">{{ $loop->iteration }}</td>
                                    <td style="white-space: normal; word-wrap: break-word; max-width: 300px;">
                                        <div class="fw-bold text-dark">{{ $item->product->product_name ?? 'Produk Dihapus' }}</div>
                                        @if(isset($item->discounts) && $item->discounts->isNotEmpty())
                                        <small class="d-block text-muted" style="font-size: 0.75rem;">
                                            <i class="bi bi-tag"></i> Disc: {{ $item->discounts->pluck('percentage')->join('%, ') }}%
                                        </small>
                                        @endif
                                    </td>
                                    <td class="text-center">
                                        <span class="badge bg-light text-dark border">{{ $item->quantity }} {{ $item->product->unit->name ?? '' }}</span>
                                    </td>
                                    <td class="text-end text-muted small">
                                        Rp {{ number_format($item->price_per_unit, 0, ',', '.') }}
                                    </td>
                                    <td class="text-end fw-semibold">Rp {{ number_format($item->subtotal, 0, ',', '.') }}</td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
            
            {{-- TABEL KOREKSI (ADJUSTMENT) --}}
            @if($purchaseOrder->adjustments->isNotEmpty())
            <div class="card card-transaction border-0 shadow-sm mb-4">
                <div class="card-header bg-white">
                    <div class="form-section-title mb-0"><i class="bi bi-exclamation-circle"></i> Riwayat Koreksi (Adjustment)</div>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-transaction align-middle mb-0">
                            <thead>
                                <tr>
                                    <th>Tanggal</th>
                                    <th>Tipe</th>
                                    <th class="text-end">Nilai</th>
                                    <th>Alasan</th>
                                    <th>User</th>
                                    <th></th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($purchaseOrder->adjustments as $adjustment)
                                <tr>
                                    <td>{{ $adjustment->adjustment_date->format('d/m/Y') }}</td>
                                    <td>
                                        @if($adjustment->type == 'credit_note')
                                            <span class="badge bg-success bg-opacity-10 text-success">Nota Kredit</span>
                                        @else
                                            <span class="badge bg-danger bg-opacity-10 text-danger">Nota Debit</span>
                                        @endif
                                    </td>
                                    <td class="text-end fw-bold">Rp {{ number_format($adjustment->amount, 0, ',', '.') }}</td>
                                    <td class="small text-muted" style="white-space: normal; max-width: 250px;">{{ $adjustment->reason }}</td>
                                    <td class="small">{{ $adjustment->user->full_name ?? '-' }}</td>
                                    <td class="text-end">
                                        <form action="{{ route('purchase-order-adjustments.destroy', $adjustment->adjustment_id) }}" method="POST" class="form-cancel-po-adjustment d-inline">
                                            @csrf @method('DELETE')
                                            <button type="submit" class="btn btn-link text-danger p-0" title="Hapus">
                                                <i class="bi bi-trash"></i>
                                            </button>
                                        </form>
                                    </td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
            @endif

            {{-- TABEL PEMBAYARAN --}}
            @if($purchaseOrder->payments->isNotEmpty())
            <div class="card card-transaction border-0 shadow-sm mb-4">
                <div class="card-header bg-white">
                    <div class="form-section-title mb-0"><i class="bi bi-wallet2"></i> Riwayat Pembayaran</div>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-transaction align-middle mb-0">
                            <thead>
                                <tr>
                                    <th>Tanggal</th>
                                    <th>Metode</th>
                                    <th class="text-end">Jumlah</th>
                                    <th>Ref</th>
                                    <th>Status</th>
                                    <th></th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($purchaseOrder->payments as $payment)
                                <tr>
                                    <td>{{ $payment->payment_date->format('d/m/Y') }}</td>
                                    <td>{{ $payment->paymentMethod->name ?? '-' }}</td>
                                    <td class="text-end fw-bold text-success">Rp {{ number_format($payment->amount, 0, ',', '.') }}</td>
                                    <td class="small text-muted">{{ $payment->reference_number ?? '-' }}</td>
                                    <td>
                                        @if($payment->status == 'completed') <i class="bi bi-check-circle-fill text-success"></i>
                                        @elseif($payment->status == 'pending_clearance') <i class="bi bi-clock-fill text-warning"></i>
                                        @else <span class="badge bg-secondary">{{ $payment->status }}</span>
                                        @endif
                                    </td>
                                    <td class="text-end">
                                        @php $paymentLabel = 'Pembayaran Rp ' . number_format($payment->amount, 0, ',', '.'); @endphp
                                        <form action="{{ route('purchase-orders.payments.destroy', $payment) }}" method="POST" class="d-inline form-delete-po-payment" data-payment-label="{{ $paymentLabel }}">
                                            @csrf @method('DELETE')
                                            <button type="submit" class="btn btn-link text-danger p-0">
                                                <i class="bi bi-trash"></i>
                                            </button>
                                        </form>
                                    </td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
            @endif
            
            @if($purchaseOrder->notes)
            <div class="p-3 bg-light rounded border border-light">
                <h6 class="fw-semibold text-secondary small"><i class="bi bi-sticky"></i> Catatan:</h6>
                <p class="text-muted mb-0 fst-italic">{{ $purchaseOrder->notes }}</p>
            </div>
            @endif
        </div>

        {{-- KOLOM KANAN (SUMMARY) --}}
        <div class="col-lg-4 col-xl-3">
            <div class="card card-transaction border-0 shadow-sm sticky-top" style="top: 20px; z-index: 99;">
                <div class="card-header bg-white">
                    <div class="form-section-title mb-0"><i class="bi bi-calculator"></i> Ringkasan Biaya</div>
                </div>
                <div class="card-body p-4">
                    {{-- RINCIAN PERHITUNGAN --}}
                    <div class="mb-4">
                        <div class="d-flex justify-content-between mb-2 small"><span>Subtotal Barang</span><span class="fw-medium">Rp {{ number_format($purchaseOrder->subtotal ?? 0, 0, ',', '.') }}</span></div>
                        <div class="d-flex justify-content-between mb-2 small"><span>Diskon/Fee</span><span class="text-danger">(-) Rp {{ number_format($purchaseOrder->disc_fee_amount ?? 0, 0, ',', '.') }}</span></div>
                        <div class="d-flex justify-content-between mb-2 small"><span>Pembulatan</span><span class="text-danger">(-) Rp {{ number_format($purchaseOrder->rounding_discount_amount ?? 0, 0, ',', '.') }}</span></div>
                        
                        <div class="border-top border-dashed my-2"></div>
                        
                        <div class="d-flex justify-content-between mb-2 small text-muted"><span>DPP</span><span>Rp {{ number_format($purchaseOrder->dpp ?? 0, 0, ',', '.') }}</span></div>
                        <div class="d-flex justify-content-between mb-2 small"><span>PPN ({{ $purchaseOrder->tax->rate ?? 0 }}%)</span><span>(+) Rp {{ number_format($purchaseOrder->ppn ?? 0, 0, ',', '.') }}</span></div>
                        <div class="d-flex justify-content-between mb-2 small"><span>Ongkir</span><span>(+) Rp {{ number_format($purchaseOrder->shipping_amount ?? 0, 0, ',', '.') }}</span></div>
                        
                        <div class="border-top border-dashed my-2"></div>
                        
                        <div class="d-flex justify-content-between fw-bold text-dark"><span>GRAND TOTAL</span><span>Rp {{ number_format($purchaseOrder->total_amount ?? 0, 0, ',', '.') }}</span></div>
                    </div>

                    {{-- RINGKASAN KEUANGAN (UTANG/LUNAS) --}}
                    <div class="bg-light p-3 rounded border">
                        <h6 class="fw-bold small text-uppercase text-secondary mb-3">Status Keuangan</h6>
                        
                        @if($totalDebitNotesPO > 0)
                            <div class="d-flex justify-content-between text-danger small mb-1"><span>Nota Debit</span><span>(+) {{ number_format($totalDebitNotesPO, 0, ',', '.') }}</span></div>
                        @endif
                        @if($totalCreditNotesPO > 0)
                            <div class="d-flex justify-content-between text-success small mb-1"><span>Nota Kredit</span><span>(-) {{ number_format($totalCreditNotesPO, 0, ',', '.') }}</span></div>
                        @endif
                        @if($purchaseOrder->total_returned > 0)
                            <div class="d-flex justify-content-between text-warning small mb-1"><span>Retur</span><span>(-) {{ number_format($purchaseOrder->total_returned, 0, ',', '.') }}</span></div>
                        @endif

                        <div class="d-flex justify-content-between text-success small mb-2"><span>Sudah Dibayar</span><span>(-) {{ number_format($purchaseOrder->amount_paid, 0, ',', '.') }}</span></div>
                        
                        <div class="border-top border-secondary border-opacity-25 my-2"></div>
                        
                        <div class="d-flex justify-content-between fw-bold {{ $sisaUtang > 0.01 ? 'text-danger' : 'text-success' }}">
                            <span>SISA TAGIHAN</span>
                            <span>Rp {{ number_format($sisaUtang, 0, ',', '.') }}</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

{{-- MODAL PEMBAYARAN --}}
<div class="modal fade" id="paymentModal" tabindex="-1" aria-labelledby="paymentModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="paymentModalLabel">Catat Pembayaran Baru</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form action="{{ route('purchase-orders.payments.store', $purchaseOrder->po_id) }}" method="POST" enctype="multipart/form-data">
                @csrf
                
                <div class="modal-body">
                    
                    <div class="alert alert-info">
                        <div class="d-flex justify-content-between"><span>Total Tagihan Awal:</span><span>Rp {{ number_format($purchaseOrder->total_amount, 0, ',', '.') }}</span></div>
                        <hr class="my-1">
                        <div class="d-flex justify-content-between small"><span>Sudah Dibayar:</span><span>(+) Rp {{ number_format($purchaseOrder->amount_paid, 0, ',', '.') }}</span></div>
                        <hr class="my-1">
                        <div class="d-flex justify-content-between fw-bold">
                            <span>Sisa Utang:</span>
                            <span id="modal-po-sisa-tagihan-display">Rp {{ number_format($sisaTagihanPO, 0, ',', '.') }}</span>
                        </div>
                    </div>

                    @if($saldoDepositSupplier > 0)
                    <div id="debit-info-container" class="alert alert-success">
                        <div class="d-flex justify-content-between fw-bold">
                            <span>Saldo Deposit Tersedia:</span>
                            <span id="modal-debit-balance-display">Rp {{ number_format($saldoDepositSupplier, 0, ',', '.') }}</span>
                        </div>
                        <div class="form-check form-switch mt-2">
                            <input class="form-check-input" type="checkbox" role="switch" id="modal-use-debit" name="use_debit_balance" value="1">
                            <label class="form-check-label" for="modal-use-debit">Gunakan Saldo Deposit</label>
                        </div>
                    </div>
                    @endif
                    
                    <div class="mb-3">
                        <label for="amount-formatted-po" class="form-label">Jumlah Dibayar (Non-Deposit)</label>
                        <input type="text" class="form-control" id="amount-formatted-po" required>
                        <input type="hidden" name="amount" id="amount-po">
                        <div id="amount-error-po" class="text-danger small mt-1"></div>
                    </div>
                    <div class="mb-3">
                        <label for="payment_date" class="form-label">Tanggal Pembayaran</label>
                        <input type="date" class="form-control" id="payment_date" name="payment_date" value="{{ now()->format('Y-m-d') }}" required>
                    </div>
                    
                    <div class="mb-3">
                        <label for="payment_method_id_po" class="form-label">Metode Pembayaran (Non-Deposit)</label>
                        <select class="form-select" id="payment_method_id_po" name="payment_method_id" required>
                            <option value="">-- Pilih Metode --</option>
                            @foreach($paymentMethods as $method)
                                <option value="{{ $method->payment_method_id }}" data-config="{{ $method->required_fields_config }}">
                                    {{ $method->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="mb-3">
                        <label for="company_bank_account_id_po" class="form-label">Keluar dari Akun <span class="text-danger">*</span></label>
                        <select class="form-select" id="company_bank_account_id_po" name="company_bank_account_id" required>
                            <option value="">-- Pilih Akun Bank/Kas --</option>
                            @foreach($companyBankAccounts as $account)
                                <option value="{{ $account->company_bank_account_id }}">
                                    {{ $account->bank_name }} - {{ $account->account_number ?? $account->account_name }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="mb-3" id="payment-reference-group-po" style="display: none;">
                        <label for="reference_number_po" class="form-label">Nomor Referensi (Giro/Cek)</label>
                        <input type="text" class="form-control" name="reference_number" id="reference_number_po">
                    </div>
                    <div class="mb-3" id="payment-proof-group-po" style="display: none;">
                        <label for="proof_of_payment_po" class="form-label">Bukti Pembayaran (Foto)</label>
                        <input type="file" class="form-control" name="proof_of_payment" id="proof_of_payment_po" accept="image/jpeg,image/png,image/jpg">
                    </div>
                    
                    <div class="mb-3">
                        <label for="notes" class="form-label">Catatan (Opsional)</label>
                        <textarea class="form-control" id="notes" name="notes" rows="2"></textarea>
                    </div>
                </div>

                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-primary">Simpan Pembayaran</button>
                </div>
            </form>
        </div>
    </div>
</div>

{{-- MODAL FAKTUR --}}
<div class="modal fade" id="supplierInvoiceModal" tabindex="-1" aria-labelledby="supplierInvoiceModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="supplierInvoiceModalLabel">Input Nomor Faktur Supplier</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form action="{{ route('purchase-orders.addSupplierInvoice', $purchaseOrder->po_id) }}" method="POST">
                @csrf
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="supplier_invoice_number" class="form-label">No. Faktur dari Supplier</label>
                        <input type="text" class="form-control" id="supplier_invoice_number" name="supplier_invoice_number" value="{{ $purchaseOrder->supplier_invoice_number }}" required>
                        <div class="form-text">Masukkan nomor yang tertera di surat jalan atau faktur dari supplier.</div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-primary">Simpan</button>
                </div>
            </form>
        </div>
    </div>
</div>

@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/autonumeric@4.6.0/dist/autoNumeric.min.js"></script>
<script>
    document.addEventListener('DOMContentLoaded', function() {
        
        const receiveGoodsForm = document.getElementById('receive-goods-form');
        if (receiveGoodsForm) {
            receiveGoodsForm.addEventListener('submit', function(event) {
                event.preventDefault(); 
                Swal.fire({
                    title: 'Konfirmasi Penerimaan',
                    text: "Apakah Anda yakin semua barang untuk pesanan ini telah diterima? Stok akan diperbarui dan jurnal akan dibuat.",
                    icon: 'question',
                    showCancelButton: true,
                    confirmButtonColor: '#198754',
                    cancelButtonColor: '#6c757d',
                    confirmButtonText: 'Ya, Sudah Diterima!',
                    cancelButtonText: 'Batal'
                }).then((result) => {
                    if (result.isConfirmed) {
                        event.target.submit();
                    }
                });
            });
        }

        const cancelPOForm = document.querySelector('.form-cancel-po');
        if (cancelPOForm) {
            cancelPOForm.addEventListener('submit', function (event) {
                event.preventDefault(); 
                Swal.fire({
                    title: 'Anda Yakin?',
                    text: "Anda akan membatalkan Pesanan Pembelian (PO) ini. Jurnal akan dibalik jika barang sudah diterima.",
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonColor: '#d33',
                    cancelButtonColor: '#6c757d',
                    confirmButtonText: 'Ya, Batalkan!',
                    cancelButtonText: 'Batal'
                }).then((result) => {
                    if (result.isConfirmed) {
                        event.target.submit();
                    }
                });
            });
        }

        const deletePOPaymentForms = document.querySelectorAll('.form-delete-po-payment');
        deletePOPaymentForms.forEach(form => {
            form.addEventListener('submit', function (event) {
                event.preventDefault();
                const paymentLabel = event.target.dataset.paymentLabel;
                Swal.fire({
                    title: 'Anda Yakin?',
                    text: `Anda akan membatalkan pembayaran ini: "${paymentLabel}". Jurnal akan dibalik dan sisa utang dihitung ulang.`,
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonColor: '#d33',
                    cancelButtonColor: '#6c757d',
                    confirmButtonText: 'Ya, Batalkan Pembayaran!',
                    cancelButtonText: 'Batal'
                }).then((result) => {
                    if (result.isConfirmed) {
                        event.target.submit();
                    }
                });
            });
        });

        const addPaymentBtnPO = document.getElementById('add-payment-btn');
        const amountFormattedInputPO = document.getElementById('amount-formatted-po');
        const amountHiddenInputPO = document.getElementById('amount-po');
        const amountErrorPO = document.getElementById('amount-error-po');
        const useDebitCheckboxPO = document.getElementById('modal-use-debit');
        
        const paymentMethodSelectPO = document.getElementById('payment_method_id_po');
        const bankAccountSelectPO = document.getElementById('company_bank_account_id_po');
        const referenceGroupPO = document.getElementById('payment-reference-group-po');
        const referenceInputPO = document.getElementById('reference_number_po');
        const proofGroupPO = document.getElementById('payment-proof-group-po');
        const proofInputPO = document.getElementById('proof_of_payment_po');

        const remainingBalancePO = {{ $sisaTagihanPO ?? 0 }};
        const currentDebitBalancePO = {{ $saldoDepositSupplier ?? 0 }};
        
        const defaultPaymentMethodIdPO = "{{ $paymentMethods->first()->payment_method_id ?? '' }}";
        const defaultBankAccountIdPO = "{{ $companyBankAccounts->first()->company_bank_account_id ?? '' }}";

        function handlePaymentMethodChangePO() {
            if (!paymentMethodSelectPO) return;
            
            const selectedOption = paymentMethodSelectPO.options[paymentMethodSelectPO.selectedIndex];
            const config = (selectedOption && !paymentMethodSelectPO.disabled) ? selectedOption.dataset.config : 'none';

            referenceGroupPO.style.display = 'none';
            referenceInputPO.required = false;
            proofGroupPO.style.display = 'none';
            proofInputPO.required = false;

            if (config === 'proof_only') {
                proofGroupPO.style.display = 'block';
                proofInputPO.required = true;
            } else if (config === 'reference_only') {
                referenceGroupPO.style.display = 'block';
                referenceInputPO.required = true;
            } else if (config === 'proof_and_reference') {
                proofGroupPO.style.display = 'block';
                proofInputPO.required = true;
                referenceGroupPO.style.display = 'block';
                referenceInputPO.required = true;
            }
        }

        if (paymentMethodSelectPO) {
            paymentMethodSelectPO.addEventListener('change', handlePaymentMethodChangePO);
        }
        
        if (amountFormattedInputPO) {
            const autoNumericInstancePO = new AutoNumeric(amountFormattedInputPO, {
                decimalCharacter: ',',
                digitGroupSeparator: '.',
                decimalPlaces: 0,
                minimumValue: '0'
            });

            function toggleRequiredFieldsPO() {
                const useDebit = useDebitCheckboxPO ? useDebitCheckboxPO.checked : false;
                const debitIsSufficient = currentDebitBalancePO >= remainingBalancePO && remainingBalancePO > 0;
                const inputAmountValue = parseFloat(amountHiddenInputPO.value || 0);

                if (useDebit) {
                    if (debitIsSufficient) {
                        autoNumericInstancePO.set(0);
                        amountFormattedInputPO.disabled = true;
                        amountFormattedInputPO.required = false;
                        
                        paymentMethodSelectPO.disabled = true;
                        paymentMethodSelectPO.required = false;
                        paymentMethodSelectPO.value = "";

                        bankAccountSelectPO.disabled = true;
                        bankAccountSelectPO.required = false;
                        bankAccountSelectPO.value = "";
                    } else {
                        const shortfall = remainingBalancePO - currentDebitBalancePO;
                        autoNumericInstancePO.set(shortfall);
                        amountFormattedInputPO.disabled = false;
                        amountFormattedInputPO.required = true;
                        
                        paymentMethodSelectPO.disabled = false;
                        paymentMethodSelectPO.required = true;
                        if (!paymentMethodSelectPO.value) paymentMethodSelectPO.value = defaultPaymentMethodIdPO;
                        
                        bankAccountSelectPO.disabled = false;
                        bankAccountSelectPO.required = true;
                        if (!bankAccountSelectPO.value) bankAccountSelectPO.value = defaultBankAccountIdPO;
                    }  
                } else {
                    autoNumericInstancePO.set(remainingBalancePO);
                    amountFormattedInputPO.disabled = false;
                    amountFormattedInputPO.required = true;

                    const isAmountPositive = inputAmountValue > 0 || remainingBalancePO > 0;

                    paymentMethodSelectPO.disabled = false;
                    paymentMethodSelectPO.required = isAmountPositive;
                    bankAccountSelectPO.disabled = false;
                    bankAccountSelectPO.required = isAmountPositive;
                    
                    if (isAmountPositive) {
                        if (!paymentMethodSelectPO.value) paymentMethodSelectPO.value = defaultPaymentMethodIdPO;
                        if (!bankAccountSelectPO.value) bankAccountSelectPO.value = defaultBankAccountIdPO;
                    }
                }
                
                handlePaymentMethodChangePO();
            }

            if (addPaymentBtnPO) {
                addPaymentBtnPO.addEventListener('click', function() {
                    if (useDebitCheckboxPO) {
                        useDebitCheckboxPO.checked = true; 
                    }
                    toggleRequiredFieldsPO();
                    amountErrorPO.textContent = '';
                });
            }

            if (useDebitCheckboxPO) {
                useDebitCheckboxPO.addEventListener('change', toggleRequiredFieldsPO);
            }

            amountFormattedInputPO.addEventListener('autoNumeric:rawValueModified', function(event) {
                const rawValue = event.detail.newRawValue;
                amountHiddenInputPO.value = rawValue;

                const isAmountPositive = parseFloat(rawValue || 0) > 0;

                if (useDebitCheckboxPO && !useDebitCheckboxPO.checked) {
                    paymentMethodSelectPO.required = isAmountPositive;
                    bankAccountSelectPO.required = isAmountPositive;
                }

                const totalPayment = (useDebitCheckboxPO && useDebitCheckboxPO.checked ? currentDebitBalancePO : 0) + parseFloat(rawValue || 0);
                if (totalPayment > remainingBalancePO) {
                    amountErrorPO.textContent = 'Info: Kelebihan bayar akan jadi saldo deposit.';
                    amountErrorPO.classList.remove('text-danger');
                    amountErrorPO.classList.add('text-success');
                } else {
                    amountErrorPO.textContent = '';
                }
            });
        }
    });
</script>
@endpush
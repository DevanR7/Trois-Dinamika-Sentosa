@extends('layouts.client')

@php
    $client = Auth::guard('client')->user();
    $allPayments = $invoice->payments; // Ambil 1x untuk efisiensi

    // 1. Untuk Ringkasan & Pembayaran
    $sisaTagihan = $invoice->remaining_balance;
    $totalReturDipotong = $invoice->total_deducting_returns;
    $totalReturKredit = $invoice->returns
        ->where('return_handling_type', 'store_as_credit')
        ->sum('total_amount');
    
    // 2. Untuk Saldo
    $saldoKreditKlien = $client->balance; // Saldo available
    $saldoKreditPending = $client->pending_balance; // Saldo pending

    // 3. Untuk Logika Tombol Bayar & Verifikasi
    $pendingPayments = $allPayments->where('status', 'pending_verification');
    $pendingPaymentAmount = $pendingPayments->sum('amount');
    $canPay = ($sisaTagihan - $pendingPaymentAmount) > 0.01;

    // 4. Untuk Modal Midtrans
    $amountToPayMidtrans = max(0, $sisaTagihan - $saldoKreditKlien);
    
    // 5. ✅ TAMBAHKAN 2 BARIS INI
    // Ini mengasumsikan $paymentMethods sudah dikirim dari Client/InvoiceController@show
    $transferMethodId = $paymentMethods->firstWhere(fn($m) => str_contains(strtolower($m->name), 'transfer'))->payment_method_id ?? null;
    $cashMethodId = $paymentMethods->firstWhere(fn($m) => str_contains(strtolower($m->name), 'cash'))->payment_method_id ?? null;
@endphp

@section('content')
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
        <a href="{{ route('client.invoices.index') }}" class="btn btn-outline-secondary">
            <i class="bi bi-arrow-left"></i> Kembali ke Daftar
        </a>

        <div class="d-flex flex-wrap gap-2">
            @if(in_array($invoice->status, ['unpaid', 'partially_paid']) && $canPay)
                <button type="button" class="btn btn-primary fw-bold" data-bs-toggle="modal" data-bs-target="#paymentMethodModal">
                    <i class="bi bi-credit-card-fill me-2"></i> Bayar Tagihan
                </button>
            @endif
        </div>
    </div>

    <div class="card shadow-sm border-0">
        <div class="card-body p-5">
            <div class="row mb-4">
                <div class="col-md-6">
                    <h2 class="fw-bold mb-1">INVOICE</h2>
                    <p class="text-muted">#{{ $invoice->invoice_number }}</p>
                </div>
                <div class="col-md-6 text-md-end">
                    <p class="mb-1"><strong>Tanggal Pesanan:</strong> {{ optional($invoice->order_date)->format('d F Y') }}</p>
                    @if($invoice->due_date)<p class="mb-1"><strong>Jatuh Tempo:</strong> {{ optional($invoice->due_date)->format('d F Y') }}</p>@endif
                    @if($invoice->sales)<p class="mb-1"><strong>Sales:</strong> {{ $invoice->sales->full_name }} ({{ $invoice->sales->sales_code }})</p>@endif
                    <p class="mb-1">
                        <strong>Status:</strong>
                        @if($invoice->status == 'paid')
                            <span class="badge bg-success fs-6">Lunas</span>
                        @elseif($invoice->status == 'partially_paid')
                            <span class="badge bg-info text-dark fs-6">Cicil</span>
                        @elseif($invoice->status == 'cancelled')
                            <span class="badge bg-danger fs-6">Dibatalkan</span>
                        @else
                            <span class="badge bg-warning text-dark fs-6">Belum Lunas</span>
                        @endif
                    </p>
                </div>
            </div>
            <hr>
            
            <h5 class="fw-semibold mt-4">Rincian Invoice</h5>
            <div class="table-responsive">
                <table class="table table-bordered align-middle">
                    <thead class="table-light">
                        <tr>
                            <th>No</th>
                            <th>Produk</th>
                            <th class="text-center">Kuantitas</th>
                            <th class="text-end">Harga Satuan</th>
                            <th class="text-end">Subtotal</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($invoice->items as $item)
                        <tr>
                            <td>{{ $loop->iteration }}</td>
                            <td>{{ $item->product->product_name ?? 'Produk Dihapus' }}</td>
                            <td class="text-center">{{ $item->quantity }}</td>
                            <td class="text-end">Rp {{ number_format($item->price_per_unit, 0, ',', '.') }}</td>
                            <td class="text-end fw-semibold">Rp {{ number_format($item->subtotal, 0, ',', '.') }}</td>
                        </tr>
                        @empty
                        <tr><td colspan="5" class="text-center text-muted">Tidak ada item dalam invoice ini.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            @if($pendingPayments->isNotEmpty())
            <div class="card my-4 border-warning">
                <div class="card-header bg-warning text-dark">
                    <h5 class="mb-0 fw-bold"><i class="bi bi-exclamation-triangle-fill me-2"></i>Menunggu Verifikasi Pembayaran</h5>
                </div>
                <div class="card-body">
                    @foreach($pendingPayments as $payment)
                    <div class="d-flex justify-content-between align-items-center mb-3 border-bottom pb-3">
                        <div>
                            <div><strong>Jumlah:</strong> Rp {{ number_format($payment->amount, 0, ',', '.') }}</div>
                            <div><strong>Tanggal Lapor:</strong> {{ $payment->created_at->format('d M Y H:i') }}</div>
                            @if($payment->notes)
                                <div class="text-muted small mt-1"><strong>Catatan Klien:</strong> {{ $payment->notes }}</div>
                            @endif
                        </div>
                        <div class="d-flex gap-3 align-items-center">
                            <div class="text-center">
                                @if($payment->paymentMethod && str_contains(strtolower($payment->paymentMethod->name), 'cash') && $payment->receivedBy)
                                    <button type="button" class="btn btn-sm btn-outline-secondary" 
                                            data-bs-toggle="popover" 
                                            data-bs-title="Diterima Oleh" 
                                            data-bs-content="{{ $payment->receivedBy->full_name }}">
                                        Cash
                                    </button>
                                @elseif($payment->proof_of_payment_path)
                                    <a href="{{ asset('storage/' . $payment->proof_of_payment_path) }}" target="_blank" class="btn btn-sm btn-outline-info">Lihat Bukti</a>
                                @else
                                    <span class="text-muted">-</span>
                                @endif
                            </div>
                        </div>
                    </div>
                    @endforeach
                </div>
            </div>
            @endif

            @if($invoice->adjustments->isNotEmpty())
            <h5 class="fw-semibold mt-4">Riwayat Penyesuaian (Koreksi)</h5>
            <div class="table-responsive">
                <table class="table table-sm table-bordered table-warning">
                    <thead class="table-light">
                        <tr>
                            <th>Tanggal</th>
                            <th>Tipe</th>
                            <th class="text-end">Nilai</th>
                            <th>Alasan</th>
                            <th>Dibuat Oleh</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($invoice->adjustments as $adjustment)
                        <tr>
                            <td>{{ $adjustment->adjustment_date->format('d M Y') }}</td>
                            <td>
                                @if($adjustment->type == 'credit_note')
                                    <span class="badge bg-success">Nota Kredit</span>
                                @else
                                    <span class="badge bg-danger">Nota Debit</span>
                                @endif
                            </td>
                            <td class="text-end fw-bold">
                                Rp {{ number_format($adjustment->amount, 0, ',', '.') }}
                            </td>
                            <td>{{ $adjustment->reason }}</td>
                            <td>{{ $adjustment->user->full_name ?? 'N/A' }}</td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            @endif

            @if($invoice->returns->isNotEmpty())
            <h5 class="fw-semibold mt-4">Riwayat Retur</h5>
            <div class="table-responsive">
                <table class="table table-sm table-bordered table-info">
                    <thead class="table-light">
                        <tr>
                            <th>Tgl. Retur</th>
                            <th>No. Retur</th>
                            <th>Item yang Diretur</th>
                            <th class="text-end">Nilai Retur</th>
                            <th>Status/Tindakan</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($invoice->returns as $return)
                        <tr>
                            <td>{{ $return->return_date->format('d M Y') }}</td>
                            <td>{{ $return->return_number }}</td>
                            <td>
                                <ul class="list-unstyled mb-0 small">
                                @foreach($return->items as $item)
                                    <li>- {{ $item->product->product_name ?? 'N/A' }} ({{ $item->quantity }} pcs)</li>
                                @endforeach
                                </ul>
                            </td>
                            <td class="text-end fw-bold">
                                Rp {{ number_format($return->total_amount, 0, ',', '.') }}
                            </td>
                            <td>
                                @if($return->return_handling_type == 'store_as_credit')
                                    <span class="badge bg-success">Disimpan sebagai Kredit</span>
                                @else
                                    <span class="badge bg-warning text-dark">Memotong Tagihan</span>
                                @endif
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            @endif

            @if($allPayments->isNotEmpty())
            <h5 class="fw-semibold mt-4">Riwayat Pembayaran</h5>
            <div class="table-responsive">
                <table class="table table-sm table-bordered">
                    <thead class="table-light">
                        <tr>
                            <th>Tanggal Bayar</th>
                            <th class="text-end">Jumlah</th>
                            <th>Metode</th>
                            <th>Status</th>
                            <th>Catatan / Ref.</th>
                            <th>Penerima / Bukti</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($allPayments as $payment)
                        <tr>
                            <td>{{ $payment->payment_date->format('d M Y') }}</td>
                            <td class="text-end">Rp {{ number_format($payment->amount, 0, ',', '.') }}</td>
                            <td>
                                {{ $payment->paymentMethod->name ?? ($payment->transaction_id ? 'Gateway / Kredit' : 'Kredit Klien') }}
                            </td>
                            <td>
                                @if($payment->status == 'completed')
                                    <span class="badge bg-success">Completed</span>
                                @elseif($payment->status == 'pending_verification')
                                    <span class="badge bg-warning text-dark">Pending Verifikasi</span>
                                @elseif($payment->status == 'pending_clearance')
                                    <span class="badge bg-info text-dark">Pending Kliring</span>
                                @elseif($payment->status == 'failed')
                                    <span class="badge bg-danger">Failed</span>
                                @else
                                    <span class="badge bg-secondary">{{ $payment->status }}</span>
                                @endif
                            </td>
                            <td>
                                {{ $payment->notes }}
                                @if($payment->reference_number)
                                    <strong class="d-block">Ref: {{ $payment->reference_number }}</strong>
                                @endif
                            </td>
                             <td>
                                @if($payment->paymentMethod && str_contains(strtolower($payment->paymentMethod->name), 'cash') && $payment->receivedBy)
                                    {{ $payment->receivedBy->full_name }} (Sales)
                                @elseif($payment->proof_of_payment_path)
                                    <a href="{{ asset('storage/' . $payment->proof_of_payment_path) }}" target="_blank" class="btn btn-sm btn-outline-info">
                                        Lihat Bukti
                                    </a>
                                @elseif($payment->transaction_id)
                                    <span class="text-muted" data-bs-toggle="tooltip" title="TX ID: {{ $payment->transaction_id }}">
                                        Gateway / Kredit
                                    </span>
                                @else
                                    -
                                @endif
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="6" class="text-center text-muted">Belum ada riwayat pembayaran.</td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            @endif

            <div class="row mt-4">
                <div class="col-md-7">
                    @if($invoice->notes)
                        <h6 class="fw-semibold">Catatan:</h6>
                        <p class="text-muted fst-italic bg-light p-3 rounded">{{ $invoice->notes }}</p>
                    @endif
                </div>
                <div class="col-md-5">
                    <h5 class="fw-semibold mb-3">Ringkasan Keuangan</h5>
                    <div class="border rounded p-3">
                        <div class="d-flex justify-content-between fw-bold"><span>Total Tagihan Awal</span><span>Rp {{ number_format($invoice->total_amount, 0, ',', '.') }}</span></div>
                        
                        @foreach ($invoice->adjustments as $adjustment)
                             <div class="d-flex justify-content-between {{ $adjustment->type == 'credit_note' ? 'text-success' : 'text-danger' }} small">
                                <span>{{ $adjustment->type == 'credit_note' ? 'Nota Kredit (Potongan)' : 'Nota Debit (Tambahan)' }}</span>
                                <span>{{ $adjustment->type == 'credit_note' ? '(-)' : '(+)' }} Rp {{ number_format($adjustment->amount, 0, ',', '.') }}</span>
                             </div>
                        @endforeach
                        
                        @if($totalReturDipotong > 0)
                            <div class="d-flex justify-content-between text-warning small"><span>Total Retur (Potong Tagihan)</span><span>(-) Rp {{ number_format($totalReturDipotong, 0, ',', '.') }}</span></div>
                        @endif
                        
                        <hr class="my-1">
                        <div class="d-flex justify-content-between text-success"><span>Sudah Dibayar</span><span>(-) Rp {{ number_format($invoice->amount_paid, 0, ',', '.') }}</span></div>
                        
                        @if($pendingPaymentAmount > 0)
                            <div class="d-flex justify-content-between text-info">
                                <span>Menunggu Verifikasi</span><span>(-) Rp {{ number_format($pendingPaymentAmount, 0, ',', '.') }}</span>
                            </div>
                        @endif
                        
                        @if($totalReturKredit > 0)
                            <div class="d-flex justify-content-between text-info small"><span>(Nilai retur jadi kredit: Rp {{ number_format($totalReturKredit, 0, ',', '.') }})</span></div>
                        @endif
                        <hr class="my-1">
                        
                        <div class="d-flex justify-content-between fw-bold fs-5 {{ ($sisaTagihan - $pendingPaymentAmount) > 0.01 ? 'text-danger' : 'text-success' }}">
                            <span>Sisa Tagihan</span>
                            <span>Rp {{ number_format($sisaTagihan - $pendingPaymentAmount, 0, ',', '.') }}</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="paymentMethodModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Pilih Metode Pembayaran</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <p>Silakan pilih cara Anda ingin membayar tagihan ini:</p>
                <div class="d-grid gap-3">
                    <button class="btn btn-outline-primary p-3" id="pay-manual-transfer-btn">
                        <i class="bi bi-bank2 fs-4 me-2"></i>
                        <div>
                            <span class="fw-bold">Transfer Bank / Lainnya</span><br>
                            <small>Upload bukti transfer manual, foto giro, dll.</small>
                        </div>
                    </button>
                    <button class="btn btn-outline-success p-3" id="pay-cash-btn">
                        <i class="bi bi-person-check-fill fs-4 me-2"></i>
                        <div>
                            <span class="fw-bold">Cash (via Sales)</span><br>
                            <small>Pembayaran tunai melalui tim sales.</small>
                        </div>
                    </button>
                    <button class="btn btn-outline-dark p-3" id="pay-online-btn">
                        <i class="bi bi-credit-card-2-front-fill fs-4 me-2"></i>
                        <div>
                            <span class="fw-bold">Pembayaran Online</span><br>
                            <small>Kartu Kredit, Virtual Account, dll.</small>
                        </div>
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="manualPaymentModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="manualPaymentModalTitle">Catat Pembayaran</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form action="{{ route('client.invoices.uploadProof', $invoice->invoice_id) }}" method="POST" enctype="multipart/form-data" id="manual-payment-form">
                @csrf
                <input type="hidden" name="use_credit" id="manual-use-credit-hidden" value="0">
                
                <div class="modal-body">
                    <div class="alert alert-info">
                        <div class="d-flex justify-content-between fw-bold">
                            <span>Sisa Tagihan:</span>
                            <span id="manual-sisa-tagihan-display">Rp {{ number_format($sisaTagihan, 0, ',', '.') }}</span>
                        </div>
                    </div>
                    
                    @if($saldoKreditKlien > 0)
                    <div id="manual-credit-info" class="alert alert-success">
                        <div class="d-flex justify-content-between fw-bold">
                            <span>Saldo Kredit Tersedia:</span>
                            <span>Rp {{ number_format($saldoKreditKlien, 0, ',', '.') }}</span>
                        </div>
                        <div class="form-check form-switch mt-2">
                            <input class="form-check-input" type="checkbox" role="switch" id="manual-use-credit" value="1">
                            <label class="form-check-label" for="manual-use-credit">Gunakan Saldo Kredit</label>
                        </div>
                    </div>
                    @endif

                    <div id="cash-fields" class="d-none">
                        <div class="mb-3">
                            <label for="user_id_sales" class="form-label">Diterima oleh Sales <span class="text-danger">*</span></label>
                            <select name="user_id_sales" id="user_id_sales" class="form-select">
                                <option value="" disabled selected>-- Pilih Sales --</option>
                                @foreach($salesUsers as $sales)
                                    <option value="{{ $sales->user_id }}">{{ $sales->full_name }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>

                    <div id="transfer-fields" class="d-none">
                        <div class="mb-3">
                            <label for="payment_method_id" class="form-label">Metode Pembayaran <span class="text-danger">*</span></label>
                            <select name="payment_method_id" id="payment_method_id" class="form-select">
                                <option value="">-- Pilih Metode --</option>
                                @foreach($paymentMethods as $method)
                                    <option value="{{ $method->payment_method_id }}" 
                                            data-config="{{ $method->required_fields_config }}">
                                        {{ $method->name }}
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        <div class="mb-3" id="payment-reference-group" style="display: none;">
                            <label for="reference_number" class="form-label">Nomor Referensi (Giro/Cek)</label>
                            <input type="text" class="form-control" name="reference_number" id="reference_number">
                        </div>

                        <div class="mb-3" id="payment-proof-group" style="display: none;">
                            <label for="proof_of_payment" class="form-label">File Bukti Bayar (JPG, PNG)</label>
                            <input type="file" class="form-control" name="proof_of_payment" id="proof_of_payment" accept="image/jpeg,image/png,image/jpg">
                        </div>
                    </div>

                    <div class="mb-3">
                        <label for="payment_amount_display" class="form-label">Jumlah Dibayar (Manual/Cash) <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" id="payment_amount_display" placeholder="Rp 0">
                        <input type="hidden" name="payment_amount" id="payment_amount">
                        <div id="amount-error" class="text-danger small mt-1 d-none"></div>
                    </div>
                    <div class="mb-3">
                        <label for="notes" class="form-label">Catatan (Opsional)</label>
                        <textarea name="notes" id="notes" class="form-control" rows="2"></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-primary" id="submit-proof-btn">Kirim Bukti</button>
                </div>
            </form>
        </div>
    </div>
</div>

<div class="modal fade" id="midtransPaymentModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Pembayaran Online Invoice #{{ $invoice->invoice_number }}</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            
            <form id="midtrans-payment-form" action="{{ route('client.invoices.pay', $invoice->invoice_id) }}" method="POST">
                @csrf
                <div class="modal-body">
                    
                    <div class="alert alert-info">
                        <div class="d-flex justify-content-between fw-bold">
                            <span>Sisa Tagihan:</span>
                            <span id="midtrans-sisa-tagihan-display">Rp {{ number_format($sisaTagihan, 0, ',', '.') }}</span>
                        </div>
                    </div>
                    
                    @if($saldoKreditKlien > 0)
                    <div id="midtrans-credit-info" class="alert alert-success">
                        <div class="d-flex justify-content-between fw-bold">
                            <span>Saldo Kredit Tersedia:</span>
                            <span>Rp {{ number_format($saldoKreditKlien, 0, ',', '.') }}</span>
                        </div>
                        <div class="form-check form-switch mt-2">
                            <input class="form-check-input" type="checkbox" role="switch" id="midtrans-use-credit" value="1">
                            <label class="form-check-label" for="midtrans-use-credit">Gunakan Saldo Kredit</label>
                        </div>
                    </div>
                    @endif
                    
                    <div class="mb-3">
                        <label for="midtrans-amount-formatted" class="form-label">Jumlah Pembayaran</label>
                        <input type="text" class="form-control" id="midtrans-amount-formatted" required>
                        <input type="hidden" name="amount" id="midtrans-amount-hidden">
                        <input type="hidden" name="use_credit" id="midtrans-use-credit-hidden" value="0">
                        <div id="midtrans-amount-error" class="text-danger small mt-1 d-none"></div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-primary" id="midtrans-submit-btn">Lanjutkan ke Pembayaran</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection


@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/autonumeric@4.6.0/dist/autoNumeric.min.js"></script>
<script type="text/javascript"
    src="{{ config('midtrans.is_production') ? 'https://app.midtrans.com/snap/snap.js' : 'https://app.sandbox.midtrans.com/snap/snap.js' }}"
    data-client-key="{{ config('midtrans.client_key') }}"></script>
<script type"text/javascript">
    
    const remainingBalance = parseFloat("{{ $sisaTagihan }}");
    const currentCreditBalance = parseFloat("{{ $saldoKreditKlien }}");
    const transferMethodId = "{{ $transferMethodId }}";
    const cashMethodId = "{{ $cashMethodId }}";

    document.addEventListener('DOMContentLoaded', function() {
        const popoverTriggerList = document.querySelectorAll('[data-bs-toggle="popover"]');
        [...popoverTriggerList].map(popoverTriggerEl => new bootstrap.Popover(popoverTriggerEl));
        const tooltipTriggerList = document.querySelectorAll('[data-bs-toggle="tooltip"]');
        [...tooltipTriggerList].map(tooltipTriggerEl => new bootstrap.Tooltip(tooltipTriggerEl));

        const paymentMethodModal = new bootstrap.Modal(document.getElementById('paymentMethodModal'));
        const manualPaymentModal = new bootstrap.Modal(document.getElementById('manualPaymentModal'));
        const midtransPaymentModal = new bootstrap.Modal(document.getElementById('midtransPaymentModal'));

        
        const manualPaymentForm = document.querySelector('#manualPaymentModal form');
        if (manualPaymentForm) {
            const titleEl = document.getElementById('manualPaymentModalTitle');
            
            const methodDropdown = document.getElementById('payment_method_id');
            
            const cashFields = document.getElementById('cash-fields');
            const transferFields = document.getElementById('transfer-fields');
            const salesSelect = document.getElementById('user_id_sales');
            const proofInput = document.getElementById('proof_of_payment');
            const proofGroup = document.getElementById('payment-proof-group');
            const referenceInput = document.getElementById('reference_number');
            const referenceGroup = document.getElementById('payment-reference-group');
            
            const submitBtn = document.getElementById('submit-proof-btn');
            
            const amountDisplay = document.getElementById('payment_amount_display');
            const amountHidden = document.getElementById('payment_amount');
            const amountError = document.getElementById('amount-error');
            const useCreditCheck = document.getElementById('manual-use-credit');
            const useCreditHidden = document.getElementById('manual-use-credit-hidden');

            const autoNumericInstance = new AutoNumeric(amountDisplay, { 
                decimalPlaces: 0, 
                digitGroupSeparator: '.', 
                decimalCharacter: ',',
                currencySymbol: 'Rp ',
                currencySymbolPlacement: 'p',
                minimumValue: 0,
            });

            function handleMethodConfigChange() {
                if (!methodDropdown) return;
                const selectedOption = methodDropdown.options[methodDropdown.selectedIndex];
                const config = (selectedOption && !methodDropdown.disabled) ? selectedOption.dataset.config : 'none';

                referenceGroup.style.display = 'none';
                referenceInput.required = false;
                proofGroup.style.display = 'none';
                proofInput.required = false;

                if (config === 'proof_only') {
                    proofGroup.style.display = 'block';
                    proofInput.required = true;
                } else if (config === 'reference_only') {
                    referenceGroup.style.display = 'block';
                    referenceInput.required = true;
                } else if (config === 'proof_and_reference') {
                    proofGroup.style.display = 'block';
                    proofInput.required = true;
                    referenceGroup.style.display = 'block';
                    referenceInput.required = true;
                }
            }

            if (methodDropdown) {
                methodDropdown.addEventListener('change', handleMethodConfigChange);
            }

            function toggleManualFields() {
                const useCredit = useCreditCheck ? useCreditCheck.checked : false;
                const creditIsSufficient = currentCreditBalance >= remainingBalance && remainingBalance > 0;

                if (useCredit) {
                    useCreditHidden.value = '1';
                    if (creditIsSufficient) {
                        autoNumericInstance.set(0);
                        amountDisplay.disabled = true;
                    } else {
                        const shortfall = remainingBalance - currentCreditBalance;
                        autoNumericInstance.set(shortfall);
                        amountDisplay.disabled = false;
                    }
                } else {
                    useCreditHidden.value = '0';
                    autoNumericInstance.set(remainingBalance);
                    amountDisplay.disabled = false;
                }
                validateManualAmount();
            }

            function validateManualAmount() {
                const rawValue = autoNumericInstance.getNumericString();
                amountHidden.value = rawValue;
                const useCredit = useCreditCheck ? useCreditCheck.checked : false;
                const totalPaymentValue = (useCredit ? currentCreditBalance : 0) + parseFloat(rawValue || 0);

                let isValid = true;
                let errorMessage = '';

                if (rawValue === null || rawValue === '' || parseFloat(rawValue) < 0) {
                     isValid = false;
                }
                
                if (totalPaymentValue > (remainingBalance + 0.01)) {
                    errorMessage = 'Info: Jumlah bayar melebihi sisa tagihan. Kelebihan akan jadi saldo kredit.';
                    isError = false;
                }
                if (totalPaymentValue <= 0.01 && remainingBalance > 0.01) {
                    isValid = false;
                    errorMessage = 'Jumlah pembayaran harus lebih dari 0.';
                    isError = true;
                }

                submitBtn.disabled = !isValid;
                if (errorMessage) {
                    amountError.textContent = errorMessage;
                    amountError.classList.remove('d-none');
                    amountError.classList.toggle('text-danger', isError);
                    amountError.classList.toggle('text-success', !isError);
                } else {
                    amountError.classList.add('d-none');
                }
            }

            if (useCreditCheck) {
                useCreditCheck.addEventListener('change', toggleManualFields);
            }
            amountDisplay.addEventListener('keyup', validateManualAmount);
            amountDisplay.addEventListener('change', validateManualAmount);

            document.getElementById('pay-manual-transfer-btn').addEventListener('click', function() {
                paymentMethodModal.hide();
                titleEl.textContent = 'Konfirmasi Pembayaran Manual';
                
                cashFields.classList.add('d-none');
                transferFields.classList.remove('d-none');
                salesSelect.required = false;
                
                methodDropdown.value = transferMethodId; // Pre-select 'Transfer'
                
                if(useCreditCheck) useCreditCheck.checked = true;
                toggleManualFields();
                handleMethodConfigChange(); // Panggil untuk set form dinamis
                manualPaymentModal.show();
            });
            
            document.getElementById('pay-cash-btn').addEventListener('click', function() {
                paymentMethodModal.hide();
                titleEl.textContent = 'Konfirmasi Pembayaran Cash';
                
                transferFields.classList.add('d-none');
                cashFields.classList.remove('d-none');
                salesSelect.required = true;

                methodDropdown.value = cashMethodId; // Pre-select 'Cash'
                
                if(useCreditCheck) useCreditCheck.checked = true;
                toggleManualFields();
                handleMethodConfigChange(); // Panggil untuk set form dinamis
                manualPaymentModal.show();
            });
        }

        document.getElementById('pay-online-btn').addEventListener('click', function() {
            paymentMethodModal.hide();
            midtransPaymentModal.show();
        });

        const midtransForm = document.getElementById('midtrans-payment-form');
        const midtransAmountFormatted = document.getElementById('midtrans-amount-formatted');
        const midtransAmountHidden = document.getElementById('midtrans-amount-hidden');
        const midtransUseCreditCheck = document.getElementById('midtrans-use-credit');
        const midtransUseCreditHidden = document.getElementById('midtrans-use-credit-hidden');
        const midtransAmountError = document.getElementById('midtrans-amount-error');
        const midtransSubmitBtn = document.getElementById('midtrans-submit-btn');

        if (midtransForm) {
            const midtransAutoNumeric = new AutoNumeric(midtransAmountFormatted, {
                decimalPlaces: 0,
                digitGroupSeparator: '.',
                decimalCharacter: ',',
                currencySymbol: 'Rp ',
                currencySymbolPlacement: 'p',
                minimumValue: 0,
            });

            function toggleMidtransFields() {
                const useCredit = midtransUseCreditCheck ? midtransUseCreditCheck.checked : false;
                const creditIsSufficient = currentCreditBalance >= remainingBalance && remainingBalance > 0;

                if (useCredit) {
                    midtransUseCreditHidden.value = '1';
                    if (creditIsSufficient) {
                        midtransAutoNumeric.set(0);
                        midtransAmountFormatted.disabled = true;
                    } else {
                        const shortfall = remainingBalance - currentCreditBalance;
                        midtransAutoNumeric.set(shortfall);
                        midtransAmountFormatted.disabled = false;
                    }
                } else {
                    midtransUseCreditHidden.value = '0';
                    midtransAutoNumeric.set(remainingBalance);
                    midtransAmountFormatted.disabled = false;
                }
                validateMidtransAmount();
            }

            function validateMidtransAmount() {
                const rawValue = midtransAutoNumeric.getNumericString();
                midtransAmountHidden.value = rawValue;
                const useCredit = midtransUseCreditCheck ? midtransUseCreditCheck.checked : false;
                const totalPaymentValue = (useCredit ? currentCreditBalance : 0) + parseFloat(rawValue || 0);
                let isValid = true;
                let errorMessage = '';
                let isError = true;

                if (rawValue === null || rawValue === '' || parseFloat(rawValue) < 0) {
                     isValid = false;
                }
                
                if (totalPaymentValue > (remainingBalance + 0.01)) {
                    errorMessage = 'Info: Jumlah bayar melebihi sisa tagihan.';
                    isError = false;
                }
                if (totalPaymentValue <= 0.01 && remainingBalance > 0.01) {
                    isValid = false;
                    errorMessage = 'Jumlah pembayaran harus lebih dari 0.';
                    isError = true;
                }

                midtransSubmitBtn.disabled = !isValid;
                if (errorMessage) {
                    midtransAmountError.textContent = errorMessage;
                    midtransAmountError.classList.remove('d-none');
                    midtransAmountError.classList.toggle('text-danger', isError);
                    midtransAmountError.classList.toggle('text-success', !isError);
                } else {
                    midtransAmountError.classList.add('d-none');
                }
            }

            if (midtransUseCreditCheck) {
                midtransUseCreditCheck.addEventListener('change', toggleMidtransFields);
            }
            midtransAmountFormatted.addEventListener('keyup', validateMidtransAmount);
            midtransAmountFormatted.addEventListener('change', validateMidtransAmount);

            midtransPaymentModal._element.addEventListener('shown.bs.modal', function () {
                if (midtransUseCreditCheck) {
                    midtransUseCreditCheck.checked = true;
                }
                toggleMidtransFields();
            });
            
            midtransForm.addEventListener('submit', function(event) {
                event.preventDefault();
                
                const csrfToken = '{{ csrf_token() }}';
                const formData = new FormData(this);
                const payButton = this.querySelector('button[type="submit"]');
                payButton.disabled = true;
                payButton.innerHTML = 'Memproses...';

                fetch(this.action, {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': csrfToken,
                        'Accept': 'application/json',
                    },
                    body: formData
                })
                .then(response => response.json())
                .then(data => {
                    const redirectUrl = "{{ route('client.invoices.index') }}";
                    
                    if (data.status === 'paid_by_credit') {
                        window.location.href = redirectUrl + '?payment_success=1';
                        return;
                    }

                    if (data.snap_token) {
                        window.snap.pay(data.snap_token, {
                            onSuccess: function(result){ window.location.href = redirectUrl + '?payment_success=1'; },
                            onPending: function(result){ window.location.href = redirectUrl + '?payment_pending=1'; },
                            onError: function(result){ 
                                alert("Pembayaran gagal!");
                                payButton.disabled = false;
                                payButton.innerHTML = 'Lanjutkan ke Pembayaran';
                            },
                            onClose: function(){
                                console.log('Snap pop-up ditutup oleh user.');
                                payButton.disabled = false;
                                payButton.innerHTML = 'Lanjutkan ke Pembayaran';
                            }
                        });
                    } else {
                        throw new Error(data.message || 'Gagal mendapatkan token pembayaran.');
                    }
                })
                .catch(error => {
                    alert('Error: ' + error.message);
                    payButton.disabled = false;
                    payButton.innerHTML = 'Lanjutkan ke Pembayaran';
                });
            });
        }
    });
</script>
@endpush
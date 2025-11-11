@extends('layouts.app')

@php
    $sisaTagihan = $invoice->remaining_balance;
    $totalReturDipotong = $invoice->total_deducting_returns;
    $totalReturKredit = $invoice->returns
        ->where('return_handling_type', 'store_as_credit')
        ->sum('total_amount');
    $saldoKreditKlien = $invoice->client->balance;
@endphp

@section('content')
<div class="container py-4">
    <div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
        <h2 class="fw-bold mb-0">Detail Invoice: {{ $invoice->invoice_number }}</h2>
        
        <div class="d-flex flex-wrap justify-content-end gap-2">
            <a href="{{ route('invoices.index') }}" class="btn btn-outline-secondary"><i class="bi bi-arrow-left me-1"></i> Kembali</a>
            
            @if($invoice->status == 'draft')
                <form id="confirm-form-show" action="{{ route('invoices.confirm', $invoice->invoice_id) }}" method="POST">
                    @csrf
                    <button type="submit" class="btn btn-primary fw-bold">
                        <i class="bi bi-check-circle-fill me-1"></i> Konfirmasi Invoice
                    </button>
                </form>
            @endif
            
            @if(!in_array($invoice->status, ['cancelled', 'draft']) && $sisaTagihan > 0.01)
                <button type="button" class="btn btn-success" data-bs-toggle="modal" data-bs-target="#paymentModal" id="add-payment-btn">
                    <i class="bi bi-cash-coin me-1"></i> Catat Pembayaran
                </button>
            @endif
            
            <div class="btn-group">
                <button type="button" class="btn btn-outline-secondary dropdown-toggle" data-bs-toggle="dropdown" aria-expanded="false">
                    <i class="bi bi-gear"></i> Opsi
                </button>
                <ul class="dropdown-menu dropdown-menu-end">
                    @if(!in_array($invoice->status, ['paid', 'cancelled']))
                        <li><a class="dropdown-item" href="{{ route('invoices.edit', $invoice->invoice_id) }}"><i class="bi bi-pencil-square me-2"></i> Edit Invoice</a></li>
                    @endif
                    <li><hr class="dropdown-divider"></li>
                    <li>
                        <a class="dropdown-item" href="{{ route('invoice-adjustments.create') }}?sales_invoice_id={{ $invoice->invoice_id }}">
                            <i class="bi bi-file-earmark-diff me-2"></i> Buat Penyesuaian
                        </a>
                    </li>
                    <li><hr class="dropdown-divider"></li>
                    <li><a class="dropdown-item" href="{{ route('invoices.pdf', $invoice->invoice_id) }}"><i class="bi bi-file-earmark-pdf me-2"></i> Download PDF</a></li>
                    @if(!in_array($invoice->status, ['draft', 'paid', 'cancelled']))
                        <li><hr class="dropdown-divider"></li>
                        <li>
                            {{-- ✅ MODIFIKASI: Menghapus onsubmit dan menambah class --}}
                            <form action="{{ route('invoices.cancel', $invoice->invoice_id) }}" method="POST" class="form-cancel-invoice">
                                @csrf
                                <button type="submit" class="dropdown-item text-danger"><i class="bi bi-x-circle me-2"></i> Batalkan Invoice</button>
                            </form>
                        </li>
                    @endif
                </ul>
            </div>
        </div>
    </div>

    <div class="card shadow-sm border-0">
        <div class="card-body p-4">
            <div class="row mb-4">
                <div class="col-md-6">
                    <h5 class="fw-semibold">Klien</h5>
                    <p class="mb-0">{{ $invoice->client->client_name }}</p>
                    <p class="text-muted">{{ $invoice->client->address ?? 'Alamat tidak tersedia' }}</p>
                </div>
                <div class="col-md-6 text-md-end">
                    <p class="mb-1"><strong>Tanggal Pesanan:</strong> {{ optional($invoice->order_date)->format('d F Y') }}</p>
                    @if($invoice->due_date)<p class="mb-1"><strong>Jatuh Tempo:</strong> {{ optional($invoice->due_date)->format('d F Y') }}</p>@endif
                    @if($invoice->sales)<p class="mb-1"><strong>Sales:</strong> {{ $invoice->sales->full_name }} ({{ $invoice->sales->sales_code }})</p>@endif
                    <p class="mb-1"><strong>Status:</strong>
                        @if($invoice->status == 'paid') <span class="badge bg-success fs-6">Lunas</span>
                        @elseif($invoice->status == 'partially_paid') <span class="badge bg-info text-dark fs-6">Cicil</span>
                        @elseif($invoice->status == 'cancelled') <span class="badge bg-danger fs-6">Dibatalkan</span>
                        @elseif($invoice->status == 'unpaid') <span class="badge bg-warning text-dark fs-6">Belum Lunas</span>
                        @elseif($invoice->status == 'draft') <span class="badge bg-secondary fs-6">Draft</span>
                        @else <span class="badge bg-secondary fs-6">{{ $invoice->status }}</span>
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
                        <tr><td colspan="5" class="text-center">Tidak ada item dalam invoice ini.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            @php
                $pendingPayments = $invoice->payments->where('status', 'pending_verification');
            @endphp

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
                            
                            <div class="d-flex gap-2">
                                {{-- ✅ MODIFIKASI: Menambah class --}}
                                <form action="{{ route('payments.reject', $payment->payment_id) }}" method="POST" class="d-inline mb-0 form-reject-payment">
                                    @csrf
                                    <button type="submit" class="btn btn-sm btn-danger">Tolak</button>
                                </form>
                                {{-- ✅ MODIFIKASI: Menambah class --}}
                                <form action="{{ route('payments.approve', $payment->payment_id) }}" method="POST" class="d-inline mb-0 form-approve-payment">
                                    @csrf
                                    <button type="submit" class="btn btn-sm btn-success">Setujui</button>
                                </form>
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
                            <th>Aksi</th>
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
                            <td>
                                {{-- Form ini sudah ada SweetAlert-nya dari script Anda sebelumnya --}}
                                <form action="{{ route('invoice-adjustments.destroy', $adjustment->adjustment_id) }}" method="POST" class="form-cancel-adjustment">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="btn btn-xs btn-danger" title="Batalkan Penyesuaian">
                                        <i class="bi bi-trash"></i>
                                    </button>
                                </form>
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            @endif

            <h5 class="fw-semibold mt-4">Riwayat Pembayaran</h5>
            <div class="table-responsive">
                <table class="table table-sm table-bordered">
                    <thead class="table-light">
                        <tr>
                            <th>Tanggal Bayar</th>
                            <th>Metode</th>
                            <th class="text-end">Jumlah</th>
                            <th>Referensi</th>
                            <th>Dicatat Oleh</th>
                            <th>Status</th>
                            <th>Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($invoice->payments as $payment)
                        <tr>
                            <td>{{ $payment->payment_date->format('d/m/Y') }}</td>
                            <td>{{ $payment->paymentMethod->name ?? 'N/A' }}</td>
                            <td class="text-end">Rp {{ number_format($payment->amount, 0, ',', '.') }}</td>
                            <td>{{ $payment->reference_number ?? '-' }}</td>
                            <td>{{ $payment->receivedBy->full_name ?? 'N/A' }}</td>
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
                                {{-- ✅ MODIFIKASI: Menghapus onsubmit dan menambah class --}}
                                <form action="{{ route('payments.destroy', $payment) }}" method="POST" class="d-inline form-delete-payment">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="btn btn-sm btn-outline-danger" title="Hapus (Rollback)">
                                        <i class="bi bi-trash-fill"></i>
                                    </button>
                                </form>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="7" class="text-center text-muted">Belum ada riwayat pembayaran.</td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

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
                        <div class="d-flex justify-content-between mb-2"><span>Subtotal Produk</span><span>Rp {{ number_format($invoice->subtotal, 0, ',', '.') }}</span></div>
                        @if($invoice->discount_amount > 0)
                            <div class="d-flex justify-content-between mb-2 text-danger"><span>Diskon ({{ $invoice->discount_percentage }}%)</span><span>(-) Rp {{ number_format($invoice->discount_amount, 0, ',', '.') }}</span></div>
                        @endif
                        <hr class="my-1">
                        <div class="d-flex justify-content-between fw-semibold mb-2"><span>Subtotal Setelah Diskon</span><span>Rp {{ number_format($invoice->subtotal - $invoice->discount_amount, 0, ',', '.') }}</span></div>
                        @foreach($invoice->taxes as $tax)
                            <div class="d-flex justify-content-between mb-2"><span>{{ $tax->pivot->name }} ({{ $tax->pivot->rate }}%)</span><span>(+) Rp {{ number_format($tax->pivot->amount, 0, ',', '.') }}</span></div>
                        @endforeach
                        <hr class="my-1">
                        <div class="d-flex justify-content-between fw-bold"><span>Total Tagihan</span><span>Rp {{ number_format($invoice->total_amount, 0, ',', '.') }}</span></div>
                        
                        @if($totalReturDipotong > 0)
                            <div class="d-flex justify-content-between text-warning"><span>Total Retur (Potong Tagihan)</span><span>(-) Rp {{ number_format($totalReturDipotong, 0, ',', '.') }}</span></div>
                        @endif
                        @foreach ($invoice->adjustments as $adjustment)
                             <div class="d-flex justify-content-between {{ $adjustment->type == 'credit_note' ? 'text-success' : 'text-danger' }}">
                                 <span>{{ $adjustment->type == 'credit_note' ? 'Nota Kredit (Potongan)' : 'Nota Debit (Tambahan)' }}</span>
                                 <span>{{ $adjustment->type == 'credit_note' ? '(-)' : '(+)' }} Rp {{ number_format($adjustment->amount, 0, ',', '.') }}</span>
                               </div>
                        @endforeach

                        <hr class="my-1">
                        <div class="d-flex justify-content-between text-success"><span>Sudah Dibayar</span><span>Rp {{ number_format($invoice->amount_paid, 0, ',', '.') }}</span></div>
                        
                        @if($totalReturKredit > 0)
                            <div class="d-flex justify-content-between text-info small"><span>(Nilai retur jadi kredit: Rp {{ number_format($totalReturKredit, 0, ',', '.') }})</span></div>
                        @endif
                        <hr class="my-1">
                        
                        <div class="d-flex justify-content-between fw-bold fs-5 {{ $sisaTagihan > 0.01 ? 'text-danger' : 'text-success' }}">
                            <span>Sisa Tagihan</span>
                            <span>Rp {{ number_format($sisaTagihan, 0, ',', '.') }}</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

{{-- MODAL PEMBAYARAN (Tidak ada perubahan di sini) --}}
<div class="modal fade" id="paymentModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header"><h5 class="modal-title">Catat Pembayaran untuk #{{ $invoice->invoice_number }}</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
            <form action="{{ route('payments.store', $invoice->invoice_id) }}" method="POST" enctype="multipart/form-data">
                @csrf
                
                <div class="modal-body">
                    
                    <div class="alert alert-info">
                        <div class="d-flex justify-content-between"><span>Total Tagihan Awal:</span><span>Rp {{ number_format($invoice->total_amount, 0, ',', '.') }}</span></div>

                        @foreach ($invoice->adjustments as $adjustment)
                            <div class="d-flex justify-content-between {{ $adjustment->type == 'credit_note' ? 'text-success' : 'text-danger' }} small">
                                <span>{{ $adjustment->type == 'credit_note' ? 'Nota Kredit (Potongan):' : 'Nota Debit (Tambahan):' }}</span>
                                <span>{{ $adjustment->type == 'credit_note' ? '(-)' : '(+)' }} Rp {{ number_format($adjustment->amount, 0, ',', '.') }}</span>
                            </div>
                        @endforeach

                        @if($totalReturDipotong > 0)
                            <div class="d-flex justify-content-between text-warning small"><span>Total Retur (Potong):</span><span>(-) Rp {{ number_format($totalReturDipotong, 0, ',', '.') }}</span></div>
                        @endif

                        <hr class="my-1">
                        <div class="d-flex justify-content-between small"><span>Sudah Dibayar:</span><span>(+) Rp {{ number_format($invoice->amount_paid, 0, ',', '.') }}</span></div>
                        <hr class="my-1">
                        
                        <div class="d-flex justify-content-between fw-bold">
                            <span>Sisa Tagihan:</span>
                            <span id="modal-sisa-tagihan-display">Rp {{ number_format($sisaTagihan, 0, ',', '.') }}</span>
                        </div>
                    </div>

                    @if($saldoKreditKlien > 0)
                    <div id="credit-info-container" class="alert alert-success">
                        <div class="d-flex justify-content-between fw-bold">
                            <span>Saldo Kredit Tersedia:</span>
                            <span id="modal-credit-balance-display">Rp {{ number_format($saldoKreditKlien, 0, ',', '.') }}</span>
                        </div>
                        <div class="form-check form-switch mt-2">
                            <input class="form-check-input" type="checkbox" role="switch" id="modal-use-credit" name="use_credit" value="1">
                            <label class="form-check-label" for="modal-use-credit">Gunakan Saldo Kredit</label>
                        </div>
                    </div>
                    @endif

                    <div class="mb-3">
                        <label for="amount-formatted" class="form-label">Jumlah Dibayar (Non-Kredit)</label>
                        <input type="text" class="form-control" id="amount-formatted" required>
                        <input type="hidden" name="amount" id="amount">
                        <div id="amount-error" class="text-danger small mt-1"></div>
                    </div>
                    <div class="mb-3">
                        <label for="payment_date" class="form-label">Tanggal Bayar</label>
                        <input type="date" class="form-control" name="payment_date" id="payment_date" value="{{ now()->format('Y-m-d') }}" required>
                    </div>
                    
                    <div class="mb-3">
                        <label for="payment_method_id" class="form-label">Metode Pembayaran (Non-Kredit)</label>
                        <select name="payment_method_id" id="payment_method" class="form-select" required>
                            <option value="">-- Pilih Metode --</option>
                            @foreach($paymentMethods as $method)
                                <option value="{{ $method->payment_method_id }}" 
                                        data-config="{{ $method->required_fields_config }}">
                                    {{ $method->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="mb-3">
                        <label for="company_bank_account_id" class="form-label">Setor ke Akun <span class="text-danger">*</span></label>
                        <select name="company_bank_account_id" id="company_bank_account_id" class="form-select" required>
                            <option value="">-- Pilih Akun Bank/Kas --</option>
                            @foreach($companyBankAccounts as $account)
                                <option value="{{ $account->company_bank_account_id }}">
                                    {{ $account->bank_name }} - {{ $account->account_number ?? $account->account_name }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    
                    <div class="mb-3" id="payment-reference-group" style="display: none;">
                        <label for="reference_number" class="form-label">Nomor Referensi (Giro/Cek)</label>
                        <input type="text" class="form-control" name="reference_number" id="reference_number">
                    </div>

                    <div class="mb-3" id="payment-proof-group" style="display: none;">
                        <label for="proof_of_payment" class="form-label">Bukti Pembayaran (Foto)</label>
                        <input type="file" class="form-control" name="proof_of_payment" id="proof_of_payment" accept="image/jpeg,image/png,image/jpg">
                    </div>
                    
                    <div class="mb-3">
                        <label for="notes" class="form-label">Catatan</label>
                        <textarea name="notes" id="notes" class="form-control" rows="2"></textarea>
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
@endsection

@push('scripts')
{{-- AutoNumeric tidak perlu di-load lagi karena sudah ada di app.blade.php, tapi tidak masalah jika ada --}}
<script src="https://cdn.jsdelivr.net/npm/autonumeric@4.6.0/dist/autoNumeric.min.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    const popoverTriggerList = document.querySelectorAll('[data-bs-toggle="popover"]');
    [...popoverTriggerList].map(popoverTriggerEl => new bootstrap.Popover(popoverTriggerEl));
    const tooltipTriggerList = document.querySelectorAll('[data-bs-toggle="tooltip"]');
    [...tooltipTriggerList].map(tooltipTriggerEl => new bootstrap.Tooltip(tooltipTriggerEl));

    const addPaymentBtn = document.getElementById('add-payment-btn');
    const amountFormattedInput = document.getElementById('amount-formatted');
    const amountHiddenInput = document.getElementById('amount');
    const amountError = document.getElementById('amount-error');
    const useCreditCheckbox = document.getElementById('modal-use-credit');
    const paymentMethodSelect = document.getElementById('payment_method');
    const companyBankAccountSelect = document.getElementById('company_bank_account_id');
    
    const remainingBalance = {{ $sisaTagihan ?? 0 }};
    const currentCreditBalance = {{ $saldoKreditKlien ?? 0 }};
    const defaultPaymentMethodId = "{{ $paymentMethods->first()->payment_method_id ?? '' }}";
    const defaultBankAccountId = "{{ $companyBankAccounts->first()->company_bank_account_id ?? '' }}";

    if (amountFormattedInput) {
        const autoNumericInstance = new AutoNumeric(amountFormattedInput, { 
            decimalPlaces: 0, 
            digitGroupSeparator: '.', 
            decimalCharacter: ',' 
        });

        function toggleRequiredFields() {
            const useCredit = useCreditCheckbox ? useCreditCheckbox.checked : false;
            const creditIsSufficient = currentCreditBalance >= remainingBalance && remainingBalance > 0;
            const inputAmountValue = parseFloat(amountHiddenInput.value || 0);

            if (useCredit) {
                if (creditIsSufficient) {
                    autoNumericInstance.set(0);
                    amountFormattedInput.disabled = true;
                    amountFormattedInput.required = false;
                    paymentMethodSelect.disabled = true;
                    paymentMethodSelect.required = false;
                    paymentMethodSelect.value = "";
                    companyBankAccountSelect.disabled = true;
                    companyBankAccountSelect.required = false;
                    companyBankAccountSelect.value = "";
                } else {
                    const shortfall = remainingBalance - currentCreditBalance;
                    autoNumericInstance.set(shortfall);
                    amountFormattedInput.disabled = false;
                    amountFormattedInput.required = true;
                    paymentMethodSelect.disabled = false;
                    paymentMethodSelect.required = true;
                    companyBankAccountSelect.disabled = false;
                    companyBankAccountSelect.required = true;
                    if (!paymentMethodSelect.value) paymentMethodSelect.value = defaultPaymentMethodId;
                    if (!companyBankAccountSelect.value) companyBankAccountSelect.value = defaultBankAccountId;
                }
            } else {
                autoNumericInstance.set(remainingBalance);
                amountFormattedInput.disabled = false;
                amountFormattedInput.required = true;
                
                const isAmountPositive = inputAmountValue > 0 || remainingBalance > 0;
                
                paymentMethodSelect.disabled = false;
                paymentMethodSelect.required = isAmountPositive;
                companyBankAccountSelect.disabled = false;
                companyBankAccountSelect.required = isAmountPositive;

                if (isAmountPositive) {
                    if (!paymentMethodSelect.value) paymentMethodSelect.value = defaultPaymentMethodId;
                    if (!companyBankAccountSelect.value) companyBankAccountSelect.value = defaultBankAccountId;
                }
            }
            // Panggil juga fungsi untuk form dinamis
            handlePaymentMethodChange();
        }

        if (addPaymentBtn) {
            addPaymentBtn.addEventListener('click', function() {
                if (useCreditCheckbox) {
                    useCreditCheckbox.checked = true; 
                }
                toggleRequiredFields();
                amountError.textContent = '';
            });
        }

        if (useCreditCheckbox) {
            useCreditCheckbox.addEventListener('change', toggleRequiredFields);
        }

        amountFormattedInput.addEventListener('autoNumeric:rawValueModified', function(event) {
            const rawValue = event.detail.newRawValue;
            amountHiddenInput.value = rawValue;
            
            const isAmountPositive = parseFloat(rawValue || 0) > 0;
            
            if (useCreditCheckbox && !useCreditCheckbox.checked) {
                paymentMethodSelect.required = isAmountPositive;
                companyBankAccountSelect.required = isAmountPositive;
            }

            const totalPayment = (useCreditCheckbox && useCreditCheckbox.checked ? currentCreditBalance : 0) + parseFloat(rawValue || 0);
            if (totalPayment > remainingBalance) {
                amountError.textContent = 'Info: Kelebihan bayar akan jadi saldo kredit.';
                amountError.classList.remove('text-danger');
                amountError.classList.add('text-success');
            } else {
                amountError.textContent = '';
            }
        });
    }

    // --- (SCRIPT ANDA YANG SUDAH ADA) ---
    const cancelAdjustmentForms = document.querySelectorAll('.form-cancel-adjustment');
    cancelAdjustmentForms.forEach(form => {
        form.addEventListener('submit', function (event) {
            event.preventDefault(); 
            Swal.fire({
                title: 'Anda Yakin?',
                text: "Anda akan membatalkan penyesuaian ini. Sisa tagihan invoice akan dihitung ulang.",
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
    });

    const confirmFormShow = document.getElementById('confirm-form-show');
    if (confirmFormShow) {
        confirmFormShow.addEventListener('submit', function(event) {
            event.preventDefault(); 
            Swal.fire({
                title: 'Konfirmasi Invoice Ini?',
                text: "Stok akan diperiksa dan dikurangi. Status akan berubah menjadi 'Belum Lunas'.",
                icon: 'question',
                showCancelButton: true,
                confirmButtonColor: '#198754', 
                cancelButtonColor: '#6c757d',
                confirmButtonText: 'Ya, Konfirmasi!',
                cancelButtonText: 'Batal'
            }).then((result) => {
                if (result.isConfirmed) {
                    event.target.submit(); 
                }
            });
        });
    }

    // --- (SCRIPT BARU DITAMBAHKAN) ---

    // Konfirmasi Batalkan Invoice
    const cancelInvoiceForm = document.querySelector('.form-cancel-invoice');
    if (cancelInvoiceForm) {
        cancelInvoiceForm.addEventListener('submit', function (event) {
            event.preventDefault(); 
            Swal.fire({
                title: 'Anda Yakin?',
                text: "Anda akan membatalkan invoice ini. Tindakan ini tidak dapat diurungkan.",
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

    // Konfirmasi Tolak Pembayaran
    const rejectPaymentForms = document.querySelectorAll('.form-reject-payment');
    rejectPaymentForms.forEach(form => {
        form.addEventListener('submit', function (event) {
            event.preventDefault(); 
            Swal.fire({
                title: 'Tolak Pembayaran?',
                text: "Anda yakin ingin menolak pembayaran ini? Status akan dikembalikan.",
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#d33',
                cancelButtonColor: '#6c757d',
                confirmButtonText: 'Ya, Tolak!',
                cancelButtonText: 'Batal'
            }).then((result) => {
                if (result.isConfirmed) {
                    event.target.submit();
                }
            });
        });
    });

    // Konfirmasi Setujui Pembayaran
    const approvePaymentForms = document.querySelectorAll('.form-approve-payment');
    approvePaymentForms.forEach(form => {
        form.addEventListener('submit', function (event) {
            event.preventDefault(); 
            Swal.fire({
                title: 'Setujui Pembayaran?',
                text: "Anda akan menyetujui pembayaran ini. Sisa tagihan akan di-update.",
                icon: 'question',
                showCancelButton: true,
                confirmButtonColor: '#198754',
                cancelButtonColor: '#6c757d',
                confirmButtonText: 'Ya, Setujui!',
                cancelButtonText: 'Batal'
            }).then((result) => {
                if (result.isConfirmed) {
                    event.target.submit();
                }
            });
        });
    });

    // Konfirmasi Hapus (Rollback) Pembayaran
    const deletePaymentForms = document.querySelectorAll('.form-delete-payment');
    deletePaymentForms.forEach(form => {
        form.addEventListener('submit', function (event) {
            event.preventDefault(); 
            Swal.fire({
                title: 'Anda Yakin?',
                text: "Anda akan membatalkan pembayaran ini. Jurnal akan dibalik dan sisa tagihan dihitung ulang. Ini adalah tindakan 'rollback'.",
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

    // --- (SCRIPT ANDA YANG SUDAH ADA) ---
    const referenceGroup = document.getElementById('payment-reference-group');
    const referenceInput = document.getElementById('reference_number');
    const proofGroup = document.getElementById('payment-proof-group');
    const proofInput = document.getElementById('proof_of_payment');
    
    function handlePaymentMethodChange() {
        if (!paymentMethodSelect) return; 
        
        const selectedOption = paymentMethodSelect.options[paymentMethodSelect.selectedIndex];
        const config = (selectedOption && !paymentMethodSelect.disabled) ? selectedOption.dataset.config : 'none';

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

    if (paymentMethodSelect) {
        paymentMethodSelect.addEventListener('change', handlePaymentMethodChange);
    }
});
</script>
@endpush
@extends('layouts.app')

{{-- ==================================================================== --}}
{{-- ✅ BLOK PHP GLOBAL UNTUK SEMUA PERHITUANGAN (WAJIB DI ATAS) --}}
{{-- ==================================================================== --}}
@php
    // Definisikan semua variabel di sini, di bagian atas
    
    // 1. Untuk Ringkasan Keuangan
    $sisaTagihan = $invoice->remaining_balance;
    $totalReturDipotong = $invoice->total_deducting_returns;
    $totalReturKredit = $invoice->returns
        ->where('return_handling_type', 'store_as_credit')
        ->sum('total_amount');
    
    // 2. Untuk Modal Pembayaran
    $saldoKreditKlien = $invoice->client->balance;
@endphp
{{-- ==================================================================== --}}


@section('content')
<div class="container py-4">
    {{-- HEADER HALAMAN DENGAN TOMBOL AKSI --}}
    <div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
        <h2 class="fw-bold mb-0">Detail Invoice: {{ $invoice->invoice_number }}</h2>
        
        <div class="d-flex flex-wrap justify-content-end gap-2">
            <a href="{{ route('invoices.index') }}" class="btn btn-outline-secondary"><i class="bi bi-arrow-left me-1"></i> Kembali</a>
            
            {{-- Gunakan variabel $sisaTagihan yang sudah didefinisikan di atas --}}
            @if(!in_array($invoice->status, ['cancelled']) && $sisaTagihan > 0.01)
                <button type="button" class="btn btn-success" data-bs-toggle="modal" data-bs-target="#paymentModal" id="add-payment-btn">
                    <i class="bi bi-cash-coin me-1"></i> Catat Pembayaran
                </button>
            @endif
            
            <div class="btn-group">
                <button type="button" class="btn btn-outline-secondary dropdown-toggle" data-bs-toggle="dropdown" aria-expanded="false">
                    <i class="bi bi-gear"></i> Opsi
                </button>
                <ul class="dropdown-menu dropdown-menu-end">
                     <li><a class="dropdown-item" href="{{ route('invoices.edit', $invoice->invoice_id) }}"><i class="bi bi-pencil-square me-2"></i> Edit Invoice</a></li>
                     <li><hr class="dropdown-divider"></li>
                     <li>
                         <a class="dropdown-item" href="{{ route('invoice-adjustments.create', $invoice->invoice_id) }}">
                             <i class="bi bi-file-earmark-diff me-2"></i> Buat Penyesuaian
                         </a>
                     </li>
                     <li><hr class="dropdown-divider"></li>
                     <li><a class="dropdown-item" href="{{ route('invoices.pdf', $invoice->invoice_id) }}"><i class="bi bi-file-earmark-pdf me-2"></i> Download PDF</a></li>
                     @if(!in_array($invoice->status, ['paid', 'cancelled']))
                         <li><hr class="dropdown-divider"></li>
                         <li>
                             <form action="{{ route('invoices.cancel', $invoice->invoice_id) }}" method="POST" onsubmit="return confirm('Anda yakin ingin membatalkan invoice ini?');">
                                 @csrf
                                 <button type="submit" class="dropdown-item text-danger"><i class="bi bi-x-circle me-2"></i> Batalkan Invoice</button>
                             </form>
                         </li>
                     @endif
                </ul>
            </div>
        </div>
    </div>

    {{-- KARTU DETAIL UTAMA --}}
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
                        @else <span class="badge bg-warning text-dark fs-6">Belum Lunas</span>
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
                            <th>N</th>
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

            {{-- =============================================== --}}
            {{-- VERIFIKASI BUKTI PEMBAYARAN --}}
            {{-- =============================================== --}}
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
            {{-- Informasi Jumlah dan Tanggal --}}
            <div>
                <div><strong>Jumlah:</strong> Rp {{ number_format($payment->amount, 0, ',', '.') }}</div>
                <div><strong>Tanggal Lapor:</strong> {{ $payment->created_at->format('d M Y H:i') }}</div>
                @if($payment->notes)
                    <div class="text-muted small mt-1"><strong>Catatan Klien:</strong> {{ $payment->notes }}</div>
                @endif
            </div>

            {{-- Aksi Verifikasi --}}
            <div class="d-flex gap-3 align-items-center">
                {{-- Kolom Bukti / Penerima --}}
                <div class="text-center">
                    @if($payment->payment_method == 'manual_transfer' && $payment->proof_of_payment_path)
                        <a href="{{ asset('storage/' . $payment->proof_of_payment_path) }}" target="_blank" class="btn btn-sm btn-outline-info">Lihat Bukti</a>
                    @elseif($payment->payment_method == 'cash' && $payment->receivedBy)
                        <button type="button" class="btn btn-sm btn-outline-secondary" 
                                data-bs-toggle="popover" 
                                data-bs-title="Diterima Oleh" 
                                data-bs-content="{{ $payment->receivedBy->full_name }}">
                            Cash
                        </button>
                    @else
                        <span class="text-muted">-</span>
                    @endif
                </div>
                
                {{-- Kolom Tombol Aksi --}}
                <div class="d-flex gap-2">
                    <form action="{{ route('payments.reject', $payment->payment_id) }}" method="POST" class="d-inline mb-0">
                        @csrf
                        <button type="submit" class="btn btn-sm btn-danger">Tolak</button>
                    </form>
                    <form action="{{ route('payments.approve', $payment->payment_id) }}" method="POST" class="d-inline mb-0">
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

            {{-- ====================================================== --}}
            {{-- ✅ POSISI BARU: RIWAYAT PENYESUAIAN --}}
            {{-- ====================================================== --}}
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
            {{-- ====================================================== --}}
            {{-- 🛑 AKHIR BLOK PENYESUAIAN --}}
            {{-- ====================================================== --}}


            {{-- =============================================== --}}
            {{-- RIWAYAT PEMBAYARAN --}}
            {{-- =============================================== --}}
            <h5 class="fw-semibold mt-4">Riwayat Pembayaran</h5>
            <div class="table-responsive">
                <table class="table table-sm table-bordered">
                    <thead class="table-light">
                        <tr>
                            <th>Tanggal Bayar</th>
                            <th class="text-end">Jumlah</th>
                            <th>Metode</th>
                            <th>Status</th>
                            <th>Diverifikasi oleh</th>
                            <th>Penerima / Bukti</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($invoice->payments as $payment)
                        <tr>
                            <td>{{ $payment->payment_date->format('d M Y') }}</td>
                            <td class="text-end">Rp {{ number_format($payment->amount, 0, ',', '.') }}</td>
                            <td>{{ Str::title(str_replace('_', ' ', $payment->payment_method)) }}</td>
                            <td>
                                @if($payment->status == 'completed')
                                    <span class="badge bg-success">Completed</span>
                                @elseif($payment->status == 'pending_verification')
                                    <span class="badge bg-warning text-dark">Pending</span>
                                @else
                                    <span class="badge bg-danger">Failed</span>
                                @endif
                            </td>
                            <td>{{ $payment->receivedBy->full_name ?? '-' }}</td>
                             <td>
                    @if($payment->payment_method == 'cash' && $payment->receivedBy)
                        {{ $payment->receivedBy->full_name }} (Sales)
                    @elseif($payment->proof_of_payment_path)
                        <a href="{{ asset('storage/' . $payment->proof_of_payment_path) }}" target="_blank" class="btn btn-sm btn-outline-info">
                            Lihat Bukti
                        </a>
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
                        
                        {{-- Tampilkan HANYA retur yang dipotong (Variabel dari @php di atas) --}}
                        @if($totalReturDipotong > 0)
                            <div class="d-flex justify-content-between text-warning"><span>Total Retur (Potong Tagihan)</span><span>(-) Rp {{ number_format($totalReturDipotong, 0, ',', '.') }}</span></div>
                        @endif
                        {{-- Tampilkan Nota Penyesuaian --}}
                        @foreach ($invoice->adjustments as $adjustment)
                             <div class="d-flex justify-content-between {{ $adjustment->type == 'credit_note' ? 'text-success' : 'text-danger' }}">
                                <span>{{ $adjustment->type == 'credit_note' ? 'Nota Kredit (Potongan)' : 'Nota Debit (Tambahan)' }}</span>
                                <span>{{ $adjustment->type == 'credit_note' ? '(-)' : '(+)' }} Rp {{ number_format($adjustment->amount, 0, ',', '.') }}</span>
                             </div>
                        @endforeach

                        <hr class="my-1">
                        <div class="d-flex justify-content-between text-success"><span>Sudah Dibayar</span><span>Rp {{ number_format($invoice->amount_paid, 0, ',', '.') }}</span></div>
                        
                        {{-- Tampilkan info jika ada retur yg jadi kredit (Variabel dari @php di atas) --}}
                        @if($totalReturKredit > 0)
                            <div class="d-flex justify-content-between text-info small"><span>(Nilai retur jadi kredit: Rp {{ number_format($totalReturKredit, 0, ',', '.') }})</span></div>
                        @endif
                        <hr class="my-1">
                        
                        {{-- Tampilkan variabel $sisaTagihan (dari @php di atas) --}}
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

<div class="modal fade" id="paymentModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header"><h5 class="modal-title">Catat Pembayaran untuk #{{ $invoice->invoice_number }}</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
            <form action="{{ route('payments.store', $invoice->invoice_id) }}" method="POST">
                @csrf
                
                {{-- =================================== --}}
                {{-- ✅ INI ADALAH BAGIAN YANG DIPERBARUI --}}
                {{-- =================================== --}}
                <div class="modal-body">
                    
                    {{-- Variabel $sisaTagihan, $saldoKreditKlien, dll. --}}
                    {{-- sudah didefinisikan di blok @php di atas halaman --}}
                    
                    {{-- Info Sisa Tagihan --}}
                    <div class="alert alert-info">
                        <div class="d-flex justify-content-between"><span>Total Tagihan Awal:</span><span>Rp {{ number_format($invoice->total_amount, 0, ',', '.') }}</span></div>

                        {{-- Tampilkan Penyesuaian (jika ada) --}}
                        @foreach ($invoice->adjustments as $adjustment)
                            <div class="d-flex justify-content-between {{ $adjustment->type == 'credit_note' ? 'text-success' : 'text-danger' }} small">
                                <span>{{ $adjustment->type == 'credit_note' ? 'Nota Kredit (Potongan):' : 'Nota Debit (Tambahan):' }}</span>
                                <span>{{ $adjustment->type == 'credit_note' ? '(-)' : '(+)' }} Rp {{ number_format($adjustment->amount, 0, ',', '.') }}</span>
                            </div>
                        @endforeach

                        {{-- Tampilkan Retur Potong Nota (jika ada) --}}
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

                    {{-- Info Saldo Kredit --}}
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

                    {{-- Form Input --}}
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
                        <label for="payment_method" class="form-label">Metode Pembayaran (Non-Kredit)</label>
                        <select name="payment_method" id="payment_method" class="form-select" required>
                            <option value="">-- Pilih Metode --</option>
                            <option value="manual_transfer">Transfer Bank</option>
                            <option value="cash">Cash</option>
                            <option value="other">Lainnya</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label for="notes" class="form-label">Catatan</label>
                        <textarea name="notes" id="notes" class="form-control" rows="2"></textarea>
                    </div>
                </div>
                {{-- =================================== --}}
                {{-- 🛑 AKHIR DARI BAGIAN YANG DIPERBARUI --}}
                {{-- =================================== --}}

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
<script src="https://cdn.jsdelivr.net/npm/autonumeric@4.6.0/dist/autoNumeric.min.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Inisialisasi Popover
    const popoverTriggerList = document.querySelectorAll('[data-bs-toggle="popover"]');
    [...popoverTriggerList].map(popoverTriggerEl => new bootstrap.Popover(popoverTriggerEl));

    // Elemen Modal
    const addPaymentBtn = document.getElementById('add-payment-btn');
    const amountFormattedInput = document.getElementById('amount-formatted');
    const amountHiddenInput = document.getElementById('amount');
    const amountError = document.getElementById('amount-error');
    const useCreditCheckbox = document.getElementById('modal-use-credit');
    const paymentMethodSelect = document.getElementById('payment_method');
    
    // Nilai-nilai (Gunakan variabel dari @php di atas)
    const remainingBalance = {{ $sisaTagihan ?? 0 }};
    const currentCreditBalance = {{ $saldoKreditKlien ?? 0 }};

    if (amountFormattedInput) {
        // Inisialisasi AutoNumeric
        const autoNumericInstance = new AutoNumeric(amountFormattedInput, { 
            decimalPlaces: 0, 
            digitGroupSeparator: '.', 
            decimalCharacter: ',' 
        });

        // Fungsi untuk mengatur state input berdasarkan checkbox kredit
        function toggleRequiredFields() {
            const useCredit = useCreditCheckbox ? useCreditCheckbox.checked : false;
            const creditIsSufficient = currentCreditBalance >= remainingBalance && remainingBalance > 0;
            const inputAmountValue = parseFloat(amountHiddenInput.value || 0);

            if (useCredit) {
                if (creditIsSufficient) {
                    // Kredit cukup: dana input 0, nonaktif, tidak wajib
                    autoNumericInstance.set(0);
                    amountFormattedInput.disabled = true;
                    amountFormattedInput.required = false;
                    paymentMethodSelect.disabled = true;
                    paymentMethodSelect.required = false;
                    paymentMethodSelect.value = "";
                } else {
                    // Kredit kurang: isi kekurangan, aktif, wajib
                    const shortfall = remainingBalance - currentCreditBalance;
                    autoNumericInstance.set(shortfall);
                    amountFormattedInput.disabled = false;
                    amountFormattedInput.required = true;
                    paymentMethodSelect.disabled = false;
                    paymentMethodSelect.required = true;
                    if (!paymentMethodSelect.value) paymentMethodSelect.value = "manual_transfer";
                }
            } else {
                // Tidak pakai kredit: isi penuh, aktif, wajib
                autoNumericInstance.set(remainingBalance);
                amountFormattedInput.disabled = false;
                amountFormattedInput.required = true;
                paymentMethodSelect.disabled = false;
                paymentMethodSelect.required = inputAmountValue > 0 || remainingBalance > 0; 
                if (paymentMethodSelect.required && !paymentMethodSelect.value) paymentMethodSelect.value = "manual_transfer";
            }
        }

        // Listener saat tombol "Catat Pembayaran" diklik
        if (addPaymentBtn) {
            addPaymentBtn.addEventListener('click', function() {
                if (useCreditCheckbox) {
                    useCreditCheckbox.checked = true; // Auto-centang
                }
                toggleRequiredFields();
                amountError.textContent = '';
            });
        }

        // Listener saat checkbox kredit diganti
        if (useCreditCheckbox) {
            useCreditCheckbox.addEventListener('change', toggleRequiredFields);
        }

        // Listener saat input amount diubah
        amountFormattedInput.addEventListener('autoNumeric:rawValueModified', function(event) {
            const rawValue = event.detail.newRawValue;
            amountHiddenInput.value = rawValue;
            
            if (useCreditCheckbox && !useCreditCheckbox.checked) {
                 paymentMethodSelect.required = parseFloat(rawValue || 0) > 0;
            }

            // Info overpayment (akan jadi saldo kredit)
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

    // Script Swal untuk Batal Penyesuaian
    const cancelAdjustmentForms = document.querySelectorAll('.form-cancel-adjustment');
    
    cancelAdjustmentForms.forEach(form => {
        form.addEventListener('submit', function (event) {
            event.preventDefault(); // Hentikan submit form
            
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
});
</script>
@endpush
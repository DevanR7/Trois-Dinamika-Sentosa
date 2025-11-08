@extends('layouts.client')

@push('styles')
{{-- Select2 CSS --}}
<link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/select2-bootstrap-5-theme@1.3.0/dist/select2-bootstrap-5-theme.min.css" />
@endpush

{{-- ==================================================================== --}}
{{-- ✅ BLOK PHP BARU UNTUK MENGAMBIL ID METODE --}}
{{-- ==================================================================== --}}
@php
    // Asumsi $paymentMethods dikirim dari Client/InvoiceController@showBatchPay
    $transferMethodId = $paymentMethods->firstWhere(fn($m) => str_contains(strtolower($m->name), 'transfer'))->payment_method_id ?? null;
    $cashMethodId = $paymentMethods->firstWhere(fn($m) => str_contains(strtolower($m->name), 'cash'))->payment_method_id ?? null;
@endphp
{{-- ==================================================================== --}}


@section('content')
<div class="container-fluid py-4">
    <h2 class="fw-bold mb-4">Pembayaran Tagihan (Batch)</h2>

    <form id="batch-payment-form">
        @csrf
        <div class="row g-4">
            {{-- Kolom Kiri: Daftar Invoice --}}
            <div class="col-lg-8">
                <div class="card shadow-sm">
                    <div class="card-header bg-white">
                        <h5 class="mb-0 fw-semibold">1. Pilih Tagihan yang Akan Dibayar</h5>
                    </div>
                    <div class="card-body p-0" style="max-height: 600px; overflow-y: auto;">
                        <div class="table-responsive">
                            <table class="table table-hover align-middle mb-0">
                                <thead class="table-light sticky-top">
                                    <tr>
                                        <th class="p-3"><input type="checkbox" id="check-all-invoices" class="form-check-input"></th>
                                        <th class="p-3">No. Invoice</th>
                                        <th class="p-3">Jatuh Tempo</th>
                                        <th class="p-3 text-end">Sisa Tagihan</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse($invoices as $invoice)
                                        <tr>
                                            <td class="px-3">
                                                <input class="form-check-input invoice-checkbox" 
                                                       type="checkbox" 
                                                       name="invoice_ids[]" 
                                                       value="{{ $invoice->invoice_id }}"
                                                       data-balance="{{ $invoice->remaining_balance }}">
                                            </td>
                                            <td>
                                                <a href="{{ route('client.invoices.show', $invoice->invoice_id) }}" target="_blank">{{ $invoice->invoice_number }}</a>
                                                <small class="d-block text-muted">Tgl: {{ $invoice->order_date->format('d M Y') }}</small>
                                            </td>
                                            <td class="{{ $invoice->due_date->isPast() ? 'text-danger fw-bold' : '' }}">
                                                {{ $invoice->due_date->format('d M Y') }}
                                            </td>
                                            <td class="text-end px-3">Rp {{ number_format($invoice->remaining_balance, 0, ',', '.') }}</td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="4" class="text-center text-muted p-5">
                                                <i class="bi bi-check-circle-fill fs-3 text-success"></i>
                                                <p class="mt-2 mb-0">Luar biasa! Tidak ada tagihan yang belum lunas.</p>
                                            </td>
                                        </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Kolom Kanan: Ringkasan Pembayaran --}}
            <div class="col-lg-4">
                <div class="card shadow-sm sticky-top" style="top: 20px;">
                    <div class="card-header bg-white">
                        <h5 class="mb-0 fw-semibold">2. Ringkasan & Pembayaran</h5>
                    </div>
                    <div class="card-body">
                        <div class="d-flex justify-content-between mb-2">
                            <span>Total Tagihan Dipilih</span>
                            <strong id="summary-total-tagihan" data-total="0">Rp 0</strong>
                        </div>
                        
                        @if($availableBalance > 0)
                        <div class="d-flex justify-content-between mb-2">
                            <span>Saldo Kredit Tersedia</span>
                            <strong class="text-success" id="summary-saldo-kredit-value" data-balance="{{ $availableBalance }}">
                                Rp {{ number_format($availableBalance, 0, ',', '.') }}
                            </strong>
                        </div>
                        <div class="form-check form-switch mb-3">
                            <input class="form-check-input" type="checkbox" role="switch" id="use-credit-batch" value="1">
                            <label class="form-check-label" for="use-credit-batch">Gunakan Saldo Kredit</label>
                        </div>
                        @else
                            <input type="hidden" id="use-credit-batch" value="0">
                        @endif

                        @if($pendingBalance > 0)
                            <div class="text-muted small mb-3">
                                (Saldo tertahan: Rp {{ number_format($pendingBalance, 0, ',', '.') }})
                            </div>
                        @endif
                        
                        <div class="mb-3">
                            <label for="batch-amount-formatted" class="form-label fw-semibold">Jumlah Pembayaran</label>
                            <input type="text" class="form-control" id="batch-amount-formatted">
                            <div id="batch-amount-error" class="text-info small mt-1 d-none"></div>
                        </div>

                        <hr>
                        <div class="d-flex justify-content-between fw-bold fs-5 text-primary mb-4">
                            <span>Total Ditagih (ke Payment)</span>
                            <strong id="summary-total-ditagih">Rp 0</strong>
                        </div>

                        <h6 class="fw-semibold">3. Pilih Metode Pembayaran</h6>
                        <div class="d-grid gap-3">
                            <button type="button" class="btn btn-outline-dark p-3" id="pay-online-btn" disabled>
                                <i class="bi bi-credit-card-2-front-fill fs-4 me-2"></i>
                                <div>
                                    <span class="fw-bold">Bayar Online (Midtrans)</span><br>
                                    <small>Total ditagih akan dibayar via Midtrans.</small>
                                </div>
                            </button>
                            <button type="button" class="btn btn-outline-primary p-3" id="pay-manual-transfer-btn" disabled>
                                <i class="bi bi-bank2 fs-4 me-2"></i>
                                <div>
                                    <span class="fw-bold">Upload Bukti Transfer</span><br>
                                    <small>Laporkan pembayaran batch manual.</small>
                                </div>
                            </button>
                            <button type="button" class="btn btn-outline-success p-3" id="pay-cash-btn" disabled>
                                <i class="bi bi-person-check-fill fs-4 me-2"></i>
                                <div>
                                    <span class="fw-bold">Lapor Bayar Tunai (via Sales)</span><br>
                                    <small>Laporkan pembayaran batch tunai.</small>
                                </div>
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </form>
</div>

{{-- ========================================================== --}}
{{-- ✅ MODAL MANUAL (DIPERBARUI) --}}
{{-- ========================================================== --}}
<div class="modal fade" id="batchManualPaymentModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="batchManualPaymentModalTitle">Konfirmasi Pembayaran Manual</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form id="batch-manual-form" action="{{ route('client.invoices.batchPay.storeManual') }}" method="POST" enctype="multipart/form-data">
                @csrf
                
                {{-- ✅ PERBAIKAN: Ganti name="payment_method" menjadi "payment_method_id" --}}
                <input type="hidden" name="payment_method_id" id="batch_payment_method_id_input">
                <input type="hidden" name="use_credit" id="batch-manual-use-credit-hidden">
                
                <div class="modal-body">
                    <div class="alert alert-info">
                        <div class="d-flex justify-content-between"><span>Total Pembayaran Anda:</span><strong id="batch-modal-total-bayar">Rp 0</strong></div>
                        <div class="d-flex justify-content-between"><span>Saldo Kredit Digunakan:</span><strong id="batch-modal-kredit-dipakai" class="text-success">Rp 0</strong></div>
                        <hr class="my-1">
                        <div class="d-flex justify-content-between fw-bold fs-5">
                            <span>Jumlah yang Harus Dibayar:</span>
                            <strong id="batch-modal-sisa-bayar">Rp 0</strong>
                        </div>
                    </div>

                    <div id="batch-cash-fields" class="d-none">
                        <div class="mb-3">
                            <label for="batch_user_id_sales" class="form-label">Diterima oleh Sales <span class="text-danger">*</span></label>
                            <select name="user_id_sales" id="batch_user_id_sales" class="form-select select2-in-modal">
                                <option value="" disabled selected>-- Pilih Sales --</option>
                                @php
                                    // Pindahkan query ke controller jika memungkinkan,
                                    // tapi untuk sekarang ini tidak apa-apa
                                    $salesUsers = \App\Models\User::role('sales')->get();
                                @endphp
                                @foreach($salesUsers as $sales)
                                    <option value="{{ $sales->user_id }}">{{ $sales->full_name }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>

                    <div id="batch-transfer-fields" class="d-none">
                        <div class="mb-3">
                            <label for="batch_proof_of_payment" class="form-label">File Bukti Bayar (JPG, PNG) <span class="text-danger">*</span></label>
                            <input type="file" class="form-control" name="proof_of_payment" id="batch_proof_of_payment" accept="image/jpeg,image/png">
                        </div>
                    </div>

                    <div class="mb-3">
                        <label for="batch_payment_amount_display" class="form-label">Jumlah Dibayar (Manual/Cash) <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" id="batch_payment_amount_display" placeholder="Rp 0">
                        <input type="hidden" name="payment_amount" id="batch_payment_amount">
                        <div id="batch-amount-error" class="text-danger small mt-1 d-none"></div>
                    </div>
                    <div class="mb-3">
                        <label for="batch_notes" class="form-label">Catatan (Opsional)</label>
                        <textarea name="notes" id="batch_notes" class="form-control" rows="2"></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-primary" id="batch-submit-proof-btn">Kirim Bukti</button>
                </div>
            </form>
        </div>
    </div>
</div>


{{-- MODAL MIDTRANS (Tidak Berubah) --}}
<div class="modal fade" id="batchMidtransPaymentModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Pembayaran Online (Batch)</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            
            <form id="batch-midtrans-form" action="{{ route('client.invoices.batchPay.storeMidtrans') }}" method="POST">
                @csrf
                
                <div class="modal-body">
                    <div class="alert alert-info">
                        <div class="d-flex justify-content-between"><span>Total Pembayaran Anda:</span><strong id="midtrans-summary-total-bayar">Rp 0</strong></div>
                        <div class="d-flex justify-content-between"><span>Saldo Kredit Digunakan:</span><strong id="midtrans-summary-kredit" class="text-success">Rp 0</strong></div>
                        <hr class="my-1">
                        <div class="d-flex justify-content-between fw-bold fs-5">
                            <span>Total Ditagih ke Midtrans:</span>
                            <strong id="midtrans-summary-ditagih">Rp 0</strong>
                        </div>
                    </div>
                    <p>Anda akan diarahkan ke halaman Midtrans untuk menyelesaikan pembayaran.</p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-primary" id="batch-midtrans-submit-btn">Lanjutkan ke Pembayaran</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection

@push('scripts')
{{-- Select2 & AutoNumeric JS --}}
{{-- <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script> --}} {{-- Asumsi jQuery sudah di-load di layouts/client.blade.php --}}
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/autonumeric@4.6.0/dist/autoNumeric.min.js"></script>
<script type="text/javascript"
    src="{{ config('midtrans.is_production') ? 'https://app.midtrans.com/snap/snap.js' : 'https://app.sandbox.midtrans.com/snap/snap.js' }}"
    data-client-key="{{ config('midtrans.client_key') }}"></script>
    
<script>
document.addEventListener('DOMContentLoaded', function () {
    // === Inisialisasi Global ===
    const checkboxes = document.querySelectorAll('.invoice-checkbox');
    const checkAll = document.getElementById('check-all-invoices');
    const useCreditCheck = document.getElementById('use-credit-batch');
    const availableBalance = parseFloat(document.getElementById('summary-saldo-kredit-value')?.dataset.balance || 0);
    
    const btnMidtrans = document.getElementById('pay-online-btn');
    const btnManual = document.getElementById('pay-manual-transfer-btn');
    const btnCash = document.getElementById('pay-cash-btn');
    
    const summaryTagihan = document.getElementById('summary-total-tagihan');
    const summaryDitagih = document.getElementById('summary-total-ditagih');

    const amountDisplay = document.getElementById('batch-amount-formatted');
    const amountError = document.getElementById('batch-amount-error');
    
    // ✅ PERBAIKAN: Ambil ID dari PHP
    const transferMethodId = "{{ $transferMethodId }}";
    const cashMethodId = "{{ $cashMethodId }}";
    
    const autoNumericInstance = new AutoNumeric(amountDisplay, {
        decimalPlaces: 0, 
        digitGroupSeparator: '.', 
        decimalCharacter: ',',
        currencySymbol: 'Rp ',
        currencySymbolPlacement: 'p',
        minimumValue: 0
    });

    const batchMidtransModal = new bootstrap.Modal(document.getElementById('batchMidtransPaymentModal'));
    const batchManualModal = new bootstrap.Modal(document.getElementById('batchManualPaymentModal'));
    const midtransForm = document.getElementById('batch-midtrans-form');
    const manualForm = document.getElementById('batch-manual-form');

    // Variabel Global
    let currentTotalTagihan = 0;
    let currentTotalDitagih = 0;
    let currentKreditDigunakan = 0;
    let currentAmountFromInput = 0;
    let currentTotalPaymentValue = 0; // Ini adalah total nilai yang ingin dibayar klien

    // === Fungsi Helper ===
    const min = (a, b) => Math.min(a, b);
    const max = (a, b) => Math.max(a, b);
    const round = (num) => Math.round(num);

    function formatRupiah(number) {
        return new Intl.NumberFormat('id-ID', { style: 'currency', currency: 'IDR', minimumFractionDigits: 0 }).format(number);
    }

    // ======================================================
    // ✅ FUNGSI PERHITUNGAN BARU (LEBIH BENAR)
    // ======================================================
    function calculateTotal() {
        // 1. Hitung total tagihan yang dicentang
        currentTotalTagihan = 0;
        checkboxes.forEach(cb => {
            if (cb.checked) {
                currentTotalTagihan += parseFloat(cb.dataset.balance);
            }
        });
        summaryTagihan.textContent = formatRupiah(currentTotalTagihan);
        summaryTagihan.dataset.total = currentTotalTagihan;
        
        // 2. Ambil jumlah yang diinput klien
        currentAmountFromInput = parseFloat(autoNumericInstance.getNumericString() || 0);
        const useCredit = useCreditCheck ? useCreditCheck.checked : false;
        
        // 3. Tentukan Total Nilai Pembayaran (apa yang ingin dibayar klien)
        currentTotalPaymentValue = currentAmountFromInput; // Nilai total yang ingin dibayar = input
        currentKreditDigunakan = 0; // Default

        // 4. Terapkan kredit jika dicentang
        if (useCredit && availableBalance > 0) {
            // Kredit yg dipakai = min(saldo, total yg mau dibayar)
            currentKreditDigunakan = min(availableBalance, currentTotalPaymentValue);
        }

        // 5. Hitung total yang akan ditagih (ke Midtrans/Manual)
        // Total yg ditagih = Total Pembayaran - Kredit yang dipakai
        currentTotalDitagih = currentTotalPaymentValue - currentKreditDigunakan;
        currentTotalDitagih = round(max(0, currentTotalDitagih)); // Tidak boleh negatif

        summaryDitagih.textContent = formatRupiah(currentTotalDitagih);

        // 6. Validasi
        let isValid = true;
        let errorMessage = '';
        let isError = true; // Tipe pesan (merah)
        const hasSelection = currentTotalTagihan > 0;

        // Validasi: Total bayar (input) tidak boleh 0
        if (currentTotalPaymentValue <= 0.01 && hasSelection) {
            isValid = false;
            errorMessage = 'Jumlah pembayaran harus lebih dari 0.';
        }
        
        // Cek Overpayment (DI-IZINKAN, tapi beri info)
        if (hasSelection && currentTotalPaymentValue > (currentTotalTagihan + 0.01)) {
            isError = false; // Ini info, bukan error
            errorMessage = 'Info: Anda akan kelebihan bayar. Sisa dana akan jadi saldo kredit.';
        }

        // Aktifkan tombol
        btnMidtrans.disabled = !isValid || !hasSelection;
        btnManual.disabled = !isValid || !hasSelection;
        btnCash.disabled = !isValid || !hasSelection;

        // Tampilkan error/info
        amountError.textContent = errorMessage;
        amountError.classList.toggle('d-none', !errorMessage);
        amountError.classList.toggle('text-danger', isError);
        amountError.classList.toggle('text-info', !isError); // Ganti jadi info
    }
    
    // === Event Listeners Utama ===
    checkAll.addEventListener('change', function () {
        checkboxes.forEach(cb => { cb.checked = this.checked; });
        let totalChecked = 0;
        if(this.checked) {
            checkboxes.forEach(c => {
                if (c.checked) totalChecked += parseFloat(c.dataset.balance);
            });
        }
        autoNumericInstance.set(totalChecked); // Auto-isi lunas/0
        calculateTotal();
    });
    
    checkboxes.forEach(cb => {
        cb.addEventListener('change', function() {
            let totalChecked = 0;
            checkboxes.forEach(c => {
                if (c.checked) totalChecked += parseFloat(c.dataset.balance);
            });
            autoNumericInstance.set(totalChecked); // Auto-isi lunas/0
            calculateTotal();
        });
    });

    if (useCreditCheck) {
        useCreditCheck.addEventListener('change', calculateTotal);
    }
    amountDisplay.addEventListener('keyup', calculateTotal);
    amountDisplay.addEventListener('change', calculateTotal);
    
    calculateTotal(); // Panggil saat memuat

    // ======================================================
    // ✅ LOGIKA MODAL MANUAL (DIPERBARUI)
    // ======================================================
    const manualTitle = document.getElementById('batchManualPaymentModalTitle');
    // ✅ PERBAIKAN: Target ID input yang baru
    const manualMethodInput = document.getElementById('batch_payment_method_id_input');
    const manualCashFields = document.getElementById('batch-cash-fields');
    const manualTransferFields = document.getElementById('batch-transfer-fields');
    const manualSalesSelect = document.getElementById('batch_user_id_sales');
    const manualProofInput = document.getElementById('batch_proof_of_payment');
    const manualAmountDisplay = document.getElementById('batch_payment_amount_display');
    const manualAmountHidden = document.getElementById('batch_payment_amount');
    const manualAmountError = document.getElementById('batch-amount-error');
    const manualSubmitBtn = document.getElementById('batch-submit-proof-btn');
    const manualTotalBayar = document.getElementById('batch-modal-total-bayar'); // Diubah
    const manualKreditDipakai = document.getElementById('batch-modal-kredit-dipakai');
    const manualSisaBayar = document.getElementById('batch-modal-sisa-bayar');
    const manualUseCreditHidden = document.getElementById('batch-manual-use-credit-hidden');
    
    // Inisialisasi Select2 di dalam modal
    $('.select2-in-modal').select2({
        theme: 'bootstrap-5',
        dropdownParent: $('#batchManualPaymentModal')
    });

    const manualAutoNumeric = new AutoNumeric(manualAmountDisplay, {
        decimalPlaces: 0, digitGroupSeparator: '.', decimalCharacter: ',', minimumValue: 0
    });
    
    function validateManualForm() {
        const rawValue = manualAutoNumeric.getNumericString();
        manualAmountHidden.value = rawValue;
        
        let isValid = true;
        let errorMessage = '';
        
        // Cek jika jumlahnya cocok (toleransi 1 rupiah)
        if(Math.abs(parseFloat(rawValue) - currentTotalDitagih) > 0.01) {
            isValid = false;
            errorMessage = 'Jumlah bayar (Rp ' + formatRupiah(rawValue) + ') tidak cocok dengan total ditagih (Rp ' + formatRupiah(currentTotalDitagih) + ').';
        }
        
        if(parseFloat(rawValue) <= 0 && currentTotalDitagih > 0.01) {
             isValid = false;
             errorMessage = 'Jumlah bayar tidak boleh 0.';
        }
        
        if(currentTotalDitagih <= 0.01 && parseFloat(rawValue) > 0) {
             isValid = false;
             errorMessage = 'Jumlah bayar harus 0 jika lunas dengan saldo.';
        }

        manualAmountError.textContent = errorMessage;
        manualAmountError.classList.toggle('d-none', !isValid);
        manualSubmitBtn.disabled = !isValid;
    }
    manualAmountDisplay.addEventListener('keyup', validateManualForm);
    manualAmountDisplay.addEventListener('change', validateManualForm);

    btnManual.addEventListener('click', function() {
        manualTitle.textContent = 'Upload Bukti Transfer Batch';
        
        // ✅ PERBAIKAN: Set value ke ID
        manualMethodInput.value = transferMethodId; 
        
        manualCashFields.classList.add('d-none');
        manualTransferFields.classList.remove('d-none');
        manualProofInput.required = (currentTotalDitagih > 0);
        manualSalesSelect.required = false;
        
        manualTotalBayar.textContent = formatRupiah(currentTotalPaymentValue);
        manualKreditDipakai.textContent = formatRupiah(currentKreditDigunakan);
        manualSisaBayar.textContent = formatRupiah(currentTotalDitagih);
        manualAutoNumeric.set(currentTotalDitagih);
        manualUseCreditHidden.value = (useCreditCheck && useCreditCheck.checked) ? '1' : '0';
        
        validateManualForm();
        batchManualModal.show();
    });

    btnCash.addEventListener('click', function() {
        manualTitle.textContent = 'Lapor Bayar Tunai Batch';
        
        // ✅ PERBAIKAN: Set value ke ID
        manualMethodInput.value = cashMethodId; 
        
        manualTransferFields.classList.add('d-none');
        manualCashFields.classList.remove('d-none');
        manualSalesSelect.required = true;
        manualProofInput.required = false;

        manualTotalBayar.textContent = formatRupiah(currentTotalPaymentValue);
        manualKreditDipakai.textContent = formatRupiah(currentKreditDigunakan);
        manualSisaBayar.textContent = formatRupiah(currentTotalDitagih);
        manualAutoNumeric.set(currentTotalDitagih);
        manualUseCreditHidden.value = (useCreditCheck && useCreditCheck.checked) ? '1' : '0';

        validateManualForm();
        batchManualModal.show();
    });
    
    manualForm.addEventListener('submit', function() {
        manualForm.querySelectorAll('input[name="invoice_ids[]"]').forEach(el => el.remove());
        checkboxes.forEach(cb => {
            if (cb.checked) {
                const hiddenInput = document.createElement('input');
                hiddenInput.type = 'hidden';
                hiddenInput.name = 'invoice_ids[]';
                hiddenInput.value = cb.value;
                manualForm.appendChild(hiddenInput);
            }
        });
    });


    // === Logika Modal Midtrans ===
    btnMidtrans.addEventListener('click', function() {
        document.getElementById('midtrans-summary-total-bayar').textContent = formatRupiah(currentTotalPaymentValue);
        document.getElementById('midtrans-summary-kredit').textContent = formatRupiah(currentKreditDigunakan);
        document.getElementById('midtrans-summary-ditagih').textContent = formatRupiah(currentTotalDitagih);
        
        if (currentTotalDitagih <= 0.01 && currentKreditDigunakan > 0) {
             document.getElementById('midtrans-summary-ditagih').textContent += " (Lunas dengan Saldo)";
        }
        
        batchMidtransModal.show();
    });
    
    midtransForm.addEventListener('submit', function(event) {
        event.preventDefault();
        
        midtransForm.querySelectorAll('input[name="invoice_ids[]"]').forEach(el => el.remove());
        midtransForm.querySelectorAll('input[name="use_credit"]').forEach(el => el.remove());
        midtransForm.querySelectorAll('input[name="amount"]').forEach(el => el.remove());

        checkboxes.forEach(cb => {
            if (cb.checked) {
                const hiddenInput = document.createElement('input');
                hiddenInput.type = 'hidden';
                hiddenInput.name = 'invoice_ids[]';
                hiddenInput.value = cb.value;
                midtransForm.appendChild(hiddenInput);
            }
        });
        
        const creditInput = document.createElement('input');
        creditInput.type = 'hidden';
        creditInput.name = 'use_credit';
        creditInput.value = (useCreditCheck && useCreditCheck.checked) ? '1' : '0';
        midtransForm.appendChild(creditInput);
        
        const amountInput = document.createElement('input');
        amountInput.type = 'hidden';
        amountInput.name = 'amount';
        amountInput.value = currentAmountFromInput;
        midtransForm.appendChild(amountInput);

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
                window.location.href = redirectUrl + '?payment_success=1&batch=1';
                return;
            }

            if (data.snap_token) {
                window.snap.pay(data.snap_token, {
                    onSuccess: function(result){ window.location.href = redirectUrl + '?payment_success=1&batch=1'; },
                    onPending: function(result){ window.location.href = redirectUrl + '?payment_pending=1&batch=1'; },
                    onError: function(result){ 
                        alert("Pembayaran gagal!");
                        payButton.disabled = false;
                        payButton.innerHTML = 'Lanjutkan ke Pembayaran';
                    },
                    onClose: function(){
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

});
</script>
@endpush
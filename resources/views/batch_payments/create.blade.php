@extends('layouts.app')

@push('styles')
<link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/select2-bootstrap-5-theme@1.3.0/dist/select2-bootstrap-5-theme.min.css" />
@endpush

@php
    // Ambil default dari collection yang benar
    $defaultPaymentMethodId = $paymentMethods->first()->payment_method_id ?? '';
    $defaultBankAccountId = $companyBankAccounts->first()->company_bank_account_id ?? '';
@endphp

@section('content')
<div class="container py-4">
    <div class="row justify-content-center">
        <div class="col-lg-10">
            <div class="card shadow-sm border-0">
                <div class="card-header bg-success text-white">
                    <h4 class="mb-0">Catat Pembayaran Piutang (Batch) 💰</h4>
                </div>
                <div class="card-body p-4">

                    @if ($errors->any() || session('error'))
                    <div class="alert alert-danger">
                        <ul class="mb-0">
                            @foreach ($errors->all() as $error)<li>{{ $error }}</li>@endforeach
                            @if (session('error'))<li>{{ session('error') }}</li>@endif
                        </ul>
                    </div>
                    @endif

                    <form action="{{ route('batch-payments.store') }}" method="POST" id="batch-payment-form" enctype="multipart/form-data">
                        @csrf
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label for="client_id" class="form-label fw-semibold">1. Pilih Klien</label>
                                <select name="client_id" id="client_id" class="form-select" required>
                                    <option value="" disabled selected>-- Cari Klien --</option>
                                    @foreach ($clients as $client)
                                    <option value="{{ $client->client_id }}">{{ $client->client_name }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-6 align-self-end">
                                <div id="client-credit-info" class="alert alert-info py-2 d-none">
                                    Saldo Kredit Tersedia: <strong id="client-credit-balance">Rp 0</strong>
                                    <br>
                                    <small class="text-muted">Kredit Tertahan: <strong id="client-pending-balance">Rp 0</strong></small>
                                </div>
                            </div>
                        </div>

                        <hr>

                        <h5 class="fw-semibold">2. Detail Pembayaran</h5>
                        <div class="row mb-3 g-3">
                            <div class="col-12 mb-2">
                                <div class="form-check form-switch" id="use-credit-container" style="display: none;">
                                    <input class="form-check-input" type="checkbox" role="switch" id="use_credit" name="use_credit" value="1">
                                    <label class="form-check-label" for="use_credit">Gunakan Saldo Kredit untuk pembayaran ini?</label>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <label for="total_amount_formatted" class="form-label">Total Dana Diterima (Non-Kredit)</label>
                                <input type="text" class="form-control" id="total_amount_formatted">
                                <input type="hidden" name="total_amount" id="total_amount" value="0">
                            </div>
                            <div class="col-md-4">
                                <label for="payment_date" class="form-label">Tanggal Bayar</label>
                                <input type="date" class="form-control" name="payment_date" value="{{ now()->format('Y-m-d') }}" required>
                            </div>
                            
                            <div class="col-md-4">
                                <label for="payment_method_id" class="form-label">Metode Bayar (Non-Kredit)</label>
                                <select name="payment_method_id" id="payment_method_id" class="form-select">
                                    <option value="">-- Pilih Metode --</option>
                                    @foreach ($paymentMethods as $method)
                                    <option value="{{ $method->payment_method_id }}" 
                                            data-config="{{ $method->required_fields_config }}">
                                        {{ $method->name }}
                                    </option>
                                    @endforeach
                                </select>
                            </div>

                            <div class="col-md-4">
                                <label for="company_bank_account_id_batch_payment" class="form-label">Masuk ke Akun <span class="text-danger">*</span></label>
                                <select name="company_bank_account_id" id="company_bank_account_id_batch_payment" class="form-select">
                                    <option value="">-- Pilih Akun Bank/Kas --</option>
                                    @foreach($companyBankAccounts as $account)
                                        <option value="{{ $account->company_bank_account_id }}">
                                            {{ $account->bank_name }} - {{ $account->account_number ?? $account->account_name }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>

                            <div class="col-md-4" id="payment-reference-group-batch-payment" style="display: none;">
                                <label for="reference_number_batch_payment" class="form-label">Nomor Referensi (Giro/Cek)</label>
                                <input type="text" class="form-control" name="reference_number" id="reference_number_batch_payment">
                            </div>
                            <div class="col-md-4" id="payment-proof-group-batch-payment" style="display: none;">
                                <label for="proof_of_payment_batch_payment" class="form-label">Bukti Pembayaran (Foto)</label>
                                <input type="file" class="form-control" name="proof_of_payment" id="proof_of_payment_batch_payment" accept="image/jpeg,image/png,image/jpg">
                            </div>
                            
                            <div class="col-md-4">
                                <label for="notes" class="form-label">Catatan (Opsional)</label>
                                <textarea name="notes" class="form-control" rows="2"></textarea>
                            </div>
                        </div>

                        <hr>

                        <h5 class="fw-semibold">3. Alokasi ke Invoice (Diurutkan dari paling lama)</h5>
                        <p class="text-muted small">Pilih Invoice yang akan dibayar. Sistem akan melunasi Invoice dari urutan teratas (paling lama) terlebih dahulu.</p>
                        <div id="invoice-list-container" class="border rounded p-3" style="max-height: 400px; overflow-y: auto; background-color: #f8f9fa;">
                            <p class="text-center text-muted" id="invoice-placeholder">Silakan pilih klien untuk melihat daftar Invoice.</p>
                            <table class="table table-sm table-hover d-none" id="invoice-table">
                                <thead>
                                    <tr>
                                        <th><input type="checkbox" id="check-all-invoices" class="form-check-input"></th>
                                        <th>No. Invoice</th>
                                        <th>Jatuh Tempo</th>
                                        <th class="text-end">Sisa Tagihan</th>
                                    </tr>
                                </thead>
                                <tbody id="invoice-list-body">
                                </tbody>
                            </table>
                        </div>

                        <div class="d-flex justify-content-between align-items-center bg-light p-3 mt-3 rounded">
                            <h5 class="mb-0">Total Tagihan Dipilih: <span id="total-selected-display" class="fw-bold text-success">Rp 0</span></h5>
                            <button type="submit" class="btn btn-success btn-lg">Simpan Pembayaran Piutang</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/autonumeric@4.6.0/dist/autoNumeric.min.js"></script>

<script>
document.addEventListener('DOMContentLoaded', function () {
    // === BAGIAN 1: Inisialisasi Variabel ===
    const clientSelect = $('#client_id');
    const invoiceTable = document.getElementById('invoice-table');
    const invoiceListBody = document.getElementById('invoice-list-body');
    const invoicePlaceholder = document.getElementById('invoice-placeholder');
    const checkAll = document.getElementById('check-all-invoices');
    const totalSelectedDisplay = document.getElementById('total-selected-display');
    const amountFormattedInput = document.getElementById('total_amount_formatted');
    const amountHiddenInput = document.getElementById('total_amount');
    const creditInfoDiv = document.getElementById('client-credit-info');
    const creditBalanceSpan = document.getElementById('client-credit-balance');
    const pendingBalanceSpan = document.getElementById('client-pending-balance');
    const useCreditContainer = document.getElementById('use-credit-container');
    const useCreditCheckbox = document.getElementById('use_credit');
    
    const paymentMethodSelect = document.getElementById('payment_method_id');
    const bankAccountSelect = document.getElementById('company_bank_account_id_batch_payment');
    
    const referenceGroup = document.getElementById('payment-reference-group-batch-payment');
    const referenceInput = document.getElementById('reference_number_batch_payment');
    const proofGroup = document.getElementById('payment-proof-group-batch-payment');
    const proofInput = document.getElementById('proof_of_payment_batch_payment');
    
    let currentCreditBalance = 0;
    
    const defaultPaymentMethodId = "{{ $defaultPaymentMethodId }}";
    const defaultBankAccountId = "{{ $defaultBankAccountId }}";

    // === BAGIAN 2: Inisialisasi Event Listener & AutoNumeric ===
    
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
    
    const autoNumericInstance = new AutoNumeric(amountFormattedInput, {
        digitGroupSeparator: '.',
        decimalCharacter: ',',
        decimalCharacterAlternative: '.',
        decimalPlaces: 0,
        minimumValue: 0
    });

    amountFormattedInput.addEventListener('autoNumeric:rawValueModified', (e) => {
        amountHiddenInput.value = e.detail.newRawValue || 0;
        toggleRequiredFields();
    });

    clientSelect.select2({
        theme: 'bootstrap-5',
        placeholder: '-- Cari Klien --'
    });

    // === BAGIAN 3: Fungsi Helper ===

    function formatRupiah(number) {
        if (isNaN(number)) return 'Rp 0';
        return new Intl.NumberFormat('id-ID', {
            style: 'currency', 
            currency: 'IDR', 
            minimumFractionDigits: 0,
            maximumFractionDigits: 2
        }).format(number);
    }

    function calculateTotalSelected() {
        let total = 0;
        document.querySelectorAll('.invoice-checkbox:checked').forEach(checkbox => {
            total += parseFloat(checkbox.dataset.balance || 0);
        });
        totalSelectedDisplay.textContent = formatRupiah(total);
        toggleRequiredFields();
    }

    function toggleRequiredFields() {
        const selectedInvoiceBalanceString = totalSelectedDisplay.textContent || 'Rp 0';
        const selectedInvoiceBalance = parseFloat(selectedInvoiceBalanceString.replace(/[^0-9,-]+/g,"").replace(",", ".")) || 0;

        const useCreditIsChecked = useCreditCheckbox.checked;
        const inputAmountValue = parseFloat(amountHiddenInput.value || 0);
        const creditIsSufficient = currentCreditBalance >= selectedInvoiceBalance && selectedInvoiceBalance > 0;

        if (useCreditIsChecked) {
            if (creditIsSufficient) {
                if (!amountFormattedInput.disabled) {
                     autoNumericInstance.set(0);
                }
                amountFormattedInput.required = false;
                amountFormattedInput.disabled = true;
                paymentMethodSelect.required = false;
                paymentMethodSelect.disabled = true;
                paymentMethodSelect.value = "";

                bankAccountSelect.required = false;
                bankAccountSelect.disabled = true;
                bankAccountSelect.value = "";
            } else {
                amountFormattedInput.required = true;
                amountFormattedInput.disabled = false;
                paymentMethodSelect.required = true;
                paymentMethodSelect.disabled = false;
                if (!paymentMethodSelect.value) paymentMethodSelect.value = defaultPaymentMethodId;
                
                bankAccountSelect.required = true;
                bankAccountSelect.disabled = false;
                if (!bankAccountSelect.value) bankAccountSelect.value = defaultBankAccountId;
            }
        } else {
            amountFormattedInput.required = true;
            amountFormattedInput.disabled = false;
            
            const isAmountPositive = inputAmountValue > 0;
            
            paymentMethodSelect.required = isAmountPositive;
            paymentMethodSelect.disabled = false;
            bankAccountSelect.required = isAmountPositive;
            bankAccountSelect.disabled = false;
            
            if (isAmountPositive) {
                if (!paymentMethodSelect.value) paymentMethodSelect.value = defaultPaymentMethodId;
                if (!bankAccountSelect.value) bankAccountSelect.value = defaultBankAccountId;
            } else {
                 paymentMethodSelect.value = "";
                 bankAccountSelect.value = "";
            }
        }
        
        handlePaymentMethodChange();
    }

    if(useCreditCheckbox) {
        useCreditCheckbox.addEventListener('change', toggleRequiredFields);
    }

    checkAll.addEventListener('change', function () {
        document.querySelectorAll('.invoice-checkbox').forEach(checkbox => {
            checkbox.checked = this.checked;
        });
        calculateTotalSelected();
    });

    function addInvoiceCheckboxListeners() {
        document.querySelectorAll('.invoice-checkbox').forEach(cb => {
            cb.removeEventListener('change', handleInvoiceCheckboxChange);
            cb.addEventListener('change', handleInvoiceCheckboxChange);
        });
    }

    function handleInvoiceCheckboxChange() {
        calculateTotalSelected();
    }

    // === BAGIAN 4: Handler Utama (onChange Select Klien) ===

    clientSelect.on('change', async function () {
        const clientId = this.value;

        creditInfoDiv.classList.add('d-none');
        useCreditContainer.style.display = 'none';
        useCreditCheckbox.checked = false;
        currentCreditBalance = 0;
        invoicePlaceholder.textContent = 'Memuat data...';
        invoiceTable.classList.add('d-none');
        invoiceListBody.innerHTML = '';
        checkAll.checked = false;
        autoNumericInstance.set(0);

        if (!clientId) {
            invoicePlaceholder.textContent = 'Silakan pilih klien untuk melihat daftar Invoice.';
            calculateTotalSelected();
            toggleRequiredFields();
            return;
        }

        try {
            const clientResponse = await fetch(`/api/clients/${clientId}/details`);
            if (!clientResponse.ok) throw new Error('Gagal ambil data klien');
            const clientData = await clientResponse.json();
            
            currentCreditBalance = parseFloat(clientData.balance) || 0;
            const pendingBalance = parseFloat(clientData.pending_balance) || 0;

            creditBalanceSpan.textContent = formatRupiah(currentCreditBalance);
            pendingBalanceSpan.textContent = formatRupiah(pendingBalance);
            creditInfoDiv.classList.remove('d-none');

            if (currentCreditBalance > 0) {
                useCreditContainer.style.display = 'block';
            }

            const invoiceResponse = await fetch(`/api/clients/${clientId}/unpaid-invoices`);
            if (!invoiceResponse.ok) throw new Error('Gagal mengambil data Invoice');
            const invoices = await invoiceResponse.json();

            if (invoices.length === 0) {
                invoicePlaceholder.textContent = 'Klien ini tidak memiliki tagihan Invoice.';
            } else {
                invoices.forEach(invoice => {
                    const row = `
                        <tr>
                            <td>
                                <input class="form-check-input invoice-checkbox"
                                       type="checkbox"
                                       name="invoice_ids[]"
                                       value="${invoice.invoice_id}"
                                       data-balance="${invoice.sisa_tagihan}">
                            </td>
                            <td>${invoice.invoice_number}</td>
                            <td>${invoice.due_date_formatted}</td>
                            <td class="text-end">${formatRupiah(invoice.sisa_tagihan)}</td>
                        </tr>
                    `;
                    invoiceListBody.insertAdjacentHTML('beforeend', row);
                });
                invoicePlaceholder.textContent = '';
                invoiceTable.classList.remove('d-none');
                addInvoiceCheckboxListeners();
            }

        } catch (error) {
            invoicePlaceholder.textContent = 'Gagal memuat data. Silakan coba lagi.';
            console.error('Error fetching data:', error);
            creditInfoDiv.classList.add('d-none');
            useCreditContainer.style.display = 'none';
        }

        calculateTotalSelected();
        toggleRequiredFields();
    });

    toggleRequiredFields();
});
</script>
@endpush
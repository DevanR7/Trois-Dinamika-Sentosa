@extends('layouts.app')

@push('styles')
<link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/select2-bootstrap-5-theme@1.3.0/dist/select2-bootstrap-5-theme.min.css" />
@endpush

@php
    $defaultPaymentMethodId = $paymentMethods->first()->payment_method_id ?? '';
    $defaultBankAccountId = $companyBankAccounts->first()->company_bank_account_id ?? '';
@endphp

@section('content')
<div class="container-fluid py-2">
    {{-- HEADER --}}
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h3 class="fw-bold text-dark mb-1">Pembayaran Piutang (Batch)</h3>
            <p class="text-muted mb-0 small">Catat satu pembayaran untuk beberapa invoice sekaligus.</p>
        </div>
        <div>
            <a href="{{ route('invoices.index') }}" class="btn btn-outline-secondary btn-sm">
                <i class="bi bi-arrow-left me-1"></i> Kembali ke List Invoice
            </a>
        </div>
    </div>

    <div class="row justify-content-center">
        <div class="col-lg-10">
            
            @if ($errors->any() || session('error'))
                <div class="alert alert-danger border-0 shadow-sm rounded-3 mb-4">
                    <div class="d-flex align-items-center">
                        <i class="bi bi-exclamation-triangle-fill fs-4 me-3"></i>
                        <ul class="mb-0 small ps-3">
                            @foreach ($errors->all() as $error)<li>{{ $error }}</li>@endforeach
                            @if (session('error'))<li>{{ session('error') }}</li>@endif
                        </ul>
                    </div>
                </div>
            @endif

            <form action="{{ route('batch-payments.store') }}" method="POST" id="batch-payment-form" enctype="multipart/form-data">
                @csrf

                {{-- 1. PILIH KLIEN --}}
                <div class="card card-transaction border-0 shadow-sm mb-4">
                    <div class="card-header bg-white p-4 border-bottom">
                        <div class="form-section-title mb-0"><i class="bi bi-person-check"></i> 1. Pilih Klien</div>
                    </div>
                    <div class="card-body p-4">
                        <div class="row align-items-center">
                            <div class="col-md-6">
                                <label for="client_id" class="form-label fw-bold small text-muted">NAMA KLIEN</label>
                                <select name="client_id" id="client_id" class="form-select" required>
                                    <option value="" disabled selected>-- Cari Klien --</option>
                                    @foreach ($clients as $client)
                                        <option value="{{ $client->client_id }}">{{ $client->client_name }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-6 mt-3 mt-md-0">
                                {{-- Info Saldo Kredit (Hidden by default) --}}
                                <div id="client-credit-info" class="alert alert-success border-0 bg-success bg-opacity-10 d-none mb-0">
                                    <div class="d-flex align-items-center">
                                        <div class="me-3 text-success">
                                            <i class="bi bi-wallet2 fs-2"></i>
                                        </div>
                                        <div>
                                            <span class="d-block text-muted small">Saldo Kredit Tersedia</span>
                                            <strong class="fs-5 text-dark" id="client-credit-balance">Rp 0</strong>
                                            <div class="small text-muted fst-italic border-top border-success border-opacity-25 mt-1 pt-1">
                                                Pending / Tertahan: <span id="client-pending-balance">Rp 0</span>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- 2. DETAIL PEMBAYARAN --}}
                <div class="card card-transaction border-0 shadow-sm mb-4">
                    <div class="card-header bg-white p-4 border-bottom">
                        <div class="form-section-title mb-0"><i class="bi bi-credit-card"></i> 2. Detail Pembayaran</div>
                    </div>
                    <div class="card-body p-4">
                        
                        {{-- Switch Kredit --}}
                        <div class="mb-4 p-3 bg-light rounded border" id="use-credit-container" style="display: none;">
                            <div class="form-check form-switch">
                                <input class="form-check-input cursor-pointer" type="checkbox" role="switch" id="use_credit" name="use_credit" value="1" style="transform: scale(1.2);">
                                <label class="form-check-label fw-bold ms-2 pt-1 cursor-pointer" for="use_credit">
                                    Gunakan Saldo Kredit untuk pembayaran ini?
                                </label>
                            </div>
                        </div>

                        <div class="row g-4">
                            {{-- Baris 1 --}}
                            <div class="col-md-6">
                                <label for="total_amount_formatted" class="form-label fw-bold small text-muted">TOTAL DANA DITERIMA (NON-KREDIT)</label>
                                <div class="input-group">
                                    <span class="input-group-text bg-white text-muted">Rp</span>
                                    <input type="text" class="form-control fw-bold fs-5 text-success" id="total_amount_formatted">
                                </div>
                                <input type="hidden" name="total_amount" id="total_amount" value="0">
                            </div>
                            <div class="col-md-6">
                                <label for="payment_date" class="form-label fw-bold small text-muted">TANGGAL BAYAR</label>
                                <input type="date" class="form-control" name="payment_date" value="{{ now()->format('Y-m-d') }}" required>
                            </div>

                            {{-- Baris 2 --}}
                            <div class="col-md-6">
                                <label for="payment_method_id" class="form-label fw-bold small text-muted">METODE BAYAR</label>
                                <select name="payment_method_id" id="payment_method_id" class="form-select">
                                    <option value="">-- Pilih Metode --</option>
                                    @foreach ($paymentMethods as $method)
                                        <option value="{{ $method->payment_method_id }}" data-config="{{ $method->required_fields_config }}">
                                            {{ $method->name }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label for="company_bank_account_id_batch_payment" class="form-label fw-bold small text-muted">MASUK KE AKUN (KAS/BANK)</label>
                                <select name="company_bank_account_id" id="company_bank_account_id_batch_payment" class="form-select">
                                    <option value="">-- Pilih Akun --</option>
                                    @foreach($companyBankAccounts as $account)
                                        <option value="{{ $account->company_bank_account_id }}">
                                            {{ $account->bank_name }} - {{ $account->account_number ?? $account->account_name }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>

                            {{-- Baris 3 (Conditional) --}}
                            <div class="col-md-6" id="payment-reference-group-batch-payment" style="display: none;">
                                <label for="reference_number_batch_payment" class="form-label fw-bold small text-muted">NOMOR REFERENSI</label>
                                <input type="text" class="form-control" name="reference_number" id="reference_number_batch_payment" placeholder="No. Cek / Giro / Transfer">
                            </div>
                            <div class="col-md-6" id="payment-proof-group-batch-payment" style="display: none;">
                                <label for="proof_of_payment_batch_payment" class="form-label fw-bold small text-muted">BUKTI TRANSFER</label>
                                <input type="file" class="form-control" name="proof_of_payment" id="proof_of_payment_batch_payment" accept="image/jpeg,image/png,image/jpg">
                            </div>

                            <div class="col-12">
                                <label for="notes" class="form-label fw-bold small text-muted">CATATAN (OPSIONAL)</label>
                                <textarea name="notes" class="form-control" rows="2" placeholder="Tambahkan keterangan pembayaran..."></textarea>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- 3. ALOKASI INVOICE --}}
                <div class="card card-transaction border-0 shadow-sm mb-5">
                    <div class="card-header bg-white p-4 border-bottom">
                        <div class="form-section-title mb-0"><i class="bi bi-list-check"></i> 3. Alokasi ke Invoice</div>
                        <p class="text-muted small mt-1 mb-0">Sistem akan melunasi Invoice secara berurutan mulai dari yang paling lama jatuh temponya.</p>
                    </div>
                    <div class="card-body p-0">
                        
                        <div id="invoice-list-container" class="p-0" style="max-height: 500px; overflow-y: auto;">
                            {{-- Placeholder State --}}
                            <div id="invoice-placeholder" class="text-center py-5 text-muted">
                                <i class="bi bi-search fs-1 d-block mb-2 opacity-25"></i>
                                Silakan pilih klien di atas untuk memuat tagihan.
                            </div>

                            {{-- Table --}}
                            <table class="table table-hover table-transaction align-middle mb-0 d-none" id="invoice-table">
                                <thead class="bg-light sticky-top" style="z-index: 1;">
                                    <tr>
                                        <th class="ps-4" style="width: 50px;">
                                            <input type="checkbox" id="check-all-invoices" class="form-check-input cursor-pointer" style="transform: scale(1.1);">
                                        </th>
                                        <th>No. Invoice</th>
                                        <th>Jatuh Tempo</th>
                                        <th class="text-end pe-4">Sisa Tagihan</th>
                                    </tr>
                                </thead>
                                <tbody id="invoice-list-body">
                                    {{-- JS will render rows here --}}
                                </tbody>
                            </table>
                        </div>

                        {{-- Total Bar (Sticky Bottom) --}}
                        <div class="bg-light p-4 border-top d-flex justify-content-between align-items-center rounded-bottom">
                            <div>
                                <small class="text-muted d-block text-uppercase fw-bold">Total Tagihan Dipilih</small>
                                <h3 class="fw-bold text-dark mb-0" id="total-selected-display">Rp 0</h3>
                            </div>
                            <button type="submit" class="btn btn-success btn-lg shadow-sm px-5 fw-bold">
                                <i class="bi bi-check-lg me-2"></i> Simpan Pembayaran
                            </button>
                        </div>

                    </div>
                </div>

            </form>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/autonumeric@4.6.0/dist/autoNumeric.min.js"></script>

<script>
document.addEventListener('DOMContentLoaded', function () {
    // (SCRIPT ANDA TIDAK BERUBAH - SAMA PERSIS)
    // Saya paste ulang agar Anda mudah copy-paste satu file penuh
    
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

    clientSelect.on('change', async function () {
        const clientId = this.value;

        creditInfoDiv.classList.add('d-none');
        useCreditContainer.style.display = 'none';
        useCreditCheckbox.checked = false;
        currentCreditBalance = 0;
        
        invoicePlaceholder.innerHTML = '<div class="spinner-border text-primary" role="status"></div><div class="mt-2">Memuat data...</div>';
        invoicePlaceholder.classList.remove('d-none');
        
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
                            <td class="ps-4">
                                <input class="form-check-input invoice-checkbox cursor-pointer"
                                       type="checkbox"
                                       name="invoice_ids[]"
                                       value="${invoice.invoice_id}"
                                       data-balance="${invoice.sisa_tagihan}"
                                       style="transform: scale(1.1);">
                            </td>
                            <td class="fw-bold text-primary">${invoice.invoice_number}</td>
                            <td>${invoice.due_date_formatted}</td>
                            <td class="text-end pe-4 fw-semibold">${formatRupiah(invoice.sisa_tagihan)}</td>
                        </tr>
                    `;
                    invoiceListBody.insertAdjacentHTML('beforeend', row);
                });
                invoicePlaceholder.classList.add('d-none');
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
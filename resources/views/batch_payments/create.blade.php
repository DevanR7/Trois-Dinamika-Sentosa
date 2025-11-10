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
<div class="container py-4">
    <div class="row justify-content-center">
        <div class="col-lg-10">
            <div class="card shadow-sm border-0">
                <div class="card-header bg-danger text-white">
                    <h4 class="mb-0">Catat Pembayaran Hutang (Batch) 💸</h4>
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

                    <form action="{{ route('batch-purchase-payments.store') }}" method="POST" id="batch-payment-form" enctype="multipart/form-data">
                        @csrf
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label for="supplier_id" class="form-label fw-semibold">1. Pilih Supplier</label>
                                <select name="supplier_id" id="supplier_id" class="form-select" required>
                                    <option value="" disabled selected>-- Cari Supplier --</option>
                                    @foreach ($suppliers as $supplier)
                                    <option value="{{ $supplier->supplier_id }}">{{ $supplier->supplier_name }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-6 align-self-end">
                                <div id="supplier-debit-info" class="alert alert-info py-2 d-none">
                                    Saldo Tersedia: <strong id="supplier-debit-balance">Rp 0</strong>
                                    <br>
                                    <small class="text-muted">Saldo Tertahan: <strong id="supplier-pending-balance">Rp 0</strong></small>
                                </div>
                            </div>
                        </div>

                        <hr>

                        <h5 class="fw-semibold">2. Detail Pembayaran</h5>
                        <div class="row mb-3 g-3">
                            <div class="col-12 mb-2">
                                <div class="form-check form-switch" id="use-debit-container" style="display: none;">
                                    <input class="form-check-input" type="checkbox" role="switch" id="use_debit_balance" name="use_debit_balance" value="1">
                                    <label class="form-check-label" for="use_debit_balance">Gunakan Saldo Deposit untuk pembayaran ini?</label>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <label for="total_amount_formatted" class="form-label">Total Dana Dibayar (Non-Deposit)</label>
                                <input type="text" class="form-control" id="total_amount_formatted">
                                <input type="hidden" name="total_amount" id="total_amount" value="0">
                            </div>
                            <div class="col-md-4">
                                <label for="payment_date" class="form-label">Tanggal Bayar</label>
                                <input type="date" class="form-control" name="payment_date" value="{{ now()->format('Y-m-d') }}" required>
                            </div>
                            
                            <div class="col-md-4">
                                <label for="payment_method_id" class="form-label">Metode Bayar (Non-Deposit)</label>
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
                                <label for="company_bank_account_id_batch_purchase" class="form-label">Keluar dari Akun <span class="text-danger">*</span></label>
                                <select name="company_bank_account_id" id="company_bank_account_id_batch_purchase" class="form-select">
                                    <option value="">-- Pilih Akun Bank/Kas --</option>
                                    @foreach($companyBankAccounts as $account)
                                        <option value="{{ $account->company_bank_account_id }}">
                                            {{ $account->bank_name }} - {{ $account->account_number ?? $account->account_name }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>

                            <div class="col-md-4" id="payment-reference-group-batch-purchase" style="display: none;">
                                <label for="reference_number_batch_purchase" class="form-label">Nomor Referensi (Giro/Cek)</label>
                                <input type="text" class="form-control" name="reference_number" id="reference_number_batch_purchase">
                            </div>
                            <div class="col-md-4" id="payment-proof-group-batch-purchase" style="display: none;">
                                <label for="proof_of_payment_batch_purchase" class="form-label">Bukti Pembayaran (Foto)</label>
                                <input type="file" class="form-control" name="proof_of_payment" id="proof_of_payment_batch_purchase" accept="image/jpeg,image/png,image/jpg">
                            </div>
                            
                            <div class="col-md-4">
                                <label for="notes" class="form-label">Catatan (Opsional)</label>
                                <textarea name="notes" class="form-control" rows="2"></textarea>
                            </div>
                        </div>

                        <hr>

                        <h5 class="fw-semibold">3. Alokasi ke Purchase Order (Diurutkan dari paling lama)</h5>
                        <p class="text-muted small">Pilih PO yang akan dibayar. Sistem akan melunasi PO dari urutan teratas (paling lama) terlebih dahulu.</p>
                        <div id="po-list-container" class="border rounded p-3" style="max-height: 400px; overflow-y: auto; background-color: #f8f9fa;">
                            <p class="text-center text-muted" id="po-placeholder">Silakan pilih supplier untuk melihat daftar PO.</p>
                            <table class="table table-sm table-hover d-none" id="po-table">
                                <thead>
                                    <tr>
                                        <th><input type="checkbox" id="check-all-pos" class="form-check-input"></th>
                                        <th>No. Purchase Order</th>
                                        <th>Jatuh Tempo</th>
                                        <th class="text-end">Sisa Tagihan</th>
                                    </tr>
                                </thead>
                                <tbody id="po-list-body">
                                </tbody>
                            </table>
                        </div>

                        <div class="d-flex justify-content-between align-items-center bg-light p-3 mt-3 rounded">
                            <h5 class="mb-0">Total Tagihan Dipilih: <span id="total-selected-display" class="fw-bold text-danger">Rp 0</span></h5>
                            <button type="submit" class="btn btn-danger btn-lg">Simpan Pembayaran Hutang</button>
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
    const supplierSelect = $('#supplier_id');
    const poTable = document.getElementById('po-table');
    const poListBody = document.getElementById('po-list-body');
    const poPlaceholder = document.getElementById('po-placeholder');
    const checkAll = document.getElementById('check-all-pos');
    const totalSelectedDisplay = document.getElementById('total-selected-display');
    const amountFormattedInput = document.getElementById('total_amount_formatted');
    const amountHiddenInput = document.getElementById('total_amount');
    const debitInfoDiv = document.getElementById('supplier-debit-info');
    const debitBalanceSpan = document.getElementById('supplier-debit-balance');
    const pendingBalanceSpan = document.getElementById('supplier-pending-balance');
    const useDebitContainer = document.getElementById('use-debit-container');
    const useDebitCheckbox = document.getElementById('use_debit_balance');
    
    const paymentMethodSelect = document.getElementById('payment_method_id');
    const bankAccountSelect = document.getElementById('company_bank_account_id_batch_purchase');
    
    const referenceGroup = document.getElementById('payment-reference-group-batch-purchase');
    const referenceInput = document.getElementById('reference_number_batch_purchase');
    const proofGroup = document.getElementById('payment-proof-group-batch-purchase');
    const proofInput = document.getElementById('proof_of_payment_batch_purchase');
    
    let currentDebitBalance = 0;
    
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

    supplierSelect.select2({
        theme: 'bootstrap-5',
        placeholder: '-- Cari Supplier --'
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
        document.querySelectorAll('.po-checkbox:checked').forEach(checkbox => {
            total += parseFloat(checkbox.dataset.balance || 0);
        });
        totalSelectedDisplay.textContent = formatRupiah(total);
        toggleRequiredFields();
    }

    function toggleRequiredFields() {
        const selectedPOBalanceString = totalSelectedDisplay.textContent || 'Rp 0';
        const selectedPOBalance = parseFloat(selectedPOBalanceString.replace(/[^0-9,-]+/g,"").replace(",", ".")) || 0;

        const useDebitIsChecked = useDebitCheckbox.checked;
        const inputAmountValue = parseFloat(amountHiddenInput.value || 0);
        const debitIsSufficient = currentDebitBalance >= selectedPOBalance && selectedPOBalance > 0;

        if (useDebitIsChecked) {
            if (debitIsSufficient) {
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

    if(useDebitCheckbox) {
        useDebitCheckbox.addEventListener('change', toggleRequiredFields);
    }

    checkAll.addEventListener('change', function () {
        document.querySelectorAll('.po-checkbox').forEach(checkbox => {
            checkbox.checked = this.checked;
        });
        calculateTotalSelected();
    });

    function addPOCheckboxListeners() {
        document.querySelectorAll('.po-checkbox').forEach(cb => {
            cb.removeEventListener('change', handlePOCheckboxChange);
            cb.addEventListener('change', handlePOCheckboxChange);
        });
    }

    function handlePOCheckboxChange() {
        calculateTotalSelected();
    }

    supplierSelect.on('change', async function () {
        const supplierId = this.value;

        debitInfoDiv.classList.add('d-none');
        useDebitContainer.style.display = 'none';
        useDebitCheckbox.checked = false;
        currentDebitBalance = 0;
        poPlaceholder.textContent = 'Memuat data...';
        poTable.classList.add('d-none');
        poListBody.innerHTML = '';
        checkAll.checked = false;
        autoNumericInstance.set(0);

        if (!supplierId) {
            poPlaceholder.textContent = 'Silakan pilih supplier untuk melihat daftar PO.';
            calculateTotalSelected();
            toggleRequiredFields();
            return;
        }

        try {
            const supplierResponse = await fetch(`/api/suppliers/${supplierId}/details`);
            if (!supplierResponse.ok) throw new Error('Gagal ambil data supplier');
            const supplierData = await supplierResponse.json();
            
            currentDebitBalance = parseFloat(supplierData.balance) || 0;
            const pendingBalance = parseFloat(supplierData.pending_balance) || 0;

            debitBalanceSpan.textContent = formatRupiah(currentDebitBalance);
            pendingBalanceSpan.textContent = formatRupiah(pendingBalance);
            debitInfoDiv.classList.remove('d-none');

            if (currentDebitBalance > 0) {
                useDebitContainer.style.display = 'block';
            }

            const poResponse = await fetch(`/api/suppliers/${supplierId}/unpaid-purchase-orders`);
            if (!poResponse.ok) throw new Error('Gagal mengambil data PO');
            const pos = await poResponse.json();

            if (pos.length === 0) {
                poPlaceholder.textContent = 'Supplier ini tidak memiliki tagihan PO.';
            } else {
                pos.forEach(po => {
                    const row = `
                        <tr>
                            <td>
                                <input class="form-check-input po-checkbox"
                                       type="checkbox"
                                       name="po_ids[]"
                                       value="${po.po_id}"
                                       data-balance="${po.sisa_tagihan}">
                            </td>
                            <td>${po.po_number}</td>
                            <td>${po.due_date_formatted}</td>
                            <td class="text-end">${formatRupiah(po.sisa_tagihan)}</td>
                        </tr>
                    `;
                    poListBody.insertAdjacentHTML('beforeend', row);
                });
                poPlaceholder.textContent = '';
                poTable.classList.remove('d-none');
                addPOCheckboxListeners();
            }

        } catch (error) {
            poPlaceholder.textContent = 'Gagal memuat data. Silakan coba lagi.';
            console.error('Error fetching data:', error);
            debitInfoDiv.classList.add('d-none');
            useDebitContainer.style.display = 'none';
        }

        calculateTotalSelected();
        toggleRequiredFields();
    });

    toggleRequiredFields();
});
</script>
@endpush
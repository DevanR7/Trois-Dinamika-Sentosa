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
    
    {{-- HEADER HALAMAN --}}
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h3 class="fw-bold text-dark mb-1">Pembayaran Hutang (Batch)</h3>
            <p class="text-muted mb-0 small">Catat pembayaran untuk beberapa PO sekaligus ke satu supplier.</p>
        </div>
        <div>
            <a href="{{ route('purchase-orders.index') }}" class="btn btn-outline-secondary btn-sm">
                <i class="bi bi-arrow-left me-1"></i> Kembali ke List PO
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

            <form action="{{ route('batch-purchase-payments.store') }}" method="POST" id="batch-payment-form" enctype="multipart/form-data">
                @csrf

                {{-- 1. PILIH SUPPLIER --}}
                <div class="card card-transaction border-0 shadow-sm mb-4">
                    <div class="card-header bg-white p-4 border-bottom">
                        <div class="form-section-title mb-0"><i class="bi bi-people"></i> 1. Pilih Supplier</div>
                    </div>
                    <div class="card-body p-4">
                        <div class="row align-items-center">
                            <div class="col-md-6">
                                <label for="supplier_id" class="form-label fw-bold small text-muted">NAMA SUPPLIER</label>
                                <select name="supplier_id" id="supplier_id" class="form-select" required>
                                    <option value="" disabled selected>-- Cari Supplier --</option>
                                    @foreach ($suppliers as $supplier)
                                        <option value="{{ $supplier->supplier_id }}">{{ $supplier->supplier_name }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-6 mt-3 mt-md-0">
                                {{-- Info Saldo Deposit (Hidden by default) --}}
                                <div id="supplier-debit-info" class="alert alert-info border-0 bg-info bg-opacity-10 d-none mb-0">
                                    <div class="d-flex align-items-center">
                                        <div class="me-3 text-info">
                                            <i class="bi bi-wallet2 fs-2"></i>
                                        </div>
                                        <div>
                                            <span class="d-block text-muted small">Saldo Deposit Tersedia</span>
                                            <strong class="fs-5 text-dark" id="supplier-debit-balance">Rp 0</strong>
                                            <div class="small text-muted fst-italic border-top border-info border-opacity-25 mt-1 pt-1">
                                                Pending / Tertahan: <span id="supplier-pending-balance">Rp 0</span>
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
                        
                        {{-- Switch Deposit --}}
                        <div class="mb-4 p-3 bg-light rounded border" id="use-debit-container" style="display: none;">
                            <div class="form-check form-switch">
                                <input class="form-check-input cursor-pointer" type="checkbox" role="switch" id="use_debit_balance" name="use_debit_balance" value="1" style="transform: scale(1.2);">
                                <label class="form-check-label fw-bold ms-2 pt-1 cursor-pointer" for="use_debit_balance">
                                    Gunakan Saldo Deposit untuk pembayaran ini?
                                </label>
                            </div>
                        </div>

                        <div class="row g-4">
                            {{-- Baris 1 --}}
                            <div class="col-md-6">
                                <label for="total_amount_formatted" class="form-label fw-bold small text-muted">TOTAL DANA KELUAR (NON-DEPOSIT)</label>
                                <div class="input-group">
                                    <span class="input-group-text bg-white text-muted">Rp</span>
                                    <input type="text" class="form-control fw-bold fs-5 text-primary" id="total_amount_formatted" placeholder="0">
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
                                <label for="company_bank_account_id_batch_purchase" class="form-label fw-bold small text-muted">SUMBER DANA (AKUN KAS/BANK)</label>
                                <select name="company_bank_account_id" id="company_bank_account_id_batch_purchase" class="form-select">
                                    <option value="">-- Pilih Akun --</option>
                                    @foreach($companyBankAccounts as $account)
                                        <option value="{{ $account->company_bank_account_id }}">
                                            {{ $account->bank_name }} - {{ $account->account_number ?? $account->account_name }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>

                            {{-- Baris 3 (Conditional) --}}
                            <div class="col-md-6" id="payment-reference-group-batch-purchase" style="display: none;">
                                <label for="reference_number_batch_purchase" class="form-label fw-bold small text-muted">NOMOR REFERENSI</label>
                                <input type="text" class="form-control" name="reference_number" id="reference_number_batch_purchase" placeholder="No. Cek / Giro / Transfer">
                            </div>
                            <div class="col-md-6" id="payment-proof-group-batch-purchase" style="display: none;">
                                <label for="proof_of_payment_batch_purchase" class="form-label fw-bold small text-muted">BUKTI TRANSFER</label>
                                <input type="file" class="form-control" name="proof_of_payment" id="proof_of_payment_batch_purchase" accept="image/jpeg,image/png,image/jpg">
                            </div>

                            <div class="col-12">
                                <label for="notes" class="form-label fw-bold small text-muted">CATATAN (OPSIONAL)</label>
                                <textarea name="notes" class="form-control" rows="2" placeholder="Tambahkan keterangan pembayaran..."></textarea>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- 3. ALOKASI PO --}}
                <div class="card card-transaction border-0 shadow-sm mb-5">
                    <div class="card-header bg-white p-4 border-bottom">
                        <div class="form-section-title mb-0"><i class="bi bi-list-check"></i> 3. Alokasi ke Purchase Order</div>
                        <p class="text-muted small mt-1 mb-0">Sistem akan melunasi PO secara berurutan mulai dari yang paling lama jatuh temponya.</p>
                    </div>
                    <div class="card-body p-0">
                        
                        <div id="po-list-container" class="p-0" style="max-height: 500px; overflow-y: auto;">
                            {{-- Placeholder State --}}
                            <div id="po-placeholder" class="text-center py-5 text-muted">
                                <i class="bi bi-search fs-1 d-block mb-2 opacity-25"></i>
                                Silakan pilih supplier di atas untuk memuat tagihan.
                            </div>

                            {{-- Table --}}
                            <table class="table table-hover table-transaction align-middle mb-0 d-none" id="po-table">
                                <thead class="bg-light sticky-top" style="z-index: 1;">
                                    <tr>
                                        <th class="ps-4" style="width: 50px;">
                                            <input type="checkbox" id="check-all-pos" class="form-check-input cursor-pointer" style="transform: scale(1.1);">
                                        </th>
                                        <th>No. Purchase Order</th>
                                        <th>Jatuh Tempo</th>
                                        <th class="text-end pe-4">Sisa Tagihan</th>
                                    </tr>
                                </thead>
                                <tbody id="po-list-body">
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
                            <button type="submit" class="btn btn-primary btn-lg shadow-sm px-5 fw-bold">
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
        
        poPlaceholder.innerHTML = '<div class="spinner-border text-primary" role="status"></div><div class="mt-2">Memuat data...</div>';
        poPlaceholder.classList.remove('d-none');
        
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
                            <td class="ps-4">
                                <input class="form-check-input po-checkbox cursor-pointer"
                                       type="checkbox"
                                       name="po_ids[]"
                                       value="${po.po_id}"
                                       data-balance="${po.sisa_tagihan}"
                                       style="transform: scale(1.1);">
                            </td>
                            <td class="fw-bold text-primary">${po.po_number}</td>
                            <td>${po.due_date_formatted}</td>
                            <td class="text-end pe-4 fw-semibold">${formatRupiah(po.sisa_tagihan)}</td>
                        </tr>
                    `;
                    poListBody.insertAdjacentHTML('beforeend', row);
                });
                poPlaceholder.classList.add('d-none');
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
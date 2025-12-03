@extends('admin.layouts.app')

@section('title', 'Pembayaran Hutang Massal (Bulk)')

@php
    $defaultPaymentMethodId = $paymentMethods->first()->payment_method_id ?? '';
    $defaultBankAccountId = $companyBankAccounts->first()->company_bank_account_id ?? '';
@endphp

@section('content')
<div class="max-w-7xl mx-auto pb-20 animate-enter">
    
    {{-- HEADER --}}
    <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center mb-8 gap-4">
        <div>
            <nav class="flex text-sm text-slate-500 mb-1">
                <a href="{{ route('admin.purchase-orders.index') }}" class="hover:text-indigo-600 transition-colors">Pembelian</a>
                <span class="mx-2 text-slate-300">/</span>
                <span class="text-slate-800 font-semibold">Bulk Payment</span>
            </nav>
            <h1 class="text-2xl font-bold text-slate-800 tracking-tight">Pembayaran Hutang Massal</h1>
            <p class="text-slate-500 text-sm mt-1">Catat satu pembayaran untuk melunasi beberapa PO sekaligus.</p>
        </div>
        <a href="{{ route('admin.purchase-orders.index') }}" class="h-[48px] px-6 bg-white border border-slate-300 text-slate-600 rounded-lg text-sm font-bold hover:bg-slate-50 transition-all flex items-center justify-center gap-2 shadow-sm">
            <i class="material-icons text-[18px]">arrow_back</i> Kembali ke List
        </a>
    </div>

    {{-- ALERT ERROR --}}
    @if ($errors->any() || session('error'))
        <div class="mb-6 bg-red-50 border-l-4 border-red-500 p-4 rounded-md shadow-sm animate-enter">
            <div class="flex items-start gap-3">
                <i class="material-icons text-red-600 text-xl mt-0.5">error_outline</i>
                <div>
                    <h3 class="text-sm font-bold text-red-800">Terdapat kesalahan</h3>
                    <ul class="mt-1 list-disc list-inside text-xs text-red-600">
                        @foreach ($errors->all() as $error) <li>{{ $error }}</li> @endforeach
                        @if (session('error')) <li>{{ session('error') }}</li> @endif
                    </ul>
                </div>
            </div>
        </div>
    @endif

    <form action="{{ route('admin.bulk-purchase-payments.store') }}" method="POST" id="bulk-payment-form" enctype="multipart/form-data">
        @csrf

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
            
            {{-- KOLOM KIRI (FORM UTAMA) --}}
            <div class="lg:col-span-2 space-y-8">
                
                {{-- CARD 1: PILIH SUPPLIER --}}
                <div class="dashboard-card p-0 overflow-hidden shadow-sm">
                    <div class="px-6 py-4 border-b border-slate-100 bg-slate-50/50 flex items-center gap-3">
                        <div class="p-2 bg-indigo-50 rounded-lg text-indigo-600">
                            <i class="material-icons text-[20px]">person_search</i>
                        </div>
                        <h3 class="text-sm font-bold text-slate-700 uppercase tracking-wide">1. Pilih Supplier</h3>
                    </div>
                    
                    <div class="p-6">
                        <label for="supplier_id" class="block text-xs font-bold text-slate-500 uppercase mb-1.5">Nama Supplier <span class="text-red-500">*</span></label>
                        <select name="supplier_id" id="supplier_id" class="form-input" style="width: 100%" required>
                            <option value="" disabled selected></option>
                            @foreach ($suppliers as $supplier)
                                <option value="{{ $supplier->supplier_id }}">{{ $supplier->supplier_name }}</option>
                            @endforeach
                        </select>

                        {{-- Info Saldo Deposit (Hidden by default) --}}
                        <div id="supplier-debit-info" class="mt-6 bg-emerald-50 border border-emerald-100 rounded-xl p-4 hidden animate-enter">
                            <div class="flex items-center gap-4">
                                <div class="p-3 bg-white rounded-full text-emerald-600 shadow-sm border border-emerald-100">
                                    <i class="material-icons text-2xl">account_balance_wallet</i>
                                </div>
                                <div>
                                    <span class="block text-xs font-bold text-emerald-800 uppercase tracking-wide">Saldo Deposit Tersedia</span>
                                    <strong class="text-xl text-emerald-700 font-mono tracking-tight" id="supplier-debit-balance">Rp 0</strong>
                                    <p class="text-[10px] text-emerald-600 mt-0.5">Pending verifikasi: <span id="supplier-pending-balance" class="font-bold">Rp 0</span></p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- CARD 2: DETAIL PEMBAYARAN --}}
                <div class="dashboard-card p-0 overflow-hidden shadow-sm">
                    <div class="px-6 py-4 border-b border-slate-100 bg-slate-50/50 flex items-center gap-3">
                        <div class="p-2 bg-indigo-50 rounded-lg text-indigo-600">
                            <i class="material-icons text-[20px]">payments</i>
                        </div>
                        <h3 class="text-sm font-bold text-slate-700 uppercase tracking-wide">2. Detail Pembayaran</h3>
                    </div>

                    <div class="p-6 space-y-6">
                        {{-- Switch Deposit --}}
                        <div class="bg-slate-50 rounded-lg p-4 border border-slate-200 transition-all duration-300" id="use-debit-container" style="display: none;">
                            <label class="flex items-center cursor-pointer select-none group">
                                <div class="relative">
                                    <input type="checkbox" id="use_debit_balance" name="use_debit_balance" value="1" class="peer sr-only">
                                    <div class="w-11 h-6 bg-slate-300 peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-indigo-300 rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-indigo-600"></div>
                                </div>
                                <span class="ml-3 text-sm font-bold text-slate-700 group-hover:text-indigo-600 transition-colors">Potong dari Saldo Deposit?</span>
                            </label>
                            <p class="text-xs text-slate-500 mt-2 ml-14 leading-relaxed">Jika diaktifkan, pembayaran akan memotong saldo deposit supplier terlebih dahulu sebelum meminta input kas/bank.</p>
                        </div>

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            {{-- Total Amount --}}
                            <div>
                                <label for="total_amount_formatted" class="block text-xs font-bold text-slate-500 uppercase mb-1.5">Total Keluar (Kas/Bank)</label>
                                <div class="relative">
                                    <span class="absolute left-4 top-1/2 -translate-y-1/2 text-slate-400 text-sm font-bold">Rp</span>
                                    {{-- AutoNumeric Input (DISABLED DEFAULT) --}}
                                    <input type="text" id="total_amount_formatted" class="form-input pl-10 text-lg font-bold text-red-600 font-mono placeholder:text-slate-300 bg-slate-50 cursor-not-allowed" placeholder="0" disabled>
                                    {{-- Hidden Input --}}
                                    <input type="hidden" name="total_amount" id="total_amount" value="0">
                                </div>

                                {{-- ALERT OVERPAYMENT --}}
                                <div id="overpayment-alert" class="hidden mt-2 p-2 bg-blue-50 border border-blue-100 rounded-lg flex items-start gap-2">
                                    <i class="material-icons text-blue-500 text-sm mt-0.5">info</i>
                                    <p class="text-[11px] text-blue-700">
                                        Kelebihan bayar sebesar <span class="font-bold" id="overpayment-amount">Rp 0</span> akan disimpan sebagai Deposit Supplier.
                                    </p>
                                </div>
                            </div>

                            {{-- Tanggal Bayar --}}
                            <div>
                                <label for="payment_date" class="block text-xs font-bold text-slate-500 uppercase mb-1.5">Tanggal Bayar</label>
                                <input type="date" name="payment_date" id="payment_date" value="{{ now()->format('Y-m-d') }}" class="form-input" required>
                            </div>

                            {{-- Metode Bayar --}}
                            <div>
                                <label for="payment_method_id" class="block text-xs font-bold text-slate-500 uppercase mb-1.5">Metode Bayar</label>
                                <select name="payment_method_id" id="payment_method_id" class="form-input" style="width: 100%" disabled>
                                    <option value="">-- Pilih Metode --</option>
                                    @foreach ($paymentMethods as $method)
                                        <option value="{{ $method->payment_method_id }}" data-config="{{ $method->required_fields_config }}">
                                            {{ $method->name }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>

                            {{-- Akun Bank --}}
                            <div>
                                <label for="company_bank_account_id" class="block text-xs font-bold text-slate-500 uppercase mb-1.5">Sumber Dana (Kas/Bank)</label>
                                <select name="company_bank_account_id" id="company_bank_account_id" class="form-input" style="width: 100%" disabled>
                                    <option value="">-- Pilih Akun --</option>
                                    @foreach($companyBankAccounts as $account)
                                        <option value="{{ $account->company_bank_account_id }}">
                                            {{ $account->bank_name }} - {{ $account->account_number ?? $account->account_name }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>

                            {{-- Conditional Fields --}}
                            <div class="md:col-span-1 hidden animate-enter" id="payment-reference-group">
                                <label for="reference_number" class="block text-xs font-bold text-slate-500 uppercase mb-1.5">Nomor Referensi</label>
                                <input type="text" name="reference_number" id="reference_number" class="form-input" placeholder="No. Cek / Giro / Transfer">
                            </div>

                            <div class="md:col-span-1 hidden animate-enter" id="payment-proof-group">
                                <label for="proof_of_payment" class="block text-xs font-bold text-slate-500 uppercase mb-1.5">Bukti Transfer</label>
                                <input type="file" name="proof_of_payment" id="proof_of_payment" class="block w-full text-sm text-slate-500 file:mr-4 file:py-2 file:px-4 file:rounded-full file:border-0 file:text-xs file:font-bold file:bg-indigo-50 file:text-indigo-700 hover:file:bg-indigo-100 transition cursor-pointer" accept="image/jpeg,image/png,image/jpg">
                            </div>

                            {{-- Notes --}}
                            <div class="md:col-span-2">
                                <label for="notes" class="block text-xs font-bold text-slate-500 uppercase mb-1.5">Catatan (Opsional)</label>
                                <textarea name="notes" id="notes" rows="2" class="form-textarea" placeholder="Tambahkan keterangan pembayaran..."></textarea>
                            </div>
                        </div>
                    </div>
                </div>

            </div>

            {{-- KOLOM KANAN (PO ALLOCATION) --}}
            <div class="lg:col-span-1">
                <div class="dashboard-card p-0 overflow-hidden shadow-lg border-0 ring-1 ring-slate-900/5 flex flex-col h-[600px] sticky top-6">
                    
                    {{-- Header List PO --}}
                    <div class="p-4 border-b border-slate-200 bg-slate-50/80 rounded-t-xl">
                        <div class="flex items-center gap-2 mb-1">
                            <div class="bg-indigo-100 p-1.5 rounded text-indigo-600">
                                <i class="material-icons text-[18px]">playlist_add_check</i>
                            </div>
                            <h3 class="text-sm font-bold text-slate-800 uppercase tracking-wide">3. Alokasi Tagihan</h3>
                        </div>
                        <p class="text-[10px] text-slate-500 ml-8">Sistem melunasi dari PO terlama.</p>
                    </div>

                    {{-- List Container --}}
                    <div class="flex-1 overflow-y-auto custom-scrollbar relative bg-white">
                        {{-- Placeholder --}}
                        <div id="po-placeholder" class="flex flex-col items-center justify-center h-full text-slate-400 p-8 text-center">
                            <i class="material-icons text-5xl mb-3 opacity-20">search</i>
                            <p class="text-sm font-medium">Silakan pilih supplier untuk memuat tagihan.</p>
                        </div>

                        {{-- Table --}}
                        <table class="min-w-full divide-y divide-slate-100 hidden" id="po-table">
                            <thead class="bg-slate-50 sticky top-0 z-10 shadow-sm">
                                <tr>
                                    <th scope="col" class="px-4 py-3 text-center w-10 border-b border-slate-200">
                                        <input type="checkbox" id="check-all-pos" class="rounded border-slate-300 text-indigo-600 focus:ring-indigo-500 cursor-pointer">
                                    </th>
                                    <th scope="col" class="px-2 py-3 text-left text-xs font-bold text-slate-500 uppercase border-b border-slate-200">No. PO</th>
                                    <th scope="col" class="px-4 py-3 text-right text-xs font-bold text-slate-500 uppercase border-b border-slate-200">Sisa</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-slate-50" id="po-list-body">
                                {{-- JS will render rows here --}}
                            </tbody>
                        </table>
                    </div>

                    {{-- Footer Action & Summary --}}
                    <div class="p-5 border-t border-slate-200 bg-slate-50">
                        
                        {{-- RINGKASAN KALKULASI --}}
                        <div class="space-y-2 mb-5 text-sm">
                            <div class="flex justify-between items-center">
                                <span class="text-slate-500 font-medium">Total Tagihan</span>
                                <span class="font-bold text-slate-800" id="summary-total-bill">Rp 0</span>
                            </div>
                            
                            {{-- Baris Potongan Deposit --}}
                            <div id="summary-deposit-row" class="flex justify-between items-center text-emerald-600 hidden animate-enter">
                                <span class="font-bold flex items-center gap-1"><i class="material-icons text-[14px]">account_balance_wallet</i> Potong Deposit</span>
                                <span class="font-bold font-mono" id="summary-deposit-amount">- Rp 0</span>
                            </div>

                            <div class="border-t border-slate-200 pt-2 mt-2 flex justify-between items-center">
                                <span class="text-xs font-bold text-slate-500 uppercase">Harus Dibayar (Cash)</span>
                                <span class="text-xl font-bold text-indigo-700 font-mono" id="total-selected-display">Rp 0</span>
                            </div>
                        </div>

                        <button type="submit" id="submit-btn" class="w-full h-[48px] bg-emerald-600 hover:bg-emerald-700 text-white text-sm font-bold rounded-lg shadow-md transition-all flex justify-center items-center gap-2 group hover:-translate-y-0.5 disabled:opacity-50 disabled:cursor-not-allowed">
                            <i class="material-icons text-[20px] group-hover:scale-110 transition-transform">check_circle</i> Simpan Pembayaran
                        </button>
                    </div>
                </div>
            </div>

        </div>
    </form>
</div>
@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/autonumeric@4.6.0/dist/autoNumeric.min.js"></script>

<script>
document.addEventListener('DOMContentLoaded', function () {
    // --- DOM ELEMENTS ---
    const supplierSelect = $('#supplier_id');
    const paymentMethodSelect = $('#payment_method_id');
    const bankAccountSelect = $('#company_bank_account_id');
    
    const poTable = document.getElementById('po-table');
    const poListBody = document.getElementById('po-list-body');
    const poPlaceholder = document.getElementById('po-placeholder');
    const checkAll = document.getElementById('check-all-pos');
    
    const amountFormattedInput = document.getElementById('total_amount_formatted');
    const amountHiddenInput = document.getElementById('total_amount');
    
    const debitInfoDiv = document.getElementById('supplier-debit-info');
    const debitBalanceSpan = document.getElementById('supplier-debit-balance');
    const pendingBalanceSpan = document.getElementById('supplier-pending-balance');
    const useDebitContainer = document.getElementById('use-debit-container');
    const useDebitCheckbox = document.getElementById('use_debit_balance');
    
    const referenceGroup = document.getElementById('payment-reference-group');
    const referenceInput = document.getElementById('reference_number');
    const proofGroup = document.getElementById('payment-proof-group');
    const proofInput = document.getElementById('proof_of_payment');

    // Elements Overpayment Alert
    const overpaymentAlert = document.getElementById('overpayment-alert');
    const overpaymentAmountSpan = document.getElementById('overpayment-amount');
    
    const totalSelectedDisplay = document.getElementById('total-selected-display');
    const displayTotalBill = document.getElementById('summary-total-bill');
    const displayDepositUsed = document.getElementById('summary-deposit-amount');
    const summaryDepositRow = document.getElementById('summary-deposit-row');
    
    let currentDebitBalance = 0;
    const defaultPaymentMethodId = "{{ $defaultPaymentMethodId }}";
    const defaultBankAccountId = "{{ $defaultBankAccountId }}";

    // --- 1. INIT SELECT2 ---
    supplierSelect.select2({ placeholder: '-- Cari Supplier --', allowClear: true, width: '100%', dropdownCssClass: 'select2-dropdown-clean' });
    paymentMethodSelect.select2({ placeholder: '-- Pilih Metode --', width: '100%', dropdownCssClass: 'select2-dropdown-clean' });
    bankAccountSelect.select2({ placeholder: '-- Pilih Akun --', width: '100%', dropdownCssClass: 'select2-dropdown-clean' });

    // --- 2. INIT AUTONUMERIC ---
    const autoNumericInstance = new AutoNumeric(amountFormattedInput, {
        digitGroupSeparator: '.', decimalCharacter: ',', decimalPlaces: 0, minimumValue: 0, emptyInputBehavior: 'zero', unformatOnSubmit: false
    });

    amountFormattedInput.addEventListener('autoNumeric:rawValueModified', (e) => {
        amountHiddenInput.value = e.detail.newRawValue || 0;
        // User sedang ngetik, jadi jangan update input otomatis
        calculateTotals(false); 
    });

    // --- 3. HELPER FORMAT ---
    function formatRupiah(number) {
        return new Intl.NumberFormat('id-ID', { style: 'currency', currency: 'IDR', minimumFractionDigits: 0, maximumFractionDigits: 2 }).format(number);
    }

    // --- 4. PAYMENT METHOD LOGIC ---
    function handlePaymentMethodChange() {
        const selectedData = paymentMethodSelect.select2('data')[0];
        const config = selectedData ? $(selectedData.element).data('config') : 'none';

        referenceGroup.classList.add('hidden'); referenceInput.required = false;
        proofGroup.classList.add('hidden'); proofInput.required = false;

        if (config === 'proof_only') {
            proofGroup.classList.remove('hidden'); proofInput.required = true;
        } else if (config === 'reference_only') {
            referenceGroup.classList.remove('hidden'); referenceInput.required = true;
        } else if (config === 'proof_and_reference') {
            proofGroup.classList.remove('hidden'); proofInput.required = true;
            referenceGroup.classList.remove('hidden'); referenceInput.required = true;
        }
    }
    paymentMethodSelect.on('change', handlePaymentMethodChange);

    // --- 5. TOGGLE INPUT STATE ---
    function toggleInputState(hasSelection, isFullPaidByDeposit) {
        if (!hasSelection) {
            // Belum ada PO dipilih -> DISABLE SEMUA
            amountFormattedInput.classList.add('bg-slate-100', 'cursor-not-allowed');
            amountFormattedInput.disabled = true;
            
            paymentMethodSelect.prop('disabled', true).val(null).trigger('change');
            bankAccountSelect.prop('disabled', true).val(null).trigger('change');
            
            autoNumericInstance.set(0);
            amountHiddenInput.value = 0;
        } 
        else if (isFullPaidByDeposit) {
            // Lunas pakai deposit -> DISABLE Payment Method
            amountFormattedInput.classList.add('bg-slate-100', 'cursor-not-allowed');
            amountFormattedInput.disabled = true;
            
            paymentMethodSelect.prop('disabled', true).val(null).trigger('change');
            bankAccountSelect.prop('disabled', true).val(null).trigger('change');
            
            autoNumericInstance.set(0);
            amountHiddenInput.value = 0;
        } 
        else {
            // Normal -> ENABLE
            amountFormattedInput.classList.remove('bg-slate-100', 'cursor-not-allowed');
            amountFormattedInput.disabled = false;
            
            paymentMethodSelect.prop('disabled', false);
            if(!paymentMethodSelect.val()) paymentMethodSelect.val(defaultPaymentMethodId).trigger('change');
            
            bankAccountSelect.prop('disabled', false);
            if(!bankAccountSelect.val()) bankAccountSelect.val(defaultBankAccountId).trigger('change');
        }
    }

    // --- 6. MAIN CALCULATION ---
    function calculateTotals(updateInput = true) {
        // 1. Hitung Total Tagihan
        let totalBill = 0;
        document.querySelectorAll('.po-checkbox:checked').forEach(checkbox => {
            totalBill += parseFloat(checkbox.dataset.balance || 0);
        });

        // Update UI Summary
        displayTotalBill.textContent = formatRupiah(totalBill);

        // 2. Hitung Deposit
        const useDebitIsChecked = useDebitCheckbox.checked;
        let depositUsed = 0;
        let cashToPay = totalBill;

        if (useDebitIsChecked && currentDebitBalance > 0) {
            depositUsed = Math.min(totalBill, currentDebitBalance);
            cashToPay = Math.max(0, totalBill - depositUsed);

            summaryDepositRow.classList.remove('hidden');
            displayDepositUsed.textContent = '- ' + formatRupiah(depositUsed);
        } else {
            summaryDepositRow.classList.add('hidden');
        }

        // Update Total Akhir
        totalSelectedDisplay.textContent = formatRupiah(cashToPay);

        // 3. State Management
        const hasSelection = totalBill > 0;
        const isFullPaidByDeposit = (hasSelection && cashToPay <= 0);

        if (updateInput) {
            toggleInputState(hasSelection, isFullPaidByDeposit);
            if (!isFullPaidByDeposit && hasSelection) {
                autoNumericInstance.set(cashToPay);
                amountHiddenInput.value = cashToPay;
            }
        }

        // 4. Overpayment Check
        const currentUserInput = parseFloat(amountHiddenInput.value) || 0;
        const totalPayment = currentUserInput + depositUsed;
        
        if (hasSelection && totalPayment > totalBill) {
            const excess = totalPayment - totalBill;
            overpaymentAlert.classList.remove('hidden');
            overpaymentAmountSpan.textContent = formatRupiah(excess);
        } else {
            overpaymentAlert.classList.add('hidden');
        }
    }

    // --- 7. EVENT LISTENERS ---
    useDebitCheckbox.addEventListener('change', () => calculateTotals(true));

    checkAll.addEventListener('change', function () {
        const isChecked = this.checked;
        document.querySelectorAll('.po-checkbox').forEach(cb => cb.checked = isChecked);
        calculateTotals(true);
    });

    poListBody.addEventListener('change', function(e) {
        if (e.target.classList.contains('po-checkbox')) {
            calculateTotals(true);
        }
    });

    // --- 8. FETCH DATA ---
    supplierSelect.on('change', async function () {
        const supplierId = $(this).val();
        
        // Reset UI
        debitInfoDiv.classList.add('hidden');
        useDebitContainer.style.display = 'none';
        useDebitCheckbox.checked = false;
        currentDebitBalance = 0;
        
        poPlaceholder.innerHTML = '<div class="flex flex-col items-center py-8"><div class="animate-spin rounded-full h-8 w-8 border-b-2 border-indigo-600 mb-3"></div><span class="text-sm text-slate-500">Memuat data...</span></div>';
        poPlaceholder.classList.remove('hidden');
        poTable.classList.add('hidden');
        poListBody.innerHTML = '';
        checkAll.checked = false;
        autoNumericInstance.set(0);
        toggleInputState(false, false); // Disable

        if (!supplierId) return;

        try {
            let urlDetails = "{{ route('admin.api.suppliers.details', ':id') }}";
            urlDetails = urlDetails.replace(':id', supplierId);

            let urlPos = "{{ route('admin.api.suppliers.unpaid-pos', ':id') }}";
            urlPos = urlPos.replace(':id', supplierId);

            const [supplierRes, poRes] = await Promise.all([
                fetch(urlDetails),
                fetch(urlPos)
            ]);

            if (!supplierRes.ok || !poRes.ok) throw new Error('Gagal ambil data');

            const supplierData = await supplierRes.json();
            const pos = await poRes.json()
            
            currentDebitBalance = parseFloat(supplierData.balance) || 0;
            debitBalanceSpan.textContent = formatRupiah(currentDebitBalance);
            pendingBalanceSpan.textContent = formatRupiah(supplierData.pending_balance || 0);
            debitInfoDiv.classList.remove('hidden');

            if (currentDebitBalance > 0) {
                useDebitContainer.style.display = 'block';
            }

            if (pos.length === 0) {
                poPlaceholder.innerHTML = '<div class="flex flex-col items-center text-slate-400 mt-10"><i class="material-icons text-4xl mb-2 opacity-30">check_circle</i><p class="text-sm">Semua tagihan lunas.</p></div>';
            } else {
                pos.forEach(po => {
                    const row = `
                        <tr class="hover:bg-indigo-50/30 transition-colors">
                            <td class="px-4 py-3 text-center border-b border-slate-50">
                                <input class="po-checkbox rounded border-slate-300 text-indigo-600 shadow-sm focus:ring-indigo-500 cursor-pointer h-4 w-4"
                                       type="checkbox" name="po_ids[]" value="${po.po_id}" data-balance="${po.sisa_tagihan}">
                            </td>
                            <td class="px-2 py-3 text-sm border-b border-slate-50">
                                <div class="font-bold text-indigo-600 font-mono">${po.po_number}</div>
                                <div class="text-xs text-slate-500 mt-0.5">Due: ${po.due_date_formatted}</div>
                            </td>
                            <td class="px-4 py-3 text-right text-sm font-medium text-slate-800 border-b border-slate-50 font-mono">
                                ${formatRupiah(po.sisa_tagihan)}
                            </td>
                        </tr>
                    `;
                    poListBody.insertAdjacentHTML('beforeend', row);
                });
                poPlaceholder.classList.add('hidden');
                poTable.classList.remove('hidden');
            }

        } catch (error) {
            console.error(error);
            poPlaceholder.innerHTML = '<div class="text-red-500 font-bold text-sm mt-10">Gagal memuat data.</div>';
        }
        
        calculateTotals(true);
    });

    // --- 9. FORM SUBMIT ---
    const form = document.getElementById('bulk-payment-form');
    form.addEventListener('submit', function(e) {
        const checkedPOs = document.querySelectorAll('.po-checkbox:checked');
        if (checkedPOs.length === 0) {
            e.preventDefault();
            Swal.fire('Pilih PO', 'Harap centang minimal satu Purchase Order.', 'warning');
            return;
        }

        const submitBtn = document.getElementById('submit-btn');
        if (submitBtn) {
            submitBtn.disabled = true;
            submitBtn.innerHTML = '<i class="material-icons animate-spin text-sm">sync</i> Menyimpan...';
        }
    });
    
    // Init state
    toggleInputState(false, false);

    @if(session('success')) window.showToast("{{ session('success') }}", 'success'); @endif
    @if(session('error')) window.showToast("{{ session('error') }}", 'error'); @endif
});
</script>
@endpush
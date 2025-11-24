@extends('layouts.app')

@section('title', 'Pembayaran Piutang (Batch)')

@php
    $defaultPaymentMethodId = $paymentMethods->first()->payment_method_id ?? '';
    $defaultBankAccountId = $companyBankAccounts->first()->company_bank_account_id ?? '';
@endphp

@section('content')
<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-6">
    
    {{-- HEADER HALAMAN --}}
    <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center mb-6">
        <div>
            <div class="flex items-center gap-2 text-sm text-gray-500 mb-1">
                <a href="{{ route('invoices.index') }}" class="hover:text-indigo-600 transition">Invoice</a>
                <span>/</span>
                <span class="text-gray-800">Pembayaran Batch</span>
            </div>
            <h2 class="text-2xl font-bold text-gray-900 tracking-tight">Pembayaran Piutang (Batch)</h2>
            <p class="text-sm text-gray-500 mt-1">Catat satu pembayaran untuk melunasi beberapa invoice sekaligus.</p>
        </div>
        <div class="mt-4 sm:mt-0">
            <a href="{{ route('invoices.index') }}" class="inline-flex items-center px-4 py-2 bg-white border border-gray-300 rounded-lg font-medium text-gray-700 hover:bg-gray-50 shadow-sm transition text-sm">
                <i class="bi bi-arrow-left mr-2"></i> Kembali ke List
            </a>
        </div>
    </div>

    {{-- ALERT ERROR --}}
    @if ($errors->any() || session('error'))
        <div class="mb-6 bg-red-50 border border-red-200 rounded-lg p-4 flex items-start gap-3">
            <i class="bi bi-exclamation-triangle-fill text-red-500 mt-0.5 text-lg"></i>
            <div>
                <h3 class="text-sm font-bold text-red-800">Terdapat kesalahan</h3>
                <ul class="mt-1 list-disc list-inside text-sm text-red-700">
                    @foreach ($errors->all() as $error) <li>{{ $error }}</li> @endforeach
                    @if (session('error')) <li>{{ session('error') }}</li> @endif
                </ul>
            </div>
        </div>
    @endif

    <form action="{{ route('batch-payments.store') }}" method="POST" id="batch-payment-form" enctype="multipart/form-data">
        @csrf

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
            
            {{-- KOLOM KIRI (FORM UTAMA) --}}
            <div class="lg:col-span-2 space-y-6">
                
                {{-- CARD 1: PILIH KLIEN --}}
                <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
                    <div class="flex items-center gap-2 mb-4 pb-2 border-b border-gray-100">
                        <div class="bg-indigo-100 p-1.5 rounded text-indigo-600">
                            <i class="bi bi-person-check text-lg"></i>
                        </div>
                        <h3 class="text-base font-bold text-gray-900">1. Pilih Klien</h3>
                    </div>
                    
                    <div class="grid grid-cols-1 gap-4">
                        <div>
                            <label for="client_id" class="block text-sm font-medium text-gray-700 mb-1">Nama Klien <span class="text-red-500">*</span></label>
                            <select name="client_id" id="client_id" class="w-full" required>
                                <option value="" disabled selected>-- Cari Klien --</option>
                                @foreach ($clients as $client)
                                    <option value="{{ $client->client_id }}">{{ $client->client_name }}</option>
                                @endforeach
                            </select>
                        </div>

                        {{-- Info Saldo Kredit (Hidden by default) --}}
                        <div id="client-credit-info" class="bg-green-50 border border-green-200 rounded-lg p-4 hidden">
                            <div class="flex items-start gap-3">
                                <div class="p-2 bg-green-100 rounded-full text-green-600">
                                    <i class="bi bi-wallet2 text-xl"></i>
                                </div>
                                <div>
                                    <span class="block text-xs font-bold text-green-800 uppercase tracking-wide">Saldo Kredit Tersedia</span>
                                    <strong class="text-xl text-green-900" id="client-credit-balance">Rp 0</strong>
                                    <div class="mt-1 pt-1 border-t border-green-200 text-xs text-green-700">
                                        Pending / Tertahan: <span id="client-pending-balance" class="font-medium">Rp 0</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- CARD 2: DETAIL PEMBAYARAN --}}
                <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
                    <div class="flex items-center gap-2 mb-4 pb-2 border-b border-gray-100">
                        <div class="bg-indigo-100 p-1.5 rounded text-indigo-600">
                            <i class="bi bi-credit-card text-lg"></i>
                        </div>
                        <h3 class="text-base font-bold text-gray-900">2. Detail Pembayaran</h3>
                    </div>

                    <div class="space-y-6">
                        {{-- Switch Kredit --}}
                        <div class="bg-gray-50 rounded-lg p-3 border border-gray-200" id="use-credit-container" style="display: none;">
                            <div class="flex items-center">
                                <input type="checkbox" id="use_credit" name="use_credit" value="1" class="h-5 w-5 text-indigo-600 focus:ring-indigo-500 border-gray-300 rounded cursor-pointer">
                                <label for="use_credit" class="ml-3 block text-sm font-medium text-gray-900 cursor-pointer select-none">
                                    Gunakan Saldo Kredit untuk pembayaran ini?
                                </label>
                            </div>
                        </div>

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            {{-- Total Amount --}}
                            <div class="md:col-span-1">
                                <label for="total_amount_formatted" class="block text-xs font-bold text-gray-500 uppercase mb-1">Total Dana Diterima (Non-Kredit)</label>
                                <div class="relative rounded-md shadow-sm">
                                    <div class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3">
                                        <span class="text-gray-500 sm:text-sm">Rp</span>
                                    </div>
                                    <input type="text" id="total_amount_formatted" class="block w-full rounded-lg border-gray-300 pl-10 focus:border-indigo-500 focus:ring-indigo-500 text-lg font-bold text-green-600 placeholder-gray-300" placeholder="0">
                                    <input type="hidden" name="total_amount" id="total_amount" value="0">
                                </div>
                            </div>

                            {{-- Tanggal Bayar --}}
                            <div class="md:col-span-1">
                                <label for="payment_date" class="block text-xs font-bold text-gray-500 uppercase mb-1">Tanggal Bayar</label>
                                <input type="date" name="payment_date" id="payment_date" value="{{ now()->format('Y-m-d') }}" class="block w-full rounded-lg border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm" required>
                            </div>

                            {{-- Metode Bayar --}}
                            <div class="md:col-span-1">
                                <label for="payment_method_id" class="block text-xs font-bold text-gray-500 uppercase mb-1">Metode Bayar</label>
                                <select name="payment_method_id" id="payment_method_id" class="block w-full rounded-lg border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm form-select">
                                    <option value="">-- Pilih Metode --</option>
                                    @foreach ($paymentMethods as $method)
                                        <option value="{{ $method->payment_method_id }}" data-config="{{ $method->required_fields_config }}">
                                            {{ $method->name }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>

                            {{-- Akun Bank --}}
                            <div class="md:col-span-1">
                                <label for="company_bank_account_id_batch_payment" class="block text-xs font-bold text-gray-500 uppercase mb-1">Masuk ke Akun (Kas/Bank)</label>
                                <select name="company_bank_account_id" id="company_bank_account_id_batch_payment" class="block w-full rounded-lg border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm form-select">
                                    <option value="">-- Pilih Akun --</option>
                                    @foreach($companyBankAccounts as $account)
                                        <option value="{{ $account->company_bank_account_id }}">
                                            {{ $account->bank_name }} - {{ $account->account_number ?? $account->account_name }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>

                            {{-- Conditional Fields --}}
                            <div class="md:col-span-1" id="payment-reference-group-batch-payment" style="display: none;">
                                <label for="reference_number_batch_payment" class="block text-xs font-bold text-gray-500 uppercase mb-1">Nomor Referensi</label>
                                <input type="text" name="reference_number" id="reference_number_batch_payment" class="block w-full rounded-lg border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm" placeholder="No. Cek / Giro / Transfer">
                            </div>

                            <div class="md:col-span-1" id="payment-proof-group-batch-payment" style="display: none;">
                                <label for="proof_of_payment_batch_payment" class="block text-xs font-bold text-gray-500 uppercase mb-1">Bukti Transfer</label>
                                <input type="file" name="proof_of_payment" id="proof_of_payment_batch_payment" class="block w-full text-sm text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded-full file:border-0 file:text-sm file:font-semibold file:bg-indigo-50 file:text-indigo-700 hover:file:bg-indigo-100" accept="image/jpeg,image/png,image/jpg">
                            </div>

                            {{-- Notes --}}
                            <div class="md:col-span-2">
                                <label for="notes" class="block text-xs font-bold text-gray-500 uppercase mb-1">Catatan (Opsional)</label>
                                <textarea name="notes" id="notes" rows="2" class="block w-full rounded-lg border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm" placeholder="Tambahkan keterangan pembayaran..."></textarea>
                            </div>
                        </div>
                    </div>
                </div>

            </div>

            {{-- KOLOM KANAN (INVOICE ALLOCATION) --}}
            <div class="lg:col-span-1">
                <div class="bg-white rounded-xl shadow-sm border border-gray-200 flex flex-col h-[600px] sticky top-6">
                    <div class="p-4 border-b border-gray-200 bg-gray-50 rounded-t-xl">
                        <div class="flex items-center gap-2 mb-1">
                            <div class="bg-indigo-100 p-1 rounded text-indigo-600">
                                <i class="bi bi-list-check"></i>
                            </div>
                            <h3 class="text-sm font-bold text-gray-900 uppercase">3. Alokasi Invoice</h3>
                        </div>
                        <p class="text-xs text-gray-500">Sistem melunasi dari invoice terlama.</p>
                    </div>

                    {{-- List Container --}}
                    <div class="flex-1 overflow-y-auto invoice-scroll-area relative bg-white">
                        {{-- Placeholder State --}}
                        <div id="invoice-placeholder" class="flex flex-col items-center justify-center h-full text-gray-400 p-6 text-center">
                            <i class="bi bi-search text-4xl mb-2 opacity-30"></i>
                            <p class="text-sm">Silakan pilih klien untuk memuat tagihan.</p>
                        </div>

                        {{-- Table --}}
                        <table class="min-w-full divide-y divide-gray-200 hidden" id="invoice-table">
                            <thead class="bg-gray-50 sticky top-0 z-10">
                                <tr>
                                    <th scope="col" class="px-4 py-3 text-center w-10">
                                        <input type="checkbox" id="check-all-invoices" class="rounded border-gray-300 text-indigo-600 shadow-sm focus:ring-indigo-500 cursor-pointer">
                                    </th>
                                    <th scope="col" class="px-2 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Invoice</th>
                                    <th scope="col" class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Sisa</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200" id="invoice-list-body">
                                {{-- JS will render rows here --}}
                            </tbody>
                        </table>
                    </div>

                    {{-- Footer Action --}}
                    <div class="p-4 border-t border-gray-200 bg-gray-50 rounded-b-xl">
                        <div class="flex justify-between items-end mb-4">
                            <span class="text-xs font-bold text-gray-500 uppercase">Total Dipilih</span>
                            <span class="text-xl font-bold text-gray-900" id="total-selected-display">Rp 0</span>
                        </div>
                        <button type="submit" class="w-full py-3 px-4 border border-transparent rounded-lg shadow-sm text-sm font-bold text-white bg-green-600 hover:bg-green-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-green-500 flex justify-center items-center transition">
                            <i class="bi bi-check-lg mr-2 text-lg"></i> Simpan Pembayaran
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
    // --- VARIABEL & ELEMENT (LOGIKA SAMA PERSIS) ---
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

    // --- LOGIC 1: PAYMENT METHOD TOGGLE ---
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
    
    // --- LOGIC 2: AUTONUMERIC ---
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

    // --- LOGIC 3: CLIENT SELECT2 ---
    clientSelect.select2({
        theme: 'bootstrap-5',
        placeholder: '-- Cari Klien --',
        width: '100%'
    });

    // --- LOGIC 4: HELPER FORMAT ---
    function formatRupiah(number) {
        if (isNaN(number)) return 'Rp 0';
        return new Intl.NumberFormat('id-ID', {
            style: 'currency', 
            currency: 'IDR', 
            minimumFractionDigits: 0,
            maximumFractionDigits: 2
        }).format(number);
    }

    // --- LOGIC 5: CALCULATE TOTAL ---
    function calculateTotalSelected() {
        let total = 0;
        document.querySelectorAll('.invoice-checkbox:checked').forEach(checkbox => {
            total += parseFloat(checkbox.dataset.balance || 0);
        });
        totalSelectedDisplay.textContent = formatRupiah(total);
        toggleRequiredFields();
    }

    // --- LOGIC 6: TOGGLE REQUIRED (CREDIT LOGIC) ---
    function toggleRequiredFields() {
        const selectedInvoiceBalanceString = totalSelectedDisplay.textContent || 'Rp 0';
        const selectedInvoiceBalance = parseFloat(selectedInvoiceBalanceString.replace(/[^0-9,-]+/g,"").replace(",", ".")) || 0;

        const useCreditIsChecked = useCreditCheckbox.checked;
        const inputAmountValue = parseFloat(amountHiddenInput.value || 0);
        const creditIsSufficient = currentCreditBalance >= selectedInvoiceBalance && selectedInvoiceBalance > 0;

        if (useCreditIsChecked) {
            if (creditIsSufficient) {
                // FULL CREDIT
                if (!amountFormattedInput.disabled) {
                     autoNumericInstance.set(0);
                }
                amountFormattedInput.required = false;
                amountFormattedInput.disabled = true;
                // Disable Select styling via class (Optional, logic disabled attr is enough)
                amountFormattedInput.classList.add('bg-gray-100', 'cursor-not-allowed');

                paymentMethodSelect.required = false;
                paymentMethodSelect.disabled = true;
                paymentMethodSelect.value = "";
                
                bankAccountSelect.required = false;
                bankAccountSelect.disabled = true;
                bankAccountSelect.value = "";
            } else {
                // PARTIAL CREDIT (Not fully supported by UI flow yet, usually handled as normal payment)
                amountFormattedInput.required = true;
                amountFormattedInput.disabled = false;
                amountFormattedInput.classList.remove('bg-gray-100', 'cursor-not-allowed');

                paymentMethodSelect.required = true;
                paymentMethodSelect.disabled = false;
                if (!paymentMethodSelect.value) paymentMethodSelect.value = defaultPaymentMethodId;
                
                bankAccountSelect.required = true;
                bankAccountSelect.disabled = false;
                if (!bankAccountSelect.value) bankAccountSelect.value = defaultBankAccountId;
            }
        } else {
            // NORMAL PAYMENT
            amountFormattedInput.required = true;
            amountFormattedInput.disabled = false;
            amountFormattedInput.classList.remove('bg-gray-100', 'cursor-not-allowed');
            
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

    // --- LOGIC 7: TABLE INTERACTION ---
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

    // --- LOGIC 8: FETCH DATA ---
    clientSelect.on('change', async function () {
        const clientId = this.value;

        creditInfoDiv.classList.add('hidden'); // Tailwind class
        useCreditContainer.style.display = 'none';
        useCreditCheckbox.checked = false;
        currentCreditBalance = 0;
        
        invoicePlaceholder.innerHTML = '<div class="animate-spin rounded-full h-8 w-8 border-b-2 border-indigo-600 mb-2"></div><div class="text-sm text-gray-500">Memuat data...</div>';
        invoicePlaceholder.classList.remove('hidden');
        
        invoiceTable.classList.add('hidden');
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
            // Fetch Client Credit
            const clientResponse = await fetch(`/api/clients/${clientId}/details`);
            if (!clientResponse.ok) throw new Error('Gagal ambil data klien');
            const clientData = await clientResponse.json();
            
            currentCreditBalance = parseFloat(clientData.balance) || 0;
            const pendingBalance = parseFloat(clientData.pending_balance) || 0;

            creditBalanceSpan.textContent = formatRupiah(currentCreditBalance);
            pendingBalanceSpan.textContent = formatRupiah(pendingBalance);
            creditInfoDiv.classList.remove('hidden');

            if (currentCreditBalance > 0) {
                useCreditContainer.style.display = 'block';
            }

            // Fetch Invoices
            const invoiceResponse = await fetch(`/api/clients/${clientId}/unpaid-invoices`);
            if (!invoiceResponse.ok) throw new Error('Gagal mengambil data Invoice');
            const invoices = await invoiceResponse.json();

            if (invoices.length === 0) {
                invoicePlaceholder.innerHTML = '<i class="bi bi-check-circle text-4xl text-green-500 mb-2"></i><p class="text-sm text-gray-600">Semua invoice telah lunas.</p>';
            } else {
                invoices.forEach(invoice => {
                    // Styling Row with Tailwind
                    const row = `
                        <tr class="hover:bg-gray-50 transition-colors">
                            <td class="px-4 py-3 text-center">
                                <input class="invoice-checkbox rounded border-gray-300 text-indigo-600 shadow-sm focus:ring-indigo-500 cursor-pointer"
                                       type="checkbox"
                                       name="invoice_ids[]"
                                       value="${invoice.invoice_id}"
                                       data-balance="${invoice.sisa_tagihan}">
                            </td>
                            <td class="px-2 py-3 text-sm">
                                <div class="font-bold text-indigo-600">${invoice.invoice_number}</div>
                                <div class="text-xs text-gray-500">Due: ${invoice.due_date_formatted}</div>
                            </td>
                            <td class="px-4 py-3 text-right text-sm font-medium text-gray-900">${formatRupiah(invoice.sisa_tagihan)}</td>
                        </tr>
                    `;
                    invoiceListBody.insertAdjacentHTML('beforeend', row);
                });
                invoicePlaceholder.classList.add('hidden');
                invoiceTable.classList.remove('hidden');
                addInvoiceCheckboxListeners();
            }

        } catch (error) {
            invoicePlaceholder.textContent = 'Gagal memuat data. Silakan coba lagi.';
            console.error('Error fetching data:', error);
            creditInfoDiv.classList.add('hidden');
            useCreditContainer.style.display = 'none';
        }

        calculateTotalSelected();
        toggleRequiredFields();
    });

    toggleRequiredFields();
});
</script>
@endpush
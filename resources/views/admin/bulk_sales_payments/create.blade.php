@extends('admin.layouts.app')

@section('title', 'Pembayaran Massal (Bulk Payment)')

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
                <a href="{{ route('admin.invoices.index') }}" class="hover:text-indigo-600 transition-colors">Invoice</a>
                <span class="mx-2 text-slate-300">/</span>
                <span class="text-slate-800 font-semibold">Bulk Payment</span>
            </nav>
            <h1 class="text-2xl font-bold text-slate-800 tracking-tight">Penerimaan Pembayaran Massal</h1>
            <p class="text-slate-500 text-sm mt-1">Catat satu pembayaran untuk melunasi beberapa invoice sekaligus.</p>
        </div>
        <a href="{{ route('admin.invoices.index') }}" class="h-[48px] px-6 bg-white border border-slate-300 text-slate-600 rounded-lg text-sm font-bold hover:bg-slate-50 transition-all flex items-center justify-center gap-2 shadow-sm">
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

    <form action="{{ route('admin.bulk-sales-payments.store') }}" method="POST" id="bulk-payment-form" enctype="multipart/form-data">
        @csrf

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
            
            {{-- KOLOM KIRI (FORM UTAMA) --}}
            <div class="lg:col-span-2 space-y-8">
                
                {{-- CARD 1: PILIH KLIEN --}}
                <div class="dashboard-card p-0 overflow-hidden shadow-sm">
                    <div class="px-6 py-4 border-b border-slate-100 bg-slate-50/50 flex items-center gap-3">
                        <div class="p-2 bg-indigo-50 rounded-lg text-indigo-600">
                            <i class="material-icons text-[20px]">person_search</i>
                        </div>
                        <h3 class="text-sm font-bold text-slate-700 uppercase tracking-wide">1. Pilih Klien</h3>
                    </div>
                    
                    <div class="p-6">
                        <label for="client_id" class="block text-xs font-bold text-slate-500 uppercase mb-1.5">Nama Klien <span class="text-red-500">*</span></label>
                        <select name="client_id" id="client_id" class="form-input" style="width: 100%" required>
                            <option value="" disabled selected></option>
                            @foreach ($clients as $client)
                                <option value="{{ $client->client_id }}">{{ $client->client_name }}</option>
                            @endforeach
                        </select>

                        {{-- Info Saldo Kredit (Hidden by default) --}}
                        <div id="client-credit-info" class="mt-6 bg-emerald-50 border border-emerald-100 rounded-xl p-4 hidden transition-all duration-300">
                            <div class="flex items-center gap-4">
                                <div class="p-3 bg-white rounded-full text-emerald-600 shadow-sm">
                                    <i class="material-icons text-2xl">account_balance_wallet</i>
                                </div>
                                <div>
                                    <span class="block text-xs font-bold text-emerald-800 uppercase tracking-wide">Saldo Kredit Tersedia</span>
                                    <strong class="text-xl text-emerald-700 font-mono" id="client-credit-balance">Rp 0</strong>
                                    <p class="text-[10px] text-emerald-600 mt-0.5">Pending: <span id="client-pending-balance">Rp 0</span></p>
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
                        {{-- Switch Kredit --}}
                        <div class="bg-slate-50 rounded-lg p-4 border border-slate-200 transition-all duration-300" id="use-credit-container" style="display: none;">
                            <label class="flex items-center cursor-pointer select-none">
                                <div class="relative">
                                    <input type="checkbox" id="use_credit" name="use_credit" value="1" class="sr-only peer">
                                    <div class="w-11 h-6 bg-slate-300 peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-indigo-300 rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-indigo-600"></div>
                                </div>
                                <span class="ml-3 text-sm font-bold text-slate-700">Gunakan Saldo Kredit?</span>
                            </label>
                            <p class="text-xs text-slate-500 mt-1 ml-14">Jika aktif, pembayaran akan memotong saldo deposit klien terlebih dahulu.</p>
                        </div>

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            {{-- Total Amount --}}
                            <div>
                                <label for="total_amount_formatted" class="block text-xs font-bold text-slate-500 uppercase mb-1.5">Total Uang Diterima (Cash/Transfer)</label>
                                <div class="relative">
                                    <span class="absolute left-4 top-1/2 -translate-y-1/2 text-slate-400 text-sm font-bold">Rp</span>
                                    {{-- AutoNumeric Input (DISABLED by DEFAULT) --}}
                                    <input type="text" id="total_amount_formatted" class="form-input pl-10 text-lg font-bold text-emerald-600 font-mono placeholder:text-slate-300 bg-slate-50 cursor-not-allowed" placeholder="0" disabled>
                                    {{-- Hidden Input --}}
                                    <input type="hidden" name="total_amount" id="total_amount" value="0">
                                </div>
                                
                                {{-- ALERT OVERPAYMENT (HIDDEN DEFAULT) --}}
                                <div id="overpayment-alert" class="hidden mt-2 p-2 bg-blue-50 border border-blue-100 rounded-lg flex items-start gap-2">
                                    <i class="material-icons text-blue-500 text-sm mt-0.5">info</i>
                                    <p class="text-[11px] text-blue-700">
                                        Kelebihan bayar sebesar <span class="font-bold" id="overpayment-amount">Rp 0</span> akan disimpan sebagai Deposit Klien.
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
                                {{-- DISABLED by DEFAULT --}}
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
                                <label for="company_bank_account_id" class="block text-xs font-bold text-slate-500 uppercase mb-1.5">Masuk ke Akun (Kas/Bank)</label>
                                {{-- DISABLED by DEFAULT --}}
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
                            <div class="md:col-span-1" id="payment-reference-group" style="display: none;">
                                <label for="reference_number" class="block text-xs font-bold text-slate-500 uppercase mb-1.5">Nomor Referensi</label>
                                <input type="text" name="reference_number" id="reference_number" class="form-input" placeholder="No. Cek / Giro / Transfer">
                            </div>

                            <div class="md:col-span-1" id="payment-proof-group" style="display: none;">
                                <label for="proof_of_payment" class="block text-xs font-bold text-slate-500 uppercase mb-1.5">Bukti Transfer</label>
                                <input type="file" name="proof_of_payment" id="proof_of_payment" class="block w-full text-sm text-slate-500 file:mr-4 file:py-2 file:px-4 file:rounded-full file:border-0 file:text-xs file:font-semibold file:bg-indigo-50 file:text-indigo-700 hover:file:bg-indigo-100" accept="image/jpeg,image/png,image/jpg">
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

            {{-- KOLOM KANAN (INVOICE ALLOCATION) --}}
            <div class="lg:col-span-1">
                <div class="dashboard-card p-0 overflow-hidden shadow-lg border-0 ring-1 ring-slate-900/5 flex flex-col h-[600px] sticky top-6">
                    
                    {{-- Header List Invoice --}}
                    <div class="p-4 border-b border-slate-200 bg-slate-50/80 rounded-t-xl">
                        <div class="flex items-center gap-2 mb-1">
                            <div class="bg-indigo-100 p-1.5 rounded text-indigo-600">
                                <i class="material-icons text-[18px]">playlist_add_check</i>
                            </div>
                            <h3 class="text-sm font-bold text-slate-800 uppercase tracking-wide">3. Alokasi Invoice</h3>
                        </div>
                        <p class="text-[10px] text-slate-500 ml-8">Sistem melunasi dari invoice terlama.</p>
                    </div>

                    {{-- List Container (Tabel Invoice) --}}
                    <div class="flex-1 overflow-y-auto custom-scrollbar relative bg-white">
                        {{-- Placeholder State --}}
                        <div id="invoice-placeholder" class="flex flex-col items-center justify-center h-full text-slate-400 p-8 text-center">
                            <i class="material-icons text-5xl mb-3 opacity-20">search</i>
                            <p class="text-sm font-medium">Silakan pilih klien untuk memuat tagihan.</p>
                        </div>

                        {{-- Table --}}
                        <table class="min-w-full divide-y divide-slate-100 hidden" id="invoice-table">
                            <thead class="bg-slate-50 sticky top-0 z-10 shadow-sm">
                                <tr>
                                    <th scope="col" class="px-4 py-3 text-center w-10 border-b border-slate-200">
                                        <input type="checkbox" id="check-all-invoices" class="rounded border-slate-300 text-indigo-600 focus:ring-indigo-500 cursor-pointer">
                                    </th>
                                    <th scope="col" class="px-2 py-3 text-left text-xs font-bold text-slate-500 uppercase border-b border-slate-200">Invoice</th>
                                    <th scope="col" class="px-4 py-3 text-right text-xs font-bold text-slate-500 uppercase border-b border-slate-200">Sisa</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-slate-50" id="invoice-list-body">
                                {{-- JS will render rows here --}}
                            </tbody>
                        </table>
                    </div>

                    {{-- Footer Action & Summary --}}
                    <div class="p-5 border-t border-slate-200 bg-slate-50">
                        
                        {{-- RINGKASAN KALKULASI --}}
                        <div class="space-y-2 mb-5 text-sm">
                            <div class="flex justify-between items-center">
                                <span class="text-slate-500 font-medium">Total Tagihan Dipilih</span>
                                <span class="font-bold text-slate-800" id="display-total-bill">Rp 0</span>
                            </div>
                            
                            {{-- Baris Potongan Kredit (Hidden by default) --}}
                            <div id="summary-credit-row" class="flex justify-between items-center text-emerald-600 hidden animate-enter">
                                <span class="font-bold flex items-center gap-1"><i class="material-icons text-[14px]">account_balance_wallet</i> Potong Deposit</span>
                                <span class="font-bold font-mono" id="display-credit-used">- Rp 0</span>
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
    const clientSelect = $('#client_id');
    const paymentMethodSelect = $('#payment_method_id');
    const bankAccountSelect = $('#company_bank_account_id');
    
    const invoiceTable = document.getElementById('invoice-table');
    const invoiceListBody = document.getElementById('invoice-list-body');
    const invoicePlaceholder = document.getElementById('invoice-placeholder');
    const checkAll = document.getElementById('check-all-invoices');
    
    const totalSelectedDisplay = document.getElementById('total-selected-display');
    const displayTotalBill = document.getElementById('display-total-bill');
    const displayCreditUsed = document.getElementById('display-credit-used');
    const summaryCreditRow = document.getElementById('summary-credit-row');
    
    const amountFormattedInput = document.getElementById('total_amount_formatted');
    const amountHiddenInput = document.getElementById('total_amount');
    
    // Overpayment Alert Elements
    const overpaymentAlert = document.getElementById('overpayment-alert');
    const overpaymentAmountSpan = document.getElementById('overpayment-amount');
    
    const creditInfoDiv = document.getElementById('client-credit-info');
    const creditBalanceSpan = document.getElementById('client-credit-balance');
    const pendingBalanceSpan = document.getElementById('client-pending-balance');
    
    const useCreditContainer = document.getElementById('use-credit-container');
    const useCreditCheckbox = document.getElementById('use_credit');
    
    const referenceGroup = document.getElementById('payment-reference-group');
    const referenceInput = document.getElementById('reference_number');
    const proofGroup = document.getElementById('payment-proof-group');
    const proofInput = document.getElementById('proof_of_payment');
    
    let currentCreditBalance = 0;
    const defaultPaymentMethodId = "{{ $defaultPaymentMethodId }}";
    const defaultBankAccountId = "{{ $defaultBankAccountId }}";

    // --- 1. INIT SELECT2 (Manual) ---
    clientSelect.select2({ placeholder: '-- Cari Klien --', allowClear: true, width: '100%', dropdownCssClass: 'select2-dropdown-clean' });
    paymentMethodSelect.select2({ placeholder: '-- Pilih Metode --', width: '100%', dropdownCssClass: 'select2-dropdown-clean' });
    bankAccountSelect.select2({ placeholder: '-- Pilih Akun --', width: '100%', dropdownCssClass: 'select2-dropdown-clean' });

    // --- 2. INIT AUTONUMERIC ---
    const anAmount = new AutoNumeric(amountFormattedInput, {
        digitGroupSeparator: '.',
        decimalCharacter: ',',
        decimalPlaces: 0,
        minimumValue: '0',
        emptyInputBehavior: 'zero',
        unformatOnSubmit: false 
    });

    // Listener saat user mengetik manual (User Input Trigger)
    amountFormattedInput.addEventListener('autoNumeric:rawValueModified', (e) => {
        const val = e.detail.newRawValue || 0;
        amountHiddenInput.value = val;
        
        // Saat user mengetik, kita tidak mau meng-overwrite nilai input mereka dengan auto-calc
        // Jadi kita panggil calculateTotals dengan updateInput = false
        calculateTotals(false); 
    });

    // --- 3. PAYMENT METHOD LOGIC ---
    function handlePaymentMethodChange() {
        const selectedData = paymentMethodSelect.select2('data')[0];
        const config = selectedData ? $(selectedData.element).data('config') : 'none';

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
    paymentMethodSelect.on('change', handlePaymentMethodChange);

    // --- 4. HELPER FORMAT ---
    function formatRupiah(number) {
        return new Intl.NumberFormat('id-ID', { style: 'currency', currency: 'IDR', minimumFractionDigits: 0 }).format(number);
    }

    // --- 5. TOGGLE INPUT STATE (ENABLE/DISABLE) ---
    function toggleInputState(hasSelection, isFullPaidByCredit) {
        if (!hasSelection) {
            // Belum ada invoice dipilih -> DISABLE SEMUA
            amountFormattedInput.classList.add('bg-slate-100', 'cursor-not-allowed');
            amountFormattedInput.disabled = true;
            
            paymentMethodSelect.prop('disabled', true);
            bankAccountSelect.prop('disabled', true);
            
            anAmount.set(0);
            amountHiddenInput.value = 0;
        } 
        else if (isFullPaidByCredit) {
            // Lunas pakai kredit -> DISABLE Payment Method
            amountFormattedInput.classList.add('bg-slate-100', 'cursor-not-allowed');
            amountFormattedInput.disabled = true;
            
            paymentMethodSelect.val(null).trigger('change').prop('disabled', true);
            bankAccountSelect.val(null).trigger('change').prop('disabled', true);
            
            anAmount.set(0);
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

    // --- 6. LOGIKA UTAMA (KALKULASI) ---
    // Param: updateInput (bool) -> Apakah kita harus auto-fill input nominal?
    function calculateTotals(updateInput = true) {
        // 1. Hitung Total Tagihan Invoice yang Dipilih
        let totalBill = 0;
        document.querySelectorAll('.invoice-checkbox:checked').forEach(checkbox => {
            totalBill += parseFloat(checkbox.dataset.balance || 0);
        });

        // Update UI: Total Tagihan
        displayTotalBill.textContent = formatRupiah(totalBill);

        // 2. Hitung Kredit
        const useCreditIsChecked = useCreditCheckbox.checked;
        let creditUsed = 0;
        let requiredCash = totalBill;

        if (useCreditIsChecked && currentCreditBalance > 0) {
            creditUsed = Math.min(totalBill, currentCreditBalance);
            requiredCash = Math.max(0, totalBill - creditUsed);

            summaryCreditRow.classList.remove('hidden');
            displayCreditUsed.textContent = '- ' + formatRupiah(creditUsed);
        } else {
            summaryCreditRow.classList.add('hidden');
        }

        // Update UI: Harus Dibayar
        totalSelectedDisplay.textContent = formatRupiah(requiredCash);

        // 3. State Management (Enable/Disable Inputs)
        const hasSelection = totalBill > 0;
        const isFullPaidByCredit = (hasSelection && requiredCash <= 0);
        
        // Kita panggil toggle state HANYA jika ini triggered by checkbox/system, 
        // BUKAN saat user sedang ngetik nominal (agar tidak interrupting).
        if (updateInput) {
            toggleInputState(hasSelection, isFullPaidByCredit);
            
            // Auto-fill nominal jika perlu bayar cash
            if (!isFullPaidByCredit && hasSelection) {
                anAmount.set(requiredCash);
                amountHiddenInput.value = requiredCash;
            }
        }

        // 4. OVERPAYMENT CHECK (Fitur Baru)
        // Bandingkan (Input User + Kredit) vs (Total Tagihan)
        const currentUserInput = parseFloat(amountHiddenInput.value) || 0;
        const totalPayment = currentUserInput + creditUsed;
        
        // Hanya cek overpayment jika ada invoice yg dipilih dan user input sesuatu
        if (hasSelection && totalPayment > totalBill) {
            const excess = totalPayment - totalBill;
            overpaymentAlert.classList.remove('hidden');
            overpaymentAmountSpan.textContent = formatRupiah(excess);
        } else {
            overpaymentAlert.classList.add('hidden');
        }
    }

    // --- 7. EVENT LISTENERS ---
    
    // Listener Checkbox Kredit
    useCreditCheckbox.addEventListener('change', () => calculateTotals(true));

    // Listener Check All
    checkAll.addEventListener('change', function () {
        const isChecked = this.checked;
        document.querySelectorAll('.invoice-checkbox').forEach(checkbox => {
            checkbox.checked = isChecked;
        });
        calculateTotals(true); // Update input karena selection berubah
    });

    // Listener Checkbox Individual (Event Delegation)
    invoiceListBody.addEventListener('change', function(e) {
        if (e.target.classList.contains('invoice-checkbox')) {
            calculateTotals(true); // Update input karena selection berubah
        }
    });

    // --- 8. FETCH DATA KLIEN ---
    clientSelect.on('change', async function () {
        const clientId = $(this).val();

        // Reset UI State
        creditInfoDiv.classList.add('hidden');
        useCreditContainer.style.display = 'none';
        useCreditCheckbox.checked = false;
        currentCreditBalance = 0;
        
        // Reset Table
        invoicePlaceholder.innerHTML = `
            <div class="flex flex-col items-center justify-center py-8">
                <div class="animate-spin rounded-full h-8 w-8 border-b-2 border-indigo-600 mb-3"></div>
                <span class="text-sm text-slate-500 font-medium">Memuat data...</span>
            </div>
        `;
        invoicePlaceholder.classList.remove('hidden');
        invoiceTable.classList.add('hidden');
        invoiceListBody.innerHTML = '';
        checkAll.checked = false;
        
        // Reset Input
        anAmount.set(0);
        amountHiddenInput.value = 0;
        toggleInputState(false, false); 

        if (!clientId) return;

        try {
            // ============================================================
            // PERBAIKAN URL DENGAN ROUTE HELPER
            // ============================================================
            let urlDetails = "{{ route('admin.api.clients.details', ':id') }}";
            urlDetails = urlDetails.replace(':id', clientId);

            let urlInvoices = "{{ route('admin.api.clients.unpaid-invoices', ':id') }}";
            urlInvoices = urlInvoices.replace(':id', clientId);

            const [clientRes, invoiceRes] = await Promise.all([
                fetch(urlDetails),
                fetch(urlInvoices)
            ]);

            if (!clientRes.ok || !invoiceRes.ok) throw new Error('Gagal memuat data');

            const clientData = await clientRes.json();
            const invoices = await invoiceRes.json();
            
            // Update Credit Info
            currentCreditBalance = parseFloat(clientData.balance) || 0;
            creditBalanceSpan.textContent = formatRupiah(currentCreditBalance);
            pendingBalanceSpan.textContent = formatRupiah(clientData.pending_balance || 0);
            
            creditInfoDiv.classList.remove('hidden');
            if (currentCreditBalance > 0) {
                useCreditContainer.style.display = 'block';
            }

            // Render Invoices
            if (invoices.length === 0) {
                invoicePlaceholder.innerHTML = `
                    <div class="flex flex-col items-center text-slate-400 mt-10">
                        <i class="material-icons text-4xl mb-2 opacity-30">check_circle</i>
                        <p class="text-sm">Semua invoice telah lunas.</p>
                    </div>
                `;
            } else {
                invoices.forEach(invoice => {
                    const row = `
                        <tr class="hover:bg-indigo-50/30 transition-colors group">
                            <td class="px-4 py-3 text-center border-b border-slate-50">
                                <input class="invoice-checkbox rounded border-slate-300 text-indigo-600 shadow-sm focus:ring-indigo-500 cursor-pointer h-4 w-4"
                                       type="checkbox"
                                       name="invoice_ids[]"
                                       value="${invoice.invoice_id}"
                                       data-balance="${invoice.sisa_tagihan}">
                            </td>
                            <td class="px-2 py-3 text-sm border-b border-slate-50">
                                <div class="font-bold text-indigo-600 font-mono">${invoice.invoice_number}</div>
                                <div class="text-xs text-slate-500 mt-0.5">Due: ${invoice.due_date_formatted}</div>
                            </td>
                            <td class="px-4 py-3 text-right text-sm font-medium text-slate-800 border-b border-slate-50 font-mono">
                                ${formatRupiah(invoice.sisa_tagihan)}
                            </td>
                        </tr>
                    `;
                    invoiceListBody.insertAdjacentHTML('beforeend', row);
                });
                invoicePlaceholder.classList.add('hidden');
                invoiceTable.classList.remove('hidden');
            }

        } catch (error) {
            console.error(error);
            invoicePlaceholder.innerHTML = `<div class="text-red-500 text-sm font-bold mt-10">Gagal memuat data.</div>`;
        }
        
        // Recalculate (akan mereset total jadi 0 dan disable input)
        calculateTotals(true);
    });

    // --- 9. FORM SUBMIT ---
    const form = document.getElementById('bulk-payment-form');
    form.addEventListener('submit', function(e) {
        const checkedInvoices = document.querySelectorAll('.invoice-checkbox:checked');
        if (checkedInvoices.length === 0) {
            e.preventDefault();
            Swal.fire('Pilih Invoice', 'Harap centang minimal satu invoice untuk dibayar.', 'warning');
            return;
        }

        const submitBtn = document.getElementById('submit-btn');
        if (submitBtn) {
            submitBtn.disabled = true;
            submitBtn.innerHTML = '<i class="material-icons animate-spin text-sm">sync</i> Menyimpan...';
        }
    });
    
    // Init State saat load (Reset semua)
    toggleInputState(false, false);

    @if(session('success')) window.showToast("{{ session('success') }}", 'success'); @endif
    @if(session('error')) window.showToast("{{ session('error') }}", 'error'); @endif
});
</script>
@endpush
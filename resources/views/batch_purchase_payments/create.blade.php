@extends('layouts.app')

@section('title', 'Pembayaran Batch (Hutang)')

@push('styles')
<style>
    .form-select-lg-custom {
        height: 48px;
        font-size: 1rem;
        border-color: #e5e7eb;
        border-radius: 0.5rem;
    }
</style>
@endpush

@php
    $defaultPaymentMethodId = $paymentMethods->first()->payment_method_id ?? '';
    $defaultBankAccountId = $companyBankAccounts->first()->company_bank_account_id ?? '';
@endphp

@section('content')
<div class="max-w-6xl mx-auto py-6">
    
    {{-- HEADER --}}
    <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center mb-8 gap-4">
        <div>
            <div class="flex items-center gap-2 text-sm text-gray-500 mb-1">
                <a href="{{ route('purchase-orders.index') }}" class="hover:text-indigo-600 transition">Pembelian</a>
                <span>/</span>
                <span class="text-gray-800">Batch Payment</span>
            </div>
            <h2 class="text-2xl font-bold text-gray-900 tracking-tight">Pembayaran Hutang (Batch)</h2>
            <p class="text-sm text-gray-500 mt-1">Catat pembayaran untuk beberapa PO sekaligus ke satu supplier.</p>
        </div>
        <a href="{{ route('purchase-orders.index') }}" class="px-4 py-2 bg-white border border-gray-300 rounded-lg text-gray-700 text-sm font-medium hover:bg-gray-50 transition shadow-sm">
            Kembali
        </a>
    </div>

    {{-- ==================================================================== --}}
    {{-- ✅ NOTIFIKASI SUKSES & ERROR (ADDED) --}}
    {{-- ==================================================================== --}}
    @if (session('success'))
        <div class="mb-6 bg-green-50 border-l-4 border-green-500 p-4 rounded-r shadow-sm flex justify-between items-center animate-fade-in-down">
            <div class="flex items-center">
                <i class="bi bi-check-circle-fill text-green-500 text-xl mr-3"></i>
                <div>
                    <h3 class="text-sm font-bold text-green-800">Berhasil!</h3>
                    <p class="text-sm text-green-700 font-medium">{{ session('success') }}</p>
                </div>
            </div>
            <button type="button" class="text-green-500 hover:text-green-700 transition" onclick="this.parentElement.remove()">
                <i class="bi bi-x text-lg"></i>
            </button>
        </div>
    @endif

    @if ($errors->any() || session('error'))
        <div class="mb-6 bg-red-50 border-l-4 border-red-500 p-4 rounded-r shadow-sm animate-fade-in-down">
            <div class="flex">
                <div class="flex-shrink-0">
                    <i class="bi bi-exclamation-triangle-fill text-red-400 text-xl"></i>
                </div>
                <div class="ml-3">
                    <h3 class="text-sm font-bold text-red-800">Terdapat kesalahan:</h3>
                    <ul class="mt-1 list-disc list-inside text-sm text-red-700">
                        @foreach ($errors->all() as $error) <li>{{ $error }}</li> @endforeach
                        @if (session('error')) <li>{{ session('error') }}</li> @endif
                    </ul>
                </div>
            </div>
        </div>
    @endif
    {{-- ==================================================================== --}}


    <form action="{{ route('batch-purchase-payments.store') }}" method="POST" id="batch-payment-form" enctype="multipart/form-data">
        @csrf

        <div class="grid grid-cols-1 lg:grid-cols-12 gap-8">

            {{-- KOLOM KIRI: SUPPLIER & PEMBAYARAN (Span 8) --}}
            <div class="lg:col-span-8 space-y-8">
                
                {{-- 1. PILIH SUPPLIER --}}
                <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
                    <div class="px-6 py-4 border-b border-gray-100 bg-gray-50 flex items-center gap-2">
                        <div class="w-6 h-6 rounded-full bg-indigo-100 flex items-center justify-center text-indigo-600 text-xs font-bold">1</div>
                        <h3 class="text-sm font-bold text-gray-700 uppercase tracking-wider">Pilih Supplier</h3>
                    </div>
                    <div class="p-6">
                        <label for="supplier_id" class="block text-xs font-bold text-gray-500 uppercase mb-2">Cari Supplier</label>
                        <select name="supplier_id" id="supplier_id" class="w-full" required>
                            <option value="" disabled selected>-- Cari Supplier --</option>
                            @foreach ($suppliers as $supplier)
                                <option value="{{ $supplier->supplier_id }}">{{ $supplier->supplier_name }}</option>
                            @endforeach
                        </select>

                        {{-- Info Saldo Deposit (Hidden by default) --}}
                        <div id="supplier-debit-info" class="hidden mt-4 p-4 bg-blue-50 rounded-lg border border-blue-100 flex items-center justify-between">
                            <div class="flex items-center gap-3">
                                <div class="p-2 bg-blue-100 rounded-full text-blue-600">
                                    <i class="bi bi-wallet2 text-xl"></i>
                                </div>
                                <div>
                                    <span class="block text-xs text-blue-600 font-bold uppercase">Saldo Deposit</span>
                                    <span class="text-lg font-bold text-gray-900" id="supplier-debit-balance">Rp 0</span>
                                </div>
                            </div>
                            <div class="text-right">
                                <span class="block text-[10px] text-gray-400 uppercase">Pending</span>
                                <span class="text-sm font-medium text-gray-600" id="supplier-pending-balance">Rp 0</span>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- 2. DETAIL PEMBAYARAN --}}
                <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
                    <div class="px-6 py-4 border-b border-gray-100 bg-gray-50 flex items-center gap-2">
                        <div class="w-6 h-6 rounded-full bg-indigo-100 flex items-center justify-center text-indigo-600 text-xs font-bold">2</div>
                        <h3 class="text-sm font-bold text-gray-700 uppercase tracking-wider">Detail Pembayaran</h3>
                    </div>
                    <div class="p-6 space-y-6">
                        
                        {{-- Switch Deposit --}}
                        <div id="use-debit-container" class="hidden p-3 bg-gray-50 rounded-lg border border-gray-200 flex items-center gap-3">
                            <input type="checkbox" role="switch" id="use_debit_balance" name="use_debit_balance" value="1" class="rounded border-gray-300 text-indigo-600 focus:ring-indigo-500 h-5 w-5 cursor-pointer">
                            <label for="use_debit_balance" class="text-sm font-medium text-gray-700 cursor-pointer select-none">
                                Gunakan Saldo Deposit untuk pembayaran ini?
                            </label>
                        </div>

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            {{-- Total Bayar --}}
                            <div>
                                <label for="total_amount_formatted" class="block text-xs font-bold text-gray-500 uppercase mb-1">Total Bayar (Non-Deposit)</label>
                                <div class="relative rounded-md shadow-sm">
                                    <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                        <span class="text-gray-500 sm:text-sm">Rp</span>
                                    </div>
                                    <input type="text" id="total_amount_formatted" class="block w-full pl-10 rounded-md border-gray-300 focus:ring-indigo-500 focus:border-indigo-500 text-lg font-bold text-indigo-600" placeholder="0">
                                </div>
                                <input type="hidden" name="total_amount" id="total_amount" value="0">
                            </div>

                            {{-- Tanggal --}}
                            <div>
                                <label class="block text-xs font-bold text-gray-500 uppercase mb-1">Tanggal Bayar</label>
                                <input type="date" name="payment_date" class="w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 text-sm" value="{{ now()->format('Y-m-d') }}" required>
                            </div>

                            {{-- Metode Bayar --}}
                            <div>
                                <label class="block text-xs font-bold text-gray-500 uppercase mb-1">Metode Bayar</label>
                                <select name="payment_method_id" id="payment_method_id" class="w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 text-sm">
                                    <option value="">-- Pilih Metode --</option>
                                    @foreach ($paymentMethods as $method)
                                        <option value="{{ $method->payment_method_id }}" data-config="{{ $method->required_fields_config }}">{{ $method->name }}</option>
                                    @endforeach
                                </select>
                            </div>

                            {{-- Akun Kas/Bank --}}
                            <div>
                                <label class="block text-xs font-bold text-gray-500 uppercase mb-1">Sumber Dana</label>
                                <select name="company_bank_account_id" id="company_bank_account_id_batch_purchase" class="w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 text-sm">
                                    <option value="">-- Pilih Akun --</option>
                                    @foreach($companyBankAccounts as $account)
                                        <option value="{{ $account->company_bank_account_id }}">{{ $account->bank_name }} - {{ $account->account_number ?? $account->account_name }}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>

                        {{-- Conditional Fields --}}
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div id="payment-reference-group-batch-purchase" style="display: none;">
                                <label class="block text-xs font-bold text-gray-500 uppercase mb-1">Nomor Referensi</label>
                                <input type="text" name="reference_number" id="reference_number_batch_purchase" class="w-full rounded-md border-gray-300 shadow-sm text-sm" placeholder="No. Cek / Giro">
                            </div>
                            <div id="payment-proof-group-batch-purchase" style="display: none;">
                                <label class="block text-xs font-bold text-gray-500 uppercase mb-1">Bukti Transfer</label>
                                <input type="file" name="proof_of_payment" id="proof_of_payment_batch_purchase" class="w-full text-sm text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded-md file:border-0 file:text-xs file:font-semibold file:bg-indigo-50 file:text-indigo-700 hover:file:bg-indigo-100">
                            </div>
                        </div>

                        <div>
                            <label class="block text-xs font-bold text-gray-500 uppercase mb-1">Catatan (Opsional)</label>
                            <textarea name="notes" class="w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 text-sm" rows="2" placeholder="Tambahkan keterangan..."></textarea>
                        </div>
                    </div>
                </div>

            </div>

            {{-- KOLOM KANAN: ALOKASI PO (Span 4) --}}
            <div class="lg:col-span-4 space-y-6">
                
                <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden flex flex-col h-full sticky top-6">
                    <div class="px-6 py-4 border-b border-gray-100 bg-gray-50 flex items-center gap-2">
                        <div class="w-6 h-6 rounded-full bg-indigo-100 flex items-center justify-center text-indigo-600 text-xs font-bold">3</div>
                        <h3 class="text-sm font-bold text-gray-700 uppercase tracking-wider">Pilih Tagihan (PO)</h3>
                    </div>
                    
                    {{-- List PO Container --}}
                    <div class="flex-1 p-0 overflow-y-auto custom-scrollbar" style="max-height: 500px;">
                        
                        {{-- Placeholder --}}
                        <div id="po-placeholder" class="flex flex-col items-center justify-center py-10 text-gray-400">
                            <i class="bi bi-receipt text-4xl mb-2 opacity-30"></i>
                            <p class="text-sm">Pilih supplier terlebih dahulu.</p>
                        </div>

                        {{-- Table PO --}}
                        <table class="w-full text-left border-collapse hidden" id="po-table">
                            <thead class="bg-gray-50 border-b border-gray-200 sticky top-0 z-10">
                                <tr>
                                    <th class="px-4 py-3 text-center w-10">
                                        <input type="checkbox" id="check-all-pos" class="rounded border-gray-300 text-indigo-600 focus:ring-indigo-500 cursor-pointer">
                                    </th>
                                    <th class="px-4 py-3 text-xs font-bold text-gray-500 uppercase">No. PO</th>
                                    <th class="px-4 py-3 text-xs font-bold text-gray-500 uppercase text-right">Sisa Tagihan</th>
                                </tr>
                            </thead>
                            <tbody id="po-list-body" class="divide-y divide-gray-100">
                                {{-- JS akan mengisi baris ini --}}
                            </tbody>
                        </table>
                    </div>

                    {{-- Footer Summary --}}
                    <div class="p-6 border-t border-gray-200 bg-gray-50">
                        
                        {{-- Rincian Hitungan --}}
                        <div class="space-y-2 mb-4 text-sm">
                            <div class="flex justify-between text-gray-600">
                                <span>Total Tagihan Dipilih:</span>
                                <span class="font-semibold text-gray-900" id="summary-total-bill">Rp 0</span>
                            </div>
                            
                            {{-- Baris Deposit (Hidden by default) --}}
                            <div id="summary-deposit-row" class="flex justify-between text-green-600 hidden">
                                <span>Potong Deposit:</span>
                                <span id="summary-deposit-amount">- Rp 0</span>
                            </div>
                        </div>

                        <div class="border-t border-gray-200 my-3"></div>

                        <div class="flex justify-between items-end mb-4">
                            <span class="text-xs font-bold text-gray-500 uppercase tracking-wider">Total Transfer/Kas</span>
                            <span class="text-2xl font-bold text-indigo-600" id="total-selected-display">Rp 0</span>
                        </div>
                        
                        <button type="submit" class="w-full py-3 bg-indigo-600 hover:bg-indigo-700 text-white text-sm font-bold rounded-lg shadow-md transition flex justify-center items-center gap-2">
                            <i class="bi bi-check-circle"></i> Simpan Pembayaran
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

    // 1. AutoNumeric
    const autoNumericInstance = new AutoNumeric(amountFormattedInput, {
        digitGroupSeparator: '.', decimalCharacter: ',', decimalPlaces: 0, minimumValue: 0
    });

    amountFormattedInput.addEventListener('autoNumeric:rawValueModified', (e) => {
        amountHiddenInput.value = e.detail.newRawValue || 0;
        toggleRequiredFields();
    });

    // 2. Select2 Supplier
    supplierSelect.select2({ theme: 'bootstrap-5', placeholder: '-- Cari Supplier --', width: '100%' });

    // 3. Toggle Payment Fields
    function handlePaymentMethodChange() {
        if (!paymentMethodSelect) return;
        const selectedOption = paymentMethodSelect.options[paymentMethodSelect.selectedIndex];
        const config = (selectedOption && !paymentMethodSelect.disabled) ? selectedOption.dataset.config : 'none';

        referenceGroup.style.display = 'none'; referenceInput.required = false;
        proofGroup.style.display = 'none'; proofInput.required = false;

        if (config === 'proof_only') { proofGroup.style.display = 'block'; proofInput.required = true; }
        else if (config === 'reference_only') { referenceGroup.style.display = 'block'; referenceInput.required = true; }
        else if (config === 'proof_and_reference') {
            proofGroup.style.display = 'block'; proofInput.required = true;
            referenceGroup.style.display = 'block'; referenceInput.required = true;
        }
    }
    if (paymentMethodSelect) paymentMethodSelect.addEventListener('change', handlePaymentMethodChange);

    // 4. Helper Format Rupiah
    function formatRupiah(number) {
        return new Intl.NumberFormat('id-ID', { style: 'currency', currency: 'IDR', minimumFractionDigits: 0, maximumFractionDigits: 2 }).format(number);
    }

    // 5. Calculate Total Selected
    function calculateTotalSelected() {
        let total = 0;
        document.querySelectorAll('.po-checkbox:checked').forEach(checkbox => {
            total += parseFloat(checkbox.dataset.balance || 0);
        });
        totalSelectedDisplay.textContent = formatRupiah(total);
        toggleRequiredFields();
    }

    // 6. Toggle Required Fields based on Debit
    function toggleRequiredFields() {
        // 1. Ambil Total Tagihan dari Checkbox yang dipilih
        let totalBill = 0;
        document.querySelectorAll('.po-checkbox:checked').forEach(checkbox => {
            totalBill += parseFloat(checkbox.dataset.balance || 0);
        });

        const useDebitIsChecked = useDebitCheckbox.checked;
        let depositUsed = 0;
        let cashToPay = totalBill;

        // 2. Hitung Logika Deposit
        if (useDebitIsChecked && currentDebitBalance > 0) {
            if (currentDebitBalance >= totalBill) {
                // Deposit cukup untuk bayar semua
                depositUsed = totalBill;
                cashToPay = 0;
            } else {
                // Deposit tidak cukup, sisanya bayar cash
                depositUsed = currentDebitBalance;
                cashToPay = totalBill - currentDebitBalance;
            }
        }

        // 3. Update Tampilan Summary
        document.getElementById('summary-total-bill').textContent = formatRupiah(totalBill);
        
        const depositRow = document.getElementById('summary-deposit-row');
        const depositSpan = document.getElementById('summary-deposit-amount');
        const netDisplay = document.getElementById('total-selected-display');

        if (depositUsed > 0) {
            depositRow.classList.remove('hidden');
            depositSpan.textContent = '- ' + formatRupiah(depositUsed);
        } else {
            depositRow.classList.add('hidden');
        }

        netDisplay.textContent = formatRupiah(cashToPay);

        // 4. Update Input Form (AutoNumeric & Disabled State)
        // Set nilai input cash sesuai perhitungan sisa
        if (amountFormattedInput) {
            autoNumericInstance.set(cashToPay);
        }

        // Logic Disable/Enable Input berdasarkan apakah sisa bayar 0 atau tidak
        if (cashToPay <= 0) {
            // Lunas pakai deposit semua
            amountFormattedInput.disabled = true;
            
            paymentMethodSelect.disabled = true;
            paymentMethodSelect.value = "";
            paymentMethodSelect.required = false;
            
            bankAccountSelect.disabled = true;
            bankAccountSelect.value = "";
            bankAccountSelect.required = false;
        } else {
            // Masih ada sisa yang harus dibayar cash
            amountFormattedInput.disabled = false;
            
            paymentMethodSelect.disabled = false;
            paymentMethodSelect.required = true;
            if (!paymentMethodSelect.value) paymentMethodSelect.value = defaultPaymentMethodId;

            bankAccountSelect.disabled = false;
            bankAccountSelect.required = true;
            if (!bankAccountSelect.value) bankAccountSelect.value = defaultBankAccountId;
        }

        // Trigger perubahan method agar field bukti/referensi menyesuaikan
        handlePaymentMethodChange();
    }

    if(useDebitCheckbox) useDebitCheckbox.addEventListener('change', toggleRequiredFields);

    // 7. Checkbox Logic
    checkAll.addEventListener('change', function () {
        document.querySelectorAll('.po-checkbox').forEach(cb => cb.checked = this.checked);
        calculateTotalSelected();
    });

    function addPOCheckboxListeners() {
        document.querySelectorAll('.po-checkbox').forEach(cb => {
            cb.addEventListener('change', handlePOCheckboxChange);
        });
    }

    function handlePOCheckboxChange() {
        calculateTotalSelected();
    }

    // 8. Fetch PO Data
    supplierSelect.on('change', async function () {
        const supplierId = this.value;

        // Reset UI
        debitInfoDiv.classList.add('hidden'); 
        useDebitContainer.style.display = 'none';
        useDebitCheckbox.checked = false;
        currentDebitBalance = 0;
        
        poPlaceholder.innerHTML = '<div class="flex flex-col items-center"><div class="animate-spin rounded-full h-8 w-8 border-b-2 border-indigo-600 mb-2"></div><span class="text-sm text-gray-500">Memuat data...</span></div>';
        poPlaceholder.classList.remove('hidden');
        poTable.classList.add('hidden');
        poListBody.innerHTML = '';
        checkAll.checked = false;
        autoNumericInstance.set(0);

        if (!supplierId) return;

        try {
            const supplierResponse = await fetch(`/api/suppliers/${supplierId}/details`);
            if (!supplierResponse.ok) throw new Error('Gagal ambil data supplier');
            const supplierData = await supplierResponse.json();
            
            currentDebitBalance = parseFloat(supplierData.balance) || 0;
            const pendingBalance = parseFloat(supplierData.pending_balance) || 0;

            debitBalanceSpan.textContent = formatRupiah(currentDebitBalance);
            pendingBalanceSpan.textContent = formatRupiah(pendingBalance);
            debitInfoDiv.classList.remove('hidden');
            debitInfoDiv.classList.add('block'); // Ensure visible

            if (currentDebitBalance > 0) useDebitContainer.style.display = 'flex';

            const poResponse = await fetch(`/api/suppliers/${supplierId}/unpaid-purchase-orders`);
            if (!poResponse.ok) throw new Error('Gagal mengambil data PO');
            const pos = await poResponse.json();

            if (pos.length === 0) {
                poPlaceholder.textContent = 'Supplier ini tidak memiliki tagihan PO.';
            } else {
                pos.forEach(po => {
                    const row = `
                        <tr class="hover:bg-gray-50 transition-colors">
                            <td class="px-4 py-3 text-center">
                                <input class="rounded border-gray-300 text-indigo-600 focus:ring-indigo-500 cursor-pointer po-checkbox"
                                       type="checkbox" name="po_ids[]" value="${po.po_id}" data-balance="${po.sisa_tagihan}">
                            </td>
                            <td class="px-4 py-3">
                                <span class="block text-sm font-bold text-indigo-600">${po.po_number}</span>
                                <span class="text-xs text-gray-500">Due: ${po.due_date_formatted}</span>
                            </td>
                            <td class="px-4 py-3 text-right text-sm font-medium text-gray-900">
                                ${formatRupiah(po.sisa_tagihan)}
                            </td>
                        </tr>
                    `;
                    poListBody.insertAdjacentHTML('beforeend', row);
                });
                poPlaceholder.classList.add('hidden');
                poTable.classList.remove('hidden');
                addPOCheckboxListeners();
            }

        } catch (error) {
            poPlaceholder.textContent = 'Gagal memuat data.';
            console.error(error);
        }
        calculateTotalSelected();
        toggleRequiredFields();
    });

    toggleRequiredFields();
});
</script>
@endpush
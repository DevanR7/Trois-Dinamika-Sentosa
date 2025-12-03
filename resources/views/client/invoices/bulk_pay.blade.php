@extends('client.layouts.app')

@section('title', 'Pembayaran Batch')

@push('styles')
<link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/select2-bootstrap-5-theme@1.3.0/dist/select2-bootstrap-5-theme.min.css" />
<style>
    /* Fix Z-Index Select2 dalam Modal Custom */
    .select2-container--open { z-index: 99999999 !important; }
    
    /* Animasi Modal */
    .modal-enter { animation: modalIn 0.3s ease-out forwards; }
    @keyframes modalIn {
        from { opacity: 0; transform: scale(0.95); }
        to { opacity: 1; transform: scale(1); }
    }
</style>
@endpush

@php
    $transferMethodId = $paymentMethods->firstWhere(fn($m) => str_contains(strtolower($m->name), 'transfer'))->payment_method_id ?? null;
    $cashMethodId = $paymentMethods->firstWhere(fn($m) => str_contains(strtolower($m->name), 'cash'))->payment_method_id ?? null;
@endphp

@section('content')
<div class="max-w-7xl mx-auto space-y-6 pb-20">
    
    <div class="flex flex-col md:flex-row justify-between items-start md:items-center gap-4">
        <div>
            <h2 class="text-2xl font-bold text-slate-800 dark:text-slate-100 tracking-tight">Pembayaran Batch</h2>
            <p class="text-sm text-slate-500 dark:text-slate-400 mt-1">Pilih beberapa invoice untuk dibayar sekaligus.</p>
        </div>
        <a href="{{ route('client.invoices.index') }}" class="text-sm text-slate-500 hover:text-indigo-600 font-bold flex items-center gap-1">
            <i class="material-icons text-[16px]">arrow_back</i> Kembali
        </a>
    </div>

    <form id="batch-payment-form">
        @csrf
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
            
            {{-- KOLOM KIRI: DAFTAR INVOICE --}}
            <div class="lg:col-span-2">
                <div class="dashboard-card h-full flex flex-col overflow-hidden">
                    <div class="px-6 py-4 border-b border-slate-100 dark:border-slate-700 bg-slate-50/50 dark:bg-slate-800/50">
                        <h5 class="font-bold text-slate-700 dark:text-slate-200">1. Pilih Tagihan</h5>
                    </div>
                    
                    <div class="overflow-y-auto custom-scrollbar flex-1" style="max-height: 600px;">
                        <table class="w-full text-left border-collapse">
                            <thead class="bg-white dark:bg-slate-800 sticky top-0 z-10 shadow-sm text-xs font-bold text-slate-500 uppercase">
                                <tr>
                                    <th class="p-4 w-12 text-center border-b border-slate-100 dark:border-slate-700">
                                        <input type="checkbox" id="check-all-invoices" class="w-4 h-4 rounded border-slate-300 text-indigo-600 focus:ring-indigo-500 cursor-pointer">
                                    </th>
                                    <th class="p-4 border-b border-slate-100 dark:border-slate-700">Invoice</th>
                                    <th class="p-4 border-b border-slate-100 dark:border-slate-700">Jatuh Tempo</th>
                                    <th class="p-4 text-right border-b border-slate-100 dark:border-slate-700">Sisa Tagihan</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-100 dark:divide-slate-700 text-sm">
                                @forelse($invoices as $invoice)
                                    <tr class="hover:bg-slate-50 dark:hover:bg-slate-800/50 transition-colors group">
                                        <td class="p-4 text-center">
                                            <input class="w-4 h-4 rounded border-slate-300 text-indigo-600 focus:ring-indigo-500 cursor-pointer invoice-checkbox" 
                                                   type="checkbox" 
                                                   name="invoice_ids[]" 
                                                   value="{{ $invoice->invoice_id }}"
                                                   data-balance="{{ $invoice->remaining_balance }}">
                                        </td>
                                        <td class="p-4">
                                            <a href="{{ route('client.invoices.show', $invoice->invoice_id) }}" target="_blank" class="font-bold text-indigo-600 hover:underline block">
                                                {{ $invoice->invoice_number }}
                                            </a>
                                            <span class="text-xs text-slate-400">Tgl: {{ $invoice->order_date->format('d M Y') }}</span>
                                        </td>
                                        <td class="p-4">
                                            <span class="{{ $invoice->due_date->isPast() ? 'text-red-600 font-bold' : 'text-slate-600 dark:text-slate-400' }}">
                                                {{ $invoice->due_date->format('d M Y') }}
                                            </span>
                                        </td>
                                        <td class="p-4 text-right font-bold text-slate-700 dark:text-slate-200">
                                            Rp {{ number_format($invoice->remaining_balance, 0, ',', '.') }}
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="4" class="py-12 text-center text-slate-500">
                                            <div class="flex flex-col items-center">
                                                <i class="material-icons text-3xl text-slate-300 mb-2">check_circle</i>
                                                <p>Tidak ada tagihan yang belum lunas.</p>
                                            </div>
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            {{-- KOLOM KANAN: RINGKASAN (STICKY) --}}
            <div class="lg:col-span-1">
                <div class="dashboard-card sticky top-6 p-6 space-y-6">
                    
                    {{-- Section 2 --}}
                    <div>
                        <h5 class="font-bold text-slate-800 dark:text-slate-100 mb-4 pb-2 border-b border-slate-100 dark:border-slate-700 text-sm uppercase tracking-wide">
                            2. Ringkasan & Saldo
                        </h5>
                        
                        <div class="flex justify-between items-center mb-2 text-sm">
                            <span class="text-slate-600 dark:text-slate-400">Total Dipilih</span>
                            <strong class="text-slate-800 dark:text-slate-200" id="summary-total-tagihan" data-total="0">Rp 0</strong>
                        </div>

                        {{-- Saldo Kredit --}}
                        @if($availableBalance > 0)
                            <div class="bg-emerald-50 dark:bg-emerald-900/20 p-3 rounded-lg border border-emerald-100 dark:border-emerald-800/30 mb-4 mt-3">
                                <div class="flex justify-between items-center mb-2 text-sm">
                                    <span class="text-emerald-700 dark:text-emerald-400 font-bold">Saldo Deposit</span>
                                    <strong class="text-emerald-700 dark:text-emerald-400" id="summary-saldo-kredit-value" data-balance="{{ $availableBalance }}">
                                        Rp {{ number_format($availableBalance, 0, ',', '.') }}
                                    </strong>
                                </div>
                                <label class="flex items-center gap-2 cursor-pointer select-none">
                                    <input type="checkbox" id="use-credit-batch" value="1" class="w-4 h-4 rounded border-emerald-300 text-emerald-600 focus:ring-emerald-500">
                                    <span class="text-xs font-bold text-emerald-800 dark:text-emerald-300">Gunakan Saldo</span>
                                </label>
                            </div>
                        @else
                            <input type="hidden" id="use-credit-batch" value="0">
                        @endif

                        @if($pendingBalance > 0)
                            <div class="text-xs text-amber-600 italic mb-4 bg-amber-50 p-2 rounded border border-amber-100">
                                <i class="material-icons text-[12px] align-middle">info</i> Ada saldo tertahan: Rp {{ number_format($pendingBalance, 0, ',', '.') }}
                            </div>
                        @endif

                        {{-- Input Nominal (Readonly for batch usually calculated automatically) --}}
                        <div class="mb-4">
                            <label for="batch-amount-formatted" class="block text-xs font-bold text-slate-500 uppercase tracking-wide mb-1">Total Yang Akan Dibayar</label>
                            <input type="text" class="form-input text-right font-bold text-lg bg-white" id="batch-amount-formatted" placeholder="Rp 0">
                            <div id="batch-amount-error" class="text-xs mt-1 hidden font-medium"></div>
                        </div>

                        <div class="flex justify-between items-center py-3 border-t border-slate-100 dark:border-slate-700">
                            <span class="font-bold text-slate-600 dark:text-slate-400">Sisa Ditagih</span>
                            <strong class="text-xl text-indigo-600 dark:text-indigo-400 font-mono" id="summary-total-ditagih">Rp 0</strong>
                        </div>
                    </div>

                    {{-- Section 3: Metode --}}
                    <div>
                        <h6 class="text-xs font-bold text-slate-500 uppercase tracking-wide mb-3">3. Pilih Metode Pembayaran</h6>
                        <div class="space-y-3">
                            {{-- Midtrans --}}
                            <button type="button" id="pay-online-btn" disabled
                                class="w-full group flex items-center gap-3 p-3 rounded-xl border border-slate-200 dark:border-slate-700 hover:border-indigo-500 hover:bg-indigo-50 dark:hover:bg-indigo-900/20 transition-all text-left disabled:opacity-50 disabled:cursor-not-allowed bg-white dark:bg-slate-800">
                                <div class="w-10 h-10 rounded-full bg-indigo-100 text-indigo-600 flex items-center justify-center flex-shrink-0">
                                    <i class="material-icons">credit_card</i>
                                </div>
                                <div>
                                    <span class="block font-bold text-slate-700 dark:text-slate-200 text-sm group-hover:text-indigo-700">Bayar Online</span>
                                    <span class="block text-[10px] text-slate-500">QRIS, VA, E-Wallet</span>
                                </div>
                            </button>

                            {{-- Manual Transfer --}}
                            <button type="button" id="pay-manual-transfer-btn" disabled
                                class="w-full group flex items-center gap-3 p-3 rounded-xl border border-slate-200 dark:border-slate-700 hover:border-blue-500 hover:bg-blue-50 dark:hover:bg-blue-900/20 transition-all text-left disabled:opacity-50 disabled:cursor-not-allowed bg-white dark:bg-slate-800">
                                <div class="w-10 h-10 rounded-full bg-blue-100 text-blue-600 flex items-center justify-center flex-shrink-0">
                                    <i class="material-icons">upload_file</i>
                                </div>
                                <div>
                                    <span class="block font-bold text-slate-700 dark:text-slate-200 text-sm group-hover:text-blue-700">Bukti Transfer</span>
                                    <span class="block text-[10px] text-slate-500">Upload struk manual</span>
                                </div>
                            </button>

                            {{-- Cash --}}
                            <button type="button" id="pay-cash-btn" disabled
                                class="w-full group flex items-center gap-3 p-3 rounded-xl border border-slate-200 dark:border-slate-700 hover:border-emerald-500 hover:bg-emerald-50 dark:hover:bg-emerald-900/20 transition-all text-left disabled:opacity-50 disabled:cursor-not-allowed bg-white dark:bg-slate-800">
                                <div class="w-10 h-10 rounded-full bg-emerald-100 text-emerald-600 flex items-center justify-center flex-shrink-0">
                                    <i class="material-icons">payments</i>
                                </div>
                                <div>
                                    <span class="block font-bold text-slate-700 dark:text-slate-200 text-sm group-hover:text-emerald-700">Tunai (Cash)</span>
                                    <span class="block text-[10px] text-slate-500">Titip via Sales</span>
                                </div>
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </form>
</div>

{{-- ======================================================================== --}}
{{-- MODALS SECTION (TAILWIND STYLE) --}}
{{-- ======================================================================== --}}

{{-- 1. MANUAL UPLOAD MODAL --}}
<div id="batchManualPaymentModal" class="fixed inset-0 z-[100] hidden" role="dialog" aria-modal="true">
    <div class="fixed inset-0 bg-slate-900/60 backdrop-blur-sm transition-opacity" onclick="closeModal('batchManualPaymentModal')"></div>
    <div class="fixed inset-0 z-10 flex items-center justify-center p-4">
        <div class="bg-white dark:bg-slate-800 rounded-2xl shadow-2xl w-full max-w-md modal-enter relative overflow-hidden flex flex-col max-h-[90vh]">
            
            {{-- Header --}}
            <div class="bg-slate-50 dark:bg-slate-900 px-6 py-4 border-b border-slate-100 dark:border-slate-700 flex justify-between items-center">
                <h5 class="font-bold text-slate-800 dark:text-slate-100 text-lg" id="batchManualPaymentModalTitle">Konfirmasi Pembayaran</h5>
                <button type="button" class="text-slate-400 hover:text-slate-600 dark:hover:text-slate-200" onclick="closeModal('batchManualPaymentModal')">
                    <i class="material-icons">close</i>
                </button>
            </div>

            <div class="overflow-y-auto p-6 space-y-4">
                <form id="batch-manual-form" action="{{ route('client.invoices.bulkPay.storeManual') }}" method="POST" enctype="multipart/form-data">
                    @csrf
                    <input type="hidden" name="use_credit" id="batch-manual-use-credit-hidden">
                    
                    {{-- Summary Box --}}
                    <div class="bg-blue-50 dark:bg-blue-900/20 text-blue-800 dark:text-blue-200 p-4 rounded-lg border border-blue-100 dark:border-blue-800 text-sm">
                        <div class="flex justify-between mb-1"><span>Total Pembayaran:</span><strong id="batch-modal-total-bayar">Rp 0</strong></div>
                        <div class="flex justify-between mb-2"><span>Saldo Digunakan:</span><strong id="batch-modal-kredit-dipakai" class="text-emerald-600 dark:text-emerald-400">Rp 0</strong></div>
                        <div class="border-t border-blue-200 dark:border-blue-700 my-2 pt-2 flex justify-between font-bold text-base">
                            <span>Sisa Bayar:</span>
                            <strong id="batch-modal-sisa-bayar">Rp 0</strong>
                        </div>
                    </div>

                    {{-- CASH SECTION --}}
                    <div id="batch-cash-fields" class="hidden space-y-3">
                        <div>
                            <label class="block text-xs font-bold text-slate-500 uppercase mb-2">Diterima Sales <span class="text-red-500">*</span></label>
                            <select name="user_id_sales" id="batch_user_id_sales" class="form-select select2-in-modal w-full">
                                <option value="" disabled selected>-- Pilih Sales --</option>
                                @foreach(\App\Models\User::role('sales')->get() as $sales)
                                    <option value="{{ $sales->user_id }}">{{ $sales->full_name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <input type="hidden" name="payment_method_id" id="batch_payment_method_id_cash">
                    </div>

                    {{-- TRANSFER SECTION --}}
                    <div id="batch-transfer-fields" class="hidden space-y-3">
                        <div>
                            <label class="block text-xs font-bold text-slate-500 uppercase mb-2">Metode <span class="text-red-500">*</span></label>
                            <select name="payment_method_id" id="batch_payment_method_id" class="form-select select2-in-modal w-full">
                                <option value="">-- Pilih Metode --</option>
                                @foreach($paymentMethods as $method)
                                    <option value="{{ $method->payment_method_id }}" data-config="{{ $method->required_fields_config }}">{{ $method->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        
                        <div id="batch-payment-reference-group" class="hidden">
                            <label class="block text-xs font-bold text-slate-500 uppercase mb-2">No. Referensi</label>
                            <input type="text" class="form-input w-full" name="reference_number" id="batch_reference_number">
                        </div>

                        <div id="batch-payment-proof-group" class="hidden">
                            <label class="block text-xs font-bold text-slate-500 uppercase mb-2">Bukti Bayar</label>
                            <input type="file" class="form-input w-full text-xs" name="proof_of_payment" id="batch_proof_of_payment" accept="image/jpeg,image/png,image/jpg">
                        </div>
                    </div>

                    <div class="mt-4">
                        <label class="block text-xs font-bold text-slate-500 uppercase mb-2">Jumlah Dibayar <span class="text-red-500">*</span></label>
                        <input type="text" class="form-input font-bold w-full" id="batch_payment_amount_display" placeholder="Rp 0">
                        <input type="hidden" name="payment_amount" id="batch_payment_amount">
                        <div id="batch-amount-error" class="text-red-500 text-xs mt-1 hidden"></div>
                    </div>

                    <div class="mt-4">
                        <label class="block text-xs font-bold text-slate-500 uppercase mb-2">Catatan</label>
                        <textarea name="notes" id="batch_notes" class="form-textarea w-full" rows="2"></textarea>
                    </div>
                </form>
            </div>

            {{-- Footer --}}
            <div class="bg-slate-50 dark:bg-slate-900 px-6 py-4 flex justify-end gap-3 border-t border-slate-100 dark:border-slate-700">
                <button type="button" class="px-4 py-2 border border-slate-300 dark:border-slate-600 rounded-lg text-slate-600 dark:text-slate-300 font-bold hover:bg-slate-100 dark:hover:bg-slate-800 transition" onclick="closeModal('batchManualPaymentModal')">Batal</button>
                <button type="button" onclick="document.getElementById('batch-manual-form').submit()" class="px-4 py-2 bg-indigo-600 text-white rounded-lg font-bold hover:bg-indigo-700 shadow-md transition" id="batch-submit-proof-btn">Kirim Bukti</button>
            </div>
        </div>
    </div>
</div>

{{-- 2. MIDTRANS MODAL --}}
<div id="batchMidtransPaymentModal" class="fixed inset-0 z-[100] hidden" role="dialog" aria-modal="true">
    <div class="fixed inset-0 bg-slate-900/60 backdrop-blur-sm transition-opacity" onclick="closeModal('batchMidtransPaymentModal')"></div>
    <div class="fixed inset-0 z-10 flex items-center justify-center p-4">
        <div class="bg-white dark:bg-slate-800 rounded-2xl shadow-2xl w-full max-w-md modal-enter relative overflow-hidden">
            <div class="bg-slate-50 dark:bg-slate-900 px-6 py-4 border-b border-slate-100 dark:border-slate-700 flex justify-between items-center">
                <h5 class="font-bold text-slate-800 dark:text-slate-100 text-lg">Pembayaran Online</h5>
                <button type="button" class="text-slate-400 hover:text-slate-600" onclick="closeModal('batchMidtransPaymentModal')"><i class="material-icons">close</i></button>
            </div>
            
            <form id="batch-midtrans-form" action="{{ route('client.invoices.bulkPay.storeMidtrans') }}" method="POST">
                @csrf
                <div class="p-6">
                    <div class="bg-indigo-50 dark:bg-indigo-900/20 text-indigo-900 dark:text-indigo-200 p-4 rounded-lg border border-indigo-100 dark:border-indigo-800 text-sm mb-4">
                        <div class="flex justify-between mb-1"><span>Total Pembayaran:</span><strong id="midtrans-summary-total-bayar">Rp 0</strong></div>
                        <div class="flex justify-between mb-2"><span>Saldo Kredit:</span><strong id="midtrans-summary-kredit" class="text-emerald-600 dark:text-emerald-400">Rp 0</strong></div>
                        <div class="border-t border-indigo-200 dark:border-indigo-700 my-2 pt-2 flex justify-between font-bold text-base">
                            <span>Bayar Online:</span>
                            <strong id="midtrans-summary-ditagih">Rp 0</strong>
                        </div>
                    </div>
                    <p class="text-sm text-slate-500 text-center">Anda akan diarahkan ke halaman pembayaran aman Midtrans.</p>
                </div>
                
                <div class="bg-slate-50 dark:bg-slate-900 px-6 py-4 flex justify-end gap-3 border-t border-slate-100 dark:border-slate-700">
                    <button type="button" class="px-4 py-2 border border-slate-300 dark:border-slate-600 rounded-lg text-slate-600 dark:text-slate-300 font-bold hover:bg-slate-100 dark:hover:bg-slate-800" onclick="closeModal('batchMidtransPaymentModal')">Batal</button>
                    <button type="submit" class="px-4 py-2 bg-indigo-600 text-white rounded-lg font-bold hover:bg-indigo-700 shadow-md transition" id="batch-midtrans-submit-btn">Bayar Sekarang</button>
                </div>
            </form>
        </div>
    </div>
</div>

@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/autonumeric@4.6.0/dist/autoNumeric.min.js"></script>
<script type="text/javascript"
    src="{{ config('midtrans.is_production') ? 'https://app.midtrans.com/snap/snap.js' : 'https://app.sandbox.midtrans.com/snap/snap.js' }}"
    data-client-key="{{ config('midtrans.client_key') }}"></script>

<script>
    // --- UTILS ---
    function openModal(id) { document.getElementById(id).classList.remove('hidden'); }
    function closeModal(id) { document.getElementById(id).classList.add('hidden'); }
    function formatRupiah(number) { return new Intl.NumberFormat('id-ID', { style: 'currency', currency: 'IDR', minimumFractionDigits: 0 }).format(number); }

    const transferMethodId = "{{ $transferMethodId }}";
    const cashMethodId = "{{ $cashMethodId }}";

    document.addEventListener('DOMContentLoaded', function () {
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
        
        // Init AutoNumeric Utama
        const autoNumericInstance = new AutoNumeric(amountDisplay, { decimalPlaces: 0, digitGroupSeparator: '.', decimalCharacter: ',', currencySymbol: 'Rp ', currencySymbolPlacement: 'p', minimumValue: 0 });

        let currentTotalTagihan = 0;
        let currentTotalDitagih = 0;
        let currentKreditDigunakan = 0;
        let currentAmountFromInput = 0;
        let currentTotalPaymentValue = 0; 

        // --- CALCULATION LOGIC ---
        function calculateTotal() {
            currentTotalTagihan = 0;
            checkboxes.forEach(cb => {
                if (cb.checked) currentTotalTagihan += parseFloat(cb.dataset.balance);
            });
            summaryTagihan.textContent = formatRupiah(currentTotalTagihan);
            summaryTagihan.dataset.total = currentTotalTagihan;
            
            currentAmountFromInput = parseFloat(autoNumericInstance.getNumericString() || 0);
            const useCredit = useCreditCheck ? useCreditCheck.checked : false;
            
            currentTotalPaymentValue = currentAmountFromInput;
            currentKreditDigunakan = 0; 

            if (useCredit && availableBalance > 0) {
                currentKreditDigunakan = Math.min(availableBalance, currentTotalPaymentValue);
            }

            currentTotalDitagih = Math.max(0, Math.round(currentTotalPaymentValue - currentKreditDigunakan));
            summaryDitagih.textContent = formatRupiah(currentTotalDitagih);

            let isValid = true;
            let errorMessage = '';
            let isError = true;
            const hasSelection = currentTotalTagihan > 0;

            if (currentTotalPaymentValue <= 0.01 && hasSelection) {
                isValid = false;
                errorMessage = 'Jumlah pembayaran harus lebih dari 0.';
            }
            
            if (hasSelection && currentTotalPaymentValue > (currentTotalTagihan + 0.01)) {
                isError = false;
                errorMessage = 'Info: Kelebihan bayar akan menjadi saldo kredit.';
            }

            btnMidtrans.disabled = !isValid || !hasSelection;
            btnManual.disabled = !isValid || !hasSelection;
            btnCash.disabled = !isValid || !hasSelection;

            amountError.textContent = errorMessage;
            amountError.classList.toggle('hidden', !errorMessage);
            amountError.classList.toggle('text-red-500', isError);
            amountError.classList.toggle('text-emerald-500', !isError);
        }
        
        // Listeners for Calculator
        checkAll.addEventListener('change', function () {
            checkboxes.forEach(cb => { cb.checked = this.checked; });
            let totalChecked = 0;
            if(this.checked) checkboxes.forEach(c => { if (c.checked) totalChecked += parseFloat(c.dataset.balance); });
            autoNumericInstance.set(totalChecked);
            calculateTotal();
        });
        
        checkboxes.forEach(cb => {
            cb.addEventListener('change', function() {
                let totalChecked = 0;
                checkboxes.forEach(c => { if (c.checked) totalChecked += parseFloat(c.dataset.balance); });
                autoNumericInstance.set(totalChecked);
                calculateTotal();
            });
        });

        if (useCreditCheck) useCreditCheck.addEventListener('change', calculateTotal);
        amountDisplay.addEventListener('keyup', calculateTotal);
        amountDisplay.addEventListener('change', calculateTotal);
        
        // Init Calc
        calculateTotal();

        // =====================================================================
        // MANUAL MODAL LOGIC
        // =====================================================================
        const manualTitle = document.getElementById('batchManualPaymentModalTitle');
        const manualMethodDropdown = document.getElementById('batch_payment_method_id');
        const manualCashFields = document.getElementById('batch-cash-fields');
        const manualTransferFields = document.getElementById('batch-transfer-fields');
        const manualSalesSelect = document.getElementById('batch_user_id_sales');
        const manualProofGroup = document.getElementById('batch-payment-proof-group');
        const manualProofInput = document.getElementById('batch_proof_of_payment');
        const manualReferenceGroup = document.getElementById('batch-payment-reference-group');
        const manualReferenceInput = document.getElementById('batch_reference_number');
        
        const manualAmountDisplay = document.getElementById('batch_payment_amount_display');
        const manualAmountHidden = document.getElementById('batch_payment_amount');
        const manualAmountError = document.getElementById('batch-amount-error');
        const manualSubmitBtn = document.getElementById('batch-submit-proof-btn');
        const manualTotalBayar = document.getElementById('batch-modal-total-bayar');
        const manualKreditDipakai = document.getElementById('batch-modal-kredit-dipakai');
        const manualSisaBayar = document.getElementById('batch-modal-sisa-bayar');
        const manualUseCreditHidden = document.getElementById('batch-manual-use-credit-hidden');
        const cashMethodIdInput = document.getElementById('batch_payment_method_id_cash');
        const manualForm = document.getElementById('batch-manual-form');

        // Init Components inside Modal
        $('.select2-in-modal').select2({ theme: 'bootstrap-5', dropdownParent: $('#batchManualPaymentModal') });
        const manualAutoNumeric = new AutoNumeric(manualAmountDisplay, { decimalPlaces: 0, digitGroupSeparator: '.', decimalCharacter: ',', minimumValue: 0 });

        function handleBatchMethodConfigChange() {
            if (!manualMethodDropdown) return;
            const selectedOption = manualMethodDropdown.options[manualMethodDropdown.selectedIndex];
            const config = (selectedOption && !manualMethodDropdown.disabled) ? selectedOption.dataset.config : 'none';

            manualReferenceGroup.classList.add('hidden'); manualReferenceInput.required = false;
            manualProofGroup.classList.add('hidden'); manualProofInput.required = false;

            if (config === 'proof_only') {
                manualProofGroup.classList.remove('hidden'); manualProofInput.required = true;
            } else if (config === 'reference_only') {
                manualReferenceGroup.classList.remove('hidden'); manualReferenceInput.required = true;
            } else if (config === 'proof_and_reference') {
                manualProofGroup.classList.remove('hidden'); manualProofInput.required = true;
                manualReferenceGroup.classList.remove('hidden'); manualReferenceInput.required = true;
            }
        }
        if (manualMethodDropdown) manualMethodDropdown.addEventListener('change', handleBatchMethodConfigChange);

        function validateManualForm() {
            const rawValue = manualAutoNumeric.getNumericString();
            manualAmountHidden.value = rawValue;
            let isValid = true;
            let errorMessage = '';
            
            if(Math.abs(parseFloat(rawValue) - currentTotalDitagih) > 0.01) {
                isValid = false;
                errorMessage = 'Jumlah bayar tidak cocok dengan sisa bayar (Rp ' + formatRupiah(currentTotalDitagih) + ').';
            }
            if(parseFloat(rawValue) <= 0 && currentTotalDitagih > 0.01) {
                 isValid = false; errorMessage = 'Jumlah bayar tidak boleh 0.';
            }
            
            manualAmountError.textContent = errorMessage;
            manualAmountError.classList.toggle('hidden', !errorMessage);
            manualSubmitBtn.disabled = !isValid;
        }
        manualAmountDisplay.addEventListener('keyup', validateManualForm);
        manualAmountDisplay.addEventListener('change', validateManualForm);

        // Open Manual Transfer
        btnManual.addEventListener('click', function() {
            manualTitle.textContent = 'Upload Bukti Transfer Batch';
            manualCashFields.classList.add('hidden');
            manualTransferFields.classList.remove('hidden');
            manualSalesSelect.required = false;
            manualMethodDropdown.disabled = false;
            manualMethodDropdown.value = transferMethodId; 
            
            // Set Values
            manualTotalBayar.textContent = formatRupiah(currentTotalPaymentValue);
            manualKreditDipakai.textContent = formatRupiah(currentKreditDigunakan);
            manualSisaBayar.textContent = formatRupiah(currentTotalDitagih);
            manualAutoNumeric.set(currentTotalDitagih);
            manualUseCreditHidden.value = (useCreditCheck && useCreditCheck.checked) ? '1' : '0';
            
            // Trigger events
            const event = new Event('change');
            manualMethodDropdown.dispatchEvent(event); 
            handleBatchMethodConfigChange();
            validateManualForm();
            openModal('batchManualPaymentModal');
        });

        // Open Cash
        btnCash.addEventListener('click', function() {
            manualTitle.textContent = 'Lapor Bayar Tunai Batch';
            manualTransferFields.classList.add('hidden');
            manualCashFields.classList.remove('hidden');
            manualSalesSelect.required = true;
            cashMethodIdInput.value = cashMethodId;
            
            manualTotalBayar.textContent = formatRupiah(currentTotalPaymentValue);
            manualKreditDipakai.textContent = formatRupiah(currentKreditDigunakan);
            manualSisaBayar.textContent = formatRupiah(currentTotalDitagih);
            manualAutoNumeric.set(currentTotalDitagih);
            manualUseCreditHidden.value = (useCreditCheck && useCreditCheck.checked) ? '1' : '0';

            validateManualForm();
            openModal('batchManualPaymentModal');
        });

        // Submit Handler Manual (Inject Checkboxes)
        manualForm.addEventListener('submit', function() {
            manualMethodDropdown.disabled = false; // Enable to send value
            manualForm.querySelectorAll('input[name="invoice_ids[]"]').forEach(el => el.remove());
            checkboxes.forEach(cb => {
                if (cb.checked) {
                    const hiddenInput = document.createElement('input');
                    hiddenInput.type = 'hidden'; hiddenInput.name = 'invoice_ids[]'; hiddenInput.value = cb.value;
                    manualForm.appendChild(hiddenInput);
                }
            });
        });

        // =====================================================================
        // MIDTRANS LOGIC
        // =====================================================================
        const midtransForm = document.getElementById('batch-midtrans-form');
        const midtransSubmitBtn = document.getElementById('batch-midtrans-submit-btn');

        btnMidtrans.addEventListener('click', function() {
            document.getElementById('midtrans-summary-total-bayar').textContent = formatRupiah(currentTotalPaymentValue);
            document.getElementById('midtrans-summary-kredit').textContent = formatRupiah(currentKreditDigunakan);
            document.getElementById('midtrans-summary-ditagih').textContent = formatRupiah(currentTotalDitagih);
            
            if (currentTotalDitagih <= 0.01 && currentKreditDigunakan > 0) {
                 document.getElementById('midtrans-summary-ditagih').textContent += " (Lunas dengan Saldo)";
            }
            openModal('batchMidtransPaymentModal');
        });

        midtransForm.addEventListener('submit', function(event) {
            event.preventDefault();
            midtransForm.querySelectorAll('input[name="invoice_ids[]"]').forEach(el => el.remove());
            midtransForm.querySelectorAll('input[name="use_credit"]').forEach(el => el.remove());
            midtransForm.querySelectorAll('input[name="amount"]').forEach(el => el.remove());

            checkboxes.forEach(cb => {
                if (cb.checked) {
                    const hiddenInput = document.createElement('input');
                    hiddenInput.type = 'hidden'; hiddenInput.name = 'invoice_ids[]'; hiddenInput.value = cb.value;
                    midtransForm.appendChild(hiddenInput);
                }
            });
            
            const creditInput = document.createElement('input');
            creditInput.type = 'hidden'; creditInput.name = 'use_credit';
            creditInput.value = (useCreditCheck && useCreditCheck.checked) ? '1' : '0';
            midtransForm.appendChild(creditInput);
            
            const amountInput = document.createElement('input');
            amountInput.type = 'hidden'; amountInput.name = 'amount';
            amountInput.value = currentAmountFromInput;
            midtransForm.appendChild(amountInput);

            const csrfToken = '{{ csrf_token() }}';
            const formData = new FormData(this);
            midtransSubmitBtn.disabled = true;
            midtransSubmitBtn.innerHTML = 'Memproses...';

            fetch(this.action, {
                method: 'POST',
                headers: { 'X-CSRF-TOKEN': csrfToken, 'Accept': 'application/json' },
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
                        onError: function(result){ alert("Pembayaran gagal!"); midtransSubmitBtn.disabled = false; midtransSubmitBtn.innerHTML = 'Bayar Sekarang'; },
                        onClose: function(){ midtransSubmitBtn.disabled = false; midtransSubmitBtn.innerHTML = 'Bayar Sekarang'; }
                    });
                } else {
                    throw new Error(data.message || 'Gagal token.');
                }
            })
            .catch(error => {
                alert('Error: ' + error.message);
                midtransSubmitBtn.disabled = false;
                midtransSubmitBtn.innerHTML = 'Bayar Sekarang';
            });
        });
    });
</script>
@endpush
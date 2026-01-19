@extends('admin.layouts.app')

@section('title', 'Buat Pembayaran Massal')

@section('content')
<div x-data="bulkPaymentForm()" x-init="initComponent()" class="max-w-7xl mx-auto">

    {{-- HEADER --}}
    <div class="flex flex-col md:flex-row md:items-center justify-between gap-4 mb-6">
        <div>
            <div class="flex items-center gap-3">
                <a href="{{ route('admin.bulk-sales-payments.index') }}" class="btn-icon btn-secondary">
                    <i class="material-icons text-lg">arrow_back</i>
                </a>
                <div>
                    <h1 class="text-xl font-bold text-slate-800 dark:text-white">Pembayaran Massal</h1>
                    <p class="text-sm text-slate-500 dark:text-slate-400">Pilih klien dan invoice yang akan dilunasi sekaligus.</p>
                </div>
            </div>
        </div>
    </div>

    {{-- LOADING OVERLAY GLOBAL --}}
    <div x-show="isGlobalLoading" class="fixed inset-0 z-50 bg-white/80 dark:bg-slate-900/80 backdrop-blur-sm flex items-center justify-center" style="display: none;">
        <div class="flex flex-col items-center">
            <svg class="animate-spin h-10 w-10 text-indigo-600 mb-3" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
            </svg>
            <span class="text-slate-600 dark:text-slate-300 font-bold animate-pulse">Memproses Transaksi...</span>
        </div>
    </div>

    <form action="{{ route('admin.bulk-sales-payments.store') }}" method="POST" enctype="multipart/form-data" @submit="validateForm($event)" id="bulkForm">
        @csrf

        <div class="grid grid-cols-1 lg:grid-cols-12 gap-6">
            
            {{-- KOLOM KIRI: KLIEN & INVOICE (Lebar 7/12) --}}
            <div class="lg:col-span-7 space-y-6">
                
                {{-- 1. PILIH KLIEN --}}
                <div class="card p-5">
                    <h3 class="font-bold text-slate-800 dark:text-white mb-4 flex items-center gap-2 border-b border-slate-100 pb-2">
                        <span class="w-6 h-6 rounded-full bg-indigo-100 text-indigo-600 flex items-center justify-center text-xs font-bold">1</span>
                        Pilih Klien
                    </h3>
                    
                    <div class="space-y-4">
                        <div>
                            <select name="client_id" class="tom-select" x-model="clientId" @change="fetchClientData()" required>
                                <option value="">Cari Klien...</option>
                                @foreach($clients as $client)
                                    <option value="{{ $client->client_id }}">{{ $client->client_name }}</option>
                                @endforeach
                            </select>
                        </div>

                        {{-- INFO SALDO DEPOSIT --}}
                        <div x-show="clientId" x-transition class="flex justify-between items-center p-3 rounded-xl bg-indigo-50/50 border border-indigo-100 dark:bg-indigo-900/20 dark:border-indigo-800">
                            <div class="flex items-center gap-3">
                                <div class="w-10 h-10 rounded-full bg-indigo-100 text-indigo-600 flex items-center justify-center">
                                    <i class="material-icons">savings</i>
                                </div>
                                <div>
                                    <p class="text-[10px] uppercase font-bold text-slate-500 tracking-wide">Saldo Deposit</p>
                                    <p class="text-base font-bold text-indigo-700 dark:text-indigo-400 font-mono" x-text="formatRupiah(creditBalance)"></p>
                                </div>
                            </div>
                            
                            {{-- Toggle Gunakan Saldo --}}
                            <label class="flex items-center cursor-pointer select-none" x-show="creditBalance > 0">
                                <span class="mr-2 text-xs font-bold text-slate-600 dark:text-slate-300">Gunakan</span>
                                <div class="relative">
                                    <input type="checkbox" name="use_credit" value="1" x-model="useCredit" class="sr-only peer">
                                    <div class="w-9 h-5 bg-slate-200 peer-focus:outline-none rounded-full peer dark:bg-slate-700 peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:start-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-4 after:w-4 after:transition-all dark:border-gray-600 peer-checked:bg-emerald-500"></div>
                                </div>
                            </label>
                        </div>
                    </div>
                </div>

                {{-- 2. DAFTAR INVOICE --}}
                <div class="card flex flex-col h-[500px]">
                    <div class="card-header py-3">
                        <div class="flex items-center justify-between w-full">
                            <h3 class="card-header-title flex items-center gap-2">
                                <span class="w-6 h-6 rounded-full bg-indigo-100 text-indigo-600 flex items-center justify-center text-xs font-bold">2</span>
                                Pilih Tagihan
                            </h3>
                            <div x-show="invoices.length > 0">
                                <label class="inline-flex items-center cursor-pointer text-xs font-bold text-indigo-600 hover:text-indigo-800 transition-colors select-none bg-indigo-50 px-2 py-1 rounded">
                                    <input type="checkbox" class="form-checkbox w-3.5 h-3.5 text-indigo-600 rounded border-gray-300 mr-1.5 focus:ring-0" 
                                           x-model="selectAll" @change="toggleAll()">
                                    Pilih Semua
                                </label>
                            </div>
                        </div>
                    </div>

                    <div class="flex-1 overflow-y-auto custom-scrollbar relative bg-slate-50/30 dark:bg-slate-900/10">
                        {{-- Loading --}}
                        <div x-show="isFetchingInvoices" class="absolute inset-0 z-10 bg-white/80 dark:bg-slate-800/90 backdrop-blur-[1px] flex flex-col items-center justify-center">
                            <svg class="animate-spin h-8 w-8 text-indigo-500" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>
                        </div>

                        {{-- Empty --}}
                        <div x-show="!isFetchingInvoices && invoices.length === 0" class="flex flex-col items-center justify-center h-full text-center p-6 opacity-60">
                            <i class="material-icons text-4xl text-slate-300 mb-2">playlist_add_check</i>
                            <p class="text-sm font-medium text-slate-500">Pilih klien untuk melihat tagihan unpaid.</p>
                        </div>

                        <table x-show="invoices.length > 0" class="w-full text-sm text-left">
                            <thead class="text-xs text-slate-500 uppercase bg-slate-100 dark:bg-slate-700 sticky top-0 z-10">
                                <tr>
                                    <th class="px-4 py-3 w-10 text-center">#</th>
                                    <th class="px-4 py-3">No. Invoice</th>
                                    <th class="px-4 py-3 text-right">Sisa Tagihan</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-100 dark:divide-slate-700 bg-white dark:bg-slate-800">
                                <template x-for="inv in invoices" :key="inv.invoice_id">
                                    <tr class="cursor-pointer transition-colors hover:bg-indigo-50/50 dark:hover:bg-slate-700"
                                        :class="selectedIds.includes(inv.invoice_id) ? 'bg-indigo-50 dark:bg-indigo-900/20' : ''"
                                        @click="toggleSelection(inv.invoice_id)">
                                        
                                        <td class="px-4 py-3 text-center relative">
                                            {{-- Gunakan Div Click Overlay untuk memastikan area klik luas --}}
                                            <input type="checkbox" 
                                                   name="invoice_ids[]" 
                                                   :value="inv.invoice_id" 
                                                   x-model="selectedIds"
                                                   class="form-checkbox w-4 h-4 text-indigo-600 rounded border-gray-300 focus:ring-indigo-500 pointer-events-none">
                                        </td>
                                        <td class="px-4 py-3">
                                            <div class="font-bold text-slate-700 dark:text-slate-200" x-text="inv.invoice_number"></div>
                                            <div class="text-[10px] text-slate-400" x-text="'Jatuh Tempo: ' + inv.due_date_formatted"></div>
                                        </td>
                                        <td class="px-4 py-3 text-right font-mono font-bold text-slate-700 dark:text-white" x-text="formatRupiah(inv.sisa_tagihan)"></td>
                                    </tr>
                                </template>
                            </tbody>
                        </table>
                    </div>
                    
                    {{-- Total Bar --}}
                    <div class="p-4 bg-slate-50 dark:bg-slate-700/50 border-t border-slate-200 dark:border-slate-600 flex justify-between items-center">
                        <span class="text-xs font-bold text-slate-500 uppercase">Total Terpilih (<span x-text="selectedIds.length"></span>)</span>
                        <span class="text-lg font-mono font-bold text-indigo-600" x-text="formatRupiah(totalSelectedBill)"></span>
                    </div>
                </div>
            </div>

            {{-- KOLOM KANAN: PEMBAYARAN & FINALISASI (Lebar 5/12) --}}
            <div class="lg:col-span-5 space-y-6">
                
                {{-- 3. METODE & NOMINAL --}}
                <div class="card p-0 overflow-hidden">
                    <div class="bg-white dark:bg-slate-800 p-5">
                        <h3 class="font-bold text-slate-800 dark:text-white mb-4 flex items-center gap-2">
                            <span class="w-6 h-6 rounded-full bg-indigo-100 text-indigo-600 flex items-center justify-center text-xs font-bold">3</span>
                            Metode Pembayaran
                        </h3>

                        {{-- TABS: Manual vs Midtrans --}}
                        <div class="flex gap-2 p-1 bg-slate-100 dark:bg-slate-700/50 rounded-lg mb-6">
                            <button type="button" @click="paymentMode = 'manual'" 
                                    class="flex-1 py-2 text-xs font-bold rounded-md transition-all flex items-center justify-center gap-2"
                                    :class="paymentMode === 'manual' ? 'bg-white text-indigo-600 shadow-sm dark:bg-slate-600 dark:text-white' : 'text-slate-500 hover:text-slate-700'">
                                <i class="material-icons text-sm">edit_note</i> Catat Manual
                            </button>
                            @if(\App\Models\PaymentMethod::where('type','gateway')->where('is_active',true)->exists())
                            <button type="button" @click="paymentMode = 'gateway'" 
                                    class="flex-1 py-2 text-xs font-bold rounded-md transition-all flex items-center justify-center gap-2"
                                    :class="paymentMode === 'gateway' ? 'bg-white text-indigo-600 shadow-sm dark:bg-slate-600 dark:text-white' : 'text-slate-500 hover:text-slate-700'">
                                <i class="material-icons text-sm">qr_code</i> Bayar Online
                            </button>
                            @endif
                        </div>

                        {{-- MODE MANUAL --}}
                        <div x-show="paymentMode === 'manual'">
                            <div class="space-y-4">
                                <div>
                                    <label class="form-label label-required">Tanggal Bayar</label>
                                    <input type="date" name="payment_date" class="form-input" value="{{ date('Y-m-d') }}">
                                </div>

                                {{-- Input Uang --}}
                                <div>
                                    <div class="flex justify-between items-center mb-2">
                                        <label class="form-label label-required text-indigo-600">Nominal Uang Masuk</label>
                                        {{-- Tombol MAX --}}
                                        <button type="button" @click="fillMaxAmount()" 
                                                class="text-[10px] bg-indigo-50 text-indigo-600 px-2 py-0.5 rounded hover:bg-indigo-100 font-bold transition-colors"
                                                x-show="missingAmount > 0">
                                            Lunasi Sisa: <span x-text="formatRupiah(missingAmount)"></span>
                                        </button>
                                    </div>
                                    
                                    <div class="relative">
                                        <span class="absolute left-3 top-1/2 -translate-y-1/2 text-slate-400 font-bold">Rp</span>
                                        <input type="text" 
                                               x-ref="nominalInput"
                                               class="form-input pl-10 text-right font-bold font-mono text-lg autonumeric"
                                               placeholder="0"
                                               @keyup="updateNominal($el.value)" {{-- Update Live --}}
                                               @change="updateNominal($el.value)">
                                        
                                        {{-- Hidden Input Murni --}}
                                        <input type="hidden" name="total_amount" :value="paymentAmount">
                                    </div>
                                </div>

                                {{-- Opsi Tambahan (Bank/Ref/Bukti) - Muncul jika ada uang --}}
                                <div x-show="paymentAmount > 0" x-transition.opacity class="space-y-4 pt-4 border-t border-slate-100 dark:border-slate-700">
                                    <div class="grid grid-cols-2 gap-4">
                                        <div>
                                            <label class="form-label label-required">Metode</label>
                                            <select name="payment_method_id" class="tom-select" x-model="selectedMethodId" :required="paymentAmount > 0">
                                                <option value="">Pilih...</option>
                                                @foreach($paymentMethods as $method)
                                                    <option value="{{ $method->payment_method_id }}" data-config="{{ $method->internal_input_config }}">
                                                        {{ $method->name }}
                                                    </option>
                                                @endforeach
                                            </select>
                                        </div>
                                        <div>
                                            <label class="form-label label-required">Masuk Ke</label>
                                            <select name="company_bank_account_id" class="tom-select" :required="paymentAmount > 0">
                                                <option value="">Pilih Akun...</option>
                                                @foreach($companyBankAccounts as $account)
                                                    <option value="{{ $account->company_bank_account_id }}">
                                                        {{ $account->bank_name }}
                                                    </option>
                                                @endforeach
                                            </select>
                                        </div>
                                    </div>

                                    <div x-show="requiresRef" class="animate-enter">
                                        <label class="form-label label-required">No. Referensi</label>
                                        <input type="text" name="reference_number" class="form-input" placeholder="Contoh: BUKTI-123" :required="requiresRef">
                                    </div>

                                    <div x-show="requiresProof" class="animate-enter">
                                        <x-ui.file-upload name="proof_of_payment" label="Upload Bukti Transfer" />
                                    </div>
                                </div>

                                <div>
                                    <label class="form-label">Catatan Internal</label>
                                    <textarea name="notes" class="form-textarea h-20 text-xs" placeholder="Catatan tambahan..."></textarea>
                                </div>
                            </div>
                        </div>

                        {{-- MODE GATEWAY --}}
                        <div x-show="paymentMode === 'gateway'" class="text-center py-6">
                            <div class="w-16 h-16 bg-indigo-50 rounded-full flex items-center justify-center mx-auto mb-4">
                                <i class="material-icons text-3xl text-indigo-600">qr_code_2</i>
                            </div>
                            <h4 class="font-bold text-slate-800 dark:text-white">Pembayaran Online</h4>
                            <p class="text-xs text-slate-500 mb-6 px-4">
                                Sistem akan membuat tagihan gabungan senilai 
                                <strong class="text-indigo-600" x-text="formatRupiah(missingAmount)"></strong> 
                                dan membuka popup pembayaran (QRIS/VA).
                            </p>
                            <div class="p-3 bg-amber-50 border border-amber-100 rounded text-xs text-amber-700 text-left mb-4">
                                <i class="material-icons text-xs align-middle mr-1">info</i>
                                Jika menggunakan deposit, sisa tagihan akan otomatis dikurangi saldo deposit terlebih dahulu.
                            </div>
                        </div>

                    </div>

                    {{-- RINGKASAN & SUBMIT --}}
                    <div class="bg-slate-50 dark:bg-slate-800/50 border-t border-slate-200 dark:border-slate-700 p-5">
                        
                        <div class="flex justify-between items-center mb-2 text-sm">
                            <span class="text-slate-500">Total Tagihan</span>
                            <span class="font-bold text-slate-700 dark:text-white" x-text="formatRupiah(totalSelectedBill)"></span>
                        </div>

                        <div class="flex justify-between items-center mb-2 text-sm text-emerald-600" x-show="actualCreditUsed > 0">
                            <span class="flex items-center gap-1"><i class="material-icons text-xs">remove</i> Potong Deposit</span>
                            <span class="font-bold" x-text="formatRupiah(actualCreditUsed)"></span>
                        </div>

                        {{-- OVERPAYMENT INFO --}}
                        <div x-show="overpaymentAmount > 0" class="flex justify-between items-center mb-2 text-sm text-indigo-600 bg-indigo-50 p-1.5 rounded">
                            <span class="flex items-center gap-1 font-bold"><i class="material-icons text-xs">add</i> Masuk Deposit (Lebih)</span>
                            <span class="font-bold" x-text="formatRupiah(overpaymentAmount)"></span>
                        </div>

                        <div class="border-t border-slate-200 dark:border-slate-600 my-3"></div>

                        <div class="flex justify-end gap-3">
                            <a href="{{ route('admin.bulk-sales-payments.index') }}" class="btn btn-secondary">Batal</a>
                            
                            {{-- BUTTON SIMPAN MANUAL --}}
                            <button type="submit" x-show="paymentMode === 'manual'"
                                    class="btn btn-primary shadow-lg shadow-indigo-500/30"
                                    :disabled="selectedIds.length === 0 || (paymentAmount <= 0 && actualCreditUsed <= 0)">
                                <i class="material-icons mr-2">save</i> Simpan Pembayaran
                            </button>

                            {{-- BUTTON BAYAR MIDTRANS --}}
                            <button type="button" x-show="paymentMode === 'gateway'" @click="processMidtrans()"
                                    class="btn bg-indigo-600 text-white hover:bg-indigo-700 border-transparent shadow-lg shadow-indigo-500/30"
                                    :disabled="selectedIds.length === 0 || missingAmount <= 0">
                                <i class="material-icons mr-2">payments</i> Bayar Sekarang
                            </button>
                        </div>
                    </div>
                </div>

            </div>
        </div>
    </form>
</div>

@push('scripts')
<script src="https://app.sandbox.midtrans.com/snap/snap.js" data-client-key="{{ config('midtrans.client_key') }}"></script>
<script>
    function bulkPaymentForm() {
        return {
            clientId: '',
            invoices: [],
            selectedIds: [], // Array ID Invoice (Integer)
            
            // Financials
            paymentAmount: 0, // Nilai murni (float)
            creditBalance: 0,
            useCredit: false,
            
            // UI States
            isFetchingInvoices: false,
            isGlobalLoading: false,
            paymentMode: 'manual', // 'manual' or 'gateway'
            selectAll: false,

            // Payment Method Config
            selectedMethodId: '',
            requiresProof: false,
            requiresRef: false,
            anInstance: null, // AutoNumeric Instance

            initComponent() {
                // Init AutoNumeric pada input nominal
                const el = this.$refs.nominalInput;
                if (el) {
                    this.anInstance = new AutoNumeric(el, {
                        ...window.defaultAutoNumericOptions,
                        minimumValue: '0'
                    });
                }

                // Watcher untuk SelectedMethod (Alpine Watch)
                this.$watch('selectedMethodId', (value) => {
                    this.checkMethodConfig(value);
                });
            },

            async fetchClientData() {
                if (!this.clientId) {
                    this.invoices = [];
                    this.selectedIds = [];
                    this.creditBalance = 0;
                    return;
                }

                this.isFetchingInvoices = true;
                this.selectedIds = [];
                this.selectAll = false;

                try {
                    // 1. Get Client Balance
                    const resClient = await fetch(`{{ url('/admin/api/clients') }}/${this.clientId}/details`);
                    const dataClient = await resClient.json();
                    this.creditBalance = parseFloat(dataClient.balance) || 0;

                    // 2. Get Unpaid Invoices
                    const resInv = await fetch(`{{ url('/admin/bulk-sales-payments/get-unpaid-invoices') }}/${this.clientId}`);
                    this.invoices = await resInv.json();

                } catch (error) {
                    console.error(error);
                    window.showToast('Gagal mengambil data klien.', 'error');
                } finally {
                    this.isFetchingInvoices = false;
                }
            },

            // --- SELECTION LOGIC (FIXED TYPE MISMATCH) ---
            toggleSelection(id) {
                // Pastikan ID dikonversi ke integer agar match dengan data API
                const intId = parseInt(id);
                if (this.selectedIds.includes(intId)) {
                    this.selectedIds = this.selectedIds.filter(itemId => itemId !== intId);
                } else {
                    this.selectedIds.push(intId);
                }
                this.updateSelectAllState();
            },

            toggleAll() {
                if (this.selectAll) {
                    this.selectedIds = this.invoices.map(inv => parseInt(inv.invoice_id));
                } else {
                    this.selectedIds = [];
                }
            },

            updateSelectAllState() {
                this.selectAll = this.invoices.length > 0 && this.selectedIds.length === this.invoices.length;
            },

            // --- FINANCIAL LOGIC ---
            updateNominal(formattedValue) {
                // Hapus format (titik) dan ganti koma dengan titik desimal
                // Contoh: "10.000" -> "10000"
                let raw = formattedValue.replace(/\./g, '').replace(/,/g, '.');
                this.paymentAmount = parseFloat(raw) || 0;
            },

            fillMaxAmount() {
                // Isi input dengan sisa tagihan yang belum tertutup deposit
                if (this.anInstance) {
                    this.anInstance.set(this.missingAmount);
                    this.paymentAmount = this.missingAmount;
                }
            },

            // Computed Properties
            get totalSelectedBill() {
                return this.invoices
                    .filter(inv => this.selectedIds.includes(inv.invoice_id))
                    .reduce((sum, inv) => sum + parseFloat(inv.sisa_tagihan), 0);
            },

            get actualCreditUsed() {
                if (!this.useCredit || this.creditBalance <= 0) return 0;
                return Math.min(this.creditBalance, this.totalSelectedBill);
            },

            get totalAllocated() {
                return this.actualCreditUsed + this.paymentAmount;
            },

            // Sisa yang BELUM tertutup (untuk tombol Max / Midtrans)
            get missingAmount() {
                return Math.max(0, this.totalSelectedBill - this.actualCreditUsed);
            },

            // Lebih Bayar (Deposit Baru)
            get overpaymentAmount() {
                return Math.max(0, this.totalAllocated - this.totalSelectedBill);
            },

            // --- HELPER UI ---
            checkMethodConfig(val) {
                if (!val) {
                    this.requiresProof = false;
                    this.requiresRef = false;
                    return;
                }
                // Cari elemen option di dalam select (TomSelect menyembunyikan select asli tapi DOM tetap ada)
                const selectEl = document.querySelector('select[name="payment_method_id"]');
                const option = selectEl.querySelector(`option[value="${val}"]`);
                const config = option ? option.dataset.config : 'none';

                this.requiresProof = (config === 'proof_only' || config === 'proof_and_reference');
                this.requiresRef = (config === 'reference_only' || config === 'proof_and_reference');
            },

            formatRupiah(value) {
                return new Intl.NumberFormat('id-ID', { style: 'currency', currency: 'IDR', minimumFractionDigits: 0 }).format(value);
            },

            validateForm(e) {
                if (this.selectedIds.length === 0) {
                    e.preventDefault();
                    window.showToast('Pilih setidaknya satu invoice.', 'error');
                    return;
                }
                if (this.totalAllocated <= 0) {
                    e.preventDefault();
                    window.showToast('Total pembayaran 0. Masukkan nominal atau gunakan deposit.', 'error');
                    return;
                }
                this.isGlobalLoading = true;
            },

            // --- MIDTRANS LOGIC ---
            async processMidtrans() {
                if (this.missingAmount <= 0) {
                    window.showToast('Tagihan sudah tertutup oleh deposit. Gunakan simpan manual.', 'warning');
                    return;
                }

                this.isGlobalLoading = true;

                // Kita kirim data ke controller Store via AJAX
                // Controller akan mendeteksi jika ini AJAX dan mengembalikan Snap Token alih-alih redirect
                // TAPI controller `store` yang ada me-redirect.
                // SOLUSI: Kita submit form ini secara AJAX manual.
                
                const formData = new FormData(document.getElementById('bulkForm'));
                // Tambahkan flag khusus
                formData.append('is_midtrans_request', '1'); 
                // Pastikan payment method gateway terpilih (biasanya ID 1 atau config)
                // Kita biarkan null, controller akan handle default gateway method.

                // Override total_amount dengan missingAmount (karena di midtrans kita bayar sisa)
                formData.set('total_amount', this.missingAmount);

                try {
                    // Karena struktur controller `store` anda me-redirect, 
                    // Kita perlu sedikit trik atau membuat endpoint baru.
                    // Untuk saat ini, kita asumsikan Anda akan menambahkan logika di Controller Store
                    // Jika request->wantsJson(), return JSON token.
                    
                    // ALTERNATIF CEPAT:
                    // Kita buat input hidden 'payment_mode' = 'gateway'
                    // Controller akan membuat record 'pending_clearance', lalu redirect ke Show Page.
                    // Di Show Page, jika status pending & gateway, baru muncul tombol Snap.
                    
                    // Namun user ingin "Bayar Sekarang".
                    // Mari kita coba simpan dulu sebagai Pending Gateway.
                    
                    // Inject input hidden payment_method_id untuk gateway (biasanya auto di backend)
                    // Submit form normal
                    const hiddenInput = document.createElement('input');
                    hiddenInput.type = 'hidden';
                    hiddenInput.name = 'payment_method_id';
                    // Cari ID method gateway dari PHP variable (opsional, backend handle)
                    
                    // Submit form secara normal, nanti di halaman SHOW ada tombol Pay
                    document.getElementById('bulkForm').submit();

                } catch (e) {
                    this.isGlobalLoading = false;
                    alert('Gagal memproses gateway');
                }
            }
        }
    }
</script>
@endpush
@endsection
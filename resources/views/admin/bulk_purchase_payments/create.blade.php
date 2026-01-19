@extends('admin.layouts.app')

@section('title', 'Buat Pembayaran Massal (Hutang)')

@section('content')
<div x-data="bulkPaymentLogic()" class="flex flex-col gap-6 pb-20">

    {{-- HEADER --}}
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div>
            <h1 class="text-2xl font-bold text-slate-800 dark:text-white tracking-tight">Pembayaran Massal (Hutang)</h1>
            <p class="text-sm text-slate-500 dark:text-slate-400 mt-1">
                Bayar beberapa Purchase Order sekaligus ke satu supplier.
            </p>
        </div>
        <a href="{{ route('admin.purchase-orders.index') }}" class="btn btn-secondary">
            <i class="material-icons text-[20px] mr-2">arrow_back</i> Kembali
        </a>
    </div>

    <form action="{{ route('admin.bulk-purchase-payments.store') }}" method="POST" enctype="multipart/form-data" id="bulkForm" @submit.prevent="submitForm">
        @csrf

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
            
            {{-- KOLOM KIRI: FILTER & TABEL TAGIHAN --}}
            <div class="lg:col-span-2 flex flex-col gap-6">
                
                {{-- 1. PILIH SUPPLIER --}}
                <div class="card p-5 border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label class="form-label label-required">Pilih Supplier</label>
                            <select name="supplier_id" id="supplier_select" class="tom-select" required>
                                <option value="">Cari Supplier...</option>
                                @foreach($suppliers as $supplier)
                                    <option value="{{ $supplier->supplier_id }}" 
                                            data-balance="{{ $supplier->balance }}">
                                        {{ $supplier->supplier_name }}
                                    </option>
                                @endforeach
                            </select>
                            {{-- Info Saldo --}}
                            <div class="mt-2 text-xs" x-show="supplierId" x-transition>
                                <span class="text-slate-500">Saldo Deposit Tersedia:</span>
                                <span class="font-bold text-emerald-600 font-mono" x-text="formatRupiah(currentSupplierBalance)"></span>
                            </div>
                        </div>

                        <div>
                            <label class="form-label label-required">Tanggal Pembayaran</label>
                            <input type="date" name="payment_date" class="form-input" value="{{ date('Y-m-d') }}" required>
                        </div>
                    </div>
                </div>

                {{-- 2. DAFTAR TAGIHAN (PO) --}}
                <div class="card border border-slate-200 dark:border-slate-700 overflow-hidden relative min-h-[200px]">
                    
                    {{-- Loading State --}}
                    <div x-show="isLoading" class="absolute inset-0 bg-white/80 dark:bg-slate-800/80 z-20 flex flex-col items-center justify-center backdrop-blur-sm">
                        <div class="w-8 h-8 border-4 border-indigo-200 border-t-indigo-600 rounded-full animate-spin mb-2"></div>
                        <span class="text-xs font-bold text-indigo-600">Memuat Data...</span>
                    </div>

                    <div class="px-5 py-3 border-b border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-800/50 flex justify-between items-center">
                        <h3 class="font-bold text-slate-700 dark:text-white flex items-center gap-2">
                            <i class="material-icons text-slate-400 text-sm">receipt_long</i> Daftar Tagihan Belum Lunas
                        </h3>
                        
                        <div x-show="invoices.length > 0">
                            <label class="inline-flex items-center cursor-pointer">
                                <input type="checkbox" @change="toggleSelectAll($event)" class="form-check-input rounded text-indigo-600">
                                <span class="ml-2 text-xs font-bold text-slate-600">Pilih Semua</span>
                            </label>
                        </div>
                    </div>

                    <div class="overflow-x-auto">
                        <table class="w-full text-sm text-left">
                            <thead class="text-xs text-slate-500 uppercase bg-white dark:bg-slate-800 border-b border-slate-200 dark:border-slate-700">
                                <tr>
                                    <th class="px-5 py-3 w-10 text-center">#</th>
                                    <th class="px-5 py-3">No. PO</th>
                                    <th class="px-5 py-3">Jatuh Tempo</th>
                                    <th class="px-5 py-3 text-right">Sisa Tagihan</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-100 dark:divide-slate-700">
                                <template x-for="inv in invoices" :key="inv.po_id">
                                    <tr class="hover:bg-indigo-50/30 transition-colors cursor-pointer" 
                                        @click="toggleSelection(inv.po_id)"
                                        :class="selectedInvoiceIds.includes(inv.po_id) ? 'bg-indigo-50/60 dark:bg-indigo-900/20' : ''">
                                        
                                        <td class="px-5 py-3 text-center">
                                            <input type="checkbox" name="po_ids[]" :value="inv.po_id" 
                                                   x-model="selectedInvoiceIds"
                                                   @click.stop="toggleSelection(inv.po_id)"
                                                   :checked="selectedInvoiceIds.includes(inv.po_id)"
                                                   class="form-check-input rounded text-indigo-600 w-4 h-4 cursor-pointer">
                                        </td>
                                        
                                        <td class="px-5 py-3 font-medium text-slate-700 dark:text-slate-200">
                                            <span x-text="inv.po_number"></span>
                                        </td>
                                        
                                        <td class="px-5 py-3 text-slate-500">
                                            <span x-text="inv.due_date_formatted"></span>
                                        </td>
                                        
                                        <td class="px-5 py-3 text-right font-mono font-bold text-rose-600">
                                            <span x-text="formatRupiah(inv.sisa_tagihan)"></span>
                                        </td>
                                    </tr>
                                </template>
                                
                                <tr x-show="invoices.length === 0 && !isLoading">
                                    <td colspan="4" class="px-5 py-10 text-center text-slate-400 italic">
                                        <span x-show="!supplierId">Silakan pilih supplier terlebih dahulu.</span>
                                        <span x-show="supplierId">Tidak ada tagihan yang belum lunas untuk supplier ini.</span>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            {{-- KOLOM KANAN: KALKULASI & PEMBAYARAN --}}
            <div class="flex flex-col gap-6">
                
                {{-- CARD KALKULASI --}}
                <div class="card p-5 border border-indigo-100 dark:border-indigo-900 shadow-lg relative overflow-hidden bg-white dark:bg-slate-800">
                    <div class="absolute top-0 left-0 w-1 h-full bg-indigo-500"></div>
                    
                    <h3 class="font-bold text-slate-700 dark:text-white mb-5 flex items-center gap-2">
                        <i class="material-icons text-indigo-500 text-sm">calculate</i> Rincian Pembayaran
                    </h3>

                    <div class="space-y-4">
                        {{-- Total Tagihan Terpilih --}}
                        <div class="flex justify-between items-center text-sm">
                            <span class="text-slate-500">Total Tagihan Dipilih</span>
                            <span class="font-mono font-bold text-slate-700 dark:text-white" x-text="formatRupiah(totalSelectedBill)"></span>
                        </div>

                        {{-- Opsi Deposit --}}
                        <div class="p-3 bg-amber-50 dark:bg-amber-900/20 rounded-lg border border-amber-100 dark:border-amber-800 transition-all"
                             x-show="currentSupplierBalance > 0">
                            <label class="flex items-center justify-between cursor-pointer">
                                <div class="flex items-center gap-2">
                                    <input type="checkbox" name="use_debit_balance" value="1" x-model="useDeposit" class="form-check-input text-amber-600 rounded focus:ring-amber-500">
                                    <span class="text-sm font-bold text-amber-800 dark:text-amber-300">Potong Deposit</span>
                                </div>
                                <span class="text-xs font-mono text-amber-600" x-text="'- ' + formatRupiah(depositUsage)"></span>
                            </label>
                        </div>

                        <div class="border-t border-dashed border-slate-200 dark:border-slate-700"></div>

                        {{-- Input Nominal Bayar --}}
                        <div>
                            <label class="form-label text-xs mb-1">Nominal Transfer / Cash Input</label>
                            <div class="flex rounded-lg shadow-sm">
                                <span class="inline-flex items-center px-3 rounded-l-lg border border-r-0 border-slate-300 bg-slate-50 text-slate-500 text-sm font-bold">Rp</span>
                                <input type="text" name="total_amount" id="total_amount_input"
                                       class="form-input rounded-l-none text-right font-mono font-bold text-lg"
                                       placeholder="0">
                            </div>
                            <div class="flex justify-end mt-1">
                                <button type="button" @click="setFullAmount()" class="text-[10px] text-indigo-600 font-bold hover:underline uppercase">
                                    Bayar Lunas (Sisa)
                                </button>
                            </div>
                        </div>

                        {{-- Info Overpayment --}}
                        <div x-show="overpayment > 0" x-transition class="p-3 bg-emerald-50 text-emerald-800 text-xs rounded-lg border border-emerald-200 flex gap-2">
                            <i class="material-icons text-sm">info</i>
                            <div>
                                <strong>Kelebihan Bayar: <span x-text="formatRupiah(overpayment)"></span></strong>
                                <p class="mt-0.5 opacity-80">Dana sisa ini akan otomatis masuk ke <strong>Deposit Supplier</strong>.</p>
                            </div>
                        </div>

                    </div>
                </div>

                {{-- CARD METODE PEMBAYARAN --}}
                <div class="card p-5 border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800"
                     x-show="rawAmount > 0" x-transition>
                    
                    <h3 class="font-bold text-slate-700 dark:text-white mb-4 text-sm uppercase tracking-wider">Sumber Dana (Transfer/Kas)</h3>

                    <div class="space-y-4">
                        <div>
                            <label class="form-label label-required">Metode Pembayaran</label>
                            <select name="payment_method_id" id="payment_method_select" class="tom-select w-full">
                                <option value="">Pilih Metode...</option>
                                @foreach($paymentMethods as $pm)
                                    <option value="{{ $pm->payment_method_id }}" data-config="{{ $pm->internal_input_config }}">
                                        {{ $pm->name }}
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        <div>
                            <label class="form-label label-required">Akun Kas/Bank (Keluar)</label>
                            <select name="company_bank_account_id" id="bank_account_select" class="tom-select w-full">
                                <option value="">Pilih Akun...</option>
                                @foreach($companyBankAccounts as $bank)
                                    <option value="{{ $bank->company_bank_account_id }}">{{ $bank->bank_name }} - {{ $bank->account_name }}</option>
                                @endforeach
                            </select>
                        </div>

                        {{-- Bukti & Ref --}}
                        <div x-show="needsReference || needsProof" class="p-3 bg-slate-50 dark:bg-slate-900/50 rounded border border-slate-200 dark:border-slate-700 space-y-3">
                            <div x-show="needsReference">
                                <label class="form-label text-xs">No. Referensi</label>
                                <input type="text" name="reference_number" class="form-input text-sm" placeholder="Contoh: TRF-12345" :required="needsReference">
                            </div>
                            
                            <div x-show="needsProof">
                                <label class="form-label text-xs">Bukti Transfer (Gambar)</label>
                                <input type="file" name="proof_of_payment" class="block w-full text-xs text-slate-500
                                  file:mr-4 file:py-2 file:px-4
                                  file:rounded-full file:border-0
                                  file:text-xs file:font-semibold
                                  file:bg-indigo-50 file:text-indigo-700
                                  hover:file:bg-indigo-100" :required="needsProof">
                            </div>
                        </div>
                    </div>
                </div>
                
                {{-- Catatan --}}
                <div class="card p-5 border border-slate-200 dark:border-slate-700">
                    <label class="form-label">Catatan (Opsional)</label>
                    <textarea name="notes" rows="2" class="form-textarea w-full text-sm" placeholder="Catatan internal..."></textarea>
                </div>

                {{-- Submit Button --}}
                <button type="submit" class="btn btn-primary btn-lg w-full shadow-xl shadow-indigo-500/20" 
                        :disabled="!isValid || isLoading">
                    <i class="material-icons mr-2">send</i> Proses Pembayaran
                </button>

            </div>
        </div>
        
        {{-- Hidden Input untuk kirim Invoice IDs yang dipilih ke Controller --}}
        <template x-for="id in selectedInvoiceIds" :key="id">
            <input type="hidden" name="po_ids[]" :value="id">
        </template>

    </form>
</div>

@push('scripts')
<script>
    document.addEventListener('alpine:init', () => {
        Alpine.data('bulkPaymentLogic', () => ({
            supplierId: '',
            currentSupplierBalance: 0,
            invoices: [],
            selectedInvoiceIds: [],
            isLoading: false,
            
            // Payment Logic
            rawAmount: 0,
            useDeposit: false,
            
            // Config Payment Method
            selectedMethodId: '',
            selectedBankId: '', // [FIX] Tambahkan state ini untuk validasi bank
            needsProof: false,
            needsReference: false,

            init() {
                // Init Tom Select Supplier
                const supplierSelect = document.getElementById('supplier_select');
                new TomSelect(supplierSelect, {
                    ...window.defaultTomSelectConfig,
                    onChange: (val) => {
                        this.supplierId = val;
                        const option = supplierSelect.querySelector(`option[value="${val}"]`);
                        this.currentSupplierBalance = option ? parseFloat(option.dataset.balance) : 0;
                        this.fetchInvoices();
                    }
                });

                // Init AutoNumeric
                const inputEl = document.getElementById('total_amount_input');
                if(inputEl) {
                    const an = new AutoNumeric(inputEl, { ...window.defaultAutoNumericOptions, minimumValue: '0' });
                    inputEl.addEventListener('autoNumeric:rawValueModified', e => {
                        this.rawAmount = parseFloat(e.detail.newRawValue) || 0;
                    });
                }

                // Init Tom Select Method
                const methodSelect = document.getElementById('payment_method_select');
                if(methodSelect) {
                    new TomSelect(methodSelect, {
                        ...window.defaultTomSelectConfig,
                        onChange: (val) => {
                            this.selectedMethodId = val;
                            this.checkMethodConfig();
                        }
                    });
                }
                
                // Init Tom Select Bank
                const bankSelect = document.getElementById('bank_account_select');
                if(bankSelect) {
                    new TomSelect(bankSelect, {
                        ...window.defaultTomSelectConfig,
                        onChange: (val) => {
                            this.selectedBankId = val; // [FIX] Update state saat bank berubah
                        }
                    });
                }
            },

            async fetchInvoices() {
                if (!this.supplierId) {
                    this.invoices = [];
                    this.selectedInvoiceIds = [];
                    return;
                }

                this.isLoading = true;
                this.selectedInvoiceIds = []; 

                try {
                    const response = await fetch(`/admin/bulk-purchase-payments/get-unpaid-pos/${this.supplierId}`);
                    const data = await response.json();
                    this.invoices = data;
                } catch (error) {
                    console.error('Error fetching invoices:', error);
                    window.showToast('Gagal mengambil data tagihan', 'error');
                } finally {
                    this.isLoading = false;
                }
            },

            toggleSelection(id) {
                if (this.selectedInvoiceIds.includes(id)) {
                    this.selectedInvoiceIds = this.selectedInvoiceIds.filter(i => i !== id);
                } else {
                    this.selectedInvoiceIds.push(id);
                }
            },

            toggleSelectAll(e) {
                if (e.target.checked) {
                    this.selectedInvoiceIds = this.invoices.map(inv => inv.po_id);
                } else {
                    this.selectedInvoiceIds = [];
                }
            },

            // --- COMPUTED PROPERTIES ---

            get totalSelectedBill() {
                return this.invoices
                    .filter(inv => this.selectedInvoiceIds.includes(inv.po_id))
                    .reduce((sum, inv) => sum + parseFloat(inv.sisa_tagihan), 0);
            },

            get depositUsage() {
                if (!this.useDeposit) return 0;
                return Math.min(this.currentSupplierBalance, this.totalSelectedBill);
            },

            get remainingBillAfterDeposit() {
                return Math.max(0, this.totalSelectedBill - this.depositUsage);
            },

            get overpayment() {
                const totalAvailable = this.rawAmount + this.depositUsage;
                if(this.totalSelectedBill <= 0) return 0;
                return Math.max(0, totalAvailable - this.totalSelectedBill);
            },

            get isValid() {
                if (this.selectedInvoiceIds.length === 0) return false;
                
                const totalPay = this.depositUsage + this.rawAmount;
                if (totalPay <= 0) return false;

                // [FIX] Validasi reaktif menggunakan state Alpine
                if (this.rawAmount > 0) {
                    if (!this.selectedMethodId || !this.selectedBankId) return false;
                }

                return true;
            },

            // --- ACTIONS ---

            setFullAmount() {
                const amountNeeded = this.remainingBillAfterDeposit;
                const inputEl = document.getElementById('total_amount_input');
                
                // 1. Update visual AutoNumeric
                if (AutoNumeric.getAutoNumericElement(inputEl)) {
                    AutoNumeric.getAutoNumericElement(inputEl).set(amountNeeded);
                }
                
                // 2. [FIX] Update state Alpine secara eksplisit agar reaktif
                this.rawAmount = parseFloat(amountNeeded);
            },

            checkMethodConfig() {
                if (!this.selectedMethodId) {
                    this.needsProof = false;
                    this.needsReference = false;
                    return;
                }
                
                const selectEl = document.getElementById('payment_method_select');
                const option = selectEl.querySelector(`option[value="${this.selectedMethodId}"]`);
                const config = option ? option.dataset.config : 'none';

                this.needsProof = ['proof_only', 'proof_and_reference'].includes(config);
                this.needsReference = ['reference_only', 'proof_and_reference'].includes(config);
            },

            async submitForm() {
                if (!this.isValid) return;
                
                // [FIX] Gunakan window.confirmDialog
                let title = 'Proses Pembayaran?';
                let text = 'Pembayaran massal akan diproses.';
                let icon = 'question';
                let confirmColor = 'primary';

                if (this.overpayment > 0) {
                    title = 'Konfirmasi Kelebihan Bayar';
                    text = `Terdapat kelebihan bayar sebesar ${this.formatRupiah(this.overpayment)} yang akan masuk ke Deposit Supplier. Lanjutkan?`;
                    icon = 'warning';
                    confirmColor = 'warning';
                }

                const result = await window.confirmDialog({
                    title: title,
                    text: text,
                    icon: icon,
                    confirmText: 'Ya, Proses',
                    cancelText: 'Batal',
                    confirmColor: confirmColor
                });
                
                if (result.isConfirmed) {
                    this.isLoading = true;
                    document.getElementById('bulkForm').submit();
                }
            },

            formatRupiah(value) {
                return new Intl.NumberFormat('id-ID', { style: 'currency', currency: 'IDR', minimumFractionDigits: 0 }).format(value || 0);
            }
        }));
    });
</script>
@endpush
@endsection
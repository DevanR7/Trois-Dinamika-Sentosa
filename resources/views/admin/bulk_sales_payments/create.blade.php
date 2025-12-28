@extends('admin.layouts.app')

@section('title', 'Buat Pembayaran Massal')

@section('content')
<div class="page-header">
    <div>
        <h1 class="page-title">Pembayaran Massal (Bulk Payment)</h1>
        <nav class="flex text-sm text-slate-500 dark:text-slate-400 mt-2">
            <a href="{{ route('admin.bulk-sales-payments.index') }}" class="hover:text-indigo-600 transition-colors">Riwayat</a>
            <span class="mx-2">/</span>
            <span class="text-slate-800 dark:text-white font-medium">Buat Baru</span>
        </nav>
    </div>
</div>

{{-- Main Container Alpine --}}
<div class="grid grid-cols-1 lg:grid-cols-3 gap-6" x-data="bulkPaymentAdmin()">
    
    {{-- Left Column: Form Selection --}}
    <div class="lg:col-span-2 space-y-6">
        
        {{-- Step 1: Pilih Klien --}}
        <div class="card">
            <div class="card-header bg-slate-50 dark:bg-slate-800/50">
                <h3 class="card-header-title">1. Pilih Pelanggan</h3>
            </div>
            <div class="card-body">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label class="form-label label-required">Pelanggan</label>
                        {{-- ✅ REVISI: Tambahkan class tom-select dan event handler --}}
                        <select id="client_id" class="tom-select" x-model="selectedClient" @change="fetchInvoices()">
                            <option value="">Pilih Pelanggan...</option>
                            @foreach($clients as $client)
                                <option value="{{ $client->client_id }}">{{ $client->client_name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="form-label label-required">Tanggal Bayar</label>
                        <input type="date" x-model="paymentDate" class="form-input">
                    </div>
                </div>

                {{-- Loader --}}
                <div x-show="loadingInvoices" class="py-8 text-center" style="display: none;">
                    <svg class="inline w-8 h-8 text-indigo-600 animate-spin" fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                    </svg>
                    <p class="text-sm text-slate-500 mt-2">Memuat tagihan...</p>
                </div>

                {{-- Invoice List --}}
                <div x-show="invoices.length > 0 && !loadingInvoices" class="mt-6" x-transition style="display: none;">
                    <div class="flex items-center justify-between mb-3">
                        <h4 class="text-sm font-bold uppercase text-slate-500">2. Pilih Tagihan (Invoice)</h4>
                        <div class="text-xs">
                            <span class="font-bold text-indigo-600" x-text="selectedInvoiceIds.length"></span> dipilih
                        </div>
                    </div>
                    
                    <div class="table-container max-h-[400px] overflow-y-auto custom-scrollbar border rounded-xl">
                        <table class="w-full text-sm text-left">
                            <thead class="text-xs text-slate-500 uppercase bg-slate-50 dark:bg-slate-800 sticky top-0 z-10">
                                <tr>
                                    <th class="px-4 py-3 text-center w-10">
                                        <input type="checkbox" @change="toggleAll($event.target.checked)" class="form-check-input">
                                    </th>
                                    <th class="px-4 py-3">No. Invoice</th>
                                    <th class="px-4 py-3">Jatuh Tempo</th>
                                    <th class="px-4 py-3 text-right">Sisa Tagihan</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-100 dark:divide-slate-700">
                                <template x-for="inv in invoices" :key="inv.invoice_id">
                                    <tr class="hover:bg-slate-50 dark:hover:bg-slate-800/50 transition-colors cursor-pointer" 
                                        @click="toggleInvoice(inv.invoice_id)">
                                        <td class="px-4 py-3 text-center" @click.stop>
                                            <input type="checkbox" :value="inv.invoice_id" x-model="selectedInvoiceIds" class="form-check-input">
                                        </td>
                                        <td class="px-4 py-3 font-medium" x-text="inv.invoice_number"></td>
                                        <td class="px-4 py-3 text-slate-500" x-text="inv.due_date_formatted"></td>
                                        <td class="px-4 py-3 text-right font-bold text-slate-700 dark:text-slate-300" x-text="formatRupiah(inv.sisa_tagihan)"></td>
                                    </tr>
                                </template>
                            </tbody>
                        </table>
                    </div>
                </div>

                <div x-show="invoices.length === 0 && !loadingInvoices && selectedClient" class="text-center py-6 text-slate-500 italic" style="display: none;">
                    Tidak ada invoice belum lunas untuk pelanggan ini.
                </div>
            </div>
        </div>

        {{-- Step 3: Metode Pembayaran --}}
        <div class="card" x-show="selectedInvoiceIds.length > 0" x-transition style="display: none;">
            <div class="card-header bg-slate-50 dark:bg-slate-800/50 justify-between items-center">
                <h3 class="card-header-title">3. Rincian Pembayaran</h3>
                
                {{-- TAB MODE SWITCHER --}}
                <div class="flex bg-white dark:bg-slate-900 rounded-lg p-1 border border-slate-200 dark:border-slate-700">
                    <button type="button" @click="changeMode('manual')" 
                        class="px-3 py-1.5 text-xs font-bold rounded-md transition-all"
                        :class="paymentMode === 'manual' ? 'bg-indigo-100 text-indigo-700 shadow-sm' : 'text-slate-500 hover:bg-slate-50'">
                        Manual / Tunai
                    </button>
                    {{-- Cek Gateway dari Backend --}}
                    @if($gatewayMethod = \App\Models\PaymentMethod::where('type', 'gateway')->where('is_active', true)->first())
                        <button type="button" @click="changeMode('online')" 
                            class="px-3 py-1.5 text-xs font-bold rounded-md transition-all"
                            :class="paymentMode === 'online' ? 'bg-indigo-100 text-indigo-700 shadow-sm' : 'text-slate-500 hover:bg-slate-50'">
                            Online / Gateway
                        </button>
                    @endif
                </div>
            </div>
            
            <div class="card-body space-y-5">
                
                {{-- Toggle Kredit --}}
                <div class="flex items-center justify-between p-4 rounded-xl border border-indigo-100 bg-indigo-50/50 dark:border-indigo-900/30 dark:bg-indigo-900/10">
                    <div class="flex items-center gap-3">
                        <div class="p-2 bg-indigo-100 text-indigo-600 rounded-lg dark:bg-indigo-900 dark:text-indigo-400">
                            <i class="material-icons">account_balance_wallet</i>
                        </div>
                        <div>
                            <div class="text-sm font-bold text-slate-700 dark:text-slate-200">Gunakan Saldo Kredit</div>
                            <div class="text-xs text-slate-500">Saldo: <span class="font-mono font-bold text-indigo-600" x-text="formatRupiah(clientBalance)"></span></div>
                        </div>
                    </div>
                    <label class="relative inline-flex items-center cursor-pointer">
                        <input type="checkbox" x-model="useCredit" class="sr-only peer">
                        <div class="w-11 h-6 bg-slate-200 peer-focus:ring-4 peer-focus:ring-indigo-300 rounded-full peer peer-checked:bg-indigo-600 after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:after:translate-x-full peer-checked:after:border-white"></div>
                    </label>
                </div>

                {{-- INPUT MANUAL FORM (Hanya tampil di mode Manual) --}}
                <div x-show="paymentMode === 'manual'" x-transition>
                    <form id="manual-form" action="{{ route('admin.bulk-sales-payments.store') }}" method="POST" enctype="multipart/form-data">
                        @csrf
                        {{-- Hidden Inputs (Disinkronkan dengan Alpine) --}}
                        <input type="hidden" name="client_id" :value="selectedClient">
                        <input type="hidden" name="payment_date" :value="paymentDate">
                        <input type="hidden" name="use_credit" :value="useCredit ? 1 : 0">
                        
                        <template x-for="id in selectedInvoiceIds" :key="id">
                            <input type="hidden" name="invoice_ids[]" :value="id">
                        </template>

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div class="md:col-span-2">
                                <label class="form-label label-required">Nominal Uang (Transfer/Cash)</label>
                                <div class="relative">
                                    <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none text-slate-500">Rp</div>
                                    <input type="text" x-model="manualAmountInput" id="input_amount_visual" 
                                           class="form-input pl-10 text-lg font-bold autonumeric" placeholder="0">
                                    {{-- Hidden input untuk kirim data bersih ke backend --}}
                                    <input type="hidden" name="total_amount" :value="parseAmount(manualAmountInput)">
                                </div>
                                {{-- Alert Overpayment --}}
                                <div x-show="overpayment > 0" class="mt-2 p-3 bg-amber-50 text-amber-700 border border-amber-200 rounded-lg text-xs flex items-start gap-2">
                                    <i class="material-icons text-sm">info</i>
                                    <span>Kelebihan <span class="font-bold" x-text="formatRupiah(overpayment)"></span> akan masuk deposit.</span>
                                </div>
                            </div>

                            {{-- Metode Pembayaran & Bank (Dynamic) --}}
                            <div x-show="parseAmount(manualAmountInput) > 0">
                                <label class="form-label label-required">Metode Pembayaran</label>
                                {{-- ✅ REVISI: Gunakan Tom Select --}}
                                <select name="payment_method_id" class="tom-select" x-model="selectedMethodId">
                                    <option value="">Pilih Metode...</option>
                                    @foreach($paymentMethods as $method)
                                        <option value="{{ $method->payment_method_id }}" data-config="{{ $method->internal_input_config }}">{{ $method->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div x-show="parseAmount(manualAmountInput) > 0">
                                <label class="form-label label-required">Masuk ke Akun</label>
                                {{-- ✅ REVISI: Gunakan Tom Select --}}
                                <select name="company_bank_account_id" class="tom-select">
                                    <option value="">Pilih Rekening...</option>
                                    @foreach($companyBankAccounts as $bank)
                                        <option value="{{ $bank->company_bank_account_id }}">{{ $bank->bank_name }} - {{ $bank->account_name }}</option>
                                    @endforeach
                                </select>
                            </div>
                            
                            {{-- Dynamic Proof & Ref --}}
                            <div class="md:col-span-1" x-show="showReferenceField">
                                <label class="form-label label-required">No. Referensi</label>
                                <input type="text" name="reference_number" class="form-input">
                            </div>
                            <div class="md:col-span-1" x-show="showProofField">
                                <label class="form-label label-required">Bukti Pembayaran</label>
                                <input type="file" name="proof_of_payment" class="form-input-file">
                            </div>
                        </div>

                        <div class="mt-4">
                            <label class="form-label">Catatan</label>
                            <textarea name="notes" class="form-textarea" rows="2"></textarea>
                        </div>
                    </form>
                </div>

                {{-- INFO ONLINE MODE --}}
                <div x-show="paymentMode === 'online'" class="text-center py-6 bg-slate-50 rounded-xl border border-dashed border-slate-300">
                    <i class="material-icons text-4xl text-indigo-300 mb-2">qr_code_scanner</i>
                    <p class="text-sm text-slate-600 font-medium">Pembayaran via Midtrans Gateway</p>
                    <p class="text-xs text-slate-400 mt-1">Total yang harus dibayar online: <span class="font-bold text-slate-700" x-text="formatRupiah(cashNeeded)"></span></p>
                </div>

            </div>
        </div>
    </div>

    {{-- Right Column: Summary --}}
    <div class="lg:col-span-1">
        <div class="card sticky top-24 border-t-4 border-t-indigo-600">
            <div class="card-header">
                <h3 class="card-header-title">Ringkasan</h3>
            </div>
            <div class="card-body space-y-4">
                <div class="flex justify-between items-center text-sm">
                    <span class="text-slate-500">Total Tagihan</span>
                    <span class="font-bold text-slate-800 dark:text-white" x-text="formatRupiah(totalSelectedDebt)"></span>
                </div>
                <div class="flex justify-between items-center text-sm">
                    <span class="text-slate-500">Potong Saldo</span>
                    <span class="font-bold text-emerald-600" x-text="'- ' + formatRupiah(creditUsed)"></span>
                </div>
                
                <hr class="border-dashed border-slate-200 dark:border-slate-700">
                
                <div class="flex justify-between items-center">
                    <span class="text-sm font-bold text-slate-500">Sisa Tagihan</span>
                    <span class="text-sm font-bold text-slate-800 dark:text-slate-300" x-text="formatRupiah(Math.max(0, totalSelectedDebt - creditUsed))"></span>
                </div>

                <div class="flex justify-between items-center pt-2 border-t border-slate-100 dark:border-slate-700">
                    <span class="text-base font-bold text-slate-800 dark:text-white">Total Bayar</span>
                    {{-- Tampilkan angka yang benar berdasarkan Mode --}}
                    <span class="text-xl font-extrabold text-indigo-600 dark:text-indigo-400" 
                          x-text="formatRupiah(paymentMode === 'manual' ? parseAmount(manualAmountInput) : cashNeeded)"></span>
                </div>

                {{-- Action Buttons --}}
                <div class="pt-4">
                    <template x-if="paymentMode === 'manual'">
                        <button type="submit" form="manual-form" class="btn btn-primary w-full justify-center" :disabled="selectedInvoiceIds.length === 0">
                            <i class="material-icons text-lg mr-1">save</i> Simpan Pembayaran
                        </button>
                    </template>

                    <template x-if="paymentMode === 'online'">
                        <button type="button" @click="payBatchOnline" class="btn btn-primary w-full justify-center" 
                            :disabled="selectedInvoiceIds.length === 0 || loadingPayment" :class="{'is-loading': loadingPayment}">
                            <i class="material-icons text-lg mr-1">payment</i> Proses Midtrans
                        </button>
                    </template>
                </div>
            </div>
        </div>
    </div>
</div>

@endsection

@push('scripts')
{{-- Load Midtrans Script --}}
@if(\App\Models\PaymentMethod::where('type', 'gateway')->where('is_active', true)->exists())
    <script src="https://app.sandbox.midtrans.com/snap/snap.js" data-client-key="{{ config('midtrans.client_key') }}"></script>
@endif

<script>
document.addEventListener('alpine:init', () => {
    Alpine.data('bulkPaymentAdmin', () => ({
        // State
        selectedClient: '',
        clients: [], 
        invoices: [],
        selectedInvoiceIds: [],
        loadingInvoices: false,
        clientBalance: 0,
        
        paymentDate: '{{ date('Y-m-d') }}',
        useCredit: false,
        paymentMode: 'manual', // 'manual' or 'online'
        
        // Manual Input State
        manualAmountInput: '0',
        selectedMethodId: '',
        
        // Computed
        totalSelectedDebt: 0,
        creditUsed: 0,
        cashNeeded: 0,
        overpayment: 0,
        loadingPayment: false,

        // Config Mapping
        methodConfigs: {
            @foreach($paymentMethods as $pm)
                '{{ $pm->payment_method_id }}': '{{ $pm->internal_input_config }}',
            @endforeach
        },

        init() {
            // Init AutoNumeric for manual input
            if (AutoNumeric) {
                new AutoNumeric('#input_amount_visual', window.defaultAutoNumericOptions);
            }
            
            // Watchers untuk kalkulasi
            this.$watch('selectedInvoiceIds', () => this.calculate(true)); // True = Auto Fill Input
            this.$watch('useCredit', () => this.calculate(true));
            // Jangan auto-fill jika user sedang mengetik manualAmountInput, tapi tetap hitung overpayment
            this.$watch('manualAmountInput', () => this.calculate(false)); 
        },

        async fetchInvoices() {
            if (!this.selectedClient) return;
            this.loadingInvoices = true;
            this.invoices = [];
            this.selectedInvoiceIds = [];
            this.clientBalance = 0;
            
            // Reset manual input
            this.updateManualInput(0);

            try {
                // Fetch invoices
                const response = await fetch(`/admin/bulk-sales-payments/get-unpaid-invoices/${this.selectedClient}`);
                const data = await response.json();
                
                // ✅ FIX: Konversi ID ke String agar cocok dengan checkbox
                this.invoices = data.map(inv => ({
                    invoice_id: String(inv.invoice_id),
                    invoice_number: inv.invoice_number,
                    due_date_formatted: inv.due_date_formatted,
                    sisa_tagihan: parseFloat(inv.sisa_tagihan)
                }));

                // Fetch saldo client
                const clientRes = await fetch(`/admin/api/clients/${this.selectedClient}/details`); 
                if(clientRes.ok) {
                    const cData = await clientRes.json();
                    this.clientBalance = parseFloat(cData.balance);
                }

            } catch (e) {
                console.error(e);
                window.showToast('Gagal memuat invoice', 'error');
            } finally {
                this.loadingInvoices = false;
                this.calculate(true);
            }
        },

        toggleAll(checked) {
            this.selectedInvoiceIds = checked ? this.invoices.map(i => i.invoice_id) : [];
        },

        toggleInvoice(id) {
            // Konversi ke string untuk konsistensi
            id = String(id);
            if (this.selectedInvoiceIds.includes(id)) {
                this.selectedInvoiceIds = this.selectedInvoiceIds.filter(i => i !== id);
            } else {
                this.selectedInvoiceIds.push(id);
            }
        },

        changeMode(mode) {
            this.paymentMode = mode;
            // Jika pindah ke manual, refresh auto-fill agar angka benar
            if (mode === 'manual') this.calculate(true);
        },

        // Logic kalkulasi utama
        calculate(autoFillInput = false) {
            // 1. Hitung Total Tagihan Terpilih
            this.totalSelectedDebt = this.invoices
                .filter(i => this.selectedInvoiceIds.includes(i.invoice_id))
                .reduce((sum, i) => sum + i.sisa_tagihan, 0);

            // 2. Hitung Kredit Digunakan
            this.creditUsed = this.useCredit ? Math.min(this.clientBalance, this.totalSelectedDebt) : 0;

            // 3. Sisa Cash Ideal (Harus dibayar)
            this.cashNeeded = Math.max(0, this.totalSelectedDebt - this.creditUsed);

            // 4. FIX: Auto-Fill Input Nominal jika dipicu checkbox
            if (autoFillInput && this.paymentMode === 'manual') {
                this.updateManualInput(this.cashNeeded);
            }

            // 5. Hitung Overpayment (Berdasarkan input manual user saat ini)
            if (this.paymentMode === 'manual') {
                let currentInput = this.parseAmount(this.manualAmountInput);
                let totalPayingPower = this.creditUsed + currentInput;
                this.overpayment = Math.max(0, totalPayingPower - this.totalSelectedDebt);
            } else {
                // Mode Online: Selalu bayar pas
                this.overpayment = 0;
            }
        },

        // Helper untuk update input AutoNumeric via JS
        updateManualInput(value) {
            this.manualAmountInput = value.toString();
            if (AutoNumeric.getAutoNumericElement('#input_amount_visual')) {
                AutoNumeric.getAutoNumericElement('#input_amount_visual').set(value);
            }
        },

        // --- Logic Midtrans Admin Bulk ---
        payBatchOnline() {
            if (this.selectedInvoiceIds.length === 0) return;
            
            window.confirmDialog({
                title: 'Proses Midtrans?',
                text: `Total Bayar Online: ${this.formatRupiah(this.cashNeeded)}`,
                icon: 'question',
                confirmButtonText: 'Ya, Proses'
            }).then((res) => {
                if(res.isConfirmed) this.executeMidtrans();
            });
        },

        executeMidtrans() {
            this.loadingPayment = true;
            
            fetch('{{ route('admin.midtrans.payBatch') }}', {
                method: 'POST',
                headers: { 
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content 
                },
                body: JSON.stringify({
                    invoice_ids: this.selectedInvoiceIds,
                    amount: this.totalSelectedDebt, // Kirim Total Tagihan Asli
                    use_credit: this.useCredit // Backend hitung ulang sisa
                })
            })
            .then(r => r.json())
            .then(data => {
                this.loadingPayment = false;
                if(data.snap_token) {
                    window.snap.pay(data.snap_token, {
                        onSuccess: () => window.location.href = "{{ route('admin.bulk-sales-payments.index') }}",
                        onPending: () => window.location.href = "{{ route('admin.bulk-sales-payments.index') }}",
                        onError: () => window.showToast('Gagal', 'error')
                    });
                } else if (data.status === 'paid_by_credit') {
                    window.location.href = "{{ route('admin.bulk-sales-payments.index') }}";
                } else {
                    window.showToast(data.message, 'error');
                }
            })
            .catch(err => {
                this.loadingPayment = false;
                console.error(err);
                window.showToast('Gagal memproses online.', 'error');
            });
        },

        // Utilities
        parseAmount(val) {
            if (!val) return 0;
            return parseFloat(val.toString().replace(/\./g, '').replace(',', '.')) || 0;
        },
        formatRupiah(num) {
            return 'Rp ' + new Intl.NumberFormat('id-ID').format(num);
        },
        
        // Dynamic Fields Visibility
        get showReferenceField() {
            let conf = this.methodConfigs[this.selectedMethodId];
            return ['reference_only', 'proof_and_reference'].includes(conf);
        },
        get showProofField() {
            let conf = this.methodConfigs[this.selectedMethodId];
            return ['proof_only', 'proof_and_reference'].includes(conf);
        }
    }));
});
</script>
@endpush
@extends('admin.layouts.app')

@section('title', 'Pembayaran Massal (Hutang Supplier)')

@section('content')
<div x-data="bulkPurchasePaymentLogic()" class="flex flex-col gap-6 pb-20">

    {{-- HEADER --}}
    <div class="flex flex-col md:flex-row justify-between items-start md:items-center gap-4">
        <div>
            <h2 class="page-title">Pembayaran Massal (Bulk Payment)</h2>
            <p class="text-sm text-slate-500 mt-1">Pilih Supplier dan beberapa PO untuk dilunasi sekaligus.</p>
        </div>
        <a href="{{ route('admin.purchase-orders.index') }}" class="btn btn-secondary">
            <i class="material-icons text-lg">arrow_back</i> Kembali
        </a>
    </div>

    {{-- ERROR SUMMARY --}}
    @if ($errors->any())
        <div class="bg-red-50 border border-red-200 text-red-600 p-4 rounded-lg text-sm animate-enter">
            <p class="font-bold mb-1">Gagal memproses pembayaran:</p>
            <ul class="list-disc pl-5">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <form action="{{ route('admin.bulk-purchase-payments.store') }}" method="POST" enctype="multipart/form-data" id="bulk-form">
        @csrf

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
            
            {{-- KOLOM KIRI: PILIH SUPPLIER & DAFTAR PO --}}
            <div class="lg:col-span-2 space-y-6">
                
                {{-- 1. Pilih Supplier --}}
                <div class="card p-5">
                    <div class="flex flex-col gap-2">
                        <label class="form-label">Pilih Supplier <span class="text-red-500">*</span></label>
                        <select name="supplier_id" 
                                x-model="supplierId" 
                                x-init="initSupplierSelect($el)"
                                class="tom-select w-full" 
                                placeholder="Cari Supplier..." required>
                            <option value="">- Cari Supplier -</option>
                            @foreach($suppliers as $supplier)
                                <option value="{{ $supplier->supplier_id }}">{{ $supplier->supplier_name }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>

                {{-- 2. Daftar PO Belum Lunas --}}
                <div class="card min-h-[400px] relative">
                    <div class="card-header flex justify-between items-center">
                        <h3 class="card-header-title">Daftar Purchase Order (Unpaid)</h3>
                        
                        {{-- Select All --}}
                        <div x-show="unpaidPos.length > 0" class="flex items-center gap-2">
                            <input type="checkbox" id="selectAll" @change="toggleSelectAll($event.target.checked)" class="rounded text-indigo-600 focus:ring-indigo-500 cursor-pointer">
                            <label for="selectAll" class="text-sm font-medium text-slate-600 cursor-pointer select-none">Pilih Semua</label>
                        </div>
                    </div>

                    <div class="card-body p-0">
                        {{-- Loading State --}}
                        <div x-show="isLoading" class="flex justify-center items-center py-20 text-slate-400">
                            <svg class="animate-spin h-8 w-8 mr-3 text-indigo-500" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                            </svg>
                            <span>Memuat data PO...</span>
                        </div>

                        {{-- Empty State --}}
                        <div x-show="!isLoading && unpaidPos.length === 0" class="text-center py-20 text-slate-400">
                            <i class="material-icons text-4xl mb-2">assignment_turned_in</i>
                            <p x-text="supplierId ? 'Tidak ada tagihan PO yang belum lunas untuk supplier ini.' : 'Silakan pilih supplier terlebih dahulu.'"></p>
                        </div>

                        {{-- Table Data --}}
                        <div x-show="!isLoading && unpaidPos.length > 0" class="overflow-x-auto">
                            <table class="table-modern w-full">
                                <thead class="bg-slate-50 dark:bg-slate-800 border-b border-slate-200">
                                    <tr>
                                        <th class="w-10 text-center">#</th>
                                        <th>No. PO</th>
                                        <th>Jatuh Tempo</th>
                                        <th class="text-right">Sisa Tagihan</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-slate-100">
                                    <template x-for="po in unpaidPos" :key="po.po_id">
                                        <tr class="hover:bg-indigo-50/50 cursor-pointer transition-colors" 
                                            :class="selectedPoIds.includes(po.po_id) ? 'bg-indigo-50 dark:bg-indigo-900/20' : ''"
                                            @click="togglePo(po.po_id)">
                                            
                                            <td class="text-center">
                                                <input type="checkbox" :value="po.po_id" x-model="selectedPoIds" 
                                                       name="po_ids[]"
                                                       class="rounded text-indigo-600 focus:ring-indigo-500 cursor-pointer pointer-events-none">
                                            </td>
                                            <td>
                                                <span class="font-bold text-slate-700 dark:text-white" x-text="po.po_number"></span>
                                                <div class="text-xs text-slate-400">ID: <span x-text="po.po_id"></span></div>
                                            </td>
                                            <td>
                                                <div class="flex items-center gap-1">
                                                    <i class="material-icons text-sm text-slate-400">calendar_today</i>
                                                    <span x-text="po.due_date_formatted"></span>
                                                </div>
                                            </td>
                                            <td class="text-right font-medium text-slate-700 dark:text-white">
                                                Rp <span x-text="formatRupiah(po.sisa_tagihan)"></span>
                                            </td>
                                        </tr>
                                    </template>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

                {{-- Catatan (Notes) --}}
                <div class="card">
                    <div class="card-body">
                        <label class="form-label">Catatan Pembayaran</label>
                        <textarea name="notes" rows="2" class="form-input w-full" placeholder="Keterangan tambahan..."></textarea>
                    </div>
                </div>

            </div>

            {{-- KOLOM KANAN: INPUT PEMBAYARAN & KALKULASI --}}
            <div class="space-y-6">
                
                {{-- Card Konfigurasi Pembayaran --}}
                <div class="card sticky top-24 border-t-4 border-t-indigo-500 shadow-lg">
                    <div class="card-header bg-slate-50 dark:bg-slate-800">
                        <h3 class="card-header-title">Rincian Pembayaran</h3>
                    </div>
                    <div class="card-body space-y-5">
                        
                        {{-- 1. Total Tagihan Terpilih --}}
                        <div class="flex justify-between items-center pb-4 border-b border-slate-100 border-dashed">
                            <span class="text-slate-500 text-sm">Total Tagihan Terpilih</span>
                            <span class="text-xl font-bold text-slate-800 dark:text-white">
                                Rp <span x-text="formatRupiah(totalSelectedDebt)"></span>
                            </span>
                        </div>

                        {{-- 2. Opsi Deposit Supplier --}}
                        <div class="bg-indigo-50 dark:bg-indigo-900/20 p-3 rounded-lg border border-indigo-100 dark:border-indigo-800">
                            <label class="flex items-start gap-3 cursor-pointer">
                                <input type="checkbox" name="use_debit_balance" value="1" x-model="useDeposit" 
                                       class="mt-1 rounded text-indigo-600 focus:ring-indigo-500">
                                <div>
                                    <span class="font-bold text-indigo-700 dark:text-indigo-300 text-sm block">Potong Deposit Supplier</span>
                                    <span class="text-xs text-indigo-600/70 dark:text-indigo-400">
                                        Saldo Tersedia: <strong>Rp <span x-text="formatRupiah(supplierBalance)"></span></strong>
                                    </span>
                                </div>
                            </label>
                            
                            {{-- Preview Pemakaian Deposit --}}
                            <div x-show="useDeposit && supplierBalance > 0" x-transition class="mt-2 text-xs flex justify-between text-indigo-800 border-t border-indigo-200 pt-2">
                                <span>Akan digunakan:</span>
                                <span class="font-bold">- Rp <span x-text="formatRupiah(calculations.depositUsed)"></span></span>
                            </div>
                        </div>

                        {{-- 3. Input Nominal Transfer --}}
                        <div>
                            <label class="form-label text-sm mb-1">Jumlah Transfer / Kas Keluar</label>
                            <div class="relative">
                                <span class="absolute left-3 top-1/2 -translate-y-1/2 text-slate-400 font-bold">Rp</span>
                                <input type="text" x-ref="amountInput" class="form-input pl-10 text-right font-bold text-lg text-emerald-600" placeholder="0">
                                {{-- Hidden Input Synced by AutoNumeric --}}
                                <input type="hidden" name="total_amount" :value="rawAmount"> 
                            </div>
                            
                            {{-- Helper Text --}}
                            <div class="flex justify-between items-center mt-1">
                                <span class="text-xs text-slate-400">Sisa Tagihan (Net):</span>
                                <span class="text-xs font-bold text-slate-600 dark:text-slate-300">Rp <span x-text="formatRupiah(calculations.netDebt)"></span></span>
                            </div>
                            <button type="button" @click="setFullPayment()" class="text-[10px] text-indigo-600 hover:underline mt-1">
                                Isi Sesuai Sisa Tagihan
                            </button>
                        </div>

                        {{-- 4. Metode & Akun Bank --}}
                        <div class="space-y-3" x-show="rawAmount > 0" x-transition>
                            <div>
                                <label class="form-label text-xs">Tanggal Bayar</label>
                                <input type="date" name="payment_date" value="{{ date('Y-m-d') }}" class="form-input text-sm w-full">
                            </div>
                            
                            {{-- REVISI: Tom Select Payment Method --}}
                            <div wire:ignore>
                                <label class="form-label text-xs">Metode Pembayaran</label>
                                <select name="payment_method_id" 
                                        x-init="initPaymentMethodSelect($el)"
                                        class="tom-select w-full" 
                                        placeholder="Pilih Metode...">
                                    <option value="">Pilih Metode...</option>
                                    @foreach($paymentMethods as $pm)
                                        <option value="{{ $pm->payment_method_id }}">{{ $pm->name }}</option>
                                    @endforeach
                                </select>
                            </div>

                            {{-- REVISI: Tom Select Company Bank Account --}}
                            <div wire:ignore>
                                <label class="form-label text-xs">Akun Kas/Bank Perusahaan</label>
                                <select name="company_bank_account_id" 
                                        x-init="initBankSelect($el)"
                                        class="tom-select w-full" 
                                        placeholder="Pilih Akun Bank...">
                                    <option value="">Pilih Akun Bank...</option>
                                    @foreach($companyBankAccounts as $ba)
                                        <option value="{{ $ba->company_bank_account_id }}">{{ $ba->bank_name }} - {{ $ba->account_name }}</option>
                                    @endforeach
                                </select>
                            </div>
                            
                            {{-- Bukti & Ref (Conditional) --}}
                            <div x-show="needsRef" x-transition>
                                <label class="form-label text-xs">No. Referensi</label>
                                <input type="text" name="reference_number" class="form-input text-sm w-full" placeholder="Cth: TRX-123">
                            </div>
                            <div x-show="needsProof" x-transition>
                                <label class="form-label text-xs">Bukti Transfer</label>
                                <input type="file" name="proof_of_payment" class="block w-full text-xs text-slate-500 file:mr-2 file:py-1 file:px-2 file:rounded-full file:border-0 file:text-xs file:font-semibold file:bg-indigo-50 file:text-indigo-700 hover:file:bg-indigo-100">
                            </div>
                        </div>

                        {{-- 5. Status Akhir (Preview) --}}
                        <div class="bg-slate-100 dark:bg-slate-900 rounded-lg p-3 text-sm space-y-2 border border-slate-200 dark:border-slate-700">
                            <div class="flex justify-between">
                                <span class="text-slate-500">Total Alokasi ke PO</span>
                                <span class="font-bold text-slate-700 dark:text-white">Rp <span x-text="formatRupiah(calculations.totalAllocated)"></span></span>
                            </div>
                            
                            {{-- Jika ada sisa lebih --}}
                            <div x-show="calculations.newDeposit > 0" class="flex justify-between text-emerald-600 font-bold border-t border-slate-200 pt-2">
                                <span>Masuk Deposit (Lebih)</span>
                                <span>Rp <span x-text="formatRupiah(calculations.newDeposit)"></span></span>
                            </div>
                            
                             {{-- Jika masih kurang --}}
                             <div x-show="calculations.remainingUnpaid > 0" class="flex justify-between text-rose-500 font-medium border-t border-slate-200 pt-2">
                                <span>Sisa Belum Lunas</span>
                                <span>Rp <span x-text="formatRupiah(calculations.remainingUnpaid)"></span></span>
                            </div>
                        </div>

                        <button type="submit" class="btn btn-primary w-full py-3 text-base shadow-lg shadow-indigo-200 dark:shadow-none"
                                :disabled="selectedPoIds.length === 0 || (rawAmount <= 0 && calculations.depositUsed <= 0)">
                            <i class="material-icons mr-2">payment</i> Proses Pembayaran
                        </button>

                    </div>
                </div>

            </div>
        </div>
    </form>

</div>

@push('scripts')
<script>
    // Konfigurasi Payment Method dari Backend
    const methodConfigs = @json($paymentMethods->pluck('required_fields_config', 'payment_method_id'));

    document.addEventListener('alpine:init', () => {
        Alpine.data('bulkPurchasePaymentLogic', () => ({
            supplierId: '',
            unpaidPos: [],
            selectedPoIds: [],
            supplierBalance: 0,
            isLoading: false,

            // Input States
            rawAmount: 0,
            useDeposit: false,
            methodId: '',
            needsProof: false,
            needsRef: false,
            anElement: null,

            init() {
                // Init AutoNumeric pada input amount
                this.anElement = new AutoNumeric(this.$refs.amountInput, {
                    ...window.defaultAutoNumericOptions,
                    minimumValue: '0',
                    unformatOnSubmit: true // Penting!
                });

                // Listen perubahan manual pada input
                this.$refs.amountInput.addEventListener('autoNumeric:rawValueModified', e => {
                    this.rawAmount = parseFloat(e.detail.newRawValue) || 0;
                });
            },

            // --- TOM SELECT INITIALIZERS ---
            
            initSupplierSelect(el) {
                if(el.tomselect) el.tomselect.destroy();
                new TomSelect(el, {
                    ...window.defaultTomSelectConfig,
                    onChange: (value) => {
                        this.supplierId = value;
                        this.fetchData();
                    }
                });
            },

            // ✅ REVISI: Init Payment Method dengan Tom Select
            initPaymentMethodSelect(el) {
                if(el.tomselect) el.tomselect.destroy();
                new TomSelect(el, {
                    ...window.defaultTomSelectConfig,
                    onChange: (value) => {
                        this.methodId = value;
                        this.updateConfig();
                    }
                });
            },

            // ✅ REVISI: Init Bank Account dengan Tom Select
            initBankSelect(el) {
                if(el.tomselect) el.tomselect.destroy();
                new TomSelect(el, window.defaultTomSelectConfig);
            },

            async fetchData() {
                if (!this.supplierId) {
                    this.unpaidPos = [];
                    this.supplierBalance = 0;
                    return;
                }

                this.isLoading = true;
                this.selectedPoIds = []; // Reset selection
                this.rawAmount = 0;
                this.anElement.set(0);

                try {
                    // 1. Fetch POs
                    const poUrl = `{{ route('admin.api.suppliers.unpaid-pos', ':id') }}`.replace(':id', this.supplierId);
                    const poRes = await fetch(poUrl);
                    this.unpaidPos = await poRes.json();

                    // 2. Fetch Supplier Details (Balance)
                    const supUrl = `{{ route('admin.api.suppliers.details', ':id') }}`.replace(':id', this.supplierId);
                    const supRes = await fetch(supUrl);
                    const supData = await supRes.json();
                    this.supplierBalance = parseFloat(supData.balance) || 0;

                } catch (error) {
                    console.error('Error fetching data:', error);
                    showToast('Gagal memuat data supplier.', 'error');
                } finally {
                    this.isLoading = false;
                }
            },

            togglePo(id) {
                if (this.selectedPoIds.includes(id)) {
                    this.selectedPoIds = this.selectedPoIds.filter(poId => poId !== id);
                } else {
                    this.selectedPoIds.push(id);
                }
            },

            toggleSelectAll(checked) {
                if (checked) {
                    this.selectedPoIds = this.unpaidPos.map(po => po.po_id);
                } else {
                    this.selectedPoIds = [];
                }
            },

            // Computed Calculations
            get totalSelectedDebt() {
                return this.unpaidPos
                    .filter(po => this.selectedPoIds.includes(po.po_id))
                    .reduce((sum, po) => sum + parseFloat(po.sisa_tagihan), 0);
            },

            get calculations() {
                const totalDebt = this.totalSelectedDebt;
                
                // 1. Hitung Deposit yang terpakai
                let depositUsed = 0;
                if (this.useDeposit && this.supplierBalance > 0) {
                    depositUsed = Math.min(this.supplierBalance, totalDebt);
                }

                // 2. Hitung Sisa Hutang (Net Debt) yang butuh transfer
                const netDebt = Math.max(0, totalDebt - depositUsed);

                // 3. Analisis Input Transfer
                const transferAmount = this.rawAmount;

                // 4. Alokasi ke Hutang
                const allocatedToDebt = Math.min(transferAmount, netDebt);

                // 5. Total Terbayar (Deposit + Transfer Alloc)
                const totalAllocated = depositUsed + allocatedToDebt;

                // 6. Sisa Belum Lunas
                const remainingUnpaid = totalDebt - totalAllocated;

                // 7. Kelebihan Bayar (Transfer - Allocated)
                const newDeposit = Math.max(0, transferAmount - netDebt);

                return {
                    depositUsed,
                    netDebt,
                    totalAllocated,
                    remainingUnpaid,
                    newDeposit
                };
            },

            // Helper: Set input transfer otomatis lunas
            setFullPayment() {
                const netDebt = this.calculations.netDebt;
                this.rawAmount = netDebt;
                this.anElement.set(netDebt);
            },

            // Helper: Config metode pembayaran
            updateConfig() {
                if (!this.methodId) {
                    this.needsProof = false;
                    this.needsRef = false;
                    return;
                }
                const config = methodConfigs[this.methodId] || 'none';
                this.needsProof = config === 'proof_only' || config === 'proof_and_reference';
                this.needsRef = config === 'reference_only' || config === 'proof_and_reference';
            },

            formatRupiah(value) {
                return new Intl.NumberFormat('id-ID', {
                    style: 'decimal',
                    minimumFractionDigits: 0,
                    maximumFractionDigits: 0
                }).format(value);
            }
        }));
    });
</script>
@endpush
@endsection
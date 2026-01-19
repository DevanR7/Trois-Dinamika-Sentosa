@extends('admin.layouts.app')

@section('title', 'Penyesuaian Manual PO ' . $purchaseOrder->po_number)

@section('content')
<div x-data="manualAdjustmentForm()" class="pb-24">

    {{-- HEADER SECTION --}}
    <div class="flex flex-col md:flex-row md:items-center justify-between gap-4 mb-8">
        <div>
            <div class="flex items-center gap-3">
                <div class="w-12 h-12 rounded-2xl bg-indigo-100 dark:bg-indigo-900/30 flex items-center justify-center text-indigo-600 dark:text-indigo-400 shadow-sm">
                    <i class="material-icons text-2xl">tune</i>
                </div>
                <div>
                    <h1 class="text-2xl font-bold text-slate-800 dark:text-white tracking-tight">Penyesuaian Manual</h1>
                    <div class="flex items-center gap-2 text-sm text-slate-500 dark:text-slate-400 mt-0.5">
                        <span>PO #{{ $purchaseOrder->po_number }}</span>
                        <span class="w-1 h-1 rounded-full bg-slate-300"></span>
                        <span>{{ $purchaseOrder->supplier->supplier_name }}</span>
                    </div>
                </div>
            </div>
        </div>
        
        <a href="{{ route('admin.purchase-order-adjustments.create') }}" class="btn btn-secondary px-4 py-2.5">
            <i class="material-icons text-sm mr-2">arrow_back</i> Kembali
        </a>
    </div>

    <form action="{{ route('admin.purchase-order-adjustments.store.manual') }}" method="POST" id="adjustmentForm" @submit.prevent="submitForm">
        @csrf
        <input type="hidden" name="purchase_order_id" value="{{ $purchaseOrder->po_id }}">

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-8 items-start">
            
            {{-- KOLOM KIRI: INPUT FORM (2/3) --}}
            <div class="lg:col-span-2 space-y-6">

                {{-- CARD 1: PILIH JENIS PENYESUAIAN --}}
                <div class="card p-0 overflow-hidden border border-slate-200 dark:border-slate-700 shadow-sm">
                    <div class="p-5 bg-slate-50/50 dark:bg-slate-800/50 border-b border-slate-100 dark:border-slate-700">
                        <h3 class="text-sm font-bold text-slate-700 dark:text-white uppercase tracking-wider flex items-center gap-2">
                            <span class="w-6 h-6 rounded-full bg-indigo-600 text-white flex items-center justify-center text-xs">1</span>
                            Tentukan Jenis Penyesuaian
                        </h3>
                    </div>
                    
                    <div class="p-6 grid grid-cols-1 md:grid-cols-2 gap-4">
                        {{-- Opsi: Credit Note --}}
                        <label class="relative group cursor-pointer">
                            <input type="radio" name="type" value="credit_note" x-model="type" class="peer sr-only">
                            <div class="h-full p-5 rounded-xl border-2 transition-all duration-200 peer-checked:border-emerald-500 peer-checked:bg-emerald-50/30 border-slate-200 hover:border-emerald-300 hover:bg-slate-50 dark:border-slate-700 dark:hover:bg-slate-800">
                                <div class="flex items-start justify-between mb-3">
                                    <div class="w-10 h-10 rounded-full bg-emerald-100 text-emerald-600 flex items-center justify-center">
                                        <i class="material-icons">trending_down</i>
                                    </div>
                                    <div x-show="type === 'credit_note'" class="text-emerald-500 animate-scale-in">
                                        <i class="material-icons">check_circle</i>
                                    </div>
                                </div>
                                <h4 class="font-bold text-slate-800 dark:text-white mb-1 group-hover:text-emerald-700 transition-colors">Credit Note</h4>
                                <p class="text-xs text-slate-500 dark:text-slate-400 leading-relaxed">
                                    Potong/Kurangi hutang kita ke supplier. 
                                    <br><span class="italic text-[10px] opacity-80">(Contoh: Diskon susulan, Kompensasi barang rusak)</span>
                                </p>
                            </div>
                        </label>

                        {{-- Opsi: Debit Note --}}
                        <label class="relative group cursor-pointer">
                            <input type="radio" name="type" value="debit_note" x-model="type" class="peer sr-only">
                            <div class="h-full p-5 rounded-xl border-2 transition-all duration-200 peer-checked:border-amber-500 peer-checked:bg-amber-50/30 border-slate-200 hover:border-amber-300 hover:bg-slate-50 dark:border-slate-700 dark:hover:bg-slate-800">
                                <div class="flex items-start justify-between mb-3">
                                    <div class="w-10 h-10 rounded-full bg-amber-100 text-amber-600 flex items-center justify-center">
                                        <i class="material-icons">trending_up</i>
                                    </div>
                                    <div x-show="type === 'debit_note'" class="text-amber-500 animate-scale-in">
                                        <i class="material-icons">check_circle</i>
                                    </div>
                                </div>
                                <h4 class="font-bold text-slate-800 dark:text-white mb-1 group-hover:text-amber-700 transition-colors">Debit Note</h4>
                                <p class="text-xs text-slate-500 dark:text-slate-400 leading-relaxed">
                                    Tambah hutang kita ke supplier.
                                    <br><span class="italic text-[10px] opacity-80">(Contoh: Koreksi harga kurang bayar, Biaya tambahan)</span>
                                </p>
                            </div>
                        </label>
                    </div>
                </div>

                {{-- CARD 2: NOMINAL & DETAIL --}}
                <div class="card p-0 overflow-hidden border border-slate-200 dark:border-slate-700 shadow-sm">
                    <div class="p-5 bg-slate-50/50 dark:bg-slate-800/50 border-b border-slate-100 dark:border-slate-700">
                        <h3 class="text-sm font-bold text-slate-700 dark:text-white uppercase tracking-wider flex items-center gap-2">
                            <span class="w-6 h-6 rounded-full bg-indigo-600 text-white flex items-center justify-center text-xs">2</span>
                            Rincian Nominal
                        </h3>
                    </div>

                    <div class="p-6 space-y-6">
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            {{-- Tanggal --}}
                            <div>
                                <label class="form-label label-required text-xs">Tanggal Dokumen</label>
                                <div class="relative">
                                    <span class="absolute left-3 top-2.5 text-slate-400"><i class="material-icons text-[18px]">calendar_today</i></span>
                                    <input type="date" name="adjustment_date" class="form-input pl-10" value="{{ date('Y-m-d') }}" required>
                                </div>
                            </div>

                            {{-- Nominal --}}
                            <div>
                                <label class="form-label label-required text-xs">Nominal Penyesuaian (Rp)</label>
                                <div class="relative">
                                    <input type="text" x-model="displayAmount" 
                                           class="form-input pr-4 pl-4 text-right font-mono text-lg font-bold text-slate-700 focus:ring-2 transition-all" 
                                           :class="type === 'credit_note' ? 'focus:ring-emerald-500 focus:border-emerald-500' : 'focus:ring-amber-500 focus:border-amber-500'"
                                           placeholder="0" required
                                           x-init="initAutoNumeric($el)">
                                    <input type="hidden" name="amount" :value="amount">
                                    <div class="absolute left-3 top-3 text-xs font-bold" 
                                         :class="type === 'credit_note' ? 'text-emerald-600' : 'text-amber-600'">
                                        <span x-text="type === 'credit_note' ? 'POTONG (-)' : 'TAMBAH (+)'"></span>
                                    </div>
                                </div>
                            </div>
                        </div>

                        {{-- OVERPAYMENT ALERT (CONDITIONAL) --}}
                        <div x-show="isOverpayment" x-transition 
                             class="p-4 rounded-xl border border-dashed border-amber-300 bg-amber-50 dark:bg-amber-900/20">
                            <div class="flex gap-3">
                                <i class="material-icons text-amber-500">warning</i>
                                <div>
                                    <h4 class="text-sm font-bold text-amber-800 dark:text-amber-400">Peringatan: Potongan Melebihi Sisa Hutang</h4>
                                    <p class="text-xs text-amber-700/80 mt-1 leading-relaxed">
                                        Total potongan (<span x-text="formatRupiah(amount)"></span>) lebih besar dari sisa hutang saat ini (<span x-text="formatRupiah(remainingBalance)"></span>).
                                        Selisih sebesar <strong x-text="formatRupiah(amount - remainingBalance)"></strong> akan dicatat sebagai:
                                    </p>
                                    
                                    <div class="mt-3 flex flex-wrap gap-4">
                                        <label class="flex items-center gap-2 cursor-pointer bg-white dark:bg-slate-800 px-3 py-2 rounded-lg border border-amber-200 shadow-sm hover:border-amber-400 transition-colors">
                                            <input type="radio" name="overpayment_action" value="deposit" class="form-check-input text-amber-600 focus:ring-amber-500" checked>
                                            <span class="text-xs font-bold text-slate-700 dark:text-slate-300">Simpan ke Deposit Supplier</span>
                                        </label>
                                        <label class="flex items-center gap-2 cursor-pointer bg-white dark:bg-slate-800 px-3 py-2 rounded-lg border border-amber-200 shadow-sm hover:border-amber-400 transition-colors">
                                            <input type="radio" name="overpayment_action" value="refund" class="form-check-input text-amber-600 focus:ring-amber-500">
                                            <span class="text-xs font-bold text-slate-700 dark:text-slate-300">Refund Tunai (Manual)</span>
                                        </label>
                                    </div>
                                </div>
                            </div>
                        </div>

                        {{-- Alasan --}}
                        <div>
                            <label class="form-label label-required text-xs">Alasan / Keterangan</label>
                            <textarea name="reason" rows="3" class="form-textarea w-full text-sm" 
                                      placeholder="Jelaskan kenapa nilai disesuaikan..." required></textarea>
                        </div>
                    </div>
                    
                    {{-- FOOTER ACTION --}}
                    <div class="p-5 bg-slate-50 dark:bg-slate-900 border-t border-slate-200 dark:border-slate-700 flex justify-end">
                        <button type="submit" class="btn btn-primary px-6 py-2.5 shadow-lg shadow-indigo-500/20 flex items-center gap-2">
                            <i class="material-icons text-[18px]">save</i> Simpan Penyesuaian
                        </button>
                    </div>
                </div>

            </div>

            {{-- KOLOM KANAN: PANEL SIMULASI (Sticky) --}}
            <div class="lg:col-span-1">
                <div class="sticky top-6 space-y-6">
                    
                    {{-- 1. Snapshot PO --}}
                    <div class="card p-5 border border-slate-200 dark:border-slate-700 shadow-sm">
                        <h3 class="text-xs font-bold text-slate-400 uppercase tracking-wider mb-4 border-b border-slate-100 dark:border-slate-700 pb-2">
                            Snapshot PO Saat Ini
                        </h3>
                        
                        <div class="space-y-3 text-sm">
                            <div class="flex justify-between">
                                <span class="text-slate-500">Total Tagihan Awal</span>
                                <span class="font-mono font-bold text-slate-700 dark:text-white">
                                    {{ number_format($purchaseOrder->grand_total, 0, ',', '.') }}
                                </span>
                            </div>
                            <div class="flex justify-between">
                                <span class="text-slate-500">Sudah Dibayar</span>
                                <span class="font-mono text-emerald-600">
                                    - {{ number_format($purchaseOrder->amount_paid, 0, ',', '.') }}
                                </span>
                            </div>
                            <div class="flex justify-between">
                                <span class="text-slate-500">Total Retur</span>
                                <span class="font-mono text-rose-500">
                                    - {{ number_format($purchaseOrder->total_returned, 0, ',', '.') }}
                                </span>
                            </div>
                            
                            {{-- Tampilkan Adjustment Sebelumnya jika ada --}}
                            @php
                                $prevDebit = $purchaseOrder->adjustments->where('type', 'debit_note')->sum('amount');
                                $prevCredit = $purchaseOrder->adjustments->where('type', 'credit_note')->sum('amount');
                            @endphp
                            
                            @if($prevDebit > 0)
                            <div class="flex justify-between">
                                <span class="text-slate-500">Adj. Debit (Tambah)</span>
                                <span class="font-mono text-amber-600">+ {{ number_format($prevDebit, 0, ',', '.') }}</span>
                            </div>
                            @endif
                            
                            @if($prevCredit > 0)
                            <div class="flex justify-between">
                                <span class="text-slate-500">Adj. Credit (Potong)</span>
                                <span class="font-mono text-emerald-600">- {{ number_format($prevCredit, 0, ',', '.') }}</span>
                            </div>
                            @endif

                            <div class="pt-3 border-t border-dashed border-slate-200 dark:border-slate-700 mt-2">
                                <div class="flex justify-between items-center">
                                    <span class="text-xs font-bold uppercase text-slate-600">Sisa Hutang Saat Ini</span>
                                    <span class="text-base font-mono font-bold text-indigo-600">
                                        Rp {{ number_format($purchaseOrder->remaining_balance, 0, ',', '.') }}
                                    </span>
                                </div>
                            </div>
                        </div>
                    </div>

                    {{-- 2. Simulasi Perubahan --}}
                    <div class="card p-5 border-l-4 transition-colors duration-300 shadow-md"
                         :class="type === 'credit_note' ? 'border-l-emerald-500 bg-emerald-50/30' : 'border-l-amber-500 bg-amber-50/30'">
                        
                        <h3 class="text-xs font-bold uppercase tracking-wider mb-2" 
                            :class="type === 'credit_note' ? 'text-emerald-700' : 'text-amber-700'">
                            Simulasi Setelah Disimpan
                        </h3>

                        <div class="flex justify-between items-center mb-1">
                            <span class="text-xs text-slate-500">Efek ke Hutang:</span>
                            <span class="font-bold text-sm" 
                                  :class="type === 'credit_note' ? 'text-emerald-600' : 'text-amber-600'"
                                  x-text="type === 'credit_note' ? 'BERKURANG' : 'BERTAMBAH'">
                            </span>
                        </div>

                        <div class="mt-3 pt-3 border-t border-slate-200/50">
                            <span class="text-[10px] uppercase text-slate-400 font-bold block mb-1">Estimasi Sisa Hutang Baru</span>
                            <div class="text-2xl font-bold font-mono text-slate-800 dark:text-white">
                                <span x-text="formatRupiah(newBalance)"></span>
                            </div>
                            
                            {{-- Helper Text --}}
                            <p class="text-[10px] mt-1 italic transition-all" 
                               x-show="newBalance < 0" 
                               x-transition>
                                <span class="text-emerald-600 font-bold flex items-center gap-1">
                                    <i class="material-icons text-[12px]">savings</i> 
                                    Overpayment! Masuk Deposit Supplier.
                                </span>
                            </p>
                        </div>
                    </div>

                </div>
            </div>

        </div>
    </form>
</div>

@push('scripts')
<script>
    function manualAdjustmentForm() {
        return {
            type: 'credit_note', // Default
            amount: 0,
            displayAmount: '',
            remainingBalance: {{ $purchaseOrder->remaining_balance }}, // Diambil dari controller
            
            initAutoNumeric(el) {
                if (AutoNumeric.getAutoNumericElement(el)) return;
                const an = new AutoNumeric(el, {
                    ...window.defaultAutoNumericOptions,
                    minimumValue: '0',
                    modifyValueOnWheel: false
                });
                el.addEventListener('autoNumeric:rawValueModified', e => {
                    this.amount = parseFloat(e.detail.newRawValue) || 0;
                });
            },

            // Logika Overpayment (Hanya jika Potong Hutang > Sisa Hutang)
            get isOverpayment() {
                if (this.type !== 'credit_note') return false;
                return this.amount > (this.remainingBalance + 0.01); 
            },

            // Hitung Estimasi Saldo Baru
            get newBalance() {
                let balance = this.remainingBalance;
                if (this.type === 'credit_note') {
                    // Credit Note mengurangi hutang
                    return balance - this.amount; 
                } else {
                    // Debit Note menambah hutang
                    return balance + this.amount;
                }
            },

            submitForm() {
                if (this.amount <= 0) {
                    if(window.showToast) window.showToast('Nominal harus lebih dari 0', 'error');
                    return;
                }
                
                // Matikan deteksi unsaved changes agar tidak muncul alert browser
                if (typeof window.isFormDirty !== 'undefined') {
                    window.isFormDirty = false;
                }
                
                document.getElementById('adjustmentForm').submit();
            },

            formatRupiah(value) {
                // Format angka negatif dengan tanda kurung atau minus
                let val = value;
                let prefix = '';
                if(value < 0) {
                    val = Math.abs(value);
                    prefix = '- ';
                }
                return prefix + 'Rp ' + new Intl.NumberFormat('id-ID', { minimumFractionDigits: 0 }).format(val);
            }
        }
    }
</script>
@endpush
@endsection
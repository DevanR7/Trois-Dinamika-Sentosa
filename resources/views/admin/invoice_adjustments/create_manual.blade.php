@extends('admin.layouts.app')

@section('title', 'Koreksi Manual Invoice #' . $invoice->invoice_number)

@section('content')
<div class="flex flex-col gap-6" x-data="manualAdjustmentForm()">

    {{-- HEADER --}}
    <div class="flex items-center justify-between">
        <div class="flex items-center gap-3">
            <a href="{{ route('admin.invoice-adjustments.create', ['invoice_id' => $invoice->invoice_id]) }}" class="btn-icon btn-secondary" title="Kembali">
                <i class="material-icons text-lg">arrow_back</i>
            </a>
            <div>
                <h1 class="page-title text-xl font-bold tracking-tight">Koreksi Manual</h1>
                <p class="text-sm text-slate-500 mt-1">
                    Invoice: <span class="font-mono font-bold text-indigo-600">{{ $invoice->invoice_number }}</span>
                    &nbsp;|&nbsp; Klien: <strong>{{ $invoice->client->client_name }}</strong>
                </p>
            </div>
        </div>
    </div>

    <form action="{{ route('admin.invoice-adjustments.store.manual') }}" method="POST" @submit.prevent="submitForm">
        @csrf
        <input type="hidden" name="sales_invoice_id" value="{{ $invoice->invoice_id }}">
        
        {{-- Hidden Input untuk Amount Bersih (Float) --}}
        <input type="hidden" name="amount" x-model="rawAmount">

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">

            {{-- KOLOM KIRI: FORM INPUT --}}
            <div class="lg:col-span-2 space-y-6">
                
                {{-- Card 1: Tipe & Nominal --}}
                <div class="card p-6">
                    <h3 class="text-sm font-bold text-slate-800 dark:text-white uppercase mb-4 border-b border-slate-100 dark:border-slate-700 pb-2">
                        1. Detail Penyesuaian
                    </h3>

                    <div class="space-y-5">
                        {{-- Pilihan Tipe --}}
                        <div>
                            <label class="form-label label-required">Jenis Nota</label>
                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                                {{-- Credit Note --}}
                                <label class="relative flex items-center p-4 border-2 rounded-xl cursor-pointer transition-all hover:bg-slate-50 dark:hover:bg-slate-800/50"
                                       :class="type === 'credit_note' ? 'border-emerald-500 bg-emerald-50/30 ring-1 ring-emerald-500' : 'border-slate-200 dark:border-slate-700'">
                                    <input type="radio" name="type" value="credit_note" x-model="type" class="form-radio text-emerald-600 w-5 h-5 mr-3">
                                    <div>
                                        <span class="block font-bold text-slate-800 dark:text-white">Credit Note (Potongan)</span>
                                        <span class="block text-xs text-slate-500 mt-0.5">Mengurangi tagihan / piutang.</span>
                                        <span class="text-[10px] text-emerald-600 font-bold mt-1 block">Contoh: Diskon, Retur, Penghapusan Denda.</span>
                                    </div>
                                </label>

                                {{-- Debit Note --}}
                                <label class="relative flex items-center p-4 border-2 rounded-xl cursor-pointer transition-all hover:bg-slate-50 dark:hover:bg-slate-800/50"
                                       :class="type === 'debit_note' ? 'border-rose-500 bg-rose-50/30 ring-1 ring-rose-500' : 'border-slate-200 dark:border-slate-700'">
                                    <input type="radio" name="type" value="debit_note" x-model="type" class="form-radio text-rose-600 w-5 h-5 mr-3">
                                    <div>
                                        <span class="block font-bold text-slate-800 dark:text-white">Debit Note (Tambahan)</span>
                                        <span class="block text-xs text-slate-500 mt-0.5">Menambah tagihan / piutang.</span>
                                        <span class="text-[10px] text-rose-600 font-bold mt-1 block">Contoh: Kurang Bayar, Biaya Admin, Revisi Harga Naik.</span>
                                    </div>
                                </label>
                            </div>
                        </div>

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            {{-- Tanggal --}}
                            <div>
                                <label class="form-label label-required">Tanggal</label>
                                <input type="date" name="adjustment_date" class="form-input" value="{{ date('Y-m-d') }}" required>
                            </div>

                            {{-- Nominal --}}
                            <div>
                                <label class="form-label label-required">Nominal (Rp)</label>
                                <div class="relative">
                                    <input type="text" 
                                           class="form-input pl-3 pr-4 text-right font-mono font-bold text-lg" 
                                           x-ref="amountInput"
                                           placeholder="0"
                                           required>
                                </div>
                            </div>
                        </div>

                        {{-- Alasan --}}
                        <div>
                            <label class="form-label label-required">Alasan / Keterangan</label>
                            <textarea name="reason" class="form-textarea h-24" placeholder="Contoh: Diskon loyalitas tambahan..." required></textarea>
                        </div>
                    </div>
                </div>

                {{-- Card 2: Handling Overpayment (Conditional) --}}
                <div class="card p-6 border-l-4 border-l-indigo-500" 
                     x-show="newBalance < 0" 
                     x-transition.opacity>
                    <h3 class="text-sm font-bold text-indigo-700 uppercase mb-2 flex items-center gap-2">
                        <i class="material-icons text-base">info</i> Penanganan Kelebihan Bayar
                    </h3>
                    <p class="text-sm text-slate-600 dark:text-slate-300 mb-4">
                        Koreksi ini menyebabkan invoice menjadi <strong>Lebih Bayar (Overpaid)</strong> sebesar <span class="font-mono font-bold" x-text="formatRupiah(Math.abs(newBalance))"></span>. 
                        Tentukan tindakan selanjutnya:
                    </p>

                    <div class="grid grid-cols-1 gap-3">
                        <label class="flex items-center gap-3 p-3 border border-slate-200 rounded-lg cursor-pointer hover:bg-slate-50 bg-white">
                            <input type="radio" name="overpayment_action" value="deposit" class="form-radio text-indigo-600" checked>
                            <div>
                                <span class="block text-sm font-bold text-slate-700">Simpan ke Deposit Klien</span>
                                <span class="block text-xs text-slate-500">Saldo akan disimpan di akun klien untuk memotong invoice berikutnya.</span>
                            </div>
                        </label>
                        {{-- 
                        Opsi Refund saat ini dimatikan atau bisa diaktifkan jika modul refund otomatis diaktifkan 
                        Agar aman, default ke deposit dulu. Jika ingin refund, admin bisa lakukan refund manual dari deposit.
                        --}}
                        <label class="flex items-center gap-3 p-3 border border-slate-200 rounded-lg cursor-pointer hover:bg-slate-50 bg-white">
                            <input type="radio" name="overpayment_action" value="refund" class="form-radio text-indigo-600">
                            <div>
                                <span class="block text-sm font-bold text-slate-700">Refund Tunai (Manual)</span>
                                <span class="block text-xs text-slate-500">Saldo invoice dinolkan. Anda perlu mencatat pengeluaran kas secara manual.</span>
                            </div>
                        </label>
                    </div>
                </div>

                {{-- Submit Button --}}
                <div class="flex justify-end pt-2">
                    <button type="submit" class="btn btn-primary btn-lg shadow-lg shadow-indigo-500/20 px-8" :disabled="rawAmount <= 0">
                        <i class="material-icons mr-2">save</i> Simpan Penyesuaian
                    </button>
                </div>
            </div>

            {{-- KOLOM KANAN: SIMULASI --}}
            <div class="lg:col-span-1">
                <div class="card bg-slate-50 dark:bg-slate-800/50 sticky top-24">
                    <div class="card-header bg-transparent border-b border-slate-200 dark:border-slate-700">
                        <h3 class="card-header-title text-sm uppercase">Simulasi Saldo</h3>
                    </div>
                    <div class="p-6 space-y-4">
                        
                        {{-- 1. Saldo Saat Ini --}}
                        <div class="flex justify-between items-center text-sm">
                            <span class="text-slate-500">Sisa Tagihan Saat Ini</span>
                            <span class="font-mono font-bold text-slate-700 dark:text-slate-200">
                                {{ number_format($invoice->remaining_balance, 0, ',', '.') }}
                            </span>
                        </div>

                        {{-- 2. Nilai Koreksi --}}
                        <div class="flex justify-between items-center text-sm pb-4 border-b border-dashed border-slate-300 dark:border-slate-600">
                            <span class="text-slate-500 flex items-center gap-1">
                                <i class="material-icons text-[16px]" :class="type === 'credit_note' ? 'text-emerald-500' : 'text-rose-500'">
                                    <span x-text="type === 'credit_note' ? 'remove_circle' : 'add_circle'"></span>
                                </i>
                                Koreksi
                            </span>
                            <span class="font-mono font-bold" 
                                  :class="type === 'credit_note' ? 'text-emerald-600' : 'text-rose-600'">
                                <span x-text="type === 'credit_note' ? '-' : '+'"></span>
                                <span x-text="formatRupiah(rawAmount)"></span>
                            </span>
                        </div>

                        {{-- 3. Saldo Baru --}}
                        <div class="bg-white dark:bg-slate-700 p-4 rounded-xl border"
                             :class="newBalance > 0 ? 'border-slate-200' : (newBalance < 0 ? 'border-indigo-300 bg-indigo-50 dark:bg-indigo-900/20' : 'border-emerald-200 bg-emerald-50 dark:bg-emerald-900/20')">
                            
                            <div class="text-xs font-bold uppercase mb-1"
                                 :class="newBalance > 0 ? 'text-slate-500' : (newBalance < 0 ? 'text-indigo-600' : 'text-emerald-600')">
                                <span x-show="newBalance > 0">Tagihan Baru</span>
                                <span x-show="newBalance < 0">Lebih Bayar</span>
                                <span x-show="newBalance == 0">Status</span>
                            </div>

                            <div class="text-2xl font-bold font-mono"
                                 :class="newBalance > 0 ? 'text-slate-800 dark:text-white' : (newBalance < 0 ? 'text-indigo-700 dark:text-indigo-300' : 'text-emerald-600')">
                                <span x-show="newBalance == 0">LUNAS</span>
                                <span x-show="newBalance != 0" x-text="formatRupiah(Math.abs(newBalance))"></span>
                            </div>
                        </div>

                        {{-- Pesan Helper --}}
                        <div class="text-xs text-slate-400 italic leading-relaxed">
                            * Credit Note akan mengurangi piutang. <br>
                            * Debit Note akan menambah piutang.
                        </div>

                    </div>
                </div>
            </div>

        </div>
    </form>
</div>

@push('scripts')
<script>
    document.addEventListener('alpine:init', () => {
        Alpine.data('manualAdjustmentForm', () => ({
            type: 'credit_note', // Default Credit Note (Potongan)
            rawAmount: 0,
            currentBalance: {{ $invoice->remaining_balance }},
            anInstance: null,

            init() {
                // Inisialisasi AutoNumeric pada input nominal
                const el = this.$refs.amountInput;
                if (el) {
                    this.anInstance = new AutoNumeric(el, {
                        digitGroupSeparator: '.',
                        decimalCharacter: ',',
                        decimalCharacterAlternative: '.',
                        currencySymbol: 'Rp ',
                        currencySymbolPlacement: 'p',
                        roundingMethod: 'U',
                        minimumValue: '0',
                        unformatOnSubmit: true 
                    });

                    // Event listener saat nilai berubah
                    el.addEventListener('autoNumeric:rawValueModified', (e) => {
                        this.rawAmount = parseFloat(e.detail.newRawValue) || 0;
                    });
                }
            },

            // Hitung Saldo Baru secara real-time
            get newBalance() {
                let adjustment = this.rawAmount;
                // Jika Credit Note, mengurangi tagihan (balance - adj)
                // Jika Debit Note, menambah tagihan (balance + adj)
                if (this.type === 'credit_note') {
                    return this.currentBalance - adjustment;
                } else {
                    return this.currentBalance + adjustment;
                }
            },

            formatRupiah(num) {
                return new Intl.NumberFormat('id-ID', { style: 'currency', currency: 'IDR', minimumFractionDigits: 0 }).format(num);
            },

            submitForm(e) {
                if (this.rawAmount <= 0) {
                    showToast('Nominal harus lebih dari 0', 'error');
                    return;
                }
                // Submit form manual karena x-model pada hidden input sudah terupdate
                e.target.submit();
            }
        }));
    });
</script>
@endpush

@endsection
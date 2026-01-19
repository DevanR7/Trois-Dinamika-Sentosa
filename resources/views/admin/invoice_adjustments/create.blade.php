@extends('admin.layouts.app')

@section('title', 'Buat Penyesuaian Invoice')

@section('content')
<div class="flex flex-col gap-6" x-data="adjustmentSelection()">

    {{-- HEADER --}}
    <div class="flex items-center justify-between">
        <div class="flex items-center gap-3">
            <a href="{{ url()->previous() }}" class="btn-icon btn-secondary" title="Kembali">
                <i class="material-icons text-lg">arrow_back</i>
            </a>
            <div>
                <h1 class="page-title text-xl font-bold tracking-tight">Buat Penyesuaian</h1>
                <p class="text-sm text-slate-500 mt-1">Pilih invoice dan metode koreksi yang ingin dilakukan.</p>
            </div>
        </div>
    </div>

    {{-- MAIN CONTENT --}}
    <div class="card p-6 md:p-8 max-w-5xl mx-auto w-full">
        
        {{-- STEP 1: PILIH INVOICE --}}
        <div class="mb-10 max-w-2xl mx-auto">
            <label class="form-label text-base mb-3 block text-center">Langkah 1: Pilih Invoice yang akan disesuaikan</label>
            <div x-init="initSelect($el)">
                <select id="invoice_select" class="tom-select w-full" x-model="selectedInvoiceId" placeholder="Cari No. Invoice atau Nama Klien...">
                    <option value="">Pilih Invoice...</option>
                    @foreach($invoices as $inv)
                        <option value="{{ $inv->invoice_id }}" {{ $preselectedInvoiceId == $inv->invoice_id ? 'selected' : '' }}>
                            {{ $inv->invoice_number }} - {{ $inv->client->client_name }} 
                            (Total: Rp {{ number_format($inv->total_amount, 0, ',', '.') }})
                        </option>
                    @endforeach
                </select>
            </div>
            
            {{-- Info Selected Invoice --}}
            <div x-show="selectedInvoiceId" x-transition class="mt-3 text-center">
                <span class="inline-flex items-center gap-2 px-3 py-1 bg-emerald-50 text-emerald-700 text-sm font-medium rounded-full border border-emerald-100">
                    <i class="material-icons text-sm">check_circle</i> Invoice Terpilih
                </span>
            </div>
        </div>

        <div class="border-t border-slate-100 dark:border-slate-700 mb-10"></div>

        {{-- STEP 2: PILIH METODE --}}
        <div class="text-center mb-6">
            <label class="form-label text-base">Langkah 2: Pilih Jenis Koreksi</label>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            
            {{-- OPSI 1: KOREKSI MANUAL --}}
            <div class="group relative bg-white dark:bg-slate-800 border-2 rounded-2xl p-6 transition-all duration-300 flex flex-col h-full"
                 :class="!selectedInvoiceId ? 'border-slate-100 opacity-50 cursor-not-allowed' : 'border-slate-200 hover:border-indigo-500 hover:shadow-lg hover:-translate-y-1 cursor-pointer'"
                 @click="if(selectedInvoiceId) goToManual()">
                
                <div class="absolute top-4 right-4">
                    <div class="w-8 h-8 rounded-full bg-indigo-50 text-indigo-600 flex items-center justify-center">
                        <i class="material-icons">tune</i>
                    </div>
                </div>

                <h3 class="text-lg font-bold text-slate-800 dark:text-white mb-2 group-hover:text-indigo-600 transition-colors">
                    Koreksi Manual
                </h3>
                <p class="text-sm text-slate-500 dark:text-slate-400 mb-6 flex-1">
                    Tambahkan nilai <strong>Debit (+)</strong> atau <strong>Kredit (-)</strong> secara manual tanpa mengubah rincian barang.
                </p>

                <ul class="text-xs text-slate-500 space-y-2 mb-6">
                    <li class="flex items-start gap-2">
                        <i class="material-icons text-emerald-500 text-sm">check</i>
                        <span>Cocok untuk diskon susulan / potongan harga.</span>
                    </li>
                    <li class="flex items-start gap-2">
                        <i class="material-icons text-emerald-500 text-sm">check</i>
                        <span>Cocok untuk koreksi pembulatan kecil.</span>
                    </li>
                    <li class="flex items-start gap-2">
                        <i class="material-icons text-emerald-500 text-sm">check</i>
                        <span>Cocok untuk biaya admin / cas tambahan.</span>
                    </li>
                </ul>

                <button type="button" 
                        class="btn w-full justify-center" 
                        :class="!selectedInvoiceId ? 'btn-secondary' : 'btn-primary group-hover:bg-indigo-700'"
                        :disabled="!selectedInvoiceId">
                    Pilih Manual
                </button>
            </div>

            {{-- OPSI 2: KOREKSI OTOMATIS --}}
            <div class="group relative bg-white dark:bg-slate-800 border-2 rounded-2xl p-6 transition-all duration-300 flex flex-col h-full"
                 :class="!selectedInvoiceId ? 'border-slate-100 opacity-50 cursor-not-allowed' : 'border-slate-200 hover:border-emerald-500 hover:shadow-lg hover:-translate-y-1 cursor-pointer'"
                 @click="if(selectedInvoiceId) goToAuto()">
                
                <div class="absolute top-4 right-4">
                    <div class="w-8 h-8 rounded-full bg-emerald-50 text-emerald-600 flex items-center justify-center">
                        <i class="material-icons">auto_fix_high</i>
                    </div>
                </div>

                <h3 class="text-lg font-bold text-slate-800 dark:text-white mb-2 group-hover:text-emerald-600 transition-colors">
                    Koreksi Otomatis (Revisi)
                </h3>
                <p class="text-sm text-slate-500 dark:text-slate-400 mb-6 flex-1">
                    Ubah rincian barang, harga satuan, pajak, atau diskon. Sistem akan menghitung selisihnya otomatis.
                </p>

                <ul class="text-xs text-slate-500 space-y-2 mb-6">
                    <li class="flex items-start gap-2">
                        <i class="material-icons text-emerald-500 text-sm">check</i>
                        <span>Cocok untuk revisi harga barang yang salah.</span>
                    </li>
                    <li class="flex items-start gap-2">
                        <i class="material-icons text-emerald-500 text-sm">check</i>
                        <span>Cocok untuk menambah/mengurangi item invoice.</span>
                    </li>
                    <li class="flex items-start gap-2">
                        <i class="material-icons text-emerald-500 text-sm">check</i>
                        <span>Menghitung ulang pajak & subtotal otomatis.</span>
                    </li>
                </ul>

                <button type="button" 
                        class="btn w-full justify-center" 
                        :class="!selectedInvoiceId ? 'btn-secondary' : 'btn-success group-hover:bg-emerald-700'"
                        :disabled="!selectedInvoiceId">
                    Pilih Otomatis
                </button>
            </div>

        </div>
    </div>
</div>

@push('scripts')
<script>
    document.addEventListener('alpine:init', () => {
        Alpine.data('adjustmentSelection', () => ({
            selectedInvoiceId: '{{ $preselectedInvoiceId }}',

            initSelect(el) {
                // Inisialisasi Tom Select
                new TomSelect(el.querySelector('select'), {
                    ...window.defaultTomSelectConfig,
                    dropdownParent: 'body',
                    onChange: (value) => {
                        this.selectedInvoiceId = value;
                    }
                });
            },

            goToManual() {
                if (!this.selectedInvoiceId) return;
                // Redirect ke route Manual
                window.location.href = `/admin/invoice-adjustments/create-manual/${this.selectedInvoiceId}`;
            },

            goToAuto() {
                if (!this.selectedInvoiceId) return;
                // Redirect ke route Auto
                window.location.href = `/admin/invoice-adjustments/create-auto/${this.selectedInvoiceId}`;
            }
        }));
    });
</script>
@endpush

@endsection
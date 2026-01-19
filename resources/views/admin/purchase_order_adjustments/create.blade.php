@extends('admin.layouts.app')

@section('title', 'Buat Penyesuaian PO')

@section('content')
<div x-data="adjustmentSelection()" class="max-w-5xl mx-auto">

    {{-- HEADER --}}
    <div class="flex items-center justify-between mb-8">
        <div>
            <h1 class="text-2xl font-bold text-slate-800 dark:text-white tracking-tight">Buat Penyesuaian (Adjustment)</h1>
            <p class="text-sm text-slate-500 dark:text-slate-400 mt-1">Koreksi nilai hutang atau persediaan untuk Purchase Order yang sedang berjalan.</p>
        </div>
        <a href="{{ route('admin.purchase-orders.index') }}" class="btn btn-secondary">
            <i class="material-icons text-[18px] mr-2">arrow_back</i> Kembali
        </a>
    </div>

    {{-- STEP 1: PILIH PO --}}
    <div class="card p-6 mb-8 border-l-4 border-l-indigo-500 shadow-lg">
        <h3 class="text-sm font-bold text-slate-500 uppercase tracking-wider mb-4 flex items-center gap-2">
            <span class="w-6 h-6 rounded-full bg-indigo-100 text-indigo-600 flex items-center justify-center text-xs font-bold">1</span>
            Pilih Target Purchase Order
        </h3>
        
        <div class="max-w-xl">
            <label class="form-label label-required">Cari Nomor PO / Supplier</label>
            <select id="po_select" class="tom-select" x-model="selectedPoId" placeholder="Ketik nomor PO atau nama supplier...">
                <option value="">-- Pilih PO --</option>
                @foreach($purchaseOrders as $po)
                    <option value="{{ $po->po_id }}">
                        {{ $po->po_number }} • {{ $po->supplier->supplier_name }} ({{ \Carbon\Carbon::parse($po->order_date)->format('d/m/Y') }})
                    </option>
                @endforeach
            </select>
            <p class="text-xs text-slate-400 mt-2 italic">
                *Hanya menampilkan PO yang aktif (bukan Cancelled).
            </p>
        </div>
    </div>

    {{-- STEP 2: PILIH METODE (Hanya Muncul Jika PO Dipilih) --}}
    <div x-show="selectedPoId" 
         x-transition:enter="transition ease-out duration-300"
         x-transition:enter-start="opacity-0 translate-y-4"
         x-transition:enter-end="opacity-100 translate-y-0"
         style="display: none;">
        
        <h3 class="text-sm font-bold text-slate-500 uppercase tracking-wider mb-4 flex items-center gap-2">
            <span class="w-6 h-6 rounded-full bg-slate-100 text-slate-500 flex items-center justify-center text-xs font-bold">2</span>
            Pilih Metode Penyesuaian
        </h3>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            
            {{-- OPTION A: MANUAL --}}
            <a :href="getManualRoute()" class="group relative card p-6 cursor-pointer hover:border-blue-500 hover:ring-1 hover:ring-blue-500 transition-all duration-200">
                <div class="flex items-start gap-4">
                    <div class="w-12 h-12 rounded-xl bg-blue-50 text-blue-600 flex items-center justify-center group-hover:bg-blue-600 group-hover:text-white transition-colors">
                        <i class="material-icons text-3xl">tune</i>
                    </div>
                    <div>
                        <h4 class="text-lg font-bold text-slate-800 dark:text-white group-hover:text-blue-600 transition-colors">Penyesuaian Manual</h4>
                        <p class="text-xs text-slate-500 dark:text-slate-400 mt-2 leading-relaxed">
                            Input nominal penyesuaian secara langsung tanpa mengubah rincian barang.
                        </p>
                        <ul class="mt-3 space-y-1">
                            <li class="text-xs text-slate-600 flex items-center gap-2">
                                <i class="material-icons text-[14px] text-emerald-500">check</i> Debit Note (Tambah Hutang)
                            </li>
                            <li class="text-xs text-slate-600 flex items-center gap-2">
                                <i class="material-icons text-[14px] text-emerald-500">check</i> Credit Note (Potong Hutang)
                            </li>
                        </ul>
                        <div class="mt-4 inline-flex items-center text-xs font-bold text-blue-600 group-hover:underline">
                            Pilih Manual <i class="material-icons text-sm ml-1">arrow_forward</i>
                        </div>
                    </div>
                </div>
            </a>

            {{-- OPTION B: AUTOMATIC --}}
            <a :href="getAutoRoute()" class="group relative card p-6 cursor-pointer hover:border-purple-500 hover:ring-1 hover:ring-purple-500 transition-all duration-200">
                <div class="flex items-start gap-4">
                    <div class="w-12 h-12 rounded-xl bg-purple-50 text-purple-600 flex items-center justify-center group-hover:bg-purple-600 group-hover:text-white transition-colors">
                        <i class="material-icons text-3xl">auto_fix_high</i>
                    </div>
                    <div>
                        <h4 class="text-lg font-bold text-slate-800 dark:text-white group-hover:text-purple-600 transition-colors">Koreksi Otomatis</h4>
                        <p class="text-xs text-slate-500 dark:text-slate-400 mt-2 leading-relaxed">
                            Ubah harga, kuantitas, atau pajak item. Sistem akan menghitung selisihnya otomatis.
                        </p>
                        <ul class="mt-3 space-y-1">
                            <li class="text-xs text-slate-600 flex items-center gap-2">
                                <i class="material-icons text-[14px] text-emerald-500">check</i> Revisi Harga/Qty Barang
                            </li>
                            <li class="text-xs text-slate-600 flex items-center gap-2">
                                <i class="material-icons text-[14px] text-emerald-500">check</i> Revisi Pajak / Diskon
                            </li>
                        </ul>
                        <div class="mt-4 inline-flex items-center text-xs font-bold text-purple-600 group-hover:underline">
                            Pilih Otomatis <i class="material-icons text-sm ml-1">arrow_forward</i>
                        </div>
                    </div>
                </div>
            </a>

        </div>
    </div>

</div>

@push('scripts')
<script>
    function adjustmentSelection() {
        return {
            selectedPoId: '{{ $preselectedPurchaseOrderId ?? "" }}',

            init() {
                // Initialize Tom Select
                const el = document.getElementById('po_select');
                if (el) {
                    new TomSelect(el, {
                        ...window.defaultTomSelectConfig,
                        onChange: (value) => {
                            this.selectedPoId = value;
                        }
                    });

                    // Set value jika ada preselected ID dari controller
                    if (this.selectedPoId) {
                        el.tomselect.setValue(this.selectedPoId);
                    }
                }
            },

            // Generate URL Dinamis untuk Route Laravel
            getManualRoute() {
                if (!this.selectedPoId) return '#';
                // Ganti placeholder ID '0' dengan ID yang dipilih
                return `{{ route('admin.purchase-order-adjustments.create.manual', ':id') }}`.replace(':id', this.selectedPoId);
            },

            getAutoRoute() {
                if (!this.selectedPoId) return '#';
                return `{{ route('admin.purchase-order-adjustments.create.auto', ':id') }}`.replace(':id', this.selectedPoId);
            }
        }
    }
</script>
@endpush
@endsection
@extends('layouts.app')

@section('title', 'Buat Penyesuaian PO')

@section('content')
<div class="max-w-3xl mx-auto pb-20 animate-enter">
    
    {{-- HEADER --}}
    <div class="text-center mb-8 pt-4">
        <div class="inline-flex items-center justify-center w-16 h-16 rounded-full bg-indigo-50 text-indigo-600 mb-4 ring-4 ring-white shadow-sm">
            <i class="material-icons text-4xl">tune</i>
        </div>
        <h2 class="text-2xl font-bold text-slate-800 tracking-tight">Buat Penyesuaian PO</h2>
        <p class="text-slate-500 mt-2 text-sm">Koreksi tagihan (Nota Debet/Kredit) untuk Pesanan Pembelian yang sudah berjalan.</p>
    </div>

    {{-- CARD WIZARD --}}
    <div class="dashboard-card p-0 overflow-hidden">
        <div class="p-8">
            
            {{-- LANGKAH 1: PILIH PO --}}
            <div class="relative">
                <div class="absolute top-0 left-0 -ml-2 -mt-2">
                    <span class="flex items-center justify-center w-8 h-8 bg-slate-800 text-white rounded-full font-bold text-sm shadow-md ring-2 ring-white">1</span>
                </div>
                
                <div class="ml-10">
                    <label for="purchase_order_id" class="block text-[11px] font-bold text-slate-500 uppercase tracking-wide mb-2 ml-1">Cari Nomor Purchase Order (PO)</label>
                    <select id="purchase_order_id" class="select2-basic w-full">
                        <option value="" disabled {{ !$preselectedPurchaseOrderId ? 'selected' : '' }}>-- Ketik No. PO atau Supplier --</option>
                        @foreach($purchaseOrders as $po)
                            <option value="{{ $po->po_id }}" {{ $preselectedPurchaseOrderId == $po->po_id ? 'selected' : '' }}>
                                {{ $po->po_number }} | {{ $po->supplier->supplier_name }} 
                                (Sisa: Rp {{ number_format($po->remaining_balance, 0, ',', '.') }})
                            </option>
                        @endforeach
                    </select>
                    <p class="text-xs text-slate-500 mt-2 flex items-center gap-1">
                        <i class="material-icons text-[14px]">info</i> Hanya PO dengan sisa tagihan.
                    </p>
                </div>
            </div>

            {{-- LANGKAH 2: PILIH METODE (HIDDEN BY DEFAULT) --}}
            <div id="method-selection" class="mt-10 ml-10 border-t border-dashed border-slate-200 pt-8 hidden opacity-0 transition-all duration-500 relative">
                
                <div class="absolute top-8 left-0 -ml-10">
                    <span class="flex items-center justify-center w-8 h-8 bg-slate-800 text-white rounded-full font-bold text-sm shadow-md ring-2 ring-white">2</span>
                </div>

                <h3 class="text-sm font-bold text-slate-800 mb-4">Pilih Metode Koreksi</h3>
                
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    
                    {{-- OPSI 1: OTOMATIS --}}
                    <a href="#" id="link-auto" class="group block">
                        <div class="h-full p-5 rounded-xl border border-slate-200 hover:border-indigo-500 hover:bg-indigo-50/30 transition-all cursor-pointer relative bg-white shadow-sm hover:shadow-md">
                            <div class="absolute top-3 right-3">
                                <span class="px-2 py-0.5 bg-emerald-50 text-emerald-700 text-[10px] font-bold rounded uppercase border border-emerald-100">Disarankan</span>
                            </div>
                            <div class="w-12 h-12 bg-indigo-100 text-indigo-600 rounded-lg flex items-center justify-center mb-4 group-hover:scale-110 transition-transform">
                                <i class="material-icons text-2xl">auto_fix_high</i>
                            </div>
                            <h4 class="font-bold text-slate-800 mb-1 group-hover:text-indigo-700 text-sm">Mode Revisi Item</h4>
                            <p class="text-xs text-slate-500 leading-relaxed">
                                Ubah detail item (harga/qty/diskon). Sistem akan menghitung selisih Nota Debet/Kredit secara otomatis.
                            </p>
                        </div>
                    </a>

                    {{-- OPSI 2: MANUAL --}}
                    <a href="#" id="link-manual" class="group block">
                        <div class="h-full p-5 rounded-xl border border-slate-200 hover:border-slate-400 hover:bg-slate-50 transition-all cursor-pointer bg-white shadow-sm hover:shadow-md">
                            <div class="w-12 h-12 bg-slate-100 text-slate-600 rounded-lg flex items-center justify-center mb-4 group-hover:scale-110 transition-transform">
                                <i class="material-icons text-2xl">edit_note</i>
                            </div>
                            <h4 class="font-bold text-slate-800 mb-1 group-hover:text-slate-800 text-sm">Mode Manual</h4>
                            <p class="text-xs text-slate-500 leading-relaxed">
                                Input nominal total untuk Nota Kredit (Potongan) atau Nota Debet (Tagihan Tambahan) secara langsung.
                            </p>
                        </div>
                    </a>

                </div>
            </div>

        </div>
        <div class="bg-slate-50 px-8 py-4 border-t border-slate-200 text-center">
            <a href="{{ route('purchase-orders.index') }}" class="text-xs font-bold text-slate-500 hover:text-indigo-600 uppercase tracking-wider transition">Batal & Kembali</a>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function () {
    // Init Select2
    const poSelect = $('#purchase_order_id');
    poSelect.select2({
        theme: 'bootstrap-5',
        placeholder: '-- Ketik No. PO atau Supplier --',
        width: '100%',
        allowClear: true,
        dropdownCssClass: 'select2-dropdown-clean'
    });

    const methodSection = document.getElementById('method-selection');
    const linkAuto = document.getElementById('link-auto');
    const linkManual = document.getElementById('link-manual');

    // URL Templates (Placeholder :id akan diganti JS)
    const urlTemplateAuto = "{{ route('purchase-order-adjustments.create.auto', ['purchaseOrder' => ':id']) }}";
    const urlTemplateManual = "{{ route('purchase-order-adjustments.create.manual', ['purchaseOrder' => ':id']) }}";

    function toggleMethodSection(show) {
        if (show) {
            methodSection.classList.remove('hidden');
            setTimeout(() => {
                methodSection.classList.remove('opacity-0');
                methodSection.classList.add('opacity-100');
            }, 50);
        } else {
            methodSection.classList.remove('opacity-100');
            methodSection.classList.add('opacity-0');
            setTimeout(() => {
                methodSection.classList.add('hidden');
            }, 500);
        }
    }

    poSelect.on('change', function() {
        const selectedPoId = $(this).val();
        
        if (selectedPoId) {
            // Update href links
            linkAuto.href = urlTemplateAuto.replace(':id', selectedPoId);
            linkManual.href = urlTemplateManual.replace(':id', selectedPoId);
            toggleMethodSection(true);
        } else {
            toggleMethodSection(false);
        }
    });

    // Pre-select jika ada parameter dari URL
    const preselectedPoId = "{{ $preselectedPurchaseOrderId ?? '' }}";
    if (preselectedPoId) {
        poSelect.val(preselectedPoId).trigger('change');
    }
});
</script>
@endpush
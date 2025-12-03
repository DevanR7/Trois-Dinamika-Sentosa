@extends('admin.layouts.app')

@section('title', 'Buat Penyesuaian PO')

@push('styles')
    {{-- Hanya load library, styling ikut app.css --}}
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
@endpush

@section('content')
<div class="max-w-4xl mx-auto animate-enter pb-20">
    
    {{-- HEADER --}}
    <div class="text-center mb-10 pt-6">
        <div class="inline-flex items-center justify-center w-16 h-16 rounded-full bg-indigo-50 text-indigo-600 mb-4 ring-4 ring-white shadow-sm">
            <i class="material-icons text-4xl">tune</i>
        </div>
        <h2 class="text-2xl font-bold text-slate-800 tracking-tight">Buat Penyesuaian PO</h2>
        <p class="text-slate-500 mt-2 text-sm max-w-lg mx-auto">Koreksi tagihan (Nota Debet/Kredit) untuk Pesanan Pembelian yang sudah berjalan.</p>
    </div>

    {{-- CARD WIZARD --}}
    <div class="dashboard-card p-0 overflow-hidden relative bg-white rounded-xl shadow-sm border border-slate-100">
        <div class="p-8">
            
            {{-- LANGKAH 1: PILIH PO --}}
            <div class="relative z-10">
                <div class="absolute top-0 left-0 -ml-2 -mt-2 hidden sm:block">
                    <span class="flex items-center justify-center w-8 h-8 bg-slate-800 text-white rounded-full font-bold text-sm shadow-lg ring-4 ring-white">1</span>
                </div>
                
                <div class="sm:ml-12">
                    <label for="purchase_order_id" class="block text-sm font-bold text-slate-700 mb-2 uppercase tracking-wide">
                        Cari Nomor Purchase Order (PO)
                    </label>
                    
                    <div class="relative">
                        {{-- FIX: Hapus 'select2-basic' agar tidak kena tema bootstrap dari app.js --}}
                        <select id="purchase_order_id" class="w-full">
                            <option value="" disabled {{ !$preselectedPurchaseOrderId ? 'selected' : '' }}></option>
                            @foreach($purchaseOrders as $po)
                                <option value="{{ $po->po_id }}" {{ $preselectedPurchaseOrderId == $po->po_id ? 'selected' : '' }}>
                                    {{ $po->po_number }} | {{ $po->supplier->supplier_name }} 
                                    (Sisa: Rp {{ number_format($po->remaining_balance, 0, ',', '.') }})
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="mt-4 flex items-start gap-3 bg-blue-50 p-4 rounded-lg border border-blue-100">
                         <i class="material-icons text-blue-500 text-base mt-0.5">info</i>
                         <p class="text-sm text-blue-700 leading-relaxed">
                            Hanya Purchase Order dengan status aktif yang muncul di sini.
                        </p>
                    </div>
                </div>
            </div>

            {{-- LANGKAH 2: PILIH METODE (HIDDEN BY DEFAULT) --}}
            <div id="method-selection" class="mt-10 sm:ml-12 border-t border-dashed border-slate-200 pt-8 hidden opacity-0 transition-all duration-500 relative">
                
                <div class="absolute top-8 left-0 -ml-14 hidden sm:block">
                    <span class="flex items-center justify-center w-8 h-8 bg-slate-800 text-white rounded-full font-bold text-sm shadow-lg ring-4 ring-white">2</span>
                </div>

                <h3 class="text-sm font-bold text-slate-700 uppercase tracking-wide mb-4">Pilih Metode Koreksi</h3>
                
                <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                    
                    {{-- OPSI 1: OTOMATIS --}}
                    <a href="#" id="link-auto" class="group relative block h-full">
                        <div class="h-full p-6 rounded-xl border border-slate-200 bg-white hover:border-indigo-500 hover:shadow-lg hover:-translate-y-1 transition-all duration-300 group-hover:bg-indigo-50/30">
                            <div class="absolute top-4 right-4">
                                <span class="px-2 py-1 bg-emerald-50 text-emerald-600 text-[10px] font-bold rounded uppercase border border-emerald-100 tracking-wide">Disarankan</span>
                            </div>
                            <div class="w-12 h-12 bg-indigo-50 text-indigo-600 rounded-lg flex items-center justify-center mb-4 group-hover:scale-110 transition-transform duration-300">
                                <i class="material-icons text-2xl">auto_fix_high</i>
                            </div>
                            <h4 class="font-bold text-slate-800 mb-2 group-hover:text-indigo-600 text-base">Revisi Item Otomatis</h4>
                            <p class="text-sm text-slate-500 leading-relaxed">
                                Ubah detail item (harga/qty/diskon). Sistem akan menghitung selisih Nota Debet/Kredit secara otomatis.
                            </p>
                        </div>
                    </a>

                    {{-- OPSI 2: MANUAL --}}
                    <a href="#" id="link-manual" class="group relative block h-full">
                        <div class="h-full p-6 rounded-xl border border-slate-200 bg-white hover:border-slate-400 hover:bg-slate-50 hover:shadow-md transition-all duration-300">
                            <div class="w-12 h-12 bg-slate-100 text-slate-600 rounded-lg flex items-center justify-center mb-4 group-hover:scale-110 transition-transform duration-300">
                                <i class="material-icons text-2xl">edit_note</i>
                            </div>
                            <h4 class="font-bold text-slate-800 mb-2 text-base">Input Manual</h4>
                            <p class="text-sm text-slate-500 leading-relaxed">
                                Input nominal total untuk Nota Kredit (Potongan) atau Nota Debet (Tagihan Tambahan) secara langsung.
                            </p>
                        </div>
                    </a>

                </div>
            </div>

        </div>
        
        <div class="bg-slate-50 px-8 py-4 border-t border-slate-200 flex justify-center rounded-b-xl">
            <a href="{{ route('admin.purchase-orders.index') }}" class="text-xs font-bold text-slate-400 hover:text-slate-600 uppercase tracking-widest transition-colors">
                Batal & Kembali
            </a>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function () {
    // Init Select2 Manual
    const poSelect = $('#purchase_order_id');
    
    // FIX: Jangan gunakan theme 'bootstrap-5' agar CSS override kita jalan
    poSelect.select2({
        placeholder: 'Ketik No. PO atau Supplier...',
        width: '100%',
        allowClear: true
    });

    const methodSection = document.getElementById('method-selection');
    const linkAuto = document.getElementById('link-auto');
    const linkManual = document.getElementById('link-manual');

    // URL Templates
    const urlTemplateAuto = "{{ route('admin.purchase-order-adjustments.create.auto', ['purchaseOrder' => ':id']) }}";
    const urlTemplateManual = "{{ route('admin.purchase-order-adjustments.create.manual', ['purchaseOrder' => ':id']) }}";
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
            // Update links
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
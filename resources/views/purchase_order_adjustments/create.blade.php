@extends('layouts.app')

@section('content')
<div class="container py-5">
    <div class="row justify-content-center">
        <div class="col-lg-7 col-md-10">
            
            {{-- HEADER --}}
            <div class="text-center mb-4">
                <h3 class="fw-bold text-dark">Buat Penyesuaian PO</h3>
                <p class="text-muted">Koreksi tagihan (Nota Debet/Kredit) untuk Pesanan Pembelian</p>
            </div>

            {{-- KARTU UTAMA --}}
            <div class="card card-transaction border-0 shadow-sm">
                <div class="card-header bg-white p-4 border-bottom border-light">
                    <div class="d-flex justify-content-between align-items-center">
                        <div class="form-section-title mb-0"><i class="bi bi-sliders"></i> Wizard Penyesuaian</div>
                        <a href="{{ route('purchase-orders.index') }}" class="btn btn-sm btn-light border text-muted">
                            <i class="bi bi-x-lg"></i> Batal
                        </a>
                    </div>
                </div>

                <div class="card-body p-4">
                    
                    {{-- LANGKAH 1: PILIH PO --}}
                    <div class="mb-4 position-relative">
                        <span class="position-absolute top-0 start-0 translate-middle badge rounded-pill bg-primary border border-white shadow-sm" style="font-size: 1rem; width: 30px; height: 30px; display: flex; align-items: center; justify-content: center; z-index: 10;">1</span>
                        
                        <div class="ps-4 ms-2">
                            <label for="purchase_order_id" class="form-label fw-bold text-dark mb-2">Cari Nomor Purchase Order (PO)</label>
                            <select id="purchase_order_id" class="form-select form-select-lg">
                                <option value="" disabled {{ !$preselectedPurchaseOrderId ? 'selected' : '' }}>-- Ketik No. PO atau Supplier --</option>
                                @foreach($purchaseOrders as $po)
                                    <option value="{{ $po->po_id }}" {{ $preselectedPurchaseOrderId == $po->po_id ? 'selected' : '' }}>
                                        {{ $po->po_number }} | {{ $po->supplier->supplier_name }} 
                                        (Sisa: Rp {{ number_format($po->remaining_balance, 0, ',', '.') }})
                                    </option>
                                @endforeach
                            </select>
                            <div class="form-text text-muted small mt-2">
                                <i class="bi bi-info-circle me-1"></i> Hanya PO dengan status <b>Ordered</b>, <b>Completed</b>, atau <b>Partially Paid</b> yang muncul.
                            </div>
                        </div>
                    </div>

                    {{-- LANGKAH 2: PILIH METODE (Hidden by default) --}}
                    <div id="method-selection" class="d-none mt-5 position-relative opacity-0 transition-fade">
                        <hr class="border-dashed my-4">
                        
                        <span class="position-absolute top-0 start-0 translate-middle badge rounded-pill bg-primary border border-white shadow-sm" style="top: 25px !important; font-size: 1rem; width: 30px; height: 30px; display: flex; align-items: center; justify-content: center; z-index: 10;">2</span>

                        <div class="ps-4 ms-2">
                            <label class="form-label fw-bold text-dark mb-3">Pilih Metode Koreksi</label>
                            
                            <div class="row g-3">
                                {{-- Opsi 1: Otomatis (Revisi) --}}
                                <div class="col-md-6">
                                    <a href="#" id="link-auto" class="text-decoration-none">
                                        <div class="card h-100 p-3 border border-2 option-card hover-primary">
                                            <div class="card-body text-center">
                                                <div class="icon-box bg-primary bg-opacity-10 text-primary mx-auto mb-3 rounded-circle d-flex align-items-center justify-content-center" style="width: 60px; height: 60px;">
                                                    <i class="bi bi-magic fs-3"></i>
                                                </div>
                                                <h6 class="fw-bold text-dark">Mode Revisi Item</h6>
                                                <p class="small text-muted mb-0 lh-sm">
                                                    Ubah harga, qty, atau diskon item PO. Sistem menghitung selisihnya otomatis.
                                                </p>
                                                <span class="badge bg-success bg-opacity-10 text-success mt-3 rounded-pill">Disarankan</span>
                                            </div>
                                        </div>
                                    </a>
                                </div>
                                
                                {{-- Opsi 2: Manual (Nominal) --}}
                                <div class="col-md-6">
                                     <a href="#" id="link-manual" class="text-decoration-none">
                                        <div class="card h-100 p-3 border border-2 option-card hover-secondary">
                                            <div class="card-body text-center">
                                                <div class="icon-box bg-secondary bg-opacity-10 text-secondary mx-auto mb-3 rounded-circle d-flex align-items-center justify-content-center" style="width: 60px; height: 60px;">
                                                    <i class="bi bi-input-cursor-text fs-3"></i>
                                                </div>
                                                <h6 class="fw-bold text-dark">Mode Manual</h6>
                                                <p class="small text-muted mb-0 lh-sm">
                                                    Langsung input nominal total untuk Nota Kredit (Potongan) atau Nota Debet (Tagihan).
                                                </p>
                                            </div>
                                        </div>
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>

                </div>
            </div>
        </div>
    </div>
</div>

{{-- STYLE KHUSUS HALAMAN INI --}}
<style>
    .transition-fade { transition: opacity 0.5s ease, transform 0.5s ease; }
    .opacity-0 { opacity: 0; transform: translateY(10px); }
    .opacity-100 { opacity: 1; transform: translateY(0); }

    /* Efek Hover Card Pilihan */
    .option-card { transition: all 0.3s cubic-bezier(.25,.8,.25,1); border-color: #f3f4f6 !important; background: #fff; }
    
    .hover-primary:hover { 
        border-color: #4f46e5 !important; /* Primary Color */
        box-shadow: 0 10px 25px -5px rgba(79, 70, 229, 0.15), 0 8px 10px -6px rgba(79, 70, 229, 0.1); 
        transform: translateY(-3px);
    }
    .hover-primary:hover .icon-box { background-color: #4f46e5 !important; color: #fff !important; }

    .hover-secondary:hover { 
        border-color: #6b7280 !important; /* Secondary Color */
        box-shadow: 0 10px 25px -5px rgba(107, 114, 128, 0.15); 
        transform: translateY(-3px);
    }
    .hover-secondary:hover .icon-box { background-color: #374151 !important; color: #fff !important; }
</style>
@endsection

@push('scripts')
{{-- Select2 CSS & JS sudah di handle layout/app atau stack jika perlu --}}
<script>
document.addEventListener('DOMContentLoaded', function () {
    const poSelect = $('#purchase_order_id');
    poSelect.select2({
        theme: 'bootstrap-5',
        placeholder: '-- Cari dan Pilih Nomor PO --',
        width: '100%'
    });

    const methodSection = document.getElementById('method-selection');
    const linkAuto = document.getElementById('link-auto');
    const linkManual = document.getElementById('link-manual');

    const urlTemplateAuto = "{{ route('purchase-order-adjustments.create.auto', ['purchaseOrder' => ':id']) }}";
    const urlTemplateManual = "{{ route('purchase-order-adjustments.create.manual', ['purchaseOrder' => ':id']) }}";

    function showMethodSection(show) {
        if (show) {
            methodSection.classList.remove('d-none');
            // Sedikit delay agar animasi CSS jalan
            setTimeout(() => {
                methodSection.classList.remove('opacity-0');
                methodSection.classList.add('opacity-100');
            }, 50);
        } else {
            methodSection.classList.remove('opacity-100');
            methodSection.classList.add('opacity-0');
            setTimeout(() => {
                methodSection.classList.add('d-none');
            }, 300);
        }
    }

    poSelect.on('change', function() {
        const selectedPoId = $(this).val();
        
        if (selectedPoId) {
            linkAuto.href = urlTemplateAuto.replace(':id', selectedPoId);
            linkManual.href = urlTemplateManual.replace(':id', selectedPoId);
            showMethodSection(true);
        } else {
            showMethodSection(false);
        }
    });

    // Cek jika datang dari 'show' page (preselectedPoId)
    const preselectedPoId = "{{ $preselectedPurchaseOrderId ?? '' }}";
    if (preselectedPoId) {
        poSelect.val(preselectedPoId).trigger('change');
    }
});
</script>
@endpush
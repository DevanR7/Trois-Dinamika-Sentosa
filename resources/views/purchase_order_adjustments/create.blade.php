@extends('layouts.app')

@push('styles')
<link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/select2-bootstrap-5-theme@1.3.0/dist/select2-bootstrap-5-theme.min.css" />
@endpush

@section('content')
<div class="container py-4">
    <div class="row justify-content-center">
        <div class="col-lg-8">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h2 class="fw-bold mb-0">Buat Penyesuaian PO</h2>
                <a href="{{ route('purchase-orders.index') }}" class="btn btn-outline-secondary">
                    <i class="bi bi-arrow-left"></i> Kembali ke Daftar PO
                </a>
            </div>
            
            <div class="card shadow-sm border-0">
                <div class="card-body p-4">
                    
                    {{-- 1. PILIH PO --}}
                    <div class="mb-4">
                        <label for="purchase_order_id" class="form-label fs-5 fw-semibold">1. Pilih PO yang Akan Dikoreksi</label>
                        <select id="purchase_order_id" class="form-select form-select-lg">
                            <option value="" disabled {{ !$preselectedPurchaseOrderId ? 'selected' : '' }}>-- Cari dan Pilih Nomor PO --</option>
                            @foreach($purchaseOrders as $po)
                                <option value="{{ $po->po_id }}" {{ $preselectedPurchaseOrderId == $po->po_id ? 'selected' : '' }}>
                                    {{ $po->po_number }} - {{ $po->supplier->supplier_name }} 
                                    (Sisa Utang: Rp {{ number_format($po->remaining_balance, 0, ',', '.') }})
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <hr>
                    
                    {{-- 2. PILIH METODE --}}
                    <div id="method-selection" class="d-none">
                        <h5 class="fw-semibold">2. Pilih Metode Penyesuaian</h5>
                        <p class="text-muted">Bagaimana Anda ingin membuat penyesuaian?</p>
                        
                        <div class="row g-3 mt-1">
                            {{-- Opsi 1: Otomatis (Revisi) --}}
                            <div class="col-md-6">
                                <a href="#" id="link-auto" class="text-decoration-none">
                                    <div class="card h-100 card-hover-primary">
                                        <div class="card-body text-center">
                                            <i class="bi bi-magic fs-1 text-primary"></i>
                                            <h6 class="fw-bold mt-2">Koreksi Otomatis (Revisi PO)</h6>
                                            <p class="small text-muted mb-0">
                                                Edit diskon, kuantitas, atau harga item. Sistem akan menghitung selisihnya.
                                            </p>
                                        </div>
                                    </div>
                                </a>
                            </div>
                            
                            {{-- Opsi 2: Manual (Nominal) --}}
                            <div class="col-md-6">
                                 <a href="#" id="link-manual" class="text-decoration-none">
                                    <div class="card h-100 card-hover-secondary">
                                        <div class="card-body text-center">
                                            <i class="bi bi-input-cursor-text fs-1 text-secondary"></i>
                                            <h6 class="fw-bold mt-2">Input Nominal Manual</h6>
                                            <p class="small text-muted mb-0">
                                                Langsung masukkan nominal Nota Kredit/Debit.
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
@endsection

@push('scripts')
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function () {
    const poSelect = $('#purchase_order_id');
    poSelect.select2({
        theme: 'bootstrap-5',
        placeholder: '-- Cari dan Pilih Nomor PO --'
    });

    const methodSection = document.getElementById('method-selection');
    const linkAuto = document.getElementById('link-auto');
    const linkManual = document.getElementById('link-manual');

    const urlTemplateAuto = "{{ route('purchase-order-adjustments.create.auto', ['purchaseOrder' => ':id']) }}";
    const urlTemplateManual = "{{ route('purchase-order-adjustments.create.manual', ['purchaseOrder' => ':id']) }}";

    poSelect.on('change', function() {
        const selectedPoId = $(this).val();
        
        if (selectedPoId) {
            methodSection.classList.remove('d-none');
            linkAuto.href = urlTemplateAuto.replace(':id', selectedPoId);
            linkManual.href = urlTemplateManual.replace(':id', selectedPoId);
        } else {
            methodSection.classList.add('d-none');
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
@extends('layouts.app')

@section('content')
<div class="container py-5">
    <div class="row justify-content-center">
        <div class="col-lg-7 col-md-10">
            
            {{-- HEADER --}}
            <div class="text-center mb-4">
                <h3 class="fw-bold text-dark">Buat Penyesuaian Invoice</h3>
                <p class="text-muted">Koreksi tagihan penjualan (Nota Debet/Kredit)</p>
            </div>

            {{-- KARTU UTAMA --}}
            <div class="card card-transaction border-0 shadow-sm">
                <div class="card-header bg-white p-4 border-bottom border-light">
                    <div class="d-flex justify-content-between align-items-center">
                        <div class="form-section-title mb-0"><i class="bi bi-sliders"></i> Wizard Penyesuaian</div>
                        <a href="{{ route('invoices.index') }}" class="btn btn-sm btn-light border text-muted">
                            <i class="bi bi-x-lg"></i> Batal
                        </a>
                    </div>
                </div>

                <div class="card-body p-4">
                    
                    {{-- LANGKAH 1: PILIH INVOICE --}}
                    <div class="mb-4 position-relative">
                        <span class="position-absolute top-0 start-0 translate-middle badge rounded-pill bg-primary border border-white shadow-sm" style="font-size: 1rem; width: 30px; height: 30px; display: flex; align-items: center; justify-content: center; z-index: 10;">1</span>
                        
                        <div class="ps-4 ms-2">
                            <label for="sales_invoice_id" class="form-label fw-bold text-dark mb-2">Cari Nomor Invoice</label>
                            <select id="sales_invoice_id" class="form-select form-select-lg">
                                <option value="" disabled selected>-- Ketik No. Invoice atau Klien --</option>
                                @foreach($invoices as $invoice)
                                    <option value="{{ $invoice->invoice_id }}">
                                        {{ $invoice->invoice_number }} | {{ $invoice->client->client_name }} 
                                        (Sisa: Rp {{ number_format($invoice->remaining_balance, 0, ',', '.') }})
                                    </option>
                                @endforeach
                            </select>
                            <div class="form-text text-muted small mt-2">
                                <i class="bi bi-info-circle me-1"></i> Hanya invoice yang belum lunas atau lunas sebagian yang disarankan untuk dikoreksi.
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
                                                    Ubah harga, qty, atau diskon item invoice. Sistem menghitung selisihnya otomatis.
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

<style>
    .transition-fade { transition: opacity 0.5s ease, transform 0.5s ease; }
    .opacity-0 { opacity: 0; transform: translateY(10px); }
    .opacity-100 { opacity: 1; transform: translateY(0); }
    .option-card { transition: all 0.3s cubic-bezier(.25,.8,.25,1); border-color: #f3f4f6 !important; background: #fff; }
    .hover-primary:hover { border-color: #4f46e5 !important; box-shadow: 0 10px 25px -5px rgba(79, 70, 229, 0.15); transform: translateY(-3px); }
    .hover-secondary:hover { border-color: #6b7280 !important; box-shadow: 0 10px 25px -5px rgba(107, 114, 128, 0.15); transform: translateY(-3px); }
</style>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    const invoiceSelect = $('#sales_invoice_id');
    invoiceSelect.select2({ theme: 'bootstrap-5', placeholder: '-- Cari dan Pilih Nomor Invoice --', width: '100%' });

    const methodSection = document.getElementById('method-selection');
    const linkAuto = document.getElementById('link-auto');
    const linkManual = document.getElementById('link-manual');

    const urlTemplateAuto = "{{ route('invoice-adjustments.create.auto', ['invoice' => ':id']) }}";
    const urlTemplateManual = "{{ route('invoice-adjustments.create.manual', ['invoice' => ':id']) }}";

    function showMethodSection(show) {
        if (show) {
            methodSection.classList.remove('d-none');
            setTimeout(() => {
                methodSection.classList.remove('opacity-0');
                methodSection.classList.add('opacity-100');
            }, 50);
        } else {
            methodSection.classList.remove('opacity-100');
            methodSection.classList.add('opacity-0');
            setTimeout(() => { methodSection.classList.add('d-none'); }, 300);
        }
    }

    invoiceSelect.on('change', function() {
        const selectedId = $(this).val();
        if (selectedId) {
            linkAuto.href = urlTemplateAuto.replace(':id', selectedId);
            linkManual.href = urlTemplateManual.replace(':id', selectedId);
            showMethodSection(true);
        } else {
            showMethodSection(false);
        }
    });

    const urlParams = new URLSearchParams(window.location.search);
    const preselectedId = urlParams.get('sales_invoice_id');
    if (preselectedId) { invoiceSelect.val(preselectedId).trigger('change'); }
});
</script>
@endpush
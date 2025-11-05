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
                {{-- =================================== --}}
                {{-- ✅ PERBAIKAN 1: Judul Generik --}}
                {{-- =================================== --}}
                <h2 class="fw-bold mb-0">Buat Penyesuaian Invoice</h2>
                
                {{-- =================================== --}}
                {{-- ✅ PERBAIKAN 2: Link Kembali ke Index --}}
                {{-- =================================== --}}
                <a href="{{ route('invoices.index') }}" class="btn btn-outline-secondary">
                    <i class="bi bi-arrow-left"></i> Kembali ke Daftar Invoice
                </a>
            </div>
            
            <div class="card shadow-sm border-0">
                <div class="card-body p-4">
                    
                    {{-- 1. PILIH INVOICE --}}
                    <div class="mb-4">
                        <label for="sales_invoice_id" class="form-label fs-5 fw-semibold">1. Pilih Invoice yang Akan Dikoreksi</label>
                        <select id="sales_invoice_id" class="form-select form-select-lg">
                            <option value="" disabled selected>-- Cari dan Pilih Nomor Invoice --</option>
                            {{-- Variabel $invoices (plural) ini sudah benar dari controller --}}
                            @foreach($invoices as $invoice_item) {{-- Ganti nama variabel lokal agar tidak bentrok --}}
                                <option value="{{ $invoice_item->invoice_id }}">
                                    {{ $invoice_item->invoice_number }} - {{ $invoice_item->client->client_name }} 
                                    (Sisa: Rp {{ number_format($invoice_item->remaining_balance, 0, ',', '.') }})
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <hr>
                    
                    {{-- 2. PILIH METODE --}}
                    <div id="method-selection" class="d-none"> {{-- Awalnya disembunyikan --}}
                        <h5 class="fw-semibold">2. Pilih Metode Penyesuaian</h5>
                        <p class="text-muted">Bagaimana Anda ingin membuat penyesuaian?</p>
                        
                        <div class="row g-3 mt-1">
                            {{-- Opsi 1: Otomatis (Revisi) --}}
                            <div class="col-md-6">
                                <a href="#" id="link-auto" class="text-decoration-none">
                                    <div class="card h-100 card-hover-primary">
                                        <div class="card-body text-center">
                                            <i class="bi bi-magic fs-1 text-primary"></i>
                                            <h6 class="fw-bold mt-2">Koreksi Otomatis (Revisi Invoice)</h6>
                                            <p class="small text-muted mb-0">
                                                Ubah diskon/item. Sistem akan menghitung selisihnya.
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
    // Inisialisasi Select2
    const invoiceSelect = $('#sales_invoice_id');
    invoiceSelect.select2({
        theme: 'bootstrap-5',
        placeholder: '-- Cari dan Pilih Nomor Invoice --'
    });

    const methodSection = document.getElementById('method-selection');
    const linkAuto = document.getElementById('link-auto');
    const linkManual = document.getElementById('link-manual');

    // URL Template
    const urlTemplateAuto = "{{ route('invoice-adjustments.create.auto', ['invoice' => ':id']) }}";
    const urlTemplateManual = "{{ route('invoice-adjustments.create.manual', ['invoice' => ':id']) }}";

    // Event listener untuk dropdown
    invoiceSelect.on('change', function() {
        const selectedInvoiceId = $(this).val();
        
        if (selectedInvoiceId) {
            // Tampilkan bagian pilihan metode
            methodSection.classList.remove('d-none');
            
            // Set href yang benar untuk kedua tombol
            linkAuto.href = urlTemplateAuto.replace(':id', selectedInvoiceId);
            linkManual.href = urlTemplateManual.replace(':id', selectedInvoiceId);
        } else {
            // Sembunyikan jika tidak ada yang dipilih
            methodSection.classList.add('d-none');
        }
    });

    // ✅ TAMBAHAN: Jika user datang dari halaman detail (dengan query string)
    // Coba temukan 'sales_invoice_id' dari URL (jika ada)
    const urlParams = new URLSearchParams(window.location.search);
    const preselectedInvoiceId = urlParams.get('sales_invoice_id');
    
    if (preselectedInvoiceId) {
        invoiceSelect.val(preselectedInvoiceId).trigger('change');
    }
});
</script>
@endpush
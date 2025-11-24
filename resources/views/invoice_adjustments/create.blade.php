@extends('layouts.app')

@section('title', 'Buat Penyesuaian Invoice')

@section('content')
<div class="max-w-3xl mx-auto py-10">
    
    {{-- HEADER --}}
    <div class="text-center mb-8">
        <div class="inline-flex items-center justify-center w-16 h-16 rounded-full bg-indigo-100 text-indigo-600 mb-4 border-4 border-white shadow-sm">
            <i class="bi bi-sliders text-3xl"></i>
        </div>
        <h2 class="text-2xl font-bold text-gray-900 tracking-tight">Buat Penyesuaian Invoice</h2>
        <p class="text-gray-500 mt-2 text-sm">Koreksi tagihan penjualan (Nota Debet/Kredit) untuk Invoice yang sudah berjalan.</p>
    </div>

    {{-- CARD WIZARD --}}
    <div class="bg-white rounded-xl shadow-lg border border-gray-200 overflow-hidden">
        <div class="p-8">
            
            {{-- LANGKAH 1: PILIH INVOICE --}}
            <div class="relative">
                <div class="absolute top-0 left-0 -ml-2 -mt-2">
                    <span class="flex items-center justify-center w-8 h-8 bg-indigo-600 text-white rounded-full font-bold text-sm shadow-md border-2 border-white">1</span>
                </div>
                
                <div class="ml-8">
                    <label for="sales_invoice_id" class="block text-sm font-bold text-gray-800 mb-2">Cari Nomor Invoice</label>
                    <select id="sales_invoice_id" class="w-full">
                        <option value="" disabled {{ !$preselectedInvoiceId ? 'selected' : '' }}>-- Ketik No. Invoice atau Nama Klien --</option>
                        @foreach($invoices as $invoice)
                            <option value="{{ $invoice->invoice_id }}" {{ $preselectedInvoiceId == $invoice->invoice_id ? 'selected' : '' }}>
                                {{ $invoice->invoice_number }} | {{ $invoice->client->client_name }} 
                                (Sisa: Rp {{ number_format($invoice->remaining_balance, 0, ',', '.') }})
                            </option>
                        @endforeach
                    </select>
                    <p class="text-xs text-gray-500 mt-2 flex items-center gap-1">
                        <i class="bi bi-info-circle"></i> Hanya Invoice dengan status <b>Unpaid</b> atau <b>Partially Paid</b>.
                    </p>
                </div>
            </div>

            {{-- LANGKAH 2: PILIH METODE (HIDDEN BY DEFAULT) --}}
            <div id="method-selection" class="mt-10 ml-8 border-t border-dashed border-gray-200 pt-8 hidden opacity-0 transition-all duration-500 relative">
                
                <div class="absolute top-8 left-0 -ml-10">
                    <span class="flex items-center justify-center w-8 h-8 bg-indigo-600 text-white rounded-full font-bold text-sm shadow-md border-2 border-white">2</span>
                </div>

                <h3 class="text-sm font-bold text-gray-800 mb-4">Pilih Metode Koreksi</h3>
                
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    
                    {{-- OPSI 1: OTOMATIS --}}
                    <a href="#" id="link-auto" class="group block">
                        <div class="h-full p-5 rounded-xl border-2 border-gray-100 hover:border-indigo-500 hover:bg-indigo-50/30 transition-all cursor-pointer relative bg-white">
                            <div class="absolute top-3 right-3">
                                <span class="px-2 py-0.5 bg-green-100 text-green-700 text-[10px] font-bold rounded-full uppercase border border-green-200">Detail</span>
                            </div>
                            <div class="w-12 h-12 bg-indigo-100 text-indigo-600 rounded-full flex items-center justify-center mb-4 group-hover:scale-110 transition-transform">
                                <i class="bi bi-magic text-xl"></i>
                            </div>
                            <h4 class="font-bold text-gray-900 mb-1 group-hover:text-indigo-700 text-sm">Mode Revisi Item</h4>
                            <p class="text-xs text-gray-500 leading-relaxed">
                                Ubah harga, qty, atau diskon pada item Invoice secara spesifik. Sistem akan menghitung selisihnya.
                            </p>
                        </div>
                    </a>

                    {{-- OPSI 2: MANUAL --}}
                    <a href="#" id="link-manual" class="group block">
                        <div class="h-full p-5 rounded-xl border-2 border-gray-100 hover:border-gray-400 hover:bg-gray-50 transition-all cursor-pointer bg-white">
                            <div class="w-12 h-12 bg-gray-100 text-gray-600 rounded-full flex items-center justify-center mb-4 group-hover:scale-110 transition-transform">
                                <i class="bi bi-input-cursor-text text-xl"></i>
                            </div>
                            <h4 class="font-bold text-gray-900 mb-1 group-hover:text-gray-800 text-sm">Mode Manual</h4>
                            <p class="text-xs text-gray-500 leading-relaxed">
                                Input nominal total untuk Nota Kredit (Potongan) atau Nota Debet (Tagihan Tambahan) secara langsung.
                            </p>
                        </div>
                    </a>

                </div>
            </div>

        </div>
        <div class="bg-gray-50 px-8 py-4 border-t border-gray-200 text-center">
            <a href="{{ route('invoices.index') }}" class="text-xs text-gray-500 hover:text-gray-700 font-bold uppercase tracking-wider hover:underline">Batal & Kembali</a>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function () {
    // Init Select2
    const invoiceSelect = $('#sales_invoice_id');
    invoiceSelect.select2({
        theme: 'bootstrap-5',
        placeholder: '-- Cari Nomor Invoice --',
        width: '100%',
        allowClear: true
    });

    const methodSection = document.getElementById('method-selection');
    const linkAuto = document.getElementById('link-auto');
    const linkManual = document.getElementById('link-manual');

    // URL Templates (Placeholder :id akan diganti JS)
    // Pastikan route name ini ada di web.php
    const urlTemplateAuto = "{{ route('invoice-adjustments.create.auto', ['invoice' => ':id']) }}";
    const urlTemplateManual = "{{ route('invoice-adjustments.create.manual', ['invoice' => ':id']) }}";

    function toggleMethodSection(show) {
        if (show) {
            methodSection.classList.remove('hidden');
            // Timeout agar animasi opacity jalan
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

    invoiceSelect.on('change', function() {
        const selectedId = $(this).val();
        
        if (selectedId) {
            // Update href links dengan ID yang dipilih
            linkAuto.href = urlTemplateAuto.replace(':id', selectedId);
            linkManual.href = urlTemplateManual.replace(':id', selectedId);
            toggleMethodSection(true);
        } else {
            toggleMethodSection(false);
        }
    });

    // Pre-select jika ada parameter dari URL (misal redirect dari halaman show invoice)
    const preselectedId = "{{ $preselectedInvoiceId ?? '' }}";
    if (preselectedId) {
        invoiceSelect.val(preselectedId).trigger('change');
    }
});
</script>
@endpush
@extends('admin.layouts.app')

@section('title', 'Buat Penyesuaian Invoice')

@section('content')

    <div class="max-w-4xl mx-auto">
        
        <div class="page-header">
            <div>
                <h1 class="page-title">Penyesuaian Invoice</h1>
                <p class="page-subtitle">Pilih faktur dan metode koreksi yang ingin dilakukan.</p>
            </div>
            <a href="{{ route('admin.invoices.index') }}" class="btn btn-secondary">
                <i class="material-icons text-sm mr-1">arrow_back</i> Kembali
            </a>
        </div>

        {{-- 1. PILIH INVOICE --}}
        <div class="card mb-8">
            <div class="card-body">
                <label class="form-label label-required mb-2">Pilih Invoice yang akan dikoreksi</label>
                <select id="invoiceSelect" class="tom-select" placeholder="Cari No. Invoice atau Klien...">
                    <option value="">Pilih Invoice...</option>
                    @foreach($invoices as $inv)
                        <option value="{{ $inv->invoice_id }}" 
                            {{ (request('invoice_id') == $inv->invoice_id) ? 'selected' : '' }}
                            data-number="{{ $inv->invoice_number }}"
                            data-client="{{ $inv->client->client_name }}"
                            data-total="{{ number_format($inv->total_amount, 0, ',', '.') }}">
                            {{ $inv->invoice_number }} - {{ $inv->client->client_name }} (Sisa: Rp {{ number_format($inv->remaining_balance, 0, ',', '.') }})
                        </option>
                    @endforeach
                </select>
                <div class="form-hint mt-2">
                    Hanya invoice dengan status <b>Unpaid, Partially Paid,</b> atau <b>Paid</b> yang dapat disesuaikan.
                </div>
            </div>
        </div>

        {{-- 2. PILIH METODE (Hidden until invoice selected) --}}
        <div id="methodSelection" class="grid grid-cols-1 md:grid-cols-2 gap-6 transition-all duration-500 {{ request('invoice_id') ? '' : 'opacity-50 pointer-events-none grayscale' }}">
            
            {{-- OPTION A: MANUAL --}}
            <a href="#" id="btnManual" class="card hover:border-indigo-500 hover:shadow-md transition-all group h-full">
                <div class="card-body p-6 flex flex-col items-center text-center h-full">
                    <div class="w-16 h-16 rounded-full bg-indigo-50 text-indigo-600 flex items-center justify-center mb-4 group-hover:scale-110 transition-transform">
                        <i class="material-icons text-3xl">tune</i>
                    </div>
                    <h3 class="text-lg font-bold text-slate-800 dark:text-white mb-2">Penyesuaian Manual</h3>
                    <p class="text-sm text-slate-500 mb-6 flex-1">
                        Input nominal koreksi secara langsung (Potongan/Tambahan). Cocok untuk diskon susulan, denda, atau koreksi harga sederhana.
                    </p>
                    <span class="btn btn-primary w-full justify-center">Pilih Manual</span>
                </div>
            </a>

            {{-- OPTION B: AUTO --}}
            <a href="#" id="btnAuto" class="card hover:border-emerald-500 hover:shadow-md transition-all group h-full">
                <div class="card-body p-6 flex flex-col items-center text-center h-full">
                    <div class="w-16 h-16 rounded-full bg-emerald-50 text-emerald-600 flex items-center justify-center mb-4 group-hover:scale-110 transition-transform">
                        <i class="material-icons text-3xl">auto_fix_high</i>
                    </div>
                    <h3 class="text-lg font-bold text-slate-800 dark:text-white mb-2">Revisi Otomatis</h3>
                    <p class="text-sm text-slate-500 mb-6 flex-1">
                        Edit item barang, jumlah, atau pajak layaknya mengedit invoice. Sistem akan menghitung selisihnya otomatis.
                    </p>
                    <span class="btn btn-primary w-full justify-center bg-emerald-600 hover:bg-emerald-700 border-emerald-600">Pilih Otomatis</span>
                </div>
            </a>

        </div>

    </div>

@endsection

@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function() {
        const invoiceSelect = document.getElementById('invoiceSelect');
        const methodSection = document.getElementById('methodSelection');
        const btnManual = document.getElementById('btnManual');
        const btnAuto = document.getElementById('btnAuto');

        // Base Routes (Placeholder ID '0' will be replaced)
        const routeManualBase = "{{ route('admin.invoice-adjustments.create.manual', 0) }}";
        const routeAutoBase = "{{ route('admin.invoice-adjustments.create.auto', 0) }}";

        function updateLinks(id) {
            if (id) {
                methodSection.classList.remove('opacity-50', 'pointer-events-none', 'grayscale');
                
                // Replace '0' with actual ID
                btnManual.href = routeManualBase.slice(0, -1) + id;
                btnAuto.href = routeAutoBase.slice(0, -1) + id;
            } else {
                methodSection.classList.add('opacity-50', 'pointer-events-none', 'grayscale');
                btnManual.href = '#';
                btnAuto.href = '#';
            }
        }

        // Init Tom Select Change Listener
        // Karena Tom Select menyembunyikan select asli, kita listen via API atau event change asli yg ditrigger tomselect
        invoiceSelect.addEventListener('change', function() {
            updateLinks(this.value);
        });

        // Init State (jika ada preselected)
        if(invoiceSelect.value) {
            updateLinks(invoiceSelect.value);
        }
    });
</script>
@endpush
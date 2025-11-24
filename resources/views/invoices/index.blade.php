@extends('layouts.app')

@section('title', 'Daftar Invoice Penjualan')

@section('content')
<div class="max-w-7xl mx-auto">
    
    {{-- HEADER --}}
    <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center mb-6 gap-4">
        <div>
            <h2 class="text-2xl font-bold text-gray-900 tracking-tight">Invoice Penjualan</h2>
            <p class="text-sm text-gray-500 mt-1">Tagihan ke pelanggan (Klien).</p>
        </div>
        <a href="{{ route('invoices.create') }}" class="inline-flex items-center px-4 py-2 bg-indigo-600 hover:bg-indigo-700 text-white text-sm font-bold rounded-lg shadow-md transition flex items-center gap-2">
            <i class="bi bi-plus-lg"></i> Buat Invoice Baru
        </a>
    </div>

    {{-- NOTIFIKASI --}}
    @if (session('success'))
        <div class="mb-6 bg-green-50 border-l-4 border-green-500 p-4 rounded-r shadow-sm flex justify-between items-center animate-fade-in-down">
            <div class="flex items-center gap-3">
                <i class="bi bi-check-circle-fill text-green-500 text-xl"></i>
                <div class="text-sm text-green-700 font-medium">{{ session('success') }}</div>
            </div>
            <button onclick="this.parentElement.remove()" class="text-green-500 hover:text-green-700"><i class="bi bi-x text-lg"></i></button>
        </div>
    @endif

    {{-- FILTER CARD --}}
    <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-5 mb-6">
        <form action="{{ route('invoices.index') }}" method="GET">
            <div class="grid grid-cols-1 md:grid-cols-12 gap-4 items-end">
                
                {{-- 1. PENCARIAN (4 Kolom) --}}
                <div class="md:col-span-4">
                    <label class="block text-xs font-bold text-gray-500 uppercase tracking-wider mb-1">Pencarian</label>
                    <div class="relative">
                        <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                            <i class="bi bi-search text-gray-400"></i>
                        </div>
                        <input type="text" name="search" value="{{ request('search') }}" 
                            class="pl-10 w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 text-sm py-2" 
                            placeholder="Cari No. Invoice / Klien...">
                    </div>
                </div>

                {{-- 2. TANGGAL MULAI (2 Kolom) --}}
                <div class="md:col-span-2">
                    <label class="block text-xs font-bold text-gray-500 uppercase tracking-wider mb-1">Dari Tanggal</label>
                    <input type="date" name="start_date" value="{{ request('start_date') }}" 
                        class="w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 text-sm py-2">
                </div>

                {{-- 3. TANGGAL AKHIR (2 Kolom) --}}
                <div class="md:col-span-2">
                    <label class="block text-xs font-bold text-gray-500 uppercase tracking-wider mb-1">Sampai Tanggal</label>
                    <input type="date" name="end_date" value="{{ request('end_date') }}" 
                        class="w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 text-sm py-2">
                </div>

                {{-- 4. STATUS (2 Kolom) --}}
                <div class="md:col-span-2">
                    <label class="block text-xs font-bold text-gray-500 uppercase tracking-wider mb-1">Status</label>
                    <select name="status" class="w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 text-sm py-2">
                        <option value="">-- Semua --</option>
                        <option value="draft" @selected(request('status') == 'draft')>Draft</option>
                        <option value="unpaid" @selected(request('status') == 'unpaid')>Belum Lunas</option>
                        <option value="partially_paid" @selected(request('status') == 'partially_paid')>Cicil</option>
                        <option value="paid" @selected(request('status') == 'paid')>Lunas</option>
                        <option value="cancelled" @selected(request('status') == 'cancelled')>Dibatalkan</option>
                    </select>
                </div>

                {{-- 5. TOMBOL (2 Kolom) --}}
                <div class="md:col-span-2 flex gap-2">
                    <button type="submit" class="flex-1 bg-gray-800 hover:bg-gray-900 text-white font-medium py-2 px-4 rounded-md shadow-sm transition text-sm flex items-center justify-center gap-2">
                        <i class="bi bi-funnel-fill"></i>
                    </button>
                    <a href="{{ route('invoices.index') }}" class="px-3 py-2 bg-white border border-gray-300 rounded-md text-gray-500 hover:text-indigo-600 hover:border-indigo-300 transition flex items-center justify-center shadow-sm" title="Reset">
                        <i class="bi bi-arrow-clockwise text-lg"></i>
                    </a>
                </div>
            </div>
        </form>
    </div>

    {{-- LIST CARD --}}
    <div class="flex flex-col gap-4">
        @forelse ($invoices as $invoice)
            @php
                $sisaPiutang = $invoice->remaining_balance;
                $borderColor = 'border-l-gray-400';
                if($invoice->status == 'paid') $borderColor = 'border-l-green-500';
                elseif($invoice->status == 'cancelled') $borderColor = 'border-l-red-500';
                elseif($invoice->status == 'draft') $borderColor = 'border-l-gray-500';
                elseif(optional($invoice->due_date)->isPast() && $invoice->status != 'paid') $borderColor = 'border-l-red-500'; // Overdue
                elseif($invoice->status == 'unpaid' || $invoice->status == 'partially_paid') $borderColor = 'border-l-yellow-500';
            @endphp

            <div class="bg-white rounded-lg shadow-sm border border-gray-200 overflow-hidden border-l-4 {{ $borderColor }} hover:shadow-md transition-shadow">
                
                {{-- HEADER CARD --}}
                <div class="p-5 cursor-pointer hover:bg-gray-50 transition-colors" onclick="toggleAccordion('collapse-{{ $invoice->invoice_id }}')">
                    <div class="flex flex-col lg:flex-row lg:items-center justify-between gap-6">
                        
                        {{-- Info Utama --}}
                        <div class="flex items-center gap-4 lg:w-1/3">
                            <div class="w-12 h-12 rounded-full bg-indigo-50 flex items-center justify-center text-indigo-600 flex-shrink-0 border border-indigo-100">
                                <i class="bi bi-receipt text-xl"></i>
                            </div>
                            <div>
                                <h3 class="text-base font-bold text-gray-900 mb-0.5">{{ $invoice->invoice_number }}</h3>
                                <p class="text-sm font-medium text-indigo-600">{{ $invoice->client->client_name ?? 'Klien Dihapus' }}</p>
                            </div>
                        </div>

                        {{-- Tanggal & Total --}}
                        <div class="flex gap-8 lg:w-1/3">
                            <div>
                                <span class="block text-[10px] font-bold text-gray-500 uppercase mb-1">Tanggal</span>
                                <span class="text-sm font-medium text-gray-900">{{ optional($invoice->order_date)->format('d M Y') }}</span>
                            </div>
                            <div>
                                <span class="block text-[10px] font-bold text-gray-500 uppercase mb-1">Total Tagihan</span>
                                <span class="text-sm font-bold text-gray-900">Rp {{ number_format($invoice->total_amount, 0, ',', '.') }}</span>
                            </div>
                        </div>

                        {{-- Status & Icon --}}
                        <div class="flex items-center justify-between lg:justify-end gap-4 lg:w-1/3">
                            <div class="text-right">
                                @php
                                    $statusStyles = [
                                        'paid' => 'bg-green-100 text-green-800 border-green-200',
                                        'partially_paid' => 'bg-blue-100 text-blue-800 border-blue-200',
                                        'unpaid' => 'bg-yellow-100 text-yellow-800 border-yellow-200',
                                        'draft' => 'bg-gray-100 text-gray-600 border-gray-200',
                                        'cancelled' => 'bg-red-100 text-red-800 border-red-200',
                                    ];
                                    
                                    $labelStatus = [
                                        'paid' => 'Lunas',
                                        'partially_paid' => 'Cicil',
                                        'unpaid' => 'Belum Lunas',
                                        'draft' => 'Draft',
                                        'cancelled' => 'Batal',
                                    ];

                                    // Cek Overdue
                                    if(optional($invoice->due_date)->isPast() && $invoice->status != 'paid' && $invoice->status != 'cancelled' && $invoice->status != 'draft') {
                                        $statusClass = 'bg-red-100 text-red-800 border-red-200';
                                        $statusText = 'Jatuh Tempo';
                                    } else {
                                        $statusClass = $statusStyles[$invoice->status] ?? 'bg-gray-100 text-gray-600';
                                        $statusText = $labelStatus[$invoice->status] ?? Str::title($invoice->status);
                                    }
                                @endphp
                                <span class="px-3 py-1 rounded-full text-xs font-bold border uppercase {{ $statusClass }}">
                                    {{ $statusText }}
                                </span>

                                @if($sisaPiutang > 0 && $invoice->status != 'cancelled' && $invoice->status != 'draft')
                                    <div class="text-[10px] text-red-500 font-bold mt-1">Sisa: Rp {{ number_format($sisaPiutang, 0, ',', '.') }}</div>
                                @endif
                            </div>
                            
                            <i class="bi bi-chevron-down text-gray-400 transition-transform duration-200" id="icon-collapse-{{ $invoice->invoice_id }}"></i>
                        </div>
                    </div>
                </div>

                {{-- COLLAPSE BODY --}}
                <div id="collapse-{{ $invoice->invoice_id }}" class="hidden bg-gray-50 border-t border-gray-100">
                    <div class="p-5 flex flex-col sm:flex-row justify-between items-center gap-4">
                        <div class="text-sm text-gray-600 flex gap-4">
                            <div><strong>Jatuh Tempo:</strong> {{ optional($invoice->due_date)->format('d M Y') }}</div>
                            <div><strong>Sales:</strong> {{ $invoice->sales->full_name ?? '-' }}</div>
                        </div>
                        
                        <div class="flex gap-2">
                            <a href="{{ route('invoices.show', $invoice->invoice_id) }}" class="px-3 py-1.5 bg-white border border-indigo-200 text-indigo-700 font-medium rounded-lg hover:bg-indigo-50 transition text-sm shadow-sm flex items-center gap-1">
                                <i class="bi bi-eye"></i> Detail
                            </a>

                            @if(!in_array($invoice->status, ['paid', 'cancelled']))
                                <a href="{{ route('invoices.edit', $invoice->invoice_id) }}" class="px-3 py-1.5 bg-white border border-yellow-200 text-yellow-700 font-medium rounded-lg hover:bg-yellow-50 transition text-sm shadow-sm flex items-center gap-1">
                                    <i class="bi bi-pencil"></i> Edit
                                </a>

                                @if($invoice->status != 'draft')
                                    <form action="{{ route('invoices.cancel', $invoice->invoice_id) }}" method="POST" class="cancel-form inline-block">
                                        @csrf
                                        <button type="submit" class="px-3 py-1.5 bg-white border border-red-200 text-red-600 font-medium rounded-lg hover:bg-red-50 transition text-sm shadow-sm flex items-center gap-1">
                                            <i class="bi bi-x-circle"></i> Batal
                                        </button>
                                    </form>
                                @endif
                            @endif

                            @if($invoice->status == 'draft')
                                <form action="{{ route('invoices.confirm', $invoice->invoice_id) }}" method="POST" class="confirm-form inline-block">
                                    @csrf
                                    <button type="submit" class="px-3 py-1.5 bg-green-600 text-white font-bold rounded-lg hover:bg-green-700 transition text-sm shadow-sm flex items-center gap-1">
                                        <i class="bi bi-check-circle"></i> Konfirmasi
                                    </button>
                                </form>
                            @endif
                        </div>
                    </div>
                </div>

            </div>
        @empty
            <div class="text-center py-12 bg-white rounded-xl border border-dashed border-gray-300">
                <div class="w-16 h-16 bg-gray-100 rounded-full flex items-center justify-center mx-auto mb-4">
                    <i class="bi bi-receipt-cutoff text-3xl text-gray-400"></i>
                </div>
                <h3 class="text-lg font-medium text-gray-900">Tidak ada invoice</h3>
                <p class="text-gray-500 text-sm mt-1">Belum ada data tagihan yang sesuai filter.</p>
                <a href="{{ route('invoices.create') }}" class="inline-flex items-center mt-4 px-4 py-2 bg-indigo-600 hover:bg-indigo-700 text-white text-sm font-bold rounded-lg shadow-md transition">
                    <i class="bi bi-plus-lg mr-2"></i> Buat Invoice Baru
                </a>
            </div>
        @endforelse
    </div>

    <div class="mt-6">
        {{ $invoices->appends(request()->query())->links() }}
    </div>
</div>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Helper Toggle Accordion
    window.toggleAccordion = function(id) {
        const el = document.getElementById(id);
        const icon = document.getElementById('icon-' + id);
        if(el.classList.contains('hidden')) {
            el.classList.remove('hidden');
            if(icon) icon.classList.add('rotate-180');
        } else {
            el.classList.add('hidden');
            if(icon) icon.classList.remove('rotate-180');
        }
    }

    // Script Konfirmasi (SweetAlert)
    function confirmAction(selector, title, text, btnText, btnColor) {
        document.querySelectorAll(selector).forEach(form => {
            form.addEventListener('submit', function(event) {
                event.preventDefault();
                Swal.fire({
                    title: title, text: text, icon: 'warning',
                    showCancelButton: true, confirmButtonColor: btnColor, cancelButtonColor: '#6b7280',
                    confirmButtonText: btnText, cancelButtonText: 'Batal'
                }).then((result) => { if (result.isConfirmed) event.target.submit(); });
            });
        });
    }
    
    confirmAction('.cancel-form', 'Batalkan Invoice?', 'Status akan berubah menjadi Cancelled.', '#ef4444', 'Ya, Batalkan!');
    confirmAction('.confirm-form', 'Konfirmasi Invoice?', 'Stok akan dikurangi dan invoice menjadi Unpaid.', '#10b981', 'Ya, Konfirmasi!');
});
</script>
@endpush
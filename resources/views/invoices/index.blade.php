@extends('layouts.app')

@section('title', 'Daftar Invoice Penjualan')

@section('content')
<div class="max-w-7xl mx-auto pb-20 animate-enter">
    
    {{-- HEADER --}}
    <div class="flex flex-col sm:flex-row justify-between items-end gap-4 mb-8">
        <div>
            <h1 class="text-2xl font-bold text-slate-800 tracking-tight">Invoice Penjualan</h1>
            <p class="text-slate-500 text-sm mt-1">Kelola tagihan dan pembayaran pelanggan.</p>
        </div>
        <a href="{{ route('invoices.create') }}" class="h-[48px] px-8 bg-indigo-600 hover:bg-indigo-700 text-white text-sm font-bold rounded-lg shadow-md transition-all flex items-center justify-center gap-2 group hover:-translate-y-0.5">
            <i class="material-icons text-[20px] group-hover:rotate-90 transition-transform">add</i> 
            <span>Buat Invoice</span>
        </a>
    </div>

    {{-- FILTER CARD --}}
    <div class="dashboard-card p-6 mb-6 shadow-sm">
        <form action="{{ route('invoices.index') }}" method="GET">
            <div class="grid grid-cols-1 md:grid-cols-12 gap-4 items-end">
                
                {{-- Pencarian --}}
                <div class="md:col-span-4">
                    <label class="block text-xs font-bold text-slate-500 uppercase mb-1.5">Pencarian</label>
                    <div class="relative">
                        <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                            <i class="material-icons text-slate-400 text-[20px]">search</i>
                        </div>
                        <input type="text" name="search" value="{{ request('search') }}" 
                            class="form-input pl-10" 
                            placeholder="No. Invoice / Klien...">
                    </div>
                </div>

                {{-- Tanggal --}}
                <div class="md:col-span-3">
                    <label class="block text-xs font-bold text-slate-500 uppercase mb-1.5">Tanggal Invoice</label>
                    <input type="date" name="date" value="{{ request('date') }}" class="form-input">
                </div>

                {{-- Status --}}
                <div class="md:col-span-3">
                    <label class="block text-xs font-bold text-slate-500 uppercase mb-1.5">Status</label>
                    <select name="status" class="form-input select2-basic">
                        <option value="">-- Semua Status --</option>
                        <option value="draft" @selected(request('status') == 'draft')>Draft</option>
                        <option value="unpaid" @selected(request('status') == 'unpaid')>Belum Lunas</option>
                        <option value="partially_paid" @selected(request('status') == 'partially_paid')>Cicilan</option>
                        <option value="paid" @selected(request('status') == 'paid')>Lunas</option>
                        <option value="overdue" @selected(request('status') == 'overdue')>Jatuh Tempo</option>
                        <option value="cancelled" @selected(request('status') == 'cancelled')>Dibatalkan</option>
                    </select>
                </div>

                {{-- Tombol --}}
                <div class="md:col-span-2 flex gap-2">
                    <button type="submit" class="flex-1 h-[48px] bg-slate-800 hover:bg-slate-900 text-white font-bold rounded-lg shadow-sm transition-all flex items-center justify-center gap-2">
                        <i class="material-icons text-[18px]">filter_list</i> Filter
                    </button>
                    <a href="{{ route('invoices.index') }}" class="h-[48px] w-[48px] flex items-center justify-center bg-white border border-slate-300 text-slate-500 hover:text-indigo-600 font-medium rounded-lg shadow-sm transition" title="Reset">
                        <i class="material-icons text-[20px]">refresh</i>
                    </a>
                </div>
            </div>
        </form>
    </div>
    
    {{-- LIST INVOICE (ACCORDION STYLE) --}}
    <div class="space-y-4">
        @forelse ($invoices as $invoice)
            <x-ui.accordion-card id="inv-{{ $invoice->invoice_id }}">
                
                {{-- HEADER (Tampilan Luar) --}}
                <x-slot name="header">
                
                {{-- Kiri: Info Utama --}}
                <div class="flex items-center gap-4 pr-4 flex-grow min-w-0">
                    <div class="w-12 h-12 rounded-xl bg-indigo-50 flex items-center justify-center text-indigo-600 flex-shrink-0 border border-indigo-100">
                        <i class="material-icons text-xl">receipt</i>
                    </div>
                    <div class="min-w-0">
                        <h3 class="text-base font-bold text-slate-800 truncate hover:text-indigo-600 transition-colors">
                            {{ $invoice->invoice_number }}
                        </h3>
                        <div class="text-xs text-slate-500 flex items-center gap-1 mt-0.5 truncate">
                            <i class="material-icons text-[14px]">business</i> {{ $invoice->client->client_name }}
                        </div>
                    </div>
                </div>

                {{-- Kanan: Status & Nominal --}}
                <div class="flex items-center gap-3 sm:gap-5 ml-auto flex-shrink-0 pl-4">
                    
                    {{-- Blok Sisa Tagihan --}}
                    <div class="text-right hidden sm:block">
                        <span class="block text-[10px] font-bold text-slate-400 uppercase tracking-wider leading-tight mb-0.5">Sisa Tagihan</span>
                        <span class="text-sm font-bold font-mono {{ $invoice->remaining_balance > 0 ? 'text-red-600' : 'text-emerald-600' }} leading-tight">
                            Rp {{ number_format($invoice->remaining_balance, 0, ',', '.') }}
                        </span>
                    </div>

                    {{-- Garis Pemisah Vertikal --}}
                    <div class="hidden sm:block w-px h-8 bg-slate-200"></div>

                    {{-- Status Badge --}}
                    @php
                        $statusMap = [
                            'paid' => ['class' => 'status-completed', 'label' => 'Lunas', 'icon' => 'check_circle'],
                            'partially_paid' => ['class' => 'status-approved', 'label' => 'Cicilan', 'icon' => 'pie_chart'],
                            'cancelled' => ['class' => 'status-rejected', 'label' => 'Batal', 'icon' => 'cancel'],
                            'draft' => ['class' => 'status-draft', 'label' => 'Draft', 'icon' => 'edit_note'],
                            'unpaid' => ['class' => 'status-pending', 'label' => 'Belum Lunas', 'icon' => 'schedule'],
                        ];

                        if ($invoice->due_date->isPast() && !in_array($invoice->status, ['paid', 'cancelled', 'draft'])) {
                            $st = ['class' => 'bg-red-100 text-red-800 border border-red-200', 'label' => 'Jatuh Tempo', 'icon' => 'warning'];
                        } else {
                            $st = $statusMap[$invoice->status] ?? ['class' => 'status-pending', 'label' => 'Unknown', 'icon' => 'help'];
                        }
                    @endphp
                    
                    <div>
                        <span class="{{ $st['class'] }} inline-flex items-center justify-center px-2.5 py-1 rounded-md text-[11px] font-bold uppercase w-fit gap-1">
                            <i class="material-icons text-[12px]">{{ $st['icon'] }}</i> <span class="hidden sm:inline">{{ $st['label'] }}</span>
                        </span>
                    </div>

                    {{-- [HAPUS BAGIAN ICON DI SINI, KARENA SUDAH ADA DI COMPONENT] --}}
                    
                </div>
            </x-slot>

                {{-- BODY (Isi Detail) --}}
                <div class="grid grid-cols-1 lg:grid-cols-12 gap-6 text-sm">
                    
                    {{-- Info Tanggal --}}
                    <div class="lg:col-span-3 space-y-3">
                        <h4 class="text-xs font-bold text-slate-500 uppercase tracking-wider mb-2">Tanggal</h4>
                        <div class="flex justify-between">
                            <span class="text-slate-500">Dibuat</span>
                            <span class="font-medium text-slate-800">{{ $invoice->order_date->format('d M Y') }}</span>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-slate-500">Jatuh Tempo</span>
                            <span class="font-medium {{ optional($invoice->due_date)->isPast() && $invoice->status != 'paid' ? 'text-red-600' : 'text-slate-800' }}">
                                {{ $invoice->due_date->format('d M Y') }}
                            </span>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-slate-500">Sales</span>
                            <span class="font-medium text-slate-800">{{ $invoice->sales->full_name ?? '-' }}</span>
                        </div>
                    </div>

                    {{-- Info Keuangan --}}
                    <div class="lg:col-span-4 space-y-3 lg:border-l lg:border-r border-slate-100 lg:px-6">
                        <h4 class="text-xs font-bold text-slate-500 uppercase tracking-wider mb-2">Rincian Nilai</h4>
                        <div class="flex justify-between">
                            <span class="text-slate-500">Total Tagihan</span>
                            <span class="font-bold text-slate-900 font-mono">Rp {{ number_format($invoice->total_amount, 0, ',', '.') }}</span>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-slate-500">Sudah Dibayar</span>
                            <span class="font-bold text-green-600 font-mono">Rp {{ number_format($invoice->amount_paid, 0, ',', '.') }}</span>
                        </div>
                        <div class="pt-2 border-t border-slate-100 flex justify-between">
                            <span class="text-slate-500 font-bold">Sisa</span>
                            <span class="font-bold {{ $invoice->remaining_balance > 0 ? 'text-red-600' : 'text-slate-400' }} font-mono">
                                Rp {{ number_format($invoice->remaining_balance, 0, ',', '.') }}
                            </span>
                        </div>
                    </div>

                    {{-- Tombol Aksi --}}
                    <div class="lg:col-span-5 flex flex-col gap-2 justify-center">
                        <h4 class="text-xs font-bold text-slate-500 uppercase tracking-wider mb-2">Tindakan</h4>
                        
                        <div class="flex gap-2">
                            {{-- Tombol Detail --}}
                            <a href="{{ route('invoices.show', $invoice->invoice_id) }}" class="flex-1 px-3 py-2 bg-white border border-slate-300 text-slate-700 rounded-lg text-xs font-bold hover:bg-slate-50 transition text-center flex items-center justify-center gap-1">
                                <i class="material-icons text-[16px]">visibility</i> Detail
                            </a>
                            
                            {{-- Tombol PDF --}}
                            <a href="{{ route('invoices.pdf', $invoice->invoice_id) }}" class="flex-1 px-3 py-2 bg-white border border-slate-300 text-slate-700 rounded-lg text-xs font-bold hover:bg-slate-50 transition text-center flex items-center justify-center gap-1" target="_blank">
                                <i class="material-icons text-[16px]">picture_as_pdf</i> PDF
                            </a>
                        </div>

                        {{-- LOGIKA STATUS --}}
                        @if($invoice->status == 'draft')
                            <div class="flex gap-2 mt-1">
                                {{-- Tombol Konfirmasi (Hijau) --}}
                                <form action="{{ route('invoices.confirm', $invoice->invoice_id) }}" method="POST" class="form-confirm w-full">
                                    @csrf
                                    <button type="submit" 
                                            data-title="Konfirmasi Invoice?" 
                                            data-text="Stok akan dikurangi dan piutang akan dicatat." 
                                            data-btn-text="Ya, Konfirmasi"
                                            data-btn-color="#10b981"
                                            data-icon="check"
                                            class="w-full px-3 py-2 bg-emerald-600 text-white rounded-lg text-xs font-bold hover:bg-emerald-700 transition flex items-center justify-center gap-1 shadow-sm">
                                        <i class="material-icons text-[16px]">check_circle</i> Konfirmasi
                                    </button>
                                </form>

                                {{-- Edit --}}
                                <a href="{{ route('invoices.edit', $invoice->invoice_id) }}" class="px-3 py-2 bg-amber-50 border border-amber-200 text-amber-700 rounded-lg text-xs font-bold hover:bg-amber-100 transition flex items-center justify-center gap-1">
                                    <i class="material-icons text-[16px]">edit</i>
                                </a>

                                {{-- Hapus Draft --}}
                                <form action="{{ route('invoices.destroy', $invoice->invoice_id) }}" method="POST" class="delete-form">
                                    @csrf @method('DELETE')
                                    <button type="submit" class="h-full px-3 bg-red-50 border border-red-200 text-red-600 rounded-lg hover:bg-red-100 transition flex items-center justify-center" title="Hapus Draft">
                                        <i class="material-icons text-[16px]">delete</i>
                                    </button>
                                </form>
                            </div>
                        @elseif(!in_array($invoice->status, ['cancelled', 'paid']) && $invoice->remaining_balance > 0)
                            {{-- Tombol Bayar --}}
                            <button onclick="location.href='{{ route('invoices.show', $invoice->invoice_id) }}'" class="w-full px-3 py-2 bg-indigo-600 text-white rounded-lg text-xs font-bold hover:bg-indigo-700 transition flex items-center justify-center gap-1 shadow-sm mt-1">
                                <i class="material-icons text-[16px]">payments</i> Catat Pembayaran
                            </button>
                        @endif

                    </div>
                </div>

            </x-ui.accordion-card>
        @empty
            <div class="col-span-full py-16 text-center bg-white rounded-xl border border-dashed border-slate-300">
                <div class="w-16 h-16 bg-slate-50 rounded-full flex items-center justify-center mb-4 mx-auto">
                    <i class="material-icons text-slate-300 text-4xl">receipt_long</i>
                </div>
                <h3 class="text-lg font-bold text-slate-700">Belum ada invoice</h3>
                <p class="text-slate-500 text-sm mt-1 max-w-xs mx-auto">Silakan buat invoice baru untuk memulai pencatatan.</p>
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
        $('.select2-basic').select2({ minimumResultsForSearch: Infinity, width: '100%', dropdownCssClass: 'select2-dropdown-clean' });
        @if(session('success')) window.showToast("{{ session('success') }}", 'success'); @endif
        @if(session('error')) window.showToast("{{ session('error') }}", 'error'); @endif
    });
</script>
@endpush
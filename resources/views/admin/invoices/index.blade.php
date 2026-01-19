@extends('admin.layouts.app')

@section('title', 'Daftar Invoice Penjualan')

@section('content')
<div class="flex flex-col gap-6">

    {{-- HEADER SECTION --}}
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div>
            <h1 class="page-title">Invoice Penjualan</h1>
            <p class="page-subtitle">Kelola tagihan, pantau jatuh tempo, dan riwayat pembayaran.</p>
        </div>
        
        <a href="{{ route('admin.invoices.create') }}" class="btn btn-primary shadow-lg shadow-indigo-500/30">
            <i class="material-icons text-lg">add</i>
            <span>Buat Invoice Baru</span>
        </a>
    </div>

    {{-- STATISTIK / INDIKATOR --}}
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
        
        {{-- Card 1: Total Omset (Paid) --}}
        <div class="card p-4 border-l-4 border-l-emerald-500 relative overflow-hidden group">
            <div class="absolute right-2 top-2 opacity-10 group-hover:opacity-20 transition-opacity">
                <i class="material-icons text-6xl text-emerald-600">payments</i>
            </div>
            <p class="text-xs text-slate-500 uppercase font-bold mb-1">Pendapatan (Lunas)</p>
            <h3 class="text-xl font-black text-emerald-600">
                Rp {{ number_format(\App\Models\SalesInvoice::where('status', 'paid')->sum('total_amount'), 0, ',', '.') }}
            </h3>
            <p class="text-[10px] text-emerald-500 mt-1">Total uang masuk</p>
        </div>

        {{-- Card 2: Piutang (Unpaid + Partial) --}}
        <div class="card p-4 border-l-4 border-l-rose-500 relative overflow-hidden group">
            <div class="absolute right-2 top-2 opacity-10 group-hover:opacity-20 transition-opacity">
                <i class="material-icons text-6xl text-rose-600">pending_actions</i>
            </div>
            <p class="text-xs text-slate-500 uppercase font-bold mb-1">Piutang (Belum Lunas)</p>
            <h3 class="text-xl font-black text-rose-600">
                Rp {{ number_format(\App\Models\SalesInvoice::whereIn('status', ['unpaid', 'partially_paid'])->sum(DB::raw('total_amount - amount_paid')), 0, ',', '.') }}
            </h3>
            <p class="text-[10px] text-rose-500 mt-1">
                {{ \App\Models\SalesInvoice::whereIn('status', ['unpaid', 'partially_paid'])->count() }} Invoice Menunggu
            </p>
        </div>

        {{-- Card 3: Draft --}}
        <div class="card p-4 border-l-4 border-l-amber-500 relative overflow-hidden group">
            <div class="absolute right-2 top-2 opacity-10 group-hover:opacity-20 transition-opacity">
                <i class="material-icons text-6xl text-amber-600">edit_note</i>
            </div>
            <p class="text-xs text-slate-500 uppercase font-bold mb-1">Draft Invoice</p>
            <h3 class="text-xl font-black text-amber-600">
                {{ \App\Models\SalesInvoice::where('status', 'draft')->count() }}
            </h3>
            <p class="text-[10px] text-amber-500 mt-1">Belum diterbitkan</p>
        </div>

        {{-- Card 4: Jatuh Tempo --}}
        <div class="card p-4 border-l-4 border-l-purple-500 relative overflow-hidden group">
            <div class="absolute right-2 top-2 opacity-10 group-hover:opacity-20 transition-opacity">
                <i class="material-icons text-6xl text-purple-600">event_busy</i>
            </div>
            <p class="text-xs text-slate-500 uppercase font-bold mb-1">Lewat Jatuh Tempo</p>
            <h3 class="text-xl font-black text-purple-600">
                {{ \App\Models\SalesInvoice::where('status', '!=', 'paid')->where('status', '!=', 'cancelled')->where('status', '!=', 'draft')->where('due_date', '<', now())->count() }}
            </h3>
            <p class="text-[10px] text-purple-500 mt-1">Perlu ditindaklanjuti</p>
        </div>
    </div>

    {{-- FILTER SECTION --}}
    <div class="card p-4">
        <form method="GET" action="{{ route('admin.invoices.index') }}" class="grid grid-cols-1 md:grid-cols-12 gap-4">
            
            {{-- Search --}}
            <div class="md:col-span-4 relative">
                <div class="absolute inset-y-0 left-0 flex items-center pl-3 pointer-events-none">
                    <i class="material-icons text-slate-400">search</i>
                </div>
                <input type="text" name="search" value="{{ request('search') }}" 
                    class="form-input pl-10" 
                    placeholder="Cari No. Invoice atau Nama Klien...">
            </div>

            {{-- Status Filter --}}
            <div class="md:col-span-2">
                <select name="status" class="form-select" onchange="this.form.submit()">
                    <option value="">Semua Status</option>
                    <option value="draft" {{ request('status') == 'draft' ? 'selected' : '' }}>Draft (Konsep)</option>
                    <option value="unpaid" {{ request('status') == 'unpaid' ? 'selected' : '' }}>Belum Bayar</option>
                    <option value="partially_paid" {{ request('status') == 'partially_paid' ? 'selected' : '' }}>Bayar Sebagian</option>
                    <option value="paid" {{ request('status') == 'paid' ? 'selected' : '' }}>Lunas</option>
                    <option value="cancelled" {{ request('status') == 'cancelled' ? 'selected' : '' }}>Dibatalkan</option>
                </select>
            </div>

            {{-- Date Range --}}
            <div class="md:col-span-2">
                <input type="date" name="start_date" class="form-input" value="{{ request('start_date') }}" title="Mulai Tanggal">
            </div>
            <div class="md:col-span-2">
                <input type="date" name="end_date" class="form-input" value="{{ request('end_date') }}" title="Sampai Tanggal">
            </div>

            {{-- Button --}}
            <div class="md:col-span-2">
                 <div class="flex gap-2">
                     <button type="submit" class="btn btn-secondary w-full justify-center">Filter</button>
                     <a href="{{ route('admin.invoices.index') }}" class="btn btn-icon btn-secondary" title="Reset">
                         <i class="material-icons">refresh</i>
                     </a>
                 </div>
            </div>
        </form>
    </div>

    {{-- ACCORDION LIST --}}
    <div x-data="{ active: null }" class="space-y-3">
        
        @forelse($invoices as $invoice)
            <div class="card overflow-hidden transition-all duration-300 hover:shadow-md border border-slate-200 dark:border-slate-700">
                
                {{-- CARD HEADER (CLICKABLE) --}}
                <div @click="active === {{ $invoice->invoice_id }} ? active = null : active = {{ $invoice->invoice_id }}" 
                     class="p-4 cursor-pointer bg-white dark:bg-slate-800 hover:bg-slate-50 dark:hover:bg-slate-700/50 transition-colors">
                    
                    <div class="flex flex-col lg:flex-row lg:items-center justify-between gap-4">
                        
                        {{-- 1. Info Utama --}}
                        <div class="flex items-center gap-4 min-w-[30%]">
                            <div class="hidden sm:flex h-11 w-11 rounded-xl bg-slate-100 dark:bg-slate-700 items-center justify-center text-slate-500 font-bold text-sm border border-slate-200 dark:border-slate-600">
                                {{ substr($invoice->client->client_name ?? 'U', 0, 1) }}
                            </div>
                            <div>
                                <div class="flex items-center gap-2">
                                    <span class="font-bold text-indigo-600 dark:text-indigo-400 text-lg tracking-tight">
                                        {{ $invoice->invoice_number }}
                                    </span>
                                    @php
                                        $badgeClass = match($invoice->status) {
                                            'paid' => 'badge-success',
                                            'partially_paid' => 'badge-warning',
                                            'unpaid' => 'badge-danger',
                                            'draft' => 'badge-primary',
                                            'cancelled' => 'bg-slate-200 text-slate-600',
                                            default => 'badge-secondary'
                                        };
                                        $statusLabel = match($invoice->status) {
                                            'paid' => 'Lunas',
                                            'partially_paid' => 'Cicilan',
                                            'unpaid' => 'Belum Lunas',
                                            'draft' => 'Draft',
                                            'cancelled' => 'Batal',
                                            default => ucfirst($invoice->status)
                                        };
                                    @endphp
                                    <span class="badge {{ $badgeClass }}">{{ $statusLabel }}</span>
                                </div>
                                <div class="text-sm font-medium text-slate-700 dark:text-slate-200 flex items-center gap-1">
                                    <i class="material-icons text-[14px] text-slate-400">business</i>
                                    {{ $invoice->client->client_name ?? 'Klien Terhapus' }}
                                </div>
                            </div>
                        </div>

                        {{-- 2. Info Tanggal --}}
                        <div class="flex flex-col sm:flex-row gap-2 sm:gap-8 text-sm text-slate-500 dark:text-slate-400">
                            <div>
                                <span class="block text-[10px] uppercase font-bold text-slate-400">Terbit</span>
                                <span class="font-medium text-slate-700 dark:text-slate-300">
                                    {{ $invoice->order_date->format('d M Y') }}
                                </span>
                            </div>
                            <div>
                                <span class="block text-[10px] uppercase font-bold text-slate-400">Jatuh Tempo</span>
                                <span class="font-medium {{ $invoice->due_date < now() && $invoice->status != 'paid' && $invoice->status != 'cancelled' ? 'text-red-600 font-bold' : 'text-slate-700 dark:text-slate-300' }}">
                                    {{ $invoice->due_date->format('d M Y') }}
                                </span>
                            </div>
                        </div>

                        {{-- 3. Info Nominal & Progress --}}
                        <div class="min-w-[20%] text-right">
                            <div class="text-xs text-slate-400 uppercase font-bold mb-0.5">Total Tagihan</div>
                            <div class="text-lg font-black text-slate-800 dark:text-white">
                                Rp {{ number_format($invoice->total_amount, 0, ',', '.') }}
                            </div>
                            
                            @if($invoice->status !== 'draft' && $invoice->status !== 'cancelled')
                                @php
                                    $percent = $invoice->total_amount > 0 ? ($invoice->amount_paid / $invoice->total_amount) * 100 : 0;
                                @endphp
                                <div class="w-full bg-slate-200 dark:bg-slate-700 rounded-full h-1.5 mt-2 overflow-hidden">
                                    <div class="bg-emerald-500 h-1.5 rounded-full transition-all duration-500" style="width: {{ $percent }}%"></div>
                                </div>
                                <div class="text-[10px] text-slate-400 mt-1">
                                    Terbayar: <span class="font-bold text-emerald-600">Rp {{ number_format($invoice->amount_paid, 0, ',', '.') }}</span>
                                </div>
                            @endif
                        </div>

                        {{-- 4. Chevron Icon --}}
                        <div class="hidden lg:flex items-center justify-center w-8 h-8 rounded-full bg-slate-50 dark:bg-slate-700 text-slate-400 transition-transform duration-300" 
                             :class="active === {{ $invoice->invoice_id }} ? 'rotate-180 bg-indigo-50 text-indigo-500' : ''">
                            <i class="material-icons">expand_more</i>
                        </div>

                    </div>
                </div>

                {{-- EXPANDED BODY --}}
                <div x-show="active === {{ $invoice->invoice_id }}" 
                     x-collapse 
                     class="bg-slate-50 dark:bg-slate-800/50 border-t border-slate-100 dark:border-slate-700">
                    
                    <div class="p-5 grid grid-cols-1 lg:grid-cols-12 gap-6">
                        
                        {{-- Tabel Rincian Item --}}
                        <div class="lg:col-span-9">
                            <div class="bg-white dark:bg-slate-800 rounded-lg border border-slate-200 dark:border-slate-700 overflow-hidden">
                                <table class="w-full text-sm text-left">
                                    <thead class="bg-slate-100 dark:bg-slate-700 text-xs text-slate-500 uppercase">
                                        <tr>
                                            <th class="px-4 py-2">Produk</th>
                                            <th class="px-4 py-2 text-center">Qty</th>
                                            <th class="px-4 py-2 text-right">Total</th>
                                        </tr>
                                    </thead>
                                    <tbody class="divide-y divide-slate-100 dark:divide-slate-700">
                                        @foreach($invoice->items as $item)
                                        <tr>
                                            <td class="px-4 py-2">
                                                <div class="font-medium text-slate-700 dark:text-slate-200">{{ $item->product->product_name }}</div>
                                            </td>
                                            <td class="px-4 py-2 text-center">{{ number_format($item->quantity, 0, ',', '.') }}</td>
                                            <td class="px-4 py-2 text-right font-medium">Rp {{ number_format($item->subtotal, 0, ',', '.') }}</td>
                                        </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                            
                            @if($invoice->notes)
                                <div class="mt-3 text-xs text-slate-500 bg-amber-50 dark:bg-amber-900/10 p-3 rounded border border-amber-100 dark:border-amber-800">
                                    <span class="font-bold text-amber-600">Catatan:</span> {{ $invoice->notes }}
                                </div>
                            @endif
                        </div>

                        {{-- Action Buttons Panel --}}
                        <div class="lg:col-span-3 flex flex-col gap-2">
                            <p class="text-xs font-bold uppercase text-slate-400 mb-1">Aksi Cepat</p>
                            
                            {{-- View Detail --}}
                            <a href="{{ route('admin.invoices.show', $invoice->invoice_id) }}" class="btn btn-sm btn-secondary w-full justify-start gap-2">
                                <i class="material-icons text-sm">visibility</i> Detail Lengkap
                            </a>

                            {{-- Actions for Draft --}}
                            @if($invoice->status == 'draft')
                                <a href="{{ route('admin.invoices.edit', $invoice->invoice_id) }}" class="btn btn-sm btn-secondary w-full justify-start gap-2">
                                    <i class="material-icons text-sm text-amber-500">edit</i> Edit
                                </a>

                                <form action="{{ route('admin.invoices.confirm', $invoice->invoice_id) }}" method="POST" onsubmit="return confirm('Konfirmasi Invoice? Stok akan berkurang.')">
                                    @csrf
                                    <button type="submit" class="btn btn-sm btn-primary w-full justify-start gap-2">
                                        <i class="material-icons text-sm">check_circle</i> Konfirmasi
                                    </button>
                                </form>

                                <form action="{{ route('admin.invoices.destroy', $invoice->invoice_id) }}" method="POST" onsubmit="return confirm('Hapus draft ini?')">
                                    @csrf @method('DELETE')
                                    <button type="submit" class="btn btn-sm btn-danger w-full justify-start gap-2 text-rose-600 bg-rose-50 border-rose-200">
                                        <i class="material-icons text-sm">delete</i> Hapus Draft
                                    </button>
                                </form>
                            @endif

                            {{-- Actions for Active Invoice --}}
                            @if(in_array($invoice->status, ['unpaid', 'partially_paid', 'overdue']))
                                <a href="{{ route('admin.invoices.pdf', $invoice->invoice_id) }}" target="_blank" class="btn btn-sm btn-secondary w-full justify-start gap-2">
                                    <i class="material-icons text-sm">print</i> Cetak PDF
                                </a>

                                <form action="{{ route('admin.invoices.cancel', $invoice->invoice_id) }}" method="POST" onsubmit="return confirm('Batalkan Invoice? Jurnal akan dibalik.')" class="mt-auto">
                                    @csrf
                                    <button type="submit" class="btn btn-sm btn-danger w-full justify-start gap-2 text-rose-600 bg-rose-50 border-rose-200">
                                        <i class="material-icons text-sm">cancel</i> Batalkan
                                    </button>
                                </form>
                            @endif

                        </div>
                    </div>
                </div>

            </div>
        @empty
            <div class="flex flex-col items-center justify-center py-16 text-center bg-white dark:bg-slate-800 rounded-xl border border-slate-200 dark:border-slate-700 border-dashed">
                <div class="w-20 h-20 bg-slate-100 dark:bg-slate-700 rounded-full flex items-center justify-center mb-4 text-slate-400">
                    <i class="material-icons text-5xl">receipt</i>
                </div>
                <h3 class="text-xl font-bold text-slate-700 dark:text-white">Tidak ada invoice ditemukan</h3>
                <p class="text-sm text-slate-500 mt-2 max-w-sm">
                    Coba sesuaikan filter pencarian atau buat invoice penjualan baru sekarang.
                </p>
                <a href="{{ route('admin.invoices.create') }}" class="btn btn-primary mt-6">
                    Buat Invoice Baru
                </a>
            </div>
        @endforelse

    </div>

    {{-- Pagination --}}
    @if($invoices->hasPages())
        <div class="mt-4">
            {{ $invoices->links('vendor.pagination.admin') }}
        </div>
    @endif

</div>
@endsection
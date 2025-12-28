@extends('admin.layouts.app')

@section('title', 'Faktur Penjualan (Invoice)')

@section('content')
    <div class="flex flex-col gap-6">
        
        {{-- Header & Tools --}}
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
            <div>
                <h2 class="page-title">Faktur Penjualan</h2>
                <p class="page-subtitle">Kelola tagihan pelanggan dan status pembayaran.</p>
            </div>
            <a href="{{ route('admin.invoices.create') }}" class="btn btn-primary">
                <i class="material-icons text-lg">post_add</i>
                Buat Invoice
            </a>
        </div>

        {{-- Filter Section --}}
        <div class="card p-4">
            <form method="GET" action="{{ route('admin.invoices.index') }}" class="grid grid-cols-1 md:grid-cols-4 gap-4">
                {{-- Search --}}
                <div class="col-span-1 md:col-span-2">
                    <label class="form-label text-xs">Cari Data</label>
                    <div class="relative">
                        <span class="absolute inset-y-0 left-0 flex items-center pl-3 text-slate-400">
                            <i class="material-icons text-lg">search</i>
                        </span>
                        <input type="text" name="search" value="{{ request('search') }}" 
                               class="form-input pl-10" 
                               placeholder="No. Invoice atau Nama Klien...">
                    </div>
                </div>

                {{-- Status Filter --}}
                <div>
                    <label class="form-label text-xs">Status Pembayaran</label>
                    <div wire:ignore>
                        <select name="status" class="tom-select w-full">
                            <option value="">Semua Status</option>
                            <option value="draft" {{ request('status') == 'draft' ? 'selected' : '' }}>Draft</option>
                            <option value="unpaid" {{ request('status') == 'unpaid' ? 'selected' : '' }}>Belum Lunas</option>
                            <option value="partially_paid" {{ request('status') == 'partially_paid' ? 'selected' : '' }}>Sebagian</option>
                            <option value="paid" {{ request('status') == 'paid' ? 'selected' : '' }}>Lunas</option>
                            <option value="overdue" {{ request('status') == 'overdue' ? 'selected' : '' }}>Jatuh Tempo</option>
                            <option value="cancelled" {{ request('status') == 'cancelled' ? 'selected' : '' }}>Dibatalkan</option>
                        </select>
                    </div>
                </div>

                {{-- Action Buttons --}}
                <div class="flex items-end gap-2">
                    <button type="submit" class="btn btn-secondary flex-1">
                        <i class="material-icons text-lg">filter_list</i> Filter
                    </button>
                    @if(request()->anyFilled(['search', 'status']))
                        <a href="{{ route('admin.invoices.index') }}" class="btn btn-danger-solid px-3" title="Reset Filter">
                            <i class="material-icons">close</i>
                        </a>
                    @endif
                </div>
            </form>
        </div>

        {{-- Invoice List (Accordion Style) --}}
        <div class="flex flex-col gap-3">
            @forelse($invoices as $invoice)
                <div class="bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-xl overflow-hidden transition-all duration-200 hover:shadow-md hover:border-indigo-200 dark:hover:border-slate-600" 
                     x-data="{ expanded: false }">
                    
                    {{-- ACCORDION HEADER --}}
                    <div @click="expanded = !expanded" class="p-4 flex flex-col sm:flex-row sm:items-center justify-between cursor-pointer group gap-4 sm:gap-0">
                        <div class="flex items-center gap-4">
                            {{-- Icon Status --}}
                            <div class="w-10 h-10 rounded-full flex-shrink-0 flex items-center justify-center text-white font-bold text-sm
                                {{ $invoice->status == 'paid' ? 'bg-emerald-500' : ($invoice->status == 'cancelled' ? 'bg-rose-500' : ($invoice->status == 'overdue' ? 'bg-rose-600' : ($invoice->status == 'draft' ? 'bg-slate-400' : 'bg-amber-500'))) }}">
                                <i class="material-icons text-lg">
                                    {{ $invoice->status == 'paid' ? 'check_circle' : ($invoice->status == 'cancelled' ? 'block' : ($invoice->status == 'draft' ? 'edit_note' : 'receipt_long')) }}
                                </i>
                            </div>

                            {{-- Info Utama --}}
                            <div>
                                <div class="flex items-center gap-2 flex-wrap">
                                    <h3 class="font-bold text-slate-700 dark:text-white text-sm sm:text-base group-hover:text-indigo-600 transition-colors font-mono">
                                        {{ $invoice->invoice_number }}
                                    </h3>
                                    
                                    {{-- BADGE STATUS --}}
                                    @php
                                        $statusClass = match($invoice->status) {
                                            'draft' => 'bg-slate-100 text-slate-600 border-slate-200',
                                            'unpaid' => 'bg-amber-100 text-amber-600 border-amber-200',
                                            'partially_paid' => 'bg-blue-100 text-blue-600 border-blue-200',
                                            'paid' => 'bg-emerald-100 text-emerald-600 border-emerald-200',
                                            'overdue' => 'bg-rose-100 text-rose-600 border-rose-200',
                                            'cancelled' => 'bg-rose-100 text-rose-600 border-rose-200',
                                            default => 'bg-slate-100 text-slate-600 border-slate-200'
                                        };
                                        
                                        // Override logic overdue (HANYA JIKA DUE DATE TIDAK NULL)
                                        if ($invoice->status != 'paid' && $invoice->status != 'cancelled' && $invoice->status != 'draft' && $invoice->due_date && $invoice->due_date < now()) {
                                            $statusClass = 'bg-rose-100 text-rose-600 border-rose-200';
                                            $label = 'Jatuh Tempo';
                                        } else {
                                            $label = match($invoice->status) {
                                                'unpaid' => 'Belum Lunas',
                                                'partially_paid' => 'Sebagian',
                                                'paid' => 'Lunas',
                                                'draft' => 'Draft',
                                                'cancelled' => 'Batal',
                                                default => ucfirst($invoice->status)
                                            };
                                        }
                                    @endphp
                                    <span class="{{ $statusClass }} border text-[10px] px-2 py-0.5 rounded-full font-bold uppercase tracking-wider">{{ $label }}</span>
                                </div>
                                <div class="flex items-center gap-2 text-xs text-slate-500 mt-1">
                                    <span class="font-medium text-slate-600 dark:text-slate-300 truncate max-w-[150px] sm:max-w-xs">{{ $invoice->client->client_name ?? 'Umum' }}</span>
                                    <span>•</span>
                                    <span>{{ $invoice->invoice_date ? $invoice->invoice_date->format('d/m/Y') : '-' }}</span>
                                </div>
                            </div>
                        </div>

                        <div class="flex items-center justify-between sm:justify-end gap-4 sm:gap-6 w-full sm:w-auto pl-14 sm:pl-0">
                            {{-- Info Tagihan (Desktop) --}}
                            <div class="hidden sm:flex flex-col items-end">
                                <span class="text-[10px] text-slate-400 uppercase font-bold">Total Tagihan</span>
                                <span class="text-sm font-bold text-slate-700 dark:text-white">Rp {{ number_format($invoice->total_amount, 0, ',', '.') }}</span>
                            </div>

                            {{-- Sisa Tagihan (Mobile Friendly) --}}
                            <div class="text-right">
                                <span class="text-[10px] text-slate-400 uppercase font-bold block sm:hidden">Sisa</span>
                                <span class="badge {{ $invoice->remaining_balance > 0 ? 'badge-warning' : 'badge-success' }}">
                                    Rp {{ number_format($invoice->remaining_balance, 0, ',', '.') }}
                                </span>
                            </div>

                            {{-- Arrow --}}
                            <div class="text-slate-400 transition-transform duration-300" :class="expanded ? 'rotate-180' : ''">
                                <i class="material-icons text-xl">expand_more</i>
                            </div>
                        </div>
                    </div>

                    {{-- ACCORDION BODY --}}
                    <div x-show="expanded" x-collapse class="bg-slate-50 dark:bg-slate-800/50 border-t border-slate-100 dark:border-slate-700">
                        
                        {{-- 1. RINCIAN ITEM (Preview) --}}
                        <div class="px-4 py-3 border-b border-slate-200/60 dark:border-slate-700/60">
                            <p class="text-xs font-bold text-slate-400 uppercase mb-2">Ringkasan Item</p>
                            <div class="space-y-2">
                                @foreach($invoice->items->take(3) as $item)
                                    <div class="flex justify-between text-sm text-slate-600 dark:text-slate-300">
                                        <span>{{ $item->product->product_name ?? 'Item Terhapus' }}</span>
                                        <div class="flex gap-4">
                                            <span class="text-slate-400">{{ number_format($item->quantity, 0, ',', '.') }} {{ $item->unit }}</span>
                                            <span class="font-medium">Rp {{ number_format($item->subtotal, 0, ',', '.') }}</span>
                                        </div>
                                    </div>
                                @endforeach
                                @if($invoice->items->count() > 3)
                                    <div class="text-xs text-slate-400 italic pt-1">+ {{ $invoice->items->count() - 3 }} item lainnya...</div>
                                @endif
                            </div>
                        </div>

                        {{-- 2. INFO KEUANGAN & BUTTONS --}}
                        <div class="px-4 py-4 flex flex-col sm:flex-row justify-between items-start sm:items-center gap-6">
                            
                            {{-- Info Ringkas --}}
                            <div class="flex flex-wrap gap-x-8 gap-y-2 text-sm w-full sm:w-auto">
                                <div>
                                    <p class="text-slate-400 text-xs font-bold uppercase mb-1">Jatuh Tempo</p>
                                    <p class="font-medium {{ $invoice->due_date && $invoice->due_date < now() && $invoice->remaining_balance > 0 ? 'text-rose-600 font-bold' : 'text-slate-700 dark:text-slate-200' }}">
                                        {{ $invoice->due_date ? $invoice->due_date->format('d M Y') : '-' }}
                                    </p>
                                </div>
                                <div>
                                    <p class="text-slate-400 text-xs font-bold uppercase mb-1">Sales</p>
                                    <p class="font-medium text-slate-700 dark:text-slate-200">
                                        {{ $invoice->sales->full_name ?? '-' }}
                                    </p>
                                </div>
                            </div>

                            {{-- Tombol Aksi --}}
                            <div class="flex flex-wrap gap-2 w-full sm:w-auto justify-end sm:justify-start border-t sm:border-t-0 border-slate-200 pt-4 sm:pt-0">
                                
                                {{-- Detail --}}
                                <a href="{{ route('admin.invoices.show', $invoice->invoice_id) }}" 
                                   class="btn btn-sm btn-secondary">
                                    <i class="material-icons text-sm">visibility</i> Detail
                                </a>

                                {{-- Edit (Hanya jika Draft) --}}
                                @if($invoice->status == 'draft')
                                    <a href="{{ route('admin.invoices.edit', $invoice->invoice_id) }}" 
                                       class="btn btn-sm btn-primary">
                                        <i class="material-icons text-sm">edit</i> Edit
                                    </a>
                                @endif

                                {{-- Konfirmasi Invoice --}}
                                @if($invoice->status == 'draft')
                                    <button type="button" onclick="confirmApprove('{{ $invoice->invoice_id }}', '{{ $invoice->invoice_number }}')" class="btn btn-sm btn-success shadow-sm shadow-emerald-200">
                                        <i class="material-icons text-sm">check_circle</i> Konfirmasi
                                    </button>
                                    <form id="approve-form-{{ $invoice->invoice_id }}" action="{{ route('admin.invoices.confirm', $invoice->invoice_id) }}" method="POST" class="hidden">@csrf</form>
                                @endif

                                {{-- Input Pembayaran (Jika Unpaid/Partial/Overdue) --}}
                                @if(in_array($invoice->status, ['unpaid', 'partially_paid', 'overdue']))
                                    <a href="{{ route('admin.invoices.show', ['invoice' => $invoice->invoice_id, 'tab' => 'payments']) }}" class="btn btn-sm btn-success text-white">
                                        <i class="material-icons text-sm">payments</i> Bayar
                                    </a>
                                @endif

                                {{-- Cancel (Hanya jika belum lunas) --}}
                                @if(in_array($invoice->status, ['unpaid', 'partially_paid', 'overdue']))
                                    {{-- REVISI: Warna Merah Solid (Rose-500) --}}
                                    <button type="button" onclick="confirmCancel('{{ $invoice->invoice_id }}', '{{ $invoice->invoice_number }}')" class="btn btn-sm bg-rose-500 hover:bg-rose-600 text-white border-transparent shadow-sm">
                                        <i class="material-icons text-sm">cancel</i> Batalkan
                                    </button>
                                    <form id="cancel-form-{{ $invoice->invoice_id }}" action="{{ route('admin.invoices.cancel', $invoice->invoice_id) }}" method="POST" class="hidden">@csrf</form>
                                @endif

                                {{-- Delete (Hanya Draft/Cancelled) --}}
                                @if(in_array($invoice->status, ['draft', 'cancelled']))
                                    <button type="button" onclick="confirmDelete('{{ $invoice->invoice_id }}', '{{ $invoice->invoice_number }}')" class="btn btn-sm btn-danger-solid" title="Hapus Permanen">
                                        <i class="material-icons text-sm">delete</i>
                                    </button>
                                    <form id="delete-form-{{ $invoice->invoice_id }}" action="{{ route('admin.invoices.destroy', $invoice->invoice_id) }}" method="POST" class="hidden">
                                        @csrf @method('DELETE')
                                    </form>
                                @endif
                            </div>
                        </div>
                    </div>
                </div>
            @empty
                <div class="card p-12 flex flex-col items-center justify-center text-slate-400 border-dashed border-2 border-slate-200">
                    <i class="material-icons text-6xl mb-4 text-slate-200">receipt_long</i>
                    <p class="text-lg font-medium text-slate-500">Belum ada faktur penjualan.</p>
                    <p class="text-sm">Silakan buat invoice baru untuk memulai.</p>
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

    {{-- SCRIPTS KONFIRMASI --}}
    @push('scripts')
    <script>
        function confirmApprove(id, number) {
            confirmDialog({
                title: 'Konfirmasi Invoice?',
                text: "Invoice #" + number + " akan diterbitkan. Stok barang akan berkurang dan piutang tercatat.",
                icon: 'question',
                confirmText: 'Ya, Terbitkan',
                confirmColor: 'success'
            }).then((result) => {
                if (result.isConfirmed) document.getElementById('approve-form-' + id).submit();
            });
        }

        function confirmCancel(id, number) {
            confirmDialog({
                title: 'Batalkan Invoice?',
                text: "Invoice #" + number + " akan dibatalkan. Stok akan dikembalikan (jika sudah dikurangi).",
                icon: 'warning',
                confirmText: 'Ya, Batalkan',
                confirmColor: 'warning'
            }).then((result) => {
                if (result.isConfirmed) document.getElementById('cancel-form-' + id).submit();
            });
        }

        function confirmDelete(id, number) {
            confirmDialog({
                title: 'Hapus Permanen?',
                text: "Data Invoice #" + number + " akan dihapus selamanya dari database.",
                icon: 'warning',
                confirmText: 'Ya, Hapus',
                confirmColor: 'danger'
            }).then((result) => {
                if (result.isConfirmed) document.getElementById('delete-form-' + id).submit();
            });
        }
    </script>
    @endpush
@endsection
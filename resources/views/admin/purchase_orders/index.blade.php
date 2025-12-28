@extends('admin.layouts.app')

@section('title', 'Pesanan Pembelian (PO)')

@section('content')
    <div class="flex flex-col gap-6">
        
        {{-- Header & Tools --}}
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
            <div>
                <h2 class="page-title">Pesanan Pembelian</h2>
                <p class="page-subtitle">Kelola pengadaan stok, status order, dan hutang supplier.</p>
            </div>
            <a href="{{ route('admin.purchase-orders.create') }}" class="btn btn-primary">
                <i class="material-icons text-lg">add</i>
                Buat PO Baru
            </a>
        </div>

        {{-- Filter Section --}}
        <div class="card p-4">
            <form method="GET" action="{{ route('admin.purchase-orders.index') }}" class="grid grid-cols-1 md:grid-cols-4 gap-4">
                {{-- Search --}}
                <div class="col-span-1 md:col-span-2">
                    <label class="form-label text-xs">Cari Data</label>
                    <div class="relative">
                        <span class="absolute inset-y-0 left-0 flex items-center pl-3 text-slate-400">
                            <i class="material-icons text-lg">search</i>
                        </span>
                        <input type="text" name="search" value="{{ request('search') }}" 
                               class="form-input pl-10" 
                               placeholder="No. PO, Invoice Supplier, atau Nama Supplier...">
                    </div>
                </div>

                {{-- Status Filter --}}
                <div>
                    <label class="form-label text-xs">Status Pembayaran</label>
                    <div wire:ignore>
                        <select name="payment_status" class="tom-select w-full">
                            <option value="">Semua Status</option>
                            <option value="unpaid" {{ request('payment_status') == 'unpaid' ? 'selected' : '' }}>Belum Lunas</option>
                            <option value="partially_paid" {{ request('payment_status') == 'partially_paid' ? 'selected' : '' }}>Sebagian</option>
                            <option value="paid" {{ request('payment_status') == 'paid' ? 'selected' : '' }}>Lunas</option>
                        </select>
                    </div>
                </div>

                {{-- Action Buttons --}}
                <div class="flex items-end gap-2">
                    <button type="submit" class="btn btn-secondary flex-1">
                        <i class="material-icons text-lg">filter_list</i>
                        Filter
                    </button>
                    @if(request()->anyFilled(['search', 'payment_status']))
                        <a href="{{ route('admin.purchase-orders.index') }}" class="btn btn-danger-solid px-3" title="Reset Filter">
                            <i class="material-icons">close</i>
                        </a>
                    @endif
                </div>
            </form>
        </div>

        {{-- PO List (Accordion Style) --}}
        <div class="flex flex-col gap-3">
            @forelse($purchaseOrders as $po)
                <div class="bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-xl overflow-hidden transition-all duration-200 hover:shadow-md hover:border-indigo-200 dark:hover:border-slate-600" 
                     x-data="{ expanded: false }">
                    
                    {{-- ACCORDION HEADER --}}
                    <div @click="expanded = !expanded" class="p-4 flex flex-col sm:flex-row sm:items-center justify-between cursor-pointer group gap-4 sm:gap-0">
                        <div class="flex items-center gap-4">
                            {{-- Icon Status --}}
                            <div class="w-10 h-10 rounded-full flex-shrink-0 flex items-center justify-center text-white font-bold text-sm
                                {{ $po->status == 'completed' ? 'bg-emerald-500' : ($po->status == 'cancelled' ? 'bg-rose-500' : ($po->status == 'ordered' ? 'bg-blue-500' : 'bg-slate-400')) }}">
                                <i class="material-icons text-lg">
                                    {{ $po->status == 'completed' ? 'inventory' : ($po->status == 'cancelled' ? 'block' : ($po->status == 'ordered' ? 'local_shipping' : 'edit_note')) }}
                                </i>
                            </div>

                            {{-- Info Utama --}}
                            <div>
                                <div class="flex items-center gap-2 flex-wrap">
                                    <h3 class="font-bold text-slate-700 dark:text-white text-sm sm:text-base group-hover:text-indigo-600 transition-colors">
                                        {{ $po->po_number }}
                                    </h3>
                                    
                                    {{-- BADGE STATUS ORDER --}}
                                    @if($po->status == 'draft')
                                        <span class="bg-slate-100 text-slate-600 border border-slate-200 text-[10px] px-2 py-0.5 rounded-full font-bold uppercase tracking-wider">Draft</span>
                                    @elseif($po->status == 'ordered')
                                        <span class="bg-blue-100 text-blue-600 border border-blue-200 text-[10px] px-2 py-0.5 rounded-full font-bold uppercase tracking-wider">Dipesan</span>
                                    @elseif($po->status == 'completed')
                                        <span class="bg-emerald-100 text-emerald-600 border border-emerald-200 text-[10px] px-2 py-0.5 rounded-full font-bold uppercase tracking-wider">Selesai</span>
                                    @else
                                        <span class="bg-rose-100 text-rose-600 border border-rose-200 text-[10px] px-2 py-0.5 rounded-full font-bold uppercase tracking-wider">Batal</span>
                                    @endif
                                </div>
                                <div class="flex items-center gap-2 text-xs text-slate-500 mt-1">
                                    <span class="font-medium text-slate-600 dark:text-slate-300 truncate max-w-[150px] sm:max-w-xs">{{ $po->supplier->supplier_name }}</span>
                                    <span>•</span>
                                    <span>{{ $po->order_date->format('d M Y') }}</span>
                                </div>
                            </div>
                        </div>

                        <div class="flex items-center justify-between sm:justify-end gap-4 sm:gap-6 w-full sm:w-auto pl-14 sm:pl-0">
                            {{-- Info Tagihan (Desktop) --}}
                            <div class="hidden sm:flex flex-col items-end">
                                <span class="text-[10px] text-slate-400 uppercase font-bold">Total Tagihan</span>
                                <span class="text-sm font-bold text-slate-700 dark:text-white">Rp {{ number_format($po->grand_total, 0, ',', '.') }}</span>
                            </div>

                            {{-- Payment Badge --}}
                            <div>
                                @if($po->payment_status == 'paid')
                                    <span class="badge badge-success">Lunas</span>
                                @elseif($po->payment_status == 'partially_paid')
                                    <span class="badge badge-warning">Sebagian</span>
                                @else
                                    <span class="badge badge-danger">Belum Lunas</span>
                                @endif
                            </div>

                            {{-- Arrow --}}
                            <div class="text-slate-400 transition-transform duration-300" :class="expanded ? 'rotate-180' : ''">
                                <i class="material-icons text-xl">expand_more</i>
                            </div>
                        </div>
                    </div>

                    {{-- ACCORDION BODY --}}
                    <div x-show="expanded" x-collapse class="bg-slate-50 dark:bg-slate-800/50 border-t border-slate-100 dark:border-slate-700">
                        
                        {{-- 1. RINCIAN ITEM (Preview 3 Item Pertama) --}}
                        <div class="px-4 py-3 border-b border-slate-200/60 dark:border-slate-700/60">
                            <p class="text-xs font-bold text-slate-400 uppercase mb-2">Ringkasan Item</p>
                            <div class="space-y-2">
                                @foreach($po->items->take(3) as $item)
                                    <div class="flex justify-between text-sm text-slate-600 dark:text-slate-300">
                                        <span>{{ $item->product->product_name }}</span>
                                        <div class="flex gap-4">
                                            <span class="text-slate-400">{{ number_format($item->quantity, 0, ',', '.') }} {{ $item->product->unit->name ?? 'Unit' }}</span>
                                            <span class="font-medium">Rp {{ number_format($item->subtotal, 0, ',', '.') }}</span>
                                        </div>
                                    </div>
                                @endforeach
                                @if($po->items->count() > 3)
                                    <div class="text-xs text-slate-400 italic pt-1">+ {{ $po->items->count() - 3 }} item lainnya...</div>
                                @endif
                            </div>
                        </div>

                        {{-- 2. INFO KEUANGAN & BUTTONS --}}
                        <div class="px-4 py-4 flex flex-col sm:flex-row justify-between items-start sm:items-center gap-6">
                            
                            {{-- Info Ringkas --}}
                            <div class="flex flex-wrap gap-x-8 gap-y-2 text-sm w-full sm:w-auto">
                                <div>
                                    <p class="text-slate-400 text-xs font-bold uppercase mb-1">Sisa Hutang</p>
                                    <p class="font-bold {{ $po->remaining_balance > 0 ? 'text-rose-600' : 'text-emerald-600' }} text-lg">
                                        Rp {{ number_format($po->remaining_balance, 0, ',', '.') }}
                                    </p>
                                </div>
                                <div>
                                    <p class="text-slate-400 text-xs font-bold uppercase mb-1">Jatuh Tempo</p>
                                    <p class="font-medium text-slate-700 dark:text-slate-200">
                                        {{ $po->due_date ? $po->due_date->format('d M Y') : '-' }}
                                    </p>
                                </div>
                                <div>
                                    <p class="text-slate-400 text-xs font-bold uppercase mb-1">Dibuat Oleh</p>
                                    <p class="font-medium text-slate-700 dark:text-slate-200">
                                        {{ $po->requester->full_name ?? 'Admin' }}
                                    </p>
                                </div>
                            </div>

                            {{-- Tombol Aksi --}}
                            <div class="flex flex-wrap gap-2 w-full sm:w-auto justify-end sm:justify-start border-t sm:border-t-0 border-slate-200 pt-4 sm:pt-0">
                                
                                <a href="{{ route('admin.purchase-orders.show', $po->po_id) }}" 
                                   class="btn btn-sm btn-secondary">
                                    <i class="material-icons text-sm">visibility</i> Detail
                                </a>

                                @if(in_array($po->status, ['draft', 'ordered']))
                                    <a href="{{ route('admin.purchase-orders.edit', $po->po_id) }}" 
                                       class="btn btn-sm btn-primary">
                                        <i class="material-icons text-sm">edit</i> Edit
                                    </a>
                                @endif

                                {{-- UPDATE: Button Terima Barang (Draft & Ordered) --}}
                                @if(in_array($po->status, ['draft', 'ordered']))
                                    <button type="button" onclick="confirmReceiveIndex('{{ $po->po_id }}')" class="btn btn-sm btn-success shadow-sm shadow-emerald-200">
                                        <i class="material-icons text-sm">check_circle</i> Terima Barang
                                    </button>
                                    <form id="receive-form-{{ $po->po_id }}" action="{{ route('admin.purchase-orders.receive', $po->po_id) }}" method="POST" class="hidden">@csrf</form>
                                @endif

                                {{-- Button Cancel (Ordered Only) --}}
                                @if($po->status == 'ordered')
                                    <button type="button" onclick="confirmCancelIndex('{{ $po->po_id }}')" class="btn btn-sm btn-warning text-white">
                                        <i class="material-icons text-sm">cancel</i> Batalkan
                                    </button>
                                    <form id="cancel-form-{{ $po->po_id }}" action="{{ route('admin.purchase-orders.cancel', $po->po_id) }}" method="POST" class="hidden">@csrf</form>
                                @endif

                                {{-- Button Delete (Draft or Cancelled Only) --}}
                                @if(in_array($po->status, ['draft', 'cancelled']))
                                    <button type="button" onclick="confirmDeleteIndex('{{ $po->po_id }}')" class="btn btn-sm btn-danger-solid" title="Hapus Permanen">
                                        <i class="material-icons text-sm">delete</i>
                                    </button>
                                    <form id="delete-form-{{ $po->po_id }}" action="{{ route('admin.purchase-orders.destroy', $po->po_id) }}" method="POST" class="hidden">
                                        @csrf @method('DELETE')
                                    </form>
                                @endif
                            </div>
                        </div>
                    </div>
                </div>
            @empty
                <div class="card p-12 flex flex-col items-center justify-center text-slate-400 border-dashed border-2 border-slate-200">
                    <i class="material-icons text-6xl mb-4 text-slate-200">shopping_cart_off</i>
                    <p class="text-lg font-medium text-slate-500">Belum ada pesanan pembelian.</p>
                    <p class="text-sm">Silakan buat PO baru untuk memulai.</p>
                </div>
            @endforelse
        </div>

        {{-- Pagination --}}
        @if($purchaseOrders->hasPages())
            <div class="mt-4">
                {{ $purchaseOrders->links('vendor.pagination.admin') }}
            </div>
        @endif
    </div>

    {{-- SCRIPTS KONFIRMASI --}}
    @push('scripts')
    <script>
        function confirmReceiveIndex(id) {
            confirmDialog({
                title: 'Konfirmasi Terima Barang?',
                text: 'Stok barang akan bertambah dan jurnal hutang akan dicatat otomatis. Pastikan barang fisik sudah diterima.',
                icon: 'info',
                confirmText: 'Ya, Terima Barang',
                confirmColor: 'success'
            }).then((result) => {
                if (result.isConfirmed) document.getElementById('receive-form-' + id).submit();
            });
        }

        function confirmCancelIndex(id) {
            confirmDialog({
                title: 'Batalkan Pesanan?',
                text: 'Pesanan akan dibatalkan. Aksi ini tidak dapat dikembalikan.',
                icon: 'warning',
                confirmText: 'Ya, Batalkan',
                confirmColor: 'warning'
            }).then((result) => {
                if (result.isConfirmed) document.getElementById('cancel-form-' + id).submit();
            });
        }

        function confirmDeleteIndex(id) {
            confirmDialog({
                title: 'Hapus Permanen?',
                text: 'Data PO ini akan dihapus permanen dari sistem.',
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
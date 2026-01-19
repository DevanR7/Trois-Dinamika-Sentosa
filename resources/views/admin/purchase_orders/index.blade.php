@extends('admin.layouts.app')

@section('title', 'Daftar Purchase Order')

@section('content')
<div class="flex flex-col gap-6">

    {{-- 1. HEADER & ACTIONS --}}
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div>
            <h1 class="page-title">Purchase Order</h1>
            <p class="page-subtitle">
                Kelola pesanan pembelian ke supplier. Pantau status barang dan pembayaran.
            </p>
        </div>
        <div class="flex gap-3">
            <button type="button" class="btn btn-secondary hidden sm:inline-flex" onclick="window.showToast('Fitur export sedang dalam pengembangan', 'info')">
                <i class="material-icons text-[18px] mr-2">download</i> Export
            </button>
            
            <a href="{{ route('admin.purchase-orders.create') }}" class="btn btn-primary shadow-lg shadow-indigo-500/20">
                <i class="material-icons text-[18px] mr-2">add_shopping_cart</i> Buat PO Baru
            </a>
        </div>
    </div>

    {{-- 2. STATS OVERVIEW --}}
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
        {{-- Total PO --}}
        <div class="stat-card bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-xl shadow-sm hover:shadow-md transition-all p-5 flex flex-row items-center justify-start gap-4">
            <div class="stat-icon bg-indigo-50 text-indigo-600 dark:bg-indigo-900/30 dark:text-indigo-400">
                <i class="material-icons">shopping_cart</i>
            </div>
            <div class="ml-4">
                <div class="stat-label">Total PO (Bulan Ini)</div>
                <div class="stat-value">
                    {{ \App\Models\PurchaseOrder::whereMonth('order_date', now()->month)->whereYear('order_date', now()->year)->count() }}
                </div>
            </div>
        </div>

        {{-- Menunggu Barang --}}
        <div class="stat-card bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-xl shadow-sm hover:shadow-md transition-all p-5 flex flex-row items-center justify-start gap-4">
            <div class="stat-icon bg-amber-50 text-amber-600 dark:bg-amber-900/30 dark:text-amber-400">
                <i class="material-icons">local_shipping</i>
            </div>
            <div class="ml-4">
                <div class="stat-label">Menunggu Barang</div>
                <div class="stat-value">
                    {{ \App\Models\PurchaseOrder::where('status', 'ordered')->count() }}
                </div>
            </div>
        </div>

        {{-- Hutang Belum Lunas --}}
        <div class="stat-card bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-xl shadow-sm hover:shadow-md transition-all p-5 flex flex-row items-center justify-start gap-4">
            <div class="stat-icon bg-rose-50 text-rose-600 dark:bg-rose-900/30 dark:text-rose-400">
                <i class="material-icons">payments</i>
            </div>
            <div class="ml-4">
                <div class="stat-label">Hutang (Unpaid)</div>
                <div class="stat-value">
                    {{ \App\Models\PurchaseOrder::whereIn('payment_status', ['unpaid', 'partially_paid'])->where('status', '!=', 'cancelled')->count() }}
                </div>
            </div>
        </div>

        {{-- Jatuh Tempo --}}
        <div class="stat-card bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-xl shadow-sm hover:shadow-md transition-all p-5 flex flex-row items-center justify-start gap-4">
            <div class="stat-icon bg-slate-100 text-slate-600 dark:bg-slate-700 dark:text-slate-300">
                <i class="material-icons">event_busy</i>
            </div>
            <div class="ml-4">
                <div class="stat-label">Jatuh Tempo</div>
                <div class="stat-value">
                    {{ \App\Models\PurchaseOrder::where('due_date', '<', now())->where('payment_status', '!=', 'paid')->where('status', '!=', 'cancelled')->count() }}
                </div>
            </div>
        </div>
    </div>

    {{-- 3. FILTER SECTION --}}
    <div class="card p-5 border border-slate-200 dark:border-slate-700" x-data="{ expanded: false }">
        <div class="flex flex-col lg:flex-row justify-between lg:items-center gap-4">
            <h3 class="text-sm font-bold text-slate-700 dark:text-white flex items-center gap-2">
                <i class="material-icons text-indigo-500">filter_list</i> Filter Data
            </h3>
            
            <div class="flex gap-2 lg:hidden">
                <button @click="expanded = !expanded" class="text-xs text-indigo-600 font-bold hover:underline flex items-center">
                    <i class="material-icons text-[16px] mr-1" x-text="expanded ? 'expand_less' : 'expand_more'"></i>
                    <span x-text="expanded ? 'Tutup Filter' : 'Buka Filter'"></span>
                </button>
            </div>
        </div>
        
        <form action="{{ route('admin.purchase-orders.index') }}" method="GET" 
              class="mt-4 grid grid-cols-1 md:grid-cols-2 lg:grid-cols-5 gap-4" 
              :class="expanded ? 'block' : 'hidden lg:grid'">
            
            <div class="lg:col-span-1">
                <label class="form-label text-[10px]">Cari No PO / Invoice</label>
                <div class="relative">
                    <span class="absolute left-3 top-2.5 text-slate-400"><i class="material-icons text-[16px]">search</i></span>
                    <input type="text" name="search" value="{{ request('search') }}" class="form-input pl-9 text-xs" placeholder="Cari nomor...">
                </div>
            </div>

            <div class="lg:col-span-1">
                <label class="form-label text-[10px]">Supplier</label>
                <select name="supplier_id" class="tom-select-filter">
                    <option value="">Semua Supplier</option>
                    @foreach($suppliers as $supplier)
                        <option value="{{ $supplier->supplier_id }}" {{ request('supplier_id') == $supplier->supplier_id ? 'selected' : '' }}>
                            {{ $supplier->supplier_name }}
                        </option>
                    @endforeach
                </select>
            </div>

            <div>
                <label class="form-label text-[10px]">Status Pesanan</label>
                <select name="status" class="tom-select-filter">
                    <option value="">Semua Status</option>
                    <option value="draft" {{ request('status') == 'draft' ? 'selected' : '' }}>Draft</option>
                    <option value="ordered" {{ request('status') == 'ordered' ? 'selected' : '' }}>Ordered (Dipesan)</option>
                    <option value="completed" {{ request('status') == 'completed' ? 'selected' : '' }}>Completed (Diterima)</option>
                    <option value="cancelled" {{ request('status') == 'cancelled' ? 'selected' : '' }}>Cancelled</option>
                </select>
            </div>

            <div>
                <label class="form-label text-[10px]">Status Pembayaran</label>
                <select name="payment_status" class="tom-select-filter">
                    <option value="">Semua Pembayaran</option>
                    <option value="paid" {{ request('payment_status') == 'paid' ? 'selected' : '' }}>Lunas</option>
                    <option value="partially_paid" {{ request('payment_status') == 'partially_paid' ? 'selected' : '' }}>Parsial</option>
                    <option value="unpaid" {{ request('payment_status') == 'unpaid' ? 'selected' : '' }}>Belum Lunas</option>
                </select>
            </div>

            <div class="flex items-end gap-2">
                <button type="submit" class="btn btn-primary w-full shadow-md h-[42px]">
                    <i class="material-icons text-[16px] mr-1">search</i> Cari
                </button>
                <a href="{{ route('admin.purchase-orders.index') }}" class="btn btn-secondary h-[42px]" title="Reset Filter">
                    <i class="material-icons text-[16px]">refresh</i>
                </a>
            </div>
        </form>
    </div>

    {{-- 4. TABLE SECTION --}}
    <div class="card overflow-hidden border border-slate-200 dark:border-slate-700" x-data="purchaseOrderIndex()">
        <div class="table-container border-0">
            <table class="table-modern">
                <thead>
                    <tr>
                        <th class="w-10 text-center"></th>
                        <th>No. PO & Supplier</th>
                        <th class="text-center">Tanggal</th>
                        <th class="text-right">Total Tagihan</th>
                        <th class="text-center">Status Barang</th>
                        <th class="w-40">Status Pembayaran</th>
                        <th class="text-center w-36">Aksi</th>
                    </tr>
                </thead>
                
                @forelse($purchaseOrders as $po)
                <tbody class="border-b border-slate-100 dark:border-slate-700 group" x-data="{ expanded: false }">
                    
                    {{-- BARIS UTAMA --}}
                    <tr class="hover:bg-indigo-50/30 dark:hover:bg-slate-800/30 transition-colors cursor-pointer" @click="expanded = !expanded">
                        
                        {{-- Icon Accordion --}}
                        <td class="text-center">
                            <i class="material-icons text-slate-400 transition-transform duration-300" :class="expanded ? 'rotate-90 text-indigo-500' : ''">chevron_right</i>
                        </td>

                        {{-- Info PO & Supplier --}}
                        <td class="align-top">
                            <div class="flex flex-col">
                                <div class="flex items-center gap-2">
                                    <span class="font-bold text-indigo-600 dark:text-indigo-400 text-sm">
                                        {{ $po->po_number }}
                                    </span>
                                    @if($po->total_returned > 0)
                                        <span class="text-[10px] bg-rose-100 text-rose-600 px-1.5 rounded border border-rose-200 font-bold" title="Ada Retur Potong Tagihan">RETUR</span>
                                    @endif
                                </div>
                                @if($po->supplier_invoice_number)
                                    <div class="text-[10px] text-slate-400 mt-0.5 flex items-center gap-1 font-mono">
                                        <i class="material-icons text-[10px]">receipt</i> {{ $po->supplier_invoice_number }}
                                    </div>
                                @endif
                                <div class="mt-1 text-xs font-bold text-slate-700 dark:text-slate-200 uppercase tracking-wide">
                                    {{ $po->supplier->supplier_name }}
                                </div>
                            </div>
                        </td>

                        {{-- Tanggal --}}
                        <td class="align-top text-center">
                            <div class="text-[10px] text-slate-400 uppercase font-bold">Order</div>
                            <div class="font-medium text-slate-600 dark:text-slate-300">{{ $po->order_date->format('d/m/Y') }}</div>
                            
                            @if($po->due_date)
                                <div class="mt-2 text-[10px] text-slate-400 uppercase font-bold">Jatuh Tempo</div>
                                <div class="font-medium {{ $po->due_date < now() && $po->payment_status != 'paid' && $po->status != 'cancelled' ? 'text-rose-500 font-bold flex items-center justify-center gap-1' : 'text-slate-600 dark:text-slate-300' }}">
                                    @if($po->due_date < now() && $po->payment_status != 'paid' && $po->status != 'cancelled')
                                        <i class="material-icons text-[12px]">warning</i>
                                    @endif
                                    {{ $po->due_date->format('d/m/Y') }}
                                </div>
                            @endif
                        </td>

                        {{-- Total --}}
                        <td class="align-top text-right">
                            <div class="font-mono font-bold text-slate-700 dark:text-white text-sm">
                                Rp {{ number_format($po->grand_total, 0, ',', '.') }}
                            </div>
                            
                            {{-- LOGIKA SISA TAGIHAN DINAMIS (Memperhitungkan Adjustment) --}}
                            @php
                                // Hitung Kewajiban Nyata
                                $adjDebit = $po->adjustments->where('type', 'debit_note')->sum('amount');
                                $adjCredit = $po->adjustments->where('type', 'credit_note')->sum('amount');
                                $netTotal = ($po->grand_total + $adjDebit) - ($po->total_returned + $adjCredit);
                                $realRemaining = max(0, $netTotal - $po->amount_paid);
                            @endphp

                            @if($realRemaining <= 0.01)
                                {{-- LUNAS --}}
                                @if($po->total_returned > 0)
                                    <div class="text-[10px] text-rose-500 mt-1 font-medium bg-rose-50 dark:bg-rose-900/20 px-2 py-0.5 rounded inline-block border border-rose-100">
                                        Retur: Rp {{ number_format($po->total_returned, 0, ',', '.') }}
                                    </div>
                                @endif
                            @else
                                {{-- BELUM LUNAS (Bisa karena Tagihan Awal ATAU Tagihan Susulan) --}}
                                <div class="text-[10px] text-rose-500 mt-1 font-medium bg-rose-50 dark:bg-rose-900/20 px-2 py-0.5 rounded inline-block">
                                    Sisa: Rp {{ number_format($realRemaining, 0, ',', '.') }}
                                </div>
                            @endif
                        </td>

                        {{-- Status Barang --}}
                        <td class="align-top text-center">
                            @php
                                $statusClass = match($po->status) {
                                    'draft' => 'bg-slate-100 text-slate-600 border border-slate-200',
                                    'ordered' => 'bg-blue-100 text-blue-700 border border-blue-200',
                                    'completed' => 'bg-emerald-100 text-emerald-700 border border-emerald-200',
                                    'cancelled' => 'bg-rose-100 text-rose-700 border border-rose-200',
                                    default => 'bg-slate-100 text-slate-600'
                                };
                            @endphp
                            <span class="px-2.5 py-1 rounded-full text-[10px] font-bold uppercase {{ $statusClass }}">
                                {{ match($po->status) { 'ordered' => 'Dipesan', 'completed' => 'Diterima', 'cancelled' => 'Batal', default => ucfirst($po->status) } }}
                            </span>
                        </td>

                        {{-- Status Pembayaran (REVISI LOGIKA UTAMA) --}}
                        <td class="align-top">
                            @php
                                // 1. Hitung Adjustment (Debit/Credit Note Manual)
                                $adjDebit = $po->adjustments->where('type', 'debit_note')->sum('amount');
                                $adjCredit = $po->adjustments->where('type', 'credit_note')->sum('amount');

                                // 2. Hitung Total Kewajiban Bersih (Net Payable)
                                // Rumus: (Grand Total + Debit Note) - (Retur + Credit Note)
                                $netObligation = ($po->grand_total + $adjDebit) - ($po->total_returned + $adjCredit);
                                
                                // 3. Hitung Persentase Real
                                if ($netObligation > 0) {
                                    $percent = ($po->amount_paid / $netObligation) * 100;
                                } else {
                                    // Jika kewajiban 0 atau minus (sangat jarang), anggap lunas jika sudah bayar atau 0
                                    $percent = ($po->grand_total == 0) ? 100 : 100;
                                }
                                
                                $percent = min(100, max(0, $percent));
                                
                                // 4. Tentukan Label & Warna secara Dinamis
                                if ($percent >= 99.9) {
                                    $label = 'Lunas';
                                    $barColor = 'bg-emerald-500';
                                    $textColor = 'text-emerald-600';
                                } elseif ($percent > 0) {
                                    // Jika ada Debit Note baru, persen turun < 100, jadi otomatis 'Parsial'
                                    $label = 'Parsial'; 
                                    $barColor = 'bg-amber-400';
                                    $textColor = 'text-amber-600';
                                } else {
                                    $label = 'Belum Lunas';
                                    $barColor = 'bg-slate-200';
                                    $textColor = 'text-slate-400';
                                }
                            @endphp
                            
                            <div class="w-full">
                                <div class="flex justify-between text-[10px] font-bold mb-1 uppercase {{ $textColor }}">
                                    <span>{{ $label }}</span>
                                    <span>{{ round($percent) }}%</span>
                                </div>
                                <div class="w-full bg-slate-100 dark:bg-slate-700 rounded-full h-1.5 overflow-hidden">
                                    <div class="{{ $barColor }} h-1.5 rounded-full transition-all duration-500" style="width: {{ $percent }}%"></div>
                                </div>
                                
                                {{-- Indikator Tambahan Tagihan --}}
                                @if($adjDebit > 0 && $percent < 100)
                                    <div class="text-[9px] text-amber-600 mt-1 italic flex items-center gap-1">
                                        <i class="material-icons text-[10px]">info</i> Ada tagihan susulan
                                    </div>
                                @endif
                            </div>
                        </td>

                        {{-- AKSI COMPACT (ICON ONLY) --}}
                        <td class="align-top text-center" @click.stop> 
                            <div class="flex items-center justify-center gap-1 flex-wrap">
                                
                                {{-- 1. View --}}
                                <a href="{{ route('admin.purchase-orders.show', $po->po_id) }}" 
                                   class="w-8 h-8 rounded-lg border border-slate-200 text-slate-500 hover:bg-slate-100 flex items-center justify-center transition-colors shadow-sm" 
                                   title="Lihat Detail">
                                    <i class="material-icons text-[16px]">visibility</i>
                                </a>

                                {{-- 2. Workflow Actions --}}
                                @if($po->status === 'draft')
                                    <form action="{{ route('admin.purchase-orders.mark-ordered', $po->po_id) }}" method="POST" id="form-process-{{ $po->po_id }}" class="inline-block m-0 p-0">
                                        @csrf @method('PATCH')
                                        <button type="button" @click="confirmProcess('form-process-{{ $po->po_id }}')" 
                                                class="w-8 h-8 rounded-lg bg-indigo-50 text-indigo-600 border border-indigo-200 hover:bg-indigo-100 flex items-center justify-center transition-colors shadow-sm" 
                                                title="Proses Pesanan">
                                            <i class="material-icons text-[16px]">send</i>
                                        </button>
                                    </form>
                                @endif

                                @if($po->status === 'ordered')
                                    <form action="{{ route('admin.purchase-orders.receive', $po->po_id) }}" method="POST" id="form-receive-{{ $po->po_id }}" class="inline-block m-0 p-0">
                                        @csrf
                                        <button type="button" @click="confirmReceive('form-receive-{{ $po->po_id }}')" 
                                                class="w-8 h-8 rounded-lg bg-emerald-50 text-emerald-600 border border-emerald-200 hover:bg-emerald-100 flex items-center justify-center transition-colors shadow-sm" 
                                                title="Terima Barang">
                                            <i class="material-icons text-[16px]">inventory_2</i>
                                        </button>
                                    </form>
                                @endif

                                @if($po->status === 'completed')
                                    @php
                                        $hasReturnableItems = $po->items->contains(function($item) {
                                            return round($item->quantity - $item->quantity_returned, 2) > 0.001;
                                        });
                                    @endphp
                                    @if($hasReturnableItems)
                                        <a href="{{ route('admin.purchase-returns.create', ['purchase_order_id' => $po->po_id]) }}" 
                                           class="w-8 h-8 rounded-lg bg-rose-50 text-rose-600 border border-rose-200 hover:bg-rose-100 flex items-center justify-center transition-colors shadow-sm" 
                                           title="Retur Barang">
                                            <i class="material-icons text-[16px]">assignment_return</i>
                                        </a>
                                    @endif
                                @endif

                                {{-- 3. Edit --}}
                                @if(in_array($po->status, ['draft', 'ordered']))
                                    <a href="{{ route('admin.purchase-orders.edit', $po->po_id) }}" 
                                       class="w-8 h-8 rounded-lg border border-amber-200 text-amber-600 hover:bg-amber-50 flex items-center justify-center transition-colors shadow-sm" 
                                       title="Edit">
                                        <i class="material-icons text-[16px]">edit</i>
                                    </a>
                                @endif

                                {{-- 4. Print --}}
                                <a href="{{ route('admin.purchase-orders.pdf', $po->po_id) }}" target="_blank" 
                                   class="w-8 h-8 rounded-lg border border-slate-200 text-slate-500 hover:text-indigo-600 hover:bg-indigo-50 flex items-center justify-center transition-colors shadow-sm" 
                                   title="Cetak PDF">
                                    <i class="material-icons text-[16px]">print</i>
                                </a>

                                {{-- 5. Delete --}}
                                @if($po->status === 'draft')
                                    <form action="{{ route('admin.purchase-orders.destroy', $po->po_id) }}" method="POST" class="inline-block m-0 p-0" id="form-delete-{{ $po->po_id }}">
                                        @csrf @method('DELETE')
                                        <button type="button" @click="confirmDelete('form-delete-{{ $po->po_id }}')" 
                                                class="w-8 h-8 rounded-lg border border-rose-200 text-rose-600 hover:bg-rose-50 flex items-center justify-center transition-colors shadow-sm" 
                                                title="Hapus Draft">
                                            <i class="material-icons text-[16px]">delete</i>
                                        </button>
                                    </form>
                                @endif
                            </div>
                        </td>
                    </tr>

                    {{-- DETAIL ITEM (ACCORDION) --}}
                    <tr>
                        <td colspan="7" class="p-0 border-0">
                            <div x-show="expanded" x-collapse class="bg-slate-50 dark:bg-slate-800/50 p-4 border-t border-dashed border-slate-200 dark:border-slate-700">
                                <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                                    <div class="lg:col-span-2">
                                        <h4 class="text-xs font-bold text-slate-500 uppercase mb-2">Rincian Barang</h4>
                                        <div class="overflow-x-auto rounded-lg border border-slate-200 dark:border-slate-600 bg-white dark:bg-slate-800">
                                            <table class="w-full text-xs text-left">
                                                <thead class="bg-slate-100 dark:bg-slate-700 text-slate-500 font-semibold">
                                                    <tr>
                                                        <th class="px-3 py-2">Produk</th>
                                                        <th class="px-3 py-2 text-right">Harga</th>
                                                        <th class="px-3 py-2 text-center">Qty</th>
                                                        <th class="px-3 py-2 text-center">Retur</th>
                                                        <th class="px-3 py-2 text-right">Subtotal</th>
                                                    </tr>
                                                </thead>
                                                <tbody class="divide-y divide-slate-100 dark:divide-slate-600">
                                                    @foreach($po->items as $item)
                                                        <tr>
                                                            <td class="px-3 py-2 font-medium">
                                                                {{ $item->product->product_name ?? 'Produk Dihapus' }} 
                                                                <span class="text-slate-400 font-normal">({{ $item->product->product_code ?? '-' }})</span>
                                                            </td>
                                                            <td class="px-3 py-2 text-right font-mono">{{ number_format($item->price_per_unit, 0, ',', '.') }}</td>
                                                            <td class="px-3 py-2 text-center font-bold">
                                                                {{ (float)$item->quantity }} <span class="font-normal text-slate-400">{{ $item->product->unit->name ?? '' }}</span>
                                                            </td>
                                                            <td class="px-3 py-2 text-center">
                                                                @if($item->quantity_returned > 0)
                                                                    <span class="text-rose-500 font-bold">-{{ (float)$item->quantity_returned }}</span>
                                                                @else
                                                                    <span class="text-slate-300">-</span>
                                                                @endif
                                                            </td>
                                                            <td class="px-3 py-2 text-right font-mono font-bold">{{ number_format($item->subtotal, 0, ',', '.') }}</td>
                                                        </tr>
                                                    @endforeach
                                                </tbody>
                                            </table>
                                        </div>
                                    </div>
                                    <div class="lg:col-span-1">
                                        <h4 class="text-xs font-bold text-slate-500 uppercase mb-2">Ringkasan Biaya</h4>
                                        <div class="bg-white dark:bg-slate-800 rounded-lg border border-slate-200 dark:border-slate-600 p-3 text-xs space-y-1">
                                            <div class="flex justify-between">
                                                <span class="text-slate-500">Subtotal Item</span>
                                                <span class="font-mono">{{ number_format($po->subtotal, 0, ',', '.') }}</span>
                                            </div>
                                            @if($po->apply_disc_fee && $po->disc_fee_amount > 0)
                                            <div class="flex justify-between text-rose-500">
                                                <span>Diskon Akhir</span>
                                                <span class="font-mono">- {{ number_format($po->disc_fee_amount, 0, ',', '.') }}</span>
                                            </div>
                                            @endif
                                            @if($po->tax_id)
                                            <div class="flex justify-between">
                                                <span class="text-slate-500">PPN ({{ $po->tax->rate }}%)</span>
                                                <span class="font-mono">{{ number_format($po->ppn, 0, ',', '.') }}</span>
                                            </div>
                                            @endif
                                            <div class="border-t border-slate-100 dark:border-slate-700 my-1"></div>
                                            <div class="flex justify-between font-bold text-sm text-indigo-600 dark:text-indigo-400">
                                                <span>Grand Total</span>
                                                <span class="font-mono">Rp {{ number_format($po->grand_total, 0, ',', '.') }}</span>
                                            </div>
                                            @if($po->total_returned > 0)
                                            <div class="flex justify-between text-rose-500 font-bold mt-1 bg-rose-50 p-1 rounded">
                                                <span>Total Retur</span>
                                                <span class="font-mono">- Rp {{ number_format($po->total_returned, 0, ',', '.') }}</span>
                                            </div>
                                            @endif
                                        </div>
                                        <div class="mt-3 text-right">
                                            <a href="{{ route('admin.purchase-orders.show', $po->po_id) }}" class="text-xs font-bold text-indigo-600 hover:underline">Lihat Detail Lengkap &rarr;</a>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </td>
                    </tr>
                </tbody>
                @empty
                <tbody>
                    <tr>
                        <td colspan="7" class="px-6 py-10 text-center text-slate-400">
                            <div class="flex flex-col items-center">
                                <div class="w-16 h-16 bg-slate-100 dark:bg-slate-800 rounded-full flex items-center justify-center mb-3">
                                    <i class="material-icons text-3xl text-slate-300">search_off</i>
                                </div>
                                <p class="font-medium">Belum ada data Purchase Order.</p>
                            </div>
                        </td>
                    </tr>
                </tbody>
                @endforelse
            </table>
        </div>
        
        @if($purchaseOrders->hasPages())
        <div class="px-6 py-4 bg-slate-50 dark:bg-slate-800 border-t border-slate-200 dark:border-slate-700">
            {{ $purchaseOrders->links('vendor.pagination.admin') }}
        </div>
        @endif
    </div>

</div>

@push('scripts')
<script>
    document.addEventListener('alpine:init', () => {
        Alpine.data('purchaseOrderIndex', () => ({
            confirmDelete(formId) {
                window.confirmDialog({
                    title: 'Hapus Draft PO?',
                    text: 'Data yang dihapus tidak dapat dikembalikan.',
                    icon: 'warning',
                    confirmText: 'Ya, Hapus',
                    confirmColor: 'danger'
                }).then((result) => {
                    if (result.isConfirmed) document.getElementById(formId).submit();
                });
            },
            confirmProcess(formId) {
                window.confirmDialog({
                    title: 'Proses Pesanan?',
                    text: 'Status akan berubah menjadi "Ordered". Stok dalam perjalanan akan dicatat.',
                    icon: 'question',
                    confirmText: 'Ya, Proses',
                    confirmColor: 'primary'
                }).then((result) => {
                    if (result.isConfirmed) document.getElementById(formId).submit();
                });
            },
            confirmReceive(formId) {
                window.confirmDialog({
                    title: 'Terima Barang?',
                    text: 'Pastikan fisik barang sudah diterima. Stok gudang akan bertambah dan HPP dihitung ulang.',
                    icon: 'warning',
                    confirmText: 'Ya, Terima',
                    confirmColor: 'success'
                }).then((result) => {
                    if (result.isConfirmed) document.getElementById(formId).submit();
                });
            }
        }));
    });

    document.addEventListener('DOMContentLoaded', function() {
        const filterSelects = document.querySelectorAll('.tom-select-filter');
        filterSelects.forEach((el) => {
            if(!el.tomselect) new TomSelect(el, { ...window.defaultTomSelectConfig, placeholder: 'Pilih...', allowEmptyOption: true, maxOptions: 50 });
        });
    });
</script>
@endpush

@endsection
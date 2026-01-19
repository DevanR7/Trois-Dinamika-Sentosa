@extends('admin.layouts.app')

@section('title', 'Detail Purchase Order ' . $purchaseOrder->po_number)

@section('content')
<div class="flex flex-col gap-6 pb-20" x-data="purchaseOrderShow()">
    
    {{-- 1. HEADER & ACTIONS --}}
    <div class="flex flex-col lg:flex-row lg:items-center justify-between gap-4 border-b border-slate-200 dark:border-slate-700 pb-4">
        {{-- KIRI: JUDUL & STATUS --}}
        <div>
            <div class="flex items-center gap-3 mb-1">
                <h1 class="text-2xl font-bold text-slate-800 dark:text-white tracking-tight">
                    {{ $purchaseOrder->po_number }}
                </h1>
                
                {{-- Status Badge Logic --}}
                @php
                    $statusClass = match($purchaseOrder->status) {
                        'draft' => 'bg-slate-100 text-slate-600 border border-slate-200',
                        'ordered' => 'bg-blue-100 text-blue-700 border border-blue-200',
                        'completed' => 'bg-emerald-100 text-emerald-700 border border-emerald-200',
                        'cancelled' => 'bg-rose-100 text-rose-700 border border-rose-200',
                        default => 'bg-slate-100 text-slate-600'
                    };
                    
                    $paymentClass = match($purchaseOrder->payment_status) {
                        'paid' => 'bg-emerald-100 text-emerald-700 border border-emerald-200',
                        'partially_paid' => 'bg-amber-100 text-amber-700 border border-amber-200',
                        'unpaid' => 'bg-rose-100 text-rose-700 border border-rose-200',
                        default => 'bg-slate-100 text-slate-600'
                    };
                @endphp
                
                <span class="px-3 py-1 rounded-full text-xs font-bold uppercase {{ $statusClass }}">
                    {{ match($purchaseOrder->status) { 'ordered' => 'Dipesan', 'completed' => 'Selesai', 'cancelled' => 'Batal', default => ucfirst($purchaseOrder->status) } }}
                </span>
                
                <span class="px-3 py-1 rounded-full text-xs font-bold uppercase {{ $paymentClass }}">
                    {{ match($purchaseOrder->payment_status) { 'paid' => 'Lunas', 'partially_paid' => 'Parsial', 'unpaid' => 'Belum Lunas', default => '-' } }}
                </span>
            </div>
            <p class="text-sm text-slate-500">
                Dibuat pada {{ $purchaseOrder->order_date->translatedFormat('d F Y') }} oleh <strong>{{ $purchaseOrder->requester->full_name ?? 'System' }}</strong>
            </p>
        </div>

        {{-- KANAN: ACTION BUTTONS --}}
        <div class="flex flex-wrap items-center gap-2">
            
            {{-- Tombol Kembali --}}
            <a href="{{ route('admin.purchase-orders.index') }}" class="btn btn-secondary shadow-sm">
                <i class="material-icons text-[18px] mr-1">arrow_back</i> Kembali
            </a>

            {{-- Tombol Proses Pesanan (Hanya Muncul saat Draft) --}}
            @if($purchaseOrder->status === 'draft')
                <form action="{{ route('admin.purchase-orders.mark-ordered', $purchaseOrder->po_id) }}" method="POST" id="form-process-order">
                    @csrf
                    @method('PATCH')
                    <button type="button" @click="confirmProcess()" class="btn btn-primary shadow-lg shadow-indigo-500/20">
                        <i class="material-icons text-[18px] mr-1">send</i> Proses Pesanan
                    </button>
                </form>
            @endif

            {{-- Tombol Terima Barang (Hanya jika Ordered) --}}
            @if($purchaseOrder->status === 'ordered')
                <form action="{{ route('admin.purchase-orders.receive', $purchaseOrder->po_id) }}" method="POST" id="form-receive-goods">
                    @csrf
                    <button type="button" @click="confirmReceive()" class="btn btn-primary shadow-lg shadow-indigo-500/20">
                        <i class="material-icons text-[18px] mr-1">inventory_2</i> Terima Barang
                    </button>
                </form>
            @endif

            {{-- Dropdown Opsi Lainnya --}}
            <div class="relative" x-data="{ open: false }">
                <button @click="open = !open" @click.outside="open = false" class="btn btn-secondary border-slate-300">
                    Opsi Lainnya <i class="material-icons text-[18px] ml-1">expand_more</i>
                </button>
                
                <div x-show="open" 
                     x-transition:enter="transition ease-out duration-100"
                     x-transition:enter-start="transform opacity-0 scale-95"
                     x-transition:enter-end="transform opacity-100 scale-100"
                     class="absolute right-0 mt-2 w-56 bg-white dark:bg-slate-800 rounded-xl shadow-xl border border-slate-200 dark:border-slate-700 z-50 py-1"
                     style="display: none;">
                    
                    {{-- Edit (Hanya Draft/Ordered) --}}
                    @if(in_array($purchaseOrder->status, ['draft', 'ordered']))
                        <a href="{{ route('admin.purchase-orders.edit', $purchaseOrder->po_id) }}" class="flex items-center px-4 py-2 text-sm text-slate-700 hover:bg-slate-50 dark:text-slate-200 dark:hover:bg-slate-700">
                            <i class="material-icons text-[18px] mr-2 text-amber-500">edit</i> Edit Pesanan
                        </a>
                    @endif

                    {{-- Cetak --}}
                    <a href="{{ route('admin.purchase-orders.pdf', $purchaseOrder->po_id) }}" target="_blank" class="flex items-center px-4 py-2 text-sm text-slate-700 hover:bg-slate-50 dark:text-slate-200 dark:hover:bg-slate-700">
                        <i class="material-icons text-[18px] mr-2 text-slate-400">print</i> Cetak PDF
                    </a>

                    @if($purchaseOrder->status === 'completed')
                        <div class="border-t border-slate-100 dark:border-slate-700 my-1"></div>
                        
                        {{-- Adjustment (Link ke Halaman Pilihan) --}}
                        <a href="{{ route('admin.purchase-order-adjustments.create', ['purchase_order_id' => $purchaseOrder->po_id]) }}" class="flex items-center px-4 py-2 text-sm text-slate-700 hover:bg-slate-50 dark:text-slate-200 dark:hover:bg-slate-700">
                            <i class="material-icons text-[18px] mr-2 text-blue-500">tune</i> Koreksi / Adjustment
                        </a>
                        
                        {{-- Return --}}
                        <a href="{{ route('admin.purchase-returns.create', ['purchase_order_id' => $purchaseOrder->po_id]) }}" class="flex items-center px-4 py-2 text-sm text-slate-700 hover:bg-slate-50 dark:text-slate-200 dark:hover:bg-slate-700">
                            <i class="material-icons text-[18px] mr-2 text-rose-500">assignment_return</i> Retur Pembelian
                        </a>
                    @endif

                    @if($purchaseOrder->status === 'draft' || $purchaseOrder->status === 'ordered')
                        <div class="border-t border-slate-100 dark:border-slate-700 my-1"></div>
                        {{-- Cancel --}}
                        <form action="{{ route('admin.purchase-orders.cancel', $purchaseOrder->po_id) }}" method="POST" onsubmit="return confirm('Yakin batalkan pesanan ini?')">
                            @csrf
                            <button type="submit" class="flex w-full items-center px-4 py-2 text-sm text-rose-600 hover:bg-rose-50 dark:hover:bg-rose-900/20">
                                <i class="material-icons text-[18px] mr-2">cancel</i> Batalkan Pesanan
                            </button>
                        </form>
                    @endif
                    
                    {{-- Tombol Hapus (Hanya Draft) --}}
                    @if($purchaseOrder->status === 'draft')
                         <form action="{{ route('admin.purchase-orders.destroy', $purchaseOrder->po_id) }}" method="POST" onsubmit="return confirm('Yakin hapus draft ini secara permanen?')">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="flex w-full items-center px-4 py-2 text-sm text-rose-600 hover:bg-rose-50 dark:hover:bg-rose-900/20">
                                <i class="material-icons text-[18px] mr-2">delete</i> Hapus Draft
                            </button>
                        </form>
                    @endif
                </div>
            </div>
        </div>
    </div>

    {{-- 2. FINANCIAL DASHBOARD & SUPPLIER INFO --}}
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        
        {{-- Card Kiri: Supplier & Info --}}
        <div class="card p-0 lg:col-span-2 overflow-hidden">
            <div class="p-5 border-b border-slate-100 dark:border-slate-700 bg-slate-50/50 dark:bg-slate-800/50 flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4">
                <h3 class="text-xs font-bold text-slate-500 uppercase tracking-wider">Informasi Supplier</h3>
                
                {{-- Form Nomor Faktur Supplier --}}
                <form action="{{ route('admin.purchase-orders.addSupplierInvoice', $purchaseOrder->po_id) }}" method="POST" class="flex items-center gap-2 w-full sm:w-auto">
                    @csrf
                    <div class="relative w-full sm:w-auto">
                        <span class="absolute left-3 top-1/2 -translate-y-1/2 text-slate-400 pointer-events-none flex items-center">
                            <i class="material-icons text-[18px]">receipt</i>
                        </span>
                        <input type="text" name="supplier_invoice_number" 
                               value="{{ $purchaseOrder->supplier_invoice_number }}" 
                               class="form-input py-2 pl-9 pr-3 text-xs w-full sm:w-64 border-slate-300 focus:border-indigo-500 rounded-lg shadow-sm" 
                               placeholder="Input No. Faktur Supplier">
                    </div>
                    <button type="submit" class="btn btn-sm btn-primary px-3 h-[34px]" title="Simpan No Faktur">
                        <i class="material-icons text-[18px]">save</i>
                    </button>
                </form>
            </div>
            
            <div class="p-5 grid grid-cols-1 md:grid-cols-2 gap-6">
                {{-- Detail Supplier --}}
                <div class="flex gap-4">
                    <div class="w-12 h-12 rounded-xl bg-indigo-50 dark:bg-indigo-900/20 flex items-center justify-center text-indigo-600 dark:text-indigo-400 shrink-0">
                        <i class="material-icons text-2xl">store</i>
                    </div>
                    <div>
                        <div class="text-base font-bold text-slate-800 dark:text-white">{{ $purchaseOrder->supplier->supplier_name }}</div>
                        <div class="text-sm text-slate-500 dark:text-slate-400 mt-0.5">
                            {{ $purchaseOrder->supplier->address ?? 'Alamat tidak tersedia' }}
                        </div>
                        <div class="flex items-center gap-2 mt-2 text-xs font-medium text-slate-600 dark:text-slate-300">
                            <span class="flex items-center gap-1"><i class="material-icons text-[14px]">person</i> {{ $purchaseOrder->supplier->person_in_charge ?? '-' }}</span>
                            <span class="text-slate-300">|</span>
                            <span class="flex items-center gap-1"><i class="material-icons text-[14px]">phone</i> {{ $purchaseOrder->supplier->phone_number ?? '-' }}</span>
                        </div>
                        @if($purchaseOrder->supplier->wa_link)
                            <a href="{{ $purchaseOrder->supplier->wa_link }}" target="_blank" class="inline-flex items-center gap-1 mt-2 text-xs text-emerald-600 hover:underline">
                                <i class="material-icons text-[14px]">chat</i> Chat WhatsApp
                            </a>
                        @endif
                    </div>
                </div>

                {{-- Detail Tanggal --}}
                <div class="space-y-3 pl-0 md:pl-6 md:border-l border-slate-100 dark:border-slate-700">
                    <div class="flex justify-between items-center text-sm">
                        <span class="text-slate-500">Tgl Order:</span>
                        <span class="font-medium text-slate-700 dark:text-slate-200">{{ $purchaseOrder->order_date->format('d/m/Y') }}</span>
                    </div>
                    <div class="flex justify-between items-center text-sm">
                        <span class="text-slate-500">Jatuh Tempo:</span>
                        <span class="font-medium {{ $purchaseOrder->due_date < now() && $purchaseOrder->payment_status != 'paid' ? 'text-rose-500 font-bold' : 'text-slate-700 dark:text-slate-200' }}">
                            {{ $purchaseOrder->due_date ? $purchaseOrder->due_date->format('d/m/Y') : '-' }}
                        </span>
                    </div>
                    @if($purchaseOrder->expected_delivery_date)
                    <div class="flex justify-between items-center text-sm">
                        <span class="text-slate-500">Estimasi Sampai:</span>
                        <span class="font-medium text-slate-700 dark:text-slate-200">
                            {{ $purchaseOrder->expected_delivery_date instanceof \Carbon\Carbon ? $purchaseOrder->expected_delivery_date->format('d/m/Y') : \Carbon\Carbon::parse($purchaseOrder->expected_delivery_date)->format('d/m/Y') }}
                        </span>
                    </div>
                    @endif
                </div>
            </div>
        </div>

        {{-- Card Kanan: Financial Stats --}}
        <div class="flex flex-col gap-4">
            {{-- Total Tagihan --}}
            <div class="card p-4 border-l-4 border-l-indigo-500 flex justify-between items-center shadow-sm hover:shadow-md transition-shadow">
                <div>
                    <div class="text-[10px] text-slate-400 uppercase font-bold tracking-wider mb-1">Total Tagihan (Awal)</div>
                    <div class="text-xl font-mono font-bold text-slate-700 dark:text-white">
                        Rp {{ number_format($purchaseOrder->grand_total, 0, ',', '.') }}
                    </div>
                </div>
                <div class="p-2.5 bg-indigo-50 dark:bg-indigo-900/30 rounded-xl text-indigo-600 dark:text-indigo-400">
                    <i class="material-icons">receipt_long</i>
                </div>
            </div>
            
            {{-- Sudah Dibayar --}}
            <div class="card p-4 border-l-4 border-l-emerald-500 flex justify-between items-center shadow-sm hover:shadow-md transition-shadow">
                <div>
                    <div class="text-[10px] text-slate-400 uppercase font-bold tracking-wider mb-1">Sudah Dibayar</div>
                    <div class="text-xl font-mono font-bold text-emerald-600 dark:text-emerald-400">
                        Rp {{ number_format($purchaseOrder->amount_paid, 0, ',', '.') }}
                    </div>
                </div>
                <div class="p-2.5 bg-emerald-50 dark:bg-emerald-900/30 rounded-xl text-emerald-600 dark:text-emerald-400">
                    <i class="material-icons">payments</i>
                </div>
            </div>

            {{-- Sisa Hutang --}}
            <div class="card p-4 border-l-4 border-l-rose-500 flex justify-between items-center shadow-sm hover:shadow-md transition-shadow">
                <div>
                    <div class="text-[10px] text-slate-400 uppercase font-bold tracking-wider mb-1">Sisa Hutang (Net)</div>
                    <div class="text-xl font-mono font-bold text-rose-600 dark:text-rose-400">
                        Rp {{ number_format($purchaseOrder->remaining_balance, 0, ',', '.') }}
                    </div>
                </div>
                <div class="p-2.5 bg-rose-50 dark:bg-rose-900/30 rounded-xl text-rose-600 dark:text-rose-400">
                    <i class="material-icons">account_balance_wallet</i>
                </div>
            </div>
        </div>
    </div>

    {{-- 3. ITEM LIST TABLE --}}
    <div class="card overflow-hidden border border-slate-200 dark:border-slate-700">
        <div class="px-6 py-4 border-b border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-800/50">
            <h3 class="font-bold text-slate-700 dark:text-white flex items-center gap-2">
                <i class="material-icons text-slate-400 text-sm">list</i> Rincian Barang
            </h3>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full text-sm text-left">
                <thead class="text-xs text-slate-500 uppercase bg-slate-50 dark:bg-slate-800 border-b border-slate-200 dark:border-slate-700">
                    <tr>
                        <th class="px-6 py-3 w-10">#</th>
                        <th class="px-6 py-3">Produk</th>
                        <th class="px-6 py-3 text-right">Harga Satuan</th>
                        <th class="px-6 py-3 text-center">Qty</th>
                        <th class="px-6 py-3 text-center">Diskon</th>
                        <th class="px-6 py-3 text-right">Subtotal</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 dark:divide-slate-700">
                    @foreach($purchaseOrder->items as $index => $item)
                        <tr class="hover:bg-slate-50/50 dark:hover:bg-slate-800/30 transition-colors">
                            <td class="px-6 py-3 text-slate-400 text-xs">{{ $loop->iteration }}</td>
                            <td class="px-6 py-3">
                                <div class="font-medium text-slate-700 dark:text-slate-200">{{ $item->product->product_name }}</div>
                                <div class="text-xs text-slate-400 font-mono mt-0.5">{{ $item->product->product_code }}</div>
                            </td>
                            <td class="px-6 py-3 text-right font-mono text-slate-600 dark:text-slate-300">
                                {{ number_format($item->price_per_unit, 0, ',', '.') }}
                            </td>
                            <td class="px-6 py-3 text-center">
                                <span class="font-bold text-slate-700 dark:text-slate-200">{{ (float)$item->quantity }}</span> 
                                <span class="text-xs text-slate-400 ml-1">{{ $item->product->unit->name ?? 'Unit' }}</span>
                            </td>
                            <td class="px-6 py-3 text-center text-xs text-slate-500">
                                @if($item->discounts->count() > 0)
                                    @foreach($item->discounts as $disc)
                                        <span class="bg-indigo-50 text-indigo-600 px-1.5 py-0.5 rounded border border-indigo-100">{{ (float)$disc->percentage }}%</span>
                                    @endforeach
                                @else
                                    -
                                @endif
                            </td>
                            <td class="px-6 py-3 text-right font-mono font-bold text-slate-800 dark:text-white">
                                {{ number_format($item->subtotal, 0, ',', '.') }}
                            </td>
                        </tr>
                    @endforeach
                </tbody>
                
                {{-- FOOTER SUMMARY DETAILED (TWEAKED) --}}
                <tfoot class="bg-slate-50/50 dark:bg-slate-800 border-t border-slate-200 dark:border-slate-700 text-sm">
                    <tr>
                        <td colspan="5" class="px-6 py-2 text-right text-xs font-bold text-slate-500 uppercase">Subtotal Barang</td>
                        <td class="px-6 py-2 text-right font-mono font-medium text-slate-700 dark:text-slate-200">
                            {{ number_format($purchaseOrder->subtotal, 0, ',', '.') }}
                        </td>
                    </tr>
                    
                    <tr>
                        <td colspan="5" class="px-6 py-1 text-right text-xs text-rose-500">
                            Diskon Akhir @if($purchaseOrder->disc_fee_percent > 0) ({{ (float)$purchaseOrder->disc_fee_percent }}%) @endif
                        </td>
                        <td class="px-6 py-1 text-right font-mono text-xs text-rose-500">
                            - {{ number_format($purchaseOrder->disc_fee_amount, 0, ',', '.') }}
                        </td>
                    </tr>

                    <tr>
                        <td colspan="5" class="px-6 py-1 text-right text-xs text-rose-500">Potongan Pembulatan</td>
                        <td class="px-6 py-1 text-right font-mono text-xs text-rose-500">
                            - {{ number_format($purchaseOrder->rounding_discount_amount, 0, ',', '.') }}
                        </td>
                    </tr>

                    <tr>
                        <td colspan="6" class="border-b border-dashed border-slate-200 dark:border-slate-700"></td>
                    </tr>

                    <tr>
                        <td colspan="5" class="px-6 py-1 text-right text-xs font-bold text-slate-600 dark:text-slate-400 uppercase">
                            DPP (Dasar Pengenaan Pajak)
                        </td>
                        <td class="px-6 py-1 text-right font-mono text-sm font-bold text-slate-700 dark:text-slate-200">
                            {{ number_format($purchaseOrder->dpp, 0, ',', '.') }}
                        </td>
                    </tr>

                    @if($purchaseOrder->use_custom_dpp_factor)
                    <tr>
                        <td colspan="5" class="px-6 py-0 text-right text-[10px] text-indigo-500 italic">
                            *Menggunakan Faktor DPP: {{ $purchaseOrder->custom_dpp_factor == 0.91666667 ? '11/12' : round($purchaseOrder->custom_dpp_factor, 4) }}
                        </td>
                        <td class="px-6 py-0"></td>
                    </tr>
                    @endif

                    <tr>
                        <td colspan="5" class="px-6 py-1 text-right text-xs text-slate-500">
                            PPN @if($purchaseOrder->tax_id) ({{ $purchaseOrder->tax->rate }}%) @else (0%) @endif
                        </td>
                        <td class="px-6 py-1 text-right font-mono text-xs text-slate-600 dark:text-slate-400">
                            + {{ number_format($purchaseOrder->ppn, 0, ',', '.') }}
                        </td>
                    </tr>

                    <tr>
                        <td colspan="5" class="px-6 py-1 text-right text-xs text-slate-500">Biaya Kirim / Lainnya</td>
                        <td class="px-6 py-1 text-right font-mono text-xs text-slate-600 dark:text-slate-400">
                            + {{ number_format($purchaseOrder->shipping_amount, 0, ',', '.') }}
                        </td>
                    </tr>

                    {{-- 1. TOTAL ORDER (GRAND TOTAL AWAL) --}}
                    <tr class="bg-slate-100 dark:bg-slate-700/50 font-bold border-y border-slate-200 dark:border-slate-700">
                        <td colspan="5" class="px-6 py-2 text-right text-xs text-slate-700 dark:text-slate-300 uppercase">Total Order</td>
                        <td class="px-6 py-2 text-right font-mono text-slate-700 dark:text-white">
                            Rp {{ number_format($purchaseOrder->grand_total, 0, ',', '.') }}
                        </td>
                    </tr>

                    {{-- 2. PENYESUAIAN (ADJUSTMENT) --}}
                    @foreach($purchaseOrder->adjustments as $adj)
                        <tr>
                            <td colspan="5" class="px-6 py-1 text-right text-xs {{ $adj->type == 'debit_note' ? 'text-indigo-600' : 'text-rose-500' }}">
                                {{ $adj->type == 'debit_note' ? 'Adj: Debit Note (Tambah Tagihan)' : 'Adj: Credit Note (Potong Tagihan)' }}
                                <span class="text-[10px] text-slate-400 ml-1">({{ Str::limit($adj->reason, 20) }})</span>
                            </td>
                            <td class="px-6 py-1 text-right font-mono text-xs {{ $adj->type == 'debit_note' ? 'text-indigo-600' : 'text-rose-500' }}">
                                {{ $adj->type == 'debit_note' ? '+' : '-' }} {{ number_format($adj->amount, 0, ',', '.') }}
                            </td>
                        </tr>
                    @endforeach

                    {{-- 3. RETUR POTONG TAGIHAN --}}
                    @if($purchaseOrder->total_returned > 0)
                    <tr>
                        <td colspan="5" class="px-6 py-1 text-right text-xs text-rose-500">
                            Retur (Potong Tagihan)
                        </td>
                        <td class="px-6 py-1 text-right font-mono text-xs text-rose-500">
                            - {{ number_format($purchaseOrder->total_returned, 0, ',', '.') }}
                        </td>
                    </tr>
                    @endif

                    {{-- 4. TOTAL KEWAJIBAN (NET) --}}
                    @php
                        $adjDebit = $purchaseOrder->adjustments->where('type', 'debit_note')->sum('amount');
                        $adjCredit = $purchaseOrder->adjustments->where('type', 'credit_note')->sum('amount');
                        // Rumus Net: GrandTotal + Debit - Credit - Retur
                        $netPayable = $purchaseOrder->grand_total + $adjDebit - $adjCredit - $purchaseOrder->total_returned;
                    @endphp
                    <tr class="bg-indigo-50 dark:bg-indigo-900/20 border-t-2 border-indigo-100 dark:border-indigo-800">
                        <td colspan="5" class="px-6 py-3 text-right text-sm font-bold text-indigo-700 dark:text-indigo-300 uppercase">
                            Total Kewajiban (Net)
                        </td>
                        <td class="px-6 py-3 text-right font-mono text-lg font-bold text-indigo-700 dark:text-indigo-300">
                            Rp {{ number_format($netPayable, 0, ',', '.') }}
                        </td>
                    </tr>

                    {{-- INFO RETUR DEPOSIT (Tidak Mengurangi Tagihan di sini, hanya info) --}}
                    @php
                        $depositReturns = $purchaseOrder->returns->where('return_handling_type', 'store_as_deposit')->sum('total_amount');
                    @endphp
                    
                    @if($depositReturns > 0)
                    <tr>
                        <td colspan="5" class="px-6 py-1 text-right text-xs text-amber-600 font-bold uppercase">
                            Info: Retur Masuk Deposit
                        </td>
                        <td class="px-6 py-1 text-right font-mono text-xs text-amber-600 font-bold">
                            ({{ number_format($depositReturns, 0, ',', '.') }})
                        </td>
                    </tr>
                    <tr>
                        <td colspan="6" class="px-6 py-0 text-right text-[10px] text-slate-400 italic">
                            *Nilai ini tersimpan di deposit supplier, gunakan centang "Gunakan Saldo" saat pembayaran.
                        </td>
                    </tr>
                    @endif

                </tfoot>
            </table>
        </div>
    </div>

    {{-- 4. TABS: PEMBAYARAN, RETUR, CATATAN --}}
    <div x-data="{ activeTab: 'payment' }" class="min-h-[400px]">
        {{-- Tab Headers --}}
        <div class="flex gap-2 border-b border-slate-200 dark:border-slate-700 mb-6 overflow-x-auto">
            <button @click="activeTab = 'payment'" 
                    class="px-5 py-3 text-sm font-bold border-b-2 transition-all whitespace-nowrap"
                    :class="activeTab === 'payment' ? 'border-indigo-500 text-indigo-600 dark:text-indigo-400' : 'border-transparent text-slate-500 hover:text-slate-700 dark:text-slate-400 dark:hover:text-slate-200'">
                <i class="material-icons text-[18px] align-text-bottom mr-1">payments</i> Pembayaran
            </button>
            
            <button @click="activeTab = 'returns'" 
                    class="px-5 py-3 text-sm font-bold border-b-2 transition-all whitespace-nowrap"
                    :class="activeTab === 'returns' ? 'border-rose-500 text-rose-600 dark:text-rose-400' : 'border-transparent text-slate-500 hover:text-slate-700 dark:text-slate-400 dark:hover:text-slate-200'">
                <i class="material-icons text-[18px] align-text-bottom mr-1">assignment_return</i> Retur & Adjustment
            </button>
            
            @if($purchaseOrder->notes)
            <button @click="activeTab = 'notes'" 
                    class="px-5 py-3 text-sm font-bold border-b-2 transition-all whitespace-nowrap"
                    :class="activeTab === 'notes' ? 'border-amber-500 text-amber-600' : 'border-transparent text-slate-500 hover:text-slate-700 dark:text-slate-400'">
                <i class="material-icons text-[18px] align-text-bottom mr-1">sticky_note_2</i> Catatan
            </button>
            @endif
        </div>

        {{-- TAB 1: PEMBAYARAN --}}
        <div x-show="activeTab === 'payment'" class="animate-enter" x-data="paymentFormLogic()">
            
            {{-- SUB-TAB SWITCHER --}}
            <div class="flex items-center gap-2 mb-4 bg-slate-100 dark:bg-slate-800 p-1 rounded-lg w-fit">
                <button @click="subTab = 'history'" 
                        class="px-4 py-1.5 text-xs font-bold rounded-md transition-all"
                        :class="subTab === 'history' ? 'bg-white dark:bg-slate-700 text-indigo-600 shadow-sm' : 'text-slate-500 hover:text-slate-700'">
                    <i class="material-icons text-[14px] align-text-bottom mr-1">history</i> Riwayat
                </button>
                
                @if($purchaseOrder->remaining_balance > 0 && $purchaseOrder->status != 'cancelled')
                <button @click="subTab = 'form'" 
                        class="px-4 py-1.5 text-xs font-bold rounded-md transition-all"
                        :class="subTab === 'form' ? 'bg-white dark:bg-slate-700 text-indigo-600 shadow-sm' : 'text-slate-500 hover:text-slate-700'">
                    <i class="material-icons text-[14px] align-text-bottom mr-1">add_card</i> Input Baru
                </button>
                @endif
            </div>

            {{-- SUB-CONTENT 1: RIWAYAT (LOG) --}}
            <div x-show="subTab === 'history'" class="card p-0 overflow-hidden">
                <table class="w-full text-sm text-left">
                    <thead class="text-xs text-slate-500 uppercase bg-slate-50 dark:bg-slate-800">
                        <tr>
                            <th class="px-5 py-3">Tanggal</th>
                            <th class="px-5 py-3">Metode</th>
                            <th class="px-5 py-3">Ref/Catatan</th>
                            <th class="px-5 py-3 text-right">Nominal</th>
                            <th class="px-5 py-3 text-center">Status</th>
                            <th class="px-5 py-3 w-10"></th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 dark:divide-slate-700">
                        @forelse($purchaseOrder->payments as $payment)
                            <tr class="hover:bg-slate-50 dark:hover:bg-slate-800/50 transition-colors">
                                <td class="px-5 py-3 text-slate-600 dark:text-slate-300">{{ $payment->payment_date->format('d/m/Y') }}</td>
                                <td class="px-5 py-3">
                                    @if(!$payment->payment_method_id && str_contains($payment->notes, 'Saldo Kredit'))
                                        <span class="badge bg-amber-100 text-amber-700 border-amber-200">Saldo Deposit</span>
                                    @elseif(!$payment->payment_method_id && str_contains($payment->notes, 'Auto-allocated'))
                                         <span class="badge bg-blue-100 text-blue-700 border-blue-200">Bulk Alloc</span>
                                    @else
                                        <span class="badge badge-secondary">{{ $payment->paymentMethod->name ?? 'Manual' }}</span>
                                    @endif
                                </td>
                                <td class="px-5 py-3">
                                    <div class="flex flex-col">
                                        <span class="text-xs font-mono text-slate-500">{{ $payment->reference_number ?? '-' }}</span>
                                        <span class="text-[10px] text-slate-400 truncate max-w-[150px]" title="{{ $payment->notes }}">{{ $payment->notes }}</span>
                                    </div>
                                </td>
                                <td class="px-5 py-3 text-right font-mono font-bold text-emerald-600 dark:text-emerald-400">
                                    {{ number_format($payment->amount, 0, ',', '.') }}
                                </td>
                                <td class="px-5 py-3 text-center">
                                    @if($payment->status == 'completed')
                                        <i class="material-icons text-emerald-500 text-sm" title="Lunas / Terverifikasi">check_circle</i>
                                    @elseif($payment->status == 'pending_clearance')
                                        <span class="badge bg-amber-50 text-amber-600 border-amber-100">Menunggu Kliring</span>
                                    @else
                                        <span class="badge badge-danger">Gagal</span>
                                    @endif
                                </td>
                                <td class="px-5 py-3 text-center">
                                    @if($payment->status == 'completed' || $payment->status == 'pending_verification')
                                        <form action="{{ route('admin.purchase-orders.payments.destroy', $payment->id) }}" method="POST" id="form-delete-payment-{{ $payment->id }}">
                                            @csrf
                                            @method('DELETE')
                                            <button type="button" @click="confirmDelete('form-delete-payment-{{ $payment->id }}')" class="text-slate-300 hover:text-rose-500 transition-colors" title="Hapus Pembayaran">
                                                <i class="material-icons text-[18px]">delete</i>
                                            </button>
                                        </form>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="6" class="text-center py-8 text-slate-400 italic">Belum ada riwayat pembayaran untuk PO ini.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            {{-- SUB-CONTENT 2: FORM INPUT BARU --}}
            @if($purchaseOrder->remaining_balance > 0 && $purchaseOrder->status != 'cancelled')
            <div x-show="subTab === 'form'" class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                
                {{-- Panel Kiri: Kalkulator Realtime --}}
                <div class="lg:col-span-2 space-y-4">
                    {{-- Alert Info Sisa Hutang --}}
                    <div class="p-4 rounded-xl bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 flex justify-between items-center">
                        <div>
                            <div class="text-xs text-slate-500 uppercase font-bold tracking-wider">Total Belum Dibayar</div>
                            <div class="text-2xl font-mono font-bold text-slate-700 dark:text-white mt-1">
                                <span x-text="formatRupiah(remainingBalance)"></span>
                            </div>
                        </div>
                        <div class="h-10 w-10 rounded-full bg-slate-200 dark:bg-slate-700 flex items-center justify-center text-slate-500">
                            <i class="material-icons">account_balance_wallet</i>
                        </div>
                    </div>

                    {{-- Card Split Payment --}}
                    <div class="card p-5 border border-indigo-100 dark:border-indigo-900 shadow-sm relative overflow-hidden">
                        <div class="absolute top-0 left-0 w-1 h-full bg-indigo-500"></div>
                        <h4 class="font-bold text-slate-700 dark:text-slate-200 mb-4 flex items-center gap-2">
                            <i class="material-icons text-indigo-500 text-sm">calculate</i> Kalkulasi Pembayaran
                        </h4>

                        {{-- 1. Opsi Saldo Deposit --}}
                        <div class="flex items-center justify-between p-3 bg-amber-50 dark:bg-amber-900/20 rounded-lg border border-amber-100 dark:border-amber-800 mb-4">
                            <div class="flex items-center gap-3">
                                <input type="checkbox" id="use_ledger" x-model="useLedger" class="form-check-input w-5 h-5 text-amber-600 rounded focus:ring-amber-500">
                                <div>
                                    <label for="use_ledger" class="text-sm font-bold text-amber-800 dark:text-amber-200 cursor-pointer">Gunakan Saldo Deposit Supplier</label>
                                    <div class="text-xs text-amber-600/80 dark:text-amber-400">Tersedia: <span x-text="formatRupiah(supplierBalance)"></span></div>
                                </div>
                            </div>
                            <div class="text-right" x-show="useLedger">
                                <span class="text-xs text-amber-600 uppercase font-bold">Akan Dipotong</span>
                                <div class="text-lg font-mono font-bold text-amber-700 dark:text-amber-300" x-text="'- ' + formatRupiah(depositUsed)"></div>
                            </div>
                        </div>

                        {{-- 2. Sisa yang harus dibayar Manual --}}
                        <div class="flex justify-between items-center py-2 border-t border-dashed border-slate-200 dark:border-slate-700 mb-2">
                            <span class="text-sm text-slate-500">Kekurangan (Bayar Manual):</span>
                            <span class="text-lg font-mono font-bold text-slate-700 dark:text-white" x-text="formatRupiah(manualNeeded)"></span>
                        </div>

                        {{-- 3. Input Nominal Manual --}}
                        <div>
                            <label class="form-label mb-1">Input Nominal Transfer / Cash</label>
                            
                            {{-- Input Group Flexbox --}}
                            <div class="flex rounded-xl shadow-sm">
                                <div class="relative flex-grow focus-within:z-10">
                                    <div class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none">
                                        <span class="text-slate-400 font-bold">Rp</span>
                                    </div>
                                    <input type="text" id="manual_payment_input" 
                                           class="form-input pl-10 text-right font-mono font-bold text-lg rounded-r-none border-r-0 focus:ring-inset" 
                                           placeholder="0">
                                </div>
                                <button type="button" @click="setFullAmount()" 
                                        class="inline-flex items-center px-4 py-2 border border-l-0 border-slate-300 dark:border-slate-600 bg-slate-100 dark:bg-slate-700 text-indigo-600 dark:text-indigo-400 font-bold text-xs uppercase hover:bg-indigo-50 dark:hover:bg-slate-600 rounded-r-xl transition-colors whitespace-nowrap">
                                    MAX / Lunas
                                </button>
                            </div>
                            
                            {{-- Notifikasi Overpayment --}}
                            <div x-show="overpayment > 0" class="mt-2 p-2 bg-emerald-50 text-emerald-700 text-xs rounded border border-emerald-100 flex items-start gap-2" style="display: none;">
                                <i class="material-icons text-sm mt-0.5">info</i>
                                <span>
                                    Pembayaran melebihi tagihan. Kelebihan dana sebesar 
                                    <strong x-text="formatRupiah(overpayment)"></strong> 
                                    akan otomatis disimpan sebagai <strong>Deposit Supplier</strong>.
                                </span>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Panel Kanan: Form Detail --}}
                <div class="card p-5 h-fit">
                    <form action="{{ route('admin.purchase-orders.payments.store', $purchaseOrder->po_id) }}" method="POST" enctype="multipart/form-data">
                        @csrf
                        
                        {{-- Hidden Inputs untuk Logic Backend --}}
                        <input type="hidden" name="use_debit_balance" :value="useLedger ? '1' : '0'">
                        <input type="hidden" name="amount" :value="manualAmount"> {{-- Nominal uang manual --}}
                        
                        <div class="space-y-4">
                            <div>
                                <label class="form-label text-xs">Tanggal Bayar</label>
                                <input type="date" name="payment_date" value="{{ date('Y-m-d') }}" class="form-input text-sm">
                            </div>

                            {{-- Dynamic Fields berdasarkan Metode --}}
                            <div x-show="manualAmount > 0" x-transition>
                                <div class="mb-4">
                                    <label class="form-label text-xs label-required">Metode Pembayaran</label>
                                    <select name="payment_method_id" class="tom-select w-full" id="payment_method_select">
                                        <option value="">Pilih Metode...</option>
                                        @foreach($paymentMethods as $pm)
                                            <option value="{{ $pm->payment_method_id }}">{{ $pm->name }}</option>
                                        @endforeach
                                    </select>
                                </div>

                                <div class="mb-4">
                                    <label class="form-label text-xs label-required">Sumber Dana (Akun Kas/Bank)</label>
                                    <select name="company_bank_account_id" class="tom-select w-full" id="bank_account_select">
                                        <option value="">Pilih Akun Kas...</option>
                                        @foreach($companyBankAccounts as $bank)
                                            <option value="{{ $bank->company_bank_account_id }}">{{ $bank->bank_name }} - {{ $bank->account_name }}</option>
                                        @endforeach
                                    </select>
                                </div>

                                {{-- Dynamic Proof & Ref (Modern Dropzone Style) --}}
                                <div class="grid grid-cols-1 gap-4 p-3 bg-slate-50 dark:bg-slate-900/50 rounded border border-slate-100 dark:border-slate-700">
                                    <div x-show="needsReference">
                                        <label class="form-label text-xs" :class="{'label-required': isReferenceRequired}">No. Referensi / Transaksi</label>
                                        <input type="text" name="reference_number" class="form-input text-xs" placeholder="Contoh: TRF-88219" :required="isReferenceRequired">
                                    </div>
                                    
                                    <div x-show="needsProof" x-data="{ fileName: null }" class="w-full">
                                        <label class="block mb-2 text-xs font-bold text-slate-500 uppercase tracking-wide">
                                            Bukti Transfer (Image) <span x-show="isProofRequired" class="text-rose-500">*</span>
                                        </label>
                                        
                                        <div class="flex items-center justify-center w-full">
                                            <label for="proof_of_payment" class="flex flex-col items-center justify-center w-full h-32 border-2 border-slate-300 border-dashed rounded-2xl cursor-pointer bg-slate-50 hover:bg-indigo-50/50 hover:border-indigo-400 dark:bg-slate-800/50 dark:border-slate-600 dark:hover:border-indigo-500 transition-all duration-300 group">
                                                
                                                {{-- Default View --}}
                                                <div class="flex flex-col items-center justify-center pt-5 pb-6 px-4" x-show="!fileName">
                                                    <i class="material-icons text-3xl mb-2 text-slate-400 group-hover:text-indigo-500 transition-colors">cloud_upload</i>
                                                    <p class="text-xs text-slate-500 dark:text-slate-400 text-center">
                                                        <span class="font-semibold">Klik upload</span> atau drag & drop
                                                    </p>
                                                </div>

                                                {{-- File Selected View --}}
                                                <div class="hidden flex-col items-center justify-center pt-5 pb-6" x-show="fileName" :class="{ 'flex': fileName, 'hidden': !fileName }">
                                                    <div class="w-8 h-8 rounded-full bg-emerald-100 text-emerald-600 flex items-center justify-center mb-2">
                                                        <i class="material-icons text-lg">check</i>
                                                    </div>
                                                    <p class="text-xs font-bold text-slate-700 dark:text-slate-200 text-center px-4 break-all" x-text="fileName"></p>
                                                    <p class="text-[10px] text-slate-400 mt-1 group-hover:text-indigo-500">Ganti file</p>
                                                </div>

                                                <input id="proof_of_payment" name="proof_of_payment" type="file" class="hidden" accept="image/*"
                                                       @change="fileName = $event.target.files[0] ? $event.target.files[0].name : null"
                                                       :required="isProofRequired" />
                                            </label>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div>
                                <label class="form-label text-xs">Catatan (Opsional)</label>
                                <textarea name="notes" rows="2" class="form-textarea text-xs" placeholder="Catatan internal..."></textarea>
                            </div>

                            <button type="submit" class="btn btn-primary w-full shadow-lg" :disabled="!isValid">
                                <i class="material-icons text-[16px] mr-2">check_circle</i> 
                                <span x-text="manualAmount > 0 ? 'Proses Pembayaran' : 'Proses Potong Saldo'"></span>
                            </button>
                        </div>
                    </form>
                </div>
            </div>
            @endif
        </div>

        {{-- TAB 2: RETUR & ADJUSTMENT --}}
        <div x-show="activeTab === 'returns'" style="display: none;" class="space-y-8 animate-enter">
            
            {{-- Log Retur --}}
            <div class="card overflow-hidden">
                <div class="px-5 py-3 bg-rose-50 dark:bg-rose-900/20 border-b border-rose-100 dark:border-rose-800 flex justify-between items-center">
                    <h3 class="font-bold text-sm text-rose-700 dark:text-rose-300">Riwayat Retur Pembelian</h3>
                    @if($purchaseOrder->status === 'completed')
                        <a href="{{ route('admin.purchase-returns.create', ['purchase_order_id' => $purchaseOrder->po_id]) }}" class="btn btn-sm btn-danger border-none shadow-sm">
                            <i class="material-icons text-[16px] mr-1">add</i> Buat Retur
                        </a>
                    @endif
                </div>
                <table class="w-full text-sm text-left">
                    <thead class="text-xs text-slate-500 uppercase bg-white dark:bg-slate-800">
                        <tr>
                            <th class="px-5 py-3">No. Retur</th>
                            <th class="px-5 py-3">Tanggal</th>
                            <th class="px-5 py-3">Tipe Penanganan</th>
                            <th class="px-5 py-3 text-right">Nilai Retur</th>
                            <th class="px-5 py-3 text-center">Opsi</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 dark:divide-slate-700">
                        @forelse($purchaseOrder->returns as $return)
                            <tr class="hover:bg-slate-50 dark:hover:bg-slate-800/50">
                                <td class="px-5 py-3 font-medium text-indigo-600">
                                    <a href="{{ route('admin.purchase-returns.show', $return->return_id) }}" class="hover:underline">{{ $return->return_number }}</a>
                                </td>
                                <td class="px-5 py-3">{{ $return->return_date->format('d/m/Y') }}</td>
                                <td class="px-5 py-3">
                                    <span class="badge {{ $return->return_handling_type == 'deduct_invoice' ? 'bg-indigo-100 text-indigo-700' : 'bg-amber-100 text-amber-700' }}">
                                        {{ $return->return_handling_type == 'deduct_invoice' ? 'Potong Tagihan' : 'Simpan Deposit' }}
                                    </span>
                                </td>
                                <td class="px-5 py-3 text-right font-mono font-bold text-rose-600">
                                    {{ number_format($return->total_amount, 0, ',', '.') }}
                                </td>
                                <td class="px-5 py-3 text-center">
                                    <a href="{{ route('admin.purchase-returns.show', $return->return_id) }}" class="text-slate-400 hover:text-indigo-600">
                                        <i class="material-icons text-[18px]">visibility</i>
                                    </a>
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="5" class="text-center py-6 text-slate-400 italic">Belum ada riwayat retur.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            {{-- Log Adjustment --}}
            <div class="card overflow-hidden">
                <div class="px-5 py-3 bg-amber-50 dark:bg-amber-900/20 border-b border-amber-100 dark:border-amber-800 flex justify-between items-center">
                    <h3 class="font-bold text-sm text-amber-700 dark:text-amber-300">Riwayat Adjustment (Koreksi)</h3>
                    @if($purchaseOrder->status === 'completed')
                         {{-- Tombol Adjustment Baru (Mengarah ke Halaman Pilihan) --}}
                         <a href="{{ route('admin.purchase-order-adjustments.create', ['purchase_order_id' => $purchaseOrder->po_id]) }}" class="btn btn-sm bg-amber-100 text-amber-700 hover:bg-amber-200 border-none">
                            <i class="material-icons text-[16px] mr-1">tune</i> Adjustment Baru
                        </a>
                    @endif
                </div>
                <table class="w-full text-sm text-left">
                    <thead class="text-xs text-slate-500 uppercase bg-white dark:bg-slate-800">
                        <tr>
                            <th class="px-5 py-3">Tanggal</th>
                            <th class="px-5 py-3">Jenis</th>
                            <th class="px-5 py-3">Alasan</th>
                            <th class="px-5 py-3 text-right">Nominal</th>
                            <th class="px-5 py-3 text-center">Opsi</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 dark:divide-slate-700">
                        @forelse($purchaseOrder->adjustments as $adj)
                            <tr class="hover:bg-slate-50 dark:hover:bg-slate-800/50">
                                <td class="px-5 py-3">{{ $adj->adjustment_date->format('d/m/Y') }}</td>
                                <td class="px-5 py-3">
                                    <span class="badge {{ $adj->type == 'credit_note' ? 'bg-emerald-100 text-emerald-700' : 'bg-rose-100 text-rose-700' }}">
                                        {{ $adj->type == 'credit_note' ? 'Credit Note (Potong Hutang)' : 'Debit Note (Tambah Hutang)' }}
                                    </span>
                                </td>
                                <td class="px-5 py-3 text-xs text-slate-500 max-w-xs truncate" title="{{ $adj->reason }}">{{ $adj->reason }}</td>
                                <td class="px-5 py-3 text-right font-mono font-bold">
                                    {{ number_format($adj->amount, 0, ',', '.') }}
                                </td>
                                <td class="px-5 py-3 text-center">
                                    {{-- FORM DELETE DENGAN ID UNIK --}}
                                    <form action="{{ route('admin.purchase-order-adjustments.destroy', $adj->adjustment_id) }}" 
                                          method="POST" 
                                          id="form-reverse-adj-{{ $adj->adjustment_id }}">
                                        @csrf
                                        @method('DELETE')
                                        
                                        {{-- TOMBOL TRIGGER CONFIRM --}}
                                        <button type="button" 
                                                @click="confirmReverseAdjustment('form-reverse-adj-{{ $adj->adjustment_id }}')"
                                                class="text-slate-400 hover:text-rose-500 transition-colors tooltip" 
                                                title="Batalkan / Reversal">
                                            <i class="material-icons text-[18px]">undo</i>
                                        </button>
                                    </form>
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="5" class="text-center py-6 text-slate-400 italic">Belum ada riwayat adjustment.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        {{-- TAB 3: CATATAN --}}
        @if($purchaseOrder->notes)
        <div x-show="activeTab === 'notes'" style="display: none;" class="animate-enter">
            <div class="card p-6 bg-yellow-50/50 dark:bg-yellow-900/10 border border-yellow-100 dark:border-yellow-900/30">
                <h4 class="font-bold text-slate-700 dark:text-yellow-400 mb-3 flex items-center gap-2">
                    <i class="material-icons text-amber-500">sticky_note_2</i> Catatan Pesanan
                </h4>
                <p class="text-slate-600 dark:text-slate-300 text-sm whitespace-pre-line leading-relaxed">{{ $purchaseOrder->notes }}</p>
            </div>
        </div>
        @endif
    </div>
</div>

@push('scripts')
<script>
    document.addEventListener('alpine:init', () => {
        
        // 1. Logic Halaman Utama
Alpine.data('purchaseOrderShow', () => ({
            // Fungsi Hapus Pembayaran
            confirmDelete(formId) {
                window.confirmDialog({
                    title: 'Hapus Pembayaran?',
                    text: 'Data pembayaran akan dihapus dan jurnal akuntansi akan di-rollback (dibatalkan).',
                    icon: 'warning',
                    confirmText: 'Ya, Hapus',
                    confirmColor: 'danger'
                }).then((result) => {
                    if (result.isConfirmed) {
                        document.getElementById(formId).submit();
                    }
                });
            },

            // Fungsi Konfirmasi Proses Pesanan
            confirmProcess() {
                window.confirmDialog({
                    title: 'Proses Pesanan?',
                    text: 'Status akan berubah menjadi "Ordered". Stok dalam perjalanan akan dicatat.',
                    icon: 'question',
                    confirmText: 'Ya, Proses',
                    confirmColor: 'primary'
                }).then((result) => {
                    if (result.isConfirmed) {
                        document.getElementById('form-process-order').submit();
                    }
                });
            },

            // Fungsi Konfirmasi Terima Barang
            confirmReceive() {
                window.confirmDialog({
                    title: 'Terima Barang?',
                    text: 'Pastikan fisik barang sudah diterima. Stok gudang akan bertambah dan status menjadi "Selesai".',
                    icon: 'warning',
                    confirmText: 'Ya, Terima Barang',
                    confirmColor: 'success'
                }).then((result) => {
                    if (result.isConfirmed) {
                        document.getElementById('form-receive-goods').submit();
                    }
                });
            },

            // [BARU] Fungsi Konfirmasi Reversal Adjustment
            confirmReverseAdjustment(formId) {
                window.confirmDialog({
                    title: 'Batalkan Penyesuaian?',
                    text: 'Jurnal akuntansi terkait akan dihapus (rollback) dan saldo hutang akan dikalkulasi ulang. Lanjutkan?',
                    icon: 'warning',
                    confirmText: 'Ya, Batalkan',
                    cancelText: 'Tidak',
                    confirmColor: 'danger'
                }).then((result) => {
                    if (result.isConfirmed) {
                        document.getElementById(formId).submit();
                    }
                });
            }
        }));

        // 2. Logic Form Pembayaran
        Alpine.data('paymentFormLogic', () => ({
            subTab: 'history',
            
            // Data dari Backend
            remainingBalance: {{ 
                max(0, (
                    ($purchaseOrder->grand_total + $purchaseOrder->adjustments->where('type', 'debit_note')->sum('amount')) - 
                    ($purchaseOrder->total_returned + $purchaseOrder->adjustments->where('type', 'credit_note')->sum('amount') + $purchaseOrder->amount_paid)
                )) 
            }},
            supplierBalance: {{ $purchaseOrder->supplier->balance }},
            
            // Payment Method Configs
            paymentMethods: [
                @foreach($paymentMethods as $pm)
                {
                    id: '{{ $pm->payment_method_id }}',
                    name: '{{ $pm->name }}',
                    config: '{{ $pm->internal_input_config }}', // none, proof_only, reference_only, proof_and_reference
                },
                @endforeach
            ],

            // State Form
            useLedger: false,
            manualAmount: 0,
            selectedMethodId: '',
            
            // Computed Logic Variables
            depositUsed: 0,
            manualNeeded: 0,
            overpayment: 0,

            // Dynamic Form Visibility
            needsProof: false,
            needsReference: false,
            isProofRequired: false,
            isReferenceRequired: false,

            init() {
                // A. Init AutoNumeric untuk Input Pembayaran
                const inputEl = document.getElementById('manual_payment_input');
                if (inputEl) {
                    // Gunakan window.AutoNumericGlobal jika sudah di assign di app.js, atau AutoNumeric class langsung
                    const an = new AutoNumeric(inputEl, {
                        ...window.defaultAutoNumericOptions,
                        minimumValue: '0' 
                    });
                    
                    // Listener perubahan nilai manual
                    inputEl.addEventListener('autoNumeric:rawValueModified', e => {
                        this.manualAmount = parseFloat(e.detail.newRawValue) || 0;
                        this.recalculate();
                    });
                }

                // B. Init Tom Select Metode
                const methodSelect = document.getElementById('payment_method_select');
                if (methodSelect) {
                    if (methodSelect.tomselect) methodSelect.tomselect.destroy();
                    new TomSelect(methodSelect, {
                        ...window.defaultTomSelectConfig,
                        onChange: (value) => {
                            this.selectedMethodId = value;
                            this.updateFormConfig();
                        }
                    });
                }

                // C. Init Tom Select Akun Bank
                const bankSelect = document.getElementById('bank_account_select');
                if (bankSelect) {
                    if (bankSelect.tomselect) bankSelect.tomselect.destroy();
                    new TomSelect(bankSelect, {
                        ...window.defaultTomSelectConfig
                    });
                }

                // D. Watcher untuk Checkbox Deposit
                this.$watch('useLedger', () => {
                    this.recalculate();
                });
                
                // Hitung awal
                this.recalculate();
            },

            // Fungsi Klik Tombol "LUNAS"
            setFullAmount() {
                // 1. Hitung dulu berapa sisa setelah deposit (jika dicentang)
                let depositAllocated = 0;
                if (this.useLedger && this.supplierBalance > 0) {
                    depositAllocated = Math.min(this.supplierBalance, this.remainingBalance);
                }
                
                const exactManualNeeded = this.remainingBalance - depositAllocated;
                
                // 2. Set nilai ke AutoNumeric (Visual)
                const inputEl = document.getElementById('manual_payment_input');
                if (AutoNumeric.getAutoNumericElement(inputEl)) {
                    AutoNumeric.getAutoNumericElement(inputEl).set(exactManualNeeded);
                }
                
                // 3. Set nilai ke State Alpine (Logic)
                this.manualAmount = exactManualNeeded;
                this.recalculate();
            },

            recalculate() {
                // 1. Hitung Deposit yang Akan Dipakai
                if (this.useLedger && this.supplierBalance > 0) {
                    this.depositUsed = Math.min(this.supplierBalance, this.remainingBalance);
                } else {
                    this.depositUsed = 0;
                }

                // 2. Hitung Sisa yang harus dibayar Manual (Untuk Display Info)
                let remainingAfterDeposit = Math.max(0, this.remainingBalance - this.depositUsed);
                this.manualNeeded = remainingAfterDeposit;

                // 3. Hitung Overpayment
                this.overpayment = Math.max(0, this.manualAmount - remainingAfterDeposit);
            },

            updateFormConfig() {
                if (!this.selectedMethodId) {
                    this.needsProof = false; 
                    this.needsReference = false;
                    return;
                }

                const method = this.paymentMethods.find(m => m.id == this.selectedMethodId);
                if (method) {
                    const cfg = method.config;
                    
                    this.needsProof = ['proof_only', 'proof_and_reference'].includes(cfg);
                    this.needsReference = ['reference_only', 'proof_and_reference'].includes(cfg);
                    
                    this.isProofRequired = this.needsProof;
                    this.isReferenceRequired = this.needsReference;
                }
            },

            // Validasi Tombol Submit
            get isValid() {
                const totalPay = this.depositUsed + this.manualAmount;
                if (totalPay <= 0) return false;
                
                // Jika ada pembayaran manual, metode & bank harus dipilih
                if (this.manualAmount > 0) {
                    if (!this.selectedMethodId) return false;
                    // Bank account validasi handled by required attribute di form, 
                    // tapi bisa ditambah disini jika mau disable button.
                }
                
                return true;
            },

            formatRupiah(value) {
                return new Intl.NumberFormat('id-ID', { style: 'currency', currency: 'IDR', minimumFractionDigits: 0 }).format(value);
            }
        }));
    });
</script>
@endpush
@endsection
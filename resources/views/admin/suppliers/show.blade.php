@extends('admin.layouts.app')

@section('title', 'Detail Supplier')

@section('content')
<div class="flex flex-col gap-6">

    {{-- Breadcrumb / Back Button Area --}}
    <div class="flex items-center gap-3 mb-2">
        <a href="{{ route('admin.suppliers.index') }}" class="btn-icon btn-sm btn-secondary hover:bg-slate-200 dark:hover:bg-slate-700 transition-colors">
            <i class="material-icons text-lg">arrow_back</i>
        </a>
        <div>
            <h1 class="text-xl font-bold text-slate-800 dark:text-white tracking-tight">Detail Supplier</h1>
            <p class="text-xs text-slate-500">Manajemen data dan riwayat transaksi supplier</p>
        </div>
    </div>

    <div class="grid grid-cols-1 xl:grid-cols-12 gap-6 items-start">
        
        {{-- ========================================================= --}}
        {{-- KOLOM KIRI: INFORMASI SUPPLIER (Profile Card) --}}
        {{-- ========================================================= --}}
        <div class="xl:col-span-4 flex flex-col gap-6">
            
            {{-- Card Profil Utama --}}
            <div class="card overflow-hidden relative">
                {{-- Decorative Background --}}
                <div class="h-24 bg-gradient-to-r from-slate-800 to-[#0f172a]"></div>
                
                <div class="px-6 pb-6 relative">
                    {{-- Avatar / Initial --}}
                    <div class="absolute -top-10 left-6">
                        <div class="w-20 h-20 rounded-2xl bg-white dark:bg-slate-800 p-1 shadow-lg">
                            <div class="w-full h-full bg-indigo-50 dark:bg-indigo-900/30 rounded-xl flex items-center justify-center text-indigo-600 dark:text-indigo-400 text-3xl font-bold border border-indigo-100 dark:border-indigo-800">
                                {{ substr($supplier->supplier_name, 0, 1) }}
                            </div>
                        </div>
                    </div>

                    {{-- Actions (Top Right) --}}
                    <div class="flex justify-end pt-3 gap-2">
                        <a href="{{ route('admin.suppliers.edit', $supplier->supplier_id) }}" class="p-2 rounded-lg text-slate-400 hover:text-indigo-600 hover:bg-slate-50 transition-colors" title="Edit Data">
                            <i class="material-icons text-xl">edit</i>
                        </a>
                        <form action="{{ route('admin.suppliers.destroy', $supplier->supplier_id) }}" method="POST" onsubmit="return confirm('Pindahkan ke sampah?');">
                            @csrf @method('DELETE')
                            <button type="submit" class="p-2 rounded-lg text-slate-400 hover:text-red-600 hover:bg-red-50 transition-colors" title="Hapus">
                                <i class="material-icons text-xl">delete</i>
                            </button>
                        </form>
                    </div>

                    {{-- Nama & Status --}}
                    <div class="mt-2">
                        <h2 class="text-xl font-bold text-slate-800 dark:text-white leading-tight">{{ $supplier->supplier_name }}</h2>
                        <div class="flex items-center gap-2 mt-1 text-sm text-slate-500">
                            <i class="material-icons text-base">badge</i>
                            <span>{{ $supplier->person_in_charge ?? 'Tidak ada PIC' }}</span>
                        </div>
                    </div>

                    <div class="border-t border-slate-100 dark:border-slate-700/50 my-5"></div>

                    {{-- Contact Info --}}
                    <div class="space-y-4">
                        {{-- WhatsApp Button --}}
                        @if($supplier->phone_number)
                        <a href="{{ $supplier->wa_link }}" target="_blank" class="flex items-center justify-center gap-2 w-full py-2.5 rounded-xl bg-emerald-500 hover:bg-emerald-600 text-white font-bold text-sm shadow-emerald-200 shadow-lg transition-all hover:-translate-y-0.5">
                            <i class="material-icons text-lg">chat</i>
                            Chat WhatsApp
                        </a>
                        @endif

                        <div class="space-y-3">
                            <div class="flex items-start gap-3">
                                <div class="w-8 h-8 rounded-lg bg-slate-50 dark:bg-slate-700 flex items-center justify-center text-slate-400 shrink-0">
                                    <i class="material-icons text-sm">call</i>
                                </div>
                                <div>
                                    <p class="text-[10px] uppercase font-bold text-slate-400">Telepon</p>
                                    <p class="text-sm font-medium text-slate-700 dark:text-slate-200">{{ $supplier->phone_number ?? '-' }}</p>
                                </div>
                            </div>
                            
                            <div class="flex items-start gap-3">
                                <div class="w-8 h-8 rounded-lg bg-slate-50 dark:bg-slate-700 flex items-center justify-center text-slate-400 shrink-0">
                                    <i class="material-icons text-sm">location_on</i>
                                </div>
                                <div>
                                    <p class="text-[10px] uppercase font-bold text-slate-400">Alamat</p>
                                    <p class="text-sm font-medium text-slate-700 dark:text-slate-200 leading-relaxed">{{ $supplier->address ?? '-' }}</p>
                                </div>
                            </div>

                            <div class="flex items-start gap-3">
                                <div class="w-8 h-8 rounded-lg bg-slate-50 dark:bg-slate-700 flex items-center justify-center text-slate-400 shrink-0">
                                    <i class="material-icons text-sm">assignment</i>
                                </div>
                                <div>
                                    <p class="text-[10px] uppercase font-bold text-slate-400">NPWP</p>
                                    <p class="text-sm font-mono font-medium text-slate-700 dark:text-slate-200">{{ $supplier->npwp ?? '-' }}</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Card Informasi Bank --}}
            <div class="card">
                <div class="card-header border-b border-slate-100 dark:border-slate-700/50 py-3">
                    <h3 class="font-bold text-sm text-slate-800 dark:text-white flex items-center gap-2">
                        <i class="material-icons text-slate-400 text-base">account_balance</i> Informasi Bank
                    </h3>
                </div>
                <div class="card-body p-5">
                    @if($supplier->bank_name)
                        <div class="bg-indigo-50 dark:bg-indigo-900/20 rounded-xl p-4 border border-indigo-100 dark:border-indigo-800">
                            <p class="text-xs text-indigo-500 uppercase font-bold mb-1">{{ $supplier->bank_name }}</p>
                            <p class="text-lg font-mono font-bold text-indigo-700 dark:text-indigo-300 tracking-wide">{{ $supplier->account_number }}</p>
                            <p class="text-xs text-indigo-400 mt-1">A.N. {{ $supplier->supplier_name }}</p>
                        </div>
                    @else
                        <div class="text-center py-4">
                            <div class="inline-flex p-3 rounded-full bg-slate-50 dark:bg-slate-800 text-slate-300 mb-2">
                                <i class="material-icons">credit_card_off</i>
                            </div>
                            <p class="text-xs text-slate-400">Belum ada data rekening.</p>
                        </div>
                    @endif
                </div>
            </div>

        </div>

        {{-- ========================================================= --}}
        {{-- KOLOM KANAN: DASHBOARD OPERASIONAL (Stats & Tabs) --}}
        {{-- ========================================================= --}}
        <div class="xl:col-span-8 flex flex-col gap-6">

            {{-- 1. Statistik Utama (Cards Row) --}}
            @php
                $totalHutang = $supplier->purchaseOrders()
                    ->where('status', '!=', 'cancelled')
                    ->get()
                    ->sum('remaining_balance');
            @endphp

            <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                {{-- Sisa Hutang --}}
                <div class="card p-5 relative overflow-hidden group hover:border-rose-300 transition-colors">
                    <div class="absolute right-0 top-0 p-3 opacity-10 group-hover:opacity-20 transition-opacity">
                        <i class="material-icons text-6xl text-rose-600">money_off</i>
                    </div>
                    <div>
                        <p class="text-xs font-bold uppercase text-slate-400 mb-1">Sisa Hutang</p>
                        <h3 class="text-2xl font-black text-rose-600">Rp {{ number_format($totalHutang, 0, ',', '.') }}</h3>
                        <p class="text-[10px] text-rose-400 mt-1 font-medium">Harus dibayar</p>
                    </div>
                </div>

                {{-- Saldo Deposit --}}
                <div class="card p-5 relative overflow-hidden group hover:border-emerald-300 transition-colors">
                    <div class="absolute right-0 top-0 p-3 opacity-10 group-hover:opacity-20 transition-opacity">
                        <i class="material-icons text-6xl text-emerald-600">savings</i>
                    </div>
                    <div>
                        <p class="text-xs font-bold uppercase text-slate-400 mb-1">Saldo Deposit</p>
                        <h3 class="text-2xl font-black text-emerald-600">Rp {{ number_format($supplier->balance, 0, ',', '.') }}</h3>
                        @if($supplier->pending_balance > 0)
                            <p class="text-[10px] text-amber-500 mt-1 font-bold bg-amber-50 inline-block px-1.5 rounded">Pending: Rp {{ number_format($supplier->pending_balance, 0, ',', '.') }}</p>
                        @else
                            <p class="text-[10px] text-emerald-400 mt-1 font-medium">Siap digunakan</p>
                        @endif
                    </div>
                </div>

                {{-- Total Pesanan --}}
                <div class="card p-5 relative overflow-hidden group hover:border-blue-300 transition-colors">
                    <div class="absolute right-0 top-0 p-3 opacity-10 group-hover:opacity-20 transition-opacity">
                        <i class="material-icons text-6xl text-blue-600">shopping_cart</i>
                    </div>
                    <div>
                        <p class="text-xs font-bold uppercase text-slate-400 mb-1">Total Pesanan</p>
                        <h3 class="text-2xl font-black text-slate-700 dark:text-white">{{ $supplier->purchaseOrders()->count() }} <span class="text-sm font-normal text-slate-400">PO</span></h3>
                        <p class="text-[10px] text-slate-400 mt-1 font-medium">Seumur hidup</p>
                    </div>
                </div>
            </div>

            {{-- 2. Tabulasi Data (AlpineJS) --}}
            <div class="card" x-data="{ activeTab: 'po' }">
                
                {{-- Tab Header --}}
                <div class="flex overflow-x-auto border-b border-slate-100 dark:border-slate-700 px-2">
                    @php
                        $tabs = [
                            'po' => ['label' => 'Riwayat Pesanan (PO)', 'icon' => 'receipt_long'],
                            'products' => ['label' => 'Produk', 'icon' => 'inventory_2'],
                            'ledger' => ['label' => 'Mutasi Deposit', 'icon' => 'history'],
                            'returns' => ['label' => 'Retur & Koreksi', 'icon' => 'assignment_return'],
                        ];
                    @endphp

                    @foreach($tabs as $key => $tab)
                    <button 
                        @click="activeTab = '{{ $key }}'"
                        :class="activeTab === '{{ $key }}' ? 'text-indigo-600 border-indigo-500 bg-indigo-50/50 dark:bg-indigo-900/20' : 'text-slate-500 border-transparent hover:text-slate-700 hover:bg-slate-50 dark:hover:bg-slate-800'"
                        class="flex items-center gap-2 px-5 py-3.5 text-sm font-bold border-b-2 transition-all whitespace-nowrap rounded-t-lg mx-1 mt-1"
                    >
                        <i class="material-icons text-[18px]">{{ $tab['icon'] }}</i>
                        {{ $tab['label'] }}
                    </button>
                    @endforeach
                </div>

                {{-- Tab Contents --}}
                <div class="p-0">

                    {{-- TAB 1: PURCHASE ORDERS --}}
                    <div x-show="activeTab === 'po'" class="animate-enter" style="display: none;">
                        <div class="table-container border-0 shadow-none rounded-none">
                            <table class="table-modern">
                                <thead class="bg-slate-50 dark:bg-slate-800">
                                    <tr>
                                        <th>No. PO</th>
                                        <th>Tanggal</th>
                                        <th class="text-right">Total</th>
                                        <th class="text-center">Status</th>
                                        <th class="text-center">Pembayaran</th>
                                        <th class="text-right">Aksi</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse($supplier->purchaseOrders()->latest('order_date')->limit(20)->get() as $po)
                                    <tr>
                                        <td>
                                            <span class="font-bold text-slate-700 dark:text-slate-200">{{ $po->po_number }}</span>
                                            @if($po->supplier_invoice_number)
                                                <div class="text-[10px] text-slate-500">Ref: {{ $po->supplier_invoice_number }}</div>
                                            @endif
                                        </td>
                                        <td>{{ $po->order_date->format('d/m/Y') }}</td>
                                        <td class="text-right font-bold">Rp {{ number_format($po->grand_total, 0, ',', '.') }}</td>
                                        <td class="text-center">
                                            @if($po->status == 'draft') <span class="badge badge-primary">Draft</span>
                                            @elseif($po->status == 'ordered') <span class="badge badge-warning">Dipesan</span>
                                            @elseif($po->status == 'completed') <span class="badge badge-success">Diterima</span>
                                            @else <span class="badge badge-danger">Batal</span> @endif
                                        </td>
                                        <td class="text-center">
                                            @if($po->payment_status == 'paid') 
                                                <span class="badge badge-success"><i class="material-icons text-[10px] mr-1">check</i>Lunas</span>
                                            @elseif($po->payment_status == 'partially_paid') 
                                                <span class="badge badge-warning">Sebagian</span>
                                            @else 
                                                <span class="badge badge-danger">Belum</span> 
                                            @endif
                                        </td>
                                        <td class="text-right">
                                            <a href="{{ route('admin.purchase-orders.show', $po->po_id) }}" class="btn-action btn-action-view" title="Lihat Detail">
                                                <i class="material-icons">visibility</i>
                                            </a>
                                        </td>
                                    </tr>
                                    @empty
                                    <tr>
                                        <td colspan="6" class="p-8 text-center text-slate-400">
                                            <div class="flex flex-col items-center gap-2">
                                                <i class="material-icons text-4xl text-slate-300">remove_shopping_cart</i>
                                                <p class="text-sm">Belum ada riwayat pesanan.</p>
                                            </div>
                                        </td>
                                    </tr>
                                    @endforelse
                                </tbody>
                            </table>
                            @if($supplier->purchaseOrders()->count() > 20)
                            <div class="p-3 text-center border-t border-slate-100 dark:border-slate-700 bg-slate-50 dark:bg-slate-800">
                                <a href="{{ route('admin.purchase-orders.index', ['supplier_id' => $supplier->supplier_id]) }}" class="text-xs font-bold text-indigo-600 hover:underline">
                                    Lihat Semua Riwayat PO &rarr;
                                </a>
                            </div>
                            @endif
                        </div>
                    </div>

                    {{-- TAB 2: PRODUK --}}
                    <div x-show="activeTab === 'products'" class="animate-enter" style="display: none;">
                        <div class="table-container border-0 shadow-none rounded-none">
                            <table class="table-modern">
                                <thead class="bg-slate-50 dark:bg-slate-800">
                                    <tr>
                                        <th>Produk</th>
                                        <th>Kategori</th>
                                        <th class="text-right">Harga Beli</th>
                                        <th class="text-right">Stok</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse($supplier->products as $product)
                                    <tr>
                                        <td>
                                            <div class="flex items-center gap-3">
                                                <div class="w-8 h-8 rounded bg-slate-100 dark:bg-slate-700 flex items-center justify-center shrink-0 overflow-hidden">
                                                    @if($product->image_path)
                                                        <img src="{{ asset('storage/' . $product->image_path) }}" class="w-full h-full object-cover">
                                                    @else
                                                        <span class="text-xs font-bold text-slate-400">{{ substr($product->product_name, 0, 1) }}</span>
                                                    @endif
                                                </div>
                                                <div>
                                                    <div class="font-bold text-slate-700 dark:text-slate-200 text-sm">{{ $product->product_name }}</div>
                                                    <div class="text-[10px] text-slate-400">{{ $product->product_code }}</div>
                                                </div>
                                            </div>
                                        </td>
                                        <td class="text-sm">{{ $product->category->name ?? '-' }}</td>
                                        <td class="text-right font-mono text-sm">Rp {{ number_format($product->purchase_price, 0, ',', '.') }}</td>
                                        <td class="text-right">
                                            <span class="font-bold {{ $product->stock_quantity <= 5 ? 'text-rose-500' : 'text-emerald-600' }}">
                                                {{ number_format($product->stock_quantity, 0, ',', '.') }}
                                            </span>
                                            <span class="text-xs text-slate-400 ml-1">{{ $product->unit->name ?? '' }}</span>
                                        </td>
                                    </tr>
                                    @empty
                                    <tr>
                                        <td colspan="4" class="p-8 text-center text-slate-400">
                                            <div class="flex flex-col items-center gap-2">
                                                <i class="material-icons text-4xl text-slate-300">inventory_2</i>
                                                <p class="text-sm">Supplier ini belum memiliki produk.</p>
                                            </div>
                                        </td>
                                    </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                    </div>

                    {{-- TAB 3: LEDGER (Mutasi Deposit) --}}
                    <div x-show="activeTab === 'ledger'" class="animate-enter" style="display: none;">
                        <div class="table-container border-0 shadow-none rounded-none">
                            <table class="table-modern">
                                <thead class="bg-slate-50 dark:bg-slate-800">
                                    <tr>
                                        <th>Tanggal</th>
                                        <th>Keterangan</th>
                                        <th class="text-right">Masuk (CR)</th>
                                        <th class="text-right">Keluar (DR)</th>
                                        <th class="text-center">Status</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse($ledgers as $ledger)
                                    <tr>
                                        <td class="text-sm text-slate-500">{{ $ledger->transaction_date->format('d M Y') }}</td>
                                        <td>
                                            <div class="text-sm font-medium text-slate-700 dark:text-slate-200">{{ $ledger->description }}</div>
                                            <div class="text-[10px] text-slate-400 font-mono mt-0.5">Ref: {{ $ledger->reference_type }} #{{ $ledger->reference_id }}</div>
                                        </td>
                                        <td class="text-right font-bold text-emerald-600">
                                            {{ $ledger->type == 'credit' ? 'Rp '.number_format($ledger->amount, 0, ',', '.') : '-' }}
                                        </td>
                                        <td class="text-right font-bold text-rose-500">
                                            {{ $ledger->type == 'debit' ? 'Rp '.number_format(abs($ledger->amount), 0, ',', '.') : '-' }}
                                        </td>
                                        <td class="text-center">
                                            @if($ledger->status == 'available') 
                                                <span class="text-[10px] font-bold text-emerald-600 bg-emerald-50 px-2 py-0.5 rounded">Berhasil</span>
                                            @else 
                                                <span class="text-[10px] font-bold text-amber-500 bg-amber-50 px-2 py-0.5 rounded">Pending</span> 
                                            @endif
                                        </td>
                                    </tr>
                                    @empty
                                    <tr>
                                        <td colspan="5" class="p-8 text-center text-slate-400">
                                            <div class="flex flex-col items-center gap-2">
                                                <i class="material-icons text-4xl text-slate-300">history_edu</i>
                                                <p class="text-sm">Belum ada riwayat mutasi deposit.</p>
                                            </div>
                                        </td>
                                    </tr>
                                    @endforelse
                                </tbody>
                            </table>
                            
                            @if($ledgers instanceof \Illuminate\Pagination\LengthAwarePaginator)
                                <div class="p-4 border-t border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-800">
                                    {{ $ledgers->appends(['tab' => 'ledger'])->links('vendor.pagination.admin') }}
                                </div>
                            @endif
                        </div>
                    </div>

                    {{-- TAB 4: RETUR & ADJUSTMENTS --}}
                    <div x-show="activeTab === 'returns'" class="animate-enter" style="display: none;">
                        <div class="p-4 space-y-6">
                            
                            {{-- Tabel Retur --}}
                            <div>
                                <h4 class="text-xs font-bold uppercase text-slate-500 mb-3 flex items-center gap-2">
                                    <i class="material-icons text-sm">assignment_return</i> Riwayat Retur Pembelian
                                </h4>
                                <div class="table-container bg-white">
                                    <table class="table-modern">
                                        <thead>
                                            <tr>
                                                <th>No. Retur</th>
                                                <th>Tgl Retur</th>
                                                <th>Asal PO</th>
                                                <th>Jenis</th>
                                                <th class="text-right">Nilai Retur</th>
                                                <th></th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @php
                                                $returns = \App\Models\PurchaseReturn::where('supplier_id', $supplier->supplier_id)->latest('return_date')->limit(10)->get();
                                            @endphp
                                            @forelse($returns as $ret)
                                            <tr>
                                                <td class="font-bold text-slate-700">{{ $ret->return_number }}</td>
                                                <td>{{ $ret->return_date->format('d/m/Y') }}</td>
                                                <td><a href="{{ route('admin.purchase-orders.show', $ret->purchase_order_id) }}" class="text-indigo-600 hover:underline font-mono text-xs">{{ $ret->purchaseOrder->po_number ?? '-' }}</a></td>
                                                <td>
                                                    @if($ret->return_handling_type == 'deduct_invoice') <span class="badge badge-info">Potong Tagihan</span>
                                                    @else <span class="badge badge-success">Simpan Deposit</span> @endif
                                                </td>
                                                <td class="text-right font-bold text-slate-700">Rp {{ number_format($ret->total_amount, 0, ',', '.') }}</td>
                                                <td class="text-right">
                                                    <a href="{{ route('admin.purchase-returns.show', $ret->return_id) }}" class="btn-action btn-action-view"><i class="material-icons">visibility</i></a>
                                                </td>
                                            </tr>
                                            @empty
                                            <tr><td colspan="6" class="text-center py-4 text-slate-400 italic text-xs">Belum ada data retur.</td></tr>
                                            @endforelse
                                        </tbody>
                                    </table>
                                </div>
                            </div>

                            {{-- Tabel Adjustment --}}
                            <div>
                                <h4 class="text-xs font-bold uppercase text-slate-500 mb-3 flex items-center gap-2">
                                    <i class="material-icons text-sm">tune</i> Log Penyesuaian (Adjustment)
                                </h4>
                                <div class="table-container bg-white">
                                    <table class="table-modern">
                                        <thead>
                                            <tr>
                                                <th>Tanggal</th>
                                                <th>Tipe</th>
                                                <th>PO Terkait</th>
                                                <th>Alasan</th>
                                                <th class="text-right">Nominal</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @php
                                                $adjustments = \App\Models\PurchaseOrderAdjustment::whereHas('purchaseOrder', function($q) use ($supplier) {
                                                    $q->where('supplier_id', $supplier->supplier_id);
                                                })->latest('adjustment_date')->limit(10)->get();
                                            @endphp
                                            @forelse($adjustments as $adj)
                                            <tr>
                                                <td>{{ $adj->adjustment_date->format('d/m/Y') }}</td>
                                                <td>
                                                    @if($adj->type == 'debit_note') <span class="text-xs font-bold text-rose-500">Debit Note (Hutang +)</span>
                                                    @else <span class="text-xs font-bold text-emerald-500">Credit Note (Hutang -)</span> @endif
                                                </td>
                                                <td><span class="font-mono text-xs text-slate-500">{{ $adj->purchaseOrder->po_number ?? '-' }}</span></td>
                                                <td class="max-w-xs truncate text-xs">{{ $adj->reason }}</td>
                                                <td class="text-right font-bold text-slate-700">Rp {{ number_format($adj->amount, 0, ',', '.') }}</td>
                                            </tr>
                                            @empty
                                            <tr><td colspan="5" class="text-center py-4 text-slate-400 italic text-xs">Belum ada penyesuaian manual.</td></tr>
                                            @endforelse
                                        </tbody>
                                    </table>
                                </div>
                            </div>

                        </div>
                    </div>

                </div>
            </div>

        </div>
    </div>
</div>
@endsection
@extends('admin.layouts.app')

@section('title', 'Detail Supplier: ' . $supplier->supplier_name)

@section('content')
<div x-data="{ activeTab: 'overview' }" class="flex flex-col gap-6">

    {{-- HEADER & ACTIONS --}}
    <div class="flex flex-col lg:flex-row lg:items-center justify-between gap-4">
        <div>
            <div class="flex items-center gap-3">
                <h2 class="page-title text-3xl">{{ $supplier->supplier_name }}</h2>
                @if($supplier->trashed())
                    <span class="badge badge-danger">Diarsipkan</span>
                @else
                    <span class="badge badge-success">Aktif</span>
                @endif
            </div>
            <p class="text-slate-500 text-sm mt-1">Ditambahkan sejak {{ $supplier->created_at->format('d M Y') }}</p>
        </div>

        <div class="flex flex-wrap gap-3">
            @if(!$supplier->trashed())
                <a href="{{ route('admin.suppliers.edit', $supplier->supplier_id) }}" class="btn btn-primary">
                    <i class="material-icons text-lg">edit</i> Edit Data
                </a>
            @endif
            <a href="{{ route('admin.suppliers.index') }}" class="btn btn-secondary">
                <i class="material-icons text-lg">arrow_back</i>
            </a>
        </div>
    </div>

    {{-- STATISTIK CARD --}}
    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
        
        {{-- Saldo Deposit --}}
        <div class="card p-5 bg-gradient-to-br from-indigo-600 to-indigo-800 text-white border-none shadow-lg">
            <p class="text-indigo-200 text-xs font-bold uppercase tracking-wider mb-1">Saldo Deposit Kita (Debit)</p>
            <h3 class="text-2xl font-bold">Rp {{ number_format($supplier->balance, 0, ',', '.') }}</h3>
            @if($supplier->pending_balance > 0)
                <div class="mt-2 text-xs bg-white/20 px-2 py-1 rounded inline-block">
                    Pending: Rp {{ number_format($supplier->pending_balance, 0, ',', '.') }}
                </div>
            @endif
        </div>

        {{-- Total PO --}}
        <div class="card p-5">
            <p class="text-slate-500 text-xs font-bold uppercase tracking-wider mb-1">Total Pesanan (PO)</p>
            <h3 class="text-2xl font-bold text-slate-700 dark:text-white">
                {{ $supplier->purchaseOrders()->count() }}
            </h3>
        </div>

        {{-- Total Hutang (Opsional - Jika mau menampilkan sisa hutang kita ke supplier) --}}
        <div class="card p-5">
            <p class="text-slate-500 text-xs font-bold uppercase tracking-wider mb-1">Sisa Hutang ke Supplier</p>
            {{-- Logic view sederhana untuk sisa hutang --}}
            @php
                $unpaidPOs = $supplier->purchaseOrders()->whereIn('payment_status', ['unpaid', 'partially_paid'])->get();
                $totalDebt = $unpaidPOs->sum(fn($po) => $po->remaining_balance);
            @endphp
            <h3 class="text-2xl font-bold text-rose-600">
                Rp {{ number_format($totalDebt, 0, ',', '.') }}
            </h3>
        </div>
    </div>

    {{-- LAYOUT UTAMA --}}
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">

        {{-- KOLOM KIRI: Profil --}}
        <div class="lg:col-span-1">
            <div class="card h-fit sticky top-24">
                <div class="card-header">
                    <h3 class="card-header-title">Informasi Supplier</h3>
                </div>
                <div class="card-body flex flex-col gap-5">
                    
                    {{-- PIC --}}
                    <div class="flex gap-3">
                        <div class="w-8 h-8 rounded-full bg-slate-100 dark:bg-slate-700 flex items-center justify-center text-slate-500">
                            <i class="material-icons text-lg">person</i>
                        </div>
                        <div>
                            <p class="text-xs text-slate-400 uppercase font-bold">PIC</p>
                            <p class="font-medium text-slate-700 dark:text-slate-200">{{ $supplier->person_in_charge ?? '-' }}</p>
                        </div>
                    </div>

                    {{-- Telepon --}}
                    <div class="flex gap-3">
                        <div class="w-8 h-8 rounded-full bg-slate-100 dark:bg-slate-700 flex items-center justify-center text-slate-500">
                            <i class="material-icons text-lg">phone</i>
                        </div>
                        <div>
                            <p class="text-xs text-slate-400 uppercase font-bold">No. Telepon</p>
                            <p class="font-medium text-slate-700 dark:text-slate-200">{{ $supplier->phone_number ?? '-' }}</p>
                        </div>
                    </div>

                    {{-- Alamat --}}
                    <div class="flex gap-3">
                        <div class="w-8 h-8 rounded-full bg-slate-100 dark:bg-slate-700 flex items-center justify-center text-slate-500">
                            <i class="material-icons text-lg">location_on</i>
                        </div>
                        <div>
                            <p class="text-xs text-slate-400 uppercase font-bold">Alamat</p>
                            <p class="font-medium text-slate-700 dark:text-slate-200 leading-relaxed">{{ $supplier->address ?? '-' }}</p>
                        </div>
                    </div>

                    <hr class="border-slate-100 dark:border-slate-700">

                    {{-- Bank --}}
                    <div class="flex gap-3">
                        <div class="w-8 h-8 rounded-full bg-slate-100 dark:bg-slate-700 flex items-center justify-center text-slate-500">
                            <i class="material-icons text-lg">account_balance</i>
                        </div>
                        <div>
                            <p class="text-xs text-slate-400 uppercase font-bold">Rekening Bank</p>
                            <p class="font-medium text-slate-700 dark:text-slate-200">{{ $supplier->bank_name ?? '-' }}</p>
                            <p class="text-sm text-slate-500">{{ $supplier->account_number ?? '-' }}</p>
                        </div>
                    </div>
                    
                    {{-- NPWP --}}
                    <div class="flex gap-3">
                        <div class="w-8 h-8 rounded-full bg-slate-100 dark:bg-slate-700 flex items-center justify-center text-slate-500">
                            <i class="material-icons text-lg">badge</i>
                        </div>
                        <div>
                            <p class="text-xs text-slate-400 uppercase font-bold">NPWP</p>
                            <p class="font-medium text-slate-700 dark:text-slate-200">{{ $supplier->npwp ?? '-' }}</p>
                        </div>
                    </div>

                </div>
            </div>
        </div>

        {{-- KOLOM KANAN: Tabs --}}
        <div class="lg:col-span-2">
            <div class="card min-h-[500px]">
                
                {{-- TAB NAVIGATION --}}
                <div class="border-b border-slate-200 dark:border-slate-700">
                    <ul class="flex flex-wrap -mb-px text-sm font-medium text-center text-slate-500">
                        <li class="mr-2">
                            <button @click="activeTab = 'overview'" 
                                    class="inline-flex items-center gap-2 p-4 border-b-2 rounded-t-lg transition-colors duration-200"
                                    :class="activeTab === 'overview' ? 'text-indigo-600 border-indigo-600 dark:text-indigo-400' : 'border-transparent hover:text-slate-600 hover:border-slate-300'">
                                <i class="material-icons text-lg">account_balance_wallet</i>
                                Riwayat Deposit
                            </button>
                        </li>
                        <li class="mr-2">
                            <button @click="activeTab = 'purchase_orders'" 
                                    class="inline-flex items-center gap-2 p-4 border-b-2 rounded-t-lg transition-colors duration-200"
                                    :class="activeTab === 'purchase_orders' ? 'text-indigo-600 border-indigo-600 dark:text-indigo-400' : 'border-transparent hover:text-slate-600 hover:border-slate-300'">
                                <i class="material-icons text-lg">shopping_cart</i>
                                Purchase Orders
                            </button>
                        </li>
                        <li class="mr-2">
                            <button @click="activeTab = 'products'" 
                                    class="inline-flex items-center gap-2 p-4 border-b-2 rounded-t-lg transition-colors duration-200"
                                    :class="activeTab === 'products' ? 'text-indigo-600 border-indigo-600 dark:text-indigo-400' : 'border-transparent hover:text-slate-600 hover:border-slate-300'">
                                <i class="material-icons text-lg">inventory_2</i>
                                Produk Disuplai
                            </button>
                        </li>
                    </ul>
                </div>

                {{-- TAB CONTENT --}}
                <div class="p-0">
                    
                    {{-- TAB 1: LEDGER --}}
                    <div x-show="activeTab === 'overview'" class="animate-enter">
                        <div class="table-container border-0 shadow-none rounded-none">
                            <table class="table-modern w-full">
                                <thead class="bg-slate-50 dark:bg-slate-800/50">
                                    <tr>
                                        <th>Tanggal</th>
                                        <th>Keterangan</th>
                                        <th class="text-center">Tipe</th>
                                        <th class="text-right">Jumlah</th>
                                        <th class="text-center">Status</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse($ledgers as $ledger)
                                        <tr>
                                            <td class="whitespace-nowrap text-slate-600">
                                                {{ \Carbon\Carbon::parse($ledger->transaction_date)->format('d/m/Y') }}
                                            </td>
                                            <td class="max-w-[200px]">
                                                <div class="truncate font-medium text-slate-700 dark:text-slate-200" title="{{ $ledger->description }}">
                                                    {{ $ledger->description }}
                                                </div>
                                                <div class="text-xs text-slate-400 mt-0.5">
                                                    Ref: {{ class_basename($ledger->reference_type) }} #{{ $ledger->reference_id }}
                                                </div>
                                            </td>
                                            <td class="text-center">
                                                {{-- 
                                                    Di SupplierLedger:
                                                    Credit = Tambah Deposit (Uang Masuk ke saldo supplier)
                                                    Debit = Kurang Deposit (Dipakai bayar PO)
                                                --}}
                                                @if($ledger->type == 'credit')
                                                    <span class="text-[10px] font-bold text-emerald-600 bg-emerald-50 border border-emerald-100 px-2 py-0.5 rounded uppercase tracking-wide">TAMBAH</span>
                                                @else
                                                    <span class="text-[10px] font-bold text-rose-600 bg-rose-50 border border-rose-100 px-2 py-0.5 rounded uppercase tracking-wide">PAKAI</span>
                                                @endif
                                            </td>
                                            <td class="text-right font-medium {{ $ledger->type == 'credit' ? 'text-emerald-600' : 'text-rose-600' }}">
                                                {{ $ledger->type == 'credit' ? '+' : '-' }} 
                                                Rp {{ number_format($ledger->amount, 0, ',', '.') }}
                                            </td>
                                            <td class="text-center">
                                                @if($ledger->status == 'available')
                                                    <span class="badge badge-success badge-pill text-[10px]">Selesai</span>
                                                @elseif($ledger->status == 'pending')
                                                    <span class="badge badge-warning badge-pill text-[10px]">Pending</span>
                                                @else
                                                    <span class="badge badge-danger badge-pill text-[10px]">{{ ucfirst($ledger->status) }}</span>
                                                @endif
                                            </td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="5" class="text-center py-12 text-slate-400">
                                                <div class="flex flex-col items-center">
                                                    <i class="material-icons text-4xl mb-2 opacity-50">account_balance_wallet</i>
                                                    <p class="text-sm">Belum ada riwayat deposit.</p>
                                                </div>
                                            </td>
                                        </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                        <div class="p-4 border-t border-slate-100 dark:border-slate-700">
                            {{ $ledgers->links('vendor.pagination.admin') }}
                        </div>
                    </div>

                    {{-- TAB 2: PURCHASE ORDERS --}}
                    <div x-show="activeTab === 'purchase_orders'" style="display: none;" class="animate-enter">
                        @php $pos = $supplier->purchaseOrders()->latest('order_date')->limit(20)->get(); @endphp
                        
                        <div class="table-container border-0 shadow-none rounded-none">
                            <table class="table-modern w-full">
                                <thead class="bg-slate-50 dark:bg-slate-800/50">
                                    <tr>
                                        <th>No. PO</th>
                                        <th>Tanggal</th>
                                        <th class="text-center">Status Bayar</th>
                                        <th class="text-right">Total</th>
                                        <th class="text-right">Sisa Hutang</th>
                                        <th class="text-center">Aksi</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse($pos as $po)
                                        <tr>
                                            <td class="font-bold text-indigo-600">{{ $po->po_number }}</td>
                                            <td>{{ $po->order_date->format('d/m/Y') }}</td>
                                            <td class="text-center">
                                                @if($po->payment_status == 'paid') <span class="badge badge-success">Lunas</span>
                                                @elseif($po->payment_status == 'unpaid') <span class="badge badge-danger">Belum Lunas</span>
                                                @else <span class="badge badge-warning">Sebagian</span>
                                                @endif
                                            </td>
                                            <td class="text-right">Rp {{ number_format($po->grand_total, 0, ',', '.') }}</td>
                                            <td class="text-right font-medium text-rose-600">Rp {{ number_format($po->remaining_balance, 0, ',', '.') }}</td>
                                            <td class="text-center">
                                                <a href="{{ route('admin.purchase-orders.show', $po->po_id) }}" class="btn-icon btn-sm btn-secondary flex items-center justify-center">
                                                    <i class="material-icons text-[16px] leading-none">visibility</i>
                                                </a>
                                            </td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="6" class="text-center py-12 text-slate-400">
                                                <div class="flex flex-col items-center">
                                                    <i class="material-icons text-4xl mb-2 opacity-50">shopping_cart</i>
                                                    <p class="text-sm">Belum ada riwayat PO.</p>
                                                </div>
                                            </td>
                                        </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                    </div>

                    {{-- TAB 3: PRODUCTS --}}
                    <div x-show="activeTab === 'products'" style="display: none;" class="animate-enter">
                        @php $products = $supplier->products()->orderBy('product_name')->limit(50)->get(); @endphp
                        
                        <div class="table-container border-0 shadow-none rounded-none">
                            <table class="table-modern w-full">
                                <thead class="bg-slate-50 dark:bg-slate-800/50">
                                    <tr>
                                        <th>Kode</th>
                                        <th>Nama Produk</th>
                                        <th class="text-right">Harga Beli</th>
                                        <th class="text-center">Stok Saat Ini</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse($products as $product)
                                        <tr>
                                            <td class="text-slate-500 text-xs">{{ $product->product_code }}</td>
                                            <td class="font-medium">{{ $product->product_name }}</td>
                                            <td class="text-right">Rp {{ number_format($product->purchase_price, 0, ',', '.') }}</td>
                                            <td class="text-center font-bold">{{ number_format($product->stock_quantity, 0, ',', '.') }}</td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="4" class="text-center py-12 text-slate-400">
                                                <div class="flex flex-col items-center">
                                                    <i class="material-icons text-4xl mb-2 opacity-50">inventory_2</i>
                                                    <p class="text-sm">Supplier ini belum memiliki produk.</p>
                                                </div>
                                            </td>
                                        </tr>
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
@endsection
@extends('admin.layouts.app')

@section('title', 'Detail Klien: ' . $client->client_name)

@section('content')
<div x-data="{ activeTab: 'overview' }" class="flex flex-col gap-6">

    {{-- HEADER & ACTIONS --}}
    <div class="flex flex-col lg:flex-row lg:items-center justify-between gap-4">
        <div>
            <div class="flex items-center gap-3">
                <h2 class="page-title text-3xl">{{ $client->client_name }}</h2>
                
                {{-- Status Badges --}}
                @if($client->is_locked)
                    <span class="badge badge-danger flex items-center gap-1">
                        <i class="material-icons text-[14px]">lock</i> Terkunci
                    </span>
                @elseif($client->is_approved)
                    <span class="badge badge-success flex items-center gap-1">
                        <i class="material-icons text-[14px]">check_circle</i> Aktif
                    </span>
                @else
                    <span class="badge badge-warning flex items-center gap-1">
                        <i class="material-icons text-[14px]">hourglass_empty</i> Menunggu Approval
                    </span>
                @endif
            </div>
            <p class="text-slate-500 text-sm mt-1">Bergabung sejak {{ $client->created_at->format('d M Y') }}</p>
        </div>

        <div class="flex flex-wrap gap-3">
            {{-- Tombol Aksi: Approve --}}
            @if(!$client->is_approved)
                <form action="{{ route('admin.clients.approve', $client->client_id) }}" method="POST">
                    @csrf @method('PATCH')
                    <button type="submit" class="btn btn-success">
                        <i class="material-icons text-lg">check_circle</i> Setujui Akun
                    </button>
                </form>
            @endif

            {{-- Tombol Aksi: Lock/Unlock --}}
            @if(!$client->trashed())
                @if($client->is_locked)
                    <form action="{{ route('admin.clients.unlock', $client->client_id) }}" method="POST">
                        @csrf @method('PATCH')
                        <button type="submit" class="btn btn-warning">
                            <i class="material-icons text-lg">lock_open</i> Buka Kunci
                        </button>
                    </form>
                @else
                    <form action="{{ route('admin.clients.lock', $client->client_id) }}" method="POST">
                        @csrf @method('PATCH')
                        <button type="submit" class="btn btn-secondary text-slate-600">
                            <i class="material-icons text-lg">lock</i> Kunci Akun
                        </button>
                    </form>
                @endif
            @endif

            <a href="{{ route('admin.clients.edit', $client->client_id) }}" class="btn btn-primary">
                <i class="material-icons text-lg">edit</i> Edit Data
            </a>
            
            <a href="{{ route('admin.clients.index') }}" class="btn btn-secondary">
                <i class="material-icons text-lg">arrow_back</i>
            </a>
        </div>
    </div>

    {{-- STATISTIK CARD GRID (4 Column) --}}
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
        
        {{-- Saldo Utama --}}
        <div class="card p-5 bg-gradient-to-br from-indigo-600 to-indigo-800 text-white border-none shadow-lg">
            <p class="text-indigo-200 text-xs font-bold uppercase tracking-wider mb-1">Saldo Deposit (Aktif)</p>
            <h3 class="text-2xl font-bold">Rp {{ number_format($client->balance, 0, ',', '.') }}</h3>
            <div class="absolute right-4 top-4 p-2 bg-white/10 rounded-lg">
                <i class="material-icons text-2xl">account_balance_wallet</i>
            </div>
        </div>

        {{-- Deposit Tertahan --}}
        <div class="card p-5 border-l-4 border-amber-500">
            <p class="text-slate-500 text-xs font-bold uppercase tracking-wider mb-1">Deposit Tertahan (Pending)</p>
            <h3 class="text-2xl font-bold text-amber-600">Rp {{ number_format($client->pending_balance, 0, ',', '.') }}</h3>
        </div>

        {{-- Total Invoice --}}
        <div class="card p-5">
            <p class="text-slate-500 text-xs font-bold uppercase tracking-wider mb-1">Total Tagihan (Invoice)</p>
            <h3 class="text-2xl font-bold text-slate-700 dark:text-white">{{ $client->salesInvoices->count() }}</h3>
            <span class="text-xs text-slate-400">Belum Lunas: {{ $client->salesInvoices->whereIn('status', ['unpaid', 'partially_paid'])->count() }}</span>
        </div>

        {{-- Total Retur --}}
        <div class="card p-5">
            <p class="text-slate-500 text-xs font-bold uppercase tracking-wider mb-1">Total Retur Barang</p>
            <h3 class="text-2xl font-bold text-slate-700 dark:text-white">
                {{-- Asumsi relasi salesReturns ada di model Client (jika belum ada, tambahkan di Model Client: public function salesReturns() { return $this->hasMany(SalesReturn::class, 'client_id'); } ) --}}
                {{ \App\Models\SalesReturn::where('client_id', $client->client_id)->count() }}
            </h3>
        </div>
    </div>

    {{-- LAYOUT UTAMA: SIDEBAR INFO + MAIN TABS --}}
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">

        {{-- KOLOM KIRI: Profil --}}
        <div class="lg:col-span-1">
            <div class="card h-fit sticky top-24">
                <div class="card-header">
                    <h3 class="card-header-title">Profil Pelanggan</h3>
                </div>
                <div class="card-body flex flex-col gap-5">
                    
                    {{-- PIC --}}
                    <div class="flex gap-3">
                        <div class="w-8 h-8 rounded-full bg-slate-100 dark:bg-slate-700 flex items-center justify-center text-slate-500">
                            <i class="material-icons text-lg">person</i>
                        </div>
                        <div>
                            <p class="text-xs text-slate-400 uppercase font-bold">Penanggung Jawab (PIC)</p>
                            <p class="font-medium text-slate-700 dark:text-slate-200">{{ $client->person_in_charge ?? '-' }}</p>
                        </div>
                    </div>

                    {{-- Email --}}
                    <div class="flex gap-3">
                        <div class="w-8 h-8 rounded-full bg-slate-100 dark:bg-slate-700 flex items-center justify-center text-slate-500">
                            <i class="material-icons text-lg">email</i>
                        </div>
                        <div>
                            <p class="text-xs text-slate-400 uppercase font-bold">Email</p>
                            <p class="font-medium text-slate-700 dark:text-slate-200 break-all">{{ $client->email ?? '-' }}</p>
                        </div>
                    </div>

                    {{-- Telepon --}}
                    <div class="flex gap-3">
                        <div class="w-8 h-8 rounded-full bg-slate-100 dark:bg-slate-700 flex items-center justify-center text-slate-500">
                            <i class="material-icons text-lg">phone</i>
                        </div>
                        <div>
                            <p class="text-xs text-slate-400 uppercase font-bold">No. Telepon</p>
                            <p class="font-medium text-slate-700 dark:text-slate-200">{{ $client->phone_number ?? '-' }}</p>
                        </div>
                    </div>

                    {{-- Alamat --}}
                    <div class="flex gap-3">
                        <div class="w-8 h-8 rounded-full bg-slate-100 dark:bg-slate-700 flex items-center justify-center text-slate-500">
                            <i class="material-icons text-lg">location_on</i>
                        </div>
                        <div>
                            <p class="text-xs text-slate-400 uppercase font-bold">Alamat</p>
                            <p class="font-medium text-slate-700 dark:text-slate-200 leading-relaxed">{{ $client->address ?? '-' }}</p>
                        </div>
                    </div>

                </div>
            </div>
        </div>

        {{-- KOLOM KANAN: Tabs & Konten --}}
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
                                Riwayat Saldo (Ledger)
                            </button>
                        </li>
                        <li class="mr-2">
                            <button @click="activeTab = 'invoices'" 
                                    class="inline-flex items-center gap-2 p-4 border-b-2 rounded-t-lg transition-colors duration-200"
                                    :class="activeTab === 'invoices' ? 'text-indigo-600 border-indigo-600 dark:text-indigo-400' : 'border-transparent hover:text-slate-600 hover:border-slate-300'">
                                <i class="material-icons text-lg">receipt</i>
                                Tagihan (Invoice)
                            </button>
                        </li>
                        <li class="mr-2">
                            <button @click="activeTab = 'returns'" 
                                    class="inline-flex items-center gap-2 p-4 border-b-2 rounded-t-lg transition-colors duration-200"
                                    :class="activeTab === 'returns' ? 'text-indigo-600 border-indigo-600 dark:text-indigo-400' : 'border-transparent hover:text-slate-600 hover:border-slate-300'">
                                <i class="material-icons text-lg">assignment_return</i>
                                Retur Penjualan
                            </button>
                        </li>
                    </ul>
                </div>

                {{-- TAB CONTENT --}}
                <div class="p-0"> {{-- Removed padding to allow table to be full width --}}
                    
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
                                            <td class="max-w-[220px]">
                                                <div class="truncate font-medium text-slate-700 dark:text-slate-200" title="{{ $ledger->description }}">
                                                    {{ $ledger->description }}
                                                </div>
                                                <div class="text-xs text-slate-400 mt-0.5">
                                                    Ref: {{ class_basename($ledger->reference_type) }} #{{ $ledger->reference_id }}
                                                </div>
                                            </td>
                                            <td class="text-center">
                                                @if($ledger->type == 'credit')
                                                    <span class="text-[10px] font-bold text-emerald-600 bg-emerald-50 border border-emerald-100 px-2 py-0.5 rounded uppercase tracking-wide">Masuk</span>
                                                @else
                                                    <span class="text-[10px] font-bold text-rose-600 bg-rose-50 border border-rose-100 px-2 py-0.5 rounded uppercase tracking-wide">Keluar</span>
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
                                                    <p class="text-sm">Belum ada riwayat transaksi deposit.</p>
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

                    {{-- TAB 2: INVOICES --}}
                    <div x-show="activeTab === 'invoices'" style="display: none;" class="animate-enter">
                        {{-- Mengambil data invoice dari relasi --}}
                        @php $invoices = $client->salesInvoices()->latest('order_date')->limit(20)->get(); @endphp
                        
                        <div class="table-container border-0 shadow-none rounded-none">
                            <table class="table-modern w-full">
                                <thead class="bg-slate-50 dark:bg-slate-800/50">
                                    <tr>
                                        <th>No. Invoice</th>
                                        <th>Tanggal</th>
                                        <th>Jatuh Tempo</th>
                                        <th class="text-center">Status</th>
                                        <th class="text-right">Total Tagihan</th>
                                        <th class="text-right">Sisa Tagihan</th>
                                        <th class="text-center">Aksi</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse($invoices as $inv)
                                        <tr>
                                            <td class="font-bold text-indigo-600">{{ $inv->invoice_number }}</td>
                                            <td>{{ \Carbon\Carbon::parse($inv->order_date)->format('d/m/Y') }}</td>
                                            <td>{{ \Carbon\Carbon::parse($inv->due_date)->format('d/m/Y') }}</td>
                                            <td class="text-center">
                                                @if($inv->status == 'paid') <span class="badge badge-success">Lunas</span>
                                                @elseif($inv->status == 'unpaid') <span class="badge badge-danger">Belum Bayar</span>
                                                @elseif($inv->status == 'partially_paid') <span class="badge badge-warning">Sebagian</span>
                                                @else <span class="badge badge-secondary">{{ ucfirst($inv->status) }}</span>
                                                @endif
                                            </td>
                                            <td class="text-right">Rp {{ number_format($inv->total_amount, 0, ',', '.') }}</td>
                                            <td class="text-right font-medium text-rose-600">Rp {{ number_format($inv->remaining_balance, 0, ',', '.') }}</td>
                                            <td class="text-center">
                                                <a href="{{ route('admin.invoices.show', $inv->invoice_id) }}" class="btn-icon btn-sm btn-secondary flex items-center justify-center">
                                                    <i class="material-icons text-[16px] leading-none">visibility</i>
                                                </a>
                                            </td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="7" class="text-center py-12 text-slate-400">
                                                <div class="flex flex-col items-center">
                                                    <i class="material-icons text-4xl mb-2 opacity-50">receipt_long</i>
                                                    <p class="text-sm">Belum ada riwayat tagihan.</p>
                                                </div>
                                            </td>
                                        </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                    </div>

                    {{-- TAB 3: RETURNS --}}
                    <div x-show="activeTab === 'returns'" style="display: none;" class="animate-enter">
                        @php 
                            // Pastikan model SalesReturn di-import atau gunakan full namespace
                            $returns = \App\Models\SalesReturn::where('client_id', $client->client_id)->latest('return_date')->limit(20)->get(); 
                        @endphp

                        <div class="table-container border-0 shadow-none rounded-none">
                            <table class="table-modern w-full">
                                <thead class="bg-slate-50 dark:bg-slate-800/50">
                                    <tr>
                                        <th>No. Retur</th>
                                        <th>Tanggal</th>
                                        <th>Asal Invoice</th>
                                        <th class="text-center">Tipe</th>
                                        <th class="text-right">Total Retur</th>
                                        <th class="text-center">Aksi</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse($returns as $ret)
                                        <tr>
                                            <td class="font-bold text-slate-700 dark:text-white">{{ $ret->return_number }}</td>
                                            <td>{{ \Carbon\Carbon::parse($ret->return_date)->format('d/m/Y') }}</td>
                                            <td>
                                                <a href="{{ route('admin.invoices.show', $ret->sales_invoice_id) }}" class="text-indigo-600 hover:underline text-xs">
                                                    {{ $ret->salesInvoice->invoice_number ?? '-' }}
                                                </a>
                                            </td>
                                            <td class="text-center">
                                                @if($ret->return_handling_type == 'deduct_invoice')
                                                    <span class="badge badge-warning">Potong Nota</span>
                                                @else
                                                    <span class="badge badge-info">Deposit</span>
                                                @endif
                                            </td>
                                            <td class="text-right font-medium">Rp {{ number_format($ret->total_amount, 0, ',', '.') }}</td>
                                            <td class="text-center">
                                                <a href="{{ route('admin.sales-returns.show', $ret->return_id) }}" class="btn-icon btn-sm btn-secondary flex items-center justify-center">
                                                    <i class="material-icons text-[16px] leading-none">visibility</i>
                                                </a>
                                            </td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="6" class="text-center py-12 text-slate-400">
                                                <div class="flex flex-col items-center">
                                                    <i class="material-icons text-4xl mb-2 opacity-50">remove_shopping_cart</i>
                                                    <p class="text-sm">Belum ada riwayat retur penjualan.</p>
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
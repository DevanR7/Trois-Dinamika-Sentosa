@extends('client.layouts.app')

@section('title', 'Dashboard Overview')

@section('content')

    {{-- 1. WELCOME SECTION & QUICK ACTIONS --}}
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 mb-8">
        <div>
            <h2 class="text-2xl font-extrabold text-slate-800 dark:text-white tracking-tight">
                Selamat Datang, {{ explode(' ', $client->client_name)[0] }}!
            </h2>
            <p class="text-sm text-slate-500 dark:text-slate-400 mt-1">
                Berikut adalah ringkasan aktivitas dan status akun Anda hari ini.
            </p>
        </div>
        <div class="flex gap-3">
            <a href="{{ route('client.client-orders.create') }}" class="btn btn-primary">
                <i class="material-icons text-sm">add_shopping_cart</i>
                Buat Pesanan Baru
            </a>
        </div>
    </div>

    {{-- 2. STATISTICS CARDS --}}
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
        
        {{-- Card: Total Piutang (Tagihan Belum Lunas) --}}
        <div class="card stat-card group">
            <div>
                <div class="stat-label">Total Tagihan</div>
                <div class="stat-value text-rose-600 dark:text-rose-400 mt-1">
                    Rp {{ number_format($totalPiutang, 0, ',', '.') }}
                </div>
                <div class="text-xs text-slate-500 mt-2 font-medium">
                    Belum Dibayar
                </div>
            </div>
            <div class="stat-icon bg-rose-50 text-rose-600 dark:bg-rose-900/20 dark:text-rose-400">
                <i class="material-icons">receipt_long</i>
            </div>
        </div>

        {{-- Card: Saldo Deposit (Available & Pending) --}}
        <div class="card stat-card group">
            <div>
                <div class="stat-label">Saldo Deposit</div>
                <div class="stat-value text-emerald-600 dark:text-emerald-400 mt-1">
                    Rp {{ number_format($availableBalance, 0, ',', '.') }}
                </div>
                @if($pendingBalance > 0)
                    <div class="text-xs text-amber-500 mt-2 font-medium flex items-center gap-1">
                        <i class="material-icons text-[12px]">schedule</i>
                        Pending: Rp {{ number_format($pendingBalance, 0, ',', '.') }}
                    </div>
                @else
                    <div class="text-xs text-slate-500 mt-2 font-medium">
                        Siap digunakan
                    </div>
                @endif
            </div>
            <div class="stat-icon bg-emerald-50 text-emerald-600 dark:bg-emerald-900/20 dark:text-emerald-400">
                <i class="material-icons">account_balance_wallet</i>
            </div>
        </div>

        {{-- Card: Pesanan Anda (Pending Review) --}}
        <div class="card stat-card group">
            <div>
                <div class="stat-label">Pesanan Saya</div>
                <div class="stat-value text-slate-800 dark:text-white mt-1">
                    {{ $pendingClientOrdersCount }}
                </div>
                <div class="text-xs text-slate-500 mt-2 font-medium">
                    Menunggu Review Admin
                </div>
            </div>
            <div class="stat-icon bg-blue-50 text-blue-600 dark:bg-blue-900/20 dark:text-blue-400">
                <i class="material-icons">shopping_cart</i>
            </div>
        </div>

        {{-- Card: Pesanan Sales (Active) --}}
        <div class="card stat-card group">
            <div>
                <div class="stat-label">Pesanan Sales</div>
                <div class="stat-value text-slate-800 dark:text-white mt-1">
                    {{ $activeSalesOrdersCount }}
                </div>
                <div class="text-xs text-slate-500 mt-2 font-medium">
                    Diproses / Dikirim
                </div>
            </div>
            <div class="stat-icon bg-indigo-50 text-indigo-600 dark:bg-indigo-900/20 dark:text-indigo-400">
                <i class="material-icons">local_shipping</i>
            </div>
        </div>
    </div>

    {{-- 3. MAIN CONTENT GRID (Split Layout) --}}
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
        
        {{-- LEFT COLUMN: Unpaid Invoices (Takes 2/3 width on large screens) --}}
        <div class="lg:col-span-2 flex flex-col gap-6">
            <div class="card h-full flex flex-col">
                <div class="card-header">
                    <h3 class="card-header-title flex items-center gap-2">
                        <i class="material-icons text-slate-400">payments</i>
                        Tagihan Perlu Dibayar
                    </h3>
                    @if($totalPiutang > 0)
                        <a href="{{ route('client.invoices.bulkPay.create') }}" class="btn btn-primary btn-sm">
                            Bayar Sekaligus
                        </a>
                    @endif
                </div>

                <div class="p-0 flex-1">
                    @if($invoicesForCard->count() > 0)
                        <div class="table-container border-0 rounded-none shadow-none">
                            <table class="table-modern">
                                <thead>
                                    <tr>
                                        <th>No. Invoice</th>
                                        <th>Jatuh Tempo</th>
                                        <th class="text-right">Sisa Tagihan</th>
                                        <th class="text-center">Aksi</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($invoicesForCard->take(5) as $invoice)
                                        <tr>
                                            <td class="font-medium text-slate-700 dark:text-slate-200">
                                                {{ $invoice->invoice_number }}
                                                @if($invoice->status == 'partially_paid')
                                                    <span class="block text-[10px] text-amber-500 font-bold uppercase mt-0.5">Sebagian</span>
                                                @endif
                                            </td>
                                            <td>
                                                <div class="flex items-center gap-2">
                                                    <span class="text-slate-600 dark:text-slate-400">
                                                        {{ $invoice->due_date->format('d M Y') }}
                                                    </span>
                                                    @if($invoice->due_date < now())
                                                        <span class="badge badge-danger text-[10px] px-1.5">Telat</span>
                                                    @endif
                                                </div>
                                            </td>
                                            <td class="text-right font-bold text-rose-600 dark:text-rose-400">
                                                Rp {{ number_format($invoice->remaining_balance, 0, ',', '.') }}
                                            </td>
                                            <td class="text-center">
                                                <a href="{{ route('client.invoices.show', $invoice->invoice_id) }}" 
                                                   class="btn btn-secondary btn-sm h-8 px-3"
                                                   title="Lihat Detail & Bayar">
                                                    Detail
                                                </a>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                        @if($invoicesForCard->count() > 5)
                            <div class="p-4 border-t border-slate-100 dark:border-slate-700/50 text-center">
                                <a href="{{ route('client.invoices.index') }}" class="text-sm font-medium text-indigo-600 hover:text-indigo-700 dark:text-indigo-400 hover:underline">
                                    Lihat {{ $invoicesForCard->count() - 5 }} tagihan lainnya
                                </a>
                            </div>
                        @endif
                    @else
                        <div class="flex flex-col items-center justify-center py-12 text-center">
                            <div class="bg-slate-50 dark:bg-slate-800/50 p-4 rounded-full mb-3">
                                <i class="material-icons text-emerald-500 text-3xl">check_circle</i>
                            </div>
                            <h4 class="text-slate-800 dark:text-white font-medium mb-1">Tidak Ada Tagihan</h4>
                            <p class="text-sm text-slate-500 dark:text-slate-400">
                                Luar biasa! Anda tidak memiliki tagihan yang tertunggak saat ini.
                            </p>
                        </div>
                    @endif
                </div>
            </div>
        </div>

        {{-- RIGHT COLUMN: Recent Activity (Takes 1/3 width) --}}
        <div class="lg:col-span-1 flex flex-col gap-6">
            <div class="card h-full">
                <div class="card-header">
                    <h3 class="card-header-title">Aktivitas Terakhir</h3>
                </div>
                <div class="card-body p-0">
                    @if($pendingActivities->count() > 0)
                        <div class="divide-y divide-slate-100 dark:divide-slate-700/50">
                            @foreach($pendingActivities as $activity)
                                @php
                                    // Tentukan tipe aktivitas (Order atau ChangeRequest)
                                    $isOrder = $activity instanceof \App\Models\Order;
                                    $link = $isOrder 
                                        ? route('client.client-orders.show', $activity->order_id)
                                        : route('client.sales-orders.show', $activity->order_id); // ChangeReq link ke Sales Order Show
                                    
                                    $icon = $isOrder ? 'shopping_cart' : 'edit_note';
                                    $iconColor = $isOrder ? 'bg-blue-100 text-blue-600' : 'bg-amber-100 text-amber-600';
                                    $title = $isOrder ? 'Pesanan Baru' : 'Req. Perubahan';
                                    $ref = $isOrder ? $activity->order_number : ($activity->order->order_number ?? 'Order Hapus');
                                    $date = $activity->created_at->diffForHumans();
                                @endphp

                                <a href="{{ $link }}" class="flex items-start gap-4 p-4 hover:bg-slate-50 dark:hover:bg-slate-800/50 transition-colors">
                                    <div class="shrink-0">
                                        <div class="w-10 h-10 rounded-lg {{ $iconColor }} flex items-center justify-center dark:bg-opacity-20">
                                            <i class="material-icons text-lg">{{ $icon }}</i>
                                        </div>
                                    </div>
                                    <div class="flex-1 min-w-0">
                                        <p class="text-sm font-bold text-slate-800 dark:text-slate-200 truncate">
                                            {{ $title }}
                                        </p>
                                        <p class="text-xs text-slate-500 dark:text-slate-400 mt-0.5 truncate">
                                            #{{ $ref }}
                                        </p>
                                        <div class="flex items-center gap-2 mt-2">
                                            <span class="badge badge-warning text-[10px] px-1.5 py-0">Pending</span>
                                            <span class="text-[10px] text-slate-400">{{ $date }}</span>
                                        </div>
                                    </div>
                                    <div class="shrink-0 self-center">
                                        <i class="material-icons text-slate-300 text-sm">chevron_right</i>
                                    </div>
                                </a>
                            @endforeach
                        </div>
                    @else
                        <div class="flex flex-col items-center justify-center py-8 text-center px-4">
                            <i class="material-icons text-slate-300 text-4xl mb-2">history</i>
                            <p class="text-sm text-slate-500 dark:text-slate-400">
                                Belum ada aktivitas pending baru-baru ini.
                            </p>
                        </div>
                    @endif
                </div>
                <div class="card-footer bg-white dark:bg-slate-800 border-t-0">
                    <a href="{{ route('client.client-orders.index') }}" class="btn btn-secondary w-full justify-center">
                        Lihat Semua Riwayat
                    </a>
                </div>
            </div>
        </div>
    </div>
@endsection
@extends('client.layouts.app')

@section('title', 'Dashboard')

@section('content')
<div class="space-y-8 pb-10">

    {{-- ====================================================== --}}
    {{-- 1. MODERN HEADER SECTION --}}
    {{-- ====================================================== --}}
    <div class="relative bg-white dark:bg-slate-800 p-6 rounded-2xl shadow-sm border border-slate-200 dark:border-slate-700 overflow-hidden">
        {{-- Background Decoration --}}
        <div class="absolute top-0 right-0 w-64 h-64 bg-indigo-50 dark:bg-indigo-900/20 rounded-full blur-3xl -mr-16 -mt-16 pointer-events-none"></div>
        
        <div class="relative z-10 flex flex-col md:flex-row justify-between md:items-center gap-4">
            <div>
                <h2 class="text-2xl md:text-3xl font-bold text-slate-800 dark:text-slate-100 tracking-tight">
                    Halo, {{ $client->client_name }}! 👋
                </h2>
                <p class="text-slate-500 dark:text-slate-400 mt-1 text-sm md:text-base">
                    Senang melihat Anda kembali. Berikut ringkasan aktivitas Anda.
                </p>
            </div>
            
            <div class="flex items-center gap-3 bg-slate-50 dark:bg-slate-900/50 px-4 py-2.5 rounded-xl border border-slate-100 dark:border-slate-700">
                <div class="w-10 h-10 rounded-full bg-indigo-100 dark:bg-indigo-900/50 flex items-center justify-center text-indigo-600 dark:text-indigo-400">
                    <i class="material-icons text-[20px]">calendar_today</i>
                </div>
                <div>
                    <span class="block text-xs font-bold text-slate-400 uppercase tracking-wider">Hari Ini</span>
                    <span class="block text-sm font-bold text-slate-700 dark:text-slate-200">{{ \Carbon\Carbon::now()->translatedFormat('d F Y') }}</span>
                </div>
            </div>
        </div>
    </div>

    {{-- ====================================================== --}}
    {{-- 2. KPI CARDS (INTERACTIVE) --}}
    {{-- ====================================================== --}}
    <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-4 gap-6">
        
        {{-- CARD 1: TAGIHAN (Red) --}}
        <div class="dashboard-card p-6 border-l-[4px] border-red-500 relative overflow-hidden group hover:-translate-y-1 hover:shadow-lg hover:shadow-red-500/10 transition-all duration-300">
            <div class="relative z-10">
                <div class="flex justify-between items-start mb-4">
                    <div class="text-[11px] font-bold text-slate-500 uppercase tracking-widest">Tagihan</div>
                    <div class="w-8 h-8 rounded-full bg-red-50 dark:bg-red-900/20 flex items-center justify-center text-red-500">
                        <i class="material-icons text-[18px]">receipt_long</i>
                    </div>
                </div>
                <h4 class="text-2xl font-extrabold text-slate-800 dark:text-slate-100">
                    Rp {{ number_format($totalPiutang, 0, ',', '.') }}
                </h4>
                <a href="{{ route('client.invoices.index') }}" class="inline-flex items-center gap-1 mt-4 text-xs font-bold text-red-600 dark:text-red-400 group-hover:underline">
                    Bayar Sekarang <i class="material-icons text-[14px] transition-transform group-hover:translate-x-1">arrow_forward</i>
                </a>
            </div>
            {{-- Decorative Icon --}}
            <i class="material-icons absolute -right-6 -bottom-6 text-[120px] text-red-500 opacity-[0.03] dark:opacity-[0.05] -rotate-12 group-hover:scale-110 group-hover:rotate-0 transition-all duration-500 select-none">receipt_long</i>
        </div>

        {{-- CARD 2: SALDO (Emerald) --}}
        <div class="dashboard-card p-6 border-l-[4px] border-emerald-500 relative overflow-hidden group hover:-translate-y-1 hover:shadow-lg hover:shadow-emerald-500/10 transition-all duration-300">
            <div class="relative z-10">
                <div class="flex justify-between items-start mb-4">
                    <div class="text-[11px] font-bold text-slate-500 uppercase tracking-widest">Saldo Deposit</div>
                    <div class="w-8 h-8 rounded-full bg-emerald-50 dark:bg-emerald-900/20 flex items-center justify-center text-emerald-500">
                        <i class="material-icons text-[18px]">account_balance_wallet</i>
                    </div>
                </div>
                <h4 class="text-2xl font-extrabold text-slate-800 dark:text-slate-100">
                    Rp {{ number_format($availableBalance, 0, ',', '.') }}
                </h4>
                @if($pendingBalance > 0)
                    <div class="mt-4 inline-flex items-center gap-1 text-xs font-bold text-amber-600 bg-amber-50 dark:bg-amber-900/20 dark:text-amber-400 px-2 py-1 rounded-md">
                        <i class="material-icons text-[12px]">hourglass_empty</i> Tertahan: Rp {{ number_format($pendingBalance, 0, ',', '.') }}
                    </div>
                @else
                    <div class="mt-4 text-xs text-slate-400 font-medium flex items-center gap-1">
                        <i class="material-icons text-[14px] text-emerald-500">check_circle</i> Siap digunakan
                    </div>
                @endif
            </div>
            <i class="material-icons absolute -right-6 -bottom-6 text-[120px] text-emerald-500 opacity-[0.03] dark:opacity-[0.05] -rotate-12 group-hover:scale-110 group-hover:rotate-0 transition-all duration-500 select-none">account_balance_wallet</i>
        </div>

        {{-- CARD 3: ORDER PENDING (Cyan) --}}
        <div class="dashboard-card p-6 border-l-[4px] border-cyan-500 relative overflow-hidden group hover:-translate-y-1 hover:shadow-lg hover:shadow-cyan-500/10 transition-all duration-300">
            <div class="relative z-10">
                <div class="flex justify-between items-start mb-4">
                    <div class="text-[11px] font-bold text-slate-500 uppercase tracking-widest">Order Pending</div>
                    <div class="w-8 h-8 rounded-full bg-cyan-50 dark:bg-cyan-900/20 flex items-center justify-center text-cyan-500">
                        <i class="material-icons text-[18px]">shopping_cart</i>
                    </div>
                </div>
                <h4 class="text-2xl font-extrabold text-slate-800 dark:text-slate-100">
                    {{ $pendingClientOrdersCount }} <span class="text-sm font-medium text-slate-400">Pesanan</span>
                </h4>
                <a href="{{ route('client.client-orders.index') }}" class="inline-flex items-center gap-1 mt-4 text-xs font-bold text-cyan-600 dark:text-cyan-400 group-hover:underline">
                    Cek Status <i class="material-icons text-[14px] transition-transform group-hover:translate-x-1">arrow_forward</i>
                </a>
            </div>
            <i class="material-icons absolute -right-6 -bottom-6 text-[120px] text-cyan-500 opacity-[0.03] dark:opacity-[0.05] -rotate-12 group-hover:scale-110 group-hover:rotate-0 transition-all duration-500 select-none">shopping_cart</i>
        </div>

        {{-- CARD 4: ORDER SALES (Indigo) --}}
        <div class="dashboard-card p-6 border-l-[4px] border-indigo-500 relative overflow-hidden group hover:-translate-y-1 hover:shadow-lg hover:shadow-indigo-500/10 transition-all duration-300">
            <div class="relative z-10">
                <div class="flex justify-between items-start mb-4">
                    <div class="text-[11px] font-bold text-slate-500 uppercase tracking-widest">Order Sales</div>
                    <div class="w-8 h-8 rounded-full bg-indigo-50 dark:bg-indigo-900/20 flex items-center justify-center text-indigo-500">
                        <i class="material-icons text-[18px]">inventory_2</i>
                    </div>
                </div>
                <h4 class="text-2xl font-extrabold text-slate-800 dark:text-slate-100">
                    {{ $activeSalesOrdersCount }} <span class="text-sm font-medium text-slate-400">Aktif</span>
                </h4>
                <a href="{{ route('client.sales-orders.index') }}" class="inline-flex items-center gap-1 mt-4 text-xs font-bold text-indigo-600 dark:text-indigo-400 group-hover:underline">
                    Lihat Order <i class="material-icons text-[14px] transition-transform group-hover:translate-x-1">arrow_forward</i>
                </a>
            </div>
            <i class="material-icons absolute -right-6 -bottom-6 text-[120px] text-indigo-500 opacity-[0.03] dark:opacity-[0.05] -rotate-12 group-hover:scale-110 group-hover:rotate-0 transition-all duration-500 select-none">inventory_2</i>
        </div>
    </div>

    {{-- ====================================================== --}}
    {{-- 3. GRID UTAMA (TIMELINE & TABLE) --}}
    {{-- ====================================================== --}}
    <div class="grid grid-cols-1 xl:grid-cols-3 gap-8">

        {{-- KOLOM KIRI: STATUS PENGAJUAN (TIMELINE STYLE) --}}
        <div class="xl:col-span-1">
            <div class="dashboard-card h-full flex flex-col">
                <div class="px-6 py-5 border-b border-slate-100 dark:border-slate-700 flex items-center justify-between">
                    <h5 class="font-bold text-slate-800 dark:text-slate-100">Aktivitas Terkini</h5>
                    <span class="text-xs font-bold bg-indigo-50 text-indigo-600 px-2 py-1 rounded">{{ $pendingActivities->count() }} Pending</span>
                </div>
                
                <div class="p-6 overflow-y-auto custom-scrollbar flex-1 min-h-[350px]" style="max-height: 500px;">
                    @if($pendingActivities->isNotEmpty())
                        <div class="relative border-l-2 border-slate-100 dark:border-slate-700 ml-3 space-y-8">
                            @foreach($pendingActivities as $activity)
                                <div class="relative pl-8 group">
                                    {{-- Timeline Dot --}}
                                    <span class="absolute -left-[9px] top-1 w-[16px] h-[16px] rounded-full border-[3px] border-white dark:border-slate-800 bg-indigo-500 group-hover:scale-125 transition-transform duration-200 shadow-sm"></span>

                                    {{-- Content --}}
                                    @if($activity instanceof \App\Models\Order)
                                        <a href="{{ route('client.client-orders.show', $activity->order_id) }}" class="block">
                                            <div class="bg-white dark:bg-slate-800/50 p-4 rounded-xl border border-slate-100 dark:border-slate-700 shadow-sm hover:shadow-md hover:border-indigo-200 dark:hover:border-indigo-800 transition-all duration-200">
                                                <div class="flex justify-between items-start mb-2">
                                                    <span class="text-xs font-bold text-indigo-600 bg-indigo-50 dark:bg-indigo-900/30 px-2 py-0.5 rounded">Pesanan Online</span>
                                                    <span class="text-[10px] text-slate-400 font-mono">{{ $activity->created_at->diffForHumans() }}</span>
                                                </div>
                                                <h6 class="font-bold text-slate-700 dark:text-slate-200 text-sm mb-1">
                                                    Order #{{ $activity->order_number }}
                                                </h6>
                                                <p class="text-xs text-slate-500">Menunggu review admin.</p>
                                            </div>
                                        </a>
                                    @elseif($activity instanceof \App\Models\OrderChangeRequest)
                                        <a href="{{ route('client.sales-orders.show', $activity->order->order_id) }}" class="block">
                                            <div class="bg-white dark:bg-slate-800/50 p-4 rounded-xl border border-slate-100 dark:border-slate-700 shadow-sm hover:shadow-md hover:border-amber-200 dark:hover:border-amber-800 transition-all duration-200">
                                                <div class="flex justify-between items-start mb-2">
                                                    <span class="text-xs font-bold text-amber-600 bg-amber-50 dark:bg-amber-900/30 px-2 py-0.5 rounded">Request Perubahan</span>
                                                    <span class="text-[10px] text-slate-400 font-mono">{{ $activity->created_at->diffForHumans() }}</span>
                                                </div>
                                                <h6 class="font-bold text-slate-700 dark:text-slate-200 text-sm mb-1">
                                                    Untuk Order #{{ $activity->order->order_number ?? 'N/A' }}
                                                </h6>
                                                <p class="text-xs text-slate-500">
                                                    Tipe: {{ $activity->request_type == 'cancel' ? 'Pembatalan' : 'Modifikasi' }}
                                                </p>
                                            </div>
                                        </a>
                                    @endif
                                </div>
                            @endforeach
                        </div>
                    @else
                        {{-- Empty State --}}
                        <div class="h-full flex flex-col items-center justify-center text-center">
                            <div class="w-16 h-16 bg-slate-50 dark:bg-slate-700/50 rounded-full grid place-items-center mb-4">
                                <i class="material-icons text-3xl text-slate-300 dark:text-slate-500">assignment_turned_in</i>
                            </div>
                            <h6 class="font-bold text-slate-600 dark:text-slate-300 text-sm">Tidak ada aktivitas</h6>
                            <p class="text-xs text-slate-400 mt-1 max-w-[200px]">Semua pengajuan Anda telah diproses atau belum ada pengajuan baru.</p>
                        </div>
                    @endif
                </div>
            </div>
        </div>

        {{-- KOLOM KANAN: TAGIHAN BELUM LUNAS (2 Kolom di XL) --}}
        <div class="xl:col-span-2">
            <div class="dashboard-card h-full flex flex-col">
                <div class="px-6 py-5 border-b border-slate-100 dark:border-slate-700 flex justify-between items-center">
                    <h5 class="font-bold text-slate-800 dark:text-slate-100">Tagihan Belum Lunas</h5>
                    <a href="{{ route('client.invoices.index') }}" class="text-xs font-bold text-indigo-600 dark:text-indigo-400 hover:text-indigo-700 hover:underline uppercase tracking-wider">
                        Lihat Semua
                    </a>
                </div>

                <div class="overflow-x-auto custom-scrollbar rounded-b-xl flex-1 min-h-[350px]">
                    @if($invoicesForCard->isEmpty())
                        {{-- Empty State --}}
                        <div class="h-full flex flex-col items-center justify-center text-center py-10">
                            <div class="w-24 h-24 bg-emerald-50 dark:bg-emerald-900/20 rounded-full grid place-items-center mb-4 ring-8 ring-emerald-50/50 dark:ring-emerald-900/10">
                                <i class="material-icons text-5xl text-emerald-500">check_circle</i>
                            </div>
                            <h3 class="text-lg font-bold text-slate-800 dark:text-slate-100">Luar Biasa!</h3>
                            <p class="text-slate-500 dark:text-slate-400 mt-1">Anda tidak memiliki tagihan yang belum lunas saat ini.</p>
                        </div>
                    @else
                        <table class="w-full text-left border-collapse">
                            <thead>
                                <tr class="bg-slate-50/50 dark:bg-slate-800/50 border-b border-slate-100 dark:border-slate-700 text-xs uppercase text-slate-500 font-bold">
                                    <th class="py-4 pl-6">No. Invoice</th>
                                    <th class="py-4">Jatuh Tempo</th>
                                    <th class="py-4 text-right">Sisa Tagihan</th>
                                    <th class="py-4 text-center">Status</th>
                                    <th class="py-4 pr-6 text-center">Aksi</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-50 dark:divide-slate-700/50 text-sm">
                                @foreach($invoicesForCard as $invoice)
                                    @php $sisaTagihan = $invoice->remaining_balance; @endphp

                                    @if($sisaTagihan > 0.01)
                                        <tr class="hover:bg-slate-50 dark:hover:bg-slate-800/50 transition-colors group">
                                            <td class="pl-6 py-4">
                                                <a href="{{ route('client.invoices.show', $invoice->invoice_id) }}" class="font-bold text-indigo-600 dark:text-indigo-400 hover:underline">
                                                    {{ $invoice->invoice_number }}
                                                </a>
                                            </td>
                                            <td class="py-4">
                                                <div class="flex items-center gap-1.5 {{ optional($invoice->due_date)->isPast() ? 'text-red-600 dark:text-red-400 font-bold' : 'text-slate-600 dark:text-slate-400' }}">
                                                    @if(optional($invoice->due_date)->isPast())
                                                        <i class="material-icons text-[16px]">error</i>
                                                    @else 
                                                        <i class="material-icons text-[16px] text-slate-400">event</i>
                                                    @endif
                                                    {{ optional($invoice->due_date)->format('d M Y') }}
                                                </div>
                                            </td>
                                            <td class="text-right font-bold text-red-600 dark:text-red-400 py-4">
                                                Rp {{ number_format($sisaTagihan, 0, ',', '.') }}
                                            </td>
                                            <td class="text-center py-4">
                                                @if($invoice->status == 'partially_paid')
                                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded text-xs font-bold bg-sky-100 text-sky-700 border border-sky-200">Cicil</span>
                                                @else
                                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded text-xs font-bold bg-amber-50 text-amber-700 border border-amber-200">Belum Lunas</span>
                                                @endif
                                            </td>
                                            <td class="text-center pr-6 py-4">
                                                <a href="{{ route('client.invoices.show', $invoice->invoice_id) }}" 
                                                   class="inline-flex items-center justify-center px-4 py-2 rounded-lg text-xs font-bold text-white bg-indigo-600 hover:bg-indigo-700 transition shadow-sm hover:shadow-md transform hover:-translate-y-0.5">
                                                    Bayar
                                                </a>
                                            </td>
                                        </tr>
                                    @endif
                                @endforeach
                            </tbody>
                        </table>
                    @endif
                </div>
            </div>
        </div>

    </div>
</div>
@endsection
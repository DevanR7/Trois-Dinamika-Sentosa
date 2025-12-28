@extends('admin.layouts.app')

@section('title', 'Dashboard')

@section('content')
    {{-- ========================================================= --}}
    {{-- 1. HEADER & GLOBAL FILTER (YEAR ONLY) --}}
    {{-- ========================================================= --}}
    <div class="flex flex-col md:flex-row md:items-end justify-between gap-4 mb-8 animate-enter">
        <div>
            <h1 class="text-3xl font-extrabold text-slate-900 dark:text-white tracking-tight leading-tight flex items-center gap-3">
                <i class="material-icons text-3xl text-indigo-500">space_dashboard</i> Dashboard
            </h1>
            <p class="text-sm text-slate-500 dark:text-slate-400 mt-1 ml-1">
                Selamat datang kembali, <span class="font-bold text-indigo-600 dark:text-indigo-400">{{ $user->full_name }}</span>.
            </p>
        </div>

        {{-- Global Year Filter --}}
        <div class="bg-white dark:bg-slate-800 p-1.5 rounded-2xl shadow-sm border border-slate-200 dark:border-slate-700">
            <form method="GET" action="{{ route('admin.dashboard') }}" class="flex items-center gap-2">
                {{-- Keep user filter if selected --}}
                @if(request('filter_user_id'))
                    <input type="hidden" name="filter_user_id" value="{{ request('filter_user_id') }}">
                @endif
                
                <div class="w-32">
                    <select name="year" class="tom-select-filter" onchange="this.form.submit()">
                        @foreach($availableYears as $yr)
                            <option value="{{ $yr }}" {{ $selectedYear == $yr ? 'selected' : '' }}>Tahun {{ $yr }}</option>
                        @endforeach
                    </select>
                </div>
            </form>
        </div>
    </div>

    @if($canViewFinancials && !$selectedUserId)
        {{-- ========================================================= --}}
        {{-- 2. FINANCIAL OVERVIEW (GLOBAL COMPANY STATS) --}}
        {{-- ========================================================= --}}
        <div class="grid grid-cols-1 lg:grid-cols-12 gap-6 mb-6 animate-enter">
            
            {{-- A. PRIMARY STATS (REVENUE & PROFIT) - GLOBAL --}}
            <div class="lg:col-span-8 grid grid-cols-1 md:grid-cols-2 gap-6">
                
                {{-- Revenue Card --}}
                <div class="relative group overflow-hidden rounded-2xl bg-gradient-to-br from-slate-800 to-[#0f172a] p-6 shadow-lg transition-transform hover:-translate-y-1">
                    <div class="relative z-10 flex flex-col justify-between h-full">
                        <div class="flex items-center gap-3 mb-4">
                            {{-- FIX: Icon Centering --}}
                            <div class="w-10 h-10 flex items-center justify-center bg-white/10 rounded-lg backdrop-blur-sm text-indigo-300 shadow-inner">
                                <i class="material-icons">account_balance_wallet</i>
                            </div>
                            <span class="text-xs font-bold text-slate-300 uppercase tracking-wider">Total Pemasukan (Global)</span>
                        </div>
                        <div>
                            <h3 class="text-3xl sm:text-4xl font-extrabold text-white tracking-tight autonumeric" data-currency-symbol="Rp " data-digit-group-separator="." data-decimal-character=",">
                                {{ $stats['revenue'] }}
                            </h3>
                            <div class="mt-2 flex items-center gap-2">
                                <span class="badge bg-emerald-500/20 text-emerald-300 border border-emerald-500/30 px-2 py-0.5 rounded text-[10px] flex items-center gap-1">
                                    <i class="material-icons text-[10px]">arrow_upward</i> Cash In
                                </span>
                            </div>
                        </div>
                    </div>
                    {{-- DECORATIVE ICON --}}
                    <i class="material-icons absolute -right-4 -bottom-8 text-[140px] text-white opacity-[0.03] rotate-12 group-hover:scale-110 transition-transform duration-500 pointer-events-none">payments</i>
                    <div class="absolute -right-6 -bottom-6 w-32 h-32 bg-indigo-500/10 rounded-full blur-2xl group-hover:bg-indigo-500/20 transition-colors pointer-events-none"></div>
                </div>

                {{-- Profit Card --}}
                <div class="relative group overflow-hidden rounded-2xl bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700 p-6 shadow-sm transition-transform hover:-translate-y-1 hover:border-indigo-300 dark:hover:border-indigo-700">
                    <div class="relative z-10 flex flex-col justify-between h-full">
                        <div class="flex items-center gap-3 mb-4">
                            {{-- FIX: Icon Centering --}}
                            <div class="w-10 h-10 flex items-center justify-center bg-emerald-50 dark:bg-emerald-900/20 rounded-lg text-emerald-600 dark:text-emerald-400">
                                <i class="material-icons">savings</i>
                            </div>
                            <span class="text-xs font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wider">Estimasi Laba (Global)</span>
                        </div>
                        <div>
                            <h3 class="text-3xl sm:text-4xl font-extrabold text-emerald-600 dark:text-emerald-400 tracking-tight autonumeric" data-currency-symbol="Rp " data-digit-group-separator="." data-decimal-character=",">
                                {{ $stats['net_profit'] }}
                            </h3>
                            <div class="mt-2 flex items-center gap-2">
                                <span class="text-[10px] text-slate-400 font-medium flex items-center gap-1">
                                    <i class="material-icons text-[10px]">calculate</i> (Pemasukan - Total Beban)
                                </span>
                            </div>
                        </div>
                    </div>
                    {{-- DECORATIVE ICON --}}
                    <i class="material-icons absolute -right-4 -bottom-6 text-[130px] text-emerald-500 opacity-[0.05] -rotate-12 group-hover:scale-110 transition-transform duration-500 pointer-events-none">monetization_on</i>
                </div>
            </div>

            {{-- B. ACTION REQUIRED WIDGET --}}
            <div class="lg:col-span-4 card h-full flex flex-col border-l-4 border-amber-400 shadow-sm">
                <div class="px-5 py-4 border-b border-slate-100 dark:border-slate-700 flex justify-between items-center bg-amber-50/30 dark:bg-transparent">
                    <h3 class="font-bold text-slate-700 dark:text-white flex items-center gap-2 text-sm">
                        <i class="material-icons text-amber-500 text-lg">notifications_active</i>
                        Perlu Tindakan
                    </h3>
                    @if(array_sum($pendingActions) > 0)
                        <span class="flex h-3 w-3 relative">
                            <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-amber-400 opacity-75"></span>
                            <span class="relative inline-flex rounded-full h-3 w-3 bg-amber-500"></span>
                        </span>
                    @endif
                </div>
                <div class="flex-1 p-0 overflow-y-auto custom-scrollbar max-h-[200px] lg:max-h-full">
                    @if(array_sum($pendingActions) == 0)
                        <div class="h-full flex flex-col items-center justify-center text-slate-400 py-6">
                            <i class="material-icons text-5xl mb-2 text-emerald-400 opacity-80">task_alt</i>
                            <p class="text-xs font-medium">Semua tugas selesai!</p>
                        </div>
                    @else
                        <ul class="divide-y divide-slate-100 dark:divide-slate-700">
                            @if($pendingActions['invoice_draft'] > 0)
                            <li>
                                <a href="{{ route('admin.invoices.index', ['status' => 'draft']) }}" class="flex items-center justify-between px-5 py-3 hover:bg-slate-50 dark:hover:bg-slate-700/50 transition group">
                                    <div class="flex items-center gap-3">
                                        {{-- FIX: Icon Centering --}}
                                        <div class="w-8 h-8 flex items-center justify-center rounded-full bg-amber-100 text-amber-600">
                                            <i class="material-icons text-xs">edit_note</i>
                                        </div>
                                        <span class="text-xs font-medium text-slate-600 dark:text-slate-300">Invoice Draft</span>
                                    </div>
                                    <span class="badge badge-warning py-0.5 px-2 text-[10px]">{{ $pendingActions['invoice_draft'] }}</span>
                                </a>
                            </li>
                            @endif
                            @if($pendingActions['pending_payments'] > 0)
                            <li>
                                <a href="{{ route('admin.payment-clearance.index') }}" class="flex items-center justify-between px-5 py-3 hover:bg-slate-50 dark:hover:bg-slate-700/50 transition group">
                                    <div class="flex items-center gap-3">
                                        {{-- FIX: Icon Centering --}}
                                        <div class="w-8 h-8 flex items-center justify-center rounded-full bg-blue-100 text-blue-600">
                                            <i class="material-icons text-xs">verified</i>
                                        </div>
                                        <span class="text-xs font-medium text-slate-600 dark:text-slate-300">Konfirmasi Pembayaran</span>
                                    </div>
                                    <span class="badge badge-info py-0.5 px-2 text-[10px]">{{ $pendingActions['pending_payments'] }}</span>
                                </a>
                            </li>
                            @endif
                            @if($pendingActions['po_draft'] > 0)
                            <li>
                                <a href="{{ route('admin.purchase-orders.index', ['status' => 'draft']) }}" class="flex items-center justify-between px-5 py-3 hover:bg-slate-50 dark:hover:bg-slate-700/50 transition group">
                                    <div class="flex items-center gap-3">
                                        {{-- FIX: Icon Centering --}}
                                        <div class="w-8 h-8 flex items-center justify-center rounded-full bg-indigo-100 text-indigo-600">
                                            <i class="material-icons text-xs">shopping_cart</i>
                                        </div>
                                        <span class="text-xs font-medium text-slate-600 dark:text-slate-300">PO Draft</span>
                                    </div>
                                    <span class="badge badge-primary py-0.5 px-2 text-[10px]">{{ $pendingActions['po_draft'] }}</span>
                                </a>
                            </li>
                            @endif
                            @if($pendingActions['stock_alert'] > 0)
                            <li>
                                <a href="{{ route('admin.products.index', ['sort' => 'stok-sedikit']) }}" class="flex items-center justify-between px-5 py-3 hover:bg-slate-50 dark:hover:bg-slate-700/50 transition group">
                                    <div class="flex items-center gap-3">
                                        {{-- FIX: Icon Centering --}}
                                        <div class="w-8 h-8 flex items-center justify-center rounded-full bg-red-100 text-red-600">
                                            <i class="material-icons text-xs">inventory</i>
                                        </div>
                                        <span class="text-xs font-medium text-slate-600 dark:text-slate-300">Stok Menipis</span>
                                    </div>
                                    <span class="badge badge-danger py-0.5 px-2 text-[10px]">{{ $pendingActions['stock_alert'] }}</span>
                                </a>
                            </li>
                            @endif
                        </ul>
                    @endif
                </div>
            </div>
        </div>

        {{-- C. SECONDARY STATS (GRID 5) --}}
        <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-5 gap-4 mb-8 animate-enter" style="animation-delay: 0.1s">
            @foreach([
                ['label' => 'Total Beban', 'value' => $stats['expense'], 'icon' => 'trending_down', 'color' => 'rose'],
                ['label' => 'Piutang Global', 'value' => $stats['receivables_global'], 'icon' => 'pending', 'color' => 'amber'],
                ['label' => 'Hutang Dagang', 'value' => $stats['payables'], 'icon' => 'money_off', 'color' => 'orange'],
                ['label' => 'Sisa Pinjaman', 'value' => $stats['loans'], 'icon' => 'account_balance', 'color' => 'blue'],
                ['label' => 'Nilai Aset', 'value' => $stats['assets'], 'icon' => 'domain', 'color' => 'purple'],
            ] as $stat)
                <div class="card p-4 hover:shadow-md transition-all duration-300 hover:-translate-y-1 group relative overflow-hidden">
                    <div class="relative z-10">
                        <div class="flex items-start justify-between mb-2">
                            {{-- FIX: Icon Centering with Flexbox --}}
                            <div class="w-10 h-10 flex items-center justify-center rounded-xl bg-{{ $stat['color'] }}-50 dark:bg-{{ $stat['color'] }}-900/20 text-{{ $stat['color'] }}-600 dark:text-{{ $stat['color'] }}-400 group-hover:bg-{{ $stat['color'] }}-100 transition-colors shadow-sm">
                                <i class="material-icons text-xl">{{ $stat['icon'] }}</i>
                            </div>
                        </div>
                        <p class="text-[10px] font-bold text-slate-400 uppercase tracking-wide">{{ $stat['label'] }}</p>
                        <h4 class="text-base font-bold text-slate-800 dark:text-white mt-0.5 autonumeric" data-currency-symbol="Rp " data-digit-group-separator="." data-decimal-character=",">
                            {{ $stat['value'] }}
                        </h4>
                    </div>
                    {{-- Decorative Icon Small --}}
                    <i class="material-icons absolute -right-3 -bottom-3 text-6xl text-{{ $stat['color'] }}-500 opacity-5 rotate-12 transition-transform group-hover:scale-110 pointer-events-none">{{ $stat['icon'] }}</i>
                </div>
            @endforeach
        </div>

        {{-- CHART SECTION: TREND ANALYSIS --}}
        <div class="card mb-8 animate-enter" style="animation-delay: 0.2s" x-data="{ chartMode: 'line' }">
            <div class="card-header flex items-center justify-between py-4 border-b border-slate-100 dark:border-slate-700">
                <div class="flex items-center gap-3">
                    {{-- FIX: Icon Centering --}}
                    <div class="w-10 h-10 flex items-center justify-center bg-indigo-50 dark:bg-indigo-900/20 rounded-lg text-indigo-600">
                        <i class="material-icons">analytics</i>
                    </div>
                    <div>
                        <h3 class="text-sm font-bold text-slate-800 dark:text-white">Analisa Tren Keuangan</h3>
                        <p class="text-[10px] text-slate-400">Pemasukan vs Pengeluaran Global ({{ $selectedYear }})</p>
                    </div>
                </div>
                
                {{-- Chart Switcher --}}
                <div class="bg-slate-100 dark:bg-slate-800 p-1 rounded-lg flex items-center">
                    <button @click="chartMode = 'line'" 
                        :class="chartMode === 'line' ? 'bg-white dark:bg-slate-700 text-indigo-600 shadow-sm' : 'text-slate-500 hover:text-slate-700 dark:text-slate-400'"
                        class="px-3 py-1.5 text-xs font-bold rounded-md transition-all flex items-center gap-1">
                        <i class="material-icons text-[14px]">show_chart</i> 
                        <span class="hidden sm:inline">Line</span>
                    </button>
                    <button @click="chartMode = 'bar'" 
                        :class="chartMode === 'bar' ? 'bg-white dark:bg-slate-700 text-indigo-600 shadow-sm' : 'text-slate-500 hover:text-slate-700 dark:text-slate-400'"
                        class="px-3 py-1.5 text-xs font-bold rounded-md transition-all flex items-center gap-1">
                        <i class="material-icons text-[14px]">bar_chart</i> 
                        <span class="hidden sm:inline">Bar</span>
                    </button>
                </div>
            </div>
            
            <div class="card-body p-4 pt-6">
                <div class="relative w-full h-[320px] sm:h-[350px]">
                    <canvas id="trendLineChart" x-show="chartMode === 'line'" class="w-full h-full"></canvas>
                    <canvas id="trendBarChart" x-show="chartMode === 'bar'" style="display: none;" class="w-full h-full"></canvas>
                </div>
            </div>
        </div>

    @else
        {{-- ========================================================= --}}
        {{-- ALTERNATIVE VIEW: SALES / STAFF OPERATIONAL DASHBOARD --}}
        {{-- ========================================================= --}}
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6 mb-8 animate-enter">
            @php
                $opStats = [
                    ['label' => 'Total Penjualan Saya', 'value' => $stats['total_sales_value'], 'icon' => 'paid', 'color' => 'blue', 'curr' => true],
                    ['label' => 'Invoice Dibuat', 'value' => $stats['total_invoices'], 'icon' => 'description', 'color' => 'indigo', 'curr' => false],
                    ['label' => 'Invoice Lunas', 'value' => $stats['paid_invoices'], 'icon' => 'check_circle', 'color' => 'emerald', 'curr' => false],
                    ['label' => 'Piutang Pelanggan', 'value' => $stats['receivables_filtered'], 'icon' => 'pending', 'color' => 'amber', 'curr' => true],
                ];
            @endphp

            @foreach($opStats as $stat)
                <div class="card p-6 relative overflow-hidden group hover:shadow-md transition-all">
                    <div class="relative z-10 flex justify-between items-start">
                        <div>
                            <p class="text-xs font-bold text-{{ $stat['color'] }}-600 uppercase tracking-wide mb-1">{{ $stat['label'] }}</p>
                            <h3 class="text-2xl font-bold text-slate-800 dark:text-white {{ $stat['curr'] ? 'autonumeric' : '' }}" 
                                @if($stat['curr']) data-currency-symbol="Rp " data-digit-group-separator="." data-decimal-character="," @endif>
                                {{ $stat['curr'] ? $stat['value'] : number_format($stat['value']) }}
                            </h3>
                        </div>
                        {{-- FIX: Icon Centering --}}
                        <div class="w-12 h-12 flex items-center justify-center bg-{{ $stat['color'] }}-50 dark:bg-{{ $stat['color'] }}-900/20 rounded-xl text-{{ $stat['color'] }}-600 dark:text-{{ $stat['color'] }}-400 group-hover:scale-110 transition-transform shadow-sm">
                            <i class="material-icons text-xl">{{ $stat['icon'] }}</i>
                        </div>
                    </div>
                    {{-- Decorative Icon --}}
                    <i class="material-icons absolute -right-4 -bottom-4 text-8xl text-{{ $stat['color'] }}-500 opacity-[0.07] rotate-12 group-hover:scale-110 transition-transform pointer-events-none">{{ $stat['icon'] }}</i>
                </div>
            @endforeach
        </div>
    @endif

    {{-- ========================================================= --}}
    {{-- 4. PRODUCT INFO SECTION (2 COLUMNS) --}}
    {{-- ========================================================= --}}
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-8 animate-enter" style="animation-delay: 0.3s">
        
        {{-- A. PRODUCT SALES 3D --}}
        <div class="card flex flex-col h-full">
            <div class="card-header border-b border-slate-100 dark:border-slate-700 py-4 flex justify-between items-center">
                <div class="flex items-center gap-3">
                    {{-- FIX: Icon Centering --}}
                    <div class="w-10 h-10 flex items-center justify-center bg-purple-50 dark:bg-purple-900/20 rounded-lg text-purple-600">
                        <i class="material-icons">donut_small</i>
                    </div>
                    <div>
                        <h3 class="text-sm font-bold text-slate-800 dark:text-white">Performa Produk</h3>
                        <p class="text-[10px] text-slate-400">Berdasarkan invoice {{ $selectedUserId ? 'User Terpilih' : 'Semua User' }}</p>
                    </div>
                </div>
            </div>
            <div class="card-body p-4 flex flex-col sm:flex-row gap-4 h-full">
                <div class="w-full sm:w-5/12 flex items-center justify-center min-h-[220px]">
                    <div class="relative w-full h-[220px]">
                        <div id="topProducts3DChart" class="w-full h-full"></div>
                    </div>
                </div>
                <div class="w-full sm:w-7/12 border-l border-slate-100 dark:border-slate-700 pl-0 sm:pl-4">
                    <div class="flex items-center justify-between text-[10px] uppercase font-bold text-slate-400 mb-2 px-2">
                        <span>Nama Produk</span><span>Terjual</span>
                    </div>
                    <div id="customLegendContainer" class="overflow-y-auto custom-scrollbar max-h-[220px] pr-2">
                        {{-- Content Generated by JS --}}
                    </div>
                </div>
            </div>
        </div>

        {{-- B. LOW STOCK ALERT --}}
        <div class="card bg-red-50/50 dark:bg-red-900/10 border border-red-100 dark:border-red-900/30 overflow-hidden flex flex-col h-full">
            <div class="px-5 py-4 border-b border-red-100 dark:border-red-800/30 flex items-center gap-3">
                {{-- FIX: Icon Centering --}}
                <div class="w-10 h-10 flex items-center justify-center bg-red-100 dark:bg-red-800/30 rounded-lg text-red-600">
                    <i class="material-icons">warning_amber</i>
                </div>
                <h3 class="text-red-600 dark:text-red-400 text-sm font-bold uppercase tracking-wider">
                    Stok Kritis (< 10)
                </h3>
            </div>
            <div class="flex-1 overflow-y-auto custom-scrollbar max-h-[300px]">
                <ul class="divide-y divide-red-100/50 dark:divide-red-800/30">
                    @forelse($lowStockProducts as $product)
                        <li class="flex justify-between items-center p-4 hover:bg-red-100/50 dark:hover:bg-red-900/20 transition">
                            <div class="min-w-0 pr-4">
                                <p class="text-sm font-bold text-slate-700 dark:text-slate-300 truncate">{{ $product->product_name }}</p>
                                <p class="text-[10px] text-slate-500 mt-0.5">{{ $product->product_code }}</p>
                            </div>
                            <span class="badge bg-red-100 text-red-700 border border-red-200 text-xs px-2.5 py-1 whitespace-nowrap">
                                {{ (float)$product->stock_quantity }} {{ $product->unit->name ?? '' }}
                            </span>
                        </li>
                    @empty
                        <li class="p-8 text-center text-slate-400 italic flex flex-col items-center justify-center h-full">
                            <i class="material-icons text-3xl mb-2 opacity-50">inventory</i>
                            <span class="text-xs">Stok aman terkendali.</span>
                        </li>
                    @endforelse
                </ul>
            </div>
        </div>
    </div>

    {{-- ========================================================= --}}
    {{-- 5. RECENT TRANSACTIONS TABLE (FILTERED BY USER HERE) --}}
    {{-- ========================================================= --}}
    <div class="card animate-enter" style="animation-delay: 0.4s">
        <div class="card-header flex flex-col sm:flex-row items-center justify-between py-4 px-6 border-b border-slate-100 dark:border-slate-700 gap-4">
            <div class="flex items-center gap-3">
                {{-- FIX: Icon Centering --}}
                <div class="w-10 h-10 flex items-center justify-center bg-blue-50 dark:bg-blue-900/20 rounded-lg text-blue-600">
                    <i class="material-icons">receipt_long</i>
                </div>
                <div>
                    <h3 class="font-bold text-slate-800 dark:text-white">Transaksi Terakhir</h3>
                    <p class="text-xs text-slate-500 hidden sm:block">Daftar penjualan terbaru.</p>
                </div>
            </div>

            <div class="flex items-center gap-3 w-full sm:w-auto">
                {{-- FILTER USER HANYA DISINI (Jika Admin) --}}
                @if($isAdmin)
                    <form method="GET" action="{{ route('admin.dashboard') }}" class="w-full sm:w-64">
                        {{-- Keep year if selected --}}
                        <input type="hidden" name="year" value="{{ $selectedYear }}">
                        
                        <select name="filter_user_id" class="tom-select-filter" placeholder="Filter Sales/User..." onchange="this.form.submit()">
                            <option value="">Semua Sales</option>
                            @foreach($allUsers as $u)
                                <option value="{{ $u->user_id }}" {{ $selectedUserId == $u->user_id ? 'selected' : '' }}>
                                    {{ $u->full_name }}
                                </option>
                            @endforeach
                        </select>
                    </form>
                @endif

                <a href="{{ route('admin.invoices.index') }}" class="btn btn-secondary btn-sm whitespace-nowrap">
                    Lihat Semua <i class="material-icons text-sm ml-1">arrow_forward</i>
                </a>
            </div>
        </div>
        <div class="table-container">
            <table class="table-modern w-full">
                <thead>
                    <tr>
                        <th class="pl-6">Invoice #</th>
                        <th>Tanggal</th>
                        <th>Klien</th>
                        <th class="hidden sm:table-cell">Sales</th>
                        <th class="text-right">Total</th>
                        <th class="text-center pr-6">Status</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 dark:divide-slate-700">
                    @forelse($recentInvoices as $inv)
                        <tr>
                            <td class="pl-6 py-3">
                                <a href="{{ route('admin.invoices.show', $inv->invoice_id) }}" class="group flex flex-col">
                                    <span class="font-bold text-indigo-600 dark:text-indigo-400 text-sm group-hover:underline">{{ $inv->invoice_number }}</span>
                                </a>
                            </td>
                            <td class="py-3 text-xs text-slate-500">{{ $inv->order_date->format('d M Y') }}</td>
                            <td class="py-3"><div class="text-sm font-medium text-slate-700 dark:text-slate-300 max-w-[180px] truncate">{{ $inv->client->client_name ?? 'Umum' }}</div></td>
                            <td class="py-3 hidden sm:table-cell">
                                <div class="flex items-center gap-2">
                                    <div class="w-6 h-6 rounded-full bg-slate-100 dark:bg-slate-700 text-slate-600 dark:text-slate-400 text-[10px] font-bold flex items-center justify-center border border-slate-200 dark:border-slate-600">{{ substr($inv->sales->full_name ?? 'S', 0, 1) }}</div>
                                    <span class="text-xs text-slate-600 dark:text-slate-400 truncate max-w-[100px]">{{ $inv->sales->full_name ?? 'Sistem' }}</span>
                                </div>
                            </td>
                            <td class="py-3 text-right"><span class="font-bold text-slate-700 dark:text-slate-200 text-sm autonumeric" data-currency-symbol="Rp " data-digit-group-separator="." data-decimal-character=",">{{ $inv->total_amount }}</span></td>
                            <td class="py-3 pr-6 text-center">
                                @if($inv->status == 'paid') <span class="badge badge-success">Lunas</span>
                                @elseif($inv->status == 'unpaid') <span class="badge badge-danger">Belum Bayar</span>
                                @elseif($inv->status == 'partially_paid') <span class="badge badge-warning">Parsial</span>
                                @else <span class="badge badge-primary">{{ ucfirst($inv->status) }}</span> @endif
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="6" class="text-center py-12"><div class="flex flex-col items-center justify-center opacity-50"><i class="material-icons text-4xl text-slate-300 mb-2">receipt_long</i><p class="text-sm text-slate-500">Belum ada data transaksi.</p></div></td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
@endsection

@push('scripts')
{{-- Libraries --}}
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script src="https://code.highcharts.com/highcharts.js"></script>
<script src="https://code.highcharts.com/highcharts-3d.js"></script>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        
        // 1. UI Init
        document.querySelectorAll('.tom-select-filter').forEach(el => {
            new TomSelect(el, { create: false, sortField: { field: "text", direction: "asc" }, dropdownParent: 'body', controlClass: 'ts-control !min-h-[38px] !h-[38px] !rounded-xl !border-slate-300 dark:!border-slate-600 !bg-white dark:!bg-slate-800 dark:!text-slate-200' });
        });

        document.querySelectorAll('.autonumeric').forEach(el => {
            if (AutoNumeric.getAutoNumericElement(el)) return;
            new AutoNumeric(el, { 
                digitGroupSeparator: '.', 
                decimalCharacter: ',', 
                currencySymbol: el.dataset.currencySymbol || '', 
                currencySymbolPlacement: 'p', 
                roundingMethod: 'U', 
                minimumValue: '-999999999999999', 
                maximumValue: '999999999999999', 
                decimalPlaces: 0 
            });
        });

        // 2. Data Preparation
        const isDarkMode = document.documentElement.classList.contains('dark');
        const textColor = isDarkMode ? '#cbd5e1' : '#475569';
        const gridColor = isDarkMode ? '#334155' : '#e2e8f0';

        const months = @json($charts['months']);
        const incomeData = @json($charts['trend_data_income']);
        const expenseData = @json($charts['trend_data_expense']);
        const productLabels = @json($charts['top_products_labels'] ?? []);
        const rawProductData = @json($charts['top_products_data'] ?? []);
        const productData = rawProductData.map(val => parseFloat(val) || 0);

        function generateColors(count) {
            const colors = [], base = ['#6366f1', '#8b5cf6', '#d946ef', '#ec4899', '#f43f5e', '#f59e0b', '#10b981', '#06b6d4', '#3b82f6'];
            for (let i = 0; i < count; i++) colors.push(i < base.length ? base[i] : `hsl(${(i * 137) % 360}, 70%, 60%)`);
            return { bg: colors };
        }
        const productColors = generateColors(productLabels.length);

        // 3. Trend Chart (Only if Financials visible)
        const ctxLine = document.getElementById('trendLineChart');
        if (ctxLine) {
            const ctx = ctxLine.getContext('2d');
            let gradIncome = ctx.createLinearGradient(0, 0, 0, 300);
            gradIncome.addColorStop(0, 'rgba(99, 102, 241, 0.3)'); gradIncome.addColorStop(1, 'rgba(99, 102, 241, 0.0)');

            // Base Options
            const baseOptions = {
                responsive: true, maintainAspectRatio: false,
                layout: { padding: { top: 20, bottom: 10, left: 10, right: 25 } },
                plugins: {
                    legend: { position: 'top', align: 'end', labels: { color: textColor, usePointStyle: true, boxWidth: 8, font: {family: 'Inter', size: 11}, padding: 20 } },
                    tooltip: { mode: 'index', intersect: false, backgroundColor: isDarkMode ? '#1e293b' : '#ffffff', titleColor: isDarkMode ? '#fff' : '#0f172a', bodyColor: isDarkMode ? '#cbd5e1' : '#475569', borderColor: isDarkMode ? '#334155' : '#e2e8f0', borderWidth: 1, padding: 12, cornerRadius: 8, callbacks: { label: function(context) { let label = context.dataset.label || ''; if (label) label += ': '; if (context.parsed.y !== null) label += new Intl.NumberFormat('id-ID', { style: 'currency', currency: 'IDR', maximumFractionDigits: 0 }).format(context.parsed.y); return label; } } }
                },
                interaction: { mode: 'nearest', axis: 'x', intersect: false }
            };

            // Line Chart
            new Chart(ctx, {
                type: 'line',
                data: {
                    labels: months,
                    datasets: [
                        { label: 'Pemasukan', data: incomeData, borderColor: '#6366f1', backgroundColor: gradIncome, tension: 0.4, fill: true, pointRadius: 4, pointHoverRadius: 6 },
                        { label: 'Pengeluaran', data: expenseData, borderColor: '#f43f5e', backgroundColor: 'transparent', tension: 0.4, borderDash: [5, 5], pointRadius: 4, pointHoverRadius: 6 }
                    ]
                },
                options: {
                    ...baseOptions,
                    scales: {
                        y: { beginAtZero: true, grace: '20%', grid: { borderDash: [4, 4], color: gridColor, drawBorder: false }, ticks: { color: textColor, font: { size: 10, family: 'Inter' }, padding: 10 } },
                        x: { grid: { display: false, drawBorder: false }, ticks: { color: textColor, font: { size: 10, family: 'Inter' }, padding: 10 } }
                    }
                }
            });

            // Bar Chart (Fixed Side-by-Side)
            const ctxBar = document.getElementById('trendBarChart').getContext('2d');
            new Chart(ctxBar, {
                type: 'bar',
                data: {
                    labels: months,
                    datasets: [
                        { label: 'Pemasukan', data: incomeData, backgroundColor: '#6366f1', borderRadius: 4, barPercentage: 0.6, categoryPercentage: 0.8 },
                        { label: 'Pengeluaran', data: expenseData, backgroundColor: '#f43f5e', borderRadius: 4, barPercentage: 0.6, categoryPercentage: 0.8 }
                    ]
                },
                options: {
                    ...baseOptions,
                    scales: {
                        y: { beginAtZero: true, grace: '20%', grid: { borderDash: [4, 4], color: gridColor, drawBorder: false }, ticks: { color: textColor, font: { size: 10, family: 'Inter' }, padding: 10 } },
                        x: { grid: { display: false, drawBorder: false }, ticks: { color: textColor, font: { size: 10, family: 'Inter' }, padding: 10 }, stacked: false } // FIX: Ensure stacked is FALSE
                    }
                }
            });
        }

        // 4. Product Chart (Highcharts 3D)
        if (typeof Highcharts !== 'undefined' && document.getElementById('topProducts3DChart')) {
            const highchartsData = productLabels.map((l, i) => ({ name: l, y: productData[i], color: productColors.bg[i] }));
            const productChart = Highcharts.chart('topProducts3DChart', {
                chart: { type: 'pie', backgroundColor: 'transparent', options3d: { enabled: true, alpha: 45, beta: 0 }, style: { fontFamily: 'Inter' } },
                title: { text: null }, credits: { enabled: false },
                tooltip: { backgroundColor: isDarkMode ? '#1e293b' : '#ffffff', borderColor: isDarkMode ? '#334155' : '#e2e8f0', style: { color: textColor }, pointFormat: '<b>{point.y} Unit</b> ({point.percentage:.1f}%)' },
                plotOptions: { pie: { innerSize: '50%', depth: 45, allowPointSelect: true, cursor: 'pointer', dataLabels: { enabled: false }, showInLegend: false, borderWidth: 0 } },
                series: [{ name: 'Terjual', data: highchartsData }]
            });

            function generateHTMLLegend() {
                const container = document.getElementById('customLegendContainer');
                if (!container) return;
                container.innerHTML = ''; 
                const totalQty = productData.reduce((a, b) => a + b, 0);
                if (productLabels.length === 0) { container.innerHTML = '<p class="text-center text-xs text-slate-400 mt-4 italic">Belum ada data.</p>'; return; }
                const ul = document.createElement('ul'); ul.className = 'divide-y divide-slate-100 dark:divide-slate-700/50';
                productLabels.forEach((label, index) => {
                    const li = document.createElement('li');
                    li.className = 'flex items-center justify-between py-2.5 px-2 hover:bg-slate-50 dark:hover:bg-slate-800/50 transition rounded-lg cursor-pointer';
                    const pct = totalQty > 0 ? ((productData[index] / totalQty) * 100).toFixed(1) + '%' : '0%';
                    li.innerHTML = `<div class="flex items-center gap-3 min-w-0"><span class="w-3 h-3 rounded-full shrink-0 shadow-sm ring-2 ring-white dark:ring-slate-800" style="background-color: ${productColors.bg[index]};"></span><div class="flex flex-col min-w-0"><span class="text-xs font-semibold text-slate-700 dark:text-slate-300 truncate max-w-[140px]" title="${label}">${label}</span><span class="text-[10px] text-slate-400">${pct}</span></div></div><span class="text-xs font-bold text-slate-700 dark:text-slate-300 bg-slate-100 dark:bg-slate-700 px-2 py-0.5 rounded-md">${productData[index]}</span>`;
                    li.addEventListener('mouseenter', () => { if(productChart.series[0].data[index]) { productChart.series[0].data[index].setState('hover'); productChart.tooltip.refresh(productChart.series[0].data[index]); } });
                    li.addEventListener('mouseleave', () => { if(productChart.series[0].data[index]) { productChart.series[0].data[index].setState(''); productChart.tooltip.hide(); } });
                    ul.appendChild(li);
                });
                container.appendChild(ul);
            }
            generateHTMLLegend();
        }
    });
</script>
@endpush
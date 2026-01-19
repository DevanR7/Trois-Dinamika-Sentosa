@extends('admin.layouts.app')

@section('title', 'Dashboard')

@section('content')
    {{-- ========================================================= --}}
    {{-- 1. HEADER & GLOBAL FILTER --}}
    {{-- ========================================================= --}}
    <div class="flex flex-col md:flex-row md:items-end justify-between gap-4 mb-8 animate-enter">
        <div>
            <h1 class="text-3xl font-extrabold text-slate-900 dark:text-white tracking-tight leading-tight flex items-center gap-3">
                <i class="material-icons text-3xl text-indigo-500">space_dashboard</i> Dashboard
            </h1>
            <div class="mt-2 ml-1">
                <p class="text-sm text-slate-500 dark:text-slate-400">
                    Selamat datang kembali, <span class="font-bold text-indigo-600 dark:text-indigo-400">{{ Auth::user()->full_name }}</span>.
                </p>
                <p class="text-xs font-medium text-slate-400 mt-1 flex items-center gap-1.5">
                    <i class="material-icons text-[14px] text-slate-400">event</i>
                    {{ \Carbon\Carbon::now()->isoFormat('dddd, D MMMM Y') }}
                </p>
            </div>
        </div>

        <div class="bg-white dark:bg-slate-800 p-1.5 rounded-2xl shadow-sm border border-slate-200 dark:border-slate-700 flex items-center gap-2">
            <div class="pl-3 pr-2 border-r border-slate-100 dark:border-slate-700">
                <span class="text-[10px] font-bold text-slate-400 uppercase tracking-wider">Periode Data</span>
            </div>
            <div class="w-36"> 
                <form method="GET" action="{{ route('admin.dashboard') }}">
                    @if(request('filter_user_id'))
                        <input type="hidden" name="filter_user_id" value="{{ request('filter_user_id') }}">
                    @endif
                    
                    <select name="year" class="tom-select-filter" onchange="this.form.submit()" autocomplete="off">
                        @foreach($availableYears as $yr)
                            <option value="{{ $yr }}" {{ $selectedYear == $yr ? 'selected' : '' }}>Tahun {{ $yr }}</option>
                        @endforeach
                    </select>
                </form>
            </div>
        </div>
    </div>

    @if($canViewFinancials && !$selectedUserId)
        {{-- ========================================================= --}}
        {{-- 2. FINANCIAL OVERVIEW --}}
        {{-- ========================================================= --}}
        <div class="grid grid-cols-1 lg:grid-cols-12 gap-6 mb-6 animate-enter">
            
            {{-- A. PRIMARY STATS (REVENUE & PROFIT) --}}
        <div class="lg:col-span-8 grid grid-cols-1 md:grid-cols-2 gap-6">
                
                {{-- Revenue Card --}}
                <a href="{{ route('admin.reports.index') }}" class="relative group overflow-hidden rounded-2xl bg-gradient-to-br from-slate-800 to-[#0f172a] p-6 shadow-lg transition-all duration-300 hover:-translate-y-1 hover:shadow-2xl hover:ring-2 hover:ring-indigo-500/50">
                    <div class="relative z-10 flex flex-col justify-between h-full">
                        <div class="flex items-center justify-between mb-4">
                            <div class="flex items-center gap-3">
                                <div class="w-10 h-10 flex items-center justify-center bg-white/10 rounded-lg backdrop-blur-sm text-indigo-300 shadow-inner group-hover:bg-white/20 transition-colors">
                                    <i class="material-icons">account_balance_wallet</i>
                                </div>
                                <span class="text-xs font-bold text-slate-300 uppercase tracking-wider group-hover:text-white transition-colors">Total Pemasukan</span>
                            </div>
                            
                            {{-- [BARU] Indikator Pertumbuhan Revenue --}}
                            @php $isRevUp = $stats['growth_revenue'] >= 0; @endphp
                            <div class="flex items-center gap-1 px-2 py-1 rounded-lg {{ $isRevUp ? 'bg-emerald-500/20 text-emerald-400' : 'bg-rose-500/20 text-rose-400' }} border {{ $isRevUp ? 'border-emerald-500/30' : 'border-rose-500/30' }}">
                                <i class="material-icons text-[14px]">{{ $isRevUp ? 'trending_up' : 'trending_down' }}</i>
                                <span class="text-[10px] font-bold">{{ abs($stats['growth_revenue']) }}%</span>
                            </div>
                        </div>
                        <div>
                            <h3 class="text-3xl sm:text-4xl font-extrabold text-white tracking-tight autonumeric" data-currency-symbol="Rp " data-digit-group-separator="." data-decimal-character=",">
                                {{ $stats['revenue'] }}
                            </h3>
                            <div class="mt-2 flex items-center justify-between">
                                <span class="badge bg-emerald-500/20 text-emerald-300 border border-emerald-500/30 px-2 py-0.5 rounded text-[10px] flex items-center gap-1">
                                    <i class="material-icons text-[10px]">arrow_upward</i> Cash In (Global)
                                </span>
                                <span class="text-[10px] text-slate-500">vs Tahun {{ $selectedYear - 1 }}</span>
                            </div>
                        </div>
                    </div>
                    <i class="material-icons absolute -right-4 -bottom-8 text-[140px] text-white opacity-[0.03] rotate-12 group-hover:scale-110 transition-transform duration-500 pointer-events-none">payments</i>
                </a>

                {{-- Profit Card --}}
                <a href="{{ route('admin.reports.index') }}" class="relative group overflow-hidden rounded-2xl bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700 p-6 shadow-sm transition-all duration-300 hover:-translate-y-1 hover:border-indigo-500 hover:shadow-lg">
                    <div class="relative z-10 flex flex-col justify-between h-full">
                        <div class="flex items-center justify-between mb-4">
                            <div class="flex items-center gap-3">
                                <div class="w-10 h-10 flex items-center justify-center bg-emerald-50 dark:bg-emerald-900/20 rounded-lg text-emerald-600 dark:text-emerald-400 group-hover:bg-emerald-100 dark:group-hover:bg-emerald-900/40 transition-colors">
                                    <i class="material-icons">savings</i>
                                </div>
                                <span class="text-xs font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wider group-hover:text-emerald-600 transition-colors">Net Cash Flow</span>
                            </div>

                            {{-- [BARU] Indikator Pertumbuhan Profit --}}
                            @php $isProfUp = $stats['growth_profit'] >= 0; @endphp
                            <div class="flex items-center gap-1 px-2 py-1 rounded-lg {{ $isProfUp ? 'bg-emerald-100 text-emerald-700 dark:bg-emerald-900/30 dark:text-emerald-400' : 'bg-rose-100 text-rose-700 dark:bg-rose-900/30 dark:text-rose-400' }}">
                                <i class="material-icons text-[14px]">{{ $isProfUp ? 'trending_up' : 'trending_down' }}</i>
                                <span class="text-[10px] font-bold">{{ abs($stats['growth_profit']) }}%</span>
                            </div>
                        </div>
                        <div>
                            <h3 class="text-3xl sm:text-4xl font-extrabold text-emerald-600 dark:text-emerald-400 tracking-tight autonumeric" data-currency-symbol="Rp " data-digit-group-separator="." data-decimal-character=",">
                                {{ $stats['net_profit'] }}
                            </h3>
                            <div class="mt-2 flex items-center justify-between">
                                <span class="text-[10px] text-slate-400 font-medium flex items-center gap-1">
                                    <i class="material-icons text-[10px]">calculate</i> (Pemasukan - Pengeluaran)
                                </span>
                                <span class="text-[10px] text-slate-400">vs Tahun {{ $selectedYear - 1 }}</span>
                            </div>
                        </div>
                    </div>
                    <i class="material-icons absolute -right-4 -bottom-6 text-[130px] text-emerald-500 opacity-[0.05] -rotate-12 group-hover:scale-110 transition-transform duration-500 pointer-events-none">monetization_on</i>
                </a>
            </div>

            {{-- B. BUTUH PERHATIAN (Top Right) --}}
            <div class="lg:col-span-4 card h-full flex flex-col shadow-sm bg-white dark:bg-slate-800">
                <div class="card-header py-4 border-b border-slate-100 dark:border-slate-700 flex justify-between items-center">
                    <h3 class="card-header-title text-sm">Butuh Perhatian</h3>
                    @if(array_sum($pendingActions) > 0)
                        <span class="badge badge-warning text-[10px] py-0.5 px-2">{{ array_sum($pendingActions) }} Items</span>
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
                                <a href="{{ route('admin.invoices.index', ['status' => 'draft']) }}" class="flex items-center justify-between px-5 py-3.5 hover:bg-slate-50 dark:hover:bg-slate-700/50 transition group">
                                    <div class="flex items-center gap-3">
                                        <div class="w-8 h-8 rounded-full bg-slate-100 dark:bg-slate-700 flex items-center justify-center text-slate-500">
                                            <i class="material-icons text-sm">edit_note</i>
                                        </div>
                                        <div>
                                            <p class="text-xs font-bold text-slate-700 dark:text-slate-200">Draft Invoice</p>
                                            <p class="text-[10px] text-slate-500">Belum dikonfirmasi</p>
                                        </div>
                                    </div>
                                    <span class="badge badge-warning py-0.5 px-2 text-[10px]">{{ $pendingActions['invoice_draft'] }}</span>
                                </a>
                            </li>
                            @endif
                            
                            @can('manage-payment-clearance')
                                @if($pendingActions['pending_payments'] > 0)
                                <li>
                                    <a href="{{ route('admin.payment-clearance.index') }}" class="flex items-center justify-between px-5 py-3.5 hover:bg-slate-50 dark:hover:bg-slate-700/50 transition group">
                                        <div class="flex items-center gap-3">
                                            <div class="w-8 h-8 rounded-full bg-blue-50 dark:bg-blue-900/20 flex items-center justify-center text-blue-500">
                                                <i class="material-icons text-sm">verified</i>
                                            </div>
                                            <div>
                                                <p class="text-xs font-bold text-slate-700 dark:text-slate-200">Verifikasi Bayar</p>
                                                <p class="text-[10px] text-slate-500">Menunggu Approval</p>
                                            </div>
                                        </div>
                                        <span class="badge badge-info py-0.5 px-2 text-[10px]">{{ $pendingActions['pending_payments'] }}</span>
                                    </a>
                                </li>
                                @endif
                            @endcan

                            @if($pendingActions['po_draft'] > 0)
                            <li>
                                <a href="{{ route('admin.purchase-orders.index', ['status' => 'draft']) }}" class="flex items-center justify-between px-5 py-3.5 hover:bg-slate-50 dark:hover:bg-slate-700/50 transition group">
                                    <div class="flex items-center gap-3">
                                        <div class="w-8 h-8 rounded-full bg-indigo-50 dark:bg-indigo-900/20 flex items-center justify-center text-indigo-500">
                                            <i class="material-icons text-sm">shopping_cart</i>
                                        </div>
                                        <div>
                                            <p class="text-xs font-bold text-slate-700 dark:text-slate-200">PO Draft</p>
                                            <p class="text-[10px] text-slate-500">Belum diproses</p>
                                        </div>
                                    </div>
                                    <span class="badge badge-primary py-0.5 px-2 text-[10px]">{{ $pendingActions['po_draft'] }}</span>
                                </a>
                            </li>
                            @endif

                            @if($pendingActions['stock_alert'] > 0)
                            <li>
                                <a href="{{ route('admin.products.index', ['sort' => 'stok-sedikit']) }}" class="flex items-center justify-between px-5 py-3.5 hover:bg-slate-50 dark:hover:bg-slate-700/50 transition group">
                                    <div class="flex items-center gap-3">
                                        <div class="w-8 h-8 rounded-full bg-rose-50 dark:bg-rose-900/20 flex items-center justify-center text-rose-500">
                                            <i class="material-icons text-sm">warning</i>
                                        </div>
                                        <div>
                                            <p class="text-xs font-bold text-slate-700 dark:text-slate-200">Stok Menipis</p>
                                            <p class="text-[10px] text-slate-500">Segera restock</p>
                                        </div>
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
        @php
            $secondaryStats = [
                ['label' => 'Total Beban', 'value' => $stats['expense'], 'icon' => 'trending_down', 'color' => 'rose', 'route' => route('admin.expenses.index')],
                ['label' => 'Piutang Global', 'value' => $stats['receivables_global'], 'icon' => 'pending', 'color' => 'amber', 'route' => route('admin.invoices.index', ['status' => 'unpaid'])],
                ['label' => 'Hutang Dagang', 'value' => $stats['payables'], 'icon' => 'money_off', 'color' => 'orange', 'route' => route('admin.purchase-orders.index', ['payment_status' => 'unpaid'])],
                ['label' => 'Sisa Pinjaman', 'value' => $stats['loans'], 'icon' => 'account_balance', 'color' => 'blue', 'route' => route('admin.loans.index')],
                ['label' => 'Nilai Aset', 'value' => $stats['assets'], 'icon' => 'domain', 'color' => 'purple', 'route' => route('admin.fixed-assets.index')]
            ];
        @endphp

        <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-5 gap-4 mb-8 animate-enter" style="animation-delay: 0.1s">
            @foreach($secondaryStats as $stat)
                <a href="{{ $stat['route'] }}" class="card p-4 hover:shadow-md transition-all duration-300 hover:-translate-y-1 group relative overflow-hidden bg-white dark:bg-slate-800 border border-transparent hover:border-{{ $stat['color'] }}-200 dark:hover:border-{{ $stat['color'] }}-800">
                    <div class="relative z-10">
                        <div class="flex items-start justify-between mb-2">
                            <div class="w-10 h-10 flex items-center justify-center rounded-xl bg-{{ $stat['color'] }}-50 dark:bg-{{ $stat['color'] }}-900/20 text-{{ $stat['color'] }}-600 dark:text-{{ $stat['color'] }}-400 group-hover:bg-{{ $stat['color'] }}-100 transition-colors shadow-sm">
                                <i class="material-icons text-xl">{{ $stat['icon'] }}</i>
                            </div>
                            <i class="material-icons text-slate-300 opacity-0 group-hover:opacity-100 transition-opacity text-sm">chevron_right</i>
                        </div>
                        <p class="text-[10px] font-bold text-slate-400 uppercase tracking-wide">{{ $stat['label'] }}</p>
                        <h4 class="text-base font-bold text-slate-800 dark:text-white mt-0.5 autonumeric" data-currency-symbol="Rp " data-digit-group-separator="." data-decimal-character=",">{{ $stat['value'] }}</h4>
                    </div>
                    <i class="material-icons absolute -right-3 -bottom-3 text-6xl text-{{ $stat['color'] }}-500 opacity-5 rotate-12 transition-transform group-hover:scale-110 pointer-events-none">{{ $stat['icon'] }}</i>
                </a>
            @endforeach
        </div>

        {{-- KPI & FORECASTING WIDGETS --}}
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 mb-6 animate-enter" style="animation-delay: 0.15s">
            {{-- SALES TARGET KPI --}}
            <div class="card p-5 bg-white dark:bg-slate-800 border-l-4 border-blue-500 shadow-sm">
                <div class="flex justify-between items-end mb-2">
                    <div>
                        <p class="text-xs font-bold text-slate-400 uppercase tracking-wide">Target Sales Bulan Ini</p>
                        <h4 class="text-xl font-bold text-slate-800 dark:text-white mt-1 autonumeric" data-currency-symbol="Rp " data-digit-group-separator="." data-decimal-character=",">{{ $forecast['current_monthly_sales'] }}</h4>
                    </div>
                    <div class="text-right">
                        <span class="text-xs text-slate-400">Target: <span class="autonumeric font-semibold" data-currency-symbol="Rp " data-digit-group-separator="." data-decimal-character=",">{{ $forecast['monthly_target'] }}</span></span>
                        <p class="text-2xl font-bold text-blue-600">{{ $forecast['target_percentage'] }}%</p>
                    </div>
                </div>
                <div class="w-full bg-slate-100 rounded-full h-3 dark:bg-slate-700 overflow-hidden">
                    <div class="bg-blue-500 h-3 rounded-full transition-all duration-1000 ease-out relative" style="width: {{ $forecast['target_percentage'] }}%">
                        <div class="absolute inset-0 bg-white/20 animate-[shimmer_2s_infinite]"></div>
                    </div>
                </div>
            </div>

            {{-- CASH FLOW FORECAST --}}
            <div class="lg:col-span-2 card p-5 bg-white dark:bg-slate-800 border-l-4 border-purple-500 shadow-sm">
                <div class="flex items-center gap-2 mb-4">
                    <div class="p-1.5 bg-purple-50 dark:bg-purple-900/20 rounded-lg text-purple-600">
                        <i class="material-icons text-lg">insights</i>
                    </div>
                    <div>
                        <h3 class="font-bold text-slate-800 dark:text-white text-sm">Proyeksi Arus Kas (30 Hari)</h3>
                        <p class="text-[10px] text-slate-400">Estimasi berdasarkan jatuh tempo invoice & PO.</p>
                    </div>
                </div>
                
                <div class="grid grid-cols-3 gap-4 text-center divide-x divide-slate-100 dark:divide-slate-700">
                    <div>
                        <p class="text-[10px] text-slate-400 uppercase font-bold mb-1">Akan Masuk (Piutang)</p>
                        <p class="text-lg font-bold text-emerald-600 autonumeric" data-currency-symbol="Rp " data-digit-group-separator="." data-decimal-character=",">{{ $forecast['incoming_30_days'] }}</p>
                    </div>
                    <div>
                        <p class="text-[10px] text-slate-400 uppercase font-bold mb-1">Akan Keluar (Hutang)</p>
                        <p class="text-lg font-bold text-rose-600 autonumeric" data-currency-symbol="Rp " data-digit-group-separator="." data-decimal-character=",">{{ $forecast['outgoing_30_days'] }}</p>
                    </div>
                    <div>
                        <p class="text-[10px] text-slate-400 uppercase font-bold mb-1">Estimasi Surplus</p>
                        <p class="text-lg font-bold {{ $forecast['net_forecast'] >= 0 ? 'text-indigo-600' : 'text-amber-600' }} autonumeric" data-currency-symbol="Rp " data-digit-group-separator="." data-decimal-character=",">{{ $forecast['net_forecast'] }}</p>
                    </div>
                </div>
            </div>
        </div>

        {{-- D. ANALISA ARUS KAS (FULL WIDTH) --}}
        <div class="mb-8 animate-enter" style="animation-delay: 0.2s" x-data="{ chartMode: 'line' }">
            <div class="card bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700 shadow-sm">
                <div class="card-header flex items-center justify-between py-5 px-6 border-b border-slate-100 dark:border-slate-700">
                    <div class="flex items-center gap-3">
                        <div class="w-10 h-10 flex items-center justify-center bg-indigo-50 dark:bg-indigo-900/20 rounded-lg text-indigo-600">
                            <i class="material-icons">analytics</i>
                        </div>
                        <div>
                            <h3 class="text-base font-bold text-slate-800 dark:text-white">Analisa Arus Kas</h3>
                            <p class="text-xs text-slate-400">Cash In vs Cash Out ({{ $selectedYear }})</p>
                        </div>
                    </div>
                    {{-- Chart Switcher --}}
                    <div class="bg-slate-100 dark:bg-slate-800 p-1 rounded-lg flex items-center">
                        <button @click="chartMode = 'line'" :class="chartMode === 'line' ? 'bg-white dark:bg-slate-700 text-indigo-600 shadow-sm' : 'text-slate-500 hover:text-slate-700 dark:text-slate-400'" class="px-3 py-1.5 text-xs font-bold rounded-md transition-all flex items-center gap-1">
                            <i class="material-icons text-[14px]">show_chart</i><span class="hidden sm:inline">Line</span>
                        </button>
                        <button @click="chartMode = 'bar'" :class="chartMode === 'bar' ? 'bg-white dark:bg-slate-700 text-indigo-600 shadow-sm' : 'text-slate-500 hover:text-slate-700 dark:text-slate-400'" class="px-3 py-1.5 text-xs font-bold rounded-md transition-all flex items-center gap-1">
                            <i class="material-icons text-[14px]">bar_chart</i><span class="hidden sm:inline">Bar</span>
                        </button>
                    </div>
                </div>
                {{-- Chart Area --}}
                <div class="card-body p-6">
                    <div class="relative w-full h-[450px]"> 
                        <canvas id="trendLineChart" x-show="chartMode === 'line'" class="w-full h-full"></canvas>
                        <canvas id="trendBarChart" x-show="chartMode === 'bar'" style="display: none;" class="w-full h-full"></canvas>
                    </div>
                </div>
            </div>
        </div>

        {{-- [REVISI] E. GRID 3 KOLOM: AKSI CEPAT | TOP PELANGGAN | PENGELUARAN --}}
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 mb-8 animate-enter" style="animation-delay: 0.3s">
            
            {{-- 1. Aksi Cepat (Lengkap & Direct Index) --}}
            <div class="card p-5 bg-gradient-to-br from-[#0f172a] to-slate-800 text-white border-none shadow-lg h-full flex flex-col">
                <h3 class="font-bold text-lg mb-1 flex items-center gap-2">
                    <i class="material-icons text-sky-400">bolt</i> Aksi Cepat
                </h3>
                <p class="text-slate-400 text-xs mb-4">Pintasan ke halaman utama.</p>
                
                <div class="grid grid-cols-2 gap-3 overflow-y-auto custom-scrollbar max-h-[300px] pr-1">
                    @can('view-invoices')
                    <a href="{{ route('admin.invoices.index') }}" class="flex flex-col items-center justify-center p-3 rounded-xl bg-white/10 hover:bg-white/20 transition backdrop-blur-sm border border-white/5 group text-center">
                        <i class="material-icons mb-1 text-emerald-400 group-hover:scale-110 transition-transform text-2xl">receipt_long</i>
                        <span class="text-[10px] font-bold uppercase tracking-wide">Invoice</span>
                    </a>
                    @endcan

                    @can('view-purchase-orders')
                    <a href="{{ route('admin.purchase-orders.index') }}" class="flex flex-col items-center justify-center p-3 rounded-xl bg-white/10 hover:bg-white/20 transition backdrop-blur-sm border border-white/5 group text-center">
                        <i class="material-icons mb-1 text-amber-400 group-hover:scale-110 transition-transform text-2xl">shopping_cart</i>
                        <span class="text-[10px] font-bold uppercase tracking-wide">Purchase Order</span>
                    </a>
                    @endcan

                    @can('view-products')
                    <a href="{{ route('admin.products.index') }}" class="flex flex-col items-center justify-center p-3 rounded-xl bg-white/10 hover:bg-white/20 transition backdrop-blur-sm border border-white/5 group text-center">
                        <i class="material-icons mb-1 text-purple-400 group-hover:scale-110 transition-transform text-2xl">inventory_2</i>
                        <span class="text-[10px] font-bold uppercase tracking-wide">Produk</span>
                    </a>
                    @endcan

                    @can('create-bulk-sales-payments')
                    <a href="{{ route('admin.bulk-sales-payments.index') }}" class="flex flex-col items-center justify-center p-3 rounded-xl bg-white/10 hover:bg-white/20 transition backdrop-blur-sm border border-white/5 group text-center">
                        <i class="material-icons mb-1 text-blue-400 group-hover:scale-110 transition-transform text-2xl">input</i>
                        <span class="text-[10px] font-bold uppercase tracking-wide">Terima Uang</span>
                    </a>
                    @endcan

                    @can('view-clients')
                    <a href="{{ route('admin.clients.index') }}" class="flex flex-col items-center justify-center p-3 rounded-xl bg-white/10 hover:bg-white/20 transition backdrop-blur-sm border border-white/5 group text-center">
                        <i class="material-icons mb-1 text-pink-400 group-hover:scale-110 transition-transform text-2xl">group</i>
                        <span class="text-[10px] font-bold uppercase tracking-wide">Klien</span>
                    </a>
                    @endcan

                    @can('view-suppliers')
                    <a href="{{ route('admin.suppliers.index') }}" class="flex flex-col items-center justify-center p-3 rounded-xl bg-white/10 hover:bg-white/20 transition backdrop-blur-sm border border-white/5 group text-center">
                        <i class="material-icons mb-1 text-cyan-400 group-hover:scale-110 transition-transform text-2xl">local_shipping</i>
                        <span class="text-[10px] font-bold uppercase tracking-wide">Supplier</span>
                    </a>
                    @endcan

                    @can('view-expenses')
                    <a href="{{ route('admin.expenses.index') }}" class="flex flex-col items-center justify-center p-3 rounded-xl bg-white/10 hover:bg-white/20 transition backdrop-blur-sm border border-white/5 group text-center">
                        <i class="material-icons mb-1 text-rose-400 group-hover:scale-110 transition-transform text-2xl">monetization_on</i>
                        <span class="text-[10px] font-bold uppercase tracking-wide">Pengeluaran</span>
                    </a>
                    @endcan

                    @can('view-reports')
                    <a href="{{ route('admin.reports.index') }}" class="flex flex-col items-center justify-center p-3 rounded-xl bg-white/10 hover:bg-white/20 transition backdrop-blur-sm border border-white/5 group text-center">
                        <i class="material-icons mb-1 text-indigo-400 group-hover:scale-110 transition-transform text-2xl">bar_chart</i>
                        <span class="text-[10px] font-bold uppercase tracking-wide">Laporan</span>
                    </a>
                    @endcan
                </div>
            </div>

            {{-- 2. Top Pelanggan (Posisi Tengah) --}}
            <div class="card flex flex-col h-full bg-white dark:bg-slate-800">
                <div class="card-header border-b border-slate-100 dark:border-slate-700 py-4 flex items-center justify-start gap-3">
                    <div class="w-10 h-10 flex items-center justify-center bg-blue-50 dark:bg-blue-900/20 rounded-lg text-blue-600"><i class="material-icons">emoji_events</i></div>
                    <div><h3 class="text-sm font-bold text-slate-800 dark:text-white">Top Pelanggan</h3><p class="text-[10px] text-slate-400">Berdasarkan omset</p></div>
                </div>
                <div class="flex-1 p-0 overflow-y-auto custom-scrollbar max-h-[300px]">
                    <ul class="divide-y divide-slate-100 dark:divide-slate-700">
                        @forelse($topClients as $clientData)
                            <li class="flex items-center justify-between p-4 hover:bg-slate-50 dark:hover:bg-slate-700/50 transition">
                                <div class="flex items-center gap-3 min-w-0">
                                    <div class="w-8 h-8 rounded-full bg-slate-100 dark:bg-slate-700 border border-slate-200 dark:border-slate-600 flex items-center justify-center text-xs font-bold text-slate-600 dark:text-slate-300">
                                        {{ substr($clientData->client->client_name, 0, 1) }}
                                    </div>
                                    <div class="min-w-0">
                                        <p class="text-xs font-bold text-slate-700 dark:text-slate-200 truncate max-w-[120px]" title="{{ $clientData->client->client_name }}">{{ $clientData->client->client_name }}</p>
                                        <p class="text-[10px] text-slate-400 truncate">{{ $clientData->client->sales_code ?? 'N/A' }}</p>
                                    </div>
                                </div>
                                <span class="text-xs font-bold text-emerald-600 dark:text-emerald-400 autonumeric" data-currency-symbol="Rp " data-digit-group-separator="." data-decimal-character=",">{{ $clientData->total_spent }}</span>
                            </li>
                        @empty
                            <li class="p-8 text-center text-slate-400 italic text-xs">Belum ada data pelanggan.</li>
                        @endforelse
                    </ul>
                </div>
            </div>

            {{-- 3. Polar Area Chart (Posisi Kanan) --}}
            <div class="card flex flex-col h-full bg-white dark:bg-slate-800">
                <div class="card-header border-b border-slate-100 dark:border-slate-700 py-4 flex items-center justify-start gap-3">
                    <div class="w-10 h-10 flex items-center justify-center bg-rose-50 dark:bg-rose-900/20 rounded-lg text-rose-600"><i class="material-icons">pie_chart</i></div>
                    <div><h3 class="text-sm font-bold text-slate-800 dark:text-white">Pengeluaran</h3><p class="text-[10px] text-slate-400">Komposisi belanja</p></div>
                </div>
                <div class="card-body p-4 flex items-center justify-center flex-1">
                    <div class="relative w-full h-[250px]">
                        <canvas id="expensePolarChart"></canvas>
                    </div>
                </div>
            </div>
        </div>

        {{-- [REVISI] F. GRID 2: STATUS PENJUALAN & PEMBELIAN --}}
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-8 animate-enter" style="animation-delay: 0.4s">
            
            {{-- Status Penjualan --}}
            <div class="card p-5 bg-white dark:bg-slate-800 flex flex-col justify-center">
                <h4 class="text-xs font-bold text-slate-500 uppercase tracking-wide mb-6 flex items-center gap-2">
                    <i class="material-icons text-emerald-500 text-sm">monetization_on</i> Status Penjualan
                </h4>
                <div class="space-y-5">
                    {{-- Paid --}}
                    <div>
                        <div class="flex justify-between text-xs mb-1"><span class="font-medium text-emerald-600">Lunas</span><span class="font-bold">{{ $stats['sales_breakdown']['paid'] }}</span></div>
                        <div class="w-full bg-slate-100 rounded-full h-2 dark:bg-slate-700"><div class="bg-emerald-500 h-2 rounded-full" style="width: {{ $stats['total_invoices'] > 0 ? ($stats['sales_breakdown']['paid'] / $stats['total_invoices'])*100 : 0 }}%"></div></div>
                    </div>
                    {{-- Partial --}}
                    <div>
                        <div class="flex justify-between text-xs mb-1"><span class="font-medium text-amber-600">Dicicil / Parsial</span><span class="font-bold">{{ $stats['sales_breakdown']['partial'] }}</span></div>
                        <div class="w-full bg-slate-100 rounded-full h-2 dark:bg-slate-700"><div class="bg-amber-500 h-2 rounded-full" style="width: {{ $stats['total_invoices'] > 0 ? ($stats['sales_breakdown']['partial'] / $stats['total_invoices'])*100 : 0 }}%"></div></div>
                    </div>
                    {{-- Overdue --}}
                    <div>
                        <div class="flex justify-between text-xs mb-1"><span class="font-medium text-rose-600 flex items-center gap-1">Macet <i class="material-icons text-[10px]">warning</i></span><span class="font-bold text-rose-600">{{ $stats['sales_breakdown']['overdue'] }}</span></div>
                        <div class="w-full bg-slate-100 rounded-full h-2 dark:bg-slate-700"><div class="bg-rose-500 h-2 rounded-full" style="width: {{ $stats['total_invoices'] > 0 ? ($stats['sales_breakdown']['overdue'] / $stats['total_invoices'])*100 : 0 }}%"></div></div>
                    </div>
                    {{-- Unpaid Current --}}
                    <div>
                        <div class="flex justify-between text-xs mb-1"><span class="font-medium text-slate-500">Belum Bayar</span><span class="font-bold">{{ $stats['sales_breakdown']['unpaid_current'] }}</span></div>
                        <div class="w-full bg-slate-100 rounded-full h-2 dark:bg-slate-700"><div class="bg-slate-400 h-2 rounded-full" style="width: {{ $stats['total_invoices'] > 0 ? ($stats['sales_breakdown']['unpaid_current'] / $stats['total_invoices'])*100 : 0 }}%"></div></div>
                    </div>
                </div>
            </div>

            {{-- Status Pembelian --}}
            <div class="card p-5 bg-white dark:bg-slate-800 flex flex-col justify-center">
                <h4 class="text-xs font-bold text-slate-500 uppercase tracking-wide mb-6 flex items-center gap-2">
                    <i class="material-icons text-orange-500 text-sm">shopping_bag</i> Status Pembelian
                </h4>
                <div class="space-y-5">
                    @php $totalPO = array_sum($stats['purchase_breakdown']); @endphp
                    {{-- Paid --}}
                    <div>
                        <div class="flex justify-between text-xs mb-1"><span class="font-medium text-emerald-600">Lunas</span><span class="font-bold">{{ $stats['purchase_breakdown']['paid'] }}</span></div>
                        <div class="w-full bg-slate-100 rounded-full h-2 dark:bg-slate-700"><div class="bg-emerald-500 h-2 rounded-full" style="width: {{ $totalPO > 0 ? ($stats['purchase_breakdown']['paid'] / $totalPO)*100 : 0 }}%"></div></div>
                    </div>
                    {{-- Partial --}}
                    <div>
                        <div class="flex justify-between text-xs mb-1"><span class="font-medium text-amber-600">Dicicil</span><span class="font-bold">{{ $stats['purchase_breakdown']['partial'] }}</span></div>
                        <div class="w-full bg-slate-100 rounded-full h-2 dark:bg-slate-700"><div class="bg-amber-500 h-2 rounded-full" style="width: {{ $totalPO > 0 ? ($stats['purchase_breakdown']['partial'] / $totalPO)*100 : 0 }}%"></div></div>
                    </div>
                    {{-- Overdue --}}
                    <div>
                        <div class="flex justify-between text-xs mb-1"><span class="font-medium text-rose-600 flex items-center gap-1">Jatuh Tempo <i class="material-icons text-[10px]">priority_high</i></span><span class="font-bold text-rose-600">{{ $stats['purchase_breakdown']['overdue'] }}</span></div>
                        <div class="w-full bg-slate-100 rounded-full h-2 dark:bg-slate-700"><div class="bg-rose-500 h-2 rounded-full" style="width: {{ $totalPO > 0 ? ($stats['purchase_breakdown']['overdue'] / $totalPO)*100 : 0 }}%"></div></div>
                    </div>
                    {{-- Unpaid Current --}}
                    <div>
                        <div class="flex justify-between text-xs mb-1"><span class="font-medium text-slate-500">Belum Bayar</span><span class="font-bold">{{ $stats['purchase_breakdown']['unpaid_current'] }}</span></div>
                        <div class="w-full bg-slate-100 rounded-full h-2 dark:bg-slate-700"><div class="bg-slate-400 h-2 rounded-full" style="width: {{ $totalPO > 0 ? ($stats['purchase_breakdown']['unpaid_current'] / $totalPO)*100 : 0 }}%"></div></div>
                    </div>
                </div>
            </div>

        </div>

    @else
        {{-- ALTERNATIVE VIEW FOR SALES STAFF (Non-Financial) --}}
        @php
            $opStats = [
                ['label' => 'Penjualan Saya', 'value' => $stats['total_sales_value'], 'icon' => 'paid', 'color' => 'blue', 'curr' => true],
                ['label' => 'Invoice Dibuat', 'value' => $stats['total_invoices'], 'icon' => 'description', 'color' => 'indigo', 'curr' => false],
                ['label' => 'Invoice Lunas', 'value' => $stats['paid_invoices'], 'icon' => 'check_circle', 'color' => 'emerald', 'curr' => false],
                ['label' => 'Sisa Tagihan Klien', 'value' => $stats['receivables_filtered'], 'icon' => 'pending', 'color' => 'amber', 'curr' => true],
            ];
        @endphp
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6 mb-8 animate-enter">
            @foreach($opStats as $stat)
                <div class="card p-6 relative overflow-hidden group hover:shadow-md transition-all bg-white dark:bg-slate-800">
                    <div class="relative z-10 flex justify-between items-start">
                        <div>
                            <p class="text-xs font-bold text-{{ $stat['color'] }}-600 uppercase tracking-wide mb-1">{{ $stat['label'] }}</p>
                            <h3 class="text-2xl font-bold text-slate-800 dark:text-white {{ $stat['curr'] ? 'autonumeric' : '' }}" @if($stat['curr']) data-currency-symbol="Rp " data-digit-group-separator="." data-decimal-character="," @endif>{{ $stat['curr'] ? $stat['value'] : number_format($stat['value']) }}</h3>
                        </div>
                        <div class="w-12 h-12 flex items-center justify-center bg-{{ $stat['color'] }}-50 dark:bg-{{ $stat['color'] }}-900/20 rounded-xl text-{{ $stat['color'] }}-600 dark:text-{{ $stat['color'] }}-400 group-hover:scale-110 transition-transform shadow-sm"><i class="material-icons text-xl">{{ $stat['icon'] }}</i></div>
                    </div>
                    <i class="material-icons absolute -right-4 -bottom-4 text-8xl text-{{ $stat['color'] }}-500 opacity-[0.07] rotate-12 group-hover:scale-110 transition-transform pointer-events-none">{{ $stat['icon'] }}</i>
                </div>
            @endforeach
        </div>
    @endif

    {{-- [REVISI] G. PRODUCT INFO: PERFORMA & STOK KRITIS (BERDAMPINGAN) --}}
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-8 animate-enter" style="animation-delay: 0.5s">
        {{-- Product 3D --}}
        <div class="card flex flex-col h-full bg-white dark:bg-slate-800">
            <div class="card-header border-b border-slate-100 dark:border-slate-700 py-4 flex justify-between items-center">
                <div class="flex items-center gap-3">
                    <div class="w-10 h-10 flex items-center justify-center bg-purple-50 dark:bg-purple-900/20 rounded-lg text-purple-600"><i class="material-icons">donut_small</i></div>
                    <div><h3 class="text-sm font-bold text-slate-800 dark:text-white">Performa Produk</h3><p class="text-[10px] text-slate-400">Berdasarkan invoice</p></div>
                </div>
            </div>
            <div class="card-body p-4 flex flex-col sm:flex-row gap-4 h-full">
                <div class="w-full sm:w-5/12 flex items-center justify-center min-h-[220px]">
                    <div class="relative w-full h-[220px]"><div id="topProducts3DChart" class="w-full h-full"></div></div>
                </div>
                <div class="w-full sm:w-7/12 border-l border-slate-100 dark:border-slate-700 pl-0 sm:pl-4">
                    <div class="flex items-center justify-between text-[10px] uppercase font-bold text-slate-400 mb-2 px-2"><span>Nama Produk</span><span>Terjual</span></div>
                    <div id="customLegendContainer" class="overflow-y-auto custom-scrollbar max-h-[220px] pr-2">{{-- Generated --}}</div>
                </div>
            </div>
        </div>
        
        {{-- Low Stock --}}
        <div class="card bg-red-50/50 dark:bg-red-900/10 border border-red-100 dark:border-red-900/30 overflow-hidden flex flex-col h-full">
            <div class="px-5 py-4 border-b border-red-100 dark:border-red-800/30 flex items-center gap-3">
                <div class="w-10 h-10 flex items-center justify-center bg-red-100 dark:bg-red-800/30 rounded-lg text-red-600"><i class="material-icons">warning_amber</i></div>
                <h3 class="text-red-600 dark:text-red-400 text-sm font-bold uppercase tracking-wider">Stok Kritis (< 10)</h3>
            </div>
            <div class="flex-1 overflow-y-auto custom-scrollbar max-h-[300px]">
                <ul class="divide-y divide-red-100/50 dark:divide-red-800/30">
                    @forelse($lowStockProducts as $product)
                        <li class="flex justify-between items-center p-4 hover:bg-red-100/50 dark:hover:bg-red-900/20 transition">
                            <div class="min-w-0 pr-4"><p class="text-sm font-bold text-slate-700 dark:text-slate-300 truncate">{{ $product->product_name }}</p><p class="text-[10px] text-slate-500 mt-0.5">{{ $product->product_code }}</p></div>
                            <span class="badge bg-red-100 text-red-700 border border-red-200 text-xs px-2.5 py-1 whitespace-nowrap">{{ (float)$product->stock_quantity }} {{ $product->unit->name ?? '' }}</span>
                        </li>
                    @empty
                        <li class="p-8 text-center text-slate-400 italic flex flex-col items-center justify-center h-full"><i class="material-icons text-3xl mb-2 opacity-50">inventory</i><span class="text-xs">Stok aman terkendali.</span></li>
                    @endforelse
                </ul>
            </div>
        </div>
    </div>

    {{-- Recent Transactions --}}
    <div class="card bg-white dark:bg-slate-800 animate-enter" style="animation-delay: 0.6s">
        <div class="card-header flex flex-col sm:flex-row items-center justify-between py-4 px-6 border-b border-slate-100 dark:border-slate-700 gap-4">
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 flex items-center justify-center bg-blue-50 dark:bg-blue-900/20 rounded-lg text-blue-600"><i class="material-icons">receipt_long</i></div>
                <div><h3 class="font-bold text-slate-800 dark:text-white">Transaksi Terakhir</h3><p class="text-xs text-slate-500 hidden sm:block">Daftar penjualan terbaru.</p></div>
            </div>
            <div class="flex items-center gap-3 w-full sm:w-auto">
                @if($isAdmin)
                    <form method="GET" action="{{ route('admin.dashboard') }}" class="w-full sm:w-64">
                        <input type="hidden" name="year" value="{{ $selectedYear }}">
                        <select name="filter_user_id" class="tom-select-filter" placeholder="Filter Sales/User..." onchange="this.form.submit()">
                            <option value="">Semua Sales</option>
                            @foreach($allUsers as $u)
                                <option value="{{ $u->user_id }}" {{ $selectedUserId == $u->user_id ? 'selected' : '' }}>{{ $u->full_name }}</option>
                            @endforeach
                        </select>
                    </form>
                @endif
                <a href="{{ route('admin.invoices.index') }}" class="btn btn-secondary btn-sm whitespace-nowrap">Lihat Semua <i class="material-icons text-sm ml-1">arrow_forward</i></a>
            </div>
        </div>
        <div class="table-container">
            <table class="table-modern w-full">
                <thead>
                    <tr><th class="pl-6">Invoice #</th><th>Tanggal</th><th>Klien</th><th class="hidden sm:table-cell">Sales</th><th class="text-right">Total</th><th class="text-center pr-6">Status</th></tr>
                </thead>
                <tbody class="divide-y divide-slate-100 dark:divide-slate-700">
                    @forelse($recentInvoices as $inv)
                        <tr>
                            <td class="pl-6 py-3"><a href="{{ route('admin.invoices.show', $inv->invoice_id) }}" class="group flex flex-col"><span class="font-bold text-indigo-600 dark:text-indigo-400 text-sm group-hover:underline">{{ $inv->invoice_number }}</span></a></td>
                            <td class="py-3 text-xs text-slate-500">{{ $inv->order_date->format('d M Y') }}</td>
                            <td class="py-3"><div class="text-sm font-medium text-slate-700 dark:text-slate-300 max-w-[180px] truncate">{{ $inv->client->client_name ?? 'Umum' }}</div></td>
                            <td class="py-3 hidden sm:table-cell"><div class="flex items-center gap-2"><div class="w-6 h-6 rounded-full bg-slate-100 dark:bg-slate-700 text-slate-600 dark:text-slate-400 text-[10px] font-bold flex items-center justify-center border border-slate-200 dark:border-slate-600">{{ substr($inv->sales->full_name ?? 'S', 0, 1) }}</div><span class="text-xs text-slate-600 dark:text-slate-400 truncate max-w-[100px]">{{ $inv->sales->full_name ?? 'Sistem' }}</span></div></td>
                            <td class="py-3 text-right"><span class="font-bold text-slate-700 dark:text-slate-200 text-sm autonumeric" data-currency-symbol="Rp " data-digit-group-separator="." data-decimal-character=",">{{ $inv->total_amount }}</span></td>
                            <td class="py-3 pr-6 text-center">@if($inv->status == 'paid') <span class="badge badge-success">Lunas</span> @elseif($inv->status == 'unpaid') <span class="badge badge-danger">Belum Bayar</span> @elseif($inv->status == 'partially_paid') <span class="badge badge-warning">Parsial</span> @else <span class="badge badge-primary">{{ ucfirst($inv->status) }}</span> @endif</td>
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
        
        // 1. UI Init (Tom Select & AutoNumeric)
        document.querySelectorAll('.tom-select-filter').forEach(el => {
            new TomSelect(el, { 
                create: false, 
                sortField: { field: "text", direction: "asc" }, 
                dropdownParent: 'body', 
                controlClass: 'ts-control !min-h-[38px] !h-[38px] !rounded-xl !border-slate-300 dark:!border-slate-600 !bg-white dark:!bg-slate-800 dark:!text-slate-200' 
            });
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

        const months = {!! json_encode($charts['months']) !!};
        const incomeData = {!! json_encode($charts['trend_data_income']) !!};
        const expenseData = {!! json_encode($charts['trend_data_expense']) !!};
        const expenseComp = {!! json_encode($charts['expense_composition'] ?? [0,0,0,0,0]) !!};
        const productLabels = {!! json_encode($charts['top_products_labels'] ?? []) !!};
        const rawProductData = {!! json_encode($charts['top_products_data'] ?? []) !!};
        const productData = rawProductData.map(val => parseFloat(val) || 0);

        function generateColors(count) {
            const colors = [], base = ['#6366f1', '#8b5cf6', '#d946ef', '#ec4899', '#f43f5e', '#f59e0b', '#10b981', '#06b6d4', '#3b82f6'];
            for (let i = 0; i < count; i++) colors.push(i < base.length ? base[i] : `hsl(${(i * 137) % 360}, 70%, 60%)`);
            return { bg: colors };
        }
        const productColors = generateColors(productLabels.length);

        // 3. Trend Chart (Chart.js - Line)
        const ctxLine = document.getElementById('trendLineChart');
        if (ctxLine) {
            const ctx = ctxLine.getContext('2d');
            let gradIncome = ctx.createLinearGradient(0, 0, 0, 300);
            gradIncome.addColorStop(0, 'rgba(99, 102, 241, 0.3)'); 
            gradIncome.addColorStop(1, 'rgba(99, 102, 241, 0.0)');

            const baseOptions = {
                responsive: true, maintainAspectRatio: false,
                layout: { padding: { top: 20, bottom: 10, left: 10, right: 25 } },
                plugins: {
                    legend: { position: 'top', align: 'end', labels: { color: textColor, usePointStyle: true, boxWidth: 8, font: {family: 'Inter', size: 11}, padding: 20 } },
                    tooltip: { 
                        mode: 'index', intersect: false, 
                        backgroundColor: isDarkMode ? '#1e293b' : '#ffffff', 
                        titleColor: isDarkMode ? '#fff' : '#0f172a', 
                        bodyColor: isDarkMode ? '#cbd5e1' : '#475569', 
                        borderColor: isDarkMode ? '#334155' : '#e2e8f0', 
                        borderWidth: 1, padding: 12, cornerRadius: 8, 
                        callbacks: { 
                            label: function(context) { 
                                let label = context.dataset.label || ''; 
                                if (label) label += ': '; 
                                if (context.parsed.y !== null) label += new Intl.NumberFormat('id-ID', { style: 'currency', currency: 'IDR', maximumFractionDigits: 0 }).format(context.parsed.y); 
                                return label; 
                            } 
                        } 
                    }
                },
                interaction: { mode: 'nearest', axis: 'x', intersect: false }
            };

            new Chart(ctx, {
                type: 'line',
                data: {
                    labels: months,
                    datasets: [
                        { label: 'Cash In', data: incomeData, borderColor: '#6366f1', backgroundColor: gradIncome, tension: 0.4, fill: true, pointRadius: 4, pointHoverRadius: 6 },
                        { label: 'Cash Out', data: expenseData, borderColor: '#f43f5e', backgroundColor: 'transparent', tension: 0.4, borderDash: [5, 5], pointRadius: 4, pointHoverRadius: 6 }
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

            // Bar Chart Instance
            const ctxBar = document.getElementById('trendBarChart').getContext('2d');
            new Chart(ctxBar, {
                type: 'bar',
                data: {
                    labels: months,
                    datasets: [
                        { label: 'Cash In', data: incomeData, backgroundColor: '#6366f1', borderRadius: 4, barPercentage: 0.6, categoryPercentage: 0.8 },
                        { label: 'Cash Out', data: expenseData, backgroundColor: '#f43f5e', borderRadius: 4, barPercentage: 0.6, categoryPercentage: 0.8 }
                    ]
                },
                options: {
                    ...baseOptions,
                    scales: {
                        y: { beginAtZero: true, grace: '20%', grid: { borderDash: [4, 4], color: gridColor, drawBorder: false }, ticks: { color: textColor, font: { size: 10, family: 'Inter' }, padding: 10 } },
                        x: { grid: { display: false, drawBorder: false }, ticks: { color: textColor, font: { size: 10, family: 'Inter' }, padding: 10 }, stacked: false }
                    }
                }
            });
        }

        // 4. Polar Area Chart (Expense Breakdown)
        const ctxPolar = document.getElementById('expensePolarChart');
        if (ctxPolar) {
            new Chart(ctxPolar, {
                type: 'polarArea',
                data: {
                    labels: ['Ops', 'Bayar PO', 'Aset', 'Pinjaman', 'Prive'],
                    datasets: [{
                        label: 'Total Pengeluaran',
                        data: expenseComp,
                        backgroundColor: [
                            'rgba(244, 63, 94, 0.7)',  // Rose
                            'rgba(249, 115, 22, 0.7)', // Orange
                            'rgba(168, 85, 247, 0.7)', // Purple
                            'rgba(59, 130, 246, 0.7)', // Blue
                            'rgba(100, 116, 139, 0.7)' // Slate
                        ],
                        borderWidth: 0
                    }]
                },
                options: {
                    responsive: true, maintainAspectRatio: false,
                    plugins: { 
                        legend: { position: 'right', labels: { color: textColor, font: {family: 'Inter', size: 10}, boxWidth: 10 } },
                        tooltip: { callbacks: { label: function(context) { let label = context.label || ''; if (label) label += ': '; if (context.parsed.r !== null) label += new Intl.NumberFormat('id-ID', { style: 'currency', currency: 'IDR', maximumFractionDigits: 0 }).format(context.parsed.r); return label; } } }
                    },
                    scales: { r: { grid: { color: gridColor }, ticks: { display: false, backdropColor: 'transparent' } } }
                }
            });
        }

        // 5. Product Chart (Highcharts 3D Donut)
        if (typeof Highcharts !== 'undefined' && document.getElementById('topProducts3DChart')) {
            const highchartsData = productLabels.map((l, i) => ({ name: l, y: productData[i], color: productColors.bg[i] }));
            const productChart = Highcharts.chart('topProducts3DChart', {
                chart: { type: 'pie', backgroundColor: 'transparent', options3d: { enabled: true, alpha: 45, beta: 0 }, style: { fontFamily: 'Inter' } },
                title: { text: null }, credits: { enabled: false },
                tooltip: { backgroundColor: isDarkMode ? '#1e293b' : '#ffffff', borderColor: isDarkMode ? '#334155' : '#e2e8f0', style: { color: textColor }, pointFormat: '<b>{point.y} Unit</b> ({point.percentage:.1f}%)' },
                plotOptions: { pie: { innerSize: '50%', depth: 45, allowPointSelect: true, cursor: 'pointer', dataLabels: { enabled: false }, showInLegend: false, borderWidth: 0 } },
                series: [{ name: 'Terjual', data: highchartsData }]
            });

            // Generate HTML Legend manually
            const container = document.getElementById('customLegendContainer');
            if (container) {
                container.innerHTML = ''; 
                const totalQty = productData.reduce((a, b) => a + b, 0);
                if (productLabels.length === 0) { container.innerHTML = '<p class="text-center text-xs text-slate-400 mt-4 italic">Belum ada data.</p>'; }
                else {
                    const ul = document.createElement('ul'); ul.className = 'divide-y divide-slate-100 dark:divide-slate-700/50';
                    productLabels.forEach((label, index) => {
                        const li = document.createElement('li');
                        li.className = 'flex items-center justify-between py-2.5 px-2 hover:bg-slate-50 dark:hover:bg-slate-800/50 transition rounded-lg cursor-pointer';
                        const pct = totalQty > 0 ? ((productData[index] / totalQty) * 100).toFixed(1) + '%' : '0%';
                        li.innerHTML = `<div class="flex items-center gap-3 min-w-0"><span class="w-3 h-3 rounded-full shrink-0 shadow-sm ring-2 ring-white dark:ring-slate-800" style="background-color: ${productColors.bg[index]};"></span><div class="flex flex-col min-w-0"><span class="text-xs font-semibold text-slate-700 dark:text-slate-300 truncate max-w-[140px]" title="${label}">${label}</span><span class="text-[10px] text-slate-400">${pct}</span></div></div><span class="text-xs font-bold text-slate-700 dark:text-slate-300 bg-slate-100 dark:bg-slate-700 px-2 py-0.5 rounded-md">${productData[index]}</span>`;
                        
                        // Interaction Highcharts
                        li.addEventListener('mouseenter', () => { if(productChart.series[0].data[index]) { productChart.series[0].data[index].setState('hover'); productChart.tooltip.refresh(productChart.series[0].data[index]); } });
                        li.addEventListener('mouseleave', () => { if(productChart.series[0].data[index]) { productChart.series[0].data[index].setState(''); productChart.tooltip.hide(); } });
                        
                        ul.appendChild(li);
                    });
                    container.appendChild(ul);
                }
            }
        }
    });
</script>
@endpush
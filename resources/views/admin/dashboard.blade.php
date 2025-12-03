@extends("admin.layouts.app")

@section("title", "Dashboard Overview")

@section("content")
<div class="max-w-7xl mx-auto space-y-6 pb-10 animate-enter">
    
    {{-- SECTION: HEADER & FILTER --}}
    <div class="flex flex-col md:flex-row justify-between items-end gap-4 mb-2">
        <div>
            <h1 class="text-2xl font-bold text-slate-800 tracking-tight flex items-center gap-2">
                <i class="material-icons text-indigo-600">dashboard</i>
                Dashboard
            </h1>
            <p class="text-slate-500 mt-1 text-sm flex items-center gap-1">
                @if(!$canViewFinancials)
                    Selamat datang, <span class="font-semibold text-indigo-600">{{ Auth::user()->full_name }}</span>! 👋
                @else
                    Ringkasan performa bisnis Anda secara real-time.
                @endif
            </p>
        </div>
        
        @if($canViewFinancials)
        <form action="{{ route('admin.dashboard') }}" method="GET" class="w-full md:w-48">
            <label for="year-select" class="sr-only">Pilih Tahun</label>
            <select name="year" id="year-select" class="form-select" onchange="this.form.submit()">
                @forelse($availableYears as $year)
                    <option value="{{ $year }}" @selected($selectedYear == $year)>Tahun {{ $year }}</option>
                @empty
                    <option value="{{ date('Y') }}">Tahun {{ date('Y') }}</option>
                @endforelse
            </select>
        </form>
        @endif
    </div>

    {{-- SECTION: STATS WIDGETS --}}
    <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-6">
        
        @if($canViewFinancials)
        {{-- Widget Saldo --}}
        <div class="dashboard-stat-card bg-gradient-to-br from-indigo-600 to-indigo-800 text-white">
            <div class="absolute inset-0 opacity-10 bg-[url('https://www.transparenttextures.com/patterns/cubes.png')]"></div>
            <div class="p-6 relative z-10 h-full flex flex-col justify-between">
                <div class="flex items-start justify-between">
                    <div>
                        <p class="text-indigo-100 text-xs font-bold uppercase tracking-wider mb-1">Saldo Kas & Bank</p>
                        <h3 class="text-3xl font-bold text-white tracking-tight">Rp {{ number_format($financialHealth['cash_balance'] ?? 0, 0, ',', '.') }}</h3>
                    </div>
                    <div class="dashboard-stat-icon text-white bg-white/20">
                        <i class="material-icons text-2xl">account_balance_wallet</i>
                    </div>
                </div>
                <div class="flex items-center gap-2 text-xs text-indigo-100 bg-indigo-900/30 w-fit px-3 py-1 rounded-lg border border-indigo-500/30 mt-4">
                    <i class="material-icons text-[14px]">info</i>
                    <span>Likuiditas tersedia</span>
                </div>
            </div>
        </div>

        {{-- Widget Laba --}}
        <div class="dashboard-stat-card bg-gradient-to-br from-emerald-500 to-emerald-700 text-white">
            <div class="absolute inset-0 opacity-10 bg-[url('https://www.transparenttextures.com/patterns/cubes.png')]"></div>
            <div class="p-6 relative z-10 h-full flex flex-col justify-between">
                <div class="flex items-start justify-between">
                    <div>
                        <p class="text-emerald-100 text-xs font-bold uppercase tracking-wider mb-1">Estimasi Laba (YTD)</p>
                        <h3 class="text-3xl font-bold text-white tracking-tight">Rp {{ number_format($financialHealth['monthly_profit'] ?? 0, 0, ',', '.') }}</h3>
                    </div>
                    <div class="dashboard-stat-icon text-white bg-white/20">
                        <i class="material-icons text-2xl">trending_up</i>
                    </div>
                </div>
                <div class="flex items-center gap-2 text-xs text-emerald-100 bg-emerald-900/20 w-fit px-3 py-1 rounded-lg border border-emerald-500/30 mt-4">
                    <i class="material-icons text-[14px]">calendar_today</i>
                    <span>Periode {{ $selectedYear }}</span>
                </div>
            </div>
        </div>
        @endif

        {{-- Widget Action Items --}}
        <div class="{{ $canViewFinancials ? '' : 'md:col-span-2 xl:col-span-3' }} dashboard-card p-0 flex flex-col h-[180px]">
            <div class="px-5 py-3 border-b border-slate-100 flex justify-between items-center bg-slate-50/50 rounded-t-xl">
                <h6 class="text-xs font-bold text-slate-600 uppercase tracking-wide flex items-center gap-2">
                    <i class="material-icons text-amber-500 text-sm">notifications_active</i>
                    Perlu Tindakan
                </h6>
                @if(array_sum($pendingActions) > 0)
                    <span class="flex h-2.5 w-2.5 relative">
                        <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-red-400 opacity-75"></span>
                        <span class="relative inline-flex rounded-full h-2.5 w-2.5 bg-red-500"></span>
                    </span>
                @endif
            </div>
            
            <div class="p-4 flex-1 overflow-y-auto custom-scrollbar">
                @if(array_sum($pendingActions) > 0)
                    <div class="space-y-2">
                        @if(($pendingActions['invoice_draft'] ?? 0) > 0)
                        <a href="{{ route('admin.invoices.index', ['status' => 'draft']) }}" class="action-item action-item-indigo group">
                            <div class="flex items-center gap-3">
                                <div class="w-8 h-8 rounded-lg bg-indigo-100 flex items-center justify-center text-indigo-600 group-hover:bg-indigo-600 group-hover:text-white transition-colors">
                                    <i class="material-icons text-sm">receipt</i>
                                </div>
                                <div>
                                    <span class="text-xs font-bold text-slate-700 block group-hover:text-indigo-700">Invoice Draft</span>
                                    <span class="text-[10px] text-slate-500">Menunggu finalisasi</span>
                                </div>
                            </div>
                            <span class="action-count bg-white text-indigo-600 border-indigo-100">{{ $pendingActions['invoice_draft'] }}</span>
                        </a>
                        @endif
                        
                        @if(($pendingActions['po_draft'] ?? 0) > 0)
                        <a href="{{ route('admin.purchase-orders.index') }}" class="action-item action-item-blue group">
                            <div class="flex items-center gap-3">
                                <div class="w-8 h-8 rounded-lg bg-blue-100 flex items-center justify-center text-blue-600 group-hover:bg-blue-600 group-hover:text-white transition-colors">
                                    <i class="material-icons text-sm">shopping_cart</i>
                                </div>
                                <div>
                                    <span class="text-xs font-bold text-slate-700 block group-hover:text-blue-700">PO Draft</span>
                                    <span class="text-[10px] text-slate-500">Menunggu approval</span>
                                </div>
                            </div>
                            <span class="action-count bg-white text-blue-600 border-blue-100">{{ $pendingActions['po_draft'] }}</span>
                        </a>
                        @endif

                        @if(($pendingActions['total_clearance'] ?? 0) > 0)
                        @can('manage-payment-clearance')
                        <a href="{{ route('admin.payment-clearance.index') }}" class="action-item action-item-amber group">
                            <div class="flex items-center gap-3">
                                <div class="w-8 h-8 rounded-lg bg-amber-100 flex items-center justify-center text-amber-600 group-hover:bg-amber-600 group-hover:text-white transition-colors">
                                    <i class="material-icons text-sm">hourglass_top</i>
                                </div>
                                <div>
                                    <span class="text-xs font-bold text-slate-700 block group-hover:text-amber-700">Pending Kliring</span>
                                    <span class="text-[10px] text-slate-500">Perlu verifikasi</span>
                                </div>
                            </div>
                            <span class="action-count bg-white text-amber-600 border-amber-100">{{ $pendingActions['total_clearance'] }}</span>
                        </a>
                        @endcan
                        @endif
                    </div>
                @else
                    <div class="h-full flex flex-col items-center justify-center text-center">
                        <div class="w-10 h-10 rounded-full bg-emerald-50 text-emerald-500 flex items-center justify-center mb-2">
                            <i class="material-icons text-xl">check</i>
                        </div>
                        <p class="text-xs font-medium text-slate-600">Semua tugas selesai!</p>
                    </div>
                @endif
            </div>
        </div>
    </div>

    {{-- SECTION: MAIN CHART --}}
    @if($canViewFinancials)
    <div class="dashboard-card p-6">
        <div class="flex flex-col sm:flex-row justify-between items-center mb-6 pb-4 border-b border-slate-100">
            <div class="flex items-center gap-4">
                <div class="w-10 h-10 rounded-xl bg-indigo-50 text-indigo-600 flex items-center justify-center">
                    <i class="material-icons text-xl">analytics</i>
                </div>
                <div>
                    <h5 class="text-sm font-bold text-slate-800 uppercase tracking-wide">Analisis Keuangan</h5>
                    <p class="text-xs text-slate-500 mt-0.5">Arus kas masuk vs keluar tahun {{ $selectedYear }}</p>
                </div>
            </div>
            <div class="flex bg-slate-100 p-1 rounded-lg mt-3 sm:mt-0">
                <button id="switchToLine" class="px-3 py-1.5 text-xs font-medium rounded-md bg-white text-indigo-600 shadow-sm transition-all hover:shadow">Line</button>
                <button id="switchToBar" class="px-3 py-1.5 text-xs font-medium rounded-md text-slate-500 hover:text-slate-700 transition-all">Bar</button>
            </div>
        </div>
        <div class="relative h-[300px] w-full">
            <canvas id="mainFinancialChart"></canvas>
        </div>
    </div>
    @endif

    {{-- SECTION: PRODUCT & INVENTORY --}}
    <div class="grid grid-cols-1 lg:grid-cols-12 gap-6">
        
        {{-- Top Products --}}
        <div class="lg:col-span-7 dashboard-card p-6">
            <div class="flex items-center gap-3 mb-6 pb-4 border-b border-slate-100">
                <div class="w-10 h-10 rounded-xl bg-purple-50 text-purple-600 flex items-center justify-center">
                    <i class="material-icons text-lg">star</i>
                </div>
                <h5 class="text-sm font-bold text-slate-800 uppercase tracking-wide">Produk Terlaris</h5>
            </div>
            <div class="flex items-center justify-center h-[300px]">
                <canvas id="topProductsChart"></canvas>
            </div>
        </div>

        {{-- Low Stock --}}
        @can('view-dashboard-inventory')
        <div class="lg:col-span-5 dashboard-card p-0 flex flex-col">
            <div class="p-5 border-b border-slate-100 flex justify-between items-center">
                <div class="flex items-center gap-3">
                    <div class="w-10 h-10 rounded-xl bg-red-50 text-red-600 flex items-center justify-center">
                        <i class="material-icons text-lg">warning</i>
                    </div>
                    <h5 class="text-sm font-bold text-slate-800 uppercase tracking-wide">Stok Menipis</h5>
                </div>
                <a href="{{ route('admin.products.index') }}" class="text-[11px] font-bold text-indigo-600 hover:text-indigo-800 tracking-wide border-b border-dashed border-indigo-300 hover:border-indigo-600">LIHAT SEMUA</a>
            </div>
            
            <div class="flex-1 overflow-y-auto custom-scrollbar max-h-[300px] p-4">
                <ul class="space-y-3">
                    @forelse ($lowStockProducts as $product)
                        <li class="action-item border-slate-100 hover:border-red-200 hover:bg-red-50/30 cursor-default p-3 rounded-xl transition-all">
                            <div class="flex items-center gap-3 overflow-hidden">
                                <div class="w-10 h-10 rounded-lg bg-slate-100 flex items-center justify-center flex-shrink-0 text-slate-400 overflow-hidden border border-slate-200">
                                    @if($product->image_path)
                                        <img src="{{ asset('storage/' . $product->image_path) }}" class="w-full h-full object-cover">
                                    @else
                                        <i class="material-icons text-lg">inventory_2</i>
                                    @endif
                                </div>
                                <div class="min-w-0">
                                    <p class="text-sm font-bold text-slate-700 truncate">{{ $product->product_name }}</p>
                                    <p class="text-[10px] text-slate-500 font-mono">{{ $product->sku ?? 'NO-SKU' }}</p>
                                </div>
                            </div>
                            <span class="px-2.5 py-1 rounded-md bg-red-100 text-red-700 text-[11px] font-bold border border-red-200 whitespace-nowrap">
                                Sisa: {{ $product->stock_quantity }}
                            </span>
                        </li>
                    @empty
                        <li class="empty-state py-8">
                            <div class="empty-state-icon w-12 h-12">
                                <i class="material-icons text-2xl">check_circle</i>
                            </div>
                            <p class="text-sm font-medium text-slate-600">Stok Aman</p>
                        </li>
                    @endforelse
                </ul>
            </div>
        </div>
        @endcan
    </div>

    {{-- SECTION: RECENT TRANSACTIONS --}}
    <div class="dashboard-card p-0 overflow-hidden">
        <div class="p-6 border-b border-slate-100 flex flex-col sm:flex-row justify-between items-center gap-4 bg-slate-50/30">
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 rounded-xl bg-green-50 text-green-600 flex items-center justify-center">
                    <i class="material-icons text-lg">receipt_long</i>
                </div>
                <div>
                    <h5 class="text-sm font-bold text-slate-800 uppercase tracking-wide">Penjualan Terbaru</h5>
                    <p class="text-xs text-slate-500">Daftar transaksi hari ini</p>
                </div>
            </div>
            
            @if($canViewFinancials)
            <form action="{{ route('admin.dashboard') }}" method="GET" class="w-full sm:w-64">
                <input type="hidden" name="year" value="{{ $selectedYear }}">
                <div class="relative">
                    <select name="sales_id" id="sales-filter" class="form-select" onchange="this.form.submit()">
                        <option value="">Semua Sales</option>
                        @foreach($filterableUsers as $role => $users)
                            <optgroup label="{{ $role }}">
                                @foreach($users as $u)
                                    <option value="{{ $u->user_id }}" @selected($selectedSalesId == $u->user_id)>{{ $u->full_name }}</option>
                                @endforeach
                            </optgroup>
                        @endforeach
                    </select>
                </div>
            </form>
            @endif
        </div>

        <div class="overflow-x-auto">
            <table class="dashboard-table w-full">
                <thead>
                    <tr>
                        <th class="pl-6">No. Pesanan</th>
                        <th>Klien</th>
                        <th>Sales</th>
                        <th>Tanggal</th>
                        <th class="text-center">Status</th>
                        <th class="text-right pr-6">Total</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($latestOrders as $order)
                        <tr>
                            <td class="pl-6">
                                <a href="{{ route('admin.sales-orders.show', $order->order_id) }}" class="text-sm font-bold text-indigo-600 hover:text-indigo-800 font-mono tracking-tight">
                                    {{ $order->order_number }}
                                </a>
                            </td>
                            <td><span class="text-sm font-semibold text-slate-700">{{ $order->client->client_name ?? "N/A" }}</span></td>
                            <td>
                                <div class="flex items-center gap-2">
                                    <div class="w-6 h-6 rounded-full bg-slate-100 border border-slate-200 flex items-center justify-center text-[10px] font-bold text-slate-500">
                                        {{ substr($order->sales->full_name ?? '?', 0, 1) }}
                                    </div>
                                    <span class="text-sm text-slate-600">{{ $order->sales->full_name ?? "N/A" }}</span>
                                </div>
                            </td>
                            <td class="text-sm text-slate-500">{{ optional($order->order_date)->format("d M Y") }}</td>
                            <td class="text-center">
                                @php
                                    $statusClass = match($order->status) {
                                        'completed', 'invoiced' => 'status-completed',
                                        'approved' => 'status-approved',
                                        'pending' => 'status-pending',
                                        'rejected' => 'status-rejected',
                                        default => 'status-draft'
                                    };
                                    $icon = match($order->status) {
                                        'completed', 'invoiced' => 'verified',
                                        'approved' => 'check_circle',
                                        'pending' => 'schedule',
                                        'rejected' => 'cancel',
                                        default => 'edit_note'
                                    };
                                @endphp
                                <span class="status-badge {{ $statusClass }}">
                                    <i class="material-icons text-[12px]">{{ $icon }}</i>
                                    {{ str_replace('_', ' ', $order->status) }}
                                </span>
                            </td>
                            <td class="text-right pr-6">
                                @if($canViewFinancials) 
                                    <span class="text-sm font-bold text-slate-800 tracking-tight">Rp {{ number_format($order->total_amount, 0, ",", ".") }}</span>
                                @else 
                                    <span class="text-slate-400 text-xs italic bg-slate-100 px-2 py-1 rounded">Tersembunyi</span> 
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="py-12 text-center">
                                <div class="flex flex-col items-center justify-center text-slate-400">
                                    <div class="w-16 h-16 bg-slate-50 rounded-full flex items-center justify-center mb-3">
                                        <i class="material-icons text-3xl opacity-40">receipt_long</i>
                                    </div>
                                    <p class="text-sm font-medium text-slate-500">Belum ada transaksi hari ini</p>
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

</div>
@endsection

@push("scripts")
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            
            // --- 1. SELECT2 INIT ---
            $('#year-select').select2({
                minimumResultsForSearch: Infinity, 
                width: '100%',
                dropdownCssClass: 'select2-dropdown-clean'
            });

            $('#sales-filter').select2({
                width: '100%',
                placeholder: 'Semua Sales',
                allowClear: true,
                dropdownCssClass: 'select2-dropdown-clean'
            });

            const formatRupiah = (val) => new Intl.NumberFormat('id-ID', { 
                style: 'currency', currency: 'IDR', minimumFractionDigits: 0 
            }).format(val);

            // --- 2. MAIN FINANCIAL CHART ---
            const ctxMain = document.getElementById('mainFinancialChart');
            let mainChart;

            if (ctxMain) {
                const ctx = ctxMain.getContext('2d');

                let gradientIndigo = ctx.createLinearGradient(0, 0, 0, 300);
                gradientIndigo.addColorStop(0, 'rgba(99, 102, 241, 0.15)');
                gradientIndigo.addColorStop(1, 'rgba(99, 102, 241, 0.0)');

                let gradientAmber = ctx.createLinearGradient(0, 0, 0, 300);
                gradientAmber.addColorStop(0, 'rgba(245, 158, 11, 0.15)');
                gradientAmber.addColorStop(1, 'rgba(245, 158, 11, 0.0)');

                const chartData = {
                    labels: @json($mainChartData['labels'] ?? []),
                    datasets: [
                        { 
                            label: 'Penjualan', 
                            data: @json($mainChartData['penjualan'] ?? []), 
                            borderColor: '#6366f1', 
                            backgroundColor: gradientIndigo,
                            tension: 0.4, fill: true, borderWidth: 2, 
                            pointRadius: 0, pointHoverRadius: 6, 
                            pointBackgroundColor: '#6366f1', pointBorderColor: '#fff', pointBorderWidth: 2
                        },
                        { 
                            label: 'Pembelian', 
                            data: @json($mainChartData['pembelian'] ?? []), 
                            borderColor: '#f59e0b', 
                            backgroundColor: gradientAmber,
                            tension: 0.4, fill: true, borderWidth: 2, 
                            pointRadius: 0, pointHoverRadius: 6,
                            pointBackgroundColor: '#f59e0b', pointBorderColor: '#fff', pointBorderWidth: 2
                        }
                    ]
                };

                mainChart = new Chart(ctxMain, {
                    type: 'line',
                    data: chartData,
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        interaction: { mode: 'index', intersect: false },
                        plugins: {
                            legend: { 
                                position: 'top', align: 'end', 
                                labels: { boxWidth: 8, usePointStyle: true, font: { family: "'Inter', sans-serif", size: 11 }, color: '#64748b' } 
                            },
                            tooltip: {
                                backgroundColor: 'rgba(255, 255, 255, 0.95)',
                                titleColor: '#1e293b', bodyColor: '#475569', borderColor: '#e2e8f0', borderWidth: 1, padding: 12,
                                titleFont: { family: "'Inter', sans-serif", size: 13, weight: '600' },
                                bodyFont: { family: "'Inter', sans-serif", size: 12 },
                                callbacks: { label: (c) => ` ${c.dataset.label}: ${formatRupiah(c.raw)}` }
                            }
                        },
                        scales: {
                            y: { beginAtZero: true, border: { display: false }, grid: { color: '#f1f5f9', borderDash: [5, 5] }, ticks: { callback: (v) => formatRupiah(v), font: {size: 10}, color: '#94a3b8', padding: 10 } },
                            x: { grid: { display: false }, ticks: { font: {size: 10}, color: '#94a3b8', padding: 10 } }
                        }
                    }
                });

                // Toggle Line/Bar
                const btnLine = document.getElementById('switchToLine');
                const btnBar = document.getElementById('switchToBar');

                if(btnLine && btnBar) {
                    const setActive = (activeBtn, inactiveBtn) => {
                        activeBtn.classList.add('bg-white', 'text-indigo-600', 'shadow-sm');
                        activeBtn.classList.remove('text-slate-500');
                        inactiveBtn.classList.remove('bg-white', 'text-indigo-600', 'shadow-sm');
                        inactiveBtn.classList.add('text-slate-500');
                    };

                    btnLine.addEventListener('click', () => {
                        mainChart.config.type = 'line';
                        mainChart.data.datasets.forEach(ds => { ds.borderRadius = 0; ds.fill = true; ds.barPercentage = undefined; });
                        mainChart.update();
                        setActive(btnLine, btnBar);
                    });

                    btnBar.addEventListener('click', () => {
                        mainChart.config.type = 'bar';
                        mainChart.data.datasets.forEach(ds => { ds.borderRadius = 4; ds.fill = false; ds.barPercentage = 0.6; });
                        mainChart.update();
                        setActive(btnBar, btnLine);
                    });
                }
            }

            // --- 3. TOP PRODUCTS CHART ---
            const ctxProd = document.getElementById('topProductsChart');
            if (ctxProd) {
                new Chart(ctxProd, {
                    type: 'doughnut',
                    data: {
                        labels: @json($topProductsChartData['labels'] ?? []),
                        datasets: [{
                            data: @json($topProductsChartData['data'] ?? []),
                            backgroundColor: ['#6366f1', '#10b981', '#f59e0b', '#ec4899', '#8b5cf6'], 
                            borderWidth: 0, hoverOffset: 10
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        cutout: '75%', 
                        plugins: {
                            legend: { 
                                position: 'right', 
                                labels: { usePointStyle: true, boxWidth: 8, padding: 15, font: {size: 11, family: "'Inter', sans-serif"}, color: '#64748b' } 
                            },
                            tooltip: {
                                backgroundColor: 'rgba(255, 255, 255, 0.95)',
                                titleColor: '#1e293b', bodyColor: '#475569', borderColor: '#e2e8f0', borderWidth: 1, padding: 12,
                                bodyFont: { family: "'Inter', sans-serif", size: 12 }
                            }
                        }
                    }
                });
            }
        });
    </script>
@endpush
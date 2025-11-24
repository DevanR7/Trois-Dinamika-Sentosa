@extends("layouts.app")

@section("title", "Dashboard")

@section("content")
<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-6 space-y-6">
    
    {{-- ========================================================================
         HEADER & FILTER TAHUN
         ======================================================================== --}}
    <div class="flex flex-col md:flex-row justify-between items-end md:items-center gap-4">
        <div>
            <h2 class="text-2xl font-bold text-gray-900 tracking-tight">Ringkasan Aktivitas</h2>
            @if(!$canViewFinancials)
                <p class="text-sm text-gray-500 mt-1">Selamat datang kembali, <span class="font-semibold text-indigo-600">{{ Auth::user()->full_name }}</span>!</p>
            @else
                <p class="text-sm text-gray-500 mt-1">Pantau performa bisnis Anda hari ini.</p>
            @endif
        </div>
        
        @if($canViewFinancials)
        <form action="{{ route('dashboard') }}" method="GET" class="flex items-center">
            {{-- Container Filter Tahun --}}
            <div class="flex items-center bg-white px-3 py-1.5 rounded-lg border border-gray-300 shadow-sm hover:border-indigo-300 transition-colors">
                <span class="text-[11px] font-bold text-gray-500 uppercase mr-2 tracking-wider flex items-center gap-1">
                    <i class="material-icons text-[16px]">calendar_today</i> Tahun:
                </span>
                
                <div class="w-[90px]">
                    <select name="year" id="year-select" class="w-full form-select text-sm border-none focus:ring-0 p-0" onchange="this.form.submit()">
                        @forelse($availableYears as $year)
                            <option value="{{ $year }}" @selected($selectedYear == $year)>{{ $year }}</option>
                        @empty
                            <option value="{{ date('Y') }}">{{ date('Y') }}</option>
                        @endforelse
                    </select>
                </div>
            </div>
        </form>
        @endif
    </div>

    {{-- ========================================================================
         BAGIAN 1: WIDGET QUICK STATS
         ======================================================================== --}}
    <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-6">
        
        {{-- WIDGET 1: SALDO KAS (Warna Biru) --}}
        @if($canViewFinancials)
        <div class="relative overflow-hidden rounded-xl bg-blue-600 text-white shadow-lg transition-transform hover:scale-[1.01] duration-300 group">
            <div class="p-6 relative z-10 h-full flex flex-col justify-between">
                <div class="flex items-start justify-between mb-4">
                    <div>
                        <p class="text-xs font-bold text-blue-100 uppercase tracking-wider mb-1">Saldo Kas & Bank</p>
                        <h3 class="text-3xl font-bold">Rp {{ number_format($financialHealth['cash_balance'] ?? 0, 0, ',', '.') }}</h3>
                    </div>
                    <div class="p-3 bg-white/20 rounded-lg backdrop-blur-sm group-hover:bg-white/30 transition flex items-center justify-center">
                        <i class="material-icons text-2xl">account_balance_wallet</i>
                    </div>
                </div>
                <div class="mt-auto">
                    <p class="text-xs text-blue-200 flex items-center gap-1">
                        <i class="material-icons text-[12px]">info</i> Total likuiditas tersedia saat ini.
                    </p>
                </div>
            </div>
            {{-- Background Decoration --}}
            <i class="material-icons absolute -right-6 -bottom-8 text-[9rem] text-white opacity-10 transform -rotate-12 pointer-events-none select-none">account_balance_wallet</i>
        </div>

        {{-- WIDGET 2: ESTIMASI LABA (Warna Hijau) --}}
        <div class="relative overflow-hidden rounded-xl bg-green-600 text-white shadow-lg transition-transform hover:scale-[1.01] duration-300 group">
            <div class="p-6 relative z-10 h-full flex flex-col justify-between">
                <div class="flex items-start justify-between mb-4">
                    <div>
                        <p class="text-xs font-bold text-green-100 uppercase tracking-wider mb-1">Estimasi Laba</p>
                        <h3 class="text-3xl font-bold">Rp {{ number_format($financialHealth['monthly_profit'] ?? 0, 0, ',', '.') }}</h3>
                    </div>
                    <div class="p-3 bg-white/20 rounded-lg backdrop-blur-sm group-hover:bg-white/30 transition flex items-center justify-center">
                        <i class="material-icons text-2xl">trending_up</i>
                    </div>
                </div>
                <div class="mt-auto">
                    <p class="text-xs text-green-200 flex items-center gap-1">
                        <i class="material-icons text-[12px]">calendar_month</i> Kinerja bulan {{ now()->format('F Y') }}.
                    </p>
                </div>
            </div>
            <i class="material-icons absolute -right-6 -bottom-6 text-[9rem] text-white opacity-10 transform -rotate-12 pointer-events-none select-none">trending_up</i>
        </div>
        @endif

        {{-- WIDGET 3: ACTION ITEMS (Warna Putih) --}}
        <div class="{{ $canViewFinancials ? '' : 'md:col-span-2 xl:col-span-3' }} bg-white rounded-xl shadow-sm border border-gray-200 flex flex-col h-full">
            <div class="px-5 py-3 border-b border-gray-100 bg-gray-50 rounded-t-xl flex justify-between items-center">
                <h6 class="text-xs font-bold text-gray-500 uppercase tracking-wider flex items-center gap-2">
                    <i class="material-icons text-sm text-yellow-500">notifications_active</i> Perlu Tindakan
                </h6>
                @if(array_sum($pendingActions) > 0)
                    <span class="flex h-2.5 w-2.5 relative">
                        <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-red-400 opacity-75"></span>
                        <span class="relative inline-flex rounded-full h-2.5 w-2.5 bg-red-500"></span>
                    </span>
                @endif
            </div>
            
            <div class="p-4 flex-1 overflow-y-auto custom-scrollbar max-h-[200px]">
                <div class="space-y-2">
                    {{-- Invoice Draft --}}
                    @if(($pendingActions['invoice_draft'] ?? 0) > 0)
                    <a href="{{ route('invoices.index', ['status' => 'draft']) }}" class="flex justify-between items-center p-2.5 rounded-lg bg-white border border-gray-200 hover:border-indigo-300 hover:shadow-sm transition group">
                        <div class="flex items-center gap-3">
                            <div class="w-8 h-8 rounded-full bg-gray-100 flex items-center justify-center text-gray-500 group-hover:bg-indigo-50 group-hover:text-indigo-600 transition">
                                <i class="material-icons text-lg">receipt_long</i>
                            </div>
                            <span class="text-sm font-medium text-gray-700">Invoice Draft</span>
                        </div>
                        <span class="bg-gray-100 text-gray-600 text-xs font-bold px-2.5 py-0.5 rounded-full border border-gray-200">{{ $pendingActions['invoice_draft'] }}</span>
                    </a>
                    @endif
                    
                    {{-- Draft PO --}}
                    @if(($pendingActions['po_draft'] ?? 0) > 0)
                    <a href="{{ route('purchase-orders.index') }}" class="flex justify-between items-center p-2.5 rounded-lg bg-white border border-gray-200 hover:border-indigo-300 hover:shadow-sm transition group">
                        <div class="flex items-center gap-3">
                            <div class="w-8 h-8 rounded-full bg-gray-100 flex items-center justify-center text-gray-500 group-hover:bg-indigo-50 group-hover:text-indigo-600 transition">
                                <i class="material-icons text-lg">shopping_cart</i>
                            </div>
                            <span class="text-sm font-medium text-gray-700">PO Draft</span>
                        </div>
                        <span class="bg-gray-100 text-gray-600 text-xs font-bold px-2.5 py-0.5 rounded-full border border-gray-200">{{ $pendingActions['po_draft'] }}</span>
                    </a>
                    @endif

                    {{-- Pending Kliring --}}
                    @if(($pendingActions['total_clearance'] ?? 0) > 0)
                    @can('manage-payment-clearance')
                    <a href="{{ route('payment-clearance.index') }}" class="flex justify-between items-center p-2.5 rounded-lg bg-amber-50 border border-amber-200 hover:border-amber-300 hover:shadow-sm transition group">
                        <div class="flex items-center gap-3">
                            <div class="w-8 h-8 rounded-full bg-amber-100 flex items-center justify-center text-amber-600">
                                <i class="material-icons text-lg">hourglass_top</i>
                            </div>
                            <span class="text-sm font-medium text-amber-800">Pending Kliring</span>
                        </div>
                        <span class="bg-amber-100 text-amber-700 text-xs font-bold px-2.5 py-0.5 rounded-full border border-amber-200">{{ $pendingActions['total_clearance'] }}</span>
                    </a>
                    @endcan
                    @endif

                    @if(array_sum($pendingActions) == 0)
                        <div class="flex flex-col items-center justify-center py-6 text-center">
                            <div class="w-12 h-12 bg-green-50 rounded-full flex items-center justify-center mb-2 text-green-500">
                                <i class="material-icons text-3xl">check_circle</i>
                            </div>
                            <span class="text-sm font-medium text-gray-900">Semua Beres!</span>
                            <span class="text-xs text-gray-500">Tidak ada tugas tertunda.</span>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>

    {{-- ========================================================================
         BAGIAN 2: GRAFIK KEUANGAN
         ======================================================================== --}}
    @if($canViewFinancials)
    <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
        <div class="flex flex-col sm:flex-row justify-between items-center mb-6 gap-4">
            <h5 class="text-base font-bold text-gray-800 flex items-center gap-2">
                <i class="material-icons text-indigo-500">analytics</i> Analisis Keuangan
            </h5>
            
            {{-- Toggle Chart Button --}}
            <div class="flex bg-gray-100 p-1 rounded-lg">
                <label class="cursor-pointer">
                    <input type="radio" name="chartType" id="switchToLine" class="peer sr-only" checked>
                    <span class="block px-4 py-1.5 text-xs font-medium rounded-md text-gray-500 peer-checked:bg-white peer-checked:text-indigo-600 peer-checked:shadow-sm transition-all">Line</span>
                </label>
                <label class="cursor-pointer">
                    <input type="radio" name="chartType" id="switchToBar" class="peer sr-only">
                    <span class="block px-4 py-1.5 text-xs font-medium rounded-md text-gray-500 peer-checked:bg-white peer-checked:text-indigo-600 peer-checked:shadow-sm transition-all">Bar</span>
                </label>
            </div>
        </div>
        {{-- Canvas Chart --}}
        <div class="relative h-[350px] w-full">
            <canvas id="mainFinancialChart"></canvas>
        </div>
    </div>
    @endif

    {{-- ========================================================================
         BAGIAN 3: STOK & PRODUK
         ======================================================================== --}}
    <div class="grid grid-cols-1 lg:grid-cols-12 gap-6">
        
        {{-- Grafik Produk Terlaris (Kiri) --}}
        <div class="lg:col-span-7 bg-white rounded-xl shadow-sm border border-gray-200 flex flex-col">
            <div class="px-6 py-4 border-b border-gray-100">
                <h5 class="text-sm font-bold text-gray-800">Produk Terlaris (Top 5)</h5>
            </div>
            <div class="p-6 flex-1 flex items-center justify-center">
                <div class="relative w-full max-w-[350px] h-[300px]">
                    <canvas id="topProductsChart"></canvas>
                </div>
            </div>
        </div>

        {{-- List Stok Menipis (Kanan) --}}
        @can('view-dashboard-inventory')
        <div class="lg:col-span-5 bg-white rounded-xl shadow-sm border border-gray-200 flex flex-col">
            <div class="px-6 py-4 border-b border-gray-100 flex justify-between items-center">
                <h5 class="text-sm font-bold text-gray-800 flex items-center gap-2">
                    <i class="material-icons text-red-500 text-lg">warning</i> Stok Menipis
                </h5>
                <a href="{{ route('products.index') }}" class="text-xs font-medium text-indigo-600 hover:text-indigo-800 hover:underline">Lihat Semua</a>
            </div>
            <div class="p-0 flex-1 overflow-y-auto max-h-[350px] custom-scrollbar">
                <ul class="divide-y divide-gray-50">
                    @forelse ($lowStockProducts as $product)
                        <li class="flex justify-between items-center px-6 py-3 hover:bg-gray-50 transition">
                            <div class="flex items-center gap-3">
                                <div class="w-8 h-8 rounded bg-red-50 flex items-center justify-center text-red-500 flex-shrink-0">
                                    <i class="material-icons text-lg">inventory_2</i>
                                </div>
                                <div>
                                    <p class="text-sm font-medium text-gray-800 line-clamp-1">{{ $product->product_name }}</p>
                                    <p class="text-[10px] text-gray-500">Kode: {{ $product->sku ?? '-' }}</p>
                                </div>
                            </div>
                            <span class="bg-red-100 text-red-700 text-xs font-bold px-2.5 py-1 rounded-md border border-red-200 min-w-[60px] text-center">
                                {{ $product->stock_quantity }} Unit
                            </span>
                        </li>
                    @empty
                        <li class="text-center py-10 text-gray-400 text-sm flex flex-col items-center">
                            <i class="material-icons text-4xl mb-2 text-green-400">check_circle</i>
                            <p>Semua stok produk aman.</p>
                        </li>
                    @endforelse
                </ul>
            </div>
        </div>
        @endcan
    </div>

    {{-- ========================================================================
         BAGIAN 4: TABEL TRANSAKSI TERBARU
         ======================================================================== --}}
    <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
        <div class="px-6 py-4 border-b border-gray-100 flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4">
            <h5 class="text-sm font-bold text-gray-800">Penjualan Terbaru</h5>
            
            @if($canViewFinancials)
            <form action="{{ route('dashboard') }}" method="GET" class="flex items-center gap-2">
                <input type="hidden" name="year" value="{{ $selectedYear }}">
                <label class="text-xs font-medium text-gray-500">Sales:</label>
                {{-- Menggunakan Select2 standar (theme bootstrap-5 tapi di override css app.css) --}}
                <select name="sales_id" id="sales-filter" class="form-select py-1 pl-2 pr-8 text-xs border-gray-300 rounded-md shadow-sm focus:border-indigo-500 focus:ring-indigo-500 w-48" onchange="this.form.submit()">
                    <option value="">-- Semua User --</option>
                    @foreach($filterableUsers as $role => $users)
                        <optgroup label="{{ $role }}">
                            @foreach($users as $u)
                                <option value="{{ $u->user_id }}" @selected($selectedSalesId == $u->user_id)>{{ $u->full_name }}</option>
                            @endforeach
                        </optgroup>
                    @endforeach
                </select>
            </form>
            @endif
        </div>
        
        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse">
                <thead class="bg-gray-50 border-b border-gray-200">
                    <tr>
                        <th class="px-6 py-3 text-xs font-bold text-gray-500 uppercase tracking-wider">No. Pesanan</th>
                        <th class="px-6 py-3 text-xs font-bold text-gray-500 uppercase tracking-wider">Klien</th>
                        <th class="px-6 py-3 text-xs font-bold text-gray-500 uppercase tracking-wider">Sales</th>
                        <th class="px-6 py-3 text-xs font-bold text-gray-500 uppercase tracking-wider">Tanggal</th>
                        <th class="px-6 py-3 text-xs font-bold text-gray-500 uppercase tracking-wider text-center">Status</th>
                        <th class="px-6 py-3 text-xs font-bold text-gray-500 uppercase tracking-wider text-right">Jumlah</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @forelse ($latestOrders as $order)
                        <tr class="hover:bg-gray-50 transition-colors">
                            <td class="px-6 py-3">
                                <a href="{{ route('sales-orders.show', $order->order_id) }}" class="text-sm font-bold text-indigo-600 hover:text-indigo-800 hover:underline font-mono">
                                    {{ $order->order_number }}
                                </a>
                            </td>
                            <td class="px-6 py-3 text-sm text-gray-700">{{ $order->client->client_name ?? "N/A" }}</td>
                            <td class="px-6 py-3 text-sm text-gray-500">{{ $order->sales->full_name ?? "N/A" }}</td>
                            <td class="px-6 py-3 text-sm text-gray-500">{{ optional($order->order_date)->format("d M Y") }}</td>
                            <td class="px-6 py-3 text-center">
                                @php
                                    $statusStyles = [
                                        'pending'  => 'bg-yellow-100 text-yellow-800 border-yellow-200',
                                        'approved' => 'bg-cyan-100 text-cyan-800 border-cyan-200',
                                        'rejected' => 'bg-red-100 text-red-800 border-red-200',
                                        'invoiced' => 'bg-green-100 text-green-800 border-green-200',
                                    ];
                                    $style = $statusStyles[$order->status] ?? 'bg-gray-100 text-gray-800 border-gray-200';
                                @endphp
                                <span class="px-2.5 py-0.5 rounded-full text-[10px] font-bold border uppercase {{ $style }}">
                                    {{ str_replace('_', ' ', $order->status) }}
                                </span>
                            </td>
                            <td class="px-6 py-3 text-right text-sm font-bold text-gray-900">
                                @if($canViewFinancials) 
                                    Rp {{ number_format($order->total_amount, 0, ",", ".") }}
                                @else 
                                    <span class="text-gray-400 text-xs italic">Tersembunyi</span> 
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="px-6 py-12 text-center text-gray-500 text-sm">
                                <div class="flex flex-col items-center justify-center">
                                    <i class="material-icons text-3xl text-gray-300 mb-2">shopping_bag</i>
                                    <p>Belum ada pesanan penjualan terbaru.</p>
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
            const formatRupiah = (val) => new Intl.NumberFormat('id-ID', { style: 'currency', currency: 'IDR', minimumFractionDigits: 0 }).format(val);

            // 1. INISIALISASI SELECT2 UNTUK FILTER
            $('#year-select').select2({
                theme: 'bootstrap-5',
                minimumResultsForSearch: -1,
                width: '100%',
                dropdownCssClass: 'text-sm'
            });
            
            $('#sales-filter').select2({
                theme: 'bootstrap-5',
                width: '200px',
                dropdownCssClass: 'text-sm'
            });

            // 2. GRAFIK KEUANGAN
            @if(isset($mainChartData) && $mainChartData)
                const ctxMain = document.getElementById('mainFinancialChart');
                if (ctxMain) {
                    let mainChart = new Chart(ctxMain, {
                        type: 'line',
                        data: {
                            labels: @json($mainChartData['labels']),
                            datasets: [
                                { label: 'Penjualan', data: @json($mainChartData['penjualan']), borderColor: '#4f46e5', backgroundColor: 'rgba(79, 70, 229, 0.1)', tension: 0.4, fill: true, borderWidth: 2 },
                                { label: 'Pembelian', data: @json($mainChartData['pembelian']), borderColor: '#f59e0b', backgroundColor: 'rgba(245, 158, 11, 0.1)', tension: 0.4, fill: true, borderWidth: 2 }
                            ]
                        },
                        options: {
                            responsive: true,
                            maintainAspectRatio: false,
                            interaction: { mode: 'index', intersect: false },
                            scales: { y: { ticks: { callback: (val) => formatRupiah(val), font: {size: 10} }, grid: { borderDash: [4, 4] }, border: { display: false } }, x: { grid: { display: false }, border: { display: false } } },
                            plugins: { legend: { position: 'top', align: 'end', labels: { boxWidth: 10, usePointStyle: true, padding: 20 } }, tooltip: { padding: 10, backgroundColor: '#1e293b' } }
                        }
                    });
                    document.getElementById('switchToBar')?.addEventListener('click', () => { mainChart.config.type = 'bar'; mainChart.update(); });
                    document.getElementById('switchToLine')?.addEventListener('click', () => { mainChart.config.type = 'line'; mainChart.update(); });
                }
            @endif

            // 3. GRAFIK PRODUK
            @if(isset($topProductsChartData) && $topProductsChartData)
                const ctxProd = document.getElementById('topProductsChart');
                if (ctxProd) {
                    new Chart(ctxProd, {
                        type: 'doughnut',
                        data: {
                            labels: @json($topProductsChartData['labels']),
                            datasets: [{
                                data: @json($topProductsChartData['data']),
                                backgroundColor: ['#4f46e5', '#10b981', '#f59e0b', '#ef4444', '#8b5cf6'],
                                borderWidth: 0,
                                hoverOffset: 10
                            }]
                        },
                        options: {
                            responsive: true,
                            maintainAspectRatio: false,
                            cutout: '75%',
                            plugins: { legend: { position: 'bottom', labels: { usePointStyle: true, padding: 20, font: {size: 11} } } }
                        }
                    });
                }
            @endif
        });
    </script>
@endpush
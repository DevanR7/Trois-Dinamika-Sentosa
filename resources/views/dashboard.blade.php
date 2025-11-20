@extends("layouts.app")

@section("content")
    <div class="container-fluid py-4">
        
        {{-- HEADER & FILTER --}}
        <div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
            <h2 class="fw-bold mb-0">Dashboard</h2>
            
            {{-- Filter Tahun hanya muncul untuk Petinggi (karena mempengaruhi keuangan) --}}
            @if($canViewFinancials)
            <form action="{{ route('dashboard') }}" method="GET" class="d-flex align-items-center gap-2">
                <label for="year-filter" class="form-label mb-0 fw-semibold">Tahun:</label>
                <select name="year" id="year-filter" class="form-select form-select-sm" onchange="this.form.submit()" style="width: 120px;">
                    @forelse($availableYears as $year)
                        <option value="{{ $year }}" @selected($selectedYear == $year)>{{ $year }}</option>
                    @empty
                        <option>{{ date('Y') }}</option>
                    @endforelse
                </select>
            </form>
            @else
            <span class="text-muted">Halo, {{ Auth::user()->full_name }}! Berikut ringkasan aktivitas Anda.</span>
            @endif
        </div>

        {{-- ✅ BAGIAN BARU: WIDGET QUICK INSIGHTS (INFO CEPAT) --}}
        <div class="row g-4 mb-4">
            {{-- KESEHATAN KAS (Hanya Petinggi) --}}
            @if($canViewFinancials)
            <div class="col-md-6 col-xl-4">
                <div class="card shadow-sm h-100 bg-primary text-white border-0 position-relative overflow-hidden">
                    {{-- Content Wrapper (Z-Index lebih tinggi agar teks di atas icon) --}}
                    <div class="card-body position-relative" style="z-index: 2;">
                        <div class="d-flex align-items-center mb-3">
                            <div class="bg-white bg-opacity-25 rounded-circle p-2 me-3 d-flex align-items-center justify-content-center" style="width: 48px; height: 48px;">
                                <i class="bi bi-wallet2 fs-4"></i>
                            </div>
                            <h6 class="card-title mb-0 text-white-50 text-uppercase fw-semibold small">Saldo Kas & Bank (Real-time)</h6>
                        </div>
                        <h3 class="fw-bold mb-1">Rp {{ number_format($financialHealth['cash_balance'] ?? 0, 0, ',', '.') }}</h3>
                        <small class="text-white-50">Total likuiditas tersedia saat ini.</small>
                    </div>
                    
                    {{-- Background Icon (Watermark) --}}
                    <i class="bi bi-wallet2" style="
                        position: absolute;
                        right: -20px;
                        bottom: -20px;
                        font-size: 9rem; /* Ukuran besar */
                        opacity: 0.15;   /* Transparan */
                        transform: rotate(-15deg); /* Miring sedikit */
                        z-index: 1;
                        color: white;
                    "></i>
                </div>
            </div>

            <div class="col-md-6 col-xl-4">
                <div class="card shadow-sm h-100 bg-success text-white border-0 position-relative overflow-hidden">
                    {{-- Content Wrapper --}}
                    <div class="card-body position-relative" style="z-index: 2;">
                        <div class="d-flex align-items-center mb-3">
                            <div class="bg-white bg-opacity-25 rounded-circle p-2 me-3 d-flex align-items-center justify-content-center" style="width: 48px; height: 48px;">
                                <i class="bi bi-graph-up-arrow fs-4"></i>
                            </div>
                            <h6 class="card-title mb-0 text-white-50 text-uppercase fw-semibold small">Estimasi Laba Bersih (Bulan Ini)</h6>
                        </div>
                        <h3 class="fw-bold mb-1">Rp {{ number_format($financialHealth['monthly_profit'] ?? 0, 0, ',', '.') }}</h3>
                        <small class="text-white-50">Kinerja profitabilitas bulan {{ now()->format('F Y') }}.</small>
                    </div>

                    {{-- Background Icon (Watermark) --}}
                    <i class="bi bi-graph-up-arrow" style="
                        position: absolute;
                        right: -20px;
                        bottom: -10px;
                        font-size: 9rem;
                        opacity: 0.15;
                        transform: rotate(-15deg);
                        z-index: 1;
                        color: white;
                    "></i>
                </div>
            </div>
            @endif

            {{-- PERLU TINDAKAN (Semua Role) --}}
            <div class="col-md-12 {{ $canViewFinancials ? 'col-xl-4' : 'col-xl-12' }}">
                <div class="card shadow-sm h-100 border-0">
                    <div class="card-header bg-white border-0 pb-0 pt-3">
                        <h6 class="fw-bold mb-0 text-uppercase small text-muted"><i class="bi bi-bell-fill text-warning me-1"></i> Perlu Tindakan</h6>
                    </div>
                    <div class="card-body">
                        <div class="d-flex flex-column gap-2">
                            @if(($pendingActions['invoice_draft'] ?? 0) > 0)
                            <a href="{{ route('invoices.index', ['status' => 'draft']) }}" class="d-flex justify-content-between align-items-center text-decoration-none p-2 rounded bg-light text-dark border">
                                <span><i class="bi bi-file-earmark-text me-2 text-secondary"></i>Invoice Draft</span>
                                <span class="badge bg-secondary rounded-pill">{{ $pendingActions['invoice_draft'] }}</span>
                            </a>
                            @endif
                            
                            @if(($pendingActions['po_draft'] ?? 0) > 0)
                            <a href="{{ route('purchase-orders.index') }}" class="d-flex justify-content-between align-items-center text-decoration-none p-2 rounded bg-light text-dark border">
                                <span><i class="bi bi-cart me-2 text-secondary"></i>PO Draft</span>
                                <span class="badge bg-secondary rounded-pill">{{ $pendingActions['po_draft'] }}</span>
                            </a>
                            @endif

                            @if(($pendingActions['total_clearance'] ?? 0) > 0)
                            @can('manage-payment-clearance')
                            <a href="{{ route('payment-clearance.index') }}" class="d-flex justify-content-between align-items-center text-decoration-none p-2 rounded bg-warning bg-opacity-10 text-dark border border-warning">
                                <span><i class="bi bi-hourglass-split me-2 text-warning"></i>Pending Kliring</span>
                                <span class="badge bg-warning text-dark rounded-pill">{{ $pendingActions['total_clearance'] }}</span>
                            </a>
                            @endcan
                            @endif

                            @if(array_sum($pendingActions) == 0)
                                <div class="text-center text-muted py-4">
                                    <div class="mb-2"><i class="bi bi-check-circle text-success fs-1"></i></div>
                                    <div class="fw-medium">Semua Beres!</div>
                                    <small>Tidak ada tugas tertunda.</small>
                                </div>
                            @endif
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- ✅ BAGIAN 1: KARTU STATISTIK (ADAPTIF: NOMINAL ATAU JUMLAH) --}}
        <div class="row row-cols-1 row-cols-md-2 row-cols-xl-5 g-4 mb-4">
            
            {{-- Kartu 1: Pendapatan / Transaksi Bayar --}}
            <div class="col">
                <div class="card shadow-sm h-100 border-start border-success border-4">
                    <div class="card-body">
                        <div class="text-muted text-uppercase small">{{ $stats['labels']['revenue'] }}</div>
                        <h4 class="fw-bold mb-0">
                            @if($canViewFinancials) Rp {{ number_format($stats['revenue'], 0, ",", ".") }}
                            @else {{ number_format($stats['revenue'], 0, ",", ".") }} Transaksi @endif
                        </h4>
                    </div>
                </div>
            </div>

            {{-- Kartu 2: Piutang / Invoice Belum Lunas --}}
            <div class="col">
                <div class="card shadow-sm h-100 border-start border-danger border-4">
                    <div class="card-body">
                        <div class="text-muted text-uppercase small">{{ $stats['labels']['piutang'] }}</div>
                        <h4 class="fw-bold mb-0">
                            @if($canViewFinancials) Rp {{ number_format($stats['piutang'], 0, ",", ".") }}
                            @else {{ number_format($stats['piutang'], 0, ",", ".") }} Nota @endif
                        </h4>
                    </div>
                </div>
            </div>

            {{-- Kartu 3: Utang / PO Belum Lunas --}}
            <div class="col">
                <div class="card shadow-sm h-100 border-start border-warning border-4">
                    <div class="card-body">
                        <div class="text-muted text-uppercase small">{{ $stats['labels']['hutang'] }}</div>
                        <h4 class="fw-bold mb-0">
                            @if($canViewFinancials) Rp {{ number_format($stats['hutang'], 0, ",", ".") }}
                            @else {{ number_format($stats['hutang'], 0, ",", ".") }} PO @endif
                        </h4>
                    </div>
                </div>
            </div>

            {{-- Kartu 4: Retur Jual --}}
            <div class="col">
                <div class="card shadow-sm h-100 border-start border-info border-4">
                    <div class="card-body">
                        <div class="text-muted text-uppercase small">{{ $stats['labels']['sales_return'] }}</div>
                        <h4 class="fw-bold mb-0">
                            @if($canViewFinancials) Rp {{ number_format($stats['sales_return'], 0, ",", ".") }}
                            @else {{ number_format($stats['sales_return'], 0, ",", ".") }} Nota @endif
                        </h4>
                    </div>
                </div>
            </div>

            {{-- Kartu 5: Retur Beli --}}
            <div class="col">
                <div class="card shadow-sm h-100 border-start border-secondary border-4">
                    <div class="card-body">
                        <div class="text-muted text-uppercase small">{{ $stats['labels']['purchase_return'] }}</div>
                        <h4 class="fw-bold mb-0">
                            @if($canViewFinancials) Rp {{ number_format($stats['purchase_return'], 0, ",", ".") }}
                            @else {{ number_format($stats['purchase_return'], 0, ",", ".") }} Nota @endif
                        </h4>
                    </div>
                </div>
            </div>
        </div>

        {{-- GRAFIK KEUANGAN (HANYA PETINGGI) --}}
        @if($canViewFinancials)
        <div class="card shadow-sm mb-4">
            <div class="card-header bg-white d-flex justify-content-between align-items-center flex-wrap gap-2">
                <h5 class="mb-0 fw-semibold">Analisis Keuangan (Tahun {{ $selectedYear }})</h5>
                <div class="btn-group btn-group-sm" role="group">
                    <input type="radio" class="btn-check" name="chartType" id="switchToLine" autocomplete="off" checked>
                    <label class="btn btn-outline-primary" for="switchToLine">Line</label>
                    <input type="radio" class="btn-check" name="chartType" id="switchToBar" autocomplete="off">
                    <label class="btn btn-outline-primary" for="switchToBar">Bar</label>
                </div>
            </div>
            <div class="card-body">
                <div style="height: 350px;">
                    <canvas id="mainFinancialChart"></canvas>
                </div>
            </div>
        </div>
        @endif

        {{-- BAGIAN 2: GRAFIK PRODUK & STOK (SEMUA BISA LIHAT) --}}
        <div class="row g-4 mb-4">
            
            {{-- Grafik Produk Terlaris (Bisa dilihat semua role, karena berdasarkan Qty bukan Rp) --}}
            <div class="col-lg-7">
                <div class="card shadow-sm h-100">
                    <div class="card-header bg-white"><h5 class="mb-0 fw-semibold">5 Produk Terlaris (Unit Terjual)</h5></div>
                    <div class="card-body d-flex align-items-center justify-content-center">
                        <div style="position: relative; height:300px; width:100%">
                             <canvas id="topProductsChart"></canvas>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Daftar Stok Menipis --}}
            @can('view-dashboard-inventory')
            <div class="col-lg-5">
                <div class="card shadow-sm h-100">
                    <div class="card-header bg-white"><h5 class="mb-0 fw-semibold">Produk Stok Menipis (&le;10)</h5></div>
                    <div class="card-body">
                        <ul class="list-group list-group-flush">
                            @forelse ($lowStockProducts as $product)
                                <li class="list-group-item d-flex justify-content-between align-items-center px-0">
                                    <span class="fw-medium">{{ $product->product_name }}</span>
                                    <span class="badge bg-danger rounded-pill">{{ $product->stock_quantity }}</span>
                                </li>
                            @empty
                                <li class="list-group-item px-0 text-center text-muted">Semua stok produk aman.</li>
                            @endforelse
                        </ul>
                    </div>
                </div>
            </div>
            @endcan
        </div>
        
        {{-- BAGIAN 3: TABEL AKTIVITAS --}}
        <div class="row g-4">
            <div class="col-lg-12">
                <div class="card shadow-sm">
                    <div class="card-header bg-white d-flex justify-content-between align-items-center flex-wrap gap-2">
                        <h5 class="mb-0 fw-semibold">Pesanan Penjualan Terbaru</h5>
                        
                        @if($canViewFinancials)
                        <form action="{{ route('dashboard') }}" method="GET">
                            <input type="hidden" name="year" value="{{ $selectedYear }}">
                            <div class="input-group">
                                <select name="sales_id" class="form-select form-select-sm" onchange="this.form.submit()">
                                    <option value="">-- Semua User --</option>
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
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-hover align-middle">
                                <thead><tr class="table-light"><th>No. Pesanan</th><th>Klien</th><th>Sales</th><th>Tanggal</th><th class="text-center">Status</th><th class="text-end">Jumlah</th></tr></thead>
                                <tbody>
                                    @forelse ($latestOrders as $order)
                                        <tr>
                                            <td>{{ $order->order_number }}</td>
                                            <td>{{ $order->client->client_name ?? "N/A" }}</td>
                                            <td>{{ $order->sales->full_name ?? "N/A" }}</td>
                                            <td>{{ optional($order->order_date)->format("d M Y") }}</td>
                                            <td class="text-center">
                                                <span class="badge bg-secondary">{{ Str::title($order->status) }}</span>
                                            </td>
                                            <td class="text-end">
                                                @if($canViewFinancials) Rp {{ number_format($order->total_amount, 0, ",", ".") }}
                                                @else <span class="text-muted small">Tersembunyi</span> @endif
                                            </td>
                                        </tr>
                                    @empty
                                        <tr><td colspan="6" class="text-center text-muted">Belum ada pesanan penjualan.</td></tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

@push("scripts")
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const formatRupiah = (value) => new Intl.NumberFormat('id-ID', { style: 'currency', currency: 'IDR', minimumFractionDigits: 0 }).format(value);

            // GRAFIK UTAMA (Hanya dirender jika data ada)
            @if(isset($mainChartData) && $mainChartData)
                const mainChartData = @json($mainChartData);
                const ctxMain = document.getElementById('mainFinancialChart');
                if (ctxMain) {
                    const mainChart = new Chart(ctxMain, {
                        type: 'line',
                        data: {
                            labels: mainChartData.labels,
                            datasets: [
                                { label: 'Penjualan', data: mainChartData.penjualan, borderColor: '#0d6efd', backgroundColor: 'rgba(13, 110, 253, 0.1)', tension: 0.3, fill: true },
                                { label: 'Pembelian', data: mainChartData.pembelian, borderColor: '#ffc107', backgroundColor: 'rgba(255, 193, 7, 0.1)', tension: 0.3, fill: true },
                                { label: 'Pendapatan', data: mainChartData.pendapatan, borderColor: '#198754', backgroundColor: 'rgba(25, 135, 84, 0.1)', tension: 0.3, fill: true }
                            ]
                        },
                        options: {
                            responsive: true,
                            maintainAspectRatio: false,
                            scales: { y: { beginAtZero: true, ticks: { callback: (value) => formatRupiah(value) } } },
                            plugins: { 
                                tooltip: { callbacks: { label: (context) => `${context.dataset.label}: ${formatRupiah(context.raw)}` } } 
                            }
                        }
                    });
                    document.getElementById('switchToBar')?.addEventListener('click', () => { mainChart.config.type = 'bar'; mainChart.update(); });
                    document.getElementById('switchToLine')?.addEventListener('click', () => { mainChart.config.type = 'line'; mainChart.update(); });
                }
            @endif

            // GRAFIK PRODUK (Selalu ada)
            @if(isset($topProductsChartData) && $topProductsChartData)
                const topProductsData = @json($topProductsChartData);
                const ctxTopProducts = document.getElementById('topProductsChart');
                if (ctxTopProducts && topProductsData.data.length > 0) {
                    new Chart(ctxTopProducts, {
                        type: 'pie',
                        data: {
                            labels: topProductsData.labels,
                            datasets: [{
                                label: 'Jumlah Terjual',
                                data: topProductsData.data,
                                backgroundColor: ['#0d6efd', '#6c757d', '#198754', '#ffc107', '#dc3545'],
                                hoverOffset: 4
                            }]
                        },
                        options: {
                            responsive: true,
                            maintainAspectRatio: false,
                            plugins: { legend: { position: 'bottom' } }
                        }
                    });
                } else if (ctxTopProducts) {
                     ctxTopProducts.parentElement.innerHTML = '<div class="text-center text-muted">Tidak ada data penjualan produk untuk ditampilkan.</div>';
                }
            @endif
        });
    </script>
@endpush
@extends("layouts.app")

@section("content")
    <div class="container-fluid py-4">
        {{-- HEADER DENGAN FILTER TAHUN --}}
        <div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
            <h2 class="fw-bold mb-0">Dashboard</h2>
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
        </div>

        {{-- BARIS KARTU STATISTIK BARU (5 KOLOM) --}}
        <div class="row row-cols-1 row-cols-md-2 row-cols-xl-5 g-4 mb-4">
            <div class="col">
                <div class="card shadow-sm h-100 border-start border-success border-4">
                    <div class="card-body">
                        <div class="text-muted text-uppercase small">Total Pendapatan</div>
                        <h4 class="fw-bold mb-0">Rp {{ number_format($totalRevenue, 0, ",", ".") }}</h4>
                    </div>
                </div>
            </div>
            <div class="col">
                <div class="card shadow-sm h-100 border-start border-danger border-4">
                    <div class="card-body">
                        <div class="text-muted text-uppercase small">Total Piutang</div>
                        <h4 class="fw-bold mb-0">Rp {{ number_format($totalPiutang, 0, ",", ".") }}</h4>
                    </div>
                </div>
            </div>
            <div class="col">
                <div class="card shadow-sm h-100 border-start border-warning border-4">
                    <div class="card-body">
                        <div class="text-muted text-uppercase small">Total Utang</div>
                        <h4 class="fw-bold mb-0">Rp {{ number_format($totalHutang, 0, ",", ".") }}</h4>
                    </div>
                </div>
            </div>
            <div class="col">
                <div class="card shadow-sm h-100 border-start border-info border-4">
                    <div class="card-body">
                        <div class="text-muted text-uppercase small">Retur Penjualan</div>
                        <h4 class="fw-bold mb-0">Rp {{ number_format($totalSalesReturn, 0, ",", ".") }}</h4>
                    </div>
                </div>
            </div>
            <div class="col">
                <div class="card shadow-sm h-100 border-start border-secondary border-4">
                    <div class="card-body">
                        <div class="text-muted text-uppercase small">Retur Pembelian</div>
                        <h4 class="fw-bold mb-0">Rp {{ number_format($totalPurchaseReturn, 0, ",", ".") }}</h4>
                    </div>
                </div>
            </div>
        </div>

        {{-- GRAFIK UTAMA INTERAKTIF --}}
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
        
        {{-- GRAFIK PRODUK & STOK --}}
        <div class="row g-4 mb-4">
            <div class="col-lg-7">
                <div class="card shadow-sm h-100">
                    <div class="card-header bg-white"><h5 class="mb-0 fw-semibold">5 Produk Terlaris (Tahun {{ $selectedYear }})</h5></div>
                    <div class="card-body d-flex align-items-center justify-content-center">
                        <div style="position: relative; height:300px; width:100%">
                             <canvas id="topProductsChart"></canvas>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-lg-5">
                <div class="card shadow-sm h-100">
                    <div class="card-header bg-white"><h5 class="mb-0 fw-semibold">Produk Stok Menipis (&le;10)</h5></div>
                    <div class="card-body">
                        <ul class="list-group list-group-flush">
                            @forelse ($lowStockProducts as $product)
                                <li class="list-group-item d-flex justify-content-between align-items-center px-0">
                                    <a href="{{ route('products.edit', $product->product_id) }}" class="text-decoration-none">{{ $product->product_name }}</a>
                                    <span class="badge bg-danger rounded-pill">{{ $product->stock_quantity }}</span>
                                </li>
                            @empty
                                <li class="list-group-item px-0 text-center text-muted">Semua stok produk aman.</li>
                            @endforelse
                        </ul>
                    </div>
                </div>
            </div>
        </div>
        
        {{-- KINERJA PENJUALAN DENGAN FILTER UNIVERSAL --}}
        <div class="card shadow-sm">
            <div class="card-header bg-white d-flex justify-content-between align-items-center flex-wrap gap-2">
                <h5 class="mb-0 fw-semibold">Pesanan Penjualan Terbaru (Tahun {{ $selectedYear }})</h5>
                <form action="{{ route('dashboard') }}" method="GET">
                    <input type="hidden" name="year" value="{{ $selectedYear }}">
                    <div class="input-group">
                        <select name="sales_id" class="form-select form-select-sm" onchange="this.form.submit()">
                            <option value="">-- Semua User --</option>
                            @foreach($filterableUsers as $role => $users)
                                <optgroup label="{{ $role }}">
                                    @foreach($users as $user)
                                        <option value="{{ $user->user_id }}" @selected($selectedSalesId == $user->user_id)>{{ $user->full_name }}</option>
                                    @endforeach
                                </optgroup>
                            @endforeach
                        </select>
                    </div>
                </form>
            </div>
            <div class="card-body">
                <div class="table-responsive">
        <table class="table table-hover align-middle">
            <thead><tr class="table-light"><th>No. Pesanan</th><th>Klien</th><th>Sales</th><th>Tanggal</th><th class="text-center">Status</th><th class="text-end">Jumlah</th></tr></thead>
            <tbody>
                {{-- ✅ BERUBAH: Menggunakan variabel $latestOrders --}}
                @forelse ($latestOrders as $order)
                    <tr>
                        {{-- Link route 'sales-orders.show' masih benar karena merujuk ke route admin --}}
                        <td><a href="{{ route('sales-orders.show', $order->order_id) }}">{{ $order->order_number }}</a></td>
                        <td>{{ $order->client->client_name ?? "N/A" }}</td>
                        <td>{{ $order->sales->full_name ?? "N/A" }}</td>
                        <td>{{ optional($order->order_date)->format("d M Y") }}</td>
                        {{-- Status badge bisa dibuat lebih dinamis --}}
                        @php
                            $statusClass = [
                                'pending' => 'bg-secondary',
                                'approved' => 'bg-info text-dark',
                                'rejected' => 'bg-danger',
                                'invoiced' => 'bg-success',
                            ];
                        @endphp
                        <td class="text-center">
                            <span class="badge {{ $statusClass[$order->status] ?? 'bg-light text-dark' }}">
                                {{ Str::title(str_replace('_', ' ', $order->status)) }}
                            </span>
                        </td>
                        <td class="text-end">Rp {{ number_format($order->total_amount, 0, ",", ".") }}</td>
                    </tr>
                @empty
                    <tr><td colspan="6" class="text-center text-muted">Belum ada pesanan penjualan.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
            </div>
  <div class="card shadow-sm">
            <div class="card-header bg-white">
                <h5 class="mb-0 fw-semibold">Invoice Berjalan (Belum Lunas)</h5>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-hover align-middle">
                        <thead>
                            <tr class="table-light">
                                <th>No. Invoice</th>
                                <th>Klien</th>
                                <th>Jatuh Tempo</th>
                                <th class="text-center">Status</th>
                                <th class="text-end">Sisa Tagihan</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($latestRunningInvoices as $invoice)
                                @php
                                    $sisaTagihan = $invoice->total_amount - $invoice->amount_paid - $invoice->returns->sum('total_amount');
                                @endphp
                                <tr>
                                    <td><a href="{{ route('invoices.show', $invoice->invoice_id) }}">{{ $invoice->invoice_number }}</a></td>
                                    <td>{{ $invoice->client->client_name ?? "N/A" }}</td>
                                    <td class="{{ optional($invoice->due_date)->isPast() ? 'text-danger fw-bold' : '' }}">
                                        {{ optional($invoice->due_date)->format("d M Y") }}
                                    </td>
                                    <td class="text-center">
                                        @if($invoice->status == 'partially_paid')
                                            <span class="badge bg-info text-dark">Cicil</span>
                                        @else
                                            <span class="badge bg-warning text-dark">Belum Lunas</span>
                                        @endif
                                    </td>
                                    <td class="text-end fw-bold text-danger">
                                        Rp {{ number_format($sisaTagihan, 0, ",", ".") }}
                                    </td>
                                </tr>
                            @empty
                                <tr><td colspan="5" class="text-center text-muted">Tidak ada invoice yang sedang berjalan.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
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
            const mainChartData = @json($mainChartData);
            const topProductsData = @json($topProductsChartData);

            // --- GRAFIK UTAMA (KEUANGAN) ---
            const ctxMain = document.getElementById('mainFinancialChart');
            const mainChart = new Chart(ctxMain, {
                type: 'line',
                data: {
                    labels: mainChartData.labels,
                    datasets: [
                        { label: 'Penjualan', data: mainChartData.penjualan, borderColor: '#0d6efd', backgroundColor: 'rgba(13, 110, 253, 0.1)', tension: 0.3, fill: true, hidden: false },
                        { label: 'Pembelian', data: mainChartData.pembelian, borderColor: '#ffc107', backgroundColor: 'rgba(255, 193, 7, 0.1)', tension: 0.3, fill: true, hidden: false },
                        { label: 'Pendapatan', data: mainChartData.pendapatan, borderColor: '#198754', backgroundColor: 'rgba(25, 135, 84, 0.1)', tension: 0.3, fill: true, hidden: false }
                    ]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    scales: { y: { beginAtZero: true, ticks: { callback: (value) => formatRupiah(value) } } },
                    plugins: { 
                        legend: { 
                            position: 'top',
                            onClick: (e, legendItem, legend) => {
                                const index = legendItem.datasetIndex;
                                const ci = legend.chart;
                                const meta = ci.getDatasetMeta(index);
                                meta.hidden = !meta.hidden;
                                ci.update();
                            }
                        }, 
                        tooltip: { callbacks: { label: (context) => `${context.dataset.label}: ${formatRupiah(context.raw)}` } } 
                    }
                }
            });
            document.getElementById('switchToBar').addEventListener('click', () => { mainChart.config.type = 'bar'; mainChart.update(); });
            document.getElementById('switchToLine').addEventListener('click', () => { mainChart.config.type = 'line'; mainChart.update(); });
            
            // --- GRAFIK PRODUK TERLARIS (PIE) ---
            const ctxTopProducts = document.getElementById('topProductsChart');
            if (topProductsData.data.length > 0) {
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
            } else {
                 ctxTopProducts.parentElement.innerHTML = '<div class="text-center text-muted">Tidak ada data penjualan produk untuk ditampilkan.</div>';
            }
        });
    </script>
@endpush
@extends("layouts.app")

@section("content")
    <div class="container-fluid py-4">
        <h2 class="fw-bold mb-4">Dashboard</h2>

        {{-- Baris untuk Kartu Statistik --}}
        <div class="row g-4 mb-4">
            <div class="col-md-4">
                <div class="card shadow-sm h-100">
                    <div class="card-body d-flex align-items-center">
                        <div class="flex-shrink-0 me-3">
                            <div class="bg-success text-white rounded-circle d-flex align-items-center justify-content-center" style="width: 50px; height: 50px">
                                <i class="bi bi-cash-stack fs-4"></i>
                            </div>
                        </div>
                        <div>
                            <h5 class="card-title fw-bold mb-1">Rp {{ number_format($totalRevenue, 0, ",", ".") }}</h5>
                            <p class="card-text text-muted mb-0">Total Pendapatan</p>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card shadow-sm h-100">
                    <div class="card-body d-flex align-items-center">
                        <div class="flex-shrink-0 me-3">
                            <div class="bg-warning text-dark rounded-circle d-flex align-items-center justify-content-center" style="width: 50px; height: 50px">
                                <i class="bi bi-file-earmark-text fs-4"></i>
                            </div>
                        </div>
                        <div>
                            <h5 class="card-title fw-bold mb-1">{{ $unpaidInvoicesCount }}</h5>
                            <p class="card-text text-muted mb-0">Invoice Belum Lunas</p>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card shadow-sm h-100">
                    <div class="card-body d-flex align-items-center">
                        <div class="flex-shrink-0 me-3">
                            <div class="bg-primary text-white rounded-circle d-flex align-items-center justify-content-center" style="width: 50px; height: 50px">
                                <i class="bi bi-box-seam fs-4"></i>
                            </div>
                        </div>
                        <div>
                            <h5 class="card-title fw-bold mb-1">{{ $productCount }}</h5>
                            <p class="card-text text-muted mb-0">Total Jenis Produk</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- Baris untuk Grafik dan Stok Menipis --}}
        <div class="row g-4 mb-4">
            <div class="col-lg-8">
                <div class="card shadow-sm">
                    <div class="card-header bg-white">
                        <h5 class="mb-0 fw-semibold">Grafik Pendapatan (Tahun Ini)</h5>
                    </div>
                    <div class="card-body">
                        <canvas id="revenueChart"></canvas>
                    </div>
                </div>
            </div>
            <div class="col-lg-4">
                <div class="card shadow-sm h-100">
                    <div class="card-header bg-white">
                        <h5 class="mb-0 fw-semibold">Produk Stok Menipis (&lt;10)</h5>
                    </div>
                    <div class="card-body">
                        <ul class="list-group list-group-flush">
                            @forelse ($lowStockProducts as $product)
                                <li class="list-group-item d-flex justify-content-between align-items-center px-0">
                                    {{ $product->product_name }}
                                    <span class="badge bg-danger rounded-pill">{{ $product->stock_quantity }}</span>
                                </li>
                            @empty
                                <li class="list-group-item px-0">Semua stok produk aman.</li>
                            @endforelse
                        </ul>
                    </div>
                </div>
            </div>
        </div>

        {{-- Tabel Invoice Terbaru --}}
        <div class="card shadow-sm">
            <div class="card-header bg-white">
                <h5 class="mb-0 fw-semibold">Invoice Terbaru</h5>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-hover align-middle">
                        <thead>
                            <tr class="table-light">
                                <th>Nomor Invoice</th>
                                <th>Klien</th>
                                <th>Tanggal</th>
                                <th class="text-center">Status</th>
                                <th class="text-end">Jumlah</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($latestInvoices as $invoice)
                                <tr>
                                    <td class="fw-semibold">
                                        <a href="{{ route('invoices.show', $invoice->invoice_id) }}">{{ $invoice->invoice_number }}</a>
                                    </td>
                                    <td>{{ $invoice->client->client_name ?? "N/A" }}</td>
                                    {{-- PERBAIKAN: Menggunakan order_date dan optional() --}}
                                    <td>{{ optional($invoice->order_date)->format("d M Y") }}</td>
                                    <td class="text-center">
                                        {{-- PERBAIKAN: Logika status yang lebih lengkap --}}
                                        @if($invoice->status == 'paid') <span class="badge bg-success">Lunas</span>
                                        @elseif($invoice->status == 'partially_paid') <span class="badge bg-info text-dark">Cicil</span>
                                        @elseif($invoice->status == 'cancelled') <span class="badge bg-dark">Dibatalkan</span>
                                        @else <span class="badge bg-warning text-dark">Belum Lunas</span>
                                        @endif
                                    </td>
                                    <td class="text-end">Rp {{ number_format($invoice->total_amount, 0, ",", ".") }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="5" class="text-center">Belum ada invoice.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
@endsection

@push("scripts")
    {{-- Library untuk Grafik --}}
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script>
        // Ambil data dari controller
        const salesData = @json($salesData);

        // Proses data untuk Chart.js (tidak berubah, sudah benar)
        const chartLabels = salesData.map((item) => item.month);
        const chartValues = salesData.map((item) => item.total_sales);

        const ctx = document.getElementById('revenueChart');

        new Chart(ctx, {
            type: 'bar',
            data: {
                labels: chartLabels,
                datasets: [
                    {
                        label: 'Total Pendapatan',
                        data: chartValues,
                        backgroundColor: 'rgba(23, 162, 184, 0.2)', // Warna info
                        borderColor: 'rgba(23, 162, 184, 1)',
                        borderWidth: 1,
                    },
                ],
            },
            options: {
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: {
                            callback: function (value, index, values) {
                                return ('Rp ' + new Intl.NumberFormat('id-ID').format(value));
                            },
                        },
                    },
                },
            },
        });
    </script>
@endpush
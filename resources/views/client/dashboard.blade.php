@extends('layouts.client')

@section('content')
<div class="container-fluid py-4">
    <h2 class="fw-bold mb-1">Selamat Datang, {{ $client->client_name }}!</h2>
    <p class="text-muted mb-4">Berikut adalah ringkasan aktivitas akun Anda.</p>

    {{-- KARTU KPI (4 KOLOM) --}}
    <div class="row g-4 mb-4">
        <div class="col-md-6 col-lg-3">
            <div class="card shadow-sm h-100 border-start border-danger border-4">
                <div class="card-body">
                    <div class="text-muted text-uppercase small">Total Tagihan Belum Lunas</div>
                    <h4 class="fw-bold mb-0">Rp {{ number_format($totalPiutang, 0, ',', '.') }}</h4>
                    <a href="{{ route('client.invoices.index') }}" class="stretched-link text-decoration-none small text-muted">Lihat Riwayat Invoice...</a>
                </div>
            </div>
        </div>
        <div class="col-md-6 col-lg-3">
            <div class="card shadow-sm h-100 border-start border-info border-4">
                <div class="card-body">
                    <div class="text-muted text-uppercase small">Pesanan Online (Pending Review)</div>
                    <h4 class="fw-bold mb-0">{{ $pendingClientOrdersCount }} <span class="fw-normal fs-6">Pesanan</span></h4>
                    <a href="{{ route('client.client-orders.index') }}" class="stretched-link text-decoration-none small text-muted">Lihat Pesanan Online Saya...</a>
                </div>
            </div>
        </div>
         <div class="col-md-6 col-lg-3">
            <div class="card shadow-sm h-100 border-start border-primary border-4">
                <div class="card-body">
                    <div class="text-muted text-uppercase small">Pesanan Sales (Aktif)</div>
                    <h4 class="fw-bold mb-0">{{ $activeSalesOrdersCount }} <span class="fw-normal fs-6">Pesanan</span></h4>
                    <a href="{{ route('client.sales-orders.index') }}" class="stretched-link text-decoration-none small text-muted">Lihat Riwayat Pesanan Sales...</a>
                </div>
            </div>
        </div>
        <div class="col-md-6 col-lg-3">
            <div class="card shadow-sm h-100 border-start border-warning border-4">
                <div class="card-body">
                    <div class="text-muted text-uppercase small">Permintaan Perubahan (Pending)</div>
                    <h4 class="fw-bold mb-0">{{ $pendingChangeRequestsCount }} <span class="fw-normal fs-6">Request</span></h4>
                     <a href="{{ route('client.sales-orders.index') }}" class="stretched-link text-decoration-none small text-muted">Lihat di Riwayat Pesanan Sales...</a>
                </div>
            </div>
        </div>
    </div>

    <div class="row g-4">
        {{-- KOLOM 1: DAFTAR "PERLU TINDAKAN" --}}
        <div class="col-12">
            <div class="card shadow-sm h-100">
                <div class="card-header bg-white">
                    <h5 class="mb-0 fw-semibold">Status Pengajuan Anda</h5>
                </div>
                <div class="card-body" style="max-height: 400px; overflow-y: auto;">
                    <div class="list-group list-group-flush">
                        @forelse($pendingActivities as $activity)
                            @if($activity instanceof \App\Models\Order)
                                <a href="{{ route('client.client-orders.show', $activity->order_id) }}" class="list-group-item list-group-item-action d-flex justify-content-between align-items-center px-0">
                                    <div>
                                        <span class="fw-semibold">Pesanan Online Dibuat</span>
                                        <small class="d-block text-muted">{{ $activity->order_number }}</small>
                                    </div>
                                    <span class="badge bg-info text-dark">{{ Str::title(str_replace('_', ' ', $activity->status)) }}</span>
                                </a>
                            @elseif($activity instanceof \App\Models\OrderChangeRequest)
                                <a href="{{ route('client.sales-orders.show', $activity->order->order_id) }}" class="list-group-item list-group-item-action d-flex justify-content-between align-items-center px-0">
                                    <div>
                                        <span class="fw-semibold">Permintaan Perubahan</span>
                                        <small class="d-block text-muted">{{ $activity->order->order_number ?? 'N/A' }} ({{ $activity->request_type == 'cancel' ? 'Batal' : 'Modifikasi' }})</small>
                                    </div>
                                    <span class="badge bg-warning text-dark">{{ Str::title($activity->status) }}</span>
                                </a>
                            @endif
                        @empty
                             <div class="list-group-item text-center text-muted py-4 px-0">
                                 Tidak ada aktivitas yang menunggu tindakan.
                             </div>
                        @endforelse
                    </div>
                </div>
            </div>
        </div>

        {{-- KOLOM 2: DAFTAR TAGIHAN BELUM LUNAS --}}
        <div class="col-12">
            <div class="card shadow-sm h-100">
                <div class="card-header bg-white d-flex justify-content-between align-items-center">
                    <h5 class="mb-0 fw-semibold">Tagihan Belum Lunas</h5>
                     <a href="{{ route('client.invoices.index') }}" class="btn btn-sm btn-outline-primary">Lihat Semua</a>
                </div>
                
                {{-- ✅ KARTU DIBUAT SCROLLABLE --}}
                <div class="card-body p-0" style="max-height: 400px; overflow-y: auto;">
                    <div class="table-responsive">
                        @if($invoicesForCard->isEmpty())
                             <div class="text-center text-muted p-5">
                                 <i class="bi bi-check-circle-fill fs-3 text-success"></i>
                                 <p class="mt-2 mb-0">Luar biasa! Tidak ada tagihan yang belum lunas.</p>
                             </div>
                        @else
                            <table class="table table-hover align-middle mb-0">
                                <thead class="table-light sticky-top">
                                    <tr>
                                        <th>No. Invoice</th>
                                        <th>Jatuh Tempo</th>
                                        <th class="text-end">Sisa Tagihan</th>
                                        <th class="text-center">Status</th> {{-- ✅ KOLOM STATUS --}}
                                        <th class="text-center">Aksi</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @php $hasUnpaid = false; @endphp
                                    @foreach($invoicesForCard as $invoice)
                                        @php
                                            $totalRetur = $invoice->returns->sum('total_amount');
                                            $sisaTagihan = $invoice->total_amount - $invoice->amount_paid - $totalRetur;
                                        @endphp
                                        
                                        @if($sisaTagihan > 0)
                                            @php $hasUnpaid = true; @endphp
                                            <tr>
                                                <td>
                                                    <a href="{{ route('client.invoices.show', $invoice->invoice_id) }}">{{ $invoice->invoice_number }}</a>
                                                </td>
                                                <td class="{{ optional($invoice->due_date)->isPast() ? 'text-danger fw-bold' : '' }}">
                                                    {{ optional($invoice->due_date)->format('d M Y') }}
                                                </td>
                                                <td class="text-end fw-bold text-danger">Rp {{ number_format($sisaTagihan, 0, ',', '.') }}</td>
                                                
                                                {{-- ✅ KONTEN KOLOM STATUS --}}
                                                <td class="text-center">
                                                    @if($invoice->status == 'partially_paid')
                                                        <span class="badge bg-info text-dark">Cicil</span>
                                                    @else
                                                        <span class="badge bg-warning text-dark">Belum Lunas</span>
                                                    @endif
                                                </td>
                                                
                                                <td class="text-center">
                                                     <a href="{{ route('client.invoices.show', $invoice->invoice_id) }}" class="btn btn-sm btn-primary">
                                                        Bayar
                                                    </a>
                                                </td>
                                            </tr>
                                        @endif
                                    @endforeach

                                    @if(!$hasUnpaid) {{-- Jika $invoicesForCard ada tapi sisa tagihannya 0 --}}
                                         <tr>
                                            <td colspan="5" class="text-center text-muted p-5">
                                                <i class="bi bi-check-circle-fill fs-3 text-success"></i>
                                                <p class="mt-2 mb-0">Luar biasa! Tidak ada tagihan yang belum lunas.</p>
                                            </td>
                                        </tr>
                                    @endif
                                </tbody>
                            </table>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
@extends('layouts.app')

@section('content')
<div class="container-fluid py-4">
    {{-- HEADER HALAMAN --}}
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h3 class="fw-bold text-dark mb-0">Review Pesanan Online</h3>
            <p class="text-muted small mb-0">Validasi pesanan masuk dari portal klien</p>
        </div>
    </div>

    {{-- NOTIFIKASI --}}
    @if (session('success'))
        <div class="alert alert-success border-0 shadow-sm d-flex align-items-center mb-4">
            <i class="bi bi-check-circle-fill fs-4 me-2"></i>
            <div>{{ session('success') }}</div>
        </div>
    @endif
    @if (session('error'))
        <div class="alert alert-danger border-0 shadow-sm d-flex align-items-center mb-4">
            <i class="bi bi-exclamation-triangle-fill fs-4 me-2"></i>
            <div>{{ session('error') }}</div>
        </div>
    @endif

    {{-- TAB NAVIGASI (Menunggu vs Riwayat) --}}
    <ul class="nav nav-pills mb-4 bg-white p-2 rounded shadow-sm d-inline-flex" style="width: fit-content;">
        <li class="nav-item">
            <a class="nav-link {{ $view == 'pending' ? 'active shadow-sm' : 'text-muted' }}" 
               href="{{ route('client-order-reviews.index', ['view' => 'pending']) }}">
                <i class="bi bi-hourglass-split me-1"></i> Menunggu Review
                @php $pendingCount = \App\Models\Order::where('order_source', 'client')->where('status', 'pending_review')->count(); @endphp
                @if($pendingCount > 0)
                    <span class="badge bg-danger rounded-pill ms-1">{{ $pendingCount }}</span>
                @endif
            </a>
        </li>
        <li class="nav-item">
            <a class="nav-link {{ $view == 'history' ? 'active shadow-sm' : 'text-muted' }}" 
               href="{{ route('client-order-reviews.index', ['view' => 'history']) }}">
                <i class="bi bi-clock-history me-1"></i> Riwayat Proses
            </a>
        </li>
    </ul>

    {{-- FORM FILTER --}}
    <div class="card card-transaction border-0 shadow-sm mb-4">
        <div class="card-body p-3">
            <form action="{{ route('client-order-reviews.index') }}" method="GET" class="row g-2 align-items-center">
                <input type="hidden" name="view" value="{{ $view }}"> 

                <div class="col-md-3">
                    <div class="input-group">
                        <span class="input-group-text bg-white border-end-0 text-muted"><i class="bi bi-search"></i></span>
                        <input type="text" name="search" id="search" class="form-control border-start-0 ps-0" placeholder="Cari No. Order / Klien..." value="{{ request('search') }}">
                    </div>
                </div>

                <div class="col-md-3">
                    <select name="date_filter" id="date_filter" class="form-select">
                        <option value="">-- Semua Periode --</option>
                        @foreach($uniqueDates as $ym => $dateLabel)
                            <option value="{{ $ym }}" @selected(request('date_filter') == $ym)>{{ $dateLabel }}</option>
                        @endforeach
                    </select>
                </div>

                @if($view == 'history')
                <div class="col-md-2">
                    <select name="status_filter" id="status_filter" class="form-select">
                        <option value="">-- Status --</option>
                        <option value="invoiced" @selected(request('status_filter') == 'invoiced')>Disetujui (Invoiced)</option>
                        <option value="rejected" @selected(request('status_filter') == 'rejected')>Ditolak</option>
                    </select>
                </div>
                @endif

                <div class="col-md-2">
                    <select name="sort" id="sort" class="form-select">
                        <option value="terbaru" @selected(request('sort', 'terbaru') == 'terbaru')>Terbaru</option>
                        <option value="terlama" @selected(request('sort') == 'terlama')>Terlama</option>
                    </select>
                </div>

                <div class="col-md-2 d-flex gap-2">
                    <button type="submit" class="btn btn-dark w-100">Filter</button>
                    <a href="{{ route('client-order-reviews.index', ['view' => $view]) }}" class="btn btn-light border w-100" title="Reset"><i class="bi bi-arrow-clockwise"></i></a>
                </div>
            </form>
        </div>
    </div>

    {{-- CARD LIST VIEW --}}
    <div class="d-flex flex-column gap-3">
        @forelse ($clientOrders as $order)
            <div class="card card-transaction shadow-sm border-0 border-start border-5 {{ $order->status == 'pending_review' ? 'border-info' : ($order->status == 'rejected' ? 'border-danger' : 'border-success') }}">
                
                {{-- HEADER KARTU --}}
                <div class="card-header bg-white p-3 cursor-pointer" data-bs-toggle="collapse" data-bs-target="#collapse-{{ $order->order_id }}" aria-expanded="false">
                    <div class="row align-items-center">
                        {{-- Kolom 1: Nomor & Klien --}}
                        <div class="col-md-4 mb-2 mb-md-0">
                            <div class="d-flex align-items-center">
                                <div class="me-3 bg-light rounded-circle d-flex align-items-center justify-content-center" style="width:45px; height:45px;">
                                    <i class="bi bi-globe text-secondary fs-5"></i>
                                </div>
                                <div>
                                    <h6 class="fw-bold text-dark mb-0">{{ $order->order_number }}</h6>
                                    <span class="fw-semibold text-primary small">{{ $order->client->client_name ?? 'Klien Dihapus' }}</span>
                                </div>
                            </div>
                        </div>

                        {{-- Kolom 2: Tanggal & Total --}}
                        <div class="col-md-4 col-6 mb-2 mb-md-0">
                            <div class="row">
                                <div class="col-6">
                                    <small class="text-muted d-block" style="font-size: 0.7rem;">TANGGAL</small>
                                    <span class="text-dark fw-medium">{{ $order->order_date->format('d M Y') }}</span>
                                </div>
                                <div class="col-6">
                                    <small class="text-muted d-block" style="font-size: 0.7rem;">TOTAL PESANAN</small>
                                    <span class="fw-bold text-dark">Rp {{ number_format($order->total_amount, 0, ',', '.') }}</span>
                                </div>
                            </div>
                        </div>

                        {{-- Kolom 3: Status & Aksi --}}
                        <div class="col-md-4 text-md-end d-flex justify-content-between justify-content-md-end align-items-center gap-3">
                            <div class="text-end">
                                @php
                                    $statusClass = [
                                        'pending_review' => 'bg-info bg-opacity-10 text-info border border-info border-opacity-25',
                                        'approved' => 'bg-success bg-opacity-10 text-success border border-success border-opacity-25',
                                        'invoiced' => 'bg-success bg-opacity-10 text-success border border-success border-opacity-25',
                                        'rejected' => 'bg-danger bg-opacity-10 text-danger border border-danger border-opacity-25',
                                    ];
                                    $statusLabel = [
                                        'pending_review' => 'Menunggu Review',
                                        'approved' => 'Disetujui',
                                        'invoiced' => 'Sudah Invoiced',
                                        'rejected' => 'Ditolak',
                                    ];
                                @endphp
                                <span class="badge {{ $statusClass[$order->status] ?? 'bg-secondary' }} rounded-pill px-3">
                                    {{ $statusLabel[$order->status] ?? Str::title(str_replace('_', ' ', $order->status)) }}
                                </span>
                            </div>
                            <i class="bi bi-chevron-down text-muted transition-icon"></i>
                        </div>
                    </div>
                </div>

                {{-- BODY (COLLAPSE) --}}
                <div class="collapse" id="collapse-{{ $order->order_id }}">
                    <div class="card-body bg-light bg-opacity-25 border-top p-3">
                        <div class="d-flex justify-content-between align-items-center flex-wrap gap-3">
                            
                            {{-- Info Tambahan --}}
                            <div class="d-flex gap-4">
                                <div>
                                    <small class="text-muted fw-bold d-block">JUMLAH ITEM</small>
                                    <span>{{ $order->items->count() }} Produk</span>
                                </div>
                                @if($order->notes)
                                <div>
                                    <small class="text-muted fw-bold d-block">CATATAN KLIEN</small>
                                    <span class="fst-italic text-muted">{{ Str::limit($order->notes, 50) }}</span>
                                </div>
                                @endif
                            </div>

                            {{-- Tombol Aksi Utama --}}
                            <div>
                                <a href="{{ route('client-order-reviews.show', $order->order_id) }}" 
                                   class="btn btn-sm {{ $view == 'pending' ? 'btn-primary px-4' : 'btn-outline-primary' }}">
                                    <i class="bi bi-eye me-1"></i> {{ $view == 'pending' ? 'Review Pesanan' : 'Lihat Detail' }}
                                </a>
                            </div>
                        </div>
                    </div>
                </div>

            </div>
        @empty
            {{-- EMPTY STATE --}}
            <div class="text-center py-5 my-3 bg-white rounded shadow-sm border border-dashed">
                <div class="mb-3">
                    <i class="bi bi-inbox text-muted" style="font-size: 3rem; opacity: 0.5;"></i>
                </div>
                <h5 class="fw-bold text-dark">Tidak ada pesanan</h5>
                <p class="text-muted mb-0">
                    @if($view == 'pending')
                        Belum ada pesanan baru dari klien yang perlu direview.
                    @else
                        Tidak ada riwayat pesanan yang cocok dengan filter.
                    @endif
                </p>
            </div>
        @endforelse
    </div>

    {{-- PAGINATION --}}
    <div class="mt-4 d-flex justify-content-center">
        {{ $clientOrders->appends(request()->query())->links() }}
    </div>
</div>
@endsection

@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function() {
        // Efek rotasi panah
        const collapses = document.querySelectorAll('.collapse');
        collapses.forEach(el => {
            el.addEventListener('show.bs.collapse', function () {
                const parent = this.closest('.card');
                const icon = parent.querySelector('.bi-chevron-down');
                if(icon) icon.style.transform = 'rotate(180deg)';
            });
            el.addEventListener('hide.bs.collapse', function () {
                const parent = this.closest('.card');
                const icon = parent.querySelector('.bi-chevron-down');
                if(icon) icon.style.transform = 'rotate(0deg)';
            });
        });
    });
</script>
@endpush
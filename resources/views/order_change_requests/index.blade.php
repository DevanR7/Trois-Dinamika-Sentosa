@extends('layouts.app')

@section('content')
<div class="container-fluid py-4">
    {{-- HEADER HALAMAN --}}
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h3 class="fw-bold text-dark mb-0">Permintaan Perubahan Pesanan</h3>
            <p class="text-muted small mb-0">Review permintaan revisi atau pembatalan dari klien</p>
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

    {{-- FORM FILTER --}}
    <div class="card card-transaction border-0 shadow-sm mb-4">
        <div class="card-body p-3">
            <form action="{{ route('order-change-requests.index') }}" method="GET" class="row g-2 align-items-center">
                <div class="col-md-3">
                    <div class="input-group">
                        <span class="input-group-text bg-white border-end-0 text-muted"><i class="bi bi-search"></i></span>
                        <input type="text" name="search" id="search" class="form-control border-start-0 ps-0" placeholder="Cari No. Request / Order..." value="{{ request('search') }}">
                    </div>
                </div>

                <div class="col-md-2">
                    <select name="date_filter" id="date_filter" class="form-select">
                        <option value="">-- Periode --</option>
                        @foreach($uniqueDates as $ym => $dateLabel)
                            <option value="{{ $ym }}" @selected(request('date_filter') == $ym)>{{ $dateLabel }}</option>
                        @endforeach
                    </select>
                </div>

                <div class="col-md-2">
                    <select name="type_filter" id="type_filter" class="form-select">
                        <option value="">-- Tipe --</option>
                        <option value="cancel" @selected(request('type_filter') == 'cancel')>Pembatalan</option>
                        <option value="modify" @selected(request('type_filter') == 'modify')>Modifikasi</option>
                    </select>
                </div>

                <div class="col-md-2">
                    <select name="sort" id="sort" class="form-select">
                        <option value="terbaru" @selected(request('sort', 'terbaru') == 'terbaru')>Terbaru</option>
                        <option value="terlama" @selected(request('sort') == 'terlama')>Terlama</option>
                    </select>
                </div>

                <div class="col-md-3 d-flex gap-2">
                    <button type="submit" class="btn btn-dark w-100">Filter</button>
                    <a href="{{ route('order-change-requests.index') }}" class="btn btn-light border w-100" title="Reset"><i class="bi bi-arrow-clockwise"></i></a>
                </div>
            </form>
        </div>
    </div>

    {{-- CARD LIST VIEW --}}
    <div class="d-flex flex-column gap-3">
        @forelse ($changeRequests as $request)
            <div class="card card-transaction shadow-sm border-0 border-start border-5 {{ $request->status == 'pending' ? 'border-warning' : ($request->status == 'rejected' ? 'border-danger' : 'border-success') }}">
                
                {{-- HEADER KARTU --}}
                <div class="card-header bg-white p-3 cursor-pointer" data-bs-toggle="collapse" data-bs-target="#collapse-{{ $request->request_id }}" aria-expanded="false">
                    <div class="row align-items-center">
                        {{-- Kolom 1: Nomor Request & Order --}}
                        <div class="col-md-4 mb-2 mb-md-0">
                            <div class="d-flex align-items-center">
                                <div class="me-3 bg-light rounded-circle d-flex align-items-center justify-content-center" style="width:45px; height:45px;">
                                    @if($request->request_type == 'cancel')
                                        <i class="bi bi-x-circle text-danger fs-5"></i>
                                    @else
                                        <i class="bi bi-pencil-square text-info fs-5"></i>
                                    @endif
                                </div>
                                <div>
                                    <h6 class="fw-bold text-dark mb-0">REQ-{{ str_pad($request->request_id, 5, '0', STR_PAD_LEFT) }}</h6>
                                    <span class="text-muted small">
                                        Order: 
                                        @if($request->order)
                                            <a href="{{ route('sales-orders.show', $request->order->order_id) }}" class="text-decoration-none fw-bold">{{ $request->order->order_number }}</a>
                                        @else
                                            <span class="text-danger">Dihapus</span>
                                        @endif
                                    </span>
                                </div>
                            </div>
                        </div>

                        {{-- Kolom 2: Klien & Tipe --}}
                        <div class="col-md-3 col-6 mb-2 mb-md-0">
                            <small class="text-muted d-block" style="font-size: 0.7rem;">KLIEN</small>
                            <span class="text-dark fw-medium">{{ $request->client->client_name ?? 'Klien Dihapus' }}</span>
                        </div>

                        {{-- Kolom 3: Tanggal --}}
                        <div class="col-md-2 col-6 mb-2 mb-md-0">
                            <small class="text-muted d-block" style="font-size: 0.7rem;">DIAJUKAN</small>
                            <span class="text-dark">{{ $request->created_at->format('d M Y') }}</span>
                        </div>

                        {{-- Kolom 4: Status & Icon --}}
                        <div class="col-md-3 text-md-end d-flex justify-content-between justify-content-md-end align-items-center gap-3">
                            <div class="text-end">
                                @if($request->status == 'pending')
                                    <span class="badge bg-warning bg-opacity-10 text-warning border border-warning border-opacity-25 rounded-pill px-3">Menunggu Review</span>
                                @elseif($request->status == 'approved')
                                    <span class="badge bg-success bg-opacity-10 text-success border border-success border-opacity-25 rounded-pill px-3">Disetujui</span>
                                @else
                                    <span class="badge bg-danger bg-opacity-10 text-danger border border-danger border-opacity-25 rounded-pill px-3">Ditolak</span>
                                @endif
                            </div>
                            <i class="bi bi-chevron-down text-muted transition-icon"></i>
                        </div>
                    </div>
                </div>

                {{-- BODY (COLLAPSE) --}}
                <div class="collapse" id="collapse-{{ $request->request_id }}">
                    <div class="card-body bg-light bg-opacity-25 border-top p-3">
                        <div class="d-flex justify-content-between align-items-center flex-wrap gap-3">
                            
                            {{-- Info Detail --}}
                            <div class="d-flex gap-4">
                                <div>
                                    <small class="text-muted fw-bold d-block">TIPE PERMINTAAN</small>
                                    <span class="{{ $request->request_type == 'cancel' ? 'text-danger' : 'text-info' }} fw-bold">
                                        {{ $request->request_type == 'cancel' ? 'Pembatalan Pesanan' : 'Modifikasi Item' }}
                                    </span>
                                </div>
                                @if($request->client_notes)
                                <div>
                                    <small class="text-muted fw-bold d-block">ALASAN KLIEN</small>
                                    <span class="fst-italic text-muted">{{ Str::limit($request->client_notes, 50) }}</span>
                                </div>
                                @endif
                            </div>

                            {{-- Tombol Aksi --}}
                            <div>
                                <a href="{{ route('order-change-requests.show', $request->request_id) }}" 
                                   class="btn btn-sm {{ $request->status == 'pending' ? 'btn-primary px-4' : 'btn-outline-primary' }}">
                                    <i class="bi bi-eye me-1"></i> {{ $request->status == 'pending' ? 'Proses Request' : 'Lihat Detail' }}
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
                <h5 class="fw-bold text-dark">Tidak ada permintaan</h5>
                <p class="text-muted mb-0">Belum ada permintaan perubahan pesanan yang masuk.</p>
            </div>
        @endforelse
    </div>

    {{-- PAGINATION --}}
    <div class="mt-4 d-flex justify-content-center">
        {{ $changeRequests->appends(request()->query())->links() }}
    </div>
</div>
@endsection

@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function() {
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
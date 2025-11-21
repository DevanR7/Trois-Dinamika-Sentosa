@extends('layouts.app')

@section('content')
<div class="container-fluid py-4">
    {{-- HEADER --}}
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h3 class="fw-bold text-dark mb-0">Retur Penjualan</h3>
            <p class="text-muted small mb-0">Daftar pengembalian barang dari pelanggan</p>
        </div>
        <a href="{{ route('sales-returns.create') }}" class="btn btn-primary shadow-sm">
            <i class="bi bi-plus-lg me-2"></i> Buat Retur Baru
        </a>
    </div>

    {{-- FILTER --}}
    <div class="card card-transaction border-0 shadow-sm mb-4">
        <div class="card-body p-3">
            <form action="{{ route('sales-returns.index') }}" method="GET" class="row g-2 align-items-center">
                <div class="col-md-5">
                    <div class="input-group">
                        <span class="input-group-text bg-white border-end-0 text-muted"><i class="bi bi-search"></i></span>
                        <input type="text" name="search" class="form-control border-start-0 ps-0" placeholder="Cari No. Retur / Klien / No. Invoice..." value="{{ request('search') }}">
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="input-group">
                        <span class="input-group-text bg-light text-muted small">Tanggal</span>
                        <input type="date" name="return_date" class="form-control" value="{{ request('return_date') }}">
                    </div>
                </div>
                <div class="col-md-3 d-flex gap-2">
                    <button type="submit" class="btn btn-dark w-100">Filter</button>
                    <a href="{{ route('sales-returns.index') }}" class="btn btn-light border w-100" title="Reset"><i class="bi bi-arrow-clockwise"></i></a>
                </div>
            </form>
        </div>
    </div>
    
    @if (session('success'))
        <div class="alert alert-success border-0 shadow-sm d-flex align-items-center mb-4">
            <i class="bi bi-check-circle-fill fs-4 me-2"></i>
            <div>{{ session('success') }}</div>
        </div>
    @endif
    
    {{-- CARD LIST VIEW --}}
    <div class="d-flex flex-column gap-3">
        @forelse ($salesReturns as $return)
            <div class="card card-transaction shadow-sm border-0 border-start border-5 border-warning">
                
                {{-- HEADER KARTU --}}
                <div class="card-header bg-white p-3 cursor-pointer" data-bs-toggle="collapse" data-bs-target="#collapse-{{ $return->return_id }}" aria-expanded="false">
                    <div class="row align-items-center">
                        {{-- Kolom 1: No Retur & Klien --}}
                        <div class="col-md-4 mb-2 mb-md-0">
                            <div class="d-flex align-items-center">
                                <div class="me-3 bg-light rounded-circle d-flex align-items-center justify-content-center" style="width:45px; height:45px;">
                                    <i class="bi bi-arrow-counterclockwise text-danger fs-5"></i>
                                </div>
                                <div>
                                    <h6 class="fw-bold text-dark mb-0">{{ $return->return_number }}</h6>
                                    <span class="fw-semibold text-primary small">{{ $return->client->client_name ?? 'Klien Dihapus' }}</span>
                                </div>
                            </div>
                        </div>

                        {{-- Kolom 2: Invoice Asal & Tanggal --}}
                        <div class="col-md-4 col-6 mb-2 mb-md-0">
                            <small class="text-muted d-block" style="font-size: 0.7rem;">INVOICE ASAL</small>
                            <span class="text-dark fw-medium small">{{ $return->salesInvoice->invoice_number ?? 'N/A' }}</span>
                            <div class="text-muted small mt-1"><i class="bi bi-calendar3 me-1"></i> {{ optional($return->return_date)->format('d M Y') }}</div>
                        </div>

                        {{-- Kolom 3: Total & Icon --}}
                        <div class="col-md-4 text-md-end d-flex justify-content-between justify-content-md-end align-items-center gap-3">
                            <div class="text-end">
                                <small class="text-muted d-block" style="font-size: 0.7rem;">NILAI RETUR</small>
                                <span class="fw-bold text-danger fs-6">Rp {{ number_format($return->total_amount, 0, ',', '.') }}</span>
                            </div>
                            <i class="bi bi-chevron-down text-muted transition-icon"></i>
                        </div>
                    </div>
                </div>

                {{-- DETAIL KARTU (COLLAPSE) --}}
                <div class="collapse" id="collapse-{{ $return->return_id }}">
                    <div class="card-body bg-light bg-opacity-25 border-top p-3">
                        <div class="d-flex justify-content-end align-items-center gap-2">
                            <span class="text-muted small me-auto">Dibuat oleh: {{ $return->user->full_name ?? 'System' }}</span>
                            
                            <a href="{{ route('sales-returns.show', $return->return_id) }}" class="btn btn-sm btn-outline-primary px-3 shadow-sm">
                                <i class="bi bi-eye me-1"></i> Detail Lengkap
                            </a>
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
                <h5 class="fw-bold text-dark">Belum ada Data Retur</h5>
                <p class="text-muted mb-0">Belum ada pengembalian barang dari penjualan yang tercatat.</p>
            </div>
        @endforelse
    </div>

    <div class="mt-4 d-flex justify-content-center">
        {{ $salesReturns->appends(request()->query())->links() }}
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
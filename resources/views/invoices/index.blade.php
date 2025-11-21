@extends('layouts.app')

@section('content')
<div class="container-fluid py-4">
    {{-- HEADER --}}
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h3 class="fw-bold text-dark mb-0">Daftar Invoice Penjualan</h3>
            <p class="text-muted small mb-0">Tagihan ke pelanggan</p>
        </div>
        <a href="{{ route('invoices.create') }}" class="btn btn-primary shadow-sm">
            <i class="bi bi-plus-lg me-2"></i> Buat Invoice Baru
        </a>
    </div>

    {{-- ALERT --}}
    @if (session('success'))
        <div class="alert alert-success border-0 shadow-sm d-flex align-items-center mb-4">
            <i class="bi bi-check-circle-fill fs-4 me-2"></i>
            <div>{{ session('success') }}</div>
            <button type="button" class="btn-close ms-auto" data-bs-dismiss="alert"></button>
        </div>
    @endif

    {{-- FILTER CARD --}}
    <div class="card card-transaction border-0 shadow-sm mb-4">
        <div class="card-body p-3">
            <form action="{{ route('invoices.index') }}" method="GET" class="row g-2 align-items-center">
                <div class="col-md-3">
                    <div class="input-group">
                        <span class="input-group-text bg-white border-end-0 text-muted"><i class="bi bi-search"></i></span>
                        <input type="text" name="search" class="form-control border-start-0 ps-0" placeholder="Cari No. Invoice / Klien..." value="{{ request('search') }}">
                    </div>
                </div>
                <div class="col-md-2">
                    <input type="date" name="start_date" class="form-control" value="{{ request('start_date') }}" title="Tanggal Mulai">
                </div>
                <div class="col-md-2">
                    <input type="date" name="end_date" class="form-control" value="{{ request('end_date') }}" title="Tanggal Akhir">
                </div>
                <div class="col-md-2">
                    <select name="status" class="form-select">
                        <option value="">-- Status --</option>
                        <option value="draft" @selected(request("status") == "draft")>Draft</option>
                        <option value="unpaid" @selected(request("status") == "unpaid")>Belum Lunas</option>
                        <option value="partially_paid" @selected(request("status") == "partially_paid")>Cicil</option>
                        <option value="paid" @selected(request("status") == "paid")>Lunas</option>
                        <option value="cancelled" @selected(request("status") == "cancelled")>Dibatalkan</option>
                    </select>
                </div>
                <div class="col-md-3 d-flex gap-2">
                    <button type="submit" class="btn btn-dark w-100">Filter</button>
                    <a href="{{ route('invoices.index') }}" class="btn btn-light border w-100" title="Reset"><i class="bi bi-arrow-clockwise"></i></a>
                </div>
            </form>
        </div>
    </div>

    {{-- CARD LIST VIEW --}}
    <div class="d-flex flex-column gap-3">
        @forelse ($invoices as $invoice)
            @php
                $sisaPiutang = $invoice->remaining_balance;
                $borderClass = 'border-warning'; // Default unpaid
                if($invoice->status == 'paid') $borderClass = 'border-success';
                elseif($invoice->status == 'cancelled') $borderClass = 'border-secondary';
                elseif($invoice->status == 'draft') $borderClass = 'border-secondary';
                elseif(optional($invoice->due_date)->isPast() && $invoice->status != 'paid') $borderClass = 'border-danger';
            @endphp

            <div class="card card-transaction shadow-sm border-0 border-start border-5 {{ $borderClass }}">
                
                {{-- HEADER KARTU --}}
                <div class="card-header bg-white p-3 cursor-pointer" data-bs-toggle="collapse" data-bs-target="#collapse-{{ $invoice->invoice_id }}" aria-expanded="false">
                    <div class="row align-items-center">
                        {{-- Kolom 1: No Invoice & Klien --}}
                        <div class="col-md-4 mb-2 mb-md-0">
                            <div class="d-flex align-items-center">
                                <div class="me-3 bg-light rounded-circle d-flex align-items-center justify-content-center" style="width:45px; height:45px;">
                                    <i class="bi bi-receipt text-primary fs-5"></i>
                                </div>
                                <div>
                                    <h6 class="fw-bold text-dark mb-0">{{ $invoice->invoice_number }}</h6>
                                    <span class="fw-semibold text-secondary small">{{ $invoice->client->client_name ?? 'N/A' }}</span>
                                </div>
                            </div>
                        </div>

                        {{-- Kolom 2: Tanggal & Total --}}
                        <div class="col-md-4 col-6 mb-2 mb-md-0">
                            <div class="row">
                                <div class="col-6">
                                    <small class="text-muted d-block" style="font-size: 0.7rem;">TANGGAL INVOICE</small>
                                    <span class="text-dark fw-medium">{{ optional($invoice->order_date)->format('d M Y') }}</span>
                                </div>
                                <div class="col-6">
                                    <small class="text-muted d-block" style="font-size: 0.7rem;">TOTAL TAGIHAN</small>
                                    <span class="fw-bold text-dark">Rp {{ number_format($invoice->total_amount, 0, ',', '.') }}</span>
                                </div>
                            </div>
                        </div>

                        {{-- Kolom 3: Status & Sisa --}}
                        <div class="col-md-4 text-md-end d-flex justify-content-between justify-content-md-end align-items-center gap-3">
                            <div class="text-end">
                                @if($invoice->status == 'paid') <span class="badge bg-success bg-opacity-10 text-success border border-success border-opacity-25 rounded-pill px-3">Lunas</span>
                                @elseif($invoice->status == 'partially_paid') <span class="badge bg-info bg-opacity-10 text-info border border-info border-opacity-25 rounded-pill px-3">Cicil</span>
                                @elseif($invoice->status == 'cancelled') <span class="badge bg-secondary bg-opacity-10 text-secondary border border-secondary border-opacity-25 rounded-pill px-3">Batal</span>
                                @elseif($invoice->status == 'draft') <span class="badge bg-secondary bg-opacity-10 text-secondary border border-secondary border-opacity-25 rounded-pill px-3">Draft</span>
                                @else 
                                    <span class="badge bg-warning bg-opacity-10 text-warning border border-warning border-opacity-25 rounded-pill px-3">
                                        {{ optional($invoice->due_date)->isPast() ? 'Jatuh Tempo' : 'Belum Lunas' }}
                                    </span>
                                @endif
                                
                                @if($sisaPiutang > 0 && $invoice->status != 'cancelled')
                                    <div class="small text-danger fw-bold mt-1">Sisa: Rp {{ number_format($sisaPiutang, 0, ',', '.') }}</div>
                                @endif
                            </div>
                            <i class="bi bi-chevron-down text-muted transition-icon"></i>
                        </div>
                    </div>
                </div>

                {{-- DETAIL KARTU (COLLAPSE) --}}
                <div class="collapse" id="collapse-{{ $invoice->invoice_id }}">
                    <div class="card-body bg-light bg-opacity-25 border-top p-3">
                        <div class="d-flex justify-content-between align-items-center flex-wrap gap-3">
                            <div class="d-flex gap-4 text-muted small">
                                <div><strong>Jatuh Tempo:</strong> {{ optional($invoice->due_date)->format('d M Y') }}</div>
                                <div><strong>Sales:</strong> {{ $invoice->sales->full_name ?? '-' }}</div>
                            </div>

                            <div class="btn-group">
                                <a href="{{ route('invoices.show', $invoice->invoice_id) }}" class="btn btn-sm btn-outline-primary"><i class="bi bi-eye"></i> Detail</a>
                                
                                @if(!in_array($invoice->status, ['paid', 'cancelled']))
                                    <a href="{{ route('invoices.edit', $invoice->invoice_id) }}" class="btn btn-sm btn-outline-warning text-dark"><i class="bi bi-pencil"></i> Edit</a>
                                    
                                    @if($invoice->status != 'draft')
                                    <form class="cancel-form d-inline" action="{{ route('invoices.cancel', $invoice->invoice_id) }}" method="POST">
                                        @csrf
                                        <button type="submit" class="btn btn-sm btn-outline-danger"><i class="bi bi-x-circle"></i> Batal</button>
                                    </form>
                                    @endif
                                @endif

                                @if($invoice->status == 'draft')
                                    <form class="confirm-form d-inline" action="{{ route('invoices.confirm', $invoice->invoice_id) }}" method="POST">
                                        @csrf
                                        <button type="submit" class="btn btn-sm btn-success"><i class="bi bi-check-circle"></i> Konfirmasi</button>
                                    </form>
                                @endif
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        @empty
            {{-- EMPTY STATE --}}
            <div class="text-center py-5 my-3 bg-white rounded shadow-sm border border-dashed">
                <div class="mb-3">
                    <i class="bi bi-receipt-cutoff text-muted" style="font-size: 3rem; opacity: 0.5;"></i>
                </div>
                <h5 class="fw-bold text-dark">Belum ada Invoice</h5>
                <p class="text-muted mb-4">Data tagihan belum tersedia.</p>
                <a href="{{ route('invoices.create') }}" class="btn btn-primary btn-sm">
                    <i class="bi bi-plus-lg me-1"></i> Buat Invoice Baru
                </a>
            </div>
        @endforelse
    </div>

    <div class="mt-4 d-flex justify-content-center">
        {{ $invoices->appends(request()->query())->links() }}
    </div>
</div>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Script Konfirmasi
    function confirmAction(selector, title, text, btnText, btnColor) {
        document.querySelectorAll(selector).forEach(form => {
            form.addEventListener('submit', function(event) {
                event.preventDefault();
                Swal.fire({
                    title: title, text: text, icon: 'warning',
                    showCancelButton: true, confirmButtonColor: btnColor, cancelButtonColor: '#6c757d',
                    confirmButtonText: btnText, cancelButtonText: 'Batal'
                }).then((result) => { if (result.isConfirmed) event.target.submit(); });
            });
        });
    }
    confirmAction('.cancel-form', 'Batalkan Invoice?', 'Status akan berubah menjadi Cancelled.', 'Ya, Batalkan!', '#d33');
    confirmAction('.confirm-form', 'Konfirmasi Invoice?', 'Stok akan dikurangi dan invoice menjadi Unpaid.', 'Ya, Konfirmasi!', '#198754');

    // Rotasi Icon
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
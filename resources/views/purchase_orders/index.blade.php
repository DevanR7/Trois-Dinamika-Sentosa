@extends('layouts.app')

@section('content')
<div class="container-fluid py-4">
    {{-- HEADER HALAMAN --}}
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h3 class="fw-bold text-dark mb-0">Pesanan Pembelian</h3>
            <p class="text-muted small mb-0">Kelola daftar pembelian ke supplier</p>
        </div>
        <a href="{{ route('purchase-orders.create') }}" class="btn btn-primary btn-lg shadow-sm fs-6">
            <i class="bi bi-plus-lg me-2"></i>Buat Pesanan Baru
        </a>
    </div>

    {{-- FORM PENCARIAN & FILTER --}}
    <div class="card card-transaction border-0 shadow-sm mb-4">
        <div class="card-body p-3">
            @include('purchase_orders.partials._filter')
        </div>
    </div>

    {{-- FLASH MESSAGES --}}
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

    {{-- DAFTAR PESANAN --}}
    <div class="d-flex flex-column gap-3">
        @forelse ($purchaseOrders as $po)
            @php
                $sisaUtang = $po->total_amount - ($po->total_returned ?? 0) - ($po->amount_paid ?? 0);
                // Border kiri: Hijau jika lunas, Kuning jika ada utang
                $borderClass = $sisaUtang <= 0 ? 'border-start border-5 border-success' : 'border-start border-5 border-warning';
            @endphp

            <div class="card card-transaction shadow-sm border-0 {{ $borderClass }}">
                
                {{-- HEADER (SELALU TERLIHAT) --}}
                <div class="card-header bg-white p-3 cursor-pointer" data-bs-toggle="collapse" data-bs-target="#collapse-{{ $po->po_id }}" aria-expanded="false">
                    <div class="row align-items-center">
                        
                        {{-- 1. PO Number & Supplier (Lebar: 4/12) --}}
                        <div class="col-md-4 mb-2 mb-md-0">
                            <div class="d-flex align-items-center">
                                <div class="me-3 bg-light rounded-circle d-flex align-items-center justify-content-center" style="width:45px; height:45px;">
                                    <i class="bi bi-cart text-secondary fs-5"></i>
                                </div>
                                <div>
                                    <h6 class="fw-bold text-primary mb-0">{{ $po->po_number }}</h6>
                                    <span class="fw-semibold text-dark small">{{ $po->supplier->supplier_name ?? 'Supplier Dihapus' }}</span>
                                </div>
                            </div>
                        </div>

                        {{-- 2. Tanggal & Tagihan (Lebar: 5/12) --}}
                        <div class="col-md-5 col-12 mb-2 mb-md-0">
                            <div class="row">
                                <div class="col-6">
                                    <small class="text-muted d-block" style="font-size: 0.7rem;">TANGGAL</small>
                                    <span class="text-dark fw-medium">{{ optional($po->order_date)->format('d M Y') }}</span>
                                </div>
                                <div class="col-6">
                                    <small class="text-muted d-block" style="font-size: 0.7rem;">TOTAL TAGIHAN</small>
                                    <span class="fw-bold text-dark">Rp {{ number_format($po->total_amount, 0, ',', '.') }}</span>
                                </div>
                            </div>
                        </div>

                        {{-- 3. DUA BADGE STATUS (Lebar: 3/12) --}}
                        <div class="col-md-3 text-md-end d-flex justify-content-between justify-content-md-end align-items-center gap-3">
                            <div class="d-flex flex-column align-items-end gap-1">
                                
                                {{-- BADGE 1: STATUS BARANG --}}
                                @if($po->status == 'completed') 
                                    <span class="badge bg-success bg-opacity-10 text-success border border-success border-opacity-25 rounded-pill" style="font-size: 0.7rem;">Barang Diterima</span>
                                @elseif($po->status == 'draft') 
                                    <span class="badge bg-secondary bg-opacity-10 text-secondary border border-secondary border-opacity-25 rounded-pill" style="font-size: 0.7rem;">Draft</span>
                                @elseif($po->status == 'cancelled') 
                                    <span class="badge bg-dark bg-opacity-10 text-dark border border-dark border-opacity-25 rounded-pill" style="font-size: 0.7rem;">Dibatalkan</span>
                                @else 
                                    <span class="badge bg-warning bg-opacity-10 text-warning border border-warning border-opacity-25 rounded-pill" style="font-size: 0.7rem;">{{ Str::title($po->status) }}</span>
                                @endif

                                {{-- BADGE 2: STATUS PEMBAYARAN --}}
                                @if($po->payment_status == 'paid') 
                                    <span class="badge bg-primary bg-opacity-10 text-primary border border-primary border-opacity-25 rounded-pill" style="font-size: 0.7rem;">Lunas</span>
                                @elseif($po->payment_status == 'partially_paid') 
                                    <span class="badge bg-info bg-opacity-10 text-info border border-info border-opacity-25 rounded-pill" style="font-size: 0.7rem;">Cicilan</span>
                                @else 
                                    <span class="badge bg-danger bg-opacity-10 text-danger border border-danger border-opacity-25 rounded-pill" style="font-size: 0.7rem;">Belum Lunas</span>
                                @endif

                            </div>
                            <i class="bi bi-chevron-down text-muted transition-icon"></i>
                        </div>

                    </div>
                </div>

                {{-- BODY (COLLAPSIBLE DETAIL) --}}
                <div class="collapse" id="collapse-{{ $po->po_id }}">
                    <div class="card-body bg-light bg-opacity-25 border-top p-3">
                        <div class="row">
                            {{-- Detail Tambahan --}}
                            <div class="col-md-8">
                                <div class="row g-3">
                                    <div class="col-sm-6">
                                        <label class="small text-muted fw-bold">NO. FAKTUR SUPPLIER</label>
                                        <div class="text-dark small">{{ $po->supplier_invoice_number ?? '-' }}</div>
                                    </div>
                                    <div class="col-sm-6">
                                        <label class="small text-muted fw-bold">JATUH TEMPO</label>
                                        <div class="text-dark small">{{ optional($po->due_date)->format('d M Y') ?? '-' }}</div>
                                    </div>
                                </div>
                                <div class="mt-3 pt-2 border-top border-dashed d-flex gap-4">
                                    <div>
                                        <small class="text-muted">Sudah Dibayar:</small>
                                        <span class="text-success fw-bold">Rp {{ number_format($po->amount_paid ?? 0, 0, ',', '.') }}</span>
                                    </div>
                                    <div>
                                        <small class="text-muted">Sisa Utang:</small>
                                        <span class="{{ $sisaUtang > 0 ? 'text-danger' : 'text-success' }} fw-bold">Rp {{ number_format($sisaUtang, 0, ',', '.') }}</span>
                                    </div>
                                </div>
                            </div>

                            {{-- Tombol Aksi --}}
                            <div class="col-md-4 d-flex align-items-center justify-content-md-end mt-3 mt-md-0">
                                <div class="btn-group w-100 w-md-auto">
                                    <a href="{{ route('purchase-orders.show', $po->po_id) }}" class="btn btn-sm btn-outline-primary">
                                        <i class="bi bi-eye me-1"></i> Detail
                                    </a>
                                    
                                    @if(in_array($po->status, ['draft', 'ordered']))
                                        <a href="{{ route('purchase-orders.edit', $po->po_id) }}" class="btn btn-sm btn-outline-secondary">
                                            <i class="bi bi-pencil me-1"></i> Edit
                                        </a>
                                        <button type="button" class="btn btn-sm btn-outline-danger" onclick="confirmCancel('{{ $po->po_id }}')">
                                            <i class="bi bi-x-circle me-1"></i> Batal
                                        </button>
                                    @endif
                                </div>
                                {{-- Form Hidden untuk Cancel --}}
                                <form id="cancel-form-{{ $po->po_id }}" action="{{ route('purchase-orders.cancel', $po->po_id) }}" method="POST" style="display: none;">
                                    @csrf
                                </form>
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
                <h5 class="fw-bold text-dark">Belum ada Pesanan</h5>
                <p class="text-muted mb-4">Data pesanan pembelian tidak ditemukan atau belum dibuat.</p>
                <a href="{{ route('purchase-orders.create') }}" class="btn btn-primary btn-sm">
                    <i class="bi bi-plus-lg me-1"></i> Buat Pesanan Baru
                </a>
            </div>
        @endforelse
    </div>

    {{-- PAGINATION --}}
    <div class="mt-4 d-flex justify-content-center">
        {{ $purchaseOrders->appends(request()->query())->links() }}
    </div>
</div>
@endsection

@push('scripts')
<script>
    function confirmCancel(poId) {
        Swal.fire({
            title: 'Batalkan Pesanan?',
            text: "Pesanan yang dibatalkan tidak bisa dikembalikan statusnya!",
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#d33',
            cancelButtonColor: '#6c757d',
            confirmButtonText: 'Ya, Batalkan!',
            cancelButtonText: 'Kembali'
        }).then((result) => {
            if (result.isConfirmed) {
                document.getElementById('cancel-form-' + poId).submit();
            }
        });
    }

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
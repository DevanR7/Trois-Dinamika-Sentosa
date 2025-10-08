@extends('layouts.app')

@section('content')
<div class="container-fluid py-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2 class="fw-bold">Pesanan Pembelian</h2>
        <a href="{{ route('purchase-orders.create') }}" class="btn btn-primary">
            <i class="bi bi-plus-circle me-2"></i>Buat Pesanan Baru
        </a>
    </div>

    {{-- FORM PENCARIAN & FILTER --}}
    <div class="card shadow-sm mb-4">
        <div class="card-body">
            @include('purchase_orders.partials._filter')
        </div>
    </div>

    @if (session('success'))<div class="alert alert-success">{{ session('success') }}</div>@endif
    @if (session('error'))<div class="alert alert-danger">{{ session('error') }}</div>@endif

    {{-- TAMPILAN DAFTAR PESANAN MODEL DROPDOWN BARU --}}
    <div class="list-group">
        @forelse ($purchaseOrders as $po)
            @php
                $sisaUtang = $po->total_amount - ($po->total_returned ?? 0) - ($po->amount_paid ?? 0);
            @endphp
            <div class="list-group-item list-group-item-action mb-2 shadow-sm border-0 rounded">
                {{-- Bagian Header yang Selalu Terlihat --}}
                <a class="d-flex w-100 justify-content-between align-items-center text-decoration-none" data-bs-toggle="collapse" href="#collapse-{{ $po->po_id }}" role="button" aria-expanded="false" aria-controls="collapse-{{ $po->po_id }}">
                    <div class="row w-100 align-items-center">
                        <div class="col-md-3 col-6 mb-2 mb-md-0">
                            <strong class="text-primary">{{ $po->po_number }}</strong>
                            <small class="d-block text-muted">{{ $po->supplier_invoice_number ?? 'No Faktur Supplier' }}</small>
                        </div>
                        <div class="col-md-3 col-6 mb-2 mb-md-0">
                            <span class="text-dark">{{ optional($po->order_date)->format('d M Y') }}</span>
                            <small class="d-block text-muted">Tgl. Pesanan</small>
                        </div>
                        <div class="col-md-3 col-6">
                            <span class="text-dark">{{ optional($po->due_date)->format('d M Y') ?? '-' }}</span>
                            <small class="d-block text-muted">Jatuh Tempo</small>
                        </div>
                        <div class="col-md-3 col-6 text-md-end">
                            @if($po->status == 'completed') <span class="badge bg-success">Diterima</span>
                            @elseif($po->status == 'cancelled') <span class="badge bg-dark">Dibatalkan</span>
                            @else <span class="badge bg-secondary">{{ Str::title($po->status) }}</span>
                            @endif
                            
                            @if($po->payment_status == 'paid') <span class="badge bg-primary">Lunas</span>
                            @elseif($po->payment_status == 'partially_paid') <span class="badge bg-info text-dark">Cicil</span>
                            @else <span class="badge bg-danger">Belum Lunas</span>
                            @endif
                        </div>
                    </div>
                </a>

                {{-- Bagian Detail yang Bisa Dibuka-Tutup --}}
                <div class="collapse" id="collapse-{{ $po->po_id }}">
                    <hr>
                    <div class="row align-items-center">
                        <div class="col-md-6 mb-3 mb-md-0">
                            <h6 class="fw-semibold mb-0">{{ $po->supplier->supplier_name ?? 'N/A' }}</h6>
                            <small class="text-muted d-block">Supplier</small>
                        </div>
                        <div class="col-md-6">
                            <div class="row text-md-end">
                                <div class="col">
                                    <h6 class="fw-semibold">Rp {{ number_format($po->total_amount, 0, ',', '.') }}</h6>
                                    <small class="text-muted d-block">Total Tagihan</small>
                                </div>
                                <div class="col">
                                    <h6 class="fw-semibold text-warning">Rp {{ number_format($po->total_returned ?? 0, 0, ',', '.') }}</h6>
                                    <small class="text-muted d-block">Total Retur</small>
                                </div>
                                <div class="col">
                                    <h6 class="fw-bold {{ $sisaUtang > 0 ? 'text-danger' : 'text-success' }}">Rp {{ number_format($sisaUtang, 0, ',', '.') }}</h6>
                                    <small class="text-muted d-block">Sisa Utang</small>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="d-flex justify-content-end gap-1 mt-3">
                        <a href="{{ route('purchase-orders.show', $po->po_id) }}" class="btn btn-sm btn-outline-info" title="Detail"><i class="bi bi-eye"></i> Detail Lengkap</a>
                        @if(in_array($po->status, ['draft', 'ordered']))
                            <a href="{{ route('purchase-orders.edit', $po->po_id) }}" class="btn btn-sm btn-outline-secondary" title="Edit"><i class="bi bi-pencil-square"></i> Edit</a>
                            <form class="cancel-form" action="{{ route('purchase-orders.cancel', $po->po_id) }}" method="POST">
                                @csrf
                                <button type="submit" class="btn btn-sm btn-outline-warning" title="Batalkan"><i class="bi bi-x-circle"></i> Batalkan</button>
                            </form>
                        @endif
                    </div>
                </div>
            </div>
        @empty
            <div class="alert alert-info text-center">
                Tidak ada data pesanan pembelian ditemukan.
            </div>
        @endforelse
    </div>

    <div class="mt-4 d-flex justify-content-center">
        {{ $purchaseOrders->appends(request()->query())->links() }}
    </div>
</div>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    const cancelForms = document.querySelectorAll('.cancel-form');
    cancelForms.forEach(form => {
        form.addEventListener('submit', function(event) {
            event.preventDefault();
            Swal.fire({
                title: 'Anda Yakin?',
                text: "Pesanan yang dibatalkan tidak bisa dikembalikan!",
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#d33',
                cancelButtonColor: '#6c757d',
                confirmButtonText: 'Ya, Batalkan!',
                cancelButtonText: 'Tidak'
            }).then((result) => {
                if (result.isConfirmed) {
                    event.target.submit();
                }
            });
        });
    });
});
</script>
@endpush
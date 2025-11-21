@extends('layouts.app')

@section('content')
<div class="container-fluid py-4">
    {{-- HEADER --}}
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h3 class="fw-bold text-dark mb-0">Verifikasi Pembayaran Batch</h3>
            <p class="text-muted small mb-0">Pembayaran masuk yang perlu dikonfirmasi</p>
        </div>
        <div></div> {{-- Spacer --}}
    </div>

    {{-- CARD LIST --}}
    <div class="d-flex flex-column gap-3">
        @forelse ($pendingBatches as $batch)
            <div class="card card-transaction shadow-sm border-0 border-start border-5 border-warning">
                <div class="card-header bg-white p-3">
                    <div class="row align-items-center">
                        {{-- Kolom 1: Info Utama --}}
                        <div class="col-md-4">
                            <div class="d-flex align-items-center">
                                <div class="me-3 bg-light rounded-circle d-flex align-items-center justify-content-center" style="width:45px; height:45px;">
                                    <i class="bi bi-wallet-fill text-warning fs-5"></i>
                                </div>
                                <div>
                                    <span class="fw-bold text-dark d-block">{{ $batch->client->client_name ?? 'N/A' }}</span>
                                    <span class="text-muted small">{{ $batch->created_at->format('d M Y, H:i') }}</span>
                                </div>
                            </div>
                        </div>

                        {{-- Kolom 2: Metode & Total --}}
                        <div class="col-md-4">
                            <small class="text-muted d-block" style="font-size: 0.7rem;">METODE & TOTAL</small>
                            <div class="d-flex align-items-center gap-2">
                                <span class="badge bg-secondary bg-opacity-10 text-secondary border border-secondary border-opacity-25">
                                    {{ $batch->paymentMethod->name ?? 'N/A' }}
                                </span>
                                <span class="fw-bold text-dark fs-5">Rp {{ number_format($batch->total_amount, 0, ',', '.') }}</span>
                            </div>
                        </div>

                        {{-- Kolom 3: Aksi --}}
                        <div class="col-md-4 text-end">
                            <a href="{{ route('batch-payments.showPending', $batch->batch_payment_id) }}" class="btn btn-sm btn-primary shadow-sm px-3 fw-bold">
                                <i class="bi bi-arrow-right-circle me-1"></i> Proses
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        @empty
            <div class="text-center py-5 my-3 bg-white rounded shadow-sm border border-dashed">
                <div class="mb-3">
                    <i class="bi bi-check2-circle text-muted" style="font-size: 3rem; opacity: 0.5;"></i>
                </div>
                <h5 class="fw-bold text-dark">Tidak Ada Data</h5>
                <p class="text-muted mb-0">Semua pembayaran batch telah diproses.</p>
            </div>
        @endforelse
    </div>

    <div class="mt-4 d-flex justify-content-center">
        {{ $pendingBatches->links() }}
    </div>
</div>
@endsection
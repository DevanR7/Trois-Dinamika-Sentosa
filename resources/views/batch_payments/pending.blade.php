@extends('layouts.app')

@section('content')
<div class="container-fluid py-4">
    <h2 class="fw-bold mb-4">Verifikasi Pembayaran Batch</h2>
    
    <div class="card shadow-sm border-0">
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-hover align-middle">
                    <thead class="table-light">
                        <tr>
                            <th>Tgl. Lapor</th>
                            <th>Klien</th>
                            <th>Metode Lapor</th>
                            <th class="text-end">Jumlah Dilaporkan</th>
                            <th class="text-center">Status</th>
                            <th class="text-center">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($pendingBatches as $batch)
                            <tr>
                                <td>{{ $batch->created_at->format('d M Y H:i') }}</td>
                                <td>{{ $batch->client->client_name ?? 'N/A' }}</td>
                                <td>
                                    @if($batch->payment_method == 'cash')
                                        <span class="badge bg-success">Cash</span>
                                    @else
                                        <span class="badge bg-primary">Transfer Bank</span>
                                    @endif
                                </td>
                                <td class="text-end fw-bold">Rp {{ number_format($batch->total_amount, 0, ',', '.') }}</td>
                                <td class="text-center">
                                    <span class="badge bg-warning text-dark">{{ Str::title(str_replace('_', ' ', $batch->status)) }}</span>
                                </td>
                                <td class="text-center">
                                    <a href="{{ route('batch-payments.showPending', $batch->batch_payment_id) }}" class="btn btn-sm btn-info">
                                        Lihat & Proses
                                    </a>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="text-center py-4 text-muted">
                                    Tidak ada pembayaran batch yang menunggu verifikasi.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            <div class="mt-3 d-flex justify-content-center">
                {{ $pendingBatches->links() }}
            </div>
        </div>
    </div>
</div>
@endsection
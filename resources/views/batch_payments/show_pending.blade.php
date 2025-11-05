@extends('layouts.app')

@section('content')
<div class="container-fluid py-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2 class="fw-bold mb-0">Proses Pembayaran Batch: {{ $batchPayment->client->client_name ?? 'N/A' }}</h2>
        <a href="{{ route('batch-payments.pending') }}" class="btn btn-outline-secondary">
            <i class="bi bi-arrow-left"></i> Kembali ke Daftar
        </a>
    </div>

    @if ($errors->any() || session('error'))
    <div class="alert alert-danger">
        <ul class="mb-0">
            @foreach ($errors->all() as $error)<li>{{ $error }}</li>@endforeach
            @if (session('error'))<li>{{ session('error') }}</li>@endif
        </ul>
    </div>
    @endif

    <div class="row g-4">
        {{-- Kolom Kiri: Detail Pembayaran & Invoice --}}
        <div class="col-lg-8">
            <div class="card shadow-sm border-0 mb-4">
                <div class="card-header bg-white">
                    <h5 class="mb-0 fw-semibold">Detail Pembayaran Dilaporkan</h5>
                </div>
                <div class="card-body">
                    <dl class="row">
                        <dt class="col-sm-4">Metode Pembayaran</dt>
                        <dd class="col-sm-8">
                            @if($batchPayment->payment_method == 'cash')
                                <span class="badge bg-success fs-6">Cash (via Sales)</span>
                            @else
                                <span class="badge bg-primary fs-6">Transfer Bank</span>
                            @endif
                        </dd>

                        <dt class="col-sm-4">Jumlah Dilaporkan</dt>
                        <dd class="col-sm-8 fw-bold fs-5">Rp {{ number_format($batchPayment->total_amount, 0, ',', '.') }}</dd>

                        <dt class="col-sm-4">Tanggal Lapor</dt>
                        <dd class="col-sm-8">{{ $batchPayment->created_at->format('d M Y H:i') }}</dd>
                        
                        <dt class="col-sm-4">Catatan Klien</dt>
                        <dd class="col-sm-8">{{ $details['notes'] ?? '-' }}</dd>

                        {{-- ====================================================== --}}
                        {{-- ✅ INI ADALAH BAGIAN BUKTI PEMBAYARAN YANG ANDA MINTA --}}
                        {{-- ====================================================== --}}
                        @if($batchPayment->payment_method == 'cash')
                            <dt class="col-sm-4">Diterima Sales</dt>
                            <dd class="col-sm-8">{{ $salesUser->full_name ?? 'N/A' }}</dd>
                        @else
                            <dt class="col-sm-4">Bukti Transfer</dt>
                            <dd class="col-sm-8">
                                @if(!empty($details['proof_path']))
                                    <a href="{{ asset('storage/' . $details['proof_path']) }}" target="_blank" class="btn btn-sm btn-outline-info">
                                        <i class="bi bi-eye me-1"></i> Lihat Bukti
                                    </a>
                                @else
                                    <span class="text-danger">Bukti tidak terlampir.</span>
                                @endif
                            </dd>
                        @endif
                        {{-- ====================================================== --}}

                    </dl>
                </div>
            </div>
            
            {{-- ====================================================== --}}
            {{-- ✅ INI ADALAH BAGIAN DAFTAR INVOICE YANG ANDA MINTA --}}
            {{-- ====================================================== --}}
            <div class="card shadow-sm border-0">
                <div class="card-header bg-white">
                    <h5 class="mb-0 fw-semibold">Invoice yang Akan Dibayar</h5>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-sm table-striped mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th>No. Invoice</th>
                                    <th>Tgl. Jatuh Tempo</th>
                                    <th class="text-end">Sisa Tagihan</th>
                                </tr>
                            </thead>
                            <tbody>
                                @php $totalTagihan = 0; @endphp
                                @forelse ($invoices as $invoice)
                                    @php $sisa = $invoice->remaining_balance; $totalTagihan += $sisa; @endphp
                                    <tr>
                                        <td>{{ $invoice->invoice_number }}</td>
                                        <td>{{ $invoice->due_date->format('d M Y') }}</td>
                                        <td class="text-end">Rp {{ number_format($sisa, 0, ',', '.') }}</td>
                                    </tr>
                                @empty
                                    <tr><td colspan="3" class="text-center p-3 text-danger">Invoice tidak ditemukan atau sudah dihapus.</td></tr>
                                @endforelse
                            </tbody>
                            <tfoot class="table-group-divider">
                                <tr class="fw-bold">
                                    <td colspan="2" class="text-end">Total Tagihan Dipilih:</td>
                                    <td class="text-end">Rp {{ number_format($totalTagihan, 0, ',', '.') }}</td>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                </div>
            </div>
            {{-- ====================================================== --}}

        </div>

        {{-- Kolom Kanan: Ringkasan Alokasi & Aksi --}}
        <div class="col-lg-4">
            <div class="card shadow-sm border-0 sticky-top" style="top: 20px;">
                <div class="card-header bg-white">
                    <h5 class="mb-0 fw-semibold">Ringkasan Alokasi</h5>
                </div>
                <div class="card-body">
                    @php
                        // Variabel $totalTagihan sudah dihitung di atas
                        $kreditDipakai = (float)($details['credit_amount_to_use'] ?? 0);
                        $inputDana = (float)$batchPayment->total_amount;
                        $totalDana = $kreditDipakai + $inputDana;
                        $overpayment = max(0, $totalDana - $totalTagihan);
                    @endphp
                    
                    <div class="d-flex justify-content-between">
                        <span>Total Tagihan</span>
                        <span>Rp {{ number_format($totalTagihan, 0, ',', '.') }}</span>
                    </div>
                    <hr class="my-2">
                    <div class="d-flex justify-content-between">
                        <span>Dana Transfer/Cash</span>
                        <span>(+) Rp {{ number_format($inputDana, 0, ',', '.') }}</span>
                    </div>
                    <div class="d-flex justify-content-between text-success">
                        <span>Saldo Kredit Dipakai</span>
                        <span>(+) Rp {{ number_format($kreditDipakai, 0, ',', '.') }}</span>
                    </div>
                    <hr class="my-2">
                    <div class="d-flex justify-content-between fw-bold">
                        <span>Total Dana</span>
                        <span>Rp {{ number_format($totalDana, 0, ',', '.') }}</span>
                    </div>
                    
                    @if($overpayment > 0)
                        <div class="alert alert-success mt-3 p-2 small">
                            <strong>Overpayment:</strong> Sejumlah <strong>Rp {{ number_format($overpayment, 0, ',', '.') }}</strong> akan dikembalikan sebagai Saldo Kredit Klien.
                        </div>
                    @endif
                    
                    <hr>
                    <div class="d-grid gap-2 mt-4">
                        <form action="{{ route('batch-payments.approve', $batchPayment->batch_payment_id) }}" method="POST" id="form-approve">
                            @csrf
                            <button type="submit" class="btn btn-success btn-lg w-100">Setujui & Alokasikan</button>
                        </form>
                        <form action="{{ route('batch-payments.reject', $batchPayment->batch_payment_id) }}" method="POST" id="form-reject">
                            @csrf
                            <button type="submit" class="btn btn-danger w-100">Tolak Pembayaran</button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
{{-- Script SweetAlert --}}
<script>
document.addEventListener('DOMContentLoaded', function () {
    
    // --- Konfirmasi Setujui ---
    const approveForm = document.getElementById('form-approve');
    if (approveForm) {
        approveForm.addEventListener('submit', function (e) {
            e.preventDefault(); // Hentikan form
            Swal.fire({
                title: 'Setujui Pembayaran Ini?',
                text: "Aksi ini akan mengalokasikan dana, memotong saldo kredit, dan memperbarui status invoice. Aksi ini tidak dapat dibatalkan.",
                icon: 'question',
                showCancelButton: true,
                confirmButtonColor: '#198754', // Hijau
                cancelButtonColor: '#6c757d',
                confirmButtonText: 'Ya, Setujui & Alokasikan!',
                cancelButtonText: 'Batal'
            }).then((result) => {
                if (result.isConfirmed) {
                    this.submit(); // Lanjutkan submit form
                }
            });
        });
    }

    // --- Konfirmasi Tolak ---
    const rejectForm = document.getElementById('form-reject');
    if (rejectForm) {
        rejectForm.addEventListener('submit', function (e) {
            e.preventDefault(); // Hentikan form
            Swal.fire({
                title: 'Anda Yakin?',
                text: "Anda akan MENOLAK laporan pembayaran ini. Status akan diubah menjadi 'Rejected'.",
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#d33', // Merah
                cancelButtonColor: '#6c757d',
                confirmButtonText: 'Ya, Tolak!',
                cancelButtonText: 'Batal'
            }).then((result) => {
                if (result.isConfirmed) {
                    this.submit(); // Lanjutkan submit form
                }
            });
        });
    }

});
</script>
@endpush
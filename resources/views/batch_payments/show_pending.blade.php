@extends('layouts.app')

@section('content')
<div class="container-fluid py-2">
    {{-- HEADER --}}
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h3 class="fw-bold text-dark mb-1">Proses Pembayaran</h3>
            <p class="text-muted mb-0 small">Klien: <span class="text-primary fw-bold">{{ $batchPayment->client->client_name ?? 'N/A' }}</span></p>
        </div>
        <div>
            <a href="{{ route('batch-payments.pending') }}" class="btn btn-outline-secondary btn-sm">
                <i class="bi bi-arrow-left me-1"></i> Kembali
            </a>
        </div>
    </div>

    <div class="row g-4">
        {{-- KOLOM KIRI: DETAIL --}}
        <div class="col-lg-8">
            <div class="card card-transaction border-0 shadow-sm mb-4">
                <div class="card-header bg-white p-3 border-bottom">
                    <div class="form-section-title mb-0"><i class="bi bi-info-circle"></i> Detail Laporan</div>
                </div>
                <div class="card-body p-4">
                    <div class="row g-4">
                        <div class="col-sm-6">
                            <label class="small fw-bold text-muted d-block mb-1">METODE PEMBAYARAN</label>
                            <span class="badge bg-secondary bg-opacity-10 text-secondary border border-secondary border-opacity-25 fs-6">
                                {{ $batchPayment->paymentMethod->name ?? 'N/A' }}
                            </span>
                        </div>
                        <div class="col-sm-6">
                            <label class="small fw-bold text-muted d-block mb-1">TANGGAL LAPOR</label>
                            <span class="text-dark">{{ $batchPayment->created_at->format('d F Y, H:i') }}</span>
                        </div>
                        <div class="col-sm-6">
                            <label class="small fw-bold text-muted d-block mb-1">DITERIMA OLEH</label>
                            @if($batchPayment->paymentMethod && str_contains(strtolower($batchPayment->paymentMethod->name), 'cash'))
                                <span class="text-dark fw-medium">{{ $salesUser->full_name ?? 'N/A' }}</span>
                            @else
                                <span class="text-muted">-</span>
                            @endif
                        </div>
                        <div class="col-sm-6">
                            <label class="small fw-bold text-muted d-block mb-1">BUKTI TRANSFER</label>
                            @if(!empty($details['proof_path']))
                                <a href="{{ asset('storage/' . $details['proof_path']) }}" target="_blank" class="btn btn-sm btn-outline-primary">
                                    <i class="bi bi-file-earmark-image me-1"></i> Lihat Bukti
                                </a>
                            @else
                                <span class="text-muted fst-italic">Tidak ada bukti terlampir.</span>
                            @endif
                        </div>
                        <div class="col-12">
                            <label class="small fw-bold text-muted d-block mb-1">CATATAN KLIEN</label>
                            <p class="mb-0 bg-light p-3 rounded border border-light fst-italic text-muted small">
                                {{ $details['notes'] ?? 'Tidak ada catatan.' }}
                            </p>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- KOLOM KANAN: SUMMARY & ACTION --}}
        <div class="col-lg-4">
            <div class="card card-transaction border-0 shadow-sm sticky-top" style="top: 20px;">
                <div class="card-header bg-white p-3 border-bottom">
                    <div class="form-section-title mb-0"><i class="bi bi-calculator"></i> Ringkasan Alokasi</div>
                </div>
                <div class="card-body p-4">
                    {{-- PERHITUNGAN PHP --}}
                    @php
                        $totalTagihan = 0;
                        foreach($invoices as $inv) { $totalTagihan += $inv->remaining_balance; }
                        
                        $kreditDipakai = (float)($details['credit_amount_to_use'] ?? 0);
                        $inputDana = (float)$batchPayment->total_amount;
                        $totalDana = $kreditDipakai + $inputDana;
                        $overpayment = max(0, $totalDana - $totalTagihan);
                    @endphp

                    <div class="d-flex justify-content-between mb-2 small">
                        <span>Total Tagihan ({{ count($invoices) }} Invoice)</span>
                        <span class="fw-medium">Rp {{ number_format($totalTagihan, 0, ',', '.') }}</span>
                    </div>
                    <hr class="border-dashed my-2">
                    <div class="d-flex justify-content-between mb-2 small">
                        <span>Dana Transfer/Cash</span>
                        <span class="text-primary fw-bold">(+) Rp {{ number_format($inputDana, 0, ',', '.') }}</span>
                    </div>
                    <div class="d-flex justify-content-between mb-2 small text-success">
                        <span>Saldo Kredit Dipakai</span>
                        <span>(+) Rp {{ number_format($kreditDipakai, 0, ',', '.') }}</span>
                    </div>
                    
                    <div class="bg-light p-3 rounded border border-light mt-3">
                        <div class="d-flex justify-content-between align-items-center">
                            <span class="fw-bold text-dark">TOTAL DANA</span>
                            <span class="fs-4 fw-bold text-success">Rp {{ number_format($totalDana, 0, ',', '.') }}</span>
                        </div>
                    </div>

                    @if($overpayment > 0)
                        <div class="alert alert-success mt-3 mb-0 p-2 small border-0 d-flex align-items-start">
                            <i class="bi bi-info-circle-fill me-2 mt-1"></i>
                            <div>
                                <strong>Overpayment:</strong><br>
                                Rp {{ number_format($overpayment, 0, ',', '.') }} akan masuk ke Saldo Kredit Klien.
                            </div>
                        </div>
                    @endif

                    <hr class="border-dashed my-4">

                    {{-- FORM APPROVE --}}
                    <form action="{{ route('batch-payments.approve', $batchPayment->batch_payment_id) }}" method="POST" id="form-approve">
                        @csrf
                        <div class="mb-3">
                            <label for="company_bank_account_id" class="form-label small fw-bold text-muted">SETOR KE AKUN</label>
                            <select name="company_bank_account_id" id="company_bank_account_id" class="form-select form-select-sm" required>
                                <option value="">-- Pilih Akun --</option>
                                @foreach($companyBankAccounts as $account)
                                    <option value="{{ $account->company_bank_account_id }}">
                                        {{ $account->bank_name }} - {{ $account->account_number }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <button type="submit" class="btn btn-success w-100 fw-bold shadow-sm mb-2">
                            <i class="bi bi-check-circle-fill me-1"></i> Setujui & Alokasikan
                        </button>
                    </form>

                    {{-- FORM REJECT --}}
                    <form action="{{ route('batch-payments.reject', $batchPayment->batch_payment_id) }}" method="POST" id="form-reject">
                        @csrf
                        <button type="submit" class="btn btn-outline-danger w-100">
                            <i class="bi bi-x-circle me-1"></i> Tolak Pembayaran
                        </button>
                    </form>

                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
document.addEventListener('DOMContentLoaded', function () {
    const approveForm = document.getElementById('form-approve');
    if (approveForm) {
        approveForm.addEventListener('submit', function (e) {
            e.preventDefault(); 
            Swal.fire({
                title: 'Setujui Pembayaran?',
                text: "Dana akan dialokasikan ke invoice dan saldo diperbarui.",
                icon: 'question',
                showCancelButton: true,
                confirmButtonColor: '#198754',
                cancelButtonColor: '#6c757d',
                confirmButtonText: 'Ya, Setujui!',
                cancelButtonText: 'Batal'
            }).then((result) => { if (result.isConfirmed) this.submit(); });
        });
    }

    const rejectForm = document.getElementById('form-reject');
    if (rejectForm) {
        rejectForm.addEventListener('submit', function (e) {
            e.preventDefault(); 
            Swal.fire({
                title: 'Tolak Pembayaran?',
                text: "Status pembayaran akan diubah menjadi Rejected.",
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#d33',
                cancelButtonColor: '#6c757d',
                confirmButtonText: 'Ya, Tolak!',
                cancelButtonText: 'Batal'
            }).then((result) => { if (result.isConfirmed) this.submit(); });
        });
    }
});
</script>
@endpush
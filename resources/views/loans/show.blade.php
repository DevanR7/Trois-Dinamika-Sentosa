@extends('layouts.app')

@section('content')
<div class="container-fluid py-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2 class="fw-bold mb-0 text-dark">Detail Pinjaman</h2>
        <a href="{{ route('loans.index') }}" class="btn btn-outline-secondary">
            <i class="bi bi-arrow-left"></i> Kembali
        </a>
    </div>

    {{-- INFO UTAMA --}}
    <div class="card shadow-sm mb-4 border-0">
        <div class="card-body p-4">
            <div class="row align-items-center">
                <div class="col-md-8">
                    <h3 class="fw-bold text-primary mb-1">{{ $loan->lender_name }}</h3>
                    <p class="text-muted mb-3">{{ $loan->description ?? 'Tidak ada deskripsi' }}</p>
                    
                    <div class="d-flex gap-4 mb-3">
                        <div>
                            <small class="text-uppercase fw-bold text-secondary d-block">Tanggal Pinjam</small>
                            <span class="fs-5">{{ $loan->loan_date->format('d M Y') }}</span>
                        </div>
                        <div>
                            <small class="text-uppercase fw-bold text-secondary d-block">Status</small>
                            @if ($loan->status == 'active')
                                <span class="badge bg-warning text-dark fs-6 mt-1">Aktif / Belum Lunas</span>
                            @else
                                <span class="badge bg-success fs-6 mt-1">Lunas</span>
                            @endif
                        </div>
                    </div>
                </div>
                
                <div class="col-md-4 border-start">
                    <div class="ps-md-3">
                        <small class="text-uppercase fw-bold text-secondary">Sisa Pokok Pinjaman</small>
                        <h2 class="fw-bold text-danger mb-0">Rp {{ number_format($loan->remaining_balance, 0, ',', '.') }}</h2>
                        <div class="progress mt-2" style="height: 6px;">
                            @php
                                $percentPaid = ($loan->principal_amount > 0) 
                                    ? (($loan->principal_amount - $loan->remaining_balance) / $loan->principal_amount) * 100 
                                    : 0;
                            @endphp
                            <div class="progress-bar bg-success" role="progressbar" style="width: {{ $percentPaid }}%"></div>
                        </div>
                        <small class="text-muted">Terbayar: {{ round($percentPaid) }}%</small>
                    </div>
                </div>
            </div>

            <div class="row mt-4 pt-3 border-top bg-light rounded py-3 mx-0">
                <div class="col-md-3">
                    <small class="d-block text-muted">Total Pinjaman Awal</small>
                    <strong>Rp {{ number_format($loan->principal_amount, 0, ',', '.') }}</strong>
                </div>
                <div class="col-md-3">
                    <small class="d-block text-muted">Total Pokok Dibayar</small>
                    <strong class="text-success">Rp {{ number_format($loan->payments->sum('principal_paid'), 0, ',', '.') }}</strong>
                </div>
                <div class="col-md-3">
                    <small class="d-block text-muted">Total Bunga Dibayar</small>
                    <strong class="text-warning text-dark">Rp {{ number_format($loan->payments->sum('interest_paid'), 0, ',', '.') }}</strong>
                </div>
                <div class="col-md-3">
                    <small class="d-block text-muted">Akun Akuntansi</small>
                    <div class="small">{{ $loan->loanAccount->account_name ?? '-' }} (Utang)</div>
                    <div class="small">{{ $loan->cashBankAccount->account_name ?? '-' }} (Kas Masuk)</div>
                </div>
            </div>
        </div>
    </div>

    {{-- RIWAYAT PEMBAYARAN --}}
    <div class="card shadow-sm border-0">
        <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center">
            <h5 class="mb-0 fw-bold"><i class="bi bi-clock-history me-2"></i> Riwayat Pembayaran Cicilan</h5>
            
            @if($loan->status == 'active')
                <a href="{{ route('loans.payments.create', $loan) }}" class="btn btn-success">
                    <i class="bi bi-plus-lg"></i> Bayar Cicilan
                </a>
            @endif
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th class="ps-4">Tanggal</th>
                            <th>Keterangan</th>
                            <th class="text-end">Pokok</th>
                            <th class="text-end">Bunga</th>
                            <th class="text-end">Total Bayar</th>
                            <th>Via Akun</th>
                            <th>Admin</th>
                            <th class="text-center">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($loan->payments as $payment)
                        <tr>
                            <td class="ps-4">{{ $payment->payment_date->format('d/m/y') }}</td>
                            <td>{{ Str::limit($payment->notes, 30) ?? '-' }}</td>
                            <td class="text-end">Rp {{ number_format($payment->principal_paid, 0, ',', '.') }}</td>
                            <td class="text-end text-muted">Rp {{ number_format($payment->interest_paid, 0, ',', '.') }}</td>
                            <td class="text-end fw-bold text-success">Rp {{ number_format($payment->total_paid, 0, ',', '.') }}</td>
                            <td>{{ $payment->cashBankAccount->account_name ?? '-' }}</td>
                            <td><small>{{ $payment->user->name ?? 'System' }}</small></td>
                            <td class="text-center">
                                <form action="{{ route('loans.payments.destroy', [$loan, $payment]) }}" method="POST" 
                                      class="d-inline form-delete-payment"
                                      data-payment-label="Pembayaran tgl {{ $payment->payment_date->format('d/m/Y') }} sebesar Rp {{ number_format($payment->total_paid, 0, ',', '.') }}">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="btn btn-sm btn-outline-danger" title="Batalkan Pembayaran">
                                        <i class="bi bi-x-circle"></i>
                                    </button>
                                </form>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="8" class="text-center py-5 text-muted">Belum ada riwayat pembayaran cicilan.</td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    const deleteForms = document.querySelectorAll('.form-delete-payment');
    deleteForms.forEach(form => {
        form.addEventListener('submit', function (event) {
            event.preventDefault(); 
            const label = event.target.dataset.paymentLabel;
            
            Swal.fire({
                title: 'Batalkan Pembayaran?',
                html: `Anda akan membatalkan:<br><b>${label}</b><br><br><small class="text-danger">Sisa pokok pinjaman akan dikembalikan dan jurnal dibalik.</small>`,
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#dc3545',
                confirmButtonText: 'Ya, Batalkan!',
                cancelButtonText: 'Tidak'
            }).then((result) => {
                if (result.isConfirmed) event.target.submit();
            });
        });
    });

    // Notifikasi Flash
    @if(session('success')) Swal.fire('Berhasil!', "{{ session('success') }}", 'success'); @endif
    @if(session('error')) Swal.fire('Gagal!', "{{ session('error') }}", 'error'); @endif
});
</script>
@endpush
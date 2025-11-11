@extends('layouts.app')

@section('content')
<div class="container-fluid py-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2 class="fw-bold mb-0">Detail Pinjaman: {{ $loan->lender_name }}</h2>
        <a href="{{ route('loans.index') }}" class="btn btn-outline-dark">
            <i class="bi bi-arrow-left"></i> Kembali ke Daftar Pinjaman
        </a>
    </div>

    {{-- Detail Pinjaman --}}
    <div class="card shadow-sm mb-4">
        <div class="card-body">
            <div class="row">
                <div class="col-md-4">
                    <p class="mb-1 text-muted">Pemberi Pinjaman</p>
                    <h5 class="fw-bold">{{ $loan->lender_name }}</h5>
                </div>
                <div class="col-md-4">
                    <p class="mb-1 text-muted">Deskripsi</p>
                    <h5 class="fw-bold">{{ $loan->description ?? '-' }}</h5>
                </div>
                <div class="col-md-2">
                    <p class="mb-1 text-muted">Tanggal Pinjam</p>
                    <h5 class="fw-bold">{{ $loan->loan_date->format('d M Y') }}</h5>
                </div>
                <div class="col-md-2">
                    <p class="mb-1 text-muted">Status</p>
                    @if ($loan->status == 'active')
                        <span class="badge bg-warning fs-6">Aktif</span>
                    @else
                        <span class="badge bg-success fs-6">Lunas</span>
                    @endif
                </div>
            </div>

            {{-- ✅ BARIS BARU UNTUK INFO AKUN --}}
            <div class="row mt-3">
                <div class="col-md-4">
                    <p class="mb-1 text-muted">Akun Utang (Liabilitas)</p>
                    <h5 class="fw-bold">{{ $loan->loanAccount->account_name ?? 'N/A' }}</h5>
                </div>
                <div class="col-md-4">
                    <p class="mb-1 text-muted">Dana Diterima Ke (Aset)</p>
                    <h5 class="fw-bold">{{ $loan->cashBankAccount->account_name ?? 'N/A' }}</h5>
                </div>
            </div>

            <hr>
            <div class="row text-center">
                <div class="col-4">
                    <p class="mb-1 text-muted">Total Pokok Pinjaman</p>
                    <h4 class="fw-bold text-primary">Rp {{ number_format($loan->principal_amount, 0, ',', '.') }}</h4>
                </div>
                <div class="col-4">
                    <p class="mb-1 text-muted">Total Telah Dibayar (Pokok)</p>
                    <h4 class="fw-bold text-success">Rp {{ number_format($loan->payments->sum('principal_paid'), 0, ',', '.') }}</h4>
                </div>
                <div class="col-4">
                    <p class="mb-1 text-muted">Sisa Pokok Pinjaman</p>
                    <h4 class="fw-bold text-danger">Rp {{ number_format($loan->remaining_balance, 0, ',', '.') }}</h4>
                </div>
            </div>
        </div>
    </div>

    {{-- Riwayat Pembayaran --}}
    <div class="card shadow-sm">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h5 class="mb-0 fw-semibold">Riwayat Pembayaran Cicilan</h5>
            @if($loan->status == 'active')
            <a href="{{ route('loans.payments.create', $loan) }}" class="btn btn-dark">
                <i class="bi bi-plus-lg"></i> Catat Pembayaran
            </a>
            @endif
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-striped table-hover align-middle">
                    <thead class="table-dark">
                        <tr>
                            <th>Tanggal Bayar</th>
                            <th>Catatan</th>
                            <th class="text-end">Bayar Pokok</th>
                            <th class="text-end">Bayar Bunga</th>
                            <th class="text-end">Total Bayar</th>
                            <th>Akun Beban</th>
                            <th>Sumber Dana</th>
                            <th>Dicatat Oleh</th>
                            <th>Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($loan->payments as $payment)
                        <tr>
                            <td>{{ $payment->payment_date->format('d/m/Y') }}</td>
                            <td>{{ $payment->notes ?? '-' }}</td>
                            <td class="text-end">Rp {{ number_format($payment->principal_paid, 0, ',', '.') }}</td>
                            <td class="text-end">Rp {{ number_format($payment->interest_paid, 0, ',', '.') }}</td>
                            <td class="text-end fw-bold">Rp {{ number_format($payment->total_paid, 0, ',', '.') }}</td>
                            
                            <td>{{ $payment->interestExpenseAccount->account_name ?? '-' }}</td>
                            <td>{{ $payment->cashBankAccount->account_name ?? 'N/A' }}</td>
                            
                            <td>{{ $payment->user->name ?? 'N/A' }}</td>
                            <td>
                                {{-- ✅ MODIFIKASI: Hapus onsubmit, tambah class & data attribute --}}
                                @php
                                    $paymentLabel = 'Pembayaran tgl ' . $payment->payment_date->format('d/m/Y') . ' (Rp ' . number_format($payment->total_paid, 0, ',', '.') . ')';
                                @endphp
                                <form action="{{ route('loans.payments.destroy', [$loan, $payment]) }}" method="POST" 
                                      class="d-inline form-delete-payment" 
                                      data-payment-label="{{ $paymentLabel }}">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="btn btn-sm btn-outline-danger" title="Hapus (Rollback)">
                                        <i class="bi bi-trash-fill"></i>
                                    </button>
                                </form>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            {{-- ✅ Colspan disesuaikan --}}
                            <td colspan="9" class="text-center text-muted">Belum ada riwayat pembayaran.</td>
                        </tr>
                        @endforelse
                    </tbody>
                    <tfoot class="table-group-divider">
                        <tr class="table-light">
                            {{-- ✅ Colspan disesuaikan --}}
                            <td colspan="2" class="text-end fw-bold">Total Terbayar:</td>
                            <td class="text-end fw-bold">Rp {{ number_format($loan->payments->sum('principal_paid'), 0, ',', '.') }}</td>
                            <td class="text-end fw-bold">Rp {{ number_format($loan->payments->sum('interest_paid'), 0, ',', '.') }}</td>
                            <td class="text-end fw-bold">Rp {{ number_format($loan->payments->sum('total_paid'), 0, ',', '.') }}</td>
                            {{-- ✅ Colspan disesuaikan --}}
                            <td colspan="4"></td>
                        </tr>
                    </tfoot>
                </table>
            </div>
        </div>
    </div>
</div>
@endsection

{{-- ✅ MODIFIKASI: Tambah script SweetAlert --}}
@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    const deleteForms = document.querySelectorAll('.form-delete-payment');
    
    deleteForms.forEach(form => {
        form.addEventListener('submit', function (event) {
            event.preventDefault(); 
            
            const paymentLabel = event.target.dataset.paymentLabel;
            const warningText = `Anda yakin ingin membatalkan pembayaran ini: "${paymentLabel}"? Sisa pokok pinjaman akan dihitung ulang dan jurnal akan dibalik.`;

            Swal.fire({
                title: 'Anda Yakin?',
                text: warningText,
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#d33',
                cancelButtonColor: '#6c757d',
                confirmButtonText: 'Ya, Batalkan!',
                cancelButtonText: 'Batal'
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
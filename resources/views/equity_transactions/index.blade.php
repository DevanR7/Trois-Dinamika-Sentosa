@extends('layouts.app')

@section('content')
<div class="container-fluid py-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2 class="fw-bold mb-0">Transaksi Modal</h2>
        <a href="{{ route('equity-transactions.create') }}" class="btn btn-dark shadow-sm">
            <i class="bi bi-plus-lg"></i> Catat Transaksi
        </a>
    </div>
    
    {{-- FORM FILTER --}}
    <div class="card shadow-sm mb-4 border-0">
        <div class="card-body bg-light rounded">
            <form action="{{ route('equity-transactions.index') }}" method="GET">
                <div class="row g-3 align-items-end">
                    <div class="col-md-4">
                        <label for="start_date" class="form-label fw-bold small text-muted">Dari Tanggal</label>
                        <input type="date" name="start_date" id="start_date" class="form-control" value="{{ request('start_date') }}">
                    </div>
                    <div class="col-md-4">
                        <label for="end_date" class="form-label fw-bold small text-muted">Sampai Tanggal</label>
                        <input type="date" name="end_date" id="end_date" class="form-control" value="{{ request('end_date') }}">
                    </div>
                    <div class="col-md-2">
                         <label for="type" class="form-label fw-bold small text-muted">Tipe Transaksi</label>
                        <select name="type" id="type" class="form-select">
                            <option value="">Semua Tipe</option>
                            <option value="investment" {{ request('type') == 'investment' ? 'selected' : '' }}>Setoran Modal</option>
                            <option value="drawing" {{ request('type') == 'drawing' ? 'selected' : '' }}>Penarikan Modal</option>
                        </select>
                    </div>
                    <div class="col-md-2">
                        <button type="submit" class="btn btn-primary w-100 fw-bold"><i class="bi bi-funnel-fill"></i> Filter</button>
                    </div>
                </div>
            </form>
        </div>
    </div>

    {{-- DAFTAR TRANSAKSI --}}
    <div class="card shadow-sm border-0">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-dark">
                        <tr>
                            <th class="ps-4 py-3">Tanggal</th>
                            <th class="py-3">Akun Transaksi</th> 
                            <th class="py-3">Deskripsi</th>
                            <th class="text-end py-3">Jumlah</th>
                            <th class="py-3">Dicatat Oleh</th>
                            <th class="text-center py-3">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($transactions as $transaction)
                        <tr>
                            <td class="ps-4">{{ $transaction->transaction_date->format('d/m/Y') }}</td>
                            
                            <td>
                                @if ($transaction->type == 'investment')
                                    {{-- Setoran: Kas (Debit), Modal (Kredit) --}}
                                    <span class="fw-bold text-success">{{ $transaction->cashBankAccount->account_name ?? 'N/A' }}</span>
                                    <i class="bi bi-arrow-left text-muted mx-1"></i>
                                    <span class="text-muted">{{ $transaction->equityAccount->account_name ?? 'N/A' }}</span>
                                    <span class="badge bg-success ms-2">Setoran</span>
                                @else
                                    {{-- Penarikan: Prive (Debit), Kas (Kredit) --}}
                                    <span class="fw-bold text-danger">{{ $transaction->equityAccount->account_name ?? 'N/A' }}</span>
                                    <i class="bi bi-arrow-left text-muted mx-1"></i>
                                    <span class="text-muted">{{ $transaction->cashBankAccount->account_name ?? 'N/A' }}</span>
                                    <span class="badge bg-danger ms-2">Penarikan</span>
                                @endif
                            </td>

                            <td>{{ Str::limit($transaction->description, 40) }}</td>
                            <td class="text-end fw-bold font-monospace">
                                Rp {{ number_format($transaction->amount, 0, ',', '.') }}
                            </td>
                            <td><small>{{ $transaction->user->name ?? 'N/A' }}</small></td>
                            <td class="text-center">
                                <div class="btn-group" role="group">
                                    <a href="{{ route('equity-transactions.edit', $transaction) }}" class="btn btn-sm btn-outline-primary" title="Edit">
                                        <i class="bi bi-pencil-square"></i>
                                    </a>
                                    
                                    <form action="{{ route('equity-transactions.destroy', $transaction) }}" method="POST" 
                                          class="d-inline form-delete-transaction" 
                                          data-transaction-label="{{ ($transaction->type == 'investment' ? 'Setoran' : 'Penarikan') . ' Rp ' . number_format($transaction->amount, 0, ',', '.') }}">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="btn btn-sm btn-outline-danger" title="Hapus">
                                            <i class="bi bi-trash-fill"></i>
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="6" class="text-center py-5 text-muted">
                                <i class="bi bi-inbox fs-1 d-block mb-2"></i>
                                Belum ada data transaksi modal.
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                    
                    {{-- RINGKASAN TOTAL --}}
                    <tfoot class="bg-light">
                        <tr>
                            <td colspan="3" class="text-end fw-bold py-2 text-muted small text-uppercase">Total Setoran</td>
                            <td class="text-end fw-bold text-success py-2">Rp {{ number_format($totalInvestment, 0, ',', '.') }}</td>
                            <td colspan="2"></td>
                        </tr>
                        <tr>
                            <td colspan="3" class="text-end fw-bold py-2 text-muted small text-uppercase">Total Penarikan</td>
                            <td class="text-end fw-bold text-danger py-2">(Rp {{ number_format($totalDrawing, 0, ',', '.') }})</td>
                            <td colspan="2"></td>
                        </tr>
                         <tr class="table-secondary border-top border-dark">
                            <td colspan="3" class="text-end fw-bold fs-5 py-3">Perubahan Modal Bersih</td>
                            <td class="text-end fw-bold fs-5 py-3 {{ $netModal >= 0 ? 'text-success' : 'text-danger' }}">
                                @if($netModal < 0)
                                    (Rp {{ number_format(abs($netModal), 0, ',', '.') }})
                                @else
                                    Rp {{ number_format($netModal, 0, ',', '.') }}
                                @endif
                            </td>
                            <td colspan="2"></td>
                        </tr>
                    </tfoot>
                </table>
            </div>
            @if($transactions->hasPages())
                <div class="card-footer bg-white">
                    {{ $transactions->links() }}
                </div>
            @endif
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    
    // 1. Konfirmasi Delete
    const deleteForms = document.querySelectorAll('.form-delete-transaction');
    deleteForms.forEach(form => {
        form.addEventListener('submit', function (event) {
            event.preventDefault(); 
            const txLabel = event.target.dataset.transactionLabel;
            
            Swal.fire({
                title: 'Hapus Transaksi?',
                html: `Anda akan menghapus:<br><b>${txLabel}</b><br><br><small class="text-danger">Tindakan ini juga akan membalik jurnal akuntansi terkait.</small>`,
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#dc3545',
                cancelButtonColor: '#6c757d',
                confirmButtonText: 'Ya, Hapus!',
                cancelButtonText: 'Batal',
                reverseButtons: true
            }).then((result) => {
                if (result.isConfirmed) {
                    event.target.submit();
                }
            });
        });
    });

    // 2. Notifikasi Flash Message
    @if(session('success'))
        Swal.fire({
            icon: 'success',
            title: 'Berhasil!',
            text: "{{ session('success') }}",
            timer: 3000,
            showConfirmButton: false
        });
    @endif

    @if(session('error'))
        Swal.fire({
            icon: 'error',
            title: 'Gagal!',
            text: "{{ session('error') }}",
        });
    @endif
});
</script>
@endpush
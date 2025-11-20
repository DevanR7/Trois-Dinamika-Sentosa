@extends('layouts.app')

@section('content')
<div class="container-fluid py-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2 class="fw-bold mb-0">Data Pinjaman</h2>
        <a href="{{ route('loans.create') }}" class="btn btn-dark shadow-sm">
            <i class="bi bi-plus-lg"></i> Tambah Pinjaman
        </a>
    </div>

    {{-- DAFTAR PINJAMAN --}}
    <div class="card shadow-sm border-0">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-dark">
                        <tr>
                            <th class="ps-4 py-3">Tanggal Pinjam</th>
                            <th class="py-3">Pemberi Pinjaman</th>
                            <th class="py-3">Akun Utang</th>
                            <th class="text-end py-3">Jumlah Pokok</th>
                            <th class="text-end py-3">Sisa Pokok</th>
                            <th class="py-3">Kas Diterima</th>
                            <th class="text-center py-3">Status</th>
                            <th class="text-center py-3">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($loans as $loan)
                        <tr>
                            <td class="ps-4">{{ $loan->loan_date->format('d/m/Y') }}</td>
                            <td class="fw-semibold">{{ $loan->lender_name }}</td>
                            <td><span class="badge bg-light text-dark border">{{ $loan->loanAccount->account_name ?? 'N/A' }}</span></td>
                            <td class="text-end font-monospace text-secondary">Rp {{ number_format($loan->principal_amount, 0, ',', '.') }}</td>
                            <td class="text-end font-monospace fw-bold text-danger">Rp {{ number_format($loan->remaining_balance, 0, ',', '.') }}</td>
                            <td>{{ $loan->cashBankAccount->account_name ?? 'N/A' }}</td>
                            <td class="text-center">
                                @if ($loan->status == 'active')
                                    <span class="badge bg-warning text-dark rounded-pill px-3">Aktif</span>
                                @else
                                    <span class="badge bg-success rounded-pill px-3">Lunas</span>
                                @endif
                            </td>
                            <td class="text-center">
                                <div class="btn-group" role="group">
                                    <a href="{{ route('loans.show', $loan) }}" class="btn btn-sm btn-outline-dark" title="Lihat & Bayar">
                                        <i class="bi bi-eye-fill"></i>
                                    </a>
                                    
                                    @if (!$loan->payments()->exists())
                                        <a href="{{ route('loans.edit', $loan) }}" class="btn btn-sm btn-outline-primary" title="Edit">
                                            <i class="bi bi-pencil-square"></i>
                                        </a>
                                        
                                        <form action="{{ route('loans.destroy', $loan) }}" method="POST" 
                                              class="d-inline form-delete-loan" 
                                              data-loan-label="{{ $loan->lender_name }} (Rp {{ number_format($loan->principal_amount, 0, ',', '.') }})">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="btn btn-sm btn-outline-danger" title="Hapus">
                                                <i class="bi bi-trash-fill"></i>
                                            </button>
                                        </form>
                                    @endif
                                </div>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="8" class="text-center py-5 text-muted">
                                <i class="bi bi-inbox fs-1 d-block mb-2"></i>
                                Belum ada data pinjaman.
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            <div class="mt-3">
                {{ $loans->links() }}
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    
    // 1. Konfirmasi Delete
    const deleteForms = document.querySelectorAll('.form-delete-loan');
    deleteForms.forEach(form => {
        form.addEventListener('submit', function (event) {
            event.preventDefault(); 
            const loanLabel = event.target.dataset.loanLabel;
            
            Swal.fire({
                title: 'Hapus Pinjaman?',
                html: `Anda akan menghapus pinjaman:<br><b>${loanLabel}</b><br><br><small class="text-danger">Jurnal penerimaan pinjaman akan dibalik/dihapus.</small>`,
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

    // 2. Notifikasi
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
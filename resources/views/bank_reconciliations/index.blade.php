@extends('layouts.app')

@section('content')
<div class="container-fluid py-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h2 class="fw-bold mb-0 text-dark">Rekonsiliasi Bank</h2>
            <p class="text-muted small mb-0">Riwayat pencocokan saldo bank dan sistem</p>
        </div>
        <a href="{{ route('bank-reconciliations.create') }}" class="btn btn-dark shadow-sm">
            <i class="bi bi-plus-lg"></i> Mulai Rekonsiliasi Baru
        </a>
    </div>

    <div class="card shadow-sm border-0">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-dark">
                        <tr>
                            <th class="ps-4 py-3">Tgl. Statement</th>
                            <th class="py-3">Akun Bank</th>
                            <th class="text-end py-3">Saldo Bank</th>
                            <th class="text-end py-3">Saldo Sistem</th>
                            <th class="text-end py-3">Selisih</th>
                            <th class="text-center py-3">Status</th>
                            <th class="py-3">User</th>
                            <th class="text-center py-3">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($reconciliations as $recon)
                        <tr>
                            <td class="ps-4 fw-semibold">{{ $recon->statement_date->format('d/m/Y') }}</td>
                            <td>
                                <div class="fw-bold text-dark">{{ $recon->account->account_name ?? 'N/A' }}</div>
                                <small class="text-muted">{{ $recon->account->account_number ?? '' }}</small>
                            </td>
                            <td class="text-end font-monospace">Rp {{ number_format($recon->statement_balance, 0, ',', '.') }}</td>
                            <td class="text-end font-monospace">Rp {{ number_format($recon->closing_balance, 0, ',', '.') }}</td>
                            <td class="text-end font-monospace">
                                @if($recon->difference == 0)
                                    <span class="badge bg-light text-success border border-success">Rp 0</span>
                                @else
                                    <span class="text-danger fw-bold">Rp {{ number_format($recon->difference, 0, ',', '.') }}</span>
                                @endif
                            </td>
                            <td class="text-center">
                                @if ($recon->status == 'reconciled')
                                    <span class="badge bg-success rounded-pill px-3"><i class="bi bi-check-circle-fill"></i> Selesai</span>
                                @else
                                    <span class="badge bg-warning text-dark rounded-pill px-3">Draft / Proses</span>
                                @endif
                            </td>
                            <td><small>{{ $recon->user->name ?? '-' }}</small></td>
                            <td class="text-center">
                                <div class="btn-group">
                                    <a href="{{ route('bank-reconciliations.show', $recon) }}" class="btn btn-sm btn-outline-primary" title="Buka Lembar Kerja">
                                        <i class="bi bi-file-earmark-spreadsheet-fill"></i>
                                    </a>
                                    @if($recon->status == 'draft')
                                    <form action="{{ route('bank-reconciliations.destroy', $recon) }}" method="POST" class="d-inline form-delete">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="btn btn-sm btn-outline-danger" title="Hapus">
                                            <i class="bi bi-trash"></i>
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
                                Belum ada data rekonsiliasi.
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
        <div class="card-footer bg-white">
            {{ $reconciliations->links() }}
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
    document.querySelectorAll('.form-delete').forEach(form => {
        form.addEventListener('submit', function(e) {
            e.preventDefault();
            Swal.fire({
                title: 'Hapus Draft?',
                text: "Progress rekonsiliasi ini akan hilang.",
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#d33',
                confirmButtonText: 'Ya, Hapus'
            }).then((result) => {
                if (result.isConfirmed) this.submit();
            });
        });
    });
</script>
@endpush
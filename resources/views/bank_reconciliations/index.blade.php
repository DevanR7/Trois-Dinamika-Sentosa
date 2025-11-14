@extends('layouts.app')

@section('content')
<div class="container-fluid py-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2 class="fw-bold mb-0">Rekonsiliasi Bank</h2>
        <a href="{{ route('bank-reconciliations.create') }}" class="btn btn-dark">
            <i class="bi bi-plus-lg"></i> Mulai Rekonsiliasi Baru
        </a>
    </div>

    <div class="card shadow-sm">
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-striped table-hover align-middle">
                    <thead class="table-dark">
                        <tr>
                            <th>Tgl. Rekening Koran</th>
                            <th>Akun Bank</th>
                            <th class="text-end">Saldo Akhir (Bank)</th>
                            <th class="text-end">Saldo Akhir (Sistem)</th>
                            <th class="text-end">Selisih</th>
                            <th>Status</th>
                            <th>Dikerjakan Oleh</th>
                            <th>Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($reconciliations as $recon)
                        <tr>
                            <td>{{ $recon->statement_date->format('d/m/Y') }}</td>
                            <td class="fw-semibold">{{ $recon->account->account_name ?? 'N/A' }}</td>
                            <td class="text-end">Rp {{ number_format($recon->statement_balance, 0, ',', '.') }}</td>
                            <td class="text-end">Rp {{ number_format($recon->closing_balance, 0, ',', '.') }}</td>
                            <td class="text-end fw-bold {{ $recon->difference != 0 ? 'text-danger' : 'text-success' }}">
                                Rp {{ number_format($recon->difference, 0, ',', '.') }}
                            </td>
                            <td>
                                @if ($recon->status == 'reconciled')
                                    <span class="badge bg-success"><i class="bi bi-check-circle-fill"></i> Reconciled</span>
                                @else
                                    <span class="badge bg-warning text-dark">Draft</span>
                                @endif
                            </td>
                            <td>{{ $recon->user->name ?? 'N/A' }}</td>
                            <td>
                                <a href="{{ route('bank-reconciliations.show', $recon) }}" class="btn btn-sm btn-outline-dark" title="Lihat/Lanjutkan">
                                    <i class="bi bi-pencil-fill"></i>
                                </a>
                                @if($recon->status == 'draft')
                                <form action="{{ route('bank-reconciliations.destroy', $recon) }}" method="POST" class="d-inline" onsubmit="return confirm('Anda yakin ingin menghapus draft ini? Semua centang akan dilepaskan.')">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="btn btn-sm btn-outline-danger" title="Hapus Draft">
                                        <i class="bi bi-trash-fill"></i>
                                    </button>
                                </form>
                                @endif
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="8" class="text-center text-muted">Belum ada riwayat rekonsiliasi.</td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            <div class="mt-3">
                {{ $reconciliations->links() }}
            </div>
        </div>
    </div>
</div>
@endsection
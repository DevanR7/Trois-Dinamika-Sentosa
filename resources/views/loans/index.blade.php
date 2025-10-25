@extends('layouts.app')

@section('content')
<div class="container-fluid py-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2 class="fw-bold mb-0">Data Pinjaman</h2>
        <a href="{{ route('loans.create') }}" class="btn btn-dark">
            <i class="bi bi-plus-lg"></i> Tambah Pinjaman
        </a>
    </div>

    {{-- DAFTAR PINJAMAN --}}
    <div class="card shadow-sm">
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-striped table-hover align-middle">
                    <thead class="table-dark">
                        <tr>
                            <th>Tanggal Pinjam</th>
                            <th>Pemberi Pinjaman</th>
                            <th>Deskripsi</th>
                            <th class="text-end">Jumlah Pokok</th>
                            <th class="text-end">Sisa Pokok</th>
                            <th>Status</th>
                            <th>Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($loans as $loan)
                        <tr>
                            <td>{{ $loan->loan_date->format('d/m/Y') }}</td>
                            <td class="fw-semibold">{{ $loan->lender_name }}</td>
                            <td>{{ $loan->description ?? '-' }}</td>
                            <td class="text-end">Rp {{ number_format($loan->principal_amount, 0, ',', '.') }}</td>
                            <td class="text-end fw-bold">Rp {{ number_format($loan->remaining_balance, 0, ',', '.') }}</td>
                            <td>
                                @if ($loan->status == 'active')
                                    <span class="badge bg-warning">Aktif</span>
                                @else
                                    <span class="badge bg-success">Lunas</span>
                                @endif
                            </td>
                            <td>
                                {{-- Link ke Detail untuk bayar cicilan --}}
                                <a href="{{ route('loans.show', $loan) }}" class="btn btn-sm btn-outline-dark" title="Lihat Detail & Bayar">
                                    <i class="bi bi-eye-fill"></i>
                                </a>
                                {{-- Hanya bisa edit/hapus jika belum ada pembayaran --}}
                                @if (!$loan->payments()->exists())
                                    <a href="{{ route('loans.edit', $loan) }}" class="btn btn-sm btn-outline-dark" title="Edit">
                                        <i class="bi bi-pencil-fill"></i>
                                    </a>
                                    <form action="{{ route('loans.destroy', $loan) }}" method="POST" class="d-inline" onsubmit="return confirm('Anda yakin ingin menghapus data ini?')">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="btn btn-sm btn-outline-danger" title="Hapus">
                                            <i class="bi bi-trash-fill"></i>
                                        </button>
                                    </form>
                                @endif
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="7" class="text-center text-muted">Belum ada data pinjaman.</td>
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
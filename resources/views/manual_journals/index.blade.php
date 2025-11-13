@extends('layouts.app')

@section('content')
<div class="container-fluid py-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2 class="fw-bold mb-0">Jurnal Umum Manual</h2>
        <a href="{{ route('manual-journals.create') }}" class="btn btn-dark">
            <i class="bi bi-plus-lg"></i> Buat Jurnal Manual
        </a>
    </div>

    {{-- FORM FILTER --}}
    <div class="card shadow-sm mb-4">
        <div class="card-body">
            <form action="{{ route('manual-journals.index') }}" method="GET">
                <div class="row g-3">
                    <div class="col-md-10">
                        <label for="search" class="form-label">Cari (No. Jurnal / Deskripsi)</label>
                        <input type="text" name="search" id="search" class="form-control" value="{{ request('search') }}" placeholder="Cari...">
                    </div>
                    <div class="col-md-2 align-self-end">
                        <button type="submit" class="btn btn-dark w-100"><i class="bi bi-funnel-fill"></i> Filter</button>
                    </div>
                </div>
            </form>
        </div>
    </div>

    {{-- DAFTAR JURNAL --}}
    <div class="card shadow-sm">
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-striped table-hover align-middle">
                    <thead class="table-dark">
                        <tr>
                            <th>Tanggal</th>
                            <th>Nomor Jurnal</th>
                            <th>Deskripsi</th>
                            <th class="text-end">Total Debit</th>
                            <th class="text-end">Total Kredit</th>
                            <th>Dibuat Oleh</th>
                            <th>Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($manualJournals as $journal)
                        <tr>
                            <td>{{ $journal->entry_date->format('d/m/Y') }}</td>
                            <td class="fw-semibold">{{ $journal->journal_number }}</td>
                            <td>{{ Str::limit($journal->description, 70) }}</td>
                            <td class="text-end font-monospace">Rp {{ number_format($journal->total_debit, 0, ',', '.') }}</td>
                            <td class="text-end font-monospace">Rp {{ number_format($journal->total_credit, 0, ',', '.') }}</td>
                            <td>{{ $journal->user->name ?? 'N/A' }}</td>
                            <td>
                                <a href="{{ route('manual-journals.show', $journal) }}" class="btn btn-sm btn-outline-dark" title="Lihat Detail">
                                    <i class="bi bi-eye-fill"></i>
                                </a>
                                <a href="{{ route('manual-journals.edit', $journal) }}" class="btn btn-sm btn-outline-dark" title="Edit">
                                    <i class="bi bi-pencil-fill"></i>
                                </a>
                                <form action="{{ route('manual-journals.destroy', $journal) }}" method="POST" class="d-inline" onsubmit="return confirm('Anda yakin ingin menghapus (membalik) jurnal ini? Aksi ini akan membuat jurnal reversal.')">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="btn btn-sm btn-outline-danger" title="Hapus (Reversal)">
                                        <i class="bi bi-trash-fill"></i>
                                    </button>
                                </form>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="7" class="text-center text-muted">Belum ada data Jurnal Umum Manual.</td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            <div class="mt-3">
                {{ $manualJournals->links() }}
            </div>
        </div>
    </div>
</div>
@endsection
@extends('layouts.app')

@section('content')
<div class="container-fluid py-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2 class="fw-bold mb-0">Data Beban Operasional</h2>
        <a href="{{ route('expenses.create') }}" class="btn btn-dark">
            <i class="bi bi-plus-lg"></i> Tambah Pengeluaran
        </a>
    </div>

    {{-- FORM FILTER --}}
    <div class="card shadow-sm mb-4">
        <div class="card-body">
            <form action="{{ route('expenses.index') }}" method="GET">
                <div class="row g-3">
                    <div class="col-md-4">
                        <label for="search" class="form-label">Cari (Deskripsi/Kategori)</label>
                        <input type="text" name="search" id="search" class="form-control" value="{{ request('search') }}" placeholder="Cari...">
                    </div>
                    <div class="col-md-3">
                        <label for="start_date" class="form-label">Dari Tanggal</label>
                        <input type="date" name="start_date" id="start_date" class="form-control" value="{{ request('start_date') }}">
                    </div>
                    <div class="col-md-3">
                        <label for="end_date" class="form-label">Sampai Tanggal</label>
                        <input type="date" name="end_date" id="end_date" class="form-control" value="{{ request('end_date') }}">
                    </div>
                    <div class="col-md-2 align-self-end">
                        <button type="submit" class="btn btn-dark w-100"><i class="bi bi-funnel-fill"></i> Filter</button>
                    </div>
                </div>
            </form>
        </div>
    </div>

    {{-- DAFTAR PENGELUARAN --}}
    <div class="card shadow-sm">
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-striped table-hover align-middle">
                    <thead class="table-dark">
                        <tr>
                            <th>Tanggal</th>
                            <th>Kategori</th>
                            <th>Deskripsi</th>
                            <th class="text-end">Jumlah</th>
                            <th>Dicatat Oleh</th>
                            <th>Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($expenses as $expense)
                        <tr>
                            <td>{{ $expense->expense_date->format('d/m/Y') }}</td>
                            <td>{{ $expense->category }}</td>
                            <td>{{ $expense->description }}</td>
                            <td class="text-end fw-semibold">Rp {{ number_format($expense->amount, 0, ',', '.') }}</td>
                            <td>{{ $expense->user->name ?? 'N/A' }}</td>
                            <td>
                                <a href="{{ route('expenses.edit', $expense) }}" class="btn btn-sm btn-outline-dark" title="Edit">
                                    <i class="bi bi-pencil-fill"></i>
                                </a>
                                <form action="{{ route('expenses.destroy', $expense) }}" method="POST" class="d-inline" onsubmit="return confirm('Anda yakin ingin menghapus data ini?')">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="btn btn-sm btn-outline-danger" title="Hapus">
                                        <i class="bi bi-trash-fill"></i>
                                    </button>
                                </form>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="6" class="text-center text-muted">Belum ada data pengeluaran.</td>
                        </tr>
                        @endforelse
                    </tbody>
                    <tfoot>
                        <tr>
                            <td colspan="3" class="text-end fw-bold">TOTAL PENGELUARAN (Sesuai Filter)</td>
                            <td class="text-end fw-bold fs-5 text-danger">Rp {{ number_format($totalExpenses, 0, ',', '.') }}</td>
                            <td colspan="2"></td>
                        </tr>
                    </tfoot>
                </table>
            </div>

            <div class="mt-3">
                {{ $expenses->links() }}
            </div>
        </div>
    </div>
</div>
@endsection
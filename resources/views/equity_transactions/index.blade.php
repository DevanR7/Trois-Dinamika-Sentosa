@extends('layouts.app')

@section('content')
<div class="container-fluid py-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2 class="fw-bold mb-0">Transaksi Modal</h2>
        <a href="{{ route('equity-transactions.create') }}" class="btn btn-dark">
            <i class="bi bi-plus-lg"></i> Catat Transaksi
        </a>
    </div>

    {{-- FORM FILTER --}}
    <div class="card shadow-sm mb-4">
        <div class="card-body">
            <form action="{{ route('equity-transactions.index') }}" method="GET">
                <div class="row g-3 align-items-end">
                    <div class="col-md-4">
                        <label for="start_date" class="form-label">Dari Tanggal</label>
                        <input type="date" name="start_date" id="start_date" class="form-control" value="{{ request('start_date') }}">
                    </div>
                    <div class="col-md-4">
                        <label for="end_date" class="form-label">Sampai Tanggal</label>
                        <input type="date" name="end_date" id="end_date" class="form-control" value="{{ request('end_date') }}">
                    </div>
                    <div class="col-md-2">
                         <label for="type" class="form-label">Tipe Transaksi</label>
                        <select name="type" id="type" class="form-select">
                            <option value="">Semua Tipe</option>
                            <option value="investment" {{ request('type') == 'investment' ? 'selected' : '' }}>Setoran Modal</option>
                            <option value="drawing" {{ request('type') == 'drawing' ? 'selected' : '' }}>Penarikan Modal</option>
                        </select>
                    </div>
                    <div class="col-md-2">
                        <button type="submit" class="btn btn-dark w-100"><i class="bi bi-funnel-fill"></i> Filter</button>
                    </div>
                </div>
            </form>
        </div>
    </div>

    {{-- DAFTAR TRANSAKSI --}}
    <div class="card shadow-sm">
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-striped table-hover align-middle">
                    <thead class="table-dark">
                        <tr>
                            <th>Tanggal</th>
                            <th>Tipe</th>
                            <th>Deskripsi</th>
                            <th class="text-end">Jumlah</th>
                            <th>Dicatat Oleh</th>
                            <th>Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($transactions as $transaction)
                        <tr>
                            <td>{{ $transaction->transaction_date->format('d/m/Y') }}</td>
                            <td>
                                @if ($transaction->type == 'investment')
                                    <span class="badge bg-success">Setoran Modal</span>
                                @else
                                    <span class="badge bg-danger">Penarikan Modal</span>
                                @endif
                            </td>
                            <td>{{ $transaction->description }}</td>
                            <td class="text-end fw-semibold">
                                Rp {{ number_format($transaction->amount, 0, ',', '.') }}
                            </td>
                            <td>{{ $transaction->user->name ?? 'N/A' }}</td>
                            <td>
                                <a href="{{ route('equity-transactions.edit', $transaction) }}" class="btn btn-sm btn-outline-dark" title="Edit">
                                    <i class="bi bi-pencil-fill"></i>
                                </a>
                                <form action="{{ route('equity-transactions.destroy', $transaction) }}" method="POST" class="d-inline" onsubmit="return confirm('Anda yakin ingin menghapus data ini?')">
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
                            <td colspan="6" class="text-center text-muted">Belum ada data transaksi modal.</td>
                        </tr>
                        @endforelse
                    </tbody>
                    {{-- RINGKASAN TOTAL --}}
                    <tfoot class="table-group-divider">
                        <tr class="table-light">
                            <td colspan="3" class="text-end fw-bold">Total Setoran Modal (Sesuai Filter)</td>
                            <td class="text-end fw-bold text-success">Rp {{ number_format($totalInvestment, 0, ',', '.') }}</td>
                            <td colspan="2"></td>
                        </tr>
                         <tr class="table-light">
                            <td colspan="3" class="text-end fw-bold">Total Penarikan Modal (Sesuai Filter)</td>
                            <td class="text-end fw-bold text-danger">(Rp {{ number_format($totalDrawing, 0, ',', '.') }})</td>
                            <td colspan="2"></td>
                        </tr>
                         <tr class="table-dark">
                            <td colspan="3" class="text-end fw-bold fs-5">Perubahan Modal Bersih</td>
                            <td class="text-end fw-bold fs-5">
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

            <div class="mt-3">
                {{ $transactions->links() }}
            </div>
        </div>
    </div>
</div>
@endsection
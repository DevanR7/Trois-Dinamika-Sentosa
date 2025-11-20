@extends('layouts.app')

@section('content')
<div class="container-fluid py-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2 class="fw-bold mb-0">Data Beban Operasional</h2>
        <a href="{{ route('expenses.create') }}" class="btn btn-dark shadow-sm">
            <i class="bi bi-plus-lg"></i> Tambah Pengeluaran
        </a>
    </div>

    {{-- FORM FILTER --}}
    <div class="card shadow-sm mb-4 border-0">
        <div class="card-body bg-light rounded">
            <form action="{{ route('expenses.index') }}" method="GET">
                <div class="row g-3">
                    <div class="col-md-4">
                        <label for="search" class="form-label fw-bold small text-muted">Cari</label>
                        <div class="input-group">
                            <span class="input-group-text bg-white"><i class="bi bi-search"></i></span>
                            <input type="text" name="search" id="search" class="form-control" value="{{ request('search') }}" placeholder="Deskripsi atau Kategori...">
                        </div>
                    </div>
                    <div class="col-md-3">
                        <label for="start_date" class="form-label fw-bold small text-muted">Dari Tanggal</label>
                        <input type="date" name="start_date" id="start_date" class="form-control" value="{{ request('start_date') }}">
                    </div>
                    <div class="col-md-3">
                        <label for="end_date" class="form-label fw-bold small text-muted">Sampai Tanggal</label>
                        <input type="date" name="end_date" id="end_date" class="form-control" value="{{ request('end_date') }}">
                    </div>
                    <div class="col-md-2 align-self-end">
                        <button type="submit" class="btn btn-primary w-100 fw-bold"><i class="bi bi-funnel-fill"></i> Filter</button>
                    </div>
                </div>
            </form>
        </div>
    </div>

    {{-- DAFTAR PENGELUARAN --}}
    <div class="card shadow-sm border-0">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-dark">
                        <tr>
                            <th class="ps-4 py-3">Tanggal</th>
                            <th class="py-3">Kategori</th>
                            <th class="py-3">Deskripsi</th>
                            <th class="text-end py-3">Jumlah</th>
                            <th class="py-3">Dicatat Oleh</th>
                            <th class="text-center py-3">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($expenses as $expense)
                        <tr>
                            <td class="ps-4">{{ $expense->expense_date->format('d/m/Y') }}</td>
                            <td>
                                <span class="badge bg-info text-dark border border-info">
                                    {{ $expense->expenseAccount->account_name ?? $expense->category }}
                                </span>
                            </td>
                            <td>{{ Str::limit($expense->description, 50) }}</td>
                            <td class="text-end fw-bold font-monospace text-danger">
                                Rp {{ number_format($expense->amount, 0, ',', '.') }}
                            </td>
                            <td><small class="text-muted">{{ $expense->user->name ?? 'N/A' }}</small></td>
                            <td class="text-center">
                                <div class="btn-group" role="group">
                                    <a href="{{ route('expenses.edit', $expense) }}" class="btn btn-sm btn-outline-primary" title="Edit">
                                        <i class="bi bi-pencil-square"></i>
                                    </a>
                                    
                                    <form action="{{ route('expenses.destroy', $expense) }}" method="POST" 
                                          class="d-inline form-delete-expense" 
                                          data-expense-label="{{ $expense->description }} (Rp {{ number_format($expense->amount, 0, ',', '.') }})">
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
                                Belum ada data pengeluaran.
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                    <tfoot class="bg-light">
                        <tr>
                            <td colspan="3" class="text-end fw-bold py-3 text-uppercase">Total Pengeluaran</td>
                            <td class="text-end fw-bold fs-5 text-danger py-3">Rp {{ number_format($totalExpenses, 0, ',', '.') }}</td>
                            <td colspan="2"></td>
                        </tr>
                    </tfoot>
                </table>
            </div>

            @if($expenses->hasPages())
                <div class="card-footer bg-white">
                    {{ $expenses->links() }}
                </div>
            @endif
        </div>
    </div>
</div>
@endsection

@push('scripts')
{{-- CDN SweetAlert2 (Wajib ada) --}}
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<script>
document.addEventListener('DOMContentLoaded', function() {
    
    // 1. Konfirmasi Delete
    const deleteForms = document.querySelectorAll('.form-delete-expense');
    deleteForms.forEach(form => {
        form.addEventListener('submit', function (event) {
            event.preventDefault(); 
            const expenseLabel = event.target.dataset.expenseLabel;
            
            Swal.fire({
                title: 'Hapus Pengeluaran?',
                html: `Anda akan menghapus:<br><b>${expenseLabel}</b><br><br><small class="text-danger">Tindakan ini juga akan membalik jurnal akuntansi terkait.</small>`,
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

    // 2. Notifikasi Sukses/Gagal dari Controller (Session Flash)
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
@extends('layouts.app')

@section('content')
<div class="container-fluid py-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h3 class="fw-bold text-dark mb-0">Pengaturan Pajak</h3>
            <p class="text-muted small mb-0">Daftar semua tarif pajak yang berlaku.</p>
        </div>
        <a href="{{ route('taxes.create') }}" class="btn btn-primary shadow-sm">
            <i class="bi bi-plus-lg me-2"></i> Tambah Tarif
        </a>
    </div>

    <div class="card card-transaction shadow-sm border-0">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover table-transaction align-middle mb-0">
                    <thead class="bg-light">
                        <tr>
                            <th class="ps-4" style="width: 50%;">Nama Pajak</th>
                            <th>Tarif (%)</th>
                            <th class="text-center">Status</th>
                            <th class="text-center pe-4">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($taxes as $tax)
                            <tr>
                                <td class="ps-4 fw-bold text-dark">{{ $tax->name }}</td>
                                <td>
                                    <span class="fs-6 fw-bold text-primary">{{ number_format($tax->rate, 2, ',', '.') }}%</span>
                                </td>
                                <td class="text-center">
                                    @if ($tax->is_active)
                                        <span class="badge bg-success bg-opacity-10 text-success border border-success border-opacity-25 rounded-pill">Aktif</span>
                                    @else
                                        <span class="badge bg-secondary bg-opacity-10 text-secondary border border-secondary border-opacity-25 rounded-pill">Tidak Aktif</span>
                                    @endif
                                </td>
                                <td class="text-center pe-4">
                                    <a href="{{ route('taxes.edit', $tax->id) }}" class="btn btn-sm btn-light border text-warning shadow-sm" title="Edit" style="width: 32px; height: 32px; display: flex; align-items: center; justify-content: center; margin: 0 auto;">
                                        <i class="bi bi-pencil-square"></i>
                                    </a>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="4" class="text-center py-5 text-muted">
                                    <i class="bi bi-percent fs-1 d-block mb-2 opacity-50"></i>
                                    Belum ada data tarif pajak.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
@endsection
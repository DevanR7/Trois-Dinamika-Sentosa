@extends('layouts.app')

@section('content')
<div class="container-fluid py-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2 class="fw-bold mb-0">Pengaturan Metode Pembayaran</h2>
        <a href="{{ route('payment-methods.create') }}" class="btn btn-primary">
            <i class="bi bi-plus-circle me-2"></i> Tambah Metode Baru
        </a>
    </div>

    <div class="card shadow-sm border-0">
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-hover align-middle">
                    <thead class="table-light">
                        <tr>
                            <th>Nama Metode</th>
                            <th>Tipe</th>
                            <th class="text-center">Status</th>
                            <th class="text-end">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($paymentMethods as $method)
                            <tr>
                                <td class="fw-semibold">{{ $method->name }}</td>
                                <td>
                                    @if($method->type == 'direct')
                                        <span class="badge bg-success">Direct (Langsung)</span>
                                    @elseif($method->type == 'pending')
                                        <span class="badge bg-warning text-dark">Pending (Giro/Cek)</span>
                                    @elseif($method->type == 'gateway')
                                        <span class="badge bg-info text-dark">Gateway (Midtrans)</span>
                                    @endif
                                </td>
                                <td class="text-center">
                                    @if($method->is_active)
                                        <span class="badge bg-success">Aktif</span>
                                    @else
                                        <span class="badge bg-secondary">Tidak Aktif</span>
                                    @endif
                                </td>
                                <td class="text-end">
                                    <a href="{{ route('payment-methods.edit', $method->payment_method_id) }}" class="btn btn-sm btn-outline-primary">
                                        <i class="bi bi-pencil-fill"></i> Edit
                                    </a>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="4" class="text-center text-muted">Belum ada metode pembayaran yang dibuat.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
@endsection
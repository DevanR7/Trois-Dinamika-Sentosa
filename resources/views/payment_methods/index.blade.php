@extends('layouts.app')

@section('content')
<div class="container-fluid py-4">
    {{-- HEADER HALAMAN --}}
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h3 class="fw-bold text-dark mb-0">Metode Pembayaran</h3>
            <p class="text-muted small mb-0">Atur cara pembayaran yang diterima (Transfer, Cash, Giro, dll)</p>
        </div>
        <div>
            <a href="{{ route('payment-methods.archived.index') }}" class="btn btn-outline-secondary btn-sm me-2 shadow-sm">
                <i class="bi bi-archive me-1"></i> Lihat Arsip
            </a>
            <a href="{{ route('payment-methods.create') }}" class="btn btn-primary btn-sm shadow-sm">
                <i class="bi bi-plus-lg me-1"></i> Tambah Metode
            </a>
        </div>
    </div>

    {{-- NOTIFIKASI --}}
    @if (session('success'))
        <div class="alert alert-success border-0 shadow-sm d-flex align-items-center mb-4">
            <i class="bi bi-check-circle-fill fs-4 me-2"></i>
            <div>{{ session('success') }}</div>
        </div>
    @endif
    @if (session('error'))
        <div class="alert alert-danger border-0 shadow-sm d-flex align-items-center mb-4">
            <i class="bi bi-exclamation-triangle-fill fs-4 me-2"></i>
            <div>{{ session('error') }}</div>
        </div>
    @endif

    {{-- TABEL DATA --}}
    <div class="card card-transaction border-0 shadow-sm">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover table-transaction align-middle mb-0">
                    <thead class="bg-light">
                        <tr>
                            <th class="ps-4">Nama Metode</th>
                            <th>Tipe</th>
                            <th>Konfigurasi</th>
                            <th class="text-center">Status</th>
                            <th class="text-end pe-4">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($paymentMethods as $method)
                            <tr>
                                <td class="ps-4 fw-bold text-dark">{{ $method->name }}</td>
                                <td>
                                    @if($method->type == 'direct') <span class="badge bg-success bg-opacity-10 text-success border border-success border-opacity-25">Langsung (Direct)</span>
                                    @elseif($method->type == 'pending') <span class="badge bg-warning bg-opacity-10 text-warning border border-warning border-opacity-25 text-dark">Tertunda (Pending)</span>
                                    @elseif($method->type == 'gateway') <span class="badge bg-info bg-opacity-10 text-info border border-info border-opacity-25">Gateway</span>
                                    @else <span class="badge bg-secondary">{{ $method->type }}</span>
                                    @endif
                                </td>
                                <td>
                                    @if($method->required_fields_config == 'none')
                                        <span class="text-muted small">Standar</span>
                                    @elseif($method->required_fields_config == 'proof_only')
                                        <span class="badge bg-light text-dark border"><i class="bi bi-image"></i> Bukti Foto</span>
                                    @elseif($method->required_fields_config == 'reference_only')
                                        <span class="badge bg-light text-dark border"><i class="bi bi-hash"></i> No. Ref</span>
                                    @elseif($method->required_fields_config == 'proof_and_reference')
                                        <span class="badge bg-light text-dark border"><i class="bi bi-image"></i> Foto</span> + <span class="badge bg-light text-dark border"><i class="bi bi-hash"></i> Ref</span>
                                    @endif
                                </td>
                                <td class="text-center">
                                    @if($method->is_active)
                                        <span class="badge bg-success rounded-pill">Aktif</span>
                                    @else
                                        <span class="badge bg-secondary rounded-pill">Non-Aktif</span>
                                    @endif
                                </td>
                                <td class="text-end pe-4">
                                    <a href="{{ route('payment-methods.edit', $method->payment_method_id) }}" class="btn btn-sm btn-outline-warning shadow-sm me-1" title="Edit">
                                        <i class="bi bi-pencil-square"></i>
                                    </a>
                                    
                                    <form action="{{ route('payment-methods.destroy', $method->payment_method_id) }}" method="POST" class="d-inline form-archive">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="btn btn-sm btn-outline-danger shadow-sm" title="Arsipkan">
                                            <i class="bi bi-archive"></i>
                                        </button>
                                    </form>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="text-center py-5 text-muted">
                                    <i class="bi bi-wallet2 fs-1 d-block mb-2 opacity-25"></i>
                                    Belum ada metode pembayaran yang ditambahkan.
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

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    document.querySelectorAll('.form-archive').forEach(form => {
        form.addEventListener('submit', function(event) {
            event.preventDefault();
            Swal.fire({
                title: 'Arsipkan Metode?',
                text: "Metode ini akan disembunyikan dari pilihan pembayaran, tapi data historis tetap aman.",
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#d33',
                cancelButtonColor: '#6c757d',
                confirmButtonText: 'Ya, Arsipkan!',
                cancelButtonText: 'Batal'
            }).then((result) => {
                if (result.isConfirmed) event.target.submit();
            });
        });
    });
});
</script>
@endpush
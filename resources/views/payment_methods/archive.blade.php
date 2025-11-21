@extends('layouts.app')

@section('content')
<div class="container-fluid py-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h3 class="fw-bold text-dark mb-0">Arsip Metode Pembayaran</h3>
            <p class="text-muted small mb-0">Daftar metode yang dinonaktifkan/dihapus sementara</p>
        </div>
        <a href="{{ route('payment-methods.index') }}" class="btn btn-outline-secondary btn-sm shadow-sm">
            <i class="bi bi-arrow-left me-1"></i> Kembali ke Daftar Aktif
        </a>
    </div>

    @if (session('success'))
        <div class="alert alert-success border-0 shadow-sm mb-4">{{ session('success') }}</div>
    @endif

    <div class="card card-transaction border-0 shadow-sm">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover table-transaction align-middle mb-0">
                    <thead class="bg-light">
                        <tr>
                            <th class="ps-4">Nama Metode</th>
                            <th>Tipe</th>
                            <th>Tanggal Arsip</th>
                            <th class="text-end pe-4">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($archivedMethods as $method)
                            <tr>
                                <td class="ps-4 text-muted">{{ $method->name }}</td>
                                <td><span class="badge bg-secondary bg-opacity-10 text-secondary border">{{ Str::title($method->type) }}</span></td>
                                <td class="small text-muted">{{ $method->deleted_at->format('d M Y H:i') }}</td>
                                <td class="text-end pe-4">
                                    <form action="{{ route('payment-methods.archived.restore', $method->payment_method_id) }}" method="POST" class="d-inline form-restore">
                                        @csrf
                                        <button type="submit" class="btn btn-sm btn-success shadow-sm me-1" title="Pulihkan">
                                            <i class="bi bi-arrow-counterclockwise"></i> Pulihkan
                                        </button>
                                    </form>
                                    
                                    <form action="{{ route('payment-methods.archived.forceDelete', $method->payment_method_id) }}" method="POST" class="d-inline form-force-delete">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="btn btn-sm btn-danger shadow-sm" title="Hapus Permanen">
                                            <i class="bi bi-trash"></i>
                                        </button>
                                    </form>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="4" class="text-center py-5 text-muted">
                                    <i class="bi bi-archive fs-1 d-block mb-2 opacity-25"></i>
                                    Tidak ada data arsip.
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
    function confirmAction(selector, title, text, btnColor, btnText) {
        document.querySelectorAll(selector).forEach(form => {
            form.addEventListener('submit', function(e) {
                e.preventDefault();
                Swal.fire({
                    title: title, text: text, icon: 'warning', showCancelButton: true,
                    confirmButtonColor: btnColor, cancelButtonColor: '#6c757d',
                    confirmButtonText: btnText, cancelButtonText: 'Batal'
                }).then((result) => { if (result.isConfirmed) this.submit(); });
            });
        });
    }

    confirmAction('.form-restore', 'Pulihkan Metode?', 'Metode ini akan kembali aktif dan bisa digunakan.', '#198754', 'Ya, Pulihkan!');
    confirmAction('.form-force-delete', 'Hapus Permanen?', 'Data akan hilang selamanya dan tidak bisa dikembalikan!', '#d33', 'Ya, Hapus!');
});
</script>
@endpush
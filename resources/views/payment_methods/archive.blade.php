@extends('layouts.app')

@section('content')
<div class="container-fluid py-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2 class="fw-bold mb-0">Arsip Metode Pembayaran</h2>
        <a href="{{ route('payment-methods.index') }}" class="btn btn-outline-secondary">
            <i class="bi bi-arrow-left me-1"></i> Kembali ke Daftar Aktif
        </a>
    </div>

    {{-- Notifikasi (ini sudah ada di layout Anda) --}}
    @if (session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif
    @if (session('error'))
        <div class="alert alert-danger">{{ session('error') }}</div>
    @endif

    <div class="card shadow-sm border-0">
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-hover align-middle">
                    <thead class="table-light">
                        <tr>
                            <th>Nama Metode</th>
                            <th>Tipe</th>
                            <th>Tanggal Arsip</th>
                            <th class="text-end">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($archivedMethods as $method)
                            <tr>
                                <td>{{ $method->name }}</td>
                                <td>{{ Str::title($method->type) }}</td>
                                <td>{{ $method->deleted_at->format('d M Y H:i') }}</td>
                                <td class="text-end">
                                    {{-- Tombol Pulihkan --}}
                                    <form action="{{ route('payment-methods.archived.restore', $method->payment_method_id) }}" method="POST" class="d-inline form-restore">
                                        @csrf
                                        <button type="submit" class="btn btn-sm btn-success">
                                            <i class="bi bi-arrow-counterclockwise"></i> Pulihkan
                                        </button>
                                    </form>
                                    {{-- Tombol Hapus Permanen --}}
                                    <form action="{{ route('payment-methods.archived.forceDelete', $method->payment_method_id) }}" method="POST" class="d-inline form-force-delete">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="btn btn-sm btn-danger">
                                            <i class="bi bi-trash3-fill"></i> Hapus Permanen
                                        </button>
                                    </form>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="4" class="text-center text-muted py-4">
                                    Tidak ada data metode pembayaran yang diarsip.
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
{{-- SweetAlert sudah ada di layout utama, tapi kita tambahkan listener-nya --}}
<script>
document.addEventListener('DOMContentLoaded', function() {
    // ✅ SCRIPT BARU: SweetAlert untuk Konfirmasi Pulihkan
    document.querySelectorAll('.form-restore').forEach(form => {
        form.addEventListener('submit', function(event) {
            event.preventDefault();
            Swal.fire({
                title: 'Pulihkan Metode?',
                text: "Metode pembayaran ini akan kembali aktif dan bisa digunakan.",
                icon: 'question',
                showCancelButton: true,
                confirmButtonColor: '#198754', // Hijau
                cancelButtonColor: '#6c757d',
                confirmButtonText: 'Ya, Pulihkan!',
                cancelButtonText: 'Batal'
            }).then((result) => {
                if (result.isConfirmed) {
                    event.target.submit();
                }
            });
        });
    });

    // ✅ SCRIPT BARU: SweetAlert untuk Konfirmasi Hapus Permanen
    document.querySelectorAll('.form-force-delete').forEach(form => {
        form.addEventListener('submit', function(event) {
            event.preventDefault();
            Swal.fire({
                title: 'HAPUS PERMANEN?',
                text: "Anda YAKIN? Aksi ini tidak dapat dibatalkan. Metode akan hilang selamanya.",
                icon: 'error',
                showCancelButton: true,
                confirmButtonColor: '#d33', // Merah
                cancelButtonColor: '#6c757d',
                confirmButtonText: 'Ya, Hapus Permanen!',
                cancelButtonText: 'Batal'
            }).then((result) => {
                if (result.isConfirmed) {
                    event.target.submit();
                }
            });
        });
    });
});
</script>
@endpush
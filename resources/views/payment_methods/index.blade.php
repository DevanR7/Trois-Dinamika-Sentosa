@extends('layouts.app')

@section('content')
<div class="container-fluid py-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2 class="fw-bold mb-0">Daftar Metode Pembayaran</h2>
        <div>
            {{-- ✅ TOMBOL BARU: Link ke halaman Arsip --}}
            <a href="{{ route('payment-methods.archived.index') }}" class="btn btn-outline-secondary">
                <i class="bi bi-archive me-1"></i> Lihat Arsip
            </a>
            <a href="{{ route('payment-methods.create') }}" class="btn btn-primary">
                <i class="bi bi-plus-circle me-1"></i> Tambah Metode Baru
            </a>
        </div>
    </div>

    {{-- Notifikasi (ini sudah ada di layout Anda, tapi tidak apa-apa didobel untuk file ini) --}}
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
                            <th class="text-center">Status</th>
                            <th class="text-end">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($paymentMethods as $method)
                            <tr>
                                <td>{{ $method->name }}</td>
                                <td>{{ Str::title($method->type) }}</td>
                                <td class="text-center">
                                    @if($method->is_active)
                                        <span class="badge bg-success">Aktif</span>
                                    @else
                                        <span class="badge bg-secondary">Tidak Aktif</span>
                                    @endif
                                </td>
                                <td class="text-end">
                                    <a href="{{ route('payment-methods.edit', $method->payment_method_id) }}" class="btn btn-sm btn-warning">
                                        <i class="bi bi-pencil-square"></i> Edit
                                    </a>
                                    
                                    {{-- ✅ PERUBAHAN: Form Hapus menjadi Form Arsip --}}
                                    <form action="{{ route('payment-methods.destroy', $method->payment_method_id) }}" method="POST" class="d-inline form-archive">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="btn btn-sm btn-outline-danger">
                                            <i class="bi bi-archive"></i> Arsip
                                        </button>
                                    </form>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="4" class="text-center text-muted py-4">
                                    Belum ada data metode pembayaran.
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
    // ✅ SCRIPT BARU: SweetAlert untuk Konfirmasi Arsip
    document.querySelectorAll('.form-archive').forEach(form => {
        form.addEventListener('submit', function(event) {
            event.preventDefault(); // Hentikan form submit
            
            Swal.fire({
                title: 'Anda Yakin?',
                text: "Anda akan mengarsipkan metode pembayaran ini. Anda bisa memulihkannya nanti.",
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#d33', // Merah
                cancelButtonColor: '#6c757d',
                confirmButtonText: 'Ya, Arsipkan!',
                cancelButtonText: 'Batal'
            }).then((result) => {
                if (result.isConfirmed) {
                    event.target.submit(); // Lanjutkan submit jika dikonfirmasi
                }
            });
        });
    });
});
</script>
@endpush
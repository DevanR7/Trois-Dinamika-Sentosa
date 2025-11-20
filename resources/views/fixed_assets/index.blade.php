@extends('layouts.app')

@section('content')
<div class="container-fluid py-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2 class="fw-bold mb-0 text-dark">Data Aset Tetap</h2>
        <a href="{{ route('fixed-assets.create') }}" class="btn btn-dark shadow-sm">
            <i class="bi bi-plus-lg"></i> Tambah Aset Tetap
        </a>
    </div>
    
    {{-- FORM FILTER --}}
    <div class="card shadow-sm mb-4 border-0">
        <div class="card-body bg-light rounded">
            <form action="{{ route('fixed-assets.index') }}" method="GET">
                <div class="row g-3">
                    <div class="col-md-10">
                        <label for="search" class="form-label fw-bold small text-muted">Cari Nama Aset</label>
                        <div class="input-group">
                            <span class="input-group-text bg-white border-end-0"><i class="bi bi-search"></i></span>
                            <input type="text" name="search" id="search" class="form-control border-start-0" value="{{ request('search') }}" placeholder="Contoh: Laptop, Mobil, dll...">
                        </div>
                    </div>
                    <div class="col-md-2 align-self-end">
                        <button type="submit" class="btn btn-primary w-100 fw-bold"><i class="bi bi-funnel-fill"></i> Filter</button>
                    </div>
                </div>
            </form>
        </div>
    </div>

    {{-- DAFTAR ASET --}}
    <div class="card shadow-sm border-0">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-dark">
                        <tr>
                            <th class="ps-4 py-3">Nama Aset</th>
                            <th class="py-3">Tanggal Beli</th>
                            <th class="py-3">Masa Manfaat</th>
                            <th class="text-end py-3">Harga Beli</th>
                            <th class="text-end py-3">Nilai Buku Saat Ini</th>
                            <th class="py-3">Akun Aset</th>
                            <th class="text-center py-3">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($fixedAssets as $asset)
                        <tr>
                            <td class="ps-4 fw-semibold">{{ $asset->asset_name }}</td>
                            <td>{{ $asset->purchase_date->format('d/m/Y') }}</td>
                            <td>{{ $asset->useful_life_months ?? 'N/A' }} bln</td>
                            <td class="text-end font-monospace text-secondary">Rp {{ number_format($asset->purchase_cost, 0, ',', '.') }}</td>
                            <td class="text-end font-monospace fw-bold text-primary">Rp {{ number_format($asset->current_book_value, 0, ',', '.') }}</td>
                            <td><span class="badge bg-light text-dark border">{{ $asset->assetAccount->account_name ?? 'N/A' }}</span></td>
                            <td class="text-center">
                                <div class="btn-group" role="group">
                                    <a href="{{ route('fixed-assets.edit', $asset) }}" class="btn btn-sm btn-outline-primary" title="Edit">
                                        <i class="bi bi-pencil-square"></i>
                                    </a>
                                    
                                    <form action="{{ route('fixed-assets.destroy', $asset) }}" method="POST" 
                                          class="d-inline form-delete-asset" 
                                          data-asset-name="{{ $asset->asset_name }}"
                                          data-has-depreciation="{{ $asset->depreciations()->exists() }}">
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
                            <td colspan="7" class="text-center py-5 text-muted">
                                <i class="bi bi-inbox fs-1 d-block mb-2"></i>
                                Belum ada data aset tetap.
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            @if($fixedAssets->hasPages())
                <div class="card-footer bg-white">
                    {{ $fixedAssets->links() }}
                </div>
            @endif
        </div>
    </div>
</div>
@endsection

@push('scripts')
{{-- CDN SweetAlert2 --}}
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<script>
document.addEventListener('DOMContentLoaded', function() {
    
    // 1. Konfirmasi Delete
    const deleteForms = document.querySelectorAll('.form-delete-asset');
    deleteForms.forEach(form => {
        form.addEventListener('submit', function (event) {
            event.preventDefault(); 
            const assetName = event.target.dataset.assetName;
            const hasDepreciation = event.target.dataset.hasDepreciation;

            if(hasDepreciation) {
                Swal.fire({
                    icon: 'error',
                    title: 'Tidak Bisa Dihapus',
                    text: 'Aset ini sudah memiliki riwayat penyusutan. Silakan hapus jurnal penyusutan terlebih dahulu.'
                });
                return;
            }
            
            Swal.fire({
                title: 'Hapus Aset?',
                html: `Anda akan menghapus:<br><b>${assetName}</b><br><br><small class="text-danger">Tindakan ini juga akan membalik jurnal pembelian aset.</small>`,
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

    // 2. Notifikasi Sukses/Gagal dari Controller
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
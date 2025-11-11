@extends('layouts.app')

@section('content')
<div class="container-fluid py-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2 class="fw-bold mb-0">Data Aset Tetap</h2>
        <a href="{{ route('fixed-assets.create') }}" class="btn btn-dark">
            <i class="bi bi-plus-lg"></i> Tambah Aset Tetap
        </a>
    </div>

    {{-- FORM FILTER --}}
    <div class="card shadow-sm mb-4">
        <div class="card-body">
            <form action="{{ route('fixed-assets.index') }}" method="GET">
                <div class="row g-3">
                    <div class="col-md-10">
                        <label for="search" class="form-label">Cari Nama Aset</label>
                        <input type="text" name="search" id="search" class="form-control" value="{{ request('search') }}" placeholder="Contoh: Laptop, Mobil, dll...">
                    </div>
                    <div class="col-md-2 align-self-end">
                        <button type="submit" class="btn btn-dark w-100"><i class="bi bi-funnel-fill"></i> Filter</button>
                    </div>
                </div>
            </form>
        </div>
    </div>

    {{-- DAFTAR ASET --}}
    <div class="card shadow-sm">
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-striped table-hover align-middle">
                    <thead class="table-dark">
                        <tr>
                            <th>Nama Aset</th>
                            <th>Tanggal Beli</th>
                            <th>Akun Aset</th>
                            <th class="text-end">Harga Beli</th>
                            <th>Sumber Dana</th>
                            <th>Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($fixedAssets as $asset)
                        <tr>
                            <td class="fw-semibold">{{ $asset->asset_name }}</td>
                            <td>{{ $asset->purchase_date->format('d/m/Y') }}</td>
                            <td>{{ $asset->assetAccount->account_name ?? 'N/A' }}</td>
                            <td class="text-end">Rp {{ number_format($asset->purchase_cost, 0, ',', '.') }}</td>
                            <td>{{ $asset->cashBankAccount->account_name ?? 'N/A' }}</td>
                            <td>
                                <a href="{{ route('fixed-assets.edit', $asset) }}" class="btn btn-sm btn-outline-dark" title="Edit">
                                    <i class="bi bi-pencil-fill"></i>
                                </a>
                                
                                {{-- ✅ MODIFIKASI: Hapus onsubmit, tambah class & data attribute --}}
                                <form action="{{ route('fixed-assets.destroy', $asset) }}" method="POST" 
                                      class="d-inline form-delete-asset" 
                                      data-asset-name="{{ $asset->asset_name }}">
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
                            <td colspan="6" class="text-center text-muted">Belum ada data aset tetap.</td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <div class="mt-3">
                {{ $fixedAssets->links() }}
            </div>
        </div>
    </div>
</div>
@endsection

{{-- ✅ MODIFIKASI: Tambah script SweetAlert --}}
@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    const deleteForms = document.querySelectorAll('.form-delete-asset');
    
    deleteForms.forEach(form => {
        form.addEventListener('submit', function (event) {
            event.preventDefault(); 
            
            // Ambil nama aset dari data attribute
            const assetName = event.target.dataset.assetName;
            const warningText = `Anda yakin ingin menghapus aset: "${assetName}"? Jurnal pembelian yang terkait akan dibalik.`;

            Swal.fire({
                title: 'Anda Yakin?',
                text: warningText,
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#d33',
                cancelButtonColor: '#6c757d',
                confirmButtonText: 'Ya, Hapus!',
                cancelButtonText: 'Batal'
            }).then((result) => {
                if (result.isConfirmed) {
                    // Jika dikonfirmasi, submit form-nya
                    event.target.submit();
                }
            });
        });
    });
});
</script>
@endpush
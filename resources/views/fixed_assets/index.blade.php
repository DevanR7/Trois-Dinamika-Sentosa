@extends('layouts.app')

@section('title', 'Data Aset Tetap')

@section('content')
<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-6">
    
    {{-- HEADER --}}
    <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center mb-6">
        <div>
            <h3 class="text-2xl font-bold text-gray-900 tracking-tight">Data Aset Tetap</h3>
            <p class="text-sm text-gray-500 mt-1">Kelola daftar aset dan penyusutan otomatis.</p>
        </div>
        <div class="mt-4 sm:mt-0">
            <a href="{{ route('fixed-assets.create') }}" class="inline-flex items-center px-4 py-2 bg-indigo-600 border border-transparent rounded-lg font-bold text-xs text-white uppercase tracking-widest hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 shadow-sm transition">
                <i class="material-icons text-base mr-2">add</i> Tambah Aset
            </a>
        </div>
    </div>

    {{-- FILTER FORM --}}
    <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-5 mb-6">
        <form action="{{ route('fixed-assets.index') }}" method="GET">
            <div class="flex flex-col md:flex-row gap-4 items-end">
                <div class="flex-grow w-full">
                    <label for="search" class="block text-xs font-bold text-gray-500 uppercase mb-1">Cari Nama Aset</label>
                    <div class="relative rounded-md shadow-sm">
                        <div class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3">
                            <i class="material-icons text-gray-400 text-sm">search</i>
                        </div>
                        <input type="text" name="search" id="search" value="{{ request('search') }}" class="form-input block w-full rounded-lg border-gray-300 pl-10 focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm" placeholder="Contoh: Laptop, Mobil...">
                    </div>
                </div>
                <div class="w-full md:w-auto">
                    <button type="submit" class="w-full inline-flex justify-center items-center px-4 py-2 border border-transparent rounded-lg shadow-sm text-sm font-medium text-white bg-gray-800 hover:bg-gray-900 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-gray-900 transition">
                        <i class="material-icons text-sm mr-2">filter_alt</i> Filter
                    </button>
                </div>
            </div>
        </form>
    </div>

    {{-- TABEL ASET --}}
    <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-bold text-gray-500 uppercase tracking-wider">Nama Aset</th>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-bold text-gray-500 uppercase tracking-wider">Tgl Beli</th>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-bold text-gray-500 uppercase tracking-wider">Masa Manfaat</th>
                        <th scope="col" class="px-6 py-3 text-right text-xs font-bold text-gray-500 uppercase tracking-wider">Harga Beli</th>
                        <th scope="col" class="px-6 py-3 text-right text-xs font-bold text-gray-500 uppercase tracking-wider">Nilai Buku</th>
                        <th scope="col" class="px-6 py-3 text-center text-xs font-bold text-gray-500 uppercase tracking-wider">Akun</th>
                        <th scope="col" class="px-6 py-3 text-center text-xs font-bold text-gray-500 uppercase tracking-wider w-32">Aksi</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    @forelse ($fixedAssets as $asset)
                        <tr class="hover:bg-gray-50 transition-colors group">
                            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900 group-hover:text-indigo-600 transition-colors">
                                {{ $asset->asset_name }}
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                {{ $asset->purchase_date->format('d M Y') }}
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                {{ $asset->useful_life_months ?? 'N/A' }} bln
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 text-right font-mono">
                                Rp {{ number_format($asset->purchase_cost, 0, ',', '.') }}
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-right font-bold text-green-600 font-mono">
                                Rp {{ number_format($asset->current_book_value, 0, ',', '.') }}
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-center">
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-gray-100 text-gray-800 border border-gray-200">
                                    {{ $asset->assetAccount->account_name ?? 'N/A' }}
                                </span>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-center text-sm font-medium">
                                <div class="flex justify-center gap-2">
                                    <a href="{{ route('fixed-assets.edit', $asset) }}" class="p-1.5 bg-white text-yellow-600 rounded-lg hover:bg-yellow-50 transition border border-gray-200 shadow-sm hover:border-yellow-200" title="Edit">
                                        <i class="material-icons text-lg leading-none">edit</i>
                                    </a>
                                    
                                    <form action="{{ route('fixed-assets.destroy', $asset) }}" method="POST" 
                                          class="d-inline form-delete-asset" 
                                          data-asset-name="{{ $asset->asset_name }}"
                                          data-has-depreciation="{{ $asset->depreciations()->exists() }}">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="p-1.5 bg-white text-red-600 rounded-lg hover:bg-red-50 transition border border-gray-200 shadow-sm hover:border-red-200" title="Hapus">
                                            <i class="material-icons text-lg leading-none">delete</i>
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="px-6 py-12 text-center text-gray-500">
                                <div class="flex flex-col items-center justify-center">
                                    <i class="material-icons text-4xl text-gray-300 mb-3">inventory</i>
                                    <p class="text-base font-medium">Belum ada data aset.</p>
                                    <p class="text-sm mt-1">Silakan tambahkan aset tetap baru.</p>
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($fixedAssets->hasPages())
            <div class="px-6 py-4 border-t border-gray-200 bg-gray-50">
                {{ $fixedAssets->links() }}
            </div>
        @endif
    </div>
</div>
@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Konfirmasi Delete
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
                    text: 'Aset ini sudah memiliki riwayat penyusutan. Silakan hapus jurnal penyusutan terlebih dahulu.',
                    confirmButtonColor: '#6366f1'
                });
                return;
            }
            
            Swal.fire({
                title: 'Hapus Aset?',
                html: `Anda akan menghapus:<br><b>${assetName}</b><br><br><span class="text-red-500 text-xs">Tindakan ini juga akan membalik jurnal pembelian aset.</span>`,
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#dc2626',
                cancelButtonColor: '#6b7280',
                confirmButtonText: 'Ya, Hapus!',
                cancelButtonText: 'Batal'
            }).then((result) => {
                if (result.isConfirmed) {
                    event.target.submit();
                }
            });
        });
    });

    // Notifikasi
    @if(session('success'))
        Swal.fire({ toast: true, position: 'top-end', icon: 'success', title: '{{ session('success') }}', showConfirmButton: false, timer: 3000 });
    @endif
    @if(session('error'))
        Swal.fire({ toast: true, position: 'top-end', icon: 'error', title: '{{ session('error') }}' });
    @endif
});
</script>
@endpush
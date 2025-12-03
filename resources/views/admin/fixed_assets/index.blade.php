@extends('admin.layouts.app')

@section('title', 'Data Aset Tetap')

@section('content')
<div class="max-w-7xl mx-auto pb-20 animate-enter">
    
    {{-- HEADER --}}
    <div class="flex flex-col sm:flex-row justify-between items-end gap-4 mb-8">
        <div>
            <h1 class="text-2xl font-bold text-slate-800 tracking-tight">Data Aset Tetap</h1>
            <p class="text-slate-500 text-sm mt-1">Kelola daftar aset dan penyusutan otomatis.</p>
        </div>
        <a href="{{ route('admin.fixed-assets.create') }}" class="h-[48px] px-8 bg-indigo-600 hover:bg-indigo-700 text-white text-sm font-bold rounded-lg shadow-md transition-all flex items-center justify-center gap-2 group hover:-translate-y-0.5">
            <i class="material-icons text-[20px] group-hover:rotate-90 transition-transform">add</i> 
            <span>Tambah Aset</span>
        </a>
    </div>

    {{-- FILTER --}}
    <div class="dashboard-card p-6 mb-6 shadow-sm">
        <form action="{{ route('admin.fixed-assets.index') }}" method="GET">
            <div class="flex flex-col md:flex-row gap-4 items-end">
                <div class="flex-grow w-full">
                    <label for="search" class="block text-xs font-bold text-slate-500 uppercase mb-1.5">Pencarian</label>
                    <div class="relative">
                        <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                            <i class="material-icons text-slate-400 text-[20px]">search</i>
                        </div>
                        <input type="text" name="search" id="search" value="{{ request('search') }}" 
                            class="form-input pl-10" 
                            placeholder="Cari Nama Aset...">
                    </div>
                </div>
                <div class="w-full md:w-auto">
                    <button type="submit" class="w-full md:w-auto h-[48px] px-6 bg-slate-800 hover:bg-slate-900 text-white rounded-lg font-bold text-sm shadow-sm transition flex items-center justify-center gap-2">
                        <i class="material-icons text-[18px]">filter_list</i> Filter
                    </button>
                </div>
            </div>
        </form>
    </div>

    {{-- TABEL --}}
    <div class="dashboard-card p-0 overflow-hidden shadow-lg border-0 ring-1 ring-slate-900/5">
        <div class="overflow-x-auto">
            <table class="dashboard-table min-w-full">
                <thead class="bg-slate-50 border-b border-slate-200">
                    <tr>
                        <th class="pl-6">Nama Aset</th>
                        <th>Tgl Beli</th>
                        <th>Masa Manfaat</th>
                        <th class="text-right">Harga Beli</th>
                        <th class="text-right">Nilai Buku</th>
                        <th class="text-center">Akun</th>
                        <th class="text-center w-32 pr-6">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 bg-white">
                    @forelse ($fixedAssets as $asset)
                        <tr class="hover:bg-slate-50/50 transition-colors group">
                            <td class="pl-6 py-4 text-sm font-bold text-slate-800 group-hover:text-indigo-600 transition-colors">
                                {{ $asset->asset_name }}
                            </td>
                            <td class="py-4 text-sm text-slate-600">
                                {{ $asset->purchase_date->format('d M Y') }}
                            </td>
                            <td class="py-4 text-sm text-slate-600">
                                {{ $asset->useful_life_months ?? 'N/A' }} bln
                            </td>
                            <td class="py-4 text-right text-sm text-slate-500 font-mono">
                                Rp {{ number_format($asset->purchase_cost, 0, ',', '.') }}
                            </td>
                            <td class="py-4 text-right text-sm font-bold text-emerald-600 font-mono">
                                Rp {{ number_format($asset->current_book_value, 0, ',', '.') }}
                            </td>
                            <td class="py-4 text-center">
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded text-[10px] font-bold bg-slate-100 text-slate-600 border border-slate-200 uppercase tracking-wide">
                                    {{ $asset->assetAccount->account_name ?? 'N/A' }}
                                </span>
                            </td>
                            <td class="pr-6 py-4 text-center">
                                <div class="flex justify-center items-center gap-2">
                                    <a href="{{ route('admin.fixed-assets.edit', $asset) }}" class="w-8 h-8 flex items-center justify-center rounded-lg bg-white border border-slate-200 text-amber-600 hover:bg-amber-50 hover:border-amber-200 transition shadow-sm" title="Edit">
                                        <i class="material-icons text-[16px]">edit</i>
                                    </a>
                                    
                                    <form action="{{ route('admin.fixed-assets.destroy', $asset) }}" method="POST" 
                                          class="delete-form inline-block" 
                                          data-name="{{ $asset->asset_name }}"
                                          {{-- Custom logic jika ingin menambah peringatan depresiasi, tapi delete-form standar sudah cukup aman jika backend handle --}}
                                          >
                                        @csrf @method('DELETE')
                                        <button type="submit" class="w-8 h-8 flex items-center justify-center rounded-lg bg-white border border-slate-200 text-red-600 hover:bg-red-50 hover:border-red-200 transition shadow-sm" title="Hapus">
                                            <i class="material-icons text-[16px]">delete</i>
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="px-6 py-12 text-center text-slate-500">
                                <div class="flex flex-col items-center justify-center">
                                    <div class="w-16 h-16 bg-slate-50 rounded-full flex items-center justify-center mb-4 text-slate-300">
                                        <i class="material-icons text-4xl">inventory</i>
                                    </div>
                                    <h3 class="text-base font-bold text-slate-800">Belum ada data aset</h3>
                                    <p class="text-sm mt-1">Silakan tambahkan aset tetap baru.</p>
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        
        @if($fixedAssets->hasPages())
            <div class="px-6 py-4 border-t border-slate-100 bg-slate-50/80">
                {{ $fixedAssets->links() }}
            </div>
        @endif
    </div>
</div>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Notifikasi Toast
    @if(session('success')) window.showToast("{{ session('success') }}", 'success'); @endif
    @if(session('error')) window.showToast("{{ session('error') }}", 'error'); @endif
});
</script>
@endpush
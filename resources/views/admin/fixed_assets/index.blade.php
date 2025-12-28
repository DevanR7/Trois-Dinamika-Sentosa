@extends('admin.layouts.app')

@section('title', 'Aset Tetap')

@section('content')

    {{-- HEADER & ACTIONS --}}
    <div class="page-header">
        <div>
            <h1 class="page-title">Aset Tetap</h1>
            <p class="page-subtitle">Kelola aset perusahaan dan konfigurasi penyusutan.</p>
        </div>
        <div class="flex items-center gap-3">
            <a href="{{ route('admin.fixed-assets.create') }}" class="btn btn-primary">
                <i class="material-icons text-sm mr-1">add_business</i> Tambah Aset
            </a>
        </div>
    </div>

    {{-- FILTER & SEARCH --}}
    <div class="card mb-6">
        <div class="card-body">
            <form action="{{ route('admin.fixed-assets.index') }}" method="GET" class="flex flex-col sm:flex-row gap-4">
                <div class="w-full sm:w-72">
                    <div class="input-group">
                        <span class="input-group-text bg-white dark:bg-slate-800">
                            <i class="material-icons text-slate-400">search</i>
                        </span>
                        <input type="text" name="search" class="form-input border-l-0 pl-0" 
                               placeholder="Cari nama aset..." 
                               value="{{ request('search') }}">
                    </div>
                </div>
                <button type="submit" class="btn btn-secondary">
                    Cari
                </button>
            </form>
        </div>
    </div>

    {{-- TABLE DATA --}}
    <div class="card">
        <div class="table-container">
            <table class="table-modern">
                <thead>
                    <tr>
                        <th class="w-16 text-center">#</th>
                        <th>Nama Aset</th>
                        <th>Tanggal Beli</th>
                        <th>Metode Penyusutan</th>
                        <th class="text-right">Harga Perolehan</th>
                        <th class="text-right">Nilai Buku Saat Ini</th>
                        <th class="text-center w-36">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($fixedAssets as $index => $asset)
                        <tr>
                            <td class="text-center text-slate-500">{{ $fixedAssets->firstItem() + $index }}</td>
                            <td>
                                <div class="font-bold text-slate-700 dark:text-slate-200">
                                    {{ $asset->asset_name }}
                                </div>
                                <div class="text-xs text-slate-500">
                                    {{ $asset->useful_life_months }} Bulan Manfaat
                                </div>
                            </td>
                            <td>
                                {{ $asset->purchase_date->format('d M Y') }}
                            </td>
                            <td>
                                @if($asset->depreciation_method == 'straight_line')
                                    <span class="badge badge-primary">Garis Lurus</span>
                                @else
                                    <span class="badge badge-warning">Saldo Menurun</span>
                                @endif
                            </td>
                            <td class="text-right font-mono text-slate-600 dark:text-slate-300">
                                Rp {{ number_format($asset->purchase_cost, 0, ',', '.') }}
                            </td>
                            <td class="text-right font-bold text-indigo-600 dark:text-indigo-400">
                                Rp {{ number_format($asset->current_book_value, 0, ',', '.') }}
                            </td>
                            <td class="text-center">
                                <div class="flex items-center justify-center gap-2">
                                    
                                    {{-- Edit Button --}}
                                    <a href="{{ route('admin.fixed-assets.edit', $asset->asset_id) }}" 
                                       class="w-9 h-9 rounded-full flex items-center justify-center bg-indigo-50 border border-transparent text-indigo-600 hover:bg-indigo-600 hover:text-white transition-colors shadow-sm dark:bg-indigo-900/30 dark:text-indigo-400"
                                       title="Edit">
                                        <i class="material-icons text-[18px] leading-none">edit</i>
                                    </a>

                                    {{-- Delete Button --}}
                                    <button type="button" onclick="confirmDelete('{{ $asset->asset_id }}', '{{ $asset->asset_name }}')" 
                                            class="w-9 h-9 rounded-full flex items-center justify-center bg-rose-50 border border-transparent text-rose-600 hover:bg-rose-600 hover:text-white transition-colors shadow-sm dark:bg-rose-900/30 dark:text-rose-400"
                                            title="Hapus">
                                        <i class="material-icons text-[18px] leading-none">delete</i>
                                    </button>
                                    
                                    <form id="delete-form-{{ $asset->asset_id }}" 
                                          action="{{ route('admin.fixed-assets.destroy', $asset->asset_id) }}" 
                                          method="POST" class="hidden">
                                        @csrf
                                        @method('DELETE')
                                    </form>

                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="text-center p-8">
                                <div class="flex flex-col items-center justify-center text-slate-400">
                                    <i class="material-icons text-5xl mb-2">domain</i>
                                    <span>Belum ada data aset tetap.</span>
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        
        <div class="card-footer">
            {{ $fixedAssets->links() }}
        </div>
    </div>

@endsection

@push('scripts')
<script>
    function confirmDelete(id, name) {
        window.confirmDialog({
            title: 'Hapus Aset?',
            text: "Aset '" + name + "' akan dihapus dan jurnal pembelian akan dibalik (reversal).",
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#ef4444',
            cancelButtonColor: '#64748b',
            confirmButtonText: 'Ya, Hapus!',
            cancelButtonText: 'Batal'
        }).then((result) => {
            if (result.isConfirmed) {
                document.getElementById('delete-form-' + id).submit();
            }
        });
    }
</script>
@endpush
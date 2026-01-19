@extends('admin.layouts.app')

@section('title', 'Manajemen Pajak')

@section('content')
    {{-- Header --}}
    <div class="page-header">
        <div>
            <h1 class="page-title">Manajemen Pajak</h1>
            <p class="page-subtitle">Kelola tarif pajak (PPN, PPh, dll) untuk transaksi</p>
        </div>
        <div>
            <a href="{{ route('admin.taxes.create') }}" class="btn btn-primary">
                <i class="material-icons text-[18px]">add</i>
                Tambah Pajak
            </a>
        </div>
    </div>

    {{-- Filter & Search --}}
    <div class="card mb-6">
        <div class="card-body">
            <div class="flex flex-col md:flex-row justify-between items-center gap-4">
                {{-- Tabs Status --}}
                <div class="flex bg-slate-100 dark:bg-slate-700/50 rounded-lg p-1">
                    <a href="{{ route('admin.taxes.index') }}" 
                       class="px-4 py-2 text-xs font-bold rounded-md transition-all {{ request('status') !== 'trash' ? 'bg-white dark:bg-slate-600 shadow-sm text-indigo-600 dark:text-white' : 'text-slate-500 hover:text-slate-700 dark:hover:text-slate-300' }}">
                        Aktif
                    </a>
                    <a href="{{ route('admin.taxes.index', ['status' => 'trash']) }}" 
                       class="px-4 py-2 text-xs font-bold rounded-md transition-all {{ request('status') === 'trash' ? 'bg-white dark:bg-slate-600 shadow-sm text-indigo-600 dark:text-white' : 'text-slate-500 hover:text-slate-700 dark:hover:text-slate-300' }}">
                        Sampah ({{ \App\Models\Tax::onlyTrashed()->count() }})
                    </a>
                </div>

                {{-- Search --}}
                <form action="{{ route('admin.taxes.index') }}" method="GET" class="w-full md:w-auto">
                    @if(request('status') === 'trash')
                        <input type="hidden" name="status" value="trash">
                    @endif
                    <div class="relative">
                        <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                            <i class="material-icons text-slate-400 text-[18px]">search</i>
                        </div>
                        <input type="text" name="search" class="form-input pl-10 w-full md:w-64" 
                               placeholder="Cari nama pajak..." 
                               value="{{ request('search') }}">
                    </div>
                </form>
            </div>
        </div>
    </div>

    {{-- Data Table --}}
    <div class="card card-plain">
        <div class="table-container">
            <table class="table-modern">
                <thead>
                    <tr>
                        <th class="w-16">#</th>
                        <th>Nama Pajak</th>
                        <th>Tarif (%)</th>
                        <th>Status</th>
                        <th>Terakhir Diupdate</th>
                        <th class="w-32 text-end">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($taxes as $tax)
                    <tr>
                        <td>{{ $loop->iteration + $taxes->firstItem() - 1 }}</td>
                        <td>
                            <span class="font-bold text-slate-700 dark:text-slate-200">{{ $tax->name }}</span>
                        </td>
                        <td>
                            <span class="font-mono font-bold text-slate-600 dark:text-slate-300">
                                {{ number_format($tax->rate, 2) }}%
                            </span>
                        </td>
                        <td>
                            @if($tax->is_active)
                                <span class="badge badge-success">Aktif</span>
                            @else
                                <span class="badge badge-secondary">Non-Aktif</span>
                            @endif
                        </td>
                        <td>
                            <div class="text-xs text-slate-500">
                                {{ $tax->updated_at->format('d M Y, H:i') }}
                            </div>
                        </td>
                        <td class="text-end">
                            <div class="flex items-center justify-end gap-2">
                                @if(request('status') === 'trash')
                                    {{-- Restore Button --}}
                                    <form action="{{ route('admin.taxes.restore', $tax->id) }}" method="POST">
                                        @csrf
                                        @method('PATCH')
                                        <button type="submit" class="btn-action btn-action-restore" title="Pulihkan">
                                            <i class="material-icons">restore</i>
                                        </button>
                                    </form>
                                    
                                    {{-- Force Delete Button --}}
                                    <button type="button" 
                                            class="btn-action btn-action-delete"
                                            title="Hapus Permanen"
                                            onclick="confirmDialog({
                                                title: 'Hapus Permanen?',
                                                text: 'Data pajak ini akan dihapus selamanya dan tidak dapat dikembalikan!',
                                                icon: 'warning',
                                                confirmText: 'Ya, Hapus Permanen',
                                                confirmColor: 'danger'
                                            }).then((result) => {
                                                if (result.isConfirmed) document.getElementById('force-delete-{{ $tax->id }}').submit();
                                            })">
                                        <i class="material-icons">delete_forever</i>
                                    </button>
                                    <form id="force-delete-{{ $tax->id }}" action="{{ route('admin.taxes.forceDelete', $tax->id) }}" method="POST" class="hidden">
                                        @csrf
                                        @method('DELETE')
                                    </form>
                                @else
                                    {{-- Edit Button --}}
                                    <a href="{{ route('admin.taxes.edit', $tax->id) }}" class="btn-action btn-action-edit" title="Edit">
                                        <i class="material-icons">edit</i>
                                    </a>

                                    {{-- Soft Delete Button --}}
                                    <button type="button" 
                                            class="btn-action btn-action-delete"
                                            title="Arsipkan"
                                            onclick="confirmDialog({
                                                title: 'Arsipkan Pajak?',
                                                text: 'Pajak ini akan dipindahkan ke sampah.',
                                                icon: 'question',
                                                confirmText: 'Ya, Arsipkan',
                                                confirmColor: 'danger'
                                            }).then((result) => {
                                                if (result.isConfirmed) document.getElementById('delete-form-{{ $tax->id }}').submit();
                                            })">
                                        <i class="material-icons">delete_outline</i>
                                    </button>
                                    <form id="delete-form-{{ $tax->id }}" action="{{ route('admin.taxes.destroy', $tax->id) }}" method="POST" class="hidden">
                                        @csrf
                                        @method('DELETE')
                                    </form>
                                @endif
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="6" class="text-center py-8">
                            <div class="flex flex-col items-center justify-center text-slate-400">
                                <i class="material-icons text-4xl mb-2">money_off</i>
                                <p class="text-sm">Tidak ada data pajak ditemukan.</p>
                            </div>
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        {{-- Pagination --}}
        <div class="p-4 border-t border-slate-100 dark:border-slate-700">
            {{ $taxes->links('vendor.pagination.admin') }}
        </div>
    </div>
@endsection
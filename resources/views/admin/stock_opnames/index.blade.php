@extends('admin.layouts.app')

@section('title', 'Riwayat Stock Opname')

@section('content')

    {{-- 1. PAGE HEADER --}}
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 mb-6">
        <div>
            <h1 class="text-2xl font-bold text-slate-800 dark:text-white tracking-tight">Stock Opname</h1>
            <p class="text-sm text-slate-500 dark:text-slate-400 mt-1">
                Riwayat audit stok fisik dan penyesuaian inventori.
            </p>
        </div>

        <div class="flex flex-wrap gap-3">
            {{-- Tombol Download Worksheet (Form Kosong untuk diprint ke gudang) --}}
            <a href="{{ route('admin.stock-opnames.worksheet') }}" class="btn bg-white border border-slate-300 text-slate-700 hover:bg-slate-50 dark:bg-slate-800 dark:border-slate-600 dark:text-slate-300">
                <i class="material-icons text-[18px]">print</i>
                <span class="hidden sm:inline">Cetak Worksheet</span>
            </a>

            @can('manage-stock-opnames')
                <a href="{{ route('admin.stock-opnames.create') }}" class="btn btn-primary">
                    <i class="material-icons text-[18px]">add</i>
                    <span>Opname Baru</span>
                </a>
            @endcan
        </div>
    </div>

    {{-- 2. FILTERS --}}
    <div class="card mb-6">
        <div class="card-body p-4">
            <form action="{{ route('admin.stock-opnames.index') }}" method="GET" class="grid grid-cols-1 md:grid-cols-4 gap-4">
                
                {{-- Search --}}
                <div class="md:col-span-2 relative">
                    <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                        <i class="material-icons text-slate-400">search</i>
                    </div>
                    <input type="text" name="search" value="{{ request('search') }}" 
                           class="form-input pl-10" 
                           placeholder="Cari Nomor Opname...">
                </div>

                {{-- Date Filter --}}
                <div>
                    <input type="date" name="start_date" value="{{ request('start_date') }}" class="form-input" title="Dari Tanggal">
                </div>
                <div>
                    <div class="flex gap-2">
                        <input type="date" name="end_date" value="{{ request('end_date') }}" class="form-input" title="Sampai Tanggal">
                        
                        <button type="submit" class="btn btn-primary btn-icon flex-shrink-0" title="Filter">
                            <i class="material-icons">filter_list</i>
                        </button>
                        
                        @if(request()->anyFilled(['search', 'start_date', 'end_date']))
                            <a href="{{ route('admin.stock-opnames.index') }}" class="btn btn-secondary btn-icon flex-shrink-0" title="Reset">
                                <i class="material-icons">refresh</i>
                            </a>
                        @endif
                    </div>
                </div>

            </form>
        </div>
    </div>

    {{-- 3. DATA TABLE --}}
    <div class="card border-0 shadow-none bg-transparent">
        <div class="table-container bg-white dark:bg-slate-800 shadow-sm rounded-xl border border-slate-200 dark:border-slate-700">
            <table class="table-modern">
                <thead>
                    <tr>
                        <th class="w-14 text-center">No</th>
                        <th>Nomor Opname</th>
                        <th>Tanggal & Petugas</th>
                        <th class="text-right">Nilai Penyesuaian (Rp)</th>
                        <th class="text-center">Status</th>
                        <th class="text-right sticky right-0 z-10 bg-slate-50 dark:bg-slate-800/50 backdrop-blur-sm px-4">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($opnames as $index => $opname)
                        <tr class="group hover:bg-slate-50 dark:hover:bg-slate-700/30 transition-colors">
                            
                            <td class="text-center text-slate-400 text-xs">
                                {{ $opnames->firstItem() + $index }}
                            </td>

                            <td>
                                <div class="flex flex-col">
                                    <span class="font-bold text-slate-700 dark:text-slate-200 text-sm font-mono">
                                        {{ $opname->opname_number }}
                                    </span>
                                    @if($opname->notes)
                                        <span class="text-[11px] text-slate-500 truncate max-w-[200px]" title="{{ $opname->notes }}">
                                            {{ $opname->notes }}
                                        </span>
                                    @endif
                                </div>
                            </td>

                            <td>
                                <div class="flex flex-col">
                                    <span class="text-sm font-medium text-slate-600 dark:text-slate-300">
                                        {{ $opname->opname_date->format('d M Y') }}
                                    </span>
                                    <span class="text-[11px] text-slate-400 flex items-center gap-1">
                                        <i class="material-icons text-[12px]">person</i>
                                        {{ $opname->user->full_name ?? 'System' }}
                                    </span>
                                </div>
                            </td>

                            <td class="text-right">
                                @php
                                    $val = $opname->total_adjustment_value;
                                    $color = $val > 0 ? 'text-emerald-600' : ($val < 0 ? 'text-rose-600' : 'text-slate-400');
                                    $prefix = $val > 0 ? '+' : '';
                                @endphp
                                <span class="font-bold {{ $color }}">
                                    {{ $prefix }} {{ number_format($val, 0, ',', '.') }}
                                </span>
                            </td>

                            <td class="text-center">
                                @if($opname->status == 'completed')
                                    <span class="badge bg-emerald-100 text-emerald-700 border-emerald-200">Selesai</span>
                                @elseif($opname->status == 'draft')
                                    <span class="badge bg-slate-100 text-slate-600 border-slate-200">Draft</span>
                                @else
                                    <span class="badge bg-rose-100 text-rose-600 border-rose-200">Dibatalkan</span>
                                @endif
                            </td>

                            <td class="text-right sticky right-0 bg-white dark:bg-slate-800 border-l border-slate-100 dark:border-slate-700/50 group-hover:bg-slate-50 dark:group-hover:bg-slate-700/30 transition-colors z-10 px-4">
                                <div class="flex items-center justify-end gap-2">
                                    
                                    <a href="{{ route('admin.stock-opnames.show', $opname->opname_id) }}" class="btn-action btn-action-view" title="Lihat Detail">
                                        <i class="material-icons">visibility</i>
                                    </a>

                                    {{-- Tombol Batal/Hapus (Hanya untuk Draft/Completed terbaru jika diperbolehkan logika bisnis) --}}
                                    {{-- Sesuai controller, Destroy akan melakukan reversal --}}
                                    @can('manage-stock-opnames')
                                        @if($opname->status != 'cancelled')
                                            <form action="{{ route('admin.stock-opnames.destroy', $opname->opname_id) }}" method="POST" class="inline-block m-0">
                                                @csrf
                                                @method('DELETE')
                                                <button type="button" 
                                                        class="btn-action btn-action-delete" 
                                                        title="Batalkan Opname (Reversal)"
                                                        onclick="handleAction(this, 'Batalkan Opname?', 'PERINGATAN: Stok akan dikembalikan ke posisi sebelum opname ini. Jurnal selisih akan dihapus.', 'danger')">
                                                    <i class="material-icons">delete_forever</i>
                                                </button>
                                            </form>
                                        @endif
                                    @endcan
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="text-center py-12">
                                <div class="flex flex-col items-center justify-center opacity-60">
                                    <i class="material-icons text-4xl text-slate-300 mb-2">fact_check</i>
                                    <p class="text-slate-500 text-sm">Belum ada riwayat stock opname.</p>
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="mt-6">
            {{ $opnames->links() }}
        </div>
    </div>

@push('scripts')
<script>
    function handleAction(button, title, text, type) {
        event.preventDefault();
        const form = button.closest('form');
        if (typeof window.confirmDialog === 'function') {
            window.confirmDialog({
                title: title,
                text: text,
                icon: type === 'danger' ? 'error' : type,
                confirmButtonText: 'Ya, Lanjutkan',
                cancelButtonText: 'Batal',
                confirmButtonColor: type
            }).then((result) => {
                if (result.isConfirmed) form.submit();
            });
        } else {
            if(confirm(text)) form.submit();
        }
    }
</script>
@endpush

@endsection
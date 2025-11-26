@extends('layouts.app')

@section('title', 'Riwayat Stock Opname')

@section('content')
<div class="max-w-7xl mx-auto pb-20 animate-enter">
    
    {{-- HEADER --}}
    <div class="flex flex-col sm:flex-row justify-between items-end gap-4 mb-8">
        <div>
            <h1 class="text-2xl font-bold text-slate-800 tracking-tight">Riwayat Stock Opname</h1>
            <p class="text-slate-500 text-sm mt-1">Audit dan penyesuaian stok gudang secara berkala.</p>
        </div>
        
        <div class="flex gap-3 w-full sm:w-auto">
            <a href="{{ route('stock-opnames.worksheet') }}" 
               class="flex-1 sm:flex-none h-[48px] px-6 bg-white border border-slate-300 text-slate-700 rounded-lg text-sm font-bold hover:bg-slate-50 transition-all shadow-sm flex items-center justify-center gap-2">
                <i class="material-icons text-[18px]">print</i> Cetak Worksheet
            </a>
            <a href="{{ route('stock-opnames.create') }}" 
               class="flex-1 sm:flex-none h-[48px] px-8 bg-indigo-600 hover:bg-indigo-700 text-white text-sm font-bold rounded-lg shadow-md transition-all flex items-center justify-center gap-2">
                <i class="material-icons text-[20px]">add</i> Mulai Opname
            </a>
        </div>
    </div>

    {{-- TABLE CARD --}}
    <div class="dashboard-card p-0 overflow-hidden shadow-lg border-0 ring-1 ring-slate-900/5">
        <div class="overflow-x-auto">
            <table class="dashboard-table min-w-full">
                <thead class="bg-slate-50 border-b border-slate-200">
                    <tr>
                        <th class="pl-6 w-[15%]">No. Opname</th>
                        <th class="w-[15%]">Tanggal</th>
                        <th class="w-[20%]">Petugas</th>
                        <th class="w-[20%]">Catatan</th>
                        <th class="text-right w-[15%]">Nilai Penyesuaian</th>
                        <th class="text-center w-[10%]">Status</th>
                        <th class="text-center pr-6 w-[10%]">Aksi</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-slate-100">
                    @forelse($opnames as $opname)
                    <tr class="hover:bg-slate-50/50 transition-colors group">
                        <td class="pl-6 py-4">
                            <span class="text-sm font-bold text-indigo-600 font-mono group-hover:text-indigo-700 transition-colors">
                                {{ $opname->opname_number }}
                            </span>
                        </td>
                        <td class="py-4">
                            <div class="flex items-center gap-2 text-slate-600 text-sm">
                                <i class="material-icons text-slate-400 text-[16px]">event</i>
                                {{ $opname->opname_date->format('d M Y') }}
                            </div>
                        </td>
                        <td class="py-4">
                            <div class="flex items-center gap-2">
                                <div class="w-6 h-6 rounded-full bg-slate-100 flex items-center justify-center text-slate-500 border border-slate-200 text-xs font-bold">
                                    {{ substr($opname->user->full_name ?? 'S', 0, 1) }}
                                </div>
                                <span class="text-sm text-slate-700 font-medium truncate max-w-[120px]">
                                    {{ $opname->user->full_name ?? 'System' }}
                                </span>
                            </div>
                        </td>
                        <td class="py-4">
                            <span class="text-sm text-slate-500 italic truncate max-w-[150px] block" title="{{ $opname->notes }}">
                                {{ $opname->notes ?: '-' }}
                            </span>
                        </td>
                        <td class="text-right py-4">
                            <span class="text-sm font-bold font-mono {{ $opname->total_adjustment_value < 0 ? 'text-red-600' : ($opname->total_adjustment_value > 0 ? 'text-emerald-600' : 'text-slate-400') }}">
                                {{ $opname->total_adjustment_value > 0 ? '+' : '' }} Rp {{ number_format($opname->total_adjustment_value, 0, ',', '.') }}
                            </span>
                        </td>
                        <td class="text-center py-4">
                            @php 
                                $isComplete = $opname->status == 'completed';
                                $class = $isComplete ? 'status-completed' : 'status-draft';
                                $icon = $isComplete ? 'verified' : 'edit_note';
                                $label = $isComplete ? 'Selesai' : ucfirst($opname->status);
                            @endphp
                            <span class="status-badge {{ $class }}">
                                <i class="material-icons text-[12px]">{{ $icon }}</i> {{ $label }}
                            </span>
                        </td>
                        <td class="text-center pr-6 py-4">
                            <div class="flex justify-center gap-2 opacity-100 sm:opacity-0 group-hover:opacity-100 transition-opacity">
                                <a href="{{ route('stock-opnames.show', $opname->opname_id) }}" 
                                   class="w-8 h-8 flex items-center justify-center rounded-lg bg-white border border-slate-200 text-slate-500 hover:text-indigo-600 hover:border-indigo-200 transition-all shadow-sm" 
                                   title="Detail">
                                    <i class="material-icons text-[16px]">visibility</i>
                                </a>
                                
                                {{-- Global Delete Handler --}}
                                <form action="{{ route('stock-opnames.destroy', $opname->opname_id) }}" method="POST" class="delete-form">
                                    @csrf @method('DELETE')
                                    <button type="submit" 
                                            data-name="Opname {{ $opname->opname_number }}"
                                            data-title="Batalkan Opname?"
                                            data-text="Stok akan dikembalikan dan jurnal dihapus. Aksi ini permanen."
                                            data-btn-text="Ya, Batalkan"
                                            class="w-8 h-8 flex items-center justify-center rounded-lg bg-white border border-slate-200 text-slate-500 hover:text-red-600 hover:border-red-200 transition-all shadow-sm" 
                                            title="Batalkan">
                                        <i class="material-icons text-[16px]">cancel</i>
                                    </button>
                                </form>
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="7" class="py-16 text-center">
                            <div class="flex flex-col items-center justify-center text-slate-400">
                                <div class="w-16 h-16 bg-slate-50 rounded-full flex items-center justify-center mb-4">
                                    <i class="material-icons text-3xl opacity-30">history</i>
                                </div>
                                <h3 class="text-slate-800 font-bold text-lg">Belum ada riwayat</h3>
                                <p class="text-sm mt-1 max-w-xs">Belum ada Stock Opname yang dilakukan.</p>
                                <a href="{{ route('stock-opnames.create') }}" class="mt-4 text-indigo-600 font-bold text-sm hover:underline">Mulai Opname Baru</a>
                            </div>
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
    
    <div class="mt-6">
        {{ $opnames->links() }}
    </div>
</div>
@endsection

@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function() {
        // Toast Handler only (Delete logic via Global app.js)
        @if(session('success')) window.showToast("{{ session('success') }}", 'success'); @endif
        @if(session('error')) window.showToast("{{ session('error') }}", 'error'); @endif
    });
</script>
@endpush
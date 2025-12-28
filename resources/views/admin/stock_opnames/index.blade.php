@extends('admin.layouts.app')

@section('title', 'Stock Opname')

@section('content')

    {{-- HEADER & ACTIONS --}}
    <div class="page-header">
        <div>
            <h1 class="page-title">Stock Opname</h1>
            <p class="page-subtitle">Penyesuaian stok fisik dan sistem.</p>
        </div>
        <div class="flex items-center gap-3">
            {{-- Tombol Download Worksheet (PDF) --}}
            <a href="{{ route('admin.stock-opnames.worksheet') }}" target="_blank" class="btn btn-secondary">
                <i class="material-icons text-sm mr-1">print</i> Download Lembar Kerja
            </a>
            
            <a href="{{ route('admin.stock-opnames.create') }}" class="btn btn-primary">
                <i class="material-icons text-sm mr-1">add</i> Input Opname
            </a>
        </div>
    </div>

    {{-- TABLE LIST --}}
    <div class="card">
        <div class="table-container">
            <table class="table-modern">
                <thead>
                    <tr>
                        <th>No. Opname</th>
                        <th>Tanggal</th>
                        <th>Petugas</th>
                        <th>Catatan</th>
                        <th class="text-right">Nilai Penyesuaian</th>
                        <th class="text-center">Status</th>
                        <th class="text-center w-36">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($opnames as $opname)
                        <tr>
                            <td class="font-medium text-slate-700 dark:text-slate-200">
                                {{ $opname->opname_number }}
                            </td>
                            <td>
                                {{ $opname->opname_date->format('d M Y') }}
                            </td>
                            <td>
                                <div class="flex items-center gap-2">
                                    <div class="w-6 h-6 rounded-full bg-indigo-100 text-indigo-600 flex items-center justify-center text-[10px] font-bold">
                                        {{ substr($opname->user->full_name ?? 'U', 0, 1) }}
                                    </div>
                                    <span class="text-xs">{{ Str::limit($opname->user->full_name ?? '-', 15) }}</span>
                                </div>
                            </td>
                            <td class="text-xs text-slate-500 italic">
                                {{ Str::limit($opname->notes, 30) ?? '-' }}
                            </td>
                            <td class="text-right font-mono text-sm">
                                @php
                                    $val = $opname->total_adjustment_value;
                                    $color = $val > 0 ? 'text-emerald-600' : ($val < 0 ? 'text-rose-600' : 'text-slate-500');
                                    $prefix = $val > 0 ? '+' : '';
                                @endphp
                                <span class="{{ $color }}">
                                    {{ $prefix }} Rp {{ number_format($val, 0, ',', '.') }}
                                </span>
                            </td>
                            <td class="text-center">
                                @if($opname->status == 'completed')
                                    <span class="badge badge-success">Selesai</span>
                                @elseif($opname->status == 'draft')
                                    <span class="badge badge-warning">Draft</span>
                                @else
                                    <span class="badge badge-danger">Batal</span>
                                @endif
                            </td>
                            <td class="text-center">
                                <div class="flex items-center justify-center gap-2">
                                    <a href="{{ route('admin.stock-opnames.show', $opname->opname_id) }}" 
                                       class="w-8 h-8 rounded-full flex items-center justify-center bg-white border border-slate-200 text-slate-500 hover:text-indigo-600 hover:border-indigo-300 transition-colors shadow-sm dark:bg-slate-800 dark:border-slate-700 dark:text-slate-400"
                                       title="Detail">
                                        <i class="material-icons text-[18px] leading-none">visibility</i>
                                    </a>

                                    {{-- Hapus hanya jika perlu (biasanya opname jarang dihapus untuk audit trail) --}}
                                    <button type="button" onclick="confirmDelete('{{ $opname->opname_id }}')" 
                                            class="w-8 h-8 rounded-full flex items-center justify-center bg-rose-50 border border-transparent text-rose-600 hover:bg-rose-600 hover:text-white transition-colors shadow-sm dark:bg-rose-900/30 dark:text-rose-400"
                                            title="Hapus">
                                        <i class="material-icons text-[18px] leading-none">delete</i>
                                    </button>
                                    
                                    <form id="delete-form-{{ $opname->opname_id }}" 
                                          action="{{ route('admin.stock-opnames.destroy', $opname->opname_id) }}" 
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
                                    <i class="material-icons text-5xl mb-2">assignment_late</i>
                                    <span>Belum ada data Stock Opname.</span>
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="card-footer">
            {{ $opnames->links() }}
        </div>
    </div>

@endsection

@push('scripts')
<script>
    function confirmDelete(id) {
        window.confirmDialog({
            title: 'Hapus Opname?',
            text: "Data stok akan dikembalikan ke posisi sebelum opname ini dilakukan. Jurnal akuntansi juga akan dihapus.",
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
@extends('admin.layouts.app')

@section('title', 'Detail Stock Opname')

@section('content')

    <div class="max-w-5xl mx-auto">
        
        {{-- Navigation --}}
        <div class="flex items-center justify-between mb-6">
            <a href="{{ route('admin.stock-opnames.index') }}" class="btn btn-secondary">
                <i class="material-icons text-sm mr-1">arrow_back</i> Kembali
            </a>
            <div class="flex gap-2">
                {{-- Tombol Delete --}}
                <button type="button" onclick="confirmDelete()" class="btn btn-danger">
                    <i class="material-icons text-sm mr-1">delete</i> Batalkan Opname
                </button>
            </div>
        </div>

        {{-- Header Info --}}
        <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-6">
            {{-- Info Utama --}}
            <div class="card md:col-span-2 p-6">
                <div class="flex justify-between items-start mb-4">
                    <div>
                        <h1 class="text-2xl font-bold text-slate-800 dark:text-white">{{ $stockOpname->opname_number }}</h1>
                        <span class="text-sm text-slate-500">{{ $stockOpname->opname_date->format('d F Y') }}</span>
                    </div>
                    @if($stockOpname->status == 'completed')
                        <span class="badge badge-success px-3 py-1 text-sm">Selesai</span>
                    @else
                        <span class="badge badge-warning px-3 py-1 text-sm">{{ ucfirst($stockOpname->status) }}</span>
                    @endif
                </div>
                
                <div class="grid grid-cols-2 gap-4 mt-4">
                    <div>
                        <label class="text-xs font-bold text-slate-400 uppercase">Petugas</label>
                        <div class="font-medium text-slate-700 dark:text-slate-200">
                            {{ $stockOpname->user->full_name ?? 'Unknown' }}
                        </div>
                    </div>
                    <div>
                        <label class="text-xs font-bold text-slate-400 uppercase">Catatan</label>
                        <div class="text-sm text-slate-600 dark:text-slate-300 italic">
                            {{ $stockOpname->notes ?: '-' }}
                        </div>
                    </div>
                </div>
            </div>

            {{-- Summary Nilai --}}
            <div class="card p-6 flex flex-col justify-center items-center text-center">
                <label class="text-xs font-bold text-slate-400 uppercase mb-2">Total Nilai Penyesuaian</label>
                @php
                    $val = $stockOpname->total_adjustment_value;
                    $colorClass = $val >= 0 ? 'text-emerald-600' : 'text-rose-600';
                    $icon = $val >= 0 ? 'trending_up' : 'trending_down';
                @endphp
                <div class="text-3xl font-extrabold {{ $colorClass }}">
                    {{ $val >= 0 ? '+' : '' }} Rp {{ number_format($val, 0, ',', '.') }}
                </div>
                <div class="flex items-center gap-1 mt-2 text-xs {{ $colorClass }} opacity-80">
                    <i class="material-icons text-sm">{{ $icon }}</i>
                    <span>{{ $val >= 0 ? 'Keuntungan Stok' : 'Kerugian Stok' }}</span>
                </div>
            </div>
        </div>

        {{-- Detail Table --}}
        <div class="card">
            <div class="card-header">
                <h3 class="card-header-title">Rincian Barang</h3>
            </div>
            <div class="table-container">
                <table class="table-modern">
                    <thead>
                        <tr>
                            <th>Produk</th>
                            <th class="text-right">Sistem</th>
                            <th class="text-right">Fisik</th>
                            <th class="text-right">Selisih</th>
                            <th class="text-right">HPP / Unit</th>
                            <th class="text-right">Nilai Adj.</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($stockOpname->items as $item)
                            <tr class="hover:bg-slate-50 dark:hover:bg-slate-800/50">
                                <td>
                                    <div class="font-bold text-slate-700 dark:text-slate-200">{{ $item->product->product_name ?? 'Item Dihapus' }}</div>
                                    <div class="text-xs text-slate-500 font-mono">{{ $item->product->product_code ?? '-' }}</div>
                                </td>
                                <td class="text-right font-mono text-slate-500">
                                    {{ number_format($item->system_qty, 2, ',', '.') }}
                                </td>
                                <td class="text-right font-bold text-slate-800 dark:text-white">
                                    {{ number_format($item->physical_qty, 2, ',', '.') }}
                                </td>
                                <td class="text-right font-bold">
                                    @if($item->difference > 0)
                                        <span class="text-emerald-600">+{{ number_format($item->difference, 2, ',', '.') }}</span>
                                    @elseif($item->difference < 0)
                                        <span class="text-rose-600">{{ number_format($item->difference, 2, ',', '.') }}</span>
                                    @else
                                        <span class="text-slate-400">-</span>
                                    @endif
                                </td>
                                <td class="text-right text-sm text-slate-500">
                                    Rp {{ number_format($item->cost_per_unit, 0, ',', '.') }}
                                </td>
                                <td class="text-right font-bold">
                                    @if($item->adjustment_value != 0)
                                        <span class="{{ $item->adjustment_value > 0 ? 'text-emerald-600' : 'text-rose-600' }}">
                                            Rp {{ number_format($item->adjustment_value, 0, ',', '.') }}
                                        </span>
                                    @else
                                        <span class="text-slate-300">-</span>
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>

        <form id="deleteForm" action="{{ route('admin.stock-opnames.destroy', $stockOpname->opname_id) }}" method="POST" class="hidden">
            @csrf
            @method('DELETE')
        </form>

    </div>

@endsection

@push('scripts')
<script>
    function confirmDelete() {
        window.confirmDialog({
            title: 'Batalkan Opname?',
            text: "Tindakan ini akan mengembalikan stok produk ke posisi semula dan menghapus jurnal akuntansi terkait.",
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#ef4444',
            confirmButtonText: 'Ya, Batalkan!',
            cancelButtonText: 'Kembali'
        }).then((result) => {
            if (result.isConfirmed) {
                document.getElementById('deleteForm').submit();
            }
        });
    }
</script>
@endpush
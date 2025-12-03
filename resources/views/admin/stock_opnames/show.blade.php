@extends('admin.layouts.app')

@section('title', 'Detail Opname')

@section('content')
<div class="max-w-7xl mx-auto pb-20 animate-enter">
    
    {{-- HEADER --}}
    <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center mb-8 gap-4">
        <div>
            <nav class="flex text-sm text-slate-500 mb-1">
                <a href="{{ route('admin.stock-opnames.index') }}" class="hover:text-indigo-600 transition-colors">Opname</a>
                <span class="mx-2 text-slate-300">/</span>
                <span class="text-slate-800 font-semibold">Detail</span>
            </nav>
            <h1 class="text-2xl font-bold text-slate-800 tracking-tight flex items-center gap-2">
                No. Dokumen: <span class="text-indigo-600 font-mono">{{ $stockOpname->opname_number }}</span>
            </h1>
        </div>
        
        <div class="flex gap-3 w-full sm:w-auto">
            {{-- Global Delete Handler (Custom Config) --}}
            <form action="{{ route('admin.stock-opnames.destroy', $stockOpname->opname_id) }}" method="POST" class="form-confirm inline-block">
                @csrf @method('DELETE')
                <button type="submit" 
                        data-title="Batalkan Opname?" 
                        data-text="Stok akan dikembalikan seperti semula. Lanjutkan?"
                        data-btn-text="Ya, Batalkan"
                        data-btn-color="#ef4444"
                        class="h-[48px] px-6 bg-white border border-red-200 text-red-600 rounded-lg text-sm font-bold hover:bg-red-50 transition-all shadow-sm flex items-center justify-center gap-2">
                    <i class="material-icons text-[18px]">cancel</i> Batalkan
                </button>
            </form>

            <a href="{{ route('admin.stock-opnames.index') }}" class="h-[48px] px-6 bg-white border border-slate-300 text-slate-700 rounded-lg text-sm font-bold hover:bg-slate-50 transition-all flex items-center justify-center gap-2 shadow-sm">
                <i class="material-icons text-[18px]">arrow_back</i> Kembali
            </a>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
        
        {{-- KOLOM KIRI: INFO UMUM --}}
        <div class="lg:col-span-1 space-y-6">
            <div class="dashboard-card p-0 overflow-hidden shadow-lg border-0 ring-1 ring-slate-900/5">
                <div class="px-6 py-4 border-b border-slate-100 bg-slate-50/50">
                    <h3 class="text-sm font-bold text-slate-700 uppercase tracking-wide flex items-center gap-2">
                        <i class="material-icons text-indigo-600 text-[20px]">info</i> Informasi Umum
                    </h3>
                </div>
                
                <div class="p-6 space-y-6">
                    <div>
                        <label class="text-xs font-bold text-slate-400 uppercase block mb-1">Tanggal Opname</label>
                        <span class="text-sm font-bold text-slate-800 flex items-center gap-2">
                            <i class="material-icons text-slate-400 text-[16px]">event</i> {{ $stockOpname->opname_date->format('d F Y') }}
                        </span>
                    </div>
                    <div>
                        <label class="text-xs font-bold text-slate-400 uppercase block mb-1">Petugas</label>
                        <span class="text-sm font-bold text-slate-800 flex items-center gap-2">
                            <i class="material-icons text-slate-400 text-[16px]">person</i> {{ $stockOpname->user->full_name ?? 'System' }}
                        </span>
                    </div>
                    <div>
                        <label class="text-xs font-bold text-slate-400 uppercase block mb-1">Status</label>
                        @php 
                            $isComplete = $stockOpname->status == 'completed';
                            $class = $isComplete ? 'status-completed' : 'status-draft';
                            $label = $isComplete ? 'Selesai' : ucfirst($stockOpname->status);
                        @endphp
                        <span class="status-badge {{ $class }}">{{ $label }}</span>
                    </div>
                    <div>
                        <label class="text-xs font-bold text-slate-400 uppercase block mb-2">Catatan</label>
                        <div class="bg-slate-50 p-3 rounded-lg border border-slate-100 text-sm text-slate-600 italic min-h-[60px]">
                            {{ $stockOpname->notes ?: 'Tidak ada catatan khusus.' }}
                        </div>
                    </div>
                    
                    <hr class="border-dashed border-slate-200">
                    
                    <div class="text-center">
                        <span class="block text-xs font-bold text-slate-400 uppercase tracking-wider mb-2">Total Nilai Penyesuaian</span>
                        @php 
                            $val = $stockOpname->total_adjustment_value;
                            $isLoss = $val < 0;
                            $colorClass = $val == 0 ? 'text-slate-400' : ($isLoss ? 'text-red-600' : 'text-emerald-600');
                            $sign = $val > 0 ? '+' : '';
                        @endphp
                        <div class="text-2xl font-bold font-mono {{ $colorClass }}">
                            {{ $sign }} Rp {{ number_format($val, 0, ',', '.') }}
                        </div>
                        <span class="inline-block mt-2 px-3 py-1 rounded-full text-[10px] font-bold uppercase border {{ $isLoss ? 'bg-red-50 text-red-600 border-red-100' : ($val > 0 ? 'bg-green-50 text-emerald-600 border-green-100' : 'bg-slate-50 text-slate-500 border-slate-200') }}">
                            {{ $val == 0 ? 'Balance' : ($isLoss ? 'Selisih Kurang' : 'Selisih Lebih') }}
                        </span>
                    </div>
                </div>
            </div>
        </div>

        {{-- KOLOM KANAN: TABEL RINCIAN --}}
        <div class="lg:col-span-2">
            <div class="dashboard-card p-0 overflow-hidden shadow-lg border-0 ring-1 ring-slate-900/5 h-full flex flex-col">
                <div class="px-6 py-4 border-b border-slate-100 flex justify-between items-center bg-slate-50/50">
                    <h3 class="text-sm font-bold text-slate-700 uppercase tracking-wide flex items-center gap-2">
                        <i class="material-icons text-indigo-600 text-[20px]">list_alt</i> Rincian Barang
                    </h3>
                    <span class="bg-indigo-100 text-indigo-700 text-xs font-bold px-2.5 py-1 rounded-md border border-indigo-200">
                        {{ $stockOpname->items->count() }} Item
                    </span>
                </div>
                
                <div class="flex-1 overflow-y-auto custom-scrollbar max-h-[600px]">
                    <table class="dashboard-table w-full">
                        <thead class="bg-slate-50 sticky top-0 z-10 shadow-sm">
                            <tr>
                                <th class="pl-6">Produk (HPP)</th>
                                <th class="text-center">System</th>
                                <th class="text-center">Fisik</th>
                                <th class="text-center">Selisih</th>
                                <th class="text-right pr-6">Nilai Penyesuaian</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-slate-100">
                            @foreach($stockOpname->items as $item)
                            <tr class="{{ $item->difference != 0 ? ($item->difference < 0 ? 'bg-red-50/30' : 'bg-emerald-50/30') : '' }}">
                                <td class="pl-6 py-3">
                                    <div class="text-sm font-bold text-slate-800">{{ $item->product->product_name }}</div>
                                    <div class="text-xs text-slate-500 font-mono mt-0.5">HPP: Rp {{ number_format($item->cost_per_unit, 0, ',', '.') }}</div>
                                </td>
                                <td class="text-center text-sm text-slate-500 font-medium">
                                    {{ $item->system_qty }}
                                </td>
                                <td class="text-center text-sm font-bold text-slate-800 bg-white/50 border-x border-slate-100">
                                    {{ $item->physical_qty }}
                                </td>
                                <td class="text-center text-sm font-bold">
                                    @if($item->difference > 0) 
                                        <span class="text-emerald-600 bg-emerald-100 px-2 py-0.5 rounded">+{{ $item->difference }}</span>
                                    @elseif($item->difference < 0) 
                                        <span class="text-red-600 bg-red-100 px-2 py-0.5 rounded">{{ $item->difference }}</span>
                                    @else 
                                        <span class="text-slate-400">-</span>
                                    @endif
                                </td>
                                <td class="text-right pr-6 text-sm font-mono font-medium">
                                    @if($item->adjustment_value != 0)
                                        <span class="{{ $item->adjustment_value < 0 ? 'text-red-600' : 'text-emerald-600' }}">
                                            {{ $item->adjustment_value > 0 ? '+' : '' }} Rp {{ number_format($item->adjustment_value, 0, ',', '.') }}
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
        </div>
    </div>
</div>
@endsection
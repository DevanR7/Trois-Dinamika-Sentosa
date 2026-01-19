@extends('admin.layouts.app')

@section('title', 'Detail Opname ' . $stockOpname->opname_number)

@section('content')

    {{-- 1. HEADER --}}
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 mb-6">
        <div>
            <div class="flex items-center gap-3">
                <h1 class="text-2xl font-bold text-slate-800 dark:text-white tracking-tight">
                    {{ $stockOpname->opname_number }}
                </h1>
                @if($stockOpname->status == 'completed')
                    <span class="badge bg-emerald-100 text-emerald-700 border-emerald-200">Selesai</span>
                @elseif($stockOpname->status == 'draft')
                    <span class="badge bg-slate-100 text-slate-600 border-slate-200">Draft</span>
                @else
                    <span class="badge bg-rose-100 text-rose-600 border-rose-200">Dibatalkan</span>
                @endif
            </div>
            <div class="flex items-center gap-3 text-sm text-slate-500 dark:text-slate-400 mt-1">
                <span class="flex items-center gap-1">
                    <i class="material-icons text-[16px]">calendar_today</i>
                    {{ $stockOpname->opname_date->format('d F Y') }}
                </span>
                <span class="w-1 h-1 bg-slate-300 rounded-full"></span>
                <span class="flex items-center gap-1">
                    <i class="material-icons text-[16px]">person</i>
                    {{ $stockOpname->user->full_name ?? 'System' }}
                </span>
            </div>
        </div>

        <div class="flex flex-wrap gap-2">
            <a href="{{ route('admin.stock-opnames.index') }}" class="btn btn-secondary">
                <i class="material-icons text-[18px]">arrow_back</i>
                Kembali
            </a>

            {{-- Tombol Batalkan (Jika belum batal) --}}
            @can('manage-stock-opnames')
                @if($stockOpname->status != 'cancelled')
                    <form action="{{ route('admin.stock-opnames.destroy', $stockOpname->opname_id) }}" method="POST" class="inline-block m-0">
                        @csrf
                        @method('DELETE')
                        <button type="button" 
                                class="btn bg-white text-rose-600 border border-rose-200 hover:bg-rose-50 hover:border-rose-300 shadow-sm"
                                onclick="handleAction(this, 'Batalkan Opname?', 'PERINGATAN: Stok akan direversal ke kondisi sebelum opname.', 'danger')">
                            <i class="material-icons text-[18px]">block</i>
                            Batalkan
                        </button>
                    </form>
                @endif
            @endcan
        </div>
    </div>

    {{-- 2. SUMMARY CARDS --}}
    <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-6">
        
        {{-- Card Total Item --}}
        <div class="card p-5 flex items-center justify-between">
            <div>
                <p class="text-[10px] uppercase font-bold text-slate-400 tracking-wider">Total Produk</p>
                <p class="text-2xl font-bold text-slate-800 dark:text-white mt-1">
                    {{ $stockOpname->items->count() }}
                </p>
            </div>
            <div class="w-10 h-10 rounded-lg bg-indigo-50 dark:bg-indigo-900/30 flex items-center justify-center text-indigo-600">
                <i class="material-icons text-xl">inventory_2</i>
            </div>
        </div>

        {{-- Card Selisih Item --}}
        @php
            $diffCount = $stockOpname->items->where('difference', '!=', 0)->count();
        @endphp
        <div class="card p-5 flex items-center justify-between">
            <div>
                <p class="text-[10px] uppercase font-bold text-slate-400 tracking-wider">Produk Selisih</p>
                <p class="text-2xl font-bold text-amber-600 mt-1">
                    {{ $diffCount }}
                </p>
            </div>
            <div class="w-10 h-10 rounded-lg bg-amber-50 dark:bg-amber-900/30 flex items-center justify-center text-amber-600">
                <i class="material-icons text-xl">compare_arrows</i>
            </div>
        </div>

        {{-- Card Nilai Penyesuaian --}}
        @php
            $totalVal = $stockOpname->total_adjustment_value;
            $valColor = $totalVal > 0 ? 'text-emerald-600' : ($totalVal < 0 ? 'text-rose-600' : 'text-slate-600');
            $valIcon = $totalVal > 0 ? 'trending_up' : ($totalVal < 0 ? 'trending_down' : 'remove');
            $bgIcon = $totalVal > 0 ? 'bg-emerald-50 text-emerald-600' : ($totalVal < 0 ? 'bg-rose-50 text-rose-600' : 'bg-slate-100 text-slate-400');
        @endphp
        <div class="card p-5 flex items-center justify-between">
            <div>
                <p class="text-[10px] uppercase font-bold text-slate-400 tracking-wider">Nilai Penyesuaian (Net)</p>
                <p class="text-2xl font-bold {{ $valColor }} mt-1">
                    Rp {{ number_format($totalVal, 0, ',', '.') }}
                </p>
            </div>
            <div class="w-10 h-10 rounded-lg {{ $bgIcon }} flex items-center justify-center">
                <i class="material-icons text-xl">{{ $valIcon }}</i>
            </div>
        </div>
    </div>

    {{-- 3. NOTES --}}
    @if($stockOpname->notes)
        <div class="card p-4 mb-6 bg-slate-50 dark:bg-slate-800/50 border border-slate-200 dark:border-slate-700">
            <h4 class="text-xs font-bold text-slate-500 uppercase mb-1">Catatan:</h4>
            <p class="text-sm text-slate-700 dark:text-slate-300 italic">"{{ $stockOpname->notes }}"</p>
        </div>
    @endif

    {{-- 4. DETAIL TABLE --}}
    <div class="card">
        <div class="card-header bg-white dark:bg-slate-800">
            <h3 class="card-header-title">Rincian Hasil Opname</h3>
        </div>
        <div class="table-container border-0 rounded-none">
            <table class="table-modern">
                <thead>
                    <tr>
                        <th class="w-10 text-center">No</th>
                        <th>Produk</th>
                        <th class="text-center bg-slate-50 dark:bg-slate-700/30">Sistem</th>
                        <th class="text-center bg-indigo-50/50 dark:bg-indigo-900/10">Fisik</th>
                        <th class="text-center">Selisih</th>
                        <th class="text-right">HPP (Avg)</th>
                        <th class="text-right">Nilai Selisih</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($stockOpname->items as $index => $item)
                        <tr class="hover:bg-slate-50 dark:hover:bg-slate-700/20">
                            <td class="text-center text-slate-400 text-xs">{{ $index + 1 }}</td>
                            
                            <td>
                                <div class="flex items-center gap-3">
                                    <div class="w-8 h-8 rounded bg-slate-100 dark:bg-slate-700 flex items-center justify-center shrink-0">
                                        @if($item->product->image_path)
                                            <img src="{{ asset('storage/'.$item->product->image_path) }}" class="w-full h-full object-cover rounded">
                                        @else
                                            <i class="material-icons text-slate-400 text-sm">image</i>
                                        @endif
                                    </div>
                                    <div class="flex flex-col">
                                        <span class="font-bold text-sm text-slate-700 dark:text-slate-200">
                                            {{ $item->product->product_name }}
                                        </span>
                                        <span class="text-[10px] font-mono text-slate-500">{{ $item->product->product_code }}</span>
                                    </div>
                                </div>
                            </td>

                            <td class="text-center bg-slate-50/50 dark:bg-slate-800/30 text-slate-500">
                                {{ (float)$item->system_qty }}
                            </td>
                            
                            <td class="text-center font-bold bg-indigo-50/30 dark:bg-indigo-900/10 text-slate-800 dark:text-white">
                                {{ (float)$item->physical_qty }}
                            </td>

                            <td class="text-center">
                                @if($item->difference > 0)
                                    <span class="badge bg-emerald-100 text-emerald-700 font-bold">+{{ (float)$item->difference }}</span>
                                @elseif($item->difference < 0)
                                    <span class="badge bg-rose-100 text-rose-700 font-bold">{{ (float)$item->difference }}</span>
                                @else
                                    <span class="text-slate-300">-</span>
                                @endif
                            </td>

                            <td class="text-right text-xs text-slate-500">
                                Rp {{ number_format($item->cost_per_unit, 0, ',', '.') }}
                            </td>

                            <td class="text-right font-medium">
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
                <tfoot>
                    <tr class="bg-slate-50 dark:bg-slate-800/50 font-bold border-t border-slate-200 dark:border-slate-700">
                        <td colspan="6" class="text-right px-6 py-3 uppercase text-xs tracking-wider text-slate-500">Total Nilai Penyesuaian</td>
                        <td class="text-right px-6 py-3 text-sm {{ $totalVal >= 0 ? 'text-emerald-600' : 'text-rose-600' }}">
                            Rp {{ number_format($totalVal, 0, ',', '.') }}
                        </td>
                    </tr>
                </tfoot>
            </table>
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
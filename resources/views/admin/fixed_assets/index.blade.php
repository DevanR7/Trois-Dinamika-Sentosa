@extends('admin.layouts.app')

@section('title', 'Daftar Aset Tetap')

@section('content')
    <div class="flex flex-col gap-6">
        
        {{-- Page Header --}}
        <div class="page-header">
            <div>
                <h1 class="page-title">Aset Tetap (Fixed Assets)</h1>
                <p class="page-subtitle">Kelola aset perusahaan dan konfigurasi penyusutan otomatis.</p>
            </div>
            <div class="flex flex-col sm:flex-row gap-3">
                <a href="{{ route('admin.fixed-assets.create') }}" class="btn btn-primary">
                    <i class="material-icons text-[18px] mr-1">add_business</i> Registrasi Aset
                </a>
            </div>
        </div>

        {{-- Filter & Search --}}
        <div class="card p-5">
            <form method="GET" action="{{ route('admin.fixed-assets.index') }}">
                <div class="flex flex-col md:flex-row gap-4 items-end">
                    <div class="w-full md:w-1/3 relative">
                        <label class="form-label">Pencarian</label>
                        <div class="relative">
                            <input type="text" name="search" value="{{ request('search') }}" 
                                   class="form-input pl-10" 
                                   placeholder="Cari Nama Aset...">
                            <span class="absolute left-3 top-2.5 text-slate-400">
                                <i class="material-icons text-[20px]">search</i>
                            </span>
                        </div>
                    </div>
                    <div class="w-full md:w-auto">
                        <button type="submit" class="btn btn-secondary w-full md:w-auto">
                            Filter Data
                        </button>
                    </div>
                </div>
            </form>
        </div>

        {{-- Table Card --}}
        <div class="card card-plain">
            <div class="table-container">
                <table class="table-modern">
                    <thead>
                        <tr>
                            <th>Nama Aset & Tanggal</th>
                            <th>Akun Aset (COA)</th>
                            <th class="text-right">Harga Perolehan</th>
                            <th class="text-right">Nilai Buku Saat Ini</th>
                            <th class="w-32 text-center">Status Nilai</th>
                            <th class="text-right">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($fixedAssets as $asset)
                            @php
                                $percentRemaining = ($asset->purchase_cost > 0) 
                                    ? ($asset->current_book_value / $asset->purchase_cost) * 100 
                                    : 0;
                                
                                // Warna bar: Hijau (Baru) -> Kuning (Setengah) -> Merah (Habis)
                                $barColor = 'bg-emerald-500';
                                if($percentRemaining < 30) $barColor = 'bg-rose-500';
                                elseif($percentRemaining < 70) $barColor = 'bg-amber-500';
                            @endphp
                            <tr class="hover:bg-slate-50 dark:hover:bg-slate-800/50 transition-colors">
                                <td>
                                    <div class="flex flex-col">
                                        <span class="font-bold text-slate-700 dark:text-slate-200">
                                            {{ $asset->asset_name }}
                                        </span>
                                        <span class="text-xs text-slate-500">
                                            Beli: {{ $asset->purchase_date->translatedFormat('d M Y') }}
                                        </span>
                                    </div>
                                </td>
                                <td>
                                    <div class="flex flex-col">
                                        <span class="text-xs font-bold text-indigo-600 dark:text-indigo-400">
                                            {{ $asset->assetAccount->account_number ?? '-' }}
                                        </span>
                                        <span class="text-xs text-slate-500 truncate max-w-[150px]" title="{{ $asset->assetAccount->account_name ?? '-' }}">
                                            {{ $asset->assetAccount->account_name ?? '-' }}
                                        </span>
                                    </div>
                                </td>
                                <td class="text-right font-mono text-slate-600 dark:text-slate-300">
                                    {{ number_format($asset->purchase_cost, 0, ',', '.') }}
                                </td>
                                <td class="text-right font-mono font-bold text-slate-800 dark:text-white">
                                    {{ number_format($asset->current_book_value, 0, ',', '.') }}
                                </td>
                                <td class="align-middle px-4">
                                    <div class="w-full bg-slate-200 rounded-full h-1.5 dark:bg-slate-700">
                                        <div class="{{ $barColor }} h-1.5 rounded-full" style="width: {{ $percentRemaining }}%"></div>
                                    </div>
                                    <div class="text-[10px] text-center mt-1 text-slate-400">
                                        {{ round($percentRemaining) }}% Tersisa
                                    </div>
                                </td>
                                <td>
                                    <div class="flex items-center justify-end gap-2">
                                        <x-ui.table-actions 
                                            edit="{{ route('admin.fixed-assets.edit', $asset->asset_id) }}"
                                            delete="{{ route('admin.fixed-assets.destroy', $asset->asset_id) }}"
                                            message="Menghapus aset akan membatalkan jurnal pembelian. Pastikan aset belum disusutkan."
                                        />
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="text-center py-12">
                                    <div class="flex flex-col items-center justify-center">
                                        <div class="w-16 h-16 bg-slate-100 dark:bg-slate-800 rounded-full flex items-center justify-center mb-4 text-slate-400">
                                            <i class="material-icons text-3xl">domain_disabled</i>
                                        </div>
                                        <p class="text-slate-500 font-medium">Belum ada data aset tetap.</p>
                                    </div>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            
            {{-- Pagination --}}
            <div class="px-6 py-4 border-t border-slate-200 dark:border-slate-700">
                {{ $fixedAssets->links('vendor.pagination.admin') }}
            </div>
        </div>
    </div>
@endsection
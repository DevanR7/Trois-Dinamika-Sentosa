@extends('admin.layouts.app')

@section('title', 'Aging Schedule')

@section('content')
    {{-- Header --}}
    <div class="page-header print:hidden">
        <div>
            <h1 class="page-title">Aging Schedule</h1>
            <p class="page-subtitle">Laporan umur Piutang (AR) dan Hutang (AP) berdasarkan jatuh tempo</p>
        </div>
        <div>
            <button onclick="window.print()" class="btn btn-secondary">
                <i class="material-icons text-[18px]">print</i>
                Cetak Laporan
            </button>
        </div>
    </div>

    {{-- Main Content with Tabs --}}
    <div x-data="{ activeTab: 'ar' }">
        
        {{-- Tabs Navigation --}}
        <div class="mb-6 border-b border-slate-200 dark:border-slate-700 print:hidden">
            <ul class="flex flex-wrap -mb-px text-sm font-medium text-center">
                <li class="me-2">
                    <button type="button" 
                            @click="activeTab = 'ar'"
                            :class="activeTab === 'ar' 
                                ? 'inline-flex items-center justify-center p-4 text-emerald-600 border-b-2 border-emerald-600 rounded-t-lg active dark:text-emerald-500 dark:border-emerald-500 group bg-emerald-50/50 dark:bg-emerald-900/10' 
                                : 'inline-flex items-center justify-center p-4 border-b-2 border-transparent rounded-t-lg hover:text-slate-600 hover:border-slate-300 dark:hover:text-slate-300 group transition-all'">
                        <i class="material-icons text-[20px] mr-2" 
                           :class="activeTab === 'ar' ? 'text-emerald-600 dark:text-emerald-500' : 'text-slate-400 group-hover:text-slate-500 dark:text-slate-500 dark:group-hover:text-slate-300'">
                            trending_up
                        </i>
                        Piutang Usaha (AR)
                        <span class="ml-2 bg-emerald-100 text-emerald-800 text-xs font-semibold px-2 py-0.5 rounded-full dark:bg-emerald-900 dark:text-emerald-300">
                            {{ count($arAging['details']) }}
                        </span>
                    </button>
                </li>
                <li class="me-2">
                    <button type="button" 
                            @click="activeTab = 'ap'"
                            :class="activeTab === 'ap' 
                                ? 'inline-flex items-center justify-center p-4 text-rose-600 border-b-2 border-rose-600 rounded-t-lg active dark:text-rose-500 dark:border-rose-500 group bg-rose-50/50 dark:bg-rose-900/10' 
                                : 'inline-flex items-center justify-center p-4 border-b-2 border-transparent rounded-t-lg hover:text-slate-600 hover:border-slate-300 dark:hover:text-slate-300 group transition-all'">
                        <i class="material-icons text-[20px] mr-2" 
                           :class="activeTab === 'ap' ? 'text-rose-600 dark:text-rose-500' : 'text-slate-400 group-hover:text-slate-500 dark:text-slate-500 dark:group-hover:text-slate-300'">
                            trending_down
                        </i>
                        Hutang Usaha (AP)
                        <span class="ml-2 bg-rose-100 text-rose-800 text-xs font-semibold px-2 py-0.5 rounded-full dark:bg-rose-900 dark:text-rose-300">
                            {{ count($apAging['details']) }}
                        </span>
                    </button>
                </li>
            </ul>
        </div>

        {{-- TAB 1: PIUTANG USAHA (AR) --}}
        <div x-show="activeTab === 'ar'" 
             x-transition:enter="transition ease-out duration-300"
             x-transition:enter-start="opacity-0 translate-y-2"
             x-transition:enter-end="opacity-100 translate-y-0">
            
            {{-- AR Summary Cards --}}
            <div class="grid grid-cols-2 md:grid-cols-5 gap-4 mb-6">
                {{-- Total --}}
                <div class="card p-4 bg-white dark:bg-slate-800 border-l-4 border-slate-500">
                    <p class="text-[10px] uppercase font-bold text-slate-500">Total Piutang</p>
                    <p class="text-lg font-bold text-slate-800 dark:text-white mt-1 truncate" title="{{ number_format($arAging['total']) }}">
                        Rp {{ number_format($arAging['total'], 0, ',', '.') }}
                    </p>
                </div>
                {{-- 0-30 --}}
                <div class="card p-4 bg-emerald-50 dark:bg-emerald-900/20 border-l-4 border-emerald-500">
                    <p class="text-[10px] uppercase font-bold text-emerald-600">0 - 30 Hari</p>
                    <p class="text-lg font-bold text-emerald-700 dark:text-emerald-400 mt-1 truncate">
                        Rp {{ number_format($arAging['0_30'], 0, ',', '.') }}
                    </p>
                </div>
                {{-- 31-60 --}}
                <div class="card p-4 bg-yellow-50 dark:bg-yellow-900/20 border-l-4 border-yellow-500">
                    <p class="text-[10px] uppercase font-bold text-yellow-600">31 - 60 Hari</p>
                    <p class="text-lg font-bold text-yellow-700 dark:text-yellow-400 mt-1 truncate">
                        Rp {{ number_format($arAging['31_60'], 0, ',', '.') }}
                    </p>
                </div>
                {{-- 61-90 --}}
                <div class="card p-4 bg-orange-50 dark:bg-orange-900/20 border-l-4 border-orange-500">
                    <p class="text-[10px] uppercase font-bold text-orange-600">61 - 90 Hari</p>
                    <p class="text-lg font-bold text-orange-700 dark:text-orange-400 mt-1 truncate">
                        Rp {{ number_format($arAging['61_90'], 0, ',', '.') }}
                    </p>
                </div>
                {{-- >90 --}}
                <div class="card p-4 bg-rose-50 dark:bg-rose-900/20 border-l-4 border-rose-500">
                    <p class="text-[10px] uppercase font-bold text-rose-600">> 90 Hari</p>
                    <p class="text-lg font-bold text-rose-700 dark:text-rose-400 mt-1 truncate">
                        Rp {{ number_format($arAging['90_plus'], 0, ',', '.') }}
                    </p>
                </div>
            </div>

            {{-- AR Table --}}
            <div class="card card-plain">
                <div class="card-header">
                    <h3 class="card-header-title">Rincian Faktur Belum Lunas (Piutang)</h3>
                </div>
                <div class="table-container">
                    <table class="table-modern w-full">
                        <thead>
                            <tr>
                                <th>No. Invoice</th>
                                <th>Klien</th>
                                <th>Tgl Faktur</th>
                                <th>Jatuh Tempo</th>
                                <th class="text-center">Hari Lewat</th>
                                <th>Kategori Umur</th>
                                <th class="text-right">Sisa Tagihan</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($arAging['details'] as $item)
                                <tr class="hover:bg-slate-50 dark:hover:bg-slate-800/50">
                                    <td>
                                        <a href="{{ route('admin.invoices.show', ['invoice' => str_replace('INV/', '', $item['number'])]) }}" class="text-indigo-600 hover:underline font-mono text-xs font-bold">
                                            {{ $item['number'] }}
                                        </a>
                                    </td>
                                    <td>
                                        <span class="text-sm font-medium text-slate-700 dark:text-slate-300">
                                            {{ $item['party'] }}
                                        </span>
                                    </td>
                                    <td class="text-xs text-slate-500">
                                        {{ \Carbon\Carbon::parse($item['date'])->format('d/m/Y') }}
                                    </td>
                                    <td class="text-xs text-slate-500">
                                        {{ \Carbon\Carbon::parse($item['due_date'])->format('d/m/Y') }}
                                    </td>
                                    <td class="text-center">
                                        @if($item['days_overdue'] <= 0)
                                            <span class="text-xs text-emerald-600 font-bold">Belum JT</span>
                                        @else
                                            <span class="text-xs font-bold {{ $item['days_overdue'] > 90 ? 'text-rose-600' : ($item['days_overdue'] > 60 ? 'text-orange-600' : 'text-slate-600') }}">
                                                {{ $item['days_overdue'] }} Hari
                                            </span>
                                        @endif
                                    </td>
                                    <td>
                                        @if($item['days_overdue'] <= 30)
                                            <span class="badge bg-emerald-100 text-emerald-800">0 - 30</span>
                                        @elseif($item['days_overdue'] <= 60)
                                            <span class="badge bg-yellow-100 text-yellow-800">31 - 60</span>
                                        @elseif($item['days_overdue'] <= 90)
                                            <span class="badge bg-orange-100 text-orange-800">61 - 90</span>
                                        @else
                                            <span class="badge bg-rose-100 text-rose-800">> 90</span>
                                        @endif
                                    </td>
                                    <td class="text-right font-bold font-mono text-slate-700 dark:text-white">
                                        Rp {{ number_format($item['amount'], 0, ',', '.') }}
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="7" class="text-center py-8 text-slate-400">
                                        <i class="material-icons text-4xl mb-2">check_circle</i>
                                        <p>Tidak ada piutang tertunggak.</p>
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        {{-- TAB 2: HUTANG USAHA (AP) --}}
        <div x-show="activeTab === 'ap'" 
             x-transition:enter="transition ease-out duration-300"
             x-transition:enter-start="opacity-0 translate-y-2"
             x-transition:enter-end="opacity-100 translate-y-0"
             style="display: none;">
            
            {{-- AP Summary Cards --}}
            <div class="grid grid-cols-2 md:grid-cols-5 gap-4 mb-6">
                {{-- Total --}}
                <div class="card p-4 bg-white dark:bg-slate-800 border-l-4 border-slate-500">
                    <p class="text-[10px] uppercase font-bold text-slate-500">Total Hutang</p>
                    <p class="text-lg font-bold text-slate-800 dark:text-white mt-1 truncate" title="{{ number_format($apAging['total']) }}">
                        Rp {{ number_format($apAging['total'], 0, ',', '.') }}
                    </p>
                </div>
                {{-- 0-30 --}}
                <div class="card p-4 bg-emerald-50 dark:bg-emerald-900/20 border-l-4 border-emerald-500">
                    <p class="text-[10px] uppercase font-bold text-emerald-600">0 - 30 Hari</p>
                    <p class="text-lg font-bold text-emerald-700 dark:text-emerald-400 mt-1 truncate">
                        Rp {{ number_format($apAging['0_30'], 0, ',', '.') }}
                    </p>
                </div>
                {{-- 31-60 --}}
                <div class="card p-4 bg-yellow-50 dark:bg-yellow-900/20 border-l-4 border-yellow-500">
                    <p class="text-[10px] uppercase font-bold text-yellow-600">31 - 60 Hari</p>
                    <p class="text-lg font-bold text-yellow-700 dark:text-yellow-400 mt-1 truncate">
                        Rp {{ number_format($apAging['31_60'], 0, ',', '.') }}
                    </p>
                </div>
                {{-- 61-90 --}}
                <div class="card p-4 bg-orange-50 dark:bg-orange-900/20 border-l-4 border-orange-500">
                    <p class="text-[10px] uppercase font-bold text-orange-600">61 - 90 Hari</p>
                    <p class="text-lg font-bold text-orange-700 dark:text-orange-400 mt-1 truncate">
                        Rp {{ number_format($apAging['61_90'], 0, ',', '.') }}
                    </p>
                </div>
                {{-- >90 --}}
                <div class="card p-4 bg-rose-50 dark:bg-rose-900/20 border-l-4 border-rose-500">
                    <p class="text-[10px] uppercase font-bold text-rose-600">> 90 Hari</p>
                    <p class="text-lg font-bold text-rose-700 dark:text-rose-400 mt-1 truncate">
                        Rp {{ number_format($apAging['90_plus'], 0, ',', '.') }}
                    </p>
                </div>
            </div>

            {{-- AP Table --}}
            <div class="card card-plain">
                <div class="card-header">
                    <h3 class="card-header-title">Rincian Hutang Dagang (Purchase Orders)</h3>
                </div>
                <div class="table-container">
                    <table class="table-modern w-full">
                        <thead>
                            <tr>
                                <th>No. PO</th>
                                <th>Supplier</th>
                                <th>Tgl PO</th>
                                <th>Jatuh Tempo</th>
                                <th class="text-center">Hari Lewat</th>
                                <th>Kategori Umur</th>
                                <th class="text-right">Sisa Hutang</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($apAging['details'] as $item)
                                <tr class="hover:bg-slate-50 dark:hover:bg-slate-800/50">
                                    <td>
                                        {{-- Link ke PO Show (perlu id, asumsikan di controller logic AP aging diupdate sedikit untuk kirim ID, 
                                             tapi jika hanya kirim number, kita disable link atau cari cara lain.
                                             Idealnya controller kirim 'id' juga. Disini kita tampilkan text saja jika ID tidak ada) --}}
                                        <span class="font-mono text-xs font-bold text-slate-700 dark:text-slate-300">
                                            {{ $item['number'] }}
                                        </span>
                                    </td>
                                    <td>
                                        <span class="text-sm font-medium text-slate-700 dark:text-slate-300">
                                            {{ $item['party'] }}
                                        </span>
                                    </td>
                                    <td class="text-xs text-slate-500">
                                        {{ \Carbon\Carbon::parse($item['date'])->format('d/m/Y') }}
                                    </td>
                                    <td class="text-xs text-slate-500">
                                        {{ \Carbon\Carbon::parse($item['due_date'])->format('d/m/Y') }}
                                    </td>
                                    <td class="text-center">
                                        @if($item['days_overdue'] <= 0)
                                            <span class="text-xs text-emerald-600 font-bold">Belum JT</span>
                                        @else
                                            <span class="text-xs font-bold {{ $item['days_overdue'] > 90 ? 'text-rose-600' : ($item['days_overdue'] > 60 ? 'text-orange-600' : 'text-slate-600') }}">
                                                {{ $item['days_overdue'] }} Hari
                                            </span>
                                        @endif
                                    </td>
                                    <td>
                                        @if($item['days_overdue'] <= 30)
                                            <span class="badge bg-emerald-100 text-emerald-800">0 - 30</span>
                                        @elseif($item['days_overdue'] <= 60)
                                            <span class="badge bg-yellow-100 text-yellow-800">31 - 60</span>
                                        @elseif($item['days_overdue'] <= 90)
                                            <span class="badge bg-orange-100 text-orange-800">61 - 90</span>
                                        @else
                                            <span class="badge bg-rose-100 text-rose-800">> 90</span>
                                        @endif
                                    </td>
                                    <td class="text-right font-bold font-mono text-slate-700 dark:text-white">
                                        Rp {{ number_format($item['amount'], 0, ',', '.') }}
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="7" class="text-center py-8 text-slate-400">
                                        <i class="material-icons text-4xl mb-2">fact_check</i>
                                        <p>Tidak ada hutang dagang.</p>
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

    </div>
@endsection
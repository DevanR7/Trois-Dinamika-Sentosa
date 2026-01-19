@extends('admin.layouts.app')

@section('title', 'Laporan Keuangan')

@section('content')
<div x-data="{ activeTab: 'profit_loss' }">

    {{-- ==================================================================== --}}
    {{-- HEADER & FILTER AREA (Disembunyikan saat Print) --}}
    {{-- ==================================================================== --}}
    <div class="print:hidden">
        
        {{-- Page Header --}}
        <div class="page-header flex flex-col md:flex-row justify-between items-start md:items-center gap-4">
            <div>
                <h1 class="page-title text-2xl">Financial Cockpit</h1>
                <p class="page-subtitle">Pusat analisa kesehatan keuangan: Laba Rugi, Neraca, & Arus Kas</p>
            </div>
            <div class="flex gap-2">
                <a href="{{ route('admin.reports.pdf', request()->all()) }}" target="_blank" class="btn btn-primary shadow-lg shadow-indigo-500/20">
    <i class="material-icons text-[18px]">download</i> Download PDF
</a>
            </div>
        </div>

        {{-- Filter Tanggal --}}
        <div class="card mb-8 border-l-4 border-indigo-500 shadow-sm">
            <div class="card-body">
                <form action="{{ route('admin.reports.index') }}" method="GET" class="flex flex-col md:flex-row items-end gap-4">
                    <div class="w-full md:w-1/3">
                        <label class="form-label font-bold">Periode Mulai</label>
                        <div class="relative">
                            <i class="material-icons absolute left-3 top-2.5 text-slate-400 text-[18px]">calendar_today</i>
                            <input type="date" name="start_date" class="form-input pl-10" value="{{ $startDate }}">
                        </div>
                    </div>
                    <div class="w-full md:w-1/3">
                        <label class="form-label font-bold">Sampai Tanggal</label>
                        <div class="relative">
                            <i class="material-icons absolute left-3 top-2.5 text-slate-400 text-[18px]">event</i>
                            <input type="date" name="end_date" class="form-input pl-10" value="{{ $endDate }}">
                        </div>
                    </div>
                    <div class="w-full md:w-auto">
                        <button type="submit" class="btn btn-secondary w-full border-slate-300 hover:border-indigo-500 hover:text-indigo-600">
                            <i class="material-icons">filter_list</i> Terapkan Filter
                        </button>
                    </div>
                </form>
            </div>
        </div>

        {{-- KPI CARDS (Ringkasan Eksekutif) --}}
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
            {{-- 1. Net Profit --}}
            <div class="card p-5 border-t-4 {{ $labaBersih >= 0 ? 'border-emerald-500' : 'border-rose-500' }} shadow-sm relative overflow-hidden">
                <div class="relative z-10">
                    <p class="text-xs font-bold text-slate-500 uppercase tracking-wider">Laba Bersih (Net Income)</p>
                    <h3 class="text-2xl font-extrabold mt-2 font-mono {{ $labaBersih >= 0 ? 'text-emerald-600' : 'text-rose-600' }}">
                        Rp {{ number_format($labaBersih, 0, ',', '.') }}
                    </h3>
                    <p class="text-[10px] text-slate-400 mt-1">Periode terpilih</p>
                </div>
                <i class="material-icons absolute right-2 bottom-2 text-6xl opacity-5 {{ $labaBersih >= 0 ? 'text-emerald-500' : 'text-rose-500' }}">show_chart</i>
            </div>

            {{-- 2. Total Assets --}}
            <div class="card p-5 border-t-4 border-indigo-500 shadow-sm relative overflow-hidden">
                <div class="relative z-10">
                    <p class="text-xs font-bold text-slate-500 uppercase tracking-wider">Total Aset</p>
                    <h3 class="text-2xl font-extrabold text-indigo-700 dark:text-indigo-400 mt-2 font-mono">
                        Rp {{ number_format($totalAset, 0, ',', '.') }}
                    </h3>
                    <p class="text-[10px] text-slate-400 mt-1">Posisi per {{ \Carbon\Carbon::parse($endDate)->format('d M') }}</p>
                </div>
                <i class="material-icons absolute right-2 bottom-2 text-6xl text-indigo-500 opacity-5">account_balance</i>
            </div>

            {{-- 3. Cash Position --}}
            <div class="card p-5 border-t-4 border-blue-500 shadow-sm relative overflow-hidden">
                <div class="relative z-10">
                    <p class="text-xs font-bold text-slate-500 uppercase tracking-wider">Posisi Kas Akhir</p>
                    <h3 class="text-2xl font-extrabold text-blue-600 dark:text-blue-400 mt-2 font-mono">
                        Rp {{ number_format($cash_ending, 0, ',', '.') }}
                    </h3>
                    <p class="text-[10px] text-slate-400 mt-1">Likuiditas tersedia</p>
                </div>
                <i class="material-icons absolute right-2 bottom-2 text-6xl text-blue-500 opacity-5">savings</i>
            </div>

            {{-- 4. Net Cash Flow --}}
            <div class="card p-5 border-t-4 {{ $net_increase_cash >= 0 ? 'border-emerald-500' : 'border-amber-500' }} shadow-sm relative overflow-hidden">
                <div class="relative z-10">
                    <p class="text-xs font-bold text-slate-500 uppercase tracking-wider">Arus Kas Bersih</p>
                    <h3 class="text-2xl font-extrabold mt-2 font-mono {{ $net_increase_cash >= 0 ? 'text-emerald-600' : 'text-amber-600' }}">
                        {{ $net_increase_cash >= 0 ? '+' : '' }} Rp {{ number_format($net_increase_cash, 0, ',', '.') }}
                    </h3>
                    <p class="text-[10px] text-slate-400 mt-1">Kenaikan/Penurunan Kas</p>
                </div>
                <i class="material-icons absolute right-2 bottom-2 text-6xl opacity-5 {{ $net_increase_cash >= 0 ? 'text-emerald-500' : 'text-amber-500' }}">timeline</i>
            </div>
        </div>

        {{-- NAVIGATION TABS (Pill Style) --}}
        <div class="mb-8 flex justify-center">
            <nav class="flex p-1 space-x-1 bg-slate-200 dark:bg-slate-700 rounded-xl overflow-x-auto max-w-full" aria-label="Tabs">
                
                <button @click="activeTab = 'profit_loss'" 
                        :class="activeTab === 'profit_loss' ? 'bg-white dark:bg-slate-600 text-indigo-600 dark:text-indigo-300 shadow-sm' : 'text-slate-500 hover:text-slate-700 dark:text-slate-400 dark:hover:text-slate-200'"
                        class="px-4 py-2 rounded-lg text-xs font-bold transition-all flex items-center gap-2 whitespace-nowrap">
                    <i class="material-icons text-[16px]">pie_chart</i> Laba Rugi
                </button>

                <button @click="activeTab = 'balance_sheet'" 
                        :class="activeTab === 'balance_sheet' ? 'bg-white dark:bg-slate-600 text-indigo-600 dark:text-indigo-300 shadow-sm' : 'text-slate-500 hover:text-slate-700 dark:text-slate-400 dark:hover:text-slate-200'"
                        class="px-4 py-2 rounded-lg text-xs font-bold transition-all flex items-center gap-2 whitespace-nowrap">
                    <i class="material-icons text-[16px]">account_balance</i> Neraca
                </button>

                <button @click="activeTab = 'cash_flow'" 
                        :class="activeTab === 'cash_flow' ? 'bg-white dark:bg-slate-600 text-indigo-600 dark:text-indigo-300 shadow-sm' : 'text-slate-500 hover:text-slate-700 dark:text-slate-400 dark:hover:text-slate-200'"
                        class="px-4 py-2 rounded-lg text-xs font-bold transition-all flex items-center gap-2 whitespace-nowrap">
                    <i class="material-icons text-[16px]">waterfall_chart</i> Arus Kas
                </button>

                <button @click="activeTab = 'details'" 
                        :class="activeTab === 'details' ? 'bg-white dark:bg-slate-600 text-indigo-600 dark:text-indigo-300 shadow-sm' : 'text-slate-500 hover:text-slate-700 dark:text-slate-400 dark:hover:text-slate-200'"
                        class="px-4 py-2 rounded-lg text-xs font-bold transition-all flex items-center gap-2 whitespace-nowrap">
                    <i class="material-icons text-[16px]">list_alt</i> Rincian Hutang/Piutang
                </button>
            </nav>
        </div>
    </div>

    {{-- ==================================================================== --}}
    {{-- PRINT HEADER (Hanya Muncul di Kertas) --}}
    {{-- ==================================================================== --}}
    <div class="hidden print:block text-center mb-8 border-b-2 border-black pb-4">
        <h2 class="text-3xl font-extrabold uppercase tracking-widest text-black">Laporan Keuangan Konsolidasi</h2>
        <h3 class="text-xl font-bold mt-2">{{ config('app.name') }}</h3>
        <p class="text-sm mt-1">Periode: <strong>{{ \Carbon\Carbon::parse($startDate)->format('d F Y') }}</strong> s/d <strong>{{ \Carbon\Carbon::parse($endDate)->format('d F Y') }}</strong></p>
    </div>

    {{-- ==================================================================== --}}
    {{-- 1. LABA RUGI (INCOME STATEMENT) --}}
    {{-- ==================================================================== --}}
    <div x-show="activeTab === 'profit_loss'" class="print:block print:break-after-page mb-8 animate-fade-in">
        <div class="card border border-slate-200 dark:border-slate-700 shadow-md">
            {{-- Header Laporan --}}
            <div class="bg-slate-50 dark:bg-slate-800 p-4 border-b border-slate-200 dark:border-slate-700 flex items-center gap-3">
                <div class="p-2 bg-white dark:bg-slate-700 rounded-lg border border-slate-200 dark:border-slate-600 shadow-sm">
                    <i class="material-icons text-indigo-600">pie_chart</i>
                </div>
                <div>
                    <h2 class="text-lg font-bold text-slate-800 dark:text-white">Laporan Laba Rugi</h2>
                    <p class="text-xs text-slate-500">Income Statement (Standard)</p>
                </div>
            </div>

            <div class="p-0 overflow-x-auto">
                <table class="w-full text-sm">
                    {{-- PENDAPATAN --}}
                    <thead class="bg-emerald-50/50 dark:bg-emerald-900/10">
                        <tr>
                            <th class="px-6 py-3 text-left font-bold text-emerald-800 dark:text-emerald-400 uppercase tracking-wider text-xs" colspan="2">
                                <i class="material-icons text-[14px] align-text-bottom mr-1">trending_up</i> Pendapatan (Revenue)
                            </th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 dark:divide-slate-700">
                        @foreach($labaRugi_pendapatan as $acc)
                            <tr class="hover:bg-slate-50 dark:hover:bg-slate-800/50">
                                <td class="px-6 py-2.5 flex gap-3">
                                    <span class="font-mono text-xs font-bold text-slate-400 bg-slate-100 dark:bg-slate-800 px-1.5 py-0.5 rounded">{{ $acc->account_number }}</span>
                                    <span class="text-slate-700 dark:text-slate-300">{{ $acc->account_name }}</span>
                                </td>
                                <td class="px-6 py-2.5 text-right font-mono text-slate-700 dark:text-slate-300">
                                    {{ number_format($acc->total_credit - $acc->total_debit, 2, ',', '.') }}
                                </td>
                            </tr>
                        @endforeach
                        <tr class="bg-emerald-50 dark:bg-emerald-900/20 font-bold border-t border-emerald-200">
                            <td class="px-6 py-3 text-right text-emerald-800 dark:text-emerald-400">TOTAL PENDAPATAN</td>
                            <td class="px-6 py-3 text-right font-mono text-emerald-700 dark:text-emerald-400 text-base">
                                Rp {{ number_format($totalPendapatan, 2, ',', '.') }}
                            </td>
                        </tr>
                    </tbody>

                    {{-- HPP --}}
                    <thead class="bg-amber-50/50 dark:bg-amber-900/10">
                        <tr>
                            <th class="px-6 py-3 text-left font-bold text-amber-800 dark:text-amber-400 uppercase tracking-wider text-xs" colspan="2">
                                <i class="material-icons text-[14px] align-text-bottom mr-1">inventory_2</i> Harga Pokok Penjualan (COGS)
                            </th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 dark:divide-slate-700">
                        @foreach($labaRugi_hpp as $acc)
                            <tr class="hover:bg-slate-50 dark:hover:bg-slate-800/50">
                                <td class="px-6 py-2.5 flex gap-3">
                                    <span class="font-mono text-xs font-bold text-slate-400 bg-slate-100 dark:bg-slate-800 px-1.5 py-0.5 rounded">{{ $acc->account_number }}</span>
                                    <span class="text-slate-700 dark:text-slate-300">{{ $acc->account_name }}</span>
                                </td>
                                <td class="px-6 py-2.5 text-right font-mono text-rose-600">
                                    ({{ number_format($acc->total_debit - $acc->total_credit, 2, ',', '.') }})
                                </td>
                            </tr>
                        @endforeach
                        <tr class="bg-amber-50 dark:bg-amber-900/20 font-bold border-t border-amber-200">
                            <td class="px-6 py-3 text-right text-amber-800 dark:text-amber-400">TOTAL HPP</td>
                            <td class="px-6 py-3 text-right font-mono text-rose-600 text-base">
                                (Rp {{ number_format($totalHPP, 2, ',', '.') }})
                            </td>
                        </tr>
                    </tbody>

                    {{-- LABA KOTOR --}}
                    <tbody>
                        <tr class="bg-slate-100 dark:bg-slate-700 font-extrabold border-y-2 border-slate-300 dark:border-slate-600">
                            <td class="px-6 py-4 text-right uppercase text-slate-700 dark:text-white">Laba Kotor (Gross Profit)</td>
                            <td class="px-6 py-4 text-right font-mono text-lg {{ $labaKotor >= 0 ? 'text-indigo-700 dark:text-indigo-400' : 'text-rose-700' }}">
                                Rp {{ number_format($labaKotor, 2, ',', '.') }}
                            </td>
                        </tr>
                    </tbody>

                    {{-- BEBAN --}}
                    <thead class="bg-rose-50/50 dark:bg-rose-900/10">
                        <tr>
                            <th class="px-6 py-3 text-left font-bold text-rose-800 dark:text-rose-400 uppercase tracking-wider text-xs" colspan="2">
                                <i class="material-icons text-[14px] align-text-bottom mr-1">money_off</i> Beban Operasional (Expenses)
                            </th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 dark:divide-slate-700">
                        @foreach($labaRugi_beban as $acc)
                            <tr class="hover:bg-slate-50 dark:hover:bg-slate-800/50">
                                <td class="px-6 py-2.5 flex gap-3">
                                    <span class="font-mono text-xs font-bold text-slate-400 bg-slate-100 dark:bg-slate-800 px-1.5 py-0.5 rounded">{{ $acc->account_number }}</span>
                                    <span class="text-slate-700 dark:text-slate-300">{{ $acc->account_name }}</span>
                                </td>
                                <td class="px-6 py-2.5 text-right font-mono text-rose-600">
                                    ({{ number_format($acc->total_debit - $acc->total_credit, 2, ',', '.') }})
                                </td>
                            </tr>
                        @endforeach
                        <tr class="bg-rose-50 dark:bg-rose-900/20 font-bold border-t border-rose-200">
                            <td class="px-6 py-3 text-right text-rose-800 dark:text-rose-400">TOTAL BEBAN</td>
                            <td class="px-6 py-3 text-right font-mono text-rose-600 text-base">
                                (Rp {{ number_format($totalBeban, 2, ',', '.') }})
                            </td>
                        </tr>
                    </tbody>

                    {{-- LABA BERSIH --}}
                    <tfoot>
                        <tr class="bg-slate-800 text-white dark:bg-black print:bg-slate-200 print:text-black">
                            <td class="px-6 py-6 text-right uppercase font-extrabold tracking-widest text-sm">Laba Bersih (Net Income)</td>
                            <td class="px-6 py-6 text-right font-mono font-extrabold text-2xl">
                                Rp {{ number_format($labaBersih, 2, ',', '.') }}
                            </td>
                        </tr>
                    </tfoot>
                </table>
            </div>
        </div>
    </div>

    {{-- ==================================================================== --}}
    {{-- 2. NERACA (BALANCE SHEET) --}}
    {{-- ==================================================================== --}}
    <div x-show="activeTab === 'balance_sheet'" class="print:block print:break-after-page mb-8 animate-fade-in">
        
        {{-- INDICATOR SEIMBANG / TIDAK SEIMBANG --}}
        @php
            $selisih = $totalAset - $totalLiabilitasDanEkuitas;
            $isBalanced = abs($selisih) < 1.0; // Toleransi rounding error
        @endphp

        <div class="mb-6 flex items-center justify-between p-4 rounded-xl shadow-sm border {{ $isBalanced ? 'bg-emerald-100 border-emerald-500 text-emerald-800' : 'bg-rose-100 border-rose-500 text-rose-800' }}">
            <div class="flex items-center gap-4">
                <div class="p-2 rounded-full {{ $isBalanced ? 'bg-emerald-200' : 'bg-rose-200' }}">
                    <i class="material-icons text-3xl">{{ $isBalanced ? 'balance' : 'error' }}</i>
                </div>
                <div>
                    <h3 class="font-bold text-lg uppercase tracking-wider">{{ $isBalanced ? 'NERACA SEIMBANG' : 'NERACA TIDAK SEIMBANG' }}</h3>
                    <p class="text-xs opacity-80">Assets = Liabilities + Equity</p>
                </div>
            </div>
            @if(!$isBalanced)
                <div class="text-right">
                    <p class="text-xs uppercase font-bold">Selisih</p>
                    <p class="font-mono font-bold text-xl">Rp {{ number_format($selisih, 2, ',', '.') }}</p>
                </div>
            @endif
        </div>

        <div class="card border border-slate-200 dark:border-slate-700 shadow-md">
            <div class="card-header bg-slate-50 dark:bg-slate-800 border-b border-slate-200 dark:border-slate-700 print:bg-transparent print:border-black flex items-center gap-3">
                <div class="p-2 bg-white dark:bg-slate-700 rounded-lg border border-slate-200 dark:border-slate-600 shadow-sm">
                    <i class="material-icons text-indigo-600">account_balance</i>
                </div>
                <div>
                    <h2 class="text-lg font-bold text-slate-800 dark:text-white">Laporan Neraca (Posisi Keuangan)</h2>
                    <p class="text-xs text-slate-500">Balance Sheet per {{ \Carbon\Carbon::parse($endDate)->format('d M Y') }}</p>
                </div>
            </div>

            <div class="card-body p-0">
                <div class="grid grid-cols-1 md:grid-cols-2 divide-y md:divide-y-0 md:divide-x divide-slate-200 dark:divide-slate-700">
                    
                    {{-- KIRI: ASET --}}
                    <div>
                        <div class="bg-emerald-50 dark:bg-emerald-900/20 px-6 py-3 border-b border-emerald-100 dark:border-slate-700">
                            <h3 class="font-bold text-emerald-800 dark:text-emerald-400 uppercase tracking-wide flex items-center gap-2">
                                <i class="material-icons text-[18px]">add_business</i> ASET (ASSETS)
                            </h3>
                        </div>
                        <table class="w-full text-sm">
                            <tbody class="divide-y divide-slate-100 dark:divide-slate-700">
                                @foreach($neraca_aset as $acc)
                                    <tr class="hover:bg-slate-50 dark:hover:bg-slate-800/50">
                                        <td class="px-6 py-2.5 flex gap-3">
                                            <span class="font-mono text-xs font-bold text-slate-400 bg-slate-100 dark:bg-slate-800 px-1.5 py-0.5 rounded">{{ $acc->account_number }}</span>
                                            <span class="text-slate-700 dark:text-slate-300">{{ $acc->account_name }}</span>
                                        </td>
                                        <td class="px-6 py-2.5 text-right font-mono font-medium text-slate-700 dark:text-slate-300">
                                            {{ number_format($acc->balance, 2, ',', '.') }}
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                            <tfoot class="bg-emerald-100 dark:bg-emerald-900/40 font-bold border-t-2 border-emerald-500">
                                <tr>
                                    <td class="px-6 py-4 uppercase text-emerald-900 dark:text-emerald-100">Total Aset</td>
                                    <td class="px-6 py-4 text-right font-mono text-emerald-800 dark:text-emerald-100 text-lg">
                                        Rp {{ number_format($totalAset, 2, ',', '.') }}
                                    </td>
                                </tr>
                            </tfoot>
                        </table>
                    </div>

                    {{-- KANAN: LIABILITAS & EKUITAS --}}
                    <div>
                        {{-- Liabilitas --}}
                        <div class="bg-rose-50 dark:bg-rose-900/20 px-6 py-3 border-b border-rose-100 dark:border-slate-700">
                            <h3 class="font-bold text-rose-800 dark:text-rose-400 uppercase tracking-wide flex items-center gap-2">
                                <i class="material-icons text-[18px]">money_off</i> LIABILITAS (LIABILITIES)
                            </h3>
                        </div>
                        <table class="w-full text-sm">
                            <tbody class="divide-y divide-slate-100 dark:divide-slate-700">
                                @foreach($neraca_liabilitas as $acc)
                                    <tr class="hover:bg-slate-50 dark:hover:bg-slate-800/50">
                                        <td class="px-6 py-2.5 flex gap-3">
                                            <span class="font-mono text-xs font-bold text-slate-400 bg-slate-100 dark:bg-slate-800 px-1.5 py-0.5 rounded">{{ $acc->account_number }}</span>
                                            <span class="text-slate-700 dark:text-slate-300">{{ $acc->account_name }}</span>
                                        </td>
                                        <td class="px-6 py-2.5 text-right font-mono font-medium text-slate-700 dark:text-slate-300">
                                            {{ number_format($acc->balance, 2, ',', '.') }}
                                        </td>
                                    </tr>
                                @endforeach
                                <tr class="bg-rose-50 dark:bg-rose-900/20 font-bold">
                                    <td class="px-6 py-2 text-right text-xs uppercase text-rose-800">Total Liabilitas</td>
                                    <td class="px-6 py-2 text-right font-mono text-rose-800">
                                        {{ number_format($totalLiabilitas, 2, ',', '.') }}
                                    </td>
                                </tr>
                            </tbody>
                        </table>

                        {{-- Ekuitas --}}
                        <div class="bg-indigo-50 dark:bg-indigo-900/20 px-6 py-3 border-y border-indigo-100 dark:border-slate-700">
                            <h3 class="font-bold text-indigo-800 dark:text-indigo-400 uppercase tracking-wide flex items-center gap-2">
                                <i class="material-icons text-[18px]">pie_chart</i> EKUITAS (EQUITY)
                            </h3>
                        </div>
                        <table class="w-full text-sm">
                            <tbody class="divide-y divide-slate-100 dark:divide-slate-700">
                                @foreach($neraca_ekuitas_non_pl as $acc)
                                    <tr class="hover:bg-slate-50 dark:hover:bg-slate-800/50">
                                        <td class="px-6 py-2.5 flex gap-3">
                                            <span class="font-mono text-xs font-bold text-slate-400 bg-slate-100 dark:bg-slate-800 px-1.5 py-0.5 rounded">{{ $acc->account_number }}</span>
                                            <span class="text-slate-700 dark:text-slate-300">{{ $acc->account_name }}</span>
                                        </td>
                                        <td class="px-6 py-2.5 text-right font-mono font-medium text-slate-700 dark:text-slate-300">
                                            {{ number_format($acc->balance, 2, ',', '.') }}
                                        </td>
                                    </tr>
                                @endforeach
                                {{-- Laba Ditahan --}}
                                <tr class="bg-indigo-50/40 dark:bg-indigo-900/10">
                                    <td class="px-6 py-3 flex gap-3 font-bold">
                                        <span class="text-indigo-700 dark:text-indigo-300">Akumulasi Laba/Rugi (Retained Earnings)</span>
                                    </td>
                                    <td class="px-6 py-3 text-right font-mono font-bold text-indigo-700 dark:text-indigo-300">
                                        {{ number_format($ekuitas_labaRugiAkumulasi, 2, ',', '.') }}
                                    </td>
                                </tr>
                            </tbody>
                            <tfoot class="bg-slate-100 dark:bg-slate-800 font-bold border-t-2 border-slate-400">
                                <tr>
                                    <td class="px-6 py-4 uppercase text-slate-700 dark:text-slate-200">Total Liabilitas & Ekuitas</td>
                                    <td class="px-6 py-4 text-right font-mono text-slate-800 dark:text-white text-lg">
                                        Rp {{ number_format($totalLiabilitasDanEkuitas, 2, ',', '.') }}
                                    </td>
                                </tr>
                            </tfoot>
                        </table>
                    </div>

                </div>
            </div>
        </div>
    </div>

    {{-- ==================================================================== --}}
    {{-- 3. ARUS KAS (CASH FLOW) --}}
    {{-- ==================================================================== --}}
    <div x-show="activeTab === 'cash_flow'" class="print:block print:break-after-page mb-8 animate-fade-in">
        <div class="card border border-slate-200 dark:border-slate-700 shadow-md">
            <div class="card-header bg-slate-50 dark:bg-slate-800 border-b border-slate-200 dark:border-slate-700 flex items-center gap-3">
                <div class="p-2 bg-white dark:bg-slate-700 rounded-lg border border-slate-200 dark:border-slate-600 shadow-sm">
                    <i class="material-icons text-blue-600">waterfall_chart</i>
                </div>
                <div>
                    <h2 class="text-lg font-bold text-slate-800 dark:text-white">Laporan Arus Kas</h2>
                    <p class="text-xs text-slate-500">Metode Tidak Langsung (Indirect Method)</p>
                </div>
            </div>

            <div class="p-0 overflow-x-auto">
                <table class="w-full text-sm">
                    {{-- OPERASI --}}
                    <thead class="bg-slate-100 dark:bg-slate-900/50">
                        <tr>
                            <th class="px-6 py-3 text-left font-bold text-slate-600 dark:text-slate-300 uppercase tracking-wider text-xs" colspan="2">
                                <i class="material-icons text-[14px] align-text-bottom mr-1">storefront</i> Aktivitas Operasi
                            </th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 dark:divide-slate-700">
                        <tr>
                            <td class="px-6 py-2.5 font-bold">Laba Bersih (Net Income)</td>
                            <td class="px-6 py-2.5 text-right font-mono font-bold">{{ number_format($cf_operating_net_income, 2, ',', '.') }}</td>
                        </tr>
                        <tr><td class="px-6 py-1 text-xs text-slate-400 uppercase font-bold tracking-wider pl-10">Penyesuaian Non-Kas:</td><td></td></tr>
                        <tr>
                            <td class="px-6 py-1 pl-10 text-slate-600 dark:text-slate-400">Depresiasi & Amortisasi</td>
                            <td class="px-6 py-1 text-right font-mono text-slate-600 dark:text-slate-400">{{ number_format($cf_operating_depreciation, 2, ',', '.') }}</td>
                        </tr>
                        <tr><td class="px-6 py-1 text-xs text-slate-400 uppercase font-bold tracking-wider pl-10 mt-2">Perubahan Modal Kerja:</td><td></td></tr>
                        <tr>
                            <td class="px-6 py-1 pl-10 text-slate-600 dark:text-slate-400">Piutang Usaha (AR)</td>
                            <td class="px-6 py-1 text-right font-mono text-slate-600 dark:text-slate-400">{{ number_format($cf_change_ar, 2, ',', '.') }}</td>
                        </tr>
                        <tr>
                            <td class="px-6 py-1 pl-10 text-slate-600 dark:text-slate-400">Persediaan (Inventory)</td>
                            <td class="px-6 py-1 text-right font-mono text-slate-600 dark:text-slate-400">{{ number_format($cf_change_inventory, 2, ',', '.') }}</td>
                        </tr>
                        <tr>
                            <td class="px-6 py-1 pl-10 text-slate-600 dark:text-slate-400">Hutang Usaha (AP)</td>
                            <td class="px-6 py-1 text-right font-mono text-slate-600 dark:text-slate-400">{{ number_format($cf_change_ap, 2, ',', '.') }}</td>
                        </tr>
                        <tr class="bg-slate-50 dark:bg-slate-800 font-bold border-t border-slate-200">
                            <td class="px-6 py-3 italic">Kas Bersih dari Operasi</td>
                            <td class="px-6 py-3 text-right font-mono text-slate-800 dark:text-white">{{ number_format($total_cash_from_operations, 2, ',', '.') }}</td>
                        </tr>
                    </tbody>

                    {{-- INVESTASI --}}
                    <thead class="bg-slate-100 dark:bg-slate-900/50">
                        <tr>
                            <th class="px-6 py-3 text-left font-bold text-slate-600 dark:text-slate-300 uppercase tracking-wider text-xs" colspan="2">
                                <i class="material-icons text-[14px] align-text-bottom mr-1">domain</i> Aktivitas Investasi
                            </th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td class="px-6 py-2 pl-10 text-slate-600 dark:text-slate-400">Pembelian Aset Tetap (CAPEX)</td>
                            <td class="px-6 py-2 text-right font-mono text-slate-600 dark:text-slate-400">{{ number_format($cf_investing_purchase_asset, 2, ',', '.') }}</td>
                        </tr>
                        <tr class="bg-slate-50 dark:bg-slate-800 font-bold border-t border-slate-200">
                            <td class="px-6 py-3 italic">Kas Bersih dari Investasi</td>
                            <td class="px-6 py-3 text-right font-mono text-slate-800 dark:text-white">{{ number_format($total_cash_from_investing, 2, ',', '.') }}</td>
                        </tr>
                    </tbody>

                    {{-- PENDANAAN --}}
                    <thead class="bg-slate-100 dark:bg-slate-900/50">
                        <tr>
                            <th class="px-6 py-3 text-left font-bold text-slate-600 dark:text-slate-300 uppercase tracking-wider text-xs" colspan="2">
                                <i class="material-icons text-[14px] align-text-bottom mr-1">paid</i> Aktivitas Pendanaan
                            </th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td class="px-6 py-2 pl-10 text-slate-600 dark:text-slate-400">Setoran Modal</td>
                            <td class="px-6 py-2 text-right font-mono text-slate-600 dark:text-slate-400">{{ number_format($cf_financing_capital_in, 2, ',', '.') }}</td>
                        </tr>
                        <tr>
                            <td class="px-6 py-2 pl-10 text-slate-600 dark:text-slate-400">Penarikan Modal (Prive/Dividen)</td>
                            <td class="px-6 py-2 text-right font-mono text-slate-600 dark:text-slate-400">({{ number_format(abs($cf_financing_drawing), 2, ',', '.') }})</td>
                        </tr>
                         <tr>
                            <td class="px-6 py-2 pl-10 text-slate-600 dark:text-slate-400">Penerimaan Pinjaman</td>
                            <td class="px-6 py-2 text-right font-mono text-slate-600 dark:text-slate-400">{{ number_format($cf_financing_loan_in, 2, ',', '.') }}</td>
                        </tr>
                        <tr>
                            <td class="px-6 py-2 pl-10 text-slate-600 dark:text-slate-400">Pelunasan Pinjaman</td>
                            <td class="px-6 py-2 text-right font-mono text-slate-600 dark:text-slate-400">({{ number_format(abs($cf_financing_loan_pay), 2, ',', '.') }})</td>
                        </tr>
                        <tr class="bg-slate-50 dark:bg-slate-800 font-bold border-t border-slate-200">
                            <td class="px-6 py-3 italic">Kas Bersih dari Pendanaan</td>
                            <td class="px-6 py-3 text-right font-mono text-slate-800 dark:text-white">{{ number_format($total_cash_from_financing, 2, ',', '.') }}</td>
                        </tr>
                    </tbody>

                    {{-- SUMMARY --}}
                    <tfoot>
                        <tr class="bg-slate-900 text-white dark:bg-black print:bg-slate-200 print:text-black">
                            <td class="px-6 py-4 font-bold uppercase tracking-wide">Kenaikan (Penurunan) Kas Bersih</td>
                            <td class="px-6 py-4 text-right font-mono font-bold">{{ number_format($net_increase_cash, 2, ',', '.') }}</td>
                        </tr>
                        <tr class="bg-white dark:bg-slate-800">
                            <td class="px-6 py-3 italic text-slate-500">Saldo Kas Awal Periode</td>
                            <td class="px-6 py-3 text-right font-mono text-slate-500">{{ number_format($cash_beginning, 2, ',', '.') }}</td>
                        </tr>
                        <tr class="bg-blue-600 text-white text-lg print:bg-blue-200 print:text-black">
                            <td class="px-6 py-5 font-extrabold uppercase tracking-widest">Saldo Kas Akhir Periode</td>
                            <td class="px-6 py-5 text-right font-mono font-extrabold">{{ number_format($cash_ending, 2, ',', '.') }}</td>
                        </tr>
                    </tfoot>
                </table>
            </div>
        </div>
    </div>

    {{-- ==================================================================== --}}
    {{-- 4. DETAILS (AR/AP) --}}
    {{-- ==================================================================== --}}
    <div x-show="activeTab === 'details'" class="print:block print:break-after-page mb-8 animate-fade-in">
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
            
            {{-- PIUTANG --}}
            <div class="card border border-slate-200 dark:border-slate-700 shadow-sm">
                <div class="card-header bg-emerald-50 dark:bg-emerald-900/20 border-b border-emerald-100 flex items-center gap-3">
                    <i class="material-icons text-emerald-600">trending_up</i>
                    <h3 class="card-header-title text-emerald-800 dark:text-emerald-400">Rincian Piutang Klien (AR)</h3>
                </div>
                <div class="table-container max-h-[500px] overflow-y-auto">
                    <table class="table-modern w-full">
                        <thead class="bg-slate-50 sticky top-0">
                            <tr>
                                <th>Klien</th>
                                <th>Invoice</th>
                                <th class="text-right">Sisa Tagihan</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($laporanPiutang as $inv)
                                @if($inv->remaining_balance > 0)
                                    <tr>
                                        <td class="font-bold text-xs">{{ $inv->client->client_name ?? 'Umum' }}</td>
                                        <td class="font-mono text-xs">{{ $inv->invoice_number }}</td>
                                        <td class="text-right font-mono font-bold text-xs text-emerald-600">
                                            {{ number_format($inv->remaining_balance, 0, ',', '.') }}
                                        </td>
                                    </tr>
                                @endif
                            @empty
                                <tr><td colspan="3" class="text-center p-4 text-xs text-slate-400">Tidak ada piutang.</td></tr>
                            @endforelse
                        </tbody>
                        <tfoot class="bg-emerald-100 sticky bottom-0">
                            <tr>
                                <td colspan="2" class="text-right font-bold text-xs uppercase p-3">Total Piutang</td>
                                <td class="text-right font-bold font-mono text-sm p-3">{{ number_format($totalPiutang_SL, 0, ',', '.') }}</td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            </div>

            {{-- HUTANG --}}
            <div class="card border border-slate-200 dark:border-slate-700 shadow-sm">
                <div class="card-header bg-rose-50 dark:bg-rose-900/20 border-b border-rose-100 flex items-center gap-3">
                    <i class="material-icons text-rose-600">trending_down</i>
                    <h3 class="card-header-title text-rose-800 dark:text-rose-400">Rincian Hutang Supplier (AP)</h3>
                </div>
                <div class="table-container max-h-[500px] overflow-y-auto">
                    <table class="table-modern w-full">
                        <thead class="bg-slate-50 sticky top-0">
                            <tr>
                                <th>Supplier</th>
                                <th>No PO</th>
                                <th class="text-right">Sisa Hutang</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($laporanUtang as $po)
                                @if($po->remaining_balance > 0)
                                    <tr>
                                        <td class="font-bold text-xs">{{ $po->supplier->supplier_name ?? 'Umum' }}</td>
                                        <td class="font-mono text-xs">{{ $po->po_number }}</td>
                                        <td class="text-right font-mono font-bold text-xs text-rose-600">
                                            {{ number_format($po->remaining_balance, 0, ',', '.') }}
                                        </td>
                                    </tr>
                                @endif
                            @empty
                                <tr><td colspan="3" class="text-center p-4 text-xs text-slate-400">Tidak ada hutang.</td></tr>
                            @endforelse
                        </tbody>
                        <tfoot class="bg-rose-100 sticky bottom-0">
                            <tr>
                                <td colspan="2" class="text-right font-bold text-xs uppercase p-3">Total Hutang</td>
                                <td class="text-right font-bold font-mono text-sm p-3">{{ number_format($totalUtang_SL, 0, ',', '.') }}</td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            </div>

        </div>
    </div>

</div>
@endsection
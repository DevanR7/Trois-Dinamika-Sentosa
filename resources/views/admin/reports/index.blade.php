@extends('admin.layouts.app')

@section('title', 'Laporan Keuangan')

@section('content')

    {{-- 1. HEADER & FILTER --}}
    <div class="card mb-6 border-l-4 border-indigo-600">
        <div class="card-body py-4 flex flex-col md:flex-row md:items-center justify-between gap-4">
            <div>
                <h1 class="text-xl font-bold text-slate-800 dark:text-white">Laporan Keuangan</h1>
                <p class="text-xs text-slate-500 dark:text-slate-400 mt-1">
                    Periode: <span class="font-mono font-semibold">{{ \Carbon\Carbon::parse($startDate)->format('d M Y') }}</span> s/d <span class="font-mono font-semibold">{{ \Carbon\Carbon::parse($endDate)->format('d M Y') }}</span>
                </p>
            </div>
            
            <form action="{{ route('admin.reports.index') }}" method="GET" class="flex items-end gap-2 bg-slate-50 dark:bg-slate-800 p-2 rounded-lg border border-slate-200 dark:border-slate-700">
                <div>
                    <label class="block text-[10px] uppercase font-bold text-slate-400 mb-1">Dari</label>
                    <input type="date" name="start_date" class="form-input py-1.5 text-xs h-9" value="{{ $startDate }}">
                </div>
                <div>
                    <label class="block text-[10px] uppercase font-bold text-slate-400 mb-1">Sampai</label>
                    <input type="date" name="end_date" class="form-input py-1.5 text-xs h-9" value="{{ $endDate }}">
                </div>
                <button type="submit" class="btn btn-primary h-9 px-4">
                    <i class="material-icons text-sm">filter_list</i>
                </button>
            </form>
        </div>
    </div>

    {{-- 2. FINANCIAL HIGHLIGHTS --}}
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
        {{-- Pendapatan --}}
        <div class="card p-4 flex items-center justify-between">
            <div>
                <div class="text-[10px] font-bold text-slate-400 uppercase tracking-wider">Total Pendapatan</div>
                <div class="text-xl font-extrabold text-emerald-600 dark:text-emerald-400 mt-1">
                    Rp {{ number_format($totalPendapatan, 0, ',', '.') }}
                </div>
            </div>
            <div class="w-10 h-10 rounded-full bg-emerald-50 text-emerald-600 flex items-center justify-center dark:bg-emerald-900/30 dark:text-emerald-400">
                <i class="material-icons">monetization_on</i>
            </div>
        </div>

        {{-- Total Beban --}}
        <div class="card p-4 flex items-center justify-between">
            <div>
                <div class="text-[10px] font-bold text-slate-400 uppercase tracking-wider">Total Biaya & HPP</div>
                <div class="text-xl font-extrabold text-rose-600 dark:text-rose-400 mt-1">
                    Rp {{ number_format($totalHPP + $totalBeban, 0, ',', '.') }}
                </div>
            </div>
            <div class="w-10 h-10 rounded-full bg-rose-50 text-rose-600 flex items-center justify-center dark:bg-rose-900/30 dark:text-rose-400">
                <i class="material-icons">money_off</i>
            </div>
        </div>

        {{-- Laba Bersih --}}
        <div class="card p-4 flex items-center justify-between border border-indigo-100 dark:border-indigo-900 bg-indigo-50/30 dark:bg-indigo-900/10">
            <div>
                <div class="text-[10px] font-bold text-indigo-500 dark:text-indigo-400 uppercase tracking-wider">Laba Bersih</div>
                <div class="text-xl font-extrabold text-indigo-700 dark:text-indigo-300 mt-1">
                    Rp {{ number_format($labaBersih, 0, ',', '.') }}
                </div>
            </div>
            <div class="w-10 h-10 rounded-full bg-indigo-100 text-indigo-600 flex items-center justify-center dark:bg-indigo-900/30 dark:text-indigo-400">
                <i class="material-icons">ssid_chart</i>
            </div>
        </div>

        {{-- Posisi Kas --}}
        <div class="card p-4 flex items-center justify-between">
            <div>
                <div class="text-[10px] font-bold text-slate-400 uppercase tracking-wider">Posisi Kas Akhir</div>
                <div class="text-xl font-extrabold text-slate-700 dark:text-slate-200 mt-1">
                    Rp {{ number_format($cash_ending, 0, ',', '.') }}
                </div>
            </div>
            <div class="w-10 h-10 rounded-full bg-slate-100 text-slate-600 flex items-center justify-center dark:bg-slate-700 dark:text-slate-300">
                <i class="material-icons">account_balance_wallet</i>
            </div>
        </div>
    </div>

    {{-- 3. MAIN REPORTS TABS --}}
    <div x-data="{ activeTab: 'profit_loss' }">
        
        {{-- Navigation Tabs --}}
        <div class="flex flex-wrap gap-2 mb-6 border-b border-slate-200 dark:border-slate-700">
            <button @click="activeTab = 'profit_loss'" 
                    :class="activeTab === 'profit_loss' ? 'border-indigo-500 text-indigo-600 bg-indigo-50 dark:bg-indigo-900/20 dark:text-indigo-300' : 'border-transparent text-slate-500 hover:text-slate-700 hover:bg-slate-50 dark:text-slate-400 dark:hover:text-slate-200 dark:hover:bg-slate-800'"
                    class="px-5 py-3 rounded-t-xl text-sm font-bold border-b-2 transition-all flex items-center gap-2">
                <i class="material-icons text-sm">trending_up</i> Laba Rugi
            </button>
            <button @click="activeTab = 'balance_sheet'" 
                    :class="activeTab === 'balance_sheet' ? 'border-indigo-500 text-indigo-600 bg-indigo-50 dark:bg-indigo-900/20 dark:text-indigo-300' : 'border-transparent text-slate-500 hover:text-slate-700 hover:bg-slate-50 dark:text-slate-400 dark:hover:text-slate-200 dark:hover:bg-slate-800'"
                    class="px-5 py-3 rounded-t-xl text-sm font-bold border-b-2 transition-all flex items-center gap-2 relative">
                <i class="material-icons text-sm">account_balance</i> Neraca
                @if(abs($totalAset - ($totalLiabilitas + $totalEkuitas)) > 1)
                    <span class="absolute top-2 right-2 w-2 h-2 rounded-full bg-rose-500"></span>
                @endif
            </button>
            <button @click="activeTab = 'cash_flow'" 
                    :class="activeTab === 'cash_flow' ? 'border-indigo-500 text-indigo-600 bg-indigo-50 dark:bg-indigo-900/20 dark:text-indigo-300' : 'border-transparent text-slate-500 hover:text-slate-700 hover:bg-slate-50 dark:text-slate-400 dark:hover:text-slate-200 dark:hover:bg-slate-800'"
                    class="px-5 py-3 rounded-t-xl text-sm font-bold border-b-2 transition-all flex items-center gap-2">
                <i class="material-icons text-sm">payments</i> Arus Kas
            </button>
            <button @click="activeTab = 'sub_ledgers'" 
                    :class="activeTab === 'sub_ledgers' ? 'border-indigo-500 text-indigo-600 bg-indigo-50 dark:bg-indigo-900/20 dark:text-indigo-300' : 'border-transparent text-slate-500 hover:text-slate-700 hover:bg-slate-50 dark:text-slate-400 dark:hover:text-slate-200 dark:hover:bg-slate-800'"
                    class="px-5 py-3 rounded-t-xl text-sm font-bold border-b-2 transition-all flex items-center gap-2">
                <i class="material-icons text-sm">list_alt</i> Hutang/Piutang
            </button>
        </div>

        {{-- ================= TAB 1: LABA RUGI ================= --}}
        <div x-show="activeTab === 'profit_loss'" x-transition.opacity>
            <div class="card max-w-5xl mx-auto border border-slate-200 dark:border-slate-700 shadow-sm">
                <div class="card-header bg-slate-50 dark:bg-slate-800 border-b border-slate-200 dark:border-slate-700 text-center py-4">
                    <h3 class="font-bold text-lg text-slate-800 dark:text-white uppercase">Laporan Laba Rugi</h3>
                    <p class="text-xs text-slate-500 dark:text-slate-400 mt-1">Periode {{ \Carbon\Carbon::parse($startDate)->format('d M Y') }} - {{ \Carbon\Carbon::parse($endDate)->format('d M Y') }}</p>
                </div>
                <div class="card-body p-0">
                    <table class="w-full text-sm">
                        {{-- A. PENDAPATAN --}}
                        <thead class="bg-indigo-50/50 dark:bg-indigo-900/20">
                            <tr>
                                <th class="px-6 py-2 text-left text-indigo-700 dark:text-indigo-300 font-bold uppercase text-xs">Pendapatan</th>
                                <th class="px-6 py-2 text-right"></th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100 dark:divide-slate-700">
                            @foreach($labaRugi_pendapatan as $acc)
                                <tr class="hover:bg-slate-50 dark:hover:bg-slate-800/50">
                                    <td class="px-6 py-2 text-slate-700 dark:text-slate-300 pl-10">
                                        <a href="{{ route('admin.reports.general-ledger', ['account_id' => $acc->account_id]) }}" class="hover:text-indigo-600 hover:underline">
                                            {{ $acc->account_name }} <span class="text-xs text-slate-400 ml-1">({{ $acc->account_id }})</span>
                                        </a>
                                    </td>
                                    <td class="px-6 py-2 text-right font-mono text-slate-700 dark:text-slate-300">{{ number_format($acc->total_credit - $acc->total_debit, 0, ',', '.') }}</td>
                                </tr>
                            @endforeach
                            <tr class="bg-slate-50 dark:bg-slate-800/80 font-bold border-t border-slate-200 dark:border-slate-700">
                                <td class="px-6 py-2 text-slate-800 dark:text-white pl-10">Total Pendapatan</td>
                                <td class="px-6 py-2 text-right text-indigo-600 dark:text-indigo-400">Rp {{ number_format($totalPendapatan, 0, ',', '.') }}</td>
                            </tr>
                        </tbody>

                        {{-- B. HPP --}}
                        <thead class="bg-slate-100 dark:bg-slate-700/50">
                            <tr>
                                <th class="px-6 py-2 text-left text-slate-600 dark:text-slate-300 font-bold uppercase text-xs">Harga Pokok Penjualan (HPP)</th>
                                <th class="px-6 py-2 text-right"></th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100 dark:divide-slate-700">
                            @foreach($labaRugi_hpp as $acc)
                                <tr class="hover:bg-slate-50 dark:hover:bg-slate-800/50">
                                    <td class="px-6 py-2 text-slate-700 dark:text-slate-300 pl-10">
                                        <a href="{{ route('admin.reports.general-ledger', ['account_id' => $acc->account_id]) }}" class="hover:text-indigo-600 hover:underline">
                                            {{ $acc->account_name }}
                                        </a>
                                    </td>
                                    <td class="px-6 py-2 text-right font-mono text-slate-700 dark:text-slate-300">({{ number_format($acc->total_debit - $acc->total_credit, 0, ',', '.') }})</td>
                                </tr>
                            @endforeach
                            <tr class="bg-slate-50 dark:bg-slate-800/80 font-bold border-y-2 border-slate-200 dark:border-slate-700">
                                <td class="px-6 py-3 text-slate-900 dark:text-white">LABA KOTOR (Gross Profit)</td>
                                <td class="px-6 py-3 text-right text-emerald-600 dark:text-emerald-400 text-lg">Rp {{ number_format($labaKotor, 0, ',', '.') }}</td>
                            </tr>
                        </tbody>

                        {{-- C. BEBAN --}}
                        <thead class="bg-rose-50/50 dark:bg-rose-900/20">
                            <tr>
                                <th class="px-6 py-2 text-left text-rose-700 dark:text-rose-300 font-bold uppercase text-xs">Beban Operasional</th>
                                <th class="px-6 py-2 text-right"></th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100 dark:divide-slate-700">
                            @foreach($labaRugi_beban as $acc)
                                <tr class="hover:bg-slate-50 dark:hover:bg-slate-800/50">
                                    <td class="px-6 py-2 text-slate-700 dark:text-slate-300 pl-10">
                                        <a href="{{ route('admin.reports.general-ledger', ['account_id' => $acc->account_id]) }}" class="hover:text-indigo-600 hover:underline">
                                            {{ $acc->account_name }}
                                        </a>
                                    </td>
                                    <td class="px-6 py-2 text-right font-mono text-slate-700 dark:text-slate-300">({{ number_format($acc->total_debit - $acc->total_credit, 0, ',', '.') }})</td>
                                </tr>
                            @endforeach
                            <tr class="bg-slate-50 dark:bg-slate-800/80 font-bold border-t border-slate-200 dark:border-slate-700">
                                <td class="px-6 py-2 text-slate-800 dark:text-white pl-10">Total Beban</td>
                                <td class="px-6 py-2 text-right text-rose-600 dark:text-rose-400">({{ number_format($totalBeban, 0, ',', '.') }})</td>
                            </tr>
                        </tbody>
                        
                        {{-- NET PROFIT --}}
                        <tfoot class="bg-slate-100 dark:bg-slate-900 border-t-4 border-double border-slate-300 dark:border-slate-600">
                            <tr>
                                <td class="px-6 py-4 text-lg font-extrabold text-slate-900 dark:text-white uppercase">Laba / Rugi Bersih</td>
                                <td class="px-6 py-4 text-right text-xl font-extrabold {{ $labaBersih >= 0 ? 'text-emerald-600 dark:text-emerald-400' : 'text-rose-600 dark:text-rose-400' }}">
                                    Rp {{ number_format($labaBersih, 0, ',', '.') }}
                                </td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            </div>
        </div>

        {{-- ================= TAB 2: NERACA ================= --}}
        <div x-show="activeTab === 'balance_sheet'" x-transition.opacity style="display: none;">
            
            {{-- INDIKATOR BALANCE --}}
            @php
                $balanceDiff = $totalAset - ($totalLiabilitas + $totalEkuitas);
                $isBalanced = abs($balanceDiff) < 1;
            @endphp

            @if($isBalanced)
                <div class="mb-6 p-3 bg-emerald-100 border border-emerald-200 text-emerald-800 rounded-xl flex items-center justify-center gap-2 shadow-sm dark:bg-emerald-900/30 dark:border-emerald-800 dark:text-emerald-300">
                    <i class="material-icons text-emerald-600 dark:text-emerald-400">check_circle</i>
                    <span class="font-bold">Neraca Seimbang (Balanced)</span>
                </div>
            @else
                <div class="mb-6 p-4 bg-rose-100 border-l-4 border-rose-500 text-rose-800 rounded-r-xl shadow-md animate-pulse dark:bg-rose-900/30 dark:border-rose-500 dark:text-rose-300">
                    <div class="flex items-center gap-2 font-bold mb-1">
                        <i class="material-icons">error</i>
                        <span>NERACA TIDAK SEIMBANG!</span>
                    </div>
                    <div class="text-sm">
                        Selisih: <strong>Rp {{ number_format($balanceDiff, 0, ',', '.') }}</strong>. Mohon periksa jurnal manual atau postingan terakhir.
                    </div>
                </div>
            @endif

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                
                {{-- ASET --}}
                <div class="card h-full flex flex-col">
                    <div class="card-header bg-emerald-50 dark:bg-emerald-900/20 border-b border-emerald-100 dark:border-emerald-800 text-center py-3">
                        <h3 class="card-header-title text-emerald-800 dark:text-emerald-300">AKTIVA (ASET)</h3>
                    </div>
                    <div class="card-body p-0 flex-1">
                        <table class="w-full text-sm">
                            <thead class="bg-slate-50 dark:bg-slate-800"><th colspan="2" class="px-4 py-2 text-xs text-left uppercase text-slate-500 dark:text-slate-400 font-bold">Aset Lancar & Tetap</th></thead>
                            <tbody class="divide-y divide-slate-100 dark:divide-slate-700">
                                @foreach($neraca_aset as $acc)
                                    <tr class="hover:bg-slate-50 dark:hover:bg-slate-800/30">
                                        <td class="px-4 py-2 text-slate-700 dark:text-slate-300">
                                            <a href="{{ route('admin.reports.general-ledger', ['account_id' => $acc->account_id]) }}" class="hover:text-indigo-600 hover:underline flex items-center justify-between">
                                                <span>{{ $acc->account_name }}</span>
                                                <span class="text-[10px] text-slate-400 font-mono">{{ $acc->account_number }}</span>
                                            </a>
                                        </td>
                                        <td class="px-4 py-2 text-right font-mono text-slate-700 dark:text-slate-300">{{ number_format($acc->balance, 0, ',', '.') }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                    <div class="bg-emerald-50 dark:bg-emerald-900/20 p-4 border-t border-emerald-100 dark:border-emerald-800">
                        <div class="flex justify-between items-center font-bold text-emerald-900 dark:text-emerald-200">
                            <span>TOTAL ASET</span>
                            <span class="text-lg">Rp {{ number_format($totalAset, 0, ',', '.') }}</span>
                        </div>
                    </div>
                </div>

                {{-- PASIVA --}}
                <div class="card h-full flex flex-col">
                    <div class="card-header bg-rose-50 dark:bg-rose-900/20 border-b border-rose-100 dark:border-rose-800 text-center py-3">
                        <h3 class="card-header-title text-rose-800 dark:text-rose-300">PASIVA (KEWAJIBAN & MODAL)</h3>
                    </div>
                    <div class="card-body p-0 flex-1">
                        <table class="w-full text-sm">
                            <thead class="bg-slate-50 dark:bg-slate-800"><th colspan="2" class="px-4 py-2 text-xs text-left uppercase text-slate-500 dark:text-slate-400 font-bold">Kewajiban (Liabilitas)</th></thead>
                            <tbody class="divide-y divide-slate-100 dark:divide-slate-700">
                                @foreach($neraca_liabilitas as $acc)
                                    <tr class="hover:bg-slate-50 dark:hover:bg-slate-800/30">
                                        <td class="px-4 py-2 text-slate-700 dark:text-slate-300">{{ $acc->account_name }}</td>
                                        <td class="px-4 py-2 text-right font-mono text-slate-700 dark:text-slate-300">{{ number_format($acc->balance, 0, ',', '.') }}</td>
                                    </tr>
                                @endforeach
                                <tr class="bg-slate-50 dark:bg-slate-800 font-semibold text-slate-600 dark:text-slate-300">
                                    <td class="px-4 py-1 text-xs uppercase text-right">Total Kewajiban</td>
                                    <td class="px-4 py-1 text-right text-xs">Rp {{ number_format($totalLiabilitas, 0, ',', '.') }}</td>
                                </tr>
                            </tbody>

                            <thead class="bg-slate-50 dark:bg-slate-800 border-t border-slate-200 dark:border-slate-700"><th colspan="2" class="px-4 py-2 text-xs text-left uppercase text-slate-500 dark:text-slate-400 font-bold">Modal (Ekuitas)</th></thead>
                            <tbody class="divide-y divide-slate-100 dark:divide-slate-700">
                                @foreach($neraca_ekuitas_non_pl as $acc)
                                    <tr class="hover:bg-slate-50 dark:hover:bg-slate-800/30">
                                        <td class="px-4 py-2 text-slate-700 dark:text-slate-300">{{ $acc->account_name }}</td>
                                        <td class="px-4 py-2 text-right font-mono text-slate-700 dark:text-slate-300">{{ number_format($acc->balance, 0, ',', '.') }}</td>
                                    </tr>
                                @endforeach
                                {{-- Laba Rugi Tahun Berjalan --}}
                                <tr class="bg-indigo-50/30 dark:bg-indigo-900/10">
                                    <td class="px-4 py-2 font-bold text-indigo-700 dark:text-indigo-300">Laba/Rugi Tahun Berjalan</td>
                                    <td class="px-4 py-2 text-right font-mono font-bold text-indigo-700 dark:text-indigo-300">{{ number_format($ekuitas_labaRugiAkumulasi, 0, ',', '.') }}</td>
                                </tr>
                                <tr class="bg-slate-50 dark:bg-slate-800 font-semibold text-slate-600 dark:text-slate-300">
                                    <td class="px-4 py-1 text-xs uppercase text-right">Total Ekuitas</td>
                                    <td class="px-4 py-1 text-right text-xs">Rp {{ number_format($totalEkuitas, 0, ',', '.') }}</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                    <div class="bg-rose-50 dark:bg-rose-900/20 p-4 border-t border-rose-100 dark:border-rose-800">
                        <div class="flex justify-between items-center font-bold text-rose-900 dark:text-rose-200">
                            <span>TOTAL PASIVA</span>
                            <span class="text-lg">Rp {{ number_format($totalLiabilitasDanEkuitas, 0, ',', '.') }}</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- ================= TAB 3: ARUS KAS (DIPERBAIKI) ================= --}}
        <div x-show="activeTab === 'cash_flow'" x-transition.opacity style="display: none;">
            <div class="card max-w-4xl mx-auto shadow-sm border border-slate-200 dark:border-slate-700">
                <div class="card-header bg-slate-50 dark:bg-slate-800 border-b border-slate-200 dark:border-slate-700 text-center py-4">
                    <h3 class="font-bold text-lg text-slate-800 dark:text-white">Laporan Arus Kas</h3>
                    <p class="text-xs text-slate-500 dark:text-slate-400">Metode Tidak Langsung (Indirect Method)</p>
                </div>
                <div class="card-body p-0">
                    <table class="w-full text-sm">
                        
                        {{-- Operasional --}}
                        <thead class="bg-slate-100 dark:bg-slate-700"><th colspan="2" class="px-6 py-2 text-left font-bold uppercase text-xs text-slate-600 dark:text-slate-300">Arus Kas dari Aktivitas Operasi</th></thead>
                        <tbody class="divide-y divide-slate-100 dark:divide-slate-700">
                            <tr>
                                <td class="px-6 py-2 font-medium text-slate-700 dark:text-slate-200">Laba Bersih</td>
                                <td class="px-6 py-2 text-right font-mono font-bold text-slate-800 dark:text-white">{{ number_format($cf_operating_net_income, 0, ',', '.') }}</td>
                            </tr>
                            <tr>
                                <td class="px-6 py-2 text-slate-500 dark:text-slate-400 pl-10 text-xs uppercase tracking-wide pt-3">Penyesuaian Non-Kas & Modal Kerja:</td>
                                <td></td>
                            </tr>
                            <tr>
                                <td class="px-6 py-1 text-slate-600 dark:text-slate-300 pl-10">+ Penyusutan (Depresiasi)</td>
                                <td class="px-6 py-1 text-right font-mono text-slate-600 dark:text-slate-300">{{ number_format($cf_operating_depreciation, 0, ',', '.') }}</td>
                            </tr>
                            <tr>
                                <td class="px-6 py-1 text-slate-600 dark:text-slate-300 pl-10">Perubahan Piutang Usaha</td>
                                <td class="px-6 py-1 text-right font-mono text-slate-600 dark:text-slate-300">{{ number_format($cf_change_ar, 0, ',', '.') }}</td>
                            </tr>
                            <tr>
                                <td class="px-6 py-1 text-slate-600 dark:text-slate-300 pl-10">Perubahan Persediaan</td>
                                <td class="px-6 py-1 text-right font-mono text-slate-600 dark:text-slate-300">{{ number_format($cf_change_inventory, 0, ',', '.') }}</td>
                            </tr>
                            <tr>
                                <td class="px-6 py-1 text-slate-600 dark:text-slate-300 pl-10">Perubahan Hutang Dagang</td>
                                <td class="px-6 py-1 text-right font-mono text-slate-600 dark:text-slate-300">{{ number_format($cf_change_ap, 0, ',', '.') }}</td>
                            </tr>
                            <tr class="bg-indigo-50/30 dark:bg-indigo-900/10 font-bold border-t border-slate-200 dark:border-slate-700">
                                <td class="px-6 py-2 text-indigo-800 dark:text-indigo-300">Kas Bersih dari Operasi</td>
                                <td class="px-6 py-2 text-right font-mono text-indigo-700 dark:text-indigo-300">{{ number_format($total_cash_from_operations, 0, ',', '.') }}</td>
                            </tr>
                        </tbody>

                        {{-- Investasi --}}
                        <thead class="bg-slate-100 dark:bg-slate-700 border-t-2 border-slate-200 dark:border-slate-600"><th colspan="2" class="px-6 py-2 text-left font-bold uppercase text-xs text-slate-600 dark:text-slate-300">Arus Kas dari Aktivitas Investasi</th></thead>
                        <tbody class="divide-y divide-slate-100 dark:divide-slate-700">
                            <tr>
                                <td class="px-6 py-2 text-slate-600 dark:text-slate-300 pl-10">Pembelian Aset Tetap</td>
                                <td class="px-6 py-2 text-right font-mono text-slate-600 dark:text-slate-300">{{ number_format($cf_investing_purchase_asset * -1, 0, ',', '.') }}</td>
                            </tr>
                            <tr class="bg-indigo-50/30 dark:bg-indigo-900/10 font-bold border-t border-slate-200 dark:border-slate-700">
                                <td class="px-6 py-2 text-indigo-800 dark:text-indigo-300">Kas Bersih dari Investasi</td>
                                <td class="px-6 py-2 text-right font-mono text-indigo-700 dark:text-indigo-300">{{ number_format($total_cash_from_investing, 0, ',', '.') }}</td>
                            </tr>
                        </tbody>

                        {{-- Pendanaan --}}
                        <thead class="bg-slate-100 dark:bg-slate-700 border-t-2 border-slate-200 dark:border-slate-600"><th colspan="2" class="px-6 py-2 text-left font-bold uppercase text-xs text-slate-600 dark:text-slate-300">Arus Kas dari Aktivitas Pendanaan</th></thead>
                        <tbody class="divide-y divide-slate-100 dark:divide-slate-700">
                            <tr>
                                <td class="px-6 py-2 text-slate-600 dark:text-slate-300 pl-10">Setoran Modal Pemilik</td>
                                <td class="px-6 py-2 text-right font-mono text-slate-600 dark:text-slate-300">{{ number_format($cf_financing_capital_in, 0, ',', '.') }}</td>
                            </tr>
                            <tr>
                                <td class="px-6 py-2 text-slate-600 dark:text-slate-300 pl-10">Prive (Penarikan)</td>
                                <td class="px-6 py-2 text-right font-mono text-slate-600 dark:text-slate-300">{{ number_format($cf_financing_drawing * -1, 0, ',', '.') }}</td>
                            </tr>
                            <tr class="bg-indigo-50/30 dark:bg-indigo-900/10 font-bold border-t border-slate-200 dark:border-slate-700">
                                <td class="px-6 py-2 text-indigo-800 dark:text-indigo-300">Kas Bersih dari Pendanaan</td>
                                <td class="px-6 py-2 text-right font-mono text-indigo-700 dark:text-indigo-300">{{ number_format($total_cash_from_financing, 0, ',', '.') }}</td>
                            </tr>
                        </tbody>

                        <tfoot class="bg-slate-800 dark:bg-slate-900 text-white border-t-4 border-slate-600 dark:border-slate-600">
                            <tr>
                                <td class="px-6 py-3 font-bold uppercase">Kenaikan/Penurunan Kas Bersih</td>
                                <td class="px-6 py-3 text-right font-extrabold text-lg">
                                    Rp {{ number_format($net_increase_cash, 0, ',', '.') }}
                                </td>
                            </tr>
                            <tr class="bg-slate-700 dark:bg-slate-800 text-slate-300">
                                <td class="px-6 py-2 text-sm">Saldo Kas Awal</td>
                                <td class="px-6 py-2 text-right font-mono">Rp {{ number_format($cash_beginning, 0, ',', '.') }}</td>
                            </tr>
                            <tr class="bg-slate-900 dark:bg-black text-emerald-400 font-bold">
                                <td class="px-6 py-3 text-sm uppercase">Saldo Kas Akhir</td>
                                <td class="px-6 py-3 text-right font-mono text-lg">Rp {{ number_format($cash_ending, 0, ',', '.') }}</td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            </div>
        </div>

        {{-- ================= TAB 4: HUTANG PIUTANG ================= --}}
        <div x-show="activeTab === 'sub_ledgers'" x-transition.opacity style="display: none;">
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                
                {{-- Piutang --}}
                <div class="card h-full border-t-4 border-indigo-500">
                    <div class="card-header flex justify-between items-center bg-indigo-50/30 dark:bg-indigo-900/10">
                        <h3 class="card-header-title text-indigo-800 dark:text-indigo-300">Rincian Piutang (Invoice Unpaid)</h3>
                        <span class="badge badge-primary font-mono text-sm">Total: Rp {{ number_format($totalPiutang_SL, 0, ',', '.') }}</span>
                    </div>
                    <div class="table-container max-h-[500px] overflow-y-auto custom-scrollbar">
                        <table class="table-modern w-full">
                            <thead class="sticky top-0 z-10 bg-white dark:bg-slate-800 shadow-sm">
                                <tr>
                                    <th>Klien</th>
                                    <th>No Invoice & Due Date</th>
                                    <th class="text-right">Sisa Tagihan</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-100 dark:divide-slate-700">
                                @forelse($laporanPiutang as $inv)
                                    <tr class="hover:bg-slate-50 dark:hover:bg-slate-800/50">
                                        <td class="text-xs font-bold text-slate-700 dark:text-slate-300">{{ Str::limit($inv->client->client_name, 25) }}</td>
                                        <td>
                                            <a href="{{ route('admin.invoices.show', $inv->invoice_id) }}" class="text-indigo-600 dark:text-indigo-400 hover:underline font-mono text-xs font-bold">
                                                {{ $inv->invoice_number }}
                                            </a>
                                            <div class="text-[10px] text-slate-500 dark:text-slate-400 {{ $inv->due_date < now() ? 'text-rose-500 font-bold' : '' }}">
                                                Jatuh Tempo: {{ $inv->due_date->format('d M Y') }}
                                            </div>
                                        </td>
                                        <td class="text-right font-bold text-slate-700 dark:text-white text-xs font-mono">
                                            {{ number_format($inv->remaining_balance, 0, ',', '.') }}
                                        </td>
                                    </tr>
                                @empty
                                    <tr><td colspan="3" class="text-center p-6 text-slate-400">Tidak ada piutang tertunggak.</td></tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>

                {{-- Hutang --}}
                <div class="card h-full border-t-4 border-rose-500">
                    <div class="card-header flex justify-between items-center bg-rose-50/30 dark:bg-rose-900/10">
                        <h3 class="card-header-title text-rose-800 dark:text-rose-300">Rincian Hutang (PO Unpaid)</h3>
                        <span class="badge badge-danger font-mono text-sm">Total: Rp {{ number_format($totalUtang_SL, 0, ',', '.') }}</span>
                    </div>
                    <div class="table-container max-h-[500px] overflow-y-auto custom-scrollbar">
                        <table class="table-modern w-full">
                            <thead class="sticky top-0 z-10 bg-white dark:bg-slate-800 shadow-sm">
                                <tr>
                                    <th>Supplier</th>
                                    <th>No PO & Due Date</th>
                                    <th class="text-right">Sisa Hutang</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-100 dark:divide-slate-700">
                                @forelse($laporanUtang as $po)
                                    <tr class="hover:bg-slate-50 dark:hover:bg-slate-800/50">
                                        <td class="text-xs font-bold text-slate-700 dark:text-slate-300">{{ Str::limit($po->supplier->supplier_name, 25) }}</td>
                                        <td>
                                            <a href="{{ route('admin.purchase-orders.show', $po->po_id) }}" class="text-indigo-600 dark:text-indigo-400 hover:underline font-mono text-xs font-bold">
                                                {{ $po->po_number }}
                                            </a>
                                            <div class="text-[10px] text-slate-500 dark:text-slate-400">
                                                Jatuh Tempo: {{ optional($po->due_date)->format('d M Y') ?? '-' }}
                                            </div>
                                        </td>
                                        <td class="text-right font-bold text-slate-700 dark:text-white text-xs font-mono">
                                            {{ number_format($po->remaining_balance, 0, ',', '.') }}
                                        </td>
                                    </tr>
                                @empty
                                    <tr><td colspan="3" class="text-center p-6 text-slate-400">Tidak ada hutang tertunggak.</td></tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>

            </div>
        </div>

    </div>

@endsection
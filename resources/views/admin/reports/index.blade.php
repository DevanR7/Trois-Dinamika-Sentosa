@extends('admin.layouts.app')

@section('title', 'Laporan Keuangan')

@section('content')
<div class="max-w-7xl mx-auto pb-20 animate-enter">
    
    {{-- HEADER & FILTER BAR --}}
    <div class="flex flex-col lg:flex-row justify-between items-start lg:items-end gap-6 mb-8">
        <div>
            <h1 class="text-3xl font-extrabold text-slate-800 dark:text-slate-100 tracking-tight">Laporan Keuangan</h1>
            <p class="text-slate-500 dark:text-slate-400 mt-1 flex items-center gap-2">
                <i class="material-icons text-base text-indigo-500 dark:text-indigo-400">analytics</i>
                Ringkasan performa & posisi keuangan (Double Entry).
            </p>
        </div>
        
        <div class="bg-white dark:bg-slate-800 p-1.5 rounded-xl border border-slate-200 dark:border-slate-700 shadow-sm w-full lg:w-auto">
            <form action="{{ route('admin.reports.index') }}" method="GET" class="flex flex-col sm:flex-row items-center gap-2">
                
                {{-- Date Range Picker Styled --}}
                <div class="flex items-center gap-2 bg-slate-50 dark:bg-slate-900 px-4 py-2 rounded-lg border border-slate-100 dark:border-slate-700 w-full sm:w-auto">
                    <i class="material-icons text-slate-400 dark:text-slate-500 text-[18px]">calendar_today</i>
                    
                    {{-- Input Date dengan style dark mode friendly --}}
                    <input type="date" name="start_date" 
                        class="bg-transparent border-none text-sm font-bold text-slate-700 dark:text-slate-200 focus:ring-0 p-0 w-28 cursor-pointer dark:[color-scheme:dark]" 
                        value="{{ $startDate }}">
                    
                    <span class="text-slate-300 dark:text-slate-600 font-light px-1">|</span>
                    
                    <input type="date" name="end_date" 
                        class="bg-transparent border-none text-sm font-bold text-slate-700 dark:text-slate-200 focus:ring-0 p-0 w-28 cursor-pointer dark:[color-scheme:dark]" 
                        value="{{ $endDate }}">
                </div>

                <div class="flex gap-2 w-full sm:w-auto">
                    <button type="submit" class="flex-1 sm:flex-none px-5 py-2 bg-slate-800 hover:bg-slate-900 dark:bg-indigo-600 dark:hover:bg-indigo-700 text-white text-sm font-bold rounded-lg transition flex items-center justify-center gap-2 shadow-md">
                        <i class="material-icons text-[18px]">sync</i> Update
                    </button>
                    <button type="button" onclick="window.print()" class="flex-1 sm:flex-none px-4 py-2 bg-white dark:bg-slate-700 border border-slate-200 dark:border-slate-600 text-slate-700 dark:text-slate-200 text-sm font-bold rounded-lg hover:bg-indigo-50 dark:hover:bg-slate-600 hover:text-indigo-600 hover:border-indigo-100 transition flex items-center justify-center gap-2">
                        <i class="material-icons text-[18px]">print</i> Cetak
                    </button>
                </div>
            </form>
        </div>
    </div>

    {{-- SECTION 1: NERACA (BALANCE SHEET) --}}
    <div class="dashboard-card p-0 overflow-hidden mb-10 shadow-lg border-0 ring-1 ring-slate-900/5 dark:ring-slate-700/50 print-section">
        
        {{-- Header Card --}}
        <div class="px-6 py-5 border-b border-slate-100 dark:border-slate-700 bg-gradient-to-r from-indigo-50 to-white dark:from-slate-800 dark:to-slate-800 flex justify-between items-center">
            <h5 class="font-bold text-lg text-slate-800 dark:text-slate-100 flex items-center gap-2">
                <div class="w-8 h-8 rounded-lg bg-indigo-600 flex items-center justify-center text-white shadow-sm">
                    <i class="material-icons text-[18px]">account_balance</i>
                </div>
                Neraca (Balance Sheet)
            </h5>
            <div class="text-right">
                <span class="block text-[10px] text-slate-400 dark:text-slate-500 uppercase font-bold tracking-wider">Posisi Per Tanggal</span>
                <span class="font-bold text-slate-700 dark:text-slate-300 text-sm">{{ \Carbon\Carbon::parse($endDate)->isoFormat('D MMMM Y') }}</span>
            </div>
        </div>
        
        <div class="grid grid-cols-1 lg:grid-cols-2 divide-y lg:divide-y-0 lg:divide-x divide-slate-100 dark:divide-slate-700 bg-white dark:bg-slate-800">
            
            {{-- KOLOM KIRI: ASET --}}
            <div class="flex flex-col h-full">
                <div class="bg-indigo-50/50 dark:bg-indigo-900/20 px-6 py-3 border-b border-indigo-100/50 dark:border-indigo-800/30 flex justify-between items-center">
                    <span class="text-xs font-extrabold text-indigo-700 dark:text-indigo-400 uppercase tracking-wider">ASET (Harta)</span>
                    <i class="material-icons text-indigo-200 dark:text-indigo-800/50">account_balance_wallet</i>
                </div>
                
                <div class="flex-1">
                    <table class="w-full">
                        <tbody class="divide-y divide-slate-50 dark:divide-slate-700/50">
                            @forelse ($neraca_aset as $account)
                            <tr class="hover:bg-indigo-50/30 dark:hover:bg-indigo-900/10 transition-colors group">
                                <td class="px-6 py-3 text-sm text-slate-600 dark:text-slate-400 group-hover:text-slate-900 dark:group-hover:text-slate-200 font-medium">{{ $account->account_name }}</td>
                                <td class="px-6 py-3 text-sm text-right font-mono font-bold text-slate-700 dark:text-slate-300 group-hover:text-indigo-700 dark:group-hover:text-indigo-400">
                                    Rp {{ number_format($account->balance, 0, ',', '.') }}
                                </td>
                            </tr>
                            @empty
                            <tr><td colspan="2" class="px-6 py-8 text-center text-xs text-slate-400 dark:text-slate-600 italic">Tidak ada data akun aset.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                {{-- Footer Aset --}}
                <div class="bg-white dark:bg-slate-800 border-t border-slate-100 dark:border-slate-700 px-6 py-4 mt-auto">
                    <div class="flex justify-between items-center p-3 rounded-xl bg-indigo-50 dark:bg-indigo-900/20 border border-indigo-100 dark:border-indigo-800">
                        <span class="text-sm font-bold text-indigo-800 dark:text-indigo-300 uppercase">Total Aset</span>
                        <span class="text-lg font-bold text-indigo-700 dark:text-indigo-400 font-mono">Rp {{ number_format($totalAset, 0, ',', '.') }}</span>
                    </div>
                </div>
            </div>

            {{-- KOLOM KANAN: PASIVA --}}
            <div class="flex flex-col h-full">
                <div class="bg-amber-50/50 dark:bg-amber-900/20 px-6 py-3 border-b border-amber-100/50 dark:border-amber-800/30 flex justify-between items-center">
                    <span class="text-xs font-extrabold text-amber-700 dark:text-amber-400 uppercase tracking-wider">LIABILITAS & EKUITAS</span>
                    <i class="material-icons text-amber-200 dark:text-amber-800/50">pie_chart</i>
                </div>
                
                <div class="flex-1">
                    <table class="w-full">
                        <tbody class="divide-y divide-slate-50 dark:divide-slate-700/50">
                            {{-- Section Title: Kewajiban --}}
                            <tr class="bg-slate-50/80 dark:bg-slate-700/50">
                                <td colspan="2" class="px-6 py-1.5 text-[10px] font-bold text-slate-400 dark:text-slate-400 uppercase tracking-wider">Kewajiban (Hutang)</td>
                            </tr>
                            
                            @forelse ($neraca_liabilitas as $account)
                            <tr class="hover:bg-amber-50/30 dark:hover:bg-amber-900/10 transition-colors">
                                <td class="px-6 py-2.5 text-sm text-slate-600 dark:text-slate-400 pl-8">{{ $account->account_name }}</td>
                                <td class="px-6 py-2.5 text-sm text-right font-mono font-medium text-slate-700 dark:text-slate-300">Rp {{ number_format($account->balance, 0, ',', '.') }}</td>
                            </tr>
                            @empty
                            <tr><td colspan="2" class="px-6 py-2 text-center text-[10px] text-slate-400 dark:text-slate-600 italic">Nihil.</td></tr>
                            @endforelse
                            
                            <tr class="border-t border-slate-100 dark:border-slate-700">
                                <td class="px-6 py-2 pl-8 text-xs font-bold text-slate-500 dark:text-slate-400">Total Kewajiban</td>
                                <td class="px-6 py-2 text-right text-xs font-mono font-bold text-slate-600 dark:text-slate-300">Rp {{ number_format($totalLiabilitas, 0, ',', '.') }}</td>
                            </tr>

                            {{-- Section Title: Modal --}}
                            <tr class="bg-slate-50/80 dark:bg-slate-700/50 border-t border-slate-100 dark:border-slate-700">
                                <td colspan="2" class="px-6 py-1.5 text-[10px] font-bold text-slate-400 dark:text-slate-400 uppercase tracking-wider">Ekuitas (Modal)</td>
                            </tr>
                            
                            @forelse ($neraca_ekuitas_non_pl as $account)
                            <tr class="hover:bg-amber-50/30 dark:hover:bg-amber-900/10 transition-colors">
                                <td class="px-6 py-2.5 text-sm text-slate-600 dark:text-slate-400 pl-8">{{ $account->account_name }}</td>
                                <td class="px-6 py-2.5 text-sm text-right font-mono font-medium text-slate-700 dark:text-slate-300">Rp {{ number_format($account->balance, 0, ',', '.') }}</td>
                            </tr>
                            @empty
                            <tr><td colspan="2" class="px-6 py-2 text-center text-[10px] text-slate-400 dark:text-slate-600 italic">Nihil.</td></tr>
                            @endforelse
                            
                            {{-- Laba Rugi Berjalan --}}
                            <tr class="bg-emerald-50/50 dark:bg-emerald-900/10 hover:bg-emerald-100/50 dark:hover:bg-emerald-900/20 transition-colors border-t border-dashed border-slate-200 dark:border-slate-600">
                                <td class="px-6 py-3 text-sm text-emerald-800 dark:text-emerald-400 pl-8 font-bold flex items-center gap-2">
                                    Laba/Rugi Periode Ini
                                </td>
                                <td class="px-6 py-3 text-sm text-right font-mono font-bold {{ $ekuitas_labaRugiAkumulasi < 0 ? 'text-red-600 dark:text-red-400' : 'text-emerald-700 dark:text-emerald-400' }}">
                                    {{ $ekuitas_labaRugiAkumulasi < 0 ? '(' : '' }}Rp {{ number_format(abs($ekuitas_labaRugiAkumulasi), 0, ',', '.') }}{{ $ekuitas_labaRugiAkumulasi < 0 ? ')' : '' }}
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                {{-- Footer Pasiva --}}
                <div class="bg-white dark:bg-slate-800 border-t border-slate-100 dark:border-slate-700 px-6 py-4 mt-auto">
                    <div class="flex justify-between items-center p-3 rounded-xl bg-amber-50 dark:bg-amber-900/20 border border-amber-100 dark:border-amber-800">
                        <span class="text-sm font-bold text-amber-800 dark:text-amber-400 uppercase">Total Pasiva</span>
                        <span class="text-lg font-bold text-amber-700 dark:text-amber-400 font-mono">Rp {{ number_format($totalLiabilitasDanEkuitas, 0, ',', '.') }}</span>
                    </div>
                </div>
            </div>
        </div>

        {{-- BALANCE CHECK INDICATOR (Solid Background for Clarity) --}}
        @php $selisih = $totalAset - $totalLiabilitasDanEkuitas; @endphp
        
        @if(round($selisih, 2) != 0)
            {{-- TIDAK SEIMBANG --}}
            <div class="bg-red-600 text-white py-3 text-center border-t border-red-700">
                <div class="flex items-center justify-center gap-2 animate-pulse">
                    <i class="material-icons text-xl">warning</i>
                    <span class="text-sm font-bold uppercase tracking-widest">TIDAK SEIMBANG!</span>
                </div>
                <div class="text-xs font-mono mt-1 opacity-90 font-medium">
                    Selisih: Rp {{ number_format($selisih, 2, ',', '.') }}
                </div>
            </div>
        @else
            {{-- SEIMBANG --}}
            <div class="bg-emerald-600 text-white py-3 text-center border-t border-emerald-700 flex items-center justify-center gap-2 shadow-inner">
                <i class="material-icons text-lg">check_circle</i>
                <span class="text-sm font-bold uppercase tracking-widest">Neraca Seimbang (Balance)</span>
            </div>
        @endif
    </div>

    {{-- SECTION 2: LABA RUGI --}}
    <div class="dashboard-card p-0 overflow-hidden mb-10 shadow-md border-0 ring-1 ring-slate-900/5 dark:ring-slate-700/50 print-section">
        <div class="px-6 py-5 border-b border-slate-100 dark:border-slate-700 bg-white dark:bg-slate-800 flex justify-between items-center">
            <h5 class="font-bold text-lg text-slate-800 dark:text-slate-100 flex items-center gap-2">
                <div class="w-8 h-8 rounded-lg bg-emerald-100 dark:bg-emerald-900/50 text-emerald-600 dark:text-emerald-400 flex items-center justify-center">
                    <i class="material-icons text-[18px]">trending_up</i>
                </div>
                Laporan Laba Rugi
            </h5>
            <div class="text-right">
                <span class="block text-[10px] text-slate-400 dark:text-slate-500 uppercase font-bold tracking-wider">Periode</span>
                <span class="font-bold text-slate-700 dark:text-slate-300 text-xs bg-slate-100 dark:bg-slate-700 px-2 py-1 rounded border border-slate-200 dark:border-slate-600">
                    {{ \Carbon\Carbon::parse($startDate)->isoFormat('D MMM') }} - {{ \Carbon\Carbon::parse($endDate)->isoFormat('D MMM Y') }}
                </span>
            </div>
        </div>

        <div class="overflow-x-auto bg-white dark:bg-slate-800">
            <table class="w-full">
                <tbody class="divide-y divide-slate-50 dark:divide-slate-700/50 text-sm">
                    
                    {{-- REVENUE --}}
                    <tr class="bg-slate-50/50 dark:bg-slate-700/30"><td colspan="2" class="px-6 py-3 text-xs font-extrabold text-slate-500 dark:text-slate-400 uppercase tracking-wider">A. Pendapatan (Revenue)</td></tr>
                    @forelse ($labaRugi_pendapatan as $account)
                    <tr class="hover:bg-slate-50 dark:hover:bg-slate-700/30 transition-colors">
                        <td class="px-6 py-2 text-slate-700 dark:text-slate-300 pl-10">{{ $account->account_name }}</td>
                        <td class="px-6 py-2 text-right font-mono font-bold text-slate-800 dark:text-slate-200">Rp {{ number_format($account->total_credit - $account->total_debit, 0, ',', '.') }}</td>
                    </tr>
                    @empty
                    <tr><td colspan="2" class="px-6 py-2 text-center text-xs text-slate-400 dark:text-slate-600 italic">Nihil.</td></tr>
                    @endforelse
                    
                    <tr class="bg-emerald-50/30 dark:bg-emerald-900/10 border-t border-emerald-100 dark:border-emerald-900/30">
                        <td class="px-6 py-2 font-bold text-emerald-800 dark:text-emerald-400 pl-10">Total Pendapatan</td>
                        <td class="px-6 py-2 text-right font-bold text-emerald-700 dark:text-emerald-400 font-mono">Rp {{ number_format($totalPendapatan, 0, ',', '.') }}</td>
                    </tr>

                    {{-- COGS --}}
                    <tr class="bg-slate-50/50 dark:bg-slate-700/30"><td colspan="2" class="px-6 py-3 text-xs font-extrabold text-slate-500 dark:text-slate-400 uppercase tracking-wider">B. Harga Pokok Penjualan (HPP)</td></tr>
                    @forelse ($labaRugi_hpp as $account)
                    <tr class="hover:bg-slate-50 dark:hover:bg-slate-700/30 transition-colors">
                        <td class="px-6 py-2 text-slate-700 dark:text-slate-300 pl-10">{{ $account->account_name }}</td>
                        <td class="px-6 py-2 text-right font-mono font-medium text-red-600 dark:text-red-400">(Rp {{ number_format($account->total_debit - $account->total_credit, 0, ',', '.') }})</td>
                    </tr>
                    @empty
                    <tr><td colspan="2" class="px-6 py-2 text-center text-xs text-slate-400 dark:text-slate-600 italic">Nihil.</td></tr>
                    @endforelse

                    {{-- GROSS PROFIT --}}
                    <tr class="bg-slate-100/80 dark:bg-slate-700/50 border-y border-slate-200 dark:border-slate-600">
                        <td class="px-6 py-3 font-bold text-slate-800 dark:text-slate-200 uppercase tracking-wide">Laba Kotor (Gross Profit)</td>
                        <td class="px-6 py-3 text-lg font-bold text-right text-slate-900 dark:text-slate-100 font-mono border-t-2 border-slate-300 dark:border-slate-500 border-double">
                            Rp {{ number_format($labaKotor, 0, ',', '.') }}
                        </td>
                    </tr>

                    {{-- EXPENSES --}}
                    <tr class="bg-slate-50/50 dark:bg-slate-700/30"><td colspan="2" class="px-6 py-3 text-xs font-extrabold text-slate-500 dark:text-slate-400 uppercase tracking-wider">C. Beban Operasional</td></tr>
                    @forelse ($labaRugi_beban as $account)
                    <tr class="hover:bg-slate-50 dark:hover:bg-slate-700/30 transition-colors">
                        <td class="px-6 py-2 text-slate-700 dark:text-slate-300 pl-10">{{ $account->account_name }}</td>
                        <td class="px-6 py-2 text-right font-mono font-medium text-red-600 dark:text-red-400">(Rp {{ number_format($account->total_debit - $account->total_credit, 0, ',', '.') }})</td>
                    </tr>
                    @empty
                    <tr><td colspan="2" class="px-6 py-2 text-center text-xs text-slate-400 dark:text-slate-600 italic">Nihil.</td></tr>
                    @endforelse
                    
                    <tr class="bg-red-50/30 dark:bg-red-900/10 border-t border-red-100 dark:border-red-900/30">
                        <td class="px-6 py-2 font-bold text-red-900 dark:text-red-400 pl-10">Total Beban</td>
                        <td class="px-6 py-2 text-right font-bold text-red-700 dark:text-red-400 font-mono">(Rp {{ number_format($totalBeban, 0, ',', '.') }})</td>
                    </tr>
                </tbody>
                
                {{-- NET PROFIT --}}
                <tfoot class="bg-slate-800 dark:bg-slate-900 text-white">
                    <tr>
                        <td class="px-6 py-5 text-lg font-bold uppercase tracking-wide flex items-center gap-2">
                            Laba Bersih (Net Profit)
                            @if($labaBersih >= 0) <i class="material-icons text-emerald-400">trending_up</i>
                            @else <i class="material-icons text-red-400">trending_down</i> @endif
                        </td>
                        <td class="px-6 py-5 text-2xl font-bold text-right font-mono {{ $labaBersih < 0 ? 'text-red-300' : 'text-emerald-300' }}">
                            {{ $labaBersih < 0 ? '(' : '' }}Rp {{ number_format(abs($labaBersih), 0, ',', '.') }}{{ $labaBersih < 0 ? ')' : '' }}
                        </td>
                    </tr>
                </tfoot>
            </table>
        </div>
    </div>

    {{-- SECTION 3: ARUS KAS & SUB LEDGER --}}
    <div class="grid grid-cols-1 xl:grid-cols-2 gap-8 print-section">
        
        {{-- ARUS KAS --}}
        <div class="dashboard-card p-0 overflow-hidden shadow-sm h-full flex flex-col">
            <div class="px-6 py-4 border-b border-slate-100 dark:border-slate-700 bg-white dark:bg-slate-800 flex justify-between items-center">
                <h5 class="font-bold text-slate-800 dark:text-slate-100 flex items-center gap-2">
                    <div class="w-8 h-8 rounded-lg bg-blue-100 dark:bg-blue-900/50 text-blue-600 dark:text-blue-400 flex items-center justify-center">
                        <i class="material-icons text-[18px]">payments</i>
                    </div>
                    Arus Kas (Indirect)
                </h5>
            </div>
            
            <div class="flex-1 overflow-auto custom-scrollbar">
                <table class="w-full text-sm bg-white dark:bg-slate-800">
                    <tbody class="divide-y divide-slate-50 dark:divide-slate-700/50">
                        {{-- Operasi --}}
                        <tr class="bg-slate-50/80 dark:bg-slate-700/50"><td colspan="2" class="px-6 py-2 text-[10px] font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wider">1. Aktivitas Operasi</td></tr>
                        <tr>
                            <td class="px-6 py-2 text-slate-700 dark:text-slate-300 pl-8 font-medium">Laba Bersih</td>
                            <td class="px-6 py-2 text-right font-mono font-bold {{ $cf_operating_net_income < 0 ? 'text-red-600 dark:text-red-400' : 'text-emerald-600 dark:text-emerald-400' }}">
                                Rp {{ number_format($cf_operating_net_income, 0, ',', '.') }}
                            </td>
                        </tr>
                        <tr>
                            <td class="px-6 py-1 text-xs text-slate-500 dark:text-slate-400 pl-10 italic">+ Penyusutan</td>
                            <td class="px-6 py-1 text-xs text-right text-slate-500 dark:text-slate-400 font-mono">Rp {{ number_format($cf_operating_depreciation, 0, ',', '.') }}</td>
                        </tr>
                        @php
                            $changes = [
                                ['Piutang Usaha', $cf_change_ar], ['Persediaan', $cf_change_inventory],
                                ['Hutang Dagang', $cf_change_ap], ['Deposit Klien', $cf_change_client_deposit],
                                ['Deposit Supplier', $cf_change_supplier_deposit]
                            ];
                        @endphp
                        @foreach($changes as $item)
                        <tr>
                            <td class="px-6 py-1 text-xs text-slate-600 dark:text-slate-400 pl-10">Perubahan {{ $item[0] }}</td>
                            <td class="px-6 py-1 text-xs text-right font-mono {{ $item[1] < 0 ? 'text-red-500 dark:text-red-400' : 'text-slate-600 dark:text-slate-400' }}">Rp {{ number_format($item[1], 0, ',', '.') }}</td>
                        </tr>
                        @endforeach
                        <tr class="bg-blue-50/30 dark:bg-blue-900/10 border-t border-blue-100 dark:border-blue-800">
                            <td class="px-6 py-2 font-bold text-blue-800 dark:text-blue-300 pl-8">Kas Bersih Operasi</td>
                            <td class="px-6 py-2 font-bold text-right text-blue-800 dark:text-blue-300 font-mono">Rp {{ number_format($total_cash_from_operations, 0, ',', '.') }}</td>
                        </tr>

                        {{-- Investasi --}}
                        <tr class="bg-slate-50/80 dark:bg-slate-700/50"><td colspan="2" class="px-6 py-2 text-[10px] font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wider">2. Aktivitas Investasi</td></tr>
                        <tr>
                            <td class="px-6 py-2 text-slate-700 dark:text-slate-300 pl-8">Pembelian Aset Tetap</td>
                            <td class="px-6 py-2 text-right text-red-600 dark:text-red-400 font-mono">(Rp {{ number_format($cf_investing_purchase_asset, 0, ',', '.') }})</td>
                        </tr>
                        <tr class="bg-amber-50/30 dark:bg-amber-900/10 border-t border-amber-100 dark:border-amber-800">
                            <td class="px-6 py-2 font-bold text-amber-800 dark:text-amber-400 pl-8">Kas Bersih Investasi</td>
                            <td class="px-6 py-2 font-bold text-right text-amber-800 dark:text-amber-400 font-mono">Rp {{ number_format($total_cash_from_investing, 0, ',', '.') }}</td>
                        </tr>

                        {{-- Pendanaan --}}
                        <tr class="bg-slate-50/80 dark:bg-slate-700/50"><td colspan="2" class="px-6 py-2 text-[10px] font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wider">3. Aktivitas Pendanaan</td></tr>
                        <tr>
                            <td class="px-6 py-2 text-slate-700 dark:text-slate-300 pl-8">Modal Masuk & Pinjaman</td>
                            <td class="px-6 py-2 text-right font-mono text-slate-700 dark:text-slate-300">Rp {{ number_format($cf_financing_capital_in + $cf_financing_loan_in, 0, ',', '.') }}</td>
                        </tr>
                        <tr>
                            <td class="px-6 py-2 text-slate-700 dark:text-slate-300 pl-8">Prive & Bayar Pinjaman</td>
                            <td class="px-6 py-2 text-right text-red-600 dark:text-red-400 font-mono">(Rp {{ number_format($cf_financing_drawing + $cf_financing_loan_pay, 0, ',', '.') }})</td>
                        </tr>
                        <tr class="bg-purple-50/30 dark:bg-purple-900/10 border-t border-purple-100 dark:border-purple-800">
                            <td class="px-6 py-2 font-bold text-purple-800 dark:text-purple-400 pl-8">Kas Bersih Pendanaan</td>
                            <td class="px-6 py-2 font-bold text-right text-purple-800 dark:text-purple-400 font-mono">Rp {{ number_format($total_cash_from_financing, 0, ',', '.') }}</td>
                        </tr>
                    </tbody>
                    <tfoot class="bg-slate-800 dark:bg-slate-900 text-white border-t-4 border-emerald-500">
                        <tr>
                            <td class="px-6 py-3 font-bold">Kenaikan Bersih Kas</td>
                            <td class="px-6 py-3 font-bold text-right font-mono">Rp {{ number_format($net_increase_cash, 0, ',', '.') }}</td>
                        </tr>
                        <tr class="border-t border-slate-700 dark:border-slate-700">
                            <td class="px-6 py-2 text-xs text-slate-400">Saldo Awal</td>
                            <td class="px-6 py-2 text-xs text-right text-slate-400 font-mono">Rp {{ number_format($cash_beginning, 0, ',', '.') }}</td>
                        </tr>
                        <tr class="bg-slate-900">
                            <td class="px-6 py-3 font-bold text-emerald-400 uppercase tracking-wide">SALDO AKHIR KAS</td>
                            <td class="px-6 py-3 font-bold text-right text-emerald-400 font-mono text-lg">Rp {{ number_format($cash_ending, 0, ',', '.') }}</td>
                        </tr>
                    </tfoot>
                </table>
            </div>
        </div>

        {{-- SUB LEDGERS --}}
        <div class="space-y-6 flex flex-col h-full">
            
            {{-- PIUTANG WIDGET --}}
            <div class="dashboard-card p-0 overflow-hidden shadow-sm flex-1 flex flex-col">
                <div class="px-6 py-3 border-b border-slate-100 dark:border-slate-700 bg-white dark:bg-slate-800 flex justify-between items-center">
                    <h5 class="font-bold text-slate-700 dark:text-slate-200 text-sm flex items-center gap-2">
                        <i class="material-icons text-indigo-500 dark:text-indigo-400 text-base">receipt_long</i> Rincian Piutang (AR)
                    </h5>
                    <span class="text-[10px] font-bold bg-indigo-50 dark:bg-indigo-900/30 text-indigo-600 dark:text-indigo-300 px-2 py-0.5 rounded border border-indigo-100 dark:border-indigo-800">{{ count($laporanPiutang) }} Invoice</span>
                </div>
                <div class="overflow-auto custom-scrollbar max-h-[300px] flex-1 bg-white dark:bg-slate-800">
                    <table class="w-full text-xs">
                        <tbody class="divide-y divide-slate-50 dark:divide-slate-700/50">
                            @forelse ($laporanPiutang as $inv)
                            <tr class="hover:bg-indigo-50/20 dark:hover:bg-indigo-900/10 transition-colors">
                                <td class="px-6 py-2.5 font-medium text-slate-700 dark:text-slate-300">{{ $inv->client->client_name }}</td>
                                <td class="px-6 py-2.5 text-right font-mono text-indigo-600 dark:text-indigo-400">
                                    <a href="{{ route('admin.invoices.show', $inv->invoice_id) }}" class="hover:underline">{{ $inv->invoice_number }}</a>
                                </td>
                                <td class="px-6 py-2.5 text-right font-bold text-slate-800 dark:text-slate-200">Rp {{ number_format($inv->remaining_balance, 0, ',', '.') }}</td>
                            </tr>
                            @empty
                            <tr><td colspan="3" class="px-6 py-10 text-center text-slate-400 dark:text-slate-600 italic">Tidak ada piutang tertunggak.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
                <div class="px-6 py-3 bg-indigo-50 dark:bg-indigo-900/20 border-t border-indigo-100 dark:border-indigo-800 flex justify-between items-center text-xs font-bold text-indigo-900 dark:text-indigo-300">
                    <span>TOTAL PIUTANG</span>
                    <span class="font-mono text-sm">Rp {{ number_format($totalPiutang_SL, 0, ',', '.') }}</span>
                </div>
            </div>

            {{-- HUTANG WIDGET --}}
            <div class="dashboard-card p-0 overflow-hidden shadow-sm flex-1 flex flex-col">
                <div class="px-6 py-3 border-b border-slate-100 dark:border-slate-700 bg-white dark:bg-slate-800 flex justify-between items-center">
                    <h5 class="font-bold text-slate-700 dark:text-slate-200 text-sm flex items-center gap-2">
                        <i class="material-icons text-red-500 dark:text-red-400 text-base">money_off</i> Rincian Hutang (AP)
                    </h5>
                    <span class="text-[10px] font-bold bg-red-50 dark:bg-red-900/30 text-red-600 dark:text-red-300 px-2 py-0.5 rounded border border-red-100 dark:border-red-800">{{ count($laporanUtang) }} PO</span>
                </div>
                <div class="overflow-auto custom-scrollbar max-h-[300px] flex-1 bg-white dark:bg-slate-800">
                    <table class="w-full text-xs">
                        <tbody class="divide-y divide-slate-50 dark:divide-slate-700/50">
                            @forelse ($laporanUtang as $po)
                            <tr class="hover:bg-red-50/20 dark:hover:bg-red-900/10 transition-colors">
                                <td class="px-6 py-2.5 font-medium text-slate-700 dark:text-slate-300">{{ $po->supplier->supplier_name }}</td>
                                <td class="px-6 py-2.5 text-right font-mono text-indigo-600 dark:text-indigo-400">
                                    <a href="{{ route('admin.purchase-orders.show', $po->po_id) }}" class="hover:underline">{{ $po->po_number }}</a>
                                </td>
                                <td class="px-6 py-2.5 text-right font-bold text-slate-800 dark:text-slate-200">Rp {{ number_format($po->remaining_balance, 0, ',', '.') }}</td>
                            </tr>
                            @empty
                            <tr><td colspan="3" class="px-6 py-10 text-center text-slate-400 dark:text-slate-600 italic">Tidak ada hutang tertunggak.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
                <div class="px-6 py-3 bg-red-50 dark:bg-red-900/20 border-t border-red-100 dark:border-red-800 flex justify-between items-center text-xs font-bold text-red-900 dark:text-red-300">
                    <span>TOTAL HUTANG</span>
                    <span class="font-mono text-sm">Rp {{ number_format($totalUtang_SL, 0, ',', '.') }}</span>
                </div>
            </div>

        </div>
    </div>

</div>
@endsection
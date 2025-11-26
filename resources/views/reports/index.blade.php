@extends('layouts.app')

@section('title', 'Laporan Keuangan')

@section('content')
<div class="max-w-7xl mx-auto pb-20 animate-enter">
    
    {{-- HEADER & FILTER --}}
    <div class="flex flex-col md:flex-row justify-between items-start md:items-end gap-4 mb-8">
        <div>
            <h1 class="text-2xl font-bold text-slate-800 tracking-tight">Laporan Keuangan</h1>
            <p class="text-slate-500 text-sm mt-1">Ringkasan performa dan posisi keuangan (Double Entry).</p>
        </div>
        
        <div class="w-full md:w-auto bg-white p-1.5 rounded-xl border border-slate-200 shadow-sm">
            <form action="{{ route('reports.index') }}" method="GET" class="flex flex-col md:flex-row items-center gap-2">
                <div class="flex items-center gap-2 bg-slate-50 px-3 py-1.5 rounded-lg border border-slate-100 w-full md:w-auto">
                    <span class="text-xs font-bold text-slate-400 uppercase">Periode</span>
                    <input type="date" name="start_date" class="bg-transparent border-none text-xs font-medium text-slate-700 focus:ring-0 p-0 w-full md:w-24" value="{{ $startDate }}">
                    <span class="text-slate-300">-</span>
                    <input type="date" name="end_date" class="bg-transparent border-none text-xs font-medium text-slate-700 focus:ring-0 p-0 w-full md:w-24" value="{{ $endDate }}">
                </div>
                <button type="submit" class="w-full md:w-auto px-4 py-1.5 bg-slate-800 text-white text-xs font-bold rounded-lg hover:bg-slate-900 transition flex items-center justify-center gap-1 h-[32px]">
                    <i class="material-icons text-[16px]">refresh</i> Update
                </button>
                <button type="button" onclick="window.print()" class="w-full md:w-auto px-3 py-1.5 bg-white border border-slate-300 text-slate-700 text-xs font-bold rounded-lg hover:bg-slate-50 transition flex items-center justify-center gap-1 h-[32px]">
                    <i class="material-icons text-[16px]">print</i> Cetak
                </button>
            </form>
        </div>
    </div>

    {{-- INFO CARD --}}
    <div class="bg-blue-50 border border-blue-100 rounded-xl p-4 mb-8 flex gap-3 shadow-sm">
        <div class="p-2 bg-blue-100 rounded-full text-blue-600 h-fit">
            <i class="material-icons text-lg">info</i>
        </div>
        <div class="text-sm text-blue-800">
            <p class="font-bold mb-1">Ringkasan Laporan:</p>
            <p class="text-xs leading-relaxed opacity-80">
                Laporan ini menyajikan <strong>Neraca</strong> (Posisi Keuangan) per tanggal akhir periode, serta <strong>Laba Rugi</strong> dan <strong>Arus Kas</strong> selama rentang waktu yang dipilih.
            </p>
        </div>
    </div>

    {{-- 1. NERACA (BALANCE SHEET) --}}
    <div class="dashboard-card p-0 overflow-hidden mb-8 shadow-lg border-0 ring-1 ring-slate-900/5 print-section">
        <div class="px-6 py-4 border-b border-slate-100 bg-indigo-600 flex justify-between items-center text-white">
            <h5 class="font-bold flex items-center gap-2">
                <i class="material-icons text-lg">account_balance</i> Neraca (Balance Sheet)
            </h5>
            <span class="text-[10px] bg-indigo-700 px-2 py-1 rounded font-medium border border-indigo-500">Per: {{ \Carbon\Carbon::parse($endDate)->isoFormat('D MMMM Y') }}</span>
        </div>
        
        <div class="grid grid-cols-1 lg:grid-cols-2 divide-y lg:divide-y-0 lg:divide-x divide-slate-100">
            
            {{-- ASET --}}
            <div>
                <div class="bg-slate-50/50 px-6 py-3 border-b border-slate-100 text-xs font-bold text-slate-500 uppercase tracking-wider">
                    ASET (Harta)
                </div>
                <table class="w-full">
                    <tbody class="divide-y divide-slate-50">
                        @forelse ($neraca_aset as $account)
                        <tr class="hover:bg-slate-50/50">
                            <td class="px-6 py-2.5 text-sm text-slate-700">{{ $account->account_name }}</td>
                            <td class="px-6 py-2.5 text-sm text-right font-mono font-medium text-slate-900">Rp {{ number_format($account->balance, 0, ',', '.') }}</td>
                        </tr>
                        @empty
                        <tr><td colspan="2" class="px-6 py-4 text-center text-xs text-slate-400 italic">Tidak ada data Aset.</td></tr>
                        @endforelse
                    </tbody>
                    <tfoot class="bg-indigo-50/30 border-t border-indigo-100">
                        <tr>
                            <td class="px-6 py-3 text-sm font-bold text-indigo-900 uppercase">Total Aset</td>
                            <td class="px-6 py-3 text-sm font-bold text-right text-indigo-900 font-mono">Rp {{ number_format($totalAset, 0, ',', '.') }}</td>
                        </tr>
                    </tfoot>
                </table>
            </div>

            {{-- PASIVA --}}
            <div>
                <div class="bg-slate-50/50 px-6 py-3 border-b border-slate-100 text-xs font-bold text-slate-500 uppercase tracking-wider">
                    LIABILITAS & EKUITAS
                </div>
                <table class="w-full">
                    <tbody class="divide-y divide-slate-50">
                        {{-- Liabilitas --}}
                        <tr><td colspan="2" class="px-6 py-2 text-[10px] font-bold text-slate-400 bg-slate-50 uppercase tracking-wide">Kewajiban</td></tr>
                        @forelse ($neraca_liabilitas as $account)
                        <tr class="hover:bg-slate-50/50">
                            <td class="px-6 py-2 text-sm text-slate-700 pl-8">{{ $account->account_name }}</td>
                            <td class="px-6 py-2 text-sm text-right font-mono font-medium text-slate-900">Rp {{ number_format($account->balance, 0, ',', '.') }}</td>
                        </tr>
                        @empty
                        <tr><td colspan="2" class="px-6 py-2 text-center text-xs text-slate-400 italic">Nihil.</td></tr>
                        @endforelse
                        <tr class="bg-slate-50/30 font-bold text-xs text-slate-600 border-t border-slate-100">
                            <td class="px-6 py-2 pl-8">Total Kewajiban</td>
                            <td class="px-6 py-2 text-right font-mono">Rp {{ number_format($totalLiabilitas, 0, ',', '.') }}</td>
                        </tr>

                        {{-- Ekuitas --}}
                        <tr><td colspan="2" class="px-6 py-2 text-[10px] font-bold text-slate-400 bg-slate-50 uppercase tracking-wide border-t border-slate-100">Modal</td></tr>
                        @forelse ($neraca_ekuitas_non_pl as $account)
                        <tr class="hover:bg-slate-50/50">
                            <td class="px-6 py-2 text-sm text-slate-700 pl-8">{{ $account->account_name }}</td>
                            <td class="px-6 py-2 text-sm text-right font-mono font-medium text-slate-900">Rp {{ number_format($account->balance, 0, ',', '.') }}</td>
                        </tr>
                        @empty
                        <tr><td colspan="2" class="px-6 py-2 text-center text-xs text-slate-400 italic">Nihil.</td></tr>
                        @endforelse
                        
                        <tr class="hover:bg-slate-50/50 border-t border-dashed border-slate-200">
                            <td class="px-6 py-2 text-sm text-slate-800 pl-8 font-medium">Laba/Rugi Periode Ini</td>
                            <td class="px-6 py-2 text-sm text-right font-mono font-bold {{ $ekuitas_labaRugiAkumulasi < 0 ? 'text-red-600' : 'text-emerald-600' }}">
                                {{ $ekuitas_labaRugiAkumulasi < 0 ? '(' : '' }}Rp {{ number_format(abs($ekuitas_labaRugiAkumulasi), 0, ',', '.') }}{{ $ekuitas_labaRugiAkumulasi < 0 ? ')' : '' }}
                            </td>
                        </tr>
                    </tbody>
                    <tfoot class="bg-indigo-50/30 border-t border-indigo-100">
                        <tr>
                            <td class="px-6 py-3 text-sm font-bold text-indigo-900 uppercase">Total Pasiva</td>
                            <td class="px-6 py-3 text-sm font-bold text-right text-indigo-900 font-mono">Rp {{ number_format($totalLiabilitasDanEkuitas, 0, ',', '.') }}</td>
                        </tr>
                    </tfoot>
                </table>
            </div>
        </div>

        {{-- Balance Check --}}
        @php $selisih = $totalAset - $totalLiabilitasDanEkuitas; @endphp
        @if(round($selisih, 2) != 0)
            <div class="bg-red-600 text-white text-center py-2 text-xs font-bold uppercase tracking-widest">
                <i class="material-icons text-sm align-text-bottom mr-1">warning</i> TIDAK SEIMBANG! Selisih: Rp {{ number_format($selisih, 2, ',', '.') }}
            </div>
        @else
            <div class="bg-emerald-600 text-white text-center py-2 text-xs font-bold uppercase tracking-widest flex items-center justify-center gap-1">
                <i class="material-icons text-sm">check_circle</i> SEIMBANG (BALANCE)
            </div>
        @endif
    </div>

    {{-- 2. LABA RUGI --}}
    <div class="dashboard-card p-0 overflow-hidden mb-8 shadow-lg border-0 ring-1 ring-slate-900/5 print-section">
        <div class="px-6 py-4 border-b border-slate-100 bg-slate-800 flex justify-between items-center text-white">
            <h5 class="font-bold flex items-center gap-2">
                <i class="material-icons text-lg">trending_up</i> Laporan Laba Rugi
            </h5>
            <span class="text-[10px] bg-slate-700 px-2 py-1 rounded font-medium border border-slate-600">
                {{ \Carbon\Carbon::parse($startDate)->isoFormat('D MMM') }} - {{ \Carbon\Carbon::parse($endDate)->isoFormat('D MMM Y') }}
            </span>
        </div>
        
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-slate-50">
                <tbody class="divide-y divide-slate-50">
                    {{-- PENDAPATAN --}}
                    <tr class="bg-slate-50"><td colspan="2" class="px-6 py-3 text-xs font-bold text-slate-500 uppercase tracking-wider">A. Pendapatan (Revenue)</td></tr>
                    @forelse ($labaRugi_pendapatan as $account)
                    <tr class="hover:bg-slate-50/50">
                        <td class="px-6 py-2 text-sm text-slate-700 pl-10">{{ $account->account_name }}</td>
                        <td class="px-6 py-2 text-sm text-right font-mono font-medium text-slate-900">Rp {{ number_format($account->total_credit - $account->total_debit, 0, ',', '.') }}</td>
                    </tr>
                    @empty
                    <tr><td colspan="2" class="px-6 py-2 text-center text-xs text-slate-400 italic">Nihil.</td></tr>
                    @endforelse
                    <tr class="bg-indigo-50/30 border-t border-indigo-100">
                        <td class="px-6 py-2 text-sm font-bold text-indigo-900 pl-10">Total Pendapatan</td>
                        <td class="px-6 py-2 text-sm font-bold text-right text-indigo-700 font-mono">Rp {{ number_format($totalPendapatan, 0, ',', '.') }}</td>
                    </tr>

                    {{-- HPP --}}
                    <tr class="bg-slate-50"><td colspan="2" class="px-6 py-3 text-xs font-bold text-slate-500 uppercase tracking-wider">B. Harga Pokok Penjualan (COGS)</td></tr>
                    @forelse ($labaRugi_hpp as $account)
                    <tr class="hover:bg-slate-50/50">
                        <td class="px-6 py-2 text-sm text-slate-700 pl-10">{{ $account->account_name }}</td>
                        <td class="px-6 py-2 text-sm text-right font-mono font-medium text-red-600">(Rp {{ number_format($account->total_debit - $account->total_credit, 0, ',', '.') }})</td>
                    </tr>
                    @empty
                    <tr><td colspan="2" class="px-6 py-2 text-center text-xs text-slate-400 italic">Nihil.</td></tr>
                    @endforelse
                    
                    {{-- LABA KOTOR --}}
                    <tr class="bg-slate-100 border-y border-slate-200">
                        <td class="px-6 py-3 text-sm font-bold text-slate-900 uppercase tracking-wide">Laba Kotor (Gross Profit)</td>
                        <td class="px-6 py-3 text-base font-bold text-right text-slate-900 font-mono">Rp {{ number_format($labaKotor, 0, ',', '.') }}</td>
                    </tr>

                    {{-- BEBAN --}}
                    <tr class="bg-slate-50"><td colspan="2" class="px-6 py-3 text-xs font-bold text-slate-500 uppercase tracking-wider">C. Beban Operasional</td></tr>
                    @forelse ($labaRugi_beban as $account)
                    <tr class="hover:bg-slate-50/50">
                        <td class="px-6 py-2 text-sm text-slate-700 pl-10">{{ $account->account_name }}</td>
                        <td class="px-6 py-2 text-sm text-right font-mono font-medium text-red-600">(Rp {{ number_format($account->total_debit - $account->total_credit, 0, ',', '.') }})</td>
                    </tr>
                    @empty
                    <tr><td colspan="2" class="px-6 py-2 text-center text-xs text-slate-400 italic">Nihil.</td></tr>
                    @endforelse
                    <tr class="bg-red-50/30 border-t border-red-100">
                        <td class="px-6 py-2 text-sm font-bold text-red-900 pl-10">Total Beban</td>
                        <td class="px-6 py-2 text-sm font-bold text-right text-red-700 font-mono">(Rp {{ number_format($totalBeban, 0, ',', '.') }})</td>
                    </tr>
                </tbody>
                <tfoot class="bg-slate-800 text-white border-t-4 border-indigo-500">
                    <tr>
                        <td class="px-6 py-4 text-base font-bold uppercase tracking-wide">Laba Bersih (Net Profit)</td>
                        <td class="px-6 py-4 text-xl font-bold text-right font-mono {{ $labaBersih < 0 ? 'text-red-300' : 'text-emerald-300' }}">
                            {{ $labaBersih < 0 ? '(' : '' }}Rp {{ number_format(abs($labaBersih), 0, ',', '.') }}{{ $labaBersih < 0 ? ')' : '' }}
                        </td>
                    </tr>
                </tfoot>
            </table>
        </div>
    </div>

    {{-- 3. ARUS KAS --}}
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-8 print-section">
        
        {{-- CASH FLOW --}}
        <div class="dashboard-card p-0 overflow-hidden shadow-md border-0 ring-1 ring-slate-900/5 h-full flex flex-col">
            <div class="px-6 py-4 border-b border-slate-100 bg-emerald-600 flex justify-between items-center text-white">
                <h5 class="font-bold text-sm flex items-center gap-2 uppercase tracking-wider">
                    <i class="material-icons text-base">payments</i> Arus Kas (Indirect)
                </h5>
            </div>
            <div class="flex-1 overflow-auto custom-scrollbar">
                <table class="min-w-full divide-y divide-slate-100">
                    <tbody class="divide-y divide-slate-50 bg-white">
                        
                        {{-- 1. OPERASI --}}
                        <tr class="bg-slate-50"><td colspan="2" class="px-6 py-2 text-[10px] font-bold text-slate-500 uppercase">1. Aktivitas Operasi</td></tr>
                        <tr>
                            <td class="px-6 py-2 text-sm text-slate-700 pl-8 font-medium">Laba Bersih</td>
                            <td class="px-6 py-2 text-sm text-right font-mono font-bold {{ $cf_operating_net_income < 0 ? 'text-red-600' : 'text-emerald-600' }}">
                                Rp {{ number_format($cf_operating_net_income, 0, ',', '.') }}
                            </td>
                        </tr>
                        <tr>
                            <td class="px-6 py-1 text-xs text-slate-500 pl-10 italic">+ Penyusutan</td>
                            <td class="px-6 py-1 text-xs text-right text-slate-500 font-mono">Rp {{ number_format($cf_operating_depreciation, 0, ',', '.') }}</td>
                        </tr>
                        
                        {{-- Item Perubahan Modal Kerja --}}
                        @php
                            $changes = [
                                ['Piutang Usaha', $cf_change_ar],
                                ['Persediaan', $cf_change_inventory],
                                ['Hutang Dagang', $cf_change_ap],
                                ['Deposit Klien', $cf_change_client_deposit],
                                ['Deposit Supplier', $cf_change_supplier_deposit]
                            ];
                        @endphp
                        @foreach($changes as $item)
                        <tr>
                            <td class="px-6 py-1 text-xs text-slate-600 pl-10">Perubahan {{ $item[0] }}</td>
                            <td class="px-6 py-1 text-xs text-right font-mono {{ $item[1] < 0 ? 'text-red-500' : 'text-slate-600' }}">
                                Rp {{ number_format($item[1], 0, ',', '.') }}
                            </td>
                        </tr>
                        @endforeach

                        <tr class="bg-emerald-50/50 border-t border-emerald-100">
                            <td class="px-6 py-2 text-xs font-bold text-emerald-800 pl-8">Kas Bersih Operasi</td>
                            <td class="px-6 py-2 text-xs font-bold text-right text-emerald-800 font-mono">Rp {{ number_format($total_cash_from_operations, 0, ',', '.') }}</td>
                        </tr>

                        {{-- 2. INVESTASI --}}
                        <tr class="bg-slate-50"><td colspan="2" class="px-6 py-2 text-[10px] font-bold text-slate-500 uppercase">2. Aktivitas Investasi</td></tr>
                        <tr>
                            <td class="px-6 py-2 text-sm text-slate-700 pl-8">Pembelian Aset</td>
                            <td class="px-6 py-2 text-sm text-right text-red-600 font-mono">(Rp {{ number_format($cf_investing_purchase_asset, 0, ',', '.') }})</td>
                        </tr>
                        <tr class="bg-yellow-50/50 border-t border-yellow-100">
                            <td class="px-6 py-2 text-xs font-bold text-yellow-800 pl-8">Kas Bersih Investasi</td>
                            <td class="px-6 py-2 text-xs font-bold text-right text-yellow-800 font-mono">Rp {{ number_format($total_cash_from_investing, 0, ',', '.') }}</td>
                        </tr>

                        {{-- 3. PENDANAAN --}}
                        <tr class="bg-slate-50"><td colspan="2" class="px-6 py-2 text-[10px] font-bold text-slate-500 uppercase">3. Aktivitas Pendanaan</td></tr>
                        <tr>
                            <td class="px-6 py-2 text-sm text-slate-700 pl-8">Modal Masuk & Pinjaman</td>
                            <td class="px-6 py-2 text-sm text-right font-mono">Rp {{ number_format($cf_financing_capital_in + $cf_financing_loan_in, 0, ',', '.') }}</td>
                        </tr>
                        <tr>
                            <td class="px-6 py-2 text-sm text-slate-700 pl-8">Prive & Bayar Pinjaman</td>
                            <td class="px-6 py-2 text-sm text-right text-red-600 font-mono">(Rp {{ number_format($cf_financing_drawing + $cf_financing_loan_pay, 0, ',', '.') }})</td>
                        </tr>
                        <tr class="bg-blue-50/50 border-t border-blue-100">
                            <td class="px-6 py-2 text-xs font-bold text-blue-800 pl-8">Kas Bersih Pendanaan</td>
                            <td class="px-6 py-2 text-xs font-bold text-right text-blue-800 font-mono">Rp {{ number_format($total_cash_from_financing, 0, ',', '.') }}</td>
                        </tr>
                    </tbody>
                    <tfoot class="bg-slate-800 text-white">
                        <tr>
                            <td class="px-6 py-3 text-sm font-bold">Kenaikan Bersih Kas</td>
                            <td class="px-6 py-3 text-sm font-bold text-right font-mono">Rp {{ number_format($net_increase_cash, 0, ',', '.') }}</td>
                        </tr>
                         <tr>
                            <td class="px-6 py-2 text-xs font-medium text-slate-400 bg-slate-900">Saldo Awal</td>
                            <td class="px-6 py-2 text-xs font-medium text-right text-slate-400 bg-slate-900 font-mono">Rp {{ number_format($cash_beginning, 0, ',', '.') }}</td>
                        </tr>
                         <tr class="border-t border-slate-700">
                            <td class="px-6 py-3 text-sm font-bold text-emerald-400 bg-slate-900">SALDO AKHIR</td>
                            <td class="px-6 py-3 text-sm font-bold text-right text-emerald-400 bg-slate-900 font-mono">Rp {{ number_format($cash_ending, 0, ',', '.') }}</td>
                        </tr>
                    </tfoot>
                </table>
            </div>
        </div>

        {{-- SUB LEDGERS (PIUTANG & HUTANG) --}}
        <div class="space-y-6 flex flex-col h-full">
            
            {{-- PIUTANG --}}
            <div class="dashboard-card p-0 overflow-hidden shadow-sm flex-1 border border-slate-200">
                <div class="px-6 py-3 border-b border-slate-100 bg-slate-50 flex justify-between items-center">
                    <h5 class="font-bold text-xs text-slate-500 uppercase tracking-wider">Rincian Piutang (AR)</h5>
                    <span class="text-[10px] font-bold bg-white border border-slate-200 px-2 py-0.5 rounded text-slate-400">{{ count($laporanPiutang) }} Inv</span>
                </div>
                <div class="overflow-auto custom-scrollbar max-h-[300px]">
                    <table class="min-w-full divide-y divide-slate-50">
                        <tbody class="bg-white">
                            @forelse ($laporanPiutang as $inv)
                            <tr class="hover:bg-slate-50/50">
                                <td class="px-6 py-2 text-xs text-slate-700 font-medium">{{ $inv->client->client_name }}</td>
                                <td class="px-6 py-2 text-xs text-indigo-600 font-mono text-right">
                                    <a href="{{ route('invoices.show', $inv->invoice_id) }}" class="hover:underline">{{ $inv->invoice_number }}</a>
                                </td>
                                <td class="px-6 py-2 text-xs text-right font-bold text-slate-800">Rp {{ number_format($inv->remaining_balance, 0, ',', '.') }}</td>
                            </tr>
                            @empty
                            <tr><td colspan="3" class="px-6 py-8 text-center text-xs text-slate-400 italic">Tidak ada piutang.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
                <div class="px-6 py-2 bg-indigo-50/30 border-t border-indigo-100 flex justify-between items-center text-xs font-bold text-indigo-900">
                    <span>Total Piutang</span>
                    <span class="font-mono">Rp {{ number_format($totalPiutang_SL, 0, ',', '.') }}</span>
                </div>
            </div>

            {{-- HUTANG --}}
            <div class="dashboard-card p-0 overflow-hidden shadow-sm flex-1 border border-slate-200">
                <div class="px-6 py-3 border-b border-slate-100 bg-slate-50 flex justify-between items-center">
                    <h5 class="font-bold text-xs text-slate-500 uppercase tracking-wider">Rincian Hutang (AP)</h5>
                    <span class="text-[10px] font-bold bg-white border border-slate-200 px-2 py-0.5 rounded text-slate-400">{{ count($laporanUtang) }} PO</span>
                </div>
                <div class="overflow-auto custom-scrollbar max-h-[300px]">
                    <table class="min-w-full divide-y divide-slate-50">
                        <tbody class="bg-white">
                            @forelse ($laporanUtang as $po)
                            <tr class="hover:bg-slate-50/50">
                                <td class="px-6 py-2 text-xs text-slate-700 font-medium">{{ $po->supplier->supplier_name }}</td>
                                <td class="px-6 py-2 text-xs text-indigo-600 font-mono text-right">
                                    <a href="{{ route('purchase-orders.show', $po->po_id) }}" class="hover:underline">{{ $po->po_number }}</a>
                                </td>
                                <td class="px-6 py-2 text-xs text-right font-bold text-slate-800">Rp {{ number_format($po->remaining_balance, 0, ',', '.') }}</td>
                            </tr>
                            @empty
                            <tr><td colspan="3" class="px-6 py-8 text-center text-xs text-slate-400 italic">Tidak ada hutang.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
                <div class="px-6 py-2 bg-red-50/30 border-t border-red-100 flex justify-between items-center text-xs font-bold text-red-900">
                    <span>Total Hutang</span>
                    <span class="font-mono">Rp {{ number_format($totalUtang_SL, 0, ',', '.') }}</span>
                </div>
            </div>

        </div>
    </div>

</div>
@endsection
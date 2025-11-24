@extends('layouts.app')

@section('title', 'Laporan Keuangan')

@section('content')
<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-6 space-y-8">
    
    {{-- HEADER & FILTER --}}
    <div class="flex flex-col md:flex-row justify-between items-start md:items-end gap-4">
        <div>
            <h2 class="text-2xl font-bold text-gray-900 tracking-tight">Laporan Keuangan</h2>
            <p class="text-sm text-gray-500 mt-1">Berbasis Jurnal Umum (Double Entry Accounting)</p>
        </div>
        
        {{-- Filter Form --}}
        <div class="w-full md:w-auto">
            <form action="{{ route('reports.index') }}" method="GET" class="flex flex-col md:flex-row items-end gap-3">
                <div>
                    <label for="start_date" class="block text-xs font-bold text-gray-500 uppercase mb-1">Dari Tanggal</label>
                    <input type="date" name="start_date" id="start_date" class="form-input block w-full rounded-lg border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm" value="{{ $startDate }}">
                </div>
                <div>
                    <label for="end_date" class="block text-xs font-bold text-gray-500 uppercase mb-1">Sampai Tanggal</label>
                    <input type="date" name="end_date" id="end_date" class="form-input block w-full rounded-lg border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm" value="{{ $endDate }}">
                </div>
                <button type="submit" class="w-full md:w-auto inline-flex justify-center items-center px-4 py-2 bg-gray-800 border border-transparent rounded-lg font-medium text-sm text-white hover:bg-gray-900 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-gray-900 transition h-[38px]">
                    <i class="material-icons text-lg mr-2">filter_alt</i> Tampilkan
                </button>
            </form>
        </div>
    </div>

    {{-- INFO CARD --}}
    <div class="bg-blue-50 border border-blue-200 rounded-lg p-4 flex items-start gap-3">
        <i class="material-icons text-blue-500 mt-0.5">info</i>
        <div class="text-sm text-blue-800">
            <p class="font-bold mb-1">Tentang Laporan:</p>
            <ul class="list-disc list-inside space-y-1 ml-1 text-xs">
                <li><strong>Laba Rugi:</strong> Menampilkan performa (Pendapatan vs Beban) selama rentang tanggal yang dipilih.</li>
                <li><strong>Neraca:</strong> Menampilkan posisi keuangan (Aset, Kewajiban, Modal) per tanggal akhir periode.</li>
            </ul>
        </div>
    </div>

    {{-- =================================== --}}
    {{-- LAPORAN NERACA (BALANCE SHEET) --}}
    {{-- =================================== --}}
    <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
        <div class="px-6 py-4 border-b border-gray-200 bg-indigo-600 flex justify-between items-center text-white">
            <h5 class="font-bold text-lg flex items-center gap-2">
                <i class="material-icons">account_balance</i> Laporan Neraca (Balance Sheet)
            </h5>
            <span class="text-xs bg-indigo-700 px-3 py-1 rounded-full">Per: {{ $endDateCarbon->isoFormat('D MMMM Y') }}</span>
        </div>
        
        <div class="grid grid-cols-1 lg:grid-cols-2 divide-y lg:divide-y-0 lg:divide-x divide-gray-200">
            
            {{-- SISI ASET --}}
            <div class="p-0">
                <div class="bg-gray-50 px-6 py-3 border-b border-gray-200 font-bold text-gray-700 uppercase tracking-wider text-sm">
                    ASET (Harta)
                </div>
                <table class="min-w-full divide-y divide-gray-100">
                    <tbody class="bg-white divide-y divide-gray-50">
                        @forelse ($neraca_aset as $account)
                        <tr class="hover:bg-gray-50">
                            <td class="px-6 py-3 text-sm text-gray-700">{{ $account->account_name }}</td>
                            <td class="px-6 py-3 text-sm text-right font-mono text-gray-900">Rp {{ number_format($account->balance, 0, ',', '.') }}</td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="2" class="px-6 py-4 text-center text-gray-400 text-sm italic">Tidak ada data Aset.</td>
                        </tr>
                        @endforelse
                    </tbody>
                    <tfoot class="bg-indigo-50">
                        <tr>
                            <td class="px-6 py-3 text-sm font-bold text-indigo-900 uppercase">Total Aset</td>
                            <td class="px-6 py-3 text-sm font-bold text-right text-indigo-900 font-mono">Rp {{ number_format($totalAset, 0, ',', '.') }}</td>
                        </tr>
                    </tfoot>
                </table>
            </div>

            {{-- SISI LIABILITAS & EKUITAS --}}
            <div class="p-0">
                <div class="bg-gray-50 px-6 py-3 border-b border-gray-200 font-bold text-gray-700 uppercase tracking-wider text-sm">
                    LIABILITAS & EKUITAS
                </div>
                <table class="min-w-full divide-y divide-gray-100">
                    <tbody class="bg-white divide-y divide-gray-50">
                        {{-- Liabilitas --}}
                        <tr>
                            <td colspan="2" class="px-6 py-2 text-xs font-bold text-gray-500 bg-gray-50 uppercase tracking-wider">Kewajiban (Hutang)</td>
                        </tr>
                        @forelse ($neraca_liabilitas as $account)
                        <tr class="hover:bg-gray-50">
                            <td class="px-6 py-2 text-sm text-gray-700 pl-8">{{ $account->account_name }}</td>
                            <td class="px-6 py-2 text-sm text-right font-mono text-gray-900">Rp {{ number_format($account->balance, 0, ',', '.') }}</td>
                        </tr>
                        @empty
                        <tr><td colspan="2" class="px-6 py-2 text-center text-gray-400 text-xs italic">Nihil.</td></tr>
                        @endforelse
                        <tr class="bg-gray-50/50">
                            <td class="px-6 py-2 text-xs font-bold text-gray-600 pl-8">Total Liabilitas</td>
                            <td class="px-6 py-2 text-xs font-bold text-right text-gray-800 font-mono">Rp {{ number_format($totalLiabilitas, 0, ',', '.') }}</td>
                        </tr>

                        {{-- Ekuitas --}}
                        <tr>
                            <td colspan="2" class="px-6 py-2 text-xs font-bold text-gray-500 bg-gray-50 uppercase tracking-wider mt-2">Ekuitas (Modal)</td>
                        </tr>
                        @forelse ($neraca_ekuitas_non_pl as $account)
                        <tr class="hover:bg-gray-50">
                            <td class="px-6 py-2 text-sm text-gray-700 pl-8">{{ $account->account_name }}</td>
                            <td class="px-6 py-2 text-sm text-right font-mono text-gray-900">Rp {{ number_format($account->balance, 0, ',', '.') }}</td>
                        </tr>
                        @empty
                        <tr><td colspan="2" class="px-6 py-2 text-center text-gray-400 text-xs italic">Nihil.</td></tr>
                        @endforelse
                        
                        {{-- Laba Rugi Akumulasi --}}
                        <tr class="hover:bg-gray-50">
                            <td class="px-6 py-2 text-sm text-gray-700 pl-8">Laba/Rugi Periode Berjalan</td>
                            <td class="px-6 py-2 text-sm text-right font-mono font-bold {{ $ekuitas_labaRugiAkumulasi < 0 ? 'text-red-600' : 'text-green-600' }}">
                                @if($ekuitas_labaRugiAkumulasi < 0)
                                    (Rp {{ number_format(abs($ekuitas_labaRugiAkumulasi), 0, ',', '.') }})
                                @else
                                    Rp {{ number_format($ekuitas_labaRugiAkumulasi, 0, ',', '.') }}
                                @endif
                            </td>
                        </tr>

                        <tr class="bg-gray-50/50">
                            <td class="px-6 py-2 text-xs font-bold text-gray-600 pl-8">Total Ekuitas</td>
                            <td class="px-6 py-2 text-xs font-bold text-right text-gray-800 font-mono">Rp {{ number_format($totalEkuitas, 0, ',', '.') }}</td>
                        </tr>
                    </tbody>
                    <tfoot class="bg-indigo-50">
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
            <div class="bg-red-600 text-white text-center py-2 font-bold text-sm">
                <i class="material-icons text-sm mr-1 align-middle">warning</i>
                TIDAK SEIMBANG! Selisih: Rp {{ number_format($selisih, 2, ',', '.') }}
            </div>
        @else
            <div class="bg-green-600 text-white text-center py-2 font-bold text-sm">
                <i class="material-icons text-sm mr-1 align-middle">check_circle</i>
                SEIMBANG (BALANCE)
            </div>
        @endif
    </div>

    {{-- =================================== --}}
    {{-- LAPORAN LABA RUGI --}}
    {{-- =================================== --}}
    <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
        <div class="px-6 py-4 border-b border-gray-200 bg-gray-800 flex justify-between items-center text-white">
            <h5 class="font-bold text-lg flex items-center gap-2">
                <i class="material-icons">trending_up</i> Laporan Laba Rugi
            </h5>
            <span class="text-xs bg-gray-700 px-3 py-1 rounded-full">
                {{ \Carbon\Carbon::parse($startDate)->isoFormat('D MMM') }} - {{ \Carbon\Carbon::parse($endDate)->isoFormat('D MMM Y') }}
            </span>
        </div>
        
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-100">
                <tbody class="divide-y divide-gray-50">
                    
                    {{-- A. PENDAPATAN --}}
                    <tr class="bg-gray-50">
                        <td colspan="2" class="px-6 py-3 text-xs font-bold text-gray-600 uppercase tracking-wider">A. Pendapatan (Revenue)</td>
                    </tr>
                    @forelse ($labaRugi_pendapatan as $account)
                    <tr class="hover:bg-gray-50">
                        <td class="px-6 py-2 text-sm text-gray-700 pl-10">{{ $account->account_name }}</td>
                        <td class="px-6 py-2 text-sm text-right text-gray-900 font-mono">Rp {{ number_format($account->total_credit - $account->total_debit, 0, ',', '.') }}</td>
                    </tr>
                    @empty
                    <tr><td colspan="2" class="px-6 py-2 text-center text-gray-400 text-xs italic">Nihil.</td></tr>
                    @endforelse
                    <tr class="bg-blue-50/50 border-t border-blue-100">
                        <td class="px-6 py-2 text-sm font-bold text-gray-800 pl-10">Total Pendapatan</td>
                        <td class="px-6 py-2 text-sm font-bold text-right text-blue-700 font-mono">Rp {{ number_format($totalPendapatan, 0, ',', '.') }}</td>
                    </tr>

                    {{-- B. HPP --}}
                    <tr class="bg-gray-50">
                        <td colspan="2" class="px-6 py-3 text-xs font-bold text-gray-600 uppercase tracking-wider mt-4">B. Harga Pokok Penjualan (COGS)</td>
                    </tr>
                    @forelse ($labaRugi_hpp as $account)
                    <tr class="hover:bg-gray-50">
                        <td class="px-6 py-2 text-sm text-gray-700 pl-10">{{ $account->account_name }}</td>
                        <td class="px-6 py-2 text-sm text-right text-red-600 font-mono">(Rp {{ number_format($account->total_debit - $account->total_credit, 0, ',', '.') }})</td>
                    </tr>
                    @empty
                    <tr><td colspan="2" class="px-6 py-2 text-center text-gray-400 text-xs italic">Nihil.</td></tr>
                    @endforelse
                    <tr class="bg-red-50/50 border-t border-red-100">
                        <td class="px-6 py-2 text-sm font-bold text-gray-800 pl-10">Total HPP</td>
                        <td class="px-6 py-2 text-sm font-bold text-right text-red-600 font-mono">(Rp {{ number_format($totalHPP, 0, ',', '.') }})</td>
                    </tr>

                    {{-- LABA KOTOR --}}
                    <tr class="bg-gray-100 border-y border-gray-200">
                        <td class="px-6 py-3 text-sm font-bold text-gray-900 uppercase tracking-wide">Laba Kotor (Gross Profit)</td>
                        <td class="px-6 py-3 text-base font-bold text-right text-gray-900 font-mono">Rp {{ number_format($labaKotor, 0, ',', '.') }}</td>
                    </tr>

                    {{-- C. BEBAN --}}
                    <tr class="bg-gray-50">
                        <td colspan="2" class="px-6 py-3 text-xs font-bold text-gray-600 uppercase tracking-wider mt-4">C. Beban Operasional</td>
                    </tr>
                    @forelse ($labaRugi_beban as $account)
                    <tr class="hover:bg-gray-50">
                        <td class="px-6 py-2 text-sm text-gray-700 pl-10">{{ $account->account_name }}</td>
                        <td class="px-6 py-2 text-sm text-right text-red-600 font-mono">(Rp {{ number_format($account->total_debit - $account->total_credit, 0, ',', '.') }})</td>
                    </tr>
                    @empty
                    <tr><td colspan="2" class="px-6 py-2 text-center text-gray-400 text-xs italic">Nihil.</td></tr>
                    @endforelse
                    <tr class="bg-red-50/50 border-t border-red-100">
                        <td class="px-6 py-2 text-sm font-bold text-gray-800 pl-10">Total Beban</td>
                        <td class="px-6 py-2 text-sm font-bold text-right text-red-600 font-mono">(Rp {{ number_format($totalBeban, 0, ',', '.') }})</td>
                    </tr>
                </tbody>
                <tfoot class="bg-gray-800 text-white">
                    <tr>
                        <td class="px-6 py-4 text-base font-bold uppercase tracking-wide">Laba Bersih (Net Profit)</td>
                        <td class="px-6 py-4 text-xl font-bold text-right font-mono {{ $labaBersih < 0 ? 'text-red-300' : 'text-green-300' }}">
                            @if($labaBersih < 0)
                                (Rp {{ number_format(abs($labaBersih), 0, ',', '.') }})
                            @else
                                Rp {{ number_format($labaBersih, 0, ',', '.') }}
                            @endif
                        </td>
                    </tr>
                </tfoot>
            </table>
        </div>
    </div>

    {{-- =================================== --}}
    {{-- LAPORAN ARUS KAS --}}
    {{-- =================================== --}}
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        
        {{-- ARUS KAS --}}
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden flex flex-col h-full">
            <div class="px-6 py-4 border-b border-gray-200 bg-green-600 flex justify-between items-center text-white">
                <h5 class="font-bold text-sm flex items-center gap-2 uppercase tracking-wider">
                    <i class="material-icons text-base">payments</i> Arus Kas (Indirect)
                </h5>
            </div>
            <div class="p-0 flex-grow overflow-auto custom-scrollbar">
                <table class="min-w-full divide-y divide-gray-100">
                    <tbody class="divide-y divide-gray-50 bg-white">
                        
                        {{-- 1. OPERASI --}}
                        <tr class="bg-gray-50"><td colspan="2" class="px-6 py-2 text-xs font-bold text-gray-500 uppercase">1. Aktivitas Operasi</td></tr>
                        <tr>
                            <td class="px-6 py-2 text-sm text-gray-800 pl-8">Laba Bersih</td>
                            <td class="px-6 py-2 text-sm text-right font-bold">Rp {{ number_format($cf_operating_net_income, 0, ',', '.') }}</td>
                        </tr>
                        <tr>
                            <td class="px-6 py-1 text-xs text-gray-500 pl-10 italic">+ Penyusutan</td>
                            <td class="px-6 py-1 text-xs text-right text-gray-500">Rp {{ number_format($cf_operating_depreciation, 0, ',', '.') }}</td>
                        </tr>
                        {{-- Perubahan Modal Kerja --}}
                        @foreach([
                            ['Piutang Usaha', $cf_change_ar],
                            ['Persediaan', $cf_change_inventory],
                            ['Hutang Dagang', $cf_change_ap],
                            ['Deposit Klien', $cf_change_client_deposit],
                            ['Deposit Supplier', $cf_change_supplier_deposit]
                        ] as $item)
                        <tr>
                            <td class="px-6 py-1 text-xs text-gray-600 pl-10">Perubahan {{ $item[0] }}</td>
                            <td class="px-6 py-1 text-xs text-right font-mono {{ $item[1] < 0 ? 'text-red-500' : 'text-gray-600' }}">
                                Rp {{ number_format($item[1], 0, ',', '.') }}
                            </td>
                        </tr>
                        @endforeach
                        <tr class="bg-green-50 border-t border-green-100">
                            <td class="px-6 py-2 text-xs font-bold text-green-800 pl-8">Kas Bersih Operasi</td>
                            <td class="px-6 py-2 text-xs font-bold text-right text-green-800">Rp {{ number_format($total_cash_from_operations, 0, ',', '.') }}</td>
                        </tr>

                        {{-- 2. INVESTASI --}}
                        <tr class="bg-gray-50"><td colspan="2" class="px-6 py-2 text-xs font-bold text-gray-500 uppercase">2. Aktivitas Investasi</td></tr>
                        <tr>
                            <td class="px-6 py-2 text-sm text-gray-800 pl-8">Pembelian Aset</td>
                            <td class="px-6 py-2 text-sm text-right text-red-600 font-mono">(Rp {{ number_format($cf_investing_purchase_asset, 0, ',', '.') }})</td>
                        </tr>
                        <tr class="bg-yellow-50 border-t border-yellow-100">
                            <td class="px-6 py-2 text-xs font-bold text-yellow-800 pl-8">Kas Bersih Investasi</td>
                            <td class="px-6 py-2 text-xs font-bold text-right text-yellow-800">Rp {{ number_format($total_cash_from_investing, 0, ',', '.') }}</td>
                        </tr>

                        {{-- 3. PENDANAAN --}}
                        <tr class="bg-gray-50"><td colspan="2" class="px-6 py-2 text-xs font-bold text-gray-500 uppercase">3. Aktivitas Pendanaan</td></tr>
                        <tr>
                            <td class="px-6 py-2 text-sm text-gray-800 pl-8">Modal Masuk & Pinjaman</td>
                            <td class="px-6 py-2 text-sm text-right font-mono">Rp {{ number_format($cf_financing_capital_in + $cf_financing_loan_in, 0, ',', '.') }}</td>
                        </tr>
                        <tr>
                            <td class="px-6 py-2 text-sm text-gray-800 pl-8">Prive & Bayar Pinjaman</td>
                            <td class="px-6 py-2 text-sm text-right text-red-600 font-mono">(Rp {{ number_format($cf_financing_drawing + $cf_financing_loan_pay, 0, ',', '.') }})</td>
                        </tr>
                        <tr class="bg-blue-50 border-t border-blue-100">
                            <td class="px-6 py-2 text-xs font-bold text-blue-800 pl-8">Kas Bersih Pendanaan</td>
                            <td class="px-6 py-2 text-xs font-bold text-right text-blue-800">Rp {{ number_format($total_cash_from_financing, 0, ',', '.') }}</td>
                        </tr>
                    </tbody>
                    <tfoot class="bg-gray-800 text-white border-t-4 border-white">
                        <tr>
                            <td class="px-6 py-3 text-sm font-bold">Kenaikan Bersih Kas</td>
                            <td class="px-6 py-3 text-sm font-bold text-right font-mono">Rp {{ number_format($net_increase_cash, 0, ',', '.') }}</td>
                        </tr>
                        <tr>
                            <td class="px-6 py-2 text-xs font-medium text-gray-400">Saldo Awal</td>
                            <td class="px-6 py-2 text-xs font-medium text-right text-gray-400 font-mono">Rp {{ number_format($cash_beginning, 0, ',', '.') }}</td>
                        </tr>
                        <tr class="bg-gray-700">
                            <td class="px-6 py-3 text-sm font-bold text-green-400">SALDO AKHIR</td>
                            <td class="px-6 py-3 text-sm font-bold text-right text-green-400 font-mono">Rp {{ number_format($cash_ending, 0, ',', '.') }}</td>
                        </tr>
                    </tfoot>
                </table>
            </div>
        </div>

        {{-- SUB LEDGERS (PIUTANG & HUTANG) --}}
        <div class="space-y-6 flex flex-col h-full">
            
            {{-- Piutang --}}
            <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden flex-1">
                <div class="px-6 py-3 border-b border-gray-200 bg-gray-50 flex justify-between items-center">
                    <h5 class="font-bold text-sm text-gray-700 uppercase tracking-wider">Rincian Piutang (AR)</h5>
                </div>
                <div class="overflow-auto custom-scrollbar max-h-[300px]">
                    <table class="min-w-full divide-y divide-gray-100">
                        <thead class="bg-gray-50 sticky top-0 z-10">
                            <tr>
                                <th class="px-4 py-2 text-left text-xs font-medium text-gray-500">Klien</th>
                                <th class="px-4 py-2 text-left text-xs font-medium text-gray-500">Inv</th>
                                <th class="px-4 py-2 text-right text-xs font-medium text-gray-500">Sisa</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-50">
                            @forelse ($laporanPiutang as $inv)
                            <tr>
                                <td class="px-4 py-2 text-xs text-gray-700">{{ $inv->client->client_name }}</td>
                                <td class="px-4 py-2 text-xs text-indigo-600"><a href="{{ route('invoices.show', $inv->invoice_id) }}">{{ $inv->invoice_number }}</a></td>
                                <td class="px-4 py-2 text-xs text-right font-bold">Rp {{ number_format($inv->remaining_balance, 0, ',', '.') }}</td>
                            </tr>
                            @empty
                            <tr><td colspan="3" class="px-4 py-8 text-center text-xs text-gray-400 italic">Nihil</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
                <div class="px-4 py-2 bg-gray-50 border-t border-gray-200 flex justify-between items-center text-xs font-bold text-gray-700">
                    <span>Total Piutang</span>
                    <span>Rp {{ number_format($totalPiutang_SL, 0, ',', '.') }}</span>
                </div>
            </div>

            {{-- Utang --}}
            <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden flex-1">
                <div class="px-6 py-3 border-b border-gray-200 bg-gray-50 flex justify-between items-center">
                    <h5 class="font-bold text-sm text-gray-700 uppercase tracking-wider">Rincian Hutang (AP)</h5>
                </div>
                <div class="overflow-auto custom-scrollbar max-h-[300px]">
                    <table class="min-w-full divide-y divide-gray-100">
                        <thead class="bg-gray-50 sticky top-0 z-10">
                            <tr>
                                <th class="px-4 py-2 text-left text-xs font-medium text-gray-500">Supplier</th>
                                <th class="px-4 py-2 text-left text-xs font-medium text-gray-500">PO</th>
                                <th class="px-4 py-2 text-right text-xs font-medium text-gray-500">Sisa</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-50">
                            @forelse ($laporanUtang as $po)
                            <tr>
                                <td class="px-4 py-2 text-xs text-gray-700">{{ $po->supplier->supplier_name }}</td>
                                <td class="px-4 py-2 text-xs text-indigo-600"><a href="{{ route('purchase-orders.show', $po->po_id) }}">{{ $po->po_number }}</a></td>
                                <td class="px-4 py-2 text-xs text-right font-bold">Rp {{ number_format($po->remaining_balance, 0, ',', '.') }}</td>
                            </tr>
                            @empty
                            <tr><td colspan="3" class="px-4 py-8 text-center text-xs text-gray-400 italic">Nihil</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
                <div class="px-4 py-2 bg-gray-50 border-t border-gray-200 flex justify-between items-center text-xs font-bold text-gray-700">
                    <span>Total Hutang</span>
                    <span>Rp {{ number_format($totalUtang_SL, 0, ',', '.') }}</span>
                </div>
            </div>

        </div>
    </div>

</div>
@endsection
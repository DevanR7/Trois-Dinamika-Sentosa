<!DOCTYPE html>
<html>
<head>
    <title>Laporan Keuangan</title>
    <style>
        body { font-family: sans-serif; font-size: 10pt; color: #333; }
        .header { text-align: center; margin-bottom: 20px; border-bottom: 2px solid #333; padding-bottom: 10px; }
        .header h1 { margin: 0; font-size: 16pt; text-transform: uppercase; }
        .header p { margin: 2px 0; font-size: 9pt; color: #666; }
        
        table { width: 100%; border-collapse: collapse; margin-bottom: 20px; }
        th, td { padding: 6px 8px; border: 1px solid #ddd; }
        th { background-color: #f4f4f4; text-align: left; font-weight: bold; text-transform: uppercase; font-size: 8pt; }
        td { font-size: 9pt; }
        
        .text-right { text-align: right; }
        .text-center { text-align: center; }
        .font-bold { font-weight: bold; }
        .uppercase { text-transform: uppercase; }
        
        /* Warna Indikator */
        .text-emerald { color: #059669; }
        .text-rose { color: #e11d48; }
        .bg-gray { background-color: #f9fafb; }
        
        /* Page Break Utility */
        .page-break { page-break-after: always; }
        
        .section-title { 
            background-color: #333; color: #fff; 
            padding: 5px 10px; font-size: 11pt; font-weight: bold; margin-top: 20px; margin-bottom: 10px; 
        }
        .sub-header { background-color: #e5e7eb; font-weight: bold; }
    </style>
</head>
<body>

    {{-- HEADER HALAMAN 1 --}}
    <div class="header">
        <h1>{{ config('app.name') }} - Financial Report</h1>
        <p>Periode: {{ \Carbon\Carbon::parse($startDate)->format('d F Y') }} s/d {{ \Carbon\Carbon::parse($endDate)->format('d F Y') }}</p>
        <p>Dicetak pada: {{ now()->format('d/m/Y H:i') }}</p>
    </div>

    {{-- 1. LABA RUGI --}}
    <div class="section-title">1. LAPORAN LABA RUGI (INCOME STATEMENT)</div>
    <table>
        <tbody>
            {{-- Pendapatan --}}
            <tr class="sub-header"><td colspan="2">PENDAPATAN (REVENUE)</td></tr>
            @foreach($labaRugi_pendapatan as $acc)
            <tr>
                <td>{{ $acc->account_number }} - {{ $acc->account_name }}</td>
                <td class="text-right">{{ number_format($acc->total_credit - $acc->total_debit, 2, ',', '.') }}</td>
            </tr>
            @endforeach
            <tr class="font-bold bg-gray">
                <td class="text-right">TOTAL PENDAPATAN</td>
                <td class="text-right text-emerald">{{ number_format($totalPendapatan, 2, ',', '.') }}</td>
            </tr>

            {{-- HPP --}}
            <tr class="sub-header"><td colspan="2">HARGA POKOK PENJUALAN (COGS)</td></tr>
            @foreach($labaRugi_hpp as $acc)
            <tr>
                <td>{{ $acc->account_number }} - {{ $acc->account_name }}</td>
                <td class="text-right">({{ number_format($acc->total_debit - $acc->total_credit, 2, ',', '.') }})</td>
            </tr>
            @endforeach
            <tr class="font-bold bg-gray">
                <td class="text-right">TOTAL HPP</td>
                <td class="text-right text-rose">({{ number_format($totalHPP, 2, ',', '.') }})</td>
            </tr>

            {{-- Laba Kotor --}}
            <tr class="font-bold" style="background-color: #f3f4f6;">
                <td class="text-right uppercase">LABA KOTOR (GROSS PROFIT)</td>
                <td class="text-right">{{ number_format($labaKotor, 2, ',', '.') }}</td>
            </tr>

            {{-- Beban --}}
            <tr class="sub-header"><td colspan="2">BEBAN OPERASIONAL (EXPENSES)</td></tr>
            @foreach($labaRugi_beban as $acc)
            <tr>
                <td>{{ $acc->account_number }} - {{ $acc->account_name }}</td>
                <td class="text-right">({{ number_format($acc->total_debit - $acc->total_credit, 2, ',', '.') }})</td>
            </tr>
            @endforeach
            <tr class="font-bold bg-gray">
                <td class="text-right">TOTAL BEBAN</td>
                <td class="text-right text-rose">({{ number_format($totalBeban, 2, ',', '.') }})</td>
            </tr>
        </tbody>
        <tfoot>
            <tr style="background-color: #1f2937; color: white;">
                <td class="text-right font-bold uppercase" style="padding: 10px;">LABA BERSIH (NET INCOME)</td>
                <td class="text-right font-bold" style="padding: 10px;">Rp {{ number_format($labaBersih, 2, ',', '.') }}</td>
            </tr>
        </tfoot>
    </table>

    <div class="page-break"></div>

    {{-- 2. NERACA --}}
    <div class="section-title">2. NERACA (BALANCE SHEET)</div>
    <p style="text-align: right; font-size: 8pt; margin-bottom: 5px;">Per Tanggal: {{ \Carbon\Carbon::parse($endDate)->format('d M Y') }}</p>

    {{-- ASET --}}
    <table>
        <thead>
            <tr style="background-color: #d1fae5;"><th colspan="2" class="text-emerald">ASET (ASSETS)</th></tr>
        </thead>
        <tbody>
            @foreach($neraca_aset as $acc)
            <tr>
                <td>{{ $acc->account_number }} - {{ $acc->account_name }}</td>
                <td class="text-right">{{ number_format($acc->balance, 2, ',', '.') }}</td>
            </tr>
            @endforeach
            <tr class="font-bold bg-gray">
                <td class="uppercase">TOTAL ASET</td>
                <td class="text-right text-emerald">Rp {{ number_format($totalAset, 2, ',', '.') }}</td>
            </tr>
        </tbody>
    </table>

    {{-- LIABILITAS & EKUITAS --}}
    <table>
        <thead>
            <tr style="background-color: #ffe4e6;"><th colspan="2" class="text-rose">LIABILITAS (LIABILITIES)</th></tr>
        </thead>
        <tbody>
            @foreach($neraca_liabilitas as $acc)
            <tr>
                <td>{{ $acc->account_number }} - {{ $acc->account_name }}</td>
                <td class="text-right">{{ number_format($acc->balance, 2, ',', '.') }}</td>
            </tr>
            @endforeach
            <tr class="font-bold bg-gray">
                <td class="uppercase text-right">Total Liabilitas</td>
                <td class="text-right">{{ number_format($totalLiabilitas, 2, ',', '.') }}</td>
            </tr>
        </tbody>
    </table>

    <table>
        <thead>
            <tr style="background-color: #e0e7ff;"><th colspan="2" style="color: #4338ca;">EKUITAS (EQUITY)</th></tr>
        </thead>
        <tbody>
            @foreach($neraca_ekuitas_non_pl as $acc)
            <tr>
                <td>{{ $acc->account_number }} - {{ $acc->account_name }}</td>
                <td class="text-right">{{ number_format($acc->balance, 2, ',', '.') }}</td>
            </tr>
            @endforeach
            <tr>
                <td>Akumulasi Laba/Rugi (Retained Earnings)</td>
                <td class="text-right font-bold">{{ number_format($ekuitas_labaRugiAkumulasi, 2, ',', '.') }}</td>
            </tr>
            <tr class="font-bold bg-gray" style="border-top: 2px solid #333;">
                <td class="uppercase">TOTAL LIABILITAS & EKUITAS</td>
                <td class="text-right">Rp {{ number_format($totalLiabilitasDanEkuitas, 2, ',', '.') }}</td>
            </tr>
        </tbody>
    </table>

    {{-- Footer Indikator Neraca --}}
    @php $selisih = $totalAset - $totalLiabilitasDanEkuitas; @endphp
    @if(abs($selisih) > 1)
        <div style="padding: 10px; background-color: #fee2e2; color: #991b1b; border: 1px solid #f87171; text-align: center; font-weight: bold;">
            NERACA TIDAK SEIMBANG (Selisih: {{ number_format($selisih, 2) }})
        </div>
    @else
        <div style="padding: 5px; background-color: #d1fae5; color: #065f46; border: 1px solid #34d399; text-align: center; font-weight: bold; font-size: 8pt;">
            Neraca Seimbang (Balanced)
        </div>
    @endif

    <div class="page-break"></div>

    {{-- 3. ARUS KAS --}}
    <div class="section-title">3. ARUS KAS (CASH FLOW) - Metode Tidak Langsung</div>
    <table>
        <thead>
            <tr><th colspan="2">AKTIVITAS OPERASI</th></tr>
        </thead>
        <tbody>
            <tr><td>Laba Bersih</td><td class="text-right font-bold">{{ number_format($cf_operating_net_income, 2, ',', '.') }}</td></tr>
            <tr><td style="padding-left: 20px; color: #666;">+ Depresiasi</td><td class="text-right">{{ number_format($cf_operating_depreciation, 2, ',', '.') }}</td></tr>
            <tr><td style="padding-left: 20px; color: #666;">+ Perubahan Piutang</td><td class="text-right">{{ number_format($cf_change_ar, 2, ',', '.') }}</td></tr>
            <tr><td style="padding-left: 20px; color: #666;">+ Perubahan Persediaan</td><td class="text-right">{{ number_format($cf_change_inventory, 2, ',', '.') }}</td></tr>
            <tr><td style="padding-left: 20px; color: #666;">+ Perubahan Hutang</td><td class="text-right">{{ number_format($cf_change_ap, 2, ',', '.') }}</td></tr>
            <tr class="bg-gray font-bold"><td>Kas Bersih dari Operasi</td><td class="text-right">{{ number_format($total_cash_from_operations, 2, ',', '.') }}</td></tr>
        </tbody>
    </table>

    <table>
        <thead>
            <tr><th colspan="2">AKTIVITAS INVESTASI</th></tr>
        </thead>
        <tbody>
            <tr><td style="padding-left: 20px; color: #666;">Pembelian Aset Tetap</td><td class="text-right">{{ number_format($cf_investing_purchase_asset, 2, ',', '.') }}</td></tr>
            <tr class="bg-gray font-bold"><td>Kas Bersih dari Investasi</td><td class="text-right">{{ number_format($total_cash_from_investing, 2, ',', '.') }}</td></tr>
        </tbody>
    </table>

    <table>
        <thead>
            <tr><th colspan="2">AKTIVITAS PENDANAAN</th></tr>
        </thead>
        <tbody>
            <tr><td style="padding-left: 20px; color: #666;">Setoran Modal</td><td class="text-right">{{ number_format($cf_financing_capital_in, 2, ',', '.') }}</td></tr>
            <tr><td style="padding-left: 20px; color: #666;">Prive / Dividen</td><td class="text-right">({{ number_format(abs($cf_financing_drawing), 2, ',', '.') }})</td></tr>
            <tr><td style="padding-left: 20px; color: #666;">Hutang Bank (Terima/Bayar)</td><td class="text-right">{{ number_format($cf_financing_loan_in + $cf_financing_loan_pay, 2, ',', '.') }}</td></tr>
            <tr class="bg-gray font-bold"><td>Kas Bersih dari Pendanaan</td><td class="text-right">{{ number_format($total_cash_from_financing, 2, ',', '.') }}</td></tr>
        </tbody>
    </table>

    <table style="border: 2px solid #333;">
        <tr>
            <td class="font-bold">Kenaikan (Penurunan) Kas Bersih</td>
            <td class="text-right font-bold">{{ number_format($net_increase_cash, 2, ',', '.') }}</td>
        </tr>
        <tr>
            <td>Saldo Kas Awal</td>
            <td class="text-right">{{ number_format($cash_beginning, 2, ',', '.') }}</td>
        </tr>
        <tr style="background-color: #333; color: white;">
            <td class="font-bold uppercase">Saldo Kas Akhir</td>
            <td class="text-right font-bold">Rp {{ number_format($cash_ending, 2, ',', '.') }}</td>
        </tr>
    </table>

</body>
</html>
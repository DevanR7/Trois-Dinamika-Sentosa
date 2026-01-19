<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Bukti Titipan {{ $invoice->invoice_number }}</title>
    <style>
        /* 1. SETUP KERTAS (9.5" x 5.5") */
        @page {
            /* Margin: Atas 20px, Kanan 25px, Bawah 25px, Kiri 25px */
            margin: 20px 25px 25px 25px; 
            size: 684pt 396pt;
        }

        body {
            font-family: Arial, Helvetica, sans-serif;
            font-size: 9pt;
            line-height: 1.2;
            color: #000;
        }

        /* UTILITIES */
        table { border-collapse: collapse; width: 100%; }
        .text-bold { font-weight: bold; }
        .text-right { text-align: right; }
        .text-center { text-align: center; }
        .text-left { text-align: left; }
        .uppercase { text-transform: uppercase; }
        .valign-top { vertical-align: top; }

        /* GARIS TEBAL PEMISAH */
        .thick-line-top { border-top: 2px solid #000; }
        .thick-line-bottom { border-bottom: 2px solid #000; }
        
        /* HEADER SECTION */
        .header-title {
            font-size: 14pt;
            font-weight: bold;
            text-transform: uppercase;
            text-align: left;
        }
        .company-name {
            font-size: 11pt;
            font-weight: bold;
            font-style: italic;
            text-align: right;
        }
        
        /* INFO TABLE */
        .info-table { margin-top: 8px; margin-bottom: 8px; }
        .info-table td { padding: 1px 0; vertical-align: top; }
        .info-label { width: 70px; font-weight: bold; }
        .info-separator { width: 10px; text-align: center; }

        /* ITEMS TABLE */
        .items-table { 
            width: 100%; 
            margin-top: 5px; 
            margin-bottom: 185px; /* Margin bawah untuk footer */
        }
        .items-table th {
            border-top: 2px solid #000;
            border-bottom: 2px solid #000;
            background-color: #f0f0f0;
            padding: 6px 4px;
            font-size: 9pt;
            font-weight: bold;
            text-transform: uppercase;
            text-align: center;
        }
        .items-table td {
            padding: 5px 4px;
            font-size: 9pt;
            border-bottom: 1px dotted #ccc;
        }

        /* Lebar Kolom */
        .col-name { text-align: left; width: 45%; }
        .col-qty { text-align: center; width: 10%; }
        .col-unit { text-align: center; width: 10%; }
        .col-price { text-align: right; width: 15%; }
        .col-total { text-align: right; width: 20%; }

        /* FOOTER WRAPPER (FIXED) */
        .footer-wrapper {
            position: fixed;
            bottom: -20px;
            left: 0;
            right: 0;
            height: 175px;
            background-color: #fff;
        }

        /* Layout Footer */
        .footer-content { width: 100%; }
        .footer-col-left { width: 60%; vertical-align: top; padding-right: 15px; }
        .footer-col-right { width: 40%; vertical-align: top; }

        /* 1. Bagian Kiri: Catatan & Bank */
        .note-box {
            font-size: 9pt;
            margin-bottom: 10px;
        }
        
        /* Bank List Horizontal */
        .bank-container {
            margin-top: 8px;
            font-size: 8pt;
            line-height: 1.4;
            border: 1px dashed #999;
            padding: 5px;
            background-color: #fafafa;
        }
        .bank-label { font-weight: bold; text-decoration: underline; margin-right: 5px; }
        .bank-item {
            display: inline-block;
            font-weight: bold; /* Dipertebal agar jelas */
        }

        /* 2. Bagian Kanan: Total & Disclaimer */
        .totals-table { width: 100%; font-size: 9pt; }
        .totals-table td { padding: 2px 0; text-align: right; }
        .grand-total-row td {
            border-top: 2px solid #000;
            font-weight: bold;
            font-size: 11pt;
            padding-top: 4px;
        }
        
        .disclaimer { 
            font-style: italic; 
            font-size: 8pt; 
            margin-top: 8px; 
            text-align: right;
        }

        /* 3. Bagian Tanda Tangan */
        .signature-section {
            position: absolute;
            bottom: 25px;
            left: 0;
            right: 0;
            width: 100%;
        }
        .signature-box { text-align: center; }
        .signature-line {
            border-bottom: 1px solid #000;
            width: 80%;
            margin: 40px auto 0 auto;
        }
        .signature-name { font-weight: bold; margin-top: 2px; }

    </style>
</head>
<body>

    {{-- HEADER --}}
    <table style="margin-bottom: 5px;">
        <tr>
            <td width="50%" class="header-title">BUKTI TITIPAN</td>
            <td width="50%" class="company-name">{{ $settings['company_name'] ?? 'NAMA PERUSAHAAN' }}</td>
        </tr>
    </table>
    <div class="thick-line-top"></div>

    {{-- INFO PELANGGAN & TANGGAL --}}
    <table class="info-table">
        <tr>
            {{-- KIRI --}}
            <td width="55%">
                <table>
                    <tr>
                        <td class="info-label">Nomor</td><td class="info-separator">:</td>
                        <td><b>{{ $invoice->invoice_number }}</b></td>
                    </tr>
                    <tr>
                        <td class="info-label">Kepada</td><td class="info-separator">:</td>
                        <td><b>{{ $invoice->client->client_name }}</b></td>
                    </tr>
                    <tr>
                        <td class="info-label">Alamat</td><td class="info-separator">:</td>
                        <td>{{ $invoice->client->address ?? '-' }}</td>
                    </tr>
                    <tr>
                        <td class="info-label">Telp</td><td class="info-separator">:</td>
                        <td>{{ $invoice->client->phone_number ?? '-' }}</td>
                    </tr>
                </table>
            </td>
            {{-- KANAN --}}
            <td width="45%">
                <table>
                    <tr>
                        <td class="info-label">Tanggal</td><td class="info-separator">:</td>
                        <td>{{ $invoice->order_date->format('d/m/Y') }}</td>
                    </tr>
                    <tr>
                        <td class="info-label">Jatuh Tempo</td><td class="info-separator">:</td>
                        <td>{{ $invoice->due_date->format('d/m/Y') }}</td>
                    </tr>
                    <tr>
                        <td class="info-label">Salesman</td><td class="info-separator">:</td>
                        <td>{{ $invoice->sales->full_name ?? 'Admin' }}</td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>

    {{-- TABEL ITEM --}}
    <table class="items-table">
        <thead>
            <tr>
                <th class="col-name text-left">NAMA BARANG</th>
                <th class="col-qty">KUANTITAS</th>
                <th class="col-unit">SATUAN</th>
                <th class="col-price">HARGA</th>
                <th class="col-total">SUBTOTAL</th>
            </tr>
        </thead>
        <tbody>
            @foreach($invoice->items as $item)
            <tr>
                <td class="col-name">{{ $item->product->product_name }}</td>
                <td class="col-qty">{{ 0 + $item->quantity }}</td>
                <td class="col-unit">{{ $item->product->unit->name ?? 'Pcs' }}</td>
                <td class="col-price">{{ number_format($item->price_per_unit, 0, ',', '.') }}</td>
                <td class="col-total">{{ number_format($item->subtotal, 0, ',', '.') }}</td>
            </tr>
            @endforeach
        </tbody>
    </table>

    {{-- FOOTER FIXED --}}
    <div class="footer-wrapper">
        <div class="thick-line-top" style="margin-bottom: 5px;"></div>

        {{-- KONTEN FOOTER --}}
        <table class="footer-content">
            <tr>
                {{-- KOLOM KIRI: CATATAN & BANK --}}
                <td class="footer-col-left">
                    <div class="note-box">
                        <b>Catatan:</b> {{ $invoice->notes ?? '-' }}
                    </div>
                    
                    {{-- Bank List: HANYA MENAMPILKAN 1 BANK PERTAMA --}}
                    <div class="bank-container">
                        <span class="bank-label">Transfer Pembayaran:</span>
                        @if($bank = $bankAccounts->first())
                            <span class="bank-item">
                                {{ $bank->bank_name }} {{ $bank->account_number }} (a/n {{ $bank->account_name }})
                            </span>
                        @endif
                    </div>
                </td>

                {{-- KOLOM KANAN: TOTAL & DISCLAIMER --}}
                <td class="footer-col-right">
                    <table class="totals-table">
                        <tr>
                            <td>Sub Total :</td>
                            <td width="90">Rp {{ number_format($invoice->subtotal, 0, ',', '.') }}</td>
                        </tr>
                        @if($invoice->discount_amount > 0)
                        <tr>
                            <td>Discount ({{ 0 + $invoice->discount_percentage }}%) :</td>
                            <td>- {{ number_format($invoice->discount_amount, 0, ',', '.') }}</td>
                        </tr>
                        @endif
                        
                        @foreach($invoice->taxes as $tax)
                        <tr>
                            <td>{{ $tax->name }} ({{ 0 + $tax->pivot->rate }}%) :</td>
                            <td>{{ number_format($tax->pivot->amount, 0, ',', '.') }}</td>
                        </tr>
                        @endforeach

                        @foreach($invoice->additionalCosts as $cost)
                        <tr>
                            <td>{{ $cost->description }} :</td>
                            <td>{{ number_format($cost->amount, 0, ',', '.') }}</td>
                        </tr>
                        @endforeach

                        <tr class="grand-total-row">
                            <td>Total :</td>
                            <td>Rp {{ number_format($invoice->total_amount, 0, ',', '.') }}</td>
                        </tr>
                    </table>

                    {{-- Disclaimer Komplain --}}
                    <div class="disclaimer">
                        * Komplain maks 1 minggu setelah barang diterima (Disertai video unboxing)
                    </div>
                </td>
            </tr>
        </table>

        {{-- AREA TANDA TANGAN (Berdampingan di Kiri) --}}
        <div class="signature-section">
            <table width="100%">
                <tr>
                    {{-- 1. Tanda Tangan Penerima --}}
                    <td width="30%" class="signature-box">
                        <div><b>Penerima,</b></div>
                        <div class="signature-line"></div>
                        <div class="signature-name">{{ $invoice->client->client_name }}</div>
                    </td>

                    {{-- 2. Tanda Tangan Hormat Kami --}}
                    <td width="30%" class="signature-box">
                        <div><b>Hormat Kami,</b></div>
                        <div class="signature-line"></div>
                        <div class="signature-name">{{ $settings['company_name'] ?? 'Admin' }}</div>
                    </td>

                    {{-- 3. Area Kosong (Kanan) --}}
                    <td width="40%"></td>
                </tr>
            </table>
        </div>
    </div>

</body>
</html>
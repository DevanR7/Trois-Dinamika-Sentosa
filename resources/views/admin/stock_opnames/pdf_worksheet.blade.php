<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Worksheet Stock Opname</title>
    <style>
        /* Reset & Base */
        @page { margin: 20px 30px; }
        body {
            font-family: sans-serif;
            font-size: 11px;
            color: #333;
            line-height: 1.4;
        }

        /* Header */
        .header-table {
            width: 100%;
            margin-bottom: 20px;
            border-bottom: 2px solid #333;
            padding-bottom: 10px;
        }
        .company-name {
            font-size: 18px;
            font-weight: bold;
            color: #000;
            text-transform: uppercase;
        }
        .doc-title {
            font-size: 16px;
            font-weight: bold;
            text-align: right;
            color: #555;
            text-transform: uppercase;
        }
        .meta-info td {
            padding: 2px 0;
            font-size: 10px;
        }

        /* Main Table */
        .data-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
        }
        .data-table th, .data-table td {
            border: 1px solid #999;
            padding: 8px 6px;
            vertical-align: middle;
        }
        .data-table th {
            background-color: #f0f0f0;
            font-weight: bold;
            text-transform: uppercase;
            font-size: 9px;
            text-align: center;
        }
        
        /* Striped Rows for readability */
        .data-table tr:nth-child(even) {
            background-color: #fcfcfc;
        }

        /* Column Specifics */
        .col-no { width: 30px; text-align: center; }
        .col-code { width: 80px; font-family: monospace; font-weight: bold; }
        .col-product { } /* Auto width */
        .col-unit { width: 50px; text-align: center; }
        .col-system { width: 60px; text-align: center; color: #777; font-size: 10px; }
        .col-physical { width: 80px; } /* Area tulis */
        .col-notes { width: 100px; } /* Area tulis */

        /* Footer / Signatures */
        .footer-table {
            width: 100%;
            margin-top: 30px;
        }
        .signature-box {
            width: 30%;
            text-align: center;
        }
        .signature-line {
            border-bottom: 1px solid #000;
            margin-top: 50px;
            margin-bottom: 5px;
        }
        .date-print {
            font-size: 9px;
            color: #888;
            font-style: italic;
            margin-top: 5px;
            text-align: right;
        }
    </style>
</head>
<body>

    {{-- HEADER --}}
    <table class="header-table">
        <tr>
            <td width="60%" valign="top">
                <div class="company-name">{{ config('app.name', 'ERP SYSTEM') }}</div>
                <div style="font-size: 10px; margin-top: 4px;">
                    Lembar Kerja Audit Inventori<br>
                    Internal Use Only
                </div>
            </td>
            <td width="40%" valign="top">
                <div class="doc-title">WORKSHEET STOCK OPNAME</div>
                <table width="100%" class="meta-info">
                    <tr>
                        <td align="right" width="40%">Tanggal Cetak:</td>
                        <td align="right"><strong>{{ \Carbon\Carbon::parse($date)->format('d F Y') }}</strong></td>
                    </tr>
                    <tr>
                        <td align="right">Dicetak Oleh:</td>
                        <td align="right">{{ Auth::user()->full_name ?? 'System' }}</td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>

    {{-- CONTENT TABLE --}}
    <table class="data-table">
        <thead>
            <tr>
                <th class="col-no">No</th>
                <th class="col-code">Kode SKU</th>
                <th class="col-product" align="left">Nama Produk</th>
                <th class="col-unit">Satuan</th>
                <th class="col-system">Stok Sistem</th>
                <th class="col-physical">Fisik (Qty)</th>
                <th class="col-notes">Catatan / Kondisi</th>
            </tr>
        </thead>
        <tbody>
            @foreach($products as $index => $product)
                <tr>
                    <td align="center">{{ $index + 1 }}</td>
                    <td class="col-code">{{ $product->product_code }}</td>
                    <td>{{ $product->product_name }}</td>
                    <td align="center">{{ $product->unit->name ?? '-' }}</td>
                    
                    {{-- Stok Sistem (Referensi untuk audit, bisa di-hide jika ingin Blind Audit) --}}
                    <td align="center">
                        {{ number_format($product->stock_quantity, 0, ',', '.') }}
                    </td>
                    
                    {{-- Area Kosong untuk ditulis tangan --}}
                    <td></td> 
                    <td></td> 
                </tr>
            @endforeach
            
            {{-- Baris Kosong Tambahan (Optional, jika ada barang temuan baru) --}}
            @for($i = 0; $i < 3; $i++)
                <tr>
                    <td align="center" style="color:#ccc;">+</td>
                    <td></td>
                    <td style="color:#ccc; font-style:italic;">(Item Tambahan/Temuan)</td>
                    <td></td>
                    <td>-</td>
                    <td></td>
                    <td></td>
                </tr>
            @endfor
        </tbody>
    </table>

    {{-- FOOTER --}}
    <table class="footer-table">
        <tr>
            <td class="signature-box">
                Dihitung Oleh (Gudang),
                <div class="signature-line"></div>
                Nama:
            </td>
            <td class="signature-box">
                Dicek Oleh (Spv/Admin),
                <div class="signature-line"></div>
                Nama:
            </td>
            <td class="signature-box">
                Disetujui Oleh (Manager),
                <div class="signature-line"></div>
                Nama:
            </td>
        </tr>
    </table>

    <div class="date-print">
        Generated by System pada {{ now()->format('d-m-Y H:i:s') }}
    </div>

</body>
</html>
<!DOCTYPE html>
<html>
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
    <title>Lembar Kerja Stock Opname</title>
    <style>
        body { font-family: sans-serif; font-size: 12px; color: #333; }
        .header { text-align: center; margin-bottom: 20px; border-bottom: 2px solid #333; padding-bottom: 10px; }
        .header h1 { margin: 0; font-size: 18px; text-transform: uppercase; }
        .header p { margin: 5px 0 0; font-size: 11px; }
        
        .meta { width: 100%; margin-bottom: 20px; }
        .meta td { padding: 4px; }
        .meta-label { font-weight: bold; width: 100px; }
        
        table.data { width: 100%; border-collapse: collapse; }
        table.data th, table.data td { border: 1px solid #999; padding: 6px 8px; }
        table.data th { background-color: #eee; text-align: left; font-weight: bold; text-transform: uppercase; font-size: 10px; }
        
        .col-check { width: 30px; text-align: center; }
        .col-code { width: 80px; }
        .col-name { }
        .col-unit { width: 50px; text-align: center; }
        .col-qty { width: 120px; } /* Area kosong untuk tulis tangan */
        
        .footer { position: fixed; bottom: 0; left: 0; right: 0; text-align: center; font-size: 10px; color: #777; border-top: 1px solid #ddd; padding-top: 5px; }
    </style>
</head>
<body>

    <div class="header">
        <h1>Lembar Kerja Stock Opname</h1>
        <p>{{ config('app.name') }}</p>
    </div>

    <table class="meta">
        <tr>
            <td class="meta-label">Tanggal Cetak:</td>
            <td>{{ now()->format('d F Y, H:i') }}</td>
            <td class="meta-label">Petugas:</td>
            <td>_______________________</td>
        </tr>
        <tr>
            <td class="meta-label">Lokasi/Gudang:</td>
            <td>Semua Gudang</td>
            <td class="meta-label">Validator:</td>
            <td>_______________________</td>
        </tr>
    </table>

    <table class="data">
        <thead>
            <tr>
                <th class="col-check">Cek</th>
                <th class="col-code">Kode</th>
                <th class="col-name">Nama Barang</th>
                <th class="col-unit">Satuan</th>
                <th class="col-qty">Stok Sistem</th>
                <th class="col-qty">Stok Fisik (Isi)</th>
            </tr>
        </thead>
        <tbody>
            @foreach($products as $product)
                <tr>
                    <td class="col-check">□</td>
                    <td class="col-code">{{ $product->product_code }}</td>
                    <td class="col-name">{{ $product->product_name }}</td>
                    <td class="col-unit">{{ $product->unit->name ?? '-' }}</td>
                    <td style="text-align: right; color: #777;">
                        {{ number_format($product->stock_quantity, 0, ',', '.') }}
                    </td>
                    <td></td> {{-- Kolom kosong untuk ditulis --}}
                </tr>
            @endforeach
        </tbody>
    </table>

    <div class="footer">
        Dicetak otomatis oleh Sistem pada {{ now()->format('d/m/Y H:i') }} | Halaman <span class="page-number"></span>
    </div>

</body>
</html>
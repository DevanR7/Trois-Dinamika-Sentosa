<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Lembar Kerja Stock Opname</title>
    <style>
        body { font-family: sans-serif; font-size: 12px; }
        .header { text-align: center; margin-bottom: 20px; border-bottom: 2px solid #000; padding-bottom: 10px; }
        .header h1 { margin: 0; font-size: 18px; text-transform: uppercase; }
        .header p { margin: 5px 0 0; }
        
        table { width: 100%; border-collapse: collapse; margin-top: 10px; }
        th, td { border: 1px solid #000; padding: 6px 8px; text-align: left; }
        th { background-color: #f0f0f0; font-weight: bold; text-align: center; }
        
        /* Kolom Fisik dikosongkan/dibesarkan agar mudah ditulis tangan */
        .col-fisik { width: 15%; } 
        .col-ket { width: 20%; }
        
        .footer { margin-top: 30px; width: 100%; }
        .signature { width: 30%; float: right; text-align: center; }
        .signature-line { border-bottom: 1px solid #000; margin-top: 50px; }
        
        .page-number:before { content: counter(page); }
    </style>
</head>
<body>

    <div class="header">
        <h1>Lembar Kerja Stock Opname</h1>
        <p>Tanggal Cetak: {{ $date->format('d F Y H:i') }}</p>
    </div>

    <table width="100%">
        <thead>
            <tr>
                <th style="width: 5%;">No</th>
                <th style="width: 15%;">Kode Barang</th>
                <th style="width: 35%;">Nama Produk</th>
                <th style="width: 10%;">Satuan</th>
                <th style="width: 10%;">Stok Sistem</th>
                <th class="col-fisik">Stok Fisik (Isi)</th>
                <th class="col-ket">Keterangan</th>
            </tr>
        </thead>
        <tbody>
            @foreach($products as $product)
            <tr>
                <td style="text-align: center;">{{ $loop->iteration }}</td>
                <td>{{ $product->product_code }}</td>
                <td>{{ $product->product_name }}</td>
                <td style="text-align: center;">{{ $product->unit->name ?? '-' }}</td>
                <td style="text-align: center;">{{ $product->stock_quantity }}</td>
                <td></td> {{-- Kosong untuk tulis tangan --}}
                <td></td> {{-- Kosong untuk keterangan --}}
            </tr>
            @endforeach
        </tbody>
    </table>

    <div class="footer">
        <div class="signature">
            <p>Dihitung Oleh:</p>
            <div class="signature-line"></div>
            <p>( Petugas Gudang )</p>
        </div>
    </div>

</body>
</html>
<!DOCTYPE html>
<html>
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
    <title>Purchase Order {{ $purchaseOrder->po_number }}</title>
    <style>
        body { font-family: 'Helvetica', sans-serif; font-size: 11pt; color: #333; }
        .container { width: 100%; margin: 0 auto; padding: 0; }
        .header h1 { margin: 0; font-size: 24px; }
        table { width: 100%; border-collapse: collapse; }
        th, td { padding: 8px; text-align: left; vertical-align: top;}
        .bordered th, .bordered td { border: 1px solid #ccc; }
        .bordered th { background-color: #f2f2f2; }
        .text-end { text-align: right; }
        .text-center { text-align: center; }
        .fw-bold { font-weight: bold; }
        .mb-4 { margin-bottom: 1.5rem; }
        .mt-4 { margin-top: 1.5rem; }
        .row::after { content: ""; clear: both; display: table; }
        .col-6 { float: left; width: 50%; box-sizing: border-box; }
        .summary-table { width: 60%; float: right; }
        .notes { margin-top: 20px; font-size: 10pt; }
    </style>
</head>
<body>
    <div class="container">
        <table style="margin-bottom: 25px;">
            <tr>
                <td style="width: 50%; vertical-align: top;">
                    <h1 style="margin: 0;">PURCHASE ORDER</h1>
                    <p style="margin: 0;">#{{ $purchaseOrder->po_number }}</p>
                </td>
                <td style="width: 50%; text-align: right; vertical-align: top;">
                    <strong>Dari:</strong><br>
                    Nama Perusahaan Anda<br>
                    Alamat Perusahaan Anda
                </td>
            </tr>
        </table>

        <table style="margin-bottom: 25px;">
             <tr>
                <td style="width: 50%; vertical-align: top;">
                    <strong>Kepada Yth:</strong><br>
                    {{ $purchaseOrder->supplier->supplier_name }}<br>
                    {!! nl2br(e($purchaseOrder->supplier->address)) !!}
                </td>
                <td style="width: 50%; text-align: right; vertical-align: top;">
                     <strong>Tanggal Pesanan:</strong> {{ optional($purchaseOrder->order_date)->format('d F Y') }}<br>
                    @if($purchaseOrder->due_date)
                        <strong>Tanggal Jatuh Tempo:</strong> {{ optional($purchaseOrder->due_date)->format('d F Y') }}
                    @endif
                </td>
            </tr>
        </table>

        <table class="bordered">
            <thead>
                <tr>
                    <th style="width: 5%;">#</th>
                    <th>Deskripsi Produk</th>
                    <th class="text-center" style="width: 15%;">Kuantitas</th>
                    <th class="text-end" style="width: 20%;">Harga Satuan</th>
                    <th class="text-end" style="width: 20%;">Jumlah</th>
                </tr>
            </thead>
            <tbody>
                @foreach($purchaseOrder->items as $item)
                <tr>
                    <td class="text-center">{{ $loop->iteration }}</td>
                    <td>{{ $item->product->product_name ?? 'Produk Dihapus' }}</td>
                    <td class="text-center">{{ $item->quantity }} {{ $item->product->unit->name ?? '' }}</td>
                    <td class="text-end">Rp {{ number_format($item->price_per_unit, 0, ',', '.') }}</td>
                    <td class="text-end">Rp {{ number_format($item->subtotal, 0, ',', '.') }}</td>
                </tr>
                @endforeach
            </tbody>
        </table>

        <div class="row mt-4">
            <div class="col-6">
                @if($purchaseOrder->notes)
                    <div class="notes">
                        <strong>Catatan:</strong><br>
                        {{ $purchaseOrder->notes }}
                    </div>
                @endif
            </div>
            <div class="col-6">
                <table class="summary-table">
                    <tr>
                        <td>Subtotal Barang</td>
                        <td class="text-end">Rp {{ number_format($purchaseOrder->subtotal ?? 0, 0, ',', '.') }}</td>
                    </tr>
                    <tr>
                        <td>Diskon / Fee</td>
                        <td class="text-end">(-) Rp {{ number_format($purchaseOrder->disc_fee_amount ?? 0, 0, ',', '.') }}</td>
                    </tr>
                    <tr>
                        <td>Diskon Pembulatan</td>
                        <td class="text-end">(-) Rp {{ number_format($purchaseOrder->rounding_discount_amount ?? 0, 0, ',', '.') }}</td>
                    </tr>
                    <tr style="border-bottom: 1px solid #ccc;">
                        <td style="padding-bottom: 8px;">Dasar Pengenaan Pajak (DPP)</td>
                        <td class="text-end" style="padding-bottom: 8px;">Rp {{ number_format($purchaseOrder->dpp ?? 0, 0, ',', '.') }}</td>
                    </tr>
                    <tr>
                        <td style="padding-top: 8px;">PPN ({{ $purchaseOrder->tax->rate ?? 0 }}%)</td>
                        <td class="text-end" style="padding-top: 8px;">(+) Rp {{ number_format($purchaseOrder->ppn ?? 0, 0, ',', '.') }}</td>
                    </tr>
                    <tr>
                        <td>Ongkos Kirim</td>
                        <td class="text-end">(+) Rp {{ number_format($purchaseOrder->shipping_amount ?? 0, 0, ',', '.') }}</td>
                    </tr>
                    <tr class="fw-bold">
                        <td style="border-top: 2px solid #333; padding-top: 8px;">Total Tagihan (Grand Total)</td>
                        <td class="text-end" style="border-top: 2px solid #333; padding-top: 8px;">Rp {{ number_format($purchaseOrder->total_amount ?? 0, 0, ',', '.') }}</td>
                    </tr>
                </table>
            </div>
        </div>
    </div>
</body>
</html>
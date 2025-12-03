<!DOCTYPE html>
<html>
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
    <title>Purchase Order {{ $purchaseOrder->po_number }}</title>
    <style>
        @page { margin: 10px 25px; }
        body { font-family: 'Helvetica', 'Arial', sans-serif; font-size: 9pt; color: #333; }
        table { width: 100%; border-collapse: collapse; }
        th, td { padding: 4px 6px; text-align: left; vertical-align: top; }
        
        .item-table thead th { 
            border-top: 1px solid #000;
            border-bottom: 1px solid #000;
            padding: 5px;
        }
        .item-table tbody td {
            border-bottom: 1px dotted #ccc;
            vertical-align: middle;
            padding: 5px 6px;
        }
        
        .text-end { text-align: right; }
        .text-center { text-align: center; }
        .fw-bold { font-weight: bold; }
        
        #footer {
            position: fixed;
            bottom: 25px; /* Jarak dari bawah halaman */
            left: 25px;
            right: 25px;
        }
        .summary-table td { padding: 1.5px 3px; }
        .notes-box {
            font-size: 8pt;
            padding: 5px;
            border: 1px solid #ccc;
            margin-bottom: 10px;
        }
    </style>
</head>
<body>
    {{-- Header Info PO --}}
    <table style="margin-bottom: 20px;">
        <tr>
            <td style="width: 50%; vertical-align: top;">
                <strong>{{ $purchaseOrder->supplier->supplier_name }}</strong><br>
                {!! nl2br(e($purchaseOrder->supplier->address)) !!}
                <hr style="border: none; border-top: 1px solid #ccc; margin: 5px 0;">
                <strong>No. Faktur Supplier:</strong> {{ $purchaseOrder->supplier_invoice_number ?? '-' }}
            </td>
            <td style="width: 50%; vertical-align: top; text-align: right;">
                <h2 style="margin: 0; font-size: 18pt;">PURCHASE ORDER</h2>
                <table style="font-size: 9pt; float: right;">
                    <tr><td style="padding: 1px 5px 1px 0;" class="text-end">No. PO</td><td style="padding: 1px 0;">: {{ $purchaseOrder->po_number }}</td></tr>
                    <tr><td style="padding: 1px 5px 1px 0;" class="text-end">Tanggal</td><td style="padding: 1px 0;">: {{ optional($purchaseOrder->order_date)->format('d/m/Y') }}</td></tr>
                    @if($purchaseOrder->due_date)
                    <tr><td style="padding: 1px 5px 1px 0;" class="text-end">Jatuh Tempo</td><td style="padding: 1px 0;">: {{ optional($purchaseOrder->due_date)->format('d/m/Y') }}</td></tr>
                    @endif
                </table>
            </td>
        </tr>
    </table>

    <div style="clear: both;"></div>

    {{-- Tabel Rincian Item --}}
    <table class="item-table">
        <thead>
            <tr>
                <th class="text-center" style="width: 8%;">Qty</th>
                <th>Nama Barang</th>
                <th class="text-end" style="width: 18%;">Harga</th>
                <th class="text-center" style="width: 20%;">Disc.</th>
                <th class="text-end" style="width: 20%;">Jumlah</th>
            </tr>
        </thead>
        <tbody>
            @foreach($purchaseOrder->items as $item)
            <tr>
                <td class="text-center">{{ rtrim(rtrim(number_format($item->quantity, 2, ',', '.'), '0'), ',') }}</td>
                <td>{{ $item->product->product_name ?? 'Produk Dihapus' }}</td>
                <td class="text-end">{{ number_format($item->price_per_unit, 0, ',', '.') }}</td>
                <td class="text-center">
                    @if($item->discounts->isNotEmpty())
                        {!! $item->discounts->pluck('percentage')->map(fn($p) => number_format($p, 2, '.', ''))->implode('&nbsp;&nbsp;') !!}
                    @else
                        -
                    @endif
                </td>
                <td class="text-end">{{ number_format($item->subtotal, 0, ',', '.') }}</td>
            </tr>
            @endforeach
        </tbody>
    </table>

    {{-- Bagian Footer yang Posisinya Tetap di Bawah --}}
    <div id="footer">
        <table>
            <tr>
                <td style="width: 60%; vertical-align: top;">
                    @if($purchaseOrder->notes)
                        <div class="notes-box">
                            <strong>Catatan:</strong><br>
                            {{ $purchaseOrder->notes }}
                        </div>
                    @endif
                    <table style="width: 100%; text-align: center; font-size: 9pt; margin-top: 10px;">
                        <tr>
                            <td style="width: 50%;">Penerima,</td>
                            <td style="width: 50%;">Hormat Kami,</td>
                        </tr>
                        <tr>
                            <td style="padding-top: 50px;">(___________________)</td>
                            <td style="padding-top: 50px;">( {{ $purchaseOrder->supplier->supplier_name }} )</td>
                        </tr>
                    </table>
                </td>
                <td style="width: 40%; vertical-align: top;">
                    <table class="summary-table">
                        <tr><td>Subtotal Barang</td><td class="text-end">Rp {{ number_format($purchaseOrder->subtotal ?? 0, 0, ',', '.') }}</td></tr>
                        <tr><td>Diskon/Fee @if($purchaseOrder->disc_fee_percent > 0)<small>({{ $purchaseOrder->disc_fee_percent }}%)</small>@endif</td><td class="text-end">(-) Rp {{ number_format($purchaseOrder->disc_fee_amount ?? 0, 0, ',', '.') }}</td></tr>
                        <tr><td>Diskon Pembulatan</td><td class="text-end">(-) Rp {{ number_format($purchaseOrder->rounding_discount_amount ?? 0, 0, ',', '.') }}</td></tr>
                        <tr style="border-bottom: 1px solid #ccc;"><td style="padding-bottom: 3px;">Taxable</td><td class="text-end">Rp {{ number_format($purchaseOrder->taxable_amount ?? 0, 0, ',', '.') }}</td></tr>
                        <tr><td style="padding-top: 3px;">DPP @if($purchaseOrder->custom_dpp_factor)<small> (F: {{ rtrim(rtrim(number_format((float)$purchaseOrder->custom_dpp_factor, 4), '0'), '.') }})</small>@endif</td><td class="text-end">Rp {{ number_format($purchaseOrder->dpp ?? 0, 0, ',', '.') }}</td></tr>
                        <tr><td>PPN ({{ $purchaseOrder->tax->rate ?? 0 }}%)</td><td class="text-end">(+) Rp {{ number_format($purchaseOrder->ppn ?? 0, 0, ',', '.') }}</td></tr>
                        <tr><td>Ongkos Kirim</td><td class="text-end">(+) Rp {{ number_format($purchaseOrder->shipping_amount ?? 0, 0, ',', '.') }}</td></tr>
                        <tr class="fw-bold"><td style="border-top: 2px solid #333; padding-top: 3px;">Grand Total</td><td style="border-top: 2px solid #333; padding-top: 3px;" class="text-end">Rp {{ number_format($purchaseOrder->total_amount ?? 0, 0, ',', '.') }}</td></tr>
                    </table>
                </td>
            </tr>
        </table>
    </div>
</body>
</html>
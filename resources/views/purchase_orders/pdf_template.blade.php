<!DOCTYPE html>
<html>
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
    <title>Purchase Order {{ $purchaseOrder->po_number }}</title>
    <style>
        @page { margin: 20px 30px; }
        body { font-family: 'Helvetica', 'Arial', sans-serif; font-size: 8pt; color: #333; line-height: 1.2; }
        table { width: 100%; border-collapse: collapse; }
        th, td { padding: 3px 5px; text-align: left; vertical-align: middle; }
        .bordered thead th { 
            border-top: 1px solid #555;
            border-bottom: 1px solid #555;
            background-color: #f2f2f2;
        }
        .bordered tbody td {
            border-bottom: 1px solid #eee;
        }
        .text-end { text-align: right; }
        .text-center { text-align: center; }
        .fw-bold { font-weight: bold; }
        .summary-table td { padding: 1.5px 3px; }
        .notes { font-size: 7pt; }
    </style>
</head>
<body>
    <div class="container">
        <table style="margin-bottom: 8px;">
            <tr>
                <td style="width: 50%; vertical-align: top;">
                    {{--  <strong>Kepada Yth:</strong><br> --}}
                    {{ $purchaseOrder->supplier->supplier_name }}<br>
                    {!! nl2br(e($purchaseOrder->supplier->address)) !!}
                </td>
                <td style="width: 50%; vertical-align: top; text-align: right;">
                    <h2 style="margin: 0; font-size: 16pt;">PURCHASE ORDER</h2>
                    <table style="font-size: 7.5pt; float: right;">
                        <tr><td style="padding: 0 5px 0 0;" class="text-end">No. PO</td><td style="padding: 0;">: {{ $purchaseOrder->po_number }}</td></tr>
                        <tr><td style="padding: 0 5px 0 0;" class="text-end">Tanggal</td><td style="padding: 0;">: {{ optional($purchaseOrder->order_date)->format('d/m/Y') }}</td></tr>
                         @if($purchaseOrder->due_date)
                        <tr><td style="padding: 0 5px 0 0;" class="text-end">Jatuh Tempo</td><td style="padding: 0;">: {{ optional($purchaseOrder->due_date)->format('d/m/Y') }}</td></tr>
                        @endif
                    </table>
                </td>
            </tr>
        </table>

        <div style="clear: both;"></div>

        <table class="bordered" style="margin-top: 15px;">
            <thead>
                <tr>
                    <th class="text-center" style="width: 5%;">Qty</th>
                    <th>Nama Barang</th>
                    <th class="text-end" style="width: 18%;">Harga</th>
                    <th class="text-center" style="width: 15%;">Disc.</th>
                    <th class="text-end" style="width: 20%;">Jumlah</th>
                </tr>
            </thead>
            <tbody>
                @foreach($purchaseOrder->items as $item)
                <tr>
                    <td class="text-center">{{ rtrim(rtrim(number_format($item->quantity, 2, ',', '.'), '0'), ',') }}</td>
                    <td>{{ $item->product->product_name ?? 'Produk Dihapus' }}</td>
                    <td class="text-end">{{ number_format($item->price_per_unit, 0, ',', '.') }}</td>

                    {{-- ========================================================== --}}
                    {{-- PERUBAHAN: Menampilkan diskon dalam tabel bersarang --}}
                    {{-- ========================================================== --}}
                    <td>
                        @if($item->discounts->isNotEmpty())
                            <table style="width: 100%;">
                                <tr>
                                    @foreach($item->discounts as $discount)
                                        <td style="border: none; text-align: right; padding: 0 2px;">
                                            {{-- Format angka menjadi 2 desimal --}}
                                            {{ number_format($discount->percentage, 2, '.', '') }}
                                        </td>
                                    @endforeach
                                </tr>
                            </table>
                        @else
                            <div style="text-align: center;">-</div>
                        @endif
                    </td>

                    <td class="text-end">{{ number_format($item->subtotal, 0, ',', '.') }}</td>
                </tr>
                @endforeach
                {{-- BARIS KOSONG DIHAPUS UNTUK MENGHEMAT RUANG --}}
            </tbody>
        </table>

        <table style="margin-top: 5px; page-break-inside: avoid;">
             <tr>
                <td style="width: 60%; vertical-align: bottom;">
                    @if($purchaseOrder->notes)
                        <div class="notes" style="border: 1px solid #ccc; padding: 5px; min-height: 30px; margin-bottom: 5px;">
                            <strong>Catatan:</strong><br>
                            {{ $purchaseOrder->notes }}
                        </div>
                    @endif
                    <table style="width: 100%; text-align: center; font-size: 7.5pt;">
                        <tr>
                            <td style="width: 50%;">Penerima,</td>
                            <td style="width: 50%;">Hormat Kami,</td>
                        </tr>
                        <tr>
                            {{-- Mengurangi spasi vertikal untuk tanda tangan --}}
                            <td style="padding-top: 30px;">(___________________)</td>
                            <td style="padding-top: 30px;">(___________________)</td>
                        </tr>
                    </table>
                </td>
                <td style="width: 40%; vertical-align: top;">
                    <table class="summary-table" style="width:100%;">
                        <tr>
                            <td>Subtotal</td>
                            <td style="width: 5%;" class="text-end">Rp</td>
                            <td style="width: 40%;" class="text-end">{{ number_format($purchaseOrder->subtotal ?? 0, 0, ',', '.') }}</td>
                        </tr>
                        <tr>
                            <td>Diskon/Fee</td>
                            <td class="text-end">Rp</td>
                            <td class="text-end">{{ number_format($purchaseOrder->disc_fee_amount ?? 0, 0, ',', '.') }}</td>
                        </tr>
                        <tr>
                            <td>Diskon Pembulatan</td>
                            <td class="text-end">Rp</td>
                            <td class="text-end">{{ number_format($purchaseOrder->rounding_discount_amount ?? 0, 0, ',', '.') }}</td>
                        </tr>
                        <tr style="border-bottom: 1px solid #ccc;">
                            <td style="padding-bottom: 3px;">DPP</td>
                            <td class="text-end">Rp</td>
                            <td class="text-end" style="padding-bottom: 3px;">{{ number_format($purchaseOrder->dpp ?? 0, 0, ',', '.') }}</td>
                        </tr>
                        <tr>
                            <td style="padding-top: 3px;">PPN ({{ $purchaseOrder->tax->rate ?? 0 }}%)</td>
                            <td class="text-end">Rp</td>
                            <td class="text-end" style="padding-top: 3px;">{{ number_format($purchaseOrder->ppn ?? 0, 0, ',', '.') }}</td>
                        </tr>
                        <tr>
                            <td>Ongkos Kirim</td>
                            <td class="text-end">Rp</td>
                            <td class="text-end">{{ number_format($purchaseOrder->shipping_amount ?? 0, 0, ',', '.') }}</td>
                        </tr>
                        <tr class="fw-bold">
                            <td style="border-top: 2px solid #333; padding-top: 3px;">Total Tagihan</td>
                            <td style="border-top: 2px solid #333; padding-top: 3px;" class="text-end">Rp</td>
                            <td style="border-top: 2px solid #333; padding-top: 3px;" class="text-end">{{ number_format($purchaseOrder->total_amount ?? 0, 0, ',', '.') }}</td>
                        </tr>
                    </table>
                </td>
            </tr>
        </table>
    </div>
</body>
</html>
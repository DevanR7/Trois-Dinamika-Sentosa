<!DOCTYPE html>
<html>
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
    <title>Invoice {{ $invoice->invoice_number }}</title>
    <style>
        @page {
            margin: 120px 25px 180px 25px; /* top, right, bottom, left */
        }

        body {
            font-family: 'Helvetica', 'Arial', sans-serif;
            font-size: 9pt;
            color: #333;
        }

        table { width: 100%; border-collapse: collapse; }
        th, td { padding: 4px 6px; vertical-align: top; }

        .text-end { text-align: right; }
        .text-center { text-align: center; }
        .fw-bold { font-weight: bold; }

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

        .summary-table td { padding: 1.5px 3px; }

        .notes-box {
            font-size: 8pt;
            padding: 5px;
            border: 1px solid #ccc;
            margin-bottom: 10px;
        }

        /* HEADER & FOOTER TEMPLATE (will repeat on each page) */
        header {
            position: fixed;
            top: -95px; /* negative sesuai margin atas */
            left: 0;
            right: 0;
            height: 100px;
        }

        footer {
            position: fixed;
            bottom: -160px; /* negative sesuai margin bawah */
            left: 0;
            right: 0;
            height: 150px;
        }

        main {
            margin-top: 10px;
        }
    </style>
</head>
<body>

    {{-- ================= HEADER (muncul di setiap halaman) ================= --}}
    <header>
        <table style="width: 100%; border-collapse: collapse; margin-bottom: 10px;">
            <tr>
                {{-- ================= KOLOM KIRI (KEPADA YTH) ================= --}}
                <td style="width: 50%; vertical-align: top; padding-right: 10px;">
                    <strong>Kepada Yth:</strong><br>
                    {{ $invoice->client->client_name }}<br>
                    {!! nl2br(e($invoice->client->address)) !!}
                </td>

                {{-- ================= KOLOM KANAN (DETAIL INVOICE) ================= --}}
                <td style="width: 50%; vertical-align: top; text-align: right; padding-left: 10px; padding-top: 2px;">
                    <div style="display: inline-block; text-align: left;">
                        <div style="font-size: 18pt; font-weight: bold; margin-bottom: 4px; text-align: right;">INVOICE</div>
                        <table style="font-size: 9pt;">
                            <tr>
                                <td style="text-align: right; padding: 1px 5px 1px 0;">No. Invoice</td>
                                <td style="text-align: left;">: {{ $invoice->invoice_number }}</td>
                            </tr>
                            <tr>
                                <td style="text-align: right; padding: 1px 5px 1px 0;">Tanggal</td>
                                <td style="text-align: left;">: {{ optional($invoice->order_date)->format('d/m/Y') }}</td>
                            </tr>
                            @if($invoice->due_date)
                            <tr>
                                <td style="text-align: right; padding: 1px 5px 1px 0;">Jatuh Tempo</td>
                                <td style="text-align: left;">: {{ optional($invoice->due_date)->format('d/m/Y') }}</td>
                            </tr>
                            @endif
                        </table>
                    </div>
                </td>
            </tr>
        </table>
    </header>

    {{-- ================= KONTEN BARANG (bisa panjang, auto-break) ================= --}}
    <main>
        <table class="item-table">
            <thead>
                <tr>
                    <th class="text-center" style="width: 8%;">Qty</th>
                    <th>Nama Barang</th>
                    <th class="text-end" style="width: 25%;">Harga Satuan</th>
                    <th class="text-end" style="width: 25%;">Jumlah</th>
                </tr>
            </thead>
            <tbody>
                @foreach($invoice->items as $item)
                <tr>
                    <td class="text-center">
                        {{ rtrim(rtrim(number_format($item->quantity, 2, ',', '.'), '0'), ',') }} {{ $item->product->unit->name ?? '' }}
                    </td>
                    <td>{{ $item->product->product_name ?? 'Produk Dihapus' }}</td>
                    <td class="text-end">Rp {{ number_format($item->price_per_unit, 0, ',', '.') }}</td>
                    <td class="text-end">Rp {{ number_format($item->subtotal, 0, ',', '.') }}</td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </main>

    {{-- ================= FOOTER (muncul di setiap halaman, selalu di bawah) ================= --}}
    <footer>
        <table style="width: 100%;">
            <tr>
                <td style="width: 60%; vertical-align: top;">
                    @if($invoice->notes)
                        <div class="notes-box">
                            <strong>Catatan:</strong><br>
                            {{ $invoice->notes }}
                        </div>
                    @endif
                    <table style="width: 100%; text-align: center; font-size: 9pt; margin-top: 10px;">
                        <tr>
                            <td style="width: 50%;">Penerima,</td>
                            <td style="width: 50%;">Hormat Kami,</td>
                        </tr>
                        <tr>
                            <td style="padding-top: 50px;">(___________________)</td>
                            <td style="padding-top: 50px;">( {{ setting('company_owner', 'Nama Pemilik') }} )</td>
                        </tr>
                    </table>
                </td>
                <td style="width: 40%; vertical-align: top;">
                    <table class="summary-table">
                        <tr><td>Subtotal Produk</td><td class="text-end">Rp {{ number_format($invoice->subtotal, 0, ',', '.') }}</td></tr>
                        @if($invoice->discount_amount > 0)
                            <tr><td>Diskon ({{ $invoice->discount_percentage }}%)</td><td class="text-end">(-) Rp {{ number_format($invoice->discount_amount, 0, ',', '.') }}</td></tr>
                        @endif
                        <tr style="border-bottom: 1px solid #ccc;"><td>Subtotal Setelah Diskon</td><td class="text-end">Rp {{ number_format($invoice->subtotal - $invoice->discount_amount, 0, ',', '.') }}</td></tr>
                        @foreach($invoice->taxes as $tax)
                            <tr><td>{{ $tax->pivot->name }} ({{ $tax->pivot->rate }}%)</td><td class="text-end">(+) Rp {{ number_format($tax->pivot->amount, 0, ',', '.') }}</td></tr>
                        @endforeach
                        <tr class="fw-bold"><td style="border-top: 2px solid #333; padding-top: 3px;">Total Tagihan</td><td style="border-top: 2px solid #333; padding-top: 3px;" class="text-end">Rp {{ number_format($invoice->total_amount, 0, ',', '.') }}</td></tr>
                    </table>
                </td>
            </tr>
        </table>
    </footer>

</body>
</html>

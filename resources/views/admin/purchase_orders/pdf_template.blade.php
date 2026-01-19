<!DOCTYPE html>
<html lang="id">
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
    <title>Purchase Order {{ $purchaseOrder->po_number }}</title>
    <style>
        @page { margin: 25px 35px; } /* Margin A4 yang pas */
        body { 
            font-family: 'Helvetica', 'Arial', sans-serif; 
            font-size: 9pt; 
            color: #000; 
            line-height: 1.3;
        }

        /* Utility */
        .w-100 { width: 100%; }
        .text-right { text-align: right; }
        .text-center { text-align: center; }
        .text-bold { font-weight: bold; }
        .valign-top { vertical-align: top; }
        .uppercase { text-transform: uppercase; }
        
        /* Header Section */
        .header-table { margin-bottom: 15px; border-bottom: 2px solid #000; padding-bottom: 10px; }
        .company-title { font-size: 14pt; font-weight: bold; text-transform: uppercase; margin-bottom: 2px; }
        .company-sub { font-size: 8pt; color: #333; }
        
        .po-title { 
            font-size: 16pt; 
            font-weight: bold; 
            text-align: right;
            margin-bottom: 5px;
        }

        /* Info Boxes */
        .info-table { margin-bottom: 15px; }
        .info-cell { padding: 0 5px; }
        .info-box-title { font-weight: bold; border-bottom: 1px solid #000; margin-bottom: 5px; font-size: 9pt; }

        /* Items Table (Clean & Professional) */
        .items-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 15px;
        }
        .items-table th {
            border-top: 1px solid #000;
            border-bottom: 1px solid #000;
            padding: 5px;
            text-align: left;
            font-weight: bold;
            font-size: 9pt;
            text-transform: uppercase;
        }
        .items-table td {
            padding: 4px 5px;
            border-bottom: 1px dotted #ccc;
            font-size: 9pt;
        }
        /* Hapus border baris terakhir */
        .items-table tbody tr:last-child td { border-bottom: 1px solid #000; }

        /* Summary Table */
        .summary-table { width: 100%; border-collapse: collapse; font-size: 9pt; }
        .summary-table td { padding: 2px 0; }
        .summary-label { text-align: right; padding-right: 15px; }
        .summary-value { text-align: right; width: 110px; }
        
        .grand-total-row td {
            border-top: 1px solid #000;
            border-bottom: 2px solid #000; /* Double border effect */
            padding: 5px 0;
            font-weight: bold;
            font-size: 10pt;
        }

        /* Footer & Signature */
        .footer-wrapper { margin-top: 20px; page-break-inside: avoid; }
        .sign-area { height: 60px; }
        .sign-name { font-weight: bold; text-decoration: underline; text-transform: uppercase; }
        
        .bank-info { font-size: 8pt; font-style: italic; margin-top: 5px; }
        .notes-box { 
            border: 1px solid #ccc; 
            padding: 8px; 
            font-size: 8pt; 
            min-height: 50px;
        }
    </style>
</head>
<body>

    {{-- HEADER --}}
    <table class="w-100 header-table">
        <tr>
            <td width="60%" class="valign-top">
                <div class="company-title">{{ $settings['company_name'] ?? 'PERUSAHAAN ANDA' }}</div>
                <div class="company-sub">
                    {{ $settings['company_address'] ?? 'Alamat Perusahaan' }}<br>
                    Telp: {{ $settings['company_phone'] ?? '-' }} | NPWP: {{ $settings['company_npwp'] ?? '-' }}
                </div>
            </td>
            <td width="40%" class="valign-top text-right">
                <div class="po-title">PURCHASE ORDER</div>
                <table width="100%" style="font-size: 9pt;">
                    <tr>
                        <td class="text-right">No. PO :</td>
                        <td class="text-right text-bold">{{ $purchaseOrder->po_number }}</td>
                    </tr>
                    <tr>
                        <td class="text-right">Tanggal :</td>
                        <td class="text-right">{{ $purchaseOrder->order_date->format('d/m/Y') }}</td>
                    </tr>
                    <tr>
                        <td class="text-right">Jatuh Tempo :</td>
                        <td class="text-right">{{ $purchaseOrder->due_date ? $purchaseOrder->due_date->format('d/m/Y') : '-' }}</td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>

    {{-- INFO SUPPLIER --}}
    <table class="w-100 info-table">
        <tr>
            <td width="50%" class="valign-top info-cell">
                <div class="info-box-title">KEPADA (SUPPLIER)</div>
                <div class="text-bold">{{ $purchaseOrder->supplier->supplier_name }}</div>
                <div>{!! nl2br(e($purchaseOrder->supplier->address)) !!}</div>
                <div>Telp: {{ $purchaseOrder->supplier->phone_number ?? '-' }}</div>
                <div>PIC: {{ $purchaseOrder->supplier->person_in_charge ?? '-' }}</div>
            </td>
            <td width="50%" class="valign-top info-cell" style="padding-left: 20px;">
                <div class="info-box-title">DETAIL PENGIRIMAN</div>
                <div>Referensi Supplier: <strong>{{ $purchaseOrder->supplier_invoice_number ?? '-' }}</strong></div>
                <div>Pemesan: {{ $purchaseOrder->requester->full_name ?? 'Admin' }}</div>
                
                @if($purchaseOrder->supplier->bank_name)
                <div class="bank-info">
                    Bank Supplier: {{ $purchaseOrder->supplier->bank_name }} - {{ $purchaseOrder->supplier->account_number }}
                </div>
                @endif
            </td>
        </tr>
    </table>

    {{-- ITEMS TABLE (Clean Style) --}}
    <table class="items-table">
        <thead>
            <tr>
                <th width="5%" class="text-center">No</th>
                <th width="40%">Deskripsi Barang</th>
                <th width="10%" class="text-center">Qty</th>
                <th width="15%" class="text-right">Harga</th>
                <th width="10%" class="text-center">Disc</th>
                <th width="20%" class="text-right">Total</th>
            </tr>
        </thead>
        <tbody>
            @foreach($purchaseOrder->items as $index => $item)
            <tr>
                <td class="text-center">{{ $index + 1 }}</td>
                <td>
                    <div class="text-bold">{{ $item->product->product_name }}</div>
                    <div style="font-size: 8pt;">{{ $item->product->product_code }}</div>
                </td>
                <td class="text-center">
                    {{ (float)$item->quantity }} {{ $item->product->unit->name ?? '' }}
                </td>
                <td class="text-right">{{ number_format($item->price_per_unit, 0, ',', '.') }}</td>
                <td class="text-center">
                    @if($item->discounts->count() > 0)
                        {{ $item->discounts->pluck('percentage')->implode('+') }}%
                    @else
                        -
                    @endif
                </td>
                <td class="text-right">{{ number_format($item->subtotal, 0, ',', '.') }}</td>
            </tr>
            @endforeach
            
            {{-- Isi baris kosong agar layout tetap stabil jika item sedikit --}}
            @for($i = $purchaseOrder->items->count(); $i < 5; $i++)
            <tr>
                <td style="color: #fff;">.</td><td></td><td></td><td></td><td></td><td></td>
            </tr>
            @endfor
        </tbody>
    </table>

    {{-- FOOTER SUMMARY & SIGNATURE --}}
    <table class="w-100 footer-wrapper">
        <tr>
            {{-- KIRI: CATATAN --}}
            <td width="55%" class="valign-top" style="padding-right: 20px;">
                <div class="info-box-title">CATATAN</div>
                <div class="notes-box">
                    {!! nl2br(e($purchaseOrder->notes ?? '-')) !!}
                </div>
            </td>

            {{-- KANAN: HITUNGAN --}}
            <td width="45%" class="valign-top">
                <table class="summary-table">
                    <tr>
                        <td class="summary-label">Subtotal</td>
                        <td class="summary-value">{{ number_format($purchaseOrder->subtotal, 0, ',', '.') }}</td>
                    </tr>
                    
                    <tr>
                        <td class="summary-label">Diskon Akhir @if($purchaseOrder->disc_fee_percent > 0)({{ (float)$purchaseOrder->disc_fee_percent }}%)@endif</td>
                        <td class="summary-value">- {{ number_format($purchaseOrder->disc_fee_amount ?? 0, 0, ',', '.') }}</td>
                    </tr>
                    
                    <tr>
                        <td class="summary-label">Pembulatan</td>
                        <td class="summary-value">- {{ number_format($purchaseOrder->rounding_discount_amount ?? 0, 0, ',', '.') }}</td>
                    </tr>

                    <tr>
                        <td class="summary-label">
                            DPP @if($purchaseOrder->use_custom_dpp_factor)<span style="font-size:7pt">(Faktor Aktif)</span>@endif
                        </td>
                        <td class="summary-value">{{ number_format($purchaseOrder->dpp ?? 0, 0, ',', '.') }}</td>
                    </tr>

                    <tr>
                        <td class="summary-label">PPN @if($purchaseOrder->tax_id)({{ (float)$purchaseOrder->tax->rate }}%)@endif</td>
                        <td class="summary-value">+ {{ number_format($purchaseOrder->ppn ?? 0, 0, ',', '.') }}</td>
                    </tr>

                    <tr>
                        <td class="summary-label">Biaya Kirim</td>
                        <td class="summary-value">+ {{ number_format($purchaseOrder->shipping_amount ?? 0, 0, ',', '.') }}</td>
                    </tr>

                    <tr class="grand-total-row">
                        <td class="summary-label text-bold">GRAND TOTAL</td>
                        <td class="summary-value">Rp {{ number_format($purchaseOrder->grand_total, 0, ',', '.') }}</td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>

    {{-- TANDA TANGAN --}}
    <table class="w-100" style="margin-top: 30px;">
        <tr>
            <td width="33%" class="text-center valign-top">
                <div class="text-bold">Pemesan,</div>
                <div class="sign-area"></div>
                <div class="sign-name">( {{ $settings['company_name'] ?? 'PERUSAHAAN' }} )</div>
            </td>
            <td width="33%"></td>
            <td width="33%" class="text-center valign-top">
                <div class="text-bold">Hormat Kami,</div>
                <div class="sign-area"></div>
                <div class="sign-name">( {{ $purchaseOrder->supplier->supplier_name }} )</div>
            </td>
        </tr>
    </table>

</body>
</html>
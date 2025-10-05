<table>
    <!-- Header Perusahaan -->
    <tr>
        <td colspan="6" style="text-align: center; font-weight: bold; font-size: 12pt;">PT. LAXTON STORE INDONESIA</td>
    </tr>
    <tr>
        <td colspan="6" style="text-align: center;">UL. MASELANG FUJIWORZYO KHIO (ROCH AI)</td>
    </tr>
    <tr>
        <td colspan="6" style="text-align: center;">FUNDUIDARI RT.1, TEHFURZO TEMPURAN, KAB. HACELANG</td>
    </tr>
    <tr>
        <td colspan="6" style="text-align: center;">NPWP: 40.215.436.3-524.000</td>
    </tr>
    
    <!-- Spasi -->
    <tr><td colspan="6" style="height: 10px;"></td></tr>
    
    <!-- Info Customer dan Tanggal -->
    <tr>
        <td colspan="3" style="font-weight: bold;">Kepada Yth:</td>
        <td colspan="3" style="text-align: right;">{{ \Carbon\Carbon::parse($purchaseOrder->order_date)->format('d-m-Y') ?? date('d-m-Y') }}</td>
    </tr>
    <tr>
        <td colspan="3">{{ $purchaseOrder->supplier->supplier_name }}</td>
        <td colspan="3" style="text-align: right;">No.Faktur: {{ $purchaseOrder->supplier_invoice_number ?? $purchaseOrder->po_number }}</td>
    </tr>
    <tr>
        <td colspan="3">{{ $purchaseOrder->supplier->address ?? 'Alamat tidak tersedia' }}</td>
        <td colspan="3" style="text-align: right;">
            @if($purchaseOrder->due_date)
                Jatuh Tempo: {{ \Carbon\Carbon::parse($purchaseOrder->due_date)->format('d-m-Y') }}
            @endif
        </td>
    </tr>
    <tr>
        <td colspan="3">{{ $purchaseOrder->supplier->email ?? '' }}</td>
        <td colspan="3"></td>
    </tr>
    
    <!-- Spasi -->
    <tr><td colspan="6" style="height: 10px;"></td></tr>
    
    <!-- Header Tabel -->
    <tr style="background-color: #f0f0f0; font-weight: bold; text-align: center;">
        <td>✓</td>
        <td>Qty</td>
        <td>Nama Barang</td>
        <td>Harga</td>
        <td>Disc%</td>
        <td>Jumlah</td>
    </tr>
    
    <!-- Data Items -->
    @foreach($purchaseOrder->items as $item)
    <tr>
        <td style="text-align: center;">✓</td>
        <td style="text-align: center;">{{ $item->quantity }} PCS</td>
        <td>{{ $item->product->product_name ?? 'Produk Dihapus' }}</td>
        <td style="text-align: right;">{{ number_format($item->price_per_unit, 0, ',', '.') }}</td>
        <td style="text-align: center;">
            @if(isset($item->discounts) && $item->discounts->isNotEmpty())
                {{ $item->discounts->pluck('percentage')->join('%, ') }}%
            @else
                0
            @endif
        </td>
        <td style="text-align: right;">{{ number_format($item->subtotal, 0, ',', '.') }}</td>
    </tr>
    @endforeach
    
    <!-- Tambahkan baris kosong jika item kurang dari minimal -->
    @for($i = count($purchaseOrder->items); $i < 4; $i++)
    <tr>
        <td>&nbsp;</td>
        <td>&nbsp;</td>
        <td>&nbsp;</td>
        <td>&nbsp;</td>
        <td>&nbsp;</td>
        <td>&nbsp;</td>
    </tr>
    @endfor
    
    <!-- Spasi -->
    <tr><td colspan="6" style="height: 15px;"></td></tr>
    
    <!-- Ringkasan Pembayaran -->
    <tr>
        <td colspan="5">Subtotal</td>
        <td style="text-align: right;">{{ number_format($purchaseOrder->subtotal ?? 0, 0, ',', '.') }}</td>
    </tr>
    <tr>
        <td colspan="5">
            Disc/Fee 
            @if($purchaseOrder->disc_fee_percent > 0)
                ({{ $purchaseOrder->disc_fee_percent }}%)
            @endif
        </td>
        <td style="text-align: right;">- {{ number_format($purchaseOrder->disc_fee_amount ?? 0, 0, ',', '.') }}</td>
    </tr>
    <tr>
        <td colspan="5">Disc Pembulatan</td>
        <td style="text-align: right;">- {{ number_format($purchaseOrder->rounding_discount_amount ?? 0, 0, ',', '.') }}</td>
    </tr>
    
    <tr><td colspan="6" style="height: 5px;"></td></tr>
    
    <tr>
        <td colspan="5">
            DPP (11/12xHrg Jual)
            @if($purchaseOrder->custom_dpp_factor)
                <small>Faktor: {{ $purchaseOrder->custom_dpp_factor }}</small>
            @endif
        </td>
        <td style="text-align: right;">{{ number_format($purchaseOrder->dpp ?? 0, 0, ',', '.') }}</td>
    </tr>
    <tr>
        <td colspan="5">PPN {{ $purchaseOrder->tax->rate ?? 11 }}%</td>
        <td style="text-align: right;">{{ number_format($purchaseOrder->ppn ?? 0, 0, ',', '.') }}</td>
    </tr>
    <tr>
        <td colspan="5">Ongkos Kirim</td>
        <td style="text-align: right;">{{ number_format($purchaseOrder->shipping_amount ?? 0, 0, ',', '.') }}</td>
    </tr>
    
    <tr><td colspan="6" style="height: 5px;"></td></tr>
    
    <tr style="font-weight: bold;">
        <td colspan="5">Jumlah</td>
        <td style="text-align: right;">{{ number_format($purchaseOrder->total_amount ?? 0, 0, ',', '.') }}</td>
    </tr>
    
    <!-- Spasi -->
    <tr><td colspan="6" style="height: 20px;"></td></tr>
    
    <!-- Catatan -->
    <tr>
        <td colspan="6" style="text-align: center; font-size: 9pt;">
            Komplain max 1 minggu stl brg diterima
        </td>
    </tr>
    <tr>
        <td colspan="6" style="text-align: center; font-size: 9pt;">
            Hrg min jual OLshop max disc 83%, klaim baterai max 1 minggu stl brg diterima
        </td>
    </tr>
    <tr>
        <td colspan="6" style="text-align: center; font-size: 9pt;">
            Invoice asli berlaku sbg tanda bukti pembayaran yg sah
        </td>
    </tr>
    
    <!-- Spasi -->
    <tr><td colspan="6" style="height: 30px;"></td></tr>
    
    <!-- Tanda Tangan -->
    <tr>
        <td colspan="3" style="text-align: center; vertical-align: top;">
            Penerima,<br><br><br><br>
            <strong>Kanemah</strong>
        </td>
        <td colspan="3" style="text-align: center; vertical-align: top;">
            Hormat Kami,<br><br><br><br>
            <strong>PT. LAXTON STORE INDONESIA</strong>
        </td>
    </tr>
</table>
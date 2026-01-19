<div class="table-container">
    <table class="table-modern">
        <thead>
            <tr>
                <th>No Retur</th>
                <th>Tanggal</th>
                <th>Asal Invoice</th>
                <th>Metode</th>
                <th class="text-right">Total Nilai</th>
                <th>Aksi</th>
            </tr>
        </thead>
        <tbody>
            @forelse($returns as $ret)
            <tr>
                <td class="font-mono font-bold">{{ $ret->return_number }}</td>
                <td>{{ $ret->return_date->format('d/m/Y') }}</td>
                <td class="font-mono text-xs">
                    @if($ret->salesInvoice)
                        <a href="{{ route('admin.invoices.show', $ret->salesInvoice->invoice_id) }}" class="text-indigo-600 hover:underline">
                            {{ $ret->salesInvoice->invoice_number }}
                        </a>
                    @else - @endif
                </td>
                <td>
                    @if($ret->return_handling_type == 'deduct_invoice') 
                        <span class="badge badge-info">Potong Tagihan</span>
                    @else 
                        <span class="badge badge-success">Simpan Kredit</span> 
                    @endif
                </td>
                <td class="text-right font-bold text-rose-600">
                    Rp {{ number_format($ret->total_amount, 0, ',', '.') }}
                </td>
                <td>
                    <a href="{{ route('admin.sales-returns.show', $ret->return_id) }}" class="btn-action btn-action-view"><i class="material-icons">visibility</i></a>
                </td>
            </tr>
            @empty
            <tr><td colspan="6" class="text-center py-8 text-slate-400">Belum ada data retur penjualan.</td></tr>
            @endforelse
        </tbody>
    </table>
</div>
<div class="mt-4">
    {{ $returns->appends(['tab' => 'returns'])->links('vendor.pagination.admin') }}
</div>
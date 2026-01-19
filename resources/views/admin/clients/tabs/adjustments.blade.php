<div class="table-container">
    <table class="table-modern">
        <thead>
            <tr>
                <th>Tanggal</th>
                <th>No Invoice</th>
                <th>Tipe</th>
                <th>Alasan / Keterangan</th>
                <th class="text-right">Nominal</th>
            </tr>
        </thead>
        <tbody>
            @forelse($adjustments as $adj)
            <tr>
                <td>{{ $adj->adjustment_date->format('d/m/Y') }}</td>
                <td class="font-mono text-xs font-bold">
                    {{ $adj->salesInvoice->invoice_number ?? '-' }}
                </td>
                <td>
                    @if($adj->type == 'credit_note') 
                        <span class="badge badge-success">Credit Note (Kurang Bayar)</span>
                    @else 
                        <span class="badge badge-danger">Debit Note (Tambah Bayar)</span> 
                    @endif
                </td>
                <td>
                    <div class="text-sm text-slate-600 max-w-md truncate" title="{{ $adj->reason }}">
                        {{ $adj->reason }}
                    </div>
                </td>
                <td class="text-right font-bold font-mono">
                    Rp {{ number_format($adj->amount, 0, ',', '.') }}
                </td>
            </tr>
            @empty
            <tr><td colspan="5" class="text-center py-8 text-slate-400">Belum ada penyesuaian invoice.</td></tr>
            @endforelse
        </tbody>
    </table>
</div>
<div class="mt-4">
    {{ $adjustments->appends(['tab' => 'adjustments'])->links('vendor.pagination.admin') }}
</div>
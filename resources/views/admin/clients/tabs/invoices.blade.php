<div class="table-container">
    <table class="table-modern">
        <thead>
            <tr>
                <th>No Invoice</th>
                <th>Tanggal</th>
                <th>Jatuh Tempo</th>
                <th class="text-right">Total Tagihan</th>
                <th class="text-right">Sisa Tagihan</th>
                <th>Status</th>
                <th>Aksi</th>
            </tr>
        </thead>
        <tbody>
            @forelse($invoices as $inv)
            <tr>
                <td class="font-mono font-bold text-indigo-600">
                    <a href="{{ route('admin.invoices.show', $inv->invoice_id) }}" class="hover:underline">
                        {{ $inv->invoice_number }}
                    </a>
                </td>
                <td>{{ $inv->order_date->format('d/m/Y') }}</td>
                <td>
                    <span class="{{ $inv->due_date < now() && $inv->status != 'paid' ? 'text-rose-600 font-bold' : '' }}">
                        {{ $inv->due_date->format('d/m/Y') }}
                    </span>
                </td>
                <td class="text-right">Rp {{ number_format($inv->total_amount, 0, ',', '.') }}</td>
                <td class="text-right font-bold text-slate-700 dark:text-white">
                    Rp {{ number_format($inv->remaining_balance, 0, ',', '.') }}
                </td>
                <td>
                    @if($inv->status == 'paid') <span class="badge badge-success">Lunas</span>
                    @elseif($inv->status == 'unpaid') <span class="badge badge-danger">Belum Lunas</span>
                    @elseif($inv->status == 'partially_paid') <span class="badge badge-warning">Sebagian</span>
                    @else <span class="badge badge-secondary">{{ ucfirst($inv->status) }}</span> @endif
                </td>
                <td>
                    <a href="{{ route('admin.invoices.show', $inv->invoice_id) }}" class="btn-action btn-action-view" title="Lihat Invoice"><i class="material-icons">visibility</i></a>
                </td>
            </tr>
            @empty
            <tr><td colspan="7" class="text-center py-8 text-slate-400">Belum ada invoice untuk klien ini.</td></tr>
            @endforelse
        </tbody>
    </table>
</div>
<div class="mt-4">
    {{ $invoices->appends(['tab' => 'invoices'])->links('vendor.pagination.admin') }}
</div>
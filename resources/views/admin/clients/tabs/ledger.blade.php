<div class="table-container">
    <table class="table-modern">
        <thead>
            <tr>
                <th>Tanggal</th>
                <th>Keterangan</th>
                <th>Referensi</th>
                <th class="text-right text-emerald-600">Masuk (Kredit)</th>
                <th class="text-right text-rose-600">Keluar (Debit)</th>
                <th>Status</th>
            </tr>
        </thead>
        <tbody>
            @forelse($ledgers as $ledger)
            <tr>
                <td class="whitespace-nowrap">
                    <div class="text-sm font-bold text-slate-700 dark:text-slate-200">
                        {{ $ledger->transaction_date->format('d/m/Y') }}
                    </div>
                    <div class="text-xs text-slate-400">
                        {{ $ledger->created_at->format('H:i') }}
                    </div>
                </td>
                <td>
                    <div class="text-sm text-slate-600 dark:text-slate-300 max-w-[250px] truncate" title="{{ $ledger->description }}">
                        {{ $ledger->description }}
                    </div>
                </td>
                <td>
                    @if($ledger->reference_type && $ledger->reference_id)
                        @php
                            $refType = class_basename($ledger->reference_type);
                            $badgeColor = 'bg-slate-100 text-slate-600';
                            $refLabel = $refType . ' #' . $ledger->reference_id;
                            
                            // Logika label yang lebih cantik
                            if ($refType === 'SalesInvoice') {
                                $badgeColor = 'bg-indigo-50 text-indigo-600 border-indigo-200';
                                // Jika relasi dimuat, bisa tampilkan nomor invoice asli
                                // $refLabel = $ledger->salesInvoice->invoice_number ?? $refLabel;
                            }
                            if ($refType === 'Payment') $badgeColor = 'bg-emerald-50 text-emerald-600 border-emerald-200';
                            if ($refType === 'SalesReturn') $badgeColor = 'bg-rose-50 text-rose-600 border-rose-200';
                        @endphp
                        <span class="badge {{ $badgeColor }} font-mono text-[10px] border">
                            {{ $refLabel }}
                        </span>
                    @else
                        <span class="text-slate-300 text-xs">-</span>
                    @endif
                </td>
                <td class="text-right font-mono font-bold text-emerald-600 bg-emerald-50/30 dark:bg-emerald-900/10">
                    @if($ledger->type === 'credit')
                        +{{ number_format($ledger->amount, 0, ',', '.') }}
                    @else
                        <span class="text-slate-300 text-xs font-normal">-</span>
                    @endif
                </td>
                <td class="text-right font-mono font-bold text-rose-600 bg-rose-50/30 dark:bg-rose-900/10">
                    @if($ledger->type === 'debit')
                        -{{ number_format(abs($ledger->amount), 0, ',', '.') }}
                    @else
                        <span class="text-slate-300 text-xs font-normal">-</span>
                    @endif
                </td>
                <td>
                    @if($ledger->status === 'available')
                        <span class="badge badge-success">Selesai</span>
                    @else
                        <span class="badge badge-warning">Pending</span>
                    @endif
                </td>
            </tr>
            @empty
            <tr><td colspan="6" class="text-center py-8 text-slate-400">Belum ada riwayat transaksi buku besar.</td></tr>
            @endforelse
        </tbody>
    </table>
</div>
{{-- Pagination Link Manual (Perlu handling klik via JS jika ingin full SPA, tapi standar link juga ok untuk reload) --}}
<div class="mt-4">
    {{ $ledgers->appends(['tab' => 'ledger'])->links('vendor.pagination.admin') }}
</div>
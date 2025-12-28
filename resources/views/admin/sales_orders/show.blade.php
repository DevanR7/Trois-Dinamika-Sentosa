@extends('admin.layouts.app')

@section('title', 'Detail Pesanan')

@section('content')

    <div class="max-w-5xl mx-auto">
        
        {{-- Navigation & Actions --}}
        <div class="flex items-center justify-between mb-6">
            <a href="{{ route('admin.sales-orders.index') }}" class="btn btn-secondary">
                <i class="material-icons text-sm mr-1">arrow_back</i> Kembali
            </a>
            
            <div class="flex gap-2">
                @if($order->status == 'pending')
                    <a href="{{ route('admin.sales-orders.edit', $order->order_id) }}" class="btn btn-secondary">
                        <i class="material-icons text-sm mr-1">edit</i> Edit
                    </a>
                    
                    {{-- Generate Invoice Button --}}
                    <a href="{{ route('admin.invoices.createFromOrder', $order->order_id) }}" class="btn btn-primary">
                        <i class="material-icons text-sm mr-1">receipt_long</i> Buat Invoice
                    </a>
                @elseif($order->status == 'invoiced')
                    <a href="{{ route('admin.invoices.show', $order->invoice_id) }}" class="btn btn-primary">
                        <i class="material-icons text-sm mr-1">description</i> Lihat Invoice
                    </a>
                @endif
            </div>
        </div>

        {{-- INFO HEADER --}}
        <div class="card mb-6 border-l-4 {{ $order->status == 'invoiced' ? 'border-emerald-500' : 'border-indigo-500' }}">
            <div class="card-body grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
                
                {{-- Order No --}}
                <div>
                    <label class="text-[10px] font-bold text-slate-400 uppercase tracking-wider">No. Pesanan</label>
                    <div class="text-xl font-bold text-slate-800 dark:text-white mt-1">{{ $order->order_number }}</div>
                    <div class="mt-2">
                        @php
                            $statusClass = match($order->status) {
                                'pending' => 'badge-warning',
                                'approved' => 'badge-primary',
                                'invoiced' => 'badge-success',
                                'rejected' => 'badge-danger',
                                'pending_review' => 'badge-info',
                                default => 'badge-secondary'
                            };
                        @endphp
                        <span class="badge {{ $statusClass }}">{{ ucfirst($order->status) }}</span>
                    </div>
                </div>

                {{-- Client --}}
                <div>
                    <label class="text-[10px] font-bold text-slate-400 uppercase tracking-wider">Pelanggan</label>
                    <div class="font-bold text-slate-700 dark:text-slate-200 mt-1">
                        {{ $order->client->client_name ?? 'N/A' }}
                    </div>
                    <div class="text-sm text-slate-500">{{ $order->client->phone_number ?? '-' }}</div>
                </div>

                {{-- Date & Sales --}}
                <div>
                    <label class="text-[10px] font-bold text-slate-400 uppercase tracking-wider">Detail</label>
                    <div class="text-sm mt-1">
                        <span class="block text-slate-600 dark:text-slate-300">Tgl: {{ $order->order_date->format('d M Y') }}</span>
                        <span class="block text-slate-500 text-xs mt-1">Sales: {{ $order->sales->full_name ?? '-' }}</span>
                    </div>
                </div>

                {{-- Amount --}}
                <div class="text-right">
                    <label class="text-[10px] font-bold text-slate-400 uppercase tracking-wider">Total Nilai</label>
                    <div class="text-2xl font-extrabold text-indigo-600 dark:text-indigo-400 mt-1">
                        Rp {{ number_format($order->total_amount, 0, ',', '.') }}
                    </div>
                </div>

            </div>
        </div>

        {{-- ITEMS TABLE --}}
        <div class="card mb-6">
            <div class="card-header bg-slate-50 dark:bg-slate-800">
                <h3 class="card-header-title">Rincian Barang</h3>
            </div>
            <div class="table-container">
                <table class="table-modern">
                    <thead>
                        <tr>
                            <th>Produk</th>
                            <th class="text-right">Harga</th>
                            <th class="text-center">Qty</th>
                            <th class="text-right">Subtotal</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($order->items as $item)
                            <tr>
                                <td>
                                    <div class="font-bold text-slate-700 dark:text-slate-200">{{ $item->product->product_name ?? 'Item Dihapus' }}</div>
                                    <div class="text-xs text-slate-500 font-mono">{{ $item->product->product_code ?? '-' }}</div>
                                </td>
                                <td class="text-right font-mono text-slate-600 dark:text-slate-300">
                                    Rp {{ number_format($item->price_per_unit, 0, ',', '.') }}
                                </td>
                                <td class="text-center font-bold">
                                    {{ number_format($item->quantity, 0, ',', '.') }}
                                </td>
                                <td class="text-right font-bold text-slate-800 dark:text-white">
                                    Rp {{ number_format($item->subtotal, 0, ',', '.') }}
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                    <tfoot class="bg-slate-50 dark:bg-slate-800 border-t border-slate-200 dark:border-slate-700">
                        <tr>
                            <td colspan="3" class="px-6 py-4 text-right font-bold text-slate-500 uppercase text-xs">Total Akhir</td>
                            <td class="px-6 py-4 text-right font-extrabold text-lg text-indigo-600 dark:text-indigo-400">
                                Rp {{ number_format($order->total_amount, 0, ',', '.') }}
                            </td>
                        </tr>
                    </tfoot>
                </table>
            </div>
        </div>

        {{-- NOTES --}}
        @if($order->notes)
            <div class="card">
                <div class="card-header">
                    <h3 class="card-header-title">Catatan</h3>
                </div>
                <div class="card-body text-sm text-slate-600 dark:text-slate-300 italic">
                    {{ $order->notes }}
                </div>
            </div>
        @endif

    </div>

@endsection
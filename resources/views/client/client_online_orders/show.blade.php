@extends('client.layouts.app')

@section('title', 'Detail Pesanan #' . $order->order_number)

@section('content')

    {{-- Back Button --}}
    <div class="mb-6 flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div>
            <a href="{{ route('client.client-orders.index') }}" class="flex items-center gap-2 text-slate-500 hover:text-indigo-600 transition-colors w-fit mb-1">
                <i class="material-icons text-sm">arrow_back</i>
                <span class="text-sm font-medium">Kembali ke Daftar</span>
            </a>
            <h1 class="text-2xl font-bold text-slate-800 dark:text-white">Pesanan #{{ $order->order_number }}</h1>
            <div class="flex items-center gap-2 text-sm text-slate-500 mt-1">
                <i class="material-icons text-sm">event</i> {{ $order->order_date->format('d F Y') }}
                <span class="mx-1">•</span>
                <span>Dibuat oleh Anda (Online)</span>
            </div>
        </div>

        {{-- Status Badge Besar --}}
        <div>
            @php
                $statusColor = match($order->status) {
                    'pending_review' => 'bg-amber-100 text-amber-700 border-amber-200',
                    'approved' => 'bg-blue-100 text-blue-700 border-blue-200',
                    'invoiced' => 'bg-emerald-100 text-emerald-700 border-emerald-200',
                    'rejected' => 'bg-red-100 text-red-700 border-red-200',
                    default => 'bg-slate-100 text-slate-700 border-slate-200',
                };
                $statusIcon = match($order->status) {
                    'pending_review' => 'hourglass_empty',
                    'approved' => 'thumb_up',
                    'invoiced' => 'receipt_long',
                    'rejected' => 'cancel',
                    default => 'info',
                };
                $statusLabel = match($order->status) {
                    'pending_review' => 'Menunggu Review Admin',
                    'approved' => 'Disetujui & Diproses',
                    'invoiced' => 'Selesai (Faktur Terbit)',
                    'rejected' => 'Ditolak',
                    default => $order->status
                };
            @endphp
            <div class="px-4 py-2 rounded-xl border flex items-center gap-2 {{ $statusColor }}">
                <i class="material-icons text-lg">{{ $statusIcon }}</i>
                <span class="font-bold text-sm uppercase tracking-wide">{{ $statusLabel }}</span>
            </div>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
        
        {{-- Left: Items List --}}
        <div class="lg:col-span-2">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-header-title">Rincian Produk</h3>
                </div>
                <div class="table-container border-0 shadow-none rounded-none">
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
                                        <div class="font-medium text-slate-700 dark:text-white">
                                            {{ $item->product->product_name }}
                                        </div>
                                        <div class="text-xs text-slate-500">
                                            {{ $item->product->product_code }}
                                        </div>
                                    </td>
                                    <td class="text-right text-slate-600">
                                        Rp {{ number_format($item->price_per_unit, 0, ',', '.') }}
                                    </td>
                                    <td class="text-center font-bold">
                                        {{ number_format($item->quantity, 0, ',', '.') }}
                                    </td>
                                    <td class="text-right font-medium text-slate-800 dark:text-white">
                                        Rp {{ number_format($item->subtotal, 0, ',', '.') }}
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                        <tfoot>
                            <tr class="bg-slate-50 dark:bg-slate-800/50 border-t border-slate-200 dark:border-slate-700">
                                <td colspan="3" class="text-right font-bold text-slate-700 dark:text-slate-300 px-6 py-4">
                                    TOTAL ESTIMASI
                                </td>
                                <td class="text-right font-extrabold text-indigo-600 dark:text-indigo-400 px-6 py-4 text-lg">
                                    Rp {{ number_format($order->total_amount, 0, ',', '.') }}
                                </td>
                            </tr>
                        </tfoot>
                    </table>
                </div>

                @if($order->notes)
                    <div class="p-6 border-t border-slate-100 dark:border-slate-700/50">
                        <p class="text-xs font-bold text-slate-400 uppercase mb-2">Catatan Anda</p>
                        <div class="bg-amber-50 dark:bg-amber-900/10 p-3 rounded-lg text-sm text-slate-700 dark:text-slate-300 italic border border-amber-100 dark:border-amber-800/30">
                            "{{ $order->notes }}"
                        </div>
                    </div>
                @endif
            </div>
        </div>

        {{-- Right: Timeline & Actions --}}
        <div class="lg:col-span-1 flex flex-col gap-6">
            
            {{-- Info Card --}}
            <div class="card">
                <div class="card-header">
                    <h3 class="card-header-title">Informasi Pesanan</h3>
                </div>
                <div class="card-body text-sm space-y-4">
                    <div class="flex justify-between">
                        <span class="text-slate-500">Tanggal Dibuat</span>
                        <span class="font-medium dark:text-white">{{ $order->created_at->format('d M Y H:i') }}</span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-slate-500">Terakhir Update</span>
                        <span class="font-medium dark:text-white">{{ $order->updated_at->diffForHumans() }}</span>
                    </div>
                    <div class="border-t border-slate-100 dark:border-slate-700 pt-4 mt-2">
                        <p class="text-xs text-slate-400 mb-1">Penerima Pesanan</p>
                        <p class="font-bold text-slate-800 dark:text-white">Admin / Sales Internal</p>
                    </div>
                </div>
            </div>

            {{-- Action: Change Request --}}
            {{-- Hanya bisa dilakukan jika status masih pending_review atau approved (sebelum jadi invoice) --}}
            @if(in_array($order->status, ['pending_review', 'approved']))
                <div class="card bg-slate-50 border-slate-200 dark:bg-slate-800/50 dark:border-slate-700">
                    <div class="card-body">
                        <h4 class="font-bold text-slate-800 dark:text-white mb-2">Perubahan Pesanan?</h4>
                        <p class="text-xs text-slate-500 dark:text-slate-400 mb-4">
                            Jika Anda ingin mengubah jumlah, menghapus item, atau membatalkan pesanan ini, ajukan permintaan perubahan.
                        </p>
                        <a href="{{ route('client.sales-orders.requestChange.create', $order->order_id) }}" 
                           class="btn btn-secondary w-full justify-center">
                            <i class="material-icons text-sm">edit_note</i>
                            Ajukan Perubahan / Batal
                        </a>
                    </div>
                </div>
            @endif

            {{-- Action: View Invoice --}}
            @if($order->status == 'invoiced' && $order->invoice_id)
                <div class="card bg-emerald-50 border-emerald-100 dark:bg-emerald-900/20 dark:border-emerald-800">
                    <div class="card-body text-center">
                        <div class="w-12 h-12 bg-emerald-100 text-emerald-600 rounded-full flex items-center justify-center mx-auto mb-3">
                            <i class="material-icons">receipt</i>
                        </div>
                        <h4 class="font-bold text-emerald-800 dark:text-emerald-400 mb-1">Tagihan Tersedia</h4>
                        <p class="text-xs text-emerald-700 dark:text-emerald-500 mb-4">
                            Pesanan telah selesai. Silakan cek tagihan Anda.
                        </p>
                        <a href="{{ route('client.invoices.show', $order->invoice_id) }}" class="btn btn-success w-full justify-center shadow-lg shadow-emerald-200 dark:shadow-none">
                            Lihat Invoice
                        </a>
                    </div>
                </div>
            @endif

        </div>
    </div>

@endsection
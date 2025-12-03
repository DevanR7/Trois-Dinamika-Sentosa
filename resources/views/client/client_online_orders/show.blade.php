@extends('client.layouts.app')

@section('title', 'Detail Pesanan')

@section('content')
<div class="max-w-5xl mx-auto space-y-6">

    {{-- Header --}}
    <div class="flex items-center justify-between">
        <a href="{{ route('client.client-orders.index') }}" class="flex items-center text-slate-500 hover:text-slate-800 transition text-sm font-medium">
            <i class="material-icons text-[18px] mr-1">arrow_back</i> Kembali ke Daftar
        </a>
    </div>

    {{-- Main Content --}}
    <div class="dashboard-card overflow-hidden">
        {{-- Card Header --}}
        <div class="bg-slate-50 dark:bg-slate-800/50 px-6 py-4 border-b border-slate-200 dark:border-slate-700 flex flex-col md:flex-row justify-between items-start md:items-center gap-4">
            <div>
                <h2 class="text-xl font-bold text-slate-800 dark:text-slate-100">Pesanan #{{ $order->order_number }}</h2>
                <div class="flex items-center gap-2 mt-1 text-sm text-slate-500">
                    <i class="material-icons text-[16px]">event</i>
                    {{ $order->order_date->format('d F Y') }}
                </div>
            </div>
            
            @php
                $statusClass = [
                    'pending_review' => 'bg-amber-100 text-amber-700 border-amber-200', 
                    'approved' => 'bg-emerald-100 text-emerald-700 border-emerald-200',
                    'rejected' => 'bg-red-100 text-red-700 border-red-200',
                    'invoiced' => 'bg-cyan-100 text-cyan-700 border-cyan-200',
                ];
                $label = [
                    'pending_review' => 'Menunggu Review Admin',
                    'approved' => 'Disetujui',
                    'rejected' => 'Ditolak',
                    'invoiced' => 'Tagihan Terbit'
                ];
            @endphp
            <div class="px-4 py-2 rounded-lg border {{ $statusClass[$order->status] ?? 'bg-gray-100' }} flex items-center gap-2">
                @if($order->status == 'pending_review') <i class="material-icons text-[18px] animate-pulse">hourglass_empty</i> @endif
                @if($order->status == 'approved') <i class="material-icons text-[18px]">check_circle</i> @endif
                <span class="font-bold text-sm uppercase tracking-wide">{{ $label[$order->status] ?? $order->status }}</span>
            </div>
        </div>

        <div class="p-6">
            {{-- Tabel Item --}}
            <h3 class="font-bold text-slate-800 dark:text-slate-100 mb-4">Rincian Produk</h3>
            <div class="border border-slate-200 dark:border-slate-700 rounded-lg overflow-hidden mb-6">
                <table class="w-full text-left text-sm">
                    <thead class="bg-slate-50 dark:bg-slate-800 border-b border-slate-200 dark:border-slate-700">
                        <tr>
                            <th class="p-3 font-semibold text-slate-500 uppercase text-xs w-12 text-center">#</th>
                            <th class="p-3 font-semibold text-slate-500 uppercase text-xs">Produk</th>
                            <th class="p-3 font-semibold text-slate-500 uppercase text-xs text-center">Qty</th>
                            <th class="p-3 font-semibold text-slate-500 uppercase text-xs text-right">Harga Satuan</th>
                            <th class="p-3 font-semibold text-slate-500 uppercase text-xs text-right">Subtotal</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 dark:divide-slate-700">
                        @foreach($order->items as $item)
                        <tr>
                            <td class="p-3 text-center text-slate-400">{{ $loop->iteration }}</td>
                            <td class="p-3 font-medium text-slate-700 dark:text-slate-300">{{ $item->product->product_name ?? 'Produk Dihapus' }}</td>
                            <td class="p-3 text-center text-slate-600">{{ $item->quantity }}</td>
                            <td class="p-3 text-right text-slate-600">Rp {{ number_format($item->price_per_unit, 0, ',', '.') }}</td>
                            <td class="p-3 text-right font-bold text-slate-700 dark:text-slate-300">Rp {{ number_format($item->subtotal, 0, ',', '.') }}</td>
                        </tr>
                        @endforeach
                        <tr class="bg-slate-50 dark:bg-slate-800/50 font-bold text-slate-800 dark:text-slate-100 border-t border-slate-200 dark:border-slate-700">
                            <td colspan="4" class="p-4 text-right uppercase text-xs tracking-wider">Total Estimasi</td>
                            <td class="p-4 text-right text-lg text-indigo-600 dark:text-indigo-400">Rp {{ number_format($order->total_amount, 0, ',', '.') }}</td>
                        </tr>
                    </tbody>
                </table>
            </div>

            {{-- Catatan --}}
            @if($order->notes)
            <div class="bg-yellow-50 dark:bg-yellow-900/20 border border-yellow-100 dark:border-yellow-900/30 p-4 rounded-lg">
                <h4 class="text-xs font-bold text-yellow-700 dark:text-yellow-500 uppercase mb-1 flex items-center gap-1">
                    <i class="material-icons text-[16px]">sticky_note_2</i> Catatan Anda
                </h4>
                <p class="text-sm text-yellow-800 dark:text-yellow-200 italic">"{{ $order->notes }}"</p>
            </div>
            @endif
        </div>
    </div>
</div>
@endsection
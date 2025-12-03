@extends('client.layouts.app')

@section('title', 'Detail Pesanan')

@section('content')
<div class="space-y-6">

    {{-- HEADER & ACTIONS --}}
    <div class="flex flex-col md:flex-row justify-between items-start md:items-center gap-4">
        <div>
            <div class="flex items-center gap-2">
                <a href="{{ route('client.sales-orders.index') }}" class="text-slate-400 hover:text-slate-600 transition">
                    <i class="material-icons">arrow_back</i>
                </a>
                <h2 class="text-2xl font-bold text-slate-800 dark:text-slate-100 tracking-tight">Detail Pesanan</h2>
            </div>
            <p class="text-sm text-slate-500 ml-8 mt-1">Kode: <span class="font-mono text-slate-700 dark:text-slate-300">{{ $order->order_number }}</span></p>
        </div>

        {{-- Tombol Ajukan Perubahan --}}
        @php
            $canRequestChange = in_array($order->status, ['pending', 'approved']) && !$order->changeRequests()->where('status', 'pending')->exists();
        @endphp
        @if($canRequestChange)
            <a href="{{ route('client.sales-orders.requestChange.create', $order->order_id) }}" class="inline-flex items-center gap-2 bg-amber-500 hover:bg-amber-600 text-white font-bold py-2.5 px-5 rounded-lg shadow-md transition-all">
                <i class="material-icons text-[18px]">edit_note</i>
                Ajukan Perubahan
            </a>
        @endif
    </div>

    {{-- Alert Messages --}}
    @if(session('warning'))
        <div class="bg-amber-50 text-amber-800 p-4 rounded-lg border border-amber-200 flex items-center gap-2">
            <i class="material-icons">warning</i> {{ session('warning') }}
        </div>
    @endif
    @if(session('success'))
        <div class="bg-emerald-50 text-emerald-800 p-4 rounded-lg border border-emerald-200 flex items-center gap-2">
            <i class="material-icons">check_circle</i> {{ session('success') }}
        </div>
    @endif

    {{-- MAIN INFO CARD --}}
    <div class="dashboard-card p-6">
        <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
            <div class="space-y-4">
                <div>
                    <span class="block text-xs font-bold text-slate-500 uppercase tracking-wide mb-1">Klien</span>
                    <p class="text-lg font-bold text-slate-800 dark:text-slate-200">{{ $order->client->client_name }}</p>
                </div>
                <div>
                    <span class="block text-xs font-bold text-slate-500 uppercase tracking-wide mb-1">Sales</span>
                    <div class="flex items-center gap-2">
                        <div class="w-6 h-6 rounded-full bg-indigo-100 text-indigo-600 flex items-center justify-center text-xs font-bold">
                            {{ substr($order->sales->full_name ?? 'N', 0, 1) }}
                        </div>
                        <span class="text-slate-700 dark:text-slate-300">{{ $order->sales->full_name ?? 'N/A' }}</span>
                    </div>
                </div>
            </div>

            <div class="space-y-4 md:text-right">
                <div>
                    <span class="block text-xs font-bold text-slate-500 uppercase tracking-wide mb-1">Tanggal Pesanan</span>
                    <p class="text-slate-700 dark:text-slate-300 font-medium">{{ $order->order_date->format('d F Y') }}</p>
                </div>
                <div>
                    <span class="block text-xs font-bold text-slate-500 uppercase tracking-wide mb-1">Status Saat Ini</span>
                    @php
                        $statusClass = [
                            'pending' => 'bg-slate-100 text-slate-600 border-slate-200',
                            'approved' => 'bg-emerald-50 text-emerald-700 border-emerald-200',
                            'rejected' => 'bg-red-50 text-red-700 border-red-200',
                            'invoiced' => 'bg-cyan-50 text-cyan-700 border-cyan-200',
                        ];
                    @endphp
                    <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-bold uppercase tracking-wide border {{ $statusClass[$order->status] ?? '' }}">
                        {{ Str::title(str_replace('_', ' ', $order->status)) }}
                    </span>
                </div>
            </div>
        </div>

        <hr class="my-6 border-slate-100 dark:border-slate-700">

        <h3 class="font-bold text-slate-800 dark:text-slate-100 mb-4">Rincian Item</h3>
        <div class="overflow-hidden border border-slate-200 dark:border-slate-700 rounded-lg">
            <table class="w-full text-left text-sm">
                <thead class="bg-slate-50 dark:bg-slate-800 border-b border-slate-200 dark:border-slate-700">
                    <tr>
                        <th class="p-3 font-semibold text-slate-500 uppercase text-xs w-12 text-center">#</th>
                        <th class="p-3 font-semibold text-slate-500 uppercase text-xs">Produk</th>
                        <th class="p-3 font-semibold text-slate-500 uppercase text-xs text-center">Qty</th>
                        <th class="p-3 font-semibold text-slate-500 uppercase text-xs text-right">Harga</th>
                        <th class="p-3 font-semibold text-slate-500 uppercase text-xs text-right">Subtotal</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 dark:divide-slate-700">
                    @foreach($order->items as $item)
                    <tr class="hover:bg-slate-50 dark:hover:bg-slate-800/50">
                        <td class="p-3 text-center text-slate-400">{{ $loop->iteration }}</td>
                        <td class="p-3 font-medium text-slate-700 dark:text-slate-300">{{ $item->product->product_name ?? 'Produk Dihapus' }}</td>
                        <td class="p-3 text-center text-slate-600">{{ $item->quantity }}</td>
                        <td class="p-3 text-right text-slate-600">Rp {{ number_format($item->price_per_unit, 0, ',', '.') }}</td>
                        <td class="p-3 text-right font-bold text-slate-700 dark:text-slate-300">Rp {{ number_format($item->subtotal, 0, ',', '.') }}</td>
                    </tr>
                    @endforeach
                    <tr class="bg-slate-50 dark:bg-slate-800/50 font-bold text-slate-800 dark:text-slate-100">
                        <td colspan="4" class="p-4 text-right uppercase text-xs tracking-wider">Total Pesanan</td>
                        <td class="p-4 text-right text-lg">Rp {{ number_format($order->total_amount, 0, ',', '.') }}</td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>

    {{-- RIWAYAT PERMINTAAN PERUBAHAN --}}
    @if($order->changeRequests->isNotEmpty())
    <div class="dashboard-card p-6">
        <h3 class="font-bold text-slate-800 dark:text-slate-100 mb-6 flex items-center gap-2">
            <i class="material-icons text-slate-400">history</i> Riwayat Permintaan Perubahan
        </h3>
        
        <div class="relative border-l-2 border-slate-200 dark:border-slate-700 ml-3 space-y-8 pl-8">
            @foreach($order->changeRequests as $request)
                <div class="relative">
                    {{-- Timeline Dot --}}
                    <span class="absolute -left-[41px] top-1 w-5 h-5 rounded-full border-2 border-white dark:border-slate-900 
                        {{ $request->status == 'pending' ? 'bg-amber-500' : ($request->status == 'approved' ? 'bg-emerald-500' : 'bg-red-500') }}">
                    </span>

                    <div class="bg-slate-50 dark:bg-slate-800/50 border border-slate-100 dark:border-slate-700 rounded-lg p-4">
                        <div class="flex justify-between items-start mb-2">
                            <div>
                                <h4 class="font-bold text-slate-800 dark:text-slate-200">
                                    Permintaan {{ $request->request_type == 'cancel' ? 'Pembatalan' : 'Modifikasi Item' }}
                                </h4>
                                <span class="text-xs text-slate-400">{{ $request->created_at->format('d M Y H:i') }}</span>
                            </div>
                            @php
                                $reqStatusClass = [
                                    'pending' => 'bg-amber-100 text-amber-700',
                                    'approved' => 'bg-emerald-100 text-emerald-700',
                                    'rejected' => 'bg-red-100 text-red-700',
                                ];
                            @endphp
                            <span class="px-2 py-1 rounded text-xs font-bold uppercase tracking-wide {{ $reqStatusClass[$request->status] ?? 'bg-gray-100' }}">
                                {{ Str::title($request->status) }}
                            </span>
                        </div>

                        @if($request->client_notes)
                            <div class="text-sm text-slate-600 dark:text-slate-300 italic mb-3">
                                "{{ $request->client_notes }}"
                            </div>
                        @endif

                        {{-- Item Changes --}}
                        @if($request->request_type == 'modify' && $request->items->isNotEmpty())
                            <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-700 rounded p-3 text-sm">
                                <p class="text-xs font-bold text-slate-500 uppercase mb-2">Detail Perubahan:</p>
                                <ul class="space-y-1">
                                    @foreach($request->items as $reqItem)
                                        <li class="flex justify-between text-slate-700 dark:text-slate-300">
                                            <span>{{ $reqItem->product->product_name ?? 'N/A' }}</span>
                                            <span>
                                                @if($reqItem->action == 'add')
                                                    <span class="text-emerald-600 font-bold">+{{ $reqItem->requested_quantity }}</span>
                                                @elseif($reqItem->action == 'remove')
                                                    <span class="text-red-600 font-bold line-through text-xs mr-1">{{ $reqItem->original_quantity }}</span> <span class="text-red-600 font-bold">Hapus</span>
                                                @elseif($reqItem->action == 'update_qty')
                                                    <span class="text-slate-400">{{ $reqItem->original_quantity }}</span> &rarr; <span class="font-bold text-indigo-600">{{ $reqItem->requested_quantity }}</span>
                                                @endif
                                            </span>
                                        </li>
                                    @endforeach
                                </ul>
                            </div>
                        @endif

                        @if($request->admin_notes)
                            <div class="mt-3 pt-3 border-t border-slate-200 dark:border-slate-700">
                                <p class="text-xs font-bold text-indigo-600 mb-1">Catatan Admin:</p>
                                <p class="text-sm text-slate-600">{{ $request->admin_notes }}</p>
                            </div>
                        @endif
                    </div>
                </div>
            @endforeach
        </div>
    </div>
    @endif

</div>
@endsection
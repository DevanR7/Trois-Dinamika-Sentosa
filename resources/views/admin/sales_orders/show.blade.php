@extends('admin.layouts.app')

@section('title', 'Detail Pesanan #' . $order->order_number)

@section('content')
<div class="flex flex-col gap-6">

    {{-- HEADER SECTION --}}
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div class="flex items-start gap-4">
            {{-- FIX: Button Back Simetris --}}
            <a href="{{ route('admin.sales-orders.index') }}" 
               class="w-9 h-9 flex items-center justify-center rounded-lg bg-white border border-slate-200 text-slate-500 hover:bg-slate-100 hover:text-indigo-600 transition-all dark:bg-slate-800 dark:border-slate-700 dark:text-slate-400 dark:hover:text-white shadow-sm mt-1">
                <i class="material-icons text-xl leading-none">arrow_back</i>
            </a>

            <div>
                <div class="flex items-center gap-3">
                    <h1 class="page-title text-xl">Pesanan <span class="text-indigo-600">#{{ $order->order_number }}</span></h1>
                    @php
                        $statusClass = match($order->status) {
                            'pending' => 'badge-warning',
                            'approved' => 'badge-primary',
                            'invoiced' => 'badge-success',
                            'rejected' => 'badge-danger',
                            'pending_review' => 'badge-info',
                            default => 'badge-secondary'
                        };
                        $statusLabel = match($order->status) {
                            'pending' => 'Pending',
                            'approved' => 'Disetujui',
                            'invoiced' => 'Terbit Invoice',
                            'rejected' => 'Dibatalkan',
                            'pending_review' => 'Review Klien',
                            default => ucfirst($order->status)
                        };
                    @endphp
                    <span class="badge {{ $statusClass }}">{{ $statusLabel }}</span>
                </div>
                <p class="text-sm text-slate-500 mt-1 flex items-center gap-2">
                    <i class="material-icons text-sm">calendar_today</i>
                    {{ $order->order_date->format('d F Y') }}
                    <span class="text-slate-300">|</span>
                    <i class="material-icons text-sm">schedule</i>
                    {{ $order->created_at->format('H:i') }} WIB
                </p>
            </div>
        </div>

        {{-- Action Buttons --}}
        <div class="flex items-center gap-2 flex-wrap sm:flex-nowrap">
            @if($order->status === 'pending' || $order->status === 'approved')
                {{-- Edit --}}
                <a href="{{ route('admin.sales-orders.edit', $order->order_id) }}" class="btn btn-secondary">
                    <i class="material-icons text-[18px]">edit</i>
                    <span class="hidden sm:inline">Edit</span>
                </a>

                {{-- Batalkan --}}
                <form action="{{ route('admin.sales-orders.cancel', $order->order_id) }}" method="POST" onsubmit="return confirm('Apakah Anda yakin ingin membatalkan pesanan ini?');">
                    @csrf
                    <button type="submit" class="btn btn-danger">
                        <i class="material-icons text-[18px]">cancel</i>
                        <span class="hidden sm:inline">Batalkan</span>
                    </button>
                </form>

                {{-- Proses Ke Invoice (Primary Action) --}}
                <a href="{{ route('admin.invoices.createFromOrder', $order->order_id) }}" class="btn btn-primary shadow-lg shadow-indigo-500/30">
                    <i class="material-icons text-[18px]">receipt_long</i>
                    <span>Proses ke Invoice</span>
                </a>
            @endif

            @if($order->status === 'invoiced' && $order->invoice_id)
                <a href="{{ route('admin.invoices.show', $order->invoice_id) }}" class="btn btn-primary bg-emerald-600 hover:bg-emerald-700 border-transparent text-white">
                    <i class="material-icons text-[18px]">description</i>
                    <span>Lihat Invoice</span>
                </a>
            @endif
            
            @if($order->status === 'pending_review')
                <a href="{{ route('admin.client-order-reviews.show', $order->order_id) }}" class="btn btn-primary bg-amber-500 hover:bg-amber-600 border-transparent text-white">
                    <i class="material-icons text-[18px]">rate_review</i>
                    <span>Review Pesanan</span>
                </a>
            @endif
        </div>
    </div>

    {{-- CONTENT GRID --}}
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        
        {{-- LEFT COLUMN: ITEMS & NOTES --}}
        <div class="lg:col-span-2 flex flex-col gap-6">
            
            {{-- Items Card --}}
            <div class="card overflow-hidden">
                <div class="card-header bg-slate-50 dark:bg-slate-800 border-b border-slate-200 dark:border-slate-700">
                    <h3 class="font-bold text-slate-700 dark:text-white flex items-center gap-2">
                        <i class="material-icons text-slate-400">shopping_cart</i> Rincian Barang
                    </h3>
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
                                    <div class="flex flex-col">
                                        <span class="font-bold text-slate-700 dark:text-slate-200">
                                            {{ $item->product->product_name ?? 'Produk Terhapus' }}
                                        </span>
                                        <span class="text-xs text-slate-500 font-mono">
                                            {{ $item->product->product_code ?? '-' }}
                                        </span>
                                    </div>
                                </td>
                                <td class="text-right">
                                    Rp {{ number_format($item->price_per_unit, 0, ',', '.') }}
                                </td>
                                <td class="text-center">
                                    <span class="px-2 py-1 bg-slate-100 dark:bg-slate-700 rounded text-xs font-bold">
                                        {{ number_format($item->quantity, 0, ',', '.') }} {{ $item->product->unit->name ?? 'pcs' }}
                                    </span>
                                </td>
                                <td class="text-right font-bold text-slate-700 dark:text-white">
                                    Rp {{ number_format($item->subtotal, 0, ',', '.') }}
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                        <tfoot class="bg-slate-50 dark:bg-slate-800/50">
                            <tr>
                                <td colspan="3" class="text-right px-6 py-4 font-bold text-slate-600 dark:text-slate-400 uppercase text-xs tracking-wider">Total Pesanan</td>
                                <td class="text-right px-6 py-4">
                                    <span class="text-xl font-black text-indigo-600 dark:text-indigo-400">
                                        Rp {{ number_format($order->total_amount, 0, ',', '.') }}
                                    </span>
                                </td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            </div>

            {{-- Notes Card --}}
            @if($order->notes)
            <div class="card">
                <div class="card-body">
                    <h4 class="text-xs font-bold uppercase text-slate-400 mb-2 flex items-center gap-2">
                        <i class="material-icons text-sm">sticky_note_2</i> Catatan
                    </h4>
                    <div class="p-4 bg-amber-50 dark:bg-amber-900/10 rounded-xl border border-amber-100 dark:border-amber-800 text-slate-700 dark:text-slate-300 text-sm leading-relaxed whitespace-pre-line">
                        {{ $order->notes }}
                    </div>
                </div>
            </div>
            @endif

            {{-- Change Request History (If exists) --}}
            @if($order->changeRequests->count() > 0)
            <div class="card">
                <div class="card-header">
                    <h3 class="card-header-title">Riwayat Permintaan Perubahan</h3>
                </div>
                <div class="table-container border-0 shadow-none rounded-none">
                    <table class="table-modern">
                        <thead>
                            <tr>
                                <th>Tanggal</th>
                                <th>Tipe</th>
                                <th>Status</th>
                                <th>Catatan Admin</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($order->changeRequests as $req)
                            <tr>
                                <td class="text-xs">{{ $req->created_at->format('d/m/Y H:i') }}</td>
                                <td>
                                    @if($req->request_type == 'cancel') <span class="badge badge-danger">Batal</span>
                                    @else <span class="badge badge-warning">Ubah Item</span> @endif
                                </td>
                                <td>
                                    @if($req->status == 'pending') <span class="badge badge-warning">Pending</span>
                                    @elseif($req->status == 'approved') <span class="badge badge-success">Disetujui</span>
                                    @else <span class="badge badge-danger">Ditolak</span> @endif
                                </td>
                                <td class="text-xs text-slate-500 italic">{{ $req->admin_notes ?? '-' }}</td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
            @endif

        </div>

        {{-- RIGHT COLUMN: INFO --}}
        <div class="flex flex-col gap-6">

            {{-- Client Info --}}
            <div class="card">
                <div class="card-header border-b border-slate-100 dark:border-slate-700/50 pb-3">
                    <h3 class="font-bold text-slate-700 dark:text-white text-sm">Informasi Klien</h3>
                </div>
                <div class="card-body space-y-4">
                    <div class="flex items-center gap-3">
                        <div class="w-12 h-12 rounded-full bg-indigo-50 dark:bg-indigo-900/30 flex items-center justify-center text-indigo-600 dark:text-indigo-400 font-bold text-lg">
                            {{ substr($order->client->client_name ?? 'U', 0, 1) }}
                        </div>
                        <div>
                            <p class="font-bold text-slate-800 dark:text-white">{{ $order->client->client_name ?? 'Umum' }}</p>
                            @if($order->client->email)
                                <p class="text-xs text-slate-500">{{ $order->client->email }}</p>
                            @endif
                        </div>
                    </div>
                    
                    <div class="space-y-2 pt-2 border-t border-slate-100 dark:border-slate-700/50">
                        <div class="flex items-start gap-3 text-sm">
                            <i class="material-icons text-slate-400 text-base mt-0.5">person</i>
                            <span class="text-slate-600 dark:text-slate-300">{{ $order->client->person_in_charge ?? '-' }} (PIC)</span>
                        </div>
                        <div class="flex items-start gap-3 text-sm">
                            <i class="material-icons text-slate-400 text-base mt-0.5">call</i>
                            @if($order->client->phone_number)
                                <a href="https://wa.me/{{ preg_replace('/^0/', '62', preg_replace('/[^0-9]/', '', $order->client->phone_number)) }}" target="_blank" class="text-emerald-600 hover:underline flex items-center gap-1">
                                    {{ $order->client->phone_number }}
                                </a>
                            @else
                                <span class="text-slate-600">-</span>
                            @endif
                        </div>
                        <div class="flex items-start gap-3 text-sm">
                            <i class="material-icons text-slate-400 text-base mt-0.5">location_on</i>
                            <span class="text-slate-600 dark:text-slate-300">{{ $order->client->address ?? '-' }}</span>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Sales Info --}}
            <div class="card">
                <div class="card-body flex items-center justify-between">
                    <div>
                        <p class="text-[10px] uppercase font-bold text-slate-400 mb-1">Dibuat Oleh Sales</p>
                        <p class="font-bold text-slate-700 dark:text-slate-200 text-sm">
                            {{ $order->sales->full_name ?? 'System Admin' }}
                        </p>
                        <p class="text-xs text-slate-500">{{ $order->sales->sales_code ?? '-' }}</p>
                    </div>
                    <div class="w-10 h-10 rounded-lg bg-slate-100 dark:bg-slate-700 flex items-center justify-center text-slate-500">
                        <i class="material-icons">badge</i>
                    </div>
                </div>
            </div>

            {{-- Linked Documents --}}
            @if($order->status === 'invoiced' && $order->invoice)
            <div class="card bg-emerald-50 dark:bg-emerald-900/10 border border-emerald-100 dark:border-emerald-800">
                <div class="card-body">
                    <p class="text-xs font-bold text-emerald-600 dark:text-emerald-400 uppercase mb-2 flex items-center gap-1">
                        <i class="material-icons text-sm">link</i> Dokumen Terhubung
                    </p>
                    <div class="flex justify-between items-center bg-white dark:bg-slate-800 p-3 rounded-lg shadow-sm border border-emerald-100 dark:border-slate-700">
                        <div>
                            <p class="text-xs text-slate-400">Sales Invoice</p>
                            <a href="{{ route('admin.invoices.show', $order->invoice_id) }}" class="font-bold text-indigo-600 hover:underline text-sm">
                                {{ $order->invoice->invoice_number }}
                            </a>
                        </div>
                        <div class="text-right">
                             @php
                                $invStatus = $order->invoice->status;
                                $badgeColor = match($invStatus) {
                                    'paid' => 'text-emerald-600 bg-emerald-50',
                                    'unpaid' => 'text-red-600 bg-red-50',
                                    'partially_paid' => 'text-amber-600 bg-amber-50',
                                    default => 'text-slate-600 bg-slate-50'
                                };
                                $invLabel = match($invStatus) {
                                    'paid' => 'Lunas',
                                    'unpaid' => 'Belum Lunas',
                                    'partially_paid' => 'Sebagian',
                                    default => ucfirst($invStatus)
                                };
                            @endphp
                            <span class="text-[10px] font-bold px-2 py-1 rounded {{ $badgeColor }}">
                                {{ $invLabel }}
                            </span>
                        </div>
                    </div>
                </div>
            </div>
            @endif

        </div>

    </div>
</div>
@endsection
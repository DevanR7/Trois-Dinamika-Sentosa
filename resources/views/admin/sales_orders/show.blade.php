@extends('admin.layouts.app')

@section('title', 'Detail Pesanan Penjualan')

@section('content')
<div class="max-w-7xl mx-auto pb-20 animate-enter">
    
    {{-- HEADER --}}
    <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center mb-8 gap-4">
        <div>
            <nav class="flex text-sm text-slate-500 mb-1">
                <a href="{{ route('admin.sales-orders.index') }}" class="hover:text-indigo-600 transition-colors">Pesanan</a>
                <span class="mx-2 text-slate-300">/</span>
                <span class="text-slate-800 font-semibold">Detail</span>
            </nav>
            <h1 class="text-2xl font-bold text-slate-800 tracking-tight flex items-center gap-3">
                <span class="font-mono text-indigo-600 bg-indigo-50 px-2 py-0.5 rounded border border-indigo-100">{{ $order->order_number }}</span>
            </h1>
        </div>
        
        <div class="flex gap-3 w-full sm:w-auto">
            <a href="{{ route('admin.sales-orders.index') }}" class="h-[48px] px-6 bg-white border border-slate-300 text-slate-700 rounded-lg text-sm font-bold hover:bg-slate-50 transition-all flex items-center justify-center gap-2 shadow-sm">
                <i class="material-icons text-[18px]">arrow_back</i> Kembali
            </a>

            {{-- DROPDOWN OPSI --}}
            @if (!in_array($order->status, ['invoiced', 'rejected']))
            <div class="relative" x-data="{ open: false }">
                <button 
                    @click="open = !open" 
                    @click.outside="open = false" 
                    class="h-[48px] px-6 bg-indigo-600 text-white border border-indigo-600 rounded-lg text-sm font-bold hover:bg-indigo-700 transition-all flex items-center justify-center gap-2 shadow-md">
                    <i class="material-icons text-[18px]">settings</i> 
                    Opsi 
                    <i class="material-icons text-[18px]">expand_more</i>
                </button>

                <div 
                    x-show="open"
                    class="absolute right-0 mt-2 w-48 bg-white rounded-lg shadow-xl border border-slate-100 z-50 overflow-hidden hidden"
                    :class="{'hidden': !open}"
                >
                    @can('create', App\Models\SalesInvoice::class)
                        <a href="{{ route('admin.invoices.createFromOrder', $order->order_id) }}" 
                           class="flex items-center px-4 py-3 text-sm text-emerald-600 hover:bg-emerald-50 transition font-medium">
                            <i class="material-icons text-lg mr-2">receipt_long</i> Buat Invoice
                        </a>
                    @endcan
                    
                    @can("update", $order)
                        <a href="{{ route('admin.sales-orders.edit', $order->order_id) }}" 
                           class="flex items-center px-4 py-3 text-sm text-amber-600 hover:bg-amber-50 transition border-t border-slate-50">
                            <i class="material-icons text-lg mr-2">edit</i> Edit Pesanan
                        </a>
                    @endcan
                    
                    @can("delete", $order)
                        <form action="{{ route('admin.sales-orders.destroy', $order->order_id) }}" method="POST" class="delete-form w-full">
                            @csrf @method('DELETE')
                            <button type="submit" data-name="Pesanan {{ $order->order_number }}" class="w-full text-left flex items-center px-4 py-3 text-sm text-red-600 hover:bg-red-50 transition border-t border-slate-50">
                                <i class="material-icons text-lg mr-2">delete</i> Hapus
                            </button>
                        </form>
                    @endcan
                </div>
            </div>
            @endif

            @if ($order->status == 'invoiced' && $order->invoice_id)
                <a href="{{ route('admin.invoices.show', $order->invoice_id) }}" class="h-[48px] px-6 bg-emerald-600 text-white rounded-lg text-sm font-bold hover:bg-emerald-700 transition-all shadow-md flex items-center justify-center gap-2">
                    <i class="material-icons text-[18px]">visibility</i> Lihat Invoice
                </a>
            @endif
        </div>
    </div>

    {{-- GRID LAYOUT --}}
    <div class="grid grid-cols-1 lg:grid-cols-12 gap-8">

        {{-- KOLOM KIRI --}}
        <div class="lg:col-span-8 space-y-8">

            {{-- CARD INFO --}}
            <div class="dashboard-card p-0 overflow-hidden shadow-sm">
                <div class="px-6 py-4 border-b border-slate-100 bg-slate-50/50 flex items-center gap-3">
                    <div class="p-2 bg-blue-50 rounded-lg text-blue-600">
                        <i class="material-icons text-[20px]">info</i>
                    </div>
                    <h3 class="text-sm font-bold text-slate-700 uppercase tracking-wide">Detail Pesanan</h3>
                </div>
                
                <div class="p-6 grid grid-cols-1 md:grid-cols-2 gap-8">
                    {{-- Klien --}}
                    <div class="flex items-start gap-4">
                        <div class="w-12 h-12 rounded-full bg-indigo-50 flex items-center justify-center text-indigo-600 flex-shrink-0 border border-indigo-100">
                            <i class="material-icons text-xl">business</i>
                        </div>
                        <div>
                            <label class="block text-xs font-bold text-slate-400 uppercase mb-1 tracking-wide">Klien</label>
                            <h4 class="text-base font-bold text-slate-900">{{ $order->client->client_name }}</h4>
                            <p class="text-xs text-slate-500 mt-0.5">{{ $order->client->address ?? 'Alamat tidak tersedia' }}</p>
                            <p class="text-xs text-slate-500">{{ $order->client->phone_number ?? '-' }}</p>
                        </div>
                    </div>

                    {{-- Sales & Tanggal --}}
                    <div class="space-y-4 border-l border-slate-100 pl-0 md:pl-8">
                        <div class="flex justify-between items-center">
                            <span class="text-xs font-bold text-slate-500 uppercase">Tanggal Pesan</span>
                            <span class="text-sm font-semibold text-slate-800 flex items-center gap-2">
                                <i class="material-icons text-slate-400 text-[16px]">event</i> 
                                {{ $order->order_date->format('d F Y') }}
                            </span>
                        </div>
                        <div class="flex justify-between items-center">
                            <span class="text-xs font-bold text-slate-500 uppercase">Sales Person</span>
                            <span class="text-sm font-semibold text-slate-800 flex items-center gap-2">
                                <i class="material-icons text-slate-400 text-[16px]">person</i> 
                                {{ $order->sales->full_name ?? 'N/A' }}
                            </span>
                        </div>

                        {{-- STATUS BADGE --}}
                        <div class="flex justify-between items-center">
                            <span class="text-xs font-bold text-slate-500 uppercase">Status</span>

                            @php
                                $statusMap = [
                                    'pending'   => ['class' => 'status-pending', 'label' => 'Pending'],
                                    'approved'  => ['class' => 'status-approved', 'label' => 'Disetujui'],
                                    'rejected'  => ['class' => 'status-rejected', 'label' => 'Ditolak'],
                                    'invoiced'  => ['class' => 'status-completed', 'label' => 'Ditagih (Invoiced)'],
                                ];
                                $st = $statusMap[$order->status] ?? ['class' => 'status-draft', 'label' => 'Draft'];
                            @endphp

                            <span class="{{ $st['class'] }}">{{ $st['label'] }}</span>
                        </div>
                    </div>
                </div>

                @if($order->notes)
                <div class="p-6 border-t border-slate-100 bg-yellow-50/30">
                    <label class="text-xs font-bold text-slate-500 uppercase block mb-2">Catatan</label>
                    <div class="text-sm text-slate-700 italic bg-white p-3 rounded border border-slate-200">
                        "{{ $order->notes }}"
                    </div>
                </div>
                @endif
            </div>

            {{-- CARD ITEM --}}
            <div class="dashboard-card p-0 overflow-hidden shadow-sm">
                <div class="px-6 py-4 border-b border-slate-100 bg-slate-50/50 flex items-center gap-3">
                    <i class="material-icons text-slate-400">list_alt</i>
                    <h3 class="text-sm font-bold text-slate-700 uppercase tracking-wide">Rincian Barang</h3>
                </div>

                <div class="overflow-x-auto">
                    <table class="dashboard-table min-w-full">
                        <thead class="bg-slate-50 border-b border-slate-200">
                            <tr>
                                <th class="pl-6 text-center w-12">#</th>
                                <th>Produk</th>
                                <th class="text-center">Qty</th>
                                <th class="text-right">Harga Satuan</th>
                                <th class="text-right pr-6">Subtotal</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100 bg-white">
                            @foreach($order->items as $item)
                            <tr class="hover:bg-slate-50 transition-colors">
                                <td class="pl-6 py-4 text-center text-sm text-slate-500">{{ $loop->iteration }}</td>
                                <td class="py-4">
                                    <div class="text-sm font-bold text-slate-800">{{ $item->product->product_name ?? 'Produk Dihapus' }}</div>
                                    <div class="text-xs text-slate-500 font-mono mt-0.5">{{ $item->product->product_code ?? '-' }}</div>
                                </td>
                                <td class="py-4 text-center">
                                    <span class="inline-block px-2.5 py-1 rounded-md bg-slate-100 text-xs font-bold text-slate-700 border border-slate-200 shadow-sm">
                                        {{ $item->quantity }} {{ $item->product->unit->name ?? '' }}
                                    </span>
                                </td>
                                <td class="py-4 text-right text-sm text-slate-600 font-mono">
                                    Rp {{ number_format($item->price_per_unit, 0, ',', '.') }}
                                </td>
                                <td class="pr-6 py-4 text-right text-sm font-bold text-slate-900 font-mono">
                                    Rp {{ number_format($item->subtotal, 0, ',', '.') }}
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>

        </div>

        {{-- KOLOM KANAN --}}
        <div class="lg:col-span-4 space-y-6">
            <div class="dashboard-card p-6 shadow-lg sticky top-6 border-t-4 border-indigo-500">
                <h3 class="card-title mb-6 border-b border-slate-100 pb-3 flex items-center gap-2">
                    <i class="material-icons text-indigo-600">calculate</i> Ringkasan Nilai
                </h3>

                <div class="flex flex-col items-center justify-center mb-8 bg-slate-50 p-6 rounded-xl border border-slate-100">
                    <span class="text-xs font-bold text-slate-400 uppercase tracking-widest mb-2">Total Tagihan</span>
                    <span class="text-3xl font-bold text-indigo-700 font-mono tracking-tight">
                        Rp {{ number_format($order->total_amount, 0, ',', '.') }}
                    </span>
                </div>

                <div class="space-y-3 text-sm text-slate-600">
                    <div class="flex justify-between border-b border-dashed border-slate-200 pb-2">
                        <span>Total Item</span>
                        <span class="font-bold">{{ $order->items->count() }} Barang</span>
                    </div>
                    <div class="flex justify-between border-b border-dashed border-slate-200 pb-2">
                        <span>Total Qty</span>
                        <span class="font-bold">{{ $order->items->sum('quantity') }} Unit</span>
                    </div>
                </div>

                @if ($order->status == 'rejected' && $order->rejection_reason)
                <div class="mt-6 p-4 bg-red-50 border border-red-200 rounded-lg">
                    <h6 class="text-xs font-bold text-red-800 uppercase mb-2 flex items-center gap-2">
                        <i class="material-icons text-red-600 text-sm">cancel</i> Alasan Penolakan
                    </h6>
                    <p class="text-sm text-red-800 italic">"{{ $order->rejection_reason }}"</p>
                </div>
                @endif
            </div>
        </div>

    </div>
</div>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    @if(session('success')) window.showToast("{{ session('success') }}", 'success'); @endif
    @if(session('error')) window.showToast("{{ session('error') }}", 'error'); @endif
});
</script>
@endpush

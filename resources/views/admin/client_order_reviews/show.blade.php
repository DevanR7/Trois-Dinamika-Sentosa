@extends('admin.layouts.app')

@section('title', 'Detail Review Pesanan')

@section('content')
<div class="max-w-6xl mx-auto pb-20 animate-enter">

    {{-- HEADER --}}
    <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center mb-8 gap-4">
        <div>
            <nav class="flex items-center gap-2 text-sm text-slate-500 mb-1">
                <a href="{{ route('admin.client-order-reviews.index') }}" class="hover:text-indigo-600 transition-colors font-medium">Review Pesanan</a>
                <span class="mx-2 text-slate-300">/</span>
                <span class="text-slate-800 font-semibold">Detail</span>
            </nav>
            <h1 class="text-2xl font-bold text-slate-800 tracking-tight flex items-center gap-3">
                <span class="font-mono text-indigo-600 bg-indigo-50 px-2 py-0.5 rounded border border-indigo-100">{{ $order->order_number }}</span>
                
                @php
                    $statusMap = [
                        'pending_review' => ['class' => 'status-pending', 'icon' => 'schedule', 'label' => 'Menunggu Review'],
                        'invoiced' => ['class' => 'status-completed', 'icon' => 'verified', 'label' => 'Disetujui'],
                        'approved' => ['class' => 'status-approved', 'icon' => 'check_circle', 'label' => 'Disetujui'],
                        'rejected' => ['class' => 'status-rejected', 'icon' => 'cancel', 'label' => 'Ditolak'],
                    ];
                    $st = $statusMap[$order->status] ?? ['class' => 'status-draft', 'icon' => 'edit_note', 'label' => 'Draft'];
                @endphp
                <span class="{{ $st['class'] }} flex items-center gap-1 text-xs h-fit px-2.5 py-1 rounded-md">
                    <i class="material-icons text-[14px]">{{ $st['icon'] }}</i> {{ $st['label'] }}
                </span>
            </h1>
        </div>
        
        <div class="flex gap-3 w-full sm:w-auto">
            <a href="{{ route('admin.client-order-reviews.index') }}" class="h-[48px] px-6 bg-white border border-slate-300 rounded-lg text-slate-700 text-sm font-bold hover:bg-slate-50 transition-all flex items-center justify-center gap-2 shadow-sm">
                <i class="material-icons text-[18px]">arrow_back</i> Kembali
            </a>
            
            @if($order->status == 'pending_review')
            <button type="button" onclick="document.getElementById('rejectModal').classList.remove('hidden')" 
                    class="h-[48px] px-6 bg-red-50 border border-red-200 text-red-700 font-bold rounded-lg hover:bg-red-100 transition-all text-sm flex items-center justify-center gap-2 shadow-sm">
                <i class="material-icons text-[18px]">cancel</i> Tolak
            </button>
            @endif
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-12 gap-8">
        
        {{-- KOLOM KIRI: DETAIL ITEM (Span 8) --}}
        <div class="lg:col-span-8 space-y-6">
            
            {{-- CARD ITEM --}}
            <div class="dashboard-card p-0 overflow-hidden shadow-sm">
                <div class="px-6 py-4 border-b border-slate-100 bg-slate-50/80 flex justify-between items-center">
                    <h3 class="card-title flex items-center gap-2">
                        <i class="material-icons text-indigo-600 text-lg">list_alt</i> Rincian Item
                    </h3>
                    <span class="text-xs font-bold bg-indigo-100 text-indigo-700 px-2.5 py-1 rounded-md border border-indigo-200">
                        {{ $order->items->count() }} Produk
                    </span>
                </div>
                <div class="overflow-x-auto">
                    <table class="dashboard-table min-w-full">
                        <thead class="bg-white border-b border-slate-200">
                            <tr>
                                <th class="pl-6 text-center w-12">No</th>
                                <th>Produk</th>
                                <th class="text-center">Qty</th>
                                <th class="text-right">Harga (@)</th>
                                <th class="text-right pr-6">Subtotal</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            @forelse($order->items as $item)
                            <tr class="hover:bg-slate-50 transition">
                                <td class="pl-6 py-4 text-center text-sm text-slate-500">{{ $loop->iteration }}</td>
                                <td class="py-4">
                                    <div class="text-sm font-bold text-slate-800">{{ $item->product->product_name ?? 'Produk Dihapus' }}</div>
                                    @if($item->product)
                                        <div class="text-xs text-slate-500 font-mono mt-0.5">{{ $item->product->product_code }}</div>
                                    @endif
                                </td>
                                <td class="py-4 text-center">
                                    <span class="inline-block px-2.5 py-1 rounded-md bg-slate-100 text-xs font-bold text-slate-700 border border-slate-200">
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
                            @empty
                            <tr><td colspan="5" class="text-center py-8 text-slate-500 italic">Tidak ada item.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
                <div class="px-6 py-4 border-t border-slate-100 bg-slate-50/50 flex justify-end gap-4 items-center">
                    <span class="text-xs font-bold text-slate-500 uppercase tracking-wider">Total Pesanan</span>
                    <span class="text-xl font-bold text-indigo-700 font-mono">Rp {{ number_format($order->total_amount, 0, ',', '.') }}</span>
                </div>
            </div>

            {{-- CARD ALASAN (JIKA DITOLAK/CATATAN) --}}
            @if($order->notes)
            <div class="bg-yellow-50 rounded-xl border border-yellow-100 p-5 flex gap-4 items-start shadow-sm">
                <div class="bg-yellow-100 p-2 rounded-full text-yellow-600">
                    <i class="material-icons text-xl">sticky_note_2</i>
                </div>
                <div>
                    <h4 class="text-xs font-bold text-yellow-800 uppercase mb-1 tracking-wide">Catatan Pesanan</h4>
                    <p class="text-sm text-yellow-900 italic leading-relaxed">"{{ $order->notes }}"</p>
                </div>
            </div>
            @endif

        </div>

        {{-- KOLOM KANAN: INFO & AKSI (Span 4) --}}
        <div class="lg:col-span-4 space-y-6">
            
            <div class="dashboard-card p-0 overflow-hidden shadow-lg border-0 ring-1 ring-slate-900/5">
                <div class="px-6 py-4 border-b border-slate-100 bg-slate-50/50 flex items-center gap-3">
                    <div class="p-2 bg-blue-50 rounded-lg text-blue-600">
                        <i class="material-icons text-[20px]">person</i>
                    </div>
                    <h3 class="text-sm font-bold text-slate-700 uppercase tracking-wide">Info Pemesan</h3>
                </div>
                
                <div class="p-6 space-y-6">
                    <div class="flex items-center gap-4">
                        <div class="w-12 h-12 rounded-full bg-indigo-50 flex items-center justify-center text-indigo-600 border border-indigo-100 flex-shrink-0">
                            <i class="material-icons text-xl">business</i>
                        </div>
                        <div>
                            <label class="block text-[10px] font-bold text-slate-400 uppercase tracking-wider">Klien</label>
                            <h4 class="text-base font-bold text-slate-900 leading-tight">{{ $order->client->client_name }}</h4>
                        </div>
                    </div>

                    <div class="space-y-3 pt-4 border-t border-dashed border-slate-200">
                         <div class="flex justify-between">
                            <span class="text-xs font-medium text-slate-500 uppercase">Tanggal Pesan</span>
                            <span class="text-sm font-bold text-slate-800">{{ $order->order_date->format('d F Y') }}</span>
                        </div>
                         <div class="flex justify-between">
                            <span class="text-xs font-medium text-slate-500 uppercase">Waktu Input</span>
                            <span class="text-xs text-slate-400 font-mono">{{ $order->created_at->format('H:i') }}</span>
                        </div>
                    </div>
                </div>
            </div>

            {{-- CARD AKSI REVIEW --}}
            <div class="dashboard-card p-6 shadow-xl sticky top-6 border-t-4 {{ $order->status == 'pending_review' ? 'border-amber-400' : ($order->status == 'rejected' ? 'border-red-500' : 'border-emerald-500') }}">
                <h3 class="card-title mb-4 border-b border-slate-100 pb-3 flex items-center gap-2">
                    <i class="material-icons text-indigo-600">gavel</i> Status Review
                </h3>

                @if($order->status == 'pending_review')
                    <div class="space-y-3">
                        <p class="text-sm text-slate-600 mb-4 leading-relaxed">Pesanan ini menunggu persetujuan Anda. Jika disetujui, Draft Invoice akan otomatis dibuat.</p>
                        
                        <a href="{{ route('admin.invoices.createFromOrder', $order->order_id) }}" 
                           class="w-full h-[48px] bg-emerald-600 hover:bg-emerald-700 text-white text-sm font-bold rounded-lg shadow-md transition-all flex justify-center items-center gap-2 hover:-translate-y-0.5">
                            <i class="material-icons text-[20px]">check_circle</i> Setujui & Buat Invoice
                        </a>
                    </div>
                @elseif($order->status == 'invoiced' || $order->status == 'approved')
                    <div class="text-center py-6">
                        <div class="w-16 h-16 bg-emerald-50 rounded-full flex items-center justify-center mx-auto mb-3 text-emerald-600">
                            <i class="material-icons text-4xl">check_circle</i>
                        </div>
                        <h4 class="text-slate-800 font-bold text-lg">Pesanan Disetujui</h4>
                        <p class="text-xs text-slate-500 mb-4 mt-1">Invoice telah dibuat untuk pesanan ini.</p>
                        
                        @if($order->invoice_id)
                        <a href="{{ route('admin.invoices.show', $order->invoice_id) }}" class="inline-flex items-center px-4 py-2 bg-indigo-600 text-white rounded-lg text-sm font-bold hover:bg-indigo-700 transition shadow-sm">
                            Lihat Invoice <i class="material-icons text-sm ml-2">arrow_forward</i>
                        </a>
                        @endif
                    </div>
                @elseif($order->status == 'rejected')
                    <div class="text-center py-6">
                        <div class="w-16 h-16 bg-red-50 rounded-full flex items-center justify-center mx-auto mb-3 text-red-600">
                            <i class="material-icons text-4xl">cancel</i>
                        </div>
                        <h4 class="text-slate-800 font-bold text-lg">Pesanan Ditolak</h4>
                        
                        @if($order->rejection_reason)
                            <div class="mt-4 p-3 bg-red-50 border border-red-100 rounded-lg text-left">
                                <span class="block text-[10px] font-bold text-red-400 uppercase mb-1">Alasan Penolakan:</span>
                                <p class="text-sm text-red-800 italic">"{{ $order->rejection_reason }}"</p>
                            </div>
                        @endif
                    </div>
                @endif

            </div>

        </div>

    </div>
</div>

{{-- MODAL TOLAK (Clean UI) --}}
<div id="rejectModal" class="fixed inset-0 z-[100] hidden overflow-y-auto" aria-labelledby="modal-title" role="dialog" aria-modal="true">
    {{-- Backdrop --}}
    <div class="fixed inset-0 bg-slate-900/50 backdrop-blur-sm transition-opacity" onclick="document.getElementById('rejectModal').classList.add('hidden')"></div>
    
    <div class="flex min-h-full items-center justify-center p-4 text-center">
        <div class="relative transform overflow-hidden rounded-xl bg-white text-left shadow-2xl transition-all sm:my-8 sm:w-full sm:max-w-lg border border-slate-100">
            
            <div class="bg-white px-4 pb-4 pt-5 sm:p-6 sm:pb-4">
                <div class="sm:flex sm:items-start">
                    <div class="mx-auto flex h-12 w-12 flex-shrink-0 items-center justify-center rounded-full bg-red-100 sm:mx-0 sm:h-10 sm:w-10">
                        <i class="material-icons text-red-600 text-xl">warning_amber</i>
                    </div>
                    <div class="mt-3 text-center sm:ml-4 sm:mt-0 sm:text-left w-full">
                        <h3 class="text-lg font-bold leading-6 text-slate-900" id="modal-title">Tolak Pesanan?</h3>
                        <div class="mt-2">
                            <p class="text-sm text-slate-500">
                                Anda yakin ingin menolak pesanan <b>{{ $order->order_number }}</b>? Tindakan ini akan mengubah status menjadi ditolak.
                            </p>
                            
                            <form action="{{ route('admin.client-order-reviews.reject', $order->order_id) }}" method="POST" class="mt-4">
                                @csrf
                                <label for="rejection_notes" class="block text-xs font-bold text-slate-500 uppercase mb-1.5">Alasan Penolakan (Opsional)</label>
                                <textarea name="rejection_notes" id="rejection_notes" rows="3" class="form-textarea w-full text-sm" placeholder="Contoh: Stok habis, harga berubah..."></textarea>
                                
                                <div class="mt-5 sm:flex sm:flex-row-reverse gap-2">
                                    <button type="submit" class="inline-flex w-full justify-center rounded-lg bg-red-600 px-4 py-2.5 text-sm font-bold text-white shadow-sm hover:bg-red-700 sm:w-auto">Ya, Tolak</button>
                                    <button type="button" class="mt-3 inline-flex w-full justify-center rounded-lg bg-white px-4 py-2.5 text-sm font-bold text-slate-700 shadow-sm ring-1 ring-inset ring-slate-300 hover:bg-slate-50 sm:mt-0 sm:w-auto" onclick="document.getElementById('rejectModal').classList.add('hidden')">Batal</button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
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
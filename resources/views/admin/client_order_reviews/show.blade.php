@extends('admin.layouts.app')

@section('title', 'Detail Pesanan Klien #' . $order->order_number)

@section('content')
    {{-- Bungkus dengan x-data untuk modal penolakan --}}
    <div class="flex flex-col gap-6" x-data="{ showRejectModal: false, rejectionReason: '' }">
        
        {{-- Header & Nav --}}
        <div class="flex items-center gap-4">
            {{-- Tombol Back Simetris --}}
            <a href="{{ route('admin.client-order-reviews.index') }}" 
               class="w-9 h-9 flex items-center justify-center rounded-lg bg-white border border-slate-200 text-slate-500 hover:bg-slate-100 hover:text-indigo-600 transition-all dark:bg-slate-800 dark:border-slate-700 dark:text-slate-400 dark:hover:text-white shadow-sm">
                <i class="material-icons text-xl leading-none">arrow_back</i>
            </a>

            <div>
                <div class="flex items-center gap-3">
                    <h1 class="page-title text-xl">Review Pesanan <span class="text-indigo-600">#{{ $order->order_number }}</span></h1>
                    
                    @if($order->status == 'pending_review')
                        <span class="badge badge-warning animate-pulse-slow">Menunggu Review</span>
                    @elseif($order->status == 'invoiced')
                        <span class="badge badge-success">Disetujui</span>
                    @elseif($order->status == 'rejected')
                        <span class="badge badge-danger">Ditolak</span>
                    @endif
                </div>
                <p class="text-sm text-slate-500 mt-1 flex items-center gap-2">
                    <i class="material-icons text-sm">event</i> {{ $order->created_at->format('d F Y, H:i') }}
                </p>
            </div>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
            
            {{-- Left Column: Order Items --}}
            <div class="lg:col-span-2 flex flex-col gap-6">
                <div class="card overflow-hidden">
                    <div class="card-header bg-slate-50 dark:bg-slate-800 border-b border-slate-200 dark:border-slate-700">
                        <h3 class="font-bold text-slate-700 dark:text-white flex items-center gap-2">
                            <i class="material-icons text-slate-400">shopping_cart</i> Detail Barang
                        </h3>
                    </div>
                    <div class="table-container border-0 shadow-none rounded-none">
                        <table class="table-modern">
                            <thead>
                                <tr>
                                    <th>Produk</th>
                                    <th class="text-right">Harga Satuan</th>
                                    <th class="text-center">Qty</th>
                                    <th class="text-right">Subtotal</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($order->items as $item)
                                    <tr>
                                        <td>
                                            <div class="font-bold text-slate-700 dark:text-slate-200">
                                                {{ $item->product->product_name }}
                                            </div>
                                            <div class="text-xs text-slate-500 font-mono">
                                                {{ $item->product->product_code ?? '-' }}
                                            </div>
                                            
                                            {{-- Indikator Stok: Muncul jika stok kurang --}}
                                            @if($item->quantity > $item->product->stock_quantity && $order->status == 'pending_review')
                                                <div class="flex items-center gap-1 text-[10px] text-rose-500 font-bold mt-1 bg-rose-50 px-2 py-0.5 rounded w-fit border border-rose-100">
                                                    <i class="material-icons text-[10px]">warning</i>
                                                    Stok Kurang (Sisa: {{ number_format($item->product->stock_quantity, 0, ',', '.') }})
                                                </div>
                                            @endif
                                        </td>
                                        <td class="text-right">
                                            Rp {{ number_format($item->price_per_unit, 0, ',', '.') }}
                                        </td>
                                        <td class="text-center">
                                            <span class="px-2 py-1 bg-slate-100 dark:bg-slate-700 rounded text-xs font-bold">
                                                {{ number_format($item->quantity, 0, ',', '.') }} {{ $item->product->unit->name ?? 'Unit' }}
                                            </span>
                                        </td>
                                        <td class="text-right font-bold text-slate-700 dark:text-white">
                                            Rp {{ number_format($item->subtotal, 0, ',', '.') }}
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                            <tfoot class="bg-slate-50 dark:bg-slate-800/50 border-t border-slate-200 dark:border-slate-700">
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

                @if($order->notes)
                    <div class="card bg-amber-50 border-amber-100 dark:bg-amber-900/20 dark:border-amber-800">
                        <div class="card-body">
                            <h4 class="text-xs font-bold text-amber-600 dark:text-amber-500 uppercase mb-2 flex items-center gap-2">
                                <i class="material-icons text-sm">sticky_note_2</i> Catatan Klien
                            </h4>
                            <p class="text-sm text-slate-700 dark:text-slate-300 leading-relaxed">{{ $order->notes }}</p>
                        </div>
                    </div>
                @endif
            </div>

            {{-- Right Column: Client & Actions --}}
            <div class="flex flex-col gap-6">
                
                {{-- Client Info --}}
                <div class="card">
                    <div class="card-header border-b border-slate-100 dark:border-slate-700/50 pb-3">
                        <h3 class="font-bold text-slate-700 dark:text-white text-sm">Informasi Klien</h3>
                    </div>
                    <div class="card-body space-y-4">
                        <div class="flex items-center gap-3">
                            <div class="h-10 w-10 rounded-full bg-slate-100 dark:bg-slate-700 flex items-center justify-center text-slate-500">
                                <i class="material-icons">person</i>
                            </div>
                            <div>
                                <div class="text-xs text-slate-400 uppercase font-bold">Nama Klien</div>
                                <div class="font-bold text-slate-700 dark:text-white">{{ $order->client->client_name }}</div>
                            </div>
                        </div>
                        <hr class="border-slate-100 dark:border-slate-700">
                        <div class="grid grid-cols-1 gap-3 text-sm">
                            <div>
                                <div class="text-xs text-slate-400 mb-1">Email</div>
                                <div class="text-slate-700 dark:text-slate-300">{{ $order->client->email ?? '-' }}</div>
                            </div>
                            <div>
                                <div class="text-xs text-slate-400 mb-1">Kontak</div>
                                @if($order->client->phone_number)
                                    <a href="https://wa.me/{{ preg_replace('/^0/', '62', preg_replace('/[^0-9]/', '', $order->client->phone_number)) }}" target="_blank" class="text-emerald-600 hover:underline flex items-center gap-1 font-medium">
                                        {{ $order->client->phone_number }} <i class="material-icons text-[10px]">open_in_new</i>
                                    </a>
                                @else
                                    <span class="text-slate-500">-</span>
                                @endif
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Action Card --}}
                <div class="card shadow-lg border-t-4 {{ $order->status == 'pending_review' ? 'border-t-indigo-500' : 'border-t-transparent' }}">
                    <div class="card-header">
                        <h3 class="card-header-title">Tindakan</h3>
                    </div>
                    <div class="card-body">
                        @if($order->status == 'pending_review')
                            <div class="flex flex-col gap-3">
                                <p class="text-xs text-slate-500 dark:text-slate-400 mb-2 leading-relaxed text-center">
                                    Silakan verifikasi stok dan harga sebelum menyetujui. Invoice akan otomatis dibuat.
                                </p>

                                {{-- Approve Form --}}
                                <form action="{{ route('admin.client-order-reviews.approve', $order->order_id) }}" method="POST" onsubmit="return confirm('Yakin setujui pesanan ini? Stok akan dikurangi dan Invoice akan terbit.')">
                                    @csrf
                                    <button type="submit" class="btn btn-primary w-full justify-center shadow-lg shadow-indigo-500/20">
                                        <i class="material-icons text-sm mr-2">check_circle</i>
                                        Setujui & Buat Invoice
                                    </button>
                                </form>
                                
                                {{-- Reject Button (Trigger Modal) --}}
                                <button type="button" @click="showRejectModal = true" class="btn btn-danger-solid w-full justify-center bg-white border-rose-200 text-rose-600 hover:bg-rose-50 hover:text-rose-700 shadow-none">
                                    <i class="material-icons text-sm mr-2">cancel</i>
                                    Tolak Pesanan
                                </button>
                            </div>
                        @else
                            {{-- Info Status --}}
                            <div class="text-center py-4">
                                @if($order->status == 'invoiced')
                                    <div class="w-12 h-12 bg-emerald-100 text-emerald-600 rounded-full flex items-center justify-center mx-auto mb-3">
                                        <i class="material-icons text-2xl">check</i>
                                    </div>
                                    <h4 class="font-bold text-emerald-700">Pesanan Disetujui</h4>
                                    @if($order->invoice_id)
                                        <a href="{{ route('admin.invoices.show', $order->invoice_id) }}" class="btn btn-sm btn-secondary mt-3">
                                            Lihat Invoice <i class="material-icons text-sm ml-1">arrow_forward</i>
                                        </a>
                                    @endif
                                @elseif($order->status == 'rejected')
                                    <div class="w-12 h-12 bg-rose-100 text-rose-600 rounded-full flex items-center justify-center mx-auto mb-3">
                                        <i class="material-icons text-2xl">block</i>
                                    </div>
                                    <h4 class="font-bold text-rose-700">Pesanan Ditolak</h4>
                                    <div class="text-xs text-rose-600 mt-3 bg-rose-50 p-3 rounded-lg border border-rose-100 text-left italic">
                                        "{{ $order->notes ?? 'Tidak ada alasan.' }}"
                                    </div>
                                @endif
                            </div>
                        @endif
                    </div>
                </div>

            </div>
        </div>

        {{-- =========================================================
             MODAL PENOLAKAN (ALPINE JS)
             ========================================================= --}}
        <div x-show="showRejectModal" 
             style="display: none;" 
             class="fixed inset-0 z-[60] flex items-center justify-center bg-slate-900/50 backdrop-blur-sm p-4"
             x-transition:enter="transition ease-out duration-200"
             x-transition:enter-start="opacity-0"
             x-transition:enter-end="opacity-100"
             x-transition:leave="transition ease-in duration-150"
             x-transition:leave-start="opacity-100"
             x-transition:leave-end="opacity-0">
            
            <div class="bg-white dark:bg-slate-800 rounded-2xl shadow-2xl w-full max-w-md p-6 overflow-hidden transform transition-all"
                 @click.away="showRejectModal = false">
                
                <div class="text-center mb-6">
                    <div class="w-12 h-12 bg-rose-100 text-rose-600 rounded-full flex items-center justify-center mx-auto mb-3">
                        <i class="material-icons text-2xl">warning</i>
                    </div>
                    <h3 class="text-lg font-bold text-slate-800 dark:text-white">Tolak Pesanan?</h3>
                    <p class="text-sm text-slate-500 dark:text-slate-400 mt-1">
                        Pesanan ini akan dibatalkan. Klien akan menerima notifikasi.
                    </p>
                </div>

                <form action="{{ route('admin.client-order-reviews.reject', $order->order_id) }}" method="POST">
                    @csrf
                    
                    <div class="mb-5">
                        <label class="form-label text-left block mb-1">Alasan Penolakan <span class="text-red-500">*</span></label>
                        <textarea name="rejection_notes" 
                                  x-model="rejectionReason"
                                  rows="3" 
                                  class="form-textarea w-full" 
                                  placeholder="Contoh: Stok habis, harga berubah, dll..."
                                  required></textarea>
                    </div>

                    <div class="flex gap-3 justify-end">
                        <button type="button" @click="showRejectModal = false" class="btn btn-secondary w-full justify-center">
                            Batal
                        </button>
                        <button type="submit" class="btn btn-danger w-full justify-center shadow-lg shadow-rose-500/20" :disabled="!rejectionReason">
                            Konfirmasi Tolak
                        </button>
                    </div>
                </form>
            </div>
        </div>

    </div>
@endsection
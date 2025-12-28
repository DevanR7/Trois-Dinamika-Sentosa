@extends('admin.layouts.app')

@section('title', 'Detail Pesanan Klien')

@section('content')
    {{-- Bungkus dengan x-data untuk modal penolakan --}}
    <div class="flex flex-col gap-6" x-data="{ showRejectModal: false, rejectionReason: '' }">
        
        {{-- Header & Nav --}}
        <div class="page-header">
            <div class="flex items-center gap-4">
                <a href="{{ route('admin.client-order-reviews.index') }}" class="btn btn-secondary btn-icon">
                    <i class="material-icons text-sm">arrow_back</i>
                </a>
                <div>
                    <div class="flex items-center gap-3">
                        <h1 class="page-title">{{ $order->order_number }}</h1>
                        @if($order->status == 'pending_review')
                            <span class="badge badge-warning">Menunggu Review</span>
                        @elseif($order->status == 'invoiced')
                            <span class="badge badge-success">Disetujui</span>
                        @elseif($order->status == 'rejected')
                            <span class="badge badge-danger">Ditolak</span>
                        @endif
                    </div>
                    <p class="page-subtitle">Dibuat pada {{ $order->created_at->format('d F Y, H:i') }}</p>
                </div>
            </div>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
            
            {{-- Left Column: Order Items --}}
            <div class="lg:col-span-2 flex flex-col gap-6">
                <div class="card">
                    <div class="card-header">
                        <h3 class="card-header-title">Detail Produk Pesanan</h3>
                    </div>
                    <div class="table-container">
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
                                            <div class="font-medium text-slate-800 dark:text-white">
                                                {{ $item->product->product_name }}
                                            </div>
                                            <div class="text-xs text-muted">{{ $item->product->product_code }}</div>
                                            
                                            {{-- Indikator Stok (Optional UX improvement) --}}
                                            @if($item->quantity > $item->product->stock_quantity && $order->status == 'pending_review')
                                                <div class="text-[10px] text-rose-500 font-bold mt-1">
                                                    <i class="material-icons text-[10px] align-middle">warning</i>
                                                    Stok Kurang (Tersedia: {{ $item->product->stock_quantity }})
                                                </div>
                                            @endif
                                        </td>
                                        <td class="text-right">
                                            Rp {{ number_format($item->price_per_unit, 0, ',', '.') }}
                                        </td>
                                        <td class="text-center">
                                            {{ number_format($item->quantity, 0, ',', '.') }} {{ $item->product->unit->name ?? 'Unit' }}
                                        </td>
                                        <td class="text-right font-bold text-slate-700 dark:text-slate-200">
                                            Rp {{ number_format($item->subtotal, 0, ',', '.') }}
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                            <tfoot>
                                <tr class="bg-slate-50 dark:bg-slate-800/50 font-bold">
                                    <td colspan="3" class="text-right py-4">TOTAL PESANAN</td>
                                    <td class="text-right py-4 text-lg text-indigo-600 dark:text-indigo-400">
                                        Rp {{ number_format($order->total_amount, 0, ',', '.') }}
                                    </td>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                </div>

                @if($order->notes)
                    <div class="card bg-amber-50 border-amber-100 dark:bg-amber-900/20 dark:border-amber-900/30">
                        <div class="card-body">
                            <h4 class="text-xs font-bold text-amber-800 dark:text-amber-500 uppercase mb-2">Catatan Klien</h4>
                            <p class="text-sm text-amber-900 dark:text-amber-100">{{ $order->notes }}</p>
                        </div>
                    </div>
                @endif
            </div>

            {{-- Right Column: Actions & Info --}}
            <div class="flex flex-col gap-6">
                
                {{-- Client Info --}}
                <div class="card">
                    <div class="card-header">
                        <h3 class="card-header-title">Informasi Klien</h3>
                    </div>
                    <div class="card-body space-y-4">
                        <div class="flex items-center gap-3">
                            <div class="h-10 w-10 rounded-full bg-slate-100 dark:bg-slate-700 flex items-center justify-center text-slate-500">
                                <i class="material-icons">business</i>
                            </div>
                            <div>
                                <div class="text-xs text-muted uppercase">Nama Perusahaan</div>
                                <div class="font-bold text-slate-700 dark:text-white">{{ $order->client->client_name }}</div>
                            </div>
                        </div>
                        <hr class="border-slate-100 dark:border-slate-700">
                        <div>
                            <div class="text-xs text-muted uppercase mb-1">PIC</div>
                            <div class="text-sm text-slate-700 dark:text-slate-300">{{ $order->client->person_in_charge ?? '-' }}</div>
                        </div>
                        <div>
                            <div class="text-xs text-muted uppercase mb-1">Kontak</div>
                            <div class="text-sm text-slate-700 dark:text-slate-300">{{ $order->client->phone_number ?? '-' }}</div>
                        </div>
                        <div>
                            <div class="text-xs text-muted uppercase mb-1">Alamat</div>
                            <div class="text-sm text-slate-700 dark:text-slate-300">{{ $order->client->address ?? '-' }}</div>
                        </div>
                    </div>
                </div>

                {{-- Action Card --}}
                <div class="card">
                    <div class="card-header">
                        <h3 class="card-header-title">Tindakan</h3>
                    </div>
                    <div class="card-body">
                        @if($order->status == 'pending_review')
                            <div class="flex flex-col gap-3">
                                <p class="text-sm text-slate-500 dark:text-slate-400 mb-2">
                                    Silakan verifikasi pesanan ini. Jika disetujui, Invoice akan otomatis dibuat.
                                </p>

                                {{-- Approve Form --}}
                                <form action="{{ route('admin.client-order-reviews.approve', $order->order_id) }}" method="POST" onsubmit="return confirm('Yakin setujui pesanan ini? Invoice akan dibuat otomatis.')">
                                    @csrf
                                    <button type="submit" class="btn btn-primary w-full justify-center">
                                        <i class="material-icons text-sm mr-2">check_circle</i>
                                        Setujui & Buat Invoice
                                    </button>
                                </form>
                                
                                {{-- Reject Button (Trigger Modal) --}}
                                <button type="button" @click="showRejectModal = true" class="btn btn-danger w-full justify-center">
                                    <i class="material-icons text-sm mr-2">cancel</i>
                                    Tolak Pesanan
                                </button>
                            </div>
                        @else
                            {{-- Info jika sudah diproses --}}
                            <div class="text-center py-4">
                                @if($order->status == 'invoiced')
                                    <div class="w-12 h-12 bg-emerald-100 text-emerald-600 rounded-full flex items-center justify-center mx-auto mb-3">
                                        <i class="material-icons text-2xl">check</i>
                                    </div>
                                    <h4 class="font-bold text-emerald-700">Pesanan Disetujui</h4>
                                    @if($order->invoice_id)
                                        <a href="{{ route('admin.invoices.show', $order->invoice_id) }}" class="btn btn-sm btn-secondary mt-3">
                                            Lihat Invoice
                                        </a>
                                    @endif
                                @elseif($order->status == 'rejected')
                                    <div class="w-12 h-12 bg-rose-100 text-rose-600 rounded-full flex items-center justify-center mx-auto mb-3">
                                        <i class="material-icons text-2xl">block</i>
                                    </div>
                                    <h4 class="font-bold text-rose-700">Pesanan Ditolak</h4>
                                    <p class="text-xs text-rose-600 mt-2 bg-rose-50 p-2 rounded border border-rose-100">
                                        "{{ $order->notes ?? 'Tidak ada alasan.' }}"
                                    </p>
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
             class="fixed inset-0 z-[60] flex items-center justify-center bg-slate-900/50 backdrop-blur-sm"
             x-transition:enter="transition ease-out duration-200"
             x-transition:enter-start="opacity-0"
             x-transition:enter-end="opacity-100"
             x-transition:leave="transition ease-in duration-150"
             x-transition:leave-start="opacity-100"
             x-transition:leave-end="opacity-0">
            
            <div class="bg-white dark:bg-slate-800 rounded-xl shadow-2xl w-full max-w-md p-6 m-4"
                 @click.away="showRejectModal = false">
                
                <div class="text-center mb-6">
                    <div class="w-12 h-12 bg-rose-100 text-rose-600 rounded-full flex items-center justify-center mx-auto mb-3">
                        <i class="material-icons">warning</i>
                    </div>
                    <h3 class="text-lg font-bold text-slate-800 dark:text-white">Tolak Pesanan?</h3>
                    <p class="text-sm text-slate-500 dark:text-slate-400">
                        Stok yang tertahan akan dikembalikan. Mohon berikan alasan penolakan.
                    </p>
                </div>

                <form action="{{ route('admin.client-order-reviews.reject', $order->order_id) }}" method="POST">
                    @csrf
                    
                    <div class="mb-4">
                        <label class="form-label text-left block mb-1">Alasan Penolakan <span class="text-red-500">*</span></label>
                        <textarea name="rejection_notes" 
                                  x-model="rejectionReason"
                                  rows="3" 
                                  class="form-textarea w-full" 
                                  placeholder="Contoh: Stok barang tidak cukup, harga berubah, dll..."
                                  required></textarea>
                    </div>

                    <div class="flex gap-3 justify-center">
                        <button type="button" @click="showRejectModal = false" class="btn btn-secondary">
                            Batal
                        </button>
                        <button type="submit" class="btn btn-danger" :disabled="!rejectionReason">
                            Konfirmasi Tolak
                        </button>
                    </div>
                </form>
            </div>
        </div>

    </div>
@endsection
@extends('admin.layouts.app')

@section('title', 'Detail Permintaan #' . $changeRequest->request_id)

@section('content')
<div class="flex flex-col gap-6" x-data="{ showRejectModal: false, rejectionReason: '' }">

    {{-- HEADER SECTION --}}
    <div class="flex items-center gap-4">
        {{-- Button Back Simetris --}}
        <a href="{{ route('admin.order-change-requests.index') }}" 
           class="w-9 h-9 flex items-center justify-center rounded-lg bg-white border border-slate-200 text-slate-500 hover:bg-slate-100 hover:text-indigo-600 transition-all dark:bg-slate-800 dark:border-slate-700 dark:text-slate-400 dark:hover:text-white shadow-sm">
            <i class="material-icons text-xl leading-none">arrow_back</i>
        </a>
        
        <div>
            <div class="flex items-center gap-3">
                <h1 class="page-title text-xl">Permintaan <span class="text-indigo-600">#{{ $changeRequest->request_id }}</span></h1>
                
                @if($changeRequest->status == 'pending')
                    <span class="badge badge-warning animate-pulse-slow">Menunggu Review</span>
                @elseif($changeRequest->status == 'approved')
                    <span class="badge badge-success">Disetujui</span>
                @else
                    <span class="badge badge-danger">Ditolak</span>
                @endif
            </div>
            <p class="text-sm text-slate-500 mt-1 flex items-center gap-2">
                <span class="font-medium text-slate-700 dark:text-slate-300">
                    {{ $changeRequest->request_type == 'cancel' ? 'Pembatalan Pesanan' : 'Modifikasi Item' }}
                </span>
                <span class="text-slate-300">|</span>
                <i class="material-icons text-xs">event</i> {{ $changeRequest->created_at->format('d F Y, H:i') }}
            </p>
        </div>
    </div>

    {{-- GRID LAYOUT --}}
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">

        {{-- LEFT COLUMN: DETAIL ISI PERMINTAAN --}}
        <div class="lg:col-span-2 flex flex-col gap-6">

            {{-- 1. Alasan Klien --}}
            <div class="card bg-slate-50 dark:bg-slate-800/50 border border-slate-200 dark:border-slate-700">
                <div class="card-body">
                    <h4 class="text-xs font-bold text-slate-500 uppercase mb-2 flex items-center gap-2">
                        <i class="material-icons text-sm">sticky_note_2</i> Catatan Klien
                    </h4>
                    <p class="text-sm text-slate-700 dark:text-slate-300 italic leading-relaxed">
                        "{{ $changeRequest->client_notes ?? 'Tidak ada catatan khusus.' }}"
                    </p>
                </div>
            </div>

            {{-- 2. Detail Modifikasi (Jika Tipe Modify) --}}
            @if($changeRequest->request_type == 'modify')
                <div class="card overflow-hidden">
                    <div class="card-header bg-white dark:bg-slate-800 border-b border-slate-100 dark:border-slate-700/50">
                        <h3 class="card-header-title flex items-center gap-2">
                            <i class="material-icons text-slate-400">list_alt</i> Rincian Perubahan Item
                        </h3>
                    </div>
                    <div class="table-container border-0 shadow-none rounded-none">
                        <table class="table-modern">
                            <thead>
                                <tr>
                                    <th>Produk</th>
                                    <th>Aksi</th>
                                    <th class="text-center">Perubahan Qty</th>
                                    <th class="text-right">Estimasi Subtotal</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($changeRequest->items as $item)
                                    <tr>
                                        <td>
                                            <div class="font-bold text-slate-700 dark:text-slate-200">
                                                {{ $item->product->product_name }}
                                            </div>
                                            <div class="text-xs text-slate-500 font-mono">
                                                {{ $item->product->product_code ?? '-' }}
                                            </div>
                                        </td>
                                        <td>
                                            @if($item->action == 'add')
                                                <span class="badge badge-success">Tambah Baru</span>
                                            @elseif($item->action == 'remove')
                                                <span class="badge badge-danger">Hapus Item</span>
                                            @elseif($item->action == 'update_qty')
                                                <span class="badge badge-info">Ubah Qty</span>
                                            @endif
                                        </td>
                                        <td class="text-center">
                                            <div class="flex items-center justify-center gap-3 text-sm font-mono">
                                                @if($item->action == 'add')
                                                    <span class="text-slate-400">-</span>
                                                    <i class="material-icons text-xs text-emerald-500">arrow_forward</i>
                                                    <span class="font-bold text-emerald-600">{{ number_format($item->requested_quantity, 0, ',', '.') }}</span>
                                                @elseif($item->action == 'remove')
                                                    <span class="font-bold text-slate-600">{{ number_format($item->original_quantity, 0, ',', '.') }}</span>
                                                    <i class="material-icons text-xs text-rose-500">arrow_forward</i>
                                                    <span class="text-rose-600 font-bold">0</span>
                                                @else
                                                    <span class="text-slate-500">{{ number_format($item->original_quantity, 0, ',', '.') }}</span>
                                                    <i class="material-icons text-xs text-indigo-500">arrow_forward</i>
                                                    <span class="font-bold text-indigo-600">{{ number_format($item->requested_quantity, 0, ',', '.') }}</span>
                                                @endif
                                            </div>
                                        </td>
                                        <td class="text-right font-bold text-slate-700 dark:text-white">
                                            Rp {{ number_format($item->subtotal, 0, ',', '.') }}
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>

            {{-- 3. Detail Pembatalan (Jika Tipe Cancel) --}}
            @else
                <div class="card p-8 text-center border-l-4 border-l-rose-500 bg-white dark:bg-slate-800">
                    <div class="w-16 h-16 bg-rose-50 text-rose-600 rounded-full flex items-center justify-center mx-auto mb-4">
                        <i class="material-icons text-3xl">cancel</i>
                    </div>
                    <h3 class="text-xl font-bold text-slate-800 dark:text-white mb-2">Permintaan Pembatalan Total</h3>
                    <p class="text-slate-500 dark:text-slate-400 text-sm max-w-md mx-auto leading-relaxed">
                        Klien meminta untuk membatalkan seluruh pesanan <strong>{{ $changeRequest->order->order_number }}</strong>. <br>
                        Jika disetujui, stok barang akan dikembalikan (jika sudah terpotong) dan status pesanan menjadi <strong>Rejected</strong>.
                    </p>
                </div>
            @endif

        </div>

        {{-- RIGHT COLUMN: INFO & ACTIONS --}}
        <div class="flex flex-col gap-6">

            {{-- Info Pesanan Asal --}}
            <div class="card">
                <div class="card-header border-b border-slate-100 dark:border-slate-700/50 pb-3">
                    <h3 class="font-bold text-slate-700 dark:text-white text-sm">Informasi Pesanan Asal</h3>
                </div>
                <div class="card-body space-y-4">
                    <div class="flex justify-between items-center text-sm">
                        <span class="text-slate-500">No. Order</span>
                        <a href="{{ route('admin.sales-orders.show', $changeRequest->order_id) }}" class="font-bold text-indigo-600 hover:underline font-mono">
                            {{ $changeRequest->order->order_number }}
                        </a>
                    </div>
                    <div class="flex justify-between items-center text-sm">
                        <span class="text-slate-500">Tanggal Order</span>
                        <span class="font-medium text-slate-700 dark:text-slate-300">
                            {{ $changeRequest->order->order_date->format('d M Y') }}
                        </span>
                    </div>
                    <div class="flex justify-between items-center text-sm">
                        <span class="text-slate-500">Total Nilai</span>
                        <span class="font-bold text-slate-800 dark:text-white">
                            Rp {{ number_format($changeRequest->order->total_amount, 0, ',', '.') }}
                        </span>
                    </div>
                    
                    <hr class="border-slate-100 dark:border-slate-700">

                    <div class="flex items-center gap-3 pt-1">
                        <div class="h-8 w-8 rounded bg-slate-100 dark:bg-slate-700 flex items-center justify-center text-slate-500">
                            <i class="material-icons text-sm">person</i>
                        </div>
                        <div>
                            <p class="text-xs text-slate-400 uppercase font-bold">Klien</p>
                            <p class="text-sm font-bold text-slate-700 dark:text-white truncate max-w-[150px]">
                                {{ $changeRequest->client->client_name }}
                            </p>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Action Card --}}
            <div class="card shadow-lg border-t-4 {{ $changeRequest->status == 'pending' ? 'border-t-indigo-500' : 'border-t-transparent' }}">
                <div class="card-header">
                    <h3 class="card-header-title">Tindakan Admin</h3>
                </div>
                <div class="card-body">
                    @if($changeRequest->status == 'pending')
                        <div class="flex flex-col gap-3">
                            <p class="text-xs text-slate-500 dark:text-slate-400 mb-2 text-center leading-relaxed">
                                Apakah Anda menyetujui permintaan ini?
                                <br>Perubahan akan diterapkan otomatis ke pesanan.
                            </p>

                            {{-- Form Approve --}}
                            <form action="{{ route('admin.order-change-requests.process', $changeRequest->request_id) }}" method="POST" onsubmit="return confirm('Yakin setujui permintaan ini?');">
                                @csrf
                                <input type="hidden" name="action" value="approve">
                                <button type="submit" class="btn btn-primary w-full justify-center shadow-md">
                                    <i class="material-icons text-sm mr-2">check_circle</i>
                                    Setujui Permintaan
                                </button>
                            </form>
                            
                            {{-- Button Reject (Modal) --}}
                            <button type="button" @click="showRejectModal = true" class="btn btn-danger-solid w-full justify-center bg-white border border-rose-200 text-rose-600 hover:bg-rose-50 hover:text-rose-700 shadow-none">
                                <i class="material-icons text-sm mr-2">cancel</i>
                                Tolak Permintaan
                            </button>
                        </div>
                    @else
                        {{-- Status Info Box --}}
                        <div class="text-center py-4">
                            @if($changeRequest->status == 'approved')
                                <div class="w-12 h-12 bg-emerald-100 text-emerald-600 rounded-full flex items-center justify-center mx-auto mb-3">
                                    <i class="material-icons text-2xl">check</i>
                                </div>
                                <h4 class="font-bold text-emerald-700">Permintaan Disetujui</h4>
                            @else
                                <div class="w-12 h-12 bg-rose-100 text-rose-600 rounded-full flex items-center justify-center mx-auto mb-3">
                                    <i class="material-icons text-2xl">block</i>
                                </div>
                                <h4 class="font-bold text-rose-700">Permintaan Ditolak</h4>
                            @endif

                            <div class="text-xs text-slate-400 mt-2">
                                Oleh: <span class="font-medium text-slate-600 dark:text-slate-300">{{ $changeRequest->processor->full_name ?? 'System' }}</span>
                                <br>
                                {{ $changeRequest->processed_at ? $changeRequest->processed_at->format('d M Y, H:i') : '' }}
                            </div>

                            @if($changeRequest->admin_notes)
                                <div class="mt-4 text-xs text-slate-600 italic bg-slate-50 p-3 rounded border border-slate-100 text-left">
                                    "{{ $changeRequest->admin_notes }}"
                                </div>
                            @endif
                        </div>
                    @endif
                </div>
            </div>

        </div>

    </div>

    {{-- MODAL PENOLAKAN (Alpine JS) --}}
    <div x-show="showRejectModal" 
         style="display: none;" 
         class="fixed inset-0 z-[60] flex items-center justify-center bg-slate-900/50 backdrop-blur-sm p-4"
         x-transition.opacity>
        
        <div class="bg-white dark:bg-slate-800 rounded-2xl shadow-2xl w-full max-w-md p-6 overflow-hidden transform transition-all"
             @click.away="showRejectModal = false">
            
            <div class="text-center mb-6">
                <div class="w-12 h-12 bg-rose-100 text-rose-600 rounded-full flex items-center justify-center mx-auto mb-3">
                    <i class="material-icons text-2xl">warning</i>
                </div>
                <h3 class="text-lg font-bold text-slate-800 dark:text-white">Tolak Permintaan?</h3>
                <p class="text-sm text-slate-500 dark:text-slate-400 mt-1">
                    Permintaan klien akan ditolak dan pesanan akan tetap seperti semula.
                </p>
            </div>

            <form action="{{ route('admin.order-change-requests.process', $changeRequest->request_id) }}" method="POST">
                @csrf
                <input type="hidden" name="action" value="reject">
                
                <div class="mb-5">
                    <label class="form-label text-left block mb-1">Alasan Penolakan <span class="text-red-500">*</span></label>
                    <textarea name="admin_notes" 
                              x-model="rejectionReason"
                              rows="3" 
                              class="form-textarea w-full" 
                              placeholder="Contoh: Stok pengganti tidak tersedia..."
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
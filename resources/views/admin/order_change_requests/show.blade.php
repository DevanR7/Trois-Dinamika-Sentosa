@extends('admin.layouts.app')

@section('title', 'Detail Permintaan Perubahan')

@section('content')
    {{-- Kita bungkus konten dengan x-data untuk mengelola Modal Penolakan --}}
    <div class="flex flex-col gap-6" x-data="{ showRejectModal: false, rejectionReason: '' }">
        
        {{-- Header & Nav --}}
        <div class="page-header">
            <div class="flex items-center gap-4">
                <a href="{{ route('admin.order-change-requests.index') }}" class="btn btn-secondary btn-icon">
                    <i class="material-icons text-sm">arrow_back</i>
                </a>
                <div>
                    <div class="flex items-center gap-3">
                        <h1 class="page-title">Request #{{ $changeRequest->request_id }}</h1>
                        @if($changeRequest->status == 'pending')
                            <span class="badge badge-warning">Menunggu</span>
                        @elseif($changeRequest->status == 'approved')
                            <span class="badge badge-success">Disetujui</span>
                        @else
                            <span class="badge badge-danger">Ditolak</span>
                        @endif
                    </div>
                    <p class="page-subtitle">
                        Tipe: {{ $changeRequest->request_type == 'cancel' ? 'Pembatalan Pesanan' : 'Modifikasi Item' }}
                    </p>
                </div>
            </div>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
            
            {{-- Left Column: Request Details --}}
            <div class="lg:col-span-2 flex flex-col gap-6">
                
                {{-- Client Notes --}}
                <div class="card bg-slate-50 border-slate-200 dark:bg-slate-800/50 dark:border-slate-700">
                    <div class="card-body">
                        <h4 class="text-xs font-bold text-slate-500 uppercase mb-2">Alasan / Catatan Klien</h4>
                        <p class="text-sm text-slate-700 dark:text-slate-300 italic">
                            "{{ $changeRequest->client_notes ?? 'Tidak ada catatan.' }}"
                        </p>
                    </div>
                </div>

                @if($changeRequest->request_type == 'modify')
                    {{-- Modification Details Table --}}
                    <div class="card">
                        <div class="card-header">
                            <h3 class="card-header-title">Rincian Perubahan Item</h3>
                        </div>
                        <div class="table-container">
                            <table class="table-modern">
                                <thead>
                                    <tr>
                                        <th>Produk</th>
                                        <th>Aksi</th>
                                        <th class="text-center">Perubahan Qty</th>
                                        <th class="text-right">Estimasi Harga</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($changeRequest->items as $item)
                                        <tr>
                                            <td>
                                                <div class="font-medium text-slate-800 dark:text-white">
                                                    {{ $item->product->product_name }}
                                                </div>
                                                <div class="text-xs text-muted">{{ $item->product->product_code }}</div>
                                            </td>
                                            <td>
                                                @if($item->action == 'add')
                                                    <span class="badge badge-success">Tambah Baru</span>
                                                @elseif($item->action == 'remove')
                                                    <span class="badge badge-danger">Hapus Item</span>
                                                @elseif($item->action == 'update_qty')
                                                    <span class="badge badge-info">Ubah Jumlah</span>
                                                @endif
                                            </td>
                                            <td class="text-center">
                                                <div class="flex items-center justify-center gap-2 text-sm">
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
                                            <td class="text-right font-medium text-slate-700 dark:text-slate-300">
                                                Rp {{ number_format($item->subtotal, 0, ',', '.') }}
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>
                @else
                    {{-- Cancel Info --}}
                    <div class="card p-6 text-center border-l-4 border-l-rose-500">
                        <div class="w-16 h-16 bg-rose-100 text-rose-600 rounded-full flex items-center justify-center mx-auto mb-4">
                            <i class="material-icons text-3xl">cancel</i>
                        </div>
                        <h3 class="text-lg font-bold text-slate-800 dark:text-white mb-1">Permintaan Pembatalan Total</h3>
                        <p class="text-slate-500 dark:text-slate-400 text-sm">
                            Klien meminta pembatalan seluruh pesanan <strong>{{ $changeRequest->order->order_number }}</strong>.
                        </p>
                    </div>
                @endif
            </div>

            {{-- Right Column: Info & Actions --}}
            <div class="flex flex-col gap-6">
                
                {{-- Order Context --}}
                <div class="card">
                    <div class="card-header">
                        <h3 class="card-header-title">Informasi Pesanan Asal</h3>
                    </div>
                    <div class="card-body space-y-4">
                        <div class="flex justify-between items-center">
                            <span class="text-xs text-slate-500 uppercase">No. Order</span>
                            <a href="{{ route('admin.sales-orders.show', $changeRequest->order_id) }}" class="text-sm font-bold text-indigo-600 hover:underline">
                                {{ $changeRequest->order->order_number }}
                            </a>
                        </div>
                        <div class="flex justify-between items-center">
                            <span class="text-xs text-slate-500 uppercase">Tanggal Order</span>
                            <span class="text-sm font-medium">{{ $changeRequest->order->order_date->format('d M Y') }}</span>
                        </div>
                        <div class="flex justify-between items-center">
                            <span class="text-xs text-slate-500 uppercase">Nilai Asli</span>
                            <span class="text-sm font-bold">Rp {{ number_format($changeRequest->order->total_amount, 0, ',', '.') }}</span>
                        </div>
                        <hr class="border-slate-100 dark:border-slate-700">
                        <div>
                            <span class="text-xs text-slate-500 uppercase block mb-1">Klien</span>
                            <div class="text-sm font-bold text-slate-800 dark:text-white">{{ $changeRequest->client->client_name }}</div>
                            <div class="text-xs text-slate-500">{{ $changeRequest->client->person_in_charge }}</div>
                        </div>
                    </div>
                </div>

                {{-- Action Card --}}
                <div class="card">
                    <div class="card-header">
                        <h3 class="card-header-title">Tindakan Admin</h3>
                    </div>
                    <div class="card-body">
                        @if($changeRequest->status == 'pending')
                            <div class="flex flex-col gap-3">
                                <p class="text-xs text-slate-500 mb-2">
                                    Setujui perubahan ini untuk memperbarui pesanan secara otomatis, atau tolak jika tidak valid.
                                </p>

                                {{-- Approve Form --}}
                                <form action="{{ route('admin.order-change-requests.process', $changeRequest->request_id) }}" method="POST" onsubmit="return confirm('Apakah Anda yakin ingin menyetujui permintaan ini? Stok akan otomatis disesuaikan.');">
                                    @csrf
                                    <input type="hidden" name="action" value="approve">
                                    <button type="submit" class="btn btn-primary w-full justify-center">
                                        <i class="material-icons text-sm mr-2">check_circle</i>
                                        Setujui Permintaan
                                    </button>
                                </form>
                                
                                {{-- Reject Button (Triggers Modal) --}}
                                <button type="button" @click="showRejectModal = true" class="btn btn-danger w-full justify-center">
                                    <i class="material-icons text-sm mr-2">cancel</i>
                                    Tolak Permintaan
                                </button>
                            </div>
                        @else
                            {{-- Processed Info --}}
                            <div class="bg-slate-50 dark:bg-slate-800/50 rounded-lg p-4 text-center">
                                @if($changeRequest->status == 'approved')
                                    <div class="text-emerald-600 font-bold mb-1 flex items-center justify-center gap-1">
                                        <i class="material-icons text-sm">check_circle</i> Disetujui
                                    </div>
                                @else
                                    <div class="text-rose-600 font-bold mb-1 flex items-center justify-center gap-1">
                                        <i class="material-icons text-sm">cancel</i> Ditolak
                                    </div>
                                @endif

                                <div class="text-xs text-slate-500 mt-2">
                                    Diproses oleh: <span class="font-medium">{{ $changeRequest->processor->full_name ?? 'Admin' }}</span>
                                </div>
                                <div class="text-xs text-slate-500">
                                    Pada: {{ $changeRequest->processed_at ? $changeRequest->processed_at->format('d M Y, H:i') : '-' }}
                                </div>

                                @if($changeRequest->admin_notes)
                                    <div class="mt-3 text-xs text-slate-600 dark:text-slate-400 italic bg-white dark:bg-slate-900 p-2 rounded border border-slate-200 dark:border-slate-700">
                                        "{{ $changeRequest->admin_notes }}"
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
                    <h3 class="text-lg font-bold text-slate-800 dark:text-white">Tolak Permintaan?</h3>
                    <p class="text-sm text-slate-500 dark:text-slate-400">
                        Silakan tuliskan alasan penolakan untuk diinformasikan kepada klien.
                    </p>
                </div>

                <form action="{{ route('admin.order-change-requests.process', $changeRequest->request_id) }}" method="POST">
                    @csrf
                    <input type="hidden" name="action" value="reject">
                    
                    <div class="mb-4">
                        <textarea name="admin_notes" 
                                  x-model="rejectionReason"
                                  rows="3" 
                                  class="form-textarea w-full" 
                                  placeholder="Contoh: Stok barang tidak tersedia, atau permintaan tidak valid..."
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
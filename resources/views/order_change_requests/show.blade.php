@extends('layouts.app')

@section('title', 'Detail Permintaan Perubahan')

@section('content')
<div class="max-w-5xl mx-auto">

    {{-- HEADER --}}
    <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center mb-6 gap-4">
        <div>
            <div class="flex items-center gap-2 text-sm text-gray-500 mb-1">
                <a href="{{ route('order-change-requests.index') }}" class="hover:text-indigo-600 transition">Permintaan</a>
                <span>/</span>
                <span class="text-gray-800">Review</span>
            </div>
            <h2 class="text-2xl font-bold text-gray-900 tracking-tight flex items-center gap-3">
                REQ-{{ str_pad($changeRequest->request_id, 5, '0', STR_PAD_LEFT) }}
                
                @if($changeRequest->request_type == 'cancel')
                    <span class="px-2.5 py-0.5 rounded-full text-xs font-bold bg-red-100 text-red-800 border border-red-200 uppercase">Pembatalan</span>
                @else
                    <span class="px-2.5 py-0.5 rounded-full text-xs font-bold bg-blue-100 text-blue-800 border border-blue-200 uppercase">Modifikasi</span>
                @endif
            </h2>
        </div>
        <a href="{{ route('order-change-requests.index') }}" class="px-4 py-2 bg-white border border-gray-300 rounded-lg text-gray-700 text-sm font-medium hover:bg-gray-50 transition shadow-sm">
            Kembali
        </a>
    </div>

    {{-- ALERT --}}
    @if (session('success'))
        <div class="mb-6 bg-green-50 border-l-4 border-green-500 p-4 rounded-r shadow-sm flex justify-between items-center">
            <div class="flex items-center gap-3">
                <i class="bi bi-check-circle-fill text-green-500 text-xl"></i>
                <span class="text-sm text-green-700 font-medium">{{ session('success') }}</span>
            </div>
        </div>
    @endif

    <div class="grid grid-cols-1 lg:grid-cols-12 gap-6">
        
        {{-- KOLOM KIRI (Span 8) --}}
        <div class="lg:col-span-8 space-y-6">
            
            {{-- CARD INFO UTAMA --}}
            <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
                <div class="px-6 py-4 border-b border-gray-100 bg-gray-50 flex justify-between items-center">
                    <h3 class="text-xs font-bold text-gray-500 uppercase tracking-wider flex items-center gap-2">
                        <i class="bi bi-info-circle text-indigo-500"></i> Informasi Permintaan
                    </h3>
                    <span class="text-xs text-gray-400">{{ $changeRequest->created_at->format('d M Y, H:i') }}</span>
                </div>
                <div class="p-6 grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div>
                        <span class="block text-xs font-bold text-gray-400 uppercase mb-1">Pesanan Terkait</span>
                        @if($changeRequest->order)
                            <a href="{{ route('sales-orders.show', $changeRequest->order_id) }}" class="text-base font-bold text-indigo-600 hover:underline flex items-center gap-1">
                                {{ $changeRequest->order->order_number }} <i class="bi bi-box-arrow-up-right text-xs"></i>
                            </a>
                        @else
                            <span class="text-red-500 font-bold">Order Dihapus</span>
                        @endif
                    </div>
                    <div>
                        <span class="block text-xs font-bold text-gray-400 uppercase mb-1">Klien</span>
                        <span class="text-base font-medium text-gray-900">{{ $changeRequest->client->client_name ?? '-' }}</span>
                    </div>
                </div>
            </div>

            {{-- DETAIL MODIFIKASI (Jika Ada) --}}
            @if($changeRequest->request_type == 'modify')
            <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
                <div class="px-6 py-4 border-b border-gray-100 flex items-center gap-2">
                    <i class="bi bi-list-check text-blue-500"></i>
                    <h3 class="text-sm font-bold text-gray-700 uppercase tracking-wider">Rincian Perubahan Item</h3>
                </div>
                <div class="overflow-x-auto">
                    <table class="w-full text-left border-collapse">
                        <thead class="bg-gray-50 border-b border-gray-200">
                            <tr>
                                <th class="px-6 py-3 text-xs font-bold text-gray-500 uppercase">Produk</th>
                                <th class="px-6 py-3 text-xs font-bold text-gray-500 uppercase text-center">Aksi</th>
                                <th class="px-6 py-3 text-xs font-bold text-gray-500 uppercase text-center">Qty Awal</th>
                                <th class="px-6 py-3 text-xs font-bold text-gray-500 uppercase text-center">Qty Baru</th>
                                <th class="px-6 py-3 text-xs font-bold text-gray-500 uppercase text-right">Subtotal Baru</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            @forelse($changeRequest->items as $item)
                            <tr class="hover:bg-gray-50 transition {{ $item->action == 'remove' ? 'bg-red-50/50' : ($item->action == 'add' ? 'bg-green-50/50' : '') }}">
                                <td class="px-6 py-4 text-sm font-medium text-gray-900">{{ $item->product->product_name ?? 'Produk Dihapus' }}</td>
                                <td class="px-6 py-4 text-center">
                                    @if($item->action == 'add') 
                                        <span class="px-2 py-1 rounded bg-green-100 text-green-700 text-xs font-bold uppercase border border-green-200">Tambah</span>
                                    @elseif($item->action == 'remove') 
                                        <span class="px-2 py-1 rounded bg-red-100 text-red-700 text-xs font-bold uppercase border border-red-200">Hapus</span>
                                    @elseif($item->action == 'update_qty') 
                                        <span class="px-2 py-1 rounded bg-blue-100 text-blue-700 text-xs font-bold uppercase border border-blue-200">Ubah Qty</span>
                                    @endif
                                </td>
                                <td class="px-6 py-4 text-center text-sm text-gray-500">{{ $item->original_quantity ?? '-' }}</td>
                                <td class="px-6 py-4 text-center text-sm font-bold text-gray-900">{{ $item->requested_quantity }}</td>
                                <td class="px-6 py-4 text-right text-sm font-bold text-gray-900">Rp {{ number_format($item->subtotal, 0, ',', '.') }}</td>
                            </tr>
                            @empty
                            <tr><td colspan="5" class="px-6 py-6 text-center text-gray-500 italic">Tidak ada detail item.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
            @else
            <div class="bg-yellow-50 border border-yellow-100 rounded-xl p-6 flex items-start gap-3">
                <i class="bi bi-exclamation-triangle-fill text-yellow-500 text-xl mt-1"></i>
                <div>
                    <h4 class="text-sm font-bold text-yellow-800 uppercase mb-1">Permintaan Pembatalan Penuh</h4>
                    <p class="text-sm text-yellow-700">Klien meminta untuk membatalkan seluruh pesanan ini.</p>
                </div>
            </div>
            @endif

            {{-- CATATAN KLIEN --}}
            @if($changeRequest->client_notes)
            <div class="bg-gray-50 border border-gray-200 rounded-xl p-4">
                <h4 class="text-xs font-bold text-gray-500 uppercase mb-2 flex items-center gap-2">
                    <i class="bi bi-chat-quote-fill"></i> Catatan Klien
                </h4>
                <p class="text-sm text-gray-700 italic">"{{ $changeRequest->client_notes }}"</p>
            </div>
            @endif

        </div>

        {{-- KOLOM KANAN: ACTION CARD (Span 4) --}}
        <div class="lg:col-span-4 space-y-6">
            
            <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6 sticky top-6">
                <h3 class="text-sm font-bold text-gray-900 mb-4 border-b border-gray-100 pb-3 flex items-center gap-2">
                    <i class="bi bi-shield-check text-indigo-500"></i> Status & Tindakan
                </h3>

                <div class="mb-6 text-center">
                    <span class="block text-xs font-bold text-gray-400 uppercase mb-2">STATUS SAAT INI</span>
                    @if($changeRequest->status == 'pending')
                        <span class="inline-block px-4 py-1.5 rounded-full text-sm font-bold bg-yellow-100 text-yellow-700 border border-yellow-200">Menunggu Review</span>
                    @elseif($changeRequest->status == 'approved')
                        <span class="inline-block px-4 py-1.5 rounded-full text-sm font-bold bg-green-100 text-green-700 border border-green-200">Disetujui</span>
                    @else
                        <span class="inline-block px-4 py-1.5 rounded-full text-sm font-bold bg-red-100 text-red-700 border border-red-200">Ditolak</span>
                    @endif
                </div>

                @if($changeRequest->status == 'pending')
                    <form action="{{ route('order-change-requests.process', $changeRequest->request_id) }}" method="POST" id="process-form">
                        @csrf
                        
                        <div class="mb-4">
                            <label class="block text-xs font-bold text-gray-700 uppercase mb-2">Tindakan Anda</label>
                            <div class="grid grid-cols-2 gap-3">
                                <label class="cursor-pointer">
                                    <input type="radio" name="action" value="approve" class="peer sr-only" checked>
                                    <div class="text-center py-2 px-3 rounded-lg border-2 border-gray-200 text-gray-500 peer-checked:border-green-500 peer-checked:bg-green-50 peer-checked:text-green-700 hover:bg-gray-50 transition">
                                        <i class="bi bi-check-circle block text-lg mb-1"></i>
                                        <span class="text-xs font-bold uppercase">Setujui</span>
                                    </div>
                                </label>
                                <label class="cursor-pointer">
                                    <input type="radio" name="action" value="reject" class="peer sr-only">
                                    <div class="text-center py-2 px-3 rounded-lg border-2 border-gray-200 text-gray-500 peer-checked:border-red-500 peer-checked:bg-red-50 peer-checked:text-red-700 hover:bg-gray-50 transition">
                                        <i class="bi bi-x-circle block text-lg mb-1"></i>
                                        <span class="text-xs font-bold uppercase">Tolak</span>
                                    </div>
                                </label>
                            </div>
                        </div>

                        <div class="mb-4">
                            <label class="block text-xs font-bold text-gray-700 uppercase mb-1">Catatan Admin (Opsional)</label>
                            <textarea name="admin_notes" rows="3" class="w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 text-sm bg-gray-50" placeholder="Tulis alasan..."></textarea>
                        </div>

                        <button type="button" onclick="confirmSubmit()" class="w-full py-3 bg-indigo-600 hover:bg-indigo-700 text-white text-sm font-bold rounded-lg shadow-md transition flex items-center justify-center gap-2">
                            <i class="bi bi-send"></i> Simpan Keputusan
                        </button>
                    </form>
                @else
                    <div class="bg-gray-50 rounded-lg p-4 border border-gray-100 text-center">
                        <p class="text-xs text-gray-500 mb-1">Diproses oleh</p>
                        <p class="text-sm font-bold text-gray-900">{{ $changeRequest->processor->full_name ?? 'System' }}</p>
                        <p class="text-xs text-gray-400 mt-1">{{ optional($changeRequest->processed_at)->format('d M Y, H:i') }}</p>
                        
                        @if($changeRequest->admin_notes)
                            <div class="mt-3 pt-3 border-t border-gray-200">
                                <span class="text-[10px] font-bold text-gray-400 uppercase">Catatan Admin</span>
                                <p class="text-sm text-gray-700 italic mt-1">"{{ $changeRequest->admin_notes }}"</p>
                            </div>
                        @endif
                    </div>
                @endif

            </div>
        </div>

    </div>
</div>
@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
    function confirmSubmit() {
        const action = document.querySelector('input[name="action"]:checked').value;
        const isApprove = action === 'approve';
        
        Swal.fire({
            title: isApprove ? 'Setujui Permintaan?' : 'Tolak Permintaan?',
            text: isApprove 
                ? "Perubahan akan diterapkan ke pesanan asli." 
                : "Permintaan akan ditolak dan status kembali ke awal.",
            icon: isApprove ? 'question' : 'warning',
            showCancelButton: true,
            confirmButtonColor: isApprove ? '#10b981' : '#ef4444',
            cancelButtonColor: '#6b7280',
            confirmButtonText: isApprove ? 'Ya, Setujui!' : 'Ya, Tolak!',
            cancelButtonText: 'Batal'
        }).then((result) => {
            if (result.isConfirmed) {
                document.getElementById('process-form').submit();
            }
        });
    }
</script>
@endpush
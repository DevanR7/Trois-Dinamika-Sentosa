@extends('layouts.app')

@section('title', 'Detail Review Pesanan')

@section('content')
<div class="max-w-6xl mx-auto">

    {{-- HEADER --}}
    <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center mb-6 gap-4">
        <div>
            <div class="flex items-center gap-2 text-sm text-gray-500 mb-1">
                <a href="{{ route('client-order-reviews.index') }}" class="hover:text-indigo-600 transition">Review Pesanan</a>
                <span>/</span>
                <span class="text-gray-800">Detail</span>
            </div>
            <h2 class="text-2xl font-bold text-gray-900 tracking-tight flex items-center gap-3">
                {{ $order->order_number }}
                {{-- Badge --}}
                @if($order->status == 'pending_review')
                    <span class="px-2.5 py-0.5 rounded-full text-xs font-bold bg-blue-100 text-blue-800 border border-blue-200 uppercase">Menunggu Review</span>
                @elseif($order->status == 'invoiced')
                    <span class="px-2.5 py-0.5 rounded-full text-xs font-bold bg-green-100 text-green-800 border border-green-200 uppercase">Disetujui</span>
                @elseif($order->status == 'rejected')
                    <span class="px-2.5 py-0.5 rounded-full text-xs font-bold bg-red-100 text-red-800 border border-red-200 uppercase">Ditolak</span>
                @endif
            </h2>
        </div>
        <a href="{{ route('client-order-reviews.index') }}" class="px-4 py-2 bg-white border border-gray-300 rounded-lg text-gray-700 text-sm font-medium hover:bg-gray-50 transition shadow-sm">
            Kembali
        </a>
    </div>

    {{-- FLASH MESSAGE --}}
    @if (session('success'))
        <div class="mb-6 bg-green-50 border-l-4 border-green-500 p-4 rounded-r shadow-sm flex justify-between items-center">
            <div class="flex items-center gap-3">
                <i class="bi bi-check-circle-fill text-green-500 text-xl"></i>
                <span class="text-sm text-green-700 font-medium">{{ session('success') }}</span>
            </div>
        </div>
    @endif
    @if (session('error'))
        <div class="mb-6 bg-red-50 border-l-4 border-red-500 p-4 rounded-r shadow-sm flex justify-between items-center">
            <div class="flex items-center gap-3">
                <i class="bi bi-exclamation-triangle-fill text-red-500 text-xl"></i>
                <span class="text-sm text-red-700 font-medium">{{ session('error') }}</span>
            </div>
        </div>
    @endif

    <div class="grid grid-cols-1 lg:grid-cols-12 gap-6">
        
        {{-- KOLOM KIRI: DETAIL ITEM (Span 8) --}}
        <div class="lg:col-span-8 space-y-6">
            
            {{-- CARD ITEM --}}
            <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
                <div class="px-6 py-4 border-b border-gray-100 bg-gray-50 flex justify-between items-center">
                    <h3 class="text-xs font-bold text-gray-500 uppercase tracking-wider flex items-center gap-2">
                        <i class="bi bi-cart-check text-indigo-500"></i> Rincian Item
                    </h3>
                    <span class="text-xs text-gray-400">{{ $order->items->count() }} Produk</span>
                </div>
                <div class="overflow-x-auto">
                    <table class="w-full text-left border-collapse">
                        <thead class="bg-white border-b border-gray-200">
                            <tr>
                                <th class="px-6 py-3 text-xs font-bold text-gray-500 uppercase w-10 text-center">No</th>
                                <th class="px-6 py-3 text-xs font-bold text-gray-500 uppercase">Produk</th>
                                <th class="px-6 py-3 text-xs font-bold text-gray-500 uppercase text-center">Qty</th>
                                <th class="px-6 py-3 text-xs font-bold text-gray-500 uppercase text-right">Harga (@)</th>
                                <th class="px-6 py-3 text-xs font-bold text-gray-500 uppercase text-right">Subtotal</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            @forelse($order->items as $item)
                            <tr class="hover:bg-gray-50 transition">
                                <td class="px-6 py-4 text-center text-sm text-gray-500">{{ $loop->iteration }}</td>
                                <td class="px-6 py-4">
                                    <div class="text-sm font-bold text-gray-900">{{ $item->product->product_name ?? 'Produk Dihapus' }}</div>
                                </td>
                                <td class="px-6 py-4 text-center">
                                    <span class="px-2.5 py-1 rounded-md bg-gray-100 text-xs font-bold text-gray-700 border border-gray-200">
                                        {{ $item->quantity }}
                                    </span>
                                </td>
                                <td class="px-6 py-4 text-right text-sm text-gray-600">
                                    Rp {{ number_format($item->price_per_unit, 0, ',', '.') }}
                                </td>
                                <td class="px-6 py-4 text-right text-sm font-bold text-gray-900">
                                    Rp {{ number_format($item->subtotal, 0, ',', '.') }}
                                </td>
                            </tr>
                            @empty
                            <tr><td colspan="5" class="text-center py-6 text-gray-500 italic">Tidak ada item.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
                <div class="p-4 bg-gray-50 border-t border-gray-200 flex justify-end items-center gap-4">
                    <span class="text-xs font-bold text-gray-500 uppercase">Total Pesanan</span>
                    <span class="text-xl font-bold text-indigo-600">Rp {{ number_format($order->total_amount, 0, ',', '.') }}</span>
                </div>
            </div>

            {{-- CARD INFO PENGIRIMAN --}}
            <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
                <div class="px-6 py-4 border-b border-gray-100 bg-gray-50">
                    <h3 class="text-xs font-bold text-gray-500 uppercase tracking-wider flex items-center gap-2">
                        <i class="bi bi-person-lines-fill text-indigo-500"></i> Informasi Klien
                    </h3>
                </div>
                <div class="p-6 grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div>
                        <label class="block text-xs font-bold text-gray-400 uppercase mb-1">Nama Klien</label>
                        <p class="text-sm font-bold text-gray-900">{{ $order->client->client_name }}</p>
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-gray-400 uppercase mb-1">Kontak</label>
                        <p class="text-sm text-gray-700">{{ $order->client->phone_number ?? '-' }}</p>
                    </div>
                    <div class="md:col-span-2">
                        <label class="block text-xs font-bold text-gray-400 uppercase mb-1">Alamat Pengiriman</label>
                        <p class="text-sm text-gray-700 bg-gray-50 p-3 rounded border border-gray-100">
                            {{ $order->client->address ?? 'Alamat tidak tersedia' }}
                        </p>
                    </div>
                    @if($order->notes)
                    <div class="md:col-span-2">
                        <label class="block text-xs font-bold text-gray-400 uppercase mb-1">Catatan Pesanan</label>
                        <p class="text-sm text-yellow-800 bg-yellow-50 p-3 rounded border border-yellow-100 italic">
                            {{ $order->notes }}
                        </p>
                    </div>
                    @endif
                </div>
            </div>

        </div>

        {{-- KOLOM KANAN: AKSI (Span 4) --}}
        <div class="lg:col-span-4 space-y-6">
            
            <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6 sticky top-6">
                <h3 class="text-sm font-bold text-gray-900 mb-4 border-b border-gray-100 pb-3">
                    Tindakan Review
                </h3>

                @if($order->status == 'pending_review')
                    <div class="space-y-3">
                        <p class="text-sm text-gray-600 mb-4">Pesanan ini menunggu persetujuan Anda. Jika disetujui, Draft Invoice akan otomatis dibuat.</p>
                        
                        <a href="{{ route('invoices.createFromOrder', $order->order_id) }}" class="block w-full py-3 bg-green-600 hover:bg-green-700 text-white text-sm font-bold rounded-lg shadow-md transition text-center flex justify-center items-center gap-2">
                            <i class="bi bi-check-circle-fill"></i> Setujui & Buat Invoice
                        </a>
                        
                        <button type="button" onclick="openModal('rejectModal')" class="w-full py-3 bg-white border border-red-200 text-red-600 hover:bg-red-50 rounded-lg text-sm font-bold transition flex justify-center items-center gap-2">
                            <i class="bi bi-x-circle"></i> Tolak Pesanan
                        </button>
                    </div>
                @elseif($order->status == 'invoiced' && $order->invoice_id)
                    <div class="text-center py-4">
                        <i class="bi bi-check-circle text-green-500 text-4xl mb-2"></i>
                        <h4 class="text-gray-900 font-bold">Pesanan Disetujui</h4>
                        <p class="text-xs text-gray-500 mb-4">Invoice telah dibuat untuk pesanan ini.</p>
                        <a href="{{ route('invoices.show', $order->invoice_id) }}" class="inline-block px-4 py-2 bg-indigo-600 text-white rounded-lg text-sm font-bold hover:bg-indigo-700 transition">
                            Lihat Invoice
                        </a>
                    </div>
                @elseif($order->status == 'rejected')
                    <div class="text-center py-4">
                        <i class="bi bi-x-circle text-red-500 text-4xl mb-2"></i>
                        <h4 class="text-gray-900 font-bold">Pesanan Ditolak</h4>
                        @if($order->rejection_reason)
                            <p class="text-sm text-red-600 bg-red-50 p-3 rounded mt-2 border border-red-100 italic">"{{ $order->rejection_reason }}"</p>
                        @endif
                    </div>
                @endif

            </div>

        </div>

    </div>
</div>

{{-- MODAL TOLAK (TAILWIND) --}}
<div id="rejectModal" class="relative z-[100] hidden" aria-labelledby="modal-title" role="dialog" aria-modal="true">
    <div class="fixed inset-0 bg-gray-900 bg-opacity-50 transition-opacity backdrop-blur-sm"></div>
    <div class="fixed inset-0 z-10 w-screen overflow-y-auto">
        <div class="flex min-h-full items-center justify-center p-4 text-center sm:p-0">
            <div class="relative transform overflow-hidden rounded-xl bg-white text-left shadow-xl transition-all sm:my-8 sm:w-full sm:max-w-lg">
                <div class="bg-red-50 px-4 py-3 sm:px-6 flex items-center gap-3 border-b border-red-100">
                    <div class="mx-auto flex h-10 w-10 flex-shrink-0 items-center justify-center rounded-full bg-red-100 sm:mx-0">
                        <i class="bi bi-exclamation-triangle text-red-600 text-lg"></i>
                    </div>
                    <h3 class="text-lg font-bold leading-6 text-red-900" id="modal-title">Tolak Pesanan?</h3>
                </div>
                
                <form action="{{ route('client-order-reviews.reject', $order->order_id) }}" method="POST">
                    @csrf
                    <div class="px-6 py-4">
                        <p class="text-sm text-gray-500 mb-4">
                            Anda yakin ingin menolak pesanan <strong>{{ $order->order_number }}</strong>? Tindakan ini akan mengubah status menjadi ditolak dan tidak dapat membuat invoice.
                        </p>
                        <div>
                            <label for="rejection_notes" class="block text-xs font-bold text-gray-700 uppercase mb-1">Alasan Penolakan (Opsional)</label>
                            <textarea name="rejection_notes" id="rejection_notes" rows="3" class="w-full rounded-md border-gray-300 shadow-sm focus:border-red-500 focus:ring-red-500 text-sm" placeholder="Contoh: Stok habis, harga berubah..."></textarea>
                        </div>
                    </div>
                    <div class="bg-gray-50 px-4 py-3 sm:flex sm:flex-row-reverse sm:px-6 border-t border-gray-100">
                        <button type="submit" class="inline-flex w-full justify-center rounded-md bg-red-600 px-3 py-2 text-sm font-semibold text-white shadow-sm hover:bg-red-500 sm:ml-3 sm:w-auto">Ya, Tolak</button>
                        <button type="button" class="mt-3 inline-flex w-full justify-center rounded-md bg-white px-3 py-2 text-sm font-semibold text-gray-900 shadow-sm ring-1 ring-inset ring-gray-300 hover:bg-gray-50 sm:mt-0 sm:w-auto" onclick="closeModal('rejectModal')">Batal</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

@endsection

@push('scripts')
<script>
    function openModal(modalId) {
        document.getElementById(modalId).classList.remove('hidden');
    }
    function closeModal(modalId) {
        document.getElementById(modalId).classList.add('hidden');
    }
</script>
@endpush
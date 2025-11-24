@extends('layouts.app')

@section('title', 'Detail Retur Pembelian')

@section('content')
<div class="max-w-7xl mx-auto">
    
    {{-- HEADER HALAMAN --}}
    <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center mb-6 gap-4">
        <div>
            <div class="flex items-center gap-2 text-sm text-gray-500 mb-1">
                <a href="{{ route('purchase-returns.index') }}" class="hover:text-indigo-600 transition">Retur</a>
                <span>/</span>
                <span class="text-gray-800">Detail</span>
            </div>
            <h2 class="text-2xl font-bold text-gray-900 tracking-tight flex items-center gap-3">
                <span class="font-mono">{{ $purchaseReturn->return_number }}</span>
            </h2>
        </div>
        
        <a href="{{ route('purchase-returns.index') }}" class="px-4 py-2 bg-white border border-gray-300 rounded-lg text-gray-700 text-sm font-medium hover:bg-gray-50 transition shadow-sm">
            Kembali
        </a>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-12 gap-6">
        
        {{-- KOLOM KIRI: INFORMASI & ITEM (Span 8) --}}
        <div class="lg:col-span-8 space-y-6">
            
            {{-- CARD 1: INFO TRANSAKSI --}}
            <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
                <div class="px-6 py-4 border-b border-gray-100 bg-gray-50 flex justify-between items-center">
                    <h3 class="text-xs font-bold text-gray-500 uppercase tracking-wider flex items-center gap-2">
                        <i class="bi bi-info-circle text-indigo-500"></i> Data Transaksi
                    </h3>
                </div>
                <div class="p-6">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        
                        {{-- Info Supplier --}}
                        <div class="flex items-start gap-4">
                            <div class="w-10 h-10 rounded-full bg-indigo-50 flex items-center justify-center text-indigo-600 flex-shrink-0 border border-indigo-100">
                                <i class="bi bi-shop text-lg"></i>
                            </div>
                            <div>
                                <label class="block text-xs font-bold text-gray-400 uppercase mb-1">Supplier</label>
                                <h4 class="text-base font-bold text-gray-900">{{ $purchaseReturn->supplier->supplier_name }}</h4>
                                <div class="mt-2 text-sm">
                                    <span class="text-gray-500 mr-1">PO Asli:</span>
                                    <a href="{{ route('purchase-orders.show', $purchaseReturn->purchase_order_id) }}" class="text-indigo-600 font-medium hover:underline">
                                        {{ $purchaseReturn->purchaseOrder->po_number }}
                                    </a>
                                </div>
                            </div>
                        </div>

                        {{-- Info Lain --}}
                        <div class="space-y-3 border-l border-gray-100 pl-0 md:pl-6">
                            <div class="flex justify-between items-center">
                                <span class="text-xs font-medium text-gray-500 uppercase">Tanggal Retur</span>
                                <span class="text-sm font-semibold text-gray-900">{{ optional($purchaseReturn->return_date)->format('d F Y') }}</span>
                            </div>
                            <div class="flex justify-between items-center">
                                <span class="text-xs font-medium text-gray-500 uppercase">Diproses Oleh</span>
                                <span class="text-sm font-semibold text-gray-900">{{ $purchaseReturn->user->full_name ?? 'System' }}</span>
                            </div>
                            <div class="flex justify-between items-center pt-2 border-t border-dashed border-gray-200">
                                <span class="text-xs font-medium text-gray-500 uppercase">Waktu Input</span>
                                <span class="text-xs text-gray-400">{{ $purchaseReturn->created_at->format('d/m/Y H:i') }}</span>
                            </div>
                        </div>

                    </div>
                </div>
            </div>

            {{-- CARD 2: ITEM DIKEMBALIKAN --}}
            <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
                <div class="px-6 py-4 border-b border-gray-100 bg-red-50">
                    <h3 class="text-xs font-bold text-red-600 uppercase tracking-wider flex items-center gap-2">
                        <i class="bi bi-arrow-return-left"></i> Item Dikembalikan
                    </h3>
                </div>
                <div class="overflow-x-auto">
                    <table class="w-full text-left border-collapse">
                        <thead class="bg-white border-b border-gray-200">
                            <tr>
                                <th class="px-6 py-3 text-xs font-bold text-gray-500 uppercase w-10 text-center">No</th>
                                <th class="px-6 py-3 text-xs font-bold text-gray-500 uppercase">Produk</th>
                                <th class="px-6 py-3 text-xs font-bold text-gray-500 uppercase text-center">Qty Retur</th>
                                <th class="px-6 py-3 text-xs font-bold text-gray-500 uppercase text-right">Harga Beli (@)</th>
                                <th class="px-6 py-3 text-xs font-bold text-gray-500 uppercase text-right">Subtotal</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            @forelse($purchaseReturn->items as $item)
                            <tr class="hover:bg-gray-50 transition">
                                <td class="px-6 py-4 text-center text-sm text-gray-500">{{ $loop->iteration }}</td>
                                <td class="px-6 py-4">
                                    <div class="text-sm font-bold text-gray-900">{{ $item->product->product_name ?? 'Produk Dihapus' }}</div>
                                </td>
                                <td class="px-6 py-4 text-center">
                                    <span class="px-2.5 py-1 rounded-md bg-red-50 text-xs font-bold text-red-700 border border-red-100">
                                        {{ $item->quantity }} {{ $item->product->unit->name ?? '' }}
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
                            <tr>
                                <td colspan="5" class="px-6 py-8 text-center text-gray-500 text-sm italic">Tidak ada item dalam retur ini.</td>
                            </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

            {{-- CARD 3: ALASAN --}}
            @if($purchaseReturn->notes)
            <div class="bg-yellow-50 rounded-xl border border-yellow-100 p-4 flex gap-3 items-start">
                <i class="bi bi-sticky text-yellow-600 mt-0.5 text-lg"></i>
                <div>
                    <h4 class="text-xs font-bold text-yellow-800 uppercase mb-1">Alasan Retur (Catatan)</h4>
                    <p class="text-sm text-yellow-900 italic">{{ $purchaseReturn->notes }}</p>
                </div>
            </div>
            @endif

        </div>

        {{-- KOLOM KANAN: SUMMARY & ACTION (Span 4) --}}
        <div class="lg:col-span-4 space-y-6">
            
            {{-- CARD TOTAL --}}
            <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6 sticky top-6">
                <h3 class="text-sm font-bold text-gray-900 mb-4 flex items-center gap-2 border-b border-gray-100 pb-3">
                    <i class="bi bi-calculator text-indigo-500"></i> Ringkasan Retur
                </h3>

                <div class="flex justify-between items-center mb-6">
                    <span class="text-sm font-bold text-gray-500 uppercase tracking-wider">Total Nilai</span>
                    <span class="text-2xl font-bold text-red-600">Rp {{ number_format($purchaseReturn->total_amount, 0, ',', '.') }}</span>
                </div>

                {{-- Opsi Refund Info --}}
                <div class="bg-gray-50 rounded-lg p-4 border border-gray-200 mb-6">
                    <h4 class="text-xs font-bold text-gray-400 uppercase mb-2 tracking-wider">Metode Pengembalian</h4>
                    @if($purchaseReturn->return_handling_type == 'store_as_deposit')
                        <div class="flex items-center gap-2 text-green-700 bg-green-50 px-3 py-2 rounded-md border border-green-100">
                            <i class="bi bi-wallet2"></i>
                            <span class="text-sm font-bold">Masuk Saldo Deposit</span>
                        </div>
                    @elseif($purchaseReturn->return_handling_type == 'deduct_invoice')
                        <div class="flex items-center gap-2 text-blue-700 bg-blue-50 px-3 py-2 rounded-md border border-blue-100">
                            <i class="bi bi-receipt-cutoff"></i>
                            <span class="text-sm font-bold">Potong Tagihan PO</span>
                        </div>
                    @else
                        <div class="flex items-center gap-2 text-gray-600 bg-gray-100 px-3 py-2 rounded-md">
                            <i class="bi bi-question-circle"></i>
                            <span class="text-sm">Tidak Diketahui</span>
                        </div>
                    @endif
                </div>

                {{-- Tombol Hapus --}}
                <form action="{{ route('purchase-returns.destroy', $purchaseReturn->return_id) }}" method="POST" class="delete-form">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="w-full py-2.5 bg-white border border-red-200 text-red-600 rounded-lg text-sm font-bold hover:bg-red-50 transition shadow-sm flex items-center justify-center gap-2">
                        <i class="bi bi-trash"></i> Batalkan Retur
                    </button>
                </form>
                
                <p class="text-center text-xs text-gray-400 mt-3 px-2">
                    Membatalkan retur akan mengembalikan stok barang masuk ke gudang dan membatalkan jurnal akuntansi terkait.
                </p>
            </div>

        </div>

    </div>
</div>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    const deleteForm = document.querySelector('.delete-form');
    if (deleteForm) {
        deleteForm.addEventListener('submit', function(event) {
            event.preventDefault();
            Swal.fire({
                title: 'Batalkan Retur?',
                text: "Stok produk akan dikembalikan ke sistem dan jurnal dihapus. Data ini tidak bisa dikembalikan!",
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#ef4444', // Tailwind Red-500
                cancelButtonColor: '#6b7280', // Tailwind Gray-500
                confirmButtonText: 'Ya, Hapus!',
                cancelButtonText: 'Batal'
            }).then((result) => {
                if (result.isConfirmed) {
                    event.target.submit();
                }
            });
        });
    }
});
</script>
@endpush
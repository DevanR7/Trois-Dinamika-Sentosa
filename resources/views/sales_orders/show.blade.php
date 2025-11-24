@extends('layouts.app')

@section('title', 'Detail Pesanan Penjualan')

@section('content')
<div class="max-w-6xl mx-auto">
    
    {{-- HEADER --}}
    <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center mb-6 gap-4">
        <div>
            <div class="flex items-center gap-2 text-sm text-gray-500 mb-1">
                <a href="{{ route('sales-orders.index') }}" class="hover:text-indigo-600 transition">Pesanan</a>
                <span>/</span>
                <span class="text-gray-800">Detail</span>
            </div>
            <h2 class="text-2xl font-bold text-gray-900 tracking-tight">
                No. Pesanan: <span class="text-indigo-600">{{ $order->order_number }}</span>
            </h2>
        </div>
        
        <div class="flex gap-3">
            <a href="{{ route('sales-orders.index') }}" class="px-4 py-2 bg-white border border-gray-300 rounded-lg text-gray-700 text-sm font-medium hover:bg-gray-50 transition shadow-sm">
                Kembali
            </a>

            {{-- Tombol Invoice --}}
            @can('create', App\Models\SalesInvoice::class)
                @if($order->status !== 'invoiced' && $order->status !== 'rejected')
                    <a href="{{ route('invoices.createFromOrder', $order) }}" class="px-4 py-2 bg-indigo-600 text-white rounded-lg text-sm font-bold hover:bg-indigo-700 transition shadow-md flex items-center gap-2">
                        <i class="bi bi-receipt-cutoff"></i> Buat Invoice
                    </a>
                @endif
            @endcan
            
            {{-- Dropdown Opsi --}}
            @if (!in_array($order->status, ['invoiced', 'rejected']))
            <div class="relative">
                <button onclick="toggleDropdown('opsi-dropdown-so')" class="px-4 py-2 bg-white border border-gray-300 rounded-lg text-gray-700 text-sm font-medium hover:bg-gray-50 transition shadow-sm flex items-center gap-2 focus:ring-2 focus:ring-indigo-500 focus:outline-none">
                    <i class="bi bi-gear"></i> Opsi <i class="bi bi-chevron-down text-xs"></i>
                </button>
                
                {{-- Dropdown Content --}}
                <div id="opsi-dropdown-so" class="hidden absolute right-0 mt-2 w-48 bg-white rounded-lg shadow-xl border border-gray-200 z-50 origin-top-right">
                    <div class="py-1">
                        @can("update", $order)
                            <a href="{{ route('sales-orders.edit', $order->order_id) }}" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100 hover:text-indigo-600">
                                <i class="bi bi-pencil-square mr-2"></i> Edit Pesanan
                            </a>
                        @endcan

                        @can("delete", $order)
                            <form action="{{ route('sales-orders.destroy', $order->order_id) }}" method="POST" class="delete-form block w-full border-t border-gray-100">
                                @csrf @method('DELETE')
                                <button type="submit" class="w-full text-left px-4 py-2 text-sm text-red-600 hover:bg-red-50 font-medium">
                                    <i class="bi bi-trash mr-2"></i> Hapus Pesanan
                                </button>
                            </form>
                        @endcan
                    </div>
                </div>
            </div>
            @endif
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-12 gap-6">
        
        {{-- KOLOM KIRI --}}
        <div class="lg:col-span-8 space-y-6">
            
            <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
                <div class="px-6 py-4 border-b border-gray-100 bg-gray-50 flex justify-between items-center">
                    <h3 class="text-xs font-bold text-gray-500 uppercase tracking-wider flex items-center gap-2">
                        <i class="bi bi-info-circle text-indigo-500"></i> Informasi Pesanan
                    </h3>
                    
                    {{-- Status Badge --}}
                    @if($order->status == 'invoiced')
                        <span class="px-2.5 py-0.5 rounded-full text-xs font-bold bg-green-100 text-green-800 border border-green-200">Invoiced</span>
                    @elseif($order->status == 'rejected')
                        <span class="px-2.5 py-0.5 rounded-full text-xs font-bold bg-red-100 text-red-800 border border-red-200">Ditolak</span>
                    @else
                        <span class="px-2.5 py-0.5 rounded-full text-xs font-bold bg-gray-100 text-gray-800 border border-gray-200">{{ Str::title($order->status) }}</span>
                    @endif
                </div>

                <div class="p-6 grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div class="flex items-start gap-4">
                        <div class="w-12 h-12 rounded-full bg-indigo-50 flex items-center justify-center text-indigo-600 flex-shrink-0 border border-indigo-100">
                            <i class="bi bi-building text-xl"></i>
                        </div>
                        <div>
                            <label class="block text-xs font-bold text-gray-400 uppercase mb-1">Klien</label>
                            <h4 class="text-base font-bold text-gray-900">{{ $order->client->client_name }}</h4>
                            <p class="text-xs text-gray-500 mt-0.5">{{ $order->client->address ?? 'Alamat tidak tersedia' }}</p>
                            <p class="text-xs text-gray-500">{{ $order->client->phone_number ?? '-' }}</p>
                        </div>
                    </div>
                    <div class="space-y-3 border-l border-gray-100 pl-0 md:pl-6">
                        <div class="flex justify-between">
                            <span class="text-xs font-medium text-gray-500 uppercase">Tanggal Pesan</span>
                            <span class="text-sm font-semibold text-gray-900">{{ $order->order_date->format('d F Y') }}</span>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-xs font-medium text-gray-500 uppercase">Sales Person</span>
                            <span class="text-sm font-semibold text-gray-900">{{ $order->sales->full_name ?? 'N/A' }}</span>
                        </div>
                    </div>
                </div>
            </div>

            <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
                <div class="px-6 py-4 border-b border-gray-100">
                    <h3 class="text-xs font-bold text-gray-500 uppercase tracking-wider">Rincian Item</h3>
                </div>
                <div class="overflow-x-auto">
                    <table class="w-full text-left border-collapse">
                        <thead class="bg-gray-50 border-b border-gray-200">
                            <tr>
                                <th class="px-6 py-3 text-xs font-bold text-gray-500 uppercase w-1/2">Produk</th>
                                <th class="px-6 py-3 text-xs font-bold text-gray-500 uppercase text-center">Qty</th>
                                <th class="px-6 py-3 text-xs font-bold text-gray-500 uppercase text-right">Harga (@)</th>
                                <th class="px-6 py-3 text-xs font-bold text-gray-500 uppercase text-right">Subtotal</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            @foreach($order->items as $item)
                            <tr class="hover:bg-gray-50 transition">
                                <td class="px-6 py-4 text-sm font-medium text-gray-900">{{ $item->product->product_name ?? 'Produk Dihapus' }}</td>
                                <td class="px-6 py-4 text-center">
                                    <span class="px-2 py-1 bg-gray-100 rounded text-xs font-bold text-gray-700 border border-gray-200">
                                        {{ $item->quantity }} {{ $item->product->unit->name ?? '' }}
                                    </span>
                                </td>
                                <td class="px-6 py-4 text-right text-sm text-gray-600">Rp {{ number_format($item->price_per_unit, 0, ',', '.') }}</td>
                                <td class="px-6 py-4 text-right text-sm font-bold text-gray-900">Rp {{ number_format($item->subtotal, 0, ',', '.') }}</td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                @if($order->notes)
                <div class="p-4 bg-yellow-50 border-t border-yellow-100 text-sm text-yellow-800 italic">
                    <i class="bi bi-sticky mr-1"></i> Catatan: {{ $order->notes }}
                </div>
                @endif
            </div>

        </div>

        {{-- KOLOM KANAN --}}
        <div class="lg:col-span-4">
            <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6 sticky top-6">
                <h3 class="text-sm font-bold text-gray-900 mb-4 flex items-center gap-2 border-b border-gray-100 pb-3">
                    <i class="bi bi-calculator text-indigo-500"></i> Ringkasan
                </h3>
                
                <div class="flex justify-between mb-3 text-sm text-gray-600">
                    <span>Jumlah Item</span>
                    <span class="font-medium">{{ $order->items->count() }}</span>
                </div>
                <div class="flex justify-between mb-4 text-sm text-gray-600">
                    <span>Total Kuantitas</span>
                    <span class="font-medium">{{ $order->items->sum('quantity') }}</span>
                </div>

                <div class="border-t border-dashed border-gray-200 my-4"></div>

                <div class="flex justify-between items-center">
                    <span class="text-sm font-bold text-gray-900 uppercase">TOTAL TAGIHAN</span>
                    <span class="text-xl font-bold text-indigo-600">Rp {{ number_format($order->total_amount, 0, ',', '.') }}</span>
                </div>
                
                <div class="mt-6 text-center text-xs text-gray-400">
                    Dibuat pada {{ $order->created_at->format('d M Y H:i') }}
                </div>
            </div>
        </div>

    </div>
</div>
@endsection

@push('scripts')
<script>
function toggleDropdown(id) {
        const el = document.getElementById(id);
        if (el.classList.contains('hidden')) {
            el.classList.remove('hidden');
        } else {
            el.classList.add('hidden');
        }
    }

    // Menutup dropdown saat klik di luar area
    window.addEventListener('click', function(e) {
        const btn = document.querySelector('button[onclick="toggleDropdown(\'opsi-dropdown-so\')"]');
        const dropdown = document.getElementById('opsi-dropdown-so');
        
        if (btn && dropdown && !btn.contains(e.target) && !dropdown.contains(e.target)) {
            dropdown.classList.add('hidden');
        }
    });
    
document.addEventListener('DOMContentLoaded', function() {
    const deleteForms = document.querySelectorAll('.delete-form');
    deleteForms.forEach(form => {
        form.addEventListener('submit', function(event) {
            event.preventDefault();
            Swal.fire({
                title: 'Hapus Pesanan?',
                text: "Data yang dihapus tidak bisa dikembalikan.",
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#ef4444',
                cancelButtonColor: '#6b7280',
                confirmButtonText: 'Ya, Hapus!'
            }).then((result) => {
                if (result.isConfirmed) form.submit();
            });
        });
    });
});
</script>
@endpush
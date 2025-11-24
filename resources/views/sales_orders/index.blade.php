@extends("layouts.app")

@section("title", "Daftar Pesanan Penjualan")

@section("content")
<div class="max-w-7xl mx-auto">

    {{-- HEADER --}}
    <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center mb-6 gap-4">
        <div>
            <h2 class="text-2xl font-bold text-gray-900 tracking-tight">Pesanan Penjualan</h2>
            <p class="text-sm text-gray-500 mt-1">Kelola order masuk dari pelanggan.</p>
        </div>
        <a href="{{ route('sales-orders.create') }}" class="inline-flex items-center px-4 py-2 bg-indigo-600 hover:bg-indigo-700 text-white text-sm font-bold rounded-lg shadow-md transition flex items-center gap-2">
            <i class="bi bi-plus-lg"></i> Buat Pesanan Baru
        </a>
    </div>

    {{-- FLASH MESSAGE --}}
    @if (session("success"))
        <div class="mb-6 bg-green-50 border-l-4 border-green-500 p-4 rounded-r shadow-sm flex justify-between items-center">
            <div class="flex items-center gap-3">
                <i class="bi bi-check-circle-fill text-green-500 text-xl"></i>
                <div class="text-sm text-green-700 font-medium">{{ session("success") }}</div>
            </div>
            <button onclick="this.parentElement.remove()" class="text-green-500 hover:text-green-700"><i class="bi bi-x text-lg"></i></button>
        </div>
    @endif

    {{-- FILTER CARD --}}
    <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-5 mb-6">
        <form action="{{ route('sales-orders.index') }}" method="GET">
            <div class="grid grid-cols-1 md:grid-cols-12 gap-4 items-end">
                
                {{-- 1. PENCARIAN (4 Kolom) --}}
                <div class="md:col-span-4">
                    <label class="block text-xs font-bold text-gray-500 uppercase tracking-wider mb-1">Pencarian</label>
                    <div class="relative">
                        <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                            <i class="bi bi-search text-gray-400"></i>
                        </div>
                        <input type="text" name="search" value="{{ request('search') }}" 
                            class="pl-10 w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 text-sm py-2" 
                            placeholder="No. Pesanan / Klien...">
                    </div>
                </div>

                {{-- 2. TANGGAL (3 Kolom) --}}
                <div class="md:col-span-3">
                    <label class="block text-xs font-bold text-gray-500 uppercase tracking-wider mb-1">Tanggal</label>
                    <input type="date" name="date" value="{{ request('date') }}" 
                        class="w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 text-sm py-2">
                </div>

                {{-- 3. URUTKAN (3 Kolom) --}}
                <div class="md:col-span-3">
                    <label class="block text-xs font-bold text-gray-500 uppercase tracking-wider mb-1">Urutkan</label>
                    <select name="sort" class="w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 text-sm py-2">
                        <option value="terbaru" {{ request('sort', 'terbaru') == 'terbaru' ? 'selected' : '' }}>Terbaru</option>
                        <option value="terlama" {{ request('sort') == 'terlama' ? 'selected' : '' }}>Terlama</option>
                        <option value="klien_az" {{ request('sort') == 'klien_az' ? 'selected' : '' }}>Klien (A-Z)</option>
                        <option value="klien_za" {{ request('sort') == 'klien_za' ? 'selected' : '' }}>Klien (Z-A)</option>
                    </select>
                </div>

                {{-- 4. TOMBOL (2 Kolom) --}}
                <div class="md:col-span-2">
                    <button type="submit" class="w-full bg-gray-800 hover:bg-gray-900 text-white font-medium py-2 px-4 rounded-md shadow-sm transition text-sm flex items-center justify-center gap-2">
                        <i class="bi bi-funnel-fill"></i> Filter
                    </button>
                </div>
            </div>
        </form>
    </div>
    
    {{-- TABEL PESANAN --}}
    <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse">
                <thead class="bg-gray-50 border-b border-gray-200">
                    <tr>
                        <th class="px-6 py-3 text-xs font-bold text-gray-500 uppercase tracking-wider text-center w-12">#</th>
                        <th class="px-6 py-3 text-xs font-bold text-gray-500 uppercase tracking-wider">No. Pesanan</th>
                        <th class="px-6 py-3 text-xs font-bold text-gray-500 uppercase tracking-wider">Klien</th>
                        <th class="px-6 py-3 text-xs font-bold text-gray-500 uppercase tracking-wider">Sales</th>
                        <th class="px-6 py-3 text-xs font-bold text-gray-500 uppercase tracking-wider">Tanggal</th>
                        <th class="px-6 py-3 text-xs font-bold text-gray-500 uppercase tracking-wider text-right">Jumlah</th>
                        <th class="px-6 py-3 text-xs font-bold text-gray-500 uppercase tracking-wider text-center">Status</th>
                        <th class="px-6 py-3 text-xs font-bold text-gray-500 uppercase tracking-wider text-center">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @forelse ($orders as $order)
                        <tr class="hover:bg-gray-50 transition-colors">
                            <td class="px-6 py-4 text-center text-gray-500 text-sm">
                                {{ $loop->iteration + $orders->firstItem() - 1 }}
                            </td>
                            <td class="px-6 py-4">
                                <a href="{{ route('sales-orders.show', $order->order_id) }}" class="text-sm font-bold text-indigo-600 hover:underline font-mono">
                                    {{ $order->order_number }}
                                </a>
                            </td>
                            <td class="px-6 py-4">
                                <div class="flex items-center gap-2">
                                    <div class="w-6 h-6 rounded-full bg-indigo-50 flex items-center justify-center text-indigo-600 border border-indigo-100">
                                        <i class="bi bi-building text-xs"></i>
                                    </div>
                                    <span class="text-sm font-medium text-gray-900">{{ $order->client->client_name ?? "N/A" }}</span>
                                </div>
                            </td>
                            <td class="px-6 py-4 text-sm text-gray-500">
                                {{ $order->sales->full_name ?? "N/A" }}
                            </td>
                            <td class="px-6 py-4 text-sm text-gray-500">
                                {{ $order->order_date->format("d M Y") }}
                            </td>
                            <td class="px-6 py-4 text-right text-sm font-bold text-gray-900">
                                Rp {{ number_format($order->total_amount, 0, ",", ".") }}
                            </td>
                            <td class="px-6 py-4 text-center">
                                @php
                                    $statusColors = [
                                        'pending' => 'bg-yellow-100 text-yellow-800 border-yellow-200',
                                        'approved' => 'bg-cyan-100 text-cyan-800 border-cyan-200',
                                        'rejected' => 'bg-red-100 text-red-800 border-red-200',
                                        'invoiced' => 'bg-green-100 text-green-800 border-green-200',
                                    ];
                                    $colorClass = $statusColors[$order->status] ?? 'bg-gray-100 text-gray-800 border-gray-200';
                                @endphp
                                <span class="px-2.5 py-0.5 rounded-full text-xs font-bold border uppercase {{ $colorClass }}">
                                    {{ str_replace('_', ' ', $order->status) }}
                                </span>
                            </td>
                            <td class="px-6 py-4 text-center">
                                <div class="flex justify-center items-center gap-1">
                                    <a href="{{ route('sales-orders.show', $order->order_id) }}" class="p-1.5 bg-white border border-gray-300 rounded-md text-indigo-600 hover:bg-indigo-50 transition shadow-sm" title="Detail">
                                        <i class="bi bi-eye"></i>
                                    </a>

                                    @if (!in_array($order->status, ['invoiced', 'rejected']))
                                        @can("update", $order)
                                            <a href="{{ route('sales-orders.edit', $order->order_id) }}" class="p-1.5 bg-white border border-gray-300 rounded-md text-yellow-600 hover:bg-yellow-50 transition shadow-sm" title="Edit">
                                                <i class="bi bi-pencil-square"></i>
                                            </a>
                                        @endcan

                                        @can("delete", $order)
                                            <form class="delete-form inline-block" action="{{ route('sales-orders.destroy', $order->order_id) }}" method="POST">
                                                @csrf @method("DELETE")
                                                <button type="submit" class="p-1.5 bg-white border border-gray-300 rounded-md text-red-600 hover:bg-red-50 transition shadow-sm" title="Hapus">
                                                    <i class="bi bi-trash"></i>
                                                </button>
                                            </form>
                                        @endcan
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="px-6 py-12 text-center text-gray-500">
                                <div class="flex flex-col items-center justify-center">
                                    <i class="bi bi-cart-x text-4xl text-gray-300 mb-3"></i>
                                    <p class="text-sm">Belum ada pesanan penjualan.</p>
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    {{-- PAGINATION --}}
    <div class="mt-6">
        {{ $orders->links() }}
    </div>
</div>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    const deleteForms = document.querySelectorAll('.delete-form');
    deleteForms.forEach(form => {
        form.addEventListener('submit', function(event) {
            event.preventDefault();
            Swal.fire({
                title: 'Hapus Pesanan?',
                text: "Data yang dihapus tidak bisa dikembalikan!",
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#ef4444',
                cancelButtonColor: '#6b7280',
                confirmButtonText: 'Ya, Hapus!',
                cancelButtonText: 'Batal'
            }).then((result) => {
                if (result.isConfirmed) form.submit();
            });
        });
    });
});
</script>
@endpush
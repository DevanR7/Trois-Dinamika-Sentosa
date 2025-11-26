@extends("layouts.app")

@section("title", "Daftar Pesanan Penjualan")

@section("content")
<div class="max-w-7xl mx-auto pb-20 animate-enter">

    {{-- HEADER --}}
    <div class="flex flex-col sm:flex-row justify-between items-end gap-4 mb-8">
        <div>
            <h1 class="text-2xl font-bold text-slate-800 tracking-tight">Pesanan Penjualan</h1>
            <p class="text-slate-500 text-sm mt-1">Kelola order masuk dari pelanggan.</p>
        </div>
        <a href="{{ route('sales-orders.create') }}" class="h-[48px] px-8 bg-indigo-600 hover:bg-indigo-700 text-white text-sm font-bold rounded-lg shadow-md transition-all flex items-center justify-center gap-2 group hover:-translate-y-0.5">
            <i class="material-icons text-[20px] group-hover:rotate-90 transition-transform">add</i> 
            <span>Buat Pesanan Baru</span>
        </a>
    </div>

    {{-- NOTIFIKASI --}}
    @push('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            @if(session("success")) window.showToast("{{ session('success') }}", 'success'); @endif
            @if(session("error")) window.showToast("{{ session('error') }}", 'error'); @endif
            
            // Init Select2 Filter
            $('select[name="sort"]').select2({ minimumResultsForSearch: Infinity, width: '100%', dropdownCssClass: 'select2-dropdown-clean' });
        });
    </script>
    @endpush

    {{-- FILTER CARD --}}
    <div class="dashboard-card p-6 mb-6">
        <form action="{{ route('sales-orders.index') }}" method="GET">
            <div class="grid grid-cols-1 md:grid-cols-12 gap-4 items-end">
                
                {{-- 1. PENCARIAN --}}
                <div class="md:col-span-4">
                    <label class="block text-xs font-bold text-slate-500 uppercase mb-1.5">Pencarian</label>
                    <div class="relative">
                        <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                            <i class="material-icons text-slate-400 text-[20px]">search</i>
                        </div>
                        <input type="text" name="search" value="{{ request('search') }}" 
                            class="form-input pl-10" 
                            placeholder="No. Pesanan / Klien...">
                    </div>
                </div>

                {{-- 2. TANGGAL --}}
                <div class="md:col-span-3">
                    <label class="block text-xs font-bold text-slate-500 uppercase mb-1.5">Tanggal</label>
                    <input type="date" name="date" value="{{ request('date') }}" class="form-input">
                </div>

                {{-- 3. URUTKAN --}}
                <div class="md:col-span-3">
                    <label class="block text-xs font-bold text-slate-500 uppercase mb-1.5">Urutkan</label>
                    <select name="sort" class="form-input select2-basic">
                        <option value="terbaru" @selected(request('sort', 'terbaru') == 'terbaru')>Terbaru</option>
                        <option value="terlama" @selected(request('sort') == 'terlama')>Terlama</option>
                        <option value="klien_az" @selected(request('sort') == 'klien_az')>Klien (A-Z)</option>
                        <option value="klien_za" @selected(request('sort') == 'klien_za')>Klien (Z-A)</option>
                    </select>
                </div>

                {{-- 4. TOMBOL --}}
                <div class="md:col-span-2 flex gap-2">
                    <button type="submit" class="flex-1 h-[48px] bg-slate-800 hover:bg-slate-900 text-white font-bold rounded-lg shadow-sm transition-all flex items-center justify-center gap-2">
                        <i class="material-icons text-[18px]">filter_list</i>
                    </button>
                    <a href="{{ route('sales-orders.index') }}" class="h-[48px] w-[48px] flex items-center justify-center bg-white border border-slate-300 text-slate-500 hover:text-indigo-600 font-medium rounded-lg shadow-sm transition" title="Reset">
                        <i class="material-icons text-[20px]">refresh</i>
                    </a>
                </div>
            </div>
        </form>
    </div>
    
    {{-- TABEL PESANAN --}}
    <div class="dashboard-card p-0 overflow-hidden shadow-lg border-0 ring-1 ring-slate-900/5">
        <div class="overflow-x-auto">
            <table class="dashboard-table min-w-full">
                <thead class="bg-slate-50 border-b border-slate-200">
                    <tr>
                        <th class="pl-6 w-32">No. Pesanan</th>
                        <th>Klien</th>
                        <th>Sales</th>
                        <th>Tanggal</th>
                        <th class="text-right">Jumlah</th>
                        <th class="text-center w-28">Status</th>
                        <th class="text-center w-32 pr-6">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 bg-white">
                    @forelse ($orders as $order)
                        <tr class="hover:bg-slate-50/50 transition-colors">
                            <td class="pl-6 py-4">
                                <a href="{{ route('sales-orders.show', $order->order_id) }}" class="text-sm font-bold text-indigo-600 hover:underline font-mono">
                                    {{ $order->order_number }}
                                </a>
                            </td>
                            <td class="py-4">
                                <div class="flex items-center gap-3">
                                    <div class="w-8 h-8 rounded-lg bg-indigo-50 flex items-center justify-center text-indigo-600 border border-indigo-100">
                                        <i class="material-icons text-[14px]">business</i>
                                    </div>
                                    <span class="text-sm font-bold text-slate-800">{{ $order->client->client_name ?? "N/A" }}</span>
                                </div>
                            </td>
                            <td class="py-4 text-sm text-slate-600">
                                {{ $order->sales->full_name ?? "N/A" }}
                            </td>
                            <td class="py-4 text-sm text-slate-600">
                                {{ $order->order_date->format("d M Y") }}
                            </td>
                            <td class="py-4 text-right text-sm font-bold text-slate-800 font-mono">
                                Rp {{ number_format($order->total_amount, 0, ",", ".") }}
                            </td>
                            <td class="py-4 text-center">
                                @php
                                    $statusMap = [
                                        'pending' => ['class' => 'status-pending', 'icon' => 'schedule'],
                                        'approved' => ['class' => 'status-approved', 'icon' => 'check_circle'],
                                        'rejected' => ['class' => 'status-rejected', 'icon' => 'cancel'],
                                        'invoiced' => ['class' => 'status-completed', 'icon' => 'verified'],
                                        'pending_review' => ['class' => 'status-pending', 'icon' => 'rate_review'],
                                    ];
                                    $status = $statusMap[$order->status] ?? ['class' => 'status-draft', 'icon' => 'edit_note'];
                                @endphp
                                <span class="{{ $status['class'] }} flex items-center justify-center gap-1 w-fit mx-auto px-2 py-0.5">
                                    <i class="material-icons text-[12px]">{{ $status['icon'] }}</i>
                                    {{ str_replace('_', ' ', strtoupper($order->status)) }}
                                </span>
                            </td>
                            <td class="pr-6 py-4 text-center">
                                <div class="flex justify-center items-center gap-2">
                                    <a href="{{ route('sales-orders.show', $order->order_id) }}" 
                                       class="w-8 h-8 flex items-center justify-center rounded-lg bg-white border border-slate-200 text-slate-500 hover:text-indigo-600 hover:border-indigo-200 transition shadow-sm" title="Detail">
                                        <i class="material-icons text-[16px]">visibility</i>
                                    </a>

                                    @if (!in_array($order->status, ['invoiced', 'rejected']))
                                        @can("update", $order)
                                            <a href="{{ route('sales-orders.edit', $order->order_id) }}" 
                                               class="w-8 h-8 flex items-center justify-center rounded-lg bg-white border border-slate-200 text-amber-600 hover:bg-amber-50 hover:border-amber-200 transition shadow-sm" title="Edit">
                                                <i class="material-icons text-[16px]">edit</i>
                                            </a>
                                        @endcan

                                        @can("delete", $order)
                                            <form class="delete-form inline-block" action="{{ route('sales-orders.destroy', $order->order_id) }}" method="POST">
                                                @csrf @method("DELETE")
                                                <button type="submit" 
                                                        data-name="{{ $order->order_number }}"
                                                        class="w-8 h-8 flex items-center justify-center rounded-lg bg-white border border-slate-200 text-slate-500 hover:text-red-600 hover:border-red-200 transition shadow-sm" 
                                                        title="Hapus">
                                                    <i class="material-icons text-[16px]">delete</i>
                                                </button>
                                            </form>
                                        @endcan
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="px-6 py-16 text-center">
                                <div class="flex flex-col items-center justify-center text-slate-400">
                                    <div class="w-16 h-16 bg-slate-50 rounded-full flex items-center justify-center mb-4">
                                        <i class="material-icons text-4xl opacity-30">shopping_cart</i>
                                    </div>
                                    <h3 class="text-base font-bold text-slate-700">Belum ada pesanan</h3>
                                    <p class="text-sm mt-1">Buat pesanan penjualan baru untuk memulai.</p>
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <div class="mt-6">
        {{ $orders->appends(request()->query())->links() }}
    </div>
</div>
@endsection
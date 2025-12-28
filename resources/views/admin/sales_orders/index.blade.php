@extends('admin.layouts.app')

@section('title', 'Pesanan Penjualan')

@section('content')

    {{-- HEADER & ACTIONS --}}
    <div class="page-header">
        <div>
            <h1 class="page-title">Pesanan Penjualan (SO)</h1>
            <p class="page-subtitle">Daftar pesanan masuk dari pelanggan.</p>
        </div>
        <div class="flex items-center gap-3">
            <a href="{{ route('admin.sales-orders.create') }}" class="btn btn-primary">
                <i class="material-icons text-sm mr-1">add_shopping_cart</i> Buat Pesanan
            </a>
        </div>
    </div>

    {{-- FILTER --}}
    <div class="card mb-6">
        <div class="card-body">
            <form action="{{ route('admin.sales-orders.index') }}" method="GET" class="grid grid-cols-1 md:grid-cols-12 gap-4">
                
                {{-- Search --}}
                <div class="md:col-span-4 lg:col-span-5">
                    <div class="input-group">
                        <span class="input-group-text bg-white dark:bg-slate-800">
                            <i class="material-icons text-slate-400">search</i>
                        </span>
                        <input type="text" name="search" class="form-input border-l-0 pl-0" 
                               placeholder="No. Order atau Nama Klien..." 
                               value="{{ request('search') }}">
                    </div>
                </div>

                {{-- Date --}}
                <div class="md:col-span-3 lg:col-span-3">
                    <input type="date" name="date" class="form-input" 
                           value="{{ request('date') }}" 
                           onchange="this.form.submit()">
                </div>

                {{-- Sort --}}
                <div class="md:col-span-3 lg:col-span-2">
                    <select name="sort" class="tom-select" onchange="this.form.submit()">
                        <option value="terbaru" {{ request('sort') == 'terbaru' ? 'selected' : '' }}>Terbaru</option>
                        <option value="terlama" {{ request('sort') == 'terlama' ? 'selected' : '' }}>Terlama</option>
                        <option value="klien_az" {{ request('sort') == 'klien_az' ? 'selected' : '' }}>Klien (A-Z)</option>
                    </select>
                </div>

                {{-- Reset --}}
                <div class="md:col-span-2 lg:col-span-2 flex items-end">
                    <a href="{{ route('admin.sales-orders.index') }}" class="btn btn-secondary w-full justify-center">
                        Reset
                    </a>
                </div>

            </form>
        </div>
    </div>

    {{-- TABLE DATA --}}
    <div class="card">
        <div class="table-container">
            <table class="table-modern">
                <thead>
                    <tr>
                        <th>No. Order</th>
                        <th>Tanggal</th>
                        <th>Pelanggan</th>
                        <th>Sales</th>
                        <th class="text-right">Total Nominal</th>
                        <th class="text-center">Status</th>
                        <th class="text-center w-36">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($orders as $order)
                        <tr>
                            <td>
                                <a href="{{ route('admin.sales-orders.show', $order->order_id) }}" class="font-bold text-indigo-600 hover:underline font-mono">
                                    {{ $order->order_number }}
                                </a>
                            </td>
                            <td>
                                <div class="text-sm text-slate-700 dark:text-slate-200">
                                    {{ $order->order_date->format('d M Y') }}
                                </div>
                            </td>
                            <td>
                                <div class="font-medium text-slate-700 dark:text-slate-200">
                                    {{ Str::limit($order->client->client_name ?? '-', 20) }}
                                </div>
                            </td>
                            <td>
                                <div class="text-xs text-slate-500">
                                    {{ Str::limit($order->sales->full_name ?? '-', 15) }}
                                </div>
                            </td>
                            <td class="text-right font-bold text-slate-700 dark:text-white">
                                Rp {{ number_format($order->total_amount, 0, ',', '.') }}
                            </td>
                            <td class="text-center">
                                @php
                                    $statusClass = match($order->status) {
                                        'pending' => 'badge-warning',
                                        'approved' => 'badge-primary',
                                        'invoiced' => 'badge-success',
                                        'rejected' => 'badge-danger',
                                        'pending_review' => 'badge-info',
                                        default => 'badge-secondary'
                                    };
                                    $statusLabel = match($order->status) {
                                        'pending_review' => 'Review Klien',
                                        'invoiced' => 'Faktur Dibuat',
                                        default => ucfirst($order->status)
                                    };
                                @endphp
                                <span class="badge {{ $statusClass }}">{{ $statusLabel }}</span>
                            </td>
                            <td class="text-center">
                                <div class="flex items-center justify-center gap-2">
                                    
                                    {{-- Detail --}}
                                    <a href="{{ route('admin.sales-orders.show', $order->order_id) }}" 
                                       class="w-8 h-8 rounded-full flex items-center justify-center bg-white border border-slate-200 text-slate-500 hover:text-indigo-600 hover:border-indigo-300 transition-colors shadow-sm dark:bg-slate-800 dark:border-slate-700 dark:text-slate-400"
                                       title="Lihat Detail">
                                        <i class="material-icons text-[18px] leading-none">visibility</i>
                                    </a>

                                    @if($order->status == 'pending')
                                        {{-- Edit --}}
                                        <a href="{{ route('admin.sales-orders.edit', $order->order_id) }}" 
                                           class="w-8 h-8 rounded-full flex items-center justify-center bg-indigo-50 border border-transparent text-indigo-600 hover:bg-indigo-600 hover:text-white transition-colors shadow-sm dark:bg-indigo-900/30 dark:text-indigo-400"
                                           title="Edit">
                                            <i class="material-icons text-[18px] leading-none">edit</i>
                                        </a>

                                        {{-- Delete --}}
                                        <button type="button" onclick="confirmDelete('{{ $order->order_id }}', '{{ $order->order_number }}')" 
                                                class="w-8 h-8 rounded-full flex items-center justify-center bg-rose-50 border border-transparent text-rose-600 hover:bg-rose-600 hover:text-white transition-colors shadow-sm dark:bg-rose-900/30 dark:text-rose-400"
                                                title="Hapus">
                                            <i class="material-icons text-[18px] leading-none">delete</i>
                                        </button>
                                        
                                        <form id="delete-form-{{ $order->order_id }}" 
                                              action="{{ route('admin.sales-orders.destroy', $order->order_id) }}" 
                                              method="POST" class="hidden">
                                            @csrf
                                            @method('DELETE')
                                        </form>
                                    @endif

                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="text-center p-8">
                                <div class="flex flex-col items-center justify-center text-slate-400">
                                    <i class="material-icons text-5xl mb-2">shopping_cart</i>
                                    <span>Belum ada pesanan penjualan.</span>
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        
        <div class="card-footer">
            {{ $orders->links() }}
        </div>
    </div>

@endsection

@push('scripts')
<script>
    function confirmDelete(id, number) {
        window.confirmDialog({
            title: 'Hapus Pesanan?',
            text: "Pesanan #" + number + " akan dihapus permanen.",
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#ef4444',
            cancelButtonColor: '#64748b',
            confirmButtonText: 'Ya, Hapus!',
            cancelButtonText: 'Batal'
        }).then((result) => {
            if (result.isConfirmed) {
                document.getElementById('delete-form-' + id).submit();
            }
        });
    }
</script>
@endpush
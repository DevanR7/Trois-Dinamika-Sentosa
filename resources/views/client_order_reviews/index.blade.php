@extends('layouts.app')

@section('title', 'Review Pesanan Online')

@section('content')
<div class="max-w-7xl mx-auto">
    
    {{-- HEADER --}}
    <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center mb-6 gap-4">
        <div>
            <h2 class="text-2xl font-bold text-gray-900 tracking-tight">Review Pesanan Online</h2>
            <p class="text-sm text-gray-500 mt-1">Validasi pesanan masuk dari portal klien.</p>
        </div>
    </div>

    {{-- NOTIFIKASI --}}
    @if (session('success'))
        <div class="mb-6 bg-green-50 border-l-4 border-green-500 p-4 rounded-r shadow-sm flex justify-between items-center">
            <div class="flex items-center gap-3">
                <i class="bi bi-check-circle-fill text-green-500 text-xl"></i>
                <div class="text-sm text-green-700 font-medium">{{ session('success') }}</div>
            </div>
            <button onclick="this.parentElement.remove()" class="text-green-500 hover:text-green-700"><i class="bi bi-x text-lg"></i></button>
        </div>
    @endif

    {{-- TABS NAVIGASI --}}
    <div class="border-b border-gray-200 mb-6">
        <nav class="-mb-px flex gap-6" aria-label="Tabs">
            @php $pendingCount = \App\Models\Order::where('order_source', 'client')->where('status', 'pending_review')->count(); @endphp
            
            <a href="{{ route('client-order-reviews.index', ['view' => 'pending']) }}" 
               class="whitespace-nowrap py-4 px-1 border-b-2 font-medium text-sm flex items-center gap-2 {{ $view == 'pending' ? 'border-indigo-500 text-indigo-600' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300' }}">
                <i class="bi bi-hourglass-split"></i> Menunggu Review
                @if($pendingCount > 0)
                    <span class="bg-red-100 text-red-600 py-0.5 px-2.5 rounded-full text-xs font-bold ml-1">{{ $pendingCount }}</span>
                @endif
            </a>

            <a href="{{ route('client-order-reviews.index', ['view' => 'history']) }}" 
               class="whitespace-nowrap py-4 px-1 border-b-2 font-medium text-sm flex items-center gap-2 {{ $view == 'history' ? 'border-indigo-500 text-indigo-600' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300' }}">
                <i class="bi bi-clock-history"></i> Riwayat Proses
            </a>
        </nav>
    </div>

    {{-- FILTER CARD --}}
    <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-5 mb-6">
        <form action="{{ route('client-order-reviews.index') }}" method="GET">
            <input type="hidden" name="view" value="{{ $view }}">
            <div class="grid grid-cols-1 md:grid-cols-12 gap-4 items-end">
                
                {{-- Search --}}
                <div class="md:col-span-4">
                    <label class="block text-xs font-bold text-gray-500 uppercase tracking-wider mb-1">Pencarian</label>
                    <div class="relative">
                        <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                            <i class="bi bi-search text-gray-400"></i>
                        </div>
                        <input type="text" name="search" value="{{ request('search') }}" 
                            class="pl-10 w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 text-sm py-2" 
                            placeholder="Cari No. Order / Klien...">
                    </div>
                </div>

                {{-- Date Filter --}}
                <div class="md:col-span-3">
                    <label class="block text-xs font-bold text-gray-500 uppercase tracking-wider mb-1">Periode</label>
                    <select name="date_filter" class="w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 text-sm py-2">
                        <option value="">-- Semua Periode --</option>
                        @foreach($uniqueDates as $ym => $dateLabel)
                            <option value="{{ $ym }}" @selected(request('date_filter') == $ym)>{{ $dateLabel }}</option>
                        @endforeach
                    </select>
                </div>

                {{-- Status Filter (History Only) --}}
                @if($view == 'history')
                <div class="md:col-span-2">
                    <label class="block text-xs font-bold text-gray-500 uppercase tracking-wider mb-1">Status</label>
                    <select name="status_filter" class="w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 text-sm py-2">
                        <option value="">-- Semua --</option>
                        <option value="invoiced" @selected(request('status_filter') == 'invoiced')>Disetujui</option>
                        <option value="rejected" @selected(request('status_filter') == 'rejected')>Ditolak</option>
                    </select>
                </div>
                @endif

                {{-- Sort & Button --}}
                <div class="md:col-span-{{ $view == 'history' ? '3' : '5' }} flex gap-2">
                    <select name="sort" class="w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 text-sm py-2">
                        <option value="terbaru" @selected(request('sort', 'terbaru') == 'terbaru')>Terbaru</option>
                        <option value="terlama" @selected(request('sort') == 'terlama')>Terlama</option>
                    </select>
                    <button type="submit" class="bg-gray-800 hover:bg-gray-900 text-white font-medium py-2 px-4 rounded-md shadow-sm transition text-sm flex items-center justify-center gap-2">
                        <i class="bi bi-funnel-fill"></i>
                    </button>
                    <a href="{{ route('client-order-reviews.index', ['view' => $view]) }}" class="bg-white border border-gray-300 text-gray-500 hover:text-indigo-600 font-medium py-2 px-3 rounded-md shadow-sm transition flex items-center justify-center" title="Reset">
                        <i class="bi bi-arrow-clockwise text-lg"></i>
                    </a>
                </div>
            </div>
        </form>
    </div>

    {{-- LIST CARD --}}
    <div class="flex flex-col gap-4">
        @forelse ($clientOrders as $order)
            @php
                $borderColor = $order->status == 'pending_review' ? 'border-l-blue-500' : ($order->status == 'rejected' ? 'border-l-red-500' : 'border-l-green-500');
            @endphp

            <div class="bg-white rounded-lg shadow-sm border border-gray-200 overflow-hidden border-l-4 {{ $borderColor }} hover:shadow-md transition-shadow">
                
                {{-- HEADER CARD --}}
                <div class="p-5 cursor-pointer hover:bg-gray-50 transition-colors" onclick="toggleAccordion('collapse-{{ $order->order_id }}')">
                    <div class="flex flex-col lg:flex-row lg:items-center justify-between gap-6">
                        
                        {{-- Info Utama --}}
                        <div class="flex items-center gap-4 lg:w-1/3">
                            <div class="w-12 h-12 rounded-full bg-indigo-50 flex items-center justify-center text-indigo-600 flex-shrink-0 border border-indigo-100">
                                <i class="bi bi-globe text-xl"></i>
                            </div>
                            <div>
                                <h3 class="text-base font-bold text-gray-900 mb-0.5">{{ $order->order_number }}</h3>
                                <p class="text-sm font-medium text-indigo-600">{{ $order->client->client_name ?? 'Klien Dihapus' }}</p>
                            </div>
                        </div>

                        {{-- Data --}}
                        <div class="flex gap-8 lg:w-1/3">
                            <div>
                                <span class="block text-[10px] font-bold text-gray-500 uppercase mb-1">Tanggal</span>
                                <span class="text-sm font-medium text-gray-900">{{ $order->order_date->format('d M Y') }}</span>
                            </div>
                            <div>
                                <span class="block text-[10px] font-bold text-gray-500 uppercase mb-1">Total</span>
                                <span class="text-sm font-bold text-gray-900">Rp {{ number_format($order->total_amount, 0, ',', '.') }}</span>
                            </div>
                        </div>

                        {{-- Status & Icon --}}
                        <div class="flex items-center justify-between lg:justify-end gap-4 lg:w-1/3">
                            @php
                                $statusStyles = [
                                    'pending_review' => 'bg-blue-100 text-blue-700 border-blue-200',
                                    'approved' => 'bg-green-100 text-green-700 border-green-200',
                                    'invoiced' => 'bg-green-100 text-green-700 border-green-200',
                                    'rejected' => 'bg-red-100 text-red-700 border-red-200',
                                ];
                                $labelStatus = [
                                    'pending_review' => 'Menunggu Review',
                                    'approved' => 'Disetujui',
                                    'invoiced' => 'Sudah Invoiced',
                                    'rejected' => 'Ditolak',
                                ];
                            @endphp
                            <span class="px-3 py-1 rounded-full text-xs font-bold border uppercase {{ $statusStyles[$order->status] ?? 'bg-gray-100 text-gray-600' }}">
                                {{ $labelStatus[$order->status] ?? $order->status }}
                            </span>
                            
                            <i class="bi bi-chevron-down text-gray-400 transition-transform duration-200" id="icon-collapse-{{ $order->order_id }}"></i>
                        </div>
                    </div>
                </div>

                {{-- COLLAPSE BODY --}}
                <div id="collapse-{{ $order->order_id }}" class="hidden bg-gray-50 border-t border-gray-100">
                    <div class="p-5">
                        <div class="flex flex-col sm:flex-row justify-between items-center gap-4">
                            <div class="text-sm text-gray-600 flex gap-4">
                                <span><strong class="text-gray-900">Item:</strong> {{ $order->items->count() }} Produk</span>
                                @if($order->notes)
                                    <span class="italic text-gray-500 border-l pl-4 border-gray-300">{{ Str::limit($order->notes, 60) }}</span>
                                @endif
                            </div>
                            
                            <a href="{{ route('client-order-reviews.show', $order->order_id) }}" class="px-4 py-2 bg-white border border-indigo-200 text-indigo-700 font-medium rounded-lg hover:bg-indigo-50 hover:border-indigo-300 transition text-sm shadow-sm">
                                <i class="bi bi-eye mr-1"></i> {{ $view == 'pending' ? 'Review Sekarang' : 'Lihat Detail' }}
                            </a>
                        </div>
                    </div>
                </div>

            </div>
        @empty
            <div class="text-center py-12 bg-white rounded-xl border border-dashed border-gray-300">
                <div class="w-16 h-16 bg-gray-100 rounded-full flex items-center justify-center mx-auto mb-4">
                    <i class="bi bi-inbox text-3xl text-gray-400"></i>
                </div>
                <h3 class="text-lg font-medium text-gray-900">Tidak ada pesanan</h3>
                <p class="text-gray-500 text-sm mt-1">
                    @if($view == 'pending') Belum ada pesanan baru yang perlu direview.
                    @else Tidak ada riwayat pesanan yang sesuai filter. @endif
                </p>
            </div>
        @endforelse
    </div>

    <div class="mt-6">
        {{ $clientOrders->appends(request()->query())->links() }}
    </div>
</div>
@endsection

@push('scripts')
<script>
    function toggleAccordion(id) {
        const el = document.getElementById(id);
        const icon = document.getElementById('icon-' + id);
        if(el.classList.contains('hidden')) {
            el.classList.remove('hidden');
            if(icon) icon.classList.add('rotate-180');
        } else {
            el.classList.add('hidden');
            if(icon) icon.classList.remove('rotate-180');
        }
    }
</script>
@endpush
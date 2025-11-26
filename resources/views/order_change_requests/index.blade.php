@extends('layouts.app')

@section('title', 'Review Pesanan Online')

@section('content')
<div class="max-w-7xl mx-auto pb-20 animate-enter">
    
    {{-- HEADER --}}
    <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center mb-8 gap-4">
        <div>
            <h1 class="text-2xl font-bold text-slate-800 tracking-tight">Review Pesanan Online</h1>
            <p class="text-slate-500 text-sm mt-1">Validasi pesanan masuk dari portal klien.</p>
        </div>
    </div>

    {{-- TABS NAVIGASI --}}
    <div class="border-b border-slate-200 mb-6">
        <nav class="-mb-px flex gap-6" aria-label="Tabs">
            @php 
                $pendingCount = \App\Models\Order::where('order_source', 'client')->where('status', 'pending_review')->count(); 
            @endphp
            
            <a href="{{ route('client-order-reviews.index', ['view' => 'pending']) }}" 
               class="whitespace-nowrap py-4 px-1 border-b-2 font-medium text-sm flex items-center gap-2 transition-colors
               {{ $view == 'pending' ? 'border-indigo-600 text-indigo-600' : 'border-transparent text-slate-500 hover:text-slate-700 hover:border-slate-300' }}">
                <i class="material-icons text-lg">schedule</i> Menunggu Review
                @if($pendingCount > 0)
                    <span class="bg-red-100 text-red-600 py-0.5 px-2.5 rounded-full text-xs font-bold ml-1">{{ $pendingCount }}</span>
                @endif
            </a>

            <a href="{{ route('client-order-reviews.index', ['view' => 'history']) }}" 
               class="whitespace-nowrap py-4 px-1 border-b-2 font-medium text-sm flex items-center gap-2 transition-colors
               {{ $view == 'history' ? 'border-indigo-600 text-indigo-600' : 'border-transparent text-slate-500 hover:text-slate-700 hover:border-slate-300' }}">
                <i class="material-icons text-lg">history</i> Riwayat Proses
            </a>
        </nav>
    </div>

    {{-- FILTER CARD --}}
    <div class="dashboard-card p-6 mb-6 shadow-sm">
        <form action="{{ route('client-order-reviews.index') }}" method="GET">
            <input type="hidden" name="view" value="{{ $view }}">
            <div class="grid grid-cols-1 md:grid-cols-12 gap-4 items-end">
                
                {{-- Search --}}
                <div class="md:col-span-4">
                    <label class="block text-xs font-bold text-slate-500 uppercase mb-1.5">Pencarian</label>
                    <div class="relative">
                        <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                            <i class="material-icons text-slate-400 text-[20px]">search</i>
                        </div>
                        <input type="text" name="search" value="{{ request('search') }}" 
                            class="form-input pl-10" 
                            placeholder="No. Order / Klien...">
                    </div>
                </div>

                {{-- Date Filter --}}
                <div class="md:col-span-3">
                    <label class="block text-xs font-bold text-slate-500 uppercase mb-1.5">Periode</label>
                    <select name="date_filter" class="form-input select2-basic">
                        <option value="">-- Semua Periode --</option>
                        @foreach($uniqueDates as $ym => $dateLabel)
                            <option value="{{ $ym }}" @selected(request('date_filter') == $ym)>{{ $dateLabel }}</option>
                        @endforeach
                    </select>
                </div>

                {{-- Status Filter (History Only) --}}
                @if($view == 'history')
                <div class="md:col-span-2">
                    <label class="block text-xs font-bold text-slate-500 uppercase mb-1.5">Status</label>
                    <select name="status_filter" class="form-input select2-basic">
                        <option value="">-- Semua --</option>
                        <option value="invoiced" @selected(request('status_filter') == 'invoiced')>Disetujui</option>
                        <option value="rejected" @selected(request('status_filter') == 'rejected')>Ditolak</option>
                    </select>
                </div>
                @endif

                {{-- Sort & Button --}}
                <div class="md:col-span-{{ $view == 'history' ? '3' : '5' }} flex gap-2">
                    <div class="flex-1">
                         @if($view != 'history') <label class="block text-xs font-bold text-slate-500 uppercase mb-1.5">Urutkan</label> @endif
                        <select name="sort" class="form-input select2-basic">
                            <option value="terbaru" @selected(request('sort', 'terbaru') == 'terbaru')>Terbaru</option>
                            <option value="terlama" @selected(request('sort') == 'terlama')>Terlama</option>
                        </select>
                    </div>
                    <button type="submit" class="h-[48px] bg-slate-800 hover:bg-slate-900 text-white font-bold py-2 px-4 rounded-lg shadow-sm transition text-sm flex items-center justify-center gap-2 flex-shrink-0">
                        <i class="material-icons text-[18px]">filter_list</i>
                    </button>
                    <a href="{{ route('client-order-reviews.index', ['view' => $view]) }}" class="h-[48px] w-[48px] flex items-center justify-center bg-white border border-slate-300 text-slate-500 hover:text-indigo-600 font-medium rounded-lg shadow-sm transition flex-shrink-0" title="Reset">
                        <i class="material-icons text-[20px]">refresh</i>
                    </a>
                </div>
            </div>
        </form>
    </div>

    {{-- LIST CARD --}}
    <div class="flex flex-col gap-4">
        @forelse ($clientOrders as $order)
            @php
                $statusMap = [
                    'pending_review' => ['style' => 'status-pending', 'icon' => 'schedule', 'label' => 'Menunggu Review'],
                    'approved' => ['style' => 'status-approved', 'icon' => 'check_circle', 'label' => 'Disetujui'],
                    'invoiced' => ['style' => 'status-completed', 'icon' => 'verified', 'label' => 'Invoiced'],
                    'rejected' => ['style' => 'status-rejected', 'icon' => 'cancel', 'label' => 'Ditolak'],
                ];
                $statusData = $statusMap[$order->status] ?? ['style' => 'status-draft', 'icon' => 'edit_note', 'label' => 'Draft'];
                
                // Border Color Logic
                $borderColor = match($order->status) {
                    'pending_review' => 'border-l-amber-400',
                    'rejected' => 'border-l-red-500',
                    'invoiced', 'approved' => 'border-l-emerald-500',
                    default => 'border-l-slate-300'
                };
            @endphp

            <div class="dashboard-card p-0 overflow-hidden border-l-4 {{ $borderColor }} hover:shadow-lg transition-shadow">
                
                {{-- HEADER CARD (ACORDION TRIGGER) --}}
                <div class="p-5 cursor-pointer hover:bg-slate-50/50 transition-colors relative z-10 bg-white" onclick="window.toggleAccordion('collapse-{{ $order->order_id }}')">
                    <div class="flex flex-col lg:flex-row lg:items-center justify-between gap-4">
                        
                        {{-- Info Utama --}}
                        <div class="flex items-center gap-4 lg:w-1/3">
                            <div class="w-12 h-12 rounded-xl bg-indigo-50 flex items-center justify-center text-indigo-600 flex-shrink-0 border border-indigo-100">
                                <i class="material-icons text-xl">dvr</i>
                            </div>
                            <div>
                                <h3 class="text-base font-bold text-slate-800 mb-0.5 hover:text-indigo-600 transition-colors">{{ $order->order_number }}</h3>
                                <p class="text-sm font-medium text-indigo-600">{{ $order->client->client_name ?? 'Klien Dihapus' }}</p>
                            </div>
                        </div>

                        {{-- Data --}}
                        <div class="flex gap-8 lg:w-1/3 border-l border-slate-100 pl-0 lg:pl-6 border-l-0 lg:border-l">
                            <div>
                                <span class="block text-[10px] font-bold text-slate-400 uppercase mb-1 tracking-wider">Tanggal Pesan</span>
                                <span class="text-sm font-bold text-slate-700">{{ $order->order_date->format('d M Y') }}</span>
                            </div>
                            <div>
                                <span class="block text-[10px] font-bold text-slate-400 uppercase mb-1 tracking-wider">Total</span>
                                <span class="text-sm font-bold text-slate-800 font-mono">Rp {{ number_format($order->total_amount, 0, ',', '.') }}</span>
                            </div>
                        </div>

                        {{-- Status & Icon --}}
                        <div class="flex items-center justify-between lg:justify-end gap-4 lg:w-1/3">
                            <span class="{{ $statusData['style'] }} flex items-center px-3 py-1 rounded-md text-xs font-bold uppercase tracking-wide border">
                                <i class="material-icons text-[14px] mr-1.5">{{ $statusData['icon'] }}</i>
                                {{ $statusData['label'] }}
                            </span>
                            
                            <i class="material-icons text-slate-400 transition-transform duration-300" id="icon-collapse-{{ $order->order_id }}">expand_more</i>
                        </div>
                    </div>
                </div>

                {{-- COLLAPSE BODY --}}
                <div id="wrapper-collapse-{{ $order->order_id }}" class="grid grid-rows-[0fr] transition-[grid-template-rows] duration-300 ease-out bg-slate-50 border-t border-slate-100">
                    <div class="overflow-hidden">
                        <div class="p-6">
                            <div class="flex flex-col md:flex-row justify-between items-start md:items-center gap-4">
                                <div class="text-sm text-slate-600 flex flex-col sm:flex-row gap-2 sm:gap-6">
                                    <span class="flex items-center gap-2">
                                        <i class="material-icons text-slate-400 text-[16px]">inventory_2</i>
                                        <strong>{{ $order->items->count() }}</strong> Produk
                                    </span>
                                    
                                    @if($order->notes)
                                        <span class="flex items-center gap-2 italic text-slate-500 border-l border-slate-300 pl-0 sm:pl-6 border-l-0 sm:border-l">
                                            <i class="material-icons text-[16px]">sticky_note_2</i>
                                            {{ Str::limit($order->notes, 60) }}
                                        </span>
                                    @endif
                                </div>
                                
                                <div class="flex gap-2 w-full md:w-auto">
                                     <a href="{{ route('client-order-reviews.show', $order->order_id) }}" class="px-5 py-2.5 bg-white border border-indigo-200 text-indigo-700 font-bold rounded-lg hover:bg-indigo-50 transition text-sm shadow-sm flex items-center justify-center gap-2 flex-1 md:flex-none">
                                        <i class="material-icons text-[18px]">visibility</i> {{ $view == 'pending' ? 'Review Sekarang' : 'Lihat Detail' }}
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

            </div>
        @empty
            <div class="empty-state py-16">
                <div class="empty-state-icon">
                    <i class="material-icons text-4xl">inbox</i>
                </div>
                <h3 class="text-lg font-medium text-slate-800">Tidak ada pesanan</h3>
                <p class="text-slate-500 text-sm mt-1 max-w-sm mx-auto">
                    @if($view == 'pending') Belum ada pesanan baru yang perlu direview saat ini.
                    @else Tidak ada riwayat pesanan yang sesuai dengan filter Anda. @endif
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
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
<script>
    // Pindahkan ke app.js global jika belum ada
    window.toggleAccordion = function(id) {
        const wrapper = document.getElementById('wrapper-' + id);
        const icon = document.getElementById('icon-' + id);
        
        if (!wrapper) return;

        if (wrapper.classList.contains('grid-rows-[0fr]')) {
            wrapper.classList.remove('grid-rows-[0fr]');
            wrapper.classList.add('grid-rows-[1fr]');
            if(icon) icon.style.transform = 'rotate(180deg)';
        } else {
            wrapper.classList.remove('grid-rows-[1fr]');
            wrapper.classList.add('grid-rows-[0fr]');
            if(icon) icon.style.transform = 'rotate(0deg)';
        }
    };

    document.addEventListener('DOMContentLoaded', function() {
        @if(session('success')) window.showToast("{{ session('success') }}", 'success'); @endif
        @if(session('error')) window.showToast("{{ session('error') }}", 'error'); @endif

        $('.select2-basic').select2({ minimumResultsForSearch: Infinity, width: '100%', dropdownCssClass: 'select2-dropdown-clean' });
    });
</script>
@endpush
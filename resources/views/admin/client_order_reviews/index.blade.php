@extends('admin.layouts.app')

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

    {{-- TABS NAVIGASI MODERN --}}
    <div class="mb-8">
        <div class="inline-flex p-1 bg-slate-100 rounded-xl border border-slate-200">
            @php 
                $pendingCount = \App\Models\Order::where('order_source', 'client')->where('status', 'pending_review')->count(); 
            @endphp
            
            <a href="{{ route('admin.client-order-reviews.index', ['view' => 'pending']) }}" 
               class="flex items-center gap-2 px-5 py-2.5 rounded-lg text-sm font-bold transition-all duration-200 
               {{ $view == 'pending' ? 'bg-white text-indigo-600 shadow-sm' : 'text-slate-500 hover:text-slate-700' }}">
                <i class="material-icons text-[18px]">schedule</i> 
                Menunggu Review
                @if($pendingCount > 0)
                    <span class="bg-red-500 text-white py-0.5 px-2 rounded-full text-[10px] font-bold shadow-sm ml-1">{{ $pendingCount }}</span>
                @endif
            </a>

            <a href="{{ route('admin.client-order-reviews.index', ['view' => 'history']) }}" 
               class="flex items-center gap-2 px-5 py-2.5 rounded-lg text-sm font-bold transition-all duration-200 
               {{ $view == 'history' ? 'bg-white text-indigo-600 shadow-sm' : 'text-slate-500 hover:text-slate-700' }}">
                <i class="material-icons text-[18px]">history</i> 
                Riwayat Proses
            </a>
        </div>
    </div>

    {{-- FILTER CARD --}}
    <div class="dashboard-card p-6 mb-8 shadow-sm border border-slate-100">
        <form action="{{ route('admin.client-order-reviews.index') }}" method="GET">
            <input type="hidden" name="view" value="{{ $view }}">
            <div class="grid grid-cols-1 md:grid-cols-12 gap-4 items-end">
                
                {{-- Search --}}
                <div class="md:col-span-4">
                    <label class="block text-xs font-bold text-slate-500 uppercase mb-1.5 tracking-wide">Pencarian</label>
                    <div class="relative group">
                        <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                            <i class="material-icons text-slate-400 group-focus-within:text-indigo-500 transition-colors text-[20px]">search</i>
                        </div>
                        <input type="text" name="search" value="{{ request('search') }}" 
                            class="form-input pl-10 transition-all focus:ring-2 focus:ring-indigo-100" 
                            placeholder="No. Order / Klien...">
                    </div>
                </div>

                {{-- Date Filter --}}
                <div class="md:col-span-3">
                    <label class="block text-xs font-bold text-slate-500 uppercase mb-1.5 tracking-wide">Periode</label>
                    <select name="date_filter" class="form-input select2-basic" data-placeholder="-- Semua Periode --">
                        <option value=""></option>
                        @foreach($uniqueDates as $ym => $dateLabel)
                            <option value="{{ $ym }}" @selected(request('date_filter') == $ym)>{{ $dateLabel }}</option>
                        @endforeach
                    </select>
                </div>

                {{-- Status Filter (History Only) --}}
                @if($view == 'history')
                <div class="md:col-span-3">
                    <label class="block text-xs font-bold text-slate-500 uppercase mb-1.5 tracking-wide">Status</label>
                    <select name="status_filter" class="form-input select2-basic" data-placeholder="-- Semua Status --">
                        <option value=""></option>
                        <option value="invoiced" @selected(request('status_filter') == 'invoiced')>Disetujui (Invoiced)</option>
                        <option value="approved" @selected(request('status_filter') == 'approved')>Disetujui (Approved)</option>
                        <option value="rejected" @selected(request('status_filter') == 'rejected')>Ditolak</option>
                    </select>
                </div>
                @endif

                {{-- Sort & Button --}}
                <div class="md:col-span-{{ $view == 'history' ? '2' : '5' }} flex gap-2">
                    @if($view != 'history') 
                    <div class="flex-1">
                        <label class="block text-xs font-bold text-slate-500 uppercase mb-1.5 tracking-wide">Urutkan</label>
                        <select name="sort" class="form-input select2-basic">
                            <option value="terbaru" @selected(request('sort', 'terbaru') == 'terbaru')>Terbaru</option>
                            <option value="terlama" @selected(request('sort') == 'terlama')>Terlama</option>
                        </select>
                    </div>
                    @endif
                    
                    <button type="submit" class="h-[42px] bg-slate-800 hover:bg-slate-900 text-white font-bold py-2 px-4 rounded-lg shadow-sm transition text-sm flex items-center justify-center gap-2 flex-shrink-0 transform active:scale-95 mt-auto">
                        <i class="material-icons text-[18px]">filter_list</i>
                    </button>
                    <a href="{{ route('admin.client-order-reviews.index', ['view' => $view]) }}" class="h-[42px] w-[42px] flex items-center justify-center bg-white border border-slate-300 text-slate-500 hover:text-indigo-600 hover:border-indigo-300 font-medium rounded-lg shadow-sm transition flex-shrink-0 mt-auto" title="Reset Filter">
                        <i class="material-icons text-[20px]">refresh</i>
                    </a>
                </div>
            </div>
        </form>
    </div>

    {{-- LIST CARD --}}
    <div class="flex flex-col gap-5">
        @forelse ($clientOrders as $order)
            @php
                $statusConfig = match($order->status) {
                    'pending_review' => ['bg' => 'bg-amber-50', 'text' => 'text-amber-700', 'border' => 'border-amber-200', 'icon' => 'hourglass_empty', 'label' => 'Menunggu Review', 'left_border' => 'border-l-amber-400'],
                    'approved' => ['bg' => 'bg-indigo-50', 'text' => 'text-indigo-700', 'border' => 'border-indigo-200', 'icon' => 'check_circle', 'label' => 'Disetujui', 'left_border' => 'border-l-indigo-500'],
                    'invoiced' => ['bg' => 'bg-emerald-50', 'text' => 'text-emerald-700', 'border' => 'border-emerald-200', 'icon' => 'verified', 'label' => 'Tagihan Terbit', 'left_border' => 'border-l-emerald-500'],
                    'rejected' => ['bg' => 'bg-red-50', 'text' => 'text-red-700', 'border' => 'border-red-200', 'icon' => 'cancel', 'label' => 'Ditolak', 'left_border' => 'border-l-red-500'],
                    default => ['bg' => 'bg-slate-50', 'text' => 'text-slate-600', 'border' => 'border-slate-200', 'icon' => 'help', 'label' => $order->status, 'left_border' => 'border-l-slate-300'],
                };
            @endphp

            <div class="dashboard-card p-0 overflow-hidden border-l-4 {{ $statusConfig['left_border'] }} hover:shadow-md transition-all duration-200 group">
                
                {{-- HEADER CARD (ACCORDION TRIGGER) --}}
                <div class="p-5 cursor-pointer hover:bg-slate-50/80 transition relative bg-white" onclick="window.toggleAccordion('order-{{ $order->order_id }}')">
                    <div class="flex flex-col lg:flex-row lg:items-center justify-between gap-4">
                        
                        {{-- 1. Icon & ID --}}
                        <div class="flex items-center gap-4 lg:w-[35%]">
                            <div class="w-12 h-12 rounded-xl {{ $statusConfig['bg'] }} flex items-center justify-center {{ $statusConfig['text'] }} border {{ $statusConfig['border'] }} shadow-sm flex-shrink-0">
                                <i class="material-icons text-[22px]">shopping_cart</i>
                            </div>
                            <div>
                                <div class="flex items-center gap-2 mb-0.5">
                                    <h3 class="text-sm font-bold text-slate-800 group-hover:text-indigo-600 transition-colors">{{ $order->order_number }}</h3>
                                    <span class="text-[10px] bg-slate-100 text-slate-500 px-1.5 py-0.5 rounded border border-slate-200 font-mono">
                                        {{ $order->order_date->format('d M Y') }}
                                    </span>
                                </div>
                                <p class="text-xs font-medium text-slate-500 flex items-center gap-1">
                                    <i class="material-icons text-[14px]">person</i> 
                                    <span class="font-bold text-slate-700 truncate max-w-[150px]">{{ $order->client->client_name ?? 'Klien Dihapus' }}</span>
                                </p>
                            </div>
                        </div>

                        {{-- 2. Total & Items --}}
                        <div class="lg:w-[30%] border-l border-slate-100 pl-0 lg:pl-6 border-l-0 lg:border-l">
                            <div class="flex justify-between items-center pr-6">
                                <div>
                                    <span class="block text-[10px] font-bold text-slate-400 uppercase tracking-wider mb-1">Total Estimasi</span>
                                    <span class="text-sm font-bold text-slate-800 font-mono">Rp {{ number_format($order->total_amount, 0, ',', '.') }}</span>
                                </div>
                                <div class="text-right">
                                    <span class="block text-[10px] font-bold text-slate-400 uppercase tracking-wider mb-1">Item</span>
                                    <span class="text-xs bg-slate-100 text-slate-600 px-2 py-1 rounded font-bold">{{ $order->items->count() }} Produk</span>
                                </div>
                            </div>
                        </div>

                        {{-- 3. Status Badge & Arrow --}}
                        <div class="flex items-center justify-between lg:justify-end gap-4 lg:w-[35%]">
                            <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-bold uppercase tracking-wide {{ $statusConfig['bg'] }} {{ $statusConfig['text'] }} {{ $statusConfig['border'] }} border">
                                <i class="material-icons text-[14px] mr-1.5">{{ $statusConfig['icon'] }}</i>
                                {{ $statusConfig['label'] }}
                            </span>
                            
                            <div class="w-8 h-8 rounded-full bg-slate-50 flex items-center justify-center text-slate-400 group-hover:bg-indigo-50 group-hover:text-indigo-500 transition-colors">
                                <i class="material-icons text-[20px] transition-transform duration-300" id="icon-order-{{ $order->order_id }}">expand_more</i>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- COLLAPSE BODY --}}
                <div id="wrapper-order-{{ $order->order_id }}" class="grid grid-rows-[0fr] transition-[grid-template-rows] duration-300 ease-out bg-slate-50/50 border-t border-slate-100">
                    <div class="overflow-hidden">
                        <div class="p-6">
                            <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
                                
                                {{-- KIRI: Catatan --}}
                                <div class="space-y-4">
                                    <div class="bg-white p-4 rounded-xl border border-slate-200 shadow-sm">
                                        <h6 class="text-xs font-bold text-slate-500 uppercase tracking-wider mb-2 flex items-center gap-1">
                                            <i class="material-icons text-[14px]">sticky_note_2</i> Catatan Klien
                                        </h6>
                                        <p class="text-sm text-slate-700 italic leading-relaxed">
                                            "{{ $order->notes ?? 'Tidak ada catatan khusus.' }}"
                                        </p>
                                    </div>
                                </div>

                                {{-- KANAN: Aksi --}}
                                <div class="flex flex-col justify-end items-end gap-3">
                                    <p class="text-xs text-slate-400 mb-1">Tindakan:</p>
                                    <div class="flex flex-wrap justify-end gap-3 w-full">
                                        <a href="{{ route('admin.client-order-reviews.show', $order->order_id) }}" 
                                           class="px-5 py-2 bg-indigo-600 text-white font-bold rounded-lg hover:bg-indigo-700 transition text-sm shadow-md flex items-center justify-center gap-2 flex-1 md:flex-none hover:shadow-indigo-500/30 transform hover:-translate-y-0.5">
                                            <i class="material-icons text-[18px]">visibility</i> 
                                            {{ $view == 'pending' ? 'Review Sekarang' : 'Lihat Detail' }}
                                        </a>
                                    </div>
                                </div>

                            </div>
                        </div>
                    </div>
                </div>
            </div>
        @empty
            {{-- EMPTY STATE --}}
            <div class="flex flex-col items-center justify-center py-16 bg-white border border-dashed border-slate-200 rounded-2xl text-center">
                
                {{-- LINGKARAN --}}
                <div class="mx-auto mb-4 flex h-24 w-24 items-center justify-center rounded-full bg-slate-50">
                    
                    {{-- MENGGUNAKAN SVG LANGSUNG (Bukan Font Icon) --}}
                    {{-- Ini akan melewati semua konflik CSS font yang Anda alami --}}
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-10 w-10 text-slate-300" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 11-4 0 2 2 0 014 0z" />
                    </svg>

                </div>

                <h3 class="text-lg font-bold text-slate-700">Tidak ada pesanan</h3>
                <p class="text-slate-500 text-sm mt-1 max-w-sm mx-auto px-4">
                    @if($view == 'pending') 
                        Saat ini tidak ada pesanan baru dari klien yang perlu divalidasi.
                    @else 
                        Tidak ada riwayat pesanan yang sesuai dengan filter pencarian Anda. 
                    @endif
                </p>
                @if($view == 'history')
                    <a href="{{ route('admin.client-order-reviews.index', ['view' => 'history']) }}" class="mt-6 inline-block text-sm font-bold text-indigo-600 hover:text-indigo-700 hover:underline">
                        Reset Filter
                    </a>
                @endif
            </div>
        @endforelse
    </div>

    <div class="mt-8">
        {{ $clientOrders->appends(request()->query())->links() }}
    </div>
</div>
@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
<script>
    // Pastikan fungsi toggleAccordion ada di scope global (biasanya di app.js)
    // Jika belum ada, gunakan script inline ini:
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

        $('.select2-basic').select2({ 
            minimumResultsForSearch: Infinity, 
            width: '100%', 
            dropdownCssClass: 'select2-dropdown-clean border-slate-200 shadow-lg rounded-lg mt-1' 
        });
    });
</script>
@endpush
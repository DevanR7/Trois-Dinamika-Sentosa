@extends('admin.layouts.app')

@section('title', 'Review Permintaan Perubahan')

@section('content')
<div class="max-w-7xl mx-auto pb-20 animate-enter">
    
    {{-- HEADER --}}
    <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center mb-8 gap-4">
        <div>
            <h1 class="text-2xl font-bold text-slate-800 tracking-tight">Review Permintaan Perubahan</h1>
            <p class="text-slate-500 text-sm mt-1">Daftar permintaan pembatalan atau revisi pesanan dari klien.</p>
        </div>
    </div>

    {{-- FILTER CARD --}}
    <div class="dashboard-card p-6 mb-8 shadow-sm border border-slate-100">
        <form action="{{ route('admin.order-change-requests.index') }}" method="GET">
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
                            placeholder="No. Order / Request ID...">
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

                {{-- Type Filter --}}
                <div class="md:col-span-3">
                    <label class="block text-xs font-bold text-slate-500 uppercase mb-1.5 tracking-wide">Tipe Request</label>
                    <select name="type_filter" class="form-input select2-basic" data-placeholder="-- Semua Tipe --">
                        <option value=""></option>
                        <option value="cancel" @selected(request('type_filter') == 'cancel')>Pembatalan</option>
                        <option value="modify" @selected(request('type_filter') == 'modify')>Revisi Item</option>
                    </select>
                </div>

                {{-- Buttons --}}
                <div class="md:col-span-2 flex gap-2">
                    <button type="submit" class="h-[42px] bg-slate-800 hover:bg-slate-900 text-white font-bold py-2 px-4 rounded-lg shadow-sm transition text-sm flex items-center justify-center gap-2 flex-1 transform active:scale-95">
                        <i class="material-icons text-[18px]">filter_list</i> Filter
                    </button>
                    <a href="{{ route('admin.order-change-requests.index') }}" class="h-[42px] w-[42px] flex items-center justify-center bg-white border border-slate-300 text-slate-500 hover:text-indigo-600 hover:border-indigo-300 font-medium rounded-lg shadow-sm transition flex-shrink-0" title="Reset Filter">
                        <i class="material-icons text-[20px]">refresh</i>
                    </a>
                </div>
            </div>
        </form>
    </div>

    {{-- REQUEST LIST --}}
    <div class="flex flex-col gap-5">
        @forelse ($changeRequests as $req)
            @php
                $statusConfig = match($req->status) {
                    'pending' => ['color' => 'amber', 'label' => 'Menunggu Review', 'icon' => 'hourglass_empty'],
                    'approved' => ['color' => 'emerald', 'label' => 'Disetujui', 'icon' => 'check_circle'],
                    'rejected' => ['color' => 'red', 'label' => 'Ditolak', 'icon' => 'cancel'],
                    default => ['color' => 'slate', 'label' => 'Unknown', 'icon' => 'help'],
                };
                
                $typeConfig = match($req->request_type) {
                    'cancel' => ['bg' => 'bg-red-50', 'text' => 'text-red-600', 'icon' => 'cancel_presentation', 'label' => 'Permintaan Batal'],
                    'modify' => ['bg' => 'bg-indigo-50', 'text' => 'text-indigo-600', 'icon' => 'edit_note', 'label' => 'Revisi Pesanan'],
                };
                
                // Status Border Left
                $borderClass = $req->status == 'pending' ? 'border-l-4 border-l-amber-400' : 'border-l-4 border-l-transparent';
            @endphp

            <div class="dashboard-card p-0 overflow-hidden {{ $borderClass }} hover:shadow-md transition-all duration-200 group">
                
                {{-- CARD HEADER (Clickable Accordion) --}}
                <div class="p-5 cursor-pointer hover:bg-slate-50/80 transition relative bg-white" onclick="window.toggleAccordion('req-{{ $req->request_id }}')">
                    <div class="flex flex-col lg:flex-row lg:items-center justify-between gap-4">
                        
                        {{-- 1. Icon & ID --}}
                        <div class="flex items-center gap-4 lg:w-[35%]">
                            <div class="w-12 h-12 rounded-xl {{ $typeConfig['bg'] }} flex items-center justify-center {{ $typeConfig['text'] }} border border-{{ $statusConfig['color'] }}-100 shadow-sm flex-shrink-0">
                                <i class="material-icons text-[22px]">{{ $typeConfig['icon'] }}</i>
                            </div>
                            <div>
                                <div class="flex items-center gap-2 mb-0.5">
                                    <h3 class="text-sm font-bold text-slate-800 group-hover:text-indigo-600 transition-colors">REQ #{{ $req->request_id }}</h3>
                                    <span class="text-[10px] bg-slate-100 text-slate-500 px-1.5 py-0.5 rounded border border-slate-200 font-mono">
                                        {{ $req->created_at->diffForHumans() }}
                                    </span>
                                </div>
                                <p class="text-xs font-medium text-slate-500 flex items-center gap-1">
                                    <i class="material-icons text-[14px]">receipt</i> Pesanan: 
                                    <span class="font-bold text-slate-700">{{ $req->order->order_number }}</span>
                                </p>
                            </div>
                        </div>

                        {{-- 2. Client Info --}}
                        <div class="lg:w-[30%] border-l border-slate-100 pl-0 lg:pl-6 border-l-0 lg:border-l">
                            <span class="block text-[10px] font-bold text-slate-400 uppercase tracking-wider mb-1">Klien</span>
                            <div class="flex items-center gap-2">
                                <div class="w-6 h-6 rounded-full bg-slate-100 flex items-center justify-center text-slate-500 text-[10px] font-bold">
                                    {{ substr($req->client->client_name, 0, 1) }}
                                </div>
                                <span class="text-sm font-bold text-slate-700 truncate">{{ $req->client->client_name }}</span>
                            </div>
                        </div>

                        {{-- 3. Status Badge & Arrow --}}
                        <div class="flex items-center justify-between lg:justify-end gap-4 lg:w-[35%]">
                            <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-bold uppercase tracking-wide bg-{{ $statusConfig['color'] }}-50 text-{{ $statusConfig['color'] }}-700 border border-{{ $statusConfig['color'] }}-200">
                                <i class="material-icons text-[14px] mr-1.5">{{ $statusConfig['icon'] }}</i>
                                {{ $statusConfig['label'] }}
                            </span>
                            
                            <div class="w-8 h-8 rounded-full bg-slate-50 flex items-center justify-center text-slate-400 group-hover:bg-indigo-50 group-hover:text-indigo-500 transition-colors">
                                <i class="material-icons text-[20px] transition-transform duration-300" id="icon-req-{{ $req->request_id }}">expand_more</i>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- COLLAPSE BODY --}}
                <div id="wrapper-req-{{ $req->request_id }}" class="grid grid-rows-[0fr] transition-[grid-template-rows] duration-300 ease-out bg-slate-50/50 border-t border-slate-100">
                    <div class="overflow-hidden">
                        <div class="p-6">
                            <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
                                
                                {{-- KIRI: Detail & Catatan --}}
                                <div class="space-y-4">
                                    <div class="bg-white p-4 rounded-xl border border-slate-200 shadow-sm">
                                        <h6 class="text-xs font-bold text-slate-500 uppercase tracking-wider mb-2 flex items-center gap-1">
                                            <i class="material-icons text-[14px]">sticky_note_2</i> Catatan Klien
                                        </h6>
                                        <p class="text-sm text-slate-700 italic leading-relaxed">
                                            "{{ $req->client_notes ?? 'Tidak ada catatan khusus.' }}"
                                        </p>
                                    </div>

                                    @if($req->request_type == 'modify')
                                        <div class="bg-indigo-50 p-4 rounded-xl border border-indigo-100">
                                            <h6 class="text-xs font-bold text-indigo-600 uppercase tracking-wider mb-2">Detail Revisi</h6>
                                            <p class="text-sm text-indigo-900 font-medium">
                                                Terdapat <span class="font-bold underline">{{ $req->items->count() }} item</span> yang diminta untuk diubah/ditambah/dihapus.
                                            </p>
                                        </div>
                                    @endif
                                </div>

                                {{-- KANAN: Aksi --}}
                                <div class="flex flex-col justify-end items-end gap-3">
                                    <p class="text-xs text-slate-400 mb-1">Tindakan:</p>
                                    <div class="flex flex-wrap justify-end gap-3 w-full">
                                        <a href="{{ route('admin.sales-orders.show', $req->order_id) }}" 
                                           class="px-4 py-2 bg-white border border-slate-300 text-slate-600 font-bold rounded-lg hover:bg-slate-50 hover:text-slate-800 transition text-sm shadow-sm flex items-center justify-center gap-2 flex-1 md:flex-none">
                                            <i class="material-icons text-[16px]">visibility</i> Cek Pesanan
                                        </a>
                                        
                                        <a href="{{ route('admin.order-change-requests.show', $req->request_id) }}" 
                                           class="px-5 py-2 bg-indigo-600 text-white font-bold rounded-lg hover:bg-indigo-700 transition text-sm shadow-md flex items-center justify-center gap-2 flex-1 md:flex-none hover:shadow-indigo-500/30 transform hover:-translate-y-0.5">
                                            <i class="material-icons text-[18px]">rate_review</i> Review & Proses
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
            <div class="flex flex-col items-center justify-center py-16 bg-white border border-dashed border-slate-200 rounded-2xl">
                <div class="w-20 h-20 bg-slate-50 rounded-full flex items-center justify-center mb-4">
                    <i class="material-icons text-4xl text-slate-300">inbox</i>
                </div>
                <h3 class="text-lg font-bold text-slate-700">Tidak ada permintaan</h3>
                <p class="text-slate-500 text-sm mt-1 max-w-sm text-center">
                    Saat ini belum ada permintaan perubahan pesanan yang masuk. Coba ubah filter jika Anda sedang mencari data lama.
                </p>
                <a href="{{ route('admin.order-change-requests.index') }}" class="mt-6 text-sm font-bold text-indigo-600 hover:text-indigo-700 hover:underline">
                    Reset Filter Pencarian
                </a>
            </div>
        @endforelse
    </div>

    {{-- PAGINATION --}}
    <div class="mt-8">
        {{ $changeRequests->appends(request()->query())->links() }}
    </div>
</div>
@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
<script>
    document.addEventListener('DOMContentLoaded', function() {
        // Init Select2 Modern
        $('.select2-basic').select2({ 
            minimumResultsForSearch: Infinity, 
            width: '100%', 
            dropdownCssClass: 'select2-dropdown-clean border-slate-200 shadow-lg rounded-lg mt-1' 
        });
    });
</script>
@endpush
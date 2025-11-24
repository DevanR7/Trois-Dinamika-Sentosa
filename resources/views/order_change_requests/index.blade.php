@extends('layouts.app')

@section('title', 'Permintaan Perubahan')

@section('content')
<div class="max-w-7xl mx-auto">
    
    {{-- HEADER --}}
    <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center mb-6 gap-4">
        <div>
            <h2 class="text-2xl font-bold text-gray-900 tracking-tight">Permintaan Perubahan</h2>
            <p class="text-sm text-gray-500 mt-1">Review permintaan revisi atau pembatalan dari klien.</p>
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

    {{-- FILTER CARD --}}
    <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-5 mb-6">
        <form action="{{ route('order-change-requests.index') }}" method="GET">
            <div class="grid grid-cols-1 md:grid-cols-12 gap-4 items-end">
                
                {{-- Pencarian --}}
                <div class="md:col-span-4">
                    <label class="block text-xs font-bold text-gray-500 uppercase tracking-wider mb-1">Pencarian</label>
                    <div class="relative">
                        <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                            <i class="bi bi-search text-gray-400"></i>
                        </div>
                        <input type="text" name="search" value="{{ request('search') }}" 
                            class="pl-10 w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 text-sm py-2" 
                            placeholder="No. Request / Order...">
                    </div>
                </div>

                {{-- Periode --}}
                <div class="md:col-span-2">
                    <label class="block text-xs font-bold text-gray-500 uppercase tracking-wider mb-1">Periode</label>
                    <select name="date_filter" class="w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 text-sm py-2">
                        <option value="">-- Semua --</option>
                        @foreach($uniqueDates as $ym => $dateLabel)
                            <option value="{{ $ym }}" @selected(request('date_filter') == $ym)>{{ $dateLabel }}</option>
                        @endforeach
                    </select>
                </div>

                {{-- Tipe --}}
                <div class="md:col-span-2">
                    <label class="block text-xs font-bold text-gray-500 uppercase tracking-wider mb-1">Tipe Request</label>
                    <select name="type_filter" class="w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 text-sm py-2">
                        <option value="">-- Semua --</option>
                        <option value="cancel" @selected(request('type_filter') == 'cancel')>Pembatalan</option>
                        <option value="modify" @selected(request('type_filter') == 'modify')>Modifikasi</option>
                    </select>
                </div>

                {{-- Sort & Button --}}
                <div class="md:col-span-4 flex gap-2">
                    <select name="sort" class="w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 text-sm py-2">
                        <option value="terbaru" @selected(request('sort', 'terbaru') == 'terbaru')>Terbaru</option>
                        <option value="terlama" @selected(request('sort') == 'terlama')>Terlama</option>
                    </select>
                    <button type="submit" class="bg-gray-800 hover:bg-gray-900 text-white font-medium py-2 px-4 rounded-md shadow-sm transition text-sm flex items-center justify-center gap-2">
                        <i class="bi bi-funnel-fill"></i>
                    </button>
                    <a href="{{ route('order-change-requests.index') }}" class="bg-white border border-gray-300 text-gray-500 hover:text-indigo-600 font-medium py-2 px-3 rounded-md shadow-sm transition flex items-center justify-center" title="Reset">
                        <i class="bi bi-arrow-clockwise text-lg"></i>
                    </a>
                </div>
            </div>
        </form>
    </div>

    {{-- LIST CARD --}}
    <div class="flex flex-col gap-4">
        @forelse ($changeRequests as $request)
            @php
                $borderColor = $request->status == 'pending' ? 'border-l-yellow-500' : ($request->status == 'rejected' ? 'border-l-red-500' : 'border-l-green-500');
            @endphp

            <div class="bg-white rounded-lg shadow-sm border border-gray-200 overflow-hidden border-l-4 {{ $borderColor }} hover:shadow-md transition-shadow">
                
                {{-- HEADER CARD --}}
                <div class="p-5 cursor-pointer hover:bg-gray-50 transition-colors" onclick="toggleAccordion('collapse-{{ $request->request_id }}')">
                    <div class="flex flex-col lg:flex-row lg:items-center justify-between gap-6">
                        
                        {{-- Info Utama --}}
                        <div class="flex items-center gap-4 lg:w-1/3">
                            <div class="w-12 h-12 rounded-full bg-gray-100 flex items-center justify-center text-gray-600 flex-shrink-0 border border-gray-200">
                                @if($request->request_type == 'cancel')
                                    <i class="bi bi-x-circle text-red-500 text-xl"></i>
                                @else
                                    <i class="bi bi-pencil-square text-blue-500 text-xl"></i>
                                @endif
                            </div>
                            <div>
                                <h3 class="text-base font-bold text-gray-900 mb-0.5">REQ-{{ str_pad($request->request_id, 5, '0', STR_PAD_LEFT) }}</h3>
                                <p class="text-sm text-gray-500">
                                    Order: <span class="font-medium text-indigo-600">{{ $request->order->order_number ?? 'Dihapus' }}</span>
                                </p>
                            </div>
                        </div>

                        {{-- Klien & Tanggal --}}
                        <div class="flex gap-8 lg:w-1/3">
                            <div>
                                <span class="block text-[10px] font-bold text-gray-500 uppercase mb-1">Klien</span>
                                <span class="text-sm font-medium text-gray-900">{{ $request->client->client_name ?? '-' }}</span>
                            </div>
                            <div>
                                <span class="block text-[10px] font-bold text-gray-500 uppercase mb-1">Diajukan</span>
                                <span class="text-sm font-medium text-gray-900">{{ $request->created_at->format('d M Y') }}</span>
                            </div>
                        </div>

                        {{-- Status & Icon --}}
                        <div class="flex items-center justify-between lg:justify-end gap-4 lg:w-1/3">
                            @php
                                $statusStyles = [
                                    'pending' => 'bg-yellow-100 text-yellow-800 border-yellow-200',
                                    'approved' => 'bg-green-100 text-green-800 border-green-200',
                                    'rejected' => 'bg-red-100 text-red-800 border-red-200',
                                ];
                                $labelStatus = [
                                    'pending' => 'Menunggu Review',
                                    'approved' => 'Disetujui',
                                    'rejected' => 'Ditolak',
                                ];
                            @endphp
                            <span class="px-3 py-1 rounded-full text-xs font-bold border uppercase {{ $statusStyles[$request->status] ?? 'bg-gray-100 text-gray-600' }}">
                                {{ $labelStatus[$request->status] ?? $request->status }}
                            </span>
                            
                            <i class="bi bi-chevron-down text-gray-400 transition-transform duration-200" id="icon-collapse-{{ $request->request_id }}"></i>
                        </div>
                    </div>
                </div>

                {{-- COLLAPSE BODY --}}
                <div id="collapse-{{ $request->request_id }}" class="hidden bg-gray-50 border-t border-gray-100">
                    <div class="p-5 flex flex-col sm:flex-row justify-between items-center gap-4">
                        <div class="text-sm text-gray-600 flex-1">
                            @if($request->client_notes)
                                <div class="flex gap-2 items-start">
                                    <i class="bi bi-chat-quote text-gray-400 mt-0.5"></i>
                                    <span class="italic text-gray-500">"{{ Str::limit($request->client_notes, 80) }}"</span>
                                </div>
                            @else
                                <span class="text-gray-400 italic">Tidak ada catatan klien.</span>
                            @endif
                        </div>
                        
                        <a href="{{ route('order-change-requests.show', $request->request_id) }}" class="px-4 py-2 bg-white border border-indigo-200 text-indigo-700 font-medium rounded-lg hover:bg-indigo-50 hover:border-indigo-300 transition text-sm shadow-sm whitespace-nowrap">
                            <i class="bi bi-eye mr-1"></i> {{ $request->status == 'pending' ? 'Proses Request' : 'Lihat Detail' }}
                        </a>
                    </div>
                </div>

            </div>
        @empty
            <div class="text-center py-12 bg-white rounded-xl border border-dashed border-gray-300">
                <div class="w-16 h-16 bg-gray-100 rounded-full flex items-center justify-center mx-auto mb-4">
                    <i class="bi bi-inbox text-3xl text-gray-400"></i>
                </div>
                <h3 class="text-lg font-medium text-gray-900">Tidak ada permintaan</h3>
                <p class="text-gray-500 text-sm mt-1">Belum ada permintaan perubahan yang masuk.</p>
            </div>
        @endforelse
    </div>

    <div class="mt-6">
        {{ $changeRequests->appends(request()->query())->links() }}
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
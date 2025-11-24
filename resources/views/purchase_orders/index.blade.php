@extends('layouts.app')

@section('title', 'Daftar Purchase Order')

@section('content')
<div class="max-w-7xl mx-auto">

    {{-- HEADER PAGE --}}
    <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center mb-6 gap-4">
        <div>
            <h2 class="text-2xl font-bold text-gray-900">Pesanan Pembelian</h2>
            <p class="text-sm text-gray-500 mt-1">Kelola daftar pembelian ke supplier.</p>
        </div>
        <a href="{{ route('purchase-orders.create') }}" class="inline-flex items-center px-5 py-2.5 bg-indigo-600 hover:bg-indigo-700 text-white text-sm font-medium rounded-lg shadow-sm transition-colors duration-200">
            <i class="bi bi-plus-lg mr-2"></i> Buat Pesanan Baru
        </a>
    </div>

    {{-- FILTER SECTION --}}
    <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-5 mb-6">
        {{-- Pastikan kamu sudah update file partials/_filter.blade.php sesuai Langkah 2 --}}
        @include('purchase_orders.partials._filter')
    </div>

    {{-- LIST DATA --}}
    <div class="flex flex-col gap-4">
        @forelse ($purchaseOrders as $po)
            @php
                $sisaUtang = $po->total_amount - ($po->total_returned ?? 0) - ($po->amount_paid ?? 0);
                $borderColorClass = $sisaUtang <= 0 ? 'border-l-green-500' : 'border-l-yellow-500';
            @endphp

            <div class="bg-white rounded-lg shadow-sm border border-gray-200 overflow-hidden border-l-4 {{ $borderColorClass }} hover:shadow-md transition-shadow">
                
                {{-- HEADER CARD (Flexbox Layout agar tidak tumpuk) --}}
                <div class="p-5 cursor-pointer hover:bg-gray-50 transition-colors" onclick="toggleAccordion('collapse-{{ $po->po_id }}')">
                    
                    {{-- Container Flex Utama --}}
                    <div class="flex flex-col lg:flex-row lg:items-center justify-between gap-6">
                        
                        {{-- BAGIAN 1: INFO UTAMA (KIRI) --}}
                        <div class="flex items-start gap-4 lg:w-1/3">
                            <div class="w-12 h-12 rounded-full bg-indigo-50 flex items-center justify-center text-indigo-600 flex-shrink-0">
                                <i class="bi bi-cart-fill text-xl"></i>
                            </div>
                            <div>
                                <h3 class="text-base font-bold text-indigo-600 mb-1 leading-tight">{{ $po->po_number }}</h3>
                                <p class="text-sm font-semibold text-gray-800 leading-tight">{{ $po->supplier->supplier_name ?? 'Supplier Dihapus' }}</p>
                                <div class="mt-2 flex flex-wrap gap-2 lg:hidden">
                                     {{-- Badge tampil di mobile disini --}}
                                     @include('purchase_orders.partials._badges')
                                </div>
                            </div>
                        </div>

                        {{-- BAGIAN 2: DATA ANGKA (TENGAH) --}}
                        <div class="flex flex-row gap-8 lg:w-1/3">
                            <div>
                                <span class="block text-[10px] uppercase tracking-wider text-gray-500 font-bold mb-1">Tanggal</span>
                                <span class="text-sm text-gray-700 font-medium whitespace-nowrap">
                                    {{ optional($po->order_date)->format('d M Y') }}
                                </span>
                            </div>
                            <div>
                                <span class="block text-[10px] uppercase tracking-wider text-gray-500 font-bold mb-1">Total Tagihan</span>
                                <span class="text-sm text-gray-900 font-bold whitespace-nowrap">
                                    Rp {{ number_format($po->total_amount, 0, ',', '.') }}
                                </span>
                            </div>
                        </div>

                        {{-- BAGIAN 3: BADGES & ICON (KANAN - Desktop Only) --}}
                        <div class="hidden lg:flex lg:w-1/3 items-center justify-end gap-4">
                            <div class="flex flex-col items-end gap-1.5">
                                @include('purchase_orders.partials._badges')
                            </div>
                            <i class="bi bi-chevron-down text-gray-400" id="icon-collapse-{{ $po->po_id }}"></i>
                        </div>

                    </div>
                </div>

                {{-- COLLAPSE DETAIL --}}
                <div id="collapse-{{ $po->po_id }}" class="hidden bg-gray-50 border-t border-gray-100">
                    <div class="p-5">
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div>
                                <div class="grid grid-cols-2 gap-4 text-sm mb-4">
                                    <div>
                                        <div class="text-xs text-gray-500 font-bold uppercase">No. Faktur</div>
                                        <div class="font-medium">{{ $po->supplier_invoice_number ?? '-' }}</div>
                                    </div>
                                    <div>
                                        <div class="text-xs text-gray-500 font-bold uppercase">Jatuh Tempo</div>
                                        <div class="font-medium">{{ optional($po->due_date)->format('d M Y') ?? '-' }}</div>
                                    </div>
                                </div>
                                <div class="flex gap-6 border-t border-dashed border-gray-300 pt-3">
                                    <div>
                                        <div class="text-xs text-gray-500">Terbayar</div>
                                        <div class="text-green-600 font-bold text-sm">Rp {{ number_format($po->amount_paid ?? 0, 0, ',', '.') }}</div>
                                    </div>
                                    <div>
                                        <div class="text-xs text-gray-500">Sisa Utang</div>
                                        <div class="{{ $sisaUtang > 0 ? 'text-red-600' : 'text-green-600' }} font-bold text-sm">Rp {{ number_format($sisaUtang, 0, ',', '.') }}</div>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="flex items-center md:justify-end gap-2">
                                <a href="{{ route('purchase-orders.show', $po->po_id) }}" class="px-3 py-1.5 border border-indigo-600 text-indigo-600 bg-white hover:bg-indigo-50 rounded text-sm font-medium">
                                    Detail
                                </a>
                                @if(in_array($po->status, ['draft', 'ordered']))
                                    <a href="{{ route('purchase-orders.edit', $po->po_id) }}" class="px-3 py-1.5 border border-gray-300 text-gray-700 bg-white hover:bg-gray-50 rounded text-sm font-medium">
                                        Edit
                                    </a>
                                    <button onclick="confirmCancel('{{ $po->po_id }}')" class="px-3 py-1.5 border border-red-200 text-red-600 bg-white hover:bg-red-50 rounded text-sm font-medium">
                                        Batal
                                    </button>
                                @endif
                            </div>
                        </div>
                        <form id="cancel-form-{{ $po->po_id }}" action="{{ route('purchase-orders.cancel', $po->po_id) }}" method="POST" class="hidden">@csrf</form>
                    </div>
                </div>
            </div>
        @empty
            <div class="text-center py-10 bg-white rounded-lg shadow-sm border border-dashed border-gray-300">
                <p class="text-gray-500">Belum ada data pesanan.</p>
            </div>
        @endforelse
    </div>

    <div class="mt-6">
        {{ $purchaseOrders->appends(request()->query())->links() }}
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
    
    function confirmCancel(poId) {
        Swal.fire({
            title: 'Batalkan?',
            text: "Tidak bisa dikembalikan!",
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#ef4444',
            confirmButtonText: 'Ya, Batalkan!'
        }).then((result) => {
            if (result.isConfirmed) document.getElementById('cancel-form-' + poId).submit();
        });
    }
</script>
@endpush
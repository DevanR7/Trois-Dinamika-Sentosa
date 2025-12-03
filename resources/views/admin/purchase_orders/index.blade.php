@extends('admin.layouts.app')

@section('title', 'Daftar Purchase Order')

@section('content')
<div class="max-w-7xl mx-auto pb-20 animate-enter">

    {{-- HEADER --}}
    <div class="flex flex-col sm:flex-row sm:justify-between sm:items-end gap-4 mb-6">
        <div>
            <h1 class="text-2xl font-bold text-slate-800 tracking-tight">Pesanan Pembelian (PO)</h1>
            <p class="text-slate-500 text-sm mt-1">Kelola pengadaan barang ke supplier.</p>
        </div>
        <a href="{{ route('admin.purchase-orders.create') }}" class="w-full sm:w-auto h-[48px] px-8 bg-indigo-600 hover:bg-indigo-700 text-white text-sm font-bold rounded-lg shadow-md transition-all flex items-center justify-center gap-2 group hover:-translate-y-0.5">
            <i class="material-icons text-[20px] group-hover:rotate-90 transition-transform">add</i> 
            <span>Buat Pesanan</span>
        </a>
    </div>

    {{-- FILTER CARD --}}
    <div class="dashboard-card p-6 mb-6 shadow-sm">
        <form action="{{ route('admin.purchase-orders.index') }}" method="GET">
            <div class="grid grid-cols-1 md:grid-cols-12 gap-4 items-end">
                
                {{-- Pencarian --}}
                <div class="md:col-span-4">
                    <label class="block text-xs font-bold text-slate-500 uppercase mb-1.5">Pencarian</label>
                    <div class="relative">
                        <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                            <i class="material-icons text-slate-400 text-[20px]">search</i>
                        </div>
                        <input type="text" name="search" value="{{ request('search') }}" 
                            class="form-input pl-10" 
                            placeholder="No. PO / Supplier...">
                    </div>
                </div>

                {{-- Tanggal --}}
                <div class="md:col-span-3">
                    <label class="block text-xs font-bold text-slate-500 uppercase mb-1.5">Tanggal Order</label>
                    <input type="date" name="date" value="{{ request('date') }}" class="form-input">
                </div>

                {{-- Status --}}
                <div class="md:col-span-3">
                    <label class="block text-xs font-bold text-slate-500 uppercase mb-1.5">Status</label>
                    <select name="status" class="form-select select2-basic">
                        <option value="">-- Semua Status --</option>
                        <option value="draft" @selected(request('status') == 'draft')>Draft</option>
                        <option value="ordered" @selected(request('status') == 'ordered')>Dipesan (Ordered)</option>
                        <option value="received" @selected(request('status') == 'received')>Diterima (Received)</option>
                        <option value="completed" @selected(request('status') == 'completed')>Selesai</option>
                        <option value="cancelled" @selected(request('status') == 'cancelled')>Dibatalkan</option>
                    </select>
                </div>

                {{-- Tombol --}}
                <div class="md:col-span-2 flex gap-2">
                    <button type="submit" class="flex-1 h-[48px] bg-slate-800 hover:bg-slate-900 text-white font-bold rounded-lg shadow-sm transition-all flex items-center justify-center gap-2">
                        <i class="material-icons text-[18px]">filter_list</i> Filter
                    </button>
                    <a href="{{ route('admin.purchase-orders.index') }}" class="h-[48px] w-[48px] flex items-center justify-center bg-white border border-slate-300 text-slate-500 hover:text-indigo-600 font-medium rounded-lg shadow-sm transition" title="Reset">
                        <i class="material-icons text-[20px]">refresh</i>
                    </a>
                </div>
            </div>
        </form>
    </div>

    {{-- LIST PO (ACCORDION STYLE) --}}
    <div class="space-y-4">
        @forelse ($purchaseOrders as $po)
            @php
                $sisaUtang = $po->total_amount - ($po->total_returned ?? 0) - ($po->amount_paid ?? 0);
                // Status Badge Logic
                $statusClass = match($po->status) {
                    'draft' => 'status-draft',
                    'ordered' => 'status-pending',
                    'received' => 'status-approved',
                    'completed' => 'status-completed',
                    'cancelled' => 'status-rejected',
                    default => 'bg-gray-100 text-gray-600'
                };
                $statusLabel = match($po->status) {
                    'ordered' => 'Dipesan',
                    'received' => 'Diterima',
                    'completed' => 'Selesai',
                    'cancelled' => 'Batal',
                    default => ucfirst($po->status)
                };
                $statusIcon = match($po->status) {
                    'draft' => 'edit_note',
                    'ordered' => 'local_shipping',
                    'received' => 'inventory_2',
                    'completed' => 'check_circle',
                    'cancelled' => 'cancel',
                    default => 'help'
                };
            @endphp

            <div class="bg-white rounded-lg shadow-sm border border-slate-200 overflow-hidden transition-shadow hover:shadow-md">
                
                {{-- HEADER CARD (Click to Expand) --}}
                <div class="p-4 sm:p-5 cursor-pointer hover:bg-slate-50 transition-colors" onclick="toggleAccordion('{{ $po->po_id }}')">
                    <div class="flex flex-col sm:flex-row sm:items-center gap-4">
                        
                        {{-- Icon & Info Utama --}}
                        <div class="flex items-center gap-4 flex-1">
                            <div class="w-12 h-12 rounded-xl bg-indigo-50 flex items-center justify-center text-indigo-600 flex-shrink-0 border border-indigo-100">
                                <i class="material-icons text-xl">shopping_cart</i>
                            </div>
                            <div class="min-w-0">
                                <h3 class="text-base font-bold text-slate-800 truncate">{{ $po->po_number }}</h3>
                                <div class="text-xs text-slate-500 flex items-center gap-1 mt-0.5">
                                    <i class="material-icons text-[14px]">store</i> {{ $po->supplier->supplier_name ?? 'Supplier Dihapus' }}
                                </div>
                            </div>
                        </div>

                        {{-- Info Nilai & Status --}}
                        <div class="flex items-center justify-between sm:justify-end gap-6 sm:w-auto w-full pt-2 sm:pt-0 border-t sm:border-t-0 border-slate-100">
                            <div class="text-right">
                                <span class="block text-[10px] font-bold text-slate-400 uppercase tracking-wider mb-0.5">Total</span>
                                <span class="text-sm font-bold font-mono text-slate-800">Rp {{ number_format($po->total_amount, 0, ',', '.') }}</span>
                            </div>
                            
                            {{-- Status Badge --}}
                            <div>
                                <span class="{{ $statusClass }} inline-flex items-center justify-center px-2.5 py-1 rounded-md text-[11px] font-bold uppercase w-fit gap-1">
                                    <i class="material-icons text-[12px]">{{ $statusIcon }}</i> {{ $statusLabel }}
                                </span>
                            </div>

                            {{-- Chevron Icon --}}
                            <div class="hidden sm:block text-slate-400 transition-transform duration-200" id="icon-{{ $po->po_id }}">
                                <i class="material-icons">expand_more</i>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- COLLAPSE CONTENT --}}
                <div id="wrapper-{{ $po->po_id }}" class="grid grid-rows-[0fr] transition-all duration-300 ease-in-out">
                    <div class="overflow-hidden">
                        <div class="p-5 border-t border-slate-100 bg-slate-50/50">
                            <div class="grid grid-cols-1 lg:grid-cols-12 gap-6 text-sm">
                                
                                {{-- Kolom Kiri: Detail Tanggal --}}
                                <div class="lg:col-span-4 space-y-3">
                                    <h4 class="text-xs font-bold text-slate-500 uppercase tracking-wider mb-2">Informasi Tanggal</h4>
                                    <div class="flex justify-between border-b border-dashed border-slate-200 pb-2">
                                        <span class="text-slate-500">Tanggal Order</span>
                                        <span class="font-medium text-slate-800">{{ optional($po->order_date)->format('d M Y') }}</span>
                                    </div>
                                    <div class="flex justify-between border-b border-dashed border-slate-200 pb-2">
                                        <span class="text-slate-500">Jatuh Tempo</span>
                                        <span class="font-medium text-slate-800">{{ $po->due_date ? $po->due_date->format('d M Y') : '-' }}</span>
                                    </div>
                                    <div class="flex justify-between">
                                        <span class="text-slate-500">Request Oleh</span>
                                        <span class="font-medium text-slate-800">{{ $po->requester->full_name ?? '-' }}</span>
                                    </div>
                                </div>

                                {{-- Kolom Tengah: Keuangan --}}
                                <div class="lg:col-span-4 space-y-3 lg:border-l lg:border-r border-slate-200 lg:px-6">
                                    <h4 class="text-xs font-bold text-slate-500 uppercase tracking-wider mb-2">Status Pembayaran</h4>
                                    <div class="flex justify-between">
                                        <span class="text-slate-500">Total Tagihan</span>
                                        <span class="font-bold text-slate-800">Rp {{ number_format($po->total_amount, 0, ',', '.') }}</span>
                                    </div>
                                    <div class="flex justify-between">
                                        <span class="text-slate-500">Sudah Dibayar</span>
                                        <span class="font-bold text-emerald-600">Rp {{ number_format($po->amount_paid, 0, ',', '.') }}</span>
                                    </div>
                                    <div class="flex justify-between pt-2 border-t border-slate-200 mt-1">
                                        <span class="text-slate-500 font-bold">Sisa Utang</span>
                                        <span class="font-bold {{ $sisaUtang > 0 ? 'text-red-500' : 'text-slate-400' }}">
                                            Rp {{ number_format($sisaUtang, 0, ',', '.') }}
                                        </span>
                                    </div>
                                </div>

                                {{-- Kolom Kanan: Aksi --}}
                                <div class="lg:col-span-4 flex flex-col gap-2 justify-center">
                                    <h4 class="text-xs font-bold text-slate-500 uppercase tracking-wider mb-2">Tindakan</h4>
                                    
                                    <div class="flex gap-2">
                                        <a href="{{ route('admin.purchase-orders.show', $po->po_id) }}" class="flex-1 px-3 py-2 bg-white border border-slate-300 text-slate-700 rounded-lg text-xs font-bold hover:bg-slate-50 transition text-center flex items-center justify-center gap-1">
                                            <i class="material-icons text-[16px]">visibility</i> Detail
                                        </a>
                                        <a href="{{ route('admin.purchase-orders.pdf', $po->po_id) }}" target="_blank" class="flex-1 px-3 py-2 bg-white border border-slate-300 text-slate-700 rounded-lg text-xs font-bold hover:bg-slate-50 transition text-center flex items-center justify-center gap-1">
                                            <i class="material-icons text-[16px]">picture_as_pdf</i> PDF
                                        </a>
                                    </div>

                                    @if(in_array($po->status, ['draft', 'ordered']))
                                        <div class="flex gap-2 mt-1">
                                            <a href="{{ route('admin.purchase-orders.edit', $po->po_id) }}" class="flex-1 px-3 py-2 bg-amber-50 border border-amber-200 text-amber-700 rounded-lg text-xs font-bold hover:bg-amber-100 transition text-center flex items-center justify-center gap-1">
                                                <i class="material-icons text-[16px]">edit</i> Edit
                                            </a>
                                            
                                            <form action="{{ route('admin.purchase-orders.cancel', $po->po_id) }}" method="POST" class="delete-form flex-1">
                                                @csrf
                                                <button type="submit" 
                                                    data-title="Batalkan Pesanan?" 
                                                    data-text="Pesanan ini akan ditandai sebagai batal." 
                                                    data-btn-text="Ya, Batalkan" 
                                                    data-btn-color="#ef4444"
                                                    class="w-full h-full px-3 py-2 bg-red-50 border border-red-200 text-red-600 rounded-lg text-xs font-bold hover:bg-red-100 transition flex items-center justify-center gap-1">
                                                    <i class="material-icons text-[16px]">cancel</i> Batal
                                                </button>
                                            </form>
                                        </div>
                                    @endif
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        @empty
            <div class="py-16 text-center bg-white rounded-xl border border-dashed border-slate-300">
                <div class="w-16 h-16 bg-slate-50 rounded-full flex items-center justify-center mb-4 mx-auto">
                    <i class="material-icons text-slate-300 text-4xl">shopping_cart_off</i>
                </div>
                <h3 class="text-lg font-bold text-slate-700">Belum ada pesanan</h3>
                <p class="text-slate-500 text-sm mt-1">Buat pesanan pembelian baru untuk memulai.</p>
            </div>
        @endforelse
    </div>

    <div class="mt-6">
        {{ $purchaseOrders->appends(request()->query())->links() }}
    </div>
</div>
@endsection
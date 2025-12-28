@extends('admin.layouts.app')

@section('title', 'Data Supplier')

@section('content')
    <div class="flex flex-col gap-6">
        
        {{-- Header & Tools --}}
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
            <div>
                <h2 class="page-title">Data Supplier</h2>
                <p class="page-subtitle">Kelola data pemasok, deposit, dan informasi kontak.</p>
            </div>
            <a href="{{ route('admin.suppliers.create') }}" class="btn btn-primary">
                <i class="material-icons text-lg">add</i>
                Tambah Supplier
            </a>
        </div>

        {{-- Filter & Search --}}
        <div class="card p-4">
            <form method="GET" action="{{ route('admin.suppliers.index') }}" class="grid grid-cols-1 md:grid-cols-4 gap-4">
                
                {{-- Search --}}
                <div class="col-span-1 md:col-span-2">
                    <label class="form-label text-xs">Cari Supplier / PIC</label>
                    <div class="relative">
                        <span class="absolute inset-y-0 left-0 flex items-center pl-3 text-slate-400">
                            <i class="material-icons text-lg">search</i>
                        </span>
                        <input type="text" name="search" value="{{ request('search') }}" 
                               class="form-input pl-10" 
                               placeholder="Cari nama supplier, PIC, atau telepon...">
                    </div>
                </div>

                {{-- Status Filter --}}
                <div>
                    <label class="form-label text-xs">Status Data</label>
                    <select name="status" class="tom-select w-full">
                        <option value="active" {{ request('status') != 'deleted' ? 'selected' : '' }}>Aktif</option>
                        <option value="deleted" {{ request('status') == 'deleted' ? 'selected' : '' }}>Diarsipkan (Terhapus)</option>
                    </select>
                </div>

                {{-- Action Buttons --}}
                <div class="flex items-end gap-2">
                    <button type="submit" class="btn btn-secondary flex-1">
                        <i class="material-icons text-lg">filter_list</i>
                        Filter
                    </button>
                    @if(request()->anyFilled(['search', 'status']))
                        <a href="{{ route('admin.suppliers.index') }}" class="btn btn-danger-solid px-3" title="Reset Filter">
                            <i class="material-icons">close</i>
                        </a>
                    @endif
                </div>
            </form>
        </div>

        {{-- Supplier List (Accordion Style) --}}
        <div class="flex flex-col gap-3">
            @forelse($suppliers as $supplier)
                <div class="bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-xl overflow-hidden transition-all duration-200 hover:shadow-md hover:border-indigo-200 dark:hover:border-slate-600" 
                     x-data="{ expanded: false }">
                    
                    {{-- ACCORDION HEADER (Visible Always) --}}
                    <div @click="expanded = !expanded" class="p-4 flex items-center justify-between cursor-pointer group">
                        <div class="flex items-center gap-4">
                            {{-- Avatar --}}
                            <div class="w-10 h-10 rounded-full bg-slate-100 dark:bg-slate-700 flex items-center justify-center text-slate-500 font-bold text-sm group-hover:bg-indigo-50 group-hover:text-indigo-600 transition-colors">
                                {{ substr($supplier->supplier_name, 0, 1) }}
                            </div>

                            {{-- Nama & Status Ringkas --}}
                            <div>
                                <h3 class="font-bold text-slate-700 dark:text-white text-sm sm:text-base group-hover:text-indigo-600 transition-colors">
                                    {{ $supplier->supplier_name }}
                                </h3>
                                <p class="text-xs text-slate-500 flex items-center gap-1">
                                    <i class="material-icons text-[12px]">person</i> {{ $supplier->person_in_charge ?? '-' }}
                                    <span class="mx-1">•</span>
                                    <i class="material-icons text-[12px]">phone</i> {{ $supplier->phone_number ?? '-' }}
                                </p>
                            </div>
                        </div>

                        <div class="flex items-center gap-4">
                            {{-- Info Cepat: Saldo --}}
                            <div class="hidden sm:flex flex-col items-end mr-2">
                                <span class="text-[10px] text-slate-400 uppercase font-bold">Saldo Deposit</span>
                                <span class="text-sm font-bold text-indigo-600 dark:text-indigo-400">Rp {{ number_format($supplier->balance, 0, ',', '.') }}</span>
                            </div>

                            {{-- Status Badge --}}
                            <div class="hidden sm:block">
                                @if($supplier->trashed())
                                    <span class="badge badge-danger">Diarsipkan</span>
                                @else
                                    <span class="badge badge-success">Aktif</span>
                                @endif
                            </div>

                            {{-- Arrow Icon --}}
                            <div class="text-slate-400 transition-transform duration-300" :class="expanded ? 'rotate-180' : ''">
                                <i class="material-icons text-xl">expand_more</i>
                            </div>
                        </div>
                    </div>

                    {{-- ACCORDION BODY (Details) --}}
                    <div x-show="expanded" x-collapse class="bg-slate-50 dark:bg-slate-800/50 border-t border-slate-100 dark:border-slate-700 px-4 py-6">
                        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                            
                            {{-- KOLOM 1: Informasi Keuangan & Hutang --}}
                            <div class="flex flex-col gap-4 border-b lg:border-b-0 lg:border-r border-slate-200 dark:border-slate-700 pb-4 lg:pb-0 lg:pr-6">
                                <h4 class="text-xs font-bold text-slate-400 uppercase tracking-wider mb-2 flex items-center gap-1">
                                    <i class="material-icons text-[14px]">monetization_on</i> Keuangan & Tagihan
                                </h4>
                                
                                {{-- Kalkulasi Sisa Hutang (Logic View) --}}
                                @php
                                    $unpaidPos = $supplier->purchaseOrders->whereIn('payment_status', ['unpaid', 'partially_paid']);
                                    $debtAmount = $unpaidPos->sum('remaining_balance');
                                    $poCount = $unpaidPos->count();
                                @endphp

                                <div class="flex justify-between items-center">
                                    <span class="text-sm text-slate-600 dark:text-slate-300">Sisa Hutang (AP)</span>
                                    <div class="text-right">
                                        <span class="font-bold text-rose-600">Rp {{ number_format($debtAmount, 0, ',', '.') }}</span>
                                        @if($poCount > 0)
                                            <div class="text-[10px] text-slate-400">{{ $poCount }} PO Belum Lunas</div>
                                        @endif
                                    </div>
                                </div>
                                
                                <div class="flex justify-between items-center pt-2 border-t border-slate-200 dark:border-slate-700/50">
                                    <span class="text-sm text-slate-600 dark:text-slate-300">Saldo Deposit</span>
                                    <span class="font-bold text-indigo-600 dark:text-indigo-400">
                                        Rp {{ number_format($supplier->balance, 0, ',', '.') }}
                                    </span>
                                </div>

                                @if($supplier->pending_balance > 0)
                                    <div class="flex justify-between items-center">
                                        <span class="text-sm text-slate-500">Deposit Tertahan</span>
                                        <span class="text-xs font-bold text-amber-500 bg-amber-50 px-2 py-0.5 rounded">
                                            Rp {{ number_format($supplier->pending_balance, 0, ',', '.') }}
                                        </span>
                                    </div>
                                @endif
                            </div>

                            {{-- KOLOM 2: Detail Identitas --}}
                            <div class="flex flex-col gap-3 border-b lg:border-b-0 lg:border-r border-slate-200 dark:border-slate-700 pb-4 lg:pb-0 lg:pr-6">
                                <h4 class="text-xs font-bold text-slate-400 uppercase tracking-wider mb-2 flex items-center gap-1">
                                    <i class="material-icons text-[14px]">info</i> Identitas & Bank
                                </h4>
                                
                                <div class="space-y-2">
                                    <div class="flex items-start gap-2 text-sm">
                                        <i class="material-icons text-slate-400 text-sm mt-0.5">account_balance</i>
                                        <div class="flex flex-col">
                                            <span class="font-medium text-slate-700 dark:text-slate-200">{{ $supplier->bank_name ?? '-' }}</span>
                                            <span class="text-xs text-slate-500">{{ $supplier->account_number ?? 'No. Rek: -' }}</span>
                                        </div>
                                    </div>

                                    <div class="flex items-center gap-2 text-sm">
                                        <i class="material-icons text-slate-400 text-sm">badge</i>
                                        <span class="text-slate-600 dark:text-slate-300">NPWP: {{ $supplier->npwp ?? '-' }}</span>
                                    </div>

                                    <div class="flex items-start gap-2 text-sm">
                                        <i class="material-icons text-slate-400 text-sm mt-0.5">location_on</i>
                                        <span class="text-slate-600 dark:text-slate-300 leading-tight">{{ $supplier->address ?? '-' }}</span>
                                    </div>
                                </div>
                            </div>

                            {{-- KOLOM 3: Aksi --}}
                            <div class="flex flex-col gap-4 justify-between">
                                <div>
                                    <h4 class="text-xs font-bold text-slate-400 uppercase tracking-wider mb-3">Aksi & Kontrol</h4>
                                    
                                    <div class="flex flex-wrap gap-2">
                                        @if($supplier->trashed())
                                            {{-- Restore Button --}}
                                            <form action="{{ route('admin.suppliers.restore', $supplier->supplier_id) }}" method="POST" class="w-full">
                                                @csrf @method('PATCH')
                                                <button type="submit" class="btn btn-sm btn-success w-full justify-center">
                                                    <i class="material-icons text-[16px]">restore</i> Pulihkan
                                                </button>
                                            </form>
                                        @else
                                            {{-- Detail --}}
                                            <a href="{{ route('admin.suppliers.show', $supplier->supplier_id) }}" class="btn btn-sm btn-primary flex-1 justify-center">
                                                <i class="material-icons text-[16px]">visibility</i> Detail
                                            </a>

                                            {{-- Edit --}}
                                            <a href="{{ route('admin.suppliers.edit', $supplier->supplier_id) }}" class="btn btn-sm btn-secondary flex-1 justify-center">
                                                <i class="material-icons text-[16px]">edit</i> Edit
                                            </a>

                                            {{-- Delete (Archive) --}}
                                            <button type="button" onclick="deleteSupplier({{ $supplier->supplier_id }}, '{{ $supplier->supplier_name }}')" class="btn btn-sm btn-danger w-full justify-center mt-2">
                                                <i class="material-icons text-[16px]">delete</i> Arsipkan
                                            </button>
                                            <form id="delete-form-{{ $supplier->supplier_id }}" action="{{ route('admin.suppliers.destroy', $supplier->supplier_id) }}" method="POST" class="hidden">
                                                @csrf @method('DELETE')
                                            </form>
                                        @endif
                                    </div>
                                </div>

                                {{-- Meta Info --}}
                                <div class="text-right">
                                    <p class="text-[10px] text-slate-400">Terdaftar: {{ $supplier->created_at->format('d M Y') }}</p>
                                </div>
                            </div>

                        </div>
                    </div>
                </div>
            @empty
                <div class="card p-8 flex flex-col items-center justify-center text-slate-400">
                    <i class="material-icons text-5xl mb-3">inventory_2</i>
                    <p class="text-base font-medium">Tidak ada data supplier.</p>
                    <p class="text-sm mt-1">Coba ubah kata kunci pencarian atau filter status.</p>
                </div>
            @endforelse
        </div>

        {{-- Pagination --}}
        @if($suppliers->hasPages())
            <div class="mt-4">
                {{ $suppliers->links('vendor.pagination.admin') }}
            </div>
        @endif
    </div>

    @push('scripts')
    <script>
        function deleteSupplier(id, name) {
            confirmDialog({
                title: 'Arsipkan Supplier?',
                text: `Anda akan mengarsipkan supplier <b>${name}</b>.<br>Data tidak hilang permanen, namun tidak akan muncul di opsi pembelian.`,
                icon: 'warning',
                confirmText: 'Ya, Arsipkan',
                confirmColor: 'danger'
            }).then((result) => {
                if (result.isConfirmed) {
                    document.getElementById('delete-form-' + id).submit();
                }
            });
        }
    </script>
    @endpush
@endsection
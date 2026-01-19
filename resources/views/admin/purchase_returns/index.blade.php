@extends('admin.layouts.app')

@section('title', 'Retur Pembelian')

@section('content')
<div class="flex flex-col gap-6">
    
    {{-- PAGE HEADER --}}
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div>
            <h1 class="page-title">Retur Pembelian</h1>
            <p class="page-subtitle">Daftar pengembalian barang kepada supplier.</p>
        </div>
        <div>
            <a href="{{ route('admin.purchase-returns.create') }}" class="btn btn-primary">
                <i class="material-icons text-sm">add</i>
                Buat Retur Baru
            </a>
        </div>
    </div>

    {{-- FILTERS & SEARCH --}}
    <div class="card p-4">
        <form action="{{ route('admin.purchase-returns.index') }}" method="GET" class="flex flex-col md:flex-row gap-4 items-end">
            
            {{-- Search Text --}}
            <div class="w-full md:flex-1">
                <label class="form-label">Pencarian</label>
                <div class="relative">
                    <div class="absolute inset-y-0 left-0 flex items-center pl-3 pointer-events-none">
                        <i class="material-icons text-slate-400">search</i>
                    </div>
                    <input type="text" name="search" value="{{ request('search') }}" 
                           class="form-input pl-10" 
                           placeholder="Cari No. Retur, Supplier, atau No. PO...">
                </div>
            </div>

            {{-- Filter Tanggal --}}
            <div class="w-full md:w-48">
                <label class="form-label">Tanggal Retur</label>
                <input type="date" name="return_date" value="{{ request('return_date') }}" class="form-input">
            </div>

            {{-- Buttons --}}
            <div class="flex gap-2">
                <button type="submit" class="btn btn-secondary btn-icon" title="Terapkan Filter">
                    <i class="material-icons">filter_list</i>
                </button>
                <a href="{{ route('admin.purchase-returns.index') }}" class="btn btn-secondary btn-icon text-slate-500" title="Reset Filter">
                    <i class="material-icons">refresh</i>
                </a>
            </div>
        </form>
    </div>

    {{-- DATA TABLE --}}
    <div class="card card-plain bg-white dark:bg-slate-800">
        <div class="table-container">
            <table class="table-modern">
                <thead>
                    <tr>
                        <th>Info Retur</th>
                        <th>Supplier</th>
                        <th>Referensi PO</th>
                        <th>Penanganan</th>
                        <th class="text-right">Total Nilai</th>
                        <th class="text-center w-24">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($purchaseReturns as $return)
                    <tr>
                        {{-- Kolom Info Retur --}}
                        <td>
                            <div class="flex items-center gap-3">
                                <div class="w-10 h-10 rounded-lg bg-rose-50 dark:bg-rose-900/20 flex items-center justify-center text-rose-600 dark:text-rose-400 border border-rose-100 dark:border-rose-800">
                                    <i class="material-icons text-lg">assignment_return</i>
                                </div>
                                <div>
                                    <div class="font-bold text-slate-800 dark:text-white">
                                        {{ $return->return_number }}
                                    </div>
                                    <div class="text-xs text-slate-500 flex items-center gap-1">
                                        <i class="material-icons text-[10px]">calendar_today</i>
                                        {{ $return->return_date->format('d M Y') }}
                                    </div>
                                </div>
                            </div>
                        </td>

                        {{-- Kolom Supplier --}}
                        <td>
                            <div class="text-sm font-medium text-slate-700 dark:text-slate-200">
                                {{ $return->supplier->supplier_name ?? '-' }}
                            </div>
                            <div class="text-xs text-slate-400">
                                {{ $return->supplier->person_in_charge ?? '' }}
                            </div>
                        </td>

                        {{-- Kolom PO Asal --}}
                        <td>
                            <a href="{{ route('admin.purchase-orders.show', $return->purchase_order_id) }}" class="text-indigo-600 hover:text-indigo-800 hover:underline font-mono text-sm">
                                {{ $return->purchaseOrder->po_number ?? '-' }}
                            </a>
                        </td>

                        {{-- Kolom Penanganan (Badge) --}}
                        <td>
                            @if($return->return_handling_type == 'deduct_invoice')
                                <span class="badge badge-info flex items-center gap-1 w-fit">
                                    <i class="material-icons text-[10px]">remove_circle_outline</i>
                                    Potong Tagihan
                                </span>
                            @elseif($return->return_handling_type == 'store_as_deposit')
                                <span class="badge badge-warning flex items-center gap-1 w-fit">
                                    <i class="material-icons text-[10px]">account_balance_wallet</i>
                                    Simpan Deposit
                                </span>
                            @else
                                <span class="badge badge-primary">{{ $return->return_handling_type }}</span>
                            @endif
                        </td>

                        {{-- Kolom Total Nilai --}}
                        <td class="text-right font-mono font-bold text-slate-700 dark:text-slate-300">
                            Rp {{ number_format($return->total_amount, 0, ',', '.') }}
                        </td>

                        {{-- Kolom Aksi --}}
                        <td>
                            <div class="flex items-center justify-center gap-2">
                                {{-- Detail --}}
                                <a href="{{ route('admin.purchase-returns.show', $return->return_id) }}" 
                                   class="btn-action btn-action-view" 
                                   title="Lihat Detail">
                                    <i class="material-icons">visibility</i>
                                </a>

                                {{-- Hapus (Hanya Admin/Manager biasanya) --}}
                                @can('delete-purchase-returns')
                                <button onclick="confirmDelete('{{ $return->return_id }}', '{{ $return->return_number }}')" 
                                        class="btn-action btn-action-delete" 
                                        title="Batalkan Retur">
                                    <i class="material-icons">delete</i>
                                </button>

                                <form id="delete-form-{{ $return->return_id }}" 
                                      action="{{ route('admin.purchase-returns.destroy', $return->return_id) }}" 
                                      method="POST" class="hidden">
                                    @csrf 
                                    @method('DELETE')
                                </form>
                                @endcan
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="6" class="p-8 text-center text-slate-500">
                            <div class="flex flex-col items-center justify-center">
                                <div class="w-16 h-16 bg-slate-100 dark:bg-slate-800 rounded-full flex items-center justify-center mb-3">
                                    <i class="material-icons text-3xl text-slate-300">folder_open</i>
                                </div>
                                <p class="font-medium">Belum ada data retur pembelian.</p>
                                <p class="text-xs mt-1 text-slate-400">Silakan buat retur baru jika ada barang yang dikembalikan ke supplier.</p>
                            </div>
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        
        {{-- Pagination --}}
        @if($purchaseReturns->hasPages())
            <div class="px-6 py-4 border-t border-slate-100 dark:border-slate-700">
                {{ $purchaseReturns->links('vendor.pagination.admin') }}
            </div>
        @endif
    </div>
</div>

@push('scripts')
<script>
    function confirmDelete(id, number) {
        window.confirmDialog({
            title: 'Batalkan Retur?',
            text: `Anda yakin ingin membatalkan Retur Pembelian ${number}? Stok akan dikembalikan dan jurnal akan dibalik.`,
            icon: 'warning',
            confirmText: 'Ya, Batalkan',
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
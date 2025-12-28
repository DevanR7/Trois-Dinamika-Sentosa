@extends('admin.layouts.app')

@section('title', 'Retur Pembelian')

@section('content')
    <div class="flex flex-col gap-6">
        
        {{-- Header & Toolbar --}}
        <div class="flex flex-col md:flex-row justify-between items-center gap-4">
            <div>
                <h2 class="page-title">Daftar Retur Pembelian</h2>
                <p class="text-sm text-slate-500 mt-1">Kelola pengembalian barang ke supplier.</p>
            </div>
            <a href="{{ route('admin.purchase-returns.create') }}" class="btn btn-primary">
                <i class="material-icons text-lg">add</i> Buat Retur Baru
            </a>
        </div>

        {{-- Filters --}}
        <div class="card p-4">
            <form method="GET" action="{{ route('admin.purchase-returns.index') }}" class="grid grid-cols-1 md:grid-cols-4 gap-4">
                
                {{-- Search --}}
                <div class="md:col-span-2">
                    <label class="form-label text-xs">Cari</label>
                    <div class="relative">
                        <span class="absolute inset-y-0 left-0 flex items-center pl-3">
                            <i class="material-icons text-slate-400">search</i>
                        </span>
                        <input type="text" name="search" value="{{ request('search') }}" 
                               class="form-input pl-10" 
                               placeholder="No. Retur, Supplier, atau No. PO...">
                    </div>
                </div>

                {{-- Tanggal --}}
                <div>
                    <label class="form-label text-xs">Tanggal Retur</label>
                    <input type="date" name="return_date" value="{{ request('return_date') }}" class="form-input">
                </div>

                {{-- Action --}}
                <div class="flex items-end gap-2">
                    <button type="submit" class="btn btn-secondary flex-1">
                        <i class="material-icons text-base">filter_list</i> Filter
                    </button>
                    @if(request()->anyFilled(['search', 'return_date']))
                        <a href="{{ route('admin.purchase-returns.index') }}" class="btn btn-white border border-slate-300 text-slate-500" title="Reset">
                            <i class="material-icons text-base">refresh</i>
                        </a>
                    @endif
                </div>
            </form>
        </div>

        {{-- Table --}}
        <div class="card overflow-hidden">
            <div class="table-container border-0 shadow-none">
                <table class="table-modern">
                    <thead>
                        <tr>
                            <th>No. Retur</th>
                            <th>Tanggal</th>
                            <th>No. PO Asal</th>
                            <th>Supplier</th>
                            <th>Jenis Penanganan</th>
                            <th class="text-right">Total Nilai</th>
                            <th class="text-center">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($purchaseReturns as $return)
                            <tr>
                                <td>
                                    <span class="font-bold text-slate-700 dark:text-white">{{ $return->return_number }}</span>
                                </td>
                                <td>
                                    <div class="flex items-center gap-1 text-slate-600 dark:text-slate-300">
                                        <i class="material-icons text-sm text-slate-400">event</i>
                                        {{ $return->return_date->format('d/m/Y') }}
                                    </div>
                                </td>
                                <td>
                                    <a href="{{ route('admin.purchase-orders.show', $return->purchase_order_id) }}" class="text-indigo-600 hover:underline text-xs font-medium">
                                        {{ $return->purchaseOrder->po_number }}
                                    </a>
                                </td>
                                <td>
                                    <div class="font-medium text-slate-700 dark:text-slate-200">{{ $return->supplier->supplier_name }}</div>
                                </td>
                                <td>
                                    {{-- PERBAIKAN: Mengganti ikon 'cut' menjadi 'content_cut' --}}
                                    @if($return->return_handling_type == 'deduct_invoice')
                                        <span class="badge badge-warning inline-flex items-center gap-1 whitespace-nowrap">
                                            <i class="material-icons text-[12px]">content_cut</i> Potong Tagihan
                                        </span>
                                    @else
                                        <span class="badge badge-info inline-flex items-center gap-1 whitespace-nowrap">
                                            <i class="material-icons text-[12px]">account_balance_wallet</i> Simpan Deposit
                                        </span>
                                    @endif
                                </td>
                                <td class="text-right font-bold text-rose-600">
                                    Rp {{ number_format($return->total_amount, 0, ',', '.') }}
                                </td>
                                <td class="text-center">
                                    <a href="{{ route('admin.purchase-returns.show', $return->return_id) }}" class="btn btn-sm btn-white border border-slate-200 shadow-sm text-slate-600 hover:text-indigo-600">
                                        Detail <i class="material-icons text-sm ml-1">arrow_forward</i>
                                    </a>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="text-center py-12">
                                    <div class="flex flex-col items-center justify-center text-slate-400">
                                        <i class="material-icons text-5xl mb-2">inbox</i>
                                        <p>Belum ada data retur pembelian.</p>
                                    </div>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            
            {{-- Pagination --}}
            @if($purchaseReturns->hasPages())
                <div class="p-4 border-t border-slate-100 dark:border-slate-700">
                    {{ $purchaseReturns->links('vendor.pagination.admin') }}
                </div>
            @endif
        </div>
    </div>
@endsection
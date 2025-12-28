@extends('admin.layouts.app')

@section('title', 'Retur Penjualan')

@section('content')
    <div class="flex flex-col gap-6">
        
        {{-- Header & Tools --}}
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
            <div>
                <h2 class="page-title">Retur Penjualan</h2>
                <p class="page-subtitle">Kelola pengembalian barang dari pelanggan.</p>
            </div>
            <a href="{{ route('admin.sales-returns.create') }}" class="btn btn-primary">
                <i class="material-icons text-lg">add</i>
                Buat Retur Baru
            </a>
        </div>

        {{-- Filter Section --}}
        <div class="card p-4">
            <form method="GET" action="{{ route('admin.sales-returns.index') }}" class="grid grid-cols-1 md:grid-cols-4 gap-4">
                {{-- Search --}}
                <div class="col-span-1 md:col-span-2">
                    <label class="form-label text-xs">Cari No. Retur / Invoice / Klien</label>
                    <div class="relative">
                        <span class="absolute inset-y-0 left-0 flex items-center pl-3 text-slate-400">
                            <i class="material-icons text-lg">search</i>
                        </span>
                        <input type="text" name="search" value="{{ request('search') }}" 
                               class="form-input pl-10" 
                               placeholder="Cari data...">
                    </div>
                </div>

                {{-- Date Filter --}}
                <div>
                    <label class="form-label text-xs">Tanggal Retur</label>
                    <input type="date" name="return_date" value="{{ request('return_date') }}" class="form-input">
                </div>

                {{-- Filter Button --}}
                <div class="flex items-end gap-2">
                    <button type="submit" class="btn btn-secondary flex-1">
                        <i class="material-icons text-lg">filter_list</i>
                        Filter
                    </button>
                    @if(request()->anyFilled(['search', 'return_date']))
                        <a href="{{ route('admin.sales-returns.index') }}" class="btn btn-danger-solid px-3" title="Reset Filter">
                            <i class="material-icons">close</i>
                        </a>
                    @endif
                </div>
            </form>
        </div>

        {{-- Table Card --}}
        <div class="card">
            <div class="table-container">
                <table class="table-modern">
                    <thead>
                        <tr>
                            <th>No. Retur</th>
                            <th>Tanggal</th>
                            <th>Referensi Invoice</th>
                            <th>Klien</th>
                            <th>Tipe Retur</th>
                            <th class="text-right">Total Nilai (Rp)</th>
                            <th class="text-center">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($salesReturns as $return)
                            <tr>
                                <td class="font-medium text-slate-700 dark:text-slate-200">
                                    {{ $return->return_number }}
                                </td>
                                <td>
                                    {{ \Carbon\Carbon::parse($return->return_date)->format('d/m/Y') }}
                                </td>
                                <td>
                                    @if($return->salesInvoice)
                                        <a href="{{ route('admin.invoices.show', $return->salesInvoice->invoice_id) }}" 
                                           class="text-indigo-600 hover:underline dark:text-indigo-400">
                                            {{ $return->salesInvoice->invoice_number }}
                                        </a>
                                    @else
                                        <span class="text-slate-400 italic">-</span>
                                    @endif
                                </td>
                                <td>
                                    <div class="flex flex-col">
                                        <span class="font-medium">{{ $return->client->client_name }}</span>
                                        <span class="text-xs text-slate-500">{{ $return->client->person_in_charge ?? '-' }}</span>
                                    </div>
                                </td>
                                <td>
                                    @if($return->return_handling_type == 'deduct_invoice')
                                        <span class="badge badge-warning">Potong Tagihan</span>
                                    @else
                                        <span class="badge badge-info">Simpan Deposit</span>
                                    @endif
                                </td>
                                <td class="text-right font-bold text-slate-700 dark:text-slate-200">
                                    {{ number_format($return->total_amount, 0, ',', '.') }}
                                </td>
<td class="text-center">
    <div class="flex items-center justify-center gap-2">
        {{-- 
           REVISI: 
           1. Tambah 'flex items-center justify-center' di container tombol (<a> / <button>)
           2. Tambah 'leading-none' di icon (<i>) untuk reset line-height
        --}}
        
        {{-- Tombol Detail (Show) --}}
        <a href="{{ route('admin.sales-returns.show', $return->return_id) }}" 
           class="btn-icon btn-sm btn-secondary flex items-center justify-center" 
           title="Detail">
            <i class="material-icons text-[18px] leading-none">visibility</i>
        </a>
        
        {{-- Tombol Hapus (Delete) --}}
        <button type="button" 
                onclick="deleteReturn({{ $return->return_id }}, '{{ $return->return_number }}')"
                class="btn-icon btn-sm btn-danger flex items-center justify-center" 
                title="Hapus">
            <i class="material-icons text-[18px] leading-none">delete</i>
        </button>

        <form id="delete-form-{{ $return->return_id }}" 
              action="{{ route('admin.sales-returns.destroy', $return->return_id) }}" 
              method="POST" class="hidden">
            @csrf
            @method('DELETE')
        </form>
    </div>
</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="text-center py-8">
                                    <div class="flex flex-col items-center justify-center text-slate-400">
                                        <i class="material-icons text-4xl mb-2">inbox</i>
                                        <p class="text-sm">Belum ada data retur penjualan.</p>
                                    </div>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            
            {{-- Pagination --}}
            <div class="p-4 border-t border-slate-100 dark:border-slate-700/50">
                {{ $salesReturns->links('vendor.pagination.admin') }}
            </div>
        </div>
    </div>

    @push('scripts')
    <script>
        function deleteReturn(id, number) {
            confirmDialog({
                title: 'Hapus Retur?',
                text: `Anda akan menghapus Retur <b>${number}</b>. <br>Stok barang akan dikembalikan ke posisi sebelum retur dan jurnal akuntansi akan dibalik.`,
                icon: 'warning',
                confirmText: 'Ya, Hapus',
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
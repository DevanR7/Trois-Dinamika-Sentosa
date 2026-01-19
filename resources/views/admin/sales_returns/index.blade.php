@extends('admin.layouts.app')

@section('title', 'Retur Penjualan')

@section('content')
    <div class="flex flex-col gap-6">

        {{-- PAGE HEADER --}}
        <div class="page-header">
            <div>
                <h1 class="page-title">Retur Penjualan</h1>
                <p class="page-subtitle">Kelola data pengembalian barang dari pelanggan.</p>
            </div>
            
            @can('create-sales-returns')
                <a href="{{ route('admin.sales-returns.create') }}" class="btn btn-primary">
                    <i class="material-icons text-sm">add</i>
                    <span>Buat Retur Baru</span>
                </a>
            @endcan
        </div>

        {{-- FILTER SECTION --}}
        <div class="card">
            <div class="card-body">
                <form action="{{ route('admin.sales-returns.index') }}" method="GET" class="grid grid-cols-1 md:grid-cols-4 gap-4">
                    
                    {{-- Search Input --}}
                    <div class="md:col-span-2">
                        <label class="form-label">Cari Data</label>
                        <div class="relative">
                            <div class="absolute inset-y-0 left-0 flex items-center pl-3 pointer-events-none">
                                <i class="material-icons text-slate-400 text-base">search</i>
                            </div>
                            <input type="text" 
                                   name="search" 
                                   value="{{ request('search') }}" 
                                   class="form-input pl-10" 
                                   placeholder="No. Retur, Nama Klien, atau No. Invoice...">
                        </div>
                    </div>

                    {{-- Date Filter --}}
                    <div>
                        <label class="form-label">Tanggal Retur</label>
                        <input type="date" 
                               name="return_date" 
                               value="{{ request('return_date') }}" 
                               class="form-input">
                    </div>

                    {{-- Action Buttons --}}
                    <div class="flex items-end gap-2">
                        <button type="submit" class="btn btn-secondary w-full justify-center">
                            <i class="material-icons text-sm">filter_list</i> Filter
                        </button>
                        @if(request()->anyFilled(['search', 'return_date']))
                            <a href="{{ route('admin.sales-returns.index') }}" class="btn btn-danger-solid w-auto px-3" title="Reset Filter">
                                <i class="material-icons text-sm">close</i>
                            </a>
                        @endif
                    </div>
                </form>
            </div>
        </div>

        {{-- TABLE SECTION --}}
        <div class="card">
            <div class="table-container">
                <table class="table-modern">
                    <thead>
                        <tr>
                            <th class="w-16">#</th>
                            <th>No. Retur</th>
                            <th>Tanggal</th>
                            <th>Klien</th>
                            <th>Invoice Asal</th>
                            <th>Metode</th>
                            <th class="text-right">Total Nilai</th>
                            <th class="text-center w-24">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($salesReturns as $return)
                            <tr>
                                <td>{{ $loop->iteration + $salesReturns->firstItem() - 1 }}</td>
                                
                                {{-- No Retur --}}
                                <td>
                                    <div class="font-bold text-slate-700 dark:text-slate-200">
                                        {{ $return->return_number }}
                                    </div>
                                </td>

                                {{-- Tanggal --}}
                                <td>
                                    <div class="text-slate-500 dark:text-slate-400">
                                        {{ $return->return_date->format('d M Y') }}
                                    </div>
                                </td>

                                {{-- Klien --}}
                                <td>
                                    <div class="font-medium text-slate-700 dark:text-white">
                                        {{ $return->client->client_name ?? '-' }}
                                    </div>
                                </td>

                                {{-- Invoice Asal --}}
                                <td>
                                    @if($return->salesInvoice)
                                        <a href="{{ route('admin.invoices.show', $return->sales_invoice_id) }}" 
                                           class="text-indigo-600 hover:text-indigo-800 dark:text-indigo-400 dark:hover:text-indigo-300 hover:underline font-medium text-xs inline-flex items-center gap-1">
                                            {{ $return->salesInvoice->invoice_number }}
                                            <i class="material-icons text-[10px]">open_in_new</i>
                                        </a>
                                    @else
                                        <span class="text-slate-400 italic">Invoice Terhapus</span>
                                    @endif
                                </td>

                                {{-- Metode Retur --}}
                                <td>
                                    @if($return->return_handling_type === 'deduct_invoice')
                                        <span class="badge badge-warning">
                                            <i class="material-icons text-[10px] mr-1">receipt</i> Potong Tagihan
                                        </span>
                                    @else
                                        <span class="badge badge-info">
                                            <i class="material-icons text-[10px] mr-1">savings</i> Simpan Deposit
                                        </span>
                                    @endif
                                </td>

                                {{-- Total Nilai --}}
                                <td class="text-right">
                                    <span class="font-bold text-slate-700 dark:text-white font-mono">
                                        Rp {{ number_format($return->total_amount, 0, ',', '.') }}
                                    </span>
                                </td>

                                {{-- Aksi --}}
                                <td>
                                    <div class="flex items-center justify-center gap-2">
                                        {{-- View Button --}}
                                        @can('view-sales-returns')
                                            <a href="{{ route('admin.sales-returns.show', $return->return_id) }}" 
                                               class="btn-action btn-action-view" 
                                               title="Lihat Detail">
                                                <i class="material-icons">visibility</i>
                                            </a>
                                        @endcan

                                        {{-- Delete Button (Hanya jika punya akses) --}}
                                        @can('delete-sales-returns')
                                            <button type="button" 
                                                    class="btn-action btn-action-delete"
                                                    title="Batalkan Retur"
                                                    onclick="confirmDelete('{{ $return->return_id }}', '{{ $return->return_number }}')">
                                                <i class="material-icons">delete</i>
                                            </button>
                                            
                                            {{-- Form Delete Hidden --}}
                                            <form id="delete-form-{{ $return->return_id }}" 
                                                  action="{{ route('admin.sales-returns.destroy', $return->return_id) }}" 
                                                  method="POST" 
                                                  class="hidden">
                                                @csrf
                                                @method('DELETE')
                                            </form>
                                        @endcan
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="8">
                                    <div class="flex flex-col items-center justify-center py-12 text-center">
                                        <div class="w-16 h-16 bg-slate-100 dark:bg-slate-700 rounded-full flex items-center justify-center mb-4">
                                            <i class="material-icons text-3xl text-slate-400">assignment_return</i>
                                        </div>
                                        <h3 class="text-base font-medium text-slate-700 dark:text-slate-200">Belum ada data retur</h3>
                                        <p class="text-sm text-slate-500 dark:text-slate-400 mt-1">
                                            Data retur penjualan yang dibuat akan muncul di sini.
                                        </p>
                                        @can('create-sales-returns')
                                            <a href="{{ route('admin.sales-returns.create') }}" class="btn btn-primary mt-4">
                                                Buat Retur Baru
                                            </a>
                                        @endcan
                                    </div>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            {{-- Pagination --}}
            @if($salesReturns->hasPages())
                <div class="card-footer">
                    {{ $salesReturns->links('vendor.pagination.admin') }}
                </div>
            @endif
        </div>
    </div>

    @push('scripts')
    <script>
        /**
         * Fungsi Konfirmasi Delete Menggunakan Helper confirmDialog (dari app.js)
         */
        function confirmDelete(id, number) {
            confirmDialog({
                title: 'Batalkan Retur?',
                text: `Anda yakin ingin membatalkan retur <strong>${number}</strong>? <br><br> Stok barang akan dikembalikan ke klien (Invoice) dan jurnal akan dibalik.`,
                icon: 'warning',
                confirmButtonText: 'Ya, Batalkan',
                cancelButtonText: 'Batal',
                confirmButtonColor: '#e11d48', // Rose-600
            }).then((result) => {
                if (result.isConfirmed) {
                    document.getElementById('delete-form-' + id).submit();
                }
            });
        }
    </script>
    @endpush

@endsection
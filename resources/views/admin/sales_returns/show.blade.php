@extends('admin.layouts.app')

@section('title', 'Detail Retur #' . $salesReturn->return_number)

@section('content')
    <div class="flex flex-col gap-6">
        
        {{-- Header --}}
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
            <div>
                <h2 class="page-title">Detail Retur Penjualan</h2>
                <div class="flex items-center gap-2 text-sm text-slate-500 mt-1">
                    <span>{{ $salesReturn->return_number }}</span>
                    <span class="w-1 h-1 bg-slate-300 rounded-full"></span>
                    <span>{{ \Carbon\Carbon::parse($salesReturn->return_date)->isoFormat('D MMMM Y') }}</span>
                </div>
            </div>
            <div class="flex gap-3">
                <a href="{{ route('admin.sales-returns.index') }}" class="btn btn-secondary">
                    <i class="material-icons text-lg">arrow_back</i>
                    Kembali
                </a>
                
                {{-- Tombol Hapus (Hanya jika belum dihapus) --}}
                <button onclick="deleteReturn()" class="btn btn-danger">
                    <i class="material-icons text-lg">delete</i>
                    Batalkan Retur
                </button>
                <form id="delete-form" action="{{ route('admin.sales-returns.destroy', $salesReturn->return_id) }}" method="POST" class="hidden">
                    @csrf @method('DELETE')
                </form>
            </div>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
            
            {{-- Info Utama --}}
            <div class="lg:col-span-2 flex flex-col gap-6">
                
                {{-- Status Card --}}
                <div class="card p-5 flex items-center justify-between bg-gradient-to-r from-slate-800 to-slate-900 text-white border-none">
                    <div class="flex flex-col">
                        <span class="text-slate-400 text-xs uppercase font-bold tracking-wider mb-1">Total Pengembalian</span>
                        <span class="text-3xl font-bold">Rp {{ number_format($salesReturn->total_amount, 0, ',', '.') }}</span>
                    </div>
                    <div class="text-right">
                        <span class="text-slate-400 text-xs uppercase font-bold tracking-wider mb-1">Jenis Penanganan</span>
                        <div class="mt-1">
                            @if($salesReturn->return_handling_type == 'deduct_invoice')
                                <span class="bg-amber-500/20 text-amber-300 px-3 py-1 rounded-full text-xs font-bold border border-amber-500/50">
                                    Potong Tagihan
                                </span>
                            @else
                                <span class="bg-blue-500/20 text-blue-300 px-3 py-1 rounded-full text-xs font-bold border border-blue-500/50">
                                    Simpan Deposit
                                </span>
                            @endif
                        </div>
                    </div>
                </div>

                {{-- Tabel Item --}}
                <div class="card">
                    <div class="card-header">
                        <h3 class="card-header-title">Item yang Dikembalikan</h3>
                    </div>
                    <div class="table-container">
                        <table class="table-modern">
                            <thead>
                                <tr>
                                    <th>Produk</th>
                                    <th class="text-right">Harga (Net)</th>
                                    <th class="text-center">Qty Retur</th>
                                    <th class="text-right">Subtotal</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($salesReturn->items as $item)
                                    <tr>
                                        <td>
                                            <div class="font-medium text-slate-700 dark:text-slate-200">
                                                {{ $item->product->product_name ?? 'Produk Dihapus' }}
                                            </div>
                                            <div class="text-xs text-slate-500">
                                                {{ $item->product->product_code ?? '-' }}
                                            </div>
                                        </td>
                                        <td class="text-right">
                                            Rp {{ number_format($item->price_per_unit, 0, ',', '.') }}
                                        </td>
                                        <td class="text-center font-bold">
                                            {{ number_format($item->quantity, 0, ',', '.') }}
                                            <span class="text-xs font-normal text-slate-400 ml-1">{{ $item->product->unit->name ?? 'Unit' }}</span>
                                        </td>
                                        <td class="text-right font-bold text-slate-700 dark:text-slate-200">
                                            Rp {{ number_format($item->subtotal, 0, ',', '.') }}
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                            <tfoot>
                                <tr class="bg-slate-50 dark:bg-slate-800 border-t border-slate-200 dark:border-slate-700">
                                    <td colspan="3" class="text-right py-4 font-bold text-slate-600 dark:text-slate-300">TOTAL</td>
                                    <td class="text-right py-4 px-6 font-bold text-indigo-600 dark:text-indigo-400 text-lg">
                                        Rp {{ number_format($salesReturn->total_amount, 0, ',', '.') }}
                                    </td>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                </div>
            </div>

            {{-- Sidebar Info --}}
            <div class="lg:col-span-1 flex flex-col gap-6">
                
                {{-- Info Referensi --}}
                <div class="card">
                    <div class="card-header">
                        <h3 class="card-header-title">Referensi</h3>
                    </div>
                    <div class="card-body flex flex-col gap-4">
                        {{-- Klien --}}
                        <div class="flex items-start gap-3">
                            <div class="w-10 h-10 rounded-lg bg-slate-100 dark:bg-slate-700 flex items-center justify-center text-slate-500">
                                <i class="material-icons">person</i>
                            </div>
                            <div>
                                <p class="text-xs text-slate-500 uppercase font-bold">Pelanggan</p>
                                <p class="font-bold text-slate-700 dark:text-slate-200">
                                    {{ $salesReturn->client->client_name }}
                                </p>
                                <p class="text-sm text-slate-500">
                                    {{ $salesReturn->client->phone_number ?? '-' }}
                                </p>
                            </div>
                        </div>

                        <hr class="border-slate-100 dark:border-slate-700">

                        {{-- Invoice --}}
                        <div class="flex items-start gap-3">
                            <div class="w-10 h-10 rounded-lg bg-indigo-50 dark:bg-indigo-900/30 flex items-center justify-center text-indigo-600">
                                <i class="material-icons">receipt</i>
                            </div>
                            <div>
                                <p class="text-xs text-slate-500 uppercase font-bold">Asal Invoice</p>
                                <a href="{{ route('admin.invoices.show', $salesReturn->sales_invoice_id) }}" class="font-bold text-indigo-600 hover:underline">
                                    {{ $salesReturn->salesInvoice->invoice_number }}
                                </a>
                                <p class="text-xs text-slate-500 mt-0.5">
                                    Tgl Order: {{ \Carbon\Carbon::parse($salesReturn->salesInvoice->order_date)->format('d/m/Y') }}
                                </p>
                            </div>
                        </div>

                        <hr class="border-slate-100 dark:border-slate-700">

                        {{-- Staff --}}
                        <div class="flex items-start gap-3">
                            <div class="w-10 h-10 rounded-lg bg-emerald-50 dark:bg-emerald-900/30 flex items-center justify-center text-emerald-600">
                                <i class="material-icons">badge</i>
                            </div>
                            <div>
                                <p class="text-xs text-slate-500 uppercase font-bold">Diproses Oleh</p>
                                <p class="font-medium text-slate-700 dark:text-slate-200">
                                    {{ $salesReturn->user->full_name ?? 'Sistem' }}
                                </p>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Catatan --}}
                @if($salesReturn->notes)
                <div class="card">
                    <div class="card-header">
                        <h3 class="card-header-title">Catatan</h3>
                    </div>
                    <div class="card-body">
                        <p class="text-sm text-slate-600 dark:text-slate-300 italic">
                            "{{ $salesReturn->notes }}"
                        </p>
                    </div>
                </div>
                @endif

            </div>
        </div>
    </div>

    @push('scripts')
    <script>
        function deleteReturn() {
            confirmDialog({
                title: 'Batalkan Retur?',
                text: 'Tindakan ini akan <b>mengembalikan stok</b> ke gudang, <b>menghapus deposit/potongan</b> terkait, dan membuat jurnal pembalik. Data tidak dapat dipulihkan.',
                icon: 'warning',
                confirmText: 'Ya, Batalkan',
                confirmColor: 'danger'
            }).then((result) => {
                if (result.isConfirmed) {
                    document.getElementById('delete-form').submit();
                }
            });
        }
    </script>
    @endpush
@endsection
@extends('admin.layouts.app')

@section('title', 'Detail Retur ' . $salesReturn->return_number)

@section('content')
<div class="flex flex-col gap-6">

    {{-- HEADER --}}
    <div class="flex flex-col md:flex-row md:items-center justify-between gap-4">
        <div>
            <div class="flex items-center gap-3">
                <a href="{{ route('admin.sales-returns.index') }}" class="btn-icon btn-secondary" title="Kembali">
                    <i class="material-icons text-lg">arrow_back</i>
                </a>
                <div>
                    <h1 class="page-title text-xl font-bold tracking-tight">
                        Retur Penjualan <span class="text-indigo-600">#{{ $salesReturn->return_number }}</span>
                    </h1>
                    <p class="text-sm text-slate-500 mt-1 flex items-center gap-2">
                        <i class="material-icons text-xs">event</i> {{ $salesReturn->return_date->format('d F Y') }}
                        <span class="text-slate-300">|</span>
                        <i class="material-icons text-xs">schedule</i> Dibuat {{ $salesReturn->created_at->format('d M Y H:i') }}
                    </p>
                </div>
            </div>
        </div>

        {{-- ACTION BUTTONS --}}
        <div class="flex flex-wrap items-center gap-2">
            {{-- Cetak (Placeholder link, bisa diimplementasikan nanti) --}}
            {{-- <a href="#" target="_blank" class="btn btn-secondary pl-3 pr-4" title="Cetak PDF">
                <i class="material-icons text-lg mr-1 text-slate-500">print</i> Cetak
            </a> --}}

            @can('delete-sales-returns')
                <button type="button" 
                        onclick="confirmDelete('{{ route('admin.sales-returns.destroy', $salesReturn->return_id) }}')"
                        class="btn btn-secondary text-rose-600 border-rose-200 hover:bg-rose-50">
                    <i class="material-icons text-lg mr-1">delete</i> Batalkan Retur
                </button>
            @endcan
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">

        {{-- KOLOM KIRI: ITEM & TOTAL (2/3) --}}
        <div class="lg:col-span-2 flex flex-col gap-6">
            
            {{-- TABEL ITEM --}}
            <div class="card overflow-hidden">
                <div class="card-header bg-slate-50 dark:bg-slate-800/50">
                    <h3 class="card-header-title">Barang yang Dikembalikan</h3>
                </div>
                
                <div class="table-container border-0 shadow-none rounded-none">
                    <table class="table-modern">
                        <thead>
                            <tr>
                                <th class="w-12 text-center">#</th>
                                <th>Produk</th>
                                <th class="text-center">Qty</th>
                                <th class="text-right">Harga Satuan</th>
                                <th class="text-right">Subtotal</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($salesReturn->items as $item)
                                <tr>
                                    <td class="text-center">{{ $loop->iteration }}</td>
                                    <td>
                                        <div class="font-bold text-slate-700 dark:text-slate-200">
                                            {{ $item->product->product_name ?? 'Item Terhapus' }}
                                        </div>
                                        <div class="text-xs text-slate-500">
                                            {{ $item->product->product_code ?? '-' }}
                                        </div>
                                    </td>
                                    <td class="text-center">
                                        <span class="inline-flex items-center px-2.5 py-1 rounded-md text-xs font-bold bg-rose-50 text-rose-700 border border-rose-100">
                                            -{{ number_format($item->quantity, 0, ',', '.') }} {{ $item->product->unit->name ?? '' }}
                                        </span>
                                    </td>
                                    <td class="text-right font-mono text-slate-600 dark:text-slate-400">
                                        Rp {{ number_format($item->price_per_unit, 0, ',', '.') }}
                                    </td>
                                    <td class="text-right font-mono font-bold text-slate-800 dark:text-white">
                                        Rp {{ number_format($item->subtotal, 0, ',', '.') }}
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                {{-- TOTAL SUMMARY --}}
                <div class="p-6 bg-slate-50 dark:bg-slate-800/30 border-t border-slate-200 dark:border-slate-700">
                    <div class="flex justify-end">
                        <div class="w-full md:w-1/2 space-y-3">
                            <div class="flex justify-between items-center">
                                <span class="text-sm font-bold text-slate-500 uppercase tracking-wider">Total Nilai Retur</span>
                                <span class="text-2xl font-bold text-rose-600 font-mono">
                                    Rp {{ number_format($salesReturn->total_amount, 0, ',', '.') }}
                                </span>
                            </div>
                            <p class="text-xs text-right text-slate-400">
                                * Nilai ini telah disesuaikan dengan diskon invoice asal.
                            </p>
                        </div>
                    </div>
                </div>
            </div>

            {{-- CATATAN --}}
            @if($salesReturn->notes)
                <div class="card p-5 border-l-4 border-l-amber-400">
                    <h4 class="text-xs font-bold text-amber-600 uppercase mb-2 flex items-center gap-2">
                        <i class="material-icons text-sm">sticky_note_2</i> Catatan Retur
                    </h4>
                    <p class="text-sm text-slate-700 dark:text-slate-300 italic">
                        "{{ $salesReturn->notes }}"
                    </p>
                </div>
            @endif

        </div>

        {{-- KOLOM KANAN: INFORMASI META (1/3) --}}
        <div class="lg:col-span-1 space-y-6">

            {{-- CARD 1: REFERENSI --}}
            <div class="card p-5">
                <h3 class="text-sm font-bold text-slate-800 dark:text-white uppercase mb-4 pb-2 border-b border-slate-100 dark:border-slate-700">
                    Referensi Asal
                </h3>
                
                {{-- Link Invoice --}}
                <div class="mb-4">
                    <label class="text-xs text-slate-400 block mb-1">Invoice Penjualan</label>
                    @if($salesReturn->salesInvoice)
                        <a href="{{ route('admin.invoices.show', $salesReturn->sales_invoice_id) }}" 
                           class="group flex items-center justify-between p-3 rounded-lg border border-slate-200 hover:border-indigo-300 hover:bg-indigo-50 dark:border-slate-700 dark:hover:bg-slate-800 transition-all">
                            <div class="flex items-center gap-3">
                                <div class="w-8 h-8 rounded bg-indigo-100 text-indigo-600 flex items-center justify-center">
                                    <i class="material-icons text-base">receipt_long</i>
                                </div>
                                <div>
                                    <div class="text-sm font-bold text-indigo-700 dark:text-indigo-400 group-hover:underline">
                                        {{ $salesReturn->salesInvoice->invoice_number }}
                                    </div>
                                    <div class="text-xs text-slate-500">
                                        {{ $salesReturn->salesInvoice->order_date->format('d/m/Y') }}
                                    </div>
                                </div>
                            </div>
                            <i class="material-icons text-slate-400 group-hover:text-indigo-500">chevron_right</i>
                        </a>
                    @else
                        <div class="p-3 rounded-lg bg-slate-100 text-slate-500 text-sm italic">
                            Invoice #{{ $salesReturn->sales_invoice_id }} (Terhapus)
                        </div>
                    @endif
                </div>

                {{-- Klien Info --}}
                <div>
                    <label class="text-xs text-slate-400 block mb-1">Klien / Pembeli</label>
                    <div class="flex items-center gap-3 p-3 bg-slate-50 dark:bg-slate-800/50 rounded-lg">
                        <div class="w-8 h-8 rounded-full bg-slate-200 dark:bg-slate-700 text-slate-500 flex items-center justify-center font-bold text-xs">
                            {{ substr($salesReturn->client->client_name ?? 'U', 0, 1) }}
                        </div>
                        <div class="overflow-hidden">
                            <div class="text-sm font-bold text-slate-700 dark:text-slate-200 truncate">
                                {{ $salesReturn->client->client_name ?? 'Umum' }}
                            </div>
                            <div class="text-xs text-slate-500 truncate">
                                {{ $salesReturn->client->phone_number ?? '-' }}
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            {{-- CARD 2: METODE PENANGANAN --}}
            <div class="card p-5">
                <h3 class="text-sm font-bold text-slate-800 dark:text-white uppercase mb-4 pb-2 border-b border-slate-100 dark:border-slate-700">
                    Metode Penanganan
                </h3>
                
                @if($salesReturn->return_handling_type == 'deduct_invoice')
                    <div class="bg-amber-50 dark:bg-amber-900/20 border border-amber-100 dark:border-amber-800 rounded-lg p-4">
                        <div class="flex items-center gap-2 mb-2 text-amber-700 dark:text-amber-500 font-bold text-sm">
                            <i class="material-icons">cut</i> Potong Tagihan
                        </div>
                        <p class="text-xs text-amber-800/80 dark:text-amber-400 leading-relaxed">
                            Nilai retur ini langsung mengurangi sisa tagihan pada invoice asal.
                        </p>
                    </div>
                @else
                    <div class="bg-sky-50 dark:bg-sky-900/20 border border-sky-100 dark:border-sky-800 rounded-lg p-4">
                        <div class="flex items-center gap-2 mb-2 text-sky-700 dark:text-sky-500 font-bold text-sm">
                            <i class="material-icons">account_balance_wallet</i> Simpan Deposit
                        </div>
                        <p class="text-xs text-sky-800/80 dark:text-sky-400 leading-relaxed">
                            Nilai retur disimpan sebagai <strong>Saldo Kredit</strong> klien dan dapat digunakan untuk invoice berikutnya.
                        </p>
                    </div>
                @endif

                <div class="mt-4 pt-4 border-t border-slate-100 dark:border-slate-700">
                    <label class="text-xs text-slate-400 block mb-1">Diproses Oleh</label>
                    <div class="flex items-center gap-2">
                        <i class="material-icons text-slate-400 text-sm">person_outline</i>
                        <span class="text-sm font-medium text-slate-600 dark:text-slate-300">
                            {{ $salesReturn->user->full_name ?? 'System' }}
                        </span>
                    </div>
                </div>
            </div>

        </div>
    </div>

</div>

{{-- FORM DELETE HIDDEN --}}
@can('delete-sales-returns')
<form id="delete-return-form" method="POST" class="hidden">
    @csrf
    @method('DELETE')
</form>
@endcan

@push('scripts')
<script>
    function confirmDelete(url) {
        confirmDialog({
            title: 'Batalkan Retur?',
            text: 'Tindakan ini akan mengembalikan stok barang ke klien (Invoice) dan membatalkan jurnal akuntansi/deposit yang terbentuk.',
            icon: 'warning',
            confirmButtonText: 'Ya, Batalkan',
            cancelButtonText: 'Kembali',
            confirmButtonColor: '#e11d48', // Rose-600
        }).then((result) => {
            if (result.isConfirmed) {
                let form = document.getElementById('delete-return-form');
                form.action = url;
                form.submit();
            }
        });
    }
</script>
@endpush

@endsection
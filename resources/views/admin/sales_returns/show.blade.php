@extends('admin.layouts.app')

@section('title', 'Detail Retur Penjualan')

@section('content')
<div class="max-w-6xl mx-auto pb-20 animate-enter">
    
    {{-- HEADER --}}
    <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center mb-8 gap-4">
        <div>
            <nav class="flex text-sm text-slate-500 mb-1">
                <a href="{{ route('admin.sales-returns.index') }}" class="hover:text-indigo-600 transition-colors font-medium">Retur Penjualan</a>
                <span class="mx-2 text-slate-300">/</span>
                <span class="text-slate-800 font-semibold">Detail</span>
            </nav>
            <h1 class="text-2xl font-bold text-slate-800 tracking-tight flex items-center gap-3">
                No. Retur: <span class="font-mono text-indigo-600 bg-indigo-50 px-2 rounded">{{ $salesReturn->return_number }}</span>
            </h1>
        </div>
        <a href="{{ route('admin.sales-returns.index') }}" class="h-[48px] px-6 bg-white border border-slate-300 rounded-lg text-slate-700 text-sm font-bold hover:bg-slate-50 transition-all flex items-center justify-center gap-2 shadow-sm">
            <i class="material-icons text-[18px]">arrow_back</i> Kembali
        </a>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-12 gap-8">
        
        {{-- KOLOM KIRI (Span 8) --}}
        <div class="lg:col-span-8 space-y-8">
            
            {{-- CARD 1: INFO TRANSAKSI --}}
            <div class="dashboard-card p-0 overflow-hidden shadow-lg border-0 ring-1 ring-slate-900/5">
                <div class="px-6 py-4 border-b border-slate-100 bg-slate-50/50 flex items-center gap-3">
                    <div class="w-8 h-8 rounded-lg bg-blue-100 flex items-center justify-center text-blue-600">
                        <i class="material-icons text-[20px]">info</i>
                    </div>
                    <h3 class="text-sm font-bold text-slate-700 uppercase tracking-wide">Informasi Transaksi</h3>
                </div>
                
                <div class="p-6 grid grid-cols-1 md:grid-cols-2 gap-8">
                    {{-- Klien --}}
                    <div class="flex items-start gap-4">
                        <div class="w-12 h-12 rounded-full bg-indigo-50 flex items-center justify-center text-indigo-600 flex-shrink-0 border border-indigo-100">
                            <i class="material-icons text-xl">business</i>
                        </div>
                        <div>
                            <label class="block text-xs font-bold text-slate-400 uppercase mb-1 tracking-wide">Klien</label>
                            <h4 class="text-base font-bold text-slate-900">{{ $salesReturn->client->client_name }}</h4>
                            <div class="mt-2 flex flex-wrap items-center gap-2">
                                <span class="text-xs text-slate-500">Invoice Asal:</span>
                                <a href="{{ route('admin.invoices.show', $salesReturn->sales_invoice_id) }}" class="text-xs font-bold text-indigo-600 hover:underline font-mono bg-indigo-50 px-2 py-1 rounded border border-indigo-100">
                                    {{ $salesReturn->salesInvoice->invoice_number }}
                                </a>
                            </div>
                        </div>
                    </div>

                    {{-- Lainnya --}}
                    <div class="space-y-4 border-l border-slate-100 pl-0 md:pl-8">
                        <div class="flex justify-between items-center">
                            <span class="text-xs font-bold text-slate-500 uppercase">Tanggal Retur</span>
                            <span class="text-sm font-bold text-slate-800 flex items-center gap-2">
                                <i class="material-icons text-slate-400 text-[16px]">event</i>
                                {{ optional($salesReturn->return_date)->format('d F Y') }}
                            </span>
                        </div>
                        <div class="flex justify-between items-center">
                            <span class="text-xs font-bold text-slate-500 uppercase">Diproses Oleh</span>
                            <span class="text-sm font-bold text-slate-800 flex items-center gap-2">
                                <i class="material-icons text-slate-400 text-[16px]">person</i>
                                {{ $salesReturn->user->full_name ?? 'System' }}
                            </span>
                        </div>
                    </div>
                </div>
            </div>

            {{-- CARD 2: ITEM --}}
            <div class="dashboard-card p-0 overflow-hidden shadow-lg border-0 ring-1 ring-slate-900/5">
                <div class="px-6 py-4 border-b border-red-100 flex items-center gap-2 bg-red-50/50">
                    <i class="material-icons text-red-500 text-lg">assignment_return</i>
                    <h3 class="text-xs font-bold text-red-700 uppercase tracking-wider">Item Dikembalikan</h3>
                </div>
                <div class="overflow-x-auto">
                    <table class="dashboard-table min-w-full">
                        <thead class="bg-slate-50 border-b border-slate-200">
                            <tr>
                                <th class="pl-6 text-center w-12">#</th>
                                <th>Produk</th>
                                <th class="text-center">Qty</th>
                                <th class="text-right">Harga (@)</th>
                                <th class="text-right pr-6">Subtotal</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            @forelse($salesReturn->items as $item)
                            <tr class="hover:bg-slate-50 transition-colors">
                                <td class="pl-6 py-4 text-center text-sm text-slate-500">{{ $loop->iteration }}</td>
                                <td class="py-4">
                                    <div class="text-sm font-bold text-slate-800">{{ $item->product->product_name ?? 'Produk Dihapus' }}</div>
                                </td>
                                <td class="py-4 text-center">
                                    <span class="inline-block px-2.5 py-1 rounded-md bg-red-50 text-xs font-bold text-red-700 border border-red-100 shadow-sm">
                                        {{ $item->quantity }} {{ $item->product->unit->name ?? '' }}
                                    </span>
                                </td>
                                <td class="py-4 text-right text-sm text-slate-600 font-mono">
                                    Rp {{ number_format($item->price_per_unit, 0, ',', '.') }}
                                </td>
                                <td class="pr-6 py-4 text-right text-sm font-bold text-slate-900 font-mono">
                                    Rp {{ number_format($item->subtotal, 0, ',', '.') }}
                                </td>
                            </tr>
                            @empty
                            <tr><td colspan="5" class="text-center py-8 text-slate-500 italic">Tidak ada item.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

            {{-- CARD 3: ALASAN --}}
            @if($salesReturn->notes)
            <div class="bg-amber-50 rounded-xl border border-amber-100 p-5 flex gap-4 items-start shadow-sm">
                <div class="bg-amber-100 p-2 rounded-full text-amber-600">
                    <i class="material-icons text-xl">sticky_note_2</i>
                </div>
                <div>
                    <h4 class="text-xs font-bold text-amber-800 uppercase mb-1 tracking-wide">Alasan Retur</h4>
                    <p class="text-sm text-amber-900 italic leading-relaxed">"{{ $salesReturn->notes }}"</p>
                </div>
            </div>
            @endif

        </div>

        {{-- KOLOM KANAN: SUMMARY & AKSI (Span 4) --}}
        <div class="lg:col-span-4 space-y-6">
            
            <div class="dashboard-card p-6 shadow-xl sticky top-6 border-t-4 border-indigo-500">
                <h3 class="card-title mb-6 border-b border-slate-100 pb-3 flex items-center gap-2">
                    <i class="material-icons text-indigo-600">calculate</i> Ringkasan Nilai
                </h3>

                <div class="flex flex-col items-center justify-center mb-8 bg-slate-50 p-6 rounded-xl border border-slate-100">
                    <span class="text-xs font-bold text-slate-400 uppercase tracking-widest mb-2">Total Retur</span>
                    <span class="text-3xl font-bold text-red-600 font-mono tracking-tight">Rp {{ number_format($salesReturn->total_amount, 0, ',', '.') }}</span>
                </div>

                <div class="border-t border-dashed border-slate-200 pt-6">
                    {{-- GLOBAL DELETE HANDLER --}}
                    <form action="{{ route('admin.sales-returns.destroy', $salesReturn->return_id) }}" method="POST" class="delete-form">
                        @csrf
                        @method('DELETE')
                        <button type="submit" 
                                data-title="Batalkan Retur?" 
                                data-text="Stok akan dikembalikan dan nilai retur dihapus. <b>Data tidak dapat dikembalikan!</b>" 
                                data-btn-text="Ya, Hapus Permanen"
                                class="w-full h-[48px] bg-white border border-red-200 text-red-600 rounded-lg text-sm font-bold hover:bg-red-50 transition shadow-sm flex items-center justify-center gap-2 group">
                            <i class="material-icons text-[20px] group-hover:scale-110 transition-transform">delete_forever</i> Batalkan Retur
                        </button>
                    </form>
                    
                    <div class="mt-4 flex items-start gap-2 text-[11px] text-slate-400 bg-slate-50 p-3 rounded border border-slate-100">
                        <i class="material-icons text-[14px] mt-0.5">info</i>
                        <p class="leading-tight">
                            Membatalkan retur akan mengurangi stok barang kembali (jika stok sudah bertambah) dan membatalkan penyesuaian saldo piutang/kredit.
                        </p>
                    </div>
                </div>
            </div>

        </div>

    </div>
</div>
@endsection
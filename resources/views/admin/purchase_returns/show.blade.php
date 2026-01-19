@extends('admin.layouts.app')

@section('title', 'Detail Retur Pembelian - ' . $purchaseReturn->return_number)

@section('content')
<div class="flex flex-col gap-6 pb-20">

    {{-- 1. HEADER & ACTIONS --}}
    <div class="flex flex-col md:flex-row md:items-center justify-between gap-4 border-b border-slate-200 dark:border-slate-700 pb-4">
        <div class="flex items-center gap-4">
            <a href="{{ route('admin.purchase-returns.index') }}" 
               class="w-10 h-10 flex items-center justify-center rounded-xl bg-white border border-slate-200 text-slate-500 hover:bg-slate-50 transition-all shadow-sm dark:bg-slate-800 dark:border-slate-700 dark:text-slate-400">
                <i class="material-icons text-xl">arrow_back</i>
            </a>
            <div>
                <div class="flex items-center gap-3 mb-1">
                    <h1 class="text-2xl font-bold text-slate-800 dark:text-white tracking-tight">{{ $purchaseReturn->return_number }}</h1>
                    <span class="badge badge-primary bg-indigo-50 text-indigo-600 border border-indigo-100 dark:bg-indigo-900/30 dark:text-indigo-300 dark:border-indigo-800">
                        Completed
                    </span>
                </div>
                <p class="text-sm text-slate-500 dark:text-slate-400 flex items-center gap-1">
                    <i class="material-icons text-[14px]">event</i>
                    Tanggal Retur: {{ $purchaseReturn->return_date->format('d F Y') }}
                </p>
            </div>
        </div>

        <div class="flex items-center gap-3">
            {{-- Tombol Cetak --}}
            <button class="btn btn-secondary shadow-sm" onclick="window.print()">
                <i class="material-icons text-[18px] mr-2">print</i> Cetak
            </button>
            
            {{-- Tombol Batalkan (Reversal) - Hanya jika punya akses --}}
            @can('delete-purchase-returns')
            <button onclick="confirmReversal()" class="btn btn-danger text-rose-600 bg-rose-50 border-rose-200 hover:bg-rose-100 dark:bg-rose-900/20 dark:border-rose-900/50 dark:text-rose-400">
                <i class="material-icons text-[18px] mr-2">restore</i> Batalkan (Reversal)
            </button>
            <form id="reversal-form" action="{{ route('admin.purchase-returns.destroy', $purchaseReturn->return_id) }}" method="POST" class="hidden">
                @csrf @method('DELETE')
            </form>
            @endcan
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        
        {{-- KOLOM KIRI: DATA UTAMA (2/3) --}}
        <div class="lg:col-span-2 space-y-6">
            
            {{-- A. INFORMASI SUPPLIER & PO (Grid 2 Kolom) --}}
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                {{-- Card Supplier --}}
                <div class="card p-5 flex items-start gap-4 hover:shadow-md transition-shadow">
                    <div class="w-12 h-12 rounded-xl bg-slate-100 dark:bg-slate-700 flex items-center justify-center text-slate-500 dark:text-slate-400">
                        <i class="material-icons text-2xl">storefront</i>
                    </div>
                    <div>
                        <span class="text-[10px] font-bold text-slate-400 uppercase tracking-widest">Supplier</span>
                        <h4 class="text-base font-bold text-slate-800 dark:text-white mt-0.5">{{ $purchaseReturn->supplier->supplier_name }}</h4>
                        <p class="text-xs text-slate-500 dark:text-slate-400 mt-1 leading-relaxed">
                            {{ $purchaseReturn->supplier->person_in_charge ?? '-' }} <br>
                            {{ $purchaseReturn->supplier->phone_number ?? '-' }}
                        </p>
                    </div>
                </div>

                {{-- Card Referensi PO --}}
                <div class="card p-5 flex items-start gap-4 hover:shadow-md transition-shadow">
                    <div class="w-12 h-12 rounded-xl bg-indigo-50 dark:bg-indigo-900/30 flex items-center justify-center text-indigo-600 dark:text-indigo-400">
                        <i class="material-icons text-2xl">receipt_long</i>
                    </div>
                    <div>
                        <span class="text-[10px] font-bold text-slate-400 uppercase tracking-widest">Referensi PO Asal</span>
                        <div class="flex items-center gap-2 mt-0.5">
                            <h4 class="text-base font-bold text-indigo-600 dark:text-indigo-400">
                                <a href="{{ route('admin.purchase-orders.show', $purchaseReturn->purchase_order_id) }}" class="hover:underline decoration-2">
                                    {{ $purchaseReturn->purchaseOrder->po_number }}
                                </a>
                            </h4>
                            <i class="material-icons text-[14px] text-slate-400">open_in_new</i>
                        </div>
                        <p class="text-xs text-slate-500 dark:text-slate-400 mt-1">
                            Total PO: Rp {{ number_format($purchaseReturn->purchaseOrder->grand_total ?? 0, 0, ',', '.') }}
                        </p>
                    </div>
                </div>
            </div>

            {{-- B. TABEL BARANG --}}
            <div class="card overflow-hidden border border-slate-200 dark:border-slate-700">
                <div class="card-header bg-slate-50/50 dark:bg-slate-800/50 flex justify-between items-center">
                    <h3 class="card-header-title flex items-center gap-2 text-sm">
                        <i class="material-icons text-indigo-500 text-sm">inventory_2</i> Item Barang Diretur
                    </h3>
                    <span class="text-xs text-slate-500 font-medium bg-white dark:bg-slate-700 px-2 py-1 rounded border border-slate-100 dark:border-slate-600">
                        {{ $purchaseReturn->items->count() }} Produk
                    </span>
                </div>
                <div class="table-container border-0 rounded-none">
                    <table class="table-modern">
                        <thead>
                            <tr>
                                <th>Produk</th>
                                <th class="text-right">Harga Beli (Net)</th>
                                <th class="text-center">Qty Retur</th>
                                <th class="text-right">Total Nilai</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($purchaseReturn->items as $item)
                            <tr>
                                <td>
                                    <div class="flex flex-col">
                                        <span class="font-bold text-slate-700 dark:text-slate-200 text-sm">{{ $item->product->product_name }}</span>
                                        <span class="text-xs text-slate-400 font-mono mt-0.5">{{ $item->product->product_code }}</span>
                                    </div>
                                </td>
                                <td class="text-right font-mono text-xs text-slate-600 dark:text-slate-400">
                                    Rp {{ number_format($item->price_per_unit, 0, ',', '.') }}
                                </td>
                                <td class="text-center">
                                    <span class="inline-flex items-center px-2.5 py-1 rounded bg-rose-50 text-rose-700 dark:bg-rose-900/30 dark:text-rose-400 font-bold text-xs border border-rose-100 dark:border-rose-800">
                                        {{ (float) $item->quantity }} {{ $item->product->unit->name ?? '' }}
                                    </span>
                                </td>
                                <td class="text-right font-mono font-bold text-slate-800 dark:text-white text-sm">
                                    Rp {{ number_format($item->subtotal, 0, ',', '.') }}
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                        <tfoot>
                            <tr class="bg-slate-50 dark:bg-slate-800/50 font-bold text-slate-700 dark:text-slate-200">
                                <td colspan="3" class="text-right px-4 py-3 uppercase text-xs tracking-wider">Total Nilai Retur</td>
                                <td class="text-right px-4 py-3 font-mono text-base text-rose-600">
                                    Rp {{ number_format($purchaseReturn->total_amount, 0, ',', '.') }}
                                </td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            </div>

            {{-- C. CATATAN --}}
            @if($purchaseReturn->notes)
            <div class="card p-0 overflow-hidden bg-amber-50/50 dark:bg-amber-900/10 border border-amber-100 dark:border-amber-800/30">
                <div class="p-4 flex gap-3">
                    <i class="material-icons text-amber-500 mt-0.5">sticky_note_2</i>
                    <div>
                        <h4 class="text-xs font-bold text-amber-700 dark:text-amber-500 uppercase tracking-wide mb-1">Catatan Retur</h4>
                        <p class="text-sm text-slate-700 dark:text-slate-300 leading-relaxed italic">
                            "{{ $purchaseReturn->notes }}"
                        </p>
                    </div>
                </div>
            </div>
            @endif
        </div>

        {{-- KOLOM KANAN: STATUS & LOG (1/3) --}}
        <div class="space-y-6">
            
            {{-- 1. METODE PENANGANAN --}}
            <div class="card overflow-hidden">
                <div class="p-4 bg-slate-50 dark:bg-slate-900 border-b border-slate-200 dark:border-slate-700">
                    <h3 class="text-xs font-bold text-slate-500 uppercase tracking-wider">Metode Penanganan</h3>
                </div>
                
                <div class="p-5">
                    @if($purchaseReturn->return_handling_type == 'deduct_invoice')
                        <div class="flex flex-col items-center text-center p-4 bg-blue-50 dark:bg-blue-900/20 rounded-xl border border-blue-100 dark:border-blue-800">
                            <div class="w-10 h-10 rounded-full bg-blue-100 dark:bg-blue-800 text-blue-600 dark:text-blue-300 flex items-center justify-center mb-3">
                                <i class="material-icons">remove_circle_outline</i>
                            </div>
                            <h4 class="text-sm font-bold text-blue-700 dark:text-blue-400 mb-1">Potong Tagihan</h4>
                            <p class="text-xs text-blue-600/80 dark:text-blue-400/70 leading-relaxed">
                                Nilai retur ini secara otomatis mengurangi sisa hutang pada PO #{{ $purchaseReturn->purchaseOrder->po_number }}.
                            </p>
                        </div>
                    @else
                        <div class="flex flex-col items-center text-center p-4 bg-amber-50 dark:bg-amber-900/20 rounded-xl border border-amber-100 dark:border-amber-800">
                            <div class="w-10 h-10 rounded-full bg-amber-100 dark:bg-amber-800 text-amber-600 dark:text-amber-300 flex items-center justify-center mb-3">
                                <i class="material-icons">account_balance_wallet</i>
                            </div>
                            <h4 class="text-sm font-bold text-amber-700 dark:text-amber-400 mb-1">Simpan Deposit</h4>
                            <p class="text-xs text-amber-600/80 dark:text-amber-400/70 leading-relaxed">
                                Nilai retur disimpan sebagai <strong>Deposit Supplier</strong> (Aset) untuk digunakan pada pembelian mendatang.
                            </p>
                        </div>
                    @endif
                </div>
            </div>

            {{-- 2. SYSTEM INFO (AUDIT) --}}
            <div class="card p-5">
                <h3 class="text-[10px] font-bold text-slate-400 uppercase tracking-widest mb-4">Informasi Sistem</h3>
                <div class="space-y-4">
                    {{-- User --}}
                    <div class="flex items-center gap-3">
                        <div class="w-8 h-8 rounded-full bg-slate-100 dark:bg-slate-700 flex items-center justify-center">
                            <i class="material-icons text-sm text-slate-500">person</i>
                        </div>
                        <div>
                            <p class="text-[10px] text-slate-400 font-bold uppercase">Dibuat Oleh</p>
                            <p class="text-xs font-bold text-slate-700 dark:text-slate-200">
                                {{ $purchaseReturn->user->full_name ?? 'System' }}
                            </p>
                        </div>
                    </div>

                    {{-- Created At --}}
                    <div class="flex items-center gap-3">
                        <div class="w-8 h-8 rounded-full bg-slate-100 dark:bg-slate-700 flex items-center justify-center">
                            <i class="material-icons text-sm text-slate-500">access_time</i>
                        </div>
                        <div>
                            <p class="text-[10px] text-slate-400 font-bold uppercase">Waktu Input</p>
                            <p class="text-xs font-bold text-slate-700 dark:text-slate-200">
                                {{ $purchaseReturn->created_at->format('d M Y, H:i') }}
                            </p>
                        </div>
                    </div>
                </div>
            </div>

        </div>
    </div>
</div>

@push('scripts')
<script>
    function confirmReversal() {
        window.confirmDialog({
            title: 'Batalkan Retur?',
            text: 'Tindakan ini akan membalikkan stok ke gudang, menghapus jurnal retur, dan mengembalikan hutang/deposit supplier. Lanjutkan?',
            icon: 'warning',
            confirmText: 'Ya, Batalkan',
            confirmColor: 'danger'
        }).then((result) => {
            if (result.isConfirmed) {
                document.getElementById('reversal-form').submit();
            }
        });
    }
</script>
@endpush

@endsection
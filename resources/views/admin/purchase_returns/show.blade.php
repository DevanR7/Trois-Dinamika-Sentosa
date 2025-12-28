@extends('admin.layouts.app')

@section('title', 'Detail Retur #' . $purchaseReturn->return_number)

@section('content')
<div class="flex flex-col gap-6 pb-10">

    {{-- Header --}}
    <div class="flex flex-col md:flex-row justify-between items-start md:items-center gap-4">
        <div>
            <div class="flex items-center gap-3 mb-1">
                <h2 class="page-title text-3xl">{{ $purchaseReturn->return_number }}</h2>
                <span class="badge badge-danger flex items-center gap-1">
                    <i class="material-icons text-[14px]">assignment_return</i> Retur Pembelian
                </span>
            </div>
            <p class="text-sm text-slate-500">
                Dibuat pada {{ $purchaseReturn->created_at->format('d M Y H:i') }} oleh {{ $purchaseReturn->user->full_name ?? 'System' }}
            </p>
        </div>
        
        <div class="flex gap-2">
            <a href="{{ route('admin.purchase-returns.index') }}" class="btn btn-secondary">
                <i class="material-icons text-lg">arrow_back</i> Kembali
            </a>
            
            {{-- Tombol Hapus (Hanya contoh, pastikan controller punya validasi) --}}
            <button onclick="confirmDelete()" class="btn btn-white border border-rose-200 text-rose-600 hover:bg-rose-50">
                <i class="material-icons text-lg">delete</i> Batalkan Retur
            </button>
            <form id="delete-form" action="{{ route('admin.purchase-returns.destroy', $purchaseReturn->return_id) }}" method="POST" class="hidden">
                @csrf
                @method('DELETE')
            </form>
        </div>
    </div>

    {{-- Info Cards --}}
    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
        
        {{-- Card 1: Supplier & PO --}}
        <div class="card p-5">
            <h4 class="text-xs font-bold text-slate-400 uppercase tracking-wider mb-4">Referensi</h4>
            <div class="space-y-3 text-sm">
                <div class="flex justify-between">
                    <span class="text-slate-500">Supplier</span>
                    <a href="{{ route('admin.suppliers.show', $purchaseReturn->supplier_id) }}" class="font-bold text-indigo-600 hover:underline">
                        {{ $purchaseReturn->supplier->supplier_name }}
                    </a>
                </div>
                <div class="flex justify-between">
                    <span class="text-slate-500">No. PO Asal</span>
                    <a href="{{ route('admin.purchase-orders.show', $purchaseReturn->purchase_order_id) }}" class="font-bold text-indigo-600 hover:underline">
                        {{ $purchaseReturn->purchaseOrder->po_number }}
                    </a>
                </div>
            </div>
        </div>

        {{-- Card 2: Status & Penanganan --}}
        <div class="card p-5">
            <h4 class="text-xs font-bold text-slate-400 uppercase tracking-wider mb-4">Metode Retur</h4>
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 rounded-full bg-slate-100 dark:bg-slate-700 flex items-center justify-center text-slate-500">
                    <i class="material-icons">settings_backup_restore</i>
                </div>
                <div>
                    @if($purchaseReturn->return_handling_type == 'deduct_invoice')
                        <span class="block font-bold text-slate-800 dark:text-white">Potong Tagihan</span>
                        <span class="text-xs text-slate-500">Mengurangi hutang PO</span>
                    @else
                        <span class="block font-bold text-slate-800 dark:text-white">Simpan sbg Deposit</span>
                        <span class="text-xs text-slate-500">Menjadi saldo kredit supplier</span>
                    @endif
                </div>
            </div>
        </div>

        {{-- Card 3: Total Nilai --}}
        <div class="card p-5 bg-gradient-to-br from-rose-600 to-rose-700 text-white border-none">
            <h4 class="text-rose-100 text-xs font-bold uppercase tracking-wider mb-1">Total Nilai Retur</h4>
            <h3 class="text-3xl font-bold">Rp {{ number_format($purchaseReturn->total_amount, 0, ',', '.') }}</h3>
            <p class="text-xs text-rose-100 mt-1 opacity-80">{{ $purchaseReturn->return_date->format('d F Y') }}</p>
        </div>
    </div>

    {{-- Detail Barang --}}
    <div class="card">
        <div class="card-header">
            <h3 class="card-header-title">Rincian Barang Dikembalikan</h3>
        </div>
        <div class="table-container border-0 shadow-none rounded-none">
            <table class="table-modern w-full">
                <thead class="bg-slate-50 dark:bg-slate-800/50">
                    <tr>
                        <th class="w-[40%]">Produk</th>
                        <th class="w-[20%] text-right">Harga Beli (Satuan)</th>
                        <th class="w-[20%] text-center">Qty Retur</th>
                        <th class="w-[20%] text-right">Subtotal</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($purchaseReturn->items as $item)
                        <tr>
                            <td>
                                <div class="font-bold text-slate-700 dark:text-white">{{ $item->product->product_name }}</div>
                                <div class="text-xs text-slate-500">{{ $item->product->product_code }}</div>
                            </td>
                            <td class="text-right">
                                Rp {{ number_format($item->price_per_unit, 0, ',', '.') }}
                            </td>
                            <td class="text-center font-bold text-slate-700 dark:text-white">
                                {{ number_format($item->quantity, 0, ',', '.') }} {{ $item->product->unit->name ?? 'Unit' }}
                            </td>
                            <td class="text-right font-bold text-rose-600">
                                Rp {{ number_format($item->subtotal, 0, ',', '.') }}
                            </td>
                        </tr>
                    @endforeach
                </tbody>
                <tfoot class="bg-slate-50 dark:bg-slate-800/30 font-bold">
                    <tr>
                        <td colspan="3" class="text-right py-4 text-slate-600">TOTAL RETUR</td>
                        <td class="text-right py-4 px-6 text-rose-600 text-lg">
                            Rp {{ number_format($purchaseReturn->total_amount, 0, ',', '.') }}
                        </td>
                    </tr>
                </tfoot>
            </table>
        </div>
        
        {{-- Notes --}}
        @if($purchaseReturn->notes)
            <div class="p-6 border-t border-slate-100 dark:border-slate-700">
                <h4 class="text-xs font-bold text-slate-400 uppercase mb-2">Catatan</h4>
                <div class="bg-slate-50 dark:bg-slate-800 p-3 rounded text-slate-600 dark:text-slate-300 text-sm italic">
                    "{{ $purchaseReturn->notes }}"
                </div>
            </div>
        @endif
    </div>

</div>

@push('scripts')
<script>
    function confirmDelete() {
        confirmDialog({
            title: 'Batalkan Retur?',
            text: 'Stok barang akan dikembalikan ke gudang dan nilai retur akan dibatalkan dari hutang/deposit.',
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
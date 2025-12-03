@extends('admin.layouts.app')

{{-- LOGIC PHP: PERSIAPAN DATA --}}
@php
    $sisaUtang = $purchaseOrder->remaining_balance;
    $adjustments = $purchaseOrder->adjustments;
    $totalCreditNotesPO = $adjustments->where('type', 'credit_note')->sum('amount');
    $totalDebitNotesPO = $adjustments->where('type', 'debit_note')->sum('amount');
    $sisaTagihanPO = $sisaUtang; 
    
    // Status Badge Color Logic
    $statusClass = match($purchaseOrder->status) {
        'completed' => 'bg-emerald-100 text-emerald-700 border-emerald-200',
        'cancelled' => 'bg-red-100 text-red-700 border-red-200',
        'draft' => 'bg-slate-100 text-slate-600 border-slate-200',
        'ordered' => 'bg-blue-100 text-blue-700 border-blue-200',
        default => 'bg-amber-100 text-amber-700 border-amber-200'
    };

    // Payment Status Color
    $payStatusClass = match($purchaseOrder->payment_status) {
        'paid' => 'text-emerald-600 bg-emerald-50 border-emerald-100',
        'partially_paid' => 'text-amber-600 bg-amber-50 border-amber-100',
        'unpaid' => 'text-red-600 bg-red-50 border-red-100',
        default => 'text-slate-600 bg-slate-50 border-slate-100'
    };
@endphp

@section('title', 'Detail Pesanan Pembelian')

@push('styles')
    <style>
        #overpayment-alert { transition: all 0.3s ease-in-out; }
        .dynamic-field { transition: all 0.3s ease-in-out; overflow: hidden; }
        .dynamic-field.hidden { display: none; opacity: 0; height: 0; }
        .dynamic-field.visible { display: block; opacity: 1; height: auto; }
    </style>
@endpush

@section('content')
<div class="max-w-7xl mx-auto pb-20 animate-enter">
    
    {{-- HEADER HALAMAN --}}
    <div class="flex flex-col md:flex-row justify-between items-start md:items-center mb-8 gap-4">
        <div>
            <div class="flex items-center gap-2 text-xs font-bold text-slate-400 uppercase tracking-wider mb-1">
                <a href="{{ route('admin.purchase-orders.index') }}" class="hover:text-indigo-600 transition">Pesanan Pembelian</a>
                <i class="material-icons text-[12px]">chevron_right</i>
                <span class="text-slate-600">Detail</span>
            </div>
            <h1 class="text-3xl font-bold text-slate-800 tracking-tight flex items-center gap-3">
                <span class="font-mono text-indigo-600">{{ $purchaseOrder->po_number }}</span>
                <span class="px-2.5 py-0.5 rounded text-xs font-bold border uppercase {{ $statusClass }}">
                    {{ $purchaseOrder->status }}
                </span>
            </h1>
        </div>
        
        <div class="flex flex-wrap gap-2">
            <a href="{{ route('admin.purchase-orders.index') }}" class="px-4 py-2 bg-white border border-slate-300 rounded-lg text-slate-600 text-sm font-bold hover:bg-slate-50 transition shadow-sm">
                Kembali
            </a>

            <a href="{{ route('admin.purchase-orders.pdf', $purchaseOrder->po_id) }}" target="_blank" class="px-4 py-2 bg-white border border-slate-300 rounded-lg text-slate-700 text-sm font-bold hover:bg-slate-50 transition shadow-sm flex items-center gap-2">
                <i class="material-icons text-sm">picture_as_pdf</i> PDF
            </a>

            {{-- DROPDOWN TINDAKAN --}}
            <div class="relative" x-data="{ open: false }">
                <button @click="open = !open" @click.outside="open = false" class="px-5 py-2 bg-indigo-600 text-white border border-indigo-600 rounded-lg text-sm font-bold hover:bg-indigo-700 transition shadow-md flex items-center gap-2">
                    <i class="material-icons text-sm">settings</i> Tindakan <i class="material-icons text-sm">expand_more</i>
                </button>
                <div x-show="open" class="absolute right-0 mt-2 w-56 bg-white rounded-xl shadow-xl border border-slate-100 z-50 py-2 hidden" :class="{'hidden': !open}">
                    
                    @if (in_array($purchaseOrder->status, ['draft', 'ordered']))
                        <a href="{{ route('admin.purchase-orders.edit', $purchaseOrder->po_id) }}" class="block px-4 py-2.5 text-sm text-slate-700 hover:bg-slate-50 hover:text-indigo-600 flex items-center gap-2">
                            <i class="material-icons text-sm">edit</i> Edit Pesanan
                        </a>
                    @endif
                    
                    <a href="{{ route('admin.purchase-order-adjustments.create') }}?purchase_order_id={{ $purchaseOrder->po_id }}" class="block px-4 py-2.5 text-sm text-slate-700 hover:bg-slate-50 hover:text-indigo-600 flex items-center gap-2 border-t border-slate-50">
                        <i class="material-icons text-sm">tune</i> Buat Penyesuaian
                    </a>
                    
                    <button onclick="openModal('supplierInvoiceModal')" class="w-full text-left block px-4 py-2.5 text-sm text-slate-700 hover:bg-slate-50 hover:text-indigo-600 flex items-center gap-2 border-t border-slate-50">
                        <i class="material-icons text-sm">receipt_long</i> Input No. Faktur
                    </button>

                    @can('cancel', $purchaseOrder)
                        @if(in_array($purchaseOrder->status, ['draft', 'ordered', 'completed']))
                            <div class="border-t border-slate-50 my-1"></div>
                            <form action="{{ route('admin.purchase-orders.cancel', $purchaseOrder->po_id) }}" method="POST" class="block w-full form-confirm-danger">
                                @csrf
                                <button type="submit" class="w-full text-left px-4 py-2.5 text-sm text-red-600 hover:bg-red-50 flex items-center gap-2">
                                    <i class="material-icons text-sm">cancel</i> Batalkan Pesanan
                                </button>
                            </form>
                        @endif
                    @endcan
                </div>
            </div>
        </div>
    </div>

    {{-- LAYOUT GRID UTAMA --}}
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-8 items-start">

        {{-- KOLOM KIRI (2/3) --}}
        <div class="lg:col-span-2 space-y-8">
            
            {{-- 1. INFORMASI SUPPLIER --}}
            <div class="bg-white rounded-xl border border-slate-200 shadow-sm overflow-hidden">
                <div class="p-6 grid grid-cols-1 md:grid-cols-2 gap-8">
                    <div class="flex gap-4">
                        <div class="w-12 h-12 rounded-full bg-slate-50 flex items-center justify-center text-slate-400 border border-slate-100 flex-shrink-0">
                            <i class="material-icons text-2xl">store</i>
                        </div>
                        <div>
                            <h4 class="text-xs font-bold text-slate-400 uppercase mb-1 tracking-wider">Supplier</h4>
                            <p class="text-base font-bold text-slate-800">{{ $purchaseOrder->supplier->supplier_name }}</p>
                            <p class="text-sm text-slate-500 mt-1 leading-relaxed">{{ $purchaseOrder->supplier->address ?? 'Alamat tidak tersedia' }}</p>
                            <p class="text-sm text-slate-500 mt-1"><i class="material-icons text-[14px] align-middle mr-1">phone</i> {{ $purchaseOrder->supplier->phone_number ?? '-' }}</p>
                        </div>
                    </div>
                    <div class="flex gap-4 border-t md:border-t-0 md:border-l border-slate-100 md:pl-8 pt-4 md:pt-0">
                        <div class="w-full space-y-3">
                            <div class="flex justify-between">
                                <span class="text-sm text-slate-500">Tanggal Pesan</span>
                                <span class="text-sm font-bold text-slate-700">{{ optional($purchaseOrder->order_date)->format('d M Y') }}</span>
                            </div>
                            <div class="flex justify-between">
                                <span class="text-sm text-slate-500">Jatuh Tempo</span>
                                <span class="text-sm font-bold text-slate-700">
                                    {{ $purchaseOrder->due_date ? optional($purchaseOrder->due_date)->format('d M Y') : '-' }}
                                </span>
                            </div>
                            <div class="pt-2 border-t border-dashed border-slate-200 mt-2">
                                <div class="flex justify-between items-center">
                                    <span class="text-xs font-bold text-slate-400 uppercase">Faktur Supplier</span>
                                    @if($purchaseOrder->supplier_invoice_number)
                                        <span class="text-sm font-bold text-indigo-600 bg-indigo-50 px-2 py-0.5 rounded border border-indigo-100 cursor-pointer" onclick="openModal('supplierInvoiceModal')" title="Klik untuk edit">
                                            {{ $purchaseOrder->supplier_invoice_number }}
                                        </span>
                                    @else
                                        <button type="button" class="text-xs text-slate-400 hover:text-indigo-600 flex items-center gap-1" onclick="openModal('supplierInvoiceModal')">
                                            <i class="material-icons text-[14px]">add_circle</i> Input Faktur
                                        </button>
                                    @endif
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                @if($purchaseOrder->notes)
                    <div class="px-6 pb-6">
                        <div class="p-4 bg-amber-50 border border-amber-100 rounded-lg text-sm text-amber-800 flex gap-3 items-start">
                            <i class="material-icons text-amber-600 text-lg mt-0.5">sticky_note_2</i>
                            <div><span class="font-bold block mb-1">Catatan:</span> {{ $purchaseOrder->notes }}</div>
                        </div>
                    </div>
                @endif
            </div>

            {{-- 2. ITEM PRODUK --}}
            <div class="bg-white rounded-xl border border-slate-200 shadow-sm overflow-hidden">
                <div class="px-6 py-4 border-b border-slate-100 bg-slate-50/50 flex justify-between items-center">
                    <h3 class="text-sm font-bold text-slate-700 uppercase tracking-wider flex items-center gap-2">
                        <i class="material-icons text-indigo-500 text-base">inventory_2</i> Rincian Barang
                    </h3>
                    <span class="text-xs font-bold text-slate-500 bg-white px-2 py-1 rounded border border-slate-200">{{ $purchaseOrder->items->count() }} Item</span>
                </div>
                <div class="overflow-x-auto">
                    <table class="w-full text-left border-collapse">
                        <thead>
                            <tr class="bg-slate-50/50 border-b border-slate-200 text-xs uppercase text-slate-500 font-semibold">
                                <th class="px-6 py-3 w-10 text-center">No</th>
                                <th class="px-6 py-3">Produk</th>
                                <th class="px-6 py-3 text-center">Qty</th>
                                <th class="px-6 py-3 text-right">Harga</th>
                                <th class="px-6 py-3 text-right">Total</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100 text-sm">
                            @foreach($purchaseOrder->items as $item)
                            <tr class="hover:bg-slate-50 transition-colors">
                                <td class="px-6 py-4 text-center text-slate-400">{{ $loop->iteration }}</td>
                                <td class="px-6 py-4">
                                    <div class="font-bold text-slate-800">{{ $item->product->product_name ?? 'Produk Dihapus' }}</div>
                                    @if(isset($item->discounts) && $item->discounts->isNotEmpty())
                                        <div class="text-xs text-emerald-600 mt-1 flex items-center gap-1 font-medium">
                                            <i class="material-icons text-[10px]">local_offer</i> Disc: {{ $item->discounts->pluck('percentage')->join('%, ') }}%
                                        </div>
                                    @endif
                                </td>
                                <td class="px-6 py-4 text-center">
                                    <span class="bg-slate-100 text-slate-600 px-2 py-1 rounded text-xs font-bold">
                                        {{ (float) $item->quantity }} {{ $item->product->unit->name ?? '' }}
                                    </span>
                                </td>
                                <td class="px-6 py-4 text-right font-mono text-slate-600">
                                    Rp {{ number_format($item->price_per_unit, 0, ',', '.') }}
                                </td>
                                <td class="px-6 py-4 text-right font-bold font-mono text-slate-800">
                                    Rp {{ number_format($item->subtotal, 0, ',', '.') }}
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>

            {{-- 3. RIWAYAT KOREKSI --}}
            @if($purchaseOrder->adjustments->isNotEmpty())
            <div class="bg-white rounded-xl border border-slate-200 shadow-sm overflow-hidden">
                <div class="px-6 py-4 border-b border-slate-100 bg-red-50/30 flex items-center gap-2">
                    <i class="material-icons text-red-600 text-base">warning</i>
                    <h3 class="text-sm font-bold text-red-800 uppercase tracking-wider">Riwayat Koreksi</h3>
                </div>
                <table class="w-full text-sm text-left">
                    <tbody class="divide-y divide-slate-100">
                        @foreach ($purchaseOrder->adjustments as $adjustment)
                        <tr class="hover:bg-slate-50 transition">
                            <td class="px-6 py-4">
                                <span class="font-bold text-slate-700">{{ $adjustment->type == 'credit_note' ? 'Nota Kredit (Potongan)' : 'Nota Debit (Tambahan)' }}</span>
                                <div class="text-xs text-slate-500 mt-1 italic">{{ Str::limit($adjustment->reason, 60) }}</div>
                            </td>
                            <td class="px-6 py-4 text-slate-400 text-xs text-right">{{ $adjustment->adjustment_date->format('d/m/Y') }}</td>
                            <td class="px-6 py-4 text-right font-bold font-mono {{ $adjustment->type == 'credit_note' ? 'text-emerald-600' : 'text-red-600' }}">
                                {{ $adjustment->type == 'credit_note' ? '-' : '+' }} Rp {{ number_format($adjustment->amount, 0, ',', '.') }}
                            </td>
                            <td class="px-6 py-4 text-right w-12">
                                <form action="{{ route('admin.purchase-order-adjustments.destroy', $adjustment->adjustment_id) }}" method="POST" class="form-confirm-danger inline-block">
                                    @csrf @method('DELETE')
                                    <button type="submit" class="text-slate-300 hover:text-red-500 transition-colors"><i class="material-icons text-lg">delete</i></button>
                                </form>
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            @endif

            {{-- 4. RIWAYAT PEMBAYARAN --}}
            @if($purchaseOrder->payments->isNotEmpty())
            <div class="bg-white rounded-xl border border-slate-200 shadow-sm overflow-hidden">
                <div class="px-6 py-4 border-b border-slate-100 bg-blue-50/30 flex items-center gap-2">
                    <i class="material-icons text-blue-600 text-base">payments</i>
                    <h3 class="text-sm font-bold text-blue-800 uppercase tracking-wider">Riwayat Pembayaran</h3>
                </div>
                <table class="w-full text-sm text-left">
                    <tbody class="divide-y divide-slate-100">
                        @foreach($purchaseOrder->payments as $payment)
                        <tr class="hover:bg-slate-50 transition">
                            <td class="px-6 py-4 text-slate-600">{{ $payment->payment_date->format('d/m/Y') }}</td>
                            <td class="px-6 py-4">
                                <div class="font-bold text-slate-700">{{ $payment->paymentMethod->name ?? 'Metode Lain' }}</div>
                                <div class="text-xs text-slate-400">{{ $payment->companyBankAccount->bank_name ?? '' }}</div>
                            </td>
                            <td class="px-6 py-4 text-right font-bold text-emerald-600 font-mono">
                                Rp {{ number_format($payment->amount, 0, ',', '.') }}
                            </td>
                            <td class="px-6 py-4 text-center w-12">
                                @if($payment->status == 'completed') <span class="text-emerald-500"><i class="material-icons text-base">check_circle</i></span>
                                @else <span class="text-amber-500" title="Menunggu"><i class="material-icons text-base">hourglass_top</i></span>
                                @endif
                            </td>
                            <td class="px-6 py-4 text-right w-12">
                                <form action="{{ route('admin.purchase-orders.payments.destroy', $payment->id) }}" method="POST" class="form-confirm-danger">
                                    @csrf @method('DELETE')
                                    <button type="submit" class="text-slate-300 hover:text-red-500 transition" title="Hapus Pembayaran">
                                        <i class="material-icons text-lg">delete</i>
                                    </button>
                                </form>
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            @endif

        </div>

        {{-- KOLOM KANAN (1/3) - Ringkasan & Aksi --}}
        <div class="lg:col-span-1">
            <div class="sticky top-6 space-y-6">
                
                {{-- CARD RINGKASAN --}}
                <div class="bg-white rounded-xl border border-slate-200 shadow-lg overflow-hidden">
                    <div class="bg-slate-800 px-6 py-4 flex justify-between items-center">
                        <h3 class="text-sm font-bold text-white uppercase tracking-wider">Ringkasan Biaya</h3>
                        <span class="px-2 py-0.5 rounded text-[10px] font-bold uppercase border {{ $payStatusClass }}">
                            {{ str_replace('_', ' ', $purchaseOrder->payment_status) }}
                        </span>
                    </div>
                    <div class="p-6 space-y-4">
                        <div class="flex justify-between text-sm text-slate-600">
                            <span>Subtotal</span>
                            <span class="font-bold text-slate-800">Rp {{ number_format($purchaseOrder->subtotal ?? 0, 0, ',', '.') }}</span>
                        </div>

                        @if($purchaseOrder->disc_fee_amount > 0)
                        <div class="flex justify-between text-sm text-red-500">
                            <span>Diskon/Fee</span>
                            <span>(-) Rp {{ number_format($purchaseOrder->disc_fee_amount, 0, ',', '.') }}</span>
                        </div>
                        @endif

                        <div class="flex justify-between text-[10px] text-slate-400 pt-1">
                            <span>DPP</span>
                            <span>Rp {{ number_format($purchaseOrder->dpp ?? 0, 0, ',', '.') }}</span>
                        </div>

                        @if($purchaseOrder->ppn > 0)
                        <div class="flex justify-between text-sm text-slate-600">
                            <span>PPN ({{ $purchaseOrder->tax->rate ?? 0 }}%)</span>
                            <span>(+) Rp {{ number_format($purchaseOrder->ppn ?? 0, 0, ',', '.') }}</span>
                        </div>
                        @endif

                        @if($purchaseOrder->shipping_amount > 0)
                        <div class="flex justify-between text-sm text-slate-600">
                            <span>Ongkir</span>
                            <span>(+) Rp {{ number_format($purchaseOrder->shipping_amount ?? 0, 0, ',', '.') }}</span>
                        </div>
                        @endif

                        <div class="border-t border-slate-200 my-2"></div>

                        <div class="flex justify-between items-end">
                            <span class="text-sm font-bold text-slate-800 uppercase">Grand Total</span>
                            <span class="text-2xl font-bold text-indigo-600 font-mono tracking-tight">
                                Rp {{ number_format($purchaseOrder->total_amount ?? 0, 0, ',', '.') }}
                            </span>
                        </div>

                        {{-- STATUS KEUANGAN --}}
                        <div class="bg-slate-50 rounded-lg p-4 border border-slate-100 space-y-2 mt-4">
                            @if($totalDebitNotesPO > 0)
                                <div class="flex justify-between text-xs text-red-500 font-bold"><span>Nota Debit (Tambah)</span><span>(+) {{ number_format($totalDebitNotesPO, 0, ',', '.') }}</span></div>
                            @endif
                            @if($totalCreditNotesPO > 0)
                                <div class="flex justify-between text-xs text-emerald-500 font-bold"><span>Nota Kredit (Potong)</span><span>(-) {{ number_format($totalCreditNotesPO, 0, ',', '.') }}</span></div>
                            @endif

                            <div class="flex justify-between text-xs text-emerald-600 font-bold pb-2 border-b border-slate-200">
                                <span>Terbayar</span>
                                <span>(-) {{ number_format($purchaseOrder->amount_paid, 0, ',', '.') }}</span>
                            </div>

                            <div class="flex justify-between items-center pt-1">
                                <span class="text-xs font-bold text-slate-700 uppercase">SISA UTANG</span>
                                <span class="text-lg font-bold font-mono {{ $sisaUtang > 0.01 ? 'text-amber-600' : 'text-emerald-600' }}">
                                    Rp {{ number_format($sisaUtang, 0, ',', '.') }}
                                </span>
                            </div>
                        </div>

                        {{-- TOMBOL AKSI UTAMA --}}
                        <div class="space-y-3 mt-4">
                            {{-- Terima Barang --}}
                            @can('receive', $purchaseOrder)
                                @if(in_array($purchaseOrder->status, ['draft', 'ordered']))
                                    <form action="{{ route('admin.purchase-orders.receive', $purchaseOrder->po_id) }}" method="POST" class="form-confirm-receive">
                                        @csrf
                                        <button type="submit" class="w-full py-3 bg-indigo-600 hover:bg-indigo-700 text-white text-sm font-bold rounded-lg shadow-lg hover:shadow-indigo-500/30 transition transform hover:-translate-y-0.5 flex justify-center items-center gap-2">
                                            <i class="material-icons text-lg">inventory</i> Terima Barang
                                        </button>
                                    </form>
                                @endif
                            @endcan

                            {{-- Bayar --}}
                            @can('pay', $purchaseOrder)
                                @if($sisaUtang > 0.01 && $purchaseOrder->payment_status != 'paid')
                                    <button type="button" class="w-full py-3 bg-emerald-600 hover:bg-emerald-700 text-white text-sm font-bold rounded-lg shadow-lg hover:shadow-emerald-500/30 transition transform hover:-translate-y-0.5 flex justify-center items-center gap-2" onclick="openModal('paymentModal')">
                                        <i class="material-icons text-lg">payment</i> Catat Pembayaran
                                    </button>
                                @endif
                            @endcan
                        </div>
                    </div>
                </div>

            </div>
        </div>
    </div>
</div>

{{-- MODAL: PEMBAYARAN --}}
<div id="paymentModal" class="fixed inset-0 z-[100] hidden" role="dialog" aria-modal="true">
    <div class="fixed inset-0 bg-slate-900/60 backdrop-blur-sm transition-opacity" onclick="closeModal('paymentModal')"></div>
    <div class="fixed inset-0 z-10 flex items-center justify-center p-4">
        <div class="bg-white rounded-2xl shadow-2xl w-full max-w-md overflow-hidden animate-enter transform transition-all scale-100">
            
            <div class="bg-white px-6 py-4 border-b border-slate-100 flex justify-between items-center">
                <h3 class="text-lg font-bold text-slate-800">Catat Pembayaran Hutang</h3>
                <button type="button" class="text-slate-400 hover:text-slate-600 transition p-1 rounded-full hover:bg-slate-50" onclick="closeModal('paymentModal')">
                    <i class="material-icons">close</i>
                </button>
            </div>

            <form action="{{ route('admin.purchase-orders.payments.store', $purchaseOrder->po_id) }}" method="POST" enctype="multipart/form-data" onsubmit="prepareSubmit()">
                @csrf
                <div class="p-6 space-y-5">
                    
                    {{-- Info Sisa --}}
                    <div class="bg-amber-50 border border-amber-100 rounded-xl p-4 text-center">
                        <span class="text-xs font-bold text-amber-600 uppercase tracking-widest">Sisa Hutang Saat Ini</span>
                        <div class="text-2xl font-bold text-amber-700 font-mono mt-1">
                            Rp {{ number_format($sisaTagihanPO, 0, ',', '.') }}
                        </div>
                    </div>

                    {{-- Input Jumlah --}}
                    <div>
                        <label class="block text-xs font-bold text-slate-500 uppercase mb-1.5">Jumlah Bayar (Rp)</label>
                        <div class="relative">
                             {{-- FIX: Input Manual AutoNumeric --}}
                            <input type="text" class="form-input text-xl font-bold text-slate-800 pl-4 h-12" id="amount-formatted-po" required placeholder="0">
                            <input type="hidden" name="amount" id="amount-po">
                        </div>
                        
                        {{-- Alert Overpayment --}}
                        <div id="overpayment-alert" class="hidden mt-2 p-3 bg-blue-50 border-l-4 border-blue-400 rounded-r flex items-start gap-2 animate-enter">
                            <i class="material-icons text-blue-600 text-sm mt-0.5">info</i>
                            <div>
                                <p class="text-xs text-blue-800 font-bold">Kelebihan Bayar Deteksi</p>
                                <p class="text-[11px] text-blue-600 mt-0.5">
                                    Sisa <span id="overpayment-amount" class="font-mono font-bold">Rp 0</span> akan otomatis disimpan sebagai <b>Deposit Supplier</b>.
                                </p>
                            </div>
                        </div>
                    </div>

                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block text-xs font-bold text-slate-500 uppercase mb-1.5">Tanggal Bayar</label>
                            <input type="date" name="payment_date" class="form-input" value="{{ now()->format('Y-m-d') }}" required>
                        </div>
                        <div>
                            <label class="block text-xs font-bold text-slate-500 uppercase mb-1.5">Metode</label>
                            <select name="payment_method_id" id="payment_method_id_po" class="form-input text-sm" required>
                                @foreach(\App\Models\PaymentMethod::where('is_active', true)->where('type', '!=', 'gateway')->get() as $method)
                                    <option value="{{ $method->payment_method_id }}" data-config="{{ $method->required_fields_config }}">{{ $method->name }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>

                    <div>
                         <label class="block text-xs font-bold text-slate-500 uppercase mb-1.5">Sumber Dana (Kas/Bank)</label>
                         <select name="company_bank_account_id" class="form-input w-full text-sm" required>
                             @foreach(\App\Models\CompanyBankAccount::where('is_active', true)->get() as $acc)
                                <option value="{{ $acc->company_bank_account_id }}">{{ $acc->bank_name }} - {{ $acc->account_number }}</option>
                             @endforeach
                         </select>
                    </div>

                    {{-- Dynamic Fields --}}
                    <div id="payment-reference-group-po" class="hidden dynamic-field">
                        <label class="block text-xs font-bold text-slate-500 uppercase mb-1.5">No. Referensi <span class="text-red-500">*</span></label>
                        <input type="text" name="reference_number" id="reference_number_po" class="form-input text-sm" placeholder="No. Bukti Transfer / Cek">
                    </div>
                    <div id="payment-proof-group-po" class="hidden dynamic-field">
                        <label class="block text-xs font-bold text-slate-500 uppercase mb-1.5">Bukti Foto <span class="text-red-500">*</span></label>
                        <input type="file" name="proof_of_payment" id="proof_of_payment_po" class="block w-full text-xs text-slate-500 file:mr-4 file:py-2 file:px-4 file:rounded-full file:border-0 file:text-xs file:font-bold file:bg-indigo-50 file:text-indigo-700 hover:file:bg-indigo-100">
                    </div>

                    <div>
                        <label class="block text-xs font-bold text-slate-500 uppercase mb-1.5">Catatan (Opsional)</label>
                        <input type="text" name="notes" class="form-input text-sm" placeholder="Catatan singkat...">
                    </div>

                </div>
                <div class="bg-slate-50 px-6 py-4 flex flex-row-reverse gap-3 border-t border-slate-100">
                    <button type="submit" class="px-6 py-2.5 bg-emerald-600 text-white font-bold rounded-lg hover:bg-emerald-700 text-sm shadow-sm transition transform hover:-translate-y-0.5">
                        Simpan Pembayaran
                    </button>
                    <button type="button" class="px-6 py-2.5 bg-white border border-slate-300 text-slate-600 font-bold rounded-lg hover:bg-slate-50 text-sm transition" onclick="closeModal('paymentModal')">
                        Batal
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

{{-- MODAL: FAKTUR --}}
<div id="supplierInvoiceModal" class="fixed inset-0 z-[100] hidden" role="dialog" aria-modal="true">
    <div class="fixed inset-0 bg-slate-900/60 backdrop-blur-sm transition-opacity" onclick="closeModal('supplierInvoiceModal')"></div>
    <div class="fixed inset-0 z-10 flex items-center justify-center p-4 text-center">
        <div class="relative transform overflow-hidden rounded-xl bg-white text-left shadow-2xl transition-all w-full max-w-sm border border-slate-200">
            <div class="bg-white px-6 py-6">
                <h3 class="text-lg font-bold text-slate-800 mb-4">Update Faktur Supplier</h3>
                <form action="{{ route('admin.purchase-orders.addSupplierInvoice', $purchaseOrder->po_id) }}" method="POST">
                    @csrf
                    <div>
                        <label class="block text-xs font-bold text-slate-500 uppercase mb-1.5">Nomor Faktur / Surat Jalan</label>
                        <input type="text" class="form-input font-bold" name="supplier_invoice_number" value="{{ $purchaseOrder->supplier_invoice_number }}" required placeholder="Contoh: INV-SUP-001">
                    </div>
                    <div class="mt-6 flex justify-end gap-3">
                        <button type="button" class="px-4 py-2 bg-white border border-slate-300 rounded-lg text-slate-700 hover:bg-slate-50 text-sm font-bold" onclick="closeModal('supplierInvoiceModal')">Batal</button>
                        <button type="submit" class="px-4 py-2 bg-indigo-600 hover:bg-indigo-700 text-white rounded-lg text-sm font-bold shadow-sm">Simpan</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/autonumeric@4.6.0/dist/autoNumeric.min.js"></script>
<script>
    function openModal(id) { 
        document.getElementById(id).classList.remove('hidden'); 
        if(id === 'paymentModal') {
             setTimeout(() => document.getElementById('amount-formatted-po').focus(), 100);
        }
    }
    function closeModal(id) { document.getElementById(id).classList.add('hidden'); }
    
    // Fungsi Helper untuk Submit Form agar value hidden terisi
    function prepareSubmit() {
        const formattedInput = document.getElementById('amount-formatted-po');
        if(formattedInput) formattedInput.dispatchEvent(new Event('change'));
    }

    document.addEventListener('DOMContentLoaded', function() {
        
        // --- SweetAlert Wrappers ---
        const forms = [
            { cls: '.form-confirm-danger', title: 'Yakin?', text: 'Tindakan ini tidak dapat dibatalkan!', color: '#ef4444' },
            { cls: '.form-confirm-receive', title: 'Terima Barang?', text: 'Stok bertambah & jurnal dibuat. Pastikan fisik barang sudah sesuai.', color: '#10b981' }
        ];

        forms.forEach(conf => {
            document.querySelectorAll(conf.cls).forEach(form => {
                form.addEventListener('submit', function(e) {
                    e.preventDefault();
                    Swal.fire({
                        title: conf.title, text: conf.text, icon: 'warning',
                        showCancelButton: true, confirmButtonColor: conf.color, confirmButtonText: 'Ya, Lanjutkan'
                    }).then((result) => { if (result.isConfirmed) e.target.submit(); });
                });
            });
        });

        // --- LOGIC PEMBAYARAN ---
        const amountInput = document.getElementById('amount-formatted-po');
        const amountHidden = document.getElementById('amount-po');
        const payMethod = document.getElementById('payment_method_id_po');
        
        const alertOverpayment = document.getElementById('overpayment-alert');
        const displayOverpayment = document.getElementById('overpayment-amount');
        const refGroup = document.getElementById('payment-reference-group-po');
        const proofGroup = document.getElementById('payment-proof-group-po');
        const refInput = document.getElementById('reference_number_po');
        const proofInput = document.getElementById('proof_of_payment_po');
        
        const sisaTagihan = {{ (float)($sisaTagihanPO ?? 0) }};

        if (amountInput && typeof AutoNumeric !== 'undefined') {
            // Init Manual agar tidak bentrok dengan global app.js
            const an = new AutoNumeric(amountInput, { 
                currencySymbol: 'Rp ', 
                currencySymbolPlacement: 'p',
                decimalCharacter: ',', 
                digitGroupSeparator: '.', 
                decimalPlaces: 0, 
                minimumValue: '0',
                emptyInputBehavior: 'zero',
                unformatOnSubmit: true
            });

            // Set default value saat modal dibuka
            document.getElementById('paymentModal')?.addEventListener('click', function(e) {
                // Reset logic jika perlu
                if (an.getRawValue() == 0) {
                    an.set(sisaTagihan);
                    amountHidden.value = sisaTagihan;
                }
            });

            // Listener Perubahan Nilai & Cek Overpayment
            amountInput.addEventListener('autoNumeric:rawValueModified', e => {
                const val = e.detail.newRawValue;
                amountHidden.value = val;
                
                if (val > (sisaTagihan + 0.01)) {
                    const diff = val - sisaTagihan;
                    alertOverpayment.classList.remove('hidden');
                    displayOverpayment.textContent = new Intl.NumberFormat('id-ID', { style: 'currency', currency: 'IDR', minimumFractionDigits: 0 }).format(diff);
                } else {
                    alertOverpayment.classList.add('hidden');
                }
            });
        }

        // Toggle Method Fields (Dynamic)
        if (payMethod) {
            payMethod.addEventListener('change', function() {
                const selectedOption = this.options[this.selectedIndex];
                const config = selectedOption.getAttribute('data-config') || 'none';
                
                // Reset
                refGroup.classList.add('hidden'); refGroup.classList.remove('visible');
                proofGroup.classList.add('hidden'); proofGroup.classList.remove('visible');
                refInput.required = false;
                proofInput.required = false;

                if (config === 'reference_only' || config === 'proof_and_reference') {
                    refGroup.classList.remove('hidden');
                    setTimeout(() => refGroup.classList.add('visible'), 10);
                    refInput.required = true;
                }
                
                if (config === 'proof_only' || config === 'proof_and_reference') {
                    proofGroup.classList.remove('hidden');
                    setTimeout(() => proofGroup.classList.add('visible'), 10);
                    proofInput.required = true;
                }
            });
        }
    });
</script>
@endpush
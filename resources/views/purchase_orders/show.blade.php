@extends('layouts.app')

{{-- ==================================================================== --}}
{{-- ✅ BLOK PHP GLOBAL (LOGIC HITUNGAN) --}}
{{-- ==================================================================== --}}
@php
    $sisaUtang = $purchaseOrder->remaining_balance;
    $adjustments = $purchaseOrder->adjustments;
    $totalCreditNotesPO = $adjustments->where('type', 'credit_note')->sum('amount');
    $totalDebitNotesPO = $adjustments->where('type', 'debit_note')->sum('amount');
    $totalReturDeposit = $purchaseOrder->returns
        ->where('return_handling_type', 'store_as_deposit')
        ->sum('total_amount');

    $sisaTagihanPO = $sisaUtang; 
    $saldoDepositSupplier = $purchaseOrder->supplier->balance ?? 0;
@endphp

@section('title', 'Detail Pesanan')

@section('content')
<div class="max-w-7xl mx-auto">
    
    {{-- ==================================================================== --}}
    {{-- HEADER HALAMAN --}}
    {{-- ==================================================================== --}}
    <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center mb-6 gap-4">
        <div>
            <div class="flex items-center gap-2 text-sm text-gray-500 mb-1">
                <a href="{{ route('purchase-orders.index') }}" class="hover:text-indigo-600 transition">Pesanan</a>
                <span>/</span>
                <span class="text-gray-800">Detail</span>
            </div>
            <h2 class="text-2xl font-bold text-gray-900 tracking-tight flex items-center gap-3">
                <span class="font-mono">{{ $purchaseOrder->po_number }}</span>
                
                {{-- Status Badge --}}
                @if($purchaseOrder->status == 'completed') 
                    <span class="px-2.5 py-0.5 rounded-full text-xs font-bold bg-green-100 text-green-800 border border-green-200 uppercase">Diterima</span>
                @elseif($purchaseOrder->status == 'cancelled') 
                    <span class="px-2.5 py-0.5 rounded-full text-xs font-bold bg-red-100 text-red-800 border border-red-200 uppercase">Dibatalkan</span>
                @elseif($purchaseOrder->status == 'draft')
                    <span class="px-2.5 py-0.5 rounded-full text-xs font-bold bg-gray-100 text-gray-800 border border-gray-200 uppercase">Draft</span>
                @else 
                    <span class="px-2.5 py-0.5 rounded-full text-xs font-bold bg-yellow-100 text-yellow-800 border border-yellow-200 uppercase">{{ Str::title($purchaseOrder->status) }}</span>
                @endif
            </h2>
        </div>
        
        <div class="flex flex-wrap gap-3">
            <a href="{{ route('purchase-orders.index') }}" class="px-4 py-2 bg-white border border-gray-300 rounded-lg text-gray-700 text-sm font-medium hover:bg-gray-50 transition shadow-sm">
                Kembali
            </a>

            {{-- TOMBOL OPSI (FIX: Menggunakan Click Toggle, bukan Hover) --}}
            <div class="relative">
                <button onclick="toggleDropdown('opsi-dropdown')" class="px-4 py-2 bg-white border border-gray-300 rounded-lg text-gray-700 text-sm font-medium hover:bg-gray-50 transition shadow-sm flex items-center gap-2 focus:ring-2 focus:ring-indigo-500 focus:outline-none">
                    <i class="bi bi-gear"></i> Opsi <i class="bi bi-chevron-down text-xs"></i>
                </button>
                
                {{-- Dropdown Content --}}
                <div id="opsi-dropdown" class="hidden absolute right-0 mt-2 w-56 bg-white rounded-lg shadow-xl border border-gray-200 z-50 origin-top-right">
                    <div class="py-1">
                        @if (in_array($purchaseOrder->status, ['draft', 'ordered']))
                            <a href="{{ route('purchase-orders.edit', $purchaseOrder->po_id) }}" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100 hover:text-indigo-600">
                                <i class="bi bi-pencil-square mr-2"></i> Edit Pesanan
                            </a>
                        @endif
                        
                        <a href="{{ route('purchase-order-adjustments.create') }}?purchase_order_id={{ $purchaseOrder->po_id }}" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100 hover:text-indigo-600 border-t border-gray-100">
                            <i class="bi bi-file-earmark-diff mr-2"></i> Buat Penyesuaian
                        </a>
                        
                        <a href="{{ route('purchase-orders.pdf', $purchaseOrder->po_id) }}" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100 hover:text-indigo-600">
                            <i class="bi bi-file-earmark-pdf mr-2"></i> Download PDF
                        </a>

                        @can('cancel', $purchaseOrder)
                            @if(in_array($purchaseOrder->status, ['draft', 'ordered', 'completed']))
                                <form action="{{ route('purchase-orders.cancel', $purchaseOrder->po_id) }}" method="POST" class="form-cancel-po border-t border-gray-100">
                                    @csrf
                                    <button type="submit" class="w-full text-left px-4 py-2 text-sm text-red-600 hover:bg-red-50 font-medium">
                                        <i class="bi bi-x-circle mr-2"></i> Batalkan Pesanan
                                    </button>
                                </form>
                            @endif
                        @endcan
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- ==================================================================== --}}
    {{-- ✅ FLASH MESSAGES (FIX: Menambahkan Notifikasi Disini) --}}
    {{-- ==================================================================== --}}
    @if (session('success'))
        <div class="mb-6 bg-green-50 border-l-4 border-green-500 p-4 rounded-r shadow-sm flex justify-between items-center animate-fade-in-down">
            <div class="flex items-center">
                <i class="bi bi-check-circle-fill text-green-500 text-xl mr-3"></i>
                <div class="text-sm text-green-700 font-medium">{{ session('success') }}</div>
            </div>
            <button type="button" class="text-green-500 hover:text-green-700" onclick="this.parentElement.remove()">
                <i class="bi bi-x text-lg"></i>
            </button>
        </div>
    @endif
    @if (session('error'))
        <div class="mb-6 bg-red-50 border-l-4 border-red-500 p-4 rounded-r shadow-sm flex justify-between items-center animate-fade-in-down">
            <div class="flex items-center">
                <i class="bi bi-exclamation-triangle-fill text-red-500 text-xl mr-3"></i>
                <div class="text-sm text-red-700 font-medium">{{ session('error') }}</div>
            </div>
            <button type="button" class="text-red-500 hover:text-red-700" onclick="this.parentElement.remove()">
                <i class="bi bi-x text-lg"></i>
            </button>
        </div>
    @endif

    <div class="grid grid-cols-1 lg:grid-cols-12 gap-6">
        
        {{-- ===================================================
             KOLOM KIRI: DETAIL & ITEMS (Span 8)
             =================================================== --}}
        <div class="lg:col-span-8 space-y-6">
            
            {{-- CARD 1: INFO SUPPLIER & TANGGAL --}}
            <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
                <div class="px-6 py-4 border-b border-gray-100 bg-gray-50 flex justify-between items-center">
                    <h3 class="text-xs font-bold text-gray-500 uppercase tracking-wider flex items-center gap-2">
                        <i class="bi bi-shop text-indigo-500"></i> Informasi Supplier
                    </h3>
                    <span class="text-xs text-gray-400">Dibuat oleh: {{ $purchaseOrder->creator->full_name ?? 'System' }}</span>
                </div>
                <div class="p-6">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        {{-- Supplier --}}
                        <div class="flex items-start gap-4">
                            <div class="w-12 h-12 rounded-full bg-indigo-50 flex items-center justify-center text-indigo-600 flex-shrink-0 border border-indigo-100">
                                <i class="bi bi-building text-xl"></i>
                            </div>
                            <div>
                                <h4 class="text-base font-bold text-gray-900">{{ $purchaseOrder->supplier->supplier_name }}</h4>
                                <p class="text-sm text-gray-500 mt-1">{{ $purchaseOrder->supplier->address ?? 'Alamat tidak tersedia' }}</p>
                                <div class="mt-2 text-xs text-gray-400">
                                    <i class="bi bi-telephone mr-1"></i> {{ $purchaseOrder->supplier->phone_number ?? '-' }}
                                </div>
                            </div>
                        </div>

                        {{-- Tanggal & Faktur --}}
                        <div class="space-y-3 border-l border-gray-100 pl-0 md:pl-6">
                            <div class="flex justify-between items-center">
                                <span class="text-xs font-medium text-gray-500 uppercase">Tgl Pesan</span>
                                <span class="text-sm font-semibold text-gray-900">{{ optional($purchaseOrder->order_date)->format('d M Y') }}</span>
                            </div>
                            <div class="flex justify-between items-center">
                                <span class="text-xs font-medium text-gray-500 uppercase">Jatuh Tempo</span>
                                <span class="text-sm font-semibold text-gray-900">{{ $purchaseOrder->due_date ? optional($purchaseOrder->due_date)->format('d M Y') : '-' }}</span>
                            </div>
                            <div class="flex justify-between items-center pt-2 border-t border-dashed border-gray-200">
                                <span class="text-xs font-medium text-gray-500 uppercase">Faktur Supplier</span>
                                @if($purchaseOrder->supplier_invoice_number)
                                    <span class="text-sm font-bold text-indigo-600 cursor-pointer hover:underline" onclick="openModal('supplierInvoiceModal')">
                                        {{ $purchaseOrder->supplier_invoice_number }} <i class="bi bi-pencil-square text-xs ml-1"></i>
                                    </span>
                                @else
                                    <button type="button" class="text-xs text-indigo-600 font-medium hover:underline flex items-center gap-1" onclick="openModal('supplierInvoiceModal')">
                                        <i class="bi bi-plus-circle"></i> Input Faktur
                                    </button>
                                @endif
                            </div>
                        </div>
                    </div>

                    @if($purchaseOrder->notes)
                        <div class="mt-6 p-3 bg-yellow-50 border border-yellow-100 rounded-lg text-sm text-yellow-800 italic flex gap-2">
                            <i class="bi bi-sticky mt-0.5"></i>
                            <div><span class="font-bold">Catatan:</span> {{ $purchaseOrder->notes }}</div>
                        </div>
                    @endif
                </div>
            </div>

            {{-- CARD 2: ITEM PRODUK --}}
            <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
                <div class="px-6 py-4 border-b border-gray-100 flex justify-between items-center">
                    <h3 class="text-xs font-bold text-gray-500 uppercase tracking-wider">Rincian Item</h3>
                    <span class="px-2 py-1 bg-gray-100 text-gray-600 text-xs rounded-md font-bold">{{ $purchaseOrder->items->count() }} Produk</span>
                </div>
                <div class="overflow-x-auto">
                    <table class="w-full text-left border-collapse">
                        <thead class="bg-gray-50 border-b border-gray-200">
                            <tr>
                                <th class="px-6 py-3 text-xs font-bold text-gray-500 uppercase w-12 text-center">No</th>
                                <th class="px-6 py-3 text-xs font-bold text-gray-500 uppercase">Produk</th>
                                <th class="px-6 py-3 text-xs font-bold text-gray-500 uppercase text-center">Qty</th>
                                <th class="px-6 py-3 text-xs font-bold text-gray-500 uppercase text-right">Harga (@)</th>
                                <th class="px-6 py-3 text-xs font-bold text-gray-500 uppercase text-right">Subtotal</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            @foreach($purchaseOrder->items as $item)
                            <tr class="hover:bg-gray-50 transition">
                                <td class="px-6 py-4 text-center text-sm text-gray-500">{{ $loop->iteration }}</td>
                                <td class="px-6 py-4">
                                    <div class="text-sm font-bold text-gray-900">{{ $item->product->product_name ?? 'Produk Dihapus' }}</div>
                                    @if(isset($item->discounts) && $item->discounts->isNotEmpty())
                                        <div class="text-xs text-green-600 mt-1 flex items-center gap-1">
                                            <i class="bi bi-tag-fill"></i> Disc: {{ $item->discounts->pluck('percentage')->join('%, ') }}%
                                        </div>
                                    @endif
                                </td>
                                <td class="px-6 py-4 text-center">
                                    <span class="px-2.5 py-1 rounded-md bg-gray-100 text-xs font-bold text-gray-700 border border-gray-200">
                                        {{ $item->quantity }} {{ $item->product->unit->name ?? '' }}
                                    </span>
                                </td>
                                <td class="px-6 py-4 text-right text-sm text-gray-600">
                                    Rp {{ number_format($item->price_per_unit, 0, ',', '.') }}
                                </td>
                                <td class="px-6 py-4 text-right text-sm font-bold text-gray-900">
                                    Rp {{ number_format($item->subtotal, 0, ',', '.') }}
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
            
            {{-- CARD 3: RIWAYAT KOREKSI (Conditional) --}}
            @if($purchaseOrder->adjustments->isNotEmpty())
            <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
                <div class="px-6 py-4 border-b border-gray-100 bg-red-50">
                    <h3 class="text-xs font-bold text-red-600 uppercase tracking-wider flex items-center gap-2">
                        <i class="bi bi-exclamation-circle"></i> Riwayat Koreksi
                    </h3>
                </div>
                <table class="w-full text-left border-collapse">
                    <thead class="bg-white border-b border-gray-200">
                        <tr>
                            <th class="px-6 py-3 text-xs font-bold text-gray-500 uppercase">Tanggal</th>
                            <th class="px-6 py-3 text-xs font-bold text-gray-500 uppercase">Tipe</th>
                            <th class="px-6 py-3 text-xs font-bold text-gray-500 uppercase text-right">Nilai</th>
                            <th class="px-6 py-3 text-xs font-bold text-gray-500 uppercase">Alasan</th>
                            <th class="px-6 py-3 text-xs font-bold text-gray-500 uppercase text-right">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        @foreach ($purchaseOrder->adjustments as $adjustment)
                        <tr class="hover:bg-gray-50 transition">
                            <td class="px-6 py-3 text-sm text-gray-600">{{ $adjustment->adjustment_date->format('d/m/Y') }}</td>
                            <td class="px-6 py-3">
                                @if($adjustment->type == 'credit_note')
                                    <span class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-green-100 text-green-700 border border-green-200 uppercase">Nota Kredit</span>
                                @else
                                    <span class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-red-100 text-red-700 border border-red-200 uppercase">Nota Debit</span>
                                @endif
                            </td>
                            <td class="px-6 py-3 text-right text-sm font-bold text-gray-900">
                                Rp {{ number_format($adjustment->amount, 0, ',', '.') }}
                            </td>
                            <td class="px-6 py-3 text-xs text-gray-500 italic">{{ Str::limit($adjustment->reason, 40) }}</td>
                            <td class="px-6 py-3 text-right">
                                <form action="{{ route('purchase-order-adjustments.destroy', $adjustment->adjustment_id) }}" method="POST" class="form-cancel-po-adjustment inline-block">
                                    @csrf @method('DELETE')
                                    <button type="submit" class="text-red-500 hover:text-red-700 text-xs font-bold uppercase tracking-wide hover:underline">
                                        Hapus
                                    </button>
                                </form>
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            @endif

            {{-- CARD 4: RIWAYAT PEMBAYARAN (Conditional) --}}
            @if($purchaseOrder->payments->isNotEmpty())
            <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
                <div class="px-6 py-4 border-b border-gray-100 bg-blue-50">
                    <h3 class="text-xs font-bold text-blue-700 uppercase tracking-wider flex items-center gap-2">
                        <i class="bi bi-wallet2"></i> Riwayat Pembayaran
                    </h3>
                </div>
                <table class="w-full text-left border-collapse">
                    <thead class="bg-white border-b border-gray-200">
                        <tr>
                            <th class="px-6 py-3 text-xs font-bold text-gray-500 uppercase">Tanggal</th>
                            <th class="px-6 py-3 text-xs font-bold text-gray-500 uppercase">Metode</th>
                            <th class="px-6 py-3 text-xs font-bold text-gray-500 uppercase text-right">Jumlah</th>
                            <th class="px-6 py-3 text-xs font-bold text-gray-500 uppercase text-center">Status</th>
                            <th class="px-6 py-3 text-xs font-bold text-gray-500 uppercase text-right">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        @foreach($purchaseOrder->payments as $payment)
                        <tr class="hover:bg-gray-50 transition">
                            <td class="px-6 py-3 text-sm text-gray-600">{{ $payment->payment_date->format('d/m/Y') }}</td>
                            <td class="px-6 py-3 text-sm text-gray-600">{{ $payment->paymentMethod->name ?? '-' }}</td>
                            <td class="px-6 py-3 text-right text-sm font-bold text-green-600">
                                Rp {{ number_format($payment->amount, 0, ',', '.') }}
                            </td>
                            <td class="px-6 py-3 text-center">
                                @if($payment->status == 'completed') <i class="bi bi-check-circle-fill text-green-500" title="Selesai"></i>
                                @elseif($payment->status == 'pending_clearance') <i class="bi bi-clock-fill text-yellow-500" title="Menunggu Kliring"></i>
                                @else <span class="text-xs text-gray-400">{{ $payment->status }}</span>
                                @endif
                            </td>
                            <td class="px-6 py-3 text-right">
                                @php $paymentLabel = 'Pembayaran Rp ' . number_format($payment->amount, 0, ',', '.'); @endphp
                                <form action="{{ route('purchase-orders.payments.destroy', $payment) }}" method="POST" class="form-delete-po-payment inline-block" data-payment-label="{{ $paymentLabel }}">
                                    @csrf @method('DELETE')
                                    <button type="submit" class="text-red-500 hover:text-red-700 transition"><i class="bi bi-trash"></i></button>
                                </form>
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            @endif
        </div>

        {{-- ===================================================
             KOLOM KANAN: RINGKASAN & AKSI (Lebar: 4/12)
             =================================================== --}}
        <div class="lg:col-span-4 space-y-6">
            
            {{-- CARD RINGKASAN BIAYA (Sticky) --}}
            <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6 sticky top-6">
                <h3 class="text-sm font-bold text-gray-900 mb-4 flex items-center gap-2 border-b border-gray-100 pb-3">
                    <i class="bi bi-calculator text-indigo-500"></i> Ringkasan Biaya
                </h3>

                <div class="space-y-3 text-sm text-gray-600 mb-4 border-b border-dashed border-gray-200 pb-4">
                    <div class="flex justify-between">
                        <span>Subtotal Barang</span>
                        <span class="font-medium text-gray-900">Rp {{ number_format($purchaseOrder->subtotal ?? 0, 0, ',', '.') }}</span>
                    </div>
                    @if($purchaseOrder->disc_fee_amount > 0)
                    <div class="flex justify-between text-red-500"><span>Diskon/Fee</span><span>(-) Rp {{ number_format($purchaseOrder->disc_fee_amount ?? 0, 0, ',', '.') }}</span></div>
                    @endif
                    @if($purchaseOrder->rounding_discount_amount > 0)
                    <div class="flex justify-between text-red-500"><span>Pembulatan</span><span>(-) Rp {{ number_format($purchaseOrder->rounding_discount_amount ?? 0, 0, ',', '.') }}</span></div>
                    @endif
                    
                    <div class="flex justify-between text-gray-400 text-xs pt-1"><span>DPP</span><span>Rp {{ number_format($purchaseOrder->dpp ?? 0, 0, ',', '.') }}</span></div>
                    @if($purchaseOrder->ppn > 0)
                    <div class="flex justify-between"><span>PPN ({{ $purchaseOrder->tax->rate ?? 0 }}%)</span><span>(+) Rp {{ number_format($purchaseOrder->ppn ?? 0, 0, ',', '.') }}</span></div>
                    @endif
                    @if($purchaseOrder->shipping_amount > 0)
                    <div class="flex justify-between"><span>Ongkir</span><span>(+) Rp {{ number_format($purchaseOrder->shipping_amount ?? 0, 0, ',', '.') }}</span></div>
                    @endif
                </div>

                <div class="flex justify-between items-center mb-6">
                    <span class="text-sm font-bold text-gray-900 uppercase">Grand Total</span>
                    <span class="text-xl font-bold text-indigo-600">Rp {{ number_format($purchaseOrder->total_amount ?? 0, 0, ',', '.') }}</span>
                </div>

                {{-- STATUS KEUANGAN (SISA TAGIHAN) --}}
                <div class="bg-gray-50 rounded-lg p-4 border border-gray-200">
                    <h4 class="text-xs font-bold text-gray-400 uppercase mb-3 tracking-wider">Status Keuangan</h4>
                    
                    @if($totalDebitNotesPO > 0)
                        <div class="flex justify-between text-xs text-red-500 mb-1"><span>Nota Debit</span><span>(+) {{ number_format($totalDebitNotesPO, 0, ',', '.') }}</span></div>
                    @endif
                    @if($totalCreditNotesPO > 0)
                        <div class="flex justify-between text-xs text-green-500 mb-1"><span>Nota Kredit</span><span>(-) {{ number_format($totalCreditNotesPO, 0, ',', '.') }}</span></div>
                    @endif
                    @if($purchaseOrder->total_returned > 0)
                        <div class="flex justify-between text-xs text-yellow-600 mb-1"><span>Retur</span><span>(-) {{ number_format($purchaseOrder->total_returned, 0, ',', '.') }}</span></div>
                    @endif

                    <div class="flex justify-between text-xs text-green-600 mb-2 pb-2 border-b border-gray-200">
                        <span>Sudah Dibayar</span>
                        <span>(-) {{ number_format($purchaseOrder->amount_paid, 0, ',', '.') }}</span>
                    </div>

                    <div class="flex justify-between items-center">
                        <span class="text-xs font-bold text-gray-700">SISA TAGIHAN</span>
                        <span class="text-lg font-bold {{ $sisaUtang > 0.01 ? 'text-red-600' : 'text-green-600' }}">
                            Rp {{ number_format($sisaUtang, 0, ',', '.') }}
                        </span>
                    </div>
                </div>

                {{-- TOMBOL AKSI UTAMA --}}
                <div class="mt-6 space-y-3">
                    {{-- Terima Barang --}}
                    @can('receive', $purchaseOrder)
                        @if(in_array($purchaseOrder->status, ['draft', 'ordered']))
                            <form id="receive-goods-form" action="{{ route('purchase-orders.receive', $purchaseOrder->po_id) }}" method="POST">
                                @csrf
                                <button type="submit" class="w-full py-2.5 bg-indigo-600 hover:bg-indigo-700 text-white text-sm font-bold rounded-lg shadow-md transition flex justify-center items-center gap-2">
                                    <i class="bi bi-box-seam"></i> Terima Barang
                                </button>
                            </form>
                        @endif
                    @endcan

                    {{-- Bayar --}}
                    @can('pay', $purchaseOrder)
                        @if($sisaUtang > 0.01 && $purchaseOrder->payment_status != 'paid')
                            @php
                                $isDP = in_array($purchaseOrder->status, ['draft', 'ordered']);
                                $btnColor = $isDP ? 'bg-yellow-500 hover:bg-yellow-600 text-white' : 'bg-green-600 hover:bg-green-700 text-white';
                                $btnText = $isDP ? 'Catat DP (Uang Muka)' : 'Catat Pembayaran';
                            @endphp
                            <button type="button" class="w-full py-2.5 {{ $btnColor }} text-sm font-bold rounded-lg shadow-md transition flex justify-center items-center gap-2" onclick="openModal('paymentModal')">
                                <i class="bi bi-cash-coin"></i> {{ $btnText }}
                            </button>
                        @endif
                    @endcan
                </div>

            </div>
        </div>

    </div>
</div>

{{-- ==================================================================== --}}
{{-- MODAL PEMBAYARAN (TAILWIND) --}}
{{-- ==================================================================== --}}
<div id="paymentModal" class="relative z-[100] hidden" aria-labelledby="modal-title" role="dialog" aria-modal="true">
    {{-- Backdrop --}}
    <div class="fixed inset-0 bg-gray-900 bg-opacity-50 transition-opacity backdrop-blur-sm"></div>
    
    {{-- Modal Content Wrapper --}}
    <div class="fixed inset-0 z-10 w-screen overflow-y-auto">
        <div class="flex min-h-full items-center justify-center p-4 text-center sm:p-0">
            <div class="relative transform overflow-hidden rounded-xl bg-white text-left shadow-xl transition-all sm:my-8 sm:w-full sm:max-w-lg">
                
                {{-- Header Modal --}}
                <div class="bg-white px-4 pb-4 pt-5 sm:p-6 sm:pb-4 border-b border-gray-100">
                    <div class="flex justify-between items-center">
                        <h3 class="text-lg font-bold leading-6 text-gray-900">Catat Pembayaran Baru</h3>
                        <button type="button" class="text-gray-400 hover:text-gray-600" onclick="closeModal('paymentModal')">
                            <i class="bi bi-x-lg text-lg"></i>
                        </button>
                    </div>
                </div>
                
                <form action="{{ route('purchase-orders.payments.store', $purchaseOrder->po_id) }}" method="POST" enctype="multipart/form-data" id="paymentForm">
                    @csrf
                    <div class="px-6 py-4 space-y-4">
                        
                        {{-- Info Sisa --}}
                        <div class="bg-blue-50 border border-blue-100 rounded-lg p-3 text-sm space-y-1">
                            <div class="flex justify-between text-blue-800 font-bold">
                                <span>Sisa Utang:</span>
                                <span id="modal-po-sisa-tagihan-display">Rp {{ number_format($sisaTagihanPO, 0, ',', '.') }}</span>
                            </div>
                        </div>

                        {{-- Info Deposit --}}
                        @if($saldoDepositSupplier > 0)
                        <div class="bg-green-50 border border-green-100 rounded-lg p-3 text-sm">
                            <div class="flex justify-between font-bold text-green-800">
                                <span>Saldo Deposit Tersedia:</span>
                                <span id="modal-debit-balance-display">Rp {{ number_format($saldoDepositSupplier, 0, ',', '.') }}</span>
                            </div>
                            <div class="mt-2 flex items-center gap-2">
                                <input class="rounded border-gray-300 text-indigo-600 focus:ring-indigo-500 cursor-pointer" type="checkbox" id="modal-use-debit" name="use_debit_balance" value="1">
                                <label class="text-xs font-medium text-gray-700 cursor-pointer" for="modal-use-debit">Gunakan Saldo</label>
                            </div>
                        </div>
                        @endif

                        <div>
                            <label class="block text-xs font-bold text-gray-700 uppercase mb-1">Jumlah Bayar</label>
                            <input type="text" class="w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 text-end font-bold text-lg" id="amount-formatted-po" required>
                            <input type="hidden" name="amount" id="amount-po">
                            <div id="amount-error-po" class="text-red-500 text-xs mt-1"></div>
                        </div>
                        
                        <div>
                            <label class="block text-xs font-bold text-gray-700 uppercase mb-1">Tanggal</label>
                            <input type="date" class="w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 text-sm" name="payment_date" value="{{ now()->format('Y-m-d') }}" required>
                        </div>

                        <div>
                            <label class="block text-xs font-bold text-gray-700 uppercase mb-1">Metode Pembayaran</label>
                            <select class="w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 text-sm" id="payment_method_id_po" name="payment_method_id" required>
                                <option value="">-- Pilih Metode --</option>
                                @foreach($paymentMethods as $method)
                                    <option value="{{ $method->payment_method_id }}" data-config="{{ $method->required_fields_config }}">{{ $method->name }}</option>
                                @endforeach
                            </select>
                        </div>

                        <div>
                            <label class="block text-xs font-bold text-gray-700 uppercase mb-1">Keluar dari Akun</label>
                            <select class="w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 text-sm" id="company_bank_account_id_po" name="company_bank_account_id" required>
                                <option value="">-- Pilih Akun Kas/Bank --</option>
                                @foreach($companyBankAccounts as $account)
                                    <option value="{{ $account->company_bank_account_id }}">{{ $account->bank_name }} - {{ $account->account_number }}</option>
                                @endforeach
                            </select>
                        </div>

                        <div id="payment-reference-group-po" style="display: none;">
                            <label class="block text-xs font-bold text-gray-700 uppercase mb-1">No. Referensi</label>
                            <input type="text" class="w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 text-sm" name="reference_number" id="reference_number_po">
                        </div>
                        <div id="payment-proof-group-po" style="display: none;">
                            <label class="block text-xs font-bold text-gray-700 uppercase mb-1">Bukti Foto</label>
                            <input type="file" class="w-full text-sm text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded-md file:border-0 file:text-xs file:font-semibold file:bg-indigo-50 file:text-indigo-700 hover:file:bg-indigo-100" name="proof_of_payment" id="proof_of_payment_po">
                        </div>
                        
                        <div>
                            <label class="block text-xs font-bold text-gray-700 uppercase mb-1">Catatan</label>
                            <textarea class="w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 text-sm" name="notes" rows="2"></textarea>
                        </div>
                    </div>

                    <div class="bg-gray-50 px-4 py-3 sm:flex sm:flex-row-reverse sm:px-6 border-t border-gray-100">
                        <button type="submit" class="inline-flex w-full justify-center rounded-md bg-indigo-600 px-3 py-2 text-sm font-semibold text-white shadow-sm hover:bg-indigo-500 sm:ml-3 sm:w-auto">Simpan</button>
                        <button type="button" class="mt-3 inline-flex w-full justify-center rounded-md bg-white px-3 py-2 text-sm font-semibold text-gray-900 shadow-sm ring-1 ring-inset ring-gray-300 hover:bg-gray-50 sm:mt-0 sm:w-auto" onclick="closeModal('paymentModal')">Batal</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

{{-- MODAL FAKTUR (TAILWIND) --}}
<div id="supplierInvoiceModal" class="relative z-[100] hidden" aria-labelledby="modal-title" role="dialog" aria-modal="true">
    <div class="fixed inset-0 bg-gray-900 bg-opacity-50 transition-opacity backdrop-blur-sm"></div>
    <div class="fixed inset-0 z-10 w-screen overflow-y-auto">
        <div class="flex min-h-full items-center justify-center p-4 text-center sm:p-0">
            <div class="relative transform overflow-hidden rounded-xl bg-white text-left shadow-xl transition-all sm:my-8 sm:w-full sm:max-w-sm">
                <div class="bg-white px-4 pb-4 pt-5 sm:p-6 sm:pb-4">
                    <h3 class="text-lg font-bold leading-6 text-gray-900 mb-4">No. Faktur Supplier</h3>
                    <form action="{{ route('purchase-orders.addSupplierInvoice', $purchaseOrder->po_id) }}" method="POST">
                        @csrf
                        <div>
                            <label class="block text-xs font-bold text-gray-500 uppercase mb-1">Nomor Faktur / Surat Jalan</label>
                            <input type="text" class="w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 text-sm" name="supplier_invoice_number" value="{{ $purchaseOrder->supplier_invoice_number }}" required>
                            <p class="text-xs text-gray-400 mt-1">Masukkan nomor referensi fisik dari supplier.</p>
                        </div>
                        <div class="mt-6 flex justify-end gap-3">
                            <button type="button" class="px-4 py-2 bg-white border border-gray-300 rounded-lg text-gray-700 hover:bg-gray-50 text-sm font-medium" onclick="closeModal('supplierInvoiceModal')">Batal</button>
                            <button type="submit" class="px-4 py-2 bg-indigo-600 hover:bg-indigo-700 text-white rounded-lg text-sm font-bold shadow-sm">Simpan</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/autonumeric@4.6.0/dist/autoNumeric.min.js"></script>
<script>
    // --- HELPER MODAL (VANILLA JS) ---
    function openModal(modalId) {
        document.getElementById(modalId).classList.remove('hidden');
    }
    function closeModal(modalId) {
        document.getElementById(modalId).classList.add('hidden');
    }
    function toggleDropdown(id) {
        const el = document.getElementById(id);
        if (el.classList.contains('hidden')) el.classList.remove('hidden');
        else el.classList.add('hidden');
    }
    
    // Close dropdown when clicking outside
    window.onclick = function(event) {
        if (!event.target.matches('.relative button') && !event.target.matches('.relative button *')) {
            const dropdowns = document.querySelectorAll('[id$="-dropdown"]');
            dropdowns.forEach(d => d.classList.add('hidden'));
        }
    }

    document.addEventListener('DOMContentLoaded', function() {
        
        // 1. Konfirmasi Aksi (SweetAlert)
        const confirmAction = (selector, title, text, btnColor = '#4f46e5', btnText = 'Ya, Lanjutkan') => {
            const form = document.querySelector(selector);
            if (form) {
                form.addEventListener('submit', function(e) {
                    e.preventDefault();
                    Swal.fire({
                        title: title, text: text, icon: 'question',
                        showCancelButton: true, confirmButtonColor: btnColor, cancelButtonColor: '#6b7280',
                        confirmButtonText: btnText, cancelButtonText: 'Batal'
                    }).then((result) => { if (result.isConfirmed) e.target.submit(); });
                });
            }
        };
        
        confirmAction('#receive-goods-form', 'Terima Barang?', 'Stok bertambah & jurnal dibuat.', '#10b981', 'Ya, Terima');
        confirmAction('.form-cancel-po', 'Batalkan Pesanan?', 'Pesanan dibatalkan permanen.', '#ef4444', 'Ya, Batalkan');

        document.querySelectorAll('.form-delete-po-payment').forEach(form => {
            form.addEventListener('submit', function(e) {
                e.preventDefault();
                Swal.fire({
                    title: 'Hapus Pembayaran?', text: 'Jurnal akan dibalik.', icon: 'warning',
                    showCancelButton: true, confirmButtonColor: '#ef4444', confirmButtonText: 'Hapus'
                }).then((result) => { if (result.isConfirmed) e.target.submit(); });
            });
        });

        // 2. Logic Pembayaran (AutoNumeric & Deposit)
        const amountInput = document.getElementById('amount-formatted-po');
        const amountHidden = document.getElementById('amount-po');
        const useDebit = document.getElementById('modal-use-debit');
        const payMethod = document.getElementById('payment_method_id_po');
        const bankAcc = document.getElementById('company_bank_account_id_po');
        const errorDiv = document.getElementById('amount-error-po');

        const sisaTagihan = {{ $sisaTagihanPO ?? 0 }};
        const saldoDeposit = {{ $saldoDepositSupplier ?? 0 }};

        if (amountInput) {
            const an = new AutoNumeric(amountInput, { decimalCharacter: ',', digitGroupSeparator: '.', decimalPlaces: 0, minimumValue: '0' });

            function updateFormState() {
                const isUsingDebit = useDebit ? useDebit.checked : false;
                const inputVal = parseFloat(amountHidden.value || 0);

                if (isUsingDebit && saldoDeposit >= sisaTagihan && sisaTagihan > 0) {
                    an.set(0); amountInput.disabled = true;
                    payMethod.disabled = true; payMethod.required = false; payMethod.value = "";
                    bankAcc.disabled = true; bankAcc.required = false; bankAcc.value = "";
                } else {
                    if (isUsingDebit) an.set(Math.max(0, sisaTagihan - saldoDeposit));
                    else an.set(sisaTagihan);
                    
                    amountInput.disabled = false;
                    payMethod.disabled = false; payMethod.required = true;
                    bankAcc.disabled = false; bankAcc.required = true;
                }
            }

            if (useDebit) useDebit.addEventListener('change', updateFormState);
            
            amountInput.addEventListener('autoNumeric:rawValueModified', e => {
                amountHidden.value = e.detail.newRawValue;
                const val = parseFloat(e.detail.newRawValue || 0);
                const total = (useDebit && useDebit.checked ? saldoDeposit : 0) + val;
                
                if (total > sisaTagihan) {
                    errorDiv.textContent = 'Info: Kelebihan bayar masuk deposit.';
                    errorDiv.className = 'text-green-600 text-xs mt-1';
                } else {
                    errorDiv.textContent = '';
                }
            });
        }

        // 3. Toggle Method Fields
        if (payMethod) {
            payMethod.addEventListener('change', function() {
                const config = this.options[this.selectedIndex].dataset.config;
                document.getElementById('payment-reference-group-po').style.display = (config === 'reference_only' || config === 'proof_and_reference') ? 'block' : 'none';
                document.getElementById('payment-proof-group-po').style.display = (config === 'proof_only' || config === 'proof_and_reference') ? 'block' : 'none';
            });
        }
    });
</script>
@endpush
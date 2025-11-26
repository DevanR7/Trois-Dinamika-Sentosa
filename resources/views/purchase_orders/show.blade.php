@extends('layouts.app')

{{-- LOGIC HITUNGAN --}}
@php
    $sisaUtang = $purchaseOrder->remaining_balance;
    $adjustments = $purchaseOrder->adjustments;
    $totalCreditNotesPO = $adjustments->where('type', 'credit_note')->sum('amount');
    $totalDebitNotesPO = $adjustments->where('type', 'debit_note')->sum('amount');
    $sisaTagihanPO = $sisaUtang; 
    $saldoDepositSupplier = $purchaseOrder->supplier->balance ?? 0;
@endphp

@section('title', 'Detail Pesanan')

@section('content')
<div class="max-w-7xl mx-auto pb-20 animate-enter">
    
    {{-- HEADER HALAMAN --}}
    <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center mb-8 gap-4">
        <div>
            <div class="flex items-center gap-2 text-xs font-bold text-slate-400 uppercase tracking-wider mb-1">
                <a href="{{ route('purchase-orders.index') }}" class="hover:text-indigo-600 transition">Pesanan</a>
                <i class="material-icons text-[12px]">chevron_right</i>
                <span class="text-slate-600">Detail</span>
            </div>
            <h2 class="text-2xl font-bold text-slate-800 tracking-tight flex items-center gap-3">
                <span class="font-mono text-indigo-600">{{ $purchaseOrder->po_number }}</span>
                
                {{-- Status Badge --}}
                @php
                    $statusClass = match($purchaseOrder->status) {
                        'completed' => 'bg-emerald-100 text-emerald-700 border-emerald-200',
                        'cancelled' => 'bg-red-100 text-red-700 border-red-200',
                        'draft' => 'bg-slate-100 text-slate-600 border-slate-200',
                        default => 'bg-amber-100 text-amber-700 border-amber-200',
                    };
                @endphp
                <span class="px-2.5 py-0.5 rounded text-xs font-bold border uppercase {{ $statusClass }}">
                    {{ $purchaseOrder->status }}
                </span>
            </h2>
        </div>
        
        <div class="flex flex-wrap gap-3">
            <a href="{{ route('purchase-orders.index') }}" class="px-4 py-2 bg-white border border-slate-300 rounded-lg text-slate-600 text-sm font-bold hover:bg-slate-50 transition shadow-sm">
                <i class="material-icons text-sm mr-2">arrow_back</i> Kembali
            </a>

            {{-- TOMBOL OPSI (FIXED: Model Klik) --}}
            <div class="relative" id="options-dropdown-container">
                <button id="options-toggle-btn" onclick="toggleDropdown('options-dropdown')" 
                        class="px-4 py-2 bg-white border border-slate-300 rounded-lg text-slate-700 text-sm font-bold hover:bg-slate-50 transition shadow-sm flex items-center gap-2 focus:ring-2 focus:ring-indigo-500 focus:outline-none">
                    <i class="material-icons text-sm">settings</i> Opsi <i class="material-icons text-sm">expand_more</i>
                </button>
                
                {{-- Dropdown Menu (Default hidden) --}}
                <div id="options-dropdown" 
                     class="absolute right-0 mt-1 w-56 bg-white rounded-lg shadow-xl border border-slate-200 z-50 origin-top-right hidden animate-enter">
                    <div class="py-1">
                        @if (in_array($purchaseOrder->status, ['draft', 'ordered']))
                            <a href="{{ route('purchase-orders.edit', $purchaseOrder->po_id) }}" class="block px-4 py-2 text-sm text-slate-700 hover:bg-indigo-50 hover:text-indigo-600">
                                <div class="flex items-center gap-2"><i class="material-icons text-sm">edit</i> Edit Pesanan</div>
                            </a>
                        @endif
                        
                        <a href="{{ route('purchase-order-adjustments.create') }}?purchase_order_id={{ $purchaseOrder->po_id }}" class="block px-4 py-2 text-sm text-slate-700 hover:bg-indigo-50 hover:text-indigo-600 border-t border-slate-100">
                            <div class="flex items-center gap-2"><i class="material-icons text-sm">tune</i> Buat Penyesuaian</div>
                        </a>
                        
                        <a href="{{ route('purchase-orders.pdf', $purchaseOrder->po_id) }}" class="block px-4 py-2 text-sm text-slate-700 hover:bg-indigo-50 hover:text-indigo-600">
                            <div class="flex items-center gap-2"><i class="material-icons text-sm">picture_as_pdf</i> Download PDF</div>
                        </a>

                        @can('cancel', $purchaseOrder)
                            @if(in_array($purchaseOrder->status, ['draft', 'ordered', 'completed']))
                                <div class="border-t border-slate-100 my-1"></div>
                                <form action="{{ route('purchase-orders.cancel', $purchaseOrder->po_id) }}" method="POST" class="form-confirm-danger">
                                    @csrf
                                    <button type="submit" class="w-full text-left px-4 py-2 text-sm text-red-600 hover:bg-red-50 font-bold">
                                        <div class="flex items-center gap-2"><i class="material-icons text-sm">cancel</i> Batalkan Pesanan</div>
                                    </button>
                                </form>
                            @endif
                        @endcan
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- ALERT TOAST --}}
    @if (session('success'))
        <script>window.showToast("{{ session('success') }}", 'success')</script>
    @endif
    @if (session('error'))
        <script>window.showToast("{{ session('error') }}", 'error')</script>
    @endif

    <div class="grid grid-cols-1 lg:grid-cols-12 gap-6">
        
        {{-- KOLOM KIRI: DETAIL & ITEMS (Span 8) --}}
        <div class="lg:col-span-8 space-y-6">
            
            {{-- 1. INFO SUPPLIER --}}
            <div class="dashboard-card p-0 overflow-hidden">
                <div class="px-6 py-4 border-b border-slate-100 bg-slate-50/50 flex justify-between items-center">
                    <h3 class="text-xs font-bold text-slate-500 uppercase tracking-wider flex items-center gap-2">
                        <i class="material-icons text-indigo-500 text-sm">store</i> Informasi Supplier
                    </h3>
                    <span class="text-[10px] font-bold text-slate-400 uppercase">Creator: {{ $purchaseOrder->creator->full_name ?? 'System' }}</span>
                </div>
                <div class="p-6">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div class="flex items-start gap-4">
                            <div class="w-12 h-12 rounded-lg bg-indigo-50 flex items-center justify-center text-indigo-600 border border-indigo-100">
                                <i class="material-icons text-2xl">business</i>
                            </div>
                            <div>
                                <h4 class="text-base font-bold text-slate-800">{{ $purchaseOrder->supplier->supplier_name }}</h4>
                                <p class="text-sm text-slate-500 mt-1 leading-relaxed">{{ $purchaseOrder->supplier->address ?? 'Alamat tidak tersedia' }}</p>
                                <div class="mt-2 text-xs text-slate-400 font-medium flex items-center gap-1">
                                    <i class="material-icons text-[12px]">phone</i> {{ $purchaseOrder->supplier->phone_number ?? '-' }}
                                </div>
                            </div>
                        </div>

                        <div class="space-y-3 border-l border-slate-100 pl-0 md:pl-6">
                            <div class="flex justify-between items-center">
                                <span class="text-xs font-bold text-slate-400 uppercase">Tgl Pesan</span>
                                <span class="text-sm font-bold text-slate-700">{{ optional($purchaseOrder->order_date)->format('d M Y') }}</span>
                            </div>
                            <div class="flex justify-between items-center">
                                <span class="text-xs font-bold text-slate-400 uppercase">Jatuh Tempo</span>
                                <span class="text-sm font-bold text-slate-700">{{ $purchaseOrder->due_date ? optional($purchaseOrder->due_date)->format('d M Y') : '-' }}</span>
                            </div>
                            <div class="pt-3 border-t border-dashed border-slate-200">
                                <div class="flex justify-between items-center">
                                    <span class="text-xs font-bold text-slate-400 uppercase">Faktur Supplier</span>
                                    @if($purchaseOrder->supplier_invoice_number)
                                        <span class="text-sm font-bold text-indigo-600 cursor-pointer hover:underline flex items-center gap-1" onclick="openModal('supplierInvoiceModal')">
                                            {{ $purchaseOrder->supplier_invoice_number }} <i class="material-icons text-[12px]">edit</i>
                                        </span>
                                    @else
                                        <button type="button" class="text-xs text-indigo-600 font-bold hover:underline flex items-center gap-1 uppercase" onclick="openModal('supplierInvoiceModal')">
                                            + Input Faktur
                                        </button>
                                    @endif
                                </div>
                            </div>
                        </div>
                    </div>

                    @if($purchaseOrder->notes)
                        <div class="mt-6 p-4 bg-amber-50 border border-amber-100 rounded-lg text-sm text-amber-800 flex gap-3">
                            <i class="material-icons text-amber-600">sticky_note_2</i>
                            <div><span class="font-bold">Catatan:</span> {{ $purchaseOrder->notes }}</div>
                        </div>
                    @endif
                </div>
            </div>

            {{-- 2. ITEM PRODUK --}}
            <div class="dashboard-card p-0 overflow-hidden">
                <div class="px-6 py-4 border-b border-slate-100 flex justify-between items-center">
                    <h3 class="text-xs font-bold text-slate-500 uppercase tracking-wider">Rincian Item</h3>
                    <span class="px-2 py-1 bg-slate-100 text-slate-600 text-[10px] rounded font-bold uppercase border border-slate-200">{{ $purchaseOrder->items->count() }} Item</span>
                </div>
                <div class="overflow-x-auto">
                    <table class="dashboard-table">
                        <thead class="bg-slate-50 border-b border-slate-200">
                            <tr>
                                <th class="px-6 py-3 text-xs font-bold text-slate-500 uppercase w-12 text-center">No</th>
                                <th class="px-6 py-3 text-xs font-bold text-slate-500 uppercase">Produk</th>
                                <th class="px-6 py-3 text-xs font-bold text-slate-500 uppercase text-center">Qty</th>
                                <th class="px-6 py-3 text-xs font-bold text-slate-500 uppercase text-right">Harga</th>
                                <th class="px-6 py-3 text-xs font-bold text-slate-500 uppercase text-right">Subtotal</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            @foreach($purchaseOrder->items as $item)
                            <tr class="hover:bg-slate-50 transition-colors">
                                <td class="px-6 py-4 text-center text-sm text-slate-500">{{ $loop->iteration }}</td>
                                <td class="px-6 py-4">
                                    <div class="text-sm font-bold text-slate-800">{{ $item->product->product_name ?? 'Produk Dihapus' }}</div>
                                    @if(isset($item->discounts) && $item->discounts->isNotEmpty())
                                        <div class="text-xs text-emerald-600 mt-1 flex items-center gap-1 font-medium">
                                            <i class="material-icons text-[10px]">local_offer</i> Disc: {{ $item->discounts->pluck('percentage')->join('%, ') }}%
                                        </div>
                                    @endif
                                </td>
                                <td class="px-6 py-4 text-center">
                                    <span class="px-2 py-1 rounded bg-slate-100 text-xs font-bold text-slate-700 border border-slate-200">
                                        {{ $item->quantity }} {{ $item->product->unit->name ?? '' }}
                                    </span>
                                </td>
                                <td class="px-6 py-4 text-right text-sm text-slate-600">
                                    Rp {{ number_format($item->price_per_unit, 0, ',', '.') }}
                                </td>
                                <td class="px-6 py-4 text-right text-sm font-bold text-slate-900">
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
            <div class="dashboard-card p-0 overflow-hidden">
                <div class="px-6 py-4 border-b border-slate-100 bg-red-50/50">
                    <h3 class="text-xs font-bold text-red-700 uppercase tracking-wider flex items-center gap-2">
                        <i class="material-icons text-sm">warning</i> Riwayat Koreksi
                    </h3>
                </div>
                <table class="dashboard-table">
                    <thead class="bg-white border-b border-slate-200">
                        <tr>
                            <th class="px-6 py-3 text-xs font-bold text-slate-500 uppercase w-[15%]">Tanggal</th>
                            <th class="px-6 py-3 text-xs font-bold text-slate-500 uppercase w-[15%]">Tipe</th>
                            <th class="px-6 py-3 text-xs font-bold text-slate-500 uppercase text-right w-[15%]">Nilai</th>
                            <th class="px-6 py-3 text-xs font-bold text-slate-500 uppercase w-[45%]">Alasan</th>
                            <th class="px-6 py-3 text-xs font-bold text-slate-500 uppercase text-right w-[10%]">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @foreach ($purchaseOrder->adjustments as $adjustment)
                        <tr class="hover:bg-slate-50 transition">
                            <td class="px-6 py-3 text-sm text-slate-600">{{ $adjustment->adjustment_date->format('d/m/Y') }}</td>
                            <td class="px-6 py-3">
                                @if($adjustment->type == 'credit_note')
                                    <span class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-emerald-100 text-emerald-700 border border-emerald-200 uppercase">Nota Kredit</span>
                                @else
                                    <span class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-red-100 text-red-700 border border-red-200 uppercase">Nota Debit</span>
                                @endif
                            </td>
                            <td class="px-6 py-3 text-right text-sm font-bold text-slate-800">
                                Rp {{ number_format($adjustment->amount, 0, ',', '.') }}
                            </td>
                            <td class="px-6 py-3 text-xs text-slate-500 italic">{{ Str::limit($adjustment->reason, 40) }}</td>
                            <td class="px-6 py-3 text-right">
                                <form action="{{ route('purchase-order-adjustments.destroy', $adjustment->adjustment_id) }}" method="POST" class="form-confirm-danger inline-block">
                                    @csrf @method('DELETE')
                                    <button type="submit" class="text-slate-300 hover:text-red-500 transition-colors"><i class="material-icons text-sm">delete</i></button>
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
            <div class="dashboard-card p-0 overflow-hidden">
                <div class="px-6 py-4 border-b border-slate-100 bg-blue-50/50">
                    <h3 class="text-xs font-bold text-blue-700 uppercase tracking-wider flex items-center gap-2">
                        <i class="material-icons text-sm">payments</i> Riwayat Pembayaran
                    </h3>
                </div>
                <table class="dashboard-table">
                    <thead class="bg-white border-b border-slate-200">
                        <tr>
                            <th class="px-6 py-3 text-xs font-bold text-slate-500 uppercase">Tanggal</th>
                            <th class="px-6 py-3 text-xs font-bold text-slate-500 uppercase">Metode</th>
                            <th class="px-6 py-3 text-xs font-bold text-slate-500 uppercase text-right">Jumlah</th>
                            <th class="px-6 py-3 text-xs font-bold text-slate-500 uppercase text-center">Status</th>
                            <th class="px-6 py-3 text-xs font-bold text-slate-500 uppercase text-right">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @foreach($purchaseOrder->payments as $payment)
                        <tr class="hover:bg-slate-50 transition">
                            <td class="px-6 py-3 text-sm text-slate-600">{{ $payment->payment_date->format('d/m/Y') }}</td>
                            <td class="px-6 py-3 text-sm text-slate-600">{{ $payment->paymentMethod->name ?? '-' }}</td>
                            <td class="px-6 py-3 text-right text-sm font-bold text-emerald-600">
                                Rp {{ number_format($payment->amount, 0, ',', '.') }}
                            </td>
                            <td class="px-6 py-3 text-center">
                                @if($payment->status == 'completed') <span class="text-emerald-500"><i class="material-icons text-sm">check_circle</i></span>
                                @else <span class="text-xs text-slate-400">{{ $payment->status }}</span>
                                @endif
                            </td>
                            <td class="px-6 py-3 text-right">
                                <form action="{{ route('purchase-orders.payments.destroy', $payment) }}" method="POST" class="form-confirm-danger inline-block">
                                    @csrf @method('DELETE')
                                    <button type="submit" class="text-slate-300 hover:text-red-500 transition"><i class="material-icons text-sm">delete</i></button>
                                </form>
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            @endif
        </div>

        {{-- KOLOM KANAN: RINGKASAN & AKSI (Span 4) --}}
        <div class="lg:col-span-4 space-y-6">
            
            <div class="dashboard-card p-6 sticky top-6">
                <h3 class="text-sm font-bold text-slate-800 mb-4 flex items-center gap-2 border-b border-slate-100 pb-3 uppercase tracking-wide">
                    <i class="material-icons text-indigo-500 text-sm">calculate</i> Ringkasan Biaya
                </h3>

                <div class="space-y-3 text-sm text-slate-600 mb-4 border-b border-dashed border-slate-200 pb-4">
                    <div class="flex justify-between">
                        <span>Subtotal</span>
                        <span class="font-bold text-slate-800">Rp {{ number_format($purchaseOrder->subtotal ?? 0, 0, ',', '.') }}</span>
                    </div>
                    @if($purchaseOrder->disc_fee_amount > 0)
                    <div class="flex justify-between text-red-500"><span>Diskon/Fee</span><span>(-) Rp {{ number_format($purchaseOrder->disc_fee_amount, 0, ',', '.') }}</span></div>
                    @endif
                    
                    <div class="flex justify-between text-[10px] text-slate-400 pt-1"><span>DPP</span><span>Rp {{ number_format($purchaseOrder->dpp ?? 0, 0, ',', '.') }}</span></div>
                    
                    @if($purchaseOrder->ppn > 0)
                    <div class="flex justify-between"><span>PPN ({{ $purchaseOrder->tax->rate ?? 0 }}%)</span><span>(+) Rp {{ number_format($purchaseOrder->ppn ?? 0, 0, ',', '.') }}</span></div>
                    @endif
                    
                    @if($purchaseOrder->shipping_amount > 0)
                    <div class="flex justify-between"><span>Ongkir</span><span>(+) Rp {{ number_format($purchaseOrder->shipping_amount ?? 0, 0, ',', '.') }}</span></div>
                    @endif
                </div>

                <div class="flex justify-between items-center mb-6">
                    <span class="text-sm font-bold text-slate-800 uppercase">Grand Total</span>
                    <span class="text-xl font-bold text-indigo-600">Rp {{ number_format($purchaseOrder->total_amount ?? 0, 0, ',', '.') }}</span>
                </div>

                {{-- STATUS TAGIHAN --}}
                <div class="bg-slate-50 rounded-lg p-4 border border-slate-200">
                    <h4 class="text-[10px] font-bold text-slate-400 uppercase mb-3 tracking-wider">Status Keuangan</h4>
                    
                    @if($totalDebitNotesPO > 0)
                        <div class="flex justify-between text-xs text-red-500 mb-1"><span>Nota Debit</span><span>(+) {{ number_format($totalDebitNotesPO, 0, ',', '.') }}</span></div>
                    @endif
                    @if($totalCreditNotesPO > 0)
                        <div class="flex justify-between text-xs text-emerald-500 mb-1"><span>Nota Kredit</span><span>(-) {{ number_format($totalCreditNotesPO, 0, ',', '.') }}</span></div>
                    @endif

                    <div class="flex justify-between text-xs text-emerald-600 mb-2 pb-2 border-b border-slate-200 font-bold">
                        <span>Terbayar</span>
                        <span>(-) {{ number_format($purchaseOrder->amount_paid, 0, ',', '.') }}</span>
                    </div>

                    <div class="flex justify-between items-center">
                        <span class="text-xs font-bold text-slate-700 uppercase">SISA TAGIHAN</span>
                        <span class="text-lg font-bold {{ $sisaUtang > 0.01 ? 'text-amber-600' : 'text-emerald-600' }}">
                            Rp {{ number_format($sisaUtang, 0, ',', '.') }}
                        </span>
                    </div>
                </div>

                {{-- TOMBOL AKSI --}}
                <div class="mt-6 space-y-3">
                    {{-- Terima Barang --}}
                    @can('receive', $purchaseOrder)
                        @if(in_array($purchaseOrder->status, ['draft', 'ordered']))
                            <form action="{{ route('purchase-orders.receive', $purchaseOrder->po_id) }}" method="POST" class="form-confirm-receive">
                                @csrf
                                <button type="submit" class="w-full py-3 bg-indigo-600 hover:bg-indigo-700 text-white text-sm font-bold rounded-lg shadow-md transition flex justify-center items-center gap-2">
                                    <i class="material-icons text-sm">inventory</i> Terima Barang
                                </button>
                            </form>
                        @endif
                    @endcan

                    {{-- Bayar --}}
                    @can('pay', $purchaseOrder)
                        @if($sisaUtang > 0.01 && $purchaseOrder->payment_status != 'paid')
                            <button type="button" class="w-full py-3 bg-emerald-600 hover:bg-emerald-700 text-white text-sm font-bold rounded-lg shadow-md transition flex justify-center items-center gap-2" onclick="openModal('paymentModal')">
                                <i class="material-icons text-sm">payment</i> Catat Pembayaran
                            </button>
                        @endif
                    @endcan
                </div>
            </div>
        </div>
    </div>
</div>

{{-- MODAL: PEMBAYARAN --}}
<div id="paymentModal" class="fixed inset-0 z-[100] hidden" aria-labelledby="modal-title" role="dialog" aria-modal="true">
    <div class="fixed inset-0 bg-slate-900/50 transition-opacity backdrop-blur-sm"></div>
    <div class="fixed inset-0 z-10 overflow-y-auto">
        <div class="flex min-h-full items-center justify-center p-4 text-center">
            <div class="relative transform overflow-hidden rounded-xl bg-white text-left shadow-2xl transition-all w-full max-w-lg border border-slate-200">
                
                <div class="bg-white px-6 py-4 border-b border-slate-100 flex justify-between items-center">
                    <h3 class="text-lg font-bold text-slate-800">Catat Pembayaran</h3>
                    <button type="button" class="text-slate-400 hover:text-slate-600" onclick="closeModal('paymentModal')">
                        <i class="material-icons">close</i>
                    </button>
                </div>
                
                <form action="{{ route('purchase-orders.payments.store', $purchaseOrder->po_id) }}" method="POST" enctype="multipart/form-data" id="paymentForm">
                    @csrf
                    <div class="px-6 py-6 space-y-5">
                        
                        <div class="bg-amber-50 border border-amber-100 rounded-lg p-3 text-sm flex justify-between items-center text-amber-800 font-bold">
                            <span>Sisa Utang:</span>
                            <span>Rp {{ number_format($sisaTagihanPO, 0, ',', '.') }}</span>
                        </div>

                        <div>
                            <label class="block text-[11px] font-bold text-slate-500 uppercase mb-1">Jumlah Bayar</label>
                            <div class="relative">
                                <span class="absolute left-3 top-1/2 -translate-y-1/2 text-slate-400 font-bold">Rp</span>
                                <input type="text" class="form-input pl-10 text-lg font-bold text-slate-800 input-currency" id="amount-formatted-po" required>
                                <input type="hidden" name="amount" id="amount-po">
                            </div>
                        </div>
                        
                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <label class="block text-[11px] font-bold text-slate-500 uppercase mb-1">Tanggal</label>
                                <input type="date" class="form-input" name="payment_date" value="{{ now()->format('Y-m-d') }}" required>
                            </div>
                            <div>
                                <label class="block text-[11px] font-bold text-slate-500 uppercase mb-1">Metode</label>
                                <select class="form-select" id="payment_method_id_po" name="payment_method_id" required>
                                    <option value="">-- Pilih Metode --</option>
                                    @foreach($paymentMethods as $method)
                                        <option value="{{ $method->payment_method_id }}" data-config="{{ $method->required_fields_config }}">{{ $method->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>

                        <div>
                            <label class="block text-[11px] font-bold text-slate-500 uppercase mb-1">Akun Kas/Bank</label>
                            <select class="form-select" id="company_bank_account_id_po" name="company_bank_account_id" required>
                                <option value="">-- Pilih Akun Kas/Bank --</option>
                                @foreach($companyBankAccounts as $account)
                                    <option value="{{ $account->company_bank_account_id }}">{{ $account->bank_name }} - {{ $account->account_number }}</option>
                                @endforeach
                            </select>
                        </div>

                        {{-- Reference & Proof (Hidden by JS) --}}
                        <div id="payment-reference-group-po" style="display: none;">
                            <label class="block text-[11px] font-bold text-slate-500 uppercase mb-1">No. Referensi</label>
                            <input type="text" class="form-input" name="reference_number" id="reference_number_po">
                        </div>
                        <div id="payment-proof-group-po" style="display: none;">
                            <label class="block text-[11px] font-bold text-slate-500 uppercase mb-1">Bukti Foto</label>
                            <input type="file" class="w-full text-sm text-slate-500 file:mr-4 file:py-2 file:px-4 file:rounded-md file:border-0 file:text-xs file:font-bold file:bg-indigo-50 file:text-indigo-700 hover:file:bg-indigo-100" name="proof_of_payment" id="proof_of_payment_po">
                        </div>
                        
                        <div>
                            <label class="block text-[11px] font-bold text-slate-500 uppercase mb-1">Catatan</label>
                            <textarea class="form-textarea" name="notes" rows="2"></textarea>
                        </div>
                    </div>

                    <div class="bg-slate-50 px-6 py-4 flex flex-row-reverse gap-2 border-t border-slate-100">
                        <button type="submit" class="inline-flex w-full justify-center rounded-lg bg-emerald-600 px-4 py-2 text-sm font-bold text-white shadow-sm hover:bg-emerald-700 sm:w-auto">Simpan</button>
                        <button type="button" class="inline-flex w-full justify-center rounded-lg border border-slate-300 bg-white px-4 py-2 text-sm font-bold text-slate-700 shadow-sm hover:bg-slate-50 sm:w-auto" onclick="closeModal('paymentModal')">Batal</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

{{-- MODAL: FAKTUR --}}
<div id="supplierInvoiceModal" class="fixed inset-0 z-[100] hidden" role="dialog" aria-modal="true">
    <div class="fixed inset-0 bg-slate-900/50 transition-opacity backdrop-blur-sm"></div>
    <div class="fixed inset-0 z-10 overflow-y-auto">
        <div class="flex min-h-full items-center justify-center p-4 text-center">
            <div class="relative transform overflow-hidden rounded-xl bg-white text-left shadow-2xl transition-all w-full max-w-sm border border-slate-200">
                <div class="bg-white px-6 py-6">
                    <h3 class="text-lg font-bold text-slate-800 mb-4">Update Faktur Supplier</h3>
                    <form action="{{ route('purchase-orders.addSupplierInvoice', $purchaseOrder->po_id) }}" method="POST">
                        @csrf
                        <div>
                            <label class="block text-[11px] font-bold text-slate-500 uppercase mb-1">Nomor Faktur / Surat Jalan</label>
                            <input type="text" class="form-input font-bold" name="supplier_invoice_number" value="{{ $purchaseOrder->supplier_invoice_number }}" required placeholder="Contoh: INV-SUP-001">
                        </div>
                        <div class="mt-6 flex justify-end gap-2">
                            <button type="button" class="px-4 py-2 bg-white border border-slate-300 rounded-lg text-slate-700 hover:bg-slate-50 text-sm font-bold" onclick="closeModal('supplierInvoiceModal')">Batal</button>
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
    // --- HELPER MODAL & DROPDOWN ---
    function openModal(id) { document.getElementById(id).classList.remove('hidden'); }
    function closeModal(id) { document.getElementById(id).classList.add('hidden'); }

    function toggleDropdown(dropdownId) {
        const dropdown = document.getElementById(dropdownId);
        const isOpen = !dropdown.classList.contains('hidden');

        // Close all other dropdowns
        document.querySelectorAll('[id$="-dropdown"]').forEach(el => {
            if (el.id !== dropdownId) {
                el.classList.add('hidden');
            }
        });

        // Toggle current dropdown
        if (!isOpen) {
            dropdown.classList.remove('hidden');
        } else {
            dropdown.classList.add('hidden');
        }
    }

    document.addEventListener('DOMContentLoaded', function() {
        
        // Listener untuk menutup dropdown saat klik di luar
        window.addEventListener('click', function(event) {
            const dropdown = document.getElementById('options-dropdown');
            const toggleButton = document.getElementById('options-toggle-btn');
            
            // Cek apakah klik terjadi di luar dropdown DAN bukan pada tombol pemicu
            if (dropdown && !dropdown.classList.contains('hidden')) {
                if (!dropdown.contains(event.target) && !toggleButton.contains(event.target)) {
                    dropdown.classList.add('hidden');
                }
            }
        });
        
        // 1. SweetAlert Confirmation Wrappers
        const forms = [
            { cls: '.form-confirm-danger', title: 'Yakin?', text: 'Tindakan ini tidak dapat dibatalkan!', color: '#ef4444' },
            { cls: '.form-confirm-receive', title: 'Terima Barang?', text: 'Stok bertambah & jurnal dibuat.', color: '#10b981' }
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

        // 2. Logic Pembayaran (AutoNumeric & Method Toggle)
        const amountInput = document.getElementById('amount-formatted-po');
        const amountHidden = document.getElementById('amount-po');
        const payMethod = document.getElementById('payment_method_id_po');
        const sisaTagihan = {{ $sisaTagihanPO ?? 0 }};

        if (amountInput) {
            const an = new AutoNumeric(amountInput, { 
                decimalCharacter: ',', 
                digitGroupSeparator: '.', 
                decimalPlaces: 0, 
                minimumValue: '0',
                emptyInputBehavior: 'focus'
            });

            // Set default value to max sisa tagihan on modal open
            document.getElementById('paymentModal').addEventListener('click', function(e) {
                if (e.target.closest('.relative.transform')) { // Jika klik di dalam modal
                    an.set(sisaTagihan);
                }
            });

            amountInput.addEventListener('autoNumeric:rawValueModified', e => {
                amountHidden.value = e.detail.newRawValue;
            });
        }

        // Toggle Method Fields
        if (payMethod) {
            payMethod.addEventListener('change', function() {
                const config = this.options[this.selectedIndex].dataset.config;
                document.getElementById('payment-reference-group-po').style.display = (config === 'reference_only' || config === 'proof_and_reference') ? 'block' : 'none';
                document.getElementById('payment-proof-group-po').style.display = (config === 'proof_only' || config === 'proof_and_reference') ? 'block' : 'none';
            });
        }

        // Init AutoNumeric on other inputs for consistency
        const autoNumericOptions = { decimalCharacter: ',', digitGroupSeparator: '.', decimalPlaces: 0, minimumValue: '0' };
        if(document.getElementById('disc_fee_amount')) new AutoNumeric(document.getElementById('disc_fee_amount'), autoNumericOptions);
        if(document.getElementById('rounding_discount_amount')) new AutoNumeric(document.getElementById('rounding_discount_amount'), autoNumericOptions);
        if(document.getElementById('shipping_amount')) new AutoNumeric(document.getElementById('shipping_amount'), autoNumericOptions);
    });
</script>
@endpush
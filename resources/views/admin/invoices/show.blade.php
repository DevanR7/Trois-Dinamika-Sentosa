@extends('admin.layouts.app')

@section('title', 'Detail Invoice ' . $invoice->invoice_number)

@push('styles')
    <style>
        #overpayment-alert { transition: all 0.3s ease-in-out; }
        /* Animasi slide untuk field dinamis */
        .dynamic-field { transition: all 0.3s ease-in-out; overflow: hidden; }
        .dynamic-field.hidden { display: none; opacity: 0; height: 0; }
        .dynamic-field.visible { display: block; opacity: 1; height: auto; }
        /* Hover effect untuk baris tabel */
        tr.hover-row:hover td { background-color: #f8fafc; }
    </style>
@endpush

@php
    $sisaTagihan = $invoice->remaining_balance;
    $totalRetur = $invoice->returns->sum('total_amount');
    
    $statusClass = match($invoice->status) {
        'paid' => 'bg-emerald-100 text-emerald-700 border-emerald-200',
        'partially_paid' => 'bg-cyan-100 text-cyan-700 border-cyan-200',
        'cancelled' => 'bg-red-100 text-red-700 border-red-200',
        'draft' => 'bg-slate-100 text-slate-600 border-slate-200',
        'unpaid' => 'bg-amber-100 text-amber-700 border-amber-200',
        default => 'bg-slate-100 text-slate-600 border-slate-200'
    };
    
    if(optional($invoice->due_date)->isPast() && !in_array($invoice->status, ['paid', 'cancelled', 'draft'])) {
        $statusClass = 'bg-rose-100 text-rose-700 border-rose-200'; 
        $isOverdue = true;
    } else {
        $isOverdue = false;
    }
@endphp

@section('content')
<div class="max-w-7xl mx-auto pb-20 animate-enter">
    
    {{-- HEADER SECTION --}}
    <div class="flex flex-col md:flex-row justify-between items-start md:items-center mb-8 gap-4">
        <div>
            <div class="flex items-center gap-2 text-xs font-bold text-slate-400 uppercase tracking-wider mb-1">
                <a href="{{ route('admin.invoices.index') }}" class="hover:text-indigo-600 transition">Invoice</a>
                <i class="material-icons text-[12px]">chevron_right</i>
                <span class="text-slate-600">Detail</span>
            </div>
            <h1 class="text-3xl font-bold text-slate-800 tracking-tight flex items-center gap-3">
                {{ $invoice->invoice_number }}
                <span class="px-3 py-1 rounded-full text-xs font-bold border uppercase tracking-wide {{ $statusClass }}">
                    {{ str_replace('_', ' ', $invoice->status) }}
                </span>
                @if($isOverdue)
                    <span class="px-2 py-1 rounded-full bg-red-50 text-red-600 text-[10px] font-bold border border-red-100">TERLAMBAT</span>
                @endif
            </h1>
        </div>
        
        <div class="flex flex-wrap gap-2">
            <a href="{{ route('admin.invoices.index') }}" class="px-4 py-2 bg-white border border-slate-300 rounded-lg text-slate-600 text-sm font-bold hover:bg-slate-50 transition shadow-sm">
                Kembali
            </a>
            
            <a href="{{ route('admin.invoices.pdf', $invoice->invoice_id) }}" target="_blank" class="px-4 py-2 bg-white border border-slate-300 rounded-lg text-slate-700 text-sm font-bold hover:bg-slate-50 transition shadow-sm flex items-center gap-2">
                <i class="material-icons text-sm">print</i> Cetak PDF
            </a>

            {{-- DROPDOWN TINDAKAN --}}
            <div class="relative" x-data="{ open: false }">
                <button @click="open = !open" @click.outside="open = false" class="px-5 py-2 bg-indigo-600 text-white border border-indigo-600 rounded-lg text-sm font-bold hover:bg-indigo-700 transition shadow-md flex items-center gap-2">
                    <i class="material-icons text-sm">settings</i> Tindakan <i class="material-icons text-sm">expand_more</i>
                </button>
                <div x-show="open" class="absolute right-0 mt-2 w-56 bg-white rounded-xl shadow-xl border border-slate-100 z-50 py-2 hidden" :class="{'hidden': !open}">
                    
                    @if($invoice->status == 'draft')
                        <form action="{{ route('admin.invoices.confirm', $invoice->invoice_id) }}" method="POST" class="block w-full form-confirm">
                            @csrf
                            <button type="submit" class="w-full text-left px-4 py-2.5 text-sm text-slate-700 hover:bg-emerald-50 hover:text-emerald-600 font-bold flex items-center gap-2">
                                <i class="material-icons text-sm">check_circle</i> Konfirmasi Invoice
                            </button>
                        </form>
                        <div class="border-t border-slate-50 my-1"></div>
                    @endif

                    @if(!in_array($invoice->status, ['paid', 'cancelled']))
                        <a href="{{ route('admin.invoices.edit', $invoice->invoice_id) }}" class="block px-4 py-2.5 text-sm text-slate-700 hover:bg-slate-50 hover:text-indigo-600 flex items-center gap-2">
                            <i class="material-icons text-sm">edit</i> Edit Invoice
                        </a>
                    @endif
                    
                    <a href="{{ route('admin.invoice-adjustments.create', ['invoice_id' => $invoice->invoice_id]) }}" class="block px-4 py-2.5 text-sm text-slate-700 hover:bg-slate-50 hover:text-indigo-600 flex items-center gap-2">
                        <i class="material-icons text-sm">tune</i> Koreksi / Retur
                    </a>

                    @if(!in_array($invoice->status, ['draft', 'paid', 'cancelled']))
                        <div class="border-t border-slate-50 my-1"></div>
                        <form action="{{ route('admin.invoices.cancel', $invoice->invoice_id) }}" method="POST" class="block w-full form-confirm-danger">
                            @csrf
                            <button type="submit" class="w-full text-left px-4 py-2.5 text-sm text-red-600 hover:bg-red-50 flex items-center gap-2">
                                <i class="material-icons text-sm">cancel</i> Batalkan Invoice
                            </button>
                        </form>
                    @endif
                </div>
            </div>
        </div>
    </div>

    {{-- LAYOUT GRID UTAMA --}}
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-8 items-start">

        {{-- KOLOM KIRI (2/3) --}}
        <div class="lg:col-span-2 space-y-8">

            {{-- 1. INFORMASI KLIEN & TANGGAL --}}
            <div class="bg-white rounded-xl border border-slate-200 shadow-sm overflow-hidden">
                <div class="p-6 grid grid-cols-1 md:grid-cols-2 gap-8">
                    {{-- Klien --}}
                    <div class="flex gap-4">
                        <div class="w-12 h-12 rounded-full bg-slate-50 flex items-center justify-center text-slate-400 border border-slate-100 flex-shrink-0">
                            <i class="material-icons text-2xl">business</i>
                        </div>
                        <div>
                            <h4 class="text-xs font-bold text-slate-400 uppercase mb-1 tracking-wider">Informasi Klien</h4>
                            <p class="text-base font-bold text-slate-800">{{ $invoice->client->client_name }}</p>
                            <p class="text-sm text-slate-500 mt-1 leading-relaxed">{{ $invoice->client->address ?? 'Alamat tidak tersedia' }}</p>
                            <p class="text-sm text-slate-500 mt-1"><i class="material-icons text-[14px] align-middle mr-1">phone</i> {{ $invoice->client->phone_number ?? '-' }}</p>
                        </div>
                    </div>
                    {{-- Detail Invoice --}}
                    <div class="flex gap-4 border-t md:border-t-0 md:border-l border-slate-100 md:pl-8 pt-4 md:pt-0">
                        <div class="w-full space-y-3">
                            <div class="flex justify-between">
                                <span class="text-sm text-slate-500">Tanggal Invoice</span>
                                <span class="text-sm font-bold text-slate-700">{{ $invoice->order_date->format('d M Y') }}</span>
                            </div>
                            <div class="flex justify-between">
                                <span class="text-sm text-slate-500">Jatuh Tempo</span>
                                <span class="text-sm font-bold {{ $isOverdue ? 'text-red-600' : 'text-slate-700' }}">
                                    {{ $invoice->due_date->format('d M Y') }}
                                </span>
                            </div>
                            <div class="flex justify-between">
                                <span class="text-sm text-slate-500">Sales Person</span>
                                <span class="text-sm font-bold text-slate-700">{{ $invoice->sales->full_name ?? '-' }}</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            {{-- 2. TABEL PRODUK --}}
            <div class="bg-white rounded-xl border border-slate-200 shadow-sm overflow-hidden">
                <div class="px-6 py-4 border-b border-slate-100 bg-slate-50/50 flex justify-between items-center">
                    <h3 class="text-sm font-bold text-slate-700 uppercase tracking-wider flex items-center gap-2">
                        <i class="material-icons text-indigo-500 text-base">shopping_bag</i> Rincian Produk
                    </h3>
                    <span class="text-xs font-bold text-slate-500 bg-white px-2 py-1 rounded border border-slate-200">{{ $invoice->items->count() }} Item</span>
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
                            @foreach($invoice->items as $item)
                            <tr class="hover:bg-slate-50 transition-colors">
                                <td class="px-6 py-4 text-center text-slate-400">{{ $loop->iteration }}</td>
                                <td class="px-6 py-4">
                                    <div class="font-bold text-slate-800">{{ $item->product->product_name ?? 'Produk Dihapus' }}</div>
                                    <div class="text-xs text-slate-400 font-mono mt-0.5">{{ $item->product->product_code ?? '' }}</div>
                                </td>
                                <td class="px-6 py-4 text-center">
                                    <span class="bg-slate-100 text-slate-600 px-2 py-1 rounded text-xs font-bold">
                                        {{ (float)$item->quantity }} {{ $item->product->unit->name ?? '' }}
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

            {{-- 3. RIWAYAT PEMBAYARAN (FIXED: View Proof) --}}
            @if($invoice->payments->isNotEmpty())
            <div class="bg-white rounded-xl border border-slate-200 shadow-sm overflow-hidden">
                <div class="px-6 py-4 border-b border-slate-100 bg-blue-50/30 flex items-center gap-2">
                    <i class="material-icons text-blue-600 text-base">payments</i>
                    <h3 class="text-sm font-bold text-blue-800 uppercase tracking-wider">Riwayat Pembayaran</h3>
                </div>
                <table class="w-full text-sm text-left">
                    <tbody class="divide-y divide-slate-100">
                        @foreach($invoice->payments as $payment)
                        <tr class="hover:bg-slate-50 transition">
                            <td class="px-6 py-4 text-slate-600">{{ $payment->payment_date->format('d/m/Y') }}</td>
                            <td class="px-6 py-4">
                                <div class="font-bold text-slate-700">{{ $payment->paymentMethod->name ?? 'Metode Lain' }}</div>
                                <div class="text-xs text-slate-400">{{ $payment->companyBankAccount->bank_name ?? '' }}</div>
                                
                                {{-- Tampilkan Link Bukti Jika Ada --}}
                                @if($payment->proof_of_payment_path)
                                    <div class="mt-1">
                                        <a href="{{ asset('storage/' . $payment->proof_of_payment_path) }}" target="_blank" class="inline-flex items-center text-[11px] font-bold text-indigo-500 hover:text-indigo-700 hover:underline gap-1">
                                            <i class="material-icons text-[12px]">attach_file</i> Lihat Bukti
                                        </a>
                                    </div>
                                @endif
                            </td>
                            <td class="px-6 py-4 text-right font-bold text-emerald-600 font-mono">
                                + Rp {{ number_format($payment->amount, 0, ',', '.') }}
                            </td>
                            <td class="px-6 py-4 text-center w-12">
                                @if($payment->status == 'completed')
                                    <span class="text-emerald-500" title="Diterima"><i class="material-icons text-base">check_circle</i></span>
                                @else
                                    <span class="text-amber-500" title="Menunggu Verifikasi"><i class="material-icons text-base">hourglass_top</i></span>
                                @endif
                            </td>
                            <td class="px-6 py-4 text-right w-12">
                                <form action="{{ route('admin.payments.destroy', $payment->payment_id) }}" method="POST" class="form-confirm-danger">
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

            {{-- 4. RIWAYAT KOREKSI --}}
            @if($invoice->adjustments->isNotEmpty() || $invoice->returns->isNotEmpty())
            <div class="bg-white rounded-xl border border-slate-200 shadow-sm overflow-hidden">
                <div class="px-6 py-4 border-b border-slate-100 bg-amber-50/30 flex items-center gap-2">
                    <i class="material-icons text-amber-600 text-base">history</i>
                    <h3 class="text-sm font-bold text-amber-800 uppercase tracking-wider">Riwayat Koreksi & Retur</h3>
                </div>
                <table class="w-full text-sm text-left">
                    <tbody class="divide-y divide-slate-100">
                        {{-- Adjustments --}}
                        @foreach($invoice->adjustments as $adj)
                        <tr class="hover:bg-slate-50">
                            <td class="px-6 py-4">
                                <span class="font-bold text-slate-700">{{ $adj->type == 'credit_note' ? 'Nota Kredit (Potongan)' : 'Nota Debet (Tambahan)' }}</span>
                                <div class="text-xs text-slate-500 mt-1 italic">{{ Str::limit($adj->reason, 60) }}</div>
                            </td>
                            <td class="px-6 py-4 text-slate-400 text-xs text-right">{{ $adj->adjustment_date->format('d/m/Y') }}</td>
                            <td class="px-6 py-4 text-right font-bold font-mono {{ $adj->type == 'credit_note' ? 'text-red-600' : 'text-emerald-600' }}">
                                {{ $adj->type == 'credit_note' ? '-' : '+' }} Rp {{ number_format($adj->amount, 0, ',', '.') }}
                            </td>
                            <td class="px-6 py-4 text-right w-12">
                                <form action="{{ route('admin.invoice-adjustments.destroy', $adj->adjustment_id) }}" method="POST" class="form-confirm-danger">
                                    @csrf @method('DELETE')
                                    <button type="submit" class="text-slate-300 hover:text-red-500 transition"><i class="material-icons text-lg">delete</i></button>
                                </form>
                            </td>
                        </tr>
                        @endforeach

                        {{-- Returns --}}
                        @foreach($invoice->returns as $retur)
                        <tr class="hover:bg-slate-50">
                            <td class="px-6 py-4 text-xs">
                                <span class="font-bold text-slate-700">Retur Barang</span><br>
                                <span class="text-slate-400">{{ $retur->return_date->format('d/m/Y') }}</span>
                            </td>
                            <td class="px-6 py-4 text-right font-bold text-red-600">
                                Rp {{ number_format($retur->total_amount, 0, ',', '.') }}
                            </td>
                            <td class="px-6 py-4 text-center w-10">
                                <a href="{{ route('admin.sales-returns.show', $retur->return_id) }}" class="text-slate-300 hover:text-indigo-500"><i class="material-icons text-sm">visibility</i></a>
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            @endif

            {{-- 5. CATATAN --}}
            @if($invoice->notes)
            <div class="bg-yellow-50/50 rounded-xl border border-yellow-100 p-6">
                <h4 class="text-xs font-bold text-yellow-700 uppercase mb-2">Catatan Invoice</h4>
                <p class="text-sm text-slate-600 italic leading-relaxed">"{{ $invoice->notes }}"</p>
            </div>
            @endif

        </div>

        {{-- KOLOM KANAN (1/3) --}}
        <div class="lg:col-span-1">
            <div class="sticky top-6 space-y-6">
                
                {{-- CARD RINGKASAN BIAYA --}}
                <div class="bg-white rounded-xl border border-slate-200 shadow-lg overflow-hidden">
                    <div class="bg-slate-800 px-6 py-4">
                        <h3 class="text-sm font-bold text-white uppercase tracking-wider">Ringkasan Biaya</h3>
                    </div>
                    <div class="p-6 space-y-4">
                        <div class="flex justify-between text-sm text-slate-600">
                            <span>Subtotal</span>
                            <span class="font-bold text-slate-800">Rp {{ number_format($invoice->subtotal, 0, ',', '.') }}</span>
                        </div>

                        @if($invoice->discount_amount > 0)
                        <div class="flex justify-between text-sm text-red-500">
                            <span>Diskon ({{ (float)$invoice->discount_percentage }}%)</span>
                            <span>- Rp {{ number_format($invoice->discount_amount, 0, ',', '.') }}</span>
                        </div>
                        @endif

                        @foreach($invoice->taxes as $tax)
                        <div class="flex justify-between text-sm text-slate-600">
                            <span>{{ $tax->pivot->name }} ({{ (float)$tax->pivot->rate }}%)</span>
                            <span>+ Rp {{ number_format($tax->pivot->amount, 0, ',', '.') }}</span>
                        </div>
                        @endforeach

                        @php $totalAdditional = $invoice->additionalCosts->sum('amount'); @endphp
                        @if($totalAdditional > 0)
                        <div class="flex justify-between text-sm text-slate-600">
                            <span>Biaya Tambahan</span>
                            <span>+ Rp {{ number_format($totalAdditional, 0, ',', '.') }}</span>
                        </div>
                        @endif

                        <div class="border-t border-slate-200 my-2"></div>

                        <div class="flex justify-between items-end">
                            <span class="text-sm font-bold text-slate-800 uppercase">Grand Total</span>
                            <span class="text-2xl font-bold text-indigo-600 font-mono tracking-tight">
                                Rp {{ number_format($invoice->total_amount, 0, ',', '.') }}
                            </span>
                        </div>

                        {{-- PEMBAYARAN & SISA --}}
                        <div class="bg-slate-50 rounded-lg p-4 border border-slate-100 space-y-2 mt-4">
                            @if($invoice->amount_paid > 0)
                            <div class="flex justify-between text-xs text-emerald-600 font-bold">
                                <span>Sudah Dibayar</span>
                                <span>- Rp {{ number_format($invoice->amount_paid, 0, ',', '.') }}</span>
                            </div>
                            @endif
                            
                            @if($totalRetur > 0)
                            <div class="flex justify-between text-xs text-amber-600 font-bold">
                                <span>Retur</span>
                                <span>- Rp {{ number_format($totalRetur, 0, ',', '.') }}</span>
                            </div>
                            @endif

                            @php 
                                $totalManualAdj = $invoice->adjustments->reduce(fn($c, $a) => $c + ($a->type == 'debit_note' ? $a->amount : -$a->amount), 0);
                            @endphp
                            @if($totalManualAdj != 0)
                            <div class="flex justify-between text-xs {{ $totalManualAdj > 0 ? 'text-red-600' : 'text-emerald-600' }} font-bold">
                                <span>Koreksi Manual</span>
                                <span>{{ $totalManualAdj > 0 ? '+' : '' }} Rp {{ number_format($totalManualAdj, 0, ',', '.') }}</span>
                            </div>
                            @endif
                            
                            <div class="border-t border-slate-200 my-2"></div>
                            
                            <div class="flex justify-between items-center">
                                <span class="text-xs font-bold text-slate-500 uppercase">Sisa Tagihan</span>
                                <span class="text-lg font-bold font-mono {{ $sisaTagihan > 0.01 ? 'text-red-600' : 'text-emerald-600' }}">
                                    Rp {{ number_format($sisaTagihan, 0, ',', '.') }}
                                </span>
                            </div>
                        </div>

                        {{-- TOMBOL BAYAR --}}
                        @if($sisaTagihan > 0.01 && !in_array($invoice->status, ['cancelled', 'draft']))
                            <button type="button" onclick="openModal('paymentModal')" class="w-full py-3 bg-emerald-600 hover:bg-emerald-700 text-white text-sm font-bold rounded-lg shadow-lg hover:shadow-emerald-500/30 transition transform hover:-translate-y-0.5 flex justify-center items-center gap-2">
                                <i class="material-icons text-lg">payments</i> Catat Pembayaran
                            </button>
                        @elseif($sisaTagihan <= 0.01)
                            <div class="w-full py-3 bg-emerald-50 text-emerald-700 text-sm font-bold rounded-lg border border-emerald-100 flex justify-center items-center gap-2">
                                <i class="material-icons">check_circle</i> LUNAS
                            </div>
                        @endif

                    </div>
                </div>

            </div>
        </div>
    </div>

    {{-- MODAL PEMBAYARAN --}}
    <div id="paymentModal" class="fixed inset-0 z-[100] hidden" role="dialog">
        <div class="fixed inset-0 bg-slate-900/60 backdrop-blur-sm transition-opacity" onclick="closeModal('paymentModal')"></div>
        
        <div class="fixed inset-0 z-10 flex items-center justify-center p-4">
            <div class="bg-white rounded-2xl shadow-2xl w-full max-w-md overflow-hidden animate-enter transform transition-all scale-100">
                
                <div class="bg-white px-6 py-4 border-b border-slate-100 flex justify-between items-center">
                    <h3 class="text-lg font-bold text-slate-800">Catat Pembayaran</h3>
                    <button type="button" class="text-slate-400 hover:text-slate-600 transition p-1 rounded-full hover:bg-slate-50" onclick="closeModal('paymentModal')">
                        <i class="material-icons">close</i>
                    </button>
                </div>

                {{-- Form --}}
                <form action="{{ route('admin.payments.store', $invoice->invoice_id) }}" method="POST" enctype="multipart/form-data" onsubmit="prepareSubmit()">
                    @csrf
                    <div class="p-6 space-y-5">
                        
                        {{-- Sisa Tagihan Alert --}}
                        <div class="bg-indigo-50 border border-indigo-100 rounded-xl p-4 text-center">
                            <span class="text-xs font-bold text-indigo-400 uppercase tracking-widest">Sisa Tagihan Saat Ini</span>
                            <div class="text-2xl font-bold text-indigo-700 font-mono mt-1">
                                Rp {{ number_format($sisaTagihan, 0, ',', '.') }}
                            </div>
                        </div>

                        {{-- Input Jumlah Bayar --}}
                        <div>
                            <label class="block text-xs font-bold text-slate-500 uppercase mb-1.5">Jumlah Bayar (Rp)</label>
                            <div class="relative">
                                <input type="text" class="form-input text-xl font-bold text-slate-800 pl-4 h-12" id="amount-formatted" required placeholder="0">
                                <input type="hidden" name="amount" id="amount">
                            </div>
                            
                            {{-- Alert Kelebihan Bayar --}}
                            <div id="overpayment-alert" class="hidden mt-2 bg-amber-50 border-l-4 border-amber-400 p-3 rounded-r text-sm text-amber-700">
                                <p class="font-bold text-xs uppercase mb-1 flex items-center gap-1">
                                    <i class="material-icons text-sm">warning</i> Kelebihan Bayar Deteksi
                                </p>
                                <p>Kelebihan sebesar <span id="over-amount" class="font-mono font-bold">Rp 0</span> akan otomatis disimpan sebagai <b>Deposit Kredit</b> untuk klien ini.</p>
                            </div>
                        </div>

                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <label class="block text-xs font-bold text-slate-500 uppercase mb-1.5">Tanggal Bayar</label>
                                <input type="date" name="payment_date" class="form-input" value="{{ now()->format('Y-m-d') }}" required>
                            </div>
                            <div>
                                <label class="block text-xs font-bold text-slate-500 uppercase mb-1.5">Metode</label>
                                <select name="payment_method_id" id="payment_method_id" class="form-input text-sm" required>
                                    @foreach($paymentMethods as $method)
                                        <option value="{{ $method->payment_method_id }}" data-config="{{ $method->required_fields_config }}">
                                            {{ $method->name }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                        </div>

                        <div>
                             <label class="block text-xs font-bold text-slate-500 uppercase mb-1.5">Masuk ke Akun (Kas/Bank)</label>
                             <select name="company_bank_account_id" class="form-input w-full text-sm" required>
                                 @foreach($companyBankAccounts as $acc)
                                    <option value="{{ $acc->company_bank_account_id }}">{{ $acc->bank_name }} - {{ $acc->account_number }}</option>
                                 @endforeach
                             </select>
                        </div>
                        
                        {{-- DYNAMIC FIELDS: REF NUMBER & PROOF --}}
                        <div id="reference-container" class="dynamic-field hidden">
                            <label class="block text-xs font-bold text-slate-500 uppercase mb-1.5">Nomor Referensi <span class="text-red-500">*</span></label>
                            <input type="text" name="reference_number" id="reference_number" class="form-input text-sm" placeholder="No. Transaksi / Cek / Giro">
                        </div>

                        <div id="proof-container" class="dynamic-field hidden">
                            <label class="block text-xs font-bold text-slate-500 uppercase mb-1.5">Bukti Pembayaran <span class="text-red-500">*</span></label>
                            <input type="file" name="proof_of_payment" id="proof_of_payment" class="block w-full text-xs text-slate-500 file:mr-4 file:py-2 file:px-4 file:rounded-full file:border-0 file:text-xs file:font-bold file:bg-indigo-50 file:text-indigo-700 hover:file:bg-indigo-100">
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

</div>
@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/autonumeric@4.6.0/dist/autoNumeric.min.js"></script>
<script>
    // --- MODAL LOGIC ---
    function openModal(id) { 
        document.getElementById(id).classList.remove('hidden'); 
        // AutoNumeric butuh waktu sejenak agar bisa fokus
        setTimeout(() => document.getElementById('amount-formatted').focus(), 100);
    }
    function closeModal(id) { document.getElementById(id).classList.add('hidden'); }
    
    // --- PREPARE SUBMIT (Safe guard) ---
    function prepareSubmit() {
        const formattedInput = document.getElementById('amount-formatted');
        // Memicu event change manual agar AutoNumeric mengupdate hidden input
        formattedInput.dispatchEvent(new Event('change'));
    }

    document.addEventListener('DOMContentLoaded', function () {
        // --- 1. HANDLING PAYMENT METHOD CHANGE (DYNAMIC FIELDS) ---
        const paymentMethodSelect = document.getElementById('payment_method_id');
        const referenceContainer = document.getElementById('reference-container');
        const proofContainer = document.getElementById('proof-container');
        const referenceInput = document.getElementById('reference_number');
        const proofInput = document.getElementById('proof_of_payment');

        function updateDynamicFields() {
            const selectedOption = paymentMethodSelect.options[paymentMethodSelect.selectedIndex];
            const config = selectedOption.getAttribute('data-config') || 'none';

            // Reset visibility & requirement
            referenceContainer.classList.add('hidden');
            proofContainer.classList.add('hidden');
            referenceInput.required = false;
            proofInput.required = false;

            if (config === 'reference_only' || config === 'proof_and_reference') {
                referenceContainer.classList.remove('hidden');
                referenceInput.required = true;
            }
            
            if (config === 'proof_only' || config === 'proof_and_reference') {
                proofContainer.classList.remove('hidden');
                proofInput.required = true;
            }
        }

        if(paymentMethodSelect) {
            paymentMethodSelect.addEventListener('change', updateDynamicFields);
            // Trigger saat load pertama kali
            updateDynamicFields();
        }

        // --- 2. AUTONUMERIC & OVERPAYMENT LOGIC ---
        if(typeof AutoNumeric !== 'undefined') {
            const amountInput = document.getElementById('amount-formatted');
            const amountHidden = document.getElementById('amount');
            const alertBox = document.getElementById('overpayment-alert');
            const overAmountText = document.getElementById('over-amount');
            
            const sisaTagihan = {{ (float)$sisaTagihan }};
            
            if(amountInput) {
                // Init Manual (Tanpa global class .input-currency)
                const an = new AutoNumeric(amountInput, { 
                    currencySymbol: 'Rp ', 
                    currencySymbolPlacement: 'p', 
                    decimalCharacter: ',', 
                    digitGroupSeparator: '.', 
                    decimalPlaces: 0, 
                    minimumValue: '0',
                    emptyInputBehavior: 'zero',
                    unformatOnSubmit: true // Kirim angka murni
                });

                // Update hidden input & Cek Kelebihan
                amountInput.addEventListener('autoNumeric:rawValueModified', e => {
                    const val = e.detail.newRawValue;
                    amountHidden.value = val;

                    // Hitung selisih
                    if (val > sisaTagihan) {
                        const diff = val - sisaTagihan;
                        const diffFormatted = new Intl.NumberFormat('id-ID', { style: 'currency', currency: 'IDR', minimumFractionDigits: 0 }).format(diff);
                        overAmountText.textContent = diffFormatted;
                        alertBox.classList.remove('hidden');
                    } else {
                        alertBox.classList.add('hidden');
                    }
                });

                // Set initial value saat modal dibuka
                document.getElementById('paymentModal')?.addEventListener('click', (e) => {
                    if (an.getRawValue() == 0) {
                        an.set(sisaTagihan); // Auto-fill sisa tagihan
                        amountHidden.value = sisaTagihan;
                    }
                });
            }
        }
        
        // --- 3. SWEETALERT CONFIRM ---
        const confirmForms = document.querySelectorAll('.form-confirm, .form-confirm-danger');
        confirmForms.forEach(form => {
            form.addEventListener('submit', function(e) {
                e.preventDefault();
                const isDanger = this.classList.contains('form-confirm-danger');
                Swal.fire({
                    title: isDanger ? 'Batalkan?' : 'Konfirmasi?',
                    text: isDanger ? "Aksi ini tidak dapat dikembalikan." : "Lanjutkan proses ini?",
                    icon: isDanger ? 'warning' : 'question',
                    showCancelButton: true,
                    confirmButtonColor: isDanger ? '#ef4444' : '#10b981',
                    confirmButtonText: 'Ya, Lanjutkan'
                }).then((result) => { if (result.isConfirmed) this.submit(); });
            });
        });
    });
</script>
@endpush
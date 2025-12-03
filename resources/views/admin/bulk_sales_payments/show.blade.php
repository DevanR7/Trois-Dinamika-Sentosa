@extends('admin.layouts.app')

@section('title', 'Detail Riwayat Pembayaran')

@section('content')
<div class="max-w-7xl mx-auto pb-20 animate-enter">
    
    {{-- HEADER --}}
    <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center mb-8 gap-4">
        <div>
            <nav class="flex text-sm text-slate-500 mb-1">
                <a href="{{ route('admin.bulk-sales-payments.index') }}" class="hover:text-indigo-600 transition-colors">Riwayat</a>
                <span class="mx-2 text-slate-300">/</span>
                <span class="text-slate-800 font-semibold">Detail Transaksi</span>
            </nav>
            <h1 class="text-2xl font-bold text-slate-800 tracking-tight">Detail Bulk Payment #{{ $bulkSalesPayment->bulk_sales_payment_id }}</h1>
        </div>
        <a href="{{ route('admin.bulk-sales-payments.index') }}" class="h-[48px] px-6 bg-white border border-slate-300 rounded-lg font-bold text-sm text-slate-700 hover:bg-slate-50 transition-all flex items-center justify-center gap-2 shadow-sm">
            <i class="material-icons text-[18px]">arrow_back</i> Kembali
        </a>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
        
        {{-- KOLOM KIRI: INFO UTAMA & INVOICE --}}
        <div class="lg:col-span-2 space-y-6">
            
            {{-- INFO PEMBAYARAN --}}
            <div class="dashboard-card p-0 overflow-hidden shadow-sm">
                <div class="px-6 py-4 border-b border-slate-100 bg-slate-50/50 flex items-center gap-3">
                    <div class="p-2 bg-indigo-50 rounded-lg text-indigo-600">
                        <i class="material-icons text-[20px]">info</i>
                    </div>
                    <h3 class="text-sm font-bold text-slate-700 uppercase tracking-wide">Informasi Umum</h3>
                </div>
                
                <div class="p-6">
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-6">
                        
                        {{-- Klien --}}
                        <div class="sm:col-span-2 flex items-center gap-4 pb-6 border-b border-slate-100">
                            <div class="w-12 h-12 rounded-full bg-slate-100 flex items-center justify-center text-slate-500 border border-slate-200">
                                <i class="material-icons text-xl">business</i>
                            </div>
                            <div>
                                <p class="text-xs font-bold text-slate-400 uppercase">Klien</p>
                                <h4 class="text-lg font-bold text-slate-800">{{ $bulkSalesPayment->client->client_name ?? 'N/A' }}</h4>
                            </div>
                        </div>

                        <div>
                            <label class="block text-xs font-bold text-slate-400 uppercase mb-1">Metode Pembayaran</label>
                            <span class="text-sm font-medium text-slate-800">
                                {{ $bulkSalesPayment->paymentMethod->name ?? 'N/A' }}
                            </span>
                        </div>

                        <div>
                            <label class="block text-xs font-bold text-slate-400 uppercase mb-1">Tanggal Transaksi</label>
                            <div class="flex items-center gap-2 text-sm font-semibold text-slate-800">
                                <i class="material-icons text-slate-400 text-[16px]">event</i>
                                <span>{{ $bulkSalesPayment->payment_date->format('d F Y') }}</span>
                            </div>
                        </div>
                        
                        <div>
                            <label class="block text-xs font-bold text-slate-400 uppercase mb-1">Dibuat Oleh</label>
                            <span class="text-sm font-medium text-slate-800">
                                {{ $bulkSalesPayment->processedByUser->full_name ?? 'System' }}
                            </span>
                        </div>

                        <div>
                            <label class="block text-xs font-bold text-slate-400 uppercase mb-1">Bukti Transfer</label>
                            @if(!empty($bulkSalesPayment->proof_of_payment_path))
                                <a href="{{ asset('storage/' . $bulkSalesPayment->proof_of_payment_path) }}" target="_blank" class="inline-flex items-center text-sm font-bold text-indigo-600 hover:text-indigo-800 hover:underline gap-1">
                                    <i class="material-icons text-[18px]">image</i> Lihat Bukti
                                </a>
                            @elseif(!empty($details['proof_path']))
                                <a href="{{ asset('storage/' . $details['proof_path']) }}" target="_blank" class="inline-flex items-center text-sm font-bold text-indigo-600 hover:text-indigo-800 hover:underline gap-1">
                                    <i class="material-icons text-[18px]">image</i> Lihat Bukti (Arsip)
                                </a>
                            @else
                                <span class="text-slate-400 text-sm italic">Tidak ada bukti.</span>
                            @endif
                        </div>

                        <div class="sm:col-span-2">
                            <label class="block text-xs font-bold text-slate-400 uppercase mb-1">Catatan</label>
                            <div class="bg-slate-50 p-3 rounded-lg border border-slate-100 text-sm text-slate-600 italic">
                                "{{ $bulkSalesPayment->notes ?? 'Tidak ada catatan.' }}"
                            </div>
                        </div>

                    </div>
                </div>
            </div>

            {{-- LIST INVOICE --}}
            <div class="dashboard-card p-0 overflow-hidden shadow-sm">
                <div class="px-6 py-4 border-b border-slate-100 bg-slate-50/50 flex items-center gap-3">
                    <div class="p-2 bg-emerald-50 rounded-lg text-emerald-600">
                        <i class="material-icons text-[20px]">playlist_add_check</i>
                    </div>
                    <h3 class="text-sm font-bold text-slate-700 uppercase tracking-wide">Invoice Terkait</h3>
                </div>
                <div class="overflow-x-auto">
                    <table class="w-full text-left border-collapse">
                        <thead class="bg-slate-50 border-b border-slate-200">
                            <tr>
                                <th class="px-6 py-3 text-xs font-bold text-slate-500 uppercase">No. Invoice</th>
                                <th class="px-6 py-3 text-xs font-bold text-slate-500 uppercase">Jatuh Tempo</th>
                                <th class="px-6 py-3 text-xs font-bold text-slate-500 uppercase text-right">Total Tagihan</th>
                                <th class="px-6 py-3 text-xs font-bold text-slate-500 uppercase text-right">Alokasi Pembayaran</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            @foreach($invoices as $invoice)
                                @php
                                    // Coba cari alokasi spesifik dari tabel payments jika ada
                                    $allocated = $bulkSalesPayment->payments->where('invoice_id', $invoice->invoice_id)->first();
                                    $amountPaid = $allocated ? $allocated->amount : 0;
                                @endphp
                                <tr class="hover:bg-slate-50">
                                    <td class="px-6 py-3 text-sm font-bold text-indigo-600">
                                        <a href="{{ route('admin.invoices.show', $invoice->invoice_id) }}" class="hover:underline">
                                            {{ $invoice->invoice_number }}
                                        </a>
                                    </td>
                                    <td class="px-6 py-3 text-sm text-slate-600">
                                        {{ $invoice->due_date->format('d M Y') }}
                                    </td>
                                    <td class="px-6 py-3 text-sm text-slate-600 text-right">
                                        Rp {{ number_format($invoice->total_amount, 0, ',', '.') }}
                                    </td>
                                    <td class="px-6 py-3 text-sm font-bold text-emerald-600 text-right font-mono">
                                        {{-- Jika status rejected, alokasi 0 --}}
                                        @if($bulkSalesPayment->status == 'rejected')
                                            <span class="text-slate-400 line-through">Rp {{ number_format($amountPaid, 0, ',', '.') }}</span>
                                        @else
                                            Rp {{ number_format($amountPaid, 0, ',', '.') }}
                                        @endif
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        {{-- KOLOM KANAN: STATUS & LOG --}}
        <div class="lg:col-span-1 space-y-6">
            
            {{-- Card Nominal --}}
            <div class="dashboard-card p-6 text-center shadow-md bg-gradient-to-br from-white to-slate-50">
                <p class="text-xs font-bold text-slate-400 uppercase tracking-widest mb-2">Total Transaksi</p>
                <h2 class="text-3xl font-bold text-emerald-600 font-mono tracking-tight">Rp {{ number_format($bulkSalesPayment->total_amount, 0, ',', '.') }}</h2>
            </div>

            {{-- Card Status --}}
            <div class="dashboard-card p-6 shadow-sm">
                <h3 class="text-sm font-bold text-slate-800 uppercase tracking-wide mb-4 flex items-center gap-2">
                    <i class="material-icons text-slate-400">flag</i> Status Pembayaran
                </h3>

                <div class="flex justify-center mb-6">
                    @if($bulkSalesPayment->status == 'completed' || $bulkSalesPayment->status == 'approved')
                        <div class="px-4 py-2 bg-emerald-100 text-emerald-700 rounded-full font-bold flex items-center gap-2">
                            <i class="material-icons">check_circle</i> Selesai (Approved)
                        </div>
                    @elseif($bulkSalesPayment->status == 'rejected' || $bulkSalesPayment->status == 'failed')
                        <div class="px-4 py-2 bg-red-100 text-red-700 rounded-full font-bold flex items-center gap-2">
                            <i class="material-icons">cancel</i> Ditolak (Rejected)
                        </div>
                    @else
                        <div class="px-4 py-2 bg-amber-100 text-amber-700 rounded-full font-bold flex items-center gap-2">
                            <i class="material-icons">hourglass_empty</i> Pending
                        </div>
                    @endif
                </div>

                {{-- Approval Log --}}
                @if($bulkSalesPayment->approved_at)
                    <div class="border-t border-slate-100 pt-4 mt-4">
                        <p class="text-xs text-slate-400 uppercase font-bold mb-1">Disetujui Oleh</p>
                        <div class="flex items-center gap-2">
                            <div class="w-8 h-8 rounded-full bg-emerald-50 text-emerald-600 flex items-center justify-center font-bold text-xs">
                                {{ substr($bulkSalesPayment->approvedByUser->full_name ?? 'A', 0, 1) }}
                            </div>
                            <div>
                                <p class="text-sm font-bold text-slate-700">{{ $bulkSalesPayment->approvedByUser->full_name ?? 'System' }}</p>
                                <p class="text-xs text-slate-500">{{ $bulkSalesPayment->approved_at->format('d M Y, H:i') }}</p>
                            </div>
                        </div>
                    </div>
                @endif

                {{-- Rejection Log --}}
                @if($bulkSalesPayment->rejected_at)
                    <div class="border-t border-slate-100 pt-4 mt-4">
                        <p class="text-xs text-slate-400 uppercase font-bold mb-1">Ditolak Oleh</p>
                        <div class="flex items-center gap-2 mb-2">
                            <div class="w-8 h-8 rounded-full bg-red-50 text-red-600 flex items-center justify-center font-bold text-xs">
                                {{ substr($bulkSalesPayment->rejectedByUser->full_name ?? 'R', 0, 1) }}
                            </div>
                            <div>
                                <p class="text-sm font-bold text-slate-700">{{ $bulkSalesPayment->rejectedByUser->full_name ?? 'System' }}</p>
                                <p class="text-xs text-slate-500">{{ $bulkSalesPayment->rejected_at->format('d M Y, H:i') }}</p>
                            </div>
                        </div>
                        @if($bulkSalesPayment->rejection_reason)
                            <div class="bg-red-50 p-3 rounded-md text-xs text-red-700 border border-red-100">
                                <strong>Alasan:</strong> {{ $bulkSalesPayment->rejection_reason }}
                            </div>
                        @endif
                    </div>
                @endif

            </div>
        </div>

    </div>
</div>
@endsection
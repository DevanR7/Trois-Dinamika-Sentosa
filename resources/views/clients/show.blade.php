@extends('layouts.app')

@section('title', 'Detail Klien')

@section('content')
<div class="max-w-7xl mx-auto">

    {{-- HEADER HALAMAN --}}
    <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center mb-6 gap-4">
        <div>
            <div class="flex items-center gap-2 text-sm text-gray-500 mb-1">
                <a href="{{ route('clients.index') }}" class="hover:text-indigo-600 transition">Klien</a>
                <span>/</span>
                <span class="text-gray-800">Detail</span>
            </div>
            <h2 class="text-2xl font-bold text-gray-900 tracking-tight">{{ $client->client_name }}</h2>
        </div>
        
        <div class="flex gap-3">
            <a href="{{ route('clients.index') }}" class="px-4 py-2 bg-white border border-gray-300 rounded-lg text-gray-700 text-sm font-medium hover:bg-gray-50 transition shadow-sm">
                Kembali
            </a>
            <a href="{{ route('clients.edit', $client->client_id) }}" class="px-4 py-2 bg-indigo-600 hover:bg-indigo-700 text-white text-sm font-bold rounded-lg shadow-md transition flex items-center gap-2">
                <i class="bi bi-pencil-square"></i> Edit Profil
            </a>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-12 gap-6">
        
        {{-- ===================================================
             KOLOM KIRI: PROFIL & SALDO (Span 4)
             =================================================== --}}
        <div class="lg:col-span-4 space-y-6">
            
            {{-- CARD PROFIL --}}
            <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
                <div class="p-6 text-center border-b border-gray-100 bg-gray-50">
                    <div class="w-20 h-20 bg-white rounded-full flex items-center justify-center mx-auto mb-3 border border-gray-200 shadow-sm">
                        <i class="bi bi-building text-3xl text-indigo-500"></i>
                    </div>
                    <h3 class="text-lg font-bold text-gray-900 mb-1">{{ $client->client_name }}</h3>
                    <p class="text-sm text-gray-500">{{ $client->person_in_charge ?? 'Tanpa PIC' }}</p>
                    
                    <div class="mt-3">
                        @if($client->trashed())
                            <span class="px-2.5 py-0.5 rounded-full text-xs font-bold bg-red-100 text-red-800 border border-red-200 uppercase">Diarsipkan</span>
                        @elseif($client->is_locked)
                            <span class="px-2.5 py-0.5 rounded-full text-xs font-bold bg-gray-800 text-white border border-gray-600 uppercase"><i class="bi bi-lock-fill mr-1"></i> Terkunci</span>
                        @elseif($client->is_approved)
                            <span class="px-2.5 py-0.5 rounded-full text-xs font-bold bg-green-100 text-green-800 border border-green-200 uppercase">Aktif</span>
                        @else
                            <span class="px-2.5 py-0.5 rounded-full text-xs font-bold bg-yellow-100 text-yellow-800 border border-yellow-200 uppercase">Menunggu</span>
                        @endif
                    </div>
                </div>
                
                <div class="p-6 space-y-4">
                    <div>
                        <span class="block text-xs font-bold text-gray-400 uppercase mb-1">Email</span>
                        <span class="text-sm font-medium text-gray-900 break-all">{{ $client->email ?? '-' }}</span>
                    </div>
                    <div>
                        <span class="block text-xs font-bold text-gray-400 uppercase mb-1">Telepon</span>
                        <span class="text-sm font-medium text-gray-900">{{ $client->phone_number ?? '-' }}</span>
                    </div>
                    <div>
                        <span class="block text-xs font-bold text-gray-400 uppercase mb-1">Alamat</span>
                        <p class="text-sm text-gray-700 bg-gray-50 p-3 rounded-lg border border-gray-100">
                            {{ $client->address ?? '-' }}
                        </p>
                    </div>
                    <div class="pt-4 border-t border-gray-100">
                        <span class="block text-xs font-bold text-gray-400 uppercase mb-1">Terdaftar Sejak</span>
                        <span class="text-sm text-gray-600">{{ $client->created_at->format('d F Y') }}</span>
                    </div>
                </div>
            </div>

            {{-- CARD SALDO --}}
            <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6 text-center">
                <span class="text-xs font-bold text-gray-400 uppercase tracking-wider">Saldo Kredit (Deposit)</span>
                <div class="text-3xl font-bold text-green-600 my-2">
                    Rp {{ number_format($client->balance ?? 0, 0, ',', '.') }}
                </div>
                <p class="text-xs text-gray-400">Dapat digunakan untuk pembayaran invoice.</p>
            </div>

        </div>

        {{-- ===================================================
             KOLOM KANAN: RIWAYAT (Span 8)
             =================================================== --}}
        <div class="lg:col-span-8 space-y-6">
            
            {{-- 1. INVOICE TERBARU --}}
            <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
                <div class="px-6 py-4 border-b border-gray-100 flex justify-between items-center">
                    <h3 class="text-sm font-bold text-gray-800 uppercase tracking-wider flex items-center gap-2">
                        <i class="bi bi-receipt text-indigo-500"></i> 5 Invoice Terbaru
                    </h3>
                    @if($client->salesInvoices()->count() > 0)
                        <a href="{{ route('invoices.index', ['search' => $client->client_name]) }}" class="text-xs font-bold text-indigo-600 hover:text-indigo-800 hover:underline">
                            Lihat Semua
                        </a>
                    @endif
                </div>
                <div class="overflow-x-auto">
                    <table class="w-full text-left border-collapse">
                        <thead class="bg-gray-50 border-b border-gray-200">
                            <tr>
                                <th class="px-6 py-3 text-xs font-bold text-gray-500 uppercase">No. Invoice</th>
                                <th class="px-6 py-3 text-xs font-bold text-gray-500 uppercase">Tanggal</th>
                                <th class="px-6 py-3 text-xs font-bold text-gray-500 uppercase text-right">Total</th>
                                <th class="px-6 py-3 text-xs font-bold text-gray-500 uppercase text-center">Status</th>
                                <th class="px-6 py-3 text-xs font-bold text-gray-500 uppercase text-center">Aksi</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            @forelse($client->salesInvoices()->latest('order_date')->take(5)->get() as $invoice)
                            <tr class="hover:bg-gray-50 transition">
                                <td class="px-6 py-3 text-sm font-mono font-bold text-indigo-600">
                                    {{ $invoice->invoice_number }}
                                </td>
                                <td class="px-6 py-3 text-sm text-gray-600">
                                    {{ $invoice->order_date->format('d M Y') }}
                                </td>
                                <td class="px-6 py-3 text-right text-sm font-bold text-gray-900">
                                    Rp {{ number_format($invoice->total_amount, 0, ',', '.') }}
                                </td>
                                <td class="px-6 py-3 text-center">
                                    @if($invoice->status == 'paid') 
                                        <span class="px-2 py-0.5 rounded text-[10px] font-bold bg-green-100 text-green-800 border border-green-200 uppercase">Lunas</span>
                                    @elseif($invoice->status == 'partially_paid') 
                                        <span class="px-2 py-0.5 rounded text-[10px] font-bold bg-cyan-100 text-cyan-800 border border-cyan-200 uppercase">Cicilan</span>
                                    @elseif($invoice->status == 'unpaid') 
                                        <span class="px-2 py-0.5 rounded text-[10px] font-bold bg-red-100 text-red-800 border border-red-200 uppercase">Belum Lunas</span>
                                    @else 
                                        <span class="px-2 py-0.5 rounded text-[10px] font-bold bg-gray-100 text-gray-800 border border-gray-200 uppercase">{{ $invoice->status }}</span>
                                    @endif
                                </td>
                                <td class="px-6 py-3 text-center">
                                    <a href="{{ route('invoices.show', $invoice->invoice_id) }}" class="p-1.5 bg-white border border-gray-300 rounded-md text-indigo-600 hover:bg-indigo-50 transition shadow-sm inline-block" title="Detail">
                                        <i class="bi bi-eye"></i>
                                    </a>
                                </td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="5" class="px-6 py-8 text-center text-gray-500 text-sm italic">Belum ada invoice.</td>
                            </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

            {{-- 2. RIWAYAT SALDO (LEDGER) --}}
            <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
                <div class="px-6 py-4 border-b border-gray-100 bg-gray-50 flex items-center gap-2">
                    <i class="bi bi-journal-text text-indigo-500"></i>
                    <h3 class="text-sm font-bold text-gray-700 uppercase tracking-wider">Riwayat Saldo (Ledger)</h3>
                </div>
                <div class="overflow-x-auto">
                    <table class="w-full text-left border-collapse">
                        <thead class="bg-white border-b border-gray-200">
                            <tr>
                                <th class="px-6 py-3 text-xs font-bold text-gray-500 uppercase w-32">Tanggal</th>
                                <th class="px-6 py-3 text-xs font-bold text-gray-500 uppercase">Keterangan</th>
                                <th class="px-6 py-3 text-xs font-bold text-gray-500 uppercase text-right">Masuk (Kredit)</th>
                                <th class="px-6 py-3 text-xs font-bold text-gray-500 uppercase text-right">Keluar (Debit)</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            @forelse($ledgers as $ledger)
                            <tr class="hover:bg-gray-50 transition">
                                <td class="px-6 py-3 text-sm text-gray-600 font-mono">
                                    {{ $ledger->transaction_date->format('d/m/Y') }}
                                </td>
                                <td class="px-6 py-3 text-sm text-gray-800">
                                    <div class="line-clamp-2" title="{{ $ledger->description }}">{{ $ledger->description }}</div>
                                    @if($ledger->reference_type === \App\Models\SalesReturn::class && $ledger->reference)
                                        <a href="{{ route('sales-returns.show', $ledger->reference_id) }}" class="text-[10px] text-indigo-600 hover:underline font-medium mt-1 block">Lihat Retur #{{ $ledger->reference->return_number }}</a>
                                    @elseif($ledger->reference_type === \App\Models\Payment::class && $ledger->reference && $ledger->reference->salesInvoice)
                                        <a href="{{ route('invoices.show', $ledger->reference->salesInvoice->invoice_id) }}" class="text-[10px] text-indigo-600 hover:underline font-medium mt-1 block">Lihat Invoice #{{ $ledger->reference->salesInvoice->invoice_number }}</a>
                                    @endif
                                </td>
                                <td class="px-6 py-3 text-right text-sm font-bold text-green-600">
                                    {{ $ledger->type == 'credit' && $ledger->amount > 0 ? 'Rp '.number_format($ledger->amount, 0, ',', '.') : '-' }}
                                </td>
                                <td class="px-6 py-3 text-right text-sm font-bold text-red-500">
                                    {{ $ledger->type == 'debit' && $ledger->amount < 0 ? 'Rp '.number_format(abs($ledger->amount), 0, ',', '.') : '-' }}
                                </td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="4" class="px-6 py-8 text-center text-gray-500 text-sm italic">Belum ada riwayat transaksi saldo.</td>
                            </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
                
                {{-- Pagination --}}
                @if($ledgers->hasPages())
                <div class="px-6 py-4 border-t border-gray-200 bg-gray-50">
                    {{ $ledgers->links() }}
                </div>
                @endif
            </div>

        </div>

    </div>
</div>
@endsection
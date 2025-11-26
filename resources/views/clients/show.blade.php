@extends('layouts.app')

@section('title', 'Detail Klien')

@section('content')
<div class="max-w-7xl mx-auto pb-20 animate-enter">

    {{-- HEADER --}}
    <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center mb-8 gap-4">
        <div>
            <nav class="flex items-center gap-2 text-sm text-slate-500 mb-1">
                <a href="{{ route('clients.index') }}" class="hover:text-indigo-600 transition-colors">Klien</a>
                <span class="mx-2 text-slate-300">/</span>
                <span class="text-slate-800 font-semibold">Detail</span>
            </nav>
            <h1 class="text-2xl font-bold text-slate-800 tracking-tight">{{ $client->client_name }}</h1>
        </div>
        
        <div class="flex gap-2 w-full sm:w-auto">
            <a href="{{ route('clients.index') }}" class="px-4 py-2 bg-white border border-slate-300 text-slate-600 rounded-lg text-sm font-bold hover:bg-slate-50 transition-all shadow-sm flex items-center justify-center gap-2">
                <i class="material-icons text-[18px]">arrow_back</i> Kembali
            </a>
            
            @if(!$client->trashed())
                <a href="{{ route('clients.edit', $client->client_id) }}" class="px-4 py-2 bg-amber-50 border border-amber-200 text-amber-700 rounded-lg text-sm font-bold hover:bg-amber-100 flex items-center gap-2 transition-all shadow-sm">
                    <i class="material-icons text-[18px]">edit</i> Edit
                </a>
                
                <form action="{{ route('clients.destroy', $client->client_id) }}" method="POST" class="form-confirm inline-block">
                    @csrf @method('DELETE')
                    <button type="submit" 
                            data-title="Arsipkan Klien?" 
                            data-text="Klien <b>{{ $client->client_name }}</b> akan diarsipkan."
                            data-btn-text="Ya, Arsipkan"
                            data-btn-color="#ef4444"
                            class="px-4 py-2 bg-red-50 border border-red-200 text-red-600 rounded-lg text-sm font-bold hover:bg-red-100 flex items-center gap-2 transition-all shadow-sm">
                        <i class="material-icons text-[18px]">archive</i> Arsipkan
                    </button>
                </form>
            @else
                <form action="{{ route('clients.restore', $client->client_id) }}" method="POST" class="form-confirm inline-block">
                    @csrf @method('PATCH')
                    <button type="submit" 
                            data-title="Pulihkan Klien?"
                            data-text="Klien <b>{{ $client->client_name }}</b> akan dipulihkan."
                            data-btn-text="Ya, Pulihkan"
                            data-btn-color="#10b981"
                            data-icon="question"
                            class="px-4 py-2 bg-emerald-50 border border-emerald-200 text-emerald-700 rounded-lg text-sm font-bold hover:bg-emerald-100 flex items-center gap-2 transition-all shadow-sm">
                        <i class="material-icons text-[18px]">restore</i> Pulihkan
                    </button>
                </form>
            @endif
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-12 gap-6">
        
        {{-- KOLOM KIRI: PROFIL & SALDO (Span 4) --}}
        <div class="lg:col-span-4 space-y-6">
            
            {{-- CARD PROFIL --}}
            <div class="dashboard-card p-0 overflow-hidden shadow-sm">
                <div class="p-6 text-center border-b border-slate-100 bg-slate-50/50 relative">
                    <div class="w-20 h-20 bg-indigo-100 rounded-full flex items-center justify-center mx-auto mb-3 border-4 border-white shadow-sm">
                        <i class="material-icons text-4xl text-indigo-600">apartment</i>
                    </div>
                    <h3 class="text-lg font-bold text-slate-800 mb-1">{{ $client->client_name }}</h3>
                    <p class="text-sm text-slate-500">{{ $client->person_in_charge ?? 'Tanpa PIC' }}</p>
                    
                    <div class="mt-4">
                        @if($client->trashed())
                            <span class="status-badge status-rejected">Diarsipkan</span>
                        @elseif($client->is_locked)
                            <span class="status-badge status-draft">Terkunci</span>
                        @elseif($client->is_approved)
                            <span class="status-badge status-completed">Aktif</span>
                        @else
                            <span class="status-badge status-pending">Pending</span>
                        @endif
                    </div>
                </div>
                
                <div class="p-6 space-y-5">
                    <div>
                        <span class="block text-xs font-bold text-slate-400 uppercase mb-1">Email</span>
                        <span class="text-sm font-medium text-slate-800 break-all flex items-center gap-2">
                            <i class="material-icons text-slate-400 text-[16px]">email</i> {{ $client->email ?? '-' }}
                        </span>
                    </div>
                    <div>
                        <span class="block text-xs font-bold text-slate-400 uppercase mb-1">Telepon</span>
                        <span class="text-sm font-medium text-slate-800 flex items-center gap-2">
                            <i class="material-icons text-slate-400 text-[16px]">phone</i> {{ $client->phone_number ?? '-' }}
                        </span>
                    </div>
                    <div>
                        <span class="block text-xs font-bold text-slate-400 uppercase mb-1">Alamat</span>
                        <p class="text-sm text-slate-700 bg-slate-50 p-3 rounded-lg border border-slate-100 leading-relaxed">
                            {{ $client->address ?? '-' }}
                        </p>
                    </div>
                    <div class="pt-4 border-t border-slate-100 flex justify-between items-center text-xs text-slate-500">
                        <span>Terdaftar:</span>
                        <span class="font-mono">{{ $client->created_at->format('d M Y') }}</span>
                    </div>
                </div>
            </div>

            {{-- CARD SALDO --}}
            <div class="dashboard-card p-6 text-center shadow-sm">
                <span class="text-xs font-bold text-slate-400 uppercase tracking-wider">Saldo Kredit (Deposit)</span>
                <div class="text-3xl font-bold {{ $client->balance > 0 ? 'text-emerald-600' : 'text-slate-800' }} my-2 font-mono">
                    Rp {{ number_format($client->balance ?? 0, 0, ',', '.') }}
                </div>
                <p class="text-xs text-slate-400">Dapat digunakan untuk pembayaran.</p>
            </div>

        </div>

        {{-- KOLOM KANAN: RIWAYAT (Span 8) --}}
        <div class="lg:col-span-8 space-y-6">
            
            {{-- INVOICE TERBARU --}}
            <div class="dashboard-card p-0 overflow-hidden shadow-sm h-full flex flex-col">
                <div class="px-6 py-4 border-b border-slate-100 bg-slate-50/50 flex justify-between items-center">
                    <h3 class="card-title flex items-center gap-2">
                        <i class="material-icons text-indigo-600 text-lg">receipt_long</i> Riwayat Invoice Terbaru
                    </h3>
                    @if($client->salesInvoices()->count() > 0)
                        <a href="{{ route('invoices.index', ['search' => $client->client_name]) }}" class="text-xs font-bold text-indigo-600 hover:text-indigo-800 hover:underline uppercase tracking-wide">
                            Lihat Semua
                        </a>
                    @endif
                </div>
                
                <div class="overflow-x-auto flex-1">
                    <table class="dashboard-table min-w-full">
                        <thead class="bg-white border-b border-slate-200">
                            <tr>
                                <th class="pl-6">No. Invoice</th>
                                <th>Tanggal</th>
                                <th class="text-right">Total</th>
                                <th class="text-center">Status</th>
                                <th class="text-center pr-6">Aksi</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            @forelse($client->salesInvoices()->latest('order_date')->take(10)->get() as $invoice)
                            <tr class="hover:bg-slate-50/50 transition">
                                <td class="pl-6 py-3">
                                    <span class="text-sm font-mono font-bold text-indigo-600">{{ $invoice->invoice_number }}</span>
                                </td>
                                <td class="py-3 text-sm text-slate-600">
                                    {{ $invoice->order_date->format('d M Y') }}
                                </td>
                                <td class="py-3 text-right text-sm font-bold text-slate-800 font-mono">
                                    Rp {{ number_format($invoice->total_amount, 0, ',', '.') }}
                                </td>
                                <td class="py-3 text-center">
                                    @php
                                        $statusClass = match($invoice->status) {
                                            'paid' => 'status-completed',
                                            'partially_paid' => 'status-approved',
                                            'unpaid' => 'status-rejected',
                                            default => 'status-draft',
                                        };
                                        $statusText = match($invoice->status) {
                                            'paid' => 'LUNAS',
                                            'partially_paid' => 'CICILAN',
                                            'unpaid' => 'BELUM',
                                            default => 'DRAFT',
                                        };
                                    @endphp
                                    <span class="{{ $statusClass }} text-[10px]">{{ $statusText }}</span>
                                </td>
                                <td class="pr-6 py-3 text-center">
                                    {{-- TOMBOL AKSI SIMETRIS (PERBAIKAN) --}}
                                    <a href="{{ route('invoices.show', $invoice->invoice_id) }}" 
                                       class="w-8 h-8 flex items-center justify-center rounded-lg bg-white border border-slate-200 text-slate-500 hover:text-indigo-600 hover:border-indigo-200 transition shadow-sm inline-flex" 
                                       title="Detail">
                                        <i class="material-icons text-[16px]">visibility</i>
                                    </a>
                                </td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="5" class="px-6 py-12 text-center text-slate-500 italic">
                                    Belum ada riwayat transaksi.
                                </td>
                            </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

        </div>

    </div>
</div>
@endsection
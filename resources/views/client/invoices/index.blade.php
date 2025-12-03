@extends('client.layouts.app')

@section('title', 'Daftar Invoice')

@section('content')
<div class="space-y-6">

    <h2 class="text-2xl font-bold text-slate-800 dark:text-slate-100 tracking-tight">Riwayat Invoice</h2>

    {{-- Alert Section --}}
    @if(request()->has('payment_success'))
        <div class="bg-emerald-50 text-emerald-700 p-4 rounded-lg border border-emerald-200 flex items-center gap-2">
            <i class="material-icons">check_circle</i> Pembayaran berhasil! Status akan segera diperbarui.
        </div>
    @endif
    @if(request()->has('payment_pending'))
        <div class="bg-blue-50 text-blue-700 p-4 rounded-lg border border-blue-200 flex items-center gap-2">
            <i class="material-icons">hourglass_empty</i> Pembayaran pending. Menunggu konfirmasi provider.
        </div>
    @endif

    {{-- FILTER SECTION --}}
    <div class="dashboard-card p-6">
        <form action="{{ route('client.invoices.index') }}" method="GET">
            <div class="grid grid-cols-1 md:grid-cols-12 gap-4 items-end">
                
                {{-- Search --}}
                <div class="md:col-span-3">
                    <label class="block text-xs font-bold text-slate-500 uppercase tracking-wide mb-2">Cari No. Invoice</label>
                    <div class="relative">
                        <span class="absolute inset-y-0 left-0 flex items-center pl-3 text-slate-400">
                            <i class="material-icons text-[18px]">search</i>
                        </span>
                        <input type="text" name="search" class="form-input pl-10" placeholder="Contoh: INV/..." value="{{ request('search') }}">
                    </div>
                </div>

                {{-- Order Date --}}
                <div class="md:col-span-2">
                    <label class="block text-xs font-bold text-slate-500 uppercase tracking-wide mb-2">Tgl. Terbit</label>
                    <select name="order_date_filter" class="form-select select2-basic" data-placeholder="-- Semua --">
                        <option value=""></option>
                        @foreach($uniqueOrderDates as $ym => $dateLabel)
                            <option value="{{ $ym }}" @selected(request('order_date_filter') == $ym)>{{ $dateLabel }}</option>
                        @endforeach
                    </select>
                </div>

                {{-- Due Date --}}
                <div class="md:col-span-2">
                    <label class="block text-xs font-bold text-slate-500 uppercase tracking-wide mb-2">Jatuh Tempo</label>
                    <select name="due_date_filter" class="form-select select2-basic" data-placeholder="-- Semua --">
                        <option value=""></option>
                        @foreach($uniqueDueDates as $ym => $dateLabel)
                            <option value="{{ $ym }}" @selected(request('due_date_filter') == $ym)>{{ $dateLabel }}</option>
                        @endforeach
                    </select>
                </div>

                {{-- Status --}}
                <div class="md:col-span-2">
                    <label class="block text-xs font-bold text-slate-500 uppercase tracking-wide mb-2">Status</label>
                    <select name="status_filter" class="form-select select2-basic" data-placeholder="-- Semua --">
                        <option value=""></option>
                        <option value="unpaid" @selected(request('status_filter') == 'unpaid')>Belum Lunas</option>
                        <option value="partially_paid" @selected(request('status_filter') == 'partially_paid')>Cicil</option>
                        <option value="paid" @selected(request('status_filter') == 'paid')>Lunas</option>
                        <option value="cancelled" @selected(request('status_filter') == 'cancelled')>Dibatalkan</option>
                    </select>
                </div>

                {{-- Sort --}}
                <div class="md:col-span-1">
                    <label class="block text-xs font-bold text-slate-500 uppercase tracking-wide mb-2">Urutkan</label>
                    <select name="sort" class="form-select select2-basic" data-placeholder="Urutkan">
                        <option value="terbaru" @selected(request('sort', 'terbaru') == 'terbaru')>Terbaru</option>
                        <option value="terlama" @selected(request('sort') == 'terlama')>Terlama</option>
                    </select>
                </div>

                {{-- Actions --}}
                <div class="md:col-span-2 flex gap-2">
                    <button type="submit" class="bg-slate-800 hover:bg-slate-700 text-white font-bold py-2.5 px-4 rounded-lg w-full transition shadow-md">
                        Filter
                    </button>
                    <a href="{{ route('client.invoices.index') }}" class="bg-white border border-slate-300 text-slate-600 hover:bg-slate-50 font-bold py-2.5 px-4 rounded-lg w-full text-center transition">
                        Reset
                    </a>
                </div>
            </div>
        </form>
    </div>

    {{-- TABLE SECTION --}}
    <div class="dashboard-card overflow-hidden">
        <div class="overflow-x-auto">
            <table class="dashboard-table">
                <thead>
                    <tr>
                        <th>No. Invoice</th>
                        <th>Tanggal Terbit</th>
                        <th>Jatuh Tempo</th>
                        <th class="text-right">Total Tagihan</th>
                        <th class="text-right">Sisa Tagihan</th>
                        <th class="text-center">Status</th>
                        <th class="text-center">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($invoices as $invoice)
                        @php $sisaTagihan = $invoice->remaining_balance; @endphp
                        <tr class="hover:bg-slate-50 dark:hover:bg-slate-800/50">
                            <td>
                                <a href="{{ route('client.invoices.show', $invoice->invoice_id) }}" class="font-bold text-indigo-600 hover:underline">
                                    {{ $invoice->invoice_number }}
                                </a>
                            </td>
                            <td>{{ optional($invoice->order_date)->format('d M Y') }}</td>
                            <td>
                                <span class="{{ optional($invoice->due_date)->isPast() && !in_array($invoice->status, ['paid', 'cancelled']) ? 'text-red-600 font-bold flex items-center gap-1' : '' }}">
                                    @if(optional($invoice->due_date)->isPast() && !in_array($invoice->status, ['paid', 'cancelled']))
                                        <i class="material-icons text-[14px]">warning</i>
                                    @endif
                                    {{ optional($invoice->due_date)->format('d M Y') }}
                                </span>
                            </td>
                            <td class="text-right font-medium text-slate-600 dark:text-slate-400">
                                Rp {{ number_format($invoice->total_amount, 0, ',', '.') }}
                            </td>
                            
                            {{-- LOGIKA SISA TAGIHAN --}}
                            <td class="text-right">
                                @if($sisaTagihan < -0.01)
                                    <div class="flex flex-col items-end">
                                        <span class="text-xs font-bold text-emerald-600 uppercase tracking-wider">Kelebihan</span>
                                        <span class="font-bold text-emerald-600">Rp {{ number_format(abs($sisaTagihan), 0, ',', '.') }}</span>
                                    </div>
                                @elseif($sisaTagihan > 0.01)
                                    <span class="font-bold text-red-600 dark:text-red-400">
                                        Rp {{ number_format($sisaTagihan, 0, ',', '.') }}
                                    </span>
                                @else
                                    <span class="font-bold text-emerald-600 flex items-center justify-end gap-1">
                                        <i class="material-icons text-[16px]">check</i> Lunas
                                    </span>
                                @endif
                            </td>

                            <td class="text-center">
                                @php
                                    $badge = 'status-draft'; // default
                                    $label = $invoice->status;
                                    
                                    if ($invoice->status == 'paid') { $badge = 'status-completed'; $label = 'Lunas'; }
                                    elseif ($invoice->status == 'partially_paid') { $badge = 'status-pending'; $label = 'Cicil'; }
                                    elseif ($invoice->status == 'cancelled') { $badge = 'bg-slate-200 text-slate-600 border-slate-300'; $label = 'Batal'; }
                                    elseif ($invoice->status == 'unpaid') { $badge = 'status-rejected'; $label = 'Belum Lunas'; }
                                @endphp
                                <span class="status-badge {{ $badge }}">
                                    {{ $label }}
                                </span>
                            </td>
                            <td class="text-center">
                                <a href="{{ route('client.invoices.show', $invoice->invoice_id) }}" 
                                   class="inline-flex items-center justify-center w-8 h-8 rounded-full bg-indigo-50 text-indigo-600 hover:bg-indigo-600 hover:text-white transition-all duration-200"
                                   title="Lihat Detail">
                                    <i class="material-icons text-[18px]">visibility</i>
                                </a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="py-12 text-center text-slate-500">
                                <div class="flex flex-col items-center">
                                    <i class="material-icons text-4xl text-slate-300 mb-2">search_off</i>
                                    <p>Tidak ada invoice yang cocok.</p>
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        
        <div class="px-6 py-4 border-t border-slate-100 dark:border-slate-700">
            {{ $invoices->appends(request()->query())->links() }}
        </div>
    </div>
</div>
@endsection
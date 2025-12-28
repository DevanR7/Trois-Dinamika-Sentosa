@extends('client.layouts.app')

@section('title', 'Daftar Tagihan')

@section('content')

    {{-- Header & Filters --}}
    <div class="card mb-6">
        <div class="card-body flex flex-col md:flex-row gap-4 items-center justify-between">
            
            {{-- Search Bar --}}
            <form action="{{ route('client.invoices.index') }}" method="GET" class="w-full md:w-1/3">
                <div class="relative">
                    <div class="absolute inset-y-0 left-0 flex items-center pl-3 pointer-events-none">
                        <i class="material-icons text-slate-400">search</i>
                    </div>
                    <input type="text" name="search" value="{{ request('search') }}" 
                        class="block w-full p-2 pl-10 text-sm text-slate-900 border border-slate-300 rounded-lg bg-gray-50 focus:ring-indigo-500 focus:border-indigo-500 dark:bg-slate-700 dark:border-slate-600 dark:placeholder-slate-400 dark:text-white" 
                        placeholder="Cari No. Invoice...">
                </div>
            </form>

            {{-- Actions & Filters --}}
            <div class="flex gap-2 w-full md:w-auto overflow-x-auto">
                {{-- Tombol Bayar Sekaligus --}}
                <a href="{{ route('client.invoices.bulkPay.create') }}" class="btn btn-primary whitespace-nowrap">
                    <i class="material-icons text-sm">checklist_rtl</i> Bayar Sekaligus
                </a>

                {{-- Status Filter --}}
                <div class="min-w-[150px]">
                    <select onchange="window.location.href=this.value" class="tom-select">
                        <option value="{{ route('client.invoices.index') }}">Semua Status</option>
                        <option value="{{ route('client.invoices.index', array_merge(request()->query(), ['status' => 'unpaid'])) }}" {{ request('status') == 'unpaid' ? 'selected' : '' }}>Belum Lunas</option>
                        <option value="{{ route('client.invoices.index', array_merge(request()->query(), ['status' => 'partially_paid'])) }}" {{ request('status') == 'partially_paid' ? 'selected' : '' }}>Cicilan</option>
                        <option value="{{ route('client.invoices.index', array_merge(request()->query(), ['status' => 'paid'])) }}" {{ request('status') == 'paid' ? 'selected' : '' }}>Lunas</option>
                    </select>
                </div>
            </div>
        </div>
    </div>

    {{-- Invoice List --}}
    <div class="card">
        <div class="table-container">
            <table class="table-modern">
                <thead>
                    <tr>
                        <th>No. Invoice</th>
                        <th>Tgl Order</th>
                        <th>Jatuh Tempo</th>
                        <th class="text-right">Total Tagihan</th>
                        <th class="text-right">Sisa Tagihan</th>
                        <th class="text-center">Status</th>
                        <th class="text-center">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($invoices as $invoice)
                        <tr>
                            <td class="font-bold text-slate-700 dark:text-white">
                                {{ $invoice->invoice_number }}
                            </td>
                            <td>{{ $invoice->order_date->format('d M Y') }}</td>
                            <td>
                                <div class="flex items-center gap-2">
                                    <span>{{ $invoice->due_date->format('d M Y') }}</span>
                                    @if($invoice->status != 'paid' && $invoice->due_date < now())
                                        <span class="w-2 h-2 rounded-full bg-red-500" title="Jatuh Tempo"></span>
                                    @endif
                                </div>
                            </td>
                            <td class="text-right font-medium">
                                Rp {{ number_format($invoice->total_amount, 0, ',', '.') }}
                            </td>
                            <td class="text-right font-bold text-slate-800 dark:text-white">
                                Rp {{ number_format($invoice->remaining_balance, 0, ',', '.') }}
                            </td>
                            <td class="text-center">
                                @php
                                    $badgeClass = match($invoice->status) {
                                        'paid' => 'badge-success',
                                        'partially_paid' => 'badge-warning',
                                        'unpaid' => 'badge-danger',
                                        'overdue' => 'badge-danger', // Jika ada logic overdue di model
                                        'cancelled' => 'badge-secondary',
                                        default => 'badge-secondary',
                                    };
                                    $label = match($invoice->status) {
                                        'paid' => 'Lunas',
                                        'partially_paid' => 'Cicilan',
                                        'unpaid' => 'Belum Lunas',
                                        'cancelled' => 'Batal',
                                        default => ucfirst($invoice->status)
                                    };
                                @endphp
                                <span class="badge {{ $badgeClass }}">{{ $label }}</span>
                            </td>
                            <td class="text-center">
                                <a href="{{ route('client.invoices.show', $invoice->invoice_id) }}" 
                                   class="btn btn-icon btn-sm btn-secondary" 
                                   title="Lihat Detail & Bayar">
                                    <i class="material-icons text-sm">visibility</i>
                                </a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="text-center py-12">
                                <div class="flex flex-col items-center justify-center">
                                    <div class="bg-slate-100 dark:bg-slate-800 p-4 rounded-full mb-3">
                                        <i class="material-icons text-slate-400 text-3xl">receipt</i>
                                    </div>
                                    <p class="text-slate-500 dark:text-slate-400 font-medium">Belum ada tagihan.</p>
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        {{-- Pagination --}}
        <div class="p-4 border-t border-slate-100 dark:border-slate-700/50">
            {{ $invoices->links() }}
        </div>
    </div>

@endsection
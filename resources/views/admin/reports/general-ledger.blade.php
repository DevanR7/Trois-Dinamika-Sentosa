@extends('admin.layouts.app')

@section('title', 'Buku Besar (General Ledger)')

@section('content')
    {{-- Header --}}
    <div class="page-header print:hidden">
        <div>
            <h1 class="page-title">Buku Besar (General Ledger)</h1>
            <p class="page-subtitle">Laporan detail seluruh transaksi jurnal akuntansi</p>
        </div>
        <div>
            <button onclick="window.print()" class="btn btn-secondary">
                <i class="material-icons text-[18px]">print</i>
                Cetak Laporan
            </button>
        </div>
    </div>

    {{-- Filter Section --}}
    <div class="card mb-6 print:hidden border-l-4 border-indigo-500">
        <div class="card-body">
            <form action="{{ route('admin.reports.general-ledger') }}" method="GET">
                <div class="grid grid-cols-1 lg:grid-cols-12 gap-4 items-end">
                    
                    {{-- Filter Akun --}}
                    <div class="lg:col-span-4">
                        <label class="form-label">Filter Akun (COA)</label>
                        <select name="account_id" class="tom-select" placeholder="Semua Akun...">
                            <option value="">Semua Akun</option>
                            @foreach($accounts as $acc)
                                <option value="{{ $acc->account_id }}" {{ request('account_id') == $acc->account_id ? 'selected' : '' }}>
                                    {{ $acc->account_number }} - {{ $acc->account_name }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    {{-- Tanggal Mulai --}}
                    <div class="lg:col-span-2">
                        <label class="form-label">Dari Tanggal</label>
                        <div class="relative">
                            <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                <i class="material-icons text-slate-400 text-[18px]">calendar_today</i>
                            </div>
                            <input type="date" name="start_date" class="form-input pl-10" 
                                   value="{{ request('start_date', now()->startOfMonth()->format('Y-m-d')) }}">
                        </div>
                    </div>

                    {{-- Tanggal Selesai --}}
                    <div class="lg:col-span-2">
                        <label class="form-label">Sampai Tanggal</label>
                        <div class="relative">
                            <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                <i class="material-icons text-slate-400 text-[18px]">event</i>
                            </div>
                            <input type="date" name="end_date" class="form-input pl-10" 
                                   value="{{ request('end_date', now()->endOfMonth()->format('Y-m-d')) }}">
                        </div>
                    </div>

                    {{-- Cari No Jurnal --}}
                    <div class="lg:col-span-2">
                        <label class="form-label">No. Jurnal</label>
                        <input type="text" name="journal_group_id" class="form-input" 
                               placeholder="Cth: INV-2023..." 
                               value="{{ request('journal_group_id') }}">
                    </div>

                    {{-- Buttons --}}
                    <div class="lg:col-span-2 flex gap-2">
                        <button type="submit" class="btn btn-primary w-full justify-center">
                            <i class="material-icons text-[18px]">search</i>
                            Filter
                        </button>
                        <a href="{{ route('admin.reports.general-ledger') }}" class="btn btn-secondary w-12 justify-center" title="Reset Filter">
                            <i class="material-icons text-[18px]">restart_alt</i>
                        </a>
                    </div>
                </div>
            </form>
        </div>
    </div>

    {{-- Report Header (Print Only) --}}
    <div class="hidden print:block mb-6 text-center border-b-2 border-black pb-4">
        <h2 class="text-2xl font-bold uppercase tracking-wider">Laporan Buku Besar</h2>
        <p class="text-sm mt-1">
            Periode: 
            <strong>{{ \Carbon\Carbon::parse(request('start_date', now()->startOfMonth()))->format('d M Y') }}</strong> 
            s/d 
            <strong>{{ \Carbon\Carbon::parse(request('end_date', now()->endOfMonth()))->format('d M Y') }}</strong>
        </p>
        @if(request('account_id'))
            @php $selectedAccount = $accounts->firstWhere('account_id', request('account_id')); @endphp
            <p class="text-sm mt-1">Akun: {{ $selectedAccount->account_number }} - {{ $selectedAccount->account_name }}</p>
        @endif
    </div>

    {{-- Data Table --}}
    <div class="card card-plain">
        <div class="table-container">
            <table class="table-modern w-full">
                <thead>
                    <tr>
                        <th class="w-32">Tanggal</th>
                        <th>No. Jurnal</th>
                        <th>Akun (COA)</th>
                        <th>Keterangan</th>
                        <th>Referensi</th>
                        <th class="text-right w-32 text-emerald-600">Debit</th>
                        <th class="text-right w-32 text-rose-600">Kredit</th>
                    </tr>
                </thead>
                <tbody>
                    @php
                        $totalDebit = 0;
                        $totalCredit = 0;
                    @endphp
                    @forelse($journalEntries as $entry)
                        @php
                            $totalDebit += $entry->debit;
                            $totalCredit += $entry->credit;
                        @endphp
                        <tr class="hover:bg-slate-50 dark:hover:bg-slate-800/50 transition-colors">
                            {{-- Tanggal --}}
                            <td class="whitespace-nowrap">
                                <span class="font-bold text-slate-700 dark:text-slate-200">
                                    {{ $entry->entry_date->format('d/m/Y') }}
                                </span>
                            </td>

                            {{-- No Jurnal --}}
                            <td>
                                <span class="font-mono text-xs font-bold bg-slate-100 dark:bg-slate-700 px-2 py-1 rounded text-slate-600 dark:text-slate-300">
                                    {{ $entry->journal_group_id }}
                                </span>
                            </td>

                            {{-- Akun --}}
                            <td>
                                <div class="flex flex-col">
                                    <span class="font-bold text-slate-700 dark:text-slate-200 text-xs">
                                        {{ $entry->account->account_number ?? '-' }}
                                    </span>
                                    <span class="text-xs text-slate-500 truncate max-w-[150px]" title="{{ $entry->account->account_name ?? '-' }}">
                                        {{ $entry->account->account_name ?? '-' }}
                                    </span>
                                </div>
                            </td>

                            {{-- Keterangan --}}
                            <td>
                                <div class="text-sm text-slate-600 dark:text-slate-300 max-w-xs truncate" title="{{ $entry->description }}">
                                    {{ $entry->description }}
                                </div>
                            </td>

                            {{-- Referensi --}}
                            <td>
                                @if($entry->reference_type && $entry->reference_id)
                                    @php
                                        $type = class_basename($entry->reference_type);
                                        $badgeColor = 'bg-slate-100 text-slate-500 border-slate-200';
                                        
                                        if($type == 'SalesInvoice') $badgeColor = 'bg-indigo-50 text-indigo-600 border-indigo-200';
                                        if($type == 'PurchaseOrder') $badgeColor = 'bg-blue-50 text-blue-600 border-blue-200';
                                        if($type == 'Payment') $badgeColor = 'bg-emerald-50 text-emerald-600 border-emerald-200';
                                        if($type == 'Expense') $badgeColor = 'bg-rose-50 text-rose-600 border-rose-200';
                                    @endphp
                                    <span class="badge {{ $badgeColor }} text-[10px] font-mono border px-1.5 py-0.5">
                                        {{ $type }} #{{ $entry->reference_id }}
                                    </span>
                                @else
                                    <span class="text-slate-300 text-xs">-</span>
                                @endif
                            </td>

                            {{-- Debit --}}
                            <td class="text-right font-mono font-bold text-emerald-600 bg-emerald-50/20 dark:bg-emerald-900/10">
                                @if($entry->debit > 0)
                                    Rp {{ number_format($entry->debit, 0, ',', '.') }}
                                @else
                                    <span class="text-slate-300 font-normal">-</span>
                                @endif
                            </td>

                            {{-- Kredit --}}
                            <td class="text-right font-mono font-bold text-rose-600 bg-rose-50/20 dark:bg-rose-900/10">
                                @if($entry->credit > 0)
                                    Rp {{ number_format($entry->credit, 0, ',', '.') }}
                                @else
                                    <span class="text-slate-300 font-normal">-</span>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="text-center py-12">
                                <div class="flex flex-col items-center justify-center text-slate-400">
                                    <i class="material-icons text-4xl mb-2">find_in_page</i>
                                    <p class="text-sm">Tidak ada data jurnal pada periode ini.</p>
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
                
                {{-- Footer Total (Per Halaman) --}}
                @if($journalEntries->count() > 0)
                    <tfoot>
                        <tr class="bg-slate-100 dark:bg-slate-800 font-bold border-t-2 border-slate-300 dark:border-slate-600">
                            <td colspan="5" class="text-right uppercase text-xs text-slate-500 py-3">Total (Halaman Ini)</td>
                            <td class="text-right font-mono text-emerald-700 dark:text-emerald-400 py-3">
                                Rp {{ number_format($totalDebit, 0, ',', '.') }}
                            </td>
                            <td class="text-right font-mono text-rose-700 dark:text-rose-400 py-3">
                                Rp {{ number_format($totalCredit, 0, ',', '.') }}
                            </td>
                        </tr>
                    </tfoot>
                @endif
            </table>
        </div>

        {{-- Pagination --}}
        <div class="p-4 border-t border-slate-100 dark:border-slate-700 print:hidden">
            {{ $journalEntries->links('vendor.pagination.admin') }}
        </div>
    </div>
@endsection
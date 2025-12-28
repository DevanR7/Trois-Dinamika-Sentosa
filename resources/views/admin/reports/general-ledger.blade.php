@extends('admin.layouts.app')

@section('title', 'Buku Besar (General Ledger)')

@section('content')

    {{-- HEADER & FILTERS --}}
    <div class="page-header">
        <div>
            <h1 class="page-title">Buku Besar (General Ledger)</h1>
            <p class="page-subtitle">Rincian seluruh transaksi jurnal per akun.</p>
        </div>
        <a href="{{ route('admin.reports.index') }}" class="btn btn-secondary">
            <i class="material-icons text-sm mr-1">arrow_back</i> Kembali ke Laporan
        </a>
    </div>

    <div class="card mb-6">
        <div class="card-body">
            <form action="{{ route('admin.reports.general-ledger') }}" method="GET" class="grid grid-cols-1 md:grid-cols-4 gap-4">
                
                {{-- Date Range --}}
                <div>
                    <label class="form-label text-[10px]">Dari Tanggal</label>
                    <input type="date" name="start_date" class="form-input text-sm py-1.5" 
                           value="{{ request('start_date', date('Y-m-01')) }}">
                </div>
                <div>
                    <label class="form-label text-[10px]">Sampai Tanggal</label>
                    <input type="date" name="end_date" class="form-input text-sm py-1.5" 
                           value="{{ request('end_date', date('Y-m-t')) }}">
                </div>

                {{-- Account Filter --}}
                <div>
                    <label class="form-label text-[10px]">Filter Akun</label>
                    <select name="account_id" class="tom-select" placeholder="Pilih Akun...">
                        <option value="">Semua Akun</option>
                        @foreach($accounts as $acc)
                            <option value="{{ $acc->account_id }}" {{ request('account_id') == $acc->account_id ? 'selected' : '' }}>
                                {{ $acc->account_number }} - {{ $acc->account_name }}
                            </option>
                        @endforeach
                    </select>
                </div>

                {{-- Journal Group Search --}}
                <div>
                    <label class="form-label text-[10px]">Cari No. Referensi / Grup</label>
                    <div class="input-group">
                        <input type="text" name="journal_group_id" class="form-input text-sm py-1.5" 
                               placeholder="Contoh: INV-2023..." 
                               value="{{ request('journal_group_id') }}">
                        <button type="submit" class="btn btn-primary px-3">
                            <i class="material-icons text-sm">search</i>
                        </button>
                    </div>
                </div>

            </form>
        </div>
    </div>

    {{-- TABLE --}}
    <div class="card">
        <div class="table-container">
            <table class="table-modern">
                <thead>
                    <tr>
                        <th>Tanggal</th>
                        <th>No. Referensi (Grup)</th>
                        <th>Akun</th>
                        <th>Keterangan</th>
                        <th class="text-right text-emerald-600">Debit</th>
                        <th class="text-right text-rose-600">Kredit</th>
                    </tr>
                </thead>
                <tbody class="text-xs md:text-sm">
                    @forelse($journalEntries as $entry)
                        <tr class="hover:bg-slate-50 dark:hover:bg-slate-800/50">
                            <td class="font-medium whitespace-nowrap">
                                {{ $entry->entry_date->format('d/m/Y') }}
                            </td>
                            <td>
                                <span class="font-mono text-slate-500 bg-slate-100 dark:bg-slate-700 px-1.5 py-0.5 rounded text-[10px]">
                                    {{ $entry->journal_group_id }}
                                </span>
                            </td>
                            <td>
                                <div class="font-bold text-slate-700 dark:text-slate-200">
                                    {{ $entry->account->account_name ?? 'Unknown' }}
                                </div>
                                <div class="font-mono text-[10px] text-slate-500">
                                    {{ $entry->account->account_number ?? '-' }}
                                </div>
                            </td>
                            <td class="max-w-xs truncate" title="{{ $entry->description }}">
                                {{ $entry->description }}
                            </td>
                            <td class="text-right font-mono {{ $entry->debit > 0 ? 'font-bold text-emerald-600' : 'text-slate-300' }}">
                                {{ $entry->debit > 0 ? number_format($entry->debit, 0, ',', '.') : '-' }}
                            </td>
                            <td class="text-right font-mono {{ $entry->credit > 0 ? 'font-bold text-rose-600' : 'text-slate-300' }}">
                                {{ $entry->credit > 0 ? number_format($entry->credit, 0, ',', '.') : '-' }}
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="text-center p-8 text-slate-400">
                                <i class="material-icons text-4xl mb-2">find_in_page</i>
                                <p>Tidak ada transaksi jurnal pada periode ini.</p>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        
        <div class="card-footer">
            {{ $journalEntries->links() }}
        </div>
    </div>

@endsection
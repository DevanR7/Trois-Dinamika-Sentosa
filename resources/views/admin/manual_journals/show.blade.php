@extends('admin.layouts.app')

@section('title', 'Detail Jurnal')

@section('content')

    <div class="max-w-5xl mx-auto">
        
        {{-- Navigation --}}
        <div class="flex items-center justify-between mb-6">
            <div>
                <h1 class="page-title">Detail Jurnal: {{ $manualJournal->journal_number }}</h1>
                <p class="page-subtitle">Informasi lengkap transaksi jurnal.</p>
            </div>
            <div class="flex gap-3">
                <a href="{{ route('admin.manual-journals.index') }}" class="btn btn-secondary">
                    <i class="material-icons text-sm mr-1">arrow_back</i> Kembali
                </a>
                <a href="{{ route('admin.manual-journals.edit', $manualJournal->journal_id) }}" class="btn btn-primary">
                    <i class="material-icons text-sm mr-1">edit</i> Edit Jurnal
                </a>
            </div>
        </div>

        {{-- INFO CARD --}}
        <div class="card mb-6">
            <div class="card-body grid grid-cols-1 md:grid-cols-3 gap-6">
                <div>
                    <label class="text-xs font-bold text-slate-400 uppercase">Tanggal</label>
                    <div class="font-medium text-slate-700 dark:text-slate-200 text-lg">
                        {{ $manualJournal->entry_date->format('d F Y') }}
                    </div>
                </div>
                <div>
                    <label class="text-xs font-bold text-slate-400 uppercase">Deskripsi</label>
                    <div class="font-medium text-slate-700 dark:text-slate-200">
                        {{ $manualJournal->description }}
                    </div>
                </div>
                <div>
                    <label class="text-xs font-bold text-slate-400 uppercase">Dibuat Oleh</label>
                    <div class="flex items-center gap-2 mt-1">
                        <div class="w-6 h-6 rounded-full bg-slate-100 text-slate-600 flex items-center justify-center text-[10px] font-bold">
                            {{ substr($manualJournal->user->full_name ?? 'S', 0, 1) }}
                        </div>
                        <span class="text-sm text-slate-700 dark:text-slate-300">
                            {{ $manualJournal->user->full_name ?? 'System' }}
                        </span>
                    </div>
                </div>
            </div>
        </div>

        {{-- ENTRIES TABLE --}}
        <div class="card">
            <div class="card-header">
                <h3 class="card-header-title">Rincian Akun</h3>
            </div>
            <div class="table-container">
                <table class="table-modern">
                    <thead>
                        <tr>
                            <th>Akun</th>
                            <th>Keterangan</th>
                            <th class="text-right">Debit</th>
                            <th class="text-right">Kredit</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($manualJournal->entries as $entry)
                            <tr class="hover:bg-slate-50 dark:hover:bg-slate-800/50">
                                <td>
                                    <div class="font-bold text-slate-700 dark:text-slate-200">
                                        {{ $entry->account->account_name ?? 'Akun Terhapus' }}
                                    </div>
                                    <div class="text-xs text-slate-500 font-mono">
                                        {{ $entry->account->account_number ?? '-' }}
                                    </div>
                                </td>
                                <td class="text-sm text-slate-600 dark:text-slate-400 italic">
                                    {{ $entry->description ?: '-' }}
                                </td>
                                <td class="text-right font-mono text-slate-700 dark:text-slate-300">
                                    @if($entry->debit > 0)
                                        Rp {{ number_format($entry->debit, 0, ',', '.') }}
                                    @else
                                        -
                                    @endif
                                </td>
                                <td class="text-right font-mono text-slate-700 dark:text-slate-300">
                                    @if($entry->credit > 0)
                                        Rp {{ number_format($entry->credit, 0, ',', '.') }}
                                    @else
                                        -
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                    <tfoot class="bg-slate-50 dark:bg-slate-800 border-t border-slate-200 dark:border-slate-700">
                        <tr>
                            <td colspan="2" class="px-6 py-4 text-right font-bold text-slate-500 uppercase text-xs tracking-wider">Total</td>
                            <td class="px-6 py-4 text-right font-bold text-slate-800 dark:text-white border-t-2 border-slate-300">
                                Rp {{ number_format($manualJournal->total_debit, 0, ',', '.') }}
                            </td>
                            <td class="px-6 py-4 text-right font-bold text-slate-800 dark:text-white border-t-2 border-slate-300">
                                Rp {{ number_format($manualJournal->total_credit, 0, ',', '.') }}
                            </td>
                        </tr>
                    </tfoot>
                </table>
            </div>
        </div>

    </div>

@endsection
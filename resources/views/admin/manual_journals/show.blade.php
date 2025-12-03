@extends('admin.layouts.app')

@section('title', 'Detail Jurnal Umum')

@section('content')
<div class="max-w-4xl mx-auto pb-20 animate-enter">
    
    {{-- HEADER --}}
    <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center mb-8 gap-4">
        <div>
            <nav class="flex items-center gap-2 text-sm text-slate-500 mb-1">
                <a href="{{ route('admin.manual-journals.index') }}" class="hover:text-indigo-600 transition-colors">Jurnal Umum</a>
                <span class="mx-2 text-slate-300">/</span>
                <span class="text-slate-800 font-semibold">Detail</span>
            </nav>
            <h1 class="text-2xl font-bold text-slate-800 tracking-tight flex items-center gap-3">
                No. Jurnal: <span class="font-mono text-indigo-600 bg-indigo-50 px-2 rounded">{{ $manualJournal->journal_number }}</span>
            </h1>
        </div>
        <div class="flex gap-2 w-full sm:w-auto">
            <a href="{{ route('admin.manual-journals.index') }}" class="h-[48px] px-6 bg-white border border-slate-300 text-slate-600 rounded-lg text-sm font-bold hover:bg-slate-50 transition-all flex items-center justify-center gap-2 shadow-sm">
                <i class="material-icons text-[18px]">arrow_back</i> Kembali
            </a>
            <a href="{{ route('admin.manual-journals.edit', $manualJournal) }}" class="h-[48px] px-6 bg-amber-50 border border-amber-200 text-amber-700 rounded-lg text-sm font-bold hover:bg-amber-100 transition-all flex items-center justify-center gap-2 shadow-sm">
                <i class="material-icons text-[18px]">edit</i> Edit
            </a>
        </div>
    </div>

    {{-- INFO UTAMA --}}
    <div class="dashboard-card p-0 overflow-hidden shadow-lg border-0 ring-1 ring-slate-900/5 mb-8">
        <div class="p-6 border-b border-slate-100 bg-slate-50/50 grid grid-cols-1 md:grid-cols-2 gap-6">
            <div>
                <label class="text-xs font-bold text-slate-400 uppercase block mb-1">Tanggal Transaksi</label>
                <div class="flex items-center gap-2 font-bold text-slate-800">
                    <i class="material-icons text-slate-400 text-[18px]">event</i>
                    {{ $manualJournal->entry_date->format('d F Y') }}
                </div>
            </div>
            <div>
                <label class="text-xs font-bold text-slate-400 uppercase block mb-1">Dibuat Oleh</label>
                <div class="flex items-center gap-2 font-medium text-slate-800">
                    <i class="material-icons text-slate-400 text-[18px]">person</i>
                    {{ $manualJournal->user->name ?? 'Sistem' }}
                </div>
            </div>
            <div class="md:col-span-2">
                <label class="text-xs font-bold text-slate-400 uppercase block mb-2">Deskripsi / Memo</label>
                <div class="bg-white p-4 rounded-lg border border-slate-200 text-sm text-slate-700 italic shadow-sm">
                    "{{ $manualJournal->description }}"
                </div>
            </div>
        </div>

        {{-- TABEL RINCIAN --}}
        <div class="border-t border-slate-200">
            <div class="px-6 py-3 bg-slate-50 border-b border-slate-200 flex items-center gap-2">
                <i class="material-icons text-indigo-500 text-[18px]">toc</i>
                <h5 class="text-xs font-bold text-slate-600 uppercase tracking-wider">Rincian Akun</h5>
            </div>
            <div class="overflow-x-auto">
                <table class="dashboard-table min-w-full">
                    <thead class="bg-slate-50 border-b border-slate-200">
                        <tr>
                            <th class="pl-6 w-24">Kode</th>
                            <th>Nama Akun</th>
                            <th>Deskripsi Baris</th>
                            <th class="text-right w-32">Debit</th>
                            <th class="text-right w-32 pr-6">Kredit</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 bg-white">
                        @foreach ($manualJournal->entries as $entry)
                        <tr class="hover:bg-slate-50 transition-colors">
                            <td class="pl-6 py-3 text-sm font-mono font-bold text-indigo-600">{{ $entry->account->account_number ?? '-' }}</td>
                            <td class="py-3 text-sm font-bold text-slate-800">{{ $entry->account->account_name ?? '-' }}</td>
                            <td class="py-3 text-xs text-slate-500 italic">{{ $entry->description ?? '-' }}</td>
                            <td class="py-3 text-right text-sm font-mono {{ $entry->debit > 0 ? 'text-slate-900 font-bold' : 'text-slate-300' }}">
                                {{ $entry->debit > 0 ? 'Rp '.number_format($entry->debit, 0, ',', '.') : '-' }}
                            </td>
                            <td class="pr-6 py-3 text-right text-sm font-mono {{ $entry->credit > 0 ? 'text-slate-900 font-bold' : 'text-slate-300' }}">
                                {{ $entry->credit > 0 ? 'Rp '.number_format($entry->credit, 0, ',', '.') : '-' }}
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                    <tfoot class="bg-slate-50 border-t-2 border-slate-200">
                        <tr>
                            <td colspan="3" class="pl-6 py-4 text-right text-xs font-bold text-slate-500 uppercase tracking-wider">TOTAL</td>
                            <td class="py-4 text-right text-base font-bold text-indigo-700 font-mono">
                                Rp {{ number_format($manualJournal->total_debit, 0, ',', '.') }}
                            </td>
                            <td class="pr-6 py-4 text-right text-base font-bold text-indigo-700 font-mono">
                                Rp {{ number_format($manualJournal->total_credit, 0, ',', '.') }}
                            </td>
                        </tr>
                    </tfoot>
                </table>
            </div>
        </div>
    </div>
</div>
@endsection
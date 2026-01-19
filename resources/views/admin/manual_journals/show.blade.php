@extends('admin.layouts.app')

@section('title', 'Detail Jurnal ' . $manualJournal->journal_number)

@section('content')
<div class="flex flex-col gap-6">
    
    {{-- Header Navigation --}}
    <div class="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4">
        <div>
            <a href="{{ route('admin.manual-journals.index') }}" class="flex items-center text-sm text-slate-500 hover:text-indigo-600 transition-colors mb-2">
                <i class="material-icons text-[16px] mr-1">arrow_back</i> Kembali ke Daftar
            </a>
            <div class="flex items-center gap-3">
                <h1 class="page-title">{{ $manualJournal->journal_number }}</h1>
                <span class="badge badge-primary">Manual Journal</span>
            </div>
        </div>
        <div class="flex items-center gap-2">
            {{-- Edit Button --}}
            <a href="{{ route('admin.manual-journals.edit', $manualJournal->journal_id) }}" class="btn btn-secondary">
                <i class="material-icons text-[18px] mr-1">edit</i> Edit
            </a>
            
            {{-- Delete Button --}}
            <form action="{{ route('admin.manual-journals.destroy', $manualJournal->journal_id) }}" method="POST">
                @csrf
                @method('DELETE')
                <button type="submit" class="btn btn-danger" 
                        data-confirm-delete="true" 
                        data-message="Menghapus jurnal ini akan menghapus entri buku besar secara permanen.">
                    <i class="material-icons text-[18px] mr-1">delete_outline</i> Hapus
                </button>
            </form>
        </div>
    </div>

    {{-- Detail Card --}}
    <div class="card p-6">
        <div class="grid grid-cols-1 md:grid-cols-4 gap-6">
            <div>
                <span class="text-xs font-bold text-slate-400 uppercase tracking-wider">Tanggal Transaksi</span>
                <p class="text-lg font-semibold text-slate-800 dark:text-slate-200 mt-1">
                    {{ \Carbon\Carbon::parse($manualJournal->entry_date)->translatedFormat('d F Y') }}
                </p>
            </div>
            <div class="md:col-span-2">
                <span class="text-xs font-bold text-slate-400 uppercase tracking-wider">Deskripsi</span>
                <p class="text-base text-slate-700 dark:text-slate-300 mt-1 leading-relaxed">
                    {{ $manualJournal->description }}
                </p>
            </div>
            <div class="text-right">
                <span class="text-xs font-bold text-slate-400 uppercase tracking-wider">Dibuat Oleh</span>
                <div class="flex items-center justify-end gap-2 mt-2">
                    <div class="w-8 h-8 rounded-full bg-indigo-100 text-indigo-600 flex items-center justify-center text-xs font-bold">
                        {{ substr($manualJournal->user->full_name ?? 'S', 0, 1) }}
                    </div>
                    <span class="text-sm font-medium">{{ $manualJournal->user->full_name ?? 'System' }}</span>
                </div>
            </div>
        </div>
    </div>

    {{-- Entries Table --}}
    <div class="card card-plain">
        <div class="table-container">
            <table class="table-modern">
                <thead>
                    <tr>
                        <th>Kode Akun</th>
                        <th>Nama Akun</th>
                        <th>Deskripsi Baris</th>
                        <th class="text-right">Debit</th>
                        <th class="text-right">Kredit</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 dark:divide-slate-700">
                    @foreach($manualJournal->entries as $entry)
                        <tr>
                            <td class="font-mono text-indigo-600 font-medium">
                                {{ $entry->account->account_number }}
                            </td>
                            <td class="font-medium text-slate-700 dark:text-slate-200">
                                {{ $entry->account->account_name }}
                            </td>
                            <td class="text-slate-500 italic">
                                {{ $entry->description ?? '-' }}
                            </td>
                            <td class="text-right font-mono {{ $entry->debit > 0 ? 'font-bold text-slate-800 dark:text-white' : 'text-slate-300' }}">
                                {{ $entry->debit > 0 ? number_format($entry->debit, 2, ',', '.') : '-' }}
                            </td>
                            <td class="text-right font-mono {{ $entry->credit > 0 ? 'font-bold text-slate-800 dark:text-white' : 'text-slate-300' }}">
                                {{ $entry->credit > 0 ? number_format($entry->credit, 2, ',', '.') : '-' }}
                            </td>
                        </tr>
                    @endforeach
                </tbody>
                <tfoot class="bg-slate-50 dark:bg-slate-800/50 border-t-2 border-slate-200 dark:border-slate-700 font-bold text-sm">
                    <tr>
                        <td colspan="3" class="px-6 py-4 text-right uppercase tracking-wider text-slate-500">Total</td>
                        <td class="px-6 py-4 text-right font-mono text-base text-emerald-600 dark:text-emerald-400">
                            {{ number_format($manualJournal->total_debit, 2, ',', '.') }}
                        </td>
                        <td class="px-6 py-4 text-right font-mono text-base text-emerald-600 dark:text-emerald-400">
                            {{ number_format($manualJournal->total_credit, 2, ',', '.') }}
                        </td>
                    </tr>
                </tfoot>
            </table>
        </div>
    </div>
</div>
@endsection
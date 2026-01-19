@extends('admin.layouts.app')

@section('title', 'Jurnal Umum Manual')

@section('content')
    <div class="flex flex-col gap-6">
        
        {{-- Page Header --}}
        <div class="page-header">
            <div>
                <h1 class="page-title">Jurnal Umum Manual</h1>
                <p class="page-subtitle">Kelola entri jurnal manual (non-otomatis) untuk penyesuaian akuntansi.</p>
            </div>
            <div class="flex flex-col sm:flex-row gap-3">
                <a href="{{ route('admin.manual-journals.create') }}" class="btn btn-primary">
                    <i class="material-icons text-[18px] mr-1">add</i> Buat Jurnal Baru
                </a>
            </div>
        </div>

        {{-- Filter & Search Card --}}
        <div class="card p-5">
            <form method="GET" action="{{ route('admin.manual-journals.index') }}">
                <div class="flex flex-col md:flex-row gap-4 items-end">
                    <div class="w-full md:w-1/3 relative">
                        <label class="form-label">Pencarian</label>
                        <div class="relative">
                            <input type="text" name="search" value="{{ request('search') }}" 
                                   class="form-input pl-10" 
                                   placeholder="Cari No. Jurnal atau Deskripsi...">
                            <span class="absolute left-3 top-2.5 text-slate-400">
                                <i class="material-icons text-[20px]">search</i>
                            </span>
                        </div>
                    </div>
                    <div class="w-full md:w-auto">
                        <button type="submit" class="btn btn-secondary w-full md:w-auto">
                            Filter Data
                        </button>
                    </div>
                </div>
            </form>
        </div>

        {{-- Table Card --}}
        <div class="card card-plain">
            <div class="table-container">
                <table class="table-modern">
                    <thead>
                        <tr>
                            <th>No. Jurnal</th>
                            <th>Tanggal</th>
                            <th>Deskripsi</th>
                            <th class="text-right">Total Debit (Rp)</th>
                            <th class="text-right">Total Kredit (Rp)</th>
                            <th>Dibuat Oleh</th>
                            <th class="text-right">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($manualJournals as $journal)
                            <tr>
                                <td class="font-medium text-slate-700 dark:text-slate-200">
                                    <a href="{{ route('admin.manual-journals.show', $journal->journal_id) }}" class="hover:text-indigo-600 hover:underline transition-colors">
                                        {{ $journal->journal_number }}
                                    </a>
                                </td>
                                <td>{{ \Carbon\Carbon::parse($journal->entry_date)->format('d M Y') }}</td>
                                <td class="max-w-xs truncate" title="{{ $journal->description }}">
                                    {{ $journal->description }}
                                </td>
                                <td class="text-right font-mono font-medium text-slate-600 dark:text-slate-300">
                                    {{ number_format($journal->total_debit, 0, ',', '.') }}
                                </td>
                                <td class="text-right font-mono font-medium text-slate-600 dark:text-slate-300">
                                    {{ number_format($journal->total_credit, 0, ',', '.') }}
                                </td>
                                <td>
                                    <div class="flex items-center gap-2">
                                        <div class="w-6 h-6 rounded-full bg-slate-200 dark:bg-slate-700 flex items-center justify-center text-[10px] font-bold">
                                            {{ substr($journal->user->full_name ?? 'Sys', 0, 1) }}
                                        </div>
                                        <span class="text-xs">{{ $journal->user->full_name ?? 'System' }}</span>
                                    </div>
                                </td>
                                <td>
                                    <div class="flex items-center justify-end gap-2">
                                        <x-ui.table-actions 
                                            view="{{ route('admin.manual-journals.show', $journal->journal_id) }}"
                                            edit="{{ route('admin.manual-journals.edit', $journal->journal_id) }}"
                                            delete="{{ route('admin.manual-journals.destroy', $journal->journal_id) }}"
                                            message="Menghapus jurnal ini akan menghapus semua entri buku besar terkait secara permanen."
                                        />
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="text-center py-12">
                                    <div class="flex flex-col items-center justify-center">
                                        <div class="w-16 h-16 bg-slate-100 dark:bg-slate-800 rounded-full flex items-center justify-center mb-4 text-slate-400">
                                            <i class="material-icons text-3xl">receipt_long</i>
                                        </div>
                                        <p class="text-slate-500 font-medium">Belum ada jurnal manual yang dibuat.</p>
                                        <a href="{{ route('admin.manual-journals.create') }}" class="text-indigo-600 hover:underline text-sm mt-2">Buat Jurnal Baru</a>
                                    </div>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            
            {{-- Pagination --}}
            <div class="px-6 py-4 border-t border-slate-200 dark:border-slate-700">
                {{ $manualJournals->links('vendor.pagination.admin') }}
            </div>
        </div>
    </div>
@endsection
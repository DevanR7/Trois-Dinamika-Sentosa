@extends('admin.layouts.app')

@section('title', 'Jurnal Umum Manual')

@section('content')

    {{-- HEADER & ACTIONS --}}
    <div class="page-header">
        <div>
            <h1 class="page-title">Jurnal Umum Manual</h1>
            <p class="page-subtitle">Input transaksi jurnal penyesuaian atau koreksi secara manual.</p>
        </div>
        <div class="flex items-center gap-3">
            <a href="{{ route('admin.manual-journals.create') }}" class="btn btn-primary">
                <i class="material-icons text-sm mr-1">post_add</i> Buat Jurnal
            </a>
        </div>
    </div>

    {{-- FILTER & SEARCH --}}
    <div class="card mb-6">
        <div class="card-body">
            <form action="{{ route('admin.manual-journals.index') }}" method="GET" class="grid grid-cols-1 md:grid-cols-12 gap-4">
                
                {{-- Search Bar --}}
                <div class="md:col-span-12">
                    <div class="input-group">
                        <span class="input-group-text bg-white dark:bg-slate-800">
                            <i class="material-icons text-slate-400">search</i>
                        </span>
                        <input type="text" name="search" class="form-input border-l-0 pl-0" 
                               placeholder="Cari nomor jurnal atau deskripsi..." 
                               value="{{ request('search') }}">
                        <button type="submit" class="btn btn-secondary rounded-l-none border-l-0">
                            Cari
                        </button>
                    </div>
                </div>

            </form>
        </div>
    </div>

    {{-- TABLE DATA --}}
    <div class="card">
        <div class="table-container">
            <table class="table-modern">
                <thead>
                    <tr>
                        <th>Tanggal & Nomor</th>
                        <th>Deskripsi</th>
                        <th class="text-right">Total Debit</th>
                        <th class="text-right">Total Kredit</th>
                        <th>Dibuat Oleh</th>
                        <th class="text-center w-36">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($manualJournals as $journal)
                        <tr>
                            <td>
                                <div class="font-bold text-slate-700 dark:text-slate-200">
                                    {{ $journal->entry_date->format('d M Y') }}
                                </div>
                                <div class="text-xs text-slate-500 font-mono">
                                    {{ $journal->journal_number }}
                                </div>
                            </td>
                            <td class="text-sm text-slate-600 dark:text-slate-300 max-w-xs truncate">
                                {{ $journal->description }}
                            </td>
                            <td class="text-right font-mono text-slate-700 dark:text-slate-300">
                                Rp {{ number_format($journal->total_debit, 0, ',', '.') }}
                            </td>
                            <td class="text-right font-mono text-slate-700 dark:text-slate-300">
                                Rp {{ number_format($journal->total_credit, 0, ',', '.') }}
                            </td>
                            <td>
                                <div class="flex items-center gap-2">
                                    <div class="w-6 h-6 rounded-full bg-slate-100 text-slate-500 flex items-center justify-center text-[10px] font-bold">
                                        {{ substr($journal->user->full_name ?? 'S', 0, 1) }}
                                    </div>
                                    <span class="text-xs">{{ Str::limit($journal->user->full_name ?? 'System', 15) }}</span>
                                </div>
                            </td>
                            <td class="text-center">
                                <div class="flex items-center justify-center gap-2">
                                    
                                    {{-- Detail --}}
                                    <a href="{{ route('admin.manual-journals.show', $journal->journal_id) }}" 
                                       class="w-9 h-9 rounded-full flex items-center justify-center bg-white border border-slate-200 text-slate-500 hover:text-indigo-600 hover:border-indigo-300 transition-colors shadow-sm dark:bg-slate-800 dark:border-slate-700 dark:text-slate-400"
                                       title="Detail">
                                        <i class="material-icons text-[18px] leading-none">visibility</i>
                                    </a>

                                    {{-- Edit --}}
                                    <a href="{{ route('admin.manual-journals.edit', $journal->journal_id) }}" 
                                       class="w-9 h-9 rounded-full flex items-center justify-center bg-indigo-50 border border-transparent text-indigo-600 hover:bg-indigo-600 hover:text-white transition-colors shadow-sm dark:bg-indigo-900/30 dark:text-indigo-400"
                                       title="Edit">
                                        <i class="material-icons text-[18px] leading-none">edit</i>
                                    </a>

                                    {{-- Delete --}}
                                    <button type="button" onclick="confirmDelete('{{ $journal->journal_id }}', '{{ $journal->journal_number }}')" 
                                            class="w-9 h-9 rounded-full flex items-center justify-center bg-rose-50 border border-transparent text-rose-600 hover:bg-rose-600 hover:text-white transition-colors shadow-sm dark:bg-rose-900/30 dark:text-rose-400"
                                            title="Hapus">
                                        <i class="material-icons text-[18px] leading-none">delete</i>
                                    </button>
                                    
                                    <form id="delete-form-{{ $journal->journal_id }}" 
                                          action="{{ route('admin.manual-journals.destroy', $journal->journal_id) }}" 
                                          method="POST" class="hidden">
                                        @csrf
                                        @method('DELETE')
                                    </form>

                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="text-center p-8">
                                <div class="flex flex-col items-center justify-center text-slate-400">
                                    <i class="material-icons text-5xl mb-2">library_books</i>
                                    <span>Belum ada jurnal manual.</span>
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        
        <div class="card-footer">
            {{ $manualJournals->links() }}
        </div>
    </div>

@endsection

@push('scripts')
<script>
    function confirmDelete(id, number) {
        window.confirmDialog({
            title: 'Hapus Jurnal?',
            text: "Jurnal #" + number + " akan dihapus permanen beserta postingan buku besarnya.",
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#ef4444',
            cancelButtonColor: '#64748b',
            confirmButtonText: 'Ya, Hapus!',
            cancelButtonText: 'Batal'
        }).then((result) => {
            if (result.isConfirmed) {
                document.getElementById('delete-form-' + id).submit();
            }
        });
    }
</script>
@endpush
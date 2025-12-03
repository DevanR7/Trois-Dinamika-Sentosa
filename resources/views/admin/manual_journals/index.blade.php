@extends('admin.layouts.app')

@section('title', 'Jurnal Umum')

@section('content')
<div class="max-w-7xl mx-auto pb-20 animate-enter">
    
    {{-- HEADER --}}
    <div class="flex flex-col sm:flex-row justify-between items-end gap-4 mb-8">
        <div>
            <h1 class="text-2xl font-bold text-slate-800 tracking-tight">Jurnal Umum Manual</h1>
            <p class="text-slate-500 text-sm mt-1">Kelola transaksi jurnal manual non-otomatis.</p>
        </div>
        <a href="{{ route('admin.manual-journals.create') }}" class="h-[48px] px-8 bg-indigo-600 hover:bg-indigo-700 text-white text-sm font-bold rounded-lg shadow-md transition-all flex items-center justify-center gap-2 group hover:-translate-y-0.5">
            <i class="material-icons text-[20px] group-hover:rotate-90 transition-transform">add</i> 
            <span>Buat Jurnal</span>
        </a>
    </div>

    {{-- FILTER --}}
    <div class="dashboard-card p-6 mb-6 shadow-sm">
        <form action="{{ route('admin.manual-journals.index') }}" method="GET">
            <div class="flex flex-col md:flex-row gap-4 items-end">
                <div class="flex-grow w-full">
                    <label class="block text-xs font-bold text-slate-500 uppercase mb-1.5">Pencarian</label>
                    <div class="relative">
                        <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                            <i class="material-icons text-slate-400 text-[20px]">search</i>
                        </div>
                        <input type="text" name="search" value="{{ request('search') }}" 
                            class="form-input pl-10" 
                            placeholder="Cari Nomor Jurnal atau Deskripsi...">
                    </div>
                </div>
                <div class="w-full md:w-auto">
                    <button type="submit" class="w-full md:w-auto h-[48px] px-6 bg-slate-800 hover:bg-slate-900 text-white rounded-lg font-bold text-sm shadow-sm transition flex items-center justify-center gap-2">
                        <i class="material-icons text-[18px]">filter_list</i> Filter
                    </button>
                </div>
            </div>
        </form>
    </div>

    {{-- TABEL --}}
    <div class="dashboard-card p-0 overflow-hidden shadow-lg border-0 ring-1 ring-slate-900/5">
        <div class="overflow-x-auto">
            <table class="dashboard-table min-w-full">
                <thead class="bg-slate-50 border-b border-slate-200">
                    <tr>
                        <th class="pl-6 w-32">Tanggal</th>
                        <th>No. Jurnal</th>
                        <th>Deskripsi</th>
                        <th class="text-right">Total Nilai</th>
                        <th>User</th>
                        <th class="text-center w-32 pr-6">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 bg-white">
                    @forelse ($manualJournals as $journal)
                        <tr class="hover:bg-slate-50/50 transition-colors group">
                            <td class="pl-6 py-4 text-sm text-slate-600 whitespace-nowrap">
                                {{ $journal->entry_date->format('d/m/Y') }}
                            </td>
                            <td class="py-4">
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded text-xs font-bold bg-indigo-50 text-indigo-700 border border-indigo-100 font-mono">
                                    {{ $journal->journal_number }}
                                </span>
                            </td>
                            <td class="py-4 text-sm text-slate-600 italic">
                                <span class="line-clamp-1" title="{{ $journal->description }}">
                                    {{ Str::limit($journal->description, 50) }}
                                </span>
                            </td>
                            <td class="py-4 text-right text-sm font-bold text-slate-800 font-mono">
                                Rp {{ number_format($journal->total_debit, 0, ',', '.') }}
                            </td>
                            <td class="py-4 text-xs text-slate-500">
                                {{ $journal->user->name ?? 'System' }}
                            </td>
                            <td class="pr-6 py-4 text-center">
                                <div class="flex justify-center gap-2">
                                    <a href="{{ route('admin.manual-journals.show', $journal) }}" class="w-8 h-8 flex items-center justify-center rounded-lg bg-white border border-slate-200 text-indigo-600 hover:bg-indigo-50 hover:border-indigo-200 transition shadow-sm" title="Detail">
                                        <i class="material-icons text-[16px]">visibility</i>
                                    </a>
                                    
                                    <a href="{{ route('admin.manual-journals.edit', $journal) }}" class="w-8 h-8 flex items-center justify-center rounded-lg bg-white border border-slate-200 text-amber-600 hover:bg-amber-50 hover:border-amber-200 transition shadow-sm" title="Edit">
                                        <i class="material-icons text-[16px]">edit</i>
                                    </a>
                                    
                                    <form action="{{ route('admin.manual-journals.destroy', $journal) }}" method="POST" class="delete-form inline-block">
                                        @csrf @method('DELETE')
                                        <button type="submit" 
                                                data-title="Hapus Jurnal?" 
                                                data-text="Tindakan ini akan membuat <b>Jurnal Pembalik (Reversal)</b> secara otomatis."
                                                class="w-8 h-8 flex items-center justify-center rounded-lg bg-white border border-slate-200 text-red-600 hover:bg-red-50 hover:border-red-200 transition shadow-sm" title="Hapus">
                                            <i class="material-icons text-[16px]">delete</i>
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="px-6 py-16 text-center">
                                <div class="flex flex-col items-center justify-center text-slate-400">
                                    <div class="w-16 h-16 bg-slate-50 rounded-full flex items-center justify-center mb-4 text-slate-300">
                                        <i class="material-icons text-4xl">content_paste_off</i>
                                    </div>
                                    <h3 class="text-base font-bold text-slate-700">Belum ada jurnal</h3>
                                    <p class="text-sm mt-1">Silakan buat jurnal manual baru.</p>
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        
        @if($manualJournals->hasPages())
            <div class="px-6 py-4 border-t border-slate-200 bg-slate-50/80">
                {{ $manualJournals->links() }}
            </div>
        @endif
    </div>
</div>
@endsection

@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function() {
        @if(session('success')) window.showToast("{{ session('success') }}", 'success'); @endif
        @if(session('error')) window.showToast("{{ session('error') }}", 'error'); @endif
    });
</script>
@endpush
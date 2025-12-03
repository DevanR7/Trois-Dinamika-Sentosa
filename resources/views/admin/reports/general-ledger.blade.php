@extends('admin.layouts.app')

@section('title', 'Laporan Jurnal Umum')

@push('styles')
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/select2-bootstrap-5-theme@1.3.0/dist/select2-bootstrap-5-theme.min.css" />
@endpush

@section('content')
<div class="max-w-7xl mx-auto pb-20 animate-enter">
    
    {{-- HEADER --}}
    <div class="flex flex-col sm:flex-row justify-between items-end gap-4 mb-8">
        <div>
            <h1 class="text-2xl font-bold text-slate-800 tracking-tight">Jurnal Umum</h1>
            <p class="text-slate-500 text-sm mt-1">Rekapitulasi transaksi keuangan (General Ledger).</p>
        </div>
        <button class="h-[48px] px-6 bg-white border border-slate-300 text-slate-700 rounded-lg text-sm font-bold hover:bg-slate-50 shadow-sm transition-all flex items-center justify-center gap-2 no-print" onclick="window.print()">
            <i class="material-icons text-[18px]">print</i> Cetak
        </button>
    </div>

    {{-- FILTER FORM --}}
    <div class="dashboard-card p-6 mb-6">
        <form action="{{ route('admin.reports.general-ledger') }}" method="GET">
            <div class="grid grid-cols-1 md:grid-cols-12 gap-4 items-end">
                
                <div class="md:col-span-3">
                    <label for="start_date" class="block text-xs font-bold text-slate-500 uppercase mb-1.5">Dari Tanggal</label>
                    <input type="date" name="start_date" id="start_date" class="form-input" value="{{ request('start_date', now()->startOfMonth()->toDateString()) }}">
                </div>
                
                <div class="md:col-span-3">
                    <label for="end_date" class="block text-xs font-bold text-slate-500 uppercase mb-1.5">Sampai Tanggal</label>
                    <input type="date" name="end_date" id="end_date" class="form-input" value="{{ request('end_date', now()->endOfMonth()->toDateString()) }}">
                </div>
                
                <div class="md:col-span-3">
                    <label for="account_id" class="block text-xs font-bold text-slate-500 uppercase mb-1.5">Filter Akun</label>
                    <select name="account_id" id="account_id" class="form-input select2-basic">
                        <option value="">-- Semua Akun --</option>
                        @foreach ($accounts as $account)
                            <option value="{{ $account->account_id }}" {{ request('account_id') == $account->account_id ? 'selected' : '' }}>
                                {{ $account->account_number }} - {{ $account->account_name }}
                            </option>
                        @endforeach
                    </select>
                </div>
                
                <div class="md:col-span-2">
                    <label for="journal_group_id" class="block text-xs font-bold text-slate-500 uppercase mb-1.5">Ref / Grup</label>
                    <input type="text" name="journal_group_id" id="journal_group_id" class="form-input" value="{{ request('journal_group_id') }}" placeholder="Contoh: INV-2023...">
                </div>
                
                <div class="md:col-span-1">
                    <button type="submit" class="w-full h-[48px] bg-slate-800 hover:bg-slate-900 text-white rounded-lg shadow-sm transition-all flex items-center justify-center gap-2" title="Filter">
                        <i class="material-icons text-[18px]">filter_list</i>
                    </button>
                </div>

            </div>
        </form>
    </div>

    {{-- TABEL JURNAL --}}
    <div class="dashboard-card p-0 overflow-hidden shadow-lg border-0 ring-1 ring-slate-900/5">
        <div class="overflow-x-auto">
            <table class="dashboard-table min-w-full">
                <thead class="bg-slate-50 border-b border-slate-200">
                    <tr>
                        <th class="pl-6 w-24">Tanggal</th>
                        <th>No. Grup</th>
                        <th>Referensi</th>
                        <th>Akun</th>
                        <th>Deskripsi</th>
                        <th class="text-right w-32">Debit</th>
                        <th class="text-right w-32">Kredit</th>
                        <th class="text-center pr-6 w-20">User</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-slate-100">
                    @php
                        $currentGroup = null;
                        $totalDebit = 0;
                        $totalCredit = 0;
                    @endphp
                    
                    @forelse ($journalEntries as $entry)
                        @php
                            $totalDebit += $entry->debit;
                            $totalCredit += $entry->credit;
                            $isNewGroup = $currentGroup !== $entry->journal_group_id;
                            $currentGroup = $entry->journal_group_id;
                        @endphp
                        
                        <tr class="hover:bg-slate-50/50 transition-colors {{ $isNewGroup ? 'border-t-4 border-slate-100' : '' }}">
                            <td class="pl-6 py-3 text-xs text-slate-500 align-top">
                                {{ $isNewGroup ? $entry->entry_date->format('d/m/Y') : '' }}
                            </td>
                            <td class="py-3 align-top">
                                @if($isNewGroup)
                                    <span class="text-xs font-mono font-bold text-indigo-600 bg-indigo-50 px-2 py-0.5 rounded border border-indigo-100">
                                        {{ $entry->journal_group_id }}
                                    </span>
                                @endif
                            </td>
                            <td class="py-3 text-xs text-slate-500 align-top">
                                @if ($entry->reference && $isNewGroup)
                                    <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded bg-slate-100 text-slate-700 border border-slate-200 font-mono">
                                        <i class="material-icons text-[10px] text-slate-400">link</i>
                                        {{ Str::limit(Str::afterLast($entry->reference_type, '\\') . ' #' . $entry->reference_id, 20) }}
                                    </span>
                                @elseif($isNewGroup)
                                    <span class="text-slate-300">-</span>
                                @endif
                            </td>
                            <td class="py-3 align-top">
                                <div class="text-xs font-bold text-slate-800">{{ $entry->account->account_number ?? '-' }}</div>
                                <div class="text-xs text-slate-600">{{ $entry->account->account_name ?? '-' }}</div>
                            </td>
                            <td class="py-3 text-xs text-slate-600 max-w-xs align-top leading-relaxed">
                                {{ $entry->description }}
                            </td>
                            <td class="py-3 text-right text-xs font-mono font-medium {{ $entry->debit > 0 ? 'text-slate-800' : 'text-slate-300' }} align-top">
                                {{ $entry->debit > 0 ? number_format($entry->debit, 0, ',', '.') : '-' }}
                            </td>
                            <td class="py-3 text-right text-xs font-mono font-medium {{ $entry->credit > 0 ? 'text-slate-800' : 'text-slate-300' }} align-top">
                                {{ $entry->credit > 0 ? number_format($entry->credit, 0, ',', '.') : '-' }}
                            </td>
                            <td class="pr-6 py-3 text-center text-[10px] text-slate-400 align-top">
                                {{ $isNewGroup ? ($entry->user->username ?? 'Sys') : '' }}
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="px-6 py-12 text-center">
                                <div class="flex flex-col items-center justify-center text-slate-400">
                                    <div class="w-16 h-16 bg-slate-50 rounded-full flex items-center justify-center mb-4">
                                        <i class="material-icons text-4xl opacity-30">receipt_long</i>
                                    </div>
                                    <h3 class="text-sm font-bold text-slate-600">Tidak Ada Data</h3>
                                    <p class="text-xs mt-1">Tidak ditemukan jurnal untuk periode ini.</p>
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
                
                @if($journalEntries->isNotEmpty())
                <tfoot class="bg-slate-50 border-t border-slate-200">
                    <tr>
                        <td colspan="5" class="pl-6 py-3 text-right text-xs font-bold text-slate-500 uppercase tracking-wider">
                            Total Halaman Ini
                        </td>
                        <td class="py-3 text-right text-sm font-bold text-indigo-700 font-mono border-t-2 border-indigo-200 bg-indigo-50/30">
                            {{ number_format($totalDebit, 0, ',', '.') }}
                        </td>
                        <td class="py-3 text-right text-sm font-bold text-indigo-700 font-mono border-t-2 border-indigo-200 bg-indigo-50/30">
                            {{ number_format($totalCredit, 0, ',', '.') }}
                        </td>
                        <td></td>
                    </tr>
                </tfoot>
                @endif
            </table>
        </div>
    </div>
    
    @if(isset($journalEntries) && $journalEntries->hasPages())
        <div class="mt-6 px-6">
            {{ $journalEntries->links() }}
        </div>
    @endif
</div>
@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
<script>
    document.addEventListener('DOMContentLoaded', function() {
        $('#account_id').select2({
            theme: 'bootstrap-5',
            width: '100%',
            placeholder: '-- Semua Akun --',
            allowClear: true
        });
        
        @if(session('success')) window.showToast("{{ session('success') }}", 'success'); @endif
        @if(session('error')) window.showToast("{{ session('error') }}", 'error'); @endif
    });
</script>
@endpush
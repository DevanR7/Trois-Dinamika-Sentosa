@extends('layouts.app')

@section('title', 'Laporan Jurnal Umum')

@section('content')
<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-6">
    
    {{-- HEADER --}}
    <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center mb-6">
        <div>
            <h3 class="text-2xl font-bold text-gray-900 tracking-tight">Laporan Jurnal Umum</h3>
            <p class="text-sm text-gray-500 mt-1">Rekapitulasi transaksi keuangan (General Ledger).</p>
        </div>
        <div class="mt-4 sm:mt-0">
            {{-- Bisa tambah tombol export Excel/PDF disini --}}
        </div>
    </div>

    {{-- FILTER FORM --}}
    <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-5 mb-6">
        <form action="{{ route('reports.general-ledger') }}" method="GET">
            <div class="grid grid-cols-1 md:grid-cols-12 gap-4 items-end">
                
                <div class="md:col-span-3">
                    <label for="start_date" class="block text-xs font-bold text-gray-500 uppercase mb-1">Dari Tanggal</label>
                    <input type="date" name="start_date" id="start_date" class="form-input block w-full rounded-lg border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm" value="{{ request('start_date', now()->startOfMonth()->toDateString()) }}">
                </div>
                
                <div class="md:col-span-3">
                    <label for="end_date" class="block text-xs font-bold text-gray-500 uppercase mb-1">Sampai Tanggal</label>
                    <input type="date" name="end_date" id="end_date" class="form-input block w-full rounded-lg border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm" value="{{ request('end_date', now()->endOfMonth()->toDateString()) }}">
                </div>
                
                <div class="md:col-span-3">
                    <label for="account_id" class="block text-xs font-bold text-gray-500 uppercase mb-1">Filter Akun</label>
                    <select name="account_id" id="account_id" class="select2 form-select block w-full rounded-lg border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                        <option value="">-- Semua Akun --</option>
                        @foreach ($accounts as $account)
                            <option value="{{ $account->account_id }}" {{ request('account_id') == $account->account_id ? 'selected' : '' }}>
                                {{ $account->account_number }} - {{ $account->account_name }}
                            </option>
                        @endforeach
                    </select>
                </div>
                
                <div class="md:col-span-2">
                    <label for="journal_group_id" class="block text-xs font-bold text-gray-500 uppercase mb-1">Ref / Grup</label>
                    <input type="text" name="journal_group_id" id="journal_group_id" class="form-input block w-full rounded-lg border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm" value="{{ request('journal_group_id') }}" placeholder="Contoh: INV-2023...">
                </div>
                
                <div class="md:col-span-1">
                    <button type="submit" class="w-full inline-flex justify-center items-center px-4 py-2 border border-transparent rounded-lg shadow-sm text-sm font-medium text-white bg-gray-800 hover:bg-gray-900 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-gray-900 transition" title="Filter">
                        <i class="material-icons text-lg">filter_alt</i>
                    </button>
                </div>

            </div>
        </form>
    </div>

    {{-- TABEL JURNAL --}}
    <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th scope="col" class="px-4 py-3 text-left text-xs font-bold text-gray-500 uppercase tracking-wider w-24">Tanggal</th>
                        <th scope="col" class="px-4 py-3 text-left text-xs font-bold text-gray-500 uppercase tracking-wider w-32">Ref Group</th>
                        <th scope="col" class="px-4 py-3 text-left text-xs font-bold text-gray-500 uppercase tracking-wider">Referensi</th>
                        <th scope="col" class="px-4 py-3 text-left text-xs font-bold text-gray-500 uppercase tracking-wider w-24">No. Akun</th>
                        <th scope="col" class="px-4 py-3 text-left text-xs font-bold text-gray-500 uppercase tracking-wider">Nama Akun</th>
                        <th scope="col" class="px-4 py-3 text-left text-xs font-bold text-gray-500 uppercase tracking-wider w-1/4">Deskripsi</th>
                        <th scope="col" class="px-4 py-3 text-right text-xs font-bold text-gray-500 uppercase tracking-wider w-32">Debit</th>
                        <th scope="col" class="px-4 py-3 text-right text-xs font-bold text-gray-500 uppercase tracking-wider w-32">Kredit</th>
                        <th scope="col" class="px-4 py-3 text-center text-xs font-bold text-gray-500 uppercase tracking-wider">User</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-100">
                    @php
                        $currentGroup = null;
                        $totalDebit = 0;
                        $totalCredit = 0;
                    @endphp
                    
                    @forelse ($journalEntries as $entry)
                        @php
                            $totalDebit += $entry->debit;
                            $totalCredit += $entry->credit;
                            
                            // Cek group baru untuk border pemisah visual
                            $isNewGroup = $currentGroup !== $entry->journal_group_id;
                            $currentGroup = $entry->journal_group_id;
                        @endphp
                        
                        <tr class="hover:bg-gray-50 transition-colors {{ $isNewGroup ? 'border-t-2 border-gray-200' : '' }}">
                            <td class="px-4 py-2 whitespace-nowrap text-xs text-gray-500">
                                {{ $entry->entry_date->format('d/m/Y') }}
                            </td>
                            <td class="px-4 py-2 whitespace-nowrap text-xs font-mono font-medium text-indigo-600">
                                {{ $entry->journal_group_id }}
                            </td>
                            <td class="px-4 py-2 whitespace-nowrap text-xs text-gray-500">
                                @if ($entry->reference)
                                    <span class="inline-flex items-center px-2 py-0.5 rounded text-[10px] font-medium bg-gray-100 text-gray-800">
                                        {{ Str::afterLast($entry->reference_type, '\\') }} #{{ $entry->reference_id }}
                                    </span>
                                @else
                                    <span class="text-gray-300">-</span>
                                @endif
                            </td>
                            <td class="px-4 py-2 whitespace-nowrap text-xs text-gray-900 font-bold">
                                {{ $entry->account->account_number ?? '-' }}
                            </td>
                            <td class="px-4 py-2 whitespace-nowrap text-xs text-gray-700">
                                {{ $entry->account->account_name ?? '-' }}
                            </td>
                            <td class="px-4 py-2 text-xs text-gray-600 truncate max-w-xs" title="{{ $entry->description }}">
                                {{ $entry->description }}
                            </td>
                            <td class="px-4 py-2 whitespace-nowrap text-xs text-right font-mono font-medium {{ $entry->debit > 0 ? 'text-gray-900' : 'text-gray-300' }}">
                                {{ $entry->debit > 0 ? number_format($entry->debit, 0, ',', '.') : '-' }}
                            </td>
                            <td class="px-4 py-2 whitespace-nowrap text-xs text-right font-mono font-medium {{ $entry->credit > 0 ? 'text-gray-900' : 'text-gray-300' }}">
                                {{ $entry->credit > 0 ? number_format($entry->credit, 0, ',', '.') : '-' }}
                            </td>
                            <td class="px-4 py-2 whitespace-nowrap text-center text-[10px] text-gray-400">
                                {{ $entry->user->username ?? 'System' }}
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="9" class="px-6 py-12 text-center text-gray-500">
                                <div class="flex flex-col items-center justify-center">
                                    <i class="material-icons text-4xl text-gray-300 mb-3">receipt_long</i>
                                    <p class="text-base font-medium">Tidak Ada Data</p>
                                    <p class="text-sm mt-1">Tidak ditemukan jurnal untuk periode ini.</p>
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
                
                {{-- FOOTER TOTAL --}}
                @if($journalEntries->isNotEmpty())
                <tfoot class="bg-gray-100 border-t border-gray-300">
                    <tr>
                        <td colspan="6" class="px-4 py-3 text-right text-xs font-bold text-gray-700 uppercase tracking-wider">
                            Total Halaman Ini
                        </td>
                        <td class="px-4 py-3 text-right text-sm font-bold text-indigo-700 font-mono border-t border-gray-300">
                            {{ number_format($totalDebit, 0, ',', '.') }}
                        </td>
                        <td class="px-4 py-3 text-right text-sm font-bold text-indigo-700 font-mono border-t border-gray-300">
                            {{ number_format($totalCredit, 0, ',', '.') }}
                        </td>
                        <td></td>
                    </tr>
                </tfoot>
                @endif
            </table>
        </div>
    </div>
    
    @if($journalEntries->hasPages())
        <div class="mt-6">
            {{ $journalEntries->links() }}
        </div>
    @endif
</div>
@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
<script>
    document.addEventListener('DOMContentLoaded', function() {
        $('.select2').select2({
            theme: 'bootstrap-5',
            width: '100%',
            placeholder: '-- Pilih Akun --',
            allowClear: true
        });
    });
</script>
@endpush
@extends('layouts.app')

@section('title', 'Detail Jurnal Umum')

@section('content')
<div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 py-6">
    
    {{-- HEADER --}}
    <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center mb-6">
        <div>
            <div class="flex items-center gap-2 text-sm text-gray-500 mb-1">
                <a href="{{ route('manual-journals.index') }}" class="hover:text-indigo-600 transition">Jurnal Umum</a>
                <span>/</span>
                <span class="text-gray-800">Detail</span>
            </div>
            <h2 class="text-2xl font-bold text-gray-900 tracking-tight">Detail Jurnal</h2>
            <span class="inline-flex items-center px-2.5 py-0.5 rounded text-sm font-mono bg-gray-100 text-gray-800 border border-gray-200 mt-1">
                {{ $manualJournal->journal_number }}
            </span>
        </div>
        <div class="mt-4 sm:mt-0">
            <a href="{{ route('manual-journals.index') }}" class="inline-flex items-center px-4 py-2 bg-white border border-gray-300 rounded-lg font-medium text-gray-700 hover:bg-gray-50 shadow-sm transition text-sm">
                <i class="material-icons text-lg mr-2">arrow_back</i> Kembali
            </a>
        </div>
    </div>

    {{-- INFO UTAMA --}}
    <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden mb-6">
        <div class="p-6 border-b border-gray-100 grid grid-cols-1 md:grid-cols-2 gap-6">
            <div>
                <p class="text-xs font-bold text-gray-500 uppercase mb-1">Tanggal Transaksi</p>
                <p class="text-gray-900 font-medium">{{ $manualJournal->entry_date->format('d F Y') }}</p>
            </div>
            <div>
                <p class="text-xs font-bold text-gray-500 uppercase mb-1">Dibuat Oleh</p>
                <p class="text-gray-900 font-medium">{{ $manualJournal->user->name ?? 'Sistem' }}</p>
            </div>
            <div class="md:col-span-2">
                <p class="text-xs font-bold text-gray-500 uppercase mb-1">Deskripsi / Memo</p>
                <div class="bg-gray-50 p-3 rounded-lg border border-gray-100 text-sm text-gray-700 italic">
                    "{{ $manualJournal->description }}"
                </div>
            </div>
        </div>

        {{-- TABEL RINCIAN --}}
        <div class="border-t border-gray-200">
            <div class="px-6 py-3 bg-gray-50 border-b border-gray-200">
                <h5 class="text-sm font-bold text-gray-700 uppercase tracking-wider">Rincian Akun</h5>
            </div>
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-bold text-gray-500 uppercase w-24">Kode</th>
                            <th class="px-6 py-3 text-left text-xs font-bold text-gray-500 uppercase">Nama Akun</th>
                            <th class="px-6 py-3 text-left text-xs font-bold text-gray-500 uppercase w-1/3">Deskripsi Baris</th>
                            <th class="px-6 py-3 text-right text-xs font-bold text-gray-500 uppercase w-32">Debit</th>
                            <th class="px-6 py-3 text-right text-xs font-bold text-gray-500 uppercase w-32">Kredit</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        @foreach ($manualJournal->entries as $entry)
                        <tr class="hover:bg-gray-50 transition">
                            <td class="px-6 py-3 text-sm font-mono text-gray-600">{{ $entry->account->account_number ?? '-' }}</td>
                            <td class="px-6 py-3 text-sm font-medium text-gray-900">{{ $entry->account->account_name ?? '-' }}</td>
                            <td class="px-6 py-3 text-xs text-gray-500">{{ $entry->description ?? '-' }}</td>
                            <td class="px-6 py-3 text-sm text-right font-mono {{ $entry->debit > 0 ? 'text-indigo-600 font-bold' : 'text-gray-400' }}">
                                {{ $entry->debit > 0 ? 'Rp '.number_format($entry->debit, 0, ',', '.') : '-' }}
                            </td>
                            <td class="px-6 py-3 text-sm text-right font-mono {{ $entry->credit > 0 ? 'text-red-600 font-bold' : 'text-gray-400' }}">
                                {{ $entry->credit > 0 ? 'Rp '.number_format($entry->credit, 0, ',', '.') : '-' }}
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                    <tfoot class="bg-gray-100 font-bold text-sm">
                        <tr>
                            <td colspan="3" class="px-6 py-3 text-right text-gray-600 uppercase text-xs">TOTAL</td>
                            <td class="px-6 py-3 text-right text-indigo-700 font-mono border-t border-gray-300">
                                Rp {{ number_format($manualJournal->total_debit, 0, ',', '.') }}
                            </td>
                            <td class="px-6 py-3 text-right text-red-700 font-mono border-t border-gray-300">
                                Rp {{ number_format($manualJournal->total_credit, 0, ',', '.') }}
                            </td>
                        </tr>
                    </tfoot>
                </table>
            </div>
        </div>
        
        <div class="p-6 bg-gray-50 border-t border-gray-200 flex justify-end">
            <a href="{{ route('manual-journals.edit', $manualJournal) }}" class="inline-flex items-center px-4 py-2 bg-yellow-500 border border-transparent rounded-lg font-bold text-sm text-white hover:bg-yellow-600 focus:outline-none shadow-sm transition">
                <i class="material-icons text-lg mr-2">edit</i> Edit Jurnal
            </a>
        </div>
    </div>
</div>
@endsection
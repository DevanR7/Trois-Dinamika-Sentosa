@extends('layouts.app')

@section('title', 'Transaksi Modal')

@section('content')
<div class="max-w-7xl mx-auto pb-20 animate-enter">
    
    {{-- HEADER --}}
    <div class="flex flex-col sm:flex-row justify-between items-end gap-4 mb-8">
        <div>
            <h1 class="text-2xl font-bold text-slate-800 tracking-tight">Modal & Prive</h1>
            <p class="text-slate-500 text-sm mt-1">Daftar riwayat setoran modal dan penarikan prive.</p>
        </div>
        <a href="{{ route('equity-transactions.create') }}" class="h-[48px] px-8 bg-indigo-600 hover:bg-indigo-700 text-white text-sm font-bold rounded-lg shadow-md transition-all flex items-center justify-center gap-2 group hover:-translate-y-0.5">
            <i class="material-icons text-[20px]">add</i> 
            <span>Catat Transaksi</span>
        </a>
    </div>

    {{-- FILTER --}}
    <div class="dashboard-card p-6 mb-6 shadow-sm">
        <form action="{{ route('equity-transactions.index') }}" method="GET">
            <div class="grid grid-cols-1 md:grid-cols-12 gap-4 items-end">
                <div class="md:col-span-3">
                    <label class="block text-xs font-bold text-slate-500 uppercase mb-1.5">Dari Tanggal</label>
                    <input type="date" name="start_date" class="form-input" value="{{ request('start_date') }}">
                </div>
                
                <div class="md:col-span-3">
                    <label class="block text-xs font-bold text-slate-500 uppercase mb-1.5">Sampai Tanggal</label>
                    <input type="date" name="end_date" class="form-input" value="{{ request('end_date') }}">
                </div>

                <div class="md:col-span-4">
                    <label class="block text-xs font-bold text-slate-500 uppercase mb-1.5">Tipe Transaksi</label>
                    <select name="type" class="form-input select2-basic">
                        <option value="">Semua Tipe</option>
                        <option value="investment" @selected(request('type') == 'investment')>Setoran Modal</option>
                        <option value="drawing" @selected(request('type') == 'drawing')>Penarikan Modal</option>
                    </select>
                </div>

                <div class="md:col-span-2">
                    <button type="submit" class="w-full h-[48px] bg-slate-800 hover:bg-slate-900 text-white font-bold rounded-lg shadow-sm transition-all flex items-center justify-center gap-2">
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
                        <th>Akun Transaksi</th>
                        <th class="w-1/3">Deskripsi</th>
                        <th class="text-right">Jumlah</th>
                        <th>Oleh</th>
                        <th class="text-center w-24 pr-6">Aksi</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-slate-100">
                    @forelse ($transactions as $transaction)
                        <tr class="hover:bg-slate-50/50 transition-colors">
                            <td class="pl-6 py-4 text-sm text-slate-600 whitespace-nowrap">
                                {{ $transaction->transaction_date->format('d/m/Y') }}
                            </td>
                            <td class="py-4">
                                @if ($transaction->type == 'investment')
                                    <div class="flex items-center gap-2 text-sm">
                                        <span class="font-bold text-slate-800">{{ $transaction->cashBankAccount->account_name ?? 'N/A' }}</span>
                                        <i class="material-icons text-slate-400 text-[14px]">arrow_back</i>
                                        <span class="text-slate-500">{{ $transaction->equityAccount->account_name ?? 'N/A' }}</span>
                                    </div>
                                    <span class="inline-flex items-center px-2 py-0.5 rounded text-[10px] font-bold bg-emerald-50 text-emerald-700 border border-emerald-100 mt-1 uppercase tracking-wide">
                                        Setoran Modal
                                    </span>
                                @else
                                    <div class="flex items-center gap-2 text-sm">
                                        <span class="font-bold text-slate-800">{{ $transaction->equityAccount->account_name ?? 'N/A' }}</span>
                                        <i class="material-icons text-slate-400 text-[14px]">arrow_back</i>
                                        <span class="text-slate-500">{{ $transaction->cashBankAccount->account_name ?? 'N/A' }}</span>
                                    </div>
                                    <span class="inline-flex items-center px-2 py-0.5 rounded text-[10px] font-bold bg-red-50 text-red-700 border border-red-100 mt-1 uppercase tracking-wide">
                                        Penarikan Prive
                                    </span>
                                @endif
                            </td>
                            <td class="py-4 text-sm text-slate-600 italic">
                                {{ Str::limit($transaction->description, 50) }}
                            </td>
                            <td class="py-4 text-right text-sm font-bold text-slate-900 font-mono">
                                Rp {{ number_format($transaction->amount, 0, ',', '.') }}
                            </td>
                            <td class="py-4 text-xs text-slate-500">
                                {{ $transaction->user->name ?? 'System' }}
                            </td>
                            <td class="pr-6 py-4 text-center">
                                {{-- PERBAIKAN: Menghapus class opacity-0 agar tombol selalu terlihat --}}
                                <div class="flex justify-center gap-2">
                                    <a href="{{ route('equity-transactions.edit', $transaction) }}" class="w-8 h-8 flex items-center justify-center rounded-lg bg-white border border-slate-200 text-amber-600 hover:bg-amber-50 hover:border-amber-200 transition shadow-sm" title="Edit">
                                        <i class="material-icons text-[16px]">edit</i>
                                    </a>
                                    
                                    {{-- Menggunakan Global Delete Handler dari app.js --}}
                                    <form action="{{ route('equity-transactions.destroy', $transaction) }}" method="POST" class="delete-form inline-block">
                                        @csrf @method('DELETE')
                                        <button type="submit" 
                                                data-name="{{ $transaction->type == 'investment' ? 'Setoran' : 'Penarikan' }} Rp {{ number_format($transaction->amount, 0, ',', '.') }}"
                                                class="w-8 h-8 flex items-center justify-center rounded-lg bg-white border border-slate-200 text-red-600 hover:bg-red-50 hover:border-red-200 transition shadow-sm" title="Hapus">
                                            <i class="material-icons text-[16px]">delete</i>
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="px-6 py-12 text-center text-slate-500">
                                <div class="flex flex-col items-center justify-center">
                                    <div class="w-16 h-16 bg-slate-50 rounded-full flex items-center justify-center mb-4">
                                        <i class="material-icons text-4xl opacity-30">account_balance_wallet</i>
                                    </div>
                                    <h3 class="text-base font-bold text-slate-800">Belum ada data</h3>
                                    <p class="text-sm mt-1">Silakan catat transaksi modal baru.</p>
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
                
                @if($transactions->isNotEmpty())
                <tfoot class="bg-slate-50 border-t border-slate-200">
                    <tr>
                        <td colspan="3" class="px-6 py-3 text-right text-xs font-bold text-slate-500 uppercase tracking-wider">Total Setoran</td>
                        <td class="px-6 py-3 text-right text-sm font-bold text-emerald-600 font-mono">Rp {{ number_format($totalInvestment, 0, ',', '.') }}</td>
                        <td colspan="2"></td>
                    </tr>
                    <tr>
                        <td colspan="3" class="px-6 py-3 text-right text-xs font-bold text-slate-500 uppercase tracking-wider">Total Penarikan</td>
                        <td class="px-6 py-3 text-right text-sm font-bold text-red-600 font-mono">(Rp {{ number_format($totalDrawing, 0, ',', '.') }})</td>
                        <td colspan="2"></td>
                    </tr>
                    <tr class="bg-indigo-50/30 border-t border-indigo-100">
                        <td colspan="3" class="px-6 py-3 text-right text-sm font-bold text-indigo-900 uppercase tracking-wider">Perubahan Modal Bersih</td>
                        <td class="px-6 py-3 text-right text-base font-bold {{ $netModal >= 0 ? 'text-emerald-700' : 'text-red-700' }} font-mono">
                             @if($netModal < 0)
                                (Rp {{ number_format(abs($netModal), 0, ',', '.') }})
                            @else
                                Rp {{ number_format($netModal, 0, ',', '.') }}
                            @endif
                        </td>
                        <td colspan="2"></td>
                    </tr>
                </tfoot>
                @endif
            </table>
        </div>
        
        @if($transactions->hasPages())
            <div class="px-6 py-4 border-t border-slate-200">
                {{ $transactions->links() }}
            </div>
        @endif
    </div>
</div>
@endsection

@push('scripts')
{{-- Hanya perlu Select2 JS untuk filter, sisanya pakai global app.js --}}
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
<script>
    document.addEventListener('DOMContentLoaded', function() {
        // Init Select2 Filter
        $('.select2-basic').select2({ minimumResultsForSearch: Infinity, width: '100%', dropdownCssClass: 'select2-dropdown-clean' });

        // Notifikasi Global dari Session
        @if(session('success')) window.showToast("{{ session('success') }}", 'success'); @endif
        @if(session('error')) window.showToast("{{ session('error') }}", 'error'); @endif
    });
</script>
@endpush
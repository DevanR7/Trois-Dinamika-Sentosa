@extends('admin.layouts.app')

@section('title', 'Transaksi Ekuitas (Modal)')

@section('content')
    <div class="flex flex-col gap-6">
        
        {{-- Page Header --}}
        <div class="page-header">
            <div>
                <h1 class="page-title">Transaksi Ekuitas</h1>
                <p class="page-subtitle">Kelola setoran modal pemilik (Investasi) dan penarikan modal (Prive).</p>
            </div>
            <div class="flex flex-col sm:flex-row gap-3">
                <a href="{{ route('admin.equity-transactions.create') }}" class="btn btn-primary">
                    <i class="material-icons text-[18px] mr-1">add</i> Transaksi Baru
                </a>
            </div>
        </div>

        {{-- Summary Cards (Financial Highlights) --}}
        <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
            {{-- Total Investasi --}}
            <div class="card p-5 border-l-4 border-l-emerald-500">
                <div class="flex items-center gap-4">
                    <div class="w-12 h-12 rounded-full bg-emerald-50 dark:bg-emerald-900/30 flex items-center justify-center text-emerald-600 dark:text-emerald-400">
                        <i class="material-icons text-2xl">trending_up</i>
                    </div>
                    <div>
                        <p class="text-xs font-bold text-slate-400 uppercase tracking-wider">Total Investasi Masuk</p>
                        <h3 class="text-xl font-bold text-emerald-600 dark:text-emerald-400 mt-1">
                            Rp {{ number_format($totalInvestment, 0, ',', '.') }}
                        </h3>
                    </div>
                </div>
            </div>

            {{-- Total Prive --}}
            <div class="card p-5 border-l-4 border-l-rose-500">
                <div class="flex items-center gap-4">
                    <div class="w-12 h-12 rounded-full bg-rose-50 dark:bg-rose-900/30 flex items-center justify-center text-rose-600 dark:text-rose-400">
                        <i class="material-icons text-2xl">trending_down</i>
                    </div>
                    <div>
                        <p class="text-xs font-bold text-slate-400 uppercase tracking-wider">Total Penarikan (Prive)</p>
                        <h3 class="text-xl font-bold text-rose-600 dark:text-rose-400 mt-1">
                            Rp {{ number_format($totalDrawing, 0, ',', '.') }}
                        </h3>
                    </div>
                </div>
            </div>

            {{-- Net Modal --}}
            <div class="card p-5 border-l-4 border-l-indigo-500">
                <div class="flex items-center gap-4">
                    <div class="w-12 h-12 rounded-full bg-indigo-50 dark:bg-indigo-900/30 flex items-center justify-center text-indigo-600 dark:text-indigo-400">
                        <i class="material-icons text-2xl">account_balance</i>
                    </div>
                    <div>
                        <p class="text-xs font-bold text-slate-400 uppercase tracking-wider">Perubahan Modal Bersih</p>
                        <h3 class="text-xl font-bold text-slate-800 dark:text-white mt-1">
                            Rp {{ number_format($netModal, 0, ',', '.') }}
                        </h3>
                    </div>
                </div>
            </div>
        </div>

        {{-- Filter Section --}}
        <div class="card p-5">
            <form method="GET" action="{{ route('admin.equity-transactions.index') }}">
                <div class="grid grid-cols-1 md:grid-cols-4 gap-4 items-end">
                    <div>
                        <label class="form-label">Tanggal Mulai</label>
                        <input type="date" name="start_date" value="{{ request('start_date') }}" class="form-input">
                    </div>
                    <div>
                        <label class="form-label">Tanggal Akhir</label>
                        <input type="date" name="end_date" value="{{ request('end_date') }}" class="form-input">
                    </div>
                    <div>
                        <label class="form-label">Tipe Transaksi</label>
                        <select name="type" class="form-select">
                            <option value="">Semua Tipe</option>
                            <option value="investment" {{ request('type') == 'investment' ? 'selected' : '' }}>Investasi (Modal Masuk)</option>
                            <option value="drawing" {{ request('type') == 'drawing' ? 'selected' : '' }}>Prive (Penarikan)</option>
                        </select>
                    </div>
                    <div>
                        <button type="submit" class="btn btn-secondary w-full">
                            <i class="material-icons text-[18px] mr-1">filter_list</i> Filter
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
                            <th>Tanggal</th>
                            <th>Tipe</th>
                            <th>Akun Ekuitas</th>
                            <th>Akun Kas/Bank</th>
                            <th>Keterangan</th>
                            <th class="text-right">Nominal (Rp)</th>
                            <th class="text-right">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($transactions as $trx)
                            <tr class="hover:bg-slate-50 dark:hover:bg-slate-800/50 transition-colors">
                                <td class="whitespace-nowrap">
                                    {{ $trx->transaction_date->translatedFormat('d M Y') }}
                                </td>
                                <td>
                                    @if($trx->type == 'investment')
                                        <span class="badge badge-success">
                                            <i class="material-icons text-[14px] mr-1">arrow_upward</i> Investasi
                                        </span>
                                    @else
                                        <span class="badge badge-danger">
                                            <i class="material-icons text-[14px] mr-1">arrow_downward</i> Prive
                                        </span>
                                    @endif
                                </td>
                                <td>
                                    <div class="flex flex-col">
                                        <span class="font-bold text-slate-700 dark:text-slate-200 text-xs">
                                            {{ $trx->equityAccount->account_name }}
                                        </span>
                                        <span class="text-[10px] text-slate-400 font-mono">
                                            {{ $trx->equityAccount->account_number }}
                                        </span>
                                    </div>
                                </td>
                                <td>
                                    <span class="text-xs text-slate-600 dark:text-slate-400">
                                        {{ $trx->cashBankAccount->account_name }}
                                    </span>
                                </td>
                                <td class="max-w-xs truncate text-xs text-slate-500" title="{{ $trx->description }}">
                                    {{ $trx->description }}
                                </td>
                                <td class="text-right font-mono font-medium {{ $trx->type == 'investment' ? 'text-emerald-600' : 'text-rose-600' }}">
                                    {{ $trx->type == 'investment' ? '+' : '-' }} {{ number_format($trx->amount, 2, ',', '.') }}
                                </td>
                                <td>
                                    <div class="flex items-center justify-end gap-2">
                                        <x-ui.table-actions 
                                            edit="{{ route('admin.equity-transactions.edit', $trx->transaction_id) }}"
                                            delete="{{ route('admin.equity-transactions.destroy', $trx->transaction_id) }}"
                                            message="Menghapus transaksi ini akan membatalkan jurnal akuntansi terkait."
                                        />
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="text-center py-12">
                                    <div class="flex flex-col items-center justify-center">
                                        <div class="w-16 h-16 bg-slate-100 dark:bg-slate-800 rounded-full flex items-center justify-center mb-4 text-slate-400">
                                            <i class="material-icons text-3xl">account_balance_wallet</i>
                                        </div>
                                        <p class="text-slate-500 font-medium">Belum ada transaksi modal.</p>
                                    </div>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            
            {{-- Pagination --}}
            <div class="px-6 py-4 border-t border-slate-200 dark:border-slate-700">
                {{ $transactions->links('vendor.pagination.admin') }}
            </div>
        </div>
    </div>
@endsection
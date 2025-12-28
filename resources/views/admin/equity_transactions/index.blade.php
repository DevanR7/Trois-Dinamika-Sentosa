@extends('admin.layouts.app')

@section('title', 'Transaksi Ekuitas')

@section('content')

    {{-- HEADER & ACTIONS --}}
    <div class="page-header">
        <div>
            <h1 class="page-title">Transaksi Ekuitas</h1>
            <p class="page-subtitle">Kelola setoran modal dan penarikan (prive) pemilik.</p>
        </div>
        <div class="flex items-center gap-3">
            <a href="{{ route('admin.equity-transactions.create') }}" class="btn btn-primary">
                <i class="material-icons text-sm mr-1">add_card</i> Catat Transaksi
            </a>
        </div>
    </div>

    {{-- SUMMARY CARDS --}}
    <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-6">
        
        {{-- Total Setoran --}}
        <div class="card p-5 border-l-4 border-emerald-500 bg-emerald-50/30 dark:bg-emerald-900/10">
            <div class="text-xs font-bold text-emerald-600 uppercase tracking-wider mb-1">Total Setoran Modal</div>
            <div class="text-2xl font-extrabold text-slate-800 dark:text-white">
                Rp {{ number_format($totalInvestment, 0, ',', '.') }}
            </div>
            <div class="text-[10px] text-slate-500 mt-1">Akumulasi modal masuk</div>
        </div>

        {{-- Total Prive --}}
        <div class="card p-5 border-l-4 border-rose-500 bg-rose-50/30 dark:bg-rose-900/10">
            <div class="text-xs font-bold text-rose-600 uppercase tracking-wider mb-1">Total Penarikan (Prive)</div>
            <div class="text-2xl font-extrabold text-slate-800 dark:text-white">
                Rp {{ number_format($totalDrawing, 0, ',', '.') }}
            </div>
            <div class="text-[10px] text-slate-500 mt-1">Akumulasi pengambilan pribadi</div>
        </div>

        {{-- Modal Bersih --}}
        <div class="card p-5 border-l-4 border-indigo-500 bg-indigo-50/30 dark:bg-indigo-900/10">
            <div class="text-xs font-bold text-indigo-600 uppercase tracking-wider mb-1">Perubahan Modal Bersih</div>
            <div class="text-2xl font-extrabold text-slate-800 dark:text-white">
                Rp {{ number_format($netModal, 0, ',', '.') }}
            </div>
            <div class="text-[10px] text-slate-500 mt-1">Setoran dikurangi Penarikan</div>
        </div>

    </div>

    {{-- FILTER & SEARCH --}}
    <div class="card mb-6">
        <div class="card-body">
            <form action="{{ route('admin.equity-transactions.index') }}" method="GET" class="grid grid-cols-1 md:grid-cols-12 gap-4">
                
                {{-- Date Range --}}
                <div class="md:col-span-4 grid grid-cols-2 gap-2">
                    <div>
                        <label class="form-label text-[10px]">Dari Tanggal</label>
                        <input type="date" name="start_date" class="form-input" 
                               value="{{ request('start_date') }}">
                    </div>
                    <div>
                        <label class="form-label text-[10px]">Sampai Tanggal</label>
                        <input type="date" name="end_date" class="form-input" 
                               value="{{ request('end_date') }}">
                    </div>
                </div>

                {{-- Type Filter --}}
                <div class="md:col-span-3">
                    <label class="form-label text-[10px]">Jenis Transaksi</label>
                    <select name="type" class="tom-select">
                        <option value="">Semua Jenis</option>
                        <option value="investment" {{ request('type') == 'investment' ? 'selected' : '' }}>Setoran Modal</option>
                        <option value="drawing" {{ request('type') == 'drawing' ? 'selected' : '' }}>Penarikan (Prive)</option>
                    </select>
                </div>

                {{-- Filter Button --}}
                <div class="md:col-span-2 flex items-end">
                    <button type="submit" class="btn btn-secondary w-full">
                        <i class="material-icons text-sm mr-1">filter_list</i> Filter
                    </button>
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
                        <th>Tanggal</th>
                        <th>Jenis</th>
                        <th>Akun Ekuitas & Kas</th>
                        <th>Keterangan</th>
                        <th class="text-right">Nominal</th>
                        <th class="text-center w-36">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($transactions as $trx)
                        <tr>
                            <td>
                                <div class="font-medium text-slate-700 dark:text-slate-200">
                                    {{ $trx->transaction_date->format('d M Y') }}
                                </div>
                            </td>
                            <td>
                                @if($trx->type == 'investment')
                                    <span class="badge badge-success">Setoran Modal</span>
                                @else
                                    <span class="badge badge-warning">Penarikan Prive</span>
                                @endif
                            </td>
                            <td>
                                <div class="text-sm font-bold text-slate-700 dark:text-slate-200">
                                    {{ $trx->equityAccount->account_name ?? '-' }}
                                </div>
                                <div class="text-xs text-slate-500 flex items-center gap-1">
                                    <i class="material-icons text-[10px]">arrow_right_alt</i>
                                    {{ $trx->cashBankAccount->account_name ?? 'Kas/Bank' }}
                                </div>
                            </td>
                            <td class="text-sm text-slate-600 dark:text-slate-300 max-w-xs truncate">
                                {{ $trx->description }}
                            </td>
                            <td class="text-right font-mono font-bold text-slate-700 dark:text-white">
                                Rp {{ number_format($trx->amount, 0, ',', '.') }}
                            </td>
                            <td class="text-center">
                                <div class="flex items-center justify-center gap-2">
                                    
                                    {{-- Edit --}}
                                    <a href="{{ route('admin.equity-transactions.edit', $trx->transaction_id) }}" 
                                       class="w-9 h-9 rounded-full flex items-center justify-center bg-indigo-50 border border-transparent text-indigo-600 hover:bg-indigo-600 hover:text-white transition-colors shadow-sm dark:bg-indigo-900/30 dark:text-indigo-400"
                                       title="Edit">
                                        <i class="material-icons text-[18px] leading-none">edit</i>
                                    </a>

                                    {{-- Delete --}}
                                    <button type="button" onclick="confirmDelete('{{ $trx->transaction_id }}')" 
                                            class="w-9 h-9 rounded-full flex items-center justify-center bg-rose-50 border border-transparent text-rose-600 hover:bg-rose-600 hover:text-white transition-colors shadow-sm dark:bg-rose-900/30 dark:text-rose-400"
                                            title="Hapus">
                                        <i class="material-icons text-[18px] leading-none">delete</i>
                                    </button>
                                    
                                    <form id="delete-form-{{ $trx->transaction_id }}" 
                                          action="{{ route('admin.equity-transactions.destroy', $trx->transaction_id) }}" 
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
                                    <i class="material-icons text-5xl mb-2">account_balance_wallet</i>
                                    <span>Belum ada transaksi ekuitas.</span>
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        
        <div class="card-footer">
            {{ $transactions->links() }}
        </div>
    </div>

@endsection

@push('scripts')
<script>
    function confirmDelete(id) {
        window.confirmDialog({
            title: 'Hapus Transaksi?',
            text: "Data ini akan dihapus permanen dan jurnal akuntansi akan dibalik.",
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
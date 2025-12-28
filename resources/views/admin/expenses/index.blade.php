@extends('admin.layouts.app')

@section('title', 'Daftar Pengeluaran')

@section('content')

    {{-- HEADER & ACTIONS --}}
    <div class="page-header">
        <div>
            <h1 class="page-title">Pengeluaran Biaya</h1>
            <p class="page-subtitle">Catat dan pantau biaya operasional perusahaan.</p>
        </div>
        <div class="flex items-center gap-3">
            <a href="{{ route('admin.expenses.create') }}" class="btn btn-primary">
                <i class="material-icons text-sm mr-1">add_circle</i> Catat Biaya
            </a>
        </div>
    </div>

    {{-- FILTER & SEARCH --}}
    <div class="card mb-6">
        <div class="card-body">
            <form action="{{ route('admin.expenses.index') }}" method="GET" class="grid grid-cols-1 md:grid-cols-12 gap-4">
                
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

                {{-- Search --}}
                <div class="md:col-span-6">
                    <label class="form-label text-[10px]">&nbsp;</label>
                    <div class="input-group">
                        <span class="input-group-text bg-white dark:bg-slate-800">
                            <i class="material-icons text-slate-400">search</i>
                        </span>
                        <input type="text" name="search" class="form-input border-l-0 pl-0" 
                               placeholder="Cari deskripsi atau kategori..." 
                               value="{{ request('search') }}">
                    </div>
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
                        <th>Kategori / Akun Biaya</th>
                        <th>Deskripsi</th>
                        <th>Sumber Dana</th>
                        <th class="text-right">Jumlah</th>
                        <th class="text-center w-36">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($expenses as $expense)
                        <tr>
                            <td>
                                <div class="font-medium text-slate-700 dark:text-slate-200">
                                    {{ $expense->expense_date->format('d M Y') }}
                                </div>
                                <div class="text-xs text-slate-500">
                                    {{ $expense->created_at->format('H:i') }}
                                </div>
                            </td>
                            <td>
                                <div class="font-bold text-slate-700 dark:text-slate-200">
                                    {{ $expense->expenseAccount->account_name ?? 'Tanpa Akun' }}
                                </div>
                                <div class="text-xs text-slate-500 font-mono">
                                    {{ $expense->expenseAccount->account_number ?? '-' }}
                                </div>
                            </td>
                            <td class="text-sm text-slate-600 dark:text-slate-300 max-w-xs truncate">
                                {{ $expense->description }}
                            </td>
                            <td>
                                <span class="badge badge-secondary">
                                    {{ $expense->cashBankAccount->account_name ?? 'Kas/Bank' }}
                                </span>
                            </td>
                            <td class="text-right font-bold text-rose-600">
                                Rp {{ number_format($expense->amount, 0, ',', '.') }}
                            </td>
                            <td class="text-center">
                                <div class="flex items-center justify-center gap-2">
                                    
                                    <a href="{{ route('admin.expenses.edit', $expense->expense_id) }}" 
                                       class="w-9 h-9 rounded-full flex items-center justify-center bg-indigo-50 border border-transparent text-indigo-600 hover:bg-indigo-600 hover:text-white transition-colors shadow-sm dark:bg-indigo-900/30 dark:text-indigo-400"
                                       title="Edit">
                                        <i class="material-icons text-[18px] leading-none">edit</i>
                                    </a>

                                    <button type="button" onclick="confirmDelete('{{ $expense->expense_id }}')" 
                                            class="w-9 h-9 rounded-full flex items-center justify-center bg-rose-50 border border-transparent text-rose-600 hover:bg-rose-600 hover:text-white transition-colors shadow-sm dark:bg-rose-900/30 dark:text-rose-400"
                                            title="Hapus">
                                        <i class="material-icons text-[18px] leading-none">delete</i>
                                    </button>
                                    
                                    <form id="delete-form-{{ $expense->expense_id }}" 
                                          action="{{ route('admin.expenses.destroy', $expense->expense_id) }}" 
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
                                    <i class="material-icons text-5xl mb-2">receipt_long</i>
                                    <span>Belum ada data pengeluaran.</span>
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
                {{-- FOOTER TOTAL --}}
                <tfoot class="bg-slate-50 dark:bg-slate-800 border-t border-slate-200 dark:border-slate-700">
                    <tr>
                        <td colspan="4" class="px-6 py-4 text-right font-bold text-slate-600 dark:text-slate-300 uppercase text-xs tracking-wider">
                            Total Periode Ini
                        </td>
                        <td class="px-6 py-4 text-right font-extrabold text-rose-600 text-lg">
                            Rp {{ number_format($totalExpenses, 0, ',', '.') }}
                        </td>
                        <td></td>
                    </tr>
                </tfoot>
            </table>
        </div>
        
        <div class="card-footer">
            {{ $expenses->links() }}
        </div>
    </div>

@endsection

@push('scripts')
<script>
    function confirmDelete(id) {
        window.confirmDialog({
            title: 'Hapus Pengeluaran?',
            text: "Data ini akan dihapus permanen dan jurnal akuntansi akan dibalik (reversal).",
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
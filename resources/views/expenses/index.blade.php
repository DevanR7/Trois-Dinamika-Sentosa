@extends('layouts.app')

@section('title', 'Beban Operasional')

@section('content')
<div class="max-w-7xl mx-auto pb-20 animate-enter">
    
    {{-- HEADER --}}
    <div class="flex flex-col sm:flex-row justify-between items-end gap-4 mb-8">
        <div>
            <h1 class="text-2xl font-bold text-slate-800 tracking-tight">Data Beban Operasional</h1>
            <p class="text-slate-500 text-sm mt-1">Daftar pengeluaran rutin perusahaan.</p>
        </div>
        <a href="{{ route('expenses.create') }}" class="h-[48px] px-8 bg-indigo-600 hover:bg-indigo-700 text-white text-sm font-bold rounded-lg shadow-md transition-all flex items-center justify-center gap-2 group hover:-translate-y-0.5">
            <i class="material-icons text-[20px]">add</i> 
            <span>Tambah Pengeluaran</span>
        </a>
    </div>

    {{-- FILTER --}}
    <div class="dashboard-card p-6 mb-6 shadow-sm">
        <form action="{{ route('expenses.index') }}" method="GET">
            <div class="grid grid-cols-1 md:grid-cols-12 gap-4 items-end">
                <div class="md:col-span-4">
                    <label for="search" class="block text-xs font-bold text-slate-500 uppercase mb-1.5">Pencarian</label>
                    <div class="relative">
                        <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                            <i class="material-icons text-slate-400 text-[20px]">search</i>
                        </div>
                        <input type="text" name="search" id="search" value="{{ request('search') }}" 
                            class="form-input pl-10" 
                            placeholder="Deskripsi atau Kategori...">
                    </div>
                </div>
                
                <div class="md:col-span-3">
                    <label class="block text-xs font-bold text-slate-500 uppercase mb-1.5">Dari Tanggal</label>
                    <input type="date" name="start_date" class="form-input" value="{{ request('start_date') }}">
                </div>

                <div class="md:col-span-3">
                    <label class="block text-xs font-bold text-slate-500 uppercase mb-1.5">Sampai Tanggal</label>
                    <input type="date" name="end_date" class="form-input" value="{{ request('end_date') }}">
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
                        <th>Kategori</th>
                        <th class="w-1/3">Deskripsi</th>
                        <th class="text-right">Jumlah</th>
                        <th>Oleh</th>
                        <th class="text-center w-24 pr-6">Aksi</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-slate-100">
                    @forelse ($expenses as $expense)
                        <tr class="hover:bg-slate-50/50 transition-colors">
                            <td class="pl-6 py-4 text-sm text-slate-600 whitespace-nowrap">
                                {{ $expense->expense_date->format('d/m/Y') }}
                            </td>
                            <td class="py-4">
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-md text-xs font-bold bg-indigo-50 text-indigo-700 border border-indigo-100">
                                    {{ $expense->expenseAccount->account_name ?? $expense->category }}
                                </span>
                            </td>
                            <td class="py-4 text-sm text-slate-600 italic">
                                <span class="line-clamp-1" title="{{ $expense->description }}">
                                    {{ Str::limit($expense->description, 50) }}
                                </span>
                            </td>
                            <td class="py-4 text-right text-sm font-bold text-red-600 font-mono">
                                Rp {{ number_format($expense->amount, 0, ',', '.') }}
                            </td>
                            <td class="py-4 text-xs text-slate-500">
                                {{ $expense->user->name ?? 'N/A' }}
                            </td>
                            <td class="pr-6 py-4 text-center">
                                <div class="flex justify-center gap-2">
                                    <a href="{{ route('expenses.edit', $expense) }}" 
                                       class="w-8 h-8 flex items-center justify-center rounded-lg bg-white border border-slate-200 text-amber-600 hover:bg-amber-50 hover:border-amber-200 transition shadow-sm" 
                                       title="Edit">
                                        <i class="material-icons text-[16px]">edit</i>
                                    </a>
                                    
                                    <form action="{{ route('expenses.destroy', $expense) }}" method="POST" class="delete-form inline-block">
                                        @csrf @method('DELETE')
                                        <button type="submit" 
                                                data-name="{{ $expense->description }} (Rp {{ number_format($expense->amount, 0, ',', '.') }})"
                                                class="w-8 h-8 flex items-center justify-center rounded-lg bg-white border border-slate-200 text-red-600 hover:bg-red-50 hover:border-red-200 transition shadow-sm" 
                                                title="Hapus">
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
                                        <i class="material-icons text-4xl opacity-30">receipt_long</i>
                                    </div>
                                    <h3 class="text-base font-bold text-slate-800">Belum ada data</h3>
                                    <p class="text-sm mt-1">Silakan tambahkan pengeluaran baru.</p>
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
                
                <tfoot class="bg-slate-50 border-t border-slate-200">
                    <tr>
                        <td colspan="3" class="px-6 py-3 text-right text-xs font-bold text-slate-500 uppercase tracking-wider">Total Pengeluaran</td>
                        <td class="px-6 py-3 text-right text-base font-bold text-red-700 font-mono">Rp {{ number_format($totalExpenses, 0, ',', '.') }}</td>
                        <td colspan="2"></td>
                    </tr>
                </tfoot>
            </table>
        </div>
        
        @if($expenses->hasPages())
            <div class="px-6 py-4 border-t border-slate-200 bg-slate-50/50">
                {{ $expenses->links() }}
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
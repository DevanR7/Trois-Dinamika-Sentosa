@extends('layouts.app')

@section('title', 'Data Pinjaman')

@section('content')
<div class="max-w-7xl mx-auto pb-20 animate-enter">
    
    {{-- HEADER --}}
    <div class="flex flex-col sm:flex-row justify-between items-end gap-4 mb-8">
        <div>
            <h1 class="text-2xl font-bold text-slate-800 tracking-tight">Data Pinjaman</h1>
            <p class="text-slate-500 text-sm mt-1">Daftar pinjaman perusahaan (Hutang Bank/Koperasi).</p>
        </div>
        <a href="{{ route('loans.create') }}" class="h-[48px] px-8 bg-indigo-600 hover:bg-indigo-700 text-white text-sm font-bold rounded-lg shadow-md transition-all flex items-center justify-center gap-2 group hover:-translate-y-0.5">
            <i class="material-icons text-[20px] group-hover:rotate-90 transition-transform">add</i> 
            <span>Tambah Pinjaman</span>
        </a>
    </div>

    {{-- FILTER --}}
    <div class="dashboard-card p-6 mb-6 shadow-sm">
        <form action="{{ route('loans.index') }}" method="GET">
            <div class="flex flex-col md:flex-row gap-4 items-end">
                <div class="flex-grow w-full">
                    <label for="search" class="block text-xs font-bold text-slate-500 uppercase mb-1.5">Pencarian</label>
                    <div class="relative">
                        <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                            <i class="material-icons text-slate-400 text-[20px]">search</i>
                        </div>
                        <input type="text" name="search" id="search" value="{{ request('search') }}" 
                            class="form-input pl-10" 
                            placeholder="Cari Pemberi Pinjaman...">
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
                        <th class="pl-6 w-32">Tgl. Pinjam</th>
                        <th>Pemberi Pinjaman</th>
                        <th>Akun Utang</th>
                        <th class="text-right">Pokok Awal</th>
                        <th class="text-right">Sisa Pokok</th>
                        <th class="text-center w-32">Status</th>
                        <th class="text-center w-32 pr-6">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 bg-white">
                    @forelse ($loans as $loan)
                        <tr class="hover:bg-slate-50/50 transition-colors group">
                            <td class="pl-6 py-4 text-sm text-slate-600 whitespace-nowrap">
                                {{ $loan->loan_date->format('d M Y') }}
                            </td>
                            <td class="py-4">
                                <span class="text-sm font-bold text-slate-800 group-hover:text-indigo-600 transition-colors">
                                    {{ $loan->lender_name }}
                                </span>
                                @if($loan->description)
                                    <p class="text-xs text-slate-500 italic truncate max-w-[200px] mt-0.5">{{ $loan->description }}</p>
                                @endif
                            </td>
                            <td class="py-4 text-xs">
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-md bg-slate-100 text-slate-600 border border-slate-200 font-medium">
                                    {{ $loan->loanAccount->account_name ?? 'N/A' }}
                                </span>
                            </td>
                            <td class="py-4 text-right text-sm text-slate-500 font-mono">
                                Rp {{ number_format($loan->principal_amount, 0, ',', '.') }}
                            </td>
                            <td class="py-4 text-right text-sm font-bold text-red-600 font-mono">
                                Rp {{ number_format($loan->remaining_balance, 0, ',', '.') }}
                            </td>
                            <td class="py-4 text-center">
                                @if ($loan->status == 'active')
                                    <span class="status-pending flex items-center justify-center w-fit mx-auto px-2.5 py-0.5 text-[11px]">
                                        Belum Lunas
                                    </span>
                                @else
                                    <span class="status-completed flex items-center justify-center w-fit mx-auto px-2.5 py-0.5 text-[11px]">
                                        <i class="material-icons text-[12px] mr-1">check_circle</i> Lunas
                                    </span>
                                @endif
                            </td>
                            <td class="pr-6 py-4 text-center">
                                <div class="flex justify-center items-center gap-2">
                                    {{-- Detail --}}
                                    <a href="{{ route('loans.show', $loan) }}" class="w-8 h-8 flex items-center justify-center rounded-lg bg-white border border-slate-200 text-indigo-600 hover:bg-indigo-50 hover:border-indigo-200 transition shadow-sm" title="Detail & Bayar">
                                        <i class="material-icons text-[16px]">visibility</i>
                                    </a>
                                    
                                    {{-- Edit & Delete (Hanya jika belum ada pembayaran) --}}
                                    @if (!$loan->payments()->exists())
                                        <a href="{{ route('loans.edit', $loan) }}" class="w-8 h-8 flex items-center justify-center rounded-lg bg-white border border-slate-200 text-amber-600 hover:bg-amber-50 hover:border-amber-200 transition shadow-sm" title="Edit">
                                            <i class="material-icons text-[16px]">edit</i>
                                        </a>
                                        
                                        {{-- Global Delete Handler --}}
                                        <form action="{{ route('loans.destroy', $loan) }}" method="POST" class="delete-form inline-block">
                                            @csrf @method('DELETE')
                                            <button type="submit" 
                                                    data-name="{{ $loan->lender_name }} (Rp {{ number_format($loan->principal_amount, 0, ',', '.') }})"
                                                    data-title="Hapus Pinjaman?"
                                                    data-text="Data pinjaman dan jurnal penerimaan kas akan dihapus permanen."
                                                    class="w-8 h-8 flex items-center justify-center rounded-lg bg-white border border-slate-200 text-red-600 hover:bg-red-50 hover:border-red-200 transition shadow-sm" 
                                                    title="Hapus">
                                                <i class="material-icons text-[16px]">delete</i>
                                            </button>
                                        </form>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="px-6 py-12 text-center text-slate-500">
                                <div class="flex flex-col items-center justify-center">
                                    <div class="w-16 h-16 bg-slate-50 rounded-full flex items-center justify-center mb-4 text-slate-300">
                                        <i class="material-icons text-4xl">account_balance_wallet</i>
                                    </div>
                                    <h3 class="text-base font-bold text-slate-800">Belum ada data pinjaman</h3>
                                    <p class="text-sm mt-1">Silakan tambahkan pinjaman baru.</p>
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        
        @if($loans->hasPages())
            <div class="px-6 py-4 border-t border-slate-100 bg-slate-50/80">
                {{ $loans->links() }}
            </div>
        @endif
    </div>
</div>
@endsection

@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function() {
        // Notifikasi Toast (Global Handler di app.js menangani sisanya)
        @if(session('success')) window.showToast("{{ session('success') }}", 'success'); @endif
        @if(session('error')) window.showToast("{{ session('error') }}", 'error'); @endif
    });
</script>
@endpush
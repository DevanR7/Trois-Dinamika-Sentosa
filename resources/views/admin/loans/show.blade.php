@extends('admin.layouts.app')

@section('title', 'Detail Pinjaman')

@section('content')
<div class="max-w-7xl mx-auto pb-20 animate-enter">
    
    {{-- HEADER --}}
    <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center mb-8 gap-4">
        <div>
            <nav class="flex items-center gap-2 text-sm text-slate-500 mb-1">
                <a href="{{ route('admin.loans.index') }}" class="hover:text-indigo-600 transition-colors">Pinjaman</a>
                <span class="mx-2 text-slate-300">/</span>
                <span class="text-slate-800 font-semibold">Detail</span>
            </nav>
            <h1 class="text-2xl font-bold text-slate-800 tracking-tight">Detail Pinjaman</h1>
        </div>
        <div class="flex gap-3 w-full sm:w-auto">
            <a href="{{ route('admin.loans.index') }}" class="h-[48px] px-6 bg-white border border-slate-300 text-slate-600 rounded-lg text-sm font-bold hover:bg-slate-50 transition-all flex items-center justify-center gap-2 shadow-sm">
                <i class="material-icons text-[18px]">arrow_back</i> Kembali
            </a>
        </div>
    </div>

    {{-- INFO UTAMA --}}
    <div class="dashboard-card p-0 overflow-hidden shadow-lg border-0 ring-1 ring-slate-900/5 mb-8">
        <div class="p-6 md:p-8 flex flex-col md:flex-row gap-8 items-center">
            
            <div class="flex-1 w-full">
                <div class="flex items-center gap-3 mb-2">
                    <h3 class="text-2xl font-bold text-indigo-600">{{ $loan->lender_name }}</h3>
                    @if ($loan->status == 'active')
                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-bold bg-amber-100 text-amber-800 border border-amber-200 uppercase tracking-wide">Belum Lunas</span>
                    @else
                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-bold bg-emerald-100 text-emerald-800 border border-emerald-200 uppercase tracking-wide">Lunas</span>
                    @endif
                </div>
                <p class="text-slate-500 mb-6 text-sm italic">{{ $loan->description ?? 'Tidak ada deskripsi' }}</p>
                
                <div class="flex gap-8">
                    <div>
                        <p class="text-[10px] font-bold text-slate-400 uppercase tracking-wider mb-1">Tanggal Pinjam</p>
                        <p class="text-lg font-bold text-slate-900 flex items-center gap-2">
                            <i class="material-icons text-slate-400 text-base">event</i> {{ $loan->loan_date->format('d M Y') }}
                        </p>
                    </div>
                </div>
            </div>

            <div class="w-full md:w-1/3 bg-slate-50 p-6 rounded-xl border border-slate-100 text-center">
                <p class="text-xs font-bold text-slate-500 uppercase tracking-wider mb-2">Sisa Pokok Pinjaman</p>
                <h2 class="text-3xl font-bold text-red-600 mb-4 font-mono">Rp {{ number_format($loan->remaining_balance, 0, ',', '.') }}</h2>
                
                @php
                    $percentPaid = ($loan->principal_amount > 0) 
                        ? (($loan->principal_amount - $loan->remaining_balance) / $loan->principal_amount) * 100 
                        : 0;
                @endphp
                <div class="w-full bg-slate-200 rounded-full h-2 mb-2 overflow-hidden">
                    <div class="bg-emerald-500 h-2 rounded-full transition-all duration-1000 ease-out" style="width: {{ $percentPaid }}%"></div>
                </div>
                <p class="text-[10px] text-slate-500 font-bold uppercase">Terbayar: {{ round($percentPaid) }}%</p>
            </div>
        </div>
        
        <div class="bg-slate-50 border-t border-slate-100 p-6 grid grid-cols-1 sm:grid-cols-2 md:grid-cols-4 gap-6 text-center md:text-left">
            <div>
                <p class="text-[10px] font-bold text-slate-400 uppercase mb-1">Total Pinjaman Awal</p>
                <p class="font-bold text-slate-900 font-mono text-lg">Rp {{ number_format($loan->principal_amount, 0, ',', '.') }}</p>
            </div>
            <div>
                <p class="text-[10px] font-bold text-slate-400 uppercase mb-1">Total Pokok Dibayar</p>
                <p class="font-bold text-emerald-600 font-mono text-lg">Rp {{ number_format($loan->payments->sum('principal_paid'), 0, ',', '.') }}</p>
            </div>
            <div>
                <p class="text-[10px] font-bold text-slate-400 uppercase mb-1">Total Bunga Dibayar</p>
                <p class="font-bold text-amber-600 font-mono text-lg">Rp {{ number_format($loan->payments->sum('interest_paid'), 0, ',', '.') }}</p>
            </div>
            <div>
                <p class="text-[10px] font-bold text-slate-400 uppercase mb-1">Akun Akuntansi</p>
                <p class="text-xs text-slate-700 font-medium truncate" title="{{ $loan->loanAccount->account_name }}">{{ $loan->loanAccount->account_name }} (Utang)</p>
                <p class="text-xs text-slate-700 font-medium truncate" title="{{ $loan->cashBankAccount->account_name }}">{{ $loan->cashBankAccount->account_name }} (Kas)</p>
            </div>
        </div>
    </div>

    {{-- RIWAYAT PEMBAYARAN --}}
    <div class="dashboard-card p-0 overflow-hidden shadow-lg border-0 ring-1 ring-slate-900/5">
        <div class="px-6 py-4 border-b border-slate-100 bg-slate-50/50 flex justify-between items-center">
            <h5 class="font-bold text-slate-700 text-sm uppercase tracking-wider flex items-center gap-2">
                <i class="material-icons text-indigo-600">history</i> Riwayat Cicilan
            </h5>
            
            @if($loan->status == 'active')
                <a href="{{ route('admin.loans.payments.create', $loan) }}" class="h-[36px] px-4 bg-emerald-600 text-white rounded-lg text-xs font-bold hover:bg-emerald-700 transition-all shadow-sm flex items-center gap-1 group">
                    <i class="material-icons text-[16px] group-hover:scale-110 transition-transform">add</i> Bayar Cicilan
                </a>
            @endif
        </div>

        <div class="overflow-x-auto">
            <table class="dashboard-table min-w-full">
                <thead class="bg-slate-50 border-b border-slate-200">
                    <tr>
                        <th class="pl-6 w-32">Tanggal</th>
                        <th>Keterangan</th>
                        <th class="text-right">Pokok</th>
                        <th class="text-right">Bunga</th>
                        <th class="text-right">Total Bayar</th>
                        <th class="w-48">Via Akun</th>
                        <th class="text-center w-24 pr-6">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 bg-white">
                    @forelse ($loan->payments as $payment)
                        <tr class="hover:bg-slate-50/50 transition-colors">
                            <td class="pl-6 py-4 text-sm text-slate-600 font-mono">
                                {{ $payment->payment_date->format('d/m/Y') }}
                            </td>
                            <td class="py-4 text-sm text-slate-600 italic truncate max-w-xs">
                                {{ $payment->notes ?? '-' }}
                            </td>
                            <td class="py-4 text-right text-sm font-mono text-slate-900">
                                Rp {{ number_format($payment->principal_paid, 0, ',', '.') }}
                            </td>
                            <td class="py-4 text-right text-sm font-mono text-amber-600 font-medium">
                                Rp {{ number_format($payment->interest_paid, 0, ',', '.') }}
                            </td>
                            <td class="py-4 text-right text-sm font-mono font-bold text-emerald-600">
                                Rp {{ number_format($payment->total_paid, 0, ',', '.') }}
                            </td>
                            <td class="py-4 text-xs text-slate-500">
                                {{ $payment->cashBankAccount->account_name ?? '-' }}
                            </td>
                            <td class="pr-6 py-4 text-center">
                                <form action="{{ route('admin.loans.payments.destroy', [$loan, $payment]) }}" method="POST" 
                                      class="delete-form inline-block"
                                      data-title="Batalkan Pembayaran?"
                                      data-text="Pembayaran sebesar <b>Rp {{ number_format($payment->total_paid, 0, ',', '.') }}</b> akan dihapus dan saldo pinjaman dikembalikan."
                                      data-btn-text="Ya, Batalkan">
                                    @csrf @method('DELETE')
                                    <button type="submit" class="w-8 h-8 flex items-center justify-center rounded-lg bg-white border border-slate-200 text-red-400 hover:text-red-600 hover:bg-red-50 transition shadow-sm" title="Batalkan">
                                        <i class="material-icons text-[16px]">cancel</i>
                                    </button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="px-6 py-12 text-center">
                                <div class="flex flex-col items-center justify-center text-slate-400">
                                    <div class="w-16 h-16 bg-slate-50 rounded-full flex items-center justify-center mb-4">
                                        <i class="material-icons text-4xl opacity-30">payments</i>
                                    </div>
                                    <h3 class="text-base font-bold text-slate-700">Belum ada pembayaran</h3>
                                    <p class="text-sm mt-1">Silakan catat pembayaran cicilan pertama.</p>
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
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
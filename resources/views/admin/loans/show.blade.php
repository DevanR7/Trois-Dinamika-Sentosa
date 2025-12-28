@extends('admin.layouts.app')

@section('title', 'Detail Pinjaman')

@section('content')
    <div class="animate-enter">
        
        {{-- Header Navigation --}}
        <div class="flex items-center justify-between mb-6">
            <div class="flex items-center gap-4">
                <a href="{{ route('admin.loans.index') }}" class="btn-icon btn-secondary">
                    <i class="material-icons">arrow_back</i>
                </a>
                <div>
                    <h1 class="page-title">{{ $loan->lender_name }}</h1>
                    <p class="page-subtitle">Detail status dan riwayat pembayaran pinjaman.</p>
                </div>
            </div>
            
            {{-- Tombol Bayar Cicilan --}}
            @if($loan->status == 'active')
                <a href="{{ route('admin.loan-payments.create', $loan->loan_id) }}" class="btn btn-primary">
                    <i class="material-icons text-sm">payments</i>
                    Bayar Cicilan
                </a>
            @endif
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
            
            {{-- KOLOM KIRI: Informasi Utama --}}
            <div class="lg:col-span-2 space-y-6">
                
                {{-- Kartu Ringkasan Progress --}}
                <div class="card p-6 bg-gradient-to-br from-indigo-600 to-indigo-800 text-white border-none shadow-lg relative overflow-hidden">
                    <div class="relative z-10">
                        <div class="flex justify-between items-start mb-6">
                            <div>
                                <p class="text-indigo-200 text-xs font-bold uppercase tracking-wider mb-1">Sisa Pokok Hutang</p>
                                <h2 class="text-3xl font-extrabold autonumeric" data-a-sign="Rp ">{{ $loan->remaining_balance }}</h2>
                            </div>
                            <span class="badge {{ $loan->status == 'active' ? 'bg-white/20 text-white' : 'bg-emerald-500 text-white' }}">
                                {{ ucfirst($loan->status) }}
                            </span>
                        </div>

                        {{-- Progress Bar --}}
                        @php
                            $paidAmount = $loan->principal_amount - $loan->remaining_balance;
                            $percent = $loan->principal_amount > 0 ? ($paidAmount / $loan->principal_amount) * 100 : 0;
                        @endphp
                        <div class="mb-2 flex justify-between text-xs text-indigo-100 font-medium">
                            <span>Terbayar: Rp {{ number_format($paidAmount, 0, ',', '.') }}</span>
                            <span>Total: Rp {{ number_format($loan->principal_amount, 0, ',', '.') }}</span>
                        </div>
                        <div class="w-full bg-black/20 rounded-full h-2.5">
                            <div class="bg-emerald-400 h-2.5 rounded-full transition-all duration-1000" style="width: {{ $percent }}%"></div>
                        </div>
                    </div>
                    
                    {{-- Decorative Icon --}}
                    <div class="absolute right-0 bottom-0 opacity-10 transform translate-x-1/4 translate-y-1/4">
                        <i class="material-icons text-[150px] text-white">account_balance</i>
                    </div>
                </div>

                {{-- Tabel Riwayat Pembayaran --}}
                <div class="card">
                    <div class="card-header">
                        <h3 class="card-header-title">Riwayat Pembayaran</h3>
                    </div>
                    <div class="table-container">
                        <table class="table-modern">
                            <thead>
                                <tr>
                                    <th>Tanggal</th>
                                    <th>Catatan</th>
                                    <th class="text-right">Pokok</th>
                                    <th class="text-right">Bunga</th>
                                    <th class="text-right">Total Bayar</th>
                                    <th class="text-center">Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($loan->payments as $payment)
                                    <tr>
                                        <td>{{ $payment->payment_date->format('d M Y') }}</td>
                                        <td>
                                            <span class="text-xs text-slate-500">{{ $payment->notes ?? '-' }}</span>
                                        </td>
                                        <td class="text-right font-medium text-slate-700 dark:text-slate-300 autonumeric" data-a-sign="Rp ">
                                            {{ $payment->principal_paid }}
                                        </td>
                                        <td class="text-right text-rose-500 autonumeric" data-a-sign="Rp ">
                                            {{ $payment->interest_paid }}
                                        </td>
                                        <td class="text-right font-bold text-emerald-600 autonumeric" data-a-sign="Rp ">
                                            {{ $payment->total_paid }}
                                        </td>
                                        <td class="text-center">
                                            @can('manage-loans')
                                                <form action="{{ route('admin.loan-payments.destroy', ['loan' => $loan->loan_id, 'payment' => $payment->payment_id]) }}" 
                                                      method="POST" 
                                                      onsubmit="return confirm('Batalkan pembayaran ini? Saldo pokok akan dikembalikan.');">
                                                    @csrf @method('DELETE')
                                                    <button type="submit" class="text-slate-400 hover:text-red-500 transition-colors" title="Batalkan">
                                                        <i class="material-icons text-sm">highlight_off</i>
                                                    </button>
                                                </form>
                                            @endcan
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="6" class="text-center py-6 text-slate-400 italic">Belum ada riwayat pembayaran.</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>

            </div>

            {{-- KOLOM KANAN: Detail Informasi --}}
            <div class="space-y-6">
                <div class="card">
                    <div class="card-header">
                        <h3 class="card-header-title">Informasi Akun</h3>
                    </div>
                    <div class="card-body space-y-4">
                        <div>
                            <span class="block text-xs font-bold text-slate-400 uppercase">Akun Hutang (Liabilitas)</span>
                            <div class="flex items-center gap-2 mt-1">
                                <span class="bg-slate-100 dark:bg-slate-700 px-2 py-1 rounded text-xs font-mono font-bold text-slate-600 dark:text-slate-300">
                                    {{ $loan->loanAccount->account_number ?? 'N/A' }}
                                </span>
                                <span class="text-sm font-medium text-slate-700 dark:text-white">
                                    {{ $loan->loanAccount->account_name ?? '-' }}
                                </span>
                            </div>
                        </div>

                        <div>
                            <span class="block text-xs font-bold text-slate-400 uppercase">Akun Penerimaan (Aset)</span>
                            <div class="flex items-center gap-2 mt-1">
                                <span class="bg-slate-100 dark:bg-slate-700 px-2 py-1 rounded text-xs font-mono font-bold text-slate-600 dark:text-slate-300">
                                    {{ $loan->cashBankAccount->account_number ?? 'N/A' }}
                                </span>
                                <span class="text-sm font-medium text-slate-700 dark:text-white">
                                    {{ $loan->cashBankAccount->account_name ?? '-' }}
                                </span>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="card">
                    <div class="card-header">
                        <h3 class="card-header-title">Detail Lainnya</h3>
                    </div>
                    <div class="card-body text-sm space-y-3">
                        <div class="flex justify-between border-b border-slate-100 dark:border-slate-700 pb-2">
                            <span class="text-slate-500">Tanggal Pinjam</span>
                            <span class="font-medium text-slate-700 dark:text-white">{{ $loan->loan_date->format('d F Y') }}</span>
                        </div>
                        <div class="flex justify-between border-b border-slate-100 dark:border-slate-700 pb-2">
                            <span class="text-slate-500">Dibuat Oleh</span>
                            <span class="font-medium text-slate-700 dark:text-white">{{ $loan->user->full_name ?? 'Sistem' }}</span>
                        </div>
                        <div>
                            <span class="block text-slate-500 mb-1">Catatan:</span>
                            <p class="text-slate-700 dark:text-slate-300 italic bg-slate-50 dark:bg-slate-900 p-3 rounded-lg border border-slate-200 dark:border-slate-700">
                                {{ $loan->description ?? 'Tidak ada catatan.' }}
                            </p>
                        </div>
                    </div>
                </div>
            </div>

        </div>
    </div>
@endsection
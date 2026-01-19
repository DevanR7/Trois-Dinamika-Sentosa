@extends('admin.layouts.app')

@section('title', 'Detail Pinjaman')

@section('content')
    {{-- Header --}}
    <div class="page-header">
        <div>
            <h1 class="page-title">Detail Pinjaman</h1>
            <p class="page-subtitle">Informasi hutang dan riwayat pembayaran cicilan</p>
        </div>
        <div class="flex gap-2">
            <a href="{{ route('admin.loans.index') }}" class="btn btn-secondary">
                <i class="material-icons text-[18px]">arrow_back</i> Kembali
            </a>
            @if($loan->remaining_balance > 0)
                <a href="{{ route('admin.loan-payments.create', $loan->loan_id) }}" class="btn btn-primary">
                    <i class="material-icons text-[18px]">payment</i>
                    Bayar Cicilan
                </a>
            @endif
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 mb-6">
        
        {{-- Card 1: Informasi Utama --}}
        <div class="lg:col-span-2">
            <div class="card h-full">
                <div class="card-header">
                    <h3 class="card-header-title">Informasi Peminjam</h3>
                    @if($loan->status == 'active')
                        <span class="badge badge-warning">Belum Lunas</span>
                    @else
                        <span class="badge badge-success">Lunas</span>
                    @endif
                </div>
                <div class="card-body">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-y-6 gap-x-4">
                        <div>
                            <label class="text-xs text-slate-400 font-bold uppercase block mb-1">Pemberi Pinjaman</label>
                            <p class="text-base font-bold text-slate-700 dark:text-slate-200">{{ $loan->lender_name }}</p>
                        </div>
                        <div>
                            <label class="text-xs text-slate-400 font-bold uppercase block mb-1">Tanggal Terima</label>
                            <p class="text-base font-medium text-slate-700 dark:text-slate-200">{{ $loan->loan_date->format('d F Y') }}</p>
                        </div>
                        <div>
                            <label class="text-xs text-slate-400 font-bold uppercase block mb-1">Akun Hutang (Liabilitas)</label>
                            <p class="text-sm font-mono bg-slate-100 dark:bg-slate-700 px-2 py-1 rounded inline-block">
                                {{ $loan->loanAccount->account_number ?? '-' }} - {{ $loan->loanAccount->account_name ?? 'N/A' }}
                            </p>
                        </div>
                        <div>
                            <label class="text-xs text-slate-400 font-bold uppercase block mb-1">Keterangan</label>
                            <p class="text-sm text-slate-600 dark:text-slate-400">{{ $loan->description ?: '-' }}</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- Card 2: Ringkasan Saldo --}}
        <div class="lg:col-span-1">
            <div class="card h-full bg-gradient-to-br from-slate-800 to-[#0f172a] text-white border-none shadow-lg">
                <div class="card-body flex flex-col justify-center h-full space-y-6">
                    <div>
                        <p class="text-slate-400 text-xs font-bold uppercase mb-1">Total Pokok Pinjaman</p>
                        <h2 class="text-2xl font-bold">Rp {{ number_format($loan->principal_amount, 0, ',', '.') }}</h2>
                    </div>
                    
                    <div class="h-px bg-slate-700 w-full"></div>

                    <div class="flex justify-between items-end">
                        <div>
                            <p class="text-slate-400 text-xs font-bold uppercase mb-1">Pokok Dibayar</p>
                            <p class="text-lg font-medium text-emerald-400">
                                Rp {{ number_format($loan->principal_amount - $loan->remaining_balance, 0, ',', '.') }}
                            </p>
                        </div>
                        <div class="text-right">
                            <p class="text-slate-400 text-xs font-bold uppercase mb-1">Sisa Hutang</p>
                            <p class="text-xl font-bold text-white">
                                Rp {{ number_format($loan->remaining_balance, 0, ',', '.') }}
                            </p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Tabel Riwayat Pembayaran --}}
    <div class="card card-plain">
        <div class="card-header">
            <h3 class="card-header-title">Riwayat Pembayaran Cicilan</h3>
        </div>
        <div class="table-container">
            <table class="table-modern">
                <thead>
                    <tr>
                        <th>Tanggal Bayar</th>
                        <th class="text-right">Pokok Dibayar</th>
                        <th class="text-right">Bunga Dibayar</th>
                        <th class="text-right">Total Keluar</th>
                        <th>Sumber Dana</th>
                        <th>Keterangan</th>
                        <th class="text-end">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($loan->payments as $payment)
                    <tr>
                        <td>{{ $payment->payment_date->format('d M Y') }}</td>
                        <td class="text-right font-mono font-medium text-emerald-600 dark:text-emerald-400">
                            Rp {{ number_format($payment->principal_paid, 0, ',', '.') }}
                        </td>
                        <td class="text-right font-mono text-rose-600 dark:text-rose-400">
                            Rp {{ number_format($payment->interest_paid, 0, ',', '.') }}
                        </td>
                        <td class="text-right font-mono font-bold">
                            Rp {{ number_format($payment->total_paid, 0, ',', '.') }}
                        </td>
                        <td>
                            <span class="text-xs font-mono bg-slate-100 dark:bg-slate-700 px-2 py-1 rounded">
                                {{ $payment->cashBankAccount->account_name ?? 'Bank' }}
                            </span>
                        </td>
                        <td class="text-xs text-slate-500 max-w-[200px] truncate">
                            {{ $payment->notes }}
                        </td>
                        <td class="text-end">
                            {{-- Delete Payment (Reversal) --}}
                            <button type="button" 
                                    class="btn-action btn-action-delete"
                                    title="Batalkan Pembayaran"
                                    onclick="confirmDialog({
                                        title: 'Batalkan Pembayaran?',
                                        text: 'Saldo hutang akan dikembalikan seperti sebelum pembayaran ini.',
                                        icon: 'warning',
                                        confirmText: 'Ya, Batalkan',
                                        confirmColor: 'danger'
                                    }).then((result) => {
                                        if (result.isConfirmed) document.getElementById('delete-payment-{{ $payment->payment_id }}').submit();
                                    })">
                                <i class="material-icons">undo</i>
                            </button>
                            <form id="delete-payment-{{ $payment->payment_id }}" 
                                  action="{{ route('admin.loan-payments.destroy', ['loan' => $loan->loan_id, 'payment' => $payment->payment_id]) }}" 
                                  method="POST" class="hidden">
                                @csrf
                                @method('DELETE')
                            </form>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="7" class="text-center py-8 text-slate-400">
                            Belum ada riwayat pembayaran cicilan.
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
@endsection
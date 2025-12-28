@extends('admin.layouts.app')

@section('title', 'Verifikasi Pembayaran Massal')

@section('content')
<div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 mb-6">
    <div>
        <h1 class="text-2xl font-bold text-slate-800 dark:text-white">Verifikasi Pending</h1>
        <p class="text-sm text-slate-500 dark:text-slate-400 mt-1">Daftar pembayaran massal yang menunggu persetujuan admin.</p>
    </div>
    <a href="{{ route('admin.bulk-sales-payments.index') }}" class="btn btn-secondary">
        <i class="material-icons text-lg mr-1">arrow_back</i>
        Kembali
    </a>
</div>

<div class="card">
    <div class="table-container">
        <table class="table-modern">
            <thead>
                <tr>
                    <th>Waktu Dibuat</th>
                    <th>Pelanggan</th>
                    <th>Metode Pembayaran</th>
                    <th>Akun Tujuan</th>
                    <th class="text-right">Total (Cash)</th>
                    <th class="text-center">Bukti</th>
                    <th class="text-center">Aksi</th>
                </tr>
            </thead>
            <tbody>
                @forelse($pendingBulkPayments as $payment)
                    <tr>
                        <td>
                            <div class="text-sm font-medium">{{ $payment->created_at->format('d M Y') }}</div>
                            <div class="text-xs text-slate-500">{{ $payment->created_at->format('H:i') }}</div>
                        </td>
                        <td>
                            <div class="font-bold text-slate-700 dark:text-slate-200">{{ $payment->client->client_name }}</div>
                        </td>
                        <td>
                            @if($payment->paymentMethod)
                                <span class="badge badge-info">{{ $payment->paymentMethod->name }}</span>
                            @else
                                <span class="text-slate-400 italic">Unknown</span>
                            @endif
                        </td>
                        <td>
                            @if($payment->companyBankAccount)
                                {{ $payment->companyBankAccount->bank_name }}
                            @else
                                -
                            @endif
                        </td>
                        <td class="text-right font-bold text-indigo-600">
                            Rp {{ number_format($payment->total_amount, 0, ',', '.') }}
                        </td>
                        <td class="text-center">
                            @if($payment->proof_of_payment_path)
                                <a href="{{ asset('storage/' . $payment->proof_of_payment_path) }}" target="_blank" class="text-indigo-600 hover:underline text-xs">
                                    <i class="material-icons text-base align-middle">image</i> Lihat
                                </a>
                            @else
                                <span class="text-slate-400 text-xs">-</span>
                            @endif
                        </td>
                        <td class="text-center">
                            <a href="{{ route('admin.bulk-sales-payments.showPending', $payment->bulk_sales_payment_id) }}" 
                               class="btn btn-sm btn-primary">
                                Review
                            </a>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7" class="text-center py-12 text-slate-500">
                            <i class="material-icons text-4xl mb-2 text-emerald-500">check_circle</i>
                            <p>Tidak ada pembayaran pending. Semua beres!</p>
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
    
    @if($pendingBulkPayments->hasPages())
        <div class="card-footer">
            {{ $pendingBulkPayments->links() }}
        </div>
    @endif
</div>
@endsection
@extends('admin.layouts.app')

@section('title', 'Detail Bulk Payment #' . $bulkSalesPayment->bulk_sales_payment_id)

@section('content')
<div class="page-header">
    <div>
        <h1 class="page-title">Detail Pembayaran Massal</h1>
        <div class="flex items-center gap-2 mt-2">
            <span class="badge {{ $bulkSalesPayment->status == 'completed' || $bulkSalesPayment->status == 'approved' ? 'badge-success' : 'badge-secondary' }}">
                {{ ucfirst($bulkSalesPayment->status) }}
            </span>
            <span class="text-sm text-slate-500">{{ $bulkSalesPayment->created_at->format('d M Y H:i') }}</span>
        </div>
    </div>
    <a href="{{ route('admin.bulk-sales-payments.index') }}" class="btn btn-secondary">
        <i class="material-icons text-lg mr-1">arrow_back</i> Kembali
    </a>
</div>

<div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
    
    {{-- Left: Payment Info --}}
    <div class="space-y-6">
        <div class="card">
            <div class="card-header">
                <h3 class="card-header-title">Informasi</h3>
            </div>
            <div class="card-body text-sm space-y-4">
                <div>
                    <label class="section-title">Pelanggan</label>
                    <div class="font-bold text-base">{{ $bulkSalesPayment->client->client_name }}</div>
                </div>
                
                <div>
                    <label class="section-title">Metode & Akun</label>
                    <div>{{ $bulkSalesPayment->paymentMethod->name ?? 'Kredit/Deposit' }}</div>
                    @if($bulkSalesPayment->companyBankAccount)
                        <div class="text-slate-500">
                            {{ $bulkSalesPayment->companyBankAccount->bank_name ?? '' }} 
                            {{ $bulkSalesPayment->companyBankAccount->account_name ?? '' }}
                        </div>
                    @endif
                </div>

                <div>
                    <label class="section-title">Total Nominal (Cash)</label>
                    <div class="text-xl font-bold text-indigo-600">
                        Rp {{ number_format($bulkSalesPayment->total_amount, 0, ',', '.') }}
                    </div>
                </div>

                @if($bulkSalesPayment->notes)
                <div>
                    <label class="section-title">Catatan</label>
                    <div class="p-3 bg-slate-50 dark:bg-slate-800/50 rounded-lg text-slate-600 dark:text-slate-300">
                        {{ $bulkSalesPayment->notes }}
                    </div>
                </div>
                @endif

                <div class="pt-4 border-t border-slate-100 dark:border-slate-700">
                    <div class="text-xs text-slate-400">
                        Dibuat oleh: {{ $bulkSalesPayment->processedByUser->full_name ?? 'System' }}
                    </div>
                    @if($bulkSalesPayment->approvedByUser)
                    <div class="text-xs text-emerald-600 mt-1">
                        Disetujui oleh: {{ $bulkSalesPayment->approvedByUser->full_name }}
                    </div>
                    @endif
                </div>
            </div>
        </div>

        @if($bulkSalesPayment->proof_of_payment_path)
            <div class="card">
                <div class="card-header">
                    <h3 class="card-header-title">Bukti Pembayaran</h3>
                </div>
                <div class="card-body p-0">
                    <a href="{{ asset('storage/' . $bulkSalesPayment->proof_of_payment_path) }}" target="_blank">
                        <img src="{{ asset('storage/' . $bulkSalesPayment->proof_of_payment_path) }}" 
                             class="w-full rounded-b-xl hover:opacity-90 transition-opacity">
                    </a>
                </div>
            </div>
        @endif
    </div>

    {{-- Right: Allocation Details --}}
    <div class="lg:col-span-2">
        <div class="card">
            <div class="card-header bg-slate-50 dark:bg-slate-800/50">
                <h3 class="card-header-title">Rincian Alokasi Dana</h3>
            </div>
            <div class="table-container">
                <table class="table-modern">
                    <thead>
                        <tr>
                            <th>No. Invoice</th>
                            <th>Tanggal Invoice</th>
                            <th class="text-right">Dialokasikan</th>
                            <th class="text-center">Status Inv.</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($bulkSalesPayment->payments as $payment)
                            <tr>
                                <td class="font-medium text-indigo-600">
                                    <a href="{{ route('admin.invoices.show', $payment->salesInvoice->invoice_id) }}" class="hover:underline">
                                        {{ $payment->salesInvoice->invoice_number }}
                                    </a>
                                </td>
                                <td>{{ $payment->salesInvoice->order_date->format('d/m/Y') }}</td>
                                <td class="text-right font-bold">
                                    Rp {{ number_format($payment->amount, 0, ',', '.') }}
                                </td>
                                <td class="text-center">
                                    {{-- Tampilkan Status Pembayaran INI, bukan Status Invoice secara umum --}}
                                    @if($payment->status == 'completed')
                                        <span class="badge badge-success">Berhasil</span>
                                    @elseif($payment->status == 'pending_clearance')
                                        <span class="badge badge-warning">Kliring</span>
                                    @else
                                        <span class="badge badge-secondary">{{ ucfirst($payment->status) }}</span>
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                    <tfoot>
                        <tr class="bg-slate-50 dark:bg-slate-800/50 font-bold">
                            <td colspan="2" class="text-right px-6 py-4">Total Terbayar (Termasuk Kredit):</td>
                            <td class="text-right px-6 py-4">
                                {{-- Total pembayaran individual --}}
                                Rp {{ number_format($bulkSalesPayment->payments->sum('amount'), 0, ',', '.') }}
                            </td>
                            <td></td>
                        </tr>
                    </tfoot>
                </table>
            </div>
        </div>
    </div>
</div>
@endsection
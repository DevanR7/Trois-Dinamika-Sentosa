@extends('admin.layouts.app')

@section('title', 'Review Pembayaran Massal #' . $bulkSalesPayment->bulk_sales_payment_id)

@section('content')
<div class="page-header">
    <div>
        <h1 class="page-title">Review Pembayaran Massal</h1>
        <p class="text-sm text-slate-500">Ref: #{{ $bulkSalesPayment->bulk_sales_payment_id }}</p>
    </div>
    <a href="{{ route('admin.bulk-sales-payments.pending') }}" class="btn btn-secondary">
        <i class="material-icons text-lg mr-1">arrow_back</i>
        Kembali
    </a>
</div>

<div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
    
    {{-- Info & Bukti --}}
    <div class="space-y-6">
        <div class="card">
            <div class="card-header">
                <h3 class="card-header-title">Info Pembayaran</h3>
            </div>
            <div class="card-body text-sm space-y-3">
                <div class="flex justify-between border-b border-slate-100 dark:border-slate-700 pb-2">
                    <span class="text-slate-500">Pelanggan</span>
                    <span class="font-bold">{{ $bulkSalesPayment->client->client_name }}</span>
                </div>
                <div class="flex justify-between border-b border-slate-100 dark:border-slate-700 pb-2">
                    <span class="text-slate-500">Tanggal</span>
                    <span class="font-medium">{{ $bulkSalesPayment->payment_date->format('d M Y') }}</span>
                </div>
                <div class="flex justify-between border-b border-slate-100 dark:border-slate-700 pb-2">
                    <span class="text-slate-500">Metode</span>
                    <span class="font-medium">{{ $bulkSalesPayment->paymentMethod->name ?? '-' }}</span>
                </div>
                <div class="flex justify-between border-b border-slate-100 dark:border-slate-700 pb-2">
                    <span class="text-slate-500">No. Referensi</span>
                    <span class="font-mono">{{ $bulkSalesPayment->reference_number ?? '-' }}</span>
                </div>
                <div class="flex justify-between items-center pt-2">
                    <span class="text-slate-500">Total Transfer (Cash)</span>
                    <span class="text-lg font-bold text-indigo-600">
                        Rp {{ number_format($bulkSalesPayment->total_amount, 0, ',', '.') }}
                    </span>
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
                             alt="Bukti Bayar" 
                             class="w-full h-auto rounded-b-xl hover:opacity-90 transition-opacity">
                    </a>
                </div>
            </div>
        @endif
    </div>

    {{-- Invoice & Actions --}}
    <div class="lg:col-span-2 space-y-6">
        
        {{-- Daftar Invoice yang dibayar --}}
        <div class="card">
            <div class="card-header bg-slate-50 dark:bg-slate-800/50">
                <h3 class="card-header-title">Alokasi Tagihan</h3>
            </div>
            <div class="table-container">
                <table class="table-modern">
                    <thead>
                        <tr>
                            <th>No. Invoice</th>
                            <th>Tanggal</th>
                            <th class="text-right">Sisa Tagihan</th>
                            {{-- Kita tidak menampilkan detail alokasi per invoice karena di Pending belum dihitung final --}}
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($invoices as $inv)
                            <tr>
                                <td class="font-medium">{{ $inv->invoice_number }}</td>
                                <td>{{ $inv->order_date->format('d/m/Y') }}</td>
                                <td class="text-right text-slate-700 dark:text-slate-300">
                                    Rp {{ number_format($inv->remaining_balance, 0, ',', '.') }}
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            @if(isset($details['credit_amount_to_use']) && $details['credit_amount_to_use'] > 0)
                <div class="p-4 bg-indigo-50 dark:bg-indigo-900/20 text-sm text-indigo-700 dark:text-indigo-300 border-t border-indigo-100 dark:border-indigo-800">
                    <i class="material-icons text-base align-bottom mr-1">info</i>
                    Pembayaran ini juga akan menggunakan <strong>Saldo Kredit</strong> sebesar 
                    <strong>Rp {{ number_format($details['credit_amount_to_use'], 0, ',', '.') }}</strong>
                </div>
            @endif
        </div>

        {{-- Action Buttons --}}
        <div class="card border border-slate-200 dark:border-slate-700 shadow-lg">
            <div class="card-body">
                <div class="flex flex-col md:flex-row gap-4 justify-end">
                    
                    {{-- Form Reject --}}
                    <form action="{{ route('admin.bulk-sales-payments.reject', $bulkSalesPayment->bulk_sales_payment_id) }}" method="POST" class="w-full md:w-auto" id="form-reject">
                        @csrf
                        <button type="button" onclick="confirmReject()" class="btn btn-danger w-full">
                            <i class="material-icons text-lg mr-1">close</i>
                            Tolak Pembayaran
                        </button>
                        <input type="hidden" name="reason" id="reject-reason">
                    </form>

                    {{-- Form Approve --}}
                    <form action="{{ route('admin.bulk-sales-payments.approve', $bulkSalesPayment->bulk_sales_payment_id) }}" method="POST" class="w-full md:w-1/2" id="form-approve">
                        @csrf
                        <div class="flex flex-col gap-3">
                            <select name="company_bank_account_id" class="tom-select w-full" required>
                                <option value="">Pilih Akun Bank Penerima...</option>
                                @foreach($companyBankAccounts as $bank)
                                    <option value="{{ $bank->company_bank_account_id }}" 
                                        {{ $bulkSalesPayment->company_bank_account_id == $bank->company_bank_account_id ? 'selected' : '' }}>
                                        {{ $bank->bank_name }} - {{ $bank->account_name }}
                                    </option>
                                @endforeach
                            </select>
                            <button type="submit" class="btn btn-success w-full justify-center">
                                <i class="material-icons text-lg mr-1">check</i>
                                Setujui & Posting Jurnal
                            </button>
                        </div>
                    </form>

                </div>
            </div>
        </div>

    </div>
</div>

<script>
    function confirmReject() {
        window.confirmDialog({
            title: 'Tolak Pembayaran?',
            text: "Masukkan alasan penolakan:",
            input: 'text',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#ef4444',
            cancelButtonColor: '#6b7280',
            confirmButtonText: 'Ya, Tolak',
            cancelButtonText: 'Batal',
            inputValidator: (value) => {
                if (!value) {
                    return 'Alasan harus diisi!'
                }
            }
        }).then((result) => {
            if (result.isConfirmed) {
                document.getElementById('reject-reason').value = result.value;
                document.getElementById('form-reject').submit();
            }
        })
    }
</script>
@endsection
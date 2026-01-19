@extends('admin.layouts.app')

@section('title', 'Detail Pembayaran Massal #' . $bulkSalesPayment->payment_number)

@section('content')
<div class="flex flex-col gap-6" x-data="{ showProofModal: false }">

    {{-- HEADER --}}
    <div class="flex flex-col md:flex-row md:items-center justify-between gap-4">
        <div>
            <div class="flex items-center gap-3">
                <a href="{{ route('admin.bulk-sales-payments.index') }}" class="btn-icon btn-secondary">
                    <i class="material-icons text-lg">arrow_back</i>
                </a>
                <div>
                    <div class="flex items-center gap-3">
                        <h1 class="text-xl font-bold text-slate-800 dark:text-white">
                            Pembayaran Massal <span class="text-indigo-600">#{{ $bulkSalesPayment->payment_number }}</span>
                        </h1>
                        @php
                            $statusClass = match($bulkSalesPayment->status) {
                                'completed' => 'bg-emerald-100 text-emerald-700 border-emerald-200',
                                'pending_verification' => 'bg-amber-100 text-amber-700 border-amber-200',
                                'rejected' => 'bg-rose-100 text-rose-700 border-rose-200',
                                default => 'bg-slate-100 text-slate-700 border-slate-200'
                            };
                            $statusLabel = match($bulkSalesPayment->status) {
                                'completed' => 'Selesai',
                                'pending_verification' => 'Menunggu Verifikasi',
                                'rejected' => 'Ditolak',
                                default => ucfirst($bulkSalesPayment->status)
                            };
                        @endphp
                        <span class="px-2.5 py-0.5 rounded-full text-xs font-bold uppercase border {{ $statusClass }}">
                            {{ $statusLabel }}
                        </span>
                    </div>
                    <p class="text-sm text-slate-500 mt-1 flex items-center gap-2">
                        <i class="material-icons text-xs">event</i> {{ $bulkSalesPayment->payment_date->format('d F Y') }}
                        <span class="text-slate-300">|</span>
                        <i class="material-icons text-xs">account_circle</i> Diproses oleh {{ $bulkSalesPayment->processedByUser->full_name ?? 'Sistem' }}
                    </p>
                </div>
            </div>
        </div>

        {{-- ACTIONS --}}
        <div class="flex flex-wrap items-center gap-2">
            
            {{-- Tombol Approval (Hanya jika Pending) --}}
            @if(in_array($bulkSalesPayment->status, ['pending_verification']))
                @can('review-bulk-payments')
                    <button type="button" 
                            onclick="confirmAction('{{ route('admin.bulk-sales-payments.approve', $bulkSalesPayment->bulk_sales_payment_id) }}', 'Setujui Pembayaran?', 'Dana akan dicatat masuk ke Kas/Bank dan pelunasan invoice dibukukan.', 'success')" 
                            class="btn btn-primary bg-emerald-600 hover:bg-emerald-700 border-transparent text-white">
                        <i class="material-icons mr-2">check_circle</i> Setujui
                    </button>
                    
                    <button type="button" 
                            onclick="promptReject('{{ route('admin.bulk-sales-payments.reject', $bulkSalesPayment->bulk_sales_payment_id) }}')" 
                            class="btn btn-danger">
                        <i class="material-icons mr-2">cancel</i> Tolak
                    </button>
                @endcan
            @endif

            {{-- Tombol Hapus (Hanya jika Completed/Pending) --}}
            @if($bulkSalesPayment->status !== 'rejected')
                @can('delete-invoices')
                    <button type="button" 
                            onclick="customDeleteBulk('{{ route('admin.bulk-sales-payments.destroy', $bulkSalesPayment->bulk_sales_payment_id) }}')"
                            class="btn btn-secondary text-rose-600 border-rose-200 hover:bg-rose-50">
                        <i class="material-icons mr-2">delete</i> Batalkan / Hapus
                    </button>
                @endcan
            @endif
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        
        {{-- KOLOM KIRI: DETAIL UTAMA (2/3) --}}
        <div class="lg:col-span-2 space-y-6">
            
            {{-- 1. INFORMASI SUMBER DANA --}}
            <div class="card p-0 overflow-hidden">
                <div class="card-header bg-slate-50 dark:bg-slate-800/50">
                    <h3 class="card-header-title">Sumber Dana</h3>
                </div>
                <div class="p-5 grid grid-cols-1 md:grid-cols-2 gap-6">
                    {{-- Klien Info --}}
                    <div class="flex items-start gap-4">
                        <div class="w-12 h-12 rounded-full bg-indigo-50 text-indigo-600 flex items-center justify-center shrink-0">
                            <i class="material-icons text-2xl">person</i>
                        </div>
                        <div>
                            <p class="text-xs text-slate-500 uppercase font-bold tracking-wider">Klien</p>
                            <h4 class="text-base font-bold text-slate-800 dark:text-white">{{ $bulkSalesPayment->client->client_name }}</h4>
                            <p class="text-sm text-slate-500">{{ $bulkSalesPayment->client->phone_number ?? '-' }}</p>
                        </div>
                    </div>

                    {{-- Metode & Bank --}}
                    <div class="flex items-start gap-4">
                        <div class="w-12 h-12 rounded-full bg-emerald-50 text-emerald-600 flex items-center justify-center shrink-0">
                            <i class="material-icons text-2xl">account_balance_wallet</i>
                        </div>
                        <div>
                            <p class="text-xs text-slate-500 uppercase font-bold tracking-wider">Metode Pembayaran</p>
                            <h4 class="text-base font-bold text-slate-800 dark:text-white">
                                {{ $bulkSalesPayment->paymentMethod->name ?? 'Manual' }}
                            </h4>
                            <p class="text-sm text-slate-500">
                                {{ $bulkSalesPayment->companyBankAccount->bank_name ?? '-' }} 
                                ({{ $bulkSalesPayment->companyBankAccount->account_number ?? '-' }})
                            </p>
                            @if($bulkSalesPayment->reference_number)
                                <p class="text-xs text-indigo-500 mt-1 font-mono bg-indigo-50 inline-block px-1 rounded">Ref: {{ $bulkSalesPayment->reference_number }}</p>
                            @endif
                        </div>
                    </div>
                </div>
                
                {{-- Kalkulasi Dana --}}
                <div class="bg-indigo-50/30 border-t border-slate-200 dark:border-slate-700 p-5">
                    @php
                        $cashAmount = $bulkSalesPayment->total_amount;
                        $creditAmount = $details['credit_amount_to_use'] ?? 0;
                        $totalFund = $cashAmount + $creditAmount;
                        
                        // Hitung alokasi aktual ke invoice
                        $allocatedAmount = $bulkSalesPayment->payments->sum('amount');
                        
                        // Overpayment = Total Dana - Total Invoice Terbayar
                        $overpayment = max(0, $totalFund - $allocatedAmount);
                    @endphp

                    <div class="grid grid-cols-1 sm:grid-cols-3 gap-4 text-center divide-x divide-slate-200 dark:divide-slate-700">
                        <div>
                            <p class="text-xs text-slate-500 mb-1">Uang Masuk (Cash)</p>
                            <p class="text-lg font-mono font-bold text-slate-700 dark:text-white">Rp {{ number_format($cashAmount, 0, ',', '.') }}</p>
                        </div>
                        <div>
                            <p class="text-xs text-slate-500 mb-1">Potong Deposit</p>
                            <p class="text-lg font-mono font-bold text-emerald-600">Rp {{ number_format($creditAmount, 0, ',', '.') }}</p>
                        </div>
                        <div>
                            <p class="text-xs text-slate-500 mb-1">Total Dana Tersedia</p>
                            <p class="text-lg font-mono font-bold text-indigo-600">Rp {{ number_format($totalFund, 0, ',', '.') }}</p>
                        </div>
                    </div>
                </div>
            </div>

            {{-- 2. ALOKASI INVOICE --}}
            <div class="card overflow-hidden">
                <div class="card-header bg-slate-50 dark:bg-slate-800/50 flex justify-between items-center">
                    <h3 class="card-header-title">Alokasi Pembayaran</h3>
                    <span class="badge badge-primary">{{ $bulkSalesPayment->payments->count() }} Invoice</span>
                </div>
                
                <div class="table-container border-0 shadow-none">
                    <table class="table-modern">
                        <thead>
                            <tr>
                                <th>No. Invoice</th>
                                <th>Tanggal Inv</th>
                                <th class="text-right">Total Tagihan</th>
                                <th class="text-right bg-emerald-50/50 text-emerald-700">Dibayar (Alokasi)</th>
                                <th class="text-center">Status Inv</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($bulkSalesPayment->payments as $payment)
                                @php $inv = $payment->salesInvoice; @endphp
                                <tr class="hover:bg-slate-50 transition-colors">
                                    <td>
                                        <a href="{{ route('admin.invoices.show', $inv->invoice_id) }}" class="font-bold text-indigo-600 hover:underline flex items-center gap-1">
                                            {{ $inv->invoice_number }}
                                            <i class="material-icons text-[12px]">open_in_new</i>
                                        </a>
                                    </td>
                                    <td class="text-slate-500 text-xs">
                                        {{ $inv->order_date->format('d/m/Y') }}
                                    </td>
                                    <td class="text-right font-mono text-slate-600">
                                        Rp {{ number_format($inv->total_amount, 0, ',', '.') }}
                                    </td>
                                    <td class="text-right font-mono font-bold text-emerald-600 bg-emerald-50/30">
                                        Rp {{ number_format($payment->amount, 0, ',', '.') }}
                                    </td>
                                    <td class="text-center">
                                        @if($inv->status == 'paid')
                                            <span class="badge badge-success">Lunas</span>
                                        @else
                                            <span class="badge badge-warning">Partial</span>
                                        @endif
                                    </td>
                                </tr>
                            @endforeach
                            
                            {{-- Baris Overpayment (Jika Ada) --}}
                            @if($overpayment > 0.01)
                                <tr class="bg-indigo-50/50">
                                    <td colspan="3" class="text-right font-bold text-indigo-600 uppercase text-xs py-3">
                                        <i class="material-icons text-sm align-middle mr-1">savings</i> Kelebihan Bayar (Masuk Deposit)
                                    </td>
                                    <td class="text-right font-mono font-bold text-indigo-600 py-3">
                                        Rp {{ number_format($overpayment, 0, ',', '.') }}
                                    </td>
                                    <td></td>
                                </tr>
                            @endif
                        </tbody>
                        <tfoot class="bg-slate-100 dark:bg-slate-700 font-bold border-t border-slate-300 dark:border-slate-600">
                            <tr>
                                <td colspan="3" class="text-right px-6 py-3">TOTAL ALOKASI</td>
                                <td class="text-right px-6 py-3 font-mono text-slate-800 dark:text-white">
                                    Rp {{ number_format($totalFund, 0, ',', '.') }}
                                </td>
                                <td></td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            </div>

        </div>

        {{-- KOLOM KANAN: META INFO (1/3) --}}
        <div class="lg:col-span-1 space-y-6">
            
            {{-- BUKTI TRANSFER --}}
            <div class="card p-5">
                <h3 class="font-bold text-slate-800 dark:text-white mb-3">Bukti Pembayaran</h3>
                
                @if($bulkSalesPayment->proof_of_payment_path)
                    <div class="relative group cursor-pointer overflow-hidden rounded-xl border border-slate-200 shadow-sm" @click="showProofModal = true">
                        <img src="{{ asset('storage/' . $bulkSalesPayment->proof_of_payment_path) }}" 
                             alt="Bukti Transfer" 
                             class="w-full h-48 object-cover transition-transform duration-500 group-hover:scale-105">
                        
                        <div class="absolute inset-0 bg-black/40 opacity-0 group-hover:opacity-100 transition-opacity flex items-center justify-center">
                            <span class="text-white font-bold flex items-center gap-2">
                                <i class="material-icons">zoom_in</i> Lihat Penuh
                            </span>
                        </div>
                    </div>
                    <div class="mt-3 text-center">
                        <a href="{{ asset('storage/' . $bulkSalesPayment->proof_of_payment_path) }}" download class="text-xs text-indigo-600 hover:underline font-bold">
                            Download Gambar
                        </a>
                    </div>
                @else
                    <div class="h-32 bg-slate-50 dark:bg-slate-800 border-2 border-dashed border-slate-200 dark:border-slate-600 rounded-xl flex flex-col items-center justify-center text-slate-400">
                        <i class="material-icons text-3xl mb-1">image_not_supported</i>
                        <span class="text-xs">Tidak ada bukti lampiran</span>
                    </div>
                @endif
            </div>

            {{-- CATATAN & LOG --}}
            <div class="card p-5">
                <h3 class="font-bold text-slate-800 dark:text-white mb-3">Catatan</h3>
                <div class="bg-slate-50 dark:bg-slate-800/50 p-3 rounded-lg border border-slate-200 dark:border-slate-700 min-h-[80px] text-sm text-slate-600">
                    {{ $bulkSalesPayment->notes ?? 'Tidak ada catatan.' }}
                </div>

                @if($bulkSalesPayment->status == 'rejected')
                    <div class="mt-4 bg-rose-50 p-3 rounded-lg border border-rose-100">
                        <p class="text-xs font-bold text-rose-600 uppercase mb-1">Alasan Penolakan:</p>
                        <p class="text-sm text-rose-700">{{ $bulkSalesPayment->rejection_reason }}</p>
                        <p class="text-[10px] text-rose-500 mt-2">Oleh: {{ $bulkSalesPayment->rejectedByUser->full_name ?? '-' }}</p>
                    </div>
                @endif
            </div>

        </div>
    </div>

    {{-- MODAL ZOOM BUKTI --}}
    <div x-show="showProofModal" 
         x-transition.opacity
         class="fixed inset-0 z-50 bg-black/90 flex items-center justify-center p-4 backdrop-blur-sm"
         style="display: none;">
        
        <div class="relative max-w-4xl w-full max-h-screen" @click.outside="showProofModal = false">
            <button @click="showProofModal = false" class="absolute -top-10 right-0 text-white hover:text-rose-400 transition-colors">
                <i class="material-icons text-4xl">close</i>
            </button>
            
            @if($bulkSalesPayment->proof_of_payment_path)
                <img src="{{ asset('storage/' . $bulkSalesPayment->proof_of_payment_path) }}" 
                     class="w-full h-auto max-h-[85vh] object-contain rounded shadow-2xl">
            @endif
        </div>
    </div>

</div>

@push('scripts')
<script>
    // Fungsi Approval
    function confirmAction(url, title, text, color) {
        confirmDialog({
            title: title,
            text: text,
            icon: 'question',
            confirmText: 'Ya, Lanjutkan',
            confirmColor: color
        }).then((result) => {
            if (result.isConfirmed) {
                let form = document.createElement('form');
                form.action = url;
                form.method = 'POST';
                form.innerHTML = `@csrf`;
                document.body.appendChild(form);
                form.submit();
            }
        });
    }

    // Fungsi Reject dengan Alasan
    function promptReject(url) {
        Swal.fire({
            title: 'Tolak Pembayaran?',
            text: "Masukkan alasan penolakan:",
            input: 'textarea',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#e11d48', // Rose-600
            cancelButtonColor: '#64748b',
            confirmButtonText: 'Tolak',
            inputValidator: (value) => {
                if (!value) {
                    return 'Alasan wajib diisi!'
                }
            }
        }).then((result) => {
            if (result.isConfirmed) {
                let form = document.createElement('form');
                form.action = url;
                form.method = 'POST';
                form.innerHTML = `@csrf <input type="hidden" name="reason" value="${result.value}">`;
                document.body.appendChild(form);
                form.submit();
            }
        });
    }

    // Fungsi Delete Aman
    function customDeleteBulk(url) {
        confirmDialog({
            title: 'Hapus & Batalkan?',
            text: 'Tindakan ini akan membatalkan pelunasan semua invoice terkait dan menghapus jurnal akuntansi.',
            icon: 'warning',
            confirmText: 'Ya, Hapus Permanen',
            confirmColor: 'danger'
        }).then((result) => {
            if (result.isConfirmed) {
                let form = document.createElement('form');
                form.action = url;
                form.method = 'POST';
                form.innerHTML = `@csrf @method('DELETE')`;
                document.body.appendChild(form);
                form.submit();
            }
        });
    }
</script>
@endpush

@endsection
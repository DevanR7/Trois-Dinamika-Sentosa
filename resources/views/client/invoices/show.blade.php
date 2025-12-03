@extends('client.layouts.app')

@section('title', 'Detail Invoice #' . $invoice->invoice_number)

@push('styles')
{{-- Select2 --}}
<link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/select2-bootstrap-5-theme@1.3.0/dist/select2-bootstrap-5-theme.min.css" />
<style>
    /* Fix Z-Index Select2 agar muncul di atas Modal Tailwind */
    .select2-container--open { z-index: 9999999 !important; }
    
    /* Animasi Modal */
    .modal-content-anim { animation: modalPop 0.3s ease-out forwards; }
    @keyframes modalPop {
        0% { transform: scale(0.95); opacity: 0; }
        100% { transform: scale(1); opacity: 1; }
    }
</style>
@endpush

@php
    $client = Auth::guard('client')->user();
    $allPayments = $invoice->payments()->orderBy('created_at', 'desc')->get();

    // 1. Logika Keuangan
    $sisaTagihan = $invoice->remaining_balance;
    $totalReturDipotong = $invoice->total_deducting_returns;
    $totalReturKredit = $invoice->returns->where('return_handling_type', 'store_as_credit')->sum('total_amount');
    
    $saldoKreditKlien = $client->balance; 
    $saldoKreditPending = $client->pending_balance; 

    // Cek Pembayaran Pending
    $pendingPayments = $allPayments->where('status', 'pending_verification');
    $pendingPaymentAmount = $pendingPayments->sum('amount');
    
    // Sisa Tagihan Final untuk UI
    $finalBalanceUI = $sisaTagihan - $pendingPaymentAmount;
    $canPay = $finalBalanceUI > 0.01;

    // 2. Helper ID Metode Bayar
    $pMethods = $paymentMethods ?? collect(); 
    $transferMethodId = $pMethods->firstWhere(fn($m) => str_contains(strtolower($m->name), 'transfer'))->payment_method_id ?? '';
    $cashMethodId = $pMethods->firstWhere(fn($m) => str_contains(strtolower($m->name), 'cash'))->payment_method_id ?? '';
@endphp

@section('content')
<div class="max-w-5xl mx-auto space-y-6 pb-20">

    {{-- HEADER --}}
    <div class="flex flex-col md:flex-row justify-between items-start md:items-center gap-4">
        <a href="{{ route('client.invoices.index') }}" class="flex items-center text-slate-500 hover:text-slate-800 transition text-sm font-medium">
            <i class="material-icons text-[18px] mr-1">arrow_back</i> Kembali ke Daftar
        </a>

        @if(in_array($invoice->status, ['unpaid', 'partially_paid']) && $canPay)
            {{-- Tombol Trigger Modal Tailwind --}}
            <button type="button" onclick="openModal('paymentMethodModal')" 
                    class="bg-indigo-600 hover:bg-indigo-700 text-white font-bold py-2.5 px-5 rounded-lg shadow-lg flex items-center gap-2 transition transform hover:-translate-y-0.5">
                <i class="material-icons text-[20px]">credit_card</i> Bayar Tagihan
            </button>
        @endif
    </div>

    {{-- ALERT --}}
    @if(session('success'))
        <div class="bg-emerald-50 text-emerald-800 p-4 rounded-lg border border-emerald-200 flex items-center gap-2">
            <i class="material-icons text-emerald-600">check_circle</i> {{ session('success') }}
        </div>
    @endif
    @if(session('error'))
        <div class="bg-red-50 text-red-800 p-4 rounded-lg border border-red-200 flex items-center gap-2">
            <i class="material-icons text-red-600">error</i> {{ session('error') }}
        </div>
    @endif

    {{-- KARTU DETAIL INVOICE --}}
    <div class="dashboard-card overflow-hidden">
        {{-- Header Kartu --}}
        <div class="bg-slate-50 dark:bg-slate-800/50 px-8 py-6 border-b border-slate-200 dark:border-slate-700 flex flex-col md:flex-row justify-between items-start">
            <div>
                <h1 class="text-3xl font-extrabold text-slate-800 dark:text-slate-100 tracking-tight">INVOICE</h1>
                <p class="text-slate-500 font-mono mt-1 text-lg">#{{ $invoice->invoice_number }}</p>
            </div>
            <div class="text-right mt-4 md:mt-0">
                @php
                    $statusClass = [
                        'paid' => 'bg-emerald-100 text-emerald-700 border-emerald-200',
                        'partially_paid' => 'bg-sky-100 text-sky-700 border-sky-200',
                        'cancelled' => 'bg-slate-100 text-slate-600 border-slate-200',
                        'unpaid' => 'bg-amber-100 text-amber-700 border-amber-200',
                    ];
                    $label = [
                        'paid' => 'LUNAS', 'partially_paid' => 'CICIL', 'cancelled' => 'DIBATALKAN', 'unpaid' => 'BELUM LUNAS'
                    ];
                @endphp
                <span class="inline-block px-4 py-2 rounded-lg border {{ $statusClass[$invoice->status] ?? 'bg-gray-100' }} font-bold text-sm tracking-wide">
                    {{ $label[$invoice->status] ?? strtoupper($invoice->status) }}
                </span>
            </div>
        </div>

        <div class="p-8">
            {{-- Info Grid --}}
            <div class="grid grid-cols-1 md:grid-cols-2 gap-8 mb-8 text-sm">
                <div>
                    <p class="text-slate-500 uppercase font-bold text-xs tracking-wider mb-1">Diterbitkan Untuk</p>
                    <p class="font-bold text-slate-800 dark:text-slate-200 text-base">{{ $invoice->client->client_name }}</p>
                    @if($invoice->sales)
                        <p class="text-slate-500 mt-1 flex items-center gap-1">
                            <i class="material-icons text-[14px]">badge</i> Sales: {{ $invoice->sales->full_name }}
                        </p>
                    @endif
                </div>
                <div class="md:text-right space-y-2">
                    <div>
                        <span class="text-slate-500 font-bold mr-2">Tanggal Terbit:</span>
                        <span class="text-slate-800 dark:text-slate-300">{{ optional($invoice->order_date)->format('d F Y') }}</span>
                    </div>
                    <div>
                        <span class="text-slate-500 font-bold mr-2">Jatuh Tempo:</span>
                        <span class="{{ optional($invoice->due_date)->isPast() && $invoice->status != 'paid' ? 'text-red-600 font-bold' : 'text-slate-800 dark:text-slate-300' }}">
                            {{ optional($invoice->due_date)->format('d F Y') }}
                        </span>
                    </div>
                </div>
            </div>

            <hr class="border-slate-100 dark:border-slate-700 my-8">

            {{-- Tabel Produk --}}
            <h3 class="font-bold text-slate-800 dark:text-slate-100 mb-4 text-lg">Rincian Item</h3>
            <div class="border border-slate-200 dark:border-slate-700 rounded-lg overflow-hidden mb-8">
                <table class="w-full text-left text-sm">
                    <thead class="bg-slate-50 dark:bg-slate-800 border-b border-slate-200 dark:border-slate-700 text-slate-500 font-semibold uppercase text-xs">
                        <tr>
                            <th class="p-3 w-12 text-center">#</th>
                            <th class="p-3">Produk</th>
                            <th class="p-3 text-center">Qty</th>
                            <th class="p-3 text-right">Harga</th>
                            <th class="p-3 text-right">Subtotal</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 dark:divide-slate-700">
                        @foreach($invoice->items as $item)
                        <tr class="hover:bg-slate-50 dark:hover:bg-slate-800/50">
                            <td class="p-3 text-center text-slate-400">{{ $loop->iteration }}</td>
                            <td class="p-3 font-medium text-slate-700 dark:text-slate-300">{{ $item->product->product_name ?? 'Produk Dihapus' }}</td>
                            <td class="p-3 text-center text-slate-600 dark:text-slate-400">{{ $item->quantity }}</td>
                            <td class="p-3 text-right text-slate-600 dark:text-slate-400">Rp {{ number_format($item->price_per_unit, 0, ',', '.') }}</td>
                            <td class="p-3 text-right font-bold text-slate-700 dark:text-slate-200">Rp {{ number_format($item->subtotal, 0, ',', '.') }}</td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
                
                {{-- KIRI: RIWAYAT --}}
                <div class="space-y-6">
                    @if($pendingPayments->isNotEmpty())
                    <div class="bg-amber-50 border border-amber-200 rounded-lg p-4">
                        <h5 class="font-bold text-amber-800 mb-2 flex items-center gap-2 text-sm uppercase">
                            <i class="material-icons text-[18px]">hourglass_top</i> Menunggu Verifikasi
                        </h5>
                        <div class="space-y-2">
                            @foreach($pendingPayments as $payment)
                            <div class="flex justify-between text-sm border-b border-amber-200/50 pb-2 last:border-0 last:pb-0">
                                <div>
                                    <span class="block text-amber-900 font-bold">Rp {{ number_format($payment->amount, 0, ',', '.') }}</span>
                                    <span class="text-xs text-amber-700">{{ $payment->created_at->format('d/m/y H:i') }}</span>
                                </div>
                                <div class="text-right">
                                    <span class="text-xs text-amber-700 badge bg-amber-200/50">Proses</span>
                                </div>
                            </div>
                            @endforeach
                        </div>
                    </div>
                    @endif

                    @php $successPayments = $allPayments->where('status', 'completed'); @endphp
                    @if($successPayments->isNotEmpty())
                    <div>
                        <h5 class="font-bold text-slate-700 dark:text-slate-300 mb-3 text-sm uppercase flex items-center gap-2">
                            <i class="material-icons text-[16px]">history</i> Riwayat Pembayaran
                        </h5>
                        <div class="border border-slate-200 dark:border-slate-700 rounded-lg overflow-hidden text-sm">
                            <table class="w-full">
                                <thead class="bg-slate-50 dark:bg-slate-800 text-xs font-bold text-slate-500 uppercase">
                                    <tr>
                                        <th class="p-2 pl-3">Tgl</th>
                                        <th class="p-2 text-right">Jumlah</th>
                                        <th class="p-2 text-center">Metode</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-slate-100 dark:divide-slate-700">
                                    @foreach($successPayments as $pay)
                                    <tr>
                                        <td class="p-2 pl-3 text-slate-600 dark:text-slate-400">{{ $pay->payment_date->format('d/m/y') }}</td>
                                        <td class="p-2 text-right font-bold text-emerald-600">Rp {{ number_format($pay->amount, 0, ',', '.') }}</td>
                                        <td class="p-2 text-center text-xs text-slate-500">{{ $pay->paymentMethod->name ?? 'Saldo' }}</td>
                                    </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>
                    @endif
                </div>

                {{-- KANAN: RINGKASAN --}}
                <div>
                    <div class="bg-slate-50 dark:bg-slate-800 rounded-xl p-6 border border-slate-200 dark:border-slate-700 shadow-sm space-y-3">
                        <h5 class="font-bold text-slate-800 dark:text-slate-100 mb-4 pb-2 border-b border-slate-200 dark:border-slate-700">
                            Ringkasan Keuangan
                        </h5>
                        
                        <div class="flex justify-between text-sm text-slate-600 dark:text-slate-400">
                            <span>Total Tagihan Awal</span>
                            <span class="font-bold text-slate-800 dark:text-slate-200">Rp {{ number_format($invoice->total_amount, 0, ',', '.') }}</span>
                        </div>

                        @foreach ($invoice->adjustments as $adjustment)
                            <div class="flex justify-between text-sm {{ $adjustment->type == 'credit_note' ? 'text-emerald-600' : 'text-red-600' }}">
                                <span>{{ $adjustment->type == 'credit_note' ? 'Potongan (Nota Kredit)' : 'Tambahan (Nota Debit)' }}</span>
                                <span>{{ $adjustment->type == 'credit_note' ? '-' : '+' }} Rp {{ number_format($adjustment->amount, 0, ',', '.') }}</span>
                            </div>
                        @endforeach

                        <div class="border-t border-slate-200 dark:border-slate-700 my-2"></div>

                        <div class="flex justify-between text-sm text-emerald-600 dark:text-emerald-400">
                            <span>Sudah Dibayar</span>
                            <span class="font-bold">- Rp {{ number_format($invoice->amount_paid, 0, ',', '.') }}</span>
                        </div>

                        @if($pendingPaymentAmount > 0)
                            <div class="flex justify-between text-sm text-amber-600 dark:text-amber-500 italic">
                                <span>Menunggu Verifikasi</span>
                                <span>- Rp {{ number_format($pendingPaymentAmount, 0, ',', '.') }}</span>
                            </div>
                        @endif

                        <div class="border-t border-slate-300 dark:border-slate-600 my-3 pt-2">
                            @if($finalBalanceUI < -0.01)
                                <div class="flex justify-between items-center text-emerald-600 dark:text-emerald-400">
                                    <span class="font-bold text-lg uppercase tracking-wide">Kelebihan Bayar</span>
                                    <span class="font-extrabold text-xl">Rp {{ number_format(abs($finalBalanceUI), 0, ',', '.') }}</span>
                                </div>
                            @elseif($finalBalanceUI > 0.01)
                                <div class="flex justify-between items-center text-red-600 dark:text-red-400">
                                    <span class="font-bold text-lg uppercase tracking-wide">Sisa Tagihan</span>
                                    <span class="font-extrabold text-xl">Rp {{ number_format($finalBalanceUI, 0, ',', '.') }}</span>
                                </div>
                            @else
                                <div class="flex justify-between items-center text-emerald-600 dark:text-emerald-400">
                                    <span class="font-bold text-lg uppercase tracking-wide">Status Akhir</span>
                                    <span class="font-extrabold text-xl">LUNAS</span>
                                </div>
                            @endif
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

{{-- ======================================================================== --}}
{{-- MODALS SECTION (TAILWIND STYLE) --}}
{{-- ======================================================================== --}}

{{-- 1. MODAL PILIH METODE --}}
<div id="paymentMethodModal" class="fixed inset-0 z-[100] hidden" role="dialog" aria-modal="true">
    <div class="fixed inset-0 bg-slate-900/60 backdrop-blur-sm transition-opacity" onclick="closeModal('paymentMethodModal')"></div>
    <div class="fixed inset-0 z-10 flex items-center justify-center p-4">
        <div class="bg-white rounded-2xl shadow-2xl w-full max-w-md modal-content-anim relative overflow-hidden">
            <div class="bg-slate-50 px-6 py-4 border-b border-slate-100 flex justify-between items-center">
                <h3 class="text-lg font-bold text-slate-800">Pilih Metode Pembayaran</h3>
                <button type="button" class="text-slate-400 hover:text-slate-600" onclick="closeModal('paymentMethodModal')"><i class="material-icons">close</i></button>
            </div>
            <div class="p-6 space-y-3">
                <button type="button" onclick="switchToManual('transfer')" class="w-full group flex items-center gap-4 p-4 rounded-xl border border-slate-200 hover:border-blue-500 hover:bg-blue-50 transition-all text-left">
                    <div class="w-10 h-10 rounded-full bg-blue-100 text-blue-600 flex items-center justify-center"><i class="material-icons">upload_file</i></div>
                    <div>
                        <span class="block font-bold text-slate-700 group-hover:text-blue-700">Upload Bukti Transfer</span>
                        <span class="block text-xs text-slate-500">Transfer bank manual & upload struk.</span>
                    </div>
                </button>
                <button type="button" onclick="switchToManual('cash')" class="w-full group flex items-center gap-4 p-4 rounded-xl border border-slate-200 hover:border-emerald-500 hover:bg-emerald-50 transition-all text-left">
                    <div class="w-10 h-10 rounded-full bg-emerald-100 text-emerald-600 flex items-center justify-center"><i class="material-icons">payments</i></div>
                    <div>
                        <span class="block font-bold text-slate-700 group-hover:text-emerald-700">Tunai (Cash)</span>
                        <span class="block text-xs text-slate-500">Bayar tunai titip melalui Sales.</span>
                    </div>
                </button>
                <button type="button" onclick="switchToMidtrans()" class="w-full group flex items-center gap-4 p-4 rounded-xl border border-slate-200 hover:border-indigo-500 hover:bg-indigo-50 transition-all text-left">
                    <div class="w-10 h-10 rounded-full bg-indigo-100 text-indigo-600 flex items-center justify-center"><i class="material-icons">credit_card</i></div>
                    <div>
                        <span class="block font-bold text-slate-700 group-hover:text-indigo-700">Pembayaran Online</span>
                        <span class="block text-xs text-slate-500">QRIS, Virtual Account (Otomatis).</span>
                    </div>
                </button>
            </div>
        </div>
    </div>
</div>

{{-- 2. MODAL FORM MANUAL (TRANSFER/CASH) --}}
<div id="manualPaymentModal" class="fixed inset-0 z-[100] hidden" role="dialog" aria-modal="true">
    <div class="fixed inset-0 bg-slate-900/60 backdrop-blur-sm transition-opacity" onclick="closeModal('manualPaymentModal')"></div>
    <div class="fixed inset-0 z-10 flex items-center justify-center p-4 overflow-y-auto">
        <div class="bg-white rounded-2xl shadow-2xl w-full max-w-md modal-content-anim relative">
            <div class="bg-slate-50 px-6 py-4 border-b border-slate-100 flex justify-between items-center">
                <h3 class="text-lg font-bold text-slate-800" id="manualPaymentModalTitle">Konfirmasi Pembayaran</h3>
                <button type="button" class="text-slate-400 hover:text-slate-600" onclick="closeModal('manualPaymentModal')"><i class="material-icons">close</i></button>
            </div>
            <form action="{{ route('client.invoices.uploadProof', $invoice->invoice_id) }}" method="POST" enctype="multipart/form-data" id="manual-payment-form">
                @csrf
                <input type="hidden" name="use_credit" id="manual-use-credit-hidden" value="0">
                
                <div class="p-6 space-y-4">
                    {{-- Info Tagihan --}}
                    <div class="bg-blue-50 text-blue-800 p-3 rounded-lg border border-blue-100 text-sm flex justify-between items-center">
                        <span>Sisa Tagihan Saat Ini:</span>
                        <span class="font-bold text-base">Rp {{ number_format($finalBalanceUI, 0, ',', '.') }}</span>
                    </div>

                    {{-- Opsi Saldo --}}
                    @if($saldoKreditKlien > 0)
                    <div class="bg-emerald-50 text-emerald-800 p-3 rounded-lg border border-emerald-100 text-sm">
                        <div class="flex justify-between font-bold mb-2">
                            <span>Saldo Deposit Tersedia:</span>
                            <span>Rp {{ number_format($saldoKreditKlien, 0, ',', '.') }}</span>
                        </div>
                        <div class="flex items-center gap-2">
                            <input type="checkbox" id="manual-use-credit" value="1" class="w-4 h-4 rounded text-emerald-600 focus:ring-emerald-500 cursor-pointer">
                            <label for="manual-use-credit" class="font-bold cursor-pointer select-none">Gunakan Saldo</label>
                        </div>
                    </div>
                    @endif

                    {{-- Form Dinamis (Cash) --}}
                    <div id="cash-fields" class="hidden">
                        <label class="block text-xs font-bold text-slate-500 uppercase mb-1">Diterima Sales <span class="text-red-500">*</span></label>
                        <select name="user_id_sales" id="user_id_sales" class="w-full p-2 border border-slate-300 rounded-lg text-sm">
                            <option value="" disabled selected>-- Pilih Sales --</option>
                            @foreach(\App\Models\User::role('sales')->get() as $sales)
                                <option value="{{ $sales->user_id }}">{{ $sales->full_name }}</option>
                            @endforeach
                        </select>
                    </div>

                    {{-- Form Dinamis (Transfer) --}}
                    <div id="transfer-fields" class="hidden space-y-3">
                        <div>
                            <label class="block text-xs font-bold text-slate-500 uppercase mb-1">Metode <span class="text-red-500">*</span></label>
                            <select name="payment_method_id" id="payment_method_id" class="w-full p-2 border border-slate-300 rounded-lg text-sm">
                                <option value="">-- Pilih Metode --</option>
                                @foreach($pMethods as $method)
                                    <option value="{{ $method->payment_method_id }}" data-config="{{ $method->required_fields_config }}">{{ $method->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div id="payment-reference-group" class="hidden">
                            <label class="block text-xs font-bold text-slate-500 uppercase mb-1">No. Referensi</label>
                            <input type="text" name="reference_number" id="reference_number" class="w-full p-2 border border-slate-300 rounded-lg text-sm">
                        </div>
                        <div id="payment-proof-group" class="hidden">
                            <label class="block text-xs font-bold text-slate-500 uppercase mb-1">Bukti Foto</label>
                            <input type="file" name="proof_of_payment" id="proof_of_payment" class="block w-full text-xs text-slate-500 file:mr-4 file:py-2 file:px-4 file:rounded-full file:border-0 file:text-xs file:font-bold file:bg-indigo-50 file:text-indigo-700 hover:file:bg-indigo-100" accept="image/*">
                        </div>
                    </div>

                    {{-- Input Nominal --}}
                    <div>
                        <label class="block text-xs font-bold text-slate-500 uppercase mb-1">Jumlah Dibayar (Manual) <span class="text-red-500">*</span></label>
                        <input type="text" class="w-full p-3 border border-slate-300 rounded-lg font-bold text-lg focus:ring-2 focus:ring-indigo-500" id="payment_amount_display" placeholder="Rp 0">
                        <input type="hidden" name="payment_amount" id="payment_amount">
                        <div id="amount-error" class="text-red-500 text-xs mt-1 hidden"></div>
                    </div>

                    {{-- Catatan --}}
                    <div>
                        <label class="block text-xs font-bold text-slate-500 uppercase mb-1">Catatan</label>
                        <textarea name="notes" class="w-full p-2 border border-slate-300 rounded-lg text-sm" rows="2"></textarea>
                    </div>
                </div>
                <div class="bg-slate-50 px-6 py-4 flex justify-end gap-3 rounded-b-2xl border-t border-slate-100">
                    <button type="button" class="px-4 py-2 border border-slate-300 rounded-lg text-slate-600 font-bold hover:bg-slate-100" onclick="closeModal('manualPaymentModal')">Batal</button>
                    <button type="submit" class="px-4 py-2 bg-indigo-600 text-white rounded-lg font-bold hover:bg-indigo-700 shadow-md" id="submit-proof-btn">Kirim Data</button>
                </div>
            </form>
        </div>
    </div>
</div>

{{-- 3. MODAL MIDTRANS --}}
<div id="midtransPaymentModal" class="fixed inset-0 z-[100] hidden" role="dialog" aria-modal="true">
    <div class="fixed inset-0 bg-slate-900/60 backdrop-blur-sm transition-opacity" onclick="closeModal('midtransPaymentModal')"></div>
    <div class="fixed inset-0 z-10 flex items-center justify-center p-4">
        <div class="bg-white rounded-2xl shadow-2xl w-full max-w-md modal-content-anim relative">
            <div class="bg-slate-50 px-6 py-4 border-b border-slate-100 flex justify-between items-center">
                <h3 class="text-lg font-bold text-slate-800">Pembayaran Online</h3>
                <button type="button" class="text-slate-400 hover:text-slate-600" onclick="closeModal('midtransPaymentModal')"><i class="material-icons">close</i></button>
            </div>
            <form id="midtrans-payment-form" action="{{ route('client.invoices.pay', $invoice->invoice_id) }}" method="POST">
                @csrf
                <div class="p-6 space-y-4">
                    <div class="bg-indigo-50 text-indigo-900 p-4 rounded-lg border border-indigo-100 text-sm flex justify-between font-bold">
                        <span>Total Sisa Tagihan:</span>
                        <span>Rp {{ number_format($finalBalanceUI, 0, ',', '.') }}</span>
                    </div>

                    @if($saldoKreditKlien > 0)
                    <div class="bg-emerald-50 text-emerald-800 p-3 rounded-lg border border-emerald-100 text-sm">
                        <div class="flex justify-between font-bold mb-2">
                            <span>Saldo Deposit:</span>
                            <span>Rp {{ number_format($saldoKreditKlien, 0, ',', '.') }}</span>
                        </div>
                        <div class="flex items-center gap-2">
                            <input type="checkbox" id="midtrans-use-credit" value="1" class="w-4 h-4 rounded text-emerald-600 focus:ring-emerald-500 cursor-pointer">
                            <label for="midtrans-use-credit" class="font-bold cursor-pointer select-none">Gunakan Saldo</label>
                        </div>
                    </div>
                    @endif

                    <div>
                        <label class="block text-xs font-bold text-slate-500 uppercase mb-1">Jumlah Bayar Online <span class="text-red-500">*</span></label>
                        <input type="text" class="w-full p-3 border border-slate-300 rounded-lg font-bold text-lg focus:ring-2 focus:ring-indigo-500" id="midtrans-amount-formatted" required>
                        <input type="hidden" name="amount" id="midtrans-amount-hidden">
                        <input type="hidden" name="use_credit" id="midtrans-use-credit-hidden" value="0">
                        <div id="midtrans-amount-error" class="text-red-500 text-xs mt-1 hidden"></div>
                    </div>
                    
                    <p class="text-xs text-center text-slate-400">Anda akan diarahkan ke halaman pembayaran aman Midtrans.</p>
                </div>
                <div class="bg-slate-50 px-6 py-4 flex justify-end gap-3 rounded-b-2xl border-t border-slate-100">
                    <button type="button" class="px-4 py-2 border border-slate-300 rounded-lg text-slate-600 font-bold hover:bg-slate-100" onclick="closeModal('midtransPaymentModal')">Batal</button>
                    <button type="submit" class="px-4 py-2 bg-indigo-600 text-white rounded-lg font-bold hover:bg-indigo-700 shadow-md" id="midtrans-submit-btn">Bayar Sekarang</button>
                </div>
            </form>
        </div>
    </div>
</div>

@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/autonumeric@4.6.0/dist/autoNumeric.min.js"></script>
<script type="text/javascript"
    src="{{ config('midtrans.is_production') ? 'https://app.midtrans.com/snap/snap.js' : 'https://app.sandbox.midtrans.com/snap/snap.js' }}"
    data-client-key="{{ config('midtrans.client_key') }}"></script>

<script>
    // --- VARIABLES ---
    const remainingBalance = parseFloat("{{ $finalBalanceUI }}");
    const currentCreditBalance = parseFloat("{{ $saldoKreditKlien }}");
    const transferMethodId = "{{ $transferMethodId }}";
    const cashMethodId = "{{ $cashMethodId }}";

    // --- MODAL UTILS (Tailwind Way) ---
    function openModal(id) {
        document.getElementById(id).classList.remove('hidden');
    }
    function closeModal(id) {
        document.getElementById(id).classList.add('hidden');
    }
    
    // Switcher Functions
    function switchToManual(type) {
        closeModal('paymentMethodModal');
        setupManualModal(type);
        openModal('manualPaymentModal');
    }
    function switchToMidtrans() {
        closeModal('paymentMethodModal');
        setupMidtransModal();
        openModal('midtransPaymentModal');
    }

    document.addEventListener('DOMContentLoaded', function() {
        
        // =====================================================================
        // 1. MANUAL PAYMENT LOGIC
        // =====================================================================
        const manualForm = document.getElementById('manual-payment-form');
        const manualTitle = document.getElementById('manualPaymentModalTitle');
        const cashFields = document.getElementById('cash-fields');
        const transferFields = document.getElementById('transfer-fields');
        const salesSelect = document.getElementById('user_id_sales');
        const methodDropdown = document.getElementById('payment_method_id');
        const proofGroup = document.getElementById('payment-proof-group');
        const proofInput = document.getElementById('proof_of_payment');
        const refGroup = document.getElementById('payment-reference-group');
        const refInput = document.getElementById('reference_number');
        
        const manualAmountDisplay = document.getElementById('payment_amount_display');
        const manualAmountHidden = document.getElementById('payment_amount');
        const manualAmountError = document.getElementById('amount-error');
        const manualUseCreditCheck = document.getElementById('manual-use-credit');
        const manualUseCreditHidden = document.getElementById('manual-use-credit-hidden');
        const manualSubmitBtn = document.getElementById('submit-proof-btn');

        const manualAutoNumeric = new AutoNumeric(manualAmountDisplay, { 
            decimalPlaces: 0, digitGroupSeparator: '.', decimalCharacter: ',', currencySymbol: 'Rp ', currencySymbolPlacement: 'p', minimumValue: 0 
        });

        // Setup Manual Form
        window.setupManualModal = function(type) {
            manualForm.reset();
            manualAmountError.classList.add('hidden');
            
            if (type === 'cash') {
                manualTitle.textContent = 'Konfirmasi Bayar Tunai';
                cashFields.classList.remove('hidden');
                transferFields.classList.add('hidden');
                salesSelect.required = true;
                methodDropdown.required = false;
            } else {
                manualTitle.textContent = 'Upload Bukti Transfer';
                cashFields.classList.add('hidden');
                transferFields.classList.remove('hidden');
                salesSelect.required = false;
                methodDropdown.required = true;
                methodDropdown.value = transferMethodId; 
                // Trigger change manually to show fields
                const event = new Event('change');
                methodDropdown.dispatchEvent(event);
            }

            if (manualUseCreditCheck) {
                manualUseCreditCheck.checked = false;
                manualUseCreditHidden.value = '0';
            }
            manualAutoNumeric.set(remainingBalance);
            manualAmountDisplay.disabled = false;
        }

        // Handle Method Change
        if (methodDropdown) {
            methodDropdown.addEventListener('change', function() {
                const selectedOption = this.options[this.selectedIndex];
                const config = selectedOption ? selectedOption.dataset.config : 'none';

                refGroup.classList.add('hidden'); refInput.required = false;
                proofGroup.classList.add('hidden'); proofInput.required = false;

                if (config === 'proof_only') {
                    proofGroup.classList.remove('hidden'); proofInput.required = true;
                } else if (config === 'reference_only') {
                    refGroup.classList.remove('hidden'); refInput.required = true;
                } else if (config === 'proof_and_reference') {
                    proofGroup.classList.remove('hidden'); proofInput.required = true;
                    refGroup.classList.remove('hidden'); refInput.required = true;
                }
            });
        }

        // Logic Saldo & Validasi Manual
        if (manualUseCreditCheck) {
            manualUseCreditCheck.addEventListener('change', function() {
                const useCredit = this.checked;
                manualUseCreditHidden.value = useCredit ? '1' : '0';
                
                if (useCredit) {
                    if (currentCreditBalance >= remainingBalance) {
                        manualAutoNumeric.set(0); 
                        manualAmountDisplay.disabled = true;
                    } else {
                        manualAutoNumeric.set(remainingBalance - currentCreditBalance);
                        manualAmountDisplay.disabled = false;
                    }
                } else {
                    manualAutoNumeric.set(remainingBalance);
                    manualAmountDisplay.disabled = false;
                }
                validateManualAmount();
            });
        }

        function validateManualAmount() {
            const rawVal = manualAutoNumeric.getNumericString();
            manualAmountHidden.value = rawVal;
            const val = parseFloat(rawVal || 0);
            const useCredit = manualUseCreditCheck ? manualUseCreditCheck.checked : false;
            const totalPaid = (useCredit ? currentCreditBalance : 0) + val;

            let isValid = true;
            let msg = '';

            if (val < 0) isValid = false;
            if (totalPaid > (remainingBalance + 1)) { 
                msg = 'Info: Kelebihan bayar akan masuk ke Saldo Kredit.';
                manualAmountError.classList.remove('text-red-500');
                manualAmountError.classList.add('text-emerald-600');
            } else {
                manualAmountError.classList.add('text-red-500');
                manualAmountError.classList.remove('text-emerald-600');
            }

            if (totalPaid <= 0 && remainingBalance > 0) {
                isValid = false;
                msg = 'Jumlah bayar tidak boleh 0.';
            }

            manualAmountError.textContent = msg;
            manualAmountError.classList.toggle('hidden', !msg);
            manualSubmitBtn.disabled = !isValid;
        }

        manualAmountDisplay.addEventListener('keyup', validateManualAmount);
        manualAmountDisplay.addEventListener('change', validateManualAmount);


        // =====================================================================
        // 2. MIDTRANS LOGIC
        // =====================================================================
        const midtransForm = document.getElementById('midtrans-payment-form');
        const midtransAmountDisplay = document.getElementById('midtrans-amount-formatted');
        const midtransAmountHidden = document.getElementById('midtrans-amount-hidden');
        const midtransUseCreditCheck = document.getElementById('midtrans-use-credit');
        const midtransUseCreditHidden = document.getElementById('midtrans-use-credit-hidden');
        const midtransAmountError = document.getElementById('midtrans-amount-error');
        const midtransSubmitBtn = document.getElementById('midtrans-submit-btn');

        const midtransAutoNumeric = new AutoNumeric(midtransAmountDisplay, { 
            decimalPlaces: 0, digitGroupSeparator: '.', decimalCharacter: ',', currencySymbol: 'Rp ', currencySymbolPlacement: 'p', minimumValue: 0 
        });

        window.setupMidtransModal = function() {
            midtransForm.reset();
            midtransAmountError.classList.add('hidden');
            if (midtransUseCreditCheck) midtransUseCreditCheck.checked = false;
            midtransUseCreditHidden.value = '0';
            midtransAutoNumeric.set(remainingBalance);
            midtransAmountDisplay.disabled = false;
        }

        if (midtransUseCreditCheck) {
            midtransUseCreditCheck.addEventListener('change', function() {
                const useCredit = this.checked;
                midtransUseCreditHidden.value = useCredit ? '1' : '0';
                if (useCredit) {
                    if (currentCreditBalance >= remainingBalance) {
                        midtransAutoNumeric.set(0); 
                        midtransAmountDisplay.disabled = true;
                    } else {
                        midtransAutoNumeric.set(remainingBalance - currentCreditBalance);
                        midtransAmountDisplay.disabled = false;
                    }
                } else {
                    midtransAutoNumeric.set(remainingBalance);
                    midtransAmountDisplay.disabled = false;
                }
                validateMidtransAmount();
            });
        }

        function validateMidtransAmount() {
            const rawVal = midtransAutoNumeric.getNumericString();
            midtransAmountHidden.value = rawVal;
            const val = parseFloat(rawVal || 0);
            const useCredit = midtransUseCreditCheck ? midtransUseCreditCheck.checked : false;
            const totalPaid = (useCredit ? currentCreditBalance : 0) + val;

            let isValid = true;
            let msg = '';

            if (totalPaid > (remainingBalance + 1)) {
                isValid = false; 
                msg = 'Pembayaran online tidak boleh melebihi sisa tagihan.';
            }
            if (totalPaid <= 0 && remainingBalance > 0) {
                isValid = false;
                msg = 'Jumlah bayar tidak boleh 0.';
            }

            midtransAmountError.textContent = msg;
            midtransAmountError.classList.toggle('hidden', !msg);
            midtransSubmitBtn.disabled = !isValid;
        }

        midtransAmountDisplay.addEventListener('keyup', validateMidtransAmount);
        midtransAmountDisplay.addEventListener('change', validateMidtransAmount);

        // Submit Midtrans
        midtransForm.addEventListener('submit', function(event) {
            event.preventDefault();
            const formData = new FormData(this);
            const payButton = this.querySelector('button[type="submit"]');
            payButton.disabled = true;
            payButton.innerHTML = 'Memproses...';

            fetch(this.action, {
                method: 'POST',
                headers: { 'X-CSRF-TOKEN': '{{ csrf_token() }}', 'Accept': 'application/json' },
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                const redirectUrl = "{{ route('client.invoices.index') }}";
                if (data.status === 'paid_by_credit') {
                    window.location.href = redirectUrl + '?payment_success=1';
                    return;
                }
                if (data.snap_token) {
                    window.snap.pay(data.snap_token, {
                        onSuccess: function(result){ window.location.href = redirectUrl + '?payment_success=1'; },
                        onPending: function(result){ window.location.href = redirectUrl + '?payment_pending=1'; },
                        onError: function(result){ alert("Pembayaran gagal!"); payButton.disabled = false; payButton.innerHTML = 'Bayar Sekarang'; },
                        onClose: function(){ payButton.disabled = false; payButton.innerHTML = 'Bayar Sekarang'; }
                    });
                } else {
                    throw new Error(data.message || 'Gagal token.');
                }
            })
            .catch(error => {
                alert('Error: ' + error.message);
                payButton.disabled = false;
                payButton.innerHTML = 'Bayar Sekarang';
            });
        });
    });
</script>
@endpush
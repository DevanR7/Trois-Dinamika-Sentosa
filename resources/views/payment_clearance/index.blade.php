@extends('layouts.app')

@section('title', 'Kliring Pembayaran')

@section('content')
<div class="max-w-7xl mx-auto pb-20 animate-enter">
    
    {{-- HEADER --}}
    <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center mb-8 gap-4">
        <div>
            <h1 class="text-2xl font-bold text-slate-800 tracking-tight">Kliring Pembayaran</h1>
            <p class="text-slate-500 text-sm mt-1">Verifikasi Cek/Giro/Transfer yang masih tertunda (Pending Clearance).</p>
        </div>
    </div>

    {{-- TABEL DATA --}}
    <div class="dashboard-card p-0 overflow-hidden shadow-lg border-0 ring-1 ring-slate-900/5">
        <div class="overflow-x-auto">
            <table class="dashboard-table min-w-full">
                <thead class="bg-slate-50 border-b border-slate-200">
                    <tr>
                        <th class="pl-6 w-32">Tanggal</th>
                        <th>Jenis Transaksi</th>
                        <th>Relasi</th>
                        <th>Referensi</th>
                        <th>Metode & Akun</th>
                        <th class="text-right">Jumlah</th>
                        <th class="text-center w-32 pr-6">Aksi</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-slate-100">
                    @forelse ($pendingPayments as $payment)
                        <tr class="hover:bg-slate-50/50 transition-colors group">
                            
                            {{-- Tanggal --}}
                            <td class="pl-6 py-4 text-sm text-slate-600 whitespace-nowrap">
                                {{ $payment->payment_date->format('d M Y') }}
                            </td>
                            
                            {{-- Jenis Transaksi --}}
                            <td class="py-4">
                                @if ($payment instanceof \App\Models\Payment)
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-bold bg-emerald-50 text-emerald-700 border border-emerald-100">
                                        <i class="material-icons text-[14px] mr-1">south_west</i> Masuk (Piutang)
                                    </span>
                                @elseif ($payment instanceof \App\Models\PurchaseOrderPayment)
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-bold bg-red-50 text-red-700 border border-red-100">
                                        <i class="material-icons text-[14px] mr-1">north_east</i> Keluar (Hutang)
                                    </span>
                                @endif
                            </td>

                            {{-- Relasi --}}
                            <td class="py-4">
                                @if ($payment instanceof \App\Models\Payment)
                                    <a href="{{ route('clients.show', $payment->salesInvoice->client_id) }}" class="text-sm font-bold text-slate-800 hover:text-indigo-600 transition">
                                        {{ $payment->salesInvoice->client->client_name }}
                                    </a>
                                    <div class="text-xs text-slate-500 mt-0.5 font-mono">
                                        Inv: <a href="{{ route('invoices.show', $payment->invoice_id) }}" class="hover:underline hover:text-indigo-500">{{ $payment->salesInvoice->invoice_number }}</a>
                                    </div>
                                @elseif ($payment instanceof \App\Models\PurchaseOrderPayment)
                                    <a href="{{ route('suppliers.show', $payment->purchaseOrder->supplier_id) }}" class="text-sm font-bold text-slate-800 hover:text-indigo-600 transition">
                                        {{ $payment->purchaseOrder->supplier->supplier_name }}
                                    </a>
                                    <div class="text-xs text-slate-500 mt-0.5 font-mono">
                                        PO: <a href="{{ route('purchase-orders.show', $payment->po_id) }}" class="hover:underline hover:text-indigo-500">{{ $payment->purchaseOrder->po_number }}</a>
                                    </div>
                                @endif
                            </td>

                            {{-- Referensi --}}
                            <td class="py-4 text-sm text-slate-700 font-mono">
                                {{ $payment->reference_number ?? '-' }}
                            </td>

                            {{-- Metode & Akun --}}
                            <td class="py-4">
                                <div class="flex flex-col">
                                    <span class="text-xs font-bold text-indigo-600 mb-0.5 uppercase tracking-wide">
                                        {{ $payment->paymentMethod->name ?? 'N/A' }}
                                    </span>
                                    @if($payment->companyBankAccount)
                                        <span class="text-xs text-slate-500 font-medium">
                                            {{ $payment->companyBankAccount->bank_name }} - {{ $payment->companyBankAccount->account_number }}
                                        </span>
                                    @else
                                        <span class="text-xs text-red-500 italic">Akun Tidak Valid</span>
                                    @endif
                                </div>
                            </td>

                            {{-- Jumlah --}}
                            <td class="py-4 text-right text-sm font-bold text-slate-900 font-mono">
                                Rp {{ number_format($payment->amount, 0, ',', '.') }}
                            </td>

                            {{-- Aksi --}}
                            <td class="pr-6 py-4 text-center">
                                <div class="flex justify-center gap-2 opacity-100 sm:opacity-0 group-hover:opacity-100 transition-opacity">
                                    @if ($payment instanceof \App\Models\Payment)
                                        {{-- SALES ACTIONS (Penerimaan) --}}
                                        <form action="{{ route('payment-clearance.sales.approve', $payment->payment_id) }}" method="POST" class="form-confirm inline-block">
                                            @csrf
                                            <button type="submit" 
                                                    data-title="Setujui Penerimaan?"
                                                    data-text="Dana sebesar <b>Rp {{ number_format($payment->amount, 0, ',', '.') }}</b> akan masuk ke kas/bank."
                                                    data-btn-text="Ya, Setujui"
                                                    data-btn-color="#10b981"
                                                    data-icon="check"
                                                    class="w-8 h-8 flex items-center justify-center rounded-lg bg-emerald-50 text-emerald-600 border border-emerald-200 hover:bg-emerald-100 transition shadow-sm" 
                                                    title="Setujui">
                                                <i class="material-icons text-[18px]">check</i>
                                            </button>
                                        </form>
                                        <form action="{{ route('payment-clearance.sales.reject', $payment->payment_id) }}" method="POST" class="form-confirm inline-block">
                                            @csrf
                                            <button type="submit" 
                                                    data-title="Tolak Penerimaan?"
                                                    data-text="Pembayaran akan dibatalkan (Gagal)."
                                                    data-btn-text="Ya, Tolak"
                                                    data-btn-color="#ef4444"
                                                    data-icon="warning"
                                                    class="w-8 h-8 flex items-center justify-center rounded-lg bg-white text-red-600 border border-slate-200 hover:bg-red-50 hover:border-red-200 transition shadow-sm" 
                                                    title="Tolak">
                                                <i class="material-icons text-[18px]">close</i>
                                            </button>
                                        </form>

                                    @elseif ($payment instanceof \App\Models\PurchaseOrderPayment)
                                        {{-- PURCHASE ACTIONS (Pengeluaran) --}}
                                        <form action="{{ route('payment-clearance.purchase.approve', $payment->payment_id) }}" method="POST" class="form-confirm inline-block">
                                            @csrf
                                            <button type="submit" 
                                                    data-title="Setujui Pengeluaran?"
                                                    data-text="Dana sebesar <b>Rp {{ number_format($payment->amount, 0, ',', '.') }}</b> akan keluar dari kas/bank."
                                                    data-btn-text="Ya, Setujui"
                                                    data-btn-color="#10b981"
                                                    data-icon="check"
                                                    class="w-8 h-8 flex items-center justify-center rounded-lg bg-emerald-50 text-emerald-600 border border-emerald-200 hover:bg-emerald-100 transition shadow-sm" 
                                                    title="Setujui">
                                                <i class="material-icons text-[18px]">check</i>
                                            </button>
                                        </form>
                                        <form action="{{ route('payment-clearance.purchase.reject', $payment->payment_id) }}" method="POST" class="form-confirm inline-block">
                                            @csrf
                                            <button type="submit" 
                                                    data-title="Tolak Pengeluaran?"
                                                    data-text="Pembayaran akan dibatalkan (Gagal)."
                                                    data-btn-text="Ya, Tolak"
                                                    data-btn-color="#ef4444"
                                                    data-icon="warning"
                                                    class="w-8 h-8 flex items-center justify-center rounded-lg bg-white text-red-600 border border-slate-200 hover:bg-red-50 hover:border-red-200 transition shadow-sm" 
                                                    title="Tolak">
                                                <i class="material-icons text-[18px]">close</i>
                                            </button>
                                        </form>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="px-6 py-16 text-center text-slate-500">
                                <div class="flex flex-col items-center justify-center">
                                    <div class="w-16 h-16 bg-slate-50 rounded-full flex items-center justify-center mb-4">
                                        <i class="material-icons text-4xl text-slate-300">playlist_add_check</i>
                                    </div>
                                    <h3 class="text-base font-bold text-slate-700">Semua Beres!</h3>
                                    <p class="text-sm mt-1">Tidak ada pembayaran tertunda yang perlu dikliring saat ini.</p>
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
        // Notifikasi Toast (Global Handler di app.js)
        @if(session('success')) window.showToast("{{ session('success') }}", 'success'); @endif
        @if(session('error')) window.showToast("{{ session('error') }}", 'error'); @endif
        
        // Script konfirmasi manual dihapus karena sudah digantikan oleh global handler .form-confirm di app.js
    });
</script>
@endpush
@extends('layouts.app')

@section('title', 'Kliring Pembayaran')

@section('content')
<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-6">
    
    {{-- HEADER --}}
    <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center mb-6">
        <div>
            <h3 class="text-2xl font-bold text-gray-900 tracking-tight">Kliring Pembayaran</h3>
            <p class="text-sm text-gray-500 mt-1">Verifikasi Cek/Giro/Transfer yang masih tertunda (Pending Clearance).</p>
        </div>
        {{-- Spacer / Filter Button jika diperlukan --}}
    </div>

    {{-- NOTIFIKASI (Alert Tailwind) --}}
    @if (session('success'))
        <div class="mb-6 bg-green-50 border border-green-200 rounded-lg p-4 flex items-center gap-3">
            <i class="material-icons text-green-500">check_circle</i>
            <span class="text-sm text-green-800 font-medium">{{ session('success') }}</span>
        </div>
    @endif
    @if (session('error'))
        <div class="mb-6 bg-red-50 border border-red-200 rounded-lg p-4 flex items-center gap-3">
            <i class="material-icons text-red-500">error</i>
            <span class="text-sm text-red-800 font-medium">{{ session('error') }}</span>
        </div>
    @endif

    {{-- TABEL DATA --}}
    <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-bold text-gray-500 uppercase tracking-wider w-32">
                            Tanggal
                        </th>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-bold text-gray-500 uppercase tracking-wider">
                            Jenis Transaksi
                        </th>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-bold text-gray-500 uppercase tracking-wider">
                            Relasi (Klien/Supplier)
                        </th>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-bold text-gray-500 uppercase tracking-wider">
                            Referensi
                        </th>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-bold text-gray-500 uppercase tracking-wider">
                            Metode & Akun
                        </th>
                        <th scope="col" class="px-6 py-3 text-right text-xs font-bold text-gray-500 uppercase tracking-wider">
                            Jumlah
                        </th>
                        <th scope="col" class="px-6 py-3 text-center text-xs font-bold text-gray-500 uppercase tracking-wider w-32">
                            Aksi
                        </th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    @forelse ($pendingPayments as $payment)
                        <tr class="hover:bg-gray-50 transition-colors">
                            
                            {{-- Tanggal --}}
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                {{ $payment->payment_date->format('d M Y') }}
                            </td>
                            
                            {{-- Jenis Transaksi --}}
                            <td class="px-6 py-4 whitespace-nowrap">
                                @if ($payment instanceof \App\Models\Payment)
                                    {{-- Pembayaran Penjualan (Piutang) --}}
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800 border border-green-200">
                                        <i class="material-icons text-[14px] mr-1">south_west</i> Masuk (Piutang)
                                    </span>
                                @elseif ($payment instanceof \App\Models\PurchaseOrderPayment)
                                    {{-- Pembayaran Pembelian (Hutang) --}}
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-red-100 text-red-800 border border-red-200">
                                        <i class="material-icons text-[14px] mr-1">north_east</i> Keluar (Hutang)
                                    </span>
                                @endif
                            </td>

                            {{-- Relasi --}}
                            <td class="px-6 py-4 whitespace-nowrap">
                                @if ($payment instanceof \App\Models\Payment)
                                    <a href="{{ route('clients.show', $payment->salesInvoice->client_id) }}" class="text-sm font-bold text-gray-900 hover:text-indigo-600 transition">
                                        {{ $payment->salesInvoice->client->client_name }}
                                    </a>
                                    <div class="text-xs text-gray-500 mt-0.5">
                                        Inv: <a href="{{ route('invoices.show', $payment->invoice_id) }}" class="hover:underline">#{{ $payment->salesInvoice->invoice_number }}</a>
                                    </div>
                                @elseif ($payment instanceof \App\Models\PurchaseOrderPayment)
                                    <a href="{{ route('suppliers.show', $payment->purchaseOrder->supplier_id) }}" class="text-sm font-bold text-gray-900 hover:text-indigo-600 transition">
                                        {{ $payment->purchaseOrder->supplier->supplier_name }}
                                    </a>
                                    <div class="text-xs text-gray-500 mt-0.5">
                                        PO: <a href="{{ route('purchase-orders.show', $payment->po_id) }}" class="hover:underline">#{{ $payment->purchaseOrder->po_number }}</a>
                                    </div>
                                @endif
                            </td>

                            {{-- Referensi --}}
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-700 font-mono">
                                @if($payment->reference_number)
                                    {{ $payment->reference_number }}
                                @else
                                    <span class="text-gray-400 italic">-</span>
                                @endif
                            </td>

                            {{-- Metode & Akun --}}
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="flex flex-col">
                                    <span class="text-xs font-bold text-indigo-600 mb-0.5">
                                        {{ $payment->paymentMethod->name ?? 'N/A' }}
                                    </span>
                                    @if($payment->companyBankAccount)
                                        <span class="text-xs text-gray-500">
                                            {{ $payment->companyBankAccount->bank_name }} - {{ $payment->companyBankAccount->account_number }}
                                        </span>
                                    @else
                                        <span class="text-xs text-red-500 italic">Akun Tidak Valid</span>
                                    @endif
                                </div>
                            </td>

                            {{-- Jumlah --}}
                            <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-bold text-gray-900">
                                Rp {{ number_format($payment->amount, 0, ',', '.') }}
                            </td>

                            {{-- Aksi --}}
                            <td class="px-6 py-4 whitespace-nowrap text-center text-sm font-medium">
                                <div class="flex justify-center gap-2">
                                    @if ($payment instanceof \App\Models\Payment)
                                        {{-- SALES ACTIONS --}}
                                        <form action="{{ route('payment-clearance.sales.approve', $payment->payment_id) }}" method="POST" class="form-approve-sales inline-block">
                                            @csrf
                                            <button type="submit" class="p-1.5 bg-green-100 text-green-700 rounded-lg hover:bg-green-200 transition border border-green-200 shadow-sm" title="Setujui Kliring">
                                                <i class="material-icons text-lg leading-none">check</i>
                                            </button>
                                        </form>
                                        <form action="{{ route('payment-clearance.sales.reject', $payment->payment_id) }}" method="POST" class="form-reject-sales inline-block">
                                            @csrf
                                            <button type="submit" class="p-1.5 bg-white text-red-600 rounded-lg hover:bg-red-50 transition border border-gray-200 shadow-sm hover:border-red-200" title="Tolak Kliring">
                                                <i class="material-icons text-lg leading-none">close</i>
                                            </button>
                                        </form>

                                    @elseif ($payment instanceof \App\Models\PurchaseOrderPayment)
                                        {{-- PURCHASE ACTIONS --}}
                                        <form action="{{ route('payment-clearance.purchase.approve', $payment->payment_id) }}" method="POST" class="form-approve-purchase inline-block">
                                            @csrf
                                            <button type="submit" class="p-1.5 bg-green-100 text-green-700 rounded-lg hover:bg-green-200 transition border border-green-200 shadow-sm" title="Setujui Kliring">
                                                <i class="material-icons text-lg leading-none">check</i>
                                            </button>
                                        </form>
                                        <form action="{{ route('payment-clearance.purchase.reject', $payment->payment_id) }}" method="POST" class="form-reject-purchase inline-block">
                                            @csrf
                                            <button type="submit" class="p-1.5 bg-white text-red-600 rounded-lg hover:bg-red-50 transition border border-gray-200 shadow-sm hover:border-red-200" title="Tolak Kliring">
                                                <i class="material-icons text-lg leading-none">close</i>
                                            </button>
                                        </form>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="px-6 py-12 text-center text-gray-500">
                                <div class="flex flex-col items-center justify-center">
                                    <i class="material-icons text-4xl text-gray-300 mb-3">playlist_add_check</i>
                                    <p class="text-base font-medium">Semua Beres!</p>
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
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    
    // Fungsi reusable untuk konfirmasi SweetAlert
    function confirmAction(selector, title, text, btnColor, btnText) {
        document.querySelectorAll(selector).forEach(form => {
            form.addEventListener('submit', function(e) {
                e.preventDefault();
                Swal.fire({
                    title: title,
                    text: text,
                    icon: 'question',
                    showCancelButton: true,
                    confirmButtonColor: btnColor,
                    cancelButtonColor: '#6b7280', // gray-500
                    confirmButtonText: btnText,
                    cancelButtonText: 'Batal'
                }).then((result) => {
                    if (result.isConfirmed) this.submit();
                });
            });
        });
    }

    // Bind ke Form Sales
    confirmAction('.form-approve-sales', 'Setujui Penerimaan?', 'Dana akan masuk ke kas/bank dan status menjadi Completed.', '#16a34a', 'Ya, Setujui!');
    confirmAction('.form-reject-sales', 'Tolak Penerimaan?', 'Pembayaran akan dibatalkan (Failed).', '#dc2626', 'Ya, Tolak!');

    // Bind ke Form Purchase
    confirmAction('.form-approve-purchase', 'Setujui Pengeluaran?', 'Dana akan keluar dari kas/bank dan status menjadi Completed.', '#16a34a', 'Ya, Setujui!');
    confirmAction('.form-reject-purchase', 'Tolak Pengeluaran?', 'Pembayaran akan dibatalkan (Failed).', '#dc2626', 'Ya, Tolak!');
});
</script>
@endpush
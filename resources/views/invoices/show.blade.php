@extends('layouts.app')

{{-- ✅ BLOK PHP GLOBAL --}}
@php
    $sisaTagihan = $invoice->remaining_balance;
    $totalReturDipotong = $invoice->total_deducting_returns;
    $saldoKreditKlien = $invoice->client->balance;
@endphp

@section('title', 'Detail Invoice Penjualan')

@section('content')
<div class="max-w-7xl mx-auto">
    
    {{-- HEADER HALAMAN --}}
    <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center mb-6 gap-4">
        <div>
            <div class="flex items-center gap-2 text-sm text-gray-500 mb-1">
                <a href="{{ route('invoices.index') }}" class="hover:text-indigo-600 transition">Invoice</a>
                <span>/</span>
                <span class="text-gray-800">Detail</span>
            </div>
            <h2 class="text-2xl font-bold text-gray-900 tracking-tight flex items-center gap-3">
                <span class="font-mono">{{ $invoice->invoice_number }}</span>
                
                {{-- Status Badge --}}
                @if($invoice->status == 'paid') <span class="px-2.5 py-0.5 rounded-full text-xs font-bold bg-green-100 text-green-800 border border-green-200 uppercase">Lunas</span>
                @elseif($invoice->status == 'partially_paid') <span class="px-2.5 py-0.5 rounded-full text-xs font-bold bg-blue-100 text-blue-800 border border-blue-200 uppercase">Cicil</span>
                @elseif($invoice->status == 'cancelled') <span class="px-2.5 py-0.5 rounded-full text-xs font-bold bg-red-100 text-red-800 border border-red-200 uppercase">Batal</span>
                @elseif($invoice->status == 'draft') <span class="px-2.5 py-0.5 rounded-full text-xs font-bold bg-gray-100 text-gray-600 border border-gray-200 uppercase">Draft</span>
                @elseif(optional($invoice->due_date)->isPast()) <span class="px-2.5 py-0.5 rounded-full text-xs font-bold bg-red-50 text-red-600 border border-red-200 uppercase">Jatuh Tempo</span>
                @else <span class="px-2.5 py-0.5 rounded-full text-xs font-bold bg-yellow-100 text-yellow-800 border border-yellow-200 uppercase">Belum Lunas</span>
                @endif
            </h2>
        </div>
        
        <div class="flex flex-wrap gap-3">
            <a href="{{ route('invoices.index') }}" class="px-4 py-2 bg-white border border-gray-300 rounded-lg text-gray-700 text-sm font-medium hover:bg-gray-50 transition shadow-sm">
                Kembali
            </a>

            {{-- TOMBOL KONFIRMASI (Draft Only) --}}
            @if($invoice->status == 'draft')
                <form id="confirm-form-show" action="{{ route('invoices.confirm', $invoice->invoice_id) }}" method="POST">
                    @csrf
                    <button type="submit" class="px-4 py-2 bg-green-600 hover:bg-green-700 text-white text-sm font-bold rounded-lg shadow-md transition flex items-center gap-2">
                        <i class="bi bi-check-circle-fill"></i> Konfirmasi Invoice
                    </button>
                </form>
            @endif

            {{-- TOMBOL OPSI (DROPDOWN) --}}
            <div class="relative">
                <button onclick="toggleDropdown('opsi-dropdown-invoice')" class="px-4 py-2 bg-white border border-gray-300 rounded-lg text-gray-700 text-sm font-medium hover:bg-gray-50 transition shadow-sm flex items-center gap-2 focus:ring-2 focus:ring-indigo-500 focus:outline-none">
                    <i class="bi bi-gear"></i> Opsi <i class="bi bi-chevron-down text-xs"></i>
                </button>
                
                <div id="opsi-dropdown-invoice" class="hidden absolute right-0 mt-2 w-56 bg-white rounded-lg shadow-xl border border-gray-200 z-50 origin-top-right">
                    <div class="py-1">
                        @if(!in_array($invoice->status, ['paid', 'cancelled']))
                            <a href="{{ route('invoices.edit', $invoice->invoice_id) }}" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100 hover:text-indigo-600">
                                <i class="bi bi-pencil-square mr-2"></i> Edit Invoice
                            </a>
                        @endif
                        
                        <a href="{{ route('invoice-adjustments.create') }}?sales_invoice_id={{ $invoice->invoice_id }}" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100 hover:text-indigo-600 border-t border-gray-100">
                            <i class="bi bi-file-earmark-diff mr-2"></i> Buat Penyesuaian
                        </a>
                        
                        <a href="{{ route('invoices.pdf', $invoice->invoice_id) }}" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100 hover:text-indigo-600">
                            <i class="bi bi-file-earmark-pdf mr-2"></i> Download PDF
                        </a>

                        @if(!in_array($invoice->status, ['draft', 'paid', 'cancelled']))
                            <form action="{{ route('invoices.cancel', $invoice->invoice_id) }}" method="POST" class="form-cancel-invoice border-t border-gray-100">
                                @csrf
                                <button type="submit" class="w-full text-left px-4 py-2 text-sm text-red-600 hover:bg-red-50 font-medium">
                                    <i class="bi bi-x-circle mr-2"></i> Batalkan Invoice
                                </button>
                            </form>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-12 gap-6">
        
        {{-- KOLOM KIRI (Span 8) --}}
        <div class="lg:col-span-8 space-y-6">
            
            {{-- CARD 1: INFO DASAR --}}
            <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
                <div class="px-6 py-4 border-b border-gray-100 bg-gray-50 flex justify-between items-center">
                    <h3 class="text-xs font-bold text-gray-500 uppercase tracking-wider flex items-center gap-2">
                        <i class="bi bi-person-vcard text-indigo-500"></i> Informasi Klien
                    </h3>
                </div>
                <div class="p-6">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div class="flex items-start gap-4">
                            <div class="w-12 h-12 rounded-full bg-indigo-50 flex items-center justify-center text-indigo-600 flex-shrink-0 border border-indigo-100">
                                <i class="bi bi-building text-xl"></i>
                            </div>
                            <div>
                                <h4 class="text-base font-bold text-gray-900">{{ $invoice->client->client_name }}</h4>
                                <p class="text-sm text-gray-500 mt-1">{{ $invoice->client->address ?? 'Alamat tidak tersedia' }}</p>
                                <div class="mt-2 text-xs text-gray-400">
                                    <i class="bi bi-telephone mr-1"></i> {{ $invoice->client->phone_number ?? '-' }}
                                </div>
                            </div>
                        </div>

                        <div class="space-y-3 border-l border-gray-100 pl-0 md:pl-6">
                            <div class="flex justify-between items-center">
                                <span class="text-xs font-medium text-gray-500 uppercase">Tgl Invoice</span>
                                <span class="text-sm font-semibold text-gray-900">{{ optional($invoice->order_date)->format('d M Y') }}</span>
                            </div>
                            <div class="flex justify-between items-center">
                                <span class="text-xs font-medium text-gray-500 uppercase">Jatuh Tempo</span>
                                <span class="text-sm font-semibold {{ optional($invoice->due_date)->isPast() && $invoice->status != 'paid' ? 'text-red-600' : 'text-gray-900' }}">
                                    {{ optional($invoice->due_date)->format('d M Y') }}
                                </span>
                            </div>
                            <div class="flex justify-between items-center">
                                <span class="text-xs font-medium text-gray-500 uppercase">Sales Person</span>
                                <span class="text-sm font-medium text-gray-700">{{ $invoice->sales->full_name ?? '-' }}</span>
                            </div>
                        </div>
                    </div>
                    
                    @if($invoice->notes)
                        <div class="mt-6 p-3 bg-yellow-50 border border-yellow-100 rounded-lg text-sm text-yellow-800 italic flex gap-2">
                            <i class="bi bi-sticky mt-0.5"></i>
                            <div><span class="font-bold">Catatan:</span> {{ $invoice->notes }}</div>
                        </div>
                    @endif
                </div>
            </div>

            {{-- CARD 2: ITEM --}}
            <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
                <div class="px-6 py-4 border-b border-gray-100 flex justify-between items-center">
                    <h3 class="text-xs font-bold text-gray-500 uppercase tracking-wider">Rincian Produk</h3>
                    <span class="px-2 py-1 bg-gray-100 text-gray-600 text-xs rounded-md font-bold">{{ $invoice->items->count() }} Item</span>
                </div>
                <div class="overflow-x-auto">
                    <table class="w-full text-left border-collapse">
                        <thead class="bg-gray-50 border-b border-gray-200">
                            <tr>
                                <th class="px-6 py-3 text-xs font-bold text-gray-500 uppercase w-10 text-center">No</th>
                                <th class="px-6 py-3 text-xs font-bold text-gray-500 uppercase">Produk</th>
                                <th class="px-6 py-3 text-xs font-bold text-gray-500 uppercase text-center">Qty</th>
                                <th class="px-6 py-3 text-xs font-bold text-gray-500 uppercase text-right">Harga (@)</th>
                                <th class="px-6 py-3 text-xs font-bold text-gray-500 uppercase text-right">Subtotal</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            @foreach($invoice->items as $item)
                            <tr class="hover:bg-gray-50 transition">
                                <td class="px-6 py-4 text-center text-sm text-gray-500">{{ $loop->iteration }}</td>
                                <td class="px-6 py-4">
                                    <div class="text-sm font-bold text-gray-900">{{ $item->product->product_name ?? 'Produk Dihapus' }}</div>
                                </td>
                                <td class="px-6 py-4 text-center">
                                    <span class="px-2.5 py-1 rounded-md bg-gray-100 text-xs font-bold text-gray-700 border border-gray-200">
                                        {{ $item->quantity }}
                                    </span>
                                </td>
                                <td class="px-6 py-4 text-right text-sm text-gray-600">
                                    Rp {{ number_format($item->price_per_unit, 0, ',', '.') }}
                                </td>
                                <td class="px-6 py-4 text-right text-sm font-bold text-gray-900">
                                    Rp {{ number_format($item->subtotal, 0, ',', '.') }}
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>

            {{-- CARD 3: PEMBAYARAN --}}
            @if($invoice->payments->isNotEmpty())
            <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
                <div class="px-6 py-4 border-b border-gray-100 bg-blue-50">
                    <h3 class="text-xs font-bold text-blue-700 uppercase tracking-wider flex items-center gap-2">
                        <i class="bi bi-wallet2"></i> Riwayat Pembayaran
                    </h3>
                </div>
                <table class="w-full text-left border-collapse">
                    <thead class="bg-white border-b border-gray-200">
                        <tr>
                            <th class="px-6 py-3 text-xs font-bold text-gray-500 uppercase">Tanggal</th>
                            <th class="px-6 py-3 text-xs font-bold text-gray-500 uppercase">Metode</th>
                            <th class="px-6 py-3 text-xs font-bold text-gray-500 uppercase text-right">Jumlah</th>
                            <th class="px-6 py-3 text-xs font-bold text-gray-500 uppercase text-center">Status</th>
                            <th class="px-6 py-3 text-xs font-bold text-gray-500 uppercase text-right">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        @foreach($invoice->payments as $payment)
                        <tr class="hover:bg-gray-50 transition">
                            <td class="px-6 py-3 text-sm text-gray-600">{{ $payment->payment_date->format('d/m/Y') }}</td>
                            <td class="px-6 py-3 text-sm text-gray-600">
                                {{ $payment->paymentMethod->name ?? '-' }}
                                @if($payment->reference_number) <br><span class="text-xs text-gray-400">Ref: {{ $payment->reference_number }}</span> @endif
                            </td>
                            <td class="px-6 py-3 text-right text-sm font-bold text-green-600">
                                Rp {{ number_format($payment->amount, 0, ',', '.') }}
                            </td>
                            <td class="px-6 py-3 text-center">
                                @if($payment->status == 'completed') <i class="bi bi-check-circle-fill text-green-500" title="Selesai"></i>
                                @elseif($payment->status == 'pending_verification') <i class="bi bi-clock-fill text-yellow-500" title="Menunggu Verifikasi"></i>
                                @else <span class="text-xs text-gray-400">{{ $payment->status }}</span>
                                @endif
                            </td>
                            <td class="px-6 py-3 text-right">
                                <form action="{{ route('payments.destroy', $payment->payment_id) }}" method="POST" class="form-delete-payment inline-block">
                                    @csrf @method('DELETE')
                                    <button type="submit" class="text-red-500 hover:text-red-700 transition"><i class="bi bi-trash"></i></button>
                                </form>
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            @endif

        </div>

        {{-- KOLOM KANAN (Span 4) --}}
        <div class="lg:col-span-4 space-y-6">
            
            <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6 sticky top-6">
                <h3 class="text-sm font-bold text-gray-900 mb-4 flex items-center gap-2 border-b border-gray-100 pb-3">
                    <i class="bi bi-calculator text-indigo-500"></i> Ringkasan Tagihan
                </h3>

                <div class="space-y-3 text-sm text-gray-600 mb-4 border-b border-dashed border-gray-200 pb-4">
                    <div class="flex justify-between">
                        <span>Subtotal</span>
                        <span class="font-medium text-gray-900">Rp {{ number_format($invoice->subtotal, 0, ',', '.') }}</span>
                    </div>
                    @if($invoice->discount_amount > 0)
                    <div class="flex justify-between text-red-500">
                        <span>Diskon ({{ $invoice->discount_percentage }}%)</span>
                        <span>(-) Rp {{ number_format($invoice->discount_amount, 0, ',', '.') }}</span>
                    </div>
                    @endif
                    
                    @foreach($invoice->taxes as $tax)
                    <div class="flex justify-between">
                        <span>{{ $tax->pivot->name }} ({{ $tax->pivot->rate }}%)</span>
                        <span>(+) Rp {{ number_format($tax->pivot->amount, 0, ',', '.') }}</span>
                    </div>
                    @endforeach

                    @php $totalAdditional = $invoice->additionalCosts->sum('amount'); @endphp
                    @if($totalAdditional > 0)
                    <div class="flex justify-between text-gray-800">
                        <span>Biaya Tambahan</span>
                        <span>(+) Rp {{ number_format($totalAdditional, 0, ',', '.') }}</span>
                    </div>
                    @endif
                </div>

                <div class="flex justify-between items-center mb-6">
                    <span class="text-sm font-bold text-gray-900 uppercase">Total Tagihan</span>
                    <span class="text-xl font-bold text-indigo-600">Rp {{ number_format($invoice->total_amount, 0, ',', '.') }}</span>
                </div>

                {{-- STATUS BAYAR --}}
                <div class="bg-gray-50 rounded-lg p-4 border border-gray-200 mb-6">
                    <h4 class="text-xs font-bold text-gray-400 uppercase mb-3 tracking-wider">Status Pembayaran</h4>
                    
                    <div class="flex justify-between text-xs text-green-600 mb-2 border-b border-gray-200 pb-2">
                        <span>Sudah Dibayar</span>
                        <span>(-) Rp {{ number_format($invoice->amount_paid, 0, ',', '.') }}</span>
                    </div>
                    
                    @if($totalReturDipotong > 0)
                    <div class="flex justify-between text-xs text-yellow-600 mb-2 border-b border-gray-200 pb-2">
                        <span>Retur (Potong Tagihan)</span>
                        <span>(-) Rp {{ number_format($totalReturDipotong, 0, ',', '.') }}</span>
                    </div>
                    @endif

                    <div class="flex justify-between items-center">
                        <span class="text-xs font-bold text-gray-700">SISA TAGIHAN</span>
                        <span class="text-lg font-bold {{ $sisaTagihan > 0.01 ? 'text-red-600' : 'text-green-600' }}">
                            Rp {{ number_format($sisaTagihan, 0, ',', '.') }}
                        </span>
                    </div>
                </div>

                {{-- AKSI UTAMA --}}
                @if(!in_array($invoice->status, ['cancelled', 'draft']) && $sisaTagihan > 0.01)
                    <button type="button" onclick="openModal('paymentModal')" class="w-full py-3 bg-green-600 hover:bg-green-700 text-white text-sm font-bold rounded-lg shadow-md transition flex justify-center items-center gap-2 group">
                        <i class="bi bi-cash-coin group-hover:scale-110 transition-transform"></i> Catat Pembayaran
                    </button>
                @endif

            </div>
        </div>

    </div>
</div>

{{-- MODAL PEMBAYARAN (TAILWIND) --}}
<div id="paymentModal" class="relative z-[100] hidden" aria-labelledby="modal-title" role="dialog" aria-modal="true">
    <div class="fixed inset-0 bg-gray-900 bg-opacity-50 transition-opacity backdrop-blur-sm"></div>
    <div class="fixed inset-0 z-10 w-screen overflow-y-auto">
        <div class="flex min-h-full items-center justify-center p-4 text-center sm:p-0">
            <div class="relative transform overflow-hidden rounded-xl bg-white text-left shadow-xl transition-all sm:my-8 sm:w-full sm:max-w-lg">
                <div class="bg-white px-4 pb-4 pt-5 sm:p-6 sm:pb-4 border-b border-gray-100">
                    <h3 class="text-lg font-bold leading-6 text-gray-900">Catat Pembayaran</h3>
                </div>
                
                <form action="{{ route('payments.store', $invoice->invoice_id) }}" method="POST" enctype="multipart/form-data">
                    @csrf
                    <div class="p-6 space-y-4">
                        
                        {{-- Info Sisa --}}
                        <div class="bg-blue-50 border border-blue-100 rounded-lg p-3 text-sm flex justify-between text-blue-800 font-bold">
                            <span>Sisa Tagihan:</span>
                            <span>Rp {{ number_format($sisaTagihan, 0, ',', '.') }}</span>
                        </div>

                        @if($saldoKreditKlien > 0)
                        <div class="bg-green-50 border border-green-100 rounded-lg p-3 text-sm">
                            <div class="flex justify-between font-bold text-green-800">
                                <span>Saldo Kredit Tersedia:</span>
                                <span>Rp {{ number_format($saldoKreditKlien, 0, ',', '.') }}</span>
                            </div>
                            <div class="mt-2 flex items-center gap-2">
                                <input class="rounded border-gray-300 text-indigo-600 focus:ring-indigo-500 cursor-pointer" type="checkbox" id="modal-use-credit" name="use_credit" value="1">
                                <label class="text-xs font-medium text-gray-700 cursor-pointer" for="modal-use-credit">Gunakan Saldo Kredit</label>
                            </div>
                        </div>
                        @endif

                        <div>
                            <label class="block text-xs font-bold text-gray-700 uppercase mb-1">Jumlah Bayar</label>
                            <input type="text" class="w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 text-end font-bold text-lg" id="amount-formatted" required>
                            <input type="hidden" name="amount" id="amount">
                        </div>

                        <div>
                            <label class="block text-xs font-bold text-gray-700 uppercase mb-1">Tanggal</label>
                            <input type="date" class="w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 text-sm" name="payment_date" value="{{ now()->format('Y-m-d') }}" required>
                        </div>

                        <div>
                            <label class="block text-xs font-bold text-gray-700 uppercase mb-1">Metode Pembayaran</label>
                            <select class="w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 text-sm" id="payment_method" name="payment_method_id" required>
                                <option value="">-- Pilih Metode --</option>
                                @foreach($paymentMethods as $method)
                                    <option value="{{ $method->payment_method_id }}" data-config="{{ $method->required_fields_config }}">{{ $method->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        
                        <div>
                            <label class="block text-xs font-bold text-gray-700 uppercase mb-1">Masuk ke Akun</label>
                            <select class="w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 text-sm" name="company_bank_account_id" required>
                                <option value="">-- Pilih Akun Kas/Bank --</option>
                                @foreach($companyBankAccounts as $account)
                                    <option value="{{ $account->company_bank_account_id }}">{{ $account->bank_name }} - {{ $account->account_number }}</option>
                                @endforeach
                            </select>
                        </div>

                        <div id="payment-reference-group" style="display: none;">
                            <label class="block text-xs font-bold text-gray-700 uppercase mb-1">Nomor Referensi</label>
                            <input type="text" class="w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 text-sm" name="reference_number">
                        </div>
                        
                        <div id="payment-proof-group" style="display: none;">
                            <label class="block text-xs font-bold text-gray-700 uppercase mb-1">Bukti Pembayaran</label>
                            <input type="file" class="w-full text-sm text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded-md file:border-0 file:text-xs file:font-semibold file:bg-indigo-50 file:text-indigo-700 hover:file:bg-indigo-100" name="proof_of_payment">
                        </div>

                        <div>
                            <label class="block text-xs font-bold text-gray-700 uppercase mb-1">Catatan</label>
                            <textarea class="w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 text-sm" name="notes" rows="2"></textarea>
                        </div>
                    </div>
                    <div class="bg-gray-50 px-4 py-3 sm:flex sm:flex-row-reverse sm:px-6 border-t border-gray-100">
                        <button type="submit" class="inline-flex w-full justify-center rounded-md bg-indigo-600 px-3 py-2 text-sm font-semibold text-white shadow-sm hover:bg-indigo-500 sm:ml-3 sm:w-auto">Simpan</button>
                        <button type="button" class="mt-3 inline-flex w-full justify-center rounded-md bg-white px-3 py-2 text-sm font-semibold text-gray-900 shadow-sm ring-1 ring-inset ring-gray-300 hover:bg-gray-50 sm:mt-0 sm:w-auto" onclick="closeModal('paymentModal')">Batal</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/autonumeric@4.6.0/dist/autoNumeric.min.js"></script>
<script>
    // Fungsi Toggle Dropdown
    function toggleDropdown(id) {
        const el = document.getElementById(id);
        if (el.classList.contains('hidden')) {
            el.classList.remove('hidden');
        } else {
            el.classList.add('hidden');
        }
    }

    // Menutup dropdown saat klik di luar area
    window.addEventListener('click', function(e) {
        const btn = document.querySelector('button[onclick="toggleDropdown(\'opsi-dropdown-invoice\')"]');
        const dropdown = document.getElementById('opsi-dropdown-invoice');
        
        if (btn && dropdown && !btn.contains(e.target) && !dropdown.contains(e.target)) {
            dropdown.classList.add('hidden');
        }
    });

    function openModal(id) { document.getElementById(id).classList.remove('hidden'); }
    function closeModal(id) { document.getElementById(id).classList.add('hidden'); }

    document.addEventListener('DOMContentLoaded', function() {
        
        // Konfirmasi Aksi
        function confirmAction(selector, title, text, btnColor = '#d33') {
            document.querySelectorAll(selector).forEach(form => {
                form.addEventListener('submit', function(e) {
                    e.preventDefault();
                    Swal.fire({
                        title: title,
                        text: text,
                        icon: 'warning',
                        showCancelButton: true,
                        confirmButtonColor: btnColor, // Warna tombol konfirmasi
                        cancelButtonColor: '#6b7280', // Warna tombol batal (Gray)
                        confirmButtonText: 'Ya, Lanjutkan!',
                        cancelButtonText: 'Batal',
                        reverseButtons: true, // Posisi tombol dibalik agar lebih UX friendly
                        customClass: {
                            container: 'z-[9999]', // Memastikan Swal muncul di atas Modal (Z-Index Tinggi)
                            popup: 'rounded-xl shadow-xl',
                            confirmButton: 'px-4 py-2 rounded-lg font-bold',
                            cancelButton: 'px-4 py-2 rounded-lg font-medium'
                        }
                    }).then((res) => { 
                        if (res.isConfirmed) e.target.submit(); 
                    });
                });
            });
        }
        confirmAction('.form-cancel-invoice', 'Batalkan Invoice?', 'Status invoice akan berubah menjadi Cancelled.');
        confirmAction('#confirm-form-show', 'Konfirmasi Invoice?', 'Stok akan dikurangi.', '#198754');
        confirmAction('.form-cancel-adjustment', 'Hapus Penyesuaian?', 'Data akan dihapus permanen.');
        confirmAction('.form-delete-payment', 'Hapus Pembayaran?', 'Pembayaran dibatalkan.');

        // AutoNumeric Modal
        const amountInput = document.getElementById('amount-formatted');
        if(amountInput) {
            const an = new AutoNumeric(amountInput, { decimalCharacter: ',', digitGroupSeparator: '.', decimalPlaces: 0, minimumValue: 0 });
            amountInput.addEventListener('autoNumeric:rawValueModified', e => {
                document.getElementById('amount').value = e.detail.newRawValue;
            });
        }

        // Toggle Method Fields
        const methodSelect = document.getElementById('payment_method');
        if(methodSelect) {
            methodSelect.addEventListener('change', function() {
                const config = this.options[this.selectedIndex].dataset.config;
                document.getElementById('payment-reference-group').style.display = (config === 'reference_only' || config === 'proof_and_reference') ? 'block' : 'none';
                document.getElementById('payment-proof-group').style.display = (config === 'proof_only' || config === 'proof_and_reference') ? 'block' : 'none';
            });
        }
    });
</script>
@endpush
@extends('layouts.app')

@section('title', 'Detail Invoice Penjualan')

{{-- PHP Variables --}}
@php
    $sisaTagihan = $invoice->remaining_balance;
    $totalReturDipotong = $invoice->total_deducting_returns;
    $saldoKreditKlien = $invoice->client->balance;
@endphp

@section('content')
<div class="max-w-7xl mx-auto pb-20 animate-enter">
    
    {{-- HEADER --}}
    <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center mb-6 gap-4">
        <div>
            <nav class="flex items-center gap-2 text-sm text-slate-500 mb-1">
                <a href="{{ route('invoices.index') }}" class="hover:text-indigo-600 transition-colors">Invoice</a>
                <span class="mx-2 text-slate-300">/</span>
                <span class="text-slate-800 font-semibold">Detail</span>
            </nav>
            <h1 class="text-2xl font-bold text-slate-800 tracking-tight flex items-center gap-3">
                <span class="font-mono text-indigo-600 bg-indigo-50 px-2 rounded">{{ $invoice->invoice_number }}</span>
                
                {{-- Status Badge --}}
                @php
                    $statusData = match($invoice->status) {
                        'paid' => ['class' => 'status-completed', 'label' => 'Lunas'],
                        'partially_paid' => ['class' => 'status-approved', 'label' => 'Cicilan'],
                        'cancelled' => ['class' => 'status-rejected', 'label' => 'Batal'],
                        'draft' => ['class' => 'status-draft', 'label' => 'Draft'],
                        default => ['class' => 'status-pending', 'label' => 'Belum Lunas']
                    };
                    // Override Overdue
                    if(optional($invoice->due_date)->isPast() && $invoice->status != 'paid' && $invoice->status != 'cancelled') {
                        $statusData = ['class' => 'bg-red-100 text-red-800 border-red-200', 'label' => 'Jatuh Tempo'];
                    }
                @endphp
                <span class="px-3 py-1 rounded-full text-xs font-bold uppercase border {{ $statusData['class'] }}">
                    {{ $statusData['label'] }}
                </span>
            </h1>
        </div>
        
        <div class="flex flex-wrap gap-3">
            <a href="{{ route('invoices.index') }}" class="h-[48px] px-6 bg-white border border-slate-300 rounded-lg text-slate-700 text-sm font-bold hover:bg-slate-50 transition shadow-sm flex items-center justify-center">
                Kembali
            </a>

            @if($invoice->status == 'draft')
                <form id="confirm-form-show" action="{{ route('invoices.confirm', $invoice->invoice_id) }}" method="POST" class="form-confirm">
                    @csrf
                    <button type="submit" 
                            data-title="Konfirmasi Invoice?" 
                            data-text="Stok akan dikurangi dan piutang akan dicatat." 
                            data-btn-text="Ya, Konfirmasi"
                            data-btn-color="#10b981"
                            data-icon="check"
                            class="h-[48px] px-6 bg-emerald-600 hover:bg-emerald-700 text-white text-sm font-bold rounded-lg shadow-md transition flex items-center gap-2">
                        <i class="material-icons text-[18px]">check_circle</i> Konfirmasi
                    </button>
                </form>
            @endif

            {{-- OPSI DROPDOWN --}}
            <div class="relative group">
                <button class="h-[48px] px-6 bg-white border border-slate-300 rounded-lg text-slate-700 text-sm font-bold hover:bg-slate-50 transition shadow-sm flex items-center gap-2 focus:outline-none">
                    <i class="material-icons text-[18px]">settings</i> Opsi <i class="material-icons text-[16px]">expand_more</i>
                </button>
                
                <div class="hidden group-hover:block absolute right-0 mt-0 w-56 bg-white rounded-lg shadow-xl border border-slate-100 z-50 overflow-hidden animate-enter">
                    <div class="py-1">
                        @if(!in_array($invoice->status, ['paid', 'cancelled']))
                            <a href="{{ route('invoices.edit', $invoice->invoice_id) }}" class="flex items-center px-4 py-3 text-sm text-slate-700 hover:bg-slate-50 hover:text-indigo-600 transition">
                                <i class="material-icons text-lg mr-3 text-slate-400">edit</i> Edit Invoice
                            </a>
                        @endif
                        
                        <a href="{{ route('invoice-adjustments.create') }}?sales_invoice_id={{ $invoice->invoice_id }}" class="flex items-center px-4 py-3 text-sm text-slate-700 hover:bg-slate-50 hover:text-indigo-600 transition border-t border-slate-50">
                            <i class="material-icons text-lg mr-3 text-slate-400">difference</i> Penyesuaian
                        </a>
                        
                        <a href="{{ route('invoices.pdf', $invoice->invoice_id) }}" class="flex items-center px-4 py-3 text-sm text-slate-700 hover:bg-slate-50 hover:text-indigo-600 transition border-t border-slate-50">
                            <i class="material-icons text-lg mr-3 text-slate-400">picture_as_pdf</i> Download PDF
                        </a>

                        @if(!in_array($invoice->status, ['draft', 'paid', 'cancelled']))
                            <form action="{{ route('invoices.cancel', $invoice->invoice_id) }}" method="POST" class="form-confirm border-t border-slate-50">
                                @csrf
                                <button type="submit" 
                                        data-title="Batalkan Invoice?" 
                                        data-text="Status akan berubah menjadi Cancelled." 
                                        data-btn-text="Ya, Batalkan" 
                                        data-btn-color="#ef4444"
                                        data-icon="warning"
                                        class="w-full text-left flex items-center px-4 py-3 text-sm text-red-600 hover:bg-red-50 transition font-medium">
                                    <i class="material-icons text-lg mr-3">cancel</i> Batalkan
                                </button>
                            </form>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-12 gap-8">
        
        {{-- KOLOM KIRI (Span 8) --}}
        <div class="lg:col-span-8 space-y-8">
            
            {{-- CARD INFO --}}
            <div class="dashboard-card p-0 overflow-hidden shadow-lg border-0 ring-1 ring-slate-900/5">
                <div class="px-6 py-4 border-b border-slate-100 bg-slate-50/50 flex items-center gap-3">
                    <div class="w-8 h-8 rounded-lg bg-indigo-100 flex items-center justify-center text-indigo-600">
                        <i class="material-icons text-[20px]">assignment_ind</i>
                    </div>
                    <h3 class="text-sm font-bold text-slate-700 uppercase tracking-wide">Informasi Klien</h3>
                </div>
                <div class="p-6 grid grid-cols-1 md:grid-cols-2 gap-8">
                    <div class="flex items-start gap-4">
                        <div class="w-12 h-12 bg-indigo-50 rounded-full flex items-center justify-center text-indigo-600 text-xl font-bold border border-indigo-100">
                            {{ substr($invoice->client->client_name, 0, 1) }}
                        </div>
                        <div>
                            <h4 class="text-base font-bold text-slate-900">{{ $invoice->client->client_name }}</h4>
                            <p class="text-sm text-slate-500 mt-1">{{ $invoice->client->address ?? 'Alamat tidak tersedia' }}</p>
                            <div class="mt-2 text-xs text-slate-400 flex items-center gap-1">
                                <i class="material-icons text-[14px]">call</i> {{ $invoice->client->phone_number ?? '-' }}
                            </div>
                        </div>
                    </div>
                    <div class="space-y-3 border-l border-slate-100 pl-0 md:pl-8">
                        <div class="flex justify-between">
                            <span class="text-xs font-bold text-slate-500 uppercase">Tanggal</span>
                            <span class="text-sm font-bold text-slate-800">{{ optional($invoice->order_date)->format('d M Y') }}</span>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-xs font-bold text-slate-500 uppercase">Jatuh Tempo</span>
                            <span class="text-sm font-bold {{ optional($invoice->due_date)->isPast() && $invoice->status != 'paid' ? 'text-red-600' : 'text-slate-800' }}">
                                {{ optional($invoice->due_date)->format('d M Y') }}
                            </span>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-xs font-bold text-slate-500 uppercase">Sales</span>
                            <span class="text-sm text-slate-700">{{ $invoice->sales->full_name ?? '-' }}</span>
                        </div>
                    </div>
                </div>
                @if($invoice->notes)
                    <div class="px-6 py-4 bg-yellow-50 border-t border-yellow-100 flex gap-3 text-sm text-yellow-800 italic">
                        <i class="material-icons text-yellow-600 text-lg">sticky_note_2</i>
                        "{{ $invoice->notes }}"
                    </div>
                @endif
            </div>

            {{-- CARD ITEM --}}
            <div class="dashboard-card p-0 overflow-hidden shadow-lg border-0 ring-1 ring-slate-900/5">
                <div class="px-6 py-4 border-b border-slate-100 bg-slate-50/50 flex justify-between items-center">
                    <h3 class="text-sm font-bold text-slate-700 uppercase tracking-wide flex items-center gap-2">
                        <i class="material-icons text-indigo-600 text-[20px]">shopping_cart</i> Rincian Produk
                    </h3>
                    <span class="bg-white border border-slate-200 px-2 py-0.5 rounded text-xs font-bold text-slate-500">{{ $invoice->items->count() }} Item</span>
                </div>
                <div class="overflow-x-auto">
                    <table class="dashboard-table min-w-full">
                        <thead class="bg-white border-b border-slate-200">
                            <tr>
                                <th class="pl-6 w-10 text-center">No</th>
                                <th>Produk</th>
                                <th class="text-center">Qty</th>
                                <th class="text-right">Harga (@)</th>
                                <th class="text-right pr-6">Subtotal</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            @foreach($invoice->items as $item)
                            <tr class="hover:bg-slate-50 transition">
                                <td class="pl-6 py-4 text-center text-sm text-slate-500">{{ $loop->iteration }}</td>
                                <td class="py-4">
                                    <div class="text-sm font-bold text-slate-800">{{ $item->product->product_name ?? 'Produk Dihapus' }}</div>
                                </td>
                                <td class="py-4 text-center">
                                    <span class="bg-slate-100 text-slate-700 px-2.5 py-0.5 rounded text-xs font-bold border border-slate-200">
                                        {{ $item->quantity }}
                                    </span>
                                </td>
                                <td class="py-4 text-right text-sm text-slate-600 font-mono">Rp {{ number_format($item->price_per_unit, 0, ',', '.') }}</td>
                                <td class="pr-6 py-4 text-right text-sm font-bold text-slate-900 font-mono">Rp {{ number_format($item->subtotal, 0, ',', '.') }}</td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>

            {{-- CARD PEMBAYARAN --}}
            @if($invoice->payments->isNotEmpty())
            <div class="dashboard-card p-0 overflow-hidden shadow-lg border-0 ring-1 ring-slate-900/5">
                <div class="px-6 py-4 border-b border-blue-100 bg-blue-50 flex items-center gap-2">
                    <i class="material-icons text-blue-600 text-[20px]">payments</i>
                    <h3 class="text-sm font-bold text-blue-800 uppercase tracking-wide">Riwayat Pembayaran</h3>
                </div>
                <table class="dashboard-table min-w-full">
                    <thead class="bg-white border-b border-slate-200">
                        <tr>
                            <th class="pl-6">Tanggal</th>
                            <th>Metode</th>
                            <th class="text-right">Jumlah</th>
                            <th class="text-center">Status</th>
                            <th class="text-center pr-6">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @foreach($invoice->payments as $payment)
                        <tr class="hover:bg-slate-50 transition">
                            <td class="pl-6 py-4 text-sm text-slate-600">{{ $payment->payment_date->format('d/m/Y') }}</td>
                            <td class="py-4 text-sm text-slate-600">
                                <span class="font-bold text-indigo-600">{{ $payment->paymentMethod->name ?? '-' }}</span>
                                @if($payment->reference_number) <div class="text-xs text-slate-400 font-mono mt-0.5">{{ $payment->reference_number }}</div> @endif
                            </td>
                            <td class="py-4 text-right text-sm font-bold text-green-600 font-mono">
                                Rp {{ number_format($payment->amount, 0, ',', '.') }}
                            </td>
                            <td class="py-4 text-center">
                                @if($payment->status == 'completed') 
                                    <i class="material-icons text-green-500 text-[18px]" title="Selesai">check_circle</i>
                                @elseif($payment->status == 'pending_verification') 
                                    <i class="material-icons text-amber-500 text-[18px]" title="Menunggu Verifikasi">hourglass_top</i>
                                @else 
                                    <span class="text-xs text-slate-400">{{ $payment->status }}</span>
                                @endif
                            </td>
                            <td class="pr-6 py-4 text-center">
                                <form action="{{ route('payments.destroy', $payment->payment_id) }}" method="POST" class="form-confirm inline-block">
                                    @csrf @method('DELETE')
                                    <button type="submit" 
                                            data-title="Batalkan Pembayaran?" 
                                            data-text="Pembayaran sebesar <b>Rp {{ number_format($payment->amount, 0, ',', '.') }}</b> akan dibatalkan." 
                                            data-btn-color="#ef4444"
                                            class="text-slate-400 hover:text-red-600 transition p-1" title="Batalkan">
                                        <i class="material-icons text-[18px]">cancel</i>
                                    </button>
                                </form>
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            @endif

        </div>

        {{-- KOLOM KANAN (SUMMARY) --}}
        <div class="lg:col-span-4 space-y-6">
            
            <div class="dashboard-card p-6 shadow-xl sticky top-6 border-t-4 border-indigo-500">
                <h3 class="card-title mb-6 border-b border-slate-100 pb-3 flex items-center gap-2">
                    <i class="material-icons text-indigo-600">calculate</i> Ringkasan Tagihan
                </h3>

                <div class="space-y-3 text-sm text-slate-600 mb-4 border-b border-dashed border-slate-200 pb-4">
                    <div class="flex justify-between">
                        <span>Subtotal</span>
                        <span class="font-medium text-slate-900">Rp {{ number_format($invoice->subtotal, 0, ',', '.') }}</span>
                    </div>
                    @if($invoice->discount_amount > 0)
                    <div class="flex justify-between text-red-500">
                        <span>Diskon ({{ $invoice->discount_percentage }}%)</span>
                        <span>- Rp {{ number_format($invoice->discount_amount, 0, ',', '.') }}</span>
                    </div>
                    @endif
                    
                    @foreach($invoice->taxes as $tax)
                    <div class="flex justify-between text-slate-700">
                        <span>{{ $tax->pivot->name }} ({{ $tax->pivot->rate }}%)</span>
                        <span>+ Rp {{ number_format($tax->pivot->amount, 0, ',', '.') }}</span>
                    </div>
                    @endforeach

                    @php $totalAdditional = $invoice->additionalCosts->sum('amount'); @endphp
                    @if($totalAdditional > 0)
                    <div class="flex justify-between text-slate-700">
                        <span>Biaya Tambahan</span>
                        <span>+ Rp {{ number_format($totalAdditional, 0, ',', '.') }}</span>
                    </div>
                    @endif
                </div>

                <div class="flex justify-between items-center mb-6">
                    <span class="text-sm font-bold text-slate-900 uppercase">Total Tagihan</span>
                    <span class="text-xl font-bold text-indigo-600 font-mono">Rp {{ number_format($invoice->total_amount, 0, ',', '.') }}</span>
                </div>

                {{-- STATUS PEMBAYARAN --}}
                <div class="bg-slate-50 rounded-lg p-4 border border-slate-200 mb-6">
                    <h4 class="text-xs font-bold text-slate-400 uppercase mb-3 tracking-wider">Status Pembayaran</h4>
                    
                    <div class="flex justify-between text-xs text-green-600 mb-2 border-b border-slate-200 pb-2 font-medium">
                        <span>Sudah Dibayar</span>
                        <span class="font-mono">- Rp {{ number_format($invoice->amount_paid, 0, ',', '.') }}</span>
                    </div>
                    
                    @if($totalReturDipotong > 0)
                    <div class="flex justify-between text-xs text-amber-600 mb-2 border-b border-slate-200 pb-2 font-medium">
                        <span>Retur (Potong Tagihan)</span>
                        <span class="font-mono">- Rp {{ number_format($totalReturDipotong, 0, ',', '.') }}</span>
                    </div>
                    @endif

                    <div class="flex justify-between items-center mt-3">
                        <span class="text-xs font-bold text-slate-700">SISA TAGIHAN</span>
                        <span class="text-lg font-bold font-mono {{ $sisaTagihan > 0.01 ? 'text-red-600' : 'text-green-600' }}">
                            Rp {{ number_format($sisaTagihan, 0, ',', '.') }}
                        </span>
                    </div>
                </div>

                {{-- TOMBOL BAYAR --}}
                @if(!in_array($invoice->status, ['cancelled', 'draft']) && $sisaTagihan > 0.01)
                    <button type="button" onclick="openModal('paymentModal')" class="w-full h-[48px] bg-emerald-600 hover:bg-emerald-700 text-white text-sm font-bold rounded-lg shadow-md transition flex justify-center items-center gap-2 group hover:-translate-y-0.5">
                        <i class="material-icons text-[20px]">payments</i> Catat Pembayaran
                    </button>
                @endif

            </div>

        </div>

    </div>
</div>

{{-- MODAL PEMBAYARAN (CLEAN UI) --}}
<div id="paymentModal" class="fixed inset-0 z-[100] hidden" role="dialog" aria-modal="true">
    {{-- Backdrop --}}
    <div class="fixed inset-0 bg-slate-900/50 backdrop-blur-sm transition-opacity" onclick="document.getElementById('paymentModal').classList.add('hidden')"></div>
    
    <div class="flex min-h-full items-center justify-center p-4">
        <div class="relative transform overflow-hidden rounded-xl bg-white text-left shadow-2xl transition-all w-full max-w-lg border border-slate-100">
            
            {{-- Modal Header --}}
            <div class="bg-white px-6 py-4 border-b border-slate-100 flex justify-between items-center">
                <h3 class="text-lg font-bold text-slate-800">Catat Pembayaran</h3>
                <button type="button" class="text-slate-400 hover:text-slate-600 transition" onclick="document.getElementById('paymentModal').classList.add('hidden')">
                    <i class="material-icons">close</i>
                </button>
            </div>
            
            <form action="{{ route('payments.store', $invoice->invoice_id) }}" method="POST" enctype="multipart/form-data">
                @csrf
                <div class="p-6 space-y-5">
                    
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
                            <input class="rounded border-slate-300 text-emerald-600 focus:ring-emerald-500 cursor-pointer" type="checkbox" id="modal-use-credit" name="use_credit" value="1">
                            <label class="text-xs font-medium text-slate-700 cursor-pointer select-none" for="modal-use-credit">Gunakan Saldo Kredit</label>
                        </div>
                    </div>
                    @endif

                    <div>
                        <label class="block text-xs font-bold text-slate-700 uppercase mb-1.5">Jumlah Bayar</label>
                        <input type="text" class="form-input input-currency text-right font-bold text-lg text-slate-800" id="amount-formatted" required>
                        <input type="hidden" name="amount" id="amount">
                    </div>

                    <div>
                        <label class="block text-xs font-bold text-slate-700 uppercase mb-1.5">Tanggal</label>
                        <input type="date" class="form-input text-sm" name="payment_date" value="{{ now()->format('Y-m-d') }}" required>
                    </div>

                    <div>
                        <label class="block text-xs font-bold text-slate-700 uppercase mb-1.5">Metode Pembayaran</label>
                        <select class="form-input select2-basic text-sm" id="payment_method" name="payment_method_id" required>
                            <option value="">-- Pilih Metode --</option>
                            @foreach($paymentMethods as $method)
                                <option value="{{ $method->payment_method_id }}" data-config="{{ $method->required_fields_config }}">{{ $method->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    
                    <div>
                        <label class="block text-xs font-bold text-slate-700 uppercase mb-1.5">Masuk ke Akun</label>
                        <select class="form-input select2-basic text-sm" name="company_bank_account_id" required>
                            <option value="">-- Pilih Akun Kas/Bank --</option>
                            @foreach($companyBankAccounts as $account)
                                <option value="{{ $account->company_bank_account_id }}">{{ $account->bank_name }} - {{ $account->account_number }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div id="payment-reference-group" style="display: none;">
                        <label class="block text-xs font-bold text-slate-700 uppercase mb-1.5">Nomor Referensi</label>
                        <input type="text" class="form-input text-sm" name="reference_number">
                    </div>
                    
                    <div id="payment-proof-group" style="display: none;">
                        <label class="block text-xs font-bold text-slate-700 uppercase mb-1.5">Bukti Pembayaran</label>
                        <input type="file" class="block w-full text-sm text-slate-500 file:mr-4 file:py-2 file:px-4 file:rounded-lg file:border-0 file:text-xs file:font-bold file:bg-indigo-50 file:text-indigo-700 hover:file:bg-indigo-100 transition cursor-pointer" name="proof_of_payment">
                    </div>

                    <div>
                        <label class="block text-xs font-bold text-slate-700 uppercase mb-1.5">Catatan</label>
                        <textarea class="form-textarea text-sm" name="notes" rows="2"></textarea>
                    </div>
                </div>
                
                <div class="bg-slate-50 px-6 py-4 flex flex-row-reverse gap-2 border-t border-slate-100">
                    <button type="submit" class="inline-flex w-full justify-center rounded-lg bg-indigo-600 px-4 py-2.5 text-sm font-bold text-white shadow-md hover:bg-indigo-700 sm:w-auto transition">Simpan</button>
                    <button type="button" class="mt-3 inline-flex w-full justify-center rounded-lg bg-white px-4 py-2.5 text-sm font-bold text-slate-700 shadow-sm ring-1 ring-inset ring-slate-300 hover:bg-slate-50 sm:mt-0 sm:w-auto transition" onclick="document.getElementById('paymentModal').classList.add('hidden')">Batal</button>
                </div>
            </form>
        </div>
    </div>
</div>

@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/autonumeric@4.6.0/dist/autoNumeric.min.js"></script>
<script>
    // Fungsi Modal Global (jika belum ada di app.js, tambahkan ini)
    window.openModal = function(id) { document.getElementById(id).classList.remove('hidden'); }
    window.closeModal = function(id) { document.getElementById(id).classList.add('hidden'); }
    
    // Dropdown Opsi
    window.toggleDropdown = function(id) {
        const el = document.getElementById(id);
        if (el.classList.contains('hidden')) el.classList.remove('hidden');
        else el.classList.add('hidden');
    }

    window.onclick = function(event) {
        if (!event.target.matches('button') && !event.target.matches('.bi-gear')) {
             const dropdowns = document.querySelectorAll("[id^='opsi-dropdown']");
             dropdowns.forEach(d => d.classList.add('hidden'));
        }
    }

    document.addEventListener('DOMContentLoaded', function() {
        // Init Select2
        $('.select2-basic').select2({ width: '100%', dropdownCssClass: 'select2-dropdown-clean', placeholder: '-- Pilih --' });

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
            // Gunakan event select2:select jika menggunakan select2
            $(methodSelect).on('select2:select', function(e) {
                 const selected = e.params.data.element;
                 const config = selected.dataset.config;
                 
                 document.getElementById('payment-reference-group').style.display = (config === 'reference_only' || config === 'proof_and_reference') ? 'block' : 'none';
                 document.getElementById('payment-proof-group').style.display = (config === 'proof_only' || config === 'proof_and_reference') ? 'block' : 'none';
            });
        }

        @if(session('success')) window.showToast("{{ session('success') }}", 'success'); @endif
        @if(session('error')) window.showToast("{{ session('error') }}", 'error'); @endif
    });
</script>
@endpush